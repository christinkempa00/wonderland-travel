import { useEffect, useState } from "react";
import { Link, useParams } from "react-router-dom";
import { api, ApiError } from "../../lib/api";
import type { Invoice, MetodePembayaran } from "../../lib/api";
import { useToast } from "../../context/ToastContext";
import { ConfirmDialog } from "../../components/ConfirmDialog";
import { formatDate, formatIDR } from "../../lib/format";
import { PaymentModal } from "./PaymentModal";

const METODE_LABEL: Record<MetodePembayaran, string> = {
  TRANSFER_BANK: "Transfer Bank",
  E_WALLET: "E-Wallet",
  KARTU_KREDIT: "Kartu Kredit",
  TUNAI: "Tunai",
  LAINNYA: "Lainnya",
};

export function InvoiceDetail() {
  const { id } = useParams();
  const { showToast } = useToast();
  const [item, setItem] = useState<Invoice | null>(null);
  const [loading, setLoading] = useState(true);
  const [paymentModalOpen, setPaymentModalOpen] = useState(false);
  const [deleteTargetId, setDeleteTargetId] = useState<string | null>(null);

  async function load() {
    try {
      const res = await api.invoice.get(id!);
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

  async function handleDeletePembayaran() {
    if (!item || !deleteTargetId) return;
    try {
      await api.invoice.removePembayaran(item.id, deleteTargetId);
      showToast("Pembayaran dihapus.");
      setDeleteTargetId(null);
      load();
    } catch (err) {
      showToast(err instanceof ApiError ? err.message : "Gagal menghapus.", "error");
    }
  }

  if (loading) return <div className="p-8 text-sm text-muted">Memuat data...</div>;
  if (!item) return <div className="p-8 text-sm text-muted">Invoice tidak ditemukan.</div>;

  return (
    <div className="mx-auto max-w-3xl p-8">
      <Link to={`/reservasi/${item.reservasiId}`} className="text-sm text-muted hover:text-heading">
        ← Kembali ke reservasi
      </Link>

      <div className="mt-2 flex flex-wrap items-start justify-between gap-4">
        <div>
          <h1 className="font-mono text-2xl font-bold text-heading">{item.nomorInvoice}</h1>
          <p className="text-sm text-muted">
            {item.reservasi?.pelanggan.nama} · {item.reservasi?.pelanggan.kodeKlien}
          </p>
        </div>
        <a
          href={api.invoice.pdfUrl(item.id)}
          target="_blank"
          rel="noopener noreferrer"
          className="rounded-full border border-border px-4 py-2 text-sm font-semibold text-heading hover:bg-mist"
        >
          Download PDF
        </a>
      </div>

      <div className="mt-6 overflow-hidden rounded-xl border border-border bg-white">
        <table className="w-full text-left text-sm">
          <thead>
            <tr className="border-b border-border text-xs uppercase tracking-wide text-muted">
              <th className="px-4 py-3">Item</th>
              <th className="px-4 py-3">Qty</th>
              <th className="px-4 py-3">Harga</th>
              <th className="px-4 py-3">Subtotal</th>
            </tr>
          </thead>
          <tbody>
            {item.items.map((line) => (
              <tr key={line.id} className="border-b border-border last:border-b-0">
                <td className="px-4 py-3 text-heading">{line.nama}</td>
                <td className="px-4 py-3 text-body">{line.qty}</td>
                <td className="px-4 py-3 text-body">{formatIDR(line.hargaSatuan)}</td>
                <td className="px-4 py-3 text-body">{formatIDR(line.qty * line.hargaSatuan)}</td>
              </tr>
            ))}
          </tbody>
        </table>
        <div className="flex flex-col gap-1 border-t border-border px-4 py-4 text-sm">
          <div className="flex justify-between text-muted">
            <span>Subtotal</span>
            <span>{formatIDR(item.subtotal)}</span>
          </div>
          {item.diskon > 0 && (
            <div className="flex justify-between text-muted">
              <span>Diskon</span>
              <span>- {formatIDR(item.diskon)}</span>
            </div>
          )}
          <div className="flex justify-between text-base font-bold text-heading">
            <span>Total</span>
            <span>{formatIDR(item.total)}</span>
          </div>
          <div className="flex justify-between text-muted">
            <span>Sudah Dibayar</span>
            <span>{formatIDR(item.totalDibayar)}</span>
          </div>
          <div className="flex justify-between font-semibold text-heading">
            <span>Sisa Tagihan</span>
            <span>{formatIDR(item.sisaTagihan)}</span>
          </div>
          <div className="flex justify-between text-xs text-muted">
            <span>Jatuh Tempo</span>
            <span>{formatDate(item.jatuhTempo)}</span>
          </div>
        </div>
      </div>

      <div className="mb-3 mt-8 flex items-center justify-between">
        <h2 className="text-lg font-bold text-heading">Riwayat Pembayaran</h2>
        {item.sisaTagihan > 0 && (
          <button
            type="button"
            onClick={() => setPaymentModalOpen(true)}
            className="rounded-full bg-black px-4 py-2 text-sm font-semibold text-white hover:bg-black/85"
          >
            + Catat Pembayaran
          </button>
        )}
      </div>

      <div className="overflow-x-auto rounded-xl border border-border bg-white">
        <table className="w-full text-left text-sm">
          <thead>
            <tr className="border-b border-border text-xs uppercase tracking-wide text-muted">
              <th className="px-4 py-3">Tanggal</th>
              <th className="px-4 py-3">Jumlah</th>
              <th className="px-4 py-3">Metode</th>
              <th className="px-4 py-3">Bukti</th>
              <th className="px-4 py-3">Catatan</th>
              <th className="px-4 py-3 text-right">Aksi</th>
            </tr>
          </thead>
          <tbody>
            {item.pembayaran.length === 0 && (
              <tr>
                <td colSpan={6} className="px-4 py-8 text-center text-muted">
                  Belum ada pembayaran tercatat.
                </td>
              </tr>
            )}
            {item.pembayaran.map((p) => (
              <tr key={p.id} className="border-b border-border last:border-b-0">
                <td className="px-4 py-3 text-body">{formatDate(p.tanggal)}</td>
                <td className="px-4 py-3 font-semibold text-heading">{formatIDR(p.jumlah)}</td>
                <td className="px-4 py-3 text-body">{METODE_LABEL[p.metode]}</td>
                <td className="px-4 py-3">
                  {p.buktiUrl ? (
                    <a href={p.buktiUrl} target="_blank" rel="noopener noreferrer" className="text-heading hover:underline">
                      Lihat
                    </a>
                  ) : (
                    "-"
                  )}
                </td>
                <td className="px-4 py-3 text-body">{p.catatan ?? "-"}</td>
                <td className="px-4 py-3 text-right">
                  <button
                    type="button"
                    onClick={() => setDeleteTargetId(p.id)}
                    className="text-sm font-semibold text-red-600 hover:underline"
                  >
                    Hapus
                  </button>
                </td>
              </tr>
            ))}
          </tbody>
        </table>
      </div>

      {paymentModalOpen && (
        <PaymentModal
          invoiceId={item.id}
          maxJumlah={item.sisaTagihan}
          onClose={() => setPaymentModalOpen(false)}
          onSaved={() => {
            setPaymentModalOpen(false);
            load();
          }}
        />
      )}

      <ConfirmDialog
        open={deleteTargetId !== null}
        title="Hapus pembayaran ini?"
        description="Status invoice akan dihitung ulang setelah pembayaran dihapus."
        onConfirm={handleDeletePembayaran}
        onCancel={() => setDeleteTargetId(null)}
      />
    </div>
  );
}
