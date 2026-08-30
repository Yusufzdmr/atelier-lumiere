<?php

declare(strict_types=1);

use Atelier\DesignSections;
use Atelier\SectionRegistry;

/*
 * Rahmen um die Abschnitte.
 *
 * "Metinler sadece alt alta gosterilmemeli … cerceve icerisinde, kart
 * seklinde." Bis hierher stand jeder Abschnitt nackt auf dem Blatt, und wer
 * eine Angabe hervorheben wollte, konnte nur ihre Schrift aendern.
 *
 * Der Rahmen ist eine Einstellung wie Ausrichtung und Luft: er gehoert JEDER
 * Art. Ein Rahmen, den nur der Ort kennt, waere ein Gestaltungsmittel mit
 * einer Ausnahmeliste.
 */

function rahmen_doc(array $sections): array
{
    return DesignSections::complete([
        'id' => 'probe', 'slug' => 'probe',
        'palette' => ['accent' => ['value' => '#B08D57'], 'paper' => ['value' => '#FAF7F2']],
        'fonts'   => ['display' => ['family' => 'Cormorant']],
        'sections' => $sections,
    ]);
}

/* --- Der Katalog kennt ihn, und zwar bei jeder Art --- */

$gemein = SectionRegistry::commonSettings();
assert_true(isset($gemein['frame']), 'Rahmen: er steht bei den gemeinsamen Einstellungen');
assert_true(isset($gemein['frameSrc']), 'Rahmen: und die eigene Zeichnung daneben');
assert_same('keine', $gemein['frame']['default'], 'Rahmen: voreingestellt gibt es keinen');

foreach (DesignSections::TYPES as $art) {
    assert_true(isset(SectionRegistry::settings($art)['frame']),
        'Rahmen: ' . $art . ' kann einen tragen');
}

/*
 * Sieben Wahlmoeglichkeiten - und "floral" ist bewusst keine davon. Ein
 * Blumenrahmen aus CSS-Strichen waere ein Versprechen, das keine Zeichnung
 * einloest; wer eine Ranke will, nimmt "eigen" und seine eigene PNG. Genau
 * danach war auch gefragt.
 */
assert_same(
    ['keine', 'linie', 'doppel', 'gold', 'papier', 'transparent', 'eigen'],
    $gemein['frame']['options'],
    'Rahmen: die Liste, die der Grafiker sieht'
);
assert_true(!in_array('floral', $gemein['frame']['options'], true),
    'Rahmen: kein "floral" ohne Zeichnung dahinter');

/* --- Ohne Rahmen aendert sich nichts --- */

$ohne = rahmen_doc([['id' => 'ort-1', 'type' => 'location']]);
$ohneHtml = DesignSections::html($ohne, ['venue' => 'Villa Sonnenhof', 'slug' => 'p'], 'de');

assert_true(!str_contains($ohneHtml, 'd-sec-r-'), 'Rahmen: ohne Wahl keine Rahmenklasse');
assert_true(!str_contains(DesignSections::css($ohne, '.d-p'), 'd-sec-r-'),
    'Rahmen: und kein toter Stilblock');

/*
 * Der Kasten um Ueberschrift und Inhalt steht trotzdem IMMER da. Ein Kasten,
 * den es nur manchmal gibt, waere ein zweiter Bauplan - jede Regel, die ihn
 * erwaehnt, muesste beide Faelle kennen.
 */
assert_contains($ohneHtml, '<div class="d-sec-inner">', 'Rahmen: der Kasten steht immer da');

/* --- Mit Rahmen: Klasse am Abschnitt, Stil am Kasten --- */

$mit = rahmen_doc([
    ['id' => 'ort-1', 'type' => 'location', 'settings' => ['frame' => 'gold']],
    ['id' => 'txt-1', 'type' => 'text', 'settings' => ['frame' => 'papier']],
    // Zweimal derselbe Rahmen: der Block soll einmal im Stilblock stehen.
    ['id' => 'txt-2', 'type' => 'text', 'settings' => ['frame' => 'papier']],
]);
$mitHtml = DesignSections::html($mit, [
    'venue' => 'Villa Sonnenhof', 'slug' => 'p',
    'sections' => ['txt-1' => ['text' => 'Eins'], 'txt-2' => ['text' => 'Zwei']],
], 'de');
$mitCss = DesignSections::css($mit, '.d-p');

assert_contains($mitHtml, 'd-sec-r-gold', 'Rahmen: die Klasse steht am Abschnitt');
assert_contains($mitHtml, 'd-sec-r-papier', 'Rahmen: und die des zweiten auch');

/*
 * Gezeichnet wird auf .d-sec-inner und nicht auf dem Abschnitt. Das muss so
 * sein: das Polster oben ist 56 % der Breite, damit der Titel zwischen die
 * Goldlinien des Blattes faellt - ein Rahmen um den ganzen Abschnitt begaenne
 * einen halben Bildschirm ueber der ersten Zeile.
 */
assert_contains($mitCss, '.d-p .d-sec-r-gold .d-sec-inner{',
    'Rahmen: er sitzt am inneren Kasten, nicht am Abschnitt');

assert_same(1, substr_count($mitCss, '.d-p .d-sec-r-papier .d-sec-inner{'),
    'Rahmen: zwei Abschnitte mit demselben Rahmen ergeben einen Block');

/* --- Jeder Rahmen bringt sein eigenes Polster mit --- */

/*
 * Ohne Polster klebte der Text an der Linie. Die Zahlen sind verschieden -
 * eine Haarlinie braucht weniger Luft als ein Papierkasten, und eine
 * gezeichnete Ranke am meisten, ihre Zierde sitzt ja im Rand.
 */
foreach (['linie', 'doppel', 'gold', 'papier', 'transparent', 'eigen'] as $art) {
    $doc = rahmen_doc([['id' => 't', 'type' => 'text', 'settings' => ['frame' => $art]]]);
    $css = DesignSections::css($doc, '.d-p');

    assert_contains($css, '.d-sec-r-' . $art . ' .d-sec-inner{', 'Rahmen: ' . $art . ' bringt einen Block mit');
    assert_contains($css, 'padding:', 'Rahmen: ' . $art . ' bringt Luft mit');
}

/* --- Die eigene Zeichnung --- */

$eigen = rahmen_doc([[
    'id' => 't', 'type' => 'text',
    'settings' => ['frame' => 'eigen', 'frameSrc' => '/uploads/designs/ranke.png'],
]]);
$eigenCss = DesignSections::css($eigen, '.d-p');

// Am ABSCHNITT und nicht in der Rahmenregel: die Regel gilt fuer alle
// Abschnitte mit diesem Rahmen, die Zeichnung gehoert einem.
assert_contains($eigenCss, ".d-p .d-sec-t{--d-sec-frame:url('/uploads/designs/ranke.png');}",
    'Rahmen: die eigene Zeichnung haengt am Abschnitt');
assert_contains($eigenCss, 'background-image:var(--d-sec-frame,none);',
    'Rahmen: und die Regel liest sie aus der Variablen');

/*
 * Eine fremde Adresse kommt nicht durch. Der Pfad geht durch safeSrc, wie
 * jedes andere Bild der Vorlage - eine Zeichnung von einem fremden Server
 * waere ein Bild, das eines Tages verschwindet, und ein Aufruf, der
 * protokolliert, wer die Einladung geoeffnet hat.
 */
$fremd = rahmen_doc([[
    'id' => 't', 'type' => 'text',
    'settings' => ['frame' => 'eigen', 'frameSrc' => 'https://fremd.example/ranke.png'],
]]);
assert_true(!str_contains(DesignSections::css($fremd, '.d-p'), 'fremd.example'),
    'Rahmen: eine fremde Adresse faellt weg');

// Ohne Datei bleibt nur das Polster. Kein leeres Rechteck: ein Rahmen, der
// nichts zeichnet, ist schlimmer als keiner.
$leer = rahmen_doc([['id' => 't', 'type' => 'text', 'settings' => ['frame' => 'eigen']]]);
assert_true(!str_contains(DesignSections::css($leer, '.d-p'), '--d-sec-frame:'),
    'Rahmen: ohne Datei keine Variable');

/* --- Das Panel bietet ihn an --- */

/*
 * Bis zum Rahmen konnte die Schleife der gemeinsamen Einstellungen nur
 * Auswahllisten. Das ging, solange dort nur Auswahllisten standen - frameSrc
 * ist eine Datei und waere als LEERE Liste erschienen: kein Fehler, kein
 * Hinweis, nur ein Feld, in das sich nichts eintragen laesst. Genau dieser
 * Fehler ist bei der Einbettung schon einmal passiert.
 */
$tafel = (string) file_get_contents(__DIR__ . '/../templates/admin/design-edit-tafeln.php');
$bauer = (string) file_get_contents(__DIR__ . '/../templates/partials/einstellung-feld.php');

assert_same(2, substr_count($tafel, "View::partial('partials/einstellung-feld'"),
    'Panel: beide Schleifen bauen ihre Felder an derselben Stelle');
assert_contains($bauer, "=== 'src'", 'Panel: der Feldbauer kennt Dateien');
assert_contains($bauer, 'sec_setdatei_', 'Panel: und bietet ein Dateifeld an');
