<?php
/**
 * Bank/Cash Form View
 */
$bc = $bankCash ?? null;
?>

<form method="POST" action="<?= url($isEdit ? '/accounting/bank-cash/' . $bc->id : '/accounting/bank-cash') ?>" class="glass-card">
    <?= csrfField() ?>
    <?php if ($isEdit): ?>
    <?= methodField('PUT') ?>
    <?php endif; ?>
    
    <div class="row">
        <div class="col-6">
            <div class="form-group">
                <label class="form-label">Nama <span class="text-danger">*</span></label>
                <input type="text" name="name" class="form-control" 
                       value="<?= e(old('name', $bc->name ?? '')) ?>" required>
            </div>
        </div>
        
        <div class="col-6">
            <div class="form-group">
                <label class="form-label">Tipe <span class="text-danger">*</span></label>
                <select name="type" class="form-control" required>
                    <option value="cash" <?= old('type', $bc->type ?? '') === 'cash' ? 'selected' : '' ?>>Kas</option>
                    <option value="bank" <?= old('type', $bc->type ?? '') === 'bank' ? 'selected' : '' ?>>Bank</option>
                </select>
            </div>
        </div>
    </div>
    
    <div class="row" id="bankFields" style="<?= (old('type', $bc->type ?? '') !== 'bank') ? 'display:none;' : '' ?>">
        <div class="col-4">
            <div class="form-group">
                <label class="form-label">Nama Bank</label>
                <input type="text" name="bank_name" class="form-control" 
                       value="<?= e(old('bank_name', $bc->bank_name ?? '')) ?>">
            </div>
        </div>
        
        <div class="col-4">
            <div class="form-group">
                <label class="form-label">No. Rekening</label>
                <input type="text" name="account_number" class="form-control" 
                       value="<?= e(old('account_number', $bc->account_number ?? '')) ?>">
            </div>
        </div>
        
        <div class="col-4">
            <div class="form-group">
                <label class="form-label">Atas Nama</label>
                <input type="text" name="account_holder" class="form-control" 
                       value="<?= e(old('account_holder', $bc->account_holder ?? '')) ?>">
            </div>
        </div>
    </div>
    
    <div class="row">
        <div class="col-6">
            <div class="form-group">
                <label class="form-label">Akun COA</label>
                <select name="account_id" class="form-control">
                    <option value="">-- Pilih Akun --</option>
                    <?php foreach ($accounts as $acc): ?>
                    <option value="<?= $acc['id'] ?>" <?= old('account_id', $bc->account_id ?? '') == $acc['id'] ? 'selected' : '' ?>>
                        <?= e($acc['code'] . ' - ' . $acc['name']) ?>
                    </option>
                    <?php endforeach; ?>
                </select>
                <small class="text-muted">Pilih akun di Chart of Accounts untuk integrasi jurnal</small>
            </div>
        </div>
        
        <?php if (!$isEdit): ?>
        <div class="col-6">
            <div class="form-group">
                <label class="form-label">Saldo Awal</label>
                <input type="number" name="balance" class="form-control" step="0.01"
                       value="<?= old('balance', 0) ?>">
            </div>
        </div>
        <?php endif; ?>
    </div>
    
    <div class="form-group">
        <label class="form-check">
            <input type="checkbox" name="is_default" value="1" class="form-check-input"
                   <?= old('is_default', $bc->is_default ?? false) ? 'checked' : '' ?>>
            <span class="form-check-label">Jadikan default</span>
        </label>
    </div>
    
    <div class="form-actions">
        <button type="submit" class="btn btn-primary">
            <i class="fas fa-save"></i> <?= $isEdit ? 'Simpan Perubahan' : 'Simpan' ?>
        </button>
        <a href="<?= url('/accounting/bank-cash') ?>" class="btn btn-secondary">Batal</a>
    </div>
</form>

<script>
document.querySelector('select[name="type"]').addEventListener('change', function() {
    document.getElementById('bankFields').style.display = this.value === 'bank' ? '' : 'none';
});
</script>
