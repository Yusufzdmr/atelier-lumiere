<?php
declare(strict_types=1);

/*
 * Jede Vorlage muss die Skripte ihrer Seite laden.
 *
 * Gefunden am 24.08.2026: der Design-Editor gibt seit jeher
 * 'scripts' => ['/assets/design-editor.js'] mit, und admin/layout.php hat es
 * nie gelesen. Das Skript wurde NIE geladen - die Vorschau reagierte auf
 * keine Eingabe, und im Markup stand trotzdem alles richtig. Ein Fehler, den
 * man nur sieht, wenn man die fertige Seite nach ihren <script>-Tags absucht.
 *
 * Ein Test am Text der Vorlage ist grob, und das ist hier der Punkt: die
 * Eigenschaft, die halten muss, ist nicht das Verhalten einer Funktion,
 * sondern dass eine Zeile in einer Datei ueberhaupt vorkommt.
 */

foreach (['layout.php', 'admin/layout.php'] as $vorlage) {
    $pfad = __DIR__ . '/../templates/' . $vorlage;
    $quelle = (string) file_get_contents($pfad);

    assert_contains($quelle, "\$meta['scripts']", $vorlage . ': liest die Skriptliste');
    assert_contains($quelle, '<script src="<?= e($script) ?>', $vorlage . ': und schreibt sie als Tag');
}

/*
 * Und die Seite muss sich selbst einrahmen duerfen.
 *
 * Gefunden am 24.08.2026 an der lebenden Seite: im Panel blieb jeder Rahmen
 * leer, der die Einladung zeigen sollte - Telefon, Tablet, Schreibtisch. Die
 * Richtlinie zaehlte unter frame-src vier Videodienste auf und die eigene
 * Adresse nicht.
 *
 * Leicht zu verwechseln, weil beide "self" sagen: frame-ancestors beantwortet
 * "wer darf UNS einrahmen", frame-src "wen duerfen WIR einrahmen". Nur die
 * zweite half.
 */

$http = (string) file_get_contents(__DIR__ . '/../src/Http.php');
$frames = substr($http, strpos($http, '$frames = ['), 700);

assert_contains($frames, "'self'", 'Http: die eigene Seite steht unter frame-src');

/*
 * Und der Film geht weich in die Karte ueber.
 *
 * Bis heute stand am Ende des Vorspanns eine Zeile: introBox.hidden = true.
 * Der Film war damit in einem Bild weg. Das war richtig, solange der Film
 * eigens dafuer gedreht war - beim Elysee-Film IST das letzte Bild das Blatt
 * der Karte, und ein Schnitt zwischen zwei gleichen Bildern sieht niemand.
 *
 * Ayhans Filme sind das nicht: ruhige Schleifen, die auf Blumen enden. Der
 * Schnitt zur Karte war zu sehen. Also blendet der Film aus, statt zu
 * verschwinden - die Karte liegt bei card=none ohnehin schon darunter, und
 * damit ist das Ausblenden selbst die Ueberblendung.
 *
 * Wieder ein Test am Dateitext, aus demselben Grund wie oben: die
 * Eigenschaft, die halten muss, ist, dass das Ausblenden ueberhaupt
 * stattfindet und die Dauer einen Namen hat.
 */

$js = (string) file_get_contents(__DIR__ . '/../public/assets/invitation.js');

assert_contains($js, 'UEBERGANG_MS', 'invitation.js: die Dauer der Ueberblendung hat einen Namen');
assert_contains($js, 'introBox.style.opacity', 'invitation.js: der Film blendet aus');
assert_contains($js, 'introBox.style.pointerEvents', 'invitation.js: und nimmt waehrenddessen keine Klicks mehr an');

/*
 * Und ein leeres Feld heisst wirklich "so lang wie der Film".
 *
 * Im Panel steht an der Dauer: leer = so lang wie der Film. Das stimmte
 * nicht. Ohne Zahl fiel der Deckel auf 6000 ms, und derselbe Deckel schnitt
 * den Film ab - ein Film von zehn Sekunden endete nach sechs, und das Feld
 * daneben behauptete das Gegenteil.
 *
 * Die 6000 waren als Notnagel gedacht und sind es weiterhin: laedt der Film
 * nicht, haengt die Einladung sonst vor einem schwarzen Kasten. Nur soll ein
 * Notnagel nicht auch dann greifen, wenn alles gut geht und die Laenge
 * bekannt ist.
 *
 * Die Obergrenze bleibt, und sie ist dieselbe wie im Panel: zwanzig Sekunden.
 * Ein versehentlich hochgeladener Film von einer Minute soll niemanden eine
 * Minute lang warten lassen.
 */

assert_contains($js, 'NOTFALL_MS', 'invitation.js: der Notnagel hat einen Namen');
assert_contains($js, 'GRENZE_MS', 'invitation.js: und die Obergrenze auch');
assert_contains($js, 'Math.min(dauer, GRENZE_MS)', 'invitation.js: ohne Zahl gilt die Laenge des Films');
assert_not_contains($js, 'introMs > 0 ? introMs : 6000',
    'invitation.js: der Notnagel ist nicht mehr der Deckel fuer jeden Film');
