import { Router } from "express";
import { prisma } from "../lib/prisma";
import { requireAuth } from "../middleware/requireAuth";

export const notifikasiRouter = Router();
notifikasiRouter.use(requireAuth);

notifikasiRouter.get("/", async (_req, res) => {
  const [items, unreadCount] = await Promise.all([
    prisma.notifikasi.findMany({ orderBy: { createdAt: "desc" }, take: 30 }),
    prisma.notifikasi.count({ where: { dibaca: false } }),
  ]);

  res.json({ items, unreadCount });
});

notifikasiRouter.patch("/:id/read", async (req, res) => {
  const item = await prisma.notifikasi
    .update({ where: { id: req.params.id }, data: { dibaca: true } })
    .catch(() => null);

  if (!item) {
    res.status(404).json({ error: "Notifikasi tidak ditemukan." });
    return;
  }

  res.json({ item });
});

notifikasiRouter.post("/read-all", async (_req, res) => {
  await prisma.notifikasi.updateMany({ where: { dibaca: false }, data: { dibaca: true } });
  res.status(204).end();
});
