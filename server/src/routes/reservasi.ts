import { Router } from "express";
import { z } from "zod";
import { Prisma, ReservasiStatus } from "@prisma/client";
import { prisma } from "../lib/prisma";
import { computeInvoiceTotals } from "../lib/invoiceTotals";
import { createPelanggan } from "../lib/pelangganService";
import { generateDailyCode } from "../lib/sequence";
import { requireAuth } from "../middleware/requireAuth";

export const reservasiRouter = Router();
reservasiRouter.use(requireAuth);

const JENIS_VALUES = ["PAKET_WISATA", "HOTEL", "PESAWAT", "RENTAL", "GABUNGAN"] as const;
const SUMBER_VALUES = ["WEBSITE_EXPLORE", "WEBSITE_KONTAK", "MANUAL_STAF", "WHATSAPP"] as const;
const STATUS_VALUES = [
  "BARU_MASUK",
  "DIKONFIRMASI",
  "MENUNGGU_PEMBAYARAN",
  "DIBAYAR_SEBAGIAN",
  "LUNAS",
  "SELESAI",
  "DIBATALKAN",
] as const;

const reservasiCreateSchema = z
  .object({
    pelangganId: z.string().optional(),
    pelangganBaru: z
      .object({
        nama: z.string().min(1),
        noHp: z.string().min(1),
        email: z.string().email().optional().or(z.literal("")),
      })
      .optional(),
    jenis: z.enum(JENIS_VALUES),
    itemRingkasan: z.any(),
    tanggalMulai: z.coerce.date(),
    tanggalSelesai: z.coerce.date().optional().nullable(),
    sumber: z.enum(SUMBER_VALUES).default("MANUAL_STAF"),
    catatan: z.string().optional().nullable(),
  })
  .refine((data) => data.pelangganId || data.pelangganBaru, {
    message: "pelangganId atau pelangganBaru wajib diisi.",
  });

const reservasiUpdateSchema = z.object({
  jenis: z.enum(JENIS_VALUES).optional(),
  itemRingkasan: z.any().optional(),
  tanggalMulai: z.coerce.date().optional(),
  tanggalSelesai: z.coerce.date().optional().nullable(),
  catatan: z.string().optional().nullable(),
});

async function notifyReservasiBaru(reservasiId: string, nomorBooking: string, namaPelanggan: string) {
  await prisma.notifikasi.create({
    data: {
      pesan: `Reservasi baru masuk: ${nomorBooking} dari ${namaPelanggan}.`,
      reservasiId,
    },
  });
}

const reservasiInclude = {
  pelanggan: true,
  invoice: { include: { items: true, pembayaran: true } },
} satisfies Prisma.ReservasiInclude;

type ReservasiWithInvoice = Prisma.ReservasiGetPayload<{ include: typeof reservasiInclude }>;

/** invoice tidak menyimpan subtotal/total/sisaTagihan sebagai kolom — dihitung di sini
 * supaya konsisten dengan bentuk yang dikembalikan endpoint /invoice. */
function serializeReservasi(item: ReservasiWithInvoice) {
  return {
    ...item,
    invoice: item.invoice ? { ...item.invoice, ...computeInvoiceTotals(item.invoice) } : null,
  };
}

reservasiRouter.get("/", async (req, res) => {
  const q = typeof req.query.q === "string" ? req.query.q.trim() : "";
  const status = req.query.status;
  const page = Math.max(1, Number(req.query.page) || 1);
  const pageSize = Math.min(100, Math.max(1, Number(req.query.pageSize) || 50));

  const where: Prisma.ReservasiWhereInput = {
    ...(STATUS_VALUES.includes(status as ReservasiStatus)
      ? { status: status as ReservasiStatus }
      : {}),
    ...(q
      ? {
          OR: [
            { nomorBooking: { contains: q } },
            { pelanggan: { nama: { contains: q } } },
            { pelanggan: { noHp: { contains: q } } },
          ],
        }
      : {}),
  };

  const [items, total] = await Promise.all([
    prisma.reservasi.findMany({
      where,
      orderBy: { createdAt: "desc" },
      skip: (page - 1) * pageSize,
      take: pageSize,
      include: reservasiInclude,
    }),
    prisma.reservasi.count({ where }),
  ]);

  res.json({ items: items.map(serializeReservasi), total, page, pageSize });
});

reservasiRouter.get("/:id", async (req, res) => {
  const item = await prisma.reservasi.findUnique({
    where: { id: req.params.id },
    include: reservasiInclude,
  });

  if (!item) {
    res.status(404).json({ error: "Reservasi tidak ditemukan." });
    return;
  }

  res.json({ item: serializeReservasi(item) });
});

reservasiRouter.post("/", async (req, res) => {
  const parsed = reservasiCreateSchema.safeParse(req.body);
  if (!parsed.success) {
    res.status(400).json({ error: parsed.error.issues[0]?.message ?? "Data tidak valid." });
    return;
  }

  const data = parsed.data;

  const pelanggan = data.pelangganId
    ? await prisma.pelanggan.findUnique({ where: { id: data.pelangganId } })
    : await createPelanggan(data.pelangganBaru!);

  if (!pelanggan) {
    res.status(404).json({ error: "Pelanggan tidak ditemukan." });
    return;
  }

  const nomorBooking = await generateDailyCode(
    "BK",
    () => {
      const start = new Date();
      start.setHours(0, 0, 0, 0);
      return prisma.reservasi.count({ where: { createdAt: { gte: start } } });
    },
    async (candidate) => (await prisma.reservasi.count({ where: { nomorBooking: candidate } })) > 0,
  );

  const item = await prisma.reservasi.create({
    data: {
      nomorBooking,
      pelangganId: pelanggan.id,
      jenis: data.jenis,
      itemRingkasan: data.itemRingkasan ?? {},
      tanggalMulai: data.tanggalMulai,
      tanggalSelesai: data.tanggalSelesai,
      sumber: data.sumber,
      catatan: data.catatan,
    },
    include: reservasiInclude,
  });

  await notifyReservasiBaru(item.id, item.nomorBooking, pelanggan.nama);

  res.status(201).json({ item: serializeReservasi(item) });
});

reservasiRouter.put("/:id", async (req, res) => {
  const parsed = reservasiUpdateSchema.safeParse(req.body);
  if (!parsed.success) {
    res.status(400).json({ error: parsed.error.issues[0]?.message ?? "Data tidak valid." });
    return;
  }

  const existing = await prisma.reservasi.findUnique({ where: { id: req.params.id } });
  if (!existing) {
    res.status(404).json({ error: "Reservasi tidak ditemukan." });
    return;
  }

  const item = await prisma.reservasi.update({
    where: { id: req.params.id },
    data: parsed.data,
    include: reservasiInclude,
  });

  res.json({ item: serializeReservasi(item) });
});

reservasiRouter.patch("/:id/status", async (req, res) => {
  const parsed = z.object({ status: z.enum(STATUS_VALUES) }).safeParse(req.body);
  if (!parsed.success) {
    res.status(400).json({ error: "Status tidak valid." });
    return;
  }

  const item = await prisma.reservasi
    .update({
      where: { id: req.params.id },
      data: { status: parsed.data.status },
      include: reservasiInclude,
    })
    .catch(() => null);

  if (!item) {
    res.status(404).json({ error: "Reservasi tidak ditemukan." });
    return;
  }

  res.json({ item: serializeReservasi(item) });
});

reservasiRouter.delete("/:id", async (req, res) => {
  const existing = await prisma.reservasi.findUnique({
    where: { id: req.params.id },
    include: { invoice: true },
  });

  if (!existing) {
    res.status(404).json({ error: "Reservasi tidak ditemukan." });
    return;
  }

  if (existing.invoice) {
    res.status(400).json({
      error: "Tidak bisa menghapus reservasi yang sudah punya invoice. Hapus invoice-nya dulu.",
    });
    return;
  }

  await prisma.reservasi.delete({ where: { id: req.params.id } });
  res.status(204).end();
});
