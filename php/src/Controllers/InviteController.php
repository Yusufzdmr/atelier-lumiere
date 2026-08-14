<?php
declare(strict_types=1);

namespace Atelier\Controllers;

use Atelier\Config;
use Atelier\Content;
use Atelier\Db;
use Atelier\Guests;
use Atelier\Dates;
use Atelier\I18n;
use Atelier\Invitations;
use Atelier\Media;
use Atelier\OgImage;
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
            // Die Kopie des Themas, wie es in diesem Moment aussieht. Was der
            // Betrieb spaeter am Thema aendert, laesst diese Karte in Ruhe.
            'themeSnapshot' => Themes::complete(Themes::find($theme) ?? []),
            'locale'    => I18n::locale(),
            'paid'      => $free,
            'price'     => Pricing::total($sections, count($events) > 1, $free),
            'createdAt' => date('c'),
            // Statt einer Anmeldung: ein geheimer Link, unter dem das Paar
            // später Gäste nachtragen kann.
            'manageKey' => bin2hex(random_bytes(16)),
            'ogImage'   => '',
        ];

        Invitations::create($invitation);

        // Im Assistenten schon eingetippte Namen gleich anlegen.
        Guests::addMany($slug, array_slice(Guests::parse(Security::clean($_POST['guests'] ?? '', 8000)), 0, Guests::MAX));

        // Erst jetzt verbrauchen – ein Abbruch vorher kostet den Code nicht.
        if ($free && ($coupon['kind'] ?? '') === 'customer') {
            Invitations::redeemCoupon($couponInput, $slug);
        }

        $token = Security::clean($_POST['token'] ?? '', 40);
        if ($token !== '') {
            Invitations::deleteDraft($token);
        }

        return [
            'slug'   => $slug,
            'path'   => I18n::path('/einladung/' . $slug),
            'url'    => Config::url() . I18n::path('/einladung/' . $slug),
            'manage' => Invitations::manageUrl($invitation),
            'guests' => Guests::all($slug),
            'price'  => $invitation['price'],
            'free'   => $free,
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

        $slug = (string) $invitation['slug'];

        // Persönlich adressierte Fassung: gleiche Karte, andere Anrede.
        // Ein unbekannter Name führt bewusst nicht auf 404 – lieber die
        // Einladung ohne Anrede als eine Fehlerseite beim Gast.
        $guest = ($params['gast'] ?? '') !== ''
            ? Guests::find($slug, (string) $params['gast'])
            : null;

        // Nicht das heutige Thema, sondern das, mit dem sie verschickt wurde.
        $theme = Invitations::theme($invitation);
        $sent = false;

        if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
            $sent = $this->saveRsvp($slug);
        }

        $events = (array) ($invitation['events'] ?? []);
        $first = (array) ($events[0] ?? []);
        $date = (string) ($first['date'] ?? '');
        $names = trim((string) $invitation['bride'] . ' & ' . (string) $invitation['groom'], ' &');

        $path = I18n::path('/einladung/' . $slug . ($guest !== null ? '/' . $guest['token'] : ''));

        View::page('pages/invitation', [
            'locale' => I18n::locale(),
            'path'   => $path,
            'meta'   => [
                // Das ist die Zeile, die in WhatsApp fett ueber der Karte steht.
                'title'       => $names . ' – ' . Invitations::kindLabel((string) ($invitation['eventType'] ?? ''), I18n::locale()),
                'description' => $this->previewText($invitation, $date),
                'image'       => OgImage::url($invitation),
                'canonical'   => Config::url() . $path,
                'ogType'      => 'article',
                'noindex'     => true,
                'scripts'     => ['/assets/invitation.js'],
            ],
            'invitation' => $invitation,
            'guest'      => $guest,
            'theme'      => Themes::complete($theme),
            'style'      => Themes::styleBlock($theme),
            'dateLong'   => Dates::long($date),
            'weekday'    => Dates::weekday($date),
            'rsvps'      => Invitations::rsvps($slug),
            'sent'       => $sent,
            'csrf'       => Security::csrf(),
        ]);
    }

    /**
     * Der Text unter dem Vorschaubild. Datum und Ort sagen mehr als der
     * Einladungstext, der oft mit „Wir heiraten!“ anfaengt.
     *
     * @param array<string,mixed> $invitation
     */
    private function previewText(array $invitation, string $date): string
    {
        $events = (array) ($invitation['events'] ?? []);
        $first = (array) ($events[0] ?? []);

        $parts = array_values(array_filter([
            $date !== '' ? Dates::long($date) : '',
            Security::clean((string) ($first['venue'] ?? ''), 80),
        ], static fn (string $v): bool => $v !== ''));

        if ($parts !== []) {
            return implode(' · ', $parts);
        }

        return Security::clean((string) ($invitation['message'] ?? ''), 160);
    }

    /* ------------------------- Gästeliste des Paares ------------------------ */

    /**
     * Die Seite, auf der das Paar persönliche Einladungen anlegt.
     *
     * Kein Konto, keine Anmeldung: wer den geheimen Link hat, darf hier
     * arbeiten. Das ist die Abwägung – ein Passwort mehr wäre ein Passwort,
     * das im Hochzeitstrubel verloren geht, und zu holen gibt es hier nichts,
     * was nicht ohnehin an alle Gäste geschickt wird.
     *
     * @param array<string,string> $params
     */
    public function manage(array $params): void
    {
        $invitation = Invitations::find($params['slug'] ?? '');
        $key = Security::clean($_GET['schluessel'] ?? ($_POST['schluessel'] ?? ''), 64);

        // Raten kosten lassen, und einen falschen Schlüssel nicht von einer
        // nicht vorhandenen Einladung unterscheidbar machen.
        if ($invitation === null || Security::throttle('verwalten', 60, 600) || !Invitations::checkManageKey($invitation, $key)) {
            (new PageController())->notFound(I18n::locale());
            return;
        }

        $slug = (string) $invitation['slug'];
        $note = '';

        if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
            if (!Security::checkCsrf($_POST['csrf'] ?? null)) {
                http_response_code(403);
                exit('Sitzung abgelaufen. Bitte die Seite neu laden.');
            }

            $note = $this->applyGuests($slug, $invitation);

            header('Location: ' . I18n::path('/einladung/' . $slug . '/verwalten')
                . '?schluessel=' . rawurlencode($key) . ($note !== '' ? '&stand=' . $note : ''), true, 303);
            exit;
        }

        $guests = [];
        foreach (Guests::all($slug) as $guest) {
            $guests[] = $guest + ['url' => Guests::url($slug, (string) $guest['token'])];
        }

        // Nach dem Speichern neu lesen: das Vorschaubild kann sich geändert haben.
        $invitation = Invitations::find($slug) ?? $invitation;

        View::page('pages/invite-manage', [
            'locale'     => I18n::locale(),
            'path'       => I18n::path('/einladung/' . $slug . '/verwalten'),
            'meta'       => ['title' => 'Gästeliste', 'noindex' => true, 'scripts' => ['/assets/invite-manage.js']],
            'invitation' => $invitation,
            'guests'     => $guests,
            'link'       => Config::url() . I18n::path('/einladung/' . $slug),
            'manageUrl'  => Invitations::manageUrl($invitation),
            'preview'    => OgImage::url($invitation),
            'key'        => $key,
            'stand'      => Security::clean($_GET['stand'] ?? '', 40),
            'csrf'       => Security::csrf(),
        ]);
    }

    /**
     * Eine Änderung an der Gästeliste ausführen und melden, was passiert ist.
     *
     * @param array<string,mixed> $invitation
     */
    private function applyGuests(string $slug, array $invitation): string
    {
        return match (Security::clean($_POST['was'] ?? '', 20)) {
            'namen' => $this->addGuests($slug),
            'loeschen' => (static function () use ($slug): string {
                Guests::delete($slug, Security::clean($_POST['token'] ?? '', 96));
                return 'geloescht';
            })(),
            'vorschau' => $this->savePreview($slug, $invitation),
            default => '',
        };
    }

    /** Namen aus Textfeld und Datei zusammen einlesen. */
    private function addGuests(string $slug): string
    {
        $names = array_merge(
            Guests::parse(Security::clean($_POST['namen'] ?? '', 20000)),
            Guests::parseUpload('liste')
        );

        if ($names === []) {
            return 'leer';
        }

        $result = Guests::addMany($slug, $names);

        return $result['added'] > 0 ? 'plus' . $result['added'] : 'doppelt';
    }

    /** Eigenes Vorschaubild für WhatsApp – oder zurück zum berechneten. */
    private function savePreview(string $slug, array $invitation): string
    {
        $old = (string) ($invitation['ogImage'] ?? '');

        if (isset($_POST['entfernen'])) {
            if ($old !== '') {
                Media::delete($old);
            }
            Invitations::update($slug, ['ogImage' => '']);
            return 'vorschau';
        }

        $file = $_FILES['bild'] ?? null;
        if (!is_array($file) || ($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
            return 'leer';
        }

        $url = Media::store($file, 'einladungen/' . $slug);
        if ($url === null) {
            return 'leer';
        }

        if ($old !== '') {
            Media::delete($old);
        }
        Invitations::update($slug, ['ogImage' => $url]);

        return 'vorschau';
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
