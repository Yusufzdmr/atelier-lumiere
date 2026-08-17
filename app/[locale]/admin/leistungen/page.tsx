import { getContent } from "@/lib/cms";
import {
  saveServices,
  addService,
  deleteService,
  addProcessStep,
  deleteProcessStep,
  resetSection,
} from "@/lib/actions";
import ImageField from "@/components/admin/ImageField";
import { isLocale, type Locale } from "@/lib/i18n";

export const dynamic = "force-dynamic";

const input =
  "w-full border-b border-sand-deep bg-transparent px-0 py-2.5 text-[0.92rem] text-ink outline-none focus:border-gold";
const label = "block text-[0.6rem] uppercase tracking-[0.18em] text-muted";
const ghost = "text-[0.66rem] uppercase tracking-[0.18em] text-muted transition-colors hover:text-gold";

export default async function AdminServices({ params }: { params: Promise<{ locale: string }> }) {
  const { locale } = await params;
  const l: Locale = isLocale(locale) ? locale : "de";
  const de = l === "de";
  const c = await getContent();

  return (
    <form action={saveServices} className="space-y-10">
      <input type="hidden" name="count" value={c.services.length} />
      <input type="hidden" name="process_count" value={c.process.length} />

      <p className="max-w-2xl text-sm leading-relaxed text-muted">
        {de
          ? "Die Leistungen erscheinen auf der Startseite und auf /leistungen. Absätze im Fließtext durch eine Leerzeile trennen, Stichpunkte eine pro Zeile."
          : "Hizmetler ana sayfada ve /leistungen sayfasında görünür. Uzun metinde paragrafları boş satırla ayırın, maddeleri satır başına bir tane yazın."}
      </p>

      {c.services.map((s, i) => (
        <section key={i} className="border border-sand-deep p-6">
          <div className="flex flex-wrap items-center justify-between gap-4">
            <h2 className="font-display text-xl text-ink">
              {de ? "Leistung" : "Hizmet"} {String(i + 1).padStart(2, "0")}
            </h2>
            <button formAction={deleteService.bind(null, i)} className={ghost}>
              {de ? "Entfernen" : "Sil"}
            </button>
          </div>

          <div className="mt-6 grid gap-7 md:grid-cols-2">
            <div>
              <label className={label}>{de ? "Titel (DE)" : "Başlık (DE)"}</label>
              <input name={`s${i}_title_de`} defaultValue={s.title.de} className={input} />
            </div>
            <div>
              <label className={label}>{de ? "Titel (TR)" : "Başlık (TR)"}</label>
              <input name={`s${i}_title_tr`} defaultValue={s.title.tr} className={input} />
            </div>
            <div>
              <label className={label}>{de ? "Kurztext (DE)" : "Kısa metin (DE)"}</label>
              <textarea name={`s${i}_short_de`} rows={2} defaultValue={s.short.de} className={`${input} resize-none`} />
            </div>
            <div>
              <label className={label}>{de ? "Kurztext (TR)" : "Kısa metin (TR)"}</label>
              <textarea name={`s${i}_short_tr`} rows={2} defaultValue={s.short.tr} className={`${input} resize-none`} />
            </div>
            <div>
              <label className={label}>{de ? "Fließtext (DE)" : "Uzun metin (DE)"}</label>
              <textarea
                name={`s${i}_body_de`}
                rows={7}
                defaultValue={s.body.de.join("\n\n")}
                className={`${input} resize-none`}
              />
            </div>
            <div>
              <label className={label}>{de ? "Fließtext (TR)" : "Uzun metin (TR)"}</label>
              <textarea
                name={`s${i}_body_tr`}
                rows={7}
                defaultValue={s.body.tr.join("\n\n")}
                className={`${input} resize-none`}
              />
            </div>
            <div>
              <label className={label}>{de ? "Enthalten (DE) – eine pro Zeile" : "İçerik (DE) – satır başına bir madde"}</label>
              <textarea
                name={`s${i}_bullets_de`}
                rows={6}
                defaultValue={s.bullets.de.join("\n")}
                className={`${input} resize-none`}
              />
            </div>
            <div>
              <label className={label}>{de ? "Enthalten (TR) – eine pro Zeile" : "İçerik (TR) – satır başına bir madde"}</label>
              <textarea
                name={`s${i}_bullets_tr`}
                rows={6}
                defaultValue={s.bullets.tr.join("\n")}
                className={`${input} resize-none`}
              />
            </div>
            <div>
              <label className={label}>{de ? "Anker (URL-Teil)" : "Bağlantı adı (URL)"}</label>
              <input name={`s${i}_slug`} defaultValue={s.slug} className={input} />
            </div>
            <div>
              <label className={label}>{de ? "Beispielfilm (YouTube, Vimeo oder Datei-URL)" : "Örnek film (YouTube, Vimeo veya dosya URL)"}</label>
              <input name={`s${i}_video`} defaultValue={s.videoUrl ?? ""} className={input} placeholder="https://youtu.be/…" />
            </div>
          </div>

          {/* Hauptbild des Abschnitts */}
          <div className="mt-8 border-t border-sand-deep pt-6">
            <ImageField name={`s${i}_seed`} defaultValue={s.seed} folder={`inhalte/leistungen/${i}`} locale={l} />
          </div>

          {/* Beispielstrecke: vier Plaetze, leere bleiben einfach leer */}
          <div className="mt-8 border-t border-sand-deep pt-6">
            <h3 className="text-[0.66rem] uppercase tracking-[0.2em] text-gold">
              {de ? "Beispielbilder" : "Örnek görseller"}
            </h3>
            <p className="mt-2 max-w-xl text-[0.76rem] leading-relaxed text-muted">
              {de
                ? "Diese Bilder stehen unter dem Text der Leistung. Bleiben alle vier leer, zeigt die Seite passende Bilder aus dem Bestand."
                : "Bu görseller hizmet metninin altında görünür. Dördü de boşsa sayfa stoktan uygun görselleri gösterir."}
            </p>
            <div className="mt-5 grid gap-7 md:grid-cols-2">
              {[0, 1, 2, 3].map((k) => (
                <ImageField
                  key={k}
                  name={`s${i}_photo${k}`}
                  defaultValue={s.photos?.[k] ?? ""}
                  folder={`inhalte/leistungen/${i}/beispiel`}
                  locale={l}
                />
              ))}
            </div>
          </div>
        </section>
      ))}

      <button formAction={addService} className="border border-ink px-6 py-3 text-[0.68rem] uppercase tracking-[0.18em] text-ink transition-colors hover:bg-ink hover:text-cream">
        {de ? "Leistung hinzufügen" : "Hizmet ekle"}
      </button>

      <section className="border border-sand-deep p-6">
        <h2 className="font-display text-xl text-ink">{de ? "Ablauf in Schritten" : "Süreç adımları"}</h2>
        <p className="mt-2 text-[0.78rem] text-muted">
          {de
            ? "Erscheint auf der Startseite. Die Nummerierung vergibt das System automatisch."
            : "Ana sayfada görünür. Numaralandırmayı sistem otomatik yapar."}
        </p>

        <div className="mt-6 space-y-8">
          {c.process.map((w, i) => (
            <div key={i} className="border-t border-sand-deep pt-6 first:border-t-0 first:pt-0">
              <div className="flex items-center justify-between gap-4">
                <span className="text-[0.68rem] uppercase tracking-[0.2em] text-gold">
                  {String(i + 1).padStart(2, "0")}
                </span>
                <button formAction={deleteProcessStep.bind(null, i)} className={ghost}>
                  {de ? "Entfernen" : "Sil"}
                </button>
              </div>
              <div className="mt-4 grid gap-7 md:grid-cols-2">
                <div>
                  <label className={label}>{de ? "Titel (DE)" : "Başlık (DE)"}</label>
                  <input name={`w${i}_title_de`} defaultValue={w.title.de} className={input} />
                </div>
                <div>
                  <label className={label}>{de ? "Titel (TR)" : "Başlık (TR)"}</label>
                  <input name={`w${i}_title_tr`} defaultValue={w.title.tr} className={input} />
                </div>
                <div>
                  <label className={label}>{de ? "Text (DE)" : "Metin (DE)"}</label>
                  <textarea name={`w${i}_text_de`} rows={3} defaultValue={w.text.de} className={`${input} resize-none`} />
                </div>
                <div>
                  <label className={label}>{de ? "Text (TR)" : "Metin (TR)"}</label>
                  <textarea name={`w${i}_text_tr`} rows={3} defaultValue={w.text.tr} className={`${input} resize-none`} />
                </div>
              </div>
            </div>
          ))}
        </div>

        <button
          formAction={addProcessStep}
          className="mt-8 border border-ink px-6 py-3 text-[0.68rem] uppercase tracking-[0.18em] text-ink transition-colors hover:bg-ink hover:text-cream"
        >
          {de ? "Schritt hinzufügen" : "Adım ekle"}
        </button>
      </section>

      <div className="flex flex-wrap items-center gap-4">
        <button className="bg-ink px-9 py-4 text-[0.68rem] uppercase tracking-[0.2em] text-cream transition-colors hover:bg-gold">
          {de ? "Speichern" : "Kaydet"}
        </button>
        <button formAction={resetSection.bind(null, "services")} className={`px-4 py-4 ${ghost}`}>
          {de ? "Auf Standard zurücksetzen" : "Varsayılana döndür"}
        </button>
      </div>
    </form>
  );
}
