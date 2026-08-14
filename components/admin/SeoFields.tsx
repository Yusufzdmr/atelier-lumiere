"use client";

import { useState } from "react";

import { seoLimits } from "@/lib/marketing";
import type { Locale } from "@/lib/i18n";

const input =
  "w-full border-b border-sand-deep bg-transparent px-0 py-2.5 text-[0.92rem] text-ink outline-none focus:border-gold";
const label = "block text-[0.6rem] uppercase tracking-[0.18em] text-muted";

type Props = {
  /** Schlüssel der Seite (bestimmt die Feldnamen im Formular) */
  pageKey: string;
  /** Sprache der Eingabe */
  lang: Locale;
  langLabel: string;
  title: string;
  description: string;
  /** Sprache des Admin-Bereichs – nur für die Beschriftungen */
  ui: Locale;
  /** Vollständige URL für die Vorschau */
  url: string;
  /** Wenn das Feld leer bleibt: woher der Text dann kommt */
  auto?: string;
};

function counter(value: string, min: number, max: number, ui: Locale) {
  const n = value.length;
  const tone = n === 0 ? "text-muted" : n > max ? "text-red-700" : n < min ? "text-gold" : "text-muted";
  const note = n === 0 ? "" : n > max ? (ui === "de" ? " · zu lang" : " · uzun") : n < min ? (ui === "de" ? " · kurz" : " · kısa") : " ✓";
  return (
    <span className={`text-[0.68rem] ${tone}`}>
      {n}/{max}
      {note}
    </span>
  );
}

/**
 * Titel und Beschreibung einer Seite, mit Zeichenzähler und einer Vorschau,
 * wie der Eintrag in Google aussieht. Der Zähler ist kein Selbstzweck: was
 * länger ist, schneidet Google ab – und dann steht dort nicht mehr das,
 * was gemeint war.
 */
export default function SeoFields({ pageKey, lang, langLabel, title, description, ui, url, auto }: Props) {
  const [t, setT] = useState(title);
  const [d, setD] = useState(description);
  const de = ui === "de";

  const previewTitle = t.trim() || (de ? "(automatisch)" : "(otomatik)");
  const previewDesc = d.trim() || auto || (de ? "(automatisch aus dem Seiteninhalt)" : "(sayfa içeriğinden otomatik)");

  return (
    <div>
      <div className="flex items-baseline justify-between gap-3">
        <label className={label}>
          {de ? "Titel" : "Başlık"} ({langLabel})
        </label>
        {counter(t, seoLimits.title.min, seoLimits.title.max, ui)}
      </div>
      <input
        name={`seo_${pageKey}_title_${lang}`}
        value={t}
        onChange={(e) => setT(e.target.value)}
        className={input}
        placeholder={auto}
      />

      <div className="mt-5 flex items-baseline justify-between gap-3">
        <label className={label}>
          {de ? "Beschreibung" : "Açıklama"} ({langLabel})
        </label>
        {counter(d, seoLimits.description.min, seoLimits.description.max, ui)}
      </div>
      <textarea
        name={`seo_${pageKey}_desc_${lang}`}
        value={d}
        onChange={(e) => setD(e.target.value)}
        rows={3}
        className={`${input} resize-none`}
        placeholder={auto}
      />

      <div className="mt-5 border border-sand-deep bg-white/60 p-4">
        <div className="text-[0.58rem] uppercase tracking-[0.18em] text-muted">
          {de ? "So sieht es in Google aus" : "Google'da böyle görünür"}
        </div>
        <div className="mt-2 truncate text-[0.72rem] text-[#4d5156]">{url}</div>
        <div className="mt-0.5 truncate text-[1rem] leading-snug text-[#1a0dab]">{previewTitle}</div>
        <p className="mt-1 line-clamp-2 text-[0.8rem] leading-relaxed text-[#4d5156]">{previewDesc}</p>
      </div>
    </div>
  );
}
