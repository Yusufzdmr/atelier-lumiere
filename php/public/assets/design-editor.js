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

  (function () {
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

  /*
   * Die drei Spalten: links waehlen, rechts erscheint die Tafel.
   *
   * Alle Tafeln stehen im Markup, sichtbar ist eine. Das Skript blendet nur
   * um - es laedt nichts nach und schickt nichts weg. Eine Tafel, die man
   * nicht sieht, traegt ihre Werte trotzdem, und beim Absenden geht alles
   * gemeinsam mit; genau deshalb verliert das Umschalten nichts.
   *
   * Ohne Skript steht die Tafel der Vorlage offen und die Abschnittstafeln
   * bleiben zu. Das ist wenig, aber es ist nicht kaputt: die Liste links ist
   * dann eine Liste, und gespeichert wird trotzdem alles.
   */
  var secListe = form.querySelector("[data-sec-liste]");
  var secReihe = form.querySelector("[data-sec-reihe]");

  var tafeln = form.querySelectorAll("[data-panel]");

  var zeigeTafel = function (name) {
    tafeln.forEach(function (tafel) {
      tafel.hidden = tafel.getAttribute("data-panel") !== name;
    });
  };

  var markiere = function (zeile) {
    form.querySelectorAll("[data-sec-zeile]").forEach(function (z) {
      z.removeAttribute("data-aktiv");
    });
    zeile.setAttribute("data-aktiv", "");
  };

  // Anfangs steht die Tafel der Vorlage offen; die Abschnittstafeln sind im
  // Markup bereits hidden. Hier wird nichts umgeschaltet, damit ein Fehler im
  // Skript nicht die einzige sichtbare Tafel wegnimmt.

  form.addEventListener("click", function (ereignis) {
    var knopf = ereignis.target.closest("[data-sec-waehl]");
    if (!knopf) return;

    var zeile = knopf.closest("[data-sec-zeile]");
    if (!zeile) return;

    var welche = zeile.getAttribute("data-sec-zeile");
    markiere(zeile);
    zeigeTafel(welche === "thema" ? "thema" : "sec-" + welche);
  });

  if (!secListe || !secReihe) return; // ab hier nur noch die Abschnittsliste

  /*
   * Die Reihe neu schreiben. Sie ist die Wahrheit ueber Ordnung UND Bestand:
   * fromPost() liest die Abschnitte in dieser Reihenfolge, und wer nicht
   * darin steht, ist geloescht.
   *
   * Die NUMMER bleibt an ihrer Zeile kleben, auch wenn die Zeile wandert -
   * die Feldnamen tragen sie (sec_title_de_3). Wuerde beim Schieben
   * umnummeriert, verlore jedes Feld dabei seinen Wert.
   */
  var reiheNeu = function () {
    var nummern = [];

    secListe.querySelectorAll("[data-sec-zeile]").forEach(function (zeile) {
      if (zeile.hasAttribute("data-weg")) return;
      nummern.push(zeile.getAttribute("data-sec-zeile"));
    });

    secReihe.value = nummern.join(",");
  };

  secListe.addEventListener("click", function (ereignis) {
    var knopf = ereignis.target.closest("button");
    if (!knopf) return;

    var zeile = knopf.closest("[data-sec-zeile]");
    if (!zeile) return;

    if (knopf.hasAttribute("data-sec-hoch") && zeile.previousElementSibling) {
      secListe.insertBefore(zeile, zeile.previousElementSibling);
    }
    if (knopf.hasAttribute("data-sec-runter") && zeile.nextElementSibling) {
      secListe.insertBefore(zeile.nextElementSibling, zeile);
    }

    /*
     * Wegnehmen ist ein Schalter, kein Schnitt: solange nicht gespeichert
     * wurde, holt ein zweiter Klick den Abschnitt zurueck. Ein
     * unwiderrufliches Loeschen mitten in einem langen Formular waere eine
     * Falle - ein Fehlklick, und der Ablauf mit zwoelf Zeilen ist weg.
     */
    if (knopf.hasAttribute("data-sec-weg")) {
      var weg = zeile.hasAttribute("data-weg");

      if (weg) {
        zeile.removeAttribute("data-weg");
        knopf.textContent = knopf.getAttribute("data-wort-weg");
      } else {
        zeile.setAttribute("data-weg", "");
        knopf.textContent = knopf.getAttribute("data-wort-zurueck");
      }
    }

    reiheNeu();
  });

  /*
   * Der Titel steht an zwei Stellen: im Feld rechts und als Name der Zeile
   * links. Ohne diese Zeile heisst der Abschnitt in der Liste noch "(ohne
   * Titel)", waehrend rechts sein Name schon dasteht - und man sucht, warum.
   */
  form.querySelectorAll("[data-sec-titel]").forEach(function (feld) {
    feld.addEventListener("input", function () {
      var zeile = secListe.querySelector('[data-sec-zeile="' + feld.getAttribute("data-sec-titel") + '"]');
      if (!zeile) return;
      var greifer = zeile.querySelector("[data-sec-waehl]");
      if (!greifer) return;
      var klein = greifer.querySelector("small");
      greifer.childNodes[0].nodeValue = feld.value.trim() !== "" ? feld.value : " ";
      if (klein) greifer.appendChild(klein);
    });
  });

  // Dieselbe Doppelung bei der Gestalt: sie steht klein unter dem Namen.
  form.querySelectorAll("[data-sec-gestalt]").forEach(function (feld) {
    feld.addEventListener("change", function () {
      var zeile = secListe.querySelector('[data-sec-zeile="' + feld.getAttribute("data-sec-gestalt") + '"]');
      if (!zeile) return;
      var klein = zeile.querySelector("[data-sec-waehl] small");
      if (!klein) return;
      var art = klein.textContent.split("·")[0].trim();
      klein.textContent = art + " · " + feld.options[feld.selectedIndex].textContent.trim();
    });
  });


  /*
   * Die Art wechseln, ohne zu speichern.
   *
   * Was ein Abschnitt anbieten kann, haengt an seiner Art: der Ablauf kennt
   * den Zeitstrahl, der Ort den Kartenlink. Bis hierher musste man erst
   * speichern, um zu sehen, was die neue Art ueberhaupt hat - und wer eine
   * Vorlage baut, wechselt die Art fuenfmal, bevor sie sitzt.
   *
   * Die Gestalten kommen aus dem Katalog, den der Server als JSON mitgibt.
   * Eine zweite Liste hier waere eine zweite Wahrheit, und die hier gewinnt
   * beim Ansehen, waehrend die im PHP beim Drucken gewinnt - so entstehen
   * Knoepfe, die nichts tun.
   */
  var katalogKnoten = document.querySelector("[data-sec-katalog]");
  var katalog = {};

  if (katalogKnoten) {
    try {
      katalog = JSON.parse(katalogKnoten.textContent);
    } catch (fehler) {
      katalog = {};
    }
  }

  var gestaltenNeu = function (nummer, art) {
    var wahl = form.querySelector('[data-sec-gestalt="' + nummer + '"]');
    if (!wahl) return;

    var vorher = wahl.value;
    var liste = katalog[art] || { "default": "default" };

    wahl.textContent = "";
    Object.keys(liste).forEach(function (kennung) {
      var option = document.createElement("option");
      option.value = kennung;
      option.textContent = liste[kennung];
      // Beim Wechsel der Art bleibt die Gestalt stehen, wenn es sie auch
      // dort gibt - "gross" heisst beim Ort und beim Countdown etwas
      // anderes, aber beide Male ist es die, die jemand gewaehlt hat.
      if (kennung === vorher) option.selected = true;
      wahl.appendChild(option);
    });
  };

  var eigenesNeu = function (tafel, art) {
    tafel.querySelectorAll("[data-fuer-art]").forEach(function (kasten) {
      kasten.hidden = kasten.getAttribute("data-fuer-art") !== art;
    });
  };

  form.querySelectorAll("[data-sec-art-feld]").forEach(function (feld) {
    feld.addEventListener("change", function () {
      var nummer = feld.getAttribute("data-sec-art-feld");
      var tafel = form.querySelector('[data-panel="sec-' + nummer + '"]');

      gestaltenNeu(nummer, feld.value);
      if (tafel) eigenesNeu(tafel, feld.value);

      // Die Zeile links traegt die Art unter dem Namen.
      var zeile = form.querySelector('[data-sec-zeile="' + nummer + '"] [data-sec-waehl] small');
      if (zeile) zeile.textContent = feld.value;
    });
  });

  /*
   * Der erste Schritt beim Anlegen: WAS soll der Abschnitt zeigen. Die
   * Kennung schlaegt das Skript vor, statt sie zu verlangen - sie ist eine
   * technische Notwendigkeit (im Stilblock adressierbar sein) und keine
   * Entscheidung, die jemand treffen will. Aendern kann man sie trotzdem.
   */
  form.querySelectorAll("[data-sec-art]").forEach(function (karte) {
    karte.addEventListener("click", function () {
      var nummer = karte.getAttribute("data-fuer");
      var art = karte.getAttribute("data-sec-art");
      var feld = form.querySelector('[data-sec-art-feld="' + nummer + '"]');
      var kennung = form.querySelector('[data-sec-kennung="' + nummer + '"]');

      karte.parentNode.querySelectorAll("[data-sec-art]").forEach(function (k) {
        k.removeAttribute("data-aktiv");
      });
      karte.setAttribute("data-aktiv", "");

      if (feld) {
        feld.value = art;
        feld.dispatchEvent(new Event("change"));
      }

      if (kennung && kennung.value.trim() === "") {
        // Schon vergeben? Dann eine Zahl dahinter. Zwei Abschnitte mit
        // derselben Kennung waeren im Stilblock ein und derselbe.
        var genommen = {};
        form.querySelectorAll("[data-sec-kennung]").forEach(function (k) {
          if (k !== kennung && k.value.trim() !== "") genommen[k.value.trim()] = true;
        });

        var vorschlag = art;
        var zaehler = 2;
        while (genommen[vorschlag]) {
          vorschlag = art + "-" + zaehler;
          zaehler++;
        }
        kennung.value = vorschlag;
      }
    });
  });
})();
