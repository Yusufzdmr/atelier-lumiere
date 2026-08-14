/**
 * Holt kuratierte Platzhalter-Bilder (Unsplash) und schreibt lib/photos.json.
 * Im Live-Betrieb werden diese durch die eigenen Aufnahmen des Fotografen ersetzt.
 *   node scripts/fetch-photos.mjs
 */
import { writeFileSync } from "node:fs";

const BUCKETS = {
  couple: ["wedding couple portrait", "bride and groom", "wedding couple sunset"],
  ceremony: ["wedding ceremony", "wedding vows", "wedding aisle"],
  party: ["wedding reception party", "wedding first dance", "wedding celebration guests"],
  details: ["wedding rings detail", "bridal bouquet", "wedding table decoration"],
  venue: ["castle wedding venue", "wedding hall interior", "wedding venue garden"],
  prep: ["bride getting ready", "wedding dress hanging", "groom suit detail"],
  portrait: ["photographer portrait studio", "wedding photographer working"],
};

const seen = new Set();

const UA = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0 Safari/537.36';

async function fetchRetry(url, tries = 5) {
  for (let i = 0; i < tries; i++) {
    const res = await fetch(url, {
      headers: { Accept: 'application/json', 'User-Agent': UA, Referer: 'https://unsplash.com/s/photos/wedding', 'Accept-Language': 'en-US,en;q=0.9' },
    });
    if (res.ok) return res;
    await new Promise((r) => setTimeout(r, 2500 * (i + 1)));
  }
  return { ok: false, status: 429, json: async () => ({ results: [] }) };
}

async function search(query, perPage = 24) {
  const url = `https://unsplash.com/napi/search/photos?query=${encodeURIComponent(query)}&per_page=${perPage}`;
  const res = await fetchRetry(url);
  if (!res.ok) { console.log("skip", query); return []; }
  const json = await res.json();
  return json.results ?? [];
}

const out = {};

for (const [bucket, queries] of Object.entries(BUCKETS)) {
  out[bucket] = [];
  for (const q of queries) {
    const results = await search(q);
    for (const r of results) {
      if (seen.has(r.id)) continue;
      if (!r.urls?.raw) continue;
      seen.add(r.id);
      out[bucket].push({
        id: r.id,
        url: r.urls.raw.split("?")[0],
        alt: (r.alt_description || r.description || "").slice(0, 120),
        by: r.user?.name ?? "",
        ratio: r.width / r.height,
      });
    }
    await new Promise((r) => setTimeout(r, 1500));
  }
  console.log(bucket, out[bucket].length);
}

writeFileSync("lib/photos.json", JSON.stringify(out, null, 1));
console.log("total", Object.values(out).flat().length);
