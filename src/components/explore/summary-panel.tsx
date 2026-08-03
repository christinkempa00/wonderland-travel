import { Building2, Car, MessageCircle, Plane, X } from "lucide-react";
import { Button } from "@/components/ui";
import type { ExploreCategory, FlightItem, HotelItem, RentalItem } from "./data";
import { formatIDR, WHATSAPP_NUMBER } from "./data";

export interface SummaryPanelProps {
  hotel: HotelItem | null;
  pesawat: FlightItem | null;
  rental: RentalItem | null;
  onRemove: (category: ExploreCategory) => void;
}

export function SummaryPanel({ hotel, pesawat, rental, onRemove }: SummaryPanelProps) {
  const rows = [
    {
      category: "hotel" as const,
      label: "Hotel",
      icon: Building2,
      title: hotel?.name,
      subtitle: hotel?.location,
      price: hotel?.price,
    },
    {
      category: "pesawat" as const,
      label: "Pesawat",
      icon: Plane,
      title: pesawat?.name,
      subtitle: pesawat ? `${pesawat.from} → ${pesawat.to}` : undefined,
      price: pesawat?.price,
    },
    {
      category: "rental" as const,
      label: "Rental",
      icon: Car,
      title: rental?.name,
      subtitle: rental?.type,
      price: rental?.price,
    },
  ];

  const hasSelection = rows.some((row) => row.price !== undefined);
  const total = rows.reduce((sum, row) => sum + (row.price ?? 0), 0);

  const message = hasSelection
    ? `Halo Wonderland Travel, saya ingin memesan:\n${rows
        .filter((row) => row.title)
        .map(
          (row) =>
            `- ${row.label}: ${row.title}${row.subtitle ? ` (${row.subtitle})` : ""} — ${formatIDR(row.price ?? 0)}`,
        )
        .join("\n")}\n\nTotal: ${formatIDR(total)}\n\nMohon info ketersediaan dan langkah selanjutnya. Terima kasih!`
    : "Halo Wonderland Travel, saya ingin bertanya tentang booking hotel/pesawat/rental.";

  const waLink = `https://wa.me/${WHATSAPP_NUMBER}?text=${encodeURIComponent(message)}`;

  return (
    <div className="flex flex-col gap-6 rounded-card border border-border bg-white p-6 shadow-soft">
      <h3 className="text-lg font-bold text-heading">Ringkasan Pesanan</h3>

      <div className="flex flex-col gap-4">
        {rows.map((row) => (
          <div
            key={row.category}
            className="flex items-start justify-between gap-3 border-b border-border pb-4 last:border-b-0 last:pb-0"
          >
            <div className="flex items-start gap-3">
              <span className="flex size-9 shrink-0 items-center justify-center rounded-full bg-mist">
                <row.icon className="size-4 text-heading" aria-hidden="true" />
              </span>
              <div>
                <p className="text-xs font-semibold uppercase tracking-wide text-muted">
                  {row.label}
                </p>
                {row.title ? (
                  <>
                    <p className="text-sm font-semibold text-heading">{row.title}</p>
                    {row.subtitle && <p className="text-xs text-muted">{row.subtitle}</p>}
                  </>
                ) : (
                  <p className="text-sm text-muted">Belum dipilih</p>
                )}
              </div>
            </div>
            {row.title && (
              <button
                type="button"
                onClick={() => onRemove(row.category)}
                aria-label={`Hapus pilihan ${row.label}`}
                className="shrink-0 text-muted transition-colors hover:text-heading"
              >
                <X className="size-4" />
              </button>
            )}
          </div>
        ))}
      </div>

      <div className="flex items-center justify-between border-t border-border pt-4">
        <span className="text-sm font-semibold text-heading">Total</span>
        <span className="text-xl font-bold text-heading">
          {hasSelection ? formatIDR(total) : "—"}
        </span>
      </div>

      <Button href={waLink} target="_blank" rel="noopener noreferrer" className="justify-center">
        <MessageCircle className="size-4" aria-hidden="true" />
        Pesan via WhatsApp
      </Button>
    </div>
  );
}
