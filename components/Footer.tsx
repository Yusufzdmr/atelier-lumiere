import Link from "next/link";
import { site } from "@/lib/site";
import { getContent } from "@/lib/cms";
import { getDict } from "@/lib/dict";
import { getCities } from "@/lib/cms";
import type { Locale } from "@/lib/i18n";

export default async function Footer({ locale }: { locale: Locale }) {
  const t = getDict(locale);
  const c = (await getContent()).contact;
  const cities = await getCities();
  const p = (path: string) => `/${locale}${path}`;

  return (
    <footer className="relative bg-ink text-cream">
      <div className="mx-auto max-w-7xl px-5 py-16 sm:px-8 sm:py-20">
        <div className="grid gap-12 md:grid-cols-4">
          <div className="md:col-span-1">
            <div className="font-display text-2xl font-light tracking-[0.16em]">ATELIER LUMIÈRE</div>
            <p className="mt-4 max-w-xs text-sm leading-relaxed text-cream/60">{t.footer.tagline}</p>
            <div className="mt-6 flex gap-4 text-[0.7rem] uppercase tracking-[0.2em] text-cream/60">
              <a href={c.instagram} className="hover:text-gold" rel="noopener noreferrer" target="_blank">
                Instagram
              </a>
              <a href={site.vimeo} className="hover:text-gold" rel="noopener noreferrer" target="_blank">
                Vimeo
              </a>
            </div>
          </div>

          <div>
            <h3 className="text-[0.7rem] uppercase tracking-[0.24em] text-gold">{t.footer.nav}</h3>
            <ul className="mt-5 space-y-2.5 text-sm text-cream/70">
              {[
                { href: p("/leistungen"), label: t.nav.services },
                { href: p("/portfolio"), label: t.nav.portfolio },
                { href: p("/preise"), label: t.nav.prices },
                { href: p("/hochzeitslocations"), label: t.nav.locations },
                { href: p("/ratgeber"), label: t.blog.nav },
                { href: p("/galerie"), label: t.nav.gallery },
                { href: p("/einladung"), label: t.nav.invitation },
                { href: p("/kontakt"), label: t.nav.contact },
              ].map((l) => (
                <li key={l.href}>
                  <Link href={l.href} className="hover:text-gold">
                    {l.label}
                  </Link>
                </li>
              ))}
            </ul>
          </div>

          <div>
            <h3 className="text-[0.7rem] uppercase tracking-[0.24em] text-gold">{t.footer.regions}</h3>
            <ul className="mt-5 grid grid-cols-1 gap-2.5 text-sm text-cream/70 sm:grid-cols-2 md:grid-cols-1">
              {cities.slice(0, 8).map((c) => (
                <li key={c.slug}>
                  <Link href={p(`/hochzeitsfotograf/${c.slug}`)} className="hover:text-gold">
                    {c.name}
                  </Link>
                </li>
              ))}
            </ul>
          </div>

          <div>
            <h3 className="text-[0.7rem] uppercase tracking-[0.24em] text-gold">{t.footer.contact}</h3>
            <address className="mt-5 space-y-2.5 text-sm not-italic text-cream/70">
              <div>{c.street}</div>
              <div>
                {c.zip} {c.city}
              </div>
              <div className="pt-2">
                <a href={`tel:${c.phone}`} className="hover:text-gold">
                  {c.phoneHuman}
                </a>
              </div>
              <div>
                <a href={`mailto:${c.email}`} className="hover:text-gold">
                  {c.email}
                </a>
              </div>
              <div className="pt-2 text-cream/50">{c.hours[locale]}</div>
            </address>

            <ul className="mt-6 space-y-2 text-sm text-cream/70">
              <li>
                <Link href={p("/impressum")} className="hover:text-gold">
                  Impressum
                </Link>
              </li>
              <li>
                <Link href={p("/datenschutz")} className="hover:text-gold">
                  Datenschutz
                </Link>
              </li>
              <li>
                <Link href={p("/agb")} className="hover:text-gold">
                  AGB
                </Link>
              </li>
            </ul>
          </div>
        </div>

        <div className="mt-14 flex flex-col gap-3 border-t border-cream/10 pt-7 text-xs text-cream/40 sm:flex-row sm:items-center sm:justify-between">
          <div>
            © {new Date().getFullYear()} {site.legalName}. {t.footer.rights}
          </div>
          <div>{t.footer.demo}</div>
        </div>
      </div>
    </footer>
  );
}
