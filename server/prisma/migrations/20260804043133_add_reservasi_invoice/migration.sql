-- CreateTable
CREATE TABLE `pelanggan` (
    `id` VARCHAR(191) NOT NULL,
    `kodeKlien` VARCHAR(191) NOT NULL,
    `nama` VARCHAR(191) NOT NULL,
    `noHp` VARCHAR(191) NOT NULL,
    `email` VARCHAR(191) NULL,
    `alamat` TEXT NULL,
    `createdAt` DATETIME(3) NOT NULL DEFAULT CURRENT_TIMESTAMP(3),
    `updatedAt` DATETIME(3) NOT NULL,

    UNIQUE INDEX `pelanggan_kodeKlien_key`(`kodeKlien`),
    PRIMARY KEY (`id`)
) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- CreateTable
CREATE TABLE `reservasi` (
    `id` VARCHAR(191) NOT NULL,
    `nomorBooking` VARCHAR(191) NOT NULL,
    `pelangganId` VARCHAR(191) NOT NULL,
    `jenis` ENUM('PAKET_WISATA', 'HOTEL', 'PESAWAT', 'RENTAL', 'GABUNGAN') NOT NULL,
    `itemRingkasan` JSON NOT NULL,
    `tanggalMulai` DATETIME(3) NOT NULL,
    `tanggalSelesai` DATETIME(3) NULL,
    `status` ENUM('BARU_MASUK', 'DIKONFIRMASI', 'MENUNGGU_PEMBAYARAN', 'DIBAYAR_SEBAGIAN', 'LUNAS', 'SELESAI', 'DIBATALKAN') NOT NULL DEFAULT 'BARU_MASUK',
    `sumber` ENUM('WEBSITE_EXPLORE', 'WEBSITE_KONTAK', 'MANUAL_STAF', 'WHATSAPP') NOT NULL DEFAULT 'MANUAL_STAF',
    `catatan` TEXT NULL,
    `createdAt` DATETIME(3) NOT NULL DEFAULT CURRENT_TIMESTAMP(3),
    `updatedAt` DATETIME(3) NOT NULL,

    UNIQUE INDEX `reservasi_nomorBooking_key`(`nomorBooking`),
    PRIMARY KEY (`id`)
) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- CreateTable
CREATE TABLE `invoice` (
    `id` VARCHAR(191) NOT NULL,
    `nomorInvoice` VARCHAR(191) NOT NULL,
    `reservasiId` VARCHAR(191) NOT NULL,
    `diskon` INTEGER NOT NULL DEFAULT 0,
    `jatuhTempo` DATETIME(3) NOT NULL,
    `statusBayar` ENUM('BELUM_BAYAR', 'SEBAGIAN', 'LUNAS') NOT NULL DEFAULT 'BELUM_BAYAR',
    `createdAt` DATETIME(3) NOT NULL DEFAULT CURRENT_TIMESTAMP(3),
    `updatedAt` DATETIME(3) NOT NULL,

    UNIQUE INDEX `invoice_nomorInvoice_key`(`nomorInvoice`),
    UNIQUE INDEX `invoice_reservasiId_key`(`reservasiId`),
    PRIMARY KEY (`id`)
) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- CreateTable
CREATE TABLE `invoice_items` (
    `id` VARCHAR(191) NOT NULL,
    `invoiceId` VARCHAR(191) NOT NULL,
    `nama` VARCHAR(191) NOT NULL,
    `qty` INTEGER NOT NULL DEFAULT 1,
    `hargaSatuan` INTEGER NOT NULL,
    `order` INTEGER NOT NULL DEFAULT 0,

    PRIMARY KEY (`id`)
) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- CreateTable
CREATE TABLE `pembayaran` (
    `id` VARCHAR(191) NOT NULL,
    `invoiceId` VARCHAR(191) NOT NULL,
    `jumlah` INTEGER NOT NULL,
    `metode` ENUM('TRANSFER_BANK', 'E_WALLET', 'KARTU_KREDIT', 'TUNAI', 'LAINNYA') NOT NULL,
    `buktiUrl` VARCHAR(191) NULL,
    `tanggal` DATETIME(3) NOT NULL,
    `catatan` VARCHAR(191) NULL,
    `createdAt` DATETIME(3) NOT NULL DEFAULT CURRENT_TIMESTAMP(3),

    PRIMARY KEY (`id`)
) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- CreateTable
CREATE TABLE `notifikasi` (
    `id` VARCHAR(191) NOT NULL,
    `pesan` VARCHAR(191) NOT NULL,
    `reservasiId` VARCHAR(191) NULL,
    `dibaca` BOOLEAN NOT NULL DEFAULT false,
    `createdAt` DATETIME(3) NOT NULL DEFAULT CURRENT_TIMESTAMP(3),

    PRIMARY KEY (`id`)
) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- AddForeignKey
ALTER TABLE `reservasi` ADD CONSTRAINT `reservasi_pelangganId_fkey` FOREIGN KEY (`pelangganId`) REFERENCES `pelanggan`(`id`) ON DELETE RESTRICT ON UPDATE CASCADE;

-- AddForeignKey
ALTER TABLE `invoice` ADD CONSTRAINT `invoice_reservasiId_fkey` FOREIGN KEY (`reservasiId`) REFERENCES `reservasi`(`id`) ON DELETE RESTRICT ON UPDATE CASCADE;

-- AddForeignKey
ALTER TABLE `invoice_items` ADD CONSTRAINT `invoice_items_invoiceId_fkey` FOREIGN KEY (`invoiceId`) REFERENCES `invoice`(`id`) ON DELETE CASCADE ON UPDATE CASCADE;

-- AddForeignKey
ALTER TABLE `pembayaran` ADD CONSTRAINT `pembayaran_invoiceId_fkey` FOREIGN KEY (`invoiceId`) REFERENCES `invoice`(`id`) ON DELETE CASCADE ON UPDATE CASCADE;
