import type { Metadata } from "next";

import PageHero from "@/components/PageHero";
import Reveal from "@/components/Reveal";
import JsonLd from "@/components/JsonLd";
import { Section, Photo, Breadcrumbs, Btn } from "@/components/ui";
import VideoEmbed from "@/components/VideoEmbed";
import { img } from "@/lib/images";
import { getServices } from "@/lib/cms";
import { serviceCover } from "@/lib/content";
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

        {/* Kapitelleiste: zeigt auf einen Blick, was es gibt, und springt dorthin.
            Der Gast soll nicht klicken muessen, um zu sehen, was ihn erwartet –
            aber er soll auch nicht scrollen muessen, um es zu finden. */}
        <nav className="sticky top-[4.5rem] z-20 -mx-4 mb-12 border-y border-sand-deep bg-cream/90 px-4 backdrop-blur sm:mx-0 sm:px-0">
          <ul className="flex gap-6 overflow-x-auto py-3.5 [scrollbar-width:none] [&::-webkit-scrollbar]:hidden">
            {services.map((s, i) => (
              <li key={s.slug} className="shrink-0">
                <a
                  href={`#${s.slug}`}
                  className="link-underline text-[0.68rem] uppercase tracking-[0.18em] text-muted transition-colors hover:text-gold"
                >
                  <span className="text-gold">0{i + 1}</span> {s.title[l]}
                </a>
              </li>
            ))}
          </ul>
        </nav>

        <div className="space-y-16 sm:space-y-20">
          {services.map((s, i) => {
            // Beispiele: eigene Aufnahmen, sonst passende Bilder aus dem Bestand.
            // Ein Abschnitt, der nur Text zeigt, beantwortet die Frage
            // "wie sieht das denn aus?" naemlich gar nicht.
            const examples = (s.photos ?? []).filter(Boolean);
            const shots = examples.length ? examples : [0, 1, 2, 3].map((k) => `svc-${s.slug}-${k}`);
            return (
            <div key={s.slug} id={s.slug} className="scroll-mt-28">
              <div className={`grid items-center gap-10 lg:grid-cols-2 lg:gap-20 ${i % 2 ? "lg:[&>*:first-child]:order-2" : ""}`}>
                <Reveal mask>
                  <Photo seed={serviceCover(s)} alt={s.title[l]} ratio="4/5" sizes="(max-width: 1024px) 100vw, 50vw" />
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

              {/* Beispielstrecke */}
              <Reveal className="mt-10">
                <h3 className="text-[0.68rem] uppercase tracking-[0.22em] text-gold">{t.services.examples}</h3>
                <div className="mt-5 grid grid-cols-2 gap-3 sm:grid-cols-4 sm:gap-4">
                  {shots.map((src, k) => (
                    <Photo
                      key={k}
                      seed={src}
                      alt={`${s.title[l]} – ${k + 1}`}
                      ratio="4/5"
                      sizes="(max-width: 640px) 50vw, 25vw"
                      w={600}
                      h={750}
                    />
                  ))}
                </div>
              </Reveal>

              {/* Beispielfilm – nur wenn einer hinterlegt ist */}
              {s.videoUrl && (
                <Reveal className="mt-10">
                  <h3 className="text-[0.68rem] uppercase tracking-[0.22em] text-gold">{t.services.exampleFilm}</h3>
                  <div className="mt-5">
                    <VideoEmbed
                      url={s.videoUrl}
                      locale={l}
                      poster={img(shots[0], 1200, 675)}
                      title={`${s.title[l]} – ${t.services.exampleFilm}`}
                    />
                  </div>
                </Reveal>
              )}
            </div>
            );
          })}
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
