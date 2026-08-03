import { Button, Card, CardBody, CardMedia, StarRating } from "@/components/ui";
import { cn } from "@/lib/cn";
import { formatIDR } from "./data";

export interface PhotoResultCardProps {
  image: string;
  name: string;
  location: string;
  price: number;
  priceUnit: string;
  rating: number;
  specs: string[];
  selected: boolean;
  onSelect: () => void;
}

/** Result card shared by Hotel & Rental tabs — photo-led, spec chips, price + select button. */
export function PhotoResultCard({
  image,
  name,
  location,
  price,
  priceUnit,
  rating,
  specs,
  selected,
  onSelect,
}: PhotoResultCardProps) {
  return (
    <div className="w-72 shrink-0 snap-start sm:w-80">
      <Card interactive className={cn(selected && "ring-2 ring-black")}>
        <CardMedia>
          <img src={`https://picsum.photos/seed/${image}/640/480`} alt={name} />
        </CardMedia>
        <CardBody className="flex flex-col gap-3">
          <div className="flex items-center justify-between">
            <span className="text-xs font-semibold uppercase tracking-wide text-muted">
              {location}
            </span>
            <StarRating rating={rating} size={14} />
          </div>
          <h3 className="text-lg font-bold text-heading">{name}</h3>
          <div className="flex flex-wrap gap-2">
            {specs.map((spec) => (
              <span key={spec} className="rounded-full bg-mist px-3 py-1 text-xs text-body">
                {spec}
              </span>
            ))}
          </div>
          <div className="mt-2 flex items-center justify-between">
            <div>
              <span className="text-lg font-bold text-heading">{formatIDR(price)}</span>
              <span className="text-xs text-muted">{priceUnit}</span>
            </div>
            <Button size="sm" variant={selected ? "outline" : "primary"} onClick={onSelect}>
              {selected ? "Terpilih" : "Pilih"}
            </Button>
          </div>
        </CardBody>
      </Card>
    </div>
  );
}
