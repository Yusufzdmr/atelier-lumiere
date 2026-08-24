/**
 * Die fertige Einladung: Umschlag öffnen, Countdown, Musik.
 *
 * Ohne dieses Skript ist die Einladung trotzdem lesbar – der Umschlag wird
 * dann einfach nicht angezeigt (siehe [data-envelope] im Stylesheet-losen Fall
 * bleibt er sichtbar, deshalb blenden wir ihn hier auch beim Laden aus, wenn
 * jemand direkt zum Inhalt springt).
 */
(function () {
  "use strict";

  var envelope = document.querySelector("[data-envelope]");
  var music = document.querySelector("[data-music]");

  // Wie lange der Vorspann in die Karte uebergeht. Kein Feld im Panel:
  // das ist keine Gestaltung der Vorlage, sondern die Naht zwischen zwei
  // Sachen - und eine Naht soll ueberall gleich lang sein.
  var UEBERGANG_MS = 600;

  /* ---------------------------- Umschlag ---------------------------- */
  if (envelope) {
    // Sofort, nicht erst beim Oeffnen: solange das Kuvert zu ist, sind die
    // bewegten Ebenen der Karte noch nicht da. Ohne diese Zeile stuenden sie
    // waehrend des ganzen Vorspanns sichtbar hinter dem Film und spraengen
    // beim Freiwerden der Karte auf null zurueck, um dann einzublenden.
    document.documentElement.setAttribute("data-karte-frei", "false");

    var open = envelope.querySelector("[data-envelope-open]");
    var kind = envelope.getAttribute("data-animation") || "seal";

    var opened = false;

    var reveal = function () {
      if (opened) return;
      opened = true;

      // Reihenfolge: erst die Eroeffnungsszene (falls das Thema eine hat),
      // dann das Kuvert, dann die Karte. Die Szene laeuft ueber allem und
      // meldet sich nicht zurueck – wir warten ihre bekannte Dauer ab.
      var intro = document.querySelector("[data-intro]");
      var introMs = Number(envelope.getAttribute("data-intro-ms")) || 0;

      // Der Filmvorspann des Themas. Er ersetzt die gezeichnete Szene, wenn
      // das Thema einen mitbringt - und er sagt selbst, wie lange er dauert,
      // statt dass wir eine Zahl raten.
      var introBox = document.querySelector("[data-intro-video]");
      var introFilm = introBox && introBox.querySelector("[data-intro-film]");

      // Wer Bewegung abbestellt hat, bekommt die Szene gar nicht erst zu
      // sehen (im Stylesheet auf display:none) – dann auch nicht warten.
      var still = window.matchMedia && window.matchMedia("(prefers-reduced-motion: reduce)").matches;

      // Der Vorspann laeuft nur, wenn Bewegung erwuenscht ist. Wer sie
      // abbestellt hat, bekommt sofort die Karte.
      if (introFilm && !still) {
        // Der Film liegt schon da - sein erstes Bild IST das geschlossene
        // Kuvert. Hier ist nichts mehr einzublenden, nur zu starten.

        // data-intro-ms bleibt die Obergrenze. Laedt der Film nicht - schlechtes
        // Netz, Format vom Server nicht ausgeliefert -, haengt die Einladung
        // sonst vor einem schwarzen Kasten fest.
        var deckel = introMs > 0 ? introMs : 6000;
        var fertig = false;
        var schliessen = function () {
          if (fertig) return;
          fertig = true;

          // Weich statt hart. Hier stand introBox.hidden = true, und der
          // Film war in einem Bild weg. Das trug, solange sein letztes
          // Bild das Blatt der Karte war - ein Schnitt zwischen zwei
          // gleichen Bildern faellt niemandem auf. Ein Film, der auf
          // Blumen endet, hat diese Naht nicht, und dann sieht man sie.
          //
          // Eingeblendet wird nichts: bei card=none liegt die Karte
          // ohnehin schon darunter und wartet auf keine Bewegung. Also
          // ist das Ausblenden des Films selbst die Ueberblendung.
          //
          // pointerEvents zuerst: waehrend der Ueberblendung liegt eine
          // fast durchsichtige Flaeche ueber der Karte, und sie soll den
          // Finger durchlassen, statt ihn zu schlucken.
          introBox.style.pointerEvents = "none";
          introBox.style.transition = "opacity " + UEBERGANG_MS + "ms ease";
          introBox.style.opacity = "0";

          // Danach wirklich weg. Eine durchsichtige Flaeche bleibt sonst
          // im Baum stehen, und nicht jeder Browser laesst durch sie
          // hindurch, nur weil man sie nicht sieht.
          setTimeout(function () {
            introBox.hidden = true;
          }, UEBERGANG_MS);
        };

        introFilm.addEventListener("ended", schliessen, { once: true });
        introFilm.addEventListener("error", schliessen, { once: true });
        setTimeout(schliessen, deckel);

        introFilm.play().catch(schliessen);

        // Die Karte wartet, bis der Film durch ist - hoechstens aber deckel.
        // Die Laenge sagt der Film selbst; steht sie noch nicht fest (die
        // Metadaten sind beim Klick nicht immer da), gilt der Deckel. Sonst
        // saehe der Gast bei einem Vorspann von drei Sekunden sechs Sekunden
        // lang nichts.
        var dauer = isFinite(introFilm.duration) ? Math.round(introFilm.duration * 1000) : 0;
        introMs = dauer > 0 ? Math.min(dauer, deckel) : deckel;
      } else if (!intro || still) {
        introMs = 0;
      }

      if (intro && introMs > 0) {
        intro.setAttribute("data-playing", "true");
        setTimeout(function () {
          intro.setAttribute("data-playing", "false");
          intro.style.display = "none";
        }, introMs);
      }

      // Ab hier laeuft alles wie bisher, nur um die Szene versetzt.
      envelope.style.pointerEvents = "none";
      setTimeout(function () {
        envelope.setAttribute("data-open", "true");
      }, introMs);

      setTimeout(function () {
        envelope.style.opacity = "0";
      }, introMs + 1900);
      setTimeout(function () {
        envelope.style.display = "none";
      }, introMs + 2600);

      var card = document.querySelector(".t-card");
      if (card && kind !== "none") {
        // Auswahl im Panel (Themes::ANIMATIONS). Ein unbekannter Wert faellt
        // auf "rise" zurueck – lieber eine andere Bewegung als eine Karte,
        // die auf opacity 0 stehen bleibt.
        var frames = {
          seal:       [{ opacity: 0, transform: "translateY(24px)" }, { opacity: 1, transform: "none" }],
          fade:       [{ opacity: 0 }, { opacity: 1 }],
          rise:       [{ opacity: 0, transform: "translateY(60px)" }, { opacity: 1, transform: "none" }],
          zoom:       [{ opacity: 0, transform: "scale(.86)" }, { opacity: 1, transform: "none" }],
          zoomOut:    [{ opacity: 0, transform: "scale(1.18)" }, { opacity: 1, transform: "none" }],
          curtain:    [{ clipPath: "inset(0 50% 0 50%)" }, { clipPath: "inset(0 0 0 0)" }],
          unfold:     [{ opacity: 0, transform: "scaleY(.04)" }, { opacity: 1, transform: "none" }],
          flip:       [{ opacity: 0, transform: "perspective(1200px) rotateX(52deg)" }, { opacity: 1, transform: "none" }],
          slideLeft:  [{ opacity: 0, transform: "translateX(70px)" }, { opacity: 1, transform: "none" }],
          slideRight: [{ opacity: 0, transform: "translateX(-70px)" }, { opacity: 1, transform: "none" }],
          blur:       [{ opacity: 0, filter: "blur(14px)" }, { opacity: 1, filter: "blur(0)" }],
          petals:     [{ opacity: 0, transform: "rotate(-1.5deg) translateY(30px)" }, { opacity: 1, transform: "none" }],
        };

        // "unfold" klappt von der Oberkante her auf, nicht aus der Mitte.
        if (kind === "unfold") card.style.transformOrigin = "top center";

        card.animate(frames[kind] || frames.rise, {
          duration: Number(card.getAttribute("data-speed")) || 1100,
          delay: introMs + 1700,
          easing: "cubic-bezier(.16,1,.3,1)",
          fill: "both",
        });
      }

      // Erst wenn die Karte frei liegt, duerfen die Abschnitte anlaufen.
      // Vorher haette der Beobachter sie hinter der Huelle abgehakt, und
      // beim Aufschlagen stuende alles schon fertig da.
      /*
       * Jetzt erst duerfen sich die Ebenen der Karte bewegen.
       *
       * Und zwar bei introMs + 1700, nicht bei introMs: das ist derselbe
       * Zeitpunkt, an dem die Karte selbst zu steigen anfaengt (siehe delay
       * unten). Frueher waere das Einblenden wieder fuer niemanden - die
       * Karte liegt bis dahin auf Deckkraft 0, und der Text blendete unter
       * ihr ein. "Nach dem Kuvert" heisst: wenn die Karte da ist.
       */
      setTimeout(function () {
        document.documentElement.setAttribute("data-karte-frei", "true");
      }, introMs + 1700);

      // Die Filme der Ebenen. Sie tragen kein autoplay - sonst liefen sie
      // hinter dem geschlossenen Kuvert, unsichtbar und im Mobilfunk bezahlt.
      // Wer Bewegung abbestellt hat, sieht das Standbild und sonst nichts.
      if (!still) {
        setTimeout(function () {
          var filme = document.querySelectorAll("video.d-el");
          for (var i = 0; i < filme.length; i++) {
            filme[i].play().catch(function () {});
          }
        }, introMs);
      }

      setTimeout(startReveals, introMs + 1800);

      // Ton darf erst nach einer Nutzeraktion starten – hier ist sie.
      if (music) {
        music.play().catch(function () {});
      }
    };

    if (open) open.addEventListener("click", reveal);

    /*
     * Der Film ist selbst der Anklickpunkt, wenn es einen gibt.
     *
     * Sein erstes Bild ist das geschlossene Kuvert; wer darauf tippt, meint
     * genau das. Das gezeichnete Kuvert liegt darunter und wird nie
     * gesehen - es bleibt fuer die Vorlagen ohne Film und fuer den Fall,
     * dass der Film nicht laedt.
     */
    var introKlick = document.querySelector("[data-intro-video]");
    if (introKlick) introKlick.addEventListener("click", reveal);
    envelope.addEventListener("click", function (event) {
      if (event.target === envelope) reveal();
    });
  } else {
    // Keine Huelle (z. B. Vorschau im Panel): dann gleich losbewegen. Die
    // Marke wird hier NIE auf "false" gesetzt - es gibt nichts, worauf zu
    // warten waere.
    document.documentElement.setAttribute("data-karte-frei", "true");
    var ruhig = window.matchMedia && window.matchMedia("(prefers-reduced-motion: reduce)").matches;
    if (!ruhig) {
      var vorschau = document.querySelectorAll("video.d-el");
      for (var v = 0; v < vorschau.length; v++) {
        vorschau[v].play().catch(function () {});
      }
    }
    startReveals();
  }

  /* ------------------------ Abschnitte beim Scrollen ------------------------ */
  /*
   * Jeder Abschnitt startet erst, wenn der Gast ihn erreicht. Mit festen
   * Verzoegerungen war auf dem Handy die halbe Einladung durchgelaufen,
   * bevor man ueberhaupt hingescrollt hatte – unten kam dann nichts mehr an.
   */
  function startReveals() {
    var pieces = [].slice.call(document.querySelectorAll(".iv:not([data-visible])"));
    if (!pieces.length) return;

    var show = function (el) {
      if (el.getAttribute("data-visible") !== "true") el.setAttribute("data-visible", "true");
    };

    // Sicherheitsnetz wie auf den übrigen Seiten: ein Abschnitt, der im Bild
    // steht, wird sichtbar – auch wenn der Beobachter ihn nie gemeldet hat.
    // Unsichtbar bleiben heisst hier: der Gast sieht eine leere Karte.
    var sweeping = false;
    var sweep = function () {
      sweeping = false;
      var height = window.innerHeight || document.documentElement.clientHeight;
      pieces = pieces.filter(function (el) {
        if (el.getAttribute("data-visible") === "true") return false;
        var box = el.getBoundingClientRect();
        if (box.top < height - 60 && box.bottom > 0) {
          show(el);
          return false;
        }
        return true;
      });
    };
    var planSweep = function () {
      if (sweeping) return;
      sweeping = true;
      window.requestAnimationFrame(sweep);
    };

    if ("IntersectionObserver" in window) {
      var watcher = new IntersectionObserver(
        function (entries) {
          entries.forEach(function (entry) {
            if (!entry.isIntersecting) return;
            show(entry.target);
            watcher.unobserve(entry.target);
          });
        },
        { threshold: 0.1, rootMargin: "0px 0px -60px 0px" }
      );
      pieces.forEach(function (el) {
        watcher.observe(el);
      });
    }

    window.addEventListener("scroll", planSweep, { passive: true });
    window.addEventListener("resize", planSweep, { passive: true });
    window.addEventListener("load", planSweep);
    planSweep();
  }

  /* ---------------------------- Countdown ---------------------------- */
  document.querySelectorAll("[data-countdown]").forEach(function (box) {
    var target = new Date(box.getAttribute("data-countdown")).getTime();
    if (isNaN(target)) return;

    var fields = {
      days: box.querySelector("[data-days]"),
      hours: box.querySelector("[data-hours]"),
      minutes: box.querySelector("[data-minutes]"),
      seconds: box.querySelector("[data-seconds]"),
    };

    function tick() {
      var seconds = Math.max(0, Math.floor((target - Date.now()) / 1000));
      var parts = {
        days: Math.floor(seconds / 86400),
        hours: Math.floor((seconds % 86400) / 3600),
        minutes: Math.floor((seconds % 3600) / 60),
        seconds: seconds % 60,
      };
      Object.keys(fields).forEach(function (key) {
        if (fields[key]) fields[key].textContent = String(parts[key]).padStart(2, "0");
      });
    }

    tick();
    setInterval(tick, 1000);
  });

  /* ------------------------------ Musik ------------------------------ */
  var toggle = document.querySelector("[data-music-toggle]");
  if (toggle && music) {
    toggle.addEventListener("click", function () {
      if (music.paused) {
        music.play().catch(function () {});
        toggle.textContent = "♪";
      } else {
        music.pause();
        toggle.textContent = "♫";
      }
    });
  }
})();
