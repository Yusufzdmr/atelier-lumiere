import type { Metadata } from "next";

import PageHero from "@/components/PageHero";
import Reveal from "@/components/Reveal";
import JsonLd from "@/components/JsonLd";
import { Section, Photo, Breadcrumbs, Btn } from "@/components/ui";
import { getServices } from "@/lib/cms";
import { getDict } from "@/lib/dict";
import { breadcrumbLd, serviceLd, pageMeta } from "@/lib/seo";
import { locales, isLocale, type Locale } from "@/lib/i18n";

export function generateStaticParams() {
  return locales.map((locale) => ({ locale }));
}

export async function generateMetadata({ params }: { params: Promise<{ locale: string }> }): Promise<Metadata> {
  const { locale } = await params;
  const l: Locale = isLocale(locale) ? locale : "de";
  return pageMeta({ locale: l, page: "leistungen" });
}

export default async function ServicesPage({ params }: { params: Promise<{ locale: string }> }) {
  const { locale } = await params;
  const l: Locale = isLocale(locale) ? locale : "de";
  const t = getDict(l);
  const services = await getServices();
  const p = (path: string) => `/${l}${path}`;

  return (
    <>
      <PageHero eyebrow={t.home.servicesEyebrow} title={t.services.title} text={t.services.lead} seed="services-hero" />

      <Section>
        <Breadcrumbs items={[{ name: "Home", href: p("") }, { name: t.services.title }]} />

        <div className="space-y-24">
          {services.map((s, i) => (
            <div key={s.slug} id={s.slug} className="scroll-mt-28">
              <div className={`grid items-center gap-12 lg:grid-cols-2 lg:gap-20 ${i % 2 ? "lg:[&>*:first-child]:order-2" : ""}`}>
                <Reveal mask>
                  <Photo seed={s.seed} alt={s.title[l]} ratio="4/5" sizes="(max-width: 1024px) 100vw, 50vw" />
                </Reveal>
                <Reveal delay={120}>
                  <div className="eyebrow">0{i + 1}</div>
                  <h2 className="headline mt-4 text-3xl sm:text-4xl">{s.title[l]}</h2>
                  <div className="prose-lux mt-6">
                    {s.body[l].map((par, k) => (
                      <p key={k}>{par}</p>
                    ))}
                  </div>
                  <h3 className="mt-8 text-[0.68rem] uppercase tracking-[0.22em] text-gold">{t.services.includes}</h3>
                  <ul className="prose-lux mt-4">
                    {s.bullets[l].map((b) => (
                      <li key={b}>{b}</li>
                    ))}
                  </ul>
                  <Btn href={p("/preise")} variant="outline" className="mt-8">
                    {t.nav.prices}
                  </Btn>
                </Reveal>
              </div>
            </div>
          ))}
        </div>
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
          ...services.map((s) =>
            serviceLd({ locale: l, name: s.title[l], description: s.short[l], area: "Stuttgart", path: "/leistungen" })
          ),
          breadcrumbLd(l, [
            { name: "Home", path: "/" },
            { name: t.services.title, path: "/leistungen" },
          ]),
        ]}
      />
    </>
  );
}
