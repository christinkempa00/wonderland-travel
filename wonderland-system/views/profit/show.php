<?php
/**
 * Profit Recipient Detail View
 */
?>

<div class="row">
    <div class="col-6">
        <!-- Recipient Info -->
        <div class="glass-card mb-4">
            <div class="d-flex align-items-start justify-content-between mb-4">
                <div>
                    <h3 class="mb-1"><?= e($recipient->name) ?></h3>
                    <?php if ($recipient->role): ?>
                    <p class="text-muted mb-0"><?= e($recipient->role) ?></p>
                    <?php endif; ?>
                </div>
                <span class="badge badge-<?= $recipient->is_active ? 'success' : 'secondary' ?>">
                    <?= $recipient->is_active ? 'Aktif' : 'Nonaktif' ?>
                </span>
            </div>
            
            <div class="info-grid">
                <div class="info-item">
                    <label>Persentase</label>
                    <span class="font-semibold text-primary"><?= number_format($recipient->percentage, 2) ?>%</span>
                </div>
                <?php if ($recipient->phone): ?>
                <div class="info-item">
                    <label>Telepon</label>
                    <a href="https://wa.me/<?= formatPhoneWa($recipient->phone) ?>" target="_blank" class="text-success">
                        <i class="fab fa-whatsapp"></i> <?= e($recipient->phone) ?>
                    </a>
                </div>
                <?php endif; ?>
                <?php if ($recipient->email): ?>
                <div class="info-item">
                    <label>Email</label>
                    <a href="mailto:<?= e($recipient->email) ?>"><?= e($recipient->email) ?></a>
                </div>
                <?php endif; ?>
                <?php if ($recipient->bank_name): ?>
                <div class="info-item full-width">
                    <label>Rekening Bank</label>
                    <span>
                        <?= e($recipient->bank_name) ?> - <?= e($recipient->bank_account) ?>
                        <br><small class="text-muted">a.n. <?= e($recipient->bank_holder) ?></small>
                    </span>
                </div>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- Stats -->
        <div class="row mb-4">
            <div class="col-6">
                <div class="stat-card">
                    <div class="stat-icon yellow">
                        <i class="fas fa-clock"></i>
                    </div>
                    <div class="stat-content">
                        <div class="stat-label">Tertunda</div>
                        <div class="stat-value"><?= formatRupiah($totalPending) ?></div>
                    </div>
                </div>
            </div>
            <div class="col-6">
                <div class="stat-card">
                    <div class="stat-icon green">
                        <i class="fas fa-check-circle"></i>
                    </div>
                    <div class="stat-content">
                        <div class="stat-label">Total Dibayar</div>
                        <div class="stat-value"><?= formatRupiah($totalPaid) ?></div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Pending Distributions -->
        <div class="glass-card">
            <div class="d-flex align-items-center justify-content-between mb-4">
                <h4 class="card-title mb-0">
                    <i class="fas fa-clock text-warning"></i>
                    Pembayaran Tertunda
                </h4>
                <?php if (!empty($pendingDistributions)): ?>
                <button type="button" class="btn btn-sm btn-success" id="markPaidBtn">
                    <i class="fas fa-check"></i> Tandai Lunas
                </button>
                <?php endif; ?>
            </div>
            
            <?php if (empty($pendingDistributions)): ?>
            <div class="text-center py-4 text-muted">
                <i class="fas fa-check-circle fa-2x mb-2 text-success"></i>
                <p class="mb-0">Semua pembayaran sudah lunas!</p>
            </div>
            <?php else: ?>
            <div class="table-responsive">
                <table class="table table-sm">
                    <thead>
                        <tr>
                            <th>
                                <input type="checkbox" id="selectAll">
                            </th>
                            <th>Order</th>
                            <th>Event</th>
                            <th class="text-right">Jumlah</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($pendingDistributions as $dist): ?>
                        <tr>
                            <td>
                                <input type="checkbox" class="share-checkbox" value="<?= $dist['id'] ?>">
                            </td>
                            <td>
                                <a href="<?= url('/orders/' . $dist['order_id']) ?>">
                                    <?= e($dist['order_number']) ?>
                                </a>
                            </td>
                            <td><?= e(truncate($dist['event_name'], 30)) ?></td>
                            <td class="text-right font-medium"><?= formatRupiah($dist['amount']) ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php endif; ?>
        </div>
    </div>
    
    <div class="col-6">
        <!-- Actions -->
        <div class="glass-card mb-4">
            <h4 class="card-title mb-4">
                <i class="fas fa-cog text-primary"></i>
                Aksi
            </h4>
            
            <div class="d-flex flex-column gap-2">
                <?php if (Session::can('profit.update')): ?>
                <a href="<?= url('/profit/' . $recipient->id . '/edit') ?>" class="btn btn-primary">
                    <i class="fas fa-edit"></i> Edit
                </a>
                <?php endif; ?>
                
                <?php if ($recipient->phone): ?>
                <button type="button" class="btn btn-success" id="notifyBtn">
                    <i class="fab fa-whatsapp"></i> Kirim Notifikasi WA
                </button>
                <?php endif; ?>
                
                <a href="<?= url('/profit') ?>" class="btn btn-secondary">
                    <i class="fas fa-arrow-left"></i> Kembali
                </a>
            </div>
        </div>
        
        <!-- History -->
        <div class="glass-card">
            <h4 class="card-title mb-4">
                <i class="fas fa-history text-primary"></i>
                Riwayat
            </h4>
            
            <?php if (empty($history)): ?>
            <p class="text-muted text-center py-2">Belum ada riwayat.</p>
            <?php else: ?>
            <div class="history-list">
                <?php foreach ($history as $item): ?>
                <div class="history-item">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <a href="<?= url('/orders/' . $item['order_id']) ?>">
                                <?= e($item['order_number']) ?>
                            </a>
                            <br>
                            <small class="text-muted"><?= formatDate($item['created_at']) ?></small>
                        </div>
                        <div class="text-right">
                            <span class="font-medium"><?= formatRupiah($item['amount']) ?></span>
                            <br>
                            <span class="badge badge-<?= $item['status'] === 'paid' ? 'success' : 'warning' ?>">
                                <?= $item['status'] === 'paid' ? 'Lunas' : 'Tertunda' ?>
                            </span>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </div>
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

.info-item.full-width {
    grid-column: 1 / -1;
}

.info-item label {
    display: block;
    font-size: 0.8rem;
    color: var(--gray-500);
    margin-bottom: 0.25rem;
}

.history-list {
    max-height: 400px;
    overflow-y: auto;
}

.history-item {
    padding: 0.75rem;
    border-bottom: 1px solid var(--gray-200);
}

.history-item:last-child {
    border-bottom: none;
}
</style>

<script>
// Select all checkbox
document.getElementById('selectAll')?.addEventListener('change', function() {
    document.querySelectorAll('.share-checkbox').forEach(cb => {
        cb.checked = this.checked;
    });
});

// Mark as paid
document.getElementById('markPaidBtn')?.addEventListener('click', function() {
    const selected = [];
    document.querySelectorAll('.share-checkbox:checked').forEach(cb => {
        selected.push(parseInt(cb.value));
    });
    
    if (selected.length === 0) {
        Toast.warning('Perhatian', 'Pilih minimal satu item.');
        return;
    }
    
    if (!confirm('Tandai ' + selected.length + ' distribusi sebagai lunas?')) return;
    
    fetch('<?= url('/profit/' . $recipient->id . '/mark-paid') ?>', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': '<?= Session::getCsrfToken() ?>',
            'X-Requested-With': 'XMLHttpRequest'
        },
        body: JSON.stringify({ share_ids: selected })
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            Toast.success('Berhasil', data.message);
            setTimeout(() => location.reload(), 1000);
        } else {
            Toast.error('Gagal', data.message);
        }
    });
});

// Send notification
document.getElementById('notifyBtn')?.addEventListener('click', function() {
    if (!confirm('Kirim notifikasi WhatsApp ke <?= e($recipient->name) ?>?')) return;
    
    fetch('<?= url('/profit/' . $recipient->id . '/notify') ?>', {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': '<?= Session::getCsrfToken() ?>',
            'X-Requested-With': 'XMLHttpRequest'
        }
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            Toast.success('Berhasil', data.message);
        } else {
            Toast.error('Gagal', data.message);
        }
    });
});
</script>
