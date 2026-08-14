import type { Metadata } from "next";
import Link from "next/link";

import PageHero from "@/components/PageHero";
import Reveal from "@/components/Reveal";
import JsonLd from "@/components/JsonLd";
import { Section, Photo, Breadcrumbs } from "@/components/ui";
import { getCities } from "@/lib/cms";
import { getDict } from "@/lib/dict";
import { meta, breadcrumbLd } from "@/lib/seo";
import { locales, isLocale, type Locale } from "@/lib/i18n";

export function generateStaticParams() {
  return locales.map((locale) => ({ locale }));
}

export async function generateMetadata({ params }: { params: Promise<{ locale: string }> }): Promise<Metadata> {
  const { locale } = await params;
  const l: Locale = isLocale(locale) ? locale : "de";
  return meta({
    locale: l,
    path: "/regionen",
    title: l === "de" ? "Hochzeitsfotograf in Stuttgart & Umgebung – alle Regionen" : "Stuttgart ve çevresinde düğün fotoğrafçısı – tüm bölgeler",
    description:
      l === "de"
        ? "Stuttgart, Ludwigsburg, Esslingen, Böblingen, Heilbronn, Tübingen und mehr: Hochzeitsfotograf und Videograf mit Ortskenntnis – Anfahrt bis 60 km inklusive."
        : "Stuttgart, Ludwigsburg, Esslingen, Böblingen, Heilbronn, Tübingen ve daha fazlası: bölgeyi tanıyan düğün fotoğrafçısı ve videografı – 60 km'ye kadar ulaşım dahil.",
  });
}

export default async function RegionsPage({ params }: { params: Promise<{ locale: string }> }) {
  const { locale } = await params;
  const l: Locale = isLocale(locale) ? locale : "de";
  const t = getDict(l);
  const p = (path: string) => `/${l}${path}`;
  const cities = await getCities();

  return (
    <>
      <PageHero
        eyebrow={t.home.citiesEyebrow}
        title={l === "de" ? "Wo wir fotografieren" : "Nerelerde çekim yapıyoruz"}
        text={t.home.citiesText}
        seed="regions-index"
      />

      <Section>
        <Breadcrumbs items={[{ name: "Home", href: p("") }, { name: t.city.allCities }]} />

        <div className="grid gap-7 sm:grid-cols-2 lg:grid-cols-3">
          {cities.map((c, i) => (
            <Reveal key={c.slug} delay={i * 50}>
              <Link href={p(`/hochzeitsfotograf/${c.slug}`)} className="group block h-full">
                <Photo seed={`city-${c.slug}`} alt={c.name} ratio="4/3" sizes="(max-width: 640px) 100vw, 33vw" />
                <h2 className="font-display mt-4 text-xl font-normal text-ink transition-colors group-hover:text-gold">
                  {l === "de" ? "Hochzeitsfotograf " : "Düğün fotoğrafçısı "}
                  {c.name}
                </h2>
                <p className="mt-2 text-[0.85rem] leading-relaxed text-muted">{c.lead[l]}</p>
                <div className="mt-3 text-[0.68rem] uppercase tracking-[0.16em] text-gold">{c.drive[l]}</div>
              </Link>
            </Reveal>
          ))}
        </div>
      </Section>

      <JsonLd
        data={breadcrumbLd(l, [
          { name: "Home", path: "/" },
          { name: t.city.allCities, path: "/regionen" },
        ])}
      />
    </>
  );
}
