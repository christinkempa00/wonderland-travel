import { Router } from "express";
import { z } from "zod";
import { prisma } from "../lib/prisma";
import { requireAuth } from "../middleware/requireAuth";

export const laporanRouter = Router();
laporanRouter.use(requireAuth);

const querySchema = z.object({
  from: z.coerce.date().optional(),
  to: z.coerce.date().optional(),
  groupBy: z.enum(["day", "month"]).default("month"),
});

laporanRouter.get("/pendapatan", async (req, res) => {
  const parsed = querySchema.safeParse(req.query);
  if (!parsed.success) {
    res.status(400).json({ error: "Parameter tidak valid." });
    return;
  }

  const to = parsed.data.to ?? new Date();
  const from = parsed.data.from ?? new Date(to.getFullYear(), to.getMonth() - 5, 1);

  const payments = await prisma.pembayaran.findMany({
    where: { tanggal: { gte: from, lte: to } },
    select: { jumlah: true, tanggal: true },
    orderBy: { tanggal: "asc" },
  });

  const buckets = new Map<string, number>();
  for (const p of payments) {
    const key =
      parsed.data.groupBy === "day"
        ? p.tanggal.toISOString().slice(0, 10)
        : p.tanggal.toISOString().slice(0, 7);
    buckets.set(key, (buckets.get(key) ?? 0) + p.jumlah);
  }

  const data = Array.from(buckets.entries())
    .sort(([a], [b]) => a.localeCompare(b))
    .map(([period, total]) => ({ period, total }));

  const totalPendapatan = payments.reduce((sum, p) => sum + p.jumlah, 0);

  res.json({ data, totalPendapatan, from, to, groupBy: parsed.data.groupBy });
});
