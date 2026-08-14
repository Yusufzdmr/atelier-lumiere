import type { InviteSections, ProgramItem } from "./store";

/** Standard-Abschnitte einer neuen Einladung (client- und serverseitig nutzbar). */
export const defaultSections = (): InviteSections => ({
  countdown: true,
  program: true,
  location: true,
  menu: false,
  family: false,
  music: false,
  video: false,
  rsvp: true,
});

export type InvitePayload = {
  slug: string;
  bride: string;
  groom: string;
  date: string;
  time: string;
  venue: string;
  address: string;
  message: string;
  photos: string[];
  program: ProgramItem[];
  hashtag?: string;
  theme: string;
  locale: "de" | "tr";
};

export function slugify(input: string) {
  return input
    .toLowerCase()
    .replace(/ä/g, "ae")
    .replace(/ö/g, "oe")
    .replace(/ü/g, "ue")
    .replace(/ß/g, "ss")
    .replace(/ı/g, "i")
    .replace(/ş/g, "s")
    .replace(/ğ/g, "g")
    .replace(/ç/g, "c")
    .replace(/[^a-z0-9]+/g, "-")
    .replace(/^-+|-+$/g, "")
    .slice(0, 60);
}

export function mapsUrl(address: string) {
  return `https://www.google.com/maps/search/?api=1&query=${encodeURIComponent(address)}`;
}

export function formatDate(date: string, locale: "de" | "tr") {
  const d = new Date(`${date}T12:00:00`);
  if (Number.isNaN(d.getTime())) return date;
  return d.toLocaleDateString(locale === "de" ? "de-DE" : "tr-TR", {
    weekday: "long",
    day: "numeric",
    month: "long",
    year: "numeric",
  });
}

/** Für die grosse Datumszeile auf der Karte: Wochentag · Tag · Monat · Jahr */
export function dateBlocks(date: string, locale: "de" | "tr") {
  const d = new Date(`${date}T12:00:00`);
  if (Number.isNaN(d.getTime())) return { weekday: "", day: date, month: "", year: "" };
  const loc = locale === "de" ? "de-DE" : "tr-TR";
  return {
    weekday: d.toLocaleDateString(loc, { weekday: "long" }),
    day: String(d.getDate()).padStart(2, "0"),
    month: d.toLocaleDateString(loc, { month: "long" }),
    year: String(d.getFullYear()),
  };
}

/**
 * Bild im Browser verkleinern, bevor es gespeichert wird.
 * Hält die Einladung auch auf dem Handy schnell und spart Speicher.
 */
export function resizeImage(file: File, maxSize = 1400, quality = 0.82): Promise<string> {
  return new Promise((resolve, reject) => {
    const reader = new FileReader();
    reader.onerror = () => reject(new Error("read-error"));
    reader.onload = () => {
      const image = new window.Image();
      image.onerror = () => reject(new Error("decode-error"));
      image.onload = () => {
        const scale = Math.min(1, maxSize / Math.max(image.width, image.height));
        const w = Math.round(image.width * scale);
        const h = Math.round(image.height * scale);
        const canvas = document.createElement("canvas");
        canvas.width = w;
        canvas.height = h;
        const ctx = canvas.getContext("2d");
        if (!ctx) return reject(new Error("canvas-error"));
        ctx.drawImage(image, 0, 0, w, h);
        resolve(canvas.toDataURL("image/jpeg", quality));
      };
      image.src = String(reader.result);
    };
    reader.readAsDataURL(file);
  });
}
