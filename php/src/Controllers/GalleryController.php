<?php
declare(strict_types=1);

namespace Atelier\Controllers;

use Atelier\Content;
use Atelier\Dates;
use Atelier\Galleries;
use Atelier\I18n;
use Atelier\OgImage;
use Atelier\Security;
use Atelier\Seo;
use Atelier\View;

/**
 * Kundengalerie: Anmeldung, Ansicht, Albumauswahl.
 *
 * Die Anmeldung liegt in der Sitzung, nicht in einem eigenen Cookie je
 * Galerie – ein Paar arbeitet ohnehin nur an einer.
 */
final class GalleryController
{
    /** Einstieg mit Anmeldeformular. */
    public function index(): void
    {
        $error = '';

        if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
            $target = $this->login();
            if ($target !== null) {
                header('Location: ' . $target, true, 303);
                exit;
            }
            $error = 'wrong';
        }

        View::page('pages/gallery-login', [
            'locale' => I18n::locale(),
            'path'   => I18n::path('/galerie'),
            'meta'   => Seo::forPage('galerie', [
                'description' => I18n::t('gallery.lead'),
                'noindex'     => true,
            ]),
            'error'      => $error,
            'presetCode' => Security::clean($_POST['code'] ?? '', 64),
            'csrf'       => Security::csrf(),
        ]);
    }

    /** @param array<string,string> $params */
    public function show(array $params): void
    {
        $code = Galleries::normalize($params['code'] ?? '');
        $gallery = Galleries::find($code);

        if ($gallery === null) {
            (new PageController())->notFound(I18n::locale());
            return;
        }

        // Anmeldung direkt auf der Galerie-Adresse
        if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
            $target = $this->login();
            if ($target !== null) {
                header('Location: ' . $target, true, 303);
                exit;
            }
        }

        if (!$this->isAuthorized($code)) {
            View::page('pages/gallery-login', [
                'locale' => I18n::locale(),
                'path'   => I18n::path('/galerie/' . $code),
                'meta'   => array_merge([
                    'title'   => (string) ($gallery['couple'] ?? ''),
                    'noindex' => true,
                ], $this->shareMeta($gallery)),
                'error'      => ($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST' ? 'wrong' : '',
                'presetCode' => $code,
                'couple'     => (string) ($gallery['couple'] ?? ''),
                'csrf'       => Security::csrf(),
            ]);
            return;
        }

        $photos = Galleries::photos($gallery);
        $selection = Galleries::selection($code);

        View::page('pages/gallery', [
            'locale' => I18n::locale(),
            'path'   => I18n::path('/galerie/' . $code),
            'meta'   => array_merge([
                'title'   => (string) ($gallery['couple'] ?? ''),
                'noindex' => true,
                'scripts' => ['/assets/gallery.js'],
            ], $this->shareMeta($gallery)),
            'gallery'            => $gallery,
            'photos'             => $photos,
            'selection'          => $selection,
            'preferencesFilled'  => Galleries::preferences($code) !== null,
            'readOnly'           => $this->isGuest($code),
            'dateLong'           => Dates::long((string) ($gallery['date'] ?? '')),
            'csrf'               => Security::csrf(),
        ]);
    }

    /**
     * Zweiter Reiter der Galerie: Bearbeitungsstil und Fragebogen.
     *
     * Anmeldung läuft wie bei show() über die Sitzung. Anders als dort ist
     * ein POST hier nie ein Anmeldeversuch, sobald man schon angemeldet ist –
     * die beiden Formulare landen also nie in der falschen Verzweigung.
     *
     * @param array<string,string> $params
     */
    public function preferences(array $params): void
    {
        $code = Galleries::normalize($params['code'] ?? '');
        $gallery = Galleries::find($code);

        if ($gallery === null) {
            (new PageController())->notFound(I18n::locale());
            return;
        }

        $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

        if (!$this->isAuthorized($code)) {
            $error = '';
            if ($method === 'POST') {
                $target = $this->login();
                if ($target !== null) {
                    header('Location: ' . $target, true, 303);
                    exit;
                }
                $error = 'wrong';
            }

            View::page('pages/gallery-login', [
                'locale' => I18n::locale(),
                'path'   => I18n::path('/galerie/' . $code . '/tercihler'),
                'meta'   => [
                    'title'   => (string) ($gallery['couple'] ?? ''),
                    'noindex' => true,
                ],
                'error'      => $error,
                'presetCode' => $code,
                'couple'     => (string) ($gallery['couple'] ?? ''),
                'csrf'       => Security::csrf(),
            ]);
            return;
        }

        // Gäste sehen nur die Bilder – die Vorlieben sind Sache des Paares.
        if ($this->isGuest($code)) {
            header('Location: ' . I18n::path('/galerie/' . $code), true, 303);
            exit;
        }

        if ($method === 'POST') {
            $this->savePreferences($code, $gallery);
            header('Location: ' . I18n::path('/galerie/' . $code . '/tercihler') . '?gespeichert=1', true, 303);
            exit;
        }

        $preferences = Galleries::preferences($code);

        View::page('pages/gallery-preferences', [
            'locale' => I18n::locale(),
            'path'   => I18n::path('/galerie/' . $code . '/tercihler'),
            'meta'   => [
                'title'   => (string) ($gallery['couple'] ?? ''),
                'noindex' => true,
            ],
            'gallery'           => $gallery,
            'preferences'       => $preferences,
            'preferencesFilled' => $preferences !== null,
            'styles'            => Content::list('editingStyles'),
            'questions'         => Content::list('galleryQuestions'),
            'saved'             => isset($_GET['gespeichert']),
            'csrf'              => Security::csrf(),
        ]);
    }

    /** @param array<string,mixed> $gallery */
    private function savePreferences(string $code, array $gallery): void
    {
        if (!Security::checkCsrf($_POST['csrf'] ?? null)) {
            return;
        }

        $styles = Content::list('editingStyles');
        $style = Security::clean($_POST['style'] ?? '', 4);
        $styleIndex = $style === '' ? null : (int) $style;
        if ($styleIndex !== null && !isset($styles[$styleIndex])) {
            $styleIndex = null;
        }

        $answers = [];
        foreach (Content::list('galleryQuestions') as $i => $question) {
            $raw = $_POST['answer'][$i] ?? '';
            $max = ($question['type'] ?? 'text') === 'choice' ? 200 : 2000;
            $answers[$i] = Security::clean($raw, $max);
        }

        Galleries::savePreferences($code, (string) ($gallery['couple'] ?? ''), $styleIndex, $answers);
    }

    /**
     * Eine Beispielgalerie – ohne Code, ohne Passwort.
     *
     * Auf der Anmeldeseite stand bisher in vier Punkten, was einen hinter dem
     * Passwort erwartet. Das liest sich gut und zeigt nichts. Wer die Galerie
     * einmal gesehen hat – die Bilder, das Herz, die Auswahl unten –, versteht
     * in zehn Sekunden, wofuer die vier Punkte Worte brauchen.
     *
     * Es sind Platzhalterbilder und kein Kundenauftrag: hier liegt nichts,
     * was jemandem gehoert.
     */
    /**
     * Bilder für die Beispielgalerie: die der vorhandenen Reportagen.
     *
     * @return list<string>
     */
    private static function demoSeeds(): array
    {
        $seeds = [];
        foreach (Content::list('stories') as $story) {
            foreach ((array) ($story['seeds'] ?? []) as $seed) {
                if (is_string($seed) && $seed !== '') {
                    $seeds[] = $seed;
                }
            }
        }

        return array_slice(array_values(array_unique($seeds)), 0, 24);
    }

    public function demo(): void
    {
        $locale = I18n::locale();
        $de = $locale === 'de';

        $gallery = [
            'code'     => 'beispiel',
            'couple'   => $de ? 'Beispielgalerie' : 'Example gallery',
            'date'     => date('Y') . '-06-20',
            'venue'    => $de ? 'So sieht eure Galerie aus' : 'This is how your gallery looks',
            'password' => '',
            'expires'  => '',
            // Die Bilder der vorhandenen Reportagen – Platzhalter, die es
            // ohnehin schon auf der Seite gibt.
            'seeds'    => self::demoSeeds(),
            'uploads'  => [],
        ];

        View::page('pages/gallery', [
            'locale' => $locale,
            'path'   => I18n::path('/galerie/beispiel', $locale),
            'meta'   => [
                'title'   => $de ? 'Beispielgalerie' : 'Example gallery',
                'noindex' => true,
                'scripts' => ['/assets/gallery.js'],
            ],
            'gallery'   => $gallery,
            'photos'    => Galleries::photos($gallery),
            'selection' => null,
            'dateLong'  => Dates::long((string) $gallery['date']),
            // Die Leiste unten zeigt sich, nimmt aber nichts entgegen.
            'demo'      => true,
            'csrf'      => Security::csrf(),
        ]);
    }

    /** Auswahl entgegennehmen (vom Skript per fetch). */
    public function saveSelection(): void
    {
        header('Content-Type: application/json; charset=UTF-8');

        $raw = file_get_contents('php://input') ?: '';
        $body = json_decode($raw, true);
        $body = is_array($body) ? $body : $_POST;

        $code = Galleries::normalize((string) ($body['code'] ?? ''));

        if (!Security::checkCsrf((string) ($body['csrf'] ?? ''))) {
            http_response_code(403);
            echo json_encode(['ok' => false, 'error' => 'csrf']);
            return;
        }

        if (!$this->isAuthorized($code) || $this->isGuest($code)) {
            http_response_code(403);
            echo json_encode(['ok' => false, 'error' => 'auth']);
            return;
        }

        $gallery = Galleries::find($code);
        if ($gallery === null) {
            http_response_code(404);
            echo json_encode(['ok' => false, 'error' => 'unknown']);
            return;
        }

        $picks = array_slice(array_map('intval', (array) ($body['picks'] ?? [])), 0, 400);
        $note = Security::clean($body['note'] ?? '', 800);

        $photoCount = count(Galleries::photos($gallery));
        $cover = $body['cover'] ?? null;
        $cover = is_numeric($cover) ? (int) $cover : null;
        if ($cover !== null && ($cover < 0 || $cover >= $photoCount)) {
            $cover = null;
        }

        Galleries::saveSelection($code, (string) ($gallery['couple'] ?? ''), $picks, $note, $cover);

        echo json_encode(['ok' => true, 'count' => count($picks)]);
    }

    public function logout(): void
    {
        Security::session();
        unset($_SESSION['gallery']);
        header('Location: ' . I18n::path('/galerie'), true, 303);
        exit;
    }

    /* ------------------------------- Intern ------------------------------- */

    /** @return string|null Zieladresse nach erfolgreicher Anmeldung */
    private function login(): ?string
    {
        if (!Security::checkCsrf($_POST['csrf'] ?? null)) {
            return null;
        }

        // Ein Passwort lässt sich durchprobieren – deshalb eine Bremse.
        if (Security::throttle('gallery-login', 10, 600)) {
            return null;
        }

        $code = Galleries::normalize(Security::clean($_POST['code'] ?? '', 64));
        $password = Security::clean($_POST['password'] ?? '', 64);

        $result = Galleries::authRole($code, $password);
        if ($result === null) {
            return null;
        }

        Security::session();
        $_SESSION['gallery'][$code] = $result['role'];
        session_regenerate_id(true);

        // Relativ umleiten: so bleibt die Sitzung auch dann gültig, wenn die
        // Seite über eine andere Schreibweise des Hosts aufgerufen wurde.
        return I18n::path('/galerie/' . $code);
    }

    private function isAuthorized(string $code): bool
    {
        Security::session();
        return !empty($_SESSION['gallery'][$code]);
    }

    /** Gast: darf ansehen, aber nichts ändern – kein Herz, kein Titelbild, kein Absenden. */
    private function isGuest(string $code): bool
    {
        Security::session();
        return ($_SESSION['gallery'][$code] ?? null) === 'guest';
    }

    /**
     * Vorschau fürs Teilen (WhatsApp & Co.) – gilt für die Anmeldeseite
     * genauso wie für die Galerie selbst: wer einen Link teilt, hat sich
     * noch nicht angemeldet, also holt der Crawler immer die Login-Seite.
     *
     * @param array<string,mixed> $gallery
     * @return array<string,mixed>
     */
    private function shareMeta(array $gallery): array
    {
        $facts = array_filter([
            (string) ($gallery['venue'] ?? ''),
            Dates::long((string) ($gallery['date'] ?? '')),
        ], static fn (string $v): bool => $v !== '');

        $code = (string) ($gallery['code'] ?? '');
        $photos = Galleries::photos($gallery);
        $source = (string) ($photos[0]['full'] ?? '');
        $image = $source === '' ? '' : OgImage::forDocument($code, $source, '#faf7f2', '#dccebc');

        return [
            'description' => implode(' · ', $facts),
            'image'       => $image,
            'imageWidth'  => OgImage::WIDTH,
            'imageHeight' => OgImage::HEIGHT,
        ];
    }
}
