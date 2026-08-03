import type { ReactNode } from "react";
import { Badge } from "./badge";
import { cn } from "@/lib/cn";

export interface SectionHeaderProps {
  badge: string;
  title: ReactNode;
  description?: ReactNode;
  align?: "left" | "center";
  /** Use "dark" on black/photo backgrounds to flip badge + text to light colors. */
  tone?: "light" | "dark";
  className?: string;
}

/**
 * Recurring section pattern used across the site:
 * badge pill -> two-font headline (use <Accent> for the serif italic keywords) -> short description.
 */
export function SectionHeader({
  badge,
  title,
  description,
  align = "center",
  tone = "light",
  className,
}: SectionHeaderProps) {
  return (
    <div
      className={cn(
        "flex flex-col gap-4",
        align === "center" ? "items-center text-center" : "items-start text-left",
        className,
      )}
    >
      <Badge variant={tone === "dark" ? "dark" : "neutral"}>{badge}</Badge>
      <h2
        className={cn(
          "max-w-2xl text-3xl font-bold sm:text-4xl",
          tone === "dark" ? "text-white" : "text-heading",
        )}
      >
        {title}
      </h2>
      {description && (
        <p className={cn("max-w-xl text-base", tone === "dark" ? "text-white/70" : "text-body")}>
          {description}
        </p>
      )}
    </div>
  );
}
