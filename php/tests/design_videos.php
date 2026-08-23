<?php
declare(strict_types=1);

use Atelier\DesignVideos;

/* --- Vollstaendig machen: fremde Adressen fallen, Kennungen entstehen --- */

$rows = DesignVideos::complete([
    ['id' => 'schwaene', 'label' => 'Schwäne', 'mp4' => '/uploads/videos/a.mp4',
     'webm' => '/uploads/videos/a.webm', 'poster' => '/uploads/videos/a.jpg', 'category' => 'floral'],
    ['label' => 'Ohne Kennung', 'mp4' => '/uploads/videos/b.mp4'],
    ['label' => 'Fremd', 'mp4' => 'https://beispiel.de/c.mp4'],
    ['label' => 'Ohne Film', 'poster' => '/uploads/videos/d.jpg'],
    'kein Array',
]);

assert_same(2, count($rows), 'videos: ohne gueltigen Film faellt der Eintrag weg');
assert_same('schwaene', $rows[0]['id'], 'videos: Kennung bleibt');
assert_same('/uploads/videos/a.webm', $rows[0]['webm'], 'videos: webm kommt durch');
assert_true($rows[1]['id'] !== '', 'videos: fehlende Kennung wird erzeugt');
assert_same('', $rows[1]['webm'], 'videos: ohne webm bleibt das Feld leer');

/* --- Unbekannte Kategorie faellt auf leer, nicht auf Unsinn --- */

$k = DesignVideos::complete([
    ['id' => 'a', 'mp4' => '/uploads/videos/a.mp4', 'category' => 'gibtesnicht'],
    ['id' => 'b', 'mp4' => '/uploads/videos/b.mp4', 'category' => 'floral'],
]);

assert_same('', $k[0]['category'], 'videos: unbekannte Kategorie wird leer');
assert_same('floral', $k[1]['category'], 'videos: bekannte Kategorie bleibt');

/* --- Kennungen sind eindeutig: zwei gleiche waeren im Formular ein Ort --- */

$doppelt = DesignVideos::complete([
    ['id' => 'a', 'mp4' => '/uploads/videos/1.mp4'],
    ['id' => 'a', 'mp4' => '/uploads/videos/2.mp4'],
]);

assert_true($doppelt[0]['id'] !== $doppelt[1]['id'], 'videos: doppelte Kennung wird aufgeloest');
