<?php
declare(strict_types=1);

use Atelier\Design;

$doc = [
    'id'      => 'elysee',
    'palette' => ['accent' => ['value' => '#B08D57'], 'paper' => ['value' => '#FBF6EE']],
    'fonts'   => ['display' => ['family' => 'Cormorant Garamond']],
    'layers'  => [
        ['id' => 'blume', 'type' => 'image', 'src' => '/uploads/blume.webp',
         'box' => ['x' => 10, 'y' => 20, 'w' => 30, 'rotate' => 5, 'opacity' => 80],
         'motion' => ['move' => 'fade', 'delay' => 200, 'duration' => 900]],
        ['id' => 'namen', 'type' => 'text', 'bind' => 'couple_names',
         'box' => ['x' => 0, 'y' => 40, 'w' => 100]],
    ],
];

$css = Design::css($doc, '.d-elysee');

/* --- Palette wird zu Eigenschaften --- */

assert_contains($css, '--d-accent:#B08D57', 'css: Palette wird zur Eigenschaft');
assert_contains($css, '--df-display:', 'css: Schrift wird zur Eigenschaft');

/* --- Jedes Element bekommt seine Regel, in Prozent --- */

assert_contains($css, '.d-elysee .d-el-blume{', 'css: Element bekommt eine Regel');
assert_contains($css, 'left:10%', 'css: x wird zu left in Prozent');
assert_contains($css, 'top:20%', 'css: y wird zu top in Prozent');
assert_contains($css, 'width:30%', 'css: w wird zu width in Prozent');
assert_contains($css, 'rotate(5deg)', 'css: rotate wird uebernommen');
assert_contains($css, 'opacity:0.8', 'css: opacity wird auf 0..1 gebracht');

/* --- Reihenfolge in der Liste ist die Stapelreihenfolge --- */

assert_contains($css, 'z-index:1', 'css: erstes Element liegt hinten');
assert_contains($css, 'z-index:2', 'css: zweites Element liegt davor');

/* --- Nur die benutzten Bewegungen werden mitgeschickt --- */

assert_contains($css, '@keyframes d-move-fade', 'css: benutzte Bewegung wird geschrieben');
assert_not_contains($css, '@keyframes d-move-sway', 'css: unbenutzte Bewegung wird nicht geschrieben');
assert_contains($css, '900ms', 'css: Dauer steht in der Regel');
assert_contains($css, '200ms', 'css: Verzoegerung steht in der Regel');

/* --- Wer Bewegung abbestellt hat, bekommt sie nicht --- */

assert_contains($css, 'prefers-reduced-motion', 'css: reduzierte Bewegung wird beachtet');

/* --- Ohne Bewegung auch kein reduced-motion-Block --- */

$still = Design::css(['id' => 'still', 'layers' => [['id' => 'a', 'src' => '/uploads/a.webp']]], '.d-still');
assert_not_contains($still, 'prefers-reduced-motion', 'css: ohne Bewegung kein Block');
assert_not_contains($still, '@keyframes', 'css: ohne Bewegung keine keyframes');

/* --- Ausbruch aus dem Bereich: eine Farbe darf die Regel nicht schliessen --- */

$boese = Design::css([
    'id'      => 'boese',
    'palette' => ['accent' => ['value' => '#fff} body{display:none} .x{color:red']],
], '.d-boese');

assert_not_contains($boese, 'body{display:none}', 'css: Farbe kann nicht aus der Regel ausbrechen');
assert_contains($boese, '--d-accent:transparent', 'css: unsaubere Farbe wird verworfen');

/* --- Dasselbe fuer Schriftnamen --- */

$boese = Design::css([
    'id'    => 'boese2',
    'fonts' => ['display' => ['family' => 'X} body{display:none} .y{a:b']],
], '.d-boese2');

assert_not_contains($boese, 'body{display:none}', 'css: Schriftname kann nicht ausbrechen');

/* --- rgba() bleibt erlaubt: die bestehenden Themen benutzen es --- */

$ok = Design::css([
    'id'      => 'ok',
    'palette' => ['edge' => ['value' => 'rgba(176,141,87,0.30)']],
], '.d-ok');

assert_contains($ok, '--d-edge:rgba(176,141,87,0.30)', 'css: rgba bleibt erhalten');

/* --- Jede Regel traegt den Bereich vorn --- */

$doc2 = Design::css(['id' => 'x', 'layers' => [['id' => 'a', 'src' => '/uploads/a.webp']]], '.d-x');

// Jedes Vorkommen des Elementwaehlers muss ein „.d-x " davor haben. Zaehlen
// statt suchen: eine einzige Regel ohne Bereich faellt so auf.
assert_same(
    substr_count($doc2, '.d-el-a'),
    substr_count($doc2, '.d-x .d-el-a'),
    'css: keine Regel ohne den Bereich davor'
);

/* --- Spiegeln: das Original kippt Szenenteile mit scale(-1 1) --- */

// Der alte Motor stellt die vier Ecken derselben Zeichnung mit drei
// CSS-Klassen auf: scene-mirror (scale:-1 1), scene-updown (scale:1 -1) und
// scene-flip (rotate:180deg). Eine Drehung um 180 Grad ist etwas anderes als
// eine Spiegelung, deshalb kennt die Kiste beides.

$ohne = Design::css(['id' => 'sp', 'layers' => [
    ['id' => 'a', 'src' => '/uploads/a.webp', 'box' => ['x' => 0, 'y' => 0, 'w' => 10]],
]], '.d-sp');

assert_contains($ohne, 'transform:rotate(0deg);', 'css: ohne Spiegelung bleibt es bei der Drehung');
assert_not_contains($ohne, 'scale(', 'css: ohne Spiegelung wird kein scale geschrieben');

$quer = Design::css(['id' => 'sp', 'layers' => [
    ['id' => 'a', 'src' => '/uploads/a.webp', 'box' => ['x' => 0, 'y' => 0, 'w' => 10, 'flipx' => 1]],
]], '.d-sp');

assert_contains($quer, 'transform:rotate(0deg) scale(-1,1);', 'css: flipx spiegelt an der Senkrechten');

$hoch = Design::css(['id' => 'sp', 'layers' => [
    ['id' => 'a', 'src' => '/uploads/a.webp', 'box' => ['x' => 0, 'y' => 0, 'w' => 10, 'flipy' => 1]],
]], '.d-sp');

assert_contains($hoch, 'transform:rotate(0deg) scale(1,-1);', 'css: flipy spiegelt an der Waagerechten');

// Gedreht UND gespiegelt: die Reihenfolge ist dieselbe wie bei den
// Einzeleigenschaften des Originals - erst drehen, dann spiegeln.
$beides = Design::css(['id' => 'sp', 'layers' => [
    ['id' => 'a', 'src' => '/uploads/a.webp', 'box' => ['x' => 0, 'y' => 0, 'w' => 10, 'rotate' => 180, 'flipx' => 1, 'flipy' => 1]],
]], '.d-sp');

assert_contains($beides, 'transform:rotate(180deg) scale(-1,-1);', 'css: Drehung vor Spiegelung');

// Ein unsinniger Wert darf das Dokument nicht unlesbar machen.
$box = Design::completeBox(['flipx' => 7, 'flipy' => -3]);
assert_same($box['flipx'], 1, 'box: flipx wird auf 1 begrenzt');
assert_same($box['flipy'], 0, 'box: flipy wird auf 0 begrenzt');

/* --- Ankern: das Original klebt Ecken an die Kante, es rechnet nicht --- */

// Im alten Motor steht scene-tr auf „top:0 right:0" und scene-bl auf
// „bottom:0 left:0". Ohne Anker muss die rechte Ecke als 100 minus Breite
// ausgerechnet werden - und verrutscht, sobald sich das Seitenverhaeltnis
// des Kastens aendert. Deshalb kennt die Kiste die vier Ecken.

$sol = Design::css(['id' => 'an', 'layers' => [
    ['id' => 'a', 'src' => '/uploads/a.webp', 'box' => ['x' => 5, 'y' => 7, 'w' => 10]],
]], '.d-an');

assert_contains($sol, 'left:5%;', 'css: ohne Anker misst x von links');
assert_contains($sol, 'top:7%;', 'css: ohne Anker misst y von oben');

$sag = Design::css(['id' => 'an', 'layers' => [
    ['id' => 'a', 'src' => '/uploads/a.webp', 'box' => ['x' => 0, 'y' => 0, 'w' => 10, 'anchor' => 'topright']],
]], '.d-an');

assert_contains($sag, 'right:0%;', 'css: topright misst x von rechts');
assert_not_contains($sag, 'left:', 'css: topright schreibt kein left');

$altSol = Design::css(['id' => 'an', 'layers' => [
    ['id' => 'a', 'src' => '/uploads/a.webp', 'box' => ['x' => 2, 'y' => 3, 'w' => 10, 'anchor' => 'bottomleft']],
]], '.d-an');

assert_contains($altSol, 'left:2%;', 'css: bottomleft misst x von links');
assert_contains($altSol, 'bottom:3%;', 'css: bottomleft misst y von unten');
assert_not_contains($altSol, 'top:', 'css: bottomleft schreibt kein top');

$altSag = Design::css(['id' => 'an', 'layers' => [
    ['id' => 'a', 'src' => '/uploads/a.webp', 'box' => ['x' => 0, 'y' => 0, 'w' => 10, 'anchor' => 'bottomright']],
]], '.d-an');

assert_contains($altSag, 'right:0%;', 'css: bottomright misst x von rechts');
assert_contains($altSag, 'bottom:0%;', 'css: bottomright misst y von unten');

// Ein unbekannter Anker faellt auf die Ecke zurueck, die alles andere auch
// benutzt - ein Dokument mit Tippfehler bleibt lesbar.
assert_same(Design::completeBox(['anchor' => 'mitte'])['anchor'], 'topleft',
    'box: unbekannter Anker faellt auf topleft zurueck');
assert_same(Design::completeBox([])['anchor'], 'topleft', 'box: Anker ist voreingestellt topleft');

/* --- Schriftwerte stehen als Variablen, nicht als feste Zahlen --- */

// Sonst kann die Vorschau im Panel sie nicht ohne Speichern aendern: eine feste
// Zahl in der Elementregel laesst sich nur ueber eine Karte Element->Schriftmarke
// erreichen, und die gibt es im DOM nicht.

$sch = Design::css([
    'id'    => 'sch',
    'fonts' => ['display' => ['family' => 'Cormorant Garamond', 'weight' => 300, 'tracking' => 4, 'lineHeight' => 115]],
    'layers' => [['id' => 'a', 'type' => 'text', 'style' => ['font' => 'display', 'size' => 100]]],
], '.d-sch');

assert_contains($sch, '--dfw-display:300;', 'css: Gewicht steht als Variable');
assert_contains($sch, '--dft-display:0.04em;', 'css: Laufweite steht als Variable');
assert_contains($sch, '--dfl-display:1.15;', 'css: Zeilenhoehe steht als Variable');
assert_contains($sch, 'font-weight:var(--dfw-display);', 'css: die Elementregel liest das Gewicht');
assert_contains($sch, 'letter-spacing:var(--dft-display);', 'css: die Elementregel liest die Laufweite');
assert_contains($sch, 'line-height:var(--dfl-display);', 'css: die Elementregel liest die Zeilenhoehe');
