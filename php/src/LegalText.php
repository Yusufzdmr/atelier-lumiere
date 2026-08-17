<?php
declare(strict_types=1);

namespace Atelier;

use function Atelier\e;

/**
 * Rendert die im Adminbereich gepflegten Rechtstexte.
 *
 * Dieselbe winzige Auszeichnung wie in components/LegalBody.tsx – bewusst
 * keine Markdown-Bibliothek:
 *   Leerzeile   -> neuer Absatz
 *   "- " Zeile  -> Aufzählungspunkt
 *   **fett** · `code` · [Text](URL)
 *   {{consent}} -> Schaltfläche für die Cookie-Einstellungen
 */
final class LegalText
{
    /**
     * Der Satz, der über jeder nicht-deutschen Fassung steht.
     *
     * Er kommt aus dem Code und nicht aus dem Adminbereich: Verbindlich ist
     * die deutsche Fassung, und dieser Hinweis darf nicht versehentlich
     * wegredigiert werden oder von ihr abweichen.
     */
    private const BINDING = [
        'en' => 'This English version is a convenience translation. '
              . 'Only the German version is legally binding.',
    ];

    /** @param array<string,mixed> $page */
    public static function render(array $page): string
    {
        $vars = self::vars();
        $locale = I18n::locale();

        $html = '<h1 class="headline text-4xl">' . e(I18n::pick($page['title'] ?? null)) . '</h1>'
            . '<div class="prose-lux mt-10 max-w-2xl">';

        $binding = self::BINDING[$locale] ?? '';
        if ($binding !== '') {
            $html .= '<p class="border-l-2 border-gold pl-4 text-[0.85rem] text-muted">' . e($binding) . '</p>';
        }

        foreach ((array) ($page['sections'] ?? []) as $section) {
            $html .= '<section>';
            $heading = I18n::pick($section['heading'] ?? null);
            if ($heading !== '') {
                $html .= '<h2>' . e(self::fill($heading, $vars)) . '</h2>';
            }
            $html .= self::blocks(I18n::pick($section['body'] ?? null), $vars);
            $html .= '</section>';
        }

        $note = I18n::pick($page['note'] ?? null);
        if ($note !== '') {
            $html .= '<div class="text-[0.8rem]">' . self::blocks($note, $vars) . '</div>';
        }

        return $html . '</div>';
    }

    /** Platzhalter aus den gepflegten Kontaktdaten. @return array<string,string> */
    private static function vars(): array
    {
        $c = Content::get('contact');

        return [
            'legalName' => 'Atelier Lumière Hochzeitsfotografie',
            'owner'     => 'Julian Roth',
            'street'    => (string) ($c['street'] ?? ''),
            'zip'       => (string) ($c['zip'] ?? ''),
            'city'      => (string) ($c['city'] ?? ''),
            'email'     => (string) ($c['email'] ?? ''),
            'phone'     => (string) ($c['phoneHuman'] ?? ''),
        ];
    }

    /** @param array<string,string> $vars */
    private static function fill(string $text, array $vars): string
    {
        return preg_replace_callback(
            '/\{(\w+)\}/',
            static fn (array $m): string => $vars[$m[1]] ?? $m[0],
            $text
        ) ?? $text;
    }

    /** @param array<string,string> $vars */
    private static function blocks(string $body, array $vars): string
    {
        $html = '';
        $parts = preg_split('/\n{2,}/', str_replace("\r\n", "\n", self::fill($body, $vars))) ?: [];

        foreach ($parts as $raw) {
            $text = trim($raw);
            if ($text === '') {
                continue;
            }

            if ($text === '{{consent}}') {
                $html .= '<button type="button" data-consent-open'
                    . ' class="my-2 border border-ink px-6 py-3 text-[0.68rem] uppercase tracking-[0.2em] text-ink transition-colors hover:bg-ink hover:text-cream">'
                    . e(I18n::t('cookie.settings')) . '</button>';
                continue;
            }

            $rows = explode("\n", $text);
            $isList = $rows !== [] && !in_array(false, array_map(static fn (string $r): bool => str_starts_with($r, '- '), $rows), true);

            if ($isList) {
                $html .= '<ul>';
                foreach ($rows as $row) {
                    $html .= '<li>' . self::inline(substr($row, 2)) . '</li>';
                }
                $html .= '</ul>';
                continue;
            }

            $html .= '<p>' . implode('<br>', array_map([self::class, 'inline'], $rows)) . '</p>';
        }

        return $html;
    }

    /** **fett**, `code` und [Text](URL) auflösen – alles andere wird maskiert. */
    private static function inline(string $text): string
    {
        $pattern = '/(\*\*[^*]+\*\*|`[^`]+`|\[[^\]]+\]\([^)]+\))/';
        $parts = preg_split($pattern, $text, -1, PREG_SPLIT_DELIM_CAPTURE) ?: [];
        $html = '';

        foreach ($parts as $part) {
            if ($part === '') {
                continue;
            }

            if (str_starts_with($part, '**') && str_ends_with($part, '**')) {
                $html .= '<strong>' . e(substr($part, 2, -2)) . '</strong>';
                continue;
            }

            if (str_starts_with($part, '`') && str_ends_with($part, '`')) {
                $html .= '<code>' . e(substr($part, 1, -1)) . '</code>';
                continue;
            }

            if (preg_match('/^\[([^\]]+)\]\(([^)]+)\)$/', $part, $m) === 1) {
                $href = $m[2];
                $external = preg_match('#^https?:#', $href) === 1;
                // Nur harmlose Ziele verlinken, kein javascript:
                if (!$external && !str_starts_with($href, '/') && !str_starts_with($href, 'mailto:') && !str_starts_with($href, 'tel:')) {
                    $html .= e($m[1]);
                    continue;
                }
                $html .= '<a href="' . e($href) . '" class="text-gold"'
                    . ($external ? ' target="_blank" rel="noopener noreferrer"' : '') . '>' . e($m[1]) . '</a>';
                continue;
            }

            $html .= e($part);
        }

        return $html;
    }
}
