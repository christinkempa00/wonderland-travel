-- Tanggal invoice yang tercetak di dokumen invoice sekarang bisa diisi
-- manual oleh admin (bukan otomatis dari tanggal selesai kegiatan lagi).
-- Kolom baru, nullable — kalau kosong, doc.php fallback ke tanggal selesai
-- kegiatan lalu ke tanggal cetak, jadi order lama tidak terpengaruh sama
-- sekali.
--
-- WAJIB dijalankan manual lewat phpMyAdmin.

ALTER TABLE `orders`
    ADD COLUMN `invoice_date` DATE NULL AFTER `pelni_invoice_number`;
