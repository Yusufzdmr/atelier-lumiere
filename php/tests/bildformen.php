<?php

declare(strict_types=1);

use Atelier\DesignSections;
use Atelier\SectionRegistry;

/*
 * Die Form der Fotos.
 *
 * "Fotograflar her zaman normal dikdortgen resim olarak gosterilmemeli …
 * Polaroid / Gold frame / Kagit fotograf / Oval / Yuvarlak / Cercevesiz /
 * Ozel PNG frame. Musteri fotografini yukler, nasil gosterilecegini secilen
 * davetiye tasarimi belirler."
 *
 * Der letzte Satz ist die eigentliche Ansage und der Grund, warum das eine
 * EINSTELLUNG des Grafikers ist und kein Feld im Assistenten: wer seine
 * Bilder einzeln in Rahmen steckt, baut keine Einladung mehr, sondern eine
 * Collage.
 */

function bf_doc(array $settings): array
{
    return DesignSections::complete([
        'id' => 'probe', 'slug' => 'probe',
        'palette' => ['accent' => ['value' => '#B08D57'], 'paper' => ['value' => '#FAF7F2']],
        'fonts'   => ['display' => ['family' => 'Cormorant']],
        // Die Kennung heisst bewusst NICHT "bilder": der Kasten der Galerie
        // heisst .d-sec-bilder, und eine gleichnamige Kennung erzeugte
        // denselben Selektor. Ein Test, der dann gruen ist, misst den
        // Zufall und nicht die Sache.
        'sections' => [['id' => 'fotos', 'type' => 'gallery', 'settings' => $settings]],
    ]);
}

$fotos = ['sections' => ['fotos' => ['photos' => [
    '/uploads/einladungen/v2/a/1.jpg',
    '/uploads/einladungen/v2/a/2.jpg',
]]]];

/* --- Der Katalog --- */

$einstellungen = SectionRegistry::settings('gallery');

assert_true(isset($einstellungen['photoFrame']), 'Bildform: sie steht im Katalog');
assert_same(
    ['keine', 'polaroid', 'gold', 'papier', 'oval', 'rund', 'eigen'],
    $einstellungen['photoFrame']['options'],
    'Bildform: die sieben aus der Anfrage'
);
assert_same('keine', $einstellungen['photoFrame']['default'],
    'Bildform: voreingestellt bleibt das Bild, wie es ist');

/*
 * "photoFrame" und nicht "frame": der Schluessel ist vergeben - das ist der
 * Rahmen um den TEXT eines Abschnitts, und den hat jede Art. Zwei Rahmen mit
 * einem Namen waeren zwei Antworten auf dieselbe Frage, und die zweite
 * ueberschriebe die erste.
 */
assert_true(isset($einstellungen['frame']), 'Bildform: der Textrahmen bleibt daneben bestehen');
assert_true($einstellungen['frame']['options'] !== $einstellungen['photoFrame']['options'],
    'Bildform: zwei getrennte Listen fuer zwei getrennte Fragen');

/* --- Jedes Bild bekommt seinen Kasten, immer --- */

$ohne = bf_doc([]);
$ohneHtml = DesignSections::html($ohne, $fotos, 'de', '2026-01-01');

assert_same(2, substr_count($ohneHtml, '<span class="d-bild">'), 'Bildform: ein Kasten je Foto');
assert_contains($ohneHtml, '<span class="d-bild"><img src="/uploads/einladungen/v2/a/1.jpg"',
    'Bildform: das Bild steht darin');

/*
 * Der Kasten steht auch ohne gewaehlte Form da. Ein Knoten, den es nur
 * manchmal gibt, waere ein zweiter Bauplan - und er kostet nichts: eine
 * Regel, position:relative.
 *
 * Er ist die Voraussetzung fuer zwei der sieben Formen: das Polaroid
 * braucht einen Rand mit breiterem Fuss, und die eigene Zeichnung legt sich
 * als ::after darueber. Beides geht am <img> selbst nicht - ein img hat kein
 * ::after.
 */
assert_contains(DesignSections::css($ohne, '.d-p'), '.d-p .d-sec-bilder .d-bild{display:block;position:relative;}',
    'Bildform: der Kasten traegt seine eine Grundregel');

/*
 * Und die gewaehlte Form haengt an einer KLASSE des Abschnitts.
 *
 * Im Browser gemessen und dabei gefunden: ohne sie trugen die Regeln nur
 * ".d-sec-bilder .d-bild", und damit bekam jede Galerie des Dokuments jede
 * gewaehlte Form. Vier Galerien mit vier Formen sahen alle vier gleich aus.
 */
$zwei = DesignSections::complete([
    'id' => 'probe', 'slug' => 'probe',
    'palette' => ['accent' => ['value' => '#B08D57'], 'paper' => ['value' => '#FAF7F2']],
    'sections' => [
        ['id' => 'oben', 'type' => 'gallery', 'settings' => ['photoFrame' => 'polaroid']],
        ['id' => 'unten', 'type' => 'gallery', 'settings' => ['photoFrame' => 'rund']],
    ],
]);
$zweiHtml = DesignSections::html($zwei, ['sections' => [
    'oben'  => ['photos' => ['/uploads/einladungen/v2/a/1.jpg']],
    'unten' => ['photos' => ['/uploads/einladungen/v2/a/2.jpg']],
]], 'de', '2026-01-01');
$zweiCss = DesignSections::css($zwei, '.d-p');

assert_contains($zweiHtml, 'd-sec-pf-polaroid', 'Bildform: die Klasse steht am Abschnitt');
assert_contains($zweiHtml, 'd-sec-pf-rund', 'Bildform: und die des zweiten auch');
assert_contains($zweiCss, '.d-p .d-sec-pf-polaroid .d-sec-bilder .d-bild{',
    'Bildform: die Regel gilt nur fuer den Abschnitt, der sie gewaehlt hat');
assert_contains($zweiCss, '.d-p .d-sec-pf-rund .d-sec-bilder .d-bild img{',
    'Bildform: und die zweite nur fuer ihren');

// Ohne gewaehlte Form auch kein toter Stilblock.
foreach (['polaroid', 'oval', 'rund'] as $art) {
    assert_true(!str_contains(DesignSections::css($ohne, '.d-p'), '.d-bild{background'),
        'Bildform: ohne Wahl kein Block fuer ' . $art);
}

/* --- Jede Form bringt ihren Stil mit --- */

/*
 * Eine Form anzubieten, die aussieht wie keine, waere ein Versprechen, das
 * die Vorlage nicht haelt - der Grafiker waehlt sie einmal, sieht keinen
 * Unterschied und traut dem Katalog danach nicht mehr.
 */
foreach (['polaroid', 'gold', 'papier', 'oval', 'rund', 'eigen'] as $art) {
    $css = DesignSections::css(bf_doc(['photoFrame' => $art]), '.d-p');
    assert_contains($css, '.d-p .d-sec-pf-' . $art . ' .d-sec-bilder .d-bild',
        'Bildform: ' . $art . ' bringt einen Stilblock mit');
}

/* --- Das Polaroid --- */

$polaroid = DesignSections::css(bf_doc(['photoFrame' => 'polaroid']), '.d-p');

// Der breitere Fuss ist der ganze Punkt: ohne ihn ist es ein Bild mit
// weissem Rand und kein Polaroid.
assert_contains($polaroid, 'padding:0.7rem 0.7rem 2.4rem;', 'Polaroid: unten breiter als oben');
assert_contains($polaroid, 'transform:rotate(-1.4deg);', 'Polaroid: leicht schief');

/*
 * Und abwechselnd in die andere Richtung. Alle gleich schief saehen aus wie
 * ein Fehler im Raster. nth-child und kein Zufall - ein zufaelliger Winkel
 * spraenge bei jedem Neuladen woanders hin.
 */
assert_contains($polaroid, ':nth-child(even){transform:rotate(1.6deg);}',
    'Polaroid: jedes zweite andersherum');

/* --- Oval und rund gehoeren dem BILD --- */

/*
 * Nicht dem Kasten: ein runder Kasten mit einem eckigen Bild darin waere ein
 * Quadrat mit runden Ecken davor.
 */
foreach (['oval' => '3/4', 'rund' => '1'] as $art => $verhaeltnis) {
    $css = DesignSections::css(bf_doc(['photoFrame' => $art]), '.d-p');
    assert_contains($css, '.d-p .d-sec-pf-' . $art . ' .d-sec-bilder .d-bild img{border-radius:',
        $art . ': die Form gehoert dem Bild');
    assert_contains($css, 'aspect-ratio:' . $verhaeltnis . ';', $art . ': mit seinem Seitenverhaeltnis');
}

/* --- Die eigene Zeichnung liegt DARUEBER --- */

$eigen = bf_doc(['photoFrame' => 'eigen', 'photoFrameSrc' => '/uploads/designs/rahmen.png']);
$eigenCss = DesignSections::css($eigen, '.d-p');

/*
 * ::after und kein Hintergrund. Eine Rahmen-PNG hat eine durchsichtige
 * Mitte; hinter dem Foto waere davon nichts zu sehen - und genau das ist der
 * Fall, den der Kunde beschrieben hat ("ozel PNG frame").
 */
assert_contains($eigenCss, '.d-p .d-sec-pf-eigen .d-sec-bilder .d-bild::after{content:"";position:absolute;inset:0;',
    'Eigen: die Zeichnung liegt als Auflage ueber dem Bild');
assert_contains($eigenCss, 'background-image:var(--d-bild-frame,none);', 'Eigen: sie kommt aus der Variablen');
assert_contains($eigenCss, 'pointer-events:none;', 'Eigen: und schluckt keinen Fingertipp');

// Die Datei haengt am ABSCHNITT: die Regel gilt fuer alle mit dieser Form,
// die Zeichnung gehoert einem.
assert_contains($eigenCss, ".d-p .d-sec-fotos{--d-bild-frame:url('/uploads/designs/rahmen.png');}",
    'Eigen: die Zeichnung haengt am Abschnitt');

// Eine fremde Adresse faellt weg - dieselbe Pruefung wie bei jedem Bild.
$fremd = bf_doc(['photoFrame' => 'eigen', 'photoFrameSrc' => 'https://fremd.example/r.png']);
assert_true(!str_contains(DesignSections::css($fremd, '.d-p'), 'fremd.example'),
    'Eigen: eine fremde Adresse kommt nicht durch');

/* --- Der Streifen misst am Kasten, nicht mehr am Bild --- */

/*
 * Seit jedes Foto seinen eigenen Kasten hat, ist DER das Flex-Element. Am
 * img gemessen waere jeder Streifen so breit wie sein Inhalt, und die
 * Schnappkante saesse am falschen Knoten.
 */
$streifen = DesignSections::css(
    DesignSections::complete([
        'id' => 'probe', 'slug' => 'probe',
        'sections' => [['id' => 'fotos', 'type' => 'gallery', 'variant' => 'streifen']],
    ]),
    '.d-p'
);
assert_contains($streifen, '.d-sec-bilder .d-bild{flex:0 0 68%;scroll-snap-align:center;}',
    'Streifen: die Breite sitzt am Kasten');

/* --- Und in "gift" kommen weiterhin keine Bilder --- */

/*
 * Ausdruecklich geprueft, weil genau das einmal missverstanden wurde: der
 * Kunde hat ein Bild in "gift" hochgeladen und gewartet. Das ist die Art mit
 * der Kontonummer. Ein zweiter Bilderplatz daneben wuerde die Verwechslung
 * neu bauen, die das Umbenennen gerade beseitigt hat.
 */
assert_true(!isset(SectionRegistry::inputs('gift')['photos']),
    'Geschenk: es nimmt weiterhin keine Bilder - dafuer gibt es "Fotos"');
assert_true(isset(SectionRegistry::inputs('gallery')['photos']),
    'Fotos: dort gehoeren sie hin');
