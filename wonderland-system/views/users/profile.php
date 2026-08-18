<?php
/**
 * User Profile View
 */
?>

<div class="row">
    <div class="col-6">
        <!-- Profile Info -->
        <form method="POST" action="<?= url('/profile') ?>" enctype="multipart/form-data">
            <?= csrfField() ?>
            
            <div class="glass-card mb-4">
                <h4 class="card-title mb-4">
                    <i class="fas fa-user text-primary"></i>
                    Informasi Profil
                </h4>
                
                <div class="text-center mb-4">
                    <div class="avatar-upload">
                        <?php if ($user->avatar): ?>
                        <img src="<?= url('/uploads/' . $user->avatar) ?>" alt="Avatar" class="avatar-preview">
                        <?php else: ?>
                        <div class="avatar-preview avatar-placeholder">
                            <?= strtoupper(substr($user->name, 0, 1)) ?>
                        </div>
                        <?php endif; ?>
                        <label class="avatar-edit">
                            <input type="file" name="avatar" accept="image/*" style="display: none;">
                            <i class="fas fa-camera"></i>
                        </label>
                    </div>
                    <p class="text-sm text-muted mt-2">Klik untuk mengubah foto</p>
                </div>
                
                <div class="form-group">
                    <label class="form-label required">Nama Lengkap</label>
                    <input type="text" name="name" class="form-control" 
                           value="<?= e($user->name) ?>" required>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Email</label>
                    <input type="email" class="form-control" value="<?= e($user->email) ?>" disabled>
                    <p class="form-text">Email tidak dapat diubah.</p>
                </div>
                
                <div class="form-group">
                    <label class="form-label">No. Telepon</label>
                    <input type="text" name="phone" class="form-control" 
                           value="<?= e($user->phone) ?>">
                </div>
                
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save"></i> Simpan Profil
                </button>
            </div>
        </form>
    </div>
    
    <div class="col-6">
        <!-- Change Password -->
        <form method="POST" action="<?= url('/profile/password') ?>">
            <?= csrfField() ?>
            
            <div class="glass-card mb-4">
                <h4 class="card-title mb-4">
                    <i class="fas fa-lock text-primary"></i>
                    Ubah Password
                </h4>
                
                <div class="form-group">
                    <label class="form-label required">Password Saat Ini</label>
                    <input type="password" name="current_password" class="form-control" required>
                </div>
                
                <div class="form-group">
                    <label class="form-label required">Password Baru</label>
                    <input type="password" name="new_password" class="form-control" 
                           required minlength="8">
                    <p class="form-text">Minimal 8 karakter</p>
                </div>
                
                <div class="form-group">
                    <label class="form-label required">Konfirmasi Password Baru</label>
                    <input type="password" name="confirm_password" class="form-control" required>
                </div>
                
                <button type="submit" class="btn btn-warning">
                    <i class="fas fa-key"></i> Ubah Password
                </button>
            </div>
        </form>
        
        <!-- Account Info -->
        <div class="glass-card">
            <h4 class="card-title mb-4">
                <i class="fas fa-info-circle text-primary"></i>
                Informasi Akun
            </h4>
            
            <div class="info-list">
                <div class="info-item">
                    <label>Role</label>
                    <span class="badge badge-primary"><?= ROLES[$user->role] ?? ucfirst($user->role) ?></span>
                </div>
                <div class="info-item">
                    <label>Terdaftar Sejak</label>
                    <span><?= formatDateIndo($user->created_at) ?></span>
                </div>
                <div class="info-item">
                    <label>Login Terakhir</label>
                    <span><?= $user->last_login ? formatDateTime($user->last_login) : '-' ?></span>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.avatar-upload {
    position: relative;
    display: inline-block;
}

.avatar-preview {
    width: 120px;
    height: 120px;
    border-radius: 50%;
    object-fit: cover;
    border: 4px solid white;
    box-shadow: var(--shadow-md);
}

.avatar-placeholder {
    display: flex;
    align-items: center;
    justify-content: center;
    background: var(--primary-500);
    color: white;
    font-size: 3rem;
    font-weight: 600;
}

.avatar-edit {
    position: absolute;
    bottom: 0;
    right: 0;
    width: 36px;
    height: 36px;
    background: var(--primary-500);
    color: white;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    box-shadow: var(--shadow-md);
    transition: all 0.2s ease;
}

.avatar-edit:hover {
    background: var(--primary-600);
    transform: scale(1.1);
}

.info-list {
    display: flex;
    flex-direction: column;
    gap: 0.75rem;
}

.info-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 0.75rem;
    background: var(--gray-50);
    border-radius: var(--border-radius-sm);
}

.info-item label {
    color: var(--gray-500);
    font-size: 0.9rem;
}
</style>

<script>
// Preview avatar before upload
document.querySelector('input[name="avatar"]').addEventListener('change', function(e) {
    const file = e.target.files[0];
    if (file) {
        const reader = new FileReader();
        reader.onload = function(e) {
            const preview = document.querySelector('.avatar-preview');
            if (preview.tagName === 'IMG') {
                preview.src = e.target.result;
            } else {
                // Replace placeholder with img
                const img = document.createElement('img');
                img.src = e.target.result;
                img.className = 'avatar-preview';
                img.alt = 'Avatar';
                preview.replaceWith(img);
            }
        };
        reader.readAsDataURL(file);
    }
});
</script>
