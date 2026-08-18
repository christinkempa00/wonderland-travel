<?php
/**
 * Financial Dashboard View
 * File: /views/accounting/dashboard.php
 */

$dashboard = $dashboard ?? [];
$cashAccounts = $dashboard['cash_accounts'] ?? [];
?>

<!-- Stats Cards -->
<div class="row mb-4">
    <div class="col-6 col-xl-3 stagger-item">
        <div class="stat-card">
            <div class="stat-icon green">
                <i class="fas fa-wallet"></i>
            </div>
            <div class="stat-content">
                <div class="stat-label">Total Saldo Kas/Bank</div>
                <div class="stat-value"><?= formatRupiah($dashboard['cash_balance'] ?? 0) ?></div>
                <small class="text-muted"><?= count($cashAccounts) ?> akun aktif</small>
            </div>
        </div>
    </div>
    
    <div class="col-6 col-xl-3 stagger-item">
        <div class="stat-card">
            <div class="stat-icon blue">
                <i class="fas fa-file-invoice-dollar"></i>
            </div>
            <div class="stat-content">
                <div class="stat-label">Piutang Usaha</div>
                <div class="stat-value"><?= formatRupiah($dashboard['receivables'] ?? 0) ?></div>
                <small class="text-muted">Belum dibayar customer</small>
            </div>
        </div>
    </div>
    
    <div class="col-6 col-xl-3 stagger-item">
        <div class="stat-card">
            <div class="stat-icon red">
                <i class="fas fa-hand-holding-usd"></i>
            </div>
            <div class="stat-content">
                <div class="stat-label">Hutang Pinjaman</div>
                <div class="stat-value"><?= formatRupiah($dashboard['payables_loan'] ?? 0) ?></div>
                <small class="text-muted">Pinjaman perorangan</small>
            </div>
        </div>
    </div>
    
    <div class="col-6 col-xl-3 stagger-item">
        <div class="stat-card">
            <div class="stat-icon yellow">
                <i class="fas fa-exclamation-triangle"></i>
            </div>
            <div class="stat-content">
                <div class="stat-label">Belum Bayar Vendor</div>
                <div class="stat-value"><?= formatRupiah($dashboard['pending_expenses'] ?? 0) ?></div>
                <small class="text-muted">Modal belum keluar</small>
            </div>
        </div>
    </div>
</div>

<div class="row mb-4">
    <!-- Kas/Bank Detail -->
    <div class="col-lg-6 stagger-item">
        <div class="glass-card h-100">
            <div class="card-header">
                <h3 class="card-title">
                    <i class="fas fa-university text-primary me-2"></i>Saldo Kas & Bank
                </h3>
                <a href="<?= url('/accounting/bank-cash') ?>" class="btn btn-sm btn-outline-primary">
                    Kelola
                </a>
            </div>
            
            <?php if (empty($cashAccounts)): ?>
            <div class="empty-state">
                <div class="empty-state-icon">
                    <i class="fas fa-piggy-bank"></i>
                </div>
                <h4 class="empty-state-title">Belum Ada Kas/Bank</h4>
                <p class="empty-state-text">Tambahkan akun kas atau bank untuk mulai mencatat transaksi.</p>
                <a href="<?= url('/accounting/bank-cash/create') ?>" class="btn btn-primary">
                    <i class="fas fa-plus"></i> Tambah Kas/Bank
                </a>
            </div>
            <?php else: ?>
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Akun</th>
                            <th>Tipe</th>
                            <th class="text-end">Saldo</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($cashAccounts as $acc): ?>
                        <tr>
                            <td>
                                <i class="fas fa-<?= ($acc['type'] ?? 'cash') === 'cash' ? 'money-bill-wave text-success' : 'building text-primary' ?> me-2"></i>
                                <?= e($acc['name'] ?? '') ?>
                            </td>
                            <td>
                                <span class="badge badge-<?= ($acc['type'] ?? 'cash') === 'cash' ? 'success' : 'primary' ?>">
                                    <?= ($acc['type'] ?? 'cash') === 'cash' ? 'Kas' : 'Bank' ?>
                                </span>
                            </td>
                            <td class="text-end fw-bold <?= ($acc['balance'] ?? 0) >= 0 ? 'text-success' : 'text-danger' ?>">
                                <?= formatRupiah($acc['balance'] ?? 0) ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                    <tfoot>
                        <tr class="table-light">
                            <th colspan="2">Total</th>
                            <th class="text-end text-success"><?= formatRupiah($dashboard['cash_balance'] ?? 0) ?></th>
                        </tr>
                    </tfoot>
                </table>
            </div>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- Performa Bulan Ini -->
    <div class="col-lg-6 stagger-item">
        <div class="glass-card h-100">
            <div class="card-header">
                <h3 class="card-title">
                    <i class="fas fa-chart-line text-primary me-2"></i>Performa Bulan Ini
                </h3>
                <span class="text-muted"><?= date('F Y') ?></span>
            </div>
            
            <div class="summary-list">
                <div class="summary-item">
                    <div class="summary-icon" style="background: #dbeafe; color: #2563eb;">
                        <i class="fas fa-arrow-up"></i>
                    </div>
                    <div class="summary-info">
                        <div class="summary-label">Pendapatan</div>
                        <div class="summary-value"><?= formatRupiah($dashboard['revenue_this_month'] ?? 0) ?></div>
                    </div>
                </div>
                
                <div class="summary-item">
                    <div class="summary-icon" style="background: #fee2e2; color: #dc2626;">
                        <i class="fas fa-arrow-down"></i>
                    </div>
                    <div class="summary-info">
                        <div class="summary-label">Pengeluaran</div>
                        <div class="summary-value"><?= formatRupiah($dashboard['expense_this_month'] ?? 0) ?></div>
                    </div>
                </div>
                
                <div class="summary-item">
                    <div class="summary-icon" style="background: #d1fae5; color: #059669;">
                        <i class="fas fa-chart-line"></i>
                    </div>
                    <div class="summary-info">
                        <div class="summary-label">Profit</div>
                        <div class="summary-value"><?= formatRupiah($dashboard['profit_this_month'] ?? 0) ?></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Quick Actions -->
<div class="row">
    <div class="col-12 stagger-item">
        <div class="glass-card">
            <div class="card-header">
                <h3 class="card-title">
                    <i class="fas fa-bolt text-warning me-2"></i>Aksi Cepat
                </h3>
            </div>
            
            <div class="row g-3">
                <div class="col-6 col-md-3">
                    <a href="<?= url('/loans/receive') ?>" class="btn btn-outline-success w-100 py-3">
                        <i class="fas fa-plus-circle d-block mb-2 fa-2x"></i>
                        Terima Pinjaman
                    </a>
                </div>
                <div class="col-6 col-md-3">
                    <a href="<?= url('/loans/repay') ?>" class="btn btn-outline-warning w-100 py-3">
                        <i class="fas fa-money-bill-wave d-block mb-2 fa-2x"></i>
                        Bayar Pinjaman
                    </a>
                </div>
                <div class="col-6 col-md-3">
                    <a href="<?= url('/analysis') ?>" class="btn btn-outline-info w-100 py-3">
                        <i class="fas fa-chart-bar d-block mb-2 fa-2x"></i>
                        Analisis Order
                    </a>
                </div>
                <div class="col-6 col-md-3">
                    <a href="<?= url('/accounting/journals') ?>" class="btn btn-outline-primary w-100 py-3">
                        <i class="fas fa-book d-block mb-2 fa-2x"></i>
                        Jurnal Umum
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.summary-list {
    display: flex;
    flex-direction: column;
    gap: 1rem;
}

.summary-item {
    display: flex;
    align-items: center;
    gap: 1rem;
    padding: 1rem;
    background: var(--gray-50);
    border-radius: var(--border-radius-sm);
}

.summary-icon {
    width: 48px;
    height: 48px;
    border-radius: var(--border-radius-sm);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.25rem;
}

.summary-info {
    flex: 1;
}

.summary-label {
    font-size: var(--font-size-sm);
    color: var(--gray-500);
}

.summary-value {
    font-size: var(--font-size-xl);
    font-weight: 600;
    color: var(--gray-800);
}
</style>