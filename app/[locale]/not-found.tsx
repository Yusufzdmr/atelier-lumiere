import Link from "next/link";
import { Section } from "@/components/ui";

export default function NotFound() {
  return (
    <Section className="flex min-h-[70vh] items-center pt-36">
      <div className="mx-auto max-w-md text-center">
        <div className="eyebrow">404</div>
        <h1 className="headline mt-4 text-4xl">Seite nicht gefunden</h1>
        <p className="mt-4 text-sm leading-relaxed text-muted">
          Diese Seite gibt es nicht (mehr). / Bu sayfa bulunamadı.
        </p>
        <Link
          href="/de"
          className="mt-9 inline-block bg-ink px-7 py-3.5 text-[0.7rem] uppercase tracking-[0.2em] text-cream hover:bg-gold"
        >
          Zur Startseite
        </Link>
      </div>
    </Section>
  );
}
