<?php
declare(strict_types=1);

use Atelier\DesignSections;

/*
 * Abschnitte sind das, was unter der Karte steht: Ort, Countdown, Familien,
 * Programm. Sie gehoeren dem Dokument, nicht der Einladung - der Grafiker
 * stellt sie auf, der Kunde darf hoechstens zu- und abschalten.
 *
 * Dieselbe Form wie bei den Ebenen: die Reihenfolge ist die Reihenfolge im
 * Feld, Farbe und Schrift sind Markennamen, und edit ist der Hauptschalter.
 */

function sec_doc(array $sections): array
{
    return ['id' => 'test', 'slug' => 'test', 'sections' => $sections];
}

// Die IDs und Typen sind bewusst so gewaehlt, dass sie gegen die Feldordnung
// sortieren - wenn jemand sort() oder usort() nach ID oder Typ einfuegt,
// faellt dieser Test sofort um.
$doc = DesignSections::complete(sec_doc([
    ['id' => 'z-prog', 'type' => 'program'],
    ['id' => 'unbekannt', 'type' => 'wetterbericht'],
    ['id' => 'a-ort', 'type' => 'location', 'enabled' => false,
     'title' => ['de' => 'Ablauf', 'en' => 'Schedule'],
     'style' => ['color' => 'Accent', 'font' => 'display'],
     'permissions' => ['edit' => true, 'hide' => true]],
]));

assert_same(2, count($doc['sections']), 'complete: unbekannter Typ faellt weg');
assert_same('z-prog', $doc['sections'][0]['id'], 'complete: Reihenfolge ist die des Feldes');
assert_same('a-ort', $doc['sections'][1]['id'], 'complete: Reihenfolge ist die des Feldes (zweiter)');

// Vollstaendig, auch wo nichts angegeben war.
$erste = $doc['sections'][0];
assert_same('program', $erste['type'], 'complete: Typ bleibt');
assert_same(true, $erste['enabled'], 'complete: enabled ist standardmaessig an');
assert_same('', $erste['title']['de'], 'complete: fehlender Titel wird leer');
assert_same('', $erste['style']['color'], 'complete: fehlende Farbmarke wird leer');
assert_same(false, $erste['permissions']['edit'], 'complete: Rechte sind standardmaessig zu');
assert_same(false, $erste['permissions']['hide'], 'complete: Rechte sind standardmaessig zu');

// Angegebenes bleibt - und der Markenname wird normalisiert wie ueberall sonst.
$zweite = $doc['sections'][1];
assert_same(false, $zweite['enabled'], 'complete: enabled=false bleibt');
assert_same('Ablauf', $zweite['title']['de'], 'complete: Titel bleibt');
assert_same('accent', $zweite['style']['color'], 'complete: Markenname wird kleingeschrieben');
assert_same(true, $zweite['permissions']['edit'], 'complete: gesetztes Recht bleibt');

// Ohne Kennung kein Abschnitt: er waere im CSS nicht adressierbar.
$ohne = DesignSections::complete(sec_doc([['type' => 'family']]));
assert_same([], $ohne['sections'], 'complete: ohne id faellt der Abschnitt weg');

// Was kein Feld ist, ist kein Abschnitt.
$mist = DesignSections::complete(sec_doc(['etwas', 42, null]));
assert_same([], $mist['sections'], 'complete: Unsinn im Feld faellt weg');

// Der Rest des Dokuments bleibt unberuehrt.
$rest = DesignSections::complete(['id' => 'x', 'layers' => [['id' => 'a']], 'sections' => []]);
assert_same(1, count($rest['layers']), 'complete: layers bleiben unangetastet');

/*
 * Ein leerer Abschnitt wird nicht gedruckt.
 *
 * Dieselbe Regel wie bei einem gebundenen Textelement ohne Wert: er faellt
 * weg, statt eine leere Ueberschrift zu hinterlassen. Der Kunde muss nichts
 * abschalten, was er ohnehin nicht ausgefuellt hat.
 *
 * Das Bezugsdatum kommt als Parameter - ein Test, der von der Uhr abhaengt,
 * faellt irgendwann von selbst um.
 */

$alle = sec_doc([
    ['id' => 'ort-1',  'type' => 'location'],
    ['id' => 'cd-1',   'type' => 'countdown'],
    ['id' => 'fam-1',  'type' => 'family'],
    ['id' => 'prog-1', 'type' => 'program'],
]);

$leer = DesignSections::visible($alle, [], '2027-01-01');
assert_same([], $leer, 'visible: ohne Daten wird nichts gedruckt');

$voll = DesignSections::visible($alle, [
    'address'  => 'Elmau 2, 82493 Krün',
    'date'     => '2027-06-12',
    'families' => ['bride' => 'Familie Weber', 'groom' => ''],
    'program'  => [['time' => '15:00', 'title' => 'Trauung']],
], '2027-01-01');
assert_same(4, count($voll), 'visible: mit Daten werden alle vier gedruckt');

// Ort ohne Adresse: der Kartenlink haette kein Ziel.
$ohneOrt = DesignSections::visible($alle, ['date' => '2027-06-12'], '2027-01-01');
assert_same(['cd-1'], array_column($ohneOrt, 'id'), 'visible: ohne Adresse kein Ort');

// Ein vergangener Termin bekommt keinen Countdown.
$vorbei = DesignSections::visible($alle, ['date' => '2026-06-12'], '2027-01-01');
assert_same([], $vorbei, 'visible: vergangenes Datum, kein Countdown');

// Der Tag selbst zaehlt noch.
$heute = DesignSections::visible($alle, ['date' => '2027-01-01'], '2027-01-01');
assert_same(['cd-1'], array_column($heute, 'id'), 'visible: der Tag selbst zaehlt noch');

// Eine Familie reicht.
$eine = DesignSections::visible($alle, ['families' => ['groom' => 'Familie Yılmaz']], '2027-01-01');
assert_same(['fam-1'], array_column($eine, 'id'), 'visible: eine Familie reicht');

// Was der Grafiker abgeschaltet hat, bleibt abgeschaltet.
$aus = DesignSections::visible(sec_doc([
    ['id' => 'fam-1', 'type' => 'family', 'enabled' => false],
]), ['families' => ['bride' => 'Familie Weber']], '2027-01-01');
assert_same([], $aus, 'visible: enabled=false bleibt weg');

// Programmzeilen: ohne Titel keine Zeile, Uhrzeit darf fehlen.
$zeilen = DesignSections::programRows(['program' => [
    ['time' => '15:00', 'title' => 'Trauung'],
    ['time' => '16:00', 'title' => ''],
    ['title' => 'Dinner'],
    'unsinn',
]]);
assert_same(2, count($zeilen), 'programRows: ohne Titel keine Zeile');
assert_same('', $zeilen[1]['time'], 'programRows: Uhrzeit darf fehlen');

// Obergrenze: was darueber liegt, faellt weg statt die Seite zu sprengen.
$viele = [];
for ($i = 0; $i < 40; $i++) {
    $viele[] = ['time' => '10:00', 'title' => 'Punkt ' . $i];
}
assert_same(DesignSections::PROGRAM_MAX, count(DesignSections::programRows(['program' => $viele])), 'programRows: Obergrenze greift');

/*
 * Gedruckt wird gegen Markennamen, nicht gegen Werte.
 *
 * Dieselbe Lehre wie in Phase 3B: der Renderer schreibt var(--d-<name>).
 * Stuende dort ein roher Wert, ergaebe das var(--d-#B08D57) - ungueltiges CSS
 * und ein farbloses Element, das niemandem auffaellt.
 */

$stil = sec_doc([
    ['id' => 'prog-1', 'type' => 'program',
     'style' => ['color' => 'accent', 'font' => 'display']],
    ['id' => 'fam-1', 'type' => 'family'],
]);

$css = DesignSections::css($stil, '.d-elysee');
assert_contains($css, '.d-elysee .d-sec-prog-1{', 'css: Abschnitt wird im Bereich adressiert');
assert_contains($css, 'color:var(--d-accent)', 'css: Farbe kommt als Marke');
assert_contains($css, 'font-family:var(--df-display)', 'css: Schrift kommt als Marke');
assert_not_contains($css, '.d-sec-fam-1{', 'css: ohne Stil keine Regel');

/*
 * Der Grundstil: ohne ihn setzt Tailwinds Preflight h1..h6 auf geerbte
 * Groesse zurueck und p auf margin:0 - Abschnitte wuerden zu einer
 * ununterschiedenen Textwand. Der Block steht einmal, vor den
 * abschnittsweisen Regeln, und jeder Selektor darin haengt am $scope, damit
 * zwei Designs auf einer Seite sich nicht gegenseitig umfaerben.
 */
assert_same(1, substr_count($css, '.d-elysee .d-sec{'), 'css: der Grundstil wird nur einmal ausgegeben');
assert_contains($css, '.d-elysee .d-sec-title{', 'css: die Ueberschrift bekommt einen Grundstil');
assert_contains($css, '.d-elysee .d-sec-program{', 'css: das Programm bekommt eine Zweispaltenregel');
assert_contains($css, '.d-elysee .d-sec-program dt{', 'css: dt bekommt eine Regel');
assert_contains($css, '.d-elysee .d-sec-program dd{', 'css: dd bekommt eine Regel');

// Jeder Selektor des Grundstils steht unter dem Bereich - keine nackte
// Klasse ohne $scope davor. Ein Selektor ohne Bereich ist genau dann drin,
// wenn er ohne das vorangestellte "$scope " im CSS vorkaeme; hier wird
// direkt geprueft, dass jede erwartete Regel *mit* dem Bereich davor steht.
foreach (['.d-sec{', '.d-sec:first-child{', '.d-sec-title{', '.d-sec p{', '.d-sec-program{', '.d-sec-program dt{', '.d-sec-program dd{'] as $sel) {
    assert_contains($css, '.d-elysee ' . $sel, 'css: Grundstil-Selektor "' . $sel . '" ist am Bereich verankert');
}

// Ein zweites Design im selben Dokument bekommt seinen eigenen Bereich -
// zwei Designs auf einer Seite duerfen sich nicht gegenseitig umfaerben.
$cssZwei = DesignSections::css($stil, '.d-noir');
assert_contains($cssZwei, '.d-noir .d-sec{', 'css: ein zweiter Bereich bekommt seinen eigenen Grundstil');
assert_not_contains($cssZwei, '.d-elysee .d-sec{', 'css: der Grundstil des ersten Bereichs bleibt aussen vor');

// Ohne Abschnitte kein Grundstil - es gibt nichts, das er stuetzen muesste.
assert_same('', DesignSections::css(sec_doc([]), '.d-elysee'), 'css: ohne Abschnitte kein Grundstil');

$daten = [
    'address'  => 'Elmau 2, 82493 Krün',
    'date'     => '2027-06-12',
    'families' => ['bride' => 'Familie Weber', 'groom' => 'Familie Yılmaz'],
    'program'  => [['time' => '15:00', 'title' => 'Trauung']],
];

$html = DesignSections::html(sec_doc([
    ['id' => 'ort-1',  'type' => 'location',  'title' => ['de' => 'Ort', 'en' => 'Place']],
    ['id' => 'cd-1',   'type' => 'countdown', 'title' => ['de' => '', 'en' => '']],
    ['id' => 'fam-1',  'type' => 'family',    'title' => ['de' => 'Familien', 'en' => 'Families']],
    ['id' => 'prog-1', 'type' => 'program',   'title' => ['de' => 'Ablauf', 'en' => 'Schedule']],
]), $daten, 'de', '2027-01-01');

assert_contains($html, 'class="d-sec d-sec-ort-1 d-sec-location"', 'html: Kennung und Art stehen in der Klasse');
assert_contains($html, '<h2', 'html: Titel wird gedruckt');
assert_contains($html, 'Ort', 'html: der deutsche Titel');
assert_not_contains($html, '<h2 class="d-sec-title"></h2>', 'html: leerer Titel wird nicht gedruckt');
assert_contains($html, 'Elmau 2', 'html: die Adresse steht da');
assert_contains($html, 'google.com/maps', 'html: der Kartenlink wird gebaut');
assert_contains($html, 'data-countdown="2027-06-12"', 'html: der Countdown traegt sein Datum');
// Der Countdown braucht einen Kind-Span fuer die Zahl: ein Attribut auf dem
// <p> selbst wird von keinem Selektor gelesen (siehe invite-v2-countdown.js).
assert_contains($html, 'data-countdown-days', 'html: der Countdown hat einen Span fuer die Tageszahl');
assert_contains($html, 'data-label="Tage"', 'html: das deutsche Wort steht im Span, nicht im Skript');
assert_contains($html, 'Familie Weber', 'html: die Familie steht da');
assert_contains($html, 'Trauung', 'html: die Programmzeile steht da');
assert_contains($html, '15:00', 'html: die Uhrzeit steht da');

// Englisch nimmt den englischen Titel.
$en = DesignSections::html(sec_doc([
    ['id' => 'ort-1', 'type' => 'location', 'title' => ['de' => 'Ort', 'en' => 'Place']],
]), $daten, 'en', '2027-01-01');
assert_contains($en, 'Place', 'html: englischer Titel auf der englischen Seite');

/*
 * Ein nur-deutscher Titel darf auf der englischen Seite nicht verschwinden.
 * complete() schreibt 'en' immer als String (auch als '') - ein ?? auf
 * $abschnitt['title']['en'] faende also immer einen Wert (den leeren) und
 * fiele nie auf 'de' zurueck. Erst ein explizites !== ''-Fallback rettet
 * den deutschen Titel.
 */
$nurDeutsch = DesignSections::html(sec_doc([
    ['id' => 'ort-1', 'type' => 'location', 'title' => ['de' => 'Ort', 'en' => '']],
]), $daten, 'en', '2027-01-01');
assert_contains($nurDeutsch, '<h2 class="d-sec-title">Ort</h2>', 'html: nur-deutscher Titel steht auch auf der englischen Seite');

// Alles, was aus den Daten kommt, wird maskiert.
$boese = DesignSections::html(sec_doc([
    ['id' => 'fam-1', 'type' => 'family'],
]), ['families' => ['bride' => '<script>alert(1)</script>']], 'de', '2027-01-01');
assert_not_contains($boese, '<script>', 'html: kein rohes Markup aus den Daten');
assert_contains($boese, '&lt;script&gt;', 'html: und zwar sichtbar maskiert');

// Nichts Sichtbares, nichts Gedrucktes.
assert_same('', DesignSections::html(sec_doc([['id' => 'p', 'type' => 'program']]), [], 'de', '2027-01-01'), 'html: ohne Inhalt kein Markup');

/*
 * programRows() schneidet time und title mit mb_substr auf PROGRAM_LEN ab -
 * aber kein Test hat das bisher geprueft. Wuerden beide mb_substr-Aufrufe
 * geloescht, liefe die ganze Suite trotzdem gruen.
 *
 * Das Zeichen '—' (Gedankenstrich, U+2014) ist in UTF-8 drei Byte breit, und
 * PROGRAM_LEN (80) ist kein Vielfaches von drei. Ein byteweiser Schnitt bei
 * 80 Byte liegt deshalb zwingend mitten in einem Zeichen (80 / 3 = 26 Rest 2)
 * und ergibt eine kaputte Zeichenkette, die erst auf der Seite auffaellt.
 * Mit 'üş' (zwei Byte je Zeichen) waere 80 dagegen ein Vielfaches der
 * Zeichenbreite - ein byteweiser Schnitt landete dort zufaellig auch auf
 * einer Zeichengrenze und mb_check_encoding koennte den Fehler nicht zeigen.
 * Deshalb steht hier ein drittes, eigens gewaehltes Zeichen.
 */

$lang = DesignSections::programRows(['program' => [
    ['time' => '15:00', 'title' => str_repeat('A', 90)],
]]);
assert_same(DesignSections::PROGRAM_LEN, mb_strlen($lang[0]['title']), 'programRows: Titel wird auf PROGRAM_LEN gekuerzt');

$mehrbytig = DesignSections::programRows(['program' => [
    ['time' => '15:00', 'title' => str_repeat('üş', 60)],
]]);
assert_same(DesignSections::PROGRAM_LEN, mb_strlen($mehrbytig[0]['title']), 'programRows: mehrbytiger Titel wird auf PROGRAM_LEN gekuerzt');

// Das eigentliche Beweisstueck: eine Zeichenbreite, die PROGRAM_LEN nicht teilt.
$dreibytig = DesignSections::programRows(['program' => [
    ['time' => '15:00', 'title' => str_repeat('—', 100)],
]]);
assert_same(DesignSections::PROGRAM_LEN, mb_strlen($dreibytig[0]['title']), 'programRows: dreibytiger Titel wird auf PROGRAM_LEN gekuerzt');
assert_same(true, mb_check_encoding($dreibytig[0]['title'], 'UTF-8'), 'programRows: dreibytiger Schnitt bleibt gueltiges UTF-8');

/*
 * Der fuenfte Typ: rsvp.
 *
 * Vier Abschnitte zeigen, dieser eine fragt. Fuer visible() ist er trotzdem
 * ein Abschnitt wie jeder andere - die Regel steht an derselben Stelle wie
 * die des Countdowns, damit sie nicht ein zweites Mal erfunden wird.
 */

assert_same('rsvp', DesignSections::TYPES[4], 'TYPES: rsvp ist die fuenfte Art');
assert_true(in_array('rsvp', DesignSections::TYPES, true), 'TYPES: rsvp steht im Katalog');

$fragt = sec_doc([['id' => 'rsvp-1', 'type' => 'rsvp']]);

// Ohne Datum wird gefragt: auch eine Einladung ohne festen Termin darf
// wissen wollen, wer kommt. Das ist der Unterschied zum Countdown, der ohne
// Datum gar nichts anzeigen koennte.
assert_same(['rsvp-1'], array_column(DesignSections::visible($fragt, [], '2027-01-01'), 'id'), 'visible: ohne Datum wird das Formular gedruckt');

// Ein kuenftiger Termin sammelt Antworten.
assert_same(['rsvp-1'], array_column(DesignSections::visible($fragt, ['date' => '2027-06-12'], '2027-01-01'), 'id'), 'visible: kuenftiger Termin sammelt Antworten');

// Ein vergangener nicht - Antworten auf eine gefeierte Hochzeit sind Laerm.
assert_same([], DesignSections::visible($fragt, ['date' => '2026-06-12'], '2027-01-01'), 'visible: vergangene Hochzeit sammelt keine Antworten mehr');

// Der Tag selbst zaehlt noch, wie beim Countdown: es wird ja bis zum Morgen
// gefeiert, und wer mittags noch zusagt, sagt zu.
assert_same(['rsvp-1'], array_column(DesignSections::visible($fragt, ['date' => '2027-01-01'], '2027-01-01'), 'id'), 'visible: der Tag selbst zaehlt noch');

// Was der Grafiker abgeschaltet hat, bleibt abgeschaltet - auch das Formular.
assert_same([], DesignSections::visible(sec_doc([
    ['id' => 'rsvp-1', 'type' => 'rsvp', 'enabled' => false],
]), [], '2027-01-01'), 'visible: abgeschaltetes Formular bleibt weg');

/*
 * Das Formular.
 *
 * Es ist der einzige Abschnitt, der schreibt, und deshalb der einzige, der
 * ein Zeichen braucht. Das Zeichen kommt als Parameter herein, nicht aus
 * Security::csrf(): diese Klasse fasst keine Sitzung an, sonst liefe sie
 * nicht mehr unter bin/test.php.
 */

$formular = DesignSections::html(sec_doc([
    ['id' => 'rsvp-1', 'type' => 'rsvp', 'title' => ['de' => 'Kommt ihr?', 'en' => 'Are you coming?']],
]), ['date' => '2027-06-12'], 'de', '2027-01-01', ['csrf' => 'ZEICHEN123', 'sent' => false]);

assert_contains($formular, 'class="d-sec d-sec-rsvp-1 d-sec-rsvp"', 'html: Kennung und Art stehen in der Klasse');
assert_contains($formular, '<h2 class="d-sec-title">Kommt ihr?</h2>', 'html: der Titel des Grafikers steht darueber');
assert_contains($formular, '<form class="d-sec-form" method="post">', 'html: es ist ein Formular und es sendet per POST');

// Ohne Zeichen kein Schutz: ein Formular, das ohne CSRF-Feld hinausgeht,
// wuerde vom Controller abgewiesen und der Gast saehe nur, dass nichts
// passiert. Dieser Test ist die Wache davor.
assert_contains($formular, '<input type="hidden" name="csrf" value="ZEICHEN123">', 'html: das CSRF-Feld traegt das uebergebene Zeichen');

assert_contains($formular, 'name="name"', 'html: nach dem Namen wird gefragt');
assert_contains($formular, 'required', 'html: der Name ist Pflicht');
assert_contains($formular, 'maxlength="60"', 'html: der Name ist begrenzt');
assert_contains($formular, 'name="coming" value="1"', 'html: zusagen geht');
assert_contains($formular, 'name="coming" value="0"', 'html: absagen auch');
assert_contains($formular, 'name="count"', 'html: nach der Anzahl wird gefragt');
assert_contains($formular, 'min="1" max="20"', 'html: die Anzahl hat Grenzen');
assert_contains($formular, 'name="note"', 'html: es gibt Platz fuer einen Satz');
assert_contains($formular, 'maxlength="300"', 'html: auch der ist begrenzt');

// Kein action-Attribut: die Einladung nimmt ihre eigene Antwort entgegen.
// Ein erfundener Endpunkt waere eine zweite Adresse, die dieselbe Sache tut.
assert_not_contains($formular, 'action=', 'html: gesendet wird an die eigene Adresse');

// Englisch spricht Englisch - die Etiketten stehen in der Klasse, nicht im
// Woerterbuch, weil I18n::t() ueber Texts::get() an die Datenbank ginge.
$formularEn = DesignSections::html(sec_doc([
    ['id' => 'rsvp-1', 'type' => 'rsvp'],
]), [], 'en', '2027-01-01', ['csrf' => 'x']);
assert_contains($formularEn, 'Your name', 'html: englische Etiketten auf der englischen Seite');
assert_not_contains($formularEn, 'Euer Name', 'html: und dann nicht auch die deutschen');

/*
 * Das Zeichen wird maskiert wie jeder andere Wert.
 *
 * Heute kommt es aus Security::csrf() und ist Hexadezimal - aber der Wert
 * ist ein Parameter, und ein Parameter ist irgendwann etwas anderes. Was
 * gedruckt wird, geht durch e(), ohne Ausnahme.
 */
$boesesZeichen = DesignSections::html(sec_doc([
    ['id' => 'r', 'type' => 'rsvp'],
]), [], 'de', '2027-01-01', ['csrf' => '"><script>alert(1)</script>']);
assert_not_contains($boesesZeichen, '<script>', 'html: kein rohes Markup aus dem Zeichen');
assert_contains($boesesZeichen, '&lt;script&gt;', 'html: und zwar sichtbar maskiert');

/*
 * Nach dem Absenden steht Dank da, kein zweites Formular.
 *
 * Ein wieder leeres Formular direkt unter der eigenen Antwort liest sich wie
 * "nicht angekommen, nochmal" - und genau das taete der Gast dann auch.
 */
$danke = DesignSections::html(sec_doc([
    ['id' => 'r', 'type' => 'rsvp'],
]), [], 'de', '2027-01-01', ['csrf' => 'x', 'sent' => true]);
assert_not_contains($danke, '<form', 'html: nach der Antwort kein zweites Formular');
assert_contains($danke, 'Danke', 'html: sondern ein Dank');

/*
 * Der Grundstil des Formulars.
 *
 * Wie bei den uebrigen Abschnitten: Tailwinds Preflight nimmt input und
 * button jede Kontur, und ein Formular ohne Kontur ist auf einer
 * typografierten Einladung eine Reihe unsichtbarer Zeilen. Jeder Selektor
 * haengt am Bereich.
 */
$cssForm = DesignSections::css(sec_doc([['id' => 'rsvp-1', 'type' => 'rsvp']]), '.d-elysee');
foreach (['.d-sec-form{', '.d-sec-form-row{', '.d-sec-form button{'] as $sel) {
    assert_contains($cssForm, '.d-elysee ' . $sel, 'css: Formular-Selektor "' . $sel . '" ist am Bereich verankert');
}

// Die alten vier Aufrufer geben keinen fuenften Parameter mit und muessen
// weiterlaufen - sonst waere die Signaturaenderung ein Bruch.
$ohneForm = DesignSections::html(sec_doc([
    ['id' => 'fam-1', 'type' => 'family'],
]), ['families' => ['bride' => 'Familie Weber']], 'de', '2027-01-01');
assert_contains($ohneForm, 'Familie Weber', 'html: die vier Anzeige-Abschnitte brauchen den fuenften Parameter nicht');

/*
 * Der sechste Typ: text.
 *
 * Dress Code, Anfahrt, Kinder, ein Dank - das sind nicht sechs Arten von
 * Abschnitt, das ist sechsmal derselbe: eine Ueberschrift und ein Absatz.
 * Eine flexible Art statt sechs starrer, weil der Unterschied zwischen ihnen
 * im Text steht und nicht im Code.
 *
 * Anders als Familien und Programm kann ein Dokument MEHRERE davon tragen.
 * Der Inhalt haengt deshalb an der Kennung des Abschnitts und nicht an einem
 * festen Namen in data - zwei Bloecke wuerden sich sonst gegenseitig
 * ueberschreiben.
 */

assert_same(6, count(DesignSections::TYPES), 'TYPES: sechs Arten, die sechste ist text');
assert_true(in_array('text', DesignSections::TYPES, true), 'TYPES: text steht im Katalog');

// Der Zugriffsweg, einmal geprueft: fehlt irgendetwas davon, ist der Text leer
// und nicht ein Fehler.
assert_same('Dunkler Anzug', DesignSections::sectionText(['sections' => ['dc' => ['text' => 'Dunkler Anzug']]], 'dc'), 'sectionText: der Text kommt unter der Kennung heraus');
assert_same('', DesignSections::sectionText([], 'dc'), 'sectionText: ohne Daten leer');
assert_same('', DesignSections::sectionText(['sections' => []], 'dc'), 'sectionText: ohne Eintrag leer');
assert_same('', DesignSections::sectionText(['sections' => 'unsinn'], 'dc'), 'sectionText: Unsinn statt Feld ist leer');

$textDoc = sec_doc([
    ['id' => 'dc',   'type' => 'text', 'title' => ['de' => 'Dress Code', 'en' => 'Dress code']],
    ['id' => 'weg',  'type' => 'text', 'title' => ['de' => 'Anfahrt', 'en' => 'Getting there']],
]);

// Ohne Text kein Abschnitt - wie ueberall sonst faellt er weg, statt eine
// leere Ueberschrift zu hinterlassen.
assert_same([], DesignSections::visible($textDoc, [], '2027-01-01'), 'visible: ohne Text kein Abschnitt');

// Jeder Block traegt seinen eigenen Inhalt. Faellt der Zugriff auf einen
// festen Namen zurueck, zeigen hier beide dasselbe - und dieser Test faellt.
$zwei = DesignSections::visible($textDoc, ['sections' => [
    'dc'  => ['text' => 'Dunkler Anzug, langes Kleid'],
    'weg' => ['text' => 'Parkplaetze hinter der Kirche'],
]], '2027-01-01');
assert_same(['dc', 'weg'], array_column($zwei, 'id'), 'visible: zwei Textbloecke, beide sichtbar');

// Nur einer gefuellt: der andere faellt weg, nicht beide.
$einer = DesignSections::visible($textDoc, ['sections' => [
    'weg' => ['text' => 'Parkplaetze hinter der Kirche'],
]], '2027-01-01');
assert_same(['weg'], array_column($einer, 'id'), 'visible: nur der gefuellte Block steht da');

$textHtml = DesignSections::html($textDoc, ['sections' => [
    'dc'  => ['text' => "Dunkler Anzug\n\nKeine Turnschuhe"],
    'weg' => ['text' => 'Parkplaetze hinter der Kirche'],
]], 'de', '2027-01-01');

assert_contains($textHtml, '<h2 class="d-sec-title">Dress Code</h2>', 'html: der Titel des Grafikers steht darueber');
assert_contains($textHtml, 'Dunkler Anzug', 'html: der Text des Kunden steht da');
assert_contains($textHtml, 'Parkplaetze hinter der Kirche', 'html: und der des zweiten Blocks auch');

// Zwei Absaetze, zwei <p> - ein Zeilenumbruch, den der Kunde gesetzt hat,
// soll nicht zu einer Textwand zusammenfallen. paragraphs() ist derselbe
// Helfer, den die uebrigen Vorlagen benutzen.
$einBlock = DesignSections::html(sec_doc([['id' => 'dc', 'type' => 'text']]), ['sections' => [
    'dc' => ['text' => "Dunkler Anzug

Keine Turnschuhe"],
]], 'de', '2027-01-01');
assert_same(2, substr_count($einBlock, '<p class="d-sec-absatz">'), 'html: zwei Absaetze werden zwei Absaetze');
assert_same(1, substr_count($textHtml, 'Parkplaetze hinter der Kirche'), 'html: der zweite Block steht genau einmal da');

// Was der Kunde tippt, wird maskiert - auch hier.
$boeserText = DesignSections::html(sec_doc([['id' => 'dc', 'type' => 'text']]), ['sections' => [
    'dc' => ['text' => '<script>alert(1)</script>'],
]], 'de', '2027-01-01');
assert_not_contains($boeserText, '<script>', 'html: kein rohes Markup aus dem Text');
assert_contains($boeserText, '&lt;script&gt;', 'html: und zwar sichtbar maskiert');

// Die Absaetze heissen anders als der Abschnitt. Die <section> traegt
// d-sec-<typ>, also d-sec-text; hiesse der Absatz genauso, faerbte eine Regel
// fuer ihn auch den Kasten um. Derselbe Grund wie bei d-sec-form.
$kollision = DesignSections::html(sec_doc([['id' => 'dc', 'type' => 'text']]), ['sections' => [
    'dc' => ['text' => 'Dunkler Anzug'],
]], 'de', '2027-01-01');
assert_same(1, substr_count($kollision, 'd-sec-text'), 'html: d-sec-text steht nur an der section, nicht am Absatz');
assert_contains($kollision, '<p class="d-sec-absatz">', 'html: der Absatz hat seinen eigenen Namen');
