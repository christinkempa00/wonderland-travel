<?php
/**
 * Activity Logs View
 */
?>

<!-- Filters -->
<div class="glass-card mb-4">
    <form method="GET" action="<?= url('/activity-logs') ?>" class="d-flex gap-3 flex-wrap">
        <div style="min-width: 140px;">
            <select name="action" class="form-control form-control-sm">
                <option value="">Semua Aksi</option>
                <?php foreach ($actions as $action): ?>
                <option value="<?= $action ?>" <?= $filters['action'] === $action ? 'selected' : '' ?>>
                    <?= ucfirst($action) ?>
                </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div style="min-width: 140px;">
            <select name="entity_type" class="form-control form-control-sm">
                <option value="">Semua Modul</option>
                <?php foreach ($entityTypes as $type): ?>
                <option value="<?= $type ?>" <?= $filters['entity_type'] === $type ? 'selected' : '' ?>>
                    <?= ucfirst($type) ?>
                </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div style="min-width: 140px;">
            <select name="user_id" class="form-control form-control-sm">
                <option value="">Semua User</option>
                <?php foreach ($users as $user): ?>
                <option value="<?= $user['user_id'] ?>" <?= $filters['user_id'] == $user['user_id'] ? 'selected' : '' ?>>
                    <?= e($user['name']) ?>
                </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div style="min-width: 130px;">
            <input type="date" name="start_date" class="form-control form-control-sm" 
                   value="<?= e($filters['start_date']) ?>" placeholder="Dari">
        </div>
        <div style="min-width: 130px;">
            <input type="date" name="end_date" class="form-control form-control-sm" 
                   value="<?= e($filters['end_date']) ?>" placeholder="Sampai">
        </div>
        <button type="submit" class="btn btn-sm btn-primary">
            <i class="fas fa-filter"></i> Filter
        </button>
        <?php if (array_filter($filters)): ?>
        <a href="<?= url('/activity-logs') ?>" class="btn btn-sm btn-secondary">
            <i class="fas fa-times"></i> Reset
        </a>
        <?php endif; ?>
        <a href="<?= url('/activity-logs/export') ?>" class="btn btn-sm btn-success">
            <i class="fas fa-download"></i> Export CSV
        </a>
    </form>
</div>

<!-- Logs List -->
<div class="glass-card">
    <?php if (empty($logs)): ?>
    <div class="empty-state">
        <div class="empty-state-icon">
            <i class="fas fa-history"></i>
        </div>
        <h4 class="empty-state-title">Tidak Ada Log</h4>
        <p class="empty-state-text">Belum ada aktivitas tercatat.</p>
    </div>
    <?php else: ?>
    <div class="timeline">
        <?php 
        $currentDate = '';
        foreach ($logs as $log): 
            $logDate = date('Y-m-d', strtotime($log['created_at']));
            if ($logDate !== $currentDate):
                $currentDate = $logDate;
        ?>
        <div class="timeline-date">
            <?= formatDateIndo($logDate) ?>
        </div>
        <?php endif; ?>
        
        <div class="timeline-item">
            <div class="timeline-icon <?= getActionColor($log['action']) ?>">
                <i class="fas <?= getActionIcon($log['action']) ?>"></i>
            </div>
            <div class="timeline-content">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <span class="font-medium"><?= e($log['user_name'] ?? 'System') ?></span>
                        <span class="badge badge-<?= getActionColor($log['action']) ?> ms-2">
                            <?= ucfirst($log['action']) ?>
                        </span>
                        <span class="badge badge-secondary ms-1">
                            <?= ucfirst($log['entity_type']) ?>
                        </span>
                    </div>
                    <small class="text-muted"><?= date('H:i', strtotime($log['created_at'])) ?></small>
                </div>
                <p class="text-muted mb-1"><?= e($log['description']) ?></p>
                <small class="text-muted">
                    <i class="fas fa-globe"></i> <?= e($log['ip_address'] ?? '-') ?>
                </small>
            </div>
        </div>
        <?php endforeach; ?>
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
            $baseUrl = '/activity-logs?' . ($queryParams ? $queryParams . '&' : '');
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

<style>
.timeline {
    position: relative;
    padding-left: 30px;
}

.timeline::before {
    content: '';
    position: absolute;
    left: 11px;
    top: 0;
    bottom: 0;
    width: 2px;
    background: var(--gray-200);
}

.timeline-date {
    position: relative;
    padding: 0.5rem 0 0.5rem 20px;
    font-weight: 600;
    color: var(--gray-700);
    font-size: 0.9rem;
}

.timeline-item {
    position: relative;
    padding: 0.75rem 0 0.75rem 20px;
}

.timeline-icon {
    position: absolute;
    left: -19px;
    width: 24px;
    height: 24px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 0.7rem;
    color: white;
}

.timeline-icon.success { background: var(--success); }
.timeline-icon.primary { background: var(--primary-500); }
.timeline-icon.warning { background: var(--warning); }
.timeline-icon.danger { background: var(--danger); }
.timeline-icon.info { background: var(--info); }

.timeline-content {
    background: var(--gray-50);
    padding: 0.75rem 1rem;
    border-radius: var(--border-radius-sm);
}
</style>

<?php
function getActionColor(string $action): string {
    $colors = [
        'create' => 'success',
        'update' => 'primary',
        'delete' => 'danger',
        'login' => 'info',
        'logout' => 'secondary',
        'post' => 'success',
        'void' => 'danger'
    ];
    return $colors[$action] ?? 'secondary';
}

function getActionIcon(string $action): string {
    $icons = [
        'create' => 'fa-plus',
        'update' => 'fa-edit',
        'delete' => 'fa-trash',
        'login' => 'fa-sign-in-alt',
        'logout' => 'fa-sign-out-alt',
        'post' => 'fa-check',
        'void' => 'fa-ban'
    ];
    return $icons[$action] ?? 'fa-circle';
}
?>
