<?php
declare(strict_types=1);

use Atelier\DesignImages;

/* --- Vollstaendig machen: fremde Adressen fallen, Kennungen entstehen --- */

$rows = DesignImages::complete([
    ['id' => 'goldrand', 'label' => 'Goldrand', 'src' => '/uploads/designs/a.webp', 'category' => 'floral'],
    ['label' => 'Ohne Kennung', 'src' => '/uploads/designs/b.webp'],
    ['label' => 'Fremd', 'src' => 'https://beispiel.de/c.webp'],
    ['label' => 'Ohne Bild'],
    'kein Array',
]);

assert_same(2, count($rows), 'images: ohne gueltigen Pfad faellt der Eintrag weg');
assert_same('goldrand', $rows[0]['id'], 'images: Kennung bleibt');
assert_same('/uploads/designs/a.webp', $rows[0]['src'], 'images: Pfad kommt durch');
assert_true($rows[1]['id'] !== '', 'images: fehlende Kennung wird erzeugt');

/* --- Unbekannte Kategorie faellt auf leer, nicht auf Unsinn --- */

$k = DesignImages::complete([
    ['id' => 'a', 'src' => '/uploads/designs/a.webp', 'category' => 'gibtesnicht'],
    ['id' => 'b', 'src' => '/uploads/designs/b.webp', 'category' => 'floral'],
]);

assert_same('', $k[0]['category'], 'images: unbekannte Kategorie wird leer');
assert_same('floral', $k[1]['category'], 'images: bekannte Kategorie bleibt');

/* --- Kennungen sind eindeutig: zwei gleiche waeren im Formular ein Ort --- */

$doppelt = DesignImages::complete([
    ['id' => 'a', 'src' => '/uploads/designs/1.webp'],
    ['id' => 'a', 'src' => '/uploads/designs/2.webp'],
]);

assert_true($doppelt[0]['id'] !== $doppelt[1]['id'], 'images: doppelte Kennung wird aufgeloest');

/*
 * Und mehr als MAX bricht ab - eine Liste ohne Ende waere ein Formular ohne
 * Ende. Dieselbe Grenze wie bei der Filmbibliothek.
 */
$viele = [];
for ($i = 0; $i < DesignImages::MAX + 5; $i++) {
    $viele[] = ['id' => 'b' . $i, 'src' => '/uploads/designs/' . $i . '.webp'];
}
assert_same(DesignImages::MAX, count(DesignImages::complete($viele)), 'images: MAX deckelt die Liste');

/* --- Das Panel bietet die Bibliothek an --- */

$katalog = (string) file_get_contents(__DIR__ . '/../templates/admin/designs.php');
assert_contains($katalog, 'Bildbibliothek', 'Panel: die Bildbibliothek hat ihren Platz im Katalog');
assert_contains($katalog, 'name="img_neu_datei"', 'Panel: ein neues Bild laesst sich hochladen');
assert_contains($katalog, 'value="bilder-kaydet"', 'Panel: und die Liste laesst sich speichern');
assert_contains($katalog, 'name="was" value="bild-loeschen-', 'Panel: ein Eintrag laesst sich entfernen');

$steuer = (string) file_get_contents(__DIR__ . '/../src/Controllers/DesignAdminController.php');
assert_contains($steuer, "\$was === 'bilder-kaydet'", 'Controller: kennt die Speicher-Aktion');
assert_contains($steuer, "str_starts_with(\$was, 'bild-loeschen-')", 'Controller: kennt die Loesch-Aktion');
assert_contains($steuer, 'Media::storeGraphic($datei', 'Controller: neue Bilder gehen durch storeGraphic, nicht store');

/* --- Und der Abschnitts-Editor bietet sie als Blatt an --- */

$tafel = (string) file_get_contents(__DIR__ . '/../templates/admin/design-edit-tafeln.php');
assert_contains($tafel, 'data-blattwahl="sec_bg_<?= $i ?>"',
    'Tafel: jeder Abschnitt bekommt seine eigene Auswahl aus der Bibliothek');
assert_contains($tafel, 'data-vorschau-pfad="sec_bg_<?= $i ?>"',
    'Tafel: das Blatt-Vorschaukaestchen haengt am Pfadfeld, nicht nur am Dateifeld');

$skript = (string) file_get_contents(__DIR__ . '/../public/assets/design-editor.js');
assert_contains($skript, 'data-blattwahl', 'Skript: kennt die Auswahl');
assert_contains($skript, 'wahl.getAttribute("data-blattwahl")',
    'Skript: eine Stelle bedient jeden Abschnitt - der Feldname kommt vom Attribut, nicht von einer festen Kennung');
