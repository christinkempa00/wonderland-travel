<?php
/**
 * Bank & Cash List View
 */
?>

<!-- Summary Cards -->
<div class="row mb-4">
    <div class="col-4">
        <div class="stat-card">
            <div class="stat-icon green">
                <i class="fas fa-wallet"></i>
            </div>
            <div class="stat-content">
                <div class="stat-label">Total Saldo</div>
                <div class="stat-value"><?= formatRupiah($totalBalance) ?></div>
            </div>
        </div>
    </div>
</div>

<!-- Bank/Cash List -->
<div class="glass-card">
    <?php if (empty($bankCash)): ?>
    <div class="empty-state">
        <div class="empty-state-icon">
            <i class="fas fa-university"></i>
        </div>
        <h4 class="empty-state-title">Belum Ada Kas/Bank</h4>
        <p class="empty-state-text">Tambahkan kas atau rekening bank untuk mengelola keuangan.</p>
        <?php if (Session::can('accounting.create')): ?>
        <a href="<?= url('/accounting/bank-cash/create') ?>" class="btn btn-primary">
            <i class="fas fa-plus"></i> Tambah Kas/Bank
        </a>
        <?php endif; ?>
    </div>
    <?php else: ?>
    <div class="table-responsive">
        <table class="table">
            <thead>
                <tr>
                    <th>Nama</th>
                    <th>Tipe</th>
                    <th>Bank</th>
                    <th>No. Rekening</th>
                    <th>Akun</th>
                    <th class="text-right">Saldo</th>
                    <th>Status</th>
                    <th width="100">Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($bankCash as $bc): ?>
                <tr>
                    <td>
                        <a href="<?= url('/accounting/bank-cash/' . $bc['id']) ?>" class="font-medium">
                            <?= e($bc['name']) ?>
                        </a>
                        <?php if ($bc['is_default']): ?>
                        <span class="badge badge-info ms-1">Default</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <span class="badge badge-<?= $bc['type'] === 'bank' ? 'primary' : 'success' ?>">
                            <?= $bc['type'] === 'bank' ? 'Bank' : 'Kas' ?>
                        </span>
                    </td>
                    <td><?= e($bc['bank_name'] ?: '-') ?></td>
                    <td><?= e($bc['account_number'] ?: '-') ?></td>
                    <td>
                        <?php if ($bc['account_code']): ?>
                        <code><?= e($bc['account_code']) ?></code>
                        <?php else: ?>
                        -
                        <?php endif; ?>
                    </td>
                    <td class="text-right font-semibold <?= ($bc['balance'] ?? 0) >= 0 ? 'text-success' : 'text-danger' ?>">
                        <?= formatRupiah($bc['balance'] ?? 0) ?>
                    </td>
                    <td>
                        <?php if ($bc['is_active']): ?>
                        <span class="badge badge-success">Aktif</span>
                        <?php else: ?>
                        <span class="badge badge-secondary">Nonaktif</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <div class="btn-group">
                            <a href="<?= url('/accounting/bank-cash/' . $bc['id']) ?>" 
                               class="btn btn-sm btn-icon btn-secondary" title="Lihat">
                                <i class="fas fa-eye"></i>
                            </a>
                            <?php if (Session::can('accounting.update')): ?>
                            <a href="<?= url('/accounting/bank-cash/' . $bc['id'] . '/edit') ?>" 
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
