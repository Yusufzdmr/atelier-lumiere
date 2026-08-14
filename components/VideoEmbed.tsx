"use client";

import Image from "next/image";
import { useState, useSyncExternalStore } from "react";

import { parseVideo } from "@/lib/video";
import { getDict } from "@/lib/dict";
import type { Locale } from "@/lib/i18n";

/**
 * Zwei-Klick-Video (DSGVO).
 *
 * YouTube und Vimeo setzen Cookies und uebertragen die IP, sobald der Player
 * geladen wird. Ohne Einwilligung steht deshalb nur ein Standbild aus dem
 * eigenen Bildbestand da – kein Vorschaubild vom Anbieter, denn auch das waere
 * schon eine Uebertragung. Erst der Klick (oder eine erteilte
 * Marketing-Einwilligung) bindet den Player ein.
 */

const CONSENT_KEY = "al-consent-v1";

function hasVideoConsent(): boolean {
  try {
    const raw = localStorage.getItem(CONSENT_KEY);
    return raw ? JSON.parse(raw)?.marketing === true : false;
  } catch {
    return false;
  }
}

function subscribeConsent(onChange: () => void) {
  window.addEventListener("al:consent", onChange);
  window.addEventListener("storage", onChange);
  return () => {
    window.removeEventListener("al:consent", onChange);
    window.removeEventListener("storage", onChange);
  };
}

export default function VideoEmbed({
  url,
  locale,
  poster,
  title,
  ratio = "16/9",
}: {
  url: string;
  locale: Locale;
  /** Standbild aus dem eigenen Bestand – wird ohne Einwilligung gezeigt */
  poster?: string;
  title: string;
  ratio?: string;
}) {
  const t = getDict(locale).video;
  const consented = useSyncExternalStore(subscribeConsent, hasVideoConsent, () => false);
  const [clicked, setClicked] = useState(false);

  const video = parseVideo(url);
  if (!video) return null;

  const loaded = consented || clicked;
  const label = video.provider === "vimeo" ? "Vimeo" : video.provider === "youtube" ? "YouTube" : "Video";

  return (
    <figure className="relative overflow-hidden bg-ink" style={{ aspectRatio: ratio }}>
      {loaded ? (
        video.provider === "file" ? (
          <video src={video.embedUrl} controls playsInline className="h-full w-full object-cover" title={title} />
        ) : (
          <iframe
            src={video.embedUrl}
            title={title}
            allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture"
            referrerPolicy="strict-origin-when-cross-origin"
            allowFullScreen
            className="absolute inset-0 h-full w-full border-0"
          />
        )
      ) : (
        <button
          type="button"
          onClick={() => setClicked(true)}
          className="group absolute inset-0 h-full w-full"
          aria-label={`${t.load} – ${title}`}
        >
          {poster && (
            <Image
              src={poster}
              alt=""
              fill
              sizes="(max-width: 1024px) 100vw, 60vw"
              className="object-cover opacity-55 transition-opacity group-hover:opacity-45"
            />
          )}
          <span className="absolute inset-0 flex flex-col items-center justify-center gap-3 px-6 text-center">
            <span className="flex h-16 w-16 items-center justify-center rounded-full border border-cream/70 transition-colors group-hover:border-gold group-hover:bg-gold/20">
              <span className="ml-1 block h-0 w-0 border-y-[10px] border-l-[16px] border-y-transparent border-l-cream" />
            </span>
            <span className="text-[0.68rem] uppercase tracking-[0.22em] text-cream">
              {t.load} · {label}
            </span>
            <span className="max-w-sm text-[0.7rem] leading-relaxed text-cream/70">{t.note}</span>
          </span>
        </button>
      )}
    </figure>
  );
}
