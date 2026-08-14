/**
 * Gutscheincodes fuer die digitale Einladung.
 *
 * Der Code wird vorgelesen und abgetippt – deshalb keine Zeichen, die man
 * verwechseln kann (0/O, 1/I/l). Aufbau: LUM-ELIF-4K27, also Praefix,
 * Kundenname und ein zufaelliger Teil, damit er nicht erratbar ist.
 */

const ALPHABET = "ABCDEFGHJKMNPQRSTUVWXYZ23456789";

const randomPart = (length: number) => {
  // crypto ist im Browser und in Node vorhanden; Math.random waere fuer einen
  // Code, der Geld wert ist, zu schwach.
  const bytes = new Uint8Array(length);
  crypto.getRandomValues(bytes);
  return Array.from(bytes, (b) => ALPHABET[b % ALPHABET.length]).join("");
};

/** Umlaute und Sonderzeichen zu A–Z; alles andere faellt weg. */
function asciiName(name: string) {
  return name
    .toUpperCase()
    .replace(/Ä/g, "AE")
    .replace(/Ö/g, "OE")
    .replace(/Ü/g, "UE")
    .replace(/ß/g, "SS")
    .replace(/İ|Ï|Î/g, "I")
    .replace(/Ş/g, "S")
    .replace(/Ğ/g, "G")
    .replace(/Ç/g, "C")
    // NFD trennt die Akzente ab, die Filterzeile darunter wirft sie mit weg.
    .normalize("NFD")
    .replace(/[^A-Z]/g, "");
}

/**
 * @param name Name des Kunden oder des Brautpaars – nur zur Wiedererkennung.
 * @param prefix Kuerzel des Betriebs.
 */
export function makeCouponCode(name: string, prefix = "LUM") {
  const part = asciiName(name).slice(0, 6);
  return [prefix, part || randomPart(4), randomPart(4)].join("-");
}

/** Vergleich immer ueber diese Funktion – Gross-/Kleinschreibung egal. */
export const normalizeCoupon = (value: string) => value.trim().toLowerCase();
