-- Field PIC Name & Phone Number pada form Buat Pesanan (diisi manual per
-- pesanan, bisa beda dari PIC klien di data master klien).
--
-- WAJIB dijalankan manual lewat phpMyAdmin sebelum field ini bisa dipakai.

ALTER TABLE `orders`
    ADD COLUMN `pic_name` VARCHAR(100) NULL
    COMMENT 'PIC pesanan ini, diisi manual (bisa beda dari PIC klien)'
    AFTER `event_location`,
    ADD COLUMN `pic_phone` VARCHAR(50) NULL AFTER `pic_name`;
