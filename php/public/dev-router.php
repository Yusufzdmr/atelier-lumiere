<?php
/**
 * Nur für den eingebauten PHP-Server während der Entwicklung:
 *
 *   php -S 127.0.0.1:8080 -t public public/dev-router.php
 *
 * Auf dem Webspace übernimmt .htaccess diese Aufgabe – dort wird diese Datei
 * nie aufgerufen.
 */

$path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
$file = __DIR__ . $path;

// Vorhandene Datei (Stylesheet, Skript, Schrift, Bild) direkt ausliefern.
if ($path !== '/' && is_file($file)) {
    return false;
}

require __DIR__ . '/index.php';
