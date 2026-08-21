<?php
/**
 * Die Regeln der Schritt-Reiter - geteilt vom Assistenten und vom
 * Bearbeiten-Bildschirm.
 *
 * Eine Datei und keine zwei Kopien: style.css ist FERTIG gebaut, diese Regeln
 * koennen also nicht dorthin, und abgeschrieben liefen sie auseinander, sobald
 * jemand nur eine der beiden Seiten anfasst - unbemerkt, weil beide Seiten
 * fuer sich stimmig blieben.
 *
 * Eingebunden INNERHALB des <style>-Blocks der jeweiligen Seite, darum steht
 * hier kein <style> darum.
 */
?>
  /*
   * Die Tabs: bisher genauso klein und grau wie ein Feldlabel, deshalb keine
   * Navigation im Blick. font-display statt der winzigen Grossschrift, dazu
   * ein Strich unter dem aktiven Tab. invite-v2.js tauscht bei jedem
   * Schrittwechsel die ganze class des <li> gegen entweder "text-ink" oder
   * "text-muted" aus (show() in invite-v2.js) - keine dritte Klasse bleibt
   * erhalten. Diese Regeln haengen deshalb bewusst nur an genau diesen beiden
   * Klassen, nicht an einer eigenen: sie greifen so unveraendert, ob das
   * Skript laeuft oder nicht.
   *
   * Im <li> steht vom Server nur Text. Laeuft ein Skript UND gibt es etwas zu
   * schalten, ruestet das Skript am Ende dieser Datei den Text zu einem
   * echten <button> auf - erst das macht den Tab fokussierbar und klickbar.
   * Die folgende Knopf-Regel greift also nur im aufgeruesteten Zustand; ohne
   * Skript trifft sie nichts, und das ist der Zweck.
   *
   * Farbe und Groesse haengen am <li>, nicht am <button>: der Knopf holt sich
   * die Farbe per color:inherit und die Schrift per font:inherit. Beide
   * Deklarationen sind noetig, ein Knopf bringt sonst seine eigene mit.
   */
  .wz-tabs [data-step-label] {
    position: relative;
    padding-bottom: .85rem;
    font-family: var(--font-display);
    font-size: 1.05rem;
    letter-spacing: .01em;
  }
  .wz-tabs [data-step-label] > button {
    display: block;
    /* Der Knopf deckt auch den Abstand bis zum Goldstrich ab. Vorher sass das
       padding allein am <li> und der Knopf endete am Text: die Schaltflaeche
       war kleiner als der Reiter, den sie zeichnet, und der Streifen direkt
       ueber dem Strich klickte nicht. Das negative margin nimmt genau zurueck,
       was das padding an Hoehe zufuegt - die Zeile steht also millimetergleich
       wie ohne Skript. */
    margin: 0 0 -.85rem;
    padding: 0 0 .85rem;
    border: 0; background: none;
    font: inherit; color: inherit; cursor: pointer;
    text-align: left;
  }
  /* Der Sichtbarkeitsring steht hier ausdruecklich, statt sich auf den Ring
     des Browsers zu verlassen: die Regel darueber setzt border und background
     zurueck, und wer das liest, entfernt beim naechsten Mal auch das outline,
     ohne zu merken, dass der Tab damit nur noch fuer die Maus existiert. */
  .wz-tabs [data-step-label] > button:focus-visible {
    outline: 2px solid var(--color-gold);
    outline-offset: 3px;
  }
  .wz-tabs [data-step-label].text-ink { color: var(--color-ink); }
  .wz-tabs [data-step-label].text-ink::after {
    content: '';
    position: absolute; left: 0; right: 0; bottom: -1px;
    height: 2px; background: var(--color-gold);
  }
  .wz-tabs [data-step-label].text-muted { color: var(--color-muted); }
  .wz-tab-num { margin-right: .4em; font-size: .8em; color: var(--color-gold); }
