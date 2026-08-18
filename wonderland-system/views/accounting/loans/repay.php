<?php
/**
 * Form Bayar Pinjaman
 * File: /views/accounting/loans/repay.php
 * 
 * FITUR: Pilih dari daftar pinjaman yang sudah ada
 */

$bankCashOptions = $bankCashOptions ?? [];
$outstandingLoans = $outstandingLoans ?? [];
$loanBalance = $loanBalance ?? 0;

// Old input atau dari URL parameter
$old = Session::get('_old_input') ?: [];
$selectedLender = $old['lender_name'] ?? ($_GET['lender'] ?? '');
$prefilledAmount = $old['amount'] ?? ($_GET['amount'] ?? '');
?>

<div class="row">
    <div class="col-lg-7">
        <div class="glass-card">
            
            <?php if (empty($outstandingLoans)): ?>
            <!-- Tidak ada pinjaman yang perlu dibayar -->
            <div class="empty-state">
                <div class="empty-state-icon">
                    <i class="fas fa-check-circle text-success"></i>
                </div>
                <h4 class="empty-state-title">Tidak Ada Hutang Pinjaman</h4>
                <p class="empty-state-text">Semua pinjaman sudah lunas atau belum ada pinjaman yang dicatat.</p>
                <a href="<?= url('/loans') ?>" class="btn btn-primary">
                    <i class="fas fa-arrow-left"></i> Kembali
                </a>
            </div>
            
            <?php else: ?>
            
            <!-- Info Total Hutang -->
            <div class="alert alert-warning mb-4">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <i class="fas fa-exclamation-triangle me-1"></i>
                        <strong>Total Sisa Hutang Pinjaman</strong>
                    </div>
                    <div class="h5 mb-0"><?= formatRupiah($loanBalance) ?></div>
                </div>
            </div>
            
            <form method="POST" action="<?= url('/loans/repay') ?>" id="repayForm">
                <?= csrfField() ?>
                
                <!-- Pilih Pinjaman yang Mau Dibayar -->
                <div class="form-group">
                    <label class="form-label required">Pilih Pinjaman yang Dibayar</label>
                    <div class="loan-list">
                        <?php foreach ($outstandingLoans as $idx => $loan): ?>
                        <label class="loan-item <?= $selectedLender === $loan['lender_name'] ? 'selected' : '' ?>">
                            <input type="radio" 
                                   name="lender_name" 
                                   value="<?= e($loan['lender_name']) ?>"
                                   data-outstanding="<?= $loan['outstanding'] ?>"
                                   <?= $selectedLender === $loan['lender_name'] ? 'checked' : '' ?>
                                   required>
                            <div class="loan-info">
                                <div class="loan-name">
                                    <i class="fas fa-user"></i>
                                    <?= e($loan['lender_name']) ?>
                                </div>
                                <div class="loan-details">
                                    <span>Pinjaman: <?= formatRupiah($loan['total_received']) ?></span>
                                    <span class="ms-2">Dibayar: <?= formatRupiah($loan['total_paid']) ?></span>
                                </div>
                            </div>
                            <div class="loan-outstanding">
                                <small>Sisa Hutang</small>
                                <strong><?= formatRupiah($loan['outstanding']) ?></strong>
                            </div>
                        </label>
                        <?php endforeach; ?>
                    </div>
                </div>
                
                <hr>
                
                <div class="form-group">
                    <label class="form-label required">Tanggal Pembayaran</label>
                    <input type="date" 
                           name="transaction_date" 
                           class="form-control" 
                           value="<?= e($old['transaction_date'] ?? date('Y-m-d')) ?>"
                           required>
                </div>
                
                <div class="form-group">
                    <label class="form-label required">Jumlah Pembayaran</label>
                    <div class="input-group">
                        <span class="input-group-text">Rp</span>
                        <input type="text" 
                               name="amount" 
                               id="amountInput"
                               class="form-control text-end" 
                               placeholder="0"
                               value="<?= e($prefilledAmount) ?>"
                               required>
                    </div>
                    <div class="d-flex justify-content-between mt-2">
                        <small class="text-muted">Sisa hutang: <strong id="outstandingDisplay">Rp 0</strong></small>
                        <a href="#" id="payFullBtn" class="small text-success" style="display:none;">
                            <i class="fas fa-check-circle"></i> Bayar Lunas
                        </a>
                    </div>
                </div>
                
                <div class="form-group">
                    <label class="form-label required">Bayar dari Kas/Bank</label>
                    <select name="bank_cash_id" class="form-control" required>
                        <option value="">-- Pilih Kas/Bank Sumber --</option>
                        <?php foreach ($bankCashOptions as $id => $name): ?>
                            <option value="<?= $id ?>" <?= ($old['bank_cash_id'] ?? '') == $id ? 'selected' : '' ?>>
                                <?= e($name) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <?php if (empty($bankCashOptions)): ?>
                        <div class="alert alert-warning mt-2 mb-0">
                            <i class="fas fa-exclamation-triangle me-1"></i>
                            <strong>Belum ada Kas/Bank.</strong>
                            <a href="<?= url('/accounting/bank-cash/create') ?>">Tambah Kas/Bank terlebih dahulu</a>
                        </div>
                    <?php endif; ?>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Catatan</label>
                    <textarea name="notes" 
                              class="form-control" 
                              rows="2" 
                              placeholder="Catatan tambahan (opsional)"><?= e($old['notes'] ?? '') ?></textarea>
                </div>
                
                <div class="d-flex gap-3 mt-4">
                    <button type="submit" class="btn btn-warning" <?= empty($bankCashOptions) ? 'disabled' : '' ?>>
                        <i class="fas fa-money-bill-wave me-1"></i> Bayar Pinjaman
                    </button>
                    <a href="<?= url('/loans') ?>" class="btn btn-secondary">
                        Batal
                    </a>
                </div>
            </form>
            <?php endif; ?>
        </div>
    </div>
    
    <div class="col-lg-5">
        <div class="glass-card">
            <h5 class="mb-3">
                <i class="fas fa-book text-primary me-2"></i>Preview Jurnal
            </h5>
            
            <div class="table-responsive">
                <table class="table table-sm table-bordered mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Akun</th>
                            <th class="text-end" width="120">Debit</th>
                            <th class="text-end" width="120">Kredit</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>Hutang Pinjaman Perorangan</td>
                            <td class="text-end text-success fw-bold" id="previewDebit">Rp 0</td>
                            <td class="text-end text-muted">-</td>
                        </tr>
                        <tr>
                            <td>Kas/Bank</td>
                            <td class="text-end text-muted">-</td>
                            <td class="text-end text-danger fw-bold" id="previewCredit">Rp 0</td>
                        </tr>
                    </tbody>
                </table>
            </div>
            
            <div class="bg-light rounded p-3 mt-3">
                <div class="d-flex align-items-start">
                    <i class="fas fa-info-circle text-info mt-1" style="margin-right: 10px;"></i>
                    <div class="small">
                        <strong class="d-block mb-1">Keterangan:</strong>
                        Saat membayar pinjaman, Hutang akan <span class="text-success fw-bold">berkurang</span> 
                        dan saldo Kas/Bank akan <span class="text-danger fw-bold">berkurang</span>.
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.loan-list {
    display: flex;
    flex-direction: column;
    gap: 0.75rem;
}

.loan-item {
    display: flex;
    align-items: center;
    padding: 1rem;
    border: 2px solid var(--gray-200, #e5e7eb);
    border-radius: 8px;
    cursor: pointer;
    transition: all 0.2s ease;
    background: #fff;
}

.loan-item:hover {
    border-color: var(--primary-300, #ecc96c);
    background: var(--gray-50, #f9fafb);
}

.loan-item.selected,
.loan-item:has(input:checked) {
    border-color: var(--primary-500, #c89b2c);
    background: var(--primary-50, #fdf8ec);
}

.loan-item input[type="radio"] {
    margin-right: 1rem;
    transform: scale(1.3);
    accent-color: var(--primary-500, #c89b2c);
}

.loan-info {
    flex: 1;
}

.loan-name {
    font-weight: 600;
    font-size: 1.05rem;
    margin-bottom: 0.25rem;
}

.loan-name i {
    color: var(--primary-500, #c89b2c);
    margin-right: 0.5rem;
}

.loan-details {
    font-size: 0.85rem;
    color: var(--gray-600, #4b5563);
}

.loan-outstanding {
    text-align: right;
    padding-left: 1rem;
    border-left: 1px solid var(--gray-200, #e5e7eb);
}

.loan-outstanding small {
    display: block;
    font-size: 0.75rem;
    color: var(--gray-500, #6b7280);
}

.loan-outstanding strong {
    font-size: 1.1rem;
    color: var(--danger-600, #dc2626);
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const amountInput = document.getElementById('amountInput');
    const previewDebit = document.getElementById('previewDebit');
    const previewCredit = document.getElementById('previewCredit');
    const outstandingDisplay = document.getElementById('outstandingDisplay');
    const payFullBtn = document.getElementById('payFullBtn');
    const loanRadios = document.querySelectorAll('input[name="lender_name"]');
    
    let currentOutstanding = 0;
    
    function formatRupiah(number) {
        return 'Rp ' + number.toString().replace(/\B(?=(\d{3})+(?!\d))/g, '.');
    }
    
    function parseNumber(str) {
        return parseInt(String(str).replace(/[^\d]/g, '')) || 0;
    }
    
    function updatePreview() {
        let value = parseNumber(amountInput.value);
        previewDebit.textContent = formatRupiah(value);
        previewCredit.textContent = formatRupiah(value);
    }
    
    function selectLoan(outstanding) {
        currentOutstanding = parseFloat(outstanding) || 0;
        outstandingDisplay.textContent = formatRupiah(currentOutstanding);
        
        if (currentOutstanding > 0) {
            payFullBtn.style.display = 'inline';
        } else {
            payFullBtn.style.display = 'none';
        }
        
        // Update loan-item selected class
        document.querySelectorAll('.loan-item').forEach(item => {
            item.classList.remove('selected');
        });
        const checkedRadio = document.querySelector('input[name="lender_name"]:checked');
        if (checkedRadio) {
            checkedRadio.closest('.loan-item').classList.add('selected');
        }
    }
    
    // Amount input formatting
    amountInput.addEventListener('input', function() {
        let value = parseNumber(this.value);
        if (value > 0) {
            this.value = value.toString().replace(/\B(?=(\d{3})+(?!\d))/g, '.');
        } else {
            this.value = '';
        }
        updatePreview();
    });
    
    // Loan selection
    loanRadios.forEach(radio => {
        radio.addEventListener('change', function() {
            selectLoan(this.dataset.outstanding);
        });
        
        // Check if already selected (from URL param or old input)
        if (radio.checked) {
            selectLoan(radio.dataset.outstanding);
        }
    });
    
    // Pay full button
    if (payFullBtn) {
        payFullBtn.addEventListener('click', function(e) {
            e.preventDefault();
            amountInput.value = currentOutstanding.toString().replace(/\B(?=(\d{3})+(?!\d))/g, '.');
            updatePreview();
        });
    }
    
    // Trigger on load if has value
    if (amountInput.value) {
        amountInput.dispatchEvent(new Event('input'));
    }
});
</script>