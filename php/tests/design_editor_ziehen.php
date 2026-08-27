<?php
declare(strict_types=1);

/*
 * Ziehen statt tippen: der Vertrag zwischen Vorschau, Feldern und Skript.
 *
 * Der Kasten einer Ebene laesst sich seit der vierten Phase im Panel
 * einstellen - mit Zahlen. Seit dem 27.08.2026 auch mit der Maus: anfassen
 * und schieben, an den Griffen ziehen, auf den Text doppelt klicken.
 *
 * Das Ziehen zeichnet nichts nach und speichert nichts eigenes. Es schreibt
 * in genau die Felder, die es schon gibt, und loest ihr input-Ereignis aus -
 * danach laeuft alles Weitere (Vorschau, Rueckgaengig, Speichern) wie bei
 * einer getippten Zahl. Genau deshalb ist der VERTRAG das, was halten muss,
 * und nicht das Verhalten einer Funktion:
 *
 *   - die Vorlage schreibt data-kasten/data-mass an jedes Zahlenfeld,
 *   - das Skript sucht die Felder ueber genau diese beiden Merkmale,
 *   - der Knoten in der Vorschau heisst d-el-<id>, und Design::css() schreibt
 *     diesen Namen ebenfalls.
 *
 * Wird eines davon umbenannt, bleibt die Seite fehlerfrei und lautlos kaputt:
 * man zieht, und nichts bewegt sich. Ein Test am Text der Dateien ist grob,
 * aber er ist der einzige, der diese Naht ueberhaupt anfassen kann - dieselbe
 * Ueberlegung wie in tests/layout_skripte.php.
 */

$js       = (string) file_get_contents(__DIR__ . '/../public/assets/design-editor.js');
$editor   = (string) file_get_contents(__DIR__ . '/../templates/admin/design-edit.php');
$anordnung = (string) file_get_contents(__DIR__ . '/../templates/admin/design-edit-sections.php');
$designPhp = (string) file_get_contents(__DIR__ . '/../src/Design.php');

/* --- Die Naht: Feldnamen ------------------------------------------------ */

assert_contains($anordnung, 'data-kasten="', 'Anordnung: die Zahlenfelder tragen data-kasten');
assert_contains($anordnung, 'data-mass="', 'Anordnung: und data-mass');
assert_contains($js, 'data-kasten="', 'Skript: sucht die Felder ueber data-kasten');
assert_contains($js, 'd-el-', 'Skript: findet den Knoten ueber d-el-<id>');
assert_contains($designPhp, '.d-el-', 'Design::css schreibt denselben Namen');

/* --- Die Griffe: Anfassen, Ziehen, Doppelklick -------------------------- */

assert_contains($js, 'pointerdown', 'Skript: die Ebene laesst sich anfassen');
assert_contains($js, 'setPointerCapture', 'Skript: und der Zeiger bleibt beim Ziehen gefangen');
assert_contains($js, 'data-griff', 'Skript: die Griffe sind benannt');
assert_contains($js, 'dblclick', 'Skript: Doppelklick oeffnet den Text');
assert_contains($js, 'contentEditable', 'Skript: und schreibt ihn an Ort und Stelle');

/*
 * Der Rahmen um das Gewaehlte ist Gestaltung dieser einen Seite und steht
 * deshalb in ihrem Stilblock - style.css ist fertig gebaut, eine erfundene
 * Klasse taete dort schweigend nichts.
 */
assert_contains($editor, '.b-griff', 'Editor: die Griffe haben Regeln im eigenen Stilblock');
assert_contains($editor, '.b-rahmen-wahl', 'Editor: der Rahmen um das Gewaehlte auch');
assert_not_contains($js, 'style.css', 'Skript: fasst das gebaute Stylesheet nicht an');

/* --- Zwei Wahrheiten, die auseinanderlaufen koennten --------------------- */

/*
 * Der Anker entscheidet, an welchen zwei Kanten eine Ebene haengt - und damit
 * beim Ziehen ueber das VORZEICHEN. Wer nach rechts zieht, verkleinert x,
 * wenn von rechts gemessen wird. Das Skript muss den Anker also lesen, nicht
 * nur x und y.
 */
assert_contains($js, '"anchor"', 'Skript: das Ziehen liest den Anker');

/*
 * Die Grenzen stehen in Design::BOX und im min/max der Felder. Das Skript
 * darf sie nicht ein drittes Mal aufschreiben, sondern klemmt am Feld.
 */
assert_contains($js, 'getAttribute("min")', 'Skript: klemmt an der Grenze des Feldes');
assert_contains($js, 'getAttribute("max")', 'Skript: an beiden Grenzen');
assert_not_contains($js, '-50, 150', 'Skript: und schreibt die Grenzen nicht selbst noch einmal');

/*
 * Waehrend im Text getippt wird, gehoert Strg+Z dem Browser: unser
 * Rueckgaengig kennt nur ganze Formularzustaende, und es wuerde den Knoten
 * neu beschriften und den Schreibzeiger verlieren. Die Abfrage auf INPUT und
 * TEXTAREA reicht dafuer nicht - ein Text auf der Karte ist keines von
 * beiden.
 */
assert_contains($js, 'isContentEditable', 'Skript: Strg+Z gehoert dem Browser, solange auf der Karte getippt wird');

/*
 * Angefasst wird, was man SIEHT - nicht der oberste Kasten.
 *
 * Gemessen am 27.08.2026 an der lebenden Vorlage "bild": die Ueberschrift ist
 * eine Textebene mit h=100, also ein Kasten ueber die ganze Karte, in dem
 * oben eine einzige Zeile steht. Sie lag damit ueber den Namen des Paares,
 * ueber dem Datum, ueber allem. Wer die Namen anfasste und zog, verschob die
 * Ueberschrift - weit oben, unbemerkt - und die Namen blieben stehen. Von
 * aussen sah das aus, als taete das Ziehen gar nichts: "suerukle birak
 * yapamiyorum".
 *
 * elementFromPoint beantwortet die falsche Frage ("welcher Kasten liegt
 * hier"), und das Ziehen braucht die andere ("welche Ebene ist hier zu
 * sehen"). Bei einer Textebene sind das die Zeilen selbst, und die kennt nur
 * ein Range.
 */
assert_contains($js, 'getClientRects', 'Skript: fragt die Zeilen des Textes ab, nicht nur den Kasten');
assert_contains($js, 'createRange', 'Skript: und zwar ueber einen Range');
assert_not_contains($js, 'elementFromPoint', 'Skript: nicht ueber den obersten Kasten');

/* --- Am Telefon ---------------------------------------------------------- */

/*
 * Ziehen mit dem Finger.
 *
 * Die Zeigerereignisse kennen den Finger von sich aus - aber der Browser
 * nimmt ihn zuerst fuer sich: eine Wischbewegung auf der Karte scrollt die
 * Seite, und das Ziehen kommt nie an. Am 27.08.2026 stand in der ganzen
 * Editorseite kein einziges touch-action, und damit war die Karte am Telefon
 * nur zum Ansehen da.
 */
assert_contains($editor, 'touch-action', 'Editor: die Karte gibt den Finger nicht an die Seite ab');

/*
 * Und ein Knopf zum Zurueckdrehen.
 *
 * Strg+Z gibt es am Telefon nicht. Gebraucht wird es dort aber mehr als am
 * Schreibtisch: "test ederken deniyorum yani degistiriyorum bakiyorum ne
 * degisti diye ... insan guzel olan bir seyi bozabiliyor". Wer probiert, muss
 * zurueckkonnen, sonst probiert er nicht.
 *
 * Die Knoepfe stehen im festen Balken unten, bei Speichern - dort, wo der
 * Daumen ohnehin liegt, und nicht oben in der Kopfzeile.
 */
assert_contains($editor, 'data-zurueck', 'Editor: der Knopf zum Zurueckdrehen steht da');
assert_contains($editor, 'data-vor', 'Editor: und einer nach vorn');
assert_contains($js, 'data-zurueck', 'Skript: haengt am selben Knopf');
assert_contains($js, 'data-vor', 'Skript: und am anderen');

/* --- Der Rahmen lebt mit ------------------------------------------------- */

/*
 * "Sürükle bırak hala diğer bölümlerde çalışmıyor ... telefon tablet
 * masaüstü kısmında falan da."
 *
 * Zwei Kaesten zeigen dieselbe Karte: das Kaestchen in der Mitte, das jedem
 * Tastendruck folgt, und der Rahmen daneben, der die ganze Seite zeigt.
 * Geschrieben wurde bisher nur in den ersten - der Rahmen holte seine Seite
 * vom Server und blieb deshalb beim GESPEICHERTEN Stand stehen. Wer aufs
 * Telefon umschaltete, sah eine Karte, die sich nicht mehr ruehrte.
 *
 * Das ist KEIN zweiter Zeichner, und genau darauf kommt es an. Die Wahrheit
 * ist das Formularfeld, die Rechnung steht in stelle(), und beide bleiben,
 * wo sie sind. Was sich aendert, ist allein die Zahl der Stellen, an denen
 * dasselbe Ergebnis abgelegt wird: bisher eine, jetzt jede, die gerade da
 * ist. Eine Rechnung, eine Quelle der Wahrheit - unveraendert.
 */

// Die Naht zum Rahmen: er wird ueber sein Merkmal gefunden, und die Buehne
// darin ueber die Klasse, die design-stage.php schreibt.
assert_contains($js, 'var rahmenWurzeln', 'Skript: es findet die Wurzeln im Rahmen');
assert_contains($js, '.d-stage', 'Skript: und zwar ueber ihren Namen');

$buehne = (string) file_get_contents(__DIR__ . '/../templates/partials/design-stage.php');
assert_contains($buehne, 'd-stage', 'Buehne: traegt denselben Namen - sonst greift die Suche ins Leere');

/*
 * Die Wurzeln. Es sind eine oder zwei, je nachdem ob der Rahmen offen ist -
 * deshalb eine Funktion und keine Liste: der Rahmen entsteht erst beim
 * ersten Klick auf ein Geraet, eine beim Laden gebaute Liste waere fuer
 * immer einelementig.
 */
assert_contains($js, 'var wurzeln', 'Skript: es kennt mehr als eine Wurzel');
assert_contains($js, 'var knotenAlle', 'Skript: und findet die Ebene in jeder');

/*
 * Die vier Variablenschreiber und die drei Knotensucher gehen ueber die
 * Wurzeln. Gezaehlt und nicht nur gesucht: bliebe EINER von ihnen an der
 * Vorschau haengen, waere der Rahmen bei genau einer Sorte Aenderung still -
 * und das ist die Art Fehler, die man erst drei Wochen spaeter bemerkt.
 */
assert_same(0, substr_count($js, 'vorschau.style.setProperty'),
    'Skript: keine Farbe, keine Schrift geht mehr nur in die Vorschau');
// Genau einer bleibt: der in knoten(), gleich darunter begruendet. Text und
// Schriftgroesse suchten bisher selbst und muessen mit - drei waren es.
assert_same(1, substr_count($js, 'vorschau.querySelector(".d-el-"'),
    'Skript: nur noch der einzelne Sucher schaut allein in die Vorschau');

/*
 * Der einzelne Sucher bleibt: knoten() liefert die Ebene aus der Vorschau,
 * und genau das wird an den Stellen gebraucht, die von der Vorschau reden.
 * Wer die Ebene in einer bestimmten Wurzel braucht, nimmt knotenIn().
 */
assert_contains($js, 'var knoten = function (id) {', 'Skript: der einzelne Sucher bleibt');

/*
 * Der Wahlrahmen lag bis Schritt drei nur in der Vorschau - im Geraetemodus
 * also nirgends, und wer im Rahmen eine Ebene anfasste, sah nicht, was er
 * anfasste. Jetzt hat jede Wurzel ihren eigenen, gebaut in ihrem eigenen
 * Dokument, und keiner wird nebenher gemerkt: gesucht wird im DOM, weil der
 * Rahmen beim Geraetewechsel neu laedt und ein gemerkter Knoten danach eine
 * Leiche waere.
 */
assert_not_contains($js, 'var rahmenWahl',
    'Skript: kein einzelner, nebenher gemerkter Wahlrahmen mehr');
assert_contains($js, 'wurzel.appendChild(kasten);',
    'Skript: jeder Wahlrahmen haengt in seiner eigenen Wurzel');

/*
 * Und die Flaeche unter der Karte gehoert dazu.
 *
 * Design::css() legt die Marken der Vorlage unter den Geltungsbereich
 * (Design.php: '--d-' . $key). Im Rahmen tragen diesen Bereich ZWEI Knoten:
 * die Buehne mit der Karte und die Flaeche mit den Abschnitten darunter -
 * DesignSections::flaeche() schreibt ihn ein zweites Mal.
 *
 * Nur auf die Buehne geschrieben faerbte sich im Rahmen die Karte um und die
 * Abschnitte blieben stehen. Ein halber Schritt sieht schlimmer aus als gar
 * keiner: bei einem stehengebliebenen Rahmen weiss man, woran man ist, bei
 * einem halb umgefaerbten sucht man den Fehler in der Vorlage.
 */
assert_contains($js, '.d-sec-flaeche', 'Skript: die Flaeche unter der Karte ist die zweite Wurzel');

$sectionsPhp = (string) file_get_contents(__DIR__ . '/../src/DesignSections.php');
assert_contains($sectionsPhp, 'd-sec-flaeche', 'Abschnitte: tragen denselben Namen');
assert_contains($sectionsPhp, "e(\$scope) . ' d-sec-flaeche",
    'Abschnitte: und den Geltungsbereich davor - daran haengen die Marken');

// Und sie liest sie auch: Papier und Schriftfarbe der Flaeche kommen aus
// denselben Marken, die der Editor beim Tippen umschreibt.
assert_contains($sectionsPhp, '.d-sec-flaeche{background-color:var(--d-paper',
    'Abschnitte: die Flaeche liest die Marken der Vorlage');

/* --- Und ziehen laesst er sich auch ------------------------------------- */

/*
 * Schritt zwei: der Rahmen folgt nicht nur, er laesst sich auch anfassen.
 *
 * Die Rechnung des Ziehens war dafuer schon fast frei: sie misst in PROZENT
 * des Elternkastens (offsetParent) und nicht in Pixeln der Seite. Was im
 * Rahmen passiert, passiert in dessen eigenem Koordinatensystem - das
 * transform:scale, mit dem der Rahmen verkleinert wird, kuerzt sich dabei
 * heraus. Es blieben genau drei Stellen, die an der Vorschau klebten.
 *
 * Erste Stelle: die Suche nach der Ebene unter dem Zeiger. Sie durchlief die
 * Vorschau; jetzt bekommt sie die Wurzel gesagt, in der gesucht wird.
 */
assert_same(0, substr_count($js, 'vorschau.querySelectorAll(".d-el")'),
    'Skript: die Ebene wird in der gefragten Wurzel gesucht, nicht immer in der Vorschau');
assert_contains($js, 'var ebeneAn = function (wurzel,',
    'Skript: die Suche nimmt die Wurzel entgegen');

/*
 * Zweite Stelle: die Bindung selbst. Sie haengt jetzt an einer Wurzel, die
 * uebergeben wird - einmal an der Vorschau, einmal an der Buehne im Rahmen.
 * Der Zeiger wird an genau dem Knoten gefangen, der das Ereignis bekommen
 * hat: setPointerCapture ueber Dokumentgrenzen hinweg gibt es nicht.
 */
assert_contains($js, 'var haengeZiehen = function (wurzel)',
    'Skript: das Ziehen laesst sich an eine Wurzel haengen');
assert_contains($js, 'haengeZiehen(vorschau)',
    'Skript: die Vorschau bekommt es wie bisher');
assert_contains($js, 'wurzel.setPointerCapture',
    'Skript: gefangen wird am Knoten, der das Ereignis bekam');

/*
 * Zweimal an dieselbe Buehne gehaengt hiesse: jedes Ziehen zaehlt doppelt,
 * und die Ebene liefe mit doppelter Geschwindigkeit davon. Der Rahmen laedt
 * bei jedem Wechsel des Geraets neu, also wird es oft versucht.
 */
assert_contains($js, 'data-zieht-bereit',
    'Skript: eine Buehne wird nur einmal angehaengt');

/*
 * Dritte Stelle: der Rahmen um das Gewaehlte.
 *
 * zeichne() gab die Wahl auf, sobald der Knoten keinen offsetParent hatte -
 * und das ist er auch, wenn nicht die EBENE weg ist, sondern das Kaestchen
 * um sie herum: im Geraetemodus ist die Vorschau versteckt. Wer dort eine
 * Ebene anfasste, verlor sie im selben Atemzug wieder, und die Zeile links
 * blinkte einmal auf.
 *
 * Also wird unterschieden: eine versteckte VORSCHAU nimmt nur den Rahmen
 * weg, eine verschwundene EBENE die Wahl.
 */
assert_contains($js, 'var versatz',
    'Skript: eine Wurzel, die sich nicht vermessen laesst, wird uebersprungen - nicht die Wahl weggeworfen');

/* --- Im Geraetemodus steht der Rahmen oben ------------------------------- */

/*
 * "Telefon sekmesine gecince acilis filmi ekrana gelmiyor, onun yerine
 * sayfanin altindaki bolumler goruluyor."
 *
 * Gemessen am 27.08.2026 auf dem Livesystem, an der Vorlage testyusuf1:
 *
 *   Kasten der lebenden Abschnitte   2677 px hoch, NICHT versteckt
 *   Rahmen (Telefon, 390x741)        beginnt bei y = 2909
 *   Fensterhoehe                      855
 *   Rahmen im Bild                    nein
 *
 * Der Film war also die ganze Zeit da und richtig - im Rahmen gemessen:
 * readyState 4, Kaplama sichtbar, elementFromPoint traf das VIDEO. Nur der
 * Rahmen selbst stand knapp drei Bildschirme weiter unten, und oben stand
 * das, was man statt seiner sah.
 *
 * Der Grund ist eine Reihenfolge im Markup: Karte, dann die lebenden
 * Abschnitte, dann der Rahmen. Beim Umschalten auf ein Geraet verschwindet
 * die KARTE (karte.hidden = true) und der Rahmen kommt - der Kasten
 * dazwischen blieb stehen, rutschte nach oben und schob den Rahmen aus dem
 * Bild.
 *
 * Nur der Kasten wird versteckt, nicht geleert: der Inhalt kostet einen Weg
 * zum Server, und beim Zurueckschalten auf die Karte will man ihn sofort
 * wieder sehen.
 *
 * Und die Entscheidung steht an EINER Stelle: das Nachladen (hole) setzte
 * sie ebenfalls, und ein Wechsel des Geraets stoesst das Nachladen an - der
 * Kasten waere 400 ms spaeter von selbst zurueckgekommen.
 */
/*
 * KORREKTUR vom selben Abend.
 *
 * Der erste Versuch versteckte den Kasten im Geraetemodus. Das brachte den
 * Rahmen ins Bild - und nahm zugleich die einzige Stelle weg, an der eine
 * ungespeicherte Aenderung an einem ABSCHNITT zu sehen war: der Rahmen holt
 * seine Seite vom Server und weiss von ihr nichts. Wer rechts die
 * Ausrichtung eines Abschnitts umstellte, sah daraufhin gar nichts mehr.
 * "Sagdaki ozelliklerden degistiriyorum ama hicbir sey olmuyor."
 *
 * Der Fehler war nie das Dasein des Kastens, sondern seine STELLE: er stand
 * zwischen Karte und Rahmen. Faellt die Karte weg, rutscht er nach oben und
 * schiebt den Rahmen hinaus. Steht der Rahmen davor, stoert er niemanden -
 * und beide sind zu sehen.
 *
 * Also: Karte, Rahmen, Abschnitte. Und kein Verstecken mehr.
 */
assert_true(
    strpos($editor, 'data-ansicht-rahmen') < strpos($editor, 'data-live-abschnitte'),
    'Editor: der Rahmen steht VOR dem Kasten der lebenden Abschnitte'
);
assert_not_contains($js, 'var abschnitteZeigen',
    'Skript: der Kasten wird nicht mehr versteckt - die Reihenfolge genuegt');
assert_contains($js, 'liveKasten.hidden = stueck.trim()',
    'Skript: er haengt wieder allein daran, ob es etwas zu zeigen gibt');

// Die Naht: der Kasten steht zwischen Karte und Rahmen, und genau das ist der
// Grund. Wandert er im Markup, ist diese Regel hinfaellig.
assert_contains($editor, 'data-live-abschnitte', 'Editor: der Kasten traegt sein Merkmal');
assert_contains($js, 'data-live-abschnitte', 'Skript: und wird darueber gefunden');

/* --- Und der Wahlrahmen liegt auch darin ---------------------------------- */

/*
 * Schritt drei und vier, zusammen: Griffe ohne Regeln waeren ein halber
 * Schritt, und ein halber Schritt sieht schlimmer aus als gar keiner.
 *
 * Bis hierher gab es EINEN Wahlrahmen, er hing in der Vorschau, und seine
 * Koordinaten waren offsetLeft/offsetTop in ihr. Im Rahmen ist beides falsch.
 */

/*
 * Erstens: der Knoten muss IN dem Dokument entstehen, in dem er liegen soll.
 * Ein div aus dem Editordokument laesst sich nicht in den Rahmen haengen -
 * und selbst importiert haette es dort keine Regeln.
 */
assert_contains($js, 'var wahlrahmenFuer', 'Skript: je Wurzel ein Wahlrahmen');
assert_contains($js, 'ownerDocument', 'Skript: gebaut im Dokument der Wurzel, nicht im eigenen');

/*
 * Zweitens: die Rechnung. In der Vorschau liegt eine Ebene EINEN Sprung unter
 * dem Kasten (.d-el in einer Huelle, die inset-0 daraufliegt). Im Rahmen sind
 * es drei: .d-el -> .d-card -> .d-stage-mitte -> .d-stage. Die alte Abkuerzung
 * addierte genau einen und traefe dort um die halbe Buehne daneben.
 */
assert_contains($js, 'var versatz', 'Skript: der Versatz wird bis zur Wurzel aufaddiert');
assert_same(0, substr_count($js, 'eltern === vorschau ? 0 : eltern.offsetLeft'),
    'Skript: die Abkuerzung ueber genau einen Sprung ist fort');

// Die Naht, an der die Rechnung haengt: diese drei Namen schreibt
// design-stage.php. Wandert einer, misst der Wahlrahmen ins Leere.
$buehne2 = (string) file_get_contents(__DIR__ . '/../templates/partials/design-stage.php');
assert_contains($buehne2, 'd-stage-mitte', 'Buehne: die Mitte traegt ihren Namen');
assert_contains($buehne2, 'd-card t-card relative', 'Buehne: und die Karte ist der Bezugskasten');

/*
 * Drittens: die Regeln der Griffe. Sie stehen im Stilblock des Editors
 * (design-edit.php) und gelten dort; im Rahmen ist von ihnen nichts bekannt.
 *
 * Kopiert wird aus dem GEBAUTEN Blatt, nicht hier noch einmal geschrieben.
 * Ein zweiter Satz Regeln im Skript waere eine zweite Wahrheit ueber das
 * Aussehen der Griffe, und die laeuft beim naechsten Handgriff am Stilblock
 * auseinander.
 */
assert_contains($js, 'styleSheets', 'Skript: die Regeln kommen aus dem Blatt des Editors');
assert_contains($js, 'dok === document',
    'Skript: und werden nicht ins eigene Dokument zurueckkopiert');
assert_not_contains($js, '.b-griff{', 'Skript: und werden nicht ein zweites Mal aufgeschrieben');

/*
 * Dabei muss der Geltungsbereich mitwandern. Mehrere Regeln haengen an
 * [data-design-preview] - den Kasten gibt es im Rahmen nicht. Die Marke, die
 * es dort gibt, ist die aus Schritt zwei: data-zieht-bereit steht auf jeder
 * Wurzel, an der das Ziehen haengt.
 *
 * Darunter ist eine, die nicht Zierde ist: .d-el{touch-action:none}. Ohne sie
 * nimmt der Browser den Finger fuer sich und wischt die Seite, statt die
 * Ebene zu ziehen - am Telefon waere der Rahmen dann wieder nur zum Ansehen.
 */
assert_contains($js, 'data-design-preview', 'Skript: es kennt den Kasten, dessen Regeln es umschreibt');
assert_contains($editor, 'touch-action:none', 'Editor: die Ebenen geben den Finger nicht ab');

/*
 * Viertens: an einem Griff im Rahmen wird die Ebene IM RAHMEN gefasst.
 * knoten() liefert die aus der Vorschau - im Geraetemodus ist die versteckt,
 * und ein versteckter Knoten hat keine Groesse. Der Griff zoege ins Nichts.
 */
assert_contains($js, 'var knotenIn', 'Skript: die Ebene wird in der angefassten Wurzel gesucht');

/* --- Schritt fuenf und sechs: aufmachen und beschriften ------------------- */

/*
 * Fuenf: das Kuvert im Rahmen geht auf, wenn man auf ein Geraet umschaltet.
 *
 * Ziehen ging auch vorher - ebeneAn() sucht ueber die Rechtecke der Ebenen
 * und fragt nicht, was darueber liegt. Zu SEHEN war nur nichts: die Karte
 * steckt hinter dem Kuvert, und der Wahlrahmen lag auf einer geschlossenen
 * Huelle. Man zog blind.
 *
 * Aufgemacht wird mit oeffneRahmen(), das es fuer die Abschnittswahl schon
 * gibt - dort ist derselbe Satz begruendet: von aussen an fremden
 * Inline-Stilen zu drehen hiesse, dieselbe Sache an zwei Stellen zu
 * entscheiden. Geklickt wird das Kuvert, den Rest macht invitation.js.
 */
assert_contains($js, 'var oeffneRahmen', 'Skript: den Weg zum Aufmachen gibt es schon');
assert_contains($js, 'kuvertAuf', 'Skript: und das Umschalten nimmt ihn auch');

/*
 * Sechs: der Doppelklick auf den Text gilt in jeder Wurzel.
 *
 * Er war die letzte Hand, die noch am Kasten in der Mitte hing. Im Rahmen
 * liess sich damit nichts beschriften - man klickte doppelt, und es geschah
 * nichts.
 */
assert_same(0, substr_count($js, 'vorschau.addEventListener("dblclick"'),
    'Skript: der Doppelklick haengt nicht mehr nur am Kasten in der Mitte');
assert_contains($js, 'wurzel.addEventListener("dblclick"',
    'Skript: sondern an jeder Wurzel');

/*
 * Und er schreibt im richtigen Dokument.
 *
 * Auswahl und Range gehoeren dem Dokument, in dem der Knoten steht.
 * document.createRange() im Editor auf einen Knoten IM RAHMEN angewandt ist
 * ein Range ueber Dokumentgrenzen - der markiert nichts, und
 * window.getSelection() des Editors weiss vom Rahmen ohnehin nichts. Dann
 * stuende der Schreibzeiger nirgends und "alles gewaehlt" waere leer.
 */
assert_contains($js, 'ownerDocument.createRange',
    'Skript: der Bereich entsteht im Dokument des Knotens');
assert_contains($js, 'defaultView.getSelection',
    'Skript: und die Auswahl gehoert demselben Fenster');

/*
 * Und die Regeln in einer @media-Gruppe muessen mit.
 *
 * Der erste Wurf sammelte ueber selectorText - eine @media-Gruppe hat keinen,
 * sie wurde also stillschweigend uebersprungen. Darunter lag ausgerechnet
 * die Regel, die den Griffen am FINGER ihren Fangbereich gibt:
 * @media (pointer: coarse) macht aus zehn Pixeln vierunddreissig. Ohne sie
 * ist ein Griff im Rahmen am Telefon zehn Pixel gross - zu treffen ist das
 * nicht, und von aussen sieht es aus, als taete das Ziehen dort nichts.
 *
 * Die Bedingung gehoert mitkopiert, nicht nur ihr Inhalt: ohne sie gaelte die
 * Vergroesserung auch mit der Maus, und dann laege ueber jedem Griff ein
 * unsichtbarer Kasten von 34 Pixeln, der den Nachbargriff verdeckt.
 */
assert_contains($js, 'cssRules', 'Skript: es sieht auch in Gruppen hinein');
assert_contains($editor, '@media (pointer: coarse)', 'Editor: die Regel fuer den Finger steht da');
assert_contains($js, 'var sammle', 'Skript: gesammelt wird rekursiv, damit Gruppen nicht durchfallen');

/* --- Die Abschnittsliste laesst sich schieben --------------------------- */

/*
 * "Surukle birak duzenleme editorunden bahsediyorum, asagidaki kartlarda
 * calismiyor."
 *
 * Die Reihenfolge der Abschnitte ging bisher nur ueber die Pfeile. Bei sieben
 * Zeilen ist das Klicken; bei einer Zeile, die von unten nach oben soll, ist
 * es sechsmal Klicken.
 *
 * KEINE zweite Sortierlogik: geschoben wird der Knoten, und danach schreibt
 * dieselbe reiheNeu() dieselbe Reihe ins versteckte Feld wie bei den Pfeilen.
 * Die Pfeile bleiben - am Telefon nimmt der Finger die Liste zum Scrollen,
 * und dort sind sie der Weg.
 */
assert_contains($js, 'var ziehtZeile', 'Skript: die Liste kennt ein Ziehen');
assert_contains($js, 'data-sec-waehl', 'Skript: angefasst wird am Greifer der Zeile');

/*
 * Erst ab einer Schwelle ist es ein Ziehen. Ohne sie waere jeder Klick auf
 * eine Zeile schon eine Bewegung um ein, zwei Pixel - und die Zeile spraenge
 * beim blossen Auswaehlen umher.
 */
assert_contains($js, 'SCHWELLE', 'Skript: ein Klick ist noch kein Ziehen');

/*
 * Und nach einem echten Ziehen wird der folgende Klick geschluckt: sonst
 * waehlt das Loslassen den Abschnitt aus und die Mitte springt auf ein
 * Geraet - man wollte nur umsortieren.
 */
assert_contains($js, 'var schluck', 'Skript: der Klick nach dem Ziehen faellt weg');

// Dieselbe Hand wie die Pfeile, nicht eine zweite daneben.
assert_contains($js, 'reiheNeu();', 'Skript: die Reihe schreibt weiterhin reiheNeu');
assert_contains($editor, '[data-sec-zeile][data-zieht]', 'Editor: die geschobene Zeile sieht man ihr an');
