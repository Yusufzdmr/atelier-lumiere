<?php
declare(strict_types=1);

/**
 * Die gezeichnete Szene eines Themas als eigenstaendige SVG-Dateien.
 *
 *   php bin/export-scene-art.php elysee --dry
 *   php bin/export-scene-art.php elysee
 *
 * Warum ueberhaupt: die Szene ist heute Code. Scenes::html() setzt sie bei
 * jedem Aufruf neu zusammen, mit den Farben des Themas. Ein Design der zweiten
 * Fassung besteht aber aus Daten – also muss die Zeichnung einmal zu einer
 * Datei werden, auf die eine Ebene zeigen kann.
 *
 * Der Preis steht hier, damit ihn niemand spaeter suchen muss: die Farben der
 * Zeichnung frieren dabei ein. Sie folgen der Palette nicht mehr. Fuer den
 * Umzug ist das richtig – die echten Vorlagen kommen ohnehin als fertige
 * Dateien vom Grafiker.
 *
 * Scenes::pieces() ist private und bleibt es. Wir nehmen die oeffentliche
 * Scenes::html() und zerlegen ihre Ausgabe.
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
    exit("Aufruf: php bin/export-scene-art.php <themen-id> [--dry]\n");
}

$theme = Themes::find($id);
if ($theme === null) {
    exit("Thema „{$id}\" nicht gefunden.\n");
}

$scene = (string) ($theme['scene'] ?? '');
$html = Scenes::html($scene, $theme);

if (trim($html) === '') {
    exit("Thema „{$id}\" hat keine gezeichnete Szene (scene = „{$scene}\").\n");
}

$dom = new DOMDocument();
libxml_use_internal_errors(true);

/*
 * loadXML und nicht loadHTML, und das ist kein Geschmack: der HTML-Leser
 * schreibt Attributnamen klein. Aus "viewBox" wird "viewbox", und SVG
 * unterscheidet Gross- und Kleinschreibung - die Datei haette dann keine
 * viewBox mehr, und der Browser zeichnete sie ohne Massstab. Gemessen, nicht
 * vermutet: mit loadHTML liefert getAttribute('viewBox') eine leere
 * Zeichenkette. Dasselbe gilt fuer preserveAspectRatio.
 *
 * Das Fragment ist wohlgeformtes XML - geprueft -, also braucht es nur eine
 * Wurzel darum.
 */
$ok = $dom->loadXML('<wurzel>' . $html . '</wurzel>');

if (!$ok) {
    echo "Die Szene liess sich nicht als XML lesen:\n";
    foreach (array_slice(libxml_get_errors(), 0, 5) as $fehler) {
        echo '  ', trim($fehler->message), "\n";
    }
    exit(1);
}
libxml_clear_errors();

$dir = __DIR__ . '/../public/assets/designs';
if (!$dry && !is_dir($dir) && !mkdir($dir, 0775, true) && !is_dir($dir)) {
    exit("Ordner {$dir} liess sich nicht anlegen.\n");
}

/** Was die Stilvorlage zu den Klassen sagt – Task 8 misst damit die Kaesten. */
$geometrie = [
    'scene-tl'     => 'width:38vw max:240px  top:0    left:0',
    'scene-tr'     => 'width:38vw max:240px  top:0    right:0',
    'scene-bl'     => 'width:32vw max:200px  bottom:0 left:0',
    'scene-br'     => 'width:32vw max:200px  bottom:0 right:0',
    'scene-left'   => 'width:42vw max:280px  top:6%   left:0',
    'scene-ml'     => 'width:20vw max:120px  top:18%  left:0',
    'scene-mr'     => 'width:22vw max:130px  top:30%  right:4%',
    'scene-top'    => 'width:100%            top:0    left:0',
    'scene-bottom' => 'width:100%            bottom:0 left:0',
    'scene-wide'   => 'width:46vw max:290px',
    'scene-flip'   => 'rotate:180deg',
    'scene-mirror' => 'scale:-1 1',
    'scene-updown' => 'scale:1 -1',
];

$svgs = $dom->getElementsByTagName('svg');
$n = 0;
$zeilen = [];

// getElementsByTagName liefert eine lebende Liste – erst einsammeln, dann
// schreiben, sonst verschiebt sich der Index unter den Fuessen.
$knoten = [];
foreach ($svgs as $svg) {
    $knoten[] = $svg;
}

foreach ($knoten as $svg) {
    $n++;
    $klassen = trim($svg->getAttribute('class'));
    $box = trim($svg->getAttribute('viewBox'));
    $stil = trim($svg->getAttribute('style'));

    // Die Datei traegt nur die Zeichnung: viewBox bleibt, Klassen und Stil der
    // Seite gehoeren nicht hinein – die Ebene bringt ihre eigene Geometrie mit.
    $inhalt = '';
    foreach ($svg->childNodes as $kind) {
        $inhalt .= $dom->saveXML($kind);
    }

    $datei = sprintf('%s-%d.svg', $id, $n);
    $pfad = $dir . '/' . $datei;

    // Kein stiller Rueckfall auf einen Standardwert: eine fehlende viewBox
    // heisst, dass das Lesen schiefging, und genau das soll auffallen.
    if ($box === '') {
        exit("Teil {$n} hat keine viewBox - das Lesen ist schiefgegangen.\n");
    }

    $svgDatei = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="' . $box . '">'
        . $inhalt . '</svg>';

    if (!$dry) {
        file_put_contents($pfad, $svgDatei);
    }

    $hinweise = [];
    foreach (explode(' ', $klassen) as $klasse) {
        if (isset($geometrie[$klasse])) {
            $hinweise[] = $klasse . ' → ' . $geometrie[$klasse];
        }
    }

    $zeilen[] = [
        'datei'  => '/assets/designs/' . $datei,
        'klasse' => $klassen,
        'box'    => $box,
        'stil'   => $stil,
        'geo'    => $hinweise === [] ? '(keine Regel gefunden)' : implode(' + ', $hinweise),
        'bytes'  => strlen($svgDatei),
    ];
}

echo "\n", $dry ? 'Probelauf' : 'Geschrieben', ": ", count($zeilen), " Teile\n\n";

foreach ($zeilen as $z) {
    echo $z['datei'], "  (", $z['bytes'], " Bytes)\n";
    echo "  Klasse : ", $z['klasse'], "\n";
    echo "  viewBox: ", $z['box'], "\n";
    echo "  Stil   : ", $z['stil'], "\n";
    echo "  Lage   : ", $z['geo'], "\n\n";
}

echo "Die Farbflecken hinter der Szene stehen NICHT in diesen Dateien –\n";
echo "sie sind shape-Ebenen mit blur und radius (siehe Task 8).\n";
