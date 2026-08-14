<?php
declare(strict_types=1);

namespace Atelier\Controllers;

use Atelier\Config;
use Atelier\Content;
use Atelier\I18n;
use Atelier\Images;
use Atelier\Seo;
use Atelier\View;

/**
 * Die öffentlichen Seiten. Ein Controller pro Bereich wäre bei dieser Größe
 * mehr Struktur als Nutzen – hier sammelt sich alles, was ein Besucher sieht.
 */
final class PageController
{
    public function home(): void
    {
        $locale = I18n::locale();

        View::page('pages/home', [
            'locale' => $locale,
            'path'   => I18n::path(''),
            'meta'   => Seo::forPage('home', [
                'image' => Images::img('lumiere-hero-main', 1200, 630),
                'jsonLd' => [Seo::localBusiness()],
            ]),
            'hero'         => Content::get('hero'),
            'stats'        => Content::get('stats'),
            'services'     => array_slice(Content::list('services'), 0, 3),
            'process'      => Content::list('process'),
            'testimonials' => Content::list('testimonials'),
            'faq'          => Content::list('faq'),
            'cities'       => Content::list('cities'),
            'venues'       => array_slice(Content::list('venues'), 0, 3),
            'stories'      => array_slice(Content::list('stories'), 0, 3),
            'packages'     => Content::get('packages'),
        ]);
    }

    public function notFound(string $locale): void
    {
        I18n::set(I18n::isLocale($locale) ? $locale : I18n::DEFAULT);
        http_response_code(404);

        View::page('pages/not-found', [
            'locale' => I18n::locale(),
            'path'   => I18n::path(''),
            'meta'   => [
                'title'       => I18n::isDe() ? 'Seite nicht gefunden' : 'Sayfa bulunamadı',
                'description' => '',
                'noindex'     => true,
                'canonical'   => Config::url() . I18n::path(''),
            ],
        ]);
    }
}
