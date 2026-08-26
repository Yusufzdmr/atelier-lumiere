<?php
declare(strict_types=1);

/**
 * Einziger Einstiegspunkt. Alles läuft über .htaccess hierher.
 */

require __DIR__ . '/../src/bootstrap.php';

use Atelier\Http;
use Atelier\Controllers\AdminController;
use Atelier\Controllers\ContentAdminController;
use Atelier\Controllers\CustomerAdminController;
use Atelier\Controllers\DesignAdminController;
use Atelier\Controllers\DesignController;
use Atelier\Controllers\GalleryController;
use Atelier\Controllers\InviteAdminController;
use Atelier\Controllers\InviteController;
use Atelier\Controllers\InviteV2Controller;
use Atelier\Controllers\ListAdminController;
use Atelier\Controllers\PageController;
use Atelier\Controllers\SelectionController;
use Atelier\Controllers\SitemapController;
use Atelier\Controllers\TextAdminController;
use Atelier\I18n;
use Atelier\Router;

// Schutzkopfzeilen, bevor irgendetwas ausgegeben wird.
Http::harden();

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

/**
 * Wie oben, aber für den Adminbereich: der spricht Deutsch und Türkisch,
 * die Website Deutsch und Englisch. /tr/admin gibt es deshalb weiter,
 * /tr/preise nicht mehr.
 *
 * @param callable(array<string,string>):void $handler
 */
$admin_ = static function (callable $handler) use ($page): callable {
    return static function (array $params) use ($handler, $page): void {
        $locale = $params['locale'] ?? '';
        if (!I18n::isAdminLocale($locale)) {
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

// Auswahl fuer den Albumhersteller: geheimer, befristeter Link ohne Login.
// "zip" steht vor dem allgemeinen Muster, sonst wird es als Token gelesen.
$router->get('/{locale}/auswahl/{token}/zip', $page_(static fn (array $p) => (new SelectionController())->zip($p)));
$router->get('/{locale}/auswahl/{token}', $page_(static fn (array $p) => (new SelectionController())->show($p)));

// Das Schaufenster steht bewusst NICHT unter /einladung/: dort greift das
// Muster {slug}, und ein Paar, das seine Einladung "designs" nennt, haette
// entweder die eigene Karte oder diese Seite unerreichbar gemacht.
$router->get('/{locale}/designs', $page_(static fn (array $p) => (new InviteController())->designs()));
$router->get('/{locale}/designs/{thema}', $page_(static fn (array $p) => (new InviteController())->designPreview($p)));

// Zweite Fassung der Einladung – laeuft neben der ersten, bis verglichen ist.
$router->get('/{locale}/v2/designs', $page_(static fn (array $p) => (new DesignController())->index()));
$router->get('/{locale}/v2/designs/{slug}', $page_(static fn (array $p) => (new DesignController())->preview($p)));

// Der Assistent der zweiten Fassung. Die feste Adresse steht vor dem Muster
// {slug} - sonst liest der Router "einladung" als Namen einer Einladung.
$router->any('/{locale}/v2/einladung', $page_(static fn (array $p) => (new InviteV2Controller())->wizard()));
// Das Kartenbild der Beispieleinladung - fuer Schaufenster und Vorschau, die
// keine gespeicherte Einladung haben, an der eine Adresse haengt.
$router->get('/{locale}/v2/karte-beispiel.png', $page_(static fn (array $p) => (new InviteV2Controller())->demoMap()));

// Das Kartenbild eines Ortes. VOR den Mustern mit {key}: "karte.png" waere
// fuer ein {key}-Muster ein Schluessel wie jeder andere, und dann bekaeme die
// Antwortenliste die Anfrage nach dem Bild.
$router->get('/{locale}/v2/einladung/{slug}/karte.png', $page_(static fn (array $p) => (new InviteV2Controller())->map($p)));

// Vor der Einladung selbst, wie /einladung/{slug}/verwalten im alten Motor.
// Beide Muster sind verankert und {slug} matcht keinen Schraegstrich, also
// koennen sie einander nicht fangen - die Reihenfolge steht hier fuer den
// Leser, nicht fuer den Router.
//
// Der Bearbeiten-Bildschirm, unter demselben Schluessel wie die Antworten.
// Vor dem kuerzeren Muster, weil sie zusammengehoeren - noetig waere die
// Reihenfolge nicht: beide Muster sind verankert und {key} matcht keinen
// Schraegstrich, also koennen sie einander nicht fangen.
//
// any und nicht get: dieser Bildschirm nimmt seine eigene Aenderung entgegen.
$router->any('/{locale}/v2/einladung/{slug}/{key}/bearbeiten', $page_(static fn (array $p) => (new InviteV2Controller())->edit($p)));
// get und nicht any: die Leseansicht schreibt nichts.
$router->get('/{locale}/v2/einladung/{slug}/{key}', $page_(static fn (array $p) => (new InviteV2Controller())->replies($p)));
// any und nicht get: die Einladung nimmt ihre eigene Antwort entgegen
// (DesignSections druckt ein Formular ohne action). Ein eigener Endpunkt
// muesste erst wieder herausfinden, zu welcher Einladung er gehoert.
$router->any('/{locale}/v2/einladung/{slug}', $page_(static fn (array $p) => (new InviteV2Controller())->show($p)));

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
$router->get('/{locale}/galerie/beispiel', $page_(static fn (array $p) => (new GalleryController())->demo()));
$router->any('/{locale}/galerie/{code}', $page_(static fn (array $p) => (new GalleryController())->show($p)));

$router->any('/{locale}/admin', $admin_(static fn (array $p) => (new AdminController($p['locale']))->overview()));
$router->get('/{locale}/admin/karte', $admin_(static fn (array $p) => (new AdminController($p['locale']))->map()));
$router->get('/{locale}/admin/abmelden', $admin_(static fn (array $p) => (new AdminController($p['locale']))->logout()));
$router->any('/{locale}/admin/inhalte', $admin_(static fn (array $p) => (new ContentAdminController($p['locale']))->texts()));
$router->any('/{locale}/admin/bilder', $admin_(static fn (array $p) => (new AdminController($p['locale']))->images()));
$router->any('/{locale}/admin/texte', $admin_(static fn (array $p) => (new TextAdminController($p['locale']))->index()));
$router->any('/{locale}/admin/pakete', $admin_(static fn (array $p) => (new ContentAdminController($p['locale']))->packages()));
$router->any('/{locale}/admin/ueber-mich', $admin_(static fn (array $p) => (new ContentAdminController($p['locale']))->about()));
$router->any('/{locale}/admin/rechtliches', $admin_(static fn (array $p) => (new ContentAdminController($p['locale']))->legal()));
$router->any('/{locale}/admin/seo', $admin_(static fn (array $p) => (new ContentAdminController($p['locale']))->seo()));
$router->any('/{locale}/admin/leistungen', $admin_(static fn (array $p) => (new ListAdminController($p['locale']))->services()));
$router->any('/{locale}/admin/staedte', $admin_(static fn (array $p) => (new ListAdminController($p['locale']))->cities()));
$router->any('/{locale}/admin/locations', $admin_(static fn (array $p) => (new ListAdminController($p['locale']))->venues()));
$router->any('/{locale}/admin/portfolio', $admin_(static fn (array $p) => (new ListAdminController($p['locale']))->stories()));
$router->any('/{locale}/admin/ratgeber', $admin_(static fn (array $p) => (new ListAdminController($p['locale']))->posts()));
$router->any('/{locale}/admin/kunden', $admin_(static fn (array $p) => (new CustomerAdminController($p['locale']))->index()));
$router->any('/{locale}/admin/kunden/{code}', $admin_(static fn (array $p) => (new CustomerAdminController($p['locale']))->show($p)));
$router->any('/{locale}/admin/einladungen', $admin_(static fn (array $p) => (new InviteAdminController($p['locale']))->index()));
$router->any('/{locale}/admin/themen', $admin_(static fn (array $p) => (new AdminController($p['locale']))->themes()));
$router->any('/{locale}/admin/designs', $admin_(static fn (array $p) => (new DesignAdminController())->index($p['locale'])));
$router->any('/{locale}/admin/designs/{slug}', $admin_(static fn (array $p) => (new DesignAdminController())->edit($p)));
$router->any('/{locale}/admin/systemcheck', $admin_(static fn (array $p) => (new AdminController($p['locale']))->preflight()));
$router->any('/{locale}/admin/integrationen', $admin_(static fn (array $p) => (new AdminController($p['locale']))->integrations()));

$router->get('/{locale}/impressum', $page_(static fn (array $p) => $page->legal('impressum')));
$router->get('/{locale}/datenschutz', $page_(static fn (array $p) => $page->legal('datenschutz')));
$router->get('/{locale}/agb', $page_(static fn (array $p) => $page->legal('agb')));

$router->notFound(static function () use ($page): void {
    $page->notFound(I18n::locale());
});

$router->dispatch($_SERVER['REQUEST_METHOD'] ?? 'GET', $_SERVER['REQUEST_URI'] ?? '/');
