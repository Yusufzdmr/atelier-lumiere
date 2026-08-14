"use client";

import { useActionState } from "react";
import { login, type LoginState } from "@/lib/actions";
import type { Locale } from "@/lib/i18n";

export default function AdminLogin({ locale }: { locale: Locale }) {
  const [state, formAction, pending] = useActionState<LoginState, FormData>(login, {});

  return (
    <div className="mx-auto flex min-h-[70vh] max-w-sm flex-col justify-center px-5">
      <div className="eyebrow">Atelier Lumière</div>
      <h1 className="headline mt-3 text-3xl">{locale === "de" ? "Admin-Bereich" : "Yönetim paneli"}</h1>
      <p className="mt-3 text-sm text-muted">
        {locale === "de"
          ? "Bitte mit dem Admin-Passwort anmelden."
          : "Lütfen yönetici parolasıyla giriş yapın."}
      </p>

      <form action={formAction} className="mt-8 space-y-6">
        <input type="hidden" name="locale" value={locale} />
        <div>
          <label className="block text-[0.64rem] uppercase tracking-[0.2em] text-muted" htmlFor="pw">
            {locale === "de" ? "Passwort" : "Parola"}
          </label>
          <input
            id="pw"
            name="password"
            type="password"
            required
            autoFocus
            className="w-full border-b border-sand-deep bg-transparent px-0 py-3 text-[0.95rem] text-ink outline-none focus:border-gold"
          />
        </div>

        {state?.error && (
          <p className="text-sm text-red-700">
            {locale === "de" ? "Passwort stimmt nicht." : "Parola hatalı."}
          </p>
        )}

        <button
          type="submit"
          disabled={pending}
          className="w-full bg-ink px-8 py-4 text-[0.7rem] uppercase tracking-[0.2em] text-cream transition-colors hover:bg-gold disabled:opacity-50"
        >
          {locale === "de" ? "Anmelden" : "Giriş yap"}
        </button>

        <p className="border-l-2 border-gold/50 pl-4 text-[0.76rem] leading-relaxed text-muted">
          {locale === "de" ? "Demo-Passwort: " : "Demo parolası: "}
          <code className="text-ink">demo</code>
        </p>
      </form>
    </div>
  );
}
