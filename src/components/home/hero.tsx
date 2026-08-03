import { ChevronDown } from "lucide-react";
import { Accent, Badge, Button } from "@/components/ui";

export function Hero() {
  return (
    <section className="relative min-h-screen overflow-hidden bg-black">
      <img
        src="https://picsum.photos/seed/wonderland-hero/1920/1080"
        alt="Pemandangan destinasi wisata"
        className="absolute inset-0 h-full w-full object-cover"
      />
      <div className="absolute inset-0 bg-black/50" aria-hidden="true" />

      <div className="relative z-10 flex min-h-screen flex-col">
        <div className="flex flex-1 items-center px-6 pt-32">
          <div className="mx-auto flex w-full max-w-7xl flex-col items-start gap-6">
            <Badge variant="dark">Wonderland Travel</Badge>
            <h1 className="max-w-3xl text-5xl font-bold leading-tight text-white sm:text-6xl md:text-7xl">
              Ke Mana Pun, <Accent>Kami Temani</Accent>
            </h1>
            <p className="max-w-xl text-lg text-white/80">
              Rencanakan perjalanan impianmu bersama pemandu berpengalaman dan itinerary yang
              disesuaikan untukmu.
            </p>
            <div className="mt-4 flex flex-wrap gap-4">
              <Button href="/paket-wisata" variant="inverse" size="lg">
                Lihat Paket Wisata
              </Button>
              <Button
                href="/kontak"
                variant="outline"
                size="lg"
                className="border-white text-white hover:bg-white/10"
              >
                Hubungi Kami
              </Button>
            </div>
          </div>
        </div>

        <div aria-hidden="true" className="pointer-events-none select-none overflow-hidden">
          <p className="text-accent -mb-6 whitespace-nowrap text-center text-[16vw] italic leading-none text-white/10 sm:-mb-8 md:-mb-14">
            WONDERLAND
          </p>
        </div>
      </div>

      <ChevronDown
        className="absolute bottom-6 left-1/2 z-10 size-8 -translate-x-1/2 animate-bounce text-white/70"
        aria-hidden="true"
      />
    </section>
  );
}
