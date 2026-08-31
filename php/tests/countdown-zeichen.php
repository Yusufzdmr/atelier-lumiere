<?php

declare(strict_types=1);

use Atelier\Design;
use Atelier\DesignSections;

/*
 * Freie Zeichen am Countdown - je Gestalt.
 *
 * Die Katalogzeichen daneben (tests/zeichen.php) haengen an einer KENNUNG:
 * das Paar waehlt "pasta", die Vorlage sagt, was eine Torte ist. Am Countdown
 * gibt es keine Kennung und keine feste Zahl - dort haengt der Grafiker so
 * viele Bilder oder Filme an die Zahlen, wie er mag.
 *
 * Und getrennt je GESTALT, denn die vier zeigen nicht dasselbe: die Uhr hat
 * Sekunden, die ruhige Zahl nicht. Ein Zeichen an "Sekunden" waere dort ein
 * Verweis auf nichts.
 */

function cd_doc(array $icons, string $variante = 'uhr'): array
{
    return DesignSections::complete([
        'id' => 'probe', 'slug' => 'probe',
        'countdownIcons' => $icons,
        'sections' => [['id' => 'zeit', 'type' => 'countdown', 'variant' => $variante]],
    ]);
}

$daten = ['date' => '2099-06-20', 'time' => '15:00'];

/* --- Ohne Angabe aendert sich nichts --- */

$ohne = cd_doc([]);
$ohneHtml = DesignSections::html($ohne, $daten, 'de', '2026-01-01');

assert_same([], $ohne['countdownIcons'], 'Countdown: eine Vorlage ohne Angabe traegt keine Liste mit');
assert_true(!str_contains($ohneHtml, 'd-cd-el'), 'Countdown: und kein Knoten im Markup');
assert_true(!str_contains(DesignSections::css($ohne, '.d-p'), '.d-cd-el{'),
    'Countdown: und keine tote Regel im Stilblock');

/* --- Ein Bild an den Tagen --- */

$eins = cd_doc(['uhr' => [['src' => '/uploads/designs/herz.png', 'anchor' => 'days']]]);
$einsHtml = DesignSections::html($eins, $daten, 'de', '2026-01-01');

assert_contains($einsHtml, '<img class="d-cd-el"', 'Countdown: das Bild steht im Markup');
assert_contains($einsHtml, 'src="/uploads/designs/herz.png"', 'Countdown: mit seiner Adresse');
assert_contains(DesignSections::css($eins, '.d-p'), '.d-cd-el{display:inline-block;',
    'Countdown: die Grundregel steht da, sobald es eines gibt');

/*
 * display:inline-block ist kein Geschmack: Tailwinds Preflight setzt img und
 * video auf display:block, und ein Zeichen NEBEN einer Zahl waere dann eine
 * Zeile darunter.
 */
assert_contains(DesignSections::css($eins, '.d-p'), 'max-width:none;',
    'Countdown: und der Deckel von Preflight ist aufgehoben');

/* --- Die Seite entscheidet, wo es steht --- */

$vorher = cd_doc(['uhr' => [['src' => '/uploads/designs/a.png', 'anchor' => 'days', 'side' => 'vor']]]);
$vorherHtml = DesignSections::html($vorher, $daten, 'de', '2026-01-01');

$posEl = strpos($vorherHtml, 'd-cd-el');
$posFeld = strpos($vorherHtml, 'd-sec-uhr-feld');
assert_true($posEl !== false && $posFeld !== false && $posEl < $posFeld,
    'Countdown: "davor" steht vor dem Feld');

$danach = cd_doc(['uhr' => [['src' => '/uploads/designs/a.png', 'anchor' => 'days', 'side' => 'nach']]]);
$danachHtml = DesignSections::html($danach, $daten, 'de', '2026-01-01');
assert_true(strpos($danachHtml, 'd-cd-el') > strpos($danachHtml, 'd-sec-uhr-feld'),
    'Countdown: "dahinter" dahinter');

/* --- Je Gestalt eigene, und nur die der gezeigten --- */

/*
 * Der Kern der Sache: "countdown gorunumu bazinda ayri saklama". Wer die
 * Gestalt wechselt, wechselt den Schmuck mit - und findet ihn beim
 * Zurueckwechseln unveraendert wieder. Eine gemeinsame Liste haette bei
 * jedem Wechsel halb gepasst.
 */
$zwei = ['uhr'  => [['src' => '/uploads/designs/uhr.png', 'anchor' => 'seconds']],
         'tage' => [['src' => '/uploads/designs/tage.png', 'anchor' => 'days']]];

$alsUhr  = DesignSections::html(cd_doc($zwei, 'uhr'), $daten, 'de', '2026-01-01');
$alsTage = DesignSections::html(cd_doc($zwei, 'tage'), $daten, 'de', '2026-01-01');

assert_contains($alsUhr, 'uhr.png', 'Gestalt: die Uhr zeigt die ihren');
assert_true(!str_contains($alsUhr, 'tage.png'), 'Gestalt: und nicht die der anderen');
assert_contains($alsTage, 'tage.png', 'Gestalt: die Tageszahl zeigt die ihren');
assert_true(!str_contains($alsTage, 'uhr.png'), 'Gestalt: und nicht die der Uhr');

// Beide bleiben im Dokument stehen - gezeigt wird nur, was zur Gestalt passt.
assert_true(isset(cd_doc($zwei, 'tage')['countdownIcons']['uhr']),
    'Gestalt: die andere Liste bleibt gespeichert');

/* --- Ein Anker, den es in dieser Gestalt nicht gibt --- */

/*
 * "days" steht in allen vier Gestalten, und darauf faellt ein unbekannter
 * Anker zurueck. Die Zeile wegzuwerfen hiesse, eine hochgeladene Datei
 * stillschweigend zu loeschen.
 */
$falsch = cd_doc(['default' => [['src' => '/uploads/designs/a.png', 'anchor' => 'seconds']]], 'default');
assert_same('days', $falsch['countdownIcons']['default'][0]['anchor'],
    'Anker: was es in dieser Gestalt nicht gibt, faellt auf die Tage zurueck');
assert_contains(DesignSections::html($falsch, $daten, 'de', '2026-01-01'), 'd-cd-el',
    'Anker: und die Datei bleibt sichtbar');

/* --- Der Film gewinnt, wie ueberall im Haus --- */

$film = cd_doc(['uhr' => [['src' => '/uploads/designs/a.png', 'video' => '/uploads/designs/a.webm', 'anchor' => 'days']]]);
$filmHtml = DesignSections::html($film, $daten, 'de', '2026-01-01');

assert_contains($filmHtml, '<video class="d-cd-el"', 'Film: er wird gedruckt');
assert_contains($filmHtml, 'autoplay muted loop playsinline', 'Film: er laeuft von allein und stumm');
assert_true(!str_contains($filmHtml, '/uploads/designs/a.png'), 'Film: das Bild tritt zurueck');

/* --- Ohne Datei keine Zeile --- */

/*
 * Der Unterschied zu den Katalogzeichen: dort gibt es die gezeichnete
 * Fassung des Hauses, an der eine blosse Geometrie noch etwas aendern kann.
 * Hier gibt es ohne Datei nichts zu zeigen - und das ist zugleich der Weg
 * zum Loeschen.
 */
$leer = cd_doc(['uhr' => [['src' => '', 'video' => '', 'size' => 300]]]);
assert_same([], $leer['countdownIcons'], 'Ohne Datei: keine Zeile');

// Eine fremde Adresse kommt nicht durch - wie bei jedem Bild der Vorlage.
$fremd = cd_doc(['uhr' => [['src' => 'https://fremd.example/a.png']]]);
assert_same([], $fremd['countdownIcons'], 'Fremde Adresse: faellt weg');

/* --- Die Geometrie steht am Knoten, in em --- */

/*
 * Am Knoten und nicht im Stilblock, und das ist der Unterschied zu den
 * Katalogzeichen: dort sagt die EINLADUNG, welche Zeichen vorkommen, also
 * muss die Vorlage sie von weitem ansprechen koennen. Hier ist der Knoten
 * selbst die Aussage der Vorlage.
 *
 * em und nicht px: "yazinin font boyutunu buyuttugumde gorsel de yaziyla
 * beraber dogru konumda hareket etmeli."
 */
$geo = cd_doc(['uhr' => [['src' => '/uploads/designs/a.png', 'anchor' => 'days',
    'size' => 180, 'x' => 40, 'y' => -25, 'gap' => 60, 'z' => 2]]]);
$geoHtml = DesignSections::html($geo, $daten, 'de', '2026-01-01');

assert_contains($geoHtml, 'width:1.8em;', 'Geometrie: die Groesse in em');
assert_contains($geoHtml, 'transform:translate(0.4em,-0.25em);', 'Geometrie: X und Y in em');
assert_contains($geoHtml, 'margin-inline:0.6em;', 'Geometrie: der Abstand, in beide Richtungen');
assert_contains($geoHtml, 'position:relative;z-index:2;', 'Geometrie: die Lage im Stapel wirkt auch');

// Grenzen wie ueberall: was danebenliegt, wird gedeckelt statt abgelehnt.
$rand = cd_doc(['uhr' => [['src' => '/uploads/designs/a.png', 'size' => 99999, 'x' => -9999, 'z' => 99]]]);
assert_same(2000, $rand['countdownIcons']['uhr'][0]['size'], 'Grenzen: zu gross wird gedeckelt');
assert_same(-400, $rand['countdownIcons']['uhr'][0]['x'], 'Grenzen: zu weit links auch');
assert_same(5, $rand['countdownIcons']['uhr'][0]['z'], 'Grenzen: und die Stapellage');

/* --- Der Weg aus dem Formular --- */

$vorherDoc = Design::complete(['id' => 'p', 'slug' => 'p']);

$ausForm = Design::fromPost($vorherDoc, [
    'cdicons_da' => '1',
    'cd_n_uhr' => '2',
    'cd_uhr_0_src' => '/uploads/designs/a.png', 'cd_uhr_0_anchor' => 'days',
    'cd_uhr_0_side' => 'vor', 'cd_uhr_0_size' => '150',
    // Die zweite Zeile ist leer - so sieht die immer mitgeschickte
    // Anfangszeile aus, und sie darf nichts anlegen.
    'cd_uhr_1_src' => '',
]);

assert_same(1, count($ausForm['countdownIcons']['uhr']), 'Formular: die leere Zeile legt nichts an');
assert_same('/uploads/designs/a.png', $ausForm['countdownIcons']['uhr'][0]['src'], 'Formular: der Pfad kommt an');
assert_same('vor', $ausForm['countdownIcons']['uhr'][0]['side'], 'Formular: und die Seite');
assert_same(150, $ausForm['countdownIcons']['uhr'][0]['size'], 'Formular: und die Groesse');

// Eine Endung fuer Film landet im Film - dieselbe Regel wie bei den Zeichen.
$filmForm = Design::fromPost($vorherDoc, [
    'cdicons_da' => '1', 'cd_n_tage' => '1',
    'cd_tage_0_src' => '/uploads/designs/a.webm',
]);
assert_same('/uploads/designs/a.webm', $filmForm['countdownIcons']['tage'][0]['video'],
    'Formular: eine Endung fuer Film landet im Film');
assert_same('', $filmForm['countdownIcons']['tage'][0]['src'], 'Formular: und nicht im Bild');

/*
 * Geloescht wird durch Leeren des Pfads. Die Liste wird als Ganzes neu
 * gebaut - was nicht mitkommt, ist weg.
 */
$geloescht = Design::fromPost($ausForm, ['cdicons_da' => '1', 'cd_n_uhr' => '1', 'cd_uhr_0_src' => '']);
assert_same([], $geloescht['countdownIcons'], 'Formular: leerer Pfad loescht die Zeile');

// Ohne Marker bleibt alles unberuehrt - dieselbe Vorsicht wie bei den Rollen.
$ohneMarker = Design::fromPost($ausForm, ['name_de' => 'Probe']);
assert_same($ausForm['countdownIcons'], $ohneMarker['countdownIcons'],
    'Formular: ohne Marker keine Aenderung');

// Eine fremde Zahl steuert keine Schleife: gedeckelt, bevor sie es tut.
$viele = Design::fromPost($vorherDoc, ['cdicons_da' => '1', 'cd_n_uhr' => '999999']);
assert_same([], $viele['countdownIcons'], 'Formular: eine wilde Zahl legt nichts an');

/* --- Und das Panel bietet sie an --- */

$tafel = (string) file_get_contents(__DIR__ . '/../templates/admin/design-edit-sections.php');

assert_contains($tafel, 'name="cdicons_da"', 'Panel: der Marker steht im Formular');
assert_contains($tafel, 'foreach (Design::COUNTDOWN_ANKER as $gestalt', 'Panel: eine Tafel je Gestalt');
assert_contains($tafel, 'cd_datei_<?= e((string) $gestalt) ?>_<?= $i ?>', 'Panel: ein Dateifeld je Zeile');
assert_contains($tafel, 'name="cd_n_<?= e((string) $gestalt) ?>"', 'Panel: und die Zahl der Zeilen');
assert_contains($tafel, 'data-cd-mehr', 'Panel: der Knopf fuer eine Zeile mehr');

$steuer = (string) file_get_contents(__DIR__ . '/../src/Controllers/DesignAdminController.php');
assert_contains($steuer, "\$_FILES['cd_datei_' . \$gestalt . '_' . \$i]",
    'Panel: der Controller nimmt die Datei entgegen');

$skript = (string) file_get_contents(__DIR__ . '/../public/assets/design-editor.js');
assert_contains($skript, 'data-cd-mehr', 'Skript: der Knopf haengt am Formular');
assert_contains($skript, 'if (nummer >= 24) return;', 'Skript: und legt nichts an, was beim Speichern wegfiele');

/* --- Und das Ziehen im Editor --- */

/*
 * Vier Zahlenfelder sind die richtige Antwort auf "genau minus fuenf
 * Hundertstel" und die falsche auf "ein bisschen weiter nach links".
 *
 * Damit sich ein Zeichen ziehen laesst, muss der Editor wissen, welche Zeile
 * des Formulars zu dem Knoten gehoert. Bei den Katalogzeichen steht das in
 * der Klasse (d-ikon-pasta); hier gibt es keine Kennung, also traegt der
 * Knoten die Gestalt und die Nummer.
 */
$gezogen = cd_doc(['uhr' => [
    ['src' => '/uploads/designs/a.png', 'anchor' => 'days'],
    ['src' => '/uploads/designs/b.png', 'anchor' => 'hours'],
]]);
$gezogenHtml = DesignSections::html($gezogen, $daten, 'de', '2026-01-01');

assert_contains($gezogenHtml, 'data-cd="uhr:0"', 'Ziehen: die erste Zeile traegt ihre Nummer');
assert_contains($gezogenHtml, 'data-cd="uhr:1"', 'Ziehen: die zweite auch');

$skript2 = (string) file_get_contents(__DIR__ . '/../public/assets/design-editor.js');

assert_contains($skript2, '".d-cd-el, .d-ikon"', 'Ziehen: beide Sorten Zeichen haben denselben Griff');
assert_contains($skript2, "'[name=\"' + name + 'x\"]'", 'Ziehen: geschrieben wird ins Feld');
assert_contains($skript2, "'[name=\"icon_x_' + kennung + '\"]'", 'Ziehen: und bei den Katalogzeichen ins ihre');

/*
 * Die Umrechnung nimmt die Schriftgroesse des Knotens: an ihr misst der
 * Browser das em, das er hineinschreibt. Und den Massstab des Rahmens, sonst
 * liefe das Zeichen dort schneller als die Maus.
 */
assert_contains($skript2, 'window.getComputedStyle(el).fontSize', 'Ziehen: die Umrechnung misst am Knoten');
assert_contains($skript2, 'rahmenEl.getBoundingClientRect().width / rahmenEl.offsetWidth',
    'Ziehen: und kuerzt den Massstab des Rahmens heraus - am Rahmen gemessen');
