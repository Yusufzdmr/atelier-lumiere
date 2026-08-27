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
