<?php
/**
 * Invoice Detail View
 */
$statusColors = ['unpaid' => 'danger', 'partial' => 'warning', 'paid' => 'success'];
$statusLabels = ['unpaid' => 'Belum Bayar', 'partial' => 'Sebagian', 'paid' => 'Lunas'];
$totalPaid = (float) $invoice->paid_amount;
$totalAmount = (float) $invoice->total;
$percentPaid = $totalAmount > 0 ? ($totalPaid / $totalAmount) * 100 : 0;
?>

<div class="row">
    <!-- Left: Items + Payment History -->
    <div class="col-12 col-lg-7">
        <div class="glass-card mb-4">
            <div class="d-flex align-items-center justify-content-between mb-3">
                <h4 class="card-title mb-0">
                    <i class="fas fa-file-invoice-dollar text-primary"></i>
                    <?= e($invoice->invoice_number) ?>
                </h4>
                <span class="badge badge-<?= $statusColors[$invoice->payment_status] ?? 'secondary' ?>">
                    <?= $statusLabels[$invoice->payment_status] ?? $invoice->payment_status ?>
                </span>
            </div>

            <div class="table-responsive mb-3">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Item</th>
                            <th width="80">Qty</th>
                            <th width="150">Harga Satuan</th>
                            <th width="150">Subtotal</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($items as $item): ?>
                        <tr>
                            <td><?= e($item['name']) ?></td>
                            <td><?= (int)$item['quantity'] ?></td>
                            <td><?= formatRupiah($item['unit_price']) ?></td>
                            <td><?= formatRupiah($item['subtotal']) ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                    <tfoot>
                        <tr>
                            <td colspan="3" class="text-end">Subtotal</td>
                            <td><?= formatRupiah($invoice->subtotal) ?></td>
                        </tr>
                        <tr>
                            <td colspan="3" class="text-end">Diskon</td>
                            <td>- <?= formatRupiah($invoice->discount) ?></td>
                        </tr>
                        <tr class="table-light">
                            <td colspan="3" class="text-end"><strong>Total</strong></td>
                            <td><strong><?= formatRupiah($invoice->total) ?></strong></td>
                        </tr>
                    </tfoot>
                </table>
            </div>

            <?php if ($invoice->notes): ?>
            <p class="text-muted mb-0"><i class="fas fa-sticky-note"></i> <?= nl2br(e($invoice->notes)) ?></p>
            <?php endif; ?>
        </div>

        <div class="glass-card">
            <h4 class="card-title mb-4">
                <i class="fas fa-history text-primary me-2"></i>
                Riwayat Pembayaran
            </h4>

            <?php if (empty($payments)): ?>
            <div class="text-center text-muted py-4">
                <i class="fas fa-inbox fa-3x mb-3 opacity-50"></i>
                <p>Belum ada pembayaran tercatat</p>
            </div>
            <?php else: ?>
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>Tanggal</th>
                            <th>Jumlah</th>
                            <th>Metode</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $methodIcons = ['transfer' => '🏦', 'ewallet' => '📱', 'credit_card' => '💳', 'cash' => '💵', 'other' => '📝'];
                        $methodLabels = ['transfer' => 'Transfer', 'ewallet' => 'E-Wallet', 'credit_card' => 'Kartu Kredit', 'cash' => 'Tunai', 'other' => 'Lainnya'];
                        ?>
                        <?php foreach ($payments as $payment): ?>
                        <tr>
                            <td>
                                <?= formatDate($payment['payment_date'], 'd/m/Y') ?>
                                <br><small class="text-muted"><?= e($payment['reference'] ?? '-') ?></small>
                            </td>
                            <td class="text-success fw-bold"><?= formatRupiah($payment['amount']) ?></td>
                            <td>
                                <?= $methodIcons[$payment['payment_method']] ?? '📝' ?>
                                <?= $methodLabels[$payment['payment_method']] ?? $payment['payment_method'] ?>
                                <?php if (!empty($payment['proof_image'])): ?>
                                <br><a href="<?= uploadUrl('payment-proofs/' . $payment['proof_image']) ?>" target="_blank" class="small">
                                    <i class="fas fa-image"></i> Lihat Bukti
                                </a>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Right: Payment Form -->
    <div class="col-12 col-lg-5">
        <div class="glass-card mb-4">
            <div class="alert alert-info mb-3">
                <div class="d-flex justify-content-between mb-2">
                    <span>Total Tagihan:</span>
                    <strong><?= formatRupiah($totalAmount) ?></strong>
                </div>
                <div class="d-flex justify-content-between mb-2">
                    <span>Sudah Dibayar:</span>
                    <strong class="text-success"><?= formatRupiah($totalPaid) ?></strong>
                </div>
                <hr class="my-2">
                <div class="d-flex justify-content-between">
                    <span>Sisa Tagihan:</span>
                    <strong class="text-<?= $remainingAmount > 0 ? 'danger' : 'success' ?>">
                        <?= formatRupiah($remainingAmount) ?>
                    </strong>
                </div>
                <div class="progress mt-3" style="height: 10px;">
                    <div class="progress-bar bg-success" style="width: <?= min(100, $percentPaid) ?>%"></div>
                </div>
            </div>

            <?php if ($remainingAmount > 0): ?>
            <h5 class="mb-3">Catat Pembayaran</h5>
            <form method="POST" action="<?= url('/invoices/' . $invoice->id . '/payment') ?>" id="invoicePaymentForm" enctype="multipart/form-data">
                <?= csrfField() ?>

                <div class="form-group mb-3">
                    <label class="form-label required">Tanggal Pembayaran</label>
                    <input type="date" name="payment_date" class="form-control" value="<?= date('Y-m-d') ?>" required>
                </div>

                <div class="form-group mb-3">
                    <label class="form-label required">Jumlah Bayar</label>
                    <input type="text" name="amount" id="invoiceAmountInput" class="form-control"
                           value="<?= number_format($remainingAmount, 0, ',', '.') ?>" required>
                    <small class="text-muted">
                        <a href="#" onclick="document.getElementById('invoiceAmountInput').value='<?= number_format($remainingAmount, 0, ',', '.') ?>'; return false;">[Bayar Penuh]</a>
                    </small>
                </div>

                <div class="form-group mb-3">
                    <label class="form-label required">Metode Pembayaran</label>
                    <select name="payment_method" class="form-control" required>
                        <option value="transfer">Transfer Bank</option>
                        <option value="ewallet">E-Wallet</option>
                        <option value="credit_card">Kartu Kredit</option>
                        <option value="cash">Tunai / Kas</option>
                        <option value="other">Lainnya</option>
                    </select>
                </div>

                <div class="form-group mb-3">
                    <label class="form-label">Kas/Bank Penerima</label>
                    <select name="bank_cash_id" class="form-control">
                        <option value="">-- Pilih Kas/Bank --</option>
                        <?php foreach ($bankCashOptions as $bc): ?>
                        <option value="<?= $bc['id'] ?>"><?= e($bc['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group mb-3">
                    <label class="form-label">No. Bukti/Referensi</label>
                    <input type="text" name="reference" class="form-control" placeholder="No. transfer, kwitansi, dll">
                </div>

                <div class="form-group mb-3">
                    <label class="form-label">Bukti Pembayaran</label>
                    <input type="file" name="proof_image" class="form-control" accept="image/*">
                </div>

                <div class="form-group mb-3">
                    <label class="form-label">Catatan</label>
                    <input type="text" name="notes" class="form-control" placeholder="Catatan tambahan">
                </div>

                <button type="submit" class="btn btn-success w-100">
                    <i class="fas fa-check"></i> Simpan Pembayaran
                </button>
            </form>
            <?php else: ?>
            <div class="alert alert-success mb-0">
                <i class="fas fa-check-circle me-2"></i>
                <strong>Lunas!</strong> Invoice ini sudah dibayar penuh.
            </div>
            <?php endif; ?>
        </div>

        <div class="glass-card">
            <h5 class="card-title mb-3">Info Order Terkait</h5>
            <table class="table table-sm table-borderless mb-0">
                <tr>
                    <td class="text-muted" width="40%">No. Order</td>
                    <td><a href="<?= url('/orders/' . $order->id) ?>"><?= e($order->order_number) ?></a></td>
                </tr>
                <tr>
                    <td class="text-muted">Klien</td>
                    <td><?= e($client->name ?? '-') ?></td>
                </tr>
                <tr>
                    <td class="text-muted">Jatuh Tempo</td>
                    <td><?= $invoice->due_date ? formatDate($invoice->due_date) : '-' ?></td>
                </tr>
            </table>
        </div>
    </div>
</div>

<script>
document.getElementById('invoiceAmountInput')?.addEventListener('input', function() {
    let value = this.value.replace(/\D/g, '');
    if (value) this.value = parseInt(value).toLocaleString('id-ID');
});

document.getElementById('invoicePaymentForm')?.addEventListener('submit', function() {
    const input = document.getElementById('invoiceAmountInput');
    input.value = input.value.replace(/\D/g, '');
});
</script>
