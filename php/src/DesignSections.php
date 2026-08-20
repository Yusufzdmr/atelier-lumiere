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
    public const TYPES = ['location', 'countdown', 'family', 'program', 'rsvp'];

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
     */
    private static function baseline(string $scope): string
    {
        return $scope . ' .d-sec{margin-top:2.5rem;line-height:1.6;}'
            . $scope . ' .d-sec:first-child{margin-top:0;}'
            . $scope . ' .d-sec-title{font-size:1.25rem;font-weight:600;line-height:1.3;margin-bottom:0.75rem;}'
            . $scope . ' .d-sec p{margin-bottom:0.5rem;}'
            . $scope . ' .d-sec-days{display:block;margin-bottom:0.25rem;}'
            . $scope . ' .d-sec-program{display:grid;grid-template-columns:auto 1fr;gap:0.375rem 1.25rem;}'
            . $scope . ' .d-sec-program dt{font-weight:600;}'
            . $scope . ' .d-sec-program dd{margin:0;}';
    }

    /**
     * Die Abschnitte als Markup.
     *
     * @param array<string,mixed> $doc
     * @param array<string,mixed> $data
     */
    public static function html(array $doc, array $data, string $locale, string $heute = ''): string
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
}
