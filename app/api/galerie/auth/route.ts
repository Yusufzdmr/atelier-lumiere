import { NextResponse } from "next/server";
import { authGallery } from "@/lib/store";

export async function POST(req: Request) {
  try {
    const { code, password } = await req.json();
    const gallery = await authGallery(String(code ?? ""), String(password ?? ""));
    if (!gallery) return NextResponse.json({ error: "invalid" }, { status: 401 });

    const res = NextResponse.json({ ok: true, code: gallery.code });
    res.cookies.set(`al-gal-${gallery.code}`, "1", {
      httpOnly: true,
      sameSite: "lax",
      secure: process.env.NODE_ENV === "production",
      path: "/",
      maxAge: 60 * 60 * 24 * 30,
    });
    return res;
  } catch {
    return NextResponse.json({ error: "bad-request" }, { status: 400 });
  }
}
