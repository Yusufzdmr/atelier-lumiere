"use client";

import { useEffect } from "react";

import type { PublicTracking } from "@/lib/integrations";
import type { TrackEvent } from "@/lib/track";

const CONSENT_KEY = "al-consent-v1";

type Consent = { stats?: boolean; marketing?: boolean };

function readConsent(): Consent | null {
  try {
    const raw = localStorage.getItem(CONSENT_KEY);
    return raw ? (JSON.parse(raw) as Consent) : null;
  } catch {
    return null;
  }
}

function addScript(id: string, src: string) {
  if (document.getElementById(id)) return false;
  const s = document.createElement("script");
  s.id = id;
  s.async = true;
  s.src = src;
  document.head.appendChild(s);
  return true;
}

/**
 * Analytics, Google Ads und Meta Pixel – aber erst nach Einwilligung.
 *
 * Reihenfolge ist hier kein Detail, sondern der ganze Punkt:
 * 1. Vor jedem Skript wird der Consent Mode auf „denied“ gesetzt.
 * 2. Geladen wird ein Anbieter nur, wenn die passende Kategorie zugestimmt hat
 *    (Statistik → Analytics, Marketing → Ads und Pixel).
 * 3. Erst danach folgt das Consent-Update mit der tatsaechlichen Auswahl.
 *
 * Wer im Banner ablehnt, bekommt keine einzige Anfrage an Google oder Meta –
 * nicht einmal die Skriptdatei.
 */
export default function Tracking({ config }: { config: PublicTracking }) {
  useEffect(() => {
    const { gaId, gtmId, adsId, metaPixelId, consentMode } = config;
    if (!gaId && !gtmId && !adsId && !metaPixelId) return;

    const gtag = (...args: unknown[]) => {
      window.dataLayer = window.dataLayer || [];
      window.dataLayer.push(args);
    };

    let baseReady = false;
    const ensureBase = () => {
      if (baseReady) return;
      baseReady = true;
      window.gtag = window.gtag ?? gtag;
      if (consentMode) {
        // Muss vor jedem Google-Skript im dataLayer stehen.
        gtag("consent", "default", {
          ad_storage: "denied",
          ad_user_data: "denied",
          ad_personalization: "denied",
          analytics_storage: "denied",
          wait_for_update: 500,
        });
        gtag("set", "ads_data_redaction", true);
        gtag("set", "url_passthrough", true);
      }
      gtag("js", new Date());
    };

    const apply = () => {
      const consent = readConsent();
      if (!consent) return; // Noch keine Entscheidung: nichts laden.

      const stats = Boolean(consent.stats);
      const marketing = Boolean(consent.marketing);
      if (!stats && !marketing) return;

      ensureBase();

      // Ein gtag.js genuegt fuer Analytics und Ads zusammen.
      const googleId = (stats && gaId) || (marketing && adsId) || "";
      if (googleId) addScript("ga-script", `https://www.googletagmanager.com/gtag/js?id=${googleId}`);

      if (stats && gaId) gtag("config", gaId, { anonymize_ip: true });
      if (marketing && adsId) gtag("config", adsId);

      if (gtmId) {
        addScript("gtm-script", `https://www.googletagmanager.com/gtm.js?id=${gtmId}`);
      }

      if (marketing && metaPixelId && !window.fbq) {
        const fbq = ((...args: unknown[]) => {
          const q = fbq as unknown as { queue: unknown[]; callMethod?: (...a: unknown[]) => void };
          if (q.callMethod) q.callMethod(...args);
          else q.queue.push(args);
        }) as Window["fbq"] & { queue: unknown[] };
        fbq.queue = [];
        fbq.version = "2.0";
        window.fbq = fbq;
        window._fbq = fbq;
        addScript("meta-pixel", "https://connect.facebook.net/en_US/fbevents.js");
        window.fbq("init", metaPixelId);
        window.fbq("track", "PageView");
      }

      if (consentMode) {
        gtag("consent", "update", {
          analytics_storage: stats ? "granted" : "denied",
          ad_storage: marketing ? "granted" : "denied",
          ad_user_data: marketing ? "granted" : "denied",
          ad_personalization: marketing ? "granted" : "denied",
        });
      }
    };

    apply();
    window.addEventListener("al:consent", apply);
    return () => window.removeEventListener("al:consent", apply);
  }, [config]);

  /* --------- Conversions: Anfrage, Einladung, Anruf --------- */
  useEffect(() => {
    const { adsId, adsLabels, leadValue, currency, metaPixelId } = config;

    const send = (event: TrackEvent, value?: number) => {
      const consent = readConsent();
      const stats = Boolean(consent?.stats);
      const marketing = Boolean(consent?.marketing);

      const amount = value ?? (leadValue ? Number(leadValue.replace(",", ".")) : undefined);

      // GA4: als Ereignis, auch ohne Ads-Konto nuetzlich.
      if (stats && window.gtag) {
        const name = event === "contact" ? "generate_lead" : event === "invite" ? "purchase" : "phone_call";
        window.gtag("event", name, amount ? { value: amount, currency } : {});
      }

      if (marketing && window.gtag && adsId && adsLabels[event]) {
        window.gtag("event", "conversion", {
          send_to: `${adsId}/${adsLabels[event]}`,
          ...(amount ? { value: amount, currency } : {}),
        });
      }

      if (marketing && metaPixelId && window.fbq) {
        if (event === "invite") window.fbq("track", "Purchase", { value: amount ?? 0, currency });
        else window.fbq("track", "Lead");
      }
    };

    window.alTrack = send;

    // Tipp auf die Telefonnummer zaehlt als Anruf – ohne dass jeder
    // tel:-Link im Code davon wissen muss.
    const onClick = (e: MouseEvent) => {
      const link = (e.target as HTMLElement | null)?.closest?.("a[href^='tel:']");
      if (link) send("phone");
    };
    document.addEventListener("click", onClick);

    return () => {
      document.removeEventListener("click", onClick);
      delete window.alTrack;
    };
  }, [config]);

  return null;
}
