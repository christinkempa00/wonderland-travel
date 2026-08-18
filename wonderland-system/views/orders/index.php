<?php
/**
 * Orders List View - FIXED v6
 * - FIXED: Calculate totals from items if stored value is 0
 * - FIXED: Display event date range (event_date s/d event_end_date)
 * - FIXED: Fallback num_days from order event duration
 * - Added: Filter by support_for (dukungan)
 * - Added: Quick payment status update
 * - Added: Show paid_at date
 */

// Helper function to calculate order total from items with event duration fallback
function calculateOrderTotalWithDuration($orderId, $eventDate, $eventEndDate) {
    $items = db()->fetchAll("SELECT * FROM order_items WHERE order_id = ?", [$orderId]);
    $total = 0;
    
    // Calculate order event duration as fallback
    $orderEventDays = 1;
    if (!empty($eventDate) && !empty($eventEndDate) && $eventEndDate != $eventDate) {
        $start = new DateTime($eventDate);
        $end = new DateTime($eventEndDate);
        $orderEventDays = $start->diff($end)->days + 1;
    }
    
    foreach ($items as $item) {
        $qty = (int)($item['quantity'] ?? 1);
        
        // Use order event duration as fallback if item num_days is 1 or 0
        $days = (int)($item['num_days'] ?? 1);
        if ($days <= 1 && $orderEventDays > 1) {
            $days = $orderEventDays;
        }
        
        $basePrice = (float)($item['base_price'] ?? 0);
        $markupType = $item['markup_type'] ?? 'percentage';
        $markupValue = (float)($item['markup_value'] ?? 0);
        
        $itemBase = $qty * $days * $basePrice;
        if ($markupType === 'percentage') {
            $itemMarkup = $itemBase * ($markupValue / 100);
        } else {
            $itemMarkup = $markupValue * $qty * $days;
        }
        
        $total += $itemBase + $itemMarkup;
    }
    
    return $total;
}

// Pre-calculate totals for orders with 0 value
$orderTotals = [];
foreach ($orders as $order) {
    if ($order['total_final_price'] == 0) {
        $orderTotals[$order['id']] = calculateOrderTotalWithDuration(
            $order['id'], 
            $order['event_date'] ?? null, 
            $order['event_end_date'] ?? null
        );
    }
}
?>

<!-- Filters -->
<div class="glass-card mb-4">
    <form method="GET" action="<?= url('/orders') ?>" class="d-flex gap-3 flex-wrap">
        <div class="flex-1" style="min-width: 200px;">
            <input type="text" name="search" class="form-control" 
                   placeholder="Cari nomor order atau nama event..."
                   value="<?= e($filters['search'] ?? '') ?>">
        </div>
        
        <div style="min-width: 150px;">
            <select name="status" class="form-control">
                <option value="">Semua Status</option>
                <?php foreach ($statuses as $key => $status): ?>
                <option value="<?= $key ?>" <?= ($filters['status'] ?? '') === $key ? 'selected' : '' ?>>
                    <?= $status['label'] ?>
                </option>
                <?php endforeach; ?>
            </select>
        </div>
        
        <div style="min-width: 150px;">
            <select name="payment_status" class="form-control">
                <option value="">Semua Pembayaran</option>
                <?php foreach ($paymentStatuses as $key => $status): ?>
                <option value="<?= $key ?>" <?= ($filters['payment_status'] ?? '') === $key ? 'selected' : '' ?>>
                    <?= $status['label'] ?>
                </option>
                <?php endforeach; ?>
            </select>
        </div>
        
        <button type="submit" class="btn btn-primary">
            <i class="fas fa-filter"></i> Filter
        </button>
        
        <?php if (($filters['search'] ?? '') || ($filters['status'] ?? '') || ($filters['payment_status'] ?? '')): ?>
        <a href="<?= url('/orders') ?>" class="btn btn-secondary">
            <i class="fas fa-times"></i> Reset
        </a>
        <?php endif; ?>
    </form>
</div>

<!-- Orders List -->
<div class="glass-card">
    <?php if (empty($orders)): ?>
    <div class="empty-state">
        <div class="empty-state-icon">
            <i class="fas fa-file-alt"></i>
        </div>
        <h4 class="empty-state-title">
            <?= (($filters['search'] ?? '') || ($filters['status'] ?? '')) ? 'Tidak Ada Hasil' : 'Belum Ada Pesanan' ?>
        </h4>
        <p class="empty-state-text">
            <?= (($filters['search'] ?? '') || ($filters['status'] ?? '')) ? 'Coba filter yang berbeda.' : 'Mulai buat pesanan pertama Anda.' ?>
        </p>
        <?php if (!($filters['search'] ?? '') && !($filters['status'] ?? '') && Session::can('orders.create')): ?>
        <a href="<?= url('/orders/create') ?>" class="btn btn-primary">
            <i class="fas fa-plus"></i> Buat Pesanan
        </a>
        <?php endif; ?>
    </div>
    <?php else: ?>
    <div class="table-responsive">
        <table class="table">
            <thead>
                <tr>
                    <th>Klien</th>
                    <th>Tanggal Event</th>
                    <th>Event</th>
                    <th>Total</th>
                    <th>Status</th>
                    <th>Pembayaran</th>
                    <th width="100">Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($orders as $order): 
                    // Use calculated total if stored value is 0
                    $displayTotal = ($order['total_final_price'] > 0) 
                        ? $order['total_final_price'] 
                        : ($orderTotals[$order['id']] ?? 0);
                    
                    // Calculate duration days for display
                    $eventDays = 1;
                    if (!empty($order['event_date']) && !empty($order['event_end_date']) && $order['event_end_date'] != $order['event_date']) {
                        $start = new DateTime($order['event_date']);
                        $end = new DateTime($order['event_end_date']);
                        $eventDays = $start->diff($end)->days + 1;
                    }
                ?>
                <tr>
                    <td>
                        <a href="<?= url('/orders/' . $order['id']) ?>" class="font-medium">
                            <?= e($order['client_name'] ?? 'Walk-in') ?>
                        </a>
                        <div class="text-muted" style="font-size: 0.75rem;">
                            <?= e($order['order_number']) ?>
                        </div>
                    </td>
                    <td>
                        <?php if (!empty($order['event_date'])): ?>
                            <div class="event-date-display">
                                <span class="date-main"><?= formatDate($order['event_date']) ?></span>
                                <?php if (!empty($order['event_end_date']) && $order['event_end_date'] != $order['event_date']): ?>
                                    <span class="date-separator">s/d</span>
                                    <span class="date-end"><?= formatDate($order['event_end_date']) ?></span>
                                    <span class="date-duration badge badge-info"><?= $eventDays ?> hari</span>
                                <?php endif; ?>
                            </div>
                        <?php else: ?>
                            <span class="text-muted"><?= formatDate($order['order_date']) ?></span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <?php if ($order['event_name']): ?>
                        <span title="<?= e($order['event_name']) ?>">
                            <?= e(truncate($order['event_name'], 25)) ?>
                        </span>
                        <?php else: ?>
                        -
                        <?php endif; ?>
                    </td>
                    <td class="font-medium"><?= formatRupiah($displayTotal) ?></td>
                    <td>
                        <?php $status = ORDER_STATUSES[$order['status']] ?? null; ?>
                        <?php if ($status): ?>
                        <span class="badge badge-<?= $status['color'] ?>">
                            <?= $status['label'] ?>
                        </span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <!-- Payment Status with Quick Update -->
                        <?php $paymentStatus = PAYMENT_STATUSES[$order['payment_status']] ?? null; ?>
                        <div class="payment-status-wrapper">
                            <?php if ($paymentStatus): ?>
                            <?php $orderRemaining = max(0, $displayTotal - (float)($order['paid_amount'] ?? 0)); ?>
                            <span class="badge badge-<?= $paymentStatus['color'] ?> payment-badge"
                                  data-order-id="<?= $order['id'] ?>"
                                  data-current-status="<?= $order['payment_status'] ?>"
                                  style="cursor: pointer;"
                                  onclick="openPaymentModal(<?= $order['id'] ?>, '<?= $order['payment_status'] ?>', '<?= e($order['paid_at'] ?? '') ?>', <?= (float) $orderRemaining ?>)">
                                <?= $paymentStatus['label'] ?>
                                <i class="fas fa-edit ms-1" style="font-size: 0.65rem;"></i>
                            </span>
                            <?php endif; ?>
                            <?php if (!empty($order['paid_at'])): ?>
                            <small class="d-block text-success mt-1" style="font-size: 0.7rem;">
                                <i class="fas fa-check-circle"></i> <?= formatDate($order['paid_at']) ?>
                            </small>
                            <?php endif; ?>
                        </div>
                    </td>
                    <td>
                        <div class="btn-group">
                            <a href="<?= url('/orders/' . $order['id']) ?>" 
                               class="btn btn-sm btn-icon btn-secondary" title="Lihat">
                                <i class="fas fa-eye"></i>
                            </a>
                            <?php if (Session::can('orders.update') && in_array($order['status'], ['draft', 'quotation'])): ?>
                            <a href="<?= url('/orders/' . $order['id'] . '/edit') ?>" 
                               class="btn btn-sm btn-icon btn-secondary" title="Edit">
                                <i class="fas fa-edit"></i>
                            </a>
                            <?php endif; ?>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    
    <!-- Pagination -->
    <?php if ($pagination['last_page'] > 1): ?>
    <div class="d-flex align-items-center justify-content-between mt-4 pt-4" style="border-top: 1px solid var(--gray-200);">
        <div class="text-sm text-muted">
            Menampilkan <?= $pagination['from'] ?> - <?= $pagination['to'] ?> dari <?= $pagination['total'] ?> data
        </div>
        <nav>
            <ul class="pagination mb-0">
                <?php if ($pagination['current_page'] > 1): ?>
                <li class="page-item">
                    <a class="page-link" href="<?= url('/orders?' . http_build_query(array_merge($filters, ['page' => $pagination['current_page'] - 1]))) ?>">
                        <i class="fas fa-chevron-left"></i>
                    </a>
                </li>
                <?php endif; ?>
                
                <?php
                $start = max(1, $pagination['current_page'] - 2);
                $end = min($pagination['last_page'], $pagination['current_page'] + 2);
                ?>
                
                <?php if ($start > 1): ?>
                <li class="page-item">
                    <a class="page-link" href="<?= url('/orders?' . http_build_query(array_merge($filters, ['page' => 1]))) ?>">1</a>
                </li>
                <?php if ($start > 2): ?>
                <li class="page-item disabled"><span class="page-link">...</span></li>
                <?php endif; ?>
                <?php endif; ?>
                
                <?php for ($i = $start; $i <= $end; $i++): ?>
                <li class="page-item <?= $i === $pagination['current_page'] ? 'active' : '' ?>">
                    <a class="page-link" href="<?= url('/orders?' . http_build_query(array_merge($filters, ['page' => $i]))) ?>"><?= $i ?></a>
                </li>
                <?php endfor; ?>
                
                <?php if ($end < $pagination['last_page']): ?>
                <?php if ($end < $pagination['last_page'] - 1): ?>
                <li class="page-item disabled"><span class="page-link">...</span></li>
                <?php endif; ?>
                <li class="page-item">
                    <a class="page-link" href="<?= url('/orders?' . http_build_query(array_merge($filters, ['page' => $pagination['last_page']]))) ?>"><?= $pagination['last_page'] ?></a>
                </li>
                <?php endif; ?>
                
                <?php if ($pagination['current_page'] < $pagination['last_page']): ?>
                <li class="page-item">
                    <a class="page-link" href="<?= url('/orders?' . http_build_query(array_merge($filters, ['page' => $pagination['current_page'] + 1]))) ?>">
                        <i class="fas fa-chevron-right"></i>
                    </a>
                </li>
                <?php endif; ?>
            </ul>
        </nav>
    </div>
    <?php endif; ?>
    <?php endif; ?>
</div>

<!-- Payment Modal -->
<div class="modal-overlay" id="paymentModal" style="display: none;">
    <div class="modal-content">
        <div class="modal-header">
            <h5><i class="fas fa-money-check-alt text-success"></i> Update Status Pembayaran</h5>
            <button type="button" class="modal-close" onclick="closePaymentModal()">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <form id="paymentForm" method="POST" action="" enctype="multipart/form-data">
            <?= csrfField() ?>
            <input type="hidden" name="_method" id="paymentMethodOverride" value="PATCH">
            <div class="modal-body">
                <div class="form-group mb-3">
                    <label class="form-label">Status Pembayaran</label>
                    <select name="payment_status" id="modalPaymentStatus" class="form-control">
                        <option value="unpaid">Belum Dibayar</option>
                        <option value="paid">Telah Dibayar</option>
                        <option value="partial">Dibayar Sebagian</option>
                    </select>
                </div>

                <!-- Lunas / Sebagian: catat pembayaran sungguhan (masuk order_payments) -->
                <div id="paymentRecordGroup" style="display: none;">
                    <input type="hidden" name="payment_method" value="transfer">
                    <div class="form-group mb-3">
                        <label class="form-label">Tanggal Pembayaran</label>
                        <input type="date" name="payment_date" id="modalPaymentDate" class="form-control" value="<?= date('Y-m-d') ?>">
                    </div>
                    <div class="form-group mb-3">
                        <label class="form-label">Nominal</label>
                        <input type="text" name="amount" id="modalAmount" class="form-control rupiah-input" data-remaining="0">
                        <small class="text-muted" id="modalRemainingHint"></small>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Bukti Transaksi</label>
                        <input type="file" name="proof_image" class="form-control" accept="image/*">
                        <small class="text-muted">Opsional, maks 2MB (jpg/png)</small>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closePaymentModal()">Batal</button>
                <button type="submit" class="btn btn-success">
                    <i class="fas fa-save"></i> Simpan
                </button>
            </div>
        </form>
    </div>
</div>

<style>
/* Event date display */
.event-date-display {
    display: flex;
    flex-direction: column;
    gap: 0.15rem;
    font-size: 0.85rem;
}

.event-date-display .date-main {
    font-weight: 500;
    color: var(--gray-800);
}

.event-date-display .date-separator {
    font-size: 0.7rem;
    color: var(--gray-500);
    font-style: italic;
}

.event-date-display .date-end {
    color: var(--gray-700);
}

.event-date-display .date-duration {
    font-size: 0.65rem;
    padding: 0.1rem 0.35rem;
    margin-top: 0.15rem;
    width: fit-content;
}

/* Payment status wrapper */
.payment-status-wrapper {
    display: flex;
    flex-direction: column;
    align-items: flex-start;
}

.payment-badge {
    transition: all 0.2s;
}

.payment-badge:hover {
    transform: scale(1.05);
    box-shadow: 0 2px 8px rgba(0,0,0,0.15);
}

/* Modal styles */
.modal-overlay {
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(0, 0, 0, 0.5);
    display: flex;
    align-items: center;
    justify-content: center;
    z-index: 9999;
    backdrop-filter: blur(4px);
}

.modal-content {
    background: white;
    border-radius: 16px;
    width: 100%;
    max-width: 420px;
    box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
    overflow: hidden;
    animation: modalSlideIn 0.3s ease;
}

@keyframes modalSlideIn {
    from {
        opacity: 0;
        transform: translateY(-20px) scale(0.95);
    }
    to {
        opacity: 1;
        transform: translateY(0) scale(1);
    }
}

.modal-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 1.25rem 1.5rem;
    border-bottom: 1px solid var(--gray-200);
    background: linear-gradient(135deg, #f0fdf4, #dcfce7);
}

.modal-header h5 {
    margin: 0;
    font-size: 1.1rem;
    font-weight: 700;
    color: var(--gray-800);
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.modal-close {
    background: none;
    border: none;
    font-size: 1.2rem;
    color: var(--gray-500);
    cursor: pointer;
    padding: 0.25rem;
    border-radius: 6px;
    transition: all 0.2s;
}

.modal-close:hover {
    background: var(--gray-100);
    color: var(--gray-700);
}

.modal-body {
    padding: 1.5rem;
}

.modal-footer {
    display: flex;
    justify-content: flex-end;
    gap: 0.75rem;
    padding: 1rem 1.5rem;
    border-top: 1px solid var(--gray-200);
    background: var(--gray-50);
}

/* Responsive */
@media (max-width: 576px) {
    .modal-content {
        margin: 1rem;
        max-width: calc(100% - 2rem);
    }
    
    .event-date-display {
        font-size: 0.75rem;
    }
}
</style>

<script>
function openPaymentModal(orderId, currentStatus, paidAt, remaining) {
    var modal = document.getElementById('paymentModal');
    var form = document.getElementById('paymentForm');
    var statusSelect = document.getElementById('modalPaymentStatus');
    var amountInput = document.getElementById('modalAmount');

    // Base action always targets payment-status; togglePaidAtField() swaps it
    // to /payment when the chosen status needs a real payment record.
    form.action = '/orders/' + orderId + '/payment-status';
    form.dataset.orderId = orderId;
    amountInput.dataset.remaining = remaining || 0;

    // Set current status
    statusSelect.value = currentStatus;

    // Show/hide payment-record fields + switch submission target based on status
    togglePaidAtField(currentStatus);

    // Show modal
    modal.style.display = 'flex';

    // Add event listener for status change
    statusSelect.onchange = function() {
        togglePaidAtField(this.value);
    };
}

function togglePaidAtField(status) {
    var form = document.getElementById('paymentForm');
    var recordGroup = document.getElementById('paymentRecordGroup');
    var methodOverride = document.getElementById('paymentMethodOverride');
    var amountInput = document.getElementById('modalAmount');
    var remainingHint = document.getElementById('modalRemainingHint');
    var remaining = parseFloat(amountInput.dataset.remaining || '0');
    var orderId = form.dataset.orderId;

    if (status === 'paid' || status === 'partial') {
        recordGroup.style.display = 'block';
        form.action = '/orders/' + orderId + '/payment';
        methodOverride.disabled = true;

        if (status === 'paid') {
            amountInput.value = new Intl.NumberFormat('id-ID').format(remaining);
            amountInput.readOnly = true;
            remainingHint.textContent = 'Nominal otomatis disamakan dengan sisa tagihan.';
        } else {
            amountInput.readOnly = false;
            if (amountInput.value === new Intl.NumberFormat('id-ID').format(remaining)) {
                amountInput.value = '';
            }
            remainingHint.textContent = 'Sisa tagihan saat ini: Rp ' + new Intl.NumberFormat('id-ID').format(remaining);
            amountInput.oninput = function() {
                var typed = parseFloat(this.value.replace(/[^0-9]/g, '')) || 0;
                var left = Math.max(0, remaining - typed);
                remainingHint.textContent = 'Sisa setelah pembayaran ini: Rp ' + new Intl.NumberFormat('id-ID').format(left);
            };
        }
    } else {
        recordGroup.style.display = 'none';
        form.action = '/orders/' + orderId + '/payment-status';
        methodOverride.disabled = false;
    }
}

function closePaymentModal() {
    document.getElementById('paymentModal').style.display = 'none';
}

// Close modal on overlay click
document.getElementById('paymentModal').addEventListener('click', function(e) {
    if (e.target === this) {
        closePaymentModal();
    }
});

// Close modal on Escape key
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        closePaymentModal();
    }
});
</script>