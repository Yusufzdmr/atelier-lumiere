import type { Metadata } from "next";

import PageHero from "@/components/PageHero";
import Reveal from "@/components/Reveal";
import JsonLd from "@/components/JsonLd";
import { Section, SectionHead, Breadcrumbs, Btn, Accordion } from "@/components/ui";
import { getContent, getFaq } from "@/lib/cms";
import { getDict } from "@/lib/dict";
import { breadcrumbLd, offerLd, faqLd, pageMeta } from "@/lib/seo";
import { locales, isLocale, type Locale } from "@/lib/i18n";

export function generateStaticParams() {
  return locales.map((locale) => ({ locale }));
}

export async function generateMetadata({ params }: { params: Promise<{ locale: string }> }): Promise<Metadata> {
  const { locale } = await params;
  const l: Locale = isLocale(locale) ? locale : "de";
  return pageMeta({ locale: l, page: "preise" });
}

export default async function PricesPage({ params }: { params: Promise<{ locale: string }> }) {
  const { locale } = await params;
  const l: Locale = isLocale(locale) ? locale : "de";
  const t = getDict(l);
  const { packages, addons } = await getContent();
  const faqGeneral = await getFaq();
  const p = (path: string) => `/${l}${path}`;

  return (
    <>
      <PageHero eyebrow={t.nav.prices} title={t.prices.title} text={t.prices.lead} seed="prices-hero" />

      <Section>
        <Breadcrumbs items={[{ name: "Home", href: p("") }, { name: t.prices.title }]} />

        <div className="grid gap-6 lg:grid-cols-3">
          {packages.map((pk, i) => (
            <Reveal key={pk.slug} delay={i * 90}>
              <div
                className={`flex h-full flex-col border p-8 transition-colors ${
                  pk.featured ? "border-gold bg-sand/40" : "border-sand-deep hover:border-muted"
                }`}
              >
                {pk.featured && (
                  <div className="mb-4 inline-block self-start bg-gold px-3 py-1 text-[0.6rem] uppercase tracking-[0.2em] text-white">
                    {t.prices.popular}
                  </div>
                )}
                <h2 className="font-display text-2xl font-light text-ink">{pk.name[l]}</h2>
                <div className="mt-4 flex items-baseline gap-2">
                  <span className="text-[0.7rem] uppercase tracking-[0.18em] text-muted">{t.prices.from}</span>
                  <span className="font-display text-4xl font-light text-ink">{pk.price}</span>
                </div>
                <div className="mt-2 text-[0.8rem] text-muted">{pk.hint[l]}</div>

                <ul className="prose-lux mt-7 flex-1">
                  {pk.features[l].map((f) => (
                    <li key={f}>{f}</li>
                  ))}
                </ul>

                <Btn href={p("/kontakt")} variant={pk.featured ? "solid" : "outline"} className="mt-8 w-full">
                  {t.prices.cta}
                </Btn>
              </div>
            </Reveal>
          ))}
        </div>

        <Reveal delay={120} className="mt-16">
          <h2 className="font-display text-2xl font-light text-ink">{t.prices.addonsTitle}</h2>
          <div className="mt-6 divide-y divide-sand-deep border-y border-sand-deep">
            {addons.map((a) => (
              <div key={a.name[l]} className="flex items-center justify-between gap-6 py-4">
                <span className="text-[0.95rem] text-ink-soft">{a.name[l]}</span>
                <span className="shrink-0 font-display text-lg text-gold">{a.price}</span>
              </div>
            ))}
          </div>
          <p className="mt-6 text-[0.82rem] leading-relaxed text-muted">{t.prices.note}</p>
        </Reveal>
      </Section>

      <Section tone="sand">
        <div className="grid gap-12 lg:grid-cols-[0.8fr_1.2fr] lg:gap-20">
          <SectionHead eyebrow="FAQ" title={t.home.faqTitle} />
          <Reveal delay={100}>
            <Accordion items={faqGeneral.map((f) => ({ q: f.q[l], a: f.a[l] }))} />
          </Reveal>
        </div>
      </Section>

      <JsonLd
        data={[
          ...packages.map((pk) => offerLd({ name: pk.name[l], price: pk.price, description: pk.hint[l] })),
          faqLd(faqGeneral.map((f) => ({ q: f.q[l], a: f.a[l] }))),
          breadcrumbLd(l, [
            { name: "Home", path: "/" },
            { name: t.prices.title, path: "/preise" },
          ]),
        ]}
      />
    </>
  );
}
