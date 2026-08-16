<?php
declare(strict_types=1);

namespace Atelier\Controllers;

use Atelier\Admin;
use Atelier\Content;
use Atelier\Customers;
use Atelier\Galleries;
use Atelier\I18n;
use Atelier\Images;
use Atelier\Invitations;
use Atelier\Media;
use Atelier\Security;
use Atelier\View;

/**
 * Der Kundenreiter.
 *
 * Hier läuft zusammen, was sonst an drei Stellen läge: der Zugang zur Galerie,
 * die Bilder, die Auswahl des Paares und der Gutschein für die digitale
 * Einladung. Ein Kunde anlegen legt die Galerie mit an – im Betrieb gibt es
 * das eine nie ohne das andere.
 */
final class CustomerAdminController
{
    private const TAB = '/kunden';

    public function __construct(private readonly string $locale)
    {
        Admin::requireLogin($this->locale);
    }

    /* --------------------------------- Liste -------------------------------- */

    public function index(): void
    {
        $error = '';

        if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
            Admin::checkCsrfOrFail();

            if (Security::clean($_POST['was'] ?? '', 20) === 'kampagne') {
                $this->saveCampaign();
                Admin::back($this->locale, self::TAB);
            }

            $result = Customers::create([
                'couple'        => $_POST['couple'] ?? '',
                'code'          => $_POST['code'] ?? '',
                'password'      => $_POST['password'] ?? '',
                'email'         => $_POST['email'] ?? '',
                'phone'         => $_POST['phone'] ?? '',
                'date'          => $_POST['date'] ?? '',
                'expires'       => $_POST['expires'] ?? '',
                'venue'         => $_POST['venue'] ?? '',
                'packageName'   => $_POST['packageName'] ?? '',
                'amount'        => $_POST['amount'] ?? '',
                'notes'         => $_POST['notes'] ?? '',
                'coupon'        => $_POST['coupon'] ?? '',
                'couponOnce'    => isset($_POST['couponOnce']),
                'couponExpires' => $_POST['couponExpires'] ?? '',
            ]);

            if ($result['ok']) {
                // Direkt in die neue Akte: dort stehen Zugangsdaten und Gutschein.
                header('Location: ' . I18n::path('/admin/kunden/' . $result['code'], $this->locale) . '?gespeichert=neu', true, 303);
                exit;
            }

            $error = (string) ($result['reason'] ?? '');
        }

        $customers = Customers::all();
        $galleries = [];
        foreach (Galleries::all() as $gallery) {
            $galleries[(string) ($gallery['code'] ?? '')] = $gallery;
        }
        $selections = [];
        foreach (\Atelier\Db::jsonList('SELECT data FROM selections') as $selection) {
            $selections[(string) ($selection['code'] ?? '')] = $selection;
        }

        $rows = [];
        foreach ($customers as $customer) {
            $code = (string) $customer['code'];
            $gallery = $galleries[$code] ?? null;
            $selection = $selections[$code] ?? null;

            $rows[] = [
                'customer'  => $customer,
                'photos'    => count((array) ($gallery['uploads'] ?? [])) + count((array) ($gallery['seeds'] ?? [])),
                'selection' => $selection,
            ];
        }

        $this->render('admin/customers', [
            'active'   => array_values(array_filter($rows, static fn (array $r): bool => $r['customer']['status'] !== 'archived')),
            'archived' => array_values(array_filter($rows, static fn (array $r): bool => $r['customer']['status'] === 'archived')),
            'campaign' => Content::get('campaign'),
            'error'    => $error,
        ]);
    }

    /* --------------------------------- Akte --------------------------------- */

    /** @param array<string,string> $params */
    public function show(array $params): void
    {
        $code = Customers::code($params['code'] ?? '');
        $customer = Customers::find($code);

        if ($customer === null) {
            http_response_code(404);
            $this->render('admin/customer-missing', ['code' => $code]);
            return;
        }

        if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
            Admin::checkCsrfOrFail();
            $this->apply($customer);
        }

        $gallery = Galleries::find($code);
        $selection = Galleries::selection($code);

        // Nur die Einladungen, die mit dem Gutschein dieses Kunden entstanden.
        $usedFor = [];
        foreach ($customer['coupon']['usedFor'] as $use) {
            $slug = (string) ($use['slug'] ?? '');
            $invitation = Invitations::find($slug);
            $usedFor[] = [
                'slug'    => $slug,
                'at'      => (string) ($use['at'] ?? ''),
                'couple'  => $invitation === null
                    ? ''
                    : trim((string) ($invitation['bride'] ?? '') . ' & ' . (string) ($invitation['groom'] ?? ''), ' &'),
                'rsvps'   => count(Invitations::rsvps($slug)),
                'exists'  => $invitation !== null,
            ];
        }

        $this->render('admin/customer', [
            'customer'  => $customer,
            'gallery'   => $gallery,
            'selection' => $selection,
            'photos'    => $gallery === null ? [] : Galleries::photos($gallery),
            'usedFor'   => $usedFor,
        ], '/kunden/' . $code);
    }

    /**
     * Eine Änderung an der Akte ausführen und danach umleiten.
     *
     * @param array<string,mixed> $customer
     */
    private function apply(array $customer): void
    {
        $code = (string) $customer['code'];
        $tab = '/kunden/' . $code;

        match (Security::clean($_POST['was'] ?? '', 20)) {
            'daten'         => $this->saveData($customer),
            'gutschein'     => $this->saveCoupon($customer),
            'gutschein-neu' => Customers::saveCoupon($code, ['code' => Customers::randomCoupon()]),
            'gutschein-frei' => Customers::resetCoupon($code),
            'archivieren'   => Customers::update($code, ['status' => 'archived']),
            'aktivieren'    => Customers::update($code, ['status' => 'active']),
            // Link fuer den Albumhersteller – erzeugen und wieder abschalten.
            'freigabe'      => Galleries::shareCreate($code),
            'freigabe-aus'  => Galleries::shareRevoke($code),
            'fotos'         => Galleries::addPhotos($code, Media::storeMany('fotos', 'galerien/' . $code, 60)),
            'foto-loeschen' => Galleries::removePhoto($code, (int) Security::clean($_POST['foto'] ?? '', 6)),
            'loeschen'      => $this->remove($customer),
            default         => null,
        };

        Admin::back($this->locale, $tab);
    }

    /** @param array<string,mixed> $customer */
    private function saveData(array $customer): void
    {
        $code = (string) $customer['code'];
        $password = Security::clean($_POST['password'] ?? '', 64);

        $patch = [
            'couple'      => Security::clean($_POST['couple'] ?? '', 120) ?: $customer['couple'],
            'email'       => Security::clean($_POST['email'] ?? '', 160),
            'phone'       => Security::clean($_POST['phone'] ?? '', 60),
            'date'        => Customers::date($_POST['date'] ?? ''),
            'venue'       => Security::clean($_POST['venue'] ?? '', 160),
            'packageName' => Security::clean($_POST['packageName'] ?? '', 120),
            'amount'      => Security::clean($_POST['amount'] ?? '', 40),
            'notes'       => Security::clean($_POST['notes'] ?? '', 2000),
        ];

        // Leeres Passwortfeld heißt „unverändert lassen“.
        if ($password !== '') {
            $patch['password'] = $password;
        }

        Customers::update($code, $patch);

        // Was zur Galerie gehört, gehört nicht in die Kundenakte.
        Galleries::update($code, [
            'date'     => $patch['date'],
            'venue'    => $patch['venue'],
            'expires'  => Customers::date($_POST['expires'] ?? ''),
            'videoUrl' => Security::clean($_POST['video'] ?? '', 300),
        ]);
    }

    /** @param array<string,mixed> $customer */
    private function saveCoupon(array $customer): void
    {
        Customers::saveCoupon((string) $customer['code'], [
            'code'    => mb_strtoupper(Security::clean($_POST['coupon'] ?? '', 40)) ?: $customer['coupon']['code'],
            'active'  => isset($_POST['couponActive']),
            'once'    => isset($_POST['couponOnce']),
            'expires' => Customers::date($_POST['couponExpires'] ?? ''),
        ]);
    }

    /** Kunde und Galerie löschen – nur wenn der Anmeldename getippt wurde. @param array<string,mixed> $customer */
    private function remove(array $customer): void
    {
        $code = (string) $customer['code'];
        if (Customers::code(Security::clean($_POST['confirm'] ?? '', 64)) !== $code) {
            return;
        }

        Customers::delete($code);
        header('Location: ' . I18n::path('/admin/kunden', $this->locale) . '?gespeichert=geloescht', true, 303);
        exit;
    }

    /* -------------------------------- Aktion -------------------------------- */

    /** Der allgemeine Aktionscode liegt bei den Inhalten, nicht beim Kunden. */
    private function saveCampaign(): void
    {
        $code = mb_strtoupper(Security::clean($_POST['campaignCode'] ?? '', 40));
        $active = isset($_POST['campaignActive']);

        Content::mutate(static function (array $content) use ($code, $active): array {
            $content['campaign'] = ['code' => $code, 'active' => $active];
            return $content;
        });
    }

    /* --------------------------------- Gerüst ------------------------------- */

    /** @param array<string,mixed> $data */
    private function render(string $template, array $data, string $tab = self::TAB): void
    {
        View::page($template, array_merge([
            'layout'  => 'admin/layout',
            'locale'  => $this->locale,
            'path'    => I18n::path('/admin' . $tab),
            'current' => self::TAB,
            'meta'    => ['title' => 'Admin', 'noindex' => true],
            'csrf'    => Security::csrf(),
            'blur'    => Images::BLUR,
        ], $data));
    }
}
