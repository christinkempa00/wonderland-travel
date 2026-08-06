import PDFDocument from "pdfkit";

export interface InvoicePdfItem {
  nama: string;
  qty: number;
  hargaSatuan: number;
}

export interface InvoicePdfData {
  nomorInvoice: string;
  createdAt: Date;
  jatuhTempo: Date;
  pelanggan: { nama: string; noHp: string; email?: string | null; kodeKlien: string };
  reservasi: { nomorBooking: string; jenis: string };
  items: InvoicePdfItem[];
  diskon: number;
  totalDibayar: number;
}

function formatIDR(value: number): string {
  return `Rp ${value.toLocaleString("id-ID")}`;
}

function formatTanggal(date: Date): string {
  return date.toLocaleDateString("id-ID", { day: "2-digit", month: "long", year: "numeric" });
}

export function generateInvoicePdf(data: InvoicePdfData): PDFKit.PDFDocument {
  const doc = new PDFDocument({ size: "A4", margin: 50 });

  const subtotal = data.items.reduce((sum, item) => sum + item.qty * item.hargaSatuan, 0);
  const total = subtotal - data.diskon;
  const sisa = Math.max(total - data.totalDibayar, 0);

  doc.fontSize(20).font("Helvetica-Bold").text("Wonderland Travel", { continued: false });
  doc.fontSize(10).font("Helvetica").fillColor("#666666").text("hello@wonderlandtravel.id");
  doc.moveDown(1.5);

  doc.fillColor("#000000").fontSize(16).font("Helvetica-Bold").text("INVOICE");
  doc.fontSize(10).font("Helvetica").text(`No. Invoice: ${data.nomorInvoice}`);
  doc.text(`Tanggal Terbit: ${formatTanggal(data.createdAt)}`);
  doc.text(`Jatuh Tempo: ${formatTanggal(data.jatuhTempo)}`);
  doc.moveDown(1);

  doc.font("Helvetica-Bold").text("Ditagihkan kepada:");
  doc.font("Helvetica").text(data.pelanggan.nama);
  doc.text(`Kode Klien: ${data.pelanggan.kodeKlien}`);
  doc.text(data.pelanggan.noHp);
  if (data.pelanggan.email) doc.text(data.pelanggan.email);
  doc.moveDown(0.5);
  doc.font("Helvetica-Bold").text("Reservasi:");
  doc
    .font("Helvetica")
    .text(`${data.reservasi.nomorBooking} — ${data.reservasi.jenis.replace(/_/g, " ")}`);
  doc.moveDown(1.5);

  const tableTop = doc.y;
  const col = { nama: 50, qty: 320, harga: 380, subtotal: 470 };

  doc.font("Helvetica-Bold").fontSize(10);
  doc.text("Item", col.nama, tableTop);
  doc.text("Qty", col.qty, tableTop);
  doc.text("Harga", col.harga, tableTop);
  doc.text("Subtotal", col.subtotal, tableTop);
  doc
    .moveTo(50, tableTop + 15)
    .lineTo(545, tableTop + 15)
    .strokeColor("#dddddd")
    .stroke();

  let y = tableTop + 22;
  doc.font("Helvetica").fontSize(10);
  for (const item of data.items) {
    doc.text(item.nama, col.nama, y, { width: 260 });
    doc.text(String(item.qty), col.qty, y);
    doc.text(formatIDR(item.hargaSatuan), col.harga, y);
    doc.text(formatIDR(item.qty * item.hargaSatuan), col.subtotal, y);
    y += 20;
  }

  y += 5;
  doc.moveTo(320, y).lineTo(545, y).strokeColor("#dddddd").stroke();
  y += 10;

  function summaryRow(label: string, value: string, bold = false) {
    doc.font(bold ? "Helvetica-Bold" : "Helvetica").fontSize(10);
    doc.text(label, col.harga, y);
    doc.text(value, col.subtotal, y);
    y += 18;
  }

  summaryRow("Subtotal", formatIDR(subtotal));
  if (data.diskon > 0) summaryRow("Diskon", `- ${formatIDR(data.diskon)}`);
  summaryRow("Total", formatIDR(total), true);
  summaryRow("Sudah Dibayar", formatIDR(data.totalDibayar));
  summaryRow("Sisa Tagihan", formatIDR(sisa), true);

  doc.moveDown(3);
  doc
    .fontSize(9)
    .fillColor("#999999")
    .text("Terima kasih telah memesan bersama Wonderland Travel.", 50, doc.y, {
      align: "center",
      width: 495,
    });

  return doc;
}
