import "server-only";

import { sql, ensureSchema, hasDb } from "./db";
import { seedGalleries, seedInvitations, seedRsvps } from "./seed";
import { deleteUpload } from "./media";
import type { SiteContent } from "./cms";

/**
 * Datenhaltung auf Neon Postgres.
 *
 * Die Funktionsnamen sind dieselben wie in der Demo-Fassung, nur asynchron:
 * Persistenz laesst sich nicht synchron lesen. Aufrufer awaiten daher.
 */

export type Gallery = {
  code: string;
  password: string;
  couple: string;
  date: string;
  venue: string;
  cover: string;
  /** Demo-Platzhalter */
  seeds: string[];
  /** Im Admin hochgeladene Bilder (Blob-URL) */
  uploads: string[];
  /** Hochzeitsfilm bei YouTube oder Vimeo */
  videoUrl?: string;
  expires: string;
};

export type Selection = {
  code: string;
  couple: string;
  picks: number[];
  note?: string;
  at: string;
};

export type ProgramItem = { time: string; title: string };

/** Eine Feier – bei zwei Zeremonien (z. B. Henna + Hochzeit) gibt es zwei davon. */
export type InviteEvent = {
  name: string;
  date: string;
  time: string;
  venue: string;
  address: string;
};

export type EventType = "wedding" | "multi" | "henna" | "engagement" | "circumcision" | "birthday" | "corporate";

/** Zuschaltbare Abschnitte der Einladung. */
export type InviteSections = {
  countdown: boolean;
  program: boolean;
  location: boolean;
  menu: boolean;
  family: boolean;
  music: boolean;
  video: boolean;
  rsvp: boolean;
};

export { defaultSections } from "./invite";

export type Invitation = {
  slug: string;
  bride: string;
  groom: string;
  eventType: EventType;
  events: InviteEvent[];
  message: string;
  closing?: string;
  families?: { bride: string; groom: string };
  /** Hochgeladene Bilder als Data-URL (Demo) bzw. Blob-URL (Live) */
  photos: string[];
  program: ProgramItem[];
  menu: string[];
  musicUrl?: string;
  videoUrl?: string;
  sections: InviteSections;
  hashtag?: string;
  theme: string;
  locale: "de" | "tr";
  /** true = Kunde mit Foto-/Filmpaket, Einladung kostenfrei */
  paid: boolean;
  /** berechneter Preis in Euro (0 bei Kundencode) */
  price: number;
  /** PayPal-Order-ID nach erfolgreicher Zahlung */
  paymentRef?: string;
  createdAt: string;
};

export type Rsvp = {
  slug: string;
  name: string;
  coming: boolean;
  count: number;
  note?: string;
  at: string;
};

export type Payment = { slug: string; orderId: string; amount: number; at: string };

export type Lead = {
  name: string;
  email: string;
  phone?: string;
  date?: string;
  location?: string;
  guests?: string;
  service?: string;
  message: string;
  locale: string;
  at: string;
};

/* --------------------------------------------------------------- */
/*  Erststart: Demo-Bestand nur in eine leere Datenbank schreiben    */
/* --------------------------------------------------------------- */

let seeded: Promise<void> | null = null;

async function ready() {
  await ensureSchema();
  if (!seeded) seeded = seedIfEmpty();
  await seeded;
}

async function seedIfEmpty() {
  const q = sql();

  // Atomarer Riegel: Nur die Sitzung, die diese Zeile tatsaechlich einfuegt,
  // darf saeen. Alle parallelen Build-Worker bekommen ein leeres Ergebnis.
  const claim = (await q`
    INSERT INTO seed_marker (id) VALUES (1)
    ON CONFLICT (id) DO NOTHING
    RETURNING id`) as { id: number }[];
  if (claim.length === 0) return;

  for (const g of seedGalleries()) {
    await q`INSERT INTO galleries (code, data) VALUES (${g.code}, ${JSON.stringify(g)})
            ON CONFLICT (code) DO NOTHING`;
  }
  for (const i of seedInvitations()) {
    await q`INSERT INTO invitations (slug, data) VALUES (${i.slug}, ${JSON.stringify(i)})
            ON CONFLICT (slug) DO NOTHING`;
  }
  for (const r of seedRsvps()) {
    await q`INSERT INTO rsvps (slug, data, at) VALUES (${r.slug}, ${JSON.stringify(r)}, ${r.at})`;
  }
}

/** Ältere Einträge ohne Upload-Feld robust machen. */
function normalize(g: Gallery): Gallery {
  if (!Array.isArray(g.uploads)) g.uploads = [];
  if (!Array.isArray(g.seeds)) g.seeds = [];
  return g;
}

/* ---------------- Redaktionelle Inhalte ---------------- */

export async function loadContent(): Promise<SiteContent | null> {
  await ready();
  const rows = (await sql()`SELECT data FROM site_content WHERE id = 1`) as { data: SiteContent }[];
  return rows[0]?.data ?? null;
}

export async function storeContent(content: SiteContent) {
  await ready();
  await sql()`
    INSERT INTO site_content (id, data, updated_at) VALUES (1, ${JSON.stringify(content)}, now())
    ON CONFLICT (id) DO UPDATE SET data = EXCLUDED.data, updated_at = now()`;
}

export async function clearContent() {
  await ready();
  await sql()`DELETE FROM site_content WHERE id = 1`;
}

/* ---------------- Galerie ---------------- */

export async function getGallery(code: string): Promise<Gallery | undefined> {
  await ready();
  const rows = (await sql()`
    SELECT data FROM galleries WHERE code = ${code.trim().toLowerCase()}`) as { data: Gallery }[];
  return rows[0] ? normalize(rows[0].data) : undefined;
}

export async function authGallery(code: string, password: string): Promise<Gallery | null> {
  const gal = await getGallery(code);
  if (!gal) return null;
  return gal.password === password.trim() ? gal : null;
}

export async function listGalleries(): Promise<Gallery[]> {
  await ready();
  const rows = (await sql()`SELECT data FROM galleries ORDER BY created_at DESC`) as { data: Gallery }[];
  return rows.map((r) => normalize(r.data));
}

export async function createGallery(
  g: Omit<Gallery, "seeds" | "uploads"> & Partial<Pick<Gallery, "seeds" | "uploads">>
): Promise<Gallery> {
  await ready();
  const gallery: Gallery = { seeds: [], uploads: [], ...g, code: g.code.trim().toLowerCase() };
  await sql()`
    INSERT INTO galleries (code, data) VALUES (${gallery.code}, ${JSON.stringify(gallery)})
    ON CONFLICT (code) DO UPDATE SET data = EXCLUDED.data`;
  return gallery;
}

export async function updateGallery(code: string, patch: Partial<Gallery>): Promise<Gallery | null> {
  const g = await getGallery(code);
  if (!g) return null;
  const next = { ...g, ...patch };
  await sql()`UPDATE galleries SET data = ${JSON.stringify(next)} WHERE code = ${code}`;
  return next;
}

export async function deleteGallery(code: string) {
  await ready();
  await sql()`DELETE FROM galleries WHERE code = ${code}`;
  await sql()`DELETE FROM selections WHERE code = ${code}`;
}

export async function addGalleryPhotos(code: string, photos: string[]) {
  const g = await getGallery(code);
  if (!g) return null;
  return updateGallery(code, { uploads: [...g.uploads, ...photos].slice(0, 200) });
}

export async function removeGalleryPhoto(code: string, index: number) {
  const g = await getGallery(code);
  if (!g) return null;
  const removed = g.uploads[index];
  const next = await updateGallery(code, { uploads: g.uploads.filter((_, i) => i !== index) });
  if (removed) await deleteUpload(removed);
  return next;
}

/* ---------------- Auswahl fürs Album ---------------- */

export async function saveSelection(s: Selection) {
  await ready();
  await sql()`
    INSERT INTO selections (code, data, at) VALUES (${s.code}, ${JSON.stringify(s)}, now())
    ON CONFLICT (code) DO UPDATE SET data = EXCLUDED.data, at = now()`;
}

export async function listSelections(): Promise<Selection[]> {
  await ready();
  const rows = (await sql()`SELECT data FROM selections ORDER BY at DESC`) as { data: Selection }[];
  return rows.map((r) => r.data);
}

/* ---------------- Einladungen ---------------- */

export async function getInvitation(slug: string): Promise<Invitation | undefined> {
  await ready();
  const rows = (await sql()`SELECT data FROM invitations WHERE slug = ${slug}`) as { data: Invitation }[];
  return rows[0]?.data;
}

export async function listInvitations(): Promise<Invitation[]> {
  await ready();
  const rows = (await sql()`SELECT data FROM invitations ORDER BY created_at DESC`) as { data: Invitation }[];
  return rows.map((r) => r.data);
}

export async function createInvitation(inv: Invitation): Promise<Invitation> {
  await ready();
  await sql()`
    INSERT INTO invitations (slug, data) VALUES (${inv.slug}, ${JSON.stringify(inv)})
    ON CONFLICT (slug) DO UPDATE SET data = EXCLUDED.data`;
  return inv;
}

export async function updateInvitation(slug: string, patch: Partial<Invitation>) {
  const inv = await getInvitation(slug);
  if (!inv) return null;
  const next = { ...inv, ...patch };
  await sql()`UPDATE invitations SET data = ${JSON.stringify(next)} WHERE slug = ${slug}`;
  return next;
}

export async function deleteInvitation(slug: string) {
  const inv = await getInvitation(slug);
  await sql()`DELETE FROM invitations WHERE slug = ${slug}`;
  await sql()`DELETE FROM rsvps WHERE slug = ${slug}`;
  for (const photo of inv?.photos ?? []) await deleteUpload(photo);
}

export async function slugAvailable(slug: string) {
  return !(await getInvitation(slug));
}

/* ---------------- Rückmeldungen ---------------- */

export async function addRsvp(r: Rsvp) {
  await ready();
  await sql()`INSERT INTO rsvps (slug, data, at) VALUES (${r.slug}, ${JSON.stringify(r)}, now())`;
}

export async function rsvpsFor(slug: string): Promise<Rsvp[]> {
  await ready();
  const rows = (await sql()`SELECT data FROM rsvps WHERE slug = ${slug} ORDER BY at DESC`) as { data: Rsvp }[];
  return rows.map((r) => r.data);
}

export async function listRsvps(): Promise<Rsvp[]> {
  await ready();
  const rows = (await sql()`SELECT data FROM rsvps ORDER BY at DESC`) as { data: Rsvp }[];
  return rows.map((r) => r.data);
}

/* ---------------- Anfragen ---------------- */

export async function addLead(l: Lead) {
  await ready();
  await sql()`INSERT INTO leads (data, at) VALUES (${JSON.stringify(l)}, now())`;
}

export async function listLeads(): Promise<Lead[]> {
  await ready();
  const rows = (await sql()`SELECT data FROM leads ORDER BY at DESC`) as { data: Lead }[];
  return rows.map((r) => r.data);
}

/* ---------------- Zahlungen ---------------- */

export async function addPayment(p: Payment) {
  await ready();
  await sql()`
    INSERT INTO payments (slug, orderid, data, at)
    VALUES (${p.slug}, ${p.orderId}, ${JSON.stringify(p)}, now())`;
}

export async function listPayments(): Promise<Payment[]> {
  await ready();
  const rows = (await sql()`SELECT data FROM payments ORDER BY at DESC`) as { data: Payment }[];
  return rows.map((r) => r.data);
}

/* ---------------- Gutscheincodes ---------------- */

const CUSTOMER_CODES = new Set(["lumiere2026", "elif-marco", "sarah-daniel", "kunde2026"]);
export const isCustomerCode = (code: string) => CUSTOMER_CODES.has(code.trim().toLowerCase());

export { hasDb };
