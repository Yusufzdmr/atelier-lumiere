import type { Metadata } from "next";
import Link from "next/link";

import PageHero from "@/components/PageHero";
import Reveal from "@/components/Reveal";
import JsonLd from "@/components/JsonLd";
import { Section, Photo, Breadcrumbs } from "@/components/ui";
import { getVenues } from "@/lib/cms";
import { getDict } from "@/lib/dict";
import { breadcrumbLd, pageMeta } from "@/lib/seo";
import { locales, isLocale, type Locale } from "@/lib/i18n";

export function generateStaticParams() {
  return locales.map((locale) => ({ locale }));
}

export async function generateMetadata({ params }: { params: Promise<{ locale: string }> }): Promise<Metadata> {
  const { locale } = await params;
  const l: Locale = isLocale(locale) ? locale : "de";
  return pageMeta({ locale: l, page: "hochzeitslocations" });
}

export default async function VenuesIndex({ params }: { params: Promise<{ locale: string }> }) {
  const { locale } = await params;
  const l: Locale = isLocale(locale) ? locale : "de";
  const t = getDict(l);
  const p = (path: string) => `/${l}${path}`;
  const venues = await getVenues();

  return (
    <>
      <PageHero
        eyebrow={t.home.venuesEyebrow}
        title={l === "de" ? "Hochzeitslocations in der Region Stuttgart" : "Stuttgart bölgesinde düğün mekânları"}
        text={t.venue.lead}
        seed="venues-index"
      />

      <Section>
        <Breadcrumbs items={[{ name: "Home", href: p("") }, { name: t.venue.all }]} />

        <div className="grid gap-8 md:grid-cols-2">
          {venues.map((v, i) => (
            <Reveal key={v.slug} delay={i * 70}>
              <Link href={p(`/hochzeitslocations/${v.slug}`)} className="group block">
                <Photo seed={`venue-${v.slug}`} alt={v.name} ratio="16/10" sizes="(max-width: 768px) 100vw, 50vw" />
                <div className="mt-5 flex items-baseline justify-between gap-4">
                  <h2 className="font-display text-2xl font-light text-ink transition-colors group-hover:text-gold">
                    {v.name}
                  </h2>
                  <span className="shrink-0 text-[0.65rem] uppercase tracking-[0.18em] text-gold">{v.type[l]}</span>
                </div>
                <p className="mt-2 text-[0.9rem] leading-relaxed text-muted">{v.lead[l]}</p>
                <div className="mt-3 text-[0.7rem] uppercase tracking-[0.16em] text-muted">
                  {v.city} · {v.capacity[l]}
                </div>
              </Link>
            </Reveal>
          ))}
        </div>

        <Reveal delay={120} className="mt-16 border border-sand-deep bg-sand/40 p-8">
          <p className="text-[0.9rem] leading-relaxed text-muted">
            {l === "de"
              ? "Eure Location ist nicht dabei? Kein Problem – wir fahren vor der Hochzeit einmal hin, prüfen Licht und Wege und stimmen den Zeitplan darauf ab. Das ist in allen Tagespaketen enthalten."
              : "Mekânınız listede yok mu? Sorun değil – düğünden önce bir kez gidip ışığı ve güzergâhı kontrol ediyor, zaman planını ona göre ayarlıyoruz. Bu, tüm tam gün paketlere dahildir."}
          </p>
          <Link
            href={p("/kontakt")}
            className="mt-6 inline-block bg-ink px-7 py-3 text-[0.68rem] uppercase tracking-[0.2em] text-cream hover:bg-gold"
          >
            {t.nav.cta}
          </Link>
        </Reveal>
      </Section>

      <JsonLd
        data={breadcrumbLd(l, [
          { name: "Home", path: "/" },
          { name: t.venue.all, path: "/hochzeitslocations" },
        ])}
      />
    </>
  );
}
