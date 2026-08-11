import { Accent, Button, SectionHeader } from "@/components/ui";

export function Cta() {
  return (
    <section className="relative overflow-hidden bg-black py-16 text-white md:py-20">
      {/* TODO: ganti data riil klien — foto latar placeholder dari picsum.photos. */}
      <img
        src="https://picsum.photos/seed/wonderland-cta/1920/1080"
        alt=""
        className="absolute inset-0 h-full w-full object-cover opacity-40"
      />
      <div className="absolute inset-0 bg-black/50" aria-hidden="true" />

      <div className="relative z-10 mx-auto flex w-full max-w-3xl flex-col items-center px-6">
        <SectionHeader
          tone="dark"
          title={
            <>
              Siap <Accent>Menjelajah</Accent> Bersama Kami?
            </>
          }
          description="Konsultasikan rencana perjalananmu dengan tim kami dan dapatkan itinerary yang dirancang khusus untukmu."
        />
        <div className="mt-6 flex flex-wrap justify-center gap-4">
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
