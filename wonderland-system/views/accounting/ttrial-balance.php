<?php
/**
 * Trial Balance Report View
 */
?>

<!-- Filter -->
<div class="glass-card mb-4">
    <form method="GET" action="<?= url('/accounting/reports/trial-balance') ?>" class="d-flex gap-3 align-items-end">
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
        <h3>NERACA SALDO</h3>
        <p>Per <?= formatDateIndo($endDate) ?></p>
    </div>
    
    <?php if (empty($trialBalance)): ?>
    <div class="text-center py-4 text-muted">
        <p>Tidak ada data.</p>
    </div>
    <?php else: ?>
    <div class="table-responsive">
        <table class="table">
            <thead>
                <tr>
                    <th width="100">Kode</th>
                    <th>Nama Akun</th>
                    <th class="text-right" width="150">Debit</th>
                    <th class="text-right" width="150">Kredit</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($trialBalance as $row): ?>
                <tr>
                    <td><code><?= e($row['code']) ?></code></td>
                    <td><?= e($row['name']) ?></td>
                    <td class="text-right"><?= $row['debit'] > 0 ? formatRupiah($row['debit']) : '-' ?></td>
                    <td class="text-right"><?= $row['credit'] > 0 ? formatRupiah($row['credit']) : '-' ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
            <tfoot>
                <tr class="font-semibold" style="font-size: 1.1rem; background: var(--gray-100);">
                    <td colspan="2" class="text-right">TOTAL</td>
                    <td class="text-right"><?= formatRupiah($totalDebit) ?></td>
                    <td class="text-right"><?= formatRupiah($totalCredit) ?></td>
                </tr>
            </tfoot>
        </table>
    </div>
    
    <?php if (abs($totalDebit - $totalCredit) < 0.01): ?>
    <div class="alert alert-success mt-4">
        <i class="fas fa-check-circle"></i>
        <strong>Balance!</strong> Total debit dan kredit seimbang.
    </div>
    <?php else: ?>
    <div class="alert alert-danger mt-4">
        <i class="fas fa-exclamation-circle"></i>
        <strong>Tidak Balance!</strong> Selisih: <?= formatRupiah(abs($totalDebit - $totalCredit)) ?>
    </div>
    <?php endif; ?>
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

@media print {
    body * {
        visibility: hidden;
    }
    
    #printArea, #printArea * {
        visibility: visible;
    }
    
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
