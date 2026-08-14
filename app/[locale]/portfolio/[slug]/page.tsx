import type { Metadata } from "next";
import Link from "next/link";
import { notFound } from "next/navigation";

import PageHero from "@/components/PageHero";
import Reveal from "@/components/Reveal";
import JsonLd from "@/components/JsonLd";
import VideoEmbed from "@/components/VideoEmbed";
import { Section, Photo, Breadcrumbs, Btn } from "@/components/ui";
import { stories } from "@/lib/content";
import { getVenue, getStory, getStories, getCity } from "@/lib/cms";

import { getDict } from "@/lib/dict";
import { img } from "@/lib/images";
import { breadcrumbLd, articleLd, templateMeta } from "@/lib/seo";
import { locales, isLocale, type Locale } from "@/lib/i18n";

export function generateStaticParams() {
  return locales.flatMap((locale) => stories.map((s) => ({ locale, slug: s.slug })));
}

export async function generateMetadata({
  params,
}: {
  params: Promise<{ locale: string; slug: string }>;
}): Promise<Metadata> {
  const { locale, slug } = await params;
  const l: Locale = isLocale(locale) ? locale : "de";
  const s = await getStory(slug);
  if (!s) return {};
  return templateMeta({
    locale: l,
    kind: "story",
    path: `/portfolio/${s.slug}`,
    vars: { couple: s.couple, venue: s.venue[l] },
    title: `${s.couple} – ${s.venue[l]}`,
    description: s.intro[l].slice(0, 158),
    image: img(s.seeds[0], 1200, 630),
  });
}

export default async function StoryPage({ params }: { params: Promise<{ locale: string; slug: string }> }) {
  const { locale, slug } = await params;
  const l: Locale = isLocale(locale) ? locale : "de";
  const s = await getStory(slug);
  if (!s) notFound();

  const t = getDict(l);
  const p = (path: string) => `/${l}${path}`;
  const venue = s.venueSlug ? await getVenue(s.venueSlug) : undefined;
  const city = await getCity(s.citySlug);
  const others = (await getStories()).filter((x) => x.slug !== s.slug).slice(0, 3);

  return (
    <>
      <PageHero eyebrow={`${s.venue[l]} · ${s.month[l]}`} title={s.couple} text={s.intro[l]} seed={s.seeds[0]} height="lg" />

      <Section>
        <Breadcrumbs
          items={[
            { name: "Home", href: p("") },
            { name: t.portfolio.title, href: p("/portfolio") },
            { name: s.couple },
          ]}
        />

        <div className="grid gap-14 lg:grid-cols-[1.1fr_0.9fr] lg:gap-20">
          <Reveal className="prose-lux max-w-none">
            {s.body[l].map((par, i) => (
              <p key={i}>{par}</p>
            ))}
            <blockquote className="my-8 border-l-2 border-gold pl-6">
              <p className="font-display text-xl font-light not-italic text-ink">&ldquo;{s.quote[l]}&rdquo;</p>
              <cite className="mt-3 block text-[0.7rem] uppercase not-italic tracking-[0.18em] text-muted">{s.couple}</cite>
            </blockquote>
          </Reveal>

          <Reveal delay={120}>
            <div className="border border-sand-deep bg-sand/40 p-7">
              <dl className="space-y-3 text-sm">
                <div className="flex justify-between gap-4 border-b border-sand-deep/60 pb-3">
                  <dt className="text-muted">{t.portfolio.venue}</dt>
                  <dd className="text-right text-ink">{s.venue[l]}</dd>
                </div>
                <div className="flex justify-between gap-4 border-b border-sand-deep/60 pb-3">
                  <dt className="text-muted">{t.portfolio.month}</dt>
                  <dd className="text-ink">{s.month[l]}</dd>
                </div>
                <div className="flex justify-between gap-4 pb-1">
                  <dt className="text-muted">{t.portfolio.guests}</dt>
                  <dd className="text-ink">{s.guests}</dd>
                </div>
              </dl>

              <div className="mt-6 space-y-2">
                {venue && (
                  <Link
                    href={p(`/hochzeitslocations/${venue.slug}`)}
                    className="block border border-ink px-5 py-3 text-center text-[0.66rem] uppercase tracking-[0.2em] text-ink transition-colors hover:bg-ink hover:text-cream"
                  >
                    {venue.name}
                  </Link>
                )}
                {city && (
                  <Link
                    href={p(`/hochzeitsfotograf/${city.slug}`)}
                    className="block border border-ink px-5 py-3 text-center text-[0.66rem] uppercase tracking-[0.2em] text-ink transition-colors hover:bg-ink hover:text-cream"
                  >
                    {l === "de" ? "Hochzeitsfotograf " : "Düğün fotoğrafçısı "}
                    {city.name}
                  </Link>
                )}
              </div>
            </div>
          </Reveal>
        </div>

        {s.videoUrl && (
          <Reveal delay={80} className="mt-16">
            <h2 className="eyebrow">{t.video.title}</h2>
            <div className="mt-5">
              <VideoEmbed
                url={s.videoUrl}
                locale={l}
                poster={img((s.uploads?.length ? s.uploads : s.seeds)[0], 1280, 720)}
                title={`${s.couple} – ${t.video.title}`}
              />
            </div>
          </Reveal>
        )}

        <div className="mt-16 columns-1 gap-4 sm:columns-2 lg:columns-3">
          {(s.uploads?.length ? s.uploads : s.seeds).map((seed, i) => (
            <Reveal key={i} delay={(i % 3) * 80} className="mb-4 break-inside-avoid">
              <Photo
                seed={seed}
                alt={`${s.couple} – ${i + 1}`}
                ratio={i % 3 === 0 ? "3/4" : i % 2 === 0 ? "1/1" : "4/5"}
                sizes="(max-width: 640px) 100vw, 33vw"
              />
            </Reveal>
          ))}
        </div>
      </Section>

      <Section tone="sand">
        <h2 className="font-display text-2xl font-light text-ink">{t.portfolio.more}</h2>
        <div className="mt-8 grid gap-7 sm:grid-cols-3">
          {others.map((o, i) => (
            <Reveal key={o.slug} delay={i * 80}>
              <Link href={p(`/portfolio/${o.slug}`)} className="group block">
                <Photo seed={o.seeds[1]} alt={o.couple} ratio="4/5" sizes="33vw" />
                <h3 className="font-display mt-4 text-lg text-ink transition-colors group-hover:text-gold">{o.couple}</h3>
                <div className="text-[0.68rem] uppercase tracking-[0.16em] text-muted">{o.venue[l]}</div>
              </Link>
            </Reveal>
          ))}
        </div>
        <Btn href={p("/portfolio")} variant="outline" className="mt-10">
          {t.portfolio.back}
        </Btn>
      </Section>

      <JsonLd
        data={[
          articleLd({
            locale: l,
            title: `${s.couple} – ${s.venue[l]}`,
            description: s.intro[l],
            path: `/portfolio/${s.slug}`,
            image: img(s.seeds[0], 1200, 630),
          }),
          breadcrumbLd(l, [
            { name: "Home", path: "/" },
            { name: t.portfolio.title, path: "/portfolio" },
            { name: s.couple, path: `/portfolio/${s.slug}` },
          ]),
        ]}
      />
    </>
  );
}
