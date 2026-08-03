import { Accent, Badge, Button } from "@/components/ui";

export function Cta() {
  return (
    <section className="relative overflow-hidden bg-black py-32 text-white">
      <img
        src="https://picsum.photos/seed/wonderland-cta/1920/1080"
        alt=""
        className="absolute inset-0 h-full w-full object-cover opacity-40"
      />
      <div className="absolute inset-0 bg-black/50" aria-hidden="true" />

      <div className="relative z-10 mx-auto flex w-full max-w-3xl flex-col items-center gap-6 px-6 text-center">
        <Badge variant="dark">Mulai Perjalananmu</Badge>
        <h2 className="text-4xl font-bold sm:text-5xl">
          Siap <Accent>Menjelajah</Accent> Bersama Kami?
        </h2>
        <p className="max-w-xl text-white/80">
          Konsultasikan rencana perjalananmu dengan tim kami dan dapatkan itinerary yang dirancang
          khusus untukmu.
        </p>
        <div className="mt-2 flex flex-wrap justify-center gap-4">
          <Button href="/kontak" variant="inverse" size="lg">
            Hubungi Kami
          </Button>
          <Button
            href="/paket-wisata"
            variant="outline"
            size="lg"
            className="border-white text-white hover:bg-white/10"
          >
            Lihat Paket Wisata
          </Button>
        </div>
      </div>
    </section>
  );
}
