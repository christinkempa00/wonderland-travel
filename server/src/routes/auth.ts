import { Router } from "express";
import bcrypt from "bcryptjs";
import { z } from "zod";
import { env } from "../lib/env";
import { signAuthToken } from "../lib/jwt";
import { prisma } from "../lib/prisma";
import { requireAuth } from "../middleware/requireAuth";

export const authRouter = Router();

const loginSchema = z.object({
  email: z.string().email("Email tidak valid."),
  password: z.string().min(1, "Password wajib diisi."),
});

const COOKIE_OPTIONS = {
  httpOnly: true,
  secure: env.NODE_ENV === "production",
  sameSite: "lax" as const,
  path: "/",
  maxAge: 7 * 24 * 60 * 60 * 1000, // 7 hari, selaras dengan JWT_EXPIRES_IN default
};

authRouter.post("/login", async (req, res) => {
  const parsed = loginSchema.safeParse(req.body);
  if (!parsed.success) {
    res.status(400).json({ error: parsed.error.issues[0]?.message ?? "Data tidak valid." });
    return;
  }

  const { email, password } = parsed.data;
  const user = await prisma.user.findUnique({ where: { email } });

  // Pesan error disamakan untuk email tidak ada maupun password salah,
  // supaya tidak membocorkan email mana yang terdaftar.
  const invalidMessage = "Email atau password salah.";

  if (!user) {
    res.status(401).json({ error: invalidMessage });
    return;
  }

  const passwordMatches = await bcrypt.compare(password, user.passwordHash);
  if (!passwordMatches) {
    res.status(401).json({ error: invalidMessage });
    return;
  }

  const token = signAuthToken({ sub: user.id, role: user.role });
  res.cookie(env.AUTH_COOKIE_NAME, token, COOKIE_OPTIONS);
  res.json({
    user: { id: user.id, name: user.name, email: user.email, role: user.role },
  });
});

authRouter.post("/logout", (_req, res) => {
  res.clearCookie(env.AUTH_COOKIE_NAME, { ...COOKIE_OPTIONS, maxAge: undefined });
  res.status(204).end();
});

authRouter.get("/me", requireAuth, (req, res) => {
  res.json({ user: req.user });
});
