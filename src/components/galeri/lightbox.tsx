"use client";

import { useEffect, useCallback } from "react";
import { createPortal } from "react-dom";
import { ChevronLeft, ChevronRight, X } from "lucide-react";
import type { Photo } from "./data";

export interface LightboxProps {
  photos: Photo[];
  index: number | null;
  onClose: () => void;
  onNavigate: (index: number) => void;
}

export function Lightbox({ photos, index, onClose, onNavigate }: LightboxProps) {
  const goPrev = useCallback(() => {
    if (index === null) return;
    onNavigate((index - 1 + photos.length) % photos.length);
  }, [index, photos.length, onNavigate]);

  const goNext = useCallback(() => {
    if (index === null) return;
    onNavigate((index + 1) % photos.length);
  }, [index, photos.length, onNavigate]);

  useEffect(() => {
    if (index === null) return;

    const handleKeyDown = (event: KeyboardEvent) => {
      if (event.key === "Escape") onClose();
      if (event.key === "ArrowLeft") goPrev();
      if (event.key === "ArrowRight") goNext();
    };
    document.addEventListener("keydown", handleKeyDown);
    document.body.style.overflow = "hidden";

    return () => {
      document.removeEventListener("keydown", handleKeyDown);
      document.body.style.overflow = "";
    };
  }, [index, onClose, goPrev, goNext]);

  if (index === null) return null;

  const photo = photos[index];

  return createPortal(
    <div
      role="dialog"
      aria-modal="true"
      aria-label={photo.caption}
      className="fixed inset-0 z-50 flex flex-col bg-black/95"
    >
      <button
        type="button"
        onClick={onClose}
        aria-label="Tutup"
        className="absolute right-4 top-4 z-10 flex size-11 items-center justify-center rounded-full text-white/80 transition-colors hover:bg-white/10 hover:text-white sm:right-6 sm:top-6"
      >
        <X className="size-6" />
      </button>

      <div className="relative flex flex-1 items-center justify-center px-4 py-16 sm:px-20">
        <button
          type="button"
          onClick={goPrev}
          aria-label="Foto sebelumnya"
          className="absolute left-2 z-10 flex size-10 items-center justify-center rounded-full bg-white/10 text-white transition-colors hover:bg-white/20 sm:left-6 sm:size-12"
        >
          <ChevronLeft className="size-5 sm:size-6" />
        </button>

        <img
          key={photo.id}
          src={`https://picsum.photos/seed/${photo.seed}/1200/900`}
          alt={photo.caption}
          className="max-h-[75vh] max-w-full rounded-card object-contain"
        />

        <button
          type="button"
          onClick={goNext}
          aria-label="Foto berikutnya"
          className="absolute right-2 z-10 flex size-10 items-center justify-center rounded-full bg-white/10 text-white transition-colors hover:bg-white/20 sm:right-6 sm:size-12"
        >
          <ChevronRight className="size-5 sm:size-6" />
        </button>
      </div>

      <div className="flex items-center justify-between gap-4 px-6 py-4 text-sm text-white/70">
        <span className="truncate">{photo.caption}</span>
        <span className="shrink-0">
          {index + 1} / {photos.length}
        </span>
      </div>
    </div>,
    document.body,
  );
}
