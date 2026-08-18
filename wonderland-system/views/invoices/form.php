<?php
/**
 * Invoice Create Form - pre-filled from an order's items
 */
?>

<div class="row">
    <div class="col-12 col-lg-8">
        <div class="glass-card mb-4">
            <div class="alert alert-info mb-4">
                <strong>Order:</strong> <?= e($order->order_number) ?> &middot;
                <strong>Klien:</strong> <?= e($client->name ?? '-') ?>
            </div>

            <form method="POST" action="<?= url('/invoices') ?>" id="invoiceForm">
                <?= csrfField() ?>
                <input type="hidden" name="order_id" value="<?= $order->id ?>">

                <div class="row g-3">
                    <div class="col-12 col-md-6">
                        <div class="form-group">
                            <label class="form-label required">Tanggal Invoice</label>
                            <input type="date" name="invoice_date" class="form-control" value="<?= date('Y-m-d') ?>" required>
                        </div>
                    </div>
                    <div class="col-12 col-md-6">
                        <div class="form-group">
                            <label class="form-label">Jatuh Tempo</label>
                            <input type="date" name="due_date" class="form-control" value="<?= e($dueDate) ?>">
                        </div>
                    </div>
                </div>

                <h5 class="mt-4 mb-3">Item Invoice</h5>
                <div class="table-responsive">
                    <table class="table" id="itemsTable">
                        <thead>
                            <tr>
                                <th>Nama Item</th>
                                <th width="100">Qty</th>
                                <th width="180">Harga Satuan</th>
                                <th width="180">Subtotal</th>
                                <th width="40"></th>
                            </tr>
                        </thead>
                        <tbody id="itemsBody">
                            <?php $rowIndex = 0; foreach ($prefillItems as $item): ?>
                            <tr class="item-row">
                                <td><input type="text" name="invoice_items[<?= $rowIndex ?>][name]" class="form-control" value="<?= e($item['name']) ?>" required></td>
                                <td><input type="number" name="invoice_items[<?= $rowIndex ?>][quantity]" class="form-control item-qty" value="<?= (int)$item['quantity'] ?>" min="1" required></td>
                                <td><input type="text" name="invoice_items[<?= $rowIndex ?>][unit_price]" class="form-control item-price" value="<?= number_format($item['unit_price'], 0, ',', '.') ?>" required></td>
                                <td><span class="item-subtotal"><?= formatRupiah($item['quantity'] * $item['unit_price']) ?></span></td>
                                <td><button type="button" class="btn-delete" onclick="removeItemRow(this)"><i class="fas fa-times"></i></button></td>
                            </tr>
                            <?php $rowIndex++; endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <button type="button" class="btn btn-sm btn-secondary mb-3" onclick="addItemRow()">
                    <i class="fas fa-plus"></i> Tambah Item
                </button>

                <div class="row g-3 mt-2">
                    <div class="col-12 col-md-6">
                        <div class="form-group">
                            <label class="form-label">Diskon</label>
                            <input type="text" name="discount" id="discountInput" class="form-control" value="0">
                        </div>
                    </div>
                    <div class="col-12 col-md-6">
                        <div class="form-group">
                            <label class="form-label">Catatan</label>
                            <input type="text" name="notes" class="form-control" placeholder="Catatan tambahan">
                        </div>
                    </div>
                </div>

                <div class="d-flex gap-2 mt-4">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-check"></i> Buat Invoice
                    </button>
                    <a href="<?= url('/orders/' . $order->id) ?>" class="btn btn-secondary">
                        <i class="fas fa-arrow-left"></i> Batal
                    </a>
                </div>
            </form>
        </div>
    </div>

    <div class="col-12 col-lg-4">
        <div class="glass-card">
            <h5 class="card-title mb-3">Ringkasan</h5>
            <table class="table table-sm table-borderless mb-0">
                <tr>
                    <td>Subtotal</td>
                    <td class="text-end"><strong id="summarySubtotal">Rp 0</strong></td>
                </tr>
                <tr>
                    <td>Diskon</td>
                    <td class="text-end"><strong id="summaryDiscount">Rp 0</strong></td>
                </tr>
                <tr class="border-top">
                    <td>Total</td>
                    <td class="text-end"><strong id="summaryTotal" class="text-primary">Rp 0</strong></td>
                </tr>
            </table>
        </div>
    </div>
</div>

<script>
let itemRowIndex = <?= $rowIndex ?>;

function formatRupiahJs(num) {
    return 'Rp ' + Math.round(num).toLocaleString('id-ID');
}

function parseNum(value) {
    return parseFloat(String(value).replace(/[^\d]/g, '')) || 0;
}

function addItemRow() {
    const tbody = document.getElementById('itemsBody');
    const idx = itemRowIndex++;
    const row = document.createElement('tr');
    row.className = 'item-row';
    row.innerHTML = `
        <td><input type="text" name="invoice_items[${idx}][name]" class="form-control" required></td>
        <td><input type="number" name="invoice_items[${idx}][quantity]" class="form-control item-qty" value="1" min="1" required></td>
        <td><input type="text" name="invoice_items[${idx}][unit_price]" class="form-control item-price" value="0" required></td>
        <td><span class="item-subtotal">Rp 0</span></td>
        <td><button type="button" class="btn-delete" onclick="removeItemRow(this)"><i class="fas fa-times"></i></button></td>
    `;
    tbody.appendChild(row);
    bindRow(row);
    recalculate();
}

function removeItemRow(btn) {
    btn.closest('tr').remove();
    recalculate();
}

function bindRow(row) {
    const qty = row.querySelector('.item-qty');
    const price = row.querySelector('.item-price');
    [qty, price].forEach(input => input.addEventListener('input', recalculate));
}

function recalculate() {
    let subtotal = 0;
    document.querySelectorAll('.item-row').forEach(row => {
        const qty = parseNum(row.querySelector('.item-qty').value);
        const price = parseNum(row.querySelector('.item-price').value);
        const rowSubtotal = qty * price;
        row.querySelector('.item-subtotal').textContent = formatRupiahJs(rowSubtotal);
        subtotal += rowSubtotal;
    });

    const discount = parseNum(document.getElementById('discountInput').value);
    const total = Math.max(0, subtotal - discount);

    document.getElementById('summarySubtotal').textContent = formatRupiahJs(subtotal);
    document.getElementById('summaryDiscount').textContent = formatRupiahJs(discount);
    document.getElementById('summaryTotal').textContent = formatRupiahJs(total);
}

document.querySelectorAll('.item-row').forEach(bindRow);
document.getElementById('discountInput').addEventListener('input', recalculate);
recalculate();
</script>
