-- Tabel `service_includes` ("Harga Sudah Termasuk" per jenis layanan, dikelola
-- lewat settings-service-includes.php) ternyata tidak pernah ada di database
-- production — bukan cuma hilang dari sql/install.sql, tapi memang belum
-- pernah dibuat. Ini yang bikin invoice/kwitansi (doc.php) 500 untuk SEMUA
-- pesanan, bukan cuma satu pesanan tertentu.
--
-- WAJIB dijalankan manual lewat phpMyAdmin.

CREATE TABLE IF NOT EXISTS `service_includes` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `service_type` VARCHAR(50) NOT NULL,
    `include_item` VARCHAR(255) NOT NULL,
    `is_active` TINYINT(1) NOT NULL DEFAULT 1,
    `sort_order` INT NOT NULL DEFAULT 0,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `service_type` (`service_type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
