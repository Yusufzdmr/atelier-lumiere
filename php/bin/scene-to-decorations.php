<?php
declare(strict_types=1);

/**
 * Die exportierte Zeichnung als Schmuckelemente ins Thema schreiben.
 *
 *   php bin/scene-to-decorations.php elysee --dry
 *   php bin/scene-to-decorations.php elysee
 *
 * export-scene-art.php macht aus Scenes::html() Dateien. Dieses Skript macht
 * aus den Dateien Eintraege in theme.decorations - erst danach zeigt das Thema
 * seinen Schmuck ohne Scenes.php.
 *
 * Es laeuft deshalb VOR Aufgabe 9 und stirbt mit ihr: wie export-scene-art.php
 * liest es Scenes::html(), und zwar aus einem Grund - die Datei traegt nur die
 * viewBox, die LAGE steht in der Klasse des Knotens ("scene-tl", "scene-br").
 * Ohne sie laege jedes Teil ganzseitig uebereinander, und aus einer Ecke
 * wuerde ein Vollbild.
 *
 * Vorhandene decorations werden NICHT angeruehrt: wer im Panel schon etwas
 * hingelegt hat, soll es behalten. Die neuen kommen dahinter, und die
 * Obergrenze von zwoelf gilt weiter - was nicht mehr passt, wird gemeldet
 * und nicht still weggeworfen.
 */

require __DIR__ . '/../src/bootstrap.php';

use Atelier\Scenes;
use Atelier\Themes;

if (PHP_SAPI !== 'cli') {
    exit('Nur über die Kommandozeile.');
}

$id = '';
foreach (array_slice($argv, 1) as $arg) {
    if ($arg !== '--dry') {
        $id = $arg;
    }
}
$dry = in_array('--dry', $argv, true);

if ($id === '') {
    exit("Welches Thema? z. B. php bin/scene-to-decorations.php elysee\n");
}

$theme = Themes::find($id);
if ($theme === null) {
    exit("Thema '$id' gibt es nicht.\n");
}

/*
 * Die Lage je Klasse, uebersetzt in das, was eine Dekoration kann: x, y und
 * width in Prozent, dazu rotate. Die Quelle sind dieselben Regeln, die
 * export-scene-art.php ausdruckt - hier nur gerechnet statt vorgelesen.
 *
 * "auge" heisst: die Uebersetzung ist eine Naeherung und gehoert vor Aufgabe 9
 * angesehen. Ein Teil, das unten klebt (scene-bottom), kennt seine eigene
 * Hoehe nicht, und scene-wide sagt nur eine Breite und keinen Ort - beides
 * laesst sich nicht ausrechnen, nur schaetzen.
 */
$lage = [
    'scene-tl'     => ['x' =>  0, 'y' =>  0, 'width' =>  38, 'auge' => false],
    'scene-tr'     => ['x' => 62, 'y' =>  0, 'width' =>  38, 'auge' => false],
    'scene-bl'     => ['x' =>  0, 'y' => 68, 'width' =>  32, 'auge' => false],
    'scene-br'     => ['x' => 68, 'y' => 68, 'width' =>  32, 'auge' => false],
    'scene-left'   => ['x' =>  0, 'y' =>  6, 'width' =>  42, 'auge' => false],
    'scene-ml'     => ['x' =>  0, 'y' => 18, 'width' =>  20, 'auge' => false],
    'scene-mr'     => ['x' => 74, 'y' => 30, 'width' =>  22, 'auge' => false],
    'scene-top'    => ['x' =>  0, 'y' =>  0, 'width' => 100, 'auge' => false],
    'scene-bottom' => ['x' =>  0, 'y' => 72, 'width' => 100, 'auge' => true],
    'scene-wide'   => ['x' => 27, 'y' => 27, 'width' =>  46, 'auge' => true],
];

/**
 * Klassen, die drehen oder spiegeln - sie kommen zu einer Lage dazu.
 *
 * Spiegeln kann eine Dekoration nicht: completeDecoration() kennt x, y, width,
 * rotate und opacity, aber kein flipx. (Eine EBENE der zweiten Fassung kann es
 * - Design::css() schreibt scale(-1,1) -, eine Dekoration des Themas nicht.)
 *
 * Die Spiegelung in die exportierte Datei zu schreiben waere naheliegend und
 * ist falsch: seed-designs.php benutzt dieselben Dateien und dreht sie ueber
 * die Kiste (noir-3 mit flipy). Waere sie schon in der Datei, drehte die
 * Vorlage sie ein zweites Mal - und damit zurueck. Eine Datei, zwei Leser.
 *
 * Also: gemeldet, nicht geloest. Wer den Spiegel braucht, legt die gedrehte
 * Fassung als eigene Datei ab.
 */
$dreht = [
    'scene-flip'   => ['rotate' => 180, 'auge' => false],
    'scene-mirror' => ['rotate' =>   0, 'auge' => true],
    'scene-updown' => ['rotate' =>   0, 'auge' => true],
];

// Dieselbe Zerlegung wie im Export, in derselben Reihenfolge: Teil n gehoert
// zur Datei <id>-<n>.svg.
$html = Scenes::html((string) ($theme['scene'] ?? ''), $theme);
if (trim($html) === '') {
    exit("Thema '$id' hat keine gezeichnete Szene - hier ist nichts umzuziehen.\n");
}

$dom = new DOMDocument();
libxml_use_internal_errors(true);
if (!$dom->loadXML('<wurzel>' . $html . '</wurzel>')) {
    exit("Die Szene liess sich nicht als XML lesen - erst export-scene-art.php pruefen.\n");
}
libxml_clear_errors();

$klassen = [];
foreach ($dom->getElementsByTagName('svg') as $svg) {
    $klassen[] = trim($svg->getAttribute('class'));
}

$vorhanden = $theme['decorations'] ?? [];
$platz = 12 - count($vorhanden);

if ($platz <= 0) {
    exit("Das Thema hat schon zwoelf Schmuckelemente. Erst im Panel Platz machen.\n");
}

$dir = __DIR__ . '/../public/assets/designs';
$neu = [];
$anzusehen = [];

foreach ($klassen as $i => $klasse) {
    $n = $i + 1;
    $datei = $id . '-' . $n . '.svg';
    $pfad = $dir . '/' . $datei;

    if (!is_file($pfad)) {
        exit("$datei fehlt - erst php bin/export-scene-art.php $id laufen lassen.\n");
    }

    if (count($neu) >= $platz) {
        fwrite(STDERR, 'Achtung: ' . (count($klassen) - $platz) . " Datei(en) passen nicht mehr rein.\n");
        break;
    }

    $box = ['x' => 0, 'y' => 0, 'width' => 100];
    $rotate = 0;
    $auge = [];

    foreach (explode(' ', $klasse) as $stueck) {
        if (isset($lage[$stueck])) {
            $box = ['x' => $lage[$stueck]['x'], 'y' => $lage[$stueck]['y'], 'width' => $lage[$stueck]['width']];
            if ($lage[$stueck]['auge']) {
                $auge[] = $stueck;
            }
        }
        if (isset($dreht[$stueck])) {
            $rotate = $dreht[$stueck]['rotate'];
            if ($dreht[$stueck]['auge']) {
                $auge[] = $stueck . ' (kann eine Dekoration nicht)';
            }
        }
    }

    if ($klasse === '' || $auge !== []) {
        $anzusehen[] = 'szene' . $n . ': ' . ($klasse === '' ? '(ohne Klasse)' : implode(', ', $auge));
    }

    $neu[] = Themes::completeDecoration([
        'id'     => 'szene' . $n,
        'label'  => 'Szene ' . $n,
        'src'    => '/assets/designs/' . $datei,
        // Die Szene lag hinter der Karte, auf der Seite - genau wie hier.
        'spot'   => 'page',
        'x'      => (string) $box['x'],
        'y'      => (string) $box['y'],
        'width'  => (string) $box['width'],
        'rotate' => (string) $rotate,
        'front'  => false,
        // Nach der Beschneidung der Bewegungen gibt es nur noch fade und none.
        'move'   => 'fade',
    ]);
}

printf("%s: %d vorhanden + %d neu\n", $id, count($vorhanden), count($neu));
foreach ($neu as $j => $d) {
    printf("  %-8s x=%-4s y=%-4s w=%-4s dreh=%-4s  %s\n",
        $d['id'], $d['x'], $d['y'], $d['width'], $d['rotate'], $d['src']);
    printf("           Klasse: %s\n", $klassen[$j] === '' ? '(keine)' : $klassen[$j]);
}

if ($anzusehen !== []) {
    echo "\nVor Aufgabe 9 ansehen:\n";
    foreach ($anzusehen as $zeile) {
        echo '  ', $zeile, "\n";
    }
}

if ($dry) {
    echo "\nKuehler Lauf - nichts geschrieben.\n";
    exit(0);
}

$theme['decorations'] = array_merge($vorhanden, $neu);
// Die Szene wird nicht mehr gezeichnet: der Schmuck steht jetzt in den Daten.
$theme['scene'] = 'none';

/*
 * Themes::save() nimmt die GANZE Liste, nicht ein Thema - es vergleicht jedes
 * gegen den Stand davor, um die Fassungsnummer nur dann zu erhoehen, wenn sich
 * wirklich etwas geaendert hat. Einzeln zu speichern hiesse, alle anderen zu
 * loeschen.
 */
$alle = [];
foreach (Themes::all() as $t) {
    $alle[] = (string) $t['id'] === $id ? $theme : $t;
}
Themes::save($alle);

echo "\nGeschrieben.\n";
