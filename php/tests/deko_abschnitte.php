<?php

declare(strict_types=1);

use Atelier\Design;
use Atelier\DesignSections;
use Atelier\Themes;

/*
 * Schmuck im Abschnittsbereich - der vierte Ort.
 *
 * "Cicek, cerceve, muhur, cizgi, dantel, kagit kenari, ornament … bunlarin
 * pozisyonu, boyutu ve layer/z-index'i ayarlanabilmeli. Resim kullanilabilen
 * uygun alanlarda video da kullanilabilmeli."
 *
 * Bis hierher endete der Schmuck an der Unterkante der Karte: es gab card,
 * page und envelope, und unter der Karte lief die Einladung nackt weiter -
 * genau der Teil, den der Gast am laengsten sieht.
 */

function deko_doc(array $layers): array
{
    return DesignSections::complete([
        'id' => 'probe', 'slug' => 'probe',
        'palette' => ['accent' => ['value' => '#B08D57'], 'paper' => ['value' => '#FAF7F2']],
        'fonts'   => ['display' => ['family' => 'Cormorant']],
        'layers'  => $layers,
        'sections' => [['id' => 'txt-1', 'type' => 'text']],
    ]);
}

$daten = ['sections' => ['txt-1' => ['text' => 'Wir freuen uns.']]];

/* --- Der Ort steht im Katalog --- */

assert_true(isset(Themes::SPOTS['sections']), 'Deko: es gibt einen vierten Ort');
assert_same(['card', 'page', 'envelope', 'sections'], array_keys(Themes::SPOTS),
    'Deko: und die drei alten sind unveraendert');

/* --- Ohne Schmuck kein Kasten --- */

$ohne = deko_doc([]);
$ohneHtml = DesignSections::flaeche($ohne, 'd-p', DesignSections::html($ohne, $daten, 'de'));

assert_contains($ohneHtml, 'd-sec-flaeche', 'Deko: die Flaeche steht da');
assert_true(!str_contains($ohneHtml, 'd-sec-deko'),
    'Deko: ohne Ebene kein leerer Kasten ueber der ganzen Einladung');

/* --- Mit Schmuck: eigener Kasten, hinter dem Text --- */

$mit = deko_doc([
    ['id' => 'ranke', 'type' => 'image', 'spot' => 'sections',
     'src' => '/uploads/designs/ranke.png',
     'box' => ['x' => 4, 'y' => 12, 'w' => 30, 'anchor' => 'topleft']],
    ['id' => 'film', 'type' => 'video', 'spot' => 'sections',
     'src' => '/uploads/designs/schleier.webm',
     'box' => ['x' => 0, 'y' => 0, 'w' => 100, 'h' => 60, 'anchor' => 'bottomleft']],
    // Eine Ebene der Karte darf hier NICHT auftauchen.
    ['id' => 'aufkarte', 'type' => 'image', 'spot' => 'card', 'src' => '/uploads/designs/ecke.png'],
]);
$mitHtml = DesignSections::flaeche($mit, 'd-p', DesignSections::html($mit, $daten, 'de'));

assert_contains($mitHtml, '<div class="d-sec-deko"', 'Deko: der Schmuck bekommt seinen Kasten');
assert_contains($mitHtml, 'ranke.png', 'Deko: die Zeichnung steht darin');
assert_contains($mitHtml, 'schleier.webm', 'Deko: und der Film auch');
/*
 * Was auf die Karte gehoert, bleibt auf der Karte - im SCHMUCKKASTEN.
 *
 * Nicht im ganzen Ausgabetext: die hinterste Bildebene der Karte ist seit
 * jeher der Ersatz fuer das Blatt des Bereichs, wenn die Vorlage keines
 * eigenes hinterlegt hat ("leer = wie die Karte"). Sie steht also zu Recht
 * noch einmal da, nur als --d-sec-blatt und nicht als Ebene.
 */
$kasten = strstr($mitHtml, '<div class="d-sec-deko"');
$kasten = is_string($kasten) ? (string) strstr($kasten, '<div class="d-sections', true) : '';

assert_true($kasten !== '', 'Deko: der Kasten laesst sich aus der Ausgabe schneiden');
assert_true(!str_contains($kasten, 'ecke.png'),
    'Deko: was auf die Karte gehoert, steht nicht im Schmuckkasten');
assert_contains($mitHtml, "--d-sec-blatt:url('/uploads/designs/ecke.png')",
    'Deko: als Blatt des Bereichs darf sie weiterhin einspringen');

/*
 * Der Kasten ist fuer die Vorlesesoftware nicht vorhanden. Schmuck ist
 * Schmuck; achtmal "Bild" vorgelesen zu bekommen, bevor die Adresse der
 * Feier kommt, ist keine Auskunft.
 */
assert_contains($mitHtml, 'aria-hidden="true"', 'Deko: der Kasten ist Schmuck, kein Inhalt');

// Und der Text steht danach - die Reihenfolge im Markup ist Teil der
// Stapelung, so wie beim Kuvert auf der Buehne.
assert_true(
    strpos($mitHtml, 'd-sec-deko') < strpos($mitHtml, 'd-sections'),
    'Deko: der Schmuck steht vor dem Text im Markup'
);

/* --- Senkrecht misst gegen die BREITE, nicht gegen die Hoehe --- */

/*
 * Der eigentliche Grund, warum das nicht einfach der vierte Wert in einer
 * Liste ist.
 *
 * Prozent im top messen gegen die HOEHE des Kastens. Auf der Karte ist die
 * bekannt - sie hat ein festes Seitenverhaeltnis. Der Abschnittsbereich ist
 * so hoch, wie das Paar geschrieben hat: dieselben 12 % waeren bei einer
 * kurzen Einladung 70 px und bei einer langen 700. Der Grafiker stellte eine
 * Ranke ein und faende sie auf jeder zweiten Einladung woanders.
 *
 * cqw ist ein Hundertstel der Breite. Damit bedeutet die Zahl ueberall
 * dasselbe - und es ist dieselbe Einheit, in der die Abschnitte ohnehin
 * rechnen (ihr Polster steht in Prozent, und Prozent im padding beziehen
 * sich auf die Breite).
 */
$css = Design::css($mit, '.d-p');

assert_contains($css, '.d-p .d-el-ranke{position:absolute;left:4%;top:12cqw;width:30%;',
    'Deko: senkrecht in cqw, waagerecht in Prozent');
assert_contains($css, 'bottom:0cqw;', 'Deko: der Anker gilt weiter');
assert_contains($css, 'height:60cqw;', 'Deko: eine gesetzte Hoehe misst auch gegen die Breite');

// Auf der Karte bleibt alles, wie es war - dort ist die Hoehe bekannt.
// Prozent und nicht cqw - dort ist die Hoehe bekannt. (4 % ist die
// Voreinstellung der Box, nicht ein gesetzter Wert.)
assert_contains($css, '.d-p .d-el-aufkarte{position:absolute;left:4%;top:4%;',
    'Deko: die Karte rechnet unveraendert in Prozent');
assert_true(!str_contains(
    (string) strstr((string) strstr($css, '.d-p .d-el-aufkarte{'), '}', true),
    'cqw'
), 'Deko: auf der Karte kommt kein cqw vor');

/* --- Der Kasten selbst --- */

$grund = DesignSections::css($mit, '.d-p');

assert_contains($grund, '.d-p .d-sec-deko{position:absolute;inset:0;z-index:0;',
    'Deko: der Kasten liegt ueber der ganzen Flaeche und unter dem Text');
assert_contains($grund, 'overflow:hidden;',
    'Deko: was ueber die Kante haengt, macht die Seite nicht breiter');
assert_contains($grund, 'pointer-events:none;',
    'Deko: eine durchsichtige Ecke schluckt keinen Fingertipp');
assert_contains($grund, 'container-type:inline-size;',
    'Deko: der Bezug fuer die cqw-Angaben');
assert_contains($grund, '.d-p .d-sections{position:relative;z-index:1;}',
    'Deko: der Text liegt darueber');

/*
 * container-type steht am KASTEN und nicht an der Flaeche - und das ist
 * kein Geschmack.
 *
 * container-type macht einen Kasten zum Bezugspunkt fuer position:fixed.
 * Der Stummschalter der Hintergrundmusik ist fixed und sitzt im
 * Abschnittsbereich (baseline: .d-sec-ton-knopf). Stuende die Angabe an der
 * Flaeche, klebte er am Abschnittsbereich statt am Fensterrand - und waere
 * genau dann weg, wenn jemand ihn sucht, naemlich mitten in der Einladung.
 */
$flaecheRegel = substr($grund, (int) strpos($grund, '.d-p.d-sec-flaeche{'));
$flaecheRegel = substr($flaecheRegel, 0, (int) strpos($flaecheRegel, '}'));
assert_true(!str_contains($flaecheRegel, 'container-type'),
    'Deko: die Flaeche selbst bekommt kein container-type - der Tonknopf ist fixed');
assert_contains($flaecheRegel, 'position:relative;',
    'Deko: sie ist aber der Bezug fuer den Kasten darin');

/* --- Durchsichtige Filme --- */

/*
 * "Transparent video destegi de duzgun calismali. Ortasi transparan olan bir
 * video yukledigimde transparan alan kaybolmamali."
 *
 * Dafuer war nichts zu bauen, und das ist die Antwort: Media::storeVideo
 * legt eine WebM ab, wie sie kommt - es wird nicht umkodiert, also
 * ueberlebt der Alphakanal. Und die Ebene bekommt keinen Grund: haette
 * .d-el eine Hintergrundfarbe, waere die Mitte des Films zugemalt.
 *
 * Geprueft wird, dass das so bleibt.
 */
$filmRegel = substr($css, (int) strpos($css, '.d-p .d-el-film{'));
$filmRegel = substr($filmRegel, 0, (int) strpos($filmRegel, '}'));

assert_true(!str_contains($filmRegel, 'background'),
    'Film: keine Farbe hinter der Ebene - sie machte die durchsichtige Mitte zu');
assert_contains($filmRegel, 'object-fit:cover;', 'Film: mit gesetzter Hoehe wird nicht gezerrt');

/* --- Und das Panel bietet den Ort an --- */

$tafel = (string) file_get_contents(__DIR__ . '/../templates/admin/design-edit-sections.php');
assert_contains($tafel, 'Themes::SPOTS',
    'Panel: die Ortsliste kommt aus dem Katalog, also ist der vierte von selbst dabei');
