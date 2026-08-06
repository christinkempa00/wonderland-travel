import { Router } from "express";
import multer from "multer";
import { z } from "zod";
import { Prisma, StatusBayar } from "@prisma/client";
import { prisma } from "../lib/prisma";
import { generateInvoicePdf } from "../lib/pdf";
import { generateDailyCode } from "../lib/sequence";
import { computeInvoiceTotals } from "../lib/invoiceTotals";
import { deleteImage, uploadImage } from "../lib/storage";
import { requireAuth } from "../middleware/requireAuth";

export const invoiceRouter = Router();
invoiceRouter.use(requireAuth);

const upload = multer({
  storage: multer.memoryStorage(),
  limits: { fileSize: 5 * 1024 * 1024 },
  fileFilter: (_req, file, cb) => {
    if (!file.mimetype.startsWith("image/")) {
      cb(new Error("File harus berupa gambar."));
      return;
    }
    cb(null, true);
  },
});

const itemSchema = z.object({
  nama: z.string().min(1, "Nama item wajib diisi."),
  qty: z.coerce.number().int().positive(),
  hargaSatuan: z.coerce.number().int().nonnegative(),
});

const invoiceCreateSchema = z.object({
  reservasiId: z.string().min(1),
  items: z.array(itemSchema).min(1, "Minimal 1 item."),
  diskon: z.coerce.number().int().nonnegative().default(0),
  jatuhTempo: z.coerce.date(),
});

const invoiceUpdateSchema = z.object({
  items: z.array(itemSchema).min(1).optional(),
  diskon: z.coerce.number().int().nonnegative().optional(),
  jatuhTempo: z.coerce.date().optional(),
});

const invoiceInclude = {
  items: { orderBy: { order: "asc" as const } },
  pembayaran: { orderBy: { tanggal: "desc" as const } },
  reservasi: { include: { pelanggan: true } },
};

function serializeInvoice<T extends { diskon: number; items: { qty: number; hargaSatuan: number }[]; pembayaran: { jumlah: number }[] }>(
  invoice: T,
) {
  return { ...invoice, ...computeInvoiceTotals(invoice) };
}

invoiceRouter.get("/", async (req, res) => {
  const q = typeof req.query.q === "string" ? req.query.q.trim() : "";
  const statusBayar = req.query.statusBayar;
  const page = Math.max(1, Number(req.query.page) || 1);
  const pageSize = Math.min(100, Math.max(1, Number(req.query.pageSize) || 20));

  const where: Prisma.InvoiceWhereInput = {
    ...(["BELUM_BAYAR", "SEBAGIAN", "LUNAS"].includes(statusBayar as StatusBayar)
      ? { statusBayar: statusBayar as StatusBayar }
      : {}),
    ...(q
      ? {
          OR: [
            { nomorInvoice: { contains: q } },
            { reservasi: { nomorBooking: { contains: q } } },
            { reservasi: { pelanggan: { nama: { contains: q } } } },
          ],
        }
      : {}),
  };

  const [items, total] = await Promise.all([
    prisma.invoice.findMany({
      where,
      orderBy: { createdAt: "desc" },
      skip: (page - 1) * pageSize,
      take: pageSize,
      include: invoiceInclude,
    }),
    prisma.invoice.count({ where }),
  ]);

  res.json({ items: items.map(serializeInvoice), total, page, pageSize });
});

invoiceRouter.get("/:id", async (req, res) => {
  const item = await prisma.invoice.findUnique({
    where: { id: req.params.id },
    include: invoiceInclude,
  });

  if (!item) {
    res.status(404).json({ error: "Invoice tidak ditemukan." });
    return;
  }

  res.json({ item: serializeInvoice(item) });
});

invoiceRouter.post("/", async (req, res) => {
  const parsed = invoiceCreateSchema.safeParse(req.body);
  if (!parsed.success) {
    res.status(400).json({ error: parsed.error.issues[0]?.message ?? "Data tidak valid." });
    return;
  }

  const reservasi = await prisma.reservasi.findUnique({
    where: { id: parsed.data.reservasiId },
    include: { invoice: true },
  });

  if (!reservasi) {
    res.status(404).json({ error: "Reservasi tidak ditemukan." });
    return;
  }
  if (reservasi.invoice) {
    res.status(400).json({ error: "Reservasi ini sudah punya invoice." });
    return;
  }

  const nomorInvoice = await generateDailyCode(
    "INV",
    () => {
      const start = new Date();
      start.setHours(0, 0, 0, 0);
      return prisma.invoice.count({ where: { createdAt: { gte: start } } });
    },
    async (candidate) => (await prisma.invoice.count({ where: { nomorInvoice: candidate } })) > 0,
  );

  await prisma.invoice.create({
    data: {
      nomorInvoice,
      reservasiId: parsed.data.reservasiId,
      diskon: parsed.data.diskon,
      jatuhTempo: parsed.data.jatuhTempo,
      items: {
        create: parsed.data.items.map((item, index) => ({ ...item, order: index })),
      },
    },
  });

  // Reservasi baru dapat invoice biasanya berarti siap ditagih.
  if (reservasi.status === "BARU_MASUK" || reservasi.status === "DIKONFIRMASI") {
    await prisma.reservasi.update({
      where: { id: reservasi.id },
      data: { status: "MENUNGGU_PEMBAYARAN" },
    });
  }

  const item = await prisma.invoice.findUniqueOrThrow({
    where: { reservasiId: parsed.data.reservasiId },
    include: invoiceInclude,
  });

  res.status(201).json({ item: serializeInvoice(item) });
});

invoiceRouter.put("/:id", async (req, res) => {
  const parsed = invoiceUpdateSchema.safeParse(req.body);
  if (!parsed.success) {
    res.status(400).json({ error: parsed.error.issues[0]?.message ?? "Data tidak valid." });
    return;
  }

  const existing = await prisma.invoice.findUnique({ where: { id: req.params.id } });
  if (!existing) {
    res.status(404).json({ error: "Invoice tidak ditemukan." });
    return;
  }

  const { items, ...rest } = parsed.data;

  const item = await prisma.$transaction(async (tx) => {
    if (items) {
      await tx.invoiceItem.deleteMany({ where: { invoiceId: req.params.id } });
      await tx.invoiceItem.createMany({
        data: items.map((it, index) => ({ ...it, invoiceId: req.params.id, order: index })),
      });
    }
    return tx.invoice.update({
      where: { id: req.params.id },
      data: rest,
      include: invoiceInclude,
    });
  });

  res.json({ item: serializeInvoice(item) });
});

invoiceRouter.delete("/:id", async (req, res) => {
  const existing = await prisma.invoice.findUnique({
    where: { id: req.params.id },
    include: { pembayaran: true },
  });

  if (!existing) {
    res.status(404).json({ error: "Invoice tidak ditemukan." });
    return;
  }

  await prisma.invoice.delete({ where: { id: req.params.id } });
  await Promise.all(
    existing.pembayaran
      .filter((p) => p.buktiPublicId)
      .map((p) => deleteImage(p.buktiPublicId!)),
  );

  res.status(204).end();
});

invoiceRouter.get("/:id/pdf", async (req, res) => {
  const invoice = await prisma.invoice.findUnique({
    where: { id: req.params.id },
    include: invoiceInclude,
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

// ---- Pembayaran (nested di bawah invoice) ----

const pembayaranSchema = z.object({
  jumlah: z.coerce.number().int().positive("Jumlah harus lebih dari 0."),
  metode: z.enum(["TRANSFER_BANK", "E_WALLET", "KARTU_KREDIT", "TUNAI", "LAINNYA"]),
  tanggal: z.coerce.date(),
  catatan: z.string().optional().nullable(),
});

async function syncStatusFromPembayaran(invoiceId: string) {
  const invoice = await prisma.invoice.findUnique({
    where: { id: invoiceId },
    include: { items: true, pembayaran: true, reservasi: true },
  });
  if (!invoice) return;

  const { total, totalDibayar } = computeInvoiceTotals(invoice);
  const statusBayar: StatusBayar =
    totalDibayar <= 0 ? "BELUM_BAYAR" : totalDibayar >= total ? "LUNAS" : "SEBAGIAN";

  await prisma.invoice.update({ where: { id: invoiceId }, data: { statusBayar } });

  const reservasiStatus = invoice.reservasi.status;
  if (reservasiStatus !== "SELESAI" && reservasiStatus !== "DIBATALKAN") {
    const nextStatus =
      statusBayar === "LUNAS" ? "LUNAS" : statusBayar === "SEBAGIAN" ? "DIBAYAR_SEBAGIAN" : null;
    if (nextStatus) {
      await prisma.reservasi.update({
        where: { id: invoice.reservasi.id },
        data: { status: nextStatus },
      });
    }
  }
}

invoiceRouter.post("/:id/pembayaran", upload.single("bukti"), async (req, res) => {
  const parsed = pembayaranSchema.safeParse(req.body);
  if (!parsed.success) {
    res.status(400).json({ error: parsed.error.issues[0]?.message ?? "Data tidak valid." });
    return;
  }

  const invoice = await prisma.invoice.findUnique({ where: { id: req.params.id } });
  if (!invoice) {
    res.status(404).json({ error: "Invoice tidak ditemukan." });
    return;
  }

  let buktiUrl: string | null = null;
  let buktiPublicId: string | null = null;
  if (req.file) {
    const result = await uploadImage(req.file.buffer, req.file.originalname, "pembayaran");
    buktiUrl = result.url;
    buktiPublicId = result.publicId;
  }

  const pembayaran = await prisma.pembayaran.create({
    data: { ...parsed.data, invoiceId: invoice.id, buktiUrl, buktiPublicId },
  });

  await syncStatusFromPembayaran(invoice.id);

  res.status(201).json({ pembayaran });
});

invoiceRouter.delete("/:id/pembayaran/:pembayaranId", async (req, res) => {
  const pembayaran = await prisma.pembayaran.findFirst({
    where: { id: req.params.pembayaranId, invoiceId: req.params.id },
  });

  if (!pembayaran) {
    res.status(404).json({ error: "Pembayaran tidak ditemukan." });
    return;
  }

  await prisma.pembayaran.delete({ where: { id: pembayaran.id } });
  if (pembayaran.buktiPublicId) {
    await deleteImage(pembayaran.buktiPublicId);
  }
  await syncStatusFromPembayaran(req.params.id);

  res.status(204).end();
});
