import type { Metadata } from "next";
import { site } from "./site";
import { img } from "./images";
import { getContent, getCities, getMarketing, getSeoEntry } from "./cms";
import { seoPageByKey, renderTemplate, type SeoTemplates } from "./marketing";
import { locales, localeMeta, type Locale } from "./i18n";

/** Kanonische URL + hreflang-Alternates für jede Seite. */
export function meta(opts: {
  locale: Locale;
  path: string; // ohne Locale-Präfix, z. B. "/preise"
  title: string;
  description: string;
  image?: string;
  noindex?: boolean;
}): Metadata {
  const { locale, path, title, description, image, noindex } = opts;
  const clean = path === "/" ? "" : path;
  const url = `${site.url}/${locale}${clean}`;

  const languages: Record<string, string> = {};
  for (const l of locales) languages[localeMeta[l].htmlLang] = `${site.url}/${l}${clean}`;
  languages["x-default"] = `${site.url}/de${clean}`;

  return {
    title,
    description,
    alternates: { canonical: url, languages },
    openGraph: {
      title,
      description,
      url,
      siteName: site.name,
      locale: localeMeta[locale].ogLocale,
      type: "website",
      images: [{ url: image ?? img("lumiere-hero-main", 1200, 630), width: 1200, height: 630, alt: title }],
    },
    twitter: { card: "summary_large_image", title, description },
    robots: noindex ? { index: false, follow: false } : undefined,
  };
}

/**
 * Metadaten einer festen Seite: Was im Admin steht, gewinnt. Ist das Feld dort
 * leer, gilt der Wert, den die Seite selbst mitbringt (z. B. der Vorspann eines
 * Beitrags) – so bleibt jede Seite auch ohne Pflege vollstaendig.
 */
export async function pageMeta(opts: {
  locale: Locale;
  /** Schluessel aus lib/marketing.ts */
  page: string;
  fallback?: { title?: string; description?: string; image?: string };
  noindex?: boolean;
}): Promise<Metadata> {
  const [entry, marketing] = await Promise.all([getSeoEntry(opts.page), getMarketing()]);
  const def = seoPageByKey(opts.page);

  return meta({
    locale: opts.locale,
    path: def?.path ?? "/",
    title: entry.title[opts.locale]?.trim() || opts.fallback?.title || site.name,
    description: entry.description[opts.locale]?.trim() || opts.fallback?.description || "",
    image: entry.image || opts.fallback?.image || marketing.defaultImage || undefined,
    noindex: entry.noindex || opts.noindex,
  });
}

/**
 * Metadaten der Seiten, die es viele Male gibt (Stadt, Location, Beitrag,
 * Reportage). Der Titel kommt aus einer Vorlage im Admin; Platzhalter wie
 * {name} werden ersetzt. Leere Vorlage = eingebauter Titel.
 */
export async function templateMeta(opts: {
  locale: Locale;
  kind: keyof SeoTemplates;
  path: string;
  vars: Record<string, string>;
  /** Titel, falls keine Vorlage gepflegt ist */
  title: string;
  description: string;
  image?: string;
  noindex?: boolean;
}): Promise<Metadata> {
  const marketing = await getMarketing();
  const template = marketing.templates?.[opts.kind]?.[opts.locale]?.trim();

  return meta({
    locale: opts.locale,
    path: opts.path,
    title: template ? renderTemplate(template, opts.vars) : opts.title,
    description: opts.description,
    image: opts.image || marketing.defaultImage || undefined,
    noindex: opts.noindex,
  });
}

/* --------------------------- JSON-LD Bausteine --------------------------- */

export async function localBusinessLd(locale: Locale) {
  // Anschrift, Telefon und Bewertung kommen aus dem Admin – sonst zeigt
  // Google noch die alte Adresse, wenn der Betrieb umzieht.
  const { contact, stats } = await getContent();

  return {
    "@context": "https://schema.org",
    "@type": ["LocalBusiness", "Photograph"],
    "@id": `${site.url}/#business`,
    name: site.name,
    legalName: site.legalName,
    url: `${site.url}/${locale}`,
    telephone: contact.phone,
    email: contact.email,
    image: img("lumiere-hero-main", 1200, 630),
    priceRange: site.priceRange,
    foundingDate: site.founded,
    address: {
      "@type": "PostalAddress",
      streetAddress: contact.street,
      postalCode: contact.zip,
      addressLocality: contact.city,
      addressRegion: site.region,
      addressCountry: site.country,
    },
    geo: { "@type": "GeoCoordinates", latitude: site.geo.lat, longitude: site.geo.lng },
    // Einzugsgebiet = die im Admin gepflegten Stadtseiten
    areaServed: (await getCities()).map((c) => c.name),
    knowsLanguage: ["de", "tr", "en"],
    sameAs: [contact.instagram, site.vimeo, site.googleProfile],
    aggregateRating: {
      "@type": "AggregateRating",
      ratingValue: stats.rating.replace(",", ".").replace(/[^\d.]/g, "") || "4.9",
      reviewCount: "87",
    },
    openingHoursSpecification: [
      {
        "@type": "OpeningHoursSpecification",
        dayOfWeek: ["Monday", "Tuesday", "Wednesday", "Thursday", "Friday"],
        opens: "10:00",
        closes: "18:00",
      },
    ],
  };
}

export function breadcrumbLd(locale: Locale, items: { name: string; path: string }[]) {
  return {
    "@context": "https://schema.org",
    "@type": "BreadcrumbList",
    itemListElement: items.map((it, i) => ({
      "@type": "ListItem",
      position: i + 1,
      name: it.name,
      item: `${site.url}/${locale}${it.path === "/" ? "" : it.path}`,
    })),
  };
}

export function faqLd(items: { q: string; a: string }[]) {
  return {
    "@context": "https://schema.org",
    "@type": "FAQPage",
    mainEntity: items.map((f) => ({
      "@type": "Question",
      name: f.q,
      acceptedAnswer: { "@type": "Answer", text: f.a },
    })),
  };
}

export function serviceLd(opts: { locale: Locale; name: string; description: string; area: string; path: string }) {
  return {
    "@context": "https://schema.org",
    "@type": "Service",
    serviceType: opts.name,
    description: opts.description,
    provider: { "@id": `${site.url}/#business` },
    areaServed: { "@type": "City", name: opts.area },
    url: `${site.url}/${opts.locale}${opts.path}`,
  };
}

export function offerLd(opts: { name: string; price: string; description: string }) {
  return {
    "@context": "https://schema.org",
    "@type": "Offer",
    name: opts.name,
    price: opts.price.replace(/[^\d]/g, ""),
    priceCurrency: "EUR",
    description: opts.description,
    seller: { "@id": `${site.url}/#business` },
    availability: "https://schema.org/InStock",
  };
}

export function articleLd(opts: {
  locale: Locale;
  title: string;
  description: string;
  path: string;
  image: string;
  /** Veroeffentlichungsdatum (ISO) – nur fuer Ratgeber-Beitraege */
  published?: string;
}) {
  return {
    "@context": "https://schema.org",
    "@type": opts.published ? "BlogPosting" : "Article",
    headline: opts.title,
    description: opts.description,
    image: opts.image,
    ...(opts.published ? { datePublished: opts.published, dateModified: opts.published } : {}),
    inLanguage: opts.locale,
    author: { "@type": "Person", name: site.owner },
    publisher: { "@id": `${site.url}/#business` },
    mainEntityOfPage: `${site.url}/${opts.locale}${opts.path}`,
  };
}
