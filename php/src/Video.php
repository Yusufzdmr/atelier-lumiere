<?php
declare(strict_types=1);

namespace Atelier;

use function Atelier\e;

/**
 * YouTube- und Vimeo-Links erkennen und einbetten – 1:1 die Regeln aus
 * lib/video.ts.
 *
 * Videos werden nicht hochgeladen, sondern beim Anbieter gehostet: das spart
 * Speicher und Transcoding, und auf einem Webhosting-Paket ist beides teuer.
 *
 * Datenschutz: YouTube über youtube-nocookie.com, Vimeo mit dnt=1 – und
 * geladen wird erst nach einem zweiten Klick. Vorher geht keine Anfrage an
 * den Anbieter, nicht einmal für das Vorschaubild.
 */
final class Video
{
    private const YOUTUBE = [
        '#youtu\.be/([\w-]{6,})#i',
        '#youtube\.com/watch\?(?:.*&)?v=([\w-]{6,})#i',
        '#youtube(?:-nocookie)?\.com/embed/([\w-]{6,})#i',
        '#youtube\.com/shorts/([\w-]{6,})#i',
    ];

    private const VIMEO = [
        '#vimeo\.com/(?:video/)?(\d{6,})#i',
        '#player\.vimeo\.com/video/(\d{6,})#i',
    ];

    /** @return array{provider:string,id:string,embedUrl:string,watchUrl:string}|null */
    public static function parse(?string $input): ?array
    {
        $url = trim((string) $input);
        if ($url === '' || preg_match('#^https?://#i', $url) !== 1) {
            return null;
        }

        foreach (self::YOUTUBE as $pattern) {
            if (preg_match($pattern, $url, $m) === 1) {
                return [
                    'provider' => 'youtube',
                    'id'       => $m[1],
                    'embedUrl' => 'https://www.youtube-nocookie.com/embed/' . $m[1] . '?rel=0&modestbranding=1',
                    'watchUrl' => 'https://www.youtube.com/watch?v=' . $m[1],
                ];
            }
        }

        foreach (self::VIMEO as $pattern) {
            if (preg_match($pattern, $url, $m) === 1) {
                return [
                    'provider' => 'vimeo',
                    'id'       => $m[1],
                    'embedUrl' => 'https://player.vimeo.com/video/' . $m[1] . '?dnt=1',
                    'watchUrl' => 'https://vimeo.com/' . $m[1],
                ];
            }
        }

        if (preg_match('#\.(mp4|webm|mov|m4v)(\?|$)#i', $url) === 1) {
            return ['provider' => 'file', 'id' => $url, 'embedUrl' => $url, 'watchUrl' => $url];
        }

        // Unbekannter Link: lieber gar kein Videoblock als ein kaputter Kasten.
        return null;
    }

    public static function isSupported(?string $url): bool
    {
        return self::parse($url) !== null;
    }

    /**
     * Zwei-Klick-Einbettung. Der Kasten zeigt ein eigenes Standbild; erst der
     * Klick lädt den Anbieter (assets/app.js übernimmt das).
     */
    public static function embedBox(string $url, string $title, string $poster = ''): string
    {
        $video = self::parse($url);
        if ($video === null) {
            return '';
        }

        if ($video['provider'] === 'file') {
            // Aspect-Ratio-Rahmen, damit auf dem Handy kein Sprung entsteht,
            // solange das Standbild noch laedt. Native <video controls> zeigt
            // seinen eigenen Play-Knopf – ein eigener Overlay-Kreis darueber
            // waere waehrend der Wiedergabe eine dauerhaft sichtbare Scheibe.
            return '<div class="relative overflow-hidden bg-ink" style="aspect-ratio: 16/9">'
                . '<video controls preload="metadata" playsinline'
                . ' class="absolute inset-0 h-full w-full bg-ink"'
                . ($poster !== '' ? ' poster="' . e($poster) . '"' : '')
                . '><source src="' . e($video['embedUrl']) . '"></video>'
                . '</div>';
        }

        $html = '<div class="relative overflow-hidden bg-ink" style="aspect-ratio: 16/9"'
            . ' data-video data-embed="' . e($video['embedUrl']) . '" data-title="' . e($title) . '">';

        if ($poster !== '') {
            $html .= '<img src="' . e($poster) . '" alt="' . e($title) . '" loading="lazy" decoding="async"'
                . ' class="absolute inset-0 h-full w-full object-cover opacity-55">';
        }

        $html .= '<div class="absolute inset-0 flex flex-col items-center justify-center gap-4 p-6 text-center">'
            . '<button type="button" data-video-load class="border border-cream/50 px-7 py-3 text-[0.68rem] uppercase tracking-[0.2em] text-cream transition-colors hover:bg-cream hover:text-ink">'
            . e(I18n::t('video.load')) . '</button>'
            . '<p class="max-w-sm text-[0.72rem] leading-relaxed text-cream/70">' . e(I18n::t('video.note')) . '</p>'
            . '<a href="' . e($video['watchUrl']) . '" target="_blank" rel="noopener noreferrer"'
            . ' class="text-[0.68rem] uppercase tracking-[0.18em] text-cream/60 underline-offset-4 hover:text-cream hover:underline">'
            . e(I18n::t('video.watch')) . ' ↗</a>'
            . '</div></div>';

        return $html;
    }
}
