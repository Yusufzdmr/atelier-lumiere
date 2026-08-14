import type { Metadata } from "next";
import Link from "next/link";

import AdminLogin from "@/components/admin/AdminLogin";
import { isAdmin, logout } from "@/lib/actions";
import { isLocale, type Locale } from "@/lib/i18n";

export const dynamic = "force-dynamic";
export const metadata: Metadata = { title: "Admin", robots: { index: false, follow: false } };

export default async function AdminLayout({
  children,
  params,
}: {
  children: React.ReactNode;
  params: Promise<{ locale: string }>;
}) {
  const { locale } = await params;
  const l: Locale = isLocale(locale) ? locale : "de";

  if (!(await isAdmin())) {
    return (
      <div className="pt-28">
        <AdminLogin locale={l} />
      </div>
    );
  }

  const nav = [
    { href: "", de: "Übersicht", tr: "Genel bakış" },
    { href: "/inhalte", de: "Texte & Kontakt", tr: "Metinler & iletişim" },
    { href: "/pakete", de: "Preise & Pakete", tr: "Fiyatlar & paketler" },
    { href: "/leistungen", de: "Leistungen & Ablauf", tr: "Hizmetler & süreç" },
    { href: "/sehirler", de: "Städte", tr: "Şehirler" },
    { href: "/mekanlar", de: "Locations", tr: "Mekânlar" },
    { href: "/portfolyo", de: "Portfolio", tr: "Portfolyo" },
    { href: "/ratgeber", de: "Ratgeber", tr: "Rehber" },
    { href: "/kunden", de: "Kunden", tr: "Müşteriler" },
    { href: "/galerien", de: "Kundengalerien", tr: "Müşteri galerileri" },
    { href: "/einladungen", de: "Einladungen", tr: "Davetiyeler" },
    { href: "/ueber-mich", de: "Über mich & Stimmen", tr: "Hakkımda & yorumlar" },
    { href: "/rechtliches", de: "Rechtstexte", tr: "Yasal metinler" },
    { href: "/seo", de: "SEO & Meta", tr: "SEO & meta" },
    { href: "/integrationen", de: "Integrationen", tr: "Entegrasyonlar" },
  ];

  return (
    <div className="min-h-screen bg-cream pt-24">
      <div className="mx-auto max-w-7xl px-5 sm:px-8">
        <div className="flex flex-wrap items-center justify-between gap-4 border-b border-sand-deep pb-5">
          <div>
            <div className="eyebrow">Atelier Lumière</div>
            <h1 className="font-display mt-1 text-2xl font-light text-ink">
              {l === "de" ? "Verwaltung" : "Yönetim"}
            </h1>
          </div>
          <div className="flex items-center gap-4">
            <Link href={`/${l}`} className="text-[0.68rem] uppercase tracking-[0.18em] text-muted hover:text-gold">
              {l === "de" ? "Zur Website" : "Siteye dön"} ↗
            </Link>
            <form action={logout}>
              <input type="hidden" name="locale" value={l} />
              <button className="border border-ink px-5 py-2.5 text-[0.66rem] uppercase tracking-[0.18em] text-ink transition-colors hover:bg-ink hover:text-cream">
                {l === "de" ? "Abmelden" : "Çıkış"}
              </button>
            </form>
          </div>
        </div>

        <nav className="mt-6 flex flex-wrap gap-1 border-b border-sand-deep">
          {nav.map((n) => (
            <Link
              key={n.href}
              href={`/${l}/admin${n.href}`}
              className="border-b-2 border-transparent px-4 py-3 text-[0.72rem] uppercase tracking-[0.16em] text-muted transition-colors hover:border-gold hover:text-ink"
            >
              {l === "de" ? n.de : n.tr}
            </Link>
          ))}
        </nav>

        <div className="py-10">{children}</div>
      </div>
    </div>
  );
}
