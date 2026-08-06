import type { ReservasiJenis, ReservasiStatus } from "./api";

export const STATUS_LABEL: Record<ReservasiStatus, string> = {
  BARU_MASUK: "Baru Masuk",
  DIKONFIRMASI: "Dikonfirmasi",
  MENUNGGU_PEMBAYARAN: "Menunggu Pembayaran",
  DIBAYAR_SEBAGIAN: "Dibayar Sebagian",
  LUNAS: "Lunas",
  SELESAI: "Selesai",
  DIBATALKAN: "Dibatalkan",
};

export const JENIS_LABEL: Record<ReservasiJenis, string> = {
  PAKET_WISATA: "Paket Wisata",
  HOTEL: "Hotel",
  PESAWAT: "Pesawat",
  RENTAL: "Rental",
  GABUNGAN: "Gabungan",
};
