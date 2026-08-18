<?php
/**
 * Cash Flow Report - Laporan Arus Kas
 * File: views/accounting/reports/cash-flow.php
 * 
 * v5: Lengkap dengan:
 *     - Saldo akhir kas real-time
 *     - Total debit & kredit
 *     - Hutang vendor (HPP belum dibayar)
 */

// Pastikan variabel ada (dari controller)
$startDate = $startDate ?? date('Y-m-01');
$endDate = $endDate ?? date('Y-m-t');
$openingBalance = $openingBalance ?? 0;
$closingBalance = $closingBalance ?? 0;
$totalInflow = $totalInflow ?? 0;
$totalOutflow = $totalOutflow ?? 0;
$transactions = $transactions ?? [];
$inflows = $inflows ?? [];
$outflows = $outflows ?? [];

$company = db()->fetchOne("SELECT * FROM companies WHERE id = ?", [Session::companyId()]);
$logoFile = $company['logo'] ?? '';
$companyId = Session::companyId();

// Helper format rupiah singkat
if (!function_exists('formatRupiahShort')) {
    function formatRupiahShort($val) {
        $val = (float)$val;
        if ($val >= 1000000000) return 'Rp ' . number_format($val / 1000000000, 1, ',', '.') . 'M';
        if ($val >= 1000000) return 'Rp ' . number_format($val / 1000000, 1, ',', '.') . 'jt';
        if ($val >= 1000) return 'Rp ' . number_format($val / 1000, 0, ',', '.') . 'rb';
        return 'Rp ' . number_format($val, 0, ',', '.');
    }
}

// ================================================================
// AMBIL SALDO KAS REAL-TIME (dari tabel bank_cash)
// ================================================================
$currentCashBalance = 0;
$cashAccounts = [];
try {
    $cashAccounts = db()->fetchAll("
        SELECT id, name, type, bank_name, balance 
        FROM bank_cash 
        WHERE company_id = ? 
        AND is_active = 1
        ORDER BY type, name
    ", [$companyId]);
    
    foreach ($cashAccounts as $acc) {
        $currentCashBalance += (float)$acc['balance'];
    }
} catch (Exception $e) {
    // Fallback ke closingBalance jika tabel tidak ada
    $currentCashBalance = $closingBalance;
}

// ================================================================
// AMBIL DATA HUTANG VENDOR (HPP belum dibayar)
// ================================================================
$vendorPayables = [];
$totalVendorPayable = 0;
try {
    $vendorPayables = db()->fetchAll("
        SELECT 
            o.id,
            o.order_number,
            o.order_date,
            o.event_name,
            COALESCE(o.total_base_price, 0) as modal_total,
            COALESCE(o.expense_paid_amount, 0) as sudah_dibayar,
            (COALESCE(o.total_base_price, 0) - COALESCE(o.expense_paid_amount, 0)) as sisa_hutang
        FROM orders o
        WHERE o.company_id = ?
        AND (o.deleted_at IS NULL OR o.deleted_at = '0000-00-00 00:00:00')
        AND (COALESCE(o.total_base_price, 0) - COALESCE(o.expense_paid_amount, 0)) > 0
        ORDER BY o.order_date DESC
        LIMIT 20
    ", [$companyId]);
    
    // Hitung total hutang vendor (semua order, bukan hanya 20)
    $totalPayable = db()->fetchOne("
        SELECT SUM(COALESCE(total_base_price, 0) - COALESCE(expense_paid_amount, 0)) as total
        FROM orders 
        WHERE company_id = ?
        AND (deleted_at IS NULL OR deleted_at = '0000-00-00 00:00:00')
        AND (COALESCE(total_base_price, 0) - COALESCE(expense_paid_amount, 0)) > 0
    ", [$companyId]);
    $totalVendorPayable = (float)($totalPayable['total'] ?? 0);
} catch (Exception $e) {
    // Abaikan error
}

// ================================================================
// AMBIL DATA HUTANG HPP PER KATEGORI
// ================================================================
$hppByCategory = [];
$totalHppByCategory = 0;
try {
    $hppByCategory = db()->fetchAll("
        SELECT 
            COALESCE(oi.item_type, 'other') as kategori,
            COUNT(DISTINCT o.id) as jumlah_order,
            SUM(COALESCE(oi.base_price, 0) * COALESCE(oi.quantity, 1) * COALESCE(oi.num_days, 1)) as total_modal,
            SUM(
                CASE WHEN oi.expense_status = 'paid' 
                THEN COALESCE(oi.base_price, 0) * COALESCE(oi.quantity, 1) * COALESCE(oi.num_days, 1) 
                ELSE 0 END
            ) as sudah_dibayar
        FROM orders o
        JOIN order_items oi ON oi.order_id = o.id
        WHERE o.company_id = ?
        AND (o.deleted_at IS NULL OR o.deleted_at = '0000-00-00 00:00:00')
        GROUP BY COALESCE(oi.item_type, 'other')
        HAVING total_modal > 0
        ORDER BY total_modal DESC
    ", [$companyId]);

    foreach ($hppByCategory as &$cat) {
        $cat['sisa_hutang'] = (float)$cat['total_modal'] - (float)$cat['sudah_dibayar'];
        $totalHppByCategory += $cat['sisa_hutang'];
    }
    unset($cat);
} catch (Exception $e) {
    $hppByCategory = [];
}

// Label kategori
$categoryLabels = [
    'flight' => 'Tiket Pesawat',
    'hotel' => 'Hotel / Akomodasi', 
    'rental' => 'Rental Kendaraan',
    'vehicle' => 'Kendaraan',
    'tour' => 'Paket Tour',
    'other' => 'Lainnya'
];
?>

<!-- ================================================================ -->
<!-- SCREEN VIEW -->
<!-- ================================================================ -->

<!-- Filter Form (no-print) -->
<div class="glass-card mb-4 no-print">
    <form method="GET" class="row g-3 align-items-end p-3">
        <div class="col-md-3">
            <label class="form-label small">Dari Tanggal</label>
            <input type="date" name="start_date" class="form-control" value="<?= e($startDate) ?>">
        </div>
        <div class="col-md-3">
            <label class="form-label small">Sampai Tanggal</label>
            <input type="date" name="end_date" class="form-control" value="<?= e($endDate) ?>">
        </div>
        <div class="col-md-6">
            <button type="submit" class="btn btn-primary">
                <i class="fas fa-filter me-1"></i> Tampilkan
            </button>
            <button type="button" class="btn btn-success ms-2" onclick="window.print()">
                <i class="fas fa-print me-1"></i> Cetak
            </button>
        </div>
    </form>
</div>

<!-- SALDO KAS REAL-TIME -->
<div class="glass-card mb-4 no-print" style="background: linear-gradient(135deg, #574314 0%, #c89b2c 100%); color: white;">
    <div class="p-4">
        <div class="row align-items-center">
            <div class="col-md-6">
                <h5 class="mb-1"><i class="fas fa-wallet me-2"></i>Saldo Kas Saat Ini</h5>
                <div class="display-5 fw-bold"><?= formatRupiah($currentCashBalance) ?></div>
                <small class="opacity-75">Total dari <?= count($cashAccounts) ?> akun kas/bank</small>
            </div>
            <div class="col-md-6">
                <div class="row text-center">
                    <?php foreach ($cashAccounts as $acc): ?>
                    <div class="col-<?= count($cashAccounts) <= 2 ? '6' : '4' ?>">
                        <div class="small opacity-75">
                            <?php if ($acc['type'] == 'bank' && $acc['bank_name']): ?>
                                <?= e($acc['bank_name']) ?>
                            <?php else: ?>
                                <?= e($acc['name']) ?>
                            <?php endif; ?>
                        </div>
                        <div class="fw-bold"><?= formatRupiahShort($acc['balance']) ?></div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Summary Cards Periode -->
<div class="row g-3 mb-4 no-print">
    <div class="col-6 col-lg-3">
        <div class="summary-card">
            <div class="summary-icon bg-secondary-soft"><i class="fas fa-calendar-alt"></i></div>
            <div class="summary-content">
                <span class="summary-value"><?= formatRupiahShort($openingBalance) ?></span>
                <span class="summary-label">Saldo Awal Periode</span>
            </div>
        </div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="summary-card border-success">
            <div class="summary-icon bg-success-soft"><i class="fas fa-arrow-down"></i></div>
            <div class="summary-content">
                <span class="summary-value text-success">+<?= formatRupiahShort($totalInflow) ?></span>
                <span class="summary-label">Total Debit</span>
            </div>
        </div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="summary-card border-danger">
            <div class="summary-icon bg-danger-soft"><i class="fas fa-arrow-up"></i></div>
            <div class="summary-content">
                <span class="summary-value text-danger">-<?= formatRupiahShort($totalOutflow) ?></span>
                <span class="summary-label">Total Kredit</span>
            </div>
        </div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="summary-card border-primary">
            <div class="summary-icon bg-primary-soft"><i class="fas fa-calculator"></i></div>
            <div class="summary-content">
                <span class="summary-value text-primary"><?= formatRupiahShort($closingBalance) ?></span>
                <span class="summary-label">Saldo Akhir Periode</span>
            </div>
        </div>
    </div>
</div>

<!-- Alert Hutang Vendor -->
<?php if ($totalVendorPayable > 0): ?>
<div class="alert alert-warning d-flex align-items-center mb-4 no-print">
    <i class="fas fa-exclamation-triangle fa-2x me-3"></i>
    <div>
        <strong>Hutang Vendor (Accounts Payable)</strong><br>
        Terdapat <strong><?= formatRupiah($totalVendorPayable) ?></strong> modal vendor yang belum dibayar.
        <a href="#vendorPayableSection" class="ms-2">Lihat Detail</a>
    </div>
</div>
<?php endif; ?>

<!-- Main Report Table (screen) -->
<div class="glass-card no-print">
    <div class="card-header-custom d-flex justify-content-between align-items-center">
        <h6 class="mb-0"><i class="fas fa-list-alt me-2"></i>Transaksi Periode</h6>
        <span class="badge bg-secondary"><?= count($transactions) ?> transaksi</span>
    </div>
    <div class="table-responsive">
        <table class="table table-bordered table-sm mb-0">
            <thead class="table-light">
                <tr>
                    <th width="90">Tanggal</th>
                    <th width="120">No. Jurnal</th>
                    <th>Keterangan</th>
                    <th width="120" class="text-end">Debit</th>
                    <th width="120" class="text-end">Kredit</th>
                    <th width="130" class="text-end">Saldo</th>
                </tr>
            </thead>
            <tbody>
                <tr class="table-secondary">
                    <td colspan="5"><em>Saldo Awal (<?= date('d/m/Y', strtotime($startDate)) ?>)</em></td>
                    <td class="text-end fw-bold"><?= formatRupiah($openingBalance) ?></td>
                </tr>
                <?php 
                $balance = $openingBalance;
                if (empty($transactions)): 
                ?>
                <tr>
                    <td colspan="6" class="text-center text-muted py-4">
                        <i class="fas fa-inbox fa-2x mb-2 d-block"></i>
                        Tidak ada transaksi pada periode ini
                    </td>
                </tr>
                <?php else: ?>
                    <?php foreach ($transactions as $t): 
                        $debit = (float)($t['debit'] ?? 0);
                        $kredit = (float)($t['credit'] ?? 0);
                        $balance += $debit - $kredit;
                    ?>
                    <tr>
                        <td><?= date('d/m/Y', strtotime($t['journal_date'])) ?></td>
                        <td><code class="small"><?= e($t['journal_number'] ?? '-') ?></code></td>
                        <td><?= e($t['description'] ?: '-') ?></td>
                        <td class="text-end <?= $debit > 0 ? 'text-success' : '' ?>"><?= $debit > 0 ? formatRupiah($debit) : '-' ?></td>
                        <td class="text-end <?= $kredit > 0 ? 'text-danger' : '' ?>"><?= $kredit > 0 ? formatRupiah($kredit) : '-' ?></td>
                        <td class="text-end fw-bold"><?= formatRupiah($balance) ?></td>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
            <tfoot class="table-dark">
                <tr>
                    <th colspan="3" class="text-end">TOTAL</th>
                    <th class="text-end"><?= formatRupiah($totalInflow) ?></th>
                    <th class="text-end"><?= formatRupiah($totalOutflow) ?></th>
                    <th class="text-end"><?= formatRupiah($closingBalance) ?></th>
                </tr>
            </tfoot>
        </table>
    </div>
</div>

<!-- Ringkasan Penerimaan & Pengeluaran -->
<div class="row mt-4 no-print">
    <div class="col-md-6 mb-4">
        <div class="card border-success h-100">
            <div class="card-header bg-success text-white d-flex justify-content-between align-items-center">
                <span><i class="fas fa-arrow-down me-2"></i>Penerimaan (Debit)</span>
                <span class="badge bg-light text-success"><?= count($inflows) ?> transaksi</span>
            </div>
            <div class="card-body p-0">
                <table class="table table-sm mb-0">
                    <?php if (empty($inflows)): ?>
                    <tr><td class="text-center text-muted py-3">Tidak ada penerimaan</td></tr>
                    <?php else: ?>
                        <?php foreach (array_slice($inflows, 0, 5) as $i): ?>
                        <tr>
                            <td width="60"><?= date('d/m', strtotime($i['journal_date'])) ?></td>
                            <td><?= e(substr($i['description'] ?: '-', 0, 35)) ?></td>
                            <td class="text-end text-success fw-bold"><?= formatRupiah($i['debit'] ?? 0) ?></td>
                        </tr>
                        <?php endforeach; ?>
                        <?php if (count($inflows) > 5): ?>
                        <tr><td colspan="3" class="text-center text-muted small py-2">+<?= count($inflows) - 5 ?> lainnya</td></tr>
                        <?php endif; ?>
                    <?php endif; ?>
                </table>
            </div>
            <div class="card-footer bg-success bg-opacity-10">
                <strong>Total Debit: <?= formatRupiah($totalInflow) ?></strong>
            </div>
        </div>
    </div>
    
    <div class="col-md-6 mb-4">
        <div class="card border-danger h-100">
            <div class="card-header bg-danger text-white d-flex justify-content-between align-items-center">
                <span><i class="fas fa-arrow-up me-2"></i>Pengeluaran (Kredit)</span>
                <span class="badge bg-light text-danger"><?= count($outflows) ?> transaksi</span>
            </div>
            <div class="card-body p-0">
                <table class="table table-sm mb-0">
                    <?php if (empty($outflows)): ?>
                    <tr><td class="text-center text-muted py-3">Tidak ada pengeluaran</td></tr>
                    <?php else: ?>
                        <?php foreach (array_slice($outflows, 0, 5) as $o): ?>
                        <tr>
                            <td width="60"><?= date('d/m', strtotime($o['journal_date'])) ?></td>
                            <td><?= e(substr($o['description'] ?: '-', 0, 35)) ?></td>
                            <td class="text-end text-danger fw-bold"><?= formatRupiah($o['credit'] ?? 0) ?></td>
                        </tr>
                        <?php endforeach; ?>
                        <?php if (count($outflows) > 5): ?>
                        <tr><td colspan="3" class="text-center text-muted small py-2">+<?= count($outflows) - 5 ?> lainnya</td></tr>
                        <?php endif; ?>
                    <?php endif; ?>
                </table>
            </div>
            <div class="card-footer bg-danger bg-opacity-10">
                <strong>Total Kredit: <?= formatRupiah($totalOutflow) ?></strong>
            </div>
        </div>
    </div>
</div>

<!-- ================================================================ -->
<!-- HUTANG VENDOR (Detail per Order) -->
<!-- ================================================================ -->
<?php if (!empty($vendorPayables)): ?>
<div class="glass-card mt-4 no-print" id="vendorPayableSection">
    <div class="card-header-custom d-flex justify-content-between align-items-center">
        <h6 class="mb-0">
            <i class="fas fa-file-invoice-dollar text-warning me-2"></i>
            Hutang Vendor (Modal Belum Dibayar)
        </h6>
        <span class="badge bg-warning text-dark"><?= formatRupiah($totalVendorPayable) ?></span>
    </div>
    
    <div class="table-responsive">
        <table class="table table-sm table-hover mb-0">
            <thead class="table-light">
                <tr>
                    <th width="50">#</th>
                    <th width="100">Tanggal</th>
                    <th width="120">No. Order</th>
                    <th>Event</th>
                    <th class="text-end" width="120">Modal</th>
                    <th class="text-end" width="120">Dibayar</th>
                    <th class="text-end" width="120">Sisa Hutang</th>
                    <th class="text-center" width="80">Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php $no = 1; foreach ($vendorPayables as $vp): ?>
                <tr>
                    <td><?= $no++ ?></td>
                    <td><?= date('d/m/Y', strtotime($vp['order_date'])) ?></td>
                    <td><a href="<?= url('/orders/' . $vp['id']) ?>"><?= e($vp['order_number']) ?></a></td>
                    <td>
                        <?= e($vp['event_name'] ?: '-') ?>
                    </td>
                    <td class="text-end"><?= formatRupiah($vp['modal_total']) ?></td>
                    <td class="text-end text-success"><?= formatRupiah($vp['sudah_dibayar']) ?></td>
                    <td class="text-end text-danger fw-bold"><?= formatRupiah($vp['sisa_hutang']) ?></td>
                    <td class="text-center">
                        <a href="<?= url('/analysis?search=' . urlencode($vp['order_number'])) ?>" 
                           class="btn btn-sm btn-outline-primary" title="Bayar Vendor">
                            <i class="fas fa-credit-card"></i>
                        </a>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
            <tfoot class="table-warning">
                <tr>
                    <th colspan="4" class="text-end">TOTAL HUTANG VENDOR</th>
                    <th class="text-end"><?= formatRupiah(array_sum(array_column($vendorPayables, 'modal_total'))) ?></th>
                    <th class="text-end"><?= formatRupiah(array_sum(array_column($vendorPayables, 'sudah_dibayar'))) ?></th>
                    <th class="text-end text-danger"><?= formatRupiah($totalVendorPayable) ?></th>
                    <th></th>
                </tr>
            </tfoot>
        </table>
    </div>
    
    <div class="p-3 bg-light border-top small">
        <i class="fas fa-info-circle text-info me-2"></i>
        <strong>Keterangan:</strong> Modal vendor adalah biaya yang harus dibayarkan ke vendor/supplier. 
        Gunakan fitur "Bayar Vendor" di halaman Analisa untuk mencatat pembayaran.
    </div>
</div>
<?php endif; ?>

<!-- Hutang per Kategori -->
<?php if (!empty($hppByCategory) && $totalHppByCategory > 0): ?>
<div class="glass-card mt-4 no-print">
    <div class="card-header-custom d-flex justify-content-between align-items-center">
        <h6 class="mb-0">
            <i class="fas fa-chart-pie text-info me-2"></i>
            Ringkasan Hutang per Kategori
        </h6>
        <span class="badge bg-info"><?= formatRupiah($totalHppByCategory) ?></span>
    </div>
    
    <div class="table-responsive">
        <table class="table table-sm table-hover mb-0">
            <thead class="table-light">
                <tr>
                    <th>Kategori</th>
                    <th class="text-center">Jumlah Order</th>
                    <th class="text-end">Total Modal</th>
                    <th class="text-end">Sudah Dibayar</th>
                    <th class="text-end">Sisa Hutang</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($hppByCategory as $cat): if ($cat['sisa_hutang'] > 0): ?>
                <tr>
                    <td>
                        <?php 
                        $icon = 'box';
                        if ($cat['kategori'] == 'flight') $icon = 'plane';
                        elseif ($cat['kategori'] == 'hotel') $icon = 'hotel';
                        elseif (in_array($cat['kategori'], ['rental', 'vehicle'])) $icon = 'car';
                        ?>
                        <i class="fas fa-<?= $icon ?> text-muted me-2"></i>
                        <?= e($categoryLabels[$cat['kategori']] ?? ucfirst($cat['kategori'])) ?>
                    </td>
                    <td class="text-center"><?= number_format($cat['jumlah_order']) ?></td>
                    <td class="text-end"><?= formatRupiah($cat['total_modal']) ?></td>
                    <td class="text-end text-success"><?= formatRupiah($cat['sudah_dibayar']) ?></td>
                    <td class="text-end text-danger fw-bold"><?= formatRupiah($cat['sisa_hutang']) ?></td>
                </tr>
                <?php endif; endforeach; ?>
            </tbody>
            <tfoot class="table-info">
                <tr>
                    <th colspan="2">TOTAL</th>
                    <th class="text-end"><?= formatRupiah(array_sum(array_column($hppByCategory, 'total_modal'))) ?></th>
                    <th class="text-end"><?= formatRupiah(array_sum(array_column($hppByCategory, 'sudah_dibayar'))) ?></th>
                    <th class="text-end text-danger"><?= formatRupiah($totalHppByCategory) ?></th>
                </tr>
            </tfoot>
        </table>
    </div>
</div>
<?php endif; ?>


<!-- ================================================================ -->
<!-- PRINT VIEW -->
<!-- ================================================================ -->
<div class="print-only">
    <!-- Kop Surat - Elegant Dark Style -->
    <div class="kop-surat">
        <?php if ($logoFile): ?>
        <img src="<?= url('/uploads/' . $logoFile) ?>" alt="Logo" class="kop-logo-img">
        <?php else: ?>
        <div class="kop-company-name"><?= e($company['name'] ?? 'PERUSAHAAN') ?></div>
        <?php endif; ?>
    </div>
    
    <!-- Judul Dokumen -->
    <div class="doc-title">
        <h2>LAPORAN ARUS KAS</h2>
        <p class="periode">Periode: <?= date('d F Y', strtotime($startDate)) ?> s/d <?= date('d F Y', strtotime($endDate)) ?></p>
    </div>
    
    <!-- Ringkasan Saldo -->
    <div class="saldo-summary">
        <div class="saldo-item">
            <span class="label">Saldo Awal</span>
            <span class="value"><?= formatRupiah($openingBalance) ?></span>
        </div>
        <div class="saldo-item success">
            <span class="label">Total Debit</span>
            <span class="value"><?= formatRupiah($totalInflow) ?></span>
        </div>
        <div class="saldo-item danger">
            <span class="label">Total Kredit</span>
            <span class="value"><?= formatRupiah($totalOutflow) ?></span>
        </div>
        <div class="saldo-item primary">
            <span class="label">Saldo Akhir</span>
            <span class="value"><?= formatRupiah($closingBalance) ?></span>
        </div>
    </div>
    
    <!-- Saldo Kas Real-time -->
    <div class="current-balance-box">
        <strong>Saldo Kas Saat Ini: <?= formatRupiah($currentCashBalance) ?></strong>
    </div>
    
    <!-- Tabel Transaksi -->
    <table class="print-table">
        <thead>
            <tr>
                <th width="70">Tanggal</th>
                <th>Keterangan</th>
                <th width="100" class="text-right">Debit</th>
                <th width="100" class="text-right">Kredit</th>
                <th width="110" class="text-right">Saldo</th>
            </tr>
        </thead>
        <tbody>
            <tr class="row-saldo-awal">
                <td colspan="4"><em>Saldo Awal</em></td>
                <td class="text-right"><strong><?= formatRupiah($openingBalance) ?></strong></td>
            </tr>
            <?php 
            $printBalance = $openingBalance;
            if (!empty($transactions)):
                foreach ($transactions as $t): 
                    $debit = (float)($t['debit'] ?? 0);
                    $kredit = (float)($t['credit'] ?? 0);
                    $printBalance += $debit - $kredit;
            ?>
            <tr>
                <td><?= date('d/m/y', strtotime($t['journal_date'])) ?></td>
                <td><?= e($t['description'] ?: '-') ?></td>
                <td class="text-right"><?= $debit > 0 ? formatRupiah($debit) : '-' ?></td>
                <td class="text-right"><?= $kredit > 0 ? formatRupiah($kredit) : '-' ?></td>
                <td class="text-right"><strong><?= formatRupiah($printBalance) ?></strong></td>
            </tr>
            <?php 
                endforeach;
            else:
            ?>
            <tr>
                <td colspan="5" class="text-center" style="padding:20px;">Tidak ada transaksi</td>
            </tr>
            <?php endif; ?>
        </tbody>
        <tfoot>
            <tr class="row-total">
                <th colspan="2" class="text-right">TOTAL</th>
                <th class="text-right"><?= formatRupiah($totalInflow) ?></th>
                <th class="text-right"><?= formatRupiah($totalOutflow) ?></th>
                <th class="text-right"><?= formatRupiah($closingBalance) ?></th>
            </tr>
        </tfoot>
    </table>
    
    <!-- Ringkasan Hutang per Kategori -->
    <?php if (!empty($hppByCategory) && $totalHppByCategory > 0): ?>
    <div class="section-title">
        <h3>RINGKASAN HUTANG USAHA</h3>
        <p>Accounts Payable - Kewajiban kepada vendor</p>
    </div>
    
    <table class="print-table table-category">
        <thead>
            <tr>
                <th>Kategori</th>
                <th width="120" class="text-right">Total Modal</th>
                <th width="120" class="text-right">Dibayar</th>
                <th width="120" class="text-right">Sisa Hutang</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($hppByCategory as $cat): if ($cat['sisa_hutang'] > 0): ?>
            <tr>
                <td><?= e($categoryLabels[$cat['kategori']] ?? ucfirst($cat['kategori'])) ?></td>
                <td class="text-right"><?= formatRupiah($cat['total_modal']) ?></td>
                <td class="text-right"><?= formatRupiah($cat['sudah_dibayar']) ?></td>
                <td class="text-right"><strong><?= formatRupiah($cat['sisa_hutang']) ?></strong></td>
            </tr>
            <?php endif; endforeach; ?>
        </tbody>
        <tfoot>
            <tr class="row-total">
                <th class="text-right">TOTAL HUTANG USAHA</th>
                <th class="text-right"><?= formatRupiah(array_sum(array_column($hppByCategory, 'total_modal'))) ?></th>
                <th class="text-right"><?= formatRupiah(array_sum(array_column($hppByCategory, 'sudah_dibayar'))) ?></th>
                <th class="text-right"><?= formatRupiah($totalHppByCategory) ?></th>
            </tr>
        </tfoot>
    </table>
    <?php endif; ?>
    
    <!-- Footer -->
    <div class="print-footer">
        <div class="footer-left">
            Dicetak: <?= date('d/m/Y H:i') ?>
        </div>
        <div class="footer-right">
            <?= e($company['name'] ?? '') ?>
        </div>
    </div>
</div>


<style>
/* ================================================================
   SCREEN STYLES
   ================================================================ */
.summary-card {
    background: white;
    border-radius: 10px;
    padding: 15px;
    display: flex;
    align-items: center;
    gap: 15px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.05);
    border: 1px solid #e9ecef;
    height: 100%;
}
.summary-icon {
    width: 50px;
    height: 50px;
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.25rem;
}
.summary-content {
    display: flex;
    flex-direction: column;
}
.summary-value {
    font-size: 1.25rem;
    font-weight: 700;
    line-height: 1.2;
}
.summary-label {
    font-size: 0.8rem;
    color: #6c757d;
}
.bg-success-soft { background: rgba(25, 135, 84, 0.1); color: #198754; }
.bg-danger-soft { background: rgba(220, 53, 69, 0.1); color: #dc3545; }
.bg-primary-soft { background: rgba(13, 110, 253, 0.1); color: #0d6efd; }
.bg-secondary-soft { background: rgba(108, 117, 125, 0.1); color: #6c757d; }

.card-header-custom {
    padding: 12px 16px;
    background: #f8f9fa;
    border-bottom: 1px solid #dee2e6;
}

.print-only { display: none; }

/* ================================================================
   PRINT STYLES
   ================================================================ */
@media print {
    .no-print,
    .glass-card:not(.print-only),
    .alert,
    .btn,
    nav, 
    .sidebar,
    .navbar,
    footer,
    .row.mt-4:not(.print-only) {
        display: none !important;
    }
    
    .print-only {
        display: block !important;
    }
    
    @page {
        size: A4;
        margin: 10mm 15mm;
    }
    
    body {
        font-family: Arial, sans-serif;
        font-size: 9pt;
        line-height: 1.4;
        color: #333;
        -webkit-print-color-adjust: exact;
        print-color-adjust: exact;
    }
    
    .kop-surat {
        text-align: center;
        padding: 15px 20px;
        background: #1a1a1a !important;
        border-bottom: 3px solid #333;
        margin-bottom: 15px;
    }
    .kop-logo-img {
        max-height: 50px;
        width: auto;
        filter: brightness(1.1);
    }
    .kop-company-name {
        color: white;
        font-size: 16pt;
        font-weight: bold;
        letter-spacing: 2px;
    }
    
    .doc-title {
        text-align: center;
        margin-bottom: 15px;
    }
    .doc-title h2 {
        font-size: 12pt;
        font-weight: bold;
        text-transform: uppercase;
        letter-spacing: 1px;
        margin: 0 0 5px 0;
    }
    .doc-title .periode {
        font-size: 9pt;
        color: #666;
        margin: 0;
    }
    
    .saldo-summary {
        display: flex;
        justify-content: space-between;
        margin-bottom: 10px;
        gap: 10px;
    }
    .saldo-item {
        flex: 1;
        text-align: center;
        padding: 8px;
        border: 1px solid #ddd;
        border-radius: 4px;
        background: #f8f9fa;
    }
    .saldo-item .label {
        display: block;
        font-size: 8pt;
        color: #666;
        margin-bottom: 3px;
    }
    .saldo-item .value {
        display: block;
        font-size: 10pt;
        font-weight: bold;
    }
    .saldo-item.success { border-color: #28a745; background: #d4edda; }
    .saldo-item.success .value { color: #155724; }
    .saldo-item.danger { border-color: #dc3545; background: #f8d7da; }
    .saldo-item.danger .value { color: #721c24; }
    .saldo-item.primary { border-color: #007bff; background: #cce5ff; }
    .saldo-item.primary .value { color: #004085; }
    
    .current-balance-box {
        text-align: center;
        padding: 10px;
        background: #e7f3ff !important;
        border: 2px solid #007bff;
        border-radius: 4px;
        margin-bottom: 15px;
        font-size: 11pt;
    }
    
    .print-table {
        width: 100%;
        border-collapse: collapse;
        margin-bottom: 15px;
        font-size: 8pt;
    }
    .print-table th,
    .print-table td {
        border: 1px solid #ddd;
        padding: 5px 8px;
        vertical-align: middle;
    }
    .print-table thead th {
        background: #f0f0f0 !important;
        font-weight: bold;
        text-align: left;
    }
    .print-table .text-right {
        text-align: right;
    }
    .print-table .row-saldo-awal {
        background: #e9ecef !important;
    }
    .print-table .row-total {
        background: #343a40 !important;
        color: white !important;
    }
    .print-table .row-total th {
        color: white !important;
        border-color: #343a40 !important;
    }
    
    .section-title {
        margin-top: 20px;
        margin-bottom: 10px;
        padding-bottom: 5px;
        border-bottom: 2px solid #1a1a1a;
    }
    .section-title h3 {
        font-size: 10pt;
        font-weight: bold;
        margin: 0;
        text-transform: uppercase;
    }
    .section-title p {
        font-size: 8pt;
        color: #666;
        margin: 3px 0 0 0;
    }
    
    .table-category tbody td:last-child {
        font-weight: bold;
    }
    
    .print-footer {
        margin-top: 20px;
        padding-top: 10px;
        border-top: 1px solid #ddd;
        display: flex;
        justify-content: space-between;
        font-size: 8pt;
        color: #666;
    }
}
</style>