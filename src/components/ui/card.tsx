import type { HTMLAttributes } from "react";
import { cn } from "@/lib/cn";

export interface CardProps extends HTMLAttributes<HTMLDivElement> {
  /** Adds hover lift + shadow, for clickable/interactive cards (e.g. tour cards). */
  interactive?: boolean;
}

export function Card({ interactive = false, className, ...props }: CardProps) {
  return (
    <div
      className={cn(
        "group overflow-hidden rounded-card border border-border bg-white shadow-soft",
        interactive &&
          "transition-all duration-300 ease-out hover:-translate-y-1 hover:shadow-soft-lift",
        className,
      )}
      {...props}
    />
  );
}

/** Media wrapper for a Card — clips and zooms its image/content on card hover. */
export function CardMedia({ className, ...props }: HTMLAttributes<HTMLDivElement>) {
  return (
    <div
      className={cn(
        "aspect-4/3 overflow-hidden [&>img]:h-full [&>img]:w-full [&>img]:object-cover " +
          "[&>img]:transition-transform [&>img]:duration-300 [&>img]:ease-out " +
          "group-hover:[&>img]:scale-105",
        className,
      )}
      {...props}
    />
  );
}

export function CardBody({ className, ...props }: HTMLAttributes<HTMLDivElement>) {
  return <div className={cn("p-6", className)} {...props} />;
}
