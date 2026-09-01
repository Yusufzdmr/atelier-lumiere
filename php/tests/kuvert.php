<?php

declare(strict_types=1);

use Atelier\Design;

/*
 * Der Umschlag - oder keiner.
 *
 * "Ya kafayi yicem (...) ben zaten video acilisi koymusum, neden bir de zarf
 * acilisi var, onu kaldirma ekle - kaldir video acilisi olanlardan mesela."
 *
 * Wer einen Film als Oeffnung hinlegt, hat die Oeffnung schon gebaut. Ein
 * gezeichnetes Kuvert davor ist eine zweite, und zwei Oeffnungen
 * hintereinander sind eine zu viel.
 */

/* --- Die Voreinstellung aendert keine bestehende Vorlage --- */

/*
 * WAHR, und das ist keine Vorliebe: keine bestehende Vorlage hat dieses Feld,
 * und keine von ihnen soll sich beim naechsten Speichern anders oeffnen als
 * gestern.
 */
$leer = Design::complete(['id' => 'p', 'slug' => 'p']);
assert_true($leer['intro']['kuvert'], 'Kuvert: ohne Angabe steht er da, wie bisher');

$aus = Design::complete(['id' => 'p', 'slug' => 'p', 'intro' => ['kuvert' => false]]);
assert_true(!$aus['intro']['kuvert'], 'Kuvert: und er laesst sich abwaehlen');

// Der Film daneben bleibt, was er war - die beiden sind zwei Fragen.
$mitFilm = Design::complete(['id' => 'p', 'slug' => 'p',
    'intro' => ['video' => '/uploads/designs/auf.mp4', 'kuvert' => false]]);
assert_same('/uploads/designs/auf.mp4', $mitFilm['intro']['video'], 'Kuvert: der Film bleibt');
assert_true(!$mitFilm['intro']['kuvert'], 'Kuvert: und ist trotzdem weg');

/* --- Der Weg aus dem Formular --- */

/*
 * Am Pfadfeld daneben festgemacht und nicht an einem eigenen Marker: die
 * beiden stehen in derselben Schublade des Editors, und wer die Schublade
 * schickt, schickt beide. Ein Haken, der fehlt, ist dann eine Aussage.
 */
$vorher = Design::complete(['id' => 'p', 'slug' => 'p']);

$ohne = Design::fromPost($vorher, ['intro_video' => '/uploads/designs/auf.mp4']);
assert_true(!$ohne['intro']['kuvert'], 'Formular: kein Haken heisst kein Kuvert');

$mit = Design::fromPost($ohne, ['intro_video' => '/uploads/designs/auf.mp4', 'intro_kuvert' => '1']);
assert_true($mit['intro']['kuvert'], 'Formular: und mit Haken steht er wieder da');

/*
 * Ohne die Schublade bleibt alles unberuehrt. Sonst verloere jedes Speichern
 * aus einem anderen Teil des Panels den Umschlag - dieselbe Vorsicht wie bei
 * den Rollen und den Zeichen.
 */
$fremd = Design::fromPost($mit, ['name_de' => 'Probe']);
assert_true($fremd['intro']['kuvert'], 'Formular: ohne das Feld daneben keine Aenderung');

/* --- Was die Buehne daraus macht --- */

$buehne = (string) file_get_contents(__DIR__ . '/../templates/partials/design-stage.php');

assert_contains($buehne, '$mitKuvert = (bool) ($design[\'intro\'][\'kuvert\'] ?? true);',
    'Buehne: sie liest es selbst aus dem Dokument');
assert_contains($buehne, '<?php if ($mitKuvert) : ?>', 'Buehne: ohne Kuvert steht kein Kuvert da');

/*
 * Ohne Kuvert traegt der Filmkasten, was sonst am Kuvert steht - sonst
 * stuenden dieselben zwei Zahlen an zwei Orten.
 */
assert_contains($buehne, "\$mitKuvert ? '' : 'data-sofort data-animation=\"'",
    'Buehne: der Filmkasten uebernimmt Art und Dauer');

/*
 * Und er deckt von Anfang an: ein durchsichtiger Kasten liesse die Karte
 * sekundenlang sehen, bevor der Film sie zudeckt.
 */
assert_contains($buehne, "opacity:<?= \$mitKuvert ? '0' : '1' ?>", 'Buehne: ohne Kuvert deckt der Film sofort');

/* --- Und was das Skript daraus macht --- */

$skript = (string) file_get_contents(__DIR__ . '/../public/assets/invitation.js');

assert_contains($skript, 'var sofort = document.querySelector("[data-intro-video][data-sofort]");',
    'Skript: es kennt den Fall ohne Kuvert');
assert_contains($skript, 'var quelle = envelope || sofort;',
    'Skript: und liest Art und Dauer von dem, den es gibt');
assert_contains($skript, 'if (envelope) envelope.style.pointerEvents = "none";',
    'Skript: kein Griff an ein Kuvert, das es nicht gibt');

/*
 * Es faengt von allein an - aber erst, wenn die Laenge des Films feststeht.
 * Sonst faende reveal() eine 0 vor und schnitte jeden Film beim Notnagel von
 * sechs Sekunden ab.
 */
assert_contains($skript, 'film.addEventListener("loadedmetadata", reveal, { once: true });',
    'Skript: gestartet wird, wenn die Laenge bekannt ist');
assert_contains($skript, 'setTimeout(reveal, 2500);',
    'Skript: und spaetestens dann, wenn sie nie bekannt wird');

/* --- Das Panel bietet es an --- */

$tafel = (string) file_get_contents(__DIR__ . '/../templates/admin/design-edit-sections.php');

assert_contains($tafel, 'name="intro_kuvert"', 'Panel: der Haken steht beim Vorspann');
assert_contains($tafel, 'Zarf açılışı göster', 'Panel: und sagt auf Tuerkisch, was er tut');
