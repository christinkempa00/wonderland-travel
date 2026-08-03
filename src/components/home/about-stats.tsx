import { Accent, SectionHeader } from "@/components/ui";

const STATS = [
  { value: "500+", label: "Klien Puas" },
  { value: "50+", label: "Destinasi" },
  { value: "10", label: "Tahun Pengalaman" },
  { value: "24/7", label: "Dukungan" },
];

export function AboutStats() {
  return (
    <section className="mx-auto grid w-full max-w-7xl grid-cols-1 items-center gap-12 px-6 py-24 lg:grid-cols-2">
      <div className="overflow-hidden rounded-card">
        <img
          src="https://picsum.photos/seed/wonderland-about/800/900"
          alt="Tim Wonderland Travel"
          className="h-full w-full object-cover"
        />
      </div>

      <div className="flex flex-col gap-8">
        <SectionHeader
          align="left"
          badge="Tentang Kami"
          title={
            <>
              Perjalanan Yang <Accent>Terasa Personal</Accent>
            </>
          }
          description="Sejak awal, kami percaya setiap perjalanan harus terasa dirancang khusus untukmu — bukan sekadar paket generik yang sama untuk semua orang."
        />
        <div className="grid grid-cols-2 gap-6 border-t border-border pt-8 sm:grid-cols-4 lg:grid-cols-2 xl:grid-cols-4">
          {STATS.map((stat) => (
            <div key={stat.label} className="flex flex-col gap-1">
              <span className="text-3xl font-bold text-heading">{stat.value}</span>
              <span className="text-sm text-muted">{stat.label}</span>
            </div>
          ))}
        </div>
      </div>
    </section>
  );
}
