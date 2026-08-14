"use client";

import { useEffect, useState } from "react";
import Link from "next/link";
import { getDict } from "@/lib/dict";
import type { Locale } from "@/lib/i18n";

const KEY = "al-consent-v1";

type Consent = { necessary: true; stats: boolean; marketing: boolean; at: string };

/**
 * DSGVO-konformes Consent-Banner:
 * - keine vorangekreuzten Kästchen
 * - "Ablehnen" gleichwertig sichtbar wie "Akzeptieren"
 * - Statistik-Skripte werden erst NACH Einwilligung nachgeladen
 * - Entscheidung jederzeit über den Footer-Link widerrufbar
 */
export default function CookieConsent({ locale }: { locale: Locale }) {
  const t = getDict(locale).cookie;
  const [show, setShow] = useState(false);
  const [details, setDetails] = useState(false);
  const [stats, setStats] = useState(false);
  const [marketing, setMarketing] = useState(false);

  useEffect(() => {
    try {
      // Liegt schon eine Entscheidung vor, bleibt das Banner weg. Die Skripte
      // lädt <Tracking> anhand derselben gespeicherten Auswahl.
      if (!localStorage.getItem(KEY)) {
        // Banner leicht verzögert einblenden, damit der LCP nicht gestört wird
        const id = window.setTimeout(() => setShow(true), 900);
        return () => window.clearTimeout(id);
      }
    } catch {
      // eslint-disable-next-line react-hooks/set-state-in-effect -- Fallback, wenn localStorage nicht lesbar ist
      setShow(true);
    }
  }, []);

  useEffect(() => {
    const open = () => {
      setDetails(true);
      setShow(true);
    };
    window.addEventListener("al:open-consent", open);
    return () => window.removeEventListener("al:open-consent", open);
  }, []);

  function persist(c: Omit<Consent, "at" | "necessary">) {
    const value: Consent = { necessary: true, ...c, at: new Date().toISOString() };
    localStorage.setItem(KEY, JSON.stringify(value));
    // Eingebettete Inhalte (Karte, Video) und <Tracking> reagieren sofort
    window.dispatchEvent(new Event("al:consent"));
    setShow(false);
  }

  if (!show) return null;

  return (
    <div className="fixed inset-x-0 bottom-0 z-[60] p-3 sm:p-5">
      <div className="mx-auto max-w-3xl border border-sand-deep bg-cream p-5 shadow-[0_20px_60px_-20px_rgba(20,17,15,0.35)] sm:p-7">
        <h2 className="font-display text-lg font-normal text-ink">{t.title}</h2>
        <p className="mt-2 text-sm leading-relaxed text-muted">{t.text}</p>

        {details && (
          <div className="mt-4 space-y-2 border-t border-sand pt-4 text-sm">
            <label className="flex cursor-not-allowed items-center gap-3 text-muted">
              <input type="checkbox" checked readOnly className="h-4 w-4 accent-[#B08D57]" />
              {t.necessary}
            </label>
            <label className="flex cursor-pointer items-center gap-3 text-ink">
              <input
                type="checkbox"
                checked={stats}
                onChange={(e) => setStats(e.target.checked)}
                className="h-4 w-4 accent-[#B08D57]"
              />
              {t.stats}
            </label>
            <label className="flex cursor-pointer items-center gap-3 text-ink">
              <input
                type="checkbox"
                checked={marketing}
                onChange={(e) => setMarketing(e.target.checked)}
                className="h-4 w-4 accent-[#B08D57]"
              />
              {t.marketing}
            </label>
          </div>
        )}

        <div className="mt-5 flex flex-col gap-2 sm:flex-row sm:items-center">
          <button
            onClick={() => persist({ stats: true, marketing: true })}
            className="bg-ink px-6 py-3 text-[0.72rem] uppercase tracking-[0.2em] text-cream transition-opacity hover:opacity-85"
          >
            {t.acceptAll}
          </button>
          <button
            onClick={() => persist({ stats: false, marketing: false })}
            className="border border-ink px-6 py-3 text-[0.72rem] uppercase tracking-[0.2em] text-ink transition-colors hover:bg-ink hover:text-cream"
          >
            {t.acceptNecessary}
          </button>
          {details ? (
            <button
              onClick={() => persist({ stats, marketing })}
              className="px-2 py-3 text-[0.72rem] uppercase tracking-[0.2em] text-gold underline-offset-4 hover:underline"
            >
              {t.save}
            </button>
          ) : (
            <button
              onClick={() => setDetails(true)}
              className="px-2 py-3 text-[0.72rem] uppercase tracking-[0.2em] text-muted underline-offset-4 hover:underline"
            >
              {t.settings}
            </button>
          )}
          <Link
            href={`/${locale}/datenschutz`}
            className="px-2 py-3 text-[0.72rem] uppercase tracking-[0.2em] text-muted underline-offset-4 hover:underline sm:ml-auto"
          >
            {t.more}
          </Link>
        </div>
      </div>
    </div>
  );
}
