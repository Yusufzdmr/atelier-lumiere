import { getContent } from "@/lib/cms";
import { saveTexts, resetTexts } from "@/lib/actions";
import { isLocale, type Locale } from "@/lib/i18n";

export const dynamic = "force-dynamic";

const input =
  "w-full border-b border-sand-deep bg-transparent px-0 py-2.5 text-[0.92rem] text-ink outline-none focus:border-gold";
const label = "block text-[0.6rem] uppercase tracking-[0.18em] text-muted";

export default async function AdminTexts({ params }: { params: Promise<{ locale: string }> }) {
  const { locale } = await params;
  const l: Locale = isLocale(locale) ? locale : "de";
  const de = l === "de";
  const c = await getContent();

  return (
    <form action={saveTexts} className="space-y-12">
      <p className="max-w-2xl text-sm leading-relaxed text-muted">
        {de
          ? "Diese Texte erscheinen direkt auf der Startseite und im Footer – in beiden Sprachen. Änderungen sind nach dem Speichern sofort live."
          : "Bu metinler ana sayfada ve alt bilgide iki dilde de görünür. Kaydettikten sonra anında yayına girer."}
      </p>

      {/* Hero */}
      <section className="border border-sand-deep p-6">
        <h2 className="font-display text-xl text-ink">{de ? "Startseite – Titelbereich" : "Ana sayfa – üst bölüm"}</h2>
        <div className="mt-6 grid gap-7 md:grid-cols-2">
          <div>
            <label className={label}>Eyebrow (DE)</label>
            <input name="hero_eyebrow_de" defaultValue={c.hero.eyebrow.de} className={input} />
          </div>
          <div>
            <label className={label}>Eyebrow (TR)</label>
            <input name="hero_eyebrow_tr" defaultValue={c.hero.eyebrow.tr} className={input} />
          </div>
          <div>
            <label className={label}>{de ? "Überschrift (DE)" : "Başlık (DE)"}</label>
            <textarea name="hero_title_de" rows={2} defaultValue={c.hero.title.de} className={`${input} resize-none`} />
          </div>
          <div>
            <label className={label}>{de ? "Überschrift (TR)" : "Başlık (TR)"}</label>
            <textarea name="hero_title_tr" rows={2} defaultValue={c.hero.title.tr} className={`${input} resize-none`} />
          </div>
          <div>
            <label className={label}>{de ? "Text (DE)" : "Metin (DE)"}</label>
            <textarea name="hero_text_de" rows={3} defaultValue={c.hero.text.de} className={`${input} resize-none`} />
          </div>
          <div>
            <label className={label}>{de ? "Text (TR)" : "Metin (TR)"}</label>
            <textarea name="hero_text_tr" rows={3} defaultValue={c.hero.text.tr} className={`${input} resize-none`} />
          </div>
        </div>
        <p className="mt-3 text-[0.72rem] text-muted">
          {de ? "Zeilenumbruch in der Überschrift = neue Zeile auf der Seite." : "Başlıkta satır sonu = sayfada yeni satır."}
        </p>
      </section>

      {/* Zahlen */}
      <section className="border border-sand-deep p-6">
        <h2 className="font-display text-xl text-ink">{de ? "Zahlen auf der Startseite" : "Ana sayfadaki sayılar"}</h2>
        <div className="mt-6 grid gap-7 sm:grid-cols-2 lg:grid-cols-4">
          <div>
            <label className={label}>{de ? "Hochzeiten" : "Düğün"}</label>
            <input name="stat_weddings" defaultValue={c.stats.weddings} className={input} />
          </div>
          <div>
            <label className={label}>{de ? "Jahre Erfahrung" : "Yıl tecrübe"}</label>
            <input name="stat_years" defaultValue={c.stats.years} className={input} />
          </div>
          <div>
            <label className={label}>{de ? "Wochen bis Galerie" : "Galeriye kadar hafta"}</label>
            <input name="stat_delivery" defaultValue={c.stats.delivery} className={input} />
          </div>
          <div>
            <label className={label}>{de ? "Bewertung" : "Puan"}</label>
            <input name="stat_rating" defaultValue={c.stats.rating} className={input} />
          </div>
        </div>
      </section>

      {/* Kontakt */}
      <section className="border border-sand-deep p-6">
        <h2 className="font-display text-xl text-ink">{de ? "Kontaktdaten" : "İletişim bilgileri"}</h2>
        <div className="mt-6 grid gap-7 md:grid-cols-2">
          <div>
            <label className={label}>{de ? "Telefon (Anzeige)" : "Telefon (görünen)"}</label>
            <input name="phone_human" defaultValue={c.contact.phoneHuman} className={input} />
          </div>
          <div>
            <label className={label}>{de ? "Telefon (Link)" : "Telefon (link)"}</label>
            <input name="phone" defaultValue={c.contact.phone} className={input} />
          </div>
          <div>
            <label className={label}>E-Mail</label>
            <input name="email" defaultValue={c.contact.email} className={input} />
          </div>
          <div>
            <label className={label}>Instagram</label>
            <input name="instagram" defaultValue={c.contact.instagram} className={input} />
          </div>
          <div>
            <label className={label}>{de ? "Straße" : "Sokak"}</label>
            <input name="street" defaultValue={c.contact.street} className={input} />
          </div>
          <div className="grid grid-cols-[100px_1fr] gap-4">
            <div>
              <label className={label}>PLZ</label>
              <input name="zip" defaultValue={c.contact.zip} className={input} />
            </div>
            <div>
              <label className={label}>{de ? "Ort" : "Şehir"}</label>
              <input name="city" defaultValue={c.contact.city} className={input} />
            </div>
          </div>
          <div>
            <label className={label}>{de ? "Zeiten (DE)" : "Saatler (DE)"}</label>
            <input name="hours_de" defaultValue={c.contact.hours.de} className={input} />
          </div>
          <div>
            <label className={label}>{de ? "Zeiten (TR)" : "Saatler (TR)"}</label>
            <input name="hours_tr" defaultValue={c.contact.hours.tr} className={input} />
          </div>
          <div className="md:col-span-2">
            <label className={label}>{de ? "Karte – abweichender Ort (optional)" : "Harita – farklı konum (isteğe bağlı)"}</label>
            <input
              name="maps_query"
              defaultValue={c.contact.mapsQuery}
              className={input}
              placeholder={`${c.contact.street}, ${c.contact.zip} ${c.contact.city}`}
            />
            <p className="mt-2 text-[0.72rem] leading-relaxed text-muted">
              {de
                ? "Leer lassen: Die Karte nutzt automatisch die Anschrift oben. Nur ausfüllen, wenn die Karte woanders hinzeigen soll – Adresse, Name der Location oder Koordinaten wie 48.3705,10.8875."
                : "Boş bırakın: Harita yukarıdaki adresi otomatik kullanır. Yalnızca harita başka bir yeri göstersin istiyorsanız doldurun – adres, mekân adı ya da 48.3705,10.8875 gibi koordinat."}
            </p>
          </div>
        </div>
      </section>

      <div className="flex flex-wrap items-center gap-4">
        <button className="bg-ink px-9 py-4 text-[0.68rem] uppercase tracking-[0.2em] text-cream transition-colors hover:bg-gold">
          {de ? "Speichern" : "Kaydet"}
        </button>
        <button
          formAction={resetTexts}
          className="px-4 py-4 text-[0.68rem] uppercase tracking-[0.2em] text-muted hover:text-gold"
        >
          {de ? "Auf Standard zurücksetzen" : "Varsayılana döndür"}
        </button>
      </div>
    </form>
  );
}
