<?php
declare(strict_types=1);

namespace Atelier;

/**
 * Gezeichnete Hintergrundkunst der Einladungsthemen.
 *
 * Alles ist SVG, das hier entsteht – keine Bilddatei. Zwei Gründe: die
 * Einladung bleibt auf dem Handy leicht, und die Motive nehmen die Farben des
 * gewählten Themas an, statt gegen sie zu arbeiten. Wer eigene Grafiken will,
 * lädt sie als Schmuckelement hoch (siehe Themes::SPOTS) – das schließt sich
 * nicht aus, es liegt übereinander.
 *
 * Die Formen sind bewusst grob gehalten. Sie stehen weit hinter dem Text und
 * sollen ihn rahmen, nicht mit ihm konkurrieren.
 */
final class Scenes
{
    /**
     * Die vollflächige Szene hinter der Karte.
     *
     * @param array<string,mixed> $theme
     */
    public static function html(string $id, array $theme): string
    {
        if ($id === '' || $id === 'none' || !in_array($id, Themes::SCENES, true)) {
            return '';
        }

        $accent = (string) ($theme['accent'] ?? '#B08D57');
        $soft   = (string) ($theme['accentSoft'] ?? $accent);
        $petal  = (string) ($theme['petal'] ?? $soft);

        $html = '<div class="scene" aria-hidden="true">'
            . '<span class="scene-wash scene-wash-a" style="background:' . e($soft) . '"></span>'
            . '<span class="scene-wash scene-wash-b" style="background:' . e($petal) . '"></span>';

        foreach (self::pieces($id, $accent, $soft) as $piece) {
            $html .= '<svg viewBox="' . e($piece['box']) . '" preserveAspectRatio="xMidYMid meet"'
                . ' class="scene-corner ' . e($piece['class']) . '"'
                . ' style="' . e($piece['style']) . '">' . $piece['svg'] . '</svg>';
        }

        return $html . '</div>';
    }

    /**
     * Prägung auf dem Kuvert: eine Naht in der Mitte, Motiv links und rechts.
     *
     * Das ersetzt kein Foto von geprägtem Papier – aber ein flaches Rechteck
     * sieht nach Farbfläche aus, und ein Kuvert soll nach Papier aussehen.
     * Gezeichnet wird nur die Kontur; die Tiefe macht das Stylesheet mit
     * einem hellen Schlagschatten nach unten (.t-emboss).
     *
     * @param array<string,mixed> $theme
     */
    public static function envelopeArt(string $sceneId, array $theme): string
    {
        $line = (string) ($theme['envelopeEdge'] ?? 'rgba(0,0,0,.25)');
        $motif = (string) ($theme['accentSoft'] ?? $theme['accent'] ?? '#B08D57');

        $body = match ($sceneId) {
            // Art déco: Fächer in den unteren Ecken, strenge Linien
            'deco' => '<g transform="translate(6 34) scale(.34)">' . self::decoFan($motif, 0.5) . '</g>'
                . '<g transform="translate(154 34) scale(-.34,.34)">' . self::decoFan($motif, 0.5) . '</g>',

            // Spitze: ein Bogen über die ganze Breite
            'lace' => '<g transform="translate(0 74) scale(.4)">' . self::laceEdge($motif, 16, 400.0) . '</g>',

            // Pampas: zwei Rispen, die an der Naht hochstehen
            'pampas' => self::plume(62, 96, 46, $motif, 0.45) . self::plume(98, 96, 40, $motif, 0.45),

            // Blüten neben der Naht
            'bouquet' => '<g transform="translate(30 88) scale(.42) rotate(-14)">'
                    . self::leafSpray($motif, 5, 'M4 96 C 20 66, 44 34, 96 8', 'none', 0.5) . '</g>'
                . '<g transform="translate(130 88) scale(-.42,.42) rotate(14)">'
                    . self::leafSpray($motif, 5, 'M4 96 C 20 66, 44 34, 96 8', 'none', 0.5) . '</g>'
                . self::blossom(44, 62, 9, $motif, $motif, 0.4)
                . self::blossom(116, 62, 8, $motif, $motif, 0.35),

            // Standard: Blätter, die zur Naht hin wachsen
            default => '<g transform="translate(22 92) scale(.56) rotate(-8)">'
                    . self::leafSpray($motif, 6, 'M4 96 C 20 66, 44 34, 96 8', 'none', 0.7) . '</g>'
                . '<g transform="translate(138 92) scale(-.56,.56) rotate(8)">'
                    . self::leafSpray($motif, 6, 'M4 96 C 20 66, 44 34, 96 8', 'none', 0.7) . '</g>',
        };

        // Die Naht läuft von der Spitze der Klappe nach unten.
        $seam = '<path d="M80 46 V 96" stroke="' . e($line) . '" stroke-width="0.7" opacity="0.65"/>';

        return '<svg class="t-emboss" viewBox="0 0 160 100" preserveAspectRatio="none" aria-hidden="true">'
            . $seam . $body . '</svg>';
    }

    /* --------------------------- Bausteine --------------------------- */

    /**
     * Blätterzweig: ein geschwungener Stiel mit paarweise sitzenden Blättern.
     *
     * Die Blätter sind spitze Ovale aus zwei Bögen, keine Ellipsen – und sie
     * stehen im Winkel zur Stielrichtung an der jeweiligen Stelle. Mit
     * gedrehten Ellipsen sah der Zweig aus wie eine Kette von Schlaufen.
     */
    private static function leafSpray(
        string $color,
        int $leaves = 7,
        string $curve = 'M4 96 C 20 66, 44 34, 96 8',
        string $fill = 'none',
        float $opacity = 1.0
    ): string {
        // Die gedachte Bahn, an der die Blätter sitzen. Sie folgt der
        // Standardkurve; bei anderen Kurven bleibt sie grob daneben, was bei
        // Zierwerk weit hinter dem Text niemandem auffällt.
        $at = static fn (float $t): array => [4 + $t * 92, 96 - ($t ** 0.72) * 88];

        $svg = '<g opacity="' . $opacity . '" stroke-linejoin="round">'
            . '<path d="' . e($curve) . '" fill="none" stroke="' . e($color) . '" stroke-width="1.1" stroke-linecap="round"/>';

        for ($i = 1; $i <= $leaves; $i++) {
            $t = $i / ($leaves + 1);
            [$x, $y] = $at($t);
            [$nx, $ny] = $at(min(1.0, $t + 0.03));

            // Stielrichtung an dieser Stelle – daran hängen beide Blätter.
            $dir = rad2deg(atan2($ny - $y, $nx - $x));
            $len = 15 * (1 - $t * 0.5);
            $w = $len * 0.34;

            // Spitzes Oval, das bei 0,0 ansetzt und nach +x zeigt.
            $leaf = 'M0 0 Q ' . round($len * 0.45, 2) . ' ' . round(-$w, 2) . ', ' . round($len, 2) . ' 0'
                . ' Q ' . round($len * 0.45, 2) . ' ' . round($w, 2) . ', 0 0 Z';

            foreach ([-38, 38] as $side) {
                $svg .= '<path d="' . $leaf . '" fill="' . e($fill) . '" stroke="' . e($color) . '" stroke-width="0.9"'
                    . ' transform="translate(' . round($x, 2) . ' ' . round($y, 2) . ') rotate(' . round($dir + $side, 2) . ')"/>';
            }
        }

        return $svg . '</g>';
    }

    /** Blüte: ineinanderliegende Blätterkränze, von außen nach innen kleiner. */
    private static function blossom(float $x, float $y, float $r, string $color, string $core, float $opacity = 1.0): string
    {
        $ring = static function (float $radius, int $count, float $turn, float $o) use ($color): string {
            $out = '';
            for ($i = 0; $i < $count; $i++) {
                $a = ($i / $count) * 360 + $turn;
                $out .= '<ellipse cx="0" cy="' . round(-$radius * 0.55, 2) . '"'
                    . ' rx="' . round($radius * 0.42, 2) . '" ry="' . round($radius * 0.58, 2) . '"'
                    . ' fill="' . e($color) . '" opacity="' . $o . '" transform="rotate(' . round($a, 2) . ')"/>';
            }
            return $out;
        };

        return '<g transform="translate(' . $x . ' ' . $y . ')" opacity="' . $opacity . '">'
            . $ring($r, 7, 0, 0.62)
            . $ring($r * 0.66, 7, 26, 0.8)
            . $ring($r * 0.38, 5, 12, 0.95)
            . '<circle r="' . round($r * 0.16, 2) . '" fill="' . e($core) . '" opacity="0.75"/>'
            . '</g>';
    }

    /** Pampasgras: viele feine Halme, die sich nach oben auffächern. */
    private static function plume(float $x, float $y, float $h, string $color, float $opacity = 1.0): string
    {
        $svg = '<g transform="translate(' . $x . ' ' . $y . ')" opacity="' . $opacity . '">'
            . '<path d="M0 0 C -2 ' . round(-$h * 0.5, 2) . ', 1 ' . round(-$h * 0.8, 2) . ', 0 ' . round(-$h, 2) . '"'
            . ' fill="none" stroke="' . e($color) . '" stroke-width="1.2"/>';

        for ($i = 0; $i < 26; $i++) {
            $t = $i / 25;
            $yy = -$h * (0.28 + $t * 0.7);
            $side = $i % 2 ? 1 : -1;
            $len = 15 * (1 - abs($t - 0.45) * 1.1);

            $svg .= '<path d="M0 ' . round($yy, 2) . ' Q ' . round($side * $len * 0.6, 2) . ' ' . round($yy - 5, 2)
                . ', ' . round($side * $len, 2) . ' ' . round($yy - 12, 2) . '"'
                . ' fill="none" stroke="' . e($color) . '" stroke-width="0.8" stroke-linecap="round"'
                . ' opacity="' . round(0.5 + $t * 0.4, 2) . '"/>';
        }

        return $svg . '</g>';
    }

    /** Art-déco-Fächer: konzentrische Viertelbögen mit Strahlen. */
    private static function decoFan(string $color, float $opacity = 1.0): string
    {
        $svg = '<g opacity="' . $opacity . '" fill="none" stroke="' . e($color) . '">';

        foreach ([26, 42, 58, 74, 90] as $i => $r) {
            $svg .= '<path d="M0 ' . $r . ' A ' . $r . ' ' . $r . ' 0 0 0 ' . $r . ' 0" stroke-width="' . ($i % 2 ? '0.6' : '1') . '"/>';
        }
        foreach ([15, 30, 45, 60, 75] as $a) {
            $svg .= '<line x1="0" y1="0" x2="' . round(cos(deg2rad($a)) * 92, 2) . '" y2="' . round(sin(deg2rad($a)) * 92, 2) . '"'
                . ' stroke-width="0.5" opacity="0.6"/>';
        }

        return $svg . '<circle cx="0" cy="0" r="4" fill="' . e($color) . '" stroke="none"/></g>';
    }

    /** Spitzenbogen: gleichmäßige Halbkreise mit Perlen darunter. */
    private static function laceEdge(string $color, int $count = 16, float $width = 400.0): string
    {
        $step = $width / $count;
        $svg = '<g fill="none" stroke="' . e($color) . '">';

        for ($i = 0; $i < $count; $i++) {
            $svg .= '<path d="M' . round($i * $step, 2) . ' 0 A ' . round($step / 2, 2) . ' ' . round($step / 2, 2)
                . ' 0 0 0 ' . round(($i + 1) * $step, 2) . ' 0" stroke-width="0.9"/>'
                . '<circle cx="' . round($i * $step + $step / 2, 2) . '" cy="' . round($step * 0.62, 2) . '"'
                . ' r="1.5" fill="' . e($color) . '" stroke="none" opacity="0.7"/>';
        }

        return $svg . '</g>';
    }

    /* ----------------------------- Szenen ----------------------------- */

    /** @return list<array{box:string,class:string,style:string,svg:string}> */
    private static function pieces(string $id, string $a, string $s): array
    {
        $flip = static fn (string $svg): string => '<g transform="scale(-1,1) translate(-100,0)">' . $svg . '</g>';

        return match ($id) {
            'botanical' => [
                [
                    'box' => '0 0 100 100', 'class' => 'scene-tl',
                    'style' => '--from-x:-14%;--from-r:-8deg;animation-delay:.2s',
                    'svg' => $flip(self::leafSpray($a, 7, 'M4 96 C 20 66, 44 34, 96 8', 'none', 0.55)
                        . self::leafSpray($s, 5, 'M14 98 C 34 74, 52 50, 82 30', 'none', 0.4)),
                ],
                [
                    'box' => '0 0 100 100', 'class' => 'scene-tr',
                    'style' => '--from-x:14%;--from-r:8deg;animation-delay:.35s',
                    'svg' => self::leafSpray($a, 7, 'M4 96 C 20 66, 44 34, 96 8', 'none', 0.55)
                        . self::leafSpray($s, 5, 'M14 98 C 34 74, 52 50, 82 30', 'none', 0.4),
                ],
                [
                    'box' => '0 0 100 100', 'class' => 'scene-bl scene-flip',
                    'style' => '--from-y:12%;animation-delay:.5s',
                    'svg' => self::leafSpray($s, 5, 'M4 96 C 20 66, 44 34, 96 8', 'none', 0.32),
                ],
            ],

            'leafy' => [
                [
                    'box' => '0 0 100 100', 'class' => 'scene-left',
                    'style' => '--from-x:-16%;animation-delay:.2s',
                    'svg' => $flip(self::leafSpray($a, 9, 'M4 96 C 20 66, 44 34, 96 8', $s, 0.5)),
                ],
                [
                    'box' => '0 0 100 100', 'class' => 'scene-br scene-flip',
                    'style' => '--from-x:16%;animation-delay:.4s',
                    'svg' => self::leafSpray($a, 8, 'M4 96 C 20 66, 44 34, 96 8', $s, 0.42),
                ],
                [
                    'box' => '0 0 100 100', 'class' => 'scene-mr',
                    'style' => '--from-y:-10%;animation-delay:.6s',
                    'svg' => self::leafSpray($s, 5, 'M4 96 C 20 66, 44 34, 96 8', 'none', 0.26),
                ],
            ],

            'bouquet' => [
                [
                    'box' => '0 0 140 140', 'class' => 'scene-tl scene-wide',
                    'style' => '--from-x:-14%;--from-y:-12%;animation-delay:.2s',
                    'svg' => self::leafSpray($s, 6, 'M6 130 C 30 96, 58 62, 118 30', 'none', 0.5)
                        . self::leafSpray($s, 5, 'M4 118 C 34 108, 62 92, 96 60', 'none', 0.35)
                        . self::blossom(44, 62, 20, $a, $s, 0.5)
                        . self::blossom(78, 38, 15, $s, $a, 0.55)
                        . self::blossom(26, 96, 12, $a, $s, 0.42),
                ],
                [
                    'box' => '0 0 140 140', 'class' => 'scene-br scene-flip',
                    'style' => '--from-x:14%;--from-y:12%;animation-delay:.4s',
                    'svg' => self::leafSpray($s, 6, 'M6 130 C 30 96, 58 62, 118 30', 'none', 0.45)
                        . self::blossom(48, 58, 18, $a, $s, 0.45)
                        . self::blossom(80, 34, 12, $s, $a, 0.5),
                ],
            ],

            'deco' => [
                ['box' => '-4 -4 100 100', 'class' => 'scene-tl', 'style' => '--from-x:-10%;animation-delay:.2s', 'svg' => self::decoFan($a, 0.55)],
                ['box' => '-4 -4 100 100', 'class' => 'scene-tr scene-mirror', 'style' => '--from-x:10%;animation-delay:.3s', 'svg' => self::decoFan($a, 0.55)],
                ['box' => '-4 -4 100 100', 'class' => 'scene-bl scene-updown', 'style' => '--from-y:10%;animation-delay:.45s', 'svg' => self::decoFan($s, 0.4)],
                ['box' => '-4 -4 100 100', 'class' => 'scene-br scene-flip', 'style' => '--from-y:10%;animation-delay:.55s', 'svg' => self::decoFan($s, 0.4)],
            ],

            'lace' => [
                ['box' => '0 0 400 40', 'class' => 'scene-top', 'style' => '--from-y:-16%;animation-delay:.2s', 'svg' => self::laceEdge($a)],
                ['box' => '0 0 400 40', 'class' => 'scene-bottom scene-flip', 'style' => '--from-y:16%;animation-delay:.35s', 'svg' => self::laceEdge($s)],
                [
                    'box' => '0 0 100 100', 'class' => 'scene-ml',
                    'style' => '--from-x:-12%;animation-delay:.5s',
                    'svg' => self::leafSpray($s, 4, 'M4 96 C 20 66, 44 34, 96 8', 'none', 0.28),
                ],
            ],

            'pampas' => [
                [
                    'box' => '0 0 120 120', 'class' => 'scene-bl',
                    'style' => '--from-y:14%;animation-delay:.2s',
                    'svg' => self::plume(34, 120, 92, $a, 0.5) . self::plume(56, 120, 70, $s, 0.55) . self::plume(16, 120, 58, $s, 0.4),
                ],
                [
                    'box' => '0 0 120 120', 'class' => 'scene-br scene-mirror',
                    'style' => '--from-y:14%;animation-delay:.4s',
                    'svg' => self::plume(40, 120, 80, $a, 0.42) . self::plume(62, 120, 58, $s, 0.45),
                ],
                [
                    'box' => '0 0 100 100', 'class' => 'scene-tr scene-flip',
                    'style' => '--from-y:-12%;animation-delay:.55s',
                    'svg' => self::leafSpray($s, 6, 'M4 96 C 20 66, 44 34, 96 8', 'none', 0.3),
                ],
            ],

            default => [],
        };
    }
}
