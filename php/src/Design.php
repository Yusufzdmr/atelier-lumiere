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

    /**
     * Die Elemente eines Designs als Markup.
     *
     * @param array<string,mixed> $doc
     * @param array<string,string> $values  Ergebnis von bindValues()
     */
    public static function html(array $doc, array $values, string $locale): string
    {
        $doc = self::complete($doc);
        $out = '';

        foreach ($doc['layers'] as $el) {
            $class = 'd-el d-el-' . $el['id'] . ' d-spot-' . $el['spot'];

            if ($el['type'] === 'text' || $el['type'] === 'button') {
                $text = self::resolveText($el, $values, $locale);
                if ($text === '') {
                    continue;
                }
                $out .= '<div class="' . e($class) . '">' . e($text) . '</div>';
                continue;
            }

            if ($el['type'] === 'image' || $el['type'] === 'photo') {
                $src = self::safeSrc($el['src']);
                if ($src === '') {
                    continue;
                }
                // Schmuck ist Schmuck: fuer die Vorlesesoftware nicht vorhanden.
                $out .= '<img class="' . e($class) . '" src="' . e($src) . '" alt="" aria-hidden="true">';
                continue;
            }

            if ($el['type'] === 'shape') {
                $out .= '<div class="' . e($class) . '" aria-hidden="true"></div>';
            }

            // video: Faz 3. Bis dahin wird das Element still uebersprungen.
        }

        return $out;
    }

    /**
     * @param array<string,mixed> $el
     * @param array<string,string> $values
     */
    private static function resolveText(array $el, array $values, string $locale): string
    {
        if ($el['bind'] !== '') {
            // Unbekannter Name wird leer – niemals der Name selbst, sonst
            // steht „couple_names" auf der Einladung.
            return (string) ($values[$el['bind']] ?? '');
        }
        return (string) ($el['text'][$locale] ?? $el['text']['de'] ?? '');
    }

    /**
     * Nur Pfade, die wir selbst vergeben haben.
     *
     * Ein Design kann aus dem Panel kommen oder als JSON eingespielt werden.
     * Ohne diese Pruefung liesse sich ueber die Bildquelle ein fremder Host
     * einbinden – und das faellt genau dann auf, wenn es zu spaet ist.
     */
    private static function safeSrc(string $src): string
    {
        $src = trim($src);
        if ($src === '') {
            return '';
        }

        // Prozentkodierung aufloesen und vergleichen. Unsere eigenen Pfade
        // tragen keine – kommt eine an, stammt der Pfad nicht von uns. Ohne
        // diesen Schritt schluepft %2e%2e an der Punktpruefung vorbei, und
        // erst der Server macht daraus wieder einen Verzeichniswechsel.
        if (rawurldecode($src) !== $src) {
            return '';
        }

        // Erlaubt ist nur, was wir selbst vergeben. Damit fallen Nullbyte,
        // Tabulator, Steuerzeichen und der Doppelpunkt von allein weg.
        if (preg_match('#^/(uploads|assets)/[A-Za-z0-9/._-]+$#', $src) !== 1) {
            return '';
        }

        if (str_contains($src, '..')) {
            return '';
        }

        return $src;
    }

    /**
     * Die dynamischen Felder aus den Daten einer Einladung.
     *
     * @param array<string,mixed> $data
     * @return array<string,string>
     */
    public static function bindValues(array $data, string $locale): array
    {
        $bride = trim((string) ($data['bride'] ?? ''));
        $groom = trim((string) ($data['groom'] ?? ''));
        $date  = trim((string) ($data['date'] ?? ''));

        $couple = $bride;
        if ($bride !== '' && $groom !== '') {
            $couple = $bride . ' & ' . $groom;
        } elseif ($bride === '') {
            $couple = $groom;
        }

        return [
            'couple_names'     => $couple,
            'bride_name'       => $bride,
            'groom_name'       => $groom,
            'initials'         => mb_substr($bride, 0, 1) . mb_substr($groom, 0, 1),
            'wedding_date'     => $date !== '' ? Dates::long($date, $locale) : '',
            'wedding_time'     => trim((string) ($data['time'] ?? '')),
            'location_name'    => trim((string) ($data['venue'] ?? '')),
            'location_address' => trim((string) ($data['address'] ?? '')),
            'invitation_text'  => trim((string) ($data['message'] ?? '')),
            'hashtag'          => trim((string) ($data['hashtag'] ?? '')),
        ];
    }

    /**
     * Was an einem Design noch fehlt.
     *
     * Faz 2 haengt daran die Veroeffentlichungspruefung; hier entsteht nur die
     * Liste. Sie meldet, statt zu blockieren – ein halbfertiges Design soll
     * sich ansehen lassen.
     *
     * @param array<string,mixed> $doc
     * @return list<array{kind:string,element:string,detail:string}>
     */
    public static function warnings(array $doc): array
    {
        $doc = self::complete($doc);
        $out = [];

        foreach ($doc['layers'] as $el) {
            if ($el['bind'] !== '' && !in_array($el['bind'], self::BINDS, true)) {
                $out[] = ['kind' => 'unknown_bind', 'element' => $el['id'], 'detail' => $el['bind']];
            }
            if (($el['type'] === 'image' || $el['type'] === 'photo') && self::safeSrc($el['src']) === '') {
                $out[] = ['kind' => 'missing_src', 'element' => $el['id'], 'detail' => $el['src']];
            }
            if ($el['style']['color'] !== '' && !isset($doc['palette'][$el['style']['color']])) {
                $out[] = ['kind' => 'unknown_color', 'element' => $el['id'], 'detail' => $el['style']['color']];
            }
            if ($el['style']['font'] !== '' && !isset($doc['fonts'][$el['style']['font']])) {
                $out[] = ['kind' => 'unknown_font', 'element' => $el['id'], 'detail' => $el['style']['font']];
            }
        }

        return $out;
    }

    /* -------------------------------- Umzug -------------------------------- */

    /**
     * Ein Thema aus dem bestehenden Motor als Dokument.
     *
     * Der Schmuck traegt dort schon alles, was ein Element hier braucht –
     * Prozentmasse, Ort, Bewegung. Deshalb ist das eine Umrechnung und keine
     * Neuanlage von Hand.
     *
     * Der feste Text der Karte (Namen, Datum, Ort) entsteht hier *nicht*: er
     * steckt heute im Kartenschablone und wird beim Aussaeen gesetzt, wo man
     * die Kaesten am fertigen Bild abmessen kann.
     *
     * @param array<string,mixed> $theme
     * @return array<string,mixed>
     */
    public static function fromTheme(array $theme): array
    {
        $theme = Themes::complete($theme);

        $palette = [];
        foreach ([
            'bg', 'paper', 'paperEdge', 'fg', 'soft', 'accent', 'accentSoft',
            'envelope', 'envelopeFlap', 'envelopeEdge', 'seal', 'sealText', 'petal',
        ] as $key) {
            $value = (string) ($theme[$key] ?? '');
            if ($value === '') {
                continue;
            }
            $palette[$key] = [
                'value'    => $value,
                'label'    => ['de' => $key, 'tr' => $key],
                'customer' => false,
            ];
        }

        $back = [];
        $front = [];

        if ((string) ($theme['image'] ?? '') !== '') {
            $back[] = [
                'id'     => 'bgimage',
                'label'  => 'Hintergrund',
                'type'   => 'image',
                'spot'   => 'page',
                'src'    => (string) $theme['image'],
                'box'    => ['x' => 0, 'y' => 0, 'w' => 100, 'h' => 100,
                             'rotate' => 0, 'opacity' => (int) ($theme['imageOpacity'] ?? 100)],
                'motion' => ['move' => 'none', 'delay' => 0, 'duration' => 0],
            ];
        }

        if ((string) ($theme['envelopeImage'] ?? '') !== '') {
            $back[] = [
                'id'     => 'envimage',
                'label'  => 'Kuvert',
                'type'   => 'image',
                'spot'   => 'envelope',
                'src'    => (string) $theme['envelopeImage'],
                'box'    => ['x' => 0, 'y' => 0, 'w' => 100, 'h' => 100, 'rotate' => 0, 'opacity' => 100],
                'motion' => ['move' => 'none', 'delay' => 0, 'duration' => 0],
            ];
        }

        foreach ((array) ($theme['decorations'] ?? []) as $deco) {
            if (!is_array($deco)) {
                continue;
            }
            $deco = Themes::completeDecoration($deco);
            if ((string) $deco['src'] === '') {
                continue;
            }

            $element = [
                'id'     => (string) $deco['id'],
                'label'  => (string) $deco['label'],
                'type'   => 'image',
                'spot'   => (string) $deco['spot'],
                'src'    => (string) $deco['src'],
                'box'    => [
                    'x'       => (int) $deco['x'],
                    'y'       => (int) $deco['y'],
                    'w'       => (int) $deco['width'],
                    'h'       => 0,
                    'rotate'  => (int) $deco['rotate'],
                    'opacity' => (int) $deco['opacity'],
                ],
                'motion' => [
                    'move'     => (string) $deco['move'],
                    'delay'    => (int) $deco['delay'],
                    'duration' => (int) $deco['duration'],
                ],
            ];

            // Aus einem Ja/Nein wird eine Position in der Liste.
            if ($deco['front']) {
                $front[] = $element;
            } else {
                $back[] = $element;
            }
        }

        return self::complete([
            'id'        => (string) ($theme['id'] ?? ''),
            'slug'      => (string) ($theme['id'] ?? ''),
            'name'      => ['de' => (string) ($theme['name'] ?? ''), 'en' => (string) ($theme['name'] ?? '')],
            'family'    => (string) ($theme['family'] ?? ''),
            'version'   => max(1, (int) ($theme['version'] ?? 1)),
            'palette'   => $palette,
            'layers'    => array_merge($back, $front),
            'animation' => [
                'intro'    => (string) ($theme['intro'] ?? 'none'),
                'idle'     => (string) ($theme['idle'] ?? 'none'),
                'reveal'   => (string) ($theme['reveal'] ?? 'up'),
                'particle' => (string) ($theme['particle'] ?? 'none'),
            ],
        ]);
    }
}
