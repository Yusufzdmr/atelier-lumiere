export const site = {
  name: "Atelier Lumière",
  legalName: "Atelier Lumière Hochzeitsfotografie",
  tagline: "Hochzeitsfotografie & Film",
  claim: "Hochzeitsfotograf & Videograf in Stuttgart",
  url:
    process.env.NEXT_PUBLIC_SITE_URL ||
    (process.env.VERCEL_PROJECT_PRODUCTION_URL
      ? `https://${process.env.VERCEL_PROJECT_PRODUCTION_URL}`
      : "http://localhost:3000"),
  locale: "de_DE",
  founded: "2016",
  owner: "Julian Roth",
  phone: "+49 711 12345678",
  phoneHuman: "+49 711 123 456 78",
  whatsapp: "4971112345678",
  email: "hallo@atelier-lumiere.de",
  street: "Königstraße 27",
  zip: "70173",
  city: "Stuttgart",
  region: "Baden-Württemberg",
  country: "DE",
  geo: { lat: 48.7784, lng: 9.18 },
  priceRange: "€€€",
  openingHours: "Mo–Fr 10:00–18:00 · Sa nach Vereinbarung",
  instagram: "https://instagram.com/",
  googleProfile: "https://g.page/",
  vimeo: "https://vimeo.com/",
} as const;

export const nav = [
  { href: "/leistungen", label: "Leistungen" },
  { href: "/portfolio", label: "Portfolio" },
  { href: "/hochzeitslocations", label: "Locations" },
  { href: "/preise", label: "Preise" },
  { href: "/ratgeber", label: "Ratgeber" },
  { href: "/ueber-mich", label: "Über mich" },
  { href: "/kontakt", label: "Kontakt" },
] as const;

export const toolsNav = [
  { href: "/galerie", label: "Kundengalerie" },
  { href: "/einladung", label: "Digitale Einladung" },
] as const;

export const legalNav = [
  { href: "/impressum", label: "Impressum" },
  { href: "/datenschutz", label: "Datenschutz" },
  { href: "/agb", label: "AGB" },
] as const;
