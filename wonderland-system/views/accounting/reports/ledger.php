<?php
/**
 * General Ledger - Buku Besar
 * File: /views/accounting/reports/ledger.php
 */
?>

<!-- Filter -->
<div class="glass-card mb-4">
    <form method="GET" class="row g-3 align-items-end">
        <div class="col-md-4">
            <label class="form-label small">Pilih Akun</label>
            <select name="account_id" class="form-control" required>
                <option value="">-- Pilih Akun --</option>
                <?php foreach ($accounts as $id => $name): ?>
                <option value="<?= $id ?>" <?= $selectedAccount == $id ? 'selected' : '' ?>><?= e($name) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-2">
            <label class="form-label small">Dari Tanggal</label>
            <input type="date" name="start_date" class="form-control" value="<?= e($startDate) ?>">
        </div>
        <div class="col-md-2">
            <label class="form-label small">Sampai Tanggal</label>
            <input type="date" name="end_date" class="form-control" value="<?= e($endDate) ?>">
        </div>
        <div class="col-md-4">
            <button type="submit" class="btn btn-primary">
                <i class="fas fa-filter me-1"></i> Tampilkan
            </button>
            <?php if ($selectedAccount): ?>
            <button type="button" class="btn btn-success ms-2" onclick="printReport()">
                <i class="fas fa-print me-1"></i> Cetak
            </button>
            <?php endif; ?>
        </div>
    </form>
</div>

<?php if ($selectedAccount && $account): ?>
<!-- Account Info -->
<div class="glass-card mb-4" id="printArea">
    <!-- Header untuk Print -->
    <div class="d-none d-print-block text-center mb-4">
        <h4 class="mb-1"><?= e(Session::companyName()) ?></h4>
        <h5 class="mb-1">BUKU BESAR</h5>
        <p class="mb-0"><?= e($account->code) ?> - <?= e($account->name) ?></p>
        <p class="text-muted small mb-0">Periode: <?= formatDate($startDate, 'd F Y') ?> s/d <?= formatDate($endDate, 'd F Y') ?></p>
    </div>
    
    <div class="d-flex justify-content-between align-items-center mb-4 d-print-none">
        <div>
            <h5 class="mb-1">
                <code class="me-2"><?= e($account->code) ?></code>
                <?= e($account->name) ?>
            </h5>
            <span class="badge bg-<?= $account->type === 'asset' ? 'primary' : ($account->type === 'liability' ? 'danger' : ($account->type === 'equity' ? 'success' : ($account->type === 'revenue' ? 'info' : 'warning'))) ?>">
                <?= ucfirst($account->type) ?>
            </span>
            <span class="badge bg-secondary ms-1">
                Normal: <?= ucfirst($account->normal_balance) ?>
            </span>
        </div>
        <div class="text-end">
            <div class="text-muted small">Saldo Awal</div>
            <div class="fs-5 fw-bold"><?= formatRupiah($openingBalance) ?></div>
        </div>
    </div>
    
    <!-- Transactions Table -->
    <div class="table-responsive">
        <table class="table table-bordered table-sm table-hover">
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
                <!-- Opening Balance Row -->
                <tr class="table-secondary">
                    <td><?= formatDate($startDate, 'd/m/Y') ?></td>
                    <td>-</td>
                    <td><em>Saldo Awal</em></td>
                    <td class="text-end">-</td>
                    <td class="text-end">-</td>
                    <td class="text-end fw-bold"><?= formatRupiah($openingBalance) ?></td>
                </tr>
                
                <?php 
                $runningBalance = $openingBalance;
                $totalDebit = 0;
                $totalCredit = 0;
                
                if (empty($transactions)): 
                ?>
                <tr>
                    <td colspan="6" class="text-center text-muted py-4">
                        <i class="fas fa-inbox fa-2x mb-2 d-block opacity-50"></i>
                        Tidak ada transaksi pada periode ini
                    </td>
                </tr>
                <?php else: ?>
                    <?php foreach ($transactions as $trx): 
                        $debit = (float)($trx['debit'] ?? 0);
                        $credit = (float)($trx['credit'] ?? 0);
                        
                        // Calculate running balance based on normal balance
                        if ($account->normal_balance === 'debit') {
                            $runningBalance += $debit - $credit;
                        } else {
                            $runningBalance += $credit - $debit;
                        }
                        
                        $totalDebit += $debit;
                        $totalCredit += $credit;
                    ?>
                    <tr>
                        <td><?= formatDate($trx['journal_date'], 'd/m/Y') ?></td>
                        <td>
                            <a href="<?= url('/accounting/journals/' . $trx['journal_id']) ?>" class="text-decoration-none">
                                <?= e($trx['journal_number']) ?>
                            </a>
                        </td>
                        <td>
                            <?= e($trx['description'] ?: $trx['journal_description'] ?: '-') ?>
                            <?php if (!empty($trx['reference'])): ?>
                            <br><small class="text-muted">Ref: <?= e($trx['reference']) ?></small>
                            <?php endif; ?>
                        </td>
                        <td class="text-end"><?= $debit > 0 ? formatRupiah($debit) : '-' ?></td>
                        <td class="text-end"><?= $credit > 0 ? formatRupiah($credit) : '-' ?></td>
                        <td class="text-end fw-bold <?= $runningBalance < 0 ? 'text-danger' : '' ?>">
                            <?= formatRupiah($runningBalance) ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
            <tfoot class="table-dark">
                <tr>
                    <th colspan="3" class="text-end">TOTAL</th>
                    <th class="text-end"><?= formatRupiah($totalDebit) ?></th>
                    <th class="text-end"><?= formatRupiah($totalCredit) ?></th>
                    <th class="text-end"><?= formatRupiah($runningBalance) ?></th>
                </tr>
            </tfoot>
        </table>
    </div>
    
    <!-- Summary -->
    <div class="row g-3 mt-3 d-print-none">
        <div class="col-md-3">
            <div class="border rounded p-3 text-center">
                <div class="text-muted small">Saldo Awal</div>
                <div class="fs-5 fw-bold"><?= formatRupiah($openingBalance) ?></div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="border rounded p-3 text-center bg-primary bg-opacity-10">
                <div class="text-muted small">Total Debit</div>
                <div class="fs-5 fw-bold text-primary"><?= formatRupiah($totalDebit) ?></div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="border rounded p-3 text-center bg-info bg-opacity-10">
                <div class="text-muted small">Total Kredit</div>
                <div class="fs-5 fw-bold text-info"><?= formatRupiah($totalCredit) ?></div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="border rounded p-3 text-center bg-success bg-opacity-10">
                <div class="text-muted small">Saldo Akhir</div>
                <div class="fs-5 fw-bold text-success"><?= formatRupiah($runningBalance) ?></div>
            </div>
        </div>
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

<?php else: ?>
<!-- Placeholder -->
<div class="glass-card text-center py-5">
    <i class="fas fa-book fa-4x text-muted mb-3 opacity-25"></i>
    <h5 class="text-muted">Pilih Akun untuk Melihat Buku Besar</h5>
    <p class="text-muted">Gunakan filter di atas untuk memilih akun dan periode yang ingin dilihat.</p>
</div>
<?php endif; ?>

<script>
function printReport() {
    window.print();
}
</script>

<style>
@media print {
    body { font-size: 10pt; }
    .glass-card { box-shadow: none !important; border: none !important; padding: 0 !important; }
    .table { font-size: 9pt; }
    .table th, .table td { padding: 4px 6px !important; }
    .d-print-none { display: none !important; }
    .d-print-block { display: block !important; }
    a { text-decoration: none !important; color: inherit !important; }
}
</style>