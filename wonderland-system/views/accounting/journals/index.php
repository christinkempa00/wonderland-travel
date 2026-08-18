<?php
/**
 * Journals List View - dengan Breakdown per Tipe
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

$typeLabels = [
    'general' => ['label' => 'Jurnal Umum', 'icon' => 'fa-book', 'color' => 'secondary'],
    'order_invoice' => ['label' => 'Invoice', 'icon' => 'fa-file-invoice', 'color' => 'primary'],
    'order_payment' => ['label' => 'Pembayaran', 'icon' => 'fa-money-bill-wave', 'color' => 'success'],
    'expense' => ['label' => 'Bayar Vendor', 'icon' => 'fa-hand-holding-usd', 'color' => 'danger'],
    'adjustment' => ['label' => 'Penyesuaian', 'icon' => 'fa-balance-scale', 'color' => 'warning'],
];

// Helper function untuk format singkat
function formatShort($num) {
    if ($num >= 1000000000) return number_format($num/1000000000, 1) . 'M';
    if ($num >= 1000000) return number_format($num/1000000, 1) . 'Jt';
    if ($num >= 1000) return number_format($num/1000, 0) . 'Rb';
    return number_format($num, 0);
}
?>

<!-- Page Actions -->
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h5 class="mb-1">Daftar Jurnal Umum</h5>
        <p class="text-muted mb-0 small">Kelola semua transaksi jurnal akuntansi</p>
    </div>
    <a href="<?= url('/accounting/journals/create') ?>" class="btn btn-primary">
        <i class="fas fa-plus me-1"></i> Buat Jurnal
    </a>
</div>

<!-- Summary Cards - Status -->
<div class="row g-3 mb-3">
    <div class="col-6 col-lg-3">
        <div class="glass-card text-center py-3">
            <div class="text-muted small">Total Jurnal</div>
            <h4 class="mb-0 text-primary"><?= number_format($summary['total_count'] ?? 0) ?></h4>
        </div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="glass-card text-center py-3">
            <div class="text-muted small">Draft</div>
            <h4 class="mb-0 text-warning"><?= number_format($summary['draft_count'] ?? 0) ?></h4>
        </div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="glass-card text-center py-3">
            <div class="text-muted small">Posted</div>
            <h4 class="mb-0 text-success"><?= number_format($summary['posted_count'] ?? 0) ?></h4>
        </div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="glass-card text-center py-3">
            <div class="text-muted small">Void</div>
            <h4 class="mb-0 text-secondary"><?= number_format($summary['void_count'] ?? 0) ?></h4>
        </div>
    </div>
</div>

<!-- Breakdown by Type - BARU -->
<div class="glass-card mb-4">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h6 class="mb-0"><i class="fas fa-chart-pie me-2"></i>Breakdown per Tipe Jurnal <small class="text-muted">(Posted)</small></h6>
        <span class="badge bg-dark fs-6">Total: <?= formatRupiah($totalAllDebit ?? 0) ?></span>
    </div>
    
    <div class="row g-2">
        <?php 
        $types = ['order_invoice', 'adjustment', 'general', 'expense', 'order_payment'];
        foreach ($types as $type):
            $info = $typeLabels[$type] ?? ['label' => ucfirst($type), 'icon' => 'fa-file', 'color' => 'secondary'];
            $data = $breakdown[$type] ?? ['count' => 0, 'total' => 0];
            $percentage = $totalAllDebit > 0 ? ($data['total'] / $totalAllDebit * 100) : 0;
        ?>
        <div class="col-6 col-md-4 col-lg">
            <a href="<?= url('/accounting/journals?journal_type=' . $type) ?>" 
               class="text-decoration-none">
                <div class="border rounded p-2 h-100 <?= ($filters['journal_type'] ?? '') === $type ? 'border-'.$info['color'].' bg-'.$info['color'].' bg-opacity-10' : '' ?>">
                    <div class="d-flex align-items-center mb-1">
                        <i class="fas <?= $info['icon'] ?> text-<?= $info['color'] ?> me-2"></i>
                        <span class="small fw-medium"><?= $info['label'] ?></span>
                    </div>
                    <div class="fs-5 fw-bold text-<?= $info['color'] ?>">
                        Rp <?= formatShort($data['total']) ?>
                    </div>
                    <div class="d-flex justify-content-between align-items-center">
                        <small class="text-muted"><?= $data['count'] ?> jurnal</small>
                        <?php if ($percentage > 0): ?>
                        <span class="badge bg-<?= $info['color'] ?> bg-opacity-25 text-<?= $info['color'] ?>"><?= number_format($percentage, 1) ?>%</span>
                        <?php endif; ?>
                    </div>
                    <!-- Progress bar -->
                    <div class="progress mt-1" style="height: 3px;">
                        <div class="progress-bar bg-<?= $info['color'] ?>" style="width: <?= $percentage ?>%"></div>
                    </div>
                </div>
            </a>
        </div>
        <?php endforeach; ?>
    </div>
    
    <!-- Penjelasan -->
    <div class="mt-3 pt-3 border-top">
        <div class="row small text-muted">
            <div class="col-md-6">
                <i class="fas fa-info-circle me-1"></i>
                <strong>Invoice</strong> = Tagihan ke customer | 
                <strong>Bayar Vendor</strong> = Pembayaran ke supplier
            </div>
            <div class="col-md-6 text-md-end">
                <strong>Penyesuaian</strong> = Koreksi selisih | 
                <strong>Jurnal Umum</strong> = Pinjaman, dll
            </div>
        </div>
    </div>
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
            <label class="form-label small">Tipe</label>
            <select name="journal_type" class="form-control form-control-sm">
                <option value="">Semua Tipe</option>
                <?php foreach ($typeLabels as $key => $info): ?>
                <option value="<?= $key ?>" <?= ($filters['journal_type'] ?? '') === $key ? 'selected' : '' ?>>
                    <?= $info['label'] ?>
                </option>
                <?php endforeach; ?>
            </select>
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
        <div class="col-md-2">
            <label class="form-label small">Pencarian</label>
            <input type="text" name="search" class="form-control form-control-sm" 
                   placeholder="No, keterangan..."
                   value="<?= e($filters['search'] ?? '') ?>">
        </div>
        <div class="col-md-2">
            <button type="submit" class="btn btn-primary btn-sm">
                <i class="fas fa-search me-1"></i> Filter
            </button>
            <a href="<?= url('/accounting/journals') ?>" class="btn btn-secondary btn-sm">
                <i class="fas fa-times"></i>
            </a>
        </div>
    </form>
</div>

<!-- Active Filter Tags -->
<?php 
$hasFilters = !empty($filters['journal_type']) || !empty($filters['status']) || !empty($filters['search']) || !empty($filters['start_date']);
if ($hasFilters): 
?>
<div class="mb-3">
    <span class="text-muted small me-2">Filter aktif:</span>
    <?php if (!empty($filters['journal_type'])): ?>
    <a href="<?= url('/accounting/journals?' . http_build_query(array_diff_key($filters, ['journal_type' => '']))) ?>" 
       class="badge bg-primary text-decoration-none me-1">
        Tipe: <?= $typeLabels[$filters['journal_type']]['label'] ?? $filters['journal_type'] ?> <i class="fas fa-times ms-1"></i>
    </a>
    <?php endif; ?>
    <?php if (!empty($filters['status'])): ?>
    <a href="<?= url('/accounting/journals?' . http_build_query(array_diff_key($filters, ['status' => '']))) ?>" 
       class="badge bg-secondary text-decoration-none me-1">
        Status: <?= $statusLabels[$filters['status']] ?? $filters['status'] ?> <i class="fas fa-times ms-1"></i>
    </a>
    <?php endif; ?>
    <?php if (!empty($filters['search'])): ?>
    <a href="<?= url('/accounting/journals?' . http_build_query(array_diff_key($filters, ['search' => '']))) ?>" 
       class="badge bg-info text-decoration-none me-1">
        "<?= e($filters['search']) ?>" <i class="fas fa-times ms-1"></i>
    </a>
    <?php endif; ?>
    <?php if (!empty($filters['start_date']) || !empty($filters['end_date'])): ?>
    <a href="<?= url('/accounting/journals?' . http_build_query(array_diff_key($filters, ['start_date' => '', 'end_date' => '']))) ?>" 
       class="badge bg-warning text-dark text-decoration-none me-1">
        Periode: <?= $filters['start_date'] ?? '...' ?> s/d <?= $filters['end_date'] ?? '...' ?> <i class="fas fa-times ms-1"></i>
    </a>
    <?php endif; ?>
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
                    <th>Tipe</th>
                    <th>Keterangan</th>
                    <th class="text-end">Debit</th>
                    <th class="text-end">Kredit</th>
                    <th class="text-center">Status</th>
                    <th width="80"></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($journals as $journal): 
                    $typeInfo = $typeLabels[$journal['journal_type']] ?? ['label' => $journal['journal_type'], 'icon' => 'fa-file', 'color' => 'secondary'];
                ?>
                <tr class="<?= $journal['status'] === 'void' ? 'text-muted text-decoration-line-through' : '' ?>">
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
                        <span class="badge bg-<?= $typeInfo['color'] ?> bg-opacity-10 text-<?= $typeInfo['color'] ?>">
                            <i class="fas <?= $typeInfo['icon'] ?> me-1"></i><?= $typeInfo['label'] ?>
                        </span>
                    </td>
                    <td>
                        <?php 
                        $desc = $journal['description'] ?? '';
                        echo e(strlen($desc) > 40 ? substr($desc, 0, 40) . '...' : $desc) ?: '<span class="text-muted">-</span>'; 
                        ?>
                    </td>
                    <td class="text-end fw-medium">
                        <?= formatRupiah($journal['total_debit'] ?? 0) ?>
                    </td>
                    <td class="text-end fw-medium">
                        <?= formatRupiah($journal['total_credit'] ?? 0) ?>
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

<style>
.progress {
    background-color: rgba(0,0,0,0.05);
}
</style>