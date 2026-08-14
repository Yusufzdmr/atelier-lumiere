import type { Metadata } from "next";
import { Section } from "@/components/ui";
import LegalBody from "@/components/LegalBody";
import { getLegal } from "@/lib/cms";
import { pageMeta } from "@/lib/seo";
import { locales, isLocale, type Locale } from "@/lib/i18n";

export function generateStaticParams() {
  return locales.map((locale) => ({ locale }));
}

export async function generateMetadata({ params }: { params: Promise<{ locale: string }> }): Promise<Metadata> {
  const { locale } = await params;
  const l: Locale = isLocale(locale) ? locale : "de";
  return pageMeta({
    locale: l,
    page: "agb",
    fallback: {
      title: "AGB",
      description:
        "Allgemeine Geschäftsbedingungen für Foto- und Filmaufträge sowie digitale Produkte.",
    },
  });
}

export default async function AGB() {
  return (
    <Section className="pt-36">
      <LegalBody page={(await getLegal()).agb} />
    </Section>
  );
}
