"use client";

import { useCallback, useEffect, useState } from "react";
import VideoEmbed from "@/components/VideoEmbed";
import { getDict } from "@/lib/dict";
import type { Locale } from "@/lib/i18n";

export type GalleryPhoto = { thumb: string; full: string };

type Props = {
  locale: Locale;
  code: string;
  couple: string;
  venue: string;
  date: string;
  photos: GalleryPhoto[];
  /** Hochzeitsfilm bei YouTube/Vimeo – optional */
  videoUrl?: string;
};

export default function ClientGallery({ locale, code, couple, venue, date, photos, videoUrl }: Props) {
  const t = getDict(locale).gallery;
  const [picks, setPicks] = useState<number[]>([]);
  const [lightbox, setLightbox] = useState<number | null>(null);
  const [state, setState] = useState<"idle" | "sending" | "sent">("idle");

  const storeKey = `al-picks-${code}`;

  useEffect(() => {
    try {
      const raw = localStorage.getItem(storeKey);
      // eslint-disable-next-line react-hooks/set-state-in-effect -- bewusste Client-Initialisierung (kein SSR-Wert vorhanden)
      if (raw) setPicks(JSON.parse(raw));
    } catch {}
  }, [storeKey]);

  const toggle = useCallback(
    (i: number) => {
      setPicks((prev) => {
        const next = prev.includes(i) ? prev.filter((x) => x !== i) : [...prev, i];
        try {
          localStorage.setItem(storeKey, JSON.stringify(next));
        } catch {}
        return next;
      });
      setState("idle");
    },
    [storeKey]
  );

  const close = useCallback(() => setLightbox(null), []);
  const step = useCallback(
    (dir: 1 | -1) => setLightbox((i) => (i === null ? null : (i + dir + photos.length) % photos.length)),
    [photos.length]
  );

  useEffect(() => {
    if (lightbox === null) return;
    const onKey = (e: KeyboardEvent) => {
      if (e.key === "Escape") close();
      if (e.key === "ArrowRight") step(1);
      if (e.key === "ArrowLeft") step(-1);
      if (e.key === " ") {
        e.preventDefault();
        toggle(lightbox);
      }
    };
    window.addEventListener("keydown", onKey);
    document.body.style.overflow = "hidden";
    return () => {
      window.removeEventListener("keydown", onKey);
      document.body.style.overflow = "";
    };
  }, [lightbox, close, step, toggle]);

  async function send() {
    setState("sending");
    try {
      await fetch("/api/galerie/auswahl", {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({ code, couple, picks }),
      });
      setState("sent");
    } catch {
      setState("idle");
    }
  }

  return (
    <div className="pb-32">
      <div className="mx-auto max-w-7xl px-5 pt-32 sm:px-8 sm:pt-40">
        <div className="eyebrow">{t.protected}</div>
        <h1 className="headline mt-3 text-4xl sm:text-5xl">{couple}</h1>
        <p className="mt-3 text-sm text-muted">
          {venue} · {new Date(date).toLocaleDateString(locale === "de" ? "de-DE" : "tr-TR", { dateStyle: "long" })} ·{" "}
          {photos.length} {t.photos}
        </p>
        <p className="mt-6 max-w-xl border-l-2 border-gold pl-4 text-sm leading-relaxed text-muted">{t.selectHint}</p>

        {videoUrl && (
          <div className="mt-10 max-w-3xl">
            <VideoEmbed url={videoUrl} locale={locale} poster={photos[0]?.full} title={`${couple} – Film`} />
          </div>
        )}
      </div>

      <div className="mx-auto mt-12 max-w-7xl px-5 sm:px-8">
        <div className="columns-2 gap-3 sm:columns-3 sm:gap-4 lg:columns-4">
          {photos.map((photo, i) => {
            const active = picks.includes(i);
            return (
              <div key={i} className="group relative mb-3 break-inside-avoid sm:mb-4">
                <button
                  onClick={() => setLightbox(i)}
                  className="relative block w-full overflow-hidden bg-sand"
                  style={{ aspectRatio: i % 5 === 0 ? "3/4" : i % 3 === 0 ? "4/5" : "1/1" }}
                  aria-label={`${couple} ${i + 1}`}
                >
                  {/* eslint-disable-next-line @next/next/no-img-element -- gemischte Quellen (Upload/Platzhalter) */}
                  <img
                    src={photo.thumb}
                    alt={`${couple} – ${i + 1}`}
                    loading={i < 8 ? "eager" : "lazy"}
                    className="absolute inset-0 h-full w-full object-cover transition-transform duration-700 group-hover:scale-[1.03]"
                  />
                </button>
                <button
                  onClick={() => toggle(i)}
                  aria-pressed={active}
                  aria-label="Favorit"
                  className={`absolute right-2 top-2 flex h-9 w-9 items-center justify-center rounded-full backdrop-blur-sm transition-all duration-300 ${
                    active ? "bg-gold text-white" : "bg-black/25 text-white/85 hover:bg-black/45"
                  }`}
                >
                  <Heart filled={active} />
                </button>
              </div>
            );
          })}
        </div>
      </div>

      {/* Auswahlleiste */}
      <div className="fixed inset-x-0 bottom-0 z-40 border-t border-sand-deep bg-cream/95 backdrop-blur-md">
        <div className="mx-auto flex max-w-7xl flex-wrap items-center justify-between gap-3 px-5 py-4 sm:px-8">
          <div className="text-sm text-ink">
            <span className="font-display text-2xl text-gold">{picks.length}</span>{" "}
            <span className="text-muted">{t.selected}</span>
            {state === "sent" && <span className="ml-4 text-gold">{t.sent}</span>}
          </div>
          <button
            onClick={send}
            disabled={picks.length === 0 || state !== "idle"}
            className="bg-ink px-7 py-3 text-[0.72rem] uppercase tracking-[0.2em] text-cream transition-colors hover:bg-gold disabled:cursor-not-allowed disabled:opacity-40"
          >
            {state === "sending" ? t.sending : t.send}
          </button>
        </div>
      </div>

      {/* Lightbox */}
      {lightbox !== null && (
        <div className="fixed inset-0 z-50 flex flex-col bg-ink/97" role="dialog" aria-modal="true">
          <div className="flex items-center justify-between px-5 py-4 text-cream/70 sm:px-8">
            <span className="text-xs uppercase tracking-[0.2em]">
              {lightbox + 1} {t.of} {photos.length}
            </span>
            <button onClick={close} className="text-xs uppercase tracking-[0.2em] hover:text-gold">
              {t.close} ✕
            </button>
          </div>

          <div className="relative flex-1">
            {/* eslint-disable-next-line @next/next/no-img-element -- gemischte Quellen (Upload/Platzhalter) */}
            <img
              src={photos[lightbox].full}
              alt={`${couple} – ${lightbox + 1}`}
              className="absolute inset-0 h-full w-full object-contain"
            />
            <button
              onClick={() => step(-1)}
              aria-label={t.prev}
              className="absolute inset-y-0 left-0 w-1/4 cursor-w-resize"
            />
            <button
              onClick={() => step(1)}
              aria-label={t.next}
              className="absolute inset-y-0 right-0 w-1/4 cursor-e-resize"
            />
          </div>

          <div className="flex items-center justify-center gap-4 px-5 py-6">
            <button
              onClick={() => toggle(lightbox)}
              className={`flex items-center gap-3 border px-6 py-3 text-[0.72rem] uppercase tracking-[0.2em] transition-colors ${
                picks.includes(lightbox)
                  ? "border-gold bg-gold text-white"
                  : "border-cream/40 text-cream hover:bg-cream hover:text-ink"
              }`}
            >
              <Heart filled={picks.includes(lightbox)} />
              {picks.includes(lightbox) ? t.selected : "Favorit"}
            </button>
          </div>
        </div>
      )}
    </div>
  );
}

function Heart({ filled }: { filled: boolean }) {
  return (
    <svg width="17" height="17" viewBox="0 0 24 24" fill={filled ? "currentColor" : "none"} stroke="currentColor" strokeWidth="1.6">
      <path d="M20.8 4.6a5.5 5.5 0 0 0-7.8 0L12 5.7l-1-1.1a5.5 5.5 0 0 0-7.8 7.8l1.1 1L12 21.2l7.7-7.8 1.1-1a5.5 5.5 0 0 0 0-7.8z" />
    </svg>
  );
}
