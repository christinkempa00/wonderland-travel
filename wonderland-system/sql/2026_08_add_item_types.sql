-- order_items.item_type is a MySQL ENUM with a fixed list of allowed values.
-- Menambahkan jenis layanan baru (Kereta Api, Kontrakan, Kapal Laut, Kapal
-- (Kamar di Kapal)) di config/constants.php (ITEM_TYPES) saja TIDAK CUKUP —
-- MySQL diam-diam menyimpan string kosong untuk nilai ENUM yang tidak
-- terdaftar di kolom ini (tidak error, jadi bug-nya tidak kelihatan sampai
-- dicek langsung ke database). Kolom ini juga HARUS diperluas.
--
-- WAJIB dijalankan manual lewat phpMyAdmin. Ini perubahan aditif — hanya
-- menambah nilai ENUM yang diizinkan, baris/order_items yang sudah ada
-- (dengan jenis layanan lama) tidak berubah sama sekali.

ALTER TABLE `order_items`
    MODIFY COLUMN `item_type` ENUM(
        'bus','towing','hotel','travel','rental','flight','restaurant',
        'train','kontrakan','ship','ship_room','other'
    ) NOT NULL;
