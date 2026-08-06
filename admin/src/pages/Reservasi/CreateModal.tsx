import { useEffect, useState } from "react";
import type { FormEvent } from "react";
import { api, ApiError } from "../../lib/api";
import type { Pelanggan, Reservasi, ReservasiJenis } from "../../lib/api";
import { useToast } from "../../context/ToastContext";
import { RESERVASI_JENIS_LABEL } from "./constants";

interface ReservasiCreateModalProps {
  onClose: () => void;
  onCreated: (item: Reservasi) => void;
  defaultPelangganId?: string;
}

export function ReservasiCreateModal({ onClose, onCreated, defaultPelangganId }: ReservasiCreateModalProps) {
  const { showToast } = useToast();
  const [pelangganMode, setPelangganMode] = useState<"existing" | "new">(
    defaultPelangganId ? "existing" : "new",
  );
  const [pelangganQuery, setPelangganQuery] = useState("");
  const [pelangganResults, setPelangganResults] = useState<Pelanggan[]>([]);
  const [selectedPelanggan, setSelectedPelanggan] = useState<Pelanggan | null>(null);
  const [pelangganBaru, setPelangganBaru] = useState({ nama: "", noHp: "", email: "" });

  const [jenis, setJenis] = useState<ReservasiJenis>("PAKET_WISATA");
  const [deskripsi, setDeskripsi] = useState("");
  const [tanggalMulai, setTanggalMulai] = useState("");
  const [tanggalSelesai, setTanggalSelesai] = useState("");
  const [catatan, setCatatan] = useState("");
  const [saving, setSaving] = useState(false);

  useEffect(() => {
    if (!defaultPelangganId) return;
    api.pelanggan.get(defaultPelangganId).then((res) => setSelectedPelanggan(res.item));
  }, [defaultPelangganId]);

  useEffect(() => {
    if (pelangganMode !== "existing" || pelangganQuery.trim().length < 2) {
      setPelangganResults([]);
      return;
    }
    const timer = setTimeout(() => {
      api.pelanggan.list({ q: pelangganQuery, pageSize: 5 }).then((res) => setPelangganResults(res.items));
    }, 300);
    return () => clearTimeout(timer);
  }, [pelangganQuery, pelangganMode]);

  async function handleSubmit(event: FormEvent<HTMLFormElement>) {
    event.preventDefault();

    if (pelangganMode === "existing" && !selectedPelanggan) {
      showToast("Pilih pelanggan terlebih dahulu.", "error");
      return;
    }
    if (pelangganMode === "new" && (!pelangganBaru.nama || !pelangganBaru.noHp)) {
      showToast("Nama dan No. HP pelanggan wajib diisi.", "error");
      return;
    }
    if (!tanggalMulai) {
      showToast("Tanggal mulai wajib diisi.", "error");
      return;
    }

    setSaving(true);
    try {
      const { item } = await api.reservasi.create({
        pelangganId: pelangganMode === "existing" ? selectedPelanggan!.id : undefined,
        pelangganBaru: pelangganMode === "new" ? pelangganBaru : undefined,
        jenis,
        itemRingkasan: { deskripsi },
        tanggalMulai,
        tanggalSelesai: tanggalSelesai || null,
        catatan: catatan || null,
      });
      showToast(`Reservasi ${item.nomorBooking} dibuat.`);
      onCreated(item);
    } catch (err) {
      showToast(err instanceof ApiError ? err.message : "Gagal membuat reservasi.", "error");
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
        <h2 className="text-lg font-bold text-heading">Buat Reservasi Baru</h2>

        <div>
          <div className="mb-2 flex gap-2">
            <button
              type="button"
              onClick={() => setPelangganMode("new")}
              className={`rounded-full px-3 py-1 text-xs font-semibold ${pelangganMode === "new" ? "bg-black text-white" : "bg-mist text-muted"}`}
            >
              Pelanggan Baru
            </button>
            <button
              type="button"
              onClick={() => setPelangganMode("existing")}
              className={`rounded-full px-3 py-1 text-xs font-semibold ${pelangganMode === "existing" ? "bg-black text-white" : "bg-mist text-muted"}`}
            >
              Pelanggan Terdaftar
            </button>
          </div>

          {pelangganMode === "new" ? (
            <div className="grid grid-cols-1 gap-3 sm:grid-cols-2">
              <input
                required
                placeholder="Nama"
                value={pelangganBaru.nama}
                onChange={(e) => setPelangganBaru({ ...pelangganBaru, nama: e.target.value })}
                className="input"
              />
              <input
                required
                placeholder="No. HP"
                value={pelangganBaru.noHp}
                onChange={(e) => setPelangganBaru({ ...pelangganBaru, noHp: e.target.value })}
                className="input"
              />
              <input
                type="email"
                placeholder="Email (opsional)"
                value={pelangganBaru.email}
                onChange={(e) => setPelangganBaru({ ...pelangganBaru, email: e.target.value })}
                className="input sm:col-span-2"
              />
            </div>
          ) : (
            <div>
              {selectedPelanggan ? (
                <div className="flex items-center justify-between rounded-lg border border-border bg-mist px-3 py-2 text-sm">
                  <span>
                    <strong>{selectedPelanggan.nama}</strong> · {selectedPelanggan.kodeKlien}
                  </span>
                  <button
                    type="button"
                    onClick={() => setSelectedPelanggan(null)}
                    className="text-xs font-semibold text-muted hover:text-heading"
                  >
                    Ganti
                  </button>
                </div>
              ) : (
                <div className="relative">
                  <input
                    placeholder="Cari nama / No. HP pelanggan..."
                    value={pelangganQuery}
                    onChange={(e) => setPelangganQuery(e.target.value)}
                    className="input w-full"
                  />
                  {pelangganResults.length > 0 && (
                    <div className="absolute z-10 mt-1 w-full rounded-lg border border-border bg-white shadow-lg">
                      {pelangganResults.map((p) => (
                        <button
                          type="button"
                          key={p.id}
                          onClick={() => {
                            setSelectedPelanggan(p);
                            setPelangganResults([]);
                          }}
                          className="block w-full px-3 py-2 text-left text-sm hover:bg-mist"
                        >
                          {p.nama} · {p.noHp}
                        </button>
                      ))}
                    </div>
                  )}
                </div>
              )}
            </div>
          )}
        </div>

        <div className="flex flex-col gap-1.5">
          <label className="text-sm font-medium text-heading">Jenis</label>
          <select
            value={jenis}
            onChange={(e) => setJenis(e.target.value as ReservasiJenis)}
            className="input"
          >
            {Object.entries(RESERVASI_JENIS_LABEL).map(([value, label]) => (
              <option key={value} value={value}>
                {label}
              </option>
            ))}
          </select>
        </div>

        <div className="flex flex-col gap-1.5">
          <label className="text-sm font-medium text-heading">Ringkasan Item</label>
          <input
            placeholder="mis. Paket Bali Highlights 4D3N"
            value={deskripsi}
            onChange={(e) => setDeskripsi(e.target.value)}
            className="input"
          />
        </div>

        <div className="grid grid-cols-2 gap-3">
          <div className="flex flex-col gap-1.5">
            <label className="text-sm font-medium text-heading">Tanggal Mulai</label>
            <input
              required
              type="date"
              value={tanggalMulai}
              onChange={(e) => setTanggalMulai(e.target.value)}
              className="input"
            />
          </div>
          <div className="flex flex-col gap-1.5">
            <label className="text-sm font-medium text-heading">Tanggal Selesai</label>
            <input
              type="date"
              value={tanggalSelesai}
              onChange={(e) => setTanggalSelesai(e.target.value)}
              className="input"
            />
          </div>
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
            {saving ? "Menyimpan..." : "Buat Reservasi"}
          </button>
        </div>
      </form>
    </div>
  );
}
