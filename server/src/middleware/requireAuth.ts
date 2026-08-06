import type { NextFunction, Request, Response } from "express";
import { env } from "../lib/env";
import { verifyAuthToken } from "../lib/jwt";
import { prisma } from "../lib/prisma";

export interface AuthenticatedUser {
  id: string;
  name: string;
  email: string;
  role: "ADMIN" | "EDITOR";
}

declare global {
  namespace Express {
    interface Request {
      user?: AuthenticatedUser;
    }
  }
}

export async function requireAuth(req: Request, res: Response, next: NextFunction) {
  const token = req.cookies?.[env.AUTH_COOKIE_NAME];

  if (!token) {
    res.status(401).json({ error: "Belum login." });
    return;
  }

  try {
    const payload = verifyAuthToken(token);
    const user = await prisma.user.findUnique({ where: { id: payload.sub } });

    if (!user) {
      res.status(401).json({ error: "Sesi tidak valid." });
      return;
    }

    req.user = { id: user.id, name: user.name, email: user.email, role: user.role };
    next();
  } catch {
    res.status(401).json({ error: "Sesi tidak valid atau sudah kedaluwarsa." });
  }
}

/** Restrict a route to specific roles. Use after requireAuth. */
export function requireRole(...roles: AuthenticatedUser["role"][]) {
  return (req: Request, res: Response, next: NextFunction) => {
    if (!req.user || !roles.includes(req.user.role)) {
      res.status(403).json({ error: "Tidak punya akses untuk aksi ini." });
      return;
    }
    next();
  };
}
