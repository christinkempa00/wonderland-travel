"use client";

import { useState } from "react";
import { cn } from "@/lib/cn";

const LANGUAGES = ["ID", "EN"] as const;

export interface LanguageToggleProps {
  variant?: "light" | "dark";
}

export function LanguageToggle({ variant = "light" }: LanguageToggleProps) {
  const [lang, setLang] = useState<(typeof LANGUAGES)[number]>("ID");

  return (
    <div
      className={cn(
        "inline-flex items-center rounded-full p-1 text-xs font-semibold",
        variant === "light" ? "border border-border bg-mist" : "border border-white/20 bg-white/5",
      )}
    >
      {LANGUAGES.map((option) => (
        <button
          key={option}
          type="button"
          onClick={() => setLang(option)}
          aria-pressed={lang === option}
          className={cn(
            "rounded-full px-3 py-1 transition-colors",
            lang === option
              ? variant === "light"
                ? "bg-black text-white"
                : "bg-white text-black"
              : variant === "light"
                ? "text-muted hover:text-heading"
                : "text-white/60 hover:text-white",
          )}
        >
          {option}
        </button>
      ))}
    </div>
  );
}
