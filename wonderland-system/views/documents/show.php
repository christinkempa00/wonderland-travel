<?php
/**
 * Document Detail View
 */

$typeInfo = $document->getTypeInfo();
?>

<div class="row">
    <div class="col-6">
        <div class="glass-card mb-4">
            <div class="d-flex align-items-start justify-content-between mb-4">
                <div>
                    <span class="badge badge-<?= $typeInfo['color'] ?> mb-2">
                        <?= $typeInfo['label'] ?>
                    </span>
                    <h3 class="mb-1"><?= e($document->doc_number) ?></h3>
                    <p class="text-muted mb-0">Dibuat pada <?= formatDateTime($document->created_at) ?></p>
                </div>
            </div>
            
            <div class="info-grid">
                <div class="info-item">
                    <label>Tanggal Dokumen</label>
                    <span><?= formatDateIndo($document->doc_date) ?></span>
                </div>
                <?php if ($document->due_date): ?>
                <div class="info-item">
                    <label>Jatuh Tempo</label>
                    <span><?= formatDateIndo($document->due_date) ?></span>
                </div>
                <?php endif; ?>
                <?php if ($order): ?>
                <div class="info-item">
                    <label>No. Order</label>
                    <a href="<?= url('/orders/' . $document->order_id) ?>">
                        <?= e($order['order_number']) ?>
                    </a>
                </div>
                <div class="info-item">
                    <label>Klien</label>
                    <span><?= e($order['client_name'] ?? '-') ?></span>
                </div>
                <div class="info-item">
                    <label>Total</label>
                    <span class="font-semibold"><?= formatRupiah($order['total_final_price']) ?></span>
                </div>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- Items Preview -->
        <?php if (!empty($items)): ?>
        <div class="glass-card">
            <h4 class="card-title mb-4">
                <i class="fas fa-list text-primary"></i>
                Item
            </h4>
            
            <div class="table-responsive">
                <table class="table table-sm">
                    <thead>
                        <tr>
                            <th>Deskripsi</th>
                            <th class="text-center">Qty</th>
                            <th class="text-right">Harga</th>
                            <th class="text-right">Subtotal</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($items as $item): ?>
                        <tr>
                            <td><?= e($item['description']) ?></td>
                            <td class="text-center"><?= $item['quantity'] ?></td>
                            <td class="text-right"><?= formatRupiah($item['final_price']) ?></td>
                            <td class="text-right"><?= formatRupiah($item['final_price'] * $item['quantity']) ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                    <tfoot>
                        <tr class="font-semibold">
                            <td colspan="3" class="text-right">Total</td>
                            <td class="text-right"><?= formatRupiah($order['total_final_price'] ?? 0) ?></td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
        <?php endif; ?>
    </div>
    
    <div class="col-6">
        <!-- Actions -->
        <div class="glass-card mb-4">
            <h4 class="card-title mb-4">
                <i class="fas fa-cog text-primary"></i>
                Aksi
            </h4>
            
            <div class="d-flex flex-column gap-2">
                <a href="<?= url('/documents/' . $document->id . '/view') ?>" 
                   class="btn btn-primary" target="_blank">
                    <i class="fas fa-print"></i> Preview & Print
                </a>
                <a href="<?= url('/documents/' . $document->id . '/pdf') ?>" 
                   class="btn btn-secondary" target="_blank">
                    <i class="fas fa-file-pdf"></i> Download PDF
                </a>
                <?php if ($order && $order['client_phone']): ?>
                <a href="https://wa.me/<?= formatPhoneWa($order['client_phone']) ?>?text=<?= urlencode('Dokumen ' . $document->doc_number) ?>" 
                   class="btn btn-success" target="_blank">
                    <i class="fab fa-whatsapp"></i> Kirim via WhatsApp
                </a>
                <?php endif; ?>
                
                <hr>
                
                <?php if (Session::can('documents.delete')): ?>
                <button type="button" class="btn btn-outline-danger"
                        data-delete="<?= url('/documents/' . $document->id) ?>"
                        data-message="Yakin ingin menghapus dokumen ini?">
                    <i class="fas fa-trash"></i> Hapus Dokumen
                </button>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- Company Info -->
        <?php if ($company): ?>
        <div class="glass-card">
            <h4 class="card-title mb-4">
                <i class="fas fa-building text-primary"></i>
                Perusahaan
            </h4>
            
            <div class="company-info">
                <h5><?= e($company['name']) ?></h5>
                <?php if ($company['address']): ?>
                <p class="mb-1"><?= nl2br(e($company['address'])) ?></p>
                <?php endif; ?>
                <?php if ($company['phone']): ?>
                <p class="mb-1"><i class="fas fa-phone"></i> <?= e($company['phone']) ?></p>
                <?php endif; ?>
                <?php if ($company['email']): ?>
                <p class="mb-0"><i class="fas fa-envelope"></i> <?= e($company['email']) ?></p>
                <?php endif; ?>
            </div>
        </div>
        <?php endif; ?>
    </div>
</div>

<style>
.info-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 1rem;
}

.info-item {
    padding: 0.75rem;
    background: var(--gray-50);
    border-radius: var(--border-radius-sm);
}

.info-item label {
    display: block;
    font-size: 0.8rem;
    color: var(--gray-500);
    margin-bottom: 0.25rem;
}

.company-info h5 {
    margin-bottom: 0.5rem;
    color: var(--gray-800);
}

.company-info p {
    color: var(--gray-600);
    font-size: 0.9rem;
}

.company-info i {
    width: 16px;
    color: var(--gray-400);
}
</style>
