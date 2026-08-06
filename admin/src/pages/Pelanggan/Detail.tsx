import { useEffect, useState } from "react";
import { Link, useParams } from "react-router-dom";
import { api, ApiError } from "../../lib/api";
import type { Pelanggan, Reservasi } from "../../lib/api";
import { useToast } from "../../context/ToastContext";
import { formatDate, formatIDR } from "../../lib/format";
import { RESERVASI_STATUS_LABEL } from "../Reservasi/constants";

export function PelangganDetail() {
  const { id } = useParams();
  const { showToast } = useToast();
  const [item, setItem] = useState<(Pelanggan & { reservasi: Reservasi[] }) | null>(null);
  const [loading, setLoading] = useState(true);

  useEffect(() => {
    api.pelanggan
      .get(id!)
      .then((res) => setItem(res.item))
      .catch((err) => showToast(err instanceof ApiError ? err.message : "Gagal memuat data.", "error"))
      .finally(() => setLoading(false));
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, [id]);

  if (loading) return <div className="p-8 text-sm text-muted">Memuat data...</div>;
  if (!item) return <div className="p-8 text-sm text-muted">Pelanggan tidak ditemukan.</div>;

  const totalTransaksi = item.reservasi.reduce((sum, r) => sum + (r.invoice?.total ?? 0), 0);
  const totalDibayar = item.reservasi.reduce((sum, r) => sum + (r.invoice?.totalDibayar ?? 0), 0);

  return (
    <div className="mx-auto max-w-4xl p-8">
      <Link to="/pelanggan" className="text-sm text-muted hover:text-heading">
        ← Kembali ke daftar
      </Link>

      <div className="mt-2 flex flex-wrap items-start justify-between gap-4">
        <div>
          <h1 className="text-2xl font-bold text-heading">{item.nama}</h1>
          <p className="font-mono text-sm text-muted">{item.kodeKlien}</p>
        </div>
        <div className="grid grid-cols-2 gap-6 rounded-xl border border-border bg-white p-4 text-right">
          <div>
            <p className="text-xs uppercase tracking-wide text-muted">Total Transaksi</p>
            <p className="text-lg font-bold text-heading">{formatIDR(totalTransaksi)}</p>
          </div>
          <div>
            <p className="text-xs uppercase tracking-wide text-muted">Sudah Dibayar</p>
            <p className="text-lg font-bold text-heading">{formatIDR(totalDibayar)}</p>
          </div>
        </div>
      </div>

      <div className="mt-6 grid grid-cols-1 gap-4 rounded-xl border border-border bg-white p-6 sm:grid-cols-2">
        <div>
          <p className="text-xs uppercase tracking-wide text-muted">No. HP</p>
          <p className="text-sm text-heading">{item.noHp}</p>
        </div>
        <div>
          <p className="text-xs uppercase tracking-wide text-muted">Email</p>
          <p className="text-sm text-heading">{item.email ?? "-"}</p>
        </div>
        <div className="sm:col-span-2">
          <p className="text-xs uppercase tracking-wide text-muted">Alamat</p>
          <p className="text-sm text-heading">{item.alamat ?? "-"}</p>
        </div>
      </div>

      <h2 className="mb-3 mt-8 text-lg font-bold text-heading">Riwayat Reservasi</h2>
      <div className="overflow-x-auto rounded-xl border border-border bg-white">
        <table className="w-full text-left text-sm">
          <thead>
            <tr className="border-b border-border text-xs uppercase tracking-wide text-muted">
              <th className="px-4 py-3">No. Booking</th>
              <th className="px-4 py-3">Tanggal</th>
              <th className="px-4 py-3">Status</th>
              <th className="px-4 py-3">Invoice</th>
              <th className="px-4 py-3 text-right">Aksi</th>
            </tr>
          </thead>
          <tbody>
            {item.reservasi.length === 0 && (
              <tr>
                <td colSpan={5} className="px-4 py-8 text-center text-muted">
                  Belum ada reservasi.
                </td>
              </tr>
            )}
            {item.reservasi.map((r) => (
              <tr key={r.id} className="border-b border-border last:border-b-0">
                <td className="px-4 py-3 font-mono text-xs text-heading">{r.nomorBooking}</td>
                <td className="px-4 py-3 text-body">{formatDate(r.tanggalMulai)}</td>
                <td className="px-4 py-3 text-body">{RESERVASI_STATUS_LABEL[r.status]}</td>
                <td className="px-4 py-3 text-body">
                  {r.invoice ? formatIDR(r.invoice.total) : "-"}
                </td>
                <td className="px-4 py-3 text-right">
                  <Link to={`/reservasi/${r.id}`} className="text-sm font-semibold text-heading hover:underline">
                    Lihat
                  </Link>
                </td>
              </tr>
            ))}
          </tbody>
        </table>
      </div>
    </div>
  );
}
