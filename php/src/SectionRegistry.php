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
                /*
                 * Das Kartenbild. Es kommt von unserem Server, nicht aus
                 * einem iframe - der Browser des Gastes spricht mit
                 * niemandem sonst (siehe StaticMap). Deshalb darf es hier
                 * an sein, ohne die Einwilligung zu beruehren.
                 *
                 * Eine Auswahl und kein Haken: die Form gehoert zur
                 * Entscheidung. Ein Haken plus ein zweites Feld "Form" waeren
                 * zwei Fragen fuer eine Sache, und die zweite stuende auch
                 * dann da, wenn die erste sie gegenstandslos macht.
                 *
                 * "aus" bleibt noetig aus demselben Grund wie beim Link:
                 * kennt der Kartendienst die Adresse nicht, bleibt das Bild
                 * leer, und dann steht der Ort besser allein da. Ohne
                 * Adresse - nur mit Saalnamen - erscheint es ohnehin nicht.
                 */
                'karte' => [
                    'type'    => 'select',
                    'options' => ['blatt', 'rechteck', 'aus'],
                    'default' => 'blatt',
                    'label'   => ['de' => 'Kleine Karte', 'tr' => 'Küçük harita'],
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
                /*
                 * Vier Felder statt einer Zahl: Tage, Stunden, Minuten,
                 * Sekunden - dieselbe Uhr, die die erste Einladung schon
                 * hatte (public/assets/invitation.js).
                 *
                 * Hier stand bis heute nur die Tageszahl, mit der
                 * Begruendung "eine Einladung ist kein Wecker". Das ist eine
                 * Haltung und keine Regel: eine Einladung, die eine Woche
                 * vorher aufgemacht wird, will genau das sein. Die ruhige
                 * Gestalt bleibt als eigene Variante stehen - der Grafiker
                 * waehlt, statt dass die Vorlage entscheidet.
                 */
                'uhr'     => ['de' => 'Tage · Stunden · Minuten · Sekunden',
                              'tr' => 'Gün · saat · dakika · saniye'],
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
        /*
         * Die Speisekarte. Sechs Gaenge, und keiner davon ist Pflicht.
         *
         * "Kullanici ne doldurduysa sadece onlar gorunur" - genau deshalb ist
         * jeder Gang eine EINGABE und keine Einstellung: Eingaben fuellt das
         * Paar, und was leer bleibt, wird nicht gedruckt. Ein Abschnitt ohne
         * einen einzigen Gang faellt ganz weg.
         *
         * Die Reihenfolge hier ist die Reihenfolge auf der Karte, und das ist
         * die, in der serviert wird.
         *
         * Das Zeichen steht am Gang und nicht in der Vorlage: eine Suppe soll
         * in jeder Vorlage wie eine Suppe aussehen. Ihre Farbe bekommt sie
         * ohnehin von der Vorlage, das genuegt.
         */
        'menu' => [
            'variants' => [
                'default' => ['de' => 'Untereinander', 'tr' => 'Alt alta'],
            ],
            'settings' => [],
            'inputs' => [
                'vorspeise' => ['type' => 'text', 'max' => 120, 'icon' => 'baslangic',
                    'label' => ['de' => 'Vorspeise', 'en' => 'Starter', 'tr' => 'Başlangıç']],
                'suppe' => ['type' => 'text', 'max' => 120, 'icon' => 'corba',
                    'label' => ['de' => 'Suppe', 'en' => 'Soup', 'tr' => 'Çorba']],
                'hauptgang' => ['type' => 'text', 'max' => 160, 'icon' => 'anayemek',
                    'label' => ['de' => 'Hauptgang', 'en' => 'Main course', 'tr' => 'Ana yemek']],
                'meze' => ['type' => 'text', 'max' => 160, 'icon' => 'meze',
                    'label' => ['de' => 'Meze', 'en' => 'Meze', 'tr' => 'Meze']],
                'dessert' => ['type' => 'text', 'max' => 120, 'icon' => 'tatli',
                    'label' => ['de' => 'Dessert', 'en' => 'Dessert', 'tr' => 'Tatlı']],
                'getraenk' => ['type' => 'text', 'max' => 120, 'icon' => 'icecek',
                    'label' => ['de' => 'Getränk', 'en' => 'Drinks', 'tr' => 'İçecek']],
            ],
        ],

        /*
         * Die Kleiderordnung. Zwei Eingaben, ein Zeichen.
         *
         * Der Code ist die Ansage ("Black Tie"), der Hinweis das, was sie im
         * Alltag bedeutet ("bequeme Schuhe fuer die Wiese"). Beide freiwillig,
         * und einer genuegt: eine Ansage ohne Erklaerung steht fuer sich, eine
         * Erklaerung ohne Ansage auch.
         *
         * Das vorbereitete Bild ist KEIN eigenes Feld. Jeder Abschnitt hat
         * sein eigenes Blatt; das ist der Ort dafuer. Ein zweiter Weg fuer
         * dasselbe waere ein zweiter Ort, an dem man suchen muss.
         */
        'dresscode' => [
            'variants' => [
                'default' => ['de' => 'Ansage über Hinweis', 'tr' => 'Kural, altında açıklama'],
            ],
            'settings' => [],
            'inputs' => [
                'code' => ['type' => 'text', 'max' => 80,
                    'label' => ['de' => 'Kleiderordnung', 'en' => 'Dress code', 'tr' => 'Kıyafet kuralı']],
                'note' => ['type' => 'textarea', 'max' => 300,
                    'label' => ['de' => 'Hinweis dazu', 'en' => 'A note about it', 'tr' => 'Açıklama']],
            ],
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
         * Die Bilder des Paares.
         *
         * Die einzige Art, deren Inhalt keine Zeichen sind, sondern Dateien.
         * Deshalb ein eigener Eingabetyp: der Assistent braucht ein Feld, das
         * mehrere Dateien nimmt, und der Controller einen Weg, sie
         * abzulegen - und zwar erst, wenn die Adresse der Einladung feststeht,
         * denn sie ist der Ordner.
         *
         * Acht Bilder. Nicht, weil mehr technisch nicht ginge, sondern weil
         * eine Einladung kein Album ist: wer zwanzig Bilder hinlegt, zwingt
         * jeden Gast, zwanzig zu laden, bevor er die Adresse der Feier sieht.
         */
        'gallery' => [
            'variants' => [
                'default'  => ['de' => 'Raster', 'tr' => 'Izgara'],
                // Ein Streifen, der seitwaerts laeuft. Auf dem Telefon ist das
                // die natuerlichere Geste, und drei Bilder nebeneinander sind
                // dort ohnehin drei Briefmarken.
                'streifen' => ['de' => 'Streifen', 'tr' => 'Şerit'],
            ],
            'settings' => [],
            'inputs' => [
                'photos' => [
                    'type'  => 'photos',
                    'max'   => 8,
                    'label' => ['de' => 'Eure Bilder', 'en' => 'Your photos', 'tr' => 'Fotoğraflarınız'],
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
                /*
                 * Im Hintergrund, wie in der ersten Einladung.
                 *
                 * Hier stand "Kleiner Spieler" als einzige Gestalt: ein
                 * <audio controls> mitten im Blatt. Auf einer Einladung sieht
                 * das aus wie ein hineingefallenes Bedienfeld - und niemand
                 * drueckt darauf. Der Ton gehoert unter die Seite, nicht in
                 * sie.
                 *
                 * Gestartet wird beim Oeffnen des Umschlags: Browser lassen
                 * Ton nur nach einer Nutzeraktion zu, und das Antippen des
                 * Kuverts IST die Aktion. Deshalb hat diese Gestalt keinen
                 * Startknopf, sondern nur einen zum Stummschalten - der
                 * Start ist schon passiert.
                 */
                'default' => ['de' => 'Im Hintergrund', 'tr' => 'Arka planda'],
                // Die alte Gestalt, fuer den Fall, dass ein Blatt einen
                // sichtbaren Spieler tragen soll (ein Lied als Geschenk etwa,
                // das man bewusst anhoert und nicht nebenbei).
                'spieler' => ['de' => 'Kleiner Spieler', 'tr' => 'Küçük çalar'],
            ],
            'settings' => [
                'track' => [
                    'type'    => 'src',
                    /*
                     * Was fuer eine Datei das ist.
                     *
                     * "src" sagt nur "ein Pfad aus dem eigenen Haus" - es
                     * sagt nicht, ob ein Bild, ein Film oder ein Lied
                     * dahinter steht. Das Panel braucht es fuer das accept
                     * des Dateifeldes, der Controller fuer die Frage, welche
                     * Pruefung er nimmt: Media::storeAudio sieht in die
                     * Datei und laesst nur echte Tonspuren durch.
                     *
                     * Ohne diese Zeile muesste eine der beiden Stellen raten,
                     * und raten heisst hier: eine zweite Liste fuehren.
                     */
                    'kind'    => 'audio',
                    'default' => '',
                    'label'   => ['de' => 'Tonspur der Vorlage',
                                  'tr' => 'Tasarımın müziği'],
                ],
            ],
            /*
             * Und das Lied des Paares.
             *
             * Zwei Quellen fuer eine Tonspur, mit klarer Rangfolge: was das
             * Paar hochlaedt, gewinnt (DesignSections::musik). Die Spur der
             * Vorlage ist ein Vorschlag - sie steht da, wenn niemand etwas
             * hochlaedt, und verschwindet hinter dem eigenen Lied, sobald
             * eines da ist. Ein Paar, das seine Musik nicht hat, bekommt
             * trotzdem keine stumme Einladung.
             */
            'inputs' => [
                'track' => ['type' => 'audio', 'max' => 1,
                    'label' => ['de' => 'Euer Lied', 'en' => 'Your song', 'tr' => 'Şarkınız']],
            ],
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
     * Welche festen Angaben eine Art braucht, um ueberhaupt zu erscheinen.
     *
     * Nicht dasselbe wie inputs(): das sind eigene Felder unter der Kennung
     * des Abschnitts. Hier stehen die Angaben aus dem Kopf der Einladung -
     * Ort, Adresse, Datum -, die sich mehrere Abschnitte teilen.
     *
     * Warum es diese Liste gibt: der Assistent hat bis heute nur die Karte
     * gefragt. Welche Felder er anbietet, kam aus den bind-Namen der Ebenen
     * (DesignWizard::BIND_FIELDS) - also aus dem, was AUF dem Papier steht.
     * Ein Design, dessen Karte keine Adresse zeigt, aber unter der Karte
     * einen location-Abschnitt fuehrt, fragte deshalb nie nach der Adresse;
     * DesignSections::hatInhalt() warf den Abschnitt beim Drucken dann
     * stillschweigend weg. Fuer das Paar sah es aus, als fehlte der
     * Abschnitt - kein Feld, kein Hinweis, keine leere Zeile. Genau so ist
     * es den Vorlagen bild, film, video und 25aug ergangen.
     *
     * Ein Abschnitt sagt jetzt selbst, was er braucht, und der Assistent
     * fragt danach. Die Liste steht hier und nicht im Assistenten, weil sie
     * zur Art gehoert: eine neue Art mit eigenem Bedarf traegt ihn hier ein
     * und ist ueberall versorgt.
     *
     * @return list<string>
     */
    public static function needs(string $type): array
    {
        return match ($type) {
            // Der Saal traegt den Namen, die Strasse steht darunter - eines
            // von beiden reicht dem Abschnitt (siehe hatInhalt), gefragt
            // werden trotzdem beide: wer nur den Saal nennt, bekommt keine
            // Karte, und das soll seine Entscheidung sein und kein Zufall.
            'location'  => ['venue', 'address'],
            'countdown' => ['date'],
            default     => [],
        };
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

    /**
     * Die Zeichen: Kennung => Datei und Etikett.
     *
     * Ein Zeichen ist kein Bild, das jemand hochlaedt, sondern ein Eintrag
     * hier. Die Kennung steht spaeter im Dokument einer Vorlage und in der
     * Einladung eines Paares - sie ist damit dauerhaft und darf sich nicht
     * mehr aendern. Die DATEI dahinter darf jederzeit gegen eine schoenere
     * getauscht werden; das bricht nichts.
     *
     * Gefaerbt wird im Stilblock, nicht in der Datei: DesignSections legt sie
     * als Maske ueber eine Flaeche in currentColor. Deshalb liegt hier eine
     * einfarbige Zeichnung je Zeichen und nicht eine je Vorlage und Farbe.
     *
     * Herkunft: Lucide, ISC-Lizenz. Der Lizenztext liegt neben den Dateien in
     * public/assets/icons/LICENSE.txt und muss dort bleiben.
     */
    private const ZEICHEN = [
        'giris'      => ['file' => 'giris.svg',      'label' => ['de' => 'Empfang',    'tr' => 'Giriş'],
                        'title' => ['de' => 'Empfang', 'en' => 'Reception']],
        'nikah'      => ['file' => 'nikah.svg',      'label' => ['de' => 'Trauung',    'tr' => 'Nikâh'],
                        'title' => ['de' => 'Trauung', 'en' => 'Ceremony']],
        'dans'       => ['file' => 'dans.svg',       'label' => ['de' => 'Tanz',       'tr' => 'Dans'],
                        'title' => ['de' => 'Tanz', 'en' => 'Dancing']],
        'yemek'      => ['file' => 'yemek.svg',      'label' => ['de' => 'Essen',      'tr' => 'Yemek'],
                        'title' => ['de' => 'Abendessen', 'en' => 'Dinner']],
        'meze'       => ['file' => 'meze.svg',       'label' => ['de' => 'Vorspeisen', 'tr' => 'Meze'],
                        'title' => ['de' => 'Vorspeisen', 'en' => 'Starters']],
        'pasta'      => ['file' => 'pasta.svg',      'label' => ['de' => 'Torte',      'tr' => 'Pasta'],
                        'title' => ['de' => 'Tortenanschnitt', 'en' => 'Cutting the cake']],
        'fotograf'   => ['file' => 'fotograf.svg',   'label' => ['de' => 'Fotos',      'tr' => 'Fotoğraf'],
                        'title' => ['de' => 'Fotos', 'en' => 'Photos']],
        'afterparty' => ['file' => 'afterparty.svg', 'label' => ['de' => 'Afterparty', 'tr' => 'After party'],
                        'title' => ['de' => 'Afterparty', 'en' => 'Afterparty']],
        'dresscode'  => ['file' => 'dresscode.svg',  'label' => ['de' => 'Dresscode',  'tr' => 'Kıyafet'],
                        'title' => ['de' => 'Dresscode', 'en' => 'Dress code']],
        'konum'      => ['file' => 'konum.svg',      'label' => ['de' => 'Ort',        'tr' => 'Konum'],
                        'title' => ['de' => 'Ort', 'en' => 'Venue']],
        'saat'       => ['file' => 'saat.svg',       'label' => ['de' => 'Uhrzeit',    'tr' => 'Saat'],
                        'title' => ['de' => 'Beginn', 'en' => 'Start']],
        'hediye'     => ['file' => 'hediye.svg',     'label' => ['de' => 'Geschenk',   'tr' => 'Hediye'],
                        'title' => ['de' => 'Geschenke', 'en' => 'Gifts']],
        'muzik'      => ['file' => 'muzik.svg',      'label' => ['de' => 'Musik',      'tr' => 'Müzik'],
                        'title' => ['de' => 'Musik', 'en' => 'Music']],
        'baslangic'  => ['file' => 'baslangic.svg',  'label' => ['de' => 'Vorspeise',  'tr' => 'Başlangıç'],
                        'title' => ['de' => 'Vorspeise', 'en' => 'Starter']],
        'corba'      => ['file' => 'corba.svg',      'label' => ['de' => 'Suppe',      'tr' => 'Çorba'],
                        'title' => ['de' => 'Suppe', 'en' => 'Soup']],
        'anayemek'   => ['file' => 'anayemek.svg',   'label' => ['de' => 'Hauptgang',  'tr' => 'Ana yemek'],
                        'title' => ['de' => 'Hauptgang', 'en' => 'Main course']],
        'tatli'      => ['file' => 'tatli.svg',      'label' => ['de' => 'Dessert',    'tr' => 'Tatlı'],
                        'title' => ['de' => 'Dessert', 'en' => 'Dessert']],
        'icecek'     => ['file' => 'icecek.svg',     'label' => ['de' => 'Getränk',    'tr' => 'İçecek'],
                        'title' => ['de' => 'Getränke', 'en' => 'Drinks']],
    ];

    /**
     * Der ganze Katalog - fuer das Panel, das eine Liste zum Auswaehlen baut.
     *
     * @return array<string,array{file:string,label:array<string,string>}>
     */
    public static function icons(): array
    {
        return self::ZEICHEN;
    }

    /**
     * Die Adresse eines Zeichens, oder nichts.
     *
     * Nichts und nicht ein Ersatzzeichen: ein Abschnitt ohne Zeichen soll
     * keines zeigen, und eine Kennung, die es nicht gibt, ist dasselbe wie
     * keine. Der Weg ueber den Katalog heisst auch, dass aus dem Dokument
     * kein Pfad in die Seite gelangt - dort steht nur eine Kennung.
     */
    public static function iconFile(string $id): string
    {
        return isset(self::ZEICHEN[$id]) ? '/assets/icons/' . self::ZEICHEN[$id]['file'] : '';
    }

    /**
     * Was das Zeichen im Druck heisst - der Vorschlag fuer die Zeile.
     *
     * Das Etikett oben ist fuer das Panel (de/tr, der Grafiker sucht in
     * einer Liste). Hier stehen die Sprachen der SEITE (de/en), und die
     * Worte sind andere: in einer Liste heisst es "Torte", in einer Zeile
     * des Ablaufs "Tortenanschnitt".
     *
     * Vorgeschlagen, nicht vorgeschrieben: schreibt das Paar etwas, gewinnt
     * das Paar. Dieselbe Regel wie bei den Voreinstellungen der Abschnitte.
     */
    public static function iconTitle(string $id, string $locale): string
    {
        $eintrag = self::ZEICHEN[$id] ?? null;
        if ($eintrag === null) {
            return '';
        }

        return (string) ($eintrag['title'][$locale] ?? $eintrag['title']['de']);
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
