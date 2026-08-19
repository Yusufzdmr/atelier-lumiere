<?php

declare(strict_types=1);

namespace Atelier\Controllers;

use Atelier\Config;
use Atelier\Design;
use Atelier\DesignSections;
use Atelier\DesignWizard;
use Atelier\I18n;
use Atelier\InvitationsV2;
use Atelier\Media;
use Atelier\Security;
use Atelier\Seo;
use Atelier\View;

/**
 * Der Assistent der zweiten Fassung.
 *
 * Er steht neben dem alten (InviteController) und beruehrt ihn nicht: der alte
 * traegt Zahlung, Gutschein und Zusagen, und die kommen erst in Phase D
 * herueber. Bis dahin ist diese Seite von nirgends verlinkt - der Knopf im
 * Schaufenster zeigt weiter auf den alten Assistenten, damit hier keine
 * unbezahlte Einladung entsteht.
 */
final class InviteV2Controller
{
    /**
     * Damit die Vorschau ueberhaupt Knoten hat.
     *
     * Ein gebundenes Textelement ohne Wert wird nicht gezeichnet. Stuende hier
     * nichts, faende das Skript in Aufgabe 9 keine [data-bind]-Knoten und die
     * Live-Vorschau bliebe still - ein Fehler, den man auf einem Bildschirmfoto
     * nicht sieht. Dieselben Felder wie im Schaufenster.
     */
    private const BEISPIEL = [
        'bride'   => 'Sophia',
        'groom'   => 'Maximilian',
        'date'    => '2027-09-12',
        'time'    => '18:00',
        'venue'   => 'Schloss Hohenstein',
        'address' => 'Schlossstraße 1, 89312 Günzburg',
        'message' => 'Wir heiraten und wünschen uns, dass ihr dabei seid.',
        'hashtag' => '#sophiaundmaximilian',
    ];

    public function wizard(): void
    {
        $locale = I18n::locale();

        $designs = Design::all('active');
        if ($designs === []) {
            // pages/not-found liest $locale unbedingt (not-found.php:10) und
            // layout.php braucht $path. Fehlen sie, meldet PHP undefinierte
            // Variablen und die Seite kommt auf Englisch heraus, egal in welcher
            // Sprache sie aufgerufen wurde. DesignController::preview() gibt sie
            // aus genau diesem Grund mit.
            http_response_code(404);
            View::page('pages/not-found', [
                'locale' => $locale,
                'path'   => I18n::path('/v2/einladung'),
                'meta'   => Seo::forPage('einladung2', ['noindex' => true]),
            ]);
            return;
        }

        // Nach dem Absenden steht die Wahl im Formular, nicht mehr in der
        // Adresse - sonst waehlte ein Neuladen ein anderes Design.
        $wunsch = Security::clean($_POST['design'] ?? $_GET['design'] ?? '', 96);
        $design = $wunsch !== '' ? Design::find($wunsch) : null;
        if ($design === null || (string) $design['status'] !== 'active') {
            $design = $designs[0];
        }
        $design = Design::complete($design);

        $scope = '.d-' . $design['id'];

        $error = '';
        $done  = null;

        if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
            $ergebnis = $this->publish($design);
            if (isset($ergebnis['error'])) {
                $error = (string) $ergebnis['error'];
            } else {
                $done = $ergebnis;
            }
        }

        View::page('pages/invite-v2-wizard', [
            'locale'  => $locale,
            // layout.php braucht $path fuer den Sprachumschalter und die
            // aktive Kopfzeile (layout.php:25, 86) - ohne ihn meldet PHP eine
            // undefinierte Variable als zweite Zeile der Seite.
            'path'    => I18n::path('/v2/einladung'),
            'meta'    => Seo::forPage('einladung2', [
                'title'    => I18n::t('invitation2.wizardTitle'),
                'noindex'  => true,
                'scripts'  => ['/assets/invite-v2.js'],
            ]),
            'design'  => $design,
            'steps'   => DesignWizard::steps($design),
            'choices' => DesignWizard::choices($design),
            'values'  => [],
            // css() braucht den gepunkteten CSS-Selektor (".d-elysee"), aber die
            // Klasse im Markup darf den Punkt nicht tragen - class=".d-elysee"
            // waere ein ungueltiger Klassenname und die Regeln griffen nie.
            // DesignController::preview() macht denselben Unterschied.
            'scope'   => ltrim($scope, '.'),
            'styles'  => Design::css($design, $scope),
            // Beispieldaten, nicht leer. Design::html() ueberspringt ein
            // gebundenes Textelement, dessen Wert leer ist (Design.php:487) -
            // mit leeren Daten stuenden vier der sechs Elemente gar nicht erst
            // im DOM, und die Vorschau in Aufgabe 9 fuellte etwas, das es nicht
            // gibt. Das Skript leert sie beim ersten Lauf wieder.
            'karte'   => Design::html($design, Design::bindValues(self::BEISPIEL, $locale), $locale, 'card'),
            'csrf'    => Security::csrf(),
            'error'   => $error,
            'done'    => $done,
        ]);
    }

    /**
     * Die Einladung anlegen.
     *
     * Was der Kunde gewaehlt hat, wird nicht als Liste gespeichert, sondern auf
     * das Design gelegt: das Ergebnis ist der Schnappschuss. Damit ist das
     * Anzeigen spaeter genau Phase 1 - css() und html(), sonst nichts.
     *
     * @param array<string,mixed> $design
     * @return array<string,mixed>
     */
    private function publish(array $design): array
    {
        if (!Security::checkCsrf($_POST['csrf'] ?? null)) {
            return ['error' => 'csrf'];
        }
        // Eigener Schluessel: der alte Assistent soll diesen hier nicht
        // aussperren und umgekehrt.
        if (Security::throttle('invite-v2-create', 8, 900)) {
            return ['error' => 'throttle'];
        }

        $darf = DesignWizard::choices($design);

        $data = [];
        foreach ($darf['fields'] as $feld) {
            $data[$feld] = Security::clean($_POST[$feld] ?? '', $feld === 'message' ? 600 : 160);
        }

        // Gefragt und leer gelassen ist erlaubt - html() laesst die Zeile dann
        // einfach weg. Nur ohne jeden Namen weiss niemand, wessen Karte das ist.
        $brauchtNamen = in_array('bride', $darf['fields'], true) || in_array('groom', $darf['fields'], true);
        if ($brauchtNamen && ($data['bride'] ?? '') === '' && ($data['groom'] ?? '') === '') {
            return ['error' => 'names'];
        }

        $slug = InvitationsV2::slug(Security::clean($_POST['slug'] ?? '', 96));
        if ($slug === '') {
            $slug = InvitationsV2::slug(($data['bride'] ?? '') . '-' . ($data['groom'] ?? ''));
        }
        if ($slug === '') {
            $slug = 'einladung-' . bin2hex(random_bytes(3));
        }
        // Die Spalte ist VARCHAR(96). Der Umweg ueber die Namen kann laenger
        // werden als das - ae, oe und ue machen aus einem Zeichen zwei -, und
        // ohne Ausnahmebehandlung im Router bekaeme ein Paar mit langen Namen
        // eine Fehlerseite statt einer Einladung. 90 laesst Platz fuer das
        // Suffix, das eine Kollision anhaengt.
        $slug = mb_substr($slug, 0, 90);
        if (!InvitationsV2::slugAvailable($slug)) {
            $slug .= '-' . bin2hex(random_bytes(2));
        }

        // Die Wahl einsammeln. Was hier hineingeht, wird in personalize()
        // noch einmal gegen die Rechte geprueft - diese Schleife ist Bequem-
        // lichkeit, nicht Sicherheit.
        $wahl = ['palette' => [], 'fonts' => [], 'layers' => []];

        foreach (array_keys($darf['palette']) as $marke) {
            $wert = Security::clean($_POST['palette_' . $marke] ?? '', 32);
            if ($wert !== '') {
                $wahl['palette'][$marke] = $wert;
            }
        }

        foreach (array_keys($darf['fonts']) as $marke) {
            $wert = Security::clean($_POST['fonts_' . $marke] ?? '', 64);
            if ($wert !== '') {
                $wahl['fonts'][$marke] = $wert;
            }
        }

        foreach ($darf['layers'] as $id => $rechte) {
            $eintrag = [];

            if ($rechte['color']) {
                $wert = Security::clean($_POST['layer_color_' . $id] ?? '', 32);
                if ($wert !== '') {
                    $eintrag['color'] = $wert;
                }
            }
            if ($rechte['font']) {
                $wert = Security::clean($_POST['layer_font_' . $id] ?? '', 64);
                if ($wert !== '') {
                    $eintrag['font'] = $wert;
                }
            }
            if ($rechte['text']) {
                $wert = Security::clean($_POST['layer_text_' . $id] ?? '', 600);
                if ($wert !== '') {
                    $eintrag['text'] = ['de' => $wert, 'en' => $wert];
                }
            }
            if ($rechte['hide'] && isset($_POST['layer_hidden_' . $id])) {
                $eintrag['hidden'] = true;
            }
            if ($rechte['photo']) {
                // Media::store() sieht in die Datei, nicht auf ihren Namen.
                $pfad = Media::store($_FILES['layer_src_' . $id] ?? [], 'einladungen/v2/' . $slug);
                if ($pfad !== null) {
                    $eintrag['src'] = $pfad;
                }
            }

            if ($eintrag !== []) {
                $wahl['layers'][$id] = $eintrag;
            }
        }

        $snapshot = DesignWizard::personalize($design, $wahl);

        $data['slug']      = $slug;
        $data['locale']    = I18n::locale();
        $data['paid']      = false;
        // Kein Bildschirm dafuer in dieser Phase - aber der Schluessel muss
        // von Anfang an dastehen. Nachtraeglich eingefuehrt sperrt er jede
        // bis dahin veroeffentlichte Einladung aus.
        $data['manageKey'] = bin2hex(random_bytes(16));
        $data['createdAt'] = date('c');

        InvitationsV2::create($slug, (string) $design['id'], $snapshot, $data);

        $path = I18n::path('/v2/einladung/' . $slug);

        return ['slug' => $slug, 'path' => $path, 'url' => Config::url() . $path];
    }

    /**
     * Die fertige Einladung.
     *
     * Gezeigt wird der Schnappschuss, nicht das Design: wer die Vorlage im
     * Panel spaeter aendert, aendert diese Karte nicht. Genau dafuer gibt es
     * die Spalte.
     *
     * @param array<string,string> $params
     */
    public function show(array $params): void
    {
        $locale = I18n::locale();
        $einladung = InvitationsV2::find($params['slug'] ?? '');

        if ($einladung === null) {
            // pages/not-found liest $locale unbedingt (not-found.php:10) und
            // layout.php braucht $path. Fehlen sie, meldet PHP undefinierte
            // Variablen und die Seite kommt auf Englisch heraus, egal in
            // welcher Sprache sie aufgerufen wurde. DesignController::preview()
            // gibt sie aus genau diesem Grund mit.
            http_response_code(404);
            View::page('pages/not-found', [
                'locale' => $locale,
                'path'   => I18n::path('/v2/einladung'),
                'meta'   => Seo::forPage('einladung2', ['noindex' => true]),
            ]);
            return;
        }

        $doc = Design::complete($einladung['design_snapshot']);
        $values = Design::bindValues($einladung['data'], $locale);
        $scope = '.d-' . $doc['id'];

        $namen = trim(((string) ($einladung['data']['bride'] ?? '')) . ' & ' . ((string) ($einladung['data']['groom'] ?? '')), ' &');

        View::page('pages/invite-v2-show', [
            'locale' => $locale,
            'path'   => I18n::path('/v2/einladung/' . $einladung['slug'], $locale),
            'meta'   => Seo::forPage('einladung2', [
                'title' => $namen !== '' ? $namen : I18n::t('invitation2.wizardTitle'),
                // Eine Einladung gehoert nicht in den Index. Der Link ist
                // fuer die Gaeste, nicht fuer die Suche.
                'noindex' => true,
                // Dieselbe Choreografie wie in der Design-Vorschau: Kuvert
                // oeffnen, Karte aufsteigen lassen. Ohne dieses Skript bleibt
                // das Kuvert zu.
                'scripts' => ['/assets/invitation.js'],
            ]),
            'design' => $doc,
            // OHNE Punkt. Design::css() bekommt den Selektor (".d-elysee"),
            // die Vorlage bekommt den Klassennamen ("d-elysee") - sie schreibt
            // ihn in ein class-Attribut. Mit Punkt entstuende die Klasse
            // ".d-elysee", die der Selektor .d-elysee niemals trifft, und die
            // Einladung kaeme voellig ungestylt heraus. DesignController::
            // preview() macht es aus demselben Grund so (DesignController.php:125).
            'scope'  => ltrim($scope, '.'),
            // Die Abschnittsregeln haengen an denselben Marken wie die Karte,
            // also gehoeren sie in denselben Stilblock.
            'styles' => Design::css($doc, $scope) . DesignSections::css($doc, $scope),
            // Die fuenf Bewegungswerte rechnet sonst design-preview.php aus.
            // Die Buehne liest sie, leitet sie aber nicht selbst ab - eine
            // Rechnung, eine Quelle der Wahrheit (Aufgabe 5).
            'ratio'   => str_replace(':', ' / ', (string) $doc['canvas']['ratio']),
            'karteAn' => (string) $doc['animation']['card'],
            'tempo'   => (int) $doc['animation']['speed'],
            'introMs' => 0,
            'idle'    => (string) $doc['animation']['idle'],
            // Die Initialen stehen auf dem Siegel. Sie kommen aus den Daten
            // des Paares, nicht aus dem Dokument.
            'initialen' => $values['initials'],
            // Leer, und zwar immer. Die Buehne zeigt Warnungen ungeprueft an
            // (design-stage.php:50) - auf einer echten Einladung hat ein Gast
            // nichts mit den Maengeln einer Vorlage zu tun. Waere der Wert gar
            // nicht gesetzt, stuende dort eine leere Box: null !== [] ist wahr.
            'warnings' => [],
            'seite'  => Design::html($doc, $values, $locale, 'page'),
            'kuvert' => Design::html($doc, $values, $locale, 'envelope'),
            'karte'  => Design::html($doc, $values, $locale, 'card'),
            // Rohdaten, nicht gebundene Werte: die Abschnitte binden ihre
            // eigenen Platzhalter (Adresse, Countdown-Datum) selbst.
            'abschnitte' => DesignSections::html($doc, $einladung['data'], $locale),
        ]);
    }
}
