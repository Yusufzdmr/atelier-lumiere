"use client";

import Link from "next/link";
import { usePathname } from "next/navigation";
import { useEffect, useState } from "react";
import { site } from "@/lib/site";
import { getDict } from "@/lib/dict";
import { locales, localeMeta, switchLocalePath, type Locale } from "@/lib/i18n";

export default function Header({ locale }: { locale: Locale }) {
  const t = getDict(locale);
  const pathname = usePathname() || `/${locale}`;
  const [open, setOpen] = useState(false);
  const [scrolled, setScrolled] = useState(false);

  useEffect(() => {
    const onScroll = () => setScrolled(window.scrollY > 24);
    onScroll();
    window.addEventListener("scroll", onScroll, { passive: true });
    return () => window.removeEventListener("scroll", onScroll);
  }, []);

  // Menü beim Navigieren schliessen – ohne setState im Effekt
  const [menuPath, setMenuPath] = useState(pathname);
  if (menuPath !== pathname) {
    setMenuPath(pathname);
    if (open) setOpen(false);
  }

  useEffect(() => {
    document.body.style.overflow = open ? "hidden" : "";
    return () => {
      document.body.style.overflow = "";
    };
  }, [open]);

  const p = (path: string) => `/${locale}${path}`;

  const links = [
    { href: p("/leistungen"), label: t.nav.services },
    { href: p("/portfolio"), label: t.nav.portfolio },
    { href: p("/hochzeitslocations"), label: t.nav.locations },
    { href: p("/regionen"), label: t.nav.cities },
    { href: p("/preise"), label: t.nav.prices },
    { href: p("/ueber-mich"), label: t.nav.about },
  ];

  const extra = [
    { href: p("/galerie"), label: t.nav.gallery },
    { href: p("/einladung"), label: t.nav.invitation },
  ];

  return (
    <>
      <header
        className={`fixed inset-x-0 top-0 z-50 transition-all duration-500 ${
          scrolled || open
            ? "bg-cream/95 backdrop-blur-md border-b border-sand-deep/40 py-3"
            : "bg-transparent py-6"
        }`}
      >
        <div className="mx-auto flex max-w-7xl items-center justify-between gap-4 px-5 sm:px-8">
          <Link href={p("")} className="group flex flex-col leading-none" aria-label={site.name}>
            <span className="font-display whitespace-nowrap text-xl font-light tracking-[0.14em] text-ink sm:text-2xl">
              ATELIER LUMIÈRE
            </span>
            <span className="mt-1 hidden text-[0.6rem] uppercase tracking-[0.32em] text-muted xl:block">
              Hochzeitsfotografie · Stuttgart
            </span>
          </Link>

          <nav className="hidden items-center gap-5 lg:flex xl:gap-6">
            {links.map((l) => (
              <Link
                key={l.href}
                href={l.href}
                className={`link-underline whitespace-nowrap text-[0.78rem] uppercase tracking-[0.12em] transition-colors ${
                  pathname === l.href ? "text-gold" : "text-ink-soft hover:text-gold"
                }`}
              >
                {l.label}
              </Link>
            ))}

            {/* Eigene Produkte – bewusst abgesetzt, damit sie auffallen */}
            <span className="h-4 w-px bg-sand-deep" aria-hidden />
            {extra.map((l) => (
              <Link
                key={l.href}
                href={l.href}
                className={`link-underline whitespace-nowrap text-[0.78rem] uppercase tracking-[0.12em] transition-colors ${
                  pathname === l.href ? "text-gold" : "text-gold/80 hover:text-gold"
                }`}
              >
                {l.label}
              </Link>
            ))}
          </nav>

          <div className="flex items-center gap-3">
            <div className="hidden items-center gap-1 sm:flex">
              {locales.map((l) => (
                <Link
                  key={l}
                  href={switchLocalePath(pathname, l)}
                  hrefLang={localeMeta[l].htmlLang}
                  className={`px-1.5 py-1 text-[0.7rem] uppercase tracking-[0.18em] transition-colors ${
                    l === locale ? "text-gold" : "text-muted hover:text-ink"
                  }`}
                >
                  {localeMeta[l].short}
                </Link>
              ))}
            </div>

            <Link
              href={p("/kontakt")}
              className="hidden whitespace-nowrap border border-ink px-5 py-2.5 text-[0.7rem] uppercase tracking-[0.16em] text-ink transition-colors hover:bg-ink hover:text-cream md:inline-block"
            >
              {t.nav.cta}
            </Link>

            <button
              onClick={() => setOpen((v) => !v)}
              aria-label={open ? t.nav.close : t.nav.menu}
              aria-expanded={open}
              className="relative z-50 flex h-10 w-10 flex-col items-center justify-center gap-[5px] lg:hidden"
            >
              <span
                className={`block h-px w-6 bg-ink transition-transform duration-300 ${open ? "translate-y-[6px] rotate-45" : ""}`}
              />
              <span className={`block h-px w-6 bg-ink transition-opacity duration-300 ${open ? "opacity-0" : ""}`} />
              <span
                className={`block h-px w-6 bg-ink transition-transform duration-300 ${open ? "-translate-y-[6px] -rotate-45" : ""}`}
              />
            </button>
          </div>
        </div>
      </header>

      {/* Mobile-Overlay */}
      <div
        className={`fixed inset-0 z-40 bg-cream transition-all duration-500 lg:hidden ${
          open ? "pointer-events-auto opacity-100" : "pointer-events-none opacity-0"
        }`}
      >
        <div className="flex h-full flex-col justify-center px-8 pt-20">
          <nav className="flex flex-col gap-1">
            {[...links, ...extra].map((l, i) => (
              <Link
                key={l.href}
                href={l.href}
                style={{ transitionDelay: `${open ? i * 45 : 0}ms` }}
                className={`font-display border-b border-sand-deep/40 py-4 text-2xl font-light text-ink transition-all duration-500 ${
                  open ? "translate-y-0 opacity-100" : "translate-y-3 opacity-0"
                }`}
              >
                {l.label}
              </Link>
            ))}
          </nav>

          <div className="mt-8 flex items-center justify-between">
            <div className="flex gap-3">
              {locales.map((l) => (
                <Link
                  key={l}
                  href={switchLocalePath(pathname, l)}
                  className={`text-xs uppercase tracking-[0.22em] ${l === locale ? "text-gold" : "text-muted"}`}
                >
                  {localeMeta[l].label}
                </Link>
              ))}
            </div>
            <Link
              href={p("/kontakt")}
              className="bg-ink px-6 py-3 text-[0.72rem] uppercase tracking-[0.2em] text-cream"
            >
              {t.nav.cta}
            </Link>
          </div>
        </div>
      </div>
    </>
  );
}
