<?php

declare(strict_types=1);

namespace Atelier\Controllers;

use Atelier\Config;
use Atelier\Design;
use Atelier\DesignSections;
use Atelier\DesignVideos;
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
            $this->nichtGefunden();
            return;
        }

        /*
         * Der Entwurf steht VOR der Wahl des Designs, denn er weiss selbst,
         * zu welchem Design er gehoert.
         *
         * Die Kennung kommt aus der Adresse (?taslak=), nach dem Speichern aus
         * dem Formular - sonst legte jedes Speichern einen neuen Entwurf an und
         * der Kunde saemmelte Links, von denen nur der letzte stimmt.
         */
        $token = Security::clean($_POST['token'] ?? $_GET['taslak'] ?? '', 40);

        /*
         * Steht nirgends eine Kennung, fragen wir den Browser. Bisher fuehrte
         * der Weg zurueck in einen Entwurf ausschliesslich ueber den Link -
         * wer ihn verlor, verlor die Arbeit, obwohl sie noch in der Datenbank
         * lag. Nur bei einem GET: ein POST bringt seine Kennung selbst mit,
         * und ein alter Keks duerfte sie nicht ueberstimmen.
         *
         * Der Keks merkt sich die eigene, unfertige Arbeit - er zaehlt oder
         * verfolgt nichts und braucht darum keine Einwilligung.
         */
        if ($token === '' && ($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'GET') {
            $token = Security::clean($_COOKIE[self::ENTWURF_KEKS] ?? '', 40);
        }

        $werte  = $token !== '' ? InvitationsV2::draft($token) : null;
        $values = is_array($werte) ? $werte : [];
        if (!is_array($werte)) {
            // Abgelaufen oder veroeffentlicht: die Kennung nicht weiterschleppen,
            // sonst traegt das Formular eine Kennung, hinter der nichts steht.
            $token = '';
        }
        $draftLink = '';

        // Nach dem Absenden steht die Wahl im Formular, nicht mehr in der
        // Adresse - sonst waehlte ein Neuladen ein anderes Design. Zuletzt
        // fragt der Entwurf: aus ihm kommt sie beim Weitermachen ueber den
        // Keks, wo die Adresse nichts sagt.
        $wunsch = Security::clean($_POST['design'] ?? $_GET['design'] ?? (string) ($values['design'] ?? ''), 96);
        $design = $wunsch !== '' ? Design::find($wunsch) : null;
        if ($design === null || (string) $design['status'] !== 'active') {
            $design = $designs[0];
        }
        $design = Design::complete($design);

        $scope = '.d-' . $design['id'];

        $error = '';
        $done  = null;

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
                    $this->merkeEntwurf($token);
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
                    $this->vergissEntwurf();
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
            'filme'   => DesignVideos::all(),
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
        // prog_title_0 ...). Dieselbe Uebersetzung wie in sammleAngaben() -
        // sie steht hier ein zweites Mal, weil die Vorschau die Uebersetzung
        // braucht, bevor ueberhaupt etwas gespeichert ist: sammleAngaben()
        // liest $_POST fuer den Schreibweg, diese Vorschau arbeitet mit
        // Werten, die schon vorliegen.
        $braut = (string) ($values['family_bride'] ?? '');
        $mann  = (string) ($values['family_groom'] ?? '');
        if ($braut !== '' || $mann !== '') {
            $daten['families'] = ['bride' => $braut, 'groom' => $mann];
        }

        $zeilen = [];
        for ($z = 0; $z < 8; $z++) {
            $titel   = (string) ($values['prog_title_' . $z] ?? '');
            $zeichen = (string) ($values['prog_icon_' . $z] ?? '');

            // Eine Zeile darf allein von ihrer Art leben - dann druckt der
            // Katalog seinen Vorschlag. Ob es die Art gibt, entscheidet
            // DesignSections::programRows(); hier wird nur eingesammelt.
            if (trim($titel) === '' && $zeichen === '') {
                continue;
            }

            $zeilen[] = [
                'time'  => (string) ($values['prog_time_' . $z] ?? ''),
                'title' => $titel,
                'icon'  => $zeichen,
            ];
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
    /**
     * Der Name des Kekses, der sich den eigenen Entwurf merkt.
     *
     * Kein Messkeks: er traegt nur die Kennung des unfertigen Entwurfs dieses
     * Browsers, damit "weitermachen" nicht ausschliesslich am Link haengt.
     */
    private const ENTWURF_KEKS = 'atelier_v2_entwurf';

    /** Wie lange der Browser sich den Entwurf merkt - so lange wie der Entwurf lebt. */
    private const ENTWURF_TAGE = 60;

    private function merkeEntwurf(string $token): void
    {
        if ($token === '' || headers_sent()) {
            return;
        }
        setcookie(self::ENTWURF_KEKS, $token, $this->keksRahmen(time() + self::ENTWURF_TAGE * 86400));
    }

    private function vergissEntwurf(): void
    {
        if (headers_sent()) {
            return;
        }
        setcookie(self::ENTWURF_KEKS, '', $this->keksRahmen(time() - 86400));
    }

    /**
     * httponly, weil kein Skript die Kennung braucht - sie ist der Schluessel
     * zu einem fremden Entwurf, wenn sie in falsche Haende geraet. Lax, damit
     * ein Link von aussen den Entwurf trotzdem oeffnet. secure nur ueber
     * HTTPS, sonst sperrte man sich in der Entwicklung selbst aus - dieselbe
     * Pruefung wie in Http::isHttps() und Config::url().
     *
     * @return array<string,mixed>
     */
    private function keksRahmen(int $bis): array
    {
        return [
            'expires'  => $bis,
            'path'     => '/',
            'secure'   => ($_SERVER['HTTPS'] ?? '') === 'on'
                || ($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https',
            'httponly' => true,
            'samesite' => 'Lax',
        ];
    }

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
     * Aus den gespeicherten Daten die Namen machen, die das Formular traegt.
     *
     * Die Gegenrichtung von sammleAngaben(): dort werden family_bride und
     * prog_title_0 zu families und program, hier wieder zurueck. Ohne diesen
     * Weg stuende der Bearbeiten-Bildschirm mit leeren Feldern da, und ein
     * Speichern loeschte alles, was der Kunde beim Veroeffentlichen eingegeben
     * hatte - der schlimmstmoegliche Ausgang fuer einen Bildschirm, der
     * Tippfehler reparieren soll.
     *
     * Ueberall is_string()/is_array(): das Dokument kommt aus JSON und muss
     * nicht die Form haben, die es haben sollte.
     *
     * @param array<string,mixed> $data
     * @return array<string,string>
     */
    private function formularWerte(array $data): array
    {
        $werte = [];

        foreach (DesignWizard::FIELD_ORDER as $feld) {
            $werte[$feld] = is_string($data[$feld] ?? null) ? $data[$feld] : '';
        }

        $familie = is_array($data['families'] ?? null) ? $data['families'] : [];
        $werte['family_bride'] = is_string($familie['bride'] ?? null) ? $familie['bride'] : '';
        $werte['family_groom'] = is_string($familie['groom'] ?? null) ? $familie['groom'] : '';

        // array_values, weil das Formular acht feste Zeilen hat und die
        // gespeicherte Liste loechrige Schluessel tragen koennte.
        $programm = is_array($data['program'] ?? null) ? array_values($data['program']) : [];
        for ($z = 0; $z < 8; $z++) {
            $zeile = is_array($programm[$z] ?? null) ? $programm[$z] : [];
            $werte['prog_time_' . $z]  = is_string($zeile['time'] ?? null) ? $zeile['time'] : '';
            $werte['prog_title_' . $z] = is_string($zeile['title'] ?? null) ? $zeile['title'] : '';
        }

        /*
         * Alles, was unter einer Abschnittskennung steht, geht als
         * sec_<schluessel>_<kennung> zurueck ins Formular. Frueher stand hier
         * nur 'text' - ein Feld, das der Assistent seit heute auch anders
         * nennen kann (hashtag, iban, holder), waere beim Zurueckkommen leer
         * gewesen, und das Paar haette es ein zweites Mal getippt.
         */
        foreach ((array) ($data['sections'] ?? []) as $sid => $eintrag) {
            if (!is_array($eintrag)) {
                continue;
            }
            foreach ($eintrag as $schluessel => $wert) {
                if (is_string($wert)) {
                    $werte['sec_' . (string) $schluessel . '_' . (string) $sid] = $wert;
                }
            }
        }

        return $werte;
    }

    /**
     * Was der Kunde eingetippt hat, in den Namen, die data traegt.
     *
     * Herausgeloest aus publish(), weil der Bearbeiten-Bildschirm dieselben
     * Felder mit denselben Grenzen und denselben Schluesseln lesen muss (Spec
     * §6). Zwei Kopien liefen auseinander, und die zweite waere die falsche.
     *
     * Ein leeres Feld setzt seinen Schluessel NICHT - families, program und
     * sections stehen nur da, wenn etwas drinsteht. Beim Bearbeiten heisst das
     * zugleich: ein geleertes Feld loescht seinen Eintrag, weil saveEdit() die
     * Inhaltsschluessel vorher wegnimmt und dieses Ergebnis darueberlegt.
     *
     * @param array{fields:list<string>,sections:array<string,array<string,mixed>>} $darf
     * @return array<string,mixed>
     */
    private function sammleAngaben(array $darf): array
    {
        $data = [];

        foreach ($darf['fields'] as $feld) {
            $data[$feld] = Security::clean($_POST[$feld] ?? '', $feld === 'message' ? 600 : 160);
        }

        foreach ($darf['sections'] as $sid => $abschnitt) {
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
                    $titel   = Security::clean($_POST['prog_title_' . $z] ?? '', DesignSections::PROGRAM_LEN);
                    $zeichen = Security::clean($_POST['prog_icon_' . $z] ?? '', 32);

                    // Wie in der Vorschau: die Art allein traegt eine Zeile.
                    if ($titel === '' && $zeichen === '') {
                        continue;
                    }

                    $zeilen[] = [
                        'time'  => Security::clean($_POST['prog_time_' . $z] ?? '', DesignSections::PROGRAM_LEN),
                        'title' => $titel,
                        'icon'  => $zeichen,
                    ];
                }
                if ($zeilen !== []) {
                    $data['program'] = $zeilen;
                }
            }

            /*
             * Die eigenen Felder dieses Abschnitts - welche es sind, sagt der
             * Katalog, und die Obergrenze steht dort ebenfalls. Zwei Listen
             * mit denselben Grenzen laufen auseinander.
             *
             * Unter der Kennung, nicht unter einem festen Namen: zwei
             * Textbloecke in einem Dokument wuerden sich sonst einen Platz
             * teilen und der zweite den ersten ueberschreiben. Der Feldname
             * ist deshalb sec_<schluessel>_<kennung> - fuer 'text' ergibt das
             * genau den bisherigen Namen sec_text_<kennung>.
             */
            foreach ($abschnitt['inputs'] ?? [] as $schluessel => $feld) {
                $wert = Security::clean($_POST['sec_' . $schluessel . '_' . $sid] ?? '', (int) $feld['max']);
                if ($wert !== '') {
                    $data['sections'][$sid][$schluessel] = $wert;
                }
            }
        }

        return $data;
    }

    /**
     * Was der Kunde am Aussehen gewaehlt hat.
     *
     * Weissliste zuerst: gefragt wird immer $darf, und was dort nicht steht,
     * faellt still. Diese Schleife ist trotzdem Bequemlichkeit und nicht
     * Sicherheit - personalize() prueft am Ende noch einmal gegen dieselben
     * Rechte.
     *
     * $alt ist die vorhandene Wahl beim Bearbeiten und leer beim
     * Veroeffentlichen. Sie wird fuer genau eine Sache gebraucht: ein Foto,
     * das diesmal nicht neu hochgeladen wurde, behaelt seinen Pfad. Alles
     * andere kommt vollstaendig aus dem Formular - ein nicht gesetzter Haken
     * ist eine Entscheidung und kein fehlender Wert.
     *
     * @param array{palette:array<string,mixed>,fonts:array<string,mixed>,layers:array<string,array<string,bool>>,sections:array<string,array<string,mixed>>} $darf
     * @param array<string,mixed> $alt
     * @return array{palette:array<string,string>,fonts:array<string,string>,layers:array<string,mixed>,sections:array<string,mixed>}
     */
    /**
     * Die Bilder einer Galerie ablegen - und die weggenommenen loeschen.
     *
     * Getrennt von sammleAngaben(), weil Dateien einen Ordner brauchen und
     * der Ordner die Adresse der Einladung ist. Beim Veroeffentlichen
     * entsteht der Slug AUS den Angaben (aus den Namen des Paares), existiert
     * also erst danach: erst die Namen, dann die Adresse, dann die Bilder.
     *
     * $alt sind die bisherigen Daten - beim Veroeffentlichen leer, beim
     * Bearbeiten die gespeicherten. Was darin steht und nicht abgewaehlt
     * wurde, bleibt: ein Formular ohne neue Datei darf keine Galerie leeren.
     *
     * Ein abgewaehltes Bild wird von der Platte genommen und nicht nur aus
     * der Liste: sonst bliebe es unter seiner Adresse oeffentlich abrufbar,
     * obwohl die Einladung es nicht mehr zeigt. Dieselbe Ueberlegung wie beim
     * ersetzten Foto einer Ebene.
     *
     * Die Obergrenze steht im Katalog und wird hier eingehalten, nicht nur im
     * Formular: das Formular kann jeder umgehen, der ein zweites Fenster
     * aufmacht.
     *
     * @param array<string,mixed> $data
     * @param array<string,mixed> $darf
     * @param array<string,mixed> $alt
     * @return array<string,mixed>
     */
    private function mitBildern(array $data, array $darf, string $slug, array $alt): array
    {
        foreach ($darf['sections'] as $sid => $abschnitt) {
            foreach ($abschnitt['inputs'] ?? [] as $schluessel => $feld) {
                if ((string) $feld['type'] !== 'photos') {
                    continue;
                }

                $weg = array_map(
                    static fn (mixed $p): string => is_string($p) ? $p : '',
                    (array) ($_POST['sec_photo_weg_' . $sid] ?? [])
                );

                $behalten = [];
                foreach (DesignSections::sectionPhotos($alt, (string) $sid) as $pfad) {
                    if (in_array($pfad, $weg, true)) {
                        Media::delete($pfad);
                        continue;
                    }
                    $behalten[] = $pfad;
                }

                foreach ($this->hochgeladene('sec_' . $schluessel . '_' . $sid) as $datei) {
                    if (count($behalten) >= (int) $feld['max']) {
                        break;
                    }
                    // Media::store() sieht in die Datei, nicht auf ihren Namen.
                    $pfad = Media::store($datei, 'einladungen/v2/' . $slug);
                    if ($pfad !== null) {
                        $behalten[] = $pfad;
                    }
                }

                if ($behalten !== []) {
                    $data['sections'][$sid][$schluessel] = $behalten;
                }
            }
        }

        return $data;
    }

    /**
     * Ein Mehrfach-Dateifeld als Liste einzelner Dateien.
     *
     * PHP dreht $_FILES bei name="x[]" um: statt einer Liste von Dateien
     * steht dort eine Datei, deren Felder Listen sind. Wer das nicht
     * auseinandernimmt, uebergibt Media::store() ein Array als Dateinamen -
     * und bekommt keinen Fehler, sondern nichts.
     *
     * @return list<array<string,mixed>>
     */
    private function hochgeladene(string $name): array
    {
        $feld = $_FILES[$name] ?? null;
        if (!is_array($feld) || !isset($feld['tmp_name'])) {
            return [];
        }

        if (!is_array($feld['tmp_name'])) {
            return [$feld];
        }

        $out = [];
        foreach (array_keys($feld['tmp_name']) as $i) {
            $out[] = [
                'name'     => $feld['name'][$i] ?? '',
                'type'     => $feld['type'][$i] ?? '',
                'tmp_name' => $feld['tmp_name'][$i] ?? '',
                'error'    => $feld['error'][$i] ?? UPLOAD_ERR_NO_FILE,
                'size'     => $feld['size'][$i] ?? 0,
            ];
        }

        return $out;
    }

    private function sammleWahl(array $darf, string $slug, array $alt, array $sockel): array
    {
        $altLayers = is_array($alt['layers'] ?? null) ? $alt['layers'] : [];

        // Der Typ je Ebene. $darf traegt nur Rechte, und eine Videoebene wird
        // anders gefragt als eine Bildebene: dort eine Datei, hier eine
        // Kennung aus der Bibliothek.
        $typen = [];
        foreach (($sockel['layers'] ?? []) as $el) {
            $typen[(string) ($el['id'] ?? '')] = (string) ($el['type'] ?? '');
        }

        $wahl = ['palette' => [], 'fonts' => [], 'layers' => [], 'sections' => []];

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
            if ($rechte['photo'] && ($typen[$id] ?? '') === 'video') {
                /*
                 * Der gewaehlte Film. Nicht der Pfad kommt aus dem Formular,
                 * sondern die Kennung - so kann niemand eine fremde Adresse
                 * einschleusen, und safeSrc muss hier gar nicht erst greifen.
                 *
                 * Die Wahl steht in der Wahl und nicht im Dokument: gedruckt
                 * wird personalize(snapshot, wahl) bei jedem Aufruf neu (siehe
                 * publish()). Ein Film, der einmal ins Dokument geschrieben
                 * wuerde, waere beim naechsten Zeichnen wieder der der
                 * Vorlage.
                 *
                 * Wer nichts waehlt, behaelt den Film der Vorlage - beim
                 * Bearbeiten den vorher gewaehlten.
                 */
                $kennung = Security::clean($_POST['film_' . $id] ?? '', 64);
                $film    = $kennung !== '' ? DesignVideos::find($kennung) : null;

                if ($film !== null) {
                    $eintrag['src']    = $film['mp4'];
                    $eintrag['poster'] = $film['poster'];
                } else {
                    $vorher = $altLayers[$id]['src'] ?? null;
                    if (is_string($vorher) && $vorher !== '') {
                        $eintrag['src']    = $vorher;
                        $eintrag['poster'] = (string) ($altLayers[$id]['poster'] ?? '');
                    }
                }
            } elseif ($rechte['photo']) {
                // Media::store() sieht in die Datei, nicht auf ihren Namen.
                $pfad = Media::store($_FILES['layer_src_' . $id] ?? [], 'einladungen/v2/' . $slug);
                if ($pfad !== null) {
                    $eintrag['src'] = $pfad;

                    // Das ersetzte Foto sonst nicht mit aufraeumen: es bliebe
                    // auf der Platte liegen und unter seiner alten Adresse
                    // oeffentlich abrufbar, obwohl die Karte es nicht mehr
                    // zeigt. $alt ist beim Veroeffentlichen immer leer (siehe
                    // publish(), drittes Argument), dieser Zweig laeuft also
                    // ausschliesslich beim Bearbeiten, wo tatsaechlich ein
                    // Vorgaenger existieren kann. Nur loeschen, wenn wirklich
                    // ein neues Bild da ist, ein altes stand und beide sich
                    // unterscheiden - sonst risse ein Foto, das jemand einfach
                    // zweimal hochlaedt, die eigene frische Datei mit weg.
                    $vorher = $altLayers[$id]['src'] ?? null;
                    if (is_string($vorher) && $vorher !== '' && $vorher !== $pfad) {
                        Media::delete($vorher);
                    }
                } else {
                    // Kein neuer Upload heisst nicht "kein Bild". Beim
                    // Bearbeiten steht der Pfad des vorhandenen Bildes in der
                    // alten Wahl und muss stehen bleiben - sonst loeschte
                    // jedes Speichern, bei dem niemand eine Datei auswaehlt,
                    // das Foto. Beim Veroeffentlichen ist $alt leer, dort
                    // aendert dieser Zweig nichts.
                    $vorher = $altLayers[$id]['src'] ?? null;
                    if (is_string($vorher) && $vorher !== '') {
                        $eintrag['src'] = $vorher;
                    }
                }
            }

            if ($eintrag !== []) {
                $wahl['layers'][$id] = $eintrag;
            }
        }

        // Abschnitte: das Zu- und Abschalten geht ins Dokument, der Inhalt in
        // die Daten (siehe sammleAngaben). Dieselbe Trennung wie bei den Ebenen.
        foreach ($darf['sections'] as $sid => $abschnitt) {
            if ($abschnitt['hide'] && isset($_POST['sec_hidden_' . $sid])) {
                $wahl['sections'][$sid] = ['hidden' => true];
            }
        }

        return $wahl;
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

        $data = $this->sammleAngaben($darf);

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

        // Die Wahl einsammeln, mit dem Slug fuer den Bilderordner. Leeres
        // drittes Argument: beim Veroeffentlichen gibt es keine alte Wahl,
        // aus der ein Foto uebernommen werden koennte.
        $wahl = $this->sammleWahl($darf, $slug, [], $design);

        /*
         * Die Bilder der Galerie - erst JETZT, weil sie einen Ordner brauchen
         * und der Ordner die Adresse ist.
         *
         * sammleAngaben() kann das nicht: der Slug entsteht ein paar Zeilen
         * weiter oben AUS den Angaben (aus den Namen des Paares), existiert
         * dort also noch gar nicht. Erst die Namen, dann die Adresse, dann die
         * Bilder.
         */
        $data = $this->mitBildern($data, $darf, $slug, []);

        /*
         * Der Schnappschuss ist die Vorlage, NICHT das Ergebnis.
         *
         * Bis zu dieser Phase stand hier personalize($design, $wahl): das
         * Ergebnis fror ein, die Eingabe wurde weggeworfen. Damit war
         * nachtraegliches Bearbeiten unmoeglich - eine zweite Wahl haette auf
         * einem Sockel gelegen, in dem die erste schon eingebrannt war.
         *
         * Jetzt friert die Vorlage ein und die Wahl liegt daneben in
         * data['wahl']. Gedruckt wird personalize(snapshot, wahl), bei jedem
         * Aufruf neu (siehe show()). Das Versprechen aus Phase 3B haelt
         * trotzdem: der Sockel ist eine Kopie in der Zeile, und wer die Vorlage
         * im Panel spaeter aendert, aendert diese Karte nicht.
         *
         * Durch beide Normalisierer, damit in der Spalte genau die Form steht,
         * die css(), html() und die Abschnittsvorlage erwarten - dieselbe Form,
         * mit der personalize() ohnehin endet.
         */
        $snapshot = DesignSections::complete(Design::complete($design));

        $data['slug']      = $slug;
        $data['locale']    = I18n::locale();
        $data['paid']      = false;
        // Kein Bildschirm dafuer in dieser Phase - aber der Schluessel muss
        // von Anfang an dastehen. Nachtraeglich eingefuehrt sperrt er jede
        // bis dahin veroeffentlichte Einladung aus.
        $data['manageKey'] = bin2hex(random_bytes(16));
        $data['createdAt'] = date('c');
        // Was der Kunde gewaehlt hat, bleibt erhalten - sonst waere der Sockel
        // eine Vorlage, die niemand mehr auf die Karte des Paares abbilden
        // kann. Ihre Anwesenheit ist zugleich das Zeichen, dass der
        // Design-Tab beim Bearbeiten offen steht (Spec §4).
        $data['wahl']      = $wahl;
        // Der Stand fuer die Zwei-Tabs-Kontrolle. Er steht ab der ersten
        // Sekunde da, weil er sonst bei der ersten Bearbeitung fehlte und die
        // Kontrolle genau dann nicht griffe, wenn sie zum ersten Mal gebraucht
        // wird.
        $data['updatedAt'] = $data['createdAt'];

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
            $this->nichtGefunden();
            return;
        }

        /*
         * Ein Entwurf ist nicht oeffentlich - und sieht fuer den Gast aus wie
         * eine Adresse, die es nicht gibt.
         *
         * Absichtlich dieselbe Antwort wie bei einer unbekannten Adresse und
         * nicht "diese Einladung ist gerade abgeschaltet": wer den Link
         * bekommen hat, geht sonst davon aus, dass er es spaeter noch einmal
         * versuchen soll - und wer ihn NICHT bekommen sollte, erfaehrt aus der
         * Unterscheidung, dass es unter dieser Adresse etwas gibt.
         *
         * Der Zustand steht nicht im Sockel: er gehoert der Adresse, nicht dem
         * Aussehen. Ein Schnappschuss friert die Vorlage ein, damit sie sich
         * nicht mehr aendert - der Zustand soll sich aendern koennen.
         */
        if (!InvitationsV2::isPublic(InvitationsV2::status((string) ($params['slug'] ?? '')))) {
            $this->nichtGefunden();
            return;
        }

        // Vor der Antwort vollstaendig, nicht danach: saveReply() muss wissen,
        // ob die Einladung ueberhaupt einen sichtbaren rsvp-Abschnitt zeigt -
        // dafuer braucht sie das fertige Dokument, nicht den rohen Schnappschuss.
        /*
         * Die Wahl des Kunden auf den eingefrorenen Sockel legen - bei jedem
         * Aufruf neu.
         *
         * Bis zu dieser Phase stand hier Design::complete($snapshot), weil der
         * Schnappschuss das fertige Dokument war. Jetzt haelt er die Vorlage,
         * und die Wahl liegt in data['wahl'].
         *
         * Eine Einladung von VOR dieser Phase traegt kein wahl. Dann laeuft
         * personalize($sockel, []) - und das ist die Identitaet, gemessen und
         * nicht geglaubt (tests/invitations_v2_edit.php). Ihre Ausgabe bleibt
         * Byte fuer Byte dieselbe; deshalb gibt es zu dieser Aenderung keine
         * Wanderung und kein Datenumschreiben.
         *
         * is_array(): wahl aus einem von Hand veraenderten Dokument koennte
         * eine Zeichenkette sein, und personalize() erwartet ein Feld.
         */
        $wahl = is_array($einladung['data']['wahl'] ?? null) ? $einladung['data']['wahl'] : [];
        $doc  = DesignWizard::personalize($einladung['design_snapshot'], $wahl);

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
            // Sekunden im Dokument, Millisekunden auf der Buehne. Bei einer
            // verschickten Einladung kommt der Wert aus dem eingefrorenen
            // Sockel, nicht aus der lebenden Vorlage - sonst aenderte sich
            // der Auftakt einer laengst verteilten Adresse.
            'introMs' => (int) round(((float) ($doc['intro']['seconds'] ?? 0)) * 1000),
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

        // Der Schluessel steht seit Phase 3B in den Daten jeder Einladung. Die
        // Pruefung - 404 statt 403, hash_equals, der leere Schluessel zuerst,
        // die Bremse - steht in manageZugang(), weil sie der Bearbeiten-
        // Bildschirm Wort fuer Wort auch braucht.
        $einladung = $this->manageZugang($params);
        if ($einladung === null) {
            return;
        }

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

    /**
     * Eine veroeffentlichte Einladung nachtraeglich aendern.
     *
     * Der Bildschirm, den Spec §1 verlangt: heute muss ein Paar wegen eines
     * Buchstabens eine neue Einladung bauen und den Link erneut verschicken.
     *
     * Zwei Tabs, dieselben Felder wie im Assistenten - und ein Sockel, der
     * sich nicht bewegt. slug, manageKey, die Vorlage, createdAt, paid und die
     * Antworten der Gaeste stehen nicht auf diesem Formular (Spec §6).
     *
     * @param array<string,string> $params
     */
    public function edit(array $params): void
    {
        $locale = I18n::locale();

        $einladung = $this->manageZugang($params);
        if ($einladung === null) {
            return;
        }

        $slug = (string) $einladung['slug'];
        // Fuer die Adresse, auf die ein erfolgreiches Speichern umleitet
        // (Post/Redirect/Get) - manageZugang() hat den Schluessel schon
        // gegen die Zeile geprueft, hier wird er nur noch fuer den Pfad
        // gebraucht.
        $key  = (string) ($params['key'] ?? '');
        $data = $einladung['data'];

        /*
         * Das Formular wird auf dem EINGEFRORENEN Sockel gebaut, nicht auf dem
         * personalisierten Dokument.
         *
         * personalize() LOESCHT eine ausgeblendete Ebene (DesignWizard.php:298)
         * und schaltet einen ausgeblendeten Abschnitt auf enabled=false; und
         * choices() bietet weder eine geloeschte Ebene noch einen
         * abgeschalteten Abschnitt an (DesignWizard.php:117). Baute man das
         * Formular auf dem Ergebnis, waere jedes Ausblenden endgueltig - das
         * Haekchen zum Wiedereinblenden stuende gar nicht erst auf der Seite.
         */
        $sockel = $einladung['design_snapshot'];
        $darf   = DesignWizard::choices($sockel);

        $wahl = InvitationsV2::canEditDesign($data) ? (array) $data['wahl'] : [];

        // Nur zum Zeichnen der Vorschau - nie als Grundlage des Formulars.
        $doc = DesignWizard::personalize($sockel, $wahl);

        $error = '';
        $werte = $this->formularWerte($data);

        if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
            $ergebnis = $this->saveEdit($einladung, $darf);
            if (isset($ergebnis['error'])) {
                $error = (string) $ergebnis['error'];

                /*
                 * Bei einem Fehler bleibt stehen, was der Kunde getippt hat.
                 *
                 * Ohne das zeigte das Formular nach einem abgelaufenen Zeichen wieder den
                 * Stand aus der Datenbank - und die Meldung "bitte noch einmal absenden"
                 * waere eine Luege: das zweite Absenden speicherte die ALTEN Werte. Der
                 * Assistent macht es auf seinem Veroeffentlichungsweg genauso.
                 *
                 * veraltet steht bewusst nicht in dieser Liste: dort hat jemand anders
                 * gespeichert, und dann ist der frische Stand aus der Zeile genau das,
                 * was das Paar sehen muss, bevor es entscheidet.
                 */
                if (in_array($error, ['csrf', 'throttle', 'names'], true)) {
                    $werte = array_merge($werte, InvitationsV2::draftValues($_POST));
                }
            } else {
                /*
                 * Post/Redirect/Get: nur auf dem Erfolgsweg.
                 *
                 * Ohne die Umleitung traegt ein F5 auf dieser Seite denselben
                 * POST-Rumpf noch einmal zum Server - mit dem "stand" von vorher,
                 * der jetzt einen Schritt hinter der Zeile liegt. Die Zwei-Tabs-
                 * Kontrolle (stale()) haelt das faelschlich fuer eine fremde
                 * Aenderung, obwohl niemand sonst gespeichert hat. Der
                 * Fehlerzweig oben bleibt bewusst ein erneutes Rendern und keine
                 * Umleitung: er traegt InvitationsV2::draftValues($_POST) und
                 * eine Umleitung wuerfe genau das wieder weg.
                 */
                $ziel = I18n::path('/v2/einladung/' . $slug . '/' . $key . '/bearbeiten');
                header('Location: ' . $ziel . '?ok=gespeichert', true, 303);
                exit;
            }
        }

        // Nach der Umleitung steht der Erfolg in der Adresse, nicht mehr in
        // einer lokalen Variable - genau dafuer gibt es Post/Redirect/Get.
        $okParam = $_GET['ok'] ?? null;
        $ok      = is_string($okParam) && $okParam === 'gespeichert';

        $scope  = '.d-' . $doc['id'];
        $values = Design::bindValues($data, $locale);
        $namen  = trim(((string) ($data['bride'] ?? '')) . ' & ' . ((string) ($data['groom'] ?? '')), ' &');

        View::page('pages/invite-v2-edit', [
            'locale' => $locale,
            // Ohne $path meldet layout.php eine undefinierte Variable im
            // Sprachumschalter. Der Schluessel gehoert NICHT hinein: der
            // Umschalter schriebe ihn sonst in eine sichtbare Adresse.
            'path'   => I18n::path('/v2/einladung'),
            'meta'   => Seo::forPage('einladung2', [
                'title'       => $namen !== '' ? $namen : I18n::t('invitation2.editTitle'),
                // Diese Seite IST der Schluessel. Sie gehoert unter keinen
                // Umstaenden in einen Index.
                'noindex'     => true,
                // Dasselbe Skript wie der Assistent, unveraendert: es blendet
                // [data-step] ein und aus und spiegelt [data-live] in die
                // Karte. Es entscheidet nichts.
                'scripts'     => ['/assets/invite-v2.js'],
            ]),
            // Der eingefrorene Sockel, vollstaendig: die Vorlage liest daraus
            // die Ausgangsfarbe einer Ebene.
            'design'     => Design::complete($sockel),
            'choices'    => $darf,
            'filme'      => DesignVideos::all(),
            'values'     => $werte,
            'wahl'       => $wahl,
            'darfDesign' => InvitationsV2::canEditDesign($data),
            // Fuer die Bilder, die schon abgelegt sind: sie stehen als Liste
            // in den Daten und nicht in $values - dort landet nur, was sich
            // als Zeichenkette in ein Formularfeld zuruecklegen laesst.
            'daten'      => $data,
            'gastPfad'   => I18n::path('/v2/einladung/' . $slug),
            'stand'      => is_string($data['updatedAt'] ?? null) ? $data['updatedAt'] : '',
            'scope'      => ltrim($scope, '.'),
            'styles'     => Design::css($doc, $scope),
            'sectionCss' => DesignSections::css($doc, $scope),
            'karte'      => Design::html($doc, $values, $locale, 'card'),
            'abschnitte' => DesignSections::html($doc, $data, $locale, '', ['csrf' => '', 'sent' => false]),
            'csrf'       => Security::csrf(),
            'error'      => $error,
            'ok'         => $ok,
        ]);
    }

    /**
     * Die Aenderung schreiben.
     *
     * Die Reihenfolge ist die Sicherung, nicht eine Geschmacksfrage:
     *
     *   1. CSRF - vor allem anderen, wie auf jedem Schreibweg dieser Datei.
     *   2. Bremse - ein eigener Eimer fuers Schreiben, damit ein Skript nicht
     *      ueber diesen Weg das Kontingent des Leseschirms aufbraucht.
     *   3. Stand - hat jemand in einem anderen Tab gespeichert (Spec §7)?
     *   4. Namen - dieselbe Mindestbedingung wie beim Veroeffentlichen: eine
     *      Karte ohne jeden Namen gehoert niemandem.
     *   5. Schreiben.
     *
     * Was NICHT geschrieben wird: design_snapshot (die Vorlage friert ein,
     * Phase 3B), slug (die Adresse ist verschickt), manageKey (die eigene Tuer
     * des Paares), createdAt und paid (Buchhaltung, nicht Kundenfeld) - und
     * die Antworten der Gaeste. In dieser Methode steht kein einziger Zugriff
     * auf rsvps, und das ist Absicht (Spec §8).
     *
     * Ein hingenommener Verlust bei einer Einladung von VOR dieser Phase: dort
     * ist der eingefrorene Sockel schon personalisiert, ein beim
     * Veroeffentlichen ausgeblendeter Abschnitt traegt also enabled=false.
     * DesignWizard::choices() bietet einen abgeschalteten Abschnitt gar nicht
     * erst an, sammleAngaben() liest also nie wieder dessen Text, und das
     * unset() oben loescht ihn beim ersten Speichern endgueltig. Kein Gast
     * sieht dadurch etwas anderes - der Abschnitt war schon verborgen -, und
     * diese Zeilen haben ohnehin keinen Design-Tab, in dem sich das
     * rueckgaengig machen liesse. Aber der Text ist danach weg.
     *
     * @param array{slug:string,design_id:string,design_snapshot:array<string,mixed>,data:array<string,mixed>,created_at:string} $einladung
     * @param array<string,mixed> $darf choices() auf dem EINGEFRORENEN Sockel
     * @return array<string,string> leer, wenn geschrieben wurde
     */
    private function saveEdit(array $einladung, array $darf): array
    {
        // is_string() faengt csrf[]=x ab: ohne die Pruefung reicht ein Feld, um
        // Security::checkCsrf() unter strict_types einen TypeError werfen zu
        // lassen - derselbe Fehler wie schon auf dem Antwortweg.
        $csrfEingabe = $_POST['csrf'] ?? null;
        if (!Security::checkCsrf(is_string($csrfEingabe) ? $csrfEingabe : null)) {
            return ['error' => 'csrf'];
        }

        $slug = (string) $einladung['slug'];
        $alt  = $einladung['data'];

        // Eigener Eimer neben v2-manage-{slug}: das Lesen der Antworten und das
        // Schreiben an der Einladung sollen einander nicht aussperren.
        if (Security::throttle('v2-edit-' . $slug, 20, 900)) {
            return ['error' => 'throttle'];
        }

        // Zwei Tabs. Der zweite Speichervorgang ueberschriebe sonst den ersten,
        // ohne dass jemand es merkt (Spec §7).
        $gesehen = Security::clean($_POST['stand'] ?? '', 40);
        if (InvitationsV2::stale($alt, $gesehen)) {
            return ['error' => 'veraltet'];
        }

        $neueAngaben = $this->sammleAngaben($darf);

        // Gefragt und leer gelassen ist erlaubt - html() laesst die Zeile dann
        // einfach weg. Nur ohne jeden Namen weiss niemand, wessen Karte das
        // ist. Dieselbe Regel wie in publish().
        $brauchtNamen = in_array('bride', $darf['fields'], true) || in_array('groom', $darf['fields'], true);
        if ($brauchtNamen && ($neueAngaben['bride'] ?? '') === '' && ($neueAngaben['groom'] ?? '') === '') {
            return ['error' => 'names'];
        }

        /*
         * Die Inhaltsschluessel werden ZUERST weggenommen und dann neu gelegt.
         *
         * Ohne das Wegnehmen waere ein geleertes Feld kein Loeschbefehl:
         * sammleAngaben() setzt families, program und sections nur, wenn etwas
         * drinsteht, und ein blosses Ueberlegen liesse den alten Wert stehen.
         * Wer eine Programmzeile loescht, will sie geloescht haben.
         *
         * Alles, was NICHT in dieser Liste steht, bleibt unangetastet - slug,
         * locale, paid, manageKey und createdAt reisen so durch, ohne dass
         * dieser Weg sie kennen muss.
         */
        /*
         * Die Bilder VOR dem Wegnehmen der Inhaltsschluessel: mitBildern()
         * liest aus $alt, was schon da liegt, und behaelt es. Danach ist
         * $alt['sections'] fuer diesen Weg zwar weg, aber die Liste steht
         * dann bereits in $neueAngaben.
         */
        $neueAngaben = $this->mitBildern($neueAngaben, $darf, $slug, $alt);

        $neu = $alt;
        unset($neu['families'], $neu['program'], $neu['sections']);
        foreach ($darf['fields'] as $feld) {
            unset($neu[$feld]);
        }

        $neu = array_merge($neu, $neueAngaben);

        /*
         * Das Design nur, wenn es ueberhaupt offen steht.
         *
         * Serverseitig und nicht nur im Markup: der Design-Tab fehlt bei einer
         * alten Einladung auf dem Bildschirm, aber eine von Hand gestellte
         * Anfrage traegt palette_* trotzdem. Wuerde sie hier angenommen, legte
         * sie eine Wahl auf einen Sockel, in dem eine erste Wahl schon
         * eingebrannt ist - genau der verlustbehaftete Fall aus Spec §4.
         *
         * $alt['wahl'] als drittes Argument: ein Foto, das diesmal nicht neu
         * hochgeladen wurde, behaelt seinen Pfad (sammleWahl).
         */
        if (InvitationsV2::canEditDesign($alt)) {
            $neu['wahl'] = $this->sammleWahl($darf, $slug, (array) $alt['wahl'], $einladung['design_snapshot']);
        }

        // Der neue Stand fuer die naechste Zwei-Tabs-Kontrolle. Zuletzt, damit
        // er den Zustand nach dieser Aenderung beschreibt und nicht den davor.
        $neu['updatedAt'] = date('c');

        // Ausdruecklich noch einmal: was in dieser Zeile unberuehrbar ist (Spec
        // §6). Heute kann keiner der Namen aus sammleAngaben() mit ihnen
        // kollidieren - FIELD_ORDER enthaelt keinen davon. "Heute kann das
        // nicht passieren" ist aber der Satz, nach dem in Phase 3C drei Fehler
        // gefunden wurden, und diese fuenf Zeilen kosten nichts.
        $neu['slug']      = $alt['slug']      ?? $slug;
        $neu['manageKey'] = $alt['manageKey'] ?? '';
        $neu['createdAt'] = $alt['createdAt'] ?? $neu['updatedAt'];
        $neu['paid']      = $alt['paid']      ?? false;
        $neu['locale']    = $alt['locale']    ?? I18n::locale();

        InvitationsV2::saveData($slug, $neu);

        return [];
    }

    /**
     * Die 404-Seite dieses Controllers.
     *
     * Sie stand bis hierher dreimal wortgleich in dieser Datei. Der Grund fuer
     * jede der drei Zeilen ist derselbe geblieben: pages/not-found liest
     * $locale unbedingt (not-found.php:10) und layout.php braucht $path -
     * fehlen sie, meldet PHP undefinierte Variablen und die Seite kommt auf
     * Englisch heraus, egal in welcher Sprache sie aufgerufen wurde.
     */
    private function nichtGefunden(): void
    {
        http_response_code(404);
        View::page('pages/not-found', [
            'locale' => I18n::locale(),
            'path'   => I18n::path('/v2/einladung'),
            'meta'   => Seo::forPage('einladung2', ['noindex' => true]),
        ]);
    }

    /**
     * Die Tuer, die manageKey oeffnet.
     *
     * Seit dieser Phase oeffnet derselbe Schluessel mehr als eine Seite: die
     * Antworten lesen UND die Einladung bearbeiten. Deshalb steht die Pruefung
     * einmal hier statt in jedem Bildschirm noch einmal - zwei Kopien
     * derselben Sicherung altern verschieden schnell.
     *
     * 404 und nicht 403: ein 403 bestaetigt, dass es diese Einladung gibt, und
     * wer den Schluessel nicht hat, soll auch das nicht erfahren.
     *
     * Die Bremse ist neu und sie ist der Preis dafuer, dass der Schluessel
     * jetzt Schreibrechte vergibt. Auf dem reinen Leseschirm war "128 Bit sind
     * nicht zu erraten" ein vertretbares Argument; sobald damit ein fremdes
     * Dokument geaendert werden kann, ist es keines mehr (Spec §5). Sie steht
     * VOR dem Vergleich, sonst braemste sie nur die Berechtigten - ein
     * falscher Schluessel faellt danach ohnehin ins 404.
     *
     * Eine ausgeloeste Bremse antwortet ebenfalls mit 404 und nicht mit einer
     * eigenen Meldung: jede unterscheidbare Antwort waere ein Orakel, an dem
     * sich ablesen liesse, dass es diese Einladung gibt. Der Preis ist, dass
     * ein Paar, das sechzigmal in zehn Minuten neu laedt, eine Fehlseite
     * sieht - bei diesem Mass ein unwahrscheinlicher Fall.
     *
     * @param array<string,string> $params
     * @return array{slug:string,design_id:string,design_snapshot:array<string,mixed>,data:array<string,mixed>,created_at:string}|null
     */
    private function manageZugang(array $params): ?array
    {
        // Normalisiert, damit "Foo" und "foo" denselben Eimer benutzen - sonst
        // waere die Bremse mit einer anderen Schreibweise zu umgehen.
        $slug = InvitationsV2::slug((string) ($params['slug'] ?? ''));

        if ($slug === '' || Security::throttle('v2-manage-' . $slug, 60, 600)) {
            $this->nichtGefunden();
            return null;
        }

        $einladung = InvitationsV2::find($slug);

        if ($einladung === null || !InvitationsV2::keyOk($einladung['data'], (string) ($params['key'] ?? ''))) {
            $this->nichtGefunden();
            return null;
        }

        // Diese Seiten sind eine geheime Adresse mit den Daten eines Paares
        // darauf - sie duerfen in keinem geteilten Cache landen. show()
        // bekommt no-store geschenkt, weil Security::csrf() dort eine Sitzung
        // startet; hier muss der Hinweis von Hand hinaus.
        header('Cache-Control: private, no-store');

        return $einladung;
    }
}
