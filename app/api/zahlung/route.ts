import { NextResponse } from "next/server";
import { headers } from "next/headers";
import { getInvitation, addPayment } from "@/lib/store";
import { computeTotal } from "@/lib/pricing";
import { isConfigured, createOrder, captureOrder } from "@/lib/paypal";
import { site } from "@/lib/site";

/**
 * Zahlung der digitalen Einladung.
 * POST { slug }              -> Bestellung anlegen, Weiterleitungs-URL zurueckgeben
 * POST { slug, orderId }     -> Zahlung einziehen und Einladung freischalten
 */
export async function POST(req: Request) {
  try {
    const { slug, orderId } = await req.json();
    const inv = await getInvitation(String(slug ?? ""));
    if (!inv) return NextResponse.json({ error: "unknown-invitation" }, { status: 404 });

    if (inv.paid || inv.price === 0) {
      return NextResponse.json({ ok: true, paid: true, free: true });
    }

    if (!isConfigured()) {
      return NextResponse.json(
        { ok: false, configured: false, amount: inv.price },
        { status: 200 }
      );
    }

    if (orderId) {
      const result = await captureOrder(String(orderId));
      if (result.paid) {
        inv.paid = true;
        inv.paymentRef = String(orderId);
      }
      return NextResponse.json({ ok: result.paid, paid: result.paid, status: result.status });
    }

    const h = await headers();
    const host = h.get("host") ?? new URL(site.url).host;
    const proto = host.startsWith("localhost") ? "http" : "https";
    const base = `${proto}://${host}/${inv.locale}/einladung/${inv.slug}`;

    // Betrag immer serverseitig neu berechnen
    const amount = computeTotal(inv.sections, inv.events.length > 1, false);

    const order = await createOrder({
      amount,
      slug: inv.slug,
      description: `Digitale Einladung ${inv.bride} & ${inv.groom}`,
      returnUrl: `${base}?bezahlt=1`,
      cancelUrl: `${base}?abbruch=1`,
    });

    await addPayment({ slug: inv.slug, orderId: order.id, amount, at: new Date().toISOString() });

    return NextResponse.json({ ok: true, configured: true, orderId: order.id, approveUrl: order.approveUrl, amount });
  } catch {
    return NextResponse.json({ error: "payment-failed" }, { status: 500 });
  }
}
