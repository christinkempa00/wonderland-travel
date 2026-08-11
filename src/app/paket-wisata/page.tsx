import type { Metadata } from "next";
import { DestinationsGrid } from "@/components/paket-wisata/destinations-grid";
import { PackageCatalog } from "@/components/paket-wisata/package-catalog";

export const metadata: Metadata = {
  title: "Paket Wisata — Wonderland Travel",
  description: "Jelajahi destinasi dan paket wisata Wonderland Travel di seluruh Indonesia.",
};

export default function PaketWisataPage() {
  return (
    <>
      <DestinationsGrid />
      <PackageCatalog />
    </>
  );
}
