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
 *
 * ---------------------------------------------------------------------------
 * ACHTUNG: SO WIE ES HIER STEHT, REICHT ES NICHT. Aufgabe 9 bleibt zu.
 *
 * Einmal durchgelaufen und angesehen (sechzehn Themen, vorher/nachher unter
 * .superpowers/szene-vorher und -nachher). Ergebnis: nach dem Schreiben ist
 * von der Szene NICHTS mehr zu sehen. Drei Gruende, alle gemessen:
 *
 * 1. DER ORT. $scene steht in pages/invitation.php:233 INNERHALB von
 *    .t-envelope-stage - "fixed inset-0 z-50", mit der Hintergrundfarbe des
 *    Themas. $decorations('page') steht in Zeile 221 davor, also DAHINTER.
 *    Eine page-Dekoration liegt hinter dem geschlossenen Kuvert und wird erst
 *    sichtbar, wenn es aufgeht. Themes::SPOTS kennt card, page und envelope -
 *    und 'envelope' ist der kleine Briefumschlag selbst (Zeile 254), nicht
 *    die Buehne. Fuer den Ort der Szene gibt es keinen Spot.
 *
 * 2. DIE BREITE. Die Stilvorlage sagt ".scene-tl{width:38vw;max-width:240px}".
 *    Auf 1280 px sind 38vw = 486 px, der Deckel greift also immer. Eine
 *    Dekoration kennt kein max-width. Mit den rohen 38 % kam das Teil 1099 px
 *    breit heraus - viermal zu gross. Die Breiten unten sind deshalb aus dem
 *    Deckel gerechnet, bezogen auf 1280 px; auf einem breiteren Bildschirm
 *    wachsen sie trotzdem mit, das Original nicht.
 *
 * 3. DIE UNTERKANTE. ".scene-bl{bottom:0}" - eine Dekoration hat nur y von
 *    oben, und .scene spannt die GANZE Seite, deren Hoehe der Inhalt
 *    bestimmt. Unten klebende Teile lassen sich nicht ausrechnen.
 *
 * Was fehlt, ist also kein Zahlendreher, sondern ein Feld und ein Ort. Wer
 * hier weitermacht, entscheidet zuerst, welcher der drei Wege es sein soll -
 * ein vierter Spot fuer die Buehne, ein Ankerfeld an der Dekoration, oder
 * v1 abschalten und die Frage verschwinden lassen.
 * ---------------------------------------------------------------------------
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
 * width in Prozent, dazu rotate.
 *
 * Die Breiten sind NICHT die vw-Werte aus der Stilvorlage, und das ist
 * gemessen und nicht vermutet: .scene-tl heisst "width:38vw;max-width:240px".
 * Auf einem Bildschirm von 1280 px sind 38vw = 486 px, der Deckel greift also
 * immer, und die wahre Breite ist 240 px = 19 %. Mit den 38 % kam das Teil
 * 1099 px breit heraus - viermal zu gross. Eine Dekoration kennt kein
 * max-width, deshalb steht hier der Deckel, umgerechnet auf 1280 px.
 *
 * "unten" heisst: das Teil klebt in der Stilvorlage an der Unterkante
 * ("bottom:0"). Eine Dekoration hat dafuer kein Feld - sie kennt nur y von
 * oben, und .scene spannt die GANZE Seite, deren Hoehe vom Inhalt abhaengt.
 * Solche Teile lassen sich nicht ausrechnen; sie werden gemeldet.
 */
$lage = [
    'scene-tl'     => ['x' =>  0, 'y' =>  0, 'width' => 19, 'unten' => false],
    'scene-tr'     => ['x' => 81, 'y' =>  0, 'width' => 19, 'unten' => false],
    'scene-bl'     => ['x' =>  0, 'y' =>  0, 'width' => 16, 'unten' => true],
    'scene-br'     => ['x' => 84, 'y' =>  0, 'width' => 16, 'unten' => true],
    'scene-left'   => ['x' =>  0, 'y' =>  6, 'width' => 22, 'unten' => false],
    'scene-ml'     => ['x' =>  0, 'y' => 18, 'width' =>  9, 'unten' => false],
    'scene-mr'     => ['x' => 86, 'y' => 30, 'width' => 10, 'unten' => false],
    'scene-top'    => ['x' =>  0, 'y' =>  0, 'width' => 100, 'unten' => false],
    'scene-bottom' => ['x' =>  0, 'y' =>  0, 'width' => 100, 'unten' => true],
    'scene-wide'   => ['x' => 39, 'y' => 27, 'width' => 23, 'unten' => false],
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
            if ($lage[$stueck]['unten']) {
                $auge[] = $stueck . ' (klebt unten - eine Dekoration kann das nicht)';
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
