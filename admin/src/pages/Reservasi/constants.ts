import type { ReservasiJenis, ReservasiStatus, ReservasiSumber } from "../../lib/api";

export const RESERVASI_STATUS_ORDER: ReservasiStatus[] = [
  "BARU_MASUK",
  "DIKONFIRMASI",
  "MENUNGGU_PEMBAYARAN",
  "DIBAYAR_SEBAGIAN",
  "LUNAS",
  "SELESAI",
  "DIBATALKAN",
];

export const RESERVASI_STATUS_LABEL: Record<ReservasiStatus, string> = {
  BARU_MASUK: "Baru Masuk",
  DIKONFIRMASI: "Dikonfirmasi",
  MENUNGGU_PEMBAYARAN: "Menunggu Pembayaran",
  DIBAYAR_SEBAGIAN: "Dibayar Sebagian",
  LUNAS: "Lunas",
  SELESAI: "Selesai",
  DIBATALKAN: "Dibatalkan",
};

export const RESERVASI_JENIS_LABEL: Record<ReservasiJenis, string> = {
  PAKET_WISATA: "Paket Wisata",
  HOTEL: "Hotel",
  PESAWAT: "Pesawat",
  RENTAL: "Rental",
  GABUNGAN: "Gabungan",
};

export const RESERVASI_SUMBER_LABEL: Record<ReservasiSumber, string> = {
  WEBSITE_EXPLORE: "Website — Explore",
  WEBSITE_KONTAK: "Website — Kontak",
  MANUAL_STAF: "Manual (Staf)",
  WHATSAPP: "WhatsApp",
};
