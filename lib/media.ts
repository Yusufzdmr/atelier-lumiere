import "server-only";
import { put, del } from "@vercel/blob";

/**
 * Bilder in Vercel Blob (Region fra1).
 *
 * Der Browser verkleinert vor dem Upload und schickt eine Data-URL. Hier wird
 * daraus eine Datei im Blob-Store; in der Datenbank steht anschliessend nur
 * noch die URL. Base64 in Postgres waere langsam und teuer.
 */

const DATA_URL = /^data:(image\/[a-z+.-]+);base64,(.+)$/i;

const EXT: Record<string, string> = {
  "image/jpeg": "jpg",
  "image/jpg": "jpg",
  "image/png": "png",
  "image/webp": "webp",
  "image/avif": "avif",
  "image/gif": "gif",
};

const hasBlob = () => Boolean(process.env.BLOB_READ_WRITE_TOKEN);

/**
 * Nimmt Data-URLs entgegen und gibt Blob-URLs zurueck. Werte, die bereits
 * eine URL sind, werden unveraendert durchgereicht – so bleiben Bestandsdaten
 * und Demo-Platzhalter gueltig.
 */
export async function saveUploads(values: string[], prefix: string): Promise<string[]> {
  const out: string[] = [];

  for (const value of values) {
    const match = DATA_URL.exec(value.trim());
    if (!match) {
      out.push(value);
      continue;
    }
    if (!hasBlob()) {
      // Ohne Blob-Token (z. B. lokal ohne .env.local) bleibt die Data-URL erhalten.
      out.push(value);
      continue;
    }

    const [, mime, base64] = match;
    const body = Buffer.from(base64, "base64");
    const { url } = await put(`${prefix}/bild.${EXT[mime.toLowerCase()] ?? "jpg"}`, body, {
      access: "public",
      contentType: mime,
      addRandomSuffix: true,
    });
    out.push(url);
  }

  return out;
}

/** Loescht eine Datei aus dem Blob-Store. Fremde URLs werden ignoriert. */
export async function deleteUpload(url: string) {
  if (!hasBlob() || !url.startsWith("https://") || !url.includes(".blob.vercel-storage.com")) return;
  try {
    await del(url);
  } catch {
    // Bereits geloescht oder nie im Store – kein Grund, den Aufrufer scheitern zu lassen.
  }
}
