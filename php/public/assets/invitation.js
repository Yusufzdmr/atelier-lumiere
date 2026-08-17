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

  /* ---------------------------- Umschlag ---------------------------- */
  if (envelope) {
    var open = envelope.querySelector("[data-envelope-open]");
    var kind = envelope.getAttribute("data-animation") || "seal";

    var opened = false;

    var reveal = function () {
      if (opened) return;
      opened = true;

      // Erst spielt das Kuvert: Siegel bricht, Klappe schlaegt auf, Karte
      // hebt sich heraus. Vorher blendete hier sofort alles aus – die
      // Animation lief zwar, sah sie aber niemand.
      envelope.setAttribute("data-open", "true");
      envelope.style.pointerEvents = "none";

      setTimeout(function () {
        envelope.style.opacity = "0";
      }, 1900);
      setTimeout(function () {
        envelope.style.display = "none";
      }, 2600);

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
          delay: 1700,
          easing: "cubic-bezier(.16,1,.3,1)",
          fill: "both",
        });
      }

      // Erst wenn die Karte frei liegt, duerfen die Abschnitte anlaufen.
      // Vorher haette der Beobachter sie hinter der Huelle abgehakt, und
      // beim Aufschlagen stuende alles schon fertig da.
      setTimeout(startReveals, 1800);

      // Ton darf erst nach einer Nutzeraktion starten – hier ist sie.
      if (music) {
        music.play().catch(function () {});
      }
    };

    if (open) open.addEventListener("click", reveal);
    envelope.addEventListener("click", function (event) {
      if (event.target === envelope) reveal();
    });
  } else {
    // Keine Huelle (z. B. Vorschau im Panel): dann gleich losbewegen.
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
