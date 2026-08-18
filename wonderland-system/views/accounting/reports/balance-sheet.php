<?php
/**
 * Balance Sheet - Neraca
 * File: /views/accounting/reports/balance-sheet.php
 */

$totalLiabilitiesEquity = $totalLiabilities + $totalEquity;
$isBalanced = abs($totalAssets - $totalLiabilitiesEquity) < 1;
?>

<!-- Filter -->
<div class="glass-card mb-4">
    <form method="GET" class="row g-3 align-items-end">
        <div class="col-md-3">
            <label class="form-label small">Per Tanggal</label>
            <input type="date" name="end_date" class="form-control" value="<?= e($endDate) ?>">
        </div>
        <div class="col-md-4">
            <button type="submit" class="btn btn-primary">
                <i class="fas fa-filter me-1"></i> Tampilkan
            </button>
            <button type="button" class="btn btn-success ms-2" onclick="printReport()">
                <i class="fas fa-print me-1"></i> Cetak
            </button>
        </div>
    </form>
</div>

<!-- Status Balance -->
<?php if (!$isBalanced): ?>
<div class="alert alert-danger mb-4 d-print-none">
    <i class="fas fa-exclamation-triangle me-2"></i>
    <strong>Neraca Tidak Balance!</strong> 
    Aset: <?= formatRupiah($totalAssets) ?> ≠ Kewajiban + Ekuitas: <?= formatRupiah($totalLiabilitiesEquity) ?>
    (Selisih: <?= formatRupiah(abs($totalAssets - $totalLiabilitiesEquity)) ?>)
</div>
<?php endif; ?>

<!-- Report -->
<div class="row" id="printArea">
    <!-- Header untuk Print -->
    <div class="col-12 d-none d-print-block text-center mb-4">
        <?php 
        $company = db()->fetchOne("SELECT name FROM companies WHERE id = ?", [Session::companyId()]);
        ?>
        <h4 class="mb-1"><?= e($company['name'] ?? 'Perusahaan') ?></h4>
        <h5 class="mb-1">NERACA</h5>
        <p class="text-muted mb-0">Per <?= formatDate($endDate, 'd F Y') ?></p>
    </div>
    
    <!-- ASET -->
    <div class="col-lg-6 mb-4">
        <div class="glass-card h-100">
            <h5 class="border-bottom pb-2 mb-3 text-primary">
                <i class="fas fa-building me-2"></i>ASET
            </h5>
            
            <table class="table table-sm mb-0">
                <tbody>
                    <?php if (empty($assets)): ?>
                    <tr>
                        <td colspan="2" class="text-muted text-center py-3">Tidak ada data aset</td>
                    </tr>
                    <?php else: ?>
                        <?php foreach ($assets as $acc): ?>
                        <?php if (abs($acc['balance']) > 0.01): ?>
                        <tr>
                            <td>
                                <code class="me-2 small"><?= e($acc['code']) ?></code>
                                <?= e($acc['name']) ?>
                            </td>
                            <td class="text-end" width="130"><?= formatRupiah($acc['balance']) ?></td>
                        </tr>
                        <?php endif; ?>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
                <tfoot>
                    <tr class="table-primary fw-bold">
                        <td>TOTAL ASET</td>
                        <td class="text-end"><?= formatRupiah($totalAssets) ?></td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>
    
    <!-- KEWAJIBAN & EKUITAS -->
    <div class="col-lg-6 mb-4">
        <div class="glass-card h-100">
            <h5 class="border-bottom pb-2 mb-3 text-danger">
                <i class="fas fa-file-invoice-dollar me-2"></i>KEWAJIBAN
            </h5>
            
            <table class="table table-sm mb-3">
                <tbody>
                    <?php if (empty($liabilities)): ?>
                    <tr>
                        <td colspan="2" class="text-muted text-center py-2">Tidak ada kewajiban</td>
                    </tr>
                    <?php else: ?>
                        <?php foreach ($liabilities as $acc): ?>
                        <?php if (abs($acc['balance']) > 0.01): ?>
                        <tr>
                            <td>
                                <code class="me-2 small"><?= e($acc['code']) ?></code>
                                <?= e($acc['name']) ?>
                            </td>
                            <td class="text-end" width="130"><?= formatRupiah($acc['balance']) ?></td>
                        </tr>
                        <?php endif; ?>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
                <tfoot>
                    <tr class="table-light fw-bold">
                        <td>Total Kewajiban</td>
                        <td class="text-end"><?= formatRupiah($totalLiabilities) ?></td>
                    </tr>
                </tfoot>
            </table>
            
            <h5 class="border-bottom pb-2 mb-3 text-success">
                <i class="fas fa-landmark me-2"></i>EKUITAS
            </h5>
            
            <table class="table table-sm mb-0">
                <tbody>
                    <?php if (empty($equity)): ?>
                    <tr>
                        <td colspan="2" class="text-muted text-center py-2">Tidak ada data ekuitas</td>
                    </tr>
                    <?php else: ?>
                        <?php foreach ($equity as $acc): ?>
                        <?php if (abs($acc['balance']) > 0.01): ?>
                        <tr>
                            <td>
                                <code class="me-2 small"><?= e($acc['code']) ?></code>
                                <?= e($acc['name']) ?>
                            </td>
                            <td class="text-end" width="130"><?= formatRupiah($acc['balance']) ?></td>
                        </tr>
                        <?php endif; ?>
                        <?php endforeach; ?>
                    <?php endif; ?>
                    
                    <!-- Laba Ditahan -->
                    <?php if (isset($retainedEarnings) && abs($retainedEarnings) > 0.01): ?>
                    <tr class="table-warning">
                        <td>
                            <i class="fas fa-chart-line me-2"></i>
                            Laba Ditahan (Retained Earnings)
                        </td>
                        <td class="text-end"><?= formatRupiah($retainedEarnings) ?></td>
                    </tr>
                    <?php endif; ?>
                </tbody>
                <tfoot>
                    <tr class="table-light fw-bold">
                        <td>Total Ekuitas</td>
                        <td class="text-end"><?= formatRupiah($totalEquity) ?></td>
                    </tr>
                    <tr class="table-success fw-bold">
                        <td>TOTAL KEWAJIBAN & EKUITAS</td>
                        <td class="text-end"><?= formatRupiah($totalLiabilitiesEquity) ?></td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>
</div>

<!-- Balance Check -->
<div class="glass-card <?= $isBalanced ? 'bg-success' : 'bg-danger' ?> bg-opacity-10">
    <div class="row align-items-center">
        <div class="col-md-4 text-center border-end py-3">
            <div class="text-muted small">Total Aset</div>
            <div class="fs-4 fw-bold"><?= formatRupiah($totalAssets) ?></div>
        </div>
        <div class="col-md-4 text-center border-end py-3">
            <div class="text-muted small">Total Kewajiban + Ekuitas</div>
            <div class="fs-4 fw-bold"><?= formatRupiah($totalLiabilitiesEquity) ?></div>
        </div>
        <div class="col-md-4 text-center py-3">
            <div class="text-muted small">Status</div>
            <div class="fs-4 fw-bold <?= $isBalanced ? 'text-success' : 'text-danger' ?>">
                <i class="fas <?= $isBalanced ? 'fa-check-circle' : 'fa-times-circle' ?> me-2"></i>
                <?= $isBalanced ? 'BALANCE' : 'TIDAK BALANCE' ?>
            </div>
        </div>
    </div>
</div>

<script>
function printReport() {
    window.print();
}
</script>

<style>
@media print {
    body { font-size: 10pt; }
    .glass-card { box-shadow: none !important; border: 1px solid #ddd !important; padding: 10px !important; margin-bottom: 10px !important; }
    .table { font-size: 9pt; }
    .col-lg-6 { width: 50% !important; float: left; }
    .d-print-none { display: none !important; }
    .d-print-block { display: block !important; }
}
</style>