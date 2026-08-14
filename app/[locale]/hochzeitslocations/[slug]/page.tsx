import type { Metadata } from "next";
import Link from "next/link";
import { notFound } from "next/navigation";

import PageHero from "@/components/PageHero";
import Reveal from "@/components/Reveal";
import JsonLd from "@/components/JsonLd";
import ContactForm from "@/components/ContactForm";
import { Section, SectionHead, Photo, Accordion, Breadcrumbs } from "@/components/ui";
import { venues } from "@/lib/venues";
import { getVenue, getStories } from "@/lib/cms";
import { cityBySlug } from "@/lib/cities";

import { getDict } from "@/lib/dict";
import { meta, faqLd, breadcrumbLd, serviceLd } from "@/lib/seo";
import { locales, isLocale, type Locale } from "@/lib/i18n";

export function generateStaticParams() {
  return locales.flatMap((locale) => venues.map((v) => ({ locale, slug: v.slug })));
}

export async function generateMetadata({
  params,
}: {
  params: Promise<{ locale: string; slug: string }>;
}): Promise<Metadata> {
  const { locale, slug } = await params;
  const l: Locale = isLocale(locale) ? locale : "de";
  const v = await getVenue(slug);
  if (!v) return {};
  return meta({
    locale: l,
    path: `/hochzeitslocations/${v.slug}`,
    title: l === "de" ? `${v.name} Hochzeitsfotograf – Erfahrung vor Ort` : `${v.name} düğün fotoğrafçısı – mekân tecrübesi`,
    description: v.lead[l].slice(0, 158),
  });
}

export default async function VenuePage({ params }: { params: Promise<{ locale: string; slug: string }> }) {
  const { locale, slug } = await params;
  const l: Locale = isLocale(locale) ? locale : "de";
  const v = await getVenue(slug);
  if (!v) notFound();

  const t = getDict(l);
  const p = (path: string) => `/${l}${path}`;
  const city = cityBySlug(v.citySlug);
  const related = (await getStories()).filter((s) => s.venueSlug === v.slug);
  const h1 = l === "de" ? `${v.name} – Hochzeitsfotograf & Videograf` : `${v.name} – düğün fotoğrafçısı & videografı`;

  return (
    <>
      <PageHero eyebrow={v.type[l]} title={h1} text={v.lead[l]} seed={`venue-${v.slug}`} height="lg" />

      <Section>
        <Breadcrumbs
          items={[
            { name: "Home", href: p("") },
            { name: t.venue.all, href: p("/hochzeitslocations") },
            { name: v.name },
          ]}
        />

        <div className="grid gap-14 lg:grid-cols-[1.15fr_0.85fr] lg:gap-20">
          <div>
            <Reveal className="prose-lux max-w-none">
              {v.body[l].map((par, i) => (
                <p key={i}>{par}</p>
              ))}

              <h3>{t.venue.light}</h3>
              <p>{v.light[l]}</p>

              <h3>{t.venue.spots}</h3>
              <ul>
                {v.spots[l].map((s) => (
                  <li key={s}>{s}</li>
                ))}
              </ul>

              <h3>{t.venue.rules}</h3>
              <ul>
                {v.rules[l].map((s) => (
                  <li key={s}>{s}</li>
                ))}
              </ul>
            </Reveal>

            <Reveal delay={100} className="mt-14">
              <h3 className="font-display text-2xl font-light text-ink">{t.venue.timing}</h3>
              <ol className="mt-6 border-l border-sand-deep">
                {v.timing.map((row) => (
                  <li key={row.time} className="relative pb-7 pl-7">
                    <span className="absolute -left-[5px] top-1.5 h-2.5 w-2.5 rounded-full bg-gold" />
                    <div className="text-[0.7rem] uppercase tracking-[0.2em] text-gold">{row.time}</div>
                    <div className="mt-1.5 text-[0.95rem] leading-relaxed text-ink-soft">{row.what[l]}</div>
                  </li>
                ))}
              </ol>
            </Reveal>

            <Reveal delay={140} className="mt-10 grid grid-cols-2 gap-4">
              <Photo seed={`venue-${v.slug}-a`} alt={`${v.name} 1`} ratio="4/5" sizes="(max-width: 768px) 50vw, 25vw" />
              <Photo seed={`venue-${v.slug}-b`} alt={`${v.name} 2`} ratio="4/5" sizes="(max-width: 768px) 50vw, 25vw" />
            </Reveal>
          </div>

          <aside className="lg:sticky lg:top-28 lg:self-start">
            <Reveal>
              <div className="border border-sand-deep bg-sand/40 p-7">
                <dl className="space-y-3 text-sm">
                  <div className="flex justify-between gap-4 border-b border-sand-deep/60 pb-3">
                    <dt className="text-muted">{t.venue.type}</dt>
                    <dd className="text-right text-ink">{v.type[l]}</dd>
                  </div>
                  <div className="flex justify-between gap-4 border-b border-sand-deep/60 pb-3">
                    <dt className="text-muted">{t.venue.city}</dt>
                    <dd className="text-right text-ink">{v.city}</dd>
                  </div>
                  <div className="flex justify-between gap-4 border-b border-sand-deep/60 pb-3">
                    <dt className="text-muted">{t.venue.capacity}</dt>
                    <dd className="text-right text-ink">{v.capacity[l]}</dd>
                  </div>
                  <div className="flex justify-between gap-4 pb-1">
                    <dt className="text-muted">{t.venue.address}</dt>
                    <dd className="text-right text-ink">{v.address}</dd>
                  </div>
                </dl>

                <a
                  href={`https://www.google.com/maps/search/?api=1&query=${encodeURIComponent(v.address)}`}
                  target="_blank"
                  rel="noopener noreferrer"
                  className="mt-6 block border border-ink px-5 py-3 text-center text-[0.68rem] uppercase tracking-[0.2em] text-ink transition-colors hover:bg-ink hover:text-cream"
                >
                  Google Maps
                </a>

                {city && (
                  <Link
                    href={p(`/hochzeitsfotograf/${city.slug}`)}
                    className="mt-3 block bg-ink px-5 py-3 text-center text-[0.68rem] uppercase tracking-[0.2em] text-cream transition-colors hover:bg-gold"
                  >
                    {l === "de" ? `Hochzeitsfotograf ${city.name}` : `${city.name} düğün fotoğrafçısı`}
                  </Link>
                )}
              </div>
            </Reveal>

            {related.length > 0 && (
              <Reveal delay={120} className="mt-8">
                <h3 className="text-[0.68rem] uppercase tracking-[0.22em] text-gold">{t.portfolio.title}</h3>
                <ul className="mt-4 space-y-2.5">
                  {related.map((s) => (
                    <li key={s.slug}>
                      <Link href={p(`/portfolio/${s.slug}`)} className="link-underline text-[0.92rem] text-ink-soft hover:text-gold">
                        {s.couple} – {s.month[l]}
                      </Link>
                    </li>
                  ))}
                </ul>
              </Reveal>
            )}

            <p className="mt-8 text-[0.72rem] leading-relaxed text-muted">{t.venue.disclaimer}</p>
          </aside>
        </div>
      </Section>

      <Section tone="sand">
        <div className="grid gap-12 lg:grid-cols-[0.8fr_1.2fr] lg:gap-20">
          <SectionHead eyebrow="FAQ" title={t.venue.faq} />
          <Reveal delay={100}>
            <Accordion items={v.faq.map((f) => ({ q: f.q[l], a: f.a[l] }))} />
          </Reveal>
        </div>
      </Section>

      <Section tone="ink">
        <div className="grid gap-14 lg:grid-cols-2 lg:gap-20">
          <SectionHead eyebrow={v.name} title={t.venue.cta} text={t.venue.ctaText} tone="light" />
          <Reveal delay={120} className="bg-cream p-8 sm:p-10">
            <ContactForm locale={l} preset={v.name} />
          </Reveal>
        </div>
      </Section>

      <JsonLd
        data={[
          serviceLd({
            locale: l,
            name: h1,
            description: v.lead[l],
            area: v.city,
            path: `/hochzeitslocations/${v.slug}`,
          }),
          faqLd(v.faq.map((f) => ({ q: f.q[l], a: f.a[l] }))),
          breadcrumbLd(l, [
            { name: "Home", path: "/" },
            { name: t.venue.all, path: "/hochzeitslocations" },
            { name: v.name, path: `/hochzeitslocations/${v.slug}` },
          ]),
        ]}
      />
    </>
  );
}
