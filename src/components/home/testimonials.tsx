import { Quote } from "lucide-react";
import { Accent, SectionHeader, StarRating } from "@/components/ui";

const TESTIMONIALS = [
  {
    name: "Rina Ayu",
    location: "Jakarta",
    quote:
      "Perjalanan ke Labuan Bajo paling terorganisir yang pernah saya ikuti. Semua detail diurus dengan rapi.",
    rating: 5,
    avatarSeed: "wonderland-avatar-rina",
  },
  {
    name: "Dimas Prasetyo",
    location: "Surabaya",
    quote:
      "Pemandunya ramah dan sangat paham medan. Itinerary fleksibel banget waktu kami minta ubah jadwal.",
    rating: 5,
    avatarSeed: "wonderland-avatar-dimas",
  },
  {
    name: "Sari Wulandari",
    location: "Bandung",
    quote:
      "Harga jujur, tidak ada biaya tambahan mendadak. Bakal booking lagi untuk trip berikutnya.",
    rating: 4.5,
    avatarSeed: "wonderland-avatar-sari",
  },
  {
    name: "Andi Kurniawan",
    location: "Medan",
    quote:
      "Respon tim di WhatsApp cepat banget, bahkan tengah malam. Sangat membantu untuk trip mendadak.",
    rating: 5,
    avatarSeed: "wonderland-avatar-andi",
  },
];

export function Testimonials() {
  return (
    <section className="bg-black py-24 text-white">
      <div className="mx-auto flex w-full max-w-7xl flex-col gap-12 px-6">
        <SectionHeader
          tone="dark"
          badge="Testimoni"
          title={
            <>
              Kata Mereka Tentang <Accent>Perjalanannya</Accent>
            </>
          }
          description="Cerita nyata dari petualang yang sudah menjelajah bersama kami."
        />

        <div className="-mx-6 flex snap-x snap-mandatory gap-6 overflow-x-auto px-6 pb-4 [scrollbar-width:none] [&::-webkit-scrollbar]:hidden">
          {TESTIMONIALS.map((t) => (
            <div
              key={t.name}
              className="flex w-[85%] shrink-0 snap-start flex-col gap-4 rounded-card border border-white/10 bg-white/5 p-8 sm:w-[60%] lg:w-[31%]"
            >
              <Quote className="size-8 text-white/30" aria-hidden="true" />
              <p className="text-base text-white/90">&ldquo;{t.quote}&rdquo;</p>
              <div className="mt-auto flex items-center gap-3 pt-4">
                <img
                  src={`https://picsum.photos/seed/${t.avatarSeed}/80/80`}
                  alt={t.name}
                  className="size-10 shrink-0 rounded-full object-cover"
                />
                <div className="min-w-0">
                  <p className="truncate text-sm font-semibold text-white">{t.name}</p>
                  <p className="text-xs text-white/50">{t.location}</p>
                </div>
                <StarRating
                  rating={t.rating}
                  size={14}
                  className="ml-auto shrink-0"
                  trackClassName="text-white/20"
                  fillClassName="text-white"
                />
              </div>
            </div>
          ))}
        </div>
      </div>
    </section>
  );
}
