<?php
/**
 * Sidebar Layout Component
 * With Accounting Integration & Mobile Responsive
 */

$currentPath = $_GET['_url'] ?? '';
$currentPath = '/' . trim($currentPath, '/');

// Get current company for logo
$companyId = Session::companyId();
$company = null;
if ($companyId) {
    $company = db()->fetchOne("SELECT * FROM companies WHERE id = ?", [$companyId]);
}

/**
 * Check if menu item is active
 */
if (!function_exists('isMenuActive')) {
    function isMenuActive(string $path, string $currentPath): bool {
        if ($path === '/dashboard') {
            return $currentPath === '/dashboard';
        }
        return strpos($currentPath, $path) === 0;
    }
}

/**
 * Check if any submenu is active
 */
if (!function_exists('isSubmenuActive')) {
    function isSubmenuActive(array $items, string $currentPath): bool {
        foreach ($items as $item) {
            if (isMenuActive($item['path'], $currentPath)) {
                return true;
            }
        }
        return false;
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
            <img src="<?= uploadUrl($logoPath) ?>" alt="Logo" class="sidebar-logo" onerror="this.parentElement.innerHTML='<div class=\'sidebar-logo d-flex align-items-center justify-content-center\' style=\'background: var(--primary-500); color: white; font-weight: bold;\'>TM</div>'">
        <?php else: ?>
            <div class="sidebar-logo d-flex align-items-center justify-content-center" style="background: var(--primary-500); color: white; font-weight: bold;">
                TM
            </div>
        <?php endif; ?>
        <span class="sidebar-brand"><?= e($company['name'] ?? APP_SHORT_NAME) ?></span>
    </div>
    
    <!-- Sidebar Navigation -->
    <nav class="sidebar-nav">
        <!-- Main Menu -->
        <div class="nav-section">
            <div class="nav-section-title">Menu Utama</div>
            
            <!-- Dashboard -->
            <div class="nav-item">
                <a href="<?= url('/dashboard') ?>" class="nav-link <?= isMenuActive('/dashboard', $currentPath) ? 'active' : '' ?>">
                    <i class="fas fa-home"></i>
                    <span>Dashboard</span>
                </a>
            </div>
            
            <!-- Orders -->
            <div class="nav-item">
                <a href="<?= url('/orders') ?>" class="nav-link <?= isMenuActive('/orders', $currentPath) ? 'active' : '' ?>">
                    <i class="fas fa-file-alt"></i>
                    <span>Pesanan</span>
                </a>
            </div>
            
            <!-- Clients -->
            <div class="nav-item">
                <a href="<?= url('/clients') ?>" class="nav-link <?= isMenuActive('/clients', $currentPath) ? 'active' : '' ?>">
                    <i class="fas fa-users"></i>
                    <span>Klien</span>
                </a>
            </div>
            
            <!-- Documents -->
            <div class="nav-item">
                <a href="<?= url('/documents') ?>" class="nav-link <?= isMenuActive('/documents', $currentPath) ? 'active' : '' ?>">
                    <i class="fas fa-file-invoice"></i>
                    <span>Dokumen</span>
                </a>
            </div>
        </div>
        
        
        <!-- Profit Sharing -->
        <div class="nav-section">
            <div class="nav-section-title">Bagi Hasil</div>
            
            <div class="nav-item">
                <a href="<?= url('/profit') ?>" class="nav-link <?= isMenuActive('/profit', $currentPath) ? 'active' : '' ?>">
                    <i class="fas fa-hand-holding-usd"></i>
                    <span>Penerima</span>
                </a>
            </div>
        </div>
        
        <!-- Settings -->
        <div class="nav-section">
            <div class="nav-section-title">Pengaturan</div>
            
            <div class="nav-item">
                <a href="<?= url('/settings') ?>" class="nav-link <?= isMenuActive('/settings', $currentPath) ? 'active' : '' ?>">
                    <i class="fas fa-cog"></i>
                    <span>Pengaturan</span>
                </a>
            </div>
            
            <div class="nav-item">
                <a href="<?= url('/users') ?>" class="nav-link <?= isMenuActive('/users', $currentPath) ? 'active' : '' ?>">
                    <i class="fas fa-user-cog"></i>
                    <span>Pengguna</span>
                </a>
            </div>
            
            <div class="nav-item">
                <a href="<?= url('/activity-logs') ?>" class="nav-link <?= isMenuActive('/activity-logs', $currentPath) ? 'active' : '' ?>">
                    <i class="fas fa-history"></i>
                    <span>Log Aktivitas</span>
                </a>
            </div>
        </div>
        
        <!-- User Section -->
        <div class="nav-section mt-auto">
            <div class="nav-section-title">Akun</div>
            
            <div class="nav-item">
                <a href="<?= url('/profile') ?>" class="nav-link <?= isMenuActive('/profile', $currentPath) ? 'active' : '' ?>">
                    <i class="fas fa-user-circle"></i>
                    <span>Profil Saya</span>
                </a>
            </div>
            
            <div class="nav-item">
                <a href="<?= url('/logout') ?>" class="nav-link text-danger">
                    <i class="fas fa-sign-out-alt"></i>
                    <span>Keluar</span>
                </a>
            </div>
        </div>
    </nav>
</aside>

<style>
/* ========================================
   SIDEBAR STYLES
   ======================================== */
:root {
    --sidebar-width: 260px;
    --sidebar-collapsed-width: 70px;
    --sidebar-bg: #2e230a;
    --sidebar-text: #c9b896;
    --sidebar-text-active: #fdf6e3;
    --sidebar-hover-bg: #4a3a12;
    --sidebar-active-bg: rgba(200, 155, 44, 0.25);
    --sidebar-border: #4a3a12;
    --sidebar-section-title: #9c8a5e;
}

.sidebar {
    position: fixed;
    top: 0;
    left: 0;
    width: var(--sidebar-width);
    height: 100vh;
    background: var(--sidebar-bg);
    color: var(--sidebar-text);
    z-index: 1000;
    display: flex;
    flex-direction: column;
    transition: all 0.3s ease;
    overflow: hidden;
}

.sidebar-header {
    padding: 20px;
    display: flex;
    align-items: center;
    gap: 12px;
    border-bottom: 1px solid var(--sidebar-border);
    flex-shrink: 0;
}

.sidebar-logo {
    width: 40px;
    height: 40px;
    border-radius: 10px;
    object-fit: contain;
    background: white;
    padding: 4px;
    flex-shrink: 0;
}

.sidebar-brand {
    font-size: 16px;
    font-weight: 600;
    color: var(--sidebar-text-active);
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.sidebar-nav {
    flex: 1;
    overflow-y: auto;
    overflow-x: hidden;
    padding: 10px 0;
}

.sidebar-nav::-webkit-scrollbar {
    width: 4px;
}

.sidebar-nav::-webkit-scrollbar-track {
    background: transparent;
}

.sidebar-nav::-webkit-scrollbar-thumb {
    background: var(--sidebar-border);
    border-radius: 4px;
}

.nav-section {
    margin-bottom: 8px;
}

.nav-section-title {
    padding: 12px 20px 6px;
    font-size: 11px;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    color: var(--sidebar-section-title);
}

.nav-item {
    position: relative;
}

.nav-link {
    display: flex;
    align-items: center;
    padding: 10px 20px;
    color: var(--sidebar-text);
    text-decoration: none;
    transition: all 0.2s ease;
    cursor: pointer;
    gap: 12px;
    font-size: 14px;
}

.nav-link:hover {
    background: var(--sidebar-hover-bg);
    color: var(--sidebar-text-active);
}

.nav-link.active {
    background: var(--sidebar-active-bg);
    color: var(--sidebar-text-active);
    border-right: 3px solid #c89b2c;
}

.nav-link i {
    width: 20px;
    text-align: center;
    font-size: 15px;
    flex-shrink: 0;
}

.nav-link span {
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

/* Submenu */
.nav-toggle {
    justify-content: flex-start;
}

.nav-toggle .toggle-icon {
    margin-left: auto;
    font-size: 10px;
    transition: transform 0.2s;
}

.nav-item.open > .nav-toggle .toggle-icon {
    transform: rotate(180deg);
}

.nav-submenu {
    max-height: 0;
    overflow: hidden;
    transition: max-height 0.3s ease;
    background: rgba(0, 0, 0, 0.15);
}

.nav-item.open > .nav-submenu {
    max-height: 500px;
}

.nav-submenu .nav-link {
    padding-left: 52px;
    font-size: 13px;
    border-right: none;
}

.nav-submenu .nav-link.active {
    background: var(--sidebar-active-bg);
    border-right: 3px solid #c89b2c;
}

.nav-submenu .nav-link i {
    font-size: 12px;
    width: 16px;
}

/* Close button */
.sidebar-close {
    display: none;
    position: absolute;
    top: 15px;
    right: 15px;
    width: 32px;
    height: 32px;
    background: var(--sidebar-hover-bg);
    border: none;
    border-radius: 8px;
    color: var(--sidebar-text);
    cursor: pointer;
    align-items: center;
    justify-content: center;
    z-index: 10;
}

.sidebar-close:hover {
    background: var(--sidebar-active-bg);
    color: var(--sidebar-text-active);
}

/* Overlay */
.sidebar-overlay {
    display: none;
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0, 0, 0, 0.5);
    z-index: 999;
    opacity: 0;
    transition: opacity 0.3s;
}

.sidebar-overlay.active {
    display: block;
    opacity: 1;
}

/* ========================================
   COLLAPSED STATE (Desktop)
   ======================================== */
body.sidebar-collapsed .sidebar {
    width: var(--sidebar-collapsed-width);
}

body.sidebar-collapsed .sidebar-brand,
body.sidebar-collapsed .nav-section-title,
body.sidebar-collapsed .nav-link span,
body.sidebar-collapsed .toggle-icon {
    opacity: 0;
    visibility: hidden;
}

body.sidebar-collapsed .nav-submenu {
    display: none;
}

body.sidebar-collapsed .nav-link {
    justify-content: center;
    padding: 12px;
}

body.sidebar-collapsed .nav-link i {
    margin: 0;
}

body.sidebar-collapsed .main-content {
    margin-left: var(--sidebar-collapsed-width);
}

/* Tooltip on collapsed */
body.sidebar-collapsed .nav-link[data-tooltip]:hover::after {
    content: attr(data-tooltip);
    position: absolute;
    left: 100%;
    top: 50%;
    transform: translateY(-50%);
    background: #1e293b;
    color: white;
    padding: 6px 12px;
    border-radius: 6px;
    font-size: 13px;
    white-space: nowrap;
    z-index: 1001;
    margin-left: 10px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.2);
}

/* ========================================
   RESPONSIVE STYLES
   ======================================== */
@media (max-width: 991.98px) {
    .sidebar {
        transform: translateX(-100%);
    }
    
    .sidebar.open {
        transform: translateX(0);
    }
    
    .sidebar-close {
        display: flex;
    }
    
    .main-content {
        margin-left: 0 !important;
    }
    
    .sidebar-header {
        padding-right: 50px;
    }
}

@media (max-width: 575.98px) {
    .sidebar {
        width: 100%;
        max-width: 300px;
    }
    
    .nav-link {
        padding: 12px 16px;
    }
    
    .nav-submenu .nav-link {
        padding-left: 44px;
    }
    
    .sidebar-header {
        padding: 16px;
    }
    
    .sidebar-logo {
        width: 36px;
        height: 36px;
    }
    
    .sidebar-brand {
        font-size: 15px;
    }
}

/* ========================================
   MOBILE HAMBURGER BUTTON
   ======================================== */
.mobile-menu-btn {
    display: none;
    position: fixed;
    top: 15px;
    left: 15px;
    width: 44px;
    height: 44px;
    background: var(--sidebar-bg);
    border: none;
    border-radius: 10px;
    color: white;
    z-index: 997;
    cursor: pointer;
    box-shadow: 0 2px 8px rgba(0,0,0,0.2);
    align-items: center;
    justify-content: center;
    transition: all 0.2s;
}

.mobile-menu-btn:hover {
    background: var(--sidebar-active-bg);
}

.mobile-menu-btn i {
    font-size: 20px;
}

@media (max-width: 991.98px) {
    .mobile-menu-btn {
        display: flex;
    }
    
    .main-content {
        padding-top: 70px;
    }
}

/* ========================================
   DARK/LIGHT MODE SUPPORT
   ======================================== */
@media (prefers-color-scheme: light) {
    :root {
        --sidebar-bg: #ffffff;
        --sidebar-text: #64748b;
        --sidebar-text-active: #1e293b;
        --sidebar-hover-bg: #f1f5f9;
        --sidebar-border: #e2e8f0;
        --sidebar-section-title: #94a3b8;
    }
    
    .sidebar {
        box-shadow: 2px 0 8px rgba(0,0,0,0.08);
    }
    
    .nav-link.active {
        background: rgba(200, 155, 44, 0.1);
    }
    
    .nav-submenu {
        background: rgba(0, 0, 0, 0.02);
    }
}

/* Force dark mode class */
.sidebar.dark-mode {
    --sidebar-bg: #1e293b;
    --sidebar-text: #94a3b8;
    --sidebar-text-active: #ffffff;
    --sidebar-hover-bg: #334155;
    --sidebar-border: #334155;
    --sidebar-section-title: #64748b;
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const sidebar = document.getElementById('sidebar');
    const sidebarOverlay = document.getElementById('sidebarOverlay');
    const sidebarClose = document.getElementById('sidebarClose');
    const sidebarToggle = document.getElementById('sidebarToggle');
    const mobileMenuBtn = document.querySelector('.mobile-menu-btn');
    const body = document.body;
    
    // Function to open sidebar (mobile)
    function openSidebar() {
        sidebar.classList.add('open');
        sidebarOverlay.classList.add('active');
        body.style.overflow = 'hidden';
    }
    
    // Function to close sidebar (mobile)
    function closeSidebar() {
        sidebar.classList.remove('open');
        sidebarOverlay.classList.remove('active');
        body.style.overflow = '';
    }
    
    // Toggle submenu
    document.querySelectorAll('.nav-toggle').forEach(function(toggle) {
        toggle.addEventListener('click', function(e) {
            e.preventDefault();
            const navItem = this.closest('.nav-item');
            navItem.classList.toggle('open');
        });
    });
    
    // Sidebar toggle button (header)
    if (sidebarToggle) {
        sidebarToggle.addEventListener('click', function() {
            if (window.innerWidth <= 991) {
                openSidebar();
            } else {
                body.classList.toggle('sidebar-collapsed');
                localStorage.setItem('sidebarCollapsed', body.classList.contains('sidebar-collapsed'));
            }
        });
    }
    
    // Mobile menu button
    if (mobileMenuBtn) {
        mobileMenuBtn.addEventListener('click', openSidebar);
    }
    
    // Close button
    if (sidebarClose) {
        sidebarClose.addEventListener('click', closeSidebar);
    }
    
    // Overlay click
    if (sidebarOverlay) {
        sidebarOverlay.addEventListener('click', closeSidebar);
    }
    
    // Close on ESC key
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape' && sidebar.classList.contains('open')) {
            closeSidebar();
        }
    });
    
    // Restore sidebar state (desktop)
    if (localStorage.getItem('sidebarCollapsed') === 'true' && window.innerWidth > 991) {
        body.classList.add('sidebar-collapsed');
    }
    
    // Handle window resize
    let resizeTimer;
    window.addEventListener('resize', function() {
        clearTimeout(resizeTimer);
        resizeTimer = setTimeout(function() {
            if (window.innerWidth > 991) {
                closeSidebar();
            }
        }, 250);
    });
    
    // Add tooltips for collapsed state
    document.querySelectorAll('.sidebar-nav .nav-link').forEach(function(link) {
        const span = link.querySelector('span');
        if (span) {
            link.setAttribute('data-tooltip', span.textContent.trim());
        }
    });
    
    // Close sidebar when clicking nav link (mobile)
    document.querySelectorAll('.sidebar-nav .nav-link:not(.nav-toggle)').forEach(function(link) {
        link.addEventListener('click', function() {
            if (window.innerWidth <= 991) {
                closeSidebar();
            }
        });
    });
    
    // Swipe to close (mobile)
    let touchStartX = 0;
    let touchEndX = 0;
    
    sidebar.addEventListener('touchstart', function(e) {
        touchStartX = e.changedTouches[0].screenX;
    }, { passive: true });
    
    sidebar.addEventListener('touchend', function(e) {
        touchEndX = e.changedTouches[0].screenX;
        if (touchStartX - touchEndX > 50) {
            closeSidebar();
        }
    }, { passive: true });
});
</script>

<!-- Mobile Menu Button (add this to your header or main layout) -->
<button class="mobile-menu-btn" id="mobileMenuBtn">
    <i class="fas fa-bars"></i>
</button>