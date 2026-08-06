import { useEffect, useState } from "react";
import type { FormEvent } from "react";
import { Link } from "react-router-dom";
import { api, ApiError } from "../../lib/api";
import type { Pelanggan } from "../../lib/api";
import { useToast } from "../../context/ToastContext";

const emptyForm = { nama: "", noHp: "", email: "", alamat: "" };

export function PelangganList() {
  const { showToast } = useToast();
  const [items, setItems] = useState<Pelanggan[]>([]);
  const [total, setTotal] = useState(0);
  const [loading, setLoading] = useState(true);
  const [search, setSearch] = useState("");
  const [debouncedSearch, setDebouncedSearch] = useState("");
  const [modalOpen, setModalOpen] = useState(false);
  const [form, setForm] = useState(emptyForm);
  const [saving, setSaving] = useState(false);

  useEffect(() => {
    const timer = setTimeout(() => setDebouncedSearch(search), 300);
    return () => clearTimeout(timer);
  }, [search]);

  async function loadItems() {
    setLoading(true);
    try {
      const res = await api.pelanggan.list({ q: debouncedSearch || undefined });
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
  }, [debouncedSearch]);

  async function handleSubmit(event: FormEvent<HTMLFormElement>) {
    event.preventDefault();
    setSaving(true);
    try {
      await api.pelanggan.create({ ...form, email: form.email || null, alamat: form.alamat || null });
      showToast("Pelanggan ditambahkan.");
      setModalOpen(false);
      setForm(emptyForm);
      loadItems();
    } catch (err) {
      showToast(err instanceof ApiError ? err.message : "Gagal menyimpan.", "error");
    } finally {
      setSaving(false);
    }
  }

  return (
    <div className="p-8">
      <div className="mb-6 flex flex-wrap items-center justify-between gap-4">
        <div>
          <h1 className="text-2xl font-bold text-heading">Pelanggan</h1>
          <p className="text-sm text-muted">{total} pelanggan terdaftar.</p>
        </div>
        <button
          type="button"
          onClick={() => setModalOpen(true)}
          className="rounded-full bg-black px-4 py-2 text-sm font-semibold text-white transition-colors hover:bg-black/85"
        >
          + Tambah Pelanggan
        </button>
      </div>

      <input
        type="search"
        placeholder="Cari nama, No. HP, email, atau kode klien..."
        value={search}
        onChange={(e) => setSearch(e.target.value)}
        className="mb-4 w-80 rounded-lg border border-border px-3 py-2 text-sm focus:border-black focus:outline-none focus:ring-2 focus:ring-black/10"
      />

      <div className="overflow-x-auto rounded-xl border border-border bg-white">
        <table className="w-full text-left text-sm">
          <thead>
            <tr className="border-b border-border text-xs uppercase tracking-wide text-muted">
              <th className="px-4 py-3">Kode Klien</th>
              <th className="px-4 py-3">Nama</th>
              <th className="px-4 py-3">No. HP</th>
              <th className="px-4 py-3">Email</th>
              <th className="px-4 py-3">Reservasi</th>
              <th className="px-4 py-3 text-right">Aksi</th>
            </tr>
          </thead>
          <tbody>
            {loading && (
              <tr>
                <td colSpan={6} className="px-4 py-10 text-center text-muted">
                  Memuat data...
                </td>
              </tr>
            )}
            {!loading && items.length === 0 && (
              <tr>
                <td colSpan={6} className="px-4 py-10 text-center text-muted">
                  Belum ada pelanggan.
                </td>
              </tr>
            )}
            {!loading &&
              items.map((item) => (
                <tr key={item.id} className="border-b border-border last:border-b-0">
                  <td className="px-4 py-3 font-mono text-xs text-muted">{item.kodeKlien}</td>
                  <td className="px-4 py-3 font-semibold text-heading">{item.nama}</td>
                  <td className="px-4 py-3 text-body">{item.noHp}</td>
                  <td className="px-4 py-3 text-body">{item.email ?? "-"}</td>
                  <td className="px-4 py-3 text-body">{item._count?.reservasi ?? 0}</td>
                  <td className="px-4 py-3 text-right">
                    <Link
                      to={`/pelanggan/${item.id}`}
                      className="text-sm font-semibold text-heading hover:underline"
                    >
                      Lihat Detail
                    </Link>
                  </td>
                </tr>
              ))}
          </tbody>
        </table>
      </div>

      {modalOpen && (
        <div className="fixed inset-0 z-50 flex items-center justify-center p-4">
          <div className="absolute inset-0 bg-black/50" onClick={() => setModalOpen(false)} />
          <form
            onSubmit={handleSubmit}
            className="relative flex w-full max-w-md flex-col gap-4 rounded-2xl bg-white p-6 shadow-xl"
          >
            <h2 className="text-lg font-bold text-heading">Tambah Pelanggan</h2>
            <div className="flex flex-col gap-1.5">
              <label className="text-sm font-medium text-heading">Nama</label>
              <input
                required
                value={form.nama}
                onChange={(e) => setForm({ ...form, nama: e.target.value })}
                className="input"
              />
            </div>
            <div className="flex flex-col gap-1.5">
              <label className="text-sm font-medium text-heading">No. HP</label>
              <input
                required
                value={form.noHp}
                onChange={(e) => setForm({ ...form, noHp: e.target.value })}
                className="input"
              />
            </div>
            <div className="flex flex-col gap-1.5">
              <label className="text-sm font-medium text-heading">Email (opsional)</label>
              <input
                type="email"
                value={form.email}
                onChange={(e) => setForm({ ...form, email: e.target.value })}
                className="input"
              />
            </div>
            <div className="flex flex-col gap-1.5">
              <label className="text-sm font-medium text-heading">Alamat (opsional)</label>
              <textarea
                rows={2}
                value={form.alamat}
                onChange={(e) => setForm({ ...form, alamat: e.target.value })}
                className="input resize-none"
              />
            </div>
            <div className="mt-2 flex justify-end gap-3">
              <button
                type="button"
                onClick={() => setModalOpen(false)}
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
      )}
    </div>
  );
}
