"use client";

import { useState } from "react";
import { ChevronDown } from "lucide-react";
import { Accent, SectionHeader } from "@/components/ui";
import { cn } from "@/lib/cn";

const FAQS = [
  {
    question: "Bagaimana cara memesan paket wisata?",
    answer:
      "Kamu bisa memilih paket di halaman Paket Wisata, lalu hubungi kami via WhatsApp atau isi form kontak di atas untuk konfirmasi ketersediaan dan pembayaran.",
  },
  {
    question: "Apakah harga sudah termasuk tiket pesawat?",
    answer:
      "Sebagian besar paket wisata domestik tidak termasuk tiket pesawat, kecuali dinyatakan lain. Kamu juga bisa memesan tiket pesawat terpisah lewat halaman Explore.",
  },
  {
    question: "Berapa lama proses konfirmasi booking?",
    answer:
      "Tim kami biasanya membalas dalam 1x24 jam pada hari kerja setelah kamu mengirim pesan atau melakukan pemesanan.",
  },
  {
    question: "Apakah bisa reschedule atau membatalkan pesanan?",
    answer:
      "Bisa, dengan ketentuan yang berlaku tergantung jenis paket dan waktu pengajuan. Hubungi tim kami untuk detail kebijakan pembatalan.",
  },
  {
    question: "Metode pembayaran apa saja yang tersedia?",
    answer:
      "Kami menerima transfer bank, e-wallet, dan kartu kredit. Detail rekening akan diberikan setelah pesanan dikonfirmasi.",
  },
  {
    question: "Apakah bisa custom itinerary sendiri?",
    answer:
      "Tentu! Kamu bisa mendiskusikan kebutuhan perjalanan khususmu langsung dengan tim kami lewat WhatsApp atau form kontak di halaman ini.",
  },
];

export function FaqAccordion() {
  const [openIndex, setOpenIndex] = useState<number | null>(0);

  return (
    <section className="mx-auto flex w-full max-w-3xl flex-col gap-10 px-6 py-24">
      <SectionHeader
        badge="FAQ"
        title={
          <>
            Pertanyaan yang <Accent>Sering Diajukan</Accent>
          </>
        }
        description="Belum ketemu jawabannya? Hubungi kami langsung lewat form di atas atau WhatsApp."
      />

      <div className="flex flex-col divide-y divide-border rounded-card border border-border bg-white">
        {FAQS.map((faq, i) => {
          const isOpen = openIndex === i;
          return (
            <div key={faq.question}>
              <button
                type="button"
                onClick={() => setOpenIndex(isOpen ? null : i)}
                aria-expanded={isOpen}
                className="flex w-full items-center justify-between gap-4 px-6 py-5 text-left"
              >
                <span className="text-sm font-semibold text-heading sm:text-base">
                  {faq.question}
                </span>
                <ChevronDown
                  className={cn(
                    "size-5 shrink-0 text-muted transition-transform duration-300",
                    isOpen && "rotate-180 text-heading",
                  )}
                  aria-hidden="true"
                />
              </button>
              <div
                className="grid transition-[grid-template-rows] duration-300 ease-out"
                style={{ gridTemplateRows: isOpen ? "1fr" : "0fr" }}
              >
                <div className="overflow-hidden">
                  <p className="px-6 pb-5 text-sm text-body">{faq.answer}</p>
                </div>
              </div>
            </div>
          );
        })}
      </div>
    </section>
  );
}
