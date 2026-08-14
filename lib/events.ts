import type { EventType } from "./store";
import type { L } from "./i18n";

export type EventTypeOption = {
  id: EventType;
  icon: string;
  name: L;
  sub: L;
  /** Standardmässig zwei Feiern (z. B. Henna-Abend + Hochzeit) */
  two?: boolean;
  defaultEventNames: L<[string, string]>;
};

export const eventTypes: EventTypeOption[] = [
  {
    id: "wedding",
    icon: "💍",
    name: { de: "Hochzeit", tr: "Düğün" },
    sub: { de: "Standesamt, freie Trauung oder Fest", tr: "Nikah veya düğün daveti" },
    defaultEventNames: { de: ["Hochzeit", "Feier"], tr: ["Düğün", "Kutlama"] },
  },
  {
    id: "multi",
    icon: "💐",
    name: { de: "Zwei Feiern", tr: "Çoklu etkinlik" },
    sub: { de: "Henna-Abend und Hochzeit in einer Einladung", tr: "Tek davetiyede kına ve düğün" },
    two: true,
    defaultEventNames: { de: ["Henna-Abend", "Hochzeit"], tr: ["Kına Gecesi", "Düğün"] },
  },
  {
    id: "henna",
    icon: "🕯️",
    name: { de: "Henna-Abend", tr: "Kına gecesi" },
    sub: { de: "Kına Gecesi", tr: "Kına gecesi daveti" },
    defaultEventNames: { de: ["Henna-Abend", ""], tr: ["Kına Gecesi", ""] },
  },
  {
    id: "engagement",
    icon: "💎",
    name: { de: "Verlobung", tr: "Nişan" },
    sub: { de: "Verlobungsfeier", tr: "Nişan töreni" },
    defaultEventNames: { de: ["Verlobung", ""], tr: ["Nişan", ""] },
  },
  {
    id: "circumcision",
    icon: "👑",
    name: { de: "Beschneidungsfest", tr: "Sünnet" },
    sub: { de: "Sünnet Töreni", tr: "Sünnet töreni" },
    defaultEventNames: { de: ["Fest", ""], tr: ["Sünnet Töreni", ""] },
  },
  {
    id: "birthday",
    icon: "🎂",
    name: { de: "Geburtstag", tr: "Doğum günü" },
    sub: { de: "Feier zum Geburtstag", tr: "Doğum günü kutlaması" },
    defaultEventNames: { de: ["Geburtstag", ""], tr: ["Doğum Günü", ""] },
  },
];

export const eventTypeById = (id: string) => eventTypes.find((e) => e.id === id) ?? eventTypes[0];

/** Überschrift der Einladung je nach Anlass. */
export const headline: Record<EventType, L> = {
  wedding: { de: "Wir heiraten", tr: "Evleniyoruz" },
  multi: { de: "Wir heiraten", tr: "Evleniyoruz" },
  henna: { de: "Henna-Abend", tr: "Kına gecemize davetlisiniz" },
  engagement: { de: "Wir verloben uns", tr: "Nişanlanıyoruz" },
  circumcision: { de: "Wir feiern", tr: "Kutluyoruz" },
  birthday: { de: "Wir feiern", tr: "Kutluyoruz" },
  corporate: { de: "Einladung", tr: "Davet" },
};
