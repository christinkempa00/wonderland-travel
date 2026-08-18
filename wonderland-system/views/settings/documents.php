<?php
/**
 * Document Settings View
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

<form method="POST" action="<?= url('/settings/documents') ?>">
    <?= csrfField() ?>
    
    <div class="row">
        <div class="col-6">
            <div class="glass-card mb-4">
                <h4 class="card-title mb-4">
                    <i class="fas fa-hashtag text-primary"></i>
                    Format Nomor Dokumen
                </h4>
                
                <div class="alert alert-info mb-4">
                    <strong>Variabel yang tersedia:</strong>
                    <ul class="mb-0 mt-2">
                        <li><code>{YEAR}</code> - Tahun 4 digit (2025)</li>
                        <li><code>{YY}</code> - Tahun 2 digit (25)</li>
                        <li><code>{MONTH}</code> atau <code>{MM}</code> - Bulan (01-12)</li>
                        <li><code>{NUM}</code> - Nomor urut</li>
                    </ul>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Format No. Order</label>
                    <input type="text" name="doc_number_format_order" class="form-control" 
                           value="<?= e($settings['doc_number_format_order']) ?>">
                    <p class="form-text">Contoh hasil: ORD/2501/0001</p>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Format No. Penawaran</label>
                    <input type="text" name="doc_number_format_quotation" class="form-control" 
                           value="<?= e($settings['doc_number_format_quotation']) ?>">
                </div>
                
                <div class="form-group">
                    <label class="form-label">Format No. Invoice</label>
                    <input type="text" name="doc_number_format_invoice" class="form-control" 
                           value="<?= e($settings['doc_number_format_invoice']) ?>">
                </div>
                
                <div class="form-group">
                    <label class="form-label">Format No. Kwitansi</label>
                    <input type="text" name="doc_number_format_receipt" class="form-control" 
                           value="<?= e($settings['doc_number_format_receipt']) ?>">
                </div>
            </div>
        </div>
        
        <div class="col-6">
            <div class="glass-card mb-4">
                <h4 class="card-title mb-4">
                    <i class="fas fa-cog text-primary"></i>
                    Pengaturan Lainnya
                </h4>
                
                <div class="form-group">
                    <label class="form-label">Jatuh Tempo Invoice (hari)</label>
                    <input type="number" name="invoice_due_days" class="form-control" 
                           value="<?= e($settings['invoice_due_days']) ?>" min="1" max="365">
                    <p class="form-text">Tanggal jatuh tempo dihitung dari tanggal invoice dibuat.</p>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Tipe Markup Default</label>
                    <select name="default_markup_type" class="form-control">
                        <option value="percentage" <?= $settings['default_markup_type'] === 'percentage' ? 'selected' : '' ?>>
                            Persentase (%)
                        </option>
                        <option value="fixed" <?= $settings['default_markup_type'] === 'fixed' ? 'selected' : '' ?>>
                            Nominal Tetap (Rp)
                        </option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Nilai Markup Default</label>
                    <input type="number" name="default_markup_value" class="form-control" 
                           value="<?= e($settings['default_markup_value']) ?>" step="0.01" min="0">
                    <p class="form-text">Nilai markup yang akan terisi otomatis saat membuat item pesanan.</p>
                </div>
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
</style>
