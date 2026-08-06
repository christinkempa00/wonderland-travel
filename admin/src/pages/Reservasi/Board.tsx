import { useEffect, useState } from "react";
import type { DragEvent } from "react";
import { useNavigate } from "react-router-dom";
import { api, ApiError } from "../../lib/api";
import type { Reservasi, ReservasiStatus } from "../../lib/api";
import { useToast } from "../../context/ToastContext";
import { formatDate, formatIDR } from "../../lib/format";
import {
  RESERVASI_JENIS_LABEL,
  RESERVASI_STATUS_LABEL,
  RESERVASI_STATUS_ORDER,
} from "./constants";
import { ReservasiCreateModal } from "./CreateModal";

export function ReservasiBoard() {
  const { showToast } = useToast();
  const navigate = useNavigate();
  const [items, setItems] = useState<Reservasi[]>([]);
  const [loading, setLoading] = useState(true);
  const [search, setSearch] = useState("");
  const [debouncedSearch, setDebouncedSearch] = useState("");
  const [dragId, setDragId] = useState<string | null>(null);
  const [createOpen, setCreateOpen] = useState(false);

  useEffect(() => {
    const timer = setTimeout(() => setDebouncedSearch(search), 300);
    return () => clearTimeout(timer);
  }, [search]);

  async function loadItems() {
    setLoading(true);
    try {
      const res = await api.reservasi.list({ q: debouncedSearch || undefined, pageSize: 200 });
      setItems(res.items);
    } catch (err) {
      showToast(err instanceof ApiError ? err.message : "Gagal memuat data.", "error");
    } finally {
      setLoading(false);
    }
  }

  useEffect(() => {
    loadItems();
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, [debouncedSearch]);

  async function handleDrop(status: ReservasiStatus) {
    if (!dragId) return;
    const current = items.find((i) => i.id === dragId);
    setDragId(null);
    if (!current || current.status === status) return;

    setItems((prev) => prev.map((i) => (i.id === dragId ? { ...i, status } : i)));
    try {
      await api.reservasi.setStatus(dragId, status);
      showToast(`${current.nomorBooking} dipindah ke "${RESERVASI_STATUS_LABEL[status]}".`);
    } catch (err) {
      showToast(err instanceof ApiError ? err.message : "Gagal mengubah status.", "error");
      loadItems();
    }
  }

  function handleDragOver(event: DragEvent<HTMLDivElement>) {
    event.preventDefault();
  }

  return (
    <div className="flex h-full flex-col p-8">
      <div className="mb-6 flex flex-wrap items-center justify-between gap-4">
        <div>
          <h1 className="text-2xl font-bold text-heading">Papan Reservasi</h1>
          <p className="text-sm text-muted">{items.length} reservasi ditampilkan.</p>
        </div>
        <div className="flex items-center gap-3">
          <input
            type="search"
            placeholder="Cari no. booking atau nama..."
            value={search}
            onChange={(e) => setSearch(e.target.value)}
            className="w-64 rounded-lg border border-border px-3 py-2 text-sm focus:border-black focus:outline-none focus:ring-2 focus:ring-black/10"
          />
          <button
            type="button"
            onClick={() => setCreateOpen(true)}
            className="rounded-full bg-black px-4 py-2 text-sm font-semibold text-white transition-colors hover:bg-black/85"
          >
            + Buat Reservasi
          </button>
        </div>
      </div>

      {loading ? (
        <p className="text-sm text-muted">Memuat data...</p>
      ) : (
        <div className="flex flex-1 gap-4 overflow-x-auto pb-4">
          {RESERVASI_STATUS_ORDER.map((status) => {
            const columnItems = items.filter((i) => i.status === status);
            return (
              <div
                key={status}
                onDragOver={handleDragOver}
                onDrop={() => handleDrop(status)}
                className="flex w-72 shrink-0 flex-col rounded-xl bg-white/60"
              >
                <div className="flex items-center justify-between rounded-t-xl border border-b-0 border-border bg-white px-3 py-2.5">
                  <span className="text-sm font-bold text-heading">
                    {RESERVASI_STATUS_LABEL[status]}
                  </span>
                  <span className="rounded-full bg-mist px-2 py-0.5 text-xs font-semibold text-muted">
                    {columnItems.length}
                  </span>
                </div>
                <div className="flex min-h-24 flex-1 flex-col gap-2 rounded-b-xl border border-t-0 border-border bg-mist/40 p-2">
                  {columnItems.map((item) => (
                    <div
                      key={item.id}
                      draggable
                      onDragStart={() => setDragId(item.id)}
                      onClick={() => navigate(`/reservasi/${item.id}`)}
                      className="cursor-grab rounded-lg border border-border bg-white p-3 text-left shadow-sm transition-shadow hover:shadow-md active:cursor-grabbing"
                    >
                      <p className="font-mono text-xs text-muted">{item.nomorBooking}</p>
                      <p className="mt-1 text-sm font-semibold text-heading">{item.pelanggan.nama}</p>
                      <p className="text-xs text-muted">{RESERVASI_JENIS_LABEL[item.jenis]}</p>
                      <div className="mt-2 flex items-center justify-between text-xs text-muted">
                        <span>{formatDate(item.tanggalMulai)}</span>
                        {item.invoice && <span className="font-semibold">{formatIDR(item.invoice.total)}</span>}
                      </div>
                    </div>
                  ))}
                </div>
              </div>
            );
          })}
        </div>
      )}

      {createOpen && (
        <ReservasiCreateModal
          onClose={() => setCreateOpen(false)}
          onCreated={(item) => {
            setCreateOpen(false);
            navigate(`/reservasi/${item.id}`);
          }}
        />
      )}
    </div>
  );
}
