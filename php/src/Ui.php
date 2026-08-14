<?php
declare(strict_types=1);

namespace Atelier;

use function Atelier\e;

/**
 * Die Bausteine aus components/ui.tsx als HTML-Funktionen.
 *
 * Bewusst dieselben Klassen wie in der bisherigen Fassung – so bleibt das
 * Aussehen identisch und das Stylesheet unverändert übernehmbar.
 */
final class Ui
{
    /** Abschnitt mit Hintergrundton. */
    public static function sectionOpen(string $tone = 'cream', string $class = '', string $id = ''): string
    {
        $tones = [
            'cream' => 'bg-cream text-ink',
            'sand'  => 'bg-sand/50 text-ink',
            'ink'   => 'bg-ink text-cream',
        ];
        $toneClass = $tones[$tone] ?? $tones['cream'];
        $idAttr = $id !== '' ? ' id="' . e($id) . '"' : '';

        return '<section' . $idAttr . ' class="' . $toneClass . ' px-5 py-20 sm:px-8 sm:py-28 ' . e($class) . '">'
            . '<div class="mx-auto max-w-7xl">';
    }

    public static function sectionClose(): string
    {
        return '</div></section>';
    }

    /** Überschriftenblock. */
    public static function head(string $title, string $eyebrow = '', string $text = '', string $align = 'left', string $tone = 'dark'): string
    {
        $wrap = 'reveal max-w-2xl' . ($align === 'center' ? ' mx-auto text-center' : '');
        $html = '<div class="' . $wrap . '" data-visible="false">';

        if ($eyebrow !== '') {
            $html .= '<div class="eyebrow">' . e($eyebrow) . '</div>';
        }

        $html .= '<h2 class="headline mt-4 text-3xl sm:text-4xl md:text-5xl ' . ($tone === 'light' ? 'text-cream' : 'text-ink')
            . '" style="white-space: pre-line">' . e($title) . '</h2>';

        if ($text !== '') {
            $html .= '<p class="mt-5 text-[0.98rem] leading-relaxed ' . ($tone === 'light' ? 'text-cream/65' : 'text-muted')
                . '">' . e($text) . '</p>';
        }

        return $html . '</div>';
    }

    /** Schaltfläche als Link. */
    public static function btn(string $href, string $label, string $variant = 'solid', string $class = ''): string
    {
        $base = 'inline-flex items-center justify-center px-7 py-3.5 text-[0.72rem] uppercase tracking-[0.2em] transition-all duration-300';
        $variants = [
            'solid'   => 'bg-ink text-cream hover:bg-gold',
            'outline' => 'border border-ink text-ink hover:bg-ink hover:text-cream',
            'light'   => 'border border-cream/40 text-cream hover:bg-cream hover:text-ink',
            'ghost'   => 'text-gold hover:text-ink',
        ];

        return '<a href="' . e($href) . '" class="' . $base . ' ' . ($variants[$variant] ?? $variants['solid']) . ' ' . e($class) . '">'
            . e($label) . '</a>';
    }

    /**
     * Bild mit festem Seitenverhältnis – dadurch springt beim Laden nichts.
     * Ohne Next/Image: die Größen kommen direkt aus der Bildquelle.
     */
    public static function photo(
        string $seed,
        string $alt,
        string $ratio = '3/4',
        string $class = '',
        string $sizes = '(max-width: 768px) 100vw, 33vw',
        int $w = 900,
        int $h = 1200,
        bool $zoom = true,
        bool $eager = false
    ): string {
        $src = Images::img($seed, $w, $h);
        $srcset = implode(', ', [
            Images::img($seed, (int) round($w / 2), (int) round($h / 2)) . ' ' . (int) round($w / 2) . 'w',
            $src . ' ' . $w . 'w',
            Images::img($seed, $w * 2, $h * 2) . ' ' . ($w * 2) . 'w',
        ]);

        return '<div class="relative overflow-hidden bg-sand ' . e($class) . '" style="aspect-ratio: ' . e($ratio) . '">'
            . '<img src="' . e($src) . '" srcset="' . e($srcset) . '" sizes="' . e($sizes) . '"'
            . ' alt="' . e($alt) . '" width="' . $w . '" height="' . $h . '"'
            . ' loading="' . ($eager ? 'eager' : 'lazy') . '" decoding="async"'
            . ' class="absolute inset-0 h-full w-full object-cover transition-transform duration-[1200ms] ease-out'
            . ($zoom ? ' hover:scale-105' : '') . '">'
            . '</div>';
    }


    /** Kopfbild einer Unterseite. */
    public static function pageHero(string $seed, string $title, string $eyebrow = '', string $text = '', string $height = 'md'): string
    {
        $box = $height === 'lg' ? 'h-[68vh] min-h-[520px]' : 'h-[52vh] min-h-[380px]';

        $html = '<section class="relative ' . $box . ' w-full overflow-hidden">'
            . '<img src="' . e(Images::img($seed, 1920, 1200)) . '" alt="' . e($title) . '"'
            . ' class="absolute inset-0 h-full w-full object-cover" fetchpriority="high" decoding="async">'
            . '<div class="absolute inset-0 bg-gradient-to-b from-ink/70 via-ink/45 to-ink/70"></div>'
            . '<div class="relative z-10 mx-auto flex h-full max-w-7xl flex-col justify-end px-5 pb-14 sm:px-8 sm:pb-20">';

        if ($eyebrow !== '') {
            $html .= '<div class="eyebrow text-gold-soft">' . e($eyebrow) . '</div>';
        }

        $html .= '<h1 class="headline mt-4 max-w-4xl text-4xl text-cream sm:text-5xl md:text-6xl">' . e($title) . '</h1>';

        if ($text !== '') {
            $html .= '<p class="mt-5 max-w-2xl text-[0.98rem] leading-relaxed text-cream/75">' . e($text) . '</p>';
        }

        return $html . '</div></section>';
    }

    /** Absätze eines Fließtextes als <p>-Folge. @param list<string> $paragraphs */
    public static function prose(array $paragraphs, string $class = 'prose-lux mt-6'): string
    {
        if ($paragraphs === []) {
            return '';
        }
        $html = '<div class="' . e($class) . '">';
        foreach ($paragraphs as $paragraph) {
            $html .= '<p>' . e($paragraph) . '</p>';
        }
        return $html . '</div>';
    }

    /** Aufzählung. @param list<string> $items */
    public static function bullets(array $items, string $class = 'prose-lux mt-4'): string
    {
        if ($items === []) {
            return '';
        }
        $html = '<ul class="' . e($class) . '">';
        foreach ($items as $item) {
            $html .= '<li>' . e($item) . '</li>';
        }
        return $html . '</ul>';
    }

    /** Kennzahl mit Beschriftung. */
    public static function stat(string $value, string $label, string $tone = 'dark'): string
    {
        return '<div>'
            . '<div class="font-display text-4xl font-light sm:text-5xl ' . ($tone === 'light' ? 'text-cream' : 'text-ink') . '">' . e($value) . '</div>'
            . '<div class="mt-2 text-[0.68rem] uppercase tracking-[0.2em] ' . ($tone === 'light' ? 'text-cream/50' : 'text-muted') . '">' . e($label) . '</div>'
            . '</div>';
    }

    /**
     * Fragen und Antworten. <details> braucht kein JavaScript – das Aufklappen
     * funktioniert auch, wenn ein Skript blockiert wird.
     *
     * @param list<array{q:string,a:string}> $items
     */
    public static function accordion(array $items): string
    {
        $html = '<div class="divide-y divide-sand-deep border-y border-sand-deep">';

        foreach ($items as $item) {
            $html .= '<details class="group">'
                . '<summary class="flex cursor-pointer list-none items-center justify-between gap-6 py-5 text-left">'
                . '<span class="font-display text-lg font-normal text-ink sm:text-xl">' . e($item['q']) . '</span>'
                . '<span class="relative h-4 w-4 shrink-0">'
                . '<span class="absolute left-0 top-1/2 h-px w-4 bg-gold"></span>'
                . '<span class="absolute left-1/2 top-0 h-4 w-px bg-gold transition-transform duration-300 group-open:rotate-90 group-open:opacity-0"></span>'
                . '</span></summary>'
                . '<p class="pb-6 pr-10 text-[0.95rem] leading-relaxed text-muted">' . e($item['a']) . '</p>'
                . '</details>';
        }

        return $html . '</div>';
    }

    /** @param list<array{name:string,href?:string}> $items */
    public static function breadcrumbs(array $items): string
    {
        $html = '<nav class="mb-8 flex flex-wrap items-center gap-2 text-[0.7rem] uppercase tracking-[0.16em] text-muted">';
        $last = count($items) - 1;

        foreach ($items as $i => $item) {
            $html .= '<span class="flex items-center gap-2">';
            $html .= isset($item['href'])
                ? '<a href="' . e($item['href']) . '" class="hover:text-gold">' . e($item['name']) . '</a>'
                : '<span class="text-ink">' . e($item['name']) . '</span>';
            if ($i < $last) {
                $html .= '<span class="text-sand-deep">/</span>';
            }
            $html .= '</span>';
        }

        return $html . '</nav>';
    }

    /** Öffnet einen Block, der beim Scrollen einblendet. */
    public static function revealOpen(int $delay = 0, string $class = '', bool $mask = false): string
    {
        return '<div class="' . ($mask ? 'reveal-mask' : 'reveal') . ' ' . e($class) . '"'
            . ' data-visible="false" style="transition-delay: ' . $delay . 'ms">';
    }

    public static function revealClose(): string
    {
        return '</div>';
    }
}
