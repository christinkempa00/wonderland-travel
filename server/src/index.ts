import path from "node:path";
import cookieParser from "cookie-parser";
import cors from "cors";
import express from "express";
import multer from "multer";
import { env } from "./lib/env";
import { UPLOAD_DIR } from "./lib/storage";
import { requireAuth } from "./middleware/requireAuth";
import { authRouter } from "./routes/auth";
import { cekTagihanRouter } from "./routes/cekTagihan";
import { invoiceRouter } from "./routes/invoice";
import { laporanRouter } from "./routes/laporan";
import { notifikasiRouter } from "./routes/notifikasi";
import { paketWisataRouter } from "./routes/paketWisata";
import { pelangganRouter } from "./routes/pelanggan";
import { reservasiRouter } from "./routes/reservasi";

const app = express();

const allowedOrigins = [
  ...env.CLIENT_ORIGIN.split(","),
  ...env.CEK_CLIENT_ORIGIN.split(","),
].map((origin) => origin.trim());

app.use(
  cors({
    origin: allowedOrigins,
    credentials: true,
  }),
);
app.use(cookieParser());
app.use(express.json());

// Dipakai saat mode penyimpanan lokal (lihat lib/storage.ts). Tidak berpengaruh kalau
// upload sedang memakai Cloudinary — folder ini tetap ada tapi kosong.
app.use("/uploads", express.static(UPLOAD_DIR));

app.get("/api/health", (_req, res) => {
  res.json({ ok: true });
});

app.use("/api/auth", authRouter);

// Semua route CMS/Reservasi/Invoice dipasang di sini, masing-masing router
// memasang requireAuth sendiri di baris pertamanya (lihat routes/paketWisata.ts).
app.get("/api/admin/ping", requireAuth, (req, res) => {
  res.json({ ok: true, user: req.user });
});
app.use("/api/admin/paket-wisata", paketWisataRouter);
app.use("/api/admin/pelanggan", pelangganRouter);
app.use("/api/admin/reservasi", reservasiRouter);
app.use("/api/admin/invoice", invoiceRouter);
app.use("/api/admin/laporan", laporanRouter);
app.use("/api/admin/notifikasi", notifikasiRouter);

// Portal Cek Tagihan (Fase 9) — publik, read-only, tanpa login. Rate-limited &
// dilindungi captcha di dalam routes/cekTagihan.ts sendiri.
app.use("/api/cek-tagihan", cekTagihanRouter);

// Di production, server ini juga menyajikan build statis admin (admin/dist),
// supaya satu Node.js App di hPanel Hostinger cukup untuk API + panel admin.
if (env.NODE_ENV === "production") {
  const adminDist = path.resolve(__dirname, "../../admin/dist");
  app.use(express.static(adminDist));
  app.get("*", (_req, res) => {
    res.sendFile(path.join(adminDist, "index.html"));
  });
}

app.use((err: unknown, _req: express.Request, res: express.Response, _next: express.NextFunction) => {
  if (err instanceof multer.MulterError) {
    const message =
      err.code === "LIMIT_FILE_SIZE" ? "Ukuran file maksimal 5MB." : "Gagal mengunggah file.";
    res.status(400).json({ error: message });
    return;
  }

  if (err instanceof Error && err.message === "File harus berupa gambar.") {
    res.status(400).json({ error: err.message });
    return;
  }

  console.error(err);
  res.status(500).json({ error: "Terjadi kesalahan pada server." });
});

app.listen(env.PORT, () => {
  console.log(`Wonderland admin server ready on http://localhost:${env.PORT}`);
});
