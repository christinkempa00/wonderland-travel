"use client";

import { useMemo, useState } from "react";
import { Maximize2 } from "lucide-react";
import { Accent, SectionHeader } from "@/components/ui";
import { cn } from "@/lib/cn";
import { DESTINATIONS, PHOTOS, type PhotoSize } from "./data";
import { Lightbox } from "./lightbox";

const SIZE_CLASSES: Record<PhotoSize, string> = {
  normal: "col-span-1 row-span-1",
  wide: "col-span-2 row-span-1",
  tall: "col-span-1 row-span-2",
  large: "col-span-2 row-span-2",
};

const FILTERS = ["Semua", ...DESTINATIONS];

export function GalleryGrid() {
  const [activeDestination, setActiveDestination] = useState<string>("Semua");
  const [lightboxIndex, setLightboxIndex] = useState<number | null>(null);

  const filteredPhotos = useMemo(
    () =>
      activeDestination === "Semua"
        ? PHOTOS
        : PHOTOS.filter((photo) => photo.destination === activeDestination),
    [activeDestination],
  );

  function handleFilterChange(destination: string) {
    setActiveDestination(destination);
    setLightboxIndex(null);
  }

  return (
    <section className="mx-auto flex w-full max-w-7xl flex-col gap-10 px-6 pb-16 pt-8 md:pb-20 md:pt-10">
      <SectionHeader
        align="left"
        badge="Galeri"
        title={
          <>
            Cerita Visual dari <Accent>Setiap Perjalanan</Accent>
          </>
        }
        description="Kumpulan momen dari para petualang yang sudah menjelajah bersama Wonderland Travel."
      />

      <div className="flex flex-wrap gap-2">
        {FILTERS.map((destination) => (
          <button
            key={destination}
            type="button"
            onClick={() => handleFilterChange(destination)}
            className={cn(
              "flex min-h-11 items-center rounded-full px-4 text-sm font-semibold transition-colors",
              activeDestination === destination
                ? "bg-black text-white"
                : "bg-mist text-body hover:bg-black/10",
            )}
          >
            {destination}
          </button>
        ))}
      </div>

      {filteredPhotos.length > 0 ? (
        <div className="grid grid-flow-dense auto-rows-[160px] grid-cols-2 gap-4 sm:auto-rows-[200px] sm:grid-cols-3 lg:auto-rows-[220px] lg:grid-cols-4">
          {filteredPhotos.map((photo, i) => (
            <button
              key={photo.id}
              type="button"
              onClick={() => setLightboxIndex(i)}
              className={cn(
                "group relative overflow-hidden rounded-card",
                SIZE_CLASSES[photo.size],
              )}
            >
              <img
                src={`https://picsum.photos/seed/${photo.seed}/800/800`}
                alt={photo.caption}
                className="h-full w-full object-cover transition-transform duration-300 ease-out group-hover:scale-105"
              />
              <div className="absolute inset-0 bg-black/0 transition-colors duration-300 group-hover:bg-black/30" />
              <span className="absolute right-3 top-3 flex size-8 items-center justify-center rounded-full bg-black/40 text-white opacity-0 transition-opacity duration-300 group-hover:opacity-100">
                <Maximize2 className="size-4" aria-hidden="true" />
              </span>
              <span className="absolute inset-x-0 bottom-0 translate-y-full bg-black/60 p-3 text-left text-xs text-white opacity-0 transition-all duration-300 group-hover:translate-y-0 group-hover:opacity-100">
                {photo.caption}
              </span>
            </button>
          ))}
        </div>
      ) : (
        <p className="py-12 text-center text-sm text-muted">Belum ada foto untuk destinasi ini.</p>
      )}

      <Lightbox
        photos={filteredPhotos}
        index={lightboxIndex}
        onClose={() => setLightboxIndex(null)}
        onNavigate={setLightboxIndex}
      />
    </section>
  );
}
