"use client";

import { useRef, useState } from "react";

import { uploadContentImage } from "@/lib/actions";
import { resizeImage } from "@/lib/invite";
import type { Locale } from "@/lib/i18n";

/**
 * Bildfeld eines Inhaltsabschnitts (Leistung, Ratgeberbeitrag …).
 *
 * Bisher stand hier nur ein Textfeld fuer die Bild-Kennung – ein Bild
 * hochzuladen oder wieder zu entfernen war ueber die Oberflaeche gar nicht
 * moeglich. Jetzt schreibt der Knopf die hochgeladene URL in dasselbe Feld.
 *
 * Wichtig: Der Upload speichert den Abschnitt *nicht* mit. Der Wert landet im
 * Formular, gespeichert wird wie bei allen anderen Feldern erst unten mit
 * "Speichern". Sonst gingen nicht gespeicherte Textaenderungen verloren.
 */
export default function ImageField({
  name,
  defaultValue,
  folder,
  locale,
}: {
  name: string;
  defaultValue: string;
  /** Zielordner im Blob-Store, z. B. "inhalte/leistungen" */
  folder: string;
  locale: Locale;
}) {
  const de = locale === "de";
  const fileRef = useRef<HTMLInputElement>(null);
  const [value, setValue] = useState(defaultValue);
  const [busy, setBusy] = useState(false);
  const [error, setError] = useState("");

  const isUpload = /^(https?:|data:|\/)/.test(value);

  async function handle(list: FileList | null) {
    const file = list?.[0];
    if (!file) return;
    setBusy(true);
    setError("");
    try {
      const res = await uploadContentImage(folder, await resizeImage(file, 1600, 0.82));
      if ("error" in res) throw new Error(res.error);
      setValue(res.url);
    } catch {
      setError(de ? "Upload fehlgeschlagen." : "Yükleme başarısız.");
    } finally {
      setBusy(false);
      if (fileRef.current) fileRef.current.value = "";
    }
  }

  return (
    <div>
      <label className="block text-[0.6rem] uppercase tracking-[0.18em] text-muted">
        {de ? "Bild" : "Görsel"}
      </label>

      {/* Der Wert selbst bleibt sichtbar und aenderbar: wer eine Demo-Kennung
          wie "lum-service-1" eintragen will, kann das weiterhin tun. */}
      <input
        name={name}
        value={value}
        onChange={(e) => setValue(e.target.value)}
        placeholder={de ? "Bild-Kennung oder URL" : "Görsel anahtarı veya URL"}
        className="w-full border-b border-sand-deep bg-transparent px-0 py-2.5 text-[0.92rem] text-ink outline-none focus:border-gold"
      />

      <input ref={fileRef} type="file" accept="image/*" className="hidden" onChange={(e) => handle(e.target.files)} />

      <div className="mt-3 flex flex-wrap items-center gap-3">
        {isUpload && (
          // eslint-disable-next-line @next/next/no-img-element -- Blob-URL, bewusst ohne Optimierung
          <img src={value} alt="" className="h-16 w-24 border border-sand-deep object-cover" />
        )}

        <button
          type="button"
          onClick={() => fileRef.current?.click()}
          disabled={busy}
          className="border border-ink px-4 py-2 text-[0.62rem] uppercase tracking-[0.16em] text-ink transition-colors hover:bg-ink hover:text-cream disabled:opacity-50"
        >
          {busy ? "…" : isUpload ? (de ? "Bild ersetzen" : "Görseli değiştir") : de ? "Bild hochladen" : "Görsel yükle"}
        </button>

        {value && (
          <button
            type="button"
            onClick={() => setValue("")}
            className="text-[0.62rem] uppercase tracking-[0.16em] text-muted underline-offset-4 hover:text-red-700 hover:underline"
          >
            {de ? "Entfernen" : "Kaldır"}
          </button>
        )}

        {error && <span className="text-[0.74rem] text-red-700">{error}</span>}
      </div>

      <p className="mt-2 text-[0.7rem] leading-relaxed text-muted">
        {de
          ? "Leer lassen, damit das Demo-Bild greift. Hochgeladene Bilder werden im Browser auf 1600 px verkleinert."
          : "Boş bırakırsanız demo görseli kullanılır. Yüklenen görseller tarayıcıda 1600 piksele küçültülür."}
      </p>
    </div>
  );
}
