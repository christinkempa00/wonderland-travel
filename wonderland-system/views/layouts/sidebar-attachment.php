<?php
/**
 * Sidebar untuk Role Attachment (Staff Lampiran)
 * Hanya menampilkan menu yang relevan untuk input lampiran
 */

$currentPath = $_GET['_url'] ?? '';
$currentPath = '/' . trim($currentPath, '/');

// Get current company for logo
$companyId = Session::companyId();
$company = null;
if ($companyId) {
    $company = db()->fetchOne("SELECT * FROM companies WHERE id = ?", [$companyId]);
}

if (!function_exists('isMenuActive')) {
    function isMenuActive(string $path, string $currentPath): bool {
        if ($path === '/dashboard' || $path === '/attachment-dashboard') {
            return $currentPath === '/dashboard' || $currentPath === '/attachment-dashboard';
        }
        return strpos($currentPath, $path) === 0;
    }
}
?>

<!-- Mobile Overlay -->
<div class="sidebar-overlay" id="sidebarOverlay"></div>

<aside class="sidebar" id="sidebar">
    <!-- Mobile Close Button -->
    <button class="sidebar-close" id="sidebarClose">
        <i class="fas fa-times"></i>
    </button>
    
    <!-- Sidebar Header -->
    <div class="sidebar-header">
        <?php if ($company && !empty($company['logo'])): ?>
            <?php 
            $logoPath = $company['logo'];
            $logoPath = preg_replace('/^logos\//', '', $logoPath);
            $logoPath = 'logos/' . $logoPath;
            ?>
            <img src="<?= uploadUrl($logoPath) ?>" alt="Logo" class="sidebar-logo">
        <?php else: ?>
            <div class="sidebar-logo d-flex align-items-center justify-content-center" style="background: var(--primary-500); color: white; font-weight: bold;">
                TM
            </div>
        <?php endif; ?>
        <span class="sidebar-brand"><?= e($company['name'] ?? APP_SHORT_NAME) ?></span>
    </div>
    
    <!-- User Info -->
    <div class="sidebar-user-info">
        <div class="user-avatar">
            <i class="fas fa-user-circle"></i>
        </div>
        <div class="user-details">
            <span class="user-name"><?= e(Session::user()['name'] ?? 'Staff Lampiran') ?></span>
            <span class="user-role">Staff Lampiran</span>
        </div>
    </div>
    
    <!-- Sidebar Navigation -->
    <nav class="sidebar-nav">
        <!-- Dashboard Lampiran -->
        <div class="nav-section">
            <div class="nav-section-title">Menu Utama</div>
            
            <div class="nav-item">
                <a href="<?= url('/attachment-dashboard') ?>" class="nav-link <?= isMenuActive('/attachment-dashboard', $currentPath) ? 'active' : '' ?>">
                    <i class="fas fa-clipboard-list"></i>
                    <span>Dashboard Lampiran</span>
                </a>
            </div>
        </div>
        
        <!-- Input Lampiran -->
        <div class="nav-section">
            <div class="nav-section-title">Input Lampiran</div>
            
            <div class="nav-item">
                <a href="<?= url('/attachment-dashboard?filter=hotel') ?>" class="nav-link <?= isset($_GET['filter']) && $_GET['filter'] === 'hotel' ? 'active' : '' ?>">
                    <i class="fas fa-hotel"></i>
                    <span>Hotel</span>
                </a>
            </div>
            
            <div class="nav-item">
                <a href="<?= url('/attachment-dashboard?filter=flight') ?>" class="nav-link <?= isset($_GET['filter']) && $_GET['filter'] === 'flight' ? 'active' : '' ?>">
                    <i class="fas fa-plane"></i>
                    <span>Pesawat</span>
                </a>
            </div>
            
            <div class="nav-item">
                <a href="<?= url('/attachment-dashboard?filter=vehicle') ?>" class="nav-link <?= isset($_GET['filter']) && $_GET['filter'] === 'vehicle' ? 'active' : '' ?>">
                    <i class="fas fa-car"></i>
                    <span>Kendaraan</span>
                </a>
            </div>
            
            <div class="nav-item">
                <a href="<?= url('/attachment-dashboard?filter=rental') ?>" class="nav-link <?= isset($_GET['filter']) && $_GET['filter'] === 'rental' ? 'active' : '' ?>">
                    <i class="fas fa-key"></i>
                    <span>Rental</span>
                </a>
            </div>
        </div>
        
        <!-- Account -->
        <div class="nav-section">
            <div class="nav-section-title">Akun</div>
            
            <div class="nav-item">
                <a href="<?= url('/profile') ?>" class="nav-link <?= isMenuActive('/profile', $currentPath) ? 'active' : '' ?>">
                    <i class="fas fa-user-cog"></i>
                    <span>Profil Saya</span>
                </a>
            </div>
            
            <div class="nav-item">
                <a href="<?= url('/logout') ?>" class="nav-link text-danger">
                    <i class="fas fa-sign-out-alt"></i>
                    <span>Logout</span>
                </a>
            </div>
        </div>
    </nav>
    
    <!-- Sidebar Footer -->
    <div class="sidebar-footer">
        <small class="text-muted">Staff Lampiran Mode</small>
    </div>
</aside>

<style>
/* Additional styles for attachment sidebar */
.sidebar-user-info {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 16px 20px;
    border-bottom: 1px solid var(--sidebar-border);
    margin-bottom: 8px;
}

.user-avatar {
    font-size: 2rem;
    color: var(--primary-500);
}

.user-details {
    display: flex;
    flex-direction: column;
}

.user-name {
    font-weight: 600;
    color: var(--sidebar-text-active);
    font-size: 0.9rem;
}

.user-role {
    font-size: 0.75rem;
    color: var(--sidebar-text);
    background: rgba(59, 130, 246, 0.1);
    padding: 2px 8px;
    border-radius: 12px;
    display: inline-block;
    margin-top: 4px;
}

.sidebar-footer {
    padding: 16px 20px;
    border-top: 1px solid var(--sidebar-border);
    text-align: center;
    margin-top: auto;
}

.text-danger {
    color: #ef4444 !important;
}

.text-danger:hover {
    background: rgba(239, 68, 68, 0.1) !important;
}
</style>

<!-- Mobile Menu Button -->
<button class="mobile-menu-btn" id="mobileMenuBtn">
    <i class="fas fa-bars"></i>
</button>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const sidebar = document.getElementById('sidebar');
    const sidebarOverlay = document.getElementById('sidebarOverlay');
    const sidebarClose = document.getElementById('sidebarClose');
    const mobileMenuBtn = document.querySelector('.mobile-menu-btn');
    const body = document.body;
    
    function openSidebar() {
        sidebar.classList.add('open');
        sidebarOverlay.classList.add('active');
        body.style.overflow = 'hidden';
    }
    
    function closeSidebar() {
        sidebar.classList.remove('open');
        sidebarOverlay.classList.remove('active');
        body.style.overflow = '';
    }
    
    if (mobileMenuBtn) mobileMenuBtn.addEventListener('click', openSidebar);
    if (sidebarClose) sidebarClose.addEventListener('click', closeSidebar);
    if (sidebarOverlay) sidebarOverlay.addEventListener('click', closeSidebar);
    
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape' && sidebar.classList.contains('open')) closeSidebar();
    });
});
</script>
