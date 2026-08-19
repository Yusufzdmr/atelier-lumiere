<?php
declare(strict_types=1);

use Atelier\Design;

$thema = [
    'id'            => 'elysee',
    'name'          => 'Élysée',
    'bg'            => '#EFE7DC',
    'paper'         => '#FBF6EE',
    'accent'        => '#B08D57',
    'seal'          => '#B08D57',
    'image'         => '/uploads/hintergrund.webp',
    'imageOpacity'  => '60',
    'envelopeImage' => '/uploads/kuvert.webp',
    'decorations'   => [
        ['id' => 'blumeli', 'label' => 'Blume links', 'src' => '/uploads/blume-l.webp',
         'spot' => 'card', 'x' => '2', 'y' => '70', 'width' => '28', 'rotate' => '-6',
         'opacity' => '90', 'front' => false, 'move' => 'rise', 'delay' => '300', 'duration' => '900'],
        ['id' => 'siegel', 'label' => 'Siegel', 'src' => '/uploads/siegel.webp',
         'spot' => 'envelope', 'x' => '40', 'y' => '45', 'width' => '20', 'rotate' => '0',
         'opacity' => '100', 'front' => true, 'move' => 'zoom', 'delay' => '0', 'duration' => '600'],
    ],
];

$doc = Design::fromTheme($thema);

/* --- Kopfdaten --- */

assert_same('elysee', $doc['id'], 'fromTheme: id wird uebernommen');
assert_same('Élysée', $doc['name']['de'], 'fromTheme: Name wird uebernommen');

/* --- Farben werden zu Marken, gesperrt --- */

assert_same('#B08D57', $doc['palette']['accent']['value'], 'fromTheme: accent wird zur Marke');
assert_same('#FBF6EE', $doc['palette']['paper']['value'], 'fromTheme: paper wird zur Marke');
assert_same(false, $doc['palette']['accent']['customer'], 'fromTheme: Marken sind zunaechst gesperrt');

/* --- Hintergrund und Kuvert werden Elemente --- */

$nachId = [];
foreach ($doc['layers'] as $i => $el) {
    $nachId[$el['id']] = ['index' => $i] + $el;
}

assert_true(isset($nachId['bgimage']), 'fromTheme: Hintergrundbild wird ein Element');
assert_same('page', $nachId['bgimage']['spot'], 'fromTheme: Hintergrund liegt auf der Seite');
assert_same(60, $nachId['bgimage']['box']['opacity'], 'fromTheme: Deckkraft des Hintergrunds');

assert_true(isset($nachId['envimage']), 'fromTheme: Kuvertbild wird ein Element');
assert_same('envelope', $nachId['envimage']['spot'], 'fromTheme: Kuvertbild liegt auf dem Kuvert');

/* --- Schmuck wird verlustfrei uebernommen --- */

$blume = $nachId['blumeli'];

assert_same('image', $blume['type'], 'fromTheme: Schmuck wird ein Bildelement');
assert_same('card', $blume['spot'], 'fromTheme: spot bleibt');
assert_same('/uploads/blume-l.webp', $blume['src'], 'fromTheme: Quelle bleibt');
assert_same(2, $blume['box']['x'], 'fromTheme: x bleibt');
assert_same(70, $blume['box']['y'], 'fromTheme: y bleibt');
assert_same(28, $blume['box']['w'], 'fromTheme: width wird zu w');
assert_same(-6, $blume['box']['rotate'], 'fromTheme: rotate bleibt');
assert_same(90, $blume['box']['opacity'], 'fromTheme: opacity bleibt');
assert_same('rise', $blume['motion']['move'], 'fromTheme: Bewegung bleibt');
assert_same(300, $blume['motion']['delay'], 'fromTheme: Verzoegerung bleibt');
assert_same(900, $blume['motion']['duration'], 'fromTheme: Dauer bleibt');
assert_same('Blume links', $blume['label'], 'fromTheme: Beschriftung bleibt');

/* --- front:true landet HINTER front:false in der Liste, also weiter oben --- */

assert_true($nachId['siegel']['index'] > $nachId['blumeli']['index'],
    'fromTheme: front:true liegt spaeter in der Liste');
assert_true($nachId['bgimage']['index'] < $nachId['blumeli']['index'],
    'fromTheme: der Hintergrund liegt ganz hinten');

/* --- Schmuck ohne Quelle wird uebersprungen --- */

$doc = Design::fromTheme(['id' => 'x', 'decorations' => [['id' => 'leer', 'src' => '']]]);
assert_same([], $doc['layers'], 'fromTheme: Schmuck ohne Bild wird uebersprungen');

/* --- Der Animationsblock wird eins zu eins uebernommen --- */

$doc = Design::fromTheme([
    'id' => 'x', 'intro' => 'darkroom', 'idle' => 'breathe',
    'reveal' => 'mask', 'particle' => 'petal',
]);

assert_same('darkroom', $doc['animation']['intro'], 'fromTheme: intro bleibt');
assert_same('breathe', $doc['animation']['idle'], 'fromTheme: idle bleibt');
assert_same('mask', $doc['animation']['reveal'], 'fromTheme: reveal bleibt');
assert_same('petal', $doc['animation']['particle'], 'fromTheme: particle bleibt');

/* --- Das Ergebnis ist ein fertiges Dokument --- */

$doc = Design::fromTheme(['id' => 'x']);
assert_same('draft', $doc['status'], 'fromTheme: Ergebnis ist vollstaendig');
assert_same([], $doc['sections'], 'fromTheme: sections bleibt leer (Faz 3)');
