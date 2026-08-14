import { NextResponse } from "next/server";

import { saveDraft, getDraft, deleteDraft } from "@/lib/store";

/**
 * Zwischenstand des Einladungs-Assistenten.
 *
 * GET    ?token=…   Entwurf laden (Fortsetzungslink)
 * POST   { token?, label, data }   speichern, gibt den Token zurueck
 * DELETE ?token=…   aufraeumen, sobald die Einladung angelegt ist
 *
 * Der Token ist zufaellig und dient als Schluessel – wer ihn hat, sieht den
 * Entwurf. Deshalb steht er nur im Link des Paares und wird nirgends gelistet
 * ausser im Admin.
 */

const MAX_BYTES = 6_000_000;

const newToken = () => crypto.randomUUID().replace(/-/g, "").slice(0, 20);

export async function GET(req: Request) {
  const token = new URL(req.url).searchParams.get("token") ?? "";
  if (!token) return NextResponse.json({ error: "missing-token" }, { status: 400 });

  const draft = await getDraft(token);
  if (!draft) return NextResponse.json({ error: "unknown-token" }, { status: 404 });

  return NextResponse.json({ token, data: draft.data, updatedAt: draft.updatedAt });
}

export async function POST(req: Request) {
  try {
    const body = await req.json();
    const token = String(body?.token ?? "").trim().slice(0, 40) || newToken();
    const label = String(body?.label ?? "").trim().slice(0, 80);

    let data = body?.data ?? {};
    // Fotos sind Data-URLs und koennen gross werden. Lieber den Entwurf ohne
    // Bilder sichern als gar nichts zu sichern.
    if (JSON.stringify(data).length > MAX_BYTES) {
      data = { ...data, photos: [] };
    }

    await saveDraft(token, label, data);
    return NextResponse.json({ ok: true, token });
  } catch {
    return NextResponse.json({ error: "save-failed" }, { status: 500 });
  }
}

export async function DELETE(req: Request) {
  const token = new URL(req.url).searchParams.get("token") ?? "";
  if (token) await deleteDraft(token);
  return NextResponse.json({ ok: true });
}
