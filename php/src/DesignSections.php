<?php

declare(strict_types=1);

namespace Atelier;

/**
 * Was unter der Karte steht.
 *
 * Die Karte ist ein fester Rahmen: jede Ebene sitzt auf Prozentkoordinaten.
 * Ein Programm mit drei Zeilen und eines mit zwoelf passen aber nicht in
 * denselben Kasten - deshalb sind Abschnitte kein vierter Ort, sondern ein
 * fliessendes Dokument unter der Buehne.
 *
 * Sie gehoeren dem Dokument, nicht der Einladung: der Grafiker stellt sie auf
 * und bestimmt Reihenfolge, Farbe und Schrift; der Kunde darf hoechstens
 * zu- und abschalten, was freigegeben ist. Die Reihenfolge im Feld ist die
 * Reihenfolge auf der Seite - genau wie bei den Ebenen der z-Index.
 *
 * Der Katalog ist fest, und das ist Absicht: ein Countdown muss ticken, ein
 * Kartenlink eine Adresse kodieren. Abschnitte aufstellen, faerben, an- und
 * abschalten ist Daten; eine neue Art Abschnitt ist Code.
 *
 * Vier Arten zeigen etwas an, die fuenfte fragt: rsvp ist der einzige
 * Abschnitt, der ein Formular druckt. Er sitzt trotzdem hier und nicht
 * daneben - dieselbe Form, dieselben Rechte, dieselbe Reihenfolge. Was ihn
 * unterscheidet, ist nicht seine Gestalt, sondern was auf der anderen Seite
 * des Absendens passiert, und das steht im Controller.
 *
 * Alles hier ist rein - keine Datenbank, keine Sitzung, kein $_POST, und kein
 * Blick auf die Uhr: das Bezugsdatum kommt als Parameter herein.
 */
final class DesignSections
{
    /** Welche Arten es gibt. Alles andere faellt beim Einlesen weg. */
    public const TYPES = [
        'location', 'countdown', 'family', 'program', 'rsvp', 'text',
        // Angehaengt und nicht eingeschoben: die Reihenfolge steht in Tests,
        // und ein Einschub verschoebe alles dahinter.
        'footer', 'gift', 'music', 'gallery',
        // Die elfte Art kam mit den Zeichen: eine Karte, die nur zeigt,
        // was das Paar wirklich serviert.
        'menu',
        // Und die zwoelfte: was man anzieht.
        'dresscode',
    ];

    /**
     * Wie das eigene Blatt eines Abschnitts sitzt.
     *
     * "blatt" ist dieselbe Rechnung wie beim grossen Blatt: Breite an der
     * Karte, oben ausgerichtet, nach unten wiederholt. Damit steht es auf
     * dem Telefon im selben Massstab wie am Schreibtisch - der Grund steht
     * ausfuehrlich in baseline().
     *
     * "cover" fuellt den Abschnitt und schneidet dafuer die Raender ab.
     */
    public const FITS = ['blatt', 'cover'];

    /** Wie viele Programmzeilen, und wie lang eine sein darf. */
    public const PROGRAM_MAX = 20;
    public const PROGRAM_LEN = 80;

    /** Und wie lang der Satz darunter sein darf. */
    public const PROGRAM_TEXT_LEN = 200;

    /**
     * Vollstaendige Abschnitte, in der Reihenfolge des Feldes.
     *
     * @param array<string,mixed> $doc
     * @return array<string,mixed>
     */
    public static function complete(array $doc): array
    {
        $out = [];

        foreach ((array) ($doc['sections'] ?? []) as $eintrag) {
            if (!is_array($eintrag)) {
                continue;
            }

            $type = (string) ($eintrag['type'] ?? '');
            if (!in_array($type, self::TYPES, true)) {
                // Unbekannt faellt still. Ein Dokument soll sich nicht wegen
                // eines Werts aus dem Panel nicht mehr oeffnen lassen.
                continue;
            }

            // Ohne Kennung waere der Abschnitt im Stilblock nicht adressierbar.
            $id = Design::key((string) ($eintrag['id'] ?? ''));
            if ($id === '') {
                continue;
            }

            $title = is_array($eintrag['title'] ?? null) ? $eintrag['title'] : [];
            $style = is_array($eintrag['style'] ?? null) ? $eintrag['style'] : [];
            $recht = is_array($eintrag['permissions'] ?? null) ? $eintrag['permissions'] : [];

            $out[] = [
                'id'      => $id,
                'type'    => $type,
                /*
                 * Die Variante: dieselbe Art, ein anderes Aussehen. Sie steht
                 * neben dem Typ und nicht darin - "program-zeitstrahl" als
                 * eigene Art haette jeden match()-Block um einen Zweig
                 * verlaengert, und der Assistent haette zwei Namen fuer
                 * denselben Inhalt lernen muessen.
                 *
                 * Eine unbekannte Variante faellt still auf die Voreinstellung
                 * zurueck, genau wie ein unbekannter Typ wegfaellt: ein
                 * Dokument soll sich nicht wegen eines Werts aus dem Panel
                 * nicht mehr oeffnen lassen.
                 */
                'variant' => SectionRegistry::isVariant($type, (string) ($eintrag['variant'] ?? ''))
                    ? (string) $eintrag['variant']
                    : SectionRegistry::defaultVariant($type),
                // Was der Grafiker dreht, ohne dass es eine Variante wird.
                // Der Katalog prueft: Fremdes faellt weg, Danebenliegendes
                // faellt zurueck.
                /*
                 * Was der Grafiker schon hineingeschrieben hat.
                 *
                 * Der Titel gehoert der Vorlage, der Text dem Paar - das war
                 * die Trennung, und sie war zu scharf: eine Vorlage soll
                 * sagen duerfen, was in einem Abschnitt STEHEN KOENNTE.
                 * Sonst baut der Grafiker eine Ueberschrift ueber nichts und
                 * sieht im Schaufenster einen Platzhalter, den er nicht
                 * aendern kann.
                 *
                 * Es bleibt eine Voreinstellung: schreibt das Paar etwas,
                 * gewinnt das Paar. Ein Wert, den man nicht ueberschreiben
                 * kann, waere ein fester Text und keine Voreinstellung.
                 *
                 * Nur Schluessel, die der Katalog fuer diese Art kennt -
                 * derselbe Filter wie bei den Einstellungen.
                 */
                'defaults' => self::completeDefaults($type, $eintrag['defaults'] ?? null),
                'settings' => SectionRegistry::completeSettings(
                    $type,
                    is_array($eintrag['settings'] ?? null) ? $eintrag['settings'] : []
                ),
                'title'   => [
                    'de' => (string) ($title['de'] ?? ''),
                    'en' => (string) ($title['en'] ?? ''),
                ],
                'enabled' => (bool) ($eintrag['enabled'] ?? true),
                'style'   => [
                    // Markennamen, keine Werte: der Renderer schreibt
                    // var(--d-<name>). Ein roher Wert ergaebe ungueltiges CSS.
                    'color' => Design::key((string) ($style['color'] ?? '')),
                    'font'  => Design::key((string) ($style['font'] ?? '')),
                    // Das eigene Blatt. Derselbe Filter wie ueberall, wo ein
                    // Pfad aus dem Panel kommt - er laesst nur zu, was wir
                    // selbst vergeben haben.
                    'bg'    => Design::safeSrc((string) ($style['bg'] ?? '')),
                    'bgFit' => in_array((string) ($style['bgFit'] ?? ''), self::FITS, true)
                        ? (string) $style['bgFit']
                        : 'blatt',
                ],
                'permissions' => [
                    // edit ist der Hauptschalter, wie bei den Ebenen: ohne ihn
                    // zaehlt hide nicht.
                    'edit' => (bool) ($recht['edit'] ?? false),
                    'hide' => (bool) ($recht['hide'] ?? false),
                ],
            ];
        }

        $doc['sections'] = $out;

        return $doc;
    }

    /**
     * Eine leere Zeile in der Gestalt einer echten.
     *
     * Das Panel haengt unter die Abschnitte eine Zeile fuer "neu". Sie wurde
     * dort von Hand gebaut, und als der Stil um das Blatt wuchs, wuchs sie
     * nicht mit: die Tafel las einen Schluessel, den es an dieser einen Zeile
     * nicht gab, und die Warnung landete mitten im Formular.
     *
     * Hier entsteht sie aus complete(), also aus derselben Quelle wie jede
     * echte. Was dazukommt, kommt an beiden Stellen zugleich an.
     *
     * Art und Kennung werden danach geleert: complete() laesst keine Zeile
     * ohne beides durch, aber genau das soll die neue Zeile sein - leer, bis
     * jemand sie ausfuellt.
     *
     * @return array<string,mixed>
     */
    public static function leer(): array
    {
        $satz = self::complete(['sections' => [['id' => 'neu', 'type' => self::TYPES[0]]]]);

        // Ueberschrieben und nicht davorgesetzt: die Reihenfolge der
        // Schluessel bleibt damit dieselbe wie an einem echten Abschnitt.
        $zeile = $satz['sections'][0];
        $zeile['id'] = '';
        $zeile['type'] = '';
        $zeile['enabled'] = false;

        return $zeile;
    }

    /**
     * Die Zeilen des Programms, sauber.
     *
     * Ohne Titel keine Zeile: eine Uhrzeit allein sagt nichts. Die Obergrenze
     * schneidet ab, statt die Eingabe abzulehnen - eine Einladung soll nicht
     * an einer zu langen Liste scheitern.
     *
     * @param array<string,mixed> $data
     * @return list<array{time:string,title:string}>
     */
    public static function programRows(array $data): array
    {
        $out = [];

        foreach ((array) ($data['program'] ?? []) as $zeile) {
            if (!is_array($zeile)) {
                continue;
            }
            $titel = trim((string) ($zeile['title'] ?? ''));

            /*
             * Das Zeichen kommt aus dem Katalog, nicht aus den Daten: im
             * Dokument steht eine Kennung, und was es nicht gibt, faellt still
             * weg. So gelangt aus einer Einladung nie ein Pfad in die Seite.
             */
            $zeichen = (string) ($zeile['icon'] ?? '');
            if (SectionRegistry::iconFile($zeichen) === '') {
                $zeichen = '';
            }

            // Frueher hiess "ohne Titel" ohne Zeile. Jetzt darf eine Zeile auch
            // allein vom Zeichen leben - dann steht der Vorschlag des Katalogs
            // da. Ohne beides bleibt sie weg wie bisher.
            if ($titel === '' && $zeichen === '') {
                continue;
            }

            $out[] = [
                'time'  => mb_substr(trim((string) ($zeile['time'] ?? '')), 0, self::PROGRAM_LEN),
                'title' => mb_substr($titel, 0, self::PROGRAM_LEN),
                'icon'  => $zeichen,
                // Der Satz unter der Ueberschrift. Geschnitten und nicht
                // abgelehnt, wie der Titel: eine Einladung soll nicht an einer
                // zu langen Zeile scheitern.
                'text'  => mb_substr(trim((string) ($zeile['text'] ?? '')), 0, self::PROGRAM_TEXT_LEN),
            ];
            if (count($out) >= self::PROGRAM_MAX) {
                break;
            }
        }

        return $out;
    }

    /**
     * Welche Abschnitte wirklich gedruckt werden.
     *
     * Getrennt von html(), weil zwei Stellen dieselbe Frage stellen: der
     * Renderer, um zu drucken, und der Assistent, um zu wissen, ob er nach
     * Inhalt fragen muss.
     *
     * $heute kommt herein statt aus date() - sonst haengt ein Test an der Uhr.
     *
     * @param array<string,mixed> $doc
     * @param array<string,mixed> $data
     * @return list<array<string,mixed>>
     */
    public static function visible(array $doc, array $data, string $heute = ''): array
    {
        $doc = self::complete($doc);
        $heute = $heute !== '' ? $heute : date('Y-m-d');

        $out = [];
        foreach ($doc['sections'] as $abschnitt) {
            if (!$abschnitt['enabled']) {
                continue;
            }
            if (!self::hatInhalt($abschnitt, $data, $heute)) {
                continue;
            }
            $out[] = $abschnitt;
        }

        return $out;
    }

    /**
     * Hat dieser Abschnitt etwas zu zeigen?
     *
     * @param array<string,mixed> $abschnitt
     * @param array<string,mixed> $data
     */
    private static function hatInhalt(array $abschnitt, array $data, string $heute): bool
    {
        $familien = is_array($data['families'] ?? null) ? $data['families'] : [];
        $datum = trim((string) ($data['date'] ?? ''));

        return match ((string) $abschnitt['type']) {
            // Ohne Adresse haette der Kartenlink kein Ziel.
            'location'  => trim((string) ($data['address'] ?? '')) !== '',
            // Ein vergangener Termin bekommt keinen Countdown; der Tag selbst
            // zaehlt noch, es wird ja bis zum Morgen gefeiert.
            'countdown' => $datum !== '' && $datum >= $heute,
            'family'    => trim((string) ($familien['bride'] ?? '')) !== ''
                        || trim((string) ($familien['groom'] ?? '')) !== '',
            'program'   => self::programRows($data) !== [],
            // Dieselbe Regel wie beim Countdown, und ausdruecklich dieselbe:
            // eine gefeierte Hochzeit sammelt keine Antworten mehr. Ohne
            // Datum wird trotzdem gedruckt - dort ist der Countdown stumm,
            // weil er nichts zu zaehlen haette, aber die Frage "kommt ihr?"
            // steht auch ohne Termin.
            'rsvp'      => $datum === '' || $datum >= $heute,
            // Ohne Text keine Ueberschrift. Der Inhalt haengt an der Kennung,
            // nicht an einem festen Namen: ein Dokument kann mehrere
            // Textbloecke tragen, und zwei feste Namen waeren einer.
            'text'      => trim(self::inhalt($abschnitt, $data, 'text')) !== '',
            // Der Schluss traegt zwei Dinge und braucht nur eines davon.
            'footer'    => trim(self::inhalt($abschnitt, $data, 'text')) !== ''
                        || trim(self::inhalt($abschnitt, $data, 'hashtag')) !== '',
            // Ohne Kontonummer bleibt der Wunsch, und der ist auch etwas.
            'gift'      => trim(self::inhalt($abschnitt, $data, 'text')) !== ''
                        || trim(self::inhalt($abschnitt, $data, 'iban')) !== '',
            // Die Tonspur gehoert der Vorlage, nicht dem Paar: ohne sie hat
            // dieser Abschnitt nichts zu spielen und wird nicht gedruckt.
            'music'     => trim((string) ($abschnitt['settings']['track'] ?? '')) !== '',
            // Kein Bild, kein Abschnitt. Eine leere Galerie ist eine
            // Ueberschrift ueber nichts.
            'gallery'   => self::sectionPhotos($data, (string) $abschnitt['id']) !== [],
            // Ein einziger Gang genuegt. Keiner heisst: eine Ueberschrift
            // ueber einer leeren Karte.
            'menu'      => self::speisekarteGefuellt($abschnitt, $data),
            // Ansage oder Hinweis - eines genuegt.
            'dresscode' => trim(self::inhalt($abschnitt, $data, 'code')) !== ''
                        || trim(self::inhalt($abschnitt, $data, 'note')) !== '',
            default     => false,
        };
    }

    /**
     * Stilregeln der Abschnitte.
     *
     * Wie bei den Ebenen: alles unter dem Bereich, damit zwei Designs auf
     * derselben Seite stehen koennen, ohne sich umzufaerben.
     *
     * @param array<string,mixed> $doc
     */
    public static function css(array $doc, string $scope): string
    {
        $doc = self::complete($doc);
        $css = '';

        if ($doc['sections'] !== []) {
            $css .= self::baseline($scope);
        }

        foreach ($doc['sections'] as $abschnitt) {
            $regeln = '';
            $farbe  = (string) $abschnitt['style']['color'];
            $schrift = (string) $abschnitt['style']['font'];

            if ($farbe !== '') {
                $regeln .= 'color:var(--d-' . $farbe . ');';
            }
            if ($schrift !== '') {
                $regeln .= 'font-family:var(--df-' . $schrift . ');'
                    . 'font-weight:var(--dfw-' . $schrift . ');'
                    . 'letter-spacing:var(--dft-' . $schrift . ');'
                    . 'line-height:var(--dfl-' . $schrift . ');';
            }
            /*
             * Nur schreiben, was abweicht. Die Grundregel sagt bereits
             * center und ein gerechnetes Polster; eine zweite center-Zeile
             * je Abschnitt waere Rauschen im Stilblock, und der steht inline
             * in JEDER Seite - nicht in einer Datei, die der Browser einmal
             * holt und behaelt.
             */
            /*
             * Das eigene Blatt des Abschnitts.
             *
             * Es liegt UEBER dem grossen Blatt: .d-sec-<id> steht innerhalb
             * von .d-sec-flaeche. Ein Abschnitt mit eigenem Blatt zeigt
             * seines, alle anderen tragen weiter das des Bereichs - dafuer
             * braucht es keinen zweiten Schalter.
             *
             * Der Pfad ist durch safeSrc() gegangen und traegt weder
             * Anfuehrungszeichen noch Klammern; er kann aus dem url() nicht
             * ausbrechen.
             */
            $blatt = (string) ($abschnitt['style']['bg'] ?? '');
            if ($blatt !== '') {
                $regeln .= "background-image:url('" . $blatt . "');";
                $regeln .= (string) ($abschnitt['style']['bgFit'] ?? 'blatt') === 'cover'
                    ? 'background-size:cover;background-position:center;background-repeat:no-repeat;'
                    : 'background-size:min(100%,42rem) auto;background-position:top center;'
                      . 'background-repeat:repeat-y;';
            }

            $einstellung = is_array($abschnitt['settings'] ?? null) ? $abschnitt['settings'] : [];

            $aus = (string) ($einstellung['align'] ?? 'center');
            if ($aus !== 'center') {
                $regeln .= 'text-align:' . $aus . ';';
            }

            // Die Luft UNTEN. Oben sitzt das gerechnete Polster, mit dem der
            // Titel zwischen die Goldlinien des Blattes faellt - daran darf
            // ein Knopf im Panel nicht drehen.
            $luft = (string) ($einstellung['space'] ?? 'normal');
            if ($luft !== 'normal') {
                $regeln .= 'padding-bottom:' . ($luft === 'eng' ? '6' : '22') . '%;';
            }

            if ($regeln !== '') {
                $css .= $scope . ' .d-sec-' . $abschnitt['id'] . '{' . $regeln . '}';
            }
        }

        /*
         * Die Variantenbloecke, und nur die benutzten. Tote Regeln in jedem
         * Dokument mitzuschleppen waere Ballast; und weil zwei Abschnitte
         * dieselbe Variante tragen koennen, sammelt der Schluessel sie zu
         * einer.
         */
        $varianten = [];
        foreach ($doc['sections'] as $abschnitt) {
            // Art UND Gestalt als Schluessel: "gross" heisst beim Ort etwas
            // anderes als beim Countdown, und ohne die Art im Schluessel
            // faerbte der eine Block den anderen um.
            $varianten[$abschnitt['type'] . '/' . $abschnitt['variant']] = true;
        }
        foreach (array_keys($varianten) as $paar) {
            [$art, $variante] = explode('/', $paar, 2);
            $css .= self::variantCss($art, $variante, $scope);
        }

        return $css;
    }

    /**
     * Grundstil, einmal, bevor die abschnittsweisen Regeln kommen.
     *
     * Tailwinds Preflight setzt h1..h6 auf geerbte Groesse und Gewicht
     * zurueck und p auf margin:0 - ohne diesen Block sind Abschnitte eine
     * ununterschiedene Textwand unter einer typografierten Karte. Bewusst
     * ohne Farbe: die kommt aus den Marken des Designs, nicht von hier.
     * Jeder Selektor haengt am $scope - zwei Designs auf einer Seite duerfen
     * sich sonst gegenseitig umfaerben (siehe Design::css()).
     *
     * .d-sec-days bekommt hier den Abstand zum gedruckten Datum, nicht als
     * Leerzeichen in der Verkettung in countdown(): ein Layout-Zwischenraum
     * ist eine Stilfrage und gehoert ins Stylesheet - ein Leerzeichen mitten
     * in einer PHP-Verkettung waere fuer den naechsten Leser unsichtbar und
     * liesse sich unbemerkt wieder loeschen.
     *
     * Das Formular braucht denselben Dienst wie die uebrigen Abschnitte, nur
     * dringender: Preflight nimmt input und button jede Kontur, und ein
     * Eingabefeld ohne Unterkante ist auf einer typografierten Einladung
     * unsichtbar. currentColor statt einer Marke - so nimmt das Formular die
     * Farbe des Abschnitts an, die der Grafiker gesetzt hat, statt eine
     * zweite Quelle dafuer aufzumachen.
     */
    private static function baseline(string $scope): string
    {
        /*
         * Die Abschnitte tragen die Welt der Karte weiter.
         *
         * Bis hierher stand hier nur Struktur - Abstaende, Raster, Formfelder -
         * und Farbe wie Schrift kamen von der Seite. Auf einer cremefarbenen
         * Seite unter einer Karte aus schwarzem Papier hiess das: oben die
         * Einladung, unten ein nacktes Formular. Der Kunde hat genau das
         * gesehen: "alt tarafa dogru kaydirinca ayni kartin devami gibi
         * olacak."
         *
         * Die Marken stehen ohnehin schon am Bereich - Design::css() schreibt
         * --d-paper, --d-fg, --d-accent und die Schriftvariablen dorthin.
         * Hier werden sie nur gelesen. Der Ersatzwert dahinter ist keine
         * Vorsicht ohne Grund: eine Vorlage ohne Papiermarke gibt es (Noir
         * hat keine), und ohne ihn stuende die Flaeche dann auf transparent.
         */
        /*
         * background-COLOR, nicht die Kurzform: die Vorlage haengt der
         * Flaeche ein Bild an (das Blatt der Karte), und die Kurzform wuerde
         * es jedes Mal wieder wegwischen.
         *
         * Die Groesse ist an die KARTE gebunden, nicht an das Fenster.
         *
         * Zuerst stand hier cover mit background-attachment:fixed. Beides
         * zusammen misst gegen den Bildschirm: die Karte ist 672 px breit,
         * das Fenster 1280 - dasselbe Blatt kam unten fast doppelt so gross
         * heraus wie oben. Der Kunde hat es sofort gesehen: "ilk boyle olup
         * sonra niye buyuyor arkaplan".
         *
         * min(100%, 42rem) ist genau die Breite der Karte (max-w-2xl). Damit
         * steht das Blatt unten im selben Massstab wie oben, und auf einem
         * schmalen Telefon nimmt es die volle Breite.
         *
         * Oben ausgerichtet und ohne Wiederholung: der Schmuck sitzt oben,
         * darunter traegt die Papierfarbe weiter. Wiederholt kaemen die
         * Goldecken alle paar hundert Pixel noch einmal.
         */
        return $scope . '.d-sec-flaeche{background-color:var(--d-paper,#faf7f2);color:var(--d-fg,#14110f);'
            /*
             * Zwei Schichten. Oben das grosse Blatt, ueber die ganze Hoehe
             * gezogen - es traegt die Papierstruktur. Darueber, unten und in
             * EIGENER Groesse, das Blatt des Schlusses: der Blumenstrauss, der
             * die Einladung zumacht.
             *
             * Die zuerst genannte Schicht liegt vorn - deshalb steht der
             * Schluss zuerst. Er wird NICHT gezogen: eine Ranke, die sich mit
             * der Laenge der Einladung streckt, sieht sofort falsch aus.
             */
            . 'background-image:var(--d-sec-blatt-end,none),var(--d-sec-blatt,none);'
            . 'background-position:bottom center,top center;'
            . 'background-size:min(100%,42rem) auto,min(100%,42rem) 100%;'
            . 'background-repeat:no-repeat,no-repeat;}'
            /*
             * Das Blatt wiederholt sich nach unten - jede Laenge ein neues
             * Blatt, nicht ein gedehntes.
             *
             * "Sayfaya sigmiyorsa mesela asagi dogru yeni bir sayfa ac."
             * Genau das: repeat-y. Vorher endete das Bild mitten auf der
             * Seite mit einer harten Kante, darunter lag die nackte Farbe -
             * eine Naht quer durch die Einladung.
             *
             * Die Breite bleibt an die Karte gebunden: min(100%, 42rem) ist
             * max-w-2xl. Cover mit fixed maass gegen den Bildschirm und kam
             * unten fast doppelt so gross heraus wie oben.
             *
             * Der Pfad kommt als eigene Eigenschaft von der Vorlage, nicht
             * als background-image: sonst wuerde diese Regel ihn ueberschreiben.
             */
            /*
             * JEDER Abschnitt ist ein eigenes Blatt.
             *
             * Vorher lag EIN Blatt hinter allem und wiederholte sich nach
             * unten - und die Naht fiel dorthin, wo sie gerade hinfiel:
             * mitten ins Antwortformular, die Goldecken quer ueber "Wie viele
             * Personen". Eine Wiederholung kennt den Inhalt nicht.
             *
             * "Yazilari bol, gerekirse yeni bir arkaplan daha koy." Genau so:
             * das Blatt sitzt am Abschnitt, nicht an der Flaeche. Dann faellt
             * die Kante immer ZWISCHEN zwei Abschnitte, nie in einen hinein -
             * und wie viele Blaetter es werden, entscheidet der Inhalt.
             *
             * Das Polster oben ist gerechnet, nicht geraten: bei einer Breite
             * W ist das Blatt 1,79 W hoch (768 x 1376), und die Goldlinien
             * fangen bei 31 % seiner Hoehe an - also bei 0,31 x 1,79 = 55 %
             * der Breite. Prozente im padding beziehen sich auf die Breite,
             * deshalb steht dort 56 %.
             */
            /*
             * OHNE eigene Papierfarbe.
             *
             * Sie stand hier, solange jeder Abschnitt sein Blatt selbst
             * zeichnete: dann lag die Farbe unter dem eigenen Blatt und
             * fuellte, was das Ausblenden freigab. Seit das Blatt einmal an
             * der ganzen Flaeche liegt, ist der Abschnitt DAVOR - und eine
             * deckende Farbe hier uebermalt genau das Blatt, das man sehen
             * soll. Die Farbe traegt jetzt die Flaeche, durchgehend.
             */
            . $scope . ' .d-sec{position:relative;'
            . 'padding:56% 14% 12%;'
            . 'margin-top:0;line-height:1.7;text-align:center;}'
            . $scope . ' .d-sec:first-child{margin-top:0;}'
            /*
             * Das Blatt liegt in einer eigenen Schicht - und blendet unten aus.
             *
             * Der Fehler, der das noetig macht: das Blatt ist 1,79 mal so hoch
             * wie breit, ein Abschnitt ist meist kuerzer. Die senkrechten
             * Goldlinien fangen bei 55 % der Breite an und enden bei 110 % -
             * ein Abschnitt von 580 px bei 672 px Breite schneidet also
             * MITTEN durch sie hindurch. Eine Linie, die einfach aufhoert,
             * sieht aus wie ein Druckfehler.
             *
             * Ausblenden statt abschneiden. Warum nicht einfach die
             * Abschnitte hoeher machen: dann waere jeder von ihnen 1200 px
             * hoch, sechs Abschnitte 7200 px, und der groesste Teil davon
             * leeres Papier. Warum nicht das Bild strecken: weil es dann
             * unten in einem anderen Massstab stuende als oben, und genau das
             * hat der Kunde schon einmal sofort gesehen ("ilk boyle olup
             * sonra niye buyuyor arkaplan").
             *
             * Eine eigene Schicht muss es sein, weil eine Maske auf dem
             * Abschnitt selbst auch seinen TEXT ausblenden wuerde. Deshalb
             * ::before mit dem Bild, und die Kinder eine Stufe darueber.
             *
             * Die Papierfarbe bleibt am Abschnitt und nicht an der Schicht:
             * sie soll NICHT mit ausblenden, sonst faellt am Fuss jedes
             * Blattes die Seitenfarbe durch.
             */
            /*
             * Das Blatt liegt nicht mehr hier.
             *
             * Bis hierher zeichnete es JEDER Abschnitt neu, von seiner eigenen
             * Oberkante an, und blendete es unten aus. Auf dem Telefon lief
             * damit an jeder Abschnittsgrenze ein heller Streifen quer durch
             * die Einladung: der obere Rand des Bildes, immer wieder.
             *
             * Es liegt jetzt einmal an der ganzen Flaeche (siehe oben) und
             * wird auf deren Hoehe gezogen. Was bleibt, ist die Schicht fuer
             * den Text - er soll ueber dem Blatt liegen, nicht darunter.
             */
            // Ueber der Schicht, sonst laege der Text darunter.
            . $scope . ' .d-sec > *{position:relative;z-index:1;}'
            // Die Ueberschriften in der Auszeichnungsschrift und im Akzent -
            // dieselben zwei Marken, die auf der Karte den Ton angeben.
            . $scope . ' .d-sec-title{font-size:1.5rem;font-weight:400;line-height:1.3;margin-bottom:1.5rem;'
            . 'font-family:var(--df-display,inherit);color:var(--d-accent,inherit);'
            . 'letter-spacing:0.16em;text-transform:uppercase;}'
            . $scope . ' .d-sec p{margin-bottom:0.5rem;}'
            . $scope . ' .d-sec-days{display:block;margin-bottom:0.25rem;}'
            /*
             * Das Zeichen fuer die Bilder. Gesperrt gesetzt, weil es
             * abgeschrieben wird und nicht gelesen - dieselbe Ueberlegung wie
             * bei der Kontonummer eine Zeile weiter.
             */
            . $scope . ' .d-sec-hashtag{margin-top:1.2rem;letter-spacing:0.1em;font-size:0.9rem;}'
            /*
             * Die Kontonummer. tabular-nums, damit die Vierergruppen
             * untereinander stehen, wenn sie umbrechen; break-all, weil eine
             * IBAN auf einem schmalen Telefon sonst aus dem Blatt laeuft -
             * und eine halbe Kontonummer ist keine.
             */
            . $scope . ' .d-sec-konto{margin-top:1.2rem;display:grid;gap:0.25rem;}'
            . $scope . ' .d-sec-inhaber{font-size:0.86rem;opacity:0.8;}'
            . $scope . ' .d-sec-iban{font-variant-numeric:tabular-nums;letter-spacing:0.08em;'
            . 'word-break:break-all;}'
            /*
             * Der Spieler ist der eingebaute des Browsers - er laesst sich
             * kaum faerben, und das ist in Ordnung: er soll aussehen wie ein
             * Spieler und nicht wie Schmuck.
             */
            . $scope . ' .d-sec-ton{display:block;margin:1.2rem auto 0;width:100%;max-width:20rem;}'
            /*
             * Die Bilder. Quadratisch beschnitten und nicht in ihrem eigenen
             * Format: acht Bilder aus acht Kameras haben acht Seitenverhaeltnisse,
             * und untereinander gestellt ergibt das eine Treppe. Ein Raster
             * verlangt eine gemeinsame Form.
             */
            . $scope . ' .d-sec-bilder{display:grid;grid-template-columns:repeat(2,1fr);'
            . 'gap:0.5rem;margin-top:1.2rem;}'
            . $scope . ' .d-sec-bilder img{display:block;width:100%;aspect-ratio:1;'
            . 'object-fit:cover;}'
            // Als Block zentriert, innen ausgerichtet: die Uhrzeiten stehen
            // untereinander, sonst waere die Spalte eine Treppe.
            . $scope . ' .d-sec-plan{display:grid;grid-template-columns:auto auto;'
            . 'gap:0.6rem 2rem;justify-content:center;text-align:left;}'
            . $scope . ' .d-sec-plan dt{font-weight:600;}'
            . $scope . ' .d-sec-plan dd{margin:0;}'
            . $scope . ' .d-sec-form{display:grid;gap:1.1rem;max-width:24rem;'
            . 'margin-inline:auto;text-align:left;}'
            . $scope . ' .d-sec-form-row{display:grid;gap:0.3rem;}'
            . $scope . ' .d-sec-form-row span{font-size:0.72rem;letter-spacing:0.12em;text-transform:uppercase;opacity:0.7;}'
            . $scope . ' .d-sec-form input[type=text],'
            . $scope . ' .d-sec-form input[type=number]{border:0;border-bottom:1px solid currentColor;background:transparent;padding:0.35rem 0;color:inherit;font:inherit;}'
            . $scope . ' .d-sec-form button{justify-self:center;margin-top:0.5rem;border:1px solid currentColor;background:transparent;padding:0.55rem 1.5rem;color:inherit;font:inherit;cursor:pointer;}'

            /*
             * Das Zeichen.
             *
             * Als MASKE und nicht als Bild: ein <img> traegt die Farben
             * seiner Datei, und dann braeuchte jede Vorlage ihre eigene
             * Kopie jedes Zeichens. Unter einer Maske liegt eine Flaeche in
             * currentColor - also in der Farbe, die der Grafiker dem
             * Abschnitt gegeben hat. Eine Datei, jede Farbe.
             *
             * Eingebettet wird die Zeichnung dabei NICHT. Das Haus zeigt
             * SVG nur in <img> und nie im Markup; eine Maske holt die Datei
             * wie ein Bild und laesst sie ebenso draussen. Die Richtlinie
             * behandelt sie unter img-src, und das steht ohnehin auf self.
             *
             * Die Groesse haengt an der Schrift (em) und nicht an Pixeln:
             * ein Zeichen neben einer Zeile soll mit ihr wachsen.
             */
            /*
             * Die Kleiderordnung: die Ansage gross und mit ihrem Zeichen, der
             * Hinweis darunter und leiser.
             */
            . $scope . ' .d-sec-dresscode .d-dress-code{display:flex;align-items:center;'
              . 'justify-content:center;gap:0.6rem;font-size:1.15em;letter-spacing:0.06em;}'
            . $scope . ' .d-sec-dresscode .d-dress-note{margin-top:0.6rem;opacity:0.75;'
              . 'max-width:24rem;margin-inline:auto;}'
            /*
             * Die Speisekarte. Zeichen links, darueber die Art, darunter das,
             * was serviert wird - das Zeichen ueber beide Zeilen.
             *
             * Die Art steht klein und leise: sie ordnet nur ein. Gelesen wird
             * "Mercimek Corbasi", nicht "Suppe".
             */
            . $scope . ' .d-sec-menu .d-menu-zeile{display:grid;'
              . 'grid-template-columns:auto 1fr;column-gap:0.85rem;'
              . 'margin-top:1.15rem;text-align:left;max-width:22rem;margin-inline:auto;}'
            . $scope . ' .d-sec-menu .d-menu-zeile:first-child{margin-top:0;}'
            . $scope . ' .d-sec-menu .d-ikon{grid-column:1;grid-row:1 / span 2;'
              . 'align-self:center;width:1.5em;height:1.5em;}'
            . $scope . ' .d-sec-menu .d-menu-art{grid-column:2;grid-row:1;'
              . 'font-size:0.7rem;letter-spacing:0.14em;text-transform:uppercase;opacity:0.6;}'
            . $scope . ' .d-sec-menu .d-menu-wert{grid-column:2;grid-row:2;}'

            /*
             * Titel und Satz einer Ablaufzeile. Der Satz steht unter dem
             * Titel und leiser - er erklaert, er ruft nicht.
             *
             * Das Rozet ist hier nur eine Huelle, die ihr Zeichen mittig
             * haelt. Rund wird es erst im Zeitstrahl, wo eine Linie
             * hindurchlaeuft, die es zudecken muss.
             */
            . $scope . ' .d-sec-plan .d-plan-titel{display:block;}'
            . $scope . ' .d-sec-plan .d-plan-text{display:block;margin-top:0.2rem;'
              . 'font-size:0.88em;opacity:0.75;}'
            . $scope . ' .d-sec-plan .d-plan-rozet{display:inline-flex;'
              . 'align-items:center;justify-content:center;flex:none;}'

            . $scope . ' .d-ikon{display:inline-block;width:1.15em;height:1.15em;'
              . 'vertical-align:-0.15em;background-color:currentColor;'
              . '-webkit-mask-position:center;mask-position:center;'
              . '-webkit-mask-size:contain;mask-size:contain;'
              . '-webkit-mask-repeat:no-repeat;mask-repeat:no-repeat;}';
    }

    /**
     * Der Stilblock einer Gestalt.
     *
     * Getrennt von baseline(), weil er nur dann geschrieben wird, wenn ihn
     * ein Abschnitt auch traegt. Und getrennt vom Markup, weil eine Gestalt
     * genau das sein soll: ein anderes AUSSEHEN derselben Sache. Keiner der
     * Bloecke hier aendert eine einzige Zeile HTML - sobald eine Gestalt
     * eigenes Markup braucht, ist sie in Wahrheit eine eigene Art und
     * gehoert in TYPES.
     *
     * Der Selektor nennt Art UND Gestalt: "gross" heisst beim Ort etwas
     * anderes als beim Countdown. Ohne die Art davor faerbte der eine Block
     * den anderen um, sobald beide auf derselben Seite stehen.
     *
     * currentColor statt einer Marke, wo immer es geht - so nimmt die
     * Gestalt die Farbe an, die der Grafiker dem Abschnitt gegeben hat,
     * statt eine zweite Quelle dafuer aufzumachen.
     */
    private static function variantCss(string $art, string $variante, string $scope): string
    {
        $sel = $scope . ' .d-sec-' . $art . '.d-sec-v-' . $variante;

        return match ($art . '/' . $variante) {
            /*
             * Der Ablauf als Strahl. Ein Programm mit acht Zeilen liest sich
             * so besser denn als Tabelle: die Uhrzeit fuehrt, der Punkt sitzt
             * auf der Linie, und was darunter steht, gehoert sichtbar dazu.
             */
            /*
             * Der Ablauf als Strahl - die Linie zwischen Uhrzeit und Ring.
             *
             * Die Linie lag frueher am linken Rand der ganzen Liste, dann
             * mitten durch den Ring. Ayhan hat eine zweite fremde Einladung
             * geschickt: dort laeuft die Linie NEBEN dem Ring, und der Ring
             * steht frei davor. Das liest sich ruhiger - der Ring muss die
             * Linie nicht mehr zudecken, also braucht er auch keine eigene
             * Papierfarbe mehr und laesst das Blatt durchscheinen.
             *
             * Drei Groessen fuehren den Block, damit die Zahlen einander
             * folgen statt sich zu widersprechen:
             *   --d-plan-ring    der Durchmesser des Rings
             *   --d-plan-strahl  der Abstand der Linie vom rechten Rand des
             *                    <dt> - Ring plus halbe Luecke, also genau
             *                    die Mitte zwischen Uhrzeit und Ring
             *   --d-plan-kopf    die erste Zeile des Titels (Groesse mal
             *                    Zeilenhoehe). Uhrzeit und Ring richten sich
             *                    daran aus, nicht an der ganzen Zeile: sonst
             *                    saesse der Ring bei einem dreizeiligen Satz
             *                    in der Mitte des Absatzes.
             *
             * Alles in em, weil die Groessen an der Schrift haengen und nicht
             * an Pixeln - nur der senkrechte Rhythmus bleibt in rem, damit
             * die Zeilen ueber verschieden grosse Abschnitte hinweg denselben
             * Abstand halten.
             */
            'program/zeitstrahl' => $sel . ' .d-sec-plan{display:grid;'
                . '--d-plan-ring:2.6em;--d-plan-strahl:3.25em;--d-plan-kopf:1.74em;'
                . 'grid-template-columns:auto 1fr;column-gap:1.15rem;text-align:left;'
                . 'max-width:26rem;margin-inline:auto;}'
                . $sel . ' .d-sec-plan dt{position:relative;display:flex;align-items:flex-start;'
                . 'justify-content:flex-end;gap:1.3em;margin-top:2.2rem;font-weight:600;}'
                . $sel . ' .d-sec-plan dd{margin:0;margin-top:2.2rem;}'
                . $sel . ' .d-sec-plan dt:first-of-type,'
                . $sel . ' .d-sec-plan dd:first-of-type{margin-top:0;}'
                // Die Uhrzeit bekommt die Zeilenhoehe des Titels: so steht sie
                // auf derselben Hoehe wie dessen erste Zeile, ohne dass eine
                // zweite Zahl dafuer erfunden werden muesste.
                . $sel . ' .d-sec-plan .d-plan-zeit{line-height:var(--d-plan-kopf);}'
                /*
                 * Die Linie: in der Luecke zwischen Uhrzeit und Ring, und
                 * ueber den Zeilenabstand hinaus zur naechsten Zeile.
                 *
                 * Sie haengt am <dt> und nicht am <dl>, weil die erste Spalte
                 * mitwaechst: wie breit die Uhrzeiten sind, weiss nur das
                 * Raster. Vom rechten Rand des <dt> aus gerechnet stimmt der
                 * Abstand dagegen immer.
                 *
                 * Das <dt> wird vom Raster auf die volle Zeilenhoehe gedehnt
                 * (kein align-self) - nur deshalb reicht die Linie von Zeile
                 * zu Zeile, auch wenn der Satz daneben dreizeilig ist.
                 */
                . $sel . ' .d-sec-plan dt::after{content:"";position:absolute;'
                . 'right:var(--d-plan-strahl);top:-2.2rem;bottom:-2.2rem;width:1px;'
                . 'background:currentColor;opacity:0.3;}'
                /*
                 * Der Punkt fuer Zeilen OHNE Zeichen - auf der Linie. Ein
                 * leerer Ring saehe aus wie ein vergessenes Bild; ein Punkt
                 * markiert den Moment und genuegt.
                 *
                 * Solange die Linie durch den Ring lief, deckte der Ring den
                 * Punkt mit zu und die Bedingung war unnoetig. Jetzt steht der
                 * Ring daneben - also muss die Zeile mit Zeichen den Punkt
                 * selbst weglassen, sonst stuende beides nebeneinander.
                 *
                 * Kennt ein Browser :has() nicht, faellt die Regel ganz weg:
                 * dann bleibt die Linie an dieser Stelle unmarkiert. Das ist
                 * die harmlosere Haelfte - ein Punkt zu viel neben jedem Ring
                 * waere in JEDER Zeile zu sehen.
                 */
                /*
                 * Und die Zeile ohne Zeichen haelt den Platz des Rings frei.
                 * Ohne das ruecken ihre Ziffern an den rechten Rand des <dt>
                 * (justify-content:flex-end) und stuenden als einzige RECHTS
                 * der Linie - die Spalte der Uhrzeiten haette einen Knick.
                 */
                . $sel . ' .d-sec-plan dt:not(:has(.d-plan-rozet))'
                . '{padding-right:calc(var(--d-plan-ring) + 1.3em);}'
                . $sel . ' .d-sec-plan dt:not(:has(.d-plan-rozet))::before{content:"";position:absolute;'
                . 'right:calc(var(--d-plan-strahl) - 0.18em);'
                . 'top:calc((var(--d-plan-kopf) - 0.36em) / 2);'
                . 'width:0.36em;height:0.36em;'
                . 'border-radius:50%;background:currentColor;}'
                // Der Ring steht frei neben der Linie: keine Fuellung, kein
                // z-index. Der negative Rand hebt ihn auf die Mitte der ersten
                // Titelzeile - er ist hoeher als sie, also um die halbe
                // Differenz nach oben.
                . $sel . ' .d-sec-plan .d-plan-rozet{'
                . 'width:var(--d-plan-ring);height:var(--d-plan-ring);'
                . 'margin-top:calc((var(--d-plan-kopf) - var(--d-plan-ring)) / 2);'
                . 'border:1px solid currentColor;border-radius:50%;}'
                // Das Zeichen bekommt Luft im Ring: kleiner als die Grundregel,
                // sonst fuellt es ihn aus und der Ring wirkt wie ein Rahmen.
                . $sel . ' .d-sec-plan .d-plan-rozet .d-ikon{width:0.95em;height:0.95em;}'
                // Der Titel fuehrt die Zeile: Auszeichnungsschrift und gross.
                // Er ist das, was gelesen wird - die Uhrzeit ordnet nur ein.
                . $sel . ' .d-sec-plan .d-plan-titel{font-family:var(--df-display,inherit);'
                . 'font-size:1.45em;line-height:1.2;}'
                . $sel . ' .d-sec-plan .d-plan-text{opacity:0.8;}',

            /*
             * Zwei Familien nebeneinander. Ohne eigenes Markup: die beiden
             * Absaetze stehen als inline-block auf einer Zeile, der Strich
             * dazwischen ist die linke Kante des zweiten. Auf einem schmalen
             * Telefon brechen sie von selbst untereinander, und dann faellt
             * auch der Strich weg - er haengt an derselben Regel.
             */
            'family/paar' => $sel . ' .d-sec-family{display:inline-block;'
                . 'padding:0 1.4rem;vertical-align:middle;}'
                . $sel . ' .d-sec-family + .d-sec-family{border-left:1px solid currentColor;}',

            /*
             * Der Saal traegt den Namen. Die Strasse steht klein darunter,
             * und der Weg dorthin ist ein Knopf: eine unterstrichene Zeile
             * liest sich auf einer Einladung wie ein Fremdkoerper.
             */
            'location/gross' => $sel . ' .d-sec-venue{font-family:var(--df-display,inherit);'
                . 'font-size:1.7rem;line-height:1.2;margin-bottom:0.4rem;}'
                . $sel . ' .d-sec-address{font-size:0.86rem;opacity:0.75;}'
                . $sel . ' .d-sec-map{display:inline-block;margin-top:1.2rem;'
                . 'border:1px solid currentColor;padding:0.5rem 1.5rem;'
                . 'font-size:0.72rem;letter-spacing:0.14em;text-transform:uppercase;'
                . 'text-decoration:none;}',

            /*
             * Dieselbe Zahl, nur laut. Nur Groesse, kein Bau: ohne Skript
             * bleibt der Span leer, und dann traegt das gedruckte Datum den
             * Abschnitt allein - das muss auch in dieser Gestalt gelten.
             */
            'countdown/gross' => $sel . ' .d-sec-days{font-family:var(--df-display,inherit);'
                . 'font-size:3.4rem;line-height:1;margin-bottom:0.6rem;}'
                . $sel . ' .d-sec-countdown{font-size:0.86rem;letter-spacing:0.1em;}',

            /*
             * Das Formular bekommt eine Kante. Auf einem gemusterten Blatt
             * verliert es sie sonst und wirkt wie hingefallen.
             */
            'rsvp/rahmen' => $sel . ' .d-sec-form{border:1px solid currentColor;'
                . 'padding:1.6rem 1.4rem;max-width:26rem;}',

            /*
             * Fuer die laengeren Bloecke: schmale Spalte, linksbuendig, ein
             * Initial. Eine Geschichte in zentrierten Zeilen liest sich wie
             * ein Gedicht, und das war selten die Absicht.
             *
             * Das Initial haengt am ERSTEN Absatz, nicht an jedem: sonst
             * faengt jeder Absatz gross an und der Block sieht aus wie eine
             * Fibel.
             */
            /*
             * Ein kurzer Strich ueber dem Schluss. Er soll den letzten
             * Abschnitt vom vorigen loesen, ohne laut zu werden - deshalb
             * vier Zentimeter Mitte und keine Linie ueber die ganze Breite.
             *
             * Am ersten Kind, was immer das ist: mal steht dort eine
             * Ueberschrift, mal gleich das Schlusswort.
             */
            'footer/linie' => $sel . ' > *:first-child{padding-top:1.8rem;}'
                . $sel . ' > *:first-child::before{content:"";position:absolute;top:0;'
                . 'left:50%;transform:translateX(-50%);width:4rem;height:1px;'
                . 'background:currentColor;opacity:0.45;}',

            /*
             * Die Kontonummer in einem Rahmen: sie ist das einzige auf der
             * Einladung, das jemand abschreiben soll, und ein Rahmen sagt
             * genau das.
             */
            /*
             * Ein Streifen, der seitwaerts laeuft. Auf dem Telefon die
             * natuerlichere Geste - und drei Bilder nebeneinander sind dort
             * ohnehin drei Briefmarken.
             *
             * scroll-snap, damit er nach dem Wischen auf einem Bild steht und
             * nicht zwischen zweien.
             */
            'gallery/streifen' => $sel . ' .d-sec-bilder{display:flex;grid-template-columns:none;'
                . 'gap:0.5rem;overflow-x:auto;scroll-snap-type:x mandatory;'
                . '-webkit-overflow-scrolling:touch;}'
                . $sel . ' .d-sec-bilder img{flex:0 0 68%;scroll-snap-align:center;'
                . 'aspect-ratio:3/4;}',

            'gift/rahmen' => $sel . ' .d-sec-konto{border:1px solid currentColor;'
                . 'padding:1rem 1.2rem;max-width:22rem;margin-inline:auto;}',

            'text/editorial' => $sel . ' .d-sec-absatz{text-align:left;max-width:26rem;'
                . 'margin-inline:auto;line-height:1.9;}'
                . $sel . ' .d-sec-absatz:first-of-type::first-letter{float:left;'
                . 'font-family:var(--df-display,inherit);font-size:3.1em;line-height:0.82;'
                . 'padding-right:0.09em;color:var(--d-accent,inherit);}',

            default => '',
        };
    }

    /**
     * Die Abschnitte als Markup.
     *
     * $form traegt, was nur der Controller wissen kann: das CSRF-Zeichen und
     * ob gerade eben geantwortet wurde. Es kommt aus demselben Grund als
     * Parameter herein wie $heute - diese Klasse fasst weder Uhr noch Sitzung
     * an, sonst liefe sie nicht mehr unter bin/test.php. Die vier
     * Anzeige-Abschnitte lesen es nie.
     *
     * @param array<string,mixed> $doc
     * @param array<string,mixed> $data
     * @param array<string,mixed> $form
     */
    public static function html(array $doc, array $data, string $locale, string $heute = '', array $form = []): string
    {
        $out = '';

        foreach (self::visible($doc, $data, $heute) as $abschnitt) {
            $id = (string) $abschnitt['id'];
            $typ = (string) $abschnitt['type'];

            // Drei Klassen, drei Fragen: welcher Abschnitt (fuer die eigene
            // Regel des Grafikers), welche Art, welches Aussehen.
            $out .= '<section class="d-sec d-sec-' . e($id) . ' d-sec-' . e($typ)
                . ' d-sec-v-' . e((string) $abschnitt['variant']) . '">';

            // Explizit auf '' pruefen, nicht mit ?? verketten: complete()
            // schreibt beide Sprachen immer als String, also feuert ?? nie -
            // ein leeres "en" ergaebe sonst gar keinen Titel statt des
            // deutschen.
            $titel = (string) ($abschnitt['title'][$locale] ?? '');
            if ($titel === '') {
                $titel = (string) ($abschnitt['title']['de'] ?? '');
            }
            if ($titel !== '') {
                $out .= '<h2 class="d-sec-title">' . e($titel) . '</h2>';
            }

            $out .= match ($typ) {
                'location'  => self::ort($data, $locale, $abschnitt['settings']),
                'countdown' => self::countdown($data, $locale),
                'family'    => self::familien($data),
                'program'   => self::programm($data, $locale),
                'rsvp'      => self::formular($form, $locale),
                'text'      => self::freitext($abschnitt, $data),
                'footer'    => self::schluss($abschnitt, $data),
                'gift'      => self::geschenk($abschnitt, $data),
                // Als einzige Art liest sie ihre Einstellung und nicht die
                // Daten des Paares - der Klang gehoert der Vorlage.
                'music'     => self::musik($abschnitt),
                'gallery'   => self::galerie($data, $id),
                'menu'      => self::speisekarte($abschnitt, $data, $locale),
                'dresscode' => self::kleiderordnung($abschnitt, $data),
                default     => '',
            };

            $out .= '</section>';
        }

        return $out;
    }

    /**
     * @param array<string,mixed> $data
     * @param array<string,mixed> $settings
     */
    private static function ort(array $data, string $locale, array $settings = []): string
    {
        $adresse = trim((string) ($data['address'] ?? ''));
        $ort = trim((string) ($data['venue'] ?? ''));

        $out = '';
        if ($ort !== '') {
            $out .= '<p class="d-sec-venue">' . e($ort) . '</p>';
        }
        $out .= '<p class="d-sec-address">' . e($adresse) . '</p>';

        // Der Link geht zur Routenplanung, nicht auf eine Karte: wer die
        // Adresse liest, will hinfahren.
        //
        // Abschaltbar, weil er nicht immer traegt: kennt Google die Adresse
        // nicht, fuehrt er ins Leere - und ein Link ins Leere ist schlimmer
        // als keiner. Die Adresse selbst steht in beiden Faellen da.
        if ($settings['map'] ?? true) {
            $out .= '<a class="d-sec-map" rel="noopener noreferrer" target="_blank" href="'
                . e('https://www.google.com/maps/dir/?api=1&destination=' . rawurlencode($adresse))
                . '">' . e($locale === 'de' ? 'Route planen' : 'Plan route') . '</a>';
        }

        return $out;
    }

    /**
     * Der Countdown traegt sein Datum als Attribut.
     *
     * Gerechnet wird im Browser - eine auf dem Server gerenderte Zahl waere
     * in dem Moment falsch, in dem die Seite eine Minute alt ist. Die Sprache
     * aber kommt vom Server, nicht aus dem Skript: sonst gaebe es zwei
     * Quellen fuer "Tage" gegen Dates - eine im PHP, eine im JavaScript, die
     * irgendwann auseinanderlaufen. Deshalb steckt hier ein leerer Span mit
     * data-label; das Skript fuellt nur die Zahl.
     *
     * Ohne Skript bleibt der Span leer, aber das gedruckte Datum steht
     * trotzdem da (siehe Aufgabe 8) - der Countdown ist eine Zugabe, keine
     * Voraussetzung, um zu wissen, wann gefeiert wird.
     *
     * @param array<string,mixed> $data
     */
    private static function countdown(array $data, string $locale): string
    {
        $datum = trim((string) ($data['date'] ?? ''));

        return '<p class="d-sec-countdown" data-countdown="' . e($datum) . '">'
            . '<span class="d-sec-days" data-countdown-days data-label="'
            . e($locale === 'de' ? 'Tage' : 'days') . '"></span>'
            . e(Dates::long($datum, $locale)) . '</p>';
    }

    /** @param array<string,mixed> $data */
    private static function familien(array $data): string
    {
        $familien = is_array($data['families'] ?? null) ? $data['families'] : [];
        $out = '';

        foreach (['bride', 'groom'] as $seite) {
            $name = trim((string) ($familien[$seite] ?? ''));
            if ($name !== '') {
                $out .= '<p class="d-sec-family">' . e($name) . '</p>';
            }
        }

        return $out;
    }

    /** @param array<string,mixed> $data */
    private static function programm(array $data, string $locale): string
    {
        // d-sec-plan und nicht d-sec-program: die <section> traegt bereits
        // d-sec-<typ>, also d-sec-program. Solange die Liste denselben Namen
        // trug, galt JEDE Regel fuer beide - und die Zweispaltenregel machte
        // aus dem Abschnitt selbst ein Raster, in dem die Ueberschrift NEBEN
        // ihrer Liste stand statt darueber. Genau dieselbe Falle, die bei
        // freitext() vermieden wurde und hier jahrelang zuschnappte.
        $out = '<dl class="d-sec-plan">';

        foreach (self::programRows($data) as $zeile) {
            // Eigener Titel gewinnt, sonst der Vorschlag des Zeichens.
            $titel = $zeile['title'] !== ''
                ? $zeile['title']
                : SectionRegistry::iconTitle($zeile['icon'], $locale);

            /*
             * Uhrzeit und Zeichen stehen zusammen im <dt>, Titel und Satz im
             * <dd>. Beide Gestalten drucken dasselbe; sie kleiden es nur
             * anders. Ein zweiter Druckweg je Gestalt waere eine zweite
             * Wahrheit - und der Zeitstrahl braucht das Zeichen dort, wo seine
             * Linie laeuft.
             */
            $out .= '<dt><span class="d-plan-zeit">' . e($zeile['time']) . '</span>';

            if ($zeile['icon'] !== '') {
                /*
                 * Die Adresse kommt aus dem Katalog und traegt weder
                 * Anfuehrungszeichen noch Klammern - sie kann aus dem url()
                 * nicht ausbrechen.
                 *
                 * Inline und nicht im Stilblock: welche Zeichen vorkommen, sagt
                 * die EINLADUNG, und den Stilblock schreibt die VORLAGE. Die
                 * Grundregel (Groesse, Farbe, Ring) steht dort; hier steht nur,
                 * welche Zeichnung es ist.
                 */
                $datei = SectionRegistry::iconFile($zeile['icon']);
                $maske = "url('" . $datei . "')";
                $out .= '<span class="d-plan-rozet"><span class="d-ikon" style="-webkit-mask-image:'
                    . $maske . ';mask-image:' . $maske . '"></span></span>';
            }

            $out .= '</dt><dd><span class="d-plan-titel">' . e($titel) . '</span>';

            // Kein leerer Absatz fuer eine Zeile ohne Satz.
            if ($zeile['text'] !== '') {
                $out .= '<span class="d-plan-text">' . e($zeile['text']) . '</span>';
            }

            $out .= '</dd>';
        }

        return $out . '</dl>';
    }

    /**
     * Der Text eines einzelnen Blocks.
     *
     * Er liegt unter der Kennung des Abschnitts, nicht unter einem festen
     * Namen wie families oder program. Grund: von denen gibt es je einen, von
     * Textbloecken beliebig viele - "Dress Code" und "Anfahrt" sind derselbe
     * Typ und muessten sich sonst einen Platz teilen.
     *
     * Jeder Schritt einzeln geprueft: fehlt einer, ist der Text leer und kein
     * Fehler. Ein Dokument aus dem Panel soll sich nicht wegen eines fehlenden
     * Schluessels nicht mehr oeffnen lassen.
     *
     * @param array<string,mixed> $data
     */
    public static function sectionText(array $data, string $id): string
    {
        return self::sectionValue($data, $id, 'text');
    }

    /**
     * Die Voreinstellungen eines Abschnitts, geprueft.
     *
     * @param mixed $roh
     * @return array<string,string>
     */
    public static function completeDefaults(string $type, mixed $roh): array
    {
        if (!is_array($roh)) {
            return [];
        }

        $out = [];
        foreach (SectionRegistry::inputs($type) as $schluessel => $feld) {
            // Bilder haben keine Voreinstellung: eine Vorlage, die Fotos
            // mitbringt, brauchte einen Ordner und einen Besitzer - und die
            // Bilder eines fremden Paares stuenden dann in jeder Einladung.
            if ((string) $feld['type'] === 'photos') {
                continue;
            }
            $wert = $roh[$schluessel] ?? '';
            if (is_scalar($wert) && trim((string) $wert) !== '') {
                $out[$schluessel] = mb_substr((string) $wert, 0, (int) $feld['max']);
            }
        }

        return $out;
    }

    /**
     * Was in diesem Abschnitt steht: erst das Paar, dann die Vorlage.
     *
     * Die Voreinstellung ist eine Voreinstellung und kein fester Text -
     * schreibt das Paar etwas, gewinnt das Paar. Und leer geschrieben ist
     * keine Wahl, sondern ein leeres Feld: dann steht wieder da, was die
     * Vorlage vorgeschlagen hat.
     *
     * @param array<string,mixed> $abschnitt
     * @param array<string,mixed> $data
     */
    public static function inhalt(array $abschnitt, array $data, string $schluessel): string
    {
        $eigen = trim(self::sectionValue($data, (string) $abschnitt['id'], $schluessel));

        return $eigen !== '' ? $eigen : (string) ($abschnitt['defaults'][$schluessel] ?? '');
    }

    /**
     * Die Bilder, die das Paar in DIESEN Abschnitt gelegt hat.
     *
     * Der erste Inhalt, der kein Text ist - deshalb ein eigener Griff und
     * nicht sectionValue(): dort kommt ein String heraus, hier eine Liste.
     *
     * Jeder Pfad geht durch Design::safeSrc. Er stammt zwar aus dem eigenen
     * Upload, aber er steht seitdem in einem JSON-Feld, und was in einem
     * JSON-Feld steht, ist beim Lesen wieder eine Behauptung.
     *
     * @param array<string,mixed> $data
     * @return list<string>
     */
    public static function sectionPhotos(array $data, string $id): array
    {
        $alle = $data['sections'] ?? null;
        if (!is_array($alle)) {
            return [];
        }
        $eintrag = $alle[$id] ?? null;
        if (!is_array($eintrag) || !is_array($eintrag['photos'] ?? null)) {
            return [];
        }

        $out = [];
        foreach ($eintrag['photos'] as $pfad) {
            $sicher = Design::safeSrc(is_string($pfad) ? $pfad : '');
            if ($sicher !== '') {
                $out[] = $sicher;
            }
        }

        return $out;
    }

    /**
     * Ein Wert, den das Paar in DIESEN Abschnitt geschrieben hat.
     *
     * Unter der Kennung, nicht unter einem festen Namen: von Textbloecken
     * kann ein Dokument beliebig viele tragen - "Dress Code" und "Anfahrt"
     * sind dieselbe Art und muessten sich sonst einen Platz teilen.
     *
     * Welche Schluessel eine Art kennt, sagt SectionRegistry::inputs(). Hier
     * wird nur nachgeschlagen; ein unbekannter Schluessel ist leer und kein
     * Fehler.
     *
     * Jeder Schritt einzeln geprueft: fehlt einer, ist der Wert leer. Ein
     * Dokument aus dem Panel soll sich nicht wegen eines fehlenden
     * Schluessels nicht mehr oeffnen lassen.
     *
     * @param array<string,mixed> $data
     */
    public static function sectionValue(array $data, string $id, string $schluessel): string
    {
        $alle = $data['sections'] ?? null;
        if (!is_array($alle)) {
            return '';
        }
        $eintrag = $alle[$id] ?? null;
        if (!is_array($eintrag)) {
            return '';
        }

        $wert = $eintrag[$schluessel] ?? '';

        return is_scalar($wert) ? (string) $wert : '';
    }

    /**
     * Ein Textblock: Ueberschrift vom Grafiker, Inhalt vom Kunden.
     *
     * Eine bewegliche Art statt sechs starrer. Dress Code, Anfahrt, Kinder,
     * ein Dank - der Unterschied zwischen ihnen steht im Text und nicht im
     * Code; sechs Typen waeren sechsmal dieselbe Methode.
     *
     * paragraphs() ist derselbe Helfer, den die uebrigen Vorlagen benutzen:
     * eine Leerzeile des Kunden wird ein Absatz. Ohne ihn faellt alles zu
     * einer Wand zusammen, und der Kunde sieht seine Gliederung nicht wieder.
     *
     * @param array<string,mixed> $data
     */
    private static function freitext(array $abschnitt, array $data): string
    {
        $out = '';

        foreach (paragraphs(self::inhalt($abschnitt, $data, 'text')) as $absatz) {
            // d-sec-absatz und nicht d-sec-text: die <section> traegt bereits
            // d-sec-<typ>, also d-sec-text. Eine Regel fuer die Absaetze
            // faerbte sonst auch den Kasten um - derselbe Grund, aus dem das
            // rsvp-Formular d-sec-form heisst und nicht d-sec-rsvp.
            $out .= '<p class="d-sec-absatz">' . e($absatz) . '</p>';
        }

        return $out;
    }

    /**
     * Der Schluss: ein letztes Wort und das Zeichen fuer die Bilder.
     *
     * Das Zeichen ist der Grund, warum das eine eigene Art ist und keine
     * Gestalt des Textblocks - es steht sonst nirgends auf der Einladung.
     *
     * Das Doppelkreuz schreibt der Renderer, nicht das Paar: wer es selbst
     * tippt, tippt es manchmal doppelt, und "##sophiaundmax" hat noch keine
     * Bilder gefunden.
     *
     * @param array<string,mixed> $data
     */
    private static function schluss(array $abschnitt, array $data): string
    {
        $out = '';

        foreach (paragraphs(self::inhalt($abschnitt, $data, 'text')) as $absatz) {
            $out .= '<p class="d-sec-absatz">' . e($absatz) . '</p>';
        }

        $zeichen = trim(self::inhalt($abschnitt, $data, 'hashtag'));
        if ($zeichen !== '') {
            $out .= '<p class="d-sec-hashtag">#' . e(ltrim($zeichen, '#')) . '</p>';
        }

        return $out;
    }

    /**
     * Das Geschenk: ein Wunsch und eine Kontonummer.
     *
     * Die IBAN steht in Vierergruppen. Das ist der Grund fuer die eigene Art:
     * eine Kontonummer will gelesen und abgeschrieben werden, und in einer
     * Zeile Fliesstext gelingt das niemandem.
     *
     * Geprueft wird sie nicht. Eine Einladung ist kein Bankformular, und eine
     * abgelehnte IBAN, die in Wahrheit richtig ist, kostet mehr als eine
     * falsche, die dasteht - das Paar liest seine eigene Einladung.
     *
     * @param array<string,mixed> $data
     */
    private static function geschenk(array $abschnitt, array $data): string
    {
        $out = '';

        foreach (paragraphs(self::inhalt($abschnitt, $data, 'text')) as $absatz) {
            $out .= '<p class="d-sec-absatz">' . e($absatz) . '</p>';
        }

        $inhaber = trim(self::inhalt($abschnitt, $data, 'holder'));
        $iban    = trim(self::inhalt($abschnitt, $data, 'iban'));

        if ($iban === '') {
            return $out;
        }

        $out .= '<p class="d-sec-konto">';
        if ($inhaber !== '') {
            $out .= '<span class="d-sec-inhaber">' . e($inhaber) . '</span>';
        }

        return $out . '<span class="d-sec-iban">' . e(self::ibanGruppen($iban)) . '</span></p>';
    }

    /**
     * Eine Kontonummer in Vierergruppen, wie sie auf Papier steht.
     *
     * Leerzeichen und Bindestriche fallen weg, bevor neu gruppiert wird -
     * sonst haengt die Gruppierung davon ab, wie jemand getippt hat.
     */
    public static function ibanGruppen(string $iban): string
    {
        $roh = strtoupper((string) preg_replace('/[^A-Za-z0-9]/', '', $iban));

        return trim(chunk_split($roh, 4, ' '));
    }

    /**
     * Die Bilder.
     *
     * loading="lazy" an jedem: acht Bilder sind schnell ein paar Megabyte,
     * und die Galerie steht weit unten - die meisten Gaeste sehen zuerst die
     * Karte und lesen dann, wo gefeiert wird.
     *
     * Ohne Bildunterschrift und ohne Reihenfolge zum Anklicken: eine
     * Einladung ist kein Album. Was hier steht, sind ein paar Bilder des
     * Paares, keine Ausstellung.
     *
     * alt bleibt leer und ist damit ausdruecklich Schmuck: ein Vorleser
     * ueberspringt es, statt achtmal "Bild" zu sagen. Was der Gast wissen
     * muss, steht in den Abschnitten darueber.
     *
     * @param array<string,mixed> $data
     */
    /**
     * Steht auf der Karte ueberhaupt etwas?
     *
     * Dieselbe Schleife wie beim Drucken, nur ohne Markup - und bewusst
     * dieselbe Quelle: der Katalog sagt, welche Gaenge es gibt. Eine zweite
     * Liste hier liefe frueher oder spaeter auseinander.
     *
     * @param array<string,mixed> $abschnitt
     * @param array<string,mixed> $data
     */
    private static function speisekarteGefuellt(array $abschnitt, array $data): bool
    {
        foreach (array_keys(SectionRegistry::inputs('menu')) as $schluessel) {
            if (trim(self::inhalt($abschnitt, $data, (string) $schluessel)) !== '') {
                return true;
            }
        }

        return false;
    }

    /**
     * Die Speisekarte.
     *
     * Kein eigener Motor: die Gaenge sind Eingaben des Katalogs wie jeder
     * andere Text eines Abschnitts, und "nur was gefuellt ist" ist die Regel,
     * nach der ohnehin jeder Abschnitt gedruckt wird. Deshalb steht hier auch
     * keine Liste der Gaenge - der Katalog sagt, welche es gibt und in
     * welcher Reihenfolge sie serviert werden.
     *
     * Das Zeichen waehlt niemand: eine Suppe ist eine Suppe. Es haengt an der
     * Art im Katalog, nicht an einer Angabe des Paares - anders als beim
     * Ablauf, wo dieselbe Uhrzeit alles Moegliche bedeuten kann.
     *
     * @param array<string,mixed> $abschnitt
     * @param array<string,mixed> $data
     */
    private static function speisekarte(array $abschnitt, array $data, string $locale): string
    {
        $out = '';

        foreach (SectionRegistry::inputs('menu') as $schluessel => $feld) {
            $wert = trim(self::inhalt($abschnitt, $data, (string) $schluessel));
            if ($wert === '') {
                continue;
            }

            $out .= '<div class="d-menu-zeile">';

            $datei = SectionRegistry::iconFile((string) ($feld['icon'] ?? ''));
            if ($datei !== '') {
                $maske = "url('" . $datei . "')";
                $out .= '<span class="d-ikon" style="-webkit-mask-image:' . $maske
                    . ';mask-image:' . $maske . '"></span>';
            }

            $etikett = (string) ($feld['label'][$locale] ?? $feld['label']['de'] ?? $schluessel);

            $out .= '<span class="d-menu-art">' . e($etikett) . '</span>'
                . '<span class="d-menu-wert">' . e($wert) . '</span></div>';
        }

        return $out;
    }

    /**
     * Die Kleiderordnung.
     *
     * Das Zeichen steht bei der Ansage und nicht beim Hinweis: es zeigt, WAS
     * hier steht, und das ist die Ansage. Ohne Ansage steht der Hinweis
     * allein - dann ist er selbst die Ansage.
     *
     * @param array<string,mixed> $abschnitt
     * @param array<string,mixed> $data
     */
    private static function kleiderordnung(array $abschnitt, array $data): string
    {
        $code = trim(self::inhalt($abschnitt, $data, 'code'));
        $hinweis = trim(self::inhalt($abschnitt, $data, 'note'));
        $out = '';

        if ($code !== '') {
            $out .= '<p class="d-dress-code">';

            $datei = SectionRegistry::iconFile('dresscode');
            if ($datei !== '') {
                $maske = "url('" . $datei . "')";
                $out .= '<span class="d-ikon" style="-webkit-mask-image:' . $maske
                    . ';mask-image:' . $maske . '"></span>';
            }

            $out .= e($code) . '</p>';
        }

        // Kein leerer Absatz fuer einen fehlenden Hinweis.
        if ($hinweis !== '') {
            $out .= '<p class="d-dress-note">' . e($hinweis) . '</p>';
        }

        return $out;
    }

    private static function galerie(array $data, string $id): string
    {
        $out = '';

        foreach (self::sectionPhotos($data, $id) as $pfad) {
            $out .= '<img src="' . e($pfad) . '" alt="" loading="lazy" decoding="async">';
        }

        return $out === '' ? '' : '<div class="d-sec-bilder">' . $out . '</div>';
    }

    /**
     * Musik: ein Spieler, kein Selbststart.
     *
     * Der Browser blockiert Ton ohne Zutun ohnehin - und selbst wenn nicht:
     * eine Einladung, die von allein anfaengt zu spielen, wird im Buero
     * geoeffnet und sofort wieder geschlossen.
     *
     * Der eingebaute Spieler des Browsers und kein eigener: ein eigener
     * braeuchte ein Skript, und ohne dieses Skript bliebe ein Knopf stehen,
     * der nichts tut. Der eingebaute funktioniert auch dann.
     *
     * preload="none": eine Tonspur ist ein paar Megabyte, und die meisten
     * Gaeste tippen nie darauf.
     *
     * @param array<string,mixed> $abschnitt
     */
    private static function musik(array $abschnitt): string
    {
        $spur = Design::safeSrc((string) ($abschnitt['settings']['track'] ?? ''));
        if ($spur === '') {
            return '';
        }

        return '<audio class="d-sec-ton" controls preload="none" src="' . e($spur) . '"></audio>';
    }

    /**
     * Der einzige Abschnitt, der schreibt.
     *
     * Kein action-Attribut: die Einladung nimmt ihre eigene Antwort entgegen,
     * genau wie im alten Motor (InviteController::show -> saveRsvp). Ein
     * eigener Endpunkt waere eine zweite Adresse fuer dieselbe Sache, und
     * eine zweite Adresse muesste ihrerseits wissen, zu welcher Einladung
     * sie gehoert.
     *
     * maxlength und min/max sind Hinweise fuer den Browser, keine Sicherung -
     * gekuerzt und beschnitten wird im Controller, wo ein Absender ohne
     * Browser genauso ankommt.
     *
     * Die Etiketten stehen hier und nicht im Woerterbuch: I18n::t() geht
     * ueber Texts::get() an die Datenbank, und diese Klasse ist rein.
     * Dieselbe Entscheidung wie bei "Route planen" und "Tage".
     *
     * @param array<string,mixed> $form
     */
    private static function formular(array $form, string $locale): string
    {
        $de = $locale !== 'en';

        // Nach dem Absenden kein zweites, wieder leeres Formular: das liest
        // sich wie "nicht angekommen" und der Gast antwortet ein zweites Mal.
        if (!empty($form['sent'])) {
            return '<p class="d-sec-form-done">'
                . e($de ? 'Danke - eure Antwort ist angekommen.' : 'Thank you - your reply has arrived.')
                . '</p>';
        }

        return '<form class="d-sec-form" method="post">'
            . '<input type="hidden" name="csrf" value="' . e((string) ($form['csrf'] ?? '')) . '">'
            . '<label class="d-sec-form-row"><span>'
            . e($de ? 'Euer Name' : 'Your name')
            . '</span><input type="text" name="name" maxlength="60" required></label>'
            . '<div class="d-sec-form-row">'
            . '<label><input type="radio" name="coming" value="1" checked> '
            . e($de ? 'Wir kommen' : 'We are coming')
            . '</label>'
            . '<label><input type="radio" name="coming" value="0"> '
            . e($de ? 'Wir kommen leider nicht' : 'We cannot make it')
            . '</label>'
            . '</div>'
            . '<label class="d-sec-form-row"><span>'
            . e($de ? 'Wie viele Personen' : 'How many people')
            . '</span><input type="number" name="count" value="1" min="1" max="20"></label>'
            . '<label class="d-sec-form-row"><span>'
            . e($de ? 'Etwas dazu' : 'Anything else')
            . '</span><input type="text" name="note" maxlength="300"></label>'
            . '<button type="submit">' . e($de ? 'Absenden' : 'Send') . '</button>'
            . '</form>';
    }
}
