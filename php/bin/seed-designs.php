<?php
declare(strict_types=1);

/**
 * Élysée als Dokument der zweiten Fassung anlegen.
 *
 *   php bin/seed-designs.php --dry   nur zeigen
 *   php bin/seed-designs.php         schreiben
 *
 * Das Thema selbst bleibt unberuehrt und laeuft weiter. Hier entsteht daneben
 * ein Eintrag in `designs`, damit sich beide nebeneinander ansehen lassen.
 *
 * Die Kaesten stehen als Zahlen hier und nicht in Design::fromTheme(): im
 * alten Motor liegen weder die Szene noch die Namen als Daten vor. Die Szene
 * kommt aus Scenes.php, die Namen aus dem Fluss des Kartensatzes. Beide lassen
 * sich nur am fertigen Bild abmessen – und eine gemessene Zahl gehoert dorthin,
 * wo jemand sie nachmessen kann.
 *
 * Voraussetzung: php bin/export-scene-art.php elysee ist gelaufen.
 */

require __DIR__ . '/../src/bootstrap.php';

use Atelier\Design;
use Atelier\Themes;

if (PHP_SAPI !== 'cli') {
    exit('Nur über die Kommandozeile.');
}

$dry = in_array('--dry', $argv, true);

$theme = Themes::find('elysee');
if ($theme === null) {
    exit("Thema „elysee\" nicht gefunden.\n");
}

foreach (['elysee-1', 'elysee-2', 'elysee-3'] as $stueck) {
    $pfad = __DIR__ . '/../public/assets/designs/' . $stueck . '.svg';
    if (!is_file($pfad)) {
        exit("Es fehlt {$stueck}.svg – erst „php bin/export-scene-art.php elysee\" laufen lassen.\n");
    }
}

$doc = Design::fromTheme($theme);

$doc['status']   = 'active';
$doc['category'] = 'luxury';
$doc['tags']     = ['creme', 'gold'];
$doc['sort']     = 1;
$doc['name']     = ['de' => 'Élysée', 'en' => 'Élysée'];
// Gemessen, nicht gewaehlt: der Kopf der Karte ist 632 x 490 - quer, nicht
// hochkant. Was darunter liegt, sind Abschnitte und gehoert nach Faz 3.
$doc['canvas']   = ['ratio' => '632:490', 'safe' => 6];

// Die Schriften des Themas als Marken.
$doc['fonts'] = [
    'display' => ['family' => 'Cormorant Garamond', 'size' => 100, 'weight' => 300,
                  'tracking' => 4, 'lineHeight' => 115, 'customer' => false],
    'body'    => ['family' => 'Jost', 'size' => 100, 'weight' => 400,
                  'tracking' => 0, 'lineHeight' => 150, 'customer' => false],
];

// Das Gold darf der Kunde spaeter waehlen.
if (isset($doc['palette']['accent'])) {
    $doc['palette']['accent']['customer'] = true;
    $doc['palette']['accent']['label'] = ['de' => 'Gold', 'tr' => 'Altın'];
}

/*
 * Alle Zahlen gemessen an /de/designs/elysee auf Desktopbreite.
 * Wer die Karte umbaut, misst nach.
 *
 * Reihenfolge ist Stapelreihenfolge: Farbflecken ganz hinten, dann die
 * Zeichnung, dann der Text.
 */
$ebenen = [
    // 1. Die weichen Farbflecken (frueher .scene-wash-a / -b)
    ['id' => 'washa', 'label' => 'Farbfleck oben links', 'type' => 'shape', 'spot' => 'page',
     'box' => ['x' => -16, 'y' => -10, 'w' => 36, 'h' => 58, 'rotate' => 0, 'opacity' => 30],
     'style' => ['color' => 'accentsoft', 'blur' => 46, 'radius' => 50],
     'motion' => ['move' => 'fade', 'delay' => 0, 'duration' => 1600]],

    ['id' => 'washb', 'label' => 'Farbfleck unten rechts', 'type' => 'shape', 'spot' => 'page',
     'box' => ['x' => 82, 'y' => 57, 'w' => 32, 'h' => 51, 'rotate' => 0, 'opacity' => 34],
     'style' => ['color' => 'petal', 'blur' => 46, 'radius' => 50],
     'motion' => ['move' => 'fade', 'delay' => 0, 'duration' => 1600]],

    // 2. Die gezeichnete Szene (frueher Scenes::html)
    ['id' => 'szenetl', 'label' => 'Blattwerk oben links', 'type' => 'image', 'spot' => 'page',
     'src' => '/assets/designs/elysee-1.svg',
     'box' => ['x' => 0, 'y' => 0, 'w' => 17, 'h' => 0, 'rotate' => 0, 'opacity' => 100],
     'motion' => ['move' => 'rise', 'delay' => 200, 'duration' => 1600]],

    ['id' => 'szenetr', 'label' => 'Blattwerk oben rechts', 'type' => 'image', 'spot' => 'page',
     'src' => '/assets/designs/elysee-2.svg',
     'box' => ['x' => 83, 'y' => 0, 'w' => 17, 'h' => 0, 'rotate' => 0, 'opacity' => 100],
     'motion' => ['move' => 'rise', 'delay' => 350, 'duration' => 1600]],

    ['id' => 'szenebl', 'label' => 'Blattwerk unten links', 'type' => 'image', 'spot' => 'page',
     'src' => '/assets/designs/elysee-3.svg',
     'box' => ['x' => 0, 'y' => 78, 'w' => 14, 'h' => 0, 'rotate' => 180, 'opacity' => 100],
     'motion' => ['move' => 'rise', 'delay' => 500, 'duration' => 1600]],

    // 3. Der Kopf der Karte. Alle Zahlen am gerenderten Original gemessen:
    //    x und w in Prozent der Kartenbreite, y in Prozent der 490 px hohen
    //    Kopfzone, size als Zehnfaches des gemessenen cqw-Werts.
    ['id' => 'gruss', 'label' => 'Gruss', 'type' => 'text', 'spot' => 'card',
     'text' => ['de' => 'Wir heiraten', 'en' => 'We are getting married'],
     'box' => ['x' => 8, 'y' => 12, 'w' => 85, 'h' => 0, 'rotate' => 0, 'opacity' => 100],
     'style' => ['font' => 'body', 'color' => 'soft', 'size' => 15, 'align' => 'center'],
     'motion' => ['move' => 'fade', 'delay' => 300, 'duration' => 1000],
     'permissions' => ['text' => true]],

    // Zwei Zeilen, kein Name am Stueck - so steht es im Original.
    ['id' => 'marie', 'label' => 'Braut', 'type' => 'text', 'spot' => 'card',
     'bind' => 'bride_name',
     // Volle Textbreite, nicht die gemessene Breite des kurzen Namens:
     // gemessen wurde "Marie", und ein langer Name laeuft aus einem
     // 27%-Kasten nach beiden Seiten heraus. Zentriert wird per text-align.
     'box' => ['x' => 8, 'y' => 20, 'w' => 85, 'h' => 0, 'rotate' => 0, 'opacity' => 100],
     'style' => ['font' => 'display', 'color' => 'accent', 'size' => 111, 'align' => 'center'],
     'motion' => ['move' => 'fade', 'delay' => 400, 'duration' => 1200],
     'permissions' => ['color' => true]],

    ['id' => 'jonas', 'label' => 'Braeutigam', 'type' => 'text', 'spot' => 'card',
     'bind' => 'groom_name',
     'box' => ['x' => 8, 'y' => 44, 'w' => 85, 'h' => 0, 'rotate' => 0, 'opacity' => 100],
     'style' => ['font' => 'display', 'color' => 'accent', 'size' => 111, 'align' => 'center'],
     'motion' => ['move' => 'fade', 'delay' => 550, 'duration' => 1200],
     'permissions' => ['color' => true]],

    ['id' => 'tag', 'label' => 'Wochentag', 'type' => 'text', 'spot' => 'card',
     'bind' => 'wedding_weekday',
     'box' => ['x' => 8, 'y' => 73, 'w' => 85, 'h' => 0, 'rotate' => 0, 'opacity' => 100],
     'style' => ['font' => 'body', 'color' => 'soft', 'size' => 18, 'align' => 'center'],
     'motion' => ['move' => 'fade', 'delay' => 700, 'duration' => 1000],
     'permissions' => []],

    ['id' => 'datum', 'label' => 'Datum', 'type' => 'text', 'spot' => 'card',
     'bind' => 'wedding_date',
     'box' => ['x' => 8, 'y' => 77, 'w' => 85, 'h' => 0, 'rotate' => 0, 'opacity' => 100],
     'style' => ['font' => 'display', 'color' => 'fg', 'size' => 38, 'align' => 'center'],
     'motion' => ['move' => 'fade', 'delay' => 800, 'duration' => 1000],
     'permissions' => []],

    ['id' => 'saat', 'label' => 'Uhrzeit', 'type' => 'text', 'spot' => 'card',
     'bind' => 'wedding_time',
     'box' => ['x' => 8, 'y' => 84, 'w' => 85, 'h' => 0, 'rotate' => 0, 'opacity' => 100],
     'style' => ['font' => 'body', 'color' => 'fg', 'size' => 23, 'align' => 'center'],
     'motion' => ['move' => 'fade', 'delay' => 880, 'duration' => 1000],
     'permissions' => []],

    ['id' => 'ort', 'label' => 'Ort', 'type' => 'text', 'spot' => 'card',
     'bind' => 'location_name',
     'box' => ['x' => 8, 'y' => 91, 'w' => 85, 'h' => 0, 'rotate' => 0, 'opacity' => 100],
     'style' => ['font' => 'body', 'color' => 'fg', 'size' => 24, 'align' => 'center'],
     'motion' => ['move' => 'fade', 'delay' => 960, 'duration' => 1000],
     'permissions' => []],

    ['id' => 'adres', 'label' => 'Adresse', 'type' => 'text', 'spot' => 'card',
     'bind' => 'location_address',
     'box' => ['x' => 8, 'y' => 96, 'w' => 85, 'h' => 0, 'rotate' => 0, 'opacity' => 100],
     'style' => ['font' => 'body', 'color' => 'soft', 'size' => 22, 'align' => 'center'],
     'motion' => ['move' => 'fade', 'delay' => 1040, 'duration' => 1000],
     'permissions' => []],
];


foreach ($ebenen as $ebene) {
    $doc['layers'][] = $ebene;
}

$doc = Design::complete($doc);

$meldungen = Design::warnings($doc);
foreach ($meldungen as $meldung) {
    echo "Hinweis: ", $meldung['kind'], " an „", $meldung['element'], "\"";
    echo $meldung['detail'] !== '' ? ' (' . $meldung['detail'] . ')' : '';
    echo "\n";
}

echo count($doc['layers']), " Ebenen, ", count($doc['palette']), " Farbmarken, ",
     count($doc['fonts']), " Schriftmarken.\n";

foreach ($doc['layers'] as $i => $ebene) {
    printf("  %2d. %-9s %-8s %-22s x%4d y%4d w%4d\n",
        $i + 1, $ebene['type'], $ebene['spot'], $ebene['label'] ?: $ebene['id'],
        $ebene['box']['x'], $ebene['box']['y'], $ebene['box']['w']);
}

if ($dry) {
    echo "\nProbelauf – nichts geschrieben.\n";
    exit(0);
}

$vorher = Design::findById($doc['id']);
Design::save($doc);
$nachher = Design::findById($doc['id']);

echo "\n", $vorher === null ? "Angelegt" : "Aktualisiert",
     ": ", $doc['id'], " (Fassung ", $nachher['version'], ")\n";
