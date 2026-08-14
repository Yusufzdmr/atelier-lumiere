"use client";

import { useRef, useState } from "react";
import { useRouter } from "next/navigation";

import { resizeImage } from "@/lib/invite";
import type { Locale } from "@/lib/i18n";

export default function GalleryUploader({
  code,
  locale,
  upload,
}: {
  code: string;
  locale: Locale;
  /** Server Action – Galerie oder Reportage */
  upload: (code: string, photos: string[]) => Promise<{ ok: boolean; added: number }>;
}) {
  const router = useRouter();
  const fileRef = useRef<HTMLInputElement>(null);
  const [busy, setBusy] = useState(false);
  const [progress, setProgress] = useState({ done: 0, total: 0 });
  const [error, setError] = useState("");

  async function handle(list: FileList | null) {
    if (!list?.length) return;
    const files = Array.from(list).filter((f) => f.type.startsWith("image/"));
    setBusy(true);
    setError("");
    setProgress({ done: 0, total: files.length });

    try {
      // In Paketen hochladen, damit auch 40 Bilder auf dem Handy durchlaufen
      const batch: string[] = [];
      for (let i = 0; i < files.length; i++) {
        batch.push(await resizeImage(files[i], 1600, 0.8));
        setProgress({ done: i + 1, total: files.length });
        if (batch.length === 8 || i === files.length - 1) {
          await upload(code, batch.splice(0, batch.length));
        }
      }
      router.refresh();
    } catch {
      setError(locale === "de" ? "Upload fehlgeschlagen." : "Yükleme başarısız.");
    } finally {
      setBusy(false);
      if (fileRef.current) fileRef.current.value = "";
    }
  }

  return (
    <div className="border border-dashed border-sand-deep p-6 text-center">
      <input
        ref={fileRef}
        type="file"
        accept="image/*"
        multiple
        className="hidden"
        onChange={(e) => handle(e.target.files)}
      />
      <button
        onClick={() => fileRef.current?.click()}
        disabled={busy}
        className="bg-ink px-7 py-3.5 text-[0.68rem] uppercase tracking-[0.2em] text-cream transition-colors hover:bg-gold disabled:opacity-50"
      >
        {busy
          ? `${progress.done} / ${progress.total}`
          : locale === "de"
            ? "Bilder hochladen"
            : "Fotoğraf yükle"}
      </button>
      <p className="mt-3 text-[0.76rem] leading-relaxed text-muted">
        {locale === "de"
          ? "Mehrfachauswahl möglich. Die Bilder werden im Browser verkleinert (max. 1600 px), bevor sie gespeichert werden."
          : "Çoklu seçim yapabilirsiniz. Görseller kaydedilmeden önce tarayıcıda küçültülür (maks. 1600 px)."}
      </p>
      {error && <p className="mt-3 text-sm text-red-700">{error}</p>}
    </div>
  );
}
