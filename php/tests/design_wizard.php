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
