import type { Metadata, Viewport } from "next";
import { notFound } from "next/navigation";
import "../globals.css";

import Header from "@/components/Header";
import Footer from "@/components/Footer";
import CookieConsent from "@/components/CookieConsent";
import JsonLd from "@/components/JsonLd";
import { localBusinessLd } from "@/lib/seo";
import { site } from "@/lib/site";
import { locales, isLocale, localeMeta, type Locale } from "@/lib/i18n";

export const viewport: Viewport = {
  themeColor: "#FAF7F2",
  width: "device-width",
  initialScale: 1,
};

export function generateStaticParams() {
  return locales.map((locale) => ({ locale }));
}

export async function generateMetadata({ params }: { params: Promise<{ locale: string }> }): Promise<Metadata> {
  const { locale } = await params;
  const l: Locale = isLocale(locale) ? locale : "de";
  const title =
    l === "de"
      ? "Hochzeitsfotograf Stuttgart | Foto & Video | Atelier Lumière"
      : "Stuttgart Düğün Fotoğrafçısı | Foto & Video | Atelier Lumière";
  const description =
    l === "de"
      ? "Hochzeitsfotograf & Videograf in Stuttgart und Umgebung. Dokumentarische Reportagen, Hochzeitsfilm, private Kundengalerie und digitale Einladung."
      : "Stuttgart ve çevresinde düğün fotoğrafçısı ve videografı. Belgesel tarzda çekim, düğün filmi, özel müşteri galerisi ve dijital davetiye.";

  return {
    metadataBase: new URL(site.url),
    title: { default: title, template: `%s | ${site.name}` },
    description,
    applicationName: site.name,
    authors: [{ name: site.owner }],
    creator: site.owner,
    formatDetection: { telephone: true, address: true, email: true },
  };
}

export default async function LocaleLayout({
  children,
  params,
}: {
  children: React.ReactNode;
  params: Promise<{ locale: string }>;
}) {
  const { locale } = await params;
  if (!isLocale(locale)) notFound();

  return (
    <html lang={localeMeta[locale].htmlLang}>
      <body className="min-h-screen antialiased">
        <a
          href="#main"
          className="sr-only focus:not-sr-only focus:absolute focus:left-4 focus:top-4 focus:z-[100] focus:bg-ink focus:px-4 focus:py-2 focus:text-cream"
        >
          Skip
        </a>
        <Header locale={locale} />
        <main id="main">{children}</main>
        <Footer locale={locale} />
        <CookieConsent locale={locale} />
        <JsonLd data={localBusinessLd(locale)} />
      </body>
    </html>
  );
}
