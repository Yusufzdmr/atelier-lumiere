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
        // Die dreizehnte: der Tag selbst, gross gesetzt. Angehaengt und
        // nicht einsortiert - die Reihenfolge steht in Tests.
        'date',
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
    /**
     * Die Leiter der Abstaende.
     *
     * Prozent und keine rem: das Polster der Abschnitte ist seit jeher an
     * die BREITE gebunden (Prozente im padding beziehen sich auf die
     * Breite), und genau deshalb sitzt es auf dem Telefon so wie auf dem
     * Schreibtisch. Eine feste rem-Zahl waere am Telefon plötzlich die
     * halbe Seite.
     *
     * s, m und l sind die drei Werte, die es vorher schon gab (eng, normal,
     * weit). xs und xl sind neu und liegen aussen daneben - eine Leiter, die
     * an ihren Enden nichts Neues bietet, waere drei Worte in fuenf
     * Verpackungen.
     */
    /**
     * Die Leiter der Mindesthoehen.
     *
     * vh und nicht Prozent, anders als bei der Luft: hier geht es um das
     * Verhaeltnis zum BILDSCHIRM ("eine Seite pro Abschnitt"), nicht zur
     * Breite. Das ist der eine Ort, an dem das die richtige Frage ist.
     *
     * "voll" steht auf 100dvh und nicht 100vh: am Telefon zaehlt vh den
     * Streifen hinter der Adressleiste mit, und dann ist der Abschnitt
     * hoeher als das Sichtbare - dieselbe Beschwerde, die die Buehne schon
     * einmal hatte (design-stage.php).
     */
    private const HOEHE = [
        's'    => '32vh',
        'm'    => '50vh',
        'l'    => '72vh',
        'voll' => '100dvh',
    ];

    private const LUFT = [
        'xs' => '4%',
        's'  => '6%',
        'm'  => '12%',
        'l'  => '22%',
        'xl' => '34%',
    ];

    public static function complete(array $doc): array
    {
        /*
         * Die eigenen Zeichen werden HIER geprueft und nicht nur in
         * Design::complete().
         *
         * Der Grund ist gemessen: DesignSections::complete() ist ein eigener
         * Weg - die Tests und die lebende Vorschau kommen hier herein, ohne
         * vorher durch Design::complete() gegangen zu sein. Ohne diese Zeile
         * las zeichen() die rohen Werte aus dem Dokument, und ein fremder
         * Pfad landete ungeprueft im Markup.
         *
         * Zweimal geprueft schadet nicht: Design::icons() ist rein und
         * liefert bei einem schon geprueften Dokument dasselbe zurueck.
         */
        $doc['icons'] = Design::icons($doc);
        // Dieselbe Vorsicht fuer die Zeichen am Countdown, aus demselben
        // Grund: auch sie tragen Pfade, und auch sie werden von hier aus
        // gedruckt.
        $doc['countdownIcons'] = Design::countdownIcons($doc);

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
                /*
                 * Der freie Schmuck dieses Abschnitts.
                 *
                 * "Gerektiginde ayni bolume birden fazla gorsel veya video
                 * elementi ekleyebilmeliyim." Am Countdown gibt es das seit
                 * heute; hier ist dasselbe fuer JEDEN Abschnitt - ein Bild
                 * neben die Ueberschrift, ein Film neben den Inhalt, so
                 * viele, wie die Vorlage will.
                 *
                 * Am Abschnitt und nicht an der Art: zwei Textbloecke in
                 * derselben Vorlage duerfen verschieden geschmueckt sein.
                 * Derselbe Grund, aus dem die Einstellungen hier stehen und
                 * nicht im Katalog.
                 *
                 * Acht statt vierundzwanzig: mehr als acht Stueck an einer
                 * Ueberschrift sind kein Schmuck mehr, sondern ein zweites
                 * Bild - und dafuer gibt es das Blatt des Abschnitts.
                 */
                'deko'    => Design::freieElemente($eintrag['deko'] ?? null, Design::DEKO_ANKER, 8),
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
            // Der Saal allein traegt den Abschnitt auch.
            //
            // Frueher hing er allein an der Adresse - "ohne Adresse haette
            // der Kartenlink kein Ziel". Das stellte den Link ueber die
            // Aussage: "Wir feiern in der Villa Sonnenhof" ist eine
            // vollstaendige Angabe, auch wenn die Strasse noch fehlt. Wer nur
            // den Saal nennt, bekommt jetzt den Saal - und keine Karte, denn
            // die braucht weiterhin eine Adresse (siehe ort()).
            'location'  => trim((string) ($data['address'] ?? '')) !== ''
                        || trim((string) ($data['venue'] ?? '')) !== '',
            // Ein vergangener Termin bekommt keinen Countdown; der Tag selbst
            // zaehlt noch, es wird ja bis zum Morgen gefeiert.
            'countdown' => $datum !== '' && $datum >= $heute,
            /*
             * Das Datum dagegen bleibt stehen, auch danach.
             *
             * Es ist keine Zugabe, sondern eine Auskunft: wer die Einladung
             * ein Jahr spaeter noch einmal oeffnet, will lesen, wann es war.
             * Genau darin unterscheidet es sich vom Countdown, der ohne
             * Zukunft nichts mehr zu sagen hat.
             */
            'date'      => $datum !== '',
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
            // Der Schluss traegt drei Dinge und braucht nur eines davon.
            //
            // Der Hinweis auf uns zaehlt mit, und das ist keine Kleinigkeit:
            // zaehlte er nicht, erschiene er nur auf Einladungen, deren Paar
            // ohnehin ein Schlusswort geschrieben hat - also selten und
            // unvorhersehbar. Der Grafiker schaltet ihn in der Vorlage ein
            // und darf dann erwarten, dass er dasteht.
            'footer'    => trim(self::inhalt($abschnitt, $data, 'text')) !== ''
                        || trim(self::inhalt($abschnitt, $data, 'hashtag')) !== ''
                        || (bool) ($abschnitt['settings']['credit'] ?? false),
            // Ohne Kontonummer bleibt der Wunsch, und der ist auch etwas.
            'gift'      => trim(self::inhalt($abschnitt, $data, 'text')) !== ''
                        || trim(self::inhalt($abschnitt, $data, 'iban')) !== '',
            // Die Tonspur gehoert der Vorlage, nicht dem Paar: ohne sie hat
            // dieser Abschnitt nichts zu spielen und wird nicht gedruckt.
            // Die Vorlage ODER das Paar. Frueher stand hier allein die Spur
            // der Vorlage - ein Paar, das sein Lied hochlud, ohne dass der
            // Grafiker eine Voreinstellung gesetzt hatte, bekam trotzdem
            // keine Musik: die Datei lag auf der Platte und der Abschnitt
            // wurde nie gedruckt.
            /*
             * Zwei Quellen, je nach Gestalt: die Gestalt "einbetten" bringt
             * keine Tonspur mit, sondern eine Adresse. Nur nach der Tonspur
             * zu fragen hiesse, dass ein eingebettetes Lied nie gedruckt
             * wird - dieselbe Falle wie oben beim Lied des Paares, nur eine
             * Gestalt weiter.
             */
            'music'     => (string) $abschnitt['variant'] === 'einbetten'
                ? Design::safeEinbettung((string) ($abschnitt['settings']['embed'] ?? '')) !== ''
                : self::tonspur($abschnitt, $data) !== '',
            // Kein Bild, kein Abschnitt. Eine leere Galerie ist eine
            // Ueberschrift ueber nichts.
            'gallery'   => self::sectionPhotos($data, (string) $abschnitt['id']) !== [],
            // Ein einziger Gang genuegt. Keiner heisst: eine Ueberschrift
            // ueber einer leeren Karte.
            'menu'      => self::speisekarteGefuellt($abschnitt, $data),
            // Ansage, Hinweis, eine der beiden Zeilen oder die Palette -
            // eines genuegt. Ohne die letzten drei stuende eine Vorlage, die
            // nur Farben zeigt, ueberhaupt nicht auf der Einladung.
            'dresscode' => trim(self::inhalt($abschnitt, $data, 'code')) !== ''
                        || trim(self::inhalt($abschnitt, $data, 'note')) !== ''
                        || trim(self::inhalt($abschnitt, $data, 'women')) !== ''
                        || trim(self::inhalt($abschnitt, $data, 'men')) !== ''
                        || trim(self::inhalt($abschnitt, $data, 'colors')) !== '',
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

            /*
             * Die Luft, oben und unten.
             *
             * Die Leiter steht in LUFT und nicht als Zahlen hier: dieselben
             * fuenf Stufen gelten fuer beide Richtungen, und zwei Listen
             * mit denselben Werten laufen auseinander.
             *
             * Geschrieben wird nur, was ABWEICHT. "m" ist genau die Zahl,
             * die schon in der Grundregel steht (12 %), und eine zweite
             * Zeile mit demselben Wert waere Rauschen in einem Stilblock,
             * der inline in jeder Seite steht - nicht in einer Datei, die
             * der Browser einmal holt und behaelt. Dieselbe Haltung wie bei
             * der Ausrichtung eine Zeile darueber.
             *
             * Oben nur, wenn jemand es sagt: "auto" ist das gerechnete
             * Polster der Grundregel, mit dem der Titel zwischen die
             * Goldlinien des Blattes faellt.
             */
            $luft = (string) ($einstellung['space'] ?? 'm');
            if ($luft !== 'm' && isset(self::LUFT[$luft])) {
                $regeln .= 'padding-bottom:' . self::LUFT[$luft] . ';';
            }

            $oben = (string) ($einstellung['spaceTop'] ?? 'auto');
            if ($oben !== 'auto' && isset(self::LUFT[$oben])) {
                $regeln .= 'padding-top:' . self::LUFT[$oben] . ';';
            }

            /*
             * Die Mindesthoehe - und die Zentrierung dazu.
             *
             * Ohne die Zentrierung waere die Hoehe nur Luft UNTEN, also
             * genau das, worueber die Beschwerde ging ("cok buyuk
             * bosluklar"). Wer einem Abschnitt eine Seite gibt, will ihn in
             * der Mitte dieser Seite.
             *
             * Nur wenn eine gesetzt ist: "auto" ist kein Wert, sondern die
             * Abwesenheit eines Werts, und flex am Abschnitt aendert die
             * Aussenabstaende seiner Kinder.
             */
            $hoch = (string) ($einstellung['height'] ?? 'auto');
            if ($hoch !== 'auto' && isset(self::HOEHE[$hoch])) {
                $regeln .= 'min-height:' . self::HOEHE[$hoch] . ';'
                    . 'display:flex;flex-direction:column;justify-content:center;';
            }

            /*
             * Die eigene Zeichnung des Rahmens.
             *
             * Als Variable am Abschnitt und nicht in der Rahmenregel: die
             * Regel gilt fuer alle Abschnitte mit diesem Rahmen, die
             * Zeichnung gehoert einem. Derselbe Bau wie beim Blatt
             * (--d-sec-blatt).
             *
             * Der Pfad ist durch safeSrc() gegangen und traegt weder
             * Anfuehrungszeichen noch Klammern; er kann aus dem url() nicht
             * ausbrechen.
             */
            $zeichnung = Design::safeSrc((string) ($einstellung['frameSrc'] ?? ''));
            if ($zeichnung !== '') {
                $regeln .= "--d-sec-frame:url('" . $zeichnung . "');";
            }

            // Dasselbe fuer die Bildform "eigen".
            $bildrahmen = Design::safeSrc((string) ($einstellung['photoFrameSrc'] ?? ''));
            if ($bildrahmen !== '') {
                $regeln .= "--d-bild-frame:url('" . $bildrahmen . "');";
            }

            if ($regeln !== '') {
                $css .= $scope . ' .d-sec-' . $abschnitt['id'] . '{' . $regeln . '}';
            }
        }

        /*
         * Die Rahmen - und nur die benutzten.
         *
         * Dieselbe Sammlung wie bei den Varianten eine Schleife weiter: zwei
         * Abschnitte koennen denselben Rahmen tragen, und der Block soll
         * einmal im Stilblock stehen. Er steht inline in JEDER Seite; tote
         * Regeln sind hier keine Datei, die der Browser einmal holt.
         */
        $rahmen = [];
        foreach ($doc['sections'] as $abschnitt) {
            $art = (string) ($abschnitt['settings']['frame'] ?? 'keine');
            if ($art !== 'keine') {
                $rahmen[$art] = true;
            }
        }
        foreach (array_keys($rahmen) as $art) {
            $css .= self::rahmenCss($art, $scope);
        }

        /*
         * Die eigenen Zeichen der Vorlage.
         *
         * Eine Regel je Kennung, die etwas zu sagen hat. Sie steht im
         * Stilblock und nicht am Knoten, weil sie der VORLAGE gehoert -
         * welche Zeichen vorkommen, sagt dagegen die Einladung, und deshalb
         * steht die Zeichnung selbst inline (siehe zeichen()).
         *
         * em und nicht rem: ein Zeichen steht neben einer Zeile und soll mit
         * ihr wachsen. Genau das war die Bitte - "yazinin font boyutunu
         * buyuttugumde gorsel de yaziyla beraber dogru konumda hareket
         * etmeli. Sabit koordinatta kalip tasarim bozulmamali."
         *
         * Die Grundgroesse steht in der Grundregel (1.15em); "size" ist ein
         * Faktor darauf, damit 100 heisst "wie bisher".
         */
        foreach ($doc['icons'] ?? [] as $kennung => $z) {
            $regeln = '';

            if ((int) $z['size'] !== 100) {
                $regeln .= 'width:calc(1.15em * ' . ($z['size'] / 100) . ');'
                    . 'height:calc(1.15em * ' . ($z['size'] / 100) . ');';
            }
            if ((int) $z['x'] !== 0 || (int) $z['y'] !== 0) {
                $regeln .= 'transform:translate(' . ($z['x'] / 100) . 'em,'
                    . ($z['y'] / 100) . 'em);';
            }
            // Der Abstand zur Zeile daneben. margin-inline, damit er in
            // beiden Richtungen gilt - das Zeichen steht mal links, mal
            // rechts vom Wort, je nach Abschnitt.
            if ((int) $z['gap'] !== 0) {
                $regeln .= 'margin-inline:' . ($z['gap'] / 100) . 'em;';
            }
            /*
             * Die Lage im Stapel. position:relative gehoert dazu: ohne sie
             * greift z-index an einem statischen Knoten gar nicht, und der
             * Grafiker drehte an einer Zahl ohne Wirkung.
             */
            if ((int) $z['z'] !== 0) {
                $regeln .= 'position:relative;z-index:' . (int) $z['z'] . ';';
            }
            // Eine eigene Datei ist ein Bild und keine Maske: sie darf nicht
            // in Textfarbe uebermalt werden.
            if ($z['src'] !== '' || $z['video'] !== '') {
                $regeln .= 'background-color:transparent;object-fit:contain;';
            }

            if ($regeln !== '') {
                $css .= $scope . ' .d-ikon-' . $kennung . '{' . $regeln . '}';
            }
        }

        /*
         * Die Grundregel der freien Zeichen am Countdown. Eine einzige, und
         * nur wenn es welche gibt - alles Weitere steht am Knoten selbst
         * (siehe cdEines()).
         *
         * display:inline-block gegen Tailwinds Preflight: das setzt img und
         * video auf display:block, und ein Zeichen NEBEN einer Zahl waere
         * dann eine Zeile darunter. max-width:none aus demselben Grund -
         * Preflight deckelt auf 100% des Kastens, und ein Zeichen darf
         * groesser sein als das Feld, an dem es haengt.
         */
        $freie = ($doc['countdownIcons'] ?? []) !== [];
        foreach ($doc['sections'] as $abschnitt) {
            if (($abschnitt['deko'] ?? []) !== []) {
                $freie = true;
                break;
            }
        }

        if ($freie) {
            $css .= $scope . ' .d-cd-el,' . $scope . ' .d-deko{display:inline-block;'
                . 'vertical-align:middle;height:auto;max-width:none;object-fit:contain;}';
        }

        // Und die Formen der Bilder, nach derselben Regel: nur die
        // benutzten, und jede einmal.
        $formen = [];
        foreach ($doc['sections'] as $abschnitt) {
            $art = (string) ($abschnitt['settings']['photoFrame'] ?? 'keine');
            if ($art !== 'keine') {
                $formen[$art] = true;
            }
        }
        foreach (array_keys($formen) as $art) {
            $css .= self::bildFormCss($art, $scope);
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
    /**
     * Die Angaben einer Textrolle als CSS-Zeilen - ohne die Abstaende.
     *
     * Getrennt von den Abstaenden, weil nicht jede Stelle beide braucht: die
     * Anschrift nimmt Groesse und Schnitt aus der Rolle, ihren Abstand aber
     * aus der Regel fuer Absaetze. Ein Helfer, der beides schriebe, muesste
     * an der Haelfte der Stellen wieder ueberschrieben werden.
     *
     * Die Ersatzwerte sind der heutige Stand. Sie greifen nie - complete()
     * legt die Rollen immer an -, aber ein Stilblock, der ohne seine
     * Variablen zusammenfaellt, ist eine Falle fuer den naechsten, der hier
     * etwas umbaut.
     */
    /**
     * Ein Rahmen um Ueberschrift und Inhalt.
     *
     * Gezeichnet wird auf .d-sec-inner und nicht auf dem Abschnitt: das
     * Polster oben ist 56 % der Breite (damit der Titel zwischen die
     * Goldlinien des Blattes faellt), ein Rahmen um den ganzen Abschnitt
     * begaenne also einen halben Bildschirm ueber der ersten Zeile.
     *
     * Alle Rahmen bringen ihr eigenes Polster mit. Es liegt nicht in einer
     * gemeinsamen Regel darueber, weil die Zahlen verschieden sind: eine
     * Haarlinie braucht weniger Luft als ein Papierkasten, und eine
     * gezeichnete Ranke braucht am meisten - ihre Zierde sitzt ja im Rand.
     *
     * Was hier NICHT steht: "floral". Ein Blumenrahmen aus CSS-Strichen
     * waere ein Versprechen, das keine Zeichnung einloest; wer eine Ranke
     * will, nimmt "eigen" und seine eigene PNG. Genau danach war auch
     * gefragt ("kendi hazirladigim transparent PNG … cerceveleri").
     */
    private static function rahmenCss(string $art, string $scope): string
    {
        $sel = $scope . ' .d-sec-r-' . $art . ' .d-sec-inner';

        return match ($art) {
            // Die schlichte klassische Linie. currentColor und keine Marke:
            // sie soll die Farbe des Abschnitts annehmen, die der Grafiker
            // ohnehin schon gesetzt hat.
            'linie' => $sel . '{border:1px solid currentColor;padding:2.4rem 1.6rem;}',

            // Zwei Linien. Die innere liegt als Auflage darauf, nicht als
            // zweiter Rand: ein zweiter Rand braeuchte ein zweites Element
            // oder outline, und outline folgt keinem border-radius.
            'doppel' => $sel . '{border:1px solid currentColor;padding:2.6rem 1.8rem;}'
                . $sel . '::after{content:"";position:absolute;inset:5px;'
                . 'border:1px solid currentColor;opacity:0.55;pointer-events:none;}',

            /*
             * Vier Ecken statt eines geschlossenen Rahmens - die Form, die
             * auf gedruckter Hochzeitspapeterie am haeufigsten vorkommt.
             *
             * Zwei Pseudoelemente, jedes fuer zwei Ecken: ein flacher
             * Kasten mit drei Raendern zeichnet oben links und oben rechts
             * gleichzeitig, ohne dass die Waagerechte durchlaeuft.
             */
            'gold' => $sel . '{padding:2.6rem 1.8rem;}'
                . $sel . '::before,' . $sel . '::after{content:"";position:absolute;'
                . 'left:0;right:0;height:1.1rem;pointer-events:none;'
                . 'border-left:1px solid var(--d-accent,currentColor);'
                . 'border-right:1px solid var(--d-accent,currentColor);}'
                . $sel . '::before{top:0;border-top:1px solid var(--d-accent,currentColor);}'
                . $sel . '::after{bottom:0;border-bottom:1px solid var(--d-accent,currentColor);}',

            /*
             * Ein Blatt Papier, das auf dem Blatt liegt.
             *
             * Der Schatten ist weich und weit unten: eine Karte liegt auf,
             * sie schwebt nicht. Die Kante ist die Papierkante des Themas -
             * dieselbe Marke, die auch das Kuvert benutzt.
             */
            'papier' => $sel . '{background:var(--d-paper,#faf7f2);'
                . 'border:1px solid var(--d-paperedge,rgba(0,0,0,0.10));'
                . 'box-shadow:0 18px 40px -26px rgba(0,0,0,0.5);'
                . 'padding:3rem 2rem;}',

            /*
             * Dieselbe Karte, nur durchscheinend - fuer Vorlagen, deren
             * Blatt eine Zeichnung traegt, die man nicht zudecken will.
             *
             * color-mix und kein rgba mit fester Farbe: der Grund muss die
             * Papierfarbe DIESER Vorlage sein, und die steht in einer
             * Variablen. Browser ohne color-mix bekommen die Zeile davor
             * und damit deckendes Papier - eine lesbare Karte ist der
             * bessere Ausfall.
             */
            'transparent' => $sel . '{background:var(--d-paper,#faf7f2);'
                . 'background:color-mix(in srgb, var(--d-paper,#faf7f2) 62%, transparent);'
                . 'border:1px solid color-mix(in srgb, currentColor 28%, transparent);'
                . 'padding:3rem 2rem;}',

            /*
             * Die eigene Zeichnung.
             *
             * Als Hintergrundbild und nicht als border-image: ein
             * border-image will eine Neunerteilung, und wie breit der Rand
             * einer fremden PNG ist, weiss nur die, die sie gezeichnet hat.
             * Ein gedehnter Hintergrund ist vorhersagbar - der Grafiker
             * sieht sofort, was passiert, und kann seine Datei danach
             * bauen.
             *
             * Ohne Datei bleibt nur das Polster stehen. Kein Kasten, keine
             * Linie: ein Rahmen, der ein leeres Rechteck malt, waere
             * schlimmer als keiner.
             */
            'eigen' => $sel . '{background-image:var(--d-sec-frame,none);'
                . 'background-size:100% 100%;background-repeat:no-repeat;'
                . 'background-position:center;padding:3.2rem 2.4rem;}',

            default => '',
        };
    }

    /**
     * Die Form eines Fotos.
     *
     * "Fotograflar her zaman normal dikdortgen resim olarak gosterilmemeli."
     * Sieben Antworten darauf - und die Entscheidung gehoert der VORLAGE,
     * nicht dem Paar: wer seine Bilder einzeln in Rahmen steckt, baut keine
     * Einladung mehr, sondern eine Collage.
     *
     * Gezeichnet wird auf .d-bild (dem Kasten) und am Bild darin, je
     * nachdem, was die Form braucht. Ein Polaroid ist ein Rand um das Bild,
     * ein Oval ist eine Eigenschaft des Bildes selbst.
     */
    private static function bildFormCss(string $art, string $scope): string
    {
        /*
         * Die Klasse der Form im Selektor - sonst faerbt eine Galerie die
         * andere um. Eine Einladung kann mehrere tragen (eine Reihe
         * Polaroids oben, ein rundes Portrait unten), und ohne diese
         * Einschraenkung bekaeme jede alles.
         */
        $sel    = $scope . ' .d-sec-pf-' . $art;
        $kasten = $sel . ' .d-sec-bilder .d-bild';
        $bild   = $sel . ' .d-sec-bilder .d-bild img';

        return match ($art) {
            /*
             * Das Polaroid: weisser Rand, unten breiter - dort stand auf dem
             * Original die Beschriftung. Der Fuss ist der ganze Punkt; ohne
             * ihn ist es ein Bild mit weissem Rand und kein Polaroid.
             *
             * Die leichte Drehung wechselt die Richtung von Bild zu Bild:
             * alle gleich schief sahen aus wie ein Fehler im Raster, und
             * abwechselnd sieht es aus wie hingelegt. nth-child und kein
             * Zufall - ein zufaelliger Winkel spraenge bei jedem Neuladen.
             */
            'polaroid' => $kasten . '{background:var(--d-paper,#faf7f2);'
                . 'padding:0.7rem 0.7rem 2.4rem;'
                . 'box-shadow:0 14px 30px -20px rgba(0,0,0,0.55);'
                . 'transform:rotate(-1.4deg);}'
                . $kasten . ':nth-child(even){transform:rotate(1.6deg);}',

            // Ein Haarstrich in der Akzentfarbe, mit etwas Luft zum Bild -
            // ohne die Luft liest sich der Strich als Kante des Fotos.
            'gold' => $kasten . '{padding:0.4rem;'
                . 'border:1px solid var(--d-accent,currentColor);}',

            // Papierfoto: weisser Rand ringsum, gleichmaessig, mit Schatten.
            'papier' => $kasten . '{background:var(--d-paper,#faf7f2);padding:0.6rem;'
                . 'box-shadow:0 12px 26px -20px rgba(0,0,0,0.5);}',

            /*
             * Oval und rund. Die Form gehoert dem BILD und nicht dem Kasten:
             * ein runder Kasten mit einem eckigen Bild darin waere ein
             * Quadrat mit runden Ecken davor.
             *
             * Das Oval ist hochkant (3:4) - ein Portrait im Oval ist die
             * Form, die auf Papeterie vorkommt; ein liegendes Oval sieht aus
             * wie ein zerdrueckter Kreis.
             */
            'oval' => $bild . '{border-radius:50%;aspect-ratio:3/4;}',
            'rund' => $bild . '{border-radius:9999px;aspect-ratio:1;}',

            /*
             * Die eigene Zeichnung, als Auflage UEBER dem Bild.
             *
             * Deshalb ::after und kein Hintergrund: eine Rahmen-PNG hat eine
             * durchsichtige Mitte, und hinter dem Foto waere davon nichts zu
             * sehen. Sie faengt keinen Finger ab - darunter liegt nichts zum
             * Antippen, aber ein Kasten, der Ereignisse schluckt, ist eine
             * Falle fuer den naechsten, der hier etwas anklickbar macht.
             *
             * Ohne Datei erscheint nichts: none als Ersatzwert, und ein
             * ::after ohne Bild zeichnet ein leeres Rechteck - deshalb
             * bleibt es bei background-image und keiner Kante.
             */
            'eigen' => $kasten . '::after{content:"";position:absolute;inset:0;'
                . 'background-image:var(--d-bild-frame,none);'
                . 'background-size:100% 100%;background-repeat:no-repeat;'
                . 'pointer-events:none;}',

            default => '',
        };
    }

    /**
     * Ein Zeichen - entweder die Zeichnung des Hauses oder die der Vorlage.
     *
     * Bis hierher stand an drei Stellen dieselbe Zeile: eine Maske ueber
     * einer Flaeche in currentColor. Das ist richtig fuer eine einfarbige
     * SVG - und falsch fuer eine PNG des Grafikers: eine Maske macht aus
     * seiner Torte einen Farbfleck in Textfarbe.
     *
     * Deshalb zwei Wege:
     *
     *   Katalogzeichen  <span> mit Maske, wie bisher, faerbbar
     *   Eigene Datei    <img> oder <video>, so wie sie ist
     *
     * Die Kennung wandert als Klasse mit (d-ikon-pasta), damit der
     * Stilblock der Vorlage Groesse und Verschiebung daran haengen kann -
     * gerechnet wird dort, nicht hier: welche Zeichen vorkommen, sagt die
     * EINLADUNG, den Stilblock schreibt die VORLAGE.
     *
     * @param array<string,mixed> $doc
     */
    private static function zeichen(array $doc, string $kennung): string
    {
        if ($kennung === '') {
            return '';
        }

        $eigen = is_array($doc['icons'][$kennung] ?? null) ? $doc['icons'][$kennung] : [];
        $klasse = 'd-ikon d-ikon-' . e($kennung);

        /*
         * Der Film gewinnt gegen das Bild - dieselbe Rangfolge wie bei der
         * Karte: wer einen hochlaedt, hat sich fuer ihn entschieden.
         *
         * autoplay, wie bei der Karte und anders als bei den Ebenen: ein
         * Zeichen steht mitten in den Abschnitten, weit unter dem Kuvert.
         * Wer es sieht, hat die Einladung geoeffnet.
         */
        $film = (string) ($eigen['video'] ?? '');
        if ($film !== '') {
            return '<video class="' . $klasse . '" src="' . e($film) . '"'
                . ' autoplay muted loop playsinline preload="metadata" aria-hidden="true"></video>';
        }

        $bild = (string) ($eigen['src'] ?? '');
        if ($bild !== '') {
            return '<img class="' . $klasse . '" src="' . e($bild) . '" alt="" aria-hidden="true">';
        }

        /*
         * Die Zeichnung des Hauses. Der Pfad steht inline und nicht im
         * Stilblock: welche Zeichen vorkommen, sagt die Einladung.
         *
         * Er ist durch iconFile() gegangen und stammt damit aus dem
         * Katalog - aus einer Einladung gelangt nie ein Pfad in die Seite.
         */
        $datei = SectionRegistry::iconFile($kennung);
        if ($datei === '') {
            return '';
        }

        $maske = "url('" . $datei . "')";

        return '<span class="' . $klasse . '" style="-webkit-mask-image:'
            . $maske . ';mask-image:' . $maske . '"></span>';
    }

    private static function typoText(string $rolle, string $groesse, string $hoehe = '1.7'): string
    {
        return 'font-family:var(--dt-' . $rolle . '-font,inherit);'
            . 'color:var(--dt-' . $rolle . '-color,inherit);'
            . 'font-size:var(--dt-' . $rolle . '-size,' . $groesse . ');'
            . 'font-weight:var(--dt-' . $rolle . '-weight,400);'
            . 'letter-spacing:var(--dt-' . $rolle . '-track,0);'
            . 'line-height:var(--dt-' . $rolle . '-line,' . $hoehe . ');'
            . 'text-transform:var(--dt-' . $rolle . '-caps,none);';
    }

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
        // position:relative, damit der Schmuckkasten darin sitzt. Nicht
        // container-type: das machte die Flaeche zum Bezugspunkt fuer
        // position:fixed, und der Stummschalter der Musik haengt daran.
        return $scope . '.d-sec-flaeche{position:relative;'
            . 'background-color:var(--d-paper,#faf7f2);color:var(--d-fg,#14110f);'
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
            /*
             * Groesse und Sitz des grossen Blattes stehen in Variablen, weil
             * die Vorlage sie waehlt (sectionsBgFit): ein PAPIER sitzt in der
             * Breite der Karte und wiederholt sich nach unten, ein BILD
             * fuellt die Flaeche von Kante zu Kante.
             *
             * Die Ersatzwerte sind der bisherige Stand - eine Vorlage ohne
             * die Angabe sieht aus wie vorher. Der Schluss (die erste
             * Schicht) bleibt fest: er ist ein Strauss am Fuss der Seite und
             * soll sich nie strecken.
             */
            . 'background-position:bottom center,var(--d-sec-blatt-pos,top center);'
            . 'background-size:min(100%,42rem) auto,var(--d-sec-blatt-size,min(100%,42rem) 100%);'
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
            /*
             * Der Fliesstext kommt aus der Rolle "body".
             *
             * Hier stand line-height:1.7 als feste Zahl, und die Groesse gar
             * nicht - die Abschnitte erbten 1rem vom Dokument. Beides gehoert
             * jetzt der Vorlage (Design::TYPO). Die Voreinstellung ist
             * genau dieser Stand, ein Dokument ohne eigene Rollen sieht also
             * aus wie vorher.
             *
             * Die Schriftmarke des ABSCHNITTS schlaegt das weiterhin: ihre
             * Regel (.d-sec-<kennung>, aus css()) hat dieselbe Genauigkeit
             * und steht spaeter im Block. Das ist die richtige Rangfolge -
             * die Rolle sagt, wie Fliesstext ueberall aussieht, der
             * Abschnitt darf ausscheren.
             */
            . $scope . ' .d-sec{position:relative;'
            . 'padding:56% 14% 12%;margin-top:0;text-align:center;'
            . 'font-family:var(--dt-body-font,inherit);color:var(--dt-body-color,inherit);'
            . 'font-size:var(--dt-body-size,1rem);font-weight:var(--dt-body-weight,400);'
            . 'letter-spacing:var(--dt-body-track,0);line-height:var(--dt-body-line,1.7);'
            . 'text-transform:var(--dt-body-caps,none);}'
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
            /*
             * Der Kasten um Ueberschrift und Inhalt. Er steht in JEDEM
             * Abschnitt, auch ohne Rahmen - dann traegt er nur diese eine
             * Zeile, und die braucht er ohnehin: die Rahmen legen ihre
             * Ecken und ihre zweite Linie als Pseudoelemente hinein, und
             * die brauchen einen Bezugspunkt.
             */
            . $scope . ' .d-sec-inner{position:relative;}'
            /*
             * Der Schmuckkasten des Bereichs.
             *
             * inset:0 und overflow:hidden: eine Ranke, die ueber die Kante
             * haengt, soll die Seite nicht breiter machen - auf dem Telefon
             * waere das ein waagerechter Scrollbalken quer durch die
             * Einladung.
             *
             * pointer-events:none, weil darunter ein Antwortformular liegt:
             * eine durchsichtige Ecke einer PNG darf keinen Fingertipp
             * schlucken, der einem Eingabefeld galt.
             *
             * container-type:inline-size ist der Bezug fuer die
             * cqw-Angaben der Ebenen (Design::css). Es steht hier und nicht
             * an der Flaeche: es macht einen Kasten zum Bezugspunkt fuer
             * position:fixed, und der Stummschalter der Musik ist fixed.
             */
            . $scope . ' .d-sec-deko{position:absolute;inset:0;z-index:0;'
              . 'overflow:hidden;pointer-events:none;container-type:inline-size;}'
            // Der Text darueber. Ohne diese Zeile lieferte der Schmuck sich
            // mit ihm ein Rennen um die Stapelreihenfolge.
            . $scope . ' .d-sections{position:relative;z-index:1;}'
            // Die Ueberschriften in der Auszeichnungsschrift und im Akzent -
            // dieselben zwei Marken, die auf der Karte den Ton angeben.
            /*
             * Die Ueberschrift kommt aus der Rolle "title".
             *
             * Die Verweise auf df-display und d-accent stehen nicht mehr
             * hier, sondern in der Rolle: WELCHE Marke die Ueberschrift
             * traegt, ist eine Entscheidung der Vorlage. Die Voreinstellung
             * der Rolle ist dieselbe Wahl wie bisher.
             */
            . $scope . ' .d-sec-title{'
            . 'font-family:var(--dt-title-font,inherit);color:var(--dt-title-color,inherit);'
            . 'font-size:var(--dt-title-size,1.5rem);font-weight:var(--dt-title-weight,400);'
            . 'letter-spacing:var(--dt-title-track,0.16em);line-height:var(--dt-title-line,1.3);'
            . 'text-transform:var(--dt-title-caps,uppercase);'
            . 'margin-top:var(--dt-title-above,0);margin-bottom:var(--dt-title-below,1.5rem);}'
            . $scope . ' .d-sec p{margin-bottom:var(--dt-body-below,0.5rem);}'
            . $scope . ' .d-sec-days{display:block;margin-bottom:0.25rem;}'
            /*
             * Das Zeichen fuer die Bilder. Gesperrt gesetzt, weil es
             * abgeschrieben wird und nicht gelesen - dieselbe Ueberlegung wie
             * bei der Kontonummer eine Zeile weiter.
             */
            /*
             * Ein Zeichen neben dem Saalnamen bekommt Luft.
             *
             * Ohne diese Zeile klebt die hochgeladene Zeichnung am ersten
             * Buchstaben - die Geometrie der Vorlage faengt bei null an, und
             * null ist hier die falsche Voreinstellung: in einer Zeile des
             * Ablaufs sitzt das Zeichen in einem eigenen Kaestchen, am Ort
             * steht es mitten im Text.
             *
             * Traegt die Vorlage einen eigenen Abstand ein, gewinnt er - die
             * Regel dafuer steht weiter unten und damit spaeter.
             */
            . $scope . ' .d-sec-venue .d-ikon{margin-inline-end:0.35em;vertical-align:-0.08em;}'
            . $scope . ' .d-sec-hashtag{margin-top:1.2rem;'
            . self::typoText('small', '0.9rem')
            // Die eigene Sperrung steht NACH der Rolle: sie ist der Grund,
            // warum das Zeichen ueberhaupt anders aussieht als eine
            // Anschrift, und darf von ihr nicht eingeebnet werden.
            . 'letter-spacing:0.1em;}'
            /*
             * Der Hinweis auf uns. Leise, und mit Absicht leiser als alles
             * andere: er steht auf der Einladung eines fremden Paares, und
             * ein Absender, der lauter ist als das Brautpaar, wird beim
             * naechsten Mal nicht wieder gebucht.
             *
             * Unterstrichen ist er nicht - Preflight nimmt den Strich
             * ohnehin, und auf einer typografierten Einladung liest sich ein
             * unterstrichener Link wie ein Fremdkoerper (dieselbe
             * Ueberlegung wie beim Weg zur Route in ort()). Erkennbar wird
             * er ueber die Akzentfarbe und die Sperrung.
             */
            . $scope . ' .d-sec-credit{margin-top:2.4rem;font-size:0.66rem;'
                . 'letter-spacing:0.22em;text-transform:uppercase;opacity:0.62;}'
            . $scope . ' .d-sec-credit a{color:inherit;text-decoration:none;}'
            /*
             * Die Kontonummer. tabular-nums, damit die Vierergruppen
             * untereinander stehen, wenn sie umbrechen; break-all, weil eine
             * IBAN auf einem schmalen Telefon sonst aus dem Blatt laeuft -
             * und eine halbe Kontonummer ist keine.
             */
            . $scope . ' .d-sec-konto{margin-top:1.2rem;display:grid;gap:0.25rem;}'
            . $scope . ' .d-sec-inhaber{' . self::typoText('small', '0.86rem') . 'opacity:0.8;}'
            . $scope . ' .d-sec-iban{font-variant-numeric:tabular-nums;letter-spacing:0.08em;'
            . 'word-break:break-all;}'
            /*
             * Der Spieler ist der eingebaute des Browsers - er laesst sich
             * kaum faerben, und das ist in Ordnung: er soll aussehen wie ein
             * Spieler und nicht wie Schmuck.
             */
            /*
             * Der Stummschalter der Hintergrundmusik.
             *
             * Fest am Fensterrand und nicht im Abschnitt: der Abschnitt
             * selbst hat nichts zu zeigen (das Lied laeuft ja schon), und ein
             * Knopf, der beim Weiterblaettern aus dem Bild rutscht, ist genau
             * dann weg, wenn jemand ihn sucht - naemlich mitten in der
             * Einladung.
             *
             * position:fixed schlaegt hier nicht fehl: er sitzt im
             * Abschnittsbereich, und der traegt keine transform. Die Buehne
             * darueber schon - deshalb steht der Ton nicht dort.
             */
            . $scope . ' .d-sec-ton-knopf{position:fixed;right:1.25rem;bottom:1.25rem;z-index:40;'
              . 'display:flex;align-items:center;justify-content:center;width:3rem;height:3rem;'
              . 'border:0;border-radius:9999px;cursor:pointer;font:inherit;font-size:1.1rem;'
              . 'line-height:1;background:var(--d-accent,#b08d57);color:var(--d-paper,#faf7f2);'
              . 'box-shadow:0 2px 10px rgba(0,0,0,0.28);}'
            /*
             * Die Bilder. Quadratisch beschnitten und nicht in ihrem eigenen
             * Format: acht Bilder aus acht Kameras haben acht Seitenverhaeltnisse,
             * und untereinander gestellt ergibt das eine Treppe. Ein Raster
             * verlangt eine gemeinsame Form.
             */
            . $scope . ' .d-sec-bilder{display:grid;grid-template-columns:repeat(2,1fr);'
            . 'gap:0.5rem;margin-top:1.2rem;}'
            . $scope . ' .d-sec-bilder .d-bild{display:block;position:relative;}'
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
            /*
             * Der Knopf des Formulars traegt jetzt die Rolle "button" -
             * dieselbe wie "Route planen". Bis hierher stand hier
             * font:inherit, also Fliesstext: zwei Knoepfe auf derselben
             * Einladung, die verschieden aussahen, ohne dass es dafuer einen
             * Grund gab.
             *
             * font:inherit bleibt stehen und kommt ZUERST: die Kurzform
             * setzt Groesse, Schnitt und Familie zurueck, und was nach ihr
             * steht, gilt. Ohne sie brauchte der Knopf die Angaben, die
             * Preflight ihm nimmt, einzeln zurueck.
             */
            . $scope . ' .d-sec-form button{justify-self:center;border:1px solid currentColor;'
            . 'background:transparent;padding:0.55rem 1.5rem;cursor:pointer;font:inherit;'
            . self::typoText('button', '0.72rem')
            . 'margin-top:var(--dt-button-above,0.5rem);}'

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
            /*
             * Die Farbpalette - in JEDER Gestalt und deshalb hier.
             *
             * Kreise und keine Quadrate: eine Farbprobe auf Papeterie ist
             * rund, und ein Quadrat liest sich wie ein Schalter. Der duenne
             * Rand traegt die Schriftfarbe mit wenig Deckkraft - ohne ihn
             * verschwindet ein cremefarbener Kreis auf cremefarbenem Papier
             * vollstaendig, und genau solche Toene stehen auf einer
             * Hochzeitseinladung.
             */
            . $scope . ' .d-sec-dresscode .d-dress-farben{display:flex;flex-wrap:wrap;'
              . 'justify-content:center;gap:0.6rem;margin-top:1.2rem;}'
            . $scope . ' .d-sec-dresscode .d-dress-kreis{display:block;'
              . 'width:1.6rem;height:1.6rem;border-radius:9999px;'
              . 'border:1px solid color-mix(in srgb, currentColor 22%, transparent);}'
            // Damen und Herren stehen auch ohne die Gestalt "nebeneinander"
            // da - dann untereinander, mit etwas Luft.
            . $scope . ' .d-sec-dresscode .d-dress-paar{margin-top:1.1rem;}'
            . $scope . ' .d-sec-dresscode .d-dress-paar p{margin-bottom:0.35rem;}'
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

            /*
             * Das Kartenbild.
             *
             * Es steht zwischen Adresse und Routenknopf und gehoert zur
             * Grundgestalt, nicht zu einer Variante: eine Karte, die nur in
             * "Grosser Name" erschiene, waere eine Falle - der Grafiker
             * schaltet sie ein und sieht nichts.
             *
             * Feste Hoehe und object-fit:cover, damit die Zeile beim Laden
             * nicht springt: das Bild kommt vom eigenen Server, aber es
             * kommt spaeter als der Text darum herum.
             *
             * Der Rahmen ist die Papierfarbe und kein Grau: das Bild ist ein
             * Fremdkoerper aus einer anderen Welt, und die duenne helle
             * Kante legt es auf das Blatt, statt es hineinzustanzen.
             */
            /*
             * Die Karte im Blatt.
             *
             * Ein Kartenausschnitt ist ein Fremdkoerper: geradkantig,
             * bunt, aus einer anderen Welt als das Papier darum herum. Die
             * Blattform nimmt ihm die Ecken - zwei spitze, zwei runde, wie
             * die Ranken, die auf diesen Vorlagen ohnehin an den Raendern
             * stehen.
             *
             * Nur eine Linie, und die in der Akzentfarbe. Hier stand kurz
             * ein zweiter Ring in der Papierfarbe, der das Blatt auf den
             * Untergrund setzen sollte. Auf "bild" war das Papier aber eine
             * Struktur und der Ring eine glatte Flaeche in #12100E - statt
             * einer Fassung sah man einen flachen Fleck um das Blatt. Ein
             * Untergrund mit Korn vertraegt keine einfarbige Umrandung in
             * seiner eigenen Farbe.
             *
             * 4:3 und nicht 2:1: eine Blattform braucht Hoehe, sonst kappen
             * die runden Ecken den halben Ausschnitt weg.
             */
            . $scope . ' .d-sec-map-bild{display:block;margin:1.4rem auto 0;'
              . 'max-width:min(100%,22rem);line-height:0;overflow:hidden;}'
            /*
             * Vier Groessen. Die mittlere ist der bisherige Stand (22rem) -
             * eine Vorlage, die nichts sagt, sieht aus wie vorher.
             */
            . $scope . ' .d-sec-map-gr-s{max-width:min(100%,14rem);}'
            . $scope . ' .d-sec-map-gr-m{max-width:min(100%,22rem);}'
            . $scope . ' .d-sec-map-gr-l{max-width:min(100%,32rem);}'
            . $scope . ' .d-sec-map-gr-voll{max-width:100%;}'
            // Der Film sitzt wie das Bild. Ohne diese Zeile stuende er in
            // seiner eigenen Groesse mitten im Kasten.
            . $scope . ' .d-sec-map-bild video{display:block;width:100%;height:auto;'
            . 'aspect-ratio:4 / 3;object-fit:cover;}'
            . $scope . ' .d-sec-map-bild img{display:block;width:100%;height:auto;'
              . 'aspect-ratio:4 / 3;object-fit:cover;}'
            . $scope . ' .d-sec-map-blatt{border-radius:58% 0 58% 0;'
              . 'border:1px solid var(--d-accent,currentColor);}'
            /*
             * Das Rechteck bleibt, was es war: eine Karte mit einem
             * Passepartout. Wer eine strenge Vorlage baut, will keine
             * Blattform darin.
             */
            . $scope . ' .d-sec-map-rechteck{border:6px solid var(--d-paper,#faf7f2);'
              . 'box-shadow:0 1px 0 rgba(0,0,0,0.18);}'

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
                . $sel . ' .d-sec-plan .d-plan-text{opacity:0.8;}'

                /*
                 * Die beiden Enden des Fadens.
                 *
                 * Der Faden haengt an JEDER Zeile und reicht 2.2rem ueber sie
                 * hinaus, nach oben wie nach unten. In der Mitte ist das genau
                 * richtig - so stossen die Stuecke auf den Zeilenkanten
                 * aneinander und ergeben eine durchgehende Linie. An den Enden
                 * ragt er ins Leere: oben ueber die erste Uhrzeit hinaus,
                 * unten ein gutes Stueck unter die letzte Zeile. Auf dem
                 * Telefon war das ein Strich, der neben der Weinflasche
                 * auslief.
                 *
                 * Also endet er in den Ringen, zwischen denen er gespannt ist:
                 * --d-plan-kopf ist die erste Titelzeile, die halbe Hoehe also
                 * die Mitte des Rings. Dieselbe Zahl, an der auch der Ring
                 * selbst haengt - keine zweite erfunden.
                 *
                 * Bei einer einzigen Zeile treffen beide Regeln dasselbe <dt>
                 * und der Faden wird null hoch. Das ist richtig so: ein
                 * einzelner Moment braucht keinen Faden zu irgendwohin.
                 */
                . $sel . ' .d-sec-plan dt:first-of-type::after{top:calc(var(--d-plan-kopf) / 2);}'
                . $sel . ' .d-sec-plan dt:last-of-type::after{bottom:calc(100% - var(--d-plan-kopf) / 2);}'

                /*
                 * Und er zeichnet sich, waehrend gelesen wird.
                 *
                 * "Birde boyle cizgi olusa uzadikca olurmu" - und dazu "once
                 * semboller gelse". Der Strahl stand bisher fertig da, bevor
                 * die erste Zeile gelesen war.
                 *
                 * Kein neues Skript: invitation.js sucht ohnehin jedes .iv und
                 * schreibt data-visible="true", sobald es im Bild steht. Die
                 * Klasse setzt programm(), die Reihenfolge steht hier.
                 *
                 * Erst die Verschiebung abstellen. Die Grundregel von .iv
                 * schiebt um 30px nach unten - fuer einen Absatz richtig, hier
                 * falsch: an jedem <dt> haengt ein Stueck des Fadens, und die
                 * Stuecke stossen auf den Zeilenkanten aneinander. Schoebe
                 * jede Zeile fuer sich, waere der Faden waehrend des Scrollens
                 * eine Leiter mit versetzten Sprossen. <dd> steht mit in der
                 * Regel, obwohl dort kein Faden haengt: Uhrzeit und Satz einer
                 * Zeile duerfen nicht verschieden weit wandern.
                 */
                . $sel . ' .d-sec-plan dt.iv,' . $sel . ' .d-sec-plan dd.iv{transform:none;}'

                // Das Zeichen zuerst - es setzt sich, sobald die Zeile im Bild
                // steht. Ohne eigene Verzoegerung: es IST der Anfang.
                . $sel . ' .d-sec-plan dt.iv .d-plan-rozet{transform:scale(0.6);'
                . 'transition:transform 0.5s cubic-bezier(.16,1,.3,1);}'
                . $sel . ' .d-sec-plan dt.iv[data-visible=true] .d-plan-rozet{transform:none;}'

                /*
                 * Dann der Faden. transform-origin:top ist der ganze
                 * Unterschied zwischen "er zieht sich zur naechsten Zeile" und
                 * "er waechst aus seiner Mitte nach beiden Seiten
                 * auseinander".
                 *
                 * Die 0.18s sind die Antwort auf "once semboller gelse": der
                 * Faden setzt an, wenn der Ring steht.
                 */
                . $sel . ' .d-sec-plan dt.iv::after{transform:scaleY(0);transform-origin:top;'
                . 'transition:transform 0.55s cubic-bezier(.16,1,.3,1) 0.18s;}'
                . $sel . ' .d-sec-plan dt.iv[data-visible=true]::after{transform:none;}'

                // Und zuletzt das Wort - es wartet Zeichen und Faden ab.
                . $sel . ' .d-sec-plan dd.iv{transition-delay:0.38s;}',

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
            'location/gross' => $sel . ' .d-sec-venue{'
                . self::typoText('subtitle', '1.7rem', '1.2')
                . 'margin-top:var(--dt-subtitle-above,0);'
                . 'margin-bottom:var(--dt-subtitle-below,0.4rem);}'
                . $sel . ' .d-sec-address{' . self::typoText('small', '0.86rem') . 'opacity:0.75;}'
                . $sel . ' .d-sec-map{display:inline-block;'
                . 'border:1px solid currentColor;padding:0.5rem 1.5rem;text-decoration:none;'
                . self::typoText('button', '0.72rem')
                . 'margin-top:var(--dt-button-above,1.2rem);}',

            /*
             * Dieselbe Zahl, nur laut. Nur Groesse, kein Bau: ohne Skript
             * bleibt der Span leer, und dann traegt das gedruckte Datum den
             * Abschnitt allein - das muss auch in dieser Gestalt gelten.
             */
            /*
             * Der Tag, gross gesetzt.
             *
             * Die Groessen stehen NICHT hier, sondern in den Rollen: der Tag
             * traegt "grosse Zahl", Monat und Jahr tragen "kleiner Hinweis"
             * (Design::TYPO). Damit entscheidet die Vorlage, wie gross die
             * 08 wird - und genau das war die Bitte.
             */
            'date/gross' => $sel . ' .d-datum-tag{'
                . self::typoText('number', '3.4rem', '1')
                // Dieselbe Untergrenze und dieselben Versalziffern wie bei
                // der Tageszahl, aus demselben Grund: unter der Zahl steht
                // eine Zeile.
                . 'line-height:max(1,var(--dt-number-line,1));'
                . 'font-variant-numeric:lining-nums;'
                . 'margin-bottom:var(--dt-number-below,0.6rem);}'
                . $sel . ' .d-datum-monat{' . self::typoText('subtitle', '1.7rem', '1.2') . '}'
                . $sel . ' .d-datum-jahr{' . self::typoText('small', '0.86rem') . 'opacity:0.75;}',

            /*
             * Eine Zeile mit Haarstrichen.
             *
             * Die Striche sind Stil und kein Text: ein Bindestrich im Markup
             * wuerde vorgelesen und liesse sich von keiner Vorlage
             * abschalten. Sie sitzen als Rand an den beiden aeusseren
             * Feldern, also wachsen sie mit der Zeile mit.
             */
            'date/zeile' => $sel . ' .d-datum-zeile{display:flex;flex-wrap:wrap;'
                . 'align-items:center;justify-content:center;gap:0.5rem 1.1rem;'
                . self::typoText('subtitle', '1.7rem', '1.2') . '}'
                . $sel . ' .d-datum-zeile span::before,'
                . $sel . ' .d-datum-zeile span::after{content:"";display:none;}'
                . $sel . ' .d-datum-zeile span:first-child::before,'
                . $sel . ' .d-datum-zeile span:last-child::after{display:inline-block;'
                . 'width:2.2rem;height:1px;vertical-align:middle;'
                . 'background:var(--d-accent,currentColor);opacity:0.7;}'
                . $sel . ' .d-datum-zeile span:first-child::before{margin-right:1.1rem;}'
                . $sel . ' .d-datum-zeile span:last-child::after{margin-left:1.1rem;}',

            // Ausgeschrieben: der Wochentag leise darueber, das Datum in der
            // Zeile darunter.
            'date/default' => $sel . ' .d-datum-wochentag{' . self::typoText('small', '0.86rem')
                . 'opacity:0.75;}'
                . $sel . ' .d-datum-lang{' . self::typoText('subtitle', '1.7rem', '1.2') . '}',

            /*
             * Die grosse Tageszahl, der Rest als leise Zeile darunter.
             *
             * Dieselbe Rolle wie die einzelne grosse Zahl - der Grafiker
             * dreht an einem Knopf und nimmt beide Gestalten mit.
             */
            /*
             * Die Zeilenhoehe der Zahl wird hier nach unten begrenzt, und
             * zwar auf 1.
             *
             * Im Browser gemessen, an der Vorlage "Papeterie": sie setzt die
             * grosse Zahl auf Zeilenhoehe 0.92 - eine ganz normale Wahl fuer
             * eine Zahl, die allein steht. Bei 102 px ist die Zeilenbox dann
             * 94 px hoch, die Ziffern aber 108: sie ragen 14 px unter ihre
             * eigene Box. Das Wort darunter begann daraufhin 7 px INNERHALB
             * der Ziffern, trotz seines Abstands.
             *
             * Der Abstand allein kann das nicht loesen - er misst ab der
             * Box, nicht ab den Ziffern, und wie weit sie herausragen,
             * haengt an der Schrift. In DIESER Anordnung steht ein Wort
             * direkt unter der Zahl; dort darf die Box nicht kleiner sein
             * als das, was in ihr steht. Groessere Werte gelten weiter.
             */
            'countdown/tage' => $sel . ' .d-uhr-tage{display:block;}'
                . $sel . ' .d-uhr-tage .d-sec-uhr-zahl{display:block;'
                . self::typoText('number', '3.4rem', '1')
                . 'line-height:max(1,var(--dt-number-line,1));'
                /*
                 * Versalziffern fuer die grosse Zahl.
                 *
                 * Cormorant setzt Ziffern als MEDIAEVALZIFFERN: die 3, die
                 * 7 und die 9 haengen unter die Grundlinie, so wie ein g
                 * oder ein p. In laufendem Text ist das richtig und schoen -
                 * unter einer 100 px grossen Zahl, unter der ein Wort steht,
                 * ist es eine Kollision, die kein Abstand loest: die
                 * Unterlaenge waechst mit der Schriftgroesse mit.
                 *
                 * Gemessen an "Papeterie": 377 bei 102 px ragte 11 px unter
                 * seine eigene Zeilenbox, und "TAGE" begann 7 px INNERHALB
                 * der Ziffern.
                 *
                 * lining-nums stellt die Ziffern auf die Grundlinie. Das ist
                 * hier nicht nur die Loesung, sondern auch das Richtige:
                 * Mediaevalziffern gehoeren in den Fliesstext, Versalziffern
                 * in eine Auszeichnung.
                 */
                . 'font-variant-numeric:lining-nums;'
                . '}'
                /*
                 * Luft zwischen Zahl und Wort. Die Zahl steht auf
                 * Zeilenhoehe 1 (das gehoert zur Rolle "grosse Zahl"), und
                 * ohne diesen Abstand klebte "TAGE" an den Unterlaengen der
                 * Ziffern - im Browser nachgesehen, nicht vermutet.
                 */
                . $sel . ' .d-uhr-tage .d-sec-uhr-wort{display:block;margin-top:0.5rem;'
                . self::typoText('small', '0.86rem')
                . 'text-transform:uppercase;letter-spacing:0.18em;opacity:0.8;}'
                . $sel . ' .d-uhr-rest{display:flex;flex-wrap:wrap;'
                . 'align-items:center;justify-content:center;gap:0.2rem 0.9rem;'
                . 'margin-top:0.9rem;' . self::typoText('small', '0.86rem')
                . 'text-transform:uppercase;letter-spacing:0.12em;opacity:0.7;}'
                // Der Mittelpunkt steht im Stil und nicht im Markup: als
                // Textknoten wuerde er vorgelesen.
                . $sel . ' .d-uhr-teil + .d-uhr-teil::before{content:"·";'
                . 'margin-right:0.9rem;opacity:0.6;}',

            /*
             * Einzelne Kaertchen.
             *
             * auto-fit statt fester Spalten: drei Stationen sollen nicht in
             * ein Dreierraster gezwungen werden, und auf dem Telefon rutscht
             * jede in ihre eigene Zeile, statt dass drei Kaesten auf 320 px
             * zusammengedrueckt werden.
             */
            'program/karten' => $sel . ' .d-sec-plan{display:grid;'
                . 'grid-template-columns:repeat(auto-fit,minmax(11rem,1fr));'
                . 'gap:1rem;justify-content:center;text-align:center;}'
                . $sel . ' .d-plan-karte{display:flex;flex-direction:column;'
                . 'align-items:center;gap:0.35rem;padding:1.4rem 1rem;'
                . 'border:1px solid currentColor;}'
                . $sel . ' .d-plan-karte dt{display:flex;flex-direction:column;'
                . 'align-items:center;gap:0.5rem;margin:0;font-weight:400;}'
                . $sel . ' .d-plan-karte dd{margin:0;}'
                . $sel . ' .d-plan-karte .d-plan-zeit{'
                . self::typoText('small', '0.86rem')
                . 'letter-spacing:0.14em;opacity:0.75;}'
                . $sel . ' .d-plan-karte .d-ikon{width:1.6em;height:1.6em;}',

            /*
             * Damen und Herren nebeneinander - und untereinander, sobald es
             * eng wird. Zwei Spalten auf 320 px waeren zwei Spalten mit je
             * zwei Woertern pro Zeile.
             */
            'dresscode/paar' => $sel . ' .d-dress-paar{display:grid;gap:0.8rem 2.4rem;'
                . 'grid-template-columns:repeat(auto-fit,minmax(9rem,1fr));'
                . 'margin-top:1.1rem;}'
                . $sel . ' .d-dress-paar p{margin:0;}',

            'countdown/gross' => $sel . ' .d-sec-days{'
                . self::typoText('number', '3.4rem', '1')
                . 'margin-top:var(--dt-number-above,0);'
                . 'margin-bottom:var(--dt-number-below,0.6rem);}'
                . $sel . ' .d-sec-countdown{' . self::typoText('small', '0.86rem')
                . 'letter-spacing:0.1em;}',

            /*
             * Die Uhr. Vier Felder in einer Reihe, mit einer festen
             * Ziffernbreite: ohne tabular-nums springt die Zeile bei jedem
             * Sekundenwechsel, weil eine 1 schmaler ist als eine 8.
             *
             * flex-wrap statt fester Spalten - auf einem schmalen Telefon
             * rutschen Minuten und Sekunden in die zweite Zeile, statt dass
             * vier Felder auf 320 px zusammengequetscht werden.
             */
            'countdown/uhr' => $sel . ' .d-sec-uhr{display:flex;flex-wrap:wrap;'
                . 'justify-content:center;gap:0.4rem 1.6rem;}'
                . $sel . ' .d-sec-uhr-feld{display:flex;flex-direction:column;'
                . 'align-items:center;min-width:3.4rem;}'
                /*
                 * Dieselbe Rolle wie die einzelne grosse Zahl, nur kleiner:
                 * vier Zahlen nebeneinander passen sonst auf kein Telefon.
                 * Das Verhaeltnis ist der heutige Stand (2.4 von 3.4) und
                 * haengt an der GESTALT, nicht an einer zweiten Rolle - der
                 * Grafiker soll an einem Knopf drehen und beide Gestalten
                 * mitnehmen.
                 */
                . $sel . ' .d-sec-uhr-zahl{'
                . 'font-family:var(--dt-number-font,inherit);'
                . 'color:var(--dt-number-color,inherit);'
                . 'font-size:calc(var(--dt-number-size,3.4rem) * 0.7059);'
                . 'font-weight:var(--dt-number-weight,400);'
                . 'letter-spacing:var(--dt-number-track,0);'
                . 'line-height:1.1;font-variant-numeric:tabular-nums;'
                . 'font-feature-settings:"tnum";}'
                . $sel . ' .d-sec-uhr-wort{font-size:0.6rem;letter-spacing:0.16em;'
                . 'text-transform:uppercase;opacity:0.7;margin-top:0.2rem;}'
                // Der Abstand wandert mit der Reihenfolge: er trennte das
                // Datum von der Uhr darueber, jetzt von der Uhr darunter.
                . $sel . ' .d-sec-countdown-datum{' . self::typoText('small', '0.86rem')
                . 'letter-spacing:0.1em;margin-bottom:1.1rem;opacity:0.85;}',

            /*
             * Der sichtbare Spieler.
             *
             * Er steht bei seiner Gestalt und nicht in der Grundregel, seit
             * die Voreinstellung der Hintergrund ist: die Grundregel gilt
             * fuer beide Gestalten, und ein <audio>-Kasten, den die eine gar
             * nicht druckt, waere eine Regel ohne Gegenstueck.
             */
            'music/spieler' => $sel . ' .d-sec-ton{display:block;margin:1.2rem auto 0;'
                . 'width:100%;max-width:20rem;}',

            /*
             * Der Platzhalter der Einbettung - und spaeter der Rahmen selbst.
             *
             * Beide teilen sich dasselbe Seitenverhaeltnis, damit im Moment
             * des Klicks nichts springt: der Knopf steht in einem Kasten von
             * 16:9, und der Rahmen, den invitation.js hineinlegt, fuellt genau
             * ihn aus. Ohne das wuechse die Seite unter dem Finger, und was
             * darunter stand, waere weg.
             *
             * Die Farben kommen aus den Marken der Vorlage - ein Kasten in
             * YouTube-Rot mitten auf einer Einladung waere ein Fremdkoerper.
             * Der Hinweis steht klein darunter und nicht im Knopf: er ist
             * eine Auskunft, keine Aufforderung.
             */
            'music/einbetten' => $sel . ' .d-sec-einbettung{display:flex;'
                . 'flex-direction:column;align-items:center;justify-content:center;gap:0.9rem;'
                . 'margin:1.2rem auto 0;width:100%;max-width:32rem;aspect-ratio:16 / 9;'
                . 'border:1px solid currentColor;padding:1.2rem;}'
                . $sel . ' .d-sec-einbettung iframe{display:block;width:100%;height:100%;border:0;}'
                // Traegt der Kasten den Rahmen, faellt sein eigener Rand weg -
                // sonst stuende ein Strich um das Video.
                . $sel . ' .d-sec-einbettung[data-geladen]{border:0;padding:0;display:block;}'
                . $sel . ' .d-sec-einbettung-knopf{cursor:pointer;font:inherit;'
                . 'border:1px solid currentColor;background:transparent;color:inherit;'
                . 'padding:0.6rem 1.6rem;font-size:0.72rem;letter-spacing:0.16em;'
                . 'text-transform:uppercase;}'
                . $sel . ' .d-sec-einbettung-hinweis{font-size:0.72rem;opacity:0.75;'
                . 'max-width:24rem;line-height:1.5;}',

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
                // Der Kasten ist das Flex-Element, nicht mehr das Bild: seit
                // jedes Foto seinen eigenen Kasten hat (fuer die Form), sitzt
                // die Breite dort. Am img gemessen waere jeder Streifen so
                // breit wie sein Inhalt.
                . $sel . ' .d-sec-bilder .d-bild{flex:0 0 68%;scroll-snap-align:center;}'
                . $sel . ' .d-sec-bilder img{aspect-ratio:3/4;}',

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
    /**
     * Das Blatt unter der Karte, auf dem die Abschnitte liegen.
     *
     * Stand bis heute zweimal wortgleich in zwei Vorlagen - design-preview
     * und invite-v2-show - und beide Male dieselbe Suche: erst das eigene
     * Blatt der Vorlage, und wenn es keins gibt, laeuft das Papier der Karte
     * einfach weiter. Die lebende Vorschau im Editor waere die dritte
     * Abschrift gewesen, und drei Abschriften einer Suche laufen
     * auseinander, sobald jemand eine davon anfasst.
     *
     * Leerer Inhalt gibt leeren String: unter einer Karte ohne Abschnitte
     * soll kein leeres Blatt haengen.
     *
     * @param array<string,mixed> $doc
     */
    public static function flaeche(
        array $doc,
        string $scope,
        string $inhalt,
        string $spalte = 'mx-auto max-w-2xl',
        string $locale = 'de'
    ): string {
        if ($inhalt === '') {
            return '';
        }

        $papier = Design::safeSrc((string) ($doc['sectionsBg'] ?? ''));

        foreach ($papier === '' ? (array) ($doc['layers'] ?? []) : [] as $ebene) {
            if (in_array($ebene['type'], ['photo', 'image'], true)
                && $ebene['spot'] === 'card' && (string) $ebene['src'] !== '') {
                $papier = Design::safeSrc((string) $ebene['src']);
                break;
            }
        }

        // Zwei Variablen, eine style-Angabe: der Schluss ist freiwillig.
        $schluss = Design::safeSrc((string) ($doc['sectionsBgEnd'] ?? ''));

        $stil = '';
        if ($papier !== '') {
            $stil .= "--d-sec-blatt:url('" . e($papier) . "');";

            /*
             * Und wie es sitzt. Nur bei "cover" geschrieben: "blatt" IST der
             * Ersatzwert in der Grundregel, und eine zweite Angabe mit
             * demselben Wert waere Rauschen in einem style-Attribut, das auf
             * jeder Einladung steht.
             */
            if ((string) ($doc['sectionsBgFit'] ?? 'blatt') === 'cover') {
                $stil .= '--d-sec-blatt-size:cover;--d-sec-blatt-pos:center;';
            }
        }
        if ($schluss !== '') {
            $stil .= "--d-sec-blatt-end:url('" . e($schluss) . "');";
        }

        /*
         * Zwei Kaesten, nicht einer: die Flaeche geht von Kante zu Kante,
         * damit das Papier der Karte einfach weiterlaeuft; der Text darin
         * bleibt in seiner Spalte.
         */
        /*
         * Der Schmuck des Bereichs - Ranken, Linien, Papierkanten, Filme.
         *
         * In einem EIGENEN Kasten und nicht direkt in der Flaeche, aus zwei
         * Gruenden. Erstens braucht er container-type:inline-size, damit die
         * Ebenen senkrecht in cqw sitzen koennen (siehe Design::css) - und
         * container-type macht einen Kasten zum Bezugspunkt fuer
         * position:fixed. Der Stummschalter der Musik ist fixed und sitzt in
         * der Flaeche; laege er darin, klebte er am Abschnittsbereich statt
         * am Fenster. Zweitens haelt der Kasten den Schmuck mit einem
         * einzigen z-index hinter dem ganzen Text, statt ihn Ebene fuer
         * Ebene einsortieren zu muessen.
         *
         * Ist nichts hinterlegt, steht hier auch nichts - kein leerer Kasten
         * ueber der ganzen Einladung.
         */
        $schmuck = Design::html($doc, [], $locale, 'sections');

        return '<div class="' . e($scope) . ' d-sec-flaeche"'
            . ($stil !== '' ? ' style="' . $stil . '"' : '') . '>'
            . ($schmuck !== '' ? '<div class="d-sec-deko" aria-hidden="true">' . $schmuck . '</div>' : '')
            . '<div class="d-sections ' . e($spalte) . '">' . $inhalt . '</div>'
            . '</div>';
    }

    public static function html(array $doc, array $data, string $locale, string $heute = '', array $form = []): string
    {
        $out = '';

        foreach (self::visible($doc, $data, $heute) as $abschnitt) {
            $id = (string) $abschnitt['id'];
            $typ = (string) $abschnitt['type'];

            // Drei Klassen, drei Fragen: welcher Abschnitt (fuer die eigene
            // Regel des Grafikers), welche Art, welches Aussehen.
            /*
             * Vier Klassen, vier Fragen: welcher Abschnitt (fuer die eigene
             * Regel des Grafikers), welche Art, welches Aussehen - und
             * welcher Rahmen.
             *
             * Der Rahmen steht am Abschnitt und wird INNEN gezeichnet
             * (d-sec-inner). Das muss er: das Polster oben ist 56 % der
             * Breite, damit der Titel zwischen die Goldlinien des Blattes
             * faellt. Ein Rahmen um den ganzen Abschnitt begaenne also einen
             * halben Bildschirm ueber der ersten Zeile.
             */
            $rahmen = (string) ($abschnitt['settings']['frame'] ?? 'keine');

            /*
             * Und dieselbe Zeile fuer die Form der Bilder.
             *
             * Sie muss am Abschnitt haengen, nicht nur im Stilblock stehen -
             * im Browser gemessen und dabei gefunden: ohne diese Klasse
             * trugen die Regeln nur ".d-sec-bilder .d-bild", und damit bekam
             * JEDE Galerie des Dokuments JEDE gewaehlte Form. Vier Galerien
             * mit vier Formen sahen alle vier gleich aus - polaroid, gold,
             * oval und rund uebereinander.
             *
             * Dieselbe Falle wie beim Geltungsbereich zweier Designs auf
             * einer Seite, nur eine Ebene tiefer.
             */
            $bildform = (string) ($abschnitt['settings']['photoFrame'] ?? 'keine');

            $out .= '<section class="d-sec d-sec-' . e($id) . ' d-sec-' . e($typ)
                . ' d-sec-v-' . e((string) $abschnitt['variant'])
                . ($rahmen !== 'keine' ? ' d-sec-r-' . e($rahmen) : '')
                . ($bildform !== 'keine' ? ' d-sec-pf-' . e($bildform) : '') . '">';

            /*
             * Der Kasten um Ueberschrift und Inhalt.
             *
             * Er steht IMMER da, auch ohne Rahmen: ein Kasten, den es nur
             * manchmal gibt, waere ein zweiter Bauplan, und jede Regel, die
             * ihn erwaehnt, muesste beide Faelle kennen. Ohne Rahmen ist er
             * ein <div> ohne eine einzige Eigenschaft und kostet nichts.
             */
            $out .= '<div class="d-sec-inner">';

            // Explizit auf '' pruefen, nicht mit ?? verketten: complete()
            // schreibt beide Sprachen immer als String, also feuert ?? nie -
            // ein leeres "en" ergaebe sonst gar keinen Titel statt des
            // deutschen.
            $titel = (string) ($abschnitt['title'][$locale] ?? '');
            if ($titel === '') {
                $titel = (string) ($abschnitt['title']['de'] ?? '');
            }
            /*
             * Der Schmuck des Abschnitts - vor und nach dem, woran er haengt.
             *
             * Er steht IM Fluss wie die Zeichen am Countdown: ein Bild neben
             * einer Ueberschrift soll mitrutschen, wenn die Ueberschrift
             * laenger wird. Wer es woanders haben will, verschiebt es mit x
             * und y - eine Verschiebung haelt auch dann, wenn die Schrift
             * waechst, eine feste Koordinate nicht.
             */
            $deko = self::dekoZeichen($abschnitt);

            if ($titel !== '') {
                $out .= $deko['vor']['titel']
                    . '<h2 class="d-sec-title">' . e($titel) . '</h2>'
                    . $deko['nach']['titel'];
            }

            $out .= $deko['vor']['inhalt'];

            $out .= match ($typ) {
                'location'  => self::ort($doc, $data, $locale, $abschnitt['settings']),
                'countdown' => self::countdown($doc, $data, $locale, (string) $abschnitt['variant']),
                'date'      => self::datum($data, $locale, (string) $abschnitt['variant']),
                'family'    => self::familien($data),
                'program'   => self::programm($doc, $data, $locale, (string) $abschnitt['variant']),
                'rsvp'      => self::formular($form, $locale),
                'text'      => self::freitext($abschnitt, $data),
                'footer'    => self::schluss($abschnitt, $data, $locale),
                'gift'      => self::geschenk($abschnitt, $data),
                // Als einzige Art liest sie ihre Einstellung und nicht die
                // Daten des Paares - der Klang gehoert der Vorlage.
                'music'     => self::musik($abschnitt, $data, $locale),
                'gallery'   => self::galerie($data, $id),
                'menu'      => self::speisekarte($doc, $abschnitt, $data, $locale),
                'dresscode' => self::kleiderordnung($doc, $abschnitt, $data),
                default     => '',
            };

            $out .= $deko['nach']['inhalt'] . '</div></section>';
        }

        return $out;
    }

    /**
     * Wohin die Route fuehrt - und warum das nicht die Anschrift allein ist.
     *
     * Gemeldet vom Kunden: "adres secilirken dogru adres bulunuyor fakat
     * navigasyona gecildiginde sadece sehir kullaniliyor." Auf dem
     * Demoserver stand in der Einladung genau das:
     *
     *     venue   = Imza Event Center
     *     address = Thannhausen, Landkreis Günzburg, Bayern, 86470, Deutschland
     *
     * Keine Strasse - und das ist kein Speicherfehler. Das Verzeichnis kennt
     * zu diesem Ort keine, und StaticMap::search nimmt den Namen aus der
     * Anschrift heraus, weil er gleich daneben ins Feld "Saal" wandert. Was
     * uebrig bleibt, ist eine Stadt, und eine Stadt ist kein Ziel.
     *
     * Der GENAUESTE Teil der Angabe ist damit der Saalname, und der stand
     * bisher nicht im Ziel. Google findet "Imza Event Center, Thannhausen"
     * auf Anhieb; "Thannhausen, Bayern, Deutschland" ist ein Ortsschild.
     *
     * Warum nicht Koordinaten: die Suche kennt sie (StaticMap::search gibt
     * lat/lng zurueck), gespeichert wird aber nur der Text - wer die
     * Anschrift von Hand tippt, hat gar keine. Ein Ziel, das nur nach der
     * Suche funktioniert, waere schlechter als eines, das immer funktioniert.
     *
     * Eine eigene Stelle und keine zwei Verkettungen: dieselbe Zeichenkette
     * steuert die Route UND das Kartenbild (InviteV2Controller::karte).
     * Liefen sie auseinander, zeigte das Bild auf das Ortsschild und der
     * Klick auf den Saal.
     *
     * @param array<string,mixed> $data
     */
    /**
     * Der gewaehlte Punkt als Ziel - "lat,lng" oder leer.
     *
     * Der zweite Anlauf auf dieselbe Beschwerde. Beim ersten stand der
     * Saalname noch nicht im Ziel; seit er drinsteht, ist es besser, aber
     * immer noch nicht genau: zu manchen Saelen kennt das Verzeichnis GAR
     * KEINE Strasse, und ein Ziel aus Text kann nie genauer werden als der
     * Text. "Imza Event Center, Thannhausen, Bayern" ist ein guter Hinweis
     * und kein Punkt.
     *
     * Die Koordinaten stehen in derselben Antwort, aus der auch die
     * Anschrift kommt. Gespeichert werden sie nur nach geprueft er Signatur
     * (InviteV2Controller::sammleAngaben), also steht hier keine zweite
     * Pruefung - was in den Daten liegt, ist durch sie hindurchgegangen.
     *
     * Leer heisst: von Hand getippt. Dann traegt der Text das Ziel weiter,
     * genau wie bisher - ein Paar ohne Kartendienst-Treffer soll nicht
     * schlechter dastehen als vorher.
     *
     * @param array<string,mixed> $data
     */
    public static function routenPunkt(array $data): string
    {
        $lat = (float) ($data['lat'] ?? 0);
        $lng = (float) ($data['lng'] ?? 0);

        // Null/Null ist kein Ort, sondern ein fehlender Wert: der Punkt liegt
        // im Atlantik vor Afrika, und dorthin soll niemand navigieren.
        if ($lat === 0.0 && $lng === 0.0) {
            return '';
        }

        return $lat . ',' . $lng;
    }

    public static function routenZiel(array $data): string
    {
        $adresse = trim((string) ($data['address'] ?? ''));
        $saal    = trim((string) ($data['venue'] ?? ''));

        // Ohne Anschrift kein Ziel. Der Saalname allein ist zu wenig: "Bei
        // Oma im Garten" schickt jeden Gast irgendwohin, und irgendwohin ist
        // schlimmer als nirgendwohin (siehe ort(): dort faellt der Link weg).
        if ($adresse === '') {
            return '';
        }

        // Steht er schon vorn, kommt er nicht zweimal. Manche
        // Verzeichniseintraege tragen ihn selbst, und ein verdoppelter Name
        // findet nichts.
        if ($saal === '' || str_starts_with(mb_strtolower($adresse), mb_strtolower($saal))) {
            return $adresse;
        }

        return $saal . ', ' . $adresse;
    }

    /**
     * @param array<string,mixed> $data
     * @param array<string,mixed> $settings
     */
    /** @param array<string,mixed> $doc */
    private static function ort(array $doc, array $data, string $locale, array $settings = []): string
    {
        $adresse = trim((string) ($data['address'] ?? ''));
        $ort = trim((string) ($data['venue'] ?? ''));

        $out = '';
        if ($ort !== '') {
            /*
             * Das Zeichen des Ortes - wenn die Vorlage eines hinterlegt hat.
             *
             * "3c simgeler kismina yukledim ama gelmedi bir sey." Das Bild war
             * da, gespeichert und im Panel zu sehen; nur gedruckt wurde es
             * nirgends. Die Kennung "konum" gab es bis hierher ausschliesslich
             * als Wahl fuer eine ZEILE DES ABLAUFS - und wer sie nie waehlt,
             * sieht sie nie. Ein Feld, dessen Wirkung woanders von einer
             * fremden Entscheidung abhaengt, ist ein Knopf, der nichts tut.
             *
             * NUR mit eigener Datei. Faellt es auf die gezeichnete Fassung des
             * Hauses zurueck, bekaeme jede bestehende Vorlage ueber Nacht ein
             * Symbol neben ihren Saalnamen - eine Aenderung, die niemand
             * bestellt hat. So aendert sich genau dort etwas, wo jemand eine
             * Datei hingelegt hat.
             */
            $eigen = is_array($doc['icons']['konum'] ?? null) ? $doc['icons']['konum'] : [];
            $marke = ((string) ($eigen['src'] ?? '')) !== '' || ((string) ($eigen['video'] ?? '')) !== ''
                ? self::zeichen($doc, 'konum')
                : '';

            $out .= '<p class="d-sec-venue">' . $marke . e($ort) . '</p>';
        }
        // Nur wenn sie da ist: seit hatInhalt auch den Saalnamen allein
        // gelten laesst, kann die Strasse fehlen - ein leerer Absatz waere
        // eine Zeile Luft ohne Grund.
        if ($adresse !== '') {
            $out .= '<p class="d-sec-address">' . e($adresse) . '</p>';
        }

        // Ohne Adresse gibt es weder etwas zu zeichnen noch etwas
        // anzusteuern; der Saalname allein ist kein Ziel.
        if ($adresse === '') {
            return $out;
        }

        // Der Saalname fuehrt das Ziel an - siehe routenZiel(), dort steht
        // der gemessene Grund. Gedruckt bleiben die beiden Zeilen getrennt:
        // das Ziel ist eine Adresse fuer Google, keine fuer den Leser.
        /*
         * Der Punkt schlaegt den Text.
         *
         * Google nimmt "48.123,10.456" als Ziel und fuehrt genau dorthin.
         * Wo es keinen Punkt gibt (von Hand getippte Adresse), traegt der
         * Text weiter - siehe routenPunkt() und routenZiel().
         */
        $ziel  = self::routenPunkt($data) ?: self::routenZiel($data);
        $route = 'https://www.google.com/maps/dir/?api=1&destination=' . rawurlencode($ziel);

        /*
         * Das Kartenbild.
         *
         * Ein Bild und kein iframe: eine eingebettete Karte holte sich der
         * Browser des Gastes bei Google, und zwar beim Aufschlagen der
         * Einladung - vor jeder Einwilligung. Hier laedt der Server das Bild
         * (StaticMap), legt es ab und liefert es unter unserer eigenen
         * Adresse aus. Nach draussen geht erst der Klick.
         *
         * Die Bildadresse haengt am Slug und nicht an der Adresse: sonst
         * stuende die Anschrift des Paares in einer URL, und ein Endpunkt,
         * der zu beliebigem Text eine Karte zeichnet, waere ein offenes Tor
         * vor einem fremden Kartendienst.
         *
         * Kein Slug heisst Vorschau (Schaufenster, Panel): dort gibt es die
         * Einladung noch nicht, also auch kein Bild - der Ort steht dann mit
         * Namen, Adresse und Route da wie bisher.
         */
        $slug = trim((string) ($data['slug'] ?? ''));

        /*
         * Kein Slug heisst Vorschau: Schaufenster und Assistent zeichnen die
         * Beispieleinladung, und die gibt es in der Datenbank nicht. Fuer sie
         * gibt es einen eigenen Endpunkt zu genau einer Adresse - der des
         * Beispiels. Ohne ihn faende der Grafiker die Karte erst auf der
         * fertigen Einladung wieder und haette im Schaufenster den Eindruck,
         * die Einstellung tue nichts.
         */
        $quelle = '';
        if ($slug !== '') {
            $quelle = I18n::path('/v2/einladung/' . rawurlencode($slug) . '/karte.png', $locale);
        } elseif ($adresse === StaticMap::DEMO_ADDRESS) {
            $quelle = I18n::path('/v2/karte-beispiel.png', $locale);
        }

        $form = (string) ($settings['karte'] ?? 'blatt');

        /*
         * Die eigene Zeichnung.
         *
         * "Kendi haritali resmimi ekleyebilmeliyim. Gercek haritayi optional
         * cikarabilmeliyim kendi harita resmimi yuklemek icin."
         *
         * Sie ersetzt das gerechnete Bild vollstaendig - inklusive der Frage
         * nach dem Slug: eine hochgeladene Zeichnung gibt es auch im
         * Schaufenster und im Assistenten, wo es die Einladung noch gar nicht
         * gibt. Genau dort war das gerechnete Bild bisher leer, und ein
         * Grafiker, der seine Karte einstellt, will sie sofort sehen.
         *
         * Die Form bleibt eine eigene Frage: auch eine gezeichnete Karte darf
         * im Blatt oder im Rechteck sitzen. "eigen" waehlt die QUELLE, nicht
         * den Rahmen - deshalb faellt sie hier auf "blatt" zurueck.
         */
        $film = '';

        if ($form === 'eigen') {
            /*
             * Der Film gewinnt gegen das Bild, wenn beide hinterlegt sind:
             * wer einen hochlaedt, hat sich fuer ihn entschieden. Das Bild
             * bleibt liegen und ist mit einem Klick wieder da.
             */
            $film  = Design::safeSrc((string) ($settings['mapVideo'] ?? ''));
            $eigen = Design::safeSrc((string) ($settings['mapSrc'] ?? ''));

            // Ohne Datei kein Bild. Ein leerer Rahmen an der Stelle, an der
            // eine Karte stehen sollte, ist schlimmer als gar keine.
            if ($film === '' && $eigen === '') {
                $form = 'aus';
            } else {
                $quelle = $eigen;
                $form = 'blatt';
            }
        }
        if ($form !== 'aus' && ($quelle !== '' || $film !== '')) {
            /*
             * Die Groesse steht als Klasse und nicht als Zahl im style: sie
             * gehoert der Vorlage, und der Stilblock ist der Ort, an dem
             * die Vorlage spricht. Ein style-Attribut waere dieselbe Angabe
             * an einem Ort, den kein anderer Abschnitt lesen kann.
             */
            $gross = (string) ($settings['mapSize'] ?? 'm');

            $out .= '<a class="d-sec-map-bild d-sec-map-' . e($form)
                . ' d-sec-map-gr-' . e($gross) . '"'
                . ' rel="noopener noreferrer" target="_blank" href="' . e($route) . '">';

            if ($film !== '') {
                /*
                 * Ein Film als Karte.
                 *
                 * autoplay, anders als bei den Ebenen der Karte: die stehen
                 * hinter dem geschlossenen Kuvert und liefen dort unsichtbar
                 * im Mobilfunk. Diese hier steht weit unten in den
                 * Abschnitten - wer sie sieht, hat die Einladung geoeffnet
                 * und ist bis zum Ort gescrollt.
                 *
                 * Kein Ton (muted), keine Bedienleiste: eine Karte ist
                 * Zierde, kein Film zum Ansehen. Und ohne muted laesst kein
                 * Browser sie von allein laufen.
                 */
                $out .= '<video src="' . e($film) . '"'
                    . ' width="640" height="480" autoplay muted loop playsinline'
                    . ' preload="metadata" aria-hidden="true"></video>';
            } else {
                $out .= '<img src="' . e($quelle) . '"'
                    . ' width="640" height="480" loading="lazy" decoding="async"'
                    . ' alt="' . e($locale === 'de' ? 'Karte: ' . $adresse : 'Map: ' . $adresse) . '">';
            }

            $out .= '</a>';
        }

        // Der Link geht zur Routenplanung, nicht auf eine Karte: wer die
        // Adresse liest, will hinfahren.
        //
        // Abschaltbar, weil er nicht immer traegt: kennt Google die Adresse
        // nicht, fuehrt er ins Leere - und ein Link ins Leere ist schlimmer
        // als keiner. Die Adresse selbst steht in beiden Faellen da.
        if ($settings['map'] ?? true) {
            $out .= '<a class="d-sec-map" rel="noopener noreferrer" target="_blank" href="' . e($route) . '">'
                . e($locale === 'de' ? 'Route planen' : 'Plan route') . '</a>';
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
    /**
     * Der Tag selbst - und zwar so gross, wie die Vorlage ihn haben will.
     *
     * "TARIH / 08 / AGUSTOS / 2026 - burada 08 cok buyuk olabilir, digerleri
     * ise daha kucuk ve zarif." Bis hierher gab es das Datum nur als Zeile:
     * klein, unter dem Countdown oder auf der Karte. Eine Angabe, die auf
     * gedruckter Papeterie fast immer das Groesste auf dem Blatt ist, hatte
     * hier keine eigene Gestalt.
     *
     * Die Rollen tun die Arbeit (Paket A): der Tag traegt "grosse Zahl",
     * Monat und Jahr tragen "kleiner Hinweis". Wie gross das ausfaellt,
     * entscheidet damit die Vorlage und nicht diese Funktion - hier steht
     * nur, WAS wo hingehoert.
     *
     * Ohne Datum kein Abschnitt (hatInhalt), hier also keine Leerpruefung.
     *
     * @param array<string,mixed> $data
     */
    private static function datum(array $data, string $locale, string $variant = 'default'): string
    {
        $iso = trim((string) ($data['date'] ?? ''));

        /*
         * Aus dem ISO-Datum geschnitten und nicht mit date() gerechnet: der
         * Wert ist eine Zeichenkette aus einem Formular, und ein
         * Zeitstempel-Umweg wuerde aus "2027-09-12" je nach Zeitzone den
         * elften machen. Dieselbe Vorsicht wie im Countdown-Skript.
         */
        $tag  = ltrim(substr($iso, 8, 2), '0');
        $jahr = substr($iso, 0, 4);

        if ($variant === 'gross') {
            return '<p class="d-datum-tag">' . e($tag) . '</p>'
                . '<p class="d-datum-monat">' . e(Dates::month($iso, $locale)) . '</p>'
                . '<p class="d-datum-jahr">' . e($jahr) . '</p>';
        }

        if ($variant === 'zeile') {
            // Die Striche sind Stil, nicht Text: ein Bindestrich im Markup
            // wuerde vorgelesen und liesse sich nicht abschalten.
            return '<p class="d-datum-zeile">'
                . '<span>' . e($tag) . '</span>'
                . '<span>' . e(Dates::month($iso, $locale)) . '</span>'
                . '<span>' . e($jahr) . '</span>'
                . '</p>';
        }

        // Ausgeschrieben, mit dem Wochentag darueber - dieselbe Auskunft,
        // die die Karte seit jeher druckt.
        return '<p class="d-datum-wochentag">' . e(Dates::weekday($iso, $locale)) . '</p>'
            . '<p class="d-datum-lang">' . e(Dates::long($iso, $locale)) . '</p>';
    }

    /**
     * Die freien Zeichen einer Gestalt, nach Anker und Seite sortiert.
     *
     * Fertig sortiert und nicht als Liste: der Bauer unten setzt sie an
     * sieben Stellen ein, und an jeder soll eine Zeichenkette stehen und
     * keine Schleife. Alle Anker stehen als Schluessel da, auch die leeren -
     * so braucht keine der Stellen ein isset.
     *
     * @param array<string,mixed> $doc
     * @return array{vor:array<string,string>,nach:array<string,string>}
     */
    private static function cdZeichen(array $doc, string $variant): array
    {
        $leer = ['datum' => '', 'days' => '', 'hours' => '', 'minutes' => '', 'seconds' => ''];
        $out  = ['vor' => $leer, 'nach' => $leer];

        $liste = $doc['countdownIcons'][$variant] ?? [];
        if (!is_array($liste)) {
            return $out;
        }

        foreach (array_values($liste) as $i => $eintrag) {
            if (!is_array($eintrag)) {
                continue;
            }

            $seite = (string) ($eintrag['side'] ?? 'nach');
            $anker = (string) ($eintrag['anchor'] ?? 'days');

            if (!isset($out[$seite][$anker])) {
                continue;
            }

            $out[$seite][$anker] .= self::cdEines($eintrag, $variant . ':' . $i);
        }

        return $out;
    }

    /**
     * Ein einzelnes freies Zeichen.
     *
     * Die Geometrie steht am Knoten und nicht im Stilblock, und das ist der
     * Unterschied zu den Katalogzeichen: dort sagt die EINLADUNG, welche
     * Zeichen vorkommen, also muss die Vorlage sie von weitem ansprechen
     * koennen. Hier ist der Knoten selbst die Aussage der Vorlage - er
     * existiert nur, weil sie ihn angelegt hat, und dann steht seine Groesse
     * am besten dort, wo er steht.
     *
     * em wie ueberall: das Zeichen haengt an einer Zahl und soll mit ihr
     * wachsen. width allein, die Hoehe folgt aus der Grundregel (height:auto)
     * und damit dem Seitenverhaeltnis der Datei.
     *
     * Die Kennung im data-Attribut ist fuer den Editor: sie sagt, welche
     * Zeile des Formulars zu diesem Knoten gehoert, damit sich das Zeichen
     * ziehen laesst statt in vier Zahlenfelder getippt zu werden. Sie steht
     * auch auf der versendeten Seite - ein totes Attribut von zwoelf Zeichen
     * ist billiger als ein zweiter Bauplan fuer die Vorschau, und ein
     * zweiter Bauplan ist es, der irgendwann auseinanderlaeuft.
     *
     * @param array<string,mixed> $e
     */
    private static function cdEines(array $e, string $kennung = '', string $klasse = 'd-cd-el'): string
    {
        $stil = 'width:' . (((int) $e['size']) / 100) . 'em;';

        if ((int) $e['x'] !== 0 || (int) $e['y'] !== 0) {
            $stil .= 'transform:translate(' . (((int) $e['x']) / 100) . 'em,'
                . (((int) $e['y']) / 100) . 'em);';
        }
        // margin-inline: das Zeichen steht mal links, mal rechts vom Feld.
        if ((int) $e['gap'] !== 0) {
            $stil .= 'margin-inline:' . (((int) $e['gap']) / 100) . 'em;';
        }
        // position:relative gehoert zum z-index - ohne sie greift er an einem
        // statischen Knoten gar nicht. Dieselbe Falle wie bei den Zeichen.
        if ((int) $e['z'] !== 0) {
            $stil .= 'position:relative;z-index:' . ((int) $e['z']) . ';';
        }

        $marke = $kennung !== '' ? ' data-cd="' . e($kennung) . '"' : '';

        $film = (string) ($e['video'] ?? '');
        if ($film !== '') {
            // autoplay, stumm, in der Schleife - wie das Zeichen daneben.
            // Wer den Countdown sieht, hat die Einladung geoeffnet.
            return '<video class="' . $klasse . '"' . $marke . ' style="' . e($stil) . '" src="' . e($film) . '"'
                . ' autoplay muted loop playsinline preload="metadata" aria-hidden="true"></video>';
        }

        $bild = (string) ($e['src'] ?? '');
        if ($bild === '') {
            return '';
        }

        return '<img class="' . $klasse . '"' . $marke . ' style="' . e($stil) . '" src="'
            . e($bild) . '" alt="" aria-hidden="true">';
    }

    /**
     * Der Schmuck eines Abschnitts, nach Anker und Seite sortiert.
     *
     * Dieselbe Form wie cdZeichen() und aus demselben Grund: der Bauer oben
     * setzt sie an vier Stellen ein, und an jeder soll eine Zeichenkette
     * stehen und keine Schleife.
     *
     * @param array<string,mixed> $abschnitt
     * @return array{vor:array<string,string>,nach:array<string,string>}
     */
    private static function dekoZeichen(array $abschnitt): array
    {
        $leer = ['titel' => '', 'inhalt' => ''];
        $out  = ['vor' => $leer, 'nach' => $leer];

        $liste = $abschnitt['deko'] ?? [];
        if (!is_array($liste)) {
            return $out;
        }

        foreach (array_values($liste) as $i => $eintrag) {
            if (!is_array($eintrag)) {
                continue;
            }

            $seite = (string) ($eintrag['side'] ?? 'nach');
            $anker = (string) ($eintrag['anchor'] ?? 'titel');

            if (!isset($out[$seite][$anker])) {
                continue;
            }

            $out[$seite][$anker] .= self::cdEines($eintrag, '', 'd-deko');
        }

        return $out;
    }

    /** @param array<string,mixed> $doc */
    private static function countdown(array $doc, array $data, string $locale, string $variant = 'default'): string
    {
        $datum = trim((string) ($data['date'] ?? ''));

        /*
         * Die Uhr-Gestalt: vier Felder, die das Skript im Sekundentakt
         * fuellt. Auch hier steht kein Wort im JavaScript - der Server
         * schreibt "Tage"/"Stunden" in die data-label, genau wie bei der
         * einzelnen Zahl. Ohne Skript bleiben die Felder leer und das
         * gedruckte Datum darunter traegt den Abschnitt allein.
         *
         * Die Uhrzeit reist mit, wenn es eine gibt: bis zum Beginn der Feier
         * und nicht bis Mitternacht davor. Fehlt sie, faengt der Tag um
         * Mitternacht an - dieselbe Annahme wie in Dates.
         */
        $zeit = trim((string) ($data['time'] ?? ''));
        $ziel = $datum . ($zeit !== '' ? 'T' . $zeit : 'T00:00');

        $felder = $locale === 'de'
            ? ['days' => 'Tage', 'hours' => 'Stunden', 'minutes' => 'Minuten', 'seconds' => 'Sekunden']
            : ['days' => 'days', 'hours' => 'hours', 'minutes' => 'minutes', 'seconds' => 'seconds'];

        /*
         * Die freien Zeichen der Vorlage - je Gestalt eigene, weil die vier
         * Gestalten nicht dieselben Felder haben.
         *
         * Sie stehen IM Fluss und nicht darueber: ein Zeichen neben einer
         * Zahl soll mitrutschen, wenn die Zahl von zwei auf drei Stellen
         * geht. Wer es woanders haben will, verschiebt es mit x und y - das
         * ist eine Verschiebung und keine feste Koordinate, also haelt sie
         * auch, wenn die Schrift waechst.
         */
        $schmuck = self::cdZeichen($doc, $variant);
        $vor     = $schmuck['vor'];
        $nach    = $schmuck['nach'];

        /*
         * Die Tage gross, der Rest als leise Zeile darunter.
         *
         * "10 GUN - altinda: 23 SAAT · 31 DAKIKA · 54 SANIYE."
         *
         * Derselbe Vertrag wie die Uhr und kein zweiter Zaehler: das Skript
         * schaltet auf den Sekundentakt, sobald [data-countdown-hours] im
         * Kasten steht, und fuellt dann alle vier Felder. Was diese Gestalt
         * anders macht, ist allein die Anordnung.
         *
         * Die Punkte zwischen den drei kleinen Angaben stehen im Stilblock
         * und nicht im Markup: ein Mittelpunkt als Textknoten wuerde
         * vorgelesen und liesse sich von keiner Vorlage abschalten.
         */
        if ($variant === 'tage') {
            $out = '<div class="d-sec-uhr d-uhr-tage" data-countdown="' . e($ziel) . '">'
                . $vor['days']
                . '<span class="d-sec-uhr-zahl" data-countdown-days>&nbsp;</span>'
                . '<span class="d-sec-uhr-wort">' . e($felder['days']) . '</span>'
                . $nach['days']
                . '<span class="d-uhr-rest">';

            foreach (['hours', 'minutes', 'seconds'] as $schluessel) {
                $out .= $vor[$schluessel]
                    . '<span class="d-uhr-teil">'
                    . '<span data-countdown-' . e($schluessel) . '>&nbsp;</span> '
                    . e($felder[$schluessel])
                    . '</span>'
                    . $nach[$schluessel];
            }

            return $out . '</span></div>';
        }

        if ($variant === 'uhr') {

            /*
             * Erst das Datum, dann die Felder.
             *
             * Andersherum stand es bis zum 27.08.2026, und die Leserichtung
             * war die schlechtere: die Uhr nennt eine Spanne, und eine Spanne
             * ohne Bezugspunkt ist eine Zahl. Zuerst also die Aussage - wann
             * -, darunter die Ungeduld - noch wie lange.
             *
             * Ohne Skript bleiben die vier Felder leer; dann traegt das
             * Datum den Abschnitt allein, und es steht jetzt an der Stelle,
             * an der es das auch kann.
             */
            $out = $vor['datum']
                . '<p class="d-sec-countdown-datum">' . e(Dates::long($datum, $locale)) . '</p>'
                . $nach['datum']
                . '<div class="d-sec-uhr" data-countdown="' . e($ziel) . '">';

            foreach ($felder as $schluessel => $wort) {
                $out .= $vor[$schluessel]
                    . '<span class="d-sec-uhr-feld">'
                    . '<span class="d-sec-uhr-zahl" data-countdown-' . e($schluessel) . '>&nbsp;</span>'
                    . '<span class="d-sec-uhr-wort">' . e($wort) . '</span>'
                    . '</span>'
                    . $nach[$schluessel];
            }

            return $out . '</div>';
        }

        return '<p class="d-sec-countdown" data-countdown="' . e($datum) . '">'
            . $vor['days']
            . '<span class="d-sec-days" data-countdown-days data-label="'
            . e($locale === 'de' ? 'Tage' : 'days') . '"></span>'
            . $nach['days']
            . $vor['datum'] . e(Dates::long($datum, $locale)) . $nach['datum']
            . '</p>';
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
    private static function programm(
        array $doc,
        array $data,
        string $locale,
        string $variant = 'default'
    ): string {
        // d-sec-plan und nicht d-sec-program: die <section> traegt bereits
        // d-sec-<typ>, also d-sec-program. Solange die Liste denselben Namen
        // trug, galt JEDE Regel fuer beide - und die Zweispaltenregel machte
        // aus dem Abschnitt selbst ein Raster, in dem die Ueberschrift NEBEN
        // ihrer Liste stand statt darueber. Genau dieselbe Falle, die bei
        // freitext() vermieden wurde und hier jahrelang zuschnappte.
        $out = '<dl class="d-sec-plan">';

        /*
         * Nur der Zeitstrahl haengt am Beobachter.
         *
         * invitation.js sucht jedes .iv auf der Seite und schreibt
         * data-visible="true", sobald es im Bild steht (startReveals). Der
         * Stilblock der Gestalt macht daraus die Reihenfolge: Zeichen, Faden,
         * Wort. Ein zweites Skript mit derselben Aufgabe waere die Sorte
         * Kopie, die ein halbes Jahr spaeter auseinanderlaeuft - dasselbe
         * Argument wie bei der Musik weiter unten.
         *
         * Die zweispaltige Gestalt bekommt es NICHT: sie hat keine Linie, die
         * sich ziehen koennte, und ein Ablauf, der beim Scrollen erst
         * auftaucht, waere dort eine Verzoegerung ohne Grund.
         */
        $iv = $variant === 'zeitstrahl' ? ' class="iv"' : '';

        /*
         * "Ayri kucuk kartlar."
         *
         * Jede Station bekommt einen Kasten um sich. Das braucht einen
         * Knoten, den es bisher nicht gab: dt und dd sind Geschwister, und
         * CSS kann zwei Geschwister nicht zu einem Kaestchen zusammenfassen.
         *
         * Ein <div> zwischen <dl> und den Paaren ist gueltiges HTML - die
         * Spezifikation erlaubt es ausdruecklich, um genau solche Gruppen zu
         * bilden. Die Liste bleibt damit eine Liste, und ein Vorleser liest
         * weiterhin "Uhrzeit - was passiert".
         *
         * Nur in dieser Gestalt: die anderen beiden rechnen mit dt und dd
         * als direkten Kindern des Rasters.
         */
        $karten = $variant === 'karten';

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
            if ($karten) {
                $out .= '<div class="d-plan-karte">';
            }

            $out .= '<dt' . $iv . '><span class="d-plan-zeit">' . e($zeile['time']) . '</span>';

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
                $out .= '<span class="d-plan-rozet">' . self::zeichen($doc, (string) $zeile['icon']) . '</span>';
            }

            $out .= '</dt><dd' . $iv . '><span class="d-plan-titel">' . e($titel) . '</span>';

            // Kein leerer Absatz fuer eine Zeile ohne Satz.
            if ($zeile['text'] !== '') {
                $out .= '<span class="d-plan-text">' . e($zeile['text']) . '</span>';
            }

            $out .= '</dd>' . ($karten ? '</div>' : '');
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
     * Das Lied, das das Paar in DIESEN Abschnitt gelegt hat.
     *
     * Getrennt von inhalt(), weil hier kein Vorschlag der Vorlage
     * einspringen darf: die Voreinstellung einer Tonspur steht in den
     * EINSTELLUNGEN des Abschnitts und gehoert dem Grafiker (siehe
     * tonspur()). Diese Auskunft ist "was hat das Paar hochgeladen" - und
     * genau das braucht das Bearbeiten-Formular, um es zum Wegnehmen
     * anzubieten.
     *
     * safeSrc wie bei den Bildern: der Pfad stammt aus dem eigenen Upload,
     * steht seitdem aber in einem JSON-Feld, und was dort steht, ist beim
     * Lesen wieder eine Behauptung.
     *
     * @param array<string,mixed> $data
     */
    public static function sectionTrack(array $data, string $id): string
    {
        return Design::safeSrc(trim(self::sectionValue($data, $id, 'track')));
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
    private static function schluss(array $abschnitt, array $data, string $locale = 'de'): string
    {
        $out = '';

        foreach (paragraphs(self::inhalt($abschnitt, $data, 'text')) as $absatz) {
            $out .= '<p class="d-sec-absatz">' . e($absatz) . '</p>';
        }

        $zeichen = trim(self::inhalt($abschnitt, $data, 'hashtag'));
        if ($zeichen !== '') {
            $out .= '<p class="d-sec-hashtag">#' . e(ltrim($zeichen, '#')) . '</p>';
        }

        /*
         * Der Weg zurueck zu uns - zuunterst, nach allem, was dem Paar
         * gehoert.
         *
         * Relativ und nicht ausgeschrieben. Die Einladung wird von derselben
         * Seite ausgeliefert, also fuehrt "/de/" dorthin, wo sie herkommt -
         * und zwar auch dann noch, wenn die Domain wechselt. Sie steht laut
         * DURUM.md noch nicht fest, und eine ausgeschriebene Adresse waere
         * eine zweite Stelle, an die beim Wechsel jemand denken muesste.
         *
         * I18n::path und keine Verkettung: dieselbe Stelle, die auch das
         * Kartenbild im Ort-Abschnitt sprachrichtig aufhaengt.
         *
         * Der Name des Hauses steht im Code und nicht in einem Feld. Er ist
         * dasselbe wie in <title>, im Impressum und in der og:site_name; ihn
         * hier eintippbar zu machen hiesse, eine fuenfte Quelle fuer einen
         * Namen zu oeffnen, der ohnehin ueberall gleich sein muss.
         */
        if (($abschnitt['settings']['credit'] ?? false)) {
            $out .= '<p class="d-sec-credit"><a href="' . e(I18n::path('/', $locale)) . '">'
                . e($locale === 'de'
                    ? 'Gestaltet mit Atelier Lumière'
                    : 'Made with Atelier Lumière')
                . '</a></p>';
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
    private static function speisekarte(array $doc, array $abschnitt, array $data, string $locale): string
    {
        $out = '';

        foreach (SectionRegistry::inputs('menu') as $schluessel => $feld) {
            $wert = trim(self::inhalt($abschnitt, $data, (string) $schluessel));
            if ($wert === '') {
                continue;
            }

            $out .= '<div class="d-menu-zeile">';

            $out .= self::zeichen($doc, (string) ($feld['icon'] ?? ''));

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
    private static function kleiderordnung(array $doc, array $abschnitt, array $data): string
    {
        $code = trim(self::inhalt($abschnitt, $data, 'code'));
        $hinweis = trim(self::inhalt($abschnitt, $data, 'note'));
        $out = '';

        if ($code !== '') {
            $out .= '<p class="d-dress-code">';

            $out .= self::zeichen($doc, 'dresscode');

            $out .= e($code) . '</p>';
        }

        // Kein leerer Absatz fuer einen fehlenden Hinweis.
        if ($hinweis !== '') {
            $out .= '<p class="d-dress-note">' . e($hinweis) . '</p>';
        }

        /*
         * Damen und Herren.
         *
         * Zwei eigene Zeilen und keine zweite Bedeutung des Hinweises: was
         * Damen tragen, ist eine andere Auskunft als was Herren tragen, und
         * in einem Absatz zusammengeschrieben liest sie niemand zu Ende.
         *
         * Der Kasten steht nur da, wenn wenigstens eine der beiden gefuellt
         * ist - sonst waere die Gestalt "nebeneinander" ein leeres Raster.
         */
        $damen  = trim(self::inhalt($abschnitt, $data, 'women'));
        $herren = trim(self::inhalt($abschnitt, $data, 'men'));

        if ($damen !== '' || $herren !== '') {
            $out .= '<div class="d-dress-paar">';
            foreach (['women' => $damen, 'men' => $herren] as $wer => $wert) {
                if ($wert === '') {
                    continue;
                }
                $out .= '<p class="d-dress-' . e($wer) . '">' . e($wert) . '</p>';
            }
            $out .= '</div>';
        }

        /*
         * Die Farbpalette.
         *
         * "Renkleri secebilmeli ve davetiyede bunlar sik renk daireleri
         * olarak gosterilebilmeli."
         *
         * Jede Farbe geht durch Design::safeColor - was keine ist, faellt
         * weg. Das ist kein Formalismus: der Wert steht am Ende in einem
         * style-Attribut, und ein Textfeld, aus dem ungeprueft CSS wird,
         * waere die eine Stelle, an der sich fremdes CSS in jede Einladung
         * schreiben liesse.
         *
         * Die Kreise tragen ihren Wert als title: wer die Farbe nachkaufen
         * will, braucht die Zahl, und eine Farbe allein laesst sich nicht
         * abschreiben.
         */
        $roh = trim(self::inhalt($abschnitt, $data, 'colors'));
        if ($roh === '') {
            return $out;
        }

        /*
         * Nur Hex-Werte, und das ist eine Folge des Trennzeichens: ein
         * rgba(12, 34, 56, .5) traegt selbst Kommas, und eine Liste mit
         * Komma zerschnitte es mitten hindurch. Ein zweites Trennzeichen
         * einzufuehren waere schlimmer - der Grafiker muesste sich merken,
         * welches hier gilt und welches nebenan.
         *
         * safeColor gibt bei allem Uebrigen "transparent" zurueck, nicht
         * eine leere Zeichenkette; ein durchsichtiger Kreis waere ein Loch
         * in der Palette, also faellt er hier weg.
         */
        $kreise = '';
        foreach (array_slice(array_map('trim', explode(',', $roh)), 0, 8) as $farbe) {
            $sicher = Design::safeColor($farbe);
            if ($sicher === 'transparent' || !str_starts_with($sicher, '#')) {
                continue;
            }
            $kreise .= '<span class="d-dress-kreis" style="background:' . e($sicher) . '"'
                . ' title="' . e($sicher) . '"></span>';
        }

        return $kreise === '' ? $out : $out . '<div class="d-dress-farben">' . $kreise . '</div>';
    }

    private static function galerie(array $data, string $id): string
    {
        $out = '';

        /*
         * Jedes Bild in einem eigenen Kasten.
         *
         * Er kostet nichts, wenn keine Form gewaehlt ist (eine Regel:
         * position:relative), und er ist die Voraussetzung fuer die, die es
         * gibt: das Polaroid braucht einen weissen Rand mit breiterem Fuss,
         * und die eigene Zeichnung legt sich als Auflage DARUEBER. Beides
         * geht am <img> selbst nicht - ein img hat kein ::after.
         *
         * Immer und nicht nur bei gewaehlter Form, aus demselben Grund wie
         * beim Kasten um den Text: ein Knoten, den es nur manchmal gibt,
         * waere ein zweiter Bauplan, und jede Regel muesste beide kennen.
         */
        foreach (self::sectionPhotos($data, $id) as $pfad) {
            $out .= '<span class="d-bild">'
                . '<img src="' . e($pfad) . '" alt="" loading="lazy" decoding="async">'
                . '</span>';
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
    private static function musik(array $abschnitt, array $data, string $locale): string
    {
        /*
         * Ein Lied von auswaerts - und die Zwei-Klick-Loesung.
         *
         * Hier steht VOR dem Antippen kein Rahmen, und das ist der ganze
         * Punkt: ein iframe im Markup waere ein Aufruf zu YouTube, den kein
         * Gast erlaubt hat. Stattdessen liegt nur die Adresse bereit; den
         * Rahmen baut invitation.js, wenn jemand den Knopf drueckt.
         *
         * Damit braucht es auch keine dritte Kategorie im Einwilligungsbanner:
         * der Klick IST die Einwilligung, und zwar fuer genau diesen einen
         * Rahmen. Eine Kategorie mehr hiesse ein Absatz mehr in der
         * Datenschutzerklaerung, den jemand pflegen muss.
         *
         * Der Hinweis steht daneben und nicht im Kleingedruckten: wer tippt,
         * soll vorher wissen, wohin er sich verbindet.
         */
        if ((string) $abschnitt['variant'] === 'einbetten') {
            // Aus dem eigenen Feld, nicht aus der Tonspur: dort steht ein Pfad
            // aus dem eigenen Haus, hier eine fremde Adresse. Beim Speichern
            // ist sie schon durch safeEinbettung gegangen; hier noch einmal,
            // weil ein Dokument auch aus einer aelteren Fassung kommen kann.
            $rahmen = Design::safeEinbettung((string) ($abschnitt['settings']['embed'] ?? ''));

            // Keine erkannte Adresse, kein Kasten. Ein Knopf, der nichts
            // laden kann, ist schlimmer als gar keiner.
            if ($rahmen === '') {
                return '';
            }

            $de = $locale === 'de';

            return '<div class="d-sec-einbettung" data-einbettung="' . e($rahmen) . '">'
                . '<button type="button" class="d-sec-einbettung-knopf" data-einbettung-start>'
                . e($de ? 'Lied auf YouTube abspielen' : 'Play the song on YouTube')
                . '</button>'
                . '<span class="d-sec-einbettung-hinweis">'
                . e($de
                    ? 'Beim Antippen wird ein Video von YouTube geladen. Dabei werden Daten an YouTube übertragen.'
                    : 'Tapping loads a video from YouTube. Data is transferred to YouTube in the process.')
                . '</span>'
                . '</div>';
        }

        $spur = self::tonspur($abschnitt, $data);
        if ($spur === '') {
            return '';
        }

        // Der sichtbare Spieler: fuer das Lied, das man bewusst anhoert.
        if ((string) $abschnitt['variant'] === 'spieler') {
            return '<audio class="d-sec-ton" controls preload="none" src="' . e($spur) . '"></audio>';
        }

        /*
         * Im Hintergrund - und mit genau denselben Haken wie in der ersten
         * Einladung: [data-music] und [data-music-toggle].
         *
         * Kein neues Skript. invitation.js liegt auf dieser Seite ohnehin
         * (InviteV2Controller::show laedt es fuer den Umschlag) und kann
         * beides schon: es startet den Ton in dem Moment, in dem jemand das
         * Kuvert antippt, und schaltet ihn am Knopf stumm. Ein zweites
         * Skript mit denselben zwei Aufgaben waere die Sorte Kopie, die ein
         * halbes Jahr spaeter auseinanderlaeuft.
         *
         * Autoplay ohne Klick verbieten alle Browser, und zu Recht. Das
         * Antippen des Umschlags IST der Klick - deshalb braucht diese
         * Gestalt keinen Startknopf, nur einen zum Stummschalten.
         *
         * preload="none" ist nicht Sparsamkeit, sondern Anstand: ein Lied
         * laedt sonst auf jedem Telefon mit, das die Einladung nur kurz
         * aufmacht.
         */
        return '<audio data-music loop preload="none" src="' . e($spur) . '"></audio>'
            . '<button type="button" data-music-toggle class="d-sec-ton-knopf"'
            . ' aria-label="' . e($locale === 'de' ? 'Musik' : 'Music') . '">&#9834;</button>';
    }

    /**
     * Welches Lied gespielt wird.
     *
     * Zwei Quellen, eine Rangfolge: das Paar gewinnt. Die Spur der Vorlage
     * ist ein Vorschlag - sie klingt, wenn niemand etwas hochgeladen hat, und
     * tritt zurueck, sobald ein eigenes Lied da ist. Andersherum waere die
     * Vorlage staerker als das Paar, und das ist bei keinem anderen Feld so.
     *
     * Beide gehen durch safeSrc: ein Pfad auf einen fremden Server waere ein
     * Zuhoerer, der mitschreibt, wer die Einladung aufgemacht hat - und ein
     * Link, der eines Tages ins Leere zeigt.
     *
     * @param array<string,mixed> $abschnitt
     * @param array<string,mixed> $data
     */
    private static function tonspur(array $abschnitt, array $data): string
    {
        // safeAudio statt safeSrc: ein Lied darf auch von auswaerts kommen,
        // solange es ueber https kommt. Bilder und Filme duerfen das nicht -
        // sie gehoeren zur Vorlage.
        $eigenes = Design::safeAudio(trim(self::inhalt($abschnitt, $data, 'track')));

        return $eigenes !== ''
            ? $eigenes
            : Design::safeAudio((string) ($abschnitt['settings']['track'] ?? ''));
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
