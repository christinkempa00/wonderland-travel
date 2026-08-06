import { useEffect, useState } from "react";
import { Link } from "react-router-dom";
import { api, ApiError } from "../../lib/api";
import type { Invoice, StatusBayar } from "../../lib/api";
import { useToast } from "../../context/ToastContext";
import { formatDate, formatIDR } from "../../lib/format";

const STATUS_LABEL: Record<StatusBayar, string> = {
  BELUM_BAYAR: "Belum Bayar",
  SEBAGIAN: "Sebagian",
  LUNAS: "Lunas",
};

const STATUS_COLOR: Record<StatusBayar, string> = {
  BELUM_BAYAR: "bg-mist text-muted",
  SEBAGIAN: "bg-amber-100 text-amber-700",
  LUNAS: "bg-green-100 text-green-700",
};

type FilterValue = StatusBayar | "all";

export function InvoiceList() {
  const { showToast } = useToast();
  const [items, setItems] = useState<Invoice[]>([]);
  const [total, setTotal] = useState(0);
  const [loading, setLoading] = useState(true);
  const [search, setSearch] = useState("");
  const [debouncedSearch, setDebouncedSearch] = useState("");
  const [filter, setFilter] = useState<FilterValue>("all");

  useEffect(() => {
    const timer = setTimeout(() => setDebouncedSearch(search), 300);
    return () => clearTimeout(timer);
  }, [search]);

  useEffect(() => {
    setLoading(true);
    api.invoice
      .list({ q: debouncedSearch || undefined, statusBayar: filter })
      .then((res) => {
        setItems(res.items);
        setTotal(res.total);
      })
      .catch((err) => showToast(err instanceof ApiError ? err.message : "Gagal memuat data.", "error"))
      .finally(() => setLoading(false));
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, [debouncedSearch, filter]);

  return (
    <div className="p-8">
      <div className="mb-6">
        <h1 className="text-2xl font-bold text-heading">Invoice</h1>
        <p className="text-sm text-muted">{total} invoice.</p>
      </div>

      <div className="mb-4 flex flex-wrap items-center gap-3">
        <input
          type="search"
          placeholder="Cari no. invoice, no. booking, atau nama..."
          value={search}
          onChange={(e) => setSearch(e.target.value)}
          className="w-72 rounded-lg border border-border px-3 py-2 text-sm focus:border-black focus:outline-none focus:ring-2 focus:ring-black/10"
        />
        <div className="flex gap-1 rounded-lg border border-border bg-white p-1">
          {(["all", "BELUM_BAYAR", "SEBAGIAN", "LUNAS"] as FilterValue[]).map((value) => (
            <button
              key={value}
              type="button"
              onClick={() => setFilter(value)}
              className={`rounded-md px-3 py-1.5 text-xs font-semibold transition-colors ${
                filter === value ? "bg-black text-white" : "text-muted hover:text-heading"
              }`}
            >
              {value === "all" ? "Semua" : STATUS_LABEL[value]}
            </button>
          ))}
        </div>
      </div>

      <div className="overflow-x-auto rounded-xl border border-border bg-white">
        <table className="w-full text-left text-sm">
          <thead>
            <tr className="border-b border-border text-xs uppercase tracking-wide text-muted">
              <th className="px-4 py-3">No. Invoice</th>
              <th className="px-4 py-3">Pelanggan</th>
              <th className="px-4 py-3">Total</th>
              <th className="px-4 py-3">Sisa Tagihan</th>
              <th className="px-4 py-3">Jatuh Tempo</th>
              <th className="px-4 py-3">Status</th>
              <th className="px-4 py-3 text-right">Aksi</th>
            </tr>
          </thead>
          <tbody>
            {loading && (
              <tr>
                <td colSpan={7} className="px-4 py-10 text-center text-muted">
                  Memuat data...
                </td>
              </tr>
            )}
            {!loading && items.length === 0 && (
              <tr>
                <td colSpan={7} className="px-4 py-10 text-center text-muted">
                  Belum ada invoice.
                </td>
              </tr>
            )}
            {!loading &&
              items.map((item) => (
                <tr key={item.id} className="border-b border-border last:border-b-0">
                  <td className="px-4 py-3 font-mono text-xs text-heading">{item.nomorInvoice}</td>
                  <td className="px-4 py-3 text-body">{item.reservasi?.pelanggan.nama}</td>
                  <td className="px-4 py-3 text-body">{formatIDR(item.total)}</td>
                  <td className="px-4 py-3 text-body">{formatIDR(item.sisaTagihan)}</td>
                  <td className="px-4 py-3 text-body">{formatDate(item.jatuhTempo)}</td>
                  <td className="px-4 py-3">
                    <span className={`rounded-full px-3 py-1 text-xs font-semibold ${STATUS_COLOR[item.statusBayar]}`}>
                      {STATUS_LABEL[item.statusBayar]}
                    </span>
                  </td>
                  <td className="px-4 py-3 text-right">
                    <Link to={`/invoice/${item.id}`} className="text-sm font-semibold text-heading hover:underline">
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
