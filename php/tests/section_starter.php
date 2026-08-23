<?php
declare(strict_types=1);

use Atelier\Design;
use Atelier\SectionRegistry;

/*
 * Ein Anfang, kein Urteil.
 *
 * Eine leere Vorlage ist die Stelle, an der Bauen am laengsten dauert: man
 * weiss, dass unter der Karte etwas stehen soll, aber jede einzelne Zeile
 * muss man erst anlegen, benennen und einordnen. elysee und noir - die zwei
 * gemessenen Vorlagen des Hauses - hatten deshalb bis heute NULL Abschnitte.
 *
 * Ein Startsatz legt die uebliche Reihenfolge hin. Danach ist alles wie
 * vorher: schieben, umbenennen, wegnehmen.
 */

/* --- Die Saetze sind vollstaendig und kennen nur bekannte Arten --- */

foreach (SectionRegistry::starters() as $name => $satz) {
    assert_true($satz['sections'] !== [], 'Start: ' . $name . ' ist nicht leer');

    foreach ($satz['sections'] as $eintrag) {
        assert_true(
            SectionRegistry::has($eintrag['type']),
            'Start: ' . $name . ' nennt mit ' . $eintrag['type'] . ' eine bekannte Art'
        );
        assert_true(
            SectionRegistry::isVariant($eintrag['type'], $eintrag['variant'] ?? 'default'),
            'Start: ' . $name . ' nennt eine Gestalt, die es bei dieser Art gibt'
        );
    }
}

/* --- Auf einer leeren Vorlage entsteht der ganze Satz --- */

$leer = Design::complete(['id' => 'start', 'slug' => 'start']);
$gefuellt = Design::withStarter($leer, 'klassisch');

$arten = array_map(static fn (array $a): string => (string) $a['type'], $gefuellt['sections']);

assert_true(count($arten) >= 5, 'Start: der klassische Satz bringt mehrere Abschnitte');
assert_same('location', $arten[0], 'Start: wo gefeiert wird, steht zuerst');
assert_true(in_array('rsvp', $arten, true), 'Start: die Frage nach dem Kommen ist dabei');

// Jeder Abschnitt ist sofort brauchbar: Kennung, Titel, angeschaltet.
foreach ($gefuellt['sections'] as $abschnitt) {
    assert_true((string) $abschnitt['id'] !== '', 'Start: jeder Abschnitt hat eine Kennung');
    assert_true((string) $abschnitt['title']['de'] !== '', 'Start: und einen Titel');
    assert_same(true, $abschnitt['enabled'], 'Start: und ist an');
}

/* --- Ein unbekannter Satz aendert nichts --- */

$nichts = Design::withStarter($leer, 'gibtesnicht');
assert_same([], $nichts['sections'], 'Start: ein unbekannter Satz legt nichts an');

/* --- Was schon da ist, bleibt und wird nicht doppelt --- */

$hatSchon = Design::complete([
    'id' => 'start2', 'slug' => 'start2',
    'sections' => [
        ['id' => 'meinort', 'type' => 'location', 'title' => ['de' => 'Mein Ort', 'en' => '']],
    ],
]);

$dazu = Design::withStarter($hatSchon, 'klassisch');
$dazuArten = array_map(static fn (array $a): string => (string) $a['type'], $dazu['sections']);

assert_same('meinort', $dazu['sections'][0]['id'], 'Start: der vorhandene Abschnitt bleibt vorn');
assert_same('Mein Ort', $dazu['sections'][0]['title']['de'], 'Start: und behaelt seinen Titel');
assert_same(1, count(array_filter($dazuArten, static fn (string $t): bool => $t === 'location')),
    'Start: eine Art, die schon steht, kommt nicht ein zweites Mal');

/* --- Zweimal derselbe Satz aendert beim zweiten Mal nichts --- */

$zweimal = Design::withStarter($gefuellt, 'klassisch');

assert_same(
    count($gefuellt['sections']),
    count($zweimal['sections']),
    'Start: zweimal angewendet kommt nichts dazu'
);

/* --- Kennungen bleiben eindeutig --- */

$kennungen = array_map(static fn (array $a): string => (string) $a['id'], $dazu['sections']);
assert_same(count($kennungen), count(array_unique($kennungen)), 'Start: keine Kennung doppelt');
