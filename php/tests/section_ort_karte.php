<?php
declare(strict_types=1);

use Atelier\Design;
use Atelier\DesignSections;
use Atelier\DesignWizard;
use Atelier\SectionRegistry;
use Atelier\StaticMap;

/*
 * Der Ort, der verschwand.
 *
 * Der Kunde hat drei Einladungen gebaut und danach gesagt: "bu düğün
 * Lokalisation olduğu yer bi acamadim". In allen drei Datensaetzen fehlten
 * venue und address vollstaendig - nicht leer, sondern gar nicht da.
 *
 * Der Weg dorthin ging ueber zwei Stellen, die einzeln beide richtig waren:
 *
 *   1. Der Assistent fragte nur nach Feldern, die die KARTE benutzt
 *      (DesignWizard::choices liest die bind-Namen der Ebenen). Die Vorlagen
 *      bild, film, video und 25aug zeigen die Adresse nicht auf dem Papier -
 *      also fragte er nie danach.
 *   2. DesignSections::hatInhalt warf den location-Abschnitt weg, wenn keine
 *      Adresse da war - wortlos, denn ein leerer Kasten waere schlimmer.
 *
 * Zusammen ergab das einen Abschnitt, den der Grafiker aufgestellt hatte, den
 * das Paar nie fuellen konnte und den niemand je zu sehen bekam.
 *
 * Diese Datei haelt beide Enden fest.
 */

/** Ein Dokument mit Abschnitten, aber ohne Adresse auf der Karte. */
function ort_doc(array $sections, array $layers = []): array
{
    return ['id' => 'probe', 'slug' => 'probe', 'layers' => $layers, 'sections' => $sections];
}

/* ------------------ 1. Der Assistent fragt, was der Abschnitt braucht ----- */

$stumm = ort_doc(
    [['id' => 'ort-1', 'type' => 'location'], ['id' => 'cd-1', 'type' => 'countdown']],
    // Die Karte zeigt nur die Namen - genau die Lage der Vorlage "bild".
    [['id' => 'n', 'type' => 'text', 'bind' => 'couple_names']]
);

$felder = DesignWizard::choices($stumm)['fields'];

assert_true(in_array('venue', $felder, true), 'choices: der location-Abschnitt verlangt den Ort');
assert_true(in_array('address', $felder, true), 'choices: der location-Abschnitt verlangt die Adresse');
assert_true(in_array('date', $felder, true), 'choices: der countdown-Abschnitt verlangt das Datum');

// Die Reihenfolge bleibt die des Formulars und nicht die des Fundorts.
assert_same(
    ['bride', 'groom', 'date', 'venue', 'address'],
    $felder,
    'choices: die Reihenfolge ist FIELD_ORDER'
);

// Ein abgeschalteter Abschnitt fragt nichts: das Paar fuellte sonst ein Feld,
// das visible() beim Drucken ohnehin wegwirft.
$aus = ort_doc(
    [['id' => 'ort-1', 'type' => 'location', 'enabled' => false]],
    [['id' => 'n', 'type' => 'text', 'bind' => 'couple_names']]
);
assert_same(
    ['bride', 'groom'],
    DesignWizard::choices($aus)['fields'],
    'choices: ein abgeschalteter Abschnitt verlangt nichts'
);

// Ohne Abschnitt bleibt alles wie vorher - die Karte allein bestimmt.
$ohne = ort_doc([], [['id' => 'n', 'type' => 'text', 'bind' => 'couple_names']]);
assert_same(['bride', 'groom'], DesignWizard::choices($ohne)['fields'], 'choices: ohne Abschnitte zaehlt nur die Karte');

assert_same(['venue', 'address'], SectionRegistry::needs('location'), 'needs: location');
assert_same(['date'], SectionRegistry::needs('countdown'), 'needs: countdown');
assert_same([], SectionRegistry::needs('rsvp'), 'needs: rsvp braucht keine feste Angabe');

/* ------------------------ 2. Der Abschnitt erscheint --------------------- */

$doc = ort_doc([['id' => 'ort-1', 'type' => 'location', 'title' => ['de' => 'Ort', 'en' => 'Place']]]);

$nurSaal = DesignSections::html($doc, ['venue' => 'Villa Sonnenhof', 'slug' => 'paar'], 'de');
assert_contains($nurSaal, 'Villa Sonnenhof', 'ort: der Saalname allein traegt den Abschnitt');
assert_not_contains($nurSaal, 'd-sec-address', 'ort: ohne Adresse kein leerer Adressabsatz');
assert_not_contains($nurSaal, 'd-sec-map', 'ort: ohne Adresse weder Karte noch Route');

$leer = DesignSections::html($doc, ['slug' => 'paar'], 'de');
assert_same('', $leer, 'ort: ohne Saal und ohne Adresse gibt es den Abschnitt nicht');

/* ---------------------------- 3. Das Kartenbild -------------------------- */

$voll = DesignSections::html(
    $doc,
    ['venue' => 'Villa Sonnenhof', 'address' => 'Seestrasse 4, 88131 Lindau', 'slug' => 'elif-kerem'],
    'de'
);

assert_contains($voll, '/de/v2/einladung/elif-kerem/karte.png', 'karte: das Bild haengt am Slug');
assert_contains($voll, 'loading="lazy"', 'karte: das Bild laedt erst beim Hinsehen');
assert_contains($voll, 'd-sec-map-blatt', 'karte: die Blattform ist die Voreinstellung');
assert_not_contains($voll, '<iframe', 'karte: kein iframe - der Gast spricht nur mit uns');
assert_not_contains($voll, 'maps.googleapis.com', 'karte: kein direkter Aufruf aus der Seite');
assert_contains($voll, 'https://www.google.com/maps/dir/', 'karte: der Klick fuehrt zur Route');

// Abschaltbar - und dann bleibt der Ort trotzdem stehen.
$ohneBild = DesignSections::html(
    ['id' => 'probe', 'slug' => 'probe', 'sections' => [
        ['id' => 'ort-1', 'type' => 'location', 'settings' => ['karte' => 'aus']],
    ]],
    ['venue' => 'Villa Sonnenhof', 'address' => 'Seestrasse 4, 88131 Lindau', 'slug' => 'elif-kerem'],
    'de'
);
assert_not_contains($ohneBild, 'karte.png', 'karte: abgeschaltet erscheint kein Bild');
assert_contains($ohneBild, 'Seestrasse 4', 'karte: abgeschaltet steht die Adresse trotzdem da');

// Ohne Slug gibt es keine gespeicherte Einladung - ausser bei der
// Beispieladresse, fuer die das Schaufenster einen eigenen Endpunkt hat.
$vorschauFremd = DesignSections::html($doc, ['address' => 'Irgendwo 1, Nirgendwo'], 'de');
assert_not_contains($vorschauFremd, 'karte.png', 'karte: ohne Slug kein Bild zu einer fremden Adresse');

$vorschauDemo = DesignSections::html($doc, ['address' => StaticMap::DEMO_ADDRESS], 'de');
assert_contains($vorschauDemo, '/de/v2/karte-beispiel.png', 'karte: das Schaufenster zeigt das Beispielbild');

/* ----------------------------- 4. Die Uhr -------------------------------- */

$uhrDoc = ort_doc([['id' => 'cd-1', 'type' => 'countdown', 'variant' => 'uhr']]);
$uhr = DesignSections::html($uhrDoc, ['date' => '2027-06-19', 'time' => '17:30'], 'de');

assert_contains($uhr, 'data-countdown="2027-06-19T17:30"', 'uhr: die Uhrzeit reist mit');
foreach (['days', 'hours', 'minutes', 'seconds'] as $feld) {
    assert_contains($uhr, 'data-countdown-' . $feld, 'uhr: das Feld ' . $feld . ' steht da');
}
assert_contains($uhr, 'Sekunden', 'uhr: die Woerter kommen vom Server, nicht aus dem Skript');
assert_contains($uhr, '19. Juni 2027', 'uhr: ohne Skript traegt das gedruckte Datum den Abschnitt');

// Ohne Uhrzeit faengt der Tag um Mitternacht an - nicht irgendwann.
$ohneZeit = DesignSections::html($uhrDoc, ['date' => '2027-06-19'], 'de');
assert_contains($ohneZeit, 'data-countdown="2027-06-19T00:00"', 'uhr: ohne Uhrzeit zaehlt sie bis Mitternacht');

// Die ruhige Gestalt bleibt, was sie war: eine Zahl, kein Wecker.
$zahl = DesignSections::html(ort_doc([['id' => 'cd-1', 'type' => 'countdown']]), ['date' => '2027-06-19'], 'de');
assert_contains($zahl, 'data-countdown-days', 'countdown: die Voreinstellung zaehlt Tage');
assert_not_contains($zahl, 'data-countdown-seconds', 'countdown: die Voreinstellung zaehlt keine Sekunden');

assert_true(SectionRegistry::isVariant('countdown', 'uhr'), 'katalog: die Uhr steht im Katalog');
assert_contains(
    DesignSections::css($uhrDoc, '.d-probe'),
    '.d-sec-v-uhr .d-sec-uhr{',
    'uhr: die Variante bringt ihren Stil mit'
);

/* ------------------ 6. Das Ziel der Route ist der SAAL ------------------- */

/*
 * Gemeldet: "adres secilirken dogru adres bulunuyor fakat navigasyona
 * gecildiginde sadece sehir kullaniliyor."
 *
 * Auf dem Demoserver nachgesehen, und dort stand es wortwoertlich so:
 *
 *     venue   = Imza Event Center
 *     address = Thannhausen, Landkreis Günzburg, Bayern, 86470, Deutschland
 *
 * Keine Strasse. Das ist kein Speicherfehler - das Verzeichnis kennt zu
 * diesem Ort keine, und StaticMap::search nimmt den Namen aus der Anschrift
 * heraus (er steht ja gleich daneben im Feld "Saal"). Uebrig bleibt die
 * Stadt.
 *
 * Der Fehler war, die Route allein aus der ANSCHRIFT zu bauen. Der Saalname
 * ist der genaueste Teil der Angabe, und Google findet "Imza Event Center,
 * Thannhausen" ohne Weiteres - "Thannhausen, Bayern, Deutschland" ist
 * dagegen ein Ortsschild.
 *
 * Gedruckt bleibt beides getrennt: der Saal gross, die Anschrift klein
 * darunter. Zusammengesetzt wird nur das Ziel.
 */

$ziel = DesignSections::routenZiel([
    'venue'   => 'Imza Event Center',
    'address' => 'Thannhausen, Landkreis Günzburg, Bayern, 86470, Deutschland',
]);
assert_same(
    'Imza Event Center, Thannhausen, Landkreis Günzburg, Bayern, 86470, Deutschland',
    $ziel,
    'ziel: der Saalname fuehrt die Anschrift an'
);

// Steht er schon vorn, kommt er nicht zweimal - manche Verzeichniseintraege
// tragen ihn selbst, und "Villa Sonnenhof, Villa Sonnenhof, ..." findet
// niemand.
assert_same(
    'Villa Sonnenhof, Seestrasse 4, 88131 Lindau',
    DesignSections::routenZiel([
        'venue'   => 'Villa Sonnenhof',
        'address' => 'Villa Sonnenhof, Seestrasse 4, 88131 Lindau',
    ]),
    'ziel: ein schon vorangestellter Saalname wird nicht verdoppelt'
);

// Gross- und Kleinschreibung entscheidet das nicht.
assert_same(
    'imza event center, Thannhausen',
    DesignSections::routenZiel(['venue' => 'Imza Event Center', 'address' => 'imza event center, Thannhausen']),
    'ziel: der Vergleich sieht ueber die Schreibweise hinweg'
);

// Nur eines von beiden: dann ist dieses eine das Ziel.
assert_same('Seestrasse 4, 88131 Lindau',
    DesignSections::routenZiel(['address' => 'Seestrasse 4, 88131 Lindau']),
    'ziel: ohne Saal traegt die Anschrift allein');
assert_same('', DesignSections::routenZiel(['venue' => 'Villa Sonnenhof']),
    'ziel: ohne Anschrift gibt es keine Route - der Saalname allein ist kein Ziel');
assert_same('', DesignSections::routenZiel([]), 'ziel: ohne alles nichts');

/* --- Und im Markup steht dasselbe --- */

$route = DesignSections::html(
    $doc,
    ['venue' => 'Imza Event Center',
     'address' => 'Thannhausen, Landkreis Günzburg, Bayern, 86470, Deutschland',
     'slug' => 'medine-ayhan'],
    'de'
);

assert_contains($route, rawurlencode('Imza Event Center, Thannhausen'),
    'ort: die Route zielt auf den Saal und nicht auf das Ortsschild');

// Gedruckt bleiben die beiden Zeilen getrennt - das Ziel ist eine Adresse
// fuer Google, keine fuer den Leser.
assert_contains($route, '<p class="d-sec-venue">Imza Event Center</p>',
    'ort: der Saalname steht weiterhin allein in seiner Zeile');
assert_contains($route, '<p class="d-sec-address">Thannhausen',
    'ort: und die Anschrift in ihrer');

/* --------------- 7. Der gewaehlte Punkt schlaegt den Text ---------------- */

/*
 * Der zweite Anlauf auf dieselbe Beschwerde.
 *
 * Beim ersten stand der Saalname noch nicht im Ziel. Seit er drinsteht, ist
 * es besser - und immer noch nicht genau: zu "Imza Event Center" kennt das
 * Verzeichnis GAR KEINE Strasse, und ein Ziel aus Text kann nie genauer
 * werden als der Text. Gemeldet: "navigasyon beni gercek adrese degil,
 * sehrin icerisinde baska bir noktaya goturuyor."
 *
 * Die Koordinaten stehen in derselben Antwort, aus der auch die Anschrift
 * kommt. Sie treffen den Punkt, egal was in der Zeile steht.
 */
assert_same('', DesignSections::routenPunkt([]), 'Punkt: ohne Koordinaten keiner');
assert_same('', DesignSections::routenPunkt(['address' => 'Seestrasse 4']),
    'Punkt: eine getippte Adresse bringt keinen mit');

/*
 * Null/Null ist kein Ort, sondern ein fehlender Wert - der Punkt liegt im
 * Atlantik vor Afrika, und dorthin soll niemand navigieren.
 */
assert_same('', DesignSections::routenPunkt(['lat' => 0, 'lng' => 0]),
    'Punkt: 0/0 ist kein Ort');

assert_same('48.4561,10.2717', DesignSections::routenPunkt(['lat' => 48.4561, 'lng' => 10.2717]),
    'Punkt: Breite und Laenge, durch Komma');

/* --- Im Markup: der Punkt gewinnt, der Text bleibt der Ersatz --- */

$mitPunkt = DesignSections::html(
    $doc,
    ['venue' => 'Imza Event Center',
     'address' => 'Thannhausen, Landkreis Günzburg, Bayern, 86470, Deutschland',
     'lat' => 48.2789, 'lng' => 10.4092, 'slug' => 'medine-ayhan'],
    'de'
);

assert_contains($mitPunkt, rawurlencode('48.2789,10.4092'),
    'Punkt: die Route zielt auf die Koordinate');
assert_true(!str_contains($mitPunkt, rawurlencode('Imza Event Center, Thannhausen')),
    'Punkt: und nicht mehr auf den Text daneben');

// Gedruckt bleibt der Text: der Gast liest eine Anschrift, keine Zahlen.
assert_contains($mitPunkt, '<p class="d-sec-venue">Imza Event Center</p>',
    'Punkt: der Saalname steht weiterhin da');
assert_contains($mitPunkt, '<p class="d-sec-address">Thannhausen', 'Punkt: und die Anschrift auch');

// Ohne Punkt traegt der Text weiter - ein Paar, das von Hand tippt, soll
// nicht schlechter dastehen als vorher.
$ohnePunkt = DesignSections::html(
    $doc,
    ['venue' => 'Imza Event Center', 'address' => 'Thannhausen, Bayern', 'slug' => 'p'],
    'de'
);
assert_contains($ohnePunkt, rawurlencode('Imza Event Center, Thannhausen'),
    'Punkt: ohne ihn bleibt es beim Text');

/*
 * Und die Signatur ist der Grund, warum das nicht einfach zwei Zahlen im
 * Formular sind: ohne sie waere es ein Weg, beliebige Koordinaten in eine
 * fremde Einladung zu schreiben - und die Karte zeichnet, wohin man sie
 * schickt.
 */
$sig = Atelier\StaticMap::sign(48.2789, 10.4092);
assert_true(Atelier\StaticMap::verify(48.2789, 10.4092, $sig), 'Punkt: die eigene Signatur gilt');
assert_true(!Atelier\StaticMap::verify(48.9999, 10.4092, $sig),
    'Punkt: sie gilt nicht fuer eine andere Koordinate');

$formular = (string) file_get_contents(__DIR__ . '/../templates/partials/angaben-felder.php');
$skript   = (string) file_get_contents(__DIR__ . '/../public/assets/invite-v2.js');
$steuer   = (string) file_get_contents(__DIR__ . '/../src/Controllers/InviteV2Controller.php');

assert_contains($formular, 'name="ort_sig"', 'Punkt: das Formular schickt die Signatur mit');
assert_contains($skript, 'punktVergessen', 'Punkt: wer weitertippt, verlaesst den gewaehlten Punkt');
assert_contains($steuer, 'StaticMap::verify($lat, $lng, $sig)',
    'Punkt: der Server prueft sie, bevor er sie speichert');

/* --------- 8. Hoehe des Abschnitts, Groesse und Film der Karte ---------- */

/*
 * "Her bolumun yuksekligi ayarlanabilmeli … bir bolumden digerine gecerken
 * cok buyuk bosluklar olusmaz."
 *
 * Eine MINDESThoehe: was drinsteht, darf immer groesser werden. Eine feste
 * Hoehe waere eine Zusage, die der Inhalt jederzeit bricht.
 */
$hoch = DesignSections::complete(ort_doc([
    ['id' => 'ort-1', 'type' => 'location', 'settings' => ['height' => 'voll']],
    ['id' => 'txt-1', 'type' => 'text'],
]));
$hochCss = DesignSections::css($hoch, '.d-x');

assert_contains($hochCss, 'min-height:100dvh;', 'Hoehe: "voll" ist eine Bildschirmhoehe');

/*
 * Und die Zentrierung dazu. Ohne sie waere die Hoehe nur Luft UNTEN - also
 * genau das, worueber die Beschwerde ging.
 */
assert_contains($hochCss, 'display:flex;flex-direction:column;justify-content:center;',
    'Hoehe: der Inhalt steht in der Mitte, nicht oben');

// "auto" ist kein Wert, sondern die Abwesenheit eines Werts: kein flex, keine
// Hoehe. Ein Abschnitt ohne Angabe soll sich nicht anders verhalten.
$txtRegel = strstr($hochCss, '.d-x .d-sec-txt-1{');
assert_true($txtRegel === false || !str_contains((string) $txtRegel, 'min-height'),
    'Hoehe: ohne Angabe keine Hoehe und kein flex');

/* --- Die Groesse der Karte --- */

/*
 * "Haritanin boyunu kucultmeli mesela." Bis hierher stand sie auf 22rem, fuer
 * jede Vorlage gleich - auf einer kompakten Einladung der groesste Kasten
 * weit und breit.
 */
$klein = DesignSections::html(
    ort_doc([['id' => 'ort-1', 'type' => 'location', 'settings' => ['mapSize' => 's']]]),
    ['venue' => 'Villa Sonnenhof', 'address' => 'Seestrasse 4, 88131 Lindau', 'slug' => 'p'],
    'de'
);
assert_contains($klein, 'd-sec-map-gr-s', 'Karte: die Groesse steht als Klasse am Bild');

// Die mittlere ist der bisherige Stand - eine Vorlage, die nichts sagt, sieht
// aus wie vorher.
assert_contains($voll, 'd-sec-map-gr-m', 'Karte: ohne Angabe die bisherige Groesse');
assert_contains($hochCss, '.d-x .d-sec-map-gr-m{max-width:min(100%,22rem);}',
    'Karte: und die ist 22rem, wie bisher');

/*
 * Die Groessenregel steht NACH der Grundregel im Stilblock. Beide sind eine
 * Klasse tief; bei gleicher Genauigkeit gewinnt die spaetere. Stuende sie
 * davor, waere jede Groesse ausser der mittleren wirkungslos - und zwar
 * unsichtbar.
 */
assert_true(
    strpos($hochCss, '.d-x .d-sec-map-gr-s{') > strpos($hochCss, '.d-x .d-sec-map-bild{'),
    'Karte: die Groesse steht nach der Grundregel, sonst greift sie nicht'
);

/* --- Ein Film als Karte --- */

$filmDoc = ort_doc([['id' => 'ort-1', 'type' => 'location',
    'settings' => ['karte' => 'eigen', 'mapVideo' => '/uploads/designs/karte.webm']]]);
$filmHtml = DesignSections::html($filmDoc, ['venue' => 'V', 'address' => 'Seestrasse 4', 'slug' => 'p'], 'de');

assert_contains($filmHtml, '<video src="/uploads/designs/karte.webm"', 'Karte: der Film wird gedruckt');
assert_contains($filmHtml, 'autoplay muted loop playsinline', 'Karte: er laeuft von allein und stumm');
assert_contains($filmHtml, 'https://www.google.com/maps/dir/',
    'Karte: die Navigation bleibt - sie haengt an der Adresse, nicht am Bild');

/*
 * Der Film gewinnt gegen das Bild, wenn beide hinterlegt sind: wer einen
 * hochlaedt, hat sich fuer ihn entschieden. Das Bild bleibt liegen.
 */
$beides = ort_doc([['id' => 'ort-1', 'type' => 'location', 'settings' => [
    'karte' => 'eigen', 'mapSrc' => '/uploads/designs/k.png', 'mapVideo' => '/uploads/designs/k.webm',
]]]);
$beidesHtml = DesignSections::html($beides, ['venue' => 'V', 'address' => 'Seestrasse 4', 'slug' => 'p'], 'de');
assert_contains($beidesHtml, 'k.webm', 'Karte: der Film gewinnt');
assert_true(!str_contains($beidesHtml, 'k.png'), 'Karte: das Bild tritt zurueck');

// Eine fremde Adresse kommt auch beim Film nicht durch.
$fremdFilm = ort_doc([['id' => 'ort-1', 'type' => 'location',
    'settings' => ['karte' => 'eigen', 'mapVideo' => 'https://fremd.example/k.webm']]]);
assert_true(
    !str_contains(DesignSections::html($fremdFilm, ['venue' => 'V', 'address' => 'S 4', 'slug' => 'p'], 'de'), 'fremd.example'),
    'Karte: eine fremde Filmadresse faellt weg'
);

/* --- Eine hochgeladene Karte IST die Entscheidung fuer sie --- */

/*
 * "Harita kismina resim atim." - "Olmadi."
 *
 * Das eigene Kartenbild wirkt nur, wenn die Auswahl daneben auf "eigen"
 * steht. Wer die Datei hinlegt und die Auswahl nicht anfasst, sah danach
 * genau dasselbe wie vorher: die gerechnete Karte. Kein Fehler, kein
 * Hinweis - der schlimmste Fall in diesem Haus.
 *
 * Also entscheidet die Datei. Dieselbe Regel wie beim Film, der gegen das
 * Bild gewinnt: wer etwas hochlaedt, hat sich dafuer entschieden.
 */
$vorlage = Design::complete([
    'id' => 'p', 'slug' => 'p',
    'sections' => [['id' => 'ort-1', 'type' => 'location', 'settings' => ['karte' => 'blatt']]],
]);

$formular = [
    'sections_da' => '1', 'sec_reihe' => '0',
    'sec_id_0' => 'ort-1', 'sec_type_0' => 'location', 'sec_variant_0' => 'default',
    'sec_on_0' => '1', 'sec_set_karte_0' => 'blatt',
    'sec_set_mapSrc_0' => '/uploads/designs/handgezeichnet.png',
];

$mitBild = Design::fromPost($vorlage, $formular);
assert_same('eigen', $mitBild['sections'][0]['settings']['karte'],
    'Karte: eine neu hochgeladene Zeichnung schaltet die Auswahl selbst um');
assert_contains(
    DesignSections::html($mitBild, ['venue' => 'V', 'address' => 'Seestrasse 4', 'slug' => 'p'], 'de'),
    'handgezeichnet.png',
    'Karte: und sie ist danach auch zu sehen'
);

/*
 * NUR wenn der Wert neu ist. Sonst liesse sich nie wieder mit "blatt"
 * speichern, solange irgendwo eine alte Zeichnung liegt - und das waere
 * derselbe Fehler noch einmal, nur andersherum: ein Feld, das nicht tut, was
 * dasteht.
 */
$zurueck = Design::fromPost($mitBild, $formular);
assert_same('blatt', $zurueck['sections'][0]['settings']['karte'],
    'Karte: ein Wert, der schon dastand, entscheidet nichts mehr');

// Und derselbe Weg fuer den Film.
$mitFilm = Design::fromPost($vorlage, ['sections_da' => '1', 'sec_reihe' => '0',
    'sec_id_0' => 'ort-1', 'sec_type_0' => 'location', 'sec_variant_0' => 'default',
    'sec_on_0' => '1', 'sec_set_karte_0' => 'rechteck',
    'sec_set_mapVideo_0' => '/uploads/designs/karte.webm']);
assert_same('eigen', $mitFilm['sections'][0]['settings']['karte'],
    'Karte: ein neuer Film ebenso');

/* --- Und eine abgelehnte Datei schweigt nicht mehr --- */

/*
 * Bis hierher stand an jeder Upload-Stelle "if ($pfad !== null)", und der
 * andere Fall war eine leere Zeile. Eine HEIC vom iPhone oder ein Bild ueber
 * sechs Megabyte verschwand wortlos.
 */
$steuer = (string) file_get_contents(__DIR__ . '/../src/Controllers/DesignAdminController.php');
$medien = (string) file_get_contents(__DIR__ . '/../src/Media.php');

/*
 * Der Sammler steht in Media und nicht in den Steuerungen: durch diese Tuer
 * geht jeder Upload des Hauses, und zwei Steuerungen mit derselben kleinen
 * Liste waeren zwei Orte, an denen sie kaputtgehen kann.
 */
assert_contains($medien, 'public static function nimm(array $datei, string $art, callable $pruefung)',
    'Upload: eine Stelle, die sich das Nein merkt');
assert_contains($medien, 'if ($fehler !== UPLOAD_ERR_NO_FILE)',
    'Upload: ein leeres Dateifeld ist keine Ablehnung');
assert_contains($steuer, "\$ziel .= '&abgelehnt=' . rawurlencode(",
    'Upload: und sie kommt beim Menschen an');
assert_true(!str_contains($steuer, "Media::storeGraphic(\$file, 'designs');"),
    'Upload: keine Stelle prueft mehr an der Meldung vorbei');

$tafel = (string) file_get_contents(__DIR__ . '/../templates/admin/design-edit.php');
assert_contains($tafel, "\$_GET['abgelehnt']", 'Panel: die Meldung steht im Editor');
assert_contains($tafel, 'HEIC', 'Panel: und sie nennt den haeufigsten Grund beim Namen');

/*
 * Und dieselbe Auskunft im Editor des PAARES.
 *
 * Dort laedt eine Braut ihre Fotos hoch, und genau dort kommt eine HEIC vom
 * iPhone am haeufigsten an. Bis hierher sah sie "Gespeichert" und danach
 * dieselbe Karte wie vorher.
 */
$v2 = (string) file_get_contents(__DIR__ . '/../src/Controllers/InviteV2Controller.php');
assert_contains($v2, "Media::nimm(\$datei, 'bild'", 'Paar: die Fotos gehen durch dieselbe Tuer');
assert_contains($v2, "\$ziel .= '&abgelehnt=' . rawurlencode(", 'Paar: und ein Nein faehrt mit');
assert_true(!str_contains($v2, "Media::store(\$datei, 'einladungen/v2/'"),
    'Paar: keine Stelle prueft mehr an der Meldung vorbei');

$v2Tafel = (string) file_get_contents(__DIR__ . '/../templates/pages/invite-v2-edit.php');
assert_contains($v2Tafel, "\$t('fileRejected')", 'Paar: die Meldung steht auf der Seite');

$woerter = require __DIR__ . '/../data/dict.php';
foreach (['de', 'en', 'tr'] as $sprache) {
    foreach (['fileRejected', 'fileRejectedWhy'] as $wort) {
        assert_true(($woerter[$sprache]['invitation2'][$wort] ?? '') !== '',
            'Paar: ' . $sprache . '.invitation2.' . $wort . ' steht da');
    }
    assert_contains((string) $woerter[$sprache]['invitation2']['fileRejectedWhy'], 'HEIC',
        'Paar: und sie nennt den haeufigsten Grund beim Namen (' . $sprache . ')');
}

/*
 * Und der Satz richtet sich nach der Art: ein Lied wird anders abgelehnt als
 * ein Foto, und ein Satz ueber JPG hilft niemandem, der gerade eine Tondatei
 * gewaehlt hat.
 */
assert_contains($v2, "'&art=' . rawurlencode((string) \$abgelehnt[0]['art'])",
    'Paar: die Art faehrt mit');
assert_contains($v2Tafel, "\$abgelehntArt === 'audio' ? 'fileRejectedWhyAudio' : 'fileRejectedWhy'",
    'Paar: und die Seite waehlt danach ihren Satz');

foreach (['de', 'en', 'tr'] as $sprache) {
    assert_true(($woerter[$sprache]['invitation2']['fileRejectedWhyAudio'] ?? '') !== '',
        'Paar: der Tonsatz steht in ' . $sprache);
}
