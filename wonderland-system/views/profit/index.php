<?php
/**
 * Profit Recipients List View
 */
?>

<!-- Summary -->
<div class="glass-card mb-4">
    <div class="d-flex align-items-center justify-content-between">
        <div>
            <h4 class="mb-1">Total Alokasi</h4>
            <div class="progress" style="width: 300px; height: 8px;">
                <div class="progress-bar <?= $totalPercentage > 100 ? 'bg-danger' : 'bg-success' ?>" 
                     style="width: <?= min($totalPercentage, 100) ?>%"></div>
            </div>
        </div>
        <div class="text-right">
            <div class="text-2xl font-bold <?= $totalPercentage > 100 ? 'text-danger' : 'text-success' ?>">
                <?= number_format($totalPercentage, 2) ?>%
            </div>
            <div class="text-sm text-muted">dari 100%</div>
        </div>
    </div>
    
    <?php if ($totalPercentage > 100): ?>
    <div class="alert alert-danger mt-3 mb-0">
        <i class="fas fa-exclamation-triangle"></i>
        Total persentase melebihi 100%. Harap sesuaikan alokasi.
    </div>
    <?php elseif ($totalPercentage < 100): ?>
    <div class="alert alert-warning mt-3 mb-0">
        <i class="fas fa-info-circle"></i>
        Sisa alokasi: <?= number_format(100 - $totalPercentage, 2) ?>%
    </div>
    <?php endif; ?>
</div>

<!-- Recipients List -->
<div class="glass-card">
    <?php if (empty($recipients)): ?>
    <div class="empty-state">
        <div class="empty-state-icon">
            <i class="fas fa-hand-holding-usd"></i>
        </div>
        <h4 class="empty-state-title">Belum Ada Penerima</h4>
        <p class="empty-state-text">Tambahkan penerima bagi hasil untuk mulai membagi keuntungan.</p>
        <?php if (Session::can('profit.create')): ?>
        <a href="<?= url('/profit/create') ?>" class="btn btn-primary">
            <i class="fas fa-plus"></i> Tambah Penerima
        </a>
        <?php endif; ?>
    </div>
    <?php else: ?>
    <div class="table-responsive">
        <table class="table">
            <thead>
                <tr>
                    <th>Nama</th>
                    <th>Jabatan</th>
                    <th class="text-center">Persentase</th>
                    <th class="text-right">Tertunda</th>
                    <th class="text-right">Total Dibayar</th>
                    <th>Status</th>
                    <th width="100">Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($recipients as $recipient): ?>
                <tr>
                    <td>
                        <a href="<?= url('/profit/' . $recipient['id']) ?>" class="font-medium">
                            <?= e($recipient['name']) ?>
                        </a>
                        <?php if ($recipient['phone']): ?>
                        <br><small class="text-muted"><?= e($recipient['phone']) ?></small>
                        <?php endif; ?>
                    </td>
                    <td><?= e($recipient['role'] ?: '-') ?></td>
                    <td class="text-center">
                        <span class="badge badge-primary"><?= number_format($recipient['percentage'], 2) ?>%</span>
                    </td>
                    <td class="text-right">
                        <?php if ($recipient['total_pending'] > 0): ?>
                        <span class="text-warning font-medium"><?= formatRupiah($recipient['total_pending']) ?></span>
                        <?php else: ?>
                        <span class="text-muted">-</span>
                        <?php endif; ?>
                    </td>
                    <td class="text-right text-success font-medium">
                        <?= formatRupiah($recipient['total_paid']) ?>
                    </td>
                    <td>
                        <?php if ($recipient['is_active']): ?>
                        <span class="badge badge-success">Aktif</span>
                        <?php else: ?>
                        <span class="badge badge-secondary">Nonaktif</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <div class="btn-group">
                            <a href="<?= url('/profit/' . $recipient['id']) ?>" 
                               class="btn btn-sm btn-icon btn-secondary" title="Lihat">
                                <i class="fas fa-eye"></i>
                            </a>
                            <?php if (Session::can('profit.update')): ?>
                            <a href="<?= url('/profit/' . $recipient['id'] . '/edit') ?>" 
                               class="btn btn-sm btn-icon btn-secondary" title="Edit">
                                <i class="fas fa-edit"></i>
                            </a>
                            <?php endif; ?>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php endif; ?>
</div>

<style>
.progress {
    background: var(--gray-200);
    border-radius: 4px;
    overflow: hidden;
}

.progress-bar {
    transition: width 0.3s ease;
}
</style>
