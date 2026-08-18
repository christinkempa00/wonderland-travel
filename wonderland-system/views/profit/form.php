<?php
/**
 * Profit Recipient Form View
 */

$data = $recipient ? $recipient->toArray() : ['is_active' => 1];
?>

<div class="row">
    <div class="col-6">
        <div class="glass-card">
            <form method="POST" 
                  action="<?= $isEdit ? url('/profit/' . $recipient->id) : url('/profit') ?>"
                  id="recipientForm">
                <?= csrfField() ?>
                <?php if ($isEdit): ?>
                <?= methodField('PUT') ?>
                <?php endif; ?>
                
                <h4 class="card-title mb-4">
                    <i class="fas fa-user text-primary"></i>
                    Informasi Penerima
                </h4>
                
                <div class="form-group">
                    <label class="form-label required">Nama</label>
                    <input type="text" name="name" class="form-control" 
                           value="<?= e(old('name', $data['name'] ?? '')) ?>" 
                           placeholder="Nama penerima" required>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Jabatan / Role</label>
                    <input type="text" name="role" class="form-control" 
                           value="<?= e(old('role', $data['role'] ?? '')) ?>" 
                           placeholder="Contoh: Komisaris, Owner">
                </div>
                
                <div class="row">
                    <div class="col-6">
                        <div class="form-group">
                            <label class="form-label">No. Telepon</label>
                            <input type="text" name="phone" class="form-control" 
                                   value="<?= e(old('phone', $data['phone'] ?? '')) ?>" 
                                   placeholder="08123456789">
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="form-group">
                            <label class="form-label">Email</label>
                            <input type="email" name="email" class="form-control" 
                                   value="<?= e(old('email', $data['email'] ?? '')) ?>" 
                                   placeholder="email@example.com">
                        </div>
                    </div>
                </div>
                
                <h4 class="card-title mb-4 mt-4">
                    <i class="fas fa-university text-primary"></i>
                    Informasi Bank
                </h4>
                
                <div class="form-group">
                    <label class="form-label">Nama Bank</label>
                    <input type="text" name="bank_name" class="form-control" 
                           value="<?= e(old('bank_name', $data['bank_name'] ?? '')) ?>" 
                           placeholder="BCA, Mandiri, BRI, dll">
                </div>
                
                <div class="row">
                    <div class="col-6">
                        <div class="form-group">
                            <label class="form-label">No. Rekening</label>
                            <input type="text" name="bank_account" class="form-control" 
                                   value="<?= e(old('bank_account', $data['bank_account'] ?? '')) ?>" 
                                   placeholder="1234567890">
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="form-group">
                            <label class="form-label">Atas Nama</label>
                            <input type="text" name="bank_holder" class="form-control" 
                                   value="<?= e(old('bank_holder', $data['bank_holder'] ?? '')) ?>" 
                                   placeholder="Nama pemilik rekening">
                        </div>
                    </div>
                </div>
                
                <h4 class="card-title mb-4 mt-4">
                    <i class="fas fa-percent text-primary"></i>
                    Alokasi Bagi Hasil
                </h4>
                
                <div class="form-group">
                    <label class="form-label required">Persentase (%)</label>
                    <input type="number" name="percentage" class="form-control" 
                           value="<?= e(old('percentage', $data['percentage'] ?? '')) ?>" 
                           min="0.01" max="<?= $remainingPercentage ?>" step="0.01" required>
                    <p class="form-text">Maksimal: <?= number_format($remainingPercentage, 2) ?>%</p>
                </div>
                
                <div class="form-group">
                    <label class="form-check">
                        <input type="checkbox" name="is_active" class="form-check-input" value="1"
                               <?= old('is_active', $data['is_active'] ?? 1) ? 'checked' : '' ?>>
                        <span class="form-check-label">Aktif</span>
                    </label>
                    <p class="form-text">Penerima nonaktif tidak akan menerima bagi hasil dari pesanan baru.</p>
                </div>
                
                <div class="d-flex gap-3 mt-4">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i>
                        <?= $isEdit ? 'Simpan Perubahan' : 'Simpan' ?>
                    </button>
                    <a href="<?= url('/profit') ?>" class="btn btn-secondary">
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
                Informasi
            </h4>
            
            <div class="alert alert-info">
                <strong>Cara Kerja Bagi Hasil:</strong>
                <ul class="mb-0 mt-2">
                    <li>Setiap pesanan akan dihitung profitnya (harga jual - harga dasar)</li>
                    <li>Profit dibagi sesuai persentase masing-masing penerima</li>
                    <li>Pembagian otomatis dihitung saat pesanan dibuat</li>
                    <li>Anda dapat menandai pembayaran sebagai lunas setelah transfer</li>
                </ul>
            </div>
            
            <div class="alert alert-warning">
                <i class="fas fa-exclamation-triangle"></i>
                Total persentase semua penerima tidak boleh melebihi 100%.
            </div>
        </div>
    </div>
</div>
