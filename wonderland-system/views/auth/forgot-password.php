<?php
/**
 * Forgot Password Page
 */
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
    <link rel="icon" type="image/png" href="<?= asset('images/favicon.png') ?>">
    
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
            background: linear-gradient(135deg, var(--primary-900) 0%, var(--primary-700) 50%, var(--primary-500) 100%);
            z-index: -1;
        }
        
        .auth-bg::before {
            content: '';
            position: absolute;
            inset: 0;
            background: url("data:image/svg+xml,%3Csvg width='60' height='60' viewBox='0 0 60 60' xmlns='http://www.w3.org/2000/svg'%3E%3Cg fill='none' fill-rule='evenodd'%3E%3Cg fill='%23ffffff' fill-opacity='0.05'%3E%3Cpath d='M36 34v-4h-2v4h-4v2h4v4h2v-4h4v-2h-4zm0-30V0h-2v4h-4v2h4v4h2V6h4V4h-4zM6 34v-4H4v4H0v2h4v4h2v-4h4v-2H6zM6 4V0H4v4H0v2h4v4h2V6h4V4H6z'/%3E%3C/g%3E%3C/g%3E%3C/svg%3E");
        }
        
        .forgot-wrapper {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 2rem;
        }
        
        .forgot-container {
            width: 100%;
            max-width: 420px;
        }
        
        .forgot-card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(20px);
            border-radius: 20px;
            box-shadow: 0 25px 50px rgba(0, 0, 0, 0.25);
            padding: 2.5rem;
            animation: fadeInUp 0.5s ease;
        }
        
        .forgot-icon {
            width: 80px;
            height: 80px;
            margin: 0 auto 1.5rem;
            display: flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(135deg, var(--warning) 0%, #d97706 100%);
            border-radius: 20px;
            box-shadow: 0 10px 30px rgba(245, 158, 11, 0.3);
        }
        
        .forgot-icon i {
            font-size: 2.5rem;
            color: white;
        }
        
        .forgot-title {
            text-align: center;
            font-size: 1.75rem;
            font-weight: 700;
            color: var(--gray-800);
            margin-bottom: 0.5rem;
        }
        
        .forgot-subtitle {
            text-align: center;
            color: var(--gray-500);
            margin-bottom: 2rem;
            line-height: 1.6;
        }
        
        .form-group {
            margin-bottom: 1.5rem;
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
            box-shadow: 0 0 0 4px rgba(59, 130, 246, 0.1);
        }
        
        .form-control::placeholder {
            color: var(--gray-400);
        }
        
        .btn-reset {
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
            box-shadow: 0 4px 15px rgba(59, 130, 246, 0.3);
            margin-bottom: 1rem;
        }
        
        .btn-reset:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(59, 130, 246, 0.4);
        }
        
        .btn-reset:disabled {
            opacity: 0.7;
            cursor: not-allowed;
            transform: none;
        }
        
        .back-link {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            color: var(--gray-600);
            font-size: 0.95rem;
            transition: color 0.2s ease;
        }
        
        .back-link:hover {
            color: var(--primary-600);
        }
        
        .forgot-footer {
            text-align: center;
            margin-top: 2rem;
            padding-top: 1.5rem;
            border-top: 1px solid var(--gray-200);
        }
        
        .forgot-footer p {
            font-size: 0.875rem;
            color: var(--gray-500);
            margin: 0;
        }
        
        /* Alert */
        .alert {
            padding: 1rem 1.25rem;
            border-radius: 10px;
            margin-bottom: 1.5rem;
            display: flex;
            align-items: flex-start;
            gap: 0.75rem;
        }
        
        .alert-success {
            background: #d1fae5;
            color: #065f46;
        }
        
        .alert-error {
            background: #fee2e2;
            color: #991b1b;
        }
        
        .alert i {
            font-size: 1.1rem;
            margin-top: 0.125rem;
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
    </style>
</head>
<body>
    <div class="auth-bg"></div>
    
    <div class="forgot-wrapper">
        <div class="forgot-container">
            <div class="forgot-card">
                <div class="forgot-icon">
                    <i class="fas fa-key"></i>
                </div>
                
                <h1 class="forgot-title">Lupa Password?</h1>
                <p class="forgot-subtitle">
                    Masukkan email Anda dan kami akan mengirimkan link untuk mereset password.
                </p>
                
                <?php
                $successMessages = Session::getFlash('success');
                foreach ($successMessages as $message):
                ?>
                <div class="alert alert-success">
                    <i class="fas fa-check-circle"></i>
                    <span><?= e($message) ?></span>
                </div>
                <?php endforeach; ?>
                
                <?php
                $errorMessages = Session::getFlash('error');
                foreach ($errorMessages as $message):
                ?>
                <div class="alert alert-error">
                    <i class="fas fa-exclamation-circle"></i>
                    <span><?= e($message) ?></span>
                </div>
                <?php endforeach; ?>
                
                <form method="POST" action="<?= url('/forgot-password') ?>" id="forgotForm">
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
                    
                    <button type="submit" class="btn-reset" id="btnReset">
                        <i class="fas fa-paper-plane"></i>
                        Kirim Link Reset
                    </button>
                    
                    <a href="<?= url('/login') ?>" class="back-link">
                        <i class="fas fa-arrow-left"></i>
                        Kembali ke halaman login
                    </a>
                </form>
                
                <div class="forgot-footer">
                    <p>&copy; <?= date('Y') ?> <?= e(APP_AUTHOR) ?>. All rights reserved.</p>
                </div>
            </div>
        </div>
    </div>
    
    <script>
        // Form submit loading state
        document.getElementById('forgotForm').addEventListener('submit', function() {
            const btn = document.getElementById('btnReset');
            btn.disabled = true;
            btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Mengirim...';
        });
    </script>
</body>
</html>
