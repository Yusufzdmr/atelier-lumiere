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

/*
 * --- Die Rundreise: sammleAngaben() -> formularWerte() -> sammleAngaben() ---
 *
 * Die Eigenschaft, deren Fehlschlagen am teuersten waere: dass sich das
 * Bearbeiten-Formular aus den gespeicherten Daten fuellt und das naechste
 * Speichern genau das zurueckschreibt, womit es gefuellt wurde. Bricht das,
 * rendert der Bildschirm leer und das naechste Speichern loescht, was das
 * Paar getippt hat - beides ohne eine einzige Fehlermeldung.
 *
 * sammleAngaben() und formularWerte() sind private auf InviteV2Controller,
 * darum per Reflection. Der Konstruktor tut nichts (siehe Klassendoc), also
 * ist newInstanceWithoutConstructor() hier nur die vorsichtigere der beiden
 * Varianten und kein Zeichen, dass etwas Besonderes noetig waere.
 */
$editController = (new ReflectionClass(\Atelier\Controllers\InviteV2Controller::class))
    ->newInstanceWithoutConstructor();
$editSammleAngaben = new ReflectionMethod($editController, 'sammleAngaben');
$editSammleAngaben->setAccessible(true);
$editFormularWerte = new ReflectionMethod($editController, 'formularWerte');
$editFormularWerte->setAccessible(true);

/*
 * Ein $darf, wie es DesignWizard::choices() tatsaechlich liefert: alle acht
 * gebundenen Felder, ein family-Abschnitt, ein program-Abschnitt und
 * mindestens ein text-Abschnitt. sammleAngaben() liest nur ['fields'] und
 * ['sections'], darum bleiben palette/fonts/layers hier leer - sie wuerden
 * ohnehin nicht gelesen.
 */
$editDarf = [
    'fields'   => DesignWizard::FIELD_ORDER,
    'palette'  => [],
    'fonts'    => [],
    'layers'   => [],
    'sections' => [
        'familie' => ['type' => 'family',  'title' => 'Familien', 'hide' => false, 'fields' => ['families']],
        'ablauf'  => ['type' => 'program', 'title' => 'Ablauf',   'hide' => false, 'fields' => ['program']],
        'text-1'  => ['type' => 'text',    'title' => 'Text',     'hide' => false, 'fields' => ['text']],
    ],
];

$editPostVorher = $_POST;

$_POST = [
    'bride'   => 'Zeynep',
    'groom'   => 'Mehmet',
    'date'    => '2027-06-18',
    'time'    => '17:30',
    'venue'   => 'Yali Bahce',
    'address' => 'Sahil Yolu No:5, Istanbul',
    // Zwei Absaetze, wie ein echter Textblock (Aufgabe "Der sechste
    // Abschnitt: ein Textblock").
    'message' => "Birlikte olmanizi\n\ndiliyoruz.",
    'hashtag' => '#zeynepvemehmet',
    'family_bride' => 'Yilmaz Ailesi',
    'family_groom' => 'Demir Ailesi',
    // Zeile 0: Uhrzeit leer, Titel gesetzt - muss ueberleben (sammleAngaben
    // prueft nur den Titel).
    'prog_time_0'  => '',
    'prog_title_0' => 'Nikah Toereni',
    // Zeile 1: Uhrzeit gesetzt, Titel leer - muss verworfen werden.
    'prog_time_1'  => '18:00',
    'prog_title_1' => '',
    // Zeile 2: der gewoehnliche Fall, beides gesetzt.
    'prog_time_2'  => '19:30',
    'prog_title_2' => 'Abendessen',
    'sec_text_text-1' => "Erster Absatz.\n\nZweiter Absatz.",
];

$ersteRunde = $editSammleAngaben->invoke($editController, $editDarf);

/*
 * Die Regel selbst, nicht nur ihre Wiederholbarkeit.
 *
 * Der Hin- und Rueckweg weiter unten beweist die Regel nicht: waere die
 * Bedingung in sammleAngaben() vertauscht - faellt die Zeile ohne UHRZEIT
 * statt der ohne TITEL -, dann spiegelte formularWerte() dieses falsche
 * Ergebnis brav zurueck, der zweite Durchlauf riefe dieselbe (falsche) Regel
 * noch einmal auf und reproduzierte es identisch, und der Vergleich zwischen
 * den Runden ginge trotzdem glatt durch. Eine Behauptung, die nicht
 * scheitern kann, behauptet nichts - darum hier die Regel direkt am ersten
 * sammleAngaben()-Ergebnis, vor jeder Rundreise.
 *
 * Eine einzige Gleichheit auf der vollstaendigen Liste erzwingt alle drei
 * Eigenschaften auf einmal: dass von den drei eingereichten Zeilen genau
 * zwei uebrig bleiben (die Laenge der Liste), dass die Zeile OHNE Uhrzeit
 * dabei ist und ihre leere Uhrzeit leer bleibt (erstes Element, per Titel
 * erkannt und nicht nur per Index), und dass die Zeile MIT Uhrzeit, aber
 * OHNE Titel (Zeile 1) verschwunden ist - bliebe sie faelschlich stehen,
 * haette die Liste drei Elemente statt zwei, oder ein falsches an dieser
 * Stelle statt 'Abendessen'. Eine eigene dritte Zusicherung dafuer waere
 * redundant.
 */
assert_same(
    [
        // Seit dem Zeitstrahl mit Zeichen traegt jede Zeile eine dritte
        // Angabe. Wer keine Art gewaehlt hat, traegt sie leer - und damit
        // steht hier, dass das Einsammeln sie nicht erfindet.
        ['time' => '', 'title' => 'Nikah Toereni', 'icon' => ''],
        ['time' => '19:30', 'title' => 'Abendessen', 'icon' => ''],
    ],
    $ersteRunde['program'],
    'sammleAngaben: die Zeile ohne Uhrzeit bleibt (mit leerer Uhrzeit), die Zeile ohne Titel faellt heraus - direkt am ersten Ergebnis geprueft, nicht erst ueber die Rundreise'
);

// formularWerte() traegt die Namen des Formulars - genau die, die
// sammleAngaben() gleich wieder liest. Damit steht das Formular so da, wie es
// ein zweites Absenden ohne jede Aenderung abschicken wuerde.
$editFormularAusRunde1 = $editFormularWerte->invoke($editController, $ersteRunde);
$_POST = $editFormularAusRunde1;
$zweiteRunde = $editSammleAngaben->invoke($editController, $editDarf);

// Und noch einmal, um Drift statt eines einmaligen Zufallstreffers
// auszuschliessen: derselbe Weg von der zweiten zur dritten Runde.
$editFormularAusRunde2 = $editFormularWerte->invoke($editController, $zweiteRunde);
$_POST = $editFormularAusRunde2;
$dritteRunde = $editSammleAngaben->invoke($editController, $editDarf);

$_POST = $editPostVorher;

/*
 * Verglichen wird mit sortierten Schluesseln, nicht mit dem rohen ===: data
 * ist ein JSON-Dokument, kein positionsabhaengiges Format - nichts in diesem
 * Code liest es der Reihe nach. sammleAngaben() und formularWerte() bauen
 * ihre Felder aus verschiedenen Schleifen (foreach ueber $darf['fields'] hier,
 * foreach ueber $data['sections'] dort), und deren Reihenfolge ist ein
 * Umsetzungsdetail, kein Vertrag. Die Listen unter 'program' bleiben davon
 * unberuehrt: ihre Schluessel sind schon 0,1,2,... und ksort() aendert an
 * einer bereits aufsteigenden Reihenfolge nichts.
 */
function edit_kanonisch(array $wert): array
{
    ksort($wert);
    foreach ($wert as $schluessel => $inhalt) {
        if (is_array($inhalt)) {
            $wert[$schluessel] = edit_kanonisch($inhalt);
        }
    }
    return $wert;
}

assert_same(
    edit_kanonisch($ersteRunde),
    edit_kanonisch($zweiteRunde),
    'sammleAngaben->formularWerte->sammleAngaben: jeder Wert uebersteht die erste Rundreise unveraendert (die leere Uhrzeit bleibt, der leere Titel bleibt draussen)'
);
assert_same(
    edit_kanonisch($zweiteRunde),
    edit_kanonisch($dritteRunde),
    'sammleAngaben->formularWerte->sammleAngaben: ein zweiter Durchlauf aendert nichts mehr - keine Drift'
);

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
