<?php
declare(strict_types=1);

namespace Atelier;

/**
 * Eröffnungsszenen der Einladung.
 *
 * Eine Szene ist kein einzelner Effekt, sondern eine Abfolge aus mehreren
 * Schichten, die zeitversetzt laufen: Dunkelheit, Blitz, Korn, Lichtleck,
 * Vignette. Der Takt steht komplett im Stylesheet (`.ti-*`), hier entsteht
 * nur das Gerüst – so bleibt die Bewegung an einer Stelle und die
 * Inhaltsrichtlinie braucht keine Ausnahme für Style-Attribute.
 *
 * `invitation.js` setzt `data-playing="true"` und wartet die Dauer aus
 * Themes::introDuration() ab, bevor die Karte freigegeben wird.
 */
final class Intro
{
    /**
     * Das Gerüst einer Szene. Leer, wenn das Thema keine will.
     *
     * @param array<string,mixed> $theme
     */
    public static function html(string $id, array $theme): string
    {
        if ($id === '' || $id === 'none' || !in_array($id, Themes::INTROS, true)) {
            return '';
        }

        $layers = match ($id) {
            // Dunkelkammer: erst schwarz, dann der Blitz, dann kommt das Bild
            // aus dem Korn heraus – wie ein Abzug in der Schale.
            'darkroom' => '<span class="ti-dark"></span>'
                . '<span class="ti-flash"></span>'
                . '<span class="ti-grain"></span>'
                . '<span class="ti-wash"></span>'
                . '<span class="ti-leak"></span>'
                . '<span class="ti-vignette"></span>',

            // Schärfe zieht nach: der Sucher findet den Punkt.
            'focus' => '<span class="ti-blur"></span>'
                . '<span class="ti-ring"></span>'
                . '<span class="ti-vignette"></span>',

            // Henna: die Linie zeichnet sich selbst, darunter ein Herzschlag.
            'henna' => '<span class="ti-warm"></span>'
                . self::hennaSvg((string) ($theme['accent'] ?? '#B08D57'))
                . '<span class="ti-beat"></span>',

            // Fest: Lichtstrahlen fahren auf, dann fällt Konfetti.
            'party' => '<span class="ti-dark ti-dark-soft"></span>'
                . '<span class="ti-beams"></span>'
                . self::confetti((string) ($theme['accent'] ?? '#B08D57'), (string) ($theme['accentSoft'] ?? '#D9C3A0')),

            // Siegel & Licht: das Wachs bricht, Gold läuft über die Fläche.
            'sealLight' => '<span class="ti-warm ti-warm-short"></span>'
                . '<span class="ti-sweep"></span>'
                . '<span class="ti-vignette"></span>',

            default => '',
        };

        if ($layers === '') {
            return '';
        }

        return '<div class="t-intro ti-' . e($id) . '" data-intro aria-hidden="true">' . $layers . '</div>';
    }

    /** Ein Hennamuster, das sich selbst zeichnet (stroke-dasharray im Stylesheet). */
    private static function hennaSvg(string $color): string
    {
        $paths = [
            'M50 92 C 50 74, 36 66, 36 52 C 36 40, 44 34, 50 34 C 56 34, 64 40, 64 52 C 64 66, 50 74, 50 92',
            'M50 34 C 44 26, 44 16, 50 8 C 56 16, 56 26, 50 34',
            'M36 52 C 26 50, 18 42, 16 32 C 26 34, 34 42, 36 52',
            'M64 52 C 74 50, 82 42, 84 32 C 74 34, 66 42, 64 52',
            'M50 60 C 40 60, 32 68, 30 78 C 40 76, 48 70, 50 60',
            'M50 60 C 60 60, 68 68, 70 78 C 60 76, 52 70, 50 60',
        ];

        $svg = '<svg class="ti-draw" viewBox="0 0 100 100" fill="none" stroke="' . e($color) . '"'
            . ' stroke-width="1.4" stroke-linecap="round" aria-hidden="true">';
        foreach ($paths as $i => $d) {
            $svg .= '<path d="' . $d . '" style="--n: ' . $i . '"/>';
        }

        return $svg . '</svg>';
    }

    /** Konfetti: feste Anzahl, feste Plätze – kein Zufall, damit es ruhig bleibt. */
    private static function confetti(string $a, string $b): string
    {
        $out = '<span class="ti-confetti">';
        for ($i = 0; $i < 22; $i++) {
            // Grosser Schritt, damit aufeinanderfolgende Schnipsel weit
            // auseinander landen. Bei einem kleinen Schritt fallen sie der
            // Reihe nach von links nach rechts – das sieht nach Liste aus,
            // nicht nach Konfetti. (fmod, weil % Kommastellen wegwirft.)
            $x = round(fmod($i * 37.3 + 5, 94), 1);
            $out .= '<i style="--n: ' . $i . '; --x: ' . $x . '%;'
                . ' background: ' . e($i % 2 ? $a : $b) . '"></i>';
        }

        return $out . '</span>';
    }
}
