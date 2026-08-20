<?php
declare(strict_types=1);

use Atelier\InvitationsV2;

/*
 * Yayin sonrasi duzenleme.
 *
 * Drei Regeln, die dieser Bildschirm braucht, stehen als reine Funktionen im
 * Modell und nicht im Controller: sie sind die Sicherung einer Seite, die
 * Schreibrechte vergibt, und eine Sicherung, die nur ueber HTTP pruefbar ist,
 * wird nicht geprueft.
 */

/* --- Der Schluessel --- */

$echt = str_repeat('a', 32);

assert_true(InvitationsV2::keyOk(['manageKey' => $echt], $echt), 'keyOk: der richtige Schluessel oeffnet');
assert_true(!InvitationsV2::keyOk(['manageKey' => $echt], str_repeat('b', 32)), 'keyOk: ein falscher Schluessel oeffnet nicht');

// hash_equals('', '') ist WAHR. Eine Einladung ohne manageKey stuende sonst
// jedem offen, der die Adresse mit einem leeren letzten Stueck aufruft.
assert_true(!InvitationsV2::keyOk(['manageKey' => ''], ''), 'keyOk: der leere gespeicherte Schluessel oeffnet niemandem');
assert_true(!InvitationsV2::keyOk([], ''), 'keyOk: ohne manageKey oeffnet nichts');
assert_true(!InvitationsV2::keyOk(['manageKey' => $echt], ''), 'keyOk: ein leerer mitgebrachter Schluessel oeffnet nicht');

// manageKey[]=x aus einer von Hand gestellten Anfrage darf keinen TypeError
// werfen - dieselbe Klasse Fehler wie die drei aus Phase 3C2.
assert_true(!InvitationsV2::keyOk(['manageKey' => ['a']], $echt), 'keyOk: ein Feld statt eines Schluessels wird abgelehnt');

/* --- Der Stand: zwei Tabs --- */

$stand = '2026-08-20T13:00:00+03:00';

assert_true(!InvitationsV2::stale(['updatedAt' => $stand], $stand), 'stale: derselbe Stand ist nicht veraltet');
assert_true(InvitationsV2::stale(['updatedAt' => $stand], '2026-08-20T12:00:00+03:00'), 'stale: ein aelterer Stand ist veraltet');

// Verglichen wird auf Gleichheit, nicht auf "kleiner": zwei Staende mit
// verschiedenem Zonenversatz waeren als Zeichenkette falsch geordnet, und ein
// Stand, den das Formular gar nicht mitbrachte, ist auch keiner.
assert_true(InvitationsV2::stale(['updatedAt' => $stand], ''), 'stale: ein fehlender mitgebrachter Stand ist veraltet');

// Eine Einladung von vor dieser Phase hat keinen Stand. Gegen nichts laesst
// sich nicht vergleichen - sonst waere die erste Bearbeitung jeder alten
// Einladung unmoeglich.
assert_true(!InvitationsV2::stale([], 'egal'), 'stale: ohne gespeicherten Stand wird nicht abgelehnt');
assert_true(!InvitationsV2::stale(['updatedAt' => ['a']], 'egal'), 'stale: ein Feld statt eines Standes wird uebergangen');

/* --- Der Preis aus Spec §4 --- */

assert_true(InvitationsV2::canEditDesign(['wahl' => ['palette' => []]]), 'canEditDesign: mit wahl ist das Design offen');
assert_true(InvitationsV2::canEditDesign(['wahl' => []]), 'canEditDesign: eine leere Wahl ist auch eine Wahl');

// Ohne wahl ist der Sockel bereits personalisiert. Eine neue Auswahl darauf
// waere verlustbehaftet - eine versteckte Ebene kaeme nicht zurueck.
assert_true(!InvitationsV2::canEditDesign([]), 'canEditDesign: ohne wahl bleibt das Design zu');
assert_true(!InvitationsV2::canEditDesign(['wahl' => 'x']), 'canEditDesign: eine Zeichenkette ist keine Wahl');

/* --- Ab hier braucht es die Datenbank --- */

if (!needs_db()) {
    echo "  (übersprungen: keine config.php, kein Datenbanktest)\n";
    return;
}

// bin/test.php hat den Autoloader schon registriert und View.php schon per
// require geladen (nicht require_once) - src/bootstrap.php wuerde View.php ein
// zweites Mal einbinden und e() doppelt erklaeren. Deshalb hier nur das eine
// Stueck aus bootstrap.php, das wirklich fehlt.
Atelier\Config::load(dirname(__DIR__) . '/config.php');

$slug = 'testedit-' . bin2hex(random_bytes(4));

Atelier\Db::run('DELETE FROM invitations_v2 WHERE slug = ?', [$slug]);

$sockel = ['id' => 'test', 'slug' => 'test', 'palette' => [], 'fonts' => [], 'layers' => [
    ['id' => 'namen', 'type' => 'text', 'bind' => 'couple_names'],
]];

InvitationsV2::create($slug, 'test', $sockel, ['slug' => $slug, 'bride' => 'Marie', 'manageKey' => $echt]);

/* --- saveData schreibt data und ruehrt design_snapshot nicht an --- */

$vorher = InvitationsV2::find($slug);
InvitationsV2::saveData($slug, ['slug' => $slug, 'bride' => 'Maria', 'manageKey' => $echt]);
$nachher = InvitationsV2::find($slug);

assert_same('Maria', $nachher['data']['bride'] ?? '', 'saveData: die Daten sind geschrieben');
assert_same($vorher['design_snapshot'], $nachher['design_snapshot'], 'saveData: der Schnappschuss ist unberuehrt');
assert_same($vorher['design_id'], $nachher['design_id'], 'saveData: die Kennung des Designs ist unberuehrt');
assert_same($vorher['created_at'], $nachher['created_at'], 'saveData: der Zeitpunkt der Anlage ist unberuehrt');

/* --- Ein leerer Slug schreibt nichts --- */

InvitationsV2::saveData('', ['bride' => 'Niemand']);
assert_same('Maria', (InvitationsV2::find($slug)['data']['bride'] ?? ''), 'saveData: ohne Slug wird nichts geschrieben');

Atelier\Db::run('DELETE FROM invitations_v2 WHERE slug = ?', [$slug]);
