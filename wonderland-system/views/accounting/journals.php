<?php
/**
 * Journals List View
 * File: /views/accounting/journals/index.php
 */

$statusColors = [
    'draft' => 'warning', 
    'posted' => 'success', 
    'void' => 'secondary'
];
$statusLabels = [
    'draft' => 'Draft', 
    'posted' => 'Posted', 
    'void' => 'Void'
];
?>

<!-- Page Actions -->
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h5 class="mb-1">Daftar Jurnal Umum</h5>
        <p class="text-muted mb-0 small">Kelola semua transaksi jurnal</p>
    </div>
    <a href="<?= url('/accounting/journals/create') ?>" class="btn btn-primary">
        <i class="fas fa-plus me-1"></i> Buat Jurnal
    </a>
</div>

<!-- Filters -->
<div class="glass-card mb-4">
    <form method="GET" action="<?= url('/accounting/journals') ?>" class="row g-3 align-items-end">
        <div class="col-md-2">
            <label class="form-label small">Dari Tanggal</label>
            <input type="date" name="start_date" class="form-control form-control-sm" 
                   value="<?= e($filters['start_date'] ?? '') ?>">
        </div>
        <div class="col-md-2">
            <label class="form-label small">Sampai Tanggal</label>
            <input type="date" name="end_date" class="form-control form-control-sm" 
                   value="<?= e($filters['end_date'] ?? '') ?>">
        </div>
        <div class="col-md-2">
            <label class="form-label small">Status</label>
            <select name="status" class="form-control form-control-sm">
                <option value="">Semua Status</option>
                <option value="draft" <?= ($filters['status'] ?? '') === 'draft' ? 'selected' : '' ?>>Draft</option>
                <option value="posted" <?= ($filters['status'] ?? '') === 'posted' ? 'selected' : '' ?>>Posted</option>
                <option value="void" <?= ($filters['status'] ?? '') === 'void' ? 'selected' : '' ?>>Void</option>
            </select>
        </div>
        <div class="col-md-3">
            <label class="form-label small">Pencarian</label>
            <input type="text" name="search" class="form-control form-control-sm" 
                   placeholder="Cari nomor, keterangan..."
                   value="<?= e($filters['search'] ?? '') ?>">
        </div>
        <div class="col-md-3">
            <button type="submit" class="btn btn-primary btn-sm">
                <i class="fas fa-search me-1"></i> Filter
            </button>
            <a href="<?= url('/accounting/journals') ?>" class="btn btn-secondary btn-sm">
                <i class="fas fa-times me-1"></i> Reset
            </a>
        </div>
    </form>
</div>

<!-- Summary Cards -->
<?php if (!empty($summary)): ?>
<div class="row g-3 mb-4">
    <div class="col-md-3">
        <div class="glass-card text-center py-3">
            <div class="text-muted small">Total Jurnal</div>
            <h4 class="mb-0 text-primary"><?= number_format($summary['total_count'] ?? 0) ?></h4>
        </div>
    </div>
    <div class="col-md-3">
        <div class="glass-card text-center py-3">
            <div class="text-muted small">Draft</div>
            <h4 class="mb-0 text-warning"><?= number_format($summary['draft_count'] ?? 0) ?></h4>
        </div>
    </div>
    <div class="col-md-3">
        <div class="glass-card text-center py-3">
            <div class="text-muted small">Posted</div>
            <h4 class="mb-0 text-success"><?= number_format($summary['posted_count'] ?? 0) ?></h4>
        </div>
    </div>
    <div class="col-md-3">
        <div class="glass-card text-center py-3">
            <div class="text-muted small">Total Nilai</div>
            <h4 class="mb-0"><?= formatRupiah($summary['total_amount'] ?? 0) ?></h4>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- Journals List -->
<div class="glass-card">
    <?php if (empty($journals)): ?>
    <div class="text-center py-5">
        <i class="fas fa-book fa-3x text-muted mb-3 opacity-50"></i>
        <h5 class="text-muted">Belum Ada Jurnal</h5>
        <p class="text-muted mb-4">Mulai buat jurnal untuk mencatat transaksi.</p>
        <a href="<?= url('/accounting/journals/create') ?>" class="btn btn-primary">
            <i class="fas fa-plus me-1"></i> Buat Jurnal Pertama
        </a>
    </div>
    <?php else: ?>
    <div class="table-responsive">
        <table class="table table-hover mb-0">
            <thead>
                <tr>
                    <th>No. Jurnal</th>
                    <th>Tanggal</th>
                    <th>Keterangan</th>
                    <th class="text-end">Debit</th>
                    <th class="text-end">Kredit</th>
                    <th class="text-center">Status</th>
                    <th width="80"></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($journals as $journal): ?>
                <tr class="<?= $journal['status'] === 'void' ? 'text-muted' : '' ?>">
                    <td>
                        <a href="<?= url('/accounting/journals/' . $journal['id']) ?>" class="fw-medium text-decoration-none">
                            <?= e($journal['journal_number']) ?>
                        </a>
                        <?php if (!empty($journal['reference'])): ?>
                        <br><small class="text-muted">Ref: <?= e($journal['reference']) ?></small>
                        <?php endif; ?>
                    </td>
                    <td>
                        <?= formatDate($journal['journal_date'], 'd/m/Y') ?>
                    </td>
                    <td>
                        <?php 
                        $desc = $journal['description'] ?? '';
                        echo e(strlen($desc) > 50 ? substr($desc, 0, 50) . '...' : $desc) ?: '<span class="text-muted">-</span>'; 
                        ?>
                    </td>
                    <td class="text-end fw-medium">
                        <?= formatRupiah($journal['total_debit'] ?? $journal['total_amount'] ?? 0) ?>
                    </td>
                    <td class="text-end fw-medium">
                        <?= formatRupiah($journal['total_credit'] ?? $journal['total_amount'] ?? 0) ?>
                    </td>
                    <td class="text-center">
                        <span class="badge bg-<?= $statusColors[$journal['status']] ?? 'secondary' ?>">
                            <?= $statusLabels[$journal['status']] ?? $journal['status'] ?>
                        </span>
                    </td>
                    <td>
                        <div class="btn-group btn-group-sm">
                            <a href="<?= url('/accounting/journals/' . $journal['id']) ?>" 
                               class="btn btn-outline-primary" title="Lihat">
                                <i class="fas fa-eye"></i>
                            </a>
                            <?php if ($journal['status'] === 'draft'): ?>
                            <a href="<?= url('/accounting/journals/' . $journal['id'] . '/edit') ?>" 
                               class="btn btn-outline-secondary" title="Edit">
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
    
    <!-- Pagination -->
    <?php if (isset($pagination) && $pagination['last_page'] > 1): ?>
    <div class="d-flex align-items-center justify-content-between mt-4 pt-3 border-top">
        <div class="text-muted small">
            Menampilkan <?= $pagination['from'] ?? 1 ?> - <?= $pagination['to'] ?? count($journals) ?> 
            dari <?= $pagination['total'] ?? 0 ?> jurnal
        </div>
        <nav>
            <ul class="pagination pagination-sm mb-0">
                <?php
                $queryParams = http_build_query(array_filter($filters ?? []));
                $baseUrl = '/accounting/journals?' . ($queryParams ? $queryParams . '&' : '');
                ?>
                
                <?php if ($pagination['current_page'] > 1): ?>
                <li class="page-item">
                    <a class="page-link" href="<?= url($baseUrl . 'page=' . ($pagination['current_page'] - 1)) ?>">
                        <i class="fas fa-chevron-left"></i>
                    </a>
                </li>
                <?php endif; ?>
                
                <?php 
                $start = max(1, $pagination['current_page'] - 2);
                $end = min($pagination['last_page'], $pagination['current_page'] + 2);
                for ($i = $start; $i <= $end; $i++): 
                ?>
                <li class="page-item <?= $i === $pagination['current_page'] ? 'active' : '' ?>">
                    <a class="page-link" href="<?= url($baseUrl . 'page=' . $i) ?>"><?= $i ?></a>
                </li>
                <?php endfor; ?>
                
                <?php if ($pagination['current_page'] < $pagination['last_page']): ?>
                <li class="page-item">
                    <a class="page-link" href="<?= url($baseUrl . 'page=' . ($pagination['current_page'] + 1)) ?>">
                        <i class="fas fa-chevron-right"></i>
                    </a>
                </li>
                <?php endif; ?>
            </ul>
        </nav>
    </div>
    <?php endif; ?>
    <?php endif; ?>
</div>