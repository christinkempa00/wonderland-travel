<?php
/**
 * Income Statement (Laba Rugi) Report View
 */
?>

<!-- Filter -->
<div class="glass-card mb-4">
    <form method="GET" action="<?= url('/accounting/reports/income-statement') ?>" class="d-flex gap-3 align-items-end">
        <div>
            <label class="form-label">Dari Tanggal</label>
            <input type="date" name="start_date" class="form-control" value="<?= e($startDate) ?>">
        </div>
        <div>
            <label class="form-label">Sampai Tanggal</label>
            <input type="date" name="end_date" class="form-control" value="<?= e($endDate) ?>">
        </div>
        <button type="submit" class="btn btn-primary">
            <i class="fas fa-sync"></i> Refresh
        </button>
        <button type="button" class="btn btn-secondary" onclick="window.print()">
            <i class="fas fa-print"></i> Cetak
        </button>
    </form>
</div>

<!-- Report -->
<div class="glass-card" id="printArea">
    <div class="report-header">
        <h3>LAPORAN LABA RUGI</h3>
        <p>Periode: <?= formatDateIndo($startDate) ?> - <?= formatDateIndo($endDate) ?></p>
    </div>
    
    <div class="row">
        <div class="col-12">
            <!-- Revenue Section -->
            <div class="report-section">
                <h4 class="section-title">PENDAPATAN</h4>
                
                <?php if (empty($revenueAccounts)): ?>
                <p class="text-muted">Tidak ada data pendapatan.</p>
                <?php else: ?>
                <table class="table table-sm">
                    <tbody>
                        <?php foreach ($revenueAccounts as $acc): ?>
                        <?php if ($acc['balance'] > 0): ?>
                        <tr>
                            <td>
                                <code class="text-muted"><?= e($acc['code']) ?></code>
                                <?= e($acc['name']) ?>
                            </td>
                            <td class="text-right" width="150"><?= formatRupiah($acc['balance']) ?></td>
                        </tr>
                        <?php endif; ?>
                        <?php endforeach; ?>
                    </tbody>
                    <tfoot>
                        <tr class="font-semibold table-success">
                            <td>Total Pendapatan</td>
                            <td class="text-right"><?= formatRupiah($totalRevenue) ?></td>
                        </tr>
                    </tfoot>
                </table>
                <?php endif; ?>
            </div>
            
            <!-- Expense Section -->
            <div class="report-section">
                <h4 class="section-title">BEBAN</h4>
                
                <?php if (empty($expenseAccounts)): ?>
                <p class="text-muted">Tidak ada data beban.</p>
                <?php else: ?>
                <table class="table table-sm">
                    <tbody>
                        <?php foreach ($expenseAccounts as $acc): ?>
                        <?php if ($acc['balance'] > 0): ?>
                        <tr>
                            <td>
                                <code class="text-muted"><?= e($acc['code']) ?></code>
                                <?= e($acc['name']) ?>
                            </td>
                            <td class="text-right" width="150"><?= formatRupiah($acc['balance']) ?></td>
                        </tr>
                        <?php endif; ?>
                        <?php endforeach; ?>
                    </tbody>
                    <tfoot>
                        <tr class="font-semibold table-danger">
                            <td>Total Beban</td>
                            <td class="text-right">(<?= formatRupiah($totalExpense) ?>)</td>
                        </tr>
                    </tfoot>
                </table>
                <?php endif; ?>
            </div>
            
            <!-- Net Income -->
            <div class="net-income-box <?= $netIncome >= 0 ? 'profit' : 'loss' ?>">
                <div class="d-flex justify-content-between align-items-center">
                    <h4 class="mb-0"><?= $netIncome >= 0 ? 'LABA BERSIH' : 'RUGI BERSIH' ?></h4>
                    <h3 class="mb-0"><?= formatRupiah(abs($netIncome)) ?></h3>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.report-header {
    text-align: center;
    margin-bottom: 2rem;
    padding-bottom: 1rem;
    border-bottom: 2px solid var(--gray-200);
}

.report-header h3 {
    margin: 0 0 0.5rem 0;
    color: var(--gray-800);
}

.report-header p {
    margin: 0;
    color: var(--gray-500);
}

.report-section {
    margin-bottom: 2rem;
}

.section-title {
    font-size: 1rem;
    font-weight: 600;
    color: var(--gray-700);
    margin-bottom: 1rem;
    padding-bottom: 0.5rem;
    border-bottom: 1px solid var(--gray-200);
}

.net-income-box {
    padding: 1.5rem;
    border-radius: var(--border-radius);
    margin-top: 2rem;
}

.net-income-box.profit {
    background: linear-gradient(135deg, #d1fae5 0%, #a7f3d0 100%);
    color: #065f46;
}

.net-income-box.loss {
    background: linear-gradient(135deg, #fee2e2 0%, #fecaca 100%);
    color: #991b1b;
}

@media print {
    body * { visibility: hidden; }
    #printArea, #printArea * { visibility: visible; }
    #printArea {
        position: absolute;
        left: 0;
        top: 0;
        width: 100%;
        box-shadow: none;
        background: white;
    }
}
</style>
