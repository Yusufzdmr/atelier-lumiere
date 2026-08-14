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
  return meta({ locale: l, path: "/impressum", title: (await getLegal()).impressum.title, description: "Impressum gemäß § 5 DDG." });
}

export default async function Impressum() {
  return (
    <Section className="pt-36">
      <LegalBody page={(await getLegal()).impressum} />
    </Section>
  );
}
