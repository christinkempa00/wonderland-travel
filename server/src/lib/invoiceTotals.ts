export interface InvoiceLike {
  diskon: number;
  items: { qty: number; hargaSatuan: number }[];
  pembayaran: { jumlah: number }[];
}

export function computeInvoiceTotals(invoice: InvoiceLike) {
  const subtotal = invoice.items.reduce((sum, item) => sum + item.qty * item.hargaSatuan, 0);
  const total = subtotal - invoice.diskon;
  const totalDibayar = invoice.pembayaran.reduce((sum, p) => sum + p.jumlah, 0);
  const sisaTagihan = Math.max(total - totalDibayar, 0);

  return { subtotal, total, totalDibayar, sisaTagihan };
}
