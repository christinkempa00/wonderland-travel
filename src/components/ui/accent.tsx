import type { HTMLAttributes } from "react";
import { cn } from "@/lib/cn";

/** Wraps the 1-3 keyword accent inside a headline in the serif italic brand font. */
export function Accent({ className, ...props }: HTMLAttributes<HTMLSpanElement>) {
  return <span className={cn("text-accent", className)} {...props} />;
}
