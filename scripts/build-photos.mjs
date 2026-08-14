/**
 * Baut lib/photos.json aus den Rohdaten in scripts/.raw/.
 * Hochformat-Bilder werden bevorzugt, weil die Seite überwiegend
 * Portraitformate verwendet.
 */
import { readdirSync, readFileSync, writeFileSync } from "node:fs";

const RAW = "scripts/.raw";
const out = {};
const seen = new Set();

for (const file of readdirSync(RAW)) {
  if (!file.endsWith(".json")) continue;
  const bucket = file.split("__")[0];
  let json;
  try {
    json = JSON.parse(readFileSync(`${RAW}/${file}`, "utf8"));
  } catch {
    console.warn("skip (kein JSON):", file);
    continue;
  }
  out[bucket] ??= [];
  for (const r of json.results ?? []) {
    if (!r?.urls?.raw || seen.has(r.id)) continue;
    // Kein KI-generiertes Material und keine reinen Illustrationen
    if (r.ai_generated) continue;
    // Unsplash+ (kostenpflichtig) ausschliessen
    if (!r.urls.raw.startsWith("https://images.unsplash.com/")) continue;
    seen.add(r.id);
    out[bucket].push({
      id: r.id,
      url: r.urls.raw.split("?")[0],
      alt: (r.alt_description || r.description || "").replace(/\s+/g, " ").trim().slice(0, 120),
      by: r.user?.name ?? "",
      ratio: Number((r.width / r.height).toFixed(3)),
    });
  }
}

// Hochformat zuerst – passt besser zu den Bildflächen der Seite
for (const bucket of Object.keys(out)) {
  out[bucket].sort((a, b) => a.ratio - b.ratio);
  console.log(bucket.padEnd(10), out[bucket].length);
}

writeFileSync("lib/photos.json", JSON.stringify(out, null, 1));
console.log("gesamt", Object.values(out).flat().length);
