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
