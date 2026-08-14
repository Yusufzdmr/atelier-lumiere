import { NextResponse } from "next/server";
import { cookies } from "next/headers";
import { getGallery, saveSelection } from "@/lib/store";

/** Speichert die Album-Auswahl des Paares. */
export async function POST(req: Request) {
  try {
    const { code, couple, picks, note } = await req.json();
    const gallery = await getGallery(String(code ?? ""));
    if (!gallery) return NextResponse.json({ error: "unknown-gallery" }, { status: 404 });

    const jar = await cookies();
    if (jar.get(`al-gal-${gallery.code}`)?.value !== "1") {
      return NextResponse.json({ error: "unauthorized" }, { status: 401 });
    }

    const selection = {
      code: gallery.code,
      couple: String(couple ?? gallery.couple),
      picks: Array.isArray(picks) ? picks.slice(0, 500).map(Number).filter((n) => Number.isInteger(n)) : [],
      note: note ? String(note).slice(0, 1000) : undefined,
      at: new Date().toISOString(),
    };

    await saveSelection(selection);
    console.log("[Album-Auswahl]", selection.code, selection.picks.length);

    return NextResponse.json({ ok: true, count: selection.picks.length });
  } catch {
    return NextResponse.json({ error: "bad-request" }, { status: 400 });
  }
}
