<?php
/**
 * Login Page
 */
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <meta name="csrf-token" content="<?= Session::getCsrfToken() ?>">
    <meta name="theme-color" content="#574314">
    
    <title><?= e($pageTitle) ?> - <?= e(APP_NAME) ?></title>
    
    <!-- Favicon -->
    <link rel="icon" type="image/png" href="<?= asset('images/favicon.png') ?>">
    
    <!-- PWA -->
    <link rel="manifest" href="<?= url('/manifest.json') ?>">
    <meta name="apple-mobile-web-app-capable" content="yes">
    
    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    
    <!-- Main CSS -->
    <link rel="stylesheet" href="<?= asset('css/style.css') ?>?v=<?= APP_VERSION ?>">
    
    <style>
        .auth-bg {
            position: fixed;
            inset: 0;
            background: linear-gradient(135deg, #ffffff 0%, #fdf6e3 45%, #d4af37 100%);
            z-index: -1;
        }

        .auth-bg::before {
            content: '';
            position: absolute;
            inset: 0;
            background: url("data:image/svg+xml,%3Csvg width='60' height='60' viewBox='0 0 60 60' xmlns='http://www.w3.org/2000/svg'%3E%3Cg fill='none' fill-rule='evenodd'%3E%3Cg fill='%23d4af37' fill-opacity='0.12'%3E%3Cpath d='M36 34v-4h-2v4h-4v2h4v4h2v-4h4v-2h-4zm0-30V0h-2v4h-4v2h4v4h2V6h4V4h-4zM6 34v-4H4v4H0v2h4v4h2v-4h4v-2H6zM6 4V0H4v4H0v2h4v4h2V6h4V4H6z'/%3E%3C/g%3E%3C/g%3E%3C/svg%3E");
        }
        
        .login-wrapper {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 2rem;
        }
        
        .login-container {
            width: 100%;
            max-width: 420px;
        }
        
        .login-card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(20px);
            border-radius: 20px;
            box-shadow: 0 25px 50px rgba(0, 0, 0, 0.25);
            padding: 2.5rem;
            animation: fadeInUp 0.5s ease;
        }
        
        .login-logo {
            width: 80px;
            height: 80px;
            margin: 0 auto 1.5rem;
            display: flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(135deg, var(--primary-500) 0%, var(--primary-600) 100%);
            border-radius: 20px;
            box-shadow: 0 10px 30px rgba(200, 155, 44, 0.3);
        }
        
        .login-logo i {
            font-size: 2.5rem;
            color: white;
        }
        
        .login-title {
            text-align: center;
            font-size: 1.75rem;
            font-weight: 700;
            color: var(--gray-800);
            margin-bottom: 0.5rem;
        }
        
        .login-subtitle {
            text-align: center;
            color: var(--gray-500);
            margin-bottom: 2rem;
        }
        
        .form-group {
            margin-bottom: 1.25rem;
        }
        
        .form-label {
            display: block;
            font-size: 0.875rem;
            font-weight: 500;
            color: var(--gray-700);
            margin-bottom: 0.5rem;
        }
        
        .input-icon-wrapper {
            position: relative;
        }
        
        .input-icon-wrapper .form-control {
            padding-left: 2.75rem;
        }
        
        .input-icon-wrapper .input-icon {
            position: absolute;
            left: 1rem;
            top: 50%;
            transform: translateY(-50%);
            color: var(--gray-400);
            transition: color 0.2s ease;
        }
        
        .input-icon-wrapper .form-control:focus + .input-icon,
        .input-icon-wrapper .form-control:focus ~ .input-icon {
            color: var(--primary-500);
        }
        
        .form-control {
            width: 100%;
            padding: 0.75rem 1rem;
            font-size: 0.95rem;
            color: var(--gray-800);
            background: var(--gray-50);
            border: 2px solid var(--gray-200);
            border-radius: 10px;
            transition: all 0.2s ease;
        }
        
        .form-control:focus {
            outline: none;
            border-color: var(--primary-500);
            background: white;
            box-shadow: 0 0 0 4px rgba(200, 155, 44, 0.1);
        }
        
        .form-control::placeholder {
            color: var(--gray-400);
        }
        
        .password-toggle {
            position: absolute;
            right: 1rem;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            color: var(--gray-400);
            cursor: pointer;
            padding: 0.25rem;
            transition: color 0.2s ease;
        }
        
        .password-toggle:hover {
            color: var(--gray-600);
        }
        
        .remember-forgot {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 1.5rem;
        }
        
        .form-check {
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .form-check-input {
            width: 18px;
            height: 18px;
            border: 2px solid var(--gray-300);
            border-radius: 4px;
            cursor: pointer;
            accent-color: var(--primary-500);
        }
        
        .form-check-label {
            font-size: 0.875rem;
            color: var(--gray-600);
            cursor: pointer;
        }
        
        .forgot-link {
            font-size: 0.875rem;
            color: var(--primary-600);
            font-weight: 500;
        }
        
        .forgot-link:hover {
            color: var(--primary-700);
            text-decoration: underline;
        }
        
        .btn-login {
            width: 100%;
            padding: 0.875rem;
            font-size: 1rem;
            font-weight: 600;
            color: white;
            background: linear-gradient(135deg, var(--primary-500) 0%, var(--primary-600) 100%);
            border: none;
            border-radius: 10px;
            cursor: pointer;
            transition: all 0.3s ease;
            box-shadow: 0 4px 15px rgba(200, 155, 44, 0.3);
        }
        
        .btn-login:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(200, 155, 44, 0.4);
        }
        
        .btn-login:active {
            transform: translateY(0);
        }
        
        .btn-login:disabled {
            opacity: 0.7;
            cursor: not-allowed;
            transform: none;
        }
        
        .btn-login i {
            margin-left: 0.5rem;
        }
        
        .login-footer {
            text-align: center;
            margin-top: 2rem;
            padding-top: 1.5rem;
            border-top: 1px solid var(--gray-200);
        }
        
        .login-footer p {
            font-size: 0.875rem;
            color: var(--gray-500);
            margin: 0;
        }
        
        .login-footer a {
            color: var(--primary-600);
            font-weight: 500;
        }
        
        /* Toast */
        .toast-container {
            position: fixed;
            top: 1rem;
            right: 1rem;
            z-index: 1000;
        }
        
        .toast {
            min-width: 300px;
            padding: 1rem;
            border-radius: 10px;
            margin-bottom: 0.75rem;
            display: flex;
            align-items: center;
            gap: 0.75rem;
            animation: slideIn 0.3s ease;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
        }
        
        .toast-success {
            background: #d1fae5;
            color: #065f46;
        }
        
        .toast-error {
            background: #fee2e2;
            color: #991b1b;
        }
        
        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        @keyframes slideIn {
            from {
                opacity: 0;
                transform: translateX(100%);
            }
            to {
                opacity: 1;
                transform: translateX(0);
            }
        }
    </style>
</head>
<body>
    <div class="auth-bg"></div>
    
    <div class="login-wrapper">
        <div class="login-container">
            <div class="login-card">
                <div class="login-logo">
                    <i class="fas fa-paper-plane"></i>
                </div>
                
                <h1 class="login-title"><?= e(APP_NAME) ?></h1>
                <p class="login-subtitle">Silakan masuk ke akun Anda</p>
                
                <form method="POST" action="<?= url('/login') ?>" id="loginForm">
                    <?= csrfField() ?>
                    
                    <div class="form-group">
                        <label for="email" class="form-label">Email</label>
                        <div class="input-icon-wrapper">
                            <input 
                                type="email" 
                                id="email" 
                                name="email" 
                                class="form-control" 
                                placeholder="nama@email.com"
                                value="<?= e(old('email')) ?>"
                                required
                                autofocus
                            >
                            <i class="fas fa-envelope input-icon"></i>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="password" class="form-label">Password</label>
                        <div class="input-icon-wrapper">
                            <input 
                                type="password" 
                                id="password" 
                                name="password" 
                                class="form-control" 
                                placeholder="••••••••"
                                required
                            >
                            <i class="fas fa-lock input-icon"></i>
                            <button type="button" class="password-toggle" id="togglePassword">
                                <i class="fas fa-eye"></i>
                            </button>
                        </div>
                    </div>
                    
                    <div class="remember-forgot">
                        <div class="form-check">
                            <input type="checkbox" id="remember" name="remember" class="form-check-input">
                            <label for="remember" class="form-check-label">Ingat saya</label>
                        </div>
                        <a href="<?= url('/forgot-password') ?>" class="forgot-link">Lupa password?</a>
                    </div>
                    
                    <button type="submit" class="btn-login" id="btnLogin">
                        Masuk
                        <i class="fas fa-arrow-right"></i>
                    </button>
                </form>
                
                <div class="login-footer">
                    <p>&copy; <?= date('Y') ?> <?= e(APP_AUTHOR) ?>. All rights reserved.</p>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Toast Container -->
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
        // Toggle password visibility
        document.getElementById('togglePassword').addEventListener('click', function() {
            const password = document.getElementById('password');
            const icon = this.querySelector('i');
            
            if (password.type === 'password') {
                password.type = 'text';
                icon.classList.remove('fa-eye');
                icon.classList.add('fa-eye-slash');
            } else {
                password.type = 'password';
                icon.classList.remove('fa-eye-slash');
                icon.classList.add('fa-eye');
            }
        });
        
        // Form submit loading state
        document.getElementById('loginForm').addEventListener('submit', function() {
            const btn = document.getElementById('btnLogin');
            btn.disabled = true;
            btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Memproses...';
        });
        
        // Auto-hide toasts
        document.querySelectorAll('.toast').forEach(function(toast) {
            setTimeout(function() {
                toast.style.animation = 'slideIn 0.3s ease reverse';
                setTimeout(function() {
                    toast.remove();
                }, 300);
            }, 5000);
        });
    </script>
</body>
</html>
