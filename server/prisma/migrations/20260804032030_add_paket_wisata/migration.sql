-- CreateTable
CREATE TABLE `paket_wisata` (
    `id` VARCHAR(191) NOT NULL,
    `name` VARCHAR(191) NOT NULL,
    `slug` VARCHAR(191) NOT NULL,
    `location` VARCHAR(191) NOT NULL,
    `duration` VARCHAR(191) NOT NULL,
    `price` INTEGER NOT NULL,
    `rating` DOUBLE NOT NULL DEFAULT 5,
    `tag` VARCHAR(191) NULL,
    `description` TEXT NOT NULL,
    `highlights` JSON NOT NULL,
    `active` BOOLEAN NOT NULL DEFAULT true,
    `order` INTEGER NOT NULL DEFAULT 0,
    `createdAt` DATETIME(3) NOT NULL DEFAULT CURRENT_TIMESTAMP(3),
    `updatedAt` DATETIME(3) NOT NULL,

    UNIQUE INDEX `paket_wisata_slug_key`(`slug`),
    PRIMARY KEY (`id`)
) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- CreateTable
CREATE TABLE `paket_wisata_images` (
    `id` VARCHAR(191) NOT NULL,
    `paketWisataId` VARCHAR(191) NOT NULL,
    `url` VARCHAR(191) NOT NULL,
    `publicId` VARCHAR(191) NOT NULL,
    `order` INTEGER NOT NULL DEFAULT 0,
    `createdAt` DATETIME(3) NOT NULL DEFAULT CURRENT_TIMESTAMP(3),

    PRIMARY KEY (`id`)
) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- AddForeignKey
ALTER TABLE `paket_wisata_images` ADD CONSTRAINT `paket_wisata_images_paketWisataId_fkey` FOREIGN KEY (`paketWisataId`) REFERENCES `paket_wisata`(`id`) ON DELETE CASCADE ON UPDATE CASCADE;
