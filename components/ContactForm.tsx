"use client";

import { useState } from "react";
import Link from "next/link";
import { getDict } from "@/lib/dict";
import { track } from "@/lib/track";
import type { Locale } from "@/lib/i18n";

const field =
  "w-full border-b border-sand-deep bg-transparent px-0 py-3 text-[0.95rem] text-ink outline-none transition-colors placeholder:text-muted/60 focus:border-gold";
const label = "block text-[0.68rem] uppercase tracking-[0.2em] text-muted";

export default function ContactForm({ locale, preset }: { locale: Locale; preset?: string }) {
  const t = getDict(locale).contact;
  const [state, setState] = useState<"idle" | "sending" | "ok" | "error">("idle");

  async function onSubmit(e: React.FormEvent<HTMLFormElement>) {
    e.preventDefault();
    const form = e.currentTarget;
    const data = Object.fromEntries(new FormData(form).entries());
    setState("sending");
    try {
      const res = await fetch("/api/kontakt", {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({ ...data, locale }),
      });
      if (!res.ok) throw new Error("failed");
      setState("ok");
      form.reset();
      // Anfrage gilt als Conversion – meldet nur, wenn eingewilligt wurde.
      track("contact");
    } catch {
      setState("error");
    }
  }

  if (state === "ok") {
    return (
      <div className="border border-gold/40 bg-sand/40 p-8 text-center">
        <div className="font-display text-2xl font-light text-ink">✓</div>
        <p className="mt-3 text-[0.95rem] leading-relaxed text-ink">{t.success}</p>
      </div>
    );
  }

  return (
    <form onSubmit={onSubmit} className="space-y-7">
      {/* Honeypot gegen Spam-Bots – für Menschen unsichtbar */}
      <input type="text" name="website" tabIndex={-1} autoComplete="off" className="hidden" aria-hidden />

      <div className="grid gap-7 sm:grid-cols-2">
        <div>
          <label className={label} htmlFor="name">
            {t.name} *
          </label>
          <input id="name" name="name" required className={field} />
        </div>
        <div>
          <label className={label} htmlFor="email">
            {t.email} *
          </label>
          <input id="email" name="email" type="email" required className={field} />
        </div>
        <div>
          <label className={label} htmlFor="phone">
            {t.phone}
          </label>
          <input id="phone" name="phone" className={field} />
        </div>
        <div>
          <label className={label} htmlFor="date">
            {t.date}
          </label>
          <input id="date" name="date" type="date" className={field} />
        </div>
        <div>
          <label className={label} htmlFor="location">
            {t.location}
          </label>
          <input id="location" name="location" defaultValue={preset} className={field} />
        </div>
        <div>
          <label className={label} htmlFor="guests">
            {t.guests}
          </label>
          <input id="guests" name="guests" inputMode="numeric" className={field} />
        </div>
      </div>

      <div>
        <label className={label} htmlFor="service">
          {t.service}
        </label>
        <select id="service" name="service" className={field} defaultValue="foto">
          <option value="foto">{locale === "de" ? "Nur Fotografie" : "Sadece fotoğraf"}</option>
          <option value="video">{locale === "de" ? "Nur Film" : "Sadece video"}</option>
          <option value="beides">{locale === "de" ? "Foto & Film" : "Foto & video"}</option>
          <option value="standesamt">{locale === "de" ? "Standesamt / Verlobung" : "Nikah / nişan"}</option>
        </select>
      </div>

      <div>
        <label className={label} htmlFor="message">
          {t.message} *
        </label>
        <textarea id="message" name="message" required rows={5} className={`${field} resize-none`} />
      </div>

      <label className="flex cursor-pointer items-start gap-3 text-[0.82rem] leading-relaxed text-muted">
        <input type="checkbox" name="consent" required className="mt-1 h-4 w-4 shrink-0 accent-[#B08D57]" />
        <span>
          {t.consent}{" "}
          <Link href={`/${locale}/datenschutz`} className="text-gold underline-offset-4 hover:underline">
            *
          </Link>
        </span>
      </label>

      {state === "error" && <p className="text-sm text-red-700">{t.error}</p>}

      <button
        type="submit"
        disabled={state === "sending"}
        className="w-full bg-ink px-8 py-4 text-[0.72rem] uppercase tracking-[0.2em] text-cream transition-colors hover:bg-gold disabled:opacity-50 sm:w-auto"
      >
        {state === "sending" ? t.sending : t.submit}
      </button>
    </form>
  );
}
