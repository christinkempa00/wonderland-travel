import { forwardRef } from "react";
import type { AnchorHTMLAttributes, ButtonHTMLAttributes } from "react";
import { cn } from "@/lib/cn";

type ButtonVariant = "primary" | "outline" | "inverse";
type ButtonSize = "sm" | "md" | "lg";

const baseClasses =
  "inline-flex items-center justify-center gap-2 rounded-full font-semibold " +
  "transition-all duration-200 ease-out disabled:opacity-50 disabled:pointer-events-none " +
  "focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-black/30 focus-visible:ring-offset-2";

const sizeClasses: Record<ButtonSize, string> = {
  sm: "min-h-11 text-sm px-4 py-2",
  md: "min-h-11 text-sm px-6 py-3",
  lg: "min-h-12 text-base px-8 py-4",
};

const variantClasses: Record<ButtonVariant, string> = {
  primary: "bg-black text-white shadow-soft hover:bg-black/85 hover:shadow-soft-lift hover:-translate-y-0.5",
  outline:
    "bg-transparent text-black border border-black hover:bg-black/5 hover:-translate-y-0.5",
  inverse: "bg-white text-black shadow-soft hover:bg-white/90 hover:-translate-y-0.5",
};

interface ButtonOwnProps {
  variant?: ButtonVariant;
  size?: ButtonSize;
}

type ButtonAsButton = ButtonOwnProps &
  ButtonHTMLAttributes<HTMLButtonElement> & { href?: undefined };

type ButtonAsAnchor = ButtonOwnProps &
  AnchorHTMLAttributes<HTMLAnchorElement> & { href: string };

export type ButtonProps = ButtonAsButton | ButtonAsAnchor;

export const Button = forwardRef<HTMLButtonElement | HTMLAnchorElement, ButtonProps>(
  ({ variant = "primary", size = "md", className, ...props }, ref) => {
    const classes = cn(baseClasses, sizeClasses[size], variantClasses[variant], className);

    if (props.href !== undefined) {
      const { href, ...anchorProps } = props as ButtonAsAnchor;
      return (
        <a
          ref={ref as React.Ref<HTMLAnchorElement>}
          href={href}
          className={classes}
          {...anchorProps}
        />
      );
    }

    const { ...buttonProps } = props as ButtonAsButton;
    return (
      <button
        ref={ref as React.Ref<HTMLButtonElement>}
        className={classes}
        {...buttonProps}
      />
    );
  },
);

Button.displayName = "Button";
