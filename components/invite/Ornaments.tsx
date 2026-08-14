/** Dekor-Elemente der Einladung – reines SVG, keine externen Dateien. */

export function Sprig({ className = "", flip = false }: { className?: string; flip?: boolean }) {
  return (
    <svg
      viewBox="0 0 120 40"
      fill="none"
      stroke="currentColor"
      strokeWidth="0.9"
      strokeLinecap="round"
      className={className}
      style={flip ? { transform: "scaleX(-1)" } : undefined}
      aria-hidden
    >
      <path d="M4 20 C 30 20, 48 20, 116 20" />
      {[18, 32, 46, 60, 74, 88].map((x, i) => (
        <g key={x} opacity={0.85 - i * 0.05}>
          <path d={`M${x} 20 C ${x + 6} 12, ${x + 14} 9, ${x + 18} 12`} />
          <path d={`M${x} 20 C ${x + 6} 28, ${x + 14} 31, ${x + 18} 28`} />
        </g>
      ))}
      <circle cx="116" cy="20" r="1.6" fill="currentColor" stroke="none" />
    </svg>
  );
}

export function Divider({ className = "" }: { className?: string }) {
  return (
    <svg viewBox="0 0 200 16" fill="none" stroke="currentColor" strokeWidth="0.8" className={className} aria-hidden>
      <path d="M6 8 H 86" />
      <path d="M114 8 H 194" />
      <path d="M100 2 L 106 8 L 100 14 L 94 8 Z" fill="currentColor" stroke="none" />
      <circle cx="90" cy="8" r="1" fill="currentColor" stroke="none" />
      <circle cx="110" cy="8" r="1" fill="currentColor" stroke="none" />
    </svg>
  );
}

export function CornerVine({ className = "" }: { className?: string }) {
  return (
    <svg viewBox="0 0 90 90" fill="none" stroke="currentColor" strokeWidth="0.8" strokeLinecap="round" className={className} aria-hidden>
      <path d="M4 86 C 4 46, 26 14, 86 4" />
      {[
        [16, 62],
        [28, 44],
        [46, 26],
        [66, 14],
      ].map(([x, y], i) => (
        <g key={i}>
          <path d={`M${x} ${y} C ${x - 8} ${y - 6}, ${x - 12} ${y - 16}, ${x - 6} ${y - 20}`} />
          <path d={`M${x} ${y} C ${x + 8} ${y + 4}, ${x + 16} ${y + 6}, ${x + 20} ${y - 2}`} />
        </g>
      ))}
    </svg>
  );
}

/** Wachssiegel mit Monogramm */
export function WaxSeal({
  initials,
  color,
  textColor,
  size = 92,
  className = "",
}: {
  initials: string;
  color: string;
  textColor: string;
  size?: number;
  className?: string;
}) {
  return (
    <svg viewBox="0 0 100 100" width={size} height={size} className={className} aria-hidden>
      <defs>
        <radialGradient id="sealShine" cx="35%" cy="30%" r="75%">
          <stop offset="0%" stopColor="#ffffff" stopOpacity="0.45" />
          <stop offset="55%" stopColor="#ffffff" stopOpacity="0.05" />
          <stop offset="100%" stopColor="#000000" stopOpacity="0.22" />
        </radialGradient>
      </defs>
      <path
        d="M50 4c8 0 10 7 17 9s13-3 18 3-1 12 2 18 9 8 9 16-7 10-9 17 3 13-3 18-12-1-18 2-8 9-16 9-10-7-17-9-13 3-18-3 1-12-2-18-9-8-9-16 7-10 9-17-3-13 3-18 12 1 18-2 8-9 16-9z"
        fill={color}
      />
      <path
        d="M50 4c8 0 10 7 17 9s13-3 18 3-1 12 2 18 9 8 9 16-7 10-9 17 3 13-3 18-12-1-18 2-8 9-16 9-10-7-17-9-13 3-18-3 1-12-2-18-9-8-9-16 7-10 9-17-3-13 3-18 12 1 18-2 8-9 16-9z"
        fill="url(#sealShine)"
      />
      <circle cx="50" cy="50" r="31" fill="none" stroke={textColor} strokeOpacity="0.45" strokeWidth="0.9" />
      <text
        x="50"
        y="58"
        textAnchor="middle"
        fill={textColor}
        fontFamily="var(--font-display)"
        fontSize="26"
        letterSpacing="1.5"
      >
        {initials}
      </text>
    </svg>
  );
}
