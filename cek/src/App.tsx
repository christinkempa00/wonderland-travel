import { useState } from "react";
import { Accent } from "./components/Accent";
import { Badge } from "./components/Badge";
import { ResultView } from "./components/ResultView";
import { SearchForm } from "./components/SearchForm";
import type { CekTagihanResult } from "./lib/api";

export default function App() {
  const [result, setResult] = useState<CekTagihanResult | null>(null);

  return (
    <div className="flex min-h-screen flex-col bg-white">
      <header className="border-b border-border px-6 py-5">
        <span className="text-lg font-bold text-heading">
          <span className="text-accent text-xl">Wonderland</span> Travel
        </span>
      </header>

      <main className="flex flex-1 flex-col items-center px-6 py-16 sm:py-24">
        <Badge>Portal Klien</Badge>
        <h1 className="mt-4 max-w-lg text-center text-4xl font-bold text-heading sm:text-5xl">
          Cek <Accent>Tagihan</Accent> Anda
        </h1>
        <p className="mt-3 max-w-md text-center text-base text-body">
          Masukkan No. ID Klien yang Anda terima saat reservasi untuk melihat status pesanan dan
          rincian tagihan — tanpa perlu login.
        </p>

        {result ? (
          <ResultView result={result} onReset={() => setResult(null)} />
        ) : (
          <SearchForm onResult={setResult} />
        )}
      </main>

      <footer className="border-t border-border px-6 py-6 text-center text-xs text-muted">
        &copy; {new Date().getFullYear()} Wonderland Travel. Halaman ini bersifat read-only.
      </footer>
    </div>
  );
}
