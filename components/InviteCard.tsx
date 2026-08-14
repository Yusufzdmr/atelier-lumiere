"use client";

import { useEffect, useRef, useState } from "react";
import Countdown from "./Countdown";
import RsvpForm from "./RsvpForm";
import { Sprig, Divider, CornerVine, WaxSeal } from "./invite/Ornaments";
import { getDict } from "@/lib/dict";
import { dateBlocks, mapsUrl } from "@/lib/invite";
import { parseVideo } from "@/lib/video";
import { themeById } from "@/lib/themes";
import { headline } from "@/lib/events";
import type { EventType, InviteEvent, InviteSections, ProgramItem } from "@/lib/store";
import type { Locale } from "@/lib/i18n";

export type InviteView = {
  slug: string;
  bride: string;
  groom: string;
  eventType: EventType;
  events: InviteEvent[];
  message: string;
  closing?: string;
  families?: { bride: string; groom: string };
  photos: string[];
  program: ProgramItem[];
  menu: string[];
  musicUrl?: string;
  videoUrl?: string;
  sections: InviteSections;
  hashtag?: string;
  theme: string;
};

type Phase = "closed" | "video" | "opening" | "done";

export default function InviteCard({
  invite,
  locale,
  origin,
  preview = false,
}: {
  invite: InviteView;
  locale: Locale;
  origin: string;
  preview?: boolean;
}) {
  const t = getDict(locale).invite;
  const th = themeById(invite.theme);
  const s = invite.sections;
  const audioRef = useRef<HTMLAudioElement>(null);

  const [phase, setPhase] = useState<Phase>(preview ? "done" : "closed");
  const [playing, setPlaying] = useState(false);

  const main = invite.events[0] ?? { name: "", date: "", time: "", venue: "", address: "" };
  const initials = `${invite.bride.charAt(0)}${invite.groom.charAt(0)}`.toUpperCase();
  const url = `${origin}/${locale}/einladung/${invite.slug}`;
  const waText = encodeURIComponent(`${t.shareText} ${url}`);
  const [hero, ...rest] = invite.photos ?? [];
  const done = phase === "done";
  const title = headline[invite.eventType]?.[locale] ?? t.weMarry;

  /** Tippen auf das Siegel: Musik starten (Nutzergeste!), dann Video oder direkt öffnen. */
  function open() {
    if (s.music && invite.musicUrl && audioRef.current) {
      audioRef.current.volume = 0.4;
      audioRef.current.play().then(
        () => setPlaying(true),
        () => setPlaying(false)
      );
    }
    setPhase(s.video && invite.videoUrl ? "video" : "opening");
  }

  useEffect(() => {
    if (phase !== "opening") return;
    const id = window.setTimeout(() => setPhase("done"), 2300);
    return () => window.clearTimeout(id);
  }, [phase]);

  useEffect(() => {
    if (preview) return;
    document.body.style.overflow = done ? "" : "hidden";
    return () => {
      document.body.style.overflow = "";
    };
  }, [done, preview]);

  function toggleMusic() {
    const a = audioRef.current;
    if (!a) return;
    if (a.paused) {
      a.play().then(() => setPlaying(true), () => {});
    } else {
      a.pause();
      setPlaying(false);
    }
  }

  return (
    <div
      data-standalone="invite"
      className="relative min-h-screen overflow-hidden"
      style={{ background: th.bg, color: th.fg, backgroundImage: th.texture }}
    >
      {s.music && invite.musicUrl && <audio ref={audioRef} src={invite.musicUrl} loop preload="none" />}

      {/* ---------- Video-Intro ---------- */}
      {phase === "video" && invite.videoUrl && (
        <div className="fixed inset-0 z-[80] flex items-center justify-center bg-black">
          {/* Direkte Datei laeuft von selbst und springt danach weiter; bei
              YouTube/Vimeo steuern die Gaeste selbst und tippen auf Weiter. */}
          {parseVideo(invite.videoUrl)?.provider === "file" ? (
            <video
              src={invite.videoUrl}
              autoPlay
              playsInline
              onEnded={() => setPhase("opening")}
              className="h-full w-full object-cover"
            />
          ) : (
            <iframe
              src={`${parseVideo(invite.videoUrl)?.embedUrl ?? ""}&autoplay=1`}
              title="Intro"
              allow="autoplay; encrypted-media; picture-in-picture"
              referrerPolicy="strict-origin-when-cross-origin"
              allowFullScreen
              className="aspect-video w-full max-w-5xl border-0"
            />
          )}
          <button
            onClick={() => setPhase("opening")}
            className="absolute bottom-8 right-8 border border-white/50 px-6 py-2.5 text-[0.64rem] uppercase tracking-[0.24em] text-white/80 transition-colors hover:bg-white hover:text-black"
          >
            {t.skip}
          </button>
        </div>
      )}

      {/* ---------- Kuvert ---------- */}
      {!preview && (
        <div className="env-stage" data-done={done} style={{ background: th.bg, backgroundImage: th.texture }}>
          <div className="flex flex-col items-center gap-10">
            <button className="env" data-open={phase !== "closed"} onClick={open} aria-label={t.openInvite}>
              <div className="env-back" style={{ background: th.envelope, border: `1px solid ${th.envelopeEdge}` }} />
              <div className="env-card" style={{ background: th.paper, border: `1px solid ${th.paperEdge}` }}>
                <div className="flex flex-col items-center gap-2 pb-6" style={{ color: th.accent }}>
                  <Sprig className="h-4 w-24 opacity-70" />
                  <span className="font-display text-2xl font-light tracking-[0.14em]">{initials}</span>
                </div>
              </div>
              <div className="env-front" style={{ background: th.envelope, borderTop: `1px solid ${th.envelopeEdge}` }} />
              <div className="env-flap" style={{ background: th.envelopeFlap, borderBottom: `1px solid ${th.envelopeEdge}` }} />
              <span className="env-seal">
                <WaxSeal initials={initials} color={th.seal} textColor={th.sealText} size={86} />
              </span>
            </button>

            {phase === "closed" && (
              <div className="env-hint text-center">
                <div className="text-[0.6rem] uppercase tracking-[0.34em]" style={{ color: th.soft }}>
                  {title}
                </div>
                <div className="font-display mt-2 text-2xl font-light">
                  {invite.bride} &amp; {invite.groom}
                </div>
                <div className="mt-4 text-[0.62rem] uppercase tracking-[0.28em]" style={{ color: th.accent }}>
                  {t.tapToOpen}
                </div>
              </div>
            )}
          </div>
        </div>
      )}

      {/* schwebende Blütenblätter */}
      <div className="pointer-events-none absolute inset-0 overflow-hidden" aria-hidden>
        {Array.from({ length: 12 }).map((_, i) => (
          <span
            key={i}
            className="petal"
            style={{
              left: `${(i * 8.4 + 3) % 96}%`,
              background: th.petal,
              opacity: 0.45,
              animationDuration: `${18 + (i % 5) * 5}s`,
              animationDelay: `${-i * 3}s`,
              transform: `scale(${0.7 + (i % 4) * 0.22})`,
            }}
          />
        ))}
      </div>

      {/* ---------- Karte ---------- */}
      <div className="relative mx-auto max-w-2xl px-4 py-8 sm:px-6 sm:py-14">
        <article
          className="relative overflow-hidden px-6 py-14 text-center sm:px-12 sm:py-20"
          style={{ background: th.paper, border: `1px solid ${th.paperEdge}`, boxShadow: "0 40px 80px -50px rgba(0,0,0,.45)" }}
        >
          <CornerVine className="pointer-events-none absolute -left-1 -top-1 h-24 w-24 opacity-30" />
          <CornerVine className="pointer-events-none absolute -bottom-1 -right-1 h-24 w-24 rotate-180 opacity-30" />

          {done && (
            <>
              <div className="rise" style={{ animationDelay: "0.05s", color: th.accent }}>
                <Sprig className="mx-auto h-5 w-32 opacity-80" />
              </div>

              {s.family && invite.families && (
                <div
                  className="rise mt-7 text-[0.68rem] uppercase tracking-[0.24em]"
                  style={{ color: th.soft, animationDelay: "0.1s" }}
                >
                  {invite.families.bride} &nbsp;·&nbsp; {invite.families.groom}
                </div>
              )}

              <div
                className="rise mt-6 text-[0.6rem] uppercase tracking-[0.36em]"
                style={{ color: th.soft, animationDelay: "0.15s" }}
              >
                {title}
              </div>

              <h1 className="rise mt-6 flex flex-col items-center" style={{ animationDelay: "0.3s" }}>
                <span className="font-display text-4xl font-light leading-tight sm:text-6xl">{invite.bride}</span>
                <span className="font-display my-2 text-2xl italic sm:text-3xl" style={{ color: th.accent }}>
                  &amp;
                </span>
                <span className="font-display text-4xl font-light leading-tight sm:text-6xl">{invite.groom}</span>
              </h1>

              <div className="rise mt-8" style={{ color: th.accent, animationDelay: "0.5s" }}>
                <Divider className="mx-auto h-4 w-52" />
              </div>

              {/* Termine – eine oder zwei Feiern */}
              <div className="rise mt-9 space-y-8" style={{ animationDelay: "0.6s" }}>
                {invite.events.map((ev, i) => {
                  const d = dateBlocks(ev.date, locale);
                  return (
                    <div key={i}>
                      {invite.events.length > 1 && ev.name && (
                        <div className="text-[0.62rem] uppercase tracking-[0.3em]" style={{ color: th.accent }}>
                          {ev.name}
                        </div>
                      )}
                      <div className="mt-3 flex items-center justify-center gap-5 sm:gap-8">
                        <div className="text-[0.6rem] uppercase tracking-[0.2em]" style={{ color: th.soft }}>
                          {d.weekday}
                        </div>
                        <div className="flex items-center gap-4">
                          <span className="h-8 w-px" style={{ background: th.accentSoft }} />
                          <span className="font-display text-4xl font-light sm:text-5xl">{d.day}</span>
                          <span className="h-8 w-px" style={{ background: th.accentSoft }} />
                        </div>
                        <div className="text-[0.6rem] uppercase tracking-[0.2em]" style={{ color: th.soft }}>
                          {d.month} {d.year}
                        </div>
                      </div>
                      {ev.time && (
                        <div className="mt-2 text-sm tracking-[0.2em]" style={{ color: th.soft }}>
                          {ev.time}
                        </div>
                      )}
                    </div>
                  );
                })}
              </div>

              {s.countdown && main.date && (
                <div className="rise mt-10" style={{ animationDelay: "0.8s" }}>
                  <Countdown date={main.date} time={main.time} locale={locale} />
                </div>
              )}

              {hero && (
                <div className="rise mt-12" style={{ animationDelay: "0.9s" }}>
                  <div className="relative mx-auto overflow-hidden" style={{ border: `1px solid ${th.paperEdge}` }}>
                    {/* eslint-disable-next-line @next/next/no-img-element -- vom Paar hochgeladenes Bild (Data-URL) */}
                    <img src={hero} alt="" className="block h-auto w-full object-cover" />
                  </div>
                </div>
              )}

              {invite.message && (
                <div className="rise mt-12" style={{ animationDelay: "1s" }}>
                  <p className="font-display mx-auto max-w-lg whitespace-pre-line text-lg font-light leading-relaxed sm:text-xl">
                    {invite.message}
                  </p>
                </div>
              )}

              {s.program && invite.program?.length > 0 && (
                <div className="rise mt-14" style={{ animationDelay: "1.05s" }}>
                  <h2 className="text-[0.6rem] uppercase tracking-[0.3em]" style={{ color: th.accent }}>
                    {t.program}
                  </h2>
                  <ul className="mx-auto mt-6 max-w-xs space-y-4">
                    {invite.program.map((p, i) => (
                      <li key={i} className="flex items-baseline justify-between gap-4">
                        <span className="font-display text-lg">{p.time}</span>
                        <span className="h-px flex-1" style={{ background: th.accentSoft, opacity: 0.6 }} />
                        <span className="text-[0.85rem]" style={{ color: th.soft }}>
                          {p.title}
                        </span>
                      </li>
                    ))}
                  </ul>
                </div>
              )}

              {s.menu && invite.menu?.length > 0 && (
                <div className="rise mt-14" style={{ animationDelay: "1.08s" }}>
                  <h2 className="text-[0.6rem] uppercase tracking-[0.3em]" style={{ color: th.accent }}>
                    {t.menuTitle}
                  </h2>
                  <ul className="mx-auto mt-6 max-w-xs space-y-3">
                    {invite.menu.map((m, i) => (
                      <li key={i} className="font-display text-lg font-light">
                        {m}
                      </li>
                    ))}
                  </ul>
                </div>
              )}

              {s.location && (
                <div className="rise mt-14 space-y-10" style={{ animationDelay: "1.1s" }}>
                  {invite.events.map((ev, i) =>
                    ev.venue ? (
                      <div key={i}>
                        <h2 className="text-[0.6rem] uppercase tracking-[0.3em]" style={{ color: th.accent }}>
                          {invite.events.length > 1 && ev.name ? ev.name : t.venue}
                        </h2>
                        <p className="font-display mt-4 text-2xl font-light sm:text-3xl">{ev.venue}</p>
                        {ev.address && (
                          <p className="mt-2 text-sm" style={{ color: th.soft }}>
                            {ev.address}
                          </p>
                        )}
                        <a
                          href={mapsUrl(ev.address || ev.venue)}
                          target="_blank"
                          rel="noopener noreferrer"
                          className="mt-5 inline-block border px-7 py-3 text-[0.64rem] uppercase tracking-[0.24em] transition-opacity hover:opacity-70"
                          style={{ borderColor: th.accent, color: th.accent }}
                        >
                          {t.directions}
                        </a>
                      </div>
                    ) : null
                  )}
                </div>
              )}

              {rest.length > 0 && (
                <div className="rise mt-14 grid grid-cols-2 gap-3" style={{ animationDelay: "1.15s" }}>
                  {rest.map((src, i) => (
                    <div key={i} className="overflow-hidden" style={{ border: `1px solid ${th.paperEdge}` }}>
                      {/* eslint-disable-next-line @next/next/no-img-element -- vom Paar hochgeladenes Bild (Data-URL) */}
                      <img src={src} alt="" className="block h-full w-full object-cover" />
                    </div>
                  ))}
                </div>
              )}

              {s.rsvp && (
                <div className="rise mt-16 text-left" style={{ animationDelay: "1.2s" }}>
                  <div className="text-center">
                    <h2 className="font-display text-2xl font-light sm:text-3xl">{t.rsvpTitle}</h2>
                    <p className="mx-auto mt-3 max-w-sm text-sm leading-relaxed" style={{ color: th.soft }}>
                      {t.rsvpText}
                    </p>
                  </div>
                  <RsvpForm
                    slug={invite.slug}
                    locale={locale}
                    theme={{ fg: th.fg, accent: th.accent, soft: th.soft, frame: th.paperEdge }}
                    disabled={preview}
                  />
                </div>
              )}

              {invite.closing && (
                <p
                  className="rise font-display mt-14 whitespace-pre-line text-lg font-light leading-relaxed"
                  style={{ animationDelay: "1.22s" }}
                >
                  {invite.closing}
                </p>
              )}

              {invite.hashtag && (
                <div className="rise mt-10 font-display text-xl" style={{ color: th.accent, animationDelay: "1.25s" }}>
                  {invite.hashtag}
                </div>
              )}

              <div className="rise mt-12 flex flex-col items-center gap-6" style={{ animationDelay: "1.3s" }}>
                <a
                  href={`https://wa.me/?text=${waText}`}
                  target="_blank"
                  rel="noopener noreferrer"
                  className="inline-flex items-center gap-3 border px-7 py-3 text-[0.64rem] uppercase tracking-[0.24em] transition-opacity hover:opacity-70"
                  style={{ borderColor: th.paperEdge, color: th.soft }}
                >
                  <svg width="15" height="15" viewBox="0 0 24 24" fill="currentColor">
                    <path d="M12 2a10 10 0 0 0-8.5 15.3L2 22l4.9-1.4A10 10 0 1 0 12 2zm5.5 14.2c-.2.6-1.2 1.2-1.7 1.2-.4 0-1 .1-3-.8a11 11 0 0 1-4.5-4c-.3-.5-1-1.5-1-2.9s.7-2 1-2.3c.2-.2.5-.3.7-.3h.5c.2 0 .4 0 .6.5l.8 2c.1.2.1.4 0 .5l-.3.5-.4.4c-.1.1-.3.3-.1.6.2.3.7 1.2 1.5 2 1 .8 1.8 1.1 2.1 1.2.3.1.5.1.6-.1l.8-1c.2-.2.4-.2.6-.1l1.9.9c.2.1.4.2.4.3.1.1.1.5-.1 1z" />
                  </svg>
                  {t.share}
                </a>
                <Sprig className="h-4 w-24 rotate-180 opacity-50" />
              </div>
            </>
          )}
        </article>

        <div className="mt-8 text-center text-[0.58rem] uppercase tracking-[0.26em]" style={{ color: th.soft }}>
          Atelier Lumière · {t.title}
        </div>
      </div>

      {/* Musik-Schalter */}
      {done && s.music && invite.musicUrl && (
        <button
          onClick={toggleMusic}
          aria-label={t.music}
          className="fixed bottom-5 right-5 z-50 flex h-12 w-12 items-center justify-center rounded-full shadow-lg transition-transform hover:scale-105"
          style={{ background: th.paper, border: `1px solid ${th.paperEdge}`, color: th.accent }}
        >
          {playing ? (
            <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor">
              <rect x="6" y="5" width="4" height="14" rx="1" />
              <rect x="14" y="5" width="4" height="14" rx="1" />
            </svg>
          ) : (
            <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor">
              <path d="M8 5v14l11-7z" />
            </svg>
          )}
        </button>
      )}
    </div>
  );
}
