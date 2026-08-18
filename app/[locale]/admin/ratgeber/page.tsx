import Link from "next/link";

import GalleryUploader from "@/components/admin/GalleryUploader";
import { getPosts, getCities, getVenues } from "@/lib/cms";
import { editPost, newPost, deletePost, uploadPostPhotos, deletePostPhoto, makePostCoverPhoto } from "@/lib/actions";
import { img } from "@/lib/images";
import { isLocale, type Locale } from "@/lib/i18n";

export const dynamic = "force-dynamic";

const input =
  "w-full border-b border-sand-deep bg-transparent px-0 py-2.5 text-[0.92rem] text-ink outline-none focus:border-gold";
const area = `${input} resize-none leading-relaxed`;
const label = "block text-[0.6rem] uppercase tracking-[0.18em] text-muted";
const ghost = "text-[0.66rem] uppercase tracking-[0.18em] text-muted transition-colors hover:text-gold";

export default async function AdminBlog({ params }: { params: Promise<{ locale: string }> }) {
  const { locale } = await params;
  const l: Locale = isLocale(locale) ? locale : "de";
  const de = l === "de";
  const [posts, cities, venues] = await Promise.all([getPosts(), getCities(), getVenues()]);

  return (
    <div className="space-y-8">
      <div>
        <h2 className="font-display text-xl text-ink">{de ? "Ratgeber" : "Rehber"}</h2>
        <p className="mt-2 max-w-2xl text-sm leading-relaxed text-muted">
          {de
            ? "Jeder Beitrag ist eine eigene Seite unter /ratgeber. Die Verknüpfung mit Stadt und Location ist der eigentliche SEO-Hebel: Der Beitrag verlinkt dorthin, die Stadtseite zeigt den Beitrag in der Seitenspalte."
            : "Her yazı /ratgeber altında ayrı bir sayfadır. Asıl SEO kazancı şehir ve mekân bağlantısında: yazı oraya link verir, şehir sayfası da yazıyı kenar sütununda gösterir."}
        </p>
        <p className="mt-2 max-w-2xl text-[0.78rem] leading-relaxed text-muted">
          {de
            ? "Absätze im Fließtext durch eine Leerzeile trennen. Eine Zeile, die mit ## beginnt, wird zur Zwischenüberschrift."
            : "Uzun metinde paragrafları boş satırla ayırın. ## ile başlayan satır ara başlık olur."}
        </p>
      </div>

      {posts.map((s) => (
        <details key={s.slug} className="border border-sand-deep">
          <summary className="flex cursor-pointer flex-wrap items-center justify-between gap-4 px-6 py-4">
            <span className="font-display text-lg text-ink">{s.title[l] || s.slug}</span>
            <span className="text-[0.66rem] uppercase tracking-[0.18em] text-muted">{s.date}</span>
          </summary>

          <div className="border-t border-sand-deep p-6">
            <form action={editPost} className="space-y-7">
              <input type="hidden" name="slug" value={s.slug} />

              <div className="grid gap-7 md:grid-cols-4">
                <div>
                  <label className={label}>{de ? "Datum" : "Tarih"}</label>
                  <input type="date" name="date" defaultValue={s.date} className={input} />
                </div>
                <div>
                  <label className={label}>{de ? "Bild-Kennung" : "Görsel anahtarı"}</label>
                  <input name="seed" defaultValue={s.seed} className={input} />
                </div>
                <div>
                  <label className={label}>{de ? "Stadtseite" : "Şehir sayfası"}</label>
                  <select name="citySlug" defaultValue={s.citySlug ?? ""} className={input}>
                    <option value="">—</option>
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

              <div className="grid gap-7 md:grid-cols-2">
                <div>
                  <label className={label}>{de ? "Titel (DE)" : "Başlık (DE)"}</label>
                  <input name="title_de" defaultValue={s.title.de} className={input} />
                </div>
                <div>
                  <label className={label}>{de ? "Titel (TR)" : "Başlık (TR)"}</label>
                  <input name="title_tr" defaultValue={s.title.tr} className={input} />
                </div>
                <div>
                  <label className={label}>{de ? "Anrisstext (DE)" : "Özet (DE)"}</label>
                  <textarea name="excerpt_de" rows={3} defaultValue={s.excerpt.de} className={area} />
                </div>
                <div>
                  <label className={label}>{de ? "Anrisstext (TR)" : "Özet (TR)"}</label>
                  <textarea name="excerpt_tr" rows={3} defaultValue={s.excerpt.tr} className={area} />
                </div>
                <div>
                  <label className={label}>{de ? "Text (DE)" : "Metin (DE)"}</label>
                  <textarea name="body_de" rows={16} defaultValue={s.body.de.join("\n\n")} className={area} />
                </div>
                <div>
                  <label className={label}>{de ? "Text (TR)" : "Metin (TR)"}</label>
                  <textarea name="body_tr" rows={16} defaultValue={s.body.tr.join("\n\n")} className={area} />
                </div>
                <div>
                  <label className={label}>{de ? "FAQ (DE) – Frage | Antwort" : "SSS (DE) – Soru | Cevap"}</label>
                  <textarea
                    name="faq_de"
                    rows={5}
                    defaultValue={(s.faq ?? []).map((f) => `${f.q.de} | ${f.a.de}`).join("\n")}
                    className={area}
                  />
                </div>
                <div>
                  <label className={label}>{de ? "FAQ (TR) – Frage | Antwort" : "SSS (TR) – Soru | Cevap"}</label>
                  <textarea
                    name="faq_tr"
                    rows={5}
                    defaultValue={(s.faq ?? []).map((f) => `${f.q.tr} | ${f.a.tr}`).join("\n")}
                    className={area}
                  />
                </div>
              </div>

              <div className="flex flex-wrap items-center gap-4">
                <button className="bg-ink px-9 py-4 text-[0.68rem] uppercase tracking-[0.2em] text-cream transition-colors hover:bg-gold">
                  {de ? "Speichern" : "Kaydet"}
                </button>
                <Link href={`/${l}/ratgeber/${s.slug}`} target="_blank" className={ghost}>
                  {de ? "Seite ansehen" : "Sayfayı gör"} ↗
                </Link>
              </div>
            </form>

            <div className="mt-8 border-t border-sand-deep pt-6">
              <h3 className="text-[0.66rem] uppercase tracking-[0.2em] text-gold">
                {de ? "Bilder" : "Görseller"}
              </h3>
              <p className="mt-2 text-[0.75rem] text-muted">
                {de
                  ? "Das erste Bild ist das Titelbild, das zweite erscheint in der Seitenspalte."
                  : "İlk görsel kapak olur, ikincisi kenar sütununda görünür."}
              </p>

              {(s.uploads?.length ?? 0) > 0 && (
                <div className="mt-5 flex flex-wrap gap-3">
                  {(s.uploads ?? []).map((src, i) => (
                    <div key={i} className="group/ph relative overflow-hidden">
                      {/* eslint-disable-next-line @next/next/no-img-element -- Blob-URL, kein Next-Loader nötig */}
                      <img src={img(src, 200, 140)} alt="" className="h-24 w-36 object-cover" />
                      {i === 0 && (
                        <span className="absolute left-1 top-1 bg-gold px-2 py-0.5 text-[0.55rem] uppercase tracking-[0.14em] text-cream">
                          {de ? "Titelbild" : "Kapak"}
                        </span>
                      )}
                      {i > 0 && (
                        <form action={makePostCoverPhoto} className="absolute inset-x-0 top-0">
                          <input type="hidden" name="slug" value={s.slug} />
                          <input type="hidden" name="index" value={i} />
                          <button className="w-full bg-gold/80 py-1 text-[0.55rem] uppercase tracking-[0.14em] text-cream opacity-0 transition-opacity group-hover/ph:opacity-100 hover:bg-gold">
                            {de ? "Als Titelbild" : "Kapak yap"}
                          </button>
                        </form>
                      )}
                      <form action={deletePostPhoto} className="absolute inset-x-0 bottom-0">
                        <input type="hidden" name="slug" value={s.slug} />
                        <input type="hidden" name="index" value={i} />
                        <button className="w-full bg-ink/80 py-1 text-[0.55rem] uppercase tracking-[0.14em] text-cream opacity-0 transition-opacity group-hover/ph:opacity-100">
                          {de ? "Löschen" : "Sil"}
                        </button>
                      </form>
                    </div>
                  ))}
                </div>
              )}

              <div className="mt-5">
                <GalleryUploader code={s.slug} locale={l} upload={uploadPostPhotos} />
              </div>
            </div>

            <form action={deletePost} className="mt-8 border-t border-sand-deep pt-6">
              <input type="hidden" name="slug" value={s.slug} />
              <button className={ghost}>{de ? "Beitrag löschen" : "Yazıyı sil"}</button>
            </form>
          </div>
        </details>
      ))}

      <form action={newPost} className="border border-gold/50 bg-sand/30 p-6">
        <input type="hidden" name="locale" value={l} />
        <h3 className="font-display text-lg text-ink">{de ? "Neuer Beitrag" : "Yeni yazı"}</h3>

        <div className="mt-6 grid gap-7 md:grid-cols-2">
          <div>
            <label className={label}>{de ? "Titel (DE)" : "Başlık (DE)"} *</label>
            <input name="title_de" required className={input} />
          </div>
          <div>
            <label className={label}>{de ? "Titel (TR)" : "Başlık (TR)"}</label>
            <input name="title_tr" className={input} />
          </div>
          <div>
            <label className={label}>{de ? "URL-Teil (optional)" : "URL adı (isteğe bağlı)"}</label>
            <input name="slug" className={input} placeholder="hochzeit-zeitplan-licht" />
          </div>
          <div>
            <label className={label}>{de ? "Datum" : "Tarih"}</label>
            <input type="date" name="date" className={input} />
          </div>
          <div>
            <label className={label}>{de ? "Stadtseite" : "Şehir sayfası"}</label>
            <select name="citySlug" className={input}>
              <option value="">—</option>
              {cities.map((c) => (
                <option key={c.slug} value={c.slug}>
                  {c.name}
                </option>
              ))}
            </select>
          </div>
          <div>
            <label className={label}>Location</label>
            <select name="venueSlug" className={input}>
              <option value="">—</option>
              {venues.map((v) => (
                <option key={v.slug} value={v.slug}>
                  {v.name}
                </option>
              ))}
            </select>
          </div>
          <div>
            <label className={label}>{de ? "Anrisstext (DE)" : "Özet (DE)"}</label>
            <textarea name="excerpt_de" rows={3} className={area} />
          </div>
          <div>
            <label className={label}>{de ? "Anrisstext (TR)" : "Özet (TR)"}</label>
            <textarea name="excerpt_tr" rows={3} className={area} />
          </div>
          <div>
            <label className={label}>{de ? "Text (DE)" : "Metin (DE)"}</label>
            <textarea name="body_de" rows={8} className={area} />
          </div>
          <div>
            <label className={label}>{de ? "Text (TR)" : "Metin (TR)"}</label>
            <textarea name="body_tr" rows={8} className={area} />
          </div>
        </div>

        <button className="mt-7 bg-ink px-9 py-4 text-[0.68rem] uppercase tracking-[0.2em] text-cream transition-colors hover:bg-gold">
          {de ? "Beitrag anlegen" : "Yazı oluştur"}
        </button>
      </form>
    </div>
  );
}
