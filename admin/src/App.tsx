import { Route, Routes } from "react-router-dom";
import { ProtectedRoute } from "./components/ProtectedRoute";
import { AdminLayout } from "./layouts/AdminLayout";
import { Dashboard } from "./pages/Dashboard";
import { Login } from "./pages/Login";
import { InvoiceDetail } from "./pages/Invoice/Detail";
import { InvoiceList } from "./pages/Invoice/List";
import { LaporanPendapatanPage } from "./pages/Laporan/Pendapatan";
import { PaketWisataForm } from "./pages/PaketWisata/Form";
import { PaketWisataList } from "./pages/PaketWisata/List";
import { PelangganDetail } from "./pages/Pelanggan/Detail";
import { PelangganList } from "./pages/Pelanggan/List";
import { ReservasiBoard } from "./pages/Reservasi/Board";
import { ReservasiDetail } from "./pages/Reservasi/Detail";

export default function App() {
  return (
    <Routes>
      <Route path="/login" element={<Login />} />
      <Route
        element={
          <ProtectedRoute>
            <AdminLayout />
          </ProtectedRoute>
        }
      >
        <Route path="/" element={<Dashboard />} />
        <Route path="/paket-wisata" element={<PaketWisataList />} />
        <Route path="/paket-wisata/baru" element={<PaketWisataForm />} />
        <Route path="/paket-wisata/:id" element={<PaketWisataForm />} />
        <Route path="/reservasi" element={<ReservasiBoard />} />
        <Route path="/reservasi/:id" element={<ReservasiDetail />} />
        <Route path="/invoice" element={<InvoiceList />} />
        <Route path="/invoice/:id" element={<InvoiceDetail />} />
        <Route path="/pelanggan" element={<PelangganList />} />
        <Route path="/pelanggan/:id" element={<PelangganDetail />} />
        <Route path="/laporan" element={<LaporanPendapatanPage />} />
      </Route>
    </Routes>
  );
}
