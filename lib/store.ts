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

export async function getSelection(code: string): Promise<Selection | undefined> {
  await ready();
  const rows = (await sql()`SELECT data FROM selections WHERE code = ${code.trim().toLowerCase()}`) as {
    data: Selection;
  }[];
  return rows[0]?.data;
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

/* ---------------- Kundenakte ---------------- */

/** Freischaltcode fuer die digitale Einladung – einer pro Kunde. */
export type CouponInfo = {
  code: string;
  active: boolean;
  /** true = eine Einladung, danach verbraucht */
  once: boolean;
  /** ISO-Datum (YYYY-MM-DD); leer = unbegrenzt gueltig */
  expires: string;
  /** Wofuer der Code eingelöst wurde */
  usedFor: { slug: string; at: string }[];
};

/**
 * Ein Kunde = ein Auftrag = eine Galerie. Der Code ist gleichzeitig der
 * Anmeldename der Galerie; das Passwort wird in beiden Datensaetzen gehalten,
 * damit die bestehende Galerie-Anmeldung unveraendert weiterläuft.
 */
export type Customer = {
  code: string;
  password: string;
  /** Anzeigename, z. B. "Elif & Marco" */
  couple: string;
  email: string;
  phone: string;
  /** Hochzeitsdatum */
  date: string;
  venue: string;
  /** gebuchtes Paket, freier Text */
  packageName: string;
  /** Auftragswert, freier Text ("1.890 €") */
  amount: string;
  /** interne Notiz – der Kunde sieht sie nie */
  notes: string;
  status: "active" | "archived";
  coupon: CouponInfo;
  createdAt: string;
};

const code = (v: string) => v.trim().toLowerCase();

/** Aeltere Datensaetze robust machen (neue Felder, fehlender Gutschein). */
function completeCustomer(c: Customer): Customer {
  const coupon = c.coupon ?? { code: "", active: false, once: true, expires: "", usedFor: [] };
  return {
    ...c,
    email: c.email ?? "",
    phone: c.phone ?? "",
    venue: c.venue ?? "",
    packageName: c.packageName ?? "",
    amount: c.amount ?? "",
    notes: c.notes ?? "",
    status: c.status === "archived" ? "archived" : "active",
    coupon: { ...coupon, usedFor: Array.isArray(coupon.usedFor) ? coupon.usedFor : [] },
  };
}

export async function listCustomers(): Promise<Customer[]> {
  await ready();
  const rows = (await sql()`SELECT data FROM customers ORDER BY created_at DESC`) as { data: Customer }[];
  return rows.map((r) => completeCustomer(r.data));
}

export async function getCustomer(value: string): Promise<Customer | undefined> {
  await ready();
  const rows = (await sql()`SELECT data FROM customers WHERE code = ${code(value)}`) as { data: Customer }[];
  return rows[0] ? completeCustomer(rows[0].data) : undefined;
}

export async function saveCustomer(c: Customer): Promise<Customer> {
  await ready();
  const next = completeCustomer({ ...c, code: code(c.code) });
  await sql()`
    INSERT INTO customers (code, data) VALUES (${next.code}, ${JSON.stringify(next)})
    ON CONFLICT (code) DO UPDATE SET data = EXCLUDED.data`;
  return next;
}

export async function updateCustomer(value: string, patch: Partial<Customer>): Promise<Customer | null> {
  const current = await getCustomer(value);
  if (!current) return null;
  const next = completeCustomer({ ...current, ...patch, code: current.code });
  await sql()`UPDATE customers SET data = ${JSON.stringify(next)} WHERE code = ${current.code}`;
  // Das Passwort ist auch der Galerie-Zugang – beide Seiten muessen gleich sein.
  if (patch.password && patch.password !== current.password) {
    await updateGallery(current.code, { password: next.password });
  }
  if (patch.couple && patch.couple !== current.couple) {
    await updateGallery(current.code, { couple: next.couple });
  }
  return next;
}

/**
 * Endgueltig entfernen. `withGallery` loescht zusaetzlich Galerie, Auswahl und
 * die hochgeladenen Bilder – im Admin bewusst ein eigener, zweiter Knopf.
 */
export async function deleteCustomer(value: string, opts: { withGallery?: boolean } = {}) {
  await ready();
  const c = code(value);
  if (opts.withGallery) {
    const gal = await getGallery(c);
    for (const url of gal?.uploads ?? []) await deleteUpload(url);
    await deleteGallery(c);
  }
  await sql()`DELETE FROM customers WHERE code = ${c}`;
}

/* ---------------- Gutscheincodes ---------------- */

export type CouponReason = "empty" | "unknown" | "inactive" | "expired" | "used" | "archived";

export type CouponCheck = {
  ok: boolean;
  reason?: CouponReason;
  /** Welcher Kunde – nur fuer den Admin-Blick, nicht fuer den Browser */
  customer?: string;
  kind?: "customer" | "campaign";
};

/**
 * Prueft einen eingegebenen Code. Die allgemeine Kampagne kommt als Parameter
 * herein, damit dieses Modul nicht auf die Inhalte (lib/cms.ts) zugreifen muss.
 */
export async function checkCoupon(
  input: string,
  campaign?: { code: string; active: boolean }
): Promise<CouponCheck> {
  const value = code(input);
  if (!value) return { ok: false, reason: "empty" };

  if (campaign?.active && campaign.code && code(campaign.code) === value) {
    return { ok: true, kind: "campaign" };
  }

  const customer = (await listCustomers()).find((c) => c.coupon.code && code(c.coupon.code) === value);
  if (!customer) return { ok: false, reason: "unknown" };
  if (customer.status === "archived") return { ok: false, reason: "archived", customer: customer.code };
  if (!customer.coupon.active) return { ok: false, reason: "inactive", customer: customer.code };

  if (customer.coupon.expires) {
    // Am Ablauftag noch gueltig – verglichen wird nur das Datum.
    const today = new Date().toISOString().slice(0, 10);
    if (customer.coupon.expires < today) return { ok: false, reason: "expired", customer: customer.code };
  }
  if (customer.coupon.once && customer.coupon.usedFor.length > 0) {
    return { ok: false, reason: "used", customer: customer.code };
  }

  return { ok: true, kind: "customer", customer: customer.code };
}

/**
 * Code als eingelöst vermerken. Wird erst aufgerufen, wenn die Einladung
 * wirklich angelegt ist – so verbraucht ein Abbruch den Code nicht.
 */
export async function redeemCoupon(input: string, slug: string) {
  const value = code(input);
  const customer = (await listCustomers()).find((c) => c.coupon.code && code(c.coupon.code) === value);
  if (!customer) return;
  await updateCustomer(customer.code, {
    coupon: {
      ...customer.coupon,
      usedFor: [...customer.coupon.usedFor, { slug, at: new Date().toISOString() }].slice(-20),
    },
  });
}

/* ---------------- Entwuerfe des Assistenten ---------------- */

/**
 * Zwischenstand der Einladung. Der Token steht in der Fortsetzungs-URL, das
 * Feld `data` ist der Formularstand des Assistenten – absichtlich unstrukturiert,
 * damit ein neuer Schritt im Assistenten kein Datenbankschema aendert.
 */
export type InviteDraft = {
  token: string;
  /** Anzeigename fuer die Liste im Admin */
  label: string;
  data: unknown;
  updatedAt: string;
};

/** Liegengelassene Entwuerfe raeumen – sonst waechst die Tabelle endlos. */
async function purgeDrafts() {
  await sql()`DELETE FROM invite_drafts WHERE updated_at < now() - interval '120 days'`;
}

export async function saveDraft(token: string, label: string, data: unknown): Promise<InviteDraft> {
  await ready();
  const draft: InviteDraft = { token, label, data, updatedAt: new Date().toISOString() };
  await sql()`
    INSERT INTO invite_drafts (token, data, updated_at) VALUES (${token}, ${JSON.stringify(draft)}, now())
    ON CONFLICT (token) DO UPDATE SET data = EXCLUDED.data, updated_at = now()`;
  await purgeDrafts();
  return draft;
}

export async function getDraft(token: string): Promise<InviteDraft | undefined> {
  await ready();
  const rows = (await sql()`SELECT data FROM invite_drafts WHERE token = ${token}`) as { data: InviteDraft }[];
  return rows[0]?.data;
}

export async function listDrafts(): Promise<InviteDraft[]> {
  await ready();
  const rows = (await sql()`SELECT data FROM invite_drafts ORDER BY updated_at DESC LIMIT 200`) as {
    data: InviteDraft;
  }[];
  return rows.map((r) => r.data);
}

export async function deleteDraft(token: string) {
  await ready();
  await sql()`DELETE FROM invite_drafts WHERE token = ${token}`;
}

export { hasDb };
