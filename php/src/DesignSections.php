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
 * Alles hier ist rein - keine Datenbank, keine Sitzung, kein $_POST, und kein
 * Blick auf die Uhr: das Bezugsdatum kommt als Parameter herein.
 */
final class DesignSections
{
    /** Welche Arten es gibt. Alles andere faellt beim Einlesen weg. */
    public const TYPES = ['location', 'countdown', 'family', 'program'];

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
            default     => false,
        };
    }
}
