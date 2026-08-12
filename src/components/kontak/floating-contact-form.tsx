"use client";

import { useState } from "react";
import type { FormEvent } from "react";
import { Clock, Loader2, Mail, MapPin, Phone, Send } from "lucide-react";
import { Button, Input, Select, Textarea } from "@/components/ui";
import {
  FacebookIcon,
  InstagramIcon,
  TiktokIcon,
  YoutubeIcon,
} from "@/components/icons/social-icons";
import { WHATSAPP_NUMBER } from "@/lib/whatsapp";

const CONTACT_DETAILS = [
  { icon: Mail, label: "Email", value: "hello@wonderlandtravel.id" },
  { icon: Phone, label: "Telepon", value: "+62 21 2345 6789" },
  { icon: MapPin, label: "Alamat", value: "Jakarta, Indonesia" },
  { icon: Clock, label: "Jam Operasional", value: "Senin - Sabtu, 09.00 - 18.00" },
];

const SOCIAL_LINKS = [
  { label: "Facebook", href: "https://facebook.com", Icon: FacebookIcon },
  { label: "Instagram", href: "https://instagram.com", Icon: InstagramIcon },
  { label: "YouTube", href: "https://youtube.com", Icon: YoutubeIcon },
  { label: "TikTok", href: "https://tiktok.com", Icon: TiktokIcon },
];

const SUBJECT_LABELS: Record<string, string> = {
  umum: "Pertanyaan Umum",
  booking: "Booking Paket Wisata",
  kerjasama: "Kerjasama",
  lainnya: "Lainnya",
};

type FormStatus = "idle" | "loading" | "success" | "error";

export function FloatingContactForm() {
  const [status, setStatus] = useState<FormStatus>("idle");

  async function handleSubmit(event: FormEvent<HTMLFormElement>) {
    event.preventDefault();
    setStatus("loading");

    const form = event.currentTarget;
    const data = new FormData(form);
    const nama = String(data.get("nama") ?? "").trim();
    const email = String(data.get("email") ?? "").trim();
    const telepon = String(data.get("telepon") ?? "").trim();
    const subjek = String(data.get("subjek") ?? "");
    const pesan = String(data.get("pesan") ?? "").trim();

    // TODO: hubungkan ke backend/WA — form ini belum terhubung ke endpoint
    // penyimpanan lead sungguhan (mis. POST /api/kontak di sistem admin,
    // yang idealnya mencatat pesan ini sebagai lead/notifikasi baru).
    // Untuk sekarang, "kirim" berarti membuka draf pesan WhatsApp berisi isi
    // form — pengguna masih perlu menekan kirim secara manual di WhatsApp,
    // jadi ini BUKAN pengiriman otomatis.
    try {
      const message = [
        "Halo Wonderland Travel, saya ingin bertanya:",
        "",
        `Nama: ${nama}`,
        `Email: ${email}`,
        telepon ? `Telepon: ${telepon}` : null,
        `Subjek: ${SUBJECT_LABELS[subjek] ?? "Lainnya"}`,
        "",
        `Pesan: ${pesan}`,
      ]
        .filter((line): line is string => line !== null)
        .join("\n");

      const waWindow = window.open(
        `https://wa.me/${WHATSAPP_NUMBER}?text=${encodeURIComponent(message)}`,
        "_blank",
        "noopener,noreferrer",
      );

      if (!waWindow) {
        throw new Error("Popup diblokir browser");
      }

      form.reset();
      setStatus("success");
    } catch (error) {
      console.error("Gagal membuka draf WhatsApp:", error);
      setStatus("error");
    }
  }

  const isLoading = status === "loading";

  return (
    <div className="relative z-20 mx-auto -mt-32 w-full max-w-7xl px-6">
      <div className="grid grid-cols-1 gap-10 rounded-card bg-white p-8 shadow-soft-lift sm:p-12 lg:grid-cols-[1fr_1.2fr] lg:gap-16">
        <div className="flex flex-col gap-8">
          <div>
            <h2 className="text-2xl font-bold text-heading">Informasi Kontak</h2>
            <p className="mt-2 text-sm text-body">
              Hubungi kami langsung, atau isi form di samping dan tim kami akan segera merespons.
            </p>
          </div>

          <ul className="flex flex-col gap-5">
            {CONTACT_DETAILS.map((detail) => (
              <li key={detail.label} className="flex items-start gap-4">
                <span className="flex size-10 shrink-0 items-center justify-center rounded-full bg-mist">
                  <detail.icon className="size-4 text-heading" aria-hidden="true" />
                </span>
                <div>
                  <p className="text-xs font-semibold uppercase tracking-wide text-muted">
                    {detail.label}
                  </p>
                  <p className="text-sm font-medium text-heading">{detail.value}</p>
                </div>
              </li>
            ))}
          </ul>

          <div className="flex items-center gap-3 border-t border-border pt-6">
            {SOCIAL_LINKS.map(({ label, href, Icon }) => (
              <a
                key={label}
                href={href}
                target="_blank"
                rel="noopener noreferrer"
                aria-label={label}
                className="flex size-9 items-center justify-center rounded-full bg-mist text-heading transition-colors hover:bg-black hover:text-white"
              >
                <Icon className="size-4" />
              </a>
            ))}
          </div>
        </div>

        <div>
          {status === "success" ? (
            <div className="flex h-full flex-col items-center justify-center gap-3 rounded-control bg-mist p-10 text-center">
              <p className="text-lg font-bold text-heading">Draf Pesan WhatsApp Terbuka</p>
              <p className="text-sm text-body">
                Tab baru berisi pesanmu sudah terbuka di WhatsApp — tinggal tekan kirim di sana
                untuk menghubungi tim kami.
              </p>
              <Button variant="outline" size="sm" onClick={() => setStatus("idle")}>
                Kirim Pesan Lain
              </Button>
            </div>
          ) : (
            <form onSubmit={handleSubmit} className="flex flex-col gap-4">
              <div className="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <Input
                  name="nama"
                  label="Nama Lengkap"
                  placeholder="Nama kamu"
                  required
                  disabled={isLoading}
                />
                <Input
                  name="email"
                  label="Email"
                  type="email"
                  placeholder="nama@email.com"
                  required
                  disabled={isLoading}
                />
              </div>
              <div className="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <Input
                  name="telepon"
                  label="Nomor Telepon"
                  type="tel"
                  placeholder="08xx xxxx xxxx"
                  disabled={isLoading}
                />
                <Select name="subjek" label="Subjek" defaultValue="" disabled={isLoading}>
                  <option value="" disabled>
                    Pilih subjek
                  </option>
                  <option value="umum">Pertanyaan Umum</option>
                  <option value="booking">Booking Paket Wisata</option>
                  <option value="kerjasama">Kerjasama</option>
                  <option value="lainnya">Lainnya</option>
                </Select>
              </div>
              <Textarea
                name="pesan"
                label="Pesan"
                placeholder="Ceritakan kebutuhan perjalananmu..."
                required
                disabled={isLoading}
              />

              {status === "error" && (
                <p className="text-sm text-red-600" role="alert">
                  Gagal membuka WhatsApp — mungkin pop-up diblokir browser. Coba lagi, atau hubungi
                  kami langsung lewat info di samping.
                </p>
              )}

              <Button type="submit" className="mt-2 justify-center" disabled={isLoading}>
                {isLoading ? (
                  <Loader2 className="size-4 animate-spin" aria-hidden="true" />
                ) : (
                  <Send className="size-4" aria-hidden="true" />
                )}
                {isLoading ? "Menyiapkan pesan..." : "Kirim Pesan"}
              </Button>
            </form>
          )}
        </div>
      </div>
    </div>
  );
}
