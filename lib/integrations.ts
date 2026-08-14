import "server-only";

import { sql, ensureSchema } from "./db";

/**
 * Zugangsdaten fremder Dienste – im Admin pflegbar, ohne Deploy.
 *
 * Eigene Tabelle, nicht in site_content: die Inhalte werden von oeffentlichen
 * Seiten gelesen, Geheimnisse gehoeren dort nicht hinein. Aus derselben
 * Ueberlegung verlaesst `clientSecret` den Server nie – ins Browser-Bundle
 * geht ausschliesslich das, was `publicTracking()` zurueckgibt.
 *
 * Rangfolge: Was im Admin steht, gewinnt. Ist das Feld leer, greift die
 * Umgebungsvariable (so bleiben bestehende Vercel-Projekte unveraendert).
 */

export type PaypalSettings = {
  clientId: string;
  clientSecret: string;
  mode: "sandbox" | "live";
};

export type GoogleSettings = {
  /** Analytics 4 – G-XXXXXXXXXX */
  gaId: string;
  /** Tag Manager – GTM-XXXXXXX */
  gtmId: string;
  /** Ads Conversion-ID – AW-123456789 */
  adsId: string;
  /** Conversion-Label je Ereignis (nur der Teil nach dem Schraegstrich) */
  adsLabels: { contact: string; invite: string; phone: string };
  /** Rechenwert einer Anfrage; leer = ohne Wert senden */
  leadValue: string;
  currency: string;
  /** Search Console: nur der content-Wert des Metatags */
  searchConsole: string;
  /** Bing Webmaster Tools (msvalidate.01) */
  bing: string;
  /** Consent Mode v2: vor der Einwilligung alles auf "denied" */
  consentMode: boolean;
};

export type MetaSettings = {
  /** Facebook/Instagram Pixel – nur Ziffern */
  pixelId: string;
};

/**
 * Platz fuer alles, was spaeter dazukommt: ein Name, ein Wert, eine Notiz.
 * `secret: true` bedeutet, dass der Wert im Formular nur maskiert erscheint.
 */
export type ExtraKey = {
  id: string;
  /** Anzeigename, z. B. "Brevo API-Key" */
  label: string;
  /** Technischer Name, unter dem der Code den Wert liest */
  name: string;
  value: string;
  secret: boolean;
  note: string;
};

export type IntegrationSettings = {
  paypal: PaypalSettings;
  google: GoogleSettings;
  meta: MetaSettings;
  extras: ExtraKey[];
  updatedAt: string;
};

export const defaultIntegrations = (): IntegrationSettings => ({
  paypal: { clientId: "", clientSecret: "", mode: "sandbox" },
  google: {
    gaId: "",
    gtmId: "",
    adsId: "",
    adsLabels: { contact: "", invite: "", phone: "" },
    leadValue: "",
    currency: "EUR",
    searchConsole: "",
    bing: "",
    consentMode: true,
  },
  meta: { pixelId: "" },
  extras: [],
  updatedAt: "",
});

/** Fehlende Felder aelterer Staende nachruesten, damit ein Deploy nicht bricht. */
function complete(stored: Partial<IntegrationSettings> | null): IntegrationSettings {
  const d = defaultIntegrations();
  if (!stored) return d;
  return {
    paypal: { ...d.paypal, ...stored.paypal },
    google: {
      ...d.google,
      ...stored.google,
      adsLabels: { ...d.google.adsLabels, ...stored.google?.adsLabels },
    },
    meta: { ...d.meta, ...stored.meta },
    extras: Array.isArray(stored.extras) ? stored.extras : [],
    updatedAt: stored.updatedAt ?? "",
  };
}

let cache: { at: number; value: IntegrationSettings } | null = null;
const TTL = 30_000;

/**
 * Gelesen wird oft (jede Zahlung, jeder Seitenaufbau), geschrieben selten.
 * Ein kurzer Prozess-Cache spart den Roundtrip, ohne dass eine Aenderung im
 * Admin lange braucht – `saveIntegrations()` leert ihn ohnehin sofort.
 */
export async function getIntegrations(): Promise<IntegrationSettings> {
  if (cache && Date.now() - cache.at < TTL) return cache.value;

  try {
    await ensureSchema();
    const rows = (await sql()`SELECT data FROM integrations WHERE id = 1`) as {
      data: Partial<IntegrationSettings>;
    }[];
    const value = complete(rows[0]?.data ?? null);
    cache = { at: Date.now(), value };
    return value;
  } catch (err) {
    // Ohne Datenbank (lokal, oder Neon schlaeft) bleiben die Umgebungsvariablen.
    console.error("[integrations] nicht lesbar, nutze Umgebungsvariablen:", err);
    return defaultIntegrations();
  }
}

export async function saveIntegrations(next: IntegrationSettings) {
  await ensureSchema();
  const value = { ...next, updatedAt: new Date().toISOString() };
  await sql()`
    INSERT INTO integrations (id, data, updated_at) VALUES (1, ${JSON.stringify(value)}, now())
    ON CONFLICT (id) DO UPDATE SET data = EXCLUDED.data, updated_at = now()`;
  cache = { at: Date.now(), value };
}

/* --------------------------- Aufloesung je Dienst --------------------------- */

/** Admin-Wert, sonst Umgebungsvariable, sonst leer. */
const pick = (value: string | undefined, env: string | undefined) => (value || env || "").trim();

export async function paypalConfig() {
  const { paypal } = await getIntegrations();
  const clientId = pick(paypal.clientId, process.env.PAYPAL_CLIENT_ID);
  const clientSecret = pick(paypal.clientSecret, process.env.PAYPAL_CLIENT_SECRET);
  const mode: "sandbox" | "live" =
    paypal.clientId || paypal.clientSecret
      ? paypal.mode
      : process.env.PAYPAL_MODE === "live"
        ? "live"
        : "sandbox";

  return { clientId, clientSecret, mode, configured: Boolean(clientId && clientSecret) };
}

/** Nur das, was der Browser braucht – niemals ein Secret. */
export async function publicTracking() {
  const { google, meta } = await getIntegrations();
  return {
    gaId: pick(google.gaId, process.env.NEXT_PUBLIC_GA_ID),
    gtmId: google.gtmId.trim(),
    adsId: google.adsId.trim(),
    adsLabels: {
      contact: google.adsLabels.contact.trim(),
      invite: google.adsLabels.invite.trim(),
      phone: google.adsLabels.phone.trim(),
    },
    leadValue: google.leadValue.trim(),
    currency: google.currency.trim() || "EUR",
    metaPixelId: meta.pixelId.trim(),
    consentMode: google.consentMode !== false,
  };
}

export type PublicTracking = Awaited<ReturnType<typeof publicTracking>>;

/** Bestaetigungs-Metatags fuer <head>. */
export async function verificationTags() {
  const { google } = await getIntegrations();
  return { google: google.searchConsole.trim(), bing: google.bing.trim() };
}

/**
 * Zusatzschluessel im Code lesen: `await integrationValue("BREVO_API_KEY")`.
 * Faellt auf die gleichnamige Umgebungsvariable zurueck.
 */
export async function integrationValue(name: string): Promise<string> {
  const { extras } = await getIntegrations();
  const hit = extras.find((e) => e.name.trim().toUpperCase() === name.trim().toUpperCase());
  return pick(hit?.value, process.env[name]);
}

/* ------------------------------- Darstellung ------------------------------- */

/** Geheimnisse im Formular nur andeuten: die letzten vier Zeichen genuegen. */
export function mask(value: string) {
  const v = value.trim();
  if (!v) return "";
  if (v.length <= 4) return "••••";
  return `${"•".repeat(Math.min(16, v.length - 4))}${v.slice(-4)}`;
}
