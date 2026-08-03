import { Star } from "lucide-react";
import { cn } from "@/lib/cn";

export interface StarRatingProps {
  rating: number;
  max?: number;
  size?: number;
  className?: string;
}

export function StarRating({ rating, max = 5, size = 16, className }: StarRatingProps) {
  return (
    <div className={cn("inline-flex items-center gap-0.5", className)} aria-label={`${rating} dari ${max} bintang`}>
      {Array.from({ length: max }, (_, index) => {
        const fillRatio = Math.min(Math.max(rating - index, 0), 1);
        return (
          <span key={index} className="relative inline-block" style={{ width: size, height: size }}>
            <Star className="absolute inset-0 text-mist" style={{ width: size, height: size }} fill="currentColor" />
            {fillRatio > 0 && (
              <span
                className="absolute inset-0 overflow-hidden"
                style={{ width: `${fillRatio * 100}%` }}
              >
                <Star className="text-black" style={{ width: size, height: size }} fill="currentColor" />
              </span>
            )}
          </span>
        );
      })}
    </div>
  );
}
