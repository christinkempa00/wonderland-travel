import type { HTMLAttributes } from "react";

export function Accent({ className = "", ...props }: HTMLAttributes<HTMLSpanElement>) {
  return <span className={`text-accent ${className}`} {...props} />;
}
