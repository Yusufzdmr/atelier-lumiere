<?php
/**
 * Die Regeln des Bildfeldes - geteilt vom Assistenten und vom
 * Bearbeiten-Bildschirm.
 *
 * Warum eine eigene Datei und kein zweiter Stilblock: style.css ist FERTIG
 * gebaut, diese Regeln koennen also nicht dorthin. Zweimal abgeschrieben
 * liefen sie auseinander, sobald jemand nur eine der beiden Seiten anfasst -
 * und man saehe es nicht, weil beide Seiten fuer sich stimmig blieben.
 *
 * Eingebunden wird sie INNERHALB des <style>-Blocks der jeweiligen Seite,
 * darum steht hier kein <style> um sie herum.
 *
 * .wz-photo--schmal ist fuer den Bearbeiten-Bildschirm: dort stehen die
 * Ebenen zu zweit nebeneinander, eine Karte ist rund 320 px breit, und eine
 * Platte samt Knopf daneben passt da nicht. Die Abfrage haengt an der Karte,
 * nicht am Fenster - deshalb eine eigene Klasse statt einer Medienabfrage,
 * die vom Fenster nichts als die falsche Antwort bekaeme.
 */
?>
  /*
   * Das Bildfeld. Eigene Regeln, weil hier fast nichts aus der gebauten
   * style.css kommen kann: die Platte braucht ein Seitenverhaeltnis, der
   * leere Zustand eine gestrichelte Innenlinie, und das Dateifeld des
   * Browsers laesst sich ueberhaupt nicht mit Klassen anfassen.
   *
   * Die Platte ist bewusst gross. Auf diesem Bildschirm ist alles Schrift -
   * sie ist die einzige Stelle, an der das Gesicht des Paares hereinkommt,
   * und ein Vorschaubildchen von 96 Pixeln behandelt sie wie eine Fussnote.
   */
  .wz-photo { display: flex; align-items: flex-start; gap: 1.75rem; }
  @media (max-width: 640px) { .wz-photo { flex-direction: column; gap: 1.25rem; } }

  .wz-photo-platte {
    position: relative;
    width: 11rem;
    flex: none;
    aspect-ratio: 4 / 5;
    overflow: hidden;
    border: 1px solid var(--color-sand-deep);
    background: var(--color-sand);
  }
  .wz-photo-platte img { display: block; width: 100%; height: 100%; object-fit: cover; }

  /*
   * Diese zwei Zeilen sind Pflicht, nicht Sorgfalt: das hidden-Attribut wirkt
   * ueber die Grundregel [hidden]{display:none} des Browsers, und die verliert
   * gegen jede eigene display-Angabe. Ohne sie zeigte die leere Platte das
   * <img hidden> trotzdem an - also ein kaputtes Bildsymbol - und das Wort
   * "Noch kein Bild" bliebe nach der Wahl darueber stehen. Der Attribut-
   * Selektor gewinnt gegen die Regeln darueber, weil er spezifischer ist.
   */
  .wz-photo-platte img[hidden] { display: none; }
  .wz-photo-leer[hidden] { display: none; }

  /* Leer ist ein Zustand, kein Fehler: die gestrichelte Linie liegt INNEN,
     damit der Rahmen der Platte durchgehend bleibt und die Flaeche trotzdem
     als "hier kommt noch etwas hin" liest. */
  .wz-photo-leer {
    position: absolute; inset: .55rem;
    display: flex; align-items: center; justify-content: center;
    padding: .5rem;
    border: 1px dashed var(--color-sand-deep);
    font-family: var(--font-display);
    font-size: .95rem;
    line-height: 1.3;
    text-align: center;
    color: var(--color-muted);
  }

  /* min-height, damit die Zeile beim ersten Waehlen nicht die Platte
     verschiebt - vorher steht hier nichts, danach "Gewaehlt". */
  .wz-photo-bu { margin-top: .6rem; min-height: 1.2em; }

  .wz-photo-name {
    font-family: var(--font-display);
    font-size: 1.15rem;
    letter-spacing: .01em;
    color: var(--color-ink);
  }

  /*
   * Der Knopf ist ein <label> mit dem Dateifeld darin: ein Klick darauf
   * oeffnet den Dateidialog ohne eine Zeile JavaScript. Das Feld bleibt
   * bedienbar und tastaturerreichbar, es ist nur nicht zu sehen - deshalb
   * opacity und ein Pixel Groesse statt display:none, das den Fokus
   * mitnaehme. Den Ring traegt darum das label, ueber :focus-within.
   */
  .wz-photo-knopf {
    position: relative;
    display: inline-block;
    margin-top: .9rem;
    border: 1px solid var(--color-ink);
    padding: .75rem 1.5rem;
    font-size: .66rem;
    text-transform: uppercase;
    letter-spacing: .16em;
    color: var(--color-ink);
    cursor: pointer;
    transition: background-color .2s, color .2s;
  }
  .wz-photo-knopf:hover { background: var(--color-ink); color: var(--color-cream); }
  .wz-photo-knopf:focus-within { outline: 2px solid var(--color-gold); outline-offset: 3px; }
  .wz-photo-knopf input { position: absolute; inset: 0; width: 100%; height: 100%; opacity: 0; cursor: pointer; }

  .wz-photo-datei {
    margin-top: .75rem;
    font-size: .9rem;
    color: var(--color-ink);
    overflow-wrap: anywhere;
  }
  .wz-photo-datei::before { content: '\2014\00a0'; color: var(--color-gold); }

  .wz-photo-hint { margin-top: .6rem; font-size: .8rem; line-height: 1.5; color: var(--color-muted); }
  .wz-photo--schmal { display: block; }
  .wz-photo--schmal .wz-photo-platte { width: 8rem; }
  .wz-photo--schmal > div + div { margin-top: 1rem; }
