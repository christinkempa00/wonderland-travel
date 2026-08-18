<?php
/**
 * Balance Sheet (Neraca) Report View
 */
?>

<!-- Filter -->
<div class="glass-card mb-4">
    <form method="GET" action="<?= url('/accounting/reports/balance-sheet') ?>" class="d-flex gap-3 align-items-end">
        <div>
            <label class="form-label">Per Tanggal</label>
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
        <h3>NERACA</h3>
        <p>Per <?= formatDateIndo($endDate) ?></p>
    </div>
    
    <div class="row">
        <!-- Assets -->
        <div class="col-6">
            <div class="report-section">
                <h4 class="section-title">ASET</h4>
                
                <?php if (empty($assets)): ?>
                <p class="text-muted">Tidak ada data aset.</p>
                <?php else: ?>
                <table class="table table-sm">
                    <tbody>
                        <?php foreach ($assets as $acc): ?>
                        <?php if (abs($acc['balance']) > 0.01): ?>
                        <tr>
                            <td>
                                <code class="text-muted"><?= e($acc['code']) ?></code>
                                <?= e($acc['name']) ?>
                            </td>
                            <td class="text-right" width="130"><?= formatRupiah($acc['balance']) ?></td>
                        </tr>
                        <?php endif; ?>
                        <?php endforeach; ?>
                    </tbody>
                    <tfoot>
                        <tr class="font-semibold" style="background: var(--primary-100);">
                            <td>Total Aset</td>
                            <td class="text-right"><?= formatRupiah($totalAssets) ?></td>
                        </tr>
                    </tfoot>
                </table>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- Liabilities & Equity -->
        <div class="col-6">
            <!-- Liabilities -->
            <div class="report-section">
                <h4 class="section-title">KEWAJIBAN</h4>
                
                <?php if (empty($liabilities)): ?>
                <p class="text-muted">Tidak ada data kewajiban.</p>
                <?php else: ?>
                <table class="table table-sm">
                    <tbody>
                        <?php foreach ($liabilities as $acc): ?>
                        <?php if (abs($acc['balance']) > 0.01): ?>
                        <tr>
                            <td>
                                <code class="text-muted"><?= e($acc['code']) ?></code>
                                <?= e($acc['name']) ?>
                            </td>
                            <td class="text-right" width="130"><?= formatRupiah($acc['balance']) ?></td>
                        </tr>
                        <?php endif; ?>
                        <?php endforeach; ?>
                    </tbody>
                    <tfoot>
                        <tr class="font-semibold" style="background: var(--warning-light);">
                            <td>Total Kewajiban</td>
                            <td class="text-right"><?= formatRupiah($totalLiabilities) ?></td>
                        </tr>
                    </tfoot>
                </table>
                <?php endif; ?>
            </div>
            
            <!-- Equity -->
            <div class="report-section">
                <h4 class="section-title">EKUITAS</h4>
                
                <?php if (empty($equity)): ?>
                <p class="text-muted">Tidak ada data ekuitas.</p>
                <?php else: ?>
                <table class="table table-sm">
                    <tbody>
                        <?php foreach ($equity as $acc): ?>
                        <?php if (abs($acc['balance']) > 0.01): ?>
                        <tr>
                            <td>
                                <code class="text-muted"><?= e($acc['code']) ?></code>
                                <?= e($acc['name']) ?>
                            </td>
                            <td class="text-right" width="130"><?= formatRupiah($acc['balance']) ?></td>
                        </tr>
                        <?php endif; ?>
                        <?php endforeach; ?>
                    </tbody>
                    <tfoot>
                        <tr class="font-semibold" style="background: var(--info-light);">
                            <td>Total Ekuitas</td>
                            <td class="text-right"><?= formatRupiah($totalEquity) ?></td>
                        </tr>
                    </tfoot>
                </table>
                <?php endif; ?>
            </div>
            
            <!-- Total Liabilities + Equity -->
            <div class="total-le-box">
                <div class="d-flex justify-content-between">
                    <strong>Total Kewajiban + Ekuitas</strong>
                    <strong><?= formatRupiah($totalLiabilities + $totalEquity) ?></strong>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Balance Check -->
    <?php $difference = abs($totalAssets - ($totalLiabilities + $totalEquity)); ?>
    <?php if ($difference < 0.01): ?>
    <div class="alert alert-success mt-4">
        <i class="fas fa-check-circle"></i>
        <strong>Balance!</strong> Aset = Kewajiban + Ekuitas
    </div>
    <?php else: ?>
    <div class="alert alert-danger mt-4">
        <i class="fas fa-exclamation-circle"></i>
        <strong>Tidak Balance!</strong> Selisih: <?= formatRupiah($difference) ?>
    </div>
    <?php endif; ?>
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
    margin-bottom: 1.5rem;
}

.section-title {
    font-size: 1rem;
    font-weight: 600;
    color: var(--gray-700);
    margin-bottom: 1rem;
    padding-bottom: 0.5rem;
    border-bottom: 1px solid var(--gray-200);
}

.total-le-box {
    background: var(--gray-100);
    padding: 1rem;
    border-radius: var(--border-radius-sm);
    font-size: 1.05rem;
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
