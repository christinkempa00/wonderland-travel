<?php
/**
 * ================================================================
 * TRAVEL MANAGEMENT SYSTEM - Financial Report View
 * ================================================================
 * File: views/analysis/report.php
 * 
 * v4: Fixed checklist storage - now uses database instead of localStorage
 *     Checklist data persists across page refresh and browser sessions
 */

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
?>

<!-- CSRF Token -->
<input type="hidden" id="csrf_token" value="<?= e(Session::getCsrfToken()) ?>" class="no-print">

<!-- Print Header (only visible when printing) -->
<div class="print-header">
    <?php 
    $company = db()->fetchOne("SELECT * FROM companies WHERE id = ?", [Session::companyId()]);
    $logoFile = $company['logo'] ?? '';
    ?>
    <?php if ($logoFile): ?>
    <img src="<?= url('/uploads/' . $logoFile) ?>" alt="Logo" class="print-logo">
    <?php endif; ?>
    <h1><?= e($company['name'] ?? 'PIM Travel') ?></h1>
    <p>Laporan Keuangan - <?php 
        if (!empty($reportFilters['month']) && !empty($reportFilters['year'])) {
            echo $months[$reportFilters['month']] . ' ' . $reportFilters['year'];
        } elseif (!empty($reportFilters['year'])) {
            echo $reportFilters['year'];
        } elseif (!empty($reportFilters['month'])) {
            echo $months[$reportFilters['month']];
        } else {
            echo 'Semua Periode';
        }
    ?></p>
    <p style="font-size: 9pt;">Dicetak: <?= date('d/m/Y H:i') ?></p>
</div>

<!-- Store logo path for JS -->
<input type="hidden" id="companyLogoPath" value="<?= $logoFile ? url('/uploads/logos/' . $logoFile) : '' ?>">

<!-- Page Header -->
<div class="d-flex justify-content-between align-items-center mb-4 no-print">
    <div>
        <h4 class="mb-1"><i class="fas fa-file-invoice-dollar text-primary me-2"></i>Laporan Keuangan</h4>
        <p class="text-muted mb-0">Rekap dukungan, modal, dan rencana pengeluaran</p>
    </div>
    <a href="<?= url('/analysis') ?>" class="btn btn-outline-secondary">
        <i class="fas fa-arrow-left me-1"></i> Kembali
    </a>
</div>

<!-- Filter Periode -->
<div class="glass-card mb-4 no-print">
    <form method="GET" action="<?= url('/analysis/report') ?>" class="row g-2 align-items-end p-3">
        <div class="col-auto">
            <label class="form-label small">Tahun</label>
            <select name="year" class="form-control form-control-sm">
                <option value="">Semua Tahun</option>
                <?php foreach ($availableYears as $y): ?>
                <option value="<?= $y['year'] ?>" <?= ($reportFilters['year'] ?? '') == $y['year'] ? 'selected' : '' ?>>
                    <?= $y['year'] ?>
                </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-auto">
            <label class="form-label small">Bulan</label>
            <select name="month" class="form-control form-control-sm">
                <option value="">Semua Bulan</option>
                <?php foreach ($months as $num => $name): ?>
                <option value="<?= $num ?>" <?= ($reportFilters['month'] ?? '') == $num ? 'selected' : '' ?>>
                    <?= $name ?>
                </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-auto">
            <button type="submit" class="btn btn-primary btn-sm">
                <i class="fas fa-filter me-1"></i> Filter
            </button>
        </div>
        <div class="col-auto ms-auto">
            <button type="button" class="btn btn-outline-success btn-sm" onclick="printReport()">
                <i class="fas fa-print me-1"></i> Cetak Laporan
            </button>
            <a href="<?= url('/analysis/export-report?' . http_build_query($reportFilters)) ?>" class="btn btn-outline-info btn-sm">
                <i class="fas fa-download me-1"></i> Export
            </a>
        </div>
    </form>
</div>

<!-- Summary Cards -->
<div class="row mb-4 summary-row">
    <div class="col-6 col-md-4 col-xl-2 mb-3">
        <div class="summary-card">
            <div class="summary-icon bg-primary-soft"><i class="fas fa-hands-helping"></i></div>
            <div class="summary-content">
                <span class="summary-value"><?= number_format($grandTotals['total_dukungan'] ?? 0) ?></span>
                <span class="summary-label">Dukungan</span>
                <small class="text-muted"><?= number_format($grandTotals['total_orders'] ?? 0) ?> order</small>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-4 col-xl-2 mb-3">
        <div class="summary-card">
            <div class="summary-icon bg-warning-soft"><i class="fas fa-coins"></i></div>
            <div class="summary-content">
                <span class="summary-value"><?= formatRupiahShort($grandTotals['total_modal'] ?? 0) ?></span>
                <span class="summary-label">Total Modal</span>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-4 col-xl-2 mb-3">
        <div class="summary-card">
            <div class="summary-icon bg-info-soft"><i class="fas fa-file-invoice-dollar"></i></div>
            <div class="summary-content">
                <span class="summary-value"><?= formatRupiahShort($grandTotals['total_tagihan'] ?? 0) ?></span>
                <span class="summary-label">Total Tagihan</span>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-4 col-xl-2 mb-3">
        <div class="summary-card border-success">
            <div class="summary-icon bg-success-soft"><i class="fas fa-hand-holding-usd"></i></div>
            <div class="summary-content">
                <span class="summary-value text-success"><?= formatRupiahShort($grandTotals['total_profit'] ?? 0) ?></span>
                <span class="summary-label">Total Profit</span>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-4 col-xl-2 mb-3">
        <div class="summary-card">
            <div class="summary-icon bg-teal-soft"><i class="fas fa-money-bill-wave"></i></div>
            <div class="summary-content">
                <span class="summary-value"><?= formatRupiahShort($grandTotals['total_dibayar'] ?? 0) ?></span>
                <span class="summary-label">Dibayar</span>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-4 col-xl-2 mb-3">
        <div class="summary-card border-danger">
            <div class="summary-icon bg-danger-soft"><i class="fas fa-clock"></i></div>
            <div class="summary-content">
                <span class="summary-value text-danger"><?= formatRupiahShort($grandTotals['total_sisa'] ?? 0) ?></span>
                <span class="summary-label">Belum Dibayar</span>
            </div>
        </div>
    </div>
</div>

<!-- Main Content Grid -->
<div class="row print-stack">
    <!-- Rekap per Dukungan -->
    <div class="col-lg-8 mb-4">
        <div class="glass-card">
            <div class="card-header-custom">
                <h6 class="mb-0"><i class="fas fa-list-alt text-primary me-2"></i>Rekap per Dukungan</h6>
            </div>
            
            <?php if (empty($supportStats)): ?>
            <div class="empty-state py-4">
                <i class="fas fa-inbox fa-2x text-muted mb-2"></i>
                <p class="text-muted mb-0">Tidak ada data dukungan</p>
            </div>
            <?php else: ?>
            <div class="table-responsive">
                <table class="table table-hover mb-0" id="tabelDukungan">
                    <thead>
                        <tr>
                            <th class="text-center" style="width:35px;">No</th>
                            <th>Dukungan</th>
                            <th class="text-center no-print">Orders</th>
                            <th class="text-end">Modal</th>
                            <th class="text-end">Tagihan</th>
                            <th class="text-end">Profit</th>
                            <th class="text-center" style="width:50px;">Clear</th>
                            <th class="text-center" style="width:60px;">Diajukan</th>
                            <th style="width:150px;">Catatan</th>
                            <th class="text-center no-print" style="width:40px;">Print</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php $noUrut = 1; foreach ($supportStats as $stat): 
                            $profitPct = ($stat['total_modal'] > 0) ? (($stat['total_profit'] / $stat['total_modal']) * 100) : 0;
                            $supportKey = md5($stat['support_name']); // Key untuk checklist
                        ?>
                        <tr data-support-key="<?= $supportKey ?>" data-support-name="<?= e($stat['support_name']) ?>" data-tagihan="<?= $stat['total_tagihan'] ?>">
                            <td class="text-center"><?= $noUrut++ ?></td>
                            <td>
                                <strong><?= e($stat['support_name']) ?></strong>
                            </td>
                            <td class="text-center no-print">
                                <span class="badge badge-primary"><?= $stat['total_orders'] ?></span>
                            </td>
                            <td class="text-end"><?= formatRupiah($stat['total_modal']) ?></td>
                            <td class="text-end"><?= formatRupiah($stat['total_tagihan']) ?></td>
                            <td class="text-end">
                                <?= formatRupiah($stat['total_profit']) ?>
                                <small class="d-block text-muted profit-pct">(<?= number_format($profitPct, 1) ?>%)</small>
                            </td>
                            <td class="text-center checklist-col">
                                <button type="button" class="btn btn-sm checklist-btn clear-btn" 
                                        data-key="<?= $supportKey ?>" 
                                        data-type="clear"
                                        onclick="toggleCheck(this)">
                                    <i class="far fa-square"></i>
                                </button>
                                <span class="print-check" data-key="<?= $supportKey ?>" data-type="clear">☐</span>
                            </td>
                            <td class="text-center checklist-col">
                                <button type="button" class="btn btn-sm checklist-btn submitted-btn" 
                                        data-key="<?= $supportKey ?>" 
                                        data-type="submitted"
                                        onclick="toggleCheck(this)">
                                    <i class="far fa-square"></i>
                                </button>
                                <span class="print-check" data-key="<?= $supportKey ?>" data-type="submitted">☐</span>
                            </td>
                            <td class="catatan-col">
                                <input type="text" class="form-control form-control-sm catatan-input" 
                                       data-key="<?= $supportKey ?>"
                                       placeholder="Catatan..."
                                       onchange="saveCatatanRow(this)">
                                <span class="catatan-print-text" data-key="<?= $supportKey ?>"></span>
                            </td>
                            <td class="text-center no-print">
                                <button type="button" class="btn btn-sm btn-outline-secondary btn-print-checklist" onclick="printChecklist('<?= e(addslashes($stat['support_name'])) ?>')" title="Print Checklist">
                                    <i class="fas fa-print"></i>
                                </button>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                    <tfoot class="table-light">
                        <tr class="fw-bold">
                            <td class="text-center">-</td>
                            <td>TOTAL</td>
                            <td class="text-center no-print"><?= $grandTotals['total_orders'] ?? 0 ?></td>
                            <td class="text-end"><?= formatRupiah($grandTotals['total_modal'] ?? 0) ?></td>
                            <td class="text-end"><?= formatRupiah($grandTotals['total_tagihan'] ?? 0) ?></td>
                            <td class="text-end"><?= formatRupiah($grandTotals['total_profit'] ?? 0) ?></td>
                            <td class="text-center" id="totalClear">0</td>
                            <td class="text-center" id="totalSubmitted">0</td>
                            <td></td>
                            <td class="no-print"></td>
                        </tr>
                    </tfoot>
                </table>
            </div>
            
            <!-- Checklist Legend & Summary -->
            <div class="p-2 border-top no-print">
                <div class="d-flex flex-wrap align-items-center gap-3 small text-muted">
                    <span><i class="fas fa-check-square text-success me-1"></i> Sudah</span>
                    <span><i class="far fa-square text-secondary me-1"></i> Belum</span>
                    <span class="ms-auto">
                        <button type="button" class="btn btn-xs btn-outline-secondary" onclick="resetAllChecklist()">
                            <i class="fas fa-undo me-1"></i> Reset
                        </button>
                    </span>
                </div>
                <div class="mt-2 pt-2 border-top">
                    <div class="row text-center small">
                        <div class="col-6">
                            <span class="text-muted d-block">Total Diajukan</span>
                            <strong class="text-success fs-6" id="totalTagihanSubmitted">Rp 0</strong>
                        </div>
                        <div class="col-6">
                            <span class="text-muted d-block">Belum Diajukan</span>
                            <strong class="text-danger fs-6" id="totalTagihanPending">Rp 0</strong>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Print Version: Total Diajukan -->
            <div class="p-2 border-top print-only">
                <div class="row text-center small mb-2">
                    <div class="col-6">
                        <span class="text-muted d-block">Total Diajukan</span>
                        <strong id="printTotalSubmitted">Rp 0</strong>
                    </div>
                    <div class="col-6">
                        <span class="text-muted d-block">Belum Diajukan</span>
                        <strong id="printTotalPending">Rp 0</strong>
                    </div>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- Draft Rencana Pengeluaran (di kanan) -->
    <div class="col-lg-4 mb-4">
        <div class="glass-card">
            <div class="card-header-custom d-flex justify-content-between align-items-center">
                <h6 class="mb-0"><i class="fas fa-clipboard-list text-warning me-2"></i>Draft Pengeluaran</h6>
                <?php if ($hasDraftTable): ?>
                <button type="button" class="btn btn-sm btn-primary no-print" onclick="openDraftModal()">
                    <i class="fas fa-plus"></i>
                </button>
                <?php endif; ?>
            </div>
            
            <?php if (!$hasDraftTable): ?>
            <div class="p-3 no-print">
                <div class="alert alert-info mb-0">
                    <i class="fas fa-info-circle me-2"></i>
                    <small>Tabel belum ada.</small>
                    <button type="button" class="btn btn-sm btn-info ms-2" onclick="createDraftTable()">
                        <i class="fas fa-database"></i> Buat
                    </button>
                </div>
            </div>
            <?php elseif (empty($draftExpenses)): ?>
            <div class="empty-state py-4 no-print">
                <i class="fas fa-clipboard fa-2x text-muted mb-2"></i>
                <p class="text-muted mb-0 small">Belum ada draft</p>
                <button type="button" class="btn btn-sm btn-outline-primary mt-2" onclick="openDraftModal()">
                    <i class="fas fa-plus me-1"></i> Tambah
                </button>
            </div>
            <div class="p-3 print-only" style="display:none;">
                <p class="text-muted mb-0">Tidak ada draft pengeluaran</p>
            </div>
            <?php else: ?>
            <!-- Screen View - Card Style -->
            <div class="draft-list no-print">
                <?php 
                $priorityColors = ['low' => 'secondary', 'medium' => 'warning', 'high' => 'danger'];
                $priorityLabels = ['low' => 'Rendah', 'medium' => 'Sedang', 'high' => 'Tinggi'];
                
                foreach ($draftExpenses as $draft): 
                    $priority = $draft['priority'] ?? 'medium';
                ?>
                <div class="draft-item">
                    <div class="draft-header">
                        <span class="draft-title"><?= e($draft['title']) ?></span>
                        <span class="badge badge-<?= $priorityColors[$priority] ?> badge-sm"><?= $priorityLabels[$priority] ?></span>
                    </div>
                    <div class="draft-body">
                        <small class="text-muted d-block"><?= formatDate($draft['planned_date']) ?></small>
                        <span class="fw-bold text-primary"><?= formatRupiah($draft['estimated_amount']) ?></span>
                    </div>
                    <div class="draft-actions">
                        <button type="button" class="btn btn-xs btn-outline-primary" onclick="editDraft(<?= $draft['id'] ?>)">
                            <i class="fas fa-edit"></i>
                        </button>
                        <button type="button" class="btn btn-xs btn-outline-danger" onclick="deleteDraft(<?= $draft['id'] ?>)">
                            <i class="fas fa-trash"></i>
                        </button>
                    </div>
                </div>
                <?php endforeach; ?>
                
                <div class="draft-total">
                    <span>Total Rencana:</span>
                    <strong class="text-danger"><?= formatRupiah(array_sum(array_column($draftExpenses, 'estimated_amount'))) ?></strong>
                </div>
            </div>
            
            <!-- Print View - Table Style -->
            <div class="table-responsive print-draft-table">
                <table class="table table-bordered mb-0">
                    <thead>
                        <tr>
                            <th style="width: 40px;">No</th>
                            <th>Rencana Pengeluaran</th>
                            <th class="text-center" style="width: 100px;">Tanggal</th>
                            <th class="text-end" style="width: 130px;">Estimasi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php $no = 1; foreach ($draftExpenses as $draft): ?>
                        <tr>
                            <td class="text-center"><?= $no++ ?></td>
                            <td><?= e($draft['title']) ?></td>
                            <td class="text-center"><?= formatDate($draft['planned_date']) ?></td>
                            <td class="text-end"><?= formatRupiah($draft['estimated_amount']) ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                    <tfoot>
                        <tr class="fw-bold">
                            <td colspan="3" class="text-end">TOTAL</td>
                            <td class="text-end"><?= formatRupiah(array_sum(array_column($draftExpenses, 'estimated_amount'))) ?></td>
                        </tr>
                    </tfoot>
                </table>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Rekap Bulanan -->
<div class="glass-card mb-4">
    <div class="card-header-custom d-flex justify-content-between align-items-center">
        <h6 class="mb-0"><i class="fas fa-calendar-alt text-success me-2"></i>Rekap Bulanan <?= !empty($reportFilters['year']) ? $reportFilters['year'] : '' ?></h6>
    </div>
    
    <?php if (empty($monthlyStats)): ?>
    <div class="empty-state py-4">
        <i class="fas fa-calendar-times fa-2x text-muted mb-2"></i>
        <p class="text-muted mb-0">Tidak ada data bulanan</p>
    </div>
    <?php else: ?>
    <div class="table-responsive">
        <table class="table table-hover mb-0">
            <thead>
                <tr>
                    <th class="text-center" style="width:45px;">No</th>
                    <th>Bulan</th>
                    <th class="text-center no-print">Orders</th>
                    <th class="text-end">Modal</th>
                    <th class="text-end">Tagihan</th>
                    <th class="text-end">Profit</th>
                    <th class="no-print" style="width: 150px;">Grafik</th>
                </tr>
            </thead>
            <tbody>
                <?php 
                $noBulan = 1;
                $maxTagihan = !empty($monthlyStats) ? max(array_column($monthlyStats, 'total_tagihan')) : 0;
                foreach ($monthlyStats as $mstat): 
                    $barWidth = ($maxTagihan > 0) ? (($mstat['total_tagihan'] / $maxTagihan) * 100) : 0;
                ?>
                <tr>
                    <td class="text-center"><?= $noBulan++ ?></td>
                    <td>
                        <strong><?= $months[$mstat['bulan']] ?? $mstat['nama_bulan'] ?></strong>
                    </td>
                    <td class="text-center no-print">
                        <span class="badge badge-light"><?= $mstat['total_orders'] ?></span>
                    </td>
                    <td class="text-end"><?= formatRupiah($mstat['total_modal']) ?></td>
                    <td class="text-end"><?= formatRupiah($mstat['total_tagihan']) ?></td>
                    <td class="text-end"><?= formatRupiah($mstat['total_profit']) ?></td>
                    <td class="no-print">
                        <div class="progress" style="height: 20px;">
                            <div class="progress-bar bg-primary" style="width: <?= $barWidth ?>%"></div>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php endif; ?>
</div>

<!-- Simulasi Pembagian Dana -->
<div class="glass-card mb-4">
    <div class="card-header-custom d-flex justify-content-between align-items-center">
        <div>
            <h6 class="mb-0"><i class="fas fa-calculator text-purple me-2"></i>Simulasi Pembagian Dana</h6>
            <small class="text-muted" id="simulasiLastSaved"></small>
        </div>
        <div class="no-print">
            <button type="button" class="btn btn-sm btn-success me-1" onclick="simpanSimulasi()" id="btnSimpanSimulasi">
                <i class="fas fa-save me-1"></i> Simpan
            </button>
            <button type="button" class="btn btn-sm btn-outline-primary" onclick="addPembagianRow()">
                <i class="fas fa-plus me-1"></i> Tambah
            </button>
        </div>
    </div>
    
    <div class="p-3">
        <!-- Info Dana -->
        <div class="row mb-4 simulasi-info-row">
            <div class="col-md-4 mb-3">
                <div class="info-box info-box-primary">
                    <div class="info-box-icon"><i class="fas fa-file-invoice-dollar"></i></div>
                    <div class="info-box-content">
                        <span class="info-box-label">Total Tagihan</span>
                        <span class="info-box-value" id="simTotalTagihan"><?= formatRupiah($grandTotals['total_tagihan'] ?? 0) ?></span>
                    </div>
                </div>
            </div>
            <div class="col-md-4 mb-3">
                <div class="info-box info-box-success">
                    <div class="info-box-icon"><i class="fas fa-hand-holding-usd"></i></div>
                    <div class="info-box-content">
                        <span class="info-box-label">Total Profit Awal</span>
                        <span class="info-box-value" id="simTotalProfit"><?= formatRupiah($grandTotals['total_profit'] ?? 0) ?></span>
                    </div>
                </div>
            </div>
            <div class="col-md-4 mb-3">
                <div class="info-box info-box-warning">
                    <div class="info-box-icon"><i class="fas fa-piggy-bank"></i></div>
                    <div class="info-box-content">
                        <span class="info-box-label">Sisa Profit</span>
                        <span class="info-box-value" id="simSisaProfit"><?= formatRupiah($grandTotals['total_profit'] ?? 0) ?></span>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Tabel Pembagian -->
        <div class="table-responsive">
            <table class="table table-bordered mb-0" id="pembagianTable">
                <thead class="table-light">
                    <tr>
                        <th style="width: 40px;">#</th>
                        <th>Nama Pembagian</th>
                        <th style="width: 120px;" class="text-center">Persentase (%)</th>
                        <th style="width: 180px;" class="text-end">Jumlah (dari Tagihan)</th>
                        <th style="width: 60px;" class="text-center no-print">Aksi</th>
                    </tr>
                </thead>
                <tbody id="pembagianBody">
                    <!-- Rows akan ditambahkan via JavaScript -->
                </tbody>
                <tfoot class="table-light">
                    <tr class="fw-bold">
                        <td colspan="2" class="text-end">Total Pembagian:</td>
                        <td class="text-center" id="totalPersen">0%</td>
                        <td class="text-end text-danger" id="totalPembagian">Rp 0</td>
                        <td class="no-print"></td>
                    </tr>
                    <tr class="fw-bold table-success">
                        <td colspan="3" class="text-end">Sisa Profit Setelah Pembagian:</td>
                        <td class="text-end text-success" id="sisaProfitFinal">Rp 0</td>
                        <td class="no-print"></td>
                    </tr>
                </tfoot>
            </table>
        </div>
        
        <!-- Preset Buttons -->
        <div class="mt-3 pt-3 border-top no-print">
            <small class="text-muted me-2">Preset:</small>
            <button type="button" class="btn btn-xs btn-outline-secondary me-1" onclick="loadPreset('marketing')">Marketing 10%</button>
            <button type="button" class="btn btn-xs btn-outline-secondary me-1" onclick="loadPreset('operasional')">Operasional 15%</button>
            <button type="button" class="btn btn-xs btn-outline-secondary me-1" onclick="loadPreset('komisi')">Komisi 5%</button>
            <button type="button" class="btn btn-xs btn-outline-secondary me-1" onclick="loadPreset('pajak')">Pajak 10%</button>
            <button type="button" class="btn btn-xs btn-outline-danger ms-2" onclick="clearAllPembagian()">
                <i class="fas fa-trash me-1"></i> Reset
            </button>
        </div>
    </div>
</div>

<!-- Modal Tambah/Edit Draft (Native, tanpa Bootstrap JS) -->
<div class="custom-modal" id="draftModal">
    <div class="custom-modal-backdrop" onclick="closeDraftModal()"></div>
    <div class="custom-modal-content">
        <div class="custom-modal-header">
            <h5><i class="fas fa-clipboard-list me-2"></i>Draft Rencana Pengeluaran</h5>
            <button type="button" class="custom-modal-close" onclick="closeDraftModal()">&times;</button>
        </div>
        <form id="draftForm" onsubmit="submitDraftForm(event)">
            <div class="custom-modal-body">
                <input type="hidden" name="draft_id" id="draft_id">
                <input type="hidden" name="_token" value="<?= e(Session::getCsrfToken()) ?>">
                
                <div class="mb-3">
                    <label class="form-label">Judul Rencana <span class="text-danger">*</span></label>
                    <input type="text" name="title" id="draft_title" class="form-control" required placeholder="Contoh: Sewa Bus untuk Kegiatan X">
                </div>
                
                <div class="mb-3">
                    <label class="form-label">Deskripsi</label>
                    <textarea name="description" id="draft_description" class="form-control" rows="2" placeholder="Keterangan tambahan..."></textarea>
                </div>
                
                <div class="row">
                    <div class="col-6 mb-3">
                        <label class="form-label">Kategori</label>
                        <select name="category" id="draft_category" class="form-control">
                            <option value="transport">Transportasi</option>
                            <option value="accommodation">Akomodasi</option>
                            <option value="ticket">Tiket</option>
                            <option value="operational">Operasional</option>
                            <option value="other">Lainnya</option>
                        </select>
                    </div>
                    <div class="col-6 mb-3">
                        <label class="form-label">Prioritas</label>
                        <select name="priority" id="draft_priority" class="form-control">
                            <option value="low">Rendah</option>
                            <option value="medium" selected>Sedang</option>
                            <option value="high">Tinggi</option>
                        </select>
                    </div>
                </div>
                
                <div class="row">
                    <div class="col-6 mb-3">
                        <label class="form-label">Tanggal Rencana <span class="text-danger">*</span></label>
                        <input type="date" name="planned_date" id="draft_planned_date" class="form-control" required>
                    </div>
                    <div class="col-6 mb-3">
                        <label class="form-label">Estimasi Biaya <span class="text-danger">*</span></label>
                        <input type="text" name="estimated_amount" id="draft_estimated_amount" class="form-control" required placeholder="0" oninput="formatRupiahInput(this)">
                    </div>
                </div>
                
                <div class="mb-3">
                    <label class="form-label">Dukungan Terkait</label>
                    <input type="text" name="support_for" id="draft_support_for" class="form-control" placeholder="Nama dukungan jika ada...">
                </div>
            </div>
            <div class="custom-modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeDraftModal()">Batal</button>
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save me-1"></i> Simpan
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Hidden Checklist Template for Print -->
<div id="checklistPrintArea" style="display: none;"></div>

<style>
/* Card Header */
.card-header-custom {
    padding: 1rem 1.25rem;
    border-bottom: 1px solid rgba(0,0,0,.08);
    background: rgba(0,0,0,.02);
}

/* Summary Cards */
.summary-card{background:#fff;border-radius:12px;padding:.875rem;display:flex;align-items:center;gap:.625rem;box-shadow:0 2px 8px rgba(0,0,0,.05);border:1px solid #e5e7eb;height:100%}
.summary-card.border-danger{border-color:#fca5a5}.summary-card.border-success{border-color:#86efac}
.summary-icon{width:40px;height:40px;border-radius:10px;display:flex;align-items:center;justify-content:center;font-size:1rem;flex-shrink:0}
.summary-content{display:flex;flex-direction:column;min-width:0}
.summary-value{font-size:1rem;font-weight:700;color:#1f2937;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
.summary-label{font-size:.65rem;color:#6b7280;text-transform:uppercase}

/* Screen only / Print only toggles */
.print-only { display: none !important; }
.screen-only { display: inline !important; }

/* Background colors */
.bg-primary-soft { background: rgba(59, 130, 246, 0.1); color: #2563eb; }
.bg-info-soft { background: rgba(6, 182, 212, 0.1); color: #0891b2; }
.bg-warning-soft { background: rgba(245, 158, 11, 0.1); color: #d97706; }
.bg-success-soft { background: rgba(34, 197, 94, 0.1); color: #16a34a; }
.bg-danger-soft { background: rgba(239, 68, 68, 0.1); color: #dc2626; }
.bg-purple-soft { background: rgba(139, 92, 246, 0.1); color: #7c3aed; }
.bg-teal-soft { background: rgba(20, 184, 166, 0.1); color: #0d9488; }

.text-warning-dark { color: #b45309; }
.text-purple { color: #7c3aed; }
.fw-semibold { font-weight: 600; }

/* Info Box untuk Simulasi */
.info-box {
    display: flex;
    align-items: center;
    padding: 1rem;
    border-radius: 10px;
    background: #fff;
    border: 1px solid #e5e7eb;
    height: 100%;
}

.info-box-primary {
    background: linear-gradient(135deg, #eff6ff 0%, #dbeafe 100%);
    border-color: #93c5fd;
}

.info-box-success {
    background: linear-gradient(135deg, #f0fdf4 0%, #dcfce7 100%);
    border-color: #86efac;
}

.info-box-warning {
    background: linear-gradient(135deg, #fffbeb 0%, #fef3c7 100%);
    border-color: #fcd34d;
}

.info-box-icon {
    width: 50px;
    height: 50px;
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.25rem;
    margin-right: 1rem;
    flex-shrink: 0;
}

.info-box-primary .info-box-icon {
    background: #3b82f6;
    color: #fff;
}

.info-box-success .info-box-icon {
    background: #22c55e;
    color: #fff;
}

.info-box-warning .info-box-icon {
    background: #f59e0b;
    color: #fff;
}

.info-box-content {
    flex: 1;
    min-width: 0;
}

.info-box-label {
    display: block;
    font-size: 0.75rem;
    color: #6b7280;
    text-transform: uppercase;
    margin-bottom: 2px;
}

.info-box-value {
    display: block;
    font-size: 1.1rem;
    font-weight: 700;
    color: #1f2937;
}

/* Pembagian Table */
#pembagianTable input {
    text-align: center;
}

#pembagianTable .nama-input {
    text-align: left;
}

.btn-xs {
    padding: 0.25rem 0.5rem;
    font-size: 0.75rem;
}

/* Checklist Styling */
.checklist-col {
    position: relative;
}

.checklist-btn {
    background: transparent;
    border: none;
    padding: 2px 6px;
    cursor: pointer;
    transition: transform 0.15s ease;
    font-size: 1.1rem;
    color: #9ca3af;
}

.checklist-btn:hover {
    transform: scale(1.15);
}

.checklist-btn.checked {
    color: #22c55e;
}

.checklist-btn.checked i:before {
    content: "\f14a"; /* fa-check-square */
}

.print-check {
    display: none;
    font-size: 14px;
}

/* Print Only elements - hidden on screen */
.print-only {
    display: none !important;
}

/* Catatan column */
.catatan-col {
    position: relative;
}

.catatan-input {
    font-size: 0.75rem !important;
    padding: 2px 6px !important;
    border: 1px solid #e5e7eb;
    border-radius: 3px;
    width: 100%;
}

.catatan-input:focus {
    border-color: #3b82f6;
    box-shadow: 0 0 0 1px rgba(59, 130, 246, 0.2);
    outline: none;
}

.catatan-print-text {
    display: none;
    font-size: 0.7rem;
    color: #4b5563;
    font-style: italic;
}

/* Row highlight when both checked */
tr.row-complete {
    background: rgba(34, 197, 94, 0.1) !important;
}

tr.row-complete td {
    background: rgba(34, 197, 94, 0.1) !important;
}

/* Draft List */
.draft-list {
    /* Tidak ada batasan tinggi - tampilkan semua */
}

.draft-item {
    padding: 0.75rem 1rem;
    border-bottom: 1px solid #f3f4f6;
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    gap: 0.5rem;
}

.draft-item:last-child {
    border-bottom: none;
}

.draft-header {
    flex: 1;
    min-width: 150px;
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.draft-title {
    font-size: 0.85rem;
}

.draft-body {
    text-align: right;
}

.draft-actions {
    display: flex;
    gap: 0.25rem;
}

.draft-total {
    padding: 0.75rem 1rem;
    background: #f8fafc;
    border-top: 2px solid #e5e7eb;
    display: flex;
    justify-content: space-between;
    align-items: center;
    font-size: 0.85rem;
}

.btn-xs {
    padding: 0.2rem 0.4rem;
    font-size: 0.7rem;
}

.badge-sm {
    font-size: 0.65rem;
    padding: 0.2rem 0.4rem;
}

/* Custom Modal (tanpa Bootstrap) */
.custom-modal {
    display: none;
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    z-index: 9999;
    align-items: center;
    justify-content: center;
}

.custom-modal.show {
    display: flex;
}

.custom-modal-backdrop {
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(0,0,0,0.5);
}

.custom-modal-content {
    position: relative;
    background: #fff;
    border-radius: 12px;
    width: 90%;
    max-width: 500px;
    max-height: 90vh;
    overflow-y: auto;
    box-shadow: 0 10px 40px rgba(0,0,0,0.2);
    animation: modalSlideIn 0.3s ease;
}

@keyframes modalSlideIn {
    from { transform: translateY(-20px); opacity: 0; }
    to { transform: translateY(0); opacity: 1; }
}

.custom-modal-header {
    padding: 1rem 1.25rem;
    border-bottom: 1px solid #e5e7eb;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.custom-modal-header h5 {
    margin: 0;
    font-size: 1rem;
}

.custom-modal-close {
    background: none;
    border: none;
    font-size: 1.5rem;
    cursor: pointer;
    color: #6b7280;
    line-height: 1;
}

.custom-modal-close:hover {
    color: #1f2937;
}

.custom-modal-body {
    padding: 1.25rem;
}

.custom-modal-footer {
    padding: 1rem 1.25rem;
    border-top: 1px solid #e5e7eb;
    display: flex;
    justify-content: flex-end;
    gap: 0.5rem;
}

/* Badges */
.badge-light { background: #f3f4f6; border: 1px solid #e5e7eb; color: #374151; }
.badge-primary { background: #3b82f6; color: #fff; }
.badge-success { background: #22c55e; color: #fff; }
.badge-warning { background: #f59e0b; color: #fff; }
.badge-danger { background: #ef4444; color: #fff; }
.badge-secondary { background: #6b7280; color: #fff; }

/* Empty State */
.empty-state {
    text-align: center;
    padding: 2rem;
}


/* Toast */
.toast-notification{position:fixed;bottom:20px;right:20px;background:#1f2937;color:#fff;padding:10px 16px;border-radius:8px;display:flex;align-items:center;gap:8px;box-shadow:0 4px 20px rgba(0,0,0,.2);transform:translateY(100px);opacity:0;transition:all .3s;z-index:10000;font-size:.85rem;max-width:90vw}
.toast-notification.show{transform:translateY(0);opacity:1}
.toast-success{background:#059669}.toast-error{background:#dc2626}.toast-warning{background:#d97706}

/* ================================================================
   PRINT STYLES - Laporan Keuangan (PROFESSIONAL v5)
   ================================================================ */

/* Screen: Hide print-only elements */
.print-header, .print-draft-table {
    display: none !important;
}

@media print {
    /* ============================================
       BASE SETTINGS
       ============================================ */
    * {
        -webkit-print-color-adjust: exact !important;
        print-color-adjust: exact !important;
        box-sizing: border-box !important;
    }
    
    @page {
        size: A4 portrait;
        margin: 8mm 10mm 8mm 10mm;
    }
    
    /* ============================================
       HIDE ELEMENTS
       ============================================ */
    .sidebar, .sidebar-overlay, .navbar, .topbar,
    .custom-modal, .toast-notification, #checklistPrintArea,
    .draft-list, .draft-actions,
    .progress, .badge,
    .screen-only,
    button, .btn, 
    form,
    form.no-print,
    input[type="hidden"],
    input[type="text"],
    input[type="number"],
    select,
    textarea,
    label.form-label,
    .filter-form,
    .no-print:not(th):not(td) {
        display: none !important;
        visibility: hidden !important;
    }
    
    th.no-print, td.no-print {
        display: none !important;
    }
    
    /* Exclude inputs inside tables that need to show values */
    #pembagianTable input {
        display: inline !important;
        visibility: visible !important;
    }
    
    /* ============================================
       SHOW PRINT ELEMENTS
       ============================================ */
    .print-header,
    .print-draft-table,
    .print-only {
        display: block !important;
        visibility: visible !important;
    }
    
    /* ============================================
       BODY & LAYOUT - PREVENT BLANK FIRST PAGE
       ============================================ */
    html {
        margin: 0 !important;
        padding: 0 !important;
        height: auto !important;
    }
    
    body {
        width: 100% !important;
        height: auto !important;
        margin: 0 !important;
        padding: 0 !important;
        background: #fff !important;
        font-family: 'Segoe UI', Tahoma, Arial, sans-serif !important;
        font-size: 8.5pt !important;
        line-height: 1.35 !important;
        color: #000 !important;
        position: relative !important;
        overflow: visible !important;
    }
    
    /* Reset ALL wrapper elements */
    .app-wrapper, 
    .main-content, 
    .content-wrapper,
    .container, 
    .container-fluid,
    .content,
    main,
    #app,
    #main,
    .page-content,
    .wrapper {
        margin: 0 !important;
        padding: 0 !important;
        width: 100% !important;
        max-width: 100% !important;
        min-height: 0 !important;
        height: auto !important;
        background: #fff !important;
        float: none !important;
        display: block !important;
        position: static !important;
        transform: none !important;
        overflow: visible !important;
        left: 0 !important;
        top: 0 !important;
        margin-left: 0 !important;
    }
    
    /* ============================================
       PRINT HEADER - Elegant Dark Style
       ============================================ */
    .print-header {
        display: block !important;
        text-align: center;
        margin: 0 0 10px 0 !important;
        padding: 12px 15px 10px 15px !important;
        background: #1a1a1a !important;
        border-bottom: 3px solid #333 !important;
        page-break-after: avoid !important;
        page-break-inside: avoid !important;
    }
    
    .print-header img,
    .print-header img.print-logo {
        display: block !important;
        max-height: 55px !important;
        width: auto !important;
        margin: 0 auto 8px auto !important;
        filter: brightness(1.1);
    }
    
    .print-header h1 {
        font-size: 14pt;
        font-weight: 700;
        margin: 0 0 3px 0;
        color: #ffffff !important;
        letter-spacing: 1px;
        text-transform: uppercase;
    }
    
    .print-header p {
        font-size: 9pt;
        margin: 2px 0;
        color: #e0e0e0 !important;
    }
    
    .print-header p:last-child {
        font-size: 8pt;
        color: #b0b0b0 !important;
    }
    
    /* ============================================
       SUMMARY CARDS - Professional Grid
       ============================================ */
    .row.mb-4.summary-row {
        display: flex !important;
        flex-wrap: nowrap !important;
        gap: 4px !important;
        margin: 0 0 8px 0 !important;
        padding: 0 !important;
        page-break-after: avoid !important;
        page-break-inside: avoid !important;
    }
    
    .row.mb-4.summary-row > div[class*="col-"] {
        flex: 1 1 0 !important;
        width: auto !important;
        max-width: none !important;
        padding: 0 !important;
        margin: 0 !important;
        float: none !important;
    }
    
    .summary-card {
        border: 1px solid #2c5282 !important;
        background: #f7fafc !important;
        box-shadow: none !important;
        padding: 4px 3px !important;
        text-align: center !important;
        height: 100% !important;
        min-height: 40px !important;
        display: flex !important;
        flex-direction: column !important;
        justify-content: center !important;
        align-items: center !important;
        border-radius: 2px !important;
    }
    
    .summary-card.border-success {
        border-color: #276749 !important;
        background: #f0fff4 !important;
    }
    
    .summary-card.border-danger {
        border-color: #c53030 !important;
        background: #fff5f5 !important;
    }
    
    .summary-icon { 
        display: none !important; 
    }
    
    .summary-content {
        display: flex !important;
        flex-direction: column !important;
        align-items: center !important;
        text-align: center !important;
        gap: 0 !important;
    }
    
    .summary-value {
        font-size: 8pt !important;
        font-weight: 700 !important;
        display: block !important;
        color: #1a202c !important;
        margin-bottom: 0 !important;
        line-height: 1.2 !important;
    }
    
    .summary-value.text-success,
    .summary-value.text-danger {
        color: #1a202c !important;
    }
    
    .summary-label {
        font-size: 5.5pt !important;
        text-transform: uppercase !important;
        display: block !important;
        color: #4a5568 !important;
        letter-spacing: 0.2px;
        font-weight: 600 !important;
    }
    
    .summary-card small {
        font-size: 5pt !important;
        display: block !important;
        color: #718096 !important;
        margin-top: 0 !important;
    }
    
    /* ============================================
       GLASS CARDS
       ============================================ */
    .glass-card {
        background: #fff !important;
        box-shadow: none !important;
        border: 1px solid #2d3748 !important;
        border-radius: 0 !important;
        margin: 0 0 6px 0 !important;
        padding: 0 !important;
        page-break-inside: avoid;
        overflow: visible !important;
    }
    
    .glass-card.no-print {
        display: none !important;
    }
    
    .card-header-custom {
        background: #2c5282 !important;
        padding: 4px 8px !important;
        border-bottom: none !important;
    }
    
    .card-header-custom h6 {
        font-size: 9pt !important;
        font-weight: 700 !important;
        margin: 0 !important;
        color: #fff !important;
    }
    
    .card-header-custom h6 i {
        display: none !important;
    }
    
    .card-header-custom button,
    .card-header-custom .btn,
    .card-header-custom > div.no-print,
    .card-header-custom small {
        display: none !important;
    }
    
    /* ============================================
       MAIN CONTENT - Row Stack
       ============================================ */
    .row.print-stack {
        display: block !important;
        width: 100% !important;
        margin: 0 !important;
        padding: 0 !important;
    }
    
    .row.print-stack > div {
        display: block !important;
        width: 100% !important;
        max-width: 100% !important;
        float: none !important;
        padding: 0 !important;
        margin: 0 0 6px 0 !important;
    }
    
    /* mb-4 should be smaller in print */
    .mb-4 {
        margin-bottom: 6px !important;
    }
    
    .mb-3 {
        margin-bottom: 4px !important;
    }
    
    .p-2, .p-3 {
        padding: 4px 6px !important;
    }
    
    /* ============================================
       TABLES - Professional Style (Compact)
       ============================================ */
    .table-responsive {
        overflow: visible !important;
        display: block !important;
    }
    
    table.table {
        width: 100% !important;
        border-collapse: collapse !important;
        font-size: 7.5pt !important;
        margin: 0 !important;
        display: table !important;
    }
    
    table.table thead {
        display: table-header-group !important;
    }
    
    table.table tbody {
        display: table-row-group !important;
    }
    
    table.table tfoot {
        display: table-footer-group !important;
    }
    
    table.table tr {
        display: table-row !important;
        page-break-inside: avoid !important;
    }
    
    table.table th,
    table.table td {
        display: table-cell !important;
        border: 1px solid #4a5568 !important;
        padding: 3px 4px !important;
        text-align: left !important;
        vertical-align: middle !important;
        background: #fff !important;
        color: #000 !important;
    }
    
    table.table th.no-print,
    table.table td.no-print {
        display: none !important;
    }
    
    table.table thead th {
        background: #e2e8f0 !important;
        font-weight: 700 !important;
        text-align: center !important;
        font-size: 7.5pt !important;
        color: #1a202c !important;
        border-color: #4a5568 !important;
    }
    
    table.table .text-end { 
        text-align: right !important; 
    }
    
    table.table .text-center { 
        text-align: center !important; 
    }
    
    table.table tfoot td,
    table.table tfoot th {
        background: #cbd5e0 !important;
        font-weight: 700 !important;
        border-color: #4a5568 !important;
    }
    
    table.table tbody tr:nth-child(even) td {
        background: #f7fafc !important;
    }
    
    /* Row complete highlight in print */
    table.table tbody tr.row-complete td {
        background: #d1fae5 !important;
    }
    
    /* Profit percentage */
    .profit-pct {
        font-size: 6pt !important;
        color: #4a5568 !important;
    }
    
    /* Checklist columns in print */
    .checklist-col .checklist-btn {
        display: none !important;
    }
    
    .checklist-col .print-check {
        display: inline !important;
        font-size: 11pt !important;
        font-family: 'Segoe UI Symbol', Arial, sans-serif !important;
    }
    
    .checklist-col .print-check.checked {
        color: #166534 !important;
    }
    
    /* Catatan column in print */
    .catatan-col .catatan-input {
        display: none !important;
    }
    
    .catatan-col .catatan-print-text {
        display: inline !important;
        font-size: 6.5pt !important;
        color: #374151 !important;
        font-style: italic !important;
    }
    
    /* Print Total Tagihan Summary */
    .print-only {
        display: block !important;
        visibility: visible !important;
    }
    
    .print-only .row {
        display: flex !important;
        margin: 0 !important;
    }
    
    .print-only .col-6 {
        flex: 0 0 50% !important;
        max-width: 50% !important;
        padding: 3px !important;
    }
    
    .print-only strong {
        font-size: 9pt !important;
    }
    
    /* ============================================
       DRAFT EXPENSES - Print Table (RED THEME - PRINT ONLY)
       ============================================ */
    .print-draft-table {
        display: block !important;
    }
    
    .print-draft-table table {
        width: 100% !important;
        display: table !important;
        border: 2px solid #991b1b !important;
    }
    
    .print-draft-table table thead,
    .print-draft-table table tbody,
    .print-draft-table table tfoot {
        display: table-row-group !important;
    }
    
    .print-draft-table table tr {
        display: table-row !important;
    }
    
    .print-draft-table table th,
    .print-draft-table table td {
        display: table-cell !important;
        font-size: 8pt !important;
        padding: 4px 6px !important;
        border: 1px solid #b91c1c !important;
    }
    
    .print-draft-table table thead th {
        background: #fecaca !important;
        color: #7f1d1d !important;
        font-weight: 700 !important;
        border-color: #b91c1c !important;
    }
    
    .print-draft-table table tfoot td {
        background: #fee2e2 !important;
        color: #7f1d1d !important;
        font-weight: 700 !important;
        border-color: #b91c1c !important;
    }
    
    .print-draft-table table tbody td {
        background: #fff !important;
    }
    
    .print-draft-table table tbody tr:nth-child(even) td {
        background: #fef2f2 !important;
    }
    
    /* Draft Card Print Header - Red */
    .col-lg-4 .glass-card .card-header-custom {
        background: #dc2626 !important;
    }
    
    .col-lg-4 .glass-card .card-header-custom h6 {
        color: #fff !important;
    }
    
    .col-lg-4 .glass-card {
        border-color: #b91c1c !important;
        border-width: 2px !important;
    }
    
    /* ============================================
       INFO BOXES - Simulasi Section
       ============================================ */
    .simulasi-info-row {
        display: flex !important;
        gap: 8px !important;
        margin: 0 0 8px 0 !important;
        padding: 0 !important;
    }
    
    .simulasi-info-row > div.col-md-4 {
        flex: 1 !important;
        padding: 0 !important;
        margin: 0 !important;
        float: none !important;
    }
    
    .info-box {
        border: 1.5px solid #2c5282 !important;
        background: #f7fafc !important;
        padding: 8px 6px !important;
        text-align: center !important;
        height: 100% !important;
        display: flex !important;
        flex-direction: column !important;
        justify-content: center !important;
        align-items: center !important;
        border-radius: 3px !important;
    }
    
    .info-box-primary,
    .info-box-success,
    .info-box-warning {
        background: #f7fafc !important;
    }
    
    .info-box-icon { 
        display: none !important; 
    }
    
    .info-box-content {
        text-align: center !important;
        display: block !important;
    }
    
    .info-box-label {
        font-size: 6.5pt !important;
        display: block !important;
        text-transform: uppercase !important;
        color: #4a5568 !important;
        margin-bottom: 2px !important;
        font-weight: 600 !important;
    }
    
    .info-box-value {
        font-size: 9pt !important;
        font-weight: 700 !important;
        display: block !important;
        color: #1a202c !important;
    }
    
    /* ============================================
       SIMULASI TABLE
       ============================================ */
    #pembagianTable {
        display: table !important;
        width: 100% !important;
    }
    
    #pembagianTable thead,
    #pembagianTable tbody,
    #pembagianTable tfoot {
        display: table-row-group !important;
    }
    
    #pembagianTable tr {
        display: table-row !important;
    }
    
    #pembagianTable th,
    #pembagianTable td {
        display: table-cell !important;
    }
    
    #pembagianTable th.no-print,
    #pembagianTable td.no-print {
        display: none !important;
    }
    
    #pembagianTable input {
        border: none !important;
        background: transparent !important;
        font-size: 8pt !important;
        padding: 0 !important;
        margin: 0 !important;
        width: 100% !important;
        color: #000 !important;
        text-align: inherit !important;
        -webkit-appearance: none !important;
        -moz-appearance: textfield !important;
        box-shadow: none !important;
        outline: none !important;
    }
    
    #pembagianTable input::-webkit-outer-spin-button,
    #pembagianTable input::-webkit-inner-spin-button {
        -webkit-appearance: none !important;
        margin: 0 !important;
    }
    
    #pembagianTable .nama-input {
        text-align: left !important;
    }
    
    #pembagianTable .persen-input {
        text-align: center !important;
    }
    
    #pembagianTable tfoot .table-success td {
        background: #c6f6d5 !important;
    }
    
    /* ============================================
       MISC
       ============================================ */
    .mt-3.pt-3.border-top {
        display: none !important;
    }
    
    .empty-state {
        padding: 15px !important;
        text-align: center !important;
    }
    
    .empty-state i { 
        display: none !important; 
    }
    
    .empty-state p {
        margin: 0 !important;
        font-style: italic !important;
        color: #718096 !important;
    }
    
    .text-primary, .text-success, .text-danger, 
    .text-warning, .text-info, .text-muted {
        color: #000 !important;
    }
    
    /* ============================================
       PAGE BREAKS
       ============================================ */
    .page-break-before {
        page-break-before: always !important;
    }
    
    .page-break-after {
        page-break-after: always !important;
    }
    
    /* Prevent orphan headers */
    .card-header-custom {
        page-break-after: avoid !important;
    }
    
    /* Keep table headers with content */
    thead {
        display: table-header-group !important;
    }
    
    tr {
        page-break-inside: avoid !important;
    }
}

/* Checklist print template */
.checklist-doc {
    font-family: Arial, sans-serif;
    padding: 20px;
    max-width: 800px;
    margin: 0 auto;
}

.checklist-header {
    text-align: center;
    margin-bottom: 30px;
    border-bottom: 2px solid #333;
    padding-bottom: 15px;
}

.checklist-header h2 {
    margin: 0 0 5px 0;
    font-size: 18px;
}

.checklist-header h3 {
    margin: 0;
    font-size: 14px;
    font-weight: normal;
    color: #666;
}

.checklist-section {
    margin-bottom: 25px;
}

.checklist-section h4 {
    background: #f0f0f0;
    padding: 8px 12px;
    margin: 0 0 10px 0;
    font-size: 14px;
    border-left: 4px solid #333;
}

.checklist-table {
    width: 100%;
    border-collapse: collapse;
    font-size: 12px;
}

.checklist-table th,
.checklist-table td {
    border: 1px solid #ddd;
    padding: 8px 10px;
    text-align: left;
}

.checklist-table th {
    background: #f8f8f8;
    font-weight: 600;
}

.checklist-table .check-col {
    width: 30px;
    text-align: center;
}

.checklist-table .checkbox {
    width: 16px;
    height: 16px;
    border: 1px solid #999;
    display: inline-block;
}

.checklist-footer {
    margin-top: 40px;
    display: flex;
    justify-content: space-between;
}

.checklist-signature {
    text-align: center;
    width: 200px;
}

.checklist-signature .line {
    border-bottom: 1px solid #333;
    height: 60px;
    margin-bottom: 5px;
}
</style>

<script>
var baseUrl = '<?= url('') ?>';

// ============================================
// CHECKLIST FUNCTIONS - Database Storage (FIXED!)
// ============================================
var currentYear = '<?= $reportFilters['year'] ?? '' ?>';
var currentMonth = '<?= $reportFilters['month'] ?? '' ?>';

// In-memory cache untuk checklist data
var checklistCache = {};

function getChecklistData() {
    return checklistCache;
}

// Toggle checklist dan simpan ke database
function toggleCheck(btn) {
    var key = btn.dataset.key;
    var type = btn.dataset.type;
    var row = btn.closest('tr');
    var supportName = row.dataset.supportName || row.querySelector('td:nth-child(2) strong').textContent;
    
    // Get current state
    if (!checklistCache[key]) {
        checklistCache[key] = { clear: false, submitted: false, catatan: '' };
    }
    
    // Toggle
    checklistCache[key][type] = !checklistCache[key][type];
    var newValue = checklistCache[key][type] ? 1 : 0;
    
    // Update UI immediately
    updateCheckUI(key, type, checklistCache[key][type]);
    updateRowComplete(key, checklistCache[key]);
    updateChecklistTotals();
    
    // Save to database
    var formData = new FormData();
    formData.append('support_key', key);
    formData.append('support_name', supportName);
    formData.append('type', type);
    formData.append('value', newValue);
    formData.append('year', currentYear);
    formData.append('month', currentMonth);
    
    var csrfToken = document.getElementById('csrf_token');
    if (csrfToken) {
        formData.append('_token', csrfToken.value);
    }
    
    fetch(baseUrl + '/analysis/save-checklist', {
        method: 'POST',
        body: formData
    })
    .then(function(r) { return r.json(); })
    .then(function(data) {
        if (!data.success) {
            console.error('Gagal simpan checklist:', data.message);
            // Revert on failure
            checklistCache[key][type] = !checklistCache[key][type];
            updateCheckUI(key, type, checklistCache[key][type]);
            updateRowComplete(key, checklistCache[key]);
            updateChecklistTotals();
            showToast('Gagal menyimpan: ' + data.message, 'error');
        }
    })
    .catch(function(err) {
        console.error('Error:', err);
        // Revert on error
        checklistCache[key][type] = !checklistCache[key][type];
        updateCheckUI(key, type, checklistCache[key][type]);
        updateRowComplete(key, checklistCache[key]);
        updateChecklistTotals();
    });
}

function updateCheckUI(key, type, isChecked) {
    // Update button
    var btn = document.querySelector('.checklist-btn[data-key="' + key + '"][data-type="' + type + '"]');
    var printCheck = document.querySelector('.print-check[data-key="' + key + '"][data-type="' + type + '"]');
    
    if (btn) {
        var icon = btn.querySelector('i');
        if (isChecked) {
            btn.classList.add('checked');
            icon.className = 'fas fa-check-square';
        } else {
            btn.classList.remove('checked');
            icon.className = 'far fa-square';
        }
    }
    
    if (printCheck) {
        if (isChecked) {
            printCheck.textContent = '☑';
            printCheck.classList.add('checked');
        } else {
            printCheck.textContent = '☐';
            printCheck.classList.remove('checked');
        }
    }
}

function updateRowComplete(key, statuses) {
    var row = document.querySelector('tr[data-support-key="' + key + '"]');
    if (row) {
        if (statuses.clear && statuses.submitted) {
            row.classList.add('row-complete');
        } else {
            row.classList.remove('row-complete');
        }
    }
}

function updateChecklistTotals() {
    var data = getChecklistData();
    var totalClear = 0;
    var totalSubmitted = 0;
    var totalTagihanSubmitted = 0;
    var totalTagihanAll = 0;
    var totalRows = document.querySelectorAll('#tabelDukungan tbody tr').length;
    
    // Loop through all rows
    document.querySelectorAll('#tabelDukungan tbody tr').forEach(function(row) {
        var key = row.dataset.supportKey;
        var tagihan = parseFloat(row.dataset.tagihan) || 0;
        
        totalTagihanAll += tagihan;
        
        if (data[key]) {
            if (data[key].clear) totalClear++;
            if (data[key].submitted) {
                totalSubmitted++;
                totalTagihanSubmitted += tagihan;
            }
        }
    });
    
    var totalTagihanPending = totalTagihanAll - totalTagihanSubmitted;
    
    // Update count display
    document.getElementById('totalClear').textContent = totalClear + '/' + totalRows;
    document.getElementById('totalSubmitted').textContent = totalSubmitted + '/' + totalRows;
    
    // Update tagihan summary
    var elSubmitted = document.getElementById('totalTagihanSubmitted');
    var elPending = document.getElementById('totalTagihanPending');
    var elPrintSubmitted = document.getElementById('printTotalSubmitted');
    var elPrintPending = document.getElementById('printTotalPending');
    
    if (elSubmitted) elSubmitted.textContent = formatRupiahJS(totalTagihanSubmitted);
    if (elPending) elPending.textContent = formatRupiahJS(totalTagihanPending);
    if (elPrintSubmitted) elPrintSubmitted.textContent = formatRupiahJS(totalTagihanSubmitted);
    if (elPrintPending) elPrintPending.textContent = formatRupiahJS(totalTagihanPending);
}

// Load checklist from database
function loadChecklistState() {
    var url = baseUrl + '/analysis/load-checklist?year=' + encodeURIComponent(currentYear) + '&month=' + encodeURIComponent(currentMonth) + '&_t=' + Date.now();
    
    fetch(url, { cache: 'no-store' })
    .then(function(r) { return r.json(); })
    .then(function(result) {
        if (result.success && result.data) {
            checklistCache = result.data;
            
            // Apply to UI
            Object.keys(checklistCache).forEach(function(key) {
                var statuses = checklistCache[key];
                if (statuses.clear) updateCheckUI(key, 'clear', true);
                if (statuses.submitted) updateCheckUI(key, 'submitted', true);
                updateRowComplete(key, statuses);
                
                // Load catatan
                if (statuses.catatan) {
                    var input = document.querySelector('.catatan-input[data-key="' + key + '"]');
                    if (input) input.value = statuses.catatan;
                    updateCatatanPrintText(key, statuses.catatan);
                }
            });
            
            updateChecklistTotals();
        }
    })
    .catch(function(err) {
        console.error('Gagal load checklist:', err);
    });
}

function resetAllChecklist() {
    if (!confirm('Reset semua checklist dan catatan? Data akan dihapus dari database.')) return;
    
    var formData = new FormData();
    formData.append('year', currentYear);
    formData.append('month', currentMonth);
    
    var csrfToken = document.getElementById('csrf_token');
    if (csrfToken) {
        formData.append('_token', csrfToken.value);
    }
    
    fetch(baseUrl + '/analysis/reset-checklist', {
        method: 'POST',
        body: formData
    })
    .then(function(r) { return r.json(); })
    .then(function(data) {
        if (data.success) {
            // Clear cache
            checklistCache = {};
            
            // Reset UI
            document.querySelectorAll('.checklist-btn').forEach(function(btn) {
                btn.classList.remove('checked');
                btn.querySelector('i').className = 'far fa-square';
            });
            
            document.querySelectorAll('.print-check').forEach(function(span) {
                span.textContent = '☐';
                span.classList.remove('checked');
            });
            
            document.querySelectorAll('tr.row-complete').forEach(function(row) {
                row.classList.remove('row-complete');
            });
            
            // Reset catatan
            document.querySelectorAll('.catatan-input').forEach(function(input) {
                input.value = '';
            });
            document.querySelectorAll('.catatan-print-text').forEach(function(span) {
                span.textContent = '';
            });
            
            updateChecklistTotals();
            showToast('Checklist dan catatan direset', 'info');
        } else {
            showToast('Gagal reset: ' + data.message, 'error');
        }
    })
    .catch(function(err) {
        showToast('Error: ' + err.message, 'error');
    });
}

// ============================================
// CATATAN (NOTES) PER ROW FUNCTIONS - Database
// ============================================
var catatanTimeout = null;

function saveCatatanRow(input) {
    var key = input.dataset.key;
    var catatan = input.value;
    var row = input.closest('tr');
    var supportName = row.dataset.supportName || row.querySelector('td:nth-child(2) strong').textContent;
    
    // Update cache
    if (!checklistCache[key]) {
        checklistCache[key] = { clear: false, submitted: false, catatan: '' };
    }
    checklistCache[key].catatan = catatan;
    
    // Update print text immediately
    updateCatatanPrintText(key, catatan);
    
    // Debounce save to database (wait 500ms after typing stops)
    if (catatanTimeout) clearTimeout(catatanTimeout);
    
    catatanTimeout = setTimeout(function() {
        var formData = new FormData();
        formData.append('support_key', key);
        formData.append('support_name', supportName);
        formData.append('catatan', catatan);
        formData.append('year', currentYear);
        formData.append('month', currentMonth);
        
        var csrfToken = document.getElementById('csrf_token');
        if (csrfToken) {
            formData.append('_token', csrfToken.value);
        }
        
        fetch(baseUrl + '/analysis/save-catatan', {
            method: 'POST',
            body: formData
        })
        .then(function(r) { return r.json(); })
        .then(function(data) {
            if (!data.success) {
                console.error('Gagal simpan catatan:', data.message);
            }
        })
        .catch(function(err) {
            console.error('Error:', err);
        });
    }, 500);
}

function updateCatatanPrintText(key, text) {
    var printSpan = document.querySelector('.catatan-print-text[data-key="' + key + '"]');
    if (printSpan) {
        printSpan.textContent = text || '';
    }
}

// Load checklist state on page load
document.addEventListener('DOMContentLoaded', function() {
    loadChecklistState();
});

// Format rupiah input
function formatRupiahInput(input) {
    var value = input.value.replace(/[^\d]/g, '');
    input.value = value ? parseInt(value).toLocaleString('id-ID') : '';
}

// Custom Modal Functions
function openDraftModal(data) {
    var form = document.getElementById('draftForm');
    if (form) form.reset();
    
    document.getElementById('draft_id').value = '';
    document.getElementById('draft_planned_date').value = new Date().toISOString().split('T')[0];
    
    if (data) {
        document.getElementById('draft_id').value = data.id || '';
        document.getElementById('draft_title').value = data.title || '';
        document.getElementById('draft_description').value = data.description || '';
        document.getElementById('draft_category').value = data.category || 'other';
        document.getElementById('draft_priority').value = data.priority || 'medium';
        document.getElementById('draft_planned_date').value = data.planned_date || '';
        document.getElementById('draft_estimated_amount').value = data.estimated_amount ? parseInt(data.estimated_amount).toLocaleString('id-ID') : '';
        document.getElementById('draft_support_for').value = data.support_for || '';
    }
    
    document.getElementById('draftModal').classList.add('show');
    document.body.style.overflow = 'hidden';
}

function closeDraftModal() {
    document.getElementById('draftModal').classList.remove('show');
    document.body.style.overflow = '';
}

// Submit Draft Form
function submitDraftForm(e) {
    e.preventDefault();
    
    var form = document.getElementById('draftForm');
    var formData = new FormData(form);
    
    // Convert amount
    var amountEl = document.getElementById('draft_estimated_amount');
    var amount = amountEl ? amountEl.value.replace(/[^\d]/g, '') : '0';
    formData.set('estimated_amount', amount);
    
    fetch(baseUrl + '/analysis/save-draft', {
        method: 'POST',
        body: formData
    })
    .then(function(r) { return r.json(); })
    .then(function(data) {
        if (data.success) {
            showToast(data.message || 'Draft berhasil disimpan', 'success');
            closeDraftModal();
            setTimeout(function() { location.reload(); }, 1000);
        } else {
            showToast(data.message || 'Gagal menyimpan draft', 'error');
        }
    })
    .catch(function(err) {
        showToast('Error: ' + err.message, 'error');
    });
}

function editDraft(id) {
    fetch(baseUrl + '/analysis/get-draft?id=' + id)
        .then(function(r) { return r.json(); })
        .then(function(data) {
            if (data.success && data.data) {
                openDraftModal(data.data);
            } else {
                showToast('Draft tidak ditemukan', 'error');
            }
        })
        .catch(function(err) {
            showToast('Error: ' + err.message, 'error');
        });
}

function deleteDraft(id) {
    if (!confirm('Hapus draft ini?')) return;
    
    var formData = new FormData();
    var csrfToken = document.getElementById('csrf_token');
    if (csrfToken) {
        formData.append('_token', csrfToken.value);
    }
    formData.append('id', id);
    
    fetch(baseUrl + '/analysis/delete-draft', {
        method: 'POST',
        body: formData
    })
    .then(function(r) { return r.json(); })
    .then(function(data) {
        if (data.success) {
            showToast('Draft dihapus', 'success');
            setTimeout(function() { location.reload(); }, 1000);
        } else {
            showToast(data.message || 'Gagal menghapus', 'error');
        }
    });
}

function createDraftTable() {
    if (!confirm('Buat tabel draft_expenses di database?')) return;
    
    var formData = new FormData();
    var csrfToken = document.getElementById('csrf_token');
    if (csrfToken) {
        formData.append('_token', csrfToken.value);
    }
    
    fetch(baseUrl + '/analysis/create-draft-table', {
        method: 'POST',
        body: formData
    })
    .then(function(r) { return r.json(); })
    .then(function(data) {
        if (data.success) {
            showToast('Tabel berhasil dibuat', 'success');
            setTimeout(function() { location.reload(); }, 1000);
        } else {
            showToast(data.message || 'Gagal membuat tabel', 'error');
        }
    });
}

// Print Checklist Function - Compact A4 dengan Kop PIM Travel
function printChecklist(supportName) {
    var today = new Date().toLocaleDateString('id-ID', { day: 'numeric', month: 'long', year: 'numeric' });
    var logoPath = document.getElementById('companyLogoPath') ? document.getElementById('companyLogoPath').value : '';
    
    // Open new window for print
    var printWindow = window.open('', '_blank');
    printWindow.document.write(`
        <!DOCTYPE html>
        <html>
        <head>
            <title>Checklist - ${supportName}</title>
            <style>
                * { margin: 0; padding: 0; box-sizing: border-box; }
                @page { size: A4; margin: 10mm 15mm; }
                body { 
                    font-family: Arial, sans-serif; 
                    font-size: 10px;
                    line-height: 1.3;
                    color: #333;
                }
                .page {
                    width: 100%;
                    max-width: 210mm;
                    margin: 0 auto;
                }
                
                /* Kop Surat - Elegant Dark Style */
                .kop-surat {
                    text-align: center;
                    padding: 12px 15px 10px 15px;
                    background: #1a1a1a;
                    border-bottom: 3px solid #333;
                    margin-bottom: 15px;
                }
                .kop-logo-img {
                    max-height: 55px;
                    width: auto;
                    margin-bottom: 5px;
                    filter: brightness(1.1);
                }
                
                /* Title */
                .doc-title {
                    text-align: center;
                    margin: 12px 0;
                }
                .doc-title h2 {
                    font-size: 13px;
                    text-transform: uppercase;
                    letter-spacing: 1px;
                    margin-bottom: 4px;
                }
                .doc-title .dukungan {
                    font-size: 11px;
                    color: #1e40af;
                    font-weight: 600;
                }
                .doc-title .tanggal {
                    font-size: 9px;
                    color: #666;
                    margin-top: 3px;
                }
                
                /* Sections */
                .sections {
                    display: flex;
                    gap: 10px;
                    margin-bottom: 10px;
                }
                .section {
                    flex: 1;
                    border: 1px solid #ddd;
                    border-radius: 4px;
                    overflow: hidden;
                }
                .section-header {
                    background: #1e40af;
                    color: white;
                    padding: 5px 8px;
                    font-weight: bold;
                    font-size: 9px;
                    text-transform: uppercase;
                }
                .section-body {
                    padding: 0;
                }
                .check-item {
                    display: flex;
                    align-items: center;
                    padding: 4px 8px;
                    border-bottom: 1px solid #eee;
                    font-size: 9px;
                }
                .check-item:last-child {
                    border-bottom: none;
                }
                .checkbox {
                    width: 12px;
                    height: 12px;
                    border: 1px solid #999;
                    margin-right: 8px;
                    flex-shrink: 0;
                }
                .check-item span {
                    flex: 1;
                }
                
                /* Notes */
                .notes {
                    border: 1px solid #ddd;
                    border-radius: 4px;
                    padding: 8px;
                    margin-bottom: 12px;
                }
                .notes-title {
                    font-weight: bold;
                    font-size: 9px;
                    margin-bottom: 4px;
                }
                .notes-line {
                    border-bottom: 1px dotted #ccc;
                    height: 16px;
                    margin-bottom: 2px;
                }
                
                /* Signatures */
                .signatures {
                    display: flex;
                    justify-content: space-between;
                    margin-top: 15px;
                }
                .signature-box {
                    width: 45%;
                    text-align: center;
                }
                .signature-box p {
                    font-size: 9px;
                    margin-bottom: 3px;
                }
                .signature-line {
                    border-bottom: 1px solid #333;
                    height: 40px;
                    margin-bottom: 3px;
                }
                .signature-name {
                    font-size: 9px;
                }
                
                @media print {
                    body { -webkit-print-color-adjust: exact; print-color-adjust: exact; }
                }
            </style>
        </head>
        <body>
            <div class="page">
                <!-- Logo -->
                <div class="kop-surat">
                    ${logoPath ? '<img src="' + logoPath + '" alt="Logo" class="kop-logo-img">' : ''}
                </div>
                
                <!-- Document Title -->
                <div class="doc-title">
                    <h2>Checklist Kelengkapan Dokumen</h2>
                    <div class="dukungan">Dukungan: ${supportName}</div>
                    <div class="tanggal">Tanggal: ${today}</div>
                </div>
                
                <!-- 3 Sections Side by Side -->
                <div class="sections">
                    <!-- Hotel -->
                    <div class="section">
                        <div class="section-header">🏨 Hotel / Akomodasi</div>
                        <div class="section-body">
                            <div class="check-item"><div class="checkbox"></div><span>Surat Penawaran</span></div>
                            <div class="check-item"><div class="checkbox"></div><span>Surat Kesepakatan</span></div>
                            <div class="check-item"><div class="checkbox"></div><span>Invoice</span></div>
                            <div class="check-item"><div class="checkbox"></div><span>Kwitansi</span></div>
                            <div class="check-item"><div class="checkbox"></div><span>Lampiran Hotel</span></div>
                        </div>
                    </div>
                    
                    <!-- Rental Kendaraan -->
                    <div class="section">
                        <div class="section-header">🚌 Rental Kendaraan</div>
                        <div class="section-body">
                            <div class="check-item"><div class="checkbox"></div><span>Surat Penawaran</span></div>
                            <div class="check-item"><div class="checkbox"></div><span>Surat Kesepakatan</span></div>
                            <div class="check-item"><div class="checkbox"></div><span>Invoice</span></div>
                            <div class="check-item"><div class="checkbox"></div><span>Kwitansi</span></div>
                            <div class="check-item"><div class="checkbox"></div><span>Lampiran Invoice</span></div>
                            <div class="check-item"><div class="checkbox"></div><span>Lampiran Data Kendaraan</span></div>
                        </div>
                    </div>
                    
                    <!-- Pesawat -->
                    <div class="section">
                        <div class="section-header">✈️ Pesawat / Tiket</div>
                        <div class="section-body">
                            <div class="check-item"><div class="checkbox"></div><span>Surat Penawaran</span></div>
                            <div class="check-item"><div class="checkbox"></div><span>Surat Kesepakatan</span></div>
                            <div class="check-item"><div class="checkbox"></div><span>Invoice</span></div>
                            <div class="check-item"><div class="checkbox"></div><span>Kwitansi</span></div>
                            <div class="check-item"><div class="checkbox"></div><span>Lampiran Pesawat</span></div>
                        </div>
                    </div>
                </div>
                
                <!-- Notes -->
                <div class="notes">
                    <div class="notes-title">Catatan:</div>
                    <div class="notes-line"></div>
                    <div class="notes-line"></div>
                    <div class="notes-line"></div>
                </div>
                
                <!-- Signatures -->
                <div class="signatures">
                    <div class="signature-box">
                        <p>Diperiksa oleh:</p>
                        <div class="signature-line"></div>
                        <div class="signature-name">(...............................)</div>
                    </div>
                    <div class="signature-box">
                        <p>Disetujui oleh:</p>
                        <div class="signature-line"></div>
                        <div class="signature-name">(...............................)</div>
                    </div>
                </div>
            </div>
            
            <script>
                window.onload = function() {
                    setTimeout(function() {
                        window.print();
                    }, 300);
                    window.onafterprint = function() {
                        window.close();
                    };
                };
            <\/script>
        </body>
        </html>
    `);
    printWindow.document.close();
}

function showToast(msg, type) {
    type = type || 'info';
    var t = document.createElement('div');
    t.className = 'toast-notification toast-' + type;
    var icon = type === 'success' ? 'check-circle' : type === 'error' ? 'times-circle' : 'info-circle';
    t.innerHTML = '<i class="fas fa-' + icon + '"></i><span>' + msg + '</span>';
    document.body.appendChild(t);
    setTimeout(function() { t.classList.add('show'); }, 10);
    setTimeout(function() { t.classList.remove('show'); setTimeout(function() { t.remove(); }, 300); }, 3000);
}

// Close modal with Escape key
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        closeDraftModal();
    }
});

// ================================================================
// SIMULASI PEMBAGIAN DANA
// ================================================================

var totalTagihan = <?= (float)($grandTotals['total_tagihan'] ?? 0) ?>;
var totalProfit = <?= (float)($grandTotals['total_profit'] ?? 0) ?>;
var pembagianCounter = 0;
var hasSimulasiTable = false;

function formatRupiahJS(num) {
    return 'Rp ' + Math.round(num).toLocaleString('id-ID');
}

function addPembagianRow(nama, persen) {
    pembagianCounter++;
    nama = nama || '';
    persen = persen || 0;
    
    var row = document.createElement('tr');
    row.id = 'pembagian-row-' + pembagianCounter;
    row.innerHTML = `
        <td class="text-center">${pembagianCounter}</td>
        <td>
            <input type="text" class="form-control form-control-sm nama-input" 
                   placeholder="Nama pembagian..." value="${nama}"
                   onchange="hitungPembagian()">
        </td>
        <td>
            <input type="number" class="form-control form-control-sm persen-input" 
                   placeholder="0" min="0" max="100" step="0.1" value="${persen}"
                   onchange="hitungPembagian()" oninput="hitungPembagian()">
        </td>
        <td class="text-end jumlah-cell">Rp 0</td>
        <td class="text-center no-print">
            <button type="button" class="btn btn-xs btn-outline-danger" onclick="hapusPembagianRow(${pembagianCounter})">
                <i class="fas fa-times"></i>
            </button>
        </td>
    `;
    
    document.getElementById('pembagianBody').appendChild(row);
    hitungPembagian();
}

function hapusPembagianRow(id) {
    var row = document.getElementById('pembagian-row-' + id);
    if (row) {
        row.remove();
        reindexPembagianRows();
        hitungPembagian();
    }
}

function reindexPembagianRows() {
    var rows = document.querySelectorAll('#pembagianBody tr');
    var counter = 0;
    rows.forEach(function(row) {
        counter++;
        row.querySelector('td:first-child').textContent = counter;
    });
}

function hitungPembagian() {
    var rows = document.querySelectorAll('#pembagianBody tr');
    var totalPersen = 0;
    var totalJumlah = 0;
    
    rows.forEach(function(row) {
        var persenInput = row.querySelector('.persen-input');
        var jumlahCell = row.querySelector('.jumlah-cell');
        
        var persen = parseFloat(persenInput.value) || 0;
        var jumlah = (persen / 100) * totalTagihan;
        
        totalPersen += persen;
        totalJumlah += jumlah;
        
        jumlahCell.textContent = formatRupiahJS(jumlah);
    });
    
    // Update totals
    document.getElementById('totalPersen').textContent = totalPersen.toFixed(1) + '%';
    document.getElementById('totalPembagian').textContent = formatRupiahJS(totalJumlah);
    
    // Hitung sisa profit
    var sisaProfit = totalProfit - totalJumlah;
    document.getElementById('sisaProfitFinal').textContent = formatRupiahJS(sisaProfit);
    document.getElementById('simSisaProfit').textContent = formatRupiahJS(sisaProfit);
    
    // Warning jika sisa profit negatif
    var sisaCell = document.getElementById('sisaProfitFinal');
    var sisaBox = document.getElementById('simSisaProfit');
    
    if (sisaProfit < 0) {
        sisaCell.classList.remove('text-success');
        sisaCell.classList.add('text-danger');
        sisaBox.classList.add('text-danger');
        sisaBox.parentElement.parentElement.classList.add('info-box-danger');
    } else {
        sisaCell.classList.remove('text-danger');
        sisaCell.classList.add('text-success');
        sisaBox.classList.remove('text-danger');
        sisaBox.parentElement.parentElement.classList.remove('info-box-danger');
    }
    
    // Warning jika total persen > 100
    var persenCell = document.getElementById('totalPersen');
    if (totalPersen > 100) {
        persenCell.classList.add('text-danger');
    } else {
        persenCell.classList.remove('text-danger');
    }
}

function loadPreset(preset) {
    var presets = {
        'marketing': { nama: 'Marketing / Promosi', persen: 10 },
        'operasional': { nama: 'Biaya Operasional', persen: 15 },
        'komisi': { nama: 'Komisi Sales', persen: 5 },
        'pajak': { nama: 'Pajak', persen: 10 }
    };
    
    if (presets[preset]) {
        addPembagianRow(presets[preset].nama, presets[preset].persen);
    }
}

function clearAllPembagian() {
    if (confirm('Hapus semua pembagian?')) {
        document.getElementById('pembagianBody').innerHTML = '';
        pembagianCounter = 0;
        hitungPembagian();
    }
}

// Fungsi Simpan Simulasi
function simpanSimulasi() {
    var rows = document.querySelectorAll('#pembagianBody tr');
    var pembagian = [];
    
    rows.forEach(function(row) {
        var namaInput = row.querySelector('.nama-input');
        var persenInput = row.querySelector('.persen-input');
        
        var nama = namaInput ? namaInput.value.trim() : '';
        var persen = persenInput ? parseFloat(persenInput.value) || 0 : 0;
        
        if (nama || persen > 0) {
            pembagian.push({ nama: nama, persen: persen });
        }
    });
    
    var btn = document.getElementById('btnSimpanSimulasi');
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i> Menyimpan...';
    
    // Use GET with query parameters (more compatible)
    var params = new URLSearchParams();
    params.append('year', currentYear);
    params.append('month', currentMonth);
    params.append('pembagian', JSON.stringify(pembagian));
    
    // Add cache buster
    params.append('_t', Date.now());
    
    var url = baseUrl + '/analysis/save-simulasi?' + params.toString();
    
    fetch(url, { cache: 'no-store' })
    .then(function(response) {
        if (!response.ok) {
            throw new Error('HTTP ' + response.status);
        }
        return response.text();
    })
    .then(function(text) {
        btn.disabled = false;
        btn.innerHTML = '<i class="fas fa-save me-1"></i> Simpan';
        
        try {
            var result = JSON.parse(text);
            if (result.success) {
                showToast(result.message || 'Simulasi berhasil disimpan', 'success');
                updateLastSaved(new Date().toISOString());
            } else {
                showToast(result.message || 'Gagal menyimpan simulasi', 'error');
            }
        } catch (e) {
            showToast('Error: Response tidak valid', 'error');
        }
    })
    .catch(function(err) {
        btn.disabled = false;
        btn.innerHTML = '<i class="fas fa-save me-1"></i> Simpan';
        showToast('Error: ' + err.message, 'error');
    });
}

// Fungsi Load Simulasi
function loadSimulasi() {
    // Add cache buster to prevent service worker caching
    var url = baseUrl + '/analysis/load-simulasi?year=' + currentYear + '&month=' + currentMonth + '&_t=' + Date.now();
    
    fetch(url, { cache: 'no-store' })
    .then(function(response) {
        if (!response.ok) {
            throw new Error('HTTP ' + response.status);
        }
        return response.text();
    })
    .then(function(text) {
        try {
            var result = JSON.parse(text);
            if (result.success && result.data && result.data.length > 0) {
                // Clear existing
                document.getElementById('pembagianBody').innerHTML = '';
                pembagianCounter = 0;
                
                // Load data
                result.data.forEach(function(item) {
                    addPembagianRow(item.nama, item.persen);
                });
                
                if (result.updated_at) {
                    updateLastSaved(result.updated_at);
                }
            }
        } catch (e) {
            // Tidak perlu tampilkan error ke user
        }
    })
    .catch(function(err) {
        // Tidak perlu tampilkan error ke user
    });
}

function updateLastSaved(datetime) {
    var el = document.getElementById('simulasiLastSaved');
    if (el && datetime) {
        var d = new Date(datetime);
        el.textContent = 'Terakhir disimpan: ' + d.toLocaleDateString('id-ID') + ' ' + d.toLocaleTimeString('id-ID', {hour: '2-digit', minute: '2-digit'});
    }
}

// Print Report Function
function printReport() {
    window.print();
}

// Load simulasi saat halaman ready
document.addEventListener('DOMContentLoaded', function() {
    loadSimulasi();
});
</script>