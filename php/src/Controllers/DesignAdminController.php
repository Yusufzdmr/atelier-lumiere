<?php
declare(strict_types=1);

namespace Atelier\Controllers;

use Atelier\Admin;
use Atelier\Design;
use Atelier\DesignSections;
use Atelier\DesignVideos;
use Atelier\I18n;
use Atelier\InvitationsV2;
use Atelier\Media;
use Atelier\SectionRegistry;
use Atelier\Security;
use Atelier\Themes;
use Atelier\View;

/**
 * Der Katalog der zweiten Fassung im Panel und sein Editor.
 *
 * Liegt neben DesignController und nicht darin: der eine bedient Gaeste, der
 * andere den Betrieb. Zwei Leser, zwei Dateien.
 */
final class DesignAdminController
{
    /** Beispieldaten fuer die Kacheln - dieselben wie in der Vorschau. */
    private const BEISPIEL = [
        'bride'   => 'Sophia',
        'groom'   => 'Maximilian',
        'date'    => '2027-09-12',
        'time'    => '18:00',
        'venue'   => 'Schloss Hohenstein',
        'address' => 'Schlossstraße 1, 89312 Günzburg',
        'message' => 'Wir heiraten und wünschen uns, dass ihr dabei seid.',
    ];

    public function index(string $locale): void
    {
        Admin::requireLogin($locale);

        if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
            $this->handle($locale);
            return;
        }

        $designs = Design::all();

        // Der Filter steht in der Adresse, nicht in der Sitzung: ein Link auf
        // "nur luxury" soll denselben Blick oeffnen wie beim Absender.
        $filter = Security::clean($_GET['kategorie'] ?? '', 48);
        $kategorien = [];
        foreach ($designs as $design) {
            $k = (string) $design['category'];
            if ($k !== '' && !in_array($k, $kategorien, true)) {
                $kategorien[] = $k;
            }
        }
        sort($kategorien);

        if ($filter !== '') {
            $designs = array_values(array_filter(
                $designs,
                static fn (array $d): bool => (string) $d['category'] === $filter
            ));
        }

        $styles = '';
        $warnings = [];
        foreach ($designs as $design) {
            $styles .= Design::css($design, '.d-' . $design['id']);
            $warnings[$design['id']] = Design::warnings($design);
        }

        View::page('admin/designs', [
            'layout'     => 'admin/layout',
            'locale'     => $locale,
            'current'    => '/designs',
            'meta'       => ['title' => 'Designs (v2)', 'noindex' => true],
            'designs'    => $designs,
            'warnings'   => $warnings,
            'styles'     => $styles,
            'values'     => Design::bindValues(self::BEISPIEL, $locale),
            'kategorien' => $kategorien,
            'filter'     => $filter,
            'themen'     => Themes::all(),
            'videos'     => DesignVideos::all(),
            'csrf'       => Security::csrf(),
        ]);
    }

    /** POST-Zweig: aendert etwas und leitet auf dieselbe Adresse zurueck. */
    private function handle(string $locale): void
    {
        if (!Security::checkCsrf($_POST['csrf'] ?? null)) {
            $this->zurueck($locale, 'fehler=csrf');
        }

        $was = (string) ($_POST['was'] ?? '');

        if ($was === 'kopyala') {
            $this->zurueck($locale, $this->kopiere());
        }
        if ($was === 'temadan') {
            $this->zurueck($locale, $this->ausThema());
        }
        if ($was === 'leer') {
            $this->zurueck($locale, $this->leer());
        }
        if ($was === 'loeschen') {
            $this->zurueck($locale, $this->loeschen());
        }
        if ($was === 'durum') {
            $this->zurueck($locale, $this->durum());
        }
        if ($was === 'videos-kaydet') {
            $this->zurueck($locale, $this->videosSpeichern());
        }
        if (str_starts_with($was, 'video-loeschen-')) {
            $this->zurueck($locale, $this->videoLoeschen(substr($was, strlen('video-loeschen-'))));
        }

        $this->zurueck($locale, 'fehler=unbekannt');
    }

    /** 303, damit ein Neuladen die Aktion nicht wiederholt. */
    private function zurueck(string $locale, string $query): never
    {
        header('Location: ' . I18n::path('/admin/designs', $locale) . ($query !== '' ? '?' . $query : ''), true, 303);
        exit;
    }

    /*
     * Eine Vorlage von vorn - ohne Ebenen, ohne Abschnitte.
     *
     * Bis heute ging beides nur von etwas Bestehendem aus: aus einem Thema
     * ueber die Anordnung einer vorhandenen Vorlage, oder als Kopie. Das hatte
     * einen Grund - die Textebenen wurden am gerenderten Original gemessen und
     * stehen in keinem Thema, also haette eine leere Karte kein Wort getragen.
     *
     * Der Grund ist weg: seit derselben Sitzung legt der Editor auch Text- und
     * Formebenen an, und der Kasten jeder Ebene laesst sich ziehen. Eine leere
     * Vorlage ist damit ein Anfang und keine Sackgasse mehr.
     *
     * Sie beginnt als Entwurf. Was Design::complete() nicht kennt, fuellt es
     * mit seinen Vorgaben - Seitenverhaeltnis, Palette, Schriften; erfunden
     * wird hier nichts.
     */
    private function leer(): string
    {
        $name = Security::clean($_POST['neuer_name'] ?? '', 60);
        if ($name === '') {
            return 'fehler=name';
        }

        $kennung = Design::key($name);
        if ($kennung === '' || Design::findById($kennung) !== null) {
            return 'fehler=belegt';
        }

        Design::save(Design::complete([
            'id'      => $kennung,
            'slug'    => $kennung,
            'name'    => ['de' => $name, 'en' => $name],
            'status'  => 'draft',
            'version' => 1,
            // Ganz vorn: wer gerade etwas angelegt hat, sucht es oben.
            'sort'    => Design::sortVorn(),
        ]));

        return 'ok=angelegt';
    }

    /** Eine vorhandene Vorlage als neuer Entwurf. */
    private function kopiere(): string
    {
        $quelle = Design::findById(Security::clean($_POST['quelle'] ?? '', 64));
        $name   = Security::clean($_POST['neuer_name'] ?? '', 60);

        if ($quelle === null) {
            return 'fehler=quelle';
        }
        if ($name === '') {
            return 'fehler=name';
        }

        $neu = Design::copy($quelle, $name, ['de' => $name, 'en' => $name]);
        $neu['sort'] = Design::sortVorn();

        if ($neu['id'] === '' || Design::findById($neu['id']) !== null) {
            return 'fehler=belegt';
        }

        Design::save($neu);
        return 'ok=kopiert';
    }

    /**
     * Ein altes Thema ueber die Anordnung einer vorhandenen Vorlage.
     *
     * Nicht fromTheme() allein: das Thema kennt Farben, Schriften und Bewegung,
     * aber keine Karte. Die Anordnung kommt deshalb aus einer Vorlage, die
     * jemand gemessen hat - erfunden wird nichts.
     */
    private function ausThema(): string
    {
        $thema = Themes::find(Security::clean($_POST['thema'] ?? '', 64));
        $basis = Design::findById(Security::clean($_POST['basis'] ?? '', 64));
        $name  = Security::clean($_POST['neuer_name'] ?? '', 60);

        if ($thema === null) {
            return 'fehler=thema';
        }
        if ($basis === null) {
            return 'fehler=basis';
        }
        if ($name === '') {
            return 'fehler=name';
        }

        // Die gezeichnete Szene liegt als Datei vor, nicht im Dokument. Hat das
        // Thema keine, behaelt die neue Vorlage die der Basis - und die Meldung
        // sagt es, sonst sucht jemand die Ecken.
        $kunst = glob(__DIR__ . '/../../public/assets/designs/' . $thema['id'] . '-*.svg') ?: [];
        sort($kunst);
        $pfade = array_map(static fn (string $p): string => '/assets/designs/' . basename($p), $kunst);

        $neu = Design::copy(
            Design::dress($basis, Design::fromTheme($thema), $pfade),
            $name,
            ['de' => $name, 'en' => $name]
        );

        if ($neu['id'] === '' || Design::findById($neu['id']) !== null) {
            return 'fehler=belegt';
        }

        $neu['sort'] = Design::sortVorn();
        Design::save($neu);

        // Wie viele Ecken hat die Basis, und wie viele bringt das Thema mit?
        // Zwei Teile ueber drei Ecken heisst: eine bleibt fremd, und das gehoert
        // gesagt statt entdeckt.
        $ecken = 0;
        foreach ($basis['layers'] as $ebene) {
            if ($ebene['type'] === 'image' && str_starts_with((string) $ebene['src'], '/assets/designs/')) {
                $ecken++;
            }
        }

        if ($pfade === []) {
            return 'ok=uebernommen_ohne_kunst';
        }
        return count($pfade) < $ecken ? 'ok=uebernommen_teilweise' : 'ok=uebernommen';
    }

    /*
     * Eine Vorlage wegnehmen - in zwei Schritten.
     *
     * Der erste Klick fragt nur, der zweite loescht. Und die Frage nennt eine
     * Zahl: wie viele Einladungen an dieser Vorlage haengen. Sie ueberleben
     * das Loeschen - jede traegt eine eingefrorene Kopie -, aber sie lassen
     * sich danach nicht mehr auffrischen. Das gehoert vor den Klick und nicht
     * danach.
     *
     * Gezaehlt wird hier und nicht im Formular geglaubt, genau wie bei den
     * Hinweisen in durum(): was das Formular mitbringt, kann alt sein.
     */
    private function loeschen(): string
    {
        $design = Design::findById(Security::clean($_POST['quelle'] ?? '', 64));
        if ($design === null) {
            return 'fehler=quelle';
        }

        $id = (string) $design['id'];

        /*
         * Ohne Rueckfrage, ausdruecklich gewuenscht: "sil dedigimde direkt
         * silsin, emin misin diye sormasin". Die Zahl der Einladungen daran
         * wandert in die Meldung danach - sie bleiben ohnehin stehen, jede
         * traegt ihre eingefrorene Kopie; was fehlt, ist nur der Weg zu einer
         * neueren Fassung.
         */
        $daran = count(InvitationsV2::byDesign($id));

        Design::delete($id);

        return $daran > 0 ? 'ok=geloescht&n=' . $daran : 'ok=geloescht';
    }

    /** Aktiv/inaktiv. Hinweise halten nicht auf, aber sie werden gesagt. */
    private function durum(): string
    {
        $design = Design::findById(Security::clean($_POST['quelle'] ?? '', 64));
        if ($design === null) {
            return 'fehler=quelle';
        }

        $ziel = (string) $design['status'] === 'active' ? 'inactive' : 'active';

        // Beim Abschalten fragt niemand. Beim Einschalten schon - und die
        // Hinweise werden hier neu gerechnet, nicht aus dem Formular geglaubt.
        if ($ziel === 'active' && !isset($_POST['bestaetigt'])) {
            $meldungen = Design::warnings($design);
            if ($meldungen !== []) {
                return 'frage=aktivieren&id=' . rawurlencode((string) $design['id']) . '&n=' . count($meldungen);
            }
        }

        $design['status'] = $ziel;
        Design::save($design);

        return 'ok=' . ($ziel === 'active' ? 'aktiv' : 'inaktiv');
    }

    /**
     * Die Abschnitte, wie sie mit dem GERADE GETIPPTEN Formular aussaehen.
     *
     * Die Karte in der Mitte folgt jedem Tastendruck, weil sie aus
     * CSS-Variablen und Inline-Kaesten besteht - das kann ein Skript. Die
     * Abschnitte kann es nicht: sie sind gedrucktes Markup, je Art ein
     * anderes. Wer im Panel einen Ablauf tippte, sah davon nichts, und das
     * Blatt dahinter schon gar nicht.
     *
     * Gerendert wird deshalb weiter hier, nur ohne zu speichern. Ein zweiter
     * Zeichner im Browser waere schneller und haette eine zweite Wahrheit -
     * die laeuft mit dem naechsten Abschnittstyp auseinander, und dann zeigt
     * die Vorschau etwas, das es draussen nicht gibt.
     *
     * Was dieser Weg NICHT tut, ist das Wichtigste an ihm: er speichert
     * nicht. Ein Design::save hier waere ein zweiter Speicherpfad ohne
     * Fassungspruefung - die Vorschau wuerde beim Tippen ueberschreiben, was
     * ein anderer Tab gerade abgelegt hat. Das Token wird trotzdem geprueft:
     * ein angemeldeter Weg, der fremde Formulare rendert, ist zwar harmlos,
     * aber es gibt keinen Grund, ihn offen zu lassen.
     *
     * @param array<string,string> $params
     */
    public function vorschau(array $params): void
    {
        $locale = $params['locale'];
        Admin::requireLogin($locale);

        header('Content-Type: text/html; charset=utf-8');
        // Eine Vorschau gehoert niemandem sonst und ueberlebt keinen Klick.
        header('Cache-Control: no-store');

        if (!Security::checkCsrf($_POST['csrf'] ?? null)) {
            http_response_code(419);
            return;
        }

        $design = Design::find(Security::clean($params['slug'] ?? '', 64));
        if ($design === null) {
            http_response_code(404);
            return;
        }

        /*
         * Ohne die hochgeladenen Bilder: Dateien schickt das Skript gar nicht
         * mit (drei Megabyte je Tastendruck), und ohne Dateien ist
         * mitHochgeladenenBildern() ohnehin ein Durchreicher, der aber
         * schreiben duerfte. Hier wird nichts geschrieben.
         */
        $doc = Design::fromPost($design, $_POST);

        $sel   = '.d-' . $doc['id'];
        $daten = self::BEISPIEL + [
            'families' => ['bride' => 'Familie Berger', 'groom' => 'Familie Lindqvist'],
            'program'  => [
                ['time' => '15:30', 'title' => $locale === 'de' ? 'Trauung' : 'Tören'],
                ['time' => '17:00', 'title' => $locale === 'de' ? 'Empfang' : 'Karşılama'],
                ['time' => '19:30', 'title' => 'Dinner'],
            ],
        ];

        // Kein Formular in der Vorschau: die Zusage schickt hier niemand ab.
        $abschnitte = DesignSections::html($doc, $daten, 'de', '', ['csrf' => '', 'sent' => false]);

        echo '<style>' . DesignSections::css($doc, $sel) . '</style>'
           . DesignSections::flaeche($doc, ltrim($sel, '.'), $abschnitte);
    }

    /**
     * Editor einer Vorlage. GET zeigt, POST speichert.
     *
     * @param array<string,string> $params
     */
    public function edit(array $params): void
    {
        $locale = (string) ($params['locale'] ?? 'de');
        Admin::requireLogin($locale);

        $design = Design::find(Security::clean($params['slug'] ?? '', 96));
        if ($design === null) {
            (new PageController())->notFound($locale);
            return;
        }

        if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
            // Der Auffrischknopf schickt sein eigenes Formular: er soll
            // nicht nebenbei die Vorlage speichern.
            $was = (string) ($_POST['was'] ?? '');
            if (str_starts_with($was, 'auffrischen-')) {
                $this->frischeAuf($locale, $design, substr($was, strlen('auffrischen-')));
            }

            $this->speichere($locale, $design);
            return;
        }

        $scope  = '.d-' . $design['id'];
        $werte  = Design::bindValues(self::BEISPIEL, $locale);

        View::page('admin/design-edit', [
            'layout'   => 'admin/layout',
            'locale'   => $locale,
            'current'  => '/designs',
            'meta'     => [
                'title'   => 'Design: ' . ($design['name']['de'] ?? ''),
                'noindex' => true,
                // Nur die Vorschau braucht ein Skript. Der Rest ist ein
                // Formular und bleibt ohne.
                'scripts' => ['/assets/design-editor.js'],
            ],
            'design'   => $design,
            'scope'    => ltrim($scope, '.'),
            /*
             * Die Filmablage. Sie stand bisher nur auf der Uebersicht, wo man
             * sie fuellt - gebraucht wird sie aber hier, wo man einen Film
             * AUSWAEHLT. Ohne sie blieb der Weg zum Vorspann: Adresse
             * abtippen oder dieselbe Datei ein zweites Mal hochladen.
             */
            'videos'   => DesignVideos::all(),
            'styles'   => Design::css($design, $scope),
            'seite'    => Design::html($design, $werte, $locale, 'page'),
            'karte'    => Design::html($design, $werte, $locale, 'card'),
            // Wer haengt noch an einer aelteren Fassung? Zwei Faecher,
            // getrennt, weil der Weg dorthin verschieden lang ist.
            'veraltet' => InvitationsV2::outdated(
                InvitationsV2::byDesign((string) $design['id']),
                (int) $design['version']
            ),
            // Die zweite Frage vor den veroeffentlichten Einladungen.
            'fragen'   => (string) ($_GET['auffrischen'] ?? '') === 'veroeffentlicht',
            'warnings' => Design::warnings($design),
            'csrf'     => Security::csrf(),
        ]);
    }

    /** @param array<string,mixed> $design */
    /**
     * Eine neue Bildebene anlegen, wenn das Formular einen Namen mitbringt.
     *
     * NACH fromPost und nicht davor: fromPost setzt die Rechte jeder Ebene aus
     * den Haken des Formulars, und fuer eine Ebene, die es beim Absenden noch
     * gar nicht gab, steht dort kein einziger Haken - sie kaeme also mit lauter
     * false auf die Welt und waere fuer das Paar unsichtbar. Hier gebaut, traegt
     * sie genau die Rechte, um die es geht.
     *
     * Sie wird VORNE eingefuegt, nicht angehaengt: die Stapelreihenfolge ist die
     * Reihenfolge der Liste (Design::css schreibt z-index als index+1), und ein
     * Hintergrund gehoert unter alles andere. Die relative Ordnung der uebrigen
     * Ebenen bleibt dabei unveraendert, ihre Nummern ruecken nur gemeinsam um
     * eins.
     *
     * Mit einem der drei Zuschnitte ueber die volle Breite - als Startpunkt,
     * nicht als Urteil: seit es den Kasten im Abschnitt "Anordnung" gibt, laesst
     * sich jede Ebene danach hinstellen. Vorher war der Zuschnitt endgueltig,
     * und eine Ebene irgendwo hinzusetzen waere eine Falle gewesen.
     *
     * @param array<string,mixed> $doc
     * @param array<string,mixed> $post
     * @return array<string,mixed>
     */
    private function mitNeuerEbene(array $doc, array $post): array
    {
        $name = Security::clean($post['neue_ebene_label'] ?? '', 60);
        if ($name === '') {
            return $doc;
        }

        // Die Kennung kommt aus dem Namen, wie ueberall sonst. Ist sie belegt
        // oder ergibt der Name keine (etwa bei reinen Sonderzeichen), haengt
        // eine Zahl an - zwei Ebenen mit derselben Kennung waeren im CSS
        // dieselbe Regel.
        $basis = Design::key($name) ?: 'bild';
        $belegt = array_map(static fn (array $el): string => (string) $el['id'], $doc['layers']);
        $id = $basis;
        for ($n = 2; in_array($id, $belegt, true); $n++) {
            $id = $basis . '-' . $n;
        }

        $art = (string) ($post['neue_ebene_typ'] ?? 'photo');
        if (!in_array($art, ['photo', 'video', 'image', 'text', 'shape'], true)) {
            $art = 'photo';
        }

        $schrift = $art === 'text' || $art === 'shape';

        /*
         * Der Zuschnitt gilt nur fuer das, was eine Flaeche ist.
         *
         * Eine Textebene ueber die ganze Karte waere genau der Kasten, an dem
         * das Ziehen im August gescheitert ist: unsichtbar, kartenhoch, und er
         * faengt jeden Klick ab. Ein neuer Text beginnt deshalb als schmale
         * Zeile in der Mitte, mit auto-Hoehe - dort steht er, dort sieht man
         * ihn, und von dort zieht man ihn hin, wo er hin soll.
         */
        $box = $schrift
            ? ['x' => 10, 'y' => 45, 'w' => 80, 'h' => $art === 'shape' ? 10 : 0]
            : match ((string) ($post['neue_ebene_schnitt'] ?? 'voll')) {
                'oben'  => ['x' => 0, 'y' => 0,  'w' => 100, 'h' => 50],
                'unten' => ['x' => 0, 'y' => 50, 'w' => 100, 'h' => 50],
                default => ['x' => 0, 'y' => 0,  'w' => 100, 'h' => 100],
            };

        /*
         * Die Rechte je nach Art. photo gibt dem Paar ein Feld zum Hochladen -
         * bei einem Text oder einer Form waere es ein Feld ohne Gegenstueck.
         * Ein Schmuckbild des Grafikers (image) gehoert ihm und nicht dem Paar.
         */
        $rechte = match ($art) {
            'photo', 'video' => ['edit' => true, 'photo' => true, 'hide' => true],
            'text'           => ['edit' => true, 'text' => true],
            default          => ['edit' => true],
        };

        $ebene = Design::completeElement([
            'id'    => $id,
            'label' => $name,
            'type'  => $art,
            'spot'  => (string) ($post['neue_ebene_spot'] ?? 'card'),
            'box'   => $box + ['anchor' => 'topleft'],
            /*
             * Der erste Satz. Ohne ihn haette die Ebene keinen Knoten -
             * Design::html laesst eine Textebene ohne Text ganz weg, und dann
             * waere sie angelegt und trotzdem nicht zu sehen.
             */
            'text'  => $art === 'text'
                ? (static function (string $wort): array {
                    return ['de' => $wort, 'en' => $wort];
                })(Security::clean($post['neue_ebene_text'] ?? '', 200) ?: $name)
                : ['de' => '', 'en' => ''],
            'permissions' => $rechte,
        ]);

        $start = $schrift ? null : ($_FILES['neue_ebene_bild'] ?? null);
        if (is_array($start) && ((int) ($start['error'] ?? UPLOAD_ERR_NO_FILE)) === UPLOAD_ERR_OK) {
            $pfad = $ebene['type'] === 'video'
                ? Media::storeVideo($start, 'designs')
                : Media::storeGraphic($start, 'designs');
            if ($pfad !== null) {
                $ebene['src'] = $pfad;
            }
        }

        array_unshift($doc['layers'], $ebene);

        return $doc;
    }

    /**
     * Hochgeladene Bilder in die Eingaben einsetzen, bevor fromPost sie liest.
     *
     * Der Weg ueber $_POST ist Absicht: Design::fromPost() schreibt das Feld
     * `src_<id>` ohnehin schon in die Ebene, und dort steht die einzige Stelle,
     * die entscheidet, was ein gueltiger Pfad ist. Ein zweiter Schreibweg
     * daneben waere eine zweite Wahrheit - der Upload sagt nur, was in dem
     * Feld stehen soll, als haette es jemand hineingetippt.
     *
     * Ohne Datei bleibt das getippte Feld unangetastet: so kann ein Grafiker
     * weiterhin einen Pfad aus assets/designs/ eintragen, wie bisher.
     *
     * Das ersetzte Bild wird NICHT geloescht, und das ist der Unterschied zur
     * Einladung, wo genau das richtig ist. Eine Vorlage steht eingefroren in
     * jedem design_snapshot bereits veroeffentlichter Einladungen; die zeigen
     * weiter auf die alte Datei. Sie wegzuraeumen risse das Bild aus Karten,
     * die laengst bei den Gaesten sind - dieselbe Ueberlegung wie in Spec §14
     * zu den drei Pruefern, die jetzt bei jeder Ansicht laufen. Was liegen
     * bleibt, kostet Platz; was fehlt, kostet eine Einladung.
     *
     * @param array<string,mixed> $design
     * @param array<string,mixed> $post
     * @return array<string,mixed>
     */
    private function mitHochgeladenenBildern(array $design, array $post): array
    {
        foreach (Design::complete($design)['layers'] as $ebene) {
            $typ = (string) ($ebene['type'] ?? '');
            $id  = (string) $ebene['id'];

            if (in_array($typ, ['image', 'photo'], true)) {
                $file = $_FILES['bild_' . $id] ?? null;
                if (!is_array($file) || ((int) ($file['error'] ?? UPLOAD_ERR_NO_FILE)) !== UPLOAD_ERR_OK) {
                    continue;
                }

                // storeGraphic und nicht store: die Vorlagen arbeiten mit SVG, und
                // getimagesize() erkennt SVG nicht - store() gaebe hier fuer jede
                // Zeichnung null zurueck. storeGraphic putzt das SVG und behaelt
                // bei allem anderen den Alphakanal, den eine Ebene ueber der Karte
                // braucht.
                $pfad = Media::storeGraphic($file, 'designs');
                if ($pfad !== null) {
                    $post['src_' . $id] = $pfad;
                }
                continue;
            }

            if ($typ === 'video') {
                $film = $_FILES['video_' . $id] ?? null;
                if (is_array($film) && ((int) ($film['error'] ?? UPLOAD_ERR_NO_FILE)) === UPLOAD_ERR_OK) {
                    // storeVideo prueft die Art am Dateiinhalt und laesst nur
                    // mp4/webm/mov durch. Kein Umkodieren - der Server kann es
                    // nicht, und die Vorgabe im Panel sagt das auch so.
                    $pfad = Media::storeVideo($film, 'designs');
                    if ($pfad !== null) {
                        $post['src_' . $id] = $pfad;
                    }
                }

                $bild = $_FILES['poster_' . $id] ?? null;
                if (is_array($bild) && ((int) ($bild['error'] ?? UPLOAD_ERR_NO_FILE)) === UPLOAD_ERR_OK) {
                    // store und nicht storeGraphic: ein Standbild ist ein Foto,
                    // kein Schmuck - Transparenz braucht es nicht, und 1600 px
                    // reichen hinter einem Film allemal.
                    $pfad = Media::store($bild, 'designs');
                    if ($pfad !== null) {
                        $post['posterpfad_' . $id] = $pfad;
                    }
                }
            }
        }

        // Der Vorspann haengt an keiner Ebene, also auch an keiner Schleife.
        // Derselbe Weg ueber $_POST wie oben: der Upload sagt nur, was in dem
        // Feld stehen soll.
        $vorspann = $_FILES['intro_datei'] ?? null;
        if (is_array($vorspann) && ((int) ($vorspann['error'] ?? UPLOAD_ERR_NO_FILE)) === UPLOAD_ERR_OK) {
            $pfad = Media::storeVideo($vorspann, 'designs');
            if ($pfad !== null) {
                $post['intro_video'] = $pfad;
            }
        }

        $vorspannBild = $_FILES['intro_poster_datei'] ?? null;
        if (is_array($vorspannBild) && ((int) ($vorspannBild['error'] ?? UPLOAD_ERR_NO_FILE)) === UPLOAD_ERR_OK) {
            $pfad = Media::store($vorspannBild, 'designs');
            if ($pfad !== null) {
                $post['intro_poster'] = $pfad;
            }
        }

        // Dasselbe noch einmal fuer das Blatt des Schlusses - eine Zeile
        // Unterschied, und beide sagen nur, was im Pfadfeld stehen soll.
        $schluss = $_FILES['sectionsbg_end_datei'] ?? null;
        if (is_array($schluss) && ((int) ($schluss['error'] ?? UPLOAD_ERR_NO_FILE)) === UPLOAD_ERR_OK) {
            $pfad = Media::store($schluss, 'designs');
            if ($pfad !== null) {
                $post['sectionsbg_end'] = $pfad;
            }
        }

        $grund = $_FILES['sectionsbg_datei'] ?? null;
        if (is_array($grund) && ((int) ($grund['error'] ?? UPLOAD_ERR_NO_FILE)) === UPLOAD_ERR_OK) {
            $pfad = Media::store($grund, 'designs');
            if ($pfad !== null) {
                $post['sectionsbg'] = $pfad;
            }
        }


        /*
         * Und das eigene Blatt eines Abschnitts. Dieselbe Bewegung wie oben:
         * die Datei sagt nur, was im Pfadfeld stehen soll.
         *
         * Ueber die Nummern des FORMULARS und nicht ueber das Dokument: ein
         * Abschnitt, den jemand gerade erst hinzugefuegt hat, steht noch in
         * keinem Dokument, soll sein Blatt aber schon mitbringen duerfen. Die
         * Obergrenze ist dieselbe wie in Design::fromPost().
         */
        for ($i = 0; $i <= 39; $i++) {
            if (!isset($post['sec_type_' . $i])) {
                continue;
            }

            /*
             * Die Dateien der Einstellungen dieser Zeile.
             *
             * Welche es gibt, sagt der Katalog: jede Einstellung vom Typ
             * "src" darf eine Datei bekommen, und 'kind' sagt, welche
             * Pruefung sie besteht. Deshalb steht hier eine Schleife und kein
             * Zweig je Feld - eine neue Vorlageneinstellung mit Datei ist ein
             * Eintrag im Katalog und keine Zeile hier.
             *
             * Die Art kommt aus dem FORMULAR, nicht aus dem Dokument: wer die
             * Art einer Zeile gerade eben umgestellt hat, soll die Datei
             * dieser neuen Art hochladen duerfen.
             */
            foreach (SectionRegistry::settings((string) $post['sec_type_' . $i]) as $schluessel => $schema) {
                if ((string) ($schema['type'] ?? '') !== 'src') {
                    continue;
                }

                $datei = $_FILES['sec_setdatei_' . $schluessel . '_' . $i] ?? null;
                if (!is_array($datei) || ((int) ($datei['error'] ?? UPLOAD_ERR_NO_FILE)) !== UPLOAD_ERR_OK) {
                    continue;
                }

                // Jede Pruefung sieht in die Datei und nicht auf ihren Namen.
                $pfad = match ((string) ($schema['kind'] ?? '')) {
                    'audio' => Media::storeAudio($datei, 'designs'),
                    'video' => Media::storeVideo($datei, 'designs'),
                    default => Media::storeGraphic($datei, 'designs'),
                };

                if ($pfad !== null) {
                    $post['sec_set_' . $schluessel . '_' . $i] = $pfad;
                }
            }

            $blatt = $_FILES['sec_bg_datei_' . $i] ?? null;
            if (!is_array($blatt) || ((int) ($blatt['error'] ?? UPLOAD_ERR_NO_FILE)) !== UPLOAD_ERR_OK) {
                continue;
            }

            // storeGraphic wie bei den Ebenen: SVG wird geputzt durchgereicht,
            // alles andere behaelt seinen Alphakanal - ein Blatt mit
            // durchsichtigen Raendern soll die Papierfarbe darunter zeigen.
            $pfad = Media::storeGraphic($blatt, 'designs');
            if ($pfad !== null) {
                $post['sec_bg_' . $i] = $pfad;
            }
        }

        return $post;
    }

    /**
     * Einladungen auf den heutigen Stand dieser Vorlage heben.
     *
     * Zwei Knoepfe und nicht einer, weil die beiden Gruppen nicht gleich
     * schwer wiegen. Ein Entwurf ist noch niemandem geschickt worden. Eine
     * veroeffentlichte Einladung liegt bei den Gaesten, ihr Bild aendert sich
     * mit, und der Weg dorthin geht deshalb ueber eine zweite Frage - die
     * Seite stellt sie, nicht der Browser: im ganzen Haus steht kein
     * onclick=, und die Richtlinie laesst nur eigene Skriptdateien zu.
     *
     * Gezaehlt wird hier noch einmal und nicht aus dem Formular gelesen:
     * zwischen dem Anzeigen und dem Druecken kann eine Einladung
     * dazugekommen sein, und eine Zahl aus dem Browser waere eine Zahl, die
     * jemand aendern kann.
     *
     * $design ist der Stand aus Design::find() und damit bereits durch
     * complete() gegangen - genau die Form, die beim Erstellen eingefroren
     * wird.
     *
     * @param array<string,mixed> $design
     */
    private function frischeAuf(string $locale, array $design, string $welche): never
    {
        $ziel = I18n::path('/admin/designs/' . $design['slug'], $locale);

        if (!Security::checkCsrf($_POST['csrf'] ?? null)) {
            header('Location: ' . $ziel . '?fehler=csrf', true, 303);
            exit;
        }

        $veraltet = InvitationsV2::outdated(
            InvitationsV2::byDesign((string) $design['id']),
            (int) $design['version']
        );

        // Nur die beiden Woerter, die es gibt. Ein drittes taete sonst
        // still das, was das erste tut.
        $slugs = match ($welche) {
            'entwuerfe'      => $veraltet['draft'],
            'veroeffentlicht' => $veraltet['published'],
            default          => [],
        };

        foreach ($slugs as $slug) {
            InvitationsV2::refreshDesign($slug, $design);
        }

        header('Location: ' . $ziel . '?ok=aufgefrischt&n=' . count($slugs), true, 303);
        exit;
    }

    private function speichere(string $locale, array $design): void
    {
        $ziel = I18n::path('/admin/designs/' . $design['slug'], $locale);

        if (!Security::checkCsrf($_POST['csrf'] ?? null)) {
            header('Location: ' . $ziel . '?fehler=csrf', true, 303);
            exit;
        }

        // Wer das Formular geoeffnet hat, hat eine Fassungsnummer mitbekommen.
        // Ist sie kleiner als die gespeicherte, hat jemand anders dazwischen
        // gespeichert - dann wird hier nichts ueberschrieben.
        $gesehen = (int) ($_POST['version'] ?? 0);
        if ($gesehen > 0 && $gesehen < (int) $design['version']) {
            header('Location: ' . $ziel . '?fehler=veraltet', true, 303);
            exit;
        }

        $doc = Design::fromPost($design, $this->mitHochgeladenenBildern($design, $_POST));
        $doc = $this->mitNeuerEbene($doc, $_POST);

        /*
         * Ein Startsatz, falls jemand einen der Knoepfe gedrueckt hat.
         *
         * NACH fromPost und aus demselben Grund wie die neue Bildebene: die
         * Abschnitte aus dem Formular sind dann schon eingelesen, und der
         * Satz haengt nur an, was noch fehlt. Anders herum stuenden die neuen
         * Abschnitte im Dokument, bevor das Formular gelesen wird - und das
         * Formular kennt sie nicht, also raeumte es sie sofort wieder weg.
         *
         * Kein eigener Zweig in handle(): der Knopf schickt dasselbe Formular
         * ab wie "Speichern". So geht die angefangene Arbeit nicht verloren,
         * bloss weil jemand mitten darin einen Satz hinlegen will.
         */
        $satz = Security::clean($_POST['starter'] ?? '', 32);
        if ($satz !== '') {
            $doc = Design::withStarter($doc, $satz);
        }

        Design::save($doc);

        header('Location: ' . $ziel . '?ok=gespeichert', true, 303);
        exit;
    }

    /**
     * Die Bibliothek speichern - bestehende Zeilen und hoechstens einen neuen
     * Film. Ein Upload je Absenden reicht: mehrere gleichzeitig waeren bei
     * 100 MB je Datei ein Zeitlimit, kein Komfort.
     *
     * Die CSRF-Pruefung steht schon in handle() und nicht noch einmal hier:
     * jeder Zweig kommt durch dieselbe Tuer.
     */
    private function videosSpeichern(): string
    {
        $rows = [];
        for ($i = 0; $i < DesignVideos::MAX; $i++) {
            if (!isset($_POST['vid_id_' . $i])) {
                continue;
            }
            $rows[] = [
                'id'       => (string) $_POST['vid_id_' . $i],
                'label'    => (string) ($_POST['vid_label_' . $i] ?? ''),
                'mp4'      => (string) ($_POST['vid_mp4_' . $i] ?? ''),
                'webm'     => (string) ($_POST['vid_webm_' . $i] ?? ''),
                'poster'   => (string) ($_POST['vid_poster_' . $i] ?? ''),
                'category' => (string) ($_POST['vid_cat_' . $i] ?? ''),
            ];
        }

        $datei = $_FILES['vid_neu_datei'] ?? null;
        if (is_array($datei) && ((int) ($datei['error'] ?? UPLOAD_ERR_NO_FILE)) === UPLOAD_ERR_OK) {
            $pfad = Media::storeVideo($datei, 'videos');
            if ($pfad !== null) {
                $poster = '';
                $bild = $_FILES['vid_neu_poster'] ?? null;
                if (is_array($bild) && ((int) ($bild['error'] ?? UPLOAD_ERR_NO_FILE)) === UPLOAD_ERR_OK) {
                    $poster = (string) Media::store($bild, 'videos');
                }
                $rows[] = [
                    'id'       => '',
                    'label'    => (string) ($_POST['vid_neu_label'] ?? ''),
                    'mp4'      => $pfad,
                    'webm'     => '',
                    'poster'   => $poster,
                    'category' => '',
                ];
            }
        }

        DesignVideos::save($rows);

        return 'ok=gespeichert';
    }

    /**
     * Einen Film aus der Bibliothek nehmen.
     *
     * Die Datei bleibt liegen. Dieselbe Ueberlegung wie bei den Bildebenen:
     * eine bereits versendete Einladung zeigt auf sie, und was fehlt, kostet
     * eine Einladung - was liegen bleibt, kostet Platz.
     */
    private function videoLoeschen(string $id): string
    {
        DesignVideos::save(array_values(array_filter(
            DesignVideos::all(),
            static fn (array $row): bool => $row['id'] !== $id
        )));

        return 'ok=gespeichert';
    }
}
