import { useState } from "react";
import type { FormEvent } from "react";
import { api, ApiError } from "../../lib/api";
import type { MetodePembayaran, Pembayaran } from "../../lib/api";
import { useToast } from "../../context/ToastContext";

const METODE_LABEL: Record<MetodePembayaran, string> = {
  TRANSFER_BANK: "Transfer Bank",
  E_WALLET: "E-Wallet",
  KARTU_KREDIT: "Kartu Kredit",
  TUNAI: "Tunai",
  LAINNYA: "Lainnya",
};

interface PaymentModalProps {
  invoiceId: string;
  maxJumlah: number;
  onClose: () => void;
  onSaved: (pembayaran: Pembayaran) => void;
}

export function PaymentModal({ invoiceId, maxJumlah, onClose, onSaved }: PaymentModalProps) {
  const { showToast } = useToast();
  const [jumlah, setJumlah] = useState(maxJumlah);
  const [metode, setMetode] = useState<MetodePembayaran>("TRANSFER_BANK");
  const [tanggal, setTanggal] = useState(() => new Date().toISOString().slice(0, 10));
  const [catatan, setCatatan] = useState("");
  const [bukti, setBukti] = useState<File | null>(null);
  const [saving, setSaving] = useState(false);

  async function handleSubmit(event: FormEvent<HTMLFormElement>) {
    event.preventDefault();
    if (jumlah <= 0) {
      showToast("Jumlah harus lebih dari 0.", "error");
      return;
    }
    setSaving(true);
    try {
      const { pembayaran } = await api.invoice.addPembayaran(
        invoiceId,
        { jumlah, metode, tanggal, catatan: catatan || undefined },
        bukti ?? undefined,
      );
      showToast("Pembayaran dicatat.");
      onSaved(pembayaran);
    } catch (err) {
      showToast(err instanceof ApiError ? err.message : "Gagal mencatat pembayaran.", "error");
    } finally {
      setSaving(false);
    }
  }

  return (
    <div className="fixed inset-0 z-50 flex items-center justify-center p-4">
      <div className="absolute inset-0 bg-black/50" onClick={onClose} />
      <form
        onSubmit={handleSubmit}
        className="relative flex w-full max-w-md flex-col gap-4 rounded-2xl bg-white p-6 shadow-xl"
      >
        <h2 className="text-lg font-bold text-heading">Catat Pembayaran</h2>

        <div className="flex flex-col gap-1.5">
          <label className="text-sm font-medium text-heading">Jumlah (Rp)</label>
          <input
            required
            type="number"
            min={1}
            value={jumlah}
            onChange={(e) => setJumlah(Number(e.target.value))}
            className="input"
          />
          <p className="text-xs text-muted">Sisa tagihan saat ini: Rp {maxJumlah.toLocaleString("id-ID")}</p>
        </div>

        <div className="flex flex-col gap-1.5">
          <label className="text-sm font-medium text-heading">Metode</label>
          <select value={metode} onChange={(e) => setMetode(e.target.value as MetodePembayaran)} className="input">
            {Object.entries(METODE_LABEL).map(([value, label]) => (
              <option key={value} value={value}>
                {label}
              </option>
            ))}
          </select>
        </div>

        <div className="flex flex-col gap-1.5">
          <label className="text-sm font-medium text-heading">Tanggal</label>
          <input
            required
            type="date"
            value={tanggal}
            onChange={(e) => setTanggal(e.target.value)}
            className="input"
          />
        </div>

        <div className="flex flex-col gap-1.5">
          <label className="text-sm font-medium text-heading">Bukti Bayar (opsional)</label>
          <input
            type="file"
            accept="image/*"
            onChange={(e) => setBukti(e.target.files?.[0] ?? null)}
            className="text-sm"
          />
        </div>

        <div className="flex flex-col gap-1.5">
          <label className="text-sm font-medium text-heading">Catatan (opsional)</label>
          <textarea
            rows={2}
            value={catatan}
            onChange={(e) => setCatatan(e.target.value)}
            className="input resize-none"
          />
        </div>

        <div className="mt-2 flex justify-end gap-3 border-t border-border pt-4">
          <button
            type="button"
            onClick={onClose}
            className="rounded-full border border-border px-4 py-2 text-sm font-semibold text-heading hover:bg-mist"
          >
            Batal
          </button>
          <button
            type="submit"
            disabled={saving}
            className="rounded-full bg-black px-4 py-2 text-sm font-semibold text-white hover:bg-black/85 disabled:opacity-50"
          >
            {saving ? "Menyimpan..." : "Simpan"}
          </button>
        </div>
      </form>
    </div>
  );
}
