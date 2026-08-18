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
  /*
   * „Mit diesen Bewegungen ansehen": baut die Adresse der Designvorschau aus
   * den gerade eingestellten Listen. Die Vorschau nimmt die sechs Achsen als
   * Parameter entgegen, also braucht es kein Speichern, um etwas auszuprobieren
   * – und ein Fehlgriff kostet kein gespeichertes Thema.
   */
  document.querySelectorAll("[data-theme-try]").forEach(function (link) {
    var id = link.getAttribute("data-theme-try");
    var form = document.querySelector('[data-theme-form][data-theme-id="' + id + '"]');
    if (!form) return;

    link.addEventListener("click", function (event) {
      event.preventDefault();
      var parts = [];
      ["intro", "idle", "animation", "nameAnimation", "particle", "reveal", "scene"].forEach(function (name) {
        var field = form.querySelector('[name="' + name + '"]');
        if (field && field.value) parts.push(name + "=" + encodeURIComponent(field.value));
      });
      var base = link.getAttribute("data-base") || "/de/designs/" + id;
      window.open(base + (parts.length ? "?" + parts.join("&") : ""), "_blank", "noopener");
    });
  });

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
        // background (Kurzform) statt backgroundColor wuerde das Wachsrelief
        // aus dem Stylesheet mitloeschen – es steckt in background-image.
        seal.style.backgroundColor = value("seal");
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

  /* -------------------------- Toast -------------------------- */
  // Kısa süreli bildirim: kaydet-yenile-oku döngüsü yerine sağ alt köşede
  // üç saniye görünür. Faz 2'de AJAX cevaplarını da bu bileşen gösterecek.
  var toastHost = document.getElementById("toast-host");

  window.adminToast = function (message, kind) {
    if (!toastHost || !message) return;

    var toast = document.createElement("div");
    var isError = kind === "error";
    var isInfo = kind === "info";
    var border = isError ? "border-red-700 text-red-800 bg-red-50"
               : isInfo  ? "border-sand-deep text-muted bg-cream"
                         : "border-gold text-ink bg-sand/40";

    toast.className =
      "pointer-events-auto flex items-center gap-3 border px-5 py-3 " +
      "text-[0.88rem] shadow-sm transition-all duration-200 " +
      "translate-y-2 opacity-0 " + border;

    var mark = document.createElement("span");
    mark.textContent = isError ? "!" : "✓";
    mark.className = isError ? "text-red-700" : isInfo ? "text-muted" : "text-gold";
    toast.appendChild(mark);

    var text = document.createElement("span");
    text.textContent = message;
    toast.appendChild(text);

    toastHost.appendChild(toast);

    // Bir sonraki paint'te transition başlasın diye requestAnimationFrame.
    requestAnimationFrame(function () {
      toast.classList.remove("translate-y-2", "opacity-0");
    });

    setTimeout(function () {
      toast.classList.add("translate-y-2", "opacity-0");
      setTimeout(function () { toast.remove(); }, 220);
    }, 3000);
  };

  /* Sayfa yüklenirken ?gespeichert=... varsa toast tetikle ve URL'yi temizle. */
  (function () {
    var params = new URLSearchParams(location.search);
    if (!params.has("gespeichert")) return;

    var val = params.get("gespeichert");
    var body = document.body;
    var kind = "ok";
    var msg  = body.getAttribute("data-toast-ok") || "";

    if (val === "geloescht") {
      kind = "info";
      msg  = body.getAttribute("data-toast-deleted") || msg;
    }

    window.adminToast(msg, kind);

    // Geri tuşu tekrar toast atmasın.
    params.delete("gespeichert");
    var query = params.toString();
    history.replaceState({}, "", location.pathname + (query ? "?" + query : ""));
  })();
})();

/* ---------------------- Themen: Vorschau in Gerätebreiten ---------------- */
// Eine Einladung wird öfter auf einem Handy geöffnet als am Bildschirm.
// Deshalb lässt sich die Vorschau auf drei Breiten stellen, statt zu hoffen.
(function () {
  "use strict";

  document.querySelectorAll("[data-theme-devices]").forEach(function (bar) {
    var id = bar.getAttribute("data-theme-devices");
    var preview = document.querySelector('[data-theme-preview="' + id + '"]');
    if (!preview) return;

    var buttons = bar.querySelectorAll("[data-theme-width]");

    function choose(button) {
      var width = button.getAttribute("data-theme-width") || "";
      preview.style.maxWidth = width;
      preview.style.marginLeft = "auto";
      preview.style.marginRight = "auto";

      buttons.forEach(function (other) {
        var active = other === button;
        other.classList.toggle("border-gold", active);
        other.classList.toggle("text-ink", active);
        other.classList.toggle("border-sand-deep", !active);
        other.classList.toggle("text-muted", !active);
      });
    }

    buttons.forEach(function (button) {
      button.addEventListener("click", function () { choose(button); });
    });
  });
})();
