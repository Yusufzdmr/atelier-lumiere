<?php
declare(strict_types=1);

namespace Atelier;

/**
 * Themen der digitalen Einladung.
 *
 * Der Betrieb baut seine Hintergründe in Canva und lässt sich Animationen
 * schreiben – deshalb kann ein Thema hier ein eigenes Bild und eigenes CSS
 * mitbringen. Das CSS wird auf `.theme-<id>` eingegrenzt, damit ein Thema
 * nie ein anderes zerlegt.
 *
 * Startbelegung: data/themes.php (aus der Next.js-Fassung exportiert).
 * Sobald im Admin etwas geändert wird, gilt der Stand aus der Datenbank.
 */
final class Themes
{
    /** Eingebaute Öffnungsanimationen. */
    public const ANIMATIONS = ['seal', 'fade', 'curtain', 'petals', 'none'];

    public static function animationLabel(string $key, string $locale): string
    {
        $labels = [
            'de' => [
                'seal'    => 'Siegel hebt sich, Kuvert öffnet sich',
                'fade'    => 'Weiches Einblenden',
                'curtain' => 'Vorhang öffnet sich',
                'petals'  => 'Blätter rieseln',
                'none'    => 'Ohne Animation',
            ],
            'tr' => [
                'seal'    => 'Mühür kalkar, zarf açılır',
                'fade'    => 'Yumuşak beliriş',
                'curtain' => 'Perde açılır',
                'petals'  => 'Yapraklar süzülür',
                'none'    => 'Animasyonsuz',
            ],
        ];

        return $labels[$locale][$key] ?? $key;
    }

    /** Felder, die eine Farbe enthalten – Reihenfolge = Reihenfolge im Formular. */
    public const COLORS = [
        'paper'        => ['de' => 'Karte / Papier', 'tr' => 'Kart / kağıt'],
        'bg'           => ['de' => 'Seitenhintergrund', 'tr' => 'Sayfa arkası'],
        'fg'           => ['de' => 'Schrift', 'tr' => 'Yazı'],
        'soft'         => ['de' => 'Schrift, gedämpft', 'tr' => 'İkincil yazı'],
        'accent'       => ['de' => 'Akzent (Linien, Kalligrafie)', 'tr' => 'Vurgu (çizgi, kaligrafi)'],
        'accentSoft'   => ['de' => 'Akzent, hell', 'tr' => 'Vurgu, açık'],
        'paperEdge'    => ['de' => 'Kartenrand', 'tr' => 'Kart kenarı'],
        'envelope'     => ['de' => 'Kuvert', 'tr' => 'Zarf'],
        'envelopeFlap' => ['de' => 'Kuvertklappe', 'tr' => 'Zarf kapağı'],
        'envelopeEdge' => ['de' => 'Kuvertkante', 'tr' => 'Zarf kenarı'],
        'seal'         => ['de' => 'Siegel', 'tr' => 'Mühür'],
        'sealText'     => ['de' => 'Siegelschrift', 'tr' => 'Mühür yazısı'],
        'petal'        => ['de' => 'Schwebende Blätter', 'tr' => 'Uçuşan yapraklar'],
    ];

    /** @return list<array<string,mixed>> */
    public static function all(): array
    {
        $stored = Content::all()['themes'] ?? null;
        $themes = is_array($stored) && $stored !== [] ? $stored : self::defaults();

        return array_values(array_map([self::class, 'complete'], array_filter($themes, 'is_array')));
    }

    /** @return list<array<string,mixed>> */
    public static function defaults(): array
    {
        /** @var list<array<string,mixed>> $themes */
        $themes = require __DIR__ . '/../data/themes.php';
        return $themes;
    }

    /** @return array<string,mixed>|null */
    public static function find(string $id): ?array
    {
        foreach (self::all() as $theme) {
            if ((string) $theme['id'] === $id) {
                return $theme;
            }
        }
        return null;
    }

    /** @param list<array<string,mixed>> $themes */
    public static function save(array $themes): void
    {
        Content::mutate(static function (array $content) use ($themes): array {
            $content['themes'] = array_values($themes);
            return $content;
        });
    }

    /**
     * Neue Felder ergänzen, damit ein alter Datensatz die Oberfläche nicht
     * mit fehlenden Schlüsseln füllt.
     *
     * @param array<string,mixed> $theme
     * @return array<string,mixed>
     */
    public static function complete(array $theme): array
    {
        $defaults = [
            'id'           => 'thema',
            'name'         => 'Thema',
            'sub'          => ['de' => '', 'tr' => ''],
            'bg'           => '#EFE7DC',
            'paper'        => '#FBF6EE',
            'paperEdge'    => 'rgba(176,141,87,0.30)',
            'fg'           => '#221C16',
            'soft'         => 'rgba(34,28,22,0.58)',
            'accent'       => '#B08D57',
            'accentSoft'   => '#D9C3A0',
            'envelope'     => '#E8DCC9',
            'envelopeFlap' => '#E0D2BB',
            'envelopeEdge' => 'rgba(176,141,87,0.45)',
            'seal'         => '#B08D57',
            'sealText'     => '#FBF6EE',
            'petal'        => '#E2CFAF',
            'texture'      => '',
            // Ab hier: im Admin dazugekommen
            'image'          => '',
            'imageMode'      => 'cover',
            'imageOpacity'   => '100',
            'envelopeImage'  => '',
            'animation'      => 'seal',
            'animationSpeed' => '1200',
            'animationDelay' => '0',
            'css'            => '',
        ];

        $merged = array_merge($defaults, $theme);
        if (!is_array($merged['sub'])) {
            $merged['sub'] = ['de' => (string) $merged['sub'], 'tr' => (string) $merged['sub']];
        }

        return $merged;
    }

    /** Kennung aus einem Namen: nur Kleinbuchstaben, Ziffern und Bindestrich. */
    public static function slug(string $value): string
    {
        $value = strtolower(trim($value));
        $map = ['ä' => 'ae', 'ö' => 'oe', 'ü' => 'ue', 'ß' => 'ss', 'ı' => 'i', 'ş' => 's', 'ğ' => 'g', 'ç' => 'c', 'é' => 'e', 'è' => 'e'];
        $value = strtr($value, $map);
        $value = preg_replace('/[^a-z0-9]+/', '-', $value) ?? '';
        return trim($value, '-') ?: 'thema';
    }

    /* ------------------------------ Darstellung ----------------------------- */

    /**
     * Eigenes CSS eines Themas absichern.
     *
     * Der Betrieb fügt hier Code ein, den ihm ein Werkzeug geschrieben hat.
     * Deshalb: keine Möglichkeit, aus dem style-Block auszubrechen, keine
     * fremden Dateien nachladen, keine alten Browser-Schlupflöcher.
     */
    public static function safeCss(string $css): string
    {
        $css = str_replace(['<', '>'], '', $css);
        $css = preg_replace('/@import[^;]*;?/i', '', $css) ?? '';
        $css = preg_replace('/expression\s*\(/i', '', $css) ?? '';
        $css = preg_replace('/behaviou?r\s*:/i', '', $css) ?? '';
        $css = preg_replace('/javascript\s*:/i', '', $css) ?? '';
        $css = preg_replace('/url\(\s*[\'"]?\s*(data:text|javascript)/i', 'url(', $css) ?? '';

        return trim(mb_substr($css, 0, 20000));
    }

    /**
     * Style-Block für die Einladungsseite: Farben als Variablen, Bild,
     * Animationsdauer und das eigene CSS – alles unter `.theme-<id>`.
     *
     * @param array<string,mixed> $theme
     */
    public static function styleBlock(array $theme): string
    {
        $theme = self::complete($theme);
        $id = self::slug((string) $theme['id']);
        $scope = '.theme-' . $id;

        $vars = [];
        foreach (array_keys(self::COLORS) as $key) {
            $vars[] = '--t-' . strtolower(preg_replace('/([A-Z])/', '-$1', $key) ?? $key) . ': ' . self::cssValue((string) $theme[$key]) . ';';
        }
        $vars[] = '--t-speed: ' . (int) $theme['animationSpeed'] . 'ms;';
        $vars[] = '--t-delay: ' . (int) $theme['animationDelay'] . 'ms;';

        $css = $scope . ' {' . implode(' ', $vars) . '}';

        $image = (string) $theme['image'];
        if ($image !== '') {
            $opacity = max(0, min(100, (int) $theme['imageOpacity'])) / 100;
            $mode = $theme['imageMode'] === 'repeat' ? 'auto' : 'cover';
            $repeat = $theme['imageMode'] === 'repeat' ? 'repeat' : 'no-repeat';
            $css .= $scope . ' .t-card::before{content:"";position:absolute;inset:0;pointer-events:none;'
                . 'background-image:url("' . self::cssUrl($image) . '");background-size:' . $mode . ';'
                . 'background-position:center;background-repeat:' . $repeat . ';opacity:' . $opacity . ';}';
        }

        $envelopeImage = (string) $theme['envelopeImage'];
        if ($envelopeImage !== '') {
            $css .= $scope . ' .t-envelope{background-image:url("' . self::cssUrl($envelopeImage) . '");background-size:cover;background-position:center;}';
        }

        $own = self::safeCss((string) $theme['css']);
        if ($own !== '') {
            // Eigenes CSS bleibt im Geltungsbereich des Themas.
            $css .= "\n" . $scope . ' {}' . "\n" . self::scopeCss($own, $scope);
        }

        return $css;
    }

    /**
     * Selektoren des eigenen CSS mit dem Themen-Geltungsbereich versehen.
     * @keyframes und Medienabfragen bleiben unangetastet.
     */
    private static function scopeCss(string $css, string $scope): string
    {
        $out = '';
        $offset = 0;
        $length = strlen($css);

        while ($offset < $length) {
            $brace = strpos($css, '{', $offset);
            if ($brace === false) {
                break;
            }

            $selector = trim(substr($css, $offset, $brace - $offset));

            // At-Regeln (@keyframes, @media) unverändert übernehmen –
            // inklusive ihres kompletten Blocks.
            if (str_starts_with($selector, '@')) {
                $depth = 0;
                $end = $brace;
                for ($i = $brace; $i < $length; $i++) {
                    if ($css[$i] === '{') {
                        $depth++;
                    } elseif ($css[$i] === '}') {
                        $depth--;
                        if ($depth === 0) {
                            $end = $i;
                            break;
                        }
                    }
                }
                $out .= substr($css, $offset, $end - $offset + 1) . "\n";
                $offset = $end + 1;
                continue;
            }

            $close = strpos($css, '}', $brace);
            if ($close === false) {
                break;
            }

            $body = substr($css, $brace + 1, $close - $brace - 1);
            $parts = array_map('trim', explode(',', $selector));
            $scoped = array_map(
                static fn (string $part): string => $part === '' ? '' : $scope . ' ' . $part,
                $parts
            );

            $out .= implode(', ', array_filter($scoped)) . ' {' . $body . "}\n";
            $offset = $close + 1;
        }

        return $out;
    }

    /** Farbwert absichern (nur das, was in einer CSS-Deklaration stehen darf). */
    private static function cssValue(string $value): string
    {
        $value = str_replace([';', '{', '}', '<', '>', '"', "'"], '', $value);
        return trim(mb_substr($value, 0, 120));
    }

    private static function cssUrl(string $url): string
    {
        return str_replace(['"', '\\', ')', '(', ' '], ['', '', '', '', '%20'], $url);
    }
}
