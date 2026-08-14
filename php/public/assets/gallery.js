/**
 * Kundengalerie: Auswahl, Lightbox, Absenden.
 *
 * Die Auswahl liegt zusätzlich im Browser (localStorage), damit ein Paar den
 * Tab schließen kann, ohne alles zu verlieren – abgeschickt wird sie erst
 * mit dem Knopf.
 */
(function () {
  "use strict";

  var root = document.querySelector("[data-gallery]");
  if (!root) return;

  var code = root.getAttribute("data-code") || "";
  var csrf = root.getAttribute("data-csrf") || "";
  var endpoint = root.getAttribute("data-endpoint") || "";
  var storeKey = "al-picks-" + code;

  var text = {
    sent: (document.querySelector("[data-sent-text]") || {}).textContent || "",
    sending: (document.querySelector("[data-sending-text]") || {}).textContent || "",
    of: (document.querySelector("[data-of-text]") || {}).textContent || "/",
  };

  var buttons = Array.prototype.slice.call(root.querySelectorAll("[data-pick]"));
  var thumbs = Array.prototype.slice.call(root.querySelectorAll("[data-photo]"));
  var counter = root.querySelector("[data-count]");
  var status = root.querySelector("[data-status]");
  var noteField = root.querySelector("[data-note]");
  var sendButton = root.querySelector("[data-send]");

  /* --------------------------- Auswahl --------------------------- */

  function stored() {
    // Der Server kennt die zuletzt abgeschickte Auswahl; der Browser
    // eventuell eine neuere, noch nicht abgeschickte.
    try {
      var raw = localStorage.getItem(storeKey);
      if (raw) return JSON.parse(raw);
    } catch (e) {}
    var initial = root.getAttribute("data-picks") || "";
    return initial ? initial.split(",").map(Number) : [];
  }

  var picks = stored();

  function persist() {
    try {
      localStorage.setItem(storeKey, JSON.stringify(picks));
    } catch (e) {}
  }

  function paint() {
    buttons.forEach(function (button) {
      var index = Number(button.getAttribute("data-pick"));
      var active = picks.indexOf(index) !== -1;
      button.setAttribute("aria-pressed", active ? "true" : "false");
      var heart = button.querySelector("[data-heart]");
      if (heart) heart.style.color = active ? "#B08D57" : "#7A6F65";
    });
    if (counter) counter.textContent = String(picks.length);
  }

  function toggle(index) {
    var at = picks.indexOf(index);
    if (at === -1) picks.push(index);
    else picks.splice(at, 1);
    persist();
    paint();
    if (status) status.textContent = "";
  }

  buttons.forEach(function (button) {
    button.addEventListener("click", function () {
      toggle(Number(button.getAttribute("data-pick")));
    });
  });

  paint();

  /* --------------------------- Lightbox --------------------------- */

  var box = root.querySelector("[data-lightbox]");
  var boxImage = root.querySelector("[data-lightbox-image]");
  var position = root.querySelector("[data-position]");
  var download = root.querySelector("[data-download]");
  var current = null;

  function open(index) {
    if (!box || !boxImage) return;
    current = index;
    var thumb = thumbs[index];
    var full = thumb ? thumb.getAttribute("data-full") : "";
    boxImage.src = full || "";
    if (download) download.href = full || "";
    if (position) position.textContent = index + 1 + " " + text.of + " " + thumbs.length;
    box.classList.remove("hidden");
    box.classList.add("flex");
    document.body.style.overflow = "hidden";
  }

  function close() {
    if (!box) return;
    current = null;
    box.classList.add("hidden");
    box.classList.remove("flex");
    document.body.style.overflow = "";
  }

  function step(direction) {
    if (current === null || thumbs.length === 0) return;
    open((current + direction + thumbs.length) % thumbs.length);
  }

  thumbs.forEach(function (thumb) {
    thumb.addEventListener("click", function () {
      open(Number(thumb.getAttribute("data-photo")));
    });
  });

  if (box) {
    var closeButton = box.querySelector("[data-close]");
    var prevButton = box.querySelector("[data-prev]");
    var nextButton = box.querySelector("[data-next]");
    var pickButton = box.querySelector("[data-lightbox-pick]");

    if (closeButton) closeButton.addEventListener("click", close);
    if (prevButton) prevButton.addEventListener("click", function () { step(-1); });
    if (nextButton) nextButton.addEventListener("click", function () { step(1); });
    if (pickButton) pickButton.addEventListener("click", function () { if (current !== null) toggle(current); });

    box.addEventListener("click", function (event) {
      if (event.target === box) close();
    });

    document.addEventListener("keydown", function (event) {
      if (current === null) return;
      if (event.key === "Escape") close();
      if (event.key === "ArrowRight") step(1);
      if (event.key === "ArrowLeft") step(-1);
      if (event.key === " ") {
        event.preventDefault();
        toggle(current);
      }
    });
  }

  /* --------------------------- Absenden --------------------------- */

  if (sendButton) {
    sendButton.addEventListener("click", function () {
      sendButton.disabled = true;
      if (status) status.textContent = text.sending;

      fetch(endpoint, {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({
          code: code,
          csrf: csrf,
          picks: picks,
          note: noteField ? noteField.value : "",
        }),
      })
        .then(function (response) {
          return response.ok ? response.json() : Promise.reject();
        })
        .then(function () {
          if (status) status.textContent = text.sent;
        })
        .catch(function () {
          if (status) status.textContent = "✕";
        })
        .then(function () {
          sendButton.disabled = false;
        });
    });
  }
})();
