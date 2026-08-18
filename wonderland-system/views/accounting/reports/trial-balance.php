<?php
/**
 * Trial Balance - Neraca Saldo
 * File: /views/accounting/reports/trial-balance.php
 */
?>

<!-- Filter -->
<div class="glass-card mb-4">
    <form method="GET" class="row g-3 align-items-end">
        <div class="col-md-3">
            <label class="form-label small">Sampai Tanggal</label>
            <input type="date" name="end_date" class="form-control" value="<?= e($endDate) ?>">
        </div>
        <div class="col-md-3">
            <button type="submit" class="btn btn-primary">
                <i class="fas fa-filter me-1"></i> Tampilkan
            </button>
            <button type="button" class="btn btn-success ms-2" onclick="printReport()">
                <i class="fas fa-print me-1"></i> Cetak
            </button>
        </div>
    </form>
</div>

<!-- Report -->
<div class="glass-card" id="printArea">
    <!-- Header untuk Print -->
    <div class="d-none d-print-block text-center mb-4">
        <?php 
        $company = db()->fetchOne("SELECT name FROM companies WHERE id = ?", [Session::companyId()]);
        ?>
        <h4 class="mb-1"><?= e($company['name'] ?? 'Perusahaan') ?></h4>
        <h5 class="mb-1">NERACA SALDO</h5>
        <p class="text-muted mb-0">Per <?= formatDate($endDate, 'd F Y') ?></p>
    </div>
    
    <!-- Status Balance -->
    <?php if (isset($isBalanced)): ?>
    <div class="alert <?= $isBalanced ? 'alert-success' : 'alert-danger' ?> mb-4 d-print-none">
        <i class="fas <?= $isBalanced ? 'fa-check-circle' : 'fa-exclamation-triangle' ?> me-2"></i>
        <?php if ($isBalanced): ?>
            <strong>Balance!</strong> Total Debit = Total Kredit
        <?php else: ?>
            <strong>Tidak Balance!</strong> Selisih: <?= formatRupiah(abs($totalDebit - $totalCredit)) ?>
        <?php endif; ?>
    </div>
    <?php endif; ?>
    
    <div class="table-responsive">
        <table class="table table-bordered table-sm">
            <thead class="table-light">
                <tr>
                    <th width="100">Kode</th>
                    <th>Nama Akun</th>
                    <th width="150" class="text-end">Debit</th>
                    <th width="150" class="text-end">Kredit</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($trialBalance)): ?>
                <tr>
                    <td colspan="4" class="text-center text-muted py-4">
                        <i class="fas fa-inbox fa-2x mb-2 d-block opacity-50"></i>
                        Tidak ada data untuk ditampilkan
                    </td>
                </tr>
                <?php else: ?>
                    <?php 
                    $currentType = '';
                    $typeLabels = [
                        'asset' => 'ASET',
                        'liability' => 'KEWAJIBAN',
                        'equity' => 'EKUITAS',
                        'revenue' => 'PENDAPATAN',
                        'expense' => 'BEBAN'
                    ];
                    foreach ($trialBalance as $row): 
                        // Group header by type
                        if (isset($row['type']) && $row['type'] !== $currentType):
                            $currentType = $row['type'];
                    ?>
                    <tr class="table-secondary">
                        <td colspan="4" class="fw-bold small">
                            <?= $typeLabels[$currentType] ?? strtoupper($currentType) ?>
                        </td>
                    </tr>
                    <?php endif; ?>
                    <tr>
                        <td><code><?= e($row['code']) ?></code></td>
                        <td><?= e($row['name']) ?></td>
                        <td class="text-end"><?= $row['debit'] > 0 ? formatRupiah($row['debit']) : '-' ?></td>
                        <td class="text-end"><?= $row['credit'] > 0 ? formatRupiah($row['credit']) : '-' ?></td>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
            <tfoot class="table-dark">
                <tr>
                    <th colspan="2" class="text-end">TOTAL</th>
                    <th class="text-end"><?= formatRupiah($totalDebit) ?></th>
                    <th class="text-end"><?= formatRupiah($totalCredit) ?></th>
                </tr>
            </tfoot>
        </table>
    </div>
    
    <!-- Footer untuk Print -->
    <div class="d-none d-print-block mt-4 pt-4 border-top">
        <div class="row">
            <div class="col-6">
                <p class="small text-muted mb-0">Dicetak: <?= date('d/m/Y H:i') ?></p>
            </div>
            <div class="col-6 text-end">
                <p class="small text-muted mb-0">Halaman 1</p>
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
    body { font-size: 11pt; }
    .glass-card { box-shadow: none !important; border: none !important; padding: 0 !important; }
    .table { font-size: 10pt; }
    .table th, .table td { padding: 4px 8px !important; }
    .d-print-none { display: none !important; }
    .d-print-block { display: block !important; }
}
</style>