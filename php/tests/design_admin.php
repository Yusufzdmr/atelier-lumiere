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

/* --- Der Kasten kommt jetzt aus dem Formular, das Seitenverhaeltnis nicht --- */

/*
 * Bis hierher stand hier "box bleibt unberuehrt". Das war richtig, solange es
 * kein Feld dafuer gab: eine Ebene, die man nicht bewegen kann, ist besser als
 * eine, die ein streunender Formularwert verschiebt. Jetzt gibt es Felder, und
 * die Grenze liegt eine Stelle weiter - der Kasten gehoert dem Editor, das
 * Seitenverhaeltnis der Vorlage. Ein canvas_* im Formular bleibt wirkungslos.
 */

$angriff = Design::fromPost($basis, [
    'box_x_gruss'  => '99',
    'box_y_gruss'  => '99',
    'canvas_ratio' => '1:1',
    'canvas_safe'  => '40',
    'sections'     => 'etwas',
    'layers'       => 'etwas',
    'version'      => '999',
]);

assert_same(99, $angriff['layers'][0]['box']['x'], 'fromPost: der Kasten kommt aus dem Formular');
assert_same(99, $angriff['layers'][0]['box']['y'], 'fromPost: der Kasten kommt aus dem Formular (y)');
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

/*
 * ------------------------------------------------------------------
 * Faz 4: Der Kasten. Verschieben, drehen, stapeln, wegnehmen.
 * ------------------------------------------------------------------
 *
 * Bis hierher konnte der Editor eine Ebene faerben, beschriften und bewegen,
 * aber nicht hinstellen. Wer eine neue anlegte, bekam einen von drei festen
 * Zuschnitten und danach nie wieder eine Handhabe. Das war die letzte Stelle,
 * an der eine Vorlage nur ueber die Datenbank entstehen konnte.
 */

$dreiEbenen = Design::complete([
    'id' => 'pruef4', 'slug' => 'pruef4',
    'canvas' => ['ratio' => '632:490', 'safe' => 6],
    'layers' => [
        ['id' => 'grund', 'type' => 'image', 'spot' => 'card', 'box' => ['x' => 0, 'y' => 0, 'w' => 100]],
        ['id' => 'name',  'type' => 'text',  'spot' => 'card', 'box' => ['x' => 8, 'y' => 20, 'w' => 85]],
        ['id' => 'ecke',  'type' => 'image', 'spot' => 'card', 'box' => ['x' => 4, 'y' => 4, 'w' => 17]],
    ],
]);

/* --- Jede Zahl des Kastens hat ein Feld --- */

$gestellt = Design::fromPost($dreiEbenen, [
    'box_x_name'       => '12',
    'box_y_name'       => '34',
    'box_w_name'       => '60',
    'box_h_name'       => '25',
    'box_rotate_name'  => '-15',
    'box_opacity_name' => '80',
    'box_anchor_name'  => 'bottomright',
    'box_flipx_name'   => '1',
]);

assert_same(12, $gestellt['layers'][1]['box']['x'], 'Kasten: x kommt an');
assert_same(34, $gestellt['layers'][1]['box']['y'], 'Kasten: y kommt an');
assert_same(60, $gestellt['layers'][1]['box']['w'], 'Kasten: Breite kommt an');
assert_same(25, $gestellt['layers'][1]['box']['h'], 'Kasten: Hoehe kommt an');
assert_same(-15, $gestellt['layers'][1]['box']['rotate'], 'Kasten: Drehung kommt an');
assert_same(80, $gestellt['layers'][1]['box']['opacity'], 'Kasten: Deckkraft kommt an');
assert_same('bottomright', $gestellt['layers'][1]['box']['anchor'], 'Kasten: der Anker kommt an');
assert_same(1, $gestellt['layers'][1]['box']['flipx'], 'Kasten: gespiegelt');
assert_same(0, $gestellt['layers'][1]['box']['flipy'], 'Kasten: ohne Haken nicht gespiegelt');

// Die Nachbarn bleiben stehen: ein Formular, das nur eine Ebene nennt, darf
// die anderen nicht mitziehen.
assert_same(0, $gestellt['layers'][0]['box']['x'], 'Kasten: die Nachbarebene bleibt stehen');
assert_same(17, $gestellt['layers'][2]['box']['w'], 'Kasten: die Nachbarebene behaelt ihre Breite');

/* --- Was ausserhalb liegt, wird geklemmt, nicht abgelehnt --- */

$masslos = Design::fromPost($dreiEbenen, [
    'box_x_name'       => '9999',
    'box_w_name'       => '0',
    'box_opacity_name' => '-40',
    'box_anchor_name'  => 'schraeg',
]);

assert_same(150, $masslos['layers'][1]['box']['x'], 'Kasten: zu weit rechts wird geklemmt');
assert_same(1, $masslos['layers'][1]['box']['w'], 'Kasten: Breite null wird geklemmt');
assert_same(0, $masslos['layers'][1]['box']['opacity'], 'Kasten: negative Deckkraft wird geklemmt');
assert_same('topleft', $masslos['layers'][1]['box']['anchor'], 'Kasten: unbekannter Anker faellt zurueck');

/*
 * --- Die Reihenfolge ist der z-Index ---
 *
 * Design::css() schreibt z-index als index+1, es gibt also kein eigenes Feld
 * dafuer. Wer stapeln will, ordnet die Liste um - und die Liste kommt als
 * eine Kennungsreihe aus dem Formular, damit "hoch" ein Klick ist und kein
 * Zahlenraten.
 */

$umgeordnet = Design::fromPost($dreiEbenen, ['ebenen_reihenfolge' => 'name,ecke,grund']);

assert_same('name', $umgeordnet['layers'][0]['id'], 'Reihenfolge: die erste steht vorn');
assert_same('ecke', $umgeordnet['layers'][1]['id'], 'Reihenfolge: die zweite in der Mitte');
assert_same('grund', $umgeordnet['layers'][2]['id'], 'Reihenfolge: die dritte hinten');
assert_same(3, count($umgeordnet['layers']), 'Reihenfolge: es geht keine verloren');

// Der Kasten faehrt mit der Ebene mit und nicht mit ihrem Platz.
assert_same(20, $umgeordnet['layers'][0]['box']['y'], 'Reihenfolge: der Kasten bleibt bei seiner Ebene');

/* --- Fehlt die Reihe, bleibt die Liste, wie sie war --- */

$ohneReihe = Design::fromPost($dreiEbenen, ['box_x_name' => '12']);

assert_same('grund', $ohneReihe['layers'][0]['id'], 'Reihenfolge: ohne Feld bleibt die Liste stehen');
assert_same(3, count($ohneReihe['layers']), 'Reihenfolge: ohne Feld verschwindet nichts');

/*
 * --- Wer nicht in der Reihe steht, ist geloescht ---
 *
 * Loeschen und Umordnen sind dieselbe Bewegung, deshalb dasselbe Feld: eine
 * Zeile aus der Liste nehmen heisst, die Ebene wegnehmen. Ein zweiter Weg mit
 * eigenem Knopf koennte die eine Aenderung speichern und die andere verlieren.
 *
 * Dass ein veraltetes Formular so nichts wegraeumen kann, haelt die
 * Fassungspruefung im Controller - sie laeuft vor fromPost.
 */

$geloescht = Design::fromPost($dreiEbenen, ['ebenen_reihenfolge' => 'grund,name']);

assert_same(2, count($geloescht['layers']), 'Loeschen: die Ebene ist weg');
assert_same('grund', $geloescht['layers'][0]['id'], 'Loeschen: die uebrigen behalten ihre Ordnung');
assert_same('name', $geloescht['layers'][1]['id'], 'Loeschen: die uebrigen behalten ihre Ordnung (2)');

// Eine leere Reihe ist eine Aussage, kein Unfall: die Vorlage hat keine Ebenen
// mehr. Das Formular schickt das Feld immer mit.
$alleWeg = Design::fromPost($dreiEbenen, ['ebenen_reihenfolge' => '']);

assert_same(0, count($alleWeg['layers']), 'Loeschen: eine leere Reihe raeumt alles ab');

// Eine Kennung, die es nicht gibt, erfindet keine Ebene.
$erfunden = Design::fromPost($dreiEbenen, ['ebenen_reihenfolge' => 'grund,gespenst,name,ecke']);

assert_same(3, count($erfunden['layers']), 'Reihenfolge: ein fremder Name erfindet nichts');

/*
 * ------------------------------------------------------------------
 * Variante und Einstellungen kommen aus dem Formular.
 * ------------------------------------------------------------------
 *
 * Wie die Abschnitte selbst: indiziert (sec_variant_0, sec_set_align_0 ...),
 * damit die Reihenfolge im Formular die Reihenfolge im Dokument bleibt.
 *
 * Welche Einstellungen ein Abschnitt ueberhaupt hat, weiss der Katalog - und
 * er weiss es erst, wenn der Typ gelesen ist. Deshalb wird hier NICHT blind
 * alles eingesammelt, was mit sec_set_ anfaengt: ein Ort hat einen
 * Kartenlink, ein Countdown nicht, und ein Wert ohne Schema waere ein
 * Schluessel, der jahrelang mitreist, ohne je etwas zu tun.
 */

$mitVariante = Design::fromPost($basis, [
    'sec_id_0'         => 'ablauf',
    'sec_type_0'       => 'program',
    'sec_variant_0'    => 'zeitstrahl',
    'sec_set_align_0'  => 'left',
    'sec_set_space_0'  => 'weit',

    'sec_id_1'         => 'wo',
    'sec_type_1'       => 'location',
    'sec_variant_1'    => 'discokugel',
    'sec_set_map_1'    => '1',
]);

assert_same('zeitstrahl', $mitVariante['sections'][0]['variant'], 'fromPost: die Variante kommt an');
assert_same('left', $mitVariante['sections'][0]['settings']['align'], 'fromPost: die Ausrichtung kommt an');
assert_same('weit', $mitVariante['sections'][0]['settings']['space'], 'fromPost: die Luft kommt an');
assert_same('default', $mitVariante['sections'][1]['variant'], 'fromPost: eine erfundene Variante faellt zurueck');
assert_same(true, $mitVariante['sections'][1]['settings']['map'], 'fromPost: der Haken kommt an');

// Ein Haken, der nicht mitkommt, ist abgeraeumt - dieselbe Regel wie bei den
// Rechten. Das Formular schickt die Zeile immer mit, also ist sein Fehlen
// eine Aussage und kein Zufall.
$ohneHaken = Design::fromPost($basis, [
    'sec_id_0'   => 'wo',
    'sec_type_0' => 'location',
]);

assert_same(false, $ohneHaken['sections'][0]['settings']['map'], 'fromPost: ohne Haken kein Kartenlink');
assert_same('center', $ohneHaken['sections'][0]['settings']['align'], 'fromPost: ohne Angabe die Voreinstellung');

// Eine Einstellung, die dieser Art nicht gehoert, kommt nicht ins Dokument.
$fremd = Design::fromPost($basis, [
    'sec_id_0'      => 'wann',
    'sec_type_0'    => 'countdown',
    'sec_set_map_0' => '1',
]);

assert_true(
    !array_key_exists('map', $fremd['sections'][0]['settings']),
    'fromPost: eine fremde Einstellung kommt nicht ins Dokument'
);

/*
 * ------------------------------------------------------------------
 * Die Reihenfolge der Abschnitte kommt aus dem Formular.
 * ------------------------------------------------------------------
 *
 * Bisher WAR der Index die Reihenfolge: sec_*_0 stand vor sec_*_1. Das
 * reicht, solange die Zeilen untereinander in einem Formular stehen - aber
 * nicht, sobald man sie in einer Liste schieben kann. Ein Feldname ist die
 * Kennung einer Zeile und darf sich beim Schieben nicht aendern, sonst
 * verliert jedes Feld beim Umsortieren seinen Wert.
 *
 * Deshalb dieselbe Loesung wie bei den Ebenen: eine Reihe von Nummern in
 * einem versteckten Feld. Wer nicht darin steht, ist geloescht - Umordnen
 * und Loeschen sind dieselbe Bewegung.
 */

$dreiAbschnitte = [
    'sec_id_0' => 'wo',    'sec_type_0' => 'location',
    'sec_id_1' => 'wann',  'sec_type_1' => 'countdown',
    'sec_id_2' => 'ablauf', 'sec_type_2' => 'program',
];

$umgedreht = Design::fromPost($basis, $dreiAbschnitte + ['sec_reihenfolge' => '2,0,1']);

assert_same('ablauf', $umgedreht['sections'][0]['id'], 'Abschnitte: die Reihe bestimmt, wer vorn steht');
assert_same('wo', $umgedreht['sections'][1]['id'], 'Abschnitte: und wer in der Mitte');
assert_same('wann', $umgedreht['sections'][2]['id'], 'Abschnitte: und wer hinten');

// Ohne das Feld bleibt es beim Index - ein Aufrufer, der von Abschnitten
// nichts weiss, soll nichts umsortieren.
$ohneReihe = Design::fromPost($basis, $dreiAbschnitte);

assert_same('wo', $ohneReihe['sections'][0]['id'], 'Abschnitte: ohne Reihe zaehlt der Index');
assert_same(3, count($ohneReihe['sections']), 'Abschnitte: ohne Reihe fehlt keiner');

// Wer nicht genannt wird, ist weg.
$geloescht = Design::fromPost($basis, $dreiAbschnitte + ['sec_reihenfolge' => '0,2']);

assert_same(2, count($geloescht['sections']), 'Abschnitte: der ungenannte ist geloescht');
assert_same('wo', $geloescht['sections'][0]['id'], 'Abschnitte: die uebrigen behalten ihre Ordnung');
assert_same('ablauf', $geloescht['sections'][1]['id'], 'Abschnitte: die uebrigen behalten ihre Ordnung (2)');

// Eine Nummer, zu der es keine Zeile gibt, erfindet keinen Abschnitt.
$erfunden = Design::fromPost($basis, $dreiAbschnitte + ['sec_reihenfolge' => '0,9,1']);

assert_same(2, count($erfunden['sections']), 'Abschnitte: eine leere Nummer erfindet nichts');

// Eine leere Reihe raeumt ab - das Formular schickt das Feld immer mit, und
// ein veraltetes faengt die Fassungspruefung im Controller ab.
$alleWeg = Design::fromPost($basis, $dreiAbschnitte + ['sec_reihenfolge' => '']);

assert_same(0, count($alleWeg['sections']), 'Abschnitte: eine leere Reihe raeumt alles ab');
