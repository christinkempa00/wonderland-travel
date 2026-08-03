"use client";

import { useEffect } from "react";
import { createPortal } from "react-dom";
import { X } from "lucide-react";
import { cn } from "@/lib/cn";

export interface ModalProps {
  open: boolean;
  onClose: () => void;
  title?: string;
  className?: string;
  children: React.ReactNode;
}

export function Modal({ open, onClose, title, className, children }: ModalProps) {
  useEffect(() => {
    if (!open) return;

    const handleKeyDown = (event: KeyboardEvent) => {
      if (event.key === "Escape") onClose();
    };
    document.addEventListener("keydown", handleKeyDown);
    document.body.style.overflow = "hidden";

    return () => {
      document.removeEventListener("keydown", handleKeyDown);
      document.body.style.overflow = "";
    };
  }, [open, onClose]);

  if (!open) return null;

  return createPortal(
    <div
      role="dialog"
      aria-modal="true"
      aria-label={title}
      className="fixed inset-0 z-50 flex items-center justify-center p-4"
    >
      <div
        className="absolute inset-0 bg-black/50 backdrop-blur-sm"
        onClick={onClose}
        aria-hidden="true"
      />
      <div
        className={cn(
          "relative w-full max-w-lg rounded-card bg-white p-8 shadow-soft-lift",
          className,
        )}
      >
        <button
          type="button"
          onClick={onClose}
          aria-label="Tutup"
          className={cn(
            "absolute right-5 top-5 flex size-9 items-center justify-center rounded-full",
            "text-muted transition-colors hover:bg-mist hover:text-heading",
            "focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-black/20",
          )}
        >
          <X className="size-5" />
        </button>
        {title && <h2 className="pr-10 text-xl font-bold text-heading">{title}</h2>}
        <div className={title ? "mt-4" : undefined}>{children}</div>
      </div>
    </div>,
    document.body,
  );
}
