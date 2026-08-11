import { Accent, Badge } from "@/components/ui";

export function HeroBanner() {
  return (
    <section className="relative flex min-h-[60vh] items-center overflow-hidden bg-black pb-40">
      {/* TODO: ganti data riil klien — foto latar placeholder dari picsum.photos. */}
      <img
        src="https://picsum.photos/seed/wonderland-kontak-goldenhour/1920/1080"
        alt="Golden hour di destinasi wisata"
        className="absolute inset-0 h-full w-full object-cover"
      />
      <div className="absolute inset-0 bg-black/45" aria-hidden="true" />

      <div className="relative z-10 mx-auto flex w-full max-w-7xl flex-col items-start gap-6 px-6 pt-16 md:pt-20">
        <Badge variant="dark">Kontak</Badge>
        <h1 className="max-w-2xl text-4xl font-bold text-white sm:text-5xl">
          Mari <Accent>Rencanakan</Accent> Perjalananmu
        </h1>
        <p className="max-w-xl text-lg text-white/80">
          Tim kami siap membantu menjawab pertanyaan dan menyusun itinerary sesuai kebutuhanmu.
        </p>
      </div>
    </section>
  );
}
