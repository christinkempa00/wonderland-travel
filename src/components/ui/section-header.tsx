import type { ReactNode } from "react";
import { Badge } from "./badge";
import { cn } from "@/lib/cn";

export interface SectionHeaderProps {
  badge: string;
  title: ReactNode;
  description?: ReactNode;
  align?: "left" | "center";
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
      <Badge>{badge}</Badge>
      <h2 className="max-w-2xl text-3xl font-bold text-heading sm:text-4xl">{title}</h2>
      {description && (
        <p className="max-w-xl text-base text-body">{description}</p>
      )}
    </div>
  );
}
