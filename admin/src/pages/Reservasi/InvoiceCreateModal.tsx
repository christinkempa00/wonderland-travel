import { useState } from "react";
import type { FormEvent } from "react";
import { api, ApiError } from "../../lib/api";
import type { Invoice } from "../../lib/api";
import { useToast } from "../../context/ToastContext";
import { formatIDR } from "../../lib/format";

interface InvoiceCreateModalProps {
  reservasiId: string;
  onClose: () => void;
  onCreated: (invoice: Invoice) => void;
}

interface ItemRow {
  nama: string;
  qty: number;
  hargaSatuan: number;
}

export function InvoiceCreateModal({ reservasiId, onClose, onCreated }: InvoiceCreateModalProps) {
  const { showToast } = useToast();
  const [items, setItems] = useState<ItemRow[]>([{ nama: "", qty: 1, hargaSatuan: 0 }]);
  const [diskon, setDiskon] = useState(0);
  const [jatuhTempo, setJatuhTempo] = useState("");
  const [saving, setSaving] = useState(false);

  const subtotal = items.reduce((sum, item) => sum + item.qty * item.hargaSatuan, 0);
  const total = Math.max(subtotal - diskon, 0);

  function updateItem(index: number, patch: Partial<ItemRow>) {
    setItems((prev) => prev.map((item, i) => (i === index ? { ...item, ...patch } : item)));
  }

  function addItem() {
    setItems((prev) => [...prev, { nama: "", qty: 1, hargaSatuan: 0 }]);
  }

  function removeItem(index: number) {
    setItems((prev) => prev.filter((_, i) => i !== index));
  }

  async function handleSubmit(event: FormEvent<HTMLFormElement>) {
    event.preventDefault();
    if (!jatuhTempo) {
      showToast("Tanggal jatuh tempo wajib diisi.", "error");
      return;
    }
    const validItems = items.filter((item) => item.nama.trim() && item.hargaSatuan > 0);
    if (validItems.length === 0) {
      showToast("Minimal 1 item dengan nama dan harga valid.", "error");
      return;
    }

    setSaving(true);
    try {
      const { item } = await api.invoice.create({
        reservasiId,
        items: validItems,
        diskon,
        jatuhTempo,
      });
      showToast(`Invoice ${item.nomorInvoice} dibuat.`);
      onCreated(item);
    } catch (err) {
      showToast(err instanceof ApiError ? err.message : "Gagal membuat invoice.", "error");
    } finally {
      setSaving(false);
    }
  }

  return (
    <div className="fixed inset-0 z-50 flex items-center justify-center p-4">
      <div className="absolute inset-0 bg-black/50" onClick={onClose} />
      <form
        onSubmit={handleSubmit}
        className="relative flex max-h-[90vh] w-full max-w-lg flex-col gap-4 overflow-y-auto rounded-2xl bg-white p-6 shadow-xl"
      >
        <h2 className="text-lg font-bold text-heading">Buat Invoice</h2>

        <div className="flex flex-col gap-2">
          {items.map((item, index) => (
            <div key={index} className="flex gap-2">
              <input
                placeholder="Nama item"
                value={item.nama}
                onChange={(e) => updateItem(index, { nama: e.target.value })}
                className="input flex-1"
              />
              <input
                type="number"
                min={1}
                placeholder="Qty"
                value={item.qty}
                onChange={(e) => updateItem(index, { qty: Number(e.target.value) })}
                className="input w-16"
              />
              <input
                type="number"
                min={0}
                placeholder="Harga satuan"
                value={item.hargaSatuan}
                onChange={(e) => updateItem(index, { hargaSatuan: Number(e.target.value) })}
                className="input w-32"
              />
              <button
                type="button"
                onClick={() => removeItem(index)}
                className="rounded-lg border border-border px-2 text-sm text-muted hover:bg-mist"
              >
                ×
              </button>
            </div>
          ))}
          <button
            type="button"
            onClick={addItem}
            className="self-start text-sm font-semibold text-heading hover:underline"
          >
            + Tambah item
          </button>
        </div>

        <div className="grid grid-cols-2 gap-3">
          <div className="flex flex-col gap-1.5">
            <label className="text-sm font-medium text-heading">Diskon (Rp)</label>
            <input
              type="number"
              min={0}
              value={diskon}
              onChange={(e) => setDiskon(Number(e.target.value))}
              className="input"
            />
          </div>
          <div className="flex flex-col gap-1.5">
            <label className="text-sm font-medium text-heading">Jatuh Tempo</label>
            <input
              required
              type="date"
              value={jatuhTempo}
              onChange={(e) => setJatuhTempo(e.target.value)}
              className="input"
            />
          </div>
        </div>

        <div className="flex items-center justify-between rounded-lg bg-mist px-4 py-3 text-sm">
          <span className="text-muted">Subtotal {formatIDR(subtotal)} − Diskon {formatIDR(diskon)}</span>
          <span className="text-lg font-bold text-heading">{formatIDR(total)}</span>
        </div>

        <div className="flex justify-end gap-3 border-t border-border pt-4">
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
            {saving ? "Menyimpan..." : "Buat Invoice"}
          </button>
        </div>
      </form>
    </div>
  );
}
