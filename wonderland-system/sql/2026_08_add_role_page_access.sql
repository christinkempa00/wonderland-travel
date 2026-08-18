-- Tabel untuk fitur "Super Admin atur halaman yang bisa diakses Admin".
-- Baris hanya dibuat untuk page yang DINONAKTIFKAN untuk suatu role — page
-- tanpa baris di sini tetap terlihat/bisa diakses seperti biasa, jadi tabel
-- kosong (sebelum migrasi ini dipakai) tidak mengunci siapa pun.
--
-- WAJIB dijalankan manual lewat phpMyAdmin sebelum halaman
-- Pengaturan > Akses Halaman bisa dipakai.

CREATE TABLE IF NOT EXISTS `role_page_access` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `company_id` INT UNSIGNED NOT NULL,
    `role` VARCHAR(30) NOT NULL,
    `page_key` VARCHAR(50) NOT NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `company_role_page` (`company_id`, `role`, `page_key`),
    CONSTRAINT `fk_rpa_company` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
