import Image from "next/image";
import Link from "next/link";
import type { Metadata } from "next";

import Reveal from "@/components/Reveal";
import JsonLd from "@/components/JsonLd";
import { Section, SectionHead, Btn, Photo, Stat, Accordion } from "@/components/ui";
import { getDict } from "@/lib/dict";
import { img, blurData } from "@/lib/images";

import { getVenues, getCities, getStories } from "@/lib/cms";
import { meta, faqLd, breadcrumbLd } from "@/lib/seo";
import { getContent, getServices, getProcess, getTestimonials, getFaq, getAbout } from "@/lib/cms";
import { isLocale, type Locale } from "@/lib/i18n";

export async function generateMetadata({ params }: { params: Promise<{ locale: string }> }): Promise<Metadata> {
  const { locale } = await params;
  const l: Locale = isLocale(locale) ? locale : "de";
  return meta({
    locale: l,
    path: "/",
    title:
      l === "de"
        ? "Hochzeitsfotograf Stuttgart – Foto & Video | Atelier Lumière"
        : "Stuttgart Düğün Fotoğrafçısı – Foto & Video | Atelier Lumière",
    description:
      l === "de"
        ? "Dokumentarische Hochzeitsfotografie und Hochzeitsfilm in Stuttgart, Ludwigsburg, Esslingen und Umgebung. Private Kundengalerie mit Album-Auswahl und digitale Hochzeitseinladung inklusive."
        : "Stuttgart, Ludwigsburg, Esslingen ve çevresinde belgesel tarzda düğün fotoğrafçılığı ve düğün filmi. Albüm seçimli özel müşteri galerisi ve dijital düğün davetiyesi dahil.",
  });
}

export default async function Home({ params }: { params: Promise<{ locale: string }> }) {
  const { locale } = await params;
  const l: Locale = isLocale(locale) ? locale : "de";
  const t = getDict(l);
  const cms = await getContent();
  const services = await getServices();
  const processSteps = await getProcess();
  const testimonials = await getTestimonials();
  const faqGeneral = await getFaq();
  const about = await getAbout();
  const venues = await getVenues();
  const cities = await getCities();
  const stories = await getStories();
  const p = (path: string) => `/${l}${path}`;

  return (
    <>
      {/* ---------------- Hero ---------------- */}
      <section className="relative h-[100svh] min-h-[600px] w-full overflow-hidden">
        <div className="absolute inset-0 animate-kenburns">
          <Image
            src={img("lumiere-hero-main", 1920, 1280)}
            alt="Hochzeitsfotografie Stuttgart"
            fill
            priority
            sizes="100vw"
            placeholder="blur"
            blurDataURL={blurData}
            className="object-cover"
          />
        </div>
        <div className="absolute inset-0 bg-gradient-to-b from-ink/55 via-ink/25 to-ink/75" />

        <div className="relative z-10 mx-auto flex h-full max-w-7xl flex-col justify-center px-5 sm:px-8">
          <div className="anim-up eyebrow text-gold-soft" style={{ animationDelay: "0.2s" }}>
            {cms.hero.eyebrow[l]}
          </div>
          <h1
            className="anim-up headline mt-6 max-w-3xl text-5xl text-cream sm:text-6xl md:text-7xl"
            style={{ whiteSpace: "pre-line", animationDelay: "0.4s" }}
          >
            {cms.hero.title[l]}
          </h1>
          <p className="anim-up mt-7 max-w-xl text-[1rem] leading-relaxed text-cream/75" style={{ animationDelay: "0.65s" }}>
            {cms.hero.text[l]}
          </p>
          <div className="anim-up mt-10 flex flex-wrap gap-3" style={{ animationDelay: "0.85s" }}>
            <Btn href={p("/kontakt")} variant="solid" className="!bg-cream !text-ink hover:!bg-gold hover:!text-cream">
              {t.home.heroCta}
            </Btn>
            <Btn href={p("/portfolio")} variant="light">
              {t.home.heroCta2}
            </Btn>
          </div>
        </div>

        <div className="absolute bottom-8 left-1/2 z-10 -translate-x-1/2 text-center">
          <div className="mx-auto h-12 w-px animate-pulse bg-cream/40" />
          <span className="mt-2 block text-[0.58rem] uppercase tracking-[0.3em] text-cream/50">{t.home.scroll}</span>
        </div>
      </section>

      {/* ---------------- Intro + Zahlen ---------------- */}
      <Section>
        <div className="grid gap-14 lg:grid-cols-[1.1fr_1fr] lg:gap-20">
          <div>
            <SectionHead eyebrow={t.home.introEyebrow} title={t.home.introTitle} text={t.home.introText} />
            <Reveal delay={150}>
              <div className="mt-12 grid grid-cols-2 gap-8 sm:grid-cols-4">
                <Stat value={cms.stats.weddings} label={t.home.statsWeddings} />
                <Stat value={cms.stats.years} label={t.home.statsYears} />
                <Stat value={cms.stats.delivery} label={t.home.statsDelivery} />
                <Stat value={cms.stats.rating} label={t.home.statsRating} />
              </div>
            </Reveal>

            {/* Arbeitsweise – gepflegt unter Admin > Über mich & Stimmen */}
            {about.values.length > 0 && (
              <Reveal delay={220} className="mt-12 border-t border-sand-deep pt-11">
                <h3 className="eyebrow">{about.valuesTitle[l]}</h3>
                <div className="mt-7 grid gap-x-10 gap-y-7 sm:grid-cols-2">
                  {about.values.slice(0, 4).map((v, i) => (
                    <div key={i} className="relative pl-5">
                      <span className="absolute left-0 top-[0.62em] h-px w-3 bg-gold" />
                      <h4 className="font-display text-[1.06rem] font-normal leading-snug text-ink">{v.t[l]}</h4>
                      <p className="mt-1.5 text-[0.86rem] leading-relaxed text-muted">{v.d[l]}</p>
                    </div>
                  ))}
                </div>
                <Link
                  href={p("/ueber-mich")}
                  className="link-underline mt-9 inline-block text-[0.68rem] uppercase tracking-[0.2em] text-ink"
                >
                  {t.nav.about} →
                </Link>
              </Reveal>
            )}
          </div>
          <Reveal delay={200} mask>
            <Photo seed="lumiere-intro" alt="Brautpaar Stuttgart" ratio="4/5" sizes="(max-width: 1024px) 100vw, 45vw" />
          </Reveal>
        </div>
      </Section>

      {/* ---------------- Leistungen ---------------- */}
      <Section tone="sand">
        <SectionHead eyebrow={t.home.servicesEyebrow} title={t.home.servicesTitle} />
        <div className="mt-14 grid gap-6 sm:grid-cols-2 lg:grid-cols-4">
          {services.map((s, i) => (
            <Reveal key={s.slug} delay={i * 90}>
              <Link href={p(`/leistungen#${s.slug}`)} className="group block">
                <Photo seed={s.seed} alt={s.title[l]} ratio="4/5" sizes="(max-width: 640px) 100vw, 25vw" />
                <h3 className="font-display mt-5 text-xl font-normal text-ink transition-colors group-hover:text-gold">
                  {s.title[l]}
                </h3>
                <p className="mt-2 text-[0.88rem] leading-relaxed text-muted">{s.short[l]}</p>
              </Link>
            </Reveal>
          ))}
        </div>
      </Section>

      {/* ---------------- Portfolio ---------------- */}
      <Section>
        <div className="flex flex-wrap items-end justify-between gap-6">
          <SectionHead eyebrow={t.home.portfolioEyebrow} title={t.home.portfolioTitle} />
          <Reveal>
            <Link href={p("/portfolio")} className="link-underline text-[0.72rem] uppercase tracking-[0.2em] text-gold">
              {t.home.portfolioAll} →
            </Link>
          </Reveal>
        </div>

        <div className="mt-14 grid gap-8 md:grid-cols-2">
          {stories.map((s, i) => (
            <Reveal key={s.slug} delay={i * 80} className={i % 3 === 0 ? "md:col-span-2" : ""}>
              <Link href={p(`/portfolio/${s.slug}`)} className="group block">
                <Photo
                  seed={s.seeds[0]}
                  alt={`${s.couple} – ${s.venue[l]}`}
                  ratio={i % 3 === 0 ? "16/9" : "4/3"}
                  sizes="(max-width: 768px) 100vw, 50vw"
                />
                <div className="mt-5 flex flex-wrap items-baseline justify-between gap-3">
                  <h3 className="font-display text-2xl font-light text-ink transition-colors group-hover:text-gold">
                    {s.couple}
                  </h3>
                  <span className="text-[0.68rem] uppercase tracking-[0.18em] text-muted">
                    {s.venue[l]} · {s.guests} {t.portfolio.guests}
                  </span>
                </div>
                <p className="mt-2 max-w-2xl text-[0.9rem] leading-relaxed text-muted">{s.intro[l]}</p>
              </Link>
            </Reveal>
          ))}
        </div>
      </Section>

      {/* ---------------- Ablauf ---------------- */}
      <Section tone="sand">
        <SectionHead eyebrow={t.home.processEyebrow} title={t.home.processTitle} />
        <div className="mt-14 grid gap-10 sm:grid-cols-2 lg:grid-cols-4">
          {processSteps.map((s, i) => (
            <Reveal key={s.step} delay={i * 100}>
              <div className="font-display text-5xl font-light text-gold/40">{s.step}</div>
              <h3 className="font-display mt-4 text-xl font-normal text-ink">{s.title[l]}</h3>
              <p className="mt-3 text-[0.88rem] leading-relaxed text-muted">{s.text[l]}</p>
            </Reveal>
          ))}
        </div>
      </Section>

      {/* ---------------- Tools: Galerie & Einladung ---------------- */}
      <Section tone="ink">
        <SectionHead eyebrow={t.home.toolsEyebrow} title={t.home.toolsTitle} tone="light" align="center" />
        <div className="mt-16 grid gap-10 md:grid-cols-2">
          <Reveal>
            <div className="group h-full border border-cream/15 p-8 transition-colors hover:border-gold/50 sm:p-10">
              <Photo seed="lumiere-tool-gallery" alt="Kundengalerie" ratio="16/10" sizes="(max-width: 768px) 100vw, 45vw" />
              <h3 className="font-display mt-7 text-2xl font-light text-cream">{t.home.toolGalleryTitle}</h3>
              <p className="mt-3 text-[0.9rem] leading-relaxed text-cream/60">{t.home.toolGalleryText}</p>
              <Link
                href={p("/galerie")}
                className="mt-7 inline-block border border-cream/40 px-6 py-3 text-[0.68rem] uppercase tracking-[0.2em] text-cream transition-colors hover:bg-cream hover:text-ink"
              >
                {t.home.toolGalleryCta}
              </Link>
            </div>
          </Reveal>
          <Reveal delay={120}>
            <div className="group h-full border border-cream/15 p-8 transition-colors hover:border-gold/50 sm:p-10">
              <Photo seed="lumiere-tool-invite" alt="Digitale Einladung" ratio="16/10" sizes="(max-width: 768px) 100vw, 45vw" />
              <h3 className="font-display mt-7 text-2xl font-light text-cream">{t.home.toolInviteTitle}</h3>
              <p className="mt-3 text-[0.9rem] leading-relaxed text-cream/60">{t.home.toolInviteText}</p>
              <Link
                href={p("/einladung")}
                className="mt-7 inline-block border border-cream/40 px-6 py-3 text-[0.68rem] uppercase tracking-[0.2em] text-cream transition-colors hover:bg-cream hover:text-ink"
              >
                {t.home.toolInviteCta}
              </Link>
            </div>
          </Reveal>
        </div>
      </Section>

      {/* ---------------- Locations ---------------- */}
      <Section>
        <SectionHead eyebrow={t.home.venuesEyebrow} title={t.home.venuesTitle} text={t.home.venuesText} />
        <div className="mt-12 grid gap-5 sm:grid-cols-2 lg:grid-cols-3">
          {venues.slice(0, 6).map((v, i) => (
            <Reveal key={v.slug} delay={i * 70}>
              <Link
                href={p(`/hochzeitslocations/${v.slug}`)}
                className="group flex h-full flex-col justify-between border border-sand-deep p-6 transition-colors hover:border-gold"
              >
                <div>
                  <div className="text-[0.62rem] uppercase tracking-[0.2em] text-gold">{v.type[l]}</div>
                  <h3 className="font-display mt-3 text-xl font-normal text-ink">{v.name}</h3>
                  <p className="mt-2 text-[0.85rem] leading-relaxed text-muted">{v.city}</p>
                </div>
                <span className="mt-6 text-[0.68rem] uppercase tracking-[0.18em] text-muted transition-colors group-hover:text-gold">
                  {t.common.readMore} →
                </span>
              </Link>
            </Reveal>
          ))}
        </div>
        <Reveal delay={150} className="mt-8">
          <Link href={p("/hochzeitslocations")} className="link-underline text-[0.72rem] uppercase tracking-[0.2em] text-gold">
            {t.venue.all} →
          </Link>
        </Reveal>
      </Section>

      {/* ---------------- Regionen ---------------- */}
      <Section tone="sand">
        <SectionHead eyebrow={t.home.citiesEyebrow} title={t.home.citiesTitle} text={t.home.citiesText} />
        <Reveal delay={120}>
          <div className="mt-10 flex flex-wrap gap-2.5">
            {cities.map((c) => (
              <Link
                key={c.slug}
                href={p(`/hochzeitsfotograf/${c.slug}`)}
                className="border border-sand-deep bg-cream px-5 py-2.5 text-[0.8rem] text-ink-soft transition-colors hover:border-gold hover:text-gold"
              >
                {c.name}
              </Link>
            ))}
          </div>
        </Reveal>
      </Section>

      {/* ---------------- Stimmen ---------------- */}
      <Section>
        <SectionHead eyebrow={t.home.testimonialsEyebrow} title={t.home.testimonialsTitle} align="center" />
        <div className="mt-14 grid gap-10 md:grid-cols-3">
          {testimonials.map((r, i) => (
            <Reveal key={r.name} delay={i * 110}>
              <div className="flex h-full flex-col">
                <div className="font-display text-4xl leading-none text-gold/50">&ldquo;</div>
                <p className="font-display mt-3 flex-1 text-lg font-light leading-relaxed text-ink">{r.text[l]}</p>
                <div className="mt-6 text-[0.68rem] uppercase tracking-[0.18em] text-muted">
                  {r.name} · {r.city[l]}
                </div>
              </div>
            </Reveal>
          ))}
        </div>
      </Section>

      {/* ---------------- FAQ ---------------- */}
      <Section tone="sand">
        <div className="grid gap-12 lg:grid-cols-[0.8fr_1.2fr] lg:gap-20">
          <SectionHead eyebrow={t.home.faqEyebrow} title={t.home.faqTitle} />
          <Reveal delay={100}>
            <Accordion items={faqGeneral.map((f) => ({ q: f.q[l], a: f.a[l] }))} />
          </Reveal>
        </div>
      </Section>

      {/* ---------------- CTA ---------------- */}
      <Section tone="ink" className="relative overflow-hidden">
        <div className="relative z-10 mx-auto max-w-2xl text-center">
          <SectionHead eyebrow="Kontakt" title={t.home.ctaTitle} text={t.home.ctaText} align="center" tone="light" />
          <Reveal delay={150} className="mt-10">
            <Btn href={p("/kontakt")} variant="light">
              {t.home.ctaButton}
            </Btn>
          </Reveal>
        </div>
      </Section>

      <JsonLd
        data={[
          faqLd(faqGeneral.map((f) => ({ q: f.q[l], a: f.a[l] }))),
          breadcrumbLd(l, [{ name: "Home", path: "/" }]),
        ]}
      />
    </>
  );
}
