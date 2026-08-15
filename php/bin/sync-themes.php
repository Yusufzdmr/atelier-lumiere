<?php
declare(strict_types=1);

/**
 * Neue Themen aus data/themes.php in den gespeicherten Stand uebernehmen.
 *
 *   php bin/sync-themes.php --dry   nur zeigen, was dazukaeme
 *   php bin/sync-themes.php         uebernehmen
 *
 * Ab der ersten Aenderung im Adminbereich gilt der Stand aus der Datenbank –
 * data/themes.php ist dann nur noch die Startbelegung. Kommt spaeter ein Thema
 * dazu, sieht es deshalb niemand mehr. Dieses Skript haengt genau die an, die
 * unter ihrer id noch fehlen.
 *
 * Bestehende Themen werden **nicht** angefasst. Wer im Admin an Élysée die
 * Farben geaendert hat, behaelt seine Fassung; ein erneuter Lauf tut nichts.
 */

require __DIR__ . '/../src/bootstrap.php';

use Atelier\Themes;

if (PHP_SAPI !== 'cli') {
    exit('Nur über die Kommandozeile.');
}

$dry = in_array('--dry', $argv, true);

$stored = Themes::all();
$have = [];
foreach ($stored as $theme) {
    $have[(string) ($theme['id'] ?? '')] = true;
}

/** @var list<array<string,mixed>> $seed */
$seed = require __DIR__ . '/../data/themes.php';

$neu = [];
foreach ($seed as $theme) {
    $id = (string) ($theme['id'] ?? '');
    if ($id === '' || isset($have[$id])) {
        continue;
    }
    $neu[] = $theme;
    printf("%s %-12s %s\n", $dry ? 'käme dazu:' : 'ergänzt: ', $id, (string) ($theme['name'] ?? ''));
}

if ($neu === []) {
    echo "Nichts zu tun – alle Themen sind vorhanden.\n";
    exit;
}

if (!$dry) {
    Themes::save(array_merge($stored, $neu));
}

printf("\n%s: %d · unveraendert: %d\n", $dry ? 'Kämen dazu' : 'Ergänzt', count($neu), count($stored));
