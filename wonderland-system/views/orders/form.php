<?php
/**
 * Order Form View (Create/Edit) - FIXED v3
 * - Better responsive layout
 * - FIXED: Jumlah hari per item INDEPENDEN (tidak auto-sync dengan tanggal event)
 * - Added: Tombol "Terapkan ke Semua Item" untuk sync manual
 * - Added: Kolom Dukungan (support_for)
 */

$orderData = $order ? $order->toArray() : [
    'order_date' => date('Y-m-d'),
    'client_id' => '',
    'event_date' => '',
    'event_end_date' => '',
    'event_name' => '',
    'description' => '',
    'notes' => ''
];
?>

<form method="POST" 
      action="<?= $isEdit ? url('/orders/' . $order->id) : url('/orders') ?>" 
      id="orderForm">
    <?= csrfField() ?>
    <?php if ($isEdit): ?>
    <?= methodField('PUT') ?>
    <?php endif; ?>
    
    <div class="row">
        <!-- Left Column - Order Info -->
        <div class="col-12 col-lg-8">
            <div class="glass-card mb-4">
                <h4 class="card-title mb-4">
                    <i class="fas fa-info-circle text-primary me-2"></i>
                    Informasi Pesanan
                </h4>
                
                <div class="row g-3">
                    <div class="col-6 col-md-3">
                        <div class="form-group">
                            <label class="form-label required">Tgl Pesanan</label>
                            <input type="date" name="order_date" class="form-control" 
                                   value="<?= e(old('order_date', $orderData['order_date'])) ?>" required>
                        </div>
                    </div>
                    <div class="col-6 col-md-3">
                        <div class="form-group">
                            <label class="form-label">Tgl Mulai Event</label>
                            <input type="date" name="event_date" class="form-control" id="eventStartDate"
                                   value="<?= e(old('event_date', $orderData['event_date'])) ?>">
                        </div>
                    </div>
                    <div class="col-6 col-md-3">
                        <div class="form-group">
                            <label class="form-label">Tgl Selesai Event</label>
                            <input type="date" name="event_end_date" class="form-control" id="eventEndDate"
                                   value="<?= e(old('event_end_date', $orderData['event_end_date'] ?? '')) ?>">
                        </div>
                    </div>
                    <div class="col-6 col-md-3">
                        <div class="form-group">
                            <label class="form-label">Durasi Event</label>
                            <div class="input-group">
                                <input type="number" id="numDaysDisplay" class="form-control" value="1" readonly 
                                       style="background: var(--gray-100);">
                                <span class="input-group-text">hari</span>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Tombol Terapkan ke Semua Item -->
                <div class="sync-days-row mt-2">
                    <button type="button" class="btn btn-outline-primary btn-sm" id="syncDaysBtn" title="Terapkan durasi event ke semua item">
                        <i class="fas fa-sync-alt"></i> Terapkan Durasi ke Semua Item
                    </button>
                    <small class="text-muted ms-2">Klik untuk menyamakan jumlah hari semua item dengan durasi event</small>
                </div>
                
                <div class="row g-3 mt-2">
                    <div class="col-12 col-md-6">
                        <div class="form-group">
                            <label class="form-label">Klien</label>
                            <select name="client_id" class="form-control" id="clientSelect">
                                <option value="">-- Pilih Klien --</option>
                                <?php foreach ($clients as $id => $name): ?>
                                <option value="<?= $id ?>" <?= old('client_id', $orderData['client_id']) == $id ? 'selected' : '' ?>>
                                    <?= e($name) ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                            <small class="text-muted">
                                <a href="<?= url('/clients/create') ?>" target="_blank">+ Tambah klien baru</a>
                            </small>
                        </div>
                    </div>
                    <div class="col-12 col-md-6">
                        <div class="form-group">
                            <label class="form-label">Nama Event/Kegiatan</label>
                            <input type="text" name="event_name" class="form-control" 
                                   value="<?= e(old('event_name', $orderData['event_name'])) ?>"
                                   placeholder="Contoh: Tour Bali 3D2N">
                        </div>
                    </div>
                </div>
                
                <div class="form-group mt-3">
                    <label class="form-label">Deskripsi</label>
                    <textarea name="description" class="form-control" rows="2" 
                              placeholder="Deskripsi singkat pesanan"><?= e(old('description', $orderData['description'])) ?></textarea>
                </div>
            </div>
            
            <!-- Items Section -->
            <div class="glass-card">
                <div class="d-flex align-items-center justify-content-between mb-4">
                    <h4 class="card-title mb-0">
                        <i class="fas fa-list text-primary me-2"></i>
                        Item Pesanan
                    </h4>
                    <button type="button" class="btn btn-success btn-sm" id="addItemBtn">
                        <i class="fas fa-plus"></i> Tambah Item
                    </button>
                </div>
                
                <!-- Info box tentang jumlah hari -->
                <div class="alert alert-info mb-3" style="font-size: 0.85rem;">
                    <i class="fas fa-info-circle"></i>
                    <strong>Catatan:</strong> Jumlah hari setiap item dapat diatur berbeda-beda. Gunakan tombol "Terapkan Durasi ke Semua Item" di atas jika ingin menyamakan semua.
                </div>
                
                <div id="itemsContainer">
                    <?php if (!empty($items)): ?>
                        <?php foreach ($items as $index => $item): ?>
                        <div class="item-card" data-index="<?= $index ?>">
                            <div class="item-card-header">
                                <span class="item-number">#<?= $index + 1 ?></span>
                                <button type="button" class="btn btn-sm btn-icon btn-danger remove-item-btn">
                                    <i class="fas fa-times"></i>
                                </button>
                            </div>
                            <div class="item-card-body">
                                <div class="row g-2">
                                    <div class="col-6 col-md-3">
                                        <label class="form-label-sm">Jenis Layanan</label>
                                        <select name="items[<?= $index ?>][item_type]" class="form-control form-control-sm item-type-select">
                                            <?php foreach ($itemTypes as $key => $type): ?>
                                            <option value="<?= $key ?>" <?= $item['item_type'] === $key ? 'selected' : '' ?>>
                                                <?= $type['label'] ?>
                                            </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <div class="col-6 col-md-3">
                                        <label class="form-label-sm">Quantity</label>
                                        <input type="number" name="items[<?= $index ?>][quantity]" 
                                               class="form-control form-control-sm item-qty" 
                                               value="<?= $item['quantity'] ?>" min="1">
                                    </div>
                                    <div class="col-6 col-md-3">
                                        <label class="form-label-sm">Jumlah Hari <i class="fas fa-info-circle text-muted" title="Bisa berbeda per item"></i></label>
                                        <input type="number" name="items[<?= $index ?>][num_days]" 
                                               class="form-control form-control-sm item-days" 
                                               value="<?= $item['num_days'] ?? 1 ?>" min="1">
                                    </div>
                                    <div class="col-6 col-md-3">
                                        <label class="form-label-sm">Harga/Unit/Hari</label>
                                        <input type="text" name="items[<?= $index ?>][base_price]" 
                                               class="form-control form-control-sm item-base-price rupiah-input" 
                                               value="<?= number_format($item['base_price'], 0, ',', '.') ?>"
                                               placeholder="0">
                                    </div>
                                </div>
                                <div class="row g-2 mt-2">
                                    <div class="col-12 col-md-4">
                                        <label class="form-label-sm">Deskripsi</label>
                                        <input type="text" name="items[<?= $index ?>][description]"
                                               class="form-control form-control-sm"
                                               value="<?= e($item['description']) ?>"
                                               placeholder="Nama hotel/maskapai/kendaraan" required>
                                    </div>
                                    <div class="col-3 col-md-2">
                                        <label class="form-label-sm">Cashback/Unit <i class="fas fa-info-circle text-muted" title="Ditambahkan ke Harga/Unit sebelum markup dihitung"></i></label>
                                        <input type="text" name="items[<?= $index ?>][cashback]"
                                               class="form-control form-control-sm item-cashback rupiah-input"
                                               value="<?= number_format($item['cashback'] ?? 0, 0, ',', '.') ?>"
                                               placeholder="0">
                                    </div>
                                    <div class="col-3 col-md-2">
                                        <label class="form-label-sm">Markup</label>
                                        <select name="items[<?= $index ?>][markup_type]" class="form-control form-control-sm item-markup-type">
                                            <option value="percentage" <?= $item['markup_type'] === 'percentage' ? 'selected' : '' ?>>%</option>
                                            <option value="fixed" <?= $item['markup_type'] === 'fixed' ? 'selected' : '' ?>>Rp</option>
                                        </select>
                                    </div>
                                    <div class="col-3 col-md-2">
                                        <label class="form-label-sm">Nilai</label>
                                        <input type="number" name="items[<?= $index ?>][markup_value]"
                                               class="form-control form-control-sm item-markup-value"
                                               value="<?= $item['markup_value'] ?>" min="0">
                                    </div>
                                    <div class="col-3 col-md-2">
                                        <label class="form-label-sm">Final</label>
                                        <input type="text" class="form-control form-control-sm item-final-price"
                                               value="<?= number_format($item['final_price'] ?? 0, 0, ',', '.') ?>" readonly
                                               style="background: var(--gray-100); font-weight: 600;">
                                    </div>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>

                <div id="emptyItemsMessage" class="empty-items <?= !empty($items) ? 'd-none' : '' ?>">
                    <i class="fas fa-box-open"></i>
                    <p>Belum ada item. Klik tombol "Tambah Item" untuk menambahkan.</p>
                </div>
            </div>
        </div>
        
        <!-- Right Column - Summary & Actions -->
        <div class="col-12 col-lg-4">
            <div class="glass-card sticky-top" style="top: 100px;">
                <h4 class="card-title mb-4">
                    <i class="fas fa-calculator text-success me-2"></i>
                    Ringkasan
                </h4>
                
                <div class="summary-rows">
                    <div class="summary-row">
                        <span>Subtotal (Modal)</span>
                        <span id="subtotalDisplay">Rp 0</span>
                    </div>
                    <div class="summary-row">
                        <span>Total Cashback</span>
                        <span id="cashbackDisplay">Rp 0</span>
                    </div>
                    <div class="summary-row">
                        <span>Total Markup</span>
                        <span id="markupDisplay" class="text-success">Rp 0</span>
                    </div>
                    <div class="summary-row total">
                        <span>Total Harga Jual</span>
                        <span id="totalDisplay">Rp 0</span>
                    </div>
                    <div class="summary-row profit">
                        <span>💰 Estimasi Profit</span>
                        <span id="profitDisplay">Rp 0</span>
                    </div>
                </div>
                
                <div class="form-group mt-4">
                    <label class="form-label">Catatan Internal</label>
                    <textarea name="notes" class="form-control" rows="3" 
                              placeholder="Catatan internal (tidak tercetak di invoice)"><?= e(old('notes', $orderData['notes'] ?? '')) ?></textarea>
                </div>
                
                <div class="d-grid gap-2 mt-4">
                    <button type="submit" class="btn btn-primary btn-lg">
                        <i class="fas fa-save"></i> 
                        <?= $isEdit ? 'Update Pesanan' : 'Simpan Pesanan' ?>
                    </button>
                    <a href="<?= url('/orders') ?>" class="btn btn-secondary">
                        <i class="fas fa-times"></i> Batal
                    </a>
                </div>
            </div>
        </div>
    </div>
</form>

<!-- Item Template -->
<template id="itemTemplate">
    <div class="item-card" data-index="__INDEX__">
        <div class="item-card-header">
            <span class="item-number">#__NUMBER__</span>
            <button type="button" class="btn btn-sm btn-icon btn-danger remove-item-btn">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <div class="item-card-body">
            <div class="row g-2">
                <div class="col-6 col-md-3">
                    <label class="form-label-sm">Jenis Layanan</label>
                    <select name="items[__INDEX__][item_type]" class="form-control form-control-sm item-type-select">
                        <?php foreach ($itemTypes as $key => $type): ?>
                        <option value="<?= $key ?>"><?= $type['label'] ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-6 col-md-3">
                    <label class="form-label-sm">Quantity</label>
                    <input type="number" name="items[__INDEX__][quantity]" 
                           class="form-control form-control-sm item-qty" value="1" min="1">
                </div>
                <div class="col-6 col-md-3">
                    <label class="form-label-sm">Jumlah Hari <i class="fas fa-info-circle text-muted" title="Bisa berbeda per item"></i></label>
                    <input type="number" name="items[__INDEX__][num_days]" 
                           class="form-control form-control-sm item-days" value="1" min="1">
                </div>
                <div class="col-6 col-md-3">
                    <label class="form-label-sm">Harga/Unit/Hari</label>
                    <input type="text" name="items[__INDEX__][base_price]" 
                           class="form-control form-control-sm item-base-price rupiah-input" placeholder="0">
                </div>
            </div>
            <div class="row g-2 mt-2">
                <div class="col-12 col-md-4">
                    <label class="form-label-sm">Deskripsi</label>
                    <input type="text" name="items[__INDEX__][description]"
                           class="form-control form-control-sm" placeholder="Nama hotel/maskapai/kendaraan" required>
                </div>
                <div class="col-3 col-md-2">
                    <label class="form-label-sm">Cashback/Unit <i class="fas fa-info-circle text-muted" title="Ditambahkan ke Harga/Unit sebelum markup dihitung"></i></label>
                    <input type="text" name="items[__INDEX__][cashback]"
                           class="form-control form-control-sm item-cashback rupiah-input" placeholder="0">
                </div>
                <div class="col-3 col-md-2">
                    <label class="form-label-sm">Markup</label>
                    <select name="items[__INDEX__][markup_type]" class="form-control form-control-sm item-markup-type">
                        <option value="percentage">%</option>
                        <option value="fixed">Rp</option>
                    </select>
                </div>
                <div class="col-3 col-md-2">
                    <label class="form-label-sm">Nilai</label>
                    <input type="number" name="items[__INDEX__][markup_value]"
                           class="form-control form-control-sm item-markup-value" value="0" min="0">
                </div>
                <div class="col-3 col-md-2">
                    <label class="form-label-sm">Final</label>
                    <input type="text" class="form-control form-control-sm item-final-price"
                           value="0" readonly style="background: var(--gray-100); font-weight: 600;">
                </div>
            </div>
        </div>
    </div>
</template>

<style>
/* Sync days row */
.sync-days-row {
    display: flex;
    align-items: center;
    flex-wrap: wrap;
    gap: 0.5rem;
    padding: 0.75rem;
    background: linear-gradient(135deg, #f0f9ff, #e0f2fe);
    border: 1px solid #bae6fd;
    border-radius: 8px;
}

.sync-days-row .btn {
    white-space: nowrap;
}

/* Summary styles */
.summary-rows {
    border: 1px solid var(--gray-200);
    border-radius: var(--border-radius);
    overflow: hidden;
}

.summary-row {
    display: flex;
    justify-content: space-between;
    padding: 0.8rem 1rem;
    border-bottom: 1px solid var(--gray-200);
    font-size: 0.9rem;
}

.summary-row:last-child {
    border-bottom: none;
}

.summary-row.total {
    background: var(--gray-50);
    font-weight: 700;
    font-size: 1rem;
}

.summary-row.profit {
    background: linear-gradient(135deg, #f0fdf4, #dcfce7);
    color: #166534;
    font-weight: 600;
}

/* Item card styles */
.item-card {
    border: 1px solid var(--gray-200);
    border-radius: var(--border-radius);
    margin-bottom: 1rem;
    overflow: hidden;
    background: white;
}

.item-card-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 0.5rem 1rem;
    background: var(--gray-50);
    border-bottom: 1px solid var(--gray-200);
}

.item-number {
    font-weight: 700;
    color: var(--gray-500);
    font-size: 0.85rem;
}

.item-card-body {
    padding: 1rem;
}

.form-label-sm {
    display: block;
    font-size: 0.75rem;
    font-weight: 600;
    color: var(--gray-600);
    margin-bottom: 0.25rem;
}

.form-label-sm i {
    font-size: 0.65rem;
    cursor: help;
}

/* Alert info styling */
.alert-info {
    background: linear-gradient(135deg, #f0f9ff, #e0f2fe);
    border: 1px solid #bae6fd;
    color: #0369a1;
    padding: 0.75rem 1rem;
    border-radius: 8px;
}

/* Empty items message */
.empty-items {
    text-align: center;
    padding: 2rem;
    color: var(--gray-500);
    border: 2px dashed var(--gray-300);
    border-radius: var(--border-radius);
}

.empty-items i {
    font-size: 2.5rem;
    margin-bottom: 0.5rem;
    opacity: 0.5;
}

/* Rupiah input */
.rupiah-input {
    text-align: right;
}

/* Input group */
.input-group-text {
    font-size: 0.85rem;
    background: var(--gray-100);
}

/* Grid gap */
.d-grid {
    display: grid;
}

.gap-2 {
    gap: 0.5rem;
}

/* Responsive adjustments */
@media (max-width: 991.98px) {
    .sticky-top {
        position: relative !important;
        top: 0 !important;
    }
}

@media (max-width: 767.98px) {
    .item-card-body {
        padding: 0.75rem;
    }
    
    .form-label-sm {
        font-size: 0.7rem;
    }
    
    .sync-days-row {
        flex-direction: column;
        align-items: flex-start;
    }
    
    .sync-days-row small {
        margin-left: 0 !important;
    }
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    var itemIndex = <?= !empty($items) ? count($items) : 0 ?>;
    var container = document.getElementById('itemsContainer');
    var template = document.getElementById('itemTemplate');
    var emptyMessage = document.getElementById('emptyItemsMessage');
    
    // Calculate days from dates (HANYA update display, TIDAK auto-update items)
    function calculateDays() {
        var startDate = document.getElementById('eventStartDate').value;
        var endDate = document.getElementById('eventEndDate').value;
        
        if (startDate && endDate) {
            var start = new Date(startDate);
            var end = new Date(endDate);
            var diffTime = Math.abs(end - start);
            var diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24)) + 1;
            document.getElementById('numDaysDisplay').value = diffDays;
        } else if (startDate && !endDate) {
            document.getElementById('numDaysDisplay').value = 1;
        }
        
        // TIDAK lagi auto-update semua item days
        // User harus klik tombol "Terapkan ke Semua Item" jika mau sync
    }
    
    // Sync days to all items (MANUAL - via tombol)
    function syncDaysToAllItems() {
        var days = parseInt(document.getElementById('numDaysDisplay').value) || 1;
        document.querySelectorAll('.item-days').forEach(function(input) {
            input.value = days;
        });
        calculateTotals();
        
        // Show feedback
        var btn = document.getElementById('syncDaysBtn');
        var originalText = btn.innerHTML;
        btn.innerHTML = '<i class="fas fa-check"></i> Berhasil!';
        btn.classList.remove('btn-outline-primary');
        btn.classList.add('btn-success');
        setTimeout(function() {
            btn.innerHTML = originalText;
            btn.classList.remove('btn-success');
            btn.classList.add('btn-outline-primary');
        }, 1500);
    }
    
    // Event listeners untuk tanggal
    document.getElementById('eventStartDate').addEventListener('change', calculateDays);
    document.getElementById('eventEndDate').addEventListener('change', calculateDays);
    
    // Event listener untuk tombol sync
    document.getElementById('syncDaysBtn').addEventListener('click', syncDaysToAllItems);
    
    // Add item
    document.getElementById('addItemBtn').addEventListener('click', function() {
        var html = template.innerHTML
            .replace(/__INDEX__/g, itemIndex)
            .replace(/__NUMBER__/g, itemIndex + 1);
        
        var div = document.createElement('div');
        div.innerHTML = html;
        var newItem = div.firstElementChild;
        
        container.appendChild(newItem);
        emptyMessage.classList.add('d-none');
        
        // Set days from global HANYA untuk item BARU (sebagai default)
        var days = document.getElementById('numDaysDisplay').value || 1;
        newItem.querySelector('.item-days').value = days;
        
        // Add event listeners
        attachItemListeners(newItem);
        
        itemIndex++;
        updateItemNumbers();
        calculateTotals();
    });
    
    // Remove item
    container.addEventListener('click', function(e) {
        if (e.target.closest('.remove-item-btn')) {
            e.target.closest('.item-card').remove();
            updateItemNumbers();
            calculateTotals();
            
            if (container.children.length === 0) {
                emptyMessage.classList.remove('d-none');
            }
        }
    });
    
    // Update item numbers
    function updateItemNumbers() {
        container.querySelectorAll('.item-card').forEach(function(card, index) {
            card.querySelector('.item-number').textContent = '#' + (index + 1);
        });
    }
    
    // Attach listeners to item
    function attachItemListeners(item) {
        var inputs = item.querySelectorAll('.item-qty, .item-days, .item-base-price, .item-cashback, .item-markup-type, .item-markup-value');
        inputs.forEach(function(input) {
            input.addEventListener('input', calculateTotals);
            input.addEventListener('change', calculateTotals);
        });

        // Rupiah formatting (Harga/Unit dan Cashback/Unit)
        item.querySelectorAll('.rupiah-input').forEach(function(rupiahInput) {
            rupiahInput.addEventListener('input', function() {
                var value = this.value.replace(/\D/g, '');
                this.value = new Intl.NumberFormat('id-ID').format(value);
            });
        });
    }
    
    // Initial listeners
    document.querySelectorAll('.item-card').forEach(attachItemListeners);
    
    // Calculate totals
    function calculateTotals() {
        var subtotal = 0;
        var totalCashback = 0;
        var totalMarkup = 0;
        var total = 0;

        document.querySelectorAll('.item-card').forEach(function(item) {
            var qty = parseInt(item.querySelector('.item-qty').value) || 0;
            var days = parseInt(item.querySelector('.item-days').value) || 1;
            var basePriceStr = item.querySelector('.item-base-price').value.replace(/\./g, '').replace(/,/g, '');
            var basePrice = parseFloat(basePriceStr) || 0;
            var cashbackStr = item.querySelector('.item-cashback').value.replace(/\./g, '').replace(/,/g, '');
            var cashback = parseFloat(cashbackStr) || 0;
            var markupType = item.querySelector('.item-markup-type').value;
            var markupValue = parseFloat(item.querySelector('.item-markup-value').value) || 0;

            // Markup dihitung dari (Harga/Unit + Cashback/Unit), bukan Harga/Unit saja
            var itemBase = qty * days * basePrice;
            var itemCashback = qty * days * cashback;
            var markupBase = itemBase + itemCashback;
            var markup = 0;

            if (markupType === 'percentage') {
                markup = markupBase * (markupValue / 100);
            } else {
                markup = markupValue * qty * days;
            }

            var itemFinal = markupBase + markup;

            item.querySelector('.item-final-price').value = new Intl.NumberFormat('id-ID').format(itemFinal);

            subtotal += itemBase;
            totalCashback += itemCashback;
            totalMarkup += markup;
            total += itemFinal;
        });

        document.getElementById('subtotalDisplay').textContent = 'Rp ' + new Intl.NumberFormat('id-ID').format(subtotal);
        document.getElementById('cashbackDisplay').textContent = 'Rp ' + new Intl.NumberFormat('id-ID').format(totalCashback);
        document.getElementById('markupDisplay').textContent = 'Rp ' + new Intl.NumberFormat('id-ID').format(totalMarkup);
        document.getElementById('totalDisplay').textContent = 'Rp ' + new Intl.NumberFormat('id-ID').format(total);
        document.getElementById('profitDisplay').textContent = 'Rp ' + new Intl.NumberFormat('id-ID').format(totalMarkup);
    }
    
    // Initial calculation
    calculateDays();
    calculateTotals();
});
</script>