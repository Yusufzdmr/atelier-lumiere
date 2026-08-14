"use client";

import { useState } from "react";
import { usePathname, useRouter } from "next/navigation";
import { getDict } from "@/lib/dict";
import type { Locale } from "@/lib/i18n";

export default function GalleryLogin({ locale, presetCode = "" }: { locale: Locale; presetCode?: string }) {
  const t = getDict(locale).gallery;
  const router = useRouter();
  const pathname = usePathname();
  const [code, setCode] = useState(presetCode);
  const [password, setPassword] = useState("");
  const [error, setError] = useState(false);
  const [busy, setBusy] = useState(false);

  async function submit(e: React.FormEvent) {
    e.preventDefault();
    setBusy(true);
    setError(false);
    try {
      const res = await fetch("/api/galerie/auth", {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({ code, password }),
      });
      if (!res.ok) throw new Error();
      const target = `/${locale}/galerie/${code.trim().toLowerCase()}`;
      // Steht das Formular bereits auf der Galerie-URL, würde push() nichts tun –
      // dann genügt refresh(), damit der Server das neue Cookie auswertet.
      if (pathname === target) router.refresh();
      else router.push(target);
    } catch {
      setError(true);
      setBusy(false);
    }
  }

  const field =
    "w-full border-b border-sand-deep bg-transparent px-0 py-3 text-[0.95rem] text-ink outline-none transition-colors placeholder:text-muted/50 focus:border-gold";

  return (
    <form onSubmit={submit} className="max-w-sm space-y-7">
      <div>
        <label className="block text-[0.66rem] uppercase tracking-[0.2em] text-muted" htmlFor="code">
          {t.code}
        </label>
        <input id="code" className={field} value={code} onChange={(e) => setCode(e.target.value)} placeholder="elif-marco" required />
      </div>
      <div>
        <label className="block text-[0.66rem] uppercase tracking-[0.2em] text-muted" htmlFor="pw">
          {t.password}
        </label>
        <input
          id="pw"
          type="password"
          className={field}
          value={password}
          onChange={(e) => setPassword(e.target.value)}
          required
        />
      </div>

      {error && <p className="text-sm text-red-700">{t.wrong}</p>}

      <button
        type="submit"
        disabled={busy}
        className="w-full bg-ink px-8 py-4 text-[0.7rem] uppercase tracking-[0.2em] text-cream transition-colors hover:bg-gold disabled:opacity-50"
      >
        {t.open}
      </button>

      <p className="border-l-2 border-gold/50 pl-4 text-[0.78rem] leading-relaxed text-muted">
        {t.demoHint} <code className="text-ink">elif-marco</code> · {t.demoAnd} <code className="text-ink">solitude24</code>
        <br />
        {t.demoHint} <code className="text-ink">sarah-daniel</code> · {t.demoAnd} <code className="text-ink">kelter25</code>
      </p>
    </form>
  );
}
