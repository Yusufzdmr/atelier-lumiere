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
        'wedding_date', 'wedding_weekday', 'wedding_time',
        'location_name', 'location_address',
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
        // Spiegeln ist keine Drehung. Der alte Motor stellt dieselbe
        // Zeichnung mit scale:-1 1 bzw. scale:1 -1 in die vier Ecken; ohne
        // die beiden Schalter laesst sich das nicht abbilden - aufgefallen
        // beim Umzug von Noir, dessen Szene vier Ecken hat.
        'flipx'   => [0, 1, 0],
        'flipy'   => [0, 1, 0],
    ];

    /**
     * Von welcher Ecke aus gemessen wird.
     *
     * Der alte Motor klebt Eckstuecke an die Kante („top:0 right:0"), er
     * rechnet nicht. Ohne Anker muesste rechts als „100 minus Breite"
     * geschrieben werden - und das verrutscht, sobald der Kasten ein anderes
     * Seitenverhaeltnis bekommt. Eckschmuck ist das haeufigste Muster
     * ueberhaupt, deshalb kann das Format es benennen.
     */
    public const ANCHORS = ['topleft', 'topright', 'bottomleft', 'bottomright'];

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
            // Wie die Karte hereinkommt und wie die Namen erscheinen. Beides
            // steht im alten Motor in eigenen Feldern und wurde hier zuerst
            // vergessen - ohne sie laesst sich die Abfolge „Siegel bricht,
            // Karte steigt auf" nicht nachbauen.
            'card'     => (string) ($animation['card'] ?? 'none'),
            'nameMove' => (string) ($animation['nameMove'] ?? 'none'),
            'speed'    => max(0, min(20000, (int) ($animation['speed'] ?? 1200))),
            'delay'    => max(0, min(20000, (int) ($animation['delay'] ?? 0))),
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
            // Nur fuer shape: die weichen Farbflecken hinter der Karte. Ohne
            // die beiden laesst sich ein bestehendes Design nicht abbilden –
            // das ist beim Umzug von Élysée aufgefallen.
            'blur'       => max(0, min(100, (int) ($style['blur'] ?? 0))),
            'radius'     => max(0, min(50, (int) ($style['radius'] ?? 0))),
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

        $anchor = (string) ($box['anchor'] ?? '');
        $out['anchor'] = in_array($anchor, self::ANCHORS, true) ? $anchor : 'topleft';

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
        // mb_strtolower, nicht strtolower: das eine arbeitet auf Zeichen, das
        // andere auf Bytes. Byteweise bleibt aus "Élysée" ein grosses É stehen,
        // das die Zeile darunter wegwirft - der Slug hiesse "lysee". Dieselbe
        // Tabelle wie in Themes::slug(), damit es nicht zwei Wahrheiten gibt.
        $value = mb_strtolower(trim($value), 'UTF-8');
        $value = strtr($value, [
            'ä' => 'ae', 'ö' => 'oe', 'ü' => 'ue', 'ß' => 'ss',
            'ı' => 'i', 'ş' => 's', 'ğ' => 'g', 'ç' => 'c',
            'é' => 'e', 'è' => 'e', 'ê' => 'e', 'à' => 'a', 'â' => 'a', 'î' => 'i',
        ]);
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
            // Gewicht, Laufweite und Zeilenhoehe ebenfalls als Variable: nur so
            // kann die Vorschau im Panel sie ohne Speichern aendern. Eine feste
            // Zahl in der Elementregel braeuchte eine Karte Element->Marke, und
            // die gibt es im DOM nicht.
            $vars .= '--dfw-' . $key . ':' . (int) $entry['weight'] . ';';
            $vars .= '--dft-' . $key . ':' . ($entry['tracking'] / 100) . 'em;';
            $vars .= '--dfl-' . $key . ':' . ($entry['lineHeight'] / 100) . ';';
        }
        // Der Bereich wird zum Bezugsrahmen: 1cqw ist ein Prozent seiner
        // Breite. Das gilt unabhaengig von Palette und Schriften – sonst
        // haette ein Design ohne beide keinen Bezug, und die Schriftgroesse
        // fiele still auf den geerbten Wert zurueck (design_shape.php prueft
        // genau diesen Fall).
        $css .= $scope . '{container-type:inline-size;' . $vars . '}';

        $moves = [];

        foreach (array_values($doc['layers']) as $index => $el) {
            $selector = $scope . ' .d-el-' . $el['id'];
            $box = $el['box'];

            $css .= $selector . '{'
                . 'position:absolute;'
                // Welche zwei Kanten geschrieben werden, sagt der Anker.
                . (str_contains($box['anchor'], 'right') ? 'right:' : 'left:') . $box['x'] . '%;'
                . (str_starts_with($box['anchor'], 'bottom') ? 'bottom:' : 'top:') . $box['y'] . '%;'
                . 'width:' . $box['w'] . '%;'
                . ($box['h'] > 0 ? 'height:' . $box['h'] . '%;' : 'height:auto;')
                . 'opacity:' . rtrim(rtrim(number_format($box['opacity'] / 100, 2, '.', ''), '0'), '.') . ';'
                . 'transform:rotate(' . $box['rotate'] . 'deg)'
                // Reihenfolge wie bei den Einzeleigenschaften des Originals:
                // erst drehen, dann spiegeln.
                . ($box['flipx'] || $box['flipy']
                    ? ' scale(' . ($box['flipx'] ? '-1' : '1') . ',' . ($box['flipy'] ? '-1' : '1') . ')'
                    : '')
                . ';'
                . 'transform-origin:center;'
                // Die Stapelreihenfolge ist die Reihenfolge der Liste. Ein
                // eigenes Feld dafuer waere eine zweite Wahrheit.
                . 'z-index:' . ($index + 1) . ';'
                . '}';

            if ($el['type'] === 'text') {
                $style = $el['style'];

                // Die Werte der Schriftmarke, auf die das Element zeigt.
                $schrift = '';
                if ($style['font'] !== '' && isset($doc['fonts'][$style['font']])) {
                    $marke = $doc['fonts'][$style['font']];
                    $schrift = 'font-weight:var(--dfw-' . $style['font'] . ');'
                        . 'letter-spacing:var(--dft-' . $style['font'] . ');'
                        . 'line-height:var(--dfl-' . $style['font'] . ');';
                }

                $css .= $selector . '{'
                    . ($style['font'] !== '' ? 'font-family:var(--df-' . $style['font'] . ');' : '')
                    . ($style['color'] !== '' ? 'color:var(--d-' . $style['color'] . ');' : '')
                    // Prozent der Kartenbreite, nicht der geerbten Groesse:
                    // sonst waechst die Karte und die Schrift bleibt stehen.
                    . 'font-size:' . ($style['size'] / 10) . 'cqw;'
                    . 'text-align:' . $style['align'] . ';'
                    // Gewicht, Laufweite und Zeilenhoehe standen bisher in der
                    // Schriftmarke und wurden nie geschrieben - ein Drittel der
                    // Typografie lag tot im Dokument.
                    . $schrift
                    . '}';
            }

            if ($el['type'] === 'shape') {
                $style = $el['style'];
                $rules = '';
                if ($style['color'] !== '') {
                    $rules .= 'background:var(--d-' . $style['color'] . ');';
                }
                if ($style['blur'] > 0) {
                    $rules .= 'filter:blur(' . $style['blur'] . 'px);';
                }
                if ($style['radius'] > 0) {
                    $rules .= 'border-radius:' . $style['radius'] . '%;';
                }
                if ($rules !== '') {
                    $css .= $selector . '{' . $rules . '}';
                }
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
     * Nur was als Farbe durchgeht.
     *
     * Oeffentlich, weil der Assistent denselben Massstab braucht: eine Farbe
     * wird beim Schreiben geklaert, nicht erst beim Drucken. Zwei Antworten
     * auf "was ist eine gueltige Farbe" waeren eine zu viel.
     */
    public static function safeColor(string $value): string
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
    public static function safeFont(string $value): string
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
    public static function html(array $doc, array $values, string $locale, string $spot = ''): string
    {
        $doc = self::complete($doc);
        $out = '';

        foreach ($doc['layers'] as $el) {
            // Leer = alle Ebenen. Sonst nur die eines Ortes: die Vorschau baut
            // Seite, Kuvert und Karte getrennt und schachtelt sie ineinander.
            if ($spot !== '' && $el['spot'] !== $spot) {
                continue;
            }

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
            'wedding_weekday'  => $date !== '' ? Dates::weekday($date, $locale) : '',
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

        // Die Schriften des Themas als Marken. Das Thema nennt nur Schluessel
        // ("cormorant"), die Familie steht in Themes::FONTS. Gewicht, Laufweite
        // und Zeilenhoehe kennt das Thema gar nicht - hier stehen die am
        // Original gemessenen Werte, dieselben, die das Aussaeen in Faz 1 von
        // Hand geschrieben hat.
        $schriftmass = [
            'display' => ['weight' => 300, 'tracking' => 4, 'lineHeight' => 115],
            'body'    => ['weight' => 400, 'tracking' => 0, 'lineHeight' => 150],
            'script'  => ['weight' => 400, 'tracking' => 0, 'lineHeight' => 106],
        ];
        $familien = [
            'cormorant'  => 'Cormorant Garamond',
            'jost'       => 'Jost',
            'greatvibes' => 'Great Vibes',
        ];

        $fonts = [];
        foreach ($schriftmass as $marke => $mass) {
            $schluessel = (string) (($theme['fonts'][$marke] ?? '') ?: '');
            $familie = $familien[$schluessel] ?? '';
            if ($familie === '') {
                continue;
            }
            $fonts[$marke] = [
                'family'     => $familie,
                'size'       => 100,
                'weight'     => $mass['weight'],
                'tracking'   => $mass['tracking'],
                'lineHeight' => $mass['lineHeight'],
                'customer'   => false,
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
            // Fassung NICHT vom Thema uebernehmen: das ist eine neue Zeile in
            // einer neuen Tabelle und hat noch nie eine eigene Speicherung
            // gesehen. complete() gibt ihr den Standard 1; save() zaehlt von
            // da an selbst hoch, wenn sich der Inhalt aendert.
            'palette'   => $palette,
            'fonts'     => $fonts,
            // Der Kopf der Karte des alten Motors ist quer: 632 x 490, gemessen
            // am gerenderten Original. 9:16 waere die Voreinstellung des
            // Formats und hier schlicht falsch.
            'canvas'    => ['ratio' => '632:490', 'safe' => 6],
            'layers'    => array_merge($back, $front),
            // Achtung, zwei Bedeutungen: das Thema hat ein skalares Feld
            // "animation" (der Karteneinzug); das Dokument nennt so den
            // ganzen Block. Deshalb landet der Wert hier unter "card".
            'animation' => [
                'intro'    => (string) ($theme['intro'] ?? 'none'),
                'idle'     => (string) ($theme['idle'] ?? 'none'),
                'reveal'   => (string) ($theme['reveal'] ?? 'up'),
                'particle' => (string) ($theme['particle'] ?? 'none'),
                'card'     => (string) ($theme['animation'] ?? 'none'),
                'nameMove' => (string) ($theme['nameAnimation'] ?? 'none'),
                'speed'    => (int) ($theme['animationSpeed'] ?? 1200),
                'delay'    => (int) ($theme['animationDelay'] ?? 0),
            ],
        ]);
    }

    /* ------------------------------ Speicher ------------------------------ */

    /*
     * Warum die Spalte `data` heisst und nicht `doc`:
     *
     * Db::json() und Db::jsonList() lesen die Spalte mit dem festen Namen
     * `data` – sie nehmen ihn nicht aus der Abfrage. Alle elf JSON-Spalten des
     * bestehenden Schemas heissen deshalb so. Eine Spalte `doc` haette hier
     * still `null` geliefert, und zwar erst bei der ersten Abfrage, die
     * jemand spaeter ohne Alias schreibt.
     */

    /** @return array<string,mixed>|null */
    public static function find(string $slug): ?array
    {
        $doc = Db::json('SELECT data FROM designs WHERE slug = ? LIMIT 1', [$slug]);
        return $doc === null ? null : self::complete($doc);
    }

    /** @return array<string,mixed>|null */
    public static function findById(string $id): ?array
    {
        $doc = Db::json('SELECT data FROM designs WHERE id = ? LIMIT 1', [$id]);
        return $doc === null ? null : self::complete($doc);
    }

    /**
     * @param string $status Leer = alle.
     * @return list<array<string,mixed>>
     */
    public static function all(string $status = ''): array
    {
        $rows = $status === ''
            ? Db::jsonList('SELECT data FROM designs ORDER BY sort, slug')
            : Db::jsonList('SELECT data FROM designs WHERE status = ? ORDER BY sort, slug', [$status]);

        return array_values(array_map([self::class, 'complete'], $rows));
    }

    /**
     * Speichern und die Fassung derer hochzaehlen, die sich geaendert haben.
     *
     * Dieselbe Regel wie bei den Themen: eine verschickte Einladung haelt ihre
     * eigene Kopie fest, und die Nummer sagt, wie weit sie vom heutigen Stand
     * entfernt ist. Wuerde jedes Speichern hochzaehlen, waere die Nummer nichts
     * wert.
     *
     * @param array<string,mixed> $doc
     */
    /**
     * Das Formular auf ein bestehendes Dokument legen.
     *
     * Rein: keine Datenbank, keine Session, kein Zugriff auf $_POST. Was nicht
     * im Formular steht, bleibt wie es war - ein leeres Feld ist kein
     * Loeschbefehl.
     *
     * Was hier NICHT steht, steht mit Absicht nicht hier: box, canvas und
     * sections. Die Kaesten gehoeren der vierten Phase, die Abschnitte der
     * dritten. tests/design_admin.php haelt diese Grenze.
     *
     * @param array<string,mixed> $doc  das gespeicherte Dokument
     * @param array<string,mixed> $post rohe Formularwerte
     * @return array<string,mixed>
     */
    public static function fromPost(array $doc, array $post): array
    {
        $doc = self::complete($doc);
        $text = static fn (string $key): ?string
            => isset($post[$key]) ? Security::clean((string) $post[$key], 200) : null;

        foreach (['de', 'en'] as $sprache) {
            $wert = $text('name_' . $sprache);
            if ($wert !== null && $wert !== '') {
                $doc['name'][$sprache] = $wert;
            }
        }

        $kategorie = $text('category');
        if ($kategorie !== null) {
            $doc['category'] = $kategorie;
        }
        if (isset($post['sort'])) {
            $doc['sort'] = (int) $post['sort'];
        }
        if (isset($post['tags'])) {
            $roh = explode(',', (string) $post['tags']);
            $doc['tags'] = array_values(array_filter(
                array_map(static fn (string $t): string => Security::clean(trim($t), 40), $roh),
                static fn (string $t): bool => $t !== ''
            ));
        }

        foreach (array_keys($doc['palette']) as $marke) {
            $wert = $text('palette_' . $marke);
            if ($wert !== null && $wert !== '') {
                $doc['palette'][$marke]['value'] = $wert;
            }
            foreach (['de' => 'palette_label_de_', 'tr' => 'palette_label_tr_'] as $sprache => $prefix) {
                $etikett = $text($prefix . $marke);
                if ($etikett !== null && $etikett !== '') {
                    $doc['palette'][$marke]['label'][$sprache] = $etikett;
                }
            }
            $doc['palette'][$marke]['customer'] = isset($post['palette_customer_' . $marke]);
        }

        foreach (array_keys($doc['fonts']) as $marke) {
            $familie = $text('font_family_' . $marke);
            if ($familie !== null && $familie !== '') {
                $doc['fonts'][$marke]['family'] = $familie;
            }
            foreach ([
                'weight'     => 'font_weight_',
                'tracking'   => 'font_tracking_',
                'lineHeight' => 'font_line_',
                'size'       => 'font_size_',
            ] as $feld => $prefix) {
                if (isset($post[$prefix . $marke])) {
                    $doc['fonts'][$marke][$feld] = (int) $post[$prefix . $marke];
                }
            }
            $doc['fonts'][$marke]['customer'] = isset($post['font_customer_' . $marke]);
        }

        foreach ($doc['layers'] as $i => $ebene) {
            $id = (string) $ebene['id'];

            foreach (['de', 'en'] as $sprache) {
                $wert = $text('text_' . $sprache . '_' . $id);
                if ($wert !== null && $wert !== '') {
                    $doc['layers'][$i]['text'][$sprache] = $wert;
                }
            }

            $quelle = $text('src_' . $id);
            if ($quelle !== null && $quelle !== '') {
                $doc['layers'][$i]['src'] = $quelle;
            }

            if (isset($post['move_' . $id])) {
                $doc['layers'][$i]['motion']['move'] = (string) $post['move_' . $id];
            }
            foreach (['delay' => 'delay_', 'duration' => 'duration_'] as $feld => $prefix) {
                if (isset($post[$prefix . $id])) {
                    $doc['layers'][$i]['motion'][$feld] = (int) $post[$prefix . $id];
                }
            }

            foreach (self::PERMISSIONS as $recht) {
                $doc['layers'][$i]['permissions'][$recht] = isset($post['perm_' . $recht . '_' . $id]);
            }
        }

        foreach ([
            'intro'    => 'anim_intro',
            'idle'     => 'anim_idle',
            'card'     => 'anim_card',
            'nameMove' => 'anim_name',
            'particle' => 'anim_particle',
            'reveal'   => 'anim_reveal',
        ] as $feld => $name) {
            if (isset($post[$name])) {
                $doc['animation'][$feld] = (string) $post[$name];
            }
        }
        foreach (['speed' => 'anim_speed', 'delay' => 'anim_delay'] as $feld => $name) {
            if (isset($post[$name])) {
                $doc['animation'][$feld] = (int) $post[$name];
            }
        }

        // complete() zieht die Grenzen: unbekannte Enums fallen auf die
        // Voreinstellung, Zahlen werden geklemmt, Rechte zu Wahrheitswerten.
        return self::complete($doc);
    }

    /**
     * Eine Vorlage als neuer Entwurf.
     *
     * Dient zwei Wegen: dem Kopieren im Katalog und dem Uebernehmen eines alten
     * Themas (dort kommt $doc aus fromTheme()). Beide Male gilt dasselbe: neue
     * Kennung aus dem Namen, Entwurf, Fassung eins. Die Fassung der Quelle geht
     * ausdruecklich NICHT mit - "Fassung 7" an einem Eintrag, den es seit einer
     * Minute gibt, waere eine Luege ueber seine Geschichte.
     *
     * @param array<string,mixed> $doc
     * @param array<string,string> $name
     * @return array<string,mixed>
     */
    public static function copy(array $doc, string $kennung, array $name): array
    {
        $doc = self::complete($doc);

        $doc['id']      = self::key($kennung);
        $doc['slug']    = self::key($kennung);
        $doc['name']    = ['de' => (string) ($name['de'] ?? ''), 'en' => (string) ($name['en'] ?? '')];
        $doc['status']  = 'draft';
        $doc['version'] = 1;

        return self::complete($doc);
    }

    /**
     * Einer gemessenen Anordnung ein Thema anziehen.
     *
     * fromTheme() bringt Farben, Schriften und Bewegung mit, aber keine Karte:
     * die Textebenen wurden am gerenderten Original gemessen und stehen in
     * keinem Thema. Wer aus einem Thema eine neue Vorlage macht, nimmt deshalb
     * die Anordnung einer vorhandenen und zieht ihr das Thema an - so wird
     * keine einzige Zahl erfunden.
     *
     * Die gezeichnete Szene wechselt mit, wenn das Thema eine eigene
     * exportiert hat. Hat es keine, bleibt die alte stehen: fremdes Blattwerk
     * ist besser als leere Ecken, und die Meldung im Panel sagt es.
     *
     * @param array<string,mixed> $basis   die Vorlage, deren Anordnung bleibt
     * @param array<string,mixed> $thema   ein Dokument aus fromTheme()
     * @param list<string>        $kunst   Pfade der exportierten Szenenteile
     * @return array<string,mixed>
     */
    public static function dress(array $basis, array $thema, array $kunst): array
    {
        $basis = self::complete($basis);
        $thema = self::complete($thema);

        $basis['palette']   = $thema['palette'];
        $basis['fonts']     = $thema['fonts'];
        $basis['animation'] = $thema['animation'];

        $i = 0;
        foreach ($basis['layers'] as $n => $ebene) {
            if ($ebene['type'] !== 'image' || !str_starts_with((string) $ebene['src'], '/assets/designs/')) {
                continue;
            }
            if (isset($kunst[$i])) {
                $basis['layers'][$n]['src'] = (string) $kunst[$i];
            }
            $i++;
        }

        return self::complete($basis);
    }

    /**
     * Welche Vorlage kann heute in den alten Assistenten?
     *
     * Er prueft ?design= gegen die Themen-Kennungen und ignoriert still, was er
     * nicht kennt. Eine im Panel kopierte Vorlage hat kein Thema desselben
     * Namens - fuer sie gibt es deshalb keinen Knopf, sondern einen Satz. Mit
     * dem eigenen Assistenten (Faz 3B) faellt diese Frage ganz weg, und sie
     * faellt an einer Stelle weg, weil sie nur hier steht.
     *
     * @param list<array<string,mixed>> $designs
     * @param list<string>              $themeIds
     * @return array<string,bool>
     */
    public static function creatable(array $designs, array $themeIds): array
    {
        $karte = [];
        foreach ($designs as $design) {
            $slug = (string) ($design['slug'] ?? '');
            if ($slug === '') {
                continue;
            }
            $karte[$slug] = in_array($slug, $themeIds, true);
        }
        return $karte;
    }

    public static function save(array $doc): void
    {
        $doc = self::complete($doc);
        $old = self::findById($doc['id']);

        if ($old !== null) {
            $a = $old;
            $b = $doc;
            unset($a['version'], $b['version']);
            $doc['version'] = $a === $b ? (int) $old['version'] : (int) $old['version'] + 1;
        }

        Db::run(
            'INSERT INTO designs (id, slug, family, category, status, version, sort, cover, data)
             VALUES (:id, :slug, :family, :category, :status, :version, :sort, :cover, :data)
             ON DUPLICATE KEY UPDATE
               slug = VALUES(slug), family = VALUES(family), category = VALUES(category),
               status = VALUES(status), version = VALUES(version), sort = VALUES(sort),
               cover = VALUES(cover), data = VALUES(data)',
            [
                'id'       => $doc['id'],
                'slug'     => $doc['slug'],
                'family'   => $doc['family'],
                'category' => $doc['category'],
                'status'   => $doc['status'],
                'version'  => $doc['version'],
                'sort'     => $doc['sort'],
                'cover'    => $doc['cover'],
                'data'     => Db::encode($doc),
            ]
        );
    }
}
