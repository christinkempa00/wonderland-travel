import type { CekTagihanResult, ReservasiRingkas } from "../lib/api";
import { api } from "../lib/api";
import { formatDate, formatIDR } from "../lib/format";
import { JENIS_LABEL, STATUS_LABEL } from "../lib/labels";

const STATUS_TONE: Record<ReservasiRingkas["status"], string> = {
  BARU_MASUK: "bg-mist text-heading",
  DIKONFIRMASI: "bg-mist text-heading",
  MENUNGGU_PEMBAYARAN: "bg-amber-100 text-amber-800",
  DIBAYAR_SEBAGIAN: "bg-amber-100 text-amber-800",
  LUNAS: "bg-green-100 text-green-800",
  SELESAI: "bg-black text-white",
  DIBATALKAN: "bg-red-100 text-red-700",
};

function ringkasanText(itemRingkasan: unknown): string | null {
  if (
    typeof itemRingkasan === "object" &&
    itemRingkasan !== null &&
    "deskripsi" in itemRingkasan
  ) {
    const value = (itemRingkasan as { deskripsi?: unknown }).deskripsi;
    return typeof value === "string" && value.trim() ? value : null;
  }
  return null;
}

function ReservasiCard({ reservasi, idKlien }: { reservasi: ReservasiRingkas; idKlien: string }) {
  const ringkasan = ringkasanText(reservasi.itemRingkasan);

  return (
    <div className="rounded-card border border-border bg-white p-6 shadow-soft">
      <div className="flex flex-wrap items-start justify-between gap-3">
        <div>
          <p className="font-mono text-xs text-muted">{reservasi.nomorBooking}</p>
          <p className="text-lg font-bold text-heading">{JENIS_LABEL[reservasi.jenis]}</p>
        </div>
        <span
          className={`rounded-full px-3 py-1 text-xs font-semibold ${STATUS_TONE[reservasi.status]}`}
        >
          {STATUS_LABEL[reservasi.status]}
        </span>
      </div>

      <p className="mt-2 text-sm text-body">
        {formatDate(reservasi.tanggalMulai)}
        {reservasi.tanggalSelesai && ` – ${formatDate(reservasi.tanggalSelesai)}`}
      </p>
      {ringkasan && <p className="mt-1 text-sm text-muted">{ringkasan}</p>}

      {reservasi.invoice && (
        <div className="mt-4 border-t border-border pt-4">
          <table className="w-full text-left text-sm">
            <tbody>
              {reservasi.invoice.items.map((item, index) => (
                <tr key={index} className="border-b border-border last:border-b-0">
                  <td className="py-2 text-heading">
                    {item.nama} <span className="text-muted">× {item.qty}</span>
                  </td>
                  <td className="py-2 text-right text-body">
                    {formatIDR(item.qty * item.hargaSatuan)}
                  </td>
                </tr>
              ))}
            </tbody>
          </table>

          <div className="mt-3 flex flex-col gap-1 text-sm">
            <div className="flex justify-between text-body">
              <span>Total Tagihan</span>
              <span className="font-semibold text-heading">{formatIDR(reservasi.invoice.total)}</span>
            </div>
            <div className="flex justify-between text-body">
              <span>Sudah Dibayar</span>
              <span>{formatIDR(reservasi.invoice.totalDibayar)}</span>
            </div>
            <div className="flex justify-between font-semibold text-heading">
              <span>Sisa Tagihan</span>
              <span>{formatIDR(reservasi.invoice.sisaTagihan)}</span>
            </div>
            <div className="flex justify-between text-xs text-muted">
              <span>Jatuh Tempo</span>
              <span>{formatDate(reservasi.invoice.jatuhTempo)}</span>
            </div>
          </div>

          <a
            href={api.invoicePdfUrl(idKlien, reservasi.invoice.id)}
            target="_blank"
            rel="noopener noreferrer"
            className="mt-4 inline-flex items-center justify-center rounded-full bg-black px-5 py-2.5 text-sm font-semibold text-white transition-colors hover:bg-black/85"
          >
            Download Invoice PDF
          </a>
        </div>
      )}
    </div>
  );
}

export function ResultView({
  result,
  onReset,
}: {
  result: CekTagihanResult;
  onReset: () => void;
}) {
  return (
    <div className="mt-10 flex w-full max-w-2xl flex-col gap-6">
      <div className="flex items-center justify-between rounded-card border border-border bg-white p-6 shadow-soft">
        <div>
          <p className="text-sm text-muted">Halo,</p>
          <p className="text-xl font-bold text-heading">{result.namaDepan}</p>
        </div>
        <button
          type="button"
          onClick={onReset}
          className="rounded-full border border-border px-4 py-2 text-sm font-semibold text-heading transition-colors hover:bg-mist"
        >
          Cek ID Lain
        </button>
      </div>

      {result.reservasi.length === 0 ? (
        <p className="rounded-card border border-dashed border-border bg-white p-8 text-center text-sm text-muted">
          Belum ada reservasi tercatat untuk ID ini.
        </p>
      ) : (
        result.reservasi.map((r) => (
          <ReservasiCard key={r.id} reservasi={r} idKlien={result.kodeKlien} />
        ))
      )}
    </div>
  );
}
