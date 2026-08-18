<?php
/**
 * Users List View
 */
?>

<!-- Search -->
<div class="glass-card mb-4">
    <form method="GET" action="<?= url('/users') ?>" class="d-flex gap-3">
        <div class="flex-1">
            <input type="text" name="search" class="form-control" 
                   placeholder="Cari nama atau email..."
                   value="<?= e($search) ?>">
        </div>
        <button type="submit" class="btn btn-primary">
            <i class="fas fa-search"></i> Cari
        </button>
        <?php if ($search): ?>
        <a href="<?= url('/users') ?>" class="btn btn-secondary">
            <i class="fas fa-times"></i> Reset
        </a>
        <?php endif; ?>
    </form>
</div>

<!-- Users List -->
<div class="glass-card">
    <?php if (empty($users)): ?>
    <div class="empty-state">
        <div class="empty-state-icon">
            <i class="fas fa-users"></i>
        </div>
        <h4 class="empty-state-title">
            <?= $search ? 'Tidak Ada Hasil' : 'Belum Ada Pengguna' ?>
        </h4>
        <p class="empty-state-text">
            <?= $search ? 'Coba kata kunci yang berbeda.' : 'Mulai tambahkan pengguna.' ?>
        </p>
    </div>
    <?php else: ?>
    <div class="table-responsive">
        <table class="table">
            <thead>
                <tr>
                    <th>Pengguna</th>
                    <th>Email</th>
                    <th>Role</th>
                    <th>Status</th>
                    <th>Login Terakhir</th>
                    <th width="100">Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($users as $user): ?>
                <?php $u = is_array($user) ? $user : (method_exists($user, 'toArray') ? $user->toArray() : (array)$user); ?>
                <tr>
                    <td>
                        <div class="d-flex align-items-center gap-2">
                            <div class="avatar avatar-sm">
                                <?php if (!empty($u['avatar'])): ?>
                                <img src="<?= uploadUrl('avatars/' . $u['avatar']) ?>" alt="">
                                <?php else: ?>
                                <?= strtoupper(substr($u['name'] ?? '', 0, 1)) ?>
                                <?php endif; ?>
                            </div>
                            <span class="font-medium"><?= e($u['name'] ?? '') ?></span>
                        </div>
                    </td>
                    <td><?= e($u['email'] ?? '') ?></td>
                    <td>
                        <?php $roleName = $roles[$u['role'] ?? ''] ?? ucfirst($u['role'] ?? ''); ?>
                        <span class="badge badge-primary"><?= e($roleName) ?></span>
                    </td>
                    <td>
                        <?php if (!empty($u['is_active'])): ?>
                        <span class="badge badge-success">Aktif</span>
                        <?php else: ?>
                        <span class="badge badge-secondary">Nonaktif</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <?= !empty($u['last_login']) ? formatDateTime($u['last_login']) : '-' ?>
                    </td>
                    <td>
                        <div class="btn-group">
                            <?php if (Session::can('users.update')): ?>
                            <a href="<?= url('/users/' . $u['id'] . '/edit') ?>" 
                               class="btn btn-sm btn-icon btn-secondary" title="Edit">
                                <i class="fas fa-edit"></i>
                            </a>
                            <?php endif; ?>
                            <?php if (Session::can('users.delete') && $u['id'] != Session::userId()): ?>
                            <button type="button" class="btn btn-sm btn-icon btn-secondary text-danger"
                                    data-delete="<?= url('/users/' . $u['id']) ?>"
                                    data-message="Yakin ingin menghapus pengguna <?= e($u['name']) ?>?">
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
            <a href="<?= url('/users?page=' . ($pagination['current_page'] - 1) . ($search ? '&search=' . urlencode($search) : '')) ?>" class="page-link">
                <i class="fas fa-chevron-left"></i>
            </a>
            <?php endif; ?>
            
            <?php for ($i = max(1, $pagination['current_page'] - 2); $i <= min($pagination['last_page'], $pagination['current_page'] + 2); $i++): ?>
            <a href="<?= url('/users?page=' . $i . ($search ? '&search=' . urlencode($search) : '')) ?>" 
               class="page-link <?= $i === $pagination['current_page'] ? 'active' : '' ?>">
                <?= $i ?>
            </a>
            <?php endfor; ?>
            
            <?php if ($pagination['current_page'] < $pagination['last_page']): ?>
            <a href="<?= url('/users?page=' . ($pagination['current_page'] + 1) . ($search ? '&search=' . urlencode($search) : '')) ?>" class="page-link">
                <i class="fas fa-chevron-right"></i>
            </a>
            <?php endif; ?>
        </nav>
    </div>
    <?php endif; ?>
    <?php endif; ?>
</div>

<style>
.avatar {
    width: 36px;
    height: 36px;
    border-radius: 50%;
    background: var(--primary-500);
    color: white;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 600;
    font-size: 0.9rem;
}

.avatar img {
    width: 100%;
    height: 100%;
    border-radius: 50%;
    object-fit: cover;
}
</style>
