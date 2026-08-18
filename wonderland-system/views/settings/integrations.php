<?php
/**
 * Integrations Settings View
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
        <?php if (Session::isSuperAdmin()): ?>
        <a href="<?= url('/settings/page-access') ?>" class="tab-link <?= $activeTab === 'page-access' ? 'active' : '' ?>">
            <i class="fas fa-shield-alt"></i> Akses Halaman
        </a>
        <?php endif; ?>
    </div>
</div>

<div class="row">
    <div class="col-6">
        <form method="POST" action="<?= url('/settings/integrations') ?>">
            <?= csrfField() ?>
            
            <div class="glass-card mb-4">
                <div class="d-flex align-items-center mb-4">
                    <img src="https://dripsender.id/assets/images/logo.png" alt="Dripsender" style="height: 40px;" class="me-3" onerror="this.style.display='none'">
                    <div>
                        <h4 class="card-title mb-0">Dripsender WhatsApp</h4>
                        <p class="text-muted mb-0">Integrasi notifikasi WhatsApp</p>
                    </div>
                </div>
                
                <div class="form-group">
                    <label class="form-label">API Key</label>
                    <input type="text" name="dripsender_api_key" class="form-control" 
                           value="<?= e($settings['dripsender_api_key']) ?>"
                           placeholder="Masukkan API Key dari Dripsender">
                    <p class="form-text">
                        Dapatkan API Key dari <a href="https://dripsender.id" target="_blank">dripsender.id</a>
                    </p>
                </div>
                
                <div class="form-group">
                    <label class="form-check">
                        <input type="checkbox" name="dripsender_enabled" class="form-check-input" value="1"
                               <?= $settings['dripsender_enabled'] ? 'checked' : '' ?>>
                        <span class="form-check-label">Aktifkan Notifikasi WhatsApp</span>
                    </label>
                    <p class="form-text">Notifikasi akan dikirim saat invoice dibuat dan saat pembayaran diterima.</p>
                </div>
                
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save"></i> Simpan Pengaturan
                </button>
            </div>
        </form>
        
        <!-- Test -->
        <div class="glass-card">
            <h4 class="card-title mb-4">
                <i class="fas fa-paper-plane text-primary"></i>
                Test Koneksi
            </h4>
            
            <div class="form-group">
                <label class="form-label">Nomor WhatsApp Tujuan</label>
                <input type="text" id="testPhone" class="form-control" placeholder="08123456789">
            </div>
            
            <button type="button" class="btn btn-success" id="testBtn" <?= !$settings['dripsender_api_key'] ? 'disabled' : '' ?>>
                <i class="fab fa-whatsapp"></i> Kirim Pesan Test
            </button>
        </div>
    </div>
    
    <div class="col-6">
        <div class="glass-card">
            <h4 class="card-title mb-4">
                <i class="fas fa-history text-primary"></i>
                Log Pengiriman
            </h4>
            
            <?php if (empty($logs)): ?>
            <p class="text-muted text-center py-4">Belum ada log pengiriman.</p>
            <?php else: ?>
            <div class="log-list">
                <?php foreach ($logs as $log): ?>
                <div class="log-item">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <span class="font-medium"><?= e($log['phone']) ?></span>
                            <br>
                            <small class="text-muted"><?= formatDateTime($log['created_at']) ?></small>
                        </div>
                        <span class="badge badge-<?= $log['status'] === 'sent' ? 'success' : 'danger' ?>">
                            <?= $log['status'] === 'sent' ? 'Terkirim' : 'Gagal' ?>
                        </span>
                    </div>
                    <p class="text-sm text-muted mt-2 mb-0" style="word-break: break-all;">
                        <?= e(truncate($log['message'], 100)) ?>
                    </p>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

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

.log-list {
    max-height: 500px;
    overflow-y: auto;
}

.log-item {
    padding: 1rem;
    border-bottom: 1px solid var(--gray-200);
}

.log-item:last-child {
    border-bottom: none;
}
</style>

<script>
document.getElementById('testBtn').addEventListener('click', function() {
    const phone = document.getElementById('testPhone').value.trim();
    
    if (!phone) {
        Toast.warning('Perhatian', 'Masukkan nomor WhatsApp tujuan.');
        return;
    }
    
    this.disabled = true;
    this.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Mengirim...';
    
    fetch('<?= url('/settings/test-dripsender') ?>', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
            'X-CSRF-TOKEN': '<?= Session::getCsrfToken() ?>',
            'X-Requested-With': 'XMLHttpRequest'
        },
        body: 'phone=' + encodeURIComponent(phone)
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            Toast.success('Berhasil', data.message);
        } else {
            Toast.error('Gagal', data.message);
        }
    })
    .catch(err => {
        Toast.error('Error', 'Terjadi kesalahan');
    })
    .finally(() => {
        this.disabled = false;
        this.innerHTML = '<i class="fab fa-whatsapp"></i> Kirim Pesan Test';
    });
});
</script>
