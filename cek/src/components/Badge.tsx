import type { HTMLAttributes } from "react";

export function Badge({ className = "", children, ...props }: HTMLAttributes<HTMLSpanElement>) {
  return (
    <span
      className={`inline-flex items-center gap-2 rounded-full border border-border bg-mist px-4 py-1.5 text-xs font-semibold uppercase tracking-wide text-heading ${className}`}
      {...props}
    >
      <span className="size-1.5 rounded-full bg-black" aria-hidden="true" />
      {children}
    </span>
  );
}
