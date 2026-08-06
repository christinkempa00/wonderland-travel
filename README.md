# Wonderland Travel

Monorepo dengan 3 bagian terpisah:

| Bagian | Folder | Stack | Deploy sebagai |
|---|---|---|---|
| 1. Website publik | [`src/`](src) (root) | Next.js + Tailwind | Static export → `public_html` |
| 2. Sistem Admin (CMS + Reservasi + Invoice) | [`server/`](server) + [`admin/`](admin) | Express + Prisma (MySQL) + React admin (Vite + Tailwind) | Node.js App di hPanel → `admin.wonderlandtravel.com` |
| 3. Portal Cek Tagihan Klien | [`cek/`](cek) | React (Vite + Tailwind), static, tanpa login | Static hosting → `cek.wonderlandtravel.com` |

Bagian 2 dan 3 berbagi satu database yang sama (satu backend `server/`) — bedanya Bagian 2 butuh login (staf), Bagian 3 publik & read-only (cukup No. ID Klien).

Status saat ini: **Fase 0–7 (website publik) selesai. Fase 8-A (autentikasi) selesai. Modul CMS "Paket Wisata" selesai** sebagai pola CRUD pertama. **Modul Reservasi & Invoice selesai. Fase 9 (Portal Cek Tagihan) selesai.** 9 tipe konten CMS lain, dan integrasi form publik → API (Fase 8-D) menyusul.

---

## 1. Website Publik (`src/`)

```bash
npm install
npm run dev
```

Buka [http://localhost:3000](http://localhost:3000). Lihat [`AGENTS.md`](AGENTS.md) / [`CLAUDE.md`](CLAUDE.md) untuk konteks desain sistem.

---

## 2. Sistem Admin (`server/` + `admin/`)

Backend (`server/`) menyediakan API auth (dan nanti CMS/Reservasi/Invoice) lewat Express + Prisma ke MySQL/MariaDB. Frontend (`admin/`) adalah panel admin React yang memanggil API tersebut. Keduanya proyek npm terpisah.

### 2.1 Prasyarat

- Node.js 20+
- Server MySQL/MariaDB yang jalan (lokal: XAMPP/MAMP/MySQL native — semuanya cocok, karena Prisma provider-nya `mysql`)

### 2.2 Setup Database (lokal)

Buat database + user khusus (jangan pakai `root` langsung, supaya perilakunya mirip database yang akan dibuat lewat hPanel Hostinger nanti):

```sql
CREATE DATABASE wonderland_admin CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'wonderland_admin'@'localhost' IDENTIFIED BY 'ganti-dengan-password-anda';
GRANT ALL PRIVILEGES ON wonderland_admin.* TO 'wonderland_admin'@'localhost';
FLUSH PRIVILEGES;
```

> Kalau mau pakai `npx prisma migrate dev` (bukan cuma `migrate deploy`) di lokal, user ini juga butuh privilege untuk membuat "shadow database" sementara — di lokal paling gampang `GRANT ALL PRIVILEGES ON *.* TO 'wonderland_admin'@'localhost';`. Ini **hanya untuk kenyamanan development lokal**; user database yang dibuat Hostinger nanti tidak akan (dan tidak perlu) punya privilege seluas ini — deploy production pakai `prisma migrate deploy` yang tidak butuh shadow database.

### 2.3 Setup Backend

```bash
cd server
cp .env.example .env
```

Edit `server/.env`:

```env
DATABASE_URL="mysql://wonderland_admin:ganti-dengan-password-anda@localhost:3306/wonderland_admin"
CLIENT_ORIGIN="http://localhost:5173"
JWT_SECRET="ganti-dengan-string-acak-panjang"   # contoh: openssl rand -hex 32
SEED_ADMIN_EMAIL="admin@wonderlandtravel.id"
SEED_ADMIN_PASSWORD="ganti-password-ini"
```

Lalu:

```bash
npm install
npm run prisma:migrate    # buat tabel di database (nama migrasi: init, dst.)
npm run seed              # buat 1 akun admin awal dari SEED_ADMIN_* di .env
npm run dev                # jalan di http://localhost:4000
```

### 2.4 Setup Admin Frontend

Di terminal terpisah (backend di atas harus tetap jalan):

```bash
cd admin
npm install
npm run dev                # jalan di http://localhost:5173
```

Dev server admin sudah dikonfigurasi proxy `/api/*` → `http://localhost:4000`, jadi cookie sesi jalan normal tanpa masalah CORS lintas-origin saat development.

### 2.5 Cara Login

1. Buka **http://localhost:5173** — karena belum login, otomatis diarahkan ke `/login`.
2. Masuk dengan kredensial dari `SEED_ADMIN_EMAIL` / `SEED_ADMIN_PASSWORD` di `server/.env` (default sebelum diganti: `admin@wonderlandtravel.id` / `ChangeMe123!`).
3. Setelah berhasil, masuk ke dashboard admin dengan sidebar navigasi — menu yang belum dibangun ditandai "Segera".
4. Tombol **Keluar** di sidebar kiri bawah untuk logout.

**Cara kerja sesi:** login mengirim JWT lewat cookie `httpOnly` (nama cookie: `wonderland_admin_token`, umur 7 hari, diatur lewat `JWT_EXPIRES_IN`). Cookie ini otomatis dikirim browser ke API di setiap request, jadi tidak perlu menyimpan token manual di frontend. Karena `httpOnly`, cookie ini juga tidak bisa diakses lewat JavaScript di browser — mengurangi risiko pencurian token lewat XSS.

**Role:** setiap user punya role `ADMIN` atau `EDITOR` (kolom `role` di tabel `users`). Saat ini semua halaman admin hanya mensyaratkan "sudah login" (lewat middleware `requireAuth`); pembatasan per-role (mis. hanya `ADMIN` yang boleh hapus data) akan dipakai mulai modul CMS/Reservasi/Invoice lewat helper `requireRole()` yang sudah disiapkan di `server/src/middleware/requireAuth.ts`.

**Menambah akun tim lain:** untuk saat ini lewat Prisma Studio atau query manual (belum ada halaman "Kelola Pengguna" di panel admin):

```bash
cd server
npx prisma studio   # buka GUI di browser, tambah baris baru di tabel users
```

> Kolom `passwordHash` harus diisi hasil bcrypt, bukan password polos. Cara termudah: sementara ubah `SEED_ADMIN_EMAIL`/`SEED_ADMIN_PASSWORD`/nama di `.env` lalu jalankan `npm run seed` lagi — script ini `upsert` berdasarkan email, jadi aman dipakai berulang untuk menambah/reset akun.

### 2.6 Upload Gambar (Cloudinary / lokal)

Modul CMS (Paket Wisata, dan nanti Destinasi/Galeri/dll.) butuh upload foto. `server/src/lib/storage.ts` otomatis memilih penyedia penyimpanan berdasarkan `.env` — **tidak perlu ubah kode** untuk berpindah:

- **3 variabel `CLOUDINARY_*` terisi** → upload ke Cloudinary, dapat URL `https://res.cloudinary.com/...`.
- **Kosong** → fallback ke disk lokal di `server/uploads/`, disajikan lewat `http://localhost:4000/uploads/...` (dev server admin sudah di-proxy supaya path ini otomatis jalan juga dari `http://localhost:5173`).

Cara dapat kredensial Cloudinary (gratis, 25GB storage + 25GB bandwidth/bulan, tanpa kartu kredit):

1. Daftar di https://cloudinary.com/users/register/free
2. Buka Dashboard (https://console.cloudinary.com/) → kartu **"Product Environment Credentials"** di bagian atas berisi **Cloud Name**, **API Key**, **API Secret** (klik ikon mata untuk menampilkan).
3. Isi ketiganya ke `server/.env`:
   ```env
   CLOUDINARY_CLOUD_NAME="..."
   CLOUDINARY_API_KEY="..."
   CLOUDINARY_API_SECRET="..."
   ```
4. Restart `npm run dev` di `server/` — log startup akan berhenti menampilkan peringatan "pakai penyimpanan lokal".

> **Penting untuk production di Hostinger:** penyimpanan lokal (`server/uploads/`) ada di filesystem Node.js App itu sendiri — kalau host/plan-nya menghapus file saat redeploy atau restart (umum di banyak setup shared/PaaS hosting), foto yang sudah diupload lewat mode lokal bisa hilang. **Pakai Cloudinary untuk production**, jangan andalkan penyimpanan lokal di luar development.

### 2.7 Struktur Backend

```
server/
├─ prisma/
│  ├─ schema.prisma        # model User, PaketWisata, PaketWisataImage
│  ├─ migrations/
│  └─ seed.ts              # bikin/ubah 1 akun admin dari env
├─ uploads/                # foto mode penyimpanan lokal (gitignored, auto-dibuat)
└─ src/
   ├─ index.ts             # entry Express: CORS, cookie-parser, static /uploads, routes
   ├─ lib/
   │  ├─ env.ts            # validasi env var (zod)
   │  ├─ prisma.ts         # Prisma client singleton
   │  ├─ jwt.ts            # sign/verify JWT
   │  ├─ slug.ts           # slugify + generator slug unik
   │  └─ storage.ts        # upload/hapus gambar, auto-switch Cloudinary/lokal
   ├─ middleware/
   │  └─ requireAuth.ts    # proteksi route + requireRole()
   └─ routes/
      ├─ auth.ts           # POST /login, POST /logout, GET /me
      └─ paketWisata.ts    # CRUD + upload/hapus/reorder gambar Paket Wisata
```

Endpoint yang sudah ada:

| Method | Path | Keterangan |
|---|---|---|
| POST | `/api/auth/login` | `{ email, password }` → set cookie sesi |
| POST | `/api/auth/logout` | hapus cookie sesi |
| GET | `/api/auth/me` | data user yang sedang login (401 kalau belum) |
| GET | `/api/admin/ping` | contoh route terproteksi (`requireAuth`) |
| GET | `/api/admin/paket-wisata` | list — query: `q`, `active`, `sort`, `order`, `page`, `pageSize` |
| GET | `/api/admin/paket-wisata/:id` | detail + gambar |
| POST | `/api/admin/paket-wisata` | buat baru (slug otomatis dari nama) |
| PUT | `/api/admin/paket-wisata/:id` | ubah |
| PATCH | `/api/admin/paket-wisata/:id/active` | `{ active }` → toggle aktif/nonaktif |
| DELETE | `/api/admin/paket-wisata/:id` | hapus (ikut hapus semua gambar di storage) |
| POST | `/api/admin/paket-wisata/:id/images` | upload gambar, form-data field `images` (multi-file, maks 5MB masing-masing) |
| DELETE | `/api/admin/paket-wisata/:id/images/:imageId` | hapus satu gambar |
| PUT | `/api/admin/paket-wisata/:id/images/reorder` | `{ order: string[] }` (urutan id gambar) |

Semua route di atas (kecuali `/auth/*`) mensyaratkan cookie sesi valid.

### 2.8 Menambah Tipe Konten CMS Baru

`paketWisata.ts` dipakai sebagai pola untuk 9 tipe konten CMS lain (Destinasi, Explore, Galeri, Testimoni, Statistik Home, Itinerary, FAQ, Info Kontak, Pengaturan Umum). Untuk entitas baru yang bentuknya mirip (list + form + upload foto):

1. **Backend:** tambah model di `schema.prisma` → `npx prisma migrate dev --name add_xxx` → salin `routes/paketWisata.ts` jadi `routes/xxx.ts`, sesuaikan field & validasi Zod → daftarkan di `src/index.ts` (`app.use("/api/admin/xxx", xxxRouter)`).
2. **Frontend:** salin folder `admin/src/pages/PaketWisata/` jadi `admin/src/pages/Xxx/` (List.tsx + Form.tsx), sesuaikan field form & kolom tabel → tambah method di `admin/src/lib/api.ts` → aktifkan link-nya di `NAV_ITEMS` (`admin/src/layouts/AdminLayout.tsx`, ubah `enabled: false` → `true`) → tambah route di `App.tsx`.

Komponen yang sudah reusable dan tidak perlu dibuat ulang: `ImageUploader` (upload multi-foto + drag-reorder), `ConfirmDialog` (konfirmasi hapus), `useToast` (notifikasi sukses/gagal) — semua di `admin/src/components/` dan `admin/src/context/`.

### 2.9 Modul Reservasi & Invoice

Alur bisnis: **Pelanggan** → **Reservasi** (nomor booking otomatis `BK-YYYYMMDD-0001`) → **Invoice** (nomor otomatis `INV-YYYYMMDD-0001`, dibuat dari satu reservasi) → **Pembayaran** (bisa dicicil, upload bukti bayar opsional).

**Status reservasi** (`BARU_MASUK → DIKONFIRMASI → MENUNGGU_PEMBAYARAN → DIBAYAR_SEBAGIAN → LUNAS → SELESAI`, atau `DIBATALKAN` kapan saja) diubah lewat tombol status di halaman detail atau drag-and-drop di Papan Reservasi (kanban). **Status pembayaran invoice** (`BELUM_BAYAR/SEBAGIAN/LUNAS`) dan status reservasi **otomatis ter-update** setiap kali pembayaran dicatat atau dihapus — lihat `syncStatusFromPembayaran()` di `server/src/routes/invoice.ts`. Reservasi yang sudah `SELESAI`/`DIBATALKAN` tidak akan ter-downgrade otomatis oleh perubahan pembayaran.

**Notifikasi "Reservasi Baru Masuk"** dibuat otomatis tiap ada reservasi baru (dari staf maupun nanti dari website — lihat §Fase 8-D). Panel admin melakukan **polling** ke `GET /api/admin/notifikasi` setiap 30 detik (lonceng di kanan atas) — dipilih daripada WebSocket/SSE supaya tetap jalan dengan aman di belakang reverse proxy Hostinger tanpa konfigurasi tambahan.

**PDF Invoice** digenerate on-the-fly dengan `pdfkit` (pure JavaScript, tanpa dependency native/Chromium — aman untuk shared hosting) lewat `GET /api/admin/invoice/:id/pdf`.

Endpoint baru (semua di belakang `requireAuth`, pola sama dengan §2.7):

| Method | Path | Keterangan |
|---|---|---|
| GET/POST/PUT | `/api/admin/pelanggan[...]` | CRUD pelanggan, `kodeKlien` (`WT000001`) otomatis |
| GET/POST/PUT/PATCH/DELETE | `/api/admin/reservasi[...]` | CRUD + `/:id/status` untuk ubah status |
| GET/POST/PUT/DELETE | `/api/admin/invoice[...]` | CRUD invoice (dibuat dari `reservasiId`) |
| GET | `/api/admin/invoice/:id/pdf` | Download/lihat PDF invoice |
| POST/DELETE | `/api/admin/invoice/:id/pembayaran[...]` | Catat/hapus pembayaran (form-data, field `bukti` opsional) |
| GET | `/api/admin/laporan/pendapatan` | `?from=&to=&groupBy=day\|month` — rekap pembayaran |
| GET/PATCH/POST | `/api/admin/notifikasi[...]` | List, `/:id/read`, `/read-all` |

> Field `total`, `subtotal`, `totalDibayar`, `sisaTagihan` pada Invoice **bukan kolom database** — selalu dihitung ulang dari `items` + `pembayaran` lewat `computeInvoiceTotals()` (`server/src/lib/invoiceTotals.ts`) setiap kali direspons, supaya tidak pernah nyasar/stale. Kalau menambah endpoint baru yang meng-`include` invoice, jangan lupa pakai helper ini — tanpanya, field-field itu akan `undefined` di response (pernah kejadian di endpoint list/detail Reservasi & Pelanggan saat development, sudah diperbaiki).

---

## 3. Portal Cek Tagihan Klien (`cek/`)

Halaman publik terpisah, **tanpa login** — pelanggan masukkan No. ID Klien untuk lihat status reservasi & tagihan sendiri. Murni statis (Vite + React + Tailwind, gaya editorial monokrom yang sama dengan website utama), memanggil `server` lewat satu endpoint publik read-only.

### 3.1 Menjalankan lokal

```bash
cd cek
npm install
npm run dev    # jalan di http://localhost:5174, sudah di-proxy ke server di :4000
```

Backend (`server`) harus sudah jalan (§2.3). Tidak perlu login untuk portal ini.

### 3.2 Keamanan (sesuai spesifikasi Fase 9)

- **Read-only** — tidak ada endpoint yang mengubah data.
- **kodeKlien acak, bukan sequential.** ⚠️ Ini perbaikan penting: kodeKlien tadinya `WT000001`, `WT000002`, ... (mudah ditebak). Sekarang `generateKodeKlien()` (`server/src/lib/sequence.ts`) menghasilkan kode 8-karakter acak kriptografis dari alfabet 31 simbol tanpa karakter ambigu (`0/O`, `1/I/L`), mis. `WT-7K4M9XQP` — ruang kemungkinan ±8×10¹¹, dikombinasikan dengan rate limit di bawah membuat brute-force tidak praktis.
- **Captcha matematika sederhana**, stateless (`server/src/lib/captcha.ts`): soal (`GET /api/cek-tagihan/captcha`) ditandatangani JWT 5 menit, diverifikasi ulang saat submit — tanpa perlu sesi server. Cukup untuk bot naif; kalau butuh proteksi lebih kuat dari bot canggih, tinggal ganti dengan hCaptcha/reCAPTCHA.
- **Rate limiting per IP** (`express-rate-limit`) di semua route `/api/cek-tagihan/*`: maksimal 10 pencarian/PDF dan 30 permintaan captcha per 15 menit per IP.
- **Data dibatasi seperlunya** — respons publik hanya berisi **nama depan**, kodeKlien, status reservasi, dan rincian tagihan. No. HP, email, dan alamat lengkap **tidak pernah dikirim** ke portal ini (dicek ulang lewat test end-to-end, lihat commit history). PDF invoice (yang butuh kodeKlien + invoiceId yang benar-benar cocok) tetap memuat data lengkap seperti invoice pada umumnya — itu dokumen pelanggan sendiri, bukan bocor ke pihak lain.

### 3.3 API

| Method | Path | Keterangan |
|---|---|---|
| GET | `/api/cek-tagihan/captcha` | `{ question, token }` — soal captcha baru |
| GET | `/api/cek-tagihan/:idKlien?captchaToken=&captchaAnswer=` | Publik, read-only, rate-limited. 404 kalau ID tidak ada (tidak membocorkan info) |
| GET | `/api/cek-tagihan/:idKlien/invoice/:invoiceId/pdf` | PDF invoice — invoice harus benar-benar milik pelanggan dengan kodeKlien tsb, kalau tidak → 404 |

### 3.4 Konfigurasi API backend untuk production

`cek/` adalah situs statis murni yang memanggil `server` lewat URL penuh (bukan proxy, karena tidak ada Node.js App-nya sendiri):

```bash
# cek/.env  (dibaca saat build, lihat cek/.env.example)
VITE_API_BASE_URL="https://admin.wonderlandtravel.com"
```

Dan di `server/.env`, tambahkan origin `cek` supaya diizinkan CORS:

```env
CEK_CLIENT_ORIGIN="https://cek.wonderlandtravel.com"
```

---

## 4. Deploy ke Hostinger Business Hosting

### 4.0 Deploy otomatis lewat GitHub Actions (edit tetap dari VS Code)

Website Publik dan Portal Cek Tagihan (keduanya situs statis) sudah punya workflow auto-deploy di [`.github/workflows/`](.github/workflows): **push ke `main` → GitHub otomatis build → upload ke Hostinger lewat FTP.** Alur kerja sehari-hari jadi cukup: edit di VS Code → commit → push, seperti biasa lewat Source Control panel — tidak perlu buka File Manager atau upload manual lagi.

Setup sekali saja:

1. **Buat akun FTP di hPanel** — *Files → FTP Accounts*:
   - Satu akun untuk `public_html` (atau pakai akun FTP utama hosting yang biasanya sudah otomatis diarahkan ke situ).
   - Satu akun lagi khusus untuk folder `cek.wonderlandtravel.com` (bikin subdomain-nya dulu di *Subdomains*, baru buat akun FTP yang document root-nya diarahkan ke folder subdomain itu — supaya ter-isolasi dari `public_html`).
   - Catat host, username, password masing-masing (host FTP Hostinger biasanya berupa IP server, terlihat di halaman yang sama).
2. **Tambahkan sebagai GitHub Secrets** — di halaman repo GitHub → *Settings → Secrets and variables → Actions → New repository secret*. **Kredensial ini tidak pernah perlu diketik di VS Code atau dibagikan ke siapa pun** — cukup disimpan di GitHub, dan workflow yang memakainya saat deploy:

   | Secret | Dipakai untuk |
   |---|---|
   | `HOSTINGER_FTP_SERVER` | Host FTP (dipakai kedua workflow) |
   | `HOSTINGER_FTP_USERNAME` / `HOSTINGER_FTP_PASSWORD` | Akun FTP untuk `public_html` (Website Publik) |
   | `HOSTINGER_CEK_FTP_USERNAME` / `HOSTINGER_CEK_FTP_PASSWORD` | Akun FTP untuk subdomain `cek` |
   | `CEK_API_BASE_URL` | URL backend admin yang sudah live, mis. `https://admin.wonderlandtravel.com` (dipakai saat build `cek/`) |

3. Selesai — push ke `main` yang menyentuh `src/**` akan trigger `deploy-website.yml`, dan yang menyentuh `cek/**` akan trigger `deploy-cek.yml`. Bisa juga dipicu manual lewat tab *Actions* di GitHub → pilih workflow → *Run workflow*.

> Workflow diset `dangerous-clean-slate: false` (tidak menghapus file lama di server yang sudah tak ada di build baru) supaya aman secara default. Setelah yakin semuanya berjalan mulus, boleh diubah ke `true` di file workflow-nya untuk keep server benar-benar sinkron dengan build terbaru.

**Bagian 2 (`server/` + `admin/`, Node.js App) belum diotomasi lewat cara ini** — FTP saja tidak cukup untuk Node.js App (butuh `npm install` + restart proses, bukan cuma copy file). Cara deploy-nya tetap manual/semi-manual lewat fitur Git bawaan hPanel, lihat §4.2 di bawah. Kalau paket hosting Anda punya akses SSH, ini bisa diotomasi juga lewat GitHub Actions — beri tahu saya kalau mau, saya bantu siapkan.

### 4.1 Website publik (Bagian 1)

**Otomatis:** sudah jalan lewat §4.0 di atas begitu secrets diisi.

**Manual (kalau perlu sekali-sekali tanpa lewat GitHub Actions):**

```bash
npm run build
```

Upload isi folder `out/` (hasil static export) ke `public_html` lewat File Manager/FTP di hPanel. Murni file statis — **tidak perlu** "Setup Node.js App" untuk bagian ini.

### 4.2 Backend + Admin (Bagian 2)

Belum diotomasi lewat GitHub Actions (lihat alasannya di §4.0) — deploy manual/lewat Git bawaan hPanel:

1. **Database** — hPanel → *MySQL Databases* → buat database + user + password. hPanel otomatis menyediakan **phpMyAdmin** untuk lihat/kelola data langsung. Salin info koneksinya ke `DATABASE_URL`.
2. **Subdomain** — hPanel → *Subdomains* → buat `admin.wonderlandtravel.com`, arahkan ke folder `server` (atau folder yang dikonfigurasi lewat "Setup Node.js App").
3. **Node.js App** — hPanel → *Setup Node.js App*:
   - Application root: folder `server`
   - Application startup file: `dist/index.js`
   - Set semua environment variable dari `server/.env.example` (isi dengan nilai production, terutama `DATABASE_URL`, `JWT_SECRET` yang baru/acak, `NODE_ENV=production`, `CLIENT_ORIGIN=https://admin.wonderlandtravel.com`, dan 3 variabel `CLOUDINARY_*` — **wajib diisi di production**, lihat §2.6).
4. Build kedua sisi sebelum/selama deploy:
   ```bash
   cd server && npm install && npm run build && npm run prisma:deploy && npm run seed
   cd ../admin && npm install && npm run build
   ```
   `server` yang sudah `NODE_ENV=production` otomatis menyajikan hasil build `admin/dist` sebagai static file untuk semua route selain `/api/*` — jadi **satu Node.js App di hPanel sudah cukup** untuk API + panel admin sekaligus.
5. Restart Node.js App dari hPanel setelah env var/kode berubah.

### 4.3 Portal Cek Tagihan (Bagian 3)

**Otomatis:** sudah jalan lewat §4.0 begitu secrets diisi (termasuk `CEK_API_BASE_URL`, jadi tidak perlu file `.env` manual untuk build lewat CI).

**Manual:** murni statis — **tidak perlu Node.js App**, sama seperti website publik:

1. Isi `cek/.env` dengan `VITE_API_BASE_URL="https://admin.wonderlandtravel.com"` (lihat §3.4).
2. Tambahkan `https://cek.wonderlandtravel.com` ke `CEK_CLIENT_ORIGIN` di `server/.env` production, lalu restart Node.js App admin (§4.2) supaya CORS mengizinkannya.
3. Build: `cd cek && npm install && npm run build`.
4. hPanel → *Subdomains* → buat `cek.wonderlandtravel.com`, arahkan document root-nya ke folder hasil build.
5. Upload isi `cek/dist` ke document root tersebut lewat File Manager/FTP.

---

## Checklist keamanan sebelum go-live

- [ ] Ganti `JWT_SECRET` jadi string acak baru (jangan pakai nilai dari `.env.example`/dev)
- [ ] Login dengan akun seed, langsung ganti passwordnya (lewat Prisma Studio untuk saat ini — halaman "ubah password" menyusul)
- [ ] Pastikan `NODE_ENV=production` di server production (supaya cookie sesi diberi flag `Secure` dan hanya terkirim lewat HTTPS)
- [ ] `CLIENT_ORIGIN` di-set ke domain admin yang sebenarnya, bukan `localhost`
- [ ] User database production **tidak** diberi privilege seluas `GRANT ALL ON *.*` — cukup akses ke database miliknya sendiri (default dari hPanel MySQL Databases sudah begini)
- [ ] 3 variabel `CLOUDINARY_*` sudah diisi di production — jangan biarkan upload gambar jatuh ke penyimpanan lokal yang tidak persisten (lihat §2.6)
- [ ] `CEK_CLIENT_ORIGIN` di-set ke domain `cek` yang sebenarnya, dan `cek/.env` (`VITE_API_BASE_URL`) di-build mengarah ke domain `admin` yang sebenarnya (lihat §3.4)
- [ ] Pastikan setiap pelanggan baru selalu lewat `generateKodeKlien()` (acak) — **jangan pernah** isi `kodeKlien` manual dengan pola berurutan, itu persis yang diminta dihindari di spesifikasi Fase 9
- [ ] Ke-6 GitHub Secrets untuk auto-deploy sudah diisi (lihat §4.0) — `HOSTINGER_FTP_SERVER`, `HOSTINGER_FTP_USERNAME`/`PASSWORD`, `HOSTINGER_CEK_FTP_USERNAME`/`PASSWORD`, `CEK_API_BASE_URL`
