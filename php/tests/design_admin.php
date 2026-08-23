<?php
declare(strict_types=1);

use Atelier\Design;

/*
 * Faz 2: Was das Formular mit dem Dokument macht.
 *
 * Die Zusammenfuehrung liegt absichtlich in einer reinen Funktion und nicht im
 * Controller: die Grenze dieser Phase - dass box und canvas unangetastet
 * bleiben, waehrend sections aus dem Formular gelesen wird - haelt nur, wenn
 * ein Test sie aussprechen kann.
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
    'move_gruss'     => 'fade',
]);

assert_same('Élysée', $neu['name']['de'], 'fromPost: Name wird uebernommen');
assert_same('luxury', $neu['category'], 'fromPost: Kategorie wird uebernommen');
assert_same('#C9A24B', $neu['palette']['accent']['value'], 'fromPost: Farbe wird uebernommen');
assert_same('Wir feiern', $neu['layers'][0]['text']['de'], 'fromPost: Text wird uebernommen');
assert_same('fade', $neu['layers'][0]['motion']['move'], 'fromPost: Bewegung wird uebernommen');

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

/* --- Die Grenze der Phase: box und canvas bleiben unberuehrt --- */

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
assert_same(1, count($angriff['layers']), 'fromPost: die Ebenenliste kommt nicht aus dem Formular');

/*
 * Die Grenze verschiebt sich: box und canvas bleiben der vierten Phase, die
 * Abschnitte kommen in der dritten herein. Frueher stand hier
 * "sections bleibt unberuehrt" - das war richtig, solange es keinen Editor
 * dafuer gab. Jetzt gibt es einen, und ein Formularwert, der kein Feld ist,
 * darf trotzdem nichts anrichten.
 */
assert_same([], $angriff['sections'], 'fromPost: sections aus einem Nicht-Feld bleibt leer');

/* --- Der Abschnitts-Editor: eine gute Zeile kommt normalisiert an, eine
       unbekannte Art faellt weg --- */

$mitAbschnitt = Design::fromPost($basis, [
    'sec_id_0'    => 'Ort 1',
    'sec_type_0'  => 'location',
    'sec_title_de_0' => 'Ort',
    'sec_title_en_0' => 'Place',
    'sec_color_0' => 'accent',
    'sec_font_0'  => 'display',
    'sec_on_0'    => '1',
    'perm_sec_edit_0' => '1',
    'perm_sec_hide_0' => '1',

    'sec_id_1'   => 'gibtesnicht',
    'sec_type_1' => 'wetterbericht',
]);

assert_same(1, count($mitAbschnitt['sections']), 'fromPost: unbekannter Typ kommt nicht herein');
assert_same('ort-1', $mitAbschnitt['sections'][0]['id'], 'fromPost: die Kennung wird normalisiert');
assert_same('location', $mitAbschnitt['sections'][0]['type'], 'fromPost: der Typ steht');
assert_same('Ort', $mitAbschnitt['sections'][0]['title']['de'], 'fromPost: der Titel steht');
assert_same('accent', $mitAbschnitt['sections'][0]['style']['color'], 'fromPost: die Farbmarke steht');
assert_same(true, $mitAbschnitt['sections'][0]['permissions']['hide'], 'fromPost: das Recht steht');

$ohneHaken = Design::fromPost($basis, ['sec_id_0' => 'ort-1', 'sec_type_0' => 'location']);
assert_same(false, $ohneHaken['sections'][0]['enabled'], 'fromPost: ohne Haken ist der Abschnitt aus');
assert_same(false, $ohneHaken['sections'][0]['permissions']['edit'], 'fromPost: ohne Haken kein Recht');

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

/* --- Ein Thema ueber eine gemessene Anordnung ziehen --- */

// Aufgefallen beim Bauen des Panels: fromTheme() liefert Farben, Schriften und
// Bewegung, aber keine Karte - die Textebenen der Karte wurden in Faz 1 von
// Hand gemessen und stehen in keinem Thema. Wer aus einem Thema eine neue
// Vorlage macht, nimmt deshalb die Anordnung einer vorhandenen und zieht ihr
// das Thema an. Erfunden wird dabei keine einzige Zahl.

$anordnung = Design::complete([
    'id' => 'basis', 'slug' => 'basis',
    'canvas'  => ['ratio' => '632:490', 'safe' => 6],
    'palette' => ['accent' => ['value' => '#B08D57'], 'bg' => ['value' => '#EFE7DC']],
    'fonts'   => ['display' => ['family' => 'Cormorant Garamond', 'weight' => 300]],
    'animation' => ['intro' => 'sealLight', 'card' => 'seal'],
    'layers'  => [
        ['id' => 'szenetl', 'type' => 'image', 'spot' => 'page', 'src' => '/assets/designs/elysee-1.svg',
         'box' => ['x' => 0, 'y' => 0, 'w' => 17]],
        ['id' => 'marie', 'type' => 'text', 'spot' => 'card', 'bind' => 'bride_name',
         'box' => ['x' => 8, 'y' => 20, 'w' => 85],
         'style' => ['font' => 'display', 'color' => 'accent', 'size' => 111]],
    ],
]);

$thema = Design::fromTheme([
    'id' => 'noir', 'bg' => '#15161B', 'accent' => '#C9A24B', 'fg' => '#F2E7D2',
    'fonts' => ['display' => 'cormorant', 'body' => 'jost', 'script' => 'greatvibes'],
    'intro' => 'darkroom', 'animation' => 'flip',
]);

$angezogen = Design::dress($anordnung, $thema, ['/assets/designs/noir-1.svg']);

assert_same('#C9A24B', $angezogen['palette']['accent']['value'], 'dress: die Farbe kommt vom Thema');
assert_same('darkroom', $angezogen['animation']['intro'], 'dress: die Bewegung kommt vom Thema');
assert_same('Great Vibes', $angezogen['fonts']['script']['family'], 'dress: die Schriften kommen vom Thema');

// Die Anordnung bleibt: Kaesten, Bindungen, Reihenfolge.
assert_same(20, $angezogen['layers'][1]['box']['y'], 'dress: der Kasten bleibt, wie er gemessen wurde');
assert_same('bride_name', $angezogen['layers'][1]['bind'], 'dress: die Bindung bleibt');
assert_same('632:490', $angezogen['canvas']['ratio'], 'dress: das Seitenverhaeltnis bleibt');
assert_same(2, count($angezogen['layers']), 'dress: es kommen keine Ebenen dazu');

// Die gezeichnete Szene wechselt mit, wenn das Thema eine eigene hat.
assert_same('/assets/designs/noir-1.svg', $angezogen['layers'][0]['src'], 'dress: die Szene wechselt zum Thema');

// Hat es keine, bleibt die alte stehen - lieber fremdes Blattwerk als leere Ecken.
$ohne = Design::dress($anordnung, $thema, []);
assert_same('/assets/designs/elysee-1.svg', $ohne['layers'][0]['src'], 'dress: ohne eigene Szene bleibt die alte');

/* --- Video-Ebene: Pfad und Poster kommen aus dem Formular --- */

$mitFilm = Design::complete([
    'id' => 'pruef2', 'slug' => 'pruef2',
    'layers' => [['id' => 'film', 'type' => 'video', 'spot' => 'page']],
]);

$neu = Design::fromPost($mitFilm, [
    'src_film'        => '/uploads/designs/a.mp4',
    'posterpfad_film' => '/uploads/designs/a.jpg',
]);

assert_same('/uploads/designs/a.mp4', $neu['layers'][0]['src'], 'fromPost: Videopfad wird uebernommen');
assert_same('/uploads/designs/a.jpg', $neu['layers'][0]['poster'], 'fromPost: Posterpfad wird uebernommen');

/* --- Ein fremder Poster kommt nicht durch, auch nicht ueber das Formular --- */

$fremd = Design::fromPost($mitFilm, ['posterpfad_film' => 'https://beispiel.de/x.jpg']);
assert_same('', $fremd['layers'][0]['poster'], 'fromPost: fremder Poster wird verworfen');

/* --- Der Oeffnungsfilm laesst sich im Editor setzen und leeren --- */

$basis = Design::complete(['id' => 'pruef3', 'slug' => 'pruef3']);

$mitIntro = Design::fromPost($basis, [
    'intro_video'  => '/uploads/designs/k.mp4',
    'intro_poster' => '/uploads/designs/k.jpg',
]);

assert_same('/uploads/designs/k.mp4', $mitIntro['intro']['video'], 'fromPost: der Oeffnungsfilm wird uebernommen');
assert_same('/uploads/designs/k.jpg', $mitIntro['intro']['poster'], 'fromPost: sein Standbild auch');

// Leeren ist ein Wunsch, kein Unfall: ohne Film laeuft wieder die
// gezeichnete Klappe, und genau das will jemand, der das Feld leert.
$geleert = Design::fromPost($mitIntro, ['intro_video' => '', 'intro_poster' => '']);

assert_same('', $geleert['intro']['video'], 'fromPost: der Oeffnungsfilm laesst sich entfernen');

$fremd = Design::fromPost($basis, ['intro_video' => 'https://beispiel.de/k.mp4']);

assert_same('', $fremd['intro']['video'], 'fromPost: fremder Host wird verworfen');
