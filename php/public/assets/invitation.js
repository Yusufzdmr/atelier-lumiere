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
        var frames = {
          seal: [{ opacity: 0, transform: "translateY(24px)" }, { opacity: 1, transform: "none" }],
          fade: [{ opacity: 0 }, { opacity: 1 }],
          curtain: [{ clipPath: "inset(0 50% 0 50%)" }, { clipPath: "inset(0 0 0 0)" }],
          petals: [{ opacity: 0, transform: "rotate(-1.5deg) translateY(30px)" }, { opacity: 1, transform: "none" }],
        };
        card.animate(frames[kind] || frames.seal, {
          duration: 1100,
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
    var pieces = document.querySelectorAll(".iv:not([data-visible])");
    if (!pieces.length) return;

    if (!("IntersectionObserver" in window)) {
      // Ohne Beobachter lieber alles sichtbar als alles unsichtbar.
      pieces.forEach(function (el) {
        el.setAttribute("data-visible", "true");
      });
      return;
    }

    var watcher = new IntersectionObserver(
      function (entries) {
        entries.forEach(function (entry) {
          if (!entry.isIntersecting) return;
          entry.target.setAttribute("data-visible", "true");
          watcher.unobserve(entry.target);
        });
      },
      { threshold: 0.1, rootMargin: "0px 0px -60px 0px" }
    );
    pieces.forEach(function (el) {
      watcher.observe(el);
    });
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
