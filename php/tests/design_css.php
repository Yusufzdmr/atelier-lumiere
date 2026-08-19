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
