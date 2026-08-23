<?php
declare(strict_types=1);

use Atelier\DesignWizard;
use Atelier\Design;

/*
 * Was darf der Kunde - und was fragt der Assistent ueberhaupt?
 *
 * Zwei Mechanismen, die leicht verwechselt werden: die Felder kommen aus den
 * bind-Namen, die das Design benutzt. Die Extras kommen aus den Rechten. Wer
 * nur die Rechte liest, bekommt einen leeren Assistenten - im heutigen
 * Bestand steht fast jedes Recht auf false.
 */

/** Ein Dokument mit genau den Ebenen, die der Test braucht. */
function wizard_doc(array $layers, array $palette = [], array $fonts = []): array
{
    return [
        'id' => 'test', 'slug' => 'test',
        'palette' => $palette,
        'fonts'   => $fonts,
        'layers'  => $layers,
    ];
}

$doc = wizard_doc([
    ['id' => 'namen', 'type' => 'text', 'bind' => 'couple_names'],
    ['id' => 'datum', 'type' => 'text', 'bind' => 'wedding_date'],
]);

$w = DesignWizard::choices($doc);

assert_same(['bride', 'groom', 'date'], $w['fields'], 'choices: couple_names fragt bride und groom, wedding_date das Datum');
assert_same([], $w['layers'], 'choices: ohne edit-Recht keine Ebene');
assert_same([], $w['palette'], 'choices: ohne customer-Haken keine Farbmarke');

// Ein bind, das die Vorlage nicht benutzt, wird nicht gefragt.
assert_true(!in_array('hashtag', $w['fields'], true), 'choices: nicht benutztes bind wird nicht gefragt');

// Dieselbe Frage zweimal gestellt bleibt eine Frage.
$doppelt = DesignWizard::choices(wizard_doc([
    ['id' => 'a', 'type' => 'text', 'bind' => 'couple_names'],
    ['id' => 'b', 'type' => 'text', 'bind' => 'bride_name'],
]));
assert_same(['bride', 'groom'], $doppelt['fields'], 'choices: vier binds, zwei Felder');

// edit ist der Hauptschalter: ohne ihn zaehlen die anderen fuenf nicht.
$zu = DesignWizard::choices(wizard_doc([
    ['id' => 'namen', 'type' => 'text', 'bind' => 'couple_names',
     'permissions' => ['edit' => false, 'color' => true, 'hide' => true]],
]));
assert_same([], $zu['layers'], 'choices: edit aus, also zaehlt color nicht');

$auf = DesignWizard::choices(wizard_doc([
    ['id' => 'namen', 'type' => 'text', 'bind' => 'couple_names',
     'permissions' => ['edit' => true, 'color' => true]],
]));
assert_same(['color' => true, 'font' => false, 'text' => false, 'photo' => false, 'hide' => false],
    $auf['layers']['namen'], 'choices: edit an, color an');

// text auf einer Ebene mit bind ist sinnlos - der Wert kommt aus den Daten.
$gebunden = DesignWizard::choices(wizard_doc([
    ['id' => 'namen', 'type' => 'text', 'bind' => 'couple_names',
     'permissions' => ['edit' => true, 'text' => true]],
]));
assert_same([], $gebunden['layers'], 'choices: text-Recht auf gebundener Ebene wird verworfen');

// photo nur, wo ein Bild steht.
$bild = DesignWizard::choices(wizard_doc([
    ['id' => 'foto', 'type' => 'photo', 'permissions' => ['edit' => true, 'photo' => true]],
    ['id' => 'zeile', 'type' => 'text', 'bind' => 'hashtag', 'permissions' => ['edit' => true, 'photo' => true]],
]));
assert_true(isset($bild['layers']['foto']), 'choices: photo-Recht auf einem Bild zaehlt');
assert_true(!isset($bild['layers']['zeile']), 'choices: photo-Recht auf Text zaehlt nicht');

// Marken: nur die mit Haken.
$marken = DesignWizard::choices(wizard_doc(
    [['id' => 'a', 'type' => 'text', 'bind' => 'hashtag']],
    ['accent' => ['value' => '#B08D57', 'customer' => true],
     'ink'    => ['value' => '#1A1A1A', 'customer' => false]],
    ['script' => ['family' => 'Great Vibes', 'customer' => true]]
));
assert_same(['accent'], array_keys($marken['palette']), 'choices: nur angehakte Farbmarke');
assert_same(['script'], array_keys($marken['fonts']), 'choices: nur angehakte Schriftmarke');

/*
 * color- und font-Recht sind an Typen gebunden, an denen Design::css()
 * tatsaechlich einen Zweig hat (nur text und shape fuer Farbe, nur text
 * fuer Schrift, Design.php:359-400). button hat dort keinen Zweig - wuerde
 * der Typ hier durchgelassen, boete der Assistent eine Kontrolle an, die
 * nie etwas veraendert.
 */
$farbtypen = DesignWizard::choices(wizard_doc([
    ['id' => 't', 'type' => 'text',  'permissions' => ['edit' => true, 'color' => true]],
    ['id' => 's', 'type' => 'shape', 'permissions' => ['edit' => true, 'color' => true]],
    ['id' => 'b', 'type' => 'button', 'permissions' => ['edit' => true, 'color' => true]],
    ['id' => 'i', 'type' => 'image', 'permissions' => ['edit' => true, 'color' => true]],
    ['id' => 'p', 'type' => 'photo', 'permissions' => ['edit' => true, 'color' => true]],
]));
assert_true($farbtypen['layers']['t']['color'], 'choices: color-Recht gilt fuer text');
assert_true($farbtypen['layers']['s']['color'], 'choices: color-Recht gilt fuer shape');
assert_true(!isset($farbtypen['layers']['b']), 'choices: color-Recht gilt nicht fuer button');
assert_true(!isset($farbtypen['layers']['i']), 'choices: color-Recht gilt nicht fuer image');
assert_true(!isset($farbtypen['layers']['p']), 'choices: color-Recht gilt nicht fuer photo');

$schrifttypen = DesignWizard::choices(wizard_doc([
    ['id' => 't', 'type' => 'text',   'permissions' => ['edit' => true, 'font' => true]],
    ['id' => 'b', 'type' => 'button', 'permissions' => ['edit' => true, 'font' => true]],
    ['id' => 'i', 'type' => 'image',  'permissions' => ['edit' => true, 'font' => true]],
]));
assert_true($schrifttypen['layers']['t']['font'], 'choices: font-Recht gilt fuer text');
assert_true(!isset($schrifttypen['layers']['b']), 'choices: font-Recht gilt nicht fuer button');
assert_true(!isset($schrifttypen['layers']['i']), 'choices: font-Recht gilt nicht fuer image');

/*
 * Die Schrittliste steht nicht fest, sie faellt aus dem Dokument.
 *
 * Elysee hat heute fast keine Rechte - der Assistent hat dort zwei Schritte,
 * und das ist richtig. Ein leerer Schritt "Design" waere ein Bildschirm ohne
 * Inhalt. Wird im Panel ein Haken gesetzt, wird der Assistent von selbst laenger.
 */

$knapp = DesignWizard::steps(wizard_doc([
    ['id' => 'namen', 'type' => 'text', 'bind' => 'couple_names'],
]));
assert_same(['angaben', 'veroeffentlichen'], $knapp, 'steps: ohne Rechte zwei Schritte');

$mitBild = DesignWizard::steps(wizard_doc([
    ['id' => 'namen', 'type' => 'text', 'bind' => 'couple_names'],
    ['id' => 'foto', 'type' => 'photo', 'permissions' => ['edit' => true, 'photo' => true]],
]));
assert_same(['angaben', 'bilder', 'veroeffentlichen'], $mitBild, 'steps: photo-Recht bringt den Bilder-Schritt');

$mitFarbe = DesignWizard::steps(wizard_doc([
    ['id' => 'namen', 'type' => 'text', 'bind' => 'couple_names',
     'permissions' => ['edit' => true, 'color' => true]],
]));
assert_same(['angaben', 'design', 'veroeffentlichen'], $mitFarbe, 'steps: color-Recht bringt den Design-Schritt');

// Der Haken an einer Marke reicht allein - ohne jedes Ebenenrecht.
$nurMarke = DesignWizard::steps(wizard_doc(
    [['id' => 'namen', 'type' => 'text', 'bind' => 'couple_names']],
    ['accent' => ['value' => '#B08D57', 'customer' => true]]
));
assert_same(['angaben', 'design', 'veroeffentlichen'], $nurMarke, 'steps: angehakte Marke oeffnet den Design-Schritt allein');

$alles = DesignWizard::steps(wizard_doc([
    ['id' => 'namen', 'type' => 'text', 'bind' => 'couple_names',
     'permissions' => ['edit' => true, 'color' => true]],
    ['id' => 'foto', 'type' => 'photo', 'permissions' => ['edit' => true, 'photo' => true]],
]));
assert_same(['angaben', 'bilder', 'design', 'veroeffentlichen'], $alles, 'steps: alle vier, in dieser Reihenfolge');

/*
 * BIND_FIELDS und Design::BINDS zaehlen dieselben elf Namen auf. Waechst die
 * eine Liste und die andere nicht, entsteht kein Fehler - es entsteht ein
 * Feld, das der Assistent nie fragt. Deshalb dieser Test.
 */
$spiegel = new ReflectionClass(DesignWizard::class);
$karte = $spiegel->getConstant('BIND_FIELDS');
$a = array_keys($karte); sort($a);
$b = Design::BINDS; sort($b);
assert_same($b, $a, 'BIND_FIELDS deckt genau Design::BINDS ab');

/*
 * personalize() ist die Grenze.
 *
 * Was hier durchkommt, steht gleich im design_snapshot und wird ausgeliefert.
 * Ein POST, der ein gesperrtes Recht behauptet, faellt still - nicht mit einer
 * Fehlerseite: das Recht kann im Panel zugegangen sein, waehrend das Formular
 * offen stand. Der Kunde bekommt dann das Design, wie es gedacht ist.
 */

$basis = wizard_doc(
    [
        ['id' => 'namen', 'type' => 'text', 'bind' => 'couple_names',
         'style' => ['color' => 'ink'],
         'permissions' => ['edit' => true, 'color' => true]],
        ['id' => 'siegel', 'type' => 'image', 'src' => '/assets/designs/elysee-1.svg',
         'permissions' => []],
    ],
    ['ink'    => ['value' => '#1A1A1A', 'customer' => false],
     'accent' => ['value' => '#B08D57', 'customer' => true]]
);

// Erlaubte Ebenenfarbe: eine eigene Marke wird gepraegt, weil der Renderer
// nur Markennamen kennt (Design.php:371).
$rot = DesignWizard::personalize($basis, ['layers' => ['namen' => ['color' => '#8B0000']]]);
assert_same('kunde-namen', $rot['layers'][0]['style']['color'], 'personalize: die Ebene zeigt auf die eigene Marke');
assert_same('#8B0000', $rot['palette']['kunde-namen']['value'], 'personalize: die Marke traegt die Farbe');
assert_same(false, $rot['palette']['kunde-namen']['customer'], 'personalize: die gepraegte Marke wird nicht wieder angeboten');
assert_contains(Design::css($rot, '.t'), 'color:var(--d-kunde-namen)', 'personalize: der Renderer schreibt die Marke');

// Zweimal dieselbe Ebene: dieselbe Marke, kein Wildwuchs.
$zweimal = DesignWizard::personalize($rot, ['layers' => ['namen' => ['color' => '#004400']]]);
assert_same('#004400', $zweimal['palette']['kunde-namen']['value'], 'personalize: zweite Farbe ueberschreibt dieselbe Marke');
assert_same(1, count(array_filter(array_keys($zweimal['palette']), static fn ($k) => str_starts_with((string) $k, 'kunde-'))), 'personalize: nur eine gepraegte Marke');

// Kollision: "kunde-<id>" ist kein reserviertes Praefix - ein Admin kann im
// Panel schon eine Marke mit genau diesem Namen angelegt haben. Die Ebene
// selbst zeigt hier noch auf 'ink', nicht auf 'kunde-namen' - die Marke ist
// also die des Grafikers, nicht eine eigene aus einer frueheren Wahl.
$kollisionsDoc = wizard_doc(
    [['id' => 'namen', 'type' => 'text', 'bind' => 'couple_names',
      'style' => ['color' => 'ink'],
      'permissions' => ['edit' => true, 'color' => true]]],
    ['ink'         => ['value' => '#1A1A1A', 'customer' => false],
     'kunde-namen' => ['value' => '#00FF00', 'customer' => false]]
);
$ausweich = DesignWizard::personalize($kollisionsDoc, ['layers' => ['namen' => ['color' => '#8B0000']]]);
assert_same('kunde-namen-2', $ausweich['layers'][0]['style']['color'], 'personalize: eine belegte kunde-Marke bekommt einen Ausweichnamen');
assert_same('#8B0000', $ausweich['palette']['kunde-namen-2']['value'], 'personalize: die neue Marke traegt die gewaehlte Farbe');
assert_same('#00FF00', $ausweich['palette']['kunde-namen']['value'], 'personalize: die urspruengliche Marke des Grafikers bleibt unangetastet');

// Unsinn wird nicht gespeichert - er wird beim Schreiben geklaert, nicht erst
// beim Drucken.
$mist = DesignWizard::personalize($basis, ['layers' => ['namen' => ['color' => 'javascript:alert(1)']]]);
assert_same('transparent', $mist['palette']['kunde-namen']['value'], 'personalize: ungueltige Farbe wird transparent');

// Gesperrte Ebene: faellt still.
$gesperrt = DesignWizard::personalize($basis, ['layers' => ['siegel' => ['color' => '#8B0000']]]);
assert_same([], array_filter(array_keys($gesperrt['palette']), static fn ($k) => str_starts_with((string) $k, 'kunde-')), 'personalize: ohne edit-Recht keine Marke');

// Erfundene Kennung: faellt still.
$erfunden = DesignWizard::personalize($basis, ['layers' => ['gibtesnicht' => ['color' => '#8B0000']]]);
assert_same(count($basis['layers']), count($erfunden['layers']), 'personalize: erfundene Ebene fuegt nichts hinzu');

// Angehakte Marke: darf.
$marke = DesignWizard::personalize($basis, ['palette' => ['accent' => '#8B0000']]);
assert_same('#8B0000', $marke['palette']['accent']['value'], 'personalize: angehakte Marke wird gesetzt');

// Nicht angehakte Marke: faellt still.
$sperre = DesignWizard::personalize($basis, ['palette' => ['ink' => '#8B0000']]);
assert_same('#1A1A1A', $sperre['palette']['ink']['value'], 'personalize: Marke ohne Haken bleibt');

// text auf einer gebundenen Ebene: faellt still.
$text = DesignWizard::personalize($basis, ['layers' => ['namen' => ['text' => ['de' => 'X', 'en' => 'X']]]]);
assert_same('', $text['layers'][0]['text']['de'], 'personalize: fester Text auf gebundener Ebene wird verworfen');

// Ausblenden ohne Recht: faellt still. Wichtig ist, welche Ebene das prueft:
// 'siegel' hat gar kein edit-Recht und wuerde schon am $rechte===null-Wächter
// scheitern, ohne je die hide-Pruefung zu erreichen. 'namen' dagegen hat
// edit und color, also passiert die Wahl den Wächter - und nur das hide-Gatter
// selbst darf sie noch stoppen. Eine falsche Umsetzung, die bloss
// "!empty($gewaehlt['hidden'])" prueft, wuerde diese Ebene trotzdem entfernen.
$weg = DesignWizard::personalize($basis, ['layers' => ['namen' => ['hidden' => true]]]);
assert_same(2, count($weg['layers']), 'personalize: ohne hide-Recht bleibt die Ebene stehen, obwohl sie andere Rechte hat');

// Ausblenden mit Recht: die Ebene verschwindet wirklich, der Rest rueckt
// mit luckenlosen Schluesseln nach und behaelt seine Reihenfolge - die
// Reihenfolge ist der z-Index.
$hideDoc = wizard_doc([
    ['id' => 'a', 'type' => 'text', 'bind' => 'couple_names',
     'permissions' => ['edit' => true, 'hide' => true]],
    ['id' => 'b', 'type' => 'text', 'bind' => 'wedding_date',
     'permissions' => ['edit' => true, 'hide' => true]],
    ['id' => 'c', 'type' => 'text', 'bind' => 'wedding_time',
     'permissions' => ['edit' => true, 'hide' => true]],
]);
$versteckt = DesignWizard::personalize($hideDoc, ['layers' => ['b' => ['hidden' => true]]]);
assert_same(2, count($versteckt['layers']), 'personalize: mit hide-Recht verschwindet die Ebene');
assert_same([0, 1], array_keys($versteckt['layers']), 'personalize: die Schluessel sind nach dem Entfernen luckenlos');
assert_same(['a', 'c'], array_map(static fn (array $el): string => (string) $el['id'], $versteckt['layers']), 'personalize: die Reihenfolge der uebrigen Ebenen bleibt erhalten');

// Schrift-Zweig: eine erlaubte Schrift praegt eine eigene Marke, die Ebene
// zeigt auf sie, und die Messwerte (Groesse, Gewicht, Laufweite, Zeilenhoehe)
// erbt sie von der Marke, die die Ebene vorher trug - sonst spraenge der
// Titel in einer fremden Groesse auf die Seite.
$fontDoc = wizard_doc(
    [['id' => 'titel', 'type' => 'text', 'bind' => '',
      'style' => ['font' => 'script'],
      'permissions' => ['edit' => true, 'font' => true]]],
    [],
    ['script' => ['family' => 'Great Vibes', 'size' => 140, 'weight' => 500, 'tracking' => 5, 'lineHeight' => 110]]
);
$schrift = DesignWizard::personalize($fontDoc, ['layers' => ['titel' => ['font' => 'Cormorant']]]);
assert_same('kunde-titel', $schrift['layers'][0]['style']['font'], 'personalize: die Ebene zeigt auf die eigene Schriftmarke');
assert_same('Cormorant', $schrift['fonts']['kunde-titel']['family'], 'personalize: die Marke traegt die Schrift');
assert_same(false, $schrift['fonts']['kunde-titel']['customer'], 'personalize: die gepraegte Schriftmarke wird nicht wieder angeboten');
assert_same(140, $schrift['fonts']['kunde-titel']['size'], 'personalize: die Groesse erbt von der vorherigen Marke');
assert_same(500, $schrift['fonts']['kunde-titel']['weight'], 'personalize: das Gewicht erbt von der vorherigen Marke');
assert_same(5, $schrift['fonts']['kunde-titel']['tracking'], 'personalize: die Laufweite erbt von der vorherigen Marke');
assert_same(110, $schrift['fonts']['kunde-titel']['lineHeight'], 'personalize: die Zeilenhoehe erbt von der vorherigen Marke');

// Foto-Zweig: ein erlaubter Pfad wird uebernommen; ein Pfad ausserhalb von
// /uploads oder /assets wird beim Schreiben verworfen (Design::safeSrc()),
// und die Ebene behaelt ihren alten Pfad, statt eine leere Quelle einzufrieren.
$fotoDoc = wizard_doc([
    ['id' => 'foto', 'type' => 'photo', 'src' => '/uploads/einladungen/v2/x/original.jpg',
     'permissions' => ['edit' => true, 'photo' => true]],
]);
$gutesFoto = DesignWizard::personalize($fotoDoc, ['layers' => ['foto' => ['src' => '/uploads/einladungen/v2/x/neu.jpg']]]);
assert_same('/uploads/einladungen/v2/x/neu.jpg', $gutesFoto['layers'][0]['src'], 'personalize: ein erlaubter Pfad wird uebernommen');

$boesesFoto = DesignWizard::personalize($fotoDoc, ['layers' => ['foto' => ['src' => 'https://evil.example/x.jpg']]]);
assert_same('/uploads/einladungen/v2/x/original.jpg', $boesesFoto['layers'][0]['src'], 'personalize: ein Pfad ausserhalb von /uploads oder /assets wird verworfen, der alte Pfad bleibt stehen');

// text-Zweig: eine mitgeschickte Sprache wird gesetzt, eine nicht
// mitgeschickte bleibt stehen - dieselbe Regel wie Design::fromPost(), ein
// leeres Feld ist kein Loeschbefehl.
$textDoc = wizard_doc([
    ['id' => 'motto', 'type' => 'text', 'bind' => '',
     'text' => ['de' => 'Hallo', 'en' => 'Hello'],
     'permissions' => ['edit' => true, 'text' => true]],
]);
$teiltext = DesignWizard::personalize($textDoc, ['layers' => ['motto' => ['text' => ['de' => 'Servus']]]]);
assert_same('Servus', $teiltext['layers'][0]['text']['de'], 'personalize: die mitgeschickte Sprache wird gesetzt');
assert_same('Hello', $teiltext['layers'][0]['text']['en'], 'personalize: eine nicht mitgeschickte Sprache bleibt stehen');

// Die Form bleibt die eines vollstaendigen Dokuments - der Schnappschuss geht
// unveraendert an den Renderer.
assert_same(Design::complete($rot), $rot, 'personalize: das Ergebnis ist bereits vollstaendig');

// Rein: dasselbe Design traegt zwei Einladungen.
$vorher = $basis;
DesignWizard::personalize($basis, ['layers' => ['namen' => ['color' => '#8B0000']]]);
assert_same($vorher, $basis, 'personalize: die Vorlage bleibt unberuehrt');

use Atelier\InvitationsV2;

/*
 * Der Name muss in beiden Tabellen frei sein.
 *
 * Das v2-Praefix in der Adresse ist absichtlich vorlaeufig (Phase 1, §1). An
 * dem Tag, an dem es faellt, muss /einladung/{slug} genau eine Einladung
 * treffen. Heute kostet das nichts; spaeter waere es unmoeglich - eine
 * veroeffentlichte Adresse benennt man nicht um.
 */
if (needs_db()) {
    // bin/test.php hat den Autoloader schon registriert und View.php schon
    // per require geladen (nicht require_once) - src/bootstrap.php wuerde
    // View.php ein zweites Mal einbinden und e() doppelt erklaeren. Deshalb
    // hier nur das eine Stueck aus bootstrap.php nachholen, das wirklich
    // fehlt: die Konfiguration fuer die Datenbankverbindung.
    Atelier\Config::load(dirname(__DIR__) . '/config.php');

    $name = 'test-v2-' . bin2hex(random_bytes(4));
    assert_true(InvitationsV2::slugAvailable($name), 'slugAvailable: ein frischer Name ist frei');

    InvitationsV2::create($name, 'elysee', ['id' => 'elysee', 'layers' => []], ['bride' => 'Marie']);
    assert_true(!InvitationsV2::slugAvailable($name), 'slugAvailable: nach dem Anlegen belegt');

    $gefunden = InvitationsV2::find($name);
    assert_same('elysee', $gefunden['design_id'] ?? '', 'find: die Kennung des Designs steht drin');
    assert_same('Marie', $gefunden['data']['bride'] ?? '', 'find: die Daten kommen als Feld zurueck');
    assert_true(isset($gefunden['design_snapshot']['layers']), 'find: der Schnappschuss kommt als Feld zurueck');

    \Atelier\Db::run('DELETE FROM invitations_v2 WHERE slug = ?', [$name]);

    /*
     * Die andere Haelfte: der Name muss auch in der ALTEN Tabelle frei sein.
     *
     * Faellt das v2 eines Tages aus der Adresse, muss /einladung/{slug} genau eine
     * Einladung treffen. Ohne diesen Test ginge eine Fassung durch, die nur
     * invitations_v2 befragt - die alte Tabelle ist leer, es faellt nicht auf.
     */
    $alt = 'test-alt-' . bin2hex(random_bytes(4));
    \Atelier\Db::run('INSERT INTO invitations (slug, data) VALUES (?, ?)', [$alt, \Atelier\Db::encode(['slug' => $alt])]);
    assert_true(!InvitationsV2::slugAvailable($alt), 'slugAvailable: in der alten Tabelle belegt zaehlt auch');
    \Atelier\Db::run('DELETE FROM invitations WHERE slug = ?', [$alt]);
} else {
    echo "  (invitations_v2: uebersprungen, keine config.php)\n";
}

use Atelier\DesignSections;

/*
 * Abschnitte im Assistenten: dieselben zwei Mechanismen wie bei den Ebenen.
 * Was gefragt wird, entscheidet der Typ (family und program brauchen Inhalt,
 * location und countdown leben von dem, was ohnehin gefragt wird). Was
 * angeboten wird, entscheiden die Rechte.
 */
function wiz_sec(array $sections, array $layers = []): array
{
    return ['id' => 'test', 'slug' => 'test', 'layers' => $layers, 'sections' => $sections];
}

$zu = DesignWizard::choices(wiz_sec([
    ['id' => 'prog-1', 'type' => 'program'],
]));
assert_same([], $zu['sections'], 'choices: ohne edit-Recht kein Abschnitt');

$auf = DesignWizard::choices(wiz_sec([
    ['id' => 'prog-1', 'type' => 'program', 'permissions' => ['edit' => true, 'hide' => true]],
    ['id' => 'ort-1',  'type' => 'location', 'permissions' => ['edit' => true]],
]));
assert_same(['prog-1', 'ort-1'], array_keys($auf['sections']), 'choices: beide edit-Abschnitte');
assert_same(true, $auf['sections']['prog-1']['hide'], 'choices: hide steht');
assert_same(false, $auf['sections']['ort-1']['hide'], 'choices: ohne hide kein hide');
assert_same(['program'], $auf['sections']['prog-1']['fields'], 'choices: das Programm braucht Inhalt');
assert_same([], $auf['sections']['ort-1']['fields'], 'choices: der Ort lebt von den Angaben');

/*
 * Ein abgeschalteter, aber sonst erlaubter Abschnitt wird nicht angeboten.
 * Ohne diese Regel fragte der Assistent nach Inhalt, den visible() beim
 * Drucken ohnehin wegwirft - der Kunde tippt ein Programm, und es
 * verschwindet spurlos.
 */
$aus = DesignWizard::choices(wiz_sec([
    ['id' => 'prog-1', 'type' => 'program', 'enabled' => false, 'permissions' => ['edit' => true, 'hide' => true]],
    ['id' => 'ort-1',  'type' => 'location', 'permissions' => ['edit' => true]],
]));
assert_same(['ort-1'], array_keys($aus['sections']), 'choices: ein abgeschalteter Abschnitt wird nicht angeboten, obwohl edit steht');

// Der Titel des Grafikers kommt mit - der Assistent soll nicht die interne
// Kennung anzeigen muessen.
$mitTitel = DesignWizard::choices(wiz_sec([
    ['id' => 'prog-1', 'type' => 'program', 'title' => ['de' => 'Ablauf', 'en' => 'Schedule'],
     'permissions' => ['edit' => true]],
]));
assert_same(['de' => 'Ablauf', 'en' => 'Schedule'], $mitTitel['sections']['prog-1']['title'], 'choices: der Titel wird mitgegeben');

// Der Schritt kommt nur, wenn es dort etwas zu tun gibt.
assert_same(['angaben', 'veroeffentlichen'], DesignWizard::steps(wiz_sec([
    ['id' => 'ort-1', 'type' => 'location'],
])), 'steps: ohne Rechte kein Abschnitte-Schritt');

/*
 * Ein angebotener Abschnitt ohne jede Kontrolle darf den Schritt nicht
 * oeffnen: location hat edit=true, aber hide=false und keine fields - dann
 * bliebe im Schritt nur eine Ueberschrift ueber einem leeren Bildschirm
 * stehen, und ein leerer Schritt ist verboten.
 */
assert_same(['angaben', 'veroeffentlichen'], DesignWizard::steps(wiz_sec([
    ['id' => 'ort-1', 'type' => 'location', 'permissions' => ['edit' => true]],
])), 'steps: edit ohne hide und ohne fields bringt keinen leeren Abschnitte-Schritt');

assert_same(['angaben', 'abschnitte', 'veroeffentlichen'], DesignWizard::steps(wiz_sec([
    ['id' => 'fam-1', 'type' => 'family', 'permissions' => ['edit' => true]],
])), 'steps: ein Abschnitt mit Inhalt bringt den Schritt');

// hide allein (ohne fields) reicht auch - der Kunde hat etwas zu schalten.
assert_same(['angaben', 'abschnitte', 'veroeffentlichen'], DesignWizard::steps(wiz_sec([
    ['id' => 'ort-1', 'type' => 'location', 'permissions' => ['edit' => true, 'hide' => true]],
])), 'steps: edit+hide ohne fields bringt den Schritt trotzdem');

// Die Reihenfolge: Inhalt vor Aussehen.
$voll = DesignWizard::steps(wiz_sec(
    [['id' => 'fam-1', 'type' => 'family', 'permissions' => ['edit' => true]]],
    [['id' => 'foto', 'type' => 'photo', 'permissions' => ['edit' => true, 'photo' => true]],
     ['id' => 'name', 'type' => 'text', 'bind' => 'couple_names',
      'permissions' => ['edit' => true, 'color' => true]]]
));
assert_same(['angaben', 'bilder', 'abschnitte', 'design', 'veroeffentlichen'], $voll, 'steps: alle fuenf, in dieser Reihenfolge');

// personalize: erlaubtes Ausblenden wird ins Dokument geschrieben.
$basis = wiz_sec([
    ['id' => 'fam-1', 'type' => 'family', 'permissions' => ['edit' => true, 'hide' => true]],
    ['id' => 'ort-1', 'type' => 'location', 'permissions' => ['edit' => true]],
]);

$weg = DesignWizard::personalize($basis, ['sections' => ['fam-1' => ['hidden' => true]]]);
assert_same(false, $weg['sections'][0]['enabled'], 'personalize: erlaubtes Ausblenden wirkt');

// Ohne hide-Recht faellt es still.
$bleibt = DesignWizard::personalize($basis, ['sections' => ['ort-1' => ['hidden' => true]]]);
assert_same(true, $bleibt['sections'][1]['enabled'], 'personalize: ohne hide-Recht bleibt der Abschnitt an');

// Erfundene Kennung faellt still.
$erfunden = DesignWizard::personalize($basis, ['sections' => ['gibtesnicht' => ['hidden' => true]]]);
assert_same(2, count($erfunden['sections']), 'personalize: erfundener Abschnitt fuegt nichts hinzu');

/* --- Eine Videoebene mit photo-Recht wird angeboten --- */

$doc = ['id' => 'x', 'layers' => [
    ['id' => 'film', 'type' => 'video', 'src' => '/uploads/a.mp4',
     'permissions' => ['edit' => true, 'photo' => true]],
]];

$wahl = DesignWizard::choices($doc);

assert_true(isset($wahl['layers']['film']), 'wizard: Videoebene wird angeboten');
assert_true($wahl['layers']['film']['photo'], 'wizard: und zwar mit dem photo-Recht');

/* --- Ohne edit bleibt sie gesperrt, wie jede andere Ebene --- */

$zu = DesignWizard::choices(['id' => 'x', 'layers' => [
    ['id' => 'film', 'type' => 'video', 'permissions' => ['edit' => false, 'photo' => true]],
]]);

assert_true(!isset($zu['layers']['film']), 'wizard: ohne edit wird nichts angeboten');

/* --- Der Poster reist mit der Quelle, sonst stuende hinter dem gewaehlten
       Film das Standbild des vorigen --- */

$sockel = ['id' => 'x', 'layers' => [
    ['id' => 'film', 'type' => 'video', 'src' => '/uploads/vorlage.mp4',
     'poster' => '/uploads/vorlage.jpg',
     'permissions' => ['edit' => true, 'photo' => true]],
]];

$fertig = DesignWizard::personalize($sockel, ['layers' => ['film' => [
    'src' => '/uploads/videos/neu.mp4', 'poster' => '/uploads/videos/neu.jpg',
]]]);

assert_same('/uploads/videos/neu.mp4', $fertig['layers'][0]['src'], 'personalize: der gewaehlte Film steht da');
assert_same('/uploads/videos/neu.jpg', $fertig['layers'][0]['poster'], 'personalize: und sein eigenes Standbild');

/* --- Ein Film ohne Standbild loescht das alte, statt ein falsches zu behalten --- */

$ohne = DesignWizard::personalize($sockel, ['layers' => ['film' => [
    'src' => '/uploads/videos/neu.mp4', 'poster' => '',
]]]);

assert_same('', $ohne['layers'][0]['poster'], 'personalize: kein Standbild heisst kein Standbild');

/* --- Ohne Wahl bleibt beides, wie die Vorlage es hatte --- */

$nichts = DesignWizard::personalize($sockel, []);

assert_same('/uploads/vorlage.mp4', $nichts['layers'][0]['src'], 'personalize: ohne Wahl bleibt der Film der Vorlage');
assert_same('/uploads/vorlage.jpg', $nichts['layers'][0]['poster'], 'personalize: und ihr Standbild auch');
