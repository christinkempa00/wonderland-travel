-- Menambahkan kolom `cashback` ke tabel `order_items` untuk fitur cashback
-- per item pesanan: cashback ditambahkan ke harga beli (base_price) SEBELUM
-- markup dihitung. Contoh: harga beli 200.000 + cashback 25.000 = 225.000,
-- baru markup 5-12% diterapkan di atas 225.000 itu.
--
-- WAJIB dijalankan manual lewat phpMyAdmin sebelum fitur cashback bisa
-- dipakai di server produksi — aplikasi tidak menjalankan ini otomatis.

ALTER TABLE `order_items`
    ADD COLUMN `cashback` DECIMAL(15,2) NOT NULL DEFAULT 0
    COMMENT 'Ditambahkan ke base_price sebelum markup dihitung'
    AFTER `base_price`;
