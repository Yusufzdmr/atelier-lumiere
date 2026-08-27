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
