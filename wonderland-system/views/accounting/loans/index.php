<?php
/**
 * Loan Index View
 * File: /views/accounting/loans/index.php
 * 
 * FIXED: Menggunakan cash_debit/cash_credit tanpa dibagi 2
 */

$transactions = $transactions ?? [];
$summary = $summary ?? ['total_received' => 0, 'total_repaid' => 0, 'outstanding' => 0, 'lender_count' => 0];
$outstandingLoans = $outstandingLoans ?? [];
$filter = $filter ?? 'all';
$search = $search ?? '';
$pagination = $pagination ?? [];
?>

<!-- Summary Cards -->
<div class="row mb-4">
    <div class="col-md-6 col-xl-3 mb-3">
        <div class="stat-card">
            <div class="stat-icon green">
                <i class="fas fa-arrow-circle-down"></i>
            </div>
            <div class="stat-content">
                <div class="stat-label">Total Pinjaman Masuk</div>
                <div class="stat-value"><?= formatRupiah($summary['total_received']) ?></div>
            </div>
        </div>
    </div>
    <div class="col-md-6 col-xl-3 mb-3">
        <div class="stat-card">
            <div class="stat-icon orange">
                <i class="fas fa-arrow-circle-up"></i>
            </div>
            <div class="stat-content">
                <div class="stat-label">Total Sudah Dibayar</div>
                <div class="stat-value"><?= formatRupiah($summary['total_repaid']) ?></div>
            </div>
        </div>
    </div>
    <div class="col-md-6 col-xl-3 mb-3">
        <div class="stat-card">
            <div class="stat-icon red">
                <i class="fas fa-balance-scale"></i>
            </div>
            <div class="stat-content">
                <div class="stat-label">Sisa Hutang</div>
                <div class="stat-value"><?= formatRupiah($summary['outstanding']) ?></div>
            </div>
        </div>
    </div>
    <div class="col-md-6 col-xl-3 mb-3">
        <div class="stat-card">
            <div class="stat-icon blue">
                <i class="fas fa-users"></i>
            </div>
            <div class="stat-content">
                <div class="stat-label">Pemberi Pinjaman</div>
                <div class="stat-value"><?= number_format($summary['lender_count']) ?> Orang</div>
            </div>
        </div>
    </div>
</div>

<?php if (!empty($outstandingLoans)): ?>
<!-- Outstanding Loans per Lender -->
<div class="glass-card mb-4">
    <h4 class="card-title mb-3">
        <i class="fas fa-user-clock text-warning"></i>
        Hutang per Pemberi Pinjaman
    </h4>
    <div class="table-responsive">
        <table class="table table-sm">
            <thead>
                <tr>
                    <th>Nama Pemberi Pinjaman</th>
                    <th class="text-right">Total Pinjaman</th>
                    <th class="text-right">Sudah Dibayar</th>
                    <th class="text-right">Sisa Hutang</th>
                    <th width="120">Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($outstandingLoans as $loan): ?>
                <tr>
                    <td>
                        <i class="fas fa-user text-primary"></i>
                        <strong><?= e($loan['lender_name']) ?></strong>
                    </td>
                    <td class="text-right"><?= formatRupiah($loan['total_received']) ?></td>
                    <td class="text-right text-success"><?= formatRupiah($loan['total_paid']) ?></td>
                    <td class="text-right text-danger font-semibold"><?= formatRupiah($loan['outstanding']) ?></td>
                    <td>
                        <a href="<?= url('/loans/repay?lender=' . urlencode($loan['lender_name']) . '&amount=' . $loan['outstanding']) ?>" 
                           class="btn btn-sm btn-warning">
                            <i class="fas fa-money-bill"></i> Bayar
                        </a>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?php endif; ?>

<!-- Filter & Search -->
<div class="glass-card mb-4">
    <form method="GET" action="<?= url('/loans') ?>" class="row align-items-end">
        <div class="col-md-3 mb-3 mb-md-0">
            <label class="form-label">Filter Tipe</label>
            <select name="filter" class="form-control" onchange="this.form.submit()">
                <option value="all" <?= $filter === 'all' ? 'selected' : '' ?>>Semua Transaksi</option>
                <option value="received" <?= $filter === 'received' ? 'selected' : '' ?>>Pinjaman Masuk</option>
                <option value="repaid" <?= $filter === 'repaid' ? 'selected' : '' ?>>Pembayaran</option>
            </select>
        </div>
        <div class="col-md-6 mb-3 mb-md-0">
            <label class="form-label">Cari</label>
            <div class="input-group">
                <input type="text" name="search" class="form-control" 
                       placeholder="Cari nama pemberi pinjaman, referensi..." 
                       value="<?= e($search) ?>">
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-search"></i>
                </button>
            </div>
        </div>
        <div class="col-md-3">
            <?php if (!empty($search) || $filter !== 'all'): ?>
                <a href="<?= url('/loans') ?>" class="btn btn-secondary w-100">
                    <i class="fas fa-times"></i> Reset
                </a>
            <?php endif; ?>
        </div>
    </form>
</div>

<!-- Transaction List -->
<div class="glass-card">
    <h4 class="card-title mb-3">
        <i class="fas fa-history text-primary"></i>
        Riwayat Transaksi
    </h4>
    
    <?php if (empty($transactions)): ?>
    <div class="empty-state">
        <div class="empty-state-icon">
            <i class="fas fa-hand-holding-usd"></i>
        </div>
        <h4 class="empty-state-title">Belum Ada Transaksi Pinjaman</h4>
        <p class="empty-state-text">Mulai dengan menerima pinjaman pertama.</p>
        <a href="<?= url('/loans/receive') ?>" class="btn btn-success">
            <i class="fas fa-plus-circle"></i> Terima Pinjaman
        </a>
    </div>
    <?php else: ?>
    <div class="table-responsive">
        <table class="table">
            <thead>
                <tr>
                    <th width="100">Tanggal</th>
                    <th width="130">No. Jurnal</th>
                    <th>Keterangan</th>
                    <th width="90" class="text-center">Tipe</th>
                    <th width="140" class="text-right">Jumlah</th>
                    <th width="80" class="text-center">Status</th>
                    <th width="70" class="text-center">Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($transactions as $trx): ?>
                <?php 
                    $isReceived = $trx['transaction_type'] === 'received';
                    // FIXED: Gunakan cash_debit/cash_credit TANPA dibagi 2
                    // cash_debit = uang masuk ke kas (pinjaman diterima)
                    // cash_credit = uang keluar dari kas (bayar pinjaman)
                    $amount = $isReceived 
                        ? (float)($trx['cash_debit'] ?? 0) 
                        : (float)($trx['cash_credit'] ?? 0);
                ?>
                <tr>
                    <td><?= date('d/m/Y', strtotime($trx['journal_date'])) ?></td>
                    <td><code class="text-primary"><?= e($trx['journal_number']) ?></code></td>
                    <td>
                        <?= e($trx['description']) ?>
                        <?php if (!empty($trx['reference'])): ?>
                            <br><small class="text-muted">Ref: <?= e($trx['reference']) ?></small>
                        <?php endif; ?>
                    </td>
                    <td class="text-center">
                        <?php if ($isReceived): ?>
                            <span class="badge badge-success">
                                <i class="fas fa-arrow-down"></i> Masuk
                            </span>
                        <?php else: ?>
                            <span class="badge badge-warning">
                                <i class="fas fa-arrow-up"></i> Bayar
                            </span>
                        <?php endif; ?>
                    </td>
                    <td class="text-right font-semibold <?= $isReceived ? 'text-success' : 'text-warning' ?>">
                        <?= $isReceived ? '+' : '-' ?> <?= formatRupiah($amount) ?>
                    </td>
                    <td class="text-center">
                        <?php if ($trx['status'] === 'posted'): ?>
                            <span class="badge badge-success">Posted</span>
                        <?php elseif ($trx['status'] === 'void'): ?>
                            <span class="badge badge-danger">Void</span>
                        <?php else: ?>
                            <span class="badge badge-secondary"><?= e($trx['status']) ?></span>
                        <?php endif; ?>
                    </td>
                    <td class="text-center">
                        <?php if ($trx['status'] === 'posted'): ?>
                            <div class="dropdown">
                                <button class="btn btn-sm btn-icon btn-secondary" data-bs-toggle="dropdown">
                                    <i class="fas fa-ellipsis-v"></i>
                                </button>
                                <ul class="dropdown-menu dropdown-menu-end">
                                    <li>
                                        <a class="dropdown-item" href="<?= url('/accounting/journals/' . $trx['id']) ?>">
                                            <i class="fas fa-eye text-primary"></i> Lihat Jurnal
                                        </a>
                                    </li>
                                    <li><hr class="dropdown-divider"></li>
                                    <li>
                                        <form method="POST" action="<?= url('/loans/' . $trx['id'] . '/void') ?>" 
                                              onsubmit="return confirm('Yakin ingin void transaksi ini?')">
                                            <?= csrfField() ?>
                                            <button type="submit" class="dropdown-item text-danger">
                                                <i class="fas fa-ban"></i> Void
                                            </button>
                                        </form>
                                    </li>
                                </ul>
                            </div>
                        <?php else: ?>
                            <span class="text-muted">-</span>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    
    <!-- Pagination -->
    <?php if (($pagination['last_page'] ?? 1) > 1): ?>
    <div class="d-flex justify-content-between align-items-center p-3 border-top">
        <small class="text-muted">
            Menampilkan <?= $pagination['from'] ?> - <?= $pagination['to'] ?> dari <?= $pagination['total'] ?>
        </small>
        <nav>
            <ul class="pagination pagination-sm mb-0">
                <?php if ($pagination['current_page'] > 1): ?>
                    <li class="page-item">
                        <a class="page-link" href="<?= url('/loans?page=' . ($pagination['current_page'] - 1) . '&filter=' . $filter . '&search=' . urlencode($search)) ?>">
                            &laquo;
                        </a>
                    </li>
                <?php endif; ?>
                
                <?php for ($i = max(1, $pagination['current_page'] - 2); $i <= min($pagination['last_page'], $pagination['current_page'] + 2); $i++): ?>
                    <li class="page-item <?= $i === $pagination['current_page'] ? 'active' : '' ?>">
                        <a class="page-link" href="<?= url('/loans?page=' . $i . '&filter=' . $filter . '&search=' . urlencode($search)) ?>">
                            <?= $i ?>
                        </a>
                    </li>
                <?php endfor; ?>
                
                <?php if ($pagination['current_page'] < $pagination['last_page']): ?>
                    <li class="page-item">
                        <a class="page-link" href="<?= url('/loans?page=' . ($pagination['current_page'] + 1) . '&filter=' . $filter . '&search=' . urlencode($search)) ?>">
                            &raquo;
                        </a>
                    </li>
                <?php endif; ?>
            </ul>
        </nav>
    </div>
    <?php endif; ?>
    <?php endif; ?>
</div>