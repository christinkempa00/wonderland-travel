<?php
/**
 * Analysis Index View - Grouped by Dukungan
 * File: views/analysis/index.php
 * 
 * MODIFIKASI v7:
 * - Modal Glass Morphism yang lebih elegan
 * - Rekap per kategori di atas (total per kategori)
 * - Filter kategori untuk data
 * - Print berdasarkan kategori
 * 
 * PROFIT = Tagihan - Modal (bukan sisa pembayaran)
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

$filingCounts = $summary['filing_counts'] ?? [];
?>

<!-- CSRF Token untuk AJAX -->
<input type="hidden" id="csrf_token" value="<?= e(Session::getCsrfToken()) ?>">

<!-- Summary Cards -->
<div class="row mb-4">
    <div class="col-6 col-md-4 col-xl-2 mb-3">
        <div class="summary-card">
            <div class="summary-icon bg-primary-soft"><i class="fas fa-layer-group"></i></div>
            <div class="summary-content">
                <span class="summary-value"><?= number_format($summary['total_groups']) ?></span>
                <span class="summary-label">Dukungan</span>
                <small class="text-muted"><?= number_format($summary['total_orders']) ?> order</small>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-4 col-xl-2 mb-3">
        <div class="summary-card">
            <div class="summary-icon bg-warning-soft"><i class="fas fa-coins"></i></div>
            <div class="summary-content">
                <span class="summary-value"><?= formatRupiahShort($summary['total_modal']) ?></span>
                <span class="summary-label">Total Modal</span>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-4 col-xl-2 mb-3">
        <div class="summary-card">
            <div class="summary-icon bg-info-soft"><i class="fas fa-file-invoice-dollar"></i></div>
            <div class="summary-content">
                <span class="summary-value"><?= formatRupiahShort($summary['total_tagihan']) ?></span>
                <span class="summary-label">Total Tagihan</span>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-4 col-xl-2 mb-3">
        <div class="summary-card border-success">
            <div class="summary-icon bg-success-soft"><i class="fas fa-hand-holding-usd"></i></div>
            <div class="summary-content">
                <span class="summary-value text-success"><?= formatRupiahShort($summary['total_profit']) ?></span>
                <span class="summary-label">Total Profit</span>
                <small class="text-success"><?= number_format($summary['avg_markup_percent'], 1) ?>%</small>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-4 col-xl-2 mb-3">
        <div class="summary-card">
            <div class="summary-icon bg-teal-soft"><i class="fas fa-money-bill-wave"></i></div>
            <div class="summary-content">
                <span class="summary-value"><?= formatRupiahShort($summary['total_dibayar']) ?></span>
                <span class="summary-label">Dibayar</span>
                <small class="text-info"><?= number_format($summary['payment_progress'], 1) ?>%</small>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-4 col-xl-2 mb-3">
        <div class="summary-card">
            <div class="summary-icon bg-purple-soft"><i class="fas fa-folder-open"></i></div>
            <div class="summary-content">
                <span class="summary-value"><?= number_format($summary['filing_progress'], 0) ?>%</span>
                <span class="summary-label">Pemberkasan</span>
                <small class="text-muted"><?= $summary['filing_in_progress'] ?? 0 ?>/<?= $summary['total_orders'] ?></small>
            </div>
        </div>
    </div>
</div>

<!-- Filters -->
<div class="glass-card mb-4">
    <form method="GET" action="<?= url('/analysis') ?>" class="filter-form">
        <div class="row g-2 align-items-end">
            <div class="col-6 col-md-3 col-lg-2">
                <label class="form-label small">Cari</label>
                <input type="text" name="search" class="form-control form-control-sm" placeholder="Event..."
                       value="<?= e($filters['search'] ?? '') ?>">
            </div>
            <div class="col-6 col-md-3 col-lg-2">
                <label class="form-label small">Dukungan</label>
                <select name="support_for" class="form-control form-control-sm">
                    <option value="">Semua</option>
                    <?php foreach ($supportForOptions as $support): ?>
                    <option value="<?= e($support) ?>" <?= ($filters['support_for'] ?? '') === $support ? 'selected' : '' ?>>
                        <?= e(truncate($support, 25)) ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-6 col-md-2 col-lg-2">
                <label class="form-label small">Bayar</label>
                <select name="payment_status" class="form-control form-control-sm">
                    <option value="">Semua</option>
                    <?php foreach ($paymentStatuses as $key => $status): ?>
                    <option value="<?= $key ?>" <?= ($filters['payment_status'] ?? '') === $key ? 'selected' : '' ?>>
                        <?= $status['label'] ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <?php if (!empty($hasFilingColumn)): ?>
            <div class="col-6 col-md-2 col-lg-2">
                <label class="form-label small">Berkas</label>
                <select name="filing_status" class="form-control form-control-sm">
                    <option value="">Semua</option>
                    <?php foreach ($filingStatuses as $key => $status): ?>
                    <option value="<?= $key ?>" <?= ($filters['filing_status'] ?? '') === $key ? 'selected' : '' ?>>
                        <?= $status['label'] ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <?php endif; ?>
            <div class="col-6 col-md-2 col-lg-2">
                <label class="form-label small">Dari</label>
                <input type="date" name="date_from" class="form-control form-control-sm" value="<?= e($filters['date_from'] ?? '') ?>">
            </div>
            <div class="col-6 col-md-2 col-lg-2">
                <label class="form-label small">Sampai</label>
                <input type="date" name="date_to" class="form-control form-control-sm" value="<?= e($filters['date_to'] ?? '') ?>">
            </div>
            <div class="col-6 col-md-2 col-lg-auto">
                <button type="submit" class="btn btn-primary btn-sm"><i class="fas fa-filter"></i> Filter</button>
                <?php if (!empty(array_filter($filters))): ?>
                <a href="<?= url('/analysis') ?>" class="btn btn-secondary btn-sm"><i class="fas fa-times"></i></a>
                <?php endif; ?>
            </div>
        </div>
    </form>
    
    <!-- Bulk Actions -->
    <div class="d-flex flex-wrap gap-2 align-items-center mt-3 pt-3 border-top">
        <span class="text-muted small d-none d-md-inline">Bulk:</span>
        <input type="number" id="bulkMarkupPercent" class="form-control form-control-sm" style="width: 70px" placeholder="%" step="0.1" min="0" max="100">
        <button type="button" class="btn btn-sm btn-success" onclick="applyBulkMarkup()">
            <i class="fas fa-percent"></i><span class="d-none d-md-inline"> Markup</span>
        </button>
        <div class="ms-auto d-flex gap-2">
            <button type="button" class="btn btn-sm btn-outline-secondary" onclick="selectAllOrders()">
                <i class="fas fa-check-square"></i>
            </button>
            <a href="<?= url('/analysis/export?' . http_build_query($filters)) ?>" class="btn btn-sm btn-outline-success">
                <i class="fas fa-download"></i><span class="d-none d-md-inline"> Export</span>
            </a>
        </div>
    </div>
</div>

<!-- Grouped Orders -->
<?php if (empty($groupedOrders)): ?>
<div class="glass-card">
    <div class="empty-state">
        <div class="empty-state-icon"><i class="fas fa-chart-bar"></i></div>
        <h4 class="empty-state-title">Tidak Ada Data</h4>
        <p class="empty-state-text">Tidak ada pesanan yang sesuai filter.</p>
    </div>
</div>
<?php else: ?>

<?php foreach ($groupedOrders as $supportName => $orders): 
    $totals = $groupTotals[$supportName];
?>
<div class="glass-card mb-4 group-card">
    <!-- Group Header -->
    <div class="group-header">
        <div class="group-title">
            <i class="fas fa-hands-helping text-primary"></i>
            <h5><?= e($supportName) ?></h5>
            <span class="badge badge-primary"><?= count($orders) ?></span>
        </div>
        <div class="group-summary d-none d-md-flex">
            <div class="group-stat">
                <span class="stat-label">Total Modal</span>
                <span class="stat-value text-warning-dark"><?= formatRupiahShort($totals['modal']) ?></span>
            </div>
            <div class="group-stat">
                <span class="stat-label">Total Tagihan</span>
                <span class="stat-value"><?= formatRupiahShort($totals['tagihan']) ?></span>
            </div>
            <div class="group-stat">
                <span class="stat-label">Total Profit</span>
                <span class="stat-value text-success"><?= formatRupiahShort($totals['profit']) ?></span>
            </div>
        </div>
    </div>
    
    <!-- Mobile Summary -->
    <div class="group-summary-mobile d-flex d-md-none">
        <span>Modal: <b class="text-warning-dark"><?= formatRupiahShort($totals['modal']) ?></b></span>
        <span>Tagihan: <b><?= formatRupiahShort($totals['tagihan']) ?></b></span>
        <span>Profit: <b class="text-success"><?= formatRupiahShort($totals['profit']) ?></b></span>
    </div>
    
    <!-- Orders Table - Desktop -->
    <div class="table-responsive d-none d-md-block">
        <table class="table analysis-table mb-0">
            <thead>
                <tr>
                    <th style="width:30px"><input type="checkbox" class="group-select-all" data-group="<?= md5($supportName) ?>"></th>
                    <th>Layanan</th>
                    <th>Event</th>
                    <th class="text-center">Qty×Hari</th>
                    <th class="text-end">Modal/Unit</th>
                    <?php if (!empty($hasSbmColumns)): ?>
                    <th class="text-end">SBM</th>
                    <?php endif; ?>
                    <th class="text-center">Markup</th>
                    <th class="text-end">Tagihan/Unit</th>
                    <th class="text-end">Profit</th>
                    <th class="text-center">Bayar</th>
                    <th class="text-center">Vendor</th>
                    <?php if (!empty($hasFilingColumn)): ?>
                    <th class="text-center">Berkas</th>
                    <?php endif; ?>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($orders as $order): 
                    $totalModal = (float)$order['modal'];
                    $totalQty = (int)($order['total_quantity'] ?? 1);
                    $totalDays = (int)($order['total_days'] ?? 1);
                    
                    if (!empty($order['unit_price'])) {
                        $unitPrice = (float)$order['unit_price'];
                    } else {
                        $divider = max($totalQty * $totalDays, 1);
                        $unitPrice = $totalModal / $divider;
                    }
                    
                    $currentMarkup = ($totalModal > 0 && $order['markup'] > 0) 
                        ? (($order['markup'] / $totalModal) * 100) 
                        : (float)($order['sbm_markup_percent'] ?? 0);
                    
                    $unitTagihan = $unitPrice * (1 + ($currentMarkup / 100));
                    $totalProfit = (float)$order['profit'];
                    
                    $itemType = $order['item_type'] ?? 'other';
                    $serviceType = $itemTypes[$itemType] ?? ['label' => 'Lainnya', 'icon' => 'box', 'color' => 'secondary'];
                    
                    // Expense status dan paid amount
                    $expenseStatus = $order['expense_status'] ?? 'pending';
                    $expensePaid = (float)($order['expense_paid'] ?? 0);
                    
                    // Determine badge
                    if ($expenseStatus === 'paid') {
                        $expenseBadgeClass = 'badge-success';
                        $expenseBadgeText = 'Lunas';
                    } elseif ($expensePaid > 0) {
                        $expenseBadgeClass = 'badge-info';
                        $pct = ($totalModal > 0) ? round(($expensePaid / $totalModal) * 100) : 0;
                        $expenseBadgeText = 'DP ' . $pct . '%';
                        $expenseStatus = 'partial';
                    } else {
                        $expenseBadgeClass = 'badge-warning';
                        $expenseBadgeText = 'Pending';
                    }
                ?>
                <tr data-order-id="<?= $order['id'] ?>" data-group="<?= md5($supportName) ?>">
                    <td><input type="checkbox" class="order-checkbox" value="<?= $order['id'] ?>"></td>
                    <td>
                        <a href="<?= url('/orders/' . $order['id']) ?>" class="service-badge service-<?= $serviceType['color'] ?>">
                            <i class="fas fa-<?= $serviceType['icon'] ?>"></i>
                            <?= $serviceType['label'] ?>
                        </a>
                    </td>
                    <td>
                        <a href="<?= url('/orders/' . $order['id']) ?>" class="event-link"><?= e($order['event_name'] ?: '-') ?></a>
                        <small class="d-block text-muted"><?= formatDate($order['order_date']) ?></small>
                    </td>
                    <td class="text-center">
                        <span class="badge badge-light text-dark">
                            <?= $totalQty ?> × <?= $totalDays ?>
                        </span>
                    </td>
                    <td class="text-end text-warning-dark fw-semibold">
                        <span class="unit-modal" data-order-id="<?= $order['id'] ?>"><?= formatRupiah($unitPrice) ?></span>
                    </td>
                    <?php if (!empty($hasSbmColumns)): ?>
                    <td class="text-end">
                        <input type="text" class="form-control form-control-sm sbm-input"
                               value="<?= number_format($order['sbm_price'], 0, ',', '.') ?>"
                               data-order-id="<?= $order['id'] ?>"
                               data-original="<?= $order['sbm_price'] ?>"
                               onchange="updateSbm(this)" onblur="formatSbmInput(this)" onfocus="unformatSbmInput(this)">
                    </td>
                    <?php endif; ?>
                    <td class="text-center">
                        <div class="markup-wrapper">
                            <input type="number" class="form-control form-control-sm markup-input"
                                   value="<?= number_format($currentMarkup, 1) ?>"
                                   data-order-id="<?= $order['id'] ?>"
                                   data-original="<?= $currentMarkup ?>"
                                   data-unit-price="<?= $unitPrice ?>"
                                   step="0.1" min="0" max="100"
                                   onchange="updateMarkup(this)">
                            <span class="markup-pct">%</span>
                        </div>
                    </td>
                    <td class="text-end fw-bold">
                        <span class="unit-tagihan text-primary" data-order-id="<?= $order['id'] ?>"><?= formatRupiah($unitTagihan) ?></span>
                    </td>
                    <td class="text-end">
                        <span class="amount-profit text-success" data-order-id="<?= $order['id'] ?>">
                            <?= formatRupiah($totalProfit) ?>
                        </span>
                    </td>
                    <td class="text-center">
                        <?php $ps = $paymentStatuses[$order['payment_status']] ?? ['label' => '-', 'color' => 'secondary']; ?>
                        <span class="badge badge-<?= $ps['color'] ?> payment-badge" 
                              data-order-id="<?= $order['id'] ?>"
                              data-status="<?= $order['payment_status'] ?>"
                              onclick="togglePaymentStatus(this)"
                              title="Klik untuk toggle">
                            <?= $ps['label'] ?>
                        </span>
                    </td>
                    <td class="text-center">
                        <span class="badge expense-badge <?= $expenseBadgeClass ?>" 
                              data-order-id="<?= $order['id'] ?>"
                              data-status="<?= $expenseStatus ?>"
                              data-modal="<?= $totalModal ?>"
                              data-paid="<?= $expensePaid ?>"
                              onclick="showExpenseModal(this)"
                              title="Klik untuk kelola pembayaran vendor">
                            <?= $expenseBadgeText ?>
                        </span>
                    </td>
                    <?php if (!empty($hasFilingColumn)): ?>
                    <td class="text-center">
                        <select class="form-select form-select-sm filing-select filing-<?= $order['filing_status'] ?>" 
                                data-order-id="<?= $order['id'] ?>"
                                onchange="updateFilingStatus(this)">
                            <?php foreach ($filingStatuses as $fKey => $fStatus): ?>
                            <option value="<?= $fKey ?>" <?= $order['filing_status'] === $fKey ? 'selected' : '' ?>>
                                <?= $fStatus['label'] ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </td>
                    <?php endif; ?>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    
    <!-- Orders Cards - Mobile -->
    <div class="d-md-none">
        <?php foreach ($orders as $order): 
            $totalModal = (float)$order['modal'];
            $totalQty = (int)($order['total_quantity'] ?? 1);
            $totalDays = (int)($order['total_days'] ?? 1);
            
            if (!empty($order['unit_price'])) {
                $unitPrice = (float)$order['unit_price'];
            } else {
                $divider = max($totalQty * $totalDays, 1);
                $unitPrice = $totalModal / $divider;
            }
            
            $currentMarkup = ($totalModal > 0 && $order['markup'] > 0) 
                ? (($order['markup'] / $totalModal) * 100) 
                : (float)($order['sbm_markup_percent'] ?? 0);
            
            $unitTagihan = $unitPrice * (1 + ($currentMarkup / 100));
            $totalProfit = (float)$order['profit'];
            
            $itemType = $order['item_type'] ?? 'other';
            $serviceType = $itemTypes[$itemType] ?? ['label' => 'Lainnya', 'icon' => 'box', 'color' => 'secondary'];
            
            $expenseStatus = $order['expense_status'] ?? 'pending';
            $expensePaid = (float)($order['expense_paid'] ?? 0);
            
            if ($expenseStatus === 'paid') {
                $expenseBadgeClass = 'badge-success';
                $expenseBadgeText = 'Lunas';
            } elseif ($expensePaid > 0) {
                $expenseBadgeClass = 'badge-info';
                $pct = ($totalModal > 0) ? round(($expensePaid / $totalModal) * 100) : 0;
                $expenseBadgeText = 'DP ' . $pct . '%';
                $expenseStatus = 'partial';
            } else {
                $expenseBadgeClass = 'badge-warning';
                $expenseBadgeText = 'Pending';
            }
        ?>
        <div class="order-card-mobile">
            <div class="order-card-header">
                <div class="order-card-service">
                    <span class="service-badge service-<?= $serviceType['color'] ?>">
                        <i class="fas fa-<?= $serviceType['icon'] ?>"></i>
                        <?= $serviceType['label'] ?>
                    </span>
                </div>
                <div class="order-card-badges">
                    <?php $ps = $paymentStatuses[$order['payment_status']] ?? ['label' => '-', 'color' => 'secondary']; ?>
                    <span class="badge badge-<?= $ps['color'] ?> payment-badge"
                          data-order-id="<?= $order['id'] ?>"
                          data-status="<?= $order['payment_status'] ?>"
                          onclick="togglePaymentStatus(this)">
                        <?= $ps['label'] ?>
                    </span>
                    <span class="badge expense-badge <?= $expenseBadgeClass ?>"
                          data-order-id="<?= $order['id'] ?>"
                          data-status="<?= $expenseStatus ?>"
                          data-modal="<?= $totalModal ?>"
                          data-paid="<?= $expensePaid ?>"
                          onclick="showExpenseModal(this)">
                        <?= $expenseBadgeText ?>
                    </span>
                </div>
            </div>
            <a href="<?= url('/orders/' . $order['id']) ?>" class="order-card-title">
                <?= e($order['event_name'] ?: '-') ?>
            </a>
            <div class="order-card-meta">
                <span><i class="fas fa-calendar"></i> <?= formatDate($order['order_date']) ?></span>
                <span><i class="fas fa-cube"></i> <?= $totalQty ?> × <?= $totalDays ?> hari</span>
            </div>
            <div class="order-card-amounts">
                <div class="amount-item">
                    <span class="amount-label">Modal/Unit</span>
                    <span class="amount-value text-warning-dark"><?= formatRupiah($unitPrice) ?></span>
                </div>
                <div class="amount-item">
                    <span class="amount-label">Markup</span>
                    <span class="amount-value"><?= number_format($currentMarkup, 1) ?>%</span>
                </div>
                <div class="amount-item">
                    <span class="amount-label">Tagihan/Unit</span>
                    <span class="amount-value text-primary fw-bold"><?= formatRupiah($unitTagihan) ?></span>
                </div>
                <div class="amount-item">
                    <span class="amount-label">Profit</span>
                    <span class="amount-value text-success fw-bold"><?= formatRupiah($totalProfit) ?></span>
                </div>
            </div>
            <?php if (!empty($hasFilingColumn)): ?>
            <div class="order-card-filing">
                <select class="form-select form-select-sm filing-select filing-<?= $order['filing_status'] ?>"
                        data-order-id="<?= $order['id'] ?>"
                        onchange="updateFilingStatus(this)">
                    <?php foreach ($filingStatuses as $fKey => $fStatus): ?>
                    <option value="<?= $fKey ?>" <?= $order['filing_status'] === $fKey ? 'selected' : '' ?>>
                        <?= $fStatus['label'] ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <?php endif; ?>
        </div>
        <?php endforeach; ?>
    </div>
</div>
<?php endforeach; ?>

<!-- Charts Section -->
<div class="row mt-4">
    <div class="col-md-8 mb-4">
        <div class="glass-card h-100">
            <h6 class="fw-bold mb-3"><i class="fas fa-chart-bar text-primary me-2"></i>Trend Bulanan</h6>
            <div style="height: 300px">
                <canvas id="monthlyChart"></canvas>
            </div>
        </div>
    </div>
    <div class="col-md-4 mb-4">
        <div class="glass-card h-100">
            <h6 class="fw-bold mb-3"><i class="fas fa-chart-pie text-primary me-2"></i>Per Dukungan</h6>
            <div style="height: 300px">
                <canvas id="supportChart"></canvas>
            </div>
        </div>
    </div>
</div>

<?php endif; ?>

<!-- Expense Modal (Bayar Vendor per Order) -->
<div class="modal-overlay" id="expenseModal" style="display:none">
    <div class="modal-container">
        <div class="modal-header">
            <h5 class="modal-title"><i class="fas fa-money-bill-wave"></i>Bayar ke Vendor</h5>
            <button type="button" class="btn-close" onclick="hideExpenseModal()" aria-label="Close"></button>
        </div>
        <div class="modal-body">
            <input type="hidden" id="expenseOrderId">
            
            <div class="expense-summary mb-4">
                <div class="row g-2">
                    <div class="col-4 text-center">
                        <div class="expense-stat">
                            <small class="text-muted d-block">Total Modal</small>
                            <strong id="expenseTotalModal" class="text-warning-dark">-</strong>
                        </div>
                    </div>
                    <div class="col-4 text-center">
                        <div class="expense-stat">
                            <small class="text-muted d-block">Sudah Bayar</small>
                            <strong id="expensePaidAmount" class="text-success">-</strong>
                        </div>
                    </div>
                    <div class="col-4 text-center">
                        <div class="expense-stat">
                            <small class="text-muted d-block">Sisa Hutang</small>
                            <strong id="expenseRemaining" class="text-danger">-</strong>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="mb-3">
                <label class="form-label">Jumlah Bayar <span class="text-danger">*</span></label>
                <input type="text" id="expensePayAmount" class="form-control" placeholder="Masukkan jumlah pembayaran..."
                       data-raw-value="0"
                       oninput="formatExpenseInput(this)" onblur="formatExpenseBlur(this)">
                <div class="form-text">
                    <button type="button" class="btn btn-sm btn-outline-secondary me-1" onclick="setExpenseAmount('remaining')">Sisa</button>
                    <button type="button" class="btn btn-sm btn-outline-secondary me-1" onclick="setExpenseAmount('50')">50%</button>
                    <button type="button" class="btn btn-sm btn-outline-secondary" onclick="setExpenseAmount('full')">Full</button>
                </div>
            </div>
            
            <div class="mb-3">
                <label class="form-label">Sumber Dana (Kas/Bank) <span class="text-danger">*</span></label>
                <select id="expenseBankCash" class="form-select">
                    <option value="">-- Pilih Kas/Bank --</option>
                    <?php if (!empty($bankCashAccounts)): ?>
                        <?php foreach ($bankCashAccounts as $acc): 
                            $typeLabel = ($acc['type'] ?? '') === 'cash' ? '💵 Kas' : '🏦 Bank';
                        ?>
                        <option value="<?= $acc['id'] ?>"><?= $typeLabel ?> - <?= e($acc['name']) ?> (<?= formatRupiah($acc['current_balance'] ?? 0) ?>)</option>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <option value="" disabled>-- Tidak ada data Kas/Bank --</option>
                    <?php endif; ?>
                </select>
                <?php if (empty($bankCashAccounts)): ?>
                <div class="form-text text-warning">
                    <i class="fas fa-exclamation-triangle me-1"></i>
                    Belum ada data Kas/Bank. Silakan tambah di menu <a href="<?= url('/bank-cash') ?>">Kas & Bank</a>
                </div>
                <?php endif; ?>
            </div>
            
            <div class="mb-3">
                <label class="form-label">Tanggal Bayar</label>
                <input type="date" id="expenseDate" class="form-control" value="<?= date('Y-m-d') ?>">
            </div>
            
            <div class="mb-3">
                <label class="form-label">Catatan (opsional)</label>
                <input type="text" id="expenseNotes" class="form-control" placeholder="Catatan pembayaran...">
            </div>
        </div>
        <div class="modal-footer">
            <button type="button" id="btnCancelExpense" class="btn btn-outline-danger" onclick="cancelExpense()" style="display:none">
                <i class="fas fa-undo"></i> Batalkan Semua
            </button>
            <div class="ms-auto d-flex gap-2">
                <button type="button" class="btn btn-secondary" onclick="hideExpenseModal()">
                    <i class="fas fa-times"></i> Batal
                </button>
                <button type="button" id="btnSaveExpense" class="btn btn-success" onclick="saveExpense()">
                    <i class="fas fa-check"></i> Bayar Sekarang
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Floating Action Buttons -->
<div class="fab-group">
    <div class="fab-item">
        <button type="button" class="fab-btn fab-vendor" onclick="showVendorRecapModal()" title="Hutang ke Vendor">
            <i class="fas fa-hand-holding-usd"></i>
        </button>
        <span class="fab-tooltip">Hutang Vendor</span>
    </div>
    <div class="fab-item">
        <a href="<?= url('/analysis/report') ?>" class="fab-btn fab-report" title="Laporan Analisis">
            <i class="fas fa-file-alt"></i>
        </a>
        <span class="fab-tooltip">Laporan</span>
    </div>
</div>

<!-- ================================================================ -->
<!-- VENDOR RECAP MODAL - PREMIUM GLASS MORPHISM v2 -->
<!-- ================================================================ -->
<div id="vendorRecapModal" class="vr-modal-overlay">
    <div class="vr-modal-container">
        <!-- Header -->
        <div class="vr-modal-header">
            <div class="vr-header-left">
                <div class="vr-header-icon">
                    <i class="fas fa-file-invoice-dollar"></i>
                </div>
                <div class="vr-header-text">
                    <h4>Hutang ke Vendor</h4>
                    <span>Rekap pembayaran yang belum dilunasi</span>
                </div>
            </div>
            <div class="vr-header-right">
                <button type="button" class="vr-header-btn" onclick="printVendorRecap()" title="Print">
                    <i class="fas fa-print"></i>
                </button>
                <button type="button" class="vr-header-btn" onclick="loadVendorRecapData()" title="Refresh">
                    <i class="fas fa-sync-alt"></i>
                </button>
                <button type="button" class="vr-close-btn" onclick="hideVendorRecapModal()">
                    <i class="fas fa-times"></i>
                </button>
            </div>
        </div>
        
        <!-- Body -->
        <div class="vr-modal-body" id="vendorRecapContent">
            <div class="vr-loading-state">
                <div class="vr-spinner"></div>
                <p>Memuat data...</p>
            </div>
        </div>
        
        <!-- Footer -->
        <div class="vr-modal-footer">
            <button type="button" class="vr-btn-close" onclick="hideVendorRecapModal()">
                <i class="fas fa-times me-2"></i>Tutup
            </button>
        </div>
    </div>
</div>

<!-- ================================================================ -->
<!-- STYLES -->
<!-- ================================================================ -->
<style>
/* ================ SUMMARY CARDS ================ */
.summary-card {
    background: white;
    border-radius: 16px;
    padding: 16px;
    display: flex;
    align-items: center;
    gap: 12px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.06);
    border: 1px solid rgba(0,0,0,0.04);
    transition: all 0.2s ease;
}
.summary-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0,0,0,0.1);
}
.summary-card.border-success {
    border-left: 3px solid #22c55e;
}
.summary-icon {
    width: 44px;
    height: 44px;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 18px;
}
.bg-primary-soft { background: rgba(200, 155, 44, 0.12); color: #c89b2c; }
.bg-warning-soft { background: rgba(251, 191, 36, 0.15); color: #f59e0b; }
.bg-info-soft { background: rgba(6, 182, 212, 0.12); color: #06b6d4; }
.bg-success-soft { background: rgba(34, 197, 94, 0.12); color: #22c55e; }
.bg-teal-soft { background: rgba(20, 184, 166, 0.12); color: #14b8a6; }
.bg-purple-soft { background: rgba(139, 92, 246, 0.12); color: #8b5cf6; }
.bg-danger-soft { background: rgba(239, 68, 68, 0.12); color: #ef4444; }
.summary-content {
    display: flex;
    flex-direction: column;
}
.summary-value {
    font-size: 1.1rem;
    font-weight: 700;
    color: #1e293b;
    line-height: 1.2;
}
.summary-label {
    font-size: 0.75rem;
    color: #64748b;
    margin-top: 2px;
}

/* ================ GLASS CARD ================ */
.glass-card {
    background: rgba(255, 255, 255, 0.95);
    backdrop-filter: blur(10px);
    border-radius: 16px;
    padding: 20px;
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
    border: 1px solid rgba(255, 255, 255, 0.8);
}

/* ================ GROUP HEADER ================ */
.group-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding-bottom: 15px;
    border-bottom: 1px solid #e5e7eb;
    margin-bottom: 15px;
}
.group-title {
    display: flex;
    align-items: center;
    gap: 10px;
}
.group-title h5 {
    margin: 0;
    font-size: 1rem;
    font-weight: 600;
    color: #1e293b;
}
.group-summary {
    display: flex;
    gap: 20px;
}
.group-stat {
    text-align: right;
}
.stat-label {
    font-size: 0.7rem;
    color: #94a3b8;
    display: block;
}
.stat-value {
    font-size: 0.9rem;
    font-weight: 600;
}
.group-summary-mobile {
    justify-content: space-between;
    padding: 10px 0;
    font-size: 0.8rem;
    border-bottom: 1px solid #e5e7eb;
    margin-bottom: 10px;
}
.text-warning-dark { color: #d97706; }

/* ================ TABLE STYLES ================ */
.analysis-table {
    font-size: 0.85rem;
}
.analysis-table th {
    font-weight: 600;
    color: #64748b;
    font-size: 0.75rem;
    text-transform: uppercase;
    padding: 10px 8px;
    border-bottom: 2px solid #e5e7eb;
    white-space: nowrap;
}
.analysis-table td {
    padding: 12px 8px;
    vertical-align: middle;
    border-bottom: 1px solid #f1f5f9;
}
.analysis-table tbody tr:hover {
    background: #f8fafc;
}

/* ================ SERVICE BADGE ================ */
.service-badge {
    display: inline-flex;
    align-items: center;
    gap: 5px;
    padding: 4px 10px;
    border-radius: 20px;
    font-size: 0.75rem;
    font-weight: 500;
    text-decoration: none;
    transition: all 0.2s;
}
.service-badge:hover {
    transform: scale(1.05);
}
.service-primary { background: rgba(200, 155, 44, 0.1); color: #c89b2c; }
.service-success { background: rgba(34, 197, 94, 0.1); color: #22c55e; }
.service-warning { background: rgba(251, 191, 36, 0.1); color: #f59e0b; }
.service-info { background: rgba(6, 182, 212, 0.1); color: #06b6d4; }
.service-secondary { background: rgba(100, 116, 139, 0.1); color: #64748b; }
.service-purple { background: rgba(139, 92, 246, 0.1); color: #8b5cf6; }
.service-pink { background: rgba(236, 72, 153, 0.1); color: #ec4899; }

/* ================ EVENT LINK ================ */
.event-link {
    color: #1e293b;
    text-decoration: none;
    font-weight: 500;
}
.event-link:hover {
    color: #c89b2c;
}

/* ================ BADGES ================ */
.badge {
    padding: 5px 10px;
    border-radius: 20px;
    font-size: 0.7rem;
    font-weight: 600;
    cursor: pointer;
}
.badge-primary { background: #c89b2c; color: white; }
.badge-success { background: #22c55e; color: white; }
.badge-warning { background: #f59e0b; color: white; }
.badge-danger { background: #ef4444; color: white; }
.badge-info { background: #06b6d4; color: white; }
.badge-secondary { background: #64748b; color: white; }
.badge-light { background: #f1f5f9; color: #475569; }
.payment-badge, .expense-badge {
    transition: all 0.2s;
}
.payment-badge:hover, .expense-badge:hover {
    transform: scale(1.1);
    box-shadow: 0 2px 8px rgba(0,0,0,0.15);
}

/* ================ INPUTS ================ */
.markup-wrapper {
    position: relative;
    display: inline-block;
}
.markup-input {
    width: 60px;
    text-align: center;
    padding-right: 20px;
}
.markup-pct {
    position: absolute;
    right: 8px;
    top: 50%;
    transform: translateY(-50%);
    color: #94a3b8;
    font-size: 0.75rem;
}
.sbm-input {
    width: 90px;
    text-align: right;
}
.filing-select {
    font-size: 0.75rem;
    padding: 4px 8px;
    border-radius: 8px;
    min-width: 90px;
}
.filing-pending { border-color: #f59e0b; background: rgba(251, 191, 36, 0.1); }
.filing-in_progress { border-color: #c89b2c; background: rgba(200, 155, 44, 0.1); }
.filing-completed { border-color: #22c55e; background: rgba(34, 197, 94, 0.1); }

/* ================ MOBILE CARDS ================ */
.order-card-mobile {
    background: white;
    border-radius: 12px;
    padding: 15px;
    margin-bottom: 12px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.06);
}
.order-card-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 10px;
}
.order-card-badges {
    display: flex;
    gap: 5px;
}
.order-card-title {
    display: block;
    font-weight: 600;
    color: #1e293b;
    text-decoration: none;
    margin-bottom: 8px;
}
.order-card-meta {
    display: flex;
    gap: 15px;
    font-size: 0.75rem;
    color: #64748b;
    margin-bottom: 12px;
}
.order-card-meta i {
    margin-right: 4px;
}
.order-card-amounts {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 10px;
    padding-top: 10px;
    border-top: 1px solid #f1f5f9;
}
.amount-item {
    display: flex;
    flex-direction: column;
}
.amount-label {
    font-size: 0.7rem;
    color: #94a3b8;
}
.amount-value {
    font-size: 0.85rem;
}
.order-card-filing {
    margin-top: 10px;
    padding-top: 10px;
    border-top: 1px solid #f1f5f9;
}

/* ================ EXPENSE MODAL (BAYAR VENDOR) ================ */
.modal-overlay {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: linear-gradient(135deg, rgba(15, 23, 42, 0.8) 0%, rgba(30, 41, 59, 0.9) 100%);
    backdrop-filter: blur(6px);
    -webkit-backdrop-filter: blur(6px);
    display: flex;
    justify-content: center;
    align-items: center;
    z-index: 99998;
    padding: 20px;
    animation: modalFadeIn 0.25s ease;
}
@keyframes modalFadeIn {
    from { opacity: 0; }
    to { opacity: 1; }
}
.modal-container {
    background: white;
    border-radius: 20px;
    width: 100%;
    max-width: 480px;
    max-height: 90vh;
    overflow: hidden;
    display: flex;
    flex-direction: column;
    box-shadow: 0 25px 60px rgba(0,0,0,0.3), 0 0 0 1px rgba(255,255,255,0.1);
    animation: modalSlideUp 0.3s cubic-bezier(0.16, 1, 0.3, 1);
}
@keyframes modalSlideUp {
    from { opacity: 0; transform: translateY(20px) scale(0.98); }
    to { opacity: 1; transform: translateY(0) scale(1); }
}
.modal-header {
    background: linear-gradient(135deg, #10b981 0%, #059669 100%);
    padding: 18px 24px;
    display: flex;
    justify-content: space-between;
    align-items: center;
    position: relative;
}
.modal-header::after {
    content: '';
    position: absolute;
    inset: 0;
    background: url("data:image/svg+xml,%3Csvg width='40' height='40' viewBox='0 0 40 40' xmlns='http://www.w3.org/2000/svg'%3E%3Cg fill='%23ffffff' fill-opacity='0.05'%3E%3Cpath d='M20 20v-10h-10v10h10zm0 10v-10h10v10h-10z'/%3E%3C/g%3E%3C/svg%3E");
    pointer-events: none;
}
.modal-title {
    margin: 0;
    font-size: 1.15rem;
    font-weight: 700;
    color: white;
    position: relative;
    z-index: 1;
    display: flex;
    align-items: center;
}
.modal-title i {
    margin-right: 10px;
    font-size: 1.1rem;
}
.modal-header .btn-close {
    width: 36px;
    height: 36px;
    border-radius: 50%;
    border: none;
    background: rgba(255,255,255,0.15);
    color: white;
    font-size: 18px;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: all 0.2s;
    position: relative;
    z-index: 1;
}
.modal-header .btn-close:hover {
    background: rgba(255,255,255,0.25);
    transform: rotate(90deg);
}
.modal-header .btn-close::before {
    content: '×';
    font-size: 24px;
    line-height: 1;
}
.modal-body {
    padding: 24px;
    overflow-y: auto;
    flex: 1;
    background: #ffffff;
}
.modal-footer {
    padding: 16px 24px;
    background: #f8fafc;
    border-top: 1px solid #e2e8f0;
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 12px;
}
.expense-summary {
    background: linear-gradient(135deg, #f0fdf4 0%, #dcfce7 100%);
    border: 1px solid #bbf7d0;
    border-radius: 16px;
    padding: 16px;
    margin-bottom: 8px;
}
.expense-stat {
    padding: 12px 8px;
    background: white;
    border-radius: 12px;
    box-shadow: 0 2px 6px rgba(0,0,0,0.04);
}
.expense-stat small {
    font-size: 0.7rem;
    text-transform: uppercase;
    letter-spacing: 0.03em;
}
.expense-stat strong {
    font-size: 1rem;
    display: block;
    margin-top: 4px;
}

/* Form Controls in Modal */
.modal-body .form-label {
    font-weight: 600;
    color: #374151;
    font-size: 0.85rem;
    margin-bottom: 6px;
}
.modal-body .form-control,
.modal-body .form-select {
    border: 2px solid #e5e7eb;
    border-radius: 10px;
    padding: 10px 14px;
    font-size: 0.95rem;
    transition: all 0.2s;
}
.modal-body .form-control:focus,
.modal-body .form-select:focus {
    border-color: #10b981;
    box-shadow: 0 0 0 3px rgba(16, 185, 129, 0.15);
    outline: none;
}
.modal-body .form-text {
    margin-top: 8px;
}
.modal-body .form-text .btn {
    font-size: 0.75rem;
    padding: 4px 10px;
    border-radius: 6px;
}

/* Modal Buttons */
.modal-footer .btn {
    padding: 10px 20px;
    border-radius: 10px;
    font-weight: 600;
    font-size: 0.9rem;
    display: inline-flex;
    align-items: center;
    gap: 6px;
    transition: all 0.2s;
}
.modal-footer .btn-success {
    background: linear-gradient(135deg, #10b981 0%, #059669 100%);
    border: none;
    color: white;
    box-shadow: 0 4px 12px rgba(16, 185, 129, 0.3);
}
.modal-footer .btn-success:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 16px rgba(16, 185, 129, 0.4);
}
.modal-footer .btn-success:disabled {
    opacity: 0.7;
    transform: none;
}
.modal-footer .btn-secondary {
    background: #f1f5f9;
    border: 1px solid #e2e8f0;
    color: #64748b;
}
.modal-footer .btn-secondary:hover {
    background: #e2e8f0;
}
.modal-footer .btn-outline-danger {
    border: 2px solid #fecaca;
    background: #fef2f2;
    color: #dc2626;
}
.modal-footer .btn-outline-danger:hover {
    background: #fee2e2;
    border-color: #fca5a5;
}

/* ================ FLOATING ACTION BUTTONS ================ */
.fab-group {
    position: fixed;
    bottom: 30px;
    right: 30px;
    z-index: 1000;
    display: flex;
    flex-direction: column;
    gap: 15px;
}
.fab-item {
    position: relative;
    display: flex;
    align-items: center;
    justify-content: flex-end;
}
.fab-btn {
    width: 56px;
    height: 56px;
    border-radius: 50%;
    border: none;
    color: white;
    font-size: 22px;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: all 0.3s ease;
    text-decoration: none;
}
.fab-vendor {
    background: linear-gradient(135deg, #8b5cf6 0%, #6366f1 100%);
    box-shadow: 0 4px 15px rgba(139, 92, 246, 0.4);
}
.fab-vendor:hover {
    transform: scale(1.1);
    box-shadow: 0 6px 25px rgba(139, 92, 246, 0.6);
}
.fab-report {
    background: linear-gradient(135deg, #c89b2c 0%, #85661a 100%);
    box-shadow: 0 4px 15px rgba(200, 155, 44, 0.4);
}
.fab-report:hover {
    transform: scale(1.1);
    box-shadow: 0 6px 25px rgba(200, 155, 44, 0.6);
    color: white;
}
.fab-tooltip {
    position: absolute;
    right: 70px;
    background: white;
    color: #1e293b;
    padding: 6px 12px;
    border-radius: 8px;
    font-size: 0.75rem;
    font-weight: 600;
    white-space: nowrap;
    box-shadow: 0 2px 10px rgba(0,0,0,0.15);
    opacity: 0;
    visibility: hidden;
    transition: all 0.2s;
}
.fab-item:hover .fab-tooltip {
    opacity: 1;
    visibility: visible;
}

/* ================================================================ */
/* VENDOR RECAP MODAL - PREMIUM GLASS v2 */
/* ================================================================ */
.vr-modal-overlay {
    display: none;
    position: fixed;
    inset: 0;
    background: linear-gradient(135deg, rgba(15, 23, 42, 0.85) 0%, rgba(30, 41, 59, 0.92) 100%);
    backdrop-filter: blur(8px);
    -webkit-backdrop-filter: blur(8px);
    z-index: 99999;
    justify-content: center;
    align-items: center;
    padding: 16px;
}
.vr-modal-overlay.show {
    display: flex !important;
    animation: vrFadeIn 0.3s ease-out;
}
@keyframes vrFadeIn {
    from { opacity: 0; }
    to { opacity: 1; }
}

.vr-modal-container {
    background: #ffffff;
    border-radius: 24px;
    width: 100%;
    max-width: 1100px;
    max-height: 92vh;
    display: flex;
    flex-direction: column;
    box-shadow: 
        0 0 0 1px rgba(255,255,255,0.1),
        0 20px 50px rgba(0, 0, 0, 0.3),
        0 0 100px rgba(99, 102, 241, 0.1);
    animation: vrSlideUp 0.4s cubic-bezier(0.16, 1, 0.3, 1);
    overflow: hidden;
}
@keyframes vrSlideUp {
    from { opacity: 0; transform: translateY(30px) scale(0.98); }
    to { opacity: 1; transform: translateY(0) scale(1); }
}

/* Header */
.vr-modal-header {
    background: linear-gradient(135deg, #4f46e5 0%, #7c3aed 100%);
    padding: 20px 24px;
    display: flex;
    justify-content: space-between;
    align-items: center;
    position: relative;
}
.vr-modal-header::after {
    content: '';
    position: absolute;
    inset: 0;
    background: url("data:image/svg+xml,%3Csvg width='40' height='40' viewBox='0 0 40 40' xmlns='http://www.w3.org/2000/svg'%3E%3Cg fill='%23ffffff' fill-opacity='0.03'%3E%3Cpath d='M20 20v-10h-10v10h10zm0 10v-10h10v10h-10z'/%3E%3C/g%3E%3C/svg%3E");
    pointer-events: none;
}
.vr-header-left {
    display: flex;
    align-items: center;
    gap: 16px;
    position: relative;
    z-index: 1;
}
.vr-header-icon {
    width: 48px;
    height: 48px;
    background: rgba(255,255,255,0.15);
    border-radius: 14px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 22px;
    color: white;
}
.vr-header-text h4 {
    margin: 0;
    font-size: 1.25rem;
    font-weight: 700;
    color: white;
}
.vr-header-text span {
    font-size: 0.85rem;
    color: rgba(255,255,255,0.8);
}
.vr-header-right {
    display: flex;
    gap: 8px;
    position: relative;
    z-index: 1;
}
.vr-header-btn {
    width: 40px;
    height: 40px;
    border: none;
    background: rgba(255,255,255,0.15);
    border-radius: 10px;
    color: white;
    font-size: 16px;
    cursor: pointer;
    transition: all 0.2s;
    display: flex;
    align-items: center;
    justify-content: center;
}
.vr-header-btn:hover {
    background: rgba(255,255,255,0.25);
    transform: scale(1.05);
}
.vr-close-btn {
    width: 40px;
    height: 40px;
    border: none;
    background: rgba(255,255,255,0.1);
    border-radius: 50%;
    color: white;
    font-size: 18px;
    cursor: pointer;
    transition: all 0.2s;
    display: flex;
    align-items: center;
    justify-content: center;
}
.vr-close-btn:hover {
    background: rgba(239,68,68,0.8);
    transform: rotate(90deg);
}

/* Body */
.vr-modal-body {
    flex: 1;
    overflow-y: auto;
    padding: 24px;
    background: linear-gradient(180deg, #f8fafc 0%, #ffffff 100%);
}

/* Loading */
.vr-loading-state {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    padding: 80px 20px;
}
.vr-spinner {
    width: 44px;
    height: 44px;
    border: 3px solid #e2e8f0;
    border-top-color: #6366f1;
    border-radius: 50%;
    animation: vrSpin 0.7s linear infinite;
}
@keyframes vrSpin {
    to { transform: rotate(360deg); }
}
.vr-loading-state p {
    margin-top: 16px;
    color: #64748b;
}

/* Category Summary Cards */
.vr-cat-summary {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(140px, 1fr));
    gap: 12px;
    margin-bottom: 24px;
}
.vr-cat-card {
    background: white;
    border-radius: 16px;
    padding: 16px;
    text-align: center;
    box-shadow: 0 2px 8px rgba(0,0,0,0.04);
    border: 1px solid #f1f5f9;
    cursor: pointer;
    transition: all 0.25s ease;
    position: relative;
    overflow: hidden;
}
.vr-cat-card::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    height: 3px;
    opacity: 0.8;
}
.vr-cat-card.flight::before { background: linear-gradient(90deg, #c89b2c, #e0b23e); }
.vr-cat-card.hotel::before { background: linear-gradient(90deg, #f59e0b, #fbbf24); }
.vr-cat-card.rental::before { background: linear-gradient(90deg, #8b5cf6, #a78bfa); }
.vr-cat-card.bus::before { background: linear-gradient(90deg, #22c55e, #4ade80); }
.vr-cat-card.towing::before { background: linear-gradient(90deg, #ef4444, #f87171); }
.vr-cat-card.restaurant::before { background: linear-gradient(90deg, #ec4899, #f472b6); }
.vr-cat-card.other::before { background: linear-gradient(90deg, #64748b, #94a3b8); }
.vr-cat-card.all::before { background: linear-gradient(90deg, #4f46e5, #7c3aed); }

.vr-cat-card:hover {
    transform: translateY(-3px);
    box-shadow: 0 8px 20px rgba(0,0,0,0.08);
}
.vr-cat-card.active {
    border-color: #6366f1;
    box-shadow: 0 0 0 2px rgba(99,102,241,0.2), 0 8px 20px rgba(99,102,241,0.15);
}
.vr-cat-icon {
    font-size: 24px;
    margin-bottom: 8px;
}
.vr-cat-name {
    font-size: 0.8rem;
    font-weight: 600;
    color: #475569;
    margin-bottom: 4px;
}
.vr-cat-amount {
    font-size: 1rem;
    font-weight: 800;
    color: #dc2626;
}
.vr-cat-count {
    font-size: 0.7rem;
    color: #94a3b8;
    margin-top: 2px;
}

/* Grand Total Card */
.vr-grand-total {
    background: linear-gradient(135deg, #fef2f2 0%, #fee2e2 100%);
    border: 2px solid #fecaca;
    border-radius: 16px;
    padding: 20px;
    margin-bottom: 24px;
    display: flex;
    justify-content: space-between;
    align-items: center;
}
.vr-grand-total-left {
    display: flex;
    align-items: center;
    gap: 16px;
}
.vr-grand-total-icon {
    width: 52px;
    height: 52px;
    background: linear-gradient(135deg, #ef4444, #dc2626);
    border-radius: 14px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 24px;
    color: white;
}
.vr-grand-total-label {
    font-size: 0.9rem;
    color: #991b1b;
    font-weight: 500;
}
.vr-grand-total-value {
    font-size: 1.75rem;
    font-weight: 800;
    color: #dc2626;
}
.vr-grand-total-info {
    text-align: right;
}
.vr-gt-paid {
    font-size: 0.85rem;
    color: #16a34a;
}
.vr-gt-modal {
    font-size: 0.8rem;
    color: #64748b;
}

/* Table Section */
.vr-table-section {
    background: white;
    border-radius: 16px;
    overflow: hidden;
    box-shadow: 0 2px 8px rgba(0,0,0,0.04);
    border: 1px solid #f1f5f9;
}
.vr-table-header {
    background: #f8fafc;
    padding: 16px 20px;
    display: flex;
    justify-content: space-between;
    align-items: center;
    border-bottom: 1px solid #e2e8f0;
}
.vr-table-title {
    font-size: 0.95rem;
    font-weight: 700;
    color: #1e293b;
    display: flex;
    align-items: center;
    gap: 8px;
}
.vr-table-title i {
    color: #6366f1;
}
.vr-table-badge {
    background: #6366f1;
    color: white;
    padding: 4px 10px;
    border-radius: 20px;
    font-size: 0.75rem;
    font-weight: 600;
}
.vr-table-wrap {
    max-height: 400px;
    overflow-y: auto;
}
.vr-table {
    width: 100%;
    border-collapse: collapse;
}
.vr-table th {
    background: #f1f5f9;
    color: #475569;
    padding: 12px 16px;
    text-align: left;
    font-weight: 600;
    font-size: 0.75rem;
    text-transform: uppercase;
    letter-spacing: 0.03em;
    position: sticky;
    top: 0;
    z-index: 10;
}
.vr-table td {
    padding: 14px 16px;
    border-bottom: 1px solid #f1f5f9;
    font-size: 0.85rem;
    color: #475569;
}
.vr-table tbody tr:hover {
    background: #fafafa;
}
.vr-table .text-end { text-align: right; }
.vr-table .fw-bold { font-weight: 700; }
.vr-table .text-danger { color: #dc2626; }
.vr-table .event-cell {
    font-weight: 600;
    color: #1e293b;
}
.vr-table .support-cell {
    font-size: 0.8rem;
    color: #64748b;
}
.vr-table .date-cell {
    color: #94a3b8;
    font-size: 0.8rem;
}
.vr-table .amount-cell {
    font-weight: 700;
    color: #dc2626;
    font-family: 'SF Mono', Monaco, 'Consolas', monospace;
}
.vr-table .cat-badge {
    display: inline-flex;
    align-items: center;
    gap: 4px;
    padding: 4px 8px;
    border-radius: 6px;
    font-size: 0.75rem;
    font-weight: 500;
}
.vr-table .cat-badge.flight { background: rgba(200, 155, 44,0.1); color: #a67f20; }
.vr-table .cat-badge.hotel { background: rgba(245,158,11,0.1); color: #d97706; }
.vr-table .cat-badge.rental { background: rgba(139,92,246,0.1); color: #7c3aed; }
.vr-table .cat-badge.bus { background: rgba(34,197,94,0.1); color: #16a34a; }
.vr-table .cat-badge.towing { background: rgba(239,68,68,0.1); color: #dc2626; }
.vr-table .cat-badge.restaurant { background: rgba(236,72,153,0.1); color: #db2777; }
.vr-table .cat-badge.other { background: rgba(100,116,139,0.1); color: #475569; }

/* Empty State */
.vr-empty-state {
    text-align: center;
    padding: 60px 20px;
}
.vr-empty-icon {
    width: 80px;
    height: 80px;
    background: linear-gradient(135deg, #22c55e, #16a34a);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0 auto 20px;
    font-size: 36px;
    color: white;
    box-shadow: 0 8px 24px rgba(34, 197, 94, 0.3);
}
.vr-empty-state h4 {
    color: #22c55e;
    margin-bottom: 8px;
}
.vr-empty-state p {
    color: #64748b;
}

/* Footer */
.vr-modal-footer {
    padding: 16px 24px;
    background: #f8fafc;
    border-top: 1px solid #e2e8f0;
    display: flex;
    justify-content: flex-end;
}
.vr-btn-close {
    padding: 12px 24px;
    border: 1px solid #e2e8f0;
    background: white;
    border-radius: 12px;
    font-size: 0.9rem;
    font-weight: 600;
    color: #475569;
    cursor: pointer;
    transition: all 0.2s;
    display: flex;
    align-items: center;
}
.vr-btn-close:hover {
    background: #f1f5f9;
    border-color: #cbd5e1;
}

/* ================ TOAST NOTIFICATION ================ */
.toast-notification {
    position: fixed;
    bottom: 30px;
    left: 50%;
    transform: translateX(-50%) translateY(100px);
    background: #1e293b;
    color: white;
    padding: 12px 24px;
    border-radius: 12px;
    display: flex;
    align-items: center;
    gap: 10px;
    box-shadow: 0 10px 40px rgba(0,0,0,0.2);
    z-index: 99999;
    opacity: 0;
    transition: all 0.3s ease;
}
.toast-notification.show {
    transform: translateX(-50%) translateY(0);
    opacity: 1;
}
.toast-success { background: linear-gradient(135deg, #22c55e, #16a34a); }
.toast-error { background: linear-gradient(135deg, #ef4444, #dc2626); }
.toast-warning { background: linear-gradient(135deg, #f59e0b, #d97706); }

/* ================ EMPTY STATE ================ */
.empty-state {
    text-align: center;
    padding: 60px 20px;
}
.empty-state-icon {
    font-size: 4rem;
    color: #cbd5e1;
    margin-bottom: 20px;
}
.empty-state-title {
    color: #475569;
    margin-bottom: 10px;
}
.empty-state-text {
    color: #94a3b8;
}

/* ================ RESPONSIVE ================ */
@media (max-width: 767.98px) {
    .fab-group {
        bottom: 20px;
        right: 20px;
        gap: 12px;
    }
    .fab-btn {
        width: 50px;
        height: 50px;
        font-size: 18px;
    }
    .fab-tooltip {
        display: none;
    }
    .vr-cat-summary {
        grid-template-columns: repeat(2, 1fr);
    }
    .vr-modal-container {
        max-height: 95vh;
        border-radius: 20px;
    }
    .vr-modal-header {
        padding: 16px 18px;
    }
    .vr-modal-body {
        padding: 16px;
    }
    .vr-grand-total {
        flex-direction: column;
        gap: 12px;
        text-align: center;
    }
    .vr-grand-total-info {
        text-align: center;
    }
    .vr-table th, .vr-table td {
        padding: 10px 12px;
    }
}
</style>

<!-- ================================================================ -->
<!-- JAVASCRIPT -->
<!-- ================================================================ -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
var baseUrl = '<?= url('') ?>';

// ================================================================
// CSRF & AJAX Helper
// ================================================================
function postData(url, data, callback) {
    var formData = new FormData();
    var csrfToken = document.getElementById('csrf_token');
    if (csrfToken) formData.append('_token', csrfToken.value);
    
    for (var key in data) {
        if (data.hasOwnProperty(key)) {
            formData.append(key, data[key]);
        }
    }
    
    var xhr = new XMLHttpRequest();
    xhr.open('POST', url, true);
    xhr.onreadystatechange = function() {
        if (xhr.readyState === 4) {
            try {
                var response = JSON.parse(xhr.responseText);
                callback(null, response);
            } catch (e) {
                callback({ message: 'Error parsing response: ' + xhr.status }, null);
            }
        }
    };
    xhr.onerror = function() {
        callback({ message: 'Network error' }, null);
    };
    xhr.send(formData);
}

// ================================================================
// MARKUP FUNCTIONS
// ================================================================
function updateMarkup(input) {
    var orderId = input.dataset.orderId;
    var newMarkup = parseFloat(input.value) || 0;
    var unitPrice = parseFloat(input.dataset.unitPrice) || 0;
    
    var newUnitTagihan = unitPrice * (1 + (newMarkup / 100));
    var tagihanEl = document.querySelector('.unit-tagihan[data-order-id="'+orderId+'"]');
    if (tagihanEl) {
        tagihanEl.textContent = formatRupiahJS(newUnitTagihan);
    }
    
    postData(baseUrl + '/analysis/update-markup', {
        order_id: orderId,
        markup_percent: newMarkup
    }, function(err, data) {
        if (err) {
            showToast('Error: ' + err.message, 'error');
            input.value = input.dataset.original;
            return;
        }
        if (data.success) {
            input.dataset.original = newMarkup;
            var profitEl = document.querySelector('.amount-profit[data-order-id="'+orderId+'"]');
            if (profitEl && data.new_profit !== undefined) {
                profitEl.textContent = formatRupiahJS(data.new_profit);
            }
        } else {
            showToast(data.message || 'Gagal update', 'error');
            input.value = input.dataset.original;
        }
    });
}

// ================================================================
// SBM FUNCTIONS
// ================================================================
function updateSbm(input) {
    var orderId = input.dataset.orderId;
    var rawValue = input.value.replace(/\./g, '').replace(/,/g, '');
    var newSbm = parseInt(rawValue) || 0;
    
    postData(baseUrl + '/analysis/update-sbm', {
        order_id: orderId,
        sbm_price: newSbm
    }, function(err, data) {
        if (err) {
            showToast('Error: ' + err.message, 'error');
            return;
        }
        if (data.success) {
            input.dataset.original = newSbm;
            showToast('SBM diupdate', 'success');
        } else {
            showToast(data.message || 'Gagal update SBM', 'error');
        }
    });
}

function formatSbmInput(input) {
    var raw = input.value.replace(/\./g, '').replace(/,/g, '');
    var num = parseInt(raw) || 0;
    input.value = num.toLocaleString('id-ID');
}

function unformatSbmInput(input) {
    var raw = input.value.replace(/\./g, '').replace(/,/g, '');
    input.value = raw;
}

// ================================================================
// PAYMENT STATUS
// ================================================================
function togglePaymentStatus(badge) {
    var orderId = badge.dataset.orderId;
    var currentStatus = badge.dataset.status;
    
    var statusCycle = ['pending', 'dp', 'paid'];
    var currentIndex = statusCycle.indexOf(currentStatus);
    var newStatus = statusCycle[(currentIndex + 1) % statusCycle.length];
    
    postData(baseUrl + '/analysis/update-payment-status', {
        order_id: orderId,
        payment_status: newStatus
    }, function(err, data) {
        if (err) {
            showToast('Error: ' + err.message, 'error');
            return;
        }
        if (data.success) {
            badge.dataset.status = newStatus;
            var labels = { pending: 'Pending', dp: 'DP', paid: 'Lunas' };
            var colors = { pending: 'warning', dp: 'info', paid: 'success' };
            badge.textContent = labels[newStatus];
            badge.className = 'badge badge-' + colors[newStatus] + ' payment-badge';
            
            document.querySelectorAll('.payment-badge[data-order-id="'+orderId+'"]').forEach(function(b) {
                if (b !== badge) {
                    b.dataset.status = newStatus;
                    b.textContent = labels[newStatus];
                    b.className = 'badge badge-' + colors[newStatus] + ' payment-badge';
                }
            });
        } else {
            showToast(data.message || 'Gagal update', 'error');
        }
    });
}

// ================================================================
// FILING STATUS
// ================================================================
function updateFilingStatus(select) {
    var orderId = select.dataset.orderId;
    var newStatus = select.value;
    
    postData(baseUrl + '/analysis/update-filing-status', {
        order_id: orderId,
        filing_status: newStatus
    }, function(err, data) {
        if (err) {
            showToast('Error: ' + err.message, 'error');
            return;
        }
        if (data.success) {
            select.className = 'form-select form-select-sm filing-select filing-' + newStatus;
            document.querySelectorAll('.filing-select[data-order-id="'+orderId+'"]').forEach(function(s) {
                if (s !== select) {
                    s.value = newStatus;
                    s.className = 'form-select form-select-sm filing-select filing-' + newStatus;
                }
            });
        } else {
            showToast(data.message || 'Gagal update', 'error');
        }
    });
}

// ================================================================
// EXPENSE MODAL (Per Order)
// ================================================================
var expenseData = {};

function showExpenseModal(badge) {
    var orderId = badge.dataset.orderId;
    var modal = document.getElementById('expenseModal');
    var totalModal = parseFloat(badge.dataset.modal) || 0;
    var paidBefore = parseFloat(badge.dataset.paid) || 0;
    var remaining = totalModal - paidBefore;
    
    expenseData = {
        orderId: orderId,
        totalModal: totalModal,
        paidBefore: paidBefore,
        remaining: remaining
    };
    
    console.log('showExpenseModal Debug:', expenseData);
    
    document.getElementById('expenseOrderId').value = orderId;
    document.getElementById('expenseTotalModal').textContent = formatRupiahJS(totalModal);
    document.getElementById('expensePaidAmount').textContent = formatRupiahJS(paidBefore);
    document.getElementById('expenseRemaining').textContent = formatRupiahJS(remaining);
    
    // Reset input dan data-raw-value
    var payInput = document.getElementById('expensePayAmount');
    payInput.value = '';
    payInput.dataset.rawValue = 0;
    
    document.getElementById('expenseNotes').value = '';
    document.getElementById('expenseDate').value = new Date().toISOString().split('T')[0];
    
    // Reset dropdown
    document.getElementById('expenseBankCash').selectedIndex = 0;
    
    // Show/hide cancel button based on previous payments
    var cancelBtn = document.getElementById('btnCancelExpense');
    if (cancelBtn) {
        cancelBtn.style.display = paidBefore > 0 ? 'inline-flex' : 'none';
    }
    
    // Reset save button
    var saveBtn = document.getElementById('btnSaveExpense');
    if (saveBtn) {
        saveBtn.disabled = false;
        saveBtn.innerHTML = '<i class="fas fa-check"></i> Bayar Sekarang';
    }
    
    modal.style.display = 'flex';
}

function hideExpenseModal() {
    document.getElementById('expenseModal').style.display = 'none';
}

function setExpenseAmount(type) {
    var input = document.getElementById('expensePayAmount');
    var amount = 0;
    
    if (type === 'remaining') {
        amount = expenseData.remaining;
    } else if (type === 'full') {
        amount = expenseData.totalModal;
    } else if (type === '50') {
        amount = expenseData.totalModal * 0.5;
    }
    
    // Simpan nilai asli di data attribute dan tampilkan format lengkap (bukan singkat)
    input.dataset.rawValue = amount;
    input.value = 'Rp ' + Math.round(amount).toLocaleString('id-ID');
}

function formatExpenseInput(input) {
    var raw = input.value.replace(/[^\d]/g, '');
    if (raw) {
        var num = parseInt(raw);
        input.dataset.rawValue = num;
        input.value = 'Rp ' + num.toLocaleString('id-ID');
    } else {
        input.dataset.rawValue = 0;
    }
}

function formatExpenseBlur(input) {
    if (!input.value) {
        input.dataset.rawValue = 0;
        return;
    }
    var raw = input.value.replace(/[^\d]/g, '');
    if (raw) {
        var num = parseInt(raw);
        input.dataset.rawValue = num;
        input.value = 'Rp ' + num.toLocaleString('id-ID');
    }
}

function saveExpense() {
    var orderId = document.getElementById('expenseOrderId').value;
    var payAmountInput = document.getElementById('expensePayAmount');
    
    // Ambil dari data-raw-value jika ada, fallback ke parse manual
    var payAmount = parseInt(payAmountInput.dataset.rawValue) || 0;
    if (payAmount <= 0) {
        // Fallback: parse dari value
        var payAmountRaw = payAmountInput.value.replace(/[^\d]/g, '');
        payAmount = parseInt(payAmountRaw) || 0;
    }
    
    var bankCashId = document.getElementById('expenseBankCash').value;
    var expenseDate = document.getElementById('expenseDate').value;
    var notes = document.getElementById('expenseNotes').value;
    
    var totalModal = expenseData.totalModal;
    var paidBefore = expenseData.paidBefore;
    
    console.log('saveExpense Debug:', {
        orderId: orderId,
        payAmount: payAmount,
        bankCashId: bankCashId,
        totalModal: totalModal,
        paidBefore: paidBefore
    });
    
    if (payAmount <= 0) {
        showToast('Masukkan jumlah pembayaran', 'warning');
        payAmountInput.focus();
        return;
    }
    if (!bankCashId) {
        showToast('Pilih sumber dana (Kas/Bank)', 'warning');
        document.getElementById('expenseBankCash').focus();
        return;
    }
    
    var saveBtn = document.getElementById('btnSaveExpense');
    if (saveBtn) {
        saveBtn.disabled = true;
        saveBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Menyimpan...';
    }
    
    postData(baseUrl + '/analysis/update-expense-status', {
        order_id: orderId,
        expense_status: 'paid',
        pay_amount: payAmount,
        bank_cash_id: bankCashId,
        expense_date: expenseDate,
        notes: notes
    }, function(err, data) {
        if (saveBtn) {
            saveBtn.disabled = false;
            saveBtn.innerHTML = '<i class="fas fa-check"></i> Bayar Sekarang';
        }
        
        if (err) {
            showToast('Error: ' + err.message, 'error');
            return;
        }
        
        if (data.success) {
            var newPaid = paidBefore + payAmount;
            var isFullyPaid = newPaid >= totalModal;
            var newStatus = isFullyPaid ? 'paid' : 'partial';
            var badgeClass = isFullyPaid ? 'badge-success' : 'badge-info';
            var badgeText = isFullyPaid ? 'Lunas' : 'DP ' + Math.round((newPaid/totalModal)*100) + '%';
            
            document.querySelectorAll('.expense-badge[data-order-id="'+orderId+'"]').forEach(function(b){
                b.dataset.status = newStatus;
                b.dataset.paid = newPaid;
                b.className = 'badge expense-badge ' + badgeClass;
                b.textContent = badgeText;
            });
            
            hideExpenseModal();
            showToast(data.message || 'Pembayaran berhasil dicatat', 'success');
        } else {
            showToast(data.message || 'Gagal menyimpan pembayaran', 'error');
        }
    });
}

function cancelExpense() {
    var orderId = document.getElementById('expenseOrderId').value;
    
    if (!confirm('Batalkan SEMUA pembayaran vendor untuk order ini?\nSaldo kas/bank akan dikembalikan.')) return;
    
    var cancelBtn = document.getElementById('btnCancelExpense');
    if (cancelBtn) {
        cancelBtn.disabled = true;
        cancelBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Membatalkan...';
    }
    
    postData(baseUrl + '/analysis/update-expense-status', {
        order_id: orderId,
        expense_status: 'pending'
    }, function(err, data) {
        if (cancelBtn) {
            cancelBtn.disabled = false;
            cancelBtn.innerHTML = '<i class="fas fa-undo"></i> Batalkan Semua';
        }
        
        if (err) {
            showToast('Error: ' + err.message, 'error');
            return;
        }
        
        if (data.success) {
            document.querySelectorAll('.expense-badge[data-order-id="'+orderId+'"]').forEach(function(b){
                b.dataset.status = 'pending';
                b.dataset.paid = '0';
                b.className = 'badge expense-badge badge-warning';
                b.textContent = 'Pending';
            });
            
            hideExpenseModal();
            showToast(data.message || 'Pembayaran dibatalkan, saldo dikembalikan', 'success');
        } else {
            showToast(data.message || 'Gagal membatalkan pembayaran', 'error');
        }
    });
}

// ================================================================
// VENDOR RECAP MODAL - v2 with Category Filter
// ================================================================
var vrData = null;
var vrFilter = 'all';

function showVendorRecapModal() {
    document.getElementById('vendorRecapModal').classList.add('show');
    document.body.style.overflow = 'hidden';
    loadVendorRecapData();
}

function hideVendorRecapModal() {
    document.getElementById('vendorRecapModal').classList.remove('show');
    document.body.style.overflow = '';
}

document.addEventListener('click', function(e) {
    if (e.target && e.target.id === 'vendorRecapModal') {
        hideVendorRecapModal();
    }
});

function loadVendorRecapData() {
    var content = document.getElementById('vendorRecapContent');
    content.innerHTML = '<div class="vr-loading-state"><div class="vr-spinner"></div><p>Memuat data...</p></div>';
    
    fetch(baseUrl + '/analysis/vendor-recap')
        .then(function(r) { return r.json(); })
        .then(function(res) {
            if (res.success) {
                vrData = res.data;
                vrFilter = 'all';
                renderVendorRecap();
            } else {
                content.innerHTML = '<div class="alert alert-danger m-4"><i class="fas fa-exclamation-circle me-2"></i>' + (res.message || 'Gagal memuat data') + '</div>';
            }
        })
        .catch(function(err) {
            content.innerHTML = '<div class="alert alert-danger m-4"><i class="fas fa-exclamation-circle me-2"></i>Error: ' + err.message + '</div>';
        });
}

function renderVendorRecap() {
    var s = vrData.summary || {};
    var cats = vrData.by_category || [];
    var trx = vrData.transactions || [];
    
    // Check if empty
    if ((s.total_pending || 0) <= 0 || trx.length === 0) {
        document.getElementById('vendorRecapContent').innerHTML = 
            '<div class="vr-empty-state">' +
            '<div class="vr-empty-icon"><i class="fas fa-check"></i></div>' +
            '<h4>Tidak Ada Hutang</h4>' +
            '<p>Semua pembayaran ke vendor sudah lunas!</p>' +
            '</div>';
        return;
    }
    
    var html = '';
    
    // Category Summary Cards (Rekap per Kategori)
    html += '<div class="vr-cat-summary">';
    
    // Card "Semua"
    html += '<div class="vr-cat-card all active" onclick="filterVendorRecap(\'all\')">';
    html += '<div class="vr-cat-icon">📊</div>';
    html += '<div class="vr-cat-name">Semua</div>';
    html += '<div class="vr-cat-amount">' + fmtRp(s.total_pending) + '</div>';
    html += '<div class="vr-cat-count">' + trx.length + ' item</div>';
    html += '</div>';
    
    // Cards per Category
    cats.forEach(function(c) {
        if ((c.pending || 0) <= 0) return;
        html += '<div class="vr-cat-card ' + c.category + '" onclick="filterVendorRecap(\'' + c.category + '\')">';
        html += '<div class="vr-cat-icon">' + catIcon(c.category) + '</div>';
        html += '<div class="vr-cat-name">' + catLabel(c.category) + '</div>';
        html += '<div class="vr-cat-amount">' + fmtRp(c.pending) + '</div>';
        html += '<div class="vr-cat-count">' + c.count + ' item</div>';
        html += '</div>';
    });
    
    html += '</div>';
    
    // Grand Total
    html += '<div class="vr-grand-total">';
    html += '<div class="vr-grand-total-left">';
    html += '<div class="vr-grand-total-icon"><i class="fas fa-exclamation-triangle"></i></div>';
    html += '<div>';
    html += '<div class="vr-grand-total-label">Total Hutang ke Vendor</div>';
    html += '<div class="vr-grand-total-value" id="vrGrandTotalValue">' + fmtRp(s.total_pending) + '</div>';
    html += '</div>';
    html += '</div>';
    html += '<div class="vr-grand-total-info">';
    html += '<div class="vr-gt-paid"><i class="fas fa-check-circle me-1"></i>Sudah dibayar: ' + fmtRp(s.total_paid) + '</div>';
    html += '<div class="vr-gt-modal">Total modal: ' + fmtRp(s.total_modal) + '</div>';
    html += '</div>';
    html += '</div>';
    
    // Table Section
    html += '<div class="vr-table-section">';
    html += '<div class="vr-table-header">';
    html += '<div class="vr-table-title"><i class="fas fa-list-alt"></i> <span id="vrTableTitle">Detail Semua Kategori</span></div>';
    html += '<div class="vr-table-badge" id="vrTableBadge">' + trx.length + ' item</div>';
    html += '</div>';
    html += '<div class="vr-table-wrap" id="vrTableWrap">';
    html += renderVRTable(trx, 'all');
    html += '</div>';
    html += '</div>';
    
    document.getElementById('vendorRecapContent').innerHTML = html;
}

function renderVRTable(trx, filter) {
    var data = filter === 'all' ? trx : trx.filter(function(t) { return t.item_type === filter; });
    
    if (data.length === 0) {
        return '<div class="text-center py-5 text-muted">Tidak ada data untuk kategori ini</div>';
    }
    
    var html = '<table class="vr-table">';
    html += '<thead><tr>';
    html += '<th width="90">Tanggal</th>';
    html += '<th>Event / Dukungan</th>';
    if (filter === 'all') {
        html += '<th width="100">Kategori</th>';
    }
    html += '<th>Deskripsi</th>';
    html += '<th class="text-end" width="130">Jumlah</th>';
    html += '</tr></thead>';
    html += '<tbody>';
    
    var total = 0;
    data.forEach(function(x) {
        var amt = parseFloat(x.amount) || 0;
        total += amt;
        
        html += '<tr>';
        html += '<td class="date-cell">' + fmtDate(x.order_date) + '</td>';
        html += '<td><div class="event-cell">' + truncate(x.event_name || '-', 28) + '</div><div class="support-cell">' + truncate(x.support_for || '-', 25) + '</div></td>';
        if (filter === 'all') {
            html += '<td><span class="cat-badge ' + x.item_type + '">' + catIcon(x.item_type) + ' ' + catLabel(x.item_type) + '</span></td>';
        }
        html += '<td>' + truncate(x.description || '-', 30) + '</td>';
        html += '<td class="text-end amount-cell">' + fmtRp(amt) + '</td>';
        html += '</tr>';
    });
    
    // Total row
    html += '<tr style="background:#fef2f2">';
    html += '<td colspan="' + (filter === 'all' ? '4' : '3') + '" class="text-end fw-bold" style="color:#991b1b;border-top:2px solid #fecaca">TOTAL:</td>';
    html += '<td class="text-end fw-bold" style="color:#dc2626;border-top:2px solid #fecaca;font-size:1rem">' + fmtRp(total) + '</td>';
    html += '</tr>';
    
    html += '</tbody></table>';
    return html;
}

function filterVendorRecap(filter) {
    vrFilter = filter;
    var trx = vrData.transactions || [];
    var cats = vrData.by_category || [];
    
    // Update active card
    document.querySelectorAll('.vr-cat-card').forEach(function(card) {
        card.classList.remove('active');
        if ((filter === 'all' && card.classList.contains('all')) || card.classList.contains(filter)) {
            card.classList.add('active');
        }
    });
    
    // Get filtered data
    var filtered = filter === 'all' ? trx : trx.filter(function(t) { return t.item_type === filter; });
    
    // Calculate total for filter
    var filterTotal = 0;
    filtered.forEach(function(t) { filterTotal += parseFloat(t.amount) || 0; });
    
    // Update grand total display
    document.getElementById('vrGrandTotalValue').textContent = fmtRp(filterTotal);
    
    // Update table title
    var titleText = filter === 'all' ? 'Detail Semua Kategori' : 'Detail ' + catLabel(filter);
    document.getElementById('vrTableTitle').textContent = titleText;
    document.getElementById('vrTableBadge').textContent = filtered.length + ' item';
    
    // Re-render table
    document.getElementById('vrTableWrap').innerHTML = renderVRTable(trx, filter);
}

function printVendorRecap() {
    if (!vrData) {
        alert('Data belum dimuat');
        return;
    }
    
    var s = vrData.summary || {};
    var cats = vrData.by_category || [];
    var trx = vrData.transactions || [];
    
    // Filter data based on current filter
    var filtered = vrFilter === 'all' ? trx : trx.filter(function(t) { return t.item_type === vrFilter; });
    var filterLabel = vrFilter === 'all' ? 'Semua Kategori' : catLabel(vrFilter);
    
    // Calculate total
    var total = 0;
    filtered.forEach(function(t) { total += parseFloat(t.amount) || 0; });
    
    var h = '<!DOCTYPE html><html><head><meta charset="UTF-8"><title>Hutang ke Vendor - ' + filterLabel + '</title>';
    h += '<style>';
    h += '@page{size:A4;margin:15mm}';
    h += 'body{font-family:"Segoe UI",Arial,sans-serif;font-size:10px;color:#333;margin:0;padding:0}';
    h += '.wrap{max-width:180mm;margin:0 auto}';
    h += '.header{text-align:center;padding-bottom:15px;margin-bottom:20px;border-bottom:3px solid #4f46e5}';
    h += '.title{font-size:18px;font-weight:bold;color:#1e293b}';
    h += '.subtitle{font-size:12px;color:#6366f1;margin-top:4px;font-weight:600}';
    h += '.date{font-size:9px;color:#94a3b8;margin-top:8px}';
    h += '.summary{display:flex;justify-content:space-around;margin-bottom:20px;padding:15px;background:#f8fafc;border-radius:8px;border:1px solid #e2e8f0}';
    h += '.summary-item{text-align:center}';
    h += '.summary-value{font-size:14px;font-weight:bold}';
    h += '.summary-value.danger{color:#dc2626}';
    h += '.summary-value.success{color:#16a34a}';
    h += '.summary-label{font-size:9px;color:#64748b;margin-top:2px}';
    h += '.cat-recap{display:flex;flex-wrap:wrap;gap:10px;margin-bottom:20px}';
    h += '.cat-item{flex:1;min-width:80px;padding:10px;background:#f1f5f9;border-radius:8px;text-align:center}';
    h += '.cat-icon{font-size:16px}';
    h += '.cat-name{font-size:9px;color:#64748b;margin:4px 0}';
    h += '.cat-val{font-size:11px;font-weight:bold;color:#dc2626}';
    h += 'table{width:100%;border-collapse:collapse}';
    h += 'th{background:#4f46e5;color:#fff;padding:10px;text-align:left;font-size:9px;font-weight:600}';
    h += 'td{padding:10px;border-bottom:1px solid #e2e8f0;font-size:9px}';
    h += '.text-end{text-align:right}';
    h += '.amount{font-weight:600;color:#dc2626}';
    h += '.total-row{background:#fef2f2}';
    h += '.total-row td{border-top:2px solid #dc2626;font-weight:bold;font-size:11px}';
    h += '.footer{margin-top:20px;padding-top:10px;border-top:1px solid #e2e8f0;font-size:8px;color:#94a3b8;text-align:center}';
    h += '</style></head><body><div class="wrap">';
    
    // Header
    h += '<div class="header">';
    h += '<div class="title">DAFTAR HUTANG KE VENDOR</div>';
    h += '<div class="subtitle">' + filterLabel + '</div>';
    h += '<div class="date">Dicetak: ' + new Date().toLocaleDateString('id-ID', {day:'2-digit',month:'long',year:'numeric',hour:'2-digit',minute:'2-digit'}) + '</div>';
    h += '</div>';
    
    // Summary
    h += '<div class="summary">';
    h += '<div class="summary-item"><div class="summary-value danger">' + fmtRp(total) + '</div><div class="summary-label">Total Hutang (' + filterLabel + ')</div></div>';
    h += '<div class="summary-item"><div class="summary-value success">' + fmtRp(s.total_paid || 0) + '</div><div class="summary-label">Sudah Dibayar</div></div>';
    h += '<div class="summary-item"><div class="summary-value">' + fmtRp(s.total_modal || 0) + '</div><div class="summary-label">Total Modal</div></div>';
    h += '</div>';
    
    // Category Recap (only if printing all)
    if (vrFilter === 'all') {
        h += '<div class="cat-recap">';
        cats.forEach(function(c) {
            if ((c.pending || 0) > 0) {
                h += '<div class="cat-item">';
                h += '<div class="cat-icon">' + catIcon(c.category) + '</div>';
                h += '<div class="cat-name">' + catLabel(c.category) + '</div>';
                h += '<div class="cat-val">' + fmtRp(c.pending) + '</div>';
                h += '</div>';
            }
        });
        h += '</div>';
    }
    
    // Table
    h += '<table><thead><tr>';
    h += '<th width="70">Tanggal</th>';
    h += '<th>Event</th>';
    h += '<th>Dukungan</th>';
    if (vrFilter === 'all') h += '<th width="70">Kategori</th>';
    h += '<th>Deskripsi</th>';
    h += '<th class="text-end" width="100">Jumlah</th>';
    h += '</tr></thead><tbody>';
    
    filtered.forEach(function(x) {
        h += '<tr>';
        h += '<td>' + fmtDate(x.order_date) + '</td>';
        h += '<td>' + (x.event_name || '-') + '</td>';
        h += '<td>' + (x.support_for || '-') + '</td>';
        if (vrFilter === 'all') h += '<td>' + catIcon(x.item_type) + ' ' + catLabel(x.item_type) + '</td>';
        h += '<td>' + (x.description || '-') + '</td>';
        h += '<td class="text-end amount">' + fmtRp(x.amount) + '</td>';
        h += '</tr>';
    });
    
    h += '<tr class="total-row">';
    h += '<td colspan="' + (vrFilter === 'all' ? '5' : '4') + '" class="text-end">TOTAL HUTANG:</td>';
    h += '<td class="text-end amount">' + fmtRp(total) + '</td>';
    h += '</tr>';
    
    h += '</tbody></table>';
    h += '<div class="footer">Dokumen ini digenerate otomatis oleh sistem • PIM Travel Management System</div>';
    h += '</div></body></html>';
    
    var w = window.open('', '_blank', 'width=800,height=600');
    w.document.write(h);
    w.document.close();
    w.focus();
    setTimeout(function() { w.print(); }, 500);
}

// Helper functions
function catIcon(cat) {
    var icons = { flight: '✈️', hotel: '🏨', rental: '🚗', bus: '🚌', towing: '🚛', restaurant: '🍽️', other: '📦' };
    return icons[cat] || '📦';
}

function catLabel(cat) {
    var labels = { flight: 'Pesawat', hotel: 'Hotel', rental: 'Kendaraan', bus: 'Bus', towing: 'Towing', restaurant: 'Restaurant', other: 'Lainnya' };
    return labels[cat] || (cat ? cat.charAt(0).toUpperCase() + cat.slice(1) : 'Lainnya');
}

function fmtRp(val) {
    val = parseFloat(val) || 0;
    if (val >= 1e9) return 'Rp ' + (val/1e9).toFixed(1).replace('.', ',') + 'M';
    if (val >= 1e6) return 'Rp ' + (val/1e6).toFixed(1).replace('.', ',') + 'jt';
    return 'Rp ' + val.toLocaleString('id-ID');
}

function fmtDate(dateStr) {
    if (!dateStr) return '-';
    var d = new Date(dateStr);
    return d.toLocaleDateString('id-ID', {day: '2-digit', month: 'short', year: 'numeric'});
}

function truncate(text, len) {
    if (!text) return '-';
    return text.length > len ? text.substr(0, len) + '...' : text;
}

function formatRupiahJS(val) {
    val = parseFloat(val) || 0;
    if (val >= 1e9) return 'Rp ' + (val/1e9).toFixed(1).replace('.', ',') + 'M';
    if (val >= 1e6) return 'Rp ' + (val/1e6).toFixed(1).replace('.', ',') + 'jt';
    return 'Rp ' + val.toLocaleString('id-ID');
}

// ================================================================
// BULK ACTIONS & SELECT ALL
// ================================================================
document.querySelectorAll('.group-select-all').forEach(function(cb) {
    cb.addEventListener('change', function() {
        var group = this.dataset.group;
        var checked = this.checked;
        document.querySelectorAll('tr[data-group="'+group+'"] .order-checkbox').forEach(function(c) {
            c.checked = checked;
        });
    });
});

function selectAllOrders() {
    var cbs = document.querySelectorAll('.order-checkbox');
    var all = Array.from(cbs).every(function(c) { return c.checked; });
    cbs.forEach(function(c) { c.checked = !all; });
    document.querySelectorAll('.group-select-all').forEach(function(c) { c.checked = !all; });
}

function applyBulkMarkup() {
    var pct = parseFloat(document.getElementById('bulkMarkupPercent').value);
    if (isNaN(pct) || pct < 0 || pct > 100) {
        showToast('Masukkan % (0-100)', 'warning');
        return;
    }
    
    var ids = [];
    document.querySelectorAll('.order-checkbox:checked').forEach(function(c) {
        ids.push(c.value);
    });
    
    if (ids.length === 0) {
        showToast('Pilih order dulu', 'warning');
        return;
    }
    
    if (!confirm('Terapkan markup ' + pct + '% ke ' + ids.length + ' order?')) return;
    
    var formData = new FormData();
    var csrfToken = document.getElementById('csrf_token');
    if (csrfToken) formData.append('_token', csrfToken.value);
    formData.append('markup_percent', pct);
    formData.append('order_ids_json', JSON.stringify(ids));
    
    var xhr = new XMLHttpRequest();
    xhr.open('POST', baseUrl + '/analysis/bulk-update-markup', true);
    xhr.onreadystatechange = function() {
        if (xhr.readyState === 4) {
            try {
                var data = JSON.parse(xhr.responseText);
                if (data.success) {
                    showToast(data.message, 'success');
                    setTimeout(function() { location.reload(); }, 1000);
                } else {
                    showToast(data.message || 'Error', 'error');
                }
            } catch (e) {
                showToast('Error: ' + xhr.status, 'error');
            }
        }
    };
    xhr.send(formData);
}

// ================================================================
// TOAST NOTIFICATION
// ================================================================
function showToast(msg, type) {
    type = type || 'info';
    var t = document.createElement('div');
    t.className = 'toast-notification toast-' + type;
    var icon = type === 'success' ? 'check-circle' : type === 'error' ? 'times-circle' : 'info-circle';
    t.innerHTML = '<i class="fas fa-' + icon + '"></i><span>' + msg + '</span>';
    document.body.appendChild(t);
    setTimeout(function() { t.classList.add('show'); }, 10);
    setTimeout(function() { 
        t.classList.remove('show');
        setTimeout(function() { t.remove(); }, 300);
    }, 3000);
}

// ================================================================
// CHARTS
// ================================================================
document.addEventListener('DOMContentLoaded', function() {
    // Monthly Chart
    fetch(baseUrl + '/analysis/chart-data?type=monthly')
        .then(function(r) { return r.json(); })
        .then(function(res) {
            if (res.success && res.data && res.data.length > 0) {
                new Chart(document.getElementById('monthlyChart'), {
                    type: 'bar',
                    data: {
                        labels: res.data.map(function(d) { return d.label; }),
                        datasets: [
                            { label: 'Modal', data: res.data.map(function(d) { return d.modal; }), backgroundColor: '#fbbf24', borderRadius: 4 },
                            { label: 'Tagihan', data: res.data.map(function(d) { return d.tagihan; }), backgroundColor: '#c89b2c', borderRadius: 4 },
                            { label: 'Profit', data: res.data.map(function(d) { return d.profit; }), backgroundColor: '#22c55e', borderRadius: 4 }
                        ]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: {
                                position: 'bottom',
                                labels: { boxWidth: 12, font: { size: 10 } }
                            }
                        },
                        scales: {
                            y: {
                                ticks: {
                                    callback: function(v) {
                                        return v >= 1e9 ? (v/1e9).toFixed(0) + 'M' : 
                                               v >= 1e6 ? (v/1e6).toFixed(0) + 'jt' : 
                                               (v/1e3).toFixed(0) + 'rb';
                                    }
                                }
                            }
                        }
                    }
                });
            }
        })
        .catch(function() {});
    
    // Support Chart
    fetch(baseUrl + '/analysis/chart-data?type=by_support')
        .then(function(r) { return r.json(); })
        .then(function(res) {
            if (res.success && res.data && res.data.length > 0) {
                var colors = ['#c89b2c', '#22c55e', '#f59e0b', '#ef4444', '#8b5cf6', '#06b6d4', '#ec4899', '#f97316', '#6366f1', '#84cc16'];
                new Chart(document.getElementById('supportChart'), {
                    type: 'doughnut',
                    data: {
                        labels: res.data.map(function(d) {
                            return d.label && d.label.length > 12 ? d.label.substr(0, 12) + '...' : d.label;
                        }),
                        datasets: [{
                            data: res.data.map(function(d) { return d.tagihan; }),
                            backgroundColor: colors.slice(0, res.data.length),
                            borderWidth: 0
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        cutout: '55%',
                        plugins: {
                            legend: {
                                position: 'bottom',
                                labels: { boxWidth: 10, font: { size: 9 } }
                            }
                        }
                    }
                });
            }
        })
        .catch(function() {});
});
</script>