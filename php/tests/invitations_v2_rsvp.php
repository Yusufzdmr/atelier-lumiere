<?php
declare(strict_types=1);

use Atelier\InvitationsV2;

/*
 * Eine Antwort je Name.
 *
 * Die Frage des Paares ist "wer kommt", und darauf gehoert eine einzige
 * Antwort. Wer seine Meinung aendert, ersetzt seine erste - stuenden beide
 * untereinander, muesste das Paar aus dem Datum schliessen, welche gilt.
 *
 * Der Preis steht in der Spec (§5) und wird hier nicht wegdiskutiert: zwei
 * echte Gaeste desselben Namens ueberschreiben einander. Der Name ist bis
 * Phase D die einzige Kennung, die wir haben.
 */

/* --- Der Vergleichsname: rein, kein Datenbanktest --- */

assert_same('mehmet', InvitationsV2::rsvpKey('  Mehmet '), 'rsvpKey: Rand und Grossschreibung fallen weg');
assert_same(InvitationsV2::rsvpKey('MEHMET'), InvitationsV2::rsvpKey('mehmet'), 'rsvpKey: zwei Schreibweisen, ein Gast');
assert_same('', InvitationsV2::rsvpKey('   '), 'rsvpKey: nur Leerzeichen ist kein Name');

// mb_strtolower und nicht strtolower: ein tuerkischer oder deutscher Name
// bliebe sonst in der Mitte grossgeschrieben und zwei Schreibweisen desselben
// Gastes waeren wieder zwei Gaeste.
assert_same('ayşe', InvitationsV2::rsvpKey('Ayşe'), 'rsvpKey: mehrbytige Namen werden kleingeschrieben');
assert_same('müller', InvitationsV2::rsvpKey('Müller'), 'rsvpKey: Umlaute werden kleingeschrieben');

// Zwei verschiedene Gaeste bleiben zwei.
assert_true(InvitationsV2::rsvpKey('Mehmet') !== InvitationsV2::rsvpKey('Ahmet'), 'rsvpKey: verschiedene Namen bleiben verschieden');

/* --- Ab hier braucht es die Datenbank --- */

if (!needs_db()) {
    echo "  (übersprungen: keine config.php, kein Datenbanktest)\n";
    return;
}

// bin/test.php hat den Autoloader schon registriert und View.php schon per
// require geladen (nicht require_once) - src/bootstrap.php wuerde View.php
// ein zweites Mal einbinden und e() doppelt erklaeren. Deshalb hier nur das
// eine Stueck aus bootstrap.php nachholen, das wirklich fehlt: die
// Konfiguration fuer die Datenbankverbindung.
Atelier\Config::load(dirname(__DIR__) . '/config.php');

$slug  = 'testrsvp-a';
$slug2 = 'testrsvp-b';

// Sauber anfangen, falls ein frueherer Lauf abgebrochen ist.
Atelier\Db::run('DELETE FROM rsvps WHERE slug IN (?, ?)', [$slug, $slug2]);

/* --- Die erste Antwort legt eine Zeile an --- */

InvitationsV2::saveRsvp($slug, [
    'slug' => $slug, 'name' => 'Mehmet', 'coming' => true,
    'count' => 2, 'note' => 'Wir freuen uns', 'at' => '2027-01-01T10:00:00+01:00',
]);

$eine = InvitationsV2::rsvps($slug);
assert_same(1, count($eine), 'saveRsvp: die erste Antwort legt eine Zeile an');
assert_same('Mehmet', $eine[0]['name'], 'saveRsvp: gespeichert wird, was der Gast geschrieben hat');
assert_same(2, $eine[0]['count'], 'saveRsvp: die Anzahl kommt zurueck');

/* --- Die zweite unter demselben Namen ersetzt sie --- */

// Andere Schreibweise, anderer Rand: derselbe Gast. Genau das ist der Punkt
// von rsvpKey - waere hier auf den rohen Namen verglichen worden, stuenden
// jetzt zwei Zeilen da und dieser Test faende sie.
InvitationsV2::saveRsvp($slug, [
    'slug' => $slug, 'name' => '  mehmet ', 'coming' => false,
    'count' => 1, 'note' => 'Leider doch nicht', 'at' => '2027-01-02T10:00:00+01:00',
]);

$zwei = InvitationsV2::rsvps($slug);
assert_same(1, count($zwei), 'saveRsvp: die zweite Antwort ersetzt, sie haengt nicht an');
assert_same(false, $zwei[0]['coming'], 'saveRsvp: die neuere Antwort gilt');
assert_same('Leider doch nicht', $zwei[0]['note'], 'saveRsvp: auch die Notiz ist die neuere');
assert_same('  mehmet ', $zwei[0]['name'], 'saveRsvp: gespeichert bleibt die Schreibweise des Gastes');

/* --- Ein anderer Name ist ein anderer Gast --- */

// Das Ueberschreiben darf nicht zu weit beissen: sonst haette das Paar am
// Ende eine Liste mit genau einem Namen darauf.
InvitationsV2::saveRsvp($slug, [
    'slug' => $slug, 'name' => 'Ayşe', 'coming' => true,
    'count' => 3, 'note' => '', 'at' => '2027-01-03T10:00:00+01:00',
]);

assert_same(2, count(InvitationsV2::rsvps($slug)), 'saveRsvp: ein anderer Name oeffnet eine neue Zeile');

/* --- Zwei Einladungen kommen einander nicht ins Gehege --- */

InvitationsV2::saveRsvp($slug2, [
    'slug' => $slug2, 'name' => 'Mehmet', 'coming' => true,
    'count' => 1, 'note' => '', 'at' => '2027-01-04T10:00:00+01:00',
]);

assert_same(2, count(InvitationsV2::rsvps($slug)), 'saveRsvp: der fremde Slug laesst diese Liste in Ruhe');
assert_same(1, count(InvitationsV2::rsvps($slug2)), 'saveRsvp: derselbe Name unter anderem Slug ist ein anderer Gast');
assert_same('Mehmet', InvitationsV2::rsvps($slug2)[0]['name'], 'rsvps: und er steht in seiner eigenen Liste');

/* --- rsvps() gibt nur diesen Slug zurueck --- */

// Die Liste wird von einer Seite gelesen, die genau einen Schluessel geprueft
// hat. Gaebe rsvps() jemals mehr zurueck als den geprueften Slug, waere der
// gepruefte Schluessel wertlos.
foreach (InvitationsV2::rsvps($slug) as $antwort) {
    assert_same($slug, $antwort['slug'], 'rsvps: jede Zeile gehoert zum abgefragten Slug');
}

// Eine Einladung ohne eine einzige Antwort ist der Normalfall am ersten Tag -
// und der haeufigste Weg, eine Leseansicht zum Absturz zu bringen.
assert_same([], InvitationsV2::rsvps('testrsvp-gibtesnicht'), 'rsvps: ohne Antworten ein leeres Feld, kein Fehler');
assert_same([], InvitationsV2::rsvps(''), 'rsvps: ohne Slug ein leeres Feld - nie die Antworten aller Einladungen');

/* --- Aufraeumen --- */

Atelier\Db::run('DELETE FROM rsvps WHERE slug IN (?, ?)', [$slug, $slug2]);
assert_same(0, count(InvitationsV2::rsvps($slug)), 'aufgeraeumt: die Testzeilen sind wieder weg');
