/**
 * Exportiert Wörterbuch und Datenbestand für die PHP-Fassung.
 *
 *   node scripts/export-to-php.mjs          alles
 *   node scripts/export-to-php.mjs dict     nur die Texte
 *   node scripts/export-to-php.mjs data     nur die Datenbank
 *
 * Warum: Die deutschen und türkischen Texte sind über tausend Zeilen. Sie
 * abzutippen wäre die teuerste und fehleranfälligste Art, sie zu übernehmen.
 * Zugangsdaten (Integrationen) werden bewusst NICHT exportiert – die trägt
 * der Betrieb im neuen Adminbereich einmal neu ein.
 */
import { readFileSync, writeFileSync, mkdirSync } from "node:fs";
import { neon } from "@neondatabase/serverless";

const ROOT = new URL("..", import.meta.url).pathname.replace(/^\/([A-Za-z]:)/, "$1");
const OUT = `${ROOT}php/data`;
mkdirSync(OUT, { recursive: true });

const what = process.argv[2] ?? "all";

/* ----------------------------- Wörterbuch ----------------------------- */

function phpValue(value, indent = 1) {
  const pad = "    ".repeat(indent);
  const padEnd = "    ".repeat(indent - 1);

  if (value === null || value === undefined) return "null";
  if (typeof value === "boolean") return value ? "true" : "false";
  if (typeof value === "number") return String(value);
  if (typeof value === "string") return "'" + value.replace(/\\/g, "\\\\").replace(/'/g, "\\'") + "'";

  if (Array.isArray(value)) {
    if (value.length === 0) return "[]";
    const items = value.map((v) => `${pad}${phpValue(v, indent + 1)},`).join("\n");
    return `[\n${items}\n${padEnd}]`;
  }

  const entries = Object.entries(value);
  if (entries.length === 0) return "[]";
  const items = entries
    .map(([k, v]) => `${pad}'${k.replace(/'/g, "\\'")}' => ${phpValue(v, indent + 1)},`)
    .join("\n");
  return `[\n${items}\n${padEnd}]`;
}

async function exportDict() {
  // Node 24 entfernt die Typen selbst; dict.ts ist reines Datenobjekt.
  const mod = await import(`file:///${ROOT}lib/dict.ts`);
  const dictionaries = mod.dictionaries;

  const php = `<?php
/**
 * Erzeugt von scripts/export-to-php.mjs – nicht von Hand bearbeiten.
 * Quelle: lib/dict.ts (Next.js-Fassung)
 */

return ${phpValue(dictionaries, 1)};
`;

  writeFileSync(`${OUT}/dict.php`, php, "utf8");
  const count = (obj) => Object.values(obj).reduce((n, v) => n + (typeof v === "object" && v !== null ? count(v) : 1), 0);
  console.log(`dict.php geschrieben – ${count(dictionaries)} Texte`);
}

/* ------------------------------- Daten ------------------------------- */

async function exportThemes() {
  const mod = await import(`file:///${ROOT}lib/themes.ts`);
  const php = `<?php
/**
 * Erzeugt von scripts/export-to-php.mjs – Startbelegung der Einladungsthemen.
 * Ab der ersten Änderung im Admin gilt der Stand aus der Datenbank.
 */

return ${phpValue(mod.themes, 1)};
`;
  writeFileSync(`${OUT}/themes.php`, php, "utf8");
  console.log(`themes.php geschrieben – ${mod.themes.length} Themen`);
}

async function exportData() {
  const env = readFileSync(`${ROOT}.env.local`, "utf8");
  const url = env.match(/^DATABASE_URL=(.+)$/m)?.[1]?.trim().replace(/^["']|["']$/g, "");
  if (!url) throw new Error("DATABASE_URL fehlt in .env.local");

  const sql = neon(url);
  const rows = async (q) => {
    try {
      return await q;
    } catch (err) {
      console.warn("  übersprungen:", err.message);
      return [];
    }
  };

  const [content] = await rows(sql`SELECT data FROM site_content WHERE id = 1`);
  const galleries = await rows(sql`SELECT code, data FROM galleries ORDER BY created_at`);
  const customers = await rows(sql`SELECT code, data FROM customers ORDER BY created_at`);
  const invitations = await rows(sql`SELECT slug, data FROM invitations ORDER BY created_at`);
  const selections = await rows(sql`SELECT code, data FROM selections`);
  const rsvps = await rows(sql`SELECT slug, data, at FROM rsvps ORDER BY at`);
  const leads = await rows(sql`SELECT data, at FROM leads ORDER BY at`);

  const dump = {
    exportedAt: new Date().toISOString(),
    content: content?.data ?? null,
    galleries,
    customers,
    invitations,
    selections,
    rsvps,
    leads,
  };

  writeFileSync(`${OUT}/export.json`, JSON.stringify(dump, null, 2), "utf8");
  console.log(
    `export.json geschrieben – ${galleries.length} Galerien, ${customers.length} Kunden, ` +
      `${invitations.length} Einladungen, ${rsvps.length} Zusagen, ${leads.length} Anfragen`
  );
  console.log("Hinweis: Zugangsdaten (PayPal, Google, Meta) sind bewusst nicht enthalten.");
}

if (what === "all" || what === "dict") await exportDict();
if (what === "all" || what === "dict") await exportThemes();
if (what === "all" || what === "data") await exportData();
