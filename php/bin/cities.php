<?php
declare(strict_types=1);

/**
 * Spielt die Stadtseiten rund um Krumbach ein.
 *
 *   php bin/cities.php           zeigt nur, was passieren würde
 *   php bin/cities.php --write   ersetzt die Städte
 *
 * Die bisherigen Städte sind der Stuttgarter Demosatz. Ersetzt wird komplett,
 * nicht ergänzt: ein halb umgestellter Regionsbereich wäre schlechter als
 * beides einzeln – die Nachbarschaftsverweise zeigten ins Leere.
 *
 * Was NICHT angefasst wird: Portfolio, Locations und Ratgeber. Die hängen an
 * Fotos und Referenzen, die es noch nicht gibt; erfundene Reportagen wären
 * schlimmer als sichtbare Demodaten.
 */

require __DIR__ . '/../src/bootstrap.php';

use Atelier\Content;

if (PHP_SAPI !== 'cli') {
    exit('Nur über die Kommandozeile.');
}

$write = in_array('--write', $argv, true);

$cities = array_merge(
    require __DIR__ . '/../data/cities-krumbach.php',
    require __DIR__ . '/../data/cities-krumbach-2.php'
);

/* ----------------------------- Erst prüfen ------------------------------ */

$slugs = array_column($cities, 'slug');
$problems = [];

if (count($slugs) !== count(array_unique($slugs))) {
    $problems[] = 'Doppelte Adressen: ' . implode(', ', array_diff_assoc($slugs, array_unique($slugs)));
}

foreach ($cities as $city) {
    foreach ($city['neighbours'] as $neighbour) {
        if (!in_array($neighbour, $slugs, true)) {
            $problems[] = $city['slug'] . ' verweist auf ' . $neighbour . ', das es nicht gibt';
        }
    }
    if (in_array($city['slug'], $city['neighbours'], true)) {
        $problems[] = $city['slug'] . ' verweist auf sich selbst';
    }
    foreach (['de', 'tr'] as $lang) {
        if (trim((string) $city['lead'][$lang]) === '' || $city['body'][$lang] === []) {
            $problems[] = $city['slug'] . ': Text fehlt (' . $lang . ')';
        }
    }
}

/* --------------------- Auf Türseiten-Verdacht prüfen -------------------- */

// Zwei Städte, deren Fließtext sich stark ähnelt, sind der Anfang genau des
// Problems, das diese Seiten vermeiden sollen. Deshalb hier ein Vergleich.
foreach ($cities as $i => $a) {
    foreach (array_slice($cities, $i + 1) as $b) {
        similar_text(
            implode(' ', $a['body']['de']),
            implode(' ', $b['body']['de']),
            $percent
        );
        if ($percent > 55) {
            $problems[] = sprintf('%s und %s sind sich zu ähnlich (%d%%)', $a['slug'], $b['slug'], $percent);
        }
    }
}

if ($problems !== []) {
    echo "Nicht eingespielt:\n";
    foreach ($problems as $problem) {
        echo '  - ' . $problem . "\n";
    }
    exit(1);
}

/* -------------------------------- Bericht -------------------------------- */

$before = Content::list('cities');

echo 'Bisher: ' . count($before) . ' Städte (' . implode(', ', array_column($before, 'slug')) . ")\n";
echo 'Neu:    ' . count($cities) . " Städte\n\n";

foreach ($cities as $city) {
    printf(
        "  %-18s %-22s %2d Absätze · %d Spots · %d Fragen · %d Nachbarn\n",
        $city['slug'],
        $city['name'],
        count($city['body']['de']) + count($city['body']['tr']),
        count($city['spots']),
        count($city['faq']),
        count($city['neighbours'])
    );
}

$words = 0;
foreach ($cities as $city) {
    foreach (['de', 'tr'] as $lang) {
        $words += str_word_count($city['lead'][$lang]) + str_word_count(implode(' ', $city['body'][$lang]));
    }
}
echo "\n  rund $words Wörter in zwei Sprachen\n";

if (!$write) {
    echo "\nNichts geändert. Mit --write einspielen.\n";
    exit(0);
}

Content::mutate(static function (array $content) use ($cities): array {
    $content['cities'] = $cities;
    return $content;
});

echo "\nEingespielt. Nicht vergessen: sitemap.xml prüfen und die alten Adressen\n";
echo "der Stuttgarter Städte umleiten, falls sie schon einmal online waren.\n";
