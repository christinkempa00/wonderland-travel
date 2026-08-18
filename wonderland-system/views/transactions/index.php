<?php
/**
 * Transaction Index View
 * File: /views/transactions/index.php
 */
?>

<!-- Filter Section -->
<div class="glass-card mb-4">
    <form method="GET" class="row g-3 align-items-end">
        <div class="col-md-3">
            <label class="form-label">Pencarian</label>
            <input type="text" name="search" class="form-control" 
                   placeholder="Cari deskripsi, referensi..."
                   value="<?= e($filters['search'] ?? '') ?>">
        </div>
        <div class="col-md-2">
            <label class="form-label">Tipe</label>
            <select name="type" class="form-control">
                <option value="">Semua</option>
                <option value="expense" <?= ($filters['type'] ?? '') === 'expense' ? 'selected' : '' ?>>Pengeluaran</option>
                <option value="income" <?= ($filters['type'] ?? '') === 'income' ? 'selected' : '' ?>>Pemasukan</option>
            </select>
        </div>
        <div class="col-md-2">
            <label class="form-label">Dari Tanggal</label>
            <input type="date" name="date_from" class="form-control" 
                   value="<?= e($filters['date_from'] ?? '') ?>">
        </div>
        <div class="col-md-2">
            <label class="form-label">Sampai Tanggal</label>
            <input type="date" name="date_to" class="form-control" 
                   value="<?= e($filters['date_to'] ?? '') ?>">
        </div>
        <div class="col-md-3">
            <button type="submit" class="btn btn-primary">
                <i class="fas fa-search"></i> Filter
            </button>
            <a href="<?= url('/transactions') ?>" class="btn btn-secondary">
                <i class="fas fa-times"></i> Reset
            </a>
        </div>
    </form>
</div>

<!-- Summary Cards -->
<div class="row mb-4">
    <div class="col-md-4">
        <div class="glass-card text-center">
            <h6 class="text-muted mb-2">Total Pemasukan</h6>
            <h3 class="text-success mb-0"><?= formatRupiah($totals['total_income'] ?? 0) ?></h3>
        </div>
    </div>
    <div class="col-md-4">
        <div class="glass-card text-center">
            <h6 class="text-muted mb-2">Total Pengeluaran</h6>
            <h3 class="text-danger mb-0"><?= formatRupiah($totals['total_expense'] ?? 0) ?></h3>
        </div>
    </div>
    <div class="col-md-4">
        <div class="glass-card text-center">
            <h6 class="text-muted mb-2">Selisih</h6>
            <?php $selisih = ($totals['total_income'] ?? 0) - ($totals['total_expense'] ?? 0); ?>
            <h3 class="text-<?= $selisih >= 0 ? 'success' : 'danger' ?> mb-0">
                <?= formatRupiah($selisih) ?>
            </h3>
        </div>
    </div>
</div>

<!-- Transaction List -->
<div class="glass-card">
    <div class="table-responsive">
        <table class="table table-hover">
            <thead>
                <tr>
                    <th>No. Transaksi</th>
                    <th>Tanggal</th>
                    <th>Tipe</th>
                    <th>Deskripsi</th>
                    <th>Akun</th>
                    <th class="text-end">Jumlah</th>
                    <th>Status</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($transactions)): ?>
                <tr>
                    <td colspan="8" class="text-center py-5 text-muted">
                        <i class="fas fa-inbox fa-3x mb-3 d-block opacity-50"></i>
                        Belum ada transaksi
                    </td>
                </tr>
                <?php else: ?>
                <?php foreach ($transactions as $trans): ?>
                <tr>
                    <td>
                        <a href="<?= url('/transactions/' . $trans['id']) ?>" class="fw-bold">
                            <?= e($trans['transaction_number']) ?>
                        </a>
                    </td>
                    <td><?= formatDate($trans['transaction_date'], 'd/m/Y') ?></td>
                    <td>
                        <?php if ($trans['transaction_type'] === 'expense'): ?>
                        <span class="badge bg-danger">
                            <i class="fas fa-minus-circle"></i> Pengeluaran
                        </span>
                        <?php else: ?>
                        <span class="badge bg-success">
                            <i class="fas fa-plus-circle"></i> Pemasukan
                        </span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <?= e($trans['description']) ?>
                        <?php if ($trans['reference']): ?>
                        <br><small class="text-muted">Ref: <?= e($trans['reference']) ?></small>
                        <?php endif; ?>
                    </td>
                    <td>
                        <small>
                            <?= e($trans['account_code']) ?><br>
                            <?= e($trans['account_name']) ?>
                        </small>
                    </td>
                    <td class="text-end fw-bold text-<?= $trans['transaction_type'] === 'expense' ? 'danger' : 'success' ?>">
                        <?= $trans['transaction_type'] === 'expense' ? '-' : '+' ?>
                        <?= formatRupiah($trans['amount']) ?>
                    </td>
                    <td>
                        <?php if ($trans['status'] === 'void'): ?>
                        <span class="badge bg-secondary">Dibatalkan</span>
                        <?php else: ?>
                        <span class="badge bg-success">Posted</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <div class="btn-group btn-group-sm">
                            <a href="<?= url('/transactions/' . $trans['id']) ?>" 
                               class="btn btn-outline-primary" title="Detail">
                                <i class="fas fa-eye"></i>
                            </a>
                            <?php if ($trans['status'] !== 'void'): ?>
                            <form method="POST" action="<?= url('/transactions/' . $trans['id'] . '/void') ?>"
                                  onsubmit="return confirm('Yakin batalkan transaksi ini?');" style="display:inline;">
                                <?= csrfField() ?>
                                <button type="submit" class="btn btn-outline-danger" title="Batalkan">
                                    <i class="fas fa-times"></i>
                                </button>
                            </form>
                            <?php endif; ?>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
    
    <!-- Pagination -->
    <?php if ($pagination['last_page'] > 1): ?>
    <nav class="mt-4">
        <ul class="pagination justify-content-center mb-0">
            <?php for ($i = 1; $i <= $pagination['last_page']; $i++): ?>
            <li class="page-item <?= $i == $pagination['current_page'] ? 'active' : '' ?>">
                <a class="page-link" href="<?= url('/transactions', array_merge($filters, ['page' => $i])) ?>">
                    <?= $i ?>
                </a>
            </li>
            <?php endfor; ?>
        </ul>
    </nav>
    <?php endif; ?>
</div>
