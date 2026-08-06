import crypto from "node:crypto";

/**
 * Generator kode/nomor unik dengan pola "PREFIX-suffix", retry sampai tidak bentrok.
 * Dipakai untuk nomorBooking (BK-20260804-0001), nomorInvoice (INV-...).
 */
async function generateUnique(
  build: (attempt: number) => string,
  exists: (candidate: string) => Promise<boolean>,
): Promise<string> {
  for (let attempt = 0; attempt < 50; attempt += 1) {
    const candidate = build(attempt);
    if (!(await exists(candidate))) return candidate;
  }
  throw new Error("Gagal membuat kode unik setelah beberapa percobaan.");
}

// Alfabet aman: tanpa 0/O dan 1/I/L supaya tidak tertukar saat dibaca manual,
// dan sengaja TIDAK berurutan — kodeKlien dipakai sebagai "kunci" publik read-only
// di Portal Cek Tagihan (Fase 9), jadi harus sulit ditebak (bukan WT000001, WT000002, ...).
const KODE_KLIEN_ALPHABET = "23456789ABCDEFGHJKMNPQRSTVWXYZ";
const KODE_KLIEN_LENGTH = 8;

function randomKodeKlien(): string {
  let code = "";
  for (let i = 0; i < KODE_KLIEN_LENGTH; i += 1) {
    code += KODE_KLIEN_ALPHABET[crypto.randomInt(KODE_KLIEN_ALPHABET.length)];
  }
  return `WT-${code}`;
}

/** Kode klien acak (mis. WT-7K4M9XQP) — bukan sequential, lihat catatan di atas. */
export async function generateKodeKlien(
  exists: (candidate: string) => Promise<boolean>,
): Promise<string> {
  for (let attempt = 0; attempt < 50; attempt += 1) {
    const candidate = randomKodeKlien();
    if (!(await exists(candidate))) return candidate;
  }
  throw new Error("Gagal membuat kode klien unik setelah beberapa percobaan.");
}

/** PREFIX-YYYYMMDD-0001, urut per hari. */
export async function generateDailyCode(
  prefix: string,
  countToday: () => Promise<number>,
  exists: (candidate: string) => Promise<boolean>,
): Promise<string> {
  const now = new Date();
  const datePart = `${now.getFullYear()}${String(now.getMonth() + 1).padStart(2, "0")}${String(
    now.getDate(),
  ).padStart(2, "0")}`;
  const todayCount = await countToday();
  return generateUnique(
    (attempt) => `${prefix}-${datePart}-${String(todayCount + 1 + attempt).padStart(4, "0")}`,
    exists,
  );
}
