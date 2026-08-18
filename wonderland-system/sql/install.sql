-- ================================================================
-- TRAVEL MANAGEMENT SYSTEM - DATABASE SCHEMA
-- ================================================================
-- Database: u251802700_travel
-- Created for: travel.pelitaintimulia.com
-- Version: 1.0.0
-- ================================================================

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+07:00";

-- ================================================================
-- TABLE: companies
-- Menyimpan data perusahaan (multi-company support)
-- ================================================================
CREATE TABLE IF NOT EXISTS `companies` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `name` VARCHAR(255) NOT NULL,
    `slug` VARCHAR(100) NOT NULL,
    `address` TEXT NULL,
    `phone` VARCHAR(50) NULL,
    `email` VARCHAR(100) NULL,
    `website` VARCHAR(255) NULL,
    `logo` VARCHAR(255) NULL,
    `favicon` VARCHAR(255) NULL,
    `bank_accounts` JSON NULL COMMENT 'Array of {bank_name, account_no, account_name}',
    `npwp` VARCHAR(50) NULL,
    `is_active` TINYINT(1) NOT NULL DEFAULT 1,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NULL ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `slug` (`slug`),
    KEY `is_active` (`is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ================================================================
-- TABLE: users
-- Data user semua role
-- ================================================================
CREATE TABLE IF NOT EXISTS `users` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `name` VARCHAR(100) NOT NULL,
    `email` VARCHAR(100) NOT NULL,
    `phone` VARCHAR(50) NULL,
    `password` VARCHAR(255) NOT NULL,
    `role` ENUM('superadmin', 'admin', 'staff', 'accounting', 'viewer') NOT NULL DEFAULT 'staff',
    `avatar` VARCHAR(255) NULL,
    `is_active` TINYINT(1) NOT NULL DEFAULT 1,
    `last_login` DATETIME NULL,
    `remember_token` VARCHAR(100) NULL,
    `reset_token` VARCHAR(100) NULL,
    `reset_expires` DATETIME NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NULL ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `email` (`email`),
    KEY `role` (`role`),
    KEY `is_active` (`is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ================================================================
-- TABLE: user_companies
-- Relasi user dengan company (many-to-many)
-- ================================================================
CREATE TABLE IF NOT EXISTS `user_companies` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `user_id` INT UNSIGNED NOT NULL,
    `company_id` INT UNSIGNED NOT NULL,
    `is_active` TINYINT(1) NOT NULL DEFAULT 1,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `user_company` (`user_id`, `company_id`),
    KEY `company_id` (`company_id`),
    CONSTRAINT `fk_uc_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_uc_company` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ================================================================
-- TABLE: clients
-- Data client/customer per company
-- ================================================================
CREATE TABLE IF NOT EXISTS `clients` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `client_code` VARCHAR(30) NULL,
    `company_id` INT UNSIGNED NOT NULL,
    `name` VARCHAR(255) NOT NULL,
    `contact_person` VARCHAR(100) NULL,
    `phone` VARCHAR(50) NULL,
    `email` VARCHAR(100) NULL,
    `address` TEXT NULL,
    `npwp` VARCHAR(50) NULL,
    `pic_name` VARCHAR(100) NULL COMMENT 'Person in Charge',
    `pic_phone` VARCHAR(50) NULL,
    `notes` TEXT NULL,
    `is_active` TINYINT(1) NOT NULL DEFAULT 1,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NULL ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `company_client_code` (`company_id`, `client_code`),
    KEY `company_id` (`company_id`),
    KEY `name` (`name`),
    CONSTRAINT `fk_clients_company` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ================================================================
-- TABLE: orders
-- Data pesanan utama
-- ================================================================
CREATE TABLE IF NOT EXISTS `orders` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `company_id` INT UNSIGNED NOT NULL,
    `client_id` INT UNSIGNED NOT NULL,
    `order_number` VARCHAR(50) NOT NULL,
    `order_date` DATE NOT NULL,
    `event_name` VARCHAR(255) NULL COMMENT 'Nama kegiatan',
    `event_date_start` DATE NULL,
    `event_date_end` DATE NULL,
    `event_date` DATE NULL,
    `event_end_date` DATE NULL,
    `event_location` VARCHAR(255) NULL,
    `support_for` VARCHAR(150) NULL COMMENT 'Dukungan/sponsor kegiatan',
    `description` TEXT NULL,
    `total_base_price` DECIMAL(15,2) NOT NULL DEFAULT 0,
    `total_markup` DECIMAL(15,2) NOT NULL DEFAULT 0,
    `total_final_price` DECIMAL(15,2) NOT NULL DEFAULT 0,
    `total_profit` DECIMAL(15,2) NOT NULL DEFAULT 0,
    `status` ENUM('draft', 'quotation', 'agreed', 'invoiced', 'paid', 'completed', 'cancelled') NOT NULL DEFAULT 'draft',
    `payment_status` ENUM('unpaid', 'partial', 'paid') NOT NULL DEFAULT 'unpaid',
    `paid_amount` DECIMAL(15,2) NOT NULL DEFAULT 0,
    `paid_at` DATE NULL,
    `service_type` VARCHAR(30) NULL,
    `expense_paid_amount` DECIMAL(15,2) NOT NULL DEFAULT 0,
    `notes` TEXT NULL,
    `internal_notes` TEXT NULL COMMENT 'Catatan internal tidak tampil di dokumen',
    `created_by` INT UNSIGNED NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NULL ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `order_number` (`company_id`, `order_number`),
    KEY `client_id` (`client_id`),
    KEY `status` (`status`),
    KEY `order_date` (`order_date`),
    KEY `created_by` (`created_by`),
    KEY `support_for` (`support_for`),
    CONSTRAINT `fk_orders_company` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_orders_client` FOREIGN KEY (`client_id`) REFERENCES `clients` (`id`) ON DELETE RESTRICT,
    CONSTRAINT `fk_orders_user` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ================================================================
-- TABLE: order_items
-- Detail item pesanan (bus, towing, hotel, travel, rental)
-- ================================================================
CREATE TABLE IF NOT EXISTS `order_items` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `order_id` INT UNSIGNED NOT NULL,
    `item_type` ENUM('bus', 'towing', 'hotel', 'travel', 'rental', 'flight', 'restaurant', 'other') NOT NULL,
    `item_name` VARCHAR(255) NOT NULL DEFAULT '' COMMENT 'Nama item/layanan',
    `description` TEXT NULL,
    `quantity` INT NOT NULL DEFAULT 1,
    `num_days` INT NOT NULL DEFAULT 1,
    `unit` VARCHAR(50) NULL COMMENT 'unit, pax, malam, hari, dll',
    `details_json` JSON NULL COMMENT 'Detail spesifik per jenis: origin, destination, vehicle_type, etc',
    `base_price` DECIMAL(15,2) NOT NULL DEFAULT 0,
    `markup_type` ENUM('percentage', 'fixed') NOT NULL DEFAULT 'percentage',
    `markup_value` DECIMAL(15,2) NOT NULL DEFAULT 0,
    `markup_amount` DECIMAL(15,2) NOT NULL DEFAULT 0,
    `final_price` DECIMAL(15,2) NOT NULL DEFAULT 0,
    `profit` DECIMAL(15,2) NOT NULL DEFAULT 0,
    `attachment_logo` VARCHAR(255) NULL,
    `vehicle_plate` VARCHAR(20) NULL,
    `hotel_name` VARCHAR(255) NULL,
    `room_type` VARCHAR(100) NULL,
    `check_in_date` DATE NULL,
    `check_out_date` DATE NULL,
    `expense_status` ENUM('pending', 'paid') NOT NULL DEFAULT 'pending',
    `expense_bank_cash_id` INT UNSIGNED NULL,
    `expense_date` DATE NULL,
    `expense_journal_id` INT UNSIGNED NULL,
    `expense_notes` TEXT NULL,
    `sort_order` INT NOT NULL DEFAULT 0,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NULL ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `order_id` (`order_id`),
    KEY `item_type` (`item_type`),
    CONSTRAINT `fk_items_order` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ================================================================
-- TABLE: hotel_guests / flight_details / rental_details / vehicle_documents
-- Detail per-item attachments (tamu hotel, penumpang pesawat, kendaraan sewa, dokumen kendaraan)
-- ================================================================
CREATE TABLE IF NOT EXISTS `hotel_guests` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `order_id` INT UNSIGNED NOT NULL,
    `order_item_id` INT UNSIGNED NULL,
    `hotel_name` VARCHAR(255) NULL,
    `guest_name` VARCHAR(150) NOT NULL,
    `room_number` VARCHAR(50) NULL,
    `room_type` VARCHAR(100) NULL,
    `check_in_date` DATE NULL,
    `check_out_date` DATE NULL,
    `notes` TEXT NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `order_id` (`order_id`),
    KEY `order_item_id` (`order_item_id`),
    CONSTRAINT `fk_hg_order` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `flight_details` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `order_id` INT UNSIGNED NOT NULL,
    `order_item_id` INT UNSIGNED NULL,
    `flight_name` VARCHAR(255) NULL,
    `passenger_name` VARCHAR(150) NOT NULL,
    `description` TEXT NULL,
    `price` DECIMAL(15,2) NOT NULL DEFAULT 0,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `order_id` (`order_id`),
    KEY `order_item_id` (`order_item_id`),
    CONSTRAINT `fk_fd_order` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `rental_details` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `order_id` INT UNSIGNED NOT NULL,
    `order_item_id` INT UNSIGNED NULL,
    `vehicle_type` VARCHAR(100) NULL,
    `start_date` DATE NULL,
    `end_date` DATE NULL,
    `same_price` TINYINT(1) NOT NULL DEFAULT 1,
    `price_per_day` DECIMAL(15,2) NOT NULL DEFAULT 0,
    `daily_prices` JSON NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `order_id` (`order_id`),
    KEY `order_item_id` (`order_item_id`),
    CONSTRAINT `fk_rd_order` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `vehicle_documents` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `order_id` INT UNSIGNED NOT NULL,
    `order_item_id` INT UNSIGNED NULL,
    `vehicle_type` VARCHAR(100) NULL,
    `plate_number` VARCHAR(20) NULL,
    `driver_name` VARCHAR(150) NULL,
    `photo_driver` VARCHAR(255) NULL,
    `photo_sim` VARCHAR(255) NULL,
    `photo_stnk` VARCHAR(255) NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `order_id` (`order_id`),
    KEY `order_item_id` (`order_item_id`),
    CONSTRAINT `fk_vd_order` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ================================================================
-- TABLE: documents
-- Dokumen yang di-generate (penawaran, kesepakatan, invoice, kwitansi)
-- ================================================================
CREATE TABLE IF NOT EXISTS `documents` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `order_id` INT UNSIGNED NOT NULL,
    `company_id` INT UNSIGNED NOT NULL,
    `doc_type` ENUM('quotation', 'agreement', 'invoice', 'receipt') NOT NULL,
    `doc_number` VARCHAR(100) NOT NULL,
    `issue_date` DATE NOT NULL,
    `due_date` DATE NULL COMMENT 'Untuk invoice',
    `notes` TEXT NULL,
    `template_id` INT UNSIGNED NULL,
    `generated_file` VARCHAR(255) NULL COMMENT 'Path file PDF jika disimpan',
    `sent_at` DATETIME NULL COMMENT 'Waktu dikirim via Dripsender',
    `created_by` INT UNSIGNED NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NULL ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `doc_number` (`company_id`, `doc_type`, `doc_number`),
    KEY `order_id` (`order_id`),
    KEY `doc_type` (`doc_type`),
    KEY `issue_date` (`issue_date`),
    CONSTRAINT `fk_docs_order` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_docs_company` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_docs_user` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ================================================================
-- TABLE: document_numbers
-- Counter untuk auto-generate nomor surat per bulan
-- ================================================================
CREATE TABLE IF NOT EXISTS `document_numbers` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `company_id` INT UNSIGNED NOT NULL,
    `doc_type` ENUM('quotation', 'agreement', 'invoice', 'receipt', 'order') NOT NULL,
    `year` SMALLINT UNSIGNED NOT NULL,
    `month` TINYINT UNSIGNED NOT NULL,
    `last_number` INT UNSIGNED NOT NULL DEFAULT 0,
    `prefix` VARCHAR(20) NULL,
    `updated_at` DATETIME NULL ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `company_doc_period` (`company_id`, `doc_type`, `year`, `month`),
    CONSTRAINT `fk_docnum_company` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ================================================================
-- TABLE: document_templates
-- Template dokumen dengan layout drag-drop
-- ================================================================
CREATE TABLE IF NOT EXISTS `document_templates` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `company_id` INT UNSIGNED NOT NULL,
    `doc_type` ENUM('quotation', 'agreement', 'invoice', 'receipt') NOT NULL,
    `name` VARCHAR(100) NOT NULL DEFAULT 'Default',
    `layout_json` JSON NULL COMMENT 'Posisi elemen: header, logo, address, table, footer, etc',
    `paper_size` ENUM('A4', 'Letter', 'Legal', 'F4') NOT NULL DEFAULT 'A4',
    `orientation` ENUM('portrait', 'landscape') NOT NULL DEFAULT 'portrait',
    `margin_top` DECIMAL(5,2) NOT NULL DEFAULT 20,
    `margin_right` DECIMAL(5,2) NOT NULL DEFAULT 15,
    `margin_bottom` DECIMAL(5,2) NOT NULL DEFAULT 20,
    `margin_left` DECIMAL(5,2) NOT NULL DEFAULT 15,
    `header_html` TEXT NULL,
    `footer_html` TEXT NULL,
    `body_html` TEXT NULL COMMENT 'Template body dengan placeholder',
    `css_custom` TEXT NULL,
    `is_default` TINYINT(1) NOT NULL DEFAULT 0,
    `is_active` TINYINT(1) NOT NULL DEFAULT 1,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NULL ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `company_id` (`company_id`),
    KEY `doc_type` (`doc_type`),
    CONSTRAINT `fk_templates_company` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ================================================================
-- TABLE: profit_recipients
-- Penerima bagi hasil per company
-- ================================================================
CREATE TABLE IF NOT EXISTS `profit_recipients` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `company_id` INT UNSIGNED NOT NULL,
    `name` VARCHAR(100) NOT NULL,
    `phone` VARCHAR(50) NULL COMMENT 'Untuk notifikasi Dripsender',
    `percentage` DECIMAL(5,2) NOT NULL DEFAULT 0,
    `is_komisaris` TINYINT(1) NOT NULL DEFAULT 0 COMMENT 'Terima notifikasi invoice',
    `is_active` TINYINT(1) NOT NULL DEFAULT 1,
    `sort_order` INT NOT NULL DEFAULT 0,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NULL ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `company_id` (`company_id`),
    CONSTRAINT `fk_recipients_company` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ================================================================
-- TABLE: profit_shares
-- Pembagian profit per order
-- ================================================================
CREATE TABLE IF NOT EXISTS `profit_shares` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `order_id` INT UNSIGNED NOT NULL,
    `recipient_id` INT UNSIGNED NOT NULL,
    `percentage` DECIMAL(5,2) NOT NULL,
    `amount` DECIMAL(15,2) NOT NULL DEFAULT 0,
    `status` ENUM('pending', 'paid') NOT NULL DEFAULT 'pending',
    `paid_date` DATE NULL,
    `paid_by` INT UNSIGNED NULL,
    `notes` TEXT NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NULL ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `order_recipient` (`order_id`, `recipient_id`),
    KEY `recipient_id` (`recipient_id`),
    KEY `status` (`status`),
    CONSTRAINT `fk_shares_order` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_shares_recipient` FOREIGN KEY (`recipient_id`) REFERENCES `profit_recipients` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_shares_paidby` FOREIGN KEY (`paid_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ================================================================
-- TABLE: accounts (Chart of Accounts)
-- Daftar akun untuk akuntansi
-- ================================================================
CREATE TABLE IF NOT EXISTS `accounts` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `company_id` INT UNSIGNED NOT NULL,
    `code` VARCHAR(20) NOT NULL,
    `name` VARCHAR(100) NOT NULL,
    `type` ENUM('asset', 'liability', 'equity', 'revenue', 'expense') NOT NULL,
    `parent_id` INT UNSIGNED NULL,
    `description` TEXT NULL,
    `normal_balance` ENUM('debit', 'credit') NOT NULL,
    `is_cash_bank` TINYINT(1) NOT NULL DEFAULT 0 COMMENT 'Apakah akun kas/bank',
    `is_system` TINYINT(1) NOT NULL DEFAULT 0 COMMENT 'Akun sistem tidak bisa dihapus',
    `is_active` TINYINT(1) NOT NULL DEFAULT 1,
    `sort_order` INT NOT NULL DEFAULT 0,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NULL ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `company_code` (`company_id`, `code`),
    KEY `type` (`type`),
    KEY `parent_id` (`parent_id`),
    CONSTRAINT `fk_accounts_company` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_accounts_parent` FOREIGN KEY (`parent_id`) REFERENCES `accounts` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ================================================================
-- TABLE: bank_cash
-- Daftar kas dan bank per company
-- ================================================================
CREATE TABLE IF NOT EXISTS `bank_cash` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `company_id` INT UNSIGNED NOT NULL,
    `account_id` INT UNSIGNED NULL COMMENT 'Link ke chart of accounts',
    `name` VARCHAR(100) NOT NULL,
    `type` ENUM('cash', 'bank') NOT NULL,
    `bank_name` VARCHAR(100) NULL,
    `account_no` VARCHAR(50) NULL,
    `account_name` VARCHAR(100) NULL,
    `initial_balance` DECIMAL(15,2) NOT NULL DEFAULT 0,
    `current_balance` DECIMAL(15,2) NOT NULL DEFAULT 0,
    `is_default` TINYINT(1) NOT NULL DEFAULT 0,
    `is_active` TINYINT(1) NOT NULL DEFAULT 1,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NULL ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `company_id` (`company_id`),
    KEY `account_id` (`account_id`),
    CONSTRAINT `fk_bankcash_company` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_bankcash_account` FOREIGN KEY (`account_id`) REFERENCES `accounts` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ================================================================
-- TABLE: journals
-- Jurnal transaksi
-- ================================================================
CREATE TABLE IF NOT EXISTS `journals` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `company_id` INT UNSIGNED NOT NULL,
    `journal_no` VARCHAR(50) NOT NULL,
    `journal_date` DATE NOT NULL,
    `description` TEXT NULL,
    `reference` VARCHAR(100) NULL COMMENT 'No. Ref dokumen terkait',
    `order_id` INT UNSIGNED NULL COMMENT 'Jika terkait order',
    `total_debit` DECIMAL(15,2) NOT NULL DEFAULT 0,
    `total_credit` DECIMAL(15,2) NOT NULL DEFAULT 0,
    `is_posted` TINYINT(1) NOT NULL DEFAULT 0,
    `posted_at` DATETIME NULL,
    `posted_by` INT UNSIGNED NULL,
    `is_system` TINYINT(1) NOT NULL DEFAULT 0 COMMENT 'Auto-generated dari order',
    `created_by` INT UNSIGNED NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NULL ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `journal_no` (`company_id`, `journal_no`),
    KEY `journal_date` (`journal_date`),
    KEY `order_id` (`order_id`),
    CONSTRAINT `fk_journals_company` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_journals_order` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE SET NULL,
    CONSTRAINT `fk_journals_postedby` FOREIGN KEY (`posted_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
    CONSTRAINT `fk_journals_createdby` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ================================================================
-- TABLE: journal_items
-- Detail jurnal (debit/credit per akun)
-- ================================================================
CREATE TABLE IF NOT EXISTS `journal_items` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `journal_id` INT UNSIGNED NOT NULL,
    `account_id` INT UNSIGNED NOT NULL,
    `description` VARCHAR(255) NULL,
    `debit` DECIMAL(15,2) NOT NULL DEFAULT 0,
    `credit` DECIMAL(15,2) NOT NULL DEFAULT 0,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `journal_id` (`journal_id`),
    KEY `account_id` (`account_id`),
    CONSTRAINT `fk_jitems_journal` FOREIGN KEY (`journal_id`) REFERENCES `journals` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_jitems_account` FOREIGN KEY (`account_id`) REFERENCES `accounts` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ================================================================
-- TABLE: ledgers
-- Buku besar per periode (untuk performa)
-- ================================================================
CREATE TABLE IF NOT EXISTS `ledgers` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `company_id` INT UNSIGNED NOT NULL,
    `account_id` INT UNSIGNED NOT NULL,
    `period` CHAR(7) NOT NULL COMMENT 'Format: YYYY-MM',
    `opening_balance` DECIMAL(15,2) NOT NULL DEFAULT 0,
    `debit_total` DECIMAL(15,2) NOT NULL DEFAULT 0,
    `credit_total` DECIMAL(15,2) NOT NULL DEFAULT 0,
    `closing_balance` DECIMAL(15,2) NOT NULL DEFAULT 0,
    `updated_at` DATETIME NULL ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `company_account_period` (`company_id`, `account_id`, `period`),
    KEY `account_id` (`account_id`),
    CONSTRAINT `fk_ledgers_company` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_ledgers_account` FOREIGN KEY (`account_id`) REFERENCES `accounts` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ================================================================
-- TABLE: company_settings
-- Pengaturan per company
-- ================================================================
CREATE TABLE IF NOT EXISTS `company_settings` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `company_id` INT UNSIGNED NOT NULL,
    `setting_key` VARCHAR(100) NOT NULL,
    `setting_value` TEXT NULL,
    `setting_type` ENUM('text', 'number', 'boolean', 'json') NOT NULL DEFAULT 'text',
    `updated_at` DATETIME NULL ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `company_key` (`company_id`, `setting_key`),
    CONSTRAINT `fk_settings_company` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ================================================================
-- TABLE: activity_logs
-- Log aktivitas user
-- ================================================================
CREATE TABLE IF NOT EXISTS `activity_logs` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `user_id` INT UNSIGNED NULL,
    `company_id` INT UNSIGNED NULL,
    `action` VARCHAR(50) NOT NULL,
    `module` VARCHAR(50) NULL,
    `record_id` INT UNSIGNED NULL,
    `record_type` VARCHAR(50) NULL,
    `description` TEXT NULL,
    `old_values` JSON NULL,
    `new_values` JSON NULL,
    `ip_address` VARCHAR(45) NULL,
    `user_agent` VARCHAR(255) NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `user_id` (`user_id`),
    KEY `company_id` (`company_id`),
    KEY `action` (`action`),
    KEY `created_at` (`created_at`),
    CONSTRAINT `fk_logs_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
    CONSTRAINT `fk_logs_company` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ================================================================
-- TABLE: notifications
-- Notifikasi untuk user
-- ================================================================
CREATE TABLE IF NOT EXISTS `notifications` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `user_id` INT UNSIGNED NOT NULL,
    `company_id` INT UNSIGNED NULL,
    `title` VARCHAR(255) NOT NULL,
    `message` TEXT NULL,
    `type` VARCHAR(50) NOT NULL DEFAULT 'info',
    `link` VARCHAR(255) NULL,
    `is_read` TINYINT(1) NOT NULL DEFAULT 0,
    `read_at` DATETIME NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `user_id` (`user_id`),
    KEY `is_read` (`is_read`),
    CONSTRAINT `fk_notif_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ================================================================
-- TABLE: dripsender_logs
-- Log pengiriman WhatsApp via Dripsender
-- ================================================================
CREATE TABLE IF NOT EXISTS `dripsender_logs` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `company_id` INT UNSIGNED NOT NULL,
    `document_id` INT UNSIGNED NULL,
    `recipient_phone` VARCHAR(50) NOT NULL,
    `recipient_name` VARCHAR(100) NULL,
    `message_type` VARCHAR(50) NULL COMMENT 'invoice_issued, payment_confirmed, etc',
    `message_content` TEXT NULL,
    `status` ENUM('pending', 'sent', 'delivered', 'failed') NOT NULL DEFAULT 'pending',
    `response_data` JSON NULL,
    `sent_at` DATETIME NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `company_id` (`company_id`),
    KEY `document_id` (`document_id`),
    KEY `status` (`status`),
    CONSTRAINT `fk_drip_company` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_drip_document` FOREIGN KEY (`document_id`) REFERENCES `documents` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ================================================================
-- TABLE: payment_records
-- Catatan pembayaran dari client
-- ================================================================
CREATE TABLE IF NOT EXISTS `payment_records` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `order_id` INT UNSIGNED NOT NULL,
    `company_id` INT UNSIGNED NOT NULL,
    `bank_cash_id` INT UNSIGNED NULL,
    `payment_date` DATE NOT NULL,
    `amount` DECIMAL(15,2) NOT NULL,
    `payment_method` VARCHAR(50) NULL COMMENT 'transfer, cash, etc',
    `reference_no` VARCHAR(100) NULL,
    `notes` TEXT NULL,
    `receipt_id` INT UNSIGNED NULL COMMENT 'Link ke kwitansi',
    `journal_id` INT UNSIGNED NULL COMMENT 'Link ke jurnal',
    `created_by` INT UNSIGNED NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NULL ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `order_id` (`order_id`),
    KEY `company_id` (`company_id`),
    KEY `payment_date` (`payment_date`),
    CONSTRAINT `fk_payment_order` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_payment_company` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_payment_bankcash` FOREIGN KEY (`bank_cash_id`) REFERENCES `bank_cash` (`id`) ON DELETE SET NULL,
    CONSTRAINT `fk_payment_receipt` FOREIGN KEY (`receipt_id`) REFERENCES `documents` (`id`) ON DELETE SET NULL,
    CONSTRAINT `fk_payment_journal` FOREIGN KEY (`journal_id`) REFERENCES `journals` (`id`) ON DELETE SET NULL,
    CONSTRAINT `fk_payment_user` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ================================================================
-- TABLE: invoices / invoice_items
-- Invoice resmi dengan item tersendiri, terpisah dari order_items
-- ================================================================
CREATE TABLE IF NOT EXISTS `invoices` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `company_id` INT UNSIGNED NOT NULL,
    `order_id` INT UNSIGNED NOT NULL,
    `invoice_number` VARCHAR(50) NOT NULL,
    `invoice_date` DATE NOT NULL,
    `due_date` DATE NULL,
    `discount` DECIMAL(15,2) NOT NULL DEFAULT 0,
    `subtotal` DECIMAL(15,2) NOT NULL DEFAULT 0,
    `total` DECIMAL(15,2) NOT NULL DEFAULT 0,
    `paid_amount` DECIMAL(15,2) NOT NULL DEFAULT 0,
    `payment_status` ENUM('unpaid', 'partial', 'paid') NOT NULL DEFAULT 'unpaid',
    `notes` TEXT NULL,
    `created_by` INT UNSIGNED NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NULL ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `company_invoice_number` (`company_id`, `invoice_number`),
    KEY `order_id` (`order_id`),
    KEY `payment_status` (`payment_status`),
    CONSTRAINT `fk_inv_company` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_inv_order` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_inv_user` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `invoice_items` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `invoice_id` INT UNSIGNED NOT NULL,
    `name` VARCHAR(255) NOT NULL,
    `quantity` INT NOT NULL DEFAULT 1,
    `unit_price` DECIMAL(15,2) NOT NULL DEFAULT 0,
    `subtotal` DECIMAL(15,2) NOT NULL DEFAULT 0,
    `sort_order` INT NOT NULL DEFAULT 0,
    PRIMARY KEY (`id`),
    KEY `invoice_id` (`invoice_id`),
    CONSTRAINT `fk_invitems_invoice` FOREIGN KEY (`invoice_id`) REFERENCES `invoices` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ================================================================
-- TABLE: order_payments
-- Pembayaran aktual (order-level atau invoice-level), terpisah dari
-- payment_records lama yang sudah tidak dipakai runtime
-- ================================================================
CREATE TABLE IF NOT EXISTS `order_payments` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `company_id` INT UNSIGNED NOT NULL,
    `order_id` INT UNSIGNED NOT NULL,
    `invoice_id` INT UNSIGNED NULL COMMENT 'diisi kalau pembayaran dicatat lewat invoice',
    `journal_id` INT UNSIGNED NULL COMMENT 'tanpa FK - skema akuntansi terpisah dari modul ini',
    `amount` DECIMAL(15,2) NOT NULL,
    `payment_date` DATE NOT NULL,
    `payment_method` ENUM('transfer', 'ewallet', 'credit_card', 'cash', 'other') NOT NULL DEFAULT 'transfer',
    `bank_cash_id` INT UNSIGNED NULL,
    `reference` VARCHAR(100) NULL,
    `notes` TEXT NULL,
    `proof_image` VARCHAR(255) NULL COMMENT 'nama file di uploads/payment-proofs/',
    `status` VARCHAR(20) NOT NULL DEFAULT 'posted',
    `created_by` INT UNSIGNED NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NULL ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `order_id` (`order_id`),
    KEY `invoice_id` (`invoice_id`),
    KEY `company_id` (`company_id`),
    CONSTRAINT `fk_op_company` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_op_order` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_op_invoice` FOREIGN KEY (`invoice_id`) REFERENCES `invoices` (`id`) ON DELETE SET NULL,
    CONSTRAINT `fk_op_bankcash` FOREIGN KEY (`bank_cash_id`) REFERENCES `bank_cash` (`id`) ON DELETE SET NULL,
    CONSTRAINT `fk_op_user` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ================================================================
-- TABLE: client_communications
-- Riwayat komunikasi dengan klien (telepon/WhatsApp/email/tatap muka)
-- ================================================================
CREATE TABLE IF NOT EXISTS `client_communications` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `company_id` INT UNSIGNED NOT NULL,
    `client_id` INT UNSIGNED NOT NULL,
    `type` ENUM('phone', 'whatsapp', 'email', 'in_person', 'other') NOT NULL DEFAULT 'phone',
    `notes` TEXT NULL,
    `communication_date` DATE NOT NULL,
    `created_by` INT UNSIGNED NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `client_id` (`client_id`),
    KEY `company_id` (`company_id`),
    CONSTRAINT `fk_comm_client` FOREIGN KEY (`client_id`) REFERENCES `clients` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_comm_company` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_comm_user` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ================================================================
-- TABLE: fiscal_years
-- Tahun buku akuntansi
-- ================================================================
CREATE TABLE IF NOT EXISTS `fiscal_years` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `company_id` INT UNSIGNED NOT NULL,
    `name` VARCHAR(50) NOT NULL,
    `start_date` DATE NOT NULL,
    `end_date` DATE NOT NULL,
    `is_closed` TINYINT(1) NOT NULL DEFAULT 0,
    `closed_at` DATETIME NULL,
    `closed_by` INT UNSIGNED NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `company_id` (`company_id`),
    CONSTRAINT `fk_fiscal_company` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ================================================================
-- DEFAULT DATA
-- ================================================================

-- Default settings keys yang akan dibuat saat company baru
-- Ini hanya sebagai referensi, akan di-insert saat instalasi

-- ================================================================
-- STORED PROCEDURE: Generate Document Number
-- ================================================================
DELIMITER //

CREATE PROCEDURE IF NOT EXISTS `sp_generate_doc_number`(
    IN p_company_id INT UNSIGNED,
    IN p_doc_type VARCHAR(20),
    OUT p_doc_number VARCHAR(100)
)
BEGIN
    DECLARE v_year SMALLINT;
    DECLARE v_month TINYINT;
    DECLARE v_last_number INT;
    DECLARE v_prefix VARCHAR(20);
    DECLARE v_month_roman VARCHAR(5);
    DECLARE v_format VARCHAR(100);
    
    SET v_year = YEAR(CURDATE());
    SET v_month = MONTH(CURDATE());
    
    -- Get format from settings or use default
    SELECT setting_value INTO v_format 
    FROM company_settings 
    WHERE company_id = p_company_id 
    AND setting_key = CONCAT('doc_number_format_', p_doc_type)
    LIMIT 1;
    
    IF v_format IS NULL THEN
        SET v_format = '{NUM}/{PREFIX}/{MONTH}/{YEAR}';
    END IF;
    
    -- Get prefix based on doc type
    CASE p_doc_type
        WHEN 'quotation' THEN SET v_prefix = 'PNW';
        WHEN 'agreement' THEN SET v_prefix = 'SPK';
        WHEN 'invoice' THEN SET v_prefix = 'INV';
        WHEN 'receipt' THEN SET v_prefix = 'KWT';
        WHEN 'order' THEN SET v_prefix = 'ORD';
        ELSE SET v_prefix = 'DOC';
    END CASE;
    
    -- Get or create counter
    INSERT INTO document_numbers (company_id, doc_type, year, month, last_number, prefix)
    VALUES (p_company_id, p_doc_type, v_year, v_month, 1, v_prefix)
    ON DUPLICATE KEY UPDATE last_number = last_number + 1;
    
    SELECT last_number INTO v_last_number
    FROM document_numbers
    WHERE company_id = p_company_id
    AND doc_type = p_doc_type
    AND year = v_year
    AND month = v_month;
    
    -- Convert month to roman
    CASE v_month
        WHEN 1 THEN SET v_month_roman = 'I';
        WHEN 2 THEN SET v_month_roman = 'II';
        WHEN 3 THEN SET v_month_roman = 'III';
        WHEN 4 THEN SET v_month_roman = 'IV';
        WHEN 5 THEN SET v_month_roman = 'V';
        WHEN 6 THEN SET v_month_roman = 'VI';
        WHEN 7 THEN SET v_month_roman = 'VII';
        WHEN 8 THEN SET v_month_roman = 'VIII';
        WHEN 9 THEN SET v_month_roman = 'IX';
        WHEN 10 THEN SET v_month_roman = 'X';
        WHEN 11 THEN SET v_month_roman = 'XI';
        WHEN 12 THEN SET v_month_roman = 'XII';
    END CASE;
    
    -- Build number from format
    SET p_doc_number = v_format;
    SET p_doc_number = REPLACE(p_doc_number, '{NUM}', LPAD(v_last_number, 4, '0'));
    SET p_doc_number = REPLACE(p_doc_number, '{PREFIX}', v_prefix);
    SET p_doc_number = REPLACE(p_doc_number, '{MONTH}', v_month_roman);
    SET p_doc_number = REPLACE(p_doc_number, '{YEAR}', v_year);
    SET p_doc_number = REPLACE(p_doc_number, '{MM}', LPAD(v_month, 2, '0'));
    SET p_doc_number = REPLACE(p_doc_number, '{YY}', RIGHT(v_year, 2));
    
END //

DELIMITER ;

-- ================================================================
-- FUNCTION: Get Month Name in Indonesian
-- ================================================================
DELIMITER //

CREATE FUNCTION IF NOT EXISTS `fn_month_indonesia`(p_month TINYINT) 
RETURNS VARCHAR(20)
DETERMINISTIC
BEGIN
    DECLARE v_name VARCHAR(20);
    CASE p_month
        WHEN 1 THEN SET v_name = 'Januari';
        WHEN 2 THEN SET v_name = 'Februari';
        WHEN 3 THEN SET v_name = 'Maret';
        WHEN 4 THEN SET v_name = 'April';
        WHEN 5 THEN SET v_name = 'Mei';
        WHEN 6 THEN SET v_name = 'Juni';
        WHEN 7 THEN SET v_name = 'Juli';
        WHEN 8 THEN SET v_name = 'Agustus';
        WHEN 9 THEN SET v_name = 'September';
        WHEN 10 THEN SET v_name = 'Oktober';
        WHEN 11 THEN SET v_name = 'November';
        WHEN 12 THEN SET v_name = 'Desember';
        ELSE SET v_name = '';
    END CASE;
    RETURN v_name;
END //

DELIMITER ;

-- ================================================================
-- FUNCTION: Number to Indonesian Words (Terbilang)
-- ================================================================
DELIMITER //

CREATE FUNCTION IF NOT EXISTS `fn_terbilang`(p_number DECIMAL(15,2)) 
RETURNS VARCHAR(500)
DETERMINISTIC
BEGIN
    DECLARE v_result VARCHAR(500);
    DECLARE v_satuan VARCHAR(100);
    DECLARE v_bilangan BIGINT;
    DECLARE v_sisa BIGINT;
    DECLARE v_temp VARCHAR(100);
    
    SET v_bilangan = FLOOR(p_number);
    SET v_result = '';
    SET v_satuan = ' Satu Dua Tiga Empat Lima Enam Tujuh Delapan Sembilan Sepuluh Sebelas';
    
    IF v_bilangan < 12 THEN
        SET v_result = TRIM(SUBSTRING_INDEX(SUBSTRING_INDEX(v_satuan, ' ', v_bilangan + 1), ' ', -1));
    ELSEIF v_bilangan < 20 THEN
        SET v_result = CONCAT(fn_terbilang(v_bilangan - 10), ' Belas');
    ELSEIF v_bilangan < 100 THEN
        SET v_result = CONCAT(fn_terbilang(FLOOR(v_bilangan / 10)), ' Puluh ', fn_terbilang(v_bilangan MOD 10));
    ELSEIF v_bilangan < 200 THEN
        SET v_result = CONCAT('Seratus ', fn_terbilang(v_bilangan - 100));
    ELSEIF v_bilangan < 1000 THEN
        SET v_result = CONCAT(fn_terbilang(FLOOR(v_bilangan / 100)), ' Ratus ', fn_terbilang(v_bilangan MOD 100));
    ELSEIF v_bilangan < 2000 THEN
        SET v_result = CONCAT('Seribu ', fn_terbilang(v_bilangan - 1000));
    ELSEIF v_bilangan < 1000000 THEN
        SET v_result = CONCAT(fn_terbilang(FLOOR(v_bilangan / 1000)), ' Ribu ', fn_terbilang(v_bilangan MOD 1000));
    ELSEIF v_bilangan < 1000000000 THEN
        SET v_result = CONCAT(fn_terbilang(FLOOR(v_bilangan / 1000000)), ' Juta ', fn_terbilang(v_bilangan MOD 1000000));
    ELSEIF v_bilangan < 1000000000000 THEN
        SET v_result = CONCAT(fn_terbilang(FLOOR(v_bilangan / 1000000000)), ' Milyar ', fn_terbilang(v_bilangan MOD 1000000000));
    ELSEIF v_bilangan < 1000000000000000 THEN
        SET v_result = CONCAT(fn_terbilang(FLOOR(v_bilangan / 1000000000000)), ' Trilyun ', fn_terbilang(v_bilangan MOD 1000000000000));
    END IF;
    
    RETURN TRIM(v_result);
END //

DELIMITER ;

-- ================================================================
-- DEFAULT CHART OF ACCOUNTS TEMPLATE
-- Akan di-insert per company saat company dibuat
-- ================================================================

-- Create temporary table for default accounts
CREATE TABLE IF NOT EXISTS `default_accounts` (
    `code` VARCHAR(20) NOT NULL,
    `name` VARCHAR(100) NOT NULL,
    `type` VARCHAR(20) NOT NULL,
    `parent_code` VARCHAR(20) NULL,
    `normal_balance` VARCHAR(10) NOT NULL,
    `is_cash_bank` TINYINT(1) NOT NULL DEFAULT 0,
    `sort_order` INT NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `default_accounts` (`code`, `name`, `type`, `parent_code`, `normal_balance`, `is_cash_bank`, `sort_order`) VALUES
-- ASET
('1', 'ASET', 'asset', NULL, 'debit', 0, 100),
('1.1', 'Aset Lancar', 'asset', '1', 'debit', 0, 110),
('1.1.01', 'Kas', 'asset', '1.1', 'debit', 1, 111),
('1.1.02', 'Bank', 'asset', '1.1', 'debit', 1, 112),
('1.1.03', 'Piutang Usaha', 'asset', '1.1', 'debit', 0, 113),
('1.1.04', 'Piutang Lain-lain', 'asset', '1.1', 'debit', 0, 114),
('1.1.05', 'Uang Muka', 'asset', '1.1', 'debit', 0, 115),
('1.2', 'Aset Tetap', 'asset', '1', 'debit', 0, 120),
('1.2.01', 'Peralatan Kantor', 'asset', '1.2', 'debit', 0, 121),
('1.2.02', 'Akumulasi Peny. Peralatan', 'asset', '1.2', 'credit', 0, 122),
('1.2.03', 'Kendaraan', 'asset', '1.2', 'debit', 0, 123),
('1.2.04', 'Akumulasi Peny. Kendaraan', 'asset', '1.2', 'credit', 0, 124),

-- LIABILITAS
('2', 'LIABILITAS', 'liability', NULL, 'credit', 0, 200),
('2.1', 'Liabilitas Jangka Pendek', 'liability', '2', 'credit', 0, 210),
('2.1.01', 'Hutang Usaha', 'liability', '2.1', 'credit', 0, 211),
('2.1.02', 'Hutang Pajak', 'liability', '2.1', 'credit', 0, 212),
('2.1.03', 'Hutang Lain-lain', 'liability', '2.1', 'credit', 0, 213),
('2.1.04', 'Pendapatan Diterima Dimuka', 'liability', '2.1', 'credit', 0, 214),
('2.2', 'Liabilitas Jangka Panjang', 'liability', '2', 'credit', 0, 220),
('2.2.01', 'Hutang Bank', 'liability', '2.2', 'credit', 0, 221),

-- EKUITAS
('3', 'EKUITAS', 'equity', NULL, 'credit', 0, 300),
('3.1', 'Modal', 'equity', '3', 'credit', 0, 310),
('3.1.01', 'Modal Disetor', 'equity', '3.1', 'credit', 0, 311),
('3.1.02', 'Laba Ditahan', 'equity', '3.1', 'credit', 0, 312),
('3.1.03', 'Laba Tahun Berjalan', 'equity', '3.1', 'credit', 0, 313),
('3.1.04', 'Prive', 'equity', '3.1', 'debit', 0, 314),

-- PENDAPATAN
('4', 'PENDAPATAN', 'revenue', NULL, 'credit', 0, 400),
('4.1', 'Pendapatan Usaha', 'revenue', '4', 'credit', 0, 410),
('4.1.01', 'Pendapatan Jasa Bus', 'revenue', '4.1', 'credit', 0, 411),
('4.1.02', 'Pendapatan Jasa Towing', 'revenue', '4.1', 'credit', 0, 412),
('4.1.03', 'Pendapatan Jasa Hotel', 'revenue', '4.1', 'credit', 0, 413),
('4.1.04', 'Pendapatan Jasa Travel', 'revenue', '4.1', 'credit', 0, 414),
('4.1.05', 'Pendapatan Sewa Kendaraan', 'revenue', '4.1', 'credit', 0, 415),
('4.2', 'Pendapatan Lain-lain', 'revenue', '4', 'credit', 0, 420),
('4.2.01', 'Pendapatan Bunga', 'revenue', '4.2', 'credit', 0, 421),
('4.2.02', 'Pendapatan Lainnya', 'revenue', '4.2', 'credit', 0, 422),

-- BEBAN
('5', 'BEBAN', 'expense', NULL, 'debit', 0, 500),
('5.1', 'Beban Operasional', 'expense', '5', 'debit', 0, 510),
('5.1.01', 'Beban Gaji', 'expense', '5.1', 'debit', 0, 511),
('5.1.02', 'Beban Transportasi', 'expense', '5.1', 'debit', 0, 512),
('5.1.03', 'Beban Makan Minum', 'expense', '5.1', 'debit', 0, 513),
('5.1.04', 'Beban Telepon & Internet', 'expense', '5.1', 'debit', 0, 514),
('5.1.05', 'Beban Listrik & Air', 'expense', '5.1', 'debit', 0, 515),
('5.1.06', 'Beban Sewa', 'expense', '5.1', 'debit', 0, 516),
('5.1.07', 'Beban Perlengkapan Kantor', 'expense', '5.1', 'debit', 0, 517),
('5.1.08', 'Beban Penyusutan', 'expense', '5.1', 'debit', 0, 518),
('5.1.09', 'Beban Administrasi', 'expense', '5.1', 'debit', 0, 519),
('5.2', 'Beban Lain-lain', 'expense', '5', 'debit', 0, 520),
('5.2.01', 'Beban Bunga', 'expense', '5.2', 'debit', 0, 521),
('5.2.02', 'Beban Pajak', 'expense', '5.2', 'debit', 0, 522),
('5.2.03', 'Beban Lainnya', 'expense', '5.2', 'debit', 0, 523),

-- HPP (Harga Pokok Penjualan) / Cost of Sales
('6', 'HARGA POKOK', 'expense', NULL, 'debit', 0, 600),
('6.1', 'Harga Pokok Jasa', 'expense', '6', 'debit', 0, 610),
('6.1.01', 'HPP Jasa Bus', 'expense', '6.1', 'debit', 0, 611),
('6.1.02', 'HPP Jasa Towing', 'expense', '6.1', 'debit', 0, 612),
('6.1.03', 'HPP Jasa Hotel', 'expense', '6.1', 'debit', 0, 613),
('6.1.04', 'HPP Jasa Travel', 'expense', '6.1', 'debit', 0, 614),
('6.1.05', 'HPP Sewa Kendaraan', 'expense', '6.1', 'debit', 0, 615);

-- ================================================================
-- END OF SCHEMA
-- ================================================================
