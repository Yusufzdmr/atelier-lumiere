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
use Atelier\Mail;
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

        /*
         * Von der Designseite kommt das gewaehlte Thema mit: /einladung?design=…
         *
         * Geprueft wird gegen die vorhandenen Themen, nicht uebernommen wie
         * geliefert - sonst stuende eine fremde Angabe im Formular. Ein
         * gespeicherter Entwurf hat Vorrang: wer weitermacht, soll nicht
         * ploetzlich ein anderes Design haben.
         */
        $wunsch = Security::clean($_GET['design'] ?? '', 60);
        if ($wunsch !== '' && !isset($values['theme'])) {
            foreach (Themes::all() as $thema) {
                if ((string) ($thema['id'] ?? '') === $wunsch) {
                    $values['theme'] = $wunsch;
                    break;
                }
            }
        }

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
            // Die Datei liegt bei uns; eine fremde Adresse nimmt das Feld nicht
            // mehr entgegen. Siehe Media::storeAudio().
            'musicUrl'  => !empty($sections['music'])
                ? (string) (Media::storeAudio($_FILES['musicFile'] ?? [], 'einladungen/' . $slug) ?? '')
                : '',
            'videoUrl'  => !empty($sections['video']) ? Security::clean($_POST['videoUrl'] ?? '', 300) : '',
            'sections'  => $sections,
            'hashtag'   => Security::clean($_POST['hashtag'] ?? '', 60),
            // Nur fuer die beiden Links nach dem Erstellen. Ungueltiges kommt
            // gar nicht erst in den Datensatz.
            'email'     => (static function (): string {
                $mail = Security::clean($_POST['email'] ?? '', 160);
                return filter_var($mail, FILTER_VALIDATE_EMAIL) ? $mail : '';
            })(),
            'theme'     => $theme,
            // Die Kopie des Themas, wie es in diesem Moment aussieht. Was der
            // Betrieb spaeter am Thema aendert, laesst diese Karte in Ruhe.
            // Die Kopie traegt die Wahl des Paares. Leer heisst „so wie das
            // Design es vorsieht" – dann bleibt der Stand des Themas stehen.
            'themeSnapshot' => (static function () use ($theme): array {
                $snapshot = Themes::complete(Themes::find($theme) ?? []);
                foreach ([
                    'anim_intro'    => ['intro', Themes::INTROS],
                    'anim_idle'     => ['idle', Themes::IDLES],
                    'anim_card'     => ['animation', Themes::ANIMATIONS],
                    'anim_name'     => ['nameAnimation', Themes::NAME_ANIMATIONS],
                    'anim_particle' => ['particle', Themes::PARTICLES],
                    'anim_reveal'   => ['reveal', Themes::REVEALS],
                ] as $field => [$key, $allowed]) {
                    $value = Security::clean($_POST[$field] ?? '', 20);
                    if ($value !== '' && in_array($value, $allowed, true)) {
                        $snapshot[$key] = $value;
                    }
                }
                return $snapshot;
            })(),
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

        $url = Config::url() . I18n::path('/einladung/' . $slug);
        $manage = Invitations::manageUrl($invitation);
        $sent = $this->mailLinks($invitation, $url, $manage);

        return [
            'slug'   => $slug,
            'path'   => I18n::path('/einladung/' . $slug),
            'url'    => $url,
            'manage' => $manage,
            'guests' => Guests::all($slug),
            'price'  => $invitation['price'],
            'free'   => $free,
            'mailed' => $sent,
            'email'  => (string) $invitation['email'],
        ];
    }

    /**
     * Die beiden Links an das Paar schicken.
     *
     * Der Verwaltungslink ist der einzige Weg zurück zur Gästeliste – es gibt
     * kein Konto, unter dem man ihn wiederfände. Wer das Fenster schliesst,
     * ohne ihn zu kopieren, hat eine bezahlte Einladung und keinen Zugang mehr
     * dazu. Deshalb geht er hier raus, sofort nach dem Anlegen.
     *
     * @param array<string,mixed> $invitation
     */
    private function mailLinks(array $invitation, string $url, string $manage): bool
    {
        $to = (string) ($invitation['email'] ?? '');
        if ($to === '') {
            return false;
        }

        $de = (string) ($invitation['locale'] ?? 'de') === 'de';
        $names = trim((string) $invitation['bride'] . ' & ' . (string) $invitation['groom'], ' &');

        $lines = $de
            ? [
                'Eure Einladung ist fertig.',
                '',
                'Das ist der Link für eure Gäste:',
                $url,
                '',
                'Und das ist eure eigene Seite. Dort tragt ihr Namen ein, holt',
                'euch die persönlichen Links und seht, wer zugesagt hat:',
                $manage,
                '',
                'Diese zweite Adresse bitte gut aufheben und nicht mit den',
                'Einladungen weitergeben – sie gehört euch.',
                '',
                'Herzliche Grüße',
                'Atelier Lumière',
            ]
            : [
                'Your invitation is ready.',
                '',
                'This is the link for your guests:',
                $url,
                '',
                'And this is your own page. There you enter names, collect the',
                'personal links and see who has replied:',
                $manage,
                '',
                'Please keep this second address safe and do not pass it on',
                'with the invitations – it belongs to you.',
                '',
                'Warm regards',
                'Atelier Lumière',
            ];

        return Mail::send(
            $to,
            ($de ? 'Eure Einladung: ' : 'Your invitation: ') . $names,
            $lines
        );
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

            /*
             * Ohne senkrechten Strich stand frueher die ganze Zeile in der
             * Uhrzeit – und die wird auf zehn Zeichen gekuerzt. Aus
             * "16:00 Beginn" wurde "16:00 begi", und der Gast las das so auf
             * der Karte. Wer die Uhrzeit vorne hinschreibt, meint sie auch:
             * bis zum ersten Leerzeichen die Zeit, der Rest der Programmpunkt.
             */
            if (count($parts) === 1) {
                if (preg_match('/^(\d{1,2}(?:[:.]\d{2})?\s*(?:Uhr)?)\s+(\S.*)$/iu', $line, $m) === 1) {
                    $parts = [trim($m[1]), trim($m[2])];
                } elseif (preg_match('/^\d/', $line) !== 1) {
                    // Gar keine Uhrzeit? Dann ist alles der Programmpunkt und
                    // nichts wird auf zehn Zeichen gestutzt.
                    $parts = ['', $line];
                }
            }

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
                // Vollbild: kein Menue, keine Fusszeile – siehe layout.php
                'bare'        => true,
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
            'meta'       => [
                'title'   => I18n::isDe() ? 'Gästeliste' : 'Guest list',
                'noindex' => true,
                'scripts' => ['/assets/invite-manage.js'],
            ],
            'invitation' => $invitation,
            'guests'     => $guests,
            // Die Zusagen gehoeren dem Paar. Bisher standen sie nur im
            // Adminbereich – wer wissen wollte, wer kommt, musste den
            // Fotografen fragen.
            'rsvps'      => Invitations::rsvps($slug),
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
            // Eine falsch geratene Anrede in der Liste geradeziehen.
            'anrede' => (static function () use ($slug): string {
                Guests::setKind(
                    $slug,
                    Security::clean($_POST['token'] ?? '', 96),
                    Security::clean($_POST['art'] ?? '', 10)
                );
                return 'anrede';
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

        // Erkennen, Familie, Herr oder Frau – davon haengt die Anrede auf der
        // Karte ab. Was nicht in KINDS steht, laesst Guests je Zeile erkennen.
        $kind = Security::clean($_POST['art'] ?? '', 10);
        $result = Guests::addMany($slug, $names, $kind);

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

        // Kam die Antwort über einen persönlichen Link, wird sie dem Namen von
        // der Liste zugeordnet – nur so kann das Paar sehen, wer noch fehlt.
        // Ein erfundenes Kürzel bleibt draußen: geprüft wird gegen die Liste.
        $token = Security::clean($_POST['gast'] ?? '', 80);
        $guest = $token !== '' ? Guests::find($slug, $token) : null;

        Invitations::addRsvp($slug, [
            'slug'   => $slug,
            'name'   => $name,
            'guest'  => $guest !== null ? (string) $guest['token'] : '',
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

    /* ------------------------------ Designs ------------------------------ */

    /**
     * Beispieldaten fuer die Themenvorschau. Bewusst eine erfundene Feier und
     * keine echte Einladung: die echten gehoeren Paaren, stehen auf noindex
     * und sollen nicht als Werbeflaeche im Netz liegen.
     *
     * @return array<string,mixed>
     */
    private function demoInvitation(array $theme, string $locale): array
    {
        $de = $locale === 'de';

        return [
            'slug'      => 'vorschau',
            'bride'     => 'Marie',
            'groom'     => 'Jonas',
            'eventType' => 'wedding',
            'events'    => [[
                'name'    => $de ? 'Hochzeit' : 'Wedding',
                // Immer im naechsten Sommer, damit der Countdown nie abgelaufen ist.
                'date'    => (string) (((int) date('Y')) + 1) . '-06-20',
                'time'    => '15:30',
                'venue'   => 'Schloss Solitude',
                'address' => 'Solitude 1, 70197 Stuttgart',
            ]],
            'message'  => $de
                ? 'Wir möchten diesen besonderen Tag mit euch feiern.'
                : 'We would love to share this special day with you.',
            'closing'  => $de ? 'Wir freuen uns auf euch' : 'We look forward to seeing you',
            'families' => null,
            'photos'   => [],
            'program'  => [
                ['time' => '15:30', 'title' => $de ? 'Trauung' : 'Ceremony'],
                ['time' => '17:00', 'title' => $de ? 'Empfang' : 'Reception'],
                ['time' => '19:00', 'title' => $de ? 'Dinner' : 'Dinner'],
            ],
            'menu'     => [],
            'musicUrl' => '',
            'videoUrl' => '',
            'sections' => [
                'rsvp'      => true,
                'location'  => true,
                'countdown' => true,
                'program'   => true,
                'family'    => false,
                'menu'      => false,
                'music'     => false,
                'video'     => false,
            ],
            'hashtag'       => '',
            'theme'         => (string) ($theme['id'] ?? ''),
            'themeSnapshot' => $theme,
            'locale'        => $locale,
            'paid'          => true,
            'price'         => 0,
            'createdAt'     => date('c'),
            'manageKey'     => '',
            'ogImage'       => '',
        ];
    }

    /** Alle Themen nebeneinander – das Schaufenster vor dem Assistenten. */
    public function designs(): void
    {
        $locale = I18n::locale();
        $themes = array_map([Themes::class, 'complete'], Themes::all());

        View::page('pages/designs', [
            'locale' => $locale,
            'path'   => I18n::path('/designs', $locale),
            'meta'   => Seo::forPage('designs', [
                'title' => $locale === 'de'
                    ? 'Designs für digitale Hochzeitseinladungen'
                    : 'Designs for digital wedding invitations',
                'description' => $locale === 'de'
                    ? 'Alle Vorlagen für die digitale Einladung: Farbwelt, Kuvert und Siegel. Jede lässt sich vorab in Ruhe ansehen.'
                    : 'Every template for the digital invitation: colours, envelope and seal. Each one can be looked at in advance, in peace.',
                'canonical' => Config::url() . I18n::path('/designs', $locale),
            ]),
            'themes' => $themes,
            // Ein Stilblock je Thema: die Karten im Raster tragen echte Farben
            // und Schriften, keine nachgebauten Farbtupfer.
            'styles' => implode(' ', array_map([Themes::class, 'styleBlock'], $themes)),
        ]);
    }

    /** Ein Thema als vollstaendige Einladung, zum Anschauen. */
    public function designPreview(array $params): void
    {
        $wanted = (string) ($params['thema'] ?? '');
        $theme = null;
        foreach (Themes::all() as $candidate) {
            if ((string) ($candidate['id'] ?? '') === $wanted) {
                $theme = Themes::complete($candidate);
                break;
            }
        }

        if ($theme === null) {
            (new PageController())->notFound(I18n::locale());
            return;
        }

        // Aus dem Panel darf man eine noch nicht gespeicherte Kombination
        // ausprobieren: die sechs Bewegungsachsen kommen dann als Parameter
        // herein. Nur bekannte Werte werden uebernommen – was nicht in der
        // Liste steht, bleibt beim Stand des Themas.
        foreach ([
            'intro'         => Themes::INTROS,
            'idle'          => Themes::IDLES,
            'animation'     => Themes::ANIMATIONS,
            'nameAnimation' => Themes::NAME_ANIMATIONS,
            'particle'      => Themes::PARTICLES,
            'reveal'        => Themes::REVEALS,
            'scene'         => Themes::SCENES,
        ] as $field => $allowed) {
            $value = (string) ($_GET[$field] ?? '');
            if ($value !== '' && in_array($value, $allowed, true)) {
                $theme[$field] = $value;
            }
        }

        $locale = I18n::locale();
        $invitation = $this->demoInvitation($theme, $locale);
        $date = (string) ($invitation['events'][0]['date'] ?? '');

        View::page('pages/invitation', [
            'locale' => $locale,
            'path'   => I18n::path('/designs/' . (string) $theme['id'], $locale),
            'meta'   => [
                'title'    => (string) $theme['name'],
                'noindex'  => true,
                'scripts'  => ['/assets/invitation.js'],
                'bare'     => true,
            ],
            'invitation' => $invitation,
            'guest'      => null,
            'theme'      => $theme,
            'style'      => Themes::styleBlock($theme),
            'dateLong'   => Dates::long($date),
            'weekday'    => Dates::weekday($date),
            'rsvps'      => [],
            // Die Vorschau nimmt keine Zusagen entgegen; das Formular steht da,
            // damit die Karte vollstaendig aussieht.
            'sent'       => false,
            'csrf'       => Security::csrf(),
        ]);
    }

}
