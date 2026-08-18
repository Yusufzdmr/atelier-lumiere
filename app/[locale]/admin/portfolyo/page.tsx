import Link from "next/link";

import GalleryUploader from "@/components/admin/GalleryUploader";
import { getStories, getCities, getVenues } from "@/lib/cms";
import { editStory, newStory, deleteStory, uploadStoryPhotos, deleteStoryPhoto, makeStoryCoverPhoto } from "@/lib/actions";
import { img } from "@/lib/images";
import { isLocale, type Locale } from "@/lib/i18n";

export const dynamic = "force-dynamic";

const input =
  "w-full border-b border-sand-deep bg-transparent px-0 py-2.5 text-[0.92rem] text-ink outline-none focus:border-gold";
const area = `${input} resize-none leading-relaxed`;
const label = "block text-[0.6rem] uppercase tracking-[0.18em] text-muted";

export default async function AdminPortfolio({ params }: { params: Promise<{ locale: string }> }) {
  const { locale } = await params;
  const l: Locale = isLocale(locale) ? locale : "de";
  const de = l === "de";
  const stories = await getStories();
  const cities = await getCities();
  const venues = await getVenues();

  return (
    <div className="space-y-8">
      <div>
        <h2 className="font-display text-xl text-ink">{de ? "Portfolio / Reportagen" : "Portfolyo / çekimler"}</h2>
        <p className="mt-2 max-w-2xl text-sm leading-relaxed text-muted">
          {de
            ? "Jede Reportage ist eine eigene Seite und verlinkt automatisch zur Location und zur Stadtseite – das stärkt beide."
            : "Her çekim kendi sayfasıdır ve otomatik olarak mekân ve şehir sayfasına bağlanır – ikisini birden güçlendirir."}
        </p>
      </div>

      <div className="space-y-3">
        {stories.map((s) => {
          const uploads = s.uploads ?? [];
          const photos = uploads.length
            ? uploads.map((src) => ({ src, upload: true }))
            : s.seeds.map((seed) => ({ src: img(seed, 400, 520), upload: false }));

          return (
            <details key={s.slug} className="group border border-sand-deep">
              <summary className="flex cursor-pointer items-center justify-between gap-4 p-5">
                <span>
                  <span className="font-display text-lg text-ink">{s.couple}</span>
                  <span className="ml-3 text-[0.72rem] text-muted">
                    {s.venue[l]} · {uploads.length > 0 ? `${uploads.length} ${de ? "eigene Bilder" : "kendi göreseliniz"}` : de ? "Platzhalter" : "temsili görsel"}
                  </span>
                </span>
                <span className="text-[0.66rem] uppercase tracking-[0.16em] text-gold group-open:hidden">
                  {de ? "Bearbeiten" : "Düzenle"}
                </span>
              </summary>

              <div className="space-y-7 border-t border-sand-deep p-6">
                <GalleryUploader code={s.slug} locale={l} upload={uploadStoryPhotos} />

                <div className="grid grid-cols-4 gap-3 sm:grid-cols-6">
                  {photos.map((p, i) => (
                    <div key={i} className="group/ph relative aspect-[3/4] overflow-hidden border border-sand-deep">
                      {/* eslint-disable-next-line @next/next/no-img-element -- gemischte Quellen */}
                      <img src={p.src} alt="" className="h-full w-full object-cover" loading="lazy" />
                      {p.upload && i === 0 && (
                        <span className="absolute left-1 top-1 bg-gold px-2 py-0.5 text-[0.55rem] uppercase tracking-[0.14em] text-cream">
                          {de ? "Titelbild" : "Kapak"}
                        </span>
                      )}
                      {p.upload && i > 0 && (
                        <form action={makeStoryCoverPhoto} className="absolute inset-x-0 top-0">
                          <input type="hidden" name="slug" value={s.slug} />
                          <input type="hidden" name="index" value={i} />
                          <button className="w-full bg-gold/80 py-1.5 text-[0.55rem] uppercase tracking-[0.14em] text-cream opacity-0 transition-opacity group-hover/ph:opacity-100 hover:bg-gold">
                            {de ? "Als Titelbild" : "Kapak yap"}
                          </button>
                        </form>
                      )}
                      {p.upload && (
                        <form action={deleteStoryPhoto} className="absolute inset-x-0 bottom-0">
                          <input type="hidden" name="slug" value={s.slug} />
                          <input type="hidden" name="index" value={i} />
                          <button className="w-full bg-ink/80 py-1.5 text-[0.55rem] uppercase tracking-[0.14em] text-cream opacity-0 transition-opacity group-hover/ph:opacity-100">
                            {de ? "Löschen" : "Sil"}
                          </button>
                        </form>
                      )}
                    </div>
                  ))}
                </div>

                <form action={editStory} className="space-y-7">
                  <input type="hidden" name="slug" value={s.slug} />

                  <div className="grid gap-7 md:grid-cols-4">
                    <div>
                      <label className={label}>{de ? "Paar" : "Çift"}</label>
                      <input name="couple" defaultValue={s.couple} className={input} />
                    </div>
                    <div>
                      <label className={label}>{de ? "Gäste" : "Kişi"}</label>
                      <input name="guests" defaultValue={s.guests} className={input} />
                    </div>
                    <div>
                      <label className={label}>{de ? "Stadtseite" : "Şehir sayfası"}</label>
                      <select name="citySlug" defaultValue={s.citySlug} className={input}>
                        {cities.map((c) => (
                          <option key={c.slug} value={c.slug}>
                            {c.name}
                          </option>
                        ))}
                      </select>
                    </div>
                    <div>
                      <label className={label}>Location</label>
                      <select name="venueSlug" defaultValue={s.venueSlug ?? ""} className={input}>
                        <option value="">—</option>
                        {venues.map((v) => (
                          <option key={v.slug} value={v.slug}>
                            {v.name}
                          </option>
                        ))}
                      </select>
                    </div>
                  </div>

                  <div className="mt-7">
                    <label className={label}>{de ? "Hochzeitsfilm (YouTube / Vimeo)" : "Düğün filmi (YouTube / Vimeo)"}</label>
                    <input
                      name="video"
                      defaultValue={s.videoUrl ?? ""}
                      className={input}
                      placeholder="https://vimeo.com/123456789"
                    />
                    <p className="mt-2 text-[0.72rem] leading-relaxed text-muted">
                      {de
                        ? "Link von YouTube oder Vimeo einfügen – der Film erscheint oben in der Reportage. Leer lassen, wenn es keinen gibt."
                        : "YouTube veya Vimeo bağlantısını yapıştırın – film hikâyenin başında görünür. Yoksa boş bırakın."}
                    </p>
                  </div>

                  <div className="grid gap-7 md:grid-cols-2">
                    <div className="grid grid-cols-2 gap-4">
                      <div>
                        <label className={label}>{de ? "Location-Text (DE)" : "Mekân yazısı (DE)"}</label>
                        <input name="venue_de" defaultValue={s.venue.de} className={input} />
                      </div>
                      <div>
                        <label className={label}>{de ? "Location-Text (TR)" : "Mekân yazısı (TR)"}</label>
                        <input name="venue_tr" defaultValue={s.venue.tr} className={input} />
                      </div>
                    </div>
                    <div className="grid grid-cols-2 gap-4">
                      <div>
                        <label className={label}>{de ? "Monat (DE)" : "Ay (DE)"}</label>
                        <input name="month_de" defaultValue={s.month.de} className={input} />
                      </div>
                      <div>
                        <label className={label}>{de ? "Monat (TR)" : "Ay (TR)"}</label>
                        <input name="month_tr" defaultValue={s.month.tr} className={input} />
                      </div>
                    </div>
                    <div>
                      <label className={label}>{de ? "Einleitung (DE)" : "Giriş (DE)"}</label>
                      <textarea name="intro_de" rows={3} defaultValue={s.intro.de} className={area} />
                    </div>
                    <div>
                      <label className={label}>{de ? "Einleitung (TR)" : "Giriş (TR)"}</label>
                      <textarea name="intro_tr" rows={3} defaultValue={s.intro.tr} className={area} />
                    </div>
                    <div>
                      <label className={label}>{de ? "Text (DE)" : "Metin (DE)"}</label>
                      <textarea name="body_de" rows={6} defaultValue={s.body.de.join("\n\n")} className={area} />
                    </div>
                    <div>
                      <label className={label}>{de ? "Text (TR)" : "Metin (TR)"}</label>
                      <textarea name="body_tr" rows={6} defaultValue={s.body.tr.join("\n\n")} className={area} />
                    </div>
                    <div>
                      <label className={label}>{de ? "Zitat des Paares (DE)" : "Çiftin yorumu (DE)"}</label>
                      <textarea name="quote_de" rows={2} defaultValue={s.quote.de} className={area} />
                    </div>
                    <div>
                      <label className={label}>{de ? "Zitat des Paares (TR)" : "Çiftin yorumu (TR)"}</label>
                      <textarea name="quote_tr" rows={2} defaultValue={s.quote.tr} className={area} />
                    </div>
                  </div>

                  <div className="flex flex-wrap items-center gap-4">
                    <button className="bg-ink px-8 py-3.5 text-[0.68rem] uppercase tracking-[0.2em] text-cream hover:bg-gold">
                      {de ? "Speichern" : "Kaydet"}
                    </button>
                    <Link
                      href={`/${l}/portfolio/${s.slug}`}
                      className="text-[0.68rem] uppercase tracking-[0.18em] text-muted hover:text-gold"
                    >
                      {de ? "Seite ansehen" : "Sayfayı gör"} ↗
                    </Link>
                  </div>
                </form>

                <form action={deleteStory} className="border-t border-sand-deep pt-5">
                  <input type="hidden" name="slug" value={s.slug} />
                  <button className="text-[0.66rem] uppercase tracking-[0.16em] text-muted transition-colors hover:text-red-800">
                    {de ? "Diese Reportage löschen" : "Bu çekimi sil"}
                  </button>
                </form>
              </div>
            </details>
          );
        })}
      </div>

      <form action={newStory} className="border border-sand-deep p-6">
        <input type="hidden" name="locale" value={l} />
        <h3 className="font-display text-lg text-ink">{de ? "Neue Reportage" : "Yeni çekim"}</h3>
        <div className="mt-6 grid gap-7 md:grid-cols-4">
          <div>
            <label className={label}>{de ? "Paar" : "Çift"} *</label>
            <input name="couple" required className={input} placeholder="Elif & Marco" />
          </div>
          <div>
            <label className={label}>URL</label>
            <input name="slug" className={input} placeholder="elif-marco" />
          </div>
          <div>
            <label className={label}>{de ? "Stadtseite" : "Şehir sayfası"}</label>
            <select name="citySlug" className={input} defaultValue="stuttgart">
              {cities.map((c) => (
                <option key={c.slug} value={c.slug}>
                  {c.name}
                </option>
              ))}
            </select>
          </div>
          <div>
            <label className={label}>Location</label>
            <select name="venueSlug" className={input} defaultValue="">
              <option value="">—</option>
              {venues.map((v) => (
                <option key={v.slug} value={v.slug}>
                  {v.name}
                </option>
              ))}
            </select>
          </div>
          <div>
            <label className={label}>{de ? "Gäste" : "Kişi"}</label>
            <input name="guests" className={input} placeholder="120" />
          </div>
          <div>
            <label className={label}>{de ? "Monat (DE)" : "Ay (DE)"}</label>
            <input name="month_de" className={input} placeholder="Juni" />
          </div>
          <div>
            <label className={label}>{de ? "Monat (TR)" : "Ay (TR)"}</label>
            <input name="month_tr" className={input} placeholder="Haziran" />
          </div>
          <div>
            <label className={label}>{de ? "Location-Text" : "Mekân yazısı"}</label>
            <input name="venue_de" className={input} placeholder="Schloss Solitude, Stuttgart" />
          </div>
        </div>
        <button className="mt-7 bg-ink px-8 py-3.5 text-[0.68rem] uppercase tracking-[0.2em] text-cream hover:bg-gold">
          {de ? "Reportage anlegen" : "Çekim oluştur"}
        </button>
      </form>
    </div>
  );
}
