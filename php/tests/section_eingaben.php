<?php
declare(strict_types=1);

use Atelier\DesignSections;
use Atelier\SectionRegistry;

/*
 * Was das Paar in einen Abschnitt schreibt - und wer das weiss.
 *
 * Bis hierher wusste es der Assistent: eine Zeile in der Vorlage
 * ("if fields enthaelt text"), eine im Controller, eine beim Vorbelegen. Eine
 * neue Art mit einem eigenen Feld hiess also vier Dateien anfassen, und wer
 * eine davon vergass, bekam ein Feld, das sich fuellen aber nicht speichern
 * liess.
 *
 * Jetzt steht es im Katalog. Der Assistent baut seine Felder daraus, der
 * Controller liest sie daraus, und beide zaehlen dasselbe auf.
 */

/* --- Der Katalog sagt, was eine Art fragt --- */

$textFelder = SectionRegistry::inputs('text');

assert_true(isset($textFelder['text']), 'Eingaben: der Textblock fragt nach Text');
assert_same('textarea', $textFelder['text']['type'], 'Eingaben: und zwar mehrzeilig');
assert_true($textFelder['text']['max'] > 0, 'Eingaben: mit einer Obergrenze');

assert_same([], SectionRegistry::inputs('countdown'), 'Eingaben: der Countdown fragt nichts');
assert_same([], SectionRegistry::inputs('gibtesnicht'), 'Eingaben: eine unbekannte Art auch nicht');

/* --- Jedes Eingabefeld ist vollstaendig beschrieben --- */

foreach (array_keys(SectionRegistry::all()) as $art) {
    foreach (SectionRegistry::inputs($art) as $schluessel => $feld) {
        assert_true(in_array($feld['type'], ['text', 'textarea'], true),
            'Eingaben: ' . $art . '.' . $schluessel . ' hat eine bekannte Art');
        assert_true(($feld['max'] ?? 0) > 0,
            'Eingaben: ' . $art . '.' . $schluessel . ' hat eine Obergrenze');
        assert_true(isset($feld['label']['de']),
            'Eingaben: ' . $art . '.' . $schluessel . ' hat ein Etikett');
    }
}

/* --- Der Wert liegt unter der Kennung des Abschnitts --- */

$daten = ['sections' => ['dank' => ['text' => 'Danke, dass ihr da wart.', 'hashtag' => 'sophiaundmax']]];

assert_same('Danke, dass ihr da wart.', DesignSections::sectionValue($daten, 'dank', 'text'),
    'Wert: der Text kommt unter seiner Kennung heraus');
assert_same('sophiaundmax', DesignSections::sectionValue($daten, 'dank', 'hashtag'),
    'Wert: und jeder andere Schluessel auch');
assert_same('', DesignSections::sectionValue($daten, 'dank', 'gibtesnicht'),
    'Wert: ein unbekannter Schluessel ist leer');
assert_same('', DesignSections::sectionValue($daten, 'anderer', 'text'),
    'Wert: eine unbekannte Kennung ist leer');
assert_same('', DesignSections::sectionValue([], 'dank', 'text'),
    'Wert: ohne Daten ist alles leer');

// sectionText bleibt, was es war - es ist derselbe Griff mit festem Schluessel.
assert_same('Danke, dass ihr da wart.', DesignSections::sectionText($daten, 'dank'),
    'Wert: sectionText ist derselbe Griff');
