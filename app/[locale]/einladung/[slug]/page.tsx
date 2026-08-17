import type { Metadata } from "next";
import { headers } from "next/headers";

import InviteResolver from "@/components/InviteResolver";
import type { InviteView } from "@/components/InviteCard";
import { getInvitation } from "@/lib/store";
import { formatDate } from "@/lib/invite";
import { site } from "@/lib/site";
import { isLocale, type Locale } from "@/lib/i18n";

export const dynamic = "force-dynamic";

export async function generateMetadata({
  params,
}: {
  params: Promise<{ locale: string; slug: string }>;
}): Promise<Metadata> {
  const { locale, slug } = await params;
  const l: Locale = isLocale(locale) ? locale : "de";
  const inv = await getInvitation(slug);
  if (!inv) return { title: "Einladung", robots: { index: false, follow: false } };

  const title = `${inv.bride} & ${inv.groom} – ${l === "de" ? "Wir heiraten" : "Evleniyoruz"}`;
  const ev = inv.events?.[0];
  return {
    title,
    description: `${ev ? formatDate(ev.date, l) : ""} · ${ev?.venue ?? ""}`,
    robots: { index: false, follow: false },
    openGraph: {
      title,
      description: `${ev ? formatDate(ev.date, l) : ""} · ${ev?.venue ?? ""}`,
      url: `${site.url}/${l}/einladung/${slug}`,
      type: "website",
    },
  };
}

export default async function InvitePage({ params }: { params: Promise<{ locale: string; slug: string }> }) {
  const { locale, slug } = await params;
  const l: Locale = isLocale(locale) ? locale : "de";
  const inv = await getInvitation(slug);

  const h = await headers();
  const host = h.get("host") ?? new URL(site.url).host;
  const proto = host.startsWith("localhost") || host.startsWith("127.") ? "http" : "https";
  const origin = `${proto}://${host}`;

  const initial: InviteView | null = inv
    ? {
        slug: inv.slug,
        bride: inv.bride,
        groom: inv.groom,
        eventType: inv.eventType,
        events: inv.events ?? [],
        message: inv.message,
        closing: inv.closing,
        families: inv.families,
        photos: inv.photos ?? [],
        backdrop: inv.backdrop,
        program: inv.program ?? [],
        menu: inv.menu ?? [],
        musicUrl: inv.musicUrl,
        videoUrl: inv.videoUrl,
        sections: inv.sections,
        hashtag: inv.hashtag,
        theme: inv.theme,
      }
    : null;

  return <InviteResolver slug={slug} locale={l} origin={origin} initial={initial} />;
}
