import { NextResponse } from "next/server";
import { addRsvp } from "@/lib/store";

export async function POST(req: Request) {
  try {
    const { slug, name, coming, count, note } = await req.json();
    if (!slug || !name) return NextResponse.json({ error: "missing-fields" }, { status: 400 });

    const entry = {
      slug: String(slug).slice(0, 80),
      name: String(name).slice(0, 120),
      coming: Boolean(coming),
      count: Math.min(20, Math.max(0, Number(count) || 0)),
      note: note ? String(note).slice(0, 500) : undefined,
      at: new Date().toISOString(),
    };

    await addRsvp(entry);
    console.log("[RSVP]", entry.slug, entry.name, entry.coming ? "+" : "-", entry.count);

    return NextResponse.json({ ok: true });
  } catch {
    return NextResponse.json({ error: "bad-request" }, { status: 400 });
  }
}
