import Link from "next/link";
import { NAV_LINKS } from "@/lib/nav-links";
import {
  FacebookIcon,
  InstagramIcon,
  TiktokIcon,
  YoutubeIcon,
} from "@/components/icons/social-icons";
// TODO: aktifkan lagi saat i18n siap — LanguageToggle saat ini cuma UI, belum benar-benar menerjemahkan konten.
// import { LanguageToggle } from "./language-toggle";

const CONTACT_INFO = [
  { label: "Email", value: "hello@wonderlandtravel.id" },
  { label: "Telepon", value: "+62 21 2345 6789" },
  { label: "Alamat", value: "Jakarta, Indonesia" },
];

const SOCIAL_LINKS = [
  { label: "Facebook", href: "https://facebook.com", Icon: FacebookIcon },
  { label: "Instagram", href: "https://instagram.com", Icon: InstagramIcon },
  { label: "YouTube", href: "https://youtube.com", Icon: YoutubeIcon },
  { label: "TikTok", href: "https://tiktok.com", Icon: TiktokIcon },
];

export function Footer() {
  return (
    <footer className="relative overflow-hidden bg-black text-white">
      <div className="mx-auto grid max-w-7xl grid-cols-1 gap-12 px-6 pb-16 pt-20 sm:grid-cols-3">
        <div className="flex flex-col gap-4">
          <span className="text-xl font-bold">
            <span className="text-accent text-2xl">Wonderland</span> Travel
          </span>
          <p className="max-w-xs text-sm text-white/60">
            Ke mana pun, kami temani — mitra perjalanan tepercaya untuk setiap petualanganmu.
          </p>
          <div className="flex items-center gap-3">
            {SOCIAL_LINKS.map(({ label, href, Icon }) => (
              <a
                key={label}
                href={href}
                target="_blank"
                rel="noopener noreferrer"
                aria-label={label}
                className="flex size-9 items-center justify-center rounded-full bg-white/10 text-white transition-colors hover:bg-white hover:text-black"
              >
                <Icon className="size-4" />
              </a>
            ))}
          </div>
        </div>

        <div className="flex flex-col gap-4">
          <h3 className="text-xs font-semibold tracking-wide text-white/50">NAVIGASI</h3>
          <ul className="flex flex-col gap-3">
            {NAV_LINKS.map((link) => (
              <li key={link.href}>
                <Link
                  href={link.href}
                  className="text-sm text-white/80 transition-colors hover:text-white"
                >
                  {link.label}
                </Link>
              </li>
            ))}
          </ul>
        </div>

        <div className="flex flex-col gap-4">
          <h3 className="text-xs font-semibold tracking-wide text-white/50">KONTAK</h3>
          <ul className="flex flex-col gap-3">
            {CONTACT_INFO.map((item) => (
              <li key={item.label} className="text-sm text-white/80">
                <span className="text-white/50">{item.label}: </span>
                {item.value}
              </li>
            ))}
          </ul>
        </div>
      </div>

      <div aria-hidden="true" className="pointer-events-none overflow-hidden select-none">
        <p className="text-accent -mb-4 whitespace-nowrap text-center text-[18vw] italic leading-none text-white/[0.08] sm:-mb-6 md:-mb-10">
          WONDERLAND
        </p>
      </div>

      <div className="relative border-t border-white/20">
        <div className="mx-auto flex max-w-7xl flex-col-reverse items-center gap-4 px-6 py-6 sm:flex-row sm:justify-between">
          <p className="text-xs text-white/50">
            &copy; {new Date().getFullYear()} Wonderland Travel. Seluruh hak cipta dilindungi.
          </p>
          {/* TODO: aktifkan lagi saat i18n siap */}
          {/* <LanguageToggle variant="dark" /> */}
        </div>
      </div>
    </footer>
  );
}
