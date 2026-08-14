import { NextResponse } from "next/server";
import { headers } from "next/headers";

import { checkCoupon } from "@/lib/store";
import { getCampaign } from "@/lib/cms";

/**
 * Prueft einen Freischaltcode, waehrend der Gast tippt.
 *
 * Die Antwort sagt nur ob und warum nicht – nie, zu welchem Kunden ein Code
 * gehoert. Der Preis wird dadurch nicht festgelegt; das passiert noch einmal
 * beim Anlegen der Einladung (siehe /api/einladung).
 */

/** Einfache Bremse gegen Durchprobieren. Pro Instanz, bewusst schlicht. */
const hits = new Map<string, { count: number; until: number }>();
const LIMIT = 12;
const WINDOW = 60_000;

function tooMany(ip: string) {
  const now = Date.now();
  const entry = hits.get(ip);

  if (!entry || entry.until < now) {
    hits.set(ip, { count: 1, until: now + WINDOW });
    if (hits.size > 500) {
      for (const [key, value] of hits) if (value.until < now) hits.delete(key);
    }
    return false;
  }

  entry.count += 1;
  return entry.count > LIMIT;
}

export async function POST(req: Request) {
  try {
    const h = await headers();
    const ip = (h.get("x-forwarded-for") ?? "").split(",")[0].trim() || "unknown";
    if (tooMany(ip)) {
      return NextResponse.json({ ok: false, reason: "throttled" }, { status: 429 });
    }

    const body = await req.json();
    const code = String(body?.code ?? "").trim().slice(0, 60);
    const result = await checkCoupon(code, await getCampaign());

    return NextResponse.json({ ok: result.ok, reason: result.reason });
  } catch {
    return NextResponse.json({ ok: false, reason: "failed" });
  }
}
