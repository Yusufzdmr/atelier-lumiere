import type { Metadata } from "next";

import PageHero from "@/components/PageHero";
import Reveal from "@/components/Reveal";
import JsonLd from "@/components/JsonLd";
import { Section, Photo, Breadcrumbs, Btn, Stat } from "@/components/ui";
import { getAbout, getContent } from "@/lib/cms";
import { getDict } from "@/lib/dict";
import { site } from "@/lib/site";
import { breadcrumbLd, pageMeta } from "@/lib/seo";
import { locales, isLocale, type Locale } from "@/lib/i18n";

export function generateStaticParams() {
  return locales.map((locale) => ({ locale }));
}

export async function generateMetadata({ params }: { params: Promise<{ locale: string }> }): Promise<Metadata> {
  const { locale } = await params;
  const l: Locale = isLocale(locale) ? locale : "de";
  const a = await getAbout();
  return pageMeta({ locale: l, page: "ueber-mich", fallback: {
      title:
        l === "de"
          ? `Über mich – ${a.name}, Hochzeitsfotograf Stuttgart`
          : `Hakkımda – ${a.name}, Stuttgart düğün fotoğrafçısı`,
      description: a.lead[l],
    } });
}

export default async function AboutPage({ params }: { params: Promise<{ locale: string }> }) {
  const { locale } = await params;
  const l: Locale = isLocale(locale) ? locale : "de";
  const t = getDict(l);
  const a = await getAbout();
  const { stats } = await getContent();
  const p = (path: string) => `/${l}${path}`;

  return (
    <>
      <PageHero eyebrow={t.about.title} title={a.name} text={a.lead[l]} seed="about-hero" height="lg" />

      <Section>
        <Breadcrumbs items={[{ name: "Home", href: p("") }, { name: t.about.title }]} />

        <div className="grid gap-14 lg:grid-cols-[1.1fr_0.9fr] lg:gap-20">
          <Reveal className="prose-lux max-w-none">
            {a.body[l].map((par, i) => (
              <p key={i}>{par}</p>
            ))}
          </Reveal>
          <Reveal delay={120} mask>
            <Photo seed="about-portrait" alt={a.name} ratio="4/5" sizes="(max-width: 1024px) 100vw, 45vw" />
          </Reveal>
        </div>

        <Reveal delay={100} className="mt-16 grid grid-cols-2 gap-8 border-y border-sand-deep py-10 sm:grid-cols-4">
          <Stat value={stats.weddings} label={t.home.statsWeddings} />
          <Stat value={stats.years} label={t.home.statsYears} />
          <Stat value={stats.delivery} label={t.home.statsDelivery} />
          <Stat value={stats.rating} label={t.home.statsRating} />
        </Reveal>
      </Section>

      <Section tone="sand">
        <h2 className="headline text-3xl sm:text-4xl">{a.valuesTitle[l]}</h2>
        <div className="mt-12 grid gap-10 sm:grid-cols-2">
          {a.values.map((v, i) => (
            <Reveal key={i} delay={i * 90}>
              <h3 className="font-display text-xl font-normal text-ink">{v.t[l]}</h3>
              <p className="mt-2.5 text-[0.92rem] leading-relaxed text-muted">{v.d[l]}</p>
            </Reveal>
          ))}
        </div>

        <Reveal delay={150} className="mt-16">
          <h2 className="font-display text-2xl font-light text-ink">{a.gearTitle[l]}</h2>
          <ul className="prose-lux mt-5 max-w-2xl">
            {a.gear[l].map((g) => (
              <li key={g}>{g}</li>
            ))}
          </ul>
        </Reveal>
      </Section>

      <Section tone="ink">
        <div className="mx-auto max-w-2xl text-center">
          <h2 className="headline text-3xl text-cream sm:text-4xl">{t.home.ctaTitle}</h2>
          <p className="mt-5 text-cream/65">{t.home.ctaText}</p>
          <Btn href={p("/kontakt")} variant="light" className="mt-9">
            {t.home.ctaButton}
          </Btn>
        </div>
      </Section>

      <JsonLd
        data={[
          {
            "@context": "https://schema.org",
            "@type": "Person",
            name: a.name,
            jobTitle: l === "de" ? "Hochzeitsfotograf" : "Düğün fotoğrafçısı",
            worksFor: { "@id": `${site.url}/#business` },
            url: `${site.url}/${l}/ueber-mich`,
            knowsLanguage: ["de", "tr"],
          },
          breadcrumbLd(l, [
            { name: "Home", path: "/" },
            { name: t.about.title, path: "/ueber-mich" },
          ]),
        ]}
      />
    </>
  );
}
