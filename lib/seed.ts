import type { Gallery, Invitation, Rsvp } from "./store";

/**
 * Demo-Datenbestand.
 *
 * Wird nur beim allerersten Start in eine leere Datenbank geschrieben
 * (siehe `lib/store.ts` -> `seedIfEmpty`). Danach gilt ausschliesslich, was
 * im Admin gepflegt wurde – ein Neustart setzt nichts mehr zurueck.
 */

export const seedGalleries = (): Gallery[] => [
  {
    code: "elif-marco",
    password: "solitude24",
    couple: "Elif & Marco",
    date: "2025-06-14",
    venue: "Schloss Solitude, Stuttgart",
    cover: "gal-elif-cover",
    seeds: Array.from({ length: 24 }, (_, i) => `gal-elif-${i + 1}`),
    uploads: [],
    expires: "2027-06-14",
  },
  {
    code: "sarah-daniel",
    password: "kelter25",
    couple: "Sarah & Daniel",
    date: "2025-09-06",
    venue: "Alte Kelter, Fellbach",
    cover: "gal-sarah-cover",
    seeds: Array.from({ length: 18 }, (_, i) => `gal-sarah-${i + 1}`),
    uploads: [],
    expires: "2027-09-06",
  },
];

export const seedInvitations = (): Invitation[] => [
  {
    slug: "ayse-mehmet",
    bride: "Ayşe",
    groom: "Mehmet",
    eventType: "multi",
    events: [
      { name: "Kına Gecesi", date: "2027-07-16", time: "19:00", venue: "La Vie Event", address: "Hauptstraße 12, 70734 Fellbach" },
      { name: "Düğün", date: "2027-07-17", time: "16:00", venue: "Alte Kelter Fellbach", address: "Kelterweg 1, 70734 Fellbach" },
    ],
    message:
      "Wir möchten diesen besonderen Tag mit euch feiern.\nBu özel günü sizinle birlikte kutlamak istiyoruz.",
    closing: "Wir freuen uns auf euch. / Sizleri aramızda görmekten mutluluk duyarız.",
    families: { bride: "Familie Yıldız", groom: "Familie Demir" },
    photos: [],
    program: [
      { time: "16:00", title: "Empfang / Karşılama" },
      { time: "17:00", title: "Trauung / Nikah" },
      { time: "19:00", title: "Dinner / Yemek" },
      { time: "21:00", title: "Party / Eğlence" },
    ],
    menu: ["Mezze & Vorspeisen", "Linsensuppe", "Lammkarree / Hähnchen", "Baklava & Obst"],
    sections: { countdown: true, program: true, location: true, menu: true, family: true, music: false, video: false, rsvp: true },
    hashtag: "#AyseVeMehmet2027",
    theme: "elysee",
    locale: "de",
    paid: true,
    price: 0,
    createdAt: "2026-01-12T10:00:00.000Z",
  },
  {
    slug: "lena-jonas",
    bride: "Lena",
    groom: "Jonas",
    eventType: "wedding",
    events: [
      { name: "Hochzeit", date: "2027-05-29", time: "14:30", venue: "Residenzschloss Ludwigsburg", address: "Schlossstraße 30, 71634 Ludwigsburg" },
    ],
    message: "Wir sagen Ja – und würden uns freuen, wenn ihr dabei seid.",
    photos: [],
    program: [
      { time: "14:30", title: "Freie Trauung" },
      { time: "16:00", title: "Sektempfang" },
      { time: "19:00", title: "Dinner" },
    ],
    menu: [],
    sections: { countdown: true, program: true, location: true, menu: false, family: false, music: false, video: false, rsvp: true },
    hashtag: "#LenaUndJonas",
    theme: "sage",
    locale: "de",
    paid: true,
    price: 0,
    createdAt: "2026-02-02T10:00:00.000Z",
  },
];

export const seedRsvps = (): Rsvp[] => [
  { slug: "ayse-mehmet", name: "Familie Yılmaz", coming: true, count: 4, at: "2026-03-01T12:00:00.000Z" },
  {
    slug: "ayse-mehmet",
    name: "Sandra & Tim",
    coming: false,
    count: 0,
    note: "Wir sind im Urlaub – alles Gute!",
    at: "2026-03-03T09:20:00.000Z",
  },
];
