<?php
/**
 * Company Settings View
 */
?>

<!-- Settings Tabs -->
<div class="glass-card mb-4">
    <div class="settings-tabs">
        <a href="<?= url('/settings/company') ?>" class="tab-link <?= $activeTab === 'company' ? 'active' : '' ?>">
            <i class="fas fa-building"></i> Perusahaan
        </a>
        <a href="<?= url('/settings/documents') ?>" class="tab-link <?= $activeTab === 'documents' ? 'active' : '' ?>">
            <i class="fas fa-file-alt"></i> Dokumen
        </a>
        <a href="<?= url('/settings/integrations') ?>" class="tab-link <?= $activeTab === 'integrations' ? 'active' : '' ?>">
            <i class="fas fa-plug"></i> Integrasi
        </a>
    </div>
</div>

<form method="POST" action="<?= url('/settings/company') ?>" enctype="multipart/form-data">
    <?= csrfField() ?>
    
    <div class="row">
        <div class="col-6">
            <div class="glass-card mb-4">
                <h4 class="card-title mb-4">
                    <i class="fas fa-building text-primary"></i>
                    Informasi Perusahaan
                </h4>
                
                <div class="form-group">
                    <label class="form-label required">Nama Perusahaan</label>
                    <input type="text" name="name" class="form-control" 
                           value="<?= e($company->name) ?>" required>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Alamat</label>
                    <textarea name="address" class="form-control" rows="3"><?= e($company->address) ?></textarea>
                </div>
                
                <div class="row">
                    <div class="col-6">
                        <div class="form-group">
                            <label class="form-label">Telepon</label>
                            <input type="text" name="phone" class="form-control" 
                                   value="<?= e($company->phone) ?>">
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="form-group">
                            <label class="form-label">Email</label>
                            <input type="email" name="email" class="form-control" 
                                   value="<?= e($company->email) ?>">
                        </div>
                    </div>
                </div>
                
                <div class="row">
                    <div class="col-6">
                        <div class="form-group">
                            <label class="form-label">Website</label>
                            <input type="url" name="website" class="form-control" 
                                   value="<?= e($company->website) ?>">
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="form-group">
                            <label class="form-label">NPWP</label>
                            <input type="text" name="npwp" class="form-control" 
                                   value="<?= e($company->npwp) ?>">
                        </div>
                    </div>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Logo</label>
                    <?php if ($company->logo): ?>
                    <div class="mb-2">
                        <img src="<?= $company->getLogoUrl() ?>" alt="Logo" style="max-height: 60px;">
                    </div>
                    <?php endif; ?>
                    <input type="file" name="logo" class="form-control" accept="image/*">
                    <p class="form-text">Format: JPG, PNG, GIF. Maks 2MB.</p>
                </div>
            </div>
        </div>
        
        <div class="col-6">
            <div class="glass-card mb-4">
                <h4 class="card-title mb-4">
                    <i class="fas fa-university text-primary"></i>
                    Rekening Bank
                </h4>
                
                <div id="bankAccountsContainer">
                    <?php if (empty($bankAccounts)): ?>
                    <div class="bank-account-row" data-index="0">
                        <div class="row">
                            <div class="col-4">
                                <input type="text" name="bank_name[]" class="form-control form-control-sm" placeholder="Nama Bank">
                            </div>
                            <div class="col-4">
                                <input type="text" name="account_number[]" class="form-control form-control-sm" placeholder="No. Rekening">
                            </div>
                            <div class="col-3">
                                <input type="text" name="account_holder[]" class="form-control form-control-sm" placeholder="Atas Nama">
                            </div>
                            <div class="col-1">
                                <button type="button" class="btn btn-sm btn-icon btn-secondary text-danger remove-bank-btn">
                                    <i class="fas fa-times"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                    <?php else: ?>
                    <?php foreach ($bankAccounts as $i => $bank): ?>
                    <div class="bank-account-row" data-index="<?= $i ?>">
                        <div class="row mb-2">
                            <div class="col-4">
                                <input type="text" name="bank_name[]" class="form-control form-control-sm" 
                                       placeholder="Nama Bank" value="<?= e($bank['bank_name']) ?>">
                            </div>
                            <div class="col-4">
                                <input type="text" name="account_number[]" class="form-control form-control-sm" 
                                       placeholder="No. Rekening" value="<?= e($bank['account_number']) ?>">
                            </div>
                            <div class="col-3">
                                <input type="text" name="account_holder[]" class="form-control form-control-sm" 
                                       placeholder="Atas Nama" value="<?= e($bank['account_holder']) ?>">
                            </div>
                            <div class="col-1">
                                <button type="button" class="btn btn-sm btn-icon btn-secondary text-danger remove-bank-btn">
                                    <i class="fas fa-times"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                    <?php endif; ?>
                </div>
                
                <button type="button" class="btn btn-sm btn-secondary mt-2" id="addBankBtn">
                    <i class="fas fa-plus"></i> Tambah Rekening
                </button>
            </div>
            
            <button type="submit" class="btn btn-primary">
                <i class="fas fa-save"></i> Simpan Pengaturan
            </button>
        </div>
    </div>
</form>

<style>
.settings-tabs {
    display: flex;
    gap: 0.5rem;
}

.tab-link {
    padding: 0.75rem 1.25rem;
    border-radius: var(--border-radius-sm);
    text-decoration: none;
    color: var(--gray-600);
    transition: all 0.2s ease;
}

.tab-link:hover {
    background: var(--gray-100);
    color: var(--gray-800);
}

.tab-link.active {
    background: var(--primary-500);
    color: white;
}

.tab-link i {
    margin-right: 0.5rem;
}

.bank-account-row {
    margin-bottom: 0.5rem;
}
</style>

<script>
document.getElementById('addBankBtn').addEventListener('click', function() {
    const container = document.getElementById('bankAccountsContainer');
    const index = container.children.length;
    
    const html = `
        <div class="bank-account-row" data-index="${index}">
            <div class="row mb-2">
                <div class="col-4">
                    <input type="text" name="bank_name[]" class="form-control form-control-sm" placeholder="Nama Bank">
                </div>
                <div class="col-4">
                    <input type="text" name="account_number[]" class="form-control form-control-sm" placeholder="No. Rekening">
                </div>
                <div class="col-3">
                    <input type="text" name="account_holder[]" class="form-control form-control-sm" placeholder="Atas Nama">
                </div>
                <div class="col-1">
                    <button type="button" class="btn btn-sm btn-icon btn-secondary text-danger remove-bank-btn">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
            </div>
        </div>
    `;
    
    container.insertAdjacentHTML('beforeend', html);
    bindRemoveButtons();
});

function bindRemoveButtons() {
    document.querySelectorAll('.remove-bank-btn').forEach(btn => {
        btn.onclick = function() {
            this.closest('.bank-account-row').remove();
        };
    });
}

bindRemoveButtons();
</script>
