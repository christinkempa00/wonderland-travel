import { forwardRef, useId } from "react";
import type { TextareaHTMLAttributes } from "react";
import { cn } from "@/lib/cn";

export interface TextareaProps extends TextareaHTMLAttributes<HTMLTextAreaElement> {
  label?: string;
}

export const Textarea = forwardRef<HTMLTextAreaElement, TextareaProps>(
  ({ label, id, className, rows = 4, ...props }, ref) => {
    const generatedId = useId();
    const textareaId = id ?? generatedId;

    return (
      <div className="flex flex-col gap-1.5">
        {label && (
          <label htmlFor={textareaId} className="text-sm font-medium text-heading">
            {label}
          </label>
        )}
        <textarea
          ref={ref}
          id={textareaId}
          rows={rows}
          className={cn(
            "rounded-control border border-border bg-white px-4 py-2.5 text-sm text-heading",
            "placeholder:text-muted",
            "transition-colors duration-150 resize-none",
            "focus:outline-none focus:border-black focus:ring-2 focus:ring-black/10",
            "disabled:cursor-not-allowed disabled:opacity-50",
            className,
          )}
          {...props}
        />
      </div>
    );
  },
);

Textarea.displayName = "Textarea";
