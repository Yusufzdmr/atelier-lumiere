import type { Metadata } from "next";

import PageHero from "@/components/PageHero";
import Reveal from "@/components/Reveal";
import GalleryLogin from "@/components/GalleryLogin";
import { Section, Breadcrumbs } from "@/components/ui";
import { getDict } from "@/lib/dict";
import { meta } from "@/lib/seo";
import { locales, isLocale, type Locale } from "@/lib/i18n";

export function generateStaticParams() {
  return locales.map((locale) => ({ locale }));
}

export async function generateMetadata({ params }: { params: Promise<{ locale: string }> }): Promise<Metadata> {
  const { locale } = await params;
  const l: Locale = isLocale(locale) ? locale : "de";
  return meta({
    locale: l,
    path: "/galerie",
    title: l === "de" ? "Kundengalerie – Login" : "Müşteri galerisi – giriş",
    description: getDict(l).gallery.lead,
    noindex: true,
  });
}

export default async function GalleryIndex({ params }: { params: Promise<{ locale: string }> }) {
  const { locale } = await params;
  const l: Locale = isLocale(locale) ? locale : "de";
  const t = getDict(l);
  const p = (path: string) => `/${l}${path}`;

  const steps =
    l === "de"
      ? [
          "Ihr bekommt von uns eine E-Mail mit Galerie-Code und Passwort.",
          "Alle Bilder in voller Auflösung – teilbar mit Familie und Freunden.",
          "Favoriten mit dem Herz markieren und die Auswahl fürs Album absenden.",
          "Wir sehen eure Auswahl sofort und gestalten das Album danach.",
        ]
      : [
          "Galeri kodu ve parolayı e-posta ile gönderiyoruz.",
          "Tüm kareler tam çözünürlükte – aile ve arkadaşlarla paylaşılabilir.",
          "Beğendiklerinizi kalple işaretleyin ve albüm seçimini gönderin.",
          "Seçiminizi anında görüyor, albümü ona göre hazırlıyoruz.",
        ];

  return (
    <>
      <PageHero eyebrow={t.gallery.protected} title={t.gallery.title} text={t.gallery.lead} seed="gallery-hero" />

      <Section>
        <Breadcrumbs items={[{ name: "Home", href: p("") }, { name: t.gallery.title }]} />

        <div className="grid gap-14 lg:grid-cols-2 lg:gap-20">
          <Reveal>
            <GalleryLogin locale={l} />
          </Reveal>

          <Reveal delay={120}>
            <ol className="space-y-7">
              {steps.map((s, i) => (
                <li key={i} className="flex gap-5">
                  <span className="font-display text-3xl font-light text-gold/40">0{i + 1}</span>
                  <span className="pt-2 text-[0.95rem] leading-relaxed text-muted">{s}</span>
                </li>
              ))}
            </ol>
          </Reveal>
        </div>
      </Section>
    </>
  );
}
