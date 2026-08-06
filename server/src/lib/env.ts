import "dotenv/config";
import { z } from "zod";

const envSchema = z.object({
  DATABASE_URL: z.string().min(1),
  PORT: z.coerce.number().default(4000),
  NODE_ENV: z.enum(["development", "production", "test"]).default("development"),
  CLIENT_ORIGIN: z.string().min(1),
  // Origin Portal Cek Tagihan (Fase 9) — publik, tanpa cookie/kredensial, jadi
  // boleh dipisah dari CLIENT_ORIGIN (yang butuh CORS credentials:true untuk admin).
  CEK_CLIENT_ORIGIN: z.string().min(1).default("http://localhost:5174"),
  JWT_SECRET: z.string().min(16, "JWT_SECRET must be at least 16 characters"),
  JWT_EXPIRES_IN: z.string().default("7d"),
  AUTH_COOKIE_NAME: z.string().default("wonderland_admin_token"),

  // Opsional — kalau ketiganya diisi, upload gambar otomatis pakai Cloudinary.
  // Kalau kosong, fallback ke penyimpanan lokal di server/uploads (lihat lib/storage.ts).
  CLOUDINARY_CLOUD_NAME: z.string().optional(),
  CLOUDINARY_API_KEY: z.string().optional(),
  CLOUDINARY_API_SECRET: z.string().optional(),
});

const parsed = envSchema.safeParse(process.env);

if (!parsed.success) {
  console.error("Invalid environment variables:", parsed.error.flatten().fieldErrors);
  throw new Error("Invalid environment variables — check server/.env against .env.example");
}

export const env = parsed.data;
