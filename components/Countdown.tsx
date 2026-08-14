"use client";

import { useEffect, useState } from "react";
import { getDict } from "@/lib/dict";
import type { Locale } from "@/lib/i18n";

function diff(target: number) {
  const ms = Math.max(0, target - Date.now());
  return {
    d: Math.floor(ms / 86400000),
    h: Math.floor((ms / 3600000) % 24),
    m: Math.floor((ms / 60000) % 60),
    s: Math.floor((ms / 1000) % 60),
    done: ms === 0,
  };
}

export default function Countdown({
  date,
  time,
  locale,
  tone = "light",
}: {
  date: string;
  time: string;
  locale: Locale;
  tone?: "light" | "dark";
}) {
  const t = getDict(locale).invite;
  const target = new Date(`${date}T${time || "12:00"}:00`).getTime();
  const [v, setV] = useState<ReturnType<typeof diff> | null>(null);

  useEffect(() => {
    if (Number.isNaN(target)) return;
    // eslint-disable-next-line react-hooks/set-state-in-effect -- bewusste Client-Initialisierung (kein SSR-Wert vorhanden)
    setV(diff(target));
    const id = setInterval(() => setV(diff(target)), 1000);
    return () => clearInterval(id);
  }, [target]);

  if (!v) return <div className="h-[86px]" aria-hidden />;

  if (v.done) {
    return <p className="font-display text-2xl font-light tracking-wide">{t.theDay}</p>;
  }

  const items = [
    { n: v.d, l: t.countdownDays },
    { n: v.h, l: t.countdownHours },
    { n: v.m, l: t.countdownMin },
    { n: v.s, l: t.countdownSec },
  ];

  return (
    <div className="flex items-start justify-center gap-5 sm:gap-9" aria-live="off">
      {items.map((it, i) => (
        <div key={i} className="text-center">
          <div
            className={`font-display text-3xl font-light tabular-nums sm:text-5xl ${
              tone === "light" ? "text-current" : "text-ink"
            }`}
          >
            {String(it.n).padStart(2, "0")}
          </div>
          <div className="mt-1.5 text-[0.58rem] uppercase tracking-[0.24em] opacity-60 sm:text-[0.65rem]">{it.l}</div>
        </div>
      ))}
    </div>
  );
}
