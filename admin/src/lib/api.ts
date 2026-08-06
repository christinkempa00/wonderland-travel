export interface AuthUser {
  id: string;
  name: string;
  email: string;
  role: "ADMIN" | "EDITOR";
}

export interface PaketWisataImage {
  id: string;
  paketWisataId: string;
  url: string;
  publicId: string;
  order: number;
  createdAt: string;
}

export interface PaketWisata {
  id: string;
  name: string;
  slug: string;
  location: string;
  duration: string;
  price: number;
  rating: number;
  tag: string | null;
  description: string;
  highlights: string[];
  active: boolean;
  order: number;
  images: PaketWisataImage[];
  createdAt: string;
  updatedAt: string;
}

export interface PaketWisataInput {
  name: string;
  location: string;
  duration: string;
  price: number;
  rating: number;
  tag?: string | null;
  description: string;
  highlights: string[];
  active: boolean;
}

export interface PaketWisataListParams {
  q?: string;
  active?: "true" | "false" | "all";
  sort?: string;
  order?: "asc" | "desc";
  page?: number;
  pageSize?: number;
}

export interface Pelanggan {
  id: string;
  kodeKlien: string;
  nama: string;
  noHp: string;
  email: string | null;
  alamat: string | null;
  createdAt: string;
  updatedAt: string;
  _count?: { reservasi: number };
}

export interface PelangganInput {
  nama: string;
  noHp: string;
  email?: string | null;
  alamat?: string | null;
}

export type ReservasiJenis = "PAKET_WISATA" | "HOTEL" | "PESAWAT" | "RENTAL" | "GABUNGAN";
export type ReservasiStatus =
  | "BARU_MASUK"
  | "DIKONFIRMASI"
  | "MENUNGGU_PEMBAYARAN"
  | "DIBAYAR_SEBAGIAN"
  | "LUNAS"
  | "SELESAI"
  | "DIBATALKAN";
export type ReservasiSumber = "WEBSITE_EXPLORE" | "WEBSITE_KONTAK" | "MANUAL_STAF" | "WHATSAPP";
export type StatusBayar = "BELUM_BAYAR" | "SEBAGIAN" | "LUNAS";
export type MetodePembayaran = "TRANSFER_BANK" | "E_WALLET" | "KARTU_KREDIT" | "TUNAI" | "LAINNYA";

export interface Reservasi {
  id: string;
  nomorBooking: string;
  pelangganId: string;
  pelanggan: Pelanggan;
  jenis: ReservasiJenis;
  itemRingkasan: unknown;
  tanggalMulai: string;
  tanggalSelesai: string | null;
  status: ReservasiStatus;
  sumber: ReservasiSumber;
  catatan: string | null;
  invoice: Invoice | null;
  createdAt: string;
  updatedAt: string;
}

export interface ReservasiInput {
  pelangganId?: string;
  pelangganBaru?: { nama: string; noHp: string; email?: string };
  jenis: ReservasiJenis;
  itemRingkasan?: unknown;
  tanggalMulai: string;
  tanggalSelesai?: string | null;
  sumber?: ReservasiSumber;
  catatan?: string | null;
}

export interface InvoiceItem {
  id: string;
  invoiceId: string;
  nama: string;
  qty: number;
  hargaSatuan: number;
  order: number;
}

export interface Pembayaran {
  id: string;
  invoiceId: string;
  jumlah: number;
  metode: MetodePembayaran;
  buktiUrl: string | null;
  tanggal: string;
  catatan: string | null;
  createdAt: string;
}

export interface Invoice {
  id: string;
  nomorInvoice: string;
  reservasiId: string;
  items: InvoiceItem[];
  diskon: number;
  jatuhTempo: string;
  statusBayar: StatusBayar;
  pembayaran: Pembayaran[];
  reservasi?: Reservasi;
  subtotal: number;
  total: number;
  totalDibayar: number;
  sisaTagihan: number;
  createdAt: string;
  updatedAt: string;
}

export interface InvoiceInput {
  reservasiId: string;
  items: { nama: string; qty: number; hargaSatuan: number }[];
  diskon: number;
  jatuhTempo: string;
}

export interface Notifikasi {
  id: string;
  pesan: string;
  reservasiId: string | null;
  dibaca: boolean;
  createdAt: string;
}

export interface LaporanPendapatan {
  data: { period: string; total: number }[];
  totalPendapatan: number;
  from: string;
  to: string;
  groupBy: "day" | "month";
}

class ApiError extends Error {
  status: number;

  constructor(message: string, status: number) {
    super(message);
    this.status = status;
  }
}

async function request<T>(path: string, options: RequestInit = {}): Promise<T> {
  const isFormData = options.body instanceof FormData;

  const res = await fetch(`/api${path}`, {
    credentials: "include",
    headers: isFormData ? undefined : { "Content-Type": "application/json" },
    ...options,
  });

  if (res.status === 204) {
    return undefined as T;
  }

  const data = await res.json().catch(() => ({}));

  if (!res.ok) {
    throw new ApiError(data.error ?? "Terjadi kesalahan.", res.status);
  }

  return data as T;
}

function buildQuery(params: Record<string, string | number | undefined>): string {
  const search = new URLSearchParams();
  for (const [key, value] of Object.entries(params)) {
    if (value !== undefined && value !== "" && value !== "all") {
      search.set(key, String(value));
    }
  }
  const qs = search.toString();
  return qs ? `?${qs}` : "";
}

export const api = {
  login: (email: string, password: string) =>
    request<{ user: AuthUser }>("/auth/login", {
      method: "POST",
      body: JSON.stringify({ email, password }),
    }),
  logout: () => request<void>("/auth/logout", { method: "POST" }),
  me: () => request<{ user: AuthUser }>("/auth/me"),

  paketWisata: {
    list: (params: PaketWisataListParams = {}) =>
      request<{ items: PaketWisata[]; total: number; page: number; pageSize: number }>(
        `/admin/paket-wisata${buildQuery(params as Record<string, string | number | undefined>)}`,
      ),
    get: (id: string) => request<{ item: PaketWisata }>(`/admin/paket-wisata/${id}`),
    create: (input: PaketWisataInput) =>
      request<{ item: PaketWisata }>("/admin/paket-wisata", {
        method: "POST",
        body: JSON.stringify(input),
      }),
    update: (id: string, input: Partial<PaketWisataInput>) =>
      request<{ item: PaketWisata }>(`/admin/paket-wisata/${id}`, {
        method: "PUT",
        body: JSON.stringify(input),
      }),
    setActive: (id: string, active: boolean) =>
      request<{ item: PaketWisata }>(`/admin/paket-wisata/${id}/active`, {
        method: "PATCH",
        body: JSON.stringify({ active }),
      }),
    remove: (id: string) => request<void>(`/admin/paket-wisata/${id}`, { method: "DELETE" }),
    uploadImages: (id: string, files: FileList) => {
      const formData = new FormData();
      Array.from(files).forEach((file) => formData.append("images", file));
      return request<{ images: PaketWisataImage[] }>(`/admin/paket-wisata/${id}/images`, {
        method: "POST",
        body: formData,
      });
    },
    deleteImage: (id: string, imageId: string) =>
      request<void>(`/admin/paket-wisata/${id}/images/${imageId}`, { method: "DELETE" }),
    reorderImages: (id: string, order: string[]) =>
      request<{ images: PaketWisataImage[] }>(`/admin/paket-wisata/${id}/images/reorder`, {
        method: "PUT",
        body: JSON.stringify({ order }),
      }),
  },

  pelanggan: {
    list: (params: { q?: string; page?: number; pageSize?: number } = {}) =>
      request<{ items: Pelanggan[]; total: number; page: number; pageSize: number }>(
        `/admin/pelanggan${buildQuery(params as Record<string, string | number | undefined>)}`,
      ),
    get: (id: string) => request<{ item: Pelanggan & { reservasi: Reservasi[] } }>(`/admin/pelanggan/${id}`),
    create: (input: PelangganInput) =>
      request<{ item: Pelanggan }>("/admin/pelanggan", { method: "POST", body: JSON.stringify(input) }),
    update: (id: string, input: Partial<PelangganInput>) =>
      request<{ item: Pelanggan }>(`/admin/pelanggan/${id}`, {
        method: "PUT",
        body: JSON.stringify(input),
      }),
  },

  reservasi: {
    list: (params: { q?: string; status?: ReservasiStatus | "all"; page?: number; pageSize?: number } = {}) =>
      request<{ items: Reservasi[]; total: number; page: number; pageSize: number }>(
        `/admin/reservasi${buildQuery(params as Record<string, string | number | undefined>)}`,
      ),
    get: (id: string) => request<{ item: Reservasi }>(`/admin/reservasi/${id}`),
    create: (input: ReservasiInput) =>
      request<{ item: Reservasi }>("/admin/reservasi", { method: "POST", body: JSON.stringify(input) }),
    update: (id: string, input: Partial<ReservasiInput>) =>
      request<{ item: Reservasi }>(`/admin/reservasi/${id}`, {
        method: "PUT",
        body: JSON.stringify(input),
      }),
    setStatus: (id: string, status: ReservasiStatus) =>
      request<{ item: Reservasi }>(`/admin/reservasi/${id}/status`, {
        method: "PATCH",
        body: JSON.stringify({ status }),
      }),
    remove: (id: string) => request<void>(`/admin/reservasi/${id}`, { method: "DELETE" }),
  },

  invoice: {
    list: (params: { q?: string; statusBayar?: StatusBayar | "all"; page?: number; pageSize?: number } = {}) =>
      request<{ items: Invoice[]; total: number; page: number; pageSize: number }>(
        `/admin/invoice${buildQuery(params as Record<string, string | number | undefined>)}`,
      ),
    get: (id: string) => request<{ item: Invoice }>(`/admin/invoice/${id}`),
    create: (input: InvoiceInput) =>
      request<{ item: Invoice }>("/admin/invoice", { method: "POST", body: JSON.stringify(input) }),
    update: (id: string, input: Partial<InvoiceInput>) =>
      request<{ item: Invoice }>(`/admin/invoice/${id}`, { method: "PUT", body: JSON.stringify(input) }),
    remove: (id: string) => request<void>(`/admin/invoice/${id}`, { method: "DELETE" }),
    pdfUrl: (id: string) => `/api/admin/invoice/${id}/pdf`,
    addPembayaran: (
      id: string,
      input: { jumlah: number; metode: MetodePembayaran; tanggal: string; catatan?: string },
      bukti?: File,
    ) => {
      const formData = new FormData();
      formData.append("jumlah", String(input.jumlah));
      formData.append("metode", input.metode);
      formData.append("tanggal", input.tanggal);
      if (input.catatan) formData.append("catatan", input.catatan);
      if (bukti) formData.append("bukti", bukti);
      return request<{ pembayaran: Pembayaran }>(`/admin/invoice/${id}/pembayaran`, {
        method: "POST",
        body: formData,
      });
    },
    removePembayaran: (id: string, pembayaranId: string) =>
      request<void>(`/admin/invoice/${id}/pembayaran/${pembayaranId}`, { method: "DELETE" }),
  },

  laporan: {
    pendapatan: (params: { from?: string; to?: string; groupBy?: "day" | "month" } = {}) =>
      request<LaporanPendapatan>(
        `/admin/laporan/pendapatan${buildQuery(params as Record<string, string | number | undefined>)}`,
      ),
  },

  notifikasi: {
    list: () => request<{ items: Notifikasi[]; unreadCount: number }>("/admin/notifikasi"),
    markRead: (id: string) =>
      request<{ item: Notifikasi }>(`/admin/notifikasi/${id}/read`, { method: "PATCH" }),
    markAllRead: () => request<void>("/admin/notifikasi/read-all", { method: "POST" }),
  },
};

export { ApiError };
