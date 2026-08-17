import photosData from "./photos.json";

type Entry = { id: string; url: string; alt: string; by: string; ratio: number };
type Bucket = "couple" | "ceremony" | "party" | "details" | "venue" | "prep" | "portrait";

const photos = photosData as Record<Bucket, Entry[]>;
const BUCKETS: Bucket[] = ["couple", "ceremony", "party", "details", "venue", "prep", "portrait"];

/**
 * Bildquelle der Demo.
 *
 * Für die Live-Version wird ausschließlich diese Datei getauscht: Die Funktion
 * `img()` liefert dann die Pfade der eigenen Aufnahmen (z. B. aus /public oder
 * Vercel Blob). Alle Komponenten bleiben unverändert.
 */

function hash(s: string) {
  let h = 2166136261;
  for (let i = 0; i < s.length; i++) {
    h ^= s.charCodeAt(i);
    h = Math.imul(h, 16777619);
  }
  return Math.abs(h);
}

/** Thematische Zuordnung: welche Bildwelt passt zu welchem Platz auf der Seite? */
function bucketFor(seed: string): Bucket {
  if (seed.startsWith("venue-") || seed === "venues-index") return "venue";
  if (seed === "about-portrait") return "portrait";
  if (seed.startsWith("lum-service-")) {
    if (seed.includes("video")) return "party";
    if (seed.includes("standesamt")) return "prep";
    if (seed.includes("after")) return "couple";
    return "ceremony";
  }
  // Beispielstrecke unter einer Leistung: svc-<anker>-<nummer>. Die Bildwelt
  // richtet sich nach der Leistung, die Nummer sorgt fuer Abwechslung.
  const svc = /^svc-(.+)-(\d+)$/.exec(seed);
  if (svc) {
    const [, name, n] = svc;
    const idx = Number(n);
    if (name.includes("video") || name.includes("film")) return (["party", "couple", "ceremony", "prep"] as Bucket[])[idx % 4];
    if (name.includes("standesamt")) return (["prep", "ceremony", "portrait", "details"] as Bucket[])[idx % 4];
    if (name.includes("after")) return (["couple", "portrait", "venue", "couple"] as Bucket[])[idx % 4];
    return (["ceremony", "couple", "details", "party"] as Bucket[])[idx % 4];
  }

  if (seed === "lumiere-tool-gallery") return "details";
  if (seed === "lumiere-tool-invite") return "details";
  if (seed === "invite-hero" || seed === "prices-hero") return "details";
  if (seed === "gallery-hero") return "prep";
  if (seed === "services-hero" || seed === "contact-hero") return "ceremony";

  // Galerien und Reportagen: über alle Bildwelten mischen
  const m = /-(\d+)$/.exec(seed);
  if (m && (seed.startsWith("gal-") || seed.startsWith("story-"))) {
    const order: Bucket[] = ["couple", "prep", "ceremony", "details", "party", "venue"];
    return order[Number(m[1]) % order.length];
  }

  return "couple";
}

function pick(seed: string): Entry | null {
  const bucket = bucketFor(seed);
  const list = photos[bucket]?.length ? photos[bucket] : photos.couple;
  if (!list?.length) return null;
  return list[hash(seed) % list.length];
}

export function img(seed: string, w = 1200, h = 1600) {
  // Hochgeladene Bilder (Vercel Blob) sind bereits fertige Quellen.
  if (/^(https?:|data:|\/)/.test(seed)) return seed;

  const entry = pick(seed);
  if (!entry) return `https://picsum.photos/seed/${encodeURIComponent(seed)}/${w}/${h}`;
  const params = new URLSearchParams({
    w: String(w),
    h: String(h),
    fit: "crop",
    crop: "faces,entropy",
    q: "72",
    fm: "jpg",
    auto: "format",
  });
  return `${entry.url}?${params.toString()}`;
}

/** Alternativtext aus den Bilddaten – für den Fall, dass kein eigener gesetzt ist. */
export function imgAlt(seed: string, fallback: string) {
  const entry = pick(seed);
  return entry?.alt?.trim() ? entry.alt : fallback;
}

/** Fotografennachweis (Unsplash) – nur für die Demo-Version relevant. */
export function credits() {
  return Array.from(new Set(BUCKETS.flatMap((b) => (photos[b] ?? []).map((e) => e.by)).filter(Boolean))).sort();
}

export const blurData =
  "data:image/svg+xml;base64,PHN2ZyB4bWxucz0iaHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmciIHdpZHRoPSIyMCIgaGVpZ2h0PSIyNiI+PHJlY3Qgd2lkdGg9IjIwIiBoZWlnaHQ9IjI2IiBmaWxsPSIjZWRlNGQ4Ii8+PC9zdmc+";
