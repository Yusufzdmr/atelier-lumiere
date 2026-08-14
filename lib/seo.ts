import type { Metadata } from "next";
import { site } from "./site";
import { img } from "./images";
import { getContent, getCities } from "./cms";
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
