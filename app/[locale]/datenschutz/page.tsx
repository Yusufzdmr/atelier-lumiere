import type { Metadata } from "next";
import { Section } from "@/components/ui";
import LegalBody from "@/components/LegalBody";
import { getLegal } from "@/lib/cms";
import { meta } from "@/lib/seo";
import { locales, isLocale, type Locale } from "@/lib/i18n";

export function generateStaticParams() {
  return locales.map((locale) => ({ locale }));
}

export async function generateMetadata({ params }: { params: Promise<{ locale: string }> }): Promise<Metadata> {
  const { locale } = await params;
  const l: Locale = isLocale(locale) ? locale : "de";
  return meta({
    locale: l,
    path: "/datenschutz",
    title: (await getLegal()).datenschutz.title,
    description: "Informationen zur Verarbeitung personenbezogener Daten nach Art. 13 DSGVO.",
  });
}

export default async function Datenschutz() {
  return (
    <Section className="pt-36">
      <LegalBody page={(await getLegal()).datenschutz} />
    </Section>
  );
}
