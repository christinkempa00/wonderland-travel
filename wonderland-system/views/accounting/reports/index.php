<?php
/**
 * Reports Index - Menu Laporan Keuangan
 * File: /views/accounting/reports/index.php
 */
?>

<div class="row g-4">
    <!-- Neraca Saldo -->
    <div class="col-md-6 col-lg-4">
        <div class="glass-card h-100">
            <div class="d-flex align-items-center mb-3">
                <div class="icon-box bg-primary bg-opacity-10 text-primary me-3">
                    <i class="fas fa-balance-scale fa-lg"></i>
                </div>
                <div>
                    <h5 class="mb-1">Neraca Saldo</h5>
                    <small class="text-muted">Trial Balance</small>
                </div>
            </div>
            <p class="text-muted small mb-3">
                Daftar semua akun dengan saldo debit dan kredit. Digunakan untuk memverifikasi keseimbangan buku besar.
            </p>
            <a href="<?= url('/accounting/reports/trial-balance') ?>" class="btn btn-primary btn-sm">
                <i class="fas fa-eye me-1"></i> Lihat Laporan
            </a>
        </div>
    </div>
    
    <!-- Laba Rugi -->
    <div class="col-md-6 col-lg-4">
        <div class="glass-card h-100">
            <div class="d-flex align-items-center mb-3">
                <div class="icon-box bg-success bg-opacity-10 text-success me-3">
                    <i class="fas fa-chart-line fa-lg"></i>
                </div>
                <div>
                    <h5 class="mb-1">Laba Rugi</h5>
                    <small class="text-muted">Income Statement</small>
                </div>
            </div>
            <p class="text-muted small mb-3">
                Menampilkan pendapatan dan beban untuk menghitung laba atau rugi dalam periode tertentu.
            </p>
            <a href="<?= url('/accounting/reports/income-statement') ?>" class="btn btn-success btn-sm">
                <i class="fas fa-eye me-1"></i> Lihat Laporan
            </a>
        </div>
    </div>
    
    <!-- Neraca -->
    <div class="col-md-6 col-lg-4">
        <div class="glass-card h-100">
            <div class="d-flex align-items-center mb-3">
                <div class="icon-box bg-info bg-opacity-10 text-info me-3">
                    <i class="fas fa-file-invoice-dollar fa-lg"></i>
                </div>
                <div>
                    <h5 class="mb-1">Neraca</h5>
                    <small class="text-muted">Balance Sheet</small>
                </div>
            </div>
            <p class="text-muted small mb-3">
                Laporan posisi keuangan: Aset, Kewajiban, dan Ekuitas pada tanggal tertentu.
            </p>
            <a href="<?= url('/accounting/reports/balance-sheet') ?>" class="btn btn-info btn-sm">
                <i class="fas fa-eye me-1"></i> Lihat Laporan
            </a>
        </div>
    </div>
    
    <!-- Buku Besar -->
    <div class="col-md-6 col-lg-4">
        <div class="glass-card h-100">
            <div class="d-flex align-items-center mb-3">
                <div class="icon-box bg-warning bg-opacity-10 text-warning me-3">
                    <i class="fas fa-book fa-lg"></i>
                </div>
                <div>
                    <h5 class="mb-1">Buku Besar</h5>
                    <small class="text-muted">General Ledger</small>
                </div>
            </div>
            <p class="text-muted small mb-3">
                Detail transaksi per akun dengan saldo berjalan. Untuk melihat riwayat mutasi setiap akun.
            </p>
            <a href="<?= url('/accounting/ledger') ?>" class="btn btn-warning btn-sm">
                <i class="fas fa-eye me-1"></i> Lihat Laporan
            </a>
        </div>
    </div>
    
    <!-- Laporan Piutang -->
    <div class="col-md-6 col-lg-4">
        <div class="glass-card h-100">
            <div class="d-flex align-items-center mb-3">
                <div class="icon-box bg-danger bg-opacity-10 text-danger me-3">
                    <i class="fas fa-hand-holding-usd fa-lg"></i>
                </div>
                <div>
                    <h5 class="mb-1">Laporan Piutang</h5>
                    <small class="text-muted">Receivables Report</small>
                </div>
            </div>
            <p class="text-muted small mb-3">
                Daftar piutang dari order yang belum lunas, termasuk aging analysis.
            </p>
            <a href="<?= url('/accounting/reports/receivables') ?>" class="btn btn-danger btn-sm">
                <i class="fas fa-eye me-1"></i> Lihat Laporan
            </a>
        </div>
    </div>
    
    <!-- Arus Kas -->
    <div class="col-md-6 col-lg-4">
        <div class="glass-card h-100">
            <div class="d-flex align-items-center mb-3">
                <div class="icon-box bg-secondary bg-opacity-10 text-secondary me-3">
                    <i class="fas fa-money-bill-wave fa-lg"></i>
                </div>
                <div>
                    <h5 class="mb-1">Arus Kas</h5>
                    <small class="text-muted">Cash Flow</small>
                </div>
            </div>
            <p class="text-muted small mb-3">
                Mutasi kas dan bank: pemasukan, pengeluaran, dan saldo per periode.
            </p>
            <a href="<?= url('/accounting/reports/cash-flow') ?>" class="btn btn-secondary btn-sm">
                <i class="fas fa-eye me-1"></i> Lihat Laporan
            </a>
        </div>
    </div>
</div>

<style>
.icon-box {
    width: 50px;
    height: 50px;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
}
</style>