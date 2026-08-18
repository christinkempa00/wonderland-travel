<?php
/**
 * User Form View (Create/Edit)
 */

$data = $user ? $user->toArray() : ['is_active' => 1];
?>

<div class="row">
    <div class="col-6">
        <div class="glass-card">
            <form method="POST" 
                  action="<?= $isEdit ? url('/users/' . $user->id) : url('/users') ?>"
                  id="userForm">
                <?= csrfField() ?>
                <?php if ($isEdit): ?>
                <?= methodField('PUT') ?>
                <?php endif; ?>
                
                <div class="form-group">
                    <label class="form-label required">Nama Lengkap</label>
                    <input type="text" name="name" class="form-control" 
                           value="<?= e(old('name', $data['name'] ?? '')) ?>" required>
                </div>
                
                <div class="form-group">
                    <label class="form-label required">Email</label>
                    <input type="email" name="email" class="form-control" 
                           value="<?= e(old('email', $data['email'] ?? '')) ?>" required>
                </div>
                
                <div class="form-group">
                    <label class="form-label">No. Telepon</label>
                    <input type="text" name="phone" class="form-control" 
                           value="<?= e(old('phone', $data['phone'] ?? '')) ?>">
                </div>
                
                <div class="form-group">
                    <label class="form-label required">Role</label>
                    <select name="role" class="form-control" required>
                        <?php foreach ($roles as $key => $role): ?>
                        <option value="<?= $key ?>" <?= old('role', $data['role'] ?? 'staff') === $key ? 'selected' : '' ?>>
                            <?= $role['label'] ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <?php if (Session::isSuperAdmin() && !empty($companies)): ?>
                <div class="form-group">
                    <label class="form-label">Perusahaan</label>
                    <div class="company-checkboxes">
                        <?php foreach ($companies as $companyId => $companyName): ?>
                        <label class="form-check">
                            <input type="checkbox" name="company_ids[]" value="<?= $companyId ?>" 
                                   class="form-check-input"
                                   <?= in_array($companyId, $userCompanyIds ?? []) ? 'checked' : '' ?>>
                            <span class="form-check-label"><?= e($companyName) ?></span>
                        </label>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endif; ?>
                
                <div class="form-group">
                    <label class="form-label <?= !$isEdit ? 'required' : '' ?>">
                        Password <?= $isEdit ? '(kosongkan jika tidak diubah)' : '' ?>
                    </label>
                    <input type="password" name="password" class="form-control" 
                           <?= !$isEdit ? 'required' : '' ?> minlength="8">
                    <p class="form-text">Minimal 8 karakter</p>
                </div>
                
                <div class="form-group">
                    <label class="form-check">
                        <input type="checkbox" name="is_active" class="form-check-input" value="1"
                               <?= old('is_active', $data['is_active'] ?? 1) ? 'checked' : '' ?>>
                        <span class="form-check-label">Aktif</span>
                    </label>
                </div>
                
                <div class="d-flex gap-3 mt-4">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i>
                        <?= $isEdit ? 'Simpan Perubahan' : 'Simpan' ?>
                    </button>
                    <a href="<?= url('/users') ?>" class="btn btn-secondary">
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
                Keterangan Role
            </h4>
            
            <div class="role-info-list">
                <?php foreach ($roles as $key => $role): ?>
                <div class="role-info-item">
                    <span class="badge badge-<?= $role['color'] ?>"><?= $role['label'] ?></span>
                    <p class="text-sm text-muted mb-0">
                        <?php
                        $desc = [
                            'superadmin' => 'Akses penuh ke semua fitur dan semua perusahaan.',
                            'admin' => 'Akses penuh ke semua fitur dalam perusahaan.',
                            'staff' => 'Dapat mengelola pesanan, klien, dan dokumen.',
                            'accounting' => 'Akses ke fitur akuntansi dan laporan keuangan.',
                            'viewer' => 'Hanya dapat melihat data, tidak dapat mengedit.'
                        ];
                        echo $desc[$key] ?? '';
                        ?>
                    </p>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</div>

<style>
.company-checkboxes {
    max-height: 200px;
    overflow-y: auto;
    padding: 0.5rem;
    background: var(--gray-50);
    border-radius: var(--border-radius-sm);
}

.company-checkboxes .form-check {
    margin-bottom: 0.5rem;
}

.role-info-list {
    display: flex;
    flex-direction: column;
    gap: 1rem;
}

.role-info-item {
    padding: 0.75rem;
    background: var(--gray-50);
    border-radius: var(--border-radius-sm);
}

.role-info-item .badge {
    margin-bottom: 0.5rem;
}
</style>
