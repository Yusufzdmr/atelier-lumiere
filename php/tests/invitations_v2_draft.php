<?php
declare(strict_types=1);

use Atelier\InvitationsV2;

/*
 * Entwuerfe der zweiten Fassung.
 *
 * Der Assistent ist ein langes Formular. Wer ihn zur Haelfte ausfuellt und den
 * Tab schliesst, hatte bisher nichts. Ein Entwurf haelt die Eingaben fest und
 * gibt einen Link zurueck, der sie wiederbringt.
 *
 * Geteilt wird die Tabelle invite_drafts, nicht der Code: Invitations steht
 * auf der Liste der Unberuehrbaren - dieselbe Entscheidung wie bei den
 * Antworten (siehe InvitationsV2::saveRsvp).
 */

/* --- Was in den Entwurf geht: rein, kein Datenbanktest --- */

$roh = [
    'csrf'   => 'geheim',
    'was'    => 'draft',
    'token'  => 'abc123',
    'design' => 'elysee',
    'bride'  => 'Elif',
    'groom'  => 'Kaan',
];

$sauber = InvitationsV2::draftValues($roh);

// Das Zeichen gehoert zur Sitzung, nicht zum Entwurf. Gespeichert waere es
// ein Geheimnis, das in einer Tabelle liegt und dort nichts zu suchen hat.
assert_true(!isset($sauber['csrf']), 'draftValues: das CSRF-Zeichen wird nicht gespeichert');
// was ist der Knopf, token die Kennung des Entwurfs selbst - beide beschreiben
// die Anfrage, nicht die Einladung.
assert_true(!isset($sauber['was']), 'draftValues: der Knopfname wird nicht gespeichert');
assert_true(!isset($sauber['token']), 'draftValues: die eigene Kennung wird nicht gespeichert');

// Das Design gehoert hinein: sonst kaeme der Kunde auf einer anderen Vorlage
// zurueck als der, die er gewaehlt hatte.
assert_same('elysee', $sauber['design'], 'draftValues: die gewaehlte Vorlage bleibt');
assert_same('Elif', $sauber['bride'], 'draftValues: die Eingaben bleiben');
assert_same('Kaan', $sauber['groom'], 'draftValues: die Eingaben bleiben (zweite)');

// Ein Feld, das keine Zeichenkette ist, kommt aus einem Formular so nicht -
// wohl aber aus einer von Hand gestellten Anfrage (name[]=x).
$mitFeld = InvitationsV2::draftValues(['bride' => ['a', 'b'], 'groom' => 'Kaan']);
assert_true(!isset($mitFeld['bride']), 'draftValues: ein Feld statt eines Wertes faellt weg');
assert_same('Kaan', $mitFeld['groom'], 'draftValues: der Nachbar bleibt davon unberuehrt');

// Steuerzeichen und Rand fallen weg, wie ueberall sonst (Security::clean).
assert_same('Elif', InvitationsV2::draftValues(['bride' => "  Elif \n"])['bride'], 'draftValues: der Rand faellt weg');

// Obergrenze: ein Entwurf ist ein Zwischenstand, keine Ablage. Die endgueltigen
// Feldgrenzen setzt publish() noch einmal - hier geht es nur darum, dass eine
// einzelne Anfrage die Tabelle nicht sprengt.
$lang = InvitationsV2::draftValues(['message' => str_repeat('A', 5000)]);
assert_same(InvitationsV2::DRAFT_LEN, mb_strlen($lang['message']), 'draftValues: zu lange Eingaben werden gekuerzt');

// Mehrbytiges bleibt gueltig - DRAFT_LEN zaehlt Zeichen, nicht Byte.
$tuerkisch = InvitationsV2::draftValues(['message' => str_repeat('ş', 5000)]);
assert_same(InvitationsV2::DRAFT_LEN, mb_strlen($tuerkisch['message']), 'draftValues: mehrbytige Eingaben werden auf Zeichen gekuerzt');
assert_same(true, mb_check_encoding($tuerkisch['message'], 'UTF-8'), 'draftValues: der Schnitt bleibt gueltiges UTF-8');

assert_same([], InvitationsV2::draftValues([]), 'draftValues: nichts eingegeben, nichts gespeichert');

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

$t1 = 'testdraft-aaaaaaaaaaaaaaaa';
$t2 = 'testdraft-bbbbbbbbbbbbbbbb';

// Sauber anfangen, falls ein frueherer Lauf abgebrochen ist.
Atelier\Db::run('DELETE FROM invite_drafts WHERE token IN (?, ?)', [$t1, $t2]);

/* --- Speichern und wiederfinden --- */

InvitationsV2::saveDraft($t1, ['design' => 'elysee', 'bride' => 'Elif']);

$zurueck = InvitationsV2::draft($t1);
assert_true($zurueck !== null, 'saveDraft: der Entwurf wird wiedergefunden');
assert_same('Elif', $zurueck['bride'], 'saveDraft: die Eingabe kommt zurueck');
assert_same('elysee', $zurueck['design'], 'saveDraft: die Vorlage kommt zurueck');

/* --- Zweimal derselbe Schluessel ersetzt, es haengt nicht an --- */

// Der Kunde drueckt zweimal auf Speichern. Zwei Zeilen waeren zwei Entwuerfe
// unter einem Link, und der Router traefe einen davon.
InvitationsV2::saveDraft($t1, ['design' => 'elysee', 'bride' => 'Elif', 'groom' => 'Kaan']);
$zweit = InvitationsV2::draft($t1);
assert_same('Kaan', $zweit['groom'], 'saveDraft: der zweite Stand gilt');
assert_same(1, (int) Atelier\Db::one('SELECT COUNT(*) AS n FROM invite_drafts WHERE token = ?', [$t1])['n'], 'saveDraft: es bleibt eine Zeile');

/* --- Zwei Entwuerfe kommen einander nicht ins Gehege --- */

InvitationsV2::saveDraft($t2, ['design' => 'noir', 'bride' => 'Ayşe']);
assert_same('Elif', InvitationsV2::draft($t1)['bride'], 'saveDraft: der fremde Entwurf laesst diesen in Ruhe');
assert_same('noir', InvitationsV2::draft($t2)['design'], 'saveDraft: jeder Entwurf haelt seine eigene Vorlage');

/* --- Unbekannt ist null, nicht leer --- */

// null und [] sind hier verschiedene Antworten: "es gibt keinen Entwurf" muss
// der Assistent von "ein Entwurf ohne Eingaben" unterscheiden koennen, sonst
// zeigt ein falscher Link ein leeres Formular statt einer ehrlichen Meldung.
assert_same(null, InvitationsV2::draft('testdraft-gibtesnicht'), 'draft: ein unbekannter Schluessel gibt null');
assert_same(null, InvitationsV2::draft(''), 'draft: ohne Schluessel gibt es nichts');

/* --- Loeschen --- */

// Nach dem Veroeffentlichen ist der Entwurf Ballast: er zeigt einen Stand, den
// die fertige Einladung laengst ueberholt hat.
InvitationsV2::deleteDraft($t1);
assert_same(null, InvitationsV2::draft($t1), 'deleteDraft: der Entwurf ist weg');
assert_true(InvitationsV2::draft($t2) !== null, 'deleteDraft: der Nachbar bleibt');

/* --- Aufraeumen --- */

Atelier\Db::run('DELETE FROM invite_drafts WHERE token IN (?, ?)', [$t1, $t2]);
assert_same(null, InvitationsV2::draft($t2), 'aufgeraeumt: die Testzeilen sind wieder weg');
