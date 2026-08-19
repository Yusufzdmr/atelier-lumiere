/*
 * Der Countdown.
 *
 * Gerechnet wird hier und nicht auf dem Server: eine gerenderte Zahl waere
 * falsch, sobald die Seite eine Minute alt ist. Ohne dieses Skript steht
 * trotzdem das Datum da - der Server hat es schon gedruckt, und das ist die
 * Aussage, auf die es ankommt.
 */
(function () {
  'use strict';

  var ziele = document.querySelectorAll('[data-countdown]');
  if (!ziele.length) return;

  function zeichne() {
    var jetzt = new Date();

    Array.prototype.forEach.call(ziele, function (el) {
      var datum = el.getAttribute('data-countdown');
      if (!datum) return;

      var ziel = new Date(datum + 'T00:00:00');
      var tage = Math.ceil((ziel - jetzt) / 86400000);
      if (isNaN(tage) || tage < 0) return;

      // Nur Tage: Stunden und Minuten laden zum Nachladen ein, und eine
      // Einladung ist kein Wecker.
      el.setAttribute('data-days', String(tage));
    });
  }

  zeichne();
  // Einmal pro Stunde reicht - der Wert aendert sich taeglich.
  window.setInterval(zeichne, 3600000);
})();
