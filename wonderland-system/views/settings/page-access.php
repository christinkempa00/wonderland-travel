<?php
/**
 * Page Access Settings View (Super Admin only)
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
        <form method="POST" action="<?= url('/settings/page-access') ?>">
            <?= csrfField() ?>

            <div class="glass-card mb-4">
                <h4 class="card-title mb-2">
                    <i class="fas fa-shield-alt text-primary"></i>
                    Akses Halaman untuk Role Admin
                </h4>
                <p class="text-muted mb-4">
                    Centang halaman yang boleh dilihat/diakses oleh role <strong>Administrator</strong>.
                    Yang tidak dicentang akan hilang dari menu dan tidak bisa dibuka langsung lewat URL.
                    Super Admin selalu bisa mengakses semua halaman, tidak terpengaruh pengaturan ini.
                </p>

                <div class="d-flex flex-column gap-3">
                    <?php foreach ($pages as $key => $label): ?>
                    <label class="form-check d-flex align-items-center gap-2">
                        <input type="checkbox" name="pages[]" value="<?= e($key) ?>" class="form-check-input"
                               <?= !in_array($key, $disabledPages, true) ? 'checked' : '' ?>>
                        <span class="form-check-label"><?= e($label) ?></span>
                    </label>
                    <?php endforeach; ?>
                </div>

                <button type="submit" class="btn btn-primary mt-4">
                    <i class="fas fa-save"></i> Simpan Akses Halaman
                </button>
            </div>
        </form>
    </div>
</div>

<style>
.settings-tabs {
    display: flex;
    gap: 0.5rem;
    flex-wrap: wrap;
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
}

.tab-link.active {
    background: var(--primary-500);
    color: white;
}
</style>
