<?php
declare(strict_types=1);

use Atelier\Themes;

/*
 * Eine Auswahlliste, die den gespeicherten Wert nicht wegwirft.
 *
 * Gefunden am 24.08.2026 an noir. Das Dokument trug idle=pulse, reveal=side,
 * particle=spark, nameMove=letters und card=seal. Der Editor bot fuer diese
 * fuenf Achsen nur je zwei bis drei Woerter an - keines davon war der
 * gespeicherte Wert. Ein <select> ohne passende Option waehlt die erste, und
 * beim Speichern stand dann breathe, up, none, fade und "0" im Dokument.
 *
 * Niemand haette es getan; jeder haette es ausgeloest. Einmal oeffnen, einmal
 * speichern - und die ganze Bewegung einer Vorlage ist eine andere. Sichtbar
 * erst auf der Seite, nie im Panel.
 *
 * Deshalb: was gespeichert ist, steht zur Wahl. Auch wenn der Katalog es
 * nicht (mehr) kennt. Ein unbekanntes Wort ist ein Hinweis fuer den
 * Grafiker - keine Erlaubnis, es zu loeschen.
 */

/* --- Der bekannte Wert aendert nichts --- */

assert_same(
    ['breathe', 'none'],
    Themes::withCurrent(['breathe', 'none'], 'breathe'),
    'withCurrent: ein bekannter Wert aendert die Liste nicht'
);

/* --- Der unbekannte kommt dazu, und zwar vorn --- */

assert_same(
    ['pulse', 'breathe', 'none'],
    Themes::withCurrent(['breathe', 'none'], 'pulse'),
    'withCurrent: ein unbekannter Wert kommt dazu'
);

/* --- Leer bleibt leer: kein Eintrag ohne Wort --- */

assert_same(
    ['breathe', 'none'],
    Themes::withCurrent(['breathe', 'none'], ''),
    'withCurrent: ein leerer Wert erfindet keine Option'
);

/* --- Die Karte hat Woerter, keine Nummern --- */

/*
 * ANIMATIONS ist eine LISTE - ihre Schluessel sind 0..12, ihre Werte sind die
 * Namen. Der Editor bot array_keys() an und schrieb deshalb Zahlen ins
 * Dokument. Der Test steht hier und nicht nur im Panel, weil er die Frage
 * beantwortet, die dahintersteckt: was ist ein Kartenwert, ein Wort oder eine
 * Nummer.
 */
assert_true(in_array('seal', Themes::ANIMATIONS, true), 'ANIMATIONS: seal ist ein Wert');
assert_true(!in_array('0', Themes::ANIMATIONS, true), 'ANIMATIONS: "0" ist kein Wert, sondern ein Schluessel');
assert_same('seal', Themes::ANIMATIONS[0], 'ANIMATIONS: der erste Wert ist seal');
