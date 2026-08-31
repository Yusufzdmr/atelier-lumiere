<?php

declare(strict_types=1);

use Atelier\Packages;

/*
 * Der Preisrechner auf der Preisseite.
 *
 * "Bu ek islerde yani pakete gelecek ilave bolumu - tikladikca paket fiyati
 * oynamasi lazim. (...) Odeme sistemi olmayacak ama musteri ne odeyecek, ne
 * alacak gorsun, forma eklensin."
 *
 * Die Preise stehen als TEXT im Inhaltsdokument, weil der Kunde sie in ein
 * Feld tippt. Also wird gelesen, was da steht - und dabei entscheidet sich
 * alles: was sich nicht sicher lesen laesst, darf nicht geraten werden.
 */

/* --- Was eine Zahl ist --- */

assert_same(69000, Packages::amount('690 €'), 'Preis: der einfache Fall');
assert_same(189000, Packages::amount('1.890 €'), 'Preis: deutscher Tausenderpunkt');
assert_same(45000, Packages::amount('+ 450 €'), 'Preis: das Plus ist Anzeige, keine Anweisung');
assert_same(49000, Packages::amount('490'), 'Preis: auch ohne Waehrungszeichen');
assert_same(123450, Packages::amount('1.234,50 €'), 'Preis: Komma sind Cent');
assert_same(0, Packages::amount('0 €'), 'Preis: null ist ein Preis');

// Das geschuetzte Leerzeichen kommt aus Word und aus jedem Einfuegen ins
// Panel - es sieht aus wie ein Leerzeichen und ist keines.
assert_same(69000, Packages::amount("690\u{00a0}€"), 'Preis: auch mit geschuetztem Leerzeichen');

/* --- Und was keine ist --- */

/*
 * Der Kern der Sache. "ab 250 €" ist kein Preis, sondern ein Satz ueber einen
 * Preis; "auf Anfrage" ist die ausdrueckliche Weigerung, einen zu nennen. Wer
 * daraus 250 macht, zeigt eine Summe, die niemand versprochen hat - und diese
 * Sorte Fehler ist hier die teuerste, weil sie nicht auffaellt.
 */
assert_same(null, Packages::amount('ab 250 €'), 'Preis: ein Wort davor macht es zu keiner Zahl');
assert_same(null, Packages::amount('auf Anfrage'), 'Preis: und eine Weigerung ist keine Zahl');
assert_same(null, Packages::amount('nach Absprache'), 'Preis: auch diese nicht');
assert_same(null, Packages::amount(''), 'Preis: leer ist nichts');
assert_same(null, Packages::amount('690-890 €'), 'Preis: eine Spanne ist keine Zahl');

/* --- Wie eine Summe aussieht --- */

/*
 * Deutsche Schreibweise in allen Sprachen: die Posten stehen als Text im
 * Dokument und sind dort deutsch geschrieben. Eine Summe in englischer
 * Schreibweise stuende neben ihren eigenen Posten und saehe aus wie ein
 * Fehler.
 */
assert_same('690 €', Packages::money(69000), 'Summe: volle Euro ohne Nachkomma');
assert_same('1.890 €', Packages::money(189000), 'Summe: mit Tausenderpunkt');
assert_same('12.345 €', Packages::money(1234500), 'Summe: und bei fuenf Stellen auch');
assert_same('1.234,50 €', Packages::money(123450), 'Summe: Cent nur, wenn es welche gibt');
assert_same('0 €', Packages::money(0), 'Summe: null bleibt null');

/* --- Die Auswahl --- */

$pakete = [
    ['name' => ['de' => 'Klein', 'en' => 'Small'], 'price' => '690 €'],
    ['name' => ['de' => 'Gross', 'en' => 'Large'], 'price' => '1.890 €'],
    ['name' => ['de' => 'Ganz nach Wunsch'], 'price' => 'auf Anfrage'],
];
$zusatz = [
    ['name' => ['de' => 'Zweiter Fotograf'], 'price' => '+ 450 €'],
    ['name' => ['de' => 'Album'], 'price' => '+ 490 €'],
    ['name' => ['de' => 'Drohne'], 'price' => 'auf Anfrage'],
];

$eins = Packages::summary($pakete, $zusatz, '1', ['0'], 'de');
assert_same(2, count($eins['lines']), 'Auswahl: ein Paket und ein Zusatz');
assert_same(234000, $eins['total'], 'Auswahl: und sie werden addiert');
assert_true(!$eins['offen'], 'Auswahl: nichts bleibt offen');
assert_same('Gross', $eins['lines'][0]['label'], 'Auswahl: der Name kommt aus der Sprache');

/*
 * Ein Posten ohne rechenbaren Preis macht die Summe nicht falsch, sondern
 * unvollstaendig - und das muss die Seite sagen koennen. Eine Summe, die
 * schweigend zu klein ist, waere die schlechtere Antwort als gar keine.
 */
$offen = Packages::summary($pakete, $zusatz, '0', ['2'], 'de');
assert_same(69000, $offen['total'], 'Offen: der Rest wird trotzdem gerechnet');
assert_true($offen['offen'], 'Offen: und die Seite erfaehrt es');
assert_same(2, count($offen['lines']), 'Offen: der Posten steht trotzdem in der Liste');

// Was es nicht gibt, gibt es nicht: die Nummern kommen aus der Adresszeile.
$quatsch = Packages::summary($pakete, $zusatz, '99', ['77', 'abc', '-1'], 'de');
assert_same([], $quatsch['lines'], 'Fremde Nummern: fallen weg');
assert_same(0, $quatsch['total'], 'Fremde Nummern: und rechnen nichts');

// Dieselbe Nummer zweimal ist keine Bestellung von zwei Stueck.
$doppelt = Packages::summary($pakete, $zusatz, '', ['0', '0'], 'de');
assert_same(1, count($doppelt['lines']), 'Doppelte Nummer: zaehlt einmal');
assert_same(45000, $doppelt['total'], 'Doppelte Nummer: und einmal im Preis');

// Ohne Auswahl gar nichts - kein leeres Paket, keine Null als Preis.
$nichts = Packages::summary($pakete, $zusatz, '', [], 'de');
assert_same([], $nichts['lines'], 'Ohne Auswahl: keine Zeile');
assert_same('', Packages::asText($nichts, 'de'), 'Ohne Auswahl: kein Satz fuer die Nachricht');

/* --- Der Satz, der im Nachrichtenfeld landet --- */

/*
 * Ins Nachrichtenfeld und nicht in ein verstecktes: dort steht die Auswahl im
 * Mailtext, in der Liste der Anfragen und vor den Augen dessen, der sie
 * abschickt - und er kann sie aendern.
 */
$text = Packages::asText($eins, 'de');
assert_contains($text, 'Gross: 1.890 €', 'Nachricht: das Paket steht da');
assert_contains($text, 'Zweiter Fotograf: + 450 €', 'Nachricht: der Zusatz auch');
assert_contains($text, 'Summe: 2.340 €', 'Nachricht: und die Summe');

$textOffen = Packages::asText($offen, 'de');
assert_contains($textOffen, 'Posten ohne festen Preis sind nicht eingerechnet.',
    'Nachricht: eine unvollstaendige Summe sagt es dazu');

/*
 * "Ohne festen Preis" und nicht "auf Anfrage": der Posten, an dem es hier
 * zuerst auffiel, heisst "Anfahrt über 60 km - 0,40 €/km". Das IST ein Preis,
 * nur keiner, den man addieren kann.
 */
$km = Packages::summary([], [['name' => ['de' => 'Anfahrt'], 'price' => '0,40 €/km']], '', ['0'], 'de');
assert_true($km['offen'], 'Kilometerpreis: laesst sich nicht addieren');
assert_same(0, $km['total'], 'Kilometerpreis: und wird nicht geraten');

assert_contains(Packages::asText($eins, 'tr'), 'Toplam:', 'Nachricht: und sie spricht Tuerkisch');
assert_contains(Packages::asText($eins, 'en'), 'Total:', 'Nachricht: und Englisch');

/* --- Die Seite bietet es an --- */

$seite = (string) file_get_contents(__DIR__ . '/../templates/pages/prices.php');

assert_contains($seite, '<form method="get"', 'Seite: ein Formular, das ohne Skript funktioniert');
assert_contains($seite, 'name="paket"', 'Seite: das Paket ist eine Wahl');
assert_contains($seite, 'name="extra[]"', 'Seite: die Zusaetze sind Kaestchen');
assert_contains($seite, 'data-preisrechner', 'Seite: und das Skript findet es');

/*
 * Ein Posten ohne rechenbaren Preis traegt kein data-cent - er ist waehlbar
 * und faellt aus der Summe.
 */
assert_contains($seite, "\$cent === null ? '' : 'data-cent=\"'",
    'Seite: nur rechenbare Posten tragen ihre Zahl');

$skript = (string) file_get_contents(__DIR__ . '/../public/assets/prices.js');
assert_contains($skript, 'data-preisrechner', 'Skript: es haengt am Formular');
assert_contains($skript, 'parseInt(wert, 10)', 'Skript: gerechnet wird in ganzen Cent');
assert_contains($skript, 'ohnePreis = true', 'Skript: und ein Posten ohne Zahl faellt auf');

$steuer = (string) file_get_contents(__DIR__ . '/../src/Controllers/PageController.php');
assert_contains($steuer, "'/assets/prices.js'", 'Seite: das Skript wird geladen');
assert_contains($steuer, "\$values['message'] = \$text;",
    'Kontakt: die Auswahl landet im Nachrichtenfeld');

$panel = (string) file_get_contents(__DIR__ . '/../src/Controllers/ContentAdminController.php');
assert_contains($panel, 'Packages::amount($preis) !== null',
    'Panel: wer den Preis eintippt, erfaehrt, dass er nicht mitgerechnet wird');

/* --- Und die Worte dafuer stehen in allen drei Sprachen --- */

$dict = require __DIR__ . '/../data/dict.php';
foreach (['de', 'en', 'tr'] as $sprache) {
    foreach (['choose', 'sumTitle', 'sumTotal', 'sumEmpty', 'sumOpen'] as $wort) {
        assert_true(($dict[$sprache]['prices'][$wort] ?? '') !== '',
            'Sprache: ' . $sprache . '.prices.' . $wort . ' steht da');
    }
}
