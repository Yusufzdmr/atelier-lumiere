<?php
declare(strict_types=1);

use Atelier\OgImage;

/*
 * Das Vorschaubild - was in WhatsApp ueber dem Link steht.
 *
 * Die zweite Einladung hatte es nicht: og:description war leer, og:image
 * fehlte, und og:url zeigte auf den Wegweiser /de/einladung2 statt auf die
 * Einladung. Geteilt sah sie damit aus wie ein nackter Link - ausgerechnet
 * die Seite, die fast nur ueber WhatsApp weitergereicht wird.
 *
 * Die erste Fassung hatte das alles laengst. Statt es ein zweites Mal zu
 * bauen, nimmt OgImage jetzt Farben statt eines Themas entgegen; Zuschnitt,
 * Vignette, Rahmen und Cache sind fuer beide Fassungen dieselben.
 *
 * Hier steht, was ohne Bilddatei pruefbar ist: die Grenzen des Pfades. Ein
 * Bild wirklich zu bauen braucht GD und eine Datei auf der Platte - das
 * gehoert in einen Durchgang mit dem Browser, nicht in die Testsuite.
 */

assert_same(1200, OgImage::WIDTH, 'og: die Breite, die WhatsApp erwartet');
assert_same(630, OgImage::HEIGHT, 'og: und die Hoehe dazu');

/* --- Ohne Quelle kein Bild --- */

assert_same('', OgImage::forDocument('paar', '', '#faf7f2', '#b08d57'), 'og: ohne Foto keine Adresse');

/*
 * --- Was NICHT als Quelle durchgeht ---
 *
 * forDocument faellt auf die Originaladresse zurueck, wenn es kein Bild bauen
 * kann (kein GD, unlesbare Datei). Das ist gewollt: lieber das Foto selbst
 * als gar keine Vorschau. Ein Pfad ausserhalb des eigenen Hauses darf aber
 * auch da nicht landen - er stuende sonst als og:image auf der Einladung und
 * meldete jedem Gast, der sie in WhatsApp oeffnet, einen Besuch bei einem
 * fremden Server.
 */
foreach ([
    'http://example.com/foto.jpg',
    '//example.com/foto.jpg',
    '/etc/passwd',
    '/uploads/../../../etc/passwd',
] as $boese) {
    $ergebnis = OgImage::forDocument('paar', $boese, '#faf7f2', '#b08d57');
    assert_true(
        !str_contains($ergebnis, 'example.com') && !str_contains($ergebnis, 'passwd'),
        'og: "' . $boese . '" wird nicht zur Vorschau'
    );
}
