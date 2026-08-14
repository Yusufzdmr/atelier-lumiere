import { NextResponse } from "next/server";
import { headers } from "next/headers";
import {
  createInvitation,
  slugAvailable,
  isCustomerCode,
  defaultSections,
  type EventType,
  type InviteEvent,
  type InviteSections,
  type ProgramItem,
} from "@/lib/store";
import { slugify } from "@/lib/invite";
import { themes } from "@/lib/themes";
import { eventTypes } from "@/lib/events";
import { computeTotal } from "@/lib/pricing";
import { saveUploads } from "@/lib/media";
import { site } from "@/lib/site";

const THEME_IDS = themes.map((t) => t.id) as string[];
const TYPE_IDS = eventTypes.map((t) => t.id) as string[];

/** Data-URLs der Demo begrenzen (Live: Upload nach Vercel Blob). */
const MAX_PHOTO_CHARS = 1_400_000;
const MAX_PHOTOS = 4;

const clean = (v: unknown, max: number) => String(v ?? "").trim().slice(0, max);

function cleanPhotos(input: unknown): string[] {
  if (!Array.isArray(input)) return [];
  return input
    .filter((x): x is string => typeof x === "string")
    .filter((x) => x.startsWith("data:image/") || x.startsWith("https://"))
    .filter((x) => x.length <= MAX_PHOTO_CHARS)
    .slice(0, MAX_PHOTOS);
}

function cleanProgram(input: unknown): ProgramItem[] {
  if (!Array.isArray(input)) return [];
  return input
    .filter((x): x is ProgramItem => !!x && typeof x === "object")
    .map((x) => ({ time: clean(x.time, 5), title: clean(x.title, 80) }))
    .filter((x) => x.title !== "")
    .slice(0, 10);
}

function cleanEvents(input: unknown): InviteEvent[] {
  if (!Array.isArray(input)) return [];
  return input
    .filter((x): x is InviteEvent => !!x && typeof x === "object")
    .map((x) => ({
      name: clean(x.name, 60),
      date: clean(x.date, 10),
      time: clean(x.time, 5),
      venue: clean(x.venue, 120),
      address: clean(x.address, 200),
    }))
    .slice(0, 2);
}

function cleanSections(input: unknown): InviteSections {
  const base = defaultSections();
  if (!input || typeof input !== "object") return base;
  const src = input as Record<string, unknown>;
  for (const key of Object.keys(base) as (keyof InviteSections)[]) {
    if (typeof src[key] === "boolean") base[key] = src[key] as boolean;
  }
  return base;
}

/** Nur http(s)-Links für Musik und Video zulassen. */
const cleanUrl = (v: unknown) => {
  const s = clean(v, 500);
  return s.startsWith("https://") || s.startsWith("http://") ? s : "";
};

export async function POST(req: Request) {
  try {
    const body = await req.json();

    const bride = clean(body.bride, 40);
    const groom = clean(body.groom, 40);
    const events = cleanEvents(body.events);

    if (!bride || !groom || !events.length || !events[0].date) {
      return NextResponse.json({ error: "missing-fields" }, { status: 400 });
    }

    let slug = slugify(String(body.slug || `${bride}-${groom}`));
    if (!slug) slug = `einladung-${Date.now().toString(36)}`;
    if (!await slugAvailable(slug)) slug = `${slug}-${Math.random().toString(36).slice(2, 5)}`;

    const theme = THEME_IDS.includes(body.theme) ? String(body.theme) : "elysee";
    const eventType = (TYPE_IDS.includes(body.eventType) ? body.eventType : "wedding") as EventType;
    const locale = body.locale === "tr" ? "tr" : "de";
    const paid = isCustomerCode(clean(body.coupon, 40));
    const sections = cleanSections(body.sections);

    const families =
      sections.family && (body.families?.bride || body.families?.groom)
        ? { bride: clean(body.families.bride, 60), groom: clean(body.families.groom, 60) }
        : undefined;

    await createInvitation({
      slug,
      bride,
      groom,
      eventType,
      events,
      message: clean(body.message, 600),
      closing: clean(body.closing, 300) || undefined,
      families,
      photos: await saveUploads(cleanPhotos(body.photos), `einladungen/${slug}`),
      program: sections.program ? cleanProgram(body.program) : [],
      menu: sections.menu && Array.isArray(body.menu) ? body.menu.map((m: unknown) => clean(m, 80)).filter(Boolean).slice(0, 12) : [],
      musicUrl: sections.music ? cleanUrl(body.musicUrl) || undefined : undefined,
      videoUrl: sections.video ? cleanUrl(body.videoUrl) || undefined : undefined,
      sections,
      hashtag: clean(body.hashtag, 60) || undefined,
      theme,
      locale,
      paid,
      price: computeTotal(sections, events.length > 1, paid),
      createdAt: new Date().toISOString(),
    });

    const h = await headers();
    const host = h.get("host") ?? new URL(site.url).host;
    const proto = host.startsWith("localhost") || host.startsWith("127.") ? "http" : "https";
    const path = `/${locale}/einladung/${slug}`;

    return NextResponse.json({
      ok: true,
      slug,
      path,
      url: `${proto}://${host}${path}`,
      free: paid,
      price: computeTotal(sections, events.length > 1, paid),
    });
  } catch {
    return NextResponse.json({ error: "bad-request" }, { status: 400 });
  }
}
