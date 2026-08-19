<?php
declare(strict_types=1);

use Atelier\Design;

/*
 * Faz 2: Was das Formular mit dem Dokument macht.
 *
 * Die Zusammenfuehrung liegt absichtlich in einer reinen Funktion und nicht im
 * Controller: die Grenze dieser Phase - dass hier nichts an box, canvas oder
 * sections schreibt - haelt nur, wenn ein Test sie aussprechen kann.
 */

$basis = Design::complete([
    'id'      => 'pruef',
    'slug'    => 'pruef',
    'name'    => ['de' => 'Prüf', 'en' => 'Test'],
    'status'  => 'draft',
    'canvas'  => ['ratio' => '632:490', 'safe' => 6],
    'palette' => ['accent' => ['value' => '#B08D57', 'customer' => false]],
    'fonts'   => ['display' => ['family' => 'Cormorant Garamond', 'weight' => 300]],
    'layers'  => [
        ['id' => 'gruss', 'type' => 'text', 'spot' => 'card',
         'text' => ['de' => 'Wir heiraten', 'en' => 'We marry'],
         'box' => ['x' => 8, 'y' => 12, 'w' => 85],
         'motion' => ['move' => 'fade', 'delay' => 300, 'duration' => 1000],
         'permissions' => ['text' => true]],
    ],
]);

/* --- Bekannte Werte kommen durch --- */

$neu = Design::fromPost($basis, [
    'name_de'        => 'Élysée',
    'category'       => 'luxury',
    'palette_accent' => '#C9A24B',
    'text_de_gruss'  => 'Wir feiern',
    'move_gruss'     => 'rise',
]);

assert_same('Élysée', $neu['name']['de'], 'fromPost: Name wird uebernommen');
assert_same('luxury', $neu['category'], 'fromPost: Kategorie wird uebernommen');
assert_same('#C9A24B', $neu['palette']['accent']['value'], 'fromPost: Farbe wird uebernommen');
assert_same('Wir feiern', $neu['layers'][0]['text']['de'], 'fromPost: Text wird uebernommen');
assert_same('rise', $neu['layers'][0]['motion']['move'], 'fromPost: Bewegung wird uebernommen');

/* --- Ein leeres Feld loescht nichts --- */

$leer = Design::fromPost($basis, ['name_de' => '', 'text_de_gruss' => '', 'palette_accent' => '']);

assert_same('Prüf', $leer['name']['de'], 'fromPost: leerer Name loescht nicht');
assert_same('Wir heiraten', $leer['layers'][0]['text']['de'], 'fromPost: leerer Text loescht nicht');
assert_same('#B08D57', $leer['palette']['accent']['value'], 'fromPost: leere Farbe loescht nicht');

/* --- Unbekannte Werte fallen auf die Voreinstellung --- */

$quatsch = Design::fromPost($basis, ['move_gruss' => 'salto', 'anim_intro' => 'discokugel']);

assert_same('none', $quatsch['layers'][0]['motion']['move'], 'fromPost: unbekannte Bewegung faellt zurueck');

/* --- rgba bleibt, die bestehenden Themen benutzen es --- */

$rgba = Design::fromPost($basis, ['palette_accent' => 'rgba(176,141,87,0.30)']);

assert_same('rgba(176,141,87,0.30)', $rgba['palette']['accent']['value'], 'fromPost: rgba ueberlebt');

/* --- Rechte gehen hin und zurueck --- */

$rechte = Design::fromPost($basis, ['perm_color_gruss' => 'an']);

assert_same(true, $rechte['layers'][0]['permissions']['color'], 'fromPost: gesetztes Recht kommt an');
assert_same(false, $rechte['layers'][0]['permissions']['text'], 'fromPost: fehlendes Haekchen loescht das Recht');

/* --- Die Grenze der Phase: box, canvas und sections bleiben unberuehrt --- */

$angriff = Design::fromPost($basis, [
    'box_x_gruss'  => '99',
    'box_y_gruss'  => '99',
    'canvas_ratio' => '1:1',
    'canvas_safe'  => '40',
    'sections'     => 'etwas',
    'layers'       => 'etwas',
    'version'      => '999',
]);

assert_same(8, $angriff['layers'][0]['box']['x'], 'fromPost: box bleibt unberuehrt');
assert_same(12, $angriff['layers'][0]['box']['y'], 'fromPost: box bleibt unberuehrt (y)');
assert_same('632:490', $angriff['canvas']['ratio'], 'fromPost: canvas bleibt unberuehrt');
assert_same(6, $angriff['canvas']['safe'], 'fromPost: canvas safe bleibt unberuehrt');
assert_same([], $angriff['sections'], 'fromPost: sections bleibt unberuehrt');
assert_same(1, count($angriff['layers']), 'fromPost: die Ebenenliste kommt nicht aus dem Formular');

/* --- Kopieren: eine neue Vorlage faengt bei eins an --- */

$quelle = Design::complete([
    'id' => 'elysee', 'slug' => 'elysee',
    'name' => ['de' => 'Élysée', 'en' => 'Élysée'],
    'status' => 'active', 'version' => 7, 'category' => 'luxury',
    'palette' => ['accent' => ['value' => '#B08D57']],
    'layers' => [['id' => 'gruss', 'type' => 'text', 'text' => ['de' => 'Hallo']]],
]);

$kopie = Design::copy($quelle, 'Élysée Nacht', ['de' => 'Élysée Nacht', 'en' => 'Élysée Night']);

assert_same('elysee-nacht', $kopie['id'], 'copy: die Kennung kommt aus dem Namen');
assert_same('elysee-nacht', $kopie['slug'], 'copy: der Slug ist die Kennung');
assert_same('Élysée Nacht', $kopie['name']['de'], 'copy: der neue Name steht drin');
assert_same('draft', $kopie['status'], 'copy: eine Kopie ist ein Entwurf');
assert_same(1, $kopie['version'], 'copy: eine neue Vorlage faengt bei Fassung eins an');
assert_same('#B08D57', $kopie['palette']['accent']['value'], 'copy: die Farben kommen mit');
assert_same('gruss', $kopie['layers'][0]['id'], 'copy: die Ebenen kommen mit');

// Die Quelle darf sich nicht veraendern - sie liegt in der Datenbank.
assert_same('elysee', $quelle['id'], 'copy: die Quelle bleibt, wie sie war');
assert_same(7, $quelle['version'], 'copy: die Quelle behaelt ihre Fassung');
