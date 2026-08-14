import Link from "next/link";
import { listGalleries, listSelections } from "@/lib/store";
import { newGallery } from "@/lib/actions";
import { isLocale, type Locale } from "@/lib/i18n";

export const dynamic = "force-dynamic";

const input =
  "w-full border-b border-sand-deep bg-transparent px-0 py-2.5 text-[0.92rem] text-ink outline-none focus:border-gold";
const label = "block text-[0.6rem] uppercase tracking-[0.18em] text-muted";

export default async function AdminGalleries({ params }: { params: Promise<{ locale: string }> }) {
  const { locale } = await params;
  const l: Locale = isLocale(locale) ? locale : "de";
  const de = l === "de";
  const galleries = await listGalleries();
  const selections = await listSelections();

  return (
    <div className="grid gap-12 lg:grid-cols-[1.2fr_0.8fr]">
      <div>
        <h2 className="font-display text-xl text-ink">{de ? "Kundengalerien" : "Müşteri galerileri"}</h2>
        <p className="mt-2 max-w-xl text-sm leading-relaxed text-muted">
          {de
            ? "Jede Galerie hat einen eigenen Code und ein eigenes Passwort. Diese beiden Angaben bekommt das Paar per E-Mail."
            : "Her galerinin kendi kodu ve parolası vardır. Bu iki bilgiyi çifte e-posta ile gönderiyorsunuz."}
        </p>

        <div className="mt-7 space-y-4">
          {galleries.map((g) => {
            const selection = selections.find((s) => s.code === g.code);
            return (
              <Link
                key={g.code}
                href={`/${l}/admin/galerien/${g.code}`}
                className="block border border-sand-deep p-5 transition-colors hover:border-gold"
              >
                <div className="flex flex-wrap items-baseline justify-between gap-3">
                  <span className="font-display text-lg text-ink">{g.couple}</span>
                  <span className="text-[0.68rem] uppercase tracking-[0.16em] text-muted">
                    {g.uploads.length + g.seeds.length} {de ? "Bilder" : "kare"}
                    {selection && (
                      <span className="ml-3 text-gold">
                        {selection.picks.length} {de ? "ausgewählt" : "seçildi"}
                      </span>
                    )}
                  </span>
                </div>
                <div className="mt-1.5 text-[0.76rem] text-muted">
                  {g.venue} · {g.date} · /{l}/galerie/{g.code}
                </div>
              </Link>
            );
          })}
          {galleries.length === 0 && (
            <p className="text-sm text-muted">{de ? "Noch keine Galerie angelegt." : "Henüz galeri yok."}</p>
          )}
        </div>
      </div>

      <form action={newGallery} className="h-fit border border-sand-deep p-6">
        <input type="hidden" name="locale" value={l} />
        <h2 className="font-display text-xl text-ink">{de ? "Neue Galerie" : "Yeni galeri"}</h2>

        <div className="mt-6 space-y-6">
          <div>
            <label className={label}>{de ? "Paar" : "Çift"}</label>
            <input name="couple" required className={input} placeholder="Elif & Marco" />
          </div>
          <div>
            <label className={label}>{de ? "Code (URL)" : "Kod (URL)"}</label>
            <input name="code" required className={input} placeholder="elif-marco" />
          </div>
          <div>
            <label className={label}>{de ? "Passwort" : "Parola"}</label>
            <input name="password" className={input} placeholder={de ? "leer = automatisch" : "boş = otomatik"} />
          </div>
          <div>
            <label className={label}>{de ? "Hochzeitsdatum" : "Düğün tarihi"}</label>
            <input type="date" name="date" className={input} />
          </div>
          <div>
            <label className={label}>Location</label>
            <input name="venue" className={input} placeholder="Schloss Solitude, Stuttgart" />
          </div>
          <div>
            <label className={label}>{de ? "Erreichbar bis" : "Erişim bitişi"}</label>
            <input type="date" name="expires" className={input} />
          </div>
        </div>

        <button className="mt-8 w-full bg-ink px-7 py-3.5 text-[0.68rem] uppercase tracking-[0.2em] text-cream transition-colors hover:bg-gold">
          {de ? "Galerie anlegen" : "Galeri oluştur"}
        </button>
      </form>
    </div>
  );
}
