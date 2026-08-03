"use client";

import { useState } from "react";
import type { FormEvent } from "react";
import { Clock, Mail, MapPin, Phone, Send } from "lucide-react";
import { Button, Input, Select, Textarea } from "@/components/ui";
import {
  FacebookIcon,
  InstagramIcon,
  TiktokIcon,
  YoutubeIcon,
} from "@/components/icons/social-icons";

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

export function FloatingContactForm() {
  const [submitted, setSubmitted] = useState(false);

  function handleSubmit(event: FormEvent<HTMLFormElement>) {
    event.preventDefault();
    setSubmitted(true);
  }

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
          {submitted ? (
            <div className="flex h-full flex-col items-center justify-center gap-3 rounded-control bg-mist p-10 text-center">
              <p className="text-lg font-bold text-heading">Pesan Terkirim!</p>
              <p className="text-sm text-body">
                Terima kasih sudah menghubungi kami. Tim kami akan membalas secepatnya.
              </p>
              <Button variant="outline" size="sm" onClick={() => setSubmitted(false)}>
                Kirim Pesan Lain
              </Button>
            </div>
          ) : (
            <form onSubmit={handleSubmit} className="flex flex-col gap-4">
              <div className="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <Input label="Nama Lengkap" placeholder="Nama kamu" required />
                <Input label="Email" type="email" placeholder="nama@email.com" required />
              </div>
              <div className="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <Input label="Nomor Telepon" type="tel" placeholder="08xx xxxx xxxx" />
                <Select label="Subjek" defaultValue="">
                  <option value="" disabled>
                    Pilih subjek
                  </option>
                  <option value="umum">Pertanyaan Umum</option>
                  <option value="booking">Booking Paket Wisata</option>
                  <option value="kerjasama">Kerjasama</option>
                  <option value="lainnya">Lainnya</option>
                </Select>
              </div>
              <Textarea label="Pesan" placeholder="Ceritakan kebutuhan perjalananmu..." required />
              <Button type="submit" className="mt-2 justify-center">
                <Send className="size-4" aria-hidden="true" />
                Kirim Pesan
              </Button>
            </form>
          )}
        </div>
      </div>
    </div>
  );
}
