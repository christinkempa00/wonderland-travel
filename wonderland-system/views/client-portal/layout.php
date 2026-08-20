<?php
/**
 * Client Portal Layout — minimal branded shell, no staff sidebar.
 * Variables: $content, $pageTitle
 */

$pageTitle = $pageTitle ?? 'Portal Klien';
$client = Session::client();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="<?= Session::getCsrfToken() ?>">
    <meta name="theme-color" content="#574314">

    <title><?= e($pageTitle) ?> - <?= e(APP_NAME) ?></title>

    <link rel="icon" type="image/png" href="<?= url('/icon.php?size=32') ?>">

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <link rel="stylesheet" href="<?= asset('css/style.css') ?>?v=<?= APP_VERSION ?>">

    <style>
        body { background: var(--gray-50); }

        .portal-topbar {
            background: #111827;
            color: #fff;
            padding: 0.9rem 1.5rem;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        .portal-topbar .brand {
            display: flex;
            align-items: center;
            gap: 0.6rem;
            font-weight: 700;
            font-size: 1.05rem;
        }
        .portal-topbar .brand .badge-w {
            width: 32px; height: 32px;
            background: linear-gradient(135deg, var(--primary-500), var(--primary-600));
            border-radius: 8px;
            display: flex; align-items: center; justify-content: center;
            font-size: 0.9rem;
        }
        .portal-topbar .who {
            display: flex; align-items: center; gap: 1rem;
            font-size: 0.85rem;
            color: #d1d5db;
        }
        .portal-topbar .who a {
            color: #fff;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 0.35rem;
        }
        .portal-accent {
            height: 4px;
            background: linear-gradient(90deg, var(--primary-500), var(--primary-600));
        }
        .portal-content {
            max-width: 1000px;
            margin: 0 auto;
            padding: 2rem 1.5rem 3rem;
        }
    </style>
</head>
<body>
    <div class="portal-topbar">
        <div class="brand">
            <span class="badge-w">W</span>
            <span><?= e(APP_NAME) ?> — Portal Klien</span>
        </div>
        <div class="who">
            <span><?= e($client['name'] ?? '') ?></span>
            <a href="<?= url('/portal/logout') ?>"><i class="fas fa-sign-out-alt"></i> Keluar</a>
        </div>
    </div>
    <div class="portal-accent"></div>

    <div class="portal-content">
        <?php if (Session::hasFlash('success')): ?>
        <div class="alert alert-success mb-4">
            <?php foreach (Session::getFlash('success') as $msg): ?>
            <div><?= e($msg) ?></div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
        <?php if (Session::hasFlash('error')): ?>
        <div class="alert alert-danger mb-4">
            <?php foreach (Session::getFlash('error') as $msg): ?>
            <div><?= e($msg) ?></div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>

        <?= $content ?>
    </div>
</body>
</html>
