export type PhotoSize = "normal" | "wide" | "tall" | "large";

export interface Photo {
  id: string;
  destination: string;
  caption: string;
  seed: string;
  size: PhotoSize;
}

export const DESTINATIONS = [
  "Bali",
  "Yogyakarta",
  "Labuan Bajo",
  "Raja Ampat",
  "Bromo Tengger",
  "Danau Toba",
] as const;

// TODO: ganti data riil klien — seed di bawah cuma nama acuan buat generate
// foto placeholder dari picsum.photos (lihat gallery-grid.tsx & lightbox.tsx),
// bukan foto asli perjalanan/klien. Ganti dengan URL foto sungguhan sebelum go-live.
export const PHOTOS: Photo[] = [
  { id: "g01", destination: "Bali", caption: "Sawah terasering Ubud saat pagi hari", seed: "wonderland-gal-bali-01", size: "large" },
  { id: "g02", destination: "Yogyakarta", caption: "Candi Prambanan menjelang senja", seed: "wonderland-gal-yogya-01", size: "normal" },
  { id: "g03", destination: "Labuan Bajo", caption: "Perahu phinisi di perairan Komodo", seed: "wonderland-gal-lbj-01", size: "tall" },
  { id: "g04", destination: "Bali", caption: "Tebing Uluwatu dari kejauhan", seed: "wonderland-gal-bali-02", size: "normal" },
  { id: "g05", destination: "Raja Ampat", caption: "Gugusan karst khas Raja Ampat", seed: "wonderland-gal-raja-01", size: "wide" },
  { id: "g06", destination: "Bromo Tengger", caption: "Lautan pasir Bromo saat sunrise", seed: "wonderland-gal-bromo-01", size: "normal" },
  { id: "g07", destination: "Danau Toba", caption: "Tepi Danau Toba dari Samosir", seed: "wonderland-gal-toba-01", size: "normal" },
  { id: "g08", destination: "Yogyakarta", caption: "Jalan Malioboro di malam hari", seed: "wonderland-gal-yogya-02", size: "tall" },
  { id: "g09", destination: "Labuan Bajo", caption: "Pink Beach dari udara", seed: "wonderland-gal-lbj-02", size: "large" },
  { id: "g10", destination: "Bali", caption: "Pura tepi danau di Bedugul", seed: "wonderland-gal-bali-03", size: "normal" },
  { id: "g11", destination: "Bromo Tengger", caption: "Kawah Bromo dari Penanjakan", seed: "wonderland-gal-bromo-02", size: "wide" },
  { id: "g12", destination: "Raja Ampat", caption: "Snorkeling di perairan jernih", seed: "wonderland-gal-raja-02", size: "normal" },
  { id: "g13", destination: "Danau Toba", caption: "Rumah adat Batak di Samosir", seed: "wonderland-gal-toba-02", size: "normal" },
  { id: "g14", destination: "Yogyakarta", caption: "Candi Borobudur di pagi berkabut", seed: "wonderland-gal-yogya-03", size: "normal" },
  { id: "g15", destination: "Labuan Bajo", caption: "Bukit Cinta menjelang matahari terbenam", seed: "wonderland-gal-lbj-03", size: "normal" },
  { id: "g16", destination: "Bali", caption: "Pantai pasir putih di Nusa Dua", seed: "wonderland-gal-bali-04", size: "wide" },
];
