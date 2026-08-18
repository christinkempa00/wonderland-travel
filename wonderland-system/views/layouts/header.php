<?php
/**
 * Header Layout Component
 * Variables: $pageTitle, $breadcrumbs (optional)
 */

$user = Session::user();
$companies = Session::getUserCompanies();
$currentCompanyId = Session::companyId();
$currentCompany = null;

foreach ($companies as $company) {
    if ($company['id'] == $currentCompanyId) {
        $currentCompany = $company;
        break;
    }
}
?>

<header class="header">
    <div class="header-left">
        <button type="button" class="sidebar-toggle" id="sidebarToggle">
            <i class="fas fa-bars"></i>
        </button>
        
        <?php if (!empty($breadcrumbs)): ?>
        <nav class="breadcrumb">
            <a href="<?= url('/dashboard') ?>">
                <i class="fas fa-home"></i>
            </a>
            <?php foreach ($breadcrumbs as $i => $crumb): ?>
                <span class="breadcrumb-separator">/</span>
                <?php if ($i === count($breadcrumbs) - 1): ?>
                    <span class="breadcrumb-current"><?= e($crumb['label']) ?></span>
                <?php else: ?>
                    <a href="<?= url($crumb['url']) ?>"><?= e($crumb['label']) ?></a>
                <?php endif; ?>
            <?php endforeach; ?>
        </nav>
        <?php endif; ?>
    </div>
    
    <div class="header-right">
        <?php if (count($companies) > 1): ?>
        <!-- Company Switcher -->
        <div class="dropdown company-switcher">
            <button type="button" class="company-switcher-btn" data-dropdown>
                <i class="fas fa-building"></i>
                <span><?= e($currentCompany['name'] ?? 'Pilih Perusahaan') ?></span>
                <i class="fas fa-chevron-down"></i>
            </button>
            <div class="dropdown-menu">
                <?php foreach ($companies as $company): ?>
                <a href="#" class="dropdown-item switch-company" data-company-id="<?= $company['id'] ?>">
                    <?php if ($company['id'] == $currentCompanyId): ?>
                        <i class="fas fa-check text-success"></i>
                    <?php else: ?>
                        <i class="fas fa-building"></i>
                    <?php endif; ?>
                    <?= e($company['name']) ?>
                </a>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>
        
        <!-- Notifications -->
        <div class="dropdown">
            <button type="button" class="notification-btn" data-dropdown>
                <i class="fas fa-bell"></i>
                <span class="notification-badge" id="notifBadge" style="display: none;">0</span>
            </button>
            <div class="dropdown-menu" style="width: 320px; max-height: 400px; overflow-y: auto;">
                <div class="p-3 d-flex align-items-center justify-content-between" style="border-bottom: 1px solid var(--gray-100);">
                    <strong>Notifikasi</strong>
                    <a href="#" class="text-sm" id="markAllRead">Tandai semua dibaca</a>
                </div>
                <div id="notificationList">
                    <div class="p-4 text-center text-muted">
                        <i class="fas fa-bell-slash mb-2" style="font-size: 2rem; opacity: 0.3;"></i>
                        <p class="mb-0 text-sm">Tidak ada notifikasi</p>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- User Menu -->
        <div class="dropdown user-menu">
            <button type="button" class="user-menu-btn" data-dropdown>
                <?php if (!empty($user['avatar'])): ?>
                    <img src="<?= uploadUrl('avatars/' . $user['avatar']) ?>" alt="Avatar" class="user-avatar">
                <?php else: ?>
                    <div class="user-avatar d-flex align-items-center justify-content-center" style="background: var(--primary-100); color: var(--primary-600); font-weight: 600;">
                        <?= strtoupper(substr($user['name'], 0, 1)) ?>
                    </div>
                <?php endif; ?>
                <span class="user-name"><?= e($user['name']) ?></span>
                <i class="fas fa-chevron-down"></i>
            </button>
            <div class="dropdown-menu">
                <div class="p-3" style="border-bottom: 1px solid var(--gray-100);">
                    <div class="font-semibold"><?= e($user['name']) ?></div>
                    <div class="text-sm text-muted"><?= e($user['email']) ?></div>
                    <span class="badge badge-primary mt-2"><?= ROLES[$user['role']] ?? $user['role'] ?></span>
                </div>
                <a href="<?= url('/profile') ?>" class="dropdown-item">
                    <i class="fas fa-user"></i>
                    Profil Saya
                </a>
                <?php if (Session::can('settings.company')): ?>
                <a href="<?= url('/settings') ?>" class="dropdown-item">
                    <i class="fas fa-cog"></i>
                    Pengaturan
                </a>
                <?php endif; ?>
                <div class="dropdown-divider"></div>
                <a href="<?= url('/logout') ?>" class="dropdown-item text-danger">
                    <i class="fas fa-sign-out-alt"></i>
                    Keluar
                </a>
            </div>
        </div>
    </div>
</header>

<!-- Company Switch Form (Hidden) -->
<form id="switchCompanyForm" action="<?= url('/switch-company') ?>" method="POST" style="display: none;">
    <?= csrfField() ?>
    <input type="hidden" name="company_id" id="switchCompanyId">
</form>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Company Switcher
    document.querySelectorAll('.switch-company').forEach(function(el) {
        el.addEventListener('click', function(e) {
            e.preventDefault();
            document.getElementById('switchCompanyId').value = this.dataset.companyId;
            document.getElementById('switchCompanyForm').submit();
        });
    });
    
    // Load notifications
    loadNotifications();
    
    // Refresh notifications every 30 seconds
    setInterval(loadNotifications, 30000);
});

function loadNotifications() {
    fetch('<?= url('/api/notifications') ?>', {
        headers: {
            'X-Requested-With': 'XMLHttpRequest'
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            updateNotificationUI(data.data);
        }
    })
    .catch(error => console.error('Error loading notifications:', error));
}

function updateNotificationUI(notifications) {
    const badge = document.getElementById('notifBadge');
    const list = document.getElementById('notificationList');
    
    const unread = notifications.filter(n => !n.is_read);
    
    if (unread.length > 0) {
        badge.textContent = unread.length > 9 ? '9+' : unread.length;
        badge.style.display = 'flex';
    } else {
        badge.style.display = 'none';
    }
    
    if (notifications.length === 0) {
        list.innerHTML = `
            <div class="p-4 text-center text-muted">
                <i class="fas fa-bell-slash mb-2" style="font-size: 2rem; opacity: 0.3;"></i>
                <p class="mb-0 text-sm">Tidak ada notifikasi</p>
            </div>
        `;
        return;
    }
    
    list.innerHTML = notifications.slice(0, 10).map(n => `
        <a href="${n.link || '#'}" class="dropdown-item ${n.is_read ? '' : 'bg-primary-50'}">
            <i class="fas fa-${getNotifIcon(n.type)}"></i>
            <div class="flex-1">
                <div class="font-medium text-sm">${escapeHtml(n.title)}</div>
                <div class="text-xs text-muted">${escapeHtml(n.message || '')}</div>
                <div class="text-xs text-muted mt-1">${formatTimeAgo(n.created_at)}</div>
            </div>
        </a>
    `).join('');
}

function getNotifIcon(type) {
    const icons = {
        'info': 'info-circle',
        'success': 'check-circle',
        'warning': 'exclamation-triangle',
        'error': 'times-circle'
    };
    return icons[type] || 'bell';
}

function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

function formatTimeAgo(dateString) {
    const date = new Date(dateString);
    const now = new Date();
    const diff = Math.floor((now - date) / 1000);
    
    if (diff < 60) return 'Baru saja';
    if (diff < 3600) return Math.floor(diff / 60) + ' menit lalu';
    if (diff < 86400) return Math.floor(diff / 3600) + ' jam lalu';
    return Math.floor(diff / 86400) + ' hari lalu';
}

// Mark all as read
document.getElementById('markAllRead')?.addEventListener('click', function(e) {
    e.preventDefault();
    fetch('<?= url('/api/notifications/read-all') ?>', {
        method: 'POST',
        headers: {
            'X-Requested-With': 'XMLHttpRequest',
            'X-CSRF-TOKEN': '<?= Session::getCsrfToken() ?>'
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            loadNotifications();
        }
    });
});
</script>
