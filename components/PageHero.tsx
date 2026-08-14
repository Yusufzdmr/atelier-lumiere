import { img, blurData } from "@/lib/images";
import Image from "next/image";

export default function PageHero({
  eyebrow,
  title,
  text,
  seed,
  height = "md",
}: {
  eyebrow?: string;
  title: string;
  text?: string;
  seed: string;
  height?: "md" | "lg";
}) {
  return (
    <section className={`relative ${height === "lg" ? "h-[68vh] min-h-[520px]" : "h-[52vh] min-h-[380px]"} w-full overflow-hidden`}>
      <Image
        src={img(seed, 1920, 1200)}
        alt={title}
        fill
        priority
        sizes="100vw"
        placeholder="blur"
        blurDataURL={blurData}
        className="object-cover"
      />
      <div className="absolute inset-0 bg-gradient-to-b from-ink/70 via-ink/45 to-ink/70" />
      <div className="relative z-10 mx-auto flex h-full max-w-7xl flex-col justify-end px-5 pb-14 sm:px-8 sm:pb-20">
        {eyebrow && <div className="eyebrow text-gold-soft">{eyebrow}</div>}
        <h1 className="headline mt-4 max-w-4xl text-4xl text-cream sm:text-5xl md:text-6xl">{title}</h1>
        {text && <p className="mt-5 max-w-2xl text-[0.98rem] leading-relaxed text-cream/75">{text}</p>}
      </div>
    </section>
  );
}
