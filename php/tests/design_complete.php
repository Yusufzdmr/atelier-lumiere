<?php
declare(strict_types=1);

use Atelier\Design;

/* --- Fehlende Felder werden ergaenzt --- */

$doc = Design::complete([]);

assert_same('draft', $doc['status'], 'complete: status faellt auf draft');
assert_same(1, $doc['version'], 'complete: version beginnt bei 1');
assert_same([], $doc['layers'], 'complete: layers ist eine leere Liste');
assert_same([], $doc['sections'], 'complete: sections ist eine leere Liste');
assert_same('9:16', $doc['canvas']['ratio'], 'complete: canvas.ratio hat einen Standard');
assert_same(['de' => '', 'en' => ''], $doc['name'], 'complete: name ist zweisprachig');

/* --- Unbekannte Aufzaehlungswerte fallen auf den Standard --- */

$doc = Design::complete(['status' => 'quatsch', 'category' => 'quatsch']);

assert_same('draft', $doc['status'], 'complete: unbekannter status faellt zurueck');
assert_same('', $doc['category'], 'complete: unbekannte category faellt zurueck');

/* --- Kennung wird zu Kleinbuchstaben und Bindestrich --- */

assert_same('golden-garden', Design::complete(['id' => 'Golden Garden!'])['id'],
    'complete: id wird bereinigt');

/* --- Kasten: Standardwerte --- */

$box = Design::completeBox([]);

// flipx/flipy kamen mit dem zweiten Design dazu: Noirs Szene stellt
// dieselbe Zeichnung gespiegelt in vier Ecken, und Spiegeln ist keine
// Drehung. Voreingestellt ist beides aus.
assert_same(['x' => 4, 'y' => 4, 'w' => 20, 'h' => 0, 'rotate' => 0, 'opacity' => 100,
             'flipx' => 0, 'flipy' => 0, 'anchor' => 'topleft'], $box,
    'completeBox: Standardwerte');

/* --- Kasten: Werte ausserhalb des Bereichs werden beschnitten, nicht verworfen --- */

$box = Design::completeBox(['x' => -900, 'y' => 900, 'w' => 0, 'rotate' => 400, 'opacity' => 250]);

assert_same(-50, $box['x'], 'completeBox: x wird unten beschnitten');
assert_same(150, $box['y'], 'completeBox: y wird oben beschnitten');
assert_same(1, $box['w'], 'completeBox: w hat ein Minimum von 1');
assert_same(180, $box['rotate'], 'completeBox: rotate wird beschnitten');
assert_same(100, $box['opacity'], 'completeBox: opacity wird beschnitten');

/* --- Element: Standardwerte und Rueckfaelle --- */

$el = Design::completeElement(['id' => 'siegel', 'type' => 'quatsch', 'spot' => 'quatsch']);

assert_same('image', $el['type'], 'completeElement: unbekannter type faellt auf image');
assert_same('card', $el['spot'], 'completeElement: unbekannter spot faellt auf card');
assert_same('none', $el['motion']['move'], 'completeElement: Bewegung ist standardmaessig aus');
assert_same(1200, $el['motion']['duration'], 'completeElement: Dauer hat einen Standard');

/* --- Element ohne Kennung bekommt eine --- */

$el = Design::completeElement([]);
assert_true($el['id'] !== '', 'completeElement: leere id wird erzeugt');

/* --- Rechte: alles standardmaessig zu --- */

$el = Design::completeElement(['id' => 'name', 'permissions' => ['color' => true]]);

assert_same(true, $el['permissions']['color'], 'completeElement: gesetztes Recht bleibt');
assert_same(false, $el['permissions']['font'], 'completeElement: ungesetztes Recht ist zu');
assert_same(false, $el['permissions']['hide'], 'completeElement: Design ist zu geboren');

/* --- Unbekannter bind wird NICHT geloescht (warnings() soll ihn noch sehen) --- */

$el = Design::completeElement(['id' => 'x', 'bind' => 'gibt_es_nicht']);
assert_same('gibt_es_nicht', $el['bind'], 'completeElement: unbekannter bind bleibt stehen');

/* --- Aber Sonderzeichen im bind werden entfernt --- */

$el = Design::completeElement(['id' => 'x', 'bind' => 'couple names!']);
assert_same('couple_names', $el['bind'], 'completeElement: bind wird bereinigt');

/* --- layers laufen durch completeElement --- */

$doc = Design::complete(['layers' => [['id' => 'a', 'box' => ['x' => 999]]]]);
assert_same(150, $doc['layers'][0]['box']['x'], 'complete: layers werden mit ergaenzt');

/* --- Palette und Schriften --- */

$doc = Design::complete(['palette' => ['accent' => ['value' => '#B08D57']]]);

assert_same('#B08D57', $doc['palette']['accent']['value'], 'complete: Palettenwert bleibt');
assert_same(false, $doc['palette']['accent']['customer'], 'complete: Palette ist standardmaessig gesperrt');

$doc = Design::complete(['fonts' => ['display' => ['family' => 'Cormorant Garamond']]]);

assert_same('Cormorant Garamond', $doc['fonts']['display']['family'], 'complete: Schriftfamilie bleibt');
assert_same(false, $doc['fonts']['display']['customer'], 'complete: Schrift ist standardmaessig gesperrt');

/* --- Video: poster ist ein eigenes Feld und geht durch dieselbe Pruefung --- */

$doc = Design::complete(['id' => 'x', 'layers' => [
    ['id' => 'film', 'type' => 'video', 'src' => '/uploads/film.mp4', 'poster' => '/uploads/film.jpg'],
    ['id' => 'fremd', 'type' => 'video', 'src' => '/uploads/a.mp4', 'poster' => 'https://beispiel.de/x.jpg'],
    ['id' => 'ohne', 'type' => 'video', 'src' => '/uploads/b.mp4'],
]]);

assert_same('video', $doc['layers'][0]['type'], 'complete: video bleibt video');
assert_same('/uploads/film.jpg', $doc['layers'][0]['poster'], 'complete: eigener Poster kommt durch');
assert_same('', $doc['layers'][1]['poster'], 'complete: fremder Host wird zu leer');
assert_same('', $doc['layers'][2]['poster'], 'complete: ohne Angabe ist der Poster leer');

/* --- Der Oeffnungsfilm gehoert dem Dokument, nicht dem lebenden Thema --- */

$mitIntro = Design::complete(['id' => 'x', 'intro' => [
    'video' => '/uploads/themen/a/intro.mp4', 'poster' => '/uploads/themen/a/intro.jpg',
]]);

assert_same('/uploads/themen/a/intro.mp4', $mitIntro['intro']['video'], 'complete: der Oeffnungsfilm bleibt');
assert_same('/uploads/themen/a/intro.jpg', $mitIntro['intro']['poster'], 'complete: sein Standbild auch');

$ohneIntro = Design::complete(['id' => 'x']);

assert_same('', $ohneIntro['intro']['video'], 'complete: ohne Angabe ist der Vorspann leer');
assert_same('', $ohneIntro['intro']['poster'], 'complete: und sein Standbild ebenso');

$fremdIntro = Design::complete(['id' => 'x', 'intro' => [
    'video' => 'https://beispiel.de/i.mp4', 'poster' => 'https://beispiel.de/i.jpg',
]]);

assert_same('', $fremdIntro['intro']['video'], 'complete: fremder Host wird verworfen');
assert_same('', $fremdIntro['intro']['poster'], 'complete: auch beim Standbild');

/* --- Das Seitenverhaeltnis geht in ein style-Attribut: zwei Zahlen, sonst nichts --- */

$boese = Design::complete(['id' => 'x', 'canvas' => ['ratio' => '1:1" onload="alert(1)']]);
assert_same('9:16', $boese['canvas']['ratio'], 'complete: ein Ausbruch aus dem Attribut faellt auf den Standard');

$echt = Design::complete(['id' => 'x', 'canvas' => ['ratio' => '768:1376']]);
assert_same('768:1376', $echt['canvas']['ratio'], 'complete: eine echte Angabe bleibt');

/* --- Der Grund unter den Abschnitten: leer heisst "wie die Karte" --- */

$g = Design::complete(['id' => 'x', 'sectionsBg' => '/uploads/designs/grund.jpg']);
assert_same('/uploads/designs/grund.jpg', $g['sectionsBg'], 'complete: eigener Grund kommt durch');

$gf = Design::complete(['id' => 'x', 'sectionsBg' => 'https://beispiel.de/g.jpg']);
assert_same('', $gf['sectionsBg'], 'complete: fremder Host wird verworfen');

assert_same('', Design::complete(['id' => 'x'])['sectionsBg'], 'complete: ohne Angabe leer - also wie die Karte');

/*
 * ------------------------------------------------------------------
 * Wie lange der Oeffnungsfilm laeuft.
 * ------------------------------------------------------------------
 *
 * Bis hierher stand in der zweiten Fassung eine Null: introMs => 0. Das
 * Skript liest sie als "keine Angabe" und nimmt dann die Laenge des Films
 * selbst, gedeckelt bei sechs Sekunden. Wer einen Film von zwoelf Sekunden
 * hinlegt, bekommt also sechs - und kann daran nichts aendern.
 *
 * In Sekunden und nicht in Millisekunden: der Grafiker denkt in Sekunden,
 * das Feld steht in Sekunden, und gerechnet wird genau einmal - dort, wo die
 * Buehne das Attribut schreibt.
 */

$film = Design::complete(['id' => 'f', 'slug' => 'f', 'intro' => [
    'video' => '/assets/vorlagen/film.mp4', 'seconds' => 3.5,
]]);

assert_same(3.5, $film['intro']['seconds'], 'intro: die Sekunden bleiben');

// Null heisst weiterhin "so lang wie der Film". Das ist die Voreinstellung,
// und sie ist genau das bisherige Verhalten - eine Vorlage, die nie etwas
// eingetragen hat, aendert sich nicht.
$ohne = Design::complete(['id' => 'f2', 'slug' => 'f2']);
assert_same(0.0, $ohne['intro']['seconds'], 'intro: ohne Angabe null');

// Geklemmt, nicht abgelehnt: eine Einladung soll nicht an einer Zahl scheitern.
$viel = Design::complete(['id' => 'f3', 'slug' => 'f3', 'intro' => ['seconds' => 900]]);
assert_same(20.0, $viel['intro']['seconds'], 'intro: zu lang wird geklemmt');

$minus = Design::complete(['id' => 'f4', 'slug' => 'f4', 'intro' => ['seconds' => -3]]);
assert_same(0.0, $minus['intro']['seconds'], 'intro: negativ wird null');

$quatsch = Design::complete(['id' => 'f5', 'slug' => 'f5', 'intro' => ['seconds' => 'bald']]);
assert_same(0.0, $quatsch['intro']['seconds'], 'intro: Unsinn wird null');

/* --- Und aus dem Formular --- */

$ausFormular = Design::fromPost($film, ['intro_sekunden' => '2.4']);
assert_same(2.4, $ausFormular['intro']['seconds'], 'fromPost: die Sekunden kommen an');

// Leer abschicken heisst "wieder so lang wie der Film" - dieselbe Haltung wie
// beim Filmpfad selbst, den man auch leeren darf.
$geleert = Design::fromPost($film, ['intro_sekunden' => '']);
assert_same(0.0, $geleert['intro']['seconds'], 'fromPost: leer heisst so lang wie der Film');
