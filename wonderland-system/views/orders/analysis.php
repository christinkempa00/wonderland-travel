<?php
/**
 * Order Financial Analysis View
 * Halaman Analisa Keuangan Order
 * 
 * Variables: $order, $client, $items, $analysis, $itemsByType
 */
?>

<div class="page-actions mb-4">
    <a href="<?= url('/orders/' . $order->id) ?>" class="btn btn-secondary">
        <i class="fas fa-arrow-left"></i> Kembali ke Detail
    </a>
    <a href="<?= url('/orders/' . $order->id . '/edit') ?>" class="btn btn-warning">
        <i class="fas fa-edit"></i> Edit Pesanan
    </a>
    <button type="button" class="btn btn-primary" onclick="window.print()">
        <i class="fas fa-print"></i> Cetak Analisa
    </button>
</div>

<!-- Summary Cards -->
<div class="row mb-4">
    <!-- Total Modal -->
    <div class="col-6 col-md-3 mb-3">
        <div class="analysis-card modal-card">
            <div class="analysis-icon">
                <i class="fas fa-coins"></i>
            </div>
            <div class="analysis-content">
                <span class="analysis-label">Total Modal</span>
                <span class="analysis-value"><?= formatRupiah($analysis['total_modal']) ?></span>
                <span class="analysis-sub">Biaya yang dikeluarkan</span>
            </div>
        </div>
    </div>
    
    <!-- Total Markup -->
    <div class="col-6 col-md-3 mb-3">
        <div class="analysis-card markup-card">
            <div class="analysis-icon">
                <i class="fas fa-chart-line"></i>
            </div>
            <div class="analysis-content">
                <span class="analysis-label">Total Markup</span>
                <span class="analysis-value"><?= formatRupiah($analysis['total_markup']) ?></span>
                <span class="analysis-sub text-success">
                    <i class="fas fa-arrow-up"></i> <?= number_format($analysis['markup_percent'], 1) ?>% dari modal
                </span>
            </div>
        </div>
    </div>
    
    <!-- Total Tagihan -->
    <div class="col-6 col-md-3 mb-3">
        <div class="analysis-card tagihan-card">
            <div class="analysis-icon">
                <i class="fas fa-file-invoice-dollar"></i>
            </div>
            <div class="analysis-content">
                <span class="analysis-label">Total Tagihan</span>
                <span class="analysis-value"><?= formatRupiah($analysis['total_tagihan']) ?></span>
                <span class="analysis-sub">Yang ditagihkan ke klien</span>
            </div>
        </div>
    </div>
    
    <!-- Profit Margin -->
    <div class="col-6 col-md-3 mb-3">
        <div class="analysis-card profit-card">
            <div class="analysis-icon">
                <i class="fas fa-percentage"></i>
            </div>
            <div class="analysis-content">
                <span class="analysis-label">Profit Margin</span>
                <span class="analysis-value"><?= number_format($analysis['profit_margin'], 1) ?>%</span>
                <span class="analysis-sub">Keuntungan dari tagihan</span>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <!-- Left Column: Payment & Charts -->
    <div class="col-12 col-lg-8">
        
        <!-- Payment Status Card -->
        <div class="glass-card mb-4">
            <h4 class="mb-4">
                <i class="fas fa-money-check-alt text-success"></i> Status Pembayaran
            </h4>
            
            <div class="payment-summary">
                <div class="payment-row">
                    <div class="payment-item">
                        <span class="payment-label">Total Tagihan</span>
                        <span class="payment-value"><?= formatRupiah($analysis['total_tagihan']) ?></span>
                    </div>
                    <div class="payment-item paid">
                        <span class="payment-label">Sudah Dibayar</span>
                        <span class="payment-value text-success"><?= formatRupiah($analysis['total_dibayar']) ?></span>
                    </div>
                    <div class="payment-item remaining">
                        <span class="payment-label">Sisa Tagihan</span>
                        <span class="payment-value <?= $analysis['sisa_tagihan'] > 0 ? 'text-danger' : 'text-success' ?>">
                            <?= formatRupiah($analysis['sisa_tagihan']) ?>
                        </span>
                    </div>
                </div>
                
                <!-- Progress Bar -->
                <div class="payment-progress-container">
                    <div class="progress-header">
                        <span>Progress Pembayaran</span>
                        <span class="progress-percent"><?= number_format($analysis['payment_progress'], 1) ?>%</span>
                    </div>
                    <div class="progress payment-progress">
                        <div class="progress-bar 
                            <?php if ($analysis['payment_progress'] >= 100): ?>bg-success
                            <?php elseif ($analysis['payment_progress'] >= 50): ?>bg-info
                            <?php elseif ($analysis['payment_progress'] > 0): ?>bg-warning
                            <?php else: ?>bg-danger<?php endif; ?>" 
                            role="progressbar" 
                            style="width: <?= min(100, $analysis['payment_progress']) ?>%">
                        </div>
                    </div>
                    <div class="progress-labels">
                        <span>Rp 0</span>
                        <span><?= formatRupiah($analysis['total_tagihan']) ?></span>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Charts Row -->
        <div class="row">
            <!-- Modal vs Markup Chart -->
            <div class="col-md-6 mb-4">
                <div class="glass-card h-100">
                    <h5 class="mb-3"><i class="fas fa-chart-pie text-primary"></i> Komposisi Harga</h5>
                    <div class="chart-container">
                        <canvas id="compositionChart"></canvas>
                    </div>
                </div>
            </div>
            
            <!-- Items by Type Chart -->
            <div class="col-md-6 mb-4">
                <div class="glass-card h-100">
                    <h5 class="mb-3"><i class="fas fa-chart-bar text-primary"></i> Modal per Kategori</h5>
                    <div class="chart-container">
                        <canvas id="categoryChart"></canvas>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Items Detail Table -->
        <div class="glass-card mb-4">
            <h4 class="mb-4">
                <i class="fas fa-list-alt text-primary"></i> Detail Item
            </h4>
            
            <div class="table-responsive">
                <table class="table table-hover items-analysis-table">
                    <thead>
                        <tr>
                            <th>Item</th>
                            <th class="text-center">Qty</th>
                            <th class="text-end">Modal</th>
                            <th class="text-end">Markup</th>
                            <th class="text-center">%</th>
                            <th class="text-end">Harga Jual</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($analysis['items'] as $item): ?>
                        <tr>
                            <td>
                                <div class="item-info-cell">
                                    <span class="badge badge-sm badge-<?= getItemTypeColor($item['type']) ?>">
                                        <?= e(ITEM_TYPES[$item['type']]['label'] ?? $item['type']) ?>
                                    </span>
                                    <span class="item-desc"><?= e($item['description']) ?></span>
                                </div>
                            </td>
                            <td class="text-center">
                                <?= $item['quantity'] ?>
                                <?php if ($item['days'] > 1): ?>
                                <small class="text-muted">×<?= $item['days'] ?>h</small>
                                <?php endif; ?>
                            </td>
                            <td class="text-end"><?= formatRupiah($item['modal']) ?></td>
                            <td class="text-end text-success">+<?= formatRupiah($item['markup_amount']) ?></td>
                            <td class="text-center">
                                <span class="markup-badge"><?= number_format($item['markup_percent'], 1) ?>%</span>
                            </td>
                            <td class="text-end fw-bold"><?= formatRupiah($item['final_price']) ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                    <tfoot>
                        <tr class="table-total">
                            <td colspan="2"><strong>TOTAL</strong></td>
                            <td class="text-end"><strong><?= formatRupiah($analysis['total_modal']) ?></strong></td>
                            <td class="text-end text-success"><strong>+<?= formatRupiah($analysis['total_markup']) ?></strong></td>
                            <td class="text-center">
                                <span class="markup-badge total"><?= number_format($analysis['markup_percent'], 1) ?>%</span>
                            </td>
                            <td class="text-end"><strong><?= formatRupiah($analysis['total_tagihan']) ?></strong></td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
        
    </div>
    
    <!-- Right Column: Order Info & Payment History -->
    <div class="col-12 col-lg-4">
        
        <!-- Order Info Card -->
        <div class="glass-card mb-4">
            <h5 class="mb-3"><i class="fas fa-info-circle text-primary"></i> Info Pesanan</h5>
            
            <div class="order-info-list">
                <div class="info-row">
                    <span class="info-label">No. Pesanan</span>
                    <span class="info-value"><?= e($order->order_number) ?></span>
                </div>
                <div class="info-row">
                    <span class="info-label">Tanggal</span>
                    <span class="info-value"><?= formatDate($order->order_date) ?></span>
                </div>
                <div class="info-row">
                    <span class="info-label">Klien</span>
                    <span class="info-value">
                        <?php if ($client): ?>
                        <a href="<?= url('/clients/' . $client['id']) ?>"><?= e($client['name']) ?></a>
                        <?php else: ?>
                        <span class="text-muted">-</span>
                        <?php endif; ?>
                    </span>
                </div>
                <?php if ($order->event_name): ?>
                <div class="info-row">
                    <span class="info-label">Event</span>
                    <span class="info-value"><?= e($order->event_name) ?></span>
                </div>
                <?php endif; ?>
                <?php if ($order->support_for): ?>
                <div class="info-row">
                    <span class="info-label">Dukungan</span>
                    <span class="info-value"><?= e($order->support_for) ?></span>
                </div>
                <?php endif; ?>
                <div class="info-row">
                    <span class="info-label">Status</span>
                    <span class="info-value">
                        <?php $status = ORDER_STATUSES[$order->status] ?? null; ?>
                        <span class="badge badge-<?= $status['color'] ?? 'secondary' ?>">
                            <?= $status['label'] ?? $order->status ?>
                        </span>
                    </span>
                </div>
                <div class="info-row">
                    <span class="info-label">Pembayaran</span>
                    <span class="info-value">
                        <?php $paymentStatus = PAYMENT_STATUSES[$order->payment_status] ?? null; ?>
                        <span class="badge badge-<?= $paymentStatus['color'] ?? 'secondary' ?>">
                            <?= $paymentStatus['label'] ?? $order->payment_status ?>
                        </span>
                    </span>
                </div>
            </div>
        </div>
        
        <!-- Profit Summary -->
        <div class="glass-card mb-4 profit-summary-card">
            <h5 class="mb-3"><i class="fas fa-hand-holding-usd text-success"></i> Ringkasan Profit</h5>
            
            <div class="profit-visual">
                <div class="profit-chart-mini">
                    <canvas id="profitMiniChart"></canvas>
                </div>
                <div class="profit-details">
                    <div class="profit-detail-row">
                        <span class="dot modal-dot"></span>
                        <span>Modal: <?= formatRupiah($analysis['total_modal']) ?></span>
                    </div>
                    <div class="profit-detail-row">
                        <span class="dot profit-dot"></span>
                        <span>Profit: <?= formatRupiah($analysis['total_markup']) ?></span>
                    </div>
                </div>
            </div>
            
            <div class="profit-highlight">
                <span class="highlight-label">Estimasi Keuntungan</span>
                <span class="highlight-value"><?= formatRupiah($analysis['total_markup']) ?></span>
                <span class="highlight-percent">(<?= number_format($analysis['markup_percent'], 1) ?>% dari modal)</span>
            </div>
        </div>
        
        <!-- Payment History -->
        <div class="glass-card">
            <h5 class="mb-3"><i class="fas fa-history text-primary"></i> Riwayat Pembayaran</h5>
            
            <?php if (empty($analysis['payments'])): ?>
            <div class="empty-state-mini">
                <i class="fas fa-receipt"></i>
                <p>Belum ada pembayaran tercatat</p>
            </div>
            <?php else: ?>
            <div class="payment-history-list">
                <?php foreach ($analysis['payments'] as $payment): ?>
                <div class="payment-history-item">
                    <div class="payment-history-date">
                        <i class="fas fa-calendar"></i>
                        <?= formatDate($payment['payment_date']) ?>
                    </div>
                    <div class="payment-history-amount">
                        <?= formatRupiah($payment['amount']) ?>
                    </div>
                    <?php if ($payment['payment_method']): ?>
                    <div class="payment-history-method">
                        <span class="badge badge-outline"><?= e(ucfirst($payment['payment_method'])) ?></span>
                    </div>
                    <?php endif; ?>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </div>
        
    </div>
</div>

<style>
/* ========================================
   ANALYSIS CARDS
   ======================================== */
.analysis-card {
    background: white;
    border-radius: 16px;
    padding: 1.25rem;
    display: flex;
    align-items: center;
    gap: 1rem;
    box-shadow: 0 4px 15px rgba(0,0,0,0.05);
    border: 1px solid #e5e7eb;
    transition: all 0.3s;
    height: 100%;
}

.analysis-card:hover {
    transform: translateY(-3px);
    box-shadow: 0 8px 25px rgba(0,0,0,0.1);
}

.analysis-icon {
    width: 50px;
    height: 50px;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.3rem;
    flex-shrink: 0;
}

.modal-card .analysis-icon { background: linear-gradient(135deg, #fef3c7, #fde68a); color: #b45309; }
.markup-card .analysis-icon { background: linear-gradient(135deg, #d1fae5, #a7f3d0); color: #059669; }
.tagihan-card .analysis-icon { background: linear-gradient(135deg, #dbeafe, #bfdbfe); color: #2563eb; }
.profit-card .analysis-icon { background: linear-gradient(135deg, #ede9fe, #ddd6fe); color: #7c3aed; }

.analysis-content {
    display: flex;
    flex-direction: column;
    min-width: 0;
}

.analysis-label {
    font-size: 0.75rem;
    color: #6b7280;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    font-weight: 600;
}

.analysis-value {
    font-size: 1.1rem;
    font-weight: 700;
    color: #1f2937;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.analysis-sub {
    font-size: 0.7rem;
    color: #9ca3af;
    margin-top: 2px;
}

.analysis-sub.text-success {
    color: #059669 !important;
}

/* ========================================
   PAYMENT SUMMARY
   ======================================== */
.payment-summary {
    background: linear-gradient(135deg, #f9fafb, #f3f4f6);
    border-radius: 12px;
    padding: 1.5rem;
}

.payment-row {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 1rem;
    margin-bottom: 1.5rem;
}

.payment-item {
    text-align: center;
    padding: 1rem;
    background: white;
    border-radius: 10px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.05);
}

.payment-label {
    display: block;
    font-size: 0.75rem;
    color: #6b7280;
    margin-bottom: 0.5rem;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.payment-value {
    display: block;
    font-size: 1.1rem;
    font-weight: 700;
    color: #1f2937;
}

.payment-progress-container {
    margin-top: 1rem;
}

.progress-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 0.5rem;
    font-size: 0.85rem;
    color: #374151;
}

.progress-percent {
    font-weight: 700;
    color: #2563eb;
}

.payment-progress {
    height: 12px;
    border-radius: 6px;
    background: #e5e7eb;
    overflow: hidden;
}

.payment-progress .progress-bar {
    border-radius: 6px;
    transition: width 0.5s ease;
}

.progress-labels {
    display: flex;
    justify-content: space-between;
    font-size: 0.7rem;
    color: #9ca3af;
    margin-top: 0.5rem;
}

/* ========================================
   CHARTS
   ======================================== */
.chart-container {
    position: relative;
    height: 220px;
}

/* ========================================
   ITEMS TABLE
   ======================================== */
.items-analysis-table {
    font-size: 0.85rem;
}

.items-analysis-table th {
    background: #f9fafb;
    font-weight: 600;
    color: #374151;
    padding: 0.75rem;
    border-bottom: 2px solid #e5e7eb;
}

.items-analysis-table td {
    padding: 0.75rem;
    vertical-align: middle;
}

.item-info-cell {
    display: flex;
    flex-direction: column;
    gap: 0.25rem;
}

.item-desc {
    font-size: 0.8rem;
    color: #374151;
}

.badge-sm {
    font-size: 0.65rem;
    padding: 0.2rem 0.5rem;
}

.markup-badge {
    display: inline-block;
    background: #dcfce7;
    color: #166534;
    padding: 0.2rem 0.5rem;
    border-radius: 20px;
    font-size: 0.75rem;
    font-weight: 600;
}

.markup-badge.total {
    background: #22c55e;
    color: white;
}

.table-total {
    background: linear-gradient(135deg, #f0fdf4, #dcfce7);
}

.table-total td {
    border-top: 2px solid #22c55e;
}

/* ========================================
   ORDER INFO LIST
   ======================================== */
.order-info-list {
    display: flex;
    flex-direction: column;
    gap: 0.75rem;
}

.info-row {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding-bottom: 0.75rem;
    border-bottom: 1px dashed #e5e7eb;
}

.info-row:last-child {
    border-bottom: none;
    padding-bottom: 0;
}

.info-label {
    font-size: 0.8rem;
    color: #6b7280;
}

.info-value {
    font-size: 0.85rem;
    font-weight: 600;
    color: #1f2937;
}

/* ========================================
   PROFIT SUMMARY CARD
   ======================================== */
.profit-summary-card {
    background: linear-gradient(135deg, #f0fdf4, #dcfce7);
    border-color: #bbf7d0;
}

.profit-visual {
    display: flex;
    align-items: center;
    gap: 1rem;
    margin-bottom: 1rem;
}

.profit-chart-mini {
    width: 80px;
    height: 80px;
}

.profit-details {
    flex: 1;
}

.profit-detail-row {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    font-size: 0.8rem;
    color: #374151;
    margin-bottom: 0.5rem;
}

.dot {
    width: 10px;
    height: 10px;
    border-radius: 50%;
}

.modal-dot { background: #fbbf24; }
.profit-dot { background: #22c55e; }

.profit-highlight {
    background: white;
    border-radius: 10px;
    padding: 1rem;
    text-align: center;
    border: 2px solid #22c55e;
}

.highlight-label {
    display: block;
    font-size: 0.75rem;
    color: #6b7280;
    margin-bottom: 0.25rem;
}

.highlight-value {
    display: block;
    font-size: 1.25rem;
    font-weight: 700;
    color: #166534;
}

.highlight-percent {
    display: block;
    font-size: 0.75rem;
    color: #22c55e;
    margin-top: 0.25rem;
}

/* ========================================
   PAYMENT HISTORY
   ======================================== */
.empty-state-mini {
    text-align: center;
    padding: 1.5rem;
    color: #9ca3af;
}

.empty-state-mini i {
    font-size: 2rem;
    margin-bottom: 0.5rem;
    opacity: 0.5;
}

.empty-state-mini p {
    margin: 0;
    font-size: 0.85rem;
}

.payment-history-list {
    display: flex;
    flex-direction: column;
    gap: 0.75rem;
}

.payment-history-item {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    justify-content: space-between;
    padding: 0.75rem;
    background: #f9fafb;
    border-radius: 8px;
    border: 1px solid #e5e7eb;
}

.payment-history-date {
    font-size: 0.8rem;
    color: #6b7280;
}

.payment-history-date i {
    margin-right: 0.25rem;
}

.payment-history-amount {
    font-weight: 700;
    color: #059669;
}

.payment-history-method {
    width: 100%;
    margin-top: 0.5rem;
}

.badge-outline {
    background: transparent;
    border: 1px solid #d1d5db;
    color: #6b7280;
}

/* ========================================
   ITEM TYPE COLORS
   ======================================== */
.badge-hotel { background: #8b5cf6; color: white; }
.badge-flight { background: #3b82f6; color: white; }
.badge-bus { background: #f59e0b; color: white; }
.badge-rental { background: #10b981; color: white; }
.badge-towing { background: #ef4444; color: white; }
.badge-other { background: #6b7280; color: white; }

/* ========================================
   PRINT STYLES
   ======================================== */
@media print {
    .page-actions { display: none; }
    .glass-card { break-inside: avoid; }
    .analysis-card { box-shadow: none; border: 1px solid #ccc; }
}

/* ========================================
   RESPONSIVE
   ======================================== */
@media (max-width: 767.98px) {
    .payment-row {
        grid-template-columns: 1fr;
    }
    
    .analysis-value {
        font-size: 0.95rem;
    }
    
    .profit-visual {
        flex-direction: column;
        text-align: center;
    }
}
</style>

<!-- Chart.js CDN -->
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Data from PHP
    const totalModal = <?= $analysis['total_modal'] ?>;
    const totalMarkup = <?= $analysis['total_markup'] ?>;
    const totalDibayar = <?= $analysis['total_dibayar'] ?>;
    const sisaTagihan = <?= $analysis['sisa_tagihan'] ?>;
    
    // Items by type data
    const itemsByType = <?= json_encode($itemsByType) ?>;
    
    // Format rupiah helper
    function formatRupiah(value) {
        return 'Rp ' + new Intl.NumberFormat('id-ID').format(value);
    }
    
    // 1. Composition Chart (Donut)
    const compositionCtx = document.getElementById('compositionChart').getContext('2d');
    new Chart(compositionCtx, {
        type: 'doughnut',
        data: {
            labels: ['Modal', 'Markup/Profit'],
            datasets: [{
                data: [totalModal, totalMarkup],
                backgroundColor: ['#fbbf24', '#22c55e'],
                borderWidth: 0,
                hoverOffset: 10
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            cutout: '60%',
            plugins: {
                legend: {
                    position: 'bottom',
                    labels: {
                        padding: 15,
                        usePointStyle: true,
                        font: { size: 11 }
                    }
                },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            const total = totalModal + totalMarkup;
                            const percent = ((context.raw / total) * 100).toFixed(1);
                            return context.label + ': ' + formatRupiah(context.raw) + ' (' + percent + '%)';
                        }
                    }
                }
            }
        }
    });
    
    // 2. Category Chart (Bar)
    const categoryLabels = [];
    const categoryModalData = [];
    const categoryMarkupData = [];
    
    const typeLabels = {
        'hotel': 'Hotel',
        'flight': 'Pesawat',
        'bus': 'Bus',
        'rental': 'Rental',
        'towing': 'Towing',
        'other': 'Lainnya'
    };
    
    for (const [type, data] of Object.entries(itemsByType)) {
        categoryLabels.push(typeLabels[type] || type);
        categoryModalData.push(data.modal);
        categoryMarkupData.push(data.markup);
    }
    
    const categoryCtx = document.getElementById('categoryChart').getContext('2d');
    new Chart(categoryCtx, {
        type: 'bar',
        data: {
            labels: categoryLabels,
            datasets: [
                {
                    label: 'Modal',
                    data: categoryModalData,
                    backgroundColor: '#fbbf24',
                    borderRadius: 4
                },
                {
                    label: 'Markup',
                    data: categoryMarkupData,
                    backgroundColor: '#22c55e',
                    borderRadius: 4
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                x: {
                    stacked: true,
                    grid: { display: false }
                },
                y: {
                    stacked: true,
                    ticks: {
                        callback: function(value) {
                            if (value >= 1000000) {
                                return (value / 1000000).toFixed(0) + 'jt';
                            }
                            return (value / 1000).toFixed(0) + 'rb';
                        }
                    }
                }
            },
            plugins: {
                legend: {
                    position: 'bottom',
                    labels: {
                        padding: 15,
                        usePointStyle: true,
                        font: { size: 11 }
                    }
                },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            return context.dataset.label + ': ' + formatRupiah(context.raw);
                        }
                    }
                }
            }
        }
    });
    
    // 3. Mini Profit Chart (Doughnut)
    const profitMiniCtx = document.getElementById('profitMiniChart').getContext('2d');
    new Chart(profitMiniCtx, {
        type: 'doughnut',
        data: {
            labels: ['Modal', 'Profit'],
            datasets: [{
                data: [totalModal, totalMarkup],
                backgroundColor: ['#fbbf24', '#22c55e'],
                borderWidth: 0
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            cutout: '70%',
            plugins: {
                legend: { display: false },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            return context.label + ': ' + formatRupiah(context.raw);
                        }
                    }
                }
            }
        }
    });
});
</script>

<?php
// Helper function for item type colors
function getItemTypeColor($type) {
    $colors = [
        'hotel' => 'hotel',
        'flight' => 'flight',
        'bus' => 'bus',
        'rental' => 'rental',
        'towing' => 'towing'
    ];
    return $colors[$type] ?? 'other';
}
?>
