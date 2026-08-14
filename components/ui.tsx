import Link from "next/link";
import Image from "next/image";
import type { ReactNode } from "react";
import { img, blurData } from "@/lib/images";
import Reveal from "./Reveal";

export function Section({
  children,
  className = "",
  id,
  tone = "cream",
}: {
  children: ReactNode;
  className?: string;
  id?: string;
  tone?: "cream" | "sand" | "ink";
}) {
  const tones = {
    cream: "bg-cream text-ink",
    sand: "bg-sand/50 text-ink",
    ink: "bg-ink text-cream",
  } as const;
  return (
    <section id={id} className={`${tones[tone]} px-5 py-20 sm:px-8 sm:py-28 ${className}`}>
      <div className="mx-auto max-w-7xl">{children}</div>
    </section>
  );
}

export function SectionHead({
  eyebrow,
  title,
  text,
  align = "left",
  tone = "dark",
}: {
  eyebrow?: string;
  title: string;
  text?: string;
  align?: "left" | "center";
  tone?: "dark" | "light";
}) {
  return (
    <Reveal className={`max-w-2xl ${align === "center" ? "mx-auto text-center" : ""}`}>
      {eyebrow && <div className="eyebrow">{eyebrow}</div>}
      <h2
        className={`headline mt-4 text-3xl sm:text-4xl md:text-5xl ${tone === "light" ? "text-cream" : "text-ink"}`}
        style={{ whiteSpace: "pre-line" }}
      >
        {title}
      </h2>
      {text && (
        <p className={`mt-5 text-[0.98rem] leading-relaxed ${tone === "light" ? "text-cream/65" : "text-muted"}`}>
          {text}
        </p>
      )}
    </Reveal>
  );
}

export function Btn({
  href,
  children,
  variant = "solid",
  className = "",
}: {
  href: string;
  children: ReactNode;
  variant?: "solid" | "outline" | "light" | "ghost";
  className?: string;
}) {
  const base =
    "inline-flex items-center justify-center px-7 py-3.5 text-[0.72rem] uppercase tracking-[0.2em] transition-all duration-300";
  const variants = {
    solid: "bg-ink text-cream hover:bg-gold",
    outline: "border border-ink text-ink hover:bg-ink hover:text-cream",
    light: "border border-cream/40 text-cream hover:bg-cream hover:text-ink",
    ghost: "text-gold hover:text-ink",
  } as const;
  return (
    <Link href={href} className={`${base} ${variants[variant]} ${className}`}>
      {children}
    </Link>
  );
}

export function Photo({
  seed,
  alt,
  ratio = "3/4",
  className = "",
  sizes = "(max-width: 768px) 100vw, 33vw",
  priority = false,
  w = 900,
  h = 1200,
  zoom = true,
}: {
  seed: string;
  alt: string;
  ratio?: string;
  className?: string;
  sizes?: string;
  priority?: boolean;
  w?: number;
  h?: number;
  zoom?: boolean;
}) {
  return (
    <div className={`relative overflow-hidden bg-sand ${className}`} style={{ aspectRatio: ratio }}>
      <Image
        src={img(seed, w, h)}
        alt={alt}
        fill
        sizes={sizes}
        priority={priority}
        placeholder="blur"
        blurDataURL={blurData}
        className={`object-cover transition-transform duration-[1200ms] ease-out ${zoom ? "hover:scale-105" : ""}`}
      />
    </div>
  );
}

export function Stat({ value, label, tone = "dark" }: { value: string; label: string; tone?: "dark" | "light" }) {
  return (
    <div>
      <div className={`font-display text-4xl font-light sm:text-5xl ${tone === "light" ? "text-cream" : "text-ink"}`}>
        {value}
      </div>
      <div className={`mt-2 text-[0.68rem] uppercase tracking-[0.2em] ${tone === "light" ? "text-cream/50" : "text-muted"}`}>
        {label}
      </div>
    </div>
  );
}

export function Hairline({ className = "" }: { className?: string }) {
  return <div className={`hairline my-14 ${className}`} />;
}

export function Accordion({ items }: { items: { q: string; a: string }[] }) {
  return (
    <div className="divide-y divide-sand-deep border-y border-sand-deep">
      {items.map((it, i) => (
        <details key={i} className="group">
          <summary className="flex cursor-pointer list-none items-center justify-between gap-6 py-5 text-left">
            <span className="font-display text-lg font-normal text-ink sm:text-xl">{it.q}</span>
            <span className="relative h-4 w-4 shrink-0">
              <span className="absolute left-0 top-1/2 h-px w-4 bg-gold" />
              <span className="absolute left-1/2 top-0 h-4 w-px bg-gold transition-transform duration-300 group-open:rotate-90 group-open:opacity-0" />
            </span>
          </summary>
          <p className="pb-6 pr-10 text-[0.95rem] leading-relaxed text-muted">{it.a}</p>
        </details>
      ))}
    </div>
  );
}

export function Breadcrumbs({ items }: { items: { name: string; href?: string }[] }) {
  return (
    <nav className="mb-8 flex flex-wrap items-center gap-2 text-[0.7rem] uppercase tracking-[0.16em] text-muted">
      {items.map((it, i) => (
        <span key={i} className="flex items-center gap-2">
          {it.href ? (
            <Link href={it.href} className="hover:text-gold">
              {it.name}
            </Link>
          ) : (
            <span className="text-ink">{it.name}</span>
          )}
          {i < items.length - 1 && <span className="text-sand-deep">/</span>}
        </span>
      ))}
    </nav>
  );
}
