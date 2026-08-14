"use client";

import { useState } from "react";
import { getDict } from "@/lib/dict";
import type { Locale } from "@/lib/i18n";

type Theme = { fg: string; accent: string; soft: string; frame: string };

export default function RsvpForm({
  slug,
  locale,
  theme,
  disabled = false,
}: {
  slug: string;
  locale: Locale;
  theme: Theme;
  disabled?: boolean;
}) {
  const t = getDict(locale).invite;
  const [coming, setComing] = useState<boolean | null>(null);
  const [state, setState] = useState<"idle" | "sending" | "ok">("idle");

  async function submit(e: React.FormEvent<HTMLFormElement>) {
    e.preventDefault();
    if (disabled) return;
    const fd = new FormData(e.currentTarget);
    setState("sending");
    try {
      await fetch("/api/einladung/rsvp", {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({
          slug,
          name: fd.get("name"),
          coming,
          count: Number(fd.get("count") || 1),
          note: fd.get("note"),
        }),
      });
      setState("ok");
    } catch {
      setState("idle");
    }
  }

  const input =
    "w-full border-b bg-transparent px-0 py-3 text-[0.95rem] outline-none transition-colors placeholder:opacity-50";

  if (state === "ok") {
    return (
      <p className="mt-10 border p-8 text-center text-sm" style={{ borderColor: theme.frame, color: theme.accent }}>
        {t.rsvpThanks}
      </p>
    );
  }

  return (
    <form onSubmit={submit} className="mt-10 space-y-7">
      <div className="grid grid-cols-2 gap-3">
        <button
          type="button"
          onClick={() => setComing(true)}
          className="border px-4 py-4 text-[0.66rem] uppercase tracking-[0.18em] transition-all"
          style={{
            borderColor: coming === true ? theme.accent : theme.frame,
            background: coming === true ? theme.accent : "transparent",
            color: coming === true ? "#fff" : theme.fg,
          }}
        >
          {t.rsvpComing}
        </button>
        <button
          type="button"
          onClick={() => setComing(false)}
          className="border px-4 py-4 text-[0.66rem] uppercase tracking-[0.18em] transition-all"
          style={{
            borderColor: coming === false ? theme.accent : theme.frame,
            background: coming === false ? theme.accent : "transparent",
            color: coming === false ? "#fff" : theme.fg,
          }}
        >
          {t.rsvpNotComing}
        </button>
      </div>

      {coming !== null && (
        <div className="anim-up space-y-6">
          <input name="name" required placeholder={t.rsvpName} className={input} style={{ borderColor: theme.frame, color: theme.fg }} />
          {coming && (
            <input
              name="count"
              type="number"
              min={1}
              max={20}
              defaultValue={2}
              placeholder={t.rsvpCount}
              className={input}
              style={{ borderColor: theme.frame, color: theme.fg }}
            />
          )}
          <textarea
            name="note"
            rows={2}
            placeholder={t.rsvpNote}
            className={`${input} resize-none`}
            style={{ borderColor: theme.frame, color: theme.fg }}
          />
          <button
            type="submit"
            disabled={state === "sending" || disabled}
            className="w-full py-4 text-[0.66rem] uppercase tracking-[0.24em] text-white transition-opacity hover:opacity-85 disabled:opacity-50"
            style={{ background: theme.accent }}
          >
            {state === "sending" ? "…" : t.rsvpSend}
          </button>
        </div>
      )}
    </form>
  );
}
