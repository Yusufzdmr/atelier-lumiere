import type { Metadata } from "next";

import PageHero from "@/components/PageHero";
import Reveal from "@/components/Reveal";
import JsonLd from "@/components/JsonLd";
import ContactForm from "@/components/ContactForm";
import ContactMap from "@/components/ContactMap";
import { Section, Breadcrumbs } from "@/components/ui";
import { getDict } from "@/lib/dict";
import { site } from "@/lib/site";
import { getContent } from "@/lib/cms";
import { breadcrumbLd, pageMeta } from "@/lib/seo";
import { locales, isLocale, type Locale } from "@/lib/i18n";

export function generateStaticParams() {
  return locales.map((locale) => ({ locale }));
}

export async function generateMetadata({ params }: { params: Promise<{ locale: string }> }): Promise<Metadata> {
  const { locale } = await params;
  const l: Locale = isLocale(locale) ? locale : "de";
  return pageMeta({ locale: l, page: "kontakt" });
}

export default async function ContactPage({ params }: { params: Promise<{ locale: string }> }) {
  const { locale } = await params;
  const l: Locale = isLocale(locale) ? locale : "de";
  const t = getDict(l);
  const c = (await getContent()).contact;
  const fullAddress = `${c.street}, ${c.zip} ${c.city}`;
  const p = (path: string) => `/${l}${path}`;

  return (
    <>
      <PageHero eyebrow={t.nav.contact} title={t.contact.title} text={t.contact.lead} seed="contact-hero" />

      <Section>
        <Breadcrumbs items={[{ name: "Home", href: p("") }, { name: t.contact.title }]} />

        <div className="grid gap-14 lg:grid-cols-[1.15fr_0.85fr] lg:gap-20">
          <Reveal>
            <ContactForm locale={l} />
          </Reveal>

          <Reveal delay={120}>
            <div className="border border-sand-deep bg-sand/40 p-8">
              <h2 className="text-[0.68rem] uppercase tracking-[0.22em] text-gold">{t.contact.directTitle}</h2>
              <div className="mt-6 space-y-5 text-[0.95rem]">
                <div>
                  <div className="text-[0.66rem] uppercase tracking-[0.18em] text-muted">Telefon</div>
                  <a href={`tel:${c.phone}`} className="mt-1 block text-ink hover:text-gold">
                    {c.phoneHuman}
                  </a>
                </div>
                <div>
                  <div className="text-[0.66rem] uppercase tracking-[0.18em] text-muted">E-Mail</div>
                  <a href={`mailto:${c.email}`} className="mt-1 block text-ink hover:text-gold">
                    {c.email}
                  </a>
                </div>
                <div>
                  <div className="text-[0.66rem] uppercase tracking-[0.18em] text-muted">WhatsApp</div>
                  <a
                    href={`https://wa.me/${site.whatsapp}`}
                    target="_blank"
                    rel="noopener noreferrer"
                    className="mt-1 block text-ink hover:text-gold"
                  >
                    {c.phoneHuman}
                  </a>
                </div>
                <div>
                  <div className="text-[0.66rem] uppercase tracking-[0.18em] text-muted">{t.contact.studio}</div>
                  <address className="mt-1 not-italic text-ink">
                    {c.street}
                    <br />
                    {c.zip} {c.city}
                  </address>
                </div>
                <div>
                  <div className="text-[0.66rem] uppercase tracking-[0.18em] text-muted">{t.contact.hours}</div>
                  <div className="mt-1 text-ink">{c.hours[l]}</div>
                </div>
              </div>
            </div>

            {/* Karte lädt erst nach Einwilligung – siehe components/ContactMap.tsx */}
            <ContactMap locale={l} address={fullAddress} query={c.mapsQuery || fullAddress} />
          </Reveal>
        </div>
      </Section>

      <JsonLd
        data={breadcrumbLd(l, [
          { name: "Home", path: "/" },
          { name: t.contact.title, path: "/kontakt" },
        ])}
      />
    </>
  );
}
