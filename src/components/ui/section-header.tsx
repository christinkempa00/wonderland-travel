import type { ReactNode } from "react";
import { cn } from "@/lib/cn";

export interface SectionHeaderProps {
  title: ReactNode;
  description?: ReactNode;
  align?: "left" | "center";
  /** Use "dark" on black/photo backgrounds to flip text to light colors. */
  tone?: "light" | "dark";
  className?: string;
}

/**
 * Recurring section pattern used across the site:
 * two-font headline (use <Accent> for the serif italic keywords) -> short description.
 */
export function SectionHeader({
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
