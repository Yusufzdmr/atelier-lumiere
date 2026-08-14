/**
 * Einwilligung und Messung.
 *
 * Die Reihenfolge ist hier kein Detail, sondern der ganze Punkt:
 *
 *   1. Vor jedem Google-Skript steht der Consent Mode auf "denied".
 *   2. Geladen wird ein Anbieter nur, wenn die passende Kategorie zugestimmt
 *      hat – Statistik fuer Analytics, Marketing fuer Ads und Pixel.
 *   3. Erst danach folgt das Update mit der tatsaechlichen Auswahl.
 *
 * Wer ablehnt, loest keine einzige Anfrage an Google oder Meta aus – nicht
 * einmal die Skriptdatei wird geholt.
 */
(function () {
  "use strict";

  var KEY = "al-consent-v1";

  var carrier = document.querySelector("[data-tracking]");
  var config = {};
  try {
    config = JSON.parse(carrier ? carrier.getAttribute("data-tracking") || "{}" : "{}");
  } catch (error) {
    config = {};
  }

  /* ------------------------------ Entscheidung ----------------------------- */

  function read() {
    try {
      var raw = window.localStorage.getItem(KEY);
      return raw ? JSON.parse(raw) : null;
    } catch (error) {
      return null;
    }
  }

  function write(stats, marketing) {
    try {
      window.localStorage.setItem(
        KEY,
        JSON.stringify({ necessary: true, stats: stats, marketing: marketing, at: new Date().toISOString() })
      );
    } catch (error) {
      // Privater Modus ohne Speicher: die Auswahl gilt dann nur fuer diesen Besuch.
    }
    window.dispatchEvent(new Event("al:consent"));
  }

  /* -------------------------------- Banner --------------------------------- */

  var banner = document.querySelector("[data-consent]");

  function show(withDetails) {
    if (!banner) return;
    banner.hidden = false;
    if (withDetails) openDetails();
  }

  function openDetails() {
    if (!banner) return;
    var details = banner.querySelector("[data-consent-details]");
    var settings = banner.querySelector("[data-consent-settings]");
    var save = banner.querySelector("[data-consent-save]");
    if (details) details.hidden = false;
    if (settings) settings.hidden = true;
    if (save) save.hidden = false;

    // Beim erneuten Oeffnen die gespeicherte Auswahl wieder anzeigen.
    var current = read();
    if (current) {
      var stats = banner.querySelector("[data-consent-stats]");
      var marketing = banner.querySelector("[data-consent-marketing]");
      if (stats) stats.checked = Boolean(current.stats);
      if (marketing) marketing.checked = Boolean(current.marketing);
    }
  }

  function decide(stats, marketing) {
    write(stats, marketing);
    if (banner) banner.hidden = true;
  }

  if (banner) {
    var all = banner.querySelector("[data-consent-all]");
    var none = banner.querySelector("[data-consent-none]");
    var settingsButton = banner.querySelector("[data-consent-settings]");
    var saveButton = banner.querySelector("[data-consent-save]");

    if (all) all.addEventListener("click", function () { decide(true, true); });
    if (none) none.addEventListener("click", function () { decide(false, false); });
    if (settingsButton) settingsButton.addEventListener("click", openDetails);
    if (saveButton) {
      saveButton.addEventListener("click", function () {
        var stats = banner.querySelector("[data-consent-stats]");
        var marketing = banner.querySelector("[data-consent-marketing]");
        decide(Boolean(stats && stats.checked), Boolean(marketing && marketing.checked));
      });
    }

    // Erst nach kurzer Zeit einblenden, damit der Kasten nicht das Erste ist,
    // was beim Laden umherspringt.
    if (!read()) {
      window.setTimeout(function () { show(false); }, 900);
    }
  }

  // Fussbereich und Datenschutzerklaerung koennen die Auswahl wieder oeffnen.
  document.addEventListener("click", function (event) {
    var opener = event.target && event.target.closest ? event.target.closest("[data-consent-open]") : null;
    if (opener) {
      event.preventDefault();
      show(true);
    }
  });

  /* -------------------------------- Messung -------------------------------- */

  function gtag() {
    window.dataLayer = window.dataLayer || [];
    window.dataLayer.push(arguments);
  }

  function addScript(id, src) {
    if (document.getElementById(id)) return false;
    var script = document.createElement("script");
    script.id = id;
    script.async = true;
    script.src = src;
    document.head.appendChild(script);
    return true;
  }

  var baseReady = false;
  function ensureBase() {
    if (baseReady) return;
    baseReady = true;
    window.gtag = window.gtag || gtag;

    if (config.consentMode) {
      // Muss im dataLayer stehen, BEVOR ein Google-Skript geladen wird.
      gtag("consent", "default", {
        ad_storage: "denied",
        ad_user_data: "denied",
        ad_personalization: "denied",
        analytics_storage: "denied",
        wait_for_update: 500
      });
      gtag("set", "ads_data_redaction", true);
      gtag("set", "url_passthrough", true);
    }
    gtag("js", new Date());
  }

  function apply() {
    if (!config.gaId && !config.gtmId && !config.adsId && !config.metaPixelId) return;

    var consent = read();
    if (!consent) return; // Noch keine Entscheidung: nichts laden.

    var stats = Boolean(consent.stats);
    var marketing = Boolean(consent.marketing);
    if (!stats && !marketing) return;

    ensureBase();

    // Ein gtag.js genuegt fuer Analytics und Ads zusammen.
    var googleId = (stats && config.gaId) || (marketing && config.adsId) || "";
    if (googleId) {
      addScript("ga-script", "https://www.googletagmanager.com/gtag/js?id=" + encodeURIComponent(googleId));
    }

    if (stats && config.gaId) gtag("config", config.gaId, { anonymize_ip: true });
    if (marketing && config.adsId) gtag("config", config.adsId);

    if (config.gtmId) {
      addScript("gtm-script", "https://www.googletagmanager.com/gtm.js?id=" + encodeURIComponent(config.gtmId));
    }

    if (marketing && config.metaPixelId && !window.fbq) {
      var fbq = function () {
        if (fbq.callMethod) fbq.callMethod.apply(fbq, arguments);
        else fbq.queue.push(arguments);
      };
      fbq.queue = [];
      fbq.version = "2.0";
      window.fbq = fbq;
      window._fbq = fbq;
      addScript("meta-pixel", "https://connect.facebook.net/en_US/fbevents.js");
      window.fbq("init", config.metaPixelId);
      window.fbq("track", "PageView");
    }

    if (config.consentMode) {
      gtag("consent", "update", {
        analytics_storage: stats ? "granted" : "denied",
        ad_storage: marketing ? "granted" : "denied",
        ad_user_data: marketing ? "granted" : "denied",
        ad_personalization: marketing ? "granted" : "denied"
      });
    }
  }

  apply();
  window.addEventListener("al:consent", apply);

  /* ------------------ Abschluesse: Anfrage, Einladung, Anruf ---------------- */

  function track(event, value) {
    var consent = read();
    var stats = Boolean(consent && consent.stats);
    var marketing = Boolean(consent && consent.marketing);
    var labels = config.adsLabels || {};
    var currency = config.currency || "EUR";

    var amount = value;
    if (amount === undefined && config.leadValue) {
      amount = Number(String(config.leadValue).replace(",", "."));
    }
    if (!amount || isNaN(amount)) amount = undefined;

    // GA4 als Ereignis – auch ohne Ads-Konto brauchbar.
    if (stats && window.gtag) {
      var name = event === "contact" ? "generate_lead" : event === "invite" ? "purchase" : "phone_call";
      window.gtag("event", name, amount ? { value: amount, currency: currency } : {});
    }

    if (marketing && window.gtag && config.adsId && labels[event]) {
      var payload = { send_to: config.adsId + "/" + labels[event] };
      if (amount) {
        payload.value = amount;
        payload.currency = currency;
      }
      window.gtag("event", "conversion", payload);
    }

    if (marketing && config.metaPixelId && window.fbq) {
      if (event === "invite") window.fbq("track", "Purchase", { value: amount || 0, currency: currency });
      else window.fbq("track", "Lead");
    }
  }

  window.alTrack = track;

  // Ein Tipp auf die Telefonnummer zaehlt als Anruf – ein Zuhoerer fuer die
  // ganze Seite, damit kein tel:-Link im HTML davon wissen muss.
  document.addEventListener("click", function (event) {
    var link = event.target && event.target.closest ? event.target.closest("a[href^='tel:']") : null;
    if (link) track("phone");
  });

  // Seiten, die nach dem Absenden zurueckkommen, melden ihren Abschluss ueber
  // ein Datenfeld statt ueber einen Skriptblock im HTML.
  var done = document.querySelector("[data-track-event]");
  if (done) {
    var value = done.getAttribute("data-track-value");
    track(done.getAttribute("data-track-event"), value ? Number(value) : undefined);
  }
})();
