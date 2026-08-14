<?php
declare(strict_types=1);

/**
 * Schreibt die redaktionellen Inhalte als SQL-Datei für den Livegang.
 *
 *   php bin/export.php
 *
 * Ergebnis: data/inhalte.sql – im phpMyAdmin des KAS nach schema.sql zu
 * importieren. Damit ist die Seite fertig gefüllt, ohne dass auf dem Webspace
 * eine Kommandozeile gebraucht wird.
 *
 * Bewusst NICHT dabei:
 *   integrations – enthält Zugangsdaten. Die werden auf dem Livesystem im
 *                  Adminbereich eingetragen, nicht aus einer Datei kopiert,
 *                  die durch drei Postfächer gewandert ist.
 *   customers, galleries, invitations, leads – das sind Daten echter Menschen
 *                  aus dem Testbetrieb. Auf dem Livesystem beginnt das leer.
 */

require __DIR__ . '/../src/bootstrap.php';

use Atelier\Content;
use Atelier\Db;

if (PHP_SAPI !== 'cli') {
    exit('Nur über die Kommandozeile.');
}

$target = __DIR__ . '/../data/inhalte.sql';

$live = Content::all();
$original = Content::original();

if ($live === []) {
    exit("site_content ist leer – hier gibt es nichts zu exportieren.\n");
}

/** Ein JSON-Dokument als SQL-Zeichenkette. */
$quote = static function (array $data): string {
    return "'" . str_replace(["\\", "'"], ["\\\\", "\\'"], Db::encode($data)) . "'";
};

$sql = "-- Atelier Lumière – Inhalte für den Livegang\n"
    . '-- Erzeugt am ' . date('Y-m-d H:i') . "\n"
    . "--\n"
    . "-- Reihenfolge im KAS:\n"
    . "--   1. schema.sql importieren  (Tabellen)\n"
    . "--   2. diese Datei importieren (Inhalte)\n"
    . "--\n"
    . "-- Datensatz 1 ist der Stand, mit dem gearbeitet wird.\n"
    . "-- Datensatz 2 ist die unberührte Kopie für „zurücksetzen“ im Adminbereich.\n\n"
    . "SET NAMES utf8mb4;\n\n"
    . 'REPLACE INTO site_content (id, data) VALUES (1, ' . $quote($live) . ");\n\n";

if ($original !== []) {
    $sql .= 'REPLACE INTO site_content (id, data) VALUES (2, ' . $quote($original) . ");\n";
} else {
    // Ohne Original gäbe es im Adminbereich kein „Vorher“ – dann lieber den
    // heutigen Stand einfrieren als gar keinen Vergleich zu haben.
    $sql .= 'REPLACE INTO site_content (id, data) VALUES (2, ' . $quote($live) . ");\n";
}

file_put_contents($target, $sql);

printf(
    "data/inhalte.sql geschrieben – %s KB\n\n",
    number_format(filesize($target) / 1024, 1)
);

printf("  %d Städte\n", count(Content::list('cities')));
printf("  %d Locations\n", count(Content::list('venues')));
printf("  %d Reportagen\n", count(Content::list('stories')));
printf("  %d Ratgeber-Beiträge\n", count(Content::list('posts')));
printf("  %d Themen\n", count(Content::list('themes')));
printf("  %d geänderte Seitentexte\n", count((array) ($live['texts'] ?? [])));

echo "\nNicht enthalten (mit Absicht): Zugangsdaten, Kunden, Galerien, Einladungen.\n";
