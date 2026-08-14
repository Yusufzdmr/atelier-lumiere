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

  /* ------------------------- Themen: Vorschau ------------------------- */
  // Farbe ändern und sofort sehen, was das Paar später sieht – sonst müsste
  // man für jede Nuance speichern und neu laden.
  document.querySelectorAll("[data-theme-form]").forEach(function (form) {
    var id = form.getAttribute("data-theme-id");
    var preview = document.querySelector('[data-theme-preview="' + id + '"]');
    if (!preview) return;

    var card = preview.querySelector(".t-card");
    var seal = preview.querySelector(".t-seal");
    var amp = preview.querySelector(".t-name span:nth-child(2)");
    var line = preview.querySelector(".t-card .h-px");
    var rsvp = preview.querySelector(".t-card .inline-block");
    var dates = preview.querySelectorAll(".t-date, .t-card .tracking-\\[0\\.3em\\]");
    var envelope = document.querySelector('[data-theme-preview="' + id + '"] ~ div .t-envelope');

    function value(name) {
      var field = form.querySelector('[data-theme-field="' + name + '"]');
      return field ? field.value.trim() : "";
    }

    function paint() {
      preview.style.background = value("bg");
      if (card) {
        card.style.background = value("paper");
        card.style.color = value("fg");
        card.style.borderColor = value("paperEdge");
      }
      if (seal) {
        seal.style.background = value("seal");
        seal.style.color = value("sealText");
      }
      if (amp) amp.style.color = value("accent");
      if (line) line.style.background = value("accent");
      if (rsvp) {
        rsvp.style.borderColor = value("accent");
        rsvp.style.color = value("accent");
      }
      dates.forEach(function (element) {
        element.style.color = value("soft");
      });
      if (envelope) {
        envelope.style.background = value("envelope");
        envelope.style.borderColor = value("envelopeEdge");
      }
    }

    // Textfeld und Farbwähler halten sich gegenseitig aktuell.
    form.querySelectorAll("[data-color-picker]").forEach(function (picker) {
      var name = picker.getAttribute("data-color-picker");
      var field = form.querySelector('[data-theme-field="' + name + '"]');
      if (!field) return;

      picker.addEventListener("input", function () {
        field.value = picker.value;
        paint();
      });
      field.addEventListener("input", function () {
        if (/^#[0-9a-fA-F]{6}$/.test(field.value.trim())) picker.value = field.value.trim();
        paint();
      });
    });

    paint();

    /* Animation zur Probe abspielen */
    var play = document.querySelector('[data-theme-play="' + id + '"]');
    if (play && card) {
      play.addEventListener("click", function () {
        var kind = (form.querySelector("[data-theme-animation]") || {}).value || "seal";
        var speed = Number((form.querySelector("[data-theme-speed]") || {}).value || 1200);
        var delay = Number((form.querySelector("[data-theme-delay]") || {}).value || 0);

        var frames = {
          seal: [
            { opacity: 0, transform: "translateY(18px) scale(.97)" },
            { opacity: 1, transform: "none" },
          ],
          fade: [{ opacity: 0 }, { opacity: 1 }],
          curtain: [
            { clipPath: "inset(0 50% 0 50%)" },
            { clipPath: "inset(0 0 0 0)" },
          ],
          petals: [
            { opacity: 0, transform: "rotate(-2deg) translateY(24px)" },
            { opacity: 1, transform: "none" },
          ],
          none: [{ opacity: 1 }, { opacity: 1 }],
        };

        card.animate(frames[kind] || frames.seal, {
          duration: Math.max(1, speed),
          delay: delay,
          easing: "cubic-bezier(.16,1,.3,1)",
          fill: "both",
        });
      });
    }
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
