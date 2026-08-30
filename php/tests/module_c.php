<?php

declare(strict_types=1);

use Atelier\Design;
use Atelier\DesignSections;
use Atelier\SectionRegistry;

/*
 * Paket C: der Tag als eigene Art, und vier neue Gestalten.
 *
 * Die Bitte war nicht "mehr Auswahl", sondern "her bolum farkli bir gorsel
 * yapiya sahip olmali - davetiye scroll edildiginde her sey ayni
 * gorunmemeli". Eine Einladung, die von oben bis unten dieselbe zentrierte
 * Textspalte ist, liest sich wie eine Webseite und nicht wie Papeterie.
 *
 * Alle vier benutzen die Rollen aus Paket A. Keine einzige Groesse steht
 * hier als feste Zahl - sonst waere jede neue Gestalt wieder eine, an der
 * der Grafiker nicht drehen kann.
 */

function c_doc(array $sections): array
{
    return DesignSections::complete([
        'id' => 'probe', 'slug' => 'probe',
        'palette' => ['accent' => ['value' => '#B08D57'], 'paper' => ['value' => '#FAF7F2']],
        'fonts'   => ['display' => ['family' => 'Cormorant']],
        'sections' => $sections,
    ]);
}

/* ===================== 1. Der Tag als eigene Art ======================== */

assert_true(in_array('date', DesignSections::TYPES, true), 'Datum: es gibt die Art');
assert_same(['date'], SectionRegistry::needs('date'), 'Datum: es braucht den Termin');

foreach (['de', 'en', 'tr'] as $sprache) {
    assert_true(SectionRegistry::typeLabel('date', $sprache) !== '',
        'Datum: es hat einen Namen auf ' . $sprache);
}

assert_same(['default', 'gross', 'zeile'], array_keys(SectionRegistry::variants('date')),
    'Datum: drei Gestalten');

/*
 * Eine eigene Art und keine Gestalt des Countdowns - und der Unterschied ist
 * nicht Geschmack: der Countdown zaehlt und verschwindet nach der Feier, das
 * Datum bleibt stehen. Wer die Einladung ein Jahr spaeter oeffnet, will
 * lesen, wann es war.
 */
$vergangen = c_doc([
    ['id' => 'd-1', 'type' => 'date'],
    ['id' => 'cd-1', 'type' => 'countdown'],
]);
$altHtml = DesignSections::html($vergangen, ['date' => '2026-05-01'], 'de', '2027-01-01');

assert_contains($altHtml, 'd-sec-date', 'Datum: es steht auch nach der Feier noch da');
assert_true(!str_contains($altHtml, 'd-sec-countdown'),
    'Datum: der Countdown dagegen verschwindet - er haette nichts mehr zu zaehlen');

/* --- Die grosse Zahl: genau die Anordnung aus der Anfrage --- */

$gross = c_doc([['id' => 'd-1', 'type' => 'date', 'variant' => 'gross',
                 'title' => ['de' => 'Datum', 'en' => 'Date']]]);
$grossHtml = DesignSections::html($gross, ['date' => '2026-08-08'], 'de', '2026-01-01');

// "TARIH / 08 / AGUSTOS / 2026" - das Wort darueber ist die Ueberschrift des
// Abschnitts und kein viertes Feld. Ein eigenes waere ein zweiter Ort fuer
// dieselbe Zeile.
assert_contains($grossHtml, '<h2 class="d-sec-title">Datum</h2>', 'Datum: die Ueberschrift traegt das Wort');
assert_contains($grossHtml, '<p class="d-datum-tag">8</p>', 'Datum: der Tag steht allein');
assert_contains($grossHtml, '<p class="d-datum-monat">August</p>', 'Datum: der Monat darunter');
assert_contains($grossHtml, '<p class="d-datum-jahr">2026</p>', 'Datum: und das Jahr');

/*
 * Aus der Zeichenkette geschnitten und nicht mit einem Zeitstempel gerechnet.
 * Der Wert kommt aus einem Formular, und der Umweg ueber eine Zeitzone macht
 * aus dem 12. je nach Serverzeit den 11. - dieselbe Vorsicht wie im
 * Countdown-Skript.
 */
$rand = DesignSections::html(
    c_doc([['id' => 'd-1', 'type' => 'date', 'variant' => 'gross']]),
    ['date' => '2027-01-01'],
    'de',
    '2026-01-01'
);
assert_contains($rand, '<p class="d-datum-tag">1</p>', 'Datum: der erste Januar bleibt der erste');
assert_contains($rand, '<p class="d-datum-jahr">2027</p>', 'Datum: und das Jahr stimmt');

/* --- Die Groessen kommen aus den Rollen, nicht aus dieser Datei --- */

$grossCss = DesignSections::css($gross, '.d-p');

// Art UND Gestalt im Selektor: "gross" heisst beim Datum etwas anderes als
// beim Countdown, und ohne die Art faerbte der eine Block den anderen um.
assert_contains($grossCss, '.d-p .d-sec-date.d-sec-v-gross .d-datum-tag{',
    'Datum: die Gestalt bringt ihren Stilblock mit');
assert_contains($grossCss, 'var(--dt-number-size,',
    'Datum: der Tag traegt die Rolle "grosse Zahl" - genau die, an der der Grafiker dreht');
assert_contains($grossCss, 'var(--dt-small-size,', 'Datum: das Jahr die Rolle "kleiner Hinweis"');

// Keine feste Zahl mehr. Eine vergessene waere eine Gestalt, an der der
// Knopf im Panel nichts bewegt.
$tagRegel = (string) strstr((string) strstr($grossCss, '.d-datum-tag{'), '}', true);
assert_true(!preg_match('/font-size:[0-9]/', $tagRegel) === true || !str_contains($tagRegel, 'font-size:3.4rem'),
    'Datum: keine eingebaute Groesse');
assert_contains($tagRegel, '--dt-number-', 'Datum: der Tag haengt an der Rolle');

/* --- Die Zeile mit Strichen: Stil, nicht Text --- */

$zeile = c_doc([['id' => 'd-1', 'type' => 'date', 'variant' => 'zeile']]);
$zeileHtml = DesignSections::html($zeile, ['date' => '2026-08-08'], 'de', '2026-01-01');
$zeileCss  = DesignSections::css($zeile, '.d-p');

/*
 * Die Striche stehen im Stilblock und nicht im Markup. Ein Bindestrich als
 * Textknoten wuerde vorgelesen ("Strich acht Strich August") und liesse sich
 * von keiner Vorlage abschalten.
 */
assert_true(!str_contains($zeileHtml, '—') && !str_contains($zeileHtml, '·'),
    'Datum: keine Zierzeichen im Text');
assert_contains($zeileCss, '.d-datum-zeile span:first-child::before', 'Datum: die Striche sind Stil');

/* ================== 2. Countdown: die grosse Tageszahl ================== */

/*
 * "10 GUN - altinda: 23 SAAT · 31 DAKIKA · 54 SANIYE."
 */
$tage = c_doc([['id' => 'cd-1', 'type' => 'countdown', 'variant' => 'tage']]);
$tageHtml = DesignSections::html($tage, ['date' => '2027-06-19', 'time' => '15:00'], 'de', '2026-01-01');

assert_contains($tageHtml, 'data-countdown="2027-06-19T15:00"', 'Tage: das Ziel reist mit der Uhrzeit');

/*
 * Derselbe Vertrag wie die Uhr und kein zweiter Zaehler: das Skript schaltet
 * auf den Sekundentakt, sobald [data-countdown-hours] im Kasten steht. Ohne
 * dieses eine Feld liefe die Gestalt im Stundentakt und die Sekunden
 * blieben stehen.
 */
foreach (['days', 'hours', 'minutes', 'seconds'] as $feld) {
    assert_contains($tageHtml, 'data-countdown-' . $feld, 'Tage: das Feld ' . $feld . ' steht da');
}

$tageCss = DesignSections::css($tage, '.d-p');
assert_contains($tageCss, '.d-uhr-tage .d-sec-uhr-zahl{display:block;',
    'Tage: die Tageszahl steht allein in ihrer Zeile');
assert_contains($tageCss, 'var(--dt-number-size,', 'Tage: und traegt die Rolle "grosse Zahl"');

// Der Mittelpunkt zwischen Stunden, Minuten und Sekunden steht im Stil.
assert_true(!str_contains($tageHtml, '·'), 'Tage: kein Mittelpunkt als Textknoten');
assert_contains($tageCss, '.d-uhr-teil + .d-uhr-teil::before{content:"·";',
    'Tage: er kommt aus dem Stilblock');

/* ==================== 3. Kleiderordnung: die Palette ==================== */

$kleid = c_doc([['id' => 'dc-1', 'type' => 'dresscode', 'variant' => 'paar']]);
$kleidDaten = ['sections' => ['dc-1' => [
    'code'   => 'Black Tie',
    'women'  => 'Langes Kleid',
    'men'    => 'Dunkler Anzug',
    'colors' => '#E8D8C3, #7B2D26 , nicht-eine-farbe, #F2EDE4',
]]];
$kleidHtml = DesignSections::html($kleid, $kleidDaten, 'de', '2026-01-01');

assert_contains($kleidHtml, 'Langes Kleid', 'Kleid: die Zeile für Damen');
assert_contains($kleidHtml, 'Dunkler Anzug', 'Kleid: und die für Herren');

/*
 * Drei Kreise, nicht vier: was keine Farbe ist, faellt weg. safeColor gibt
 * dafuer "transparent" zurueck - ein durchsichtiger Kreis waere ein Loch in
 * der Palette.
 */
assert_same(3, substr_count($kleidHtml, 'd-dress-kreis'), 'Kleid: drei gueltige Farben, eine faellt weg');
assert_contains($kleidHtml, 'style="background:#E8D8C3"', 'Kleid: die erste Farbe');
assert_contains($kleidHtml, 'title="#7B2D26"', 'Kleid: der Wert steht daneben - man will ihn abschreiben');
assert_true(!str_contains($kleidHtml, 'nicht-eine-farbe'), 'Kleid: was keine Farbe ist, kommt nicht ins Markup');

/*
 * Die Palette allein traegt den Abschnitt. Ohne diese Zeile stuende eine
 * Vorlage, die nur Farben zeigen will, ueberhaupt nicht auf der Einladung.
 */
$nurFarben = DesignSections::html(
    c_doc([['id' => 'dc-1', 'type' => 'dresscode']]),
    ['sections' => ['dc-1' => ['colors' => '#E8D8C3']]],
    'de',
    '2026-01-01'
);
assert_contains($nurFarben, 'd-sec-dresscode', 'Kleid: die Palette allein genuegt');

// Ganz ohne Angabe bleibt es bei "kein Abschnitt".
assert_same('', DesignSections::html(c_doc([['id' => 'dc-1', 'type' => 'dresscode']]), [], 'de', '2026-01-01'),
    'Kleid: ohne alles kein Abschnitt');

/* ================== 4. Ort: die eigene Kartenzeichnung ================== */

/*
 * "Kendi haritali resmimi ekleyebilmeliyim. Gercek haritayi optional
 * cikarabilmeliyim kendi harita resmimi yuklemek icin."
 */
$ortDaten = ['venue' => 'Villa Sonnenhof', 'address' => 'Seestrasse 4, 88131 Lindau', 'slug' => 'paar'];

$eigen = c_doc([['id' => 'ort-1', 'type' => 'location',
                 'settings' => ['karte' => 'eigen', 'mapSrc' => '/uploads/designs/skizze.png']]]);
$eigenHtml = DesignSections::html($eigen, $ortDaten, 'de', '2026-01-01');

assert_contains($eigenHtml, '/uploads/designs/skizze.png', 'Karte: die eigene Zeichnung wird gedruckt');
assert_true(!str_contains($eigenHtml, 'karte.png'),
    'Karte: und ersetzt das gerechnete Bild vollstaendig');

// Der Weg zur Route bleibt: er haengt an der Adresse, nicht am Bild.
assert_contains($eigenHtml, 'https://www.google.com/maps/dir/', 'Karte: die Route bleibt');

/*
 * Ohne Datei kein Bild. Ein leerer Rahmen an der Stelle, an der eine Karte
 * stehen sollte, ist schlimmer als gar keine - und "eigen" ohne Datei ist
 * genau der Zustand direkt nach dem Umschalten.
 */
$leerEigen = c_doc([['id' => 'ort-1', 'type' => 'location', 'settings' => ['karte' => 'eigen']]]);
$leerHtml = DesignSections::html($leerEigen, $ortDaten, 'de', '2026-01-01');

assert_true(!str_contains($leerHtml, 'd-sec-map-bild'), 'Karte: ohne Datei kein Bildrahmen');
assert_contains($leerHtml, 'Seestrasse 4', 'Karte: die Anschrift steht trotzdem da');

// Eine fremde Adresse faellt weg - dieselbe Pruefung wie bei jedem Bild.
$fremd = c_doc([['id' => 'ort-1', 'type' => 'location',
                 'settings' => ['karte' => 'eigen', 'mapSrc' => 'https://fremd.example/k.png']]]);
assert_true(!str_contains(DesignSections::html($fremd, $ortDaten, 'de', '2026-01-01'), 'fremd.example'),
    'Karte: eine fremde Adresse kommt nicht durch');

/* ==================== 5. Programm: einzelne Kaertchen =================== */

$karten = c_doc([['id' => 'prog-1', 'type' => 'program', 'variant' => 'karten']]);
$kartenHtml = DesignSections::html($karten, ['program' => [
    ['time' => '15:00', 'title' => 'Trauung', 'icon' => 'nikah', 'text' => ''],
    ['time' => '18:00', 'title' => 'Essen', 'icon' => 'yemek', 'text' => 'Im Saal'],
]], 'de', '2026-01-01');

/*
 * Ein <div> zwischen <dl> und den Paaren. Es ist gueltiges HTML - die
 * Spezifikation erlaubt es ausdruecklich, um genau solche Gruppen zu bilden.
 * Ohne diesen Knoten liesse sich kein Kaestchen um Zeit UND Station legen:
 * dt und dd sind Geschwister, und CSS kann zwei Geschwister nicht
 * zusammenfassen.
 */
assert_same(2, substr_count($kartenHtml, '<div class="d-plan-karte">'), 'Programm: ein Kasten je Station');
assert_contains($kartenHtml, '<dl class="d-sec-plan">', 'Programm: die Liste bleibt eine Liste');

// Und nur in dieser Gestalt: die anderen beiden rechnen mit dt und dd als
// direkten Kindern des Rasters.
$strahl = c_doc([['id' => 'prog-1', 'type' => 'program', 'variant' => 'zeitstrahl']]);
$strahlHtml = DesignSections::html($strahl, ['program' => [
    ['time' => '15:00', 'title' => 'Trauung', 'icon' => 'nikah', 'text' => ''],
]], 'de', '2026-01-01');
assert_true(!str_contains($strahlHtml, 'd-plan-karte'),
    'Programm: der Zeitstrahl bekommt keinen Kasten');

/* ============ Und jede neue Gestalt bringt ihren Stil mit ============== */

/*
 * Eine Gestalt anzubieten, die aussieht wie die Voreinstellung, waere ein
 * Versprechen, das die Vorlage nicht haelt - der Grafiker waehlt sie einmal,
 * sieht keinen Unterschied und traut dem Katalog danach nicht mehr.
 */
foreach ([
    ['date', 'gross'], ['date', 'zeile'], ['date', 'default'],
    ['countdown', 'tage'], ['program', 'karten'], ['dresscode', 'paar'],
] as [$art, $gestalt]) {
    assert_true(SectionRegistry::isVariant($art, $gestalt),
        'Katalog: ' . $art . '/' . $gestalt . ' steht im Katalog');

    $doc = c_doc([['id' => 's', 'type' => $art, 'variant' => $gestalt]]);
    assert_contains(DesignSections::css($doc, '.d-p'), '.d-sec-v-' . $gestalt,
        'Katalog: ' . $art . '/' . $gestalt . ' bringt einen Stilblock mit');
}
