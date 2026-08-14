"use client";

import { useActionState } from "react";

import { checkPaypal, type PaypalTestState } from "@/lib/actions";
import type { Locale } from "@/lib/i18n";

/**
 * Prüft die hinterlegten PayPal-Zugangsdaten, ohne eine Zahlung auszulösen –
 * damit man nicht erst beim ersten echten Kunden merkt, dass ein Schlüssel
 * aus der Sandbox stammt.
 */
export default function PaypalTest({ locale }: { locale: Locale }) {
  const de = locale === "de";
  const [state, run, pending] = useActionState<PaypalTestState>(checkPaypal, { state: "idle" });

  const message: Record<PaypalTestState["state"], string> = {
    idle: "",
    ok: de
      ? `Verbindung steht (${state.mode === "live" ? "Live" : "Sandbox"}).`
      : `Bağlantı kuruldu (${state.mode === "live" ? "Live" : "Sandbox"}).`,
    missing: de ? "Es fehlen Client-ID oder Secret." : "Client ID veya Secret eksik.",
    rejected: de
      ? "PayPal hat die Zugangsdaten abgelehnt. Passen Client-ID, Secret und Modus zusammen?"
      : "PayPal bilgileri reddetti. Client ID, Secret ve mod birbiriyle uyumlu mu?",
    failed: de ? "PayPal war nicht erreichbar." : "PayPal'a ulaşılamadı.",
  };

  return (
    <form action={run} className="mt-6">
      <button
        disabled={pending}
        className="border border-ink px-6 py-3 text-[0.66rem] uppercase tracking-[0.2em] text-ink transition-colors hover:bg-ink hover:text-cream disabled:opacity-50"
      >
        {pending ? (de ? "Prüfe …" : "Kontrol ediliyor …") : de ? "Verbindung testen" : "Bağlantıyı test et"}
      </button>
      {state.state !== "idle" && !pending && (
        <p className={`mt-3 text-[0.78rem] ${state.state === "ok" ? "text-gold" : "text-red-700"}`}>
          {message[state.state]}
        </p>
      )}
    </form>
  );
}
