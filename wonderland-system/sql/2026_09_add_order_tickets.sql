-- Fitur E-Tiket: admin bisa upload beberapa file PDF e-tiket per pesanan
-- (misal tiket pesawat, kereta, dll) supaya mudah dicek ulang. Khusus
-- admin — tidak ditampilkan di Portal Klien. Tabel baru, tidak menyentuh
-- data yang sudah ada.
--
-- WAJIB dijalankan manual lewat phpMyAdmin.

CREATE TABLE IF NOT EXISTS `order_tickets` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `order_id` INT UNSIGNED NOT NULL,
    `file_name` VARCHAR(255) NOT NULL,
    `file_path` VARCHAR(255) NOT NULL,
    `file_size` INT UNSIGNED NOT NULL DEFAULT 0,
    `uploaded_by` INT UNSIGNED NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`), KEY `order_id` (`order_id`),
    CONSTRAINT `fk_ot_order` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
