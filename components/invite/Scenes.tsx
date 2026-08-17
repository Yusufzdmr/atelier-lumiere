/**
 * Hintergrundkunst der Einladungs-Themes.
 *
 * Alles ist gezeichnetes SVG – keine Fotodateien. Das hat zwei Gruende:
 * die Einladung bleibt auf dem Handy leicht, und die Motive nehmen die
 * Farben des gewaehlten Themes an, statt gegen sie zu arbeiten.
 * Wer ein eigenes Bild hochlaedt, legt es darueber (siehe <Backdrop>).
 */

import type { InviteTheme, SceneId } from "@/lib/themes";

/* ------------------------------------------------------------------ */
/* Bausteine                                                           */
/* ------------------------------------------------------------------ */

/** Blaetterzweig: ein geschwungener Stiel mit paarweise sitzenden Blaettern. */
function LeafSpray({
  leaves = 7,
  curve = "M4 96 C 20 66, 44 34, 96 8",
  color,
  fill,
  opacity = 1,
}: {
  leaves?: number;
  curve?: string;
  color: string;
  fill?: string;
  opacity?: number;
}) {
  // Die Blaetter sitzen auf gedachten Punkten entlang des Stiels. Die Werte
  // sind auf die Standardkurve abgestimmt und wandern mit ihr grob mit.
  const stops = Array.from({ length: leaves }, (_, i) => {
    const t = (i + 1) / (leaves + 1);
    return { x: 4 + t * 92, y: 96 - Math.pow(t, 0.72) * 88, s: 1 - t * 0.42 };
  });

  return (
    <g opacity={opacity}>
      <path d={curve} fill="none" stroke={color} strokeWidth="1.1" strokeLinecap="round" />
      {stops.map((p, i) => (
        <g key={i} transform={`translate(${p.x} ${p.y}) scale(${p.s})`}>
          {/* Drehpunkt mitgeben: rotate() dreht sonst um den Ursprung und die
              Blaetter wandern auf einer Kreisbahn vom Stiel weg. */}
          <ellipse cx="9" cy="-7" rx="9" ry="4.2" fill={fill ?? "none"} stroke={color} strokeWidth="1" transform="rotate(-28 9 -7)" />
          <ellipse cx="9" cy="7" rx="9" ry="4.2" fill={fill ?? "none"} stroke={color} strokeWidth="1" transform="rotate(28 9 7)" />
        </g>
      ))}
    </g>
  );
}

/** Bluete: ineinanderliegende Blaetterkraenze, von aussen nach innen kleiner. */
function Blossom({
  x,
  y,
  r = 16,
  petals = 7,
  color,
  core,
  opacity = 1,
}: {
  x: number;
  y: number;
  r?: number;
  petals?: number;
  color: string;
  core: string;
  opacity?: number;
}) {
  const ring = (radius: number, count: number, turn: number, o: number) =>
    Array.from({ length: count }, (_, i) => {
      const a = (i / count) * 360 + turn;
      return (
        <ellipse
          key={`${radius}-${i}`}
          cx="0"
          cy={-radius * 0.55}
          rx={radius * 0.42}
          ry={radius * 0.58}
          fill={color}
          opacity={o}
          transform={`rotate(${a})`}
        />
      );
    });

  return (
    <g transform={`translate(${x} ${y})`} opacity={opacity}>
      {ring(r, petals, 0, 0.62)}
      {ring(r * 0.66, petals, 26, 0.8)}
      {ring(r * 0.38, petals - 2, 12, 0.95)}
      <circle r={r * 0.16} fill={core} opacity="0.75" />
    </g>
  );
}

/** Pampasgras: viele feine Halme, die sich nach oben auffaechern. */
function Plume({ x, y, h = 78, color, opacity = 1 }: { x: number; y: number; h?: number; color: string; opacity?: number }) {
  return (
    <g transform={`translate(${x} ${y})`} opacity={opacity}>
      <path d={`M0 0 C -2 ${-h * 0.5}, 1 ${-h * 0.8}, 0 ${-h}`} fill="none" stroke={color} strokeWidth="1.2" />
      {Array.from({ length: 26 }, (_, i) => {
        const t = i / 25;
        const yy = -h * (0.28 + t * 0.7);
        const side = i % 2 ? 1 : -1;
        const len = 15 * (1 - Math.abs(t - 0.45) * 1.1);
        return (
          <path
            key={i}
            d={`M0 ${yy} Q ${side * len * 0.6} ${yy - 5}, ${side * len} ${yy - 12}`}
            fill="none"
            stroke={color}
            strokeWidth="0.8"
            strokeLinecap="round"
            opacity={0.5 + t * 0.4}
          />
        );
      })}
    </g>
  );
}

/** Art-déco-Faecher: konzentrische Viertelboegen mit Strahlen. */
function DecoFan({ color, opacity = 1 }: { color: string; opacity?: number }) {
  return (
    <g opacity={opacity} fill="none" stroke={color}>
      {[26, 42, 58, 74, 90].map((r, i) => (
        <path key={r} d={`M0 ${r} A ${r} ${r} 0 0 0 ${r} 0`} strokeWidth={i % 2 ? 0.6 : 1} />
      ))}
      {[15, 30, 45, 60, 75].map((a) => (
        <line key={a} x1="0" y1="0" x2={Math.cos((a * Math.PI) / 180) * 92} y2={Math.sin((a * Math.PI) / 180) * 92} strokeWidth="0.5" opacity="0.6" />
      ))}
      <circle cx="0" cy="0" r="4" fill={color} stroke="none" />
    </g>
  );
}

/** Spitzenbogen: gleichmaessige Halbkreise mit Perlen darunter. */
function LaceEdge({ color, count = 14, width = 400 }: { color: string; count?: number; width?: number }) {
  const step = width / count;
  return (
    <g fill="none" stroke={color}>
      {Array.from({ length: count }, (_, i) => (
        <g key={i}>
          <path d={`M${i * step} 0 A ${step / 2} ${step / 2} 0 0 0 ${(i + 1) * step} 0`} strokeWidth="0.9" />
          <circle cx={i * step + step / 2} cy={step * 0.62} r="1.5" fill={color} stroke="none" opacity="0.7" />
        </g>
      ))}
    </g>
  );
}

/* ------------------------------------------------------------------ */
/* Szenen                                                              */
/* ------------------------------------------------------------------ */

type Piece = { className: string; style: React.CSSProperties; svg: React.ReactNode; box: string };

function pieces(id: SceneId, th: InviteTheme): Piece[] {
  const a = th.accent;
  const s = th.accentSoft;

  switch (id) {
    case "botanical":
      return [
        {
          box: "0 0 100 100",
          className: "left-0 top-0 w-[38vw] max-w-[240px]",
          style: { ["--from-x" as string]: "-14%", ["--from-r" as string]: "-8deg", animationDelay: ".2s" },
          svg: (
            <g transform="scale(-1,1) translate(-100,0)">
              <LeafSpray color={a} opacity={0.55} />
              <LeafSpray color={s} leaves={5} curve="M14 98 C 34 74, 52 50, 82 30" opacity={0.4} />
            </g>
          ),
        },
        {
          box: "0 0 100 100",
          className: "right-0 top-0 w-[38vw] max-w-[240px]",
          style: { ["--from-x" as string]: "14%", ["--from-r" as string]: "8deg", animationDelay: ".35s" },
          svg: (
            <>
              <LeafSpray color={a} opacity={0.55} />
              <LeafSpray color={s} leaves={5} curve="M14 98 C 34 74, 52 50, 82 30" opacity={0.4} />
            </>
          ),
        },
        {
          box: "0 0 100 100",
          className: "bottom-0 left-0 w-[30vw] max-w-[190px] rotate-180",
          style: { ["--from-y" as string]: "12%", animationDelay: ".5s" },
          svg: <LeafSpray color={s} leaves={5} opacity={0.32} />,
        },
      ];

    case "leafy":
      return [
        {
          box: "0 0 100 100",
          className: "left-0 top-[6%] w-[42vw] max-w-[280px]",
          style: { ["--from-x" as string]: "-16%", animationDelay: ".2s" },
          svg: (
            <g transform="scale(-1,1) translate(-100,0)">
              <LeafSpray color={a} fill={s} leaves={9} opacity={0.5} />
            </g>
          ),
        },
        {
          box: "0 0 100 100",
          className: "bottom-[4%] right-0 w-[36vw] max-w-[230px] rotate-180",
          style: { ["--from-x" as string]: "16%", animationDelay: ".4s" },
          svg: <LeafSpray color={a} fill={s} leaves={8} opacity={0.42} />,
        },
        {
          box: "0 0 100 100",
          className: "right-[4%] top-[30%] w-[22vw] max-w-[130px]",
          style: { ["--from-y" as string]: "-10%", animationDelay: ".6s" },
          svg: <LeafSpray color={s} leaves={5} opacity={0.26} />,
        },
      ];

    case "bouquet":
      return [
        {
          box: "0 0 140 140",
          className: "left-0 top-0 w-[46vw] max-w-[290px]",
          style: { ["--from-x" as string]: "-14%", ["--from-y" as string]: "-12%", animationDelay: ".2s" },
          svg: (
            <>
              <LeafSpray color={s} leaves={6} curve="M6 130 C 30 96, 58 62, 118 30" opacity={0.5} />
              <LeafSpray color={s} leaves={5} curve="M4 118 C 34 108, 62 92, 96 60" opacity={0.35} />
              <Blossom x={44} y={62} r={20} color={a} core={s} opacity={0.5} />
              <Blossom x={78} y={38} r={15} color={s} core={a} opacity={0.55} />
              <Blossom x={26} y={96} r={12} color={a} core={s} opacity={0.42} />
            </>
          ),
        },
        {
          box: "0 0 140 140",
          className: "bottom-0 right-0 w-[40vw] max-w-[250px] rotate-180",
          style: { ["--from-x" as string]: "14%", ["--from-y" as string]: "12%", animationDelay: ".4s" },
          svg: (
            <>
              <LeafSpray color={s} leaves={6} curve="M6 130 C 30 96, 58 62, 118 30" opacity={0.45} />
              <Blossom x={48} y={58} r={18} color={a} core={s} opacity={0.45} />
              <Blossom x={80} y={34} r={12} color={s} core={a} opacity={0.5} />
            </>
          ),
        },
      ];

    case "deco":
      return [
        {
          box: "-4 -4 100 100",
          className: "left-0 top-0 w-[34vw] max-w-[200px]",
          style: { ["--from-x" as string]: "-10%", animationDelay: ".2s" },
          svg: <DecoFan color={a} opacity={0.55} />,
        },
        {
          box: "-4 -4 100 100",
          className: "right-0 top-0 w-[34vw] max-w-[200px] -scale-x-100",
          style: { ["--from-x" as string]: "10%", animationDelay: ".3s" },
          svg: <DecoFan color={a} opacity={0.55} />,
        },
        {
          box: "-4 -4 100 100",
          className: "bottom-0 left-0 w-[28vw] max-w-[160px] -scale-y-100",
          style: { ["--from-y" as string]: "10%", animationDelay: ".45s" },
          svg: <DecoFan color={s} opacity={0.4} />,
        },
        {
          box: "-4 -4 100 100",
          className: "bottom-0 right-0 w-[28vw] max-w-[160px] -scale-100",
          style: { ["--from-y" as string]: "10%", animationDelay: ".55s" },
          svg: <DecoFan color={s} opacity={0.4} />,
        },
      ];

    case "lace":
      return [
        {
          box: "0 0 400 40",
          className: "left-0 right-0 top-0 w-full",
          style: { ["--from-y" as string]: "-16%", animationDelay: ".2s" },
          svg: <LaceEdge color={a} count={16} />,
        },
        {
          box: "0 0 400 40",
          className: "bottom-0 left-0 right-0 w-full rotate-180",
          style: { ["--from-y" as string]: "16%", animationDelay: ".35s" },
          svg: <LaceEdge color={s} count={16} />,
        },
        {
          box: "0 0 100 100",
          className: "left-0 top-[18%] w-[20vw] max-w-[120px]",
          style: { ["--from-x" as string]: "-12%", animationDelay: ".5s" },
          svg: <LeafSpray color={s} leaves={4} opacity={0.28} />,
        },
      ];

    case "pampas":
      return [
        {
          box: "0 0 120 120",
          className: "bottom-0 left-0 w-[38vw] max-w-[240px]",
          style: { ["--from-y" as string]: "14%", animationDelay: ".2s" },
          svg: (
            <>
              <Plume x={34} y={120} h={92} color={a} opacity={0.5} />
              <Plume x={56} y={120} h={70} color={s} opacity={0.55} />
              <Plume x={16} y={120} h={58} color={s} opacity={0.4} />
            </>
          ),
        },
        {
          box: "0 0 120 120",
          className: "bottom-0 right-0 w-[32vw] max-w-[200px] -scale-x-100",
          style: { ["--from-y" as string]: "14%", animationDelay: ".4s" },
          svg: (
            <>
              <Plume x={40} y={120} h={80} color={a} opacity={0.42} />
              <Plume x={62} y={120} h={58} color={s} opacity={0.45} />
            </>
          ),
        },
        {
          box: "0 0 100 100",
          className: "right-[2%] top-0 w-[26vw] max-w-[150px] rotate-180",
          style: { ["--from-y" as string]: "-12%", animationDelay: ".55s" },
          svg: <LeafSpray color={s} leaves={6} opacity={0.3} />,
        },
      ];
  }
}

/** Zwei weiche Farbwolken hinter der Karte – die Aquarell-Anmutung. */
function Wash({ th }: { th: InviteTheme }) {
  return (
    <>
      <span
        className="scene-wash"
        style={{ background: th.accentSoft, opacity: 0.3, width: "58vw", height: "58vw", maxWidth: 520, maxHeight: 520, left: "-16%", top: "-10%" }}
      />
      <span
        className="scene-wash"
        style={{
          background: th.petal,
          opacity: 0.34,
          width: "52vw",
          height: "52vw",
          maxWidth: 460,
          maxHeight: 460,
          right: "-14%",
          bottom: "-8%",
          animationDelay: "-9s",
        }}
      />
    </>
  );
}

/**
 * Vollflaechige Szene hinter der Karte.
 * `still` schaltet die Bewegung ab – dafuer gibt es die Vorschau im Assistenten,
 * die sonst dauerhaft im Blickfeld wackeln wuerde.
 */
export function Scene({ id, theme, still = false }: { id: SceneId; theme: InviteTheme; still?: boolean }) {
  return (
    <div className="scene" aria-hidden>
      <Wash th={theme} />
      {pieces(id, theme).map((p, i) => (
        <svg
          key={i}
          viewBox={p.box}
          className={`${still ? "absolute" : "scene-corner"} ${p.className}`}
          style={still ? undefined : p.style}
          preserveAspectRatio="xMidYMid meet"
        >
          {p.svg}
        </svg>
      ))}
    </div>
  );
}

/**
 * Eigenes Bild des Paares als Hintergrund.
 * Liegt ueber der Szene, nimmt ihr aber nicht die Lesbarkeit: der Verlauf
 * darueber haelt das Papier hell genug fuer die Schrift.
 */
export function Backdrop({ src, theme }: { src: string; theme: InviteTheme }) {
  return (
    <div className="scene" aria-hidden>
      {/* eslint-disable-next-line @next/next/no-img-element -- vom Paar hochgeladenes Bild (Data-/Blob-URL) */}
      <img src={src} alt="" className="h-full w-full object-cover animate-kenburns" />
      <span
        className="absolute inset-0"
        style={{ background: `linear-gradient(to bottom, ${theme.bg}D9, ${theme.bg}A6 45%, ${theme.bg}E6)` }}
      />
    </div>
  );
}
