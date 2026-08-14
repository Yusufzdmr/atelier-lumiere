<?php
declare(strict_types=1);

/**
 * Einziger Einstiegspunkt. Alles läuft über .htaccess hierher.
 */

require __DIR__ . '/../src/bootstrap.php';

use Atelier\Config;
use Atelier\Controllers\PageController;
use Atelier\I18n;
use Atelier\Router;

$router = new Router();

/* -------------------------- Sprachwahl -------------------------- */

// "/" leitet auf die deutsche Fassung – wie bisher in next.config.ts
$router->get('/', static function (): void {
    header('Location: /de', true, 307);
    exit;
});

/**
 * Setzt die Sprache und bricht mit 404 ab, wenn das Präfix keine ist.
 * @param array<string,string> $params
 */
$withLocale = static function (callable $handler): callable {
    return static function (array $params) use ($handler): void {
        $locale = $params['locale'] ?? '';
        if (!I18n::isLocale($locale)) {
            http_response_code(404);
            (new PageController())->notFound($locale);
            return;
        }
        I18n::set($locale);
        $handler($params);
    };
};

$page = new PageController();

/* ---------------------------- Seiten ---------------------------- */

$router->get('/{locale}', $withLocale(static fn (array $p) => $page->home()));

$router->notFound(static function (): void {
    (new PageController())->notFound(I18n::DEFAULT);
});

$router->dispatch($_SERVER['REQUEST_METHOD'] ?? 'GET', $_SERVER['REQUEST_URI'] ?? '/');
