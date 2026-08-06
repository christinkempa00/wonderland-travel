import { Router } from "express";
import rateLimit from "express-rate-limit";
import { prisma } from "../lib/prisma";
import { generateCaptcha, verifyCaptcha } from "../lib/captcha";
import { computeInvoiceTotals } from "../lib/invoiceTotals";
import { generateInvoicePdf } from "../lib/pdf";

export const cekTagihanRouter = Router();

// Endpoint publik tanpa login — dibatasi ketat per IP supaya tidak dipakai
// untuk brute-force menebak kodeKlien (yang sekarang acak, lihat lib/sequence.ts).
const lookupLimiter = rateLimit({
  windowMs: 15 * 60 * 1000,
  max: 10,
  standardHeaders: true,
  legacyHeaders: false,
  message: { error: "Terlalu banyak percobaan. Coba lagi dalam beberapa menit." },
});

const captchaLimiter = rateLimit({
  windowMs: 15 * 60 * 1000,
  max: 30,
  standardHeaders: true,
  legacyHeaders: false,
  message: { error: "Terlalu banyak permintaan. Coba lagi dalam beberapa menit." },
});

cekTagihanRouter.get("/captcha", captchaLimiter, (_req, res) => {
  res.json(generateCaptcha());
});

cekTagihanRouter.get("/:idKlien/invoice/:invoiceId/pdf", lookupLimiter, async (req, res) => {
  const pelanggan = await prisma.pelanggan.findUnique({ where: { kodeKlien: req.params.idKlien } });
  if (!pelanggan) {
    res.status(404).json({ error: "No. ID Klien tidak ditemukan." });
    return;
  }

  // Invoice harus benar-benar milik pelanggan dengan kodeKlien ini — mencegah
  // orang menebak-nebak invoiceId untuk melihat invoice orang lain.
  const invoice = await prisma.invoice.findFirst({
    where: { id: req.params.invoiceId, reservasi: { pelangganId: pelanggan.id } },
    include: { items: true, pembayaran: true, reservasi: { include: { pelanggan: true } } },
  });

  if (!invoice) {
    res.status(404).json({ error: "Invoice tidak ditemukan." });
    return;
  }

  const { totalDibayar } = computeInvoiceTotals(invoice);

  res.setHeader("Content-Type", "application/pdf");
  res.setHeader("Content-Disposition", `inline; filename="${invoice.nomorInvoice}.pdf"`);

  const doc = generateInvoicePdf({
    nomorInvoice: invoice.nomorInvoice,
    createdAt: invoice.createdAt,
    jatuhTempo: invoice.jatuhTempo,
    pelanggan: invoice.reservasi.pelanggan,
    reservasi: invoice.reservasi,
    items: invoice.items,
    diskon: invoice.diskon,
    totalDibayar,
  });

  doc.pipe(res);
  doc.end();
});

cekTagihanRouter.get("/:idKlien", lookupLimiter, async (req, res) => {
  if (!verifyCaptcha(req.query.captchaToken, req.query.captchaAnswer)) {
    res.status(400).json({ error: "Jawaban captcha salah atau sudah kedaluwarsa." });
    return;
  }

  const pelanggan = await prisma.pelanggan.findUnique({
    where: { kodeKlien: req.params.idKlien },
    include: {
      reservasi: {
        orderBy: { createdAt: "desc" },
        include: { invoice: { include: { items: true, pembayaran: true } } },
      },
    },
  });

  if (!pelanggan) {
    res.status(404).json({ error: "No. ID Klien tidak ditemukan. Periksa kembali kodenya." });
    return;
  }

  // Privasi: hanya nama depan + status + rincian tagihan yang ditampilkan ke publik.
  // No. HP, email, dan alamat lengkap TIDAK disertakan di respons ini.
  const namaDepan = pelanggan.nama.trim().split(/\s+/)[0];

  res.json({
    namaDepan,
    kodeKlien: pelanggan.kodeKlien,
    reservasi: pelanggan.reservasi.map((r) => ({
      id: r.id,
      nomorBooking: r.nomorBooking,
      jenis: r.jenis,
      itemRingkasan: r.itemRingkasan,
      tanggalMulai: r.tanggalMulai,
      tanggalSelesai: r.tanggalSelesai,
      status: r.status,
      invoice: r.invoice
        ? {
            id: r.invoice.id,
            nomorInvoice: r.invoice.nomorInvoice,
            items: r.invoice.items.map((it) => ({
              nama: it.nama,
              qty: it.qty,
              hargaSatuan: it.hargaSatuan,
            })),
            jatuhTempo: r.invoice.jatuhTempo,
            statusBayar: r.invoice.statusBayar,
            ...computeInvoiceTotals(r.invoice),
          }
        : null,
    })),
  });
});
