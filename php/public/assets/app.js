/**
 * Verhalten der Website – ohne Framework.
 *
 * Alles, was in der Next.js-Fassung ein kleiner Client-Baustein war, steht
 * hier: Kopfbereich, Menü, Einblenden beim Scrollen, Countdown. Die
 * aufwendigen Teile (Einladungs-Assistent, Kundengalerie) bekommen jeweils
 * ein eigenes Skript.
 */
(function () {
  "use strict";

  /* ------------------------- Kopfbereich ------------------------- */
  var header = document.getElementById("site-header");
  var toggle = document.getElementById("menu-toggle");
  var overlay = document.getElementById("menu-overlay");
  var open = false;

  function paintHeader() {
    if (!header) return;
    var solid = window.scrollY > 24 || open;
    header.classList.toggle("bg-cream/95", solid);
    header.classList.toggle("backdrop-blur-md", solid);
    header.classList.toggle("border-b", solid);
    header.classList.toggle("border-sand-deep/40", solid);
    header.classList.toggle("py-3", solid);
    header.classList.toggle("bg-transparent", !solid);
    header.classList.toggle("py-6", !solid);
  }

  function setMenu(next) {
    open = next;
    if (!overlay || !toggle) return;

    overlay.classList.toggle("opacity-100", open);
    overlay.classList.toggle("opacity-0", !open);
    overlay.classList.toggle("pointer-events-auto", open);
    overlay.classList.toggle("pointer-events-none", !open);
    toggle.setAttribute("aria-expanded", open ? "true" : "false");
    document.body.style.overflow = open ? "hidden" : "";

    overlay.querySelectorAll(".menu-item").forEach(function (item) {
      item.classList.toggle("opacity-0", !open);
      item.classList.toggle("translate-y-3", !open);
      item.classList.toggle("opacity-100", open);
    });

    var bars = toggle.querySelectorAll(".bar");
    if (bars.length === 3) {
      bars[0].classList.toggle("translate-y-[6px]", open);
      bars[0].classList.toggle("rotate-45", open);
      bars[1].classList.toggle("opacity-0", open);
      bars[2].classList.toggle("-translate-y-[6px]", open);
      bars[2].classList.toggle("-rotate-45", open);
    }

    paintHeader();
  }

  if (toggle) toggle.addEventListener("click", function () { setMenu(!open); });
  window.addEventListener("scroll", paintHeader, { passive: true });
  paintHeader();

  /* --------------------- Einblenden beim Scrollen --------------------- */
  // Dieselben Klassen wie in der bisherigen Fassung: .reveal / .reveal-mask
  // werden über data-visible geschaltet, das Stylesheet bleibt unverändert.
  var reveals = document.querySelectorAll(".reveal, .reveal-mask");
  if (reveals.length) {
    if (!("IntersectionObserver" in window)) {
      reveals.forEach(function (el) { el.setAttribute("data-visible", "true"); });
    } else {
      var observer = new IntersectionObserver(
        function (entries) {
          entries.forEach(function (entry) {
            if (!entry.isIntersecting) return;
            entry.target.setAttribute("data-visible", "true");
            observer.unobserve(entry.target);
          });
        },
        { rootMargin: "0px 0px -40px 0px", threshold: 0.12 }
      );
      reveals.forEach(function (el) { observer.observe(el); });
    }
  }

  /* --------------------------- Aufklappen --------------------------- */
  document.querySelectorAll("[data-accordion] > button").forEach(function (button) {
    button.addEventListener("click", function () {
      var item = button.parentElement;
      var wasOpen = item.getAttribute("data-open") === "1";
      item.setAttribute("data-open", wasOpen ? "0" : "1");
      var panel = item.querySelector("[data-panel]");
      if (panel) panel.style.maxHeight = wasOpen ? "0px" : panel.scrollHeight + "px";
      var sign = item.querySelector("[data-sign]");
      if (sign) sign.textContent = wasOpen ? "+" : "−";
    });
  });

  /* ------------------- Video: erst nach dem Klick ------------------- */
  // Vorher geht keine Anfrage an YouTube oder Vimeo – auch nicht für das
  // Vorschaubild. Deshalb liegt das Standbild aus eigener Quelle darunter.
  document.querySelectorAll("[data-video]").forEach(function (box) {
    var button = box.querySelector("[data-video-load]");
    if (!button) return;

    button.addEventListener("click", function () {
      var frame = document.createElement("iframe");
      frame.src = box.getAttribute("data-embed") || "";
      frame.title = box.getAttribute("data-title") || "Video";
      frame.allow = "accelerometer; autoplay; clipboard-write; encrypted-media; picture-in-picture";
      frame.allowFullscreen = true;
      frame.className = "absolute inset-0 h-full w-full";
      frame.setAttribute("loading", "lazy");
      box.innerHTML = "";
      box.appendChild(frame);
    });
  });

  /* ------------------ Karte: erst nach dem Klick ------------------- */
  document.querySelectorAll("[data-map]").forEach(function (box) {
    var button = box.querySelector("[data-map-load]");
    if (!button) return;

    button.addEventListener("click", function () {
      var query = box.getAttribute("data-query") || "";
      var frame = document.createElement("iframe");
      frame.src = "https://www.google.com/maps?q=" + encodeURIComponent(query) + "&output=embed";
      frame.title = "Karte";
      frame.className = "mt-5 w-full border border-sand-deep";
      frame.height = "320";
      frame.setAttribute("loading", "lazy");
      frame.setAttribute("referrerpolicy", "no-referrer-when-downgrade");
      button.remove();
      box.appendChild(frame);
    });
  });

  /* ------------- Cookie-Einstellungen aus den Rechtstexten ------------- */
  document.querySelectorAll("[data-consent-open]").forEach(function (button) {
    button.addEventListener("click", function () {
      window.dispatchEvent(new Event("al:open-consent"));
    });
  });

  /* --------------------------- Countdown --------------------------- */
  document.querySelectorAll("[data-countdown]").forEach(function (el) {
    var target = new Date(el.getAttribute("data-countdown")).getTime();
    if (isNaN(target)) return;

    var fields = {
      days: el.querySelector("[data-days]"),
      hours: el.querySelector("[data-hours]"),
      minutes: el.querySelector("[data-minutes]"),
      seconds: el.querySelector("[data-seconds]"),
    };

    function tick() {
      var diff = Math.max(0, target - Date.now());
      var s = Math.floor(diff / 1000);
      var parts = {
        days: Math.floor(s / 86400),
        hours: Math.floor((s % 86400) / 3600),
        minutes: Math.floor((s % 3600) / 60),
        seconds: s % 60,
      };
      Object.keys(fields).forEach(function (key) {
        if (fields[key]) fields[key].textContent = String(parts[key]).padStart(2, "0");
      });
    }

    tick();
    setInterval(tick, 1000);
  });
})();
