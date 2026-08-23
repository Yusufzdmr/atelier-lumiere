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

  /*
   * Der Kasten: hinstellen, drehen, stapeln, wegnehmen.
   *
   * Dieselbe Regel wie oben - das Skript zeichnet nichts nach. Es schreibt in
   * die Vorschau genau die Eigenschaften, die Design::css() serverseitig
   * schreiben wuerde, nur als Inline-Stil, und der gewinnt gegen die Regel im
   * Stilblock. Speichern erzeugt danach dieselbe Karte noch einmal, diesmal
   * aus dem Dokument.
   *
   * Die Wahrheit ueber Ordnung und Bestand ist das versteckte Feld: fromPost()
   * baut die Ebenenliste aus dieser Kennungsreihe. Ohne Skript bleibt sie
   * stehen, wie der Server sie geschrieben hat - dann aendert sich nichts, und
   * niemand verliert eine Ebene daran, dass JavaScript ausfaellt.
   */
  var reihe = form.querySelector("[data-ebenen-reihe]");
  var liste = form.querySelector("[data-ebenen-liste]");

  // Kuvertebenen stehen nicht in der Vorschau - dort gibt es nur Seite und
  // Karte. Ein fehlender Knoten ist deshalb kein Fehler, sondern der Normalfall.
  var knoten = function (id) {
    return vorschau.querySelector(".d-el-" + id);
  };

  var wert = function (id, mass) {
    var feld = form.querySelector('[data-kasten="' + id + '"][data-mass="' + mass + '"]');
    if (!feld) return null;
    return feld.type === "checkbox" ? feld.checked : feld.value;
  };

  var zahl = function (id, mass) {
    var roh = parseInt(wert(id, mass), 10);
    return isNaN(roh) ? 0 : roh;
  };

  var stelle = function (id) {
    var el = knoten(id);
    if (!el) return;

    var anker = wert(id, "anchor") || "topleft";
    var hoehe = zahl(id, "h");
    var dreh  = zahl(id, "rotate");
    var sx    = wert(id, "flipx") ? "-1" : "1";
    var sy    = wert(id, "flipy") ? "-1" : "1";

    // Welche zwei Kanten geschrieben werden, sagt der Anker - und die andere
    // muss ausdruecklich auf auto, sonst bleibt die Regel aus dem Stilblock
    // stehen und die Ebene haengt an zwei Kanten gleichzeitig.
    if (anker.indexOf("right") >= 0) {
      el.style.left = "auto";
      el.style.right = zahl(id, "x") + "%";
    } else {
      el.style.right = "auto";
      el.style.left = zahl(id, "x") + "%";
    }
    if (anker.indexOf("bottom") === 0) {
      el.style.top = "auto";
      el.style.bottom = zahl(id, "y") + "%";
    } else {
      el.style.bottom = "auto";
      el.style.top = zahl(id, "y") + "%";
    }

    el.style.width = zahl(id, "w") + "%";
    el.style.height = hoehe > 0 ? hoehe + "%" : "auto";
    el.style.opacity = String(zahl(id, "opacity") / 100);
    el.style.transform = "rotate(" + dreh + "deg)"
      + (sx === "-1" || sy === "-1" ? " scale(" + sx + "," + sy + ")" : "");
  };

  form.querySelectorAll("[data-kasten]").forEach(function (feld) {
    var art = feld.type === "checkbox" || feld.tagName === "SELECT" ? "change" : "input";
    feld.addEventListener(art, function () {
      stelle(feld.getAttribute("data-kasten"));
    });
  });

  if (!liste || !reihe) return;

  /*
   * Neu stapeln heisst neu zaehlen: der z-Index IST die Position in der Liste
   * (Design::css schreibt index+1). Eine weggenommene Zeile zaehlt nicht mit -
   * sie steht auch nicht in der Reihe und ist nach dem Speichern fort.
   */
  var stapleNeu = function () {
    var kennungen = [];

    liste.querySelectorAll("[data-ebene]").forEach(function (zeile) {
      var id = zeile.getAttribute("data-ebene");
      var el = knoten(id);
      var stufe = zeile.querySelector("[data-ebene-stufe]");

      if (zeile.hasAttribute("data-weg")) {
        if (stufe) stufe.textContent = "—";
        return;
      }

      kennungen.push(id);
      if (el) el.style.zIndex = String(kennungen.length);
      if (stufe) stufe.textContent = "z " + kennungen.length;
    });

    reihe.value = kennungen.join(",");
  };

  liste.addEventListener("click", function (ereignis) {
    var knopf = ereignis.target.closest("button");
    if (!knopf) return;

    var zeile = knopf.closest("[data-ebene]");
    if (!zeile) return;

    if (knopf.hasAttribute("data-ebene-hinten") && zeile.previousElementSibling) {
      liste.insertBefore(zeile, zeile.previousElementSibling);
    }
    if (knopf.hasAttribute("data-ebene-vorn") && zeile.nextElementSibling) {
      liste.insertBefore(zeile.nextElementSibling, zeile);
    }

    /*
     * Wegnehmen ist ein Schalter, kein Schnitt: solange nicht gespeichert
     * wurde, holt ein zweiter Klick die Ebene zurueck. Ein unwiderrufliches
     * Loeschen mitten in einem langen Formular waere eine Falle - vierzehn
     * Ebenen, ein Fehlklick, und die Arbeit einer Stunde ist weg.
     */
    if (knopf.hasAttribute("data-ebene-weg")) {
      var weg = zeile.hasAttribute("data-weg");
      var el = knoten(zeile.getAttribute("data-ebene"));

      if (weg) {
        zeile.removeAttribute("data-weg");
        zeile.style.opacity = "";
        knopf.textContent = knopf.getAttribute("data-wort-weg");
        if (el) el.style.display = "";
      } else {
        zeile.setAttribute("data-weg", "");
        zeile.style.opacity = "0.45";
        knopf.textContent = knopf.getAttribute("data-wort-zurueck");
        if (el) el.style.display = "none";
      }
    }

    stapleNeu();
  });
})();
