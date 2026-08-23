<?php
declare(strict_types=1);

namespace Atelier\Controllers;

use Atelier\Admin;
use Atelier\Content;
use Atelier\Db;
use Atelier\Galleries;
use Atelier\I18n;
use Atelier\Images;
use Atelier\Integrations;
use Atelier\Leads;
use Atelier\Paypal;
use Atelier\Places;
use Atelier\Preflight;
use Atelier\Media;
use Atelier\Security;
use Atelier\Themes;
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
        $leads = Leads::all(30);

        $pending = Admin::pendingWork(
            $this->locale,
            $leads,
            $selections,
            $invitations,
            $customers,
            $galleries
        );

        $this->render('admin/overview', '', [
            'leads'       => $leads,
            'selections'  => $selections,
            'galleries'   => $galleries,
            'invitations' => $invitations,
            'rsvps'       => $rsvps,
            'customers'   => $customers,
            'pending'     => $pending,
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
            // Leeres Feld heisst auch hier: unveraendert lassen.
            'mapsKey'       => $keep('maps_key', (string) ($settings['google']['mapsKey'] ?? '')),
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


    /* -------------------------------- Themen -------------------------------- */

    public function themes(): void
    {
        if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
            Admin::checkCsrfOrFail();

            $was = Security::clean($_POST['was'] ?? '', 60);

            // Ein einzelnes Schmuckelement entfernen: die Kennung haengt am Knopf.
            if (str_starts_with($was, 'deco-delete:')) {
                $this->deleteDecoration(substr($was, 12));
                Admin::back($this->locale, '/themen');
            }

            match ($was) {
                'save'            => $this->saveTheme(),
                'add'             => $this->addTheme(),
                'duplicate'       => $this->duplicateTheme(),
                'variant'         => $this->variantTheme(),
                'delete'          => $this->deleteTheme(),
                'deco-add'        => $this->addDecoration(),
                'image-delete'    => $this->deleteThemeImage('image'),
                'envelope-delete' => $this->deleteThemeImage('envelopeImage'),
                'backdrop-delete' => $this->deleteThemeImage('backdropVideo'),
                'backdrop-poster-delete' => $this->deleteThemeImage('backdropPoster'),
                'intro-delete'    => $this->deleteThemeImage('introVideo'),
                default           => null,
            };

            Admin::back($this->locale, '/themen');
        }

        $this->render('admin/themes', '/themen', ['themes' => Themes::all()]);
    }

    private function saveTheme(): void
    {
        $id = Themes::slug(Security::clean($_POST['id'] ?? '', 40));
        $themes = Themes::all();

        foreach ($themes as $index => $theme) {
            if ((string) $theme['id'] !== $id) {
                continue;
            }

            $next = $theme;
            $next['name'] = Security::clean($_POST['name'] ?? '', 60) ?: $theme['name'];
            $next['sub'] = [
                'de' => Security::clean($_POST['sub_de'] ?? '', 60),
                'tr' => Security::clean($_POST['sub_tr'] ?? '', 60),
            ];

            foreach (array_keys(Themes::COLORS) as $key) {
                $next[$key] = Security::clean($_POST[$key] ?? '', 120) ?: $theme[$key];
            }

            $next['imageMode'] = Security::clean($_POST['imageMode'] ?? '', 10) === 'repeat' ? 'repeat' : 'cover';
            $next['imageOpacity'] = (string) max(0, min(100, (int) ($_POST['imageOpacity'] ?? 100)));
            $next['animation'] = in_array((string) ($_POST['animation'] ?? ''), Themes::ANIMATIONS, true)
                ? (string) $_POST['animation']
                : 'seal';
            // Die drei uebrigen Bewegungsachsen. Ein unbekannter Wert wird auf
            // die Voreinstellung gezogen, nicht durchgereicht – sonst stuende
            // im Datensatz etwas, das die Vorlage nicht kennt.
            $next['intro'] = in_array((string) ($_POST['intro'] ?? ''), Themes::INTROS, true)
                ? (string) $_POST['intro']
                : 'none';
            $next['idle'] = in_array((string) ($_POST['idle'] ?? ''), Themes::IDLES, true)
                ? (string) $_POST['idle']
                : 'breathe';
            $next['nameAnimation'] = in_array((string) ($_POST['nameAnimation'] ?? ''), Themes::NAME_ANIMATIONS, true)
                ? (string) $_POST['nameAnimation']
                : 'write';
            $next['particle'] = in_array((string) ($_POST['particle'] ?? ''), Themes::PARTICLES, true)
                ? (string) $_POST['particle']
                : 'petal';
            $next['reveal'] = in_array((string) ($_POST['reveal'] ?? ''), Themes::REVEALS, true)
                ? (string) $_POST['reveal']
                : 'up';
            $next['animationSpeed'] = (string) max(0, min(8000, (int) ($_POST['animationSpeed'] ?? 1200)));
            $next['animationDelay'] = (string) max(0, min(8000, (int) ($_POST['animationDelay'] ?? 0)));
            $next['css'] = Themes::safeCss((string) ($_POST['css'] ?? ''));

            $next['family'] = Security::clean($_POST['family'] ?? '', 60);
            $next['scene'] = in_array((string) ($_POST['scene'] ?? ''), Themes::SCENES, true)
                ? (string) $_POST['scene']
                : 'botanical';
            $next['fonts'] = [
                'display'  => array_key_exists((string) ($_POST['font_display'] ?? ''), Themes::FONTS) ? (string) $_POST['font_display'] : 'cormorant',
                'body'     => array_key_exists((string) ($_POST['font_body'] ?? ''), Themes::FONTS) ? (string) $_POST['font_body'] : 'jost',
                'script'   => array_key_exists((string) ($_POST['font_script'] ?? ''), Themes::FONTS) ? (string) $_POST['font_script'] : 'greatvibes',
                'scale'    => (string) max(60, min(160, (int) ($_POST['font_scale'] ?? 100))),
                'tracking' => (string) max(-30, min(80, (int) ($_POST['font_tracking'] ?? 0))),
            ];

            // Die Schmuckelemente kommen als deco_<kennung>_<feld> herein.
            $next['decorations'] = array_map(static function (array $deco): array {
                $key = 'deco_' . $deco['id'] . '_';
                foreach (['spot', 'x', 'y', 'width', 'rotate', 'opacity', 'move', 'delay', 'duration'] as $field) {
                    if (array_key_exists($key . $field, $_POST)) {
                        $deco[$field] = Security::clean($_POST[$key . $field], 40);
                    }
                }
                $deco['front'] = isset($_POST[$key . 'front']);
                return Themes::completeDecoration($deco);
            }, (array) $theme['decorations']);

            // Hochgeladene Hintergruende (Canva-Export) ersetzen das bisherige Bild.
            foreach (['image', 'envelopeImage', 'backdropPoster'] as $field) {
                $file = $_FILES[$field] ?? null;
                if (is_array($file) && ($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_OK) {
                    $url = Media::store($file, 'themen/' . $id);
                    if ($url !== null) {
                        if ((string) $theme[$field] !== '') {
                            Media::delete((string) $theme[$field]);
                        }
                        $next[$field] = $url;
                    }
                }
            }

            // Hintergrundvideo (mp4/webm/mov) fuer Themen wie Lumina. Wird
            // hinter dem Karteninhalt gespielt; jede Vorlage bekommt so ihre
            // eigene Bewegung ohne Codeaenderung.
            $vidFile = $_FILES['backdropVideo'] ?? null;
            if (is_array($vidFile) && ($vidFile['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_OK) {
                $vidUrl = Media::storeVideo($vidFile, 'themen/' . $id);
                if ($vidUrl !== null) {
                    if ((string) ($theme['backdropVideo'] ?? '') !== '') {
                        Media::delete((string) $theme['backdropVideo']);
                    }
                    $next['backdropVideo'] = $vidUrl;
                }
            }

            // Der Vorspann. Dieselbe Pruefung wie beim Hintergrundvideo -
            // storeVideo liest die Art aus dem Dateiinhalt, nicht aus dem
            // Namen, und laesst nur mp4/webm/mov durch.
            $introFile = $_FILES['introVideo'] ?? null;
            if (is_array($introFile) && ((int) ($introFile['error'] ?? UPLOAD_ERR_NO_FILE)) === UPLOAD_ERR_OK) {
                $introUrl = Media::storeVideo($introFile, 'themen/' . $id);
                if ($introUrl !== null) {
                    if ((string) ($theme['introVideo'] ?? '') !== '') {
                        Media::delete((string) $theme['introVideo']);
                    }
                    $next['introVideo'] = $introUrl;
                }
            }

            $introBild = $_FILES['introPoster'] ?? null;
            if (is_array($introBild) && ((int) ($introBild['error'] ?? UPLOAD_ERR_NO_FILE)) === UPLOAD_ERR_OK) {
                $introBildUrl = Media::store($introBild, 'themen/' . $id);
                if ($introBildUrl !== null) {
                    $next['introPoster'] = $introBildUrl;
                }
            }

            $themes[$index] = $next;
            Themes::save($themes);
            return;
        }
    }

    /**
     * Eine Variante desselben Entwurfs: Ivory, Rose, Sage, Dark.
     *
     * Unterschied zur Kopie: die Variante bleibt in der Familie. Der Assistent
     * stellt sie dann neben ihre Geschwister, statt sie als fremdes Thema
     * weiter unten einzureihen.
     */
    private function variantTheme(): void
    {
        $id = Themes::slug(Security::clean($_POST['id'] ?? '', 40));
        $themes = Themes::all();

        foreach ($themes as $theme) {
            if ((string) $theme['id'] !== $id) {
                continue;
            }

            $variant = $theme;
            $variant['id'] = $this->freeThemeId($id . '-variante', $themes);
            $variant['name'] = (string) $theme['name'] . ' II';
            $variant['family'] = (string) $theme['family'];
            $variant['version'] = 1;

            $themes[] = $variant;
            Themes::save($themes);
            return;
        }
    }

    /** Ein Schmuckelement hochladen – mit Durchsichtigkeit, ohne JPEG. */
    private function addDecoration(): void
    {
        $id = Themes::slug(Security::clean($_POST['id'] ?? '', 40));
        $file = $_FILES['deco_neu'] ?? null;
        if (!is_array($file)) {
            return;
        }

        $url = Media::storeGraphic($file, 'themen/' . $id . '/schmuck');
        if ($url === null) {
            return;
        }

        $themes = Themes::all();
        foreach ($themes as $index => $theme) {
            if ((string) $theme['id'] !== $id) {
                continue;
            }

            $decorations = (array) $theme['decorations'];
            if (count($decorations) >= 12) {
                Media::delete($url);
                return;
            }

            $decorations[] = Themes::completeDecoration(['id' => bin2hex(random_bytes(4)), 'src' => $url]);
            $themes[$index]['decorations'] = $decorations;
            Themes::save($themes);
            return;
        }

        Media::delete($url);
    }

    private function deleteDecoration(string $decoId): void
    {
        $id = Themes::slug(Security::clean($_POST['id'] ?? '', 40));
        $decoId = preg_replace('/[^a-z0-9]/', '', strtolower($decoId)) ?? '';
        $themes = Themes::all();

        foreach ($themes as $index => $theme) {
            if ((string) $theme['id'] !== $id) {
                continue;
            }

            $kept = [];
            foreach ((array) $theme['decorations'] as $deco) {
                if ((string) ($deco['id'] ?? '') === $decoId) {
                    Media::delete((string) ($deco['src'] ?? ''));
                    continue;
                }
                $kept[] = $deco;
            }

            $themes[$index]['decorations'] = $kept;
            Themes::save($themes);
            return;
        }
    }

    private function addTheme(): void
    {
        $name = Security::clean($_POST['name'] ?? '', 60);
        $themes = Themes::all();

        // Eingefuegtes Thema aus einer anderen Installation.
        $paste = trim((string) ($_POST['einfuegen'] ?? ''));
        if ($paste !== '') {
            $decoded = json_decode($paste, true);
            if (is_array($decoded) && ($decoded['name'] ?? '') !== '') {
                $imported = Themes::complete($decoded);
                $imported['id'] = $this->freeThemeId(Themes::slug($name !== '' ? $name : (string) $imported['name']), $themes);
                if ($name !== '') {
                    $imported['name'] = $name;
                }
                $imported['version'] = 1;
                // Bilder liegen auf der anderen Installation; die Adressen
                // wuerden ins Leere zeigen.
                $imported['image'] = '';
                $imported['envelopeImage'] = '';
                $imported['decorations'] = [];

                $themes[] = $imported;
                Themes::save($themes);
            }
            return;
        }

        if ($name === '') {
            return;
        }
        $id = $this->freeThemeId(Themes::slug($name), $themes);

        // Als Ausgangspunkt das erste Thema - so ist ein neues Thema nie roh.
        $base = Themes::complete($themes[0] ?? []);
        $base['id'] = $id;
        $base['name'] = $name;
        $base['sub'] = ['de' => '', 'tr' => ''];
        $base['image'] = '';
        $base['envelopeImage'] = '';
        $base['css'] = '';

        $themes[] = $base;
        Themes::save($themes);
    }

    private function duplicateTheme(): void
    {
        $id = Themes::slug(Security::clean($_POST['id'] ?? '', 40));
        $themes = Themes::all();
        $source = null;

        foreach ($themes as $theme) {
            if ((string) $theme['id'] === $id) {
                $source = $theme;
                break;
            }
        }
        if ($source === null) {
            return;
        }

        $copy = $source;
        $copy['id'] = $this->freeThemeId($id . '-kopie', $themes);
        $copy['name'] = $source['name'] . ' (Kopie)';
        $themes[] = $copy;
        Themes::save($themes);
    }

    private function deleteTheme(): void
    {
        $id = Themes::slug(Security::clean($_POST['id'] ?? '', 40));
        $themes = Themes::all();

        // Das letzte Thema bleibt stehen - ohne Thema gaebe es keine Einladung.
        if (count($themes) <= 1) {
            return;
        }

        foreach ($themes as $theme) {
            if ((string) $theme['id'] !== $id) {
                continue;
            }
            foreach (['image', 'envelopeImage'] as $field) {
                if ((string) $theme[$field] !== '') {
                    Media::delete((string) $theme[$field]);
                }
            }
        }

        Themes::save(array_values(array_filter(
            $themes,
            static fn (array $theme): bool => (string) $theme['id'] !== $id
        )));
    }

    private function deleteThemeImage(string $field): void
    {
        $id = Themes::slug(Security::clean($_POST['id'] ?? '', 40));
        $themes = Themes::all();

        foreach ($themes as $index => $theme) {
            if ((string) $theme['id'] !== $id) {
                continue;
            }
            if ((string) $theme[$field] !== '') {
                Media::delete((string) $theme[$field]);
            }
            $themes[$index][$field] = '';
            Themes::save($themes);
            return;
        }
    }

    /** @param list<array<string,mixed>> $themes */
    private function freeThemeId(string $wanted, array $themes): string
    {
        $taken = array_map(static fn (array $theme): string => (string) $theme['id'], $themes);
        $id = $wanted;
        $n = 2;
        while (in_array($id, $taken, true)) {
            $id = $wanted . '-' . $n++;
        }
        return $id;
    }

    /* ------------------------------ Systemcheck ----------------------------- */

    public function preflight(): void
    {
        $checks = Preflight::run($this->locale);

        $this->render('admin/preflight', '/systemcheck', [
            'checks' => $checks,
            'tally'  => Preflight::tally($checks),
        ]);
    }

    /* --------------------------------- Karte -------------------------------- */

    /**
     * Kartenausschnitt fuer die Ortssuche.
     *
     * Laeuft ueber die eigene Adresse, damit der Google-Schluessel nicht im
     * HTML des Adminbereichs steht. Ein Schluessel in einer Seite ist ein
     * Schluessel, der ueber einen geteilten Bildschirm oder ein Bildschirmfoto
     * abfliesst.
     */
    public function map(): void
    {
        $lat = (float) Security::clean($_GET['lat'] ?? '', 24);
        $lng = (float) Security::clean($_GET['lng'] ?? '', 24);

        // Ausserhalb der Erde gibt es nichts zu sehen.
        if ($lat < -90 || $lat > 90 || $lng < -180 || $lng > 180 || ($lat === 0.0 && $lng === 0.0)) {
            http_response_code(404);
            exit;
        }

        $image = Places::staticMap($lat, $lng, 480, 260);
        if ($image === null) {
            http_response_code(404);
            exit;
        }

        header('Content-Type: image/png');
        header('Cache-Control: private, max-age=600');
        echo $image;
        exit;
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
    /* -------------------------------- Bilder -------------------------------- */

    /**
     * Die festen Bildplätze der Website tauschen.
     *
     * Alles andere auf der Seite liess sich hier längst ändern – nur die
     * Bilder nicht: sie standen als Kürzel in den Vorlagen. Wer sein eigenes
     * Porträt auf „Über mich“ wollte, fand dafür keine Stelle.
     */
    public function images(): void
    {
        $de = $this->locale === 'de';

        if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
            Admin::checkCsrfOrFail();

            $bilder = Content::get('images');
            $bilder = is_array($bilder) ? $bilder : [];

            foreach (array_keys(Images::SLOTS) as $slot) {
                // Leeren heisst: zurueck zum Platzhalter.
                if (isset($_POST['weg'][$slot])) {
                    Media::delete((string) ($bilder[$slot] ?? ''));
                    unset($bilder[$slot]);
                    continue;
                }

                /*
                 * PHP dreht Feldnamen mit Klammern um: aus bild[about-portrait]
                 * wird nicht $_FILES['bild']['about-portrait'], sondern
                 * $_FILES['bild']['name']['about-portrait'] und so weiter. Hier
                 * wieder zu einer Datei zusammensetzen.
                 */
                $f = $_FILES['bild'] ?? [];
                $datei = isset($f['name'][$slot]) ? [
                    'name'     => (string) $f['name'][$slot],
                    'type'     => (string) ($f['type'][$slot] ?? ''),
                    'tmp_name' => (string) ($f['tmp_name'][$slot] ?? ''),
                    'error'    => (int) ($f['error'][$slot] ?? UPLOAD_ERR_NO_FILE),
                    'size'     => (int) ($f['size'][$slot] ?? 0),
                ] : [];

                $url = Media::store($datei, 'seite');
                if ($url !== null) {
                    Media::delete((string) ($bilder[$slot] ?? ''));
                    $bilder[$slot] = $url;
                }
            }

            Content::mutate(static function (array $content) use ($bilder): array {
                $content['images'] = $bilder;
                return $content;
            });

            Admin::back($this->locale, '/bilder');
        }

        $bilder = Content::get('images');

        $this->render('admin/images', '/bilder', [
            'slots'  => Images::SLOTS,
            'own'    => is_array($bilder) ? $bilder : [],
            'title'  => $de ? 'Bilder der Website' : 'Site görselleri',
        ]);
    }

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
