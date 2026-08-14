import "server-only";

/**
 * PayPal-Anbindung (Orders v2).
 *
 * Die Integration ist vollständig vorbereitet – es fehlen nur die Zugangsdaten.
 * Sobald in Vercel diese drei Variablen gesetzt sind, läuft die Zahlung live:
 *
 *   PAYPAL_CLIENT_ID
 *   PAYPAL_CLIENT_SECRET
 *   PAYPAL_MODE=sandbox | live        (Standard: sandbox)
 *   NEXT_PUBLIC_PAYPAL_CLIENT_ID      (derselbe Wert, für das Frontend-SDK)
 *
 * Ohne Zugangsdaten meldet `isConfigured()` false; die Oberfläche zeigt dann
 * den Hinweis „Zahlung wird nach Freischaltung aktiviert" statt eines Fehlers.
 */

const API = () =>
  (process.env.PAYPAL_MODE ?? "sandbox") === "live"
    ? "https://api-m.paypal.com"
    : "https://api-m.sandbox.paypal.com";

export const isConfigured = () => Boolean(process.env.PAYPAL_CLIENT_ID && process.env.PAYPAL_CLIENT_SECRET);

export type PayPalOrder = { id: string; status: string; approveUrl?: string };

async function token(): Promise<string> {
  const auth = Buffer.from(`${process.env.PAYPAL_CLIENT_ID}:${process.env.PAYPAL_CLIENT_SECRET}`).toString("base64");
  const res = await fetch(`${API()}/v1/oauth2/token`, {
    method: "POST",
    headers: { Authorization: `Basic ${auth}`, "Content-Type": "application/x-www-form-urlencoded" },
    body: "grant_type=client_credentials",
    cache: "no-store",
  });
  if (!res.ok) throw new Error("paypal-auth-failed");
  const json = await res.json();
  return json.access_token as string;
}

/** Bestellung anlegen – Betrag kommt immer vom Server, nie aus dem Browser. */
export async function createOrder(opts: {
  amount: number;
  slug: string;
  description: string;
  returnUrl: string;
  cancelUrl: string;
}): Promise<PayPalOrder> {
  const access = await token();
  const res = await fetch(`${API()}/v2/checkout/orders`, {
    method: "POST",
    headers: { Authorization: `Bearer ${access}`, "Content-Type": "application/json" },
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
  const access = await token();
  const res = await fetch(`${API()}/v2/checkout/orders/${orderId}/capture`, {
    method: "POST",
    headers: { Authorization: `Bearer ${access}`, "Content-Type": "application/json" },
    cache: "no-store",
  });
  if (!res.ok) throw new Error("paypal-capture-failed");
  const json = await res.json();
  return { status: json.status, paid: json.status === "COMPLETED" };
}
