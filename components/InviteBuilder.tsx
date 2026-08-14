"use client";

import { useEffect, useMemo, useRef, useState } from "react";
import Link from "next/link";
import { Sprig, Divider, WaxSeal } from "./invite/Ornaments";
import { getDict } from "@/lib/dict";
import { track } from "@/lib/track";
import { slugify, resizeImage, dateBlocks, defaultSections } from "@/lib/invite";
import { themes, themeById } from "@/lib/themes";
import { eventTypes, eventTypeById, headline } from "@/lib/events";
import { SECTION_PRICES, priceLines, totals, euro } from "@/lib/pricing";
import type { EventType, InviteEvent, InviteSections, ProgramItem } from "@/lib/store";
import type { Locale } from "@/lib/i18n";

const field =
  "w-full border-b border-sand-deep bg-transparent px-0 py-3 text-[0.95rem] text-ink outline-none transition-colors placeholder:text-muted/50 focus:border-gold";
const label = "block text-[0.64rem] uppercase tracking-[0.2em] text-muted";

const emptyEvent = (name = ""): InviteEvent => ({ name, date: "", time: "16:00", venue: "", address: "" });

/** Zwischenstand im Browser – überlebt Neuladen, Zurück-Taste und Handywechsel des Tabs. */
const DRAFT_KEY = "al-invite-draft-v1";

export default function InviteBuilder({ locale }: { locale: Locale }) {
  const t = getDict(locale).invite;
  const de = locale === "de";
  const [step, setStep] = useState(0);
  const [busy, setBusy] = useState(false);
  const [error, setError] = useState("");
  const [copied, setCopied] = useState(false);
  const [uploading, setUploading] = useState(false);
  const [done, setDone] = useState<{ url: string; path: string; slug: string; price: number } | null>(null);
  const [payState, setPayState] = useState<"idle" | "loading" | "unconfigured">("idle");
  const fileRef = useRef<HTMLInputElement>(null);

  const [f, setF] = useState({
    eventType: "wedding" as EventType,
    theme: "elysee",
    bride: "",
    groom: "",
    message: de
      ? "Wir möchten diesen besonderen Tag mit euch feiern."
      : "Bu özel günü sizinle birlikte kutlamak isteriz.",
    closing: "",
    hashtag: "",
    familyBride: "",
    familyGroom: "",
    musicUrl: "",
    videoUrl: "",
    slug: "",
    coupon: "",
  });

  const [events, setEvents] = useState<InviteEvent[]>([emptyEvent(de ? "Hochzeit" : "Düğün")]);
  const [sections, setSections] = useState<InviteSections>(defaultSections());
  const [program, setProgram] = useState<ProgramItem[]>([
    { time: "16:00", title: de ? "Empfang" : "Karşılama" },
    { time: "17:00", title: de ? "Trauung" : "Nikah" },
  ]);
  const [menu, setMenu] = useState<string[]>([]);
  const [photos, setPhotos] = useState<string[]>([]);

  /** Gutschein: geprüft wird auf dem Server, nie im Browser. */
  const [coupon, setCoupon] = useState<{ checking: boolean; ok: boolean; reason?: string }>({
    checking: false,
    ok: false,
  });
  const [draft, setDraft] = useState<{ token: string; url: string } | null>(null);
  const [draftState, setDraftState] = useState<"idle" | "saving" | "saved" | "error">("idle");
  const [restored, setRestored] = useState(false);

  const set = (k: keyof typeof f, v: string) => {
    setF((prev) => ({ ...prev, [k]: v }));
    setError("");
  };
  const setEvent = (i: number, patch: Partial<InviteEvent>) =>
    setEvents((prev) => prev.map((e, k) => (k === i ? { ...e, ...patch } : e)));
  const toggle = (k: keyof InviteSections) => setSections((prev) => ({ ...prev, [k]: !prev[k] }));

  /* ------------------------- Zwischenstand ------------------------- */

  type Snapshot = {
    f?: Partial<typeof f>;
    events?: InviteEvent[];
    sections?: Partial<InviteSections>;
    program?: ProgramItem[];
    menu?: string[];
    photos?: string[];
    step?: number;
  };

  function applySnapshot(data: Snapshot | null) {
    if (!data) return;
    if (data.f) setF((prev) => ({ ...prev, ...data.f }));
    if (Array.isArray(data.events) && data.events.length) setEvents(data.events);
    if (data.sections) setSections((prev) => ({ ...prev, ...data.sections }));
    if (Array.isArray(data.program)) setProgram(data.program);
    if (Array.isArray(data.menu)) setMenu(data.menu);
    if (Array.isArray(data.photos)) setPhotos(data.photos);
    if (typeof data.step === "number") setStep(Math.max(0, Math.min(data.step, LAST)));
  }

  // Beim Öffnen: Fortsetzungslink schlägt den Browser-Zwischenstand.
  useEffect(() => {
    const token = new URLSearchParams(window.location.search).get("taslak");
    if (!token) {
      try {
        const raw = localStorage.getItem(DRAFT_KEY);
        // eslint-disable-next-line react-hooks/set-state-in-effect -- localStorage gibt es erst im Browser, also nach dem ersten Rendern
        if (raw) applySnapshot(JSON.parse(raw) as Snapshot);
      } catch {
        // Kein lesbarer Zwischenstand – dann eben von vorn.
      }
      setRestored(true);
      return;
    }

    fetch(`/api/entwurf?token=${encodeURIComponent(token)}`)
      .then((r) => (r.ok ? r.json() : null))
      .then((json) => {
        if (json?.data) {
          applySnapshot(json.data as Snapshot);
          setDraft({ token, url: `${window.location.origin}/${locale}/einladung?taslak=${token}` });
        }
      })
      .finally(() => setRestored(true));
    // eslint-disable-next-line react-hooks/exhaustive-deps -- nur beim ersten Rendern
  }, []);

  // Laufend sichern. Ohne Fotos: Data-URLs sprengen den localStorage –
  // die liegen nur im serverseitigen Entwurf.
  useEffect(() => {
    if (!restored || done) return;
    const id = window.setTimeout(() => {
      try {
        localStorage.setItem(DRAFT_KEY, JSON.stringify({ f, events, sections, program, menu, step }));
      } catch {
        // Speicher voll oder gesperrt – der Assistent läuft trotzdem weiter.
      }
    }, 400);
    return () => window.clearTimeout(id);
  }, [restored, done, f, events, sections, program, menu, step]);

  // Gutschein serverseitig prüfen, kurz verzögert nach der Eingabe.
  useEffect(() => {
    const value = f.coupon.trim();
    const id = window.setTimeout(async () => {
      if (!value) {
        setCoupon({ checking: false, ok: false });
        return;
      }
      setCoupon((c) => ({ ...c, checking: true }));
      try {
        const res = await fetch("/api/kupon", {
          method: "POST",
          headers: { "Content-Type": "application/json" },
          body: JSON.stringify({ code: value }),
        });
        const json = await res.json();
        setCoupon({ checking: false, ok: Boolean(json.ok), reason: json.reason });
      } catch {
        setCoupon({ checking: false, ok: false, reason: "failed" });
      }
    }, 400);
    return () => window.clearTimeout(id);
  }, [f.coupon]);

  /** Entwurf auf dem Server ablegen und einen Fortsetzungslink zurückgeben. */
  async function saveDraft() {
    setDraftState("saving");
    try {
      const res = await fetch("/api/entwurf", {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({
          token: draft?.token,
          label: `${f.bride} & ${f.groom}`.trim() === "&" ? "" : `${f.bride} & ${f.groom}`,
          data: { f, events, sections, program, menu, photos, step },
        }),
      });
      const json = await res.json();
      if (!res.ok || !json.token) throw new Error("failed");
      setDraft({ token: json.token, url: `${window.location.origin}/${locale}/einladung?taslak=${json.token}` });
      setDraftState("saved");
    } catch {
      setDraftState("error");
    }
  }

  function chooseType(id: EventType) {
    const opt = eventTypeById(id);
    const [n1, n2] = opt.defaultEventNames[locale];
    setF((prev) => ({ ...prev, eventType: id }));
    setEvents(opt.two ? [emptyEvent(n1), emptyEvent(n2)] : [emptyEvent(n1)]);
  }

  const th = themeById(f.theme);
  const autoSlug = useMemo(() => f.slug || slugify(`${f.bride}-${f.groom}`) || "einladung", [f.slug, f.bride, f.groom]);
  const isFree = coupon.ok;
  const initials = `${f.bride.charAt(0) || "A"}${f.groom.charAt(0) || "M"}`.toUpperCase();
  const title = headline[f.eventType]?.[locale] ?? t.weMarry;

  const steps = [t.stepEvent, t.stepTheme, t.stepCouple, t.stepPlace, t.stepSections, t.stepPhotos, t.stepFinish];
  const LAST = steps.length - 1;

  const canNext =
    (step === 0) ||
    (step === 1) ||
    (step === 2 && f.bride.trim() !== "" && f.groom.trim() !== "" && events[0].date !== "") ||
    (step === 3 && events[0].venue.trim() !== "") ||
    step === 4 ||
    step === 5;

  async function onFiles(list: FileList | null) {
    if (!list?.length) return;
    setUploading(true);
    try {
      const next: string[] = [];
      for (const file of Array.from(list).slice(0, 4 - photos.length)) {
        if (!file.type.startsWith("image/")) continue;
        next.push(await resizeImage(file));
      }
      setPhotos((p) => [...p, ...next].slice(0, 4));
    } catch {
      setError(de ? "Bild konnte nicht gelesen werden." : "Görsel okunamadı.");
    } finally {
      setUploading(false);
      if (fileRef.current) fileRef.current.value = "";
    }
  }

  async function create() {
    setBusy(true);
    setError("");
    try {
      const body = {
        ...f,
        families: sections.family ? { bride: f.familyBride, groom: f.familyGroom } : undefined,
        slug: autoSlug,
        events,
        sections,
        program,
        menu,
        photos,
        locale,
      };
      const res = await fetch("/api/einladung", {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify(body),
      });
      const data = await res.json();
      if (!res.ok) throw new Error(data.error || "error");
      try {
        localStorage.setItem(`al-invite-${data.slug}`, JSON.stringify({ ...body, slug: data.slug }));
      } catch {}
      setDone({ url: data.url, path: data.path, slug: data.slug, price: data.price ?? 0 });
      setStep(LAST + 1);
      track("invite", data.price ?? 0);

      // Der Entwurf hat seinen Zweck erfüllt.
      try {
        localStorage.removeItem(DRAFT_KEY);
      } catch {
        // egal
      }
      if (draft?.token) {
        fetch(`/api/entwurf?token=${encodeURIComponent(draft.token)}`, { method: "DELETE" }).catch(() => {});
      }
    } catch {
      setError(de ? "Da ist etwas schiefgelaufen." : "Bir şeyler ters gitti.");
    } finally {
      setBusy(false);
    }
  }

  const sectionLabels = {
    menu: t.secMenu,
    music: t.secMusic,
    video: t.secVideo,
  } as Partial<Record<keyof InviteSections, string>>;

  const lines = priceLines(
    sections,
    events.length > 1,
    sectionLabels,
    de ? "Digitale Einladung" : "Dijital davetiye",
    de ? "Zweite Feier" : "İkinci tören"
  );
  const sum = totals(lines);

  const sectionRows: { key: keyof InviteSections; name: string; desc: string }[] = [
    { key: "rsvp", name: t.secRsvp, desc: t.secRsvpD },
    { key: "location", name: t.secLocation, desc: t.secLocationD },
    { key: "countdown", name: t.secCountdown, desc: t.secCountdownD },
    { key: "program", name: t.secProgram, desc: t.secProgramD },
    { key: "menu", name: t.secMenu, desc: t.secMenuD },
    { key: "family", name: t.secFamily, desc: t.secFamilyD },
    { key: "music", name: t.secMusic, desc: t.secMusicD },
    { key: "video", name: t.secVideo, desc: t.secVideoD },
  ];

  return (
    <div className="grid gap-12 lg:grid-cols-[1fr_360px] lg:gap-16">
      <div>
        {/* Schrittanzeige */}
        <div className="mb-10 border-b border-sand-deep pb-5">
          <div className="flex items-baseline gap-4">
            <span className="text-[0.68rem] uppercase tracking-[0.24em] text-gold">
              {String(Math.min(step + 1, steps.length)).padStart(2, "0")} / 0{steps.length}
            </span>
            <h2 className="font-display text-2xl font-light text-ink sm:text-3xl">
              {step > LAST ? t.stepDone : steps[step]}
            </h2>
          </div>
          <div className="mt-4 h-px w-full bg-sand-deep">
            <div
              className="h-px bg-gold transition-all duration-500"
              style={{ width: `${((Math.min(step, LAST) + 1) / steps.length) * 100}%` }}
            />
          </div>
        </div>

        {/* 01 — Anlass */}
        {step === 0 && (
          <div className="anim-up">
            <p className="text-sm text-muted">{t.eventQuestion}</p>
            <div className="mt-7 grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
              {eventTypes.map((opt) => (
                <button
                  key={opt.id}
                  onClick={() => chooseType(opt.id)}
                  className={`border p-6 text-center transition-all ${
                    f.eventType === opt.id
                      ? "border-gold bg-sand/40 shadow-[0_10px_30px_-20px_rgba(20,17,15,.6)]"
                      : "border-sand-deep hover:border-muted"
                  }`}
                >
                  <div className="text-2xl">{opt.icon}</div>
                  <div className="font-display mt-3 text-lg text-ink">{opt.name[locale]}</div>
                  <div className="mt-1.5 text-[0.74rem] leading-relaxed text-muted">{opt.sub[locale]}</div>
                </button>
              ))}
            </div>
          </div>
        )}

        {/* 02 — Tema */}
        {step === 1 && (
          <div className="anim-up">
            <p className="text-sm text-muted">{t.designHint}</p>
            <div className="mt-7 grid grid-cols-2 gap-4 sm:grid-cols-3">
              {themes.map((theme) => (
                <button
                  key={theme.id}
                  onClick={() => set("theme", theme.id)}
                  className={`border p-2 text-left transition-all ${
                    f.theme === theme.id ? "border-gold shadow-[0_10px_30px_-18px_rgba(20,17,15,.6)]" : "border-sand-deep hover:border-muted"
                  }`}
                >
                  <div
                    className="relative flex aspect-[3/4] items-center justify-center overflow-hidden"
                    style={{ background: theme.envelope, backgroundImage: theme.texture }}
                  >
                    <div
                      className="absolute inset-x-4 bottom-4 top-8 flex flex-col items-center justify-center gap-2"
                      style={{ background: theme.paper, border: `1px solid ${theme.paperEdge}` }}
                    >
                      <span style={{ color: theme.accent }}>
                        <Sprig className="h-3 w-14 opacity-70" />
                      </span>
                      <span className="font-display text-lg font-light" style={{ color: theme.fg }}>
                        {initials}
                      </span>
                    </div>
                    <span className="absolute bottom-3 right-3">
                      <WaxSeal initials={initials} color={theme.seal} textColor={theme.sealText} size={30} />
                    </span>
                  </div>
                  <div className="mt-2.5 font-display text-[0.95rem] text-ink">{theme.name}</div>
                  <div className="text-[0.62rem] uppercase tracking-[0.12em] text-muted">{theme.sub[locale]}</div>
                </button>
              ))}
            </div>
          </div>
        )}

        {/* 03 — Bilgiler */}
        {step === 2 && (
          <div className="anim-up space-y-8">
            <div className="grid gap-7 sm:grid-cols-2">
              <div>
                <label className={label}>{t.bride} *</label>
                <input className={field} value={f.bride} onChange={(e) => set("bride", e.target.value)} placeholder="Ayşe" />
              </div>
              <div>
                <label className={label}>{t.groom} *</label>
                <input className={field} value={f.groom} onChange={(e) => set("groom", e.target.value)} placeholder="Mehmet" />
              </div>
            </div>

            {events.map((ev, i) => (
              <div key={i} className={events.length > 1 ? "border border-sand-deep p-5" : ""}>
                {events.length > 1 && (
                  <div className="mb-4 text-[0.64rem] uppercase tracking-[0.2em] text-gold">
                    {i === 0 ? t.event1 : t.event2}
                  </div>
                )}
                <div className="grid gap-7 sm:grid-cols-3">
                  {events.length > 1 && (
                    <div className="sm:col-span-3">
                      <label className={label}>{t.eventName}</label>
                      <input className={field} value={ev.name} onChange={(e) => setEvent(i, { name: e.target.value })} />
                    </div>
                  )}
                  <div className="sm:col-span-2">
                    <label className={label}>
                      {t.date} {i === 0 ? "*" : ""}
                    </label>
                    <input type="date" className={field} value={ev.date} onChange={(e) => setEvent(i, { date: e.target.value })} />
                  </div>
                  <div>
                    <label className={label}>{t.time}</label>
                    <input type="time" className={field} value={ev.time} onChange={(e) => setEvent(i, { time: e.target.value })} />
                  </div>
                </div>
              </div>
            ))}

            <div>
              <label className={label}>{t.message}</label>
              <textarea className={`${field} resize-none`} rows={3} value={f.message} onChange={(e) => set("message", e.target.value)} />
            </div>
            <div className="grid gap-7 sm:grid-cols-2">
              <div>
                <label className={label}>{t.closing}</label>
                <input className={field} value={f.closing} onChange={(e) => set("closing", e.target.value)} />
              </div>
              <div>
                <label className={label}>{t.hashtag}</label>
                <input className={field} value={f.hashtag} onChange={(e) => set("hashtag", e.target.value)} placeholder="#AyseVeMehmet" />
              </div>
            </div>
          </div>
        )}

        {/* 04 — Mekân */}
        {step === 3 && (
          <div className="anim-up space-y-8">
            {events.map((ev, i) => (
              <div key={i} className={events.length > 1 ? "border border-sand-deep p-5" : ""}>
                {events.length > 1 && (
                  <div className="mb-4 text-[0.64rem] uppercase tracking-[0.2em] text-gold">{ev.name || (i === 0 ? t.event1 : t.event2)}</div>
                )}
                <div className="space-y-7">
                  <div>
                    <label className={label}>
                      {t.venue} {i === 0 ? "*" : ""}
                    </label>
                    <input className={field} value={ev.venue} onChange={(e) => setEvent(i, { venue: e.target.value })} placeholder="Alte Kelter Fellbach" />
                  </div>
                  <div>
                    <label className={label}>{t.address}</label>
                    <input
                      className={field}
                      value={ev.address}
                      onChange={(e) => setEvent(i, { address: e.target.value })}
                      placeholder="Kelterweg 1, 70734 Fellbach"
                    />
                  </div>
                </div>
              </div>
            ))}
          </div>
        )}

        {/* 05 — Bölümler */}
        {step === 4 && (
          <div className="anim-up">
            <p className="max-w-xl text-sm leading-relaxed text-muted">{t.sectionsIntro}</p>

            <div className="mt-7 space-y-3">
              {sectionRows.map((row) => (
                <div key={row.key} className="border border-sand-deep p-5">
                  <div className="flex items-start justify-between gap-5">
                    <div>
                      <div className="flex items-center gap-3">
                        <span className="font-display text-lg text-ink">{row.name}</span>
                        {SECTION_PRICES[row.key].regular === 0 ? (
                          <span className="border border-sand-deep px-2 py-0.5 text-[0.55rem] uppercase tracking-[0.14em] text-muted">
                            {t.free}
                          </span>
                        ) : (
                          <span className="flex items-center gap-2">
                            {SECTION_PRICES[row.key].now < SECTION_PRICES[row.key].regular && (
                              <span className="text-[0.62rem] text-muted line-through">
                                {euro(SECTION_PRICES[row.key].regular)}
                              </span>
                            )}
                            <span className="bg-gold/15 px-2 py-0.5 text-[0.58rem] uppercase tracking-[0.12em] text-gold">
                              {SECTION_PRICES[row.key].now === 0 ? t.free : `+ ${euro(SECTION_PRICES[row.key].now)}`}
                            </span>
                            {SECTION_PRICES[row.key].now < SECTION_PRICES[row.key].regular && (
                              <span className="text-[0.55rem] uppercase tracking-[0.12em] text-gold">{t.introOffer}</span>
                            )}
                          </span>
                        )}
                      </div>
                      <p className="mt-1 text-[0.8rem] leading-relaxed text-muted">{row.desc}</p>
                    </div>
                    <button
                      onClick={() => toggle(row.key)}
                      role="switch"
                      aria-checked={sections[row.key]}
                      aria-label={row.name}
                      className={`relative h-7 w-12 shrink-0 rounded-full transition-colors ${
                        sections[row.key] ? "bg-gold" : "bg-sand-deep"
                      }`}
                    >
                      <span
                        className={`absolute top-1 h-5 w-5 rounded-full bg-cream shadow transition-all ${
                          sections[row.key] ? "left-6" : "left-1"
                        }`}
                      />
                    </button>
                  </div>

                  {/* Inline-Editoren */}
                  {sections[row.key] && row.key === "program" && (
                    <div className="mt-5 space-y-3 border-t border-sand-deep/60 pt-5">
                      {program.map((p, i) => (
                        <div key={i} className="flex items-center gap-3">
                          <input
                            type="time"
                            value={p.time}
                            onChange={(e) => setProgram((prev) => prev.map((x, k) => (k === i ? { ...x, time: e.target.value } : x)))}
                            className={`${field} w-28 shrink-0`}
                          />
                          <input
                            value={p.title}
                            onChange={(e) => setProgram((prev) => prev.map((x, k) => (k === i ? { ...x, title: e.target.value } : x)))}
                            className={field}
                            placeholder={de ? "Programmpunkt" : "Program başlığı"}
                          />
                          <button
                            onClick={() => setProgram((prev) => prev.filter((_, k) => k !== i))}
                            className="shrink-0 text-muted transition-colors hover:text-gold"
                          >
                            ✕
                          </button>
                        </div>
                      ))}
                      {program.length < 8 && (
                        <button
                          onClick={() => setProgram((p) => [...p, { time: "20:00", title: "" }])}
                          className="mt-2 border border-sand-deep px-5 py-2 text-[0.62rem] uppercase tracking-[0.18em] text-ink-soft hover:border-gold hover:text-gold"
                        >
                          + {t.programAdd}
                        </button>
                      )}
                    </div>
                  )}

                  {sections[row.key] && row.key === "menu" && (
                    <div className="mt-5 border-t border-sand-deep/60 pt-5">
                      <textarea
                        rows={4}
                        value={menu.join("\n")}
                        onChange={(e) => setMenu(e.target.value.split("\n"))}
                        className={`${field} resize-none`}
                        placeholder={de ? "Vorspeise\nSuppe\nHauptgang\nDessert" : "Başlangıç\nÇorba\nAna yemek\nTatlı"}
                      />
                      <p className="mt-2 text-[0.72rem] text-muted">{t.menuHint}</p>
                    </div>
                  )}

                  {sections[row.key] && row.key === "family" && (
                    <div className="mt-5 grid gap-5 border-t border-sand-deep/60 pt-5 sm:grid-cols-2">
                      <div>
                        <label className={label}>{t.familyBride}</label>
                        <input className={field} value={f.familyBride} onChange={(e) => set("familyBride", e.target.value)} placeholder="Yıldız Ailesi" />
                      </div>
                      <div>
                        <label className={label}>{t.familyGroom}</label>
                        <input className={field} value={f.familyGroom} onChange={(e) => set("familyGroom", e.target.value)} placeholder="Demir Ailesi" />
                      </div>
                    </div>
                  )}

                  {sections[row.key] && row.key === "music" && (
                    <div className="mt-5 border-t border-sand-deep/60 pt-5">
                      <label className={label}>{t.musicUrl}</label>
                      <input className={field} value={f.musicUrl} onChange={(e) => set("musicUrl", e.target.value)} placeholder="https://…/song.mp3" />
                    </div>
                  )}

                  {sections[row.key] && row.key === "video" && (
                    <div className="mt-5 border-t border-sand-deep/60 pt-5">
                      <label className={label}>{t.videoUrl}</label>
                      <input className={field} value={f.videoUrl} onChange={(e) => set("videoUrl", e.target.value)} placeholder="https://vimeo.com/123456789" />
                    </div>
                  )}
                </div>
              ))}
            </div>

            <div className="mt-8 border border-sand-deep bg-sand/30 p-6">
              <div className="text-[0.6rem] uppercase tracking-[0.24em] text-gold">{t.priceSummary}</div>
              <div className="mt-4 divide-y divide-sand-deep/60">
                {lines.map((l) => (
                  <div key={l.key} className="flex items-baseline justify-between gap-6 py-2.5 text-[0.88rem]">
                    <span className="text-ink-soft">{l.label}</span>
                    <span className="flex items-baseline gap-3">
                      {l.now < l.regular && <span className="text-[0.75rem] text-muted line-through">{euro(l.regular)}</span>}
                      <span className={l.now === 0 ? "text-gold" : "text-ink"}>{l.now === 0 ? t.free : euro(l.now)}</span>
                    </span>
                  </div>
                ))}
              </div>
              <div className="mt-3 flex items-baseline justify-between border-t border-sand-deep pt-4">
                <span className="text-[0.62rem] uppercase tracking-[0.2em] text-muted">{t.total}</span>
                <span className="font-display text-3xl font-light text-ink">{isFree ? t.priceFree : euro(sum.now)}</span>
              </div>
              {!isFree && sum.saved > 0 && (
                <div className="mt-2 text-right text-[0.74rem] text-gold">
                  {t.youSave} {euro(sum.saved)}
                </div>
              )}
            </div>
          </div>
        )}

        {/* 06 — Fotoğraflar */}
        {step === 5 && (
          <div className="anim-up">
            <p className="text-sm text-muted">{t.photosHint}</p>
            <div className="mt-6 grid grid-cols-2 gap-4 sm:grid-cols-4">
              {photos.map((src, i) => (
                <div key={i} className="group relative aspect-[3/4] overflow-hidden border border-sand-deep">
                  {/* eslint-disable-next-line @next/next/no-img-element -- lokale Vorschau der hochgeladenen Datei */}
                  <img src={src} alt="" className="h-full w-full object-cover" />
                  {i === 0 && (
                    <span className="absolute left-2 top-2 bg-gold px-2 py-0.5 text-[0.55rem] uppercase tracking-[0.14em] text-white">1</span>
                  )}
                  <button
                    onClick={() => setPhotos((p) => p.filter((_, k) => k !== i))}
                    className="absolute inset-x-0 bottom-0 bg-ink/80 py-2 text-[0.6rem] uppercase tracking-[0.16em] text-cream opacity-0 transition-opacity group-hover:opacity-100"
                  >
                    {t.photoRemove}
                  </button>
                </div>
              ))}

              {photos.length < 4 && (
                <button
                  onClick={() => fileRef.current?.click()}
                  className="flex aspect-[3/4] flex-col items-center justify-center gap-3 border border-dashed border-sand-deep text-muted transition-colors hover:border-gold hover:text-gold"
                >
                  <svg width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="1.2">
                    <rect x="3" y="5" width="18" height="15" rx="1.5" />
                    <circle cx="12" cy="12" r="3.4" />
                    <path d="M8 5l1.4-2h5.2L16 5" />
                  </svg>
                  <span className="px-3 text-center text-[0.62rem] uppercase tracking-[0.16em]">
                    {uploading ? t.photoWorking : t.photoAdd}
                  </span>
                </button>
              )}
            </div>
            <input ref={fileRef} type="file" accept="image/*" multiple onChange={(e) => onFiles(e.target.files)} className="hidden" />
          </div>
        )}

        {/* 07 — Link */}
        {step === 6 && (
          <div className="anim-up space-y-9">
            <div>
              <label className={label}>{t.slug}</label>
              <div className="flex items-center gap-1 border-b border-sand-deep py-3 text-[0.9rem] text-muted">
                <span className="shrink-0">/{locale}/einladung/</span>
                <input
                  className="w-full bg-transparent text-ink outline-none"
                  value={f.slug}
                  onChange={(e) => set("slug", slugify(e.target.value))}
                  placeholder={slugify(`${f.bride}-${f.groom}`) || "ayse-mehmet"}
                />
              </div>
              <p className="mt-2 text-[0.72rem] text-muted">{t.slugHint}</p>
            </div>

            <div>
              <label className={label}>{t.coupon}</label>
              <input className={field} value={f.coupon} onChange={(e) => set("coupon", e.target.value)} placeholder="lumiere2026" />
              <p className={`mt-2 text-[0.72rem] ${isFree ? "text-gold" : "text-muted"}`}>
                {!f.coupon
                  ? t.couponHint
                  : coupon.checking
                    ? t.couponChecking
                    : isFree
                      ? t.couponOk
                      : coupon.reason === "used"
                        ? t.couponUsed
                        : coupon.reason === "expired"
                          ? t.couponExpired
                          : t.couponBad}
              </p>
            </div>

            {/* Entwurf sichern */}
            <div className="border border-sand-deep p-5">
              <div className="text-[0.64rem] uppercase tracking-[0.2em] text-muted">{t.draftTitle}</div>
              <p className="mt-2 text-[0.78rem] leading-relaxed text-muted">{t.draftHint}</p>
              <button
                type="button"
                onClick={saveDraft}
                disabled={draftState === "saving"}
                className="mt-4 border border-ink px-6 py-3 text-[0.66rem] uppercase tracking-[0.2em] text-ink transition-colors hover:bg-ink hover:text-cream disabled:opacity-50"
              >
                {draftState === "saving" ? t.draftSaving : t.draftSave}
              </button>
              {draftState === "saved" && draft && (
                <div className="mt-4">
                  <p className="text-[0.74rem] text-gold">{t.draftSaved}</p>
                  <div className="mt-2 flex flex-col gap-2 sm:flex-row">
                    <code className="flex-1 overflow-x-auto border border-sand-deep bg-sand/40 px-3 py-2.5 text-[0.74rem] text-ink">
                      {draft.url}
                    </code>
                    <button
                      type="button"
                      onClick={() => navigator.clipboard?.writeText(draft.url)}
                      className="border border-ink px-5 py-2.5 text-[0.66rem] uppercase tracking-[0.2em] text-ink hover:bg-ink hover:text-cream"
                    >
                      {t.copy}
                    </button>
                  </div>
                </div>
              )}
              {draftState === "error" && <p className="mt-3 text-[0.74rem] text-red-700">{t.draftError}</p>}
            </div>

            {/* Zusammenfassung */}
            <div className="divide-y divide-sand-deep border-y border-sand-deep text-[0.88rem]">
              {[
                [t.stepEvent, eventTypeById(f.eventType).name[locale]],
                [t.stepTheme, th.name],
                [de ? "Namen" : "İsimler", `${f.bride || "—"} & ${f.groom || "—"}`],
                [t.date, events.map((e) => e.date || "—").join(" · ")],
                [t.venue, events.map((e) => e.venue || "—").join(" · ")],
                [
                  t.stepSections,
                  sectionRows.filter((r) => sections[r.key]).map((r) => r.name).join(", ") || "—",
                ],
                [t.stepPhotos, String(photos.length)],
              ].map(([k, v]) => (
                <div key={k} className="flex justify-between gap-6 py-3">
                  <span className="text-muted">{k}</span>
                  <span className="text-right text-ink">{v}</span>
                </div>
              ))}
            </div>

            <div className="border border-sand-deep bg-sand/30 p-6">
              <div className="text-[0.6rem] uppercase tracking-[0.24em] text-gold">{t.priceSummary}</div>
              <div className="mt-4 divide-y divide-sand-deep/60">
                {lines.map((l) => (
                  <div key={l.key} className="flex items-baseline justify-between gap-6 py-2.5 text-[0.88rem]">
                    <span className="text-ink-soft">{l.label}</span>
                    <span className="flex items-baseline gap-3">
                      {l.now < l.regular && <span className="text-[0.75rem] text-muted line-through">{euro(l.regular)}</span>}
                      <span className={l.now === 0 ? "text-gold" : "text-ink"}>{l.now === 0 ? t.free : euro(l.now)}</span>
                    </span>
                  </div>
                ))}
              </div>
              <div className="mt-3 flex items-baseline justify-between border-t border-sand-deep pt-4">
                <span className="text-[0.62rem] uppercase tracking-[0.2em] text-muted">{t.total}</span>
                <span className="font-display text-3xl font-light text-ink">{isFree ? t.priceFree : euro(sum.now)}</span>
              </div>
              {isFree ? (
                <div className="mt-2 text-right text-[0.74rem] text-gold">{t.couponOk}</div>
              ) : (
                <>
                  {sum.saved > 0 && (
                    <div className="mt-2 text-right text-[0.74rem] text-gold">
                      {t.youSave} {euro(sum.saved)}
                    </div>
                  )}
                  <div className="mt-4 text-[0.72rem] leading-relaxed text-muted">{t.payNote}</div>
                </>
              )}
            </div>
          </div>
        )}

        {/* Fertig */}
        {step > LAST && done && (
          <div className="anim-up">
            {done.price > 0 && (
              <div className="mb-8 border border-gold/50 bg-sand/40 p-6">
                <div className="flex flex-wrap items-center justify-between gap-4">
                  <div>
                    <div className="text-[0.6rem] uppercase tracking-[0.24em] text-gold">{t.payTitle}</div>
                    <div className="font-display mt-1 text-2xl font-light text-ink">{euro(done.price)}</div>
                  </div>
                  <button
                    onClick={async () => {
                      setPayState("loading");
                      try {
                        const res = await fetch("/api/zahlung", {
                          method: "POST",
                          headers: { "Content-Type": "application/json" },
                          body: JSON.stringify({ slug: done.slug }),
                        });
                        const data = await res.json();
                        if (data.approveUrl) window.location.href = data.approveUrl;
                        else setPayState("unconfigured");
                      } catch {
                        setPayState("unconfigured");
                      }
                    }}
                    disabled={payState === "loading"}
                    className="flex items-center gap-3 rounded-full bg-[#ffc439] px-8 py-3.5 text-[0.8rem] font-medium text-[#003087] transition-opacity hover:opacity-90 disabled:opacity-60"
                  >
                    <span className="font-bold italic">PayPal</span>
                    {payState === "loading" ? "…" : t.payNow}
                  </button>
                </div>
                <p className="mt-4 text-[0.74rem] leading-relaxed text-muted">
                  {payState === "unconfigured" ? t.payPending : t.payNote}
                </p>
              </div>
            )}

            <div className="eyebrow">✓</div>
            <h3 className="headline mt-3 text-3xl">{t.doneTitle}</h3>
            <p className="mt-4 text-sm text-muted">{t.doneText}</p>
            <div className="mt-5 flex flex-col gap-3 sm:flex-row">
              <code className="flex-1 overflow-x-auto border border-sand-deep bg-sand/40 px-4 py-3.5 text-[0.8rem] text-ink">{done.url}</code>
              <button
                onClick={() => {
                  navigator.clipboard?.writeText(done.url);
                  setCopied(true);
                  setTimeout(() => setCopied(false), 2000);
                }}
                className="border border-ink px-6 py-3.5 text-[0.68rem] uppercase tracking-[0.2em] text-ink hover:bg-ink hover:text-cream"
              >
                {copied ? t.copied : t.copy}
              </button>
            </div>
            <div className="mt-8 flex flex-wrap gap-3">
              <Link href={done.path} className="bg-ink px-7 py-3.5 text-[0.68rem] uppercase tracking-[0.2em] text-cream hover:bg-gold">
                {t.openInvite}
              </Link>
              <a
                href={`https://wa.me/?text=${encodeURIComponent(`${t.shareText} ${done.url}`)}`}
                target="_blank"
                rel="noopener noreferrer"
                className="border border-ink px-7 py-3.5 text-[0.68rem] uppercase tracking-[0.2em] text-ink hover:bg-ink hover:text-cream"
              >
                {t.share}
              </a>
              <button
                onClick={() => {
                  setDone(null);
                  setStep(0);
                }}
                className="px-4 py-3.5 text-[0.68rem] uppercase tracking-[0.2em] text-muted hover:text-gold"
              >
                {t.createAnother}
              </button>
            </div>
          </div>
        )}

        {error && <p className="mt-6 text-sm text-red-700">{error}</p>}

        {step <= LAST && (
          <div className="mt-12 flex items-center gap-4 border-t border-sand-deep pt-7">
            {step > 0 && (
              <button
                onClick={() => setStep((s) => s - 1)}
                className="px-2 py-3 text-[0.68rem] uppercase tracking-[0.2em] text-muted transition-colors hover:text-ink"
              >
                ← {t.back}
              </button>
            )}
            {step < LAST ? (
              <button
                onClick={() => canNext && setStep((s) => s + 1)}
                disabled={!canNext}
                className="ml-auto bg-ink px-9 py-4 text-[0.68rem] uppercase tracking-[0.2em] text-cream transition-colors hover:bg-gold disabled:opacity-40"
              >
                {t.next} →
              </button>
            ) : (
              <button
                onClick={create}
                disabled={busy}
                className="ml-auto bg-gold px-9 py-4 text-[0.68rem] uppercase tracking-[0.2em] text-white transition-opacity hover:opacity-90 disabled:opacity-50"
              >
                {busy ? t.creating : isFree ? t.create : `${euro(sum.now)} · ${t.create}`}
              </button>
            )}
          </div>
        )}
      </div>

      {/* ---------- Live-Vorschau ---------- */}
      <aside className="lg:sticky lg:top-28 lg:self-start">
        <div className="text-[0.64rem] uppercase tracking-[0.2em] text-muted">{t.preview}</div>
        <div className="mt-4 overflow-hidden rounded-[2.2rem] border-[9px] border-ink shadow-[0_30px_70px_-30px_rgba(20,17,15,.55)]">
          <div
            className="flex min-h-[540px] flex-col items-center px-6 py-10 text-center"
            style={{ background: th.paper, backgroundImage: th.texture, color: th.fg }}
          >
            <span style={{ color: th.accent }}>
              <Sprig className="h-4 w-24 opacity-80" />
            </span>

            {sections.family && (f.familyBride || f.familyGroom) && (
              <div className="mt-4 text-[0.52rem] uppercase tracking-[0.2em]" style={{ color: th.soft }}>
                {f.familyBride || "…"} · {f.familyGroom || "…"}
              </div>
            )}

            <div className="mt-4 text-[0.52rem] uppercase tracking-[0.32em]" style={{ color: th.soft }}>
              {title}
            </div>

            <div className="font-display mt-4 flex flex-col leading-tight">
              <span className="text-2xl font-light">{f.bride || "Ayşe"}</span>
              <span className="my-0.5 text-lg italic" style={{ color: th.accent }}>
                &amp;
              </span>
              <span className="text-2xl font-light">{f.groom || "Mehmet"}</span>
            </div>

            <span className="mt-4" style={{ color: th.accent }}>
              <Divider className="h-3 w-36" />
            </span>

            {events.map((ev, i) => {
              const d = ev.date ? dateBlocks(ev.date, locale) : null;
              return (
                <div key={i} className="mt-4">
                  {events.length > 1 && ev.name && (
                    <div className="text-[0.5rem] uppercase tracking-[0.22em]" style={{ color: th.accent }}>
                      {ev.name}
                    </div>
                  )}
                  <div className="mt-1 flex items-center justify-center gap-3">
                    <span className="text-[0.5rem] uppercase tracking-[0.16em]" style={{ color: th.soft }}>
                      {d ? d.weekday : "—"}
                    </span>
                    <span className="font-display text-xl font-light">{d ? d.day : "··"}</span>
                    <span className="text-[0.5rem] uppercase tracking-[0.16em]" style={{ color: th.soft }}>
                      {d ? `${d.month} ${d.year}` : ""} {ev.time && `· ${ev.time}`}
                    </span>
                  </div>
                </div>
              );
            })}

            {photos[0] && (
              // eslint-disable-next-line @next/next/no-img-element -- lokale Vorschau
              <img src={photos[0]} alt="" className="mt-5 h-28 w-full object-cover" style={{ border: `1px solid ${th.paperEdge}` }} />
            )}

            {f.message && (
              <p className="mt-5 max-w-[15rem] text-[0.7rem] leading-relaxed" style={{ color: th.soft }}>
                {f.message}
              </p>
            )}

            {sections.program && program.filter((p) => p.title).length > 0 && (
              <ul className="mt-5 w-full max-w-[13rem] space-y-1.5">
                {program
                  .filter((p) => p.title)
                  .slice(0, 4)
                  .map((p, i) => (
                    <li key={i} className="flex items-baseline justify-between gap-3 text-[0.62rem]">
                      <span className="font-display text-sm">{p.time}</span>
                      <span className="h-px flex-1" style={{ background: th.accentSoft, opacity: 0.6 }} />
                      <span style={{ color: th.soft }}>{p.title}</span>
                    </li>
                  ))}
              </ul>
            )}

            {sections.menu && menu.filter(Boolean).length > 0 && (
              <ul className="mt-5 space-y-1">
                {menu.filter(Boolean).slice(0, 4).map((m, i) => (
                  <li key={i} className="font-display text-[0.8rem]">
                    {m}
                  </li>
                ))}
              </ul>
            )}

            {sections.location && events[0]?.venue && (
              <div className="font-display mt-5 text-base" style={{ color: th.fg }}>
                {events[0].venue}
              </div>
            )}

            {sections.rsvp && (
              <div className="mt-6 border px-5 py-2 text-[0.52rem] uppercase tracking-[0.24em]" style={{ borderColor: th.accent, color: th.accent }}>
                RSVP
              </div>
            )}

            {f.hashtag && (
              <div className="font-display mt-5 text-sm" style={{ color: th.accent }}>
                {f.hashtag}
              </div>
            )}
          </div>
        </div>
      </aside>
    </div>
  );
}
