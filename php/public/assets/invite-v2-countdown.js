/*
 * Der Countdown.
 *
 * Gerechnet wird hier und nicht auf dem Server: eine gerenderte Zahl waere
 * falsch, sobald die Seite eine Minute alt ist. Ohne dieses Skript steht
 * trotzdem das Datum da - der Server hat es schon gedruckt, und das ist die
 * Aussage, auf die es ankommt.
 *
 * Zwei Gestalten, ein Skript:
 *
 *   Zahl   ein Feld, "noch 34 Tage". Aendert sich taeglich, also reicht ein
 *          Blick pro Stunde.
 *   Uhr    vier Felder, Tage/Stunden/Minuten/Sekunden. Die laeuft im
 *          Sekundentakt, sonst waere die Sekundenanzeige eine Behauptung.
 *
 * Welche gemeint ist, sagt das Dokument und nicht eine Einstellung hier: die
 * Uhr bringt [data-countdown-hours] mit, die Zahl nicht. So bleibt die Wahl
 * dort, wo der Grafiker sie trifft (SectionRegistry: countdown/uhr).
 *
 * Die Sprache steht auch hier nicht: "Tage" bzw. "days" kaeme sonst ein
 * zweites Mal vor, einmal in PHP (DesignSections::countdown) und einmal hier
 * - zwei Quellen, die auseinanderlaufen koennen. Der Server schreibt das Wort
 * in die Vorlage, dieses Skript schreibt nur Zahlen.
 */
(function () {
  'use strict';

  var ziele = document.querySelectorAll('[data-countdown]');
  if (!ziele.length) return;

  /* Fuehrende Null, damit die Felder beim Zaehlen nicht in der Breite springen. */
  function zwei(n) {
    return (n < 10 ? '0' : '') + String(n);
  }

  function zeichne() {
    var jetzt = new Date();

    Array.prototype.forEach.call(ziele, function (el) {
      var datum = el.getAttribute('data-countdown');
      if (!datum) return;

      // Ohne Uhrzeit faengt der Tag um Mitternacht an. Das "T00:00:00" muss
      // stehen: ein blosses "2027-05-01" liest der Browser als UTC, ein
      // Datum mit Zeit als Ortszeit - der Unterschied ist bis zu ein Tag im
      // Ergebnis, und zwar genau am letzten.
      var ziel = new Date(datum.indexOf('T') === -1 ? datum + 'T00:00:00' : datum);
      var uebrig = ziel.getTime() - jetzt.getTime();
      if (isNaN(uebrig)) return;

      var stunden = el.querySelector('[data-countdown-hours]');

      /* ------------------------------ Die Uhr ----------------------------- */
      if (stunden) {
        // Nach dem Termin steht die Uhr auf Null statt ins Minus zu laufen.
        if (uebrig < 0) uebrig = 0;

        var sek = Math.floor(uebrig / 1000);
        var tage = Math.floor(sek / 86400);

        setze(el, 'days', String(tage));
        setze(el, 'hours', zwei(Math.floor(sek / 3600) % 24));
        setze(el, 'minutes', zwei(Math.floor(sek / 60) % 60));
        setze(el, 'seconds', zwei(sek % 60));
        return;
      }

      /* ----------------------------- Die Zahl ----------------------------- */
      var spanne = el.querySelector('[data-countdown-days]');
      if (!spanne) return;

      var tageAuf = Math.ceil(uebrig / 86400000);
      if (tageAuf < 0) return;

      var label = spanne.getAttribute('data-label') || '';
      spanne.textContent = String(tageAuf) + (label ? ' ' + label : '');
    });
  }

  function setze(el, name, wert) {
    var feld = el.querySelector('[data-countdown-' + name + ']');
    if (feld) feld.textContent = wert;
  }

  zeichne();

  // Die Uhr im Sekundentakt, die blosse Tageszahl stuendlich. Ein Sekunden-
  // takt fuer eine Zahl, die sich taeglich aendert, waere 3599 Rechnungen
  // umsonst - auf einem Telefon, das die Einladung offen liegen laesst,
  // merkt man das an der Batterie.
  var uhr = document.querySelector('[data-countdown-hours]');
  window.setInterval(zeichne, uhr ? 1000 : 3600000);

  // Zurueck aus dem Hintergrund: Browser halten Timer in einem schlafenden
  // Tab an. Ohne diesen Blick stuende beim Aufwachen die Zeit von vorhin da.
  document.addEventListener('visibilitychange', function () {
    if (!document.hidden) zeichne();
  });
})();
