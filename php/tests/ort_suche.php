<?php
declare(strict_types=1);

use Atelier\SectionRegistry;
use Atelier\StaticMap;

/*
 * Die Ortssuche und ihre Unterschrift.
 *
 * Der Kunde: "harita için konum girme falan müşteriye kolaylık sağlıcak şeyi
 * yap". Vorher tippte das Paar seine Anschrift in ein leeres Feld, und ob der
 * Kartendienst sie kennt, stellte sich erst auf der verschickten Einladung
 * heraus - dann naemlich, wenn keine Karte kam.
 *
 * Hier steht nur, was ohne Netz pruefbar ist: die Unterschrift, mit der das
 * Vorschaubild eine Koordinate als "von uns" erkennt, und die Grenzen der
 * Suche. Was Nominatim antwortet, gehoert nicht in einen Test - sonst haengt
 * die Testsuite an einem fremden Dienst und faellt um, wenn der es nicht tut.
 */

/* --- Zu kurz ist keine Suche, sondern eine Anfrage zu viel --- */

assert_same([], StaticMap::search(''), 'suche: leer fragt niemanden');
assert_same([], StaticMap::search('a'), 'suche: ein Zeichen fragt niemanden');
assert_same([], StaticMap::search('ab'), 'suche: zwei Zeichen auch nicht');

/* --- Die Unterschrift --- */

$sig = StaticMap::sign(48.2890852, 10.4616158);

assert_same(16, strlen($sig), 'unterschrift: sechzehn Zeichen');
assert_true(StaticMap::verify(48.2890852, 10.4616158, $sig), 'unterschrift: die eigene passt');
assert_true(!StaticMap::verify(48.2890852, 10.4616158, 'deadbeefdeadbeef'), 'unterschrift: eine erfundene nicht');
assert_true(!StaticMap::verify(48.2890852, 10.4616158, ''), 'unterschrift: eine fehlende nicht');

// Ein anderer Ort, eine andere Unterschrift - sonst waere sie keine.
assert_true(!StaticMap::verify(48.3, 10.4616158, $sig), 'unterschrift: gilt nicht fuer den Nachbarort');
assert_true(!StaticMap::verify(48.2890852, 10.5, $sig), 'unterschrift: auch nicht in die andere Richtung');

/*
 * Gerundet wird auf fuenf Stellen, und beide Seiten runden gleich.
 *
 * Das ist die Stelle, an der so etwas kaputtgeht: die Zahl geht als Text
 * durch eine Adresse und kommt als Gleitkommazahl zurueck. Waere die
 * Unterschrift ueber die ungerundete Zahl gebildet, passte sie danach nie
 * wieder - und das Vorschaubild waere dauerhaft ein 404, das niemand erklaeren
 * kann.
 */
assert_same(
    $sig,
    StaticMap::sign((float) '48.2890852', (float) '10.4616158'),
    'unterschrift: der Weg durch einen String aendert sie nicht'
);

// Unterhalb der fuenften Stelle ist es derselbe Ort - gut einen Meter.
assert_true(
    StaticMap::verify(48.28908521, 10.46161581, $sig),
    'unterschrift: ein Meter Unterschied ist kein anderer Ort'
);

/* --- Der Katalog weiss, welche Datei hinter einer Einstellung steckt --- */

$musik = SectionRegistry::settings('music');
assert_same('src', $musik['track']['type'], 'katalog: die Tonspur ist ein Pfad');
assert_same('audio', $musik['track']['kind'], 'katalog: und zwar ein Lied');

/*
 * 'kind' ist die Auskunft, mit der das Panel sein accept baut und der
 * Controller seine Pruefung waehlt (Media::storeAudio statt storeGraphic).
 * Eine src-Einstellung ohne sie bekaeme stillschweigend die Bildpruefung -
 * ein hochgeladenes Lied fiele durch, und niemand erfuehre warum.
 */
foreach (array_keys(SectionRegistry::all()) as $art) {
    foreach (SectionRegistry::settings($art) as $schluessel => $schema) {
        if ((string) $schema['type'] !== 'src') {
            continue;
        }
        assert_true(
            in_array((string) ($schema['kind'] ?? ''), ['audio', 'video', 'bild'], true),
            'katalog: ' . $art . '.' . $schluessel . ' sagt, was fuer eine Datei es ist'
        );
    }
}
