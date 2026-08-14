import ConsentReopen from "@/components/ConsentReopen";
import { getContent } from "@/lib/cms";
import { site } from "@/lib/site";
import type { LegalPage } from "@/lib/legal";

/**
 * Rendert die im Admin gepflegten Rechtstexte.
 *
 * Bewusst winzige Auszeichnungssprache statt Markdown-Abhängigkeit:
 *   Leerzeile   -> neuer Absatz
 *   "- " Zeile  -> Aufzählungspunkt
 *   **fett** · `code` · [Text](URL)
 *   {{consent}} -> Button für die Cookie-Einstellungen
 */

type Vars = Record<string, string>;

/** Platzhalter aus den im Admin gepflegten Kontaktdaten füllen */
const fill = (text: string, vars: Vars) =>
  text.replace(/\{(\w+)\}/g, (m, key: string) => vars[key] ?? m);

const INLINE = /(\*\*[^*]+\*\*|`[^`]+`|\[[^\]]+\]\([^)]+\))/g;

/** **fett**, `code` und [Text](URL) innerhalb einer Zeile auflösen */
function inline(text: string, keyBase: string) {
  return text.split(INLINE).map((part, i) => {
    const key = `${keyBase}-${i}`;
    if (part.startsWith("**") && part.endsWith("**")) return <strong key={key}>{part.slice(2, -2)}</strong>;
    if (part.startsWith("`") && part.endsWith("`")) return <code key={key}>{part.slice(1, -1)}</code>;

    const link = /^\[([^\]]+)\]\(([^)]+)\)$/.exec(part);
    if (link) {
      const [, text_, href] = link;
      const external = /^https?:/.test(href);
      return (
        <a
          key={key}
          href={href}
          className="text-gold"
          {...(external ? { target: "_blank", rel: "noopener noreferrer" } : {})}
        >
          {text_}
        </a>
      );
    }
    return <span key={key}>{part}</span>;
  });
}

/** Einzelne Zeilen eines Absatzes durch <br /> trennen */
function paragraph(text: string, key: string) {
  const rows = text.split("\n");
  return (
    <p key={key}>
      {rows.map((row, i) => (
        <span key={i}>
          {i > 0 && <br />}
          {inline(row, `${key}-${i}`)}
        </span>
      ))}
    </p>
  );
}

function block(raw: string, key: string) {
  const text = raw.trim();
  if (!text) return null;
  if (text === "{{consent}}") return <ConsentReopen key={key} />;

  const rows = text.split("\n");
  if (rows.every((r) => r.startsWith("- "))) {
    return (
      <ul key={key}>
        {rows.map((r, i) => (
          <li key={i}>{inline(r.slice(2), `${key}-${i}`)}</li>
        ))}
      </ul>
    );
  }
  return paragraph(text, key);
}

const blocks = (body: string, key: string, vars: Vars) =>
  fill(body, vars)
    .split(/\n{2,}/)
    .map((b, i) => block(b, `${key}-${i}`));

export default async function LegalBody({ page }: { page: LegalPage }) {
  const { contact } = await getContent();
  const vars: Vars = {
    legalName: site.legalName,
    owner: site.owner,
    street: contact.street,
    zip: contact.zip,
    city: contact.city,
    email: contact.email,
    phone: contact.phoneHuman,
  };

  return (
    <>
      <h1 className="headline text-4xl">{page.title}</h1>

      <div className="prose-lux mt-10 max-w-2xl">
        {page.sections.map((s, i) => (
          <section key={i}>
            {s.heading && <h2>{s.heading}</h2>}
            {blocks(s.body, `s${i}`, vars)}
          </section>
        ))}

        {page.note && <div className="text-[0.8rem]">{blocks(page.note, "note", vars)}</div>}
      </div>
    </>
  );
}
