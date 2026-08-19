/*
 * Vorschau im Design-Editor.
 *
 * Das Skript zeichnet nichts nach: die Karte daneben ist dieselbe, die der Gast
 * sieht. Es aendert ausschliesslich CSS-Variablen und Textknoten. Keyframes
 * bleiben beim Server - sonst gaebe es zwei Wahrheiten, eine im Panel und eine
 * auf der Seite, und sie wuerden auseinanderlaufen.
 */
(function () {
  "use strict";

  var vorschau = document.querySelector("[data-design-preview]");
  var form = document.querySelector("[data-design-form]");
  if (!vorschau || !form) return;

  // Farbe: das Textfeld ist die Wahrheit, der Waehler schreibt hinein. So
  // ueberlebt ein rgba(), das der Waehler gar nicht darstellen kann.
  form.querySelectorAll("[data-farbfeld]").forEach(function (feld) {
    var marke = feld.getAttribute("data-farbfeld");
    var waehler = form.querySelector('[data-farbwahl="' + marke + '"]');

    var male = function () {
      vorschau.style.setProperty("--d-" + marke.toLowerCase(), feld.value.trim());
    };

    feld.addEventListener("input", function () {
      if (/^#[0-9a-fA-F]{6}$/.test(feld.value.trim()) && waehler) waehler.value = feld.value.trim();
      male();
    });

    if (waehler) {
      waehler.addEventListener("input", function () {
        feld.value = waehler.value;
        male();
      });
    }
  });

  // Schriftfamilie und Gewicht gehen ueber die Variablen der Schriftmarke.
  form.querySelectorAll("[data-schriftfeld]").forEach(function (feld) {
    feld.addEventListener("change", function () {
      vorschau.style.setProperty("--df-" + feld.getAttribute("data-schriftfeld"), '"' + feld.value + '"');
    });
  });

  form.querySelectorAll("[data-gewichtfeld]").forEach(function (feld) {
    feld.addEventListener("input", function () {
      vorschau.style.setProperty("--dfw-" + feld.getAttribute("data-gewichtfeld"), feld.value);
    });
  });

  // Fester Text: der Knoten in der Vorschau traegt die Klasse d-el-<id>.
  form.querySelectorAll("[data-textfeld]").forEach(function (feld) {
    feld.addEventListener("input", function () {
      var ziel = vorschau.querySelector(".d-el-" + feld.getAttribute("data-textfeld"));
      if (ziel) ziel.textContent = feld.value;
    });
  });
})();
