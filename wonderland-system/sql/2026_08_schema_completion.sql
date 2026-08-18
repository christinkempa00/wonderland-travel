-- ================================================================
-- SCHEMA COMPLETION — closes gaps between install.sql and what the
-- application code actually reads/writes (found via code audit,
-- 2026-08). Idempotent: safe to re-run.
-- ================================================================

-- orders: columns the code already reads/writes but install.sql lacks
ALTER TABLE `orders`
    ADD COLUMN IF NOT EXISTS `event_date` DATE NULL AFTER `event_name`,
    ADD COLUMN IF NOT EXISTS `event_end_date` DATE NULL AFTER `event_date`,
    ADD COLUMN IF NOT EXISTS `support_for` VARCHAR(150) NULL AFTER `event_end_date`,
    ADD COLUMN IF NOT EXISTS `total_markup` DECIMAL(15,2) NOT NULL DEFAULT 0 AFTER `total_base_price`,
    ADD COLUMN IF NOT EXISTS `paid_at` DATE NULL AFTER `paid_amount`,
    ADD COLUMN IF NOT EXISTS `service_type` VARCHAR(30) NULL AFTER `paid_at`,
    ADD COLUMN IF NOT EXISTS `expense_paid_amount` DECIMAL(15,2) NOT NULL DEFAULT 0 AFTER `service_type`;
ALTER TABLE `orders` ADD KEY IF NOT EXISTS `support_for` (`support_for`);

-- order_items: pricing formula needs num_days; plus attachment/hotel/expense columns
ALTER TABLE `order_items`
    MODIFY COLUMN `item_name` VARCHAR(255) NOT NULL DEFAULT '',
    MODIFY COLUMN `item_type` ENUM('bus','towing','hotel','travel','rental','flight','restaurant','other') NOT NULL,
    ADD COLUMN IF NOT EXISTS `num_days` INT NOT NULL DEFAULT 1 AFTER `quantity`,
    ADD COLUMN IF NOT EXISTS `markup_amount` DECIMAL(15,2) NOT NULL DEFAULT 0 AFTER `markup_value`,
    ADD COLUMN IF NOT EXISTS `attachment_logo` VARCHAR(255) NULL,
    ADD COLUMN IF NOT EXISTS `vehicle_plate` VARCHAR(20) NULL,
    ADD COLUMN IF NOT EXISTS `hotel_name` VARCHAR(255) NULL,
    ADD COLUMN IF NOT EXISTS `room_type` VARCHAR(100) NULL,
    ADD COLUMN IF NOT EXISTS `check_in_date` DATE NULL,
    ADD COLUMN IF NOT EXISTS `check_out_date` DATE NULL,
    ADD COLUMN IF NOT EXISTS `expense_status` ENUM('pending','paid') NOT NULL DEFAULT 'pending',
    ADD COLUMN IF NOT EXISTS `expense_bank_cash_id` INT UNSIGNED NULL,
    ADD COLUMN IF NOT EXISTS `expense_date` DATE NULL,
    ADD COLUMN IF NOT EXISTS `expense_journal_id` INT UNSIGNED NULL,
    ADD COLUMN IF NOT EXISTS `expense_notes` TEXT NULL;

-- clients: contact_person/npwp (code expects these; install.sql only has pic_name/pic_phone)
-- plus new client_code
ALTER TABLE `clients`
    ADD COLUMN IF NOT EXISTS `contact_person` VARCHAR(100) NULL AFTER `name`,
    ADD COLUMN IF NOT EXISTS `npwp` VARCHAR(50) NULL,
    ADD COLUMN IF NOT EXISTS `client_code` VARCHAR(30) NULL AFTER `id`;
UPDATE `clients` SET `contact_person` = `pic_name`
 WHERE `contact_person` IS NULL AND `pic_name` IS NOT NULL;
UPDATE `clients` c
JOIN (SELECT id, CONCAT('CLI-', LPAD(ROW_NUMBER() OVER (PARTITION BY company_id ORDER BY id), 4, '0')) AS gen_code
      FROM `clients`) x ON x.id = c.id
SET c.client_code = x.gen_code WHERE c.client_code IS NULL;
ALTER TABLE `clients` ADD UNIQUE KEY IF NOT EXISTS `company_client_code` (`company_id`, `client_code`);

-- order_payments: the REAL runtime payment table (not in install.sql at all today)
CREATE TABLE IF NOT EXISTS `order_payments` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `company_id` INT UNSIGNED NOT NULL,
    `order_id` INT UNSIGNED NOT NULL,
    `invoice_id` INT UNSIGNED NULL COMMENT 'set when payment recorded against an invoice instead of directly on an order',
    `journal_id` INT UNSIGNED NULL COMMENT 'no FK - accounting schema has separately drifted, out of scope',
    `amount` DECIMAL(15,2) NOT NULL,
    `payment_date` DATE NOT NULL,
    `payment_method` ENUM('transfer','ewallet','credit_card','cash','other') NOT NULL DEFAULT 'transfer',
    `bank_cash_id` INT UNSIGNED NULL,
    `reference` VARCHAR(100) NULL,
    `notes` TEXT NULL,
    `proof_image` VARCHAR(255) NULL COMMENT 'filename in uploads/payment-proofs/',
    `status` VARCHAR(20) NOT NULL DEFAULT 'posted',
    `created_by` INT UNSIGNED NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NULL ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`), KEY `order_id` (`order_id`), KEY `invoice_id` (`invoice_id`), KEY `company_id` (`company_id`),
    CONSTRAINT `fk_op_company` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_op_order` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_op_bankcash` FOREIGN KEY (`bank_cash_id`) REFERENCES `bank_cash` (`id`) ON DELETE SET NULL,
    CONSTRAINT `fk_op_user` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- invoices / invoice_items (new module)
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
    `payment_status` ENUM('unpaid','partial','paid') NOT NULL DEFAULT 'unpaid',
    `notes` TEXT NULL,
    `created_by` INT UNSIGNED NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NULL ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`), UNIQUE KEY `company_invoice_number` (`company_id`, `invoice_number`),
    KEY `order_id` (`order_id`), KEY `payment_status` (`payment_status`),
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
    PRIMARY KEY (`id`), KEY `invoice_id` (`invoice_id`),
    CONSTRAINT `fk_invitems_invoice` FOREIGN KEY (`invoice_id`) REFERENCES `invoices` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

ALTER TABLE `order_payments`
    ADD CONSTRAINT `fk_op_invoice` FOREIGN KEY (`invoice_id`) REFERENCES `invoices` (`id`) ON DELETE SET NULL;

-- client_communications (new module)
CREATE TABLE IF NOT EXISTS `client_communications` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `company_id` INT UNSIGNED NOT NULL,
    `client_id` INT UNSIGNED NOT NULL,
    `type` ENUM('phone','whatsapp','email','in_person','other') NOT NULL DEFAULT 'phone',
    `notes` TEXT NULL,
    `communication_date` DATE NOT NULL,
    `created_by` INT UNSIGNED NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`), KEY `client_id` (`client_id`), KEY `company_id` (`company_id`),
    CONSTRAINT `fk_comm_client` FOREIGN KEY (`client_id`) REFERENCES `clients` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_comm_company` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_comm_user` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Existing per-item attachment tables used by already-shipped pages
-- (hotel-guests.php and its flight/rental/vehicle-document siblings) —
-- confirmed missing from install.sql.
CREATE TABLE IF NOT EXISTS `hotel_guests` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT, `order_id` INT UNSIGNED NOT NULL, `order_item_id` INT UNSIGNED NULL,
    `hotel_name` VARCHAR(255) NULL, `guest_name` VARCHAR(150) NOT NULL, `room_number` VARCHAR(50) NULL,
    `room_type` VARCHAR(100) NULL, `check_in_date` DATE NULL, `check_out_date` DATE NULL, `notes` TEXT NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`), KEY `order_id` (`order_id`), KEY `order_item_id` (`order_item_id`),
    CONSTRAINT `fk_hg_order` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `flight_details` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT, `order_id` INT UNSIGNED NOT NULL, `order_item_id` INT UNSIGNED NULL,
    `flight_name` VARCHAR(255) NULL, `passenger_name` VARCHAR(150) NOT NULL, `description` TEXT NULL,
    `price` DECIMAL(15,2) NOT NULL DEFAULT 0, `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`), KEY `order_id` (`order_id`), KEY `order_item_id` (`order_item_id`),
    CONSTRAINT `fk_fd_order` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `rental_details` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT, `order_id` INT UNSIGNED NOT NULL, `order_item_id` INT UNSIGNED NULL,
    `vehicle_type` VARCHAR(100) NULL, `start_date` DATE NULL, `end_date` DATE NULL,
    `same_price` TINYINT(1) NOT NULL DEFAULT 1, `price_per_day` DECIMAL(15,2) NOT NULL DEFAULT 0,
    `daily_prices` JSON NULL, `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`), KEY `order_id` (`order_id`), KEY `order_item_id` (`order_item_id`),
    CONSTRAINT `fk_rd_order` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `vehicle_documents` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT, `order_id` INT UNSIGNED NOT NULL, `order_item_id` INT UNSIGNED NULL,
    `vehicle_type` VARCHAR(100) NULL, `plate_number` VARCHAR(20) NULL, `driver_name` VARCHAR(150) NULL,
    `photo_driver` VARCHAR(255) NULL, `photo_sim` VARCHAR(255) NULL, `photo_stnk` VARCHAR(255) NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`), KEY `order_id` (`order_id`), KEY `order_item_id` (`order_item_id`),
    CONSTRAINT `fk_vd_order` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
