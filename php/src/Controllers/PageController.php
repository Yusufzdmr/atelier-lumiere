<?php
declare(strict_types=1);

namespace Atelier\Controllers;

use Atelier\Config;
use Atelier\Content;
use Atelier\I18n;
use Atelier\Images;
use Atelier\Leads;
use Atelier\Security;
use Atelier\Seo;
use Atelier\View;

/**
 * Die öffentlichen Seiten.
 *
 * Aufbau überall gleich: Daten holen, Metadaten bauen, Vorlage rendern.
 * Damit bleibt in den Vorlagen nur Darstellung übrig.
 */
final class PageController
{
    /* -------------------------------- Start -------------------------------- */

    public function home(): void
    {
        View::page('pages/home', $this->base('', [
            'meta' => Seo::forPage('home', [
                'image'  => Images::img('lumiere-hero-main', 1200, 630),
                'jsonLd' => [
                    Seo::localBusiness(),
                    Seo::faq($this->faqPairs(Content::list('faq'))),
                    Seo::breadcrumb([['name' => 'Home', 'path' => '/']]),
                ],
            ]),
            'hero'         => Content::get('hero'),
            'stats'        => Content::get('stats'),
            'services'     => array_slice(Content::list('services'), 0, 4),
            'process'      => Content::list('process'),
            'testimonials' => Content::list('testimonials'),
            'faq'          => Content::list('faq'),
            'cities'       => Content::listed('cities'),
            'venues'       => array_slice(Content::listed('venues'), 0, 6),
            'stories'      => array_slice(Content::list('stories'), 0, 3),
            'packages'     => Content::list('packages'),
        ]));
    }

    /* ------------------------------ Leistungen ------------------------------ */

    public function services(): void
    {
        $services = Content::list('services');

        View::page('pages/services', $this->base('/leistungen', [
            'meta' => Seo::forPage('leistungen', [
                'jsonLd' => [Seo::breadcrumb([
                    ['name' => 'Home', 'path' => '/'],
                    ['name' => I18n::t('services.title'), 'path' => '/leistungen'],
                ])],
            ]),
            'services' => $services,
        ]));
    }

    /* -------------------------------- Preise -------------------------------- */

    public function prices(): void
    {
        $faq = Content::list('faq');

        View::page('pages/prices', $this->base('/preise', [
            'meta' => Seo::forPage('preise', [
                'jsonLd' => [
                    Seo::faq($this->faqPairs($faq)),
                    Seo::breadcrumb([
                        ['name' => 'Home', 'path' => '/'],
                        ['name' => I18n::t('prices.title'), 'path' => '/preise'],
                    ]),
                ],
            ]),
            'packages' => Content::list('packages'),
            'addons'   => Content::list('addons'),
            'faq'      => $faq,
        ]));
    }

    /* ------------------------------- Portfolio ------------------------------ */

    public function portfolio(): void
    {
        View::page('pages/portfolio', $this->base('/portfolio', [
            'meta' => Seo::forPage('portfolio', [
                'jsonLd' => [Seo::breadcrumb([
                    ['name' => 'Home', 'path' => '/'],
                    ['name' => I18n::t('portfolio.title'), 'path' => '/portfolio'],
                ])],
            ]),
            'stories' => Content::list('stories'),
        ]));
    }

    /** @param array<string,string> $params */
    public function story(array $params): void
    {
        $story = Content::story($params['slug'] ?? '');
        if ($story === null) {
            $this->notFound(I18n::locale());
            return;
        }

        $slug = (string) $story['slug'];
        $seeds = (array) ($story['seeds'] ?? []);
        $venueSlug = (string) ($story['venueSlug'] ?? '');
        $citySlug = (string) ($story['citySlug'] ?? '');

        View::page('pages/story', $this->base('/portfolio/' . $slug, [
            'meta' => Seo::forTemplate(
                'story',
                ['couple' => (string) ($story['couple'] ?? ''), 'venue' => I18n::pick($story['venue'] ?? null)],
                '/portfolio/' . $slug,
                [
                    'title'       => (string) ($story['couple'] ?? '') . ' – ' . I18n::pick($story['venue'] ?? null),
                    'description' => mb_substr(I18n::pick($story['intro'] ?? null), 0, 158),
                    'image'       => Images::img((string) ($seeds[0] ?? ''), 1200, 630),
                    'jsonLd'      => [Seo::breadcrumb([
                        ['name' => 'Home', 'path' => '/'],
                        ['name' => I18n::t('portfolio.title'), 'path' => '/portfolio'],
                        ['name' => (string) ($story['couple'] ?? ''), 'path' => '/portfolio/' . $slug],
                    ])],
                ]
            ),
            'story'  => $story,
            'venue'  => $venueSlug !== '' ? Content::venue($venueSlug) : null,
            'city'   => $citySlug !== '' ? Content::city($citySlug) : null,
            'others' => array_slice(array_values(array_filter(
                Content::list('stories'),
                static fn (array $s): bool => ($s['slug'] ?? '') !== $slug
            )), 0, 3),
        ]));
    }

    /* -------------------------------- Regionen ------------------------------- */

    public function regions(): void
    {
        View::page('pages/regions', $this->base('/regionen', [
            'meta' => Seo::forPage('regionen', [
                'jsonLd' => [Seo::breadcrumb([
                    ['name' => 'Home', 'path' => '/'],
                    ['name' => I18n::t('city.allCities'), 'path' => '/regionen'],
                ])],
            ]),
            'cities' => Content::listed('cities'),
        ]));
    }

    /** @param array<string,string> $params */
    public function city(array $params): void
    {
        $city = Content::city($params['stadt'] ?? '');
        if ($city === null) {
            $this->notFound(I18n::locale());
            return;
        }

        $slug = (string) $city['slug'];
        $name = (string) ($city['name'] ?? '');
        $de = I18n::isDe();
        $h1 = $de ? 'Hochzeitsfotograf ' . $name : 'Wedding photographer ' . $name;

        // Nachbarstädte und Locations nach den im Admin gepflegten Verweisen
        $neighbours = [];
        foreach ((array) ($city['neighbours'] ?? []) as $neighbourSlug) {
            $neighbour = Content::city((string) $neighbourSlug);
            if ($neighbour !== null) {
                $neighbours[] = $neighbour;
            }
        }

        $cityVenues = [];
        foreach ((array) ($city['venues'] ?? []) as $venueSlug) {
            $venue = Content::venue((string) $venueSlug);
            if ($venue !== null) {
                $cityVenues[] = $venue;
            }
        }

        View::page('pages/city', $this->base('/hochzeitsfotograf/' . $slug, [
            'meta' => Seo::forTemplate('city', ['name' => $name], '/hochzeitsfotograf/' . $slug, [
                'title'       => $de ? "Hochzeitsfotograf $name – Foto & Video ab 690 €" : "Wedding photographer $name – photo & film from 690 €",
                'description' => mb_substr(I18n::pick($city['lead'] ?? null), 0, 158),
                // Aus der Suche genommen, aber weiter erreichbar: wer den Link
                // hat, sieht die Seite.
                'noindex'     => !Content::shows($city, 'indexed'),
                'jsonLd'      => [
                    Seo::faq($this->faqPairs((array) ($city['faq'] ?? []))),
                    Seo::breadcrumb([
                        ['name' => 'Home', 'path' => '/'],
                        ['name' => I18n::t('city.allCities'), 'path' => '/regionen'],
                        ['name' => $name, 'path' => '/hochzeitsfotograf/' . $slug],
                    ]),
                ],
            ]),
            'city'       => $city,
            'cityVenues' => $cityVenues,
            'neighbours' => $neighbours,
            'cityPosts'  => Content::postsForCity($slug),
            'packages'   => Content::list('packages'),
            'csrf'       => Security::csrf(),
        ]));
    }

    /* ------------------------------- Locations ------------------------------ */

    public function venues(): void
    {
        View::page('pages/venues', $this->base('/hochzeitslocations', [
            'meta' => Seo::forPage('hochzeitslocations', [
                'jsonLd' => [Seo::breadcrumb([
                    ['name' => 'Home', 'path' => '/'],
                    ['name' => I18n::t('venue.all'), 'path' => '/hochzeitslocations'],
                ])],
            ]),
            'venues' => Content::listed('venues'),
        ]));
    }

    /** @param array<string,string> $params */
    public function venue(array $params): void
    {
        $venue = Content::venue($params['slug'] ?? '');
        if ($venue === null) {
            $this->notFound(I18n::locale());
            return;
        }

        $slug = (string) $venue['slug'];
        $name = (string) ($venue['name'] ?? '');
        $de = I18n::isDe();

        View::page('pages/venue', $this->base('/hochzeitslocations/' . $slug, [
            'meta' => Seo::forTemplate(
                'venue',
                ['name' => $name, 'city' => (string) ($venue['city'] ?? '')],
                '/hochzeitslocations/' . $slug,
                [
                    'title'       => $de ? "$name Hochzeitsfotograf – Erfahrung vor Ort" : "$name wedding photographer – we know the place",
                    'description' => mb_substr(I18n::pick($venue['lead'] ?? null), 0, 158),
                    'noindex'     => !Content::shows($venue, 'indexed'),
                    'jsonLd'      => [Seo::breadcrumb([
                        ['name' => 'Home', 'path' => '/'],
                        ['name' => I18n::t('venue.all'), 'path' => '/hochzeitslocations'],
                        ['name' => $name, 'path' => '/hochzeitslocations/' . $slug],
                    ])],
                ]
            ),
            'venue'   => $venue,
            'city'    => Content::city((string) ($venue['citySlug'] ?? '')),
            'related' => array_values(array_filter(
                Content::list('stories'),
                static fn (array $s): bool => ($s['venueSlug'] ?? '') === $slug
            )),
        ]));
    }

    /* -------------------------------- Ratgeber ------------------------------- */

    public function blog(): void
    {
        View::page('pages/blog', $this->base('/ratgeber', [
            'meta' => Seo::forPage('ratgeber', [
                'description' => I18n::t('blog.lead'),
                'jsonLd'      => [Seo::breadcrumb([
                    ['name' => 'Home', 'path' => '/'],
                    ['name' => I18n::t('blog.title'), 'path' => '/ratgeber'],
                ])],
            ]),
            'posts' => Content::posts(),
        ]));
    }

    /** @param array<string,string> $params */
    public function post(array $params): void
    {
        $post = Content::post($params['slug'] ?? '');
        if ($post === null) {
            $this->notFound(I18n::locale());
            return;
        }

        $slug = (string) $post['slug'];
        $uploads = (array) ($post['uploads'] ?? []);
        $cover = (string) ($uploads[0] ?? ($post['seed'] ?? ''));
        $citySlug = (string) ($post['citySlug'] ?? '');
        $venueSlug = (string) ($post['venueSlug'] ?? '');

        View::page('pages/post', $this->base('/ratgeber/' . $slug, [
            'meta' => Seo::forTemplate('post', ['title' => I18n::pick($post['title'] ?? null)], '/ratgeber/' . $slug, [
                'title'       => I18n::pick($post['title'] ?? null),
                'description' => I18n::pick($post['excerpt'] ?? null),
                'image'       => Images::img($cover, 1200, 630),
                'jsonLd'      => [
                    $this->articleLd($post, $slug, $cover),
                    Seo::faq($this->faqPairs((array) ($post['faq'] ?? []))),
                    Seo::breadcrumb([
                        ['name' => 'Home', 'path' => '/'],
                        ['name' => I18n::t('blog.title'), 'path' => '/ratgeber'],
                        ['name' => I18n::pick($post['title'] ?? null), 'path' => '/ratgeber/' . $slug],
                    ]),
                ],
            ]),
            'post'  => $post,
            'city'  => $citySlug !== '' ? Content::city($citySlug) : null,
            'venue' => $venueSlug !== '' ? Content::venue($venueSlug) : null,
            'more'  => array_slice(array_values(array_filter(
                Content::posts(),
                static fn (array $p): bool => ($p['slug'] ?? '') !== $slug
            )), 0, 2),
        ]));
    }

    /* ------------------------------- Über mich ------------------------------ */

    public function about(): void
    {
        $about = Content::get('about');

        View::page('pages/about', $this->base('/ueber-mich', [
            'meta' => Seo::forPage('ueber-mich', [
                'title'       => I18n::isDe()
                    ? 'Über mich – ' . (string) ($about['name'] ?? '') . ', Hochzeitsfotograf Stuttgart'
                    : 'About me – ' . (string) ($about['name'] ?? '') . ', wedding photographer in Stuttgart',
                'description' => I18n::pick($about['lead'] ?? null),
                'jsonLd'      => [Seo::breadcrumb([
                    ['name' => 'Home', 'path' => '/'],
                    ['name' => I18n::t('about.title'), 'path' => '/ueber-mich'],
                ])],
            ]),
            'about' => $about,
            'stats' => Content::get('stats'),
        ]));
    }

    /* -------------------------------- Kontakt ------------------------------- */

    public function contact(): void
    {
        $sent = false;
        $errors = [];
        $values = [];

        if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
            [$sent, $errors, $values] = $this->handleContact();
        }

        View::page('pages/contact', $this->base('/kontakt', [
            'meta' => Seo::forPage('kontakt', [
                'jsonLd' => [Seo::breadcrumb([
                    ['name' => 'Home', 'path' => '/'],
                    ['name' => I18n::t('contact.title'), 'path' => '/kontakt'],
                ])],
            ]),
            'contact' => Content::get('contact'),
            'csrf'    => Security::csrf(),
            'sent'    => $sent,
            'errors'  => $errors,
            'values'  => $values,
        ]));
    }

    /**
     * Anfrage prüfen und speichern.
     *
     * @return array{0:bool,1:array<string,string>,2:array<string,string>}
     */
    private function handleContact(): array
    {
        $values = [
            'name'     => Security::clean($_POST['name'] ?? '', 80),
            'email'    => Security::clean($_POST['email'] ?? '', 120),
            'phone'    => Security::clean($_POST['phone'] ?? '', 40),
            'date'     => Security::clean($_POST['date'] ?? '', 20),
            'location' => Security::clean($_POST['location'] ?? '', 120),
            'guests'   => Security::clean($_POST['guests'] ?? '', 10),
            'service'  => Security::clean($_POST['service'] ?? '', 20),
            'message'  => Security::clean($_POST['message'] ?? '', 2000),
        ];

        // Der Honigtopf ist für Menschen unsichtbar: ist er gefüllt, war es ein Roboter.
        if (Security::clean($_POST['website'] ?? '', 50) !== '') {
            return [true, [], []];
        }

        if (!Security::checkCsrf($_POST['csrf'] ?? null)) {
            return [false, ['csrf' => '1'], $values];
        }

        if (Security::throttle('contact', 5, 600)) {
            return [false, ['throttle' => '1'], $values];
        }

        if ($values['name'] === '' || $values['message'] === '' || !filter_var($values['email'], FILTER_VALIDATE_EMAIL)) {
            return [false, ['fields' => '1'], $values];
        }

        Leads::store($values + ['locale' => I18n::locale()]);
        return [true, [], []];
    }

    /* ------------------------------ Rechtstexte ----------------------------- */

    public function legal(string $key): void
    {
        $legal = Content::get('legal');
        $page = $legal[$key] ?? null;

        if (!is_array($page)) {
            $this->notFound(I18n::locale());
            return;
        }

        $descriptions = [
            'impressum'   => [
                'de' => 'Impressum gemäß § 5 DDG.',
                'en' => 'Legal notice in accordance with § 5 DDG.',
            ],
            'datenschutz' => [
                'de' => 'Informationen zur Verarbeitung personenbezogener Daten nach Art. 13 DSGVO.',
                'en' => 'Information on the processing of personal data under Art. 13 GDPR.',
            ],
            'agb'         => [
                'de' => 'Allgemeine Geschäftsbedingungen für Foto- und Filmaufträge sowie digitale Produkte.',
                'en' => 'Terms and conditions for photography and film commissions and for digital products.',
            ],
        ];

        View::page('pages/legal', $this->base('/' . $key, [
            'meta' => Seo::forPage($key, [
                'title'       => I18n::pick($page['title'] ?? null) ?: ucfirst($key),
                'description' => I18n::pick($descriptions[$key] ?? null),
            ]),
            'page' => $page,
        ]));
    }

    /* -------------------------------- Fehler -------------------------------- */

    public function notFound(string $locale): void
    {
        I18n::set(I18n::isLocale($locale) ? $locale : I18n::DEFAULT);
        http_response_code(404);

        View::page('pages/not-found', $this->base('', [
            'meta' => [
                'title'       => I18n::isDe() ? 'Seite nicht gefunden' : 'Page not found',
                'description' => '',
                'noindex'     => true,
                'canonical'   => Config::url() . I18n::path(''),
            ],
        ]));
    }

    /* -------------------------------- Helfer -------------------------------- */

    /**
     * Werte, die jede Vorlage braucht.
     *
     * @param array<string,mixed> $data
     * @return array<string,mixed>
     */
    private function base(string $path, array $data): array
    {
        return array_merge([
            'locale' => I18n::locale(),
            'path'   => I18n::path($path),
        ], $data);
    }

    /**
     * @param array<int,array<string,mixed>> $items
     * @return list<array{q:string,a:string}>
     */
    private function faqPairs(array $items): array
    {
        $pairs = [];
        foreach ($items as $item) {
            $q = I18n::pick($item['q'] ?? null);
            $a = I18n::pick($item['a'] ?? null);
            if ($q !== '' && $a !== '') {
                $pairs[] = ['q' => $q, 'a' => $a];
            }
        }
        return $pairs;
    }

    /**
     * @param array<string,mixed> $post
     * @return array<string,mixed>
     */
    private function articleLd(array $post, string $slug, string $cover): array
    {
        return [
            '@context'      => 'https://schema.org',
            '@type'         => 'BlogPosting',
            'headline'      => I18n::pick($post['title'] ?? null),
            'description'   => I18n::pick($post['excerpt'] ?? null),
            'image'         => Images::img($cover, 1200, 630),
            'datePublished' => (string) ($post['date'] ?? ''),
            'dateModified'  => (string) ($post['date'] ?? ''),
            'inLanguage'    => I18n::locale(),
            'author'        => ['@type' => 'Person', 'name' => 'Julian Roth'],
            'publisher'     => ['@id' => Config::url() . '/#business'],
            'mainEntityOfPage' => Config::url() . I18n::path('/ratgeber/' . $slug),
        ];
    }
}
