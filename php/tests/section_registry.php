<?php
declare(strict_types=1);

use Atelier\DesignSections;
use Atelier\SectionRegistry;

/*
 * Der Katalog der Abschnittsarten - und was jede von ihnen kann.
 *
 * Bis hierher war eine Art ein Wort: "program" hiess genau ein Aussehen, das
 * in DesignSections::programm() stand. Wer ein zweites wollte, brauchte eine
 * siebte Art - und damit einen neuen Zweig in vier match()-Bloecken.
 *
 * Der Katalog trennt beides: die ART sagt, WAS ein Abschnitt zeigt (ein
 * Ablauf, ein Ort, eine Frage), die VARIANTE sagt, WIE er aussieht. Eine neue
 * Variante ist damit ein Eintrag und ein Stilblock, keine neue Art.
 *
 * Die EINSTELLUNGEN sind der dritte Teil: Knoepfe, die der Grafiker dreht,
 * ohne dass daraus eine eigene Variante wird - Ausrichtung, Luft, ob der
 * Kartenlink mitkommt. Sie tragen ein Schema, damit derselbe Eintrag sowohl
 * das Formular baut als auch die Eingabe prueft. Zwei Listen mit denselben
 * Grenzen liefen auseinander.
 */

/* --- Jede Art im Katalog hat mindestens eine Variante --- */

foreach (DesignSections::TYPES as $art) {
    assert_true(SectionRegistry::has($art), 'Katalog: ' . $art . ' steht im Register');
    assert_true(SectionRegistry::variants($art) !== [], 'Katalog: ' . $art . ' hat eine Variante');
    assert_true(
        isset(SectionRegistry::variants($art)[SectionRegistry::defaultVariant($art)]),
        'Katalog: die Voreinstellung von ' . $art . ' steht in seiner Liste'
    );
}

// Umgekehrt auch: kein Eintrag ohne Art. Sonst boete das Panel eine Variante
// an, die DesignSections gar nicht drucken kann.
foreach (array_keys(SectionRegistry::all()) as $art) {
    assert_true(in_array($art, DesignSections::TYPES, true), 'Katalog: ' . $art . ' ist eine bekannte Art');
}

/* --- Die Voreinstellung heisst ueberall gleich --- */

assert_same('default', SectionRegistry::defaultVariant('program'), 'Katalog: die Voreinstellung heisst default');
assert_same('default', SectionRegistry::defaultVariant('gibtesnicht'), 'Katalog: auch fuer eine unbekannte Art');

/* --- Der Ablauf hat eine zweite Gestalt --- */

assert_true(SectionRegistry::isVariant('program', 'zeitstrahl'), 'Katalog: der Ablauf kann Zeitstrahl');
assert_true(!SectionRegistry::isVariant('program', 'discokugel'), 'Katalog: erfundene Varianten gibt es nicht');
assert_true(!SectionRegistry::isVariant('location', 'zeitstrahl'), 'Katalog: eine Variante gehoert ihrer Art');

/* --- Einstellungen: was fehlt, faellt auf die Voreinstellung --- */

$leer = SectionRegistry::completeSettings('program', []);

assert_same('center', $leer['align'], 'Einstellungen: ohne Angabe steht der Abschnitt mittig');
assert_same('normal', $leer['space'], 'Einstellungen: ohne Angabe normale Luft');

/* --- Was danebenliegt, faellt zurueck; was nicht im Schema steht, faellt weg --- */

$quatsch = SectionRegistry::completeSettings('program', [
    'align'      => 'diagonal',
    'space'      => 'normal',
    'discokugel' => 'an',
]);

assert_same('center', $quatsch['align'], 'Einstellungen: eine unbekannte Wahl faellt zurueck');
assert_true(!array_key_exists('discokugel', $quatsch), 'Einstellungen: ein fremder Schluessel faellt weg');

/* --- Wahrheitswerte und Zahlen --- */

$ort = SectionRegistry::completeSettings('location', ['map' => '1']);
assert_same(true, $ort['map'], 'Einstellungen: ein Haken wird zum Wahrheitswert');

$ohneKarte = SectionRegistry::completeSettings('location', ['map' => '']);
assert_same(false, $ohneKarte['map'], 'Einstellungen: leer heisst aus');

/* --- Eine Art, die es nicht gibt, hat auch keine Einstellungen --- */

assert_same([], SectionRegistry::completeSettings('wetterbericht', ['align' => 'left']),
    'Einstellungen: eine unbekannte Art hat keine');

/* --- Das Schema baut das Formular: jeder Eintrag sagt, was er ist --- */

foreach (SectionRegistry::all() as $art => $eintrag) {
    foreach (SectionRegistry::settings($art) as $schluessel => $schema) {
        assert_true(isset($schema['type']), 'Schema: ' . $art . '.' . $schluessel . ' sagt seine Art');
        assert_true(array_key_exists('default', $schema), 'Schema: ' . $art . '.' . $schluessel . ' hat eine Voreinstellung');
        if ($schema['type'] === 'select') {
            assert_true(
                in_array($schema['default'], $schema['options'], true),
                'Schema: die Voreinstellung von ' . $art . '.' . $schluessel . ' steht in ihrer Liste'
            );
        }
    }
}

/* --- Ein Pfad als Einstellung: die Musik bringt ihre Tonspur mit --- */

$ton = SectionRegistry::completeSettings('music', ['track' => '/uploads/designs/lied.mp3']);
assert_same('/uploads/designs/lied.mp3', $ton['track'], 'Einstellungen: der Pfad kommt an');

/*
 * Ein fremder Host faellt weg - dieselbe Pruefung wie bei Bildern und Filmen.
 * Eine Tonspur von woanders waere ein Hoerer, der mitschreibt, wer die
 * Einladung geoeffnet hat.
 */
$fremd = SectionRegistry::completeSettings('music', ['track' => 'https://beispiel.de/lied.mp3']);
assert_same('', $fremd['track'], 'Einstellungen: ein fremder Host faellt weg');

$leer = SectionRegistry::completeSettings('music', []);
assert_same('', $leer['track'], 'Einstellungen: ohne Angabe keine Tonspur');
