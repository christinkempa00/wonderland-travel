import { Router } from "express";
import multer from "multer";
import { z } from "zod";
import { Prisma } from "@prisma/client";
import { prisma } from "../lib/prisma";
import { uniqueSlug } from "../lib/slug";
import { deleteImage, uploadImage } from "../lib/storage";
import { requireAuth } from "../middleware/requireAuth";

export const paketWisataRouter = Router();
paketWisataRouter.use(requireAuth);

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

const paketWisataSchema = z.object({
  name: z.string().min(1, "Nama wajib diisi."),
  location: z.string().min(1, "Lokasi wajib diisi."),
  duration: z.string().min(1, "Durasi wajib diisi."),
  price: z.coerce.number().int().nonnegative("Harga tidak boleh negatif."),
  rating: z.coerce.number().min(0).max(5).default(5),
  tag: z.string().trim().optional().nullable(),
  description: z.string().min(1, "Deskripsi wajib diisi."),
  highlights: z.array(z.string().min(1)).default([]),
  active: z.coerce.boolean().default(true),
});

const SORTABLE_FIELDS = ["name", "location", "price", "rating", "order", "createdAt"] as const;

// GET / — list dengan search, filter aktif/nonaktif, sort, pagination
paketWisataRouter.get("/", async (req, res) => {
  const q = typeof req.query.q === "string" ? req.query.q.trim() : "";
  const activeParam = req.query.active;
  const sortField = SORTABLE_FIELDS.includes(req.query.sort as (typeof SORTABLE_FIELDS)[number])
    ? (req.query.sort as (typeof SORTABLE_FIELDS)[number])
    : "order";
  const sortOrder = req.query.order === "desc" ? "desc" : "asc";
  const page = Math.max(1, Number(req.query.page) || 1);
  const pageSize = Math.min(100, Math.max(1, Number(req.query.pageSize) || 20));

  const where: Prisma.PaketWisataWhereInput = {
    ...(q ? { OR: [{ name: { contains: q } }, { location: { contains: q } }] } : {}),
    ...(activeParam === "true" ? { active: true } : {}),
    ...(activeParam === "false" ? { active: false } : {}),
  };

  const [items, total] = await Promise.all([
    prisma.paketWisata.findMany({
      where,
      orderBy: { [sortField]: sortOrder },
      skip: (page - 1) * pageSize,
      take: pageSize,
      include: { images: { orderBy: { order: "asc" } } },
    }),
    prisma.paketWisata.count({ where }),
  ]);

  res.json({ items, total, page, pageSize });
});

// GET /:id — detail
paketWisataRouter.get("/:id", async (req, res) => {
  const item = await prisma.paketWisata.findUnique({
    where: { id: req.params.id },
    include: { images: { orderBy: { order: "asc" } } },
  });

  if (!item) {
    res.status(404).json({ error: "Paket wisata tidak ditemukan." });
    return;
  }

  res.json({ item });
});

// POST / — buat baru
paketWisataRouter.post("/", async (req, res) => {
  const parsed = paketWisataSchema.safeParse(req.body);
  if (!parsed.success) {
    res.status(400).json({ error: parsed.error.issues[0]?.message ?? "Data tidak valid." });
    return;
  }

  const slug = await uniqueSlug(
    parsed.data.name,
    async (candidate) => (await prisma.paketWisata.count({ where: { slug: candidate } })) > 0,
  );

  const maxOrder = await prisma.paketWisata.aggregate({ _max: { order: true } });

  const item = await prisma.paketWisata.create({
    data: {
      ...parsed.data,
      slug,
      order: (maxOrder._max.order ?? 0) + 1,
    },
    include: { images: true },
  });

  res.status(201).json({ item });
});

// PUT /:id — ubah
paketWisataRouter.put("/:id", async (req, res) => {
  const parsed = paketWisataSchema.partial().safeParse(req.body);
  if (!parsed.success) {
    res.status(400).json({ error: parsed.error.issues[0]?.message ?? "Data tidak valid." });
    return;
  }

  const existing = await prisma.paketWisata.findUnique({ where: { id: req.params.id } });
  if (!existing) {
    res.status(404).json({ error: "Paket wisata tidak ditemukan." });
    return;
  }

  const item = await prisma.paketWisata.update({
    where: { id: req.params.id },
    data: parsed.data,
    include: { images: { orderBy: { order: "asc" } } },
  });

  res.json({ item });
});

// PATCH /:id/active — toggle aktif/nonaktif
paketWisataRouter.patch("/:id/active", async (req, res) => {
  const parsed = z.object({ active: z.boolean() }).safeParse(req.body);
  if (!parsed.success) {
    res.status(400).json({ error: "Nilai active harus boolean." });
    return;
  }

  const item = await prisma.paketWisata
    .update({ where: { id: req.params.id }, data: { active: parsed.data.active } })
    .catch(() => null);

  if (!item) {
    res.status(404).json({ error: "Paket wisata tidak ditemukan." });
    return;
  }

  res.json({ item });
});

// DELETE /:id — hapus (beserta gambar di storage)
paketWisataRouter.delete("/:id", async (req, res) => {
  const existing = await prisma.paketWisata.findUnique({
    where: { id: req.params.id },
    include: { images: true },
  });

  if (!existing) {
    res.status(404).json({ error: "Paket wisata tidak ditemukan." });
    return;
  }

  await prisma.paketWisata.delete({ where: { id: req.params.id } });
  await Promise.all(existing.images.map((img) => deleteImage(img.publicId)));

  res.status(204).end();
});

// POST /:id/images — upload satu atau beberapa gambar
paketWisataRouter.post("/:id/images", upload.array("images", 10), async (req, res) => {
  const paket = await prisma.paketWisata.findUnique({ where: { id: req.params.id } });
  if (!paket) {
    res.status(404).json({ error: "Paket wisata tidak ditemukan." });
    return;
  }

  const files = (req.files as Express.Multer.File[] | undefined) ?? [];
  if (files.length === 0) {
    res.status(400).json({ error: "Tidak ada file yang diunggah." });
    return;
  }

  const maxOrder = await prisma.paketWisataImage.aggregate({
    where: { paketWisataId: paket.id },
    _max: { order: true },
  });
  let nextOrder = (maxOrder._max.order ?? -1) + 1;

  const uploaded = [];
  for (const file of files) {
    const result = await uploadImage(file.buffer, file.originalname, "paket-wisata");
    const image = await prisma.paketWisataImage.create({
      data: {
        paketWisataId: paket.id,
        url: result.url,
        publicId: result.publicId,
        order: nextOrder,
      },
    });
    uploaded.push(image);
    nextOrder += 1;
  }

  res.status(201).json({ images: uploaded });
});

// DELETE /:id/images/:imageId — hapus satu gambar
paketWisataRouter.delete("/:id/images/:imageId", async (req, res) => {
  const image = await prisma.paketWisataImage.findFirst({
    where: { id: req.params.imageId, paketWisataId: req.params.id },
  });

  if (!image) {
    res.status(404).json({ error: "Gambar tidak ditemukan." });
    return;
  }

  await prisma.paketWisataImage.delete({ where: { id: image.id } });
  await deleteImage(image.publicId);

  res.status(204).end();
});

// PUT /:id/images/reorder — drag-to-reorder, body: { order: string[] } (urutan id gambar)
paketWisataRouter.put("/:id/images/reorder", async (req, res) => {
  const parsed = z.object({ order: z.array(z.string()) }).safeParse(req.body);
  if (!parsed.success) {
    res.status(400).json({ error: "Data urutan tidak valid." });
    return;
  }

  await prisma.$transaction(
    parsed.data.order.map((imageId, index) =>
      prisma.paketWisataImage.updateMany({
        where: { id: imageId, paketWisataId: req.params.id },
        data: { order: index },
      }),
    ),
  );

  const images = await prisma.paketWisataImage.findMany({
    where: { paketWisataId: req.params.id },
    orderBy: { order: "asc" },
  });

  res.json({ images });
});
