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
    public const TYPES = ['location', 'countdown', 'family', 'program', 'rsvp', 'text'];

    /** Wie viele Programmzeilen, und wie lang eine sein darf. */
    public const PROGRAM_MAX = 20;
    public const PROGRAM_LEN = 80;

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
            if ($titel === '') {
                continue;
            }
            $out[] = [
                'time'  => mb_substr(trim((string) ($zeile['time'] ?? '')), 0, self::PROGRAM_LEN),
                'title' => mb_substr($titel, 0, self::PROGRAM_LEN),
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
            'text'      => trim(self::sectionText($data, (string) $abschnitt['id'])) !== '',
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
            if ($regeln !== '') {
                $css .= $scope . ' .d-sec-' . $abschnitt['id'] . '{' . $regeln . '}';
            }
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
        return $scope . '.d-sec-flaeche{background-color:var(--d-paper,#faf7f2);color:var(--d-fg,#14110f);}'
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
            . $scope . ' .d-sec{background-image:var(--d-sec-blatt,none);'
            . 'background-color:var(--d-paper,#faf7f2);'
            . 'background-position:top center;background-size:100% auto;'
            . 'background-repeat:no-repeat;padding:56% 14% 12%;'
            . 'margin-top:0;line-height:1.7;text-align:center;}'
            . $scope . ' .d-sec:first-child{margin-top:0;}'
            // Die Ueberschriften in der Auszeichnungsschrift und im Akzent -
            // dieselben zwei Marken, die auf der Karte den Ton angeben.
            . $scope . ' .d-sec-title{font-size:1.5rem;font-weight:400;line-height:1.3;margin-bottom:1.5rem;'
            . 'font-family:var(--df-display,inherit);color:var(--d-accent,inherit);'
            . 'letter-spacing:0.16em;text-transform:uppercase;}'
            . $scope . ' .d-sec p{margin-bottom:0.5rem;}'
            . $scope . ' .d-sec-days{display:block;margin-bottom:0.25rem;}'
            // Als Block zentriert, innen ausgerichtet: die Uhrzeiten stehen
            // untereinander, sonst waere die Spalte eine Treppe.
            . $scope . ' .d-sec-program{display:grid;grid-template-columns:auto auto;'
            . 'gap:0.6rem 2rem;justify-content:center;text-align:left;}'
            . $scope . ' .d-sec-program dt{font-weight:600;}'
            . $scope . ' .d-sec-program dd{margin:0;}'
            . $scope . ' .d-sec-form{display:grid;gap:1.1rem;max-width:24rem;'
            . 'margin-inline:auto;text-align:left;}'
            . $scope . ' .d-sec-form-row{display:grid;gap:0.3rem;}'
            . $scope . ' .d-sec-form-row span{font-size:0.72rem;letter-spacing:0.12em;text-transform:uppercase;opacity:0.7;}'
            . $scope . ' .d-sec-form input[type=text],'
            . $scope . ' .d-sec-form input[type=number]{border:0;border-bottom:1px solid currentColor;background:transparent;padding:0.35rem 0;color:inherit;font:inherit;}'
            . $scope . ' .d-sec-form button{justify-self:center;margin-top:0.5rem;border:1px solid currentColor;background:transparent;padding:0.55rem 1.5rem;color:inherit;font:inherit;cursor:pointer;}';
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

            $out .= '<section class="d-sec d-sec-' . e($id) . ' d-sec-' . e($typ) . '">';

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
                'location'  => self::ort($data, $locale),
                'countdown' => self::countdown($data, $locale),
                'family'    => self::familien($data),
                'program'   => self::programm($data),
                'rsvp'      => self::formular($form, $locale),
                'text'      => self::freitext($data, $id),
                default     => '',
            };

            $out .= '</section>';
        }

        return $out;
    }

    /** @param array<string,mixed> $data */
    private static function ort(array $data, string $locale): string
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
        $out .= '<a class="d-sec-map" rel="noopener noreferrer" target="_blank" href="'
            . e('https://www.google.com/maps/dir/?api=1&destination=' . rawurlencode($adresse))
            . '">' . e($locale === 'de' ? 'Route planen' : 'Plan route') . '</a>';

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
    private static function programm(array $data): string
    {
        $out = '<dl class="d-sec-program">';

        foreach (self::programRows($data) as $zeile) {
            $out .= '<dt>' . e($zeile['time']) . '</dt><dd>' . e($zeile['title']) . '</dd>';
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
        $alle = $data['sections'] ?? null;
        if (!is_array($alle)) {
            return '';
        }
        $eintrag = $alle[$id] ?? null;
        if (!is_array($eintrag)) {
            return '';
        }

        return (string) ($eintrag['text'] ?? '');
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
    private static function freitext(array $data, string $id): string
    {
        $out = '';

        foreach (paragraphs(self::sectionText($data, $id)) as $absatz) {
            // d-sec-absatz und nicht d-sec-text: die <section> traegt bereits
            // d-sec-<typ>, also d-sec-text. Eine Regel fuer die Absaetze
            // faerbte sonst auch den Kasten um - derselbe Grund, aus dem das
            // rsvp-Formular d-sec-form heisst und nicht d-sec-rsvp.
            $out .= '<p class="d-sec-absatz">' . e($absatz) . '</p>';
        }

        return $out;
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
