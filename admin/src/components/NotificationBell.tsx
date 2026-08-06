import { useEffect, useRef, useState } from "react";
import { Link } from "react-router-dom";
import { api } from "../lib/api";
import type { Notifikasi } from "../lib/api";

function timeAgo(iso: string): string {
  const diffMs = Date.now() - new Date(iso).getTime();
  const minutes = Math.floor(diffMs / 60000);
  if (minutes < 1) return "baru saja";
  if (minutes < 60) return `${minutes} menit lalu`;
  const hours = Math.floor(minutes / 60);
  if (hours < 24) return `${hours} jam lalu`;
  return `${Math.floor(hours / 24)} hari lalu`;
}

export function NotificationBell() {
  const [items, setItems] = useState<Notifikasi[]>([]);
  const [unreadCount, setUnreadCount] = useState(0);
  const [open, setOpen] = useState(false);
  const containerRef = useRef<HTMLDivElement>(null);

  async function load() {
    try {
      const res = await api.notifikasi.list();
      setItems(res.items);
      setUnreadCount(res.unreadCount);
    } catch {
      // Diam-diam gagal — notifikasi bukan fitur kritikal, tidak perlu ganggu UI dengan error.
    }
  }

  useEffect(() => {
    load();
    const interval = setInterval(load, 30000);
    return () => clearInterval(interval);
  }, []);

  useEffect(() => {
    function handleClickOutside(event: MouseEvent) {
      if (containerRef.current && !containerRef.current.contains(event.target as Node)) {
        setOpen(false);
      }
    }
    document.addEventListener("mousedown", handleClickOutside);
    return () => document.removeEventListener("mousedown", handleClickOutside);
  }, []);

  async function handleMarkAllRead() {
    await api.notifikasi.markAllRead();
    setItems((prev) => prev.map((n) => ({ ...n, dibaca: true })));
    setUnreadCount(0);
  }

  async function handleItemClick(item: Notifikasi) {
    if (!item.dibaca) {
      await api.notifikasi.markRead(item.id);
      setItems((prev) => prev.map((n) => (n.id === item.id ? { ...n, dibaca: true } : n)));
      setUnreadCount((prev) => Math.max(0, prev - 1));
    }
    setOpen(false);
  }

  return (
    <div ref={containerRef} className="relative">
      <button
        type="button"
        onClick={() => setOpen((v) => !v)}
        className="relative flex size-9 items-center justify-center rounded-full text-heading transition-colors hover:bg-mist"
        aria-label="Notifikasi"
      >
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth={1.8} className="size-5">
          <path
            strokeLinecap="round"
            strokeLinejoin="round"
            d="M15 17h5l-1.4-1.4A2 2 0 0 1 18 14.2V11a6 6 0 1 0-12 0v3.2c0 .5-.2 1-.6 1.4L4 17h5m6 0v1a3 3 0 1 1-6 0v-1m6 0H9"
          />
        </svg>
        {unreadCount > 0 && (
          <span className="absolute right-0.5 top-0.5 flex size-4 items-center justify-center rounded-full bg-red-600 text-[10px] font-bold text-white">
            {unreadCount > 9 ? "9+" : unreadCount}
          </span>
        )}
      </button>

      {open && (
        <div className="absolute right-0 z-20 mt-2 w-80 rounded-xl border border-border bg-white shadow-xl">
          <div className="flex items-center justify-between border-b border-border px-4 py-3">
            <p className="text-sm font-bold text-heading">Notifikasi</p>
            {unreadCount > 0 && (
              <button
                type="button"
                onClick={handleMarkAllRead}
                className="text-xs font-semibold text-heading hover:underline"
              >
                Tandai semua dibaca
              </button>
            )}
          </div>
          <div className="max-h-80 overflow-y-auto">
            {items.length === 0 && (
              <p className="px-4 py-6 text-center text-sm text-muted">Belum ada notifikasi.</p>
            )}
            {items.map((item) => (
              <Link
                key={item.id}
                to={item.reservasiId ? `/reservasi/${item.reservasiId}` : "/reservasi"}
                onClick={() => handleItemClick(item)}
                className={`flex flex-col gap-0.5 border-b border-border px-4 py-3 text-sm last:border-b-0 hover:bg-mist ${
                  item.dibaca ? "text-muted" : "font-medium text-heading"
                }`}
              >
                <span>{item.pesan}</span>
                <span className="text-xs text-muted">{timeAgo(item.createdAt)}</span>
              </Link>
            ))}
          </div>
        </div>
      )}
    </div>
  );
}
