import type { Metadata } from "next";
import Link from "next/link";
import { notFound } from "next/navigation";

import PageHero from "@/components/PageHero";
import Reveal from "@/components/Reveal";
import JsonLd from "@/components/JsonLd";
import { Section, Photo, Breadcrumbs, Btn, Accordion } from "@/components/ui";
import { getPost, getPosts, getCity, getVenue } from "@/lib/cms";
import { posts as defaultPosts } from "@/lib/posts";
import { getDict } from "@/lib/dict";
import { img } from "@/lib/images";
import { meta, articleLd, faqLd, breadcrumbLd } from "@/lib/seo";
import { locales, isLocale, dateLocale, type Locale } from "@/lib/i18n";

export function generateStaticParams() {
  return locales.flatMap((locale) => defaultPosts.map((p) => ({ locale, slug: p.slug })));
}

export async function generateMetadata({
  params,
}: {
  params: Promise<{ locale: string; slug: string }>;
}): Promise<Metadata> {
  const { locale, slug } = await params;
  const l: Locale = isLocale(locale) ? locale : "de";
  const post = await getPost(slug);
  if (!post) return {};

  return meta({
    locale: l,
    path: `/ratgeber/${post.slug}`,
    title: post.title[l],
    description: post.excerpt[l],
    image: img(post.uploads?.[0] ?? post.seed, 1200, 630),
  });
}

export default async function PostPage({ params }: { params: Promise<{ locale: string; slug: string }> }) {
  const { locale, slug } = await params;
  const l: Locale = isLocale(locale) ? locale : "de";
  const t = getDict(l);
  const post = await getPost(slug);
  if (!post) notFound();

  const [city, venue, all] = await Promise.all([
    post.citySlug ? getCity(post.citySlug) : undefined,
    post.venueSlug ? getVenue(post.venueSlug) : undefined,
    getPosts(),
  ]);
  const more = all.filter((x) => x.slug !== post.slug).slice(0, 2);
  const p = (path: string) => `/${l}${path}`;
  const cover = post.uploads?.[0] ?? post.seed;

  return (
    <>
      <PageHero
        eyebrow={new Date(post.date).toLocaleDateString(dateLocale[l], { dateStyle: "long" })}
        title={post.title[l]}
        text={post.excerpt[l]}
        seed={cover}
      />

      <Section>
        <Breadcrumbs
          items={[
            { name: "Home", href: p("") },
            { name: t.blog.title, href: p("/ratgeber") },
            { name: post.title[l] },
          ]}
        />

        <div className="grid gap-14 lg:grid-cols-[1.15fr_0.85fr] lg:gap-20">
          <Reveal className="prose-lux max-w-none">
            {post.body[l].map((par, i) =>
              par.startsWith("## ") ? (
                <h2 key={i}>{par.slice(3)}</h2>
              ) : (
                <p key={i}>{par}</p>
              )
            )}
          </Reveal>

          <div className="space-y-6">
            {post.uploads?.[1] && (
              <Reveal delay={100} mask>
                <Photo seed={post.uploads[1]} alt={post.title[l]} ratio="4/5" sizes="(max-width: 1024px) 100vw, 40vw" />
              </Reveal>
            )}

            {(city || venue) && (
              <Reveal delay={140} className="border border-sand-deep bg-sand/40 p-6">
                <h2 className="text-[0.66rem] uppercase tracking-[0.22em] text-gold">{t.blog.related}</h2>
                <div className="mt-5 space-y-2.5">
                  {city && (
                    <Link
                      href={p(`/hochzeitsfotograf/${city.slug}`)}
                      className="block border border-ink px-5 py-3 text-center text-[0.66rem] uppercase tracking-[0.2em] text-ink transition-colors hover:bg-ink hover:text-cream"
                    >
                      {l === "de" ? "Hochzeitsfotograf " : "Düğün fotoğrafçısı "}
                      {city.name}
                    </Link>
                  )}
                  {venue && (
                    <Link
                      href={p(`/hochzeitslocations/${venue.slug}`)}
                      className="block border border-ink px-5 py-3 text-center text-[0.66rem] uppercase tracking-[0.2em] text-ink transition-colors hover:bg-ink hover:text-cream"
                    >
                      {venue.name}
                    </Link>
                  )}
                </div>
              </Reveal>
            )}
          </div>
        </div>

        {post.faq && post.faq.length > 0 && (
          <div className="mt-20 grid gap-12 lg:grid-cols-[0.8fr_1.2fr] lg:gap-20">
            <h2 className="headline text-3xl sm:text-4xl">{t.blog.faq}</h2>
            <Reveal delay={100}>
              <Accordion items={post.faq.map((f) => ({ q: f.q[l], a: f.a[l] }))} />
            </Reveal>
          </div>
        )}
      </Section>

      {more.length > 0 && (
        <Section tone="sand">
          <h2 className="font-display text-2xl font-light text-ink">{t.blog.all}</h2>
          <div className="mt-10 grid gap-10 sm:grid-cols-2">
            {more.map((x, i) => (
              <Reveal key={x.slug} delay={i * 90}>
                <Link href={p(`/ratgeber/${x.slug}`)} className="group block">
                  <Photo seed={x.uploads?.[0] ?? x.seed} alt={x.title[l]} ratio="3/2" sizes="(max-width: 640px) 100vw, 45vw" />
                  <h3 className="font-display mt-4 text-xl font-normal leading-snug text-ink transition-colors group-hover:text-gold">
                    {x.title[l]}
                  </h3>
                </Link>
              </Reveal>
            ))}
          </div>
          <Btn href={p("/ratgeber")} variant="outline" className="mt-10">
            {t.blog.all}
          </Btn>
        </Section>
      )}

      <JsonLd
        data={[
          articleLd({
            locale: l,
            title: post.title[l],
            description: post.excerpt[l],
            path: `/ratgeber/${post.slug}`,
            image: img(cover, 1200, 630),
            published: post.date,
          }),
          ...(post.faq?.length ? [faqLd(post.faq.map((f) => ({ q: f.q[l], a: f.a[l] })))] : []),
          breadcrumbLd(l, [
            { name: "Home", path: "/" },
            { name: t.blog.title, path: "/ratgeber" },
            { name: post.title[l], path: `/ratgeber/${post.slug}` },
          ]),
        ]}
      />
    </>
  );
}
