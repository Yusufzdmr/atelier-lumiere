<?php

declare(strict_types=1);

/*
 * Der Vorspann darf nichts verdecken, was er nicht ersetzt.
 *
 * Gemeldet von einem echten Gast auf einem echten iPhone: "davetiyeye
 * girince bembeyaz bir goeruentue geliyor - tiklayinca ortaya cikiyor".
 * Der Befund stand im Markup, nicht im Geraet.
 *
 * Der Filmkasten lag deckend ueber der ganzen Seite (fixed inset-0, z-40,
 * background var(--d-bg)) und das gezeichnete Kuvert wurde stattdessen GAR
 * NICHT gedruckt - die Bedingung dafuer war "$introFilm === ''". Darin lag
 * ein <video> ohne autoplay, und ein <video> ohne autoplay zeichnet auf iOS
 * sein erstes Bild nicht. Uebrig blieb eine leere Flaeche in der Grundfarbe
 * der Vorlage; bei einem cremefarbenen Thema (elysee: #EFE7DC) also eine
 * weisse Seite. Ohne Kuvert, ohne Siegel - und ohne den Satz "Tippen zum
 * Oeffnen", denn auch der stand im selben Zweig.
 *
 * Dazu sperrt invitation.js beim Laden das Scrollen. Der Gast konnte also
 * nicht einmal wegwischen, um zu sehen, ob darunter etwas ist.
 *
 * Die Umkehrung: das Kuvert wird IMMER gedruckt und traegt den Hinweis, der
 * Film liegt unsichtbar darueber und schiebt sich erst ins Bild, wenn er
 * laeuft. Damit gibt es keinen Zustand mehr, in dem der Gast eine leere
 * Flaeche ohne Aufforderung sieht.
 *
 * Was dabei NICHT zurueckkommen darf: zwei Kuverts hintereinander. Lief der
 * Film - der ein echtes Kuvert zeigt -, faellt das gezeichnete stumm weg,
 * statt danach noch einmal aufzuklappen.
 */

$buehne = (string) file_get_contents(__DIR__ . '/../templates/partials/design-stage.php');
$js     = (string) file_get_contents(__DIR__ . '/../public/assets/invitation.js');

/* --- Das gezeichnete Kuvert haengt nicht mehr am Fehlen des Films --- */

assert_true(
    !str_contains($buehne, "\$introFilm === ''"),
    'Buehne: Kuvert und Hinweis haengen nicht mehr daran, dass es KEINEN Film gibt'
);

assert_contains($buehne, 'data-envelope-open', 'Buehne: der Anklickpunkt steht da');
assert_contains($buehne, 'Tippen zum Öffnen', 'Buehne: und der Hinweis, was zu tun ist');
assert_contains($buehne, 'Tap to open', 'Buehne: auch auf Englisch');

/*
 * Genau einmal. Stuende der Hinweis zweimal im Markup - einmal fuer den Fall
 * mit Film, einmal ohne -, waeren es zwei Stellen, die auseinanderlaufen
 * koennen, und auf der Seite unter Umstaenden zwei Zeilen untereinander.
 */
// Gezaehlt wird das Markup und nicht das Wort: "data-envelope-open" steht
// auch im Kommentar darueber, der den Vertrag mit invitation.js beschreibt.
assert_same(1, substr_count($buehne, 'type="button" data-envelope-open'),
    'Buehne: es gibt genau einen Anklickpunkt');
assert_same(1, substr_count($buehne, 'Tippen zum Öffnen'),
    'Buehne: und genau einen Hinweis');

/* --- Der Filmkasten liegt unsichtbar da, bis er laeuft --- */

assert_contains($buehne, 'opacity:0',
    'Buehne: der Filmkasten faengt durchsichtig an, sonst verdeckt er das Kuvert');
assert_contains($buehne, 'pointer-events:none',
    'Buehne: und faengt den Finger nicht ab, solange er nichts zeigt');

/*
 * Kein autoplay, und das bleibt so: der Film soll erst nach einer Geste des
 * Gastes laufen. Das war nie der Fehler - der Fehler war, was daneben fehlte.
 *
 * Gelesen wird der <video>-Tag selbst und nicht die ganze Datei: das Wort
 * steht auch in den Kommentaren, und ein Test, der Prosa misst, faellt beim
 * naechsten umformulierten Absatz um.
 */
// Kein regulaerer Ausdruck ueber "[^>]": im Tag steht ein PHP-Echo, und
// dessen schliessende Klammer beendet die Suche mitten im Attribut.
$tag = strstr((string) strstr($buehne, '<video '), '</video>', true);

assert_true($tag !== false && $tag !== '', 'Buehne: der Filmknoten steht im Markup');
assert_true(
    !str_contains((string) $tag, 'autoplay'),
    'Buehne: der Film startet weiterhin erst auf Tippen, nicht von allein'
);
assert_contains((string) $tag, 'playsinline',
    'Buehne: und laeuft auf dem Telefon in der Seite, nicht im Vollbild');

/* --- Das Skript blendet ihn ein, wenn er wirklich laeuft --- */

assert_contains($js, '"playing"',
    'Skript: es wartet auf das Ereignis, das sagt "es ist etwas zu sehen"');
assert_contains($js, 'filmLief',
    'Skript: es merkt sich, ob der Film ueberhaupt lief');

/*
 * Und nur dann faellt das gezeichnete Kuvert stumm weg. Ohne diese Frage
 * saehe der Gast nach dem Film ein zweites Kuvert aufklappen - genau das,
 * was am 18. August einmal abgeschafft wurde.
 */
assert_contains($js, 'if (filmLief)',
    'Skript: lief der Film, klappt das gezeichnete Kuvert nicht noch einmal auf');
