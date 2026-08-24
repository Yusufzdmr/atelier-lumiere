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

/*
 * Und keine Vorlage ruft eine Klasse, die sie nicht geholt hat.
 *
 * Gefunden am 24.08.2026 auf die harte Tour: design-edit-liste.php bekam
 * einen Aufruf von DesignSections::leer(), aber kein use dazu. php -l sagt
 * dazu nichts (die Klasse fehlt erst zur Laufzeit), die Testreihe auch nicht
 * (sie rendert keine Vorlagen) - erst die aufgerufene Seite stand als Fatal
 * error da, und zwar die GANZE Seite, nicht nur eine Zeile.
 *
 * Grob wie die Tests darueber, und aus demselben Grund: die Eigenschaft, die
 * halten muss, ist nicht das Verhalten einer Funktion, sondern dass eine
 * Zeile in einer Datei vorkommt. Geprueft wird nur, was es in src/ wirklich
 * gibt - ein DateTime:: oder PDO:: ist keine Sache dieses Tests.
 */

$klassen = [];
foreach (glob(__DIR__ . '/../src/*.php') ?: [] as $datei) {
    $klassen[basename($datei, '.php')] = true;
}

foreach (glob(__DIR__ . '/../templates/**/*.php') ?: [] as $vorlage) {
    $quelle = (string) file_get_contents($vorlage);
    $kurz   = 'templates/' . basename(dirname($vorlage)) . '/' . basename($vorlage);

    /*
     * Mit dem Zerleger und nicht mit einem Muster: "Design::" steht auch in
     * Kommentaren und in Fliesstext, und ein Test, der davon ausgeht, meldet
     * zwei Dutzend Stellen, an denen nichts kaputt ist. Der Zerleger sieht
     * nur, was PHP auch sieht.
     */
    $marken = token_get_all($quelle);
    $gerufen = [];
    foreach ($marken as $nr => $marke) {
        if (!is_array($marke) || $marke[0] !== T_STRING) {
            continue;
        }
        $naechste = $marken[$nr + 1] ?? null;
        if ($naechste === '::' || (is_array($naechste) && $naechste[0] === T_DOUBLE_COLON)) {
            $gerufen[$marke[1]] = true;
        }
    }

    foreach (array_keys($gerufen) as $name) {
        if (!isset($klassen[$name])) {
            continue;
        }
        // chr(92) statt eines Schraegstrichs im Text: der Weg durch die
        // Werkzeuge frisst ihn sonst, und der Test suchte dann nach
        // "use AtelierDesignSections;".
        $holt = 'use Atelier' . chr(92) . $name . ';';
        assert_contains($quelle, $holt, $kurz . ': holt ' . $name);
    }
}

/*
 * Und der Hinweis am Oeffnungsfilm sagt nicht mehr, was einmal galt.
 *
 * Dort stand "2-5 s, und am Ende auf dem geoeffneten Kuvert stehen bleiben".
 * Beides war richtig, solange der Film hart abgeschnitten wurde: sein letztes
 * Bild musste das Blatt der Karte sein, sonst sah man den Schnitt, und laenger
 * als sechs Sekunden lief er ohnehin nicht.
 *
 * Seit der Vorspann ausblendet und ein leeres Feld wirklich "so lang wie der
 * Film" heisst, stimmt keine der beiden Angaben mehr - und ein Hinweis, der
 * nicht stimmt, kostet mehr als keiner.
 */

$vorspann = (string) file_get_contents(__DIR__ . '/../templates/admin/design-edit-sections.php');

assert_not_contains($vorspann, '2–5 saniye', 'Vorspann-Hinweis: die alte Laengenangabe ist weg (tr)');
assert_not_contains($vorspann, '2-5 s,', 'Vorspann-Hinweis: und auf Deutsch auch');
assert_not_contains($vorspann, 'zarfın üzerinde bitmeli', 'Vorspann-Hinweis: das Schlussbild wird nicht mehr verlangt');
assert_contains($vorspann, '20 saniye', 'Vorspann-Hinweis: dafuer steht die echte Obergrenze da');
