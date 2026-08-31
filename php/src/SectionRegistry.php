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
        /*
         * Der Rahmen um den Text.
         *
         * "Metinler sadece alt alta gosterilmemeli … cerceve icerisinde,
         * kart seklinde." Bis hierher stand jeder Abschnitt nackt auf dem
         * Blatt; wer eine Angabe hervorheben wollte, konnte nur ihre
         * Schrift aendern.
         *
         * Er umschliesst UEBERSCHRIFT UND INHALT, nicht nur den Text: eine
         * Ueberschrift, die ueber ihrem eigenen Rahmen schwebt, sieht aus
         * wie ein Fehler beim Setzen.
         *
         * "eigen" ist der wichtigste Eintrag der Liste: er nimmt die
         * transparente PNG, die der Grafiker selbst gezeichnet hat
         * (frameSrc daneben). Ein "floral" aus CSS-Strichen waere ein
         * Versprechen, das keine Zeichnung einloest - dafuer gibt es das
         * Feld darunter, und dort ist es eine echte Ranke.
         */
        'frame' => [
            'type'    => 'select',
            'options' => ['keine', 'linie', 'doppel', 'gold', 'papier', 'transparent', 'eigen'],
            'default' => 'keine',
            'label'   => ['de' => 'Rahmen', 'tr' => 'Çerçeve'],
        ],
        /*
         * Und die eigene Zeichnung dazu.
         *
         * Je Abschnitt und nicht je Vorlage: eine Einladung kann eine Ranke
         * um den Ablauf und eine schlichte Linie um die Anfahrt tragen. Der
         * Weg ist derselbe wie beim Blatt eines Abschnitts - hochladen,
         * Pfad bleibt sichtbar, leeren entfernt.
         *
         * Sie wird als border-image mit "fill" gelegt: die Mitte der Datei
         * traegt den Grund, die Raender die Zierde. Eine PNG mit
         * durchsichtiger Mitte gibt damit einen Rahmen, durch den das Blatt
         * sichtbar bleibt - genau das, was eine transparente Zeichnung soll.
         */
        'frameSrc' => [
            'type'    => 'src',
            // 'bild' ist das Wort, das der Katalog schon kennt (neben
            // 'audio' und 'video'); der Controller waehlt danach seine
            // Pruefung, das Panel sein accept.
            'kind'    => 'bild',
            'default' => '',
            'label'   => ['de' => 'Eigener Rahmen (PNG)', 'tr' => 'Kendi çerçeven (PNG)'],
        ],

        /*
         * Die Luft UEBER dem Abschnitt.
         *
         * "auto" ist die Voreinstellung und bedeutet: das gerechnete
         * Polster, mit dem der Titel zwischen die Goldlinien des Blattes
         * faellt (DesignSections::baseline - 56 %, hergeleitet aus dem
         * Seitenverhaeltnis des Blattes). An dieser Zahl darf ein Knopf im
         * Panel nicht versehentlich drehen; wer sie ueberschreibt, tut es
         * ausdruecklich.
         *
         * Es GIBT den Knopf jetzt trotzdem, weil die alte Begruendung nur
         * fuer Vorlagen mit diesem Blatt trug. Eine Vorlage ohne Goldlinien
         * traegt 56 % Polster ohne Grund - das war die Beschwerde: "boslukar
         * daha duzenli olsun".
         */
        'spaceTop' => [
            'type'    => 'select',
            'options' => ['auto', 'xs', 's', 'm', 'l', 'xl'],
            'default' => 'auto',
            'label'   => ['de' => 'Luft darüber', 'tr' => 'Üst boşluk'],
        ],
        /*
         * Und darunter.
         *
         * Frueher drei Worte (eng / normal / weit), jetzt eine Leiter von
         * fuenf. Die alten drei bleiben als Alias erhalten und treffen
         * genau ihre alten Werte: eng = s = 6 %, normal = m = 12 %,
         * weit = l = 22 %. Ohne das faende completeSettings() sie nicht in
         * der Liste, setzte stillschweigend die Voreinstellung - und jede
         * Vorlage auf dem Demoserver, die "weit" gewaehlt hatte, rueckte
         * beim naechsten Deploy zusammen.
         */
        /*
         * Die MINDESTHOEHE eines Abschnitts.
         *
         * "Her bolumun yuksekligi ayarlanabilmeli … bir bolumden digerine
         * gecerken cok buyuk bosluklar olusmaz."
         *
         * Eine Mindesthoehe und keine Hoehe: was drinsteht, darf immer
         * groesser werden. Eine feste Hoehe waere eine Zusage, die der
         * Inhalt jederzeit bricht - und dann steht Text ueber der Kante.
         *
         * Wer eine Hoehe setzt, bekommt den Inhalt SENKRECHT ZENTRIERT
         * dazu. Ohne das waere die Hoehe nur Luft unten, also genau das,
         * worueber die Beschwerde ging.
         *
         * vh und nicht Prozent: hier geht es um das Verhaeltnis zum
         * Bildschirm ("eine Seite pro Abschnitt"), nicht zur Breite. Das ist
         * der eine Ort, an dem das die richtige Frage ist.
         */
        'height' => [
            'type'    => 'select',
            'options' => ['auto', 's', 'm', 'l', 'voll'],
            'default' => 'auto',
            'label'   => ['de' => 'Mindesthöhe', 'tr' => 'En az yükseklik'],
        ],
        'space' => [
            'type'    => 'select',
            'options' => ['xs', 's', 'm', 'l', 'xl'],
            'aliases' => ['eng' => 's', 'normal' => 'm', 'weit' => 'l'],
            'default' => 'm',
            'label'   => ['de' => 'Luft darunter', 'tr' => 'Alt boşluk'],
        ],
    ];

    /**
     * Wie die Arten heissen - und wozu sie da sind.
     *
     * Die KENNUNG bleibt englisch und klein: sie steht im Dokument jeder
     * Vorlage und in den Daten jeder verschickten Einladung, und sie zu
     * uebersetzen hiesse, alte Einladungen unlesbar zu machen. Uebersetzt
     * wird nur, was der Grafiker im Panel liest.
     *
     * Warum das eine eigene Liste ist und keine Zeile im Katalog: der
     * Katalog beschreibt, was eine Art KANN (Varianten, Einstellungen,
     * Eingaben). Wie sie heisst, ist Oberflaeche - und Oberflaeche gehoert
     * an einen Ort, an dem man sie ohne Angst umformulieren kann.
     *
     * Der Anlass: das Panel druckte bisher den rohen Schluessel. Der Kunde
     * fragte "Gift ne oluyor" und "Footer ney", lud dann ein Bild in "gift"
     * hoch und wartete darauf, dass es erscheint. "gift" ist die Art mit der
     * Kontonummer; Bilder gehoeren in "gallery". Ein Wort haette gereicht.
     *
     * Deshalb steht neben dem Namen ein Halbsatz. Er beantwortet nicht "wie
     * heisst das", sondern "was kommt da hinein" - und genau daran ist die
     * halbe Stunde verlorengegangen.
     *
     * @var array<string,array{label:array<string,string>,hint:array<string,string>}>
     */
    private const NAMEN = [
        'location' => [
            'label' => ['de' => 'Ort & Anfahrt', 'en' => 'Venue & route', 'tr' => 'Konum & yol'],
            'hint'  => ['de' => 'Saalname, Adresse, kleine Karte, Route',
                        'en' => 'Venue name, address, small map, route',
                        'tr' => 'Salon adı, adres, küçük harita, yol tarifi'],
        ],
        'date' => [
            'label' => ['de' => 'Datum', 'en' => 'Date', 'tr' => 'Tarih'],
            'hint'  => ['de' => 'Der Tag selbst — gross gesetzt statt in einer Zeile',
                        'en' => 'The day itself — set large instead of in one line',
                        'tr' => 'Günün kendisi — satır içinde değil, büyük'],
        ],
        'countdown' => [
            'label' => ['de' => 'Countdown', 'en' => 'Countdown', 'tr' => 'Geri sayım'],
            'hint'  => ['de' => 'Zählt bis zum Datum der Feier',
                        'en' => 'Counts down to the date of the celebration',
                        'tr' => 'Düğün tarihine kadar sayar'],
        ],
        'family' => [
            'label' => ['de' => 'Familien', 'en' => 'Families', 'tr' => 'Aileler'],
            'hint'  => ['de' => 'Die Namen der beiden Familien',
                        'en' => 'The names of the two families',
                        'tr' => 'İki ailenin adı'],
        ],
        'program' => [
            'label' => ['de' => 'Ablauf des Tages', 'en' => 'The day', 'tr' => 'Günün programı'],
            'hint'  => ['de' => 'Uhrzeit, Zeichen und was dann passiert',
                        'en' => 'Time, icon and what happens then',
                        'tr' => 'Saat, simge ve o saatte ne olduğu'],
        ],
        'rsvp' => [
            'label' => ['de' => 'Rückmeldung', 'en' => 'RSVP', 'tr' => 'Katılım formu'],
            'hint'  => ['de' => 'Das Formular, mit dem Gäste zu- oder absagen',
                        'en' => 'The form guests answer with',
                        'tr' => 'Davetlinin gelip gelmediğini yazdığı form'],
        ],
        'text' => [
            'label' => ['de' => 'Freier Text', 'en' => 'Free text', 'tr' => 'Serbest metin'],
            'hint'  => ['de' => 'Ein Absatz, den das Paar selbst schreibt',
                        'en' => 'A paragraph the couple writes itself',
                        'tr' => 'Çiftin kendi yazdığı bir paragraf'],
        ],
        'footer' => [
            'label' => ['de' => 'Schluss', 'en' => 'Closing', 'tr' => 'Alt bilgi'],
            'hint'  => ['de' => 'Ganz unten: Schlusswort, Hashtag, Hinweis auf uns',
                        'en' => 'At the very bottom: closing words, hashtag, credit',
                        'tr' => 'En altta: kapanış sözü, hashtag, bize yönlendirme'],
        ],
        /*
         * "Geschenk" und nicht "gift": das englische Wort liest sich fuer
         * einen tuerkischsprachigen Betrachter wie ein Bilderformat, und
         * genau so wurde es benutzt.
         */
        'gift' => [
            'label' => ['de' => 'Geschenk & Konto', 'en' => 'Gift & account', 'tr' => 'Hediye & hesap'],
            'hint'  => ['de' => 'Wunsch und IBAN — KEINE Bilder',
                        'en' => 'A wish and an IBAN — NO photos',
                        'tr' => 'Dilek ve IBAN — resim BURAYA değil'],
        ],
        'music' => [
            'label' => ['de' => 'Musik', 'en' => 'Music', 'tr' => 'Müzik'],
            'hint'  => ['de' => 'Eigene Tondatei oder ein YouTube-Rahmen',
                        'en' => 'Your own audio file or a YouTube frame',
                        'tr' => 'Kendi ses dosyanız ya da YouTube çerçevesi'],
        ],
        'gallery' => [
            'label' => ['de' => 'Fotos', 'en' => 'Photos', 'tr' => 'Fotoğraflar'],
            'hint'  => ['de' => 'Bis zu acht Bilder des Paares — hierhin gehören sie',
                        'en' => 'Up to eight photos of the couple — this is where they go',
                        'tr' => 'Çiftin sekiz fotoğrafına kadar — resimlerin yeri burası'],
        ],
        'menu' => [
            'label' => ['de' => 'Speisekarte', 'en' => 'Menu', 'tr' => 'Menü'],
            'hint'  => ['de' => 'Was serviert wird, Gang für Gang',
                        'en' => 'What is served, course by course',
                        'tr' => 'Servis edilenler, sırayla'],
        ],
        'dresscode' => [
            'label' => ['de' => 'Dresscode', 'en' => 'Dress code', 'tr' => 'Kıyafet'],
            'hint'  => ['de' => 'Die Ansage und ein Hinweis dazu',
                        'en' => 'The rule and a note about it',
                        'tr' => 'Kural ve altındaki açıklama'],
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
                /*
                 * "eigen" ist neu: "Kendi haritali resmimi ekleyebilmeliyim.
                 * Gercek haritayi optional cikarabilmeliyim kendi harita
                 * resmimi yuklemek icin."
                 *
                 * Eine gezeichnete Karte ist auf einer Einladung oft die
                 * bessere: sie zeigt drei Strassen und den Saal, statt einen
                 * Kartendienst nachzumalen. "aus" gab es schon und bleibt -
                 * es ist die Antwort fuer eine Adresse, die niemand kennt.
                 */
                'karte' => [
                    'type'    => 'select',
                    'options' => ['blatt', 'rechteck', 'eigen', 'aus'],
                    'default' => 'blatt',
                    'label'   => ['de' => 'Kleine Karte', 'tr' => 'Küçük harita'],
                ],
                // Die eigene Zeichnung dazu. Sie ersetzt das gerechnete
                // Kartenbild vollstaendig - der Weg zur Route bleibt, er
                // haengt an der Adresse und nicht am Bild.
                'mapSrc' => [
                    'type'    => 'src',
                    'kind'    => 'bild',
                    'default' => '',
                    'label'   => ['de' => 'Eigenes Kartenbild (für "eigen")',
                                  'tr' => 'Kendi harita resmin ("eigen" için)'],
                ],
                /*
                 * Und ein Film statt eines Bildes.
                 *
                 * Ein zweites Feld und kein gemeinsames: der Controller
                 * waehlt seine Pruefung nach 'kind' (Media::storeGraphic
                 * gegen storeVideo), und ein Feld, das beides annimmt,
                 * muesste eine der beiden Pruefungen aufweichen.
                 *
                 * Er gewinnt gegen das Bild, wenn beide hinterlegt sind: wer
                 * einen Film hochlaedt, hat sich fuer ihn entschieden. Das
                 * Bild bleibt liegen und ist mit einem Klick wieder da.
                 *
                 * Durchsichtige Filme funktionieren hier wie ueberall:
                 * Media::storeVideo legt eine WebM ab, wie sie kommt - es
                 * wird nicht umkodiert, also ueberlebt der Alphakanal.
                 */
                'mapVideo' => [
                    'type'    => 'src',
                    'kind'    => 'video',
                    'default' => '',
                    'label'   => ['de' => 'Eigener Kartenfilm (für "eigen")',
                                  'tr' => 'Kendi harita videon ("eigen" için)'],
                ],
                /*
                 * Wie gross die Karte sitzt.
                 *
                 * "Haritanin boyunu kucultmeli mesela." Bis hierher stand
                 * sie auf 22rem, fuer jede Vorlage gleich - und auf einer
                 * kompakten Einladung ist das der groesste Kasten weit und
                 * breit.
                 */
                'mapSize' => [
                    'type'    => 'select',
                    'options' => ['s', 'm', 'l', 'voll'],
                    'default' => 'm',
                    'label'   => ['de' => 'Grösse der Karte', 'tr' => 'Harita boyutu'],
                ],
            ],
        ],
        /*
         * Der Tag, gross gesetzt.
         *
         * "TARIH / 08 / AGUSTOS / 2026 — burada 08 cok buyuk olabilir,
         * digerleri ise daha kucuk ve zarif." Bis hierher gab es das Datum
         * nur als Zeile unter dem Countdown und als Zeile auf der Karte -
         * beide Male klein und beide Male in einem anderen Abschnitt.
         *
         * Eine eigene Art und keine Gestalt des Countdowns: der Countdown
         * zaehlt und verschwindet nach der Feier (hatInhalt), das Datum
         * steht. Zwei Aufgaben, zwei Arten.
         */
        'date' => [
            'variants' => [
                // Wochentag und ausgeschriebenes Datum, wie es die Karte
                // seit jeher druckt.
                'default' => ['de' => 'Ausgeschrieben', 'tr' => 'Yazıyla'],
                /*
                 * Die grosse Zahl. Genau die Anordnung aus der Anfrage: der
                 * Tag traegt die Rolle "grosse Zahl", Monat und Jahr die
                 * Rolle "kleiner Hinweis".
                 */
                'gross'   => ['de' => 'Grosse Zahl', 'tr' => 'Büyük rakam'],
                // Eine Zeile mit Haarstrichen links und rechts - fuer
                // Vorlagen, die das Datum nicht zum Denkmal machen wollen.
                'zeile'   => ['de' => 'Eine Zeile mit Strichen', 'tr' => 'Çizgili tek satır'],
            ],
            'settings' => [],
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
                /*
                 * "10 GUN — altinda: 23 SAAT · 31 DAKIKA · 54 SANIYE."
                 *
                 * Die Tage gross, der Rest als eine leise Zeile darunter.
                 * Sie benutzt denselben Vertrag wie die Uhr (das Skript
                 * fuellt vier Felder, sobald [data-countdown-hours] da ist)
                 * und ordnet ihn nur anders an - kein zweiter Zaehler.
                 */
                'tage'    => ['de' => 'Grosse Tageszahl, Rest darunter',
                              'tr' => 'Büyük gün sayısı, altında kalanı'],
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
                // "Ayri kucuk kartlar." Jede Station steht fuer sich, mit
                // ihrem Zeichen darueber - auf dem Telefon untereinander,
                // auf dem Schreibtisch nebeneinander.
                'karten'     => ['de' => 'Einzelne Kärtchen', 'tr' => 'Ayrı küçük kartlar'],
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
                // Frauen links, Maenner rechts. Auf dem Telefon
                // untereinander - zwei Spalten auf 320 px waeren zwei
                // Spalten mit je zwei Woertern pro Zeile.
                'paar'    => ['de' => 'Damen und Herren nebeneinander',
                              'tr' => 'Kadın ve erkek yan yana'],
            ],
            'settings' => [],
            'inputs' => [
                'code' => ['type' => 'text', 'max' => 80,
                    'label' => ['de' => 'Kleiderordnung', 'en' => 'Dress code', 'tr' => 'Kıyafet kuralı']],
                'note' => ['type' => 'textarea', 'max' => 300,
                    'label' => ['de' => 'Hinweis dazu', 'en' => 'A note about it', 'tr' => 'Açıklama']],
                // "Kadin / Erkek" - zwei eigene Zeilen und keine zweite
                // Bedeutung des Hinweises: was Damen tragen, ist eine andere
                // Auskunft als was Herren tragen, und in einem Absatz
                // zusammengeschrieben liest sie niemand zu Ende.
                'women' => ['type' => 'text', 'max' => 160,
                    'label' => ['de' => 'Für Damen', 'en' => 'For women', 'tr' => 'Kadın']],
                'men' => ['type' => 'text', 'max' => 160,
                    'label' => ['de' => 'Für Herren', 'en' => 'For men', 'tr' => 'Erkek']],
                /*
                 * Die Farbpalette.
                 *
                 * "Admin veya musteri renkleri secebilmeli ve davetiyede
                 * bunlar sik renk daireleri olarak gosterilebilmeli."
                 *
                 * Ein Textfeld mit Farben, durch Komma getrennt, und kein
                 * Feld je Farbe: wie viele es sind, weiss nur das Paar - drei
                 * feste Felder waeren bei zwei Farben zwei leere und bei
                 * fuenf zu wenige. Jede geht beim Drucken durch
                 * Design::safeColor; was keine Farbe ist, faellt weg.
                 */
                'colors' => ['type' => 'text', 'max' => 200,
                    'label' => ['de' => 'Farben (mit Komma)', 'en' => 'Colours (comma separated)',
                                'tr' => 'Renkler (virgülle)']],
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
            'settings' => [
                /*
                 * Der Hinweis auf uns, ganz unten.
                 *
                 * "Footer sitenin en alt tarafidir … oraya sitenin
                 * reklamini yapabiliriz." Eine Einladung liegt bei fuenfzig
                 * bis dreihundert Menschen, und die meisten von ihnen
                 * heiraten irgendwann selbst.
                 *
                 * Ein Haken und kein Adressfeld. Die Adresse ist immer
                 * unsere eigene; ein freies URL-Feld waere eine
                 * Weiterleitung in JEDER Einladung, die eines Tages jemand
                 * irgendwohin zeigen laesst - auf einer Seite, der die
                 * Gaeste vertrauen, weil das Brautpaar sie geschickt hat.
                 *
                 * Voreingestellt aus. Eine Einladung, die ungefragt fuer den
                 * Hersteller wirbt, ist eine Entscheidung des Paares und
                 * nicht des Werkzeugs - der Grafiker schaltet sie je Vorlage
                 * ein.
                 */
                'credit' => [
                    'type'    => 'bool',
                    'default' => false,
                    'label'   => ['de' => 'Hinweis auf Atelier Lumière',
                                  'tr' => 'Altta Atelier Lumière yönlendirmesi'],
                ],
            ],
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
            'settings' => [
                /*
                 * Wie ein Foto gezeigt wird.
                 *
                 * "Fotograflar her zaman normal dikdortgen resim olarak
                 * gosterilmemeli … Musteri fotografini yukler, nasil
                 * gosterilecegini secilen davetiye tasarimi belirler."
                 *
                 * Genau diese Trennung: das Paar laedt hoch, die VORLAGE
                 * entscheidet die Form. Deshalb eine Einstellung des
                 * Grafikers und kein Feld im Assistenten - ein Paar, das
                 * seine Bilder einzeln in Rahmen steckt, baut keine
                 * Einladung mehr, sondern eine Collage.
                 *
                 * Der Name traegt "photo" im Schluessel, weil "frame" schon
                 * vergeben ist: das ist der Rahmen um den TEXT eines
                 * Abschnitts (GRUND). Zwei Rahmen mit einem Namen waeren
                 * zwei Antworten auf dieselbe Frage.
                 */
                'photoFrame' => [
                    'type'    => 'select',
                    'options' => ['keine', 'polaroid', 'gold', 'papier', 'oval', 'rund', 'eigen'],
                    'default' => 'keine',
                    'label'   => ['de' => 'Form der Bilder', 'tr' => 'Fotoğraf biçimi'],
                ],
                // Die eigene Zeichnung dazu - sie legt sich UEBER das Bild,
                // nicht dahinter. Eine Rahmen-PNG mit durchsichtiger Mitte
                // gibt damit genau das, was sie soll.
                'photoFrameSrc' => [
                    'type'    => 'src',
                    'kind'    => 'bild',
                    'default' => '',
                    'label'   => ['de' => 'Eigener Bildrahmen (PNG)', 'tr' => 'Kendi foto çerçeven (PNG)'],
                ],
            ],
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
                /*
                 * Ein Lied von auswaerts, als Rahmen.
                 *
                 * "Muzigi youtube veya spotify ile gomme." Es ist bewusst
                 * eine dritte Gestalt und keine Erweiterung der ersten:
                 * "Hintergrundmusik, aber von YouTube" gibt es nicht. Der
                 * Hintergrund haengt am Oeffnen des Kuverts und laeuft unter
                 * der Seite; ein fremder Rahmen kann weder das eine noch das
                 * andere. Wer einbettet, bekommt einen sichtbaren Spieler -
                 * und der Gast tippt ihn an.
                 *
                 * Ins selbe Feld wie sonst die Tonspur: was hineingehoert,
                 * sagt die Gestalt, nicht ein zweites Feld daneben.
                 */
                'einbetten' => ['de' => 'YouTube einbetten', 'tr' => 'YouTube olarak göm'],
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
                /*
                 * Und die Adresse fuer die Gestalt "YouTube einbetten".
                 *
                 * Ein eigenes Feld und nicht dasselbe wie oben: dort steht
                 * ein Pfad aus dem eigenen Haus, hier eine fremde Adresse.
                 * Beides in ein Feld zu legen hiesse, die Pruefung des einen
                 * fuer den anderen aufzuweichen - und die Pruefung ist hier
                 * das Einzige, was zwischen dem Textfeld und dem Markup
                 * steht.
                 *
                 * Gelesen wird es nur von der einen Gestalt. Die anderen
                 * beiden sehen es nicht, und ein leeres Feld kostet nichts.
                 */
                'embed' => [
                    'type'    => 'einbettung',
                    'default' => '',
                    'label'   => ['de' => 'YouTube-Adresse (nur für "YouTube einbetten")',
                                  'tr' => 'YouTube adresi (yalnız "YouTube olarak göm" için)'],
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
            // Dieselbe Angabe wie der Countdown, andere Aufgabe: der eine
            // zaehlt darauf zu, der andere zeigt sie.
            'date'      => ['date'],
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

    /**
     * Wie eine Art im Panel heisst.
     *
     * Eine fremde Sprache faellt auf Deutsch, wie ueberall sonst hier
     * (iconTitle, I18n::raw). Eine unbekannte Art bleibt leer und nicht
     * "unbekannt": der Aufrufer soll selbst entscheiden, was er dann
     * hinschreibt - im Panel steht dort die Kennung, und die ist besser als
     * ein Wort, das nichts sagt.
     */
    public static function typeLabel(string $type, string $locale): string
    {
        return (string) (self::NAMEN[$type]['label'][$locale]
            ?? self::NAMEN[$type]['label']['de']
            ?? '');
    }

    /**
     * Der Halbsatz darunter: was in diese Art hineingehoert.
     */
    public static function typeHint(string $type, string $locale): string
    {
        return (string) (self::NAMEN[$type]['hint'][$locale]
            ?? self::NAMEN[$type]['hint']['de']
            ?? '');
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

            /*
             * Ein alter Name fuer denselben Wert.
             *
             * Er wird VOR der Pruefung uebersetzt, nicht danach: was in
             * einem Dokument steht, ist eine Behauptung von gestern, und
             * gestern hiess "weit" das, was heute "l" heisst. Ohne diese
             * Zeile faende die Pruefung "weit" nicht in der Liste und
             * setzte die Voreinstellung - eine stille Aenderung an einer
             * Vorlage, die niemand angefasst hat.
             */
            if (is_string($wert) && isset($schema['aliases'][$wert])) {
                $wert = $schema['aliases'][$wert];
            }

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
                /*
                 * Ein Lied von auswaerts - als Rahmen, nicht als Datei.
                 *
                 * Warum nicht 'src': dort darf nichts Fremdes stehen, und das
                 * ist richtig so. Eine Einbettung IST aber fremd; das Feld
                 * sagt es in seiner Art, statt die Regel des anderen Feldes
                 * aufzuweichen.
                 *
                 * Geprueft wird beim SPEICHERN und nicht erst beim Drucken:
                 * was in der Vorlage liegt, ist dann schon eine bekannte
                 * Einbettungsadresse und nichts anderes. Wer spaeter etwas
                 * anderes daraus machen will, muss an safeEinbettung vorbei -
                 * und dort kommt nur heraus, was neu zusammengesetzt wurde.
                 */
                'einbettung' => Design::safeEinbettung((string) ($wert ?? $schema['default'])),
                default  => $schema['default'],
            };
        }

        return $out;
    }
}
