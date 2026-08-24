<?php
/**
 * Client Form View (Create/Edit)
 */

$data = $client ? $client->toArray() : [];
?>

<div class="row">
    <div class="col-6">
        <div class="glass-card">
            <form method="POST" 
                  action="<?= $isEdit ? url('/clients/' . $client->id) : url('/clients') ?>" 
                  id="clientForm">
                <?= csrfField() ?>
                <?php if ($isEdit): ?>
                <?= methodField('PUT') ?>
                <?php endif; ?>
                
                <div class="form-group">
                    <label for="name" class="form-label required">Nama Klien</label>
                    <input type="text" id="name" name="name" class="form-control" 
                           value="<?= e(old('name', $data['name'] ?? '')) ?>" 
                           placeholder="PT. Contoh Perusahaan" required>
                </div>
                
                <div class="form-group">
                    <label for="contact_person" class="form-label">Kontak Person</label>
                    <input type="text" id="contact_person" name="contact_person" class="form-control" 
                           value="<?= e(old('contact_person', $data['contact_person'] ?? '')) ?>" 
                           placeholder="Nama penanggung jawab">
                </div>
                
                <div class="row">
                    <div class="col-6">
                        <div class="form-group">
                            <label for="email" class="form-label">Email</label>
                            <input type="email" id="email" name="email" class="form-control" 
                                   value="<?= e(old('email', $data['email'] ?? '')) ?>" 
                                   placeholder="email@perusahaan.com">
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="form-group">
                            <label for="phone" class="form-label">No. Telepon</label>
                            <input type="text" id="phone" name="phone" class="form-control" 
                                   value="<?= e(old('phone', $data['phone'] ?? '')) ?>" 
                                   placeholder="08123456789">
                        </div>
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="address" class="form-label">Alamat</label>
                    <textarea id="address" name="address" class="form-control" rows="3" 
                              placeholder="Alamat lengkap"><?= e(old('address', $data['address'] ?? '')) ?></textarea>
                </div>
                
                <div class="form-group">
                    <label for="npwp" class="form-label">NPWP</label>
                    <input type="text" id="npwp" name="npwp" class="form-control" 
                           value="<?= e(old('npwp', $data['npwp'] ?? '')) ?>" 
                           placeholder="00.000.000.0-000.000">
                </div>
                
                <div class="form-group">
                    <div class="form-check">
                        <input type="checkbox" id="uses_divisi" name="uses_divisi" class="form-check-input" value="1"
                               <?= old('uses_divisi', $data['uses_divisi'] ?? 0) ? 'checked' : '' ?>>
                        <label for="uses_divisi" class="form-check-label">
                            Klien PELNI (aktifkan dropdown Divisi &amp; penomoran invoice khusus di form pesanan)
                        </label>
                    </div>
                </div>

                <?php if ($isEdit && $client): ?>
                <div class="form-group" style="border-top: 1px solid var(--gray-200); padding-top: 1rem;">
                    <label class="form-label">Portal Klien</label>
                    <div class="form-check mb-2">
                        <input type="checkbox" id="portal_enabled" name="portal_enabled" class="form-check-input" value="1"
                               <?= old('portal_enabled', $data['portal_enabled'] ?? 0) ? 'checked' : '' ?>>
                        <label for="portal_enabled" class="form-check-label">
                            Aktifkan akses Portal Klien (klien bisa lihat tagihan &amp; riwayat pesanan sendiri)
                        </label>
                    </div>
                    <div class="text-sm text-muted mb-2">
                        Kode Klien untuk login: <strong><?= e($data['client_code'] ?? '-') ?></strong>
                        <br>Klien login cukup dengan kode ini, tanpa password.
                    </div>
                </div>
                <?php endif; ?>

                <div class="form-group">
                    <label for="notes" class="form-label">Catatan</label>
                    <textarea id="notes" name="notes" class="form-control" rows="2" 
                              placeholder="Catatan tambahan (opsional)"><?= e(old('notes', $data['notes'] ?? '')) ?></textarea>
                </div>
                
                <div class="d-flex gap-3 mt-4">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i>
                        <?= $isEdit ? 'Simpan Perubahan' : 'Simpan' ?>
                    </button>
                    <a href="<?= $isEdit ? url('/clients/' . $client->id) : url('/clients') ?>" 
                       class="btn btn-secondary">
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
                <i class="fas fa-lightbulb"></i>
                <div>
                    <strong>Tips:</strong>
                    <ul class="mb-0 mt-2" style="padding-left: 1.25rem;">
                        <li>Nama klien akan muncul pada dokumen penawaran dan invoice</li>
                        <li>Kontak person memudahkan komunikasi langsung</li>
                        <li>No. telepon dengan format +62 akan otomatis terformat untuk WhatsApp</li>
                        <li>NPWP diperlukan untuk penerbitan faktur pajak</li>
                    </ul>
                </div>
            </div>
            
            <?php if ($isEdit && $client): ?>
            <div class="mt-4 pt-4" style="border-top: 1px solid var(--gray-200);">
                <h5 class="mb-3">Statistik Klien</h5>
                <div class="d-flex gap-4">
                    <div>
                        <div class="text-sm text-muted">Total Pesanan</div>
                        <div class="font-semibold text-lg"><?= $client->getOrderCount() ?></div>
                    </div>
                    <div>
                        <div class="text-sm text-muted">Total Transaksi</div>
                        <div class="font-semibold text-lg"><?= formatRupiah($client->getTotalRevenue()) ?></div>
                    </div>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>
