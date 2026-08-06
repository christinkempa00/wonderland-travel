import { Link } from "react-router-dom";
import { useAuth } from "../context/AuthContext";

export function Dashboard() {
  const { user } = useAuth();

  return (
    <div className="p-8">
      <h1 className="text-2xl font-bold text-heading">Selamat datang, {user?.name}.</h1>
      <p className="mt-1 max-w-md text-sm text-muted">
        Modul lain (Reservasi, Invoice, dan tipe konten CMS lainnya) menyusul di fase berikutnya.
      </p>

      <Link
        to="/paket-wisata"
        className="mt-6 inline-flex flex-col gap-1 rounded-xl border border-border bg-white p-5 shadow-sm transition-shadow hover:shadow-md"
      >
        <span className="text-base font-bold text-heading">Kelola Paket Wisata →</span>
        <span className="text-sm text-muted">
          Tambah, ubah, hapus, dan atur foto paket wisata yang tampil di website.
        </span>
      </Link>
    </div>
  );
}
