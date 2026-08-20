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

use Atelier\Design;
use Atelier\DesignSections;
use Atelier\DesignWizard;

/*
 * Der eingefrorene Sockel.
 *
 * Ab dieser Phase haelt design_snapshot die UNpersonalisierte Vorlage, und
 * die Wahl des Kunden liegt daneben in data['wahl']. Gedruckt wird
 * personalize(snapshot, wahl) - bei jedem Aufruf neu.
 *
 * Der Rueckwaertsvertrag dieser Aenderung ist eine Behauptung ueber
 * personalize(), und sie wird hier gemessen und nicht geglaubt: eine alte
 * Einladung traegt einen bereits personalisierten Sockel und KEIN wahl, also
 * laeuft auf ihr personalize(sockel, []) - und das muss die Identitaet sein,
 * sonst aendert sich das Aussehen jeder heute veroeffentlichten Einladung.
 */

/** Eine Vorlage mit genau den Rechten, die diese Tests brauchen. */
function edit_doc(): array
{
    return [
        'id' => 'test', 'slug' => 'test',
        'palette' => [
            'accent' => ['value' => '#B08D57', 'customer' => true],
            'bg'     => ['value' => '#EFE7DC', 'customer' => false],
        ],
        'fonts'  => [],
        'layers' => [
            ['id' => 'namen', 'type' => 'text', 'bind' => 'couple_names'],
            ['id' => 'zier', 'type' => 'shape',
             'permissions' => ['edit' => true, 'color' => true, 'hide' => true]],
        ],
    ];
}

$vorlage = edit_doc();

// So sieht der Sockel aus, den publish() ab jetzt einfriert.
$sockel = DesignSections::complete(Design::complete($vorlage));

// GENAU der Tausch, den show() macht: vorher Design::complete($snapshot),
// nachher DesignWizard::personalize($snapshot, wahl). Fuer eine alte
// Einladung ist wahl leer, und dann muessen beide dasselbe Dokument ergeben.
assert_same(
    Design::complete($sockel),
    DesignWizard::personalize($sockel, []),
    'personalize: mit leerer Wahl ist es genau das, was show() bisher tat'
);

// Zweimal angewendet aendert nichts mehr: ein zweites Speichern darf die
// Einladung nicht verformen.
$einmal = DesignWizard::personalize($sockel, []);
assert_same($einmal, DesignWizard::personalize($einmal, []), 'personalize: leere Wahl ist idempotent');

$wahl = ['palette' => ['accent' => '#123456'], 'fonts' => [], 'layers' => [], 'sections' => []];
assert_same(
    DesignWizard::personalize($sockel, $wahl),
    DesignWizard::personalize(DesignWizard::personalize($sockel, $wahl), $wahl),
    'personalize: dieselbe Wahl zweimal ist idempotent'
);

/* --- Der Sockel bleibt der Sockel: nur die Wahl aendert sich --- */

$rot  = DesignWizard::personalize($sockel, ['palette' => ['accent' => '#AA0000']]);
$blau = DesignWizard::personalize($sockel, ['palette' => ['accent' => '#0000AA']]);

assert_same($rot['layers'], $blau['layers'], 'personalize: eine andere Farbe laesst die Ebenen unberuehrt');
assert_same('#AA0000', $rot['palette']['accent']['value'], 'personalize: die gewaehlte Farbe steht in der Marke');
assert_same('#0000AA', $blau['palette']['accent']['value'], 'personalize: und die andere Wahl in der anderen');
assert_same($sockel['palette']['bg']['value'], $rot['palette']['bg']['value'], 'personalize: eine Marke ohne Haken bleibt, wie der Grafiker sie setzte');

/*
 * Und der eigentliche Punkt der Phase: aendert der Grafiker die Vorlage,
 * aendert sich die veroeffentlichte Einladung NICHT. Der Sockel liegt in der
 * Zeile, nicht im Katalog - das wird hier so nachgestellt, wie es passiert:
 * die Vorlage bekommt eine Ebene dazu, der eingefrorene Sockel nicht.
 */
$spaeter = edit_doc();
$spaeter['layers'][] = ['id' => 'neu', 'type' => 'text', 'bind' => 'hashtag'];

$gedruckt = DesignWizard::personalize($sockel, $wahl);
$ids = array_map(static fn (array $el): string => (string) $el['id'], $gedruckt['layers']);
assert_true(!in_array('neu', $ids, true), 'personalize: was nach dem Einfrieren in die Vorlage kam, steht nicht auf der Karte');

/*
 * Die Kehrseite von §4, gemessen: auf einem BEREITS personalisierten Sockel
 * ist das Ausblenden nicht rueckgaengig zu machen - die Ebene ist weg, und
 * choices() bietet sie nicht mehr an. Genau deshalb bleibt der Design-Tab bei
 * einer Einladung ohne wahl geschlossen.
 */
$versteckt = DesignWizard::personalize($sockel, ['layers' => ['zier' => ['hidden' => true]]]);
assert_true(!isset(DesignWizard::choices($versteckt)['layers']['zier']), 'choices: eine ausgeblendete Ebene wird auf dem personalisierten Sockel nicht mehr angeboten');
assert_true(isset(DesignWizard::choices($sockel)['layers']['zier']), 'choices: auf dem eingefrorenen Sockel steht sie weiter zur Wahl');

/*
 * Und der Fall, der wirklich zaehlt: ein Sockel, in dem eine Wahl schon
 * eingebrannt ist.
 *
 * Genau so sieht der design_snapshot einer Einladung aus, die VOR dieser
 * Phase veroeffentlicht wurde: die Farbe ueberschrieben, eine Ebene
 * entfernt, eine kunde-Marke gepraegt - und kein wahl daneben. show()
 * legt darauf ab jetzt personalize($sockel, []). Waere das nicht die
 * Identitaet, saehe jede heute ausgelieferte Einladung ab morgen anders
 * aus. Die Behauptung steht in Spec §3 und wird hier gemessen, nicht
 * geglaubt - der Test darueber misst nur ein Dokument, auf dem nie jemand
 * etwas gewaehlt hat, und das ist der leichtere Fall.
 */
$vorher = DesignWizard::personalize($sockel, [
    'palette' => ['accent' => '#AA0000'],
    'layers'  => ['zier' => ['hidden' => true]],
]);

assert_same($vorher, DesignWizard::personalize($vorher, []), 'personalize: auf einem BEREITS personalisierten Sockel ist die leere Wahl die Identitaet');
assert_same(Design::complete($vorher), DesignWizard::personalize($vorher, []), 'personalize: und zwar genau der Tausch, den show() bei einer alten Einladung macht');

// Eine gepraegte kunde-Marke ist der Fall, in dem personalize dem Dokument
// eine NEUE Marke hinzufuegt. Auch sie muss eine leere Wahl unveraendert
// ueberstehen, sonst verloere eine alte Einladung ihre eigene Farbe.
$gepraegt = DesignWizard::personalize($sockel, ['layers' => ['zier' => ['color' => '#123456']]]);
assert_true(isset($gepraegt['palette']['kunde-zier']), 'personalize: die eigene Farbe wird eine eigene Marke');
assert_same($gepraegt, DesignWizard::personalize($gepraegt, []), 'personalize: auch eine gepraegte kunde-Marke ueberlebt die leere Wahl unveraendert');

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

/* --- Bearbeiten ruehrt die Antworten der Gaeste nicht an --- */

/*
 * Spec §8: "Antworten sind unberuehrbar." Die Regel ist einfach genug, um sie
 * zu glauben, und genau deshalb wird sie gemessen: der Bearbeiten-Weg schreibt
 * mit InvitationsV2::saveData(), und wenn dort je ein zweiter Schreibzugriff
 * dazukaeme, faellt dieser Test.
 */
Atelier\Db::run('DELETE FROM rsvps WHERE slug = ?', [$slug]);
InvitationsV2::saveRsvp($slug, [
    'slug' => $slug, 'name' => 'Mehmet', 'coming' => true,
    'count' => 2, 'note' => 'Wir kommen', 'at' => '2027-01-01T10:00:00+01:00',
]);

$vorAntworten = InvitationsV2::rsvps($slug);

InvitationsV2::saveData($slug, [
    'slug' => $slug, 'bride' => 'Marije', 'manageKey' => $echt, 'updatedAt' => '2026-08-20T14:00:00+03:00',
]);

assert_same($vorAntworten, InvitationsV2::rsvps($slug), 'saveData: die Antworten der Gaeste bleiben, wie sie waren');
assert_same('Marije', (InvitationsV2::find($slug)['data']['bride'] ?? ''), 'saveData: und die Daten sind trotzdem geschrieben');

/* --- Der Stand wandert mit und sperrt den zweiten Tab --- */

$jetzt = InvitationsV2::find($slug)['data'];
assert_true(!InvitationsV2::stale($jetzt, '2026-08-20T14:00:00+03:00'), 'stale: der gerade geschriebene Stand passt');
assert_true(InvitationsV2::stale($jetzt, '2026-08-20T13:00:00+03:00'), 'stale: ein Formular von vorher wird abgelehnt');

Atelier\Db::run('DELETE FROM rsvps WHERE slug = ?', [$slug]);

Atelier\Db::run('DELETE FROM invitations_v2 WHERE slug = ?', [$slug]);
