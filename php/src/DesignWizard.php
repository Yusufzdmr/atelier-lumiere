<?php

declare(strict_types=1);

namespace Atelier;

/**
 * Was der Kunde am Design aendern darf - und was der Assistent ihn fragt.
 *
 * Zwei Fragen, zwei Quellen, und sie werden leicht verwechselt:
 *
 *   Welche Felder frage ich?   -> die bind-Namen, die das Design benutzt.
 *                                 Ohne Recht, ohne Haken: die Werte einer
 *                                 Einladung kommen immer aus den Daten.
 *   Was biete ich darueber
 *   hinaus an?                 -> die Rechte (Design::PERMISSIONS) und die
 *                                 customer-Haken der Marken.
 *
 * Wer nur die Rechte liest, baut einen leeren Assistenten: im heutigen Bestand
 * steht fast jedes Recht auf false.
 *
 * Alles hier ist rein - keine Datenbank, keine Sitzung, kein $_POST. Deshalb
 * laeuft es unter bin/test.php, das keine config.php kennt.
 */
final class DesignWizard
{
    /** In dieser Reihenfolge stehen die Felder im Formular. */
    public const FIELD_ORDER = ['bride', 'groom', 'date', 'time', 'venue', 'address', 'message', 'hashtag'];

    /**
     * Welches Feld hinter welchem bind steckt.
     *
     * Weniger Felder als binds: vier Namen ziehen dieselben zwei Felder. Die
     * Karte steht hier und nicht in der Vorlage - sonst muesste jede Vorlage,
     * die den Assistenten zeichnet, sie noch einmal kennen.
     */
    private const BIND_FIELDS = [
        'couple_names'     => ['bride', 'groom'],
        'initials'         => ['bride', 'groom'],
        'bride_name'       => ['bride'],
        'groom_name'       => ['groom'],
        'wedding_date'     => ['date'],
        'wedding_weekday'  => ['date'],
        'wedding_time'     => ['time'],
        'location_name'    => ['venue'],
        'location_address' => ['address'],
        'invitation_text'  => ['message'],
        'hashtag'          => ['hashtag'],
    ];

    /**
     * Alles, was der Assistent zu diesem Design anbieten darf.
     *
     * @param array<string,mixed> $doc
     * @return array{fields:list<string>,palette:array<string,mixed>,fonts:array<string,mixed>,layers:array<string,array<string,bool>>}
     */
    public static function choices(array $doc): array
    {
        $doc = Design::complete($doc);

        $felder = [];
        foreach ($doc['layers'] as $el) {
            foreach (self::BIND_FIELDS[(string) $el['bind']] ?? [] as $feld) {
                $felder[$feld] = true;
            }
        }
        // Nach FIELD_ORDER, nicht nach Fundort: sonst haengt die Reihenfolge
        // im Formular daran, wie der Grafiker die Ebenen sortiert hat.
        $fields = array_values(array_filter(self::FIELD_ORDER, static fn (string $f): bool => isset($felder[$f])));

        $palette = array_filter($doc['palette'], static fn (array $e): bool => (bool) $e['customer']);
        $fonts   = array_filter($doc['fonts'], static fn (array $e): bool => (bool) $e['customer']);

        $layers = [];
        foreach ($doc['layers'] as $el) {
            $p = $el['permissions'];
            // edit ist der Hauptschalter. Eine Ebene mit fuenf Haken und ohne
            // edit ist gesperrt - so ist Sperren ein Haken und nicht fuenf.
            if (!$p['edit']) {
                continue;
            }

            $rechte = [
                'color' => $p['color'] && in_array($el['type'], ['text', 'button', 'shape'], true),
                'font'  => $p['font'] && in_array($el['type'], ['text', 'button'], true),
                // Ein bind holt seinen Wert aus den Daten. Ein fester Text
                // daneben waere eine zweite Wahrheit, die nie gewinnt.
                'text'  => $p['text'] && $el['bind'] === '' && in_array($el['type'], ['text', 'button'], true),
                'photo' => $p['photo'] && in_array($el['type'], ['image', 'photo'], true),
                'hide'  => $p['hide'],
            ];

            if (in_array(true, $rechte, true)) {
                $layers[(string) $el['id']] = $rechte;
            }
        }

        return ['fields' => $fields, 'palette' => $palette, 'fonts' => $fonts, 'layers' => $layers];
    }

    /**
     * Welche Schritte dieses Design braucht.
     *
     * Nicht fest verdrahtet: ein Design ohne Rechte hat zwei Schritte, eines
     * mit Bildern und Farben vier. Ein leerer Schritt ist ein Bildschirm, auf
     * dem nichts zu tun ist - der wird nicht gezeigt.
     *
     * @param array<string,mixed> $doc
     * @return list<string>
     */
    public static function steps(array $doc): array
    {
        $w = self::choices($doc);

        $schritte = ['angaben'];

        foreach ($w['layers'] as $rechte) {
            if ($rechte['photo']) {
                $schritte[] = 'bilder';
                break;
            }
        }

        $design = $w['palette'] !== [] || $w['fonts'] !== [];
        if (!$design) {
            foreach ($w['layers'] as $rechte) {
                if ($rechte['color'] || $rechte['font'] || $rechte['text'] || $rechte['hide']) {
                    $design = true;
                    break;
                }
            }
        }
        if ($design) {
            $schritte[] = 'design';
        }

        $schritte[] = 'veroeffentlichen';

        return $schritte;
    }
}
