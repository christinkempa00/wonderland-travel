import { useEffect, useState } from "react";
import type { FormEvent } from "react";
import { Link, useNavigate, useParams } from "react-router-dom";
import { api, ApiError } from "../../lib/api";
import type { PaketWisataImage } from "../../lib/api";
import { useToast } from "../../context/ToastContext";
import { ImageUploader } from "../../components/ImageUploader";

const emptyForm = {
  name: "",
  location: "",
  duration: "",
  price: 0,
  rating: 5,
  tag: "",
  description: "",
  active: true,
};

export function PaketWisataForm() {
  const { id } = useParams();
  const isCreate = !id || id === "baru";
  const navigate = useNavigate();
  const { showToast } = useToast();

  const [form, setForm] = useState(emptyForm);
  const [highlights, setHighlights] = useState<string[]>([""]);
  const [images, setImages] = useState<PaketWisataImage[]>([]);
  const [loading, setLoading] = useState(!isCreate);
  const [saving, setSaving] = useState(false);
  const [uploading, setUploading] = useState(false);

  useEffect(() => {
    if (isCreate) return;
    api.paketWisata
      .get(id!)
      .then(({ item }) => {
        setForm({
          name: item.name,
          location: item.location,
          duration: item.duration,
          price: item.price,
          rating: item.rating,
          tag: item.tag ?? "",
          description: item.description,
          active: item.active,
        });
        setHighlights(item.highlights.length > 0 ? item.highlights : [""]);
        setImages(item.images);
      })
      .catch((err) => {
        showToast(err instanceof ApiError ? err.message : "Gagal memuat data.", "error");
      })
      .finally(() => setLoading(false));
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, [id]);

  function updateHighlight(index: number, value: string) {
    setHighlights((prev) => prev.map((h, i) => (i === index ? value : h)));
  }

  function addHighlight() {
    setHighlights((prev) => [...prev, ""]);
  }

  function removeHighlight(index: number) {
    setHighlights((prev) => prev.filter((_, i) => i !== index));
  }

  async function handleSubmit(event: FormEvent<HTMLFormElement>) {
    event.preventDefault();
    setSaving(true);

    const payload = {
      ...form,
      tag: form.tag.trim() || null,
      highlights: highlights.map((h) => h.trim()).filter(Boolean),
    };

    try {
      if (isCreate) {
        const { item } = await api.paketWisata.create(payload);
        showToast("Paket wisata dibuat. Sekarang tambahkan foto.");
        navigate(`/paket-wisata/${item.id}`, { replace: true });
      } else {
        await api.paketWisata.update(id!, payload);
        showToast("Perubahan disimpan.");
      }
    } catch (err) {
      showToast(err instanceof ApiError ? err.message : "Gagal menyimpan.", "error");
    } finally {
      setSaving(false);
    }
  }

  async function handleUpload(files: FileList) {
    if (isCreate) return;
    setUploading(true);
    try {
      const { images: uploaded } = await api.paketWisata.uploadImages(id!, files);
      setImages((prev) => [...prev, ...uploaded]);
      showToast(`${uploaded.length} foto diunggah.`);
    } catch (err) {
      showToast(err instanceof ApiError ? err.message : "Gagal mengunggah foto.", "error");
    } finally {
      setUploading(false);
    }
  }

  async function handleDeleteImage(imageId: string) {
    if (isCreate) return;
    try {
      await api.paketWisata.deleteImage(id!, imageId);
      setImages((prev) => prev.filter((img) => img.id !== imageId));
      showToast("Foto dihapus.");
    } catch (err) {
      showToast(err instanceof ApiError ? err.message : "Gagal menghapus foto.", "error");
    }
  }

  async function handleReorderImages(orderedIds: string[]) {
    if (isCreate) return;
    const reordered = orderedIds
      .map((imgId) => images.find((img) => img.id === imgId))
      .filter((img): img is PaketWisataImage => Boolean(img));
    setImages(reordered);
    try {
      await api.paketWisata.reorderImages(id!, orderedIds);
    } catch (err) {
      showToast(err instanceof ApiError ? err.message : "Gagal menyimpan urutan.", "error");
    }
  }

  if (loading) {
    return <div className="p-8 text-sm text-muted">Memuat data...</div>;
  }

  return (
    <div className="mx-auto max-w-3xl p-8">
      <Link to="/paket-wisata" className="text-sm text-muted hover:text-heading">
        ← Kembali ke daftar
      </Link>
      <h1 className="mt-2 text-2xl font-bold text-heading">
        {isCreate ? "Tambah Paket Wisata" : "Edit Paket Wisata"}
      </h1>

      <form onSubmit={handleSubmit} className="mt-6 flex flex-col gap-6">
        <div className="grid grid-cols-1 gap-4 sm:grid-cols-2">
          <Field label="Nama Paket">
            <input
              required
              value={form.name}
              onChange={(e) => setForm({ ...form, name: e.target.value })}
              className="input"
            />
          </Field>
          <Field label="Lokasi">
            <input
              required
              value={form.location}
              onChange={(e) => setForm({ ...form, location: e.target.value })}
              className="input"
            />
          </Field>
          <Field label="Durasi (mis. 4D3N)">
            <input
              required
              value={form.duration}
              onChange={(e) => setForm({ ...form, duration: e.target.value })}
              className="input"
            />
          </Field>
          <Field label="Tag (opsional)">
            <input
              value={form.tag}
              onChange={(e) => setForm({ ...form, tag: e.target.value })}
              placeholder="Populer, Terlaris, dst."
              className="input"
            />
          </Field>
          <Field label="Harga (Rp)">
            <input
              required
              type="number"
              min={0}
              value={form.price}
              onChange={(e) => setForm({ ...form, price: Number(e.target.value) })}
              className="input"
            />
          </Field>
          <Field label="Rating (0-5)">
            <input
              required
              type="number"
              min={0}
              max={5}
              step={0.1}
              value={form.rating}
              onChange={(e) => setForm({ ...form, rating: Number(e.target.value) })}
              className="input"
            />
          </Field>
        </div>

        <Field label="Deskripsi">
          <textarea
            required
            rows={4}
            value={form.description}
            onChange={(e) => setForm({ ...form, description: e.target.value })}
            className="input resize-none"
          />
        </Field>

        <div>
          <label className="text-sm font-medium text-heading">Highlight / Fasilitas</label>
          <div className="mt-2 flex flex-col gap-2">
            {highlights.map((highlight, index) => (
              <div key={index} className="flex gap-2">
                <input
                  value={highlight}
                  onChange={(e) => updateHighlight(index, e.target.value)}
                  placeholder="mis. Hotel bintang 4, 3 malam"
                  className="input flex-1"
                />
                <button
                  type="button"
                  onClick={() => removeHighlight(index)}
                  className="rounded-lg border border-border px-3 text-sm text-muted hover:bg-mist"
                >
                  Hapus
                </button>
              </div>
            ))}
            <button
              type="button"
              onClick={addHighlight}
              className="self-start text-sm font-semibold text-heading hover:underline"
            >
              + Tambah highlight
            </button>
          </div>
        </div>

        <label className="flex items-center gap-2 text-sm font-medium text-heading">
          <input
            type="checkbox"
            checked={form.active}
            onChange={(e) => setForm({ ...form, active: e.target.checked })}
            className="size-4 rounded border-border"
          />
          Tampilkan di website (aktif)
        </label>

        <div>
          <label className="text-sm font-medium text-heading">Foto</label>
          <div className="mt-2">
            {isCreate ? (
              <p className="rounded-lg border border-dashed border-border p-4 text-sm text-muted">
                Simpan paket dulu, lalu Anda bisa menambahkan foto di sini.
              </p>
            ) : (
              <ImageUploader
                images={images}
                onUpload={handleUpload}
                onDelete={handleDeleteImage}
                onReorder={handleReorderImages}
                uploading={uploading}
              />
            )}
          </div>
        </div>

        <div className="flex justify-end gap-3 border-t border-border pt-6">
          <Link
            to="/paket-wisata"
            className="rounded-full border border-border px-5 py-2.5 text-sm font-semibold text-heading hover:bg-mist"
          >
            Batal
          </Link>
          <button
            type="submit"
            disabled={saving}
            className="rounded-full bg-black px-5 py-2.5 text-sm font-semibold text-white transition-colors hover:bg-black/85 disabled:cursor-not-allowed disabled:opacity-50"
          >
            {saving ? "Menyimpan..." : isCreate ? "Buat Paket" : "Simpan Perubahan"}
          </button>
        </div>
      </form>
    </div>
  );
}

function Field({ label, children }: { label: string; children: React.ReactNode }) {
  return (
    <div className="flex flex-col gap-1.5">
      <label className="text-sm font-medium text-heading">{label}</label>
      {children}
    </div>
  );
}
