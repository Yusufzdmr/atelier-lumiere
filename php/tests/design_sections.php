<?php
declare(strict_types=1);

use Atelier\DesignSections;

/*
 * Abschnitte sind das, was unter der Karte steht: Ort, Countdown, Familien,
 * Programm. Sie gehoeren dem Dokument, nicht der Einladung - der Grafiker
 * stellt sie auf, der Kunde darf hoechstens zu- und abschalten.
 *
 * Dieselbe Form wie bei den Ebenen: die Reihenfolge ist die Reihenfolge im
 * Feld, Farbe und Schrift sind Markennamen, und edit ist der Hauptschalter.
 */

function sec_doc(array $sections): array
{
    return ['id' => 'test', 'slug' => 'test', 'sections' => $sections];
}

$doc = DesignSections::complete(sec_doc([
    ['id' => 'ort-1', 'type' => 'location'],
    ['id' => 'unbekannt', 'type' => 'wetterbericht'],
    ['id' => 'prog-1', 'type' => 'program', 'enabled' => false,
     'title' => ['de' => 'Ablauf', 'en' => 'Schedule'],
     'style' => ['color' => 'Accent', 'font' => 'display'],
     'permissions' => ['edit' => true, 'hide' => true]],
]));

assert_same(2, count($doc['sections']), 'complete: unbekannter Typ faellt weg');
assert_same('ort-1', $doc['sections'][0]['id'], 'complete: Reihenfolge ist die des Feldes');
assert_same('prog-1', $doc['sections'][1]['id'], 'complete: Reihenfolge ist die des Feldes (zweiter)');

// Vollstaendig, auch wo nichts angegeben war.
$erste = $doc['sections'][0];
assert_same('location', $erste['type'], 'complete: Typ bleibt');
assert_same(true, $erste['enabled'], 'complete: enabled ist standardmaessig an');
assert_same('', $erste['title']['de'], 'complete: fehlender Titel wird leer');
assert_same('', $erste['style']['color'], 'complete: fehlende Farbmarke wird leer');
assert_same(false, $erste['permissions']['edit'], 'complete: Rechte sind standardmaessig zu');
assert_same(false, $erste['permissions']['hide'], 'complete: Rechte sind standardmaessig zu');

// Angegebenes bleibt - und der Markenname wird normalisiert wie ueberall sonst.
$zweite = $doc['sections'][1];
assert_same(false, $zweite['enabled'], 'complete: enabled=false bleibt');
assert_same('Ablauf', $zweite['title']['de'], 'complete: Titel bleibt');
assert_same('accent', $zweite['style']['color'], 'complete: Markenname wird kleingeschrieben');
assert_same(true, $zweite['permissions']['edit'], 'complete: gesetztes Recht bleibt');

// Ohne Kennung kein Abschnitt: er waere im CSS nicht adressierbar.
$ohne = DesignSections::complete(sec_doc([['type' => 'family']]));
assert_same([], $ohne['sections'], 'complete: ohne id faellt der Abschnitt weg');

// Was kein Feld ist, ist kein Abschnitt.
$mist = DesignSections::complete(sec_doc(['etwas', 42, null]));
assert_same([], $mist['sections'], 'complete: Unsinn im Feld faellt weg');

// Der Rest des Dokuments bleibt unberuehrt.
$rest = DesignSections::complete(['id' => 'x', 'layers' => [['id' => 'a']], 'sections' => []]);
assert_same(1, count($rest['layers']), 'complete: layers bleiben unangetastet');
