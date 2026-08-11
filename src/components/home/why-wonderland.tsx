import { Headset, Route, UserCheck, Wallet } from "lucide-react";
import { Accent, SectionHeader } from "@/components/ui";

const FEATURES = [
  {
    icon: Wallet,
    title: "Harga Transparan",
    description: "Tidak ada biaya tersembunyi — semua tercantum jelas sejak awal pemesanan.",
  },
  {
    icon: UserCheck,
    title: "Pemandu Berpengalaman",
    description: "Tim lokal yang mengenal setiap sudut destinasi dan siap membantu perjalananmu.",
  },
  {
    icon: Route,
    title: "Itinerary Fleksibel",
    description: "Sesuaikan rencana perjalanan kapan saja sesuai kebutuhan dan mintamu.",
  },
  {
    icon: Headset,
    title: "Dukungan 24/7",
    description: "Tim kami siap membantu kapan pun kamu butuhkan, sebelum maupun saat perjalanan.",
  },
];

export function WhyWonderland() {
  return (
    <section className="py-16 md:py-20">
      <div className="mx-auto flex w-full max-w-7xl flex-col gap-12 px-6">
        <SectionHeader
          title={
            <>
              Alasan Memilih <Accent>Wonderland</Accent>
            </>
          }
          description="Empat prinsip yang kami pegang di setiap perjalanan yang kami rancang untukmu."
        />
        <div className="grid grid-cols-1 gap-6 sm:grid-cols-2">
          {FEATURES.map(({ icon: Icon, title, description }) => (
            <div
              key={title}
              className="flex flex-col gap-4 rounded-card bg-white p-8 shadow-soft"
            >
              <div className="flex size-12 items-center justify-center rounded-full bg-mist">
                <Icon className="size-5 text-heading" />
              </div>
              <h3 className="text-lg font-bold text-heading">{title}</h3>
              <p className="text-sm text-body">{description}</p>
            </div>
          ))}
        </div>
      </div>
    </section>
  );
}
