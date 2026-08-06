import { useEffect, useState } from "react";
import type { FormEvent } from "react";
import { api, ApiError } from "../lib/api";
import type { CaptchaChallenge, CekTagihanResult } from "../lib/api";

export function SearchForm({ onResult }: { onResult: (result: CekTagihanResult) => void }) {
  const [idKlien, setIdKlien] = useState("");
  const [captcha, setCaptcha] = useState<CaptchaChallenge | null>(null);
  const [captchaAnswer, setCaptchaAnswer] = useState("");
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState<string | null>(null);

  async function loadCaptcha() {
    try {
      const challenge = await api.getCaptcha();
      setCaptcha(challenge);
      setCaptchaAnswer("");
    } catch {
      setError("Gagal memuat captcha. Muat ulang halaman dan coba lagi.");
    }
  }

  useEffect(() => {
    loadCaptcha();
  }, []);

  async function handleSubmit(event: FormEvent<HTMLFormElement>) {
    event.preventDefault();
    if (!captcha) return;
    setError(null);
    setLoading(true);
    try {
      const result = await api.cekTagihan(idKlien.trim(), captcha.token, captchaAnswer);
      onResult(result);
    } catch (err) {
      setError(err instanceof ApiError ? err.message : "Terjadi kesalahan. Coba lagi.");
      loadCaptcha();
    } finally {
      setLoading(false);
    }
  }

  return (
    <form
      onSubmit={handleSubmit}
      className="mt-10 flex w-full max-w-md flex-col gap-5 rounded-card border border-border bg-white p-8 shadow-soft"
    >
      <div className="flex flex-col gap-1.5">
        <label htmlFor="idKlien" className="text-sm font-medium text-heading">
          No. ID Klien
        </label>
        <input
          id="idKlien"
          required
          autoComplete="off"
          autoCapitalize="characters"
          value={idKlien}
          onChange={(e) => setIdKlien(e.target.value.toUpperCase())}
          placeholder="mis. WT-7K4M9XQP"
          className="rounded-control border border-border px-4 py-3 text-center font-mono text-lg tracking-widest text-heading placeholder:font-sans placeholder:text-base placeholder:tracking-normal placeholder:text-muted focus:border-black focus:outline-none focus:ring-2 focus:ring-black/10"
        />
        <p className="text-xs text-muted">
          Kode ini dikirim lewat WhatsApp/email saat reservasi Anda dibuat.
        </p>
      </div>

      <div className="flex flex-col gap-1.5">
        <label htmlFor="captchaAnswer" className="text-sm font-medium text-heading">
          Verifikasi: berapa {captcha ? captcha.question : "..."}?
        </label>
        <div className="flex gap-2">
          <input
            id="captchaAnswer"
            required
            type="number"
            value={captchaAnswer}
            onChange={(e) => setCaptchaAnswer(e.target.value)}
            className="w-full rounded-control border border-border px-4 py-3 text-heading focus:border-black focus:outline-none focus:ring-2 focus:ring-black/10"
          />
          <button
            type="button"
            onClick={loadCaptcha}
            aria-label="Ganti soal verifikasi"
            title="Ganti soal"
            className="shrink-0 rounded-control border border-border px-4 text-sm font-semibold text-muted transition-colors hover:bg-mist hover:text-heading"
          >
            ↻
          </button>
        </div>
      </div>

      {error && <p className="text-sm text-red-600">{error}</p>}

      <button
        type="submit"
        disabled={loading || !captcha}
        className="rounded-full bg-black px-5 py-3 text-sm font-semibold text-white transition-colors hover:bg-black/85 disabled:cursor-not-allowed disabled:opacity-50"
      >
        {loading ? "Memeriksa..." : "Cek Tagihan"}
      </button>
    </form>
  );
}
