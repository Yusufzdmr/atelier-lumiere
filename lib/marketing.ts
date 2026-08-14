import type { L } from "./i18n";

/**
 * SEO-Metadaten und Marketing-Kennungen – alles im Admin pflegbar.
 *
 * Warum hier und nicht in den Seiten: Titel und Beschreibung sind das, was in
 * Google steht. Wer die anpassen will, soll keinen Entwickler brauchen. Die
 * Werte unten sind nur die Startbelegung; ab dem ersten Speichern gilt der
 * Stand aus der Datenbank (siehe lib/cms.ts).
 */

/** Ein Eintrag pro Seite. Leerer Titel/Text = die Seite entscheidet selbst. */
export type SeoEntry = {
  title: L;
  description: L;
  /** Aus dem Google-Index nehmen (und aus der Sitemap). */
  noindex: boolean;
  /** Eigenes Vorschaubild fuer WhatsApp, Facebook, X. Leer = Standardbild. */
  image: string;
};

/**
 * Titelvorlagen fuer die Seiten, die es viele Male gibt. Platzhalter in
 * geschweiften Klammern werden ersetzt; leere Vorlage = eingebauter Titel.
 */
export type SeoTemplates = {
  /** {name} = Stadtname */
  city: L;
  /** {name} = Name der Location */
  venue: L;
  /** {title} = Beitragstitel */
  post: L;
  /** {couple} = Brautpaar, {venue} = Location */
  story: L;
};

/**
 * Kennungen fuer Analytics, Ads und Pixel liegen bewusst nicht hier, sondern
 * unter „Integrationen“ (lib/integrations.ts) – zusammen mit den Zugangsdaten
 * der uebrigen Dienste. Hier geht es nur um das, was in Google zu lesen ist.
 */
export type MarketingContent = {
  pages: Record<string, SeoEntry>;
  templates: SeoTemplates;
  /** Vorschaubild, wenn eine Seite keins hat */
  defaultImage: string;
};

type PageDef = {
  key: string;
  /** Pfad ohne Sprachpraefix */
  path: string;
  label: L;
  /** Startbelegung; leer = die Seite setzt den Wert selbst (z. B. aus dem Beitrag) */
  title?: L;
  description?: L;
  /** Der Text kommt aus einem anderen Admin-Bereich, wenn hier nichts steht */
  auto?: L;
  noindex?: boolean;
};

/**
 * Reihenfolge = Reihenfolge im Admin. Wer eine neue Seite anlegt, traegt sie
 * hier ein – Admin-Formular, Sitemap und Metadaten lesen alle diese Liste.
 */
export const seoPages: PageDef[] = [
  {
    key: "home",
    path: "/",
    label: { de: "Startseite", tr: "Ana sayfa" },
    title: {
      de: "Hochzeitsfotograf Stuttgart – Foto & Video | Atelier Lumière",
      tr: "Stuttgart Düğün Fotoğrafçısı – Foto & Video | Atelier Lumière",
    },
    description: {
      de: "Dokumentarische Hochzeitsfotografie und Hochzeitsfilm in Stuttgart, Ludwigsburg, Esslingen und Umgebung. Private Kundengalerie mit Album-Auswahl und digitale Hochzeitseinladung inklusive.",
      tr: "Stuttgart, Ludwigsburg, Esslingen ve çevresinde belgesel tarzda düğün fotoğrafçılığı ve düğün filmi. Albüm seçimli özel müşteri galerisi ve dijital düğün davetiyesi dahil.",
    },
  },
  {
    key: "leistungen",
    path: "/leistungen",
    label: { de: "Leistungen", tr: "Hizmetler" },
    title: {
      de: "Leistungen – Hochzeitsfotografie & Hochzeitsfilm",
      tr: "Hizmetler – düğün fotoğrafçılığı & düğün filmi",
    },
    description: {
      de: "Hochzeitsreportage, Hochzeitsfilm in 4K, Standesamt, Henna-Abend und After-Wedding-Shooting in Stuttgart und Umgebung.",
      tr: "Stuttgart ve çevresinde düğün çekimi, 4K düğün filmi, nikah, kına gecesi ve after-wedding çekimi.",
    },
  },
  {
    key: "preise",
    path: "/preise",
    label: { de: "Preise", tr: "Fiyatlar" },
    title: {
      de: "Preise Hochzeitsfotograf Stuttgart – ab 690 €",
      tr: "Stuttgart düğün fotoğrafçısı fiyatları – 690 €'dan",
    },
    description: {
      de: "Transparente Pakete: Standesamt ab 690 €, Ganztagesreportage ab 1.890 €, Foto & Film ab 3.490 €. Anfahrt bis 60 km inklusive.",
      tr: "Şeffaf paketler: nikah 690 €'dan, tam gün çekim 1.890 €'dan, foto & film 3.490 €'dan. 60 km'ye kadar ulaşım dahil.",
    },
  },
  {
    key: "portfolio",
    path: "/portfolio",
    label: { de: "Portfolio", tr: "Portfolyo" },
    title: {
      de: "Portfolio – echte Hochzeiten aus Stuttgart & Region",
      tr: "Portfolyo – Stuttgart ve bölgeden gerçek düğünler",
    },
    description: {
      de: "Vier Hochzeitsreportagen aus Stuttgart, Ludwigsburg und Fellbach – Schloss, Kelter und große Eventhalle.",
      tr: "Stuttgart, Ludwigsburg ve Fellbach'tan dört düğün çekimi – saray, şaraphane ve büyük etkinlik salonu.",
    },
  },
  {
    key: "hochzeitslocations",
    path: "/hochzeitslocations",
    label: { de: "Locations (Übersicht)", tr: "Mekânlar (liste)" },
    title: {
      de: "Hochzeitslocations Stuttgart & Region – Fotograf mit Ortskenntnis",
      tr: "Stuttgart ve çevresi düğün mekânları – mekânı tanıyan fotoğrafçı",
    },
    description: {
      de: "Schloss, Festhalle, Eventlocation: Für die beliebtesten Hochzeitslocations der Region Stuttgart kennen wir Licht, Zeitplan und Hausregeln.",
      tr: "Saray, düğün salonu, etkinlik mekânı: Stuttgart bölgesinin en çok tercih edilen düğün mekânlarında ışığı, zaman planını ve kuralları biliyoruz.",
    },
  },
  {
    key: "regionen",
    path: "/regionen",
    label: { de: "Regionen", tr: "Bölgeler" },
    title: {
      de: "Hochzeitsfotograf in Stuttgart & Umgebung – alle Regionen",
      tr: "Stuttgart ve çevresinde düğün fotoğrafçısı – tüm bölgeler",
    },
    description: {
      de: "Stuttgart, Ludwigsburg, Esslingen, Böblingen, Heilbronn, Tübingen und mehr: Hochzeitsfotograf und Videograf mit Ortskenntnis – Anfahrt bis 60 km inklusive.",
      tr: "Stuttgart, Ludwigsburg, Esslingen, Böblingen, Heilbronn, Tübingen ve daha fazlası: bölgeyi tanıyan düğün fotoğrafçısı ve videografı – 60 km'ye kadar ulaşım dahil.",
    },
  },
  {
    key: "ratgeber",
    path: "/ratgeber",
    label: { de: "Ratgeber (Übersicht)", tr: "Rehber (liste)" },
    title: {
      de: "Ratgeber – Hochzeitsfotografie, Zeitplan & Locations",
      tr: "Rehber – düğün fotoğrafçılığı, akış planı ve mekânlar",
    },
    auto: { de: "Text aus dem Ratgeber-Vorspann", tr: "Metin rehber girişinden" },
  },
  {
    key: "ueber-mich",
    path: "/ueber-mich",
    label: { de: "Über mich", tr: "Hakkımda" },
    auto: {
      de: 'Titel und Text aus "Über mich & Stimmen"',
      tr: '"Hakkımda & yorumlar" bölümünden',
    },
  },
  {
    key: "kontakt",
    path: "/kontakt",
    label: { de: "Kontakt", tr: "İletişim" },
    title: {
      de: "Kontakt – Hochzeitsfotograf Stuttgart anfragen",
      tr: "İletişim – Stuttgart düğün fotoğrafçısı",
    },
    description: {
      de: "Fragt euer Hochzeitsdatum an. Antwort in der Regel innerhalb von 24 Stunden. Atelier in Stuttgart-Mitte.",
      tr: "Düğün tarihinizi sorun. Genelde 24 saat içinde yanıt. Atölye Stuttgart merkezde.",
    },
  },
  {
    key: "einladung",
    path: "/einladung",
    label: { de: "Digitale Einladung", tr: "Dijital davetiye" },
    title: {
      de: "Digitale Hochzeitseinladung erstellen – mit RSVP & Countdown",
      tr: "Dijital düğün davetiyesi oluştur – RSVP ve geri sayımlı",
    },
    description: {
      de: "Eigene Einladungsseite in drei Minuten: Countdown, Google-Maps-Route, WhatsApp-Versand und Zusagen. Für Hochzeitspaare von Atelier Lumière kostenlos.",
      tr: "Üç dakikada kendi davetiye sayfanız: geri sayım, Google Maps yol tarifi, WhatsApp paylaşımı ve katılım bildirimi. Atelier Lumière çiftlerine ücretsiz.",
    },
  },
  {
    key: "galerie",
    path: "/galerie",
    label: { de: "Kundengalerie (Login)", tr: "Müşteri galerisi (giriş)" },
    title: { de: "Kundengalerie – Login", tr: "Müşteri galerisi – giriş" },
    auto: { de: "Text aus dem Galerie-Vorspann", tr: "Metin galeri girişinden" },
    noindex: true,
  },
  {
    key: "impressum",
    path: "/impressum",
    label: { de: "Impressum", tr: "Impressum" },
    auto: { de: 'Titel aus "Rechtstexte"', tr: '"Yasal metinler" bölümünden' },
  },
  {
    key: "datenschutz",
    path: "/datenschutz",
    label: { de: "Datenschutz", tr: "Gizlilik" },
    auto: { de: 'Titel aus "Rechtstexte"', tr: '"Yasal metinler" bölümünden' },
  },
  {
    key: "agb",
    path: "/agb",
    label: { de: "AGB", tr: "AGB" },
    auto: { de: 'Titel aus "Rechtstexte"', tr: '"Yasal metinler" bölümünden' },
  },
];

export const seoPageByKey = (key: string) => seoPages.find((p) => p.key === key);

const empty = (): L => ({ de: "", tr: "" });

export const defaultMarketing = (): MarketingContent => ({
  pages: Object.fromEntries(
    seoPages.map((p) => [
      p.key,
      {
        title: p.title ? { ...p.title } : empty(),
        description: p.description ? { ...p.description } : empty(),
        noindex: Boolean(p.noindex),
        image: "",
      },
    ])
  ),
  templates: {
    city: {
      de: "Hochzeitsfotograf {name} – Foto & Video ab 690 €",
      tr: "{name} Düğün Fotoğrafçısı – Foto & Video 690 €'dan",
    },
    venue: {
      de: "{name} Hochzeitsfotograf – Erfahrung vor Ort",
      tr: "{name} düğün fotoğrafçısı – mekân tecrübesi",
    },
    post: { de: "{title}", tr: "{title}" },
    story: { de: "{couple} – {venue}", tr: "{couple} – {venue}" },
  },
  defaultImage: "",
});

/** {name} → Wert. Unbekannte Platzhalter bleiben stehen, damit der Fehler sichtbar ist. */
export function renderTemplate(template: string, vars: Record<string, string>) {
  return template.replace(/\{(\w+)\}/g, (all, key: string) => vars[key] ?? all);
}

/** Google zeigt etwa 60 Zeichen Titel und 160 Zeichen Beschreibung. */
export const seoLimits = {
  title: { min: 30, max: 60 },
  description: { min: 70, max: 160 },
} as const;
