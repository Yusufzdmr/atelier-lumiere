import SeoFields from "@/components/admin/SeoFields";
import SeoImage from "@/components/admin/SeoImage";
import { getMarketing } from "@/lib/cms";
import { seoPages } from "@/lib/marketing";
import { saveSeo, resetSeo, uploadSeoImage, removeSeoImage } from "@/lib/actions";
import { site } from "@/lib/site";
import { isLocale, type Locale } from "@/lib/i18n";

export const dynamic = "force-dynamic";

const input =
  "w-full border-b border-sand-deep bg-transparent px-0 py-2.5 text-[0.92rem] text-ink outline-none focus:border-gold";
const label = "block text-[0.6rem] uppercase tracking-[0.18em] text-muted";

export default async function AdminSeo({ params }: { params: Promise<{ locale: string }> }) {
  const { locale } = await params;
  const l: Locale = isLocale(locale) ? locale : "de";
  const de = l === "de";
  const m = await getMarketing();

  return (
    <div className="space-y-10">
      <div>
        <h2 className="font-display text-xl text-ink">{de ? "SEO – Titel & Beschreibungen" : "SEO – başlık & açıklamalar"}</h2>
        <p className="mt-2 max-w-3xl text-sm leading-relaxed text-muted">
          {de
            ? "Das hier ist der Text, den Google in der Trefferliste anzeigt: die blaue Zeile und die zwei Zeilen darunter. Jede Seite in beiden Sprachen. Bleibt ein Feld leer, setzt die Seite ihren eingebauten Text ein – es geht also nie etwas verloren."
            : "Burası Google'ın sonuç listesinde gösterdiği metin: mavi satır ve altındaki iki satır. Her sayfa, iki dilde. Alan boş kalırsa sayfa kendi hazır metnini kullanır – yani hiçbir zaman boşa düşmez."}
        </p>
        <p className="mt-2 max-w-3xl text-[0.78rem] leading-relaxed text-muted">
          {de
            ? "Analytics, Google Ads und die Bestätigungscodes der Suchmaschinen liegen unter „Integrationen“."
            : "Analytics, Google Ads ve arama motoru doğrulama kodları „Entegrasyonlar“ bölümünde."}
        </p>
      </div>

      <form action={saveSeo} className="space-y-8">
        {/* ------------------------- Standardbild ------------------------- */}
        <section className="border border-sand-deep p-6">
          <h3 className="font-display text-lg text-ink">{de ? "Standard-Vorschaubild" : "Varsayılan önizleme görseli"}</h3>
          <p className="mt-2 max-w-2xl text-[0.78rem] leading-relaxed text-muted">
            {de
              ? "Wird angezeigt, wenn jemand einen Link zur Seite in WhatsApp, Facebook oder Instagram teilt – für alle Seiten ohne eigenes Bild. Bestes Format: 1200 × 630 Pixel."
              : "Birisi siteden bir linki WhatsApp, Facebook veya Instagram'da paylaştığında görünür – kendi görseli olmayan tüm sayfalar için. En iyi ölçü: 1200 × 630 piksel."}
          </p>
          <div className="mt-5">
            <SeoImage
              pageKey="default"
              locale={l}
              current={m.defaultImage}
              upload={uploadSeoImage}
              remove={removeSeoImage}
            />
          </div>
        </section>

        {/* --------------------------- Seiten --------------------------- */}
        {seoPages.map((page, i) => {
          const entry = m.pages[page.key] ?? {
            title: { de: "", tr: "" },
            description: { de: "", tr: "" },
            noindex: false,
            image: "",
          };
          const path = page.path === "/" ? "" : page.path;

          return (
            <details key={page.key} open={i === 0} className="border border-sand-deep p-6">
              <summary className="flex cursor-pointer flex-wrap items-center justify-between gap-3">
                <span className="font-display text-lg text-ink">{page.label[l]}</span>
                <span className="text-[0.68rem] uppercase tracking-[0.14em] text-muted">
                  /{l}
                  {path}
                  {entry.noindex && <span className="ml-3 text-red-700">noindex</span>}
                </span>
              </summary>

              <div className="mt-7 grid gap-9 lg:grid-cols-2">
                <SeoFields
                  pageKey={page.key}
                  lang="de"
                  langLabel="DE"
                  ui={l}
                  title={entry.title.de}
                  description={entry.description.de}
                  url={`${site.url}/de${path}`}
                  auto={page.auto?.[l]}
                />
                <SeoFields
                  pageKey={page.key}
                  lang="tr"
                  langLabel="TR"
                  ui={l}
                  title={entry.title.tr}
                  description={entry.description.tr}
                  url={`${site.url}/tr${path}`}
                  auto={page.auto?.[l]}
                />
              </div>

              <div className="mt-7 flex flex-wrap items-center justify-between gap-6 border-t border-sand-deep pt-6">
                <SeoImage
                  pageKey={page.key}
                  locale={l}
                  current={entry.image}
                  upload={uploadSeoImage}
                  remove={removeSeoImage}
                />
                <label className="flex cursor-pointer items-center gap-3 text-[0.8rem] text-ink">
                  <input
                    type="checkbox"
                    name={`seo_${page.key}_noindex`}
                    defaultChecked={entry.noindex}
                    className="h-4 w-4 accent-[#B08D57]"
                  />
                  {de ? "Nicht in Google aufnehmen" : "Google'a girmesin"}
                </label>
              </div>
            </details>
          );
        })}

        {/* --------------------------- Vorlagen --------------------------- */}
        <section className="border border-sand-deep p-6">
          <h3 className="font-display text-lg text-ink">{de ? "Titel-Vorlagen" : "Başlık şablonları"}</h3>
          <p className="mt-2 max-w-3xl text-[0.78rem] leading-relaxed text-muted">
            {de
              ? "Für die Seiten, die es viele Male gibt. Der Platzhalter in geschweiften Klammern wird eingesetzt – aus {name} wird also der Stadt- oder Locationname. Feld leer lassen = eingebauter Titel."
              : "Çok sayıda örneği olan sayfalar için. Süslü parantez içindeki yer tutucu yerine gerçek değer gelir – {name} yerine şehir ya da mekân adı yazılır. Alan boşsa hazır başlık kullanılır."}
          </p>

          <div className="mt-6 grid gap-7 md:grid-cols-2">
            {(
              [
                ["city", de ? "Stadtseiten – {name}" : "Şehir sayfaları – {name}", m.templates.city],
                ["venue", de ? "Locations – {name}" : "Mekânlar – {name}", m.templates.venue],
                ["post", de ? "Ratgeber – {title}" : "Rehber – {title}", m.templates.post],
                ["story", de ? "Portfolio – {couple}, {venue}" : "Portfolyo – {couple}, {venue}", m.templates.story],
              ] as const
            ).map(([key, caption, value]) => (
              <div key={key} className="space-y-4">
                <div className="text-[0.66rem] uppercase tracking-[0.16em] text-muted">{caption}</div>
                <div>
                  <label className={label}>DE</label>
                  <input name={`tpl_${key}_de`} defaultValue={value.de} className={input} />
                </div>
                <div>
                  <label className={label}>TR</label>
                  <input name={`tpl_${key}_tr`} defaultValue={value.tr} className={input} />
                </div>
              </div>
            ))}
          </div>
        </section>

        <div className="flex flex-wrap items-center gap-4">
          <button className="bg-ink px-9 py-4 text-[0.68rem] uppercase tracking-[0.2em] text-cream transition-colors hover:bg-gold">
            {de ? "Speichern" : "Kaydet"}
          </button>
          <button
            formAction={resetSeo}
            className="px-4 py-4 text-[0.68rem] uppercase tracking-[0.2em] text-muted hover:text-gold"
          >
            {de ? "Auf Standard zurücksetzen" : "Varsayılana döndür"}
          </button>
        </div>
      </form>
    </div>
  );
}
