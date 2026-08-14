"use client";

import { useState, useSyncExternalStore } from "react";

import { getDict } from "@/lib/dict";
import type { Locale } from "@/lib/i18n";

/**
 * Zwei-Klick-Karte (DSGVO).
 *
 * Ohne Einwilligung wird nichts an Google gesendet: Es steht nur ein
 * Platzhalter mit der Anschrift da. Erst der Klick auf "Karte laden" – oder
 * eine bereits erteilte Marketing-Einwilligung im Cookie-Banner – bindet den
 * Google-Maps-iframe ein. Die Route lässt sich immer öffnen, das ist ein
 * gewöhnlicher Link ohne Datenübertragung im Hintergrund.
 */

const CONSENT_KEY = "al-consent-v1";

function hasMapConsent(): boolean {
  try {
    const raw = localStorage.getItem(CONSENT_KEY);
    return raw ? JSON.parse(raw)?.marketing === true : false;
  } catch {
    return false;
  }
}

/** Einwilligung als externer Store – reagiert sofort auf das Cookie-Banner. */
function subscribeConsent(onChange: () => void) {
  window.addEventListener("al:consent", onChange);
  window.addEventListener("storage", onChange);
  return () => {
    window.removeEventListener("al:consent", onChange);
    window.removeEventListener("storage", onChange);
  };
}

export default function ContactMap({
  locale,
  address,
  query,
}: {
  locale: Locale;
  /** Sichtbare Anschrift */
  address: string;
  /** Kartenziel – Anschrift oder abweichender Ort aus dem Admin */
  query: string;
}) {
  const t = getDict(locale).contact;

  // Auf dem Server und beim ersten Rendern immer der Platzhalter – so wird
  // ohne Einwilligung garantiert nichts an Google gesendet.
  const consented = useSyncExternalStore(subscribeConsent, hasMapConsent, () => false);
  const [clicked, setClicked] = useState(false);
  const loaded = consented || clicked;

  const q = encodeURIComponent(query);
  const routeUrl = `https://www.google.com/maps/dir/?api=1&destination=${q}`;
  const embedUrl = `https://www.google.com/maps?q=${q}&hl=${locale}&z=15&output=embed`;

  return (
    <div className="mt-6 border border-sand-deep">
      <div className="flex items-center justify-between gap-4 border-b border-sand-deep px-5 py-3">
        <span className="text-[0.66rem] uppercase tracking-[0.2em] text-gold">{t.mapTitle}</span>
        <a
          href={routeUrl}
          target="_blank"
          rel="noopener noreferrer"
          className="text-[0.66rem] uppercase tracking-[0.18em] text-ink transition-colors hover:text-gold"
        >
          {t.mapRoute} →
        </a>
      </div>

      {loaded ? (
        <>
          <iframe
            src={embedUrl}
            title={address}
            loading="lazy"
            referrerPolicy="no-referrer-when-downgrade"
            allowFullScreen
            className="block h-64 w-full border-0"
          />
          <a
            href={routeUrl}
            target="_blank"
            rel="noopener noreferrer"
            className="block bg-ink px-5 py-4 text-center text-[0.68rem] uppercase tracking-[0.2em] text-cream transition-colors hover:bg-gold"
          >
            {t.mapRoute}
          </a>
        </>
      ) : (
        <button
          type="button"
          onClick={() => setClicked(true)}
          className="flex h-64 w-full flex-col items-center justify-center gap-2 bg-sand px-6 text-center transition-colors hover:bg-sand-deep/40"
        >
          <span className="font-display text-xl text-ink">{address}</span>
          <span className="text-[0.66rem] uppercase tracking-[0.2em] text-gold">{t.mapLoad}</span>
          <span className="mt-1 max-w-xs text-[0.7rem] leading-relaxed text-muted">{t.mapNote}</span>
        </button>
      )}
    </div>
  );
}
