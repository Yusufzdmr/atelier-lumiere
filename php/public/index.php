<?php
declare(strict_types=1);

/**
 * Einziger Einstiegspunkt. Alles läuft über .htaccess hierher.
 */

require __DIR__ . '/../src/bootstrap.php';

use Atelier\Controllers\AdminController;
use Atelier\Controllers\ContentAdminController;
use Atelier\Controllers\CustomerAdminController;
use Atelier\Controllers\GalleryController;
use Atelier\Controllers\InviteAdminController;
use Atelier\Controllers\InviteController;
use Atelier\Controllers\ListAdminController;
use Atelier\Controllers\PageController;
use Atelier\Controllers\SitemapController;
use Atelier\I18n;
use Atelier\Router;

$router = new Router();
$page = new PageController();

/* --------------------------- Ohne Sprachpräfix --------------------------- */

// "/" führt auf die deutsche Fassung – wie bisher in next.config.ts
$router->get('/', static function (): void {
    header('Location: /de', true, 307);
    exit;
});

$router->get('/sitemap.xml', static fn () => (new SitemapController())->xml());
$router->get('/robots.txt', static fn () => (new SitemapController())->robots());

/* ------------------------------ Mit Sprache ------------------------------ */

/**
 * Prüft das Sprachpräfix und setzt die Sprache, bevor der Handler läuft.
 *
 * @param callable(array<string,string>):void $handler
 */
$page_ = static function (callable $handler) use ($page): callable {
    return static function (array $params) use ($handler, $page): void {
        $locale = $params['locale'] ?? '';
        if (!I18n::isLocale($locale)) {
            $page->notFound(I18n::DEFAULT);
            return;
        }
        I18n::set($locale);
        $handler($params);
    };
};

$router->get('/{locale}', $page_(static fn (array $p) => $page->home()));
$router->get('/{locale}/leistungen', $page_(static fn (array $p) => $page->services()));
$router->get('/{locale}/preise', $page_(static fn (array $p) => $page->prices()));
$router->get('/{locale}/portfolio', $page_(static fn (array $p) => $page->portfolio()));
$router->get('/{locale}/portfolio/{slug}', $page_(static fn (array $p) => $page->story($p)));
$router->get('/{locale}/regionen', $page_(static fn (array $p) => $page->regions()));
$router->get('/{locale}/hochzeitsfotograf/{stadt}', $page_(static fn (array $p) => $page->city($p)));
$router->get('/{locale}/hochzeitslocations', $page_(static fn (array $p) => $page->venues()));
$router->get('/{locale}/hochzeitslocations/{slug}', $page_(static fn (array $p) => $page->venue($p)));
$router->get('/{locale}/ratgeber', $page_(static fn (array $p) => $page->blog()));
$router->get('/{locale}/ratgeber/{slug}', $page_(static fn (array $p) => $page->post($p)));
$router->get('/{locale}/ueber-mich', $page_(static fn (array $p) => $page->about()));
$router->any('/{locale}/kontakt', $page_(static fn (array $p) => $page->contact()));

$router->any('/{locale}/einladung', $page_(static fn (array $p) => (new InviteController())->wizard()));
$router->get('/{locale}/einladung/{slug}/zahlung', $page_(static fn (array $p) => (new InviteController())->payment($p)));
$router->any('/{locale}/einladung/{slug}/verwalten', $page_(static fn (array $p) => (new InviteController())->manage($p)));
$router->any('/{locale}/einladung/{slug}', $page_(static fn (array $p) => (new InviteController())->show($p)));
// Persoenlich adressierte Fassung. Steht bewusst NACH "zahlung" und
// "verwalten": das Muster wuerde sie sonst schlucken.
$router->any('/{locale}/einladung/{slug}/{gast}', $page_(static fn (array $p) => (new InviteController())->show($p)));
$router->post('/api/kupon', static fn (array $p) => (new InviteController())->checkCoupon());

$router->any('/{locale}/galerie', $page_(static fn (array $p) => (new GalleryController())->index()));
$router->get('/{locale}/galerie/abmelden', $page_(static fn (array $p) => (new GalleryController())->logout()));
$router->post('/{locale}/galerie/{code}/auswahl', $page_(static fn (array $p) => (new GalleryController())->saveSelection()));
$router->any('/{locale}/galerie/{code}', $page_(static fn (array $p) => (new GalleryController())->show($p)));

$router->any('/{locale}/admin', $page_(static fn (array $p) => (new AdminController($p['locale']))->overview()));
$router->get('/{locale}/admin/abmelden', $page_(static fn (array $p) => (new AdminController($p['locale']))->logout()));
$router->any('/{locale}/admin/inhalte', $page_(static fn (array $p) => (new ContentAdminController($p['locale']))->texts()));
$router->any('/{locale}/admin/pakete', $page_(static fn (array $p) => (new ContentAdminController($p['locale']))->packages()));
$router->any('/{locale}/admin/ueber-mich', $page_(static fn (array $p) => (new ContentAdminController($p['locale']))->about()));
$router->any('/{locale}/admin/rechtliches', $page_(static fn (array $p) => (new ContentAdminController($p['locale']))->legal()));
$router->any('/{locale}/admin/seo', $page_(static fn (array $p) => (new ContentAdminController($p['locale']))->seo()));
$router->any('/{locale}/admin/leistungen', $page_(static fn (array $p) => (new ListAdminController($p['locale']))->services()));
$router->any('/{locale}/admin/staedte', $page_(static fn (array $p) => (new ListAdminController($p['locale']))->cities()));
$router->any('/{locale}/admin/locations', $page_(static fn (array $p) => (new ListAdminController($p['locale']))->venues()));
$router->any('/{locale}/admin/portfolio', $page_(static fn (array $p) => (new ListAdminController($p['locale']))->stories()));
$router->any('/{locale}/admin/ratgeber', $page_(static fn (array $p) => (new ListAdminController($p['locale']))->posts()));
$router->any('/{locale}/admin/kunden', $page_(static fn (array $p) => (new CustomerAdminController($p['locale']))->index()));
$router->any('/{locale}/admin/kunden/{code}', $page_(static fn (array $p) => (new CustomerAdminController($p['locale']))->show($p)));
$router->any('/{locale}/admin/einladungen', $page_(static fn (array $p) => (new InviteAdminController($p['locale']))->index()));
$router->any('/{locale}/admin/themen', $page_(static fn (array $p) => (new AdminController($p['locale']))->themes()));
$router->any('/{locale}/admin/integrationen', $page_(static fn (array $p) => (new AdminController($p['locale']))->integrations()));

$router->get('/{locale}/impressum', $page_(static fn (array $p) => $page->legal('impressum')));
$router->get('/{locale}/datenschutz', $page_(static fn (array $p) => $page->legal('datenschutz')));
$router->get('/{locale}/agb', $page_(static fn (array $p) => $page->legal('agb')));

$router->notFound(static function () use ($page): void {
    $page->notFound(I18n::locale());
});

$router->dispatch($_SERVER['REQUEST_METHOD'] ?? 'GET', $_SERVER['REQUEST_URI'] ?? '/');
