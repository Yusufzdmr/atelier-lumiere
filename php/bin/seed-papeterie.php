<?php

declare(strict_types=1);

/**
 * "Papeterie" - die Vorlage, die zeigt, wozu die Pakete A bis D da waren.
 *
 *   php bin/seed-papeterie.php          schreiben
 *   php bin/seed-papeterie.php --dry    nur zeigen, was entstuende
 *
 * Der Anlass ist Punkt 12 der Anfrage, und er ist der einzige, der KEIN
 * Feature war: "her bolum farkli bir gorsel yapiya sahip olmali - davetiye
 * scroll edildiginde her sey ayni gorunmemeli."
 *
 * Das laesst sich nicht programmieren. Was sich programmieren liess, steht
 * seit den vier Paketen bereit - sechs Textrollen, eine Leiter fuer die Luft,
 * sieben Rahmen um den Text, sieben Formen fuer Fotos, mehrere Gestalten je
 * Art. Hier werden sie einmal BENUTZT, damit sichtbar wird, was sie ergeben,
 * und damit der Grafiker eine Vorlage zum Abschauen hat statt einer Liste von
 * Knoepfen.
 *
 * Die Karte kommt unveraendert von Élysée: Ebenen, Palette, Schriften,
 * Kuvert. Was hier entsteht, ist die STRECKE darunter - und genau die war
 * bisher zehnmal derselbe zentrierte Absatz.
 *
 * Idempotent: ein zweiter Lauf schreibt dieselbe Vorlage noch einmal. Sie
 * entsteht als Entwurf (status=draft), damit sie nicht ungefragt im
 * Schaufenster steht.
 */

require __DIR__ . '/../src/bootstrap.php';

use Atelier\Design;

if (PHP_SAPI !== 'cli') {
    exit('Nur über die Kommandozeile.');
}

$dry = in_array('--dry', $argv, true);

$basis = Design::find('elysee');
if ($basis === null) {
    exit("elysee gibt es nicht - erst php bin/seed-designs.php laufen lassen.\n");
}

$doc = Design::copy($basis, 'papeterie', ['de' => 'Papeterie', 'en' => 'Papeterie']);
$doc['category'] = 'luxury';
$doc['tags']     = ['creme', 'gold', 'papeterie'];
$doc['sort']     = 5;

/*
 * Die Stimme der Vorlage.
 *
 * Sie steht hier und nicht auf der Voreinstellung, weil das der Punkt ist:
 * die Voreinstellung IST der bisherige Stand, und eine Vorlage, die ihn nicht
 * verlaesst, sieht aus wie jede andere. Drei Entscheidungen:
 *
 *   Die Ueberschriften sind KLEINER als bisher und weiter gesperrt - auf
 *   Papeterie fuehrt eine Ueberschrift nicht, sie kuendigt an. Was fuehrt,
 *   ist die Angabe darunter.
 *
 *   Die grosse Zahl ist deutlich groesser (3.4 -> 6.4rem). Genau darum ging
 *   es in der Anfrage: "08 cok buyuk olabilir".
 *
 *   Der kleine Hinweis wird leiser und weiter - er soll gelesen werden
 *   koennen, ohne mitzureden.
 */
$doc['typo'] = [
    'title'    => ['size' => 96, 'tracking' => 30, 'below' => 220, 'caps' => true],
    'subtitle' => ['size' => 190, 'lineHeight' => 115, 'below' => 60],
    'number'   => ['size' => 640, 'lineHeight' => 92, 'below' => 40],
    'body'     => ['size' => 98, 'lineHeight' => 185, 'below' => 70],
    'small'    => ['size' => 80, 'tracking' => 8, 'lineHeight' => 165],
    'button'   => ['size' => 68, 'tracking' => 20, 'above' => 160],
];

/*
 * Die Strecke.
 *
 * Die Reihenfolge ist die Dramaturgie einer Einladung: erst WANN, dann wie
 * lange noch, dann WO, dann was passiert, dann wer dabei ist, dann was man
 * anzieht, dann die Bilder, dann ein Wort des Paares - und zuletzt die Frage.
 *
 * Und jede Station sieht anders aus. Das ist die ganze Uebung: keine zwei
 * aufeinanderfolgenden Abschnitte teilen Gestalt UND Rahmen.
 *
 *   Datum        grosse Zahl, ohne Rahmen        - eine Zahl braucht Luft
 *   Countdown    grosse Tageszahl, ohne Rahmen   - dieselbe Sprache, kleiner
 *   Ort          grosser Saalname + Kartenblatt  - eine Zeichnung, kein Text
 *   Ablauf       Kaertchen                       - viele kleine Kaesten
 *   Familien     nebeneinander, Haarlinie        - erster Rahmen, ganz leise
 *   Kleidung     Damen/Herren + Farbkreise, Papierkarte
 *   Bilder       Polaroids                       - schief, kein Raster mehr
 *   Wort         Editorial mit Initiale, durchscheinende Karte
 *   Zusage       Formular in Goldecken           - der lauteste Rahmen zuletzt
 *   Schluss      Haarstrich + Hinweis auf uns
 *
 * Die Luft wechselt mit: eng dort, wo zwei Angaben zusammengehoeren (Datum
 * und Countdown), weit vor jedem neuen Gedanken.
 */
$abschnitte = [
    ['id' => 'datum', 'type' => 'date', 'variant' => 'gross',
     'title' => ['de' => 'Wir heiraten am', 'en' => 'We marry on'],
     'settings' => ['spaceTop' => 'auto', 'space' => 'xs']],

    // Eng an das Datum, weil es dieselbe Angabe von der anderen Seite ist.
    ['id' => 'noch', 'type' => 'countdown', 'variant' => 'tage',
     'title' => ['de' => 'Noch', 'en' => 'Still'],
     'settings' => ['spaceTop' => 'xs', 'space' => 'l']],

    ['id' => 'ort', 'type' => 'location', 'variant' => 'gross',
     'title' => ['de' => 'Wo wir feiern', 'en' => 'Where we celebrate'],
     'settings' => ['karte' => 'blatt', 'map' => true, 'spaceTop' => 's', 'space' => 'l']],

    ['id' => 'ablauf', 'type' => 'program', 'variant' => 'karten',
     'title' => ['de' => 'Der Tag', 'en' => 'The day'],
     'settings' => ['spaceTop' => 's', 'space' => 'l']],

    ['id' => 'familien', 'type' => 'family', 'variant' => 'paar',
     'title' => ['de' => 'Unsere Familien', 'en' => 'Our families'],
     'settings' => ['frame' => 'linie', 'spaceTop' => 's', 'space' => 'l']],

    ['id' => 'kleidung', 'type' => 'dresscode', 'variant' => 'paar',
     'title' => ['de' => 'Dresscode', 'en' => 'Dress code'],
     'settings' => ['frame' => 'papier', 'spaceTop' => 's', 'space' => 'l'],
     /*
      * Voreinstellungen und keine festen Texte: schreibt das Paar etwas
      * anderes, gewinnt das Paar. Die Farben stehen hier als Vorschlag, weil
      * eine leere Palette nicht zeigt, wozu sie da ist.
      */
     'defaults' => [
         'code'   => 'Festlich',
         'note'   => 'Bitte keine weissen Kleider – die sind an diesem Tag vergeben.',
         'women'  => 'Langes oder midi Kleid',
         'men'    => 'Anzug, gern ohne Krawatte',
         'colors' => '#E8D8C3, #C9B79C, #7B8C7A, #B08D57',
     ]],

    ['id' => 'bilder', 'type' => 'gallery', 'variant' => 'default',
     'title' => ['de' => 'Wir zwei', 'en' => 'The two of us'],
     'settings' => ['photoFrame' => 'polaroid', 'spaceTop' => 's', 'space' => 'l']],

    ['id' => 'wort', 'type' => 'text', 'variant' => 'editorial',
     'title' => ['de' => 'Gut zu wissen', 'en' => 'Good to know'],
     'settings' => ['frame' => 'transparent', 'align' => 'left', 'spaceTop' => 's', 'space' => 'l']],

    // Der lauteste Rahmen zuletzt: hier soll jemand etwas TUN.
    ['id' => 'zusage', 'type' => 'rsvp', 'variant' => 'rahmen',
     'title' => ['de' => 'Kommt ihr?', 'en' => 'Are you coming?'],
     'settings' => ['frame' => 'gold', 'spaceTop' => 's', 'space' => 'm']],

    ['id' => 'schluss', 'type' => 'footer', 'variant' => 'linie',
     'title' => ['de' => '', 'en' => ''],
     'settings' => ['credit' => true, 'spaceTop' => 'xs', 'space' => 's'],
     'defaults' => ['text' => 'Wir freuen uns auf euch.']],
];

$doc['sections'] = array_map(
    // Das Paar darf jeden Abschnitt bearbeiten und keinen wegnehmen: die
    // Strecke ist die Aussage der Vorlage, der Inhalt gehoert dem Paar.
    static fn (array $a): array => $a + ['enabled' => true, 'permissions' => ['edit' => true]],
    $abschnitte
);

$doc = Design::complete($doc);

/* ------------------------------ Ausgabe ------------------------------ */

echo 'Papeterie: ', count($doc['sections']), " Abschnitte\n\n";

foreach ($doc['sections'] as $a) {
    printf(
        "  %-9s %-10s %-9s Rahmen %-11s Bild %-9s Luft %s/%s\n",
        $a['id'],
        $a['type'],
        $a['variant'],
        (string) ($a['settings']['frame'] ?? '-'),
        (string) ($a['settings']['photoFrame'] ?? '-'),
        (string) ($a['settings']['spaceTop'] ?? '-'),
        (string) ($a['settings']['space'] ?? '-')
    );
}

/*
 * Die Probe aufs Exempel, und sie steht hier und nicht in einem Test:
 * "keine zwei aufeinanderfolgenden Abschnitte sehen gleich aus" ist die
 * Aussage DIESER Vorlage und keine Regel des Motors. Ein Test im Ordner
 * tests/ waere eine Behauptung ueber jede Vorlage, die je entsteht.
 */
$vorher = null;
$gleich = [];
foreach ($doc['sections'] as $a) {
    $jetzt = $a['type'] . '/' . $a['variant'] . '/' . (string) ($a['settings']['frame'] ?? '');
    if ($jetzt === $vorher) {
        $gleich[] = $a['id'];
    }
    $vorher = $jetzt;
}

echo "\n", $gleich === []
    ? "  Keine zwei aufeinander folgenden Abschnitte teilen Gestalt und Rahmen.\n"
    : "  ACHTUNG, sieht aus wie der Vorgaenger: " . implode(', ', $gleich) . "\n";

if ($dry) {
    echo "\n--dry: nichts geschrieben.\n";
    exit;
}

Design::save($doc);

echo "\nGeschrieben als Entwurf. Ansehen: /de/admin/designs/papeterie\n";
echo "Veroeffentlichen im Panel - ungefragt stellt sich hier nichts ins Schaufenster.\n";
