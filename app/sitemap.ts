import type { MetadataRoute } from "next";
import { site } from "@/lib/site";
import { getCities, getVenues, getStories, getPosts } from "@/lib/cms";
import { locales, localeMeta } from "@/lib/i18n";

/**
 * Die dynamischen Einträge kommen aus dem Admin, nicht aus den Standarddaten –
 * sonst taucht eine neu angelegte Stadtseite oder ein neuer Ratgeber-Beitrag
 * nie in der Sitemap auf. Stündlich neu erzeugt.
 */
export const revalidate = 3600;

/** Statische Seiten mit Priorität und Änderungsfrequenz. */
const staticPaths: { path: string; priority: number; changeFrequency: "weekly" | "monthly" | "yearly" }[] = [
  { path: "", priority: 1, changeFrequency: "weekly" },
  { path: "/leistungen", priority: 0.9, changeFrequency: "monthly" },
  { path: "/preise", priority: 0.9, changeFrequency: "monthly" },
  { path: "/portfolio", priority: 0.8, changeFrequency: "monthly" },
  { path: "/hochzeitslocations", priority: 0.9, changeFrequency: "monthly" },
  { path: "/regionen", priority: 0.8, changeFrequency: "monthly" },
  { path: "/ratgeber", priority: 0.8, changeFrequency: "weekly" },
  { path: "/ueber-mich", priority: 0.6, changeFrequency: "yearly" },
  { path: "/kontakt", priority: 0.8, changeFrequency: "yearly" },
  { path: "/einladung", priority: 0.7, changeFrequency: "monthly" },
  { path: "/impressum", priority: 0.2, changeFrequency: "yearly" },
  { path: "/datenschutz", priority: 0.2, changeFrequency: "yearly" },
  { path: "/agb", priority: 0.2, changeFrequency: "yearly" },
];

export default async function sitemap(): Promise<MetadataRoute.Sitemap> {
  const now = new Date();
  const [cities, venues, stories, posts] = await Promise.all([
    getCities(),
    getVenues(),
    getStories(),
    getPosts(),
  ]);

  const dynamicPaths = [
    ...cities.map((c) => ({ path: `/hochzeitsfotograf/${c.slug}`, priority: 0.95, changeFrequency: "monthly" as const })),
    ...venues.map((v) => ({ path: `/hochzeitslocations/${v.slug}`, priority: 0.9, changeFrequency: "monthly" as const })),
    ...posts.map((p) => ({ path: `/ratgeber/${p.slug}`, priority: 0.7, changeFrequency: "monthly" as const })),
    ...stories.map((s) => ({ path: `/portfolio/${s.slug}`, priority: 0.6, changeFrequency: "yearly" as const })),
  ];

  const all = [...staticPaths, ...dynamicPaths];

  return all.flatMap((entry) =>
    locales.map((locale) => ({
      url: `${site.url}/${locale}${entry.path}`,
      lastModified: now,
      changeFrequency: entry.changeFrequency,
      priority: entry.priority,
      alternates: {
        languages: Object.fromEntries([
          ...locales.map((l) => [localeMeta[l].htmlLang, `${site.url}/${l}${entry.path}`]),
          ["x-default", `${site.url}/de${entry.path}`],
        ]),
      },
    }))
  );
}
