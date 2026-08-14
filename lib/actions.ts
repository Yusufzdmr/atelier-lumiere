"use server";

import { cookies } from "next/headers";
import { revalidatePath } from "next/cache";
import { redirect } from "next/navigation";
import {
  addGalleryPhotos,
  createGallery,
  deleteGallery,
  deleteInvitation,
  removeGalleryPhoto,
  updateGallery,
} from "./store";
import {
  getContent, saveContent, resetContent, defaultContent,
  saveVenue, addVenue, removeVenue,
  saveCity, addCity, removeCity,
  saveStory, addStory, removeStory, addStoryPhotos, removeStoryPhoto,
  savePost, addPost, removePost, addPostPhotos, removePostPhoto,
  type SiteContent,
} from "./cms";
import type { Service, ProcessStep, Testimonial, FaqItem } from "./content";
import { legalPageOrder, type LegalKey, type LegalSection } from "./legal";
import { slugify } from "./invite";
import { saveUploads } from "./media";

const COOKIE = "al-admin";

export async function isAdmin() {
  const jar = await cookies();
  return jar.get(COOKIE)?.value === "1";
}

async function requireAdmin() {
  if (!(await isAdmin())) throw new Error("unauthorized");
}

/* ------------------------------ Login ------------------------------ */

export type LoginState = { error?: string };

export async function login(_prev: LoginState, formData: FormData): Promise<LoginState> {
  const password = String(formData.get("password") ?? "");
  const locale = String(formData.get("locale") ?? "de");
  const expected = process.env.ADMIN_KEY || "demo";
  if (password !== expected) return { error: "wrong" };

  const jar = await cookies();
  jar.set(COOKIE, "1", {
    httpOnly: true,
    sameSite: "lax",
    secure: process.env.NODE_ENV === "production",
    path: "/",
    maxAge: 60 * 60 * 12,
  });
  // Neu rendern, damit das Layout den angemeldeten Zustand kennt
  redirect(`/${locale}/admin`);
}

export async function logout(formData: FormData) {
  const jar = await cookies();
  jar.delete(COOKIE);
  redirect(`/${String(formData.get("locale") ?? "de")}/admin`);
}

/* ------------------------------ Inhalte ------------------------------ */

/**
 * Browser senden Zeilenumbrüche aus einem <textarea> als CRLF. Ohne diese
 * Normalisierung greift die Absatztrennung in paras() nicht mehr.
 */
const CRLF = new RegExp(String.fromCharCode(13, 10), "g");
const field = (fd: FormData, key: string) =>
  String(fd.get(key) ?? "").replace(CRLF, String.fromCharCode(10));

const str = (fd: FormData, key: string) => field(fd, key).trim();
const lines = (fd: FormData, key: string) =>
  field(fd, key)
    .split("\n")
    .map((l) => l.trim())
    .filter(Boolean);

export async function saveTexts(formData: FormData) {
  await requireAdmin();
  const c: SiteContent = structuredClone(await getContent());

  c.hero.eyebrow.de = str(formData, "hero_eyebrow_de");
  c.hero.eyebrow.tr = str(formData, "hero_eyebrow_tr");
  c.hero.title.de = str(formData, "hero_title_de");
  c.hero.title.tr = str(formData, "hero_title_tr");
  c.hero.text.de = str(formData, "hero_text_de");
  c.hero.text.tr = str(formData, "hero_text_tr");

  c.stats.weddings = str(formData, "stat_weddings");
  c.stats.years = str(formData, "stat_years");
  c.stats.delivery = str(formData, "stat_delivery");
  c.stats.rating = str(formData, "stat_rating");

  c.contact.phoneHuman = str(formData, "phone_human");
  c.contact.phone = str(formData, "phone");
  c.contact.email = str(formData, "email");
  c.contact.street = str(formData, "street");
  c.contact.zip = str(formData, "zip");
  c.contact.city = str(formData, "city");
  c.contact.hours.de = str(formData, "hours_de");
  c.contact.hours.tr = str(formData, "hours_tr");
  c.contact.instagram = str(formData, "instagram");
  c.contact.mapsQuery = str(formData, "maps_query");

  await saveContent(c);
  revalidatePath("/", "layout");
}

export async function savePackages(formData: FormData) {
  await requireAdmin();
  const c: SiteContent = structuredClone(await getContent());

  c.packages = c.packages.map((p, i) => ({
    ...p,
    name: { de: str(formData, `p${i}_name_de`), tr: str(formData, `p${i}_name_tr`) },
    price: str(formData, `p${i}_price`),
    hint: { de: str(formData, `p${i}_hint_de`), tr: str(formData, `p${i}_hint_tr`) },
    features: { de: lines(formData, `p${i}_features_de`), tr: lines(formData, `p${i}_features_tr`) },
    featured: formData.get(`featured`) === String(i),
  }));

  c.addons = c.addons.map((a, i) => ({
    name: { de: str(formData, `a${i}_name_de`), tr: str(formData, `a${i}_name_tr`) },
    price: str(formData, `a${i}_price`),
  }));

  await saveContent(c);
  revalidatePath("/", "layout");
}

export async function resetTexts() {
  await requireAdmin();
  await resetContent();
  revalidatePath("/", "layout");
}

/* ------------------------------ Galerien ------------------------------ */

export async function newGallery(formData: FormData) {
  await requireAdmin();
  const code = str(formData, "code").toLowerCase().replace(/[^a-z0-9-]/g, "-");
  if (!code) return;

  await createGallery({
    code,
    password: str(formData, "password") || Math.random().toString(36).slice(2, 8),
    couple: str(formData, "couple"),
    date: str(formData, "date"),
    venue: str(formData, "venue"),
    cover: `gal-${code}-cover`,
    expires: str(formData, "expires") || "",
  });
  revalidatePath("/", "layout");
  redirect(`/${str(formData, "locale") || "de"}/admin/galerien/${code}`);
}

export async function editGallery(formData: FormData) {
  await requireAdmin();
  const code = str(formData, "code");
  await updateGallery(code, {
    couple: str(formData, "couple"),
    password: str(formData, "password"),
    videoUrl: str(formData, "video") || undefined,
    date: str(formData, "date"),
    venue: str(formData, "venue"),
    expires: str(formData, "expires"),
  });
  revalidatePath("/", "layout");
}

export async function removeGallery(formData: FormData) {
  await requireAdmin();
  await deleteGallery(str(formData, "code"));
  revalidatePath("/", "layout");
  redirect(`/${str(formData, "locale") || "de"}/admin/galerien`);
}

/** Wird vom Upload-Client mit bereits verkleinerten Data-URLs aufgerufen. */
export async function uploadGalleryPhotos(code: string, photos: string[]) {
  await requireAdmin();
  const safe = photos.filter((p) => typeof p === "string" && p.startsWith("data:image/") && p.length < 1_400_000);
  const urls = await saveUploads(safe.slice(0, 60), `galerien/${code}`);
  await addGalleryPhotos(code, urls);
  revalidatePath("/", "layout");
  return { ok: true, added: safe.length };
}

export async function deleteGalleryPhoto(formData: FormData) {
  await requireAdmin();
  await removeGalleryPhoto(str(formData, "code"), Number(formData.get("index")));
  revalidatePath("/", "layout");
}

/* --------------------------- Hochzeitslocations --------------------------- */

const paras = (fd: FormData, key: string) =>
  field(fd, key)
    .split(new RegExp(String.fromCharCode(92) + "n{2,}"))
    .map((p) => p.trim())
    .filter(Boolean);

export async function editVenue(formData: FormData) {
  await requireAdmin();
  const slug = str(formData, "slug");
  await saveVenue(slug, {
    name: str(formData, "name"),
    city: str(formData, "city"),
    citySlug: str(formData, "citySlug"),
    address: str(formData, "address"),
    type: { de: str(formData, "type_de"), tr: str(formData, "type_tr") },
    capacity: { de: str(formData, "capacity_de"), tr: str(formData, "capacity_tr") },
    lead: { de: str(formData, "lead_de"), tr: str(formData, "lead_tr") },
    body: { de: paras(formData, "body_de"), tr: paras(formData, "body_tr") },
    light: { de: str(formData, "light_de"), tr: str(formData, "light_tr") },
    spots: { de: lines(formData, "spots_de"), tr: lines(formData, "spots_tr") },
    rules: { de: lines(formData, "rules_de"), tr: lines(formData, "rules_tr") },
  });
  revalidatePath("/", "layout");
}

export async function newVenue(formData: FormData) {
  await requireAdmin();
  const name = str(formData, "name");
  const slug = slugify(str(formData, "slug") || name);
  if (!name || !slug) return;

  await addVenue({
    slug,
    name,
    city: str(formData, "city"),
    citySlug: str(formData, "citySlug") || "stuttgart",
    address: str(formData, "address"),
    type: { de: str(formData, "type_de") || "Location", tr: str(formData, "type_tr") || "Mekân" },
    capacity: { de: str(formData, "capacity_de"), tr: str(formData, "capacity_tr") },
    lead: { de: str(formData, "lead_de"), tr: str(formData, "lead_tr") },
    body: { de: paras(formData, "body_de"), tr: paras(formData, "body_tr") },
    light: { de: "", tr: "" },
    timing: [],
    spots: { de: [], tr: [] },
    rules: { de: [], tr: [] },
    faq: [],
  });
  revalidatePath("/", "layout");
  redirect(`/${str(formData, "locale") || "de"}/admin/mekanlar/${slug}`);
}

export async function deleteVenue(formData: FormData) {
  await requireAdmin();
  await removeVenue(str(formData, "slug"));
  revalidatePath("/", "layout");
  redirect(`/${str(formData, "locale") || "de"}/admin/mekanlar`);
}

/* -------------------------------- Städte -------------------------------- */

/** "Name | Beschreibung" pro Zeile */
const pairs = (fd: FormData, key: string) =>
  lines(fd, key).map((l) => {
    const [a, ...rest] = l.split("|");
    return { a: a.trim(), b: rest.join("|").trim() };
  });

export async function editCity(formData: FormData) {
  await requireAdmin();
  const slug = str(formData, "slug");
  const spotsDe = pairs(formData, "spots_de");
  const spotsTr = pairs(formData, "spots_tr");
  const faqDe = pairs(formData, "faq_de");
  const faqTr = pairs(formData, "faq_tr");

  await saveCity(slug, {
    name: str(formData, "name"),
    kreis: { de: str(formData, "kreis_de"), tr: str(formData, "kreis_tr") },
    drive: { de: str(formData, "drive_de"), tr: str(formData, "drive_tr") },
    lead: { de: str(formData, "lead_de"), tr: str(formData, "lead_tr") },
    body: { de: paras(formData, "body_de"), tr: paras(formData, "body_tr") },
    spots: spotsDe.map((s, i) => ({
      name: s.a,
      note: { de: s.b, tr: spotsTr[i]?.b ?? s.b },
    })),
    faq: faqDe.map((f, i) => ({
      q: { de: f.a, tr: faqTr[i]?.a ?? f.a },
      a: { de: f.b, tr: faqTr[i]?.b ?? f.b },
    })),
  });
  revalidatePath("/", "layout");
}

export async function newCity(formData: FormData) {
  await requireAdmin();
  const name = str(formData, "name");
  const slug = slugify(str(formData, "slug") || name);
  if (!name || !slug) return;
  await addCity({
    slug,
    name,
    kreis: { de: str(formData, "kreis_de"), tr: str(formData, "kreis_tr") },
    drive: { de: str(formData, "drive_de"), tr: str(formData, "drive_tr") },
    lead: { de: str(formData, "lead_de"), tr: str(formData, "lead_tr") },
    body: { de: paras(formData, "body_de"), tr: paras(formData, "body_tr") },
    spots: [],
    venues: [],
    neighbours: [],
    faq: [],
  });
  revalidatePath("/", "layout");
  redirect(`/${str(formData, "locale") || "de"}/admin/sehirler`);
}

export async function deleteCity(formData: FormData) {
  await requireAdmin();
  await removeCity(str(formData, "slug"));
  revalidatePath("/", "layout");
}

/* ------------------------------ Reportagen ------------------------------ */

export async function editStory(formData: FormData) {
  await requireAdmin();
  const slug = str(formData, "slug");
  await saveStory(slug, {
    couple: str(formData, "couple"),
    videoUrl: str(formData, "video") || undefined,
    guests: str(formData, "guests"),
    citySlug: str(formData, "citySlug"),
    venueSlug: str(formData, "venueSlug") || undefined,
    venue: { de: str(formData, "venue_de"), tr: str(formData, "venue_tr") },
    month: { de: str(formData, "month_de"), tr: str(formData, "month_tr") },
    intro: { de: str(formData, "intro_de"), tr: str(formData, "intro_tr") },
    body: { de: paras(formData, "body_de"), tr: paras(formData, "body_tr") },
    quote: { de: str(formData, "quote_de"), tr: str(formData, "quote_tr") },
  });
  revalidatePath("/", "layout");
}

export async function newStory(formData: FormData) {
  await requireAdmin();
  const couple = str(formData, "couple");
  const slug = slugify(str(formData, "slug") || couple);
  if (!couple || !slug) return;
  await addStory({
    slug,
    couple,
    citySlug: str(formData, "citySlug") || "stuttgart",
    venueSlug: str(formData, "venueSlug") || undefined,
    venue: { de: str(formData, "venue_de"), tr: str(formData, "venue_tr") },
    month: { de: str(formData, "month_de"), tr: str(formData, "month_tr") },
    guests: str(formData, "guests"),
    seeds: Array.from({ length: 6 }, (_, i) => `story-${slug}-${i + 1}`),
    uploads: [],
    intro: { de: str(formData, "intro_de"), tr: str(formData, "intro_tr") },
    body: { de: [], tr: [] },
    quote: { de: "", tr: "" },
  });
  revalidatePath("/", "layout");
  redirect(`/${str(formData, "locale") || "de"}/admin/portfolyo`);
}

export async function deleteStory(formData: FormData) {
  await requireAdmin();
  await removeStory(str(formData, "slug"));
  revalidatePath("/", "layout");
}

export async function uploadStoryPhotos(slug: string, photos: string[]) {
  await requireAdmin();
  const safe = photos.filter((p) => typeof p === "string" && p.startsWith("data:image/") && p.length < 1_400_000);
  const urls = await saveUploads(safe.slice(0, 24), `portfolio/${slug}`);
  await addStoryPhotos(slug, urls);
  revalidatePath("/", "layout");
  return { ok: true, added: safe.length };
}

export async function deleteStoryPhoto(formData: FormData) {
  await requireAdmin();
  await removeStoryPhoto(str(formData, "slug"), Number(formData.get("index")));
  revalidatePath("/", "layout");
}

/* ------------------------------ Einladungen ------------------------------ */

export async function removeInvitation(formData: FormData) {
  await requireAdmin();
  await deleteInvitation(str(formData, "slug"));
  revalidatePath("/", "layout");
}

/* --------------------------- Leistungen --------------------------- */

/**
 * Liest die Formularzeilen positionstreu ein: Zeilen ohne Titel werden zu
 * `null`, damit ein Löschen-Button den richtigen Index trifft. Erst danach
 * wird gefiltert. So gehen beim Hinzufügen/Löschen keine Änderungen in den
 * übrigen Feldern verloren.
 */
function readServices(fd: FormData, prev: Service[]): (Service | null)[] {
  const count = Number(fd.get("count") ?? prev.length);
  return Array.from({ length: count }, (_, i) => {
    const titleDe = str(fd, `s${i}_title_de`);
    if (!titleDe) return null;
    const before = prev[i];
    return {
      slug: slugify(str(fd, `s${i}_slug`) || titleDe),
      seed: str(fd, `s${i}_seed`) || before?.seed || `lum-service-${i + 1}`,
      title: { de: titleDe, tr: str(fd, `s${i}_title_tr`) || titleDe },
      short: { de: str(fd, `s${i}_short_de`), tr: str(fd, `s${i}_short_tr`) },
      body: { de: paras(fd, `s${i}_body_de`), tr: paras(fd, `s${i}_body_tr`) },
      bullets: { de: lines(fd, `s${i}_bullets_de`), tr: lines(fd, `s${i}_bullets_tr`) },
    };
  });
}

function readProcess(fd: FormData, count: number): (ProcessStep | null)[] {
  return Array.from({ length: count }, (_, i) => {
    const titleDe = str(fd, `w${i}_title_de`);
    if (!titleDe) return null;
    return {
      step: "",
      title: { de: titleDe, tr: str(fd, `w${i}_title_tr`) || titleDe },
      text: { de: str(fd, `w${i}_text_de`), tr: str(fd, `w${i}_text_tr`) },
    };
  });
}

/** Schrittnummern immer fortlaufend: 01, 02, 03 … */
const numbered = (steps: ProcessStep[]): ProcessStep[] =>
  steps.map((s, i) => ({ ...s, step: String(i + 1).padStart(2, "0") }));

const emptyService = (): Service => ({
  slug: "",
  seed: `lum-service-${Math.abs(Date.now() % 9999)}`,
  title: { de: "", tr: "" },
  short: { de: "", tr: "" },
  body: { de: [], tr: [] },
  bullets: { de: [], tr: [] },
});

function applyServicesForm(fd: FormData, c: SiteContent) {
  const services = readServices(fd, c.services);
  const process = readProcess(fd, Number(fd.get("process_count") ?? c.process.length));
  c.services = services.filter((s): s is Service => Boolean(s));
  c.process = numbered(process.filter((s): s is ProcessStep => Boolean(s)));
  return { services, process };
}

export async function saveServices(formData: FormData) {
  await requireAdmin();
  const c: SiteContent = structuredClone(await getContent());
  applyServicesForm(formData, c);
  await saveContent(c);
  revalidatePath("/", "layout");
}

export async function addService(formData: FormData) {
  await requireAdmin();
  const c: SiteContent = structuredClone(await getContent());
  applyServicesForm(formData, c);
  c.services = [...c.services, emptyService()];
  await saveContent(c);
  revalidatePath("/", "layout");
}

export async function deleteService(idx: number, formData: FormData) {
  await requireAdmin();
  const c: SiteContent = structuredClone(await getContent());
  const { services } = applyServicesForm(formData, c);
  c.services = services.filter((s, i): s is Service => Boolean(s) && i !== idx);
  await saveContent(c);
  revalidatePath("/", "layout");
}

export async function addProcessStep(formData: FormData) {
  await requireAdmin();
  const c: SiteContent = structuredClone(await getContent());
  applyServicesForm(formData, c);
  c.process = numbered([...c.process, { step: "", title: { de: "", tr: "" }, text: { de: "", tr: "" } }]);
  await saveContent(c);
  revalidatePath("/", "layout");
}

export async function deleteProcessStep(idx: number, formData: FormData) {
  await requireAdmin();
  const c: SiteContent = structuredClone(await getContent());
  const { process } = applyServicesForm(formData, c);
  c.process = numbered(process.filter((s, i): s is ProcessStep => Boolean(s) && i !== idx));
  await saveContent(c);
  revalidatePath("/", "layout");
}

/* -------------------- Über mich, Stimmen & FAQ -------------------- */

function readTestimonials(fd: FormData, count: number): (Testimonial | null)[] {
  return Array.from({ length: count }, (_, i) => {
    const name = str(fd, `t${i}_name`);
    if (!name) return null;
    return {
      name,
      city: { de: str(fd, `t${i}_city_de`), tr: str(fd, `t${i}_city_tr`) || str(fd, `t${i}_city_de`) },
      text: { de: str(fd, `t${i}_text_de`), tr: str(fd, `t${i}_text_tr`) },
    };
  });
}

function readFaq(fd: FormData, count: number): (FaqItem | null)[] {
  return Array.from({ length: count }, (_, i) => {
    const qDe = str(fd, `f${i}_q_de`);
    if (!qDe) return null;
    return {
      q: { de: qDe, tr: str(fd, `f${i}_q_tr`) || qDe },
      a: { de: str(fd, `f${i}_a_de`), tr: str(fd, `f${i}_a_tr`) },
    };
  });
}

function applyAboutForm(fd: FormData, c: SiteContent) {
  const valuesDe = pairs(fd, "about_values_de");
  const valuesTr = pairs(fd, "about_values_tr");

  c.about = {
    name: str(fd, "about_name"),
    lead: { de: str(fd, "about_lead_de"), tr: str(fd, "about_lead_tr") },
    body: { de: paras(fd, "about_body_de"), tr: paras(fd, "about_body_tr") },
    valuesTitle: { de: str(fd, "about_values_title_de"), tr: str(fd, "about_values_title_tr") },
    values: valuesDe.map((v, i) => ({
      t: { de: v.a, tr: valuesTr[i]?.a ?? v.a },
      d: { de: v.b, tr: valuesTr[i]?.b ?? v.b },
    })),
    gearTitle: { de: str(fd, "about_gear_title_de"), tr: str(fd, "about_gear_title_tr") },
    gear: { de: lines(fd, "about_gear_de"), tr: lines(fd, "about_gear_tr") },
  };

  const testimonials = readTestimonials(fd, Number(fd.get("testimonial_count") ?? c.testimonials.length));
  const faq = readFaq(fd, Number(fd.get("faq_count") ?? c.faq.length));
  c.testimonials = testimonials.filter((t): t is Testimonial => Boolean(t));
  c.faq = faq.filter((f): f is FaqItem => Boolean(f));
  return { testimonials, faq };
}

export async function saveAbout(formData: FormData) {
  await requireAdmin();
  const c: SiteContent = structuredClone(await getContent());
  applyAboutForm(formData, c);
  await saveContent(c);
  revalidatePath("/", "layout");
}

export async function addTestimonial(formData: FormData) {
  await requireAdmin();
  const c: SiteContent = structuredClone(await getContent());
  applyAboutForm(formData, c);
  c.testimonials = [...c.testimonials, { name: "", city: { de: "", tr: "" }, text: { de: "", tr: "" } }];
  await saveContent(c);
  revalidatePath("/", "layout");
}

export async function deleteTestimonial(idx: number, formData: FormData) {
  await requireAdmin();
  const c: SiteContent = structuredClone(await getContent());
  const { testimonials } = applyAboutForm(formData, c);
  c.testimonials = testimonials.filter((t, i): t is Testimonial => Boolean(t) && i !== idx);
  await saveContent(c);
  revalidatePath("/", "layout");
}

export async function addFaqItem(formData: FormData) {
  await requireAdmin();
  const c: SiteContent = structuredClone(await getContent());
  applyAboutForm(formData, c);
  c.faq = [...c.faq, { q: { de: "", tr: "" }, a: { de: "", tr: "" } }];
  await saveContent(c);
  revalidatePath("/", "layout");
}

export async function deleteFaqItem(idx: number, formData: FormData) {
  await requireAdmin();
  const c: SiteContent = structuredClone(await getContent());
  const { faq } = applyAboutForm(formData, c);
  c.faq = faq.filter((f, i): f is FaqItem => Boolean(f) && i !== idx);
  await saveContent(c);
  revalidatePath("/", "layout");
}

/* ------------------------- Rechtstexte ---------------------------- */

const legalKey = (fd: FormData): LegalKey => {
  const k = str(fd, "legal_key");
  return legalPageOrder.includes(k as LegalKey) ? (k as LegalKey) : "impressum";
};

function applyLegalForm(fd: FormData, c: SiteContent) {
  const key = legalKey(fd);
  const count = Number(fd.get("count") ?? c.legal[key].sections.length);
  const sections = Array.from({ length: count }, (_, i) => {
    const heading = str(fd, `l${i}_heading`);
    const body = str(fd, `l${i}_body`);
    if (!heading && !body) return null;
    return { heading, body };
  });

  c.legal[key] = {
    title: str(fd, "legal_title") || c.legal[key].title,
    sections: sections.filter((s): s is LegalSection => Boolean(s)),
    note: str(fd, "legal_note"),
  };
  return { key, sections };
}

export async function saveLegal(formData: FormData) {
  await requireAdmin();
  const c: SiteContent = structuredClone(await getContent());
  applyLegalForm(formData, c);
  await saveContent(c);
  revalidatePath("/", "layout");
}

export async function addLegalSection(formData: FormData) {
  await requireAdmin();
  const c: SiteContent = structuredClone(await getContent());
  const { key } = applyLegalForm(formData, c);
  c.legal[key].sections = [...c.legal[key].sections, { heading: "", body: "" }];
  await saveContent(c);
  revalidatePath("/", "layout");
}

export async function deleteLegalSection(idx: number, formData: FormData) {
  await requireAdmin();
  const c: SiteContent = structuredClone(await getContent());
  const { key, sections } = applyLegalForm(formData, c);
  c.legal[key].sections = sections.filter((s, i): s is LegalSection => Boolean(s) && i !== idx);
  await saveContent(c);
  revalidatePath("/", "layout");
}

/* ------------------- Einzelne Bereiche zurücksetzen ------------------- */

export async function resetSection(section: string, formData: FormData) {
  await requireAdmin();
  const c: SiteContent = structuredClone(await getContent());
  const d = defaultContent();

  switch (section) {
    case "services":
      c.services = d.services;
      c.process = d.process;
      break;
    case "about":
      c.about = d.about;
      c.testimonials = d.testimonials;
      c.faq = d.faq;
      break;
    case "legal": {
      const key = legalKey(formData);
      c.legal[key] = d.legal[key];
      break;
    }
    default:
      return;
  }

  await saveContent(c);
  revalidatePath("/", "layout");
}

/* ------------------------------ Ratgeber ------------------------------ */

export async function editPost(formData: FormData) {
  await requireAdmin();
  const slug = str(formData, "slug");
  const faqDe = pairs(formData, "faq_de");
  const faqTr = pairs(formData, "faq_tr");

  await savePost(slug, {
    title: { de: str(formData, "title_de"), tr: str(formData, "title_tr") },
    excerpt: { de: str(formData, "excerpt_de"), tr: str(formData, "excerpt_tr") },
    body: { de: paras(formData, "body_de"), tr: paras(formData, "body_tr") },
    date: str(formData, "date"),
    seed: str(formData, "seed"),
    citySlug: str(formData, "citySlug") || undefined,
    venueSlug: str(formData, "venueSlug") || undefined,
    faq: faqDe.map((f, i) => ({
      q: { de: f.a, tr: faqTr[i]?.a ?? f.a },
      a: { de: f.b, tr: faqTr[i]?.b ?? f.b },
    })),
  });
  revalidatePath("/", "layout");
}

export async function newPost(formData: FormData) {
  await requireAdmin();
  const titleDe = str(formData, "title_de");
  const slug = slugify(str(formData, "slug") || titleDe);
  if (!titleDe || !slug) return;

  await addPost({
    slug,
    title: { de: titleDe, tr: str(formData, "title_tr") || titleDe },
    excerpt: { de: str(formData, "excerpt_de"), tr: str(formData, "excerpt_tr") },
    body: { de: paras(formData, "body_de"), tr: paras(formData, "body_tr") },
    date: str(formData, "date") || new Date().toISOString().slice(0, 10),
    seed: str(formData, "seed") || `lum-blog-${slug.slice(0, 20)}`,
    citySlug: str(formData, "citySlug") || undefined,
    venueSlug: str(formData, "venueSlug") || undefined,
    faq: [],
  });
  revalidatePath("/", "layout");
  redirect(`/${str(formData, "locale") || "de"}/admin/ratgeber`);
}

export async function deletePost(formData: FormData) {
  await requireAdmin();
  await removePost(str(formData, "slug"));
  revalidatePath("/", "layout");
}

export async function uploadPostPhotos(slug: string, photos: string[]) {
  await requireAdmin();
  const safe = photos.filter((p) => typeof p === "string" && p.startsWith("data:image/") && p.length < 1_400_000);
  const urls = await saveUploads(safe.slice(0, 8), `ratgeber/${slug}`);
  await addPostPhotos(slug, urls);
  revalidatePath("/", "layout");
  return { ok: true, added: safe.length };
}

export async function deletePostPhoto(formData: FormData) {
  await requireAdmin();
  await removePostPhoto(str(formData, "slug"), Number(formData.get("index")));
  revalidatePath("/", "layout");
}
