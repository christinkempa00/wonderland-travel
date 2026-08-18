<?php
/**
 * Transaction Form View
 * File: /views/transactions/form.php
 */

$isExpense = ($type ?? 'expense') === 'expense';
?>

<div class="row justify-content-center">
    <div class="col-lg-8">
        <div class="glass-card">
            <div class="d-flex align-items-center mb-4">
                <div class="icon-box <?= $isExpense ? 'bg-danger' : 'bg-success' ?> bg-opacity-10 me-3">
                    <i class="fas fa-<?= $isExpense ? 'minus' : 'plus' ?>-circle text-<?= $isExpense ? 'danger' : 'success' ?> fa-2x"></i>
                </div>
                <div>
                    <h4 class="mb-0"><?= $isExpense ? 'Catat Pengeluaran' : 'Catat Pemasukan' ?></h4>
                    <small class="text-muted">
                        <?= $isExpense ? 'Input pengeluaran operasional' : 'Input pemasukan lain-lain' ?>
                    </small>
                </div>
            </div>
            
            <form method="POST" action="<?= url('/transactions') ?>">
                <?= csrfField() ?>
                <input type="hidden" name="transaction_type" value="<?= $type ?>">
                
                <div class="row g-3">
                    <!-- Tanggal -->
                    <div class="col-md-6">
                        <div class="form-group">
                            <label class="form-label required">Tanggal Transaksi</label>
                            <input type="date" name="transaction_date" class="form-control" 
                                   value="<?= old('transaction_date', date('Y-m-d')) ?>" required>
                        </div>
                    </div>
                    
                    <!-- Jumlah -->
                    <div class="col-md-6">
                        <div class="form-group">
                            <label class="form-label required">Jumlah (Rp)</label>
                            <input type="text" name="amount" id="amountInput" class="form-control" 
                                   value="<?= old('amount', '0') ?>" 
                                   placeholder="0" required>
                        </div>
                    </div>
                    
                    <!-- Akun Beban/Pendapatan -->
                    <div class="col-md-6">
                        <div class="form-group">
                            <label class="form-label required">
                                <?= $isExpense ? 'Akun Beban' : 'Akun Pendapatan' ?>
                            </label>
                            <select name="account_id" class="form-control" required>
                                <option value="">-- Pilih Akun --</option>
                                <?php 
                                $accounts = $isExpense ? ($expenseAccounts ?? []) : ($incomeAccounts ?? []);
                                foreach ($accounts as $acc): 
                                ?>
                                <option value="<?= $acc['id'] ?>" <?= old('account_id') == $acc['id'] ? 'selected' : '' ?>>
                                    <?= e($acc['code']) ?> - <?= e($acc['name']) ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                            <small class="text-muted">
                                <?= $isExpense ? 'Pilih jenis beban/pengeluaran' : 'Pilih jenis pendapatan' ?>
                            </small>
                        </div>
                    </div>
                    
                    <!-- Kas/Bank -->
                    <div class="col-md-6">
                        <div class="form-group">
                            <label class="form-label">
                                <?= $isExpense ? 'Dibayar dari' : 'Diterima di' ?>
                            </label>
                            <select name="bank_cash_id" class="form-control">
                                <option value="">-- Pilih Kas/Bank --</option>
                                <?php foreach ($bankCashOptions ?? [] as $id => $name): ?>
                                <option value="<?= $id ?>" <?= old('bank_cash_id') == $id ? 'selected' : '' ?>>
                                    <?= e($name) ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                            <small class="text-muted">Untuk update saldo kas/bank</small>
                        </div>
                    </div>
                    
                    <!-- Kategori -->
                    <div class="col-md-6">
                        <div class="form-group">
                            <label class="form-label">Kategori</label>
                            <input type="text" name="category" class="form-control" 
                                   value="<?= old('category', '') ?>"
                                   placeholder="Contoh: Operasional, Marketing, dll"
                                   list="categoryList">
                            <datalist id="categoryList">
                                <option value="Operasional">
                                <option value="Marketing">
                                <option value="Administrasi">
                                <option value="Transportasi">
                                <option value="Makan Minum">
                                <option value="Perlengkapan">
                                <option value="Maintenance">
                                <option value="Lain-lain">
                            </datalist>
                        </div>
                    </div>
                    
                    <!-- Referensi -->
                    <div class="col-md-6">
                        <div class="form-group">
                            <label class="form-label">No. Bukti/Referensi</label>
                            <input type="text" name="reference" class="form-control" 
                                   value="<?= old('reference', '') ?>"
                                   placeholder="No. nota, kwitansi, dll">
                        </div>
                    </div>
                    
                    <!-- Deskripsi -->
                    <div class="col-12">
                        <div class="form-group">
                            <label class="form-label required">Deskripsi</label>
                            <textarea name="description" class="form-control" rows="3" 
                                      placeholder="Keterangan transaksi..." required><?= old('description', '') ?></textarea>
                        </div>
                    </div>
                </div>
                
                <!-- Journal Info -->
                <div class="alert alert-<?= $isExpense ? 'warning' : 'info' ?> mt-4">
                    <i class="fas fa-magic me-2"></i>
                    <strong>Jurnal Otomatis:</strong>
                    <?php if ($isExpense): ?>
                    <ul class="mb-0 mt-2 small">
                        <li><strong>Debit:</strong> Akun Beban yang dipilih</li>
                        <li><strong>Credit:</strong> Kas/Bank</li>
                    </ul>
                    <?php else: ?>
                    <ul class="mb-0 mt-2 small">
                        <li><strong>Debit:</strong> Kas/Bank</li>
                        <li><strong>Credit:</strong> Akun Pendapatan yang dipilih</li>
                    </ul>
                    <?php endif; ?>
                </div>
                
                <div class="d-flex gap-2 mt-4">
                    <button type="submit" class="btn btn-<?= $isExpense ? 'danger' : 'success' ?>">
                        <i class="fas fa-save"></i> Simpan Transaksi
                    </button>
                    <a href="<?= url('/transactions') ?>" class="btn btn-secondary">
                        <i class="fas fa-arrow-left"></i> Kembali
                    </a>
                </div>
            </form>
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

// Convert to number before submit
document.querySelector('form')?.addEventListener('submit', function(e) {
    const amountInput = document.getElementById('amountInput');
    if (amountInput) {
        amountInput.value = amountInput.value.replace(/\D/g, '');
    }
});
</script>

<style>
.icon-box {
    width: 60px;
    height: 60px;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
}
</style>
