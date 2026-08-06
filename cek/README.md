# Wonderland Travel — Portal Cek Tagihan

React + Vite + TypeScript + Tailwind. Halaman publik **tanpa login** — pelanggan masukkan No. ID Klien untuk melihat status reservasi & tagihan mereka sendiri.

Situs statis murni, memanggil API publik read-only di [`../server`](../server). Untuk detail keamanan (captcha, rate limit, kodeKlien acak) dan deploy, lihat [root README](../README.md).

Quick start (`server` harus sudah jalan di port 4000):

```bash
npm install
npm run dev
```

Buka http://localhost:5174.
