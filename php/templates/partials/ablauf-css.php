<?php
/**
 * Die Regeln fuer das <details> der uebrigen Ablauf-Zeilen - geteilt vom
 * Assistenten und vom Bearbeiten-Bildschirm. Eingebunden INNERHALB des
 * <style>-Blocks der jeweiligen Seite.
 */
?>
  /*
   * Die uebrigen, meist leeren Ablauf-Zeilen: reines <details>, kein Skript.
   * list-style entfernt die Standard-Markierung (Chrome/Safari zusaetzlich
   * ueber ::-webkit-details-marker), das + / - davor kommt aus content.
   */
  .wz-more { margin-top: .75rem; border: 1px solid var(--color-sand-deep); }
  .wz-more > summary {
    display: flex; align-items: center; gap: .5rem;
    padding: .75rem 1rem;
    cursor: pointer;
    list-style: none;
    font-size: .72rem; text-transform: uppercase; letter-spacing: .16em;
    color: var(--color-muted);
  }
  .wz-more > summary::-webkit-details-marker { display: none; }
  .wz-more > summary::before { content: '+'; color: var(--color-gold); font-size: 1rem; line-height: 1; }
  .wz-more[open] > summary::before { content: '\2013'; }
  .wz-more[open] > summary { border-bottom: 1px solid var(--color-sand-deep); }
  .wz-more-body { padding: .25rem 1rem 1rem; }
