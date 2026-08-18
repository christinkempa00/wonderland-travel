<?php
/**
 * Income Statement - Laporan Laba Rugi (dengan HPP)
 * File: /views/accounting/reports/income-statement.php
 * 
 * Format:
 * - Pendapatan
 * - HPP (Harga Pokok Penjualan)
 * = Laba Kotor
 * - Beban Operasional
 * = Laba Bersih
 */
?>

<!-- Filter -->
<div class="glass-card mb-4">
    <form method="GET" class="row g-3 align-items-end">
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
            <button type="button" class="btn btn-success ms-2" onclick="printReport()">
                <i class="fas fa-print me-1"></i> Cetak
            </button>
        </div>
    </form>
</div>

<?php if (isset($hppSource) && $hppSource === 'orders'): ?>
<div class="alert alert-warning d-print-none">
    <i class="fas fa-exclamation-triangle me-2"></i>
    <strong>Perhatian:</strong> HPP dihitung dari data order karena jurnal HPP belum ada. 
    Jalankan migrasi <code>migrate-hpp-journals.php</code> untuk membuat jurnal HPP.
</div>
<?php endif; ?>

<!-- Report -->
<div class="glass-card" id="printArea">
    <!-- Header untuk Print -->
    <div class="d-none d-print-block text-center mb-4">
        <?php 
        $company = db()->fetchOne("SELECT name FROM companies WHERE id = ?", [Session::companyId()]);
        ?>
        <h4 class="mb-1"><?= e($company['name'] ?? 'Perusahaan') ?></h4>
        <h5 class="mb-1">LAPORAN LABA RUGI</h5>
        <p class="text-muted mb-0">Periode: <?= formatDate($startDate, 'd F Y') ?> s/d <?= formatDate($endDate, 'd F Y') ?></p>
    </div>
    
    <div class="table-responsive">
        <table class="table table-sm mb-0">
            <!-- PENDAPATAN -->
            <thead>
                <tr class="table-success">
                    <th colspan="2" class="fw-bold">
                        <i class="fas fa-arrow-down me-2"></i>PENDAPATAN
                    </th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($revenueAccounts)): ?>
                <tr>
                    <td colspan="2" class="text-muted text-center py-3">Tidak ada data pendapatan</td>
                </tr>
                <?php else: ?>
                    <?php foreach ($revenueAccounts as $acc): ?>
                    <?php if (abs($acc['balance']) > 0.01): ?>
                    <tr>
                        <td class="ps-4">
                            <code class="me-2"><?= e($acc['code']) ?></code>
                            <?= e($acc['name']) ?>
                        </td>
                        <td class="text-end" width="180"><?= formatRupiah($acc['balance']) ?></td>
                    </tr>
                    <?php endif; ?>
                    <?php endforeach; ?>
                <?php endif; ?>
                <tr class="table-light fw-bold">
                    <td class="ps-4">Total Pendapatan</td>
                    <td class="text-end"><?= formatRupiah($totalRevenue) ?></td>
                </tr>
            </tbody>
            
            <!-- HARGA POKOK PENJUALAN (HPP) -->
            <thead>
                <tr class="table-warning mt-3">
                    <th colspan="2" class="fw-bold">
                        <i class="fas fa-box me-2"></i>HARGA POKOK PENJUALAN (HPP)
                    </th>
                </tr>
            </thead>
            <tbody>
                <?php if ($totalHpp > 0): ?>
                    <?php if (!empty($hppAccounts) && array_sum(array_column($hppAccounts, 'balance')) > 0): ?>
                        <?php foreach ($hppAccounts as $acc): ?>
                        <?php if (abs($acc['balance']) > 0.01): ?>
                        <tr>
                            <td class="ps-4">
                                <code class="me-2"><?= e($acc['code']) ?></code>
                                <?= e($acc['name']) ?>
                            </td>
                            <td class="text-end"><?= formatRupiah($acc['balance']) ?></td>
                        </tr>
                        <?php endif; ?>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td class="ps-4">
                                <span class="text-muted">(Modal Vendor dari Data Order)</span>
                            </td>
                            <td class="text-end"><?= formatRupiah($totalHpp) ?></td>
                        </tr>
                    <?php endif; ?>
                <?php else: ?>
                <tr>
                    <td colspan="2" class="text-muted text-center py-2">Tidak ada HPP</td>
                </tr>
                <?php endif; ?>
                <tr class="table-light fw-bold">
                    <td class="ps-4">Total HPP</td>
                    <td class="text-end">(<?= formatRupiah($totalHpp) ?>)</td>
                </tr>
            </tbody>
            
            <!-- LABA KOTOR -->
            <tbody>
                <tr class="table-info fw-bold fs-6">
                    <td class="py-2">
                        <i class="fas fa-chart-bar me-2"></i>LABA KOTOR
                    </td>
                    <td class="text-end py-2"><?= formatRupiah($grossProfit ?? ($totalRevenue - $totalHpp)) ?></td>
                </tr>
            </tbody>
            
            <!-- BEBAN OPERASIONAL -->
            <thead>
                <tr class="table-danger mt-3">
                    <th colspan="2" class="fw-bold">
                        <i class="fas fa-arrow-up me-2"></i>BEBAN OPERASIONAL
                    </th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($operationalAccounts) || array_sum(array_column($operationalAccounts ?? [], 'balance')) < 0.01): ?>
                <tr>
                    <td colspan="2" class="text-muted text-center py-2">Tidak ada beban operasional</td>
                </tr>
                <?php else: ?>
                    <?php foreach ($operationalAccounts as $acc): ?>
                    <?php if (abs($acc['balance']) > 0.01): ?>
                    <tr>
                        <td class="ps-4">
                            <code class="me-2"><?= e($acc['code']) ?></code>
                            <?= e($acc['name']) ?>
                        </td>
                        <td class="text-end"><?= formatRupiah($acc['balance']) ?></td>
                    </tr>
                    <?php endif; ?>
                    <?php endforeach; ?>
                <?php endif; ?>
                <tr class="table-light fw-bold">
                    <td class="ps-4">Total Beban Operasional</td>
                    <td class="text-end">(<?= formatRupiah($totalOperational ?? 0) ?>)</td>
                </tr>
            </tbody>
            
            <!-- LABA/RUGI BERSIH -->
            <tfoot>
                <tr class="<?= $netIncome >= 0 ? 'table-success' : 'table-danger' ?> fs-5">
                    <th class="py-3">
                        <i class="fas <?= $netIncome >= 0 ? 'fa-chart-line' : 'fa-chart-line-down' ?> me-2"></i>
                        <?= $netIncome >= 0 ? 'LABA BERSIH' : 'RUGI BERSIH' ?>
                    </th>
                    <th class="text-end py-3"><?= formatRupiah(abs($netIncome)) ?></th>
                </tr>
            </tfoot>
        </table>
    </div>
    
    <!-- Summary Cards -->
    <div class="row g-3 mt-4 d-print-none">
        <div class="col-6 col-md-3">
            <div class="border rounded p-3 text-center bg-success bg-opacity-10">
                <div class="text-muted small">Pendapatan</div>
                <div class="fs-5 fw-bold text-success"><?= formatRupiah($totalRevenue) ?></div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="border rounded p-3 text-center bg-warning bg-opacity-10">
                <div class="text-muted small">HPP</div>
                <div class="fs-5 fw-bold text-warning"><?= formatRupiah($totalHpp) ?></div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="border rounded p-3 text-center bg-info bg-opacity-10">
                <div class="text-muted small">Laba Kotor</div>
                <div class="fs-5 fw-bold text-info"><?= formatRupiah($grossProfit ?? ($totalRevenue - $totalHpp)) ?></div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="border rounded p-3 text-center <?= $netIncome >= 0 ? 'bg-primary' : 'bg-danger' ?> bg-opacity-10">
                <div class="text-muted small"><?= $netIncome >= 0 ? 'Laba Bersih' : 'Rugi Bersih' ?></div>
                <div class="fs-5 fw-bold <?= $netIncome >= 0 ? 'text-primary' : 'text-danger' ?>"><?= formatRupiah(abs($netIncome)) ?></div>
            </div>
        </div>
    </div>
    
    <!-- Rumus Perhitungan -->
    <div class="mt-4 pt-3 border-top small text-muted d-print-none">
        <strong>Rumus:</strong><br>
        Laba Kotor = Pendapatan - HPP = <?= formatRupiah($totalRevenue) ?> - <?= formatRupiah($totalHpp) ?> = <?= formatRupiah($grossProfit ?? ($totalRevenue - $totalHpp)) ?><br>
        Laba Bersih = Laba Kotor - Beban Operasional = <?= formatRupiah($grossProfit ?? ($totalRevenue - $totalHpp)) ?> - <?= formatRupiah($totalOperational ?? 0) ?> = <?= formatRupiah($netIncome) ?>
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
    .d-print-none { display: none !important; }
    .d-print-block { display: block !important; }
}
</style>