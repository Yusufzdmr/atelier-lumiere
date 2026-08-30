<?php
declare(strict_types=1);

use Atelier\Design;

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
// Der Bauer eines einzelnen Einstellungsfeldes steht seit dem Rahmen in einer
// eigenen Vorlage - er wird von beiden Schleifen der Tafel gebraucht.
$feldbauer  = (string) file_get_contents(__DIR__ . '/../templates/partials/einstellung-feld.php');
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
assert_contains($feldbauer, 'data-tonvorschau', 'Panel: die Tonspur hat einen Spieler am Feld');

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
/*
 * Und zwar in EINEM Schritt.
 *
 * Erst fragte der Knopf nach und nannte die Zahl der Einladungen daran.
 * Ausdruecklich abbestellt: "sil dedigimde direkt silsin, emin misin diye
 * sormasin". Die Zahl wandert in die Meldung danach - sie aendert nichts an
 * der Entscheidung, sie sagt, was gerade passiert ist.
 *
 * Der Test haelt fest, dass die Rueckfrage wirklich weg ist: eine, die nur im
 * Controller noch stuende, waere ein Weg, der nie erreicht wird - und beim
 * naechsten Lesen eine Luege ueber das Verhalten.
 */
assert_not_contains($steuer, 'frage=loeschen', 'Controller: fragt nicht mehr nach');
assert_contains($steuer, "'ok=geloescht&n='", 'Controller: nennt die Zahl hinterher');

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

/*
 * Ein Lied darf von auswaerts kommen - als einziges.
 *
 * "Linkle yukleme yapabilelim muzik." safeSrc laesst nur zu, was wir selbst
 * vergeben (/uploads, /assets); fuer Bild und Film ist das richtig, sie
 * gehoeren zur Vorlage. Ein Lied liegt oft schon irgendwo.
 *
 * Nur https, und nicht aus Formalismus: die Einladung laeuft ueber https, und
 * ein Lied ueber http verweigert der Browser als gemischten Inhalt - der Ton
 * bliebe stumm, ohne dass jemand sagen koennte, warum.
 */
assert_same('/uploads/designs/lied.mp3', Design::safeAudio('/uploads/designs/lied.mp3'), 'Ton: das eigene Haus');
assert_same('https://cdn.example.com/l.mp3', Design::safeAudio('https://cdn.example.com/l.mp3'), 'Ton: eine fremde https-Adresse');
assert_same('', Design::safeAudio('http://cdn.example.com/l.mp3'), 'Ton: http faellt weg');
assert_same('', Design::safeAudio('javascript:alert(1)'), 'Ton: ein Skript erst recht');
assert_same('', Design::safeAudio('https://x.test/a b.mp3'), 'Ton: Leerzeichen faellt weg');
assert_same('', Design::safeSrc('https://cdn.example.com/bild.webp'), 'Bild: bleibt beim eigenen Haus');

/*
 * Der Vorspann laesst sich aus der Ablage waehlen.
 *
 * Die Filme liegen laengst da, mit Namen und Standbild - nur eben auf der
 * Uebersicht, wo man sie FUELLT. Im Editor, wo man einen auswaehlt, gab es
 * sie nicht: der Weg war "Adresse abtippen" oder "dieselbe Datei ein zweites
 * Mal hochladen".
 *
 * Die Auswahl schreibt in die Pfadfelder und hat selbst keinen Namen im
 * Formular - gespeichert wird weiterhin, was in den Feldern steht. Und sie
 * schreibt BEIDE: ein gewechselter Film mit stehengebliebenem Standbild zeigt
 * beim Oeffnen das falsche erste Bild.
 */
$steuerV = (string) file_get_contents(__DIR__ . '/../src/Controllers/DesignAdminController.php');
assert_contains($steuerV, "'videos'   => DesignVideos::all()", 'Controller: der Editor bekommt die Ablage');
assert_contains($editorSec, 'data-introwahl', 'Editor: die Auswahl steht da');
assert_contains($editorSec, 'data-poster', 'Editor: mit dem Standbild des Films');
assert_contains($js, 'data-introwahl', 'Skript: haengt daran');
assert_contains($js, 'schreibeIntro("intro_poster"', 'Skript: und schreibt auch das Standbild');

/*
 * Und wieder wegnehmen.
 *
 * Der Weg dorthin war, den Pfad von Hand zu leeren - darauf kommt niemand:
 * "kaldirmak istedigimde kaldir koy". Die leere Zeile in der Liste hiess
 * "waehlen" und tat beim Anklicken gar nichts.
 *
 * Weggenommen wird BEIDES. Ein Standbild ohne Film ist ein erstes Bild fuer
 * nichts - es bliebe im Dokument stehen und machte irgendwann mit fremdem
 * Gesicht auf, wenn ein anderer Film dazukommt.
 */
assert_contains($editorSec, 'data-introweg', 'Editor: ein Knopf nimmt den Film weg');
assert_contains($js, 'var introWeg', 'Skript: und die Hand dazu');
assert_contains($js, 'schreibeIntro("intro_poster", "")', 'Skript: das Standbild geht mit');

/*
 * Der Kasten haengt am Pfad, nicht nur am Dateifeld.
 *
 * Es gibt zwei Wege, wie ein Bild oder ein Film in eine Vorlage kommt: man
 * waehlt eine Datei, oder ein Pfad aendert sich - getippt oder aus der
 * Filmablage eingesetzt. Der Kasten kannte nur den ersten, und deshalb blieb
 * er leer, als die neue Auswahl den Film brav ins Feld schrieb: "videoyu
 * sectim ama gelmedi onizleme".
 *
 * Welche Art hineingehoert, sagt bei einem Pfad die Vorlage - ein Pfad sagt
 * es nicht von sich aus, und ".mp4" zu lesen waere Raten.
 */
assert_same(6, substr_count($editorSec, 'data-vorschau-pfad'),
    'Editor: alle sechs Kaesten kennen ihr Pfadfeld');
assert_contains($editorSec, 'data-vorschau-art="film"', 'Editor: und sagen, was hineingehoert');
assert_contains($js, 'data-vorschau-pfad', 'Skript: hoert auch auf den Pfad');
assert_contains($js, 'var zeigeImKasten', 'Skript: beide Wege nehmen dieselbe Hand');

/*
 * Der Vorspann steht jetzt auch im Editor.
 *
 * Die Buehne der echten Seite zeigt ihn ueber dem Kuvert; das Kaestchen im
 * Panel zeigte nur Seite und Karte. Wer einen Film auswaehlte und speicherte,
 * sah ihn danach an keiner Stelle: "sagdan video secip kaydettim hala gelmedi
 * onizlemeye".
 *
 * Er folgt dem Pfadfeld und geht mit einem Klick weg - solange er liegt,
 * faengt er die Klicks ab, und darunter will man ziehen.
 */
$editorHaupt = (string) file_get_contents(__DIR__ . '/../templates/admin/design-edit.php');
assert_contains($editorHaupt, 'data-vorspann', 'Editor: der Vorspann hat seinen Platz ueber der Karte');
assert_contains($editorHaupt, 'z-index:60', 'Editor: und liegt darueber - als Stil, nicht als erfundene Klasse');
assert_contains($js, 'data-vorspann', 'Skript: haengt daran');
assert_contains($js, 'vorspann.hidden = true', 'Skript: ein Klick nimmt ihn weg');
