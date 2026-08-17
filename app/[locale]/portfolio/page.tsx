import type { Metadata } from "next";
import Link from "next/link";

import PageHero from "@/components/PageHero";
import Reveal from "@/components/Reveal";
import JsonLd from "@/components/JsonLd";
import { Section, Photo, Breadcrumbs } from "@/components/ui";
import { getStories } from "@/lib/cms";
import { getDict } from "@/lib/dict";
import { breadcrumbLd, pageMeta } from "@/lib/seo";
import { locales, isLocale, type Locale } from "@/lib/i18n";

export function generateStaticParams() {
  return locales.map((locale) => ({ locale }));
}

export async function generateMetadata({ params }: { params: Promise<{ locale: string }> }): Promise<Metadata> {
  const { locale } = await params;
  const l: Locale = isLocale(locale) ? locale : "de";
  return pageMeta({ locale: l, page: "portfolio" });
}

export default async function PortfolioPage({ params }: { params: Promise<{ locale: string }> }) {
  const { locale } = await params;
  const l: Locale = isLocale(locale) ? locale : "de";
  const t = getDict(l);
  const p = (path: string) => `/${l}${path}`;
  const stories = await getStories();

  return (
    <>
      <PageHero eyebrow={t.home.portfolioEyebrow} title={t.portfolio.title} text={t.portfolio.lead} seed="portfolio-hero" />

      <Section>
        <Breadcrumbs items={[{ name: "Home", href: p("") }, { name: t.portfolio.title }]} />

        <div className="space-y-14 sm:space-y-16">
          {stories.map((s) => (
            <Reveal key={s.slug} delay={60}>
              <Link href={p(`/portfolio/${s.slug}`)} className="group block">
                <div className="grid gap-4 sm:grid-cols-3">
                  <div className="sm:col-span-2">
                    <Photo seed={s.seeds[0]} alt={s.couple} ratio="16/10" sizes="(max-width: 640px) 100vw, 66vw" />
                  </div>
                  <div className="hidden sm:block">
                    <Photo seed={s.seeds[1]} alt={`${s.couple} 2`} ratio="4/5" sizes="33vw" />
                  </div>
                </div>
                <div className="mt-6 flex flex-wrap items-baseline justify-between gap-4">
                  <h2 className="font-display text-3xl font-light text-ink transition-colors group-hover:text-gold sm:text-4xl">
                    {s.couple}
                  </h2>
                  <div className="text-[0.68rem] uppercase tracking-[0.18em] text-muted">
                    {s.venue[l]} · {s.month[l]} · {s.guests} {t.portfolio.guests}
                  </div>
                </div>
                <p className="mt-3 max-w-3xl text-[0.95rem] leading-relaxed text-muted">{s.intro[l]}</p>
              </Link>
            </Reveal>
          ))}
        </div>
      </Section>

      <JsonLd
        data={breadcrumbLd(l, [
          { name: "Home", path: "/" },
          { name: t.portfolio.title, path: "/portfolio" },
        ])}
      />
    </>
  );
}
