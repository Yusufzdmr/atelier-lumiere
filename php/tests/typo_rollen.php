<?php

declare(strict_types=1);

use Atelier\Design;
use Atelier\DesignSections;

/*
 * Die sechs Textrollen.
 *
 * Bis hierher standen die Groessen der Abschnitte als feste Zahlen im Code:
 * 1.5rem fuer die Ueberschrift, 0.86rem fuer die Anschrift, 3.4rem fuer die
 * grosse Zahl. Wer eine davon aendern wollte, aenderte sie fuer jede Vorlage
 * im Haus. Der Kunde wollte das Gegenteil - "08 cok buyuk olabilir, digerleri
 * daha kucuk ve zarif" ist eine Entscheidung DIESER Vorlage.
 *
 * Die wichtigste Zusage dieser Aenderung steht ganz unten und heisst:
 * ein Dokument ohne eigenen typo-Block sieht aus wie vorher. Auf dem
 * Demoserver liegen verschickte Einladungen; ihr Sockel ist eingefroren, die
 * Regeln aber kommen aus dem Code, und eine Vorlage, die sich beim naechsten
 * Deploy verschiebt, ist ein gebrochenes Versprechen.
 */

$doc = Design::complete([
    'id' => 'probe', 'slug' => 'probe',
    'palette' => ['accent' => ['value' => '#B08D57'], 'fg' => ['value' => '#221C16']],
    'fonts'   => ['display' => ['family' => 'Cormorant'], 'body' => ['family' => 'Jost']],
]);

/* --- Alle sechs sind da, auch wenn das Dokument nichts sagt --- */

assert_same(
    ['title', 'subtitle', 'number', 'body', 'small', 'button'],
    array_keys($doc['typo']),
    'typo: die sechs Rollen des Kunden, in seiner Reihenfolge'
);

foreach (Design::TYPO as $rolle => $stand) {
    foreach (['de', 'en', 'tr'] as $sprache) {
        assert_true(($stand['label'][$sprache] ?? '') !== '',
            'typo: ' . $rolle . ' hat einen Namen auf ' . $sprache);
    }
}

/* --- Die Voreinstellung IST der heutige Stand --- */

/*
 * Jede Zahl hier stand vor dieser Aenderung als feste Angabe in
 * DesignSections. Sie sind einzeln nachgeschlagen und nicht gerundet - eine
 * Ueberschrift, die von 1.5rem auf 1.4rem rutscht, faellt niemandem beim
 * Lesen des Diffs auf und jedem beim Ansehen der Einladung.
 */
$css = Design::css($doc, '.d-probe');

$heute = [
    // .d-sec-title{font-size:1.5rem;font-weight:400;line-height:1.3;
    //              margin-bottom:1.5rem;letter-spacing:0.16em;text-transform:uppercase}
    '--dt-title-size:1.5rem;',
    '--dt-title-weight:400;',
    '--dt-title-track:0.16em;',
    '--dt-title-line:1.3;',
    '--dt-title-caps:uppercase;',
    '--dt-title-below:1.5rem;',
    // .d-sec-venue{font-size:1.7rem;line-height:1.2;margin-bottom:0.4rem}
    '--dt-subtitle-size:1.7rem;',
    '--dt-subtitle-line:1.2;',
    '--dt-subtitle-below:0.4rem;',
    // .d-sec-v-gross .d-sec-days{font-size:3.4rem;line-height:1;margin-bottom:0.6rem}
    '--dt-number-size:3.4rem;',
    '--dt-number-line:1;',
    '--dt-number-below:0.6rem;',
    // .d-sec{line-height:1.7} und .d-sec p{margin-bottom:0.5rem}
    '--dt-body-size:1rem;',
    '--dt-body-line:1.7;',
    '--dt-body-below:0.5rem;',
    // .d-sec-address{font-size:0.86rem}
    '--dt-small-size:0.86rem;',
    // .d-sec-map{font-size:0.72rem;letter-spacing:0.14em;
    //            text-transform:uppercase;margin-top:1.2rem}
    '--dt-button-size:0.72rem;',
    '--dt-button-track:0.14em;',
    '--dt-button-caps:uppercase;',
    '--dt-button-above:1.2rem;',
];

foreach ($heute as $erwartet) {
    assert_contains($css, $erwartet, 'typo: die Voreinstellung ist der heutige Stand — ' . $erwartet);
}

/* --- Verweise und keine zweiten Werte --- */

assert_contains($css, '--dt-title-font:var(--df-display);',
    'typo: die Ueberschrift verweist auf die Schriftmarke, sie kopiert sie nicht');
assert_contains($css, '--dt-title-color:var(--d-accent);',
    'typo: und auf die Farbmarke');

// Erben heisst erben, nicht "wieder die Voreinstellung".
assert_contains($css, '--dt-body-font:inherit;', 'typo: wo die Rolle nichts sagt, wird geerbt');
assert_contains($css, '--dt-body-color:inherit;', 'typo: auch bei der Farbe');

/*
 * Ein Verweis auf eine Marke, die es im Dokument nicht gibt, faellt auf
 * "erben" - nicht auf die Voreinstellung. Sonst haette eine Vorlage ohne die
 * Marke "display" eine Variable, die auf nichts zeigt, und der Browser
 * bekaeme font-family:var(--df-display) ohne Wert.
 */
$ohneDisplay = Design::complete([
    'id' => 'p2', 'slug' => 'p2',
    'palette' => ['fg' => ['value' => '#000000']],
    'fonts'   => ['body' => ['family' => 'Jost']],
]);
assert_same('', $ohneDisplay['typo']['title']['font'],
    'typo: ein Verweis auf eine fehlende Schriftmarke wird zu "erben"');
assert_same('', $ohneDisplay['typo']['title']['color'],
    'typo: dasselbe bei einer fehlenden Farbmarke');
assert_contains(Design::css($ohneDisplay, '.d-p2'), '--dt-title-font:inherit;',
    'typo: und im Stilblock steht dann inherit statt einer leeren Variablen');

/* --- Eigene Werte gewinnen, und werden begrenzt --- */

$eigen = Design::complete([
    'id' => 'p3', 'slug' => 'p3',
    'palette' => ['accent' => ['value' => '#B08D57']],
    'fonts'   => ['display' => ['family' => 'Cormorant']],
    'typo'    => [
        'number' => ['size' => 620, 'caps' => true, 'below' => 20],
        'title'  => ['size' => 99999, 'weight' => 5, 'tracking' => 999, 'lineHeight' => 1],
    ],
]);

assert_same(620, $eigen['typo']['number']['size'], 'typo: der eigene Wert gewinnt');
assert_contains(Design::css($eigen, '.d-p3'), '--dt-number-size:6.2rem;', 'typo: und steht im Stilblock');
assert_same(2000, $eigen['typo']['title']['size'], 'typo: zu gross wird gedeckelt');
assert_same(100, $eigen['typo']['title']['weight'], 'typo: zu leicht wird angehoben');
assert_same(100, $eigen['typo']['title']['tracking'], 'typo: zu weit wird gedeckelt');
assert_same(50, $eigen['typo']['title']['lineHeight'], 'typo: zu eng wird angehoben');

// Was die Rolle nicht sagt, bleibt der heutige Stand - eine halbe Angabe
// loescht die andere Haelfte nicht.
assert_same(400, $eigen['typo']['number']['weight'], 'typo: ungenannte Felder behalten ihren Stand');

/* --- Die Regeln benutzen die Variablen auch --- */

/*
 * Die Variablen allein aendern nichts. Der eigentliche Umbau steht in
 * DesignSections: dort standen die Zahlen, und dort muessen jetzt die
 * Rollen stehen - sonst dreht der Grafiker an einem Knopf, der nirgends
 * ankommt. Genau diese Sorte Feld hat er im Panel schon einmal gefunden
 * (fonts.size, monatelang ohne Wirkung).
 */
$secDoc = DesignSections::complete([
    'id' => 'probe', 'slug' => 'probe',
    'palette' => ['accent' => ['value' => '#B08D57']],
    'fonts'   => ['display' => ['family' => 'Cormorant']],
    'sections' => [
        ['id' => 'ort-1', 'type' => 'location', 'variant' => 'gross'],
        ['id' => 'cd-1', 'type' => 'countdown', 'variant' => 'gross'],
        ['id' => 'cd-2', 'type' => 'countdown', 'variant' => 'uhr'],
        ['id' => 'txt-1', 'type' => 'text'],
    ],
]);
$secCss = DesignSections::css($secDoc, '.d-probe');

$rollen = [
    '.d-sec-title'   => 'title',
    '.d-sec-venue'   => 'subtitle',
    '.d-sec-address' => 'small',
    '.d-sec-map'     => 'button',
];
foreach ($rollen as $wahl => $rolle) {
    // Mit dem Komma gesucht: die Regeln nennen immer einen Ersatzwert
    // (var(--dt-title-size,1.5rem)). Ohne ihn faellt der Stilblock in dem
    // Moment zusammen, in dem jemand eine Variable umbenennt.
    assert_contains($secCss, 'var(--dt-' . $rolle . '-size,',
        'typo: ' . $wahl . ' nimmt seine Groesse aus der Rolle "' . $rolle . '"');
}

assert_contains($secCss, 'var(--dt-number-size,',
    'typo: die grosse Zahl nimmt ihre Groesse aus der Rolle');

/*
 * Keine der umgestellten Regeln darf ihre alte Zahl behalten - eine
 * vergessene waere ein Knopf, der die Haelfte bewegt.
 *
 * Geprueft wird je Regel und nicht mit einer Suche ueber den ganzen Block:
 * es BLEIBEN feste Groessen im Stilblock, und zwar mit Absicht. Die
 * Feldbeschriftungen des Formulars (0.72rem, gesperrt, Versalien) und das
 * Wort unter der Uhrziffer (0.6rem) sind Teile einer Komposition und keine
 * eigenstaendigen Textrollen; der Hinweis auf uns (0.66rem) soll leiser
 * bleiben als alles, woran der Grafiker dreht. Eine pauschale Suche haette
 * genau diese drei zu Fehlern erklaert.
 */
$regeln = [
    '.d-sec-title{'     => ['1.5rem', 'title'],
    '.d-sec-venue{'     => ['1.7rem', 'subtitle'],
    '.d-sec-address{'   => ['0.86rem', 'small'],
    '.d-sec-map{'       => ['0.72rem', 'button'],
    // Mit der Gestalt davor: die Grundregel kennt .d-sec-days auch, dort
    // steht aber nur der Zeilenumbruch - die Groesse gehoert der Gestalt
    // "grosse Zahl".
    '.d-sec-v-gross .d-sec-days{' => ['3.4rem', 'number'],
    '.d-sec-uhr-zahl{'  => ['2.4rem', 'number'],
];

foreach ($regeln as $wahl => [$alt, $rolle]) {
    $von = strpos($secCss, $wahl);
    assert_true($von !== false, 'typo: die Regel fuer ' . $wahl . ' steht im Stilblock');
    $regel = substr($secCss, (int) $von, (int) strpos($secCss, '}', (int) $von) - (int) $von);

    assert_true(!str_contains($regel, 'font-size:' . $alt),
        'typo: ' . $wahl . ' traegt nicht mehr die feste Groesse ' . $alt);
    assert_contains($regel, '--dt-' . $rolle . '-',
        'typo: ' . $wahl . ' haengt an der Rolle "' . $rolle . '"');
}

/*
 * Und die Uhr bewegt sich mit. Vier Zahlen nebeneinander muessen kleiner
 * sein als eine allein - sonst passen sie auf keinem Telefon in eine Zeile.
 * Das Verhaeltnis (2.4 von 3.4) ist der heutige Stand und bleibt an der
 * Variante haengen, nicht an einer zweiten Rolle: "gross" und "uhr" sind
 * dieselbe Angabe in zwei Anordnungen, nicht zwei Angaben.
 */
assert_contains($secCss, 'calc(var(--dt-number-size,',
    'typo: die Uhr rechnet aus derselben Rolle, statt eine eigene Zahl zu fuehren');

/* --- Der Weg aus dem Formular --- */

/*
 * Hinter einem Marker, und das ist kein Zierrat.
 *
 * "Versalien" ist ein Haken, und ein abgeraeumter Haken sieht in einem POST
 * genauso aus wie ein Feld, das gar nicht mitgeschickt wurde. Ohne den
 * Marker loeschte jeder Aufruf mit einem Teilformular alle sechs
 * Versalien-Einstellungen - und so einen Aufruf gibt es (design_admin.php
 * schickt Geometriefelder allein und erwartet ein stillstehendes Dokument).
 */
$vorher = Design::complete([
    'id' => 'p4', 'slug' => 'p4',
    'palette' => ['accent' => ['value' => '#B08D57']],
    'fonts'   => ['display' => ['family' => 'Cormorant']],
]);

$ohneMarker = Design::fromPost($vorher, ['name_de' => 'Probe']);
assert_same($vorher['typo'], $ohneMarker['typo'],
    'fromPost: ohne Marker bleiben die Rollen unberuehrt');

$mit = Design::fromPost($vorher, [
    'typo_da'            => '1',
    'typo_title_size'    => '260',
    'typo_title_weight'  => '700',
    'typo_title_font'    => 'display',
    'typo_title_color'   => 'accent',
    'typo_title_below'   => '80',
    'typo_number_size'   => '900',
    // typo_title_caps fehlt: der Haken ist abgeraeumt.
]);

assert_same(260, $mit['typo']['title']['size'], 'fromPost: die Groesse kommt an');
assert_same(700, $mit['typo']['title']['weight'], 'fromPost: das Gewicht auch');
assert_same(80, $mit['typo']['title']['below'], 'fromPost: und die Luft darunter');
assert_same(900, $mit['typo']['number']['size'], 'fromPost: jede Rolle fuer sich');
assert_same(false, $mit['typo']['title']['caps'],
    'fromPost: ein fehlender Haken ist ein abgeraeumter Haken');

// "Erben" ist eine Wahl und kein fehlender Wert.
$erbt = Design::fromPost($vorher, ['typo_da' => '1', 'typo_title_font' => '']);
assert_same('', $erbt['typo']['title']['font'], 'fromPost: leer heisst erben');

/* --- Und beide Seiten rechnen gleich --- */

/*
 * Die lebende Vorschau im Panel schreibt dieselben Variablen wie
 * Design::css(), nur im Browser. Zwei Rechnungen fuer dieselbe Zahl laufen
 * frueher oder spaeter auseinander - und zwar unsichtbar: das Panel zeigt
 * dann etwas anderes als die fertige Einladung, und gemerkt wird es erst
 * nach dem Speichern.
 *
 * Geprueft wird der Text des Skripts. Ein echter Vergleich braeuchte einen
 * Browser; was hier haelt, ist die Zusage, dass beide Seiten dieselben
 * Nenner benutzen und dieselben Variablennamen schreiben.
 */
$editor = (string) file_get_contents(__DIR__ . '/../public/assets/design-editor.js');

assert_contains($editor, 'data-typo', 'Editor: er findet die Felder der Rollen');
assert_contains($editor, '"--dt-" + rolle', 'Editor: und schreibt die Variablen der Rollen');

foreach (['(zahl / 100) + "rem"', '(zahl / 100) + "em"', 'String(zahl / 100)'] as $rechnung) {
    assert_contains($editor, $rechnung, 'Editor: dieselben Nenner wie Design::css — ' . $rechnung);
}

// Der Name weicht an genau einer Stelle ab, und zwar bewusst: das Formular
// sagt "tracking", die Variable heisst "-track".
assert_contains($editor, 'feld === "tracking" ? "track"', 'Editor: die eine Umbenennung steht an einer Stelle');
assert_contains($css, '--dt-title-track:', 'typo: und der Server schreibt denselben Namen');

// Erben muss auch in der Vorschau ankommen - eine stehengebliebene
// Variable waere ein Umschalten ohne Wirkung.
assert_contains($editor, '"inherit"', 'Editor: leer schreibt inherit, statt die alte Marke stehenzulassen');

/* --- Das Panel bietet sie an --- */

$tafel = (string) file_get_contents(__DIR__ . '/../templates/admin/design-edit-sections.php');

assert_contains($tafel, 'name="typo_da"', 'Panel: der Marker steht im Formular');
foreach (array_keys(Design::TYPO) as $rolle) {
    assert_contains($tafel, 'typo_<?= e((string) $rolle) ?>_size',
        'Panel: die Groesse jeder Rolle ist ein Feld');
    break;
}
foreach (['_font', '_color', '_size', '_weight', '_tracking', '_line', '_above', '_below', '_caps'] as $feld) {
    assert_contains($tafel, 'typo_<?= e((string) $rolle) ?>' . $feld,
        'Panel: das Feld ' . $feld . ' steht in der Tafel');
}
