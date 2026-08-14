"use client";

export default function ConsentReopen() {
  return (
    <button
      onClick={() => window.dispatchEvent(new Event("al:open-consent"))}
      className="my-2 border border-ink px-6 py-3 text-[0.68rem] uppercase tracking-[0.2em] text-ink transition-colors hover:bg-ink hover:text-cream"
    >
      Cookie-Einstellungen ändern
    </button>
  );
}
