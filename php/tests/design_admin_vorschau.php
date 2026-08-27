<?php
declare(strict_types=1);

/*
 * Was hochgeladen ist, muss man sehen.
 *
 * Vier Medienfelder stehen im Editor untereinander - Film, Standbild des
 * Films, Blatt der Abschnitte, Blatt des Schlusses. Zwei davon zeigten, was
 * hinterlegt ist, zwei nicht: "bazilarinda yukledigin halde neyin yuklu
 * oldugu gorulmuyor". Ein Pfad ist keine Auskunft - "bild-2.webp" erkennt
 * niemand wieder, und nachsehen hiess: die Einladung aufmachen.
 *
 * Der Test haelt die Kaesten und ihre Paarung mit dem Dateifeld. Die
 * Vorschau vor dem Speichern haengt an genau dieser Paarung: das Skript
 * sucht zum Dateifeld den Kasten ueber data-vorschau-fuer, und ohne den
 * Namen dazwischen findet es nichts - lautlos, wie immer bei einer Naht
 * zwischen Vorlage und Skript.
 */

$abschnitte = (string) file_get_contents(__DIR__ . '/../templates/admin/design-edit-sections.php');
$tafeln     = (string) file_get_contents(__DIR__ . '/../templates/admin/design-edit-tafeln.php');
$js         = (string) file_get_contents(__DIR__ . '/../public/assets/design-editor.js');

/* --- Die vier Medienfelder tragen einen Kasten --------------------------- */

foreach ([
    'bild_'                => 'die Bildebenen',
    'intro_datei'          => 'der Film',
    'intro_poster_datei'   => 'das Standbild des Films',
    'sectionsbg_datei'     => 'das Blatt der Abschnitte',
    'sectionsbg_end_datei' => 'das Blatt des Schlusses',
] as $feld => $was) {
    assert_contains($abschnitte, 'data-vorschau-fuer="' . $feld,
        'Editor: ' . $was . ' hat einen Vorschaukasten');
}

/* --- Und das Skript kennt die Paarung ------------------------------------ */

assert_contains($js, 'data-vorschau-fuer', 'Skript: findet den Kasten ueber den Namen des Dateifeldes');
assert_contains($js, 'createObjectURL', 'Skript: zeigt die gewaehlte Datei, bevor gespeichert wird');
assert_contains($js, 'revokeObjectURL', 'Skript: und gibt sie wieder frei');

/*
 * Die Tonspur hat ihren Spieler schon - er stand nur nie da, wenn noch nichts
 * hinterlegt war, und genau dann sucht man ihn. Jetzt gehoert er zum Feld und
 * nicht zum Wert: leer bleibt er stumm, gefuellt spielt er.
 */
assert_contains($tafeln, 'data-tonvorschau', 'Panel: die Tonspur hat einen Spieler am Feld');

/* --- Eine Vorlage wieder loswerden --------------------------------------- */

/*
 * Bis heute wuchs die Liste nur. Es gab "aktiv/inaktiv" und "kopieren", aber
 * keinen Weg zurueck: "oluşturuldukça hep liste büyüyor ama dizaynları
 * silebilmek de gerekiyor". Wer ausprobiert, legt Probevorlagen an - und auf
 * dem Server standen davon schon vier neben den echten.
 *
 * Geloescht wird endgueltig und mit Rueckfrage, und die Rueckfrage NENNT die
 * Zahl der Einladungen, die daran haengen. Sie halten es aus - jede traegt
 * eine eingefrorene Kopie der Vorlage (design_snapshot) und wird weiter
 * angezeigt -, aber sie lassen sich danach nicht mehr auffrischen, und das
 * muss dastehen, bevor jemand drueckt.
 */
$liste   = (string) file_get_contents(__DIR__ . '/../templates/admin/designs.php');
$steuer  = (string) file_get_contents(__DIR__ . '/../src/Controllers/DesignAdminController.php');
$modell  = (string) file_get_contents(__DIR__ . '/../src/Design.php');

assert_contains($modell, 'DELETE FROM designs', 'Design: kann eine Vorlage wirklich entfernen');
assert_contains($steuer, "'loeschen'", 'Controller: kennt die Aktion');
assert_contains($steuer, 'byDesign', 'Controller: zaehlt die Einladungen daran');
assert_contains($liste, 'value="loeschen"', 'Liste: jede Zeile hat den Knopf');
assert_contains($steuer, 'frage=loeschen', 'Controller: fragt erst nach, statt gleich zu loeschen');
assert_contains($liste, "=== 'loeschen'", 'Liste: und zeigt die Frage an derselben Kachel');
assert_contains($liste, 'bestaetigt', 'Liste: die Bestaetigung ist ein zweiter Schritt');

/* --- Von vorn anfangen --------------------------------------------------- */

/*
 * Es gab keinen Weg, eine Vorlage von vorn zu beginnen - nur aus einem Thema
 * ueber eine vorhandene Anordnung, oder als Kopie: "yeni tema
 * olusturamiyorum, temadan yeni tasarim yapiliyor sadece".
 *
 * Der Grund dafuer war echt und stand im Editor: er konnte Ebenen nur in zwei
 * Arten anlegen, Bild und Video. Eine leere Vorlage waere ein Blatt gewesen,
 * auf das man kein Wort schreiben kann - deshalb ging alles von etwas
 * Bestehendem aus, denn die Textebenen kamen mit der Anordnung mit.
 *
 * Beides gehoert zusammen: die leere Vorlage ist erst dann ein Anfang und
 * keine Sackgasse, wenn der Editor auch Text anlegt.
 */
$editorSec = (string) file_get_contents(__DIR__ . '/../templates/admin/design-edit-sections.php');

assert_contains($liste, "value=\"leer\"", 'Liste: der Weg von vorn steht da');
assert_contains($steuer, 'private function leer', 'Controller: und legt eine leere Vorlage an');
assert_contains($editorSec, 'value="text"', 'Editor: eine Textebene laesst sich anlegen');
assert_contains($editorSec, 'neue_ebene_text', 'Editor: mit ihrem ersten Satz');
assert_contains($steuer, "'photo', 'video', 'image', 'text', 'shape'", 'Controller: fuenf Arten statt zweier');

/*
 * Und der neue Text beginnt NICHT ueber die ganze Karte. Genau so ein Kasten -
 * unsichtbar, kartenhoch - hat das Ziehen unbrauchbar gemacht, bis die
 * Trefferpruefung auf das Sichtbare umgestellt wurde. Ihn hier neu zu
 * erzeugen waere derselbe Fehler noch einmal.
 */
assert_contains($steuer, "'x' => 10, 'y' => 45, 'w' => 80", 'Controller: ein neuer Text ist eine Zeile, keine Flaeche');
assert_not_contains($steuer, "'neue_ebene_schnitt'] ?? 'voll');\n        \$box = match", 'Controller: der Zuschnitt gilt nur noch fuer Flaechen');

/*
 * Neu angelegtes steht vorn.
 *
 * Alle Vorlagen tragen von Haus aus sort=0, und dann entscheidet der Slug -
 * eine neue hiess "testyusuf" und landete zwischen "test2" und "video". Wer
 * gerade etwas angelegt hat, sucht es aber nicht im Alphabet: "yeni actigim
 * tasarim ilk siraya gecsin".
 *
 * Eine kleiner als die kleinste, und zwar auf allen drei Wegen ins Leben -
 * leer, kopiert, aus einem Thema. Zwei davon zu bedienen und den dritten zu
 * vergessen waere schlimmer als keiner: dann haengt es davon ab, wie man
 * angefangen hat.
 */
assert_contains($modell, 'public static function sortVorn', 'Design: kennt die Nummer fuer ganz vorn');
assert_contains($modell, 'MIN(sort)', 'Design: und holt sie aus der kleinsten');
assert_same(3, substr_count($steuer, 'Design::sortVorn()'),
    'Controller: alle drei Wege ins Leben setzen die neue Vorlage nach vorn');
