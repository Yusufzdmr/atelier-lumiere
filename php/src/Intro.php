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

            // Lumina laeuft nicht als Vorspann-Overlay, sondern als
            // dauerhaftes Hintergrundvideo hinter Kuvert und Karte. Deshalb
            // hier leer – die eigentliche Ausgabe liefert Intro::backdrop(),
            // das invitation.php ausserhalb des Kuverts einbindet.
            'lumina' => '',

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

    /**
     * Hintergrund innerhalb der Karte: liest `theme.backdropVideo` und legt
     * das Video als lebendige Papierflaeche der Karte hinter den Text.
     * Ohne gesetztes Video (die meisten Themen) leerer String.
     *
     * Frueher nur fuer die Kennung 'lumina' fest verdrahtet; jetzt darf jedes
     * Thema im Panel eine mp4/webm-Datei hochladen und bekommt automatisch
     * denselben Effekt.
     *
     * @param array<string,mixed> $theme
     */
    public static function backdrop(string $id, array $theme): string
    {
        $video = (string) ($theme['backdropVideo'] ?? '');
        if ($video === '') {
            return '';
        }

        $poster = (string) ($theme['backdropPoster'] ?? '');
        // Poster darf ein Bild oder – rein historisch – auch das Lumina-GIF
        // sein. Beides greift der Browser als erstes Standbild.
        $posterAttr = $poster !== '' ? ' poster="' . e($poster) . '"' : '';

        // Endung entscheidet ueber den <source type>: mp4/mov -> h264, webm -> vp9.
        $sourceType = str_ends_with(strtolower($video), '.webm') ? 'video/webm' : 'video/mp4';

        $style = '<style>'
            // Papierfarbe ausschalten, damit das Video sichtbar ist.
            . '.t-card--backdrop{background:transparent!important;color:#f6ecd8!important}'
            . '.t-card--backdrop > *{position:relative;z-index:1}'
            // Alle Farbwerte kommen aus dem Theme als inline styles („color:
            // #2A241D") und schlagen die Klassenregel. Auf einer Video-Karte
            // deshalb einheitlich helle Toene erzwingen; goldene Toene bleiben
            // als hellerer Goldton stehen.
            . '.t-card--backdrop [style*="color"]{color:#f6ecd8!important}'
            . '.t-card--backdrop [style*="color:#B08D57"],'
            . '.t-card--backdrop [style*="color:#9E7A45"],'
            . '.t-card--backdrop [style*="color:#D5BA8F"]{color:#E9CB92!important}'
            . '.t-card--backdrop [style*="background:#"]{background:transparent!important}'
            . '.t-card--backdrop [style*="border"]{border-color:rgba(233,203,146,.35)!important}'
            // Eigene Compositing-Schicht, Video ohne separate Opazitaet.
            // !important auf width/height/object-fit muss sein: Tailwinds
            // Preflight setzt `video{height:auto}` und liess sonst schwarze
            // Streifen stehen. .t-cardbg bleibt jetzt transparent (statt
            // #0b0906), damit ausserhalb des Videobereichs keine dunkle
            // Flaeche aufblitzt, wenn der Browser Layoutspruenge macht.
            . '.t-card--backdrop{min-height:70vh;text-shadow:0 1px 4px rgba(0,0,0,.9),0 0 18px rgba(0,0,0,.6)}'
            . '.t-cardbg{position:absolute;inset:0;overflow:hidden;pointer-events:none;z-index:0;background:transparent;contain:paint;transform:translateZ(0)}'
            // Video wieder scharf – kein filter:blur mehr, das die Kunden
            // ausdruecklich abgelehnt haben. Lesbarkeit uebernimmt jetzt der
            // Wash + die Textschatten.
            . '.t-cardbg-vid{position:absolute!important;inset:0!important;height:100%!important;width:100%!important;object-fit:cover!important;transform:translateZ(0)}'
            // Uniformer, ausreichend kraeftiger Wash, damit heller Text auf
            // hellen Reflexionen nicht verschwindet.
            . '.t-cardbg-wash{position:absolute;inset:0;background:linear-gradient(180deg,rgba(11,9,6,.5) 0%,rgba(11,9,6,.55) 45%,rgba(11,9,6,.65) 100%)}'
            . '</style>';

        return $style
            . '<div class="t-cardbg" aria-hidden="true">'
            . '<video class="t-cardbg-vid" autoplay muted loop playsinline preload="auto"' . $posterAttr . '>'
            . '<source src="' . e($video) . '" type="' . e($sourceType) . '">'
            . '</video>'
            . '<span class="t-cardbg-wash"></span>'
            . '</div>';
    }

    /**
     * (Deprecated) Frueher als Vorspann-Overlay; jetzt uebernimmt backdrop().
     * Bleibt als Fallback fuer aeltere Referenzen erhalten.
     */
    private static function luminaSwans(): string
    {
        // Zeiten sind auf introDuration('lumina') = 4200 ms abgestimmt:
        //  0.00–0.35 s  ein
        //  0.35–3.85 s  halten
        //  3.85–4.20 s  aus
        // Frueher als <img> auf ein GIF – 3.2 MB, jedes Frame in der CPU
        // dekodiert, auf dem Handy hakelig. Als <video> mit H.264/VP9 sind es
        // ~120 KB, laeuft hardwarebeschleunigt, keine Ruckler. Zwei Quellen:
        // webm zuerst (kleiner, wo unterstuetzt), mp4 als Fallback fuer Safari.
        $style = '<style>'
            . '.t-intro.ti-lumina{background:#0b0906}'
            . '.t-intro.ti-lumina .ti-lumina-vid{opacity:0;object-fit:cover;height:100%;width:100%}'
            . '.t-intro.ti-lumina[data-playing="true"] .ti-lumina-vid{animation:tiLumina 4.2s ease both}'
            . '.t-intro.ti-lumina .ti-warm{opacity:0}'
            . '.t-intro.ti-lumina[data-playing="true"] .ti-warm{animation:tiWarmShort 4.2s ease-in-out both}'
            . '.t-intro.ti-lumina .ti-vignette{opacity:0}'
            . '.t-intro.ti-lumina[data-playing="true"] .ti-vignette{animation:tiVignette 1s ease-in 3.2s both}'
            . '@keyframes tiLumina{0%{opacity:0;transform:scale(1.06)}12%{opacity:1;transform:scale(1.04)}88%{opacity:1;transform:scale(1)}100%{opacity:0;transform:scale(1)}}'
            . '</style>';

        return $style
            . '<video class="ti-lumina-vid" autoplay muted loop playsinline preload="auto" poster="/assets/intro/lumina-swans.gif">'
            . '<source src="/assets/intro/lumina-swans.webm" type="video/webm">'
            . '<source src="/assets/intro/lumina-swans.mp4" type="video/mp4">'
            . '</video>'
            . '<span class="ti-warm"></span>'
            . '<span class="ti-vignette"></span>';
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
