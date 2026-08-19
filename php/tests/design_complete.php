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
