<?php
declare(strict_types=1);

namespace Atelier\Controllers;

use Atelier\Config;
use Atelier\Content;
use Atelier\I18n;
use Atelier\Marketing;

/**
 * sitemap.xml und robots.txt.
 *
 * Beide lesen aus dem Adminbereich: eine neu angelegte Stadt, Location oder
 * ein neuer Beitrag steht sofort drin, ohne dass jemand eine Liste pflegt.
 * Seiten, die im Admin auf „nicht indexieren“ stehen, fehlen hier ebenso.
 */
final class SitemapController
{
    /** Feste Seiten mit Priorität und Änderungsfrequenz. */
    private const STATIC_PAGES = [
        ['path' => '', 'key' => 'home', 'priority' => '1.0', 'freq' => 'weekly'],
        ['path' => '/leistungen', 'key' => 'leistungen', 'priority' => '0.9', 'freq' => 'monthly'],
        ['path' => '/preise', 'key' => 'preise', 'priority' => '0.9', 'freq' => 'monthly'],
        ['path' => '/portfolio', 'key' => 'portfolio', 'priority' => '0.8', 'freq' => 'monthly'],
        ['path' => '/hochzeitslocations', 'key' => 'hochzeitslocations', 'priority' => '0.9', 'freq' => 'monthly'],
        ['path' => '/regionen', 'key' => 'regionen', 'priority' => '0.8', 'freq' => 'monthly'],
        ['path' => '/ratgeber', 'key' => 'ratgeber', 'priority' => '0.8', 'freq' => 'weekly'],
        ['path' => '/ueber-mich', 'key' => 'ueber-mich', 'priority' => '0.6', 'freq' => 'yearly'],
        ['path' => '/kontakt', 'key' => 'kontakt', 'priority' => '0.8', 'freq' => 'yearly'],
        ['path' => '/einladung', 'key' => 'einladung', 'priority' => '0.7', 'freq' => 'monthly'],
        ['path' => '/designs', 'key' => 'designs', 'priority' => '0.7', 'freq' => 'monthly'],
        ['path' => '/impressum', 'key' => 'impressum', 'priority' => '0.2', 'freq' => 'yearly'],
        ['path' => '/datenschutz', 'key' => 'datenschutz', 'priority' => '0.2', 'freq' => 'yearly'],
        ['path' => '/agb', 'key' => 'agb', 'priority' => '0.2', 'freq' => 'yearly'],
    ];

    public function xml(): void
    {
        $marketing = Content::get('marketing');
        $pages = $marketing['pages'] ?? [];
        $url = Config::url();
        $today = date('Y-m-d');

        $entries = [];
        foreach (self::STATIC_PAGES as $page) {
            if (!empty($pages[$page['key']]['noindex'])) {
                continue;
            }
            $entries[] = $page;
        }

        foreach (Content::list('cities') as $city) {
            $entries[] = ['path' => '/hochzeitsfotograf/' . (string) ($city['slug'] ?? ''), 'priority' => '0.95', 'freq' => 'monthly'];
        }
        foreach (Content::list('venues') as $venue) {
            $entries[] = ['path' => '/hochzeitslocations/' . (string) ($venue['slug'] ?? ''), 'priority' => '0.9', 'freq' => 'monthly'];
        }
        foreach (Content::posts() as $post) {
            $entries[] = ['path' => '/ratgeber/' . (string) ($post['slug'] ?? ''), 'priority' => '0.7', 'freq' => 'monthly'];
        }
        foreach (Content::list('stories') as $story) {
            $entries[] = ['path' => '/portfolio/' . (string) ($story['slug'] ?? ''), 'priority' => '0.6', 'freq' => 'yearly'];
        }

        header('Content-Type: application/xml; charset=UTF-8');

        $out = '<?xml version="1.0" encoding="UTF-8"?>' . "\n"
            . '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9" xmlns:xhtml="http://www.w3.org/1999/xhtml">' . "\n";

        foreach ($entries as $entry) {
            foreach (I18n::LOCALES as $locale) {
                $loc = $url . '/' . $locale . $entry['path'];
                $out .= "  <url>\n";
                $out .= '    <loc>' . htmlspecialchars($loc, ENT_XML1) . "</loc>\n";
                $out .= '    <lastmod>' . $today . "</lastmod>\n";
                $out .= '    <changefreq>' . $entry['freq'] . "</changefreq>\n";
                $out .= '    <priority>' . $entry['priority'] . "</priority>\n";

                foreach (I18n::LOCALES as $alt) {
                    $out .= '    <xhtml:link rel="alternate" hreflang="' . ($alt === 'en' ? 'en' : 'de-DE')
                        . '" href="' . htmlspecialchars($url . '/' . $alt . $entry['path'], ENT_XML1) . '"/>' . "\n";
                }
                $out .= '    <xhtml:link rel="alternate" hreflang="x-default" href="'
                    . htmlspecialchars($url . '/de' . $entry['path'], ENT_XML1) . '"/>' . "\n";
                $out .= "  </url>\n";
            }
        }

        echo $out . '</urlset>';
    }

    public function robots(): void
    {
        header('Content-Type: text/plain; charset=UTF-8');

        // Testadresse: alles sperren. Eine Vorschau unter einer anderen Adresse
        // ist derselbe Inhalt zweimal – und wenn Google zuerst die Testadresse
        // findet, rankt spaeter die falsche.
        if (\Atelier\Config::get('noindex', false)) {
            echo "User-agent: *\nDisallow: /\n";
            return;
        }

        // Kundenbereich, Einladungen und Verwaltung gehören nicht in den Index.
        echo implode("\n", [
            'User-agent: *',
            'Allow: /',
            'Disallow: /de/admin',
            'Disallow: /tr/admin',
            'Disallow: /de/galerie/',
            'Disallow: /tr/galerie/',
            'Disallow: /de/einladung/',
            'Disallow: /tr/einladung/',
            '',
            'Sitemap: ' . Config::url() . '/sitemap.xml',
            '',
        ]);
    }
}
