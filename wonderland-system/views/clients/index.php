<?php
/**
 * Clients List View
 */
?>

<!-- Search & Filter -->
<div class="glass-card mb-4">
    <form method="GET" action="<?= url('/clients') ?>" class="d-flex gap-3">
        <div class="flex-1">
            <div class="input-group">
                <span class="input-group-text"><i class="fas fa-search"></i></span>
                <input type="text" name="search" class="form-control" 
                       placeholder="Cari nama, kontak, email, atau telepon..."
                       value="<?= e($search) ?>">
            </div>
        </div>
        <button type="submit" class="btn btn-primary">
            <i class="fas fa-search"></i> Cari
        </button>
        <?php if ($search): ?>
        <a href="<?= url('/clients') ?>" class="btn btn-secondary">
            <i class="fas fa-times"></i> Reset
        </a>
        <?php endif; ?>
    </form>
</div>

<!-- Client List -->
<div class="glass-card">
    <?php if (empty($clients)): ?>
    <div class="empty-state">
        <div class="empty-state-icon">
            <i class="fas fa-users"></i>
        </div>
        <h4 class="empty-state-title">
            <?= $search ? 'Tidak Ada Hasil' : 'Belum Ada Klien' ?>
        </h4>
        <p class="empty-state-text">
            <?= $search ? 'Coba kata kunci yang berbeda.' : 'Mulai tambahkan klien pertama Anda.' ?>
        </p>
        <?php if (!$search && Session::can('clients.create')): ?>
        <a href="<?= url('/clients/create') ?>" class="btn btn-primary">
            <i class="fas fa-plus"></i> Tambah Klien
        </a>
        <?php endif; ?>
    </div>
    <?php else: ?>
    <div class="table-responsive">
        <table class="table">
            <thead>
                <tr>
                    <th>Kode Klien</th>
                    <th>Nama</th>
                    <th>Kontak Person</th>
                    <th>Email</th>
                    <th>Telepon</th>
                    <th width="120">Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($clients as $client): ?>
                <?php $c = is_array($client) ? $client : (array)$client; ?>
                <tr class="clickable-row" onclick="if (!event.target.closest('.aksi-cell')) window.location.href='<?= url('/clients/' . $c['id']) ?>'">
                    <td><span class="text-muted"><?= e($c['client_code'] ?? '-') ?></span></td>
                    <td>
                        <a href="<?= url('/clients/' . $c['id']) ?>" class="font-medium">
                            <?= e($c['name']) ?>
                        </a>
                    </td>
                    <td><?= e($c['contact_person'] ?? '-') ?></td>
                    <td onclick="event.stopPropagation()">
                        <?php if (!empty($c['email'])): ?>
                        <a href="mailto:<?= e($c['email']) ?>"><?= e($c['email']) ?></a>
                        <?php else: ?>
                        -
                        <?php endif; ?>
                    </td>
                    <td onclick="event.stopPropagation()">
                        <?php if (!empty($c['phone'])): ?>
                        <?php $waPhone = preg_replace('/[^0-9]/', '', $c['phone']); if (strpos($waPhone, '0') === 0) $waPhone = '62' . substr($waPhone, 1); ?>
                        <a href="https://wa.me/<?= e($waPhone) ?>" target="_blank"
                           class="text-success" title="Chat WhatsApp">
                            <i class="fab fa-whatsapp"></i> <?= e($c['phone']) ?>
                        </a>
                        <?php else: ?>
                        -
                        <?php endif; ?>
                    </td>
                    <td class="aksi-cell">
                        <div class="btn-group">
                            <a href="<?= url('/clients/' . $c['id']) ?>"
                               class="btn btn-sm btn-icon btn-secondary" title="Lihat">
                                <i class="fas fa-eye"></i>
                            </a>
                            <?php if (Session::can('clients.update')): ?>
                            <a href="<?= url('/clients/' . $c['id'] . '/edit') ?>" 
                               class="btn btn-sm btn-icon btn-secondary" title="Edit">
                                <i class="fas fa-edit"></i>
                            </a>
                            <?php endif; ?>
                            <?php if (Session::can('clients.delete')): ?>
                            <button type="button" class="btn btn-sm btn-icon btn-secondary text-danger" 
                                    title="Hapus"
                                    data-delete="<?= url('/clients/' . $c['id']) ?>"
                                    data-message="Yakin ingin menghapus klien <?= e($c['name']) ?>?">
                                <i class="fas fa-trash"></i>
                            </button>
                            <?php endif; ?>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    
    <!-- Pagination -->
    <?php if ($pagination && isset($pagination['last_page']) && $pagination['last_page'] > 1): ?>
    <div class="d-flex align-items-center justify-content-between mt-4 pt-4" style="border-top: 1px solid var(--gray-200);">
        <div class="text-sm text-muted">
            Menampilkan <?= $pagination['from'] ?> - <?= $pagination['to'] ?> dari <?= $pagination['total'] ?> data
        </div>
        <nav class="pagination">
            <?php if ($pagination['current_page'] > 1): ?>
            <a href="<?= url('/clients?page=' . ($pagination['current_page'] - 1) . ($search ? '&search=' . urlencode($search) : '')) ?>" 
               class="page-link">
                <i class="fas fa-chevron-left"></i>
            </a>
            <?php endif; ?>
            
            <?php
            $start = max(1, $pagination['current_page'] - 2);
            $end = min($pagination['last_page'], $pagination['current_page'] + 2);
            
            if ($start > 1):
            ?>
            <a href="<?= url('/clients?page=1' . ($search ? '&search=' . urlencode($search) : '')) ?>" 
               class="page-link">1</a>
            <?php if ($start > 2): ?>
            <span class="page-link disabled">...</span>
            <?php endif; ?>
            <?php endif; ?>
            
            <?php for ($i = $start; $i <= $end; $i++): ?>
            <a href="<?= url('/clients?page=' . $i . ($search ? '&search=' . urlencode($search) : '')) ?>" 
               class="page-link <?= $i === $pagination['current_page'] ? 'active' : '' ?>">
                <?= $i ?>
            </a>
            <?php endfor; ?>
            
            <?php if ($end < $pagination['last_page']): ?>
            <?php if ($end < $pagination['last_page'] - 1): ?>
            <span class="page-link disabled">...</span>
            <?php endif; ?>
            <a href="<?= url('/clients?page=' . $pagination['last_page'] . ($search ? '&search=' . urlencode($search) : '')) ?>" 
               class="page-link"><?= $pagination['last_page'] ?></a>
            <?php endif; ?>
            
            <?php if ($pagination['current_page'] < $pagination['last_page']): ?>
            <a href="<?= url('/clients?page=' . ($pagination['current_page'] + 1) . ($search ? '&search=' . urlencode($search) : '')) ?>" 
               class="page-link">
                <i class="fas fa-chevron-right"></i>
            </a>
            <?php endif; ?>
        </nav>
    </div>
    <?php endif; ?>
    <?php endif; ?>
</div>
