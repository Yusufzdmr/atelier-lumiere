"use client";

import { useRef, useState, useTransition } from "react";
import { useRouter } from "next/navigation";

import { resizeImage } from "@/lib/invite";
import type { Locale } from "@/lib/i18n";

/**
 * Vorschaubild für WhatsApp, Facebook und Co. Ein Bild pro Seite, im Browser
 * auf 1200 px verkleinert – das entspricht dem Format, das die Netzwerke
 * anzeigen (1200 × 630).
 *
 * Der Knopf ist bewusst `type="button"`: Er sitzt in einem Formular, und ein
 * Standardknopf würde beim Klick das ganze Formular abschicken.
 */
export default function SeoImage({
  pageKey,
  locale,
  current,
  upload,
  remove,
}: {
  pageKey: string;
  locale: Locale;
  current: string;
  upload: (key: string, photos: string[]) => Promise<{ ok: boolean; added: number }>;
  remove: (key: string) => Promise<void>;
}) {
  const router = useRouter();
  const fileRef = useRef<HTMLInputElement>(null);
  const [busy, setBusy] = useState(false);
  const [error, setError] = useState("");
  const [pending, startTransition] = useTransition();
  const de = locale === "de";

  async function handle(list: FileList | null) {
    const file = list?.[0];
    if (!file) return;
    setBusy(true);
    setError("");
    try {
      await upload(pageKey, [await resizeImage(file, 1200, 0.82)]);
      router.refresh();
    } catch {
      setError(de ? "Upload fehlgeschlagen." : "Yükleme başarısız.");
    } finally {
      setBusy(false);
      if (fileRef.current) fileRef.current.value = "";
    }
  }

  return (
    <div className="flex flex-wrap items-center gap-4">
      <input ref={fileRef} type="file" accept="image/*" className="hidden" onChange={(e) => handle(e.target.files)} />

      {current && (
        // eslint-disable-next-line @next/next/no-img-element -- Blob-URL, bewusst ohne Optimierung
        <img src={current} alt="" className="h-16 w-28 border border-sand-deep object-cover" />
      )}

      <button
        type="button"
        onClick={() => fileRef.current?.click()}
        disabled={busy}
        className="border border-ink px-5 py-2.5 text-[0.64rem] uppercase tracking-[0.18em] text-ink transition-colors hover:bg-ink hover:text-cream disabled:opacity-50"
      >
        {busy ? "…" : current ? (de ? "Bild ersetzen" : "Görseli değiştir") : de ? "Bild wählen" : "Görsel seç"}
      </button>

      {current && (
        <button
          type="button"
          disabled={pending}
          onClick={() =>
            startTransition(async () => {
              await remove(pageKey);
              router.refresh();
            })
          }
          className="text-[0.64rem] uppercase tracking-[0.18em] text-muted underline-offset-4 hover:text-red-700 hover:underline"
        >
          {de ? "Entfernen" : "Kaldır"}
        </button>
      )}

      {error && <span className="text-[0.74rem] text-red-700">{error}</span>}
    </div>
  );
}
