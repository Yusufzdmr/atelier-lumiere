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
assert_contains($css, '.d-elysee .d-sec-plan{', 'css: das Programm bekommt eine Zweispaltenregel');
assert_contains($css, '.d-elysee .d-sec-plan dt{', 'css: dt bekommt eine Regel');
assert_contains($css, '.d-elysee .d-sec-plan dd{', 'css: dd bekommt eine Regel');

// Jeder Selektor des Grundstils steht unter dem Bereich - keine nackte
// Klasse ohne $scope davor. Ein Selektor ohne Bereich ist genau dann drin,
// wenn er ohne das vorangestellte "$scope " im CSS vorkaeme; hier wird
// direkt geprueft, dass jede erwartete Regel *mit* dem Bereich davor steht.
foreach (['.d-sec{', '.d-sec:first-child{', '.d-sec-title{', '.d-sec p{', '.d-sec-plan{', '.d-sec-plan dt{', '.d-sec-plan dd{'] as $sel) {
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

assert_contains($html, 'class="d-sec d-sec-ort-1 d-sec-location d-sec-v-default"', 'html: Kennung, Art und Variante stehen in der Klasse');
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

assert_contains($formular, 'class="d-sec d-sec-rsvp-1 d-sec-rsvp d-sec-v-default"', 'html: Kennung, Art und Variante stehen in der Klasse');
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

assert_same(12, count(DesignSections::TYPES), 'TYPES: zwoelf Arten - sechs alte, dann Schluss, Geschenk, Musik, Galerie, Speisekarte, Kleiderordnung');
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

/*
 * ------------------------------------------------------------------
 * Variante und Einstellungen: dieselbe Art, ein anderes Aussehen.
 * ------------------------------------------------------------------
 *
 * Der Katalog (SectionRegistry) sagt, welche Varianten eine Art hat und
 * woran sich drehen laesst. Hier wird geprueft, dass das Dokument es
 * uebernimmt, der Stilblock es schreibt und das Markup es traegt.
 */

$mitVariante = DesignSections::complete(sec_doc([
    ['id' => 'ablauf', 'type' => 'program', 'variant' => 'zeitstrahl',
     'settings' => ['align' => 'left', 'space' => 'weit']],
    ['id' => 'wo', 'type' => 'location', 'variant' => 'discokugel'],
    ['id' => 'wann', 'type' => 'countdown'],
]));

assert_same('zeitstrahl', $mitVariante['sections'][0]['variant'], 'complete: die Variante bleibt');
assert_same('default', $mitVariante['sections'][1]['variant'], 'complete: eine erfundene Variante faellt zurueck');
assert_same('default', $mitVariante['sections'][2]['variant'], 'complete: ohne Angabe die Voreinstellung');

assert_same('left', $mitVariante['sections'][0]['settings']['align'], 'complete: die Einstellung bleibt');
assert_same('weit', $mitVariante['sections'][0]['settings']['space'], 'complete: die zweite auch');
assert_same('center', $mitVariante['sections'][2]['settings']['align'], 'complete: ohne Angabe die Voreinstellung');

// Die eigene Einstellung einer Art steht neben den gemeinsamen.
assert_same(true, $mitVariante['sections'][1]['settings']['map'], 'complete: der Ort bringt seinen Kartenlink mit');
assert_true(!array_key_exists('map', $mitVariante['sections'][2]['settings']), 'complete: der Countdown hat keinen');

/* --- Das Markup traegt die Variante als Klasse --- */

$html = DesignSections::html($mitVariante, [
    'program' => [['time' => '15:00', 'title' => 'Trauung']],
    'address' => 'Beispielweg 1',
    'date'    => '2099-01-01',
], 'de', '2026-01-01');

assert_contains($html, 'd-sec-v-zeitstrahl', 'html: die Variante steht als Klasse im Markup');
assert_contains($html, 'd-sec-v-default', 'html: auch die Voreinstellung steht da');

/* --- Der Stilblock schreibt die Einstellungen --- */

$css = DesignSections::css($mitVariante, '.d-x');

assert_contains($css, '.d-x .d-sec-ablauf{', 'css: der Abschnitt bekommt eine eigene Regel');
assert_contains($css, 'text-align:left', 'css: die Ausrichtung wird geschrieben');

/*
 * Der Variantenblock steht genau dann da, wenn ihn jemand benutzt. Tote
 * Regeln in jedem Dokument mitzuschleppen waere Ballast - und der Stilblock
 * steht inline in jeder Seite, nicht in einer Datei, die der Browser einmal
 * holt und behaelt.
 */
assert_contains($css, '.d-x .d-sec-program.d-sec-v-zeitstrahl', 'css: der Variantenblock steht da, weil er gebraucht wird');

$ohneVariante = DesignSections::complete(sec_doc([['id' => 'wann', 'type' => 'countdown']]));
$cssOhne = DesignSections::css($ohneVariante, '.d-x');

assert_true(
    !str_contains($cssOhne, 'd-sec-v-zeitstrahl'),
    'css: ein unbenutzter Variantenblock wird nicht geschrieben'
);

/* --- Der Kartenlink laesst sich abschalten --- */

$ohneKarte = DesignSections::complete(sec_doc([
    ['id' => 'wo', 'type' => 'location', 'settings' => ['map' => false]],
]));

$htmlOhneKarte = DesignSections::html($ohneKarte, ['address' => 'Beispielweg 1'], 'de', '2026-01-01');

assert_true(!str_contains($htmlOhneKarte, 'google.com/maps'), 'html: ohne Haken kein Kartenlink');
assert_contains($htmlOhneKarte, 'Beispielweg 1', 'html: die Adresse steht trotzdem da');

/*
 * --- Die Liste heisst nicht wie ihr Abschnitt ---
 *
 * Die <section> traegt d-sec-<typ>, also d-sec-program. Trug die Liste
 * darin denselben Namen, galt jede Regel fuer beide - und die
 * Zweispaltenregel machte aus dem ABSCHNITT ein Raster, in dem die
 * Ueberschrift neben ihrer Liste stand statt darueber. Das lief so, seit es
 * den Abschnitt gibt, und faellt nur auf, wenn man hinsieht.
 *
 * Derselbe Grund, aus dem das Formular d-sec-form heisst und der Absatz
 * d-sec-absatz. Hier steht die Regel als Test, damit der naechste Name
 * nicht wieder zurueckfaellt.
 */

$plan = DesignSections::complete(sec_doc([['id' => 'ablauf', 'type' => 'program']]));
$planHtml = DesignSections::html($plan, ['program' => [['time' => '15:00', 'title' => 'Trauung']]], 'de', '2026-01-01');
$planCss  = DesignSections::css($plan, '.d-x');

assert_contains($planHtml, '<dl class="d-sec-plan">', 'html: die Liste hat einen eigenen Namen');
assert_true(
    !str_contains($planHtml, '<dl class="d-sec-program"'),
    'html: die Liste heisst nicht wie ihr Abschnitt'
);
assert_true(
    !str_contains($planCss, ' .d-sec-program{'),
    'css: keine Regel traegt den Namen, den auch die Section traegt'
);

/*
 * --- Jede Gestalt im Katalog haelt ihr Versprechen ---
 *
 * Eine Gestalt, die aussieht wie die Voreinstellung, ist schlimmer als keine:
 * der Grafiker waehlt sie einmal, sieht keinen Unterschied und traut dem
 * Katalog danach nicht mehr. Deshalb steht hier eine Schleife und keine
 * Aufzaehlung - sie prueft auch jede Gestalt, die es morgen gibt.
 */

foreach (Atelier\SectionRegistry::all() as $art => $eintrag) {
    foreach (array_keys($eintrag['variants']) as $gestalt) {
        if ($gestalt === Atelier\SectionRegistry::DEFAULT_VARIANT) {
            continue;
        }

        $einer = DesignSections::complete(sec_doc([
            ['id' => 'probe', 'type' => $art, 'variant' => $gestalt],
        ]));

        assert_contains(
            DesignSections::css($einer, '.d-x'),
            '.d-x .d-sec-' . $art . '.d-sec-v-' . $gestalt,
            'Gestalt: ' . $art . '/' . $gestalt . ' bringt einen eigenen Stilblock mit'
        );
    }
}

/*
 * --- Derselbe Name, zwei Arten, kein Uebergriff ---
 *
 * "gross" heisst beim Ort etwas anderes als beim Countdown. Stuende im
 * Selektor nur die Gestalt, faerbte der eine Block den anderen um, sobald
 * beide auf derselben Seite stehen - und das faellt erst am fertigen
 * Dokument auf, nie im Panel.
 */

$beide = DesignSections::complete(sec_doc([
    ['id' => 'wo',   'type' => 'location',  'variant' => 'gross'],
    ['id' => 'wann', 'type' => 'countdown', 'variant' => 'gross'],
]));

$cssBeide = DesignSections::css($beide, '.d-x');

assert_contains($cssBeide, '.d-x .d-sec-location.d-sec-v-gross .d-sec-venue{', 'Gestalt: der Ort bekommt seinen Block');
assert_contains($cssBeide, '.d-x .d-sec-countdown.d-sec-v-gross .d-sec-days{', 'Gestalt: der Countdown seinen eigenen');
assert_true(
    !str_contains($cssBeide, '.d-x .d-sec-v-gross '),
    'Gestalt: kein Selektor ohne Art davor'
);

/*
 * --- Das Blatt liegt in einer eigenen Schicht und blendet unten aus ---
 *
 * Das Blatt ist 1,79 mal so hoch wie breit, ein Abschnitt ist meist kuerzer.
 * Die senkrechten Goldlinien fangen bei 55 % der Breite an und enden bei
 * 110 % - ein Abschnitt von 580 px bei 672 px Breite schneidet also mitten
 * durch sie hindurch, und eine Linie, die einfach aufhoert, sieht aus wie ein
 * Druckfehler.
 *
 * Eine eigene Schicht muss es sein: eine Maske auf dem Abschnitt selbst
 * wuerde auch seinen Text ausblenden.
 */

$blatt = DesignSections::css(
    DesignSections::complete(sec_doc([['id' => 'wo', 'type' => 'location']])),
    '.d-x'
);

/*
 * Ein Blatt fuer die ganze Flaeche, nicht eines je Abschnitt.
 *
 * Bis hierher zeichnete JEDER Abschnitt das Blatt neu, von seiner eigenen
 * Oberkante an, und blendete es unten aus. Auf dem Telefon sah man das
 * Ergebnis sofort: an jeder Abschnittsgrenze lief ein heller Streifen quer
 * durch die Einladung - der obere Rand des Bildes, immer wieder. Ayhan hat
 * genau diese Streifen gruen eingekreist.
 *
 * Jetzt liegt das Blatt EINMAL ueber dem ganzen Bereich und wird auf dessen
 * Hoehe gezogen. Damit kann es keine Naht mehr geben - es gibt nur noch eine
 * Kante, und die ist der Anfang und das Ende der Einladung.
 *
 * Der Preis ist bekannt und angenommen: eine lange Einladung zieht das Bild
 * in die Laenge. Die Alternative waere ein neunteiliges Bild (Ecken fest,
 * Mitte gedehnt) - mehr Arbeit und eine Angabe je Bild, und dafuer ist es
 * heute zu frueh.
 */
assert_not_contains($blatt, '.d-x .d-sec::before{', 'Blatt: kein Blatt mehr je Abschnitt');
assert_contains($blatt, '.d-x.d-sec-flaeche{', 'Blatt: die Flaeche traegt es');
assert_contains($blatt, 'background-image:var(--d-sec-blatt,none)', 'Blatt: und zwar als Bild');
assert_contains($blatt, 'background-size:min(100%,42rem) 100%', 'Blatt: auf die volle Hoehe gezogen');
assert_contains($blatt, '.d-x .d-sec > *{position:relative;z-index:1;}', 'Blatt: der Text liegt darueber');

/*
 * Die Papierfarbe bleibt am Abschnitt selbst. Blendete sie mit aus, fiele am
 * Fuss jedes Blattes die Farbe der Seite durch - und die ist bei einer
 * dunklen Vorlage auf einer hellen Seite genau der Bruch, den das Ausblenden
 * verhindern soll.
 */
assert_true(
    (bool) preg_match('/\.d-x \.d-sec\{[^}]*background-color:var\(--d-paper/', $blatt),
    'Blatt: die Papierfarbe bleibt am Abschnitt'
);
assert_true(
    !preg_match('/\.d-x \.d-sec\{[^}]*background-image/', $blatt),
    'Blatt: das Bild steht nicht mehr am Abschnitt selbst'
);

/*
 * ------------------------------------------------------------------
 * Die drei neuen Arten: Schluss, Geschenk, Musik.
 * ------------------------------------------------------------------
 */

$dreiDoc = DesignSections::complete(sec_doc([
    ['id' => 'schluss', 'type' => 'footer'],
    ['id' => 'konto',   'type' => 'gift'],
    ['id' => 'ton',     'type' => 'music', 'settings' => ['track' => '/uploads/designs/lied.mp3']],
]));

$dreiDaten = ['sections' => [
    'schluss' => ['text' => "Danke, dass ihr da wart.\n\nBis bald.", 'hashtag' => '#sophiaundmax'],
    'konto'   => ['text' => 'Wir freuen uns über einen Beitrag zur Reise.',
                  'holder' => 'Sophia Weber', 'iban' => 'de89370400440532013000'],
]];

$dreiHtml = DesignSections::html($dreiDoc, $dreiDaten, 'de', '2026-01-01');

/* --- Der Schluss: zwei Absaetze und das Zeichen --- */

assert_contains($dreiHtml, 'd-sec-footer', 'Schluss: der Abschnitt wird gedruckt');
assert_contains($dreiHtml, 'Danke, dass ihr da wart.', 'Schluss: der Text steht da');
assert_contains($dreiHtml, 'Bis bald.', 'Schluss: die Leerzeile macht einen zweiten Absatz');

/*
 * Das Doppelkreuz schreibt der Renderer. Wer es selbst tippt, tippt es
 * manchmal doppelt - und "##sophiaundmax" hat noch keine Bilder gefunden.
 */
assert_contains($dreiHtml, '<p class="d-sec-hashtag">#sophiaundmax</p>', 'Schluss: genau EIN Doppelkreuz');

/* --- Das Geschenk: die Kontonummer in Vierergruppen --- */

assert_contains($dreiHtml, 'Sophia Weber', 'Geschenk: der Inhaber steht da');
assert_contains($dreiHtml, 'DE89 3704 0044 0532 0130 00', 'Geschenk: die IBAN steht in Vierergruppen');

// Getippt wird sie, wie sie gerade kommt - gruppiert wird immer gleich.
assert_same('DE89 3704 0044 0532 0130 00', DesignSections::ibanGruppen('de89 3704-0044 0532013000'),
    'Geschenk: Leerzeichen und Striche des Tippenden zaehlen nicht');

/* --- Die Musik: der eingebaute Spieler, ohne Selbststart --- */

assert_contains($dreiHtml, '<audio class="d-sec-ton" controls preload="none" src="/uploads/designs/lied.mp3">',
    'Musik: der Spieler steht da');
assert_true(!str_contains($dreiHtml, 'autoplay'), 'Musik: und faengt nicht von allein an');

/* --- Ohne Inhalt kein Abschnitt --- */

$leerHtml = DesignSections::html($dreiDoc, [], 'de', '2026-01-01');

assert_true(!str_contains($leerHtml, 'd-sec-footer'), 'Schluss: ohne Wort und ohne Zeichen kein Abschnitt');
assert_true(!str_contains($leerHtml, 'd-sec-gift'), 'Geschenk: ohne Wunsch und ohne Konto kein Abschnitt');

/*
 * Die Musik bleibt: ihre Tonspur gehoert der VORLAGE und nicht dem Paar -
 * sie ist also auch dann da, wenn das Paar nichts eingetragen hat.
 */
assert_contains($leerHtml, 'd-sec-music', 'Musik: die Tonspur der Vorlage traegt den Abschnitt allein');

$ohneTon = DesignSections::complete(sec_doc([['id' => 'ton', 'type' => 'music']]));
assert_true(
    !str_contains(DesignSections::html($ohneTon, [], 'de', '2026-01-01'), 'd-sec-music'),
    'Musik: ohne Tonspur kein Abschnitt'
);

/* --- Die Galerie: Bilder statt Zeichen --- */

$galDoc = DesignSections::complete(sec_doc([['id' => 'bilder', 'type' => 'gallery']]));
$galDaten = ['sections' => ['bilder' => ['photos' => [
    '/uploads/einladungen/v2/a/1.jpg',
    'https://fremd.example/2.jpg',
    '/uploads/einladungen/v2/a/3.jpg',
]]]];

// Der Pfad geht durch safeSrc: er stammt zwar aus dem eigenen Upload, steht
// seitdem aber in einem JSON-Feld - und was dort steht, ist beim Lesen wieder
// eine Behauptung.
assert_same(
    ['/uploads/einladungen/v2/a/1.jpg', '/uploads/einladungen/v2/a/3.jpg'],
    DesignSections::sectionPhotos($galDaten, 'bilder'),
    'Galerie: ein fremder Host faellt weg'
);

$galHtml = DesignSections::html($galDoc, $galDaten, 'de', '2026-01-01');
assert_contains($galHtml, '<div class="d-sec-bilder">', 'Galerie: die Bilder stehen in einem Kasten');
assert_contains($galHtml, 'loading="lazy"', 'Galerie: sie laden erst, wenn jemand hinsieht');
assert_true(!str_contains($galHtml, 'fremd.example'), 'Galerie: der fremde Host steht nicht im Markup');

assert_true(
    !str_contains(DesignSections::html($galDoc, [], 'de', '2026-01-01'), 'd-sec-gallery'),
    'Galerie: ohne Bild kein Abschnitt'
);

/*
 * --- Was die Vorlage vorschlaegt, und was das Paar daraus macht ---
 *
 * Der Titel gehoerte der Vorlage, der Text dem Paar. Zu scharf: der Grafiker
 * baute eine Ueberschrift ueber nichts und sah im Schaufenster einen
 * Platzhalter, den er nicht aendern konnte.
 */

$mitVorgabe = DesignSections::complete(sec_doc([
    ['id' => 'wort', 'type' => 'text', 'defaults' => [
        'text'       => 'Zieht euch an, wie ihr euch wohlfuehlt.',
        'gibtesnicht' => 'faellt weg',
    ]],
]));

assert_same('Zieht euch an, wie ihr euch wohlfuehlt.',
    $mitVorgabe['sections'][0]['defaults']['text'], 'Vorgabe: sie steht im Dokument');
assert_true(!array_key_exists('gibtesnicht', $mitVorgabe['sections'][0]['defaults']),
    'Vorgabe: ein Schluessel, den die Art nicht kennt, faellt weg');

// Ohne eigenen Text druckt die Vorlage ihren Vorschlag.
assert_contains(DesignSections::html($mitVorgabe, [], 'de', '2026-01-01'),
    'Zieht euch an', 'Vorgabe: ohne eigenen Text steht der Vorschlag da');

// Mit eigenem Text gewinnt das Paar.
$eigen = ['sections' => ['wort' => ['text' => 'Kommt, wie ihr seid.']]];
$eigenHtml = DesignSections::html($mitVorgabe, $eigen, 'de', '2026-01-01');
assert_contains($eigenHtml, 'Kommt, wie ihr seid.', 'Vorgabe: das Paar gewinnt');
assert_true(!str_contains($eigenHtml, 'Zieht euch an'), 'Vorgabe: und der Vorschlag verschwindet');

// Bilder bekommen keine Voreinstellung - die Fotos eines fremden Paares
// stuenden sonst in jeder Einladung.
$galVorgabe = DesignSections::complete(sec_doc([
    ['id' => 'bilder', 'type' => 'gallery', 'defaults' => ['photos' => '/uploads/x.jpg']],
]));
assert_same([], $galVorgabe['sections'][0]['defaults'], 'Vorgabe: Bilder haben keine');

/* --- Jeder Abschnitt darf sein eigenes Blatt tragen ------------------------
 *
 * Bisher gab es ein Blatt fuer den ganzen Bereich (sectionsBg). Der Kunde
 * will eines je Abschnitt: "her birinin arkaplanini ozel olarak ayarlayabi-
 * leyim". Der Ort dafuer stand schon bereit - jeder Abschnitt hat eine eigene
 * Regel (.d-sec-<id>), aus der heute Farbe, Schrift und Ausrichtung kommen.
 *
 * Zwei Passungen, weil beide gebraucht werden: "blatt" ist dieselbe Rechnung
 * wie beim grossen Blatt (Breite an der Karte, oben, nach unten wiederholt),
 * "cover" fuellt den Abschnitt und schneidet dafuer die Raender ab.
 */

$doc = DesignSections::complete(sec_doc([
    ['id' => 'ort',    'type' => 'location',
     'style' => ['bg' => '/uploads/designs/rosen.webp', 'bgFit' => 'cover']],
    ['id' => 'wort',   'type' => 'text',
     'style' => ['bg' => '/uploads/designs/blatt.webp', 'bgFit' => 'quatsch']],
    ['id' => 'ablauf', 'type' => 'program'],
]));

assert_same('/uploads/designs/rosen.webp', $doc['sections'][0]['style']['bg'],
    'complete: der Pfad des Blattes bleibt stehen');
assert_same('cover', $doc['sections'][0]['style']['bgFit'], 'complete: cover bleibt cover');
assert_same('blatt', $doc['sections'][1]['style']['bgFit'],
    'complete: eine unbekannte Passung faellt auf blatt');
assert_same('', $doc['sections'][2]['style']['bg'], 'complete: ohne Angabe kein eigenes Blatt');

// Derselbe Filter wie ueberall, wo ein Pfad aus dem Panel kommt.
$boese = DesignSections::complete(sec_doc([
    ['id' => 'ort', 'type' => 'location', 'style' => ['bg' => 'javascript:alert(1)']],
]));
assert_same('', $boese['sections'][0]['style']['bg'], 'complete: ein unsauberer Pfad faellt weg');

/* --- Und die Regel steht im Stilblock, an der Kennung des Abschnitts --- */

$css = DesignSections::css(sec_doc([
    ['id' => 'ort',    'type' => 'location', 'style' => ['bg' => '/uploads/a.webp', 'bgFit' => 'blatt']],
    ['id' => 'wort',   'type' => 'text',     'style' => ['bg' => '/uploads/b.webp', 'bgFit' => 'cover']],
    ['id' => 'ablauf', 'type' => 'program'],
]), '.d-x');

assert_contains($css, ".d-x .d-sec-ort{", 'css: der Abschnitt bekommt seine eigene Regel');
assert_contains($css, "background-image:url('/uploads/a.webp');", 'css: mit seinem Blatt');
assert_contains($css, 'background-size:min(100%,42rem) auto;', 'css: blatt haengt an der Breite der Karte');
assert_contains($css, 'background-repeat:repeat-y;', 'css: und wiederholt sich nach unten');
assert_contains($css, 'background-size:cover;', 'css: cover fuellt den Abschnitt');
assert_contains($css, 'background-repeat:no-repeat;', 'css: und wiederholt sich nicht');

// Ein Abschnitt ohne Blatt bekommt auch keine Regel dafuer - der Stilblock
// steht inline in JEDER Seite, tote Zeilen kosten dort echtes Gewicht.
assert_not_contains($css, '.d-x .d-sec-ablauf{background-image', 'css: ohne Blatt keine Regel');

/* --- Die leere Zeile des Panels hat dieselbe Gestalt wie eine echte -------
 *
 * Unter die Abschnitte haengt das Panel eine leere Zeile fuer "neu". Sie war
 * von Hand gebaut, und als der Stil um das Blatt wuchs, wuchs sie nicht mit:
 * die Tafel las einen Schluessel, den es an dieser einen Zeile nicht gab, und
 * die Warnung landete mitten im Formular. Derselbe Fehler wie heute frueh bei
 * den Abschnitten aus der Datenbank, nur eine Zeile weiter.
 *
 * Also entsteht sie jetzt aus complete() - derselben Quelle wie jede echte -
 * und dieser Test haelt fest, dass die beiden Gestalten sich nicht wieder
 * auseinanderleben.
 */

$echt = DesignSections::complete(sec_doc([['id' => 'ort', 'type' => 'location']]))['sections'][0];
$leer = DesignSections::leer();

assert_same(array_keys($echt), array_keys($leer), 'leer: dieselben Schluessel wie ein echter Abschnitt');
assert_same(array_keys($echt['style']), array_keys($leer['style']), 'leer: und derselbe Stil');
assert_same(array_keys($echt['permissions']), array_keys($leer['permissions']), 'leer: und dieselben Rechte');
assert_same('', $leer['id'], 'leer: ohne Kennung');
assert_same('', $leer['type'], 'leer: ohne Art');
assert_same(false, $leer['enabled'], 'leer: und ausgeschaltet');

/* --- Die Regel fuer die Zeichen steht einmal im Stilblock --- */

$grund = DesignSections::css(sec_doc([['id' => 'ort', 'type' => 'location']]), '.d-x');

assert_contains($grund, '.d-x .d-ikon{', 'css: die Grundregel des Zeichens steht');
assert_contains($grund, 'background-color:currentColor', 'css: es nimmt die Farbe des Abschnitts');
assert_contains($grund, 'mask-repeat:no-repeat', 'css: und liegt als Maske darueber');
assert_same(1, substr_count($grund, '.d-x .d-ikon{'), 'css: und zwar genau einmal');

/* --- Eine Ablaufzeile darf ein Zeichen tragen ---------------------------- */

$plan = DesignSections::programRows(['program' => [
    ['time' => '21:00', 'icon' => 'pasta', 'title' => 'Pasta Kesimi'],
    ['time' => '16:00', 'icon' => 'giris'],
    ['time' => '17:00', 'icon' => 'gibtesnicht', 'title' => 'Etwas'],
    ['time' => '18:00'],
    ['time' => '19:00', 'title' => 'Ohne Zeichen'],
]]);

assert_same(4, count($plan), 'programRows: eine Zeile ohne Titel UND ohne Zeichen faellt weg');
assert_same('pasta', $plan[0]['icon'], 'programRows: das Zeichen bleibt');
assert_same('giris', $plan[1]['icon'], 'programRows: eine Zeile darf allein vom Zeichen leben');
assert_same('', $plan[1]['title'], 'programRows: und traegt dann keinen eigenen Titel');
assert_same('', $plan[2]['icon'], 'programRows: ein unbekanntes Zeichen faellt still weg');
assert_same('', $plan[3]['icon'], 'programRows: ohne Angabe kein Zeichen');

/* --- Und im Druck steht die Maske, dazu der Vorschlag --- */

$gedruckt = DesignSections::html(
    sec_doc([['id' => 'ablauf', 'type' => 'program', 'enabled' => true]]),
    ['program' => [
        ['time' => '21:00', 'icon' => 'pasta'],
        ['time' => '22:00', 'icon' => 'dans', 'title' => 'Bizim dans'],
    ]],
    'de'
);

assert_contains($gedruckt, 'class="d-ikon"', 'html: die Zeile traegt ein Zeichen');
assert_contains($gedruckt, "mask-image:url('/assets/icons/pasta.svg')", 'html: und zwar seine Datei');
assert_contains($gedruckt, 'Tortenanschnitt', 'html: ohne eigenen Titel steht der Vorschlag da');
assert_contains($gedruckt, 'Bizim dans', 'html: mit eigenem Titel gewinnt das Paar');
assert_not_contains($gedruckt, 'Tanz</dd>', 'html: und der Vorschlag tritt zurueck');

/* --- Eine Ablaufzeile darf einen Satz tragen -----------------------------
 *
 * Ayhan hat eine fremde Einladung geschickt und die Zeichen darin gruen
 * eingekreist. Was daneben steht, gehoert zum selben Bild: unter jeder
 * Ueberschrift ein Satz - "Wir empfangen euch mit Aperitif in der Hand".
 * Eine Zeile aus Uhrzeit und zwei Woertern ist ein Fahrplan; mit dem Satz
 * wird sie eine Einladung.
 *
 * Leer bleibt leer: wer nichts schreibt, bekommt keinen leeren Absatz.
 */

$mitSatz = DesignSections::programRows(['program' => [
    ['time' => '14:00', 'icon' => 'giris', 'title' => 'Ankommen',
     'text' => '  Wir empfangen euch mit Aperitif in der Hand.  '],
    ['time' => '15:00', 'title' => 'Trauung'],
]]);

assert_same('Wir empfangen euch mit Aperitif in der Hand.', $mitSatz[0]['text'],
    'programRows: der Satz kommt an, ohne Rand');
assert_same('', $mitSatz[1]['text'], 'programRows: ohne Angabe kein Satz');
assert_same(DesignSections::PROGRAM_TEXT_LEN,
    mb_strlen(DesignSections::programRows(['program' => [
        ['title' => 'x', 'text' => str_repeat('ä', DesignSections::PROGRAM_TEXT_LEN + 40)],
    ]])[0]['text']),
    'programRows: ein zu langer Satz wird geschnitten, nicht abgelehnt');

$gedruckt2 = DesignSections::html(
    sec_doc([['id' => 'ablauf', 'type' => 'program', 'enabled' => true]]),
    ['program' => [
        ['time' => '14:00', 'icon' => 'giris', 'title' => 'Ankommen', 'text' => 'Mit Aperitif.'],
        ['time' => '15:00', 'title' => 'Trauung'],
    ]],
    'de'
);

assert_contains($gedruckt2, '<dl class="d-sec-plan">', 'html: die Liste behaelt ihren Namen');
assert_contains($gedruckt2, 'class="d-plan-zeit"', 'html: die Uhrzeit steht fuer sich');
assert_contains($gedruckt2, 'class="d-plan-rozet"', 'html: das Zeichen sitzt in einem Rozet');
assert_contains($gedruckt2, 'class="d-plan-titel"', 'html: der Titel steht fuer sich');
assert_contains($gedruckt2, 'Mit Aperitif.', 'html: und der Satz steht darunter');
assert_same(1, substr_count($gedruckt2, 'class="d-plan-text"'), 'html: nur die Zeile mit Satz bekommt einen');
// Ohne Zeichen kein Rozet - ein leerer Ring auf der Linie waere schlimmer als
// der schlichte Punkt, den die Regel ohnehin setzt.
assert_same(1, substr_count($gedruckt2, 'class="d-plan-rozet"'), 'html: ohne Zeichen kein Rozet');

/* --- Und der Strahl traegt den Ring --- */

$strahl = DesignSections::css(sec_doc([
    ['id' => 'ablauf', 'type' => 'program', 'variant' => 'zeitstrahl'],
]), '.d-x');

assert_contains($strahl, '.d-x .d-sec-plan .d-plan-rozet{', 'css: der Strahl kennt den Ring');
assert_contains($strahl, 'border-radius:50%', 'css: und der Ring ist rund');

/* --- Die Speisekarte -----------------------------------------------------
 *
 * "Davetiyede yemek menusu gosterilsin mi? Evet derse: Baslangic, Corba, Ana
 * yemek, Meze, Tatli, Icecek alanlari acilir. Kullanici ne doldurduysa
 * sadece onlar gorunur."
 *
 * Genau das, und ohne neuen Motor: die Gaenge sind Eingaben des Katalogs wie
 * jeder andere Text eines Abschnitts, und "nur was gefuellt ist" ist die
 * Regel, nach der jeder Abschnitt ohnehin gedruckt wird.
 *
 * Die Art traegt ihr Zeichen selbst - anders als beim Ablauf waehlt hier
 * niemand: eine Suppe ist eine Suppe.
 */

assert_true(in_array('menu', DesignSections::TYPES, true), 'menu: der Katalog kennt die Art');

$speise = sec_doc([['id' => 'menue', 'type' => 'menu', 'enabled' => true]]);

// Leer heisst nicht gedruckt - eine Ueberschrift ueber nichts.
assert_same([], DesignSections::visible($speise, []), 'menu: ohne einen einzigen Gang faellt sie weg');

$mitGang = ['sections' => ['menue' => ['suppe' => 'Mercimek Çorbası', 'dessert' => 'Cheesecake']]];
assert_same(1, count(DesignSections::visible($speise, $mitGang)), 'menu: ein Gang genuegt');

$karte = DesignSections::html($speise, $mitGang, 'de');

assert_contains($karte, 'Mercimek Çorbası', 'html: der gefuellte Gang steht da');
assert_contains($karte, 'Cheesecake', 'html: und der zweite auch');
assert_contains($karte, "mask-image:url('/assets/icons/corba.svg')", 'html: die Suppe bringt ihr Zeichen mit');
assert_contains($karte, 'Suppe', 'html: und die Art steht dabei');
// Was niemand gefuellt hat, steht auch nicht da - sonst waere die Karte eine
// Liste leerer Versprechen.
assert_not_contains($karte, 'Hauptgang', 'html: ein leerer Gang bleibt weg');
assert_not_contains($karte, 'Vorspeise', 'html: und der auch');

/* --- Die Kleiderordnung ---------------------------------------------------
 *
 * "Dress code gostermek istiyor musunuz? Evet: baslik, aciklama, onceden
 * hazirlanmis gorsel."
 *
 * Zwei Eingaben und ein Zeichen - mehr braucht es nicht. Das vorbereitete
 * Bild ist kein neues Feld: der Abschnitt hat seit heute frueh sein eigenes
 * Blatt, und genau das ist der Ort dafuer. Ein zweiter Weg fuer dasselbe
 * waere ein zweiter Ort, an dem man suchen muss.
 */

assert_true(in_array('dresscode', DesignSections::TYPES, true), 'dresscode: der Katalog kennt die Art');

$kleid = sec_doc([['id' => 'kleidung', 'type' => 'dresscode', 'enabled' => true]]);

assert_same([], DesignSections::visible($kleid, []), 'dresscode: ohne Angabe faellt sie weg');
assert_same(1, count(DesignSections::visible($kleid, ['sections' => ['kleidung' => ['code' => 'Black Tie']]])),
    'dresscode: der Code allein genuegt');
assert_same(1, count(DesignSections::visible($kleid, ['sections' => ['kleidung' => ['note' => 'Bitte festlich.']]])),
    'dresscode: und der Hinweis allein auch');

$gedruckt3 = DesignSections::html($kleid, ['sections' => ['kleidung' => [
    'code' => 'Elegant · Black Tie',
    'note' => 'Lange Kleider, dunkler Anzug — bequeme Schuhe fuer die Wiese.',
]]], 'de');

assert_contains($gedruckt3, 'Elegant · Black Tie', 'html: der Code steht da');
assert_contains($gedruckt3, 'bequeme Schuhe', 'html: und der Hinweis darunter');
assert_contains($gedruckt3, "mask-image:url('/assets/icons/dresscode.svg')", 'html: mit seinem Zeichen');

$nurCode = DesignSections::html($kleid, ['sections' => ['kleidung' => ['code' => 'Black Tie']]], 'de');
assert_not_contains($nurCode, 'd-dress-note', 'html: ohne Hinweis kein leerer Absatz');
