"use client";

import { useState } from "react";
import { Check } from "lucide-react";
import {
  Accent,
  Badge,
  Button,
  Card,
  CardBody,
  CardMedia,
  Modal,
  SectionHeader,
  StarRating,
} from "@/components/ui";
import { ItineraryTimeline } from "./itinerary-timeline";

// TODO: ganti data riil klien — field `image` (seed picsum.photos) dan `price`
// di bawah masih placeholder, bukan foto/harga paket sungguhan.
const PACKAGES = [
  {
    id: "bali-4d3n",
    name: "Bali Highlights",
    location: "Bali",
    duration: "4D3N",
    price: "Rp 4.500.000",
    rating: 4.8,
    tag: "Populer",
    image: "wonderland-pkg-bali",
    description:
      "Jelajahi sisi terbaik Bali — dari sawah terasering Ubud hingga tebing dramatis Uluwatu — bersama pemandu lokal berpengalaman.",
    highlights: [
      "Hotel bintang 4, 3 malam",
      "Mobil + supir pribadi selama perjalanan",
      "Tiket masuk semua destinasi",
      "Sarapan setiap hari",
    ],
  },
  {
    id: "yogyakarta-3d2n",
    name: "Yogyakarta Heritage",
    location: "Yogyakarta",
    duration: "3D2N",
    price: "Rp 2.800.000",
    rating: 4.7,
    tag: "Budget Ramah",
    image: "wonderland-pkg-yogyakarta",
    description:
      "Susuri jejak sejarah Borobudur dan Prambanan, lalu nikmati kekayaan kuliner khas Yogyakarta bersama pemandu setempat.",
    highlights: [
      "Hotel bintang 3, 2 malam",
      "Tiket masuk Borobudur & Prambanan",
      "Wisata kuliner malam Malioboro",
      "Sarapan setiap hari",
    ],
  },
  {
    id: "labuan-bajo-5d4n",
    name: "Labuan Bajo Explorer",
    location: "Labuan Bajo",
    duration: "5D4N",
    price: "Rp 6.200.000",
    rating: 4.9,
    tag: "Terlaris",
    image: "wonderland-pkg-labuanbajo",
    description:
      "Berlayar menyusuri Taman Nasional Komodo, snorkeling di Pink Beach, dan menyaksikan matahari terbenam dari Bukit Cinta.",
    highlights: [
      "Kapal phinisi private trip 2 hari",
      "Hotel bintang 4, 3 malam",
      "Peralatan snorkeling lengkap",
      "Izin masuk Taman Nasional Komodo",
    ],
  },
  {
    id: "raja-ampat-6d5n",
    name: "Raja Ampat Paradise",
    location: "Raja Ampat",
    duration: "6D5N",
    price: "Rp 9.800.000",
    rating: 4.9,
    tag: "Premium",
    image: "wonderland-dest-rajaampat",
    description:
      "Menyelam di salah satu titik keanekaragaman hayati laut tertinggi di dunia, ditemani pemandu selam bersertifikat.",
    highlights: [
      "Resor tepi pantai, 5 malam",
      "Speedboat pribadi antar-pulau",
      "Alat diving & snorkeling lengkap",
      "Pemandu selam bersertifikat",
    ],
  },
  {
    id: "bromo-2d1n",
    name: "Bromo Sunrise",
    location: "Bromo Tengger",
    duration: "2D1N",
    price: "Rp 1.750.000",
    rating: 4.6,
    tag: "Singkat & Padat",
    image: "wonderland-dest-bromo",
    description:
      "Saksikan matahari terbit dari Penanjakan dan jelajahi lautan pasir Bromo dengan jip 4x4.",
    highlights: [
      "Jip 4x4 menuju titik sunrise",
      "Homestay 1 malam",
      "Tiket masuk kawasan Bromo",
      "Pemandu lokal berpengalaman",
    ],
  },
  {
    id: "toba-4d3n",
    name: "Toba Highland",
    location: "Danau Toba",
    duration: "4D3N",
    price: "Rp 3.900.000",
    rating: 4.7,
    tag: "Alam & Budaya",
    image: "wonderland-dest-toba",
    description:
      "Nikmati ketenangan danau vulkanik terbesar di dunia dan kenali budaya Batak langsung dari penduduk Pulau Samosir.",
    highlights: [
      "Hotel tepi danau, 3 malam",
      "Penyeberangan ke Pulau Samosir",
      "Kunjungan desa budaya Batak",
      "Sarapan setiap hari",
    ],
  },
];

export function PackageCatalog() {
  const [selected, setSelected] = useState<(typeof PACKAGES)[number] | null>(null);

  return (
    <section id="katalog" className="mx-auto flex w-full max-w-7xl scroll-mt-24 flex-col gap-12 px-6 py-16 md:py-20">
      <SectionHeader
        align="left"
        title={
          <>
            Semua <Accent>Paket Wisata</Accent> Kami
          </>
        }
        description="Bandingkan durasi, fasilitas, dan harga sebelum menentukan perjalanan berikutnya."
      />

      <div className="grid grid-cols-1 gap-8 sm:grid-cols-2 lg:grid-cols-3">
        {PACKAGES.map((pkg) => (
          <Card key={pkg.id} interactive>
            <CardMedia>
              <img src={`https://picsum.photos/seed/${pkg.image}/640/480`} alt={pkg.name} />
            </CardMedia>
            <CardBody className="flex flex-col gap-3">
              <div className="flex items-center justify-between">
                <Badge variant="neutral">{pkg.tag}</Badge>
                <StarRating rating={pkg.rating} />
              </div>
              <h3 className="text-xl font-bold text-heading">{pkg.name}</h3>
              <p className="text-sm text-muted">
                {pkg.location} · {pkg.duration}
              </p>
              <div className="mt-2 flex items-center justify-between">
                <span className="text-lg font-bold text-heading">{pkg.price}</span>
                <Button size="sm" onClick={() => setSelected(pkg)}>
                  Lihat Detail
                </Button>
              </div>
            </CardBody>
          </Card>
        ))}
      </div>

      <ItineraryTimeline />

      <Modal open={selected !== null} onClose={() => setSelected(null)} title={selected?.name}>
        {selected && (
          <div className="flex flex-col gap-4">
            <div className="overflow-hidden rounded-control">
              <img
                src={`https://picsum.photos/seed/${selected.image}/800/450`}
                alt={selected.name}
                className="h-48 w-full object-cover"
              />
            </div>
            <div className="flex items-center justify-between">
              <p className="text-sm text-muted">
                {selected.location} · {selected.duration}
              </p>
              <StarRating rating={selected.rating} />
            </div>
            <p className="text-sm text-body">{selected.description}</p>
            <ul className="flex flex-col gap-2">
              {selected.highlights.map((highlight) => (
                <li key={highlight} className="flex items-start gap-2 text-sm text-body">
                  <Check className="mt-0.5 size-4 shrink-0 text-heading" aria-hidden="true" />
                  {highlight}
                </li>
              ))}
            </ul>
            <div className="mt-2 flex items-center justify-between border-t border-border pt-4">
              <span className="text-xl font-bold text-heading">{selected.price}</span>
              <Button href="/kontak" size="sm">
                Hubungi Kami
              </Button>
            </div>
          </div>
        )}
      </Modal>
    </section>
  );
}
