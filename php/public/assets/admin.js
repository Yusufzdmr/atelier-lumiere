/**
 * Kleine Hilfen im Adminbereich.
 *
 * Bewusst wenig: Der Adminbereich ist ein Formular-Werkzeug. Alles, was ohne
 * JavaScript funktioniert, bleibt auch ohne JavaScript bedienbar.
 */
(function () {
  "use strict";

  /* -------------------- Löschen versehentlich verhindern -------------------- */
  document.querySelectorAll("[data-confirm]").forEach(function (element) {
    element.addEventListener("click", function (event) {
      var message = element.getAttribute("data-confirm") || "?";
      if (!window.confirm(message)) {
        event.preventDefault();
      }
    });
  });

  /* ------------------------- Zeichenzähler für SEO ------------------------- */
  // Google schneidet lange Titel ab; der Zähler zeigt es, bevor es passiert.
  document.querySelectorAll("[data-counter]").forEach(function (field) {
    var max = Number(field.getAttribute("data-counter")) || 60;
    var min = Number(field.getAttribute("data-counter-min")) || 0;
    var output = document.querySelector('[data-counter-for="' + field.id + '"]');
    if (!output) return;

    function paint() {
      var length = field.value.length;
      output.textContent = length + "/" + max;
      output.className =
        "text-[0.68rem] " +
        (length === 0 ? "text-muted" : length > max ? "text-red-700" : length < min ? "text-gold" : "text-muted");

      var preview = document.querySelector('[data-preview-for="' + field.id + '"]');
      if (preview) preview.textContent = field.value || preview.getAttribute("data-empty") || "";
    }

    field.addEventListener("input", paint);
    paint();
  });

  /* ---------------------- Kopieren (Codes, Links) ---------------------- */
  document.querySelectorAll("[data-copy]").forEach(function (button) {
    button.addEventListener("click", function () {
      var value = button.getAttribute("data-copy") || "";
      if (!navigator.clipboard) return;
      navigator.clipboard.writeText(value).then(function () {
        var before = button.textContent;
        button.textContent = "✓";
        setTimeout(function () {
          button.textContent = before;
        }, 1500);
      });
    });
  });
})();
