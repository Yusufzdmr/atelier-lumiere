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

  /*
   * Was gewaehlt ist, zeigen - vor dem Speichern.
   *
   * Ein Dateifeld sagt nur "bild-2.webp", und der Kasten daneben zeigt bis
   * zum Speichern noch das ALTE Bild. Wer vier Medienfelder untereinander
   * hat, weiss danach nicht, was er gerade hinterlegt hat: "yukledigin halde
   * neyin yuklu oldugu gorulmuyor". Nachsehen hiess: speichern, Einladung
   * aufmachen, zurueck.
   *
   * Die Paarung steht in der Vorlage - data-vorschau-fuer traegt den NAMEN
   * des Dateifeldes. Ein Kasten ohne Gegenstueck bleibt einfach, wie er ist;
   * es wird nichts erfunden.
   *
   * createObjectURL und nicht FileReader: die Adresse steht sofort, ohne
   * dass die Datei erst durch den Speicher gelesen wird - bei einem Blatt von
   * drei Megabyte ist das der Unterschied zwischen "sofort" und "gleich".
   * Freigegeben wird die vorige Adresse beim naechsten Mal; ohne das haelt
   * der Browser jede gewaehlte Datei bis zum Neuladen fest.
   */
  /*
   * Der Vorspann ueber der Karte.
   *
   * Er folgt dem Pfadfeld wie die kleinen Kaesten auch - wer einen Film aus
   * der Ablage waehlt, sieht ihn sofort und nicht erst nach dem Speichern.
   * Und er geht mit einem Klick weg: solange er liegt, faengt er die Klicks
   * ab, und darunter will man ziehen.
   *
   * Leeres Feld heisst kein Vorspann. Dann verschwindet er ganz, statt ein
   * schwarzes Rechteck ueber der Karte stehen zu lassen - genau das waere die
   * Antwort auf eine Frage, die niemand gestellt hat.
   */
  (function () {
    var vorspann = vorschau.querySelector("[data-vorspann]");
    if (!vorspann) return;

    var feld = form.querySelector('[name="intro_video"]');

    var stelle = function () {
      var wert = feld ? feld.value.trim() : "";
      var film = vorspann.querySelector("video");

      if (wert === "") {
        vorspann.hidden = true;
        if (film) film.removeAttribute("src");
        return;
      }

      if (!film) {
        film = document.createElement("video");
        film.muted = true;
        film.setAttribute("playsinline", "");
        film.setAttribute("preload", "metadata");
        film.style.height = "100%";
        film.style.width = "100%";
        film.style.objectFit = "contain";
        vorspann.insertBefore(film, vorspann.firstChild);
      }

      if (film.getAttribute("src") !== wert) film.setAttribute("src", wert);
      vorspann.hidden = false;
    };

    // Wegnehmen. Er kommt wieder, sobald jemand den Film wechselt - das ist
    // der einzige Moment, in dem man ihn wieder sehen will.
    vorspann.addEventListener("click", function () {
      vorspann.hidden = true;
    });

    if (feld) feld.addEventListener("input", stelle);
  })();

  /*
   * Ein Bild oder einen Film in einen Vorschaukasten setzen.
   *
   * Film oder Bild entscheidet der Aufrufer: bei einer Datei sagt es ihr Typ,
   * bei einem Pfad die Vorlage. Ein Standbild in einem <video> waere ein
   * schwarzes Rechteck, ein Film in einem <img> gar nichts.
   */
  var zeigeImKasten = function (kasten, adresse, film, istBlob) {
    var art = film ? "VIDEO" : "IMG";
    var knoten = kasten.firstElementChild;

    if (!knoten || knoten.tagName !== art) {
      kasten.innerHTML = "";
      knoten = document.createElement(film ? "video" : "img");
      knoten.className = "h-full w-full object-contain";
      if (film) {
        knoten.muted = true;
        knoten.setAttribute("playsinline", "");
      } else {
        knoten.alt = "";
      }
      kasten.appendChild(knoten);
    }

    // Die vorige Blob-Adresse freigeben; ohne das haelt der Browser jede
    // gewaehlte Datei bis zum Neuladen fest.
    if (knoten.dataset.blob === "1") URL.revokeObjectURL(knoten.src);

    knoten.src = adresse;
    if (istBlob) {
      knoten.dataset.blob = "1";
    } else {
      delete knoten.dataset.blob;
    }
  };

  form.querySelectorAll('input[type="file"]').forEach(function (feld) {
    var name = feld.getAttribute("name") || "";
    if (name === "") return;

    var kasten = form.querySelector('[data-vorschau-fuer="' + name + '"]');
    var ton = form.querySelector('[data-tonvorschau="' + name + '"]');
    if (!kasten && !ton) return;

    feld.addEventListener("change", function () {
      var datei = feld.files && feld.files[0];
      if (!datei) return;

      var adresse = URL.createObjectURL(datei);

      // Der Ton: derselbe Spieler, nur eine andere Quelle.
      if (ton) {
        if (ton.dataset.blob === "1") URL.revokeObjectURL(ton.src);
        ton.src = adresse;
        ton.dataset.blob = "1";
        ton.load();
        return;
      }

      zeigeImKasten(kasten, adresse, datei.type.indexOf("video/") === 0, true);
    });
  });

  /*
   * Und dasselbe, wenn nicht eine DATEI gewaehlt wird, sondern ein PFAD sich
   * aendert - von Hand getippt oder aus der Filmablage eingesetzt.
   *
   * Genau daran fehlte es: die Auswahl schrieb den Film brav ins Feld, und
   * der Kasten daneben zeigte weiter nichts. "Videoyu sectim ama gelmedi
   * onizleme." Der Kasten hing am Dateifeld allein, und das war nur die
   * Haelfte der Wege, auf denen ein Bild in eine Vorlage kommt.
   *
   * Welche Art hineingehoert, sagt hier die Vorlage (data-vorschau-art) und
   * nicht die Datei - ein Pfad sagt es nicht von sich aus, und ".mp4" zu
   * lesen waere Raten.
   */
  form.querySelectorAll("[data-vorschau-pfad]").forEach(function (kasten) {
    var feld = form.querySelector('[name="' + kasten.getAttribute("data-vorschau-pfad") + '"]');
    if (!feld) return;

    var film = kasten.getAttribute("data-vorschau-art") === "film";

    feld.addEventListener("input", function () {
      var wert = feld.value.trim();

      // Leer heisst leer: der Kasten faellt auf seinen Platzhalter zurueck,
      // statt das vorige Bild zu behalten und etwas zu behaupten.
      if (wert === "") {
        kasten.innerHTML = "";
        return;
      }

      zeigeImKasten(kasten, wert, film, false);
    });
  });

  /*
   * Einen Film aus der Ablage waehlen.
   *
   * Die Auswahl selbst wird nicht gespeichert - gespeichert wird, was in den
   * beiden Pfadfeldern steht. Sie schreibt also nur hinein, und zwar beides
   * auf einmal: ein Film in der Ablage bringt sein Standbild mit, und wer den
   * Film wechselt und das alte Standbild stehen laesst, sieht beim Oeffnen
   * das falsche erste Bild.
   *
   * Das Standbild nur, wenn der Film eins hat. Sonst bliebe das vorhandene
   * ohne Grund zurueck - und ein Vorspann ohne Standbild ist besser als einer
   * mit einem fremden.
   */
  var schreibeIntro = function (name, wert) {
    var feld = form.querySelector('[name="' + name + '"]');
    if (!feld) return;
    feld.value = wert;
    feld.dispatchEvent(new Event("input", { bubbles: true }));
  };

  /*
   * Wegnehmen heisst: beides.
   *
   * Ein Standbild ohne Film ist ein erstes Bild fuer nichts - es stuende im
   * Dokument und waere nirgends zu sehen, bis irgendwann ein anderer Film
   * kommt und mit fremdem Gesicht aufmacht.
   */
  var introWeg = function () {
    schreibeIntro("intro_video", "");
    schreibeIntro("intro_poster", "");

    var wahl = form.querySelector("[data-introwahl]");
    if (wahl) wahl.selectedIndex = 0;
  };

  form.querySelectorAll("[data-introweg]").forEach(function (knopf) {
    knopf.addEventListener("click", introWeg);
  });

  form.querySelectorAll("[data-introwahl]").forEach(function (wahl) {
    wahl.addEventListener("change", function () {
      var film = wahl.value;

      // Die leere Zeile heisst "keiner" und nicht "nichts tun".
      if (film === "") {
        introWeg();
        return;
      }

      var gewaehlt = wahl.options[wahl.selectedIndex];
      var standbild = gewaehlt ? gewaehlt.getAttribute("data-poster") : "";

      schreibeIntro("intro_video", film);
      if (standbild) schreibeIntro("intro_poster", standbild);
    });
  });

  /* ======================================================================
   * Zwei Kaesten, dieselbe Karte.
   *
   * In der Mitte steht das Kaestchen, das jedem Tastendruck folgt. Daneben
   * der Rahmen, der die ganze Seite zeigt - Karte UND Abschnitte - und sie
   * sich vom Server holt. Geschrieben wurde bisher nur in den ersten, und
   * deshalb blieb der Rahmen beim GESPEICHERTEN Stand stehen: wer aufs
   * Telefon umschaltete, sah eine Karte, die sich nicht mehr ruehrte.
   *
   * "Surukle birak hala diger bolumlerde calismiyor ... telefon tablet
   * masaustu kisminda falan da."
   *
   * Kein zweiter Zeichner, und darauf kommt alles an. Die Wahrheit bleibt das
   * Formularfeld, die Rechnung bleibt in stelle() und in den Schreibern hier
   * darunter. Was sich aendert, ist allein die Zahl der Stellen, an denen
   * dasselbe Ergebnis abgelegt wird: bisher eine, jetzt jede, die gerade da
   * ist. Eine Rechnung, eine Quelle der Wahrheit - unveraendert.
   *
   * Als Funktion und nicht als Liste: den Rahmen gibt es erst nach dem ersten
   * Klick auf ein Geraet, und sein Dokument wird bei jedem Wechsel neu
   * geladen. Eine beim Start gebaute Liste bliebe fuer immer einelementig.
   * ==================================================================== */

  var rahmenWurzeln = function () {
    var kasten = document.querySelector("[data-ansicht-rahmen]");
    if (!kasten || kasten.hidden) return [];

    var kind = kasten.querySelector("iframe");
    if (!kind) return [];

    var doc;
    // Gleicher Ursprung, also sollte das nie werfen. Aber ein Editor, der an
    // einer Ausnahme stehenbleibt, ist schlimmer als einer, der eine
    // Kleinigkeit nicht kann - dieselbe Ueberlegung wie in rahmenDokument().
    try { doc = kind.contentDocument; } catch (fehler) { return []; }
    if (!doc) return [];

    /*
     * ZWEI Knoten, nicht einer.
     *
     * Design::css() legt die Marken der Vorlage unter den Geltungsbereich,
     * und den tragen im Rahmen beide: die Buehne mit der Karte
     * (templates/partials/design-stage.php) und die Flaeche mit den
     * Abschnitten darunter (DesignSections::flaeche). Nur auf die Buehne
     * geschrieben faerbte sich die Karte um und die Abschnitte blieben
     * stehen - ein halber Schritt sieht schlimmer aus als gar keiner: bei
     * einem stehengebliebenen Rahmen weiss man, woran man ist, bei einem
     * halb umgefaerbten sucht man den Fehler in der Vorlage.
     *
     * Werden die Namen dort umbenannt, greift diese Suche ins Leere und der
     * Rahmen ist wieder still. Ein Test haelt beide Nahtstellen fest.
     */
    return Array.prototype.slice.call(doc.querySelectorAll(".d-stage, .d-sec-flaeche"));
  };

  var wurzeln = function () {
    return [vorschau].concat(rahmenWurzeln());
  };

  // Die Marken der Vorlage: Farbe, Schriftfamilie, Gewicht, Groessenfaktor.
  // Sie haengen am Geltungsbereich und fallen von dort auf alles darunter.
  var setzeMarke = function (name, wert) {
    wurzeln().forEach(function (w) { w.style.setProperty(name, wert); });
  };

  // Dieselbe Ebene in jeder Wurzel. Die Kennung steht in beiden Kaesten in
  // derselben Klasse, weil beide dasselbe Server-Markup zeigen.
  var knotenAlle = function (id) {
    var treffer = [];
    wurzeln().forEach(function (w) {
      var el = w.querySelector(".d-el-" + id);
      if (el) treffer.push(el);
    });
    return treffer;
  };

  // Farbe: das Textfeld ist die Wahrheit, der Waehler schreibt hinein. So
  // ueberlebt ein rgba(), das der Waehler gar nicht darstellen kann.
  form.querySelectorAll("[data-farbfeld]").forEach(function (feld) {
    var marke = feld.getAttribute("data-farbfeld");
    var waehler = form.querySelector('[data-farbwahl="' + marke + '"]');

    var male = function () {
      setzeMarke("--d-" + marke.toLowerCase(), feld.value.trim());
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
      setzeMarke("--df-" + feld.getAttribute("data-schriftfeld"), '"' + feld.value + '"');
    });
  });

  form.querySelectorAll("[data-gewichtfeld]").forEach(function (feld) {
    feld.addEventListener("input", function () {
      setzeMarke("--dfw-" + feld.getAttribute("data-gewichtfeld"), feld.value);
    });
  });

  // Die Groesse der Marke ist ein Faktor: das Feld zeigt Prozent, die
  // Variable traegt das Verhaeltnis. Design::css() rechnet dieselbe Division.
  form.querySelectorAll("[data-groessefeld]").forEach(function (feld) {
    feld.addEventListener("input", function () {
      var zahl = parseInt(feld.value, 10);
      if (!isFinite(zahl) || zahl < 1) return;
      setzeMarke("--dfs-" + feld.getAttribute("data-groessefeld"), zahl / 100);
    });
  });

  /*
   * Die Groesse einer einzelnen Zeile. Dieselbe Rechnung wie in
   * Design::css(): Zehntelprozent der Kartenbreite, mal dem Faktor der
   * Marke. Der Faktor kommt aus der Variablen und nicht aus dem Feld daneben
   * - so stimmt die Vorschau auch, wenn beides zugleich verstellt wird.
   */
  form.querySelectorAll("[data-schriftgroesse]").forEach(function (feld) {
    feld.addEventListener("input", function () {
      var ziele = knotenAlle(feld.getAttribute("data-schriftgroesse"));
      var zahl = parseInt(feld.value, 10);
      if (!ziele.length || !isFinite(zahl) || zahl < 1) return;
      var marke = feld.getAttribute("data-schriftmarke");
      var basis = (zahl / 10) + "cqw";
      var groesse = marke
        ? "calc(" + basis + " * var(--dfs-" + marke + ", 1))"
        : basis;
      ziele.forEach(function (ziel) { ziel.style.fontSize = groesse; });
    });
  });

  // Fester Text: der Knoten in der Vorschau traegt die Klasse d-el-<id>.
  form.querySelectorAll("[data-textfeld]").forEach(function (feld) {
    feld.addEventListener("input", function () {
      knotenAlle(feld.getAttribute("data-textfeld")).forEach(function (ziel) {
        ziel.textContent = feld.value;
      });
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

  // Dieselbe Ebene, aber in der Wurzel, die gerade angefasst wird. An einem
  // Griff im Rahmen ist knoten() die falsche Antwort: die liefert die aus der
  // Vorschau, und die ist im Geraetemodus versteckt und ohne Groesse - der
  // Griff zoege ins Nichts.
  var knotenIn = function (wurzel, id) {
    return wurzel.querySelector(".d-el-" + id);
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
    // In jede Wurzel, nicht nur in die Vorschau: der Rahmen zeigt dieselbe
    // Karte und soll denselben Schritt mitmachen. Die Rechnung darunter ist
    // dieselbe geblieben - sie wird nur einmal gemacht und zweimal abgelegt.
    var ziele = knotenAlle(id);
    if (!ziele.length) return;

    var anker = wert(id, "anchor") || "topleft";
    var hoehe = zahl(id, "h");
    var dreh  = zahl(id, "rotate");
    var sx    = wert(id, "flipx") ? "-1" : "1";
    var sy    = wert(id, "flipy") ? "-1" : "1";

    // Erst rechnen, dann ablegen. Die Zahlen kommen aus dem Formular und sind
    // fuer jede Wurzel dieselben - sie im Schleifenrumpf zu holen hiesse, sie
    // je Kasten neu zu lesen und die Gelegenheit zu schaffen, dass zwei
    // Kaesten verschiedene Antworten bekommen.
    var x = zahl(id, "x") + "%";
    var y = zahl(id, "y") + "%";
    var breite = zahl(id, "w") + "%";
    var hoeheStil = hoehe > 0 ? hoehe + "%" : "auto";
    var deckkraft = String(zahl(id, "opacity") / 100);
    var wandlung = "rotate(" + dreh + "deg)"
      + (sx === "-1" || sy === "-1" ? " scale(" + sx + "," + sy + ")" : "");

    // Welche zwei Kanten geschrieben werden, sagt der Anker - und die andere
    // muss ausdruecklich auf auto, sonst bleibt die Regel aus dem Stilblock
    // stehen und die Ebene haengt an zwei Kanten gleichzeitig.
    var rechts = anker.indexOf("right") >= 0;
    var unten  = anker.indexOf("bottom") === 0;

    ziele.forEach(function (el) {
      el.style.left  = rechts ? "auto" : x;
      el.style.right = rechts ? x : "auto";
      el.style.top    = unten ? "auto" : y;
      el.style.bottom = unten ? y : "auto";

      el.style.width = breite;
      el.style.height = hoeheStil;
      el.style.opacity = deckkraft;
      el.style.transform = wandlung;
    });
  };

  form.querySelectorAll("[data-kasten]").forEach(function (feld) {
    var art = feld.type === "checkbox" || feld.tagName === "SELECT" ? "change" : "input";
    feld.addEventListener(art, function () {
      stelle(feld.getAttribute("data-kasten"));
    });
  });

  /* ======================================================================
   * Ziehen statt tippen: anfassen, an den Griffen ziehen, doppelt klicken.
   *
   * Der Block darueber ist die Wahrheit, dieser hier nur eine zweite Hand an
   * denselben Feldern. Das Ziehen rechnet nichts eigenes aus und speichert
   * nichts eigenes: es schreibt eine Zahl in genau das Feld, in das sonst
   * jemand tippt, und loest dessen input-Ereignis aus. Danach laeuft alles
   * Weitere von selbst - stelle() rueckt die Ebene, das Formular merkt sich
   * den Schritt fuer Strg+Z, und Speichern schickt dieselben Namen wie immer.
   *
   * Deshalb gibt es hier auch keine zweite Liste von Grenzen: geklemmt wird
   * am min/max des Feldes, und das steht in der Vorlage, die es aus
   * Design::BOX bezieht. Drei Stellen mit denselben Zahlen laufen frueher
   * oder spaeter auseinander, zwei sind schon eine zu viel.
   *
   * Was NICHT gezogen werden kann: Ebenen des Kuverts (die Vorschau zeigt nur
   * Seite und Karte, ihr Knoten fehlt) und Textebenen ohne Text (Design::html
   * laesst einen leeren Text ganz weg - es gibt nichts anzufassen).
   * ==================================================================== */
  (function () {
    var kastenFeld = function (id, mass) {
      return form.querySelector('[data-kasten="' + id + '"][data-mass="' + mass + '"]');
    };
    var schriftFeld = function (id) {
      return form.querySelector('[data-schriftgroesse="' + id + '"]');
    };
    var textFeld = function (id) {
      return form.querySelector('[data-textfeld="' + id + '"]');
    };

    /*
     * Eine Zahl ins Feld schreiben - geklemmt, gerundet, und mit dem
     * Ereignis, an dem alles andere haengt.
     *
     * bubbles: true, und das ist kein Detail. Die Vorschau haengt am Feld
     * selbst, das Rueckgaengig aber am FORMULAR (form.addEventListener
     * "input"). Ein Ereignis ohne bubbles erreicht nur das erste von beiden -
     * die Karte bewegte sich, und Strg+Z kaeme nie an dieser Bewegung vorbei.
     */
    var setze = function (f, wieviel) {
      if (!f) return;

      var min = parseInt(f.getAttribute("min"), 10);
      var max = parseInt(f.getAttribute("max"), 10);
      if (isFinite(min) && wieviel < min) wieviel = min;
      if (isFinite(max) && wieviel > max) wieviel = max;

      var gerundet = String(Math.round(wieviel));
      if (gerundet === f.value) return;

      f.value = gerundet;
      f.dispatchEvent(new Event("input", { bubbles: true }));
    };

    // Die Kennung steht in der Klasse: d-el d-el-<id> d-spot-<ort>.
    var kennung = function (el) {
      var treffer = null;
      Array.prototype.forEach.call(el.classList, function (name) {
        if (name.indexOf("d-el-") === 0) treffer = name.slice(5);
      });
      return treffer;
    };

    /* --- Der Rahmen um das Gewaehlte ------------------------------------ */

    /*
     * Acht Griffe, benannt wie die Himmelsrichtungen. Der Name traegt die
     * Rechnung: "nw" fasst die obere und die linke Kante an, "e" nur die
     * rechte. Welche davon sich bewegen darf, entscheidet der Anker.
     *
     * Wo sie sitzen, steht im Stilblock der Seite und nicht hier: sie liegen
     * INNEN an der Kante, weil der Vorschaukasten abschneidet, was ueber ihn
     * hinausragt - ein mittig auf der Kante sitzender Griff waere bei einer
     * Ebene, die die Karte fuellt, zur Haelfte weggeschnitten und nur noch
     * mit fuenf Pixeln zu treffen.
     */
    var GRIFFE = ["nw", "n", "ne", "e", "se", "s", "sw", "w"];

    var gewaehlt = null;
    var tippt = null;

    /*
     * Die Regeln der Griffe - kopiert, nicht neu geschrieben.
     *
     * Sie stehen im Stilblock des Editors (design-edit.php) und gelten dort.
     * Im Dokument des Rahmens ist von ihnen nichts bekannt: ein Wahlrahmen,
     * der dorthin gehaengt wird, waere ein unsichtbares div mit acht
     * unsichtbaren Kindern.
     *
     * Geholt wird aus dem GEBAUTEN Blatt und nicht hier zweitgeschrieben. Ein
     * zweiter Satz Regeln im Skript waere eine zweite Wahrheit ueber das
     * Aussehen der Griffe, und die laeuft beim naechsten Handgriff am
     * Stilblock auseinander - dieselbe Ueberlegung wie ueberall sonst hier.
     *
     * Der Geltungsbereich wandert mit: mehrere Regeln haengen an
     * [data-design-preview], und den Kasten gibt es im Rahmen nicht. Die
     * Marke, die es dort gibt, steht seit dem Anhaengen des Ziehens auf jeder
     * Wurzel. Darunter ist eine Regel, die nicht Zierde ist:
     * .d-el{touch-action:none}. Ohne sie nimmt der Browser den Finger fuer
     * sich und wischt die Seite, statt die Ebene zu ziehen.
     */
    /*
     * Rekursiv, und das ist kein Selbstzweck.
     *
     * Der erste Wurf sammelte ueber selectorText und uebersprang alles ohne
     * einen. Eine @media-Gruppe hat keinen - und darunter lag ausgerechnet
     * die Regel, die den Griffen am FINGER ihren Fangbereich gibt:
     * @media (pointer: coarse) macht aus zehn Pixeln vierunddreissig. Ohne
     * sie ist ein Griff im Rahmen am Telefon zehn Pixel gross; zu treffen
     * ist das nicht, und von aussen sieht es aus, als taete das Ziehen dort
     * nichts.
     *
     * Die Bedingung wird mitkopiert und nicht nur ihr Inhalt: ohne sie gaelte
     * die Vergroesserung auch mit der Maus, und dann laege ueber jedem Griff
     * ein unsichtbarer Kasten von 34 Pixeln, der den Nachbarn verdeckt.
     */
    var sammle = function (regeln, aus) {
      for (var j = 0; j < regeln.length; j++) {
        var regel = regeln[j];

        // Eine Gruppe (@media, @supports): hineinsehen, und nur wenn darin
        // etwas Passendes steht, die Gruppe samt Bedingung mitnehmen.
        if (!regel.selectorText && regel.cssRules) {
          var innen = [];
          sammle(regel.cssRules, innen);

          if (innen.length) {
            // Alles vor der ersten Klammer ist die Bedingung - so bleibt es
            // richtig, egal ob @media oder @supports.
            var bedingung = regel.cssText.split("{")[0];
            aus.push(bedingung + "{" + innen.join("\n") + "}");
          }
          continue;
        }

        var wahl = regel.selectorText;
        if (!wahl) continue;

        if (wahl.indexOf(".b-rahmen-wahl") < 0 && wahl.indexOf(".b-griff") < 0
            && wahl.indexOf("[data-design-preview]") < 0) continue;

        aus.push(regel.cssText.split("[data-design-preview]").join("[data-zieht-bereit]"));
      }
    };

    var griffRegeln = function () {
      var aus = [];
      var blaetter = document.styleSheets;

      for (var i = 0; i < blaetter.length; i++) {
        var regeln;
        // Ein fremdes Blatt (CDN) laesst sich nicht lesen und wirft. Uns
        // gehoert ohnehin nur das eigene.
        try { regeln = blaetter[i].cssRules; } catch (fehler) { continue; }
        if (!regeln) continue;

        sammle(regeln, aus);
      }

      return aus.join("\n");
    };

    var regelnHinein = function (dok) {
      // Ins EIGENE Dokument nicht: dort stehen sie schon, und eine Kopie
      // davon waere eine zweite Fassung derselben Regeln - genau das, was
      // hier vermieden werden soll.
      if (!dok || dok === document) return;
      if (dok.querySelector("[data-griffregeln]")) return;

      var blatt = dok.createElement("style");
      blatt.setAttribute("data-griffregeln", "");
      blatt.textContent = griffRegeln();
      (dok.head || dok.documentElement).appendChild(blatt);
    };

    /*
     * Je Wurzel ein Wahlrahmen, und jeder in seinem eigenen Dokument.
     *
     * Ein Knoten aus dem Editordokument laesst sich nicht in den Rahmen
     * haengen; importiert haette er dort trotzdem keine Regeln. Also wird er
     * dort gebaut, wo er liegen soll.
     *
     * Kein Gedaechtnis nebenher: gefunden wird er als Kind der Wurzel. Der
     * Rahmen laedt neu, wenn jemand das Geraet wechselt - eine Liste von
     * Knoten waere danach eine Liste von Leichen, das DOM dagegen stimmt
     * immer.
     */
    var wahlrahmenFuer = function (wurzel) {
      var vorhanden = wurzel.querySelector(".b-rahmen-wahl");
      if (vorhanden) return vorhanden;

      var dok = wurzel.ownerDocument;
      regelnHinein(dok);

      var kasten = dok.createElement("div");
      kasten.className = "b-rahmen-wahl";

      GRIFFE.forEach(function (g) {
        var punkt = dok.createElement("span");
        punkt.className = "b-griff";
        punkt.setAttribute("data-griff", g);
        kasten.appendChild(punkt);
      });

      wurzel.appendChild(kasten);
      return kasten;
    };

    /*
     * Wie weit die Ebene von der Wurzel entfernt liegt - ueber ALLE Spruenge.
     *
     * In der Vorschau ist es einer: .d-el haengt in einer Huelle, die inset-0
     * darauf liegt. Im Rahmen sind es drei - .d-el steht in .d-card, die in
     * .d-stage-mitte, die in .d-stage. Die alte Fassung addierte genau einen
     * Sprung und traefe dort um die halbe Buehne daneben.
     *
     * Erreicht der Weg die Wurzel nicht, ist der Kasten gerade nicht im Bild
     * (versteckte Vorschau im Geraetemodus, weggenommene Ebene). Dann kommt
     * null zurueck und der Aufrufer laesst diese Wurzel aus - was NICHT
     * heisst, dass die Wahl verloren ist: sie kann in der anderen Wurzel
     * sehr wohl zu sehen sein.
     */
    var versatz = function (el, wurzel) {
      var x = el.offsetLeft;
      var y = el.offsetTop;
      var eltern = el.offsetParent;

      while (eltern && eltern !== wurzel) {
        x += eltern.offsetLeft;
        y += eltern.offsetTop;
        eltern = eltern.offsetParent;
      }

      return eltern === wurzel ? { x: x, y: y } : null;
    };

    /*
     * Den Rahmen auf die Ebene legen.
     *
     * Gemessen wird mit offsetLeft/offsetWidth und nicht mit
     * getBoundingClientRect: das eine ist die Groesse VOR der Drehung, das
     * andere danach. Ein gedrehter Kasten haette sonst einen waagerechten
     * Rahmen, der groesser ist als er selbst.
     *
     * Der Rahmen haengt im Vorschaukasten und nicht in der Ebene: in ihr
     * wuerde er ihre Deckkraft erben und mit ihr verblassen, und bei einer
     * Ebene der SEITE laege er unter der Karte. Beide Huellen liegen
     * absolute inset-0 auf dem Vorschaukasten - die Koordinaten stimmen
     * also unveraendert.
     *
     * Gespiegelt wird nicht mitgedreht: scale(-1) um die Mitte laesst den
     * Kasten dort, wo er ist, und wuerde nur die Griffe vertauschen - "nw"
     * saesse rechts und zoege in die falsche Richtung.
     */
    var zeichne = function () {
      if (!gewaehlt) return;

      /*
       * In JEDE Wurzel, die den Knoten gerade zeigt.
       *
       * Bis hierher gab es einen Wahlrahmen in der Vorschau. Im Geraetemodus
       * ist die versteckt - dort war also nichts zu sehen, und wer im Rahmen
       * eine Ebene anfasste, sah nicht, was er anfasste.
       *
       * Eine Wurzel, die sich nicht vermessen laesst, wird ausgelassen und
       * nicht zum Anlass genommen, die Wahl wegzuwerfen: sie kann in der
       * anderen sehr wohl zu sehen sein. Erst wenn KEINE sie zeigt, ist die
       * Ebene wirklich fort (weggenommen, Auge zu) - dann geht auch die Wahl.
       */
      var getroffen = 0;

      wurzeln().forEach(function (wurzel) {
        var el = wurzel.querySelector(".d-el-" + gewaehlt);
        if (!el || el.hidden || el.style.display === "none") return;

        var wo = versatz(el, wurzel);
        if (!wo) return;

        getroffen += 1;

        var kasten = wahlrahmenFuer(wurzel);

        kasten.style.left = wo.x + "px";
        kasten.style.top = wo.y + "px";
        kasten.style.width = el.offsetWidth + "px";
        kasten.style.height = el.offsetHeight + "px";

        /*
         * Gemessen mit offsetLeft/offsetWidth und nicht mit
         * getBoundingClientRect: das eine ist die Groesse VOR der Drehung,
         * das andere danach. Ein gedrehter Kasten haette sonst einen
         * waagerechten Rahmen, der groesser ist als er selbst.
         *
         * Gespiegelt wird nicht mitgedreht: scale(-1) um die Mitte laesst den
         * Kasten dort, wo er ist, und wuerde nur die Griffe vertauschen -
         * "nw" saesse rechts und zoege in die falsche Richtung.
         */
        var dreh = zahl(gewaehlt, "rotate");
        kasten.style.transform = dreh ? "rotate(" + dreh + "deg)" : "";

        /*
         * Duenne Ebenen: die Griffe nach AUSSEN.
         *
         * Innen an der Kante ist die richtige Stelle, solange der Kasten
         * groesser ist als zwei Griffe. Eine Textzeile ist das oft nicht:
         * gemessen an "Wir heiraten" mit 14 Pixel Hoehe lagen der obere Griff
         * bei 0-10 und der untere bei 4-14 - sie ueberlappten, der spaeter
         * gezeichnete gewann, und der obere war nicht mehr zu treffen. Man
         * fasste oben an und zog unten.
         */
        if (el.offsetHeight < 24) {
          kasten.setAttribute("data-eng", "");
        } else {
          kasten.removeAttribute("data-eng");
        }
        if (el.offsetWidth < 24) {
          kasten.setAttribute("data-schmal", "");
        } else {
          kasten.removeAttribute("data-schmal");
        }

        // An einem Text ziehen die Ecken die SCHRIFT - der Zeiger soll es sagen.
        if (schriftFeld(gewaehlt)) {
          kasten.setAttribute("data-schrift", "");
        } else {
          kasten.removeAttribute("data-schrift");
        }
      });

      // Nirgends zu sehen heisst: es gibt sie nicht mehr.
      if (getroffen === 0) waehle(null);
    };

    /*
     * Waehlen heisst: Rahmen auf die Karte, Zeile in der Liste markieren.
     * Beides zusammen, damit man nie raten muss, welche der vierzehn Zeilen
     * gerade die angefasste Ebene ist.
     */
    var waehle = function (id) {
      gewaehlt = id;

      if (liste) {
        liste.querySelectorAll("[data-ebene]").forEach(function (zeile) {
          if (id !== null && zeile.getAttribute("data-ebene") === id) {
            zeile.setAttribute("data-gewaehlt", "");
          } else {
            zeile.removeAttribute("data-gewaehlt");
          }
        });
      }

      if (id === null) {
        /*
         * Aus JEDER Wurzel, und ueber das DOM gesucht statt gemerkt: der
         * Rahmen laedt neu, wenn jemand das Geraet wechselt, und ein
         * gemerkter Knoten waere danach eine Leiche.
         */
        wurzeln().forEach(function (wurzel) {
          wurzel.querySelectorAll(".b-rahmen-wahl").forEach(function (kasten) {
            kasten.parentNode.removeChild(kasten);
          });
        });
        return;
      }

      zeichne();
    };

    /* --- Welche Ebene liegt unter dem Zeiger? ---------------------------- */

    /*
     * Nicht die oberste - die ist oft nur ein Kasten.
     *
     * Gemessen an der Vorlage "bild": die Ueberschrift ist eine Textebene mit
     * h=100, also ein Kasten ueber die ganze Karte, in dem oben eine einzige
     * Zeile steht. Er lag ueber den Namen des Paares, ueber dem Datum, ueber
     * allem. Wer die Namen anfasste, zog die Ueberschrift - weit oben,
     * unbemerkt - und die Namen blieben stehen. Von aussen sah das aus, als
     * taete das Ziehen gar nichts.
     *
     * Der Kasten ist also die falsche Frage. Gesucht wird, was zu SEHEN ist:
     * bei Text die Zeilen selbst, bei Bild, Form und Film der Kasten - dort
     * IST er das Sichtbare. Trifft nichts davon, bleibt es beim obersten
     * Kasten: irgendetwas anzufassen ist besser als nichts, und ein leerer
     * Textkasten will manchmal auch bewegt werden.
     */
    var sichtbarHier = function (el, x, y) {
      // Bild, Form, Film: der Kasten ist die Zeichnung.
      if (el.tagName !== "DIV") return true;

      // Ein DIV ohne Text ist eine Form - auch da ist der Kasten alles.
      if (el.textContent.trim() === "") return true;

      var bereich = document.createRange();
      bereich.selectNodeContents(el);

      var zeilen = bereich.getClientRects();
      if (!zeilen.length) return true;

      /*
       * Die Zeilen zu EINEM Kasten zusammenfassen, nicht einzeln pruefen.
       *
       * Einzeln geprueft faellt der Zwischenraum zwischen den Zeilen heraus,
       * und der ist bei einer Schauschrift breiter als die Zeile selbst:
       * gemessen an den Namen des Paares - drei Zeilen, "Sophia / & /
       * Maximilian" - lag ein Griff zwischen zwei Zeilen daneben und fasste
       * den Hintergrund. Wer einen Namen anfassen will, zielt auf den Block,
       * nicht auf eine Zeile.
       *
       * Bei einer einzelnen Zeile ist der Zusammenschluss die Zeile selbst -
       * genau das, was die Ueberschrift von ihrem karten-hohen Kasten
       * unterscheidet.
       */
      var links = Infinity, rechts = -Infinity, oben = Infinity, unten = -Infinity;

      for (var i = 0; i < zeilen.length; i++) {
        var z = zeilen[i];
        if (z.left < links) links = z.left;
        if (z.right > rechts) rechts = z.right;
        if (z.top < oben) oben = z.top;
        if (z.bottom > unten) unten = z.bottom;
      }

      // Etwas Luft: eine Zeile trifft man am Rand der Buchstaben, nicht erst
      // in ihrer Mitte.
      return x >= links - 4 && x <= rechts + 4 && y >= oben - 4 && y <= unten + 4;
    };

    var ebeneAn = function (wurzel, x, y) {
      var sichtbar = null, sichtbarZ = -1;
      var kasten = null, kastenZ = -1;

      /*
       * Die Stapelfolge zaehlt, nicht die Reihenfolge im Markup - beim
       * Umsortieren ohne Speichern schreibt stapleNeu() den z-Index neu, und
       * dann stimmt das Markup nicht mehr mit dem ueberein, was oben liegt.
       */
      wurzel.querySelectorAll(".d-el").forEach(function (el) {
        if (el.hidden || el.style.display === "none") return;

        var id = kennung(el);
        if (!id || !kastenFeld(id, "x")) return;

        var r = el.getBoundingClientRect();
        if (!r.width || !r.height) return;
        if (x < r.left || x > r.right || y < r.top || y > r.bottom) return;

        var z = parseInt(window.getComputedStyle(el).zIndex, 10);
        if (!isFinite(z)) z = 0;

        if (z >= kastenZ) { kasten = el; kastenZ = z; }
        if (z >= sichtbarZ && sichtbarHier(el, x, y)) { sichtbar = el; sichtbarZ = z; }
      });

      return sichtbar || kasten;
    };

    /* --- Anfassen und schieben ------------------------------------------ */

    var zieht = null;

    /*
     * Die drei Haende fragen nicht mehr nach der Vorschau, sondern nach dem
     * Knoten, an dem sie haengen (currentTarget). Damit sind sie an jeder
     * Wurzel dieselben - und die Rechnung darunter musste dafuer nicht
     * angefasst werden: sie misst ohnehin in Prozent des Elternkastens, und
     * das transform:scale des Rahmens kuerzt sich dabei heraus.
     */
    var beimDruecken = function (ereignis) {
      var wurzel = ereignis.currentTarget;
      if (ereignis.button !== 0) return;

      /*
       * Waehrend auf der Karte getippt wird, gehoert der Klick dem Browser:
       * er setzt den Schreibzeiger oder nimmt den Fokus weg, und das Wegnehmen
       * ist es, was das Tippen beendet. Stuende hier preventDefault, kaeme man
       * aus dem Text nie wieder heraus.
       */
      if (tippt) return;

      var griff = ereignis.target.closest("[data-griff]");
      var el = griff
        ? knotenIn(wurzel, gewaehlt)
        : ebeneAn(wurzel, ereignis.clientX, ereignis.clientY);

      if (!el) {
        waehle(null);
        return;
      }

      var id = griff ? gewaehlt : kennung(el);
      // Ohne Feld ist die Ebene hier nicht einstellbar - das Kuvert etwa
      // steht in der Liste, aber nicht in dieser Vorschau.
      if (!id || !kastenFeld(id, "x")) return;

      if (id !== gewaehlt) {
        waehle(id);
        var zeile = liste && liste.querySelector('[data-ebene="' + id + '"]');
        if (zeile && zeile.scrollIntoView) zeile.scrollIntoView({ block: "nearest" });
      }

      var eltern = el.offsetParent;
      if (!eltern) return;

      var mass = eltern.getBoundingClientRect();
      if (!mass.width || !mass.height) return;

      /*
       * Der Anker sagt, von welcher Kante gemessen wird - und damit ueber das
       * VORZEICHEN. Haengt eine Ebene rechts, wird x KLEINER, wenn man nach
       * rechts zieht. Ohne diese beiden Zahlen liefe jede Ebene mit Anker
       * "oben rechts" der Maus davon.
       */
      var anker = wert(id, "anchor") || "topleft";

      zieht = {
        id: id,
        griff: griff ? griff.getAttribute("data-griff") : "",
        x0: ereignis.clientX,
        y0: ereignis.clientY,
        breite: mass.width,
        hoehe: mass.height,
        sx: anker.indexOf("right") >= 0 ? -1 : 1,
        sy: anker.indexOf("bottom") === 0 ? -1 : 1,
        haeltX: anker.indexOf("right") >= 0 ? "e" : "w",
        haeltY: anker.indexOf("bottom") === 0 ? "s" : "n",
        wx: zahl(id, "x"),
        wy: zahl(id, "y"),
        ww: zahl(id, "w"),
        wh: zahl(id, "h"),
        // Die gemessene Hoehe in Prozent - gebraucht, wenn aus "auto" eine
        // Zahl wird und dabei nichts springen soll.
        hoeheJetzt: el.offsetHeight / mass.height * 100,
        kastenBreite: el.offsetWidth,
        groesse: schriftFeld(id) ? parseInt(schriftFeld(id).value, 10) : 0
      };

      wurzel.setPointerCapture(ereignis.pointerId);
      wurzel.setAttribute("data-zieht", "");
      ereignis.preventDefault();
    };

    var beimBewegen = function (ereignis) {
      if (!zieht) return;

      var id = zieht.id;
      var dxP = (ereignis.clientX - zieht.x0) / zieht.breite * 100;
      var dyP = (ereignis.clientY - zieht.y0) / zieht.hoehe * 100;

      // Ohne Griff wandert die ganze Ebene.
      if (zieht.griff === "") {
        setze(kastenFeld(id, "x"), zieht.wx + dxP * zieht.sx);
        setze(kastenFeld(id, "y"), zieht.wy + dyP * zieht.sy);
        zeichne();
        return;
      }

      var g = zieht.griff;
      var kanteX = g.indexOf("w") >= 0 ? "w" : (g.indexOf("e") >= 0 ? "e" : "");
      var kanteY = g.charAt(0) === "n" ? "n" : (g.charAt(0) === "s" ? "s" : "");
      var ecke = kanteX !== "" && kanteY !== "";

      /*
       * Wieviel die angefasste Kante nach AUSSEN gegangen ist, in Pixeln.
       * Die freie Kante waechst mit der Bewegung, die angeankerte gegen sie:
       * wer den linken Rand einer links haengenden Ebene nach rechts zieht,
       * macht sie schmaler, nicht breiter.
       */
      var wachsPx = (ereignis.clientX - zieht.x0) * zieht.sx * (kanteX === zieht.haeltX ? -1 : 1);

      /*
       * Die Ecke eines Textes zieht die SCHRIFT, nicht den Kasten.
       *
       * Einen Textkasten breiter zu ziehen aendert nur, wo die Zeile
       * umbricht - man zieht und zieht, und die Buchstaben bleiben gleich
       * gross. Wer an einer Ecke zieht, will groessere Buchstaben; wer die
       * Zeile umbrechen lassen will, nimmt die Kante links oder rechts.
       */
      if (ecke && zieht.groesse > 0 && zieht.kastenBreite > 0) {
        var faktor = (zieht.kastenBreite + wachsPx) / zieht.kastenBreite;
        if (faktor > 0.05) setze(schriftFeld(id), zieht.groesse * faktor);
        zeichne();
        return;
      }

      if (kanteX !== "") {
        if (kanteX === zieht.haeltX) {
          // Die angeankerte Kante zieht den Kasten hinter sich her: die
          // gegenueberliegende bleibt stehen, also wandert auch x.
          setze(kastenFeld(id, "x"), zieht.wx + dxP * zieht.sx);
          setze(kastenFeld(id, "w"), zieht.ww - dxP * zieht.sx);
        } else {
          setze(kastenFeld(id, "w"), zieht.ww + dxP * zieht.sx);
        }
      }

      /*
       * Die Senkrechte, nach derselben Regel wie die Waagerechte - mit einer
       * Ausnahme an der Ecke.
       *
       * Eine Hoehe von 0 heisst "so hoch wie der Inhalt". Wer den Griff oben
       * oder unten anfasst, will genau das aendern; aus auto wird dann die
       * gerade gemessene Zahl, damit im ersten Moment nichts springt.
       *
       * An der ECKE bleibt auto dagegen auto. Ein Bild ohne feste Hoehe
       * traegt seine eigene Proportion, und ihm nebenbei eine Hoehe zu geben
       * hiesse, es zu beschneiden: Design::css() schreibt zu jeder gesetzten
       * Hoehe object-fit:cover. Von elf Bildebenen oertlich und sechzehn in
       * der Produktion hat KEINE eine Hoehe - die Ecke waere also der
       * haeufigste Weg in eine Aenderung, die niemand wollte.
       */
      if (kanteY !== "" && !(ecke && zieht.wh === 0)) {
        var basisH = zieht.wh > 0 ? zieht.wh : zieht.hoeheJetzt;
        if (kanteY === zieht.haeltY) {
          setze(kastenFeld(id, "y"), zieht.wy + dyP * zieht.sy);
          setze(kastenFeld(id, "h"), basisH - dyP * zieht.sy);
        } else {
          setze(kastenFeld(id, "h"), basisH + dyP * zieht.sy);
        }
      }

      zeichne();
    };

    var beende = function (ereignis) {
      var wurzel = ereignis && ereignis.currentTarget;
      if (!zieht) return;
      zieht = null;
      if (wurzel) wurzel.removeAttribute("data-zieht");

      if (wurzel && wurzel.hasPointerCapture && wurzel.hasPointerCapture(ereignis.pointerId)) {
        wurzel.releasePointerCapture(ereignis.pointerId);
      }
      zeichne();
    };

    /*
     * Zweimal an dieselbe Buehne gehaengt hiesse: jedes Ziehen zaehlt doppelt
     * und die Ebene liefe mit doppelter Geschwindigkeit davon. Der Rahmen
     * laedt bei jedem Wechsel des Geraets neu, also wird es oft versucht -
     * die Marke am Knoten selbst ueberlebt genau so lange wie er.
     */
    var haengeZiehen = function (wurzel) {
      if (!wurzel || wurzel.hasAttribute("data-zieht-bereit")) return;
      wurzel.setAttribute("data-zieht-bereit", "");

      wurzel.addEventListener("pointerdown", beimDruecken);
      wurzel.addEventListener("pointermove", beimBewegen);
      wurzel.addEventListener("pointerup", beende);
      wurzel.addEventListener("pointercancel", beende);

      /*
       * Der Doppelklick steht weiter unten - er braucht beginneTippen, und das
       * steht dort. Ueber eine Huelle gebunden, damit beim ANHAENGEN noch
       * nicht danach gefragt wird: gefragt wird erst beim Klick, und dann ist
       * die Datei laengst durchgelaufen.
       */
      wurzel.addEventListener("dblclick", function (ereignis) { beimDoppelklick(ereignis); });
    };

    haengeZiehen(vorschau);

    // Und die Buehne im Rahmen, sobald es eine gibt. rahmenWurzeln() liefert
    // auch die Flaeche mit den Abschnitten - dort gibt es keine .d-el, das
    // Anhaengen ist also folgenlos und eine Ausnahme waere nur eine Regel
    // mehr, die stimmen muss.
    var ziehenImRahmen = function () { rahmenWurzeln().forEach(haengeZiehen); };

    /*
     * Angehaengt wird beim Klick auf ein Geraet - denselben Weg nimmt weiter
     * unten schon das Nachziehen der Reihenfolge. Zweimal, weil es zwei
     * Faelle sind: beim ERSTEN Klick entsteht der Rahmen gerade erst und hat
     * noch nichts geladen (dafuer das load-Ereignis), bei jedem weiteren
     * steht er schon und laedt nicht neu (dafuer der kurze Aufschub).
     */
    document.querySelectorAll("[data-ansicht]").forEach(function (knopf) {
      knopf.addEventListener("click", function () {
        var kasten = document.querySelector("[data-ansicht-rahmen]");
        var kind = kasten && kasten.querySelector("iframe");
        if (kind) kind.addEventListener("load", ziehenImRahmen, { once: true });
        window.setTimeout(ziehenImRahmen, 60);
      });
    });

    /* --- Den Text an Ort und Stelle schreiben ---------------------------- */

    /*
     * Doppelklick oeffnet den Text auf der Karte.
     *
     * Geschrieben wird trotzdem ins FELD - der Knoten ist nur die Tastatur.
     * Waehrend getippt wird, geht das Feld aber OHNE Ereignis: sein eigener
     * Zuhoerer (oben, [data-textfeld]) beschriftet den Knoten neu, und ein
     * neu beschrifteter Knoten hat keinen Schreibzeiger mehr - man tippt
     * einen Buchstaben und steht wieder am Anfang. Das eine input-Ereignis
     * kommt deshalb erst zum Schluss, und es ist zugleich der eine Schritt,
     * den Strg+Z zurueckdreht.
     *
     * Nur Ebenen mit eigenem Textfeld: eine gebundene Ebene (die Namen des
     * Paares) zeigt hier Beispieltext, ihre Worte stehen in der Einladung
     * und nicht in der Vorlage.
     */
    var beginneTippen = function (el, id, f) {
      if (tippt) return;
      tippt = id;

      var vorher = f.value;

      try {
        el.contentEditable = "plaintext-only";
      } catch (fehler) {
        el.contentEditable = "true";
      }
      if (!el.isContentEditable) el.contentEditable = "true";
      el.focus();

      // Alles gewaehlt: eine Textebene traegt ein Wort, ein Datum, einen
      // Namen - ueberschreiben ist der Normalfall, anhaengen die Ausnahme.
      /*
       * Im Dokument des KNOTENS, nicht im eigenen.
       *
       * Ein Range aus dem Editordokument ueber einen Knoten im Rahmen geht
       * ueber eine Dokumentgrenze und markiert nichts; window.getSelection()
       * des Editors weiss vom Rahmen ohnehin nichts. Dann stuende der
       * Schreibzeiger nirgends und "alles gewaehlt" waere leer.
       */
      var auswahl = el.ownerDocument.defaultView.getSelection();
      if (auswahl) {
        var bereich = el.ownerDocument.createRange();
        bereich.selectNodeContents(el);
        auswahl.removeAllRanges();
        auswahl.addRange(bereich);
      }

      var schreibe = function () {
        f.value = el.textContent;
      };

      /*
       * stopPropagation, nicht nur preventDefault.
       *
       * Escape hat hier eine Bedeutung (nimm die Eingabe zurueck) und weiter
       * unten am Dokument eine zweite (loese die Auswahl). Ohne das Anhalten
       * laufen beide: gemessen am 27.08.2026 nahm ein Esc die Eingabe
       * zurueck UND liess den Rahmen verschwinden - man wollte ein Wort
       * verwerfen und stand ohne gewaehlte Ebene da. Das Naehere gewinnt.
       */
      var taste = function (e2) {
        if (e2.key === "Enter") {
          e2.preventDefault();
          e2.stopPropagation();
          beenden(false);
        } else if (e2.key === "Escape") {
          e2.preventDefault();
          e2.stopPropagation();
          beenden(true);
        }
      };

      var aufBlur = function () { beenden(false); };

      var beenden = function (zurueckdrehen) {
        if (tippt !== id) return;
        tippt = null;

        el.removeEventListener("input", schreibe);
        el.removeEventListener("keydown", taste);
        el.removeEventListener("blur", aufBlur);
        el.removeAttribute("contenteditable");

        if (zurueckdrehen) f.value = vorher;

        /*
         * Ein leer gelassener Text nimmt die Ebene beim Speichern mit:
         * Design::html laesst eine Textebene ohne Text ganz weg. Hier bleibt
         * der Knoten noch stehen, nach dem Speichern ist er fort. Das ist
         * dieselbe Regel wie beim Tippen ins Feld daneben, und deshalb steht
         * hier keine Sonderbehandlung.
         */
        el.textContent = f.value;
        f.dispatchEvent(new Event("input", { bubbles: true }));
        zeichne();
      };

      el.addEventListener("input", schreibe);
      el.addEventListener("keydown", taste);
      el.addEventListener("blur", aufBlur);
    };

    var beimDoppelklick = function (ereignis) {
      var wurzel = ereignis.currentTarget;
      /*
       * NICHT ueber ereignis.target - das war der erste Versuch, und er hat
       * nie gegriffen.
       *
       * Der Doppelklick traegt als Ziel den gemeinsamen Vorfahren der beiden
       * Klicks, und pointerdown wird hier default-verhindert (sonst faengt
       * der Browser an, Text zu markieren, sobald man eine Ebene zieht).
       * Ohne mousedown faellt das Ziel auf den Vorschaukasten zurueck:
       * gemessen am 27.08.2026 stand dort "d-elysee buehne", und
       * closest(".d-el") fand nichts - man klickte doppelt, und es geschah
       * nichts.
       *
       * Gebraucht wird das Ziel aber gar nicht: der ERSTE Klick des
       * Doppelklicks hat die Ebene unter dem Zeiger schon gewaehlt. Was dort
       * liegt, steht also in gewaehlt - und das ist zugleich das, was der
       * Rahmen zeigt. Eine Wahrheit statt zweier.
       */
      if (!gewaehlt) return;
      // Auf einem Griff wird gezogen, nicht geschrieben.
      if (ereignis.target.closest("[data-griff]")) return;

      var el = knotenIn(wurzel, gewaehlt);
      var f = el ? textFeld(gewaehlt) : null;
      if (!f) return;

      ereignis.preventDefault();
      beginneTippen(el, gewaehlt, f);
    };

    /* --- Von der Liste aus waehlen, mit den Pfeilen schieben ------------- */

    if (liste) {
      liste.addEventListener("click", function (ereignis) {
        // Die Knoepfe der Zeile (vorn, hinten, weg) haben ihre eigene Arbeit.
        if (ereignis.target.closest("button, input, select, label")) return;

        var zeile = ereignis.target.closest("[data-ebene]");
        if (!zeile) return;

        var id = zeile.getAttribute("data-ebene");
        if (knoten(id) && kastenFeld(id, "x")) waehle(id);
      });
    }

    /*
     * Ein Prozent ist der kleinste Schritt, den das Dokument kennt - die
     * Werte sind ganze Zahlen (Design::completeBox castet auf int). Mit den
     * Pfeilen ist er erreichbar, ohne die Maus ruhig halten zu muessen; mit
     * Umschalt geht es in Fuenfern.
     */
    document.addEventListener("keydown", function (ereignis) {
      if (!gewaehlt || tippt) return;
      if (ereignis.ctrlKey || ereignis.metaKey || ereignis.altKey) return;

      var wo = document.activeElement;
      if (wo && (wo.tagName === "INPUT" || wo.tagName === "TEXTAREA"
                 || wo.tagName === "SELECT" || wo.isContentEditable)) return;

      if (ereignis.key === "Escape") {
        waehle(null);
        return;
      }

      var pfeile = {
        ArrowLeft: [-1, 0], ArrowRight: [1, 0],
        ArrowUp: [0, -1], ArrowDown: [0, 1]
      };
      var schritt = pfeile[ereignis.key];
      if (!schritt) return;

      ereignis.preventDefault();

      var weite = ereignis.shiftKey ? 5 : 1;
      var anker = wert(gewaehlt, "anchor") || "topleft";
      var sx = anker.indexOf("right") >= 0 ? -1 : 1;
      var sy = anker.indexOf("bottom") === 0 ? -1 : 1;

      if (schritt[0]) setze(kastenFeld(gewaehlt, "x"), zahl(gewaehlt, "x") + schritt[0] * weite * sx);
      if (schritt[1]) setze(kastenFeld(gewaehlt, "y"), zahl(gewaehlt, "y") + schritt[1] * weite * sy);
      zeichne();
    });

    /*
     * Der Rahmen folgt allem, was die Ebene bewegt - auch der getippten Zahl,
     * der gewechselten Schrift und dem Rueckgaengig. Eng gefasst und nicht am
     * ganzen Formular: zeichne() misst, und Messen mitten im Tippen in einem
     * beliebigen Feld waere Arbeit fuer nichts.
     */
    var folgt = function (ereignis) {
      if (!gewaehlt) return;

      var t = ereignis.target;
      if (!t || !t.hasAttribute) return;

      if (t.hasAttribute("data-kasten") || t.hasAttribute("data-schriftgroesse")
          || t.hasAttribute("data-textfeld") || t.hasAttribute("data-groessefeld")
          || t.hasAttribute("data-schriftfeld") || t.hasAttribute("data-gewichtfeld")) {
        zeichne();
      }
    };

    form.addEventListener("input", folgt);
    form.addEventListener("change", folgt);

    // Nach einem Knopf: die Ebene kann weggenommen worden sein, dann nimmt
    // zeichne() den Rahmen von selbst zurueck.
    form.addEventListener("click", function () {
      if (gewaehlt) setTimeout(zeichne, 0);
    });

    window.addEventListener("resize", function () {
      if (gewaehlt) zeichne();
    });
  })();

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

  /*
   * Die Reihenfolge mit der Hand.
   *
   * "Surukle birak duzenleme editorunden bahsediyorum, asagidaki kartlarda
   * calismiyor." Bisher ging sie nur ueber die Pfeile: bei sieben Zeilen ist
   * das Klicken, und eine Zeile von unten nach oben sind sechs Klicks.
   *
   * KEINE zweite Sortierlogik. Geschoben wird der Knoten, und danach schreibt
   * dieselbe reiheNeu() dieselbe Reihe ins versteckte Feld wie bei den
   * Pfeilen - inklusive allem, was sich weiter unten daran gehaengt hat (der
   * Rahmen zieht die Reihenfolge nach). Die Pfeile bleiben: am Telefon nimmt
   * der Finger die Liste zum Scrollen, und dort sind sie der Weg.
   *
   * Angefasst wird am Greifer - dem Knopf, der den Namen traegt. Er heisst
   * schon so, und er ist die einzige grosse Flaeche der Zeile, die nicht
   * schon eine andere Aufgabe hat (Auge, Pfeile, Verdoppeln, Wegnehmen).
   */
  var SCHWELLE = 6;   // Pixel, ab denen aus einem Klick ein Ziehen wird
  var ziehtZeile = null;

  secListe.addEventListener("pointerdown", function (ereignis) {
    if (ereignis.button !== 0) return;

    var griff = ereignis.target.closest("[data-sec-waehl]");
    if (!griff) return;

    var zeile = griff.closest("[data-sec-zeile]");
    // Die Zeile "+ Abschnitt" ist keine Zeile, die eine Stelle hat.
    if (!zeile || zeile.hasAttribute("data-sec-neu")) return;

    ziehtZeile = { zeile: zeile, y0: ereignis.clientY, aktiv: false };
  });

  secListe.addEventListener("pointermove", function (ereignis) {
    if (!ziehtZeile) return;

    /*
     * Erst ab der Schwelle ist es ein Ziehen. Ohne sie waere jeder Klick auf
     * eine Zeile schon eine Bewegung um ein, zwei Pixel, und die Zeile
     * spraenge beim blossen Auswaehlen umher.
     */
    if (!ziehtZeile.aktiv) {
      if (Math.abs(ereignis.clientY - ziehtZeile.y0) < SCHWELLE) return;

      ziehtZeile.aktiv = true;
      ziehtZeile.zeile.setAttribute("data-zieht", "");
      if (secListe.setPointerCapture) secListe.setPointerCapture(ereignis.pointerId);
    }

    // Welche Zeile liegt unter dem Zeiger? Ueber die Rechtecke und nicht
    // ueber den obersten Knoten: der ist beim Ziehen die geschobene Zeile.
    var unter = null;
    secListe.querySelectorAll("[data-sec-zeile]").forEach(function (z) {
      if (z === ziehtZeile.zeile || z.hasAttribute("data-sec-neu")) return;

      var r = z.getBoundingClientRect();
      if (ereignis.clientY >= r.top && ereignis.clientY <= r.bottom) unter = z;
    });
    if (!unter) return;

    // Ueber der Mitte davor, darunter dahinter - so kippt die Zeile erst,
    // wenn der Zeiger die andere Haelfte erreicht, und nicht schon am Rand.
    var kasten = unter.getBoundingClientRect();
    var dahinter = ereignis.clientY > kasten.top + kasten.height / 2;

    secListe.insertBefore(ziehtZeile.zeile, dahinter ? unter.nextSibling : unter);
  });

  var zeileFertig = function (ereignis) {
    if (!ziehtZeile) return;

    var war = ziehtZeile.aktiv;
    ziehtZeile.zeile.removeAttribute("data-zieht");
    ziehtZeile = null;

    if (secListe.hasPointerCapture && secListe.hasPointerCapture(ereignis.pointerId)) {
      secListe.releasePointerCapture(ereignis.pointerId);
    }

    if (!war) return;

    reiheNeu();

    /*
     * Den Klick danach schlucken. Das Loslassen loest sonst den Klick auf dem
     * Greifer aus, der Abschnitt wird ausgewaehlt und die Mitte springt auf
     * ein Geraet - man wollte nur umsortieren.
     *
     * Mit Uhr dahinter: kommt gar kein Klick (weil ausserhalb losgelassen
     * wurde), muss der Horcher trotzdem wieder weg, sonst verschluckt er den
     * naechsten echten.
     */
    var schluck = function (e2) {
      e2.stopPropagation();
      e2.preventDefault();
      secListe.removeEventListener("click", schluck, true);
    };

    secListe.addEventListener("click", schluck, true);
    window.setTimeout(function () {
      secListe.removeEventListener("click", schluck, true);
    }, 300);
  };

  secListe.addEventListener("pointerup", zeileFertig);
  secListe.addEventListener("pointercancel", zeileFertig);

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

        // Zweimal, weil es zwei Faelle sind: beim ERSTEN Klick entsteht der
        // Rahmen gerade erst und hat noch nichts geladen (dafuer das
        // load-Ereignis), bei jedem weiteren steht er schon und laedt nicht
        // neu (dafuer der kurze Aufschub).
        var frisch = rahmen.querySelector("iframe");
        if (frisch) frisch.addEventListener("load", kuvertAuf, { once: true });
        window.setTimeout(kuvertAuf, 60);

        if (hinweisAnsicht) hinweisAnsicht.textContent = worte.seite;
      });
    });

    /*
     * Einen Abschnitt waehlen heisst: ihn auch sehen.
     *
     * Links stehen die Abschnitte, in der Mitte die Karte - und ein Abschnitt
     * steht NICHT auf der Karte, sondern darunter auf der Seite. Wer links
     * klickte, sah in der Mitte deshalb nichts; rechts erschien eine Tafel,
     * und in der Mitte blieb dieselbe Karte stehen. Das liest sich wie ein
     * kaputter Editor, und genau so kam es zurueck: "soldan sectigim karti
     * onizleyemiyorum".
     *
     * Also holt die Mitte von selbst die Ansicht, in der es etwas zu sehen
     * gibt. Steht dort schon ein Geraet, bleibt es stehen - wer am
     * Schreibtisch prueft, will nicht bei jedem Klick aufs Telefon
     * zurueckgeworfen werden.
     *
     * Was sich dadurch NICHT aendert: der Rahmen zeigt den gespeicherten
     * Stand. Ein Abschnitt, den es dort noch nicht gibt, wird nicht gefunden
     * - dann bleibt es beim Umschalten, und die Zeile unter dem Rahmen sagt
     * ohnehin, woran man ist.
     */
    var mitAbschnitt = function (name, tuWas) {
      var kind = rahmen.querySelector("iframe");
      if (!kind) return;

      /*
       * Erst versuchen, dann warten. Ein Rahmen, der gerade erst entstanden
       * ist, traegt noch about:blank - dort ist nichts zu finden, und der
       * Versuch sagt das von selbst, ohne dass hier ein Ladezustand geraten
       * werden muesste.
       */
      var versuch = function () {
        var doc;
        try { doc = kind.contentDocument; } catch (fehler) { return false; }
        if (!doc) return false;

        var knoten = doc.querySelector(".d-sec-" + name);
        if (!knoten) return false;

        tuWas(knoten, doc);
        return true;
      };

      if (versuch()) return;
      kind.addEventListener("load", versuch, { once: true });
    };

    var hervor = null;

    /*
     * Den Rahmen aufmachen, bevor darin gesucht wird.
     *
     * Der Rahmen zeigt die Einladung, und die faengt GESCHLOSSEN an:
     * invitation.js legt die Seite still, solange das Kuvert zu ist - nicht
     * nur overflow:hidden, sondern ein festgestellter body (position:fixed),
     * weil am Telefon der Finger sonst daran vorbeiscrollt.
     *
     * Ein Abschnitt liegt unter der Karte. Solange die Sperre haelt, ist er
     * also nicht zu erreichen - gemessen im Rahmen: scrollY blieb 0, obwohl
     * der Abschnitt bei 2278 Pixeln stand. Deshalb konnte man Abschnitte auch
     * von Hand nie im Rahmen sehen; erst ein Klick aufs Kuvert im Rahmen
     * selbst haette geholfen, und darauf kommt niemand.
     *
     * Geklickt wird das Kuvert und nicht die Sperre aufgehoben: invitation.js
     * hebt sie selbst auf, wenn seine Choreografie durch ist (mit
     * Oeffnungsfilm dauert das ein paar Sekunden). Von aussen an fremden
     * Inline-Stilen zu drehen hiesse, dieselbe Sache an zwei Stellen zu
     * entscheiden.
     */
    var oeffneRahmen = function (doc, dann) {
      var kuvert = doc.querySelector("[data-envelope]");
      if (!kuvert) { dann(); return; }

      if (kuvert.getAttribute("data-open") !== "true") {
        // invitation.js hoert auf das Kuvert selbst (event.target === envelope)
        // und auf den Anklickpunkt darin - der eine oder der andere ist da,
        // je nachdem ob das Thema einen Oeffnungsfilm mitbringt.
        kuvert.click();
      }

      /*
       * Warten, bis die Seite wieder laeuft. Wie lange das dauert, weiss nur
       * das andere Skript (Auftakt, Film, Kartenbewegung), also wird gefragt
       * statt gerechnet. Nach zehn Sekunden wird trotzdem gesprungen - lieber
       * an die falsche Stelle als gar nicht.
       */
      var versuche = 0;
      var schau = window.setInterval(function () {
        versuche += 1;

        var frei;
        try {
          frei = doc.body.style.position !== "fixed";
        } catch (fehler) {
          frei = true;
        }

        if (frei || versuche > 40) {
          window.clearInterval(schau);
          dann();
        }
      }, 250);
    };

    var zeigeAbschnitt = function (nummer) {
      var aktiv = form.querySelector("[data-ansicht][data-aktiv]");

      if (!aktiv || aktiv.getAttribute("data-ansicht") === "karte") {
        // Telefon zuerst: Einladungen werden auf Telefonen geoeffnet.
        var telefon = form.querySelector('[data-ansicht="390"]');
        if (telefon) telefon.click();
      }

      var kennung = form.querySelector('[data-sec-kennung="' + nummer + '"]');
      var name = kennung ? kennung.value.trim() : "";
      if (name === "") return;

      mitAbschnitt(name, function (knoten, doc) {
        oeffneRahmen(doc, function () {
          knoten.scrollIntoView({ block: "center" });

          /*
           * Kurz umranden, nicht faerben und nicht dauerhaft: der Rahmen zeigt
           * die Seite, wie der Gast sie sieht, und eine bleibende Markierung
           * waere eine Aussage darueber, die nicht stimmt. Der Rand liegt
           * inline im Dokument des Rahmens und ist beim naechsten Laden fort.
           */
          if (hervor) {
            hervor.knoten.style.outline = hervor.vorher;
            hervor.knoten.style.outlineOffset = hervor.vorherAbstand;
            window.clearTimeout(hervor.uhr);
          }

          var vorher = knoten.style.outline;
          var vorherAbstand = knoten.style.outlineOffset;

          knoten.style.outline = "2px solid #b08d57";
          knoten.style.outlineOffset = "4px";

          hervor = {
            knoten: knoten,
            vorher: vorher,
            vorherAbstand: vorherAbstand,
            uhr: window.setTimeout(function () {
              knoten.style.outline = vorher;
              knoten.style.outlineOffset = vorherAbstand;
              hervor = null;
            }, 1800)
          };
        });
      });
    };

    form.addEventListener("click", function (ereignis) {
      var knopf = ereignis.target.closest("[data-sec-waehl]");
      if (!knopf) return;

      var zeile = knopf.closest("[data-sec-zeile]");
      if (!zeile) return;

      /*
       * Links waehlen entscheidet, was in der Mitte steht - in beide
       * Richtungen. Die Vorlage IST die Karte (Farben, Schriften, Ebenen),
       * also kommt die Karte zurueck; ein Abschnitt steht auf der Seite, also
       * kommt der Rahmen. Nur so heisst ein Klick links immer dasselbe.
       */
      var welche = zeile.getAttribute("data-sec-zeile");

      if (welche === "thema") {
        var zurKarte = form.querySelector('[data-ansicht="karte"]');
        if (zurKarte && !zurKarte.hasAttribute("data-aktiv")) zurKarte.click();
        return;
      }

      zeigeAbschnitt(welche);
    });

    /* --- Die Abschnitte unter der Karte, lebend ------------------------- */

    /*
     * Gerendert wird auf dem Server, nur ohne zu speichern.
     *
     * Die Karte folgt jedem Tastendruck, weil ein Skript CSS-Variablen und
     * Inline-Kaesten setzen kann. Die Abschnitte kann es nicht: sie sind
     * gedrucktes Markup, je Art ein anderes. Es hier ein zweites Mal zu
     * schreiben waere schneller und haette eine zweite Wahrheit - und die
     * laeuft mit dem naechsten Abschnittstyp auseinander.
     *
     * Also geht das Formular an .../vorschau und kommt als fertiges Stueck
     * zurueck. Das kostet einen Weg zum Server; deshalb erst, wenn jemand
     * aufhoert zu tippen.
     */
    var liveKasten = form.querySelector("[data-live-abschnitte]");
    // Die Adresse traegt die Vorlage: Sprache und Kennung stehen dort, und
    // sie hier ein zweites Mal zusammenzusetzen hiesse, den Router zweimal
    // zu kennen.
    var liveAdresse = liveKasten ? liveKasten.getAttribute("data-adresse") : "";

    if (liveKasten && liveAdresse) {
      var laeuft = null;
      var nochmal = false;

      /*
       * Ohne Dateien.
       *
       * FormData nimmt sonst jede gewaehlte Datei mit - ein Blatt von drei
       * Megabyte, bei jedem Halt im Tippen. Zu sehen waere davon ohnehin
       * nichts Zusaetzliches: der kleine Kasten neben dem Feld zeigt die
       * gewaehlte Datei schon, und in die grosse Vorschau kommt sie mit dem
       * Speichern.
       */
      var formularOhneDateien = function () {
        var daten = new FormData();

        form.querySelectorAll("input, select, textarea").forEach(function (feld) {
          var name = feld.getAttribute("name");
          if (!name || feld.disabled) return;
          if (feld.type === "file") return;
          if ((feld.type === "checkbox" || feld.type === "radio") && !feld.checked) return;

          daten.append(name, feld.value);
        });

        return daten;
      };

      /*
       * Die Felder in der Vorschau stilllegen - und das ist keine Kosmetik.
       *
       * Der Abschnitt "Zusage" bringt ein echtes Formular mit, samt eigenem
       * csrf-Feld. Der Kasten liegt IM Formular des Editors, und damit stand
       * dieses Feld plötzlich ein zweites Mal darin - leer, weil die Vorschau
       * kein Token vergibt. PHP nimmt bei zwei gleichen Namen den LETZTEN:
       * das echte Token war weg, und zwar nicht nur fuer die naechste
       * Vorschau (419), sondern auch fuer das Speichern. Ein Kasten, der nur
       * zeigen sollte, haette den Knopf daneben unbrauchbar gemacht.
       *
       * Gesperrte Felder werden nicht abgeschickt - damit ist der Name wieder
       * einmalig. Und richtig ist es ohnehin: in eine Vorschau tippt man
       * nicht, sie zeigt, wie es beim Gast aussieht.
       */
      var entwaffne = function () {
        liveKasten.querySelectorAll("input, select, textarea, button").forEach(function (el) {
          el.disabled = true;
        });
      };

      var hole = function () {
        if (laeuft) { nochmal = true; return; }
        laeuft = true;

        window.fetch(liveAdresse, {
          method: "POST",
          body: formularOhneDateien(),
          credentials: "same-origin"
        }).then(function (antwort) {
          return antwort.ok ? antwort.text() : null;
        }).then(function (stueck) {
          /*
           * null heisst: der Server hat nein gesagt (Token abgelaufen, Vorlage
           * fort). Dann bleibt stehen, was zuletzt richtig war - eine leere
           * Vorschau waere die schlechtere Auskunft.
           */
          if (stueck !== null) {
            liveKasten.innerHTML = stueck;
            liveKasten.hidden = stueck.trim() === "";
            entwaffne();
          }
        }).catch(function () {
          // Netz weg: dasselbe wie oben, stehenlassen.
        }).then(function () {
          laeuft = false;
          if (nochmal) { nochmal = false; hole(); }
        });
      };

      var liveWartend = null;
      var spaeterHolen = function () {
        if (liveWartend) window.clearTimeout(liveWartend);
        liveWartend = window.setTimeout(hole, 400);
      };

      form.addEventListener("input", spaeterHolen);
      form.addEventListener("change", spaeterHolen);
      // Nach einem Knopf: verschoben, verdoppelt, weggenommen.
      form.addEventListener("click", function (ereignis) {
        if (ereignis.target.closest("button[type=button]")) spaeterHolen();
      });

      hole();
    }

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

  // In eine Huelle gewickelt und nicht direkt uebergeben: reiheNeu wird weiter
  // unten erweitert (es zieht dann auch den Rahmen nach). Wer die Funktion
  // selbst uebergibt, haelt die alte fest und bemerkt es nie - das Ziehen
  // wuerde als einziges den Rahmen nicht mitnehmen.
  ziehenErlauben(secListe, "data-sec-zeile", function () { reiheNeu(); });
  ziehenErlauben(liste, "data-ebene", function () { stapleNeu(); });

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

  /*
   * Die beiden Knoepfe unten im Balken.
   *
   * Strg+Z gibt es am Telefon nicht, und dort wird es mehr gebraucht als am
   * Schreibtisch: wer ausprobiert, verstellt auch mal etwas, das gut war -
   * und ohne Weg zurueck probiert man beim naechsten Mal nicht mehr.
   *
   * Sie zeigen auch, OB es einen Weg gibt: ein Knopf, der nichts tut, ist
   * schlimmer als keiner, weil man ihn zweimal drueckt und dann der Seite
   * nicht mehr traut. Gesperrt starten sie ohnehin - so verspricht die
   * Vorlage nichts, was ohne Skript niemand einloest.
   *
   * Sie stehen VOR merken(), nicht bei zurueck/vor: merken() laeuft einmal
   * beim Laden und stellt die Knoepfe mit - waere knoepfeStellen dann noch
   * nicht zugewiesen, bliebe der ganze Editor an dieser einen Zeile stehen.
   */
  var knopfZurueck = form.querySelector("[data-zurueck]");
  var knopfVor = form.querySelector("[data-vor]");

  var knoepfeStellen = function () {
    if (knopfZurueck) knopfZurueck.disabled = geschichte.length < 2;
    if (knopfVor) knopfVor.disabled = kuenftig.length === 0;
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

    // Erst ab dem zweiten Zustand gibt es etwas zurueckzudrehen, und ein
    // neuer Schritt wirft den Weg nach vorn weg - beides steht den Knoepfen
    // an, sobald es passiert.
    knoepfeStellen();
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
    knoepfeStellen();
  };

  var vor = function () {
    if (!kuenftig.length) return;
    var naechster = kuenftig.pop();
    geschichte.push(naechster);
    herstellen(naechster);
    knoepfeStellen();
  };

  if (knopfZurueck) knopfZurueck.addEventListener("click", zurueck);
  if (knopfVor) knopfVor.addEventListener("click", vor);

  document.addEventListener("keydown", function (ereignis) {
    if (!ereignis.ctrlKey && !ereignis.metaKey) return;

    /*
     * In einem Textfeld gehoert Strg+Z dem Browser. Sein Rueckgaengig kennt
     * einzelne Buchstaben; unseres kennt nur ganze Zustaende, und es waere
     * ein schlechter Tausch, ein getipptes Wort nur im Ganzen zurueckdrehen
     * zu koennen.
     *
     * isContentEditable gehoert dazu, seit auf der Karte selbst getippt
     * werden kann: ein Text dort ist weder INPUT noch TEXTAREA, und
     * herstellen() wuerde ihn mitten im Wort neu beschriften - der
     * Schreibzeiger waere weg und die halbe Eingabe dazu.
     */
    var wo = document.activeElement;
    if (wo && (wo.tagName === "INPUT" || wo.tagName === "TEXTAREA" || wo.isContentEditable)) return;

    var taste = ereignis.key.toLowerCase();
    if (taste === "z" && !ereignis.shiftKey) {
      ereignis.preventDefault();
      zurueck();
    } else if ((taste === "z" && ereignis.shiftKey) || taste === "y") {
      ereignis.preventDefault();
      vor();
    }
  });

  /*
   * Die Reihenfolge im Rahmen mitziehen.
   *
   * Die Karte in der Mitte zeigt die KARTE - Ebenen, Farben, Schrift. Die
   * Abschnitte stehen dort gar nicht, sie stehen unter der Karte auf der
   * Seite. Wer also links einen Abschnitt verschiebt, sieht in der Mitte
   * nichts, und das sah aus wie ein kaputter Editor.
   *
   * Der Rahmen daneben zeigt die ganze Seite - aber den GESPEICHERTEN Stand,
   * denn er holt sie vom Server. Verschieben ohne Speichern kaeme dort also
   * auch nicht an.
   *
   * Seit die Richtlinie die eigene Seite einrahmen laesst, liegt der Rahmen
   * im selben Ursprung: sein Inhalt ist erreichbar. Also wird die Reihenfolge
   * dort NACHGEZOGEN, statt auf das naechste Speichern zu warten.
   *
   * Nachgezogen und nicht neu gezeichnet: was der Server geschickt hat,
   * bleibt stehen: dieselben Knoten, nur in anderer Reihenfolge. Ein
   * Abschnitt, den es beim Laden des Rahmens noch nicht gab, ist dort nicht
   * zu finden - er kommt beim naechsten Speichern dazu, und der Hinweis unter
   * dem Rahmen sagt ohnehin, dass er den gespeicherten Stand zeigt.
   */
  var rahmenDokument = function () {
    var kind = rahmen && rahmen.querySelector("iframe");
    if (!kind || rahmen.hidden) return null;

    try {
      return kind.contentDocument;
    } catch (fehler) {
      // Sollte nicht vorkommen - gleicher Ursprung. Aber ein Editor, der an
      // einer Ausnahme stehenbleibt, ist schlimmer als einer, der eine
      // Kleinigkeit nicht kann.
      return null;
    }
  };

  var rahmenNachziehen = function () {
    var doc = rahmenDokument();
    if (!doc) return;

    secListe.querySelectorAll("[data-sec-zeile]").forEach(function (zeile) {
      var nummer = zeile.getAttribute("data-sec-zeile");
      var kennung = form.querySelector('[data-sec-kennung="' + nummer + '"]');
      if (!kennung || kennung.value.trim() === "") return;

      var abschnitt = doc.querySelector(".d-sec-" + kennung.value.trim());
      if (!abschnitt) return;

      // Ans Ende schieben - in der Reihenfolge der Liste ergibt das die Liste.
      abschnitt.parentNode.appendChild(abschnitt);

      // Weggenommen oder Auge zu: hier nur ausblenden. Ob der Abschnitt beim
      // Drucken wirklich wegfaellt, entscheidet der Server - das Auge ist
      // eines von zwei Kriterien, das andere ist, ob ueberhaupt Inhalt da ist.
      var auge = form.querySelector('[name="sec_on_' + nummer + '"]');
      var weg = zeile.hasAttribute("data-weg") || (auge && !auge.checked);
      abschnitt.style.display = weg ? "none" : "";
    });
  };

  // An dieselben Stellen haengen, an denen die Reihe neu geschrieben wird.
  var reiheVorher = reiheNeu;
  reiheNeu = function () {
    reiheVorher();
    rahmenNachziehen();
  };

  secListe.addEventListener("change", rahmenNachziehen);
  form.querySelectorAll("[data-ansicht]").forEach(function (knopf) {
    knopf.addEventListener("click", function () {
      // Der Rahmen entsteht beim ersten Klick; erst wenn er geladen hat, gibt
      // es darin etwas zu ordnen.
      var kind = rahmen.querySelector("iframe");
      if (kind) kind.addEventListener("load", rahmenNachziehen, { once: true });
      setTimeout(rahmenNachziehen, 60);
    });
  });
})();
