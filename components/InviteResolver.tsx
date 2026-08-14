"use client";

import { useEffect, useState } from "react";
import Link from "next/link";
import InviteCard, { type InviteView } from "./InviteCard";
import { getDict } from "@/lib/dict";
import type { Locale } from "@/lib/i18n";

/**
 * Auflösung der Einladung in zwei Stufen:
 * 1. Server-Store (in der Live-Version: Datenbank)
 * 2. lokale Kopie im Browser des Erstellers – so bleibt die Demo auch nach
 *    einem Neustart der serverlosen Instanz benutzbar.
 */
export default function InviteResolver({
  slug,
  locale,
  origin,
  initial,
}: {
  slug: string;
  locale: Locale;
  origin: string;
  initial: InviteView | null;
}) {
  const [invite, setInvite] = useState<InviteView | null>(initial);
  const [checked, setChecked] = useState(!!initial);

  useEffect(() => {
    if (initial) return;
    let local: InviteView | null = null;
    try {
      const raw = localStorage.getItem(`al-invite-${slug}`);
      if (raw) local = JSON.parse(raw) as InviteView;
    } catch {}
    // eslint-disable-next-line react-hooks/set-state-in-effect -- Browser-Speicher steht erst nach dem Mount zur Verfügung
    setInvite(local);
    setChecked(true);
  }, [initial, slug]);

  if (!checked) return <div data-standalone="invite" className="min-h-screen bg-cream" />;

  if (!invite) {
    const t = getDict(locale).invite;
    return (
      <div
        data-standalone="invite"
        className="flex min-h-screen flex-col items-center justify-center gap-6 bg-cream px-6 text-center"
      >
        <h1 className="headline text-3xl">{t.notFound}</h1>
        <Link
          href={`/${locale}/einladung`}
          className="border border-ink px-7 py-3 text-[0.7rem] uppercase tracking-[0.2em] text-ink hover:bg-ink hover:text-cream"
        >
          {t.title}
        </Link>
      </div>
    );
  }

  return <InviteCard invite={invite} locale={locale} origin={origin} />;
}
