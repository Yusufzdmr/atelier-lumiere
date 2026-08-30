<?php
declare(strict_types=1);

use Atelier\DesignSections;
use Atelier\SectionRegistry;

/*
 * Der Katalog der Abschnittsarten - und was jede von ihnen kann.
 *
 * Bis hierher war eine Art ein Wort: "program" hiess genau ein Aussehen, das
 * in DesignSections::programm() stand. Wer ein zweites wollte, brauchte eine
 * siebte Art - und damit einen neuen Zweig in vier match()-Bloecken.
 *
 * Der Katalog trennt beides: die ART sagt, WAS ein Abschnitt zeigt (ein
 * Ablauf, ein Ort, eine Frage), die VARIANTE sagt, WIE er aussieht. Eine neue
 * Variante ist damit ein Eintrag und ein Stilblock, keine neue Art.
 *
 * Die EINSTELLUNGEN sind der dritte Teil: Knoepfe, die der Grafiker dreht,
 * ohne dass daraus eine eigene Variante wird - Ausrichtung, Luft, ob der
 * Kartenlink mitkommt. Sie tragen ein Schema, damit derselbe Eintrag sowohl
 * das Formular baut als auch die Eingabe prueft. Zwei Listen mit denselben
 * Grenzen liefen auseinander.
 */

/* --- Jede Art im Katalog hat mindestens eine Variante --- */

foreach (DesignSections::TYPES as $art) {
    assert_true(SectionRegistry::has($art), 'Katalog: ' . $art . ' steht im Register');
    assert_true(SectionRegistry::variants($art) !== [], 'Katalog: ' . $art . ' hat eine Variante');
    assert_true(
        isset(SectionRegistry::variants($art)[SectionRegistry::defaultVariant($art)]),
        'Katalog: die Voreinstellung von ' . $art . ' steht in seiner Liste'
    );
}

// Umgekehrt auch: kein Eintrag ohne Art. Sonst boete das Panel eine Variante
// an, die DesignSections gar nicht drucken kann.
foreach (array_keys(SectionRegistry::all()) as $art) {
    assert_true(in_array($art, DesignSections::TYPES, true), 'Katalog: ' . $art . ' ist eine bekannte Art');
}

/* --- Die Voreinstellung heisst ueberall gleich --- */

assert_same('default', SectionRegistry::defaultVariant('program'), 'Katalog: die Voreinstellung heisst default');
assert_same('default', SectionRegistry::defaultVariant('gibtesnicht'), 'Katalog: auch fuer eine unbekannte Art');

/* --- Der Ablauf hat eine zweite Gestalt --- */

assert_true(SectionRegistry::isVariant('program', 'zeitstrahl'), 'Katalog: der Ablauf kann Zeitstrahl');
assert_true(!SectionRegistry::isVariant('program', 'discokugel'), 'Katalog: erfundene Varianten gibt es nicht');
assert_true(!SectionRegistry::isVariant('location', 'zeitstrahl'), 'Katalog: eine Variante gehoert ihrer Art');

/* --- Einstellungen: was fehlt, faellt auf die Voreinstellung --- */

$leer = SectionRegistry::completeSettings('program', []);

assert_same('center', $leer['align'], 'Einstellungen: ohne Angabe steht der Abschnitt mittig');
assert_same('m', $leer['space'], 'Einstellungen: ohne Angabe mittlere Luft');
assert_same('auto', $leer['spaceTop'], 'Einstellungen: oben bleibt das gerechnete Polster');

/*
 * Die drei alten Worte treffen ihre alten Werte.
 *
 * Auf dem Demoserver stehen Vorlagen, die "weit" gewaehlt haben. Ohne
 * Uebersetzung faende die Pruefung das Wort nicht in der Liste, setzte
 * stillschweigend die Voreinstellung - und der Abschnitt rueckte beim
 * naechsten Deploy von 22 % auf 12 % zusammen, ohne dass jemand etwas
 * angefasst haette.
 */
foreach (['eng' => 's', 'normal' => 'm', 'weit' => 'l'] as $alt => $neu) {
    assert_same($neu, SectionRegistry::completeSettings('program', ['space' => $alt])['space'],
        'Einstellungen: "' . $alt . '" ist heute "' . $neu . '"');
}

// Und die Leiter hat fuenf Sprossen, nicht drei.
assert_same(['xs', 's', 'm', 'l', 'xl'],
    SectionRegistry::settings('program')['space']['options'],
    'Einstellungen: die Leiter der Luft');

/* --- Was danebenliegt, faellt zurueck; was nicht im Schema steht, faellt weg --- */

$quatsch = SectionRegistry::completeSettings('program', [
    'align'      => 'diagonal',
    'space'      => 'm',
    'discokugel' => 'an',
]);

assert_same('center', $quatsch['align'], 'Einstellungen: eine unbekannte Wahl faellt zurueck');
assert_true(!array_key_exists('discokugel', $quatsch), 'Einstellungen: ein fremder Schluessel faellt weg');

/* --- Wahrheitswerte und Zahlen --- */

$ort = SectionRegistry::completeSettings('location', ['map' => '1']);
assert_same(true, $ort['map'], 'Einstellungen: ein Haken wird zum Wahrheitswert');

$ohneKarte = SectionRegistry::completeSettings('location', ['map' => '']);
assert_same(false, $ohneKarte['map'], 'Einstellungen: leer heisst aus');

/* --- Eine Art, die es nicht gibt, hat auch keine Einstellungen --- */

assert_same([], SectionRegistry::completeSettings('wetterbericht', ['align' => 'left']),
    'Einstellungen: eine unbekannte Art hat keine');

/* --- Das Schema baut das Formular: jeder Eintrag sagt, was er ist --- */

foreach (SectionRegistry::all() as $art => $eintrag) {
    foreach (SectionRegistry::settings($art) as $schluessel => $schema) {
        assert_true(isset($schema['type']), 'Schema: ' . $art . '.' . $schluessel . ' sagt seine Art');
        assert_true(array_key_exists('default', $schema), 'Schema: ' . $art . '.' . $schluessel . ' hat eine Voreinstellung');
        if ($schema['type'] === 'select') {
            assert_true(
                in_array($schema['default'], $schema['options'], true),
                'Schema: die Voreinstellung von ' . $art . '.' . $schluessel . ' steht in ihrer Liste'
            );
        }
    }
}

/* --- Ein Pfad als Einstellung: die Musik bringt ihre Tonspur mit --- */

$ton = SectionRegistry::completeSettings('music', ['track' => '/uploads/designs/lied.mp3']);
assert_same('/uploads/designs/lied.mp3', $ton['track'], 'Einstellungen: der Pfad kommt an');

/*
 * Ein fremder Host faellt weg - dieselbe Pruefung wie bei Bildern und Filmen.
 * Eine Tonspur von woanders waere ein Hoerer, der mitschreibt, wer die
 * Einladung geoeffnet hat.
 */
$fremd = SectionRegistry::completeSettings('music', ['track' => 'https://beispiel.de/lied.mp3']);
assert_same('', $fremd['track'], 'Einstellungen: ein fremder Host faellt weg');

$leer = SectionRegistry::completeSettings('music', []);
assert_same('', $leer['track'], 'Einstellungen: ohne Angabe keine Tonspur');

/* --- Der Katalog der Zeichen ---------------------------------------------
 *
 * Ein Zeichen ist kein Bild, das jemand hochlaedt, sondern ein Eintrag im
 * Katalog: Kennung, Datei, Etikett. Die Kennung steht spaeter im Dokument
 * einer Vorlage und in der Einladung eines Paares - sie darf sich also nicht
 * mehr aendern, und der Test haelt sie fest.
 *
 * Gefaerbt wird nicht hier, sondern im Stilblock: die Datei liegt als Maske
 * ueber einer Flaeche in currentColor. Deshalb steht in jeder Datei eine
 * einfarbige Zeichnung und keine Palette - eine bunte SVG waere unter einer
 * Maske ohnehin nur noch eine Silhouette.
 */

$zeichen = SectionRegistry::icons();

assert_true($zeichen !== [], 'icons: der Katalog ist nicht leer');

foreach ($zeichen as $kennung => $eintrag) {
    assert_same(1, preg_match('/^[a-z][a-z0-9-]*$/', (string) $kennung),
        'icons: die Kennung "' . $kennung . '" ist sauber');

    $pfad = __DIR__ . '/../public/assets/icons/' . $eintrag['file'];
    assert_true(is_file($pfad), 'icons: die Datei zu "' . $kennung . '" liegt da');

    assert_true(($eintrag['label']['de'] ?? '') !== '', 'icons: "' . $kennung . '" hat ein deutsches Etikett');
    assert_true(($eintrag['label']['tr'] ?? '') !== '', 'icons: "' . $kennung . '" hat ein tuerkisches Etikett');
}

// Der Weg von der Kennung zur Adresse - und was passiert, wenn es sie nicht gibt.
assert_same('/assets/icons/pasta.svg', SectionRegistry::iconFile('pasta'), 'icons: die Adresse steht');
assert_same('', SectionRegistry::iconFile('gibtesnicht'), 'icons: eine unbekannte Kennung faellt still');
assert_same('', SectionRegistry::iconFile('../../config'), 'icons: und ein Ausbruchsversuch auch');

/* --- Was ein Zeichen im Druck heisst -------------------------------------
 *
 * Das Etikett oben ist fuer das PANEL (de/tr - der Grafiker sucht in einer
 * Liste). Auf der Einladung steht etwas anderes und in anderen Sprachen: die
 * Seite spricht Deutsch und Englisch, und dort heisst "pasta" nicht "Torte",
 * sondern "Tortenanschnitt" - eine Zeile im Ablauf, kein Listeneintrag.
 *
 * Vorgeschlagen, nicht vorgeschrieben: schreibt das Paar etwas, gewinnt das
 * Paar. Dieselbe Regel wie bei den Voreinstellungen der Abschnitte.
 */

foreach (SectionRegistry::icons() as $kennung => $eintrag) {
    assert_true(($eintrag['title']['de'] ?? '') !== '', 'icons: "' . $kennung . '" hat einen deutschen Vorschlag');
    assert_true(($eintrag['title']['en'] ?? '') !== '', 'icons: "' . $kennung . '" hat einen englischen Vorschlag');
}

assert_same('Tortenanschnitt', SectionRegistry::iconTitle('pasta', 'de'), 'iconTitle: der deutsche Vorschlag');
assert_same('Cutting the cake', SectionRegistry::iconTitle('pasta', 'en'), 'iconTitle: der englische');
assert_same('Tortenanschnitt', SectionRegistry::iconTitle('pasta', 'tr'), 'iconTitle: eine fremde Sprache faellt auf Deutsch');
assert_same('', SectionRegistry::iconTitle('gibtesnicht', 'de'), 'iconTitle: unbekannt bleibt leer');

/* --- Die Gaenge der Speisekarte --- */

$gaenge = SectionRegistry::inputs('menu');

assert_same(['vorspeise', 'suppe', 'hauptgang', 'meze', 'dessert', 'getraenk'], array_keys($gaenge),
    'menu: die sechs Gaenge stehen in der Reihenfolge, in der sie serviert werden');

foreach ($gaenge as $schluessel => $feld) {
    foreach (['de', 'en', 'tr'] as $sprache) {
        assert_true(($feld['label'][$sprache] ?? '') !== '',
            'menu: "' . $schluessel . '" hat ein Etikett auf ' . $sprache);
    }
    // Das Zeichen der Art steht im Katalog und nicht in der Vorlage: eine
    // Suppe sieht in jeder Vorlage wie eine Suppe aus.
    assert_true(SectionRegistry::iconFile((string) ($feld['icon'] ?? '')) !== '',
        'menu: "' . $schluessel . '" traegt ein Zeichen, das es gibt');
}

/* --- Wie die Arten heissen --- */

/*
 * Bis hierher stand im Panel der rohe englische Schluessel: "gift",
 * "footer", "dresscode". Der Kunde hat genau danach gefragt - "Gift ne
 * oluyor", "Footer ney" -, und die zweite Frage kam, weil die erste
 * beantwortet werden musste.
 *
 * Schlimmer als unschoen: er hat in "gift" ein Bild hochgeladen und
 * gewartet. Das ist die Kontonummer-Art; Bilder gehoeren in "gallery". Ein
 * Wort haette die halbe Stunde gespart.
 *
 * Die Kennung selbst bleibt englisch - sie steht in jedem Dokument und in
 * jeder Einladung. Uebersetzt wird nur, was der Grafiker liest.
 */

foreach (DesignSections::TYPES as $art) {
    foreach (['de', 'en', 'tr'] as $sprache) {
        $etikett = SectionRegistry::typeLabel($art, $sprache);
        assert_true($etikett !== '', 'typeLabel: ' . $art . ' hat einen Namen auf ' . $sprache);
        assert_true($etikett !== $art,
            'typeLabel: ' . $art . ' heisst auf ' . $sprache . ' nicht wieder "' . $art . '"');
    }
}

// Die zwei, die der Kunde nicht verstanden hat - namentlich, damit sie
// niemand versehentlich wieder zu "Sonstiges" macht.
assert_same('Hediye & hesap', SectionRegistry::typeLabel('gift', 'tr'), 'typeLabel: gift ist die Kontonummer');
assert_same('Fotoğraflar', SectionRegistry::typeLabel('gallery', 'tr'), 'typeLabel: gallery sind die Bilder');
assert_same('Alt bilgi', SectionRegistry::typeLabel('footer', 'tr'), 'typeLabel: footer ist der Schluss');

// Eine fremde Sprache faellt auf Deutsch, wie ueberall sonst im Katalog.
assert_same(
    SectionRegistry::typeLabel('gift', 'de'),
    SectionRegistry::typeLabel('gift', 'fr'),
    'typeLabel: eine unbekannte Sprache faellt auf Deutsch'
);

assert_same('', SectionRegistry::typeLabel('gibtesnicht', 'de'), 'typeLabel: unbekannte Art bleibt leer');

/*
 * Und der Halbsatz darunter. Er ist der eigentliche Dienst: der Name sagt,
 * wie die Art heisst, der Hinweis sagt, was hineingehoert - und daran ist
 * die Verwechslung entstanden.
 */
foreach (DesignSections::TYPES as $art) {
    foreach (['de', 'en', 'tr'] as $sprache) {
        assert_true(SectionRegistry::typeHint($art, $sprache) !== '',
            'typeHint: ' . $art . ' sagt auf ' . $sprache . ', was hineingehoert');
    }
}

// Die beiden, die einmal verwechselt wurden, sagen es ausdruecklich.
assert_contains(SectionRegistry::typeHint('gift', 'tr'), 'resim', 'typeHint: gift warnt vor Bildern');
assert_contains(SectionRegistry::typeHint('gallery', 'tr'), 'resimlerin', 'typeHint: gallery holt sie ab');

/*
 * Und das Panel benutzt die Namen auch.
 *
 * Die Funktion allein haette nichts geaendert: der Fehler war nicht, dass es
 * keine Uebersetzung gab, sondern dass an drei Stellen die rohe Kennung
 * gedruckt wurde. Zwei davon sind die Tafel (Kartenwahl und Auswahlliste),
 * die dritte ist die Zeile in der Liste links.
 *
 * Geprueft wird die Vorlage als Text, wie bei der Buehne in
 * kuvert_vorspann.php - eine gerenderte Panelseite braucht eine Anmeldung.
 */
$tafelnQuelle = (string) file_get_contents(__DIR__ . '/../templates/admin/design-edit-tafeln.php');
$listeQuelle  = (string) file_get_contents(__DIR__ . '/../templates/admin/design-edit-liste.php');

assert_same(2, substr_count($tafelnQuelle, 'SectionRegistry::typeLabel'),
    'Tafel: beide Stellen - Kartenwahl und Auswahlliste - nennen die Art beim Namen');
assert_contains($tafelnQuelle, 'SectionRegistry::typeHint',
    'Tafel: und sagen darunter, was hineingehoert');
assert_contains($listeQuelle, 'SectionRegistry::typeLabel',
    'Liste: die Zeile links nennt die Art beim Namen');

// Die KENNUNG bleibt der Wert des Feldes. Wuerde dort der Name stehen, kaeme
// beim Speichern "Hediye & hesap" als Art an und faellt in complete() weg -
// der Abschnitt waere still verschwunden.
assert_contains($tafelnQuelle, 'value="<?= e($typ) ?>"',
    'Tafel: gespeichert wird weiterhin die englische Kennung');
