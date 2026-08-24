<?php
declare(strict_types=1);

use Atelier\InvitationsV2;

/*
 * Wer haengt noch an einer aelteren Fassung seiner Vorlage?
 *
 * Eine verschickte Einladung traegt ihre eigene Kopie der Vorlage - den
 * design_snapshot. Aendert der Grafiker die Vorlage danach, aendert sich die
 * Einladung nicht, und das ist das Versprechen der Phase 3B. Der Preis: nach
 * ein paar Wochen weiss niemand mehr, welche Einladung auf welchem Stand
 * steht.
 *
 * Die Entscheidung ist eine Zahl gegen eine Zahl, und sie steht hier - nicht
 * in der Abfrage. Die Abfrage holt drei Spalten, sonst nichts; so laesst sich
 * dieser Teil ohne Datenbank pruefen, wie alles andere in diesem Ordner.
 *
 * Getrennt nach Zustand, weil die beiden nicht gleich schwer wiegen: einen
 * Entwurf darf man nachziehen, eine veroeffentlichte Einladung liegt bereits
 * bei den Gaesten.
 */

$zeilen = [
    ['slug' => 'alt-entwurf',      'status' => 'draft',      'fassung' => 2],
    ['slug' => 'alt-im-netz',      'status' => 'published',  'fassung' => 3],
    ['slug' => 'aktuell',          'status' => 'published',  'fassung' => 5],
    ['slug' => 'ohne-zeile',       'status' => '',           'fassung' => 1],
];

$veraltet = InvitationsV2::outdated($zeilen, 5);

assert_same(['alt-entwurf'], $veraltet['draft'], 'outdated: der Entwurf steht fuer sich');
assert_same(['alt-im-netz', 'ohne-zeile'], $veraltet['published'],
    'outdated: fehlender Zustand zaehlt als veroeffentlicht');

// Gleiche Fassung ist nicht veraltet - sonst haette jede Einladung immer
// einen Knopf, der nichts tut.
assert_same([], InvitationsV2::outdated([
    ['slug' => 'a', 'status' => 'draft', 'fassung' => 5],
], 5)['draft'], 'outdated: gleiche Fassung faellt weg');

// Ein Schnappschuss ohne Fassung ist der aelteste, den es gibt: die erste.
assert_same(['a'], InvitationsV2::outdated([
    ['slug' => 'a', 'status' => 'draft', 'fassung' => null],
], 2)['draft'], 'outdated: ohne Fassung gilt die erste');

// Und eine Vorlage, die jemand von Hand zurueckgesetzt hat, macht aus einer
// Einladung keine veraltete: groesser heisst veraltet, ungleich nicht.
assert_same([], InvitationsV2::outdated([
    ['slug' => 'a', 'status' => 'draft', 'fassung' => 9],
], 5)['draft'], 'outdated: neuer als die Vorlage ist nicht veraltet');

// Beide Schluessel stehen immer da - der Aufrufer soll nicht pruefen muessen.
$leer = InvitationsV2::outdated([], 3);
assert_same(['draft' => [], 'published' => []], $leer, 'outdated: beide Faecher stehen immer');
