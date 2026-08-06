const API_BASE = import.meta.env.VITE_API_BASE_URL ?? "";

export type ReservasiJenis = "PAKET_WISATA" | "HOTEL" | "PESAWAT" | "RENTAL" | "GABUNGAN";
export type ReservasiStatus =
  | "BARU_MASUK"
  | "DIKONFIRMASI"
  | "MENUNGGU_PEMBAYARAN"
  | "DIBAYAR_SEBAGIAN"
  | "LUNAS"
  | "SELESAI"
  | "DIBATALKAN";
export type StatusBayar = "BELUM_BAYAR" | "SEBAGIAN" | "LUNAS";

export interface InvoiceRingkas {
  id: string;
  nomorInvoice: string;
  items: { nama: string; qty: number; hargaSatuan: number }[];
  jatuhTempo: string;
  statusBayar: StatusBayar;
  subtotal: number;
  total: number;
  totalDibayar: number;
  sisaTagihan: number;
}

export interface ReservasiRingkas {
  id: string;
  nomorBooking: string;
  jenis: ReservasiJenis;
  itemRingkasan: unknown;
  tanggalMulai: string;
  tanggalSelesai: string | null;
  status: ReservasiStatus;
  invoice: InvoiceRingkas | null;
}

export interface CekTagihanResult {
  namaDepan: string;
  kodeKlien: string;
  reservasi: ReservasiRingkas[];
}

export interface CaptchaChallenge {
  question: string;
  token: string;
}

class ApiError extends Error {
  status: number;

  constructor(message: string, status: number) {
    super(message);
    this.status = status;
  }
}

async function request<T>(path: string): Promise<T> {
  const res = await fetch(`${API_BASE}${path}`);
  const data = await res.json().catch(() => ({}));

  if (!res.ok) {
    throw new ApiError(data.error ?? "Terjadi kesalahan.", res.status);
  }

  return data as T;
}

export const api = {
  getCaptcha: () => request<CaptchaChallenge>("/api/cek-tagihan/captcha"),

  cekTagihan: (idKlien: string, captchaToken: string, captchaAnswer: string) => {
    const params = new URLSearchParams({ captchaToken, captchaAnswer });
    return request<CekTagihanResult>(
      `/api/cek-tagihan/${encodeURIComponent(idKlien)}?${params.toString()}`,
    );
  },

  invoicePdfUrl: (idKlien: string, invoiceId: string) =>
    `${API_BASE}/api/cek-tagihan/${encodeURIComponent(idKlien)}/invoice/${invoiceId}/pdf`,
};

export { ApiError };
