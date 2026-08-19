<?php
declare(strict_types=1);

use Atelier\Design;

/* --- Standardwerte: kein Weichzeichner, keine Rundung --- */

$el = Design::completeElement(['id' => 'a', 'type' => 'shape']);

assert_same(0, $el['style']['blur'], 'shape: ohne Angabe kein Weichzeichner');
assert_same(0, $el['style']['radius'], 'shape: ohne Angabe keine Rundung');

/* --- Werte werden uebernommen und beschnitten --- */

$el = Design::completeElement(['id' => 'a', 'type' => 'shape', 'style' => ['blur' => 46, 'radius' => 50]]);

assert_same(46, $el['style']['blur'], 'shape: blur bleibt');
assert_same(50, $el['style']['radius'], 'shape: radius bleibt');

$el = Design::completeElement(['id' => 'a', 'style' => ['blur' => 900, 'radius' => 900]]);

assert_same(100, $el['style']['blur'], 'shape: blur wird beschnitten');
assert_same(50, $el['style']['radius'], 'shape: radius wird beschnitten');

$el = Design::completeElement(['id' => 'a', 'style' => ['blur' => -20, 'radius' => -20]]);

assert_same(0, $el['style']['blur'], 'shape: blur wird unten beschnitten');
assert_same(0, $el['style']['radius'], 'shape: radius wird unten beschnitten');

/* --- css() schreibt sie, aber nur fuer shape --- */

$css = Design::css([
    'id'      => 'x',
    'palette' => ['petal' => ['value' => '#E2CFAF']],
    'layers'  => [
        ['id' => 'wash', 'type' => 'shape',
         'box' => ['x' => -16, 'y' => -10, 'w' => 58, 'h' => 58],
         'style' => ['color' => 'petal', 'blur' => 46, 'radius' => 50]],
    ],
], '.d-x');

assert_contains($css, 'filter:blur(46px)', 'css: shape bekommt den Weichzeichner');
assert_contains($css, 'border-radius:50%', 'css: shape bekommt die Rundung');
assert_contains($css, 'background:var(--d-petal)', 'css: shape nimmt seine Farbe als Flaeche');

/* --- Ein Bild bekommt nichts davon --- */

$css = Design::css([
    'id'     => 'y',
    'layers' => [
        ['id' => 'bild', 'type' => 'image', 'src' => '/uploads/a.webp',
         'style' => ['blur' => 46, 'radius' => 50]],
    ],
], '.d-y');

assert_not_contains($css, 'filter:blur', 'css: ein Bild wird nicht weichgezeichnet');
assert_not_contains($css, 'border-radius', 'css: ein Bild wird nicht gerundet');

/* --- blur 0 schreibt keine leere Regel --- */

$css = Design::css([
    'id'     => 'z',
    'layers' => [['id' => 's', 'type' => 'shape', 'style' => ['blur' => 0, 'radius' => 0]]],
], '.d-z');

assert_not_contains($css, 'filter:blur(0px)', 'css: kein Weichzeichner ohne Wert');
assert_not_contains($css, 'border-radius:0', 'css: keine Rundung ohne Wert');

/* --- Ein shape ohne Farbe bekommt keine Flaeche --- */

$css = Design::css([
    'id'     => 'w',
    'layers' => [['id' => 's', 'type' => 'shape', 'style' => ['blur' => 10]]],
], '.d-w');

assert_not_contains($css, 'background:var(--d-)', 'css: shape ohne Farbmarke bekommt keine Flaeche');

/* --- Text waechst mit der Karte, nicht mit der geerbten Groesse --- */

$css = Design::css([
    'id'     => 't',
    'layers' => [['id' => 'namen', 'type' => 'text', 'style' => ['size' => 260]]],
], '.d-t');

assert_contains($css, 'container-type:inline-size', 'css: der Bereich wird zum Bezugsrahmen');
assert_contains($css, 'font-size:26cqw', 'css: Groesse zaehlt in Kartenbreite');
assert_not_contains($css, 'font-size:260%', 'css: kein Prozent der geerbten Groesse mehr');

$css = Design::css(['id' => 'u', 'layers' => [['id' => 'a', 'type' => 'text']]], '.d-u');
assert_contains($css, 'font-size:10cqw', 'css: der Standard 100 wird zu 10cqw');

/* --- Die Schriftmarke bringt mehr mit als ihre Familie --- */

$css = Design::css([
    'id'     => 'typo',
    'fonts'  => ['display' => ['family' => 'Cormorant Garamond', 'weight' => 300,
                              'tracking' => 4, 'lineHeight' => 115]],
    'layers' => [['id' => 'n', 'type' => 'text', 'style' => ['font' => 'display']]],
], '.d-typo');

// Seit Faz 2 stehen die drei Werte als Variable am Bereich und werden in der
// Elementregel gelesen - sonst koennte die Vorschau im Panel sie nicht ohne
// Speichern aendern. Geprueft wird deshalb beides: der Wert oben, der Zugriff
// unten. Gerendert kommt dasselbe heraus.
assert_contains($css, '--dfw-display:300;', 'css: das Gewicht der Marke steht als Variable');
assert_contains($css, '--dft-display:0.04em;', 'css: die Laufweite steht als Variable');
assert_contains($css, '--dfl-display:1.15;', 'css: die Zeilenhoehe steht als Variable');
assert_contains($css, 'font-weight:var(--dfw-display)', 'css: das Gewicht wird gelesen');
assert_contains($css, 'letter-spacing:var(--dft-display)', 'css: die Laufweite wird gelesen');
assert_contains($css, 'line-height:var(--dfl-display)', 'css: die Zeilenhoehe wird gelesen');

// Ohne Marke keine leeren Angaben.
$css = Design::css(['id' => 'ohne', 'layers' => [['id' => 'n', 'type' => 'text']]], '.d-ohne');
assert_not_contains($css, 'font-weight:', 'css: ohne Marke kein Gewicht');
assert_not_contains($css, 'line-height:', 'css: ohne Marke keine Zeilenhoehe');
