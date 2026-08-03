import type { Metadata } from "next";
import { GalleryGrid } from "@/components/galeri/gallery-grid";

export const metadata: Metadata = {
  title: "Galeri — Wonderland Travel",
  description: "Momen-momen perjalanan dari para petualang Wonderland Travel.",
};

export default function GaleriPage() {
  return <GalleryGrid />;
}
