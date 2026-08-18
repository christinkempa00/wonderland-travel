-- Item 1: Divisi PELNI (JM / OFFICE / TIKOM) + penomoran invoice khusus.
-- Item 4: Qty peserta + nama peserta per item pesanan.
--
-- WAJIB dijalankan manual lewat phpMyAdmin sebelum fitur-fitur ini bisa dipakai.

-- Klien PELNI (dan klien serupa di masa depan) ditandai lewat flag ini,
-- bukan dicocokkan dari nama klien, supaya tidak rapuh kalau nama berubah.
ALTER TABLE `clients`
    ADD COLUMN `uses_divisi` TINYINT(1) NOT NULL DEFAULT 0
    COMMENT 'Klien PELNI-style: form pesanan menampilkan dropdown Divisi + penomoran invoice khusus'
    AFTER `npwp`;

ALTER TABLE `orders`
    ADD COLUMN `divisi` VARCHAR(10) NULL
    COMMENT 'JM / OFFICE / TIKOM, hanya diisi untuk klien dengan uses_divisi = 1'
    AFTER `pic_phone`,
    ADD COLUMN `pelni_invoice_number` VARCHAR(50) NULL
    COMMENT 'Nomor invoice persisten format INV-PLNI{TAHUN ROMAWI}{INFIX}-{00001}, dibuat sekali saat order dibuat'
    AFTER `divisi`;

ALTER TABLE `order_items`
    ADD COLUMN `participant_qty` INT UNSIGNED NULL
    COMMENT 'Jumlah peserta yang hadir (terpisah dari quantity yang dipakai untuk hitung harga)'
    AFTER `quantity`,
    ADD COLUMN `participant_names` TEXT NULL
    COMMENT 'Nama-nama peserta terkait item ini, bebas format (satu nama per baris)'
    AFTER `participant_qty`;
