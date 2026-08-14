import Link from "next/link";
import { listInvitations, listRsvps } from "@/lib/store";
import { removeInvitation } from "@/lib/actions";
import { themeById } from "@/lib/themes";
import { isLocale, type Locale } from "@/lib/i18n";

export const dynamic = "force-dynamic";

export default async function AdminInvitations({ params }: { params: Promise<{ locale: string }> }) {
  const { locale } = await params;
  const l: Locale = isLocale(locale) ? locale : "de";
  const de = l === "de";
  const [invitations, allRsvps] = await Promise.all([listInvitations(), listRsvps()]);

  return (
    <div className="space-y-8">
      <p className="max-w-2xl text-sm leading-relaxed text-muted">
        {de
          ? "Alle erstellten Einladungen mit den Rückmeldungen der Gäste. Einladungen von Hochzeitspaaren sind kostenfrei, alle anderen kostenpflichtig."
          : "Oluşturulan tüm davetiyeler ve misafir yanıtları. Düğün çiftlerinin davetiyeleri ücretsiz, diğerleri ücretlidir."}
      </p>

      <div className="space-y-5">
        {invitations.map((inv) => {
          const rsvps = allRsvps.filter((r) => r.slug === inv.slug);
          const yes = rsvps.filter((r) => r.coming);
          const guests = yes.reduce((n, r) => n + r.count, 0);
          const th = themeById(inv.theme);

          return (
            <div key={inv.slug} className="border border-sand-deep p-6">
              <div className="flex flex-wrap items-start justify-between gap-4">
                <div className="flex items-center gap-4">
                  <span
                    className="flex h-12 w-12 items-center justify-center rounded-full text-[0.7rem]"
                    style={{ background: th.paper, color: th.accent, border: `1px solid ${th.paperEdge}` }}
                  >
                    {inv.bride.charAt(0)}
                    {inv.groom.charAt(0)}
                  </span>
                  <div>
                    <Link href={`/${inv.locale}/einladung/${inv.slug}`} className="font-display text-lg text-ink hover:text-gold">
                      {inv.bride} &amp; {inv.groom}
                    </Link>
                    <div className="mt-1 text-[0.74rem] text-muted">
                      /{inv.locale}/einladung/{inv.slug} · {inv.events?.[0]?.date ?? "—"} · {th.name}
                      {inv.photos.length > 0 && ` · ${inv.photos.length} ${de ? "Fotos" : "foto"}`}
                    </div>
                  </div>
                </div>

                <div className="flex items-center gap-6">
                  <div className="text-right">
                    <div className="font-display text-2xl text-gold">
                      {yes.length}
                      <span className="text-base text-muted">/{rsvps.length}</span>
                    </div>
                    <div className="text-[0.6rem] uppercase tracking-[0.14em] text-muted">
                      {guests} {de ? "Personen" : "kişi"}
                    </div>
                  </div>
                  <span className="text-[0.66rem] uppercase tracking-[0.14em] text-muted">
                    {inv.paid ? (de ? "Kunde · gratis" : "müşteri · ücretsiz") : `${inv.price ?? 79} €`}
                  </span>
                  <form action={removeInvitation}>
                    <input type="hidden" name="slug" value={inv.slug} />
                    <button className="text-[0.66rem] uppercase tracking-[0.14em] text-muted transition-colors hover:text-red-800">
                      {de ? "Löschen" : "Sil"}
                    </button>
                  </form>
                </div>
              </div>

              {rsvps.length > 0 && (
                <ul className="mt-5 space-y-2 border-t border-sand-deep/60 pt-4">
                  {rsvps.map((r, i) => (
                    <li key={i} className="flex flex-wrap items-baseline gap-3 text-[0.84rem]">
                      <span className={r.coming ? "text-gold" : "text-muted"}>{r.coming ? "✓" : "✕"}</span>
                      <span className="text-ink">{r.name}</span>
                      {r.coming && <span className="text-muted">· {r.count} {de ? "Pers." : "kişi"}</span>}
                      {r.note && <span className="text-muted">· {r.note}</span>}
                    </li>
                  ))}
                </ul>
              )}
            </div>
          );
        })}

        {invitations.length === 0 && (
          <p className="text-sm text-muted">{de ? "Noch keine Einladungen." : "Henüz davetiye yok."}</p>
        )}
      </div>
    </div>
  );
}
