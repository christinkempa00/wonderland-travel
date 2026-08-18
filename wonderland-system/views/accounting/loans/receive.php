<?php
/**
 * Form Terima Pinjaman
 * File: /views/accounting/loans/receive.php
 */

$bankCashOptions = $bankCashOptions ?? [];
$lenderSuggestions = $lenderSuggestions ?? [];

// Old input
$old = Session::get('_old_input') ?: [];
?>

<div class="row">
    <div class="col-lg-7">
        <div class="glass-card">
            <form method="POST" action="<?= url('/loans/receive') ?>" id="loanForm">
                <?= csrfField() ?>
                
                <div class="form-group">
                    <label class="form-label required">Tanggal Transaksi</label>
                    <input type="date" 
                           name="transaction_date" 
                           class="form-control" 
                           value="<?= e($old['transaction_date'] ?? date('Y-m-d')) ?>"
                           required>
                </div>
                
                <div class="form-group">
                    <label class="form-label required">Nama Pemberi Pinjaman</label>
                    <input type="text" 
                           name="lender_name" 
                           class="form-control" 
                           placeholder="Contoh: Pak Ahmad, Bu Siti"
                           value="<?= e($old['lender_name'] ?? '') ?>"
                           list="lenderList"
                           autocomplete="off"
                           required>
                    <?php if (!empty($lenderSuggestions)): ?>
                    <datalist id="lenderList">
                        <?php foreach ($lenderSuggestions as $name): ?>
                            <option value="<?= e($name) ?>">
                        <?php endforeach; ?>
                    </datalist>
                    <small class="text-muted">Pilih dari daftar atau ketik nama baru.</small>
                    <?php endif; ?>
                </div>
                
                <div class="form-group">
                    <label class="form-label required">Jumlah Pinjaman</label>
                    <div class="input-group">
                        <span class="input-group-text">Rp</span>
                        <input type="text" 
                               name="amount" 
                               id="amountInput"
                               class="form-control text-end" 
                               placeholder="0"
                               value="<?= e($old['amount'] ?? '') ?>"
                               required>
                    </div>
                </div>
                
                <div class="form-group">
                    <label class="form-label required">Masuk ke Kas/Bank</label>
                    <select name="bank_cash_id" class="form-control" required>
                        <option value="">-- Pilih Kas/Bank Tujuan --</option>
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
                    <button type="submit" class="btn btn-success" <?= empty($bankCashOptions) ? 'disabled' : '' ?>>
                        <i class="fas fa-save me-1"></i> Simpan Pinjaman
                    </button>
                    <a href="<?= url('/loans') ?>" class="btn btn-secondary">
                        Batal
                    </a>
                </div>
            </form>
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
                            <td>Kas/Bank</td>
                            <td class="text-end text-success fw-bold" id="previewDebit">Rp 0</td>
                            <td class="text-end text-muted">-</td>
                        </tr>
                        <tr>
                            <td>Hutang Pinjaman Perorangan</td>
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
                        Saat menerima pinjaman, saldo Kas/Bank akan <span class="text-success fw-bold">bertambah</span> 
                        dan Hutang Pinjaman akan <span class="text-danger fw-bold">meningkat</span>.
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const amountInput = document.getElementById('amountInput');
    const previewDebit = document.getElementById('previewDebit');
    const previewCredit = document.getElementById('previewCredit');
    
    function formatRupiah(number) {
        return 'Rp ' + number.toString().replace(/\B(?=(\d{3})+(?!\d))/g, '.');
    }
    
    function parseNumber(str) {
        return parseInt(str.replace(/[^\d]/g, '')) || 0;
    }
    
    amountInput.addEventListener('input', function() {
        let value = parseNumber(this.value);
        if (value > 0) {
            this.value = value.toString().replace(/\B(?=(\d{3})+(?!\d))/g, '.');
        } else {
            this.value = '';
        }
        
        previewDebit.textContent = formatRupiah(value);
        previewCredit.textContent = formatRupiah(value);
    });
    
    // Trigger on load if has value
    if (amountInput.value) {
        amountInput.dispatchEvent(new Event('input'));
    }
});
</script>