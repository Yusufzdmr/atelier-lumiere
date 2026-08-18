import type { NextConfig } from "next";

const nextConfig: NextConfig = {
  experimental: {
    // Ohne Blob-Token landen hochgeladene Bilder als Data-URL im Formular.
    // Der Standardwert von 1 MB reicht dafuer nicht; sonst verwerfen die
    // Server Actions den Speichern-Klick stillschweigend.
    serverActions: { bodySizeLimit: "6mb" },
  },
  images: {
    // Demo-Bilder. Im Live-Betrieb werden die Originale des Fotografen
    // über /public oder Vercel Blob ausgeliefert (WebP/AVIF automatisch).
    formats: ["image/avif", "image/webp"],
    remotePatterns: [
      { protocol: "https", hostname: "picsum.photos" },
      { protocol: "https", hostname: "fastly.picsum.photos" },
      { protocol: "https", hostname: "images.unsplash.com" },
      // Im Admin hochgeladene Bilder (Vercel Blob, Region fra1)
      { protocol: "https", hostname: "*.public.blob.vercel-storage.com" },
    ],
  },
  poweredByHeader: false,
  compress: true,
  async redirects() {
    return [{ source: '/', destination: '/de', permanent: false }];
  },
  async headers() {
    return [
      {
        source: "/(.*)",
        headers: [
          { key: "X-Content-Type-Options", value: "nosniff" },
          { key: "X-Frame-Options", value: "SAMEORIGIN" },
          { key: "Referrer-Policy", value: "strict-origin-when-cross-origin" },
          { key: "Permissions-Policy", value: "camera=(), microphone=(), geolocation=()" },
        ],
      },
    ];
  },
};

export default nextConfig;
