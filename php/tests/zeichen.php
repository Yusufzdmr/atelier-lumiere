<?php

declare(strict_types=1);

use Atelier\Design;
use Atelier\DesignSections;
use Atelier\SectionRegistry;

/*
 * Eigene Zeichen der Vorlage.
 *
 * "Gunun Programi'nda 'Pasta Kesimi' yazisinin yanina arka plansiz
 * transparan kucuk bir pasta PNG'si koyabilirim."
 *
 * Die Zeilen des Ablaufs gehoeren dem PAAR - Uhrzeit, Titel und die Wahl des
 * Zeichens stehen in seinen Daten. Wie ein Zeichen AUSSIEHT, gehoert der
 * Vorlage. Dieselbe Trennung wie bei den Fotos, und sie ist der Grund, warum
 * hier keine Liste von Bildern je Zeile steht, sondern eine je KENNUNG: das
 * Paar waehlt "pasta", die Vorlage sagt, was eine Torte ist.
 */

function z_doc(array $icons): array
{
    return DesignSections::complete([
        'id' => 'probe', 'slug' => 'probe',
        'palette' => ['accent' => ['value' => '#B08D57']],
        'icons'   => $icons,
        'sections' => [['id' => 'ablauf', 'type' => 'program']],
    ]);
}

$zeilen = ['program' => [['time' => '21:00', 'title' => 'Tortenanschnitt', 'icon' => 'pasta', 'text' => '']]];

/* --- Ohne eigene Datei bleibt alles, wie es war --- */

$ohne = z_doc([]);
$ohneHtml = DesignSections::html($ohne, $zeilen, 'de', '2026-01-01');

assert_same([], $ohne['icons'], 'Zeichen: eine Vorlage ohne Angabe traegt keine Kaesten mit');
assert_contains($ohneHtml, "mask-image:url('/assets/icons/pasta.svg')",
    'Zeichen: das gezeichnete Zeichen des Hauses bleibt der Ersatz');
assert_contains($ohneHtml, 'class="d-ikon d-ikon-pasta"', 'Zeichen: die Kennung wandert als Klasse mit');
assert_true(!str_contains(DesignSections::css($ohne, '.d-p'), '.d-ikon-pasta{'),
    'Zeichen: und kein toter Stilblock');

/* --- Eine eigene PNG: Bild statt Maske --- */

/*
 * Der Kern der Sache. Eine Maske ueber einer Flaeche in currentColor ist
 * richtig fuer eine einfarbige SVG - und macht aus der Torte des Grafikers
 * einen Farbfleck in Textfarbe.
 */
$png = z_doc(['pasta' => ['src' => '/uploads/designs/torte.png']]);
$pngHtml = DesignSections::html($png, $zeilen, 'de', '2026-01-01');

assert_contains($pngHtml, '<img class="d-ikon d-ikon-pasta" src="/uploads/designs/torte.png"',
    'Zeichen: die eigene Datei kommt als Bild');
assert_true(!str_contains($pngHtml, 'mask-image'), 'Zeichen: und nicht als Maske');
assert_contains(DesignSections::css($png, '.d-p'), 'background-color:transparent;',
    'Zeichen: die Grundregel faerbt sie nicht mehr ein');

/* --- Ein Film, und er gewinnt --- */

$film = z_doc(['pasta' => ['src' => '/uploads/designs/torte.png', 'video' => '/uploads/designs/torte.webm']]);
$filmHtml = DesignSections::html($film, $zeilen, 'de', '2026-01-01');

assert_contains($filmHtml, '<video class="d-ikon d-ikon-pasta" src="/uploads/designs/torte.webm"',
    'Zeichen: der Film wird gedruckt');
assert_contains($filmHtml, 'autoplay muted loop playsinline', 'Zeichen: er laeuft von allein und stumm');
assert_true(!str_contains($filmHtml, 'torte.png'), 'Zeichen: das Bild tritt zurueck');

// Eine fremde Adresse kommt nicht durch - wie bei jedem Bild der Vorlage.
$fremd = z_doc(['pasta' => ['src' => 'https://fremd.example/t.png']]);
assert_true(!str_contains(DesignSections::html($fremd, $zeilen, 'de', '2026-01-01'), 'fremd.example'),
    'Zeichen: eine fremde Adresse faellt weg');

/* --- Die Geometrie: em, damit sie mit der Zeile waechst --- */

/*
 * "Yazinin font boyutunu buyuttugumde gorsel de yaziyla beraber dogru
 * konumda hareket etmeli. Sabit koordinatta kalip tasarim bozulmamali."
 *
 * Genau deshalb em und nicht rem oder px: em misst gegen die Schriftgroesse
 * der Zeile, neben der das Zeichen steht. Waechst die Zeile, waechst der
 * Abstand mit, und die Verschiebung bleibt richtig.
 */
$geo = z_doc(['pasta' => ['src' => '/uploads/designs/t.png',
    'size' => 180, 'x' => 40, 'y' => -25, 'gap' => 60, 'z' => 2]]);
$geoCss = DesignSections::css($geo, '.d-p');

assert_contains($geoCss, '.d-p .d-ikon-pasta{', 'Geometrie: eine Regel je Kennung');
assert_contains($geoCss, 'width:calc(1.15em * 1.8);', 'Geometrie: die Groesse ist ein Faktor auf die Grundgroesse');
assert_contains($geoCss, 'transform:translate(0.4em,-0.25em);', 'Geometrie: X und Y in em');
assert_contains($geoCss, 'margin-inline:0.6em;', 'Geometrie: der Abstand zur Zeile, in beide Richtungen');

/*
 * position:relative gehoert zum z-index. Ohne sie greift er an einem
 * statischen Knoten gar nicht, und der Grafiker drehte an einer Zahl ohne
 * Wirkung - genau die Sorte Feld, die hier schon einmal gefunden wurde
 * (fonts.size, monatelang ohne Wirkung).
 */
assert_contains($geoCss, 'position:relative;z-index:2;', 'Geometrie: die Lage im Stapel wirkt auch');

/* --- Nur echte Kennungen, und nur echte Werte --- */

// Was der Katalog nicht kennt, gibt es hier nicht: sonst gelangte aus einem
// Dokument ein erfundener Schluessel in einen Selektor.
$quatsch = z_doc(['discokugel' => ['src' => '/uploads/designs/d.png']]);
assert_true(!isset($quatsch['icons']['discokugel']), 'Zeichen: eine erfundene Kennung faellt weg');

foreach (array_keys(SectionRegistry::icons()) as $kennung) {
    assert_true(true, 'Katalog: ' . $kennung . ' ist eine gueltige Kennung');
    break;
}

// Grenzen wie ueberall: was danebenliegt, wird gedeckelt statt abgelehnt.
$rand = z_doc(['pasta' => ['src' => '/u.png', 'size' => 99999, 'x' => -9999, 'z' => 99]]);
assert_same(1000, $rand['icons']['pasta']['size'], 'Zeichen: zu gross wird gedeckelt');
assert_same(-400, $rand['icons']['pasta']['x'], 'Zeichen: zu weit links auch');
assert_same(5, $rand['icons']['pasta']['z'], 'Zeichen: und die Stapellage');

/* --- Dieselben Zeichen in Speisekarte und Kleiderordnung --- */

/*
 * Drei Aufrufstellen, ein Bauer. Bis hierher stand dieselbe Maskenzeile
 * dreimal im Code; eine eigene Datei haette an zwei davon gefehlt, und
 * gemerkt haette man es an der Speisekarte.
 */
$drei = DesignSections::complete([
    'id' => 'p', 'slug' => 'p',
    'icons' => ['dresscode' => ['src' => '/uploads/designs/anzug.png']],
    'sections' => [['id' => 'dc', 'type' => 'dresscode']],
]);
$dreiHtml = DesignSections::html($drei, ['sections' => ['dc' => ['code' => 'Black Tie']]], 'de', '2026-01-01');

assert_contains($dreiHtml, 'src="/uploads/designs/anzug.png"',
    'Zeichen: die Kleiderordnung nimmt dieselbe eigene Datei');

/* --- Der Weg aus dem Formular --- */

/*
 * EIN sichtbares Pfadfeld je Kennung und nicht zwei.
 *
 * Das Modell kennt src und video getrennt (der Controller braucht die
 * Unterscheidung fuer seine Pruefung beim Hochladen), aber der Grafiker soll
 * nicht raten muessen, in welches Feld seine Datei gehoert. Wohin der Pfad
 * kommt, entscheidet die Endung.
 */
$vorher = Design::complete(['id' => 'p', 'slug' => 'p']);

$mitFilm = Design::fromPost($vorher, [
    'icons_da' => '1', 'icon_src_pasta' => '/uploads/designs/t.webm', 'icon_size_pasta' => '150',
]);
assert_same('/uploads/designs/t.webm', $mitFilm['icons']['pasta']['video'], 'Formular: eine Endung fuer Film landet im Film');
assert_same('', $mitFilm['icons']['pasta']['src'], 'Formular: und nicht im Bild');
assert_same(150, $mitFilm['icons']['pasta']['size'], 'Formular: die Groesse kommt an');

$mitBild = Design::fromPost($mitFilm, ['icons_da' => '1', 'icon_src_pasta' => '/uploads/designs/t.png']);
assert_same('/uploads/designs/t.png', $mitBild['icons']['pasta']['src'], 'Formular: ein Bild landet im Bild');

/*
 * Und das jeweils andere Feld wird geleert. Bliebe der Film stehen, traege
 * die Vorlage beides, der Film gewaenne (siehe zeichen()), und der Grafiker
 * saehe seine gerade hochgeladene PNG nicht - ohne zu erfahren, warum.
 */
assert_same('', $mitBild['icons']['pasta']['video'], 'Formular: der alte Film tritt zurueck');

// Ohne Marker bleibt alles unberuehrt - dieselbe Vorsicht wie bei den Rollen.
$ohneMarker = Design::fromPost($mitBild, ['name_de' => 'Probe']);
assert_same($mitBild['icons'], $ohneMarker['icons'], 'Formular: ohne Marker keine Aenderung');

/* --- Und das Panel bietet sie an --- */

$tafel = (string) file_get_contents(__DIR__ . '/../templates/admin/design-edit-sections.php');

assert_contains($tafel, 'name="icons_da"', 'Panel: der Marker steht im Formular');
assert_contains($tafel, 'icon_datei_<?= e((string) $kennung) ?>', 'Panel: ein Dateifeld je Kennung');
assert_contains($tafel, 'video/webm', 'Panel: es nimmt auch einen Film');

foreach (['icon_src_', 'icon_size_', 'icon_x_', 'icon_y_', 'icon_gap_', 'icon_z_'] as $feld) {
    assert_contains($tafel, $feld . '<?= e((string) $kennung) ?>', 'Panel: das Feld ' . $feld . ' steht da');
}

/*
 * Alle siebzehn stehen da, auch die ungenutzten: eine Liste, die nur zeigt,
 * was schon belegt ist, beantwortet die Frage nicht, mit der man herkommt -
 * "welche gibt es denn?".
 */
assert_contains($tafel, 'foreach (SectionRegistry::icons() as $kennung', 'Panel: die Liste kommt aus dem Katalog');

$steuer = (string) file_get_contents(__DIR__ . '/../src/Controllers/DesignAdminController.php');
assert_contains($steuer, "Media::storeVideo(\$f, 'designs') ?? Media::storeGraphic(\$f, 'designs')",
    'Panel: erst der Film, dann das Bild - die Datei entscheidet selbst');
