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

  // Auch von aussen gebraucht: Rueckgaengig zieht beide Listen nach.
  var stapleNeu = function () {};

  (function () {
    if (!liste || !reihe) return;

  /*
   * Neu stapeln heisst neu zaehlen: der z-Index IST die Position in der Liste
   * (Design::css schreibt index+1). Eine weggenommene Zeile zaehlt nicht mit -
   * sie steht auch nicht in der Reihe und ist nach dem Speichern fort.
   */
  stapleNeu = function () {
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

  /*
   * Jedes Mal frisch fragen, nicht einmal einsammeln.
   *
   * Eine Liste, die beim Laden entsteht, kennt die Tafel nicht, die beim
   * Verdoppeln dazukommt - und dann versteckt "zeige sec-6" alle anderen,
   * waehrend sec-6 selbst versteckt bleibt. Nichts ist zu sehen, und das
   * sieht aus wie ein leerer Editor.
   */
  var zeigeTafel = function (name) {
    form.querySelectorAll("[data-panel]").forEach(function (tafel) {
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

  /*
   * Karte oder Geraet.
   *
   * Die Karte ist die lebende: sie folgt jedem Tastendruck. Die drei Geraete
   * zeigen die ganze oeffentliche Seite in einem Rahmen - und damit den
   * GESPEICHERTEN Stand, denn der Rahmen holt sich die Seite vom Server.
   *
   * Der Rahmen entsteht beim ersten Klick und nicht im Markup: sonst laedt
   * jeder Aufruf des Editors die Einladung samt Kuvertfilm mit, auch wenn
   * niemand hinsieht.
   *
   * Verkleinert statt abgeschnitten: ein Schreibtisch ist 1280 breit und die
   * Spalte ist es nicht. Die Hoehe wird mitgerechnet, sonst stuende unter
   * dem verkleinerten Rahmen ein Loch in seiner vollen Hoehe.
   */
  var geraete = form.querySelectorAll("[data-ansicht]");
  var rahmen  = form.querySelector("[data-ansicht-rahmen]");
  var karte   = vorschau;
  var hinweisAnsicht = form.querySelector("[data-ansicht-hinweis]");

  if (geraete.length && rahmen) {
    var worte = {
      karte: hinweisAnsicht ? hinweisAnsicht.textContent.trim() : "",
      seite: rahmen.getAttribute("data-wort-seite") || "Der Rahmen zeigt den gespeicherten Stand."
    };

    var passeAn = function (breite) {
      var kind = rahmen.querySelector("iframe");
      if (!kind) return;

      var platz = rahmen.clientWidth;
      var faktor = Math.min(1, platz / breite);
      var hoehe = Math.round(breite * 1.9);

      kind.style.width = breite + "px";
      kind.style.height = hoehe + "px";
      kind.style.transform = "scale(" + faktor + ")";
      rahmen.style.height = Math.round(hoehe * faktor) + "px";
    };

    geraete.forEach(function (knopf) {
      knopf.addEventListener("click", function () {
        geraete.forEach(function (k) { k.removeAttribute("data-aktiv"); });
        knopf.setAttribute("data-aktiv", "");

        var welche = knopf.getAttribute("data-ansicht");

        if (welche === "karte") {
          karte.hidden = false;
          rahmen.hidden = true;
          if (hinweisAnsicht) hinweisAnsicht.textContent = worte.karte;
          return;
        }

        karte.hidden = true;
        rahmen.hidden = false;

        if (!rahmen.querySelector("iframe")) {
          var kind = document.createElement("iframe");
          kind.setAttribute("loading", "lazy");
          kind.src = rahmen.getAttribute("data-adresse");
          rahmen.appendChild(kind);
        }

        passeAn(parseInt(welche, 10));
        if (hinweisAnsicht) hinweisAnsicht.textContent = worte.seite;
      });
    });

    window.addEventListener("resize", function () {
      var aktiv = form.querySelector("[data-ansicht][data-aktiv]");
      if (!aktiv || aktiv.getAttribute("data-ansicht") === "karte") return;
      passeAn(parseInt(aktiv.getAttribute("data-ansicht"), 10));
    });
  }

  /*
   * Verdoppeln.
   *
   * Zwei Textbloecke, zwei Ablaeufe fuer zwei Tage, derselbe Abschnitt in
   * einer anderen Gestalt zum Vergleichen - lauter Faelle, in denen man von
   * etwas Bestehendem ausgehen will statt neu anzufangen. Vorher hiess das:
   * anlegen und sieben Felder abtippen.
   *
   * Die Kopie bekommt eine NEUE Nummer, und zwar eine hoehere als jede
   * vorhandene. Die Nummer ist die Kennung einer Zeile im Formular
   * (sec_title_de_3); zwei Zeilen mit derselben Nummer waeren beim Absenden
   * eine einzige, und die zweite ueberschriebe die erste still.
   *
   * cloneNode kopiert Attribute, nicht Zustaende: was jemand seit dem Laden
   * getippt oder angehakt hat, steht in der Eigenschaft und nicht im
   * Attribut. Deshalb werden Werte und Haken danach von Hand nachgezogen -
   * sonst kopiert man den Stand von vor zehn Minuten.
   */
  var naechsteNummer = function () {
    var groesste = -1;

    form.querySelectorAll("[data-sec-zeile]").forEach(function (zeile) {
      var nummer = parseInt(zeile.getAttribute("data-sec-zeile"), 10);
      if (!isNaN(nummer) && nummer > groesste) groesste = nummer;
    });

    return groesste + 1;
  };

  var umnummerieren = function (wurzel, alt, neu) {
    var alle = wurzel.querySelectorAll("*");
    var enden = new RegExp("_" + alt + "$");

    Array.prototype.forEach.call(alle, function (knoten) {
      var name = knoten.getAttribute("name");
      if (name && enden.test(name)) {
        knoten.setAttribute("name", name.replace(enden, "_" + neu));
      }

      ["data-sec-titel", "data-sec-gestalt", "data-sec-art-feld", "data-sec-kennung", "data-fuer"]
        .forEach(function (merkmal) {
          if (knoten.getAttribute(merkmal) === String(alt)) {
            knoten.setAttribute(merkmal, String(neu));
          }
        });
    });
  };

  // cloneNode nimmt Attribute mit, nicht Zustaende. Was seit dem Laden
  // getippt wurde, steht nur in der Eigenschaft.
  var zustaendeUebernehmen = function (quelle, ziel) {
    var vonAllen = quelle.querySelectorAll("input, select, textarea");
    var nachAllen = ziel.querySelectorAll("input, select, textarea");

    Array.prototype.forEach.call(vonAllen, function (von, i) {
      var nach = nachAllen[i];
      if (!nach) return;
      if (von.type === "checkbox" || von.type === "radio") {
        nach.checked = von.checked;
      } else {
        nach.value = von.value;
      }
    });
  };

  secListe.addEventListener("click", function (ereignis) {
    var knopf = ereignis.target.closest("[data-sec-kopie]");
    if (!knopf) return;

    var zeile = knopf.closest("[data-sec-zeile]");
    var alt = zeile.getAttribute("data-sec-zeile");
    var tafel = form.querySelector('[data-panel="sec-' + alt + '"]');
    if (!tafel) return;

    var neu = naechsteNummer();

    var tafelKopie = tafel.cloneNode(true);
    zustaendeUebernehmen(tafel, tafelKopie);
    tafelKopie.setAttribute("data-panel", "sec-" + neu);
    umnummerieren(tafelKopie, alt, neu);

    // Die Kennung muss eine eigene sein: zwei Abschnitte mit derselben waeren
    // im Stilblock ein und derselbe.
    var kennung = tafelKopie.querySelector("[data-sec-kennung]");
    if (kennung) kennung.value = (kennung.value || "abschnitt") + "-" + neu;

    tafel.parentNode.appendChild(tafelKopie);

    var zeileKopie = zeile.cloneNode(true);
    zustaendeUebernehmen(zeile, zeileKopie);
    zeileKopie.setAttribute("data-sec-zeile", neu);
    zeileKopie.removeAttribute("data-aktiv");
    zeileKopie.removeAttribute("data-weg");
    umnummerieren(zeileKopie, alt, neu);

    var auge = zeileKopie.querySelector('input[type="checkbox"]');
    if (auge) auge.setAttribute("name", "sec_on_" + neu);

    zeile.parentNode.insertBefore(zeileKopie, zeile.nextSibling);

    reiheNeu();

    // Und gleich hinsehen: eine Kopie, die man erst suchen muss, ist eine
    // halbe Kopie.
    markiere(zeileKopie);
    zeigeTafel("sec-" + neu);
  });

  /*
   * Ziehen statt Klicken.
   *
   * Die Pfeile bleiben, und zwar nicht aus Bequemlichkeit: HTML5-Ziehen gibt
   * es auf Telefonen nicht. Wer die Liste am Schreibtisch sortiert, zieht;
   * wer sie unterwegs sortiert, tippt.
   *
   * Beim Ziehen wandert die Zeile sofort mit - man sieht, wo sie landet,
   * bevor man loslaesst. Geschrieben wird die Reihe erst beim Loslassen: die
   * Reihe ist die Wahrheit ueber Ordnung UND Bestand, und sie waehrend des
   * Ziehens dutzendfach neu zu schreiben hiesse, dutzende Schritte in die
   * Geschichte zu legen.
   */
  var ziehenErlauben = function (liste, merkmal, fertig) {
    if (!liste) return;

    var gezogen = null;

    liste.querySelectorAll("[" + merkmal + "]").forEach(function (zeile) {
      // Die Zeile "+ Abschnitt" bleibt, wo sie ist: sie ist kein Abschnitt,
      // sondern der Platz, an dem einer entsteht.
      if (zeile.hasAttribute("data-sec-neu")) return;

      zeile.setAttribute("draggable", "true");

      zeile.addEventListener("dragstart", function (ereignis) {
        gezogen = zeile;
        zeile.setAttribute("data-zieht", "");
        // Ohne Nutzlast startet der Zug in manchen Browsern gar nicht.
        if (ereignis.dataTransfer) {
          ereignis.dataTransfer.effectAllowed = "move";
          ereignis.dataTransfer.setData("text/plain", zeile.getAttribute(merkmal) || "");
        }
      });

      zeile.addEventListener("dragend", function () {
        zeile.removeAttribute("data-zieht");
        gezogen = null;
        fertig();
      });

      zeile.addEventListener("dragover", function (ereignis) {
        if (!gezogen || gezogen === zeile) return;
        ereignis.preventDefault();

        // Vor oder hinter die Zeile, je nachdem, wo sie herkommt. Ohne diese
        // Unterscheidung springt eine Zeile beim Ueberfahren hin und her.
        var davor = gezogen.compareDocumentPosition(zeile) & Node.DOCUMENT_POSITION_FOLLOWING;
        liste.insertBefore(gezogen, davor ? zeile.nextSibling : zeile);
      });
    });

    liste.addEventListener("drop", function (ereignis) {
      ereignis.preventDefault();
    });
  };

  ziehenErlauben(secListe, "data-sec-zeile", reiheNeu);
  ziehenErlauben(liste, "data-ebene", stapleNeu);

  /*
   * Rueckgaengig und Wiederherstellen.
   *
   * Der Zustand ist das ganze Formular, nach Feldnamen. Nicht nach Position:
   * Verdoppeln legt Felder dazu, und eine Liste nach Position waere danach
   * um eins verschoben - man haette beim Rueckgaengigmachen die Werte
   * fremder Felder eingesetzt.
   *
   * Die beiden Reihen (Abschnitte, Ebenen) sind selbst Felder und fahren
   * deshalb einfach mit. Sie sind die Wahrheit ueber Ordnung und Bestand -
   * wer sie zurueckdreht, dreht auch die Listen zurueck, und genau das tut
   * listeSyncen() danach.
   */
  var geschichte = [];
  var kuenftig = [];
  var haltAn = false;

  var eingaben = function () {
    return form.querySelectorAll("input[name], select[name], textarea[name]");
  };

  var zustand = function () {
    var werte = {};

    eingaben().forEach(function (feld) {
      werte[feld.name] = (feld.type === "checkbox" || feld.type === "radio")
        ? (feld.checked ? 1 : 0)
        : feld.value;
    });

    return JSON.stringify(werte);
  };

  var listeSyncen = function (liste, merkmal, feld, knopfMerkmal) {
    if (!liste || !feld) return;

    var genannt = {};

    feld.value.split(",").forEach(function (kennung) {
      kennung = kennung.trim();
      if (kennung === "") return;
      genannt[kennung] = true;

      var zeile = liste.querySelector("[" + merkmal + '="' + kennung + '"]');
      // appendChild schiebt sie ans Ende - in der Reihenfolge der Reihe
      // ergibt das genau die Reihe.
      if (zeile) liste.appendChild(zeile);
    });

    liste.querySelectorAll("[" + merkmal + "]").forEach(function (zeile) {
      var weg = !genannt[zeile.getAttribute(merkmal)];
      var knopf = zeile.querySelector("[" + knopfMerkmal + "]");

      if (weg) {
        zeile.setAttribute("data-weg", "");
      } else {
        zeile.removeAttribute("data-weg");
      }

      if (knopf) {
        knopf.textContent = knopf.getAttribute(weg ? "data-wort-zurueck" : "data-wort-weg");
      }
    });
  };

  var herstellen = function (roh) {
    var werte = JSON.parse(roh);

    haltAn = true;

    eingaben().forEach(function (feld) {
      if (!(feld.name in werte)) return;
      if (feld.type === "checkbox" || feld.type === "radio") {
        feld.checked = !!werte[feld.name];
      } else {
        feld.value = werte[feld.name];
      }
    });

    listeSyncen(secListe, "data-sec-zeile", secReihe, "data-sec-weg");
    listeSyncen(liste, "data-ebene", reihe, "data-ebene-weg");
    stapleNeu();

    // Die Vorschau folgt: sie haengt an Ereignissen, und ein gesetzter Wert
    // loest keines aus.
    form.querySelectorAll("[data-kasten]").forEach(function (f) {
      f.dispatchEvent(new Event(f.type === "checkbox" || f.tagName === "SELECT" ? "change" : "input"));
    });
    form.querySelectorAll("[data-textfeld], [data-farbfeld]").forEach(function (f) {
      f.dispatchEvent(new Event("input"));
    });

    haltAn = false;
  };

  var merken = function () {
    if (haltAn) return;

    var jetzt = zustand();
    if (geschichte.length && geschichte[geschichte.length - 1] === jetzt) return;

    geschichte.push(jetzt);
    // Hundert Schritte reichen fuer eine Sitzung und halten den Speicher
    // klein; wer weiter zurueck will, laedt die Seite neu.
    if (geschichte.length > 100) geschichte.shift();
    kuenftig.length = 0;
  };

  merken();

  /*
   * Nicht bei jedem Tastendruck. Ein Schieberegler feuert dutzende Male je
   * Bewegung, und jeder davon waere ein eigener Schritt zurueck - man
   * drueckte fuenfzigmal, um eine Bewegung rueckgaengig zu machen.
   */
  var wartend = null;
  var spaeterMerken = function () {
    if (wartend) clearTimeout(wartend);
    wartend = setTimeout(merken, 450);
  };

  form.addEventListener("input", spaeterMerken);
  form.addEventListener("change", merken);
  form.addEventListener("click", function (ereignis) {
    // Nach einem Knopf, der etwas verschoben oder weggenommen hat.
    if (ereignis.target.closest("button[type=button]")) setTimeout(merken, 0);
  });

  var zurueck = function () {
    if (geschichte.length < 2) return;
    kuenftig.push(geschichte.pop());
    herstellen(geschichte[geschichte.length - 1]);
  };

  var vor = function () {
    if (!kuenftig.length) return;
    var naechster = kuenftig.pop();
    geschichte.push(naechster);
    herstellen(naechster);
  };

  document.addEventListener("keydown", function (ereignis) {
    if (!ereignis.ctrlKey && !ereignis.metaKey) return;

    /*
     * In einem Textfeld gehoert Strg+Z dem Browser. Sein Rueckgaengig kennt
     * einzelne Buchstaben; unseres kennt nur ganze Zustaende, und es waere
     * ein schlechter Tausch, ein getipptes Wort nur im Ganzen zurueckdrehen
     * zu koennen.
     */
    var wo = document.activeElement;
    if (wo && (wo.tagName === "INPUT" || wo.tagName === "TEXTAREA")) return;

    var taste = ereignis.key.toLowerCase();
    if (taste === "z" && !ereignis.shiftKey) {
      ereignis.preventDefault();
      zurueck();
    } else if ((taste === "z" && ereignis.shiftKey) || taste === "y") {
      ereignis.preventDefault();
      vor();
    }
  });
})();
