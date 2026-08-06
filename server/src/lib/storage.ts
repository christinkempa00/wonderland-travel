import crypto from "node:crypto";
import fs from "node:fs/promises";
import path from "node:path";
import { v2 as cloudinary } from "cloudinary";
import { env } from "./env";

export interface UploadResult {
  url: string;
  publicId: string;
}

const useCloudinary = Boolean(
  env.CLOUDINARY_CLOUD_NAME && env.CLOUDINARY_API_KEY && env.CLOUDINARY_API_SECRET,
);

if (useCloudinary) {
  cloudinary.config({
    cloud_name: env.CLOUDINARY_CLOUD_NAME,
    api_key: env.CLOUDINARY_API_KEY,
    api_secret: env.CLOUDINARY_API_SECRET,
  });
} else {
  console.warn(
    "[storage] CLOUDINARY_* belum diisi di .env — upload gambar memakai penyimpanan lokal " +
      "(server/uploads). Cukup untuk development; isi 3 variabel CLOUDINARY_* sebelum production.",
  );
}

export const storageProvider = useCloudinary ? "cloudinary" : "local";

const UPLOAD_DIR = path.resolve(__dirname, "../../uploads");

async function ensureUploadDir() {
  await fs.mkdir(UPLOAD_DIR, { recursive: true });
}

/** Upload satu gambar. `folder` mengelompokkan aset per modul, mis. "paket-wisata". */
export async function uploadImage(
  buffer: Buffer,
  originalName: string,
  folder: string,
): Promise<UploadResult> {
  if (useCloudinary) {
    return new Promise((resolve, reject) => {
      const stream = cloudinary.uploader.upload_stream(
        { folder: `wonderland/${folder}` },
        (error, result) => {
          if (error || !result) {
            reject(error ?? new Error("Upload ke Cloudinary gagal."));
            return;
          }
          resolve({ url: result.secure_url, publicId: result.public_id });
        },
      );
      stream.end(buffer);
    });
  }

  await ensureUploadDir();
  const ext = path.extname(originalName).toLowerCase() || ".jpg";
  const filename = `${folder}-${crypto.randomUUID()}${ext}`;
  await fs.writeFile(path.join(UPLOAD_DIR, filename), buffer);
  return { url: `/uploads/${filename}`, publicId: filename };
}

export async function deleteImage(publicId: string): Promise<void> {
  if (useCloudinary) {
    await cloudinary.uploader.destroy(publicId);
    return;
  }
  await fs.unlink(path.join(UPLOAD_DIR, publicId)).catch(() => {});
}

export { UPLOAD_DIR };
