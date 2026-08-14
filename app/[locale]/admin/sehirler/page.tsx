import Link from "next/link";
import { getCities } from "@/lib/cms";
import { editCity, newCity, deleteCity } from "@/lib/actions";
import { isLocale, type Locale } from "@/lib/i18n";

export const dynamic = "force-dynamic";

const input =
  "w-full border-b border-sand-deep bg-transparent px-0 py-2.5 text-[0.92rem] text-ink outline-none focus:border-gold";
const area = `${input} resize-none leading-relaxed`;
const label = "block text-[0.6rem] uppercase tracking-[0.18em] text-muted";

export default async function AdminCities({ params }: { params: Promise<{ locale: string }> }) {
  const { locale } = await params;
  const l: Locale = isLocale(locale) ? locale : "de";
  const de = l === "de";
  const cities = await getCities();

  return (
    <div className="space-y-8">
      <div>
        <h2 className="font-display text-xl text-ink">{de ? "Stadt- und Regionsseiten" : "Şehir ve bölge sayfaları"}</h2>
        <p className="mt-2 max-w-2xl text-sm leading-relaxed text-muted">
          {de
            ? "Jede Stadt bekommt eine eigene Seite (Hochzeitsfotograf Stuttgart). Die Texte müssen sich unterscheiden – kopierte Stadtseiten wertet Google ab."
            : "Her şehir kendi sayfasını alır (Stuttgart düğün fotoğrafçısı). Metinler birbirinden farklı olmalı – kopya şehir sayfalarını Google değersizleştirir."}
        </p>
      </div>

      <div className="space-y-3">
        {cities.map((c) => (
          <details key={c.slug} className="group border border-sand-deep">
            <summary className="flex cursor-pointer items-center justify-between gap-4 p-5">
              <span>
                <span className="font-display text-lg text-ink">{c.name}</span>
                <span className="ml-3 text-[0.72rem] text-muted">/{l}/hochzeitsfotograf/{c.slug}</span>
              </span>
              <span className="text-[0.66rem] uppercase tracking-[0.16em] text-gold group-open:hidden">
                {de ? "Bearbeiten" : "Düzenle"}
              </span>
            </summary>

            <div className="border-t border-sand-deep p-6">
              <form action={editCity} className="space-y-7">
                <input type="hidden" name="slug" value={c.slug} />

                <div className="grid gap-7 md:grid-cols-3">
                  <div>
                    <label className={label}>{de ? "Name" : "Ad"}</label>
                    <input name="name" defaultValue={c.name} className={input} />
                  </div>
                  <div className="grid grid-cols-2 gap-4">
                    <div>
                      <label className={label}>{de ? "Landkreis (DE)" : "İlçe (DE)"}</label>
                      <input name="kreis_de" defaultValue={c.kreis.de} className={input} />
                    </div>
                    <div>
                      <label className={label}>{de ? "Landkreis (TR)" : "İlçe (TR)"}</label>
                      <input name="kreis_tr" defaultValue={c.kreis.tr} className={input} />
                    </div>
                  </div>
                  <div className="grid grid-cols-2 gap-4">
                    <div>
                      <label className={label}>{de ? "Anfahrt (DE)" : "Ulaşım (DE)"}</label>
                      <input name="drive_de" defaultValue={c.drive.de} className={input} />
                    </div>
                    <div>
                      <label className={label}>{de ? "Anfahrt (TR)" : "Ulaşım (TR)"}</label>
                      <input name="drive_tr" defaultValue={c.drive.tr} className={input} />
                    </div>
                  </div>
                </div>

                <div className="grid gap-7 md:grid-cols-2">
                  <div>
                    <label className={label}>{de ? "Einleitung (DE)" : "Giriş (DE)"}</label>
                    <textarea name="lead_de" rows={3} defaultValue={c.lead.de} className={area} />
                  </div>
                  <div>
                    <label className={label}>{de ? "Einleitung (TR)" : "Giriş (TR)"}</label>
                    <textarea name="lead_tr" rows={3} defaultValue={c.lead.tr} className={area} />
                  </div>
                  <div>
                    <label className={label}>{de ? "Fliesstext (DE)" : "Uzun metin (DE)"}</label>
                    <textarea name="body_de" rows={8} defaultValue={c.body.de.join("\n\n")} className={area} />
                  </div>
                  <div>
                    <label className={label}>{de ? "Fliesstext (TR)" : "Uzun metin (TR)"}</label>
                    <textarea name="body_tr" rows={8} defaultValue={c.body.tr.join("\n\n")} className={area} />
                  </div>
                  <div>
                    <label className={label}>
                      {de ? "Fotospots (DE) – Name | Beschreibung" : "Çekim noktaları (DE) – Ad | Açıklama"}
                    </label>
                    <textarea
                      name="spots_de"
                      rows={4}
                      defaultValue={c.spots.map((s) => `${s.name} | ${s.note.de}`).join("\n")}
                      className={area}
                    />
                  </div>
                  <div>
                    <label className={label}>
                      {de ? "Fotospots (TR) – Name | Beschreibung" : "Çekim noktaları (TR) – Ad | Açıklama"}
                    </label>
                    <textarea
                      name="spots_tr"
                      rows={4}
                      defaultValue={c.spots.map((s) => `${s.name} | ${s.note.tr}`).join("\n")}
                      className={area}
                    />
                  </div>
                  <div>
                    <label className={label}>{de ? "FAQ (DE) – Frage | Antwort" : "SSS (DE) – Soru | Cevap"}</label>
                    <textarea
                      name="faq_de"
                      rows={4}
                      defaultValue={c.faq.map((f) => `${f.q.de} | ${f.a.de}`).join("\n")}
                      className={area}
                    />
                  </div>
                  <div>
                    <label className={label}>{de ? "FAQ (TR) – Frage | Antwort" : "SSS (TR) – Soru | Cevap"}</label>
                    <textarea
                      name="faq_tr"
                      rows={4}
                      defaultValue={c.faq.map((f) => `${f.q.tr} | ${f.a.tr}`).join("\n")}
                      className={area}
                    />
                  </div>
                </div>

                <div className="flex flex-wrap items-center gap-4">
                  <button className="bg-ink px-8 py-3.5 text-[0.68rem] uppercase tracking-[0.2em] text-cream hover:bg-gold">
                    {de ? "Speichern" : "Kaydet"}
                  </button>
                  <Link
                    href={`/${l}/hochzeitsfotograf/${c.slug}`}
                    className="text-[0.68rem] uppercase tracking-[0.18em] text-muted hover:text-gold"
                  >
                    {de ? "Seite ansehen" : "Sayfayı gör"} ↗
                  </Link>
                </div>
              </form>

              <form action={deleteCity} className="mt-6 border-t border-sand-deep pt-5">
                <input type="hidden" name="slug" value={c.slug} />
                <button className="text-[0.66rem] uppercase tracking-[0.16em] text-muted transition-colors hover:text-red-800">
                  {de ? "Diese Stadtseite löschen" : "Bu şehir sayfasını sil"}
                </button>
              </form>
            </div>
          </details>
        ))}
      </div>

      <form action={newCity} className="border border-sand-deep p-6">
        <input type="hidden" name="locale" value={l} />
        <h3 className="font-display text-lg text-ink">{de ? "Neue Stadtseite" : "Yeni şehir sayfası"}</h3>
        <div className="mt-6 grid gap-7 md:grid-cols-3">
          <div>
            <label className={label}>{de ? "Name" : "Ad"} *</label>
            <input name="name" required className={input} placeholder="Karlsruhe" />
          </div>
          <div>
            <label className={label}>URL</label>
            <input name="slug" className={input} placeholder="karlsruhe" />
          </div>
          <div className="grid grid-cols-2 gap-4">
            <div>
              <label className={label}>{de ? "Anfahrt (DE)" : "Ulaşım (DE)"}</label>
              <input name="drive_de" className={input} placeholder="60 Minuten" />
            </div>
            <div>
              <label className={label}>{de ? "Anfahrt (TR)" : "Ulaşım (TR)"}</label>
              <input name="drive_tr" className={input} placeholder="60 dakika" />
            </div>
          </div>
          <div className="md:col-span-3 grid gap-7 md:grid-cols-2">
            <div>
              <label className={label}>{de ? "Einleitung (DE)" : "Giriş (DE)"}</label>
              <textarea name="lead_de" rows={2} className={area} />
            </div>
            <div>
              <label className={label}>{de ? "Einleitung (TR)" : "Giriş (TR)"}</label>
              <textarea name="lead_tr" rows={2} className={area} />
            </div>
          </div>
        </div>
        <button className="mt-7 bg-ink px-8 py-3.5 text-[0.68rem] uppercase tracking-[0.2em] text-cream hover:bg-gold">
          {de ? "Stadtseite anlegen" : "Şehir sayfası oluştur"}
        </button>
      </form>
    </div>
  );
}
