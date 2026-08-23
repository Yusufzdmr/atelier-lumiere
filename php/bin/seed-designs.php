<?php
declare(strict_types=1);

/**
 * Ein Thema als Dokument der zweiten Fassung anlegen.
 *
 *   php bin/seed-designs.php              Élysée schreiben
 *   php bin/seed-designs.php noir         Noir schreiben
 *   php bin/seed-designs.php noir --dry   nur zeigen
 *   php bin/seed-designs.php referenz     die zwei Vorlagen "film" und "bild"
 *
 * Zwei Vorlagen, weil eine nichts beweist: Élysée ist hell, floral und ruhig,
 * Noir dunkel und geometrisch. Was das Format nur bei einer von beiden kann,
 * kann es nicht.
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
 * Voraussetzung: php bin/export-scene-art.php <id> ist gelaufen.
 */

require __DIR__ . '/../src/bootstrap.php';

use Atelier\Design;
use Atelier\Themes;

if (PHP_SAPI !== 'cli') {
    exit('Nur über die Kommandozeile.');
}

$dry = in_array('--dry', $argv, true);

/*
 * Was sich zwischen zwei Vorlagen unterscheidet, steht hier; alles andere ist
 * geteilt. Die y-Werte sind Prozente der Kopfzone, gemessen am gerenderten
 * Original. Sie unterscheiden sich, weil der Namensblock von Noir enger sitzt
 * als der von Élysée - die Karte ist eben nicht themenunabhaengig.
 */
$tasarimlar = [
    'elysee' => [
        'kategorie' => 'luxury',
        'tags'      => ['creme', 'gold'],
        'sort'      => 1,
        // Datei, Kasten und Kippung je Teil. Élysée zeichnet jede Ecke
        // einzeln, deshalb drei verschiedene Dateien und keine Spiegelung
        // ausser unten links.
        'parcalar'  => [
            ['id' => 'szenetl', 'wo' => 'oben links',   'datei' => 'elysee-1', 'x' => 0, 'y' => 0, 'w' => 17, 'anker' => 'topleft',     'delay' => 200],
            ['id' => 'szenetr', 'wo' => 'oben rechts',  'datei' => 'elysee-2', 'x' => 0, 'y' => 0, 'w' => 17, 'anker' => 'topright',    'delay' => 350],
            ['id' => 'szenebl', 'wo' => 'unten links',  'datei' => 'elysee-3', 'x' => 0, 'y' => 0, 'w' => 14, 'anker' => 'bottomleft',  'flipy' => 1, 'delay' => 500],
        ],
        'kunstwort' => 'Blattwerk',
        'y'         => ['gruss' => 12, 'marie' => 20, 'und' => 34, 'jonas' => 44,
                        'tag' => 73, 'datum' => 77, 'saat' => 84, 'ort' => 91, 'adres' => 95],
    ],
    'noir' => [
        'kategorie' => 'modern',
        'tags'      => ['dunkel', 'gold'],
        'sort'      => 2,
        // Noir stellt zwei Zeichnungen in vier Ecken: gespiegelt und
        // gedreht. Seit die Kiste flipx/flipy kennt, braucht es dafuer auch
        // nur zwei Dateien - noir-2 und noir-4 waren Kopien von noir-1 und
        // noir-3 und sind geloescht.
        'parcalar'  => [
            ['id' => 'szenetl', 'wo' => 'oben links',   'datei' => 'noir-1', 'x' => 0, 'y' => 0, 'w' => 17, 'anker' => 'topleft',     'delay' => 200],
            ['id' => 'szenetr', 'wo' => 'oben rechts',  'datei' => 'noir-1', 'x' => 0, 'y' => 0, 'w' => 17, 'anker' => 'topright',    'flipx' => 1, 'delay' => 350],
            ['id' => 'szenebl', 'wo' => 'unten links',  'datei' => 'noir-3', 'x' => 0, 'y' => 0, 'w' => 14, 'anker' => 'bottomleft',  'flipy' => 1, 'delay' => 500],
            ['id' => 'szenebr', 'wo' => 'unten rechts', 'datei' => 'noir-3', 'x' => 0, 'y' => 0, 'w' => 14, 'anker' => 'bottomright', 'rotate' => 180, 'delay' => 650],
        ],
        // Noirs Szene zeichnet keine Blaetter, sondern Winkel.
        'kunstwort' => 'Ecke',
        'y'         => ['gruss' => 12, 'marie' => 23, 'und' => 36, 'jonas' => 47,
                        'tag' => 73, 'datum' => 77, 'saat' => 84, 'ort' => 91, 'adres' => 95],
    ],
];

$id = 'elysee';
foreach (array_slice($argv, 1) as $arg) {
    if ($arg !== '--dry') { $id = $arg; }
}

/*
 * Ein dritter Aufruf, neben den beiden Themen: die zwei Vorlagen, mit denen
 * der Kunde anfangen will. "1. video, 2. resim."
 *
 *   php bin/seed-designs.php referenz
 *
 * Sie kommen aus KEINEM Thema - deshalb dieser eigene Weg und nicht ein
 * Eintrag in $tasarimlar: dort steht neben jeder Kennung ein Thema, das es
 * fuer "film" und "bild" nicht gibt.
 *
 * Sie unterscheiden sich in genau einer Ebene - der hintersten. Alles andere
 * ist gleich, mit Absicht: was der Kunde vergleichen will, ist der Film gegen
 * das Foto, nicht zwei Entwuerfe gegeneinander.
 *
 * Die Mitte bleibt frei. "Ortasi bos dusun, sadece cicekler var."
 */
if ($id === 'referenz') {
    /*
     * Marken statt roher Werte, wie ueberall: eine Vorlage ohne Palette und
     * ohne Schriftmarken zeigt im Panel fuer jede Textebene eine Warnung, und
     * eine Referenz, die warnt, ist keine.
     */
    $referenzPalette = [
        'paper'  => ['value' => '#FBF6EE', 'label' => ['de' => 'Papier',   'tr' => 'Kagit'],  'customer' => false],
        'bg'     => ['value' => '#F3EADC', 'label' => ['de' => 'Seite',    'tr' => 'Sayfa'],  'customer' => false],
        'fg'     => ['value' => '#2E2A26', 'label' => ['de' => 'Schrift',  'tr' => 'Yazi'],   'customer' => false],
        'soft'   => ['value' => '#8A8078', 'label' => ['de' => 'Gedaempft','tr' => 'Soluk'],  'customer' => false],
        // Das Gold darf der Kunde waehlen - dieselbe Regel wie bei Elysee.
        'accent' => ['value' => '#B08D57', 'label' => ['de' => 'Gold',     'tr' => 'Altin'],  'customer' => true],
    ];

    $referenzSchriften = [
        'display' => ['family' => 'Cormorant Garamond', 'size' => 100, 'weight' => 300,
                      'tracking' => 4, 'lineHeight' => 115, 'customer' => false],
        'body'    => ['family' => 'Jost', 'size' => 100, 'weight' => 400,
                      'tracking' => 0, 'lineHeight' => 150, 'customer' => false],
    ];

    $grundEbenen = static function (array $hinten): array {
        return array_merge([$hinten], [
            // Der Rahmen aus Motiven. Er liegt auf der Seite, hinter der Karte,
            // und laesst die Mitte offen - die Datei selbst hat dort nichts.
            [
                'id'    => 'rahmen',
                'label' => 'Bluetenrahmen',
                'type'  => 'image',
                'spot'  => 'page',
                'src'   => '',   // Der Grafiker legt die Datei; leer faellt sie weg.
                'box'   => ['x' => 0, 'y' => 0, 'w' => 100, 'h' => 100, 'opacity' => 100],
                'motion' => ['move' => 'fade', 'delay' => 0, 'duration' => 1200],
            ],
            [
                'id'    => 'obertitel',
                'label' => 'Ueberschrift',
                'type'  => 'text',
                'spot'  => 'card',
                'text'  => ['de' => 'WIR HEIRATEN', 'en' => 'WE ARE GETTING MARRIED'],
                'box'   => ['x' => 10, 'y' => 30, 'w' => 80],
                'style' => ['font' => 'body', 'color' => 'soft', 'size' => 26, 'align' => 'center'],
                'motion' => ['move' => 'fade', 'delay' => 200, 'duration' => 1200],
            ],
            [
                'id'    => 'namen',
                'label' => 'Namen',
                'type'  => 'text',
                'spot'  => 'card',
                'bind'  => 'couple_names',
                'box'   => ['x' => 8, 'y' => 42, 'w' => 84],
                'style' => ['font' => 'display', 'color' => 'fg', 'size' => 108, 'align' => 'center'],
                'motion' => ['move' => 'fade', 'delay' => 500, 'duration' => 1400],
            ],
            [
                'id'    => 'datum',
                'label' => 'Datum',
                'type'  => 'text',
                'spot'  => 'card',
                'bind'  => 'wedding_date',
                'box'   => ['x' => 10, 'y' => 60, 'w' => 80],
                'style' => ['font' => 'body', 'color' => 'soft', 'size' => 30, 'align' => 'center'],
                'motion' => ['move' => 'fade', 'delay' => 800, 'duration' => 1200],
            ],
        ]);
    };

    $vorlagen = [
        [
            'id'   => 'film',
            'slug' => 'film',
            'name' => ['de' => 'Film', 'en' => 'Film'],
            'category' => 'floral',
            'status'   => 'draft',
            'canvas'   => ['ratio' => '632:490', 'safe' => 6],
            'palette'  => $referenzPalette,
            'fonts'    => $referenzSchriften,
            'layers'   => $grundEbenen([
                'id'     => 'hintergrund',
                'label'  => 'Hintergrundfilm',
                'type'   => 'video',
                'spot'   => 'page',
                'src'    => '',      // kommt aus der Bibliothek oder vom Grafiker
                'poster' => '',
                'box'    => ['x' => 0, 'y' => 0, 'w' => 100, 'h' => 100, 'opacity' => 100],
                // Das Recht, aus der Bibliothek zu waehlen: edit ist der
                // Hauptschalter, photo die Erlaubnis fuer den Inhalt.
                'permissions' => ['edit' => true, 'photo' => true],
            ]),
        ],
        [
            'id'   => 'bild',
            'slug' => 'bild',
            'name' => ['de' => 'Bild', 'en' => 'Image'],
            'category' => 'floral',
            'status'   => 'draft',
            'canvas'   => ['ratio' => '632:490', 'safe' => 6],
            'palette'  => $referenzPalette,
            'fonts'    => $referenzSchriften,
            'layers'   => $grundEbenen([
                'id'    => 'hintergrund',
                'label' => 'Hintergrundbild',
                'type'  => 'photo',
                'spot'  => 'page',
                'src'   => '',
                'box'   => ['x' => 0, 'y' => 0, 'w' => 100, 'h' => 100, 'opacity' => 100],
                'permissions' => ['edit' => true, 'photo' => true],
            ]),
        ],
    ];

    foreach ($vorlagen as $roh) {
        // Nicht ueberschreiben, was schon da ist: der Grafiker hat womoeglich
        // laengst Dateien hinterlegt, und ein zweiter Seed-Lauf soll seine
        // Arbeit nicht wegwerfen.
        if (Design::findById($roh['id']) !== null) {
            echo "  {$roh['id']}: gibt es schon, uebersprungen\n";
            continue;
        }
        if ($dry) {
            echo "  {$roh['id']}: wuerde angelegt (", count($roh['layers']), " Ebenen)\n";
            continue;
        }
        Design::save(Design::complete($roh));
        echo "  {$roh['id']}: angelegt\n";
    }

    exit(0);
}

if (!isset($tasarimlar[$id])) {
    exit("Unbekannt: {$id}. Bekannt: " . implode(', ', array_keys($tasarimlar)) . ", referenz" . PHP_EOL);
}
$cfg = $tasarimlar[$id];
$y   = $cfg['y'];

$theme = Themes::find($id);
if ($theme === null) {
    exit("Thema „{$id}\" nicht gefunden.\n");
}

foreach (array_unique(array_column($cfg['parcalar'], 'datei')) as $stueck) {
    $pfad = __DIR__ . '/../public/assets/designs/' . $stueck . '.svg';
    if (!is_file($pfad)) {
        exit("Es fehlt {$stueck}.svg – erst „php bin/export-scene-art.php {$id}\" laufen lassen.\n");
    }
}

$doc = Design::fromTheme($theme);

$doc['status']   = 'active';
$doc['category'] = $cfg['kategorie'];
$doc['tags']     = $cfg['tags'];
$doc['sort']     = $cfg['sort'];
// Gemessen, nicht gewaehlt: der Kopf der Karte ist 632 x 490 - quer, nicht
// hochkant. Was darunter liegt, sind Abschnitte und gehoert nach Faz 3.
$doc['canvas']   = ['ratio' => '632:490', 'safe' => 6];

// Die Schriften des Themas als Marken.
$doc['fonts'] = [
    'display' => ['family' => 'Cormorant Garamond', 'size' => 100, 'weight' => 300,
                  'tracking' => 4, 'lineHeight' => 115, 'customer' => false],
    'body'    => ['family' => 'Jost', 'size' => 100, 'weight' => 400,
                  'tracking' => 0, 'lineHeight' => 150, 'customer' => false],
    // Die Namen stehen im Original in einer Schreibschrift, nicht in der
    // Serifenschrift. Gemessen am gerenderten Original: Great Vibes, Gewicht
    // 400, keine Laufweite, Zeilenhoehe 1,056. Die Datei liegt im Projekt
    // (/fonts/greatvibes-latin.woff2), es kommt nichts von aussen dazu.
    'script'  => ['family' => 'Great Vibes', 'size' => 100, 'weight' => 400,
                  'tracking' => 0, 'lineHeight' => 106, 'customer' => false],
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

    // 2. Die gezeichnete Szene (frueher Scenes::html). Aus der Tabelle oben,
    //    damit eine Vorlage mit vier Ecken keine vierte Zeile Code braucht.
    ...array_map(static fn (array $p): array => [
        'id'     => $p['id'],
        'label'  => $cfg['kunstwort'] . ' ' . $p['wo'],
        'type'   => 'image',
        'spot'   => 'page',
        'src'    => '/assets/designs/' . $p['datei'] . '.svg',
        'box'    => ['x' => $p['x'], 'y' => $p['y'], 'w' => $p['w'], 'h' => 0,
                     'rotate' => $p['rotate'] ?? 0,
                     'flipx' => $p['flipx'] ?? 0, 'flipy' => $p['flipy'] ?? 0,
                     // Ecken kleben, sie werden nicht ausgerechnet.
                     'anchor' => $p['anker'] ?? 'topleft',
                     'opacity' => 100],
        'motion' => ['move' => 'rise', 'delay' => $p['delay'], 'duration' => 1600],
    ], $cfg['parcalar']),

    // 3. Der Kopf der Karte. Alle Zahlen am gerenderten Original gemessen:
    //    x und w in Prozent der Kartenbreite, y in Prozent der 490 px hohen
    //    Kopfzone, size als Zehnfaches des gemessenen cqw-Werts.
    ['id' => 'gruss', 'label' => 'Gruss', 'type' => 'text', 'spot' => 'card',
     'text' => ['de' => 'Wir heiraten', 'en' => 'We are getting married'],
     'box' => ['x' => 8, 'y' => $y['gruss'], 'w' => 85, 'h' => 0, 'rotate' => 0, 'opacity' => 100],
     'style' => ['font' => 'body', 'color' => 'soft', 'size' => 15, 'align' => 'center'],
     'motion' => ['move' => 'fade', 'delay' => 300, 'duration' => 1000],
     'permissions' => ['text' => true]],

    // Zwei Zeilen, kein Name am Stueck - so steht es im Original.
    ['id' => 'marie', 'label' => 'Braut', 'type' => 'text', 'spot' => 'card',
     'bind' => 'bride_name',
     // Volle Textbreite, nicht die gemessene Breite des kurzen Namens:
     // gemessen wurde "Marie", und ein langer Name laeuft aus einem
     // 27%-Kasten nach beiden Seiten heraus. Zentriert wird per text-align.
     'box' => ['x' => 8, 'y' => $y['marie'], 'w' => 85, 'h' => 0, 'rotate' => 0, 'opacity' => 100],
     'style' => ['font' => 'script', 'color' => 'accent', 'size' => 111, 'align' => 'center'],
     'motion' => ['move' => 'fade', 'delay' => 400, 'duration' => 1200],
     'permissions' => ['color' => true]],

    // Das Kaufmanns-Und zwischen den Namen. Im Original ein eigenes Element
    // (invitation.php:289), in Gold und in der Serifenschrift - nicht in der
    // Schreibschrift der Namen. Gemessen: y 176,8 px von 521 = 34 %,
    // 4,75 cqw = size 47. Kursiv kann das Format noch nicht; es steht
    // deshalb aufrecht, und das ist in der Spec vermerkt.
    ['id' => 'und', 'label' => 'Und-Zeichen', 'type' => 'text', 'spot' => 'card',
     'text' => ['de' => '&', 'en' => '&'],
     'box' => ['x' => 8, 'y' => $y['und'], 'w' => 85, 'h' => 0, 'rotate' => 0, 'opacity' => 100],
     'style' => ['font' => 'display', 'color' => 'accent', 'size' => 47, 'align' => 'center'],
     'motion' => ['move' => 'fade', 'delay' => 475, 'duration' => 1200],
     'permissions' => []],

    ['id' => 'jonas', 'label' => 'Braeutigam', 'type' => 'text', 'spot' => 'card',
     'bind' => 'groom_name',
     'box' => ['x' => 8, 'y' => $y['jonas'], 'w' => 85, 'h' => 0, 'rotate' => 0, 'opacity' => 100],
     'style' => ['font' => 'script', 'color' => 'accent', 'size' => 111, 'align' => 'center'],
     'motion' => ['move' => 'fade', 'delay' => 550, 'duration' => 1200],
     'permissions' => ['color' => true]],

    ['id' => 'tag', 'label' => 'Wochentag', 'type' => 'text', 'spot' => 'card',
     'bind' => 'wedding_weekday',
     'box' => ['x' => 8, 'y' => $y['tag'], 'w' => 85, 'h' => 0, 'rotate' => 0, 'opacity' => 100],
     'style' => ['font' => 'body', 'color' => 'soft', 'size' => 18, 'align' => 'center'],
     'motion' => ['move' => 'fade', 'delay' => 700, 'duration' => 1000],
     'permissions' => []],

    ['id' => 'datum', 'label' => 'Datum', 'type' => 'text', 'spot' => 'card',
     'bind' => 'wedding_date',
     'box' => ['x' => 8, 'y' => $y['datum'], 'w' => 85, 'h' => 0, 'rotate' => 0, 'opacity' => 100],
     'style' => ['font' => 'display', 'color' => 'fg', 'size' => 38, 'align' => 'center'],
     'motion' => ['move' => 'fade', 'delay' => 800, 'duration' => 1000],
     'permissions' => []],

    ['id' => 'saat', 'label' => 'Uhrzeit', 'type' => 'text', 'spot' => 'card',
     'bind' => 'wedding_time',
     'box' => ['x' => 8, 'y' => $y['saat'], 'w' => 85, 'h' => 0, 'rotate' => 0, 'opacity' => 100],
     'style' => ['font' => 'body', 'color' => 'fg', 'size' => 23, 'align' => 'center'],
     'motion' => ['move' => 'fade', 'delay' => 880, 'duration' => 1000],
     'permissions' => []],

    ['id' => 'ort', 'label' => 'Ort', 'type' => 'text', 'spot' => 'card',
     'bind' => 'location_name',
     'box' => ['x' => 8, 'y' => $y['ort'], 'w' => 85, 'h' => 0, 'rotate' => 0, 'opacity' => 100],
     'style' => ['font' => 'body', 'color' => 'fg', 'size' => 24, 'align' => 'center'],
     'motion' => ['move' => 'fade', 'delay' => 960, 'duration' => 1000],
     'permissions' => []],

    ['id' => 'adres', 'label' => 'Adresse', 'type' => 'text', 'spot' => 'card',
     'bind' => 'location_address',
     // 96 % waeren 500 px, und die Unterlaengen von "Guenzburg" enden bei
     // 522 - einen Pixel unter der 521 px hohen Karte, die sie abschneidet.
     'box' => ['x' => 8, 'y' => $y['adres'], 'w' => 85, 'h' => 0, 'rotate' => 0, 'opacity' => 100],
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
