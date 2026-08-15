<?php
declare(strict_types=1);

namespace Atelier;

/**
 * Titel, Beschreibungen und strukturierte Daten.
 *
 * Die Texte kommen aus dem Adminbereich (content.marketing) – genau wie in
 * der Next.js-Fassung. Ist dort nichts gepflegt, greift der Vorgabewert, den
 * die Seite selbst mitbringt. So bleibt keine Seite ohne Titel.
 */
final class Seo
{
    /** Google zeigt etwa so viel an. */
    public const TITLE_MAX = 60;
    public const DESC_MAX = 160;

    /**
     * @param array<string,mixed> $fallback title, description, image, jsonLd, noindex
     * @return array<string,mixed>
     */
    public static function forPage(string $key, array $fallback = []): array
    {
        $locale = I18n::locale();
        $marketing = Content::get('marketing');
        $entry = $marketing['pages'][$key] ?? [];

        $title = I18n::pick($entry['title'] ?? null, $locale);
        $description = I18n::pick($entry['description'] ?? null, $locale);
        $image = (string) ($entry['image'] ?? '');
        $default = (string) ($marketing['defaultImage'] ?? '');

        // array_merge, damit Schluessel durchkommen, die der Adminbereich nicht
        // verwaltet: 'scripts', 'ogType'. Eine feste Liste liess sie still
        // verschwinden – der Einladungsassistent kam ohne sein invite.js auf
        // die Seite, und damit tat dort kein einziger Knopf mehr etwas.
        return array_merge($fallback, [
            'title'       => $title !== '' ? $title : (string) ($fallback['title'] ?? 'Atelier Lumière'),
            'description' => $description !== '' ? $description : (string) ($fallback['description'] ?? ''),
            'image'       => $image !== '' ? $image : (string) ($fallback['image'] ?? $default),
            'noindex'     => (bool) ($entry['noindex'] ?? $fallback['noindex'] ?? false),
            'canonical'   => (string) ($fallback['canonical'] ?? Config::url() . I18n::path(self::pathFor($key))),
            'jsonLd'      => $fallback['jsonLd'] ?? [],
        ]);
    }

    /**
     * Titel der vielfach vorhandenen Seiten (Stadt, Location, Beitrag,
     * Reportage) aus der Vorlage im Adminbereich.
     *
     * @param array<string,string> $vars
     * @param array<string,mixed> $fallback
     * @return array<string,mixed>
     */
    public static function forTemplate(string $kind, array $vars, string $path, array $fallback = []): array
    {
        $marketing = Content::get('marketing');
        $template = I18n::pick($marketing['templates'][$kind] ?? null);

        $title = $template !== ''
            ? preg_replace_callback(
                '/\{(\w+)\}/',
                static fn (array $m): string => $vars[$m[1]] ?? $m[0],
                $template
            )
            : (string) ($fallback['title'] ?? '');

        // Wie oben: mitgegebene Zusatzschluessel durchreichen.
        return array_merge($fallback, [
            'title'       => (string) $title,
            'description' => (string) ($fallback['description'] ?? ''),
            'image'       => (string) ($fallback['image'] ?? ($marketing['defaultImage'] ?? '')),
            'noindex'     => (bool) ($fallback['noindex'] ?? false),
            'canonical'   => Config::url() . I18n::path($path),
            'jsonLd'      => $fallback['jsonLd'] ?? [],
        ]);
    }

    /** Pfad einer festen Seite. */
    public static function pathFor(string $key): string
    {
        return match ($key) {
            'home' => '',
            'ueber-mich' => '/ueber-mich',
            default => '/' . $key,
        };
    }

    /* --------------------------- Strukturierte Daten --------------------------- */

    /** @return array<string,mixed> */
    public static function localBusiness(): array
    {
        $c = Content::get('contact');
        $stats = Content::get('stats');
        $url = Config::url();

        $rating = str_replace(',', '.', (string) ($stats['rating'] ?? '4.9'));
        $rating = preg_replace('/[^\d.]/', '', $rating) ?: '4.9';

        return [
            '@context'    => 'https://schema.org',
            '@type'       => ['LocalBusiness', 'Photograph'],
            '@id'         => $url . '/#business',
            'name'        => 'Atelier Lumière',
            'legalName'   => 'Atelier Lumière Hochzeitsfotografie',
            'url'         => $url . I18n::path(''),
            'telephone'   => $c['phone'] ?? '',
            'email'       => $c['email'] ?? '',
            'image'       => Images::img('lumiere-hero-main', 1200, 630),
            'priceRange'  => '€€€',
            'address'     => [
                '@type'           => 'PostalAddress',
                'streetAddress'   => $c['street'] ?? '',
                'postalCode'      => $c['zip'] ?? '',
                'addressLocality' => $c['city'] ?? '',
                'addressCountry'  => 'DE',
            ],
            'areaServed'    => array_map(
                static fn (array $city): string => (string) ($city['name'] ?? ''),
                Content::list('cities')
            ),
            'knowsLanguage' => ['de', 'tr', 'en'],
            'aggregateRating' => [
                '@type'       => 'AggregateRating',
                'ratingValue' => $rating,
                'reviewCount' => '87',
            ],
        ];
    }

    /**
     * @param list<array{name:string,path:string}> $items
     * @return array<string,mixed>
     */
    public static function breadcrumb(array $items): array
    {
        $list = [];
        foreach ($items as $i => $item) {
            $list[] = [
                '@type'    => 'ListItem',
                'position' => $i + 1,
                'name'     => $item['name'],
                'item'     => Config::url() . I18n::path($item['path'] === '/' ? '' : $item['path']),
            ];
        }

        return ['@context' => 'https://schema.org', '@type' => 'BreadcrumbList', 'itemListElement' => $list];
    }

    /**
     * @param list<array{q:string,a:string}> $items
     * @return array<string,mixed>
     */
    public static function faq(array $items): array
    {
        return [
            '@context'   => 'https://schema.org',
            '@type'      => 'FAQPage',
            'mainEntity' => array_map(static fn (array $f): array => [
                '@type'          => 'Question',
                'name'           => $f['q'],
                'acceptedAnswer' => ['@type' => 'Answer', 'text' => $f['a']],
            ], $items),
        ];
    }
}
