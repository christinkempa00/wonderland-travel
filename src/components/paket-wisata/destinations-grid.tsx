import { Accent, SectionHeader } from "@/components/ui";

// TODO: ganti data riil klien — field `image` (seed picsum.photos) di bawah
// masih placeholder, bukan foto destinasi sungguhan.
const DESTINATIONS = [
  {
    name: "Bali",
    tagline: "Pantai & budaya",
    packages: 8,
    image: "wonderland-dest-bali",
  },
  {
    name: "Yogyakarta",
    tagline: "Sejarah & kuliner",
    packages: 5,
    image: "wonderland-dest-yogyakarta",
  },
  {
    name: "Labuan Bajo",
    tagline: "Laut & komodo",
    packages: 6,
    image: "wonderland-dest-labuanbajo",
  },
  {
    name: "Raja Ampat",
    tagline: "Surga bawah laut",
    packages: 4,
    image: "wonderland-dest-rajaampat",
  },
  {
    name: "Bromo Tengger",
    tagline: "Sunrise pegunungan",
    packages: 3,
    image: "wonderland-dest-bromo",
  },
  {
    name: "Danau Toba",
    tagline: "Danau vulkanik",
    packages: 4,
    image: "wonderland-dest-toba",
  },
];

export function DestinationsGrid() {
  return (
    <section className="mx-auto flex w-full max-w-7xl flex-col gap-12 px-6 pb-16 pt-8 md:pb-20 md:pt-10">
      <SectionHeader
        badge="Destinasi"
        title={
          <>
            Jelajahi <Accent>Indonesia</Accent> dari Ujung ke Ujung
          </>
        }
        description="Pilih destinasi impianmu — setiap tempat punya paket wisata yang dirancang khusus untuk pengalaman terbaik."
      />

      <div className="grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-3">
        {DESTINATIONS.map((dest) => (
          <a
            key={dest.name}
            href="#katalog"
            className="group relative aspect-4/5 overflow-hidden rounded-card"
          >
            <img
              src={`https://picsum.photos/seed/${dest.image}/640/800`}
              alt={dest.name}
              className="h-full w-full object-cover transition-transform duration-300 ease-out group-hover:scale-105"
            />
            <div className="absolute inset-x-0 bottom-0 bg-black/60 p-6">
              <h3 className="text-xl font-bold text-white">{dest.name}</h3>
              <p className="text-sm text-white/70">{dest.tagline}</p>
              <p className="mt-2 text-xs font-semibold uppercase tracking-wide text-white/50">
                {dest.packages} paket tersedia
              </p>
            </div>
          </a>
        ))}
      </div>
    </section>
  );
}
