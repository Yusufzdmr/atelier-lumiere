<?php
declare(strict_types=1);

use Atelier\Design;

/* --- Dynamische Felder werden eingesetzt --- */

$doc = ['id' => 'x', 'layers' => [
    ['id' => 'namen', 'type' => 'text', 'bind' => 'couple_names'],
    ['id' => 'ort', 'type' => 'text', 'bind' => 'location_name'],
]];

$werte = ['couple_names' => 'Ayşe & Mehmet', 'location_name' => 'Schloss Hohenstein'];
$html = Design::html($doc, $werte, 'de');

assert_contains($html, 'Ayşe &amp; Mehmet', 'html: bind wird eingesetzt');
assert_contains($html, 'Schloss Hohenstein', 'html: zweiter bind wird eingesetzt');
assert_contains($html, 'd-el-namen', 'html: Element traegt seine Klasse');

/* --- Ohne bind gilt der feste Text, in der Sprache der Seite --- */

$doc = ['id' => 'x', 'layers' => [
    ['id' => 'gruss', 'type' => 'text', 'text' => ['de' => 'Wir heiraten', 'en' => 'We are getting married']],
]];

assert_contains(Design::html($doc, [], 'de'), 'Wir heiraten', 'html: fester Text auf Deutsch');
assert_contains(Design::html($doc, [], 'en'), 'We are getting married', 'html: fester Text auf Englisch');

/* --- Ein Name ist ein Feld, kein Markup --- */

$doc = ['id' => 'x', 'layers' => [['id' => 'namen', 'type' => 'text', 'bind' => 'bride_name']]];
$html = Design::html($doc, ['bride_name' => '<script>alert(1)</script>'], 'de');

assert_not_contains($html, '<script>', 'html: Eingaben werden maskiert');
assert_contains($html, '&lt;script&gt;', 'html: und zwar sichtbar maskiert');

/* --- Unbekannter bind wird leer, nicht zum Namen des Feldes --- */

$doc = ['id' => 'x', 'layers' => [['id' => 'a', 'type' => 'text', 'bind' => 'gibt_es_nicht']]];
$html = Design::html($doc, [], 'de');

assert_not_contains($html, 'gibt_es_nicht', 'html: unbekannter bind wird nicht ausgegeben');

/* --- Bilder: nur Pfade, die wir selbst vergeben --- */

$doc = ['id' => 'x', 'layers' => [
    ['id' => 'gut', 'type' => 'image', 'src' => '/uploads/blume.webp'],
    ['id' => 'fremd', 'type' => 'image', 'src' => 'https://beispiel.de/bild.jpg'],
    ['id' => 'hoch', 'type' => 'image', 'src' => '/uploads/../../config.php'],
]];
$html = Design::html($doc, [], 'de');

assert_contains($html, '/uploads/blume.webp', 'html: eigener Pfad wird gezeigt');
assert_not_contains($html, 'beispiel.de', 'html: fremder Host wird verworfen');
assert_not_contains($html, '..', 'html: Verzeichniswechsel wird verworfen');

/* --- Kein Skript, keine Ereignisse: die CSP bleibt eng --- */

assert_not_contains($html, '<script', 'html: erzeugt keine Skriptbloecke');
assert_not_contains($html, 'onclick', 'html: erzeugt keine Ereignisse');

/* --- bindValues: aus den Feldern einer Einladung --- */

$werte = Design::bindValues([
    'bride' => 'Ayşe', 'groom' => 'Mehmet', 'date' => '2027-09-12',
    'time' => '18:00', 'venue' => 'Schloss Hohenstein', 'address' => 'Hauptstr. 1',
    'message' => 'Wir freuen uns', 'hashtag' => '#AyseMehmet',
], 'de');

assert_same('Ayşe & Mehmet', $werte['couple_names'], 'bindValues: Namen werden verbunden');
assert_same('Ayşe', $werte['bride_name'], 'bindValues: Braut');
assert_same('Mehmet', $werte['groom_name'], 'bindValues: Braeutigam');
assert_same('AM', $werte['initials'], 'bindValues: Initialen');
assert_same('18:00', $werte['wedding_time'], 'bindValues: Uhrzeit');
assert_same('Schloss Hohenstein', $werte['location_name'], 'bindValues: Ort');
assert_same('Hauptstr. 1', $werte['location_address'], 'bindValues: Adresse');
assert_same('Wir freuen uns', $werte['invitation_text'], 'bindValues: Text');
assert_true($werte['wedding_date'] !== '', 'bindValues: Datum wird ausgeschrieben');
assert_true(str_contains($werte['wedding_date'], '2027'), 'bindValues: Datum traegt das Jahr');

/* --- Fehlt ein Name, entsteht kein einsames Kaufmanns-Und --- */

$werte = Design::bindValues(['bride' => 'Ayşe', 'groom' => ''], 'de');
assert_same('Ayşe', $werte['couple_names'], 'bindValues: ohne zweiten Namen kein &');

/* --- Jeder bind aus der Liste kommt vor --- */

$werte = Design::bindValues([], 'de');
foreach (Design::BINDS as $bind) {
    assert_true(array_key_exists($bind, $werte), 'bindValues: ' . $bind . ' wird geliefert');
}

/* --- warnings: was ein Design noch braucht --- */

$meldungen = Design::warnings(['id' => 'x', 'layers' => [
    ['id' => 'a', 'type' => 'text', 'bind' => 'gibt_es_nicht'],
    ['id' => 'b', 'type' => 'image', 'src' => ''],
    ['id' => 'c', 'type' => 'text', 'style' => ['color' => 'fehlt']],
    ['id' => 'd', 'type' => 'text', 'style' => ['font' => 'fehlt']],
]]);

$arten = array_column($meldungen, 'kind');

assert_true(in_array('unknown_bind', $arten, true), 'warnings: unbekannter bind wird gemeldet');
assert_true(in_array('missing_src', $arten, true), 'warnings: fehlendes Bild wird gemeldet');
assert_true(in_array('unknown_color', $arten, true), 'warnings: fehlende Farbmarke wird gemeldet');
assert_true(in_array('unknown_font', $arten, true), 'warnings: fehlende Schriftmarke wird gemeldet');

/* --- Ein sauberes Design meldet nichts --- */

$sauber = Design::warnings([
    'id'      => 'x',
    'palette' => ['accent' => ['value' => '#B08D57']],
    'fonts'   => ['display' => ['family' => 'Cormorant Garamond']],
    'layers'  => [
        ['id' => 'a', 'type' => 'text', 'bind' => 'couple_names',
         'style' => ['color' => 'accent', 'font' => 'display']],
        ['id' => 'b', 'type' => 'image', 'src' => '/uploads/blume.webp'],
    ],
]);

assert_same([], $sauber, 'warnings: sauberes Design meldet nichts');

/* --- safeSrc: was wir nicht selbst vergeben haben, kommt nicht durch --- */

$fremd = ['id' => 'x', 'type' => 'image'];
$pruef = static function (string $src) use ($fremd): string {
    return Design::html(['id' => 'd', 'layers' => [$fremd + ['src' => $src]]], [], 'de');
};

assert_same('', $pruef('/uploads/%2e%2e/config.php'), 'safeSrc: prozentkodierter Wechsel wird verworfen');
assert_same('', $pruef('/uploads/%252e%252e/config.php'), 'safeSrc: doppelt kodiert wird verworfen');
assert_same('', $pruef("/uploads/a\x00.png"), 'safeSrc: Nullbyte wird verworfen');
assert_same('', $pruef("/uploads/a\tb.png"), 'safeSrc: Tabulator wird verworfen');
assert_same('', $pruef('/uploads/a b.png'), 'safeSrc: Leerzeichen wird verworfen');
assert_same('', $pruef('//evil.example/x.png'), 'safeSrc: fremder Host wird verworfen');
assert_same('', $pruef('/UPLOADS/x.png'), 'safeSrc: falsche Schreibung wird verworfen');

assert_contains($pruef('/uploads/blume.webp'), '/uploads/blume.webp', 'safeSrc: eigener Upload kommt durch');
assert_contains($pruef('/assets/designs/elysee-1.svg'), '/assets/designs/elysee-1.svg', 'safeSrc: eigenes Asset kommt durch');

/* --- Der Wochentag ist ein eigenes Feld: Dates::long() kennt ihn nicht --- */

$werte = Design::bindValues(['date' => '2027-06-20'], 'de');

assert_true(str_contains($werte['wedding_date'], '2027'), 'bindValues: Datum traegt das Jahr');
assert_true(!str_contains($werte['wedding_date'], 'Sonntag'), 'bindValues: das Datum traegt den Tag NICHT');
assert_same('Sonntag', $werte['wedding_weekday'], 'bindValues: der Wochentag kommt getrennt');
assert_same('', Design::bindValues([], 'de')['wedding_weekday'], 'bindValues: ohne Datum kein Wochentag');

/* --- Nach Ort filtern --- */

$doc = ['id' => 'x', 'layers' => [
    ['id' => 'a', 'type' => 'text', 'spot' => 'page', 'text' => ['de' => 'SEITE']],
    ['id' => 'b', 'type' => 'text', 'spot' => 'card', 'text' => ['de' => 'KARTE']],
    ['id' => 'c', 'type' => 'text', 'spot' => 'envelope', 'text' => ['de' => 'KUVERT']],
]];

$alle = Design::html($doc, [], 'de');
assert_contains($alle, 'SEITE', 'html: ohne Filter kommt alles');
assert_contains($alle, 'KARTE', 'html: ohne Filter kommt alles (2)');

$nurKarte = Design::html($doc, [], 'de', 'card');
assert_contains($nurKarte, 'KARTE', 'html: Filter laesst den Ort durch');
assert_not_contains($nurKarte, 'SEITE', 'html: Filter haelt andere Orte zurueck');
assert_not_contains($nurKarte, 'KUVERT', 'html: Filter haelt andere Orte zurueck (2)');

assert_same('', Design::html($doc, [], 'de', 'gibtesnicht'), 'html: unbekannter Ort ist leer');

/* --- Video: wird gezeichnet, aber nie von allein gestartet --- */

$doc = ['id' => 'x', 'layers' => [
    ['id' => 'film', 'type' => 'video', 'spot' => 'page',
     'src' => '/uploads/film.mp4', 'poster' => '/uploads/film.jpg'],
]];
$html = Design::html($doc, [], 'de');

assert_contains($html, '<video', 'html: video wird gezeichnet');
assert_contains($html, '/uploads/film.mp4', 'html: die Quelle steht da');
assert_contains($html, 'poster="/uploads/film.jpg"', 'html: der Poster steht da');
assert_contains($html, 'muted', 'html: video ist stumm');
assert_contains($html, 'playsinline', 'html: video laeuft im Fluss, nicht im Vollbild');
assert_contains($html, 'loop', 'html: video laeuft in der Schleife');
assert_contains($html, 'd-spot-page', 'html: video traegt seinen Ort');

// Das Wichtigste: OHNE autoplay. Sonst dreht sich der Film hinter dem
// geschlossenen Kuvert, sieht ihn niemand, und das Handy zahlt.
assert_not_contains($html, 'autoplay', 'html: video startet NICHT von allein');

/* --- Video ohne Quelle: der Knoten bleibt, wie bei photo --- */

$leer = Design::html(['id' => 'x', 'layers' => [
    ['id' => 'film', 'type' => 'video', 'src' => ''],
]], [], 'de');

assert_contains($leer, '<video', 'html: leeres Video behaelt seinen Knoten');
assert_contains($leer, 'hidden', 'html: und ist versteckt');

/* --- Fremde Quelle faellt weg --- */

$fremd = Design::html(['id' => 'x', 'layers' => [
    ['id' => 'film', 'type' => 'video', 'src' => 'https://beispiel.de/f.mp4'],
]], [], 'de');

assert_not_contains($fremd, 'beispiel.de', 'html: fremder Host wird verworfen');
