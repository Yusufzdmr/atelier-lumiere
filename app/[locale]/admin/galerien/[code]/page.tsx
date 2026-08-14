import Link from "next/link";
import { notFound } from "next/navigation";

import GalleryUploader from "@/components/admin/GalleryUploader";
import { getGallery, listSelections } from "@/lib/store";
import { editGallery, removeGallery, deleteGalleryPhoto, uploadGalleryPhotos } from "@/lib/actions";
import { img } from "@/lib/images";
import { isLocale, type Locale } from "@/lib/i18n";

export const dynamic = "force-dynamic";

const input =
  "w-full border-b border-sand-deep bg-transparent px-0 py-2.5 text-[0.92rem] text-ink outline-none focus:border-gold";
const label = "block text-[0.6rem] uppercase tracking-[0.18em] text-muted";

export default async function AdminGallery({ params }: { params: Promise<{ locale: string; code: string }> }) {
  const { locale, code } = await params;
  const l: Locale = isLocale(locale) ? locale : "de";
  const de = l === "de";
  const g = await getGallery(code);
  if (!g) notFound();

  const selection = (await listSelections()).find((s) => s.code === g.code);
  const photos = [...g.uploads.map((src) => ({ src, upload: true })), ...g.seeds.map((s) => ({ src: img(s, 500, 650), upload: false }))];

  return (
    <div className="space-y-10">
      <div className="flex flex-wrap items-baseline justify-between gap-4">
        <div>
          <Link href={`/${l}/admin/galerien`} className="text-[0.68rem] uppercase tracking-[0.16em] text-muted hover:text-gold">
            ← {de ? "Alle Galerien" : "Tüm galeriler"}
          </Link>
          <h2 className="font-display mt-2 text-2xl text-ink">{g.couple}</h2>
        </div>
        <Link
          href={`/${l}/galerie/${g.code}`}
          className="border border-ink px-5 py-2.5 text-[0.66rem] uppercase tracking-[0.18em] text-ink transition-colors hover:bg-ink hover:text-cream"
        >
          {de ? "Galerie ansehen" : "Galeriyi gör"} ↗
        </Link>
      </div>

      <div className="grid gap-8 lg:grid-cols-[1fr_340px]">
        <div className="space-y-6">
          <GalleryUploader code={g.code} locale={l} upload={uploadGalleryPhotos} />

          <div className="grid grid-cols-3 gap-3 sm:grid-cols-4 lg:grid-cols-5">
            {photos.map((p, i) => {
              const picked = selection?.picks.includes(i);
              return (
                <div key={i} className="group relative aspect-[3/4] overflow-hidden border border-sand-deep">
                  {/* eslint-disable-next-line @next/next/no-img-element -- gemischte Quellen (Upload/Platzhalter) */}
                  <img src={p.src} alt="" className="h-full w-full object-cover" loading="lazy" />
                  <span className="absolute left-1.5 top-1.5 bg-ink/70 px-1.5 py-0.5 text-[0.55rem] text-cream">
                    {i + 1}
                  </span>
                  {picked && (
                    <span className="absolute right-1.5 top-1.5 bg-gold px-1.5 py-0.5 text-[0.55rem] text-white">♥</span>
                  )}
                  {p.upload && (
                    <form action={deleteGalleryPhoto} className="absolute inset-x-0 bottom-0">
                      <input type="hidden" name="code" value={g.code} />
                      <input type="hidden" name="index" value={i} />
                      <button className="w-full bg-ink/80 py-1.5 text-[0.55rem] uppercase tracking-[0.14em] text-cream opacity-0 transition-opacity group-hover:opacity-100">
                        {de ? "Löschen" : "Sil"}
                      </button>
                    </form>
                  )}
                </div>
              );
            })}
          </div>
        </div>

        <div className="space-y-6">
          <form action={editGallery} className="border border-sand-deep p-6">
            <input type="hidden" name="code" value={g.code} />
            <h3 className="font-display text-lg text-ink">{de ? "Zugang & Daten" : "Erişim & bilgiler"}</h3>
            <div className="mt-5 space-y-5">
              <div>
                <label className={label}>{de ? "Paar" : "Çift"}</label>
                <input name="couple" defaultValue={g.couple} className={input} />
              </div>
              <div>
                <label className={label}>Code</label>
                <div className="py-2.5 text-[0.92rem] text-muted">{g.code}</div>
              </div>
              <div>
                <label className={label}>{de ? "Passwort" : "Parola"}</label>
                <input name="password" defaultValue={g.password} className={input} />
              </div>
              <div>
                <label className={label}>{de ? "Datum" : "Tarih"}</label>
                <input type="date" name="date" defaultValue={g.date} className={input} />
              </div>
              <div>
                <label className={label}>Location</label>
                <input name="venue" defaultValue={g.venue} className={input} />
              </div>
              <div>
                <label className={label}>{de ? "Erreichbar bis" : "Erişim bitişi"}</label>
                <input type="date" name="expires" defaultValue={g.expires} className={input} />
              </div>
              <div>
                <label className={label}>{de ? "Hochzeitsfilm (YouTube / Vimeo)" : "Düğün filmi (YouTube / Vimeo)"}</label>
                <input
                  name="video"
                  defaultValue={g.videoUrl ?? ""}
                  className={input}
                  placeholder="https://vimeo.com/123456789"
                />
                <p className="mt-2 text-[0.72rem] leading-relaxed text-muted">
                  {de
                    ? "Link einfügen – der Film erscheint über den Bildern. Leer lassen, wenn es keinen gibt."
                    : "Bağlantıyı yapıştırın – film karelerin üstünde görünür. Yoksa boş bırakın."}
                </p>
              </div>
            </div>
            <button className="mt-7 w-full bg-ink px-7 py-3.5 text-[0.68rem] uppercase tracking-[0.2em] text-cream transition-colors hover:bg-gold">
              {de ? "Speichern" : "Kaydet"}
            </button>
          </form>

          {selection && (
            <div className="border border-gold/50 bg-sand/40 p-6">
              <h3 className="font-display text-lg text-ink">{de ? "Album-Auswahl des Paares" : "Çiftin albüm seçimi"}</h3>
              <div className="mt-2 text-[0.74rem] text-muted">
                {new Date(selection.at).toLocaleString(de ? "de-DE" : "tr-TR")}
              </div>
              <div className="font-display mt-3 text-3xl text-gold">{selection.picks.length}</div>
              <p className="mt-2 text-[0.78rem] leading-relaxed text-muted">
                {de ? "Bild-Nummern: " : "Kare no: "}
                {selection.picks.map((n) => n + 1).join(", ")}
              </p>
            </div>
          )}

          <form action={removeGallery} className="border border-sand-deep p-6">
            <input type="hidden" name="code" value={g.code} />
            <input type="hidden" name="locale" value={l} />
            <h3 className="font-display text-lg text-ink">{de ? "Galerie löschen" : "Galeriyi sil"}</h3>
            <p className="mt-2 text-[0.76rem] leading-relaxed text-muted">
              {de
                ? "Entfernt die Galerie samt Auswahl. Kann nicht rückgängig gemacht werden."
                : "Galeriyi ve seçimi kaldırır. Geri alınamaz."}
            </p>
            <button className="mt-5 w-full border border-red-800/60 px-7 py-3 text-[0.66rem] uppercase tracking-[0.18em] text-red-800 transition-colors hover:bg-red-800 hover:text-white">
              {de ? "Endgültig löschen" : "Kalıcı olarak sil"}
            </button>
          </form>
        </div>
      </div>
    </div>
  );
}
