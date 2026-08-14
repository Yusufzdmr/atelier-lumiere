import type { Metadata } from "next";
import { cookies } from "next/headers";
import { notFound } from "next/navigation";

import ClientGallery from "@/components/ClientGallery";
import GalleryLogin from "@/components/GalleryLogin";
import { Section } from "@/components/ui";
import { getGallery } from "@/lib/store";
import { img } from "@/lib/images";
import { getDict } from "@/lib/dict";
import { isLocale, type Locale } from "@/lib/i18n";

export const dynamic = "force-dynamic";

export async function generateMetadata({ params }: { params: Promise<{ locale: string }> }): Promise<Metadata> {
  const { locale } = await params;
  const l: Locale = isLocale(locale) ? locale : "de";
  return { title: getDict(l).gallery.title, robots: { index: false, follow: false } };
}

export default async function GalleryPage({ params }: { params: Promise<{ locale: string; code: string }> }) {
  const { locale, code } = await params;
  const l: Locale = isLocale(locale) ? locale : "de";
  const gallery = await getGallery(code);
  if (!gallery) notFound();

  const jar = await cookies();
  const authorized = jar.get(`al-gal-${gallery.code}`)?.value === "1";

  if (!authorized) {
    const t = getDict(l);
    return (
      <Section className="pt-40">
        <div className="eyebrow">{t.gallery.protected}</div>
        <h1 className="headline mt-3 text-4xl">{gallery.couple}</h1>
        <p className="mt-4 max-w-md text-sm text-muted">{t.gallery.lead}</p>
        <div className="mt-10">
          <GalleryLogin locale={l} presetCode={gallery.code} />
        </div>
      </Section>
    );
  }

  const photos = [
    ...gallery.uploads.map((src) => ({ thumb: src, full: src })),
    ...gallery.seeds.map((seed) => ({ thumb: img(seed, 700, 900), full: img(seed, 1400, 1800) })),
  ];

  return (
    <ClientGallery
      locale={l}
      code={gallery.code}
      couple={gallery.couple}
      venue={gallery.venue}
      date={gallery.date}
      photos={photos}
      videoUrl={gallery.videoUrl}
    />
  );
}
