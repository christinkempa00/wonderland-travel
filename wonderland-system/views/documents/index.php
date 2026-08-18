<?php
/**
 * Documents List View
 */
?>

<!-- Filters -->
<div class="glass-card mb-4">
    <form method="GET" action="<?= url('/documents') ?>" class="d-flex gap-3 flex-wrap">
        <div class="flex-1" style="min-width: 200px;">
            <input type="text" name="search" class="form-control" 
                   placeholder="Cari nomor dokumen..."
                   value="<?= e($filters['search']) ?>">
        </div>
        <div style="min-width: 150px;">
            <select name="doc_type" class="form-control">
                <option value="">Semua Tipe</option>
                <?php foreach ($documentTypes as $type => $info): ?>
                <option value="<?= $type ?>" <?= $filters['doc_type'] === $type ? 'selected' : '' ?>>
                    <?= $info['label'] ?>
                </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div style="min-width: 140px;">
            <input type="date" name="start_date" class="form-control" 
                   value="<?= e($filters['start_date']) ?>" placeholder="Dari">
        </div>
        <div style="min-width: 140px;">
            <input type="date" name="end_date" class="form-control" 
                   value="<?= e($filters['end_date']) ?>" placeholder="Sampai">
        </div>
        <button type="submit" class="btn btn-primary">
            <i class="fas fa-filter"></i> Filter
        </button>
        <?php if (array_filter($filters)): ?>
        <a href="<?= url('/documents') ?>" class="btn btn-secondary">
            <i class="fas fa-times"></i> Reset
        </a>
        <?php endif; ?>
    </form>
</div>

<!-- Documents List -->
<div class="glass-card">
    <?php if (empty($documents)): ?>
    <div class="empty-state">
        <div class="empty-state-icon">
            <i class="fas fa-file-alt"></i>
        </div>
        <h4 class="empty-state-title">Belum Ada Dokumen</h4>
        <p class="empty-state-text">Dokumen akan dibuat otomatis dari pesanan.</p>
    </div>
    <?php else: ?>
    <div class="table-responsive">
        <table class="table">
            <thead>
                <tr>
                    <th>No. Dokumen</th>
                    <th>Tipe</th>
                    <th>Tanggal</th>
                    <th>No. Order</th>
                    <th>Klien</th>
                    <th width="120">Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($documents as $doc): ?>
                <tr>
                    <td>
                        <a href="<?= url('/documents/' . $doc['id']) ?>" class="font-medium">
                            <?= e($doc['doc_number']) ?>
                        </a>
                    </td>
                    <td>
                        <?php $typeInfo = DOCUMENT_TYPES[$doc['doc_type']] ?? null; ?>
                        <?php if ($typeInfo): ?>
                        <span class="badge badge-<?= $typeInfo['color'] ?>">
                            <?= $typeInfo['label'] ?>
                        </span>
                        <?php endif; ?>
                    </td>
                    <td><?= formatDate($doc['doc_date']) ?></td>
                    <td>
                        <?php if ($doc['order_number']): ?>
                        <a href="<?= url('/orders/' . $doc['order_id']) ?>">
                            <?= e($doc['order_number']) ?>
                        </a>
                        <?php else: ?>
                        -
                        <?php endif; ?>
                    </td>
                    <td><?= e($doc['client_name'] ?? '-') ?></td>
                    <td>
                        <div class="btn-group">
                            <a href="<?= url('/documents/' . $doc['id']) ?>" 
                               class="btn btn-sm btn-icon btn-secondary" title="Lihat">
                                <i class="fas fa-eye"></i>
                            </a>
                            <a href="<?= url('/documents/' . $doc['id'] . '/view') ?>" 
                               class="btn btn-sm btn-icon btn-secondary" title="Print" target="_blank">
                                <i class="fas fa-print"></i>
                            </a>
                            <a href="<?= url('/documents/' . $doc['id'] . '/pdf') ?>" 
                               class="btn btn-sm btn-icon btn-secondary" title="PDF" target="_blank">
                                <i class="fas fa-file-pdf"></i>
                            </a>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    
    <!-- Pagination -->
    <?php if ($pagination['last_page'] > 1): ?>
    <div class="d-flex align-items-center justify-content-between mt-4 pt-4" style="border-top: 1px solid var(--gray-200);">
        <div class="text-sm text-muted">
            Menampilkan <?= $pagination['from'] ?> - <?= $pagination['to'] ?> dari <?= $pagination['total'] ?> data
        </div>
        <nav class="pagination">
            <?php
            $queryParams = http_build_query(array_filter($filters));
            $baseUrl = '/documents?' . ($queryParams ? $queryParams . '&' : '');
            ?>
            
            <?php if ($pagination['current_page'] > 1): ?>
            <a href="<?= url($baseUrl . 'page=' . ($pagination['current_page'] - 1)) ?>" class="page-link">
                <i class="fas fa-chevron-left"></i>
            </a>
            <?php endif; ?>
            
            <?php for ($i = max(1, $pagination['current_page'] - 2); $i <= min($pagination['last_page'], $pagination['current_page'] + 2); $i++): ?>
            <a href="<?= url($baseUrl . 'page=' . $i) ?>" 
               class="page-link <?= $i === $pagination['current_page'] ? 'active' : '' ?>">
                <?= $i ?>
            </a>
            <?php endfor; ?>
            
            <?php if ($pagination['current_page'] < $pagination['last_page']): ?>
            <a href="<?= url($baseUrl . 'page=' . ($pagination['current_page'] + 1)) ?>" class="page-link">
                <i class="fas fa-chevron-right"></i>
            </a>
            <?php endif; ?>
        </nav>
    </div>
    <?php endif; ?>
    <?php endif; ?>
</div>
