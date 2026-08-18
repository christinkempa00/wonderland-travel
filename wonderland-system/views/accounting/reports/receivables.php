<?php
/**
 * Receivables Report - Laporan Piutang
 * File: /views/accounting/reports/receivables.php
 */

$company = db()->fetchOne("SELECT name FROM companies WHERE id = ?", [Session::companyId()]);
?>

<div class="glass-card mb-4">
    <form method="GET" class="row g-3 align-items-end">
        <div class="col-md-3">
            <label class="form-label small">Per Tanggal</label>
            <input type="date" name="end_date" class="form-control" value="<?= e($endDate) ?>">
        </div>
        <div class="col-md-3">
            <label class="form-label small">Umur Minimal</label>
            <select name="aging_days" class="form-control">
                <option value="">Semua</option>
                <option value="30" <?= $agingDays == 30 ? 'selected' : '' ?>>≥ 30 hari</option>
                <option value="60" <?= $agingDays == 60 ? 'selected' : '' ?>>≥ 60 hari</option>
                <option value="90" <?= $agingDays == 90 ? 'selected' : '' ?>>≥ 90 hari</option>
            </select>
        </div>
        <div class="col-md-6">
            <button type="submit" class="btn btn-primary">
                <i class="fas fa-filter me-1"></i> Filter
            </button>
            <button type="button" class="btn btn-success ms-2" onclick="window.print()">
                <i class="fas fa-print me-1"></i> Cetak
            </button>
        </div>
    </form>
</div>

<div class="row g-3 mb-4">
    <div class="col-6 col-lg">
        <div class="glass-card text-center p-3 bg-success bg-opacity-10">
            <div class="small text-muted">0-30 Hari</div>
            <div class="fs-5 fw-bold text-success"><?= formatRupiah($aging['current']) ?></div>
        </div>
    </div>
    <div class="col-6 col-lg">
        <div class="glass-card text-center p-3 bg-warning bg-opacity-10">
            <div class="small text-muted">31-60 Hari</div>
            <div class="fs-5 fw-bold text-warning"><?= formatRupiah($aging['31_60']) ?></div>
        </div>
    </div>
    <div class="col-6 col-lg">
        <div class="glass-card text-center p-3 bg-danger bg-opacity-10">
            <div class="small text-muted">61-90 Hari</div>
            <div class="fs-5 fw-bold text-danger"><?= formatRupiah($aging['61_90']) ?></div>
        </div>
    </div>
    <div class="col-6 col-lg">
        <div class="glass-card text-center p-3" style="background:rgba(100,0,0,0.1)">
            <div class="small text-muted">> 90 Hari</div>
            <div class="fs-5 fw-bold" style="color:darkred"><?= formatRupiah($aging['over_90']) ?></div>
        </div>
    </div>
    <div class="col-12 col-lg">
        <div class="glass-card text-center p-3 bg-primary bg-opacity-10">
            <div class="small text-muted">Total Piutang</div>
            <div class="fs-5 fw-bold text-primary"><?= formatRupiah($totalReceivables) ?></div>
        </div>
    </div>
</div>

<div class="glass-card">
    <div class="d-none d-print-block text-center mb-4">
        <h4><?= e($company['name'] ?? 'Perusahaan') ?></h4>
        <h5>LAPORAN PIUTANG</h5>
        <p>Per <?= formatDate($endDate, 'd F Y') ?></p>
    </div>
    
    <div class="table-responsive">
        <table class="table table-bordered table-sm">
            <thead class="table-light">
                <tr>
                    <th>No. Order</th>
                    <th>Klien / Event</th>
                    <th>Tgl Order</th>
                    <th class="text-center">Umur</th>
                    <th class="text-end">Tagihan</th>
                    <th class="text-end">Dibayar</th>
                    <th class="text-end">Sisa</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($receivables)): ?>
                <tr>
                    <td colspan="7" class="text-center text-muted py-4">Tidak ada piutang</td>
                </tr>
                <?php else: ?>
                <?php foreach ($receivables as $r): 
                    $days = (int)$r['days_overdue'];
                    $cls = $days > 90 ? 'table-danger' : ($days > 60 ? 'table-warning' : '');
                ?>
                <tr class="<?= $cls ?>">
                    <td>
                        <a href="<?= url('/orders/'.$r['id']) ?>"><?= e($r['order_number']) ?></a>
                    </td>
                    <td>
                        <?php if ($r['client_name']): ?><strong><?= e($r['client_name']) ?></strong><br><?php endif; ?>
                        <small class="text-muted"><?= e($r['event_name'] ?: '-') ?></small>
                    </td>
                    <td><?= formatDate($r['order_date'], 'd/m/Y') ?></td>
                    <td class="text-center">
                        <span class="badge bg-<?= $days > 60 ? 'danger' : ($days > 30 ? 'warning' : 'secondary') ?>"><?= $days ?> hari</span>
                    </td>
                    <td class="text-end"><?= formatRupiah($r['total_final_price']) ?></td>
                    <td class="text-end"><?= formatRupiah($r['paid_amount'] ?? 0) ?></td>
                    <td class="text-end fw-bold text-danger"><?= formatRupiah($r['outstanding']) ?></td>
                </tr>
                <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
            <?php if (!empty($receivables)): ?>
            <tfoot class="table-dark">
                <tr>
                    <th colspan="4" class="text-end">TOTAL</th>
                    <th class="text-end"><?= formatRupiah(array_sum(array_column($receivables, 'total_final_price'))) ?></th>
                    <th class="text-end"><?= formatRupiah(array_sum(array_column($receivables, 'paid_amount'))) ?></th>
                    <th class="text-end"><?= formatRupiah($totalReceivables) ?></th>
                </tr>
            </tfoot>
            <?php endif; ?>
        </table>
    </div>
</div>

<style>
@media print {
    .glass-card { box-shadow:none!important; border:1px solid #ddd!important; }
    .d-print-none { display:none!important; }
    .d-print-block { display:block!important; }
}
</style>