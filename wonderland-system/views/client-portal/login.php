<?php
/**
 * Client Portal Login Page
 */

$loginLogoUrl = null;
try {
    $loginCompany = db()->fetchOne("SELECT logo FROM companies WHERE logo IS NOT NULL AND logo != '' LIMIT 1");
    if (!empty($loginCompany['logo'])) {
        $loginLogoUrl = uploadUrl($loginCompany['logo']);
    }
} catch (Exception $e) {
    $loginLogoUrl = null;
}
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
        .auth-bg {
            position: fixed;
            inset: 0;
            background: linear-gradient(135deg, #ffffff 0%, #fdf6e3 45%, #d4af37 100%);
            z-index: -1;
        }

        .login-wrapper {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 2rem;
        }

        .login-container { width: 100%; max-width: 420px; }

        .login-card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(20px);
            border-radius: 20px;
            box-shadow: 0 25px 50px rgba(0, 0, 0, 0.25);
            padding: 2.5rem;
        }

        .login-logo {
            width: 80px; height: 80px;
            margin: 0 auto 1.5rem;
            display: flex; align-items: center; justify-content: center;
            background: linear-gradient(135deg, var(--primary-500) 0%, var(--primary-600) 100%);
            border-radius: 20px;
            box-shadow: 0 10px 30px rgba(200, 155, 44, 0.3);
        }
        .login-logo i { font-size: 2.5rem; color: white; }
        .login-logo-img { width: auto; height: 90px; background: none; box-shadow: none; border-radius: 0; }
        .login-logo-img img { max-width: 220px; max-height: 90px; object-fit: contain; }

        .login-title { text-align: center; font-size: 1.5rem; font-weight: 700; color: var(--gray-800); margin-bottom: 0.25rem; }
        .login-subtitle { text-align: center; color: var(--gray-500); margin-bottom: 2rem; }

        .form-group { margin-bottom: 1.25rem; }
        .form-label { display: block; font-size: 0.875rem; font-weight: 500; color: var(--gray-700); margin-bottom: 0.5rem; }

        .input-icon-wrapper { position: relative; }
        .input-icon-wrapper .form-control { padding-left: 2.75rem; }
        .input-icon-wrapper .input-icon { position: absolute; left: 1rem; top: 50%; transform: translateY(-50%); color: var(--gray-400); }

        .form-control {
            width: 100%; padding: 0.75rem 1rem; font-size: 0.95rem; color: var(--gray-800);
            background: var(--gray-50); border: 2px solid var(--gray-200); border-radius: 10px;
            transition: all 0.2s ease;
        }
        .form-control:focus {
            outline: none; border-color: var(--primary-500); background: white;
            box-shadow: 0 0 0 4px rgba(200, 155, 44, 0.1);
        }

        .password-toggle {
            position: absolute; right: 1rem; top: 50%; transform: translateY(-50%);
            background: none; border: none; color: var(--gray-400); cursor: pointer; padding: 0.25rem;
        }
        .password-toggle:hover { color: var(--gray-600); }

        .btn-login {
            width: 100%; padding: 0.875rem; font-size: 1rem; font-weight: 600; color: white;
            background: linear-gradient(135deg, var(--primary-500) 0%, var(--primary-600) 100%);
            border: none; border-radius: 10px; cursor: pointer; transition: all 0.3s ease;
            box-shadow: 0 4px 15px rgba(200, 155, 44, 0.3); margin-top: 0.5rem;
        }
        .btn-login:hover { transform: translateY(-2px); box-shadow: 0 8px 25px rgba(200, 155, 44, 0.4); }
        .btn-login i { margin-left: 0.5rem; }

        .login-footer { text-align: center; margin-top: 2rem; padding-top: 1.5rem; border-top: 1px solid var(--gray-200); }
        .login-footer p { font-size: 0.875rem; color: var(--gray-500); margin: 0; }

        .toast-container { position: fixed; top: 1rem; right: 1rem; z-index: 1000; }
        .toast {
            min-width: 280px; padding: 1rem; border-radius: 10px; margin-bottom: 0.75rem;
            display: flex; align-items: center; gap: 0.75rem;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
        }
        .toast-success { background: #d1fae5; color: #065f46; }
        .toast-error { background: #fee2e2; color: #991b1b; }
    </style>
</head>
<body>
    <div class="auth-bg"></div>

    <div class="login-wrapper">
        <div class="login-container">
            <div class="login-card">
                <?php if ($loginLogoUrl): ?>
                <div class="login-logo login-logo-img">
                    <img src="<?= e($loginLogoUrl) ?>" alt="<?= e(APP_NAME) ?>">
                </div>
                <?php else: ?>
                <div class="login-logo">
                    <i class="fas fa-user-circle"></i>
                </div>
                <?php endif; ?>

                <h1 class="login-title">Portal Klien</h1>
                <p class="login-subtitle">Cek tagihan & riwayat pesanan Anda di <?= e(APP_NAME) ?></p>

                <form method="POST" action="<?= url('/portal/login') ?>" id="loginForm">
                    <?= csrfField() ?>
                    <div class="form-group">
                        <label for="client_code" class="form-label">Kode Klien</label>
                        <div class="input-icon-wrapper">
                            <input type="text" id="client_code" name="client_code" class="form-control"
                                   placeholder="Mis. 48213975" required autofocus autocomplete="username">
                            <i class="fas fa-id-badge input-icon"></i>
                        </div>
                    </div>

                    <button type="submit" class="btn-login" id="btnLogin">
                        Masuk
                        <i class="fas fa-arrow-right"></i>
                    </button>
                </form>

                <div class="login-footer">
                    <p>Kode klien Anda didapat dari <?= e(APP_NAME) ?>.</p>
                    <p>&copy; <?= date('Y') ?> <?= e(APP_AUTHOR) ?>.</p>
                </div>
            </div>
        </div>
    </div>

    <div class="toast-container" id="toastContainer">
        <?php
        $flashTypes = ['success' => 'toast-success', 'error' => 'toast-error'];
        foreach ($flashTypes as $type => $class):
            $messages = Session::getFlash($type);
            foreach ($messages as $message):
        ?>
        <div class="toast <?= $class ?>">
            <i class="fas fa-<?= $type === 'success' ? 'check-circle' : 'exclamation-circle' ?>"></i>
            <span><?= e($message) ?></span>
        </div>
        <?php
            endforeach;
        endforeach;
        ?>
    </div>

    <script>
        document.getElementById('loginForm').addEventListener('submit', function() {
            var btn = document.getElementById('btnLogin');
            btn.disabled = true;
            btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Memproses...';
        });

        document.querySelectorAll('.toast').forEach(function(toast) {
            setTimeout(function() { toast.remove(); }, 5000);
        });
    </script>
</body>
</html>
