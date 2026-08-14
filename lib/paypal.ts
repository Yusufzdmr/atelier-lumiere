import "server-only";

import { paypalConfig } from "./integrations";

/**
 * PayPal-Anbindung (Orders v2).
 *
 * Die Zugangsdaten kommen aus dem Admin-Bereich („Integrationen"). Ist dort
 * nichts eingetragen, greifen die Umgebungsvariablen:
 *
 *   PAYPAL_CLIENT_ID
 *   PAYPAL_CLIENT_SECRET
 *   PAYPAL_MODE=sandbox | live        (Standard: sandbox)
 *
 * Ohne Zugangsdaten meldet `isConfigured()` false; die Oberfläche zeigt dann
 * den Hinweis „Zahlung wird nach Freischaltung aktiviert" statt eines Fehlers.
 * Ein Frontend-SDK wird nicht geladen – bezahlt wird per Weiterleitung, damit
 * ohne Einwilligung kein PayPal-Skript in die Seite kommt.
 */

const HOST = (mode: string) =>
  mode === "live" ? "https://api-m.paypal.com" : "https://api-m.sandbox.paypal.com";

export const isConfigured = async () => (await paypalConfig()).configured;

export type PayPalOrder = { id: string; status: string; approveUrl?: string };

async function auth() {
  const cfg = await paypalConfig();
  if (!cfg.configured) throw new Error("paypal-not-configured");

  const basic = Buffer.from(`${cfg.clientId}:${cfg.clientSecret}`).toString("base64");
  const res = await fetch(`${HOST(cfg.mode)}/v1/oauth2/token`, {
    method: "POST",
    headers: { Authorization: `Basic ${basic}`, "Content-Type": "application/x-www-form-urlencoded" },
    body: "grant_type=client_credentials",
    cache: "no-store",
  });
  if (!res.ok) throw new Error("paypal-auth-failed");
  const json = await res.json();
  return { token: json.access_token as string, host: HOST(cfg.mode), mode: cfg.mode };
}

/**
 * Zugangsdaten pruefen, ohne eine Bestellung anzulegen – fuer den Testknopf
 * im Admin. Gibt eine sprechende Meldung zurueck, keinen Stacktrace.
 */
export async function testConnection(): Promise<{ ok: boolean; mode: string; message: string }> {
  const cfg = await paypalConfig();
  if (!cfg.configured) {
    return { ok: false, mode: cfg.mode, message: "missing" };
  }
  try {
    await auth();
    return { ok: true, mode: cfg.mode, message: "ok" };
  } catch (err) {
    return {
      ok: false,
      mode: cfg.mode,
      message: err instanceof Error && err.message === "paypal-auth-failed" ? "rejected" : "failed",
    };
  }
}

/** Bestellung anlegen – Betrag kommt immer vom Server, nie aus dem Browser. */
export async function createOrder(opts: {
  amount: number;
  slug: string;
  description: string;
  returnUrl: string;
  cancelUrl: string;
}): Promise<PayPalOrder> {
  const { token, host } = await auth();
  const res = await fetch(`${host}/v2/checkout/orders`, {
    method: "POST",
    headers: { Authorization: `Bearer ${token}`, "Content-Type": "application/json" },
    cache: "no-store",
    body: JSON.stringify({
      intent: "CAPTURE",
      purchase_units: [
        {
          reference_id: opts.slug,
          description: opts.description.slice(0, 127),
          amount: { currency_code: "EUR", value: opts.amount.toFixed(2) },
        },
      ],
      payment_source: {
        paypal: {
          experience_context: {
            brand_name: "Atelier Lumière",
            locale: "de-DE",
            user_action: "PAY_NOW",
            return_url: opts.returnUrl,
            cancel_url: opts.cancelUrl,
          },
        },
      },
    }),
  });

  if (!res.ok) throw new Error("paypal-order-failed");
  const json = await res.json();
  const approve = (json.links ?? []).find((l: { rel: string; href: string }) => l.rel === "payer-action" || l.rel === "approve");
  return { id: json.id, status: json.status, approveUrl: approve?.href };
}

/** Zahlung einziehen, nachdem der Gast bei PayPal bestätigt hat. */
export async function captureOrder(orderId: string): Promise<{ status: string; paid: boolean }> {
  const { token, host } = await auth();
  const res = await fetch(`${host}/v2/checkout/orders/${orderId}/capture`, {
    method: "POST",
    headers: { Authorization: `Bearer ${token}`, "Content-Type": "application/json" },
    cache: "no-store",
  });
  if (!res.ok) throw new Error("paypal-capture-failed");
  const json = await res.json();
  return { status: json.status, paid: json.status === "COMPLETED" };
}
