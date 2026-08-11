import { Accent, SectionHeader } from "@/components/ui";

// Contoh diambil dari paket "Bali Highlights" (id: bali-4d3n) di katalog di atas.
const EXAMPLE_PACKAGE_NAME = "Bali Highlights";

const ITINERARY = [
  {
    day: 1,
    title: "Tiba di Bali & Check-in",
    description:
      "Penjemputan di bandara, check-in hotel, dan waktu santai menikmati sunset di pantai.",
  },
  {
    day: 2,
    title: "Ubud & Sawah Terasering",
    description:
      "Eksplorasi pusat seni Ubud, kunjungi Tegalalang, dan makan siang dengan pemandangan sawah.",
  },
  {
    day: 3,
    title: "Petualangan Pantai Selatan",
    description:
      "Kunjungi Uluwatu, Pantai Padang-Padang, dan tonton tari Kecak saat matahari terbenam.",
  },
  {
    day: 4,
    title: "Waktu Bebas & Kepulangan",
    description: "Waktu bebas berbelanja oleh-oleh sebelum diantar ke bandara.",
  },
];

// Ditampilkan sebagai sub-bagian di dalam section Katalog Paket (lihat
// package-catalog.tsx), bukan section halaman sendiri — supaya jelas ini cuma
// satu contoh dari salah satu paket, bukan itinerary yang berlaku untuk semua
// paket di katalog.
// TODO: idealnya itinerary jadi bagian detail tiap paket (mis. ditambahkan ke
// dalam Modal "Lihat Detail" masing-masing paket), bukan satu contoh statis
// yang sama untuk semua pengunjung halaman ini.
export function ItineraryTimeline() {
  return (
    <div className="flex flex-col gap-12 border-t border-border pt-16">
      <SectionHeader
        badge="Contoh Itinerary"
        title={
          <>
            Seperti Apa Itinerary <Accent>{EXAMPLE_PACKAGE_NAME}</Accent>?
          </>
        }
        description={`Ini contoh rencana perjalanan hari demi hari dari salah satu paket di atas (${EXAMPLE_PACKAGE_NAME}, 4D3N). Itinerary tiap paket berbeda-beda — klik "Lihat Detail" pada paket pilihanmu untuk susunan lengkapnya.`}
      />

      <div className="grid grid-cols-1 gap-12 lg:grid-cols-2 lg:items-start">
        <div className="overflow-hidden rounded-card lg:sticky lg:top-28">
          {/* TODO: ganti data riil klien — foto placeholder dari picsum.photos. */}
          <img
            src="https://picsum.photos/seed/wonderland-itinerary-bali/800/1000"
            alt="Itinerary Bali"
            className="h-full w-full object-cover"
          />
        </div>

        <div className="relative">
          <div
            className="absolute bottom-6 left-6 top-6 w-px bg-border"
            aria-hidden="true"
          />
          <div className="flex flex-col gap-10">
            {ITINERARY.map((item) => (
              <div key={item.day} className="relative flex gap-6">
                <div className="relative z-10 flex size-12 shrink-0 items-center justify-center rounded-full bg-black text-base font-bold text-white">
                  {item.day}
                </div>
                <div className="flex flex-col gap-1 pt-2">
                  <span className="text-xs font-semibold uppercase tracking-wide text-muted">
                    Hari {item.day}
                  </span>
                  <h3 className="text-lg font-bold text-heading">{item.title}</h3>
                  <p className="text-sm text-body">{item.description}</p>
                </div>
              </div>
            ))}
          </div>
        </div>
      </div>
    </div>
  );
}
