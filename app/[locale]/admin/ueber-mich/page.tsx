import { getContent } from "@/lib/cms";
import {
  saveAbout,
  addTestimonial,
  deleteTestimonial,
  addFaqItem,
  deleteFaqItem,
  resetSection,
} from "@/lib/actions";
import { isLocale, type Locale } from "@/lib/i18n";

export const dynamic = "force-dynamic";

const input =
  "w-full border-b border-sand-deep bg-transparent px-0 py-2.5 text-[0.92rem] text-ink outline-none focus:border-gold";
const label = "block text-[0.6rem] uppercase tracking-[0.18em] text-muted";
const ghost = "text-[0.66rem] uppercase tracking-[0.18em] text-muted transition-colors hover:text-gold";
const addBtn =
  "border border-ink px-6 py-3 text-[0.68rem] uppercase tracking-[0.18em] text-ink transition-colors hover:bg-ink hover:text-cream";

export default async function AdminAbout({ params }: { params: Promise<{ locale: string }> }) {
  const { locale } = await params;
  const l: Locale = isLocale(locale) ? locale : "de";
  const de = l === "de";
  const c = await getContent();
  const a = c.about;

  return (
    <form action={saveAbout} className="space-y-10">
      <input type="hidden" name="testimonial_count" value={c.testimonials.length} />
      <input type="hidden" name="faq_count" value={c.faq.length} />

      <p className="max-w-2xl text-sm leading-relaxed text-muted">
        {de
          ? "Seite Uber-mich, Kundenstimmen auf der Startseite und die allgemeinen Fragen unter Startseite und Preise."
          : "Hakkımda sayfası, ana sayfadaki müşteri yorumları ve ana sayfa ile fiyatlar sayfasındaki genel sorular."}
      </p>

      {/* Über mich */}
      <section className="border border-sand-deep p-6">
        <h2 className="font-display text-xl text-ink">{de ? "Über mich" : "Hakkımda"}</h2>

        <div className="mt-6 grid gap-7 md:grid-cols-2">
          <div>
            <label className={label}>{de ? "Name" : "Ad"}</label>
            <input name="about_name" defaultValue={a.name} className={input} />
          </div>
          <div />
          <div>
            <label className={label}>{de ? "Einleitung (DE)" : "Giriş (DE)"}</label>
            <textarea name="about_lead_de" rows={2} defaultValue={a.lead.de} className={`${input} resize-none`} />
          </div>
          <div>
            <label className={label}>{de ? "Einleitung (TR)" : "Giriş (TR)"}</label>
            <textarea name="about_lead_tr" rows={2} defaultValue={a.lead.tr} className={`${input} resize-none`} />
          </div>
          <div>
            <label className={label}>{de ? "Fließtext (DE)" : "Uzun metin (DE)"}</label>
            <textarea
              name="about_body_de"
              rows={10}
              defaultValue={a.body.de.join("\n\n")}
              className={`${input} resize-none`}
            />
          </div>
          <div>
            <label className={label}>{de ? "Fließtext (TR)" : "Uzun metin (TR)"}</label>
            <textarea
              name="about_body_tr"
              rows={10}
              defaultValue={a.body.tr.join("\n\n")}
              className={`${input} resize-none`}
            />
          </div>
          <div>
            <label className={label}>{de ? "Überschrift Arbeitsweise (DE)" : "Çalışma şekli başlığı (DE)"}</label>
            <input name="about_values_title_de" defaultValue={a.valuesTitle.de} className={input} />
          </div>
          <div>
            <label className={label}>{de ? "Überschrift Arbeitsweise (TR)" : "Çalışma şekli başlığı (TR)"}</label>
            <input name="about_values_title_tr" defaultValue={a.valuesTitle.tr} className={input} />
          </div>
          <div>
            <label className={label}>{de ? "Arbeitsweise (DE) – Titel | Text" : "Çalışma şekli (DE) – Başlık | Metin"}</label>
            <textarea
              name="about_values_de"
              rows={6}
              defaultValue={a.values.map((v) => `${v.t.de} | ${v.d.de}`).join("\n")}
              className={`${input} resize-none`}
            />
          </div>
          <div>
            <label className={label}>{de ? "Arbeitsweise (TR) – Titel | Text" : "Çalışma şekli (TR) – Başlık | Metin"}</label>
            <textarea
              name="about_values_tr"
              rows={6}
              defaultValue={a.values.map((v) => `${v.t.tr} | ${v.d.tr}`).join("\n")}
              className={`${input} resize-none`}
            />
          </div>
          <div>
            <label className={label}>{de ? "Überschrift Technik (DE)" : "Ekipman başlığı (DE)"}</label>
            <input name="about_gear_title_de" defaultValue={a.gearTitle.de} className={input} />
          </div>
          <div>
            <label className={label}>{de ? "Überschrift Technik (TR)" : "Ekipman başlığı (TR)"}</label>
            <input name="about_gear_title_tr" defaultValue={a.gearTitle.tr} className={input} />
          </div>
          <div>
            <label className={label}>{de ? "Technik (DE) – eine pro Zeile" : "Ekipman (DE) – satır başına bir madde"}</label>
            <textarea
              name="about_gear_de"
              rows={6}
              defaultValue={a.gear.de.join("\n")}
              className={`${input} resize-none`}
            />
          </div>
          <div>
            <label className={label}>{de ? "Technik (TR) – eine pro Zeile" : "Ekipman (TR) – satır başına bir madde"}</label>
            <textarea
              name="about_gear_tr"
              rows={6}
              defaultValue={a.gear.tr.join("\n")}
              className={`${input} resize-none`}
            />
          </div>
        </div>
      </section>

      {/* Stimmen */}
      <section className="border border-sand-deep p-6">
        <h2 className="font-display text-xl text-ink">{de ? "Kundenstimmen" : "Müşteri yorumları"}</h2>

        <div className="mt-6 space-y-8">
          {c.testimonials.map((t, i) => (
            <div key={i} className="border-t border-sand-deep pt-6 first:border-t-0 first:pt-0">
              <div className="flex items-center justify-between gap-4">
                <span className="text-[0.68rem] uppercase tracking-[0.2em] text-gold">
                  {String(i + 1).padStart(2, "0")}
                </span>
                <button formAction={deleteTestimonial.bind(null, i)} className={ghost}>
                  {de ? "Entfernen" : "Sil"}
                </button>
              </div>
              <div className="mt-4 grid gap-7 md:grid-cols-2">
                <div>
                  <label className={label}>{de ? "Namen" : "İsimler"}</label>
                  <input name={`t${i}_name`} defaultValue={t.name} className={input} />
                </div>
                <div className="grid grid-cols-2 gap-4">
                  <div>
                    <label className={label}>{de ? "Ort (DE)" : "Şehir (DE)"}</label>
                    <input name={`t${i}_city_de`} defaultValue={t.city.de} className={input} />
                  </div>
                  <div>
                    <label className={label}>{de ? "Ort (TR)" : "Şehir (TR)"}</label>
                    <input name={`t${i}_city_tr`} defaultValue={t.city.tr} className={input} />
                  </div>
                </div>
                <div>
                  <label className={label}>{de ? "Zitat (DE)" : "Yorum (DE)"}</label>
                  <textarea name={`t${i}_text_de`} rows={3} defaultValue={t.text.de} className={`${input} resize-none`} />
                </div>
                <div>
                  <label className={label}>{de ? "Zitat (TR)" : "Yorum (TR)"}</label>
                  <textarea name={`t${i}_text_tr`} rows={3} defaultValue={t.text.tr} className={`${input} resize-none`} />
                </div>
              </div>
            </div>
          ))}
        </div>

        <button formAction={addTestimonial} className={`mt-8 ${addBtn}`}>
          {de ? "Stimme hinzufügen" : "Yorum ekle"}
        </button>
      </section>

      {/* FAQ */}
      <section className="border border-sand-deep p-6">
        <h2 className="font-display text-xl text-ink">{de ? "Allgemeine Fragen" : "Genel sorular"}</h2>
        <p className="mt-2 text-[0.78rem] text-muted">
          {de
            ? "Erscheint auf der Startseite und unter Preise – und als FAQ-Schema für Google."
            : "Ana sayfada ve fiyatlar sayfasında görünür – ayrıca Google için FAQ şeması olarak."}
        </p>

        <div className="mt-6 space-y-8">
          {c.faq.map((f, i) => (
            <div key={i} className="border-t border-sand-deep pt-6 first:border-t-0 first:pt-0">
              <div className="flex items-center justify-between gap-4">
                <span className="text-[0.68rem] uppercase tracking-[0.2em] text-gold">
                  {String(i + 1).padStart(2, "0")}
                </span>
                <button formAction={deleteFaqItem.bind(null, i)} className={ghost}>
                  {de ? "Entfernen" : "Sil"}
                </button>
              </div>
              <div className="mt-4 grid gap-7 md:grid-cols-2">
                <div>
                  <label className={label}>{de ? "Frage (DE)" : "Soru (DE)"}</label>
                  <input name={`f${i}_q_de`} defaultValue={f.q.de} className={input} />
                </div>
                <div>
                  <label className={label}>{de ? "Frage (TR)" : "Soru (TR)"}</label>
                  <input name={`f${i}_q_tr`} defaultValue={f.q.tr} className={input} />
                </div>
                <div>
                  <label className={label}>{de ? "Antwort (DE)" : "Cevap (DE)"}</label>
                  <textarea name={`f${i}_a_de`} rows={4} defaultValue={f.a.de} className={`${input} resize-none`} />
                </div>
                <div>
                  <label className={label}>{de ? "Antwort (TR)" : "Cevap (TR)"}</label>
                  <textarea name={`f${i}_a_tr`} rows={4} defaultValue={f.a.tr} className={`${input} resize-none`} />
                </div>
              </div>
            </div>
          ))}
        </div>

        <button formAction={addFaqItem} className={`mt-8 ${addBtn}`}>
          {de ? "Frage hinzufügen" : "Soru ekle"}
        </button>
      </section>

      <div className="flex flex-wrap items-center gap-4">
        <button className="bg-ink px-9 py-4 text-[0.68rem] uppercase tracking-[0.2em] text-cream transition-colors hover:bg-gold">
          {de ? "Speichern" : "Kaydet"}
        </button>
        <button formAction={resetSection.bind(null, "about")} className={`px-4 py-4 ${ghost}`}>
          {de ? "Auf Standard zurücksetzen" : "Varsayılana döndür"}
        </button>
      </div>
    </form>
  );
}
