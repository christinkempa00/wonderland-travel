<?php
/**
 * Main Application Layout
 * Variables: $content, $pageTitle, $breadcrumbs (optional)
 * 
 * UPDATED: Added role-based sidebar selection
 */

$pageTitle = $pageTitle ?? 'Dashboard';
$breadcrumbs = $breadcrumbs ?? [];

// Get company for favicon
$companyId = Session::companyId();
$company = null;
$favicon = null;
if ($companyId) {
    $company = db()->fetchOne("SELECT logo FROM companies WHERE id = ?", [$companyId]);
    // Use logo as favicon fallback
    $favicon = $company['logo'] ?? null;
}

// Get user role for sidebar selection
$userRole = $_SESSION['user']['role'] ?? null;
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <meta name="csrf-token" content="<?= Session::getCsrfToken() ?>">
    <meta name="theme-color" content="#1e3a8a">
    
    <title><?= e($pageTitle) ?> - <?= e(APP_NAME) ?></title>
    
    <!-- Favicon -->
    <?php if ($favicon): ?>
    <link rel="icon" type="image/png" href="<?= uploadUrl('logos/' . $favicon) ?>">
    <?php else: ?>
    <link rel="icon" type="image/png" href="<?= url('/icon.php?size=32') ?>">
    <?php endif; ?>
    
    <!-- PWA Manifest -->
    <link rel="manifest" href="<?= url('/manifest.json') ?>">
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <meta name="apple-mobile-web-app-title" content="<?= e(APP_SHORT_NAME) ?>">
    
    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    
    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
    
    <!-- Main CSS - Cache Busting -->
    <link rel="stylesheet" href="<?= asset('css/style.css') ?>?v=<?= time() ?>">
    
    <?php if (isset($additionalCss)): ?>
        <?= $additionalCss ?>
    <?php endif; ?>
</head>
<body>
    <div class="app-wrapper">
        <!-- Sidebar - Pilih berdasarkan role -->
        <?php 
        if ($userRole === 'attachment') {
            include VIEWS_PATH . '/layouts/sidebar-attachment.php';
        } else {
            include VIEWS_PATH . '/layouts/sidebar.php';
        }
        ?>
        
        <!-- Main Content -->
        <div class="main-content">
            <!-- Header -->
            <?php include VIEWS_PATH . '/layouts/header.php'; ?>
            
            <!-- Content -->
            <main class="content-wrapper">
                <?php if (isset($pageHeader) && $pageHeader): ?>
                <div class="page-header glass-card mb-4">
                    <div class="d-flex align-items-center justify-content-between flex-wrap gap-3">
                        <div>
                            <h1 class="h3 mb-1"><?= e($pageTitle) ?></h1>
                            <?php if (isset($pageSubtitle)): ?>
                            <p class="text-muted mb-0"><?= e($pageSubtitle) ?></p>
                            <?php endif; ?>
                        </div>
                        <?php if (isset($pageActions)): ?>
                        <div class="page-actions">
                            <?= $pageActions ?>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endif; ?>
                
                <!-- Page Content -->
                <?= $content ?>
            </main>
            
            <!-- Footer -->
            <?php include VIEWS_PATH . '/layouts/footer.php'; ?>
        </div>
    </div>
    
    <?php if (isset($additionalJs)): ?>
        <?= $additionalJs ?>
    <?php endif; ?>
    
    <!-- Service Worker Registration -->
    <script>
    if ('serviceWorker' in navigator) {
        window.addEventListener('load', function() {
            navigator.serviceWorker.register('/sw.js')
                .then(function(registration) {
                    console.log('ServiceWorker registered:', registration.scope);
                })
                .catch(function(error) {
                    console.log('ServiceWorker registration failed:', error);
                });
        });
    }
    </script>
</body>
</html>