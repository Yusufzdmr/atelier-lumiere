/**
 * Gästeliste: Links kopieren und Löschen bestätigen.
 *
 * Ohne dieses Skript bleibt die Seite bedienbar – die Links stehen im Klartext
 * da und lassen sich markieren. Das Skript spart nur Handgriffe.
 */
(function () {
  "use strict";

  /* ------------------------------- Kopieren ------------------------------- */
  document.querySelectorAll("[data-copy]").forEach(function (button) {
    var original = button.textContent;

    button.addEventListener("click", function () {
      var text = button.getAttribute("data-copy") || "";

      function done() {
        // Kurz zurückmelden: sonst weiß niemand, ob der Klick angekommen ist.
        button.textContent = document.documentElement.lang.startsWith("de") ? "Kopiert" : "Copied";
        window.setTimeout(function () {
          button.textContent = original;
        }, 1800);
      }

      if (navigator.clipboard && window.isSecureContext) {
        navigator.clipboard.writeText(text).then(done, fallback);
      } else {
        fallback();
      }

      // Ohne HTTPS gibt es die Zwischenablage-Schnittstelle nicht.
      function fallback() {
        var field = document.createElement("textarea");
        field.value = text;
        field.setAttribute("readonly", "");
        field.style.position = "fixed";
        field.style.opacity = "0";
        document.body.appendChild(field);
        field.select();
        try {
          document.execCommand("copy");
          done();
        } catch (error) {
          window.prompt(document.documentElement.lang.startsWith("de") ? "Kopieren:" : "Copy:", text);
        }
        document.body.removeChild(field);
      }
    });
  });

  /* ------------------------- Versehentliches Löschen ---------------------- */
  document.querySelectorAll("[data-confirm]").forEach(function (element) {
    element.addEventListener("click", function (event) {
      if (!window.confirm(element.getAttribute("data-confirm") || "?")) {
        event.preventDefault();
      }
    });
  });
})();
