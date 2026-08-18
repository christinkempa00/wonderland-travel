<?php
/**
 * Order Show View - FIXED v6
 * Variables from render(): $order, $items, $client, $pageTitle
 * 
 * FIXED: Use num_days from each item directly (NO fallback to event duration)
 * FIXED: Display item price calculation breakdown (qty × days × unit_price = total)
 * Added: support_for (dukungan) field
 * Added: paid_at (tanggal pembayaran) field
 */

// Get items and client if not passed
if (!isset($items)) {
    $items = $order->getItems();
}
if (!isset($client)) {
    $client = $order->getClient();
}

// Calculate order event duration for display only (NOT for calculation)
$orderEventDays = 1;
if (!empty($order->event_date) && !empty($order->event_end_date) && $order->event_end_date != $order->event_date) {
    $startDate = new DateTime($order->event_date);
    $endDate = new DateTime($order->event_end_date);
    $orderEventDays = $startDate->diff($endDate)->days + 1;
}

// Calculate totals from items data - using each item's own num_days DIRECTLY
$calculatedSubtotal = 0;
$calculatedMarkup = 0;
$calculatedTotal = 0;

foreach ($items as $item) {
    $qty = (int)($item['quantity'] ?? 1);
    $days = (int)($item['num_days'] ?? 1); // Use item's num_days directly - NO FALLBACK
    $basePrice = (float)($item['base_price'] ?? 0);
    $markupType = $item['markup_type'] ?? 'percentage';
    $markupValue = (float)($item['markup_value'] ?? 0);
    
    // Calculate item base (qty * days * base_price)
    $itemBase = $qty * $days * $basePrice;
    
    // Calculate markup
    if ($markupType === 'percentage') {
        $itemMarkup = $itemBase * ($markupValue / 100);
    } else {
        $itemMarkup = $markupValue * $qty * $days;
    }
    
    // Calculate item final
    $itemFinal = $itemBase + $itemMarkup;
    
    $calculatedSubtotal += $itemBase;
    $calculatedMarkup += $itemMarkup;
    $calculatedTotal += $itemFinal;
}

// Use calculated values if stored values are 0
$displaySubtotal = ($order->total_base_price > 0) ? $order->total_base_price : $calculatedSubtotal;
$displayMarkup = ($order->total_markup != 0 || $order->total_final_price > 0) ? $order->total_markup : $calculatedMarkup;
$displayTotal = ($order->total_final_price > 0) ? $order->total_final_price : $calculatedTotal;
$displayProfit = ($order->total_profit != 0 || $order->total_markup != 0) ? ($order->total_profit ?: $order->total_markup) : $calculatedMarkup;

// If all stored are 0, use calculated
if ($order->total_base_price == 0 && $order->total_final_price == 0) {
    $displaySubtotal = $calculatedSubtotal;
    $displayMarkup = $calculatedMarkup;
    $displayTotal = $calculatedTotal;
    $displayProfit = $calculatedMarkup;
}
?>

<div class="page-actions mb-4">
    <a href="<?= url('/orders') ?>" class="btn btn-secondary">
        <i class="fas fa-arrow-left"></i> Kembali
    </a>
    <?php if (Session::can('orders.update')): ?>
    <a href="<?= url('/orders/' . $order->id . '/edit') ?>" class="btn btn-warning">
        <i class="fas fa-edit"></i> Edit
    </a>
    
    <?php endif; ?>
    <?php if (Session::can('orders.delete')): ?>
    <form method="POST" action="<?= url('/orders/' . $order->id) ?>" style="display:inline" 
          onsubmit="return confirm('Yakin ingin menghapus pesanan ini?')">
        <?= csrfField() ?>
        <?= methodField('DELETE') ?>
        <button type="submit" class="btn btn-danger">
            <i class="fas fa-trash"></i> Hapus
        </button>
    </form>
    <?php endif; ?>
</div>

<div class="row">
    <!-- Left Column -->
    <div class="col-12 col-lg-8">
        <!-- Order Info -->
        <div class="glass-card mb-4">
            <div class="order-header">
                <h3><i class="fas fa-info-circle text-primary"></i> Informasi Pesanan</h3>
                <span class="order-num-badge"><?= e($order->order_number) ?></span>
            </div>
            
            <div class="info-grid">
                <div class="info-item">
                    <label>Tanggal Pesanan</label>
                    <span><?= formatDate($order->order_date) ?></span>
                </div>
                <div class="info-item">
                    <label>Klien</label>
                    <span>
                        <?php if ($client): ?>
                        <a href="<?= url('/clients/' . $client['id']) ?>"><?= e($client['name']) ?></a>
                        <?php else: ?>
                        <span class="text-muted">-</span>
                        <?php endif; ?>
                    </span>
                </div>
                <div class="info-item">
                    <label>Tanggal Event</label>
                    <span>
                        <?php if ($order->event_date): ?>
                            <?= formatDate($order->event_date) ?>
                            <?php if ($order->event_end_date && $order->event_end_date != $order->event_date): ?>
                            s/d <?= formatDate($order->event_end_date) ?>
                            <span class="badge badge-info" style="font-size: 0.7rem; margin-left: 0.3rem;"><?= $orderEventDays ?> hari</span>
                            <?php endif; ?>
                        <?php else: ?>
                        -
                        <?php endif; ?>
                    </span>
                </div>
                <div class="info-item">
                    <label>Nama Event</label>
                    <span><?= e($order->event_name) ?: '-' ?></span>
                </div>
            </div>
            
            <?php if ($order->description): ?>
            <div class="order-desc">
                <label>Deskripsi</label>
                <p><?= nl2br(e($order->description)) ?></p>
            </div>
            <?php endif; ?>
        </div>
        
        <!-- Items -->
        <div class="glass-card">
            <h3 class="mb-4"><i class="fas fa-list text-primary"></i> Item Pesanan</h3>
            
            <?php if (empty($items)): ?>
            <div class="empty-state">
                <i class="fas fa-box-open"></i>
                <p>Belum ada item</p>
            </div>
            <?php else: ?>
            <div class="items-list">
                <?php foreach ($items as $idx => $item): 
                    // Calculate item final price for display
                    $qty = (int)($item['quantity'] ?? 1);
                    $days = (int)($item['num_days'] ?? 1); // Use item's num_days directly - NO FALLBACK
                    $basePrice = (float)($item['base_price'] ?? 0);
                    $markupType = $item['markup_type'] ?? 'percentage';
                    $markupValue = (float)($item['markup_value'] ?? 0);
                    
                    // Calculate base total (qty × days × unit_price)
                    $itemBaseTotal = $qty * $days * $basePrice;
                    
                    // Calculate markup
                    if ($markupType === 'percentage') {
                        $itemMarkup = $itemBaseTotal * ($markupValue / 100);
                    } else {
                        $itemMarkup = $markupValue * $qty * $days;
                    }
                    
                    // Final price = base + markup
                    $itemFinal = $itemBaseTotal + $itemMarkup;
                    
                    // Use stored final_price if > 0, otherwise use calculated
                    $displayItemFinal = ($item['final_price'] > 0) ? $item['final_price'] : $itemFinal;
                    $displayItemMarkup = ($item['markup_amount'] > 0) ? $item['markup_amount'] : $itemMarkup;
                ?>
                <div class="item-row">
                    <div class="item-num"><?= $idx + 1 ?></div>
                    <div class="item-info">
                        <div class="item-title">
                            <?= e($item['description']) ?>
                            <?php if (!empty($item['vehicle_plate'])): ?>
                            <span class="plate-badge"><?= e($item['vehicle_plate']) ?></span>
                            <?php endif; ?>
                        </div>
                        <div class="item-meta">
                            <span class="badge badge-secondary"><?= e(ITEM_TYPES[$item['item_type']]['label'] ?? $item['item_type']) ?></span>
                            
                            <!-- Show complete calculation breakdown using item's own num_days -->
                            <span class="price-calc">
                                <?= $qty ?> unit
                                <?php if ($days > 1): ?>
                                × <?= $days ?> hari
                                <?php endif; ?>
                                × <?= formatRupiah($basePrice) ?>
                                = <strong><?= formatRupiah($itemBaseTotal) ?></strong>
                            </span>
                        </div>
                        
                        <?php if ($markupValue > 0): ?>
                        <div class="item-markup-info">
                            <small class="text-success">
                                <i class="fas fa-plus-circle"></i>
                                Markup: 
                                <?php if ($markupType === 'percentage'): ?>
                                    <?= number_format($markupValue, 0) ?>% = <?= formatRupiah($displayItemMarkup) ?>
                                <?php else: ?>
                                    <?= formatRupiah($markupValue) ?>/unit/hari × <?= $qty ?> × <?= $days ?> = <?= formatRupiah($displayItemMarkup) ?>
                                <?php endif; ?>
                            </small>
                        </div>
                        <?php endif; ?>
                    </div>
                    <div class="item-price">
                        <span class="final-price"><?= formatRupiah($displayItemFinal) ?></span>
                        <?php if ($displayItemMarkup > 0): ?>
                        <small class="text-success">incl. markup</small>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            
            <!-- Totals - Using calculated values -->
            <div class="totals-box">
                <div class="total-row">
                    <span>Subtotal (Modal)</span>
                    <span><?= formatRupiah($displaySubtotal) ?></span>
                </div>
                <div class="total-row">
                    <span>Total Markup</span>
                    <span class="text-success">+<?= formatRupiah($displayMarkup) ?></span>
                </div>
                <div class="total-row grand">
                    <span>Total Harga Jual</span>
                    <span><?= formatRupiah($displayTotal) ?></span>
                </div>
                <div class="total-row profit">
                    <span>💰 Estimasi Profit</span>
                    <span><?= formatRupiah($displayProfit) ?></span>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- Right Column -->
    <div class="col-12 col-lg-4">
        <!-- Status & Payment Section -->
        <div class="glass-card mb-4">
            <h3 class="mb-3"><i class="fas fa-tasks text-primary"></i> Status Pesanan</h3>
            
            <div class="status-grid">
                <div class="status-item">
                    <label>Status Order</label>
                    <?php $status = ORDER_STATUSES[$order->status] ?? null; ?>
                    <?php if ($status): ?>
                    <span class="badge badge-<?= $status['color'] ?> badge-lg">
                        <?= $status['label'] ?>
                    </span>
                    <?php endif; ?>
                </div>
                
                <div class="status-item">
                    <label>Status Pembayaran</label>
                    <?php $paymentStatus = PAYMENT_STATUSES[$order->payment_status] ?? null; ?>
                    <?php if ($paymentStatus): ?>
                    <span class="badge badge-<?= $paymentStatus['color'] ?> badge-lg">
                        <?= $paymentStatus['label'] ?>
                    </span>
                    <?php endif; ?>
                </div>
                
                <?php if (!empty($order->paid_at)): ?>
                <div class="status-item paid-info">
                    <label><i class="fas fa-calendar-check text-success"></i> Tanggal Pembayaran</label>
                    <span class="paid-date"><?= formatDate($order->paid_at) ?></span>
                </div>
                <?php endif; ?>
            </div>
            
            <!-- Quick Payment Update Button -->
            <?php if (Session::can('orders.update')): ?>
            <div class="mt-3 pt-3" style="border-top: 1px solid var(--gray-200);">
                <button type="button" class="btn btn-success btn-block" onclick="openPaymentModal(<?= $order->id ?>, '<?= $order->payment_status ?>', '<?= e($order->paid_at ?? '') ?>')">
                    <i class="fas fa-money-check-alt"></i> Update Status Pembayaran
                </button>
            </div>
            <?php endif; ?>
        </div>
        
        <!-- Hotel Guests Section -->
        <?php if (!empty($hasHotelItems)): ?>
        <div class="glass-card mb-4">
            <h3 class="mb-3"><i class="fas fa-hotel text-primary"></i> Data Tamu Hotel</h3>
            <div class="hotel-guests-info">
                <?php if ($hotelGuestCount > 0): ?>
                <div class="guest-count-badge">
                    <i class="fas fa-users"></i>
                    <span><?= $hotelGuestCount ?> tamu terdaftar</span>
                </div>
                <?php else: ?>
                <p class="text-muted mb-3">Belum ada data tamu hotel.</p>
                <?php endif; ?>
                
                <div class="hotel-actions">
                    <a href="<?= url('/orders/' . $order->id . '/hotel-guests') ?>" class="btn btn-primary btn-block">
                        <i class="fas fa-user-edit"></i> Kelola Data Tamu
                    </a>
                    <?php if ($hotelGuestCount > 0): ?>
                    <a href="<?= url('/orders/' . $order->id . '/hotel-attachment') ?>" target="_blank" class="btn btn-outline-success btn-block mt-2">
                        <i class="fas fa-print"></i> Cetak Lampiran
                    </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <?php endif; ?>
        
        <!-- Flight Passengers Section -->
        <?php if (!empty($hasFlightItems)): ?>
        <div class="glass-card mb-4">
            <h3 class="mb-3"><i class="fas fa-plane text-primary"></i> Data Penumpang Pesawat</h3>
            <div class="hotel-guests-info">
                <?php if ($flightDetailCount > 0): ?>
                <div class="guest-count-badge flight-badge">
                    <i class="fas fa-ticket-alt"></i>
                    <span><?= $flightDetailCount ?> tiket terdaftar</span>
                </div>
                <?php else: ?>
                <p class="text-muted mb-3">Belum ada data penumpang.</p>
                <?php endif; ?>
                
                <div class="hotel-actions">
                    <a href="<?= url('/orders/' . $order->id . '/flight-guests') ?>" class="btn btn-primary btn-block">
                        <i class="fas fa-user-edit"></i> Kelola Data Penumpang
                    </a>
                    <?php if ($flightDetailCount > 0): ?>
                    <a href="<?= url('/orders/' . $order->id . '/flight-attachment') ?>" target="_blank" class="btn btn-outline-success btn-block mt-2">
                        <i class="fas fa-print"></i> Cetak Lampiran
                    </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <?php endif; ?>
        
        <!-- Rental Vehicles Section -->
        <?php if (!empty($hasRentalItems)): ?>
        <div class="glass-card mb-4">
            <h3 class="mb-3"><i class="fas fa-car text-primary"></i> Data Rental Kendaraan</h3>
            <div class="hotel-guests-info">
                <?php if ($rentalDetailCount > 0): ?>
                <div class="guest-count-badge rental-badge">
                    <i class="fas fa-car"></i>
                    <span><?= $rentalDetailCount ?> kendaraan terdaftar</span>
                </div>
                <?php else: ?>
                <p class="text-muted mb-3">Belum ada data kendaraan.</p>
                <?php endif; ?>
                
                <div class="hotel-actions">
                    <a href="<?= url('/orders/' . $order->id . '/rental-vehicles') ?>" class="btn btn-primary btn-block">
                        <i class="fas fa-car"></i> Kelola Data Kendaraan
                    </a>
                    <?php if ($rentalDetailCount > 0): ?>
                    <a href="<?= url('/orders/' . $order->id . '/rental-attachment') ?>" target="_blank" class="btn btn-outline-success btn-block mt-2">
                        <i class="fas fa-print"></i> Cetak Lampiran
                    </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <?php endif; ?>
        
        <!-- Vehicle Documents Section -->
        <?php if (!empty($hasVehicleItems)): ?>
        <div class="glass-card mb-4">
            <h3 class="mb-3"><i class="fas fa-id-card text-primary"></i> Dokumen Kendaraan</h3>
            <div class="hotel-guests-info">
                <?php if ($vehicleDocCount > 0): ?>
                <div class="guest-count-badge vehicle-badge">
                    <i class="fas fa-file-alt"></i>
                    <span><?= $vehicleDocCount ?> dokumen terdaftar</span>
                </div>
                <?php else: ?>
                <p class="text-muted mb-3">Belum ada dokumen kendaraan.</p>
                <?php endif; ?>
                
                <div class="hotel-actions">
                    <a href="<?= url('/orders/' . $order->id . '/vehicle-documents') ?>" class="btn btn-primary btn-block">
                        <i class="fas fa-id-card"></i> Kelola Dokumen
                    </a>
                    <?php if ($vehicleDocCount > 0): ?>
                    <a href="<?= url('/orders/' . $order->id . '/vehicle-documents/print') ?>" target="_blank" class="btn btn-outline-success btn-block mt-2">
                        <i class="fas fa-print"></i> Cetak Dokumen
                    </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <?php endif; ?>
        
        <!-- Documents -->
        <div class="glass-card mb-4">
            <h3 class="mb-3"><i class="fas fa-file-pdf text-primary"></i> Dokumen</h3>
            <div class="doc-buttons">
                <a href="<?= url('/doc.php?order=' . $order->id . '&type=invoice') ?>" target="_blank" class="doc-btn doc-invoice">
                    <span class="doc-icon"><i class="fas fa-file-invoice-dollar"></i></span>
                    <span class="doc-text">
                        <strong>Invoice</strong>
                        <small>Tagihan Pembayaran</small>
                    </span>
                    <i class="fas fa-external-link-alt"></i>
                </a>
                <a href="<?= url('/doc.php?order=' . $order->id . '&type=kwitansi') ?>" target="_blank" class="doc-btn doc-kwitansi">
                    <span class="doc-icon"><i class="fas fa-receipt"></i></span>
                    <span class="doc-text">
                        <strong>Kwitansi</strong>
                        <small>Bukti Pembayaran</small>
                    </span>
                    <i class="fas fa-external-link-alt"></i>
                </a>
                <a href="<?= url('/doc.php?order=' . $order->id . '&type=penawaran') ?>" target="_blank" class="doc-btn doc-penawaran">
                    <span class="doc-icon"><i class="fas fa-file-alt"></i></span>
                    <span class="doc-text">
                        <strong>Penawaran</strong>
                        <small>Surat Penawaran Harga</small>
                    </span>
                    <i class="fas fa-external-link-alt"></i>
                </a>
                <a href="<?= url('/doc.php?order=' . $order->id . '&type=kesepakatan') ?>" target="_blank" class="doc-btn doc-kesepakatan">
                    <span class="doc-icon"><i class="fas fa-handshake"></i></span>
                    <span class="doc-text">
                        <strong>Kesepakatan</strong>
                        <small>Surat Kesepakatan Harga</small>
                    </span>
                    <i class="fas fa-external-link-alt"></i>
                </a>
            </div>
        </div>

        <!-- Notes -->
        <?php if ($order->notes): ?>
        <div class="glass-card mb-4">
            <h3 class="mb-3"><i class="fas fa-sticky-note text-primary"></i> Catatan</h3>
            <div class="notes-box">
                <?= nl2br(e($order->notes)) ?>
            </div>
        </div>
        <?php endif; ?>
        
        <!-- Client Info -->
        <?php if ($client): ?>
        <div class="glass-card">
            <h3 class="mb-3"><i class="fas fa-user text-primary"></i> Info Klien</h3>
            <div class="client-box">
                <div class="client-name"><?= e($client['name']) ?></div>
                <?php if ($client['company']): ?>
                <p><i class="fas fa-building"></i> <?= e($client['company']) ?></p>
                <?php endif; ?>
                <?php if ($client['phone']): ?>
                <p><i class="fas fa-phone"></i> <?= e($client['phone']) ?></p>
                <?php endif; ?>
                <?php if ($client['email']): ?>
                <p><i class="fas fa-envelope"></i> <?= e($client['email']) ?></p>
                <?php endif; ?>
                <?php if ($client['address']): ?>
                <p><i class="fas fa-map-marker-alt"></i> <?= e($client['address']) ?></p>
                <?php endif; ?>
            </div>
        </div>
        <?php endif; ?>
    </div>
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
        <form id="paymentForm" method="POST" action="<?= url('/orders/' . $order->id . '/payment-status') ?>">
            <?= csrfField() ?>
            <?= methodField('PATCH') ?>
            <div class="modal-body">
                <div class="form-group mb-3">
                    <label class="form-label">Status Pembayaran</label>
                    <select name="payment_status" id="modalPaymentStatus" class="form-control">
                        <?php foreach (PAYMENT_STATUSES as $key => $ps): ?>
                        <option value="<?= $key ?>" <?= $order->payment_status === $key ? 'selected' : '' ?>>
                            <?= $ps['label'] ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group" id="paidAtGroup">
                    <label class="form-label">Tanggal Pembayaran</label>
                    <input type="date" name="paid_at" id="modalPaidAt" class="form-control" 
                           value="<?= e($order->paid_at ?? date('Y-m-d')) ?>">
                    <small class="text-muted">Tanggal saat pembayaran diterima</small>
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
/* Order Header */
.order-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 1.5rem;
    padding-bottom: 1rem;
    border-bottom: 1px solid var(--gray-200);
}

.order-header h3 {
    margin: 0;
    font-size: 1.1rem;
    color: var(--gray-800);
}

.order-num-badge {
    background: linear-gradient(135deg, var(--primary), var(--primary-hover));
    color: white;
    padding: 0.4rem 1rem;
    border-radius: 20px;
    font-weight: 700;
    font-size: 0.85rem;
    letter-spacing: 0.5px;
}

/* Info Grid */
.info-grid {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 1rem;
    margin-bottom: 1rem;
}

.info-item label {
    display: block;
    font-size: 0.75rem;
    color: var(--gray-500);
    text-transform: uppercase;
    letter-spacing: 0.5px;
    margin-bottom: 0.3rem;
}

.info-item span {
    font-size: 0.9rem;
    color: var(--gray-800);
    font-weight: 500;
}

.info-item a {
    color: var(--primary);
    text-decoration: none;
}

.info-item a:hover {
    text-decoration: underline;
}

/* Support For Box */
.support-for-box {
    background: linear-gradient(135deg, #f0fdf4, #dcfce7);
    border: 1px solid #86efac;
    border-radius: 8px;
    padding: 1rem;
    margin-bottom: 1rem;
}

.support-for-box label {
    display: block;
    font-size: 0.8rem;
    color: #166534;
    margin-bottom: 0.3rem;
    font-weight: 600;
}

.support-for-box p {
    margin: 0;
    color: #166534;
    font-size: 0.95rem;
}

/* Order Desc */
.order-desc {
    background: var(--gray-50);
    padding: 1rem;
    border-radius: 8px;
}

.order-desc label {
    display: block;
    font-size: 0.75rem;
    color: var(--gray-500);
    text-transform: uppercase;
    margin-bottom: 0.5rem;
}

.order-desc p {
    margin: 0;
    font-size: 0.9rem;
    color: var(--gray-700);
    line-height: 1.6;
}

/* Items List */
.items-list {
    display: flex;
    flex-direction: column;
    gap: 0.75rem;
}

.item-row {
    display: flex;
    align-items: flex-start;
    gap: 1rem;
    padding: 1rem;
    background: var(--gray-50);
    border-radius: 8px;
    transition: background 0.2s;
}

.item-row:hover {
    background: var(--gray-100);
}

.item-num {
    width: 28px;
    height: 28px;
    background: var(--primary);
    color: white;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 0.75rem;
    font-weight: 700;
    flex-shrink: 0;
    margin-top: 0.2rem;
}

.item-info {
    flex: 1;
    min-width: 0;
}

.item-title {
    font-weight: 600;
    color: var(--gray-800);
    font-size: 0.95rem;
    margin-bottom: 0.4rem;
    display: flex;
    align-items: center;
    gap: 0.5rem;
    flex-wrap: wrap;
}

.plate-badge {
    background: var(--gray-800);
    color: white;
    padding: 0.15rem 0.5rem;
    border-radius: 4px;
    font-size: 0.7rem;
    font-weight: 600;
}

.item-meta {
    display: flex;
    flex-wrap: wrap;
    gap: 0.5rem;
    font-size: 0.8rem;
    color: var(--gray-600);
    align-items: center;
}

/* Price calculation styling */
.price-calc {
    background: #f0f9ff;
    border: 1px solid #bae6fd;
    padding: 0.25rem 0.5rem;
    border-radius: 4px;
    font-family: 'Monaco', 'Menlo', monospace;
    font-size: 0.75rem;
}

.price-calc strong {
    color: #0369a1;
}

/* Item markup info */
.item-markup-info {
    margin-top: 0.4rem;
    padding-left: 0.5rem;
    border-left: 2px solid #86efac;
}

.item-markup-info small {
    font-size: 0.72rem;
}

.item-price {
    text-align: right;
    flex-shrink: 0;
    min-width: 120px;
}

.item-price .final-price {
    font-weight: 700;
    color: var(--gray-900);
    font-size: 1rem;
    display: block;
}

.item-price small {
    display: block;
    font-weight: 500;
    font-size: 0.68rem;
    color: #16a34a;
}

/* Totals Box */
.totals-box {
    margin-top: 1.5rem;
    padding-top: 1rem;
    border-top: 2px solid var(--gray-200);
}

.total-row {
    display: flex;
    justify-content: space-between;
    padding: 0.5rem 0;
    font-size: 0.9rem;
    color: var(--gray-700);
}

.total-row.grand {
    font-size: 1.1rem;
    font-weight: 700;
    color: var(--gray-900);
    border-top: 1px solid var(--gray-200);
    padding-top: 0.75rem;
    margin-top: 0.5rem;
}

.total-row.profit {
    background: linear-gradient(135deg, #fef3c7, #fde68a);
    margin: 0.5rem -1rem -1rem;
    padding: 0.75rem 1rem;
    border-radius: 0 0 8px 8px;
    font-weight: 600;
    color: #92400e;
}

/* Status Grid */
.status-grid {
    display: flex;
    flex-direction: column;
    gap: 1rem;
}

.status-item label {
    display: block;
    font-size: 0.75rem;
    color: var(--gray-500);
    text-transform: uppercase;
    margin-bottom: 0.3rem;
}

.badge-lg {
    padding: 0.5rem 1rem;
    font-size: 0.85rem;
}

.paid-info {
    background: linear-gradient(135deg, #f0fdf4, #dcfce7);
    padding: 0.75rem;
    border-radius: 8px;
    border: 1px solid #86efac;
}

.paid-date {
    font-weight: 600;
    color: #166534;
}

/* Guest Count Badge */
.guest-count-badge {
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    background: linear-gradient(135deg, #faefd0, #f4dfa3);
    color: #6b5216;
    padding: 0.5rem 1rem;
    border-radius: 20px;
    font-weight: 600;
    margin-bottom: 1rem;
}

.guest-count-badge.flight-badge {
    background: linear-gradient(135deg, #fef3c7, #fde68a);
    color: #92400e;
}

.guest-count-badge.rental-badge {
    background: linear-gradient(135deg, #fce7f3, #fbcfe8);
    color: #9d174d;
}

.guest-count-badge.vehicle-badge {
    background: linear-gradient(135deg, #e0e7ff, #c7d2fe);
    color: #3730a3;
}

.hotel-actions {
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
}

/* Document Buttons */
.doc-buttons {
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
}

.doc-btn {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    padding: 0.75rem 1rem;
    border-radius: 10px;
    text-decoration: none;
    color: white;
    transition: all 0.2s;
}

.doc-btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 16px rgba(0,0,0,0.15);
    color: white;
}

.doc-btn .doc-icon {
    font-size: 1.3rem;
}

.doc-btn .doc-text {
    flex: 1;
}

.doc-btn .doc-text strong {
    display: block;
    font-size: 0.85rem;
}

.doc-btn .doc-text small {
    font-size: 0.7rem;
    opacity: 0.85;
}

.doc-btn .fa-external-link-alt {
    opacity: 0.6;
    font-size: 0.7rem;
}

.doc-invoice { background: linear-gradient(135deg, #dc2626, #b91c1c); }
.doc-kwitansi { background: linear-gradient(135deg, #7c3aed, #6d28d9); }
.doc-penawaran { background: linear-gradient(135deg, #a67f20, #85661a); }
.doc-kesepakatan { background: linear-gradient(135deg, #22c55e, #16a34a); }

/* Notes & Client */
.notes-box {
    background: var(--gray-50);
    padding: 0.8rem;
    border-radius: 8px;
    font-size: 0.85rem;
    color: var(--gray-700);
    line-height: 1.5;
}

.client-box .client-name {
    font-size: 1rem;
    font-weight: 700;
    color: var(--gray-800);
    margin-bottom: 0.6rem;
}

.client-box p {
    font-size: 0.8rem;
    color: var(--gray-600);
    margin-bottom: 0.3rem;
    display: flex;
    align-items: flex-start;
    gap: 0.4rem;
}

.client-box p i {
    width: 14px;
    color: var(--gray-400);
    margin-top: 2px;
}

.empty-state {
    text-align: center;
    padding: 2rem 1rem;
    color: var(--gray-500);
}

.empty-state i {
    font-size: 2.5rem;
    margin-bottom: 0.8rem;
    opacity: 0.5;
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

@media (max-width: 991.98px) {
    .info-grid {
        grid-template-columns: 1fr;
    }
}

@media (max-width: 767.98px) {
    .item-row {
        flex-direction: column;
        gap: 0.6rem;
    }
    
    .item-price {
        text-align: left;
        min-width: auto;
        width: 100%;
        padding-top: 0.5rem;
        border-top: 1px dashed var(--gray-300);
    }
    
    .modal-content {
        margin: 1rem;
        max-width: calc(100% - 2rem);
    }
    
    .price-calc {
        font-size: 0.7rem;
        width: 100%;
        display: block;
        margin-top: 0.3rem;
    }
}
</style>

<script>
function openPaymentModal(orderId, currentStatus, paidAt) {
    var modal = document.getElementById('paymentModal');
    var statusSelect = document.getElementById('modalPaymentStatus');
    var paidAtInput = document.getElementById('modalPaidAt');
    
    // Set current status
    statusSelect.value = currentStatus;
    
    // Set paid_at if exists
    if (paidAt) {
        paidAtInput.value = paidAt;
    } else {
        paidAtInput.value = new Date().toISOString().split('T')[0];
    }
    
    // Show/hide paid_at based on status
    togglePaidAtField(currentStatus);
    
    // Show modal
    modal.style.display = 'flex';
    
    // Add event listener for status change
    statusSelect.onchange = function() {
        togglePaidAtField(this.value);
    };
}

function togglePaidAtField(status) {
    var paidAtGroup = document.getElementById('paidAtGroup');
    if (status === 'paid' || status === 'partial') {
        paidAtGroup.style.display = 'block';
    } else {
        paidAtGroup.style.display = 'none';
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

// Initialize paid_at field visibility
document.addEventListener('DOMContentLoaded', function() {
    togglePaidAtField('<?= $order->payment_status ?>');
});
</script>