import type { Metadata } from "next";
import Link from "next/link";

import PageHero from "@/components/PageHero";
import Reveal from "@/components/Reveal";
import JsonLd from "@/components/JsonLd";
import { Section, Photo, Breadcrumbs } from "@/components/ui";
import { getPosts } from "@/lib/cms";
import { getDict } from "@/lib/dict";
import { breadcrumbLd, pageMeta } from "@/lib/seo";
import { locales, isLocale, dateLocale, type Locale } from "@/lib/i18n";

export function generateStaticParams() {
  return locales.map((locale) => ({ locale }));
}

export async function generateMetadata({ params }: { params: Promise<{ locale: string }> }): Promise<Metadata> {
  const { locale } = await params;
  const l: Locale = isLocale(locale) ? locale : "de";
  const t = getDict(l);
  return pageMeta({ locale: l, page: "ratgeber", fallback: { description: t.blog.lead } });
}

export default async function BlogPage({ params }: { params: Promise<{ locale: string }> }) {
  const { locale } = await params;
  const l: Locale = isLocale(locale) ? locale : "de";
  const t = getDict(l);
  const posts = await getPosts();
  const p = (path: string) => `/${l}${path}`;

  return (
    <>
      <PageHero eyebrow={t.blog.nav} title={t.blog.title} text={t.blog.lead} seed="lum-blog-hero" />

      <Section>
        <Breadcrumbs items={[{ name: "Home", href: p("") }, { name: t.blog.title }]} />

        {posts.length === 0 ? (
          <p className="text-muted">{t.blog.empty}</p>
        ) : (
          <div className="grid gap-12 md:grid-cols-2 lg:gap-x-16">
            {posts.map((post, i) => (
              <Reveal key={post.slug} delay={(i % 2) * 90}>
                <Link href={p(`/ratgeber/${post.slug}`)} className="group block">
                  <Photo
                    seed={post.uploads?.[0] ?? post.seed}
                    alt={post.title[l]}
                    ratio="3/2"
                    sizes="(max-width: 768px) 100vw, 45vw"
                  />
                  <time
                    dateTime={post.date}
                    className="mt-5 block text-[0.66rem] uppercase tracking-[0.2em] text-gold"
                  >
                    {new Date(post.date).toLocaleDateString(dateLocale[l], { dateStyle: "long" })}
                  </time>
                  <h2 className="font-display mt-2 text-2xl font-normal leading-snug text-ink transition-colors group-hover:text-gold">
                    {post.title[l]}
                  </h2>
                  <p className="mt-3 text-[0.92rem] leading-relaxed text-muted">{post.excerpt[l]}</p>
                  <span className="link-underline mt-4 inline-block text-[0.68rem] uppercase tracking-[0.2em] text-ink">
                    {t.blog.readMore} →
                  </span>
                </Link>
              </Reveal>
            ))}
          </div>
        )}
      </Section>

      <JsonLd
        data={breadcrumbLd(l, [
          { name: "Home", path: "/" },
          { name: t.blog.title, path: "/ratgeber" },
        ])}
      />
    </>
  );
}
