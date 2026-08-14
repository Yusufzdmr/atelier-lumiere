import Link from "next/link";
import { listLeads, listGalleries, listSelections, listInvitations, listRsvps } from "@/lib/store";
import { isLocale, type Locale } from "@/lib/i18n";

export const dynamic = "force-dynamic";

export default async function AdminDashboard({ params }: { params: Promise<{ locale: string }> }) {
  const [leads, galleries, selections, invitations, rsvps] = await Promise.all([
    listLeads(),
    listGalleries(),
    listSelections(),
    listInvitations(),
    listRsvps(),
  ]);
  const { locale } = await params;
  const l: Locale = isLocale(locale) ? locale : "de";
  const de = l === "de";

  const cards = [
    { n: leads.length, de: "Anfragen", tr: "Talepler", href: "" },
    { n: galleries.length, de: "Galerien", tr: "Galeriler", href: "/galerien" },
    { n: selections.length, de: "Album-Auswahlen", tr: "Albüm seçimleri", href: "/galerien" },
    { n: invitations.length, de: "Einladungen", tr: "Davetiyeler", href: "/einladungen" },
    { n: rsvps.length, de: "Zusagen", tr: "Katılımlar", href: "/einladungen" },
  ];

  const box = "border border-sand-deep p-6";
  const th = "pb-2 text-left text-[0.6rem] uppercase tracking-[0.16em] text-muted";
  const td = "border-t border-sand-deep/60 py-3 pr-4 align-top text-[0.86rem] text-ink-soft";

  return (
    <div className="space-y-10">
      <div className="grid grid-cols-2 gap-4 sm:grid-cols-3 lg:grid-cols-5">
        {cards.map((c) => (
          <Link key={c.de} href={`/${l}/admin${c.href}`} className="border border-sand-deep p-5 transition-colors hover:border-gold">
            <div className="font-display text-4xl font-light text-ink">{c.n}</div>
            <div className="mt-2 text-[0.62rem] uppercase tracking-[0.16em] text-muted">{de ? c.de : c.tr}</div>
          </Link>
        ))}
      </div>

      <div className="grid gap-6 lg:grid-cols-2">
        <div className={box}>
          <h2 className="font-display text-xl text-ink">{de ? "Neueste Anfragen" : "Son talepler"}</h2>
          {leads.length === 0 ? (
            <p className="mt-4 text-sm text-muted">{de ? "Noch keine Einträge." : "Henüz kayıt yok."}</p>
          ) : (
            <table className="mt-4 w-full">
              <thead>
                <tr>
                  <th className={th}>Name</th>
                  <th className={th}>E-Mail</th>
                  <th className={th}>{de ? "Datum" : "Tarih"}</th>
                  <th className={th}>Location</th>
                </tr>
              </thead>
              <tbody>
                {leads.slice(0, 12).map((x, i) => (
                  <tr key={i}>
                    <td className={td}>{x.name}</td>
                    <td className={td}>
                      <a href={`mailto:${x.email}`} className="hover:text-gold">
                        {x.email}
                      </a>
                    </td>
                    <td className={td}>{x.date || "–"}</td>
                    <td className={td}>{x.location || "–"}</td>
                  </tr>
                ))}
              </tbody>
            </table>
          )}
        </div>

        <div className={box}>
          <h2 className="font-display text-xl text-ink">{de ? "Album-Auswahlen" : "Albüm seçimleri"}</h2>
          {selections.length === 0 ? (
            <p className="mt-4 text-sm text-muted">{de ? "Noch keine Einträge." : "Henüz kayıt yok."}</p>
          ) : (
            <ul className="mt-4 space-y-4">
              {selections.map((s, i) => (
                <li key={i} className="border-t border-sand-deep/60 pt-3">
                  <div className="flex items-baseline justify-between gap-4">
                    <Link href={`/${l}/admin/galerien/${s.code}`} className="text-[0.92rem] text-ink hover:text-gold">
                      {s.couple}
                    </Link>
                    <span className="font-display text-lg text-gold">{s.picks.length}</span>
                  </div>
                  <div className="mt-1 text-[0.72rem] text-muted">
                    {s.code} · {new Date(s.at).toLocaleString(de ? "de-DE" : "tr-TR")}
                  </div>
                  <div className="mt-2 text-[0.72rem] leading-relaxed text-muted">
                    {de ? "Bild-Nummern: " : "Kare no: "}
                    {s.picks.map((n) => n + 1).join(", ")}
                  </div>
                </li>
              ))}
            </ul>
          )}
        </div>

        <div className={`${box} lg:col-span-2`}>
          <h2 className="font-display text-xl text-ink">{de ? "Zusagen (RSVP)" : "Katılım bildirimleri"}</h2>
          {rsvps.length === 0 ? (
            <p className="mt-4 text-sm text-muted">{de ? "Noch keine Einträge." : "Henüz kayıt yok."}</p>
          ) : (
            <table className="mt-4 w-full">
              <thead>
                <tr>
                  <th className={th}>{de ? "Einladung" : "Davetiye"}</th>
                  <th className={th}>Name</th>
                  <th className={th}>Status</th>
                  <th className={th}>{de ? "Personen" : "Kişi"}</th>
                  <th className={th}>{de ? "Nachricht" : "Mesaj"}</th>
                </tr>
              </thead>
              <tbody>
                {rsvps.slice(0, 30).map((r, i) => (
                  <tr key={i}>
                    <td className={td}>{r.slug}</td>
                    <td className={td}>{r.name}</td>
                    <td className={td}>
                      <span className={r.coming ? "text-gold" : "text-muted"}>{r.coming ? "✓" : "✕"}</span>
                    </td>
                    <td className={td}>{r.count}</td>
                    <td className={td}>{r.note || "–"}</td>
                  </tr>
                ))}
              </tbody>
            </table>
          )}
        </div>
      </div>
    </div>
  );
}
