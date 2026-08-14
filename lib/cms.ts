import { loadContent, storeContent, clearContent } from "./store";
import { deleteUpload } from "./media";
import { packages as defaultPackages, addons as defaultAddons } from "./content";
import { venues as defaultVenues, type Venue } from "./venues";
import { cities as defaultCities, type City } from "./cities";
import { stories as defaultStories, type Story } from "./content";
import {
  services as defaultServices,
  processSteps as defaultProcess,
  testimonials as defaultTestimonials,
  faqGeneral as defaultFaq,
  type Service,
  type ProcessStep,
  type Testimonial,
  type FaqItem,
} from "./content";
import { about as defaultAbout, type AboutContent } from "./about";
import { legal as defaultLegal, type LegalContent } from "./legal";
import { posts as defaultPosts, type Post } from "./posts";
import { defaultMarketing, seoPages, type MarketingContent, type SeoEntry } from "./marketing";
import { site } from "./site";
import type { L } from "./i18n";

/**
 * Redaktionell änderbare Inhalte.
 *
 * Alles hier lässt sich im Admin-Bereich ohne Code bearbeiten. Die Defaults
 * stammen aus den Content-Dateien; Änderungen liegen als Overlay im Store
 * (Live-Version: Datenbank bzw. Headless-CMS).
 */

export type PackageContent = {
  slug: string;
  name: L;
  price: string;
  hint: L;
  features: L<string[]>;
  featured?: boolean;
};

export type SiteContent = {
  hero: { title: L; text: L; eyebrow: L };
  stats: { weddings: string; years: string; delivery: string; rating: string };
  contact: {
    phone: string;
    phoneHuman: string;
    email: string;
    street: string;
    zip: string;
    city: string;
    hours: L;
    instagram: string;
    /** Optionale Abweichung fuer die Karte (Adresse, Ortsname oder "lat,lng"). Leer = Anschrift oben. */
    mapsQuery: string;
  };
  packages: PackageContent[];
  addons: { name: L; price: string }[];
  /** Hochzeitslocations – im Admin bearbeitbar */
  venues: Venue[];
  /** Stadt-/Regionsseiten */
  cities: City[];
  /** Referenzreportagen */
  stories: Story[];
  /** Leistungen (/leistungen + Startseite) */
  services: Service[];
  /** Ablauf in vier Schritten (Startseite) */
  process: ProcessStep[];
  /** Kundenstimmen (Startseite) */
  testimonials: Testimonial[];
  /** Allgemeine FAQ (Startseite + /preise) */
  faq: FaqItem[];
  /** Seite "Über mich" */
  about: AboutContent;
  /** Impressum, Datenschutz, AGB */
  legal: LegalContent;
  /** Ratgeber-Beiträge */
  posts: Post[];
  /** Seitentitel und Beschreibungen für Google */
  marketing: MarketingContent;
  /** Allgemeiner Aktionscode für die digitale Einladung (neben den Kundencodes) */
  campaign: { code: string; active: boolean };
};

export const defaultContent = (): SiteContent => ({
  hero: {
    eyebrow: {
      de: "Hochzeitsfotografie & Film · Stuttgart",
      tr: "Düğün Fotoğrafçılığı & Film · Stuttgart",
    },
    title: { de: "Der Tag vergeht.\nDie Bilder bleiben.", tr: "Gün geçer.\nKareler kalır." },
    text: {
      de: "Dokumentarische Hochzeitsfotografie und Film in Stuttgart und Umgebung. Ruhig gearbeitet, ehrlich erzählt – und pünktlich geliefert.",
      tr: "Stuttgart ve çevresinde belgesel tarzda düğün fotoğrafçılığı ve video. Sakin çalışılmış, dürüst anlatılmış – ve zamanında teslim edilmiş.",
    },
  },
  stats: { weddings: "180+", years: "9", delivery: "3", rating: "4,9 ★" },
  contact: {
    phone: site.phone,
    phoneHuman: site.phoneHuman,
    email: site.email,
    street: site.street,
    zip: site.zip,
    city: site.city,
    hours: { de: site.openingHours, tr: "Pzt–Cu 10:00–18:00 · Cmt randevuyla" },
    instagram: site.instagram,
    mapsQuery: "",
  },
  packages: defaultPackages.map((p) => ({ ...p })),
  addons: defaultAddons.map((a) => ({ ...a })),
  venues: structuredClone(defaultVenues),
  cities: structuredClone(defaultCities),
  stories: structuredClone(defaultStories),
  services: structuredClone(defaultServices),
  process: structuredClone(defaultProcess),
  testimonials: structuredClone(defaultTestimonials),
  faq: structuredClone(defaultFaq),
  about: structuredClone(defaultAbout),
  legal: structuredClone(defaultLegal),
  posts: structuredClone(defaultPosts),
  marketing: defaultMarketing(),
  campaign: { code: "lumiere2026", active: true },
});

/**
 * Aktueller Stand aus der Datenbank; beim allerersten Aufruf werden die
 * Standardinhalte geschrieben. Fehlende Felder aelterer Staende werden
 * nachgeruestet, damit ein Deploy mit neuen Bereichen nicht bricht.
 */
export async function getContent(): Promise<SiteContent> {
  let stored: SiteContent | null;
  try {
    stored = await loadContent();
  } catch (err) {
    // Datenbank kurzzeitig nicht erreichbar: lieber die Standardinhalte
    // ausliefern als die ganze Seite mit 500 abbrechen.
    console.error("[cms] Datenbank nicht erreichbar, nutze Standardinhalte:", err);
    return defaultContent();
  }

  if (!stored) {
    const fresh = defaultContent();
    await storeContent(fresh);
    return fresh;
  }

  const d = defaultContent();
  let patched = false;
  const fill = <K extends keyof SiteContent>(key: K) => {
    if (stored[key] === undefined || stored[key] === null) {
      stored[key] = d[key];
      patched = true;
    }
  };
  (["venues", "cities", "stories", "services", "process", "testimonials", "faq", "about", "legal", "posts", "marketing", "campaign"] as const).forEach(fill);
  if (stored.contact.mapsQuery === undefined) {
    stored.contact.mapsQuery = "";
    patched = true;
  }
  // Eine neu hinzugekommene Seite braucht ihren SEO-Eintrag, sonst faellt das
  // Admin-Formular auf undefined.
  for (const page of seoPages) {
    if (!stored.marketing.pages[page.key]) {
      stored.marketing.pages[page.key] = d.marketing.pages[page.key];
      patched = true;
    }
  }
  if (!stored.marketing.templates) {
    stored.marketing.templates = d.marketing.templates;
    patched = true;
  }
  if (stored.marketing.defaultImage === undefined) {
    stored.marketing.defaultImage = "";
    patched = true;
  }
  if (patched) await storeContent(stored);

  return stored;
}

/** Lesen, aendern, zurueckschreiben – jede Mutation unten laeuft hierueber. */
async function mutate(fn: (c: SiteContent) => void) {
  const c = await getContent();
  fn(c);
  await storeContent(c);
}

/* ------------------------------ Städte ------------------------------ */

export const getCities = async (): Promise<City[]> => (await getContent()).cities;
export const getCity = async (slug: string) => (await getCities()).find((c) => c.slug === slug);

export const saveCity = (slug: string, patch: Partial<City>) =>
  mutate((c) => {
    c.cities = c.cities.map((x) => (x.slug === slug ? { ...x, ...patch } : x));
  });

export async function addCity(city: City) {
  const c = await getContent();
  if (c.cities.some((x) => x.slug === city.slug)) return null;
  c.cities = [...c.cities, city];
  await storeContent(c);
  return city;
}

export const removeCity = (slug: string) =>
  mutate((c) => {
    c.cities = c.cities.filter((x) => x.slug !== slug);
  });

/* ---------------------------- Reportagen ---------------------------- */

export const getStories = async (): Promise<Story[]> => (await getContent()).stories;
export const getStory = async (slug: string) => (await getStories()).find((s) => s.slug === slug);

export const saveStory = (slug: string, patch: Partial<Story>) =>
  mutate((c) => {
    c.stories = c.stories.map((s) => (s.slug === slug ? { ...s, ...patch } : s));
  });

export async function addStory(story: Story) {
  const c = await getContent();
  if (c.stories.some((s) => s.slug === story.slug)) return null;
  c.stories = [...c.stories, story];
  await storeContent(c);
  return story;
}

export async function removeStory(slug: string) {
  const c = await getContent();
  const gone = c.stories.find((s) => s.slug === slug);
  c.stories = c.stories.filter((s) => s.slug !== slug);
  await storeContent(c);
  for (const url of gone?.uploads ?? []) await deleteUpload(url);
}

export const addStoryPhotos = (slug: string, photos: string[]) =>
  mutate((c) => {
    c.stories = c.stories.map((s) =>
      s.slug === slug ? { ...s, uploads: [...(s.uploads ?? []), ...photos].slice(0, 24) } : s
    );
  });

export async function removeStoryPhoto(slug: string, index: number) {
  const c = await getContent();
  const story = c.stories.find((s) => s.slug === slug);
  if (!story) return;
  const removed = (story.uploads ?? [])[index];
  story.uploads = (story.uploads ?? []).filter((_, i) => i !== index);
  await storeContent(c);
  if (removed) await deleteUpload(removed);
}

/* ------------------------- Hochzeitslocations ------------------------- */

export const getVenues = async (): Promise<Venue[]> => (await getContent()).venues;
export const getVenue = async (slug: string) => (await getVenues()).find((v) => v.slug === slug);
export const getVenuesByCity = async (citySlug: string) =>
  (await getVenues()).filter((v) => v.citySlug === citySlug);

export const saveVenue = (slug: string, patch: Partial<Venue>) =>
  mutate((c) => {
    c.venues = c.venues.map((v) => (v.slug === slug ? { ...v, ...patch } : v));
  });

export async function addVenue(v: Venue) {
  const c = await getContent();
  if (c.venues.some((x) => x.slug === v.slug)) return null;
  c.venues = [...c.venues, v];
  await storeContent(c);
  return v;
}

export const removeVenue = (slug: string) =>
  mutate((c) => {
    c.venues = c.venues.filter((v) => v.slug !== slug);
  });

/* ---------------------- Leistungen & Startseite ---------------------- */

export const getServices = async (): Promise<Service[]> => (await getContent()).services;
export const getProcess = async (): Promise<ProcessStep[]> => (await getContent()).process;
export const getTestimonials = async (): Promise<Testimonial[]> => (await getContent()).testimonials;
export const getFaq = async (): Promise<FaqItem[]> => (await getContent()).faq;

/* --------------------------- Über mich ------------------------------ */

export const getAbout = async (): Promise<AboutContent> => (await getContent()).about;

/* -------------------------- Rechtstexte ----------------------------- */

export const getLegal = async (): Promise<LegalContent> => (await getContent()).legal;

/* ---------------------------- Ratgeber ------------------------------ */

/** Neueste zuerst – die Reihenfolge steuert das Datum, nicht die Eingabe. */
export const getPosts = async (): Promise<Post[]> =>
  [...(await getContent()).posts].sort((a, b) => b.date.localeCompare(a.date));

export const getPost = async (slug: string) => (await getPosts()).find((p) => p.slug === slug);

/** Beiträge, die auf eine Stadtseite verweisen – für die interne Verlinkung. */
export const getPostsForCity = async (citySlug: string) =>
  (await getPosts()).filter((p) => p.citySlug === citySlug);

export const getPostsForVenue = async (venueSlug: string) =>
  (await getPosts()).filter((p) => p.venueSlug === venueSlug);

export const savePost = (slug: string, patch: Partial<Post>) =>
  mutate((c) => {
    c.posts = c.posts.map((p) => (p.slug === slug ? { ...p, ...patch } : p));
  });

export async function addPost(post: Post) {
  const c = await getContent();
  if (c.posts.some((p) => p.slug === post.slug)) return null;
  c.posts = [...c.posts, post];
  await storeContent(c);
  return post;
}

export async function removePost(slug: string) {
  const c = await getContent();
  const gone = c.posts.find((p) => p.slug === slug);
  c.posts = c.posts.filter((p) => p.slug !== slug);
  await storeContent(c);
  for (const url of gone?.uploads ?? []) await deleteUpload(url);
}

export const addPostPhotos = (slug: string, photos: string[]) =>
  mutate((c) => {
    c.posts = c.posts.map((p) =>
      p.slug === slug ? { ...p, uploads: [...(p.uploads ?? []), ...photos].slice(0, 8) } : p
    );
  });

export async function removePostPhoto(slug: string, index: number) {
  const c = await getContent();
  const post = c.posts.find((p) => p.slug === slug);
  if (!post) return;
  const removed = (post.uploads ?? [])[index];
  post.uploads = (post.uploads ?? []).filter((_, i) => i !== index);
  await storeContent(c);
  if (removed) await deleteUpload(removed);
}

/* ------------------------- SEO & Aktionscode ------------------------- */

export const getMarketing = async (): Promise<MarketingContent> => (await getContent()).marketing;

/** Eintrag einer Seite; unbekannter Schlüssel = leerer Eintrag statt Absturz. */
export async function getSeoEntry(key: string): Promise<SeoEntry> {
  const m = await getMarketing();
  return (
    m.pages[key] ?? { title: { de: "", tr: "" }, description: { de: "", tr: "" }, noindex: false, image: "" }
  );
}

export const getCampaign = async () => (await getContent()).campaign;

/* ---------------------------- Schreiben ----------------------------- */

export const saveContent = (next: SiteContent) => storeContent(next);

/** Alles auf die Standardinhalte zurücksetzen. */
export async function resetContent() {
  await clearContent();
  const fresh = defaultContent();
  await storeContent(fresh);
  return fresh;
}
