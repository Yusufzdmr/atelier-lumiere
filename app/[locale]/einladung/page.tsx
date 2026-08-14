import type { Metadata } from "next";
import Link from "next/link";

import PageHero from "@/components/PageHero";
import Reveal from "@/components/Reveal";
import InviteBuilder from "@/components/InviteBuilder";
import { Section, Breadcrumbs } from "@/components/ui";
import { getDict } from "@/lib/dict";
import { meta, breadcrumbLd, offerLd } from "@/lib/seo";
import JsonLd from "@/components/JsonLd";
import { locales, isLocale, type Locale } from "@/lib/i18n";

export function generateStaticParams() {
  return locales.map((locale) => ({ locale }));
}

export async function generateMetadata({ params }: { params: Promise<{ locale: string }> }): Promise<Metadata> {
  const { locale } = await params;
  const l: Locale = isLocale(locale) ? locale : "de";
  return meta({
    locale: l,
    path: "/einladung",
    title:
      l === "de"
        ? "Digitale Hochzeitseinladung erstellen – mit RSVP & Countdown"
        : "Dijital düğün davetiyesi oluştur – RSVP ve geri sayımlı",
    description:
      l === "de"
        ? "Eigene Einladungsseite in drei Minuten: Countdown, Google-Maps-Route, WhatsApp-Versand und Zusagen. Für Hochzeitspaare von Atelier Lumière kostenlos."
        : "Üç dakikada kendi davetiye sayfanız: geri sayım, Google Maps yol tarifi, WhatsApp paylaşımı ve katılım bildirimi. Atelier Lumière çiftlerine ücretsiz.",
  });
}

export default async function InviteBuilderPage({ params }: { params: Promise<{ locale: string }> }) {
  const { locale } = await params;
  const l: Locale = isLocale(locale) ? locale : "de";
  const t = getDict(l);
  const p = (path: string) => `/${l}${path}`;

  const features =
    l === "de"
      ? [
          ["Eigener Link", "website.de/einladung/ayse-mehmet"],
          ["Countdown", "Live-Countdown bis zur Trauung"],
          ["Google Maps", "Ein Klick zur Route"],
          ["WhatsApp", "Direkt an alle Gäste versenden"],
          ["RSVP", "Zu- und Absagen inklusive Personenzahl"],
          ["Vier Designs", "Klassisch, botanisch, modern, gold"],
        ]
      : [
          ["Kendi linkiniz", "website.de/einladung/ayse-mehmet"],
          ["Geri sayım", "Nikaha kadar canlı geri sayım"],
          ["Google Maps", "Tek tıkla yol tarifi"],
          ["WhatsApp", "Tüm misafirlere anında gönderim"],
          ["RSVP", "Kişi sayısıyla katılım bildirimi"],
          ["Dört tasarım", "Klasik, botanik, modern, altın"],
        ];

  return (
    <>
      <PageHero eyebrow={t.nav.invitation} title={t.invite.title} text={t.invite.lead} seed="invite-hero" />

      <Section>
        <Breadcrumbs items={[{ name: "Home", href: p("") }, { name: t.invite.title }]} />

        <Reveal className="mb-14 border-l-2 border-gold bg-sand/40 p-6">
          <p className="text-[0.92rem] leading-relaxed text-ink">{t.invite.freeNote}</p>
          <p className="mt-2 text-[0.82rem] text-muted">
            {l === "de" ? "Demo-Code: " : "Demo kodu: "}
            <code className="text-gold">lumiere2026</code>
          </p>
        </Reveal>

        <InviteBuilder locale={l} />
      </Section>

      <Section tone="sand">
        <h2 className="headline text-3xl sm:text-4xl">
          {l === "de" ? "Was die Einladung kann" : "Davetiye neler yapıyor"}
        </h2>
        <div className="mt-12 grid gap-8 sm:grid-cols-2 lg:grid-cols-3">
          {features.map(([title, text], i) => (
            <Reveal key={title} delay={i * 70}>
              <div className="text-[0.68rem] uppercase tracking-[0.2em] text-gold">{title}</div>
              <p className="mt-2 text-[0.92rem] leading-relaxed text-muted">{text}</p>
            </Reveal>
          ))}
        </div>

        <Reveal delay={150} className="mt-14">
          <div className="text-[0.68rem] uppercase tracking-[0.2em] text-muted">
            {l === "de" ? "Beispiele ansehen" : "Örneklere bakın"}
          </div>
          <div className="mt-4 flex flex-wrap gap-3">
            <Link
              href={p("/einladung/ayse-mehmet")}
              className="border border-sand-deep bg-cream px-5 py-2.5 text-[0.82rem] text-ink-soft transition-colors hover:border-gold hover:text-gold"
            >
              Ayşe &amp; Mehmet
            </Link>
            <Link
              href={p("/einladung/lena-jonas")}
              className="border border-sand-deep bg-cream px-5 py-2.5 text-[0.82rem] text-ink-soft transition-colors hover:border-gold hover:text-gold"
            >
              Lena &amp; Jonas
            </Link>
          </div>
        </Reveal>
      </Section>

      <JsonLd
        data={[
          offerLd({
            name: l === "de" ? "Digitale Hochzeitseinladung" : "Dijital düğün davetiyesi",
            price: "79",
            description:
              l === "de"
                ? "Persönliche Einladungsseite mit Countdown, Karte, WhatsApp-Versand und RSVP."
                : "Geri sayım, harita, WhatsApp paylaşımı ve RSVP içeren kişisel davetiye sayfası.",
          }),
          breadcrumbLd(l, [
            { name: "Home", path: "/" },
            { name: t.invite.title, path: "/einladung" },
          ]),
        ]}
      />
    </>
  );
}
