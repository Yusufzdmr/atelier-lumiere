<?php

declare(strict_types=1);

/*
 * Speichern nebenbei.
 *
 * "Kaydete basmak zorunda kalmayim, foto yukledeysem oto kaydetsin, yaziyi
 * degistirirken oto kaydetsin, ayarlarini falan, en son kaydete basinca yine
 * kaydetsin."
 *
 * Hier stand ein Nein, und es hatte einen Grund - der Editor ist EIN Formular
 * mit EINER Fassungsnummer, und eine automatische Sicherung ueberschriebe bei
 * zwei offenen Tabs die Arbeit des einen mit der des anderen.
 *
 * Der Grund faellt weg, wenn die Nummer mitwaechst: der Server antwortet mit
 * der neuen, dieser Tab uebernimmt sie, und ein zweiter haelt weiter seine
 * alte. Das Schloss bleibt, es bekommt nur einen Schluessel, der nachgezogen
 * wird. Genau das halten die Zeilen hier fest.
 */

$steuer  = (string) file_get_contents(__DIR__ . '/../src/Controllers/DesignAdminController.php');
$skript  = (string) file_get_contents(__DIR__ . '/../public/assets/design-editor.js');
$tafel   = (string) file_get_contents(__DIR__ . '/../templates/admin/design-edit.php');

/* --- Ein Weg, zwei Antworten --- */

/*
 * EIN Weg und nicht zwei: ein zweiter Endpunkt fuers Speichern nebenbei waere
 * eine zweite Stelle, an der Uploads, Startsaetze und die Fassungspruefung
 * stimmen muessen - und die zweite laeuft der ersten irgendwann davon.
 */
assert_contains($steuer, "\$nebenbei = (\$_POST['auto'] ?? '') === '1';",
    'Nebenbei: derselbe Weg, nur eine andere Antwort');
assert_contains($steuer, 'private function fertig(bool $nebenbei, string $ziel, array $antwort): never',
    'Nebenbei: der Schluss steht an einer Stelle');
assert_contains($steuer, "header('Content-Type: application/json; charset=utf-8');",
    'Nebenbei: das Skript bekommt JSON');
assert_contains($steuer, "header('Location: ' . \$ziel, true, 303);",
    'Von Hand: der Mensch bekommt weiterhin eine Umleitung');

/* --- Das Schloss bleibt --- */

assert_contains($steuer, "\$this->fertig(\$nebenbei, \$ziel . '?fehler=veraltet', [",
    'Schloss: eine fremde Aenderung faellt auch nebenbei auf');

/*
 * Die neue Nummer kommt aus der ZEILE und wird nicht gerechnet:
 * Design::save() zaehlt nur hoch, wenn sich wirklich etwas geaendert hat -
 * ein Speichern ohne Aenderung entwertet damit auch keinen zweiten Tab.
 */
assert_contains($steuer, '$frisch = Design::findById($doc[\'id\']);',
    'Schloss: die neue Nummer kommt aus der Zeile');
assert_contains($steuer, "\$antwort = ['ok' => true, 'version' => \$neueVersion];",
    'Schloss: und faehrt zurueck zum Tab');

/* --- Was das Skript tut --- */

assert_contains($skript, 'daten.append("auto", "1");', 'Skript: es sagt, dass es nebenbei speichert');
assert_contains($skript, 'if (ergebnis.version) versionsFeld.value = String(ergebnis.version);',
    'Skript: und zieht die Nummer nach');

/*
 * Bei "veraltet" wird ab da NICHTS mehr geschrieben - weder nebenbei noch
 * aus Versehen: was im Formular steht, wuerde die fremde Arbeit
 * ueberschreiben. Das Formular bleibt stehen, damit nichts Getipptes
 * verlorengeht.
 */
assert_contains($skript, 'gesperrt = true;', 'Skript: eine fremde Aenderung sperrt das Schreiben');
assert_contains($skript, 'if (gesperrt || laeuft || !schmutzig) return;',
    'Skript: gesperrt heisst gesperrt');

/*
 * Ohne Dateien. Dieselbe Datei bei jedem Halt im Tippen hochzuladen legte sie
 * jedes Mal neu auf die Platte - ein Blatt von drei Megabyte, alle
 * anderthalb Sekunden.
 */
assert_contains($skript, 'var daten = formularOhneDateien();', 'Skript: ohne Dateien');
assert_true(substr_count($skript, 'var formularOhneDateien = function ()') === 1,
    'Skript: und der Formularbauer steht nur einmal da');

/*
 * Eine Datei geht den normalen Weg, mit Neuladen: danach steht ein neuer
 * Pfad im Feld und ein neues Bild in der Vorschau, und das Dateifeld ist
 * leer - sonst reiste dieselbe Datei bei jedem weiteren Speichern mit.
 */
assert_contains($skript, 'if (feld && feld.type === "file" && feld.files && feld.files.length)',
    'Skript: eine gewaehlte Datei speichert sofort');
assert_contains($skript, 'form.submit();', 'Skript: und zwar auf dem normalen Weg');

/*
 * Der Knopf wartet, wenn gerade nebenbei gespeichert wird: sonst waeren zwei
 * Anfragen mit derselben Fassungsnummer unterwegs, und die zweite bekaeme
 * "veraltet" zu sehen, ohne dass jemand etwas falsch gemacht hat.
 */
assert_contains($skript, 'willAbschicken = true;',
    'Skript: der Knopf wartet auf die laufende Sicherung');
assert_contains($skript, 'if (willAbschicken) {', 'Skript: und geht danach von allein los');

// Wer den Tab wechselt, hat aufgehoert zu arbeiten - dann jetzt und nicht in
// anderthalb Sekunden.
assert_contains($skript, 'keepalive: true', 'Skript: beim Wegsehen sofort und ueber die Seite hinaus');

/* --- Und es steht dabei, was gerade passiert --- */

/*
 * Ohne diese Zeile waere das Speichern nebenbei die naechste stille Sache in
 * diesem Haus: es geschieht etwas, und niemand sagt es.
 */
assert_contains($tafel, 'data-stand', 'Panel: die Standzeile steht neben dem Knopf');

foreach (['geaendert', 'laeuft', 'fertig', 'veraltet', 'fehler'] as $wort) {
    assert_contains($tafel, 'data-wort-' . $wort . '="', 'Panel: das Wort fuer "' . $wort . '" steht da');
}

// Ein Nein, das nicht "veraltet" heisst, faellt trotzdem auf.
assert_contains($skript, 'melde("fehler", true);', 'Panel: und ein Nein wird rot');
