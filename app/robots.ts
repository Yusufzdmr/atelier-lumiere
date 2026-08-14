import type { MetadataRoute } from "next";
import { site } from "@/lib/site";

export default function robots(): MetadataRoute.Robots {
  return {
    rules: [
      {
        userAgent: "*",
        allow: "/",
        // Private Bereiche: Kundengalerien, Einladungen und Admin gehören nicht in den Index
        disallow: ["/de/galerie/", "/tr/galerie/", "/de/einladung/", "/tr/einladung/", "/de/admin", "/tr/admin", "/api/"],
      },
    ],
    sitemap: `${site.url}/sitemap.xml`,
    host: site.url,
  };
}
