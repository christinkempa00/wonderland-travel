-- Portal klien: klien login pakai client_code + password sendiri untuk
-- lihat tagihan tersisa & riwayat pesanan mereka. Perubahan aditif —
-- kolom baru default NULL/0, tidak menyentuh baris klien yang sudah ada.
--
-- WAJIB dijalankan manual lewat phpMyAdmin.

ALTER TABLE `clients`
    ADD COLUMN `password` VARCHAR(255) NULL AFTER `client_code`,
    ADD COLUMN `portal_enabled` TINYINT(1) NOT NULL DEFAULT 0 AFTER `password`;

-- client_code dipakai sebagai "username" login, jadi harus unik. Aman
-- ditambahkan sekarang — belum ada data client_code yang duplikat.
ALTER TABLE `clients` ADD UNIQUE KEY `uniq_client_code` (`client_code`);
