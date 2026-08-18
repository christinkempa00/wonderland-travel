-- Menghapus kolom `support_for` (Dukungan) dari tabel `orders`.
--
-- Konsep "Dukungan" sudah dihapus dari seluruh aplikasi (form, filter, dan
-- fitur Analisa Keuangan yang sekarang dikelompokkan per Event, bukan lagi
-- per Dukungan). Kolom ini sudah tidak dibaca/ditulis oleh kode manapun.
--
-- OPSIONAL — jalankan manual lewat phpMyAdmin kapan pun siap. Tidak
-- dijalankan otomatis oleh aplikasi. Sebaiknya backup tabel `orders`
-- dulu sebelum menjalankan ini kalau masih butuh data historisnya.

ALTER TABLE `orders` DROP INDEX `support_for`;
ALTER TABLE `orders` DROP COLUMN `support_for`;
