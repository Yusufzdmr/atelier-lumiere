import { getContent } from "@/lib/cms";
import { savePackages } from "@/lib/actions";
import { isLocale, type Locale } from "@/lib/i18n";

export const dynamic = "force-dynamic";

const input =
  "w-full border-b border-sand-deep bg-transparent px-0 py-2.5 text-[0.92rem] text-ink outline-none focus:border-gold";
const label = "block text-[0.6rem] uppercase tracking-[0.18em] text-muted";

export default async function AdminPackages({ params }: { params: Promise<{ locale: string }> }) {
  const { locale } = await params;
  const l: Locale = isLocale(locale) ? locale : "de";
  const de = l === "de";
  const c = await getContent();

  return (
    <form action={savePackages} className="space-y-10">
      <p className="max-w-2xl text-sm leading-relaxed text-muted">
        {de
          ? "Preise, Paketnamen und Leistungen. Jede Zeile im Textfeld wird zu einem Punkt in der Liste."
          : "Fiyatlar, paket adları ve içerikler. Metin alanındaki her satır listede bir madde olur."}
      </p>

      {c.packages.map((p, i) => (
        <section key={p.slug} className="border border-sand-deep p-6">
          <div className="flex flex-wrap items-center justify-between gap-4">
            <h2 className="font-display text-xl text-ink">
              {de ? "Paket" : "Paket"} {i + 1}
            </h2>
            <label className="flex cursor-pointer items-center gap-2 text-[0.68rem] uppercase tracking-[0.16em] text-muted">
              <input type="radio" name="featured" value={i} defaultChecked={p.featured} className="accent-[#B08D57]" />
              {de ? "Als Empfehlung markieren" : "Öne çıkan olarak işaretle"}
            </label>
          </div>

          <div className="mt-6 grid gap-7 md:grid-cols-2">
            <div>
              <label className={label}>{de ? "Name (DE)" : "Ad (DE)"}</label>
              <input name={`p${i}_name_de`} defaultValue={p.name.de} className={input} />
            </div>
            <div>
              <label className={label}>{de ? "Name (TR)" : "Ad (TR)"}</label>
              <input name={`p${i}_name_tr`} defaultValue={p.name.tr} className={input} />
            </div>
            <div>
              <label className={label}>{de ? "Preis" : "Fiyat"}</label>
              <input name={`p${i}_price`} defaultValue={p.price} className={input} />
            </div>
            <div className="grid grid-cols-2 gap-4">
              <div>
                <label className={label}>{de ? "Zusatz (DE)" : "Not (DE)"}</label>
                <input name={`p${i}_hint_de`} defaultValue={p.hint.de} className={input} />
              </div>
              <div>
                <label className={label}>{de ? "Zusatz (TR)" : "Not (TR)"}</label>
                <input name={`p${i}_hint_tr`} defaultValue={p.hint.tr} className={input} />
              </div>
            </div>
            <div>
              <label className={label}>{de ? "Leistungen (DE) – eine pro Zeile" : "İçerik (DE) – satır başına bir madde"}</label>
              <textarea
                name={`p${i}_features_de`}
                rows={7}
                defaultValue={p.features.de.join("\n")}
                className={`${input} resize-none`}
              />
            </div>
            <div>
              <label className={label}>{de ? "Leistungen (TR) – eine pro Zeile" : "İçerik (TR) – satır başına bir madde"}</label>
              <textarea
                name={`p${i}_features_tr`}
                rows={7}
                defaultValue={p.features.tr.join("\n")}
                className={`${input} resize-none`}
              />
            </div>
          </div>
        </section>
      ))}

      <section className="border border-sand-deep p-6">
        <h2 className="font-display text-xl text-ink">{de ? "Zusatzleistungen" : "Ek hizmetler"}</h2>
        <div className="mt-6 space-y-5">
          {c.addons.map((a, i) => (
            <div key={i} className="grid gap-4 sm:grid-cols-[1fr_1fr_140px]">
              <input name={`a${i}_name_de`} defaultValue={a.name.de} className={input} placeholder="DE" />
              <input name={`a${i}_name_tr`} defaultValue={a.name.tr} className={input} placeholder="TR" />
              <input name={`a${i}_price`} defaultValue={a.price} className={input} placeholder="€" />
            </div>
          ))}
        </div>
      </section>

      <button className="bg-ink px-9 py-4 text-[0.68rem] uppercase tracking-[0.2em] text-cream transition-colors hover:bg-gold">
        {de ? "Speichern" : "Kaydet"}
      </button>
    </form>
  );
}
