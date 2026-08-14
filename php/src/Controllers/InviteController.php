<?php
declare(strict_types=1);

namespace Atelier\Controllers;

use Atelier\Config;
use Atelier\Content;
use Atelier\Db;
use Atelier\Dates;
use Atelier\I18n;
use Atelier\Invitations;
use Atelier\Media;
use Atelier\Paypal;
use Atelier\Pricing;
use Atelier\Security;
use Atelier\Seo;
use Atelier\Themes;
use Atelier\View;

/**
 * Der Einladungs-Assistent und die fertige Einladung.
 *
 * Bewusst ein normales Formular statt einer Anwendung im Browser: Die Schritte
 * blendet ein Skript ein und aus, abgeschickt wird einmal. Ohne JavaScript
 * stehen alle Schritte untereinander und es funktioniert trotzdem – auf einem
 * Handy im Hochzeitstrubel ist das kein theoretischer Vorteil.
 */
final class InviteController
{
    private const MAX_PHOTOS = 4;

    /* ------------------------------ Assistent ------------------------------- */

    public function wizard(): void
    {
        $locale = I18n::locale();
        $error = '';
        $done = null;
        $draftLink = '';

        if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
            $was = (string) ($_POST['was'] ?? 'create');

            if (!Security::checkCsrf($_POST['csrf'] ?? null)) {
                $error = 'csrf';
            } elseif ($was === 'draft') {
                $draftLink = $this->saveDraft();
            } else {
                $result = $this->create();
                if (isset($result['error'])) {
                    $error = (string) $result['error'];
                } else {
                    $done = $result;
                }
            }
        }

        // Fortsetzungslink: gespeicherten Stand wieder einsetzen
        $token = Security::clean($_GET['taslak'] ?? '', 40);
        $draft = $token !== '' ? Invitations::draft($token) : null;
        $values = is_array($draft['data'] ?? null) ? $draft['data'] : [];

        View::page('pages/invite-wizard', [
            'locale'   => $locale,
            'path'     => I18n::path('/einladung'),
            'meta'     => Seo::forPage('einladung', ['scripts' => ['/assets/invite.js']]),
            'themes'   => Themes::all(),
            'campaign' => Content::get('campaign'),
            'sections' => Pricing::defaultSections(),
            'values'   => $values,
            'token'    => $token,
            'csrf'     => Security::csrf(),
            'error'    => $error,
            'done'     => $done,
            'draftLink' => $draftLink,
        ]);
    }

    /**
     * Einladung anlegen.
     *
     * @return array<string,mixed>
     */
    private function create(): array
    {
        if (Security::throttle('invite-create', 8, 900)) {
            return ['error' => 'throttle'];
        }

        $bride = Security::clean($_POST['bride'] ?? '', 40);
        $groom = Security::clean($_POST['groom'] ?? '', 40);
        $events = $this->readEvents();

        if ($bride === '' || $groom === '' || $events === [] || ($events[0]['date'] ?? '') === '') {
            return ['error' => 'fields'];
        }

        $slug = Invitations::slug(Security::clean($_POST['slug'] ?? '', 60));
        if ($slug === '') {
            $slug = Invitations::slug($bride . '-' . $groom);
        }
        if ($slug === '') {
            $slug = 'einladung-' . bin2hex(random_bytes(3));
        }
        if (!Invitations::slugAvailable($slug)) {
            $slug .= '-' . bin2hex(random_bytes(2));
        }

        $sections = $this->readSections();
        $couponInput = Security::clean($_POST['coupon'] ?? '', 60);
        $coupon = Invitations::checkCoupon($couponInput);
        $free = (bool) $coupon['ok'];

        $themeIds = array_map(static fn (array $t): string => (string) $t['id'], Themes::all());
        $theme = Security::clean($_POST['theme'] ?? '', 40);
        if (!in_array($theme, $themeIds, true)) {
            $theme = $themeIds[0] ?? 'elysee';
        }

        $eventType = Security::clean($_POST['eventType'] ?? '', 20);
        if (!in_array($eventType, Invitations::EVENT_TYPES, true)) {
            $eventType = 'wedding';
        }

        $invitation = [
            'slug'      => $slug,
            'bride'     => $bride,
            'groom'     => $groom,
            'eventType' => $eventType,
            'events'    => $events,
            'message'   => Security::clean($_POST['message'] ?? '', 600),
            'closing'   => Security::clean($_POST['closing'] ?? '', 300),
            'families'  => !empty($sections['family'])
                ? [
                    'bride' => Security::clean($_POST['familyBride'] ?? '', 60),
                    'groom' => Security::clean($_POST['familyGroom'] ?? '', 60),
                ]
                : null,
            'photos'    => Media::storeMany('photos', 'einladungen/' . $slug, self::MAX_PHOTOS),
            'program'   => !empty($sections['program']) ? $this->readProgram() : [],
            'menu'      => !empty($sections['menu']) ? $this->readLines('menu', 12, 80) : [],
            'musicUrl'  => !empty($sections['music']) ? Security::clean($_POST['musicUrl'] ?? '', 300) : '',
            'videoUrl'  => !empty($sections['video']) ? Security::clean($_POST['videoUrl'] ?? '', 300) : '',
            'sections'  => $sections,
            'hashtag'   => Security::clean($_POST['hashtag'] ?? '', 60),
            'theme'     => $theme,
            'locale'    => I18n::locale(),
            'paid'      => $free,
            'price'     => Pricing::total($sections, count($events) > 1, $free),
            'createdAt' => date('c'),
        ];

        Invitations::create($invitation);

        // Erst jetzt verbrauchen – ein Abbruch vorher kostet den Code nicht.
        if ($free && ($coupon['kind'] ?? '') === 'customer') {
            Invitations::redeemCoupon($couponInput, $slug);
        }

        $token = Security::clean($_POST['token'] ?? '', 40);
        if ($token !== '') {
            Invitations::deleteDraft($token);
        }

        return [
            'slug'  => $slug,
            'path'  => I18n::path('/einladung/' . $slug),
            'url'   => Config::url() . I18n::path('/einladung/' . $slug),
            'price' => $invitation['price'],
            'free'  => $free,
        ];
    }

    /** Zwischenstand sichern und den Fortsetzungslink zurückgeben. */
    private function saveDraft(): string
    {
        $token = Security::clean($_POST['token'] ?? '', 40);
        if ($token === '') {
            $token = bin2hex(random_bytes(10));
        }

        // Nur Formularwerte, keine Dateien – Bilder werden erst beim Anlegen
        // übernommen.
        $data = [];
        foreach ($_POST as $key => $value) {
            if (in_array($key, ['csrf', 'was', 'token'], true) || !is_string($value)) {
                continue;
            }
            $data[$key] = Security::clean($value, 2000);
        }

        $label = trim(Security::clean($_POST['bride'] ?? '', 40) . ' & ' . Security::clean($_POST['groom'] ?? '', 40), ' &');
        Invitations::saveDraft($token, $label, $data);

        return Config::url() . I18n::path('/einladung') . '?taslak=' . $token;
    }

    /** @return list<array<string,string>> */
    private function readEvents(): array
    {
        $events = [];
        for ($i = 0; $i < 2; $i++) {
            $date = Security::clean($_POST["event{$i}_date"] ?? '', 20);
            $venue = Security::clean($_POST["event{$i}_venue"] ?? '', 120);

            if ($date === '' && $venue === '') {
                continue;
            }

            $events[] = [
                'name'    => Security::clean($_POST["event{$i}_name"] ?? '', 60),
                'date'    => $date,
                'time'    => Security::clean($_POST["event{$i}_time"] ?? '', 10),
                'venue'   => $venue,
                'address' => Security::clean($_POST["event{$i}_address"] ?? '', 160),
            ];
        }

        return $events;
    }

    /** @return array<string,bool> */
    private function readSections(): array
    {
        $sections = [];
        foreach (Pricing::SECTION_KEYS as $key) {
            $sections[$key] = isset($_POST['section_' . $key]);
        }
        return $sections;
    }

    /** @return list<array{time:string,title:string}> */
    private function readProgram(): array
    {
        $out = [];
        foreach ($this->readLines('program', 12, 120) as $line) {
            $parts = array_map('trim', explode('|', $line, 2));
            $out[] = ['time' => mb_substr($parts[0] ?? '', 0, 10), 'title' => mb_substr($parts[1] ?? '', 0, 100)];
        }
        return $out;
    }

    /** @return list<string> */
    private function readLines(string $field, int $max, int $length): array
    {
        $raw = Security::clean($_POST[$field] ?? '', $max * $length);
        $lines = array_values(array_filter(array_map('trim', explode("\n", $raw)), static fn (string $l): bool => $l !== ''));
        return array_slice(array_map(static fn (string $l): string => mb_substr($l, 0, $length), $lines), 0, $max);
    }

    /* ------------------------- Gutschein (Zwischenruf) ---------------------- */

    public function checkCoupon(): void
    {
        header('Content-Type: application/json; charset=UTF-8');

        if (Security::throttle('coupon', 12, 60)) {
            http_response_code(429);
            echo json_encode(['ok' => false, 'reason' => 'throttled']);
            return;
        }

        $body = json_decode(file_get_contents('php://input') ?: '', true);
        $code = Security::clean(is_array($body) ? ($body['code'] ?? '') : ($_POST['code'] ?? ''), 60);
        $result = Invitations::checkCoupon($code);

        // Zu wem ein Code gehört, geht den Browser nichts an.
        echo json_encode(['ok' => $result['ok'], 'reason' => $result['reason'] ?? null]);
    }

    /* --------------------------- Fertige Einladung -------------------------- */

    /** @param array<string,string> $params */
    public function show(array $params): void
    {
        $invitation = Invitations::find($params['slug'] ?? '');
        if ($invitation === null) {
            (new PageController())->notFound(I18n::locale());
            return;
        }

        $theme = Themes::find((string) ($invitation['theme'] ?? '')) ?? Themes::all()[0];
        $sent = false;

        if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
            $sent = $this->saveRsvp((string) $invitation['slug']);
        }

        $events = (array) ($invitation['events'] ?? []);
        $first = (array) ($events[0] ?? []);
        $names = (string) $invitation['bride'] . ' & ' . (string) $invitation['groom'];

        View::page('pages/invitation', [
            'locale' => I18n::locale(),
            'path'   => I18n::path('/einladung/' . $invitation['slug']),
            'meta'   => [
                'title'       => $names,
                'description' => Security::clean((string) ($invitation['message'] ?? ''), 160),
                'noindex'     => true,
                'scripts'     => ['/assets/invitation.js'],
            ],
            'invitation' => $invitation,
            'theme'      => Themes::complete($theme),
            'style'      => Themes::styleBlock($theme),
            'dateLong'   => Dates::long((string) ($first['date'] ?? '')),
            'weekday'    => Dates::weekday((string) ($first['date'] ?? '')),
            'rsvps'      => Invitations::rsvps((string) $invitation['slug']),
            'sent'       => $sent,
            'csrf'       => Security::csrf(),
        ]);
    }

    private function saveRsvp(string $slug): bool
    {
        if (!Security::checkCsrf($_POST['csrf'] ?? null) || Security::throttle('rsvp-' . $slug, 20, 600)) {
            return false;
        }

        $name = Security::clean($_POST['name'] ?? '', 60);
        if ($name === '') {
            return false;
        }

        Invitations::addRsvp($slug, [
            'slug'   => $slug,
            'name'   => $name,
            'coming' => (string) ($_POST['coming'] ?? '1') === '1',
            'count'  => max(1, min(20, (int) ($_POST['count'] ?? 1))),
            'note'   => Security::clean($_POST['note'] ?? '', 300),
            'at'     => date('c'),
        ]);

        return true;
    }

    /* -------------------------------- Zahlung ------------------------------- */

    /** @param array<string,string> $params */
    public function payment(array $params): void
    {
        $slug = Invitations::slug($params['slug'] ?? '');
        $invitation = Invitations::find($slug);
        $back = I18n::path('/einladung/' . $slug);

        if ($invitation === null) {
            (new PageController())->notFound(I18n::locale());
            return;
        }

        // Rückkehr von PayPal: Zahlung einziehen und Einladung freischalten.
        $orderId = Security::clean($_GET['token'] ?? '', 60);
        if ($orderId !== '') {
            if (Paypal::captureOrder($orderId)) {
                Invitations::update($slug, ['paid' => true, 'paymentRef' => $orderId]);
                header('Location: ' . $back . '?bezahlt=1', true, 303);
                exit;
            }
            header('Location: ' . $back . '?abbruch=1', true, 303);
            exit;
        }

        if (!empty($invitation['paid']) || (int) ($invitation['price'] ?? 0) === 0) {
            header('Location: ' . $back, true, 303);
            exit;
        }

        if (!Paypal::isConfigured()) {
            header('Location: ' . $back . '?zahlung=offen', true, 303);
            exit;
        }

        // Betrag serverseitig neu berechnen – nie aus dem Datensatz übernehmen.
        $amount = Pricing::total(
            (array) ($invitation['sections'] ?? []),
            count((array) ($invitation['events'] ?? [])) > 1,
            false
        );

        $order = Paypal::createOrder(
            (float) $amount,
            $slug,
            'Digitale Einladung ' . $invitation['bride'] . ' & ' . $invitation['groom'],
            Config::url() . I18n::path('/einladung/' . $slug . '/zahlung'),
            Config::url() . $back . '?abbruch=1'
        );

        if ($order === null || $order['approveUrl'] === '') {
            header('Location: ' . $back . '?zahlung=fehler', true, 303);
            exit;
        }

        Db::run(
            'INSERT INTO payments (slug, orderid, data) VALUES (?, ?, ?)',
            [$slug, $order['id'], Db::encode(['slug' => $slug, 'orderId' => $order['id'], 'amount' => $amount, 'at' => date('c')])]
        );

        header('Location: ' . $order['approveUrl'], true, 303);
        exit;
    }
}
