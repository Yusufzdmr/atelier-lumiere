<?php
declare(strict_types=1);

namespace Atelier;

/**
 * Ein Design als Dokument.
 *
 * Der Unterschied zu Themes: dort hat ein Thema feste Faecher (Papier, Kuvert,
 * Siegel) und daneben eine Liste Schmuckelemente. Hier ist *alles* ein Element
 * in einer geordneten Liste, und Farben und Schriften sind benannte Marken, auf
 * die die Elemente zeigen. Deshalb laesst sich ein Design vollstaendig im Panel
 * bauen, ohne dass jemand eine Zeile Code schreibt.
 *
 * Themes.php bleibt unberuehrt und laeuft weiter.
 */
final class Design
{
    public const CATEGORIES = ['luxury', 'floral', 'modern', 'minimal', 'oriental', 'boho'];
    public const STATUSES   = ['draft', 'active', 'inactive'];
    public const TYPES      = ['image', 'text', 'photo', 'shape', 'button', 'video'];
    public const ALIGNS     = ['left', 'center', 'right'];

    /** Welche dynamischen Felder eine Vorlage einsetzen darf. */
    public const BINDS = [
        'couple_names', 'bride_name', 'groom_name', 'initials',
        'wedding_date', 'wedding_time', 'location_name', 'location_address',
        'invitation_text', 'hashtag',
    ];

    /** Was ein Kunde an einem einzelnen Element duerfen kann. */
    public const PERMISSIONS = ['edit', 'color', 'font', 'photo', 'text', 'hide'];

    /**
     * Grenzen des Kastens: [min, max, standard].
     *
     * Dieselben Zahlen wie in Themes::completeDecoration. Sie sind dort nicht
     * geraten, sondern am Handy gemessen worden – deshalb werden sie hier
     * uebernommen und nicht neu erfunden.
     */
    private const BOX = [
        'x'       => [-50, 150, 4],
        'y'       => [-50, 150, 4],
        'w'       => [1, 200, 20],
        'h'       => [0, 200, 0],
        'rotate'  => [-180, 180, 0],
        'opacity' => [0, 100, 100],
    ];

    /**
     * @param array<string,mixed> $doc
     * @return array<string,mixed>
     */
    public static function complete(array $doc): array
    {
        $defaults = [
            'id'        => '',
            'slug'      => '',
            'name'      => ['de' => '', 'en' => ''],
            'category'  => '',
            'tags'      => [],
            'cover'     => '',
            'family'    => '',
            'status'    => 'draft',
            'version'   => 1,
            'sort'      => 0,
            'canvas'    => ['ratio' => '9:16', 'safe' => 6],
            'palette'   => [],
            'fonts'     => [],
            'layers'    => [],
            'sections'  => [],
            'animation' => ['intro' => 'none', 'idle' => 'none', 'reveal' => 'up', 'particle' => 'none'],
        ];

        $doc = array_merge($defaults, $doc);

        $doc['id']   = self::key((string) $doc['id']);
        $doc['slug'] = self::key((string) $doc['slug']) ?: $doc['id'];

        $doc['name'] = [
            'de' => (string) (is_array($doc['name']) ? ($doc['name']['de'] ?? '') : ''),
            'en' => (string) (is_array($doc['name']) ? ($doc['name']['en'] ?? '') : ''),
        ];

        $doc['status']   = in_array($doc['status'], self::STATUSES, true) ? (string) $doc['status'] : 'draft';
        $doc['category'] = in_array($doc['category'], self::CATEGORIES, true) ? (string) $doc['category'] : '';
        $doc['version']  = max(1, (int) $doc['version']);
        $doc['sort']     = (int) $doc['sort'];
        $doc['cover']    = (string) $doc['cover'];
        $doc['family']   = self::key((string) $doc['family']);

        $doc['tags'] = array_values(array_filter(
            array_map(static fn (mixed $t): string => self::key((string) $t), (array) $doc['tags']),
            static fn (string $t): bool => $t !== ''
        ));

        $canvas = is_array($doc['canvas']) ? $doc['canvas'] : [];
        $doc['canvas'] = [
            'ratio' => (string) ($canvas['ratio'] ?? '9:16'),
            'safe'  => max(0, min(40, (int) ($canvas['safe'] ?? 6))),
        ];

        $palette = [];
        foreach ((array) $doc['palette'] as $key => $entry) {
            $key = self::key((string) $key);
            if ($key === '' || !is_array($entry)) {
                continue;
            }
            $label = is_array($entry['label'] ?? null) ? $entry['label'] : [];
            $palette[$key] = [
                'value'    => (string) ($entry['value'] ?? ''),
                'label'    => ['de' => (string) ($label['de'] ?? $key), 'tr' => (string) ($label['tr'] ?? $key)],
                'customer' => (bool) ($entry['customer'] ?? false),
            ];
        }
        $doc['palette'] = $palette;

        $fonts = [];
        foreach ((array) $doc['fonts'] as $key => $entry) {
            $key = self::key((string) $key);
            if ($key === '' || !is_array($entry)) {
                continue;
            }
            $fonts[$key] = [
                'family'     => (string) ($entry['family'] ?? ''),
                'size'       => max(1, min(400, (int) ($entry['size'] ?? 100))),
                'weight'     => max(100, min(900, (int) ($entry['weight'] ?? 400))),
                'tracking'   => max(-20, min(100, (int) ($entry['tracking'] ?? 0))),
                'lineHeight' => max(50, min(300, (int) ($entry['lineHeight'] ?? 120))),
                'customer'   => (bool) ($entry['customer'] ?? false),
            ];
        }
        $doc['fonts'] = $fonts;

        $doc['layers'] = array_values(array_map(
            [self::class, 'completeElement'],
            array_filter((array) $doc['layers'], 'is_array')
        ));

        $doc['sections'] = array_values(array_filter((array) $doc['sections'], 'is_array'));

        $animation = is_array($doc['animation']) ? $doc['animation'] : [];
        $doc['animation'] = [
            'intro'    => (string) ($animation['intro'] ?? 'none'),
            'idle'     => (string) ($animation['idle'] ?? 'none'),
            'reveal'   => (string) ($animation['reveal'] ?? 'up'),
            'particle' => (string) ($animation['particle'] ?? 'none'),
        ];

        return $doc;
    }

    /**
     * @param array<string,mixed> $el
     * @return array<string,mixed>
     */
    public static function completeElement(array $el): array
    {
        $defaults = [
            'id'          => '',
            'label'       => '',
            'type'        => 'image',
            'spot'        => 'card',
            'box'         => [],
            'src'         => '',
            'bind'        => '',
            'text'        => ['de' => '', 'en' => ''],
            'style'       => [],
            'motion'      => [],
            'permissions' => [],
        ];

        $el = array_merge($defaults, $el);

        $el['id']    = self::key((string) $el['id']) ?: bin2hex(random_bytes(4));
        $el['label'] = (string) $el['label'];
        $el['type']  = in_array($el['type'], self::TYPES, true) ? (string) $el['type'] : 'image';
        $el['spot']  = array_key_exists((string) $el['spot'], Themes::SPOTS) ? (string) $el['spot'] : 'card';
        $el['src']   = (string) $el['src'];

        // Unbekannte Namen bleiben stehen: warnings() soll sie melden koennen.
        // Nur Sonderzeichen fliegen raus. Leerzeichen werden zum Unterstrich,
        // nicht geloescht (dieselbe Logik wie key(), nur mit "_" statt "-").
        $bind = strtolower(trim((string) $el['bind']));
        $bind = (string) preg_replace('/\s+/', '_', $bind);
        $el['bind'] = (string) preg_replace('/[^a-z_]/', '', $bind);

        $text = is_array($el['text']) ? $el['text'] : [];
        $el['text'] = ['de' => (string) ($text['de'] ?? ''), 'en' => (string) ($text['en'] ?? '')];

        $el['box'] = self::completeBox(is_array($el['box']) ? $el['box'] : []);

        $style = is_array($el['style']) ? $el['style'] : [];
        $el['style'] = [
            'font'       => self::key((string) ($style['font'] ?? '')),
            'color'      => self::key((string) ($style['color'] ?? '')),
            'size'       => max(1, min(500, (int) ($style['size'] ?? 100))),
            'align'      => in_array($style['align'] ?? '', self::ALIGNS, true) ? (string) $style['align'] : 'center',
            'autoShrink' => (bool) ($style['autoShrink'] ?? true),
        ];

        $motion = is_array($el['motion']) ? $el['motion'] : [];
        $move = (string) ($motion['move'] ?? 'none');
        $el['motion'] = [
            'move'     => in_array($move, Themes::MOVES, true) ? $move : 'none',
            'delay'    => max(0, min(20000, (int) ($motion['delay'] ?? 0))),
            'duration' => max(0, min(20000, (int) ($motion['duration'] ?? 1200))),
        ];

        $permissions = is_array($el['permissions']) ? $el['permissions'] : [];
        $out = [];
        foreach (self::PERMISSIONS as $name) {
            // Ein Design wird zu geboren. Rechte werden aufgeschlossen, nicht
            // zugesperrt – so kann ein vergessenes Feld nichts kaputtmachen.
            $out[$name] = (bool) ($permissions[$name] ?? false);
        }
        $el['permissions'] = $out;

        return $el;
    }

    /**
     * @param array<string,mixed> $box
     * @return array<string,int>
     */
    public static function completeBox(array $box): array
    {
        $out = [];
        foreach (self::BOX as $key => [$min, $max, $default]) {
            $value = array_key_exists($key, $box) ? (int) $box[$key] : $default;
            $out[$key] = max($min, min($max, $value));
        }
        return $out;
    }

    /**
     * Kennung: nur Kleinbuchstaben, Ziffern und Bindestrich.
     *
     * Leerzeichen werden zum Bindestrich, nicht geloescht: aus „Golden Garden"
     * soll „golden-garden" werden und nicht „goldengarden". Die Kennung steht
     * in der Adresszeile, und dort ist der Bindestrich die Wortgrenze.
     */
    private static function key(string $value): string
    {
        $value = strtolower(trim($value));
        $value = (string) preg_replace('/[\s_]+/', '-', $value);
        $value = (string) preg_replace('/[^a-z0-9-]/', '', $value);
        $value = (string) preg_replace('/-{2,}/', '-', $value);
        return trim($value, '-');
    }

    /* ------------------------------ Darstellung ------------------------------ */

    /**
     * Stilblock eines Designs.
     *
     * Alles wird vom Bereich ($scope) eingeschlossen: zwei Designs koennen auf
     * derselben Seite stehen (Katalog!), ohne sich gegenseitig umzufaerben.
     *
     * @param array<string,mixed> $doc
     */
    public static function css(array $doc, string $scope): string
    {
        $doc = self::complete($doc);
        $css = '';

        $vars = '';
        foreach ($doc['palette'] as $key => $entry) {
            $vars .= '--d-' . $key . ':' . self::safeColor((string) $entry['value']) . ';';
        }
        foreach ($doc['fonts'] as $key => $entry) {
            $vars .= '--df-' . $key . ':' . self::safeFont((string) $entry['family']) . ';';
        }
        if ($vars !== '') {
            $css .= $scope . '{' . $vars . '}';
        }

        $moves = [];

        foreach (array_values($doc['layers']) as $index => $el) {
            $selector = $scope . ' .d-el-' . $el['id'];
            $box = $el['box'];

            $css .= $selector . '{'
                . 'position:absolute;'
                . 'left:' . $box['x'] . '%;'
                . 'top:' . $box['y'] . '%;'
                . 'width:' . $box['w'] . '%;'
                . ($box['h'] > 0 ? 'height:' . $box['h'] . '%;' : 'height:auto;')
                . 'opacity:' . rtrim(rtrim(number_format($box['opacity'] / 100, 2, '.', ''), '0'), '.') . ';'
                . 'transform:rotate(' . $box['rotate'] . 'deg);'
                . 'transform-origin:center;'
                // Die Stapelreihenfolge ist die Reihenfolge der Liste. Ein
                // eigenes Feld dafuer waere eine zweite Wahrheit.
                . 'z-index:' . ($index + 1) . ';'
                . '}';

            if ($el['type'] === 'text') {
                $style = $el['style'];
                $css .= $selector . '{'
                    . ($style['font'] !== '' ? 'font-family:var(--df-' . $style['font'] . ');' : '')
                    . ($style['color'] !== '' ? 'color:var(--d-' . $style['color'] . ');' : '')
                    . 'font-size:' . $style['size'] . '%;'
                    . 'text-align:' . $style['align'] . ';'
                    . '}';
            }

            if ($el['motion']['move'] !== 'none') {
                $moves[$el['motion']['move']] = true;
                $css .= $selector . '{'
                    . 'animation:d-move-' . $el['motion']['move'] . ' '
                    . $el['motion']['duration'] . 'ms ease-out '
                    . $el['motion']['delay'] . 'ms both;'
                    . '}';
            }
        }

        foreach (array_keys($moves) as $move) {
            $css .= self::moveKeyframes((string) $move);
        }

        if ($moves !== []) {
            $css .= '@media (prefers-reduced-motion: reduce){' . $scope . ' .d-el{animation:none;}}';
        }

        return $css;
    }

    /** Dieselben Bewegungen wie im bestehenden Themenmotor, eigener Namensraum. */
    private static function moveKeyframes(string $move): string
    {
        return match ($move) {
            'fade'  => '@keyframes d-move-fade{from{opacity:0}}',
            'rise'  => '@keyframes d-move-rise{from{opacity:0;transform:translateY(14px)}}',
            'float' => '@keyframes d-move-float{from{opacity:0;transform:translateY(-10px)}}',
            'sway'  => '@keyframes d-move-sway{from{opacity:0;transform:rotate(-4deg)}}',
            'zoom'  => '@keyframes d-move-zoom{from{opacity:0;transform:scale(0.94)}}',
            default => '',
        };
    }

    /**
     * Eine Farbe, die keine Regel schliessen kann.
     *
     * Der Wert kommt aus dem Panel und landet ungefiltert in einem Stilblock.
     * Ohne diese Pruefung reicht ein „}" im Feld, um die Seite umzubauen.
     */
    private static function safeColor(string $value): string
    {
        $value = trim($value);
        if (preg_match('/^#[0-9A-Fa-f]{3,8}$/', $value) === 1) {
            return $value;
        }
        if (preg_match('/^rgba?\(\s*[0-9.,%\s]+\)$/', $value) === 1) {
            return $value;
        }
        return 'transparent';
    }

    /** Schriftname aus demselben Grund: nur Buchstaben, Ziffern, Leerzeichen, Komma, Bindestrich. */
    private static function safeFont(string $value): string
    {
        $value = trim($value);
        if ($value === '' || preg_match('/^[A-Za-z0-9 ,\-]+$/', $value) !== 1) {
            return 'inherit';
        }
        return $value;
    }
}
