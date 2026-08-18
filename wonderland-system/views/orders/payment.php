<?php
/**
 * Order Payment Form
 * File: /views/orders/payment.php
 */

// Get data from controller
$orderArray = $order->toArray();
$remainingAmount = function_exists('getOrderRemainingAmount') 
    ? getOrderRemainingAmount($orderArray) 
    : ((float)($orderArray['total_final_price'] ?? 0) - (float)($orderArray['paid_amount'] ?? 0));

$totalPaid = (float)($orderArray['paid_amount'] ?? 0);
$totalPrice = (float)($orderArray['total_final_price'] ?? 0);
$percentPaid = $totalPrice > 0 ? ($totalPaid / $totalPrice) * 100 : 0;

// $payments dan $bankCashOptions disediakan dari controller
$payments = $payments ?? [];
$bankCashOptions = $bankCashOptions ?? [];
?>

<div class="row">
    <!-- Left Column - Payment Form -->
    <div class="col-12 col-lg-6">
        <div class="glass-card mb-4">
            <h4 class="card-title mb-4">
                <i class="fas fa-money-bill-wave text-success me-2"></i>
                Catat Pembayaran
            </h4>
            
            <!-- Order Summary -->
            <div class="alert alert-info mb-4">
                <div class="d-flex justify-content-between mb-2">
                    <span>Total Tagihan:</span>
                    <strong><?= formatRupiah($totalPrice) ?></strong>
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
                
                <!-- Progress Bar -->
                <div class="progress mt-3" style="height: 10px;">
                    <div class="progress-bar bg-success" style="width: <?= min(100, $percentPaid) ?>%"></div>
                </div>
                <small class="text-muted"><?= number_format($percentPaid, 1) ?>% terbayar</small>
            </div>
            
            <?php if ($remainingAmount > 0): ?>
            <form method="POST" action="<?= url('/orders/' . $order->id . '/payment') ?>" id="paymentForm" enctype="multipart/form-data">
                <?= csrfField() ?>
                
                <div class="row g-3">
                    <div class="col-12 col-md-6">
                        <div class="form-group">
                            <label class="form-label required">Tanggal Pembayaran</label>
                            <input type="date" name="payment_date" class="form-control" 
                                   value="<?= date('Y-m-d') ?>" required>
                        </div>
                    </div>
                    
                    <div class="col-12 col-md-6">
                        <div class="form-group">
                            <label class="form-label required">Jumlah Bayar</label>
                            <input type="text" name="amount" id="amountInput" class="form-control" 
                                   value="<?= number_format($remainingAmount, 0, ',', '.') ?>" 
                                   required>
                            <small class="text-muted">
                                Maks: <?= formatRupiah($remainingAmount) ?>
                                <a href="#" class="ms-2" onclick="setFullAmount(); return false;">
                                    [Bayar Penuh]
                                </a>
                            </small>
                        </div>
                    </div>
                </div>
                
                <div class="row g-3 mt-2">
                    <div class="col-12 col-md-6">
                        <div class="form-group">
                            <label class="form-label required">Metode Pembayaran</label>
                            <select name="payment_method" class="form-control" required>
                                <option value="transfer">Transfer Bank</option>
                                <option value="ewallet">E-Wallet</option>
                                <option value="credit_card">Kartu Kredit</option>
                                <option value="cash">Tunai / Kas</option>
                                <option value="other">Lainnya</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="col-12 col-md-6">
                        <div class="form-group">
                            <label class="form-label">Kas/Bank Penerima</label>
                            <select name="bank_cash_id" class="form-control">
                                <option value="">-- Pilih Kas/Bank --</option>
                                <?php foreach ($bankCashOptions as $id => $name): ?>
                                <option value="<?= $id ?>"><?= e($name) ?></option>
                                <?php endforeach; ?>
                            </select>
                            <small class="text-muted">Opsional - untuk update saldo</small>
                        </div>
                    </div>
                </div>
                
                <div class="row g-3 mt-2">
                    <div class="col-12 col-md-6">
                        <div class="form-group">
                            <label class="form-label">No. Bukti/Referensi</label>
                            <input type="text" name="reference" class="form-control" 
                                   placeholder="No. transfer, kwitansi, dll">
                        </div>
                    </div>
                    
                    <div class="col-12 col-md-6">
                        <div class="form-group">
                            <label class="form-label">Catatan</label>
                            <input type="text" name="notes" class="form-control"
                                   placeholder="Catatan tambahan">
                        </div>
                    </div>
                </div>

                <div class="row g-3 mt-2">
                    <div class="col-12">
                        <div class="form-group">
                            <label class="form-label">Bukti Pembayaran</label>
                            <input type="file" name="proof_image" class="form-control" accept="image/*">
                            <small class="text-muted">Opsional - screenshot/foto bukti transfer</small>
                        </div>
                    </div>
                </div>

                <?php
                $accountingReady = function_exists('createOrderPaymentJournal')
                    && function_exists('getAccountingSetting')
                    && getAccountingSetting(Session::companyId(), 'account_receivable');
                ?>
                <?php if ($accountingReady): ?>
                <!-- Auto Journal Notice -->
                <div class="alert alert-warning mt-4 mb-3">
                    <i class="fas fa-magic me-2"></i>
                    <strong>Jurnal Otomatis:</strong> Sistem akan membuat jurnal:
                    <ul class="mb-0 mt-2 small">
                        <li><strong>Debit:</strong> Kas/Bank</li>
                        <li><strong>Credit:</strong> Piutang Usaha</li>
                    </ul>
                </div>
                <?php else: ?>
                <div class="alert alert-secondary mt-4 mb-3">
                    <i class="fas fa-info-circle me-2"></i>
                    Pembayaran akan dicatat langsung tanpa jurnal akuntansi (Akuntansi belum diaktifkan).
                </div>
                <?php endif; ?>
                
                <div class="d-flex gap-2">
                    <button type="submit" class="btn btn-success">
                        <i class="fas fa-check"></i> Simpan Pembayaran
                    </button>
                    <a href="<?= url('/orders/' . $order->id) ?>" class="btn btn-secondary">
                        <i class="fas fa-arrow-left"></i> Kembali
                    </a>
                </div>
            </form>
            <?php else: ?>
            <div class="alert alert-success">
                <i class="fas fa-check-circle me-2"></i>
                <strong>Lunas!</strong> Order ini sudah dibayar penuh.
            </div>
            <a href="<?= url('/orders/' . $order->id) ?>" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i> Kembali ke Detail Order
            </a>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- Right Column - Payment History -->
    <div class="col-12 col-lg-6">
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
                            <th>Jurnal</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($payments as $payment): ?>
                        <tr>
                            <td>
                                <?= formatDate($payment['payment_date'], 'd/m/Y') ?>
                                <br>
                                <small class="text-muted"><?= e($payment['reference'] ?? '-') ?></small>
                            </td>
                            <td class="text-success fw-bold">
                                <?= formatRupiah($payment['amount']) ?>
                            </td>
                            <td>
                                <?php
                                $methodIcons = ['transfer' => '🏦', 'ewallet' => '📱', 'credit_card' => '💳', 'cash' => '💵', 'other' => '📝'];
                                $methodLabels = ['transfer' => 'Transfer', 'ewallet' => 'E-Wallet', 'credit_card' => 'Kartu Kredit', 'cash' => 'Tunai', 'other' => 'Lainnya'];
                                ?>
                                <?= $methodIcons[$payment['payment_method']] ?? '📝' ?>
                                <?= $methodLabels[$payment['payment_method']] ?? $payment['payment_method'] ?>
                                <?php if (!empty($payment['bank_cash_name'])): ?>
                                <br><small class="text-muted"><?= e($payment['bank_cash_name']) ?></small>
                                <?php endif; ?>
                                <?php if (!empty($payment['proof_image'])): ?>
                                <br><a href="<?= uploadUrl('payment-proofs/' . $payment['proof_image']) ?>" target="_blank" class="small">
                                    <i class="fas fa-image"></i> Lihat Bukti
                                </a>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if (!empty($payment['journal_number'])): ?>
                                <a href="<?= url('/accounting/journals/' . $payment['journal_id']) ?>" 
                                   class="text-primary" title="Lihat Jurnal">
                                    <?= e($payment['journal_number']) ?>
                                </a>
                                <?php else: ?>
                                <span class="text-muted">-</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <form method="POST" action="<?= url('/orders/' . $order->id . '/payment/' . $payment['id'] . '/void') ?>"
                                      onsubmit="return confirm('Yakin batalkan pembayaran ini?');">
                                    <?= csrfField() ?>
                                    <button type="submit" class="btn btn-sm btn-outline-danger" title="Batalkan">
                                        <i class="fas fa-times"></i>
                                    </button>
                                </form>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                    <tfoot>
                        <tr class="table-light">
                            <td><strong>Total Dibayar</strong></td>
                            <td class="text-success fw-bold" colspan="4">
                                <?= formatRupiah($totalPaid) ?>
                            </td>
                        </tr>
                    </tfoot>
                </table>
            </div>
            <?php endif; ?>
        </div>
        
        <!-- Order Info -->
        <div class="glass-card mt-4">
            <h5 class="card-title mb-3">
                <i class="fas fa-file-invoice text-primary me-2"></i>
                Info Order
            </h5>
            <table class="table table-sm table-borderless mb-0">
                <tr>
                    <td class="text-muted" width="40%">No. Order</td>
                    <td><strong><?= e($order->order_number) ?></strong></td>
                </tr>
                <tr>
                    <td class="text-muted">Klien</td>
                    <td><?= e($client->name ?? '-') ?></td>
                </tr>
                <tr>
                    <td class="text-muted">Event</td>
                    <td><?= e($order->event_name ?: '-') ?></td>
                </tr>
                <tr>
                    <td class="text-muted">Status Bayar</td>
                    <td>
                        <?php
                        $statusColors = ['unpaid' => 'danger', 'partial' => 'warning', 'paid' => 'success'];
                        $statusLabels = ['unpaid' => 'Belum Bayar', 'partial' => 'Sebagian', 'paid' => 'Lunas'];
                        ?>
                        <span class="badge bg-<?= $statusColors[$order->payment_status] ?? 'secondary' ?>">
                            <?= $statusLabels[$order->payment_status] ?? $order->payment_status ?>
                        </span>
                    </td>
                </tr>
            </table>
        </div>
    </div>
</div>

<script>
// Format currency input
document.getElementById('amountInput')?.addEventListener('input', function(e) {
    let value = this.value.replace(/\D/g, '');
    if (value) {
        this.value = parseInt(value).toLocaleString('id-ID');
    }
});

// Set full amount
function setFullAmount() {
    const maxAmount = <?= $remainingAmount ?>;
    document.getElementById('amountInput').value = maxAmount.toLocaleString('id-ID');
}

// Convert to number before submit
document.getElementById('paymentForm')?.addEventListener('submit', function(e) {
    const amountInput = document.getElementById('amountInput');
    const value = amountInput.value.replace(/\D/g, '');
    const maxAmount = <?= $remainingAmount ?>;
    
    if (parseInt(value) > maxAmount) {
        e.preventDefault();
        alert('Jumlah pembayaran tidak boleh melebihi sisa tagihan!');
        return false;
    }
    
    amountInput.value = value;
});
</script>
