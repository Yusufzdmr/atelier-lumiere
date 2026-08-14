<?php
declare(strict_types=1);

namespace Atelier;

/**
 * Gemeinsamer Start für Webseite, Adminbereich und Kommandozeilenskripte.
 *
 * Autoloader von Hand: Composer ist auf dem Webspace nicht vorausgesetzt, und
 * bei einem gutem Dutzend Klassen ist eine Zeile Code weniger Aufwand als eine
 * Abhängigkeit, die jemand nachziehen muss.
 */

spl_autoload_register(static function (string $class): void {
    $prefix = 'Atelier\\';
    if (!str_starts_with($class, $prefix)) {
        return;
    }
    $file = __DIR__ . '/' . str_replace('\\', '/', substr($class, strlen($prefix))) . '.php';
    if (is_file($file)) {
        require $file;
    }
});

require __DIR__ . '/View.php'; // enthält die Kurzhelfer e(), paragraphs(), lines()

mb_internal_encoding('UTF-8');
date_default_timezone_set('Europe/Berlin');

Config::load(dirname(__DIR__) . '/config.php');

if (Config::isDev()) {
    error_reporting(E_ALL);
    ini_set('display_errors', '1');
} else {
    error_reporting(E_ALL & ~E_DEPRECATED);
    ini_set('display_errors', '0');
}
