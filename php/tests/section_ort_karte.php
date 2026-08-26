<?php
declare(strict_types=1);

use Atelier\DesignSections;
use Atelier\DesignWizard;
use Atelier\SectionRegistry;
use Atelier\StaticMap;

/*
 * Der Ort, der verschwand.
 *
 * Der Kunde hat drei Einladungen gebaut und danach gesagt: "bu düğün
 * Lokalisation olduğu yer bi acamadim". In allen drei Datensaetzen fehlten
 * venue und address vollstaendig - nicht leer, sondern gar nicht da.
 *
 * Der Weg dorthin ging ueber zwei Stellen, die einzeln beide richtig waren:
 *
 *   1. Der Assistent fragte nur nach Feldern, die die KARTE benutzt
 *      (DesignWizard::choices liest die bind-Namen der Ebenen). Die Vorlagen
 *      bild, film, video und 25aug zeigen die Adresse nicht auf dem Papier -
 *      also fragte er nie danach.
 *   2. DesignSections::hatInhalt warf den location-Abschnitt weg, wenn keine
 *      Adresse da war - wortlos, denn ein leerer Kasten waere schlimmer.
 *
 * Zusammen ergab das einen Abschnitt, den der Grafiker aufgestellt hatte, den
 * das Paar nie fuellen konnte und den niemand je zu sehen bekam.
 *
 * Diese Datei haelt beide Enden fest.
 */

/** Ein Dokument mit Abschnitten, aber ohne Adresse auf der Karte. */
function ort_doc(array $sections, array $layers = []): array
{
    return ['id' => 'probe', 'slug' => 'probe', 'layers' => $layers, 'sections' => $sections];
}

/* ------------------ 1. Der Assistent fragt, was der Abschnitt braucht ----- */

$stumm = ort_doc(
    [['id' => 'ort-1', 'type' => 'location'], ['id' => 'cd-1', 'type' => 'countdown']],
    // Die Karte zeigt nur die Namen - genau die Lage der Vorlage "bild".
    [['id' => 'n', 'type' => 'text', 'bind' => 'couple_names']]
);

$felder = DesignWizard::choices($stumm)['fields'];

assert_true(in_array('venue', $felder, true), 'choices: der location-Abschnitt verlangt den Ort');
assert_true(in_array('address', $felder, true), 'choices: der location-Abschnitt verlangt die Adresse');
assert_true(in_array('date', $felder, true), 'choices: der countdown-Abschnitt verlangt das Datum');

// Die Reihenfolge bleibt die des Formulars und nicht die des Fundorts.
assert_same(
    ['bride', 'groom', 'date', 'venue', 'address'],
    $felder,
    'choices: die Reihenfolge ist FIELD_ORDER'
);

// Ein abgeschalteter Abschnitt fragt nichts: das Paar fuellte sonst ein Feld,
// das visible() beim Drucken ohnehin wegwirft.
$aus = ort_doc(
    [['id' => 'ort-1', 'type' => 'location', 'enabled' => false]],
    [['id' => 'n', 'type' => 'text', 'bind' => 'couple_names']]
);
assert_same(
    ['bride', 'groom'],
    DesignWizard::choices($aus)['fields'],
    'choices: ein abgeschalteter Abschnitt verlangt nichts'
);

// Ohne Abschnitt bleibt alles wie vorher - die Karte allein bestimmt.
$ohne = ort_doc([], [['id' => 'n', 'type' => 'text', 'bind' => 'couple_names']]);
assert_same(['bride', 'groom'], DesignWizard::choices($ohne)['fields'], 'choices: ohne Abschnitte zaehlt nur die Karte');

assert_same(['venue', 'address'], SectionRegistry::needs('location'), 'needs: location');
assert_same(['date'], SectionRegistry::needs('countdown'), 'needs: countdown');
assert_same([], SectionRegistry::needs('rsvp'), 'needs: rsvp braucht keine feste Angabe');

/* ------------------------ 2. Der Abschnitt erscheint --------------------- */

$doc = ort_doc([['id' => 'ort-1', 'type' => 'location', 'title' => ['de' => 'Ort', 'en' => 'Place']]]);

$nurSaal = DesignSections::html($doc, ['venue' => 'Villa Sonnenhof', 'slug' => 'paar'], 'de');
assert_contains($nurSaal, 'Villa Sonnenhof', 'ort: der Saalname allein traegt den Abschnitt');
assert_not_contains($nurSaal, 'd-sec-address', 'ort: ohne Adresse kein leerer Adressabsatz');
assert_not_contains($nurSaal, 'd-sec-map', 'ort: ohne Adresse weder Karte noch Route');

$leer = DesignSections::html($doc, ['slug' => 'paar'], 'de');
assert_same('', $leer, 'ort: ohne Saal und ohne Adresse gibt es den Abschnitt nicht');

/* ---------------------------- 3. Das Kartenbild -------------------------- */

$voll = DesignSections::html(
    $doc,
    ['venue' => 'Villa Sonnenhof', 'address' => 'Seestrasse 4, 88131 Lindau', 'slug' => 'elif-kerem'],
    'de'
);

assert_contains($voll, '/de/v2/einladung/elif-kerem/karte.png', 'karte: das Bild haengt am Slug');
assert_contains($voll, 'loading="lazy"', 'karte: das Bild laedt erst beim Hinsehen');
assert_contains($voll, 'd-sec-map-blatt', 'karte: die Blattform ist die Voreinstellung');
assert_not_contains($voll, '<iframe', 'karte: kein iframe - der Gast spricht nur mit uns');
assert_not_contains($voll, 'maps.googleapis.com', 'karte: kein direkter Aufruf aus der Seite');
assert_contains($voll, 'https://www.google.com/maps/dir/', 'karte: der Klick fuehrt zur Route');

// Abschaltbar - und dann bleibt der Ort trotzdem stehen.
$ohneBild = DesignSections::html(
    ['id' => 'probe', 'slug' => 'probe', 'sections' => [
        ['id' => 'ort-1', 'type' => 'location', 'settings' => ['karte' => 'aus']],
    ]],
    ['venue' => 'Villa Sonnenhof', 'address' => 'Seestrasse 4, 88131 Lindau', 'slug' => 'elif-kerem'],
    'de'
);
assert_not_contains($ohneBild, 'karte.png', 'karte: abgeschaltet erscheint kein Bild');
assert_contains($ohneBild, 'Seestrasse 4', 'karte: abgeschaltet steht die Adresse trotzdem da');

// Ohne Slug gibt es keine gespeicherte Einladung - ausser bei der
// Beispieladresse, fuer die das Schaufenster einen eigenen Endpunkt hat.
$vorschauFremd = DesignSections::html($doc, ['address' => 'Irgendwo 1, Nirgendwo'], 'de');
assert_not_contains($vorschauFremd, 'karte.png', 'karte: ohne Slug kein Bild zu einer fremden Adresse');

$vorschauDemo = DesignSections::html($doc, ['address' => StaticMap::DEMO_ADDRESS], 'de');
assert_contains($vorschauDemo, '/de/v2/karte-beispiel.png', 'karte: das Schaufenster zeigt das Beispielbild');

/* ----------------------------- 4. Die Uhr -------------------------------- */

$uhrDoc = ort_doc([['id' => 'cd-1', 'type' => 'countdown', 'variant' => 'uhr']]);
$uhr = DesignSections::html($uhrDoc, ['date' => '2027-06-19', 'time' => '17:30'], 'de');

assert_contains($uhr, 'data-countdown="2027-06-19T17:30"', 'uhr: die Uhrzeit reist mit');
foreach (['days', 'hours', 'minutes', 'seconds'] as $feld) {
    assert_contains($uhr, 'data-countdown-' . $feld, 'uhr: das Feld ' . $feld . ' steht da');
}
assert_contains($uhr, 'Sekunden', 'uhr: die Woerter kommen vom Server, nicht aus dem Skript');
assert_contains($uhr, '19. Juni 2027', 'uhr: ohne Skript traegt das gedruckte Datum den Abschnitt');

// Ohne Uhrzeit faengt der Tag um Mitternacht an - nicht irgendwann.
$ohneZeit = DesignSections::html($uhrDoc, ['date' => '2027-06-19'], 'de');
assert_contains($ohneZeit, 'data-countdown="2027-06-19T00:00"', 'uhr: ohne Uhrzeit zaehlt sie bis Mitternacht');

// Die ruhige Gestalt bleibt, was sie war: eine Zahl, kein Wecker.
$zahl = DesignSections::html(ort_doc([['id' => 'cd-1', 'type' => 'countdown']]), ['date' => '2027-06-19'], 'de');
assert_contains($zahl, 'data-countdown-days', 'countdown: die Voreinstellung zaehlt Tage');
assert_not_contains($zahl, 'data-countdown-seconds', 'countdown: die Voreinstellung zaehlt keine Sekunden');

assert_true(SectionRegistry::isVariant('countdown', 'uhr'), 'katalog: die Uhr steht im Katalog');
assert_contains(
    DesignSections::css($uhrDoc, '.d-probe'),
    '.d-sec-v-uhr .d-sec-uhr{',
    'uhr: die Variante bringt ihren Stil mit'
);
