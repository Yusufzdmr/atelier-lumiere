/**
 * YouTube- und Vimeo-Links erkennen und in Einbett-Adressen uebersetzen.
 *
 * Videos werden nicht hochgeladen, sondern bei YouTube oder Vimeo gehostet und
 * hier eingebunden – das spart Speicher und Transcoding und die Wiedergabe
 * funktioniert auf jedem Geraet.
 *
 * Datenschutz: YouTube wird ueber `youtube-nocookie.com` eingebunden, Vimeo mit
 * `dnt=1`. Geladen wird ohnehin erst nach Einwilligung (components/VideoEmbed).
 */

export type VideoProvider = "youtube" | "vimeo" | "file";

export type ParsedVideo = {
  provider: VideoProvider;
  /** Video-Kennung bei YouTube/Vimeo, sonst die Datei-URL */
  id: string;
  /** Adresse fuer den iframe bzw. das <video>-Element */
  embedUrl: string;
  /** Seite beim Anbieter – fuer den Link „bei YouTube ansehen“ */
  watchUrl: string;
};

const YOUTUBE = [
  /youtu\.be\/([\w-]{6,})/i,
  /youtube\.com\/watch\?(?:.*&)?v=([\w-]{6,})/i,
  /youtube(?:-nocookie)?\.com\/embed\/([\w-]{6,})/i,
  /youtube\.com\/shorts\/([\w-]{6,})/i,
];

const VIMEO = [/vimeo\.com\/(?:video\/)?(\d{6,})/i, /player\.vimeo\.com\/video\/(\d{6,})/i];

/** Gibt `null` zurueck, wenn nichts Brauchbares in der Eingabe steht. */
export function parseVideo(input?: string | null): ParsedVideo | null {
  const url = (input ?? "").trim();
  if (!url || !/^https?:\/\//i.test(url)) return null;

  for (const rx of YOUTUBE) {
    const m = rx.exec(url);
    if (m) {
      return {
        provider: "youtube",
        id: m[1],
        embedUrl: `https://www.youtube-nocookie.com/embed/${m[1]}?rel=0&modestbranding=1`,
        watchUrl: `https://www.youtube.com/watch?v=${m[1]}`,
      };
    }
  }

  for (const rx of VIMEO) {
    const m = rx.exec(url);
    if (m) {
      return {
        provider: "vimeo",
        id: m[1],
        embedUrl: `https://player.vimeo.com/video/${m[1]}?dnt=1`,
        watchUrl: `https://vimeo.com/${m[1]}`,
      };
    }
  }

  // Direkte Videodatei (z. B. ein kurzer Intro-Clip)
  if (/\.(mp4|webm|mov|m4v)(\?|$)/i.test(url)) {
    return { provider: "file", id: url, embedUrl: url, watchUrl: url };
  }

  return null;
}

export const isEmbeddedVideo = (v: ParsedVideo | null) => v?.provider === "youtube" || v?.provider === "vimeo";
