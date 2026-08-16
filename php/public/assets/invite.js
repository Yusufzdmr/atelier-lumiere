/**
 * Einladungs-Assistent: Schritte, Vorschau, Preis, Gutschein.
 *
 * Das Formular funktioniert auch ohne dieses Skript – dann stehen alle
 * Schritte untereinander. Hier kommt nur die Bequemlichkeit dazu.
 */
(function () {
  "use strict";

  var form = document.querySelector("[data-wizard]");
  if (!form) return;

  var steps = Array.prototype.slice.call(form.querySelectorAll("[data-step]"));
  var labels = Array.prototype.slice.call(form.querySelectorAll("[data-step-label]"));
  var back = form.querySelector("[data-back]");
  var next = form.querySelector("[data-next]");
  var submit = form.querySelector("[data-submit]");
  var current = 0;

  /* ---------------------------- Schritte ---------------------------- */

  function show(index) {
    current = Math.max(0, Math.min(index, steps.length - 1));

    steps.forEach(function (step, i) {
      step.style.display = i === current ? "" : "none";
    });
    labels.forEach(function (label, i) {
      label.className = i === current ? "text-gold" : i < current ? "text-ink" : "text-muted";
    });

    if (back) back.style.visibility = current === 0 ? "hidden" : "visible";
    if (next) next.style.display = current === steps.length - 1 ? "none" : "";
    if (submit) submit.style.display = current === steps.length - 1 ? "" : "none";

    window.scrollTo({ top: form.getBoundingClientRect().top + window.scrollY - 120, behavior: "smooth" });
  }

  if (back) back.addEventListener("click", function () { show(current - 1); });
  if (next) next.addEventListener("click", function () { show(current + 1); });
  show(0);

  /* ---------------------------- Vorschau ---------------------------- */

  var stage = form.querySelector("[data-preview-stage]");
  var card = form.querySelector("[data-preview-card]");
  var brideOut = form.querySelector("[data-preview-bride-out]");
  var groomOut = form.querySelector("[data-preview-groom-out]");
  var dateOut = form.querySelector("[data-preview-date-out]");
  var seal = form.querySelector("[data-preview-seal]");
  var accents = form.querySelectorAll("[data-preview-accent], [data-preview-line]");
  var softs = form.querySelectorAll("[data-preview-soft]");

  function bind(selector, handler) {
    var field = form.querySelector(selector);
    if (field) field.addEventListener("input", handler);
    return field;
  }

  var bride = bind("[data-preview-bride]", paintNames);
  var groom = bind("[data-preview-groom]", paintNames);
  var date = bind("[data-preview-date]", paintDate);
  var time = bind("[data-preview-time]", paintDate);
  var slug = form.querySelector("[data-slug]");

  function paintNames() {
    if (brideOut) brideOut.textContent = (bride && bride.value.trim()) || "Ayşe";
    if (groomOut) groomOut.textContent = (groom && groom.value.trim()) || "Mehmet";

    // Vorschlag für die Adresse, solange niemand selbst etwas eingetragen hat
    if (slug && !slug.dataset.touched) {
      var parts = [(bride && bride.value) || "", (groom && groom.value) || ""];
      slug.value = parts
        .join("-")
        .toLowerCase()
        .replace(/[ğ]/g, "g").replace(/[ü]/g, "u").replace(/[ş]/g, "s")
        .replace(/[ı]/g, "i").replace(/[ö]/g, "o").replace(/[ç]/g, "c")
        .replace(/[äÄ]/g, "ae").replace(/[öÖ]/g, "oe").replace(/[üÜ]/g, "ue").replace(/ß/g, "ss")
        .replace(/[^a-z0-9]+/g, "-")
        .replace(/^-+|-+$/g, "");
    }
  }

  if (slug) {
    slug.addEventListener("input", function () {
      slug.dataset.touched = "1";
    });
  }

  function paintDate() {
    if (!dateOut) return;
    var value = date && date.value ? date.value : "";
    if (!value) {
      dateOut.textContent = "—";
      return;
    }
    var parts = value.split("-");
    dateOut.textContent = parts[2] + "." + parts[1] + "." + parts[0] + (time && time.value ? " · " + time.value : "");
  }

  /* ----------------------------- Design ----------------------------- */

  form.querySelectorAll("[data-theme-radio]").forEach(function (radio) {
    radio.addEventListener("change", function () {
      form.querySelectorAll("[data-theme-option]").forEach(function (option) {
        option.classList.remove("border-gold");
        option.classList.add("border-sand-deep");
      });
      var box = radio.closest("[data-theme-option]");
      if (box) {
        box.classList.add("border-gold");
        box.classList.remove("border-sand-deep");
      }

      var colors;
      try {
        colors = JSON.parse(radio.getAttribute("data-colors") || "{}");
      } catch (e) {
        return;
      }

      if (stage) stage.style.background = colors.bg;
      if (card) {
        card.style.background = colors.paper;
        card.style.color = colors.fg;
        card.style.borderColor = colors.edge;
        card.style.backgroundImage = colors.image ? "url(" + colors.image + ")" : "";
        card.style.backgroundSize = "cover";
        card.style.backgroundPosition = "center";
      }
      if (seal) {
        seal.style.background = colors.seal;
        seal.style.color = colors.sealText;
      }
      accents.forEach(function (element) {
        if (element.hasAttribute("data-preview-line")) element.style.background = colors.accent;
        else element.style.color = colors.accent;
      });
      softs.forEach(function (element) {
        element.style.color = colors.soft;
      });
    });
  });

  /* ------------------------- Zweite Feier ------------------------- */

  var type = form.querySelector("[data-event-type]");
  var second = form.querySelector("[data-second-event]");

  function paintEvents() {
    if (!second || !type) return;
    // „Mehrere Feiern“ ist der einzige Anlass mit zwei Terminen.
    second.style.display = type.value === "multi" ? "" : "none";
    paintPrice();
  }

  if (type) type.addEventListener("change", paintEvents);

  /* --------------------------- Abschnitte --------------------------- */

  var boxes = Array.prototype.slice.call(form.querySelectorAll("[data-section]"));
  var priceOut = form.querySelector("[data-price]");
  var priceLabel = form.querySelector("[data-price-label]");
  var couponField = form.querySelector("[data-coupon]");
  var couponNote = form.querySelector("[data-coupon-note]");
  var free = false;

  function paintPrice() {
    var sum = 79 + (type && type.value === "multi" ? 20 : 0);
    boxes.forEach(function (box) {
      if (box.checked) sum += Number(box.getAttribute("data-price") || 0);
    });

    var text = free ? "0 €" : sum + " €";
    if (priceOut) priceOut.textContent = text;
    if (priceLabel) priceLabel.textContent = text;

    // Felder nur zeigen, wenn der Abschnitt auch gewählt ist
    form.querySelectorAll("[data-needs]").forEach(function (block) {
      var key = block.getAttribute("data-needs");
      var box = form.querySelector('[data-section="' + key + '"]');
      block.style.display = box && box.checked ? "" : "none";
    });
  }

  boxes.forEach(function (box) {
    box.addEventListener("change", paintPrice);
  });

  /* --------------------------- Gutschein --------------------------- */

  if (couponField && couponNote) {
    var timer = null;

    couponField.addEventListener("input", function () {
      window.clearTimeout(timer);
      var value = couponField.value.trim();

      if (!value) {
        free = false;
        couponNote.textContent = couponNote.getAttribute("data-hint") || "";
        couponNote.className = "mt-2 text-[0.72rem] text-muted";
        paintPrice();
        return;
      }

      timer = window.setTimeout(function () {
        couponNote.textContent = couponNote.getAttribute("data-checking") || "…";

        fetch("/api/kupon", {
          method: "POST",
          headers: { "Content-Type": "application/json" },
          body: JSON.stringify({ code: value }),
        })
          .then(function (response) { return response.json(); })
          .then(function (result) {
            free = Boolean(result.ok);
            var key = result.ok ? "ok" : result.reason === "used" ? "used" : result.reason === "expired" ? "expired" : "bad";
            couponNote.textContent = couponNote.getAttribute("data-" + key) || "";
            couponNote.className = "mt-2 text-[0.72rem] " + (free ? "text-gold" : "text-muted");
            paintPrice();
          })
          .catch(function () {
            couponNote.textContent = couponNote.getAttribute("data-bad") || "";
          });
      }, 400);
    });
  }

  paintNames();
  paintDate();
  paintEvents();
  paintPrice();

})();

/**
 * Zeilen mit Plus-Knopf für Programm und Menü.
 *
 * Darunter liegt weiter das Textfeld, und abgeschickt wird auch weiter dessen
 * Inhalt – eine Zeile je Punkt, bei mehreren Spalten mit "|" dazwischen. Das
 * Feld verschwindet nur aus dem Blick. Wer ohne Skript kommt, sieht es wie
 * vorher und kann tippen; wer mit Skript kommt, sieht Felder und muss von
 * dem senkrechten Strich nie erfahren.
 */
(function () {
  "use strict";

  document.querySelectorAll("[data-repeater]").forEach(function (box) {
    var area = box.querySelector("textarea");
    if (!area) return;

    var columns = (box.getAttribute("data-columns") || "text").split(",");
    var rows = document.createElement("div");
    rows.className = "mt-3 space-y-3";

    area.hidden = true;
    box.appendChild(rows);

    /** Aus den Zeilen wieder den Text bauen, den der Server erwartet. */
    function sync() {
      var lines = [];
      rows.querySelectorAll("[data-row]").forEach(function (row) {
        var values = [];
        row.querySelectorAll("input").forEach(function (input) {
          values.push(input.value.trim());
        });
        // Eine Zeile, in der nichts steht, gehört nicht in die Einladung.
        if (values.join("") !== "") lines.push(values.join(" | "));
      });
      area.value = lines.join("\n");
    }

    function addRow(values) {
      var row = document.createElement("div");
      row.setAttribute("data-row", "");
      row.className = "flex items-center gap-3";

      columns.forEach(function (column, i) {
        var input = document.createElement("input");
        input.type = "text";
        input.value = values[i] || "";
        input.placeholder = box.getAttribute("data-placeholder-" + column) || "";
        // Die Uhrzeit ist kurz, der Programmpunkt bekommt den Rest.
        input.className =
          (column === "time" ? "w-24 shrink-0 " : "flex-1 ") +
          "border-b border-sand-deep bg-transparent px-0 py-2.5 text-[0.95rem] text-ink outline-none transition-colors placeholder:text-muted/50 focus:border-gold";
        input.addEventListener("input", sync);
        row.appendChild(input);
      });

      var remove = document.createElement("button");
      remove.type = "button";
      remove.textContent = "×";
      remove.title = box.getAttribute("data-remove") || "";
      remove.setAttribute("aria-label", remove.title);
      remove.className = "shrink-0 px-2 text-lg leading-none text-muted transition-colors hover:text-ink";
      remove.addEventListener("click", function () {
        row.remove();
        if (!rows.querySelector("[data-row]")) addRow([]);
        sync();
      });
      row.appendChild(remove);

      rows.appendChild(row);
      return row;
    }

    // Was schon dasteht, in Zeilen übersetzen – sonst eine leere zum Anfangen.
    var existing = area.value.split("\n").filter(function (line) {
      return line.trim() !== "";
    });
    if (existing.length === 0) {
      addRow([]);
    } else {
      existing.forEach(function (line) {
        addRow(line.split("|").map(function (part) { return part.trim(); }));
      });
    }

    var add = document.createElement("button");
    add.type = "button";
    add.textContent = box.getAttribute("data-add") || "+";
    add.className = "mt-4 text-[0.68rem] uppercase tracking-[0.18em] text-gold transition-colors hover:text-ink";
    add.addEventListener("click", function () {
      var row = addRow([]);
      var first = row.querySelector("input");
      if (first) first.focus();
    });
    box.appendChild(add);

    sync();
  });
})();

/**
 * Link kopieren – eigener Block.
 *
 * Das stand vorher im Assistenten, hinter dessen "kein Formular, dann nichts
 * tun". Auf der Seite nach dem Erstellen gibt es aber kein Formular mehr, nur
 * noch die beiden Knöpfe "Link kopieren" – und die taten deshalb nie etwas.
 *
 * navigator.clipboard gibt es nur in sicheren Kontexten; unter http auf einer
 * LAN-Adresse fehlt es. Dann der alte Weg über ein unsichtbares Feld, damit
 * der Knopf überall etwas tut.
 */
(function () {
  "use strict";

  function copy(text) {
    if (navigator.clipboard && window.isSecureContext) {
      return navigator.clipboard.writeText(text);
    }

    var helper = document.createElement("textarea");
    helper.value = text;
    helper.setAttribute("readonly", "");
    helper.style.position = "fixed";
    helper.style.top = "-1000px";
    document.body.appendChild(helper);
    helper.select();
    try {
      document.execCommand("copy");
    } catch (e) {
      /* Dann bleibt dem Paar das Markieren von Hand. */
    }
    document.body.removeChild(helper);
    return Promise.resolve();
  }

  document.querySelectorAll("[data-copy]").forEach(function (button) {
    button.addEventListener("click", function () {
      var before = button.textContent;
      copy(button.getAttribute("data-copy") || "").then(function () {
        button.textContent = "✓";
        setTimeout(function () { button.textContent = before; }, 1500);
      });
    });
  });
})();
