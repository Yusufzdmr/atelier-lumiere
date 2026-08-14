import { NextResponse } from "next/server";
import { addLead } from "@/lib/store";

/**
 * Kontaktanfrage.
 * Demo: Speicherung im Server-Store + Log.
 * Live: Versand über Resend/Postmark (EU-Region) + Eintrag in die Datenbank.
 */
export async function POST(req: Request) {
  try {
    const body = await req.json();

    // Honeypot – Bots füllen dieses Feld aus
    if (body.website) return NextResponse.json({ ok: true });

    if (!body.name || !body.email || !body.message || !body.consent) {
      return NextResponse.json({ error: "missing-fields" }, { status: 400 });
    }

    const lead = {
      name: String(body.name).slice(0, 120),
      email: String(body.email).slice(0, 160),
      phone: body.phone ? String(body.phone).slice(0, 60) : undefined,
      date: body.date ? String(body.date) : undefined,
      location: body.location ? String(body.location).slice(0, 120) : undefined,
      guests: body.guests ? String(body.guests).slice(0, 20) : undefined,
      service: body.service ? String(body.service).slice(0, 40) : undefined,
      message: String(body.message).slice(0, 4000),
      locale: String(body.locale ?? "de"),
      at: new Date().toISOString(),
    };

    await addLead(lead);
    console.log("[Anfrage]", lead.name, lead.email, lead.date ?? "-", lead.location ?? "-");

    return NextResponse.json({ ok: true });
  } catch {
    return NextResponse.json({ error: "bad-request" }, { status: 400 });
  }
}
