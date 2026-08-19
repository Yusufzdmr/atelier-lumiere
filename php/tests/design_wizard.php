<?php
declare(strict_types=1);

use Atelier\DesignWizard;

/*
 * Was darf der Kunde - und was fragt der Assistent ueberhaupt?
 *
 * Zwei Mechanismen, die leicht verwechselt werden: die Felder kommen aus den
 * bind-Namen, die das Design benutzt. Die Extras kommen aus den Rechten. Wer
 * nur die Rechte liest, bekommt einen leeren Assistenten - im heutigen
 * Bestand steht fast jedes Recht auf false.
 */

/** Ein Dokument mit genau den Ebenen, die der Test braucht. */
function wizard_doc(array $layers, array $palette = [], array $fonts = []): array
{
    return [
        'id' => 'test', 'slug' => 'test',
        'palette' => $palette,
        'fonts'   => $fonts,
        'layers'  => $layers,
    ];
}

$doc = wizard_doc([
    ['id' => 'namen', 'type' => 'text', 'bind' => 'couple_names'],
    ['id' => 'datum', 'type' => 'text', 'bind' => 'wedding_date'],
]);

$w = DesignWizard::choices($doc);

assert_same(['bride', 'groom', 'date'], $w['fields'], 'choices: couple_names fragt bride und groom, wedding_date das Datum');
assert_same([], $w['layers'], 'choices: ohne edit-Recht keine Ebene');
assert_same([], $w['palette'], 'choices: ohne customer-Haken keine Farbmarke');

// Ein bind, das die Vorlage nicht benutzt, wird nicht gefragt.
assert_true(!in_array('hashtag', $w['fields'], true), 'choices: nicht benutztes bind wird nicht gefragt');

// Dieselbe Frage zweimal gestellt bleibt eine Frage.
$doppelt = DesignWizard::choices(wizard_doc([
    ['id' => 'a', 'type' => 'text', 'bind' => 'couple_names'],
    ['id' => 'b', 'type' => 'text', 'bind' => 'bride_name'],
]));
assert_same(['bride', 'groom'], $doppelt['fields'], 'choices: vier binds, zwei Felder');

// edit ist der Hauptschalter: ohne ihn zaehlen die anderen fuenf nicht.
$zu = DesignWizard::choices(wizard_doc([
    ['id' => 'namen', 'type' => 'text', 'bind' => 'couple_names',
     'permissions' => ['edit' => false, 'color' => true, 'hide' => true]],
]));
assert_same([], $zu['layers'], 'choices: edit aus, also zaehlt color nicht');

$auf = DesignWizard::choices(wizard_doc([
    ['id' => 'namen', 'type' => 'text', 'bind' => 'couple_names',
     'permissions' => ['edit' => true, 'color' => true]],
]));
assert_same(['color' => true, 'font' => false, 'text' => false, 'photo' => false, 'hide' => false],
    $auf['layers']['namen'], 'choices: edit an, color an');

// text auf einer Ebene mit bind ist sinnlos - der Wert kommt aus den Daten.
$gebunden = DesignWizard::choices(wizard_doc([
    ['id' => 'namen', 'type' => 'text', 'bind' => 'couple_names',
     'permissions' => ['edit' => true, 'text' => true]],
]));
assert_same([], $gebunden['layers'], 'choices: text-Recht auf gebundener Ebene wird verworfen');

// photo nur, wo ein Bild steht.
$bild = DesignWizard::choices(wizard_doc([
    ['id' => 'foto', 'type' => 'photo', 'permissions' => ['edit' => true, 'photo' => true]],
    ['id' => 'zeile', 'type' => 'text', 'bind' => 'hashtag', 'permissions' => ['edit' => true, 'photo' => true]],
]));
assert_true(isset($bild['layers']['foto']), 'choices: photo-Recht auf einem Bild zaehlt');
assert_true(!isset($bild['layers']['zeile']), 'choices: photo-Recht auf Text zaehlt nicht');

// Marken: nur die mit Haken.
$marken = DesignWizard::choices(wizard_doc(
    [['id' => 'a', 'type' => 'text', 'bind' => 'hashtag']],
    ['accent' => ['value' => '#B08D57', 'customer' => true],
     'ink'    => ['value' => '#1A1A1A', 'customer' => false]],
    ['script' => ['family' => 'Great Vibes', 'customer' => true]]
));
assert_same(['accent'], array_keys($marken['palette']), 'choices: nur angehakte Farbmarke');
assert_same(['script'], array_keys($marken['fonts']), 'choices: nur angehakte Schriftmarke');
