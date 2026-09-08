<?php
declare(strict_types=1);

/**
 * Einmalige Korrektur, 08.09.2026: "şu zarf açılışını kaldır artık" - bei
 * allen bestehenden Themen die Zarf-Oeffnung ausschalten (intro.kuvert =>
 * false). Das ist derselbe Haken wie im Themen-Editor
 * (design-edit-sections.php, "Zarf açılışı göster"); dieses Skript setzt ihn
 * nur bei jedem Thema auf einmal, statt es von Hand durchzuklicken.
 *
 * Design::save() zaehlt die Fassung nur hoch, wenn sich wirklich etwas
 * aendert - ein Thema, das den Haken schon aus hatte, bleibt unangetastet.
 *
 * Betrifft NUR die designs-Tabelle. Bereits verschickte Einladungen
 * (invitations_v2) tragen ihren eigenen eingefrorenen Sockel und aendern
 * sich hier bewusst nicht mit - dieselbe Zusage wie bei jeder anderen
 * Themenaenderung (Design::css()-Kommentar, "Tema versiyonlama").
 *
 *   php bin/zarf-aus.php
 */

if (PHP_SAPI !== 'cli') {
    exit('Nur über die Kommandozeile.');
}

require __DIR__ . '/../src/bootstrap.php';

use Atelier\Design;

$geaendert = 0;
foreach (Design::all() as $doc) {
    if (($doc['intro']['kuvert'] ?? true) === false) {
        echo "unveraendert  " . $doc['slug'] . "\n";
        continue;
    }
    $doc['intro']['kuvert'] = false;
    Design::save($doc);
    $geaendert++;
    echo "ausgeschaltet " . $doc['slug'] . "\n";
}

echo $geaendert . " von " . count(Design::all()) . " Themen geaendert.\n";
