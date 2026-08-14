<?php
declare(strict_types=1);

namespace Atelier\Controllers;

use Atelier\Admin;
use Atelier\Content;
use Atelier\Db;
use Atelier\Galleries;
use Atelier\I18n;
use Atelier\Integrations;
use Atelier\Leads;
use Atelier\Paypal;
use Atelier\Security;
use Atelier\View;

/**
 * Der Adminbereich.
 *
 * Jede Seite prüft zuerst die Anmeldung, dann bei POST das CSRF-Token, ändert
 * die Daten und leitet auf sich selbst um. Das „Post-Redirect-Get“ ist keine
 * Förmlichkeit: sonst schickt ein Neuladen dasselbe Formular noch einmal ab.
 */
final class AdminController
{
    public function __construct(private readonly string $locale)
    {
        Admin::requireLogin($this->locale);
    }

    /* ------------------------------- Übersicht ------------------------------ */

    public function overview(): void
    {
        $galleries = Galleries::all();
        $selections = Db::jsonList('SELECT data FROM selections ORDER BY at DESC');
        $invitations = Db::jsonList('SELECT data FROM invitations ORDER BY created_at DESC');
        $rsvps = Db::jsonList('SELECT data FROM rsvps ORDER BY at DESC');
        $customers = Db::jsonList('SELECT data FROM customers ORDER BY created_at DESC');

        $this->render('admin/overview', '', [
            'leads'       => Leads::all(30),
            'selections'  => $selections,
            'galleries'   => $galleries,
            'invitations' => $invitations,
            'rsvps'       => $rsvps,
            'customers'   => $customers,
        ]);
    }

    /* ----------------------------- Integrationen ---------------------------- */

    public function integrations(): void
    {
        $test = null;

        if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
            Admin::checkCsrfOrFail();
            $was = (string) ($_POST['was'] ?? '');

            if ($was === 'paypal-test') {
                // Nicht umleiten: das Ergebnis soll direkt danebenstehen.
                $test = Paypal::test();
            } elseif ($was === 'settings') {
                $this->saveIntegrationSettings();
                Admin::back($this->locale, '/integrationen');
            } elseif ($was === 'extra-add') {
                $this->addIntegrationKey();
                Admin::back($this->locale, '/integrationen');
            } elseif ($was === 'extra-save') {
                $this->saveIntegrationKey();
                Admin::back($this->locale, '/integrationen');
            } elseif ($was === 'extra-delete') {
                $this->deleteIntegrationKey();
                Admin::back($this->locale, '/integrationen');
            }
        }

        $this->render('admin/integrations', '/integrationen', [
            'settings' => Integrations::all(),
            'test'     => $test,
        ]);
    }

    private function saveIntegrationSettings(): void
    {
        $settings = Integrations::all();

        // Leeres Geheimnisfeld heißt „unverändert lassen“.
        $keep = static fn (string $field, string $previous): string
            => Security::clean($_POST[$field] ?? '', 200) !== ''
                ? Security::clean($_POST[$field] ?? '', 200)
                : $previous;

        $settings['paypal'] = [
            'clientId'     => $keep('paypal_client_id', (string) $settings['paypal']['clientId']),
            'clientSecret' => $keep('paypal_secret', (string) $settings['paypal']['clientSecret']),
            'mode'         => Security::clean($_POST['paypal_mode'] ?? '', 10) === 'live' ? 'live' : 'sandbox',
        ];

        $settings['google'] = [
            'gaId'      => Security::clean($_POST['ga_id'] ?? '', 40),
            'gtmId'     => Security::clean($_POST['gtm_id'] ?? '', 40),
            'adsId'     => Security::clean($_POST['ads_id'] ?? '', 40),
            'adsLabels' => [
                'contact' => Security::clean($_POST['ads_label_contact'] ?? '', 60),
                'invite'  => Security::clean($_POST['ads_label_invite'] ?? '', 60),
                'phone'   => Security::clean($_POST['ads_label_phone'] ?? '', 60),
            ],
            'leadValue'     => Security::clean($_POST['ads_lead_value'] ?? '', 12),
            'currency'      => Security::clean($_POST['ads_currency'] ?? '', 8) ?: 'EUR',
            'searchConsole' => Security::clean($_POST['gsc'] ?? '', 200),
            'bing'          => Security::clean($_POST['bing'] ?? '', 200),
            'consentMode'   => isset($_POST['consent_mode']),
        ];

        $settings['meta'] = ['pixelId' => Security::clean($_POST['meta_pixel'] ?? '', 40)];

        Integrations::save($settings);
    }

    private function addIntegrationKey(): void
    {
        $settings = Integrations::all();
        $name = strtoupper(preg_replace('/[^A-Za-z0-9_]/', '_', Security::clean($_POST['name'] ?? '', 60)) ?? '');
        if ($name === '') {
            return;
        }

        $settings['extras'][] = [
            'id'     => bin2hex(random_bytes(4)),
            'label'  => Security::clean($_POST['label'] ?? '', 60) ?: $name,
            'name'   => $name,
            'value'  => Security::clean($_POST['value'] ?? '', 400),
            'secret' => isset($_POST['secret']),
            'note'   => Security::clean($_POST['note'] ?? '', 200),
        ];

        $settings['extras'] = array_slice($settings['extras'], 0, 40);
        Integrations::save($settings);
    }

    private function saveIntegrationKey(): void
    {
        $settings = Integrations::all();
        $id = Security::clean($_POST['id'] ?? '', 20);

        $settings['extras'] = array_map(static function (array $extra) use ($id): array {
            if ((string) $extra['id'] !== $id) {
                return $extra;
            }
            $value = Security::clean($_POST['value'] ?? '', 400);
            return [
                'id'     => $extra['id'],
                'label'  => Security::clean($_POST['label'] ?? '', 60) ?: $extra['label'],
                'name'   => $extra['name'],
                // Leeres Feld: bestehenden Wert behalten.
                'value'  => $value !== '' ? $value : $extra['value'],
                'secret' => (bool) ($extra['secret'] ?? true),
                'note'   => Security::clean($_POST['note'] ?? '', 200),
            ];
        }, $settings['extras']);

        Integrations::save($settings);
    }

    private function deleteIntegrationKey(): void
    {
        $settings = Integrations::all();
        $id = Security::clean($_POST['id'] ?? '', 20);
        $settings['extras'] = array_values(array_filter(
            $settings['extras'],
            static fn (array $extra): bool => (string) $extra['id'] !== $id
        ));
        Integrations::save($settings);
    }

    /* -------------------------------- Abmelden ------------------------------ */

    public function logout(): void
    {
        Admin::logout();
        header('Location: ' . I18n::path('/admin', $this->locale), true, 303);
        exit;
    }

    /* --------------------------------- Helfer ------------------------------- */

    /** @param array<string,mixed> $data */
    private function render(string $template, string $tab, array $data = []): void
    {
        View::page($template, array_merge([
            'layout'  => 'admin/layout',
            'locale'  => $this->locale,
            'path'    => I18n::path('/admin' . $tab),
            'current' => $tab,
            'meta'    => ['title' => 'Admin', 'noindex' => true],
            'csrf'    => Security::csrf(),
        ], $data));
    }
}
