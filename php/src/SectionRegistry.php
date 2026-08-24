<?php

declare(strict_types=1);

namespace Atelier;

/**
 * Der Katalog der Abschnittsarten - und was jede von ihnen kann.
 *
 * Bis hierher war eine Art ein Wort: "program" hiess genau ein Aussehen, das
 * in DesignSections::programm() stand. Wer ein zweites wollte, brauchte eine
 * siebte Art - und damit einen neuen Zweig in jedem match()-Block, eine neue
 * Zeile in TYPES und einen neuen Namen, den der Grafiker lernen muss.
 *
 * Hier stehen drei Dinge getrennt, die vorher eines waren:
 *
 *   ART          was ein Abschnitt zeigt (ein Ablauf, ein Ort, eine Frage)
 *   VARIANTE     wie er dabei aussieht
 *   EINSTELLUNG  woran der Grafiker dreht, ohne dass es eine Variante wird
 *
 * Eine neue Variante ist damit ein Eintrag hier und ein Stilblock in
 * DesignSections - keine neue Art, kein neuer Zweig, kein neues Wort im
 * Panel. Genau das war die Stelle, an der hunderte Vorlagen bisher nicht
 * entstehen konnten.
 *
 * Die Einstellungen tragen ein Schema, weil dieselbe Angabe zwei Aufgaben
 * hat: das Formular im Panel bauen UND die Eingabe pruefen. Zwei Listen mit
 * denselben Grenzen laufen frueher oder spaeter auseinander - eine reicht.
 *
 * Rein wie DesignSections: keine Datenbank, keine Sitzung, kein $_POST.
 */
final class SectionRegistry
{
    /** Wie die Variante heisst, die jede Art mindestens hat. */
    public const DEFAULT_VARIANT = 'default';

    /**
     * Was jeder Abschnitt kann, egal welcher Art.
     *
     * Sechsmal dieselben zwei Zeilen in den Katalog zu schreiben waere
     * Rauschen - und die siebte Art haette sie dann vergessen.
     *
     * "space" ist die Luft UNTER dem Abschnitt, nicht darueber: oben sitzt
     * das gerechnete Polster, mit dem der Titel zwischen die Goldlinien des
     * Blattes faellt (siehe DesignSections::baseline).
     */
    private const GRUND = [
        'align' => [
            'type'    => 'select',
            'options' => ['left', 'center', 'right'],
            'default' => 'center',
            'label'   => ['de' => 'Ausrichtung', 'tr' => 'Hizalama'],
        ],
        'space' => [
            'type'    => 'select',
            'options' => ['eng', 'normal', 'weit'],
            'default' => 'normal',
            'label'   => ['de' => 'Luft darunter', 'tr' => 'Alt boşluk'],
        ],
    ];

    /**
     * Der Katalog.
     *
     * Bewusst duenn besetzt: hier steht nur, was DesignSections auch wirklich
     * druckt. Eine Variante anzubieten, die aussieht wie die Voreinstellung,
     * waere ein Versprechen, das die Vorlage nicht haelt - der Grafiker
     * waehlt sie einmal, sieht keinen Unterschied und traut dem Katalog
     * danach nicht mehr.
     *
     * @var array<string,array{variants:array<string,array<string,string>>,settings:array<string,array<string,mixed>>}>
     */
    private const KATALOG = [
        'location' => [
            'variants' => [
                'default' => ['de' => 'Schlicht', 'tr' => 'Sade'],
                // Der Saal traegt den Namen, die Strasse steht klein
                // darunter, und der Weg dorthin ist ein Knopf statt einer
                // unterstrichenen Zeile: auf einer Einladung liest sich ein
                // Link wie ein Fremdkoerper.
                'gross'   => ['de' => 'Grosser Name', 'tr' => 'Büyük ad'],
            ],
            'settings' => [
                // Der Kartenlink ist nicht immer erwuenscht: bei einer
                // Adresse, die Google nicht kennt, fuehrt er ins Leere.
                'map' => [
                    'type'    => 'bool',
                    'default' => true,
                    'label'   => ['de' => 'Link zur Route', 'tr' => 'Yol tarifi bağlantısı'],
                ],
            ],
        ],
        'countdown' => [
            'variants' => [
                'default' => ['de' => 'Zahl über dem Datum', 'tr' => 'Tarihin üstünde sayı'],
                // Dieselbe Zahl, nur laut. Sie aendert die Groesse und nicht
                // den Bau: ohne Skript bleibt der Span leer, und dann traegt
                // das gedruckte Datum den Abschnitt allein - das muss auch in
                // dieser Gestalt gelten.
                'gross'   => ['de' => 'Grosse Zahl', 'tr' => 'Büyük sayı'],
            ],
            'settings' => [],
        ],
        'family' => [
            'variants' => [
                'default' => ['de' => 'Untereinander', 'tr' => 'Alt alta'],
                // Zwei Familien nebeneinander, ein Strich dazwischen - die
                // aeltere Form auf gedruckten Einladungen.
                'paar'    => ['de' => 'Nebeneinander', 'tr' => 'Yan yana'],
            ],
            'settings' => [],
        ],
        'program' => [
            'variants' => [
                'default' => ['de' => 'Zwei Spalten', 'tr' => 'İki sütun'],
                // Die erste zweite Gestalt im Haus. Sie beweist den Weg vom
                // Katalog ueber das Formular bis in den Stilblock - und sie
                // ist der Fall, in dem er sich lohnt: ein Ablauf mit acht
                // Zeilen liest sich als Strahl besser denn als Tabelle.
                'zeitstrahl' => ['de' => 'Zeitstrahl', 'tr' => 'Zaman çizgisi'],
            ],
            'settings' => [],
        ],
        'rsvp' => [
            'variants' => [
                'default' => ['de' => 'Formular', 'tr' => 'Form'],
                // Das Formular in einem Rahmen: auf einem gemusterten Blatt
                // verliert es sonst seine Kante und wirkt wie hingefallen.
                'rahmen'  => ['de' => 'Im Rahmen', 'tr' => 'Çerçeveli'],
            ],
            'settings' => [],
        ],
        'text' => [
            'variants' => [
                'default'   => ['de' => 'Fliesstext', 'tr' => 'Akan metin'],
                // Linksbuendig, schmale Spalte, Initial - fuer die laengeren
                // Bloecke: eine Geschichte in zentrierten Zeilen liest sich
                // wie ein Gedicht, und das war selten die Absicht.
                'editorial' => ['de' => 'Editorial', 'tr' => 'Editoryal'],
            ],
            'settings' => [],
            'inputs' => [
                'text' => [
                    'type'  => 'textarea',
                    'max'   => 1200,
                    // Drei Sprachen, weil der Assistent sie spricht - die
                    // Einstellungen daneben sieht nur der Grafiker, und der
                    // liest Deutsch oder Tuerkisch.
                    'label' => ['de' => 'Euer Text', 'en' => 'Your text', 'tr' => 'Metniniz'],
                ],
            ],
        ],

        /*
         * Der Schluss. Er zeigt etwas, das sonst nirgends steht - das
         * Zeichen, unter dem die Gaeste ihre Bilder ablegen sollen. Ohne das
         * waere er eine Gestalt des Textblocks und keine eigene Art.
         */
        'footer' => [
            'variants' => [
                'default' => ['de' => 'Schlicht', 'tr' => 'Sade'],
                // Ein Haarstrich darueber: der Schluss soll sich vom
                // Abschnitt darueber loesen, ohne laut zu werden.
                'linie'   => ['de' => 'Mit Strich', 'tr' => 'Çizgili'],
            ],
            'settings' => [],
            'inputs' => [
                'text' => [
                    'type'  => 'textarea',
                    'max'   => 400,
                    'label' => ['de' => 'Schlusswort', 'en' => 'Closing words', 'tr' => 'Kapanış sözü'],
                ],
                'hashtag' => [
                    'type'  => 'text',
                    'max'   => 60,
                    'label' => ['de' => 'Hashtag (ohne #)', 'en' => 'Hashtag (without #)', 'tr' => 'Hashtag (# olmadan)'],
                ],
            ],
        ],

        /*
         * Die Kontonummer. Sie ist der Grund, warum das eine eigene Art ist
         * und keine Gestalt des Textblocks: eine IBAN will gelesen und
         * abgeschrieben werden, also steht sie in eigenen Vierergruppen und
         * nicht mitten im Fliesstext.
         */
        'gift' => [
            'variants' => [
                'default' => ['de' => 'Schlicht', 'tr' => 'Sade'],
                'rahmen'  => ['de' => 'Im Rahmen', 'tr' => 'Çerçeveli'],
            ],
            'settings' => [],
            'inputs' => [
                'text' => [
                    'type'  => 'textarea',
                    'max'   => 600,
                    'label' => ['de' => 'Worte dazu', 'en' => 'A few words', 'tr' => 'Açıklama'],
                ],
                'holder' => [
                    'type'  => 'text',
                    'max'   => 80,
                    'label' => ['de' => 'Kontoinhaber', 'en' => 'Account holder', 'tr' => 'Hesap sahibi'],
                ],
                'iban' => [
                    'type'  => 'text',
                    'max'   => 40,
                    'label' => ['de' => 'IBAN', 'en' => 'IBAN', 'tr' => 'IBAN'],
                ],
            ],
        ],

        /*
         * Musik. Der Track gehoert dem Grafiker und nicht dem Paar - deshalb
         * eine Einstellung und kein Eingabefeld: die Vorlage bringt ihren
         * Klang mit, so wie sie ihre Schrift mitbringt.
         *
         * Ein Knopf und kein Selbststart. Browser blockieren Ton ohne
         * Zutun, und selbst wenn nicht: eine Einladung, die von allein
         * anfaengt zu spielen, wird im Buero geoeffnet und sofort geschlossen.
         */
        'music' => [
            'variants' => [
                'default' => ['de' => 'Kleiner Spieler', 'tr' => 'Küçük çalar'],
            ],
            'settings' => [
                'track' => [
                    'type'    => 'src',
                    'default' => '',
                    'label'   => ['de' => 'Tonspur (Pfad)', 'tr' => 'Ses dosyası (yol)'],
                ],
            ],
            'inputs' => [],
        ],
    ];

    /**
     * Der ganze Katalog, mit den Grundeinstellungen bereits eingemischt.
     *
     * @return array<string,array{variants:array<string,array<string,string>>,settings:array<string,array<string,mixed>>}>
     */
    public static function all(): array
    {
        $out = [];

        foreach (self::KATALOG as $art => $eintrag) {
            $eintrag['settings'] = self::settings($art);
            $out[$art] = $eintrag;
        }

        return $out;
    }

    /**
     * Startsaetze: die uebliche Reihenfolge, einmal hingelegt.
     *
     * Eine leere Vorlage ist die Stelle, an der Bauen am laengsten dauert.
     * Man weiss, dass unter der Karte etwas stehen soll - aber jede Zeile
     * muss erst angelegt, benannt und einsortiert werden. elysee und noir,
     * die zwei gemessenen Vorlagen des Hauses, hatten deshalb bis heute NULL
     * Abschnitte: nicht, weil dort nichts hingehoert, sondern weil der Weg
     * dorthin zu lang war.
     *
     * Ein Anfang und kein Urteil: danach ist alles wie sonst - schieben,
     * umbenennen, wegnehmen. Deshalb legen die Saetze auch nur Arten hin, die
     * es gibt, und keine Inhalte; was drinsteht, kommt vom Paar.
     *
     * Die Reihenfolge ist die Dramaturgie einer Einladung: erst wo und wann,
     * dann wer, dann was passiert, dann die Frage - und der freie Text
     * dazwischen, wo das Paar etwas zu sagen hat.
     *
     * @var array<string,array{label:array<string,string>,sections:list<array<string,mixed>>}>
     */
    private const START = [
        'klassisch' => [
            'label' => ['de' => 'Klassische Hochzeit', 'tr' => 'Klasik düğün'],
            'sections' => [
                ['type' => 'location',  'title' => ['de' => 'Wo wir feiern',  'en' => 'Where we celebrate']],
                ['type' => 'countdown', 'title' => ['de' => 'Noch',           'en' => 'Still']],
                ['type' => 'family',    'title' => ['de' => 'Unsere Familien', 'en' => 'Our families']],
                ['type' => 'program',   'title' => ['de' => 'Ablauf des Tages', 'en' => 'The day']],
                ['type' => 'text',      'title' => ['de' => 'Gut zu wissen',  'en' => 'Good to know']],
                ['type' => 'rsvp',      'title' => ['de' => 'Kommt ihr?',     'en' => 'Are you coming?']],
            ],
        ],
        'schlicht' => [
            'label' => ['de' => 'Schlicht', 'tr' => 'Sade'],
            'sections' => [
                ['type' => 'location', 'title' => ['de' => 'Wo wir feiern', 'en' => 'Where we celebrate']],
                ['type' => 'text',     'title' => ['de' => 'Gut zu wissen', 'en' => 'Good to know']],
                ['type' => 'rsvp',     'title' => ['de' => 'Kommt ihr?',    'en' => 'Are you coming?']],
            ],
        ],
        'ablauf' => [
            'label' => ['de' => 'Mit ausführlichem Ablauf', 'tr' => 'Ayrıntılı programlı'],
            'sections' => [
                ['type' => 'location',  'title' => ['de' => 'Wo wir feiern', 'en' => 'Where we celebrate']],
                ['type' => 'countdown', 'title' => ['de' => 'Noch',          'en' => 'Still'],
                 'variant' => 'gross'],
                ['type' => 'program',   'title' => ['de' => 'Ablauf des Tages', 'en' => 'The day'],
                 'variant' => 'zeitstrahl'],
                ['type' => 'family',    'title' => ['de' => 'Unsere Familien', 'en' => 'Our families'],
                 'variant' => 'paar'],
                ['type' => 'rsvp',      'title' => ['de' => 'Kommt ihr?',    'en' => 'Are you coming?'],
                 'variant' => 'rahmen'],
            ],
        ],
    ];

    /**
     * @return array<string,array{label:array<string,string>,sections:list<array<string,mixed>>}>
     */
    public static function starters(): array
    {
        return self::START;
    }

    /**
     * @return array{label:array<string,string>,sections:list<array<string,mixed>>}|null
     */
    public static function starter(string $name): ?array
    {
        return self::START[$name] ?? null;
    }

    /**
     * Was das Paar in einen Abschnitt dieser Art schreibt.
     *
     * Getrennt von den Einstellungen, weil es einer anderen Person gehoert:
     * Einstellungen dreht der Grafiker, Eingaben fuellt das Paar. Sie landen
     * unter der KENNUNG des Abschnitts (data.sections.<id>.<schluessel>) und
     * nicht unter einem festen Namen - ein Dokument kann zwei Textbloecke
     * tragen, und ein fester Name waere fuer beide derselbe.
     *
     * @return array<string,array<string,mixed>>
     */
    public static function inputs(string $type): array
    {
        return self::KATALOG[$type]['inputs'] ?? [];
    }

    /**
     * Die Einstellungen, die JEDE Art hat.
     *
     * Das Panel braucht die Trennung: die gemeinsamen stehen bei jeder Zeile,
     * die eigenen nur bei ihrer Art. Ohne diese Auskunft muesste das Panel
     * raten, welcher Schluessel woher kommt - und raten heisst hier, eine
     * zweite Liste zu fuehren.
     *
     * @return array<string,array<string,mixed>>
     */
    public static function commonSettings(): array
    {
        return self::GRUND;
    }

    public static function has(string $type): bool
    {
        return isset(self::KATALOG[$type]);
    }

    /**
     * Die Varianten einer Art: Kennung => Etikett je Sprache.
     *
     * @return array<string,array<string,string>>
     */
    public static function variants(string $type): array
    {
        return self::KATALOG[$type]['variants'] ?? [];
    }

    /**
     * Auch fuer eine unbekannte Art ein Wort und nicht leer: der Aufrufer
     * setzt es in ein Dokument, und ein leerer Variantenname waere ein Loch,
     * das erst im Stilblock auffiele.
     */
    public static function defaultVariant(string $type): string
    {
        return self::DEFAULT_VARIANT;
    }

    public static function isVariant(string $type, string $variant): bool
    {
        return isset(self::KATALOG[$type]['variants'][$variant]);
    }

    /**
     * Das Schema der Einstellungen einer Art - Grund plus Eigenes.
     *
     * @return array<string,array<string,mixed>>
     */
    public static function settings(string $type): array
    {
        if (!self::has($type)) {
            return [];
        }

        return self::GRUND + (self::KATALOG[$type]['settings'] ?? []);
    }

    /**
     * Rohe Werte in gueltige Einstellungen.
     *
     * Dieselbe Haltung wie in Design::complete(): was danebenliegt, faellt
     * auf die Voreinstellung, und was nicht im Schema steht, faellt weg. Ein
     * Dokument soll sich nicht wegen eines Werts aus dem Panel nicht mehr
     * oeffnen lassen - und ein fremder Schluessel soll nicht jahrelang
     * mitreisen, bloss weil ihn einmal jemand geschickt hat.
     *
     * @param array<string,mixed> $roh
     * @return array<string,mixed>
     */
    public static function completeSettings(string $type, array $roh): array
    {
        $out = [];

        foreach (self::settings($type) as $schluessel => $schema) {
            $wert = $roh[$schluessel] ?? null;

            $out[$schluessel] = match ((string) $schema['type']) {
                'select' => in_array($wert, $schema['options'], true)
                    ? (string) $wert
                    : (string) $schema['default'],
                // Ein leerer Wert ist ein abgeraeumter Haken und kein
                // fehlender Wert - deshalb (bool) und nicht "?? default".
                'bool'   => $wert === null ? (bool) $schema['default'] : (bool) $wert,
                'number' => max(
                    (int) $schema['min'],
                    min((int) $schema['max'], $wert === null ? (int) $schema['default'] : (int) $wert)
                ),
                /*
                 * Ein Pfad, und zwar einer aus dem eigenen Haus.
                 *
                 * Design::safeSrc wirft alles weg, was auf einen fremden Host
                 * zeigt - dieselbe Pruefung wie bei Bildern, Filmen und dem
                 * Blatt der Abschnitte. Eine Tonspur von einem fremden Server
                 * waere ein Hoerer, der protokolliert, wer die Einladung
                 * geoeffnet hat, und ein Link, der eines Tages ins Leere
                 * fuehrt.
                 */
                'src'    => Design::safeSrc((string) ($wert ?? $schema['default'])),
                default  => $schema['default'],
            };
        }

        return $out;
    }
}
