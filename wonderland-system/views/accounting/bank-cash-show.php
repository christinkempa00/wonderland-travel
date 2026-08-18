<?php
/**
 * Bank/Cash Detail View
 */
?>

<!-- Header Info -->
<div class="row mb-4">
    <div class="col-md-6">
        <div class="glass-card">
            <h5 class="mb-3">
                <i class="fas fa-<?= $bankCash->type === 'bank' ? 'university' : 'wallet' ?> me-2"></i>
                <?= e($bankCash->name) ?>
            </h5>
            <table class="table table-sm mb-0">
                <tr>
                    <td width="140">Tipe</td>
                    <td>
                        <span class="badge badge-<?= $bankCash->type === 'bank' ? 'primary' : 'success' ?>">
                            <?= $bankCash->type === 'bank' ? 'Bank' : 'Kas' ?>
                        </span>
                    </td>
                </tr>
                <?php if ($bankCash->type === 'bank'): ?>
                <tr>
                    <td>Nama Bank</td>
                    <td><?= e($bankCash->bank_name ?: '-') ?></td>
                </tr>
                <tr>
                    <td>No. Rekening</td>
                    <td><?= e($bankCash->account_number ?: '-') ?></td>
                </tr>
                <tr>
                    <td>Atas Nama</td>
                    <td><?= e($bankCash->account_holder ?: '-') ?></td>
                </tr>
                <?php endif; ?>
                <tr>
                    <td>Akun</td>
                    <td>
                        <?php if ($bankCash->account_id): ?>
                            <?php 
                            $account = $bankCash->getAccount();
                            echo $account ? '<code>' . e($account['code']) . '</code> ' . e($account['name']) : '-';
                            ?>
                        <?php else: ?>
                            -
                        <?php endif; ?>
                    </td>
                </tr>
                <tr>
                    <td>Status</td>
                    <td>
                        <span class="badge badge-<?= $bankCash->is_active ? 'success' : 'secondary' ?>">
                            <?= $bankCash->is_active ? 'Aktif' : 'Nonaktif' ?>
                        </span>
                        <?php if ($bankCash->is_default): ?>
                        <span class="badge badge-info">Default</span>
                        <?php endif; ?>
                    </td>
                </tr>
            </table>
        </div>
    </div>
    <div class="col-md-6">
        <div class="stat-card">
            <div class="stat-icon green">
                <i class="fas fa-money-bill-wave"></i>
            </div>
            <div class="stat-content">
                <div class="stat-label">Saldo Saat Ini</div>
                <div class="stat-value"><?= formatRupiah($bankCash->balance ?? 0) ?></div>
            </div>
        </div>
        
        <div class="mt-3">
            <a href="<?= url('/accounting/bank-cash/' . $bankCash->id . '/edit') ?>" class="btn btn-warning">
                <i class="fas fa-edit"></i> Edit
            </a>
            <a href="<?= url('/accounting/bank-cash') ?>" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i> Kembali
            </a>
        </div>
    </div>
</div>

<!-- Filter -->
<div class="glass-card mb-4">
    <form method="GET" class="row g-3">
        <div class="col-md-4">
            <label class="form-label">Dari Tanggal</label>
            <input type="date" name="start_date" class="form-control" value="<?= e($startDate) ?>">
        </div>
        <div class="col-md-4">
            <label class="form-label">Sampai Tanggal</label>
            <input type="date" name="end_date" class="form-control" value="<?= e($endDate) ?>">
        </div>
        <div class="col-md-4 d-flex align-items-end">
            <button type="submit" class="btn btn-primary">
                <i class="fas fa-filter"></i> Filter
            </button>
        </div>
    </form>
</div>

<!-- Transactions -->
<div class="glass-card">
    <h5 class="mb-3"><i class="fas fa-history me-2"></i>Riwayat Transaksi</h5>
    
    <?php if (empty($transactions)): ?>
    <div class="alert alert-info mb-0">
        <i class="fas fa-info-circle me-2"></i>
        Belum ada transaksi pada periode ini.
    </div>
    <?php else: ?>
    <div class="table-responsive">
        <table class="table">
            <thead>
                <tr>
                    <th width="100">Tanggal</th>
                    <th width="130">No. Jurnal</th>
                    <th>Keterangan</th>
                    <th class="text-end" width="130">Debit</th>
                    <th class="text-end" width="130">Kredit</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($transactions as $tx): ?>
                <tr>
                    <td><?= formatDate($tx['journal_date']) ?></td>
                    <td>
                        <a href="<?= url('/accounting/journals/' . ($tx['journal_id'] ?? '')) ?>">
                            <?= e($tx['journal_number']) ?>
                        </a>
                    </td>
                    <td><?= e($tx['description'] ?: $tx['journal_desc']) ?></td>
                    <td class="text-end"><?= ($tx['debit'] ?? 0) > 0 ? formatRupiah($tx['debit']) : '-' ?></td>
                    <td class="text-end"><?= ($tx['credit'] ?? 0) > 0 ? formatRupiah($tx['credit']) : '-' ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php endif; ?>
</div>
