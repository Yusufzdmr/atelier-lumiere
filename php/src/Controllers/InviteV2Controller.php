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

        /*
         * Der Entwurf.
         *
         * Die Kennung kommt aus der Adresse (?taslak=), nach dem Speichern aus
         * dem Formular - sonst legte jedes Speichern einen neuen Entwurf an und
         * der Kunde saemmelte Links, von denen nur der letzte stimmt.
         */
        $token  = Security::clean($_POST['token'] ?? $_GET['taslak'] ?? '', 40);
        $werte  = $token !== '' ? InvitationsV2::draft($token) : null;
        $values = is_array($werte) ? $werte : [];
        $draftLink = '';

        if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
            // Die Vorschau antwortet mit einem Bruchstueck und endet hier: sie
            // ist eine Ansicht des Formulars, keine Seite. Vor allem anderen,
            // damit sie nicht versehentlich in den Veroeffentlichungszweig
            // faellt - sie schreibt nichts.
            if ((string) ($_POST['was'] ?? '') === 'preview') {
                $this->previewFragment($design);
                return;
            }
            if ((string) ($_POST['was'] ?? '') === 'draft') {
                $ergebnis = $this->saveDraft($token);
                if (isset($ergebnis['error'])) {
                    $error = (string) $ergebnis['error'];
                } else {
                    $token     = (string) $ergebnis['token'];
                    $draftLink = (string) $ergebnis['url'];
                    // Was gerade eingetippt wurde, steht jetzt im Entwurf - und
                    // von dort kommt es zurueck ins Formular. Ohne das saehe der
                    // Kunde nach dem Speichern wieder leere Felder.
                    $values    = InvitationsV2::draft($token) ?? [];
                }
            } else {
                $ergebnis = $this->publish($design);
                if (isset($ergebnis['error'])) {
                    $error = (string) $ergebnis['error'];
                    // Bei einem Fehler bleibt stehen, was der Kunde getippt hat.
                    $values = InvitationsV2::draftValues($_POST);
                } else {
                    $done = $ergebnis;
                    // Veroeffentlicht: der Entwurf zeigt einen ueberholten Stand.
                    InvitationsV2::deleteDraft($token);
                }
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
            'values'    => $values,
            'token'     => $token,
            'draftLink' => $draftLink,
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
            // Was unter der Karte steht, beim ersten Zeichnen. Danach holt das
            // Skript es bei jedem Schrittwechsel neu - gerendert wird immer
            // hier, nie im Browser (siehe previewFragment).
            'abschnitte' => $this->abschnittsVorschau($design, $values, $locale),
            'sectionCss' => DesignSections::css($design, '.' . ltrim($scope, '.')),
            'csrf'    => Security::csrf(),
            'error'   => $error,
            'done'    => $done,
        ]);
    }

    /**
     * Die Abschnitte, wie sie unter der Karte stuenden.
     *
     * Gezeichnet wird mit den Werten, die gerade im Formular stehen - nicht
     * mit den Beispieldaten der Karte. Wer eine Adresse eingetippt hat, soll
     * seine Adresse sehen und nicht Schloss Hohenstein.
     *
     * Das CSRF-Zeichen ist absichtlich leer: das hier ist eine Vorschau, aus
     * ihr wird nichts abgeschickt. Ein gueltiges Zeichen in einem Formular,
     * das nie sendet, waere ein Geheimnis ohne Zweck.
     *
     * @param array<string,mixed> $design
     * @param array<string,string> $values
     */
    private function abschnittsVorschau(array $design, array $values, string $locale): string
    {
        $daten = $values;

        // Die Abschnitte lesen ihre Inhalte aus anderen Namen, als das
        // Formular sie traegt (families/program statt family_bride,
        // prog_title_0 ...). Dieselbe Uebersetzung wie in publish() - sie
        // steht hier ein zweites Mal, weil publish() sie mitten im Speichern
        // macht und sich nicht herausloesen laesst, ohne den Schreibweg
        // umzubauen.
        $braut = (string) ($values['family_bride'] ?? '');
        $mann  = (string) ($values['family_groom'] ?? '');
        if ($braut !== '' || $mann !== '') {
            $daten['families'] = ['bride' => $braut, 'groom' => $mann];
        }

        $zeilen = [];
        for ($z = 0; $z < 8; $z++) {
            $titel = (string) ($values['prog_title_' . $z] ?? '');
            if (trim($titel) === '') {
                continue;
            }
            $zeilen[] = ['time' => (string) ($values['prog_time_' . $z] ?? ''), 'title' => $titel];
        }
        if ($zeilen !== []) {
            $daten['program'] = $zeilen;
        }

        foreach ($values as $name => $wert) {
            if (str_starts_with((string) $name, 'sec_text_') && trim((string) $wert) !== '') {
                $daten['sections'][substr((string) $name, 9)]['text'] = (string) $wert;
            }
        }

        // Abgeschaltete Abschnitte verschwinden auch in der Vorschau, sonst
        // zeigte sie etwas, das die Einladung nicht drucken wird.
        $doc = $design;
        foreach ($doc['sections'] as $i => $abschnitt) {
            if (isset($values['sec_hidden_' . $abschnitt['id']])) {
                $doc['sections'][$i]['enabled'] = false;
            }
        }

        return DesignSections::html($doc, $daten, $locale, '', ['csrf' => '', 'sent' => false]);
    }

    /**
     * Die Vorschau als Bruchstueck, ohne Rahmen.
     *
     * Gerendert wird auf dem Server und nicht im Browser, weil sonst
     * DesignSections::html() ein zweites Mal in JavaScript stuende - mit dem
     * Kartenlink, der Datumsregel und der Frage, welcher Abschnitt ueberhaupt
     * gedruckt wird. Zwei Quellen fuer dieselbe Antwort laufen auseinander;
     * dieselbe Ueberlegung wie beim Wort "Tage" im Countdown.
     *
     * @param array<string,mixed> $design
     */
    private function previewFragment(array $design): void
    {
        if (!Security::checkCsrf(is_string($_POST['csrf'] ?? null) ? $_POST['csrf'] : null)) {
            http_response_code(400);
            return;
        }

        header('Content-Type: text/html; charset=utf-8');
        // Eine Ansicht des gerade Getippten gehoert in keinen Zwischenspeicher.
        header('Cache-Control: private, no-store');

        echo $this->abschnittsVorschau($design, InvitationsV2::draftValues($_POST), I18n::locale());
    }

    /**
     * Den Zwischenstand festhalten und den Link dazu zurueckgeben.
     *
     * Ein eigener Knopf, kein Hintergrundschreiben: so ist das Speichern eine
     * Handlung des Kunden und nicht ein Schreibzugriff, den jede Tastatur-
     * eingabe ausloest. Das ist auch die Entscheidung der ersten Fassung
     * (InviteController::saveDraft).
     *
     * @return array<string,string>
     */
    private function saveDraft(string $token): array
    {
        if (!Security::checkCsrf(is_string($_POST['csrf'] ?? null) ? $_POST['csrf'] : null)) {
            return ['error' => 'csrf'];
        }
        // Eigener Schluessel: der alte Assistent soll diesen hier nicht
        // aussperren und umgekehrt.
        if (Security::throttle('invite-v2-draft', 40, 900)) {
            return ['error' => 'throttle'];
        }

        // Eine neue Kennung nur beim ersten Mal. 20 Hexzeichen wie in der
        // ersten Fassung; der Link ist ein Zugang, kein Geheimnis von Rang -
        // wer ihn hat, sieht eine halb ausgefuellte Einladung.
        if ($token === '') {
            $token = bin2hex(random_bytes(10));
        }

        InvitationsV2::saveDraft($token, InvitationsV2::draftValues($_POST));

        $path = I18n::path('/v2/einladung') . '?taslak=' . $token;

        return ['token' => $token, 'path' => $path, 'url' => Config::url() . $path];
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
        // is_string() faengt csrf[]=x ab: ohne die Pruefung reicht ein Array,
        // um Security::checkCsrf() unter strict_types einen TypeError werfen
        // zu lassen - derselbe Fehler wie schon in saveReply() auf dem
        // Antwortweg.
        $csrfEingabe = $_POST['csrf'] ?? null;
        if (!Security::checkCsrf(is_string($csrfEingabe) ? $csrfEingabe : null)) {
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

        // Abschnitte: das Zu- und Abschalten geht ins Dokument, der Inhalt in
        // die Daten. Dieselbe Trennung wie bei den Ebenen.
        $wahl['sections'] = [];
        foreach ($darf['sections'] as $sid => $abschnitt) {
            if ($abschnitt['hide'] && isset($_POST['sec_hidden_' . $sid])) {
                $wahl['sections'][$sid] = ['hidden' => true];
            }

            if (in_array('families', $abschnitt['fields'], true)) {
                $braut = Security::clean($_POST['family_bride'] ?? '', 120);
                $mann  = Security::clean($_POST['family_groom'] ?? '', 120);
                if ($braut !== '' || $mann !== '') {
                    $data['families'] = ['bride' => $braut, 'groom' => $mann];
                }
            }

            if (in_array('program', $abschnitt['fields'], true)) {
                $zeilen = [];
                for ($z = 0; $z < 8; $z++) {
                    $titel = Security::clean($_POST['prog_title_' . $z] ?? '', DesignSections::PROGRAM_LEN);
                    if ($titel === '') {
                        continue;
                    }
                    $zeilen[] = [
                        'time'  => Security::clean($_POST['prog_time_' . $z] ?? '', DesignSections::PROGRAM_LEN),
                        'title' => $titel,
                    ];
                }
                if ($zeilen !== []) {
                    $data['program'] = $zeilen;
                }
            }

            if (in_array('text', $abschnitt['fields'], true)) {
                // Unter der Kennung, nicht unter einem festen Namen: zwei
                // Textbloecke in einem Dokument wuerden sich sonst einen Platz
                // teilen und der zweite den ersten ueberschreiben.
                $text = Security::clean($_POST['sec_text_' . $sid] ?? '', 1200);
                if ($text !== '') {
                    $data['sections'][$sid]['text'] = $text;
                }
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
        // Derselbe Aufbau wie oben, nur mit dem Schluessel als letztem
        // Wegstueck - genau das Muster, das replies() unter /{slug}/{key}
        // erwartet. Ohne diesen Link findet das Paar den Leseschirm nur per
        // SQL-Abfrage, und eine Antwort, die niemand liest, ist laut
        // Lastenheft §2 keine Funktion.
        $managePath = I18n::path('/v2/einladung/' . $slug . '/' . $data['manageKey']);

        return [
            'slug'       => $slug,
            'path'       => $path,
            'url'        => Config::url() . $path,
            'managePath' => $managePath,
            'manageUrl'  => Config::url() . $managePath,
        ];
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

        // Vor der Antwort vollstaendig, nicht danach: saveReply() muss wissen,
        // ob die Einladung ueberhaupt einen sichtbaren rsvp-Abschnitt zeigt -
        // dafuer braucht sie das fertige Dokument, nicht den rohen Schnappschuss.
        $doc = Design::complete($einladung['design_snapshot']);

        // Erst antworten, dann zeichnen: die Seite, die nach dem Absenden
        // erscheint, soll den Dank zeigen und nicht noch einmal das leere
        // Formular. Waere die Reihenfolge umgekehrt, saehe der Gast seine
        // eigene Antwort nicht und schickte sie ein zweites Mal.
        $gesendet = false;
        if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
            $gesendet = $this->saveReply((string) $einladung['slug'], $einladung['data'], $doc);
        }

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
                // Vollbild, wie in der ersten Fassung (InviteController::show):
                // ein Gast oeffnet das hier in WhatsApp. Menue und Fusszeile
                // des Fotografen gehoeren nicht darueber - sie machen aus der
                // Einladung eine Unterseite seiner Website. Der
                // Einwilligungsbanner bleibt, den druckt layout.php auch im
                // Vollbild.
                'bare'    => true,
                // Dieselbe Choreografie wie in der Design-Vorschau: Kuvert
                // oeffnen, Karte aufsteigen lassen. Ohne dieses Skript bleibt
                // das Kuvert zu.
                'scripts' => ['/assets/invitation.js', '/assets/invite-v2-countdown.js'],
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
            //
            // $form ist alles, was DesignSections nicht selbst wissen darf:
            // das CSRF-Zeichen kommt aus der Sitzung, und ob gerade
            // geantwortet wurde, weiss nur diese Anfrage. Das leere vierte
            // Argument laesst das Bezugsdatum bei date('Y-m-d') - eine echte
            // Einladung schaut auf die echte Uhr.
            'abschnitte' => DesignSections::html($doc, $einladung['data'], $locale, '', [
                'csrf' => Security::csrf(),
                'sent' => $gesendet,
            ]),
        ]);
    }

    /**
     * Die Antwort eines Gastes.
     *
     * Sie geht in die Tabelle rsvps und nirgendwo sonst - weder in
     * design_snapshot noch in invitations_v2.data. Das ist die Regel aus
     * Phase 3B ("das Dokument einer veroeffentlichten Einladung friert ein"),
     * und sie haelt hier ein Versprechen, das mehr wert ist als Bequemlich-
     * keit: nichts, was ein Gast tippt, kann das Aussehen der Einladung
     * veraendern. Deshalb steht hier kein einziger Schreibzugriff auf die
     * Einladung selbst.
     *
     * Falsch heisst still: ein abgelaufenes Zeichen, eine Flut oder ein
     * leerer Name geben false zurueck und die Seite erscheint einfach ohne
     * Dank. Das ist wenig - aber die Alternative waere, einem Gast eine
     * Fehlermeldung ueber CSRF zu zeigen.
     *
     * @param array<string,mixed> $data die Daten der Einladung, nicht des Gastes
     * @param array<string,mixed> $doc  das fertige Dokument - fuer die Frage, ob rsvp ueberhaupt sichtbar ist
     */
    private function saveReply(string $slug, array $data, array $doc): bool
    {
        // Erste Kontrolle, vor allem anderen. is_string() faengt csrf[]=x ab:
        // ohne die Pruefung reicht ein Array, um Security::checkCsrf() unter
        // strict_types einen TypeError werfen zu lassen, und die Seite
        // stuerbe fuer jeden, der das Formular von Hand veraendert.
        $csrfEingabe = $_POST['csrf'] ?? null;
        if (!Security::checkCsrf(is_string($csrfEingabe) ? $csrfEingabe : null)) {
            return false;
        }

        // Eigener Schluessel, getrennt vom alten Motor: eine Flut auf
        // /einladung/{slug} soll /v2/einladung/{slug} nicht mitsperren. Das
        // Mass ist das des alten Motors - 20 in zehn Minuten je Einladung
        // reicht einer grossen Hochzeit und stoppt ein Skript.
        if (Security::throttle('rsvp-v2-' . $slug, 20, 600)) {
            return false;
        }

        // DesignSections::visible() entscheidet, ob ein rsvp-Abschnitt
        // gedruckt wird - ausgeschaltet vom Paar, gar nicht im Design
        // vorhanden, ODER das Datum bereits vorbei (hatInhalt() prueft die
        // rsvp-Zeile gegen date('Y-m-d'), dieselbe Regel wie hier). Diese eine
        // Kontrolle deckt also beides ab: eine abgeschaltete Frage UND eine
        // Frage, deren Termin verstrichen ist. Ohne sie sammelte die Tabelle
        // Antworten auf eine Frage, die niemand mehr stellt - serverseitig
        // durchgesetzt, nicht nur im Markup versteckt. Nach CSRF und Flut,
        // damit sie nicht zum Werkzeug wird, mit dem sich von aussen erraten
        // liesse, welche Einladung rsvp eingeschaltet hat.
        $hatRsvp = false;
        foreach (DesignSections::visible($doc, $data) as $abschnitt) {
            if ((string) ($abschnitt['type'] ?? '') === 'rsvp') {
                $hatRsvp = true;
                break;
            }
        }
        if (!$hatRsvp) {
            return false;
        }

        // Ohne Namen keine Antwort: bis zur Gaesteliste in Phase D ist der
        // Name die einzige Kennung, die wir haben. Eine namenlose Zeile
        // koennte weder angezeigt noch ersetzt werden.
        $name = Security::clean($_POST['name'] ?? '', 60);
        if ($name === '') {
            return false;
        }

        $kommtEingabe = $_POST['coming'] ?? '0';
        $anzahlEingabe = $_POST['count'] ?? 1;

        InvitationsV2::saveRsvp($slug, [
            'slug'   => $slug,
            'name'   => $name,
            // Alles ausser "1" heisst nein - so ist ein fehlendes oder
            // verformtes Feld eine Absage und keine stille Zusage. is_string()
            // faengt coming[]=1 ab, bevor der Vergleich ein Array in einen
            // String zwaenge.
            'coming' => (is_string($kommtEingabe) ? $kommtEingabe : '0') === '1',
            // Beschnitten, nicht abgelehnt: wer sich vertippt und 50 schreibt,
            // soll eine Einladung sehen und keine Fehlerseite. is_scalar()
            // haelt count[]=1 fern von einem (int)-Cast auf ein Array.
            'count'  => max(1, min(20, is_scalar($anzahlEingabe) ? (int) $anzahlEingabe : 1)),
            'note'   => Security::clean($_POST['note'] ?? '', 300),
            // Im Dokument, nicht nur in der Spalte: rsvps() liest ueber
            // Db::jsonList() und bekommt die Spalte at gar nicht zu sehen.
            'at'     => date('c'),
        ]);

        return true;
    }

    /**
     * Was die Gaeste geantwortet haben.
     *
     * Der Schluessel steht seit Phase 3B in den Daten jeder Einladung, und
     * bis heute hat ihn niemand gebraucht. Er wurde damals mit genau diesem
     * Argument geschrieben: nachtraeglich eingefuehrt haette er jede bis
     * dahin veroeffentlichte Einladung ausgesperrt. Dies ist die Phase, fuer
     * die das Argument gemacht war.
     *
     * Nur lesen: Loeschen, Aendern und Ausleiten sind Phase D.
     *
     * @param array<string,string> $params
     */
    public function replies(array $params): void
    {
        $locale = I18n::locale();
        $einladung = InvitationsV2::find($params['slug'] ?? '');

        $erwartet = $einladung !== null ? (string) ($einladung['data']['manageKey'] ?? '') : '';
        $gegeben  = (string) ($params['key'] ?? '');

        /*
         * 404 und nicht 403.
         *
         * Ein 403 bestaetigt, dass es diese Einladung gibt - wer den
         * Schluessel nicht hat, soll auch das nicht erfahren. "Diese Seite
         * gibt es nicht" ist die richtige Antwort an jemanden, der nicht
         * gemeint ist.
         *
         * hash_equals statt ===: der Schluessel ist 32 Hexadezimalzeichen und
         * die einzige Sicherung dieser Seite. Ein Vergleich, der beim ersten
         * ungleichen Zeichen abbricht, verraet ueber die Laufzeit, wie weit
         * ein Rateversuch gekommen ist.
         *
         * Der leere Schluessel wird ausdruecklich vorher abgefangen:
         * hash_equals('', '') ist WAHR. Eine Einladung ohne manageKey stuende
         * sonst jedem offen. Heute schreibt publish() ihn immer - aber "heute
         * kann das nicht passieren" ist der Satz, nach dem in Phase C drei
         * Fehler gefunden wurden.
         */
        if ($einladung === null || $erwartet === '' || !hash_equals($erwartet, $gegeben)) {
            // pages/not-found liest $locale unbedingt (not-found.php:10) und
            // layout.php braucht $path. Fehlen sie, meldet PHP undefinierte
            // Variablen und die Seite kommt auf Englisch heraus, egal in
            // welcher Sprache sie aufgerufen wurde.
            http_response_code(404);
            View::page('pages/not-found', [
                'locale' => $locale,
                'path'   => I18n::path('/v2/einladung'),
                'meta'   => Seo::forPage('einladung2', ['noindex' => true]),
            ]);
            return;
        }

        // Diese Seite ist eine geheime Adresse mit einer Gaesteliste
        // namentlich darauf - sie darf in keinem geteilten Cache landen.
        // show() bekommt no-store geschenkt, weil Security::csrf() dort eine
        // Sitzung startet; hier startet keine, also muss der Hinweis von Hand
        // hinaus.
        header('Cache-Control: private, no-store');

        $antworten = InvitationsV2::rsvps((string) $einladung['slug']);

        // Zwei Zahlen, weil es zwei Fragen sind: eine Absage ist eine
        // Antwort und kein Gast, und eine Zusage bringt mehrere Personen mit.
        $kommen = 0;
        foreach ($antworten as $antwort) {
            if (!empty($antwort['coming'])) {
                $kommen += max(1, (int) ($antwort['count'] ?? 1));
            }
        }

        $namen = trim(((string) ($einladung['data']['bride'] ?? '')) . ' & ' . ((string) ($einladung['data']['groom'] ?? '')), ' &');

        View::page('pages/invite-v2-replies', [
            'locale' => $locale,
            // Ohne $path meldet layout.php eine undefinierte Variable im
            // Sprachumschalter. Der Schluessel gehoert NICHT hinein: der
            // Umschalter schriebe ihn sonst in eine sichtbare Adresse.
            'path'   => I18n::path('/v2/einladung'),
            'meta'   => Seo::forPage('einladung2', [
                'title'   => I18n::t('invitation2.repliesTitle'),
                // Kein Titelbild - wie im Schaufenster der zweiten Fassung.
                'solidHeader' => true,
                // Diese Seite IST der Schluessel. Sie gehoert unter keinen
                // Umstaenden in einen Index.
                'noindex' => true,
            ]),
            'namen'     => $namen,
            'antworten' => $antworten,
            'kommen'    => $kommen,
        ]);
    }
}
