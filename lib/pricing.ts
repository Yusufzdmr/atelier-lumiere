import type { InviteSections } from "./store";

/**
 * Preise der digitalen Einladung.
 * `regular` ist der reguläre Preis, `now` der aktuelle Einführungspreis –
 * die Differenz wird im Warenkorb als Ersparnis ausgewiesen.
 */
export type Price = { regular: number; now: number };

export const BASE: Price = { regular: 99, now: 79 };

/** Aufpreis, wenn zwei Feiern in einer Einladung liegen (z. B. Henna + Hochzeit). */
export const SECOND_EVENT: Price = { regular: 39, now: 20 };

export const SECTION_PRICES: Record<keyof InviteSections, Price> = {
  rsvp: { regular: 0, now: 0 },
  location: { regular: 0, now: 0 },
  countdown: { regular: 0, now: 0 },
  program: { regular: 0, now: 0 },
  family: { regular: 0, now: 0 },
  menu: { regular: 19, now: 0 },
  music: { regular: 29, now: 19 },
  video: { regular: 49, now: 29 },
};

export type PriceLine = { key: string; label: string; regular: number; now: number };

export function priceLines(
  sections: InviteSections,
  twoEvents: boolean,
  labels: Partial<Record<keyof InviteSections, string>>,
  baseLabel: string,
  secondLabel: string
): PriceLine[] {
  const lines: PriceLine[] = [{ key: "base", label: baseLabel, regular: BASE.regular, now: BASE.now }];

  if (twoEvents) {
    lines.push({ key: "second", label: secondLabel, regular: SECOND_EVENT.regular, now: SECOND_EVENT.now });
  }

  for (const key of Object.keys(SECTION_PRICES) as (keyof InviteSections)[]) {
    const p = SECTION_PRICES[key];
    if (!sections[key] || p.regular === 0) continue;
    lines.push({ key, label: labels[key] ?? key, regular: p.regular, now: p.now });
  }

  return lines;
}

export function totals(lines: PriceLine[]) {
  const now = lines.reduce((n, l) => n + l.now, 0);
  const regular = lines.reduce((n, l) => n + l.regular, 0);
  return { now, regular, saved: regular - now };
}

export const euro = (n: number) => (n === 0 ? "0 €" : `${n} €`);

/** Serverseitige Berechnung – der Client-Preis wird nie ungeprüft übernommen. */
export function computeTotal(sections: InviteSections, twoEvents: boolean, free: boolean) {
  if (free) return 0;
  let sum = BASE.now + (twoEvents ? SECOND_EVENT.now : 0);
  for (const key of Object.keys(SECTION_PRICES) as (keyof InviteSections)[]) {
    if (sections[key]) sum += SECTION_PRICES[key].now;
  }
  return sum;
}
