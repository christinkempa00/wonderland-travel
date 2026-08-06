import { NavLink, Outlet } from "react-router-dom";
import { useAuth } from "../context/AuthContext";
import { NotificationBell } from "../components/NotificationBell";

const NAV_ITEMS = [
  { label: "Dashboard", path: "/", enabled: true },
  { label: "Papan Reservasi", path: "/reservasi", enabled: true },
  { label: "Invoice", path: "/invoice", enabled: true },
  { label: "Pelanggan", path: "/pelanggan", enabled: true },
  { label: "Laporan Pendapatan", path: "/laporan", enabled: true },
  { label: "Paket Wisata", path: "/paket-wisata", enabled: true },
  { label: "Destinasi", path: "/destinasi", enabled: false },
  { label: "Explore (Hotel/Pesawat/Rental)", path: "/explore", enabled: false },
  { label: "Galeri", path: "/galeri", enabled: false },
  { label: "Testimoni", path: "/testimoni", enabled: false },
  { label: "Statistik Home", path: "/statistik", enabled: false },
  { label: "Itinerary", path: "/itinerary", enabled: false },
  { label: "FAQ", path: "/faq", enabled: false },
  { label: "Info Kontak", path: "/info-kontak", enabled: false },
  { label: "Pengaturan Umum", path: "/pengaturan", enabled: false },
];

export function AdminLayout() {
  const { user, logout } = useAuth();

  return (
    <div className="flex min-h-screen bg-mist">
      <aside className="flex w-64 shrink-0 flex-col border-r border-border bg-white">
        <div className="border-b border-border px-6 py-5">
          <p className="text-base font-bold text-heading">Wonderland Travel</p>
          <p className="text-xs text-muted">Admin Panel</p>
        </div>

        <nav className="flex flex-1 flex-col gap-1 overflow-y-auto px-3 py-4">
          {NAV_ITEMS.map((item) =>
            item.enabled ? (
              <NavLink
                key={item.path}
                to={item.path}
                end={item.path === "/"}
                className={({ isActive }) =>
                  `rounded-lg px-3 py-2 text-sm font-medium transition-colors ${
                    isActive ? "bg-black text-white" : "text-heading hover:bg-mist"
                  }`
                }
              >
                {item.label}
              </NavLink>
            ) : (
              <span
                key={item.path}
                className="flex items-center justify-between rounded-lg px-3 py-2 text-sm text-muted"
                title="Menyusul di fase berikutnya"
              >
                {item.label}
                <span className="text-[10px] uppercase tracking-wide text-muted/70">Segera</span>
              </span>
            ),
          )}
        </nav>

        <div className="border-t border-border px-4 py-4">
          <p className="truncate text-sm font-semibold text-heading">{user?.name}</p>
          <p className="truncate text-xs text-muted">
            {user?.email} · {user?.role === "ADMIN" ? "Admin" : "Editor"}
          </p>
          <button
            type="button"
            onClick={() => logout()}
            className="mt-3 w-full rounded-full border border-border px-3 py-1.5 text-sm font-semibold text-heading transition-colors hover:bg-mist"
          >
            Keluar
          </button>
        </div>
      </aside>

      <div className="flex min-w-0 flex-1 flex-col">
        <header className="flex items-center justify-end border-b border-border bg-white px-6 py-3">
          <NotificationBell />
        </header>
        <main className="min-w-0 flex-1 overflow-x-hidden">
          <Outlet />
        </main>
      </div>
    </div>
  );
}
