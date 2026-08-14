import Link from "next/link";
import { notFound } from "next/navigation";

import { getVenue } from "@/lib/cms";
import { editVenue, deleteVenue } from "@/lib/actions";
import { cities } from "@/lib/cities";
import { isLocale, type Locale } from "@/lib/i18n";

export const dynamic = "force-dynamic";

const input =
  "w-full border-b border-sand-deep bg-transparent px-0 py-2.5 text-[0.92rem] text-ink outline-none focus:border-gold";
const area = `${input} resize-none leading-relaxed`;
const label = "block text-[0.6rem] uppercase tracking-[0.18em] text-muted";

export default async function AdminVenue({ params }: { params: Promise<{ locale: string; slug: string }> }) {
  const { locale, slug } = await params;
  const l: Locale = isLocale(locale) ? locale : "de";
  const de = l === "de";
  const v = await getVenue(slug);
  if (!v) notFound();

  return (
    <div className="space-y-8">
      <div className="flex flex-wrap items-baseline justify-between gap-4">
        <div>
          <Link href={`/${l}/admin/mekanlar`} className="text-[0.68rem] uppercase tracking-[0.16em] text-muted hover:text-gold">
            ← {de ? "Alle Locations" : "Tüm mekânlar"}
          </Link>
          <h2 className="font-display mt-2 text-2xl text-ink">{v.name}</h2>
        </div>
        <Link
          href={`/${l}/hochzeitslocations/${v.slug}`}
          className="border border-ink px-5 py-2.5 text-[0.66rem] uppercase tracking-[0.18em] text-ink transition-colors hover:bg-ink hover:text-cream"
        >
          {de ? "Seite ansehen" : "Sayfayı gör"} ↗
        </Link>
      </div>

      <form action={editVenue} className="space-y-8">
        <input type="hidden" name="slug" value={v.slug} />

        <section className="border border-sand-deep p-6">
          <h3 className="font-display text-lg text-ink">{de ? "Eckdaten" : "Temel bilgiler"}</h3>
          <div className="mt-6 grid gap-7 md:grid-cols-2">
            <div>
              <label className={label}>{de ? "Name" : "Ad"}</label>
              <input name="name" defaultValue={v.name} className={input} />
            </div>
            <div>
              <label className={label}>{de ? "Adresse" : "Adres"}</label>
              <input name="address" defaultValue={v.address} className={input} />
            </div>
            <div>
              <label className={label}>{de ? "Stadt" : "Şehir"}</label>
              <input name="city" defaultValue={v.city} className={input} />
            </div>
            <div>
              <label className={label}>{de ? "Verknüpfte Stadtseite" : "Bağlı şehir sayfası"}</label>
              <select name="citySlug" defaultValue={v.citySlug} className={input}>
                {cities.map((c) => (
                  <option key={c.slug} value={c.slug}>
                    {c.name}
                  </option>
                ))}
              </select>
            </div>
            <div className="grid grid-cols-2 gap-4">
              <div>
                <label className={label}>{de ? "Art (DE)" : "Tür (DE)"}</label>
                <input name="type_de" defaultValue={v.type.de} className={input} />
              </div>
              <div>
                <label className={label}>{de ? "Art (TR)" : "Tür (TR)"}</label>
                <input name="type_tr" defaultValue={v.type.tr} className={input} />
              </div>
            </div>
            <div className="grid grid-cols-2 gap-4">
              <div>
                <label className={label}>{de ? "Kapazität (DE)" : "Kapasite (DE)"}</label>
                <input name="capacity_de" defaultValue={v.capacity.de} className={input} />
              </div>
              <div>
                <label className={label}>{de ? "Kapazität (TR)" : "Kapasite (TR)"}</label>
                <input name="capacity_tr" defaultValue={v.capacity.tr} className={input} />
              </div>
            </div>
          </div>
        </section>

        <section className="border border-sand-deep p-6">
          <h3 className="font-display text-lg text-ink">{de ? "Texte" : "Metinler"}</h3>
          <p className="mt-2 text-[0.76rem] text-muted">
            {de
              ? "Der Einleitungssatz erscheint auch als Google-Beschreibung. Im Fliesstext trennt eine Leerzeile die Absätze."
              : "Giriş cümlesi Google açıklaması olarak da kullanılır. Uzun metinde boş satır paragrafları ayırır."}
          </p>
          <div className="mt-6 grid gap-7 md:grid-cols-2">
            <div>
              <label className={label}>{de ? "Einleitung (DE)" : "Giriş (DE)"}</label>
              <textarea name="lead_de" rows={3} defaultValue={v.lead.de} className={area} />
            </div>
            <div>
              <label className={label}>{de ? "Einleitung (TR)" : "Giriş (TR)"}</label>
              <textarea name="lead_tr" rows={3} defaultValue={v.lead.tr} className={area} />
            </div>
            <div>
              <label className={label}>{de ? "Fliesstext (DE)" : "Uzun metin (DE)"}</label>
              <textarea name="body_de" rows={10} defaultValue={v.body.de.join("\n\n")} className={area} />
            </div>
            <div>
              <label className={label}>{de ? "Fliesstext (TR)" : "Uzun metin (TR)"}</label>
              <textarea name="body_tr" rows={10} defaultValue={v.body.tr.join("\n\n")} className={area} />
            </div>
            <div>
              <label className={label}>{de ? "Licht & beste Zeiten (DE)" : "Işık & en iyi saatler (DE)"}</label>
              <textarea name="light_de" rows={3} defaultValue={v.light.de} className={area} />
            </div>
            <div>
              <label className={label}>{de ? "Licht & beste Zeiten (TR)" : "Işık & en iyi saatler (TR)"}</label>
              <textarea name="light_tr" rows={3} defaultValue={v.light.tr} className={area} />
            </div>
            <div>
              <label className={label}>{de ? "Fotospots (DE) – einer pro Zeile" : "Çekim noktaları (DE) – satır başına bir"}</label>
              <textarea name="spots_de" rows={5} defaultValue={v.spots.de.join("\n")} className={area} />
            </div>
            <div>
              <label className={label}>{de ? "Fotospots (TR) – einer pro Zeile" : "Çekim noktaları (TR) – satır başına bir"}</label>
              <textarea name="spots_tr" rows={5} defaultValue={v.spots.tr.join("\n")} className={area} />
            </div>
            <div>
              <label className={label}>{de ? "Gut zu wissen (DE)" : "Bilinmesi gerekenler (DE)"}</label>
              <textarea name="rules_de" rows={4} defaultValue={v.rules.de.join("\n")} className={area} />
            </div>
            <div>
              <label className={label}>{de ? "Gut zu wissen (TR)" : "Bilinmesi gerekenler (TR)"}</label>
              <textarea name="rules_tr" rows={4} defaultValue={v.rules.tr.join("\n")} className={area} />
            </div>
          </div>
        </section>

        <button className="bg-ink px-9 py-4 text-[0.68rem] uppercase tracking-[0.2em] text-cream transition-colors hover:bg-gold">
          {de ? "Speichern" : "Kaydet"}
        </button>
      </form>

      <form action={deleteVenue} className="border border-sand-deep p-6">
        <input type="hidden" name="slug" value={v.slug} />
        <input type="hidden" name="locale" value={l} />
        <h3 className="font-display text-lg text-ink">{de ? "Location löschen" : "Mekânı sil"}</h3>
        <p className="mt-2 max-w-lg text-[0.76rem] leading-relaxed text-muted">
          {de
            ? "Die Seite verschwindet damit auch aus der Sitemap. Bei bereits indexierten Seiten besser eine Weiterleitung einrichten."
            : "Sayfa site haritasından da kalkar. Google'a düşmüş sayfalarda yönlendirme kurmak daha doğrudur."}
        </p>
        <button className="mt-5 border border-red-800/60 px-7 py-3 text-[0.66rem] uppercase tracking-[0.18em] text-red-800 transition-colors hover:bg-red-800 hover:text-white">
          {de ? "Endgültig löschen" : "Kalıcı olarak sil"}
        </button>
      </form>
    </div>
  );
}
