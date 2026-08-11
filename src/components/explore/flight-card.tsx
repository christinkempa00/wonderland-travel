import { Plane } from "lucide-react";
import { Button, StarRating } from "@/components/ui";
import { cn } from "@/lib/cn";
import { formatIDR } from "./data";

export interface FlightCardProps {
  airline: string;
  from: string;
  to: string;
  departTime: string;
  arriveTime: string;
  duration: string;
  travelClass: string;
  price: number;
  rating: number;
  selected: boolean;
  onSelect: () => void;
}

export function FlightCard({
  airline,
  from,
  to,
  departTime,
  arriveTime,
  duration,
  travelClass,
  price,
  rating,
  selected,
  onSelect,
}: FlightCardProps) {
  return (
    <div className="w-80 shrink-0 snap-start sm:w-96">
      <div
        className={cn(
          "flex h-full flex-col gap-4 rounded-card bg-white p-6 shadow-soft",
          "transition-all duration-300 ease-out hover:-translate-y-1 hover:shadow-soft-lift",
          selected && "ring-2 ring-black",
        )}
      >
        <div className="flex items-center justify-between">
          <div className="flex items-center gap-2">
            <span className="flex size-9 items-center justify-center rounded-full bg-mist">
              <Plane className="size-4 text-heading" aria-hidden="true" />
            </span>
            <span className="text-sm font-semibold text-heading">{airline}</span>
          </div>
          <StarRating rating={rating} size={14} />
        </div>

        <div className="flex items-center justify-between gap-3">
          <div className="text-center">
            <p className="text-lg font-bold text-heading">{departTime}</p>
            <p className="text-xs text-muted">{from}</p>
          </div>
          <div className="flex flex-1 flex-col items-center gap-1">
            <span className="text-xs text-muted">{duration}</span>
            <div className="h-px w-full bg-border" />
            <span className="text-xs text-muted">{travelClass}</span>
          </div>
          <div className="text-center">
            <p className="text-lg font-bold text-heading">{arriveTime}</p>
            <p className="text-xs text-muted">{to}</p>
          </div>
        </div>

        <div className="mt-2 flex items-center justify-between border-t border-border pt-4">
          <span className="text-lg font-bold text-heading">{formatIDR(price)}</span>
          <Button size="sm" variant={selected ? "outline" : "primary"} onClick={onSelect}>
            {selected ? "Terpilih" : "Pilih"}
          </Button>
        </div>
      </div>
    </div>
  );
}
