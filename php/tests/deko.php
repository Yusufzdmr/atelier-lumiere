<?php

declare(strict_types=1);

use Atelier\Design;
use Atelier\DesignSections;

/*
 * Freier Schmuck an einem Abschnitt.
 *
 * "Gerektiginde ayni bolume birden fazla gorsel veya video elementi
 * ekleyebilmeliyim. Her eklenen element icin ayri ayri boyut, pozisyon,
 * yaziya gore konum, gerekirse layer/z-index ayarlanabilmeli."
 *
 * Am Countdown gab es das seit heute; hier ist dasselbe fuer JEDEN
 * Abschnitt. Am ABSCHNITT und nicht an der Art: zwei Textbloecke derselben
 * Vorlage duerfen verschieden geschmueckt sein.
 */

function d_doc(array $deko): array
{
    return DesignSections::complete([
        'id' => 'p', 'slug' => 'p',
        'sections' => [['id' => 't-1', 'type' => 'text', 'title' => ['de' => 'Gut zu wissen'], 'deko' => $deko]],
    ]);
}

$daten = ['sections' => ['t-1' => ['text' => 'Ein Satz.']]];

/* --- Mehrere Elemente, jedes mit eigener Geometrie --- */

$doc = d_doc([
    ['src' => '/uploads/designs/blume.png', 'anchor' => 'titel', 'side' => 'vor', 'size' => 250, 'x' => 40],
    ['video' => '/uploads/designs/rauch.webm', 'anchor' => 'inhalt', 'side' => 'nach', 'z' => 2],
]);
$html = DesignSections::html($doc, $daten, 'de', '2026-01-01');

assert_same(2, count($doc['sections'][0]['deko']), 'Schmuck: zwei Elemente an einem Abschnitt');
assert_contains($html, '<img class="d-deko"', 'Schmuck: das Bild steht im Markup');
assert_contains($html, '<video class="d-deko"', 'Schmuck: der Film auch');
assert_contains($html, 'width:2.5em;', 'Schmuck: mit eigener Groesse');
assert_contains($html, 'transform:translate(0.4em,0em);', 'Schmuck: und eigener Verschiebung');
assert_contains($html, 'position:relative;z-index:2;', 'Schmuck: und eigener Ebene');

// Transparente Filme wie ueberall: die WebM wird nicht umkodiert.
assert_contains($html, 'autoplay muted loop playsinline', 'Schmuck: der Film laeuft von allein und stumm');

/* --- Die Seite entscheidet, wo es steht --- */

$vorTitel = strpos($html, 'd-deko');
$titel = strpos($html, 'd-sec-title');
assert_true($vorTitel !== false && $titel !== false && $vorTitel < $titel,
    'Schmuck: "davor" steht vor der Ueberschrift');

/* --- Ohne Datei keine Zeile, und fremde Adressen fallen weg --- */

assert_same([], d_doc([['src' => '', 'size' => 300]])['sections'][0]['deko'], 'Ohne Datei: keine Zeile');
assert_same([], d_doc([['src' => 'https://fremd.example/a.png']])['sections'][0]['deko'],
    'Fremde Adresse: faellt weg');

// Ein Anker, den es nicht gibt, faellt auf den ersten zurueck statt die
// hochgeladene Datei stillschweigend zu loeschen.
$falsch = d_doc([['src' => '/uploads/designs/a.png', 'anchor' => 'quatsch']]);
assert_same('titel', $falsch['sections'][0]['deko'][0]['anchor'], 'Anker: unbekannt faellt auf die Ueberschrift');

// Und die Grundregel steht im Stilblock, sobald es Schmuck gibt.
assert_contains(DesignSections::css($doc, '.d-p'), '.d-deko{display:inline-block;',
    'Schmuck: die Grundregel steht da');

/* --- Der Weg aus dem Formular --- */

$vorher = Design::complete(['id' => 'p', 'slug' => 'p',
    'sections' => [['id' => 't-1', 'type' => 'text']]]);

$ausForm = Design::fromPost($vorher, [
    'sections_da' => '1', 'sec_reihe' => '0',
    'sec_id_0' => 't-1', 'sec_type_0' => 'text', 'sec_variant_0' => 'default', 'sec_on_0' => '1',
    'sec_deko_n_0' => '2',
    'sec_deko_0_0_src' => '/uploads/designs/blume.png', 'sec_deko_0_0_anchor' => 'inhalt',
    'sec_deko_0_0_size' => '180',
    // Die immer mitgeschickte leere Anfangszeile darf nichts anlegen.
    'sec_deko_0_1_src' => '',
]);

assert_same(1, count($ausForm['sections'][0]['deko']), 'Formular: die leere Zeile legt nichts an');
assert_same(180, $ausForm['sections'][0]['deko'][0]['size'], 'Formular: die Groesse kommt an');
assert_same('inhalt', $ausForm['sections'][0]['deko'][0]['anchor'], 'Formular: und der Anker');

// Eine Endung fuer Film landet im Film - dieselbe Regel wie ueberall.
$film = Design::fromPost($vorher, ['sections_da' => '1', 'sec_reihe' => '0',
    'sec_id_0' => 't-1', 'sec_type_0' => 'text', 'sec_on_0' => '1',
    'sec_deko_n_0' => '1', 'sec_deko_0_0_src' => '/uploads/designs/a.webm']);
assert_same('/uploads/designs/a.webm', $film['sections'][0]['deko'][0]['video'], 'Formular: der Film landet im Film');

/*
 * Ohne die Zahl bleibt der Schmuck stehen. Sonst raeumte jede Speicherung
 * aus einer anderen Schublade ihn weg - dieselbe Vorsicht wie bei den Rollen
 * und den Zeichen.
 */
$ohne = Design::fromPost($ausForm, ['sections_da' => '1', 'sec_reihe' => '0',
    'sec_id_0' => 't-1', 'sec_type_0' => 'text', 'sec_on_0' => '1']);
assert_same(1, count($ohne['sections'][0]['deko']), 'Formular: ohne die Zahl bleibt er stehen');

// Und leerer Pfad loescht - der einzige Weg, und derselbe wie am Countdown.
$weg = Design::fromPost($ausForm, ['sections_da' => '1', 'sec_reihe' => '0',
    'sec_id_0' => 't-1', 'sec_type_0' => 'text', 'sec_on_0' => '1',
    'sec_deko_n_0' => '1', 'sec_deko_0_0_src' => '']);
assert_same([], $weg['sections'][0]['deko'], 'Formular: leerer Pfad loescht');

/* --- Panel und Steuerung --- */

$tafel = (string) file_get_contents(__DIR__ . '/../templates/admin/design-edit-tafeln.php');
assert_contains($tafel, 'name="sec_deko_n_<?= $i ?>"', 'Panel: die Zahl der Zeilen steht im Formular');
assert_contains($tafel, 'sec_dekodatei_<?= $i ?>_<?= $d ?>', 'Panel: ein Dateifeld je Zeile');
assert_contains($tafel, 'data-cd-mehr="deko_<?= $i ?>"', 'Panel: und der Knopf fuer eine Zeile mehr');

$steuer = (string) file_get_contents(__DIR__ . '/../src/Controllers/DesignAdminController.php');
assert_contains($steuer, "\$_FILES['sec_dekodatei_' . \$i . '_' . \$d]",
    'Panel: der Controller nimmt die Datei entgegen');

/*
 * Der Knopf "+" numeriert die LETZTE Zahl im Namen um.
 *
 * Beim Countdown steht nur eine darin (cd_uhr_0_src), beim Schmuck eines
 * Abschnitts stehen zwei (sec_deko_0_1_src: erst der Abschnitt, dann die
 * Zeile). Die erste zu nehmen hiesse dort, den Abschnitt umzunummerieren und
 * die neue Zeile einem fremden anzuhaengen.
 */
$skript = (string) file_get_contents(__DIR__ . '/../public/assets/design-editor.js');
assert_contains($skript, 'replace(/^(.*)_\d+(?=_|$)/, "$1_" + nummer)',
    'Knopf: er trifft die letzte Zahl im Namen');
