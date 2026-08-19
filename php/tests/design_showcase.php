<?php
declare(strict_types=1);

use Atelier\Design;

/*
 * Welche Vorlage kann heute in den Assistenten?
 *
 * Der alte Assistent prueft ?design= gegen die Themen-Kennungen und ignoriert
 * still, was er nicht kennt (InviteController.php:74). Ein Knopf, der still
 * nichts tut, ist schlechter als kein Knopf - also entscheidet das hier, mit
 * einem Test, und nicht die Vorlage.
 */

$designs = [
    ['slug' => 'elysee'],
    ['slug' => 'noir'],
    ['slug' => 'elysee-nacht'],
];

$karte = Design::creatable($designs, ['elysee', 'noir', 'sage']);

assert_same(true, $karte['elysee'], 'creatable: mit passendem Thema ja');
assert_same(true, $karte['noir'], 'creatable: mit passendem Thema ja');
assert_same(false, $karte['elysee-nacht'], 'creatable: eine Kopie hat kein Thema');
assert_same(3, count($karte), 'creatable: jede Vorlage kommt vor');

$ohne = Design::creatable($designs, []);
assert_same(false, $ohne['elysee'], 'creatable: ohne Themen kann keine');
assert_same([], Design::creatable([], ['elysee']), 'creatable: ohne Vorlagen leer');
