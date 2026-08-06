import { useEffect, useState } from "react";
import { Link, useNavigate, useParams } from "react-router-dom";
import { api, ApiError } from "../../lib/api";
import type { Reservasi, ReservasiStatus } from "../../lib/api";
import { useToast } from "../../context/ToastContext";
import { ConfirmDialog } from "../../components/ConfirmDialog";
import { formatDate, formatIDR } from "../../lib/format";
import {
  RESERVASI_JENIS_LABEL,
  RESERVASI_STATUS_LABEL,
  RESERVASI_STATUS_ORDER,
  RESERVASI_SUMBER_LABEL,
} from "./constants";
import { InvoiceCreateModal } from "./InvoiceCreateModal";

export function ReservasiDetail() {
  const { id } = useParams();
  const navigate = useNavigate();
  const { showToast } = useToast();
  const [item, setItem] = useState<Reservasi | null>(null);
  const [loading, setLoading] = useState(true);
  const [invoiceModalOpen, setInvoiceModalOpen] = useState(false);
  const [deleteOpen, setDeleteOpen] = useState(false);

  async function load() {
    try {
      const res = await api.reservasi.get(id!);
      setItem(res.item);
    } catch (err) {
      showToast(err instanceof ApiError ? err.message : "Gagal memuat data.", "error");
    } finally {
      setLoading(false);
    }
  }

  useEffect(() => {
    load();
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, [id]);

  async function handleStatusChange(status: ReservasiStatus) {
    if (!item) return;
    try {
      const res = await api.reservasi.setStatus(item.id, status);
      setItem(res.item);
      showToast(`Status diubah ke "${RESERVASI_STATUS_LABEL[status]}".`);
    } catch (err) {
      showToast(err instanceof ApiError ? err.message : "Gagal mengubah status.", "error");
    }
  }

  async function handleDelete() {
    if (!item) return;
    try {
      await api.reservasi.remove(item.id);
      showToast("Reservasi dihapus.");
      navigate("/reservasi");
    } catch (err) {
      showToast(err instanceof ApiError ? err.message : "Gagal menghapus.", "error");
      setDeleteOpen(false);
    }
  }

  if (loading) return <div className="p-8 text-sm text-muted">Memuat data...</div>;
  if (!item) return <div className="p-8 text-sm text-muted">Reservasi tidak ditemukan.</div>;

  const ringkasan =
    typeof item.itemRingkasan === "object" &&
    item.itemRingkasan !== null &&
    "deskripsi" in item.itemRingkasan
      ? String((item.itemRingkasan as { deskripsi?: string }).deskripsi ?? "")
      : JSON.stringify(item.itemRingkasan);

  return (
    <div className="mx-auto max-w-3xl p-8">
      <Link to="/reservasi" className="text-sm text-muted hover:text-heading">
        ← Kembali ke papan reservasi
      </Link>

      <div className="mt-2 flex flex-wrap items-start justify-between gap-4">
        <div>
          <h1 className="font-mono text-2xl font-bold text-heading">{item.nomorBooking}</h1>
          <p className="text-sm text-muted">
            Dibuat {formatDate(item.createdAt)} · Sumber: {RESERVASI_SUMBER_LABEL[item.sumber]}
          </p>
        </div>
        <button
          type="button"
          onClick={() => setDeleteOpen(true)}
          className="text-sm font-semibold text-red-600 hover:underline"
        >
          Hapus Reservasi
        </button>
      </div>

      <div className="mt-4 flex flex-wrap gap-2">
        {RESERVASI_STATUS_ORDER.map((status) => (
          <button
            key={status}
            type="button"
            onClick={() => handleStatusChange(status)}
            className={`rounded-full px-3 py-1.5 text-xs font-semibold transition-colors ${
              item.status === status
                ? "bg-black text-white"
                : "bg-white text-muted ring-1 ring-inset ring-border hover:text-heading"
            }`}
          >
            {RESERVASI_STATUS_LABEL[status]}
          </button>
        ))}
      </div>

      <div className="mt-6 grid grid-cols-1 gap-4 rounded-xl border border-border bg-white p-6 sm:grid-cols-2">
        <div>
          <p className="text-xs uppercase tracking-wide text-muted">Pelanggan</p>
          <Link to={`/pelanggan/${item.pelanggan.id}`} className="text-sm font-semibold text-heading hover:underline">
            {item.pelanggan.nama}
          </Link>
          <p className="text-xs text-muted">
            {item.pelanggan.kodeKlien} · {item.pelanggan.noHp}
          </p>
        </div>
        <div>
          <p className="text-xs uppercase tracking-wide text-muted">Jenis</p>
          <p className="text-sm text-heading">{RESERVASI_JENIS_LABEL[item.jenis]}</p>
        </div>
        <div>
          <p className="text-xs uppercase tracking-wide text-muted">Tanggal Mulai</p>
          <p className="text-sm text-heading">{formatDate(item.tanggalMulai)}</p>
        </div>
        <div>
          <p className="text-xs uppercase tracking-wide text-muted">Tanggal Selesai</p>
          <p className="text-sm text-heading">
            {item.tanggalSelesai ? formatDate(item.tanggalSelesai) : "-"}
          </p>
        </div>
        <div className="sm:col-span-2">
          <p className="text-xs uppercase tracking-wide text-muted">Ringkasan Item</p>
          <p className="text-sm text-heading">{ringkasan || "-"}</p>
        </div>
        {item.catatan && (
          <div className="sm:col-span-2">
            <p className="text-xs uppercase tracking-wide text-muted">Catatan</p>
            <p className="text-sm text-heading">{item.catatan}</p>
          </div>
        )}
      </div>

      <h2 className="mb-3 mt-8 text-lg font-bold text-heading">Invoice</h2>
      {item.invoice ? (
        <div className="flex items-center justify-between rounded-xl border border-border bg-white p-6">
          <div>
            <p className="font-mono text-sm font-semibold text-heading">{item.invoice.nomorInvoice}</p>
            <p className="text-xs text-muted">
              Total {formatIDR(item.invoice.total)} · Sisa {formatIDR(item.invoice.sisaTagihan)}
            </p>
          </div>
          <Link
            to={`/invoice/${item.invoice.id}`}
            className="rounded-full bg-black px-4 py-2 text-sm font-semibold text-white hover:bg-black/85"
          >
            Lihat Invoice
          </Link>
        </div>
      ) : (
        <div className="flex items-center justify-between rounded-xl border border-dashed border-border bg-white p-6">
          <p className="text-sm text-muted">Reservasi ini belum punya invoice.</p>
          <button
            type="button"
            onClick={() => setInvoiceModalOpen(true)}
            className="rounded-full bg-black px-4 py-2 text-sm font-semibold text-white hover:bg-black/85"
          >
            + Buat Invoice
          </button>
        </div>
      )}

      {invoiceModalOpen && (
        <InvoiceCreateModal
          reservasiId={item.id}
          onClose={() => setInvoiceModalOpen(false)}
          onCreated={(invoice) => {
            setInvoiceModalOpen(false);
            navigate(`/invoice/${invoice.id}`);
          }}
        />
      )}

      <ConfirmDialog
        open={deleteOpen}
        title={`Hapus reservasi ${item.nomorBooking}?`}
        description="Tindakan ini tidak bisa dibatalkan."
        onConfirm={handleDelete}
        onCancel={() => setDeleteOpen(false)}
      />
    </div>
  );
}
