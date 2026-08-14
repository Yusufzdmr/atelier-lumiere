export const locales = ["de", "tr"] as const;
export type Locale = (typeof locales)[number];
export const defaultLocale: Locale = "de";

/** Zweisprachiges Feld / iki dilli alan */
export type L<T = string> = Record<Locale, T>;

export const isLocale = (v: string): v is Locale => (locales as readonly string[]).includes(v);

export const localeMeta: Record<Locale, { label: string; short: string; htmlLang: string; ogLocale: string }> = {
  de: { label: "Deutsch", short: "DE", htmlLang: "de-DE", ogLocale: "de_DE" },
  tr: { label: "Türkçe", short: "TR", htmlLang: "tr-TR", ogLocale: "tr_TR" },
};

/** /de/preise -> /tr/preise */
export function switchLocalePath(pathname: string, next: Locale) {
  const parts = pathname.split("/").filter(Boolean);
  if (parts.length && isLocale(parts[0])) parts[0] = next;
  else parts.unshift(next);
  return "/" + parts.join("/");
}

export const dateLocale: Record<Locale, string> = { de: "de-DE", tr: "tr-TR" };
