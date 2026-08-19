/*
 * Der Countdown.
 *
 * Gerechnet wird hier und nicht auf dem Server: eine gerenderte Zahl waere
 * falsch, sobald die Seite eine Minute alt ist. Ohne dieses Skript steht
 * trotzdem das Datum da - der Server hat es schon gedruckt, und das ist die
 * Aussage, auf die es ankommt.
 *
 * Die Sprache steht hier nicht: "Tage" bzw. "days" kaeme sonst ein zweites
 * Mal vor, einmal in PHP (DesignSections::countdown) und einmal hier - zwei
 * Quellen, die auseinanderlaufen koennen. Der Server schreibt das Wort ins
 * data-label des Spans, dieses Skript liest es nur und schreibt die Zahl in
 * denselben Span - genau wie admin.js es mit anderen data-*-Attributen tut
 * (Nachkommen-Selektor, kein Attribut auf dem Absatz selbst).
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

      var spanne = el.querySelector('[data-countdown-days]');
      if (!spanne) return;

      var ziel = new Date(datum + 'T00:00:00');
      var tage = Math.ceil((ziel - jetzt) / 86400000);
      if (isNaN(tage) || tage < 0) return;

      // Nur Tage: Stunden und Minuten laden zum Nachladen ein, und eine
      // Einladung ist kein Wecker.
      var label = spanne.getAttribute('data-label') || '';
      spanne.textContent = String(tage) + (label ? ' ' + label : '');
    });
  }

  zeichne();
  // Einmal pro Stunde reicht - der Wert aendert sich taeglich.
  window.setInterval(zeichne, 3600000);
})();
