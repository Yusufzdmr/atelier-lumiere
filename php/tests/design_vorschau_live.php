<?php
declare(strict_types=1);

use Atelier\Design;
use Atelier\DesignSections;

/*
 * Die Abschnitte im Editor - lebend, und aus derselben Hand wie draussen.
 *
 * Die Karte in der Mitte folgt jedem Tastendruck, weil sie aus CSS-Variablen
 * und Inline-Kaesten besteht: das kann ein Skript. Die Abschnitte kann es
 * nicht - sie sind gedrucktes Markup, je Art ein anderes. Deshalb sah man
 * beim Tippen nichts: "arka plandaki resim gorunmuyor tam bir Live olmuyor".
 *
 * Gerendert wird deshalb weiter auf dem SERVER, nur ohne zu speichern: das
 * Formular geht an einen eigenen Weg, der daraus ein Dokument baut und die
 * Abschnitte zurueckgibt. Ein zweiter Zeichner im Browser waere schneller und
 * haette eine zweite Wahrheit - und die laeuft mit dem naechsten
 * Abschnittstyp auseinander.
 */

/* --- Die Flaeche: eine Stelle statt dreier ------------------------------- */

/*
 * Das Blatt unter den Abschnitten stand zweimal wortgleich in zwei Vorlagen
 * (design-preview und invite-v2-show) - dieselbe Suche nach dem Papier,
 * dieselben zwei Variablen. Die Vorschau waere die dritte Abschrift gewesen.
 */
$doc = DesignSections::complete(['id' => 'pruef', 'slug' => 'pruef', 'sections' => []]);
$doc['sectionsBg']    = '/uploads/designs/blatt.webp';
$doc['sectionsBgEnd'] = '/uploads/designs/schluss.webp';
$doc['layers']        = [];

$flaeche = DesignSections::flaeche($doc, 'd-pruef', '<p>Inhalt</p>');

assert_contains($flaeche, 'd-pruef d-sec-flaeche', 'flaeche: traegt den Geltungsbereich');
assert_contains($flaeche, "--d-sec-blatt:url('/uploads/designs/blatt.webp')", 'flaeche: das Blatt');
assert_contains($flaeche, "--d-sec-blatt-end:url('/uploads/designs/schluss.webp')", 'flaeche: und das des Schlusses');
assert_contains($flaeche, '<p>Inhalt</p>', 'flaeche: der Inhalt steht darin');

// Ohne Inhalt kein Kasten - sonst stuende ein leeres Blatt unter der Karte.
assert_same('', DesignSections::flaeche($doc, 'd-pruef', ''), 'flaeche: leer bleibt leer');

// Ohne eigenes Blatt nimmt sie das Bild der Karte.
$ohne = DesignSections::complete(['id' => 'p2', 'slug' => 'p2', 'sections' => []]);
$ohne['sectionsBg'] = '';
$ohne['layers'] = [[
    'id' => 'grund', 'type' => 'image', 'spot' => 'card', 'src' => '/uploads/designs/karte.webp',
]];
$ohne = Design::complete($ohne);
$ohne['sectionsBg'] = '';
assert_contains(DesignSections::flaeche($ohne, 'd-p2', '<p>x</p>'), 'karte.webp',
    'flaeche: ohne eigenes Blatt laeuft das Papier der Karte weiter');

/* --- Und die drei Aufrufer nehmen sie auch ------------------------------- */

foreach (['pages/design-preview.php', 'pages/invite-v2-show.php'] as $vorlage) {
    $quelle = (string) file_get_contents(__DIR__ . '/../templates/' . $vorlage);
    assert_contains($quelle, 'DesignSections::flaeche', $vorlage . ': nimmt die gemeinsame Flaeche');
    assert_not_contains($quelle, "--d-sec-blatt:url", $vorlage . ': und baut sie nicht mehr selbst');
}

/* --- Der Weg, der rendert und nicht speichert ---------------------------- */

$steuer = (string) file_get_contents(__DIR__ . '/../src/Controllers/DesignAdminController.php');
$router = (string) file_get_contents(__DIR__ . '/../public/index.php');
$js     = (string) file_get_contents(__DIR__ . '/../public/assets/design-editor.js');
$editor = (string) file_get_contents(__DIR__ . '/../templates/admin/design-edit.php');

assert_contains($router, 'vorschau', 'Router: der Weg steht da');
assert_contains($steuer, 'public function vorschau', 'Controller: und die Methode dazu');
assert_contains($editor, 'data-live-abschnitte', 'Editor: der Platz unter der Karte');
assert_contains($js, 'data-live-abschnitte', 'Skript: legt die Antwort dorthin');

/*
 * Das Wichtigste an diesem Weg ist, was er NICHT tut. Design::save darin
 * waere ein zweiter Speicherpfad ohne Fassungspruefung - die Vorschau wuerde
 * beim Tippen ueberschreiben, was ein anderer Tab gerade gespeichert hat.
 */
$von = strpos($steuer, 'public function vorschau');
$bis = strpos($steuer, "\n    }", (int) $von);
$koerper = substr($steuer, (int) $von, (int) $bis - (int) $von);
assert_not_contains($koerper, 'Design::save', 'Vorschau: speichert nichts');
assert_contains($koerper, 'Security::checkCsrf', 'Vorschau: prueft das Token trotzdem');

/*
 * Und die Vorschau darf das Formular nicht vergiften.
 *
 * Der Abschnitt "Zusage" bringt ein echtes Formular mit, samt csrf-Feld. Der
 * Kasten liegt IM Formular des Editors - damit stand das Feld ein zweites
 * Mal darin, leer, weil die Vorschau kein Token vergibt. PHP nimmt bei zwei
 * gleichen Namen den letzten: gemessen am 27.08.2026 an der lebenden Seite
 * kam 419 zurueck, und dasselbe waere beim Speichern passiert. Ein Kasten,
 * der nur zeigen sollte, haette den Knopf daneben unbrauchbar gemacht.
 *
 * Gesperrte Felder werden nicht abgeschickt - der Name ist damit wieder
 * einmalig, und in eine Vorschau tippt ohnehin niemand.
 */
assert_contains($js, 'el.disabled = true', 'Skript: legt die Felder der Vorschau still');
