import PaypalTest from "@/components/admin/PaypalTest";
import { getIntegrations, mask } from "@/lib/integrations";
import {
  saveIntegrationSettings,
  addIntegrationKey,
  saveIntegrationKey,
  deleteIntegrationKey,
} from "@/lib/actions";
import { isLocale, type Locale } from "@/lib/i18n";

export const dynamic = "force-dynamic";

const input =
  "w-full border-b border-sand-deep bg-transparent px-0 py-2.5 text-[0.92rem] text-ink outline-none focus:border-gold";
const label = "block text-[0.6rem] uppercase tracking-[0.18em] text-muted";
const hint = "mt-2 text-[0.72rem] leading-relaxed text-muted";

export default async function AdminIntegrations({ params }: { params: Promise<{ locale: string }> }) {
  const { locale } = await params;
  const l: Locale = isLocale(locale) ? locale : "de";
  const de = l === "de";
  const s = await getIntegrations();

  const chip = (on: boolean, name: string) => (
    <span
      key={name}
      className={`border px-3 py-1.5 text-[0.62rem] uppercase tracking-[0.14em] ${
        on ? "border-gold text-gold" : "border-sand-deep text-muted"
      }`}
    >
      {name} {on ? "✓" : "—"}
    </span>
  );

  return (
    <div className="space-y-12">
      <div>
        <h2 className="font-display text-xl text-ink">{de ? "Integrationen" : "Entegrasyonlar"}</h2>
        <p className="mt-2 max-w-3xl text-sm leading-relaxed text-muted">
          {de
            ? "Zugangsdaten fremder Dienste – hier eingetragen, ohne dass jemand am Code arbeiten muss. Was hier steht, hat Vorrang vor den Umgebungsvariablen des Servers; bleibt ein Feld leer, gilt weiter der Wert aus der Serverkonfiguration."
            : "Diğer servislerin erişim bilgileri – kod tarafına dokunmadan buradan girilir. Buradaki değer, sunucudaki ortam değişkeninden önce gelir; alan boşsa sunucudaki değer geçerli kalır."}
        </p>
        <p className={hint}>
          {de
            ? "Geheimnisse werden nur verdeckt angezeigt. Ein leeres Feld bedeutet „unverändert lassen“ – zum Ändern den neuen Wert eintippen."
            : "Gizli değerler maskeli gösterilir. Boş alan „değiştirme“ demektir – değiştirmek için yeni değeri yazın."}
        </p>

        <div className="mt-6 flex flex-wrap gap-2">
          {chip(Boolean(s.paypal.clientId && s.paypal.clientSecret), "PayPal")}
          {chip(Boolean(s.google.gaId), "Analytics")}
          {chip(Boolean(s.google.gtmId), "Tag Manager")}
          {chip(Boolean(s.google.adsId), "Google Ads")}
          {chip(Boolean(s.meta.pixelId), "Meta Pixel")}
          {chip(Boolean(s.google.searchConsole), "Search Console")}
        </div>
      </div>

      <form action={saveIntegrationSettings} className="space-y-10">
        {/* ------------------------------ PayPal ------------------------------ */}
        <section className="border border-sand-deep p-6">
          <h3 className="font-display text-xl text-ink">PayPal</h3>
          <p className={hint}>
            {de
              ? "Aus dem PayPal-Entwicklerkonto: Apps & Credentials → Live (oder Sandbox) → App auswählen. Es werden nur Client-ID und Secret gebraucht, niemals das Kontopasswort."
              : "PayPal geliştirici hesabından: Apps & Credentials → Live (veya Sandbox) → uygulamayı seçin. Yalnızca Client ID ve Secret gerekir, hesap parolası asla."}
          </p>

          <div className="mt-6 grid gap-7 md:grid-cols-2">
            <div>
              <label className={label}>Client ID</label>
              <input name="paypal_client_id" className={input} placeholder={mask(s.paypal.clientId) || "AeA1QIZX…"} />
            </div>
            <div>
              <label className={label}>Secret</label>
              <input
                name="paypal_secret"
                type="password"
                autoComplete="new-password"
                className={input}
                placeholder={mask(s.paypal.clientSecret) || "EGnHDxD_qRp…"}
              />
            </div>
            <div>
              <label className={label}>{de ? "Modus" : "Mod"}</label>
              <select name="paypal_mode" defaultValue={s.paypal.mode} className={input}>
                <option value="sandbox">Sandbox ({de ? "Test" : "test"})</option>
                <option value="live">Live ({de ? "echtes Geld" : "gerçek ödeme"})</option>
              </select>
              <p className={hint}>
                {de
                  ? "Sandbox-Schlüssel funktionieren nur im Sandbox-Modus – und umgekehrt."
                  : "Sandbox anahtarları yalnızca sandbox modunda çalışır – tersi de geçerli."}
              </p>
            </div>
          </div>
        </section>

        {/* ------------------------------ Google ------------------------------ */}
        <section className="border border-sand-deep p-6">
          <h3 className="font-display text-xl text-ink">Google</h3>
          <p className={hint}>
            {de
              ? "Analytics und Ads laufen erst, nachdem der Besucher im Cookie-Banner zugestimmt hat: Statistik schaltet Analytics frei, Marketing schaltet Ads und den Pixel frei. Ohne Einwilligung wird nicht einmal das Skript geladen."
              : "Analytics ve Ads yalnızca ziyaretçi çerez bandında onay verdikten sonra çalışır: istatistik onayı Analytics'i, pazarlama onayı Ads ve pikseli açar. Onay yoksa script bile yüklenmez."}
          </p>

          <div className="mt-6 grid gap-7 md:grid-cols-3">
            <div>
              <label className={label}>Analytics 4 (G-…)</label>
              <input name="ga_id" defaultValue={s.google.gaId} className={input} placeholder="G-XXXXXXXXXX" />
            </div>
            <div>
              <label className={label}>Tag Manager (GTM-…)</label>
              <input name="gtm_id" defaultValue={s.google.gtmId} className={input} placeholder="GTM-XXXXXXX" />
            </div>
            <div>
              <label className={label}>Ads Conversion-ID (AW-…)</label>
              <input name="ads_id" defaultValue={s.google.adsId} className={input} placeholder="AW-123456789" />
            </div>
          </div>

          <div className="mt-8 border-t border-sand-deep pt-6">
            <div className="text-[0.66rem] uppercase tracking-[0.18em] text-muted">
              {de ? "Conversion-Label" : "Dönüşüm etiketleri"}
            </div>
            <p className={hint}>
              {de
                ? "In Google Ads steht bei jeder Conversion ein Wert wie AW-123456789/AbC-D_efG. Hier gehört nur der Teil nach dem Schrägstrich hinein."
                : "Google Ads'te her dönüşümde AW-123456789/AbC-D_efG gibi bir değer olur. Buraya yalnızca eğik çizgiden sonraki kısım yazılır."}
            </p>
            <div className="mt-5 grid gap-7 md:grid-cols-3">
              <div>
                <label className={label}>{de ? "Anfrage über das Formular" : "Formdan gelen talep"}</label>
                <input name="ads_label_contact" defaultValue={s.google.adsLabels.contact} className={input} />
              </div>
              <div>
                <label className={label}>{de ? "Einladung erstellt" : "Davetiye oluşturuldu"}</label>
                <input name="ads_label_invite" defaultValue={s.google.adsLabels.invite} className={input} />
              </div>
              <div>
                <label className={label}>{de ? "Klick auf die Telefonnummer" : "Telefon numarasına tıklama"}</label>
                <input name="ads_label_phone" defaultValue={s.google.adsLabels.phone} className={input} />
              </div>
            </div>

            <div className="mt-7 grid gap-7 md:grid-cols-3">
              <div>
                <label className={label}>{de ? "Wert einer Anfrage" : "Bir talebin değeri"}</label>
                <input name="ads_lead_value" defaultValue={s.google.leadValue} className={input} placeholder="150" />
                <p className={hint}>
                  {de
                    ? "Optional. Hilft Google beim Optimieren: Was ist eine Anfrage im Schnitt wert?"
                    : "İsteğe bağlı. Google'ın optimizasyonuna yardım eder: bir talep ortalama ne değerinde?"}
                </p>
              </div>
              <div>
                <label className={label}>{de ? "Währung" : "Para birimi"}</label>
                <input name="ads_currency" defaultValue={s.google.currency} className={input} placeholder="EUR" />
              </div>
            </div>
          </div>

          <div className="mt-8 border-t border-sand-deep pt-6">
            <div className="grid gap-7 md:grid-cols-2">
              <div>
                <label className={label}>Search Console</label>
                <input name="gsc" defaultValue={s.google.searchConsole} className={input} placeholder="google-site-verification=…" />
                <p className={hint}>
                  {de
                    ? "Nur der content-Wert des Metatags aus der Search Console („HTML-Tag“-Methode)."
                    : "Search Console'un verdiği meta etiketindeki content değeri („HTML etiketi“ yöntemi)."}
                </p>
              </div>
              <div>
                <label className={label}>Bing Webmaster Tools</label>
                <input name="bing" defaultValue={s.google.bing} className={input} placeholder="msvalidate.01" />
              </div>
            </div>

            <label className="mt-6 flex cursor-pointer items-start gap-3 text-[0.82rem] leading-relaxed text-ink">
              <input
                type="checkbox"
                name="consent_mode"
                defaultChecked={s.google.consentMode}
                className="mt-1 h-4 w-4 shrink-0 accent-[#B08D57]"
              />
              <span>
                {de ? "Google Consent Mode v2 verwenden" : "Google Consent Mode v2 kullan"}
                <span className="mt-1 block text-[0.74rem] text-muted">
                  {de
                    ? "Empfohlen und für Werbung in der EU vorausgesetzt: Vor der Einwilligung wird alles auf „abgelehnt“ gesetzt, danach auf die tatsächliche Auswahl aktualisiert."
                    : "Önerilir ve AB'de reklam için gereklidir: onaydan önce her şey „reddedildi“ olarak ayarlanır, onaydan sonra gerçek seçime güncellenir."}
                </span>
              </span>
            </label>
          </div>
        </section>

        {/* ------------------------------- Meta ------------------------------- */}
        <section className="border border-sand-deep p-6">
          <h3 className="font-display text-xl text-ink">Meta (Facebook &amp; Instagram)</h3>
          <div className="mt-6 max-w-md">
            <label className={label}>Pixel-ID</label>
            <input name="meta_pixel" defaultValue={s.meta.pixelId} className={input} placeholder="123456789012345" />
            <p className={hint}>
              {de
                ? "Wird nur nach Marketing-Einwilligung geladen."
                : "Yalnızca pazarlama onayından sonra yüklenir."}
            </p>
          </div>
        </section>

        <div className="flex flex-wrap items-center gap-6">
          <button className="bg-ink px-9 py-4 text-[0.68rem] uppercase tracking-[0.2em] text-cream transition-colors hover:bg-gold">
            {de ? "Speichern" : "Kaydet"}
          </button>
          {s.updatedAt && (
            <span className="text-[0.72rem] text-muted">
              {de ? "Zuletzt geändert" : "Son değişiklik"}: {new Date(s.updatedAt).toLocaleString(de ? "de-DE" : "tr-TR")}
            </span>
          )}
        </div>
      </form>

      <section className="border border-sand-deep p-6">
        <h3 className="font-display text-xl text-ink">{de ? "PayPal-Verbindung prüfen" : "PayPal bağlantısını kontrol et"}</h3>
        <p className={hint}>
          {de
            ? "Fragt bei PayPal ein Zugriffstoken an. Es wird keine Zahlung ausgelöst und kein Betrag bewegt."
            : "PayPal'dan yalnızca bir erişim anahtarı ister. Ödeme başlatmaz, para hareketi olmaz."}
        </p>
        <PaypalTest locale={l} />
      </section>

      {/* ---------------------------- Weitere Keys ---------------------------- */}
      <section className="space-y-6">
        <div>
          <h3 className="font-display text-xl text-ink">{de ? "Weitere Schlüssel" : "Diğer anahtarlar"}</h3>
          <p className="mt-2 max-w-3xl text-sm leading-relaxed text-muted">
            {de
              ? "Platz für alles, was später dazukommt – E-Mail-Versand, WhatsApp, Buchhaltung. Der technische Name ist der Schlüssel, unter dem der Code den Wert liest; er darf jederzeit hier stehen, bevor die Anbindung gebaut ist."
              : "Sonradan eklenecek her şey için yer – e-posta gönderimi, WhatsApp, muhasebe. Teknik ad, kodun değeri okuduğu anahtardır; entegrasyon yazılmadan önce de burada durabilir."}
          </p>
        </div>

        {s.extras.map((e) => (
          <form key={e.id} action={saveIntegrationKey.bind(null, e.id)} className="border border-sand-deep p-6">
            <div className="grid gap-7 md:grid-cols-[1fr_1fr_1.2fr]">
              <div>
                <label className={label}>{de ? "Anzeigename" : "Görünen ad"}</label>
                <input name={`label_${e.id}`} defaultValue={e.label} className={input} />
              </div>
              <div>
                <label className={label}>{de ? "Technischer Name" : "Teknik ad"}</label>
                <input readOnly value={e.name} className={`${input} font-mono text-muted`} />
              </div>
              <div>
                <label className={label}>{de ? "Wert" : "Değer"}</label>
                <input
                  name={`value_${e.id}`}
                  type={e.secret ? "password" : "text"}
                  autoComplete="new-password"
                  className={input}
                  placeholder={e.secret ? mask(e.value) : e.value}
                />
              </div>
            </div>
            <div className="mt-6 grid gap-7 md:grid-cols-[1fr_auto] md:items-end">
              <div>
                <label className={label}>{de ? "Notiz" : "Not"}</label>
                <input name={`note_${e.id}`} defaultValue={e.note} className={input} />
              </div>
              <div className="flex gap-3">
                <button className="border border-ink px-6 py-3 text-[0.66rem] uppercase tracking-[0.2em] text-ink transition-colors hover:bg-ink hover:text-cream">
                  {de ? "Speichern" : "Kaydet"}
                </button>
                <button
                  formAction={deleteIntegrationKey.bind(null, e.id)}
                  className="px-4 py-3 text-[0.66rem] uppercase tracking-[0.2em] text-muted hover:text-red-700"
                >
                  {de ? "Löschen" : "Sil"}
                </button>
              </div>
            </div>
          </form>
        ))}

        <form action={addIntegrationKey} className="border border-dashed border-sand-deep p-6">
          <div className="grid gap-7 md:grid-cols-[1fr_1fr_1.2fr]">
            <div>
              <label className={label}>{de ? "Anzeigename" : "Görünen ad"}</label>
              <input name="extra_label" className={input} placeholder="Brevo API-Key" />
            </div>
            <div>
              <label className={label}>{de ? "Technischer Name" : "Teknik ad"}</label>
              <input name="extra_name" required className={`${input} font-mono`} placeholder="BREVO_API_KEY" />
            </div>
            <div>
              <label className={label}>{de ? "Wert" : "Değer"}</label>
              <input name="extra_value" className={input} />
            </div>
          </div>
          <div className="mt-6 grid gap-7 md:grid-cols-[1fr_auto] md:items-end">
            <div>
              <label className={label}>{de ? "Notiz" : "Not"}</label>
              <input name="extra_note" className={input} placeholder={de ? "wofür ist der Schlüssel?" : "anahtar ne için?"} />
            </div>
            <div className="flex items-center gap-6">
              <label className="flex cursor-pointer items-center gap-3 text-[0.78rem] text-ink">
                <input type="checkbox" name="extra_secret" defaultChecked className="h-4 w-4 accent-[#B08D57]" />
                {de ? "Geheim" : "Gizli"}
              </label>
              <button className="bg-ink px-7 py-3 text-[0.66rem] uppercase tracking-[0.2em] text-cream transition-colors hover:bg-gold">
                {de ? "Hinzufügen" : "Ekle"}
              </button>
            </div>
          </div>
        </form>
      </section>
    </div>
  );
}
