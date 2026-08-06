import { useEffect, useMemo, useState } from "react";
import { Link } from "react-router-dom";
import { api, ApiError } from "../../lib/api";
import type { PaketWisata } from "../../lib/api";
import { useToast } from "../../context/ToastContext";
import { ConfirmDialog } from "../../components/ConfirmDialog";

type ActiveFilter = "all" | "true" | "false";
type SortField = "name" | "location" | "price" | "rating" | "order" | "createdAt";

function formatIDR(value: number): string {
  return `Rp ${value.toLocaleString("id-ID")}`;
}

export function PaketWisataList() {
  const { showToast } = useToast();
  const [items, setItems] = useState<PaketWisata[]>([]);
  const [total, setTotal] = useState(0);
  const [loading, setLoading] = useState(true);
  const [search, setSearch] = useState("");
  const [debouncedSearch, setDebouncedSearch] = useState("");
  const [activeFilter, setActiveFilter] = useState<ActiveFilter>("all");
  const [sort, setSort] = useState<SortField>("order");
  const [order, setOrder] = useState<"asc" | "desc">("asc");
  const [deleteTarget, setDeleteTarget] = useState<PaketWisata | null>(null);

  useEffect(() => {
    const timer = setTimeout(() => setDebouncedSearch(search), 300);
    return () => clearTimeout(timer);
  }, [search]);

  async function loadItems() {
    setLoading(true);
    try {
      const res = await api.paketWisata.list({
        q: debouncedSearch || undefined,
        active: activeFilter === "all" ? undefined : activeFilter,
        sort,
        order,
      });
      setItems(res.items);
      setTotal(res.total);
    } catch (err) {
      showToast(err instanceof ApiError ? err.message : "Gagal memuat data.", "error");
    } finally {
      setLoading(false);
    }
  }

  useEffect(() => {
    loadItems();
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, [debouncedSearch, activeFilter, sort, order]);

  function toggleSort(field: SortField) {
    if (sort === field) {
      setOrder(order === "asc" ? "desc" : "asc");
    } else {
      setSort(field);
      setOrder("asc");
    }
  }

  async function handleToggleActive(item: PaketWisata) {
    try {
      await api.paketWisata.setActive(item.id, !item.active);
      setItems((prev) =>
        prev.map((i) => (i.id === item.id ? { ...i, active: !item.active } : i)),
      );
      showToast(`"${item.name}" ${!item.active ? "diaktifkan" : "dinonaktifkan"}.`);
    } catch (err) {
      showToast(err instanceof ApiError ? err.message : "Gagal mengubah status.", "error");
    }
  }

  async function handleDelete() {
    if (!deleteTarget) return;
    try {
      await api.paketWisata.remove(deleteTarget.id);
      showToast(`"${deleteTarget.name}" dihapus.`);
      setDeleteTarget(null);
      loadItems();
    } catch (err) {
      showToast(err instanceof ApiError ? err.message : "Gagal menghapus.", "error");
    }
  }

  const columns: { field: SortField; label: string }[] = useMemo(
    () => [
      { field: "name", label: "Nama" },
      { field: "location", label: "Lokasi" },
      { field: "price", label: "Harga" },
      { field: "rating", label: "Rating" },
    ],
    [],
  );

  return (
    <div className="p-8">
      <div className="mb-6 flex flex-wrap items-center justify-between gap-4">
        <div>
          <h1 className="text-2xl font-bold text-heading">Paket Wisata</h1>
          <p className="text-sm text-muted">{total} paket terdaftar.</p>
        </div>
        <Link
          to="/paket-wisata/baru"
          className="rounded-full bg-black px-4 py-2 text-sm font-semibold text-white transition-colors hover:bg-black/85"
        >
          + Tambah Paket
        </Link>
      </div>

      <div className="mb-4 flex flex-wrap items-center gap-3">
        <input
          type="search"
          placeholder="Cari nama atau lokasi..."
          value={search}
          onChange={(e) => setSearch(e.target.value)}
          className="w-64 rounded-lg border border-border px-3 py-2 text-sm focus:border-black focus:outline-none focus:ring-2 focus:ring-black/10"
        />
        <div className="flex gap-1 rounded-lg border border-border bg-white p-1">
          {(["all", "true", "false"] as ActiveFilter[]).map((value) => (
            <button
              key={value}
              type="button"
              onClick={() => setActiveFilter(value)}
              className={`rounded-md px-3 py-1.5 text-xs font-semibold transition-colors ${
                activeFilter === value ? "bg-black text-white" : "text-muted hover:text-heading"
              }`}
            >
              {value === "all" ? "Semua" : value === "true" ? "Aktif" : "Nonaktif"}
            </button>
          ))}
        </div>
      </div>

      <div className="overflow-x-auto rounded-xl border border-border bg-white">
        <table className="w-full text-left text-sm">
          <thead>
            <tr className="border-b border-border text-xs uppercase tracking-wide text-muted">
              <th className="px-4 py-3">Foto</th>
              {columns.map((col) => (
                <th key={col.field} className="px-4 py-3">
                  <button
                    type="button"
                    onClick={() => toggleSort(col.field)}
                    className="flex items-center gap-1 font-semibold hover:text-heading"
                  >
                    {col.label}
                    {sort === col.field && <span>{order === "asc" ? "▲" : "▼"}</span>}
                  </button>
                </th>
              ))}
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
                  Belum ada paket wisata. Klik "Tambah Paket" untuk membuat yang pertama.
                </td>
              </tr>
            )}

            {!loading &&
              items.map((item) => (
                <tr key={item.id} className="border-b border-border last:border-b-0">
                  <td className="px-4 py-3">
                    <div className="size-12 overflow-hidden rounded-lg bg-mist">
                      {item.images[0] && (
                        <img
                          src={item.images[0].url}
                          alt=""
                          className="h-full w-full object-cover"
                        />
                      )}
                    </div>
                  </td>
                  <td className="px-4 py-3">
                    <p className="font-semibold text-heading">{item.name}</p>
                    {item.tag && <p className="text-xs text-muted">{item.tag}</p>}
                  </td>
                  <td className="px-4 py-3 text-body">
                    {item.location} · {item.duration}
                  </td>
                  <td className="px-4 py-3 text-body">{formatIDR(item.price)}</td>
                  <td className="px-4 py-3 text-body">{item.rating.toFixed(1)}</td>
                  <td className="px-4 py-3">
                    <button
                      type="button"
                      onClick={() => handleToggleActive(item)}
                      className={`rounded-full px-3 py-1 text-xs font-semibold transition-colors ${
                        item.active
                          ? "bg-green-100 text-green-700 hover:bg-green-200"
                          : "bg-mist text-muted hover:bg-border"
                      }`}
                    >
                      {item.active ? "Aktif" : "Nonaktif"}
                    </button>
                  </td>
                  <td className="px-4 py-3">
                    <div className="flex justify-end gap-3">
                      <Link
                        to={`/paket-wisata/${item.id}`}
                        className="text-sm font-semibold text-heading hover:underline"
                      >
                        Edit
                      </Link>
                      <button
                        type="button"
                        onClick={() => setDeleteTarget(item)}
                        className="text-sm font-semibold text-red-600 hover:underline"
                      >
                        Hapus
                      </button>
                    </div>
                  </td>
                </tr>
              ))}
          </tbody>
        </table>
      </div>

      <ConfirmDialog
        open={deleteTarget !== null}
        title={`Hapus "${deleteTarget?.name}"?`}
        description="Semua foto yang terkait akan ikut terhapus. Tindakan ini tidak bisa dibatalkan."
        onConfirm={handleDelete}
        onCancel={() => setDeleteTarget(null)}
      />
    </div>
  );
}
