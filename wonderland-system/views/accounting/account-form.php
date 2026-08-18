<?php
/**
 * Account Form View (Create/Edit)
 */

$data = $account ? $account->toArray() : [];
?>

<div class="row">
    <div class="col-6">
        <div class="glass-card">
            <form method="POST" 
                  action="<?= $isEdit ? url('/accounting/accounts/' . $account->id) : url('/accounting/accounts') ?>"
                  id="accountForm">
                <?= csrfField() ?>
                <?php if ($isEdit): ?>
                <?= methodField('PUT') ?>
                <?php endif; ?>
                
                <div class="row">
                    <div class="col-4">
                        <div class="form-group">
                            <label class="form-label required">Kode Akun</label>
                            <input type="text" name="code" class="form-control" 
                                   value="<?= e(old('code', $data['code'] ?? '')) ?>" 
                                   placeholder="1.1.01" required>
                        </div>
                    </div>
                    <div class="col-8">
                        <div class="form-group">
                            <label class="form-label required">Nama Akun</label>
                            <input type="text" name="name" class="form-control" 
                                   value="<?= e(old('name', $data['name'] ?? '')) ?>" 
                                   placeholder="Kas" required>
                        </div>
                    </div>
                </div>
                
                <div class="row">
                    <div class="col-6">
                        <div class="form-group">
                            <label class="form-label required">Tipe Akun</label>
                            <select name="type" class="form-control" required>
                                <?php foreach ($accountTypes as $type => $typeData): ?>
<option value="<?= $type ?>" <?= old('type', $data['type'] ?? '') === $type ? 'selected' : '' ?>>
    <?= $typeData['label'] ?>
</option>
<?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="form-group">
                            <label class="form-label">Akun Induk</label>
                            <select name="parent_id" class="form-control">
                                <option value="">-- Tidak Ada --</option>
                                <?php foreach ($parentAccounts as $id => $label): ?>
                                <?php if (!$isEdit || $id != $account->id): ?>
                                <option value="<?= $id ?>" <?= old('parent_id', $data['parent_id'] ?? '') == $id ? 'selected' : '' ?>>
                                    <?= e($label) ?>
                                </option>
                                <?php endif; ?>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                </div>
                
                <div class="form-group">
                    <label class="form-check">
                        <input type="checkbox" name="is_cash_bank" class="form-check-input" value="1"
                               <?= old('is_cash_bank', $data['is_cash_bank'] ?? 0) ? 'checked' : '' ?>>
                        <span class="form-check-label">Akun Kas/Bank</span>
                    </label>
                    <p class="form-text">Centang jika akun ini adalah kas atau rekening bank.</p>
                </div>
                
                <div class="d-flex gap-3 mt-4">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i>
                        <?= $isEdit ? 'Simpan Perubahan' : 'Simpan' ?>
                    </button>
                    <a href="<?= url('/accounting/accounts') ?>" class="btn btn-secondary">
                        Batal
                    </a>
                </div>
            </form>
        </div>
    </div>
    
    <div class="col-6">
        <div class="glass-card">
            <h4 class="card-title mb-4">
                <i class="fas fa-info-circle text-primary"></i>
                Panduan
            </h4>
            
            <div class="alert alert-info">
                <strong>Format Kode Akun:</strong>
                <ul class="mb-0 mt-2">
                    <li><code>1.x.xx</code> - Aset</li>
                    <li><code>2.x.xx</code> - Kewajiban</li>
                    <li><code>3.x.xx</code> - Ekuitas</li>
                    <li><code>4.x.xx</code> - Pendapatan</li>
                    <li><code>5.x.xx</code> - Beban</li>
                </ul>
            </div>
            
            <div class="alert alert-warning">
                <i class="fas fa-exclamation-triangle"></i>
                <strong>Perhatian:</strong> Akun yang sudah memiliki transaksi tidak dapat dihapus.
            </div>
        </div>
    </div>
</div>
