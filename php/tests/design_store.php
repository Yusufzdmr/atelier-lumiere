<?php
declare(strict_types=1);

use Atelier\Design;

if (!needs_db()) {
    echo "  (übersprungen: keine config.php, kein Datenbanktest)\n";
    return;
}

// bin/test.php hat den Autoloader schon registriert und View.php schon per
// require geladen (nicht require_once) - src/bootstrap.php wuerde View.php
// ein zweites Mal einbinden und e() doppelt erklaeren. Deshalb hier nur das
// eine Stueck aus bootstrap.php nachholen, das wirklich fehlt: die
// Konfiguration fuer die Datenbankverbindung.
Atelier\Config::load(dirname(__DIR__) . '/config.php');

$id = 'testdesign';

// Sauber anfangen, falls ein früherer Lauf abgebrochen ist.
Atelier\Db::run('DELETE FROM designs WHERE id = ?', [$id]);

/* --- Speichern und wiederfinden --- */

Design::save([
    'id'      => $id,
    'slug'    => $id,
    'name'    => ['de' => 'Testdesign', 'en' => 'Test design'],
    'status'  => 'active',
    'palette' => ['accent' => ['value' => '#B08D57']],
    'layers'  => [['id' => 'a', 'type' => 'image', 'src' => '/uploads/a.webp']],
]);

$doc = Design::find($id);

assert_true($doc !== null, 'store: gespeichertes Design wird gefunden');
assert_same('Testdesign', $doc['name']['de'], 'store: Name kommt zurueck');
assert_same('#B08D57', $doc['palette']['accent']['value'], 'store: Palette kommt zurueck');
assert_same(1, count($doc['layers']), 'store: Elemente kommen zurueck');
assert_same(1, $doc['version'], 'store: erste Fassung ist 1');

/* --- Rechte ueberleben den Weg durch die Datenbank --- */

Design::save([
    'id' => $id, 'slug' => $id, 'status' => 'active',
    'layers' => [['id' => 'a', 'type' => 'text', 'permissions' => ['color' => true, 'text' => true]]],
]);

$doc = Design::find($id);

assert_same(true, $doc['layers'][0]['permissions']['color'], 'store: Recht color bleibt erhalten');
assert_same(true, $doc['layers'][0]['permissions']['text'], 'store: Recht text bleibt erhalten');
assert_same(false, $doc['layers'][0]['permissions']['font'], 'store: ungesetztes Recht bleibt zu');

/* --- Ohne Aenderung keine neue Fassung --- */

$vorher = Design::find($id)['version'];
Design::save(Design::find($id));
assert_same($vorher, Design::find($id)['version'], 'store: gleicher Inhalt zaehlt nicht hoch');

/* --- Mit Aenderung schon --- */

$doc = Design::find($id);
$doc['name']['de'] = 'Anderer Name';
Design::save($doc);
assert_same($vorher + 1, Design::find($id)['version'], 'store: echte Aenderung zaehlt hoch');

/* --- all() filtert nach Zustand --- */

$aktive = Design::all('active');
$ids = array_column($aktive, 'id');
assert_true(in_array($id, $ids, true), 'store: all(active) enthaelt das Design');

$doc = Design::find($id);
$doc['status'] = 'inactive';
Design::save($doc);

$ids = array_column(Design::all('active'), 'id');
assert_true(!in_array($id, $ids, true), 'store: all(active) laesst inaktive weg');
assert_true(in_array($id, array_column(Design::all(), 'id'), true), 'store: all() zeigt alle');

/* --- Unbekanntes Design ist null, kein Fehler --- */

assert_same(null, Design::find('gibtesnicht'), 'store: unbekanntes Design ist null');

Atelier\Db::run('DELETE FROM designs WHERE id = ?', [$id]);
