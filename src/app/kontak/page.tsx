import type { Metadata } from "next";
import { FaqAccordion } from "@/components/kontak/faq-accordion";
import { FloatingContactForm } from "@/components/kontak/floating-contact-form";
import { HeroBanner } from "@/components/kontak/hero-banner";

export const metadata: Metadata = {
  title: "Kontak — Wonderland Travel",
  description: "Hubungi Wonderland Travel untuk konsultasi dan pemesanan perjalananmu.",
};

export default function KontakPage() {
  return (
    <>
      <HeroBanner />
      <FloatingContactForm />
      <FaqAccordion />
    </>
  );
}
