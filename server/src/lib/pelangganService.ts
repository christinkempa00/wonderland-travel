import { prisma } from "./prisma";
import { generateKodeKlien } from "./sequence";

export interface CreatePelangganInput {
  nama: string;
  noHp: string;
  email?: string | null;
  alamat?: string | null;
}

export async function createPelanggan(data: CreatePelangganInput) {
  const kodeKlien = await generateKodeKlien(
    async (candidate) => (await prisma.pelanggan.count({ where: { kodeKlien: candidate } })) > 0,
  );

  return prisma.pelanggan.create({
    data: { ...data, email: data.email || null, kodeKlien },
  });
}
