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
    /**
     * Wie die Karte hereinkommt, nachdem das Kuvert offen ist.
     *
     * Die Namen sind Schlüssel für `invitation.js`; dort steht, was jede
     * Bewegung tatsächlich tut. Ein Eintrag ohne Gegenstück dort fällt
     * auf `rise` zurück, statt die Karte unsichtbar zu lassen.
     */
    public const ANIMATIONS = [
        'seal', 'fade', 'rise', 'zoom', 'zoomOut', 'curtain', 'unfold',
        'flip', 'slideLeft', 'slideRight', 'blur', 'petals', 'none',
    ];

    /**
     * Die Eröffnungsszene: eine gespielte Abfolge, bevor die Karte kommt.
     *
     * Im Unterschied zu ANIMATIONS (eine Bewegung für die Karte) ist das hier
     * eine Szene aus mehreren Schichten mit eigenem Takt – Dunkelheit, Blitz,
     * Korn, Lichtleck. Die Dauer steht in `introDuration()`; `invitation.js`
     * wartet sie ab, bevor es die Karte freigibt.
     */
    public const INTROS = ['none', 'darkroom', 'focus', 'henna', 'party', 'sealLight'];

    /** Wie lange eine Szene läuft (ms). Muss zum Stylesheet passen. */
    public static function introDuration(string $intro): int
    {
        return match ($intro) {
            'darkroom'  => 2800,
            'focus'     => 2600,
            'henna'     => 3800,
            'party'     => 3200,
            'sealLight' => 2400,
            default     => 0,
        };
    }

    public static function introLabel(string $key, string $locale): string
    {
        $labels = [
            'de' => [
                'none'      => 'Keine Szene – Kuvert öffnet direkt',
                'darkroom'  => 'Dunkelkammer: Blitz, Korn, das Bild entwickelt sich',
                'focus'     => 'Schärfe zieht nach: unscharf, dann klar',
                'henna'     => 'Henna: die Linie zeichnet sich, Herzschlag',
                'party'     => 'Fest: Lichtstrahlen und Konfetti',
                'sealLight' => 'Siegel & Licht: Wachs bricht, Gold läuft durch',
            ],
            'en' => [
                'none' => 'No scene — the envelope opens straight away',
                'darkroom' => 'Darkroom: flash, grain, the image develops',
                'focus' => 'Focus pull: out of the blur, into the picture',
                'henna' => 'Henna: the line draws itself, over a heartbeat',
                'party' => 'Celebration: light beams and confetti',
                'sealLight' => 'Seal & light: the wax breaks, gold runs through',
            ],
            'tr' => [
                'none'      => 'Sahne yok – zarf doğrudan açılır',
                'darkroom'  => 'Karanlık oda: flaş, gren, görüntü banyodan çıkar',
                'focus'     => 'Odak kayması: bulanıktan nete',
                'henna'     => 'Kına: desen çizilir, kalp atışı',
                'party'     => 'Kutlama: ışık huzmeleri ve konfeti',
                'sealLight' => 'Mühür & ışık: mum kırılır, altın geçer',
            ],
        ];

        return $labels[$locale][$key] ?? $key;
    }

    /**
     * Wie das geschlossene Kuvert wartet.
     *
     * Das ist das Erste, was ein Gast sieht – und das Einzige, was ihm sagt,
     * dass hier etwas zum Antippen ist. Deshalb hat es eine eigene Achse und
     * nicht nur eine feste Bewegung für alle Themen.
     */
    /*
     * Weniger, mit Absicht.
     *
     * Der Kunde hat die Einladung mit "Cizgifilm gibi" beschrieben und zwei
     * Referenzen gezeigt, die ueberhaupt nichts bewegen. Die Auswahl war
     * vorher 32 Eintraege gross - und eine grosse Auswahl laedt dazu ein,
     * mehrere davon zu nehmen. Was hier fehlt, fehlt nicht aus Versehen.
     *
     * Die zugehoerigen Keyframes im Stylesheet bleiben stehen: versendete
     * Einladungen tragen die alten Werte in ihrem themeSnapshot und zeigen
     * auf sie. Waehlen kann man sie nicht mehr.
     */

    /** Das Atmen bleibt: es ist kein Schmuck, sondern das Zeichen "fass mich an". */
    public const IDLES = ['breathe', 'none'];

    public static function idleLabel(string $key, string $locale): string
    {
        $labels = [
            'de' => [
                'breathe'   => 'Atmet ruhig auf und ab',
                'none'      => 'Steht still',
            ],
            'en' => [
                'breathe' => 'Breathes quietly',
                'none' => 'Stands still',
            ],
            'tr' => [
                'breathe'   => 'Sakince inip kalkar',
                'none'      => 'Hareketsiz durur',
            ],
        ];

        return $labels[$locale][$key] ?? $key;
    }

    /** Wie die Namen erscheinen. */
    public const NAME_ANIMATIONS = ['fade', 'none'];

    /** Konfetti, Schnee, Funken - genau das war gemeint mit "zu viel". */
    public const PARTICLES = ['none'];

    /** Wie die Abschnitte der Karte beim Scrollen kommen. */
    public const REVEALS = ['up', 'none'];

    public static function animationLabel(string $key, string $locale): string
    {
        $labels = [
            'de' => [
                'seal'       => 'Siegel bricht, Karte steigt auf',
                'fade'       => 'Weiches Einblenden',
                'rise'       => 'Steigt von unten auf',
                'zoom'       => 'Wächst heran',
                'zoomOut'    => 'Kommt von vorn zur Ruhe',
                'curtain'    => 'Vorhang öffnet sich',
                'unfold'     => 'Klappt auf',
                'flip'       => 'Dreht sich herein',
                'slideLeft'  => 'Schiebt von rechts herein',
                'slideRight' => 'Schiebt von links herein',
                'blur'       => 'Wird scharf',
                'petals'     => 'Legt sich leicht schräg hin',
                'none'       => 'Ohne Animation',
            ],
            'en' => [
                'seal' => 'The seal breaks, the card rises',
                'fade' => 'Fades in',
                'rise' => 'Rises from below',
                'zoom' => 'Grows into place',
                'zoomOut' => 'Comes forward and settles',
                'curtain' => 'A curtain opens',
                'unfold' => 'Unfolds',
                'flip' => 'Turns in',
                'slideLeft' => 'Slides in from the right',
                'slideRight' => 'Slides in from the left',
                'blur' => 'Comes into focus',
                'petals' => 'Settles slightly askew',
                'none' => 'No animation',
            ],
            'tr' => [
                'seal'       => 'Mühür kırılır, kart yükselir',
                'fade'       => 'Yumuşak belirir',
                'rise'       => 'Aşağıdan yükselir',
                'zoom'       => 'Büyüyerek gelir',
                'zoomOut'    => 'Önden gelip yerine oturur',
                'curtain'    => 'Perde açılır',
                'unfold'     => 'Açılarak gelir',
                'flip'       => 'Dönerek gelir',
                'slideLeft'  => 'Sağdan kayar',
                'slideRight' => 'Soldan kayar',
                'blur'       => 'Netleşir',
                'petals'     => 'Hafif eğik oturur',
                'none'       => 'Animasyonsuz',
            ],
        ];

        return $labels[$locale][$key] ?? $key;
    }

    public static function nameAnimationLabel(string $key, string $locale): string
    {
        $labels = [
            'de' => [
                'fade'    => 'Blendet ein',
                'none'    => 'Ohne Animation',
            ],
            'en' => [
                'fade' => 'Fades in',
                'none' => 'No animation',
            ],
            'tr' => [
                'fade'    => 'Belirir',
                'none'    => 'Animasyonsuz',
            ],
        ];

        return $labels[$locale][$key] ?? $key;
    }

    public static function particleLabel(string $key, string $locale): string
    {
        $labels = [
            'de' => [
                'none'     => 'Nichts',
            ],
            'en' => [
                'none' => 'Nothing',
            ],
            'tr' => [
                'none'     => 'Yok',
            ],
        ];

        return $labels[$locale][$key] ?? $key;
    }

    public static function revealLabel(string $key, string $locale): string
    {
        $labels = [
            'de' => [
                'up'   => 'Von unten herein',
                'none' => 'Alles steht sofort da',
            ],
            'en' => [
                'up' => 'In from below',
                'none' => 'Everything stands there at once',
            ],
            'tr' => [
                'up'   => 'Aşağıdan gelir',
                'none' => 'Hepsi hazır durur',
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

    /**
     * Themen sichern und die Fassung derer hochzaehlen, die sich geaendert
     * haben.
     *
     * Die Nummer ist kein Selbstzweck: eine schon verschickte Einladung haelt
     * ihre eigene Kopie des Themas fest (siehe Invitations::theme). Die Nummer
     * sagt dann, wie weit sie vom heutigen Stand entfernt ist – und ob es sich
     * lohnt, sie auf den neuen Stand zu heben.
     *
     * @param list<array<string,mixed>> $themes
     */
    public static function save(array $themes): void
    {
        $before = [];
        foreach (self::all() as $theme) {
            $before[(string) $theme['id']] = $theme;
        }

        $next = [];
        foreach (array_values($themes) as $theme) {
            $theme = self::complete(is_array($theme) ? $theme : []);
            $old = $before[(string) $theme['id']] ?? null;

            if ($old !== null) {
                // Fassung und Zeitstempel selbst duerfen den Vergleich nicht
                // stoeren, sonst zaehlt jedes Speichern hoch.
                $a = $old;
                $b = $theme;
                unset($a['version'], $a['updatedAt'], $b['version'], $b['updatedAt']);

                if ($a === $b) {
                    $theme['version'] = (int) $old['version'];
                    $theme['updatedAt'] = (string) $old['updatedAt'];
                    $next[] = $theme;
                    continue;
                }

                $theme['version'] = (int) $old['version'] + 1;
            }

            $theme['updatedAt'] = date('c');
            $next[] = $theme;
        }

        Content::mutate(static function (array $content) use ($next): array {
            $content['themes'] = $next;
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
            // Hintergrundvideo *in* der Karte (nicht ueber der Buehne).
            // Wenn gesetzt, liegt der Film hinter dem Karteninhalt; die
            // Papierflaeche wird transparent und der Text hell.
            'backdropVideo'   => '',
            'backdropPoster'  => '',
            // Der Vorspann: ein Film vom echten Kuvert, statt der gezeichneten
            // Klappe. Leer = das bisherige CSS-Kuvert. Getrennt von backdrop*,
            // weil das eine VOR der Karte laeuft und das andere DAHINTER.
            'introVideo'      => '',
            'introPoster'     => '',
            'animation'      => '',
            'intro'          => '',
            'idle'           => '',
            'nameAnimation'  => '',
            'particle'       => '',
            'reveal'         => '',
            'animationSpeed' => '1200',
            'animationDelay' => '0',
            'css'            => '',
            // Ab hier: die modularen Bausteine
            'family'      => '',
            'fonts'       => ['display' => 'cormorant', 'body' => 'jost', 'script' => 'greatvibes', 'scale' => '100', 'tracking' => '0'],
            'scene'       => '',
            'decorations' => [],
            'version'     => 1,
            'updatedAt'   => '',
        ];

        $merged = array_merge($defaults, $theme);
        if (!is_array($merged['sub'])) {
            $merged['sub'] = ['de' => (string) $merged['sub'], 'tr' => (string) $merged['sub']];
        }

        $merged['fonts'] = array_merge($defaults['fonts'], is_array($merged['fonts']) ? $merged['fonts'] : []);
        $merged['decorations'] = array_values(array_map(
            [self::class, 'completeDecoration'],
            array_filter(is_array($merged['decorations']) ? $merged['decorations'] : [], 'is_array')
        ));

        // Ohne eigene Familie steht ein Thema fuer sich allein.
        if ((string) $merged['family'] === '') {
            $merged['family'] = (string) $merged['name'];
        }

        // Bestehende Themen kennen das Feld noch nicht. Statt alle auf dasselbe
        // Motiv zu setzen, bekommt jedes eins, das zu seiner Farbwelt passt –
        // im Panel laesst sich das mit einem Griff aendern.
        if (!in_array((string) $merged['scene'], self::SCENES, true) || (string) $merged['scene'] === '') {
            $merged['scene'] = self::defaultScene((string) $merged['id']);
        }

        // Dasselbe fuer die vier Bewegungen. Alle Themen auf dieselbe Antwort
        // zu setzen waere das Gegenteil von dem, wozu die Auswahl da ist.
        $moves = self::defaultMoves((string) $merged['id']);
        foreach (['animation' => self::ANIMATIONS, 'nameAnimation' => self::NAME_ANIMATIONS,
                  'particle' => self::PARTICLES, 'reveal' => self::REVEALS,
                  'intro' => self::INTROS, 'idle' => self::IDLES] as $field => $allowed) {
            if (!in_array((string) $merged[$field], $allowed, true) || (string) $merged[$field] === '') {
                $merged[$field] = $moves[$field];
            }
        }

        return $merged;
    }

    /**
     * Passende Bewegungen fuer ein Thema, das noch keine gewaehlt hat.
     *
     * @return array{animation:string,nameAnimation:string,particle:string,reveal:string,intro:string,idle:string}
     */
    private static function defaultMoves(string $id): array
    {
        /*
         * Die Tabelle je Thema ist weg, und zwar folgerichtig: sie stand hier,
         * damit nicht alle Themen dieselbe Antwort bekommen - "Alle Themen auf
         * dieselbe Antwort zu setzen waere das Gegenteil von dem, wozu die
         * Auswahl da ist." Nach der Beschneidung gibt es bei vier der sechs
         * Achsen aber nur noch eine Antwort. Eine Tabelle, die zwischen einer
         * Moeglichkeit waehlt, ist keine Tabelle.
         *
         * Die zwei Achsen mit echter Auswahl bleiben unterschieden:
         * animation (ANIMATIONS, zwoelf Bewegungen der Karte) und intro
         * (INTROS, die Auftakte der ersten Fassung).
         */
        $sets = [
            'elysee'   => ['seal',       'sealLight'],
            'sage'     => ['rise',       'focus'],
            'foresta'  => ['rise',       'focus'],
            'blush'    => ['fade',       'darkroom'],
            'lavande'  => ['blur',       'focus'],
            'noir'     => ['flip',       'darkroom'],
            'bordeaux' => ['zoom',       'sealLight'],
            'pearl'    => ['curtain',    'focus'],
            'marbre'   => ['zoomOut',    'sealLight'],
            'azur'     => ['slideRight', 'focus'],
            'terra'    => ['unfold',     'darkroom'],
            'safran'   => ['zoom',       'party'],
            'rubis'    => ['slideLeft',  'party'],
            'moderne'  => ['fade',       'none'],
        ];

        [$animation, $intro] = $sets[$id] ?? ['seal', 'sealLight'];

        return [
            'animation'     => $animation,
            'intro'         => $intro,
            // Ab hier gibt es nichts mehr zu waehlen.
            'nameAnimation' => 'fade',
            'particle'      => 'none',
            'reveal'        => 'up',
            'idle'          => 'breathe',
        ];
    }

    /** Passendes Motiv fuer ein Thema, das noch keins gewaehlt hat. */
    private static function defaultScene(string $id): string
    {
        return match ($id) {
            'sage', 'foresta'       => 'leafy',
            'blush', 'lavande'      => 'bouquet',
            'noir', 'bordeaux'      => 'deco',
            'pearl', 'marbre', 'azur' => 'lace',
            'terra'                 => 'pampas',
            default                 => 'botanical',
        };
    }

    /* ------------------------------ Bausteine ------------------------------- */

    /** Die selbst gehosteten Schriften – mehr gibt es nicht, und das ist gut so. */
    public const FONTS = [
        'cormorant'  => ['label' => 'Cormorant Garamond', 'stack' => 'var(--font-cormorant), "Times New Roman", serif'],
        'jost'       => ['label' => 'Jost', 'stack' => 'var(--font-jost), ui-sans-serif, system-ui, sans-serif'],
        'greatvibes' => ['label' => 'Great Vibes (Kalligrafie)', 'stack' => 'var(--font-greatvibes), "Apple Chancery", cursive'],
    ];

    /**
     * Gezeichnete Hintergrundkunst. Kostet keine Datei und nimmt die Farben
     * des Themas an – im Unterschied zu einem hochgeladenen Bild, das bei
     * jeder Farbaenderung neu gebaut werden muesste.
     */
    public const SCENES = ['none', 'botanical', 'leafy', 'bouquet', 'deco', 'lace', 'pampas'];

    public static function sceneLabel(string $key, string $locale): string
    {
        $labels = [
            'de' => [
                'none'      => 'Keine – nur Farbe',
                'botanical' => 'Zweige in den Ecken',
                'leafy'     => 'Grosser Blattzweig',
                'bouquet'   => 'Blumenbouquet',
                'deco'      => 'Art-déco-Faecher',
                'lace'      => 'Spitzenbogen',
                'pampas'    => 'Pampasgras',
            ],
            'tr' => [
                'none'      => 'Yok – yalnızca renk',
                'botanical' => 'Köşelerde dallar',
                'leafy'     => 'Büyük yapraklı dal',
                'bouquet'   => 'Çiçek buketi',
                'deco'      => 'Art deco yelpaze',
                'lace'      => 'Dantel kemer',
                'pampas'    => 'Pampas otu',
            ],
        ];

        return $labels[$locale][$key] ?? $key;
    }

    /** Wo ein Schmuckelement sitzen kann. */
    public const SPOTS = [
        'card'     => ['de' => 'Auf der Karte', 'tr' => 'Kartın üzerinde'],
        'page'     => ['de' => 'Auf der Seite (hinter der Karte)', 'tr' => 'Sayfada (kartın arkasında)'],
        'envelope' => ['de' => 'Auf dem Kuvert', 'tr' => 'Zarfın üzerinde'],
    ];

    /**
     * Eine Auswahlliste, die den gespeicherten Wert nicht wegwirft.
     *
     * Gefunden am 24.08.2026 an noir: das Dokument trug idle=pulse,
     * reveal=side, particle=spark, nameMove=letters und card=seal, und der
     * Editor bot fuer diese fuenf Achsen nur je zwei bis drei Woerter an -
     * keines davon war der gespeicherte Wert. Ein <select> ohne passende
     * Option waehlt die erste; beim Speichern stand danach breathe, up, none
     * und fade im Dokument.
     *
     * Niemand haette das getan, jeder haette es ausgeloest: einmal oeffnen,
     * einmal speichern. Sichtbar wird es erst auf der Seite, nie im Panel.
     *
     * Der unbekannte Wert kommt nach VORN und nicht ans Ende: er ist der
     * gewaehlte, und er soll dort stehen, wo man ihn sieht, wenn die Liste
     * lang ist.
     *
     * @param list<string> $liste
     * @return list<string>
     */
    public static function withCurrent(array $liste, string $aktuell): array
    {
        if ($aktuell === '' || in_array($aktuell, $liste, true)) {
            return $liste;
        }

        return array_merge([$aktuell], $liste);
    }

    /** Wie ein Schmuckelement hereinkommt. */
    public const MOVES = ['none', 'fade'];

    public static function moveLabel(string $key, string $locale): string
    {
        $labels = [
            'de' => [
                'none'  => 'Steht still',
                'fade'  => 'Blendet ein',
            ],
            'tr' => [
                'none'  => 'Sabit durur',
                'fade'  => 'Belirir',
            ],
        ];

        return $labels[$locale][$key] ?? $key;
    }

    /**
     * Ein Schmuckelement vollstaendig machen.
     *
     * Alle Masse in Prozent: eine Einladung wird oefter auf einem Handy
     * geoeffnet als auf einem Bildschirm, und Prozent skaliert mit.
     *
     * @param array<string,mixed> $deco
     * @return array<string,mixed>
     */
    public static function completeDecoration(array $deco): array
    {
        $defaults = [
            'id'       => '',
            'label'    => '',
            'src'      => '',
            'spot'     => 'card',
            'x'        => '4',
            'y'        => '4',
            'width'    => '20',
            'rotate'   => '0',
            'opacity'  => '100',
            'front'    => false,
            'move'     => 'fade',
            'delay'    => '0',
            'duration' => '1200',
        ];

        $merged = array_merge($defaults, $deco);
        $merged['id'] = preg_replace('/[^a-z0-9]/', '', strtolower((string) $merged['id'])) ?: bin2hex(random_bytes(4));
        $merged['spot'] = array_key_exists((string) $merged['spot'], self::SPOTS) ? (string) $merged['spot'] : 'card';
        $merged['move'] = in_array((string) $merged['move'], self::MOVES, true) ? (string) $merged['move'] : 'fade';
        $merged['front'] = (bool) $merged['front'];

        foreach (['x' => [-50, 150], 'y' => [-50, 150], 'width' => [1, 200], 'rotate' => [-180, 180], 'opacity' => [0, 100]] as $key => [$min, $max]) {
            $merged[$key] = (string) max($min, min($max, (int) $merged[$key]));
        }
        $merged['delay'] = (string) max(0, min(20000, (int) $merged['delay']));
        $merged['duration'] = (string) max(0, min(20000, (int) $merged['duration']));

        return $merged;
    }

    /** Kennung aus einem Namen: nur Kleinbuchstaben, Ziffern und Bindestrich. */
    /* ------------------------------ Lesbarkeit ------------------------------ */

    /**
     * Welche Farbe steht worauf.
     *
     * Wichtig ist das Papier, nicht der Seitenhintergrund: der Text der Karte
     * liegt auf `paper`, und nur dort entscheidet sich, ob man ihn lesen kann.
     *
     * Die Schwellen sind nicht überall gleich. Fließtext folgt der WCAG-Grenze
     * von 4.5:1. Für Akzentlinien und das „&“ wäre dieselbe Grenze falsch –
     * gedecktes Gold auf Creme ist bei einer Hochzeitseinladung Absicht und
     * kein Fehler. Deshalb schlägt der Akzent erst sehr spät an: eine Warnung,
     * die bei jedem Thema erscheint, liest nach dem dritten Mal niemand mehr.
     *
     * @var array<int,array{0:string,1:string,2:float,3:array<string,string>}>
     */
    private const READABLE = [
        ['fg',       'paper', 4.5, ['de' => 'Schrift auf der Karte', 'tr' => 'Karttaki yazı']],
        ['soft',     'paper', 3.0, ['de' => 'Gedämpfte Schrift (Datum, Zusätze)', 'tr' => 'İkincil yazı (tarih, notlar)']],
        ['sealText', 'seal',  3.0, ['de' => 'Initialen im Siegel', 'tr' => 'Mühürdeki harfler']],
        ['accent',   'paper', 1.8, ['de' => 'Akzent (Linien, „&“)', 'tr' => 'Vurgu (çizgi, „&“)']],
    ];

    /**
     * Was an diesem Thema schwer zu lesen ist.
     *
     * Leere Liste heißt: alles in Ordnung. Sonst je Eintrag der Name des
     * Feldes, das gemessene Verhältnis und der Satz dazu.
     *
     * @param array<string,mixed> $theme
     * @return list<array{key:string,label:string,ratio:float,needed:float}>
     */
    public static function readability(array $theme, string $locale = 'de'): array
    {
        $out = [];

        foreach (self::READABLE as [$vorn, $hinten, $grenze, $namen]) {
            $ratio = self::contrast(
                (string) ($theme[$vorn] ?? ''),
                (string) ($theme[$hinten] ?? '')
            );

            if ($ratio === null || $ratio >= $grenze) {
                continue;
            }

            $out[] = [
                'key'    => $vorn,
                'label'  => $namen[$locale] ?? $namen['de'],
                'ratio'  => $ratio,
                'needed' => $grenze,
            ];
        }

        return $out;
    }

    /**
     * Kontrastverhältnis nach WCAG: 1.0 heißt gleiche Farbe, 21.0 ist
     * Schwarz auf Weiß. Null, wenn eine der Farben nicht lesbar war.
     */
    public static function contrast(string $vorn, string $hinten): ?float
    {
        $b = self::rgb($hinten);
        $v = self::rgb($vorn);
        if ($b === null || $v === null) {
            return null;
        }

        // Halbdurchsichtige Schrift (soft steht als rgba da) zuerst auf den
        // Hintergrund rechnen – sonst misst man eine Farbe, die nie zu sehen ist.
        if ($v[3] < 1.0) {
            foreach ([0, 1, 2] as $i) {
                $v[$i] = $v[$i] * $v[3] + $b[$i] * (1 - $v[3]);
            }
        }

        $lv = self::luminance($v);
        $lb = self::luminance($b);

        return (max($lv, $lb) + 0.05) / (min($lv, $lb) + 0.05);
    }

    /** @return array{0:float,1:float,2:float,3:float}|null r,g,b (0-255) und Alpha */
    private static function rgb(string $value): ?array
    {
        $value = trim($value);

        if (preg_match('/^rgba?\(([^)]+)\)$/i', $value, $m) === 1) {
            $teile = array_map('trim', explode(',', $m[1]));
            if (count($teile) < 3) {
                return null;
            }
            return [
                (float) $teile[0],
                (float) $teile[1],
                (float) $teile[2],
                isset($teile[3]) ? (float) $teile[3] : 1.0,
            ];
        }

        $hex = ltrim($value, '#');
        if (strlen($hex) === 3) {
            $hex = $hex[0] . $hex[0] . $hex[1] . $hex[1] . $hex[2] . $hex[2];
        }
        if (preg_match('/^[0-9a-fA-F]{6}([0-9a-fA-F]{2})?$/', $hex) !== 1) {
            return null;
        }

        return [
            (float) hexdec(substr($hex, 0, 2)),
            (float) hexdec(substr($hex, 2, 2)),
            (float) hexdec(substr($hex, 4, 2)),
            strlen($hex) === 8 ? hexdec(substr($hex, 6, 2)) / 255 : 1.0,
        ];
    }

    /** @param array{0:float,1:float,2:float,3:float} $rgb */
    private static function luminance(array $rgb): float
    {
        $lin = static function (float $v): float {
            $v /= 255;
            return $v <= 0.03928 ? $v / 12.92 : pow(($v + 0.055) / 1.055, 2.4);
        };

        return 0.2126 * $lin($rgb[0]) + 0.7152 * $lin($rgb[1]) + 0.0722 * $lin($rgb[2]);
    }

    public static function slug(string $value): string
    {
        // mb_strtolower, nicht strtolower: das eine arbeitet auf Zeichen, das
        // andere auf Bytes. Byteweise blieb aus "Élysée" das grosse É stehen,
        // das die Zeile darunter als Nicht-ASCII wegwarf - die Kennung hiess
        // "lysee", und aus "Şafak Işık" wurde "afak-isik". Aufgefallen an der
        // Schwesterfunktion Design::key(), die denselben Fehler hatte.
        $value = mb_strtolower(trim($value), 'UTF-8');
        $map = ['ä' => 'ae', 'ö' => 'oe', 'ü' => 'ue', 'ß' => 'ss', 'ı' => 'i', 'ş' => 's', 'ğ' => 'g', 'ç' => 'c', 'é' => 'e', 'è' => 'e', 'ê' => 'e', 'à' => 'a', 'â' => 'a', 'î' => 'i'];
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

        // Schriften: nur die beiden selbst gehosteten, sonst laedt die Seite
        // wieder von fremden Servern – und der Hinweis auf der Datenschutzseite
        // stimmt nicht mehr.
        $fonts = is_array($theme['fonts'] ?? null) ? $theme['fonts'] : [];
        $display = self::FONTS[(string) ($fonts['display'] ?? '')] ?? self::FONTS['cormorant'];
        $body = self::FONTS[(string) ($fonts['body'] ?? '')] ?? self::FONTS['jost'];
        $script = self::FONTS[(string) ($fonts['script'] ?? '')] ?? self::FONTS['greatvibes'];
        $vars[] = '--t-display: ' . $display['stack'] . ';';
        $vars[] = '--t-body: ' . $body['stack'] . ';';
        $vars[] = '--t-script: ' . $script['stack'] . ';';

        // Blattgold der Namen: aus dem Akzent gerechnet, damit jedes Thema es
        // bekommt, ohne dass jemand drei weitere Farben pflegen muss.
        [$foilA, $foilB, $foilC] = self::foil((string) $theme['accent']);
        $vars[] = '--t-foil-a: ' . $foilA . ';';
        $vars[] = '--t-foil-b: ' . $foilB . ';';
        $vars[] = '--t-foil-c: ' . $foilC . ';';
        $vars[] = '--t-scale: ' . (max(60, min(160, (int) ($fonts['scale'] ?? 100))) / 100) . ';';
        $vars[] = '--t-tracking: ' . (max(-30, min(80, (int) ($fonts['tracking'] ?? 0))) / 1000) . 'em;';

        $css = $scope . ' {' . implode(' ', $vars) . '}';

        // Die Schriften greifen, ohne dass die Vorlage davon wissen muss.
        $css .= $scope . ' .font-display{font-family:var(--t-display);}';
        $css .= $scope . ' .t-card{font-family:var(--t-body);font-size:calc(1rem * var(--t-scale));letter-spacing:var(--t-tracking);}';

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

        $css .= self::decorationCss($theme, $scope);

        $own = self::safeCss((string) $theme['css']);
        if ($own !== '') {
            // Eigenes CSS bleibt im Geltungsbereich des Themas.
            $css .= "\n" . $scope . ' {}' . "\n" . self::scopeCss($own, $scope);
        }

        return $css;
    }

    /**
     * Die Schmuckelemente als CSS.
     *
     * Jedes Element bekommt seine eigene Regel; die Vorlage setzt nur ein
     * <img> mit der passenden Klasse. So bleibt alles Gestalterische hier und
     * nichts davon im HTML – und die Inhaltsrichtlinie muss keine
     * Ausnahme fuer style-Attribute machen.
     *
     * @param array<string,mixed> $theme
     */
    /**
     * Drei Stufen einer Farbe fuer den Goldverlauf: dunkel, wie sie ist, hell.
     *
     * Nur Hex-Werte lassen sich rechnen. Steht dort ein rgba() – bei den
     * Kantenfarben ueblich –, bleibt es bei der Farbe selbst; der Verlauf ist
     * dann flach, aber nichts sieht kaputt aus.
     *
     * @return array{0:string,1:string,2:string}
     */
    private static function foil(string $color): array
    {
        $hex = ltrim(trim($color), '#');
        if (strlen($hex) === 3) {
            $hex = $hex[0] . $hex[0] . $hex[1] . $hex[1] . $hex[2] . $hex[2];
        }
        if (preg_match('/^[0-9a-f]{6}$/i', $hex) !== 1) {
            return [$color, $color, $color];
        }

        $mix = static function (int $channel, float $amount): int {
            // amount < 0 dunkelt ab, > 0 hellt auf
            $target = $amount < 0 ? 0 : 255;
            return (int) round($channel + ($target - $channel) * abs($amount));
        };

        $rgb = [hexdec(substr($hex, 0, 2)), hexdec(substr($hex, 2, 2)), hexdec(substr($hex, 4, 2))];
        $shade = static fn (float $amount): string => sprintf(
            '#%02X%02X%02X',
            $mix((int) $rgb[0], $amount),
            $mix((int) $rgb[1], $amount),
            $mix((int) $rgb[2], $amount)
        );

        return [$shade(-0.32), '#' . strtoupper($hex), $shade(0.62)];
    }

    private static function decorationCss(array $theme, string $scope): string
    {
        $css = '';
        $moves = [];

        foreach ((array) ($theme['decorations'] ?? []) as $deco) {
            if (!is_array($deco) || (string) ($deco['src'] ?? '') === '') {
                continue;
            }

            $deco = self::completeDecoration($deco);
            $selector = $scope . ' .t-deco-' . $deco['id'];

            $css .= $selector . '{'
                . 'position:absolute;'
                . 'left:' . (float) $deco['x'] . '%;'
                . 'top:' . (float) $deco['y'] . '%;'
                . 'width:' . (float) $deco['width'] . '%;'
                . 'height:auto;'
                . 'opacity:' . ((int) $deco['opacity'] / 100) . ';'
                . 'transform:rotate(' . (float) $deco['rotate'] . 'deg);'
                . 'transform-origin:center;'
                . 'pointer-events:none;'
                . 'z-index:' . ($deco['front'] ? '5' : '0') . ';'
                . '}';

            if ($deco['move'] !== 'none') {
                $name = 't-move-' . $deco['move'];
                $moves[$deco['move']] = true;
                $css .= $selector . '{'
                    . 'animation:' . $name . ' ' . (int) $deco['duration'] . 'ms ease-out '
                    . (int) $deco['delay'] . 'ms both;'
                    . '}';
            }
        }

        // Nur die Bewegungen mitschicken, die auch gebraucht werden.
        foreach (array_keys($moves) as $move) {
            $css .= self::moveKeyframes((string) $move);
        }

        // Wer Bewegung im Betriebssystem abbestellt hat, bekommt sie nicht.
        if ($moves !== []) {
            $css .= '@media (prefers-reduced-motion: reduce){' . $scope . ' [class*="t-deco-"]{animation:none;}}';
        }

        return $css;
    }

    private static function moveKeyframes(string $move): string
    {
        return match ($move) {
            'fade'  => '@keyframes t-move-fade{from{opacity:0}}',
            'rise'  => '@keyframes t-move-rise{from{opacity:0;transform:translateY(14px) rotate(var(--t-rot,0deg))}}',
            'zoom'  => '@keyframes t-move-zoom{from{opacity:0;transform:scale(.86)}}',
            'float' => '@keyframes t-move-float{0%{opacity:0}30%{opacity:1}50%{transform:translateY(-8px)}100%{opacity:1;transform:translateY(0)}}',
            'sway'  => '@keyframes t-move-sway{0%{opacity:0}30%{opacity:1}50%{transform:translateX(6px)}100%{opacity:1;transform:translateX(0)}}',
            default => '',
        };
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
