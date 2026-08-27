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
            // Der Oeffnungsfilm. Er steht im DOKUMENT und nicht im lebenden
            // Thema: fromTheme() kopiert ihn einmal, danach friert er mit dem
            // Sockel ein. Eine versendete Einladung oeffnet also morgen so wie
            // heute, auch wenn der Grafiker das Thema inzwischen umgebaut hat -
            // dieselbe Zusage wie fuer alles andere am Sockel.
            //
            // Achtung, zwei Bedeutungen von "intro": animation.intro ist die
            // Auftaktbewegung, dieser Block ist der Film davor.
            'intro'     => ['video' => '', 'poster' => ''],
            /*
             * Der Grund unter den Abschnitten.
             *
             * Leer heisst NICHT "keiner": leer heisst "derselbe wie auf der
             * Karte" - die Vorlage sucht sich dann die hinterste Bildebene
             * selbst. Das ist der Normalfall und der Grund, warum es kein
             * Pflichtfeld ist. Wer unten etwas anderes will, traegt es hier
             * ein.
             */
            'sectionsBg' => '',
            // Das Blatt des SCHLUSSES. Ayhan: "Son sayfa da ayri eklensin -
            // bastaki sayfa cicekler yukarda, son sayfada asagida." Es liegt
            // ueber dem grossen Blatt, unten, in eigener Groesse.
            'sectionsBgEnd' => '',
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

        $doc['sectionsBg'] = self::safeSrc((string) $doc['sectionsBg']);
        $doc['sectionsBgEnd'] = self::safeSrc((string) $doc['sectionsBgEnd']);

        $intro = is_array($doc['intro']) ? $doc['intro'] : [];
        $doc['intro'] = [
            'video'  => self::safeSrc((string) ($intro['video'] ?? '')),
            'poster' => self::safeSrc((string) ($intro['poster'] ?? '')),
            /*
             * Wie lange der Film laeuft, bevor die Karte kommt.
             *
             * Null heisst "so lang wie der Film selbst" - das ist genau das
             * bisherige Verhalten, also aendert sich an keiner Vorlage
             * etwas, die nie etwas eingetragen hat. Eine Zahl deckelt ihn:
             * wer einen Film von zwoelf Sekunden hinlegt und drei eintraegt,
             * bekommt drei.
             *
             * In SEKUNDEN und nicht in Millisekunden. Der Grafiker denkt in
             * Sekunden, das Feld steht in Sekunden, und umgerechnet wird
             * genau einmal - dort, wo die Buehne das Attribut schreibt.
             * Zwei Umrechnungen waeren zwei Stellen, an denen sich ein
             * Faktor 1000 verstecken kann.
             */
            'seconds' => max(0.0, min(20.0, (float) ($intro['seconds'] ?? 0))),
        ];

        $doc['tags'] = array_values(array_filter(
            array_map(static fn (mixed $t): string => self::key((string) $t), (array) $doc['tags']),
            static fn (string $t): bool => $t !== ''
        ));

        $canvas = is_array($doc['canvas']) ? $doc['canvas'] : [];

        /*
         * Zwei Zahlen mit einem Doppelpunkt, sonst nichts.
         *
         * Der Wert kommt als freier Text aus dem Panel und landet in einem
         * style-Attribut ("aspect-ratio: 768 / 1376"). Ungeprueft traegt er
         * ein Anfuehrungszeichen mit hinein und bricht aus dem Attribut aus.
         * Die Ausgabestellen entkommen ihn zusaetzlich - aber richtig ist die
         * Pruefung hier, weil es dieselbe Zahl an mehreren Stellen ist und
         * eine vergessene Stelle sonst wieder offen stuende.
         */
        $ratio = trim((string) ($canvas['ratio'] ?? '9:16'));
        $doc['canvas'] = [
            'ratio' => preg_match('/^\d{1,5}:\d{1,5}$/', $ratio) === 1 ? $ratio : '9:16',
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

        /*
         * Die Abschnitte gehen durch dieselbe Tuer wie alles andere.
         *
         * Frueher stand hier nur ein Filter, und wer vollstaendige Abschnitte
         * brauchte, musste DesignSections::complete() selbst rufen. Die
         * oeffentliche Seite tat es - css(), visible() und html() rufen es
         * jedes fuer sich. Das Panel tat es nicht, und ein Dokument, dessen
         * Abschnitte aelter sind als das Feld style, warf dort eine Warnung
         * mitten ins Formular. Die Warnung landete im Eingabefeld und beim
         * naechsten Speichern als Farbmarke im Dokument.
         *
         * Eine Stelle statt sechs: wer liest, bekommt vollstaendige Abschnitte.
         */
        $doc = DesignSections::complete($doc);

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
            'poster'      => '',
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

        // Der Poster ist ein Bild, keine zweite Quelle: er wird sofort
        // geprueft und nicht erst beim Zeichnen. Ein fremder Host hat in
        // einem poster-Attribut nichts verloren - dieselbe Regel wie bei src,
        // nur frueher, weil hier kein Zweig ihn spaeter noch abfangen wuerde.
        $el['poster'] = self::safeSrc((string) $el['poster']);

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
     * Adresstauglicher Schluessel: Kleinbuchstaben, Ziffern, Bindestrich.
     *
     * Oeffentlich, weil die Abschnitte dieselbe Regel brauchen. Zwei
     * Normalisierer hiessen zwei Antworten auf "was ist ein Schluessel", und
     * die zweite faellt beim ersten Umlaut auseinander.
     */
    public static function key(string $value): string
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
            /*
             * Die Groesse der Marke ist ein FAKTOR, keine Groesse.
             *
             * Wie gross eine einzelne Zeile ist, weiss nur die Ebene - eine
             * Ueberschrift und eine Bildunterschrift koennen dieselbe
             * Auszeichnungsschrift tragen und muessen trotzdem verschieden
             * gross sein. Was der Marke gehoert, ist das Verhaeltnis: "alles
             * in dieser Schrift eine Spur groesser". 100 heisst unveraendert,
             * die Ebenen behalten also ihre Groessen.
             *
             * Bisher stand die Zahl zwar im Dokument und wurde aus dem
             * Formular gelesen, aber nirgends geschrieben - sie war die
             * einzige der vier Angaben ohne Wirkung. Genau die, nach der
             * Ayhan im Panel gesucht hat.
             */
            $vars .= '--dfs-' . $key . ':' . ($entry['size'] / 100) . ';';
        }
        // Der Bereich wird zum Bezugsrahmen: 1cqw ist ein Prozent seiner
        // Breite. Das gilt unabhaengig von Palette und Schriften – sonst
        // haette ein Design ohne beide keinen Bezug, und die Schriftgroesse
        // fiele still auf den geerbten Wert zurueck (design_shape.php prueft
        // genau diesen Fall).
        $css .= $scope . '{container-type:inline-size;' . $vars . '}';

        /*
         * Die Namen duerfen umbrechen, wo bindValues() umbricht - und nur
         * sie. pre-line laesst den Umbruch stehen, faltet aber alle anderen
         * Leerraeume wie sonst auch zusammen; ein versehentliches Leerzeichen
         * im Namensfeld bleibt also folgenlos.
         *
         * Am gebundenen Namen und nicht an einer Ebenen-Kennung: WELCHE Ebene
         * die Namen traegt, entscheidet der Grafiker je Vorlage. Der Name der
         * Bindung ist das einzige, was in allen gleich heisst.
         */
        $css .= $scope . ' .d-el[data-bind="couple_names"]{white-space:pre-line;}';

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
                // Nur wirksam, wenn eine Hoehe gesetzt ist: bei height:auto
                // bestimmt das Bild seine Proportion selbst und object-fit
                // hat nichts zu tun. Mit einer Hoehe dagegen wuerde das Bild
                // ohne diese Zeile GEZERRT - und genau das braucht eine
                // fuellende Ebene, etwa ein Hintergrundfoto ueber die ganze
                // Karte. Gemessen vor der Einfuehrung: von 11 Bildebenen
                // lokal und 16 in der Produktion hatte KEINE eine Hoehe, die
                // Zeile aendert also an keiner bestehenden Vorlage etwas.
                . (in_array($el['type'], ['image', 'photo', 'video'], true) ? 'object-fit:cover;' : '')
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

                /*
                 * Die Groesse: Prozent der Kartenbreite, nicht der geerbten
                 * Groesse - sonst waechst die Karte und die Schrift bleibt
                 * stehen. Mal dem Faktor der Marke, wo eine gesetzt ist: die
                 * Ebene sagt, wie gross SIE ist, die Marke, wie gross alles
                 * in ihrer Schrift ist. Ohne Marke bleibt die Zahl allein -
                 * ein var() ohne Quelle machte das ganze calc() ungueltig.
                 */
                $groesse = ($style['size'] / 10) . 'cqw';
                if ($style['font'] !== '' && isset($doc['fonts'][$style['font']])) {
                    $groesse = 'calc(' . $groesse . ' * var(--dfs-' . $style['font'] . ', 1))';
                }

                $css .= $selector . '{'
                    . ($style['font'] !== '' ? 'font-family:var(--df-' . $style['font'] . ');' : '')
                    . ($style['color'] !== '' ? 'color:var(--d-' . $style['color'] . ');' : '')
                    . 'font-size:' . $groesse . ';'
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

                /*
                 * ZWEI Regeln, nicht eine - und die erste ist die wichtige.
                 *
                 * Die Bewegung an die Marke zu haengen reicht nicht: solange
                 * sie fehlt, laeuft keine Animation, und ohne Animation steht
                 * das Element bei seiner eigenen Deckkraft. Der Text stand
                 * also waehrend des ganzen Vorspanns sichtbar da und sprang
                 * erst beim Setzen der Marke auf null zurueck, um dann
                 * einzublenden. Gemessen: 1/1/1 fuer vier Sekunden, dann
                 * 0.13/0/0. Genau das hat der Kunde gesehen.
                 *
                 * Deshalb sagt die Marke jetzt DREI Dinge statt zwei:
                 *
                 *   fehlt   kein Skript - der Text steht da. So bleibt die
                 *           Vorschau im Panel und im Assistenten heil, die
                 *           invitation.js gar nicht laedt.
                 *   "false" Skript da, Kuvert noch zu - der Text ist noch
                 *           nicht.
                 *   "true"  die Karte liegt frei - jetzt kommt er.
                 *
                 * Die Marke sitzt am <html>, nicht an der Buehne: dasselbe
                 * Skript bedient beide Fassungen, und in der ersten gibt es
                 * keine .d-stage.
                 */
                $css .= '[data-karte-frei="false"] ' . $selector . '{opacity:0;}';

                $css .= '[data-karte-frei="true"] ' . $selector . '{'
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

    /**
     * Schriftname aus demselben Grund wie safeColor(): nur Buchstaben, Ziffern,
     * Leerzeichen, Komma, Bindestrich.
     *
     * Oeffentlich aus demselben Grund: der Assistent klaert eine Schriftfamilie
     * beim Schreiben, nicht der Renderer beim Drucken.
     */
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
                $out .= '<div class="' . e($class) . '"'
                    . ($el['bind'] !== '' ? ' data-bind="' . e($el['bind']) . '"' : '')
                    . '>' . e($text) . '</div>';
                continue;
            }

            if ($el['type'] === 'image' || $el['type'] === 'photo') {
                $src = self::safeSrc($el['src']);
                if ($src === '') {
                    /*
                     * Ohne Quelle faellt eine image-Ebene weg: sie ist der
                     * Schmuck des Grafikers, und was er nicht hinterlegt hat,
                     * gibt es schlicht nicht.
                     *
                     * Eine photo-Ebene ist etwas anderes - sie ist der PLATZ
                     * des Paares und darf leer beginnen. Ihr Knoten bleibt
                     * deshalb stehen, nur versteckt: sonst haette die Vorschau
                     * im Assistenten nichts, wohin sie das gerade gewaehlte
                     * Bild legen koennte, und das Paar waehlte einen
                     * Hintergrund, ohne ihn je zu sehen. Ein <img> ohne src
                     * wuerde in manchen Browsern ein kaputtes Symbol zeigen,
                     * darum hidden - die Grundregel [hidden]{display:none}
                     * greift hier, weil css() fuer Ebenen kein display setzt.
                     */
                    if ($el['type'] !== 'photo') {
                        continue;
                    }
                    $out .= '<img class="' . e($class) . '" alt="" aria-hidden="true" hidden>';
                    continue;
                }
                // Schmuck ist Schmuck: fuer die Vorlesesoftware nicht vorhanden.
                $out .= '<img class="' . e($class) . '" src="' . e($src) . '" alt="" aria-hidden="true">';
                continue;
            }

            if ($el['type'] === 'shape') {
                $out .= '<div class="' . e($class) . '" aria-hidden="true"></div>';
            }

            if ($el['type'] === 'video') {
                $src = self::safeSrc($el['src']);

                // Kein autoplay. Das Attribut wuerde den Film hinter dem
                // geschlossenen Kuvert laufen lassen - unsichtbar, und im
                // Mobilfunk bezahlt. invitation.js startet ihn, wenn die
                // Karte frei liegt (und gar nicht bei reduzierter Bewegung).
                $attr = ' muted loop playsinline preload="metadata" aria-hidden="true"';

                if ($src === '') {
                    // Wie bei photo: der Knoten bleibt stehen, damit die
                    // Vorschau im Assistenten etwas hat, worin sie das
                    // gerade Gewaehlte zeigen kann.
                    $out .= '<video class="' . e($class) . '"' . $attr . ' hidden></video>';
                    continue;
                }

                $poster = self::safeSrc($el['poster']);

                $out .= '<video class="' . e($class) . '" src="' . e($src) . '"'
                    . ($poster !== '' ? ' poster="' . e($poster) . '"' : '')
                    . $attr . '></video>';
            }
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
     *
     * Oeffentlich aus demselben Grund wie safeColor()/safeFont(): der
     * Assistent klaert einen Bildpfad beim Schreiben, nicht der Renderer
     * beim Drucken.
     */
    public static function safeSrc(string $src): string
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

    /*
     * Wie safeSrc, aber fuer Ton - und der darf auch von auswaerts kommen.
     *
     * safeSrc laesst nur zu, was wir selbst vergeben (/uploads, /assets). Fuer
     * Bilder und Filme ist das richtig: sie gehoeren zur Vorlage. Ein Lied
     * liegt oft schon irgendwo, und es dann erst herunterzuladen und wieder
     * hochzuladen ist ein Umweg ohne Gewinn - "linkle yukleme yapabilelim
     * muzik".
     *
     * Nur https. Nicht aus Formalismus: die Einladung selbst laeuft ueber
     * https, und ein Lied ueber http wuerde der Browser als gemischten Inhalt
     * verweigern - der Ton bliebe stumm, ohne dass jemand sagen koennte,
     * warum.
     *
     * Und nur die Adresse, kein Markup: das Ergebnis landet in einem
     * src-Attribut, also faellt alles weg, was ein Anfuehrungszeichen, einen
     * Winkel oder Steuerzeichen traegt.
     */
    /**
     * Ein Lied von auswaerts, als Rahmen - aber erst nach einem Klick.
     *
     * "Muzigi youtube veya spotify ile gomme." Zwei Sachen daran sind keine
     * Gestaltungsfrage, sondern stehen fest:
     *
     * Erstens gibt es "Hintergrundmusik, aber von YouTube" nicht. Die Gestalt
     * music/default haengt den Ton an das Oeffnen des Kuverts und laesst ihn
     * unter der Seite laufen; ein fremder Rahmen kann weder das eine noch das
     * andere. Was eingebettet wird, ist immer ein SICHTBARER Spieler.
     *
     * Zweitens darf bis zum Antippen kein Aufruf zu YouTube gehen. Diese
     * Methode liefert deshalb nur die ADRESSE; den Rahmen baut erst der Klick
     * (Zwei-Klick-Loesung, siehe DesignSections::musik).
     *
     * Eine weisse Liste und keine schwarze: das Feld ist ein Textfeld, in das
     * jemand alles schreiben kann. Erkannt werden die drei Schreibweisen, die
     * man aus der Adresszeile kopiert; heraus kommt entweder eine bekannte
     * Einbettungsadresse oder gar nichts. Die Kennung wird dabei nicht
     * durchgereicht, sondern NEU zusammengesetzt - so kann aus ihr nichts
     * mitkommen, was im Attribut Aerger macht.
     *
     * Ueber youtube-nocookie.com, wie ueberall hier: Http.php fuehrt die
     * Adresse ohnehin schon in frame-src.
     *
     * Spotify fehlt mit Absicht: eingebettet spielt es Nichtangemeldeten nur
     * dreissig Sekunden vor, und die meisten Gaeste sind nicht angemeldet.
     * Ein halbes Lied ist schlechter als ein Link.
     *
     * @return string leer, wenn nichts Bekanntes erkannt wurde
     */
    public static function safeEinbettung(string $src): string
    {
        $src = trim($src);
        if ($src === '' || !str_starts_with($src, 'https://')) {
            return '';
        }

        $teile = parse_url($src);
        if ($teile === false || !isset($teile['host'])) {
            return '';
        }

        $host = strtolower($teile['host']);
        $weg  = (string) ($teile['path'] ?? '');
        $kennung = '';

        if ($host === 'youtu.be') {
            $kennung = ltrim($weg, '/');
        } elseif (in_array($host, [
            'youtube.com', 'www.youtube.com', 'm.youtube.com',
            /*
             * Die eigene Ausgabe gehoert dazu, und das ist keine Spielerei.
             *
             * Geprueft wird ZWEIMAL: einmal beim Speichern (SectionRegistry
             * legt schon die fertige Adresse ab) und einmal beim Drucken.
             * Erkennt die zweite Pruefung die erste nicht wieder, faellt der
             * Abschnitt still weg - und niemand sieht, warum.
             */
            'youtube-nocookie.com', 'www.youtube-nocookie.com',
        ], true)) {
            if (str_starts_with($weg, '/embed/')) {
                $kennung = substr($weg, strlen('/embed/'));
            } else {
                parse_str((string) ($teile['query'] ?? ''), $fragen);
                $kennung = (string) ($fragen['v'] ?? '');
            }
        }

        // Elf Zeichen aus genau diesem Vorrat - so sieht eine YouTube-Kennung
        // aus, und nur so darf sie ins Attribut.
        if (preg_match('~^[A-Za-z0-9_-]{11}$~', $kennung) !== 1) {
            return '';
        }

        return 'https://www.youtube-nocookie.com/embed/' . $kennung;
    }

    public static function safeAudio(string $src): string
    {
        $src = trim($src);
        if ($src === '') {
            return '';
        }

        // Das eigene Haus zuerst - dieselbe Pruefung wie ueberall sonst.
        $eigen = self::safeSrc($src);
        if ($eigen !== '') {
            return $eigen;
        }

        if (!str_starts_with($src, 'https://')) {
            return '';
        }
        // Alles, was in einem src-Attribut oder im Markup Aerger macht.
        if (preg_match('~[\s\"<>]~', $src) === 1) {
            return '';
        }
        if (str_contains($src, chr(39)) || str_contains($src, chr(92))) {
            return '';
        }
        if (filter_var($src, FILTER_VALIDATE_URL) === false) {
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

        /*
         * Das Und steht auf einer EIGENEN Zeile.
         *
         * Mit einem blossen Leerzeichen entscheidet der Umbruch, wo es
         * landet, und auf einer hochkant stehenden Karte landet es immer
         * hinten am ersten Namen: "Sophia &" / "Maximilian". Von Hand liess
         * es sich nicht loesen - der Text ist EIN gebundener Wert, und ein
         * Zeilenumbruch im Namensfeld waere ein Umbruch im Namen.
         *
         * Also echte Zeilenumbrueche im Wert. Sie wirken nur dort, wo die
         * Regel in css() sie wirken laesst (white-space:pre-line auf der
         * gebundenen Ebene); ueberall sonst faellt ein Umbruch im HTML auf
         * ein Leerzeichen zusammen und die Namen lesen sich wie bisher.
         * Deshalb braucht auch keine andere Stelle etwas davon zu wissen.
         */
        $couple = $bride;
        if ($bride !== '' && $groom !== '') {
            $couple = $bride . "\n&\n" . $groom;
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
            // Einmal kopiert, nicht nachgeschlagen: siehe complete().
            'intro'     => [
                'video'  => (string) ($theme['introVideo'] ?? ''),
                'poster' => (string) ($theme['introPoster'] ?? ''),
            ],
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
     * Was hier NICHT steht, steht mit Absicht nicht hier: box und canvas.
     * Die Kaesten gehoeren der vierten Phase. Die Abschnitte kommen mit der
     * dritten Phase herein - sie werden gelesen. tests/design_admin.php
     * haelt diese Grenze.
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

        // Der Oeffnungsfilm. Beide Felder duerfen ausdruecklich leer
        // abgeschickt werden: ohne Film laeuft wieder die gezeichnete Klappe,
        // und genau das will jemand, der das Feld leert. complete() prueft
        // beide Adressen.
        foreach (['video', 'poster'] as $teil) {
            $wert = $text('intro_' . $teil);
            if ($wert !== null) {
                $doc['intro'][$teil] = $wert;
            }
        }

        // Leer abschicken ist erlaubt und heisst "wieder so lang wie der
        // Film" - dieselbe Haltung wie beim Filmpfad daneben.
        if (isset($post['intro_sekunden'])) {
            $doc['intro']['seconds'] = (float) $post['intro_sekunden'];
        }

        // Leer abschicken ist erlaubt und heisst "wieder wie die Karte".
        $grund = $text('sectionsbg');
        if ($grund !== null) {
            $doc['sectionsBg'] = $grund;
        }

        $schluss = $text('sectionsbg_end');
        if ($schluss !== null) {
            $doc['sectionsBgEnd'] = $schluss;
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

            // Eigenes Feld und nicht src: ein Video hat zwei Adressen, und
            // wer den Film tauscht, will nicht jedes Mal auch das Standbild
            // neu setzen muessen. complete() prueft beide.
            $bild = $text('posterpfad_' . $id);
            if ($bild !== null) {
                $doc['layers'][$i]['poster'] = $bild;
            }

            /*
             * Der Kasten. Bis hierher stand hier nichts: eine Ebene liess
             * sich faerben, beschriften und bewegen, aber nicht hinstellen.
             * Wer eine neue anlegte, bekam einen von drei festen Zuschnitten
             * und danach nie wieder eine Handhabe - die letzte Stelle, an der
             * eine Vorlage nur ueber die Datenbank entstehen konnte.
             *
             * Jede Zahl einzeln und nur, wenn das Feld wirklich da war: ein
             * Formular, das nur eine Ebene nennt, darf die anderen nicht auf
             * null ziehen. Geklemmt wird hier nicht, sondern in completeBox() -
             * dort stehen die Grenzen, und zwei Stellen mit denselben Zahlen
             * laufen frueher oder spaeter auseinander.
             */
            foreach (['x', 'y', 'w', 'h', 'rotate', 'opacity'] as $mass) {
                if (isset($post['box_' . $mass . '_' . $id])) {
                    $doc['layers'][$i]['box'][$mass] = (int) $post['box_' . $mass . '_' . $id];
                }
            }
            if (isset($post['box_anchor_' . $id])) {
                $doc['layers'][$i]['box']['anchor'] = (string) $post['box_anchor_' . $id];
            }

            // Die Schriftgroesse gehoert zum Stil, nicht zum Kasten - sie
            // steht im Formular aber daneben, weil sie dort gesucht wird.
            // Geklemmt wird auch sie erst in completeElement().
            if (isset($post['style_size_' . $id])) {
                $doc['layers'][$i]['style']['size'] = (int) $post['style_size_' . $id];
            }
            // Die Spiegelungen sind Haken und werden wie die Rechte gelesen:
            // da heisst an, weg heisst aus. Das darf hier stehen, weil genau
            // ein Formular diese Funktion aufruft - der Assistent des Paares
            // geht einen anderen Weg.
            foreach (['flipx', 'flipy'] as $spiegel) {
                $doc['layers'][$i]['box'][$spiegel] = isset($post['box_' . $spiegel . '_' . $id]) ? 1 : 0;
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

        /*
         * Die Reihenfolge - und damit der Stapel, denn Design::css() schreibt
         * z-index als index+1. Es gibt kein Feld je Ebene, sondern EINE Reihe
         * von Kennungen: "nach vorn" ist dann ein Tausch zweier Nachbarn und
         * kein Zahlenraten, und zwei Ebenen koennen nicht auf derselben Stufe
         * landen.
         *
         * Wer nicht in der Reihe steht, ist geloescht. Loeschen und Umordnen
         * sind dieselbe Bewegung, deshalb dasselbe Feld - zwei Wege koennten
         * die eine Aenderung speichern und die andere verlieren.
         *
         * Fehlt das Feld ganz, bleibt die Liste unangetastet: ein Aufrufer,
         * der von Ebenen nichts weiss, soll sie nicht abraeumen. Eine LEERE
         * Reihe dagegen ist eine Aussage - das Formular schickt das Feld immer
         * mit, und ein veraltetes faengt die Fassungspruefung im Controller
         * ab, bevor es hier ankommt.
         */
        if (isset($post['ebenen_reihenfolge'])) {
            $nach    = [];
            $genannt = [];
            foreach (explode(',', (string) $post['ebenen_reihenfolge']) as $kennung) {
                $kennung = self::key(trim($kennung));
                // Zweimal genannt bleibt einmal: sonst stuende dieselbe Ebene
                // doppelt im Dokument und traege zwei z-Indizes.
                if ($kennung === '' || isset($genannt[$kennung])) {
                    continue;
                }
                foreach ($doc['layers'] as $ebene) {
                    if ((string) $ebene['id'] === $kennung) {
                        $nach[] = $ebene;
                        $genannt[$kennung] = true;
                        break;
                    }
                }
            }
            $doc['layers'] = $nach;
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

        /*
         * Die Abschnitte kommen indiziert herein (sec_*_0, sec_*_1 …), damit
         * die Reihenfolge im Formular die Reihenfolge im Dokument wird - genau
         * wie bei den Ebenen der Index den z-Index bestimmt. Ein Eintrag ohne
         * Kennung oder mit unbekanntem Typ faellt in DesignSections::complete()
         * still weg; hier wird nur eingesammelt.
         */
        /*
         * Welche Zeilen, in welcher Reihenfolge.
         *
         * Bisher WAR der Index die Reihenfolge: sec_*_0 stand vor sec_*_1.
         * Das reicht, solange die Zeilen untereinander in einem Formular
         * stehen - aber nicht, sobald man sie in einer Liste schieben kann.
         * Ein Feldname ist die Kennung einer Zeile; aenderte er sich beim
         * Schieben, verloere jedes Feld dabei seinen Wert.
         *
         * Deshalb dieselbe Loesung wie bei den Ebenen: eine Reihe von
         * Nummern in einem versteckten Feld. Wer nicht darin steht, ist
         * geloescht - Umordnen und Loeschen sind dieselbe Bewegung.
         *
         * Fehlt das Feld, zaehlt wie bisher der Index. Ein Aufrufer, der von
         * Abschnitten nichts weiss, soll nichts umsortieren.
         */
        $reihe = [];
        if (isset($post['sec_reihenfolge'])) {
            foreach (explode(',', (string) $post['sec_reihenfolge']) as $nummer) {
                $nummer = trim($nummer);
                if ($nummer !== '' && ctype_digit($nummer)) {
                    $reihe[] = (int) $nummer;
                }
            }
        } else {
            $reihe = range(0, 39);
        }

        $abschnitte = [];
        foreach ($reihe as $i) {
            if (!isset($post['sec_type_' . $i])) {
                continue;
            }
            $typ = Security::clean($post['sec_type_' . $i] ?? '', 24);

            /*
             * Welche Einstellungen dieser Abschnitt ueberhaupt hat, weiss der
             * Katalog - und er weiss es erst, wenn der Typ gelesen ist.
             *
             * Deshalb wird hier nicht blind alles eingesammelt, was mit
             * sec_set_ anfaengt: ein Ort hat einen Kartenlink, ein Countdown
             * nicht. Ein Wert ohne Schema waere ein Schluessel, der jahrelang
             * im Dokument mitreist, ohne je etwas zu tun.
             *
             * Haken werden gelesen wie die Rechte: da heisst an, weg heisst
             * aus. Das Formular schickt die Zeile immer mit, also ist ein
             * fehlender Haken eine Aussage und kein Zufall.
             */
            $einstellungen = [];
            foreach (SectionRegistry::settings($typ) as $schluessel => $schema) {
                $name = 'sec_set_' . $schluessel . '_' . $i;
                if ((string) $schema['type'] === 'bool') {
                    $einstellungen[$schluessel] = isset($post[$name]);
                    continue;
                }
                if (isset($post[$name])) {
                    $einstellungen[$schluessel] = $post[$name];
                }
            }

            /*
             * Was der Grafiker vorschreibt. Welche Schluessel es gibt, sagt der
             * Katalog - dieselbe Quelle wie bei den Einstellungen, und aus
             * demselben Grund: ein Wert ohne Schema reist jahrelang im
             * Dokument mit, ohne je etwas zu tun.
             */
            $vorgaben = [];
            foreach (SectionRegistry::inputs($typ) as $schluessel => $feld) {
                $name = 'sec_def_' . $schluessel . '_' . $i;
                if (isset($post[$name])) {
                    $vorgaben[$schluessel] = Security::clean($post[$name], (int) $feld['max']);
                }
            }

            $abschnitte[] = [
                'id'      => Security::clean($post['sec_id_' . $i] ?? '', 64),
                'defaults' => $vorgaben,
                'type'    => $typ,
                // Eine unbekannte Variante faellt in DesignSections::complete()
                // auf die Voreinstellung zurueck - hier wird nur eingesammelt.
                'variant' => Security::clean($post['sec_variant_' . $i] ?? '', 48),
                'settings' => $einstellungen,
                'title'   => [
                    'de' => Security::clean($post['sec_title_de_' . $i] ?? '', 120),
                    'en' => Security::clean($post['sec_title_en_' . $i] ?? '', 120),
                ],
                'enabled' => isset($post['sec_on_' . $i]),
                'style'   => [
                    'color' => Security::clean($post['sec_color_' . $i] ?? '', 64),
                    'font'  => Security::clean($post['sec_font_' . $i] ?? '', 64),
                    // Nur eingesammelt. Ob der Pfad taugt, entscheidet
                    // DesignSections::complete() mit safeSrc() - eine Stelle,
                    // dieselbe wie fuer jeden anderen Pfad im Dokument.
                    'bg'    => Security::clean($post['sec_bg_' . $i] ?? '', 256),
                    'bgFit' => Security::clean($post['sec_bgfit_' . $i] ?? '', 16),
                ],
                'permissions' => [
                    'edit' => isset($post['perm_sec_edit_' . $i]),
                    'hide' => isset($post['perm_sec_hide_' . $i]),
                ],
            ];
        }
        $doc['sections'] = $abschnitte;
        $doc = DesignSections::complete($doc);

        // complete() zieht die Grenzen: unbekannte Enums fallen auf die
        // Voreinstellung, Zahlen werden geklemmt, Rechte zu Wahrheitswerten.
        return self::complete($doc);
    }

    /**
     * Einen Startsatz hinlegen.
     *
     * Fuegt nur an, was noch fehlt: eine Art, die schon steht, kommt nicht
     * ein zweites Mal. Der Grund ist der Umgang damit - man drueckt den Knopf
     * nicht einmal und nie wieder, sondern probiert einen Satz, nimmt zwei
     * Abschnitte weg, drueckt den naechsten. Ein Satz, der stur anhaengt,
     * haette nach dem dritten Versuch vier Ortsabschnitte im Dokument.
     *
     * Die Kennung kommt aus der Art und bekommt eine Zahl, wenn sie belegt
     * ist. Zwei Abschnitte mit derselben Kennung waeren im Stilblock ein und
     * derselbe.
     *
     * Die Rechte stehen auf offen: ein Startsatz, den das Paar nicht fuellen
     * darf, ist ein Satz leerer Ueberschriften. Wer das enger will, macht es
     * hinterher zu - das ist ein Haken, und das Zumachen ist die Ausnahme.
     *
     * @param array<string,mixed> $doc
     * @return array<string,mixed>
     */
    public static function withStarter(array $doc, string $name): array
    {
        $satz = SectionRegistry::starter($name);
        if ($satz === null) {
            return $doc;
        }

        $doc = DesignSections::complete($doc);

        $vorhanden = [];
        $kennungen = [];
        foreach ($doc['sections'] as $abschnitt) {
            $vorhanden[(string) $abschnitt['type']] = true;
            $kennungen[(string) $abschnitt['id']] = true;
        }

        foreach ($satz['sections'] as $eintrag) {
            $art = (string) $eintrag['type'];
            if (isset($vorhanden[$art])) {
                continue;
            }

            $kennung = $art;
            $zaehler = 2;
            while (isset($kennungen[$kennung])) {
                $kennung = $art . '-' . $zaehler;
                $zaehler++;
            }
            $kennungen[$kennung] = true;
            $vorhanden[$art] = true;

            $doc['sections'][] = [
                'id'      => $kennung,
                'type'    => $art,
                'variant' => (string) ($eintrag['variant'] ?? SectionRegistry::DEFAULT_VARIANT),
                'title'   => $eintrag['title'],
                'enabled' => true,
                'permissions' => ['edit' => true, 'hide' => true],
            ];
        }

        return DesignSections::complete($doc);
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

    /*
     * Eine Vorlage endgueltig wegnehmen.
     *
     * Ohne diesen Weg wuchs die Liste nur: aktiv/inaktiv nimmt eine Vorlage
     * aus dem Schaufenster, aber nicht aus dem Panel, und wer ausprobiert,
     * legt Probevorlagen an.
     *
     * Die Einladungen daran bleiben stehen und werden weiter angezeigt: jede
     * traegt in design_snapshot eine eingefrorene Kopie der Vorlage, und
     * genau dafuer ist sie da. Was danach nicht mehr geht, ist das
     * Auffrischen auf eine neuere Fassung - es gibt keine mehr. Deshalb
     * nennt die Rueckfrage im Panel ihre Zahl, bevor jemand drueckt.
     */
    /*
     * Die Nummer, mit der eine neue Vorlage ganz vorn steht.
     *
     * Alle Vorlagen tragen von Haus aus sort=0, und dann entscheidet der
     * Slug - eine neue hiess also "testyusuf" und landete zwischen "test2" und
     * "video". Wer gerade etwas angelegt hat, sucht es aber nicht im Alphabet,
     * sondern oben: "yeni actigim tasarim ilk siraya gecsin".
     *
     * Eine kleiner als die kleinste. Die Reihenfolge der uebrigen bleibt
     * dabei unangetastet - es wird nichts umnummeriert, nur davorgesetzt.
     *
     * Achtung, das gilt auch fuer das Schaufenster: die Liste dort nimmt
     * dieselbe Reihenfolge. Eine neue Vorlage steht aber als Entwurf da und
     * ist dort ohnehin erst zu sehen, wenn sie jemand veroeffentlicht.
     */
    public static function sortVorn(): int
    {
        $row = Db::one('SELECT MIN(sort) AS kleinste FROM designs');
        $kleinste = $row['kleinste'] ?? null;

        return $kleinste === null ? 0 : ((int) $kleinste) - 1;
    }

    public static function delete(string $id): void
    {
        Db::run('DELETE FROM designs WHERE id = ?', [$id]);
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
