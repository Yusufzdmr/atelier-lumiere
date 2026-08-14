import Link from "next/link";
import { getVenues } from "@/lib/cms";
import { newVenue } from "@/lib/actions";
import { cities } from "@/lib/cities";
import { isLocale, type Locale } from "@/lib/i18n";

export const dynamic = "force-dynamic";

const input =
  "w-full border-b border-sand-deep bg-transparent px-0 py-2.5 text-[0.92rem] text-ink outline-none focus:border-gold";
const label = "block text-[0.6rem] uppercase tracking-[0.18em] text-muted";

export default async function AdminVenues({ params }: { params: Promise<{ locale: string }> }) {
  const { locale } = await params;
  const l: Locale = isLocale(locale) ? locale : "de";
  const de = l === "de";
  const venues = await getVenues();

  return (
    <div className="grid gap-12 lg:grid-cols-[1.2fr_0.8fr]">
      <div>
        <h2 className="font-display text-xl text-ink">{de ? "Hochzeitslocations" : "Düğün mekânları"}</h2>
        <p className="mt-2 max-w-xl text-sm leading-relaxed text-muted">
          {de
            ? "Jede Location hat eine eigene Seite mit eigenem Text – das ist die Basis für die Google-Sichtbarkeit über Locationsuchen."
            : "Her mekânın kendi metniyle ayrı bir sayfası var – mekân aramalarından Google görünürlüğünün temeli budur."}
        </p>

        <div className="mt-7 space-y-3">
          {venues.map((v) => (
            <Link
              key={v.slug}
              href={`/${l}/admin/mekanlar/${v.slug}`}
              className="block border border-sand-deep p-5 transition-colors hover:border-gold"
            >
              <div className="flex flex-wrap items-baseline justify-between gap-3">
                <span className="font-display text-lg text-ink">{v.name}</span>
                <span className="text-[0.66rem] uppercase tracking-[0.16em] text-gold">{v.type[l]}</span>
              </div>
              <div className="mt-1.5 text-[0.76rem] text-muted">
                {v.city} · /{l}/hochzeitslocations/{v.slug}
              </div>
              <p className="mt-2 line-clamp-2 text-[0.82rem] leading-relaxed text-muted">{v.lead[l]}</p>
            </Link>
          ))}
        </div>
      </div>

      <form action={newVenue} className="h-fit border border-sand-deep p-6">
        <input type="hidden" name="locale" value={l} />
        <h2 className="font-display text-xl text-ink">{de ? "Neue Location" : "Yeni mekân"}</h2>
        <p className="mt-2 text-[0.76rem] leading-relaxed text-muted">
          {de
            ? "Name und Stadt genügen – Texte können später ergänzt werden."
            : "Ad ve şehir yeterli – metinleri sonra da ekleyebilirsiniz."}
        </p>

        <div className="mt-6 space-y-6">
          <div>
            <label className={label}>{de ? "Name" : "Ad"} *</label>
            <input name="name" required className={input} placeholder="Schloss Solitude" />
          </div>
          <div>
            <label className={label}>URL</label>
            <input name="slug" className={input} placeholder="schloss-solitude" />
          </div>
          <div>
            <label className={label}>{de ? "Stadt" : "Şehir"}</label>
            <input name="city" className={input} placeholder="Stuttgart" />
          </div>
          <div>
            <label className={label}>{de ? "Region (verknüpfte Stadtseite)" : "Bölge (bağlı şehir sayfası)"}</label>
            <select name="citySlug" className={input} defaultValue="stuttgart">
              {cities.map((c) => (
                <option key={c.slug} value={c.slug}>
                  {c.name}
                </option>
              ))}
            </select>
          </div>
          <div>
            <label className={label}>{de ? "Adresse" : "Adres"}</label>
            <input name="address" className={input} />
          </div>
          <div className="grid grid-cols-2 gap-4">
            <div>
              <label className={label}>{de ? "Art (DE)" : "Tür (DE)"}</label>
              <input name="type_de" className={input} placeholder="Schloss" />
            </div>
            <div>
              <label className={label}>{de ? "Art (TR)" : "Tür (TR)"}</label>
              <input name="type_tr" className={input} placeholder="Saray" />
            </div>
          </div>
        </div>

        <button className="mt-8 w-full bg-ink px-7 py-3.5 text-[0.68rem] uppercase tracking-[0.2em] text-cream transition-colors hover:bg-gold">
          {de ? "Location anlegen" : "Mekân oluştur"}
        </button>
      </form>
    </div>
  );
}
