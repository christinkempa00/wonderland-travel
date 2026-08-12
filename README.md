# Wonderland Travel

Website publik Wonderland Travel — Next.js (App Router, static export) + Tailwind CSS.

> Sistem admin (CMS + Reservasi + Invoice) dan portal cek tagihan klien dibuat di proyek terpisah, tidak lagi bagian dari repo ini.

## Menjalankan lokal

```bash
npm install
npm run dev
```

Buka [http://localhost:3000](http://localhost:3000). Lihat [`AGENTS.md`](AGENTS.md) / [`CLAUDE.md`](CLAUDE.md) untuk konteks desain sistem.

## Build

```bash
npm run build
```

`output: "export"` di `next.config.ts` menghasilkan static export ke folder `out/` — situs murni file statis (HTML/CSS/JS), tidak butuh Node.js server saat runtime.

---

## Deploy ke Hostinger

### Otomatis lewat GitHub Actions

Push ke `main` yang menyentuh `src/**`, `public/**`, `package.json`, `package-lock.json`, atau `next.config.ts` otomatis memicu [`.github/workflows/deploy-website.yml`](.github/workflows/deploy-website.yml): build → upload `out/` ke Hostinger lewat FTPS. Bisa juga dipicu manual lewat tab *Actions* di GitHub → pilih workflow → *Run workflow*.

Setup sekali saja — 3 GitHub Secrets (*Settings → Secrets and variables → Actions*):

| Secret | Keterangan |
|---|---|
| `HOSTINGER_FTP_SERVER` | Host FTP |
| `HOSTINGER_FTP_USERNAME` | Username akun FTP yang document root-nya adalah root domain situs ini |
| `HOSTINGER_FTP_PASSWORD` | Password akun FTP tersebut |

> Pastikan akun FTP yang dipakai benar-benar menunjuk ke document root domain publik (bukan folder lain) — kalau `server-dir` di workflow sudah `./`, berarti FTP root akun itu sendiri sudah harus persis document root-nya.

### Manual (sekali-sekali tanpa GitHub Actions)

```bash
npm run build
```

Upload isi folder `out/` ke `public_html` (atau document root domain) lewat File Manager/FTP di hPanel. Murni file statis — tidak perlu "Setup Node.js App".

---

## Checklist sebelum go-live

- [ ] Nomor WhatsApp placeholder (`src/lib/whatsapp.ts`) diganti nomor bisnis asli
- [ ] Semua foto `picsum.photos` diganti foto asli (lihat komentar `// TODO: ganti data riil klien` di tiap sumbernya)
- [ ] Harga paket (Home & Paket Wisata) diganti harga riil
- [ ] Data mock Explore (hotel/pesawat/rental) disambungkan ke sumber data/API pemesanan sungguhan
- [ ] Form Kontak: putuskan apakah tetap via WhatsApp atau disambungkan ke backend sungguhan (lihat TODO di `src/components/kontak/floating-contact-form.tsx`)
- [ ] 3 GitHub Secrets auto-deploy sudah diisi dan diverifikasi (lihat bagian Deploy di atas)
