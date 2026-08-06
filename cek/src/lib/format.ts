export function formatIDR(value: number): string {
  return `Rp ${value.toLocaleString("id-ID")}`;
}

export function formatDate(iso: string): string {
  return new Date(iso).toLocaleDateString("id-ID", {
    day: "2-digit",
    month: "long",
    year: "numeric",
  });
}
