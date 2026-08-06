import { useRef, useState } from "react";
import type { DragEvent } from "react";

export interface UploaderImage {
  id: string;
  url: string;
}

interface ImageUploaderProps {
  images: UploaderImage[];
  onUpload: (files: FileList) => Promise<void> | void;
  onDelete: (id: string) => Promise<void> | void;
  onReorder: (orderedIds: string[]) => Promise<void> | void;
  uploading?: boolean;
}

export function ImageUploader({
  images,
  onUpload,
  onDelete,
  onReorder,
  uploading = false,
}: ImageUploaderProps) {
  const fileInputRef = useRef<HTMLInputElement>(null);
  const [dragIndex, setDragIndex] = useState<number | null>(null);

  function handleFilesSelected(files: FileList | null) {
    if (files && files.length > 0) {
      onUpload(files);
    }
    if (fileInputRef.current) fileInputRef.current.value = "";
  }

  function handleDragStart(index: number) {
    setDragIndex(index);
  }

  function handleDragOver(event: DragEvent<HTMLDivElement>) {
    event.preventDefault();
  }

  function handleDrop(index: number) {
    if (dragIndex === null || dragIndex === index) {
      setDragIndex(null);
      return;
    }
    const reordered = [...images];
    const [moved] = reordered.splice(dragIndex, 1);
    reordered.splice(index, 0, moved);
    setDragIndex(null);
    onReorder(reordered.map((img) => img.id));
  }

  return (
    <div>
      <div className="grid grid-cols-2 gap-3 sm:grid-cols-3 md:grid-cols-4">
        {images.map((image, index) => (
          <div
            key={image.id}
            draggable
            onDragStart={() => handleDragStart(index)}
            onDragOver={handleDragOver}
            onDrop={() => handleDrop(index)}
            className="group relative aspect-square cursor-grab overflow-hidden rounded-lg border border-border bg-mist active:cursor-grabbing"
          >
            <img src={image.url} alt="" className="h-full w-full object-cover" />
            <button
              type="button"
              onClick={() => onDelete(image.id)}
              className="absolute right-1.5 top-1.5 flex size-6 items-center justify-center rounded-full bg-black/70 text-xs text-white opacity-0 transition-opacity group-hover:opacity-100"
              aria-label="Hapus gambar"
            >
              ×
            </button>
            {index === 0 && (
              <span className="absolute bottom-1.5 left-1.5 rounded bg-black/70 px-1.5 py-0.5 text-[10px] font-semibold text-white">
                Cover
              </span>
            )}
          </div>
        ))}

        <button
          type="button"
          onClick={() => fileInputRef.current?.click()}
          disabled={uploading}
          className="flex aspect-square flex-col items-center justify-center gap-1 rounded-lg border border-dashed border-border text-xs font-medium text-muted transition-colors hover:border-black hover:text-heading disabled:cursor-not-allowed disabled:opacity-50"
        >
          <span className="text-xl leading-none">+</span>
          {uploading ? "Mengunggah..." : "Tambah Foto"}
        </button>
      </div>

      <input
        ref={fileInputRef}
        type="file"
        accept="image/*"
        multiple
        className="hidden"
        onChange={(e) => handleFilesSelected(e.target.files)}
      />

      <p className="mt-2 text-xs text-muted">
        Seret untuk mengurutkan ulang. Foto pertama jadi foto sampul. Maks. 5MB per file.
      </p>
    </div>
  );
}
