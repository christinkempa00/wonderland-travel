import type { SVGProps } from "react";

type IconProps = SVGProps<SVGSVGElement>;

const base = {
  viewBox: "0 0 24 24",
  fill: "none",
  stroke: "currentColor",
  strokeWidth: 1.6,
  strokeLinecap: "round" as const,
  strokeLinejoin: "round" as const,
};

export function FacebookIcon(props: IconProps) {
  return (
    <svg {...base} {...props}>
      <path d="M14 21v-7.2h2.4l.4-3H14V8.8c0-.87.24-1.5 1.53-1.5H17V4.62c-.3-.04-1.3-.12-2.4-.12-2.4 0-4.1 1.46-4.1 4.15v2.15H8v3h2.5V21Z" />
    </svg>
  );
}

export function InstagramIcon(props: IconProps) {
  return (
    <svg {...base} {...props}>
      <rect x="3.5" y="3.5" width="17" height="17" rx="5" />
      <circle cx="12" cy="12" r="4" />
      <circle cx="17.2" cy="6.8" r="0.6" fill="currentColor" stroke="none" />
    </svg>
  );
}

export function YoutubeIcon(props: IconProps) {
  return (
    <svg {...base} {...props}>
      <rect x="2.5" y="6" width="19" height="12" rx="4" />
      <path d="M10.3 9.6v4.8l4.4-2.4Z" fill="currentColor" stroke="none" />
    </svg>
  );
}

export function TiktokIcon(props: IconProps) {
  return (
    <svg {...base} {...props}>
      <path d="M14.7 3v11a3.3 3.3 0 1 1-3.3-3.3c.15 0 .3 0 .45.02" />
      <path d="M14.7 3.2c.35 2.5 1.95 4.3 4.4 4.55" />
    </svg>
  );
}
