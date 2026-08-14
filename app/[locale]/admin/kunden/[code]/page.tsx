import Link from "next/link";
import { notFound } from "next/navigation";

import GalleryUploader from "@/components/admin/GalleryUploader";
import { getCustomer, getGallery, getSelection, listInvitations, listRsvps } from "@/lib/store";
import {
  editCustomer,
  saveCustomerCoupon,
  resetCustomerCoupon,
  setCustomerStatus,
  removeCustomer,
  deleteGalleryPhoto,
  uploadGalleryPhotos,
} from "@/lib/actions";
import { img } from "@/lib/images";
import { isLocale, type Locale } from "@/lib/i18n";

export const dynamic = "force-dynamic";

const input =
  "w-full border-b border-sand-deep bg-transparent px-0 py-2.5 text-[0.92rem] text-ink outline-none focus:border-gold";
const label = "block text-[0.6rem] uppercase tracking-[0.18em] text-muted";
const box = "border border-sand-deep p-6";

export default async function AdminCustomer({ params }: { params: Promise<{ locale: string; code: string }> }) {
  const { locale, code } = await params;
  const l: Locale = isLocale(locale) ? locale : "de";
  const de = l === "de";

  const customer = await getCustomer(code);
  if (!customer) notFound();

  const [gallery, selection, invitations, rsvps] = await Promise.all([
    getGallery(customer.code),
    getSelection(customer.code),
    listInvitations(),
    listRsvps(),
  ]);

  const photos = [
    ...(gallery?.uploads ?? []).map((src) => ({ src, upload: true })),
    ...(gallery?.seeds ?? []).map((s) => ({ src: img(s, 500, 650), upload: false })),
  ];

  // Einladungen, die mit dem Gutschein dieses Kunden entstanden sind
  const usedSlugs = customer.coupon.usedFor.map((u) => u.slug);
  const invites = invitations.filter((i) => usedSlugs.includes(i.slug));

  const archive = setCustomerStatus.bind(null, "archived");
  const activate = setCustomerStatus.bind(null, "active");

  return (
    <div className="space-y-10">
      <div className="flex flex-wrap items-baseline justify-between gap-4">
        <div>
          <Link href={`/${l}/admin/kunden`} className="text-[0.68rem] uppercase tracking-[0.16em] text-muted hover:text-gold">
            ← {de ? "Alle Kunden" : "Tüm müşteriler"}
          </Link>
          <h2 className="font-display mt-2 text-2xl text-ink">{customer.couple}</h2>
          <div className="mt-1 text-[0.76rem] text-muted">
            {de ? "Anmeldung" : "Giriş"}: <strong className="text-ink">{customer.code}</strong> ·{" "}
            {de ? "Passwort" : "Parola"}: <strong className="text-ink">{customer.password}</strong>
          </div>
        </div>
        <div className="flex flex-wrap gap-3">
          <Link
            href={`/${l}/galerie/${customer.code}`}
            className="border border-ink px-5 py-2.5 text-[0.66rem] uppercase tracking-[0.18em] text-ink transition-colors hover:bg-ink hover:text-cream"
          >
            {de ? "Galerie ansehen" : "Galeriyi gör"} ↗
          </Link>
          <form action={customer.status === "archived" ? activate : archive}>
            <input type="hidden" name="code" value={customer.code} />
            <button className="border border-sand-deep px-5 py-2.5 text-[0.66rem] uppercase tracking-[0.18em] text-muted transition-colors hover:border-gold hover:text-gold">
              {customer.status === "archived"
                ? de
                  ? "Wieder aktivieren"
                  : "Yeniden aktif et"
                : de
                  ? "Archivieren"
                  : "Arşivle"}
            </button>
          </form>
        </div>
      </div>

      {customer.status === "archived" && (
        <p className="border border-sand-deep bg-sand/40 px-4 py-3 text-sm text-muted">
          {de
            ? "Dieser Kunde ist archiviert: Der Gutschein greift nicht mehr. Die Galerie und die Bilder bleiben erhalten."
            : "Bu müşteri arşivde: kuponu artık çalışmaz. Galerisi ve fotoğrafları duruyor."}
        </p>
      )}

      <div className="grid gap-8 lg:grid-cols-[1fr_360px]">
        {/* ---------------- Bilder ---------------- */}
        <div className="space-y-6">
          <GalleryUploader code={customer.code} locale={l} upload={uploadGalleryPhotos} />

          {selection && (
            <div className="border border-gold/50 bg-sand/30 p-5">
              <div className="text-[0.62rem] uppercase tracking-[0.18em] text-gold">
                {de ? "Auswahl des Paares" : "Çiftin seçimi"}
              </div>
              <p className="mt-2 text-[0.9rem] text-ink">
                {selection.picks.length} {de ? "Bilder ausgewählt" : "kare seçildi"} ·{" "}
                {new Date(selection.at).toLocaleDateString(de ? "de-DE" : "tr-TR")}
              </p>
              <p className="mt-2 break-all text-[0.78rem] text-muted">
                {de ? "Bildnummern" : "Kare numaraları"}: {selection.picks.map((p) => p + 1).join(", ") || "—"}
              </p>
              {selection.note && (
                <p className="mt-3 border-t border-sand-deep pt-3 text-[0.85rem] italic leading-relaxed text-ink">
                  &bdquo;{selection.note}&ldquo;
                </p>
              )}
            </div>
          )}

          <div className="grid grid-cols-3 gap-3 sm:grid-cols-4 lg:grid-cols-5">
            {photos.map((p, i) => {
              const picked = selection?.picks.includes(i);
              return (
                <div key={i} className="group relative aspect-[3/4] overflow-hidden border border-sand-deep">
                  {/* eslint-disable-next-line @next/next/no-img-element -- gemischte Quellen (Upload/Platzhalter) */}
                  <img src={p.src} alt="" className="h-full w-full object-cover" loading="lazy" />
                  <span className="absolute left-1.5 top-1.5 bg-ink/70 px-1.5 py-0.5 text-[0.55rem] text-cream">{i + 1}</span>
                  {picked && <span className="absolute right-1.5 top-1.5 bg-gold px-1.5 py-0.5 text-[0.55rem] text-white">♥</span>}
                  {p.upload && (
                    <form action={deleteGalleryPhoto} className="absolute inset-x-0 bottom-0">
                      <input type="hidden" name="code" value={customer.code} />
                      <input type="hidden" name="index" value={i} />
                      <button className="w-full bg-ink/80 py-1.5 text-[0.55rem] uppercase tracking-[0.14em] text-cream opacity-0 transition-opacity group-hover:opacity-100">
                        {de ? "Löschen" : "Sil"}
                      </button>
                    </form>
                  )}
                </div>
              );
            })}
            {photos.length === 0 && (
              <p className="col-span-full text-sm text-muted">
                {de ? "Noch keine Bilder hochgeladen." : "Henüz fotoğraf yüklenmedi."}
              </p>
            )}
          </div>
        </div>

        {/* ---------------- Seitenspalte ---------------- */}
        <div className="space-y-6">
          <form action={editCustomer} className={box}>
            <input type="hidden" name="code" value={customer.code} />
            <h3 className="font-display text-lg text-ink">{de ? "Kundendaten" : "Müşteri bilgileri"}</h3>

            <div className="mt-5 space-y-5">
              <div>
                <label className={label}>{de ? "Paar / Name" : "Çift / ad"}</label>
                <input name="couple" defaultValue={customer.couple} className={input} />
              </div>
              <div>
                <label className={label}>{de ? "Neues Passwort" : "Yeni parola"}</label>
                <input name="password" className={input} placeholder={de ? "leer = unverändert" : "boş = değişmez"} />
              </div>
              <div className="grid grid-cols-2 gap-4">
                <div>
                  <label className={label}>E-Mail</label>
                  <input name="email" defaultValue={customer.email} className={input} />
                </div>
                <div>
                  <label className={label}>Telefon</label>
                  <input name="phone" defaultValue={customer.phone} className={input} />
                </div>
              </div>
              <div className="grid grid-cols-2 gap-4">
                <div>
                  <label className={label}>{de ? "Hochzeit" : "Düğün"}</label>
                  <input type="date" name="date" defaultValue={customer.date} className={input} />
                </div>
                <div>
                  <label className={label}>{de ? "Galerie bis" : "Galeri bitişi"}</label>
                  <input type="date" name="expires" defaultValue={gallery?.expires ?? ""} className={input} />
                </div>
              </div>
              <div>
                <label className={label}>Location</label>
                <input name="venue" defaultValue={customer.venue} className={input} />
              </div>
              <div className="grid grid-cols-2 gap-4">
                <div>
                  <label className={label}>{de ? "Paket" : "Paket"}</label>
                  <input name="packageName" defaultValue={customer.packageName} className={input} />
                </div>
                <div>
                  <label className={label}>{de ? "Betrag" : "Tutar"}</label>
                  <input name="amount" defaultValue={customer.amount} className={input} />
                </div>
              </div>
              <div>
                <label className={label}>{de ? "Hochzeitsfilm (YouTube / Vimeo)" : "Düğün filmi (YouTube / Vimeo)"}</label>
                <input name="video" defaultValue={gallery?.videoUrl ?? ""} className={input} placeholder="https://" />
              </div>
              <div>
                <label className={label}>{de ? "Interne Notiz" : "İç not"}</label>
                <textarea name="notes" rows={3} defaultValue={customer.notes} className={`${input} resize-none`} />
              </div>
            </div>

            <button className="mt-7 w-full bg-ink px-7 py-3.5 text-[0.68rem] uppercase tracking-[0.2em] text-cream transition-colors hover:bg-gold">
              {de ? "Speichern" : "Kaydet"}
            </button>
          </form>

          {/* ---------------- Gutschein ---------------- */}
          <form action={saveCustomerCoupon} className={box}>
            <input type="hidden" name="code" value={customer.code} />
            <h3 className="font-display text-lg text-ink">{de ? "Gutschein Einladung" : "Davetiye kuponu"}</h3>
            <p className="mt-2 text-[0.76rem] leading-relaxed text-muted">
              {de
                ? "Mit diesem Code erstellt das Paar seine digitale Einladung kostenlos."
                : "Bu kodla çift dijital davetiyesini ücretsiz oluşturur."}
            </p>

            <div className="mt-5">
              <label className={label}>Code</label>
              <input name="coupon" defaultValue={customer.coupon.code} className={`${input} font-mono`} />
            </div>

            <label className="mt-4 flex cursor-pointer items-center gap-3 text-[0.78rem] text-ink">
              <input
                type="checkbox"
                name="couponActive"
                defaultChecked={customer.coupon.active}
                className="h-4 w-4 accent-[#B08D57]"
              />
              {de ? "Aktiv" : "Aktif"}
            </label>
            <label className="mt-2 flex cursor-pointer items-center gap-3 text-[0.78rem] text-ink">
              <input
                type="checkbox"
                name="couponOnce"
                defaultChecked={customer.coupon.once}
                className="h-4 w-4 accent-[#B08D57]"
              />
              {de ? "Nur einmal einlösbar" : "Tek kullanımlık"}
            </label>

            <div className="mt-4">
              <label className={label}>{de ? "Gültig bis" : "Son geçerlilik"}</label>
              <input type="date" name="couponExpires" defaultValue={customer.coupon.expires} className={input} />
            </div>

            {customer.coupon.usedFor.length > 0 && (
              <div className="mt-5 border-t border-sand-deep pt-4">
                <div className="text-[0.62rem] uppercase tracking-[0.18em] text-muted">
                  {de ? "Eingelöst" : "Kullanıldı"}
                </div>
                <ul className="mt-2 space-y-1.5">
                  {customer.coupon.usedFor.map((u) => {
                    const invite = invites.find((i) => i.slug === u.slug);
                    const count = rsvps.filter((r) => r.slug === u.slug).length;
                    return (
                      <li key={u.slug} className="text-[0.78rem]">
                        <Link href={`/${l}/einladung/${u.slug}`} className="text-gold underline-offset-4 hover:underline">
                          /{u.slug}
                        </Link>
                        <span className="ml-2 text-muted">
                          {new Date(u.at).toLocaleDateString(de ? "de-DE" : "tr-TR")}
                          {invite ? ` · ${invite.bride} & ${invite.groom}` : ""}
                          {count > 0 ? ` · ${count} RSVP` : ""}
                        </span>
                      </li>
                    );
                  })}
                </ul>
              </div>
            )}

            <div className="mt-6 flex flex-wrap gap-3">
              <button className="bg-ink px-6 py-3 text-[0.66rem] uppercase tracking-[0.2em] text-cream transition-colors hover:bg-gold">
                {de ? "Speichern" : "Kaydet"}
              </button>
              <button
                name="regenerate"
                value="1"
                className="border border-sand-deep px-5 py-3 text-[0.66rem] uppercase tracking-[0.2em] text-muted transition-colors hover:border-gold hover:text-gold"
              >
                {de ? "Neuer Code" : "Yeni kod"}
              </button>
              {customer.coupon.usedFor.length > 0 && (
                <button
                  formAction={resetCustomerCoupon}
                  className="px-3 py-3 text-[0.66rem] uppercase tracking-[0.2em] text-muted underline-offset-4 hover:text-gold hover:underline"
                >
                  {de ? "Wieder freigeben" : "Yeniden aç"}
                </button>
              )}
            </div>
          </form>

          {/* ---------------- Löschen ---------------- */}
          <form action={removeCustomer} className="border border-red-700/30 p-6">
            <input type="hidden" name="code" value={customer.code} />
            <input type="hidden" name="locale" value={l} />
            <h3 className="font-display text-lg text-ink">{de ? "Endgültig löschen" : "Kalıcı olarak sil"}</h3>
            <p className="mt-2 text-[0.76rem] leading-relaxed text-muted">
              {de
                ? "Löscht Kundenakte, Galerie, Auswahl und alle hochgeladenen Bilder. Nicht rückgängig zu machen – zum Bestätigen den Anmeldenamen eintippen."
                : "Müşteri kaydını, galeriyi, seçimi ve yüklenen tüm fotoğrafları siler. Geri alınamaz – onaylamak için giriş adını yazın."}
            </p>
            <input name="confirm" className={`${input} mt-4`} placeholder={customer.code} />
            <button className="mt-5 w-full border border-red-700/50 px-7 py-3 text-[0.66rem] uppercase tracking-[0.2em] text-red-700 transition-colors hover:bg-red-700 hover:text-white">
              {de ? "Unwiderruflich löschen" : "Geri dönüşsüz sil"}
            </button>
          </form>
        </div>
      </div>
    </div>
  );
}
