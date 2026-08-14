import type { Metadata } from "next";
import Link from "next/link";
import { notFound } from "next/navigation";

import PageHero from "@/components/PageHero";
import Reveal from "@/components/Reveal";
import JsonLd from "@/components/JsonLd";
import ContactForm from "@/components/ContactForm";
import { Section, SectionHead, Photo, Accordion, Breadcrumbs, Btn } from "@/components/ui";
import { cities } from "@/lib/cities";
import { getVenue, getContent, getCity, getPostsForCity } from "@/lib/cms";
import { getDict } from "@/lib/dict";
import { meta, faqLd, breadcrumbLd, serviceLd } from "@/lib/seo";
import { locales, isLocale, type Locale } from "@/lib/i18n";

export function generateStaticParams() {
  return locales.flatMap((locale) => cities.map((c) => ({ locale, stadt: c.slug })));
}

export async function generateMetadata({
  params,
}: {
  params: Promise<{ locale: string; stadt: string }>;
}): Promise<Metadata> {
  const { locale, stadt } = await params;
  const l: Locale = isLocale(locale) ? locale : "de";
  const city = await getCity(stadt);
  if (!city) return {};

  return meta({
    locale: l,
    path: `/hochzeitsfotograf/${city.slug}`,
    title:
      l === "de"
        ? `Hochzeitsfotograf ${city.name} – Foto & Video ab 690 €`
        : `${city.name} Düğün Fotoğrafçısı – Foto & Video 690 €'dan`,
    description: city.lead[l].slice(0, 158),
  });
}

export default async function CityPage({ params }: { params: Promise<{ locale: string; stadt: string }> }) {
  const { locale, stadt } = await params;
  const l: Locale = isLocale(locale) ? locale : "de";
  const city = await getCity(stadt);
  if (!city) notFound();

  const t = getDict(l);
  const { packages } = await getContent();
  const p = (path: string) => `/${l}${path}`;
  const cityVenues = (await Promise.all(city.venues.map((v) => getVenue(v)))).filter(Boolean);
  const neighbours = (await Promise.all(city.neighbours.map((n) => getCity(n)))).filter(Boolean);
  const cityPosts = await getPostsForCity(city.slug);
  const h1 =
    l === "de"
      ? `Hochzeitsfotograf ${city.name}`
      : `${city.name} düğün fotoğrafçısı`;

  return (
    <>
      <PageHero eyebrow={t.city.metaTitleSuffix} title={h1} text={city.lead[l]} seed={`city-${city.slug}`} height="lg" />

      <Section>
        <Breadcrumbs
          items={[
            { name: "Home", href: p("") },
            { name: t.city.allCities, href: p("/regionen") },
            { name: city.name },
          ]}
        />

        <div className="grid gap-14 lg:grid-cols-[1.15fr_0.85fr] lg:gap-20">
          <div>
            <Reveal className="prose-lux max-w-none">
              <h2>
                {l === "de" ? `Hochzeitsfotografie in ${city.name}` : `${city.name} bölgesinde düğün fotoğrafçılığı`}
              </h2>
              {city.body[l].map((par, i) => (
                <p key={i}>{par}</p>
              ))}

              <h3>{t.city.spots}</h3>
              <ul>
                {city.spots.map((s) => (
                  <li key={s.name}>
                    <strong>{s.name}</strong> – {s.note[l]}
                  </li>
                ))}
              </ul>
            </Reveal>

            <Reveal delay={120} className="mt-12 grid grid-cols-2 gap-4">
              <Photo seed={`city-${city.slug}-a`} alt={`${h1} 1`} ratio="3/4" sizes="(max-width: 768px) 50vw, 25vw" />
              <Photo seed={`city-${city.slug}-b`} alt={`${h1} 2`} ratio="3/4" sizes="(max-width: 768px) 50vw, 25vw" />
            </Reveal>
          </div>

          <aside className="lg:sticky lg:top-28 lg:self-start">
            <Reveal>
              <div className="border border-sand-deep bg-sand/40 p-7">
                <div className="eyebrow">{city.kreis[l]}</div>
                <dl className="mt-5 space-y-3 text-sm">
                  <div className="flex justify-between gap-4 border-b border-sand-deep/60 pb-3">
                    <dt className="text-muted">{t.city.drive}</dt>
                    <dd className="text-ink">{city.drive[l]}</dd>
                  </div>
                  <div className="flex justify-between gap-4 border-b border-sand-deep/60 pb-3">
                    <dt className="text-muted">{t.city.district}</dt>
                    <dd className="text-right text-ink">{city.kreis[l]}</dd>
                  </div>
                  {packages.map((pk) => (
                    <div key={pk.slug} className="flex justify-between gap-4 border-b border-sand-deep/60 pb-3">
                      <dt className="text-muted">{pk.name[l]}</dt>
                      <dd className="text-ink">{pk.price}</dd>
                    </div>
                  ))}
                </dl>
                <Btn href={p("/kontakt")} variant="solid" className="mt-7 w-full">
                  {t.nav.cta}
                </Btn>
              </div>
            </Reveal>

            {cityPosts.length > 0 && (
              <Reveal delay={110} className="mt-8">
                <h3 className="text-[0.68rem] uppercase tracking-[0.22em] text-gold">{t.blog.nav}</h3>
                <ul className="mt-4 space-y-2.5">
                  {cityPosts.slice(0, 3).map((post) => (
                    <li key={post.slug}>
                      <Link
                        href={p(`/ratgeber/${post.slug}`)}
                        className="link-underline text-[0.92rem] leading-snug text-ink-soft hover:text-gold"
                      >
                        {post.title[l]}
                      </Link>
                    </li>
                  ))}
                </ul>
              </Reveal>
            )}

            {cityVenues.length > 0 && (
              <Reveal delay={120} className="mt-8">
                <h3 className="text-[0.68rem] uppercase tracking-[0.22em] text-gold">{t.city.venues}</h3>
                <ul className="mt-4 space-y-2.5">
                  {cityVenues.map((v) => (
                    <li key={v!.slug}>
                      <Link
                        href={p(`/hochzeitslocations/${v!.slug}`)}
                        className="link-underline text-[0.92rem] text-ink-soft hover:text-gold"
                      >
                        {v!.name}
                      </Link>
                    </li>
                  ))}
                </ul>
              </Reveal>
            )}
          </aside>
        </div>
      </Section>

      <Section tone="sand">
        <div className="grid gap-12 lg:grid-cols-[0.8fr_1.2fr] lg:gap-20">
          <SectionHead eyebrow="FAQ" title={t.city.faq} />
          <Reveal delay={100}>
            <Accordion items={city.faq.map((f) => ({ q: f.q[l], a: f.a[l] }))} />
          </Reveal>
        </div>
      </Section>

      <Section>
        <SectionHead eyebrow={t.city.neighbours} title={t.city.allCities} />
        <Reveal delay={100}>
          <div className="mt-8 flex flex-wrap gap-2.5">
            {neighbours.map((nb) => {
              const n = nb!.slug;
              return (
                <Link
                  key={n}
                  href={p(`/hochzeitsfotograf/${n}`)}
                  className="border border-sand-deep px-5 py-2.5 text-[0.8rem] text-ink-soft transition-colors hover:border-gold hover:text-gold"
                >
                  {l === "de" ? "Hochzeitsfotograf " : "Düğün fotoğrafçısı "}
                  {nb!.name}
                </Link>
              );
            })}
          </div>
        </Reveal>
      </Section>

      <Section tone="ink">
        <div className="grid gap-14 lg:grid-cols-2 lg:gap-20">
          <div>
            <SectionHead
              eyebrow={t.nav.contact}
              title={`${t.city.cta} ${city.name}`}
              text={t.city.ctaText}
              tone="light"
            />
          </div>
          <Reveal delay={120} className="bg-cream p-8 sm:p-10">
            <ContactForm locale={l} preset={city.name} />
          </Reveal>
        </div>
      </Section>

      <JsonLd
        data={[
          serviceLd({
            locale: l,
            name: h1,
            description: city.lead[l],
            area: city.name,
            path: `/hochzeitsfotograf/${city.slug}`,
          }),
          faqLd(city.faq.map((f) => ({ q: f.q[l], a: f.a[l] }))),
          breadcrumbLd(l, [
            { name: "Home", path: "/" },
            { name: t.city.allCities, path: "/regionen" },
            { name: city.name, path: `/hochzeitsfotograf/${city.slug}` },
          ]),
        ]}
      />
    </>
  );
}
