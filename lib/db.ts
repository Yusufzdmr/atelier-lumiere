import "server-only";
import { neon } from "@neondatabase/serverless";

/**
 * Neon Postgres (Vercel Marketplace, Region fra1).
 *
 * Bewusst schlank: Der Inhalt der Website ist ein verschachteltes Objekt und
 * wird als JSONB abgelegt. Es gibt keine komplexen Abfragen – gelesen wird
 * über den Primärschlüssel oder als vollständige Liste. Damit bleibt die
 * Struktur aus `lib/store.ts` und `lib/cms.ts` unverändert; nur der Weg zur
 * Persistenz ist neu.
 */

/** Lazy, damit `next build` ohne gesetzte DATABASE_URL nicht abbricht. */
let _sql: ReturnType<typeof neon> | null = null;

export function sql() {
  if (!_sql) {
    const url = process.env.DATABASE_URL;
    if (!url) throw new Error("DATABASE_URL fehlt – Neon-Integration nicht verbunden?");
    _sql = neon(url);
  }
  return _sql;
}

/** true, wenn eine Datenbank konfiguriert ist (lokal ohne .env.local: false). */
export const hasDb = () => Boolean(process.env.DATABASE_URL);

/* --------------------------------------------------------------- */
/*  Schema                                                          */
/* --------------------------------------------------------------- */

let ready: Promise<void> | null = null;

/**
 * Neon faehrt im kostenlosen Tarif nach Leerlauf herunter. Die erste Abfrage
 * weckt die Instanz und laeuft dabei gelegentlich in den Verbindungs-Timeout.
 * Deshalb wird der erste Zugriff mehrfach versucht, statt den Build oder eine
 * Seite daran scheitern zu lassen.
 */
async function withRetry<T>(fn: () => Promise<T>, attempts = 4): Promise<T> {
  let last: unknown;
  for (let i = 0; i < attempts; i++) {
    try {
      return await fn();
    } catch (err) {
      last = err;
      if (i < attempts - 1) await new Promise((r) => setTimeout(r, 1500 * (i + 1)));
    }
  }
  throw last;
}

/**
 * Legt die Tabellen an – einmal pro Server-Instanz, danach gecacht.
 * Jede Datenfunktion ruft das vorab auf, damit ein frischer Deploy ohne
 * separaten Migrationsschritt funktioniert.
 */
export function ensureSchema(): Promise<void> {
  if (!ready) {
    ready = withRetry(createTables).catch((err) => {
      // Nicht dauerhaft merken: der naechste Aufruf darf es erneut versuchen.
      ready = null;
      throw err;
    });
  }
  return ready;
}

async function createTables() {
  const q = sql();

  // Markierung fuer den einmaligen Erststart. Beim Build laufen mehrere
  // Worker parallel – ohne diesen Riegel wuerde jeder von ihnen die
  // Demo-Daten einspielen.
  await q`
    CREATE TABLE IF NOT EXISTS seed_marker (
      id int PRIMARY KEY,
      at timestamptz NOT NULL DEFAULT now()
    )`;

  await q`
    CREATE TABLE IF NOT EXISTS site_content (
      id         int PRIMARY KEY DEFAULT 1,
      data       jsonb NOT NULL,
      updated_at timestamptz NOT NULL DEFAULT now(),
      CONSTRAINT site_content_single_row CHECK (id = 1)
    )`;

  await q`
    CREATE TABLE IF NOT EXISTS galleries (
      code       text PRIMARY KEY,
      data       jsonb NOT NULL,
      created_at timestamptz NOT NULL DEFAULT now()
    )`;

  // Eine Auswahl pro Galerie – eine neue Einsendung ersetzt die alte.
  await q`
    CREATE TABLE IF NOT EXISTS selections (
      code text PRIMARY KEY,
      data jsonb NOT NULL,
      at   timestamptz NOT NULL DEFAULT now()
    )`;

  await q`
    CREATE TABLE IF NOT EXISTS invitations (
      slug       text PRIMARY KEY,
      data       jsonb NOT NULL,
      created_at timestamptz NOT NULL DEFAULT now()
    )`;

  await q`
    CREATE TABLE IF NOT EXISTS rsvps (
      id   bigserial PRIMARY KEY,
      slug text NOT NULL,
      data jsonb NOT NULL,
      at   timestamptz NOT NULL DEFAULT now()
    )`;
  await q`CREATE INDEX IF NOT EXISTS rsvps_slug_idx ON rsvps (slug, at DESC)`;

  await q`
    CREATE TABLE IF NOT EXISTS leads (
      id   bigserial PRIMARY KEY,
      data jsonb NOT NULL,
      at   timestamptz NOT NULL DEFAULT now()
    )`;

  await q`
    CREATE TABLE IF NOT EXISTS payments (
      id      bigserial PRIMARY KEY,
      slug    text NOT NULL,
      orderid text NOT NULL,
      data    jsonb NOT NULL,
      at      timestamptz NOT NULL DEFAULT now()
    )`;
}
