import Link from "next/link";

import { listCustomers, listSelections, listGalleries } from "@/lib/store";
import { newCustomer, saveCampaign } from "@/lib/actions";
import { getCampaign } from "@/lib/cms";
import { isLocale, type Locale } from "@/lib/i18n";

export const dynamic = "force-dynamic";

const input =
  "w-full border-b border-sand-deep bg-transparent px-0 py-2.5 text-[0.92rem] text-ink outline-none focus:border-gold";
const label = "block text-[0.6rem] uppercase tracking-[0.18em] text-muted";

export default async function AdminCustomers({
  params,
  searchParams,
}: {
  params: Promise<{ locale: string }>;
  searchParams: Promise<{ fehler?: string }>;
}) {
  const { locale } = await params;
  const { fehler } = await searchParams;
  const l: Locale = isLocale(locale) ? locale : "de";
  const de = l === "de";

  const [customers, selections, galleries, campaign] = await Promise.all([
    listCustomers(),
    listSelections(),
    listGalleries(),
    getCampaign(),
  ]);

  const active = customers.filter((c) => c.status === "active");
  const archived = customers.filter((c) => c.status === "archived");

  const row = (c: (typeof customers)[number]) => {
    const gallery = galleries.find((g) => g.code === c.code);
    const selection = selections.find((s) => s.code === c.code);
    const photos = (gallery?.uploads.length ?? 0) + (gallery?.seeds.length ?? 0);
    const used = c.coupon.usedFor.length > 0;

    return (
      <Link
        key={c.code}
        href={`/${l}/admin/kunden/${c.code}`}
        className="block border border-sand-deep p-5 transition-colors hover:border-gold"
      >
        <div className="flex flex-wrap items-baseline justify-between gap-3">
          <span className="font-display text-lg text-ink">{c.couple}</span>
          <span className="text-[0.68rem] uppercase tracking-[0.16em] text-muted">
            {photos} {de ? "Bilder" : "kare"}
            {selection && (
              <span className="ml-3 text-gold">
                {selection.picks.length} {de ? "ausgewählt" : "seçildi"}
              </span>
            )}
          </span>
        </div>
        <div className="mt-1.5 text-[0.76rem] text-muted">
          {[c.venue, c.date, c.packageName].filter(Boolean).join(" · ") || (de ? "ohne Angaben" : "bilgi yok")}
        </div>
        <div className="mt-3 flex flex-wrap items-center gap-2 text-[0.62rem] uppercase tracking-[0.14em]">
          <span className="border border-sand-deep px-2 py-1 text-muted">
            {de ? "Login" : "Giriş"}: {c.code}
          </span>
          <span className={`border px-2 py-1 ${used ? "border-sand-deep text-muted" : "border-gold text-gold"}`}>
            {de ? "Gutschein" : "Kupon"}: {used ? (de ? "eingelöst" : "kullanıldı") : c.coupon.active ? (de ? "offen" : "açık") : (de ? "gesperrt" : "kapalı")}
          </span>
          {selection && (
            <span className="border border-gold px-2 py-1 text-gold">{de ? "Auswahl liegt vor" : "Seçim geldi"}</span>
          )}
        </div>
      </Link>
    );
  };

  return (
    <div className="grid gap-12 lg:grid-cols-[1.2fr_0.8fr]">
      <div>
        <h2 className="font-display text-xl text-ink">{de ? "Kunden" : "Müşteriler"}</h2>
        <p className="mt-2 max-w-xl text-sm leading-relaxed text-muted">
          {de
            ? "Ein Kunde ist ein Auftrag: Zugang zur Galerie, Gutschein für die digitale Einladung und die Auftragsdaten in einem Datensatz. Beim Anlegen entsteht die Galerie automatisch mit."
            : "Bir müşteri = bir iş: galeri girişi, dijital davetiye kuponu ve iş bilgileri tek kayıtta. Kaydı açtığınızda galerisi de otomatik oluşur."}
        </p>

        {fehler === "code" && (
          <p className="mt-4 border border-red-700/40 bg-red-50 px-4 py-3 text-sm text-red-700">
            {de
              ? "Diesen Anmeldenamen gibt es schon. Bitte einen anderen wählen."
              : "Bu giriş adı zaten var. Lütfen başka bir ad seçin."}
          </p>
        )}

        <div className="mt-7 space-y-4">
          {active.map(row)}
          {active.length === 0 && (
            <p className="text-sm text-muted">{de ? "Noch kein Kunde angelegt." : "Henüz müşteri yok."}</p>
          )}
        </div>

        {archived.length > 0 && (
          <div className="mt-10">
            <h3 className="text-[0.66rem] uppercase tracking-[0.18em] text-muted">
              {de ? "Archiv" : "Arşiv"} ({archived.length})
            </h3>
            <div className="mt-4 space-y-4 opacity-60">{archived.map(row)}</div>
          </div>
        )}
      </div>

      <div className="h-fit space-y-8">
        <form action={newCustomer} className="border border-sand-deep p-6">
          <input type="hidden" name="locale" value={l} />
          <h2 className="font-display text-xl text-ink">{de ? "Neuer Kunde" : "Yeni müşteri"}</h2>

          <div className="mt-6 space-y-6">
            <div>
              <label className={label}>{de ? "Paar / Name" : "Çift / ad"}</label>
              <input name="couple" required className={input} placeholder="Elif & Marco" />
            </div>
            <div>
              <label className={label}>{de ? "Anmeldename" : "Giriş adı"}</label>
              <input name="code" className={input} placeholder={de ? "leer = aus dem Namen" : "boş = addan üretilir"} />
            </div>
            <div>
              <label className={label}>{de ? "Passwort" : "Parola"}</label>
              <input name="password" className={input} placeholder={de ? "leer = automatisch" : "boş = otomatik"} />
            </div>
            <div className="grid grid-cols-2 gap-4">
              <div>
                <label className={label}>{de ? "Hochzeit" : "Düğün"}</label>
                <input type="date" name="date" className={input} />
              </div>
              <div>
                <label className={label}>{de ? "Galerie bis" : "Galeri bitişi"}</label>
                <input type="date" name="expires" className={input} />
              </div>
            </div>
            <div>
              <label className={label}>Location</label>
              <input name="venue" className={input} placeholder="Schloss Solitude, Stuttgart" />
            </div>
            <div className="grid grid-cols-2 gap-4">
              <div>
                <label className={label}>{de ? "Paket" : "Paket"}</label>
                <input name="packageName" className={input} placeholder={de ? "Ganztagesreportage" : "Tam gün"} />
              </div>
              <div>
                <label className={label}>{de ? "Betrag" : "Tutar"}</label>
                <input name="amount" className={input} placeholder="1.890 €" />
              </div>
            </div>
            <div className="grid grid-cols-2 gap-4">
              <div>
                <label className={label}>E-Mail</label>
                <input name="email" type="email" className={input} />
              </div>
              <div>
                <label className={label}>{de ? "Telefon" : "Telefon"}</label>
                <input name="phone" className={input} />
              </div>
            </div>
            <div>
              <label className={label}>{de ? "Interne Notiz" : "İç not"}</label>
              <textarea name="notes" rows={2} className={`${input} resize-none`} />
            </div>

            <div className="border-t border-sand-deep pt-5">
              <label className={label}>{de ? "Gutscheincode Einladung" : "Davetiye kupon kodu"}</label>
              <input name="coupon" className={input} placeholder={de ? "leer = automatisch erzeugen" : "boş = otomatik üret"} />
              <label className="mt-4 flex cursor-pointer items-center gap-3 text-[0.78rem] text-ink">
                <input type="checkbox" name="couponOnce" defaultChecked className="h-4 w-4 accent-[#B08D57]" />
                {de ? "Nur einmal einlösbar" : "Tek kullanımlık"}
              </label>
              <div className="mt-4">
                <label className={label}>{de ? "Gültig bis (optional)" : "Son geçerlilik (isteğe bağlı)"}</label>
                <input type="date" name="couponExpires" className={input} />
              </div>
            </div>
          </div>

          <button className="mt-8 w-full bg-ink px-7 py-3.5 text-[0.68rem] uppercase tracking-[0.2em] text-cream transition-colors hover:bg-gold">
            {de ? "Kunde anlegen" : "Müşteriyi oluştur"}
          </button>
        </form>

        <form action={saveCampaign} className="border border-sand-deep p-6">
          <h2 className="font-display text-xl text-ink">{de ? "Aktionscode" : "Kampanya kodu"}</h2>
          <p className="mt-2 text-[0.78rem] leading-relaxed text-muted">
            {de
              ? "Gilt zusätzlich zu den Kundencodes – etwa für eine Messe oder eine Aktion. Wer ihn kennt, erstellt die Einladung kostenlos."
              : "Müşteri kodlarına ek olarak geçerlidir – fuar ya da kampanya için. Kodu bilen davetiyeyi ücretsiz oluşturur."}
          </p>
          <div className="mt-5">
            <label className={label}>Code</label>
            <input name="campaignCode" defaultValue={campaign.code} className={input} />
          </div>
          <label className="mt-4 flex cursor-pointer items-center gap-3 text-[0.78rem] text-ink">
            <input
              type="checkbox"
              name="campaignActive"
              defaultChecked={campaign.active}
              className="h-4 w-4 accent-[#B08D57]"
            />
            {de ? "Aktiv" : "Aktif"}
          </label>
          <button className="mt-6 w-full border border-ink px-7 py-3 text-[0.66rem] uppercase tracking-[0.2em] text-ink transition-colors hover:bg-ink hover:text-cream">
            {de ? "Speichern" : "Kaydet"}
          </button>
        </form>
      </div>
    </div>
  );
}
