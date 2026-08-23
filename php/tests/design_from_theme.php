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

/* --- Die Bewegung der Karte und der Namen geht mit --- */

$doc = Design::fromTheme([
    'id' => 'x', 'animation' => 'seal', 'nameAnimation' => 'letters',
    'animationSpeed' => 1400, 'animationDelay' => 250,
]);

assert_same('seal', $doc['animation']['card'], 'fromTheme: Karteneinzug geht mit');
assert_same('letters', $doc['animation']['nameMove'], 'fromTheme: Namensbewegung geht mit');
assert_same(1400, $doc['animation']['speed'], 'fromTheme: Tempo geht mit');
assert_same(250, $doc['animation']['delay'], 'fromTheme: Verzoegerung geht mit');

/* --- complete() wirft sie nicht wieder weg --- */

$roh = ['id' => 'y', 'animation' => ['card' => 'curtain', 'nameMove' => 'rise', 'speed' => 900]];
$fertig = Design::complete($roh);

assert_same('curtain', $fertig['animation']['card'], 'complete: card ueberlebt');
assert_same('rise', $fertig['animation']['nameMove'], 'complete: nameMove ueberlebt');
assert_same(900, $fertig['animation']['speed'], 'complete: speed ueberlebt');
assert_same(0, $fertig['animation']['delay'], 'complete: delay hat einen Standard');

/* --- Und beschneidet, statt zu verwerfen --- */

$fertig = Design::complete(['id' => 'z', 'animation' => ['speed' => 999999, 'delay' => -5]]);
assert_same(20000, $fertig['animation']['speed'], 'complete: speed wird beschnitten');
assert_same(0, $fertig['animation']['delay'], 'complete: delay wird unten beschnitten');

/* --- Das echte Élysée traegt seinen Siegelauftakt --- */

if (needs_db()) {
    // bin/test.php hat den Autoloader schon registriert und View.php schon
    // per require geladen (nicht require_once) - src/bootstrap.php wuerde
    // View.php ein zweites Mal einbinden und e() doppelt erklaeren. Deshalb
    // hier nur das eine Stueck aus bootstrap.php nachholen, das wirklich
    // fehlt: die Konfiguration fuer die Datenbankverbindung.
    Atelier\Config::load(dirname(__DIR__) . '/config.php');

    $elysee = Atelier\Themes::find('elysee');
    if ($elysee !== null) {
        $doc = Design::fromTheme($elysee);
        assert_same('seal', $doc['animation']['card'], 'fromTheme: Élysée behaelt seal');
    }
}

/* --- Die Schriften des Themas kommen mit --- */

// Aufgefallen in Faz 2: „aus einem Thema anlegen" ergab ein Dokument ohne eine
// einzige Schriftmarke, weil das Aussaeen sie in Faz 1 von Hand geschrieben
// hatte. Ein Dokument ohne Schriften rendert Text in der Browservoreinstellung.

$mitSchrift = Design::fromTheme([
    'id' => 'sage',
    'fonts' => ['display' => 'cormorant', 'body' => 'jost', 'script' => 'greatvibes'],
]);

assert_same('Cormorant Garamond', $mitSchrift['fonts']['display']['family'], 'fromTheme: Anzeigeschrift kommt mit');
assert_same('Jost', $mitSchrift['fonts']['body']['family'], 'fromTheme: Grundschrift kommt mit');
assert_same('Great Vibes', $mitSchrift['fonts']['script']['family'], 'fromTheme: Schreibschrift kommt mit');
assert_same(300, $mitSchrift['fonts']['display']['weight'], 'fromTheme: gemessenes Gewicht der Anzeigeschrift');
assert_same(106, $mitSchrift['fonts']['script']['lineHeight'], 'fromTheme: gemessene Zeilenhoehe der Schreibschrift');

// Der Kopf der Karte ist quer, nicht hochkant: 9:16 waere die Voreinstellung
// des Formats, aber der alte Motor zeigt 632:490.
assert_same('632:490', $mitSchrift['canvas']['ratio'], 'fromTheme: das gemessene Seitenverhaeltnis');

/* --- Der Oeffnungsfilm des Themas wird EINMAL kopiert --- */

$ausThema = Design::fromTheme([
    'id' => 'tuscany', 'name' => 'Tuscany',
    'introVideo' => '/uploads/themen/tuscany/kuvert.mp4',
    'introPoster' => '/uploads/themen/tuscany/kuvert.jpg',
]);

assert_same('/uploads/themen/tuscany/kuvert.mp4', $ausThema['intro']['video'], 'fromTheme: der Oeffnungsfilm kommt mit');
assert_same('/uploads/themen/tuscany/kuvert.jpg', $ausThema['intro']['poster'], 'fromTheme: sein Standbild auch');

$ohne = Design::fromTheme(['id' => 'schlicht', 'name' => 'Schlicht']);

assert_same('', $ohne['intro']['video'], 'fromTheme: ein Thema ohne Film gibt keinen weiter');
