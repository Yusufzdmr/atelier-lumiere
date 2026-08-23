<?php
declare(strict_types=1);

use Atelier\Themes;

/*
 * Der Kunde: "Cok animasyon. Cizgifilm gibi. Esas olmasi daha elegant ve
 * romantik." Also weniger Auswahl, nicht kleinere Zahlen - eine Liste mit
 * acht Eintraegen laedt nicht dazu ein, sieben davon anzuschalten.
 */

/* --- Teilchen sind ganz weg --- */

assert_same(['none'], Themes::PARTICLES, 'motion: keine Teilchen mehr');

/* --- Von den uebrigen bleibt je eine Bewegung und die Ruhe --- */

assert_same(['breathe', 'none'], Themes::IDLES, 'motion: nur das Atmen bleibt');
assert_same(['fade', 'none'], Themes::NAME_ANIMATIONS, 'motion: Namen blenden ein, mehr nicht');
assert_same(['up', 'none'], Themes::REVEALS, 'motion: eine Richtung reicht');
assert_same(['none', 'fade'], Themes::MOVES, 'motion: Schmuck blendet ein oder steht');

/* --- INTROS gehoert v1 und bleibt unangetastet --- */

assert_same(6, count(Themes::INTROS), 'motion: die Auftakte der ersten Fassung bleiben');

/* --- Ein Wert, den es nicht mehr gibt, faellt auf den Ersatz - er wirft nicht.
       Der Ersatz kommt aus defaultMoves(), NICHT stur aus 'none': das steht
       so in complete() und ist der Grund, warum defaultMoves() in derselben
       Aufgabe mitgeht. Bliebe die alte Tabelle stehen, ersetzte sie einen
       ungueltigen Wert durch den naechsten ungueltigen. --- */

$thema = Themes::complete([
    'id'       => 'safran',        // in der alten Tabelle: confetti + heartbeat
    'particle' => 'confetti',
    'idle'     => 'heartbeat',
    'reveal'   => 'zoom',
]);

assert_same('none', $thema['particle'], 'motion: Konfetti faellt auf none - mehr gibt es nicht');
assert_same('breathe', $thema['idle'], 'motion: Herzschlag faellt auf das Atmen');
assert_same('up', $thema['reveal'], 'motion: zoom faellt auf die eine Richtung');

/* --- Und zwar fuer JEDES Thema gleich: bei einer Auswahl von eins gibt es
       nichts mehr, was "zur Farbwelt passen" koennte --- */

foreach (['elysee', 'noir', 'moderne', 'gibtesnicht'] as $id) {
    $t = Themes::complete(['id' => $id, 'particle' => 'spark', 'idle' => 'ring']);
    assert_same('none', $t['particle'], "motion: $id hat keine Teilchen");
    assert_same('breathe', $t['idle'], "motion: $id atmet");
}
