import crypto from "node:crypto";
import jwt from "jsonwebtoken";
import { env } from "./env";

/**
 * Captcha matematika sederhana & stateless: soal ditandatangani (JWT, 5 menit) dan
 * dikirim balik ke klien bersama pertanyaannya, lalu diverifikasi ulang saat submit —
 * tidak perlu penyimpanan sesi di server. Cukup untuk menahan bot kasar/naif; kalau nanti
 * butuh proteksi lebih kuat, ganti dengan layanan pihak ketiga (hCaptcha/reCAPTCHA).
 */

export interface CaptchaChallenge {
  question: string;
  token: string;
}

interface CaptchaPayload {
  answer: number;
}

export function generateCaptcha(): CaptchaChallenge {
  const a = crypto.randomInt(1, 10);
  const b = crypto.randomInt(1, 10);
  const token = jwt.sign({ answer: a + b } satisfies CaptchaPayload, env.JWT_SECRET, {
    expiresIn: "5m",
  });

  return { question: `${a} + ${b}`, token };
}

export function verifyCaptcha(token: unknown, answer: unknown): boolean {
  if (typeof token !== "string" || answer === undefined || answer === null || answer === "") {
    return false;
  }

  try {
    const payload = jwt.verify(token, env.JWT_SECRET) as CaptchaPayload;
    return payload.answer === Number(answer);
  } catch {
    return false;
  }
}
