/**
 * Conversion-Meldungen aus der Oberflaeche heraus.
 *
 * Die eigentliche Arbeit macht <Tracking>: dort haengt `window.alTrack` und
 * dort wird auch geprueft, ob eine Einwilligung vorliegt. Hier steht nur der
 * Aufruf, damit Formulare und Assistent nichts ueber Google oder Meta wissen
 * muessen – und damit ohne Einwilligung schlicht nichts passiert.
 */

export type TrackEvent = "contact" | "invite" | "phone";

declare global {
  interface Window {
    alTrack?: (event: TrackEvent, value?: number) => void;
    dataLayer?: unknown[];
    gtag?: (...args: unknown[]) => void;
    fbq?: ((...args: unknown[]) => void) & { queue?: unknown[]; loaded?: boolean; version?: string; push?: unknown };
    _fbq?: unknown;
  }
}

export function track(event: TrackEvent, value?: number) {
  if (typeof window === "undefined") return;
  try {
    window.alTrack?.(event, value);
  } catch {
    // Messung darf nie den Ablauf stoeren.
  }
}
