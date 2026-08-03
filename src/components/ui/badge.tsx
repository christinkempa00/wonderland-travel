import type { HTMLAttributes } from "react";
import { cn } from "@/lib/cn";

type BadgeVariant = "neutral" | "dark";

const variantClasses: Record<BadgeVariant, string> = {
  neutral: "bg-mist text-heading border border-border",
  dark: "bg-black text-white",
};

const dotClasses: Record<BadgeVariant, string> = {
  neutral: "bg-black",
  dark: "bg-white",
};

export interface BadgeProps extends HTMLAttributes<HTMLSpanElement> {
  variant?: BadgeVariant;
}

export function Badge({ variant = "neutral", className, children, ...props }: BadgeProps) {
  return (
    <span
      className={cn(
        "inline-flex items-center gap-2 rounded-full px-4 py-1.5 text-xs font-semibold uppercase tracking-wide",
        variantClasses[variant],
        className,
      )}
      {...props}
    >
      <span className={cn("size-1.5 rounded-full", dotClasses[variant])} aria-hidden="true" />
      {children}
    </span>
  );
}
