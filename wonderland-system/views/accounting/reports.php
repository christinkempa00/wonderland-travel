<?php
/**
 * Reports Index View
 */
?>

<div class="row">
    <div class="col-4">
        <a href="<?= url('/accounting/reports/trial-balance') ?>" class="report-card">
            <div class="report-icon blue">
                <i class="fas fa-balance-scale"></i>
            </div>
            <div class="report-content">
                <h4>Neraca Saldo</h4>
                <p>Trial Balance - Daftar saldo semua akun</p>
            </div>
            <i class="fas fa-chevron-right report-arrow"></i>
        </a>
    </div>
    
    <div class="col-4">
        <a href="<?= url('/accounting/reports/income-statement') ?>" class="report-card">
            <div class="report-icon green">
                <i class="fas fa-chart-line"></i>
            </div>
            <div class="report-content">
                <h4>Laporan Laba Rugi</h4>
                <p>Income Statement - Pendapatan dan beban</p>
            </div>
            <i class="fas fa-chevron-right report-arrow"></i>
        </a>
    </div>
    
    <div class="col-4">
        <a href="<?= url('/accounting/reports/balance-sheet') ?>" class="report-card">
            <div class="report-icon purple">
                <i class="fas fa-file-invoice-dollar"></i>
            </div>
            <div class="report-content">
                <h4>Neraca</h4>
                <p>Balance Sheet - Posisi keuangan</p>
            </div>
            <i class="fas fa-chevron-right report-arrow"></i>
        </a>
    </div>
</div>

<style>
.report-card {
    display: flex;
    align-items: center;
    gap: 1rem;
    padding: 1.5rem;
    background: rgba(255, 255, 255, 0.7);
    backdrop-filter: blur(10px);
    border: 1px solid rgba(255, 255, 255, 0.3);
    border-radius: var(--border-radius-lg);
    box-shadow: var(--shadow-glass);
    text-decoration: none;
    transition: all 0.3s ease;
}

.report-card:hover {
    transform: translateY(-4px);
    box-shadow: var(--shadow-glass-hover);
}

.report-icon {
    width: 60px;
    height: 60px;
    border-radius: var(--border-radius);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.5rem;
}

.report-icon.blue {
    background: linear-gradient(135deg, var(--primary-500), var(--primary-600));
    color: white;
}

.report-icon.green {
    background: linear-gradient(135deg, var(--success), #059669);
    color: white;
}

.report-icon.purple {
    background: linear-gradient(135deg, #8b5cf6, #7c3aed);
    color: white;
}

.report-content {
    flex: 1;
}

.report-content h4 {
    margin: 0 0 0.25rem 0;
    color: var(--gray-800);
    font-size: 1.1rem;
}

.report-content p {
    margin: 0;
    color: var(--gray-500);
    font-size: 0.9rem;
}

.report-arrow {
    color: var(--gray-400);
    transition: transform 0.2s ease;
}

.report-card:hover .report-arrow {
    transform: translateX(4px);
    color: var(--primary-500);
}
</style>
