import { Router } from "express";
import { z } from "zod";
import { Prisma } from "@prisma/client";
import { prisma } from "../lib/prisma";
import { computeInvoiceTotals } from "../lib/invoiceTotals";
import { createPelanggan } from "../lib/pelangganService";
import { requireAuth } from "../middleware/requireAuth";

export const pelangganRouter = Router();
pelangganRouter.use(requireAuth);

const pelangganSchema = z.object({
  nama: z.string().min(1, "Nama wajib diisi."),
  noHp: z.string().min(1, "No. HP wajib diisi."),
  email: z.string().email().optional().or(z.literal("")).nullable(),
  alamat: z.string().optional().nullable(),
});

pelangganRouter.get("/", async (req, res) => {
  const q = typeof req.query.q === "string" ? req.query.q.trim() : "";
  const page = Math.max(1, Number(req.query.page) || 1);
  const pageSize = Math.min(100, Math.max(1, Number(req.query.pageSize) || 20));

  const where: Prisma.PelangganWhereInput = q
    ? {
        OR: [
          { nama: { contains: q } },
          { noHp: { contains: q } },
          { email: { contains: q } },
          { kodeKlien: { contains: q } },
        ],
      }
    : {};

  const [items, total] = await Promise.all([
    prisma.pelanggan.findMany({
      where,
      orderBy: { createdAt: "desc" },
      skip: (page - 1) * pageSize,
      take: pageSize,
      include: { _count: { select: { reservasi: true } } },
    }),
    prisma.pelanggan.count({ where }),
  ]);

  res.json({ items, total, page, pageSize });
});

pelangganRouter.get("/:id", async (req, res) => {
  const item = await prisma.pelanggan.findUnique({
    where: { id: req.params.id },
    include: {
      reservasi: {
        orderBy: { createdAt: "desc" },
        include: { invoice: { include: { items: true, pembayaran: true } } },
      },
    },
  });

  if (!item) {
    res.status(404).json({ error: "Pelanggan tidak ditemukan." });
    return;
  }

  res.json({
    item: {
      ...item,
      reservasi: item.reservasi.map((r) => ({
        ...r,
        invoice: r.invoice ? { ...r.invoice, ...computeInvoiceTotals(r.invoice) } : null,
      })),
    },
  });
});

pelangganRouter.post("/", async (req, res) => {
  const parsed = pelangganSchema.safeParse(req.body);
  if (!parsed.success) {
    res.status(400).json({ error: parsed.error.issues[0]?.message ?? "Data tidak valid." });
    return;
  }

  const item = await createPelanggan(parsed.data);

  res.status(201).json({ item });
});

pelangganRouter.put("/:id", async (req, res) => {
  const parsed = pelangganSchema.partial().safeParse(req.body);
  if (!parsed.success) {
    res.status(400).json({ error: parsed.error.issues[0]?.message ?? "Data tidak valid." });
    return;
  }

  const existing = await prisma.pelanggan.findUnique({ where: { id: req.params.id } });
  if (!existing) {
    res.status(404).json({ error: "Pelanggan tidak ditemukan." });
    return;
  }

  const item = await prisma.pelanggan.update({
    where: { id: req.params.id },
    data: { ...parsed.data, email: parsed.data.email || undefined },
  });

  res.json({ item });
});
