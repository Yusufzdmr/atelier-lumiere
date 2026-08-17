"use client";

import { useEffect, useRef, useState, type CSSProperties, type ElementType, type ReactNode } from "react";

type Variant = "up" | "zoom" | "mask" | "line";

const cls: Record<Variant, string> = {
  up: "iv",
  zoom: "iv iv-zoom",
  mask: "iv iv-mask",
  line: "iv iv-line",
};

/**
 * Abschnitt der Einladung, der erst startet, wenn der Gast ihn erreicht.
 *
 * Vorher lag auf jedem Block ein fester animation-delay. Auf dem Handy war
 * die halbe Einladung damit schon durchgelaufen, bevor man ueberhaupt
 * hingescrollt hatte – unten kam der Inhalt dann ohne Bewegung an.
 */
export default function Rise({
  children,
  variant = "up",
  delay = 0,
  className = "",
  style,
  as: Tag = "div",
}: {
  children: ReactNode;
  variant?: Variant;
  /** Sekunden Versatz – staffelt Geschwister innerhalb eines Blocks. */
  delay?: number;
  className?: string;
  style?: CSSProperties;
  as?: ElementType;
}) {
  const ref = useRef<HTMLElement>(null);
  const [visible, setVisible] = useState(false);

  useEffect(() => {
    const el = ref.current;
    if (!el) return;
    const io = new IntersectionObserver(
      ([entry]) => {
        if (!entry.isIntersecting) return;
        setVisible(true);
        io.disconnect();
      },
      { threshold: 0.1, rootMargin: "0px 0px -60px 0px" }
    );
    io.observe(el);
    return () => io.disconnect();
  }, []);

  return (
    <Tag
      ref={ref}
      data-visible={visible}
      className={`${cls[variant]} ${className}`}
      style={{ ...style, ["--iv-delay" as string]: `${delay}s` }}
    >
      {children}
    </Tag>
  );
}
