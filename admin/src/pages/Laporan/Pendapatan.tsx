import { useEffect, useState } from "react";
import { api, ApiError } from "../../lib/api";
import type { LaporanPendapatan } from "../../lib/api";
import { useToast } from "../../context/ToastContext";
import { formatIDR } from "../../lib/format";

function defaultFrom(): string {
  const d = new Date();
  d.setMonth(d.getMonth() - 5);
  d.setDate(1);
  return d.toISOString().slice(0, 10);
}

function defaultTo(): string {
  return new Date().toISOString().slice(0, 10);
}

export function LaporanPendapatanPage() {
  const { showToast } = useToast();
  const [from, setFrom] = useState(defaultFrom());
  const [to, setTo] = useState(defaultTo());
  const [groupBy, setGroupBy] = useState<"day" | "month">("month");
  const [report, setReport] = useState<LaporanPendapatan | null>(null);
  const [loading, setLoading] = useState(true);

  async function load() {
    setLoading(true);
    try {
      const res = await api.laporan.pendapatan({ from, to, groupBy });
      setReport(res);
    } catch (err) {
      showToast(err instanceof ApiError ? err.message : "Gagal memuat laporan.", "error");
    } finally {
      setLoading(false);
    }
  }

  useEffect(() => {
    load();
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, []);

  const maxTotal = report ? Math.max(...report.data.map((d) => d.total), 1) : 1;

  return (
    <div className="p-8">
      <h1 className="text-2xl font-bold text-heading">Laporan Pendapatan</h1>
      <p className="mt-1 text-sm text-muted">Rekap pembayaran yang tercatat, dikelompokkan per periode.</p>

      <div className="mt-6 flex flex-wrap items-end gap-3">
        <div className="flex flex-col gap-1.5">
          <label className="text-sm font-medium text-heading">Dari</label>
          <input type="date" value={from} onChange={(e) => setFrom(e.target.value)} className="input" />
        </div>
        <div className="flex flex-col gap-1.5">
          <label className="text-sm font-medium text-heading">Sampai</label>
          <input type="date" value={to} onChange={(e) => setTo(e.target.value)} className="input" />
        </div>
        <div className="flex flex-col gap-1.5">
          <label className="text-sm font-medium text-heading">Kelompokkan</label>
          <select value={groupBy} onChange={(e) => setGroupBy(e.target.value as "day" | "month")} className="input">
            <option value="month">Per Bulan</option>
            <option value="day">Per Hari</option>
          </select>
        </div>
        <button
          type="button"
          onClick={load}
          className="rounded-full bg-black px-4 py-2 text-sm font-semibold text-white hover:bg-black/85"
        >
          Terapkan
        </button>
      </div>

      {loading ? (
        <p className="mt-8 text-sm text-muted">Memuat laporan...</p>
      ) : report ? (
        <>
          <div className="mt-6 rounded-xl border border-border bg-white p-6">
            <p className="text-xs uppercase tracking-wide text-muted">Total Pendapatan</p>
            <p className="text-3xl font-bold text-heading">{formatIDR(report.totalPendapatan)}</p>
          </div>

          <div className="mt-6 rounded-xl border border-border bg-white p-6">
            {report.data.length === 0 ? (
              <p className="text-sm text-muted">Belum ada pembayaran pada rentang ini.</p>
            ) : (
              <div className="flex items-end gap-3" style={{ height: 200 }}>
                {report.data.map((d) => (
                  <div key={d.period} className="flex flex-1 flex-col items-center gap-2">
                    <div
                      className="w-full rounded-t-md bg-black"
                      style={{ height: `${(d.total / maxTotal) * 160}px` }}
                      title={formatIDR(d.total)}
                    />
                    <span className="text-[10px] text-muted">{d.period}</span>
                  </div>
                ))}
              </div>
            )}
          </div>

          <div className="mt-6 overflow-x-auto rounded-xl border border-border bg-white">
            <table className="w-full text-left text-sm">
              <thead>
                <tr className="border-b border-border text-xs uppercase tracking-wide text-muted">
                  <th className="px-4 py-3">Periode</th>
                  <th className="px-4 py-3">Pendapatan</th>
                </tr>
              </thead>
              <tbody>
                {report.data.map((d) => (
                  <tr key={d.period} className="border-b border-border last:border-b-0">
                    <td className="px-4 py-3 text-heading">{d.period}</td>
                    <td className="px-4 py-3 text-body">{formatIDR(d.total)}</td>
                  </tr>
                ))}
              </tbody>
            </table>
          </div>
        </>
      ) : null}
    </div>
  );
}
