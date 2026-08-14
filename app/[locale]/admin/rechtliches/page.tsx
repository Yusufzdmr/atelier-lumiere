import { getContent } from "@/lib/cms";
import { saveLegal, addLegalSection, deleteLegalSection, resetSection } from "@/lib/actions";
import { legalPageOrder, type LegalKey } from "@/lib/legal";
import { isLocale, type Locale } from "@/lib/i18n";

export const dynamic = "force-dynamic";

const input =
  "w-full border-b border-sand-deep bg-transparent px-0 py-2.5 text-[0.92rem] text-ink outline-none focus:border-gold";
const label = "block text-[0.6rem] uppercase tracking-[0.18em] text-muted";
const ghost = "text-[0.66rem] uppercase tracking-[0.18em] text-muted transition-colors hover:text-gold";

const pageLabel: Record<LegalKey, string> = {
  impressum: "Impressum",
  datenschutz: "Datenschutz",
  agb: "AGB",
};

export default async function AdminLegal({ params }: { params: Promise<{ locale: string }> }) {
  const { locale } = await params;
  const l: Locale = isLocale(locale) ? locale : "de";
  const de = l === "de";
  const c = await getContent();

  return (
    <div className="space-y-14">
      <div className="max-w-2xl space-y-3 text-sm leading-relaxed text-muted">
        <p>
          {de
            ? "Impressum, Datenschutzerklärung und AGB richten sich nach deutschem Recht und erscheinen deshalb in beiden Sprachfassungen auf Deutsch."
            : "Impressum, gizlilik metni ve AGB Alman hukukuna tabidir; bu yüzden iki dilde de Almanca görünür."}
        </p>
        <p className="text-[0.78rem]">
          {de
            ? "Formatierung im Textfeld: Leerzeile = neuer Absatz · Zeile mit „-“ = Aufzählungspunkt · **fett** · `code` · [Text](URL) · {{consent}} setzt den Button für die Cookie-Einstellungen."
            : "Metin alanı biçimlendirmesi: boş satır = yeni paragraf · başında „- “ olan satır = madde · **kalın** · `kod` · [Metin](URL) · {{consent}} çerez ayarları butonunu yerleştirir."}
        </p>
        <p className="text-[0.78rem]">
          {de
            ? "Platzhalter aus den Kontaktdaten: {legalName} {owner} {street} {zip} {city} {email} {phone}"
            : "İletişim bilgilerinden gelen yer tutucular: {legalName} {owner} {street} {zip} {city} {email} {phone}"}
        </p>
      </div>

      {legalPageOrder.map((key) => {
        const page = c.legal[key];
        return (
          <form key={key} action={saveLegal} className="border border-sand-deep p-6">
            <input type="hidden" name="legal_key" value={key} />
            <input type="hidden" name="count" value={page.sections.length} />

            <div className="flex flex-wrap items-center justify-between gap-4">
              <h2 className="font-display text-xl text-ink">{pageLabel[key]}</h2>
              <a
                href={`/${l}/${key}`}
                target="_blank"
                rel="noopener noreferrer"
                className="text-[0.66rem] uppercase tracking-[0.18em] text-muted hover:text-gold"
              >
                {de ? "Seite ansehen" : "Sayfayı gör"} ↗
              </a>
            </div>

            <div className="mt-6">
              <label className={label}>{de ? "Seitentitel" : "Sayfa başlığı"}</label>
              <input name="legal_title" defaultValue={page.title} className={input} />
            </div>

            <div className="mt-8 space-y-8">
              {page.sections.map((s, i) => (
                <div key={i} className="border-t border-sand-deep pt-6">
                  <div className="flex items-center justify-between gap-4">
                    <span className="text-[0.68rem] uppercase tracking-[0.2em] text-gold">
                      {String(i + 1).padStart(2, "0")}
                    </span>
                    <button formAction={deleteLegalSection.bind(null, i)} className={ghost}>
                      {de ? "Abschnitt entfernen" : "Bölümü sil"}
                    </button>
                  </div>
                  <div className="mt-4 space-y-5">
                    <div>
                      <label className={label}>{de ? "Überschrift" : "Başlık"}</label>
                      <input name={`l${i}_heading`} defaultValue={s.heading} className={input} />
                    </div>
                    <div>
                      <label className={label}>{de ? "Text" : "Metin"}</label>
                      <textarea
                        name={`l${i}_body`}
                        rows={Math.min(14, Math.max(4, Math.ceil(s.body.length / 95) + 1))}
                        defaultValue={s.body}
                        className={`${input} resize-y leading-relaxed`}
                      />
                    </div>
                  </div>
                </div>
              ))}
            </div>

            <button
              formAction={addLegalSection}
              className="mt-8 border border-ink px-6 py-3 text-[0.68rem] uppercase tracking-[0.18em] text-ink transition-colors hover:bg-ink hover:text-cream"
            >
              {de ? "Abschnitt hinzufügen" : "Bölüm ekle"}
            </button>

            <div className="mt-8 border-t border-sand-deep pt-6">
              <label className={label}>{de ? "Hinweis am Seitenende" : "Sayfa sonundaki not"}</label>
              <textarea name="legal_note" rows={3} defaultValue={page.note} className={`${input} resize-none`} />
            </div>

            <div className="mt-8 flex flex-wrap items-center gap-4">
              <button className="bg-ink px-9 py-4 text-[0.68rem] uppercase tracking-[0.2em] text-cream transition-colors hover:bg-gold">
                {de ? "Speichern" : "Kaydet"}
              </button>
              <button formAction={resetSection.bind(null, "legal")} className={`px-4 py-4 ${ghost}`}>
                {de ? "Vorlage wiederherstellen" : "Şablonu geri yükle"}
              </button>
            </div>
          </form>
        );
      })}
    </div>
  );
}
