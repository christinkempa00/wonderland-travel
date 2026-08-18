<?php
/**
 * Install Step 4 - Admin Account
 */

$flashErrors = Session::getFlash('error');
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($pageTitle) ?> - <?= e(APP_NAME) ?></title>
    
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    
    <style>
        :root {
            --primary-500: #c89b2c;
            --primary-600: #a67f20;
            --primary-700: #85661a;
            --primary-900: #574314;
            --success: #10b981;
            --danger: #ef4444;
            --warning: #f59e0b;
            --gray-50: #f8fafc;
            --gray-100: #f1f5f9;
            --gray-200: #e2e8f0;
            --gray-300: #cbd5e1;
            --gray-400: #94a3b8;
            --gray-500: #64748b;
            --gray-600: #475569;
            --gray-700: #334155;
            --gray-800: #1e293b;
        }
        
        * { margin: 0; padding: 0; box-sizing: border-box; }
        
        body {
            font-family: 'Inter', sans-serif;
            background: linear-gradient(135deg, var(--primary-900) 0%, var(--primary-700) 50%, var(--primary-500) 100%);
            min-height: 100vh;
            padding: 2rem;
        }
        
        .install-container { max-width: 700px; margin: 0 auto; }
        
        .install-card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(20px);
            border-radius: 20px;
            box-shadow: 0 25px 50px rgba(0, 0, 0, 0.25);
            overflow: hidden;
            animation: fadeInUp 0.5s ease;
        }
        
        @keyframes fadeInUp {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        .install-header {
            background: linear-gradient(135deg, var(--primary-600) 0%, var(--primary-700) 100%);
            padding: 2rem; text-align: center; color: white;
        }
        
        .install-logo {
            width: 70px; height: 70px;
            background: rgba(255,255,255,0.2); border-radius: 16px;
            display: flex; align-items: center; justify-content: center;
            margin: 0 auto 1rem; font-size: 2rem;
        }
        
        .install-title { font-size: 1.5rem; font-weight: 700; margin-bottom: 0.5rem; }
        .install-subtitle { opacity: 0.9; font-size: 0.95rem; }
        
        .steps-indicator {
            display: flex; justify-content: center; gap: 0.5rem;
            padding: 1.5rem; background: var(--gray-50); border-bottom: 1px solid var(--gray-200);
        }
        
        .step-dot {
            width: 12px; height: 12px; border-radius: 50%;
            background: var(--gray-300); transition: all 0.3s ease;
        }
        .step-dot.active { background: var(--primary-500); transform: scale(1.2); }
        .step-dot.completed { background: var(--success); }
        
        .install-body { padding: 2rem; }
        
        .section-title {
            font-size: 1.1rem; font-weight: 600; color: var(--gray-800);
            margin-bottom: 1rem; display: flex; align-items: center; gap: 0.5rem;
        }
        .section-title i { color: var(--primary-500); }
        
        .alert {
            padding: 1rem 1.25rem; border-radius: 10px; margin-bottom: 1.5rem;
            display: flex; align-items: flex-start; gap: 0.75rem;
        }
        .alert-danger { background: #fee2e2; color: #991b1b; }
        .alert-info { background: #faefd0; color: #6b5216; }
        .alert i { margin-top: 0.125rem; }
        
        .form-group { margin-bottom: 1.25rem; }
        
        .form-label {
            display: block; font-size: 0.9rem; font-weight: 500;
            color: var(--gray-700); margin-bottom: 0.5rem;
        }
        .form-label .required { color: var(--danger); }
        
        .input-wrapper { position: relative; }
        
        .form-control {
            width: 100%; padding: 0.75rem 1rem; padding-left: 2.75rem;
            font-size: 0.95rem; color: var(--gray-800);
            background: var(--gray-50); border: 2px solid var(--gray-200);
            border-radius: 10px; transition: all 0.2s ease;
        }
        
        .form-control:focus {
            outline: none; border-color: var(--primary-500);
            background: white; box-shadow: 0 0 0 4px rgba(200, 155, 44, 0.1);
        }
        
        .input-icon {
            position: absolute; left: 1rem; top: 50%; transform: translateY(-50%);
            color: var(--gray-400);
        }
        
        .password-toggle {
            position: absolute; right: 1rem; top: 50%; transform: translateY(-50%);
            background: none; border: none; color: var(--gray-400); cursor: pointer;
        }
        
        .form-text { font-size: 0.8rem; color: var(--gray-500); margin-top: 0.375rem; }
        
        .password-strength {
            height: 4px; background: var(--gray-200); border-radius: 2px;
            margin-top: 0.5rem; overflow: hidden;
        }
        .password-strength-bar {
            height: 100%; width: 0; transition: all 0.3s ease;
        }
        .strength-weak { width: 33%; background: var(--danger); }
        .strength-medium { width: 66%; background: var(--warning); }
        .strength-strong { width: 100%; background: var(--success); }
        
        .install-footer {
            padding: 1.5rem 2rem; background: var(--gray-50);
            display: flex; justify-content: space-between; align-items: center;
        }
        
        .btn {
            display: inline-flex; align-items: center; gap: 0.5rem;
            padding: 0.75rem 1.5rem; font-size: 0.95rem; font-weight: 500;
            border-radius: 10px; border: none; cursor: pointer;
            transition: all 0.3s ease; text-decoration: none;
        }
        
        .btn-primary {
            background: linear-gradient(135deg, var(--primary-500) 0%, var(--primary-600) 100%);
            color: white; box-shadow: 0 4px 15px rgba(200, 155, 44, 0.3);
        }
        .btn-primary:hover { transform: translateY(-2px); box-shadow: 0 6px 20px rgba(200, 155, 44, 0.4); }
        
        .btn-secondary { background: var(--gray-200); color: var(--gray-700); }
        .btn-secondary:hover { background: var(--gray-300); }
        
        .step-info { font-size: 0.9rem; color: var(--gray-500); }
        .btn-group { display: flex; gap: 0.75rem; }
        
        .form-row { display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; }
        @media (max-width: 600px) { .form-row { grid-template-columns: 1fr; } }
    </style>
</head>
<body>
    <div class="install-container">
        <div class="install-card">
            <div class="install-header">
                <div class="install-logo"><i class="fas fa-paper-plane"></i></div>
                <h1 class="install-title"><?= e(APP_NAME) ?></h1>
                <p class="install-subtitle">Wizard Instalasi</p>
            </div>
            
            <div class="steps-indicator">
                <?php for ($i = 1; $i <= $totalSteps; $i++): ?>
                <div class="step-dot <?= $i === $step ? 'active' : ($i < $step ? 'completed' : '') ?>"></div>
                <?php endfor; ?>
            </div>
            
            <div class="install-body">
                <h2 class="section-title">
                    <i class="fas fa-user-shield"></i>
                    Langkah 4: Akun Administrator
                </h2>
                
                <p style="color: var(--gray-600); margin-bottom: 1.5rem;">
                    Buat akun Super Admin untuk mengelola sistem.
                </p>
                
                <?php foreach ($flashErrors as $error): ?>
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-circle"></i>
                    <span><?= e($error) ?></span>
                </div>
                <?php endforeach; ?>
                
                <div class="alert alert-info">
                    <i class="fas fa-info-circle"></i>
                    <span>Akun ini memiliki akses penuh ke seluruh sistem. Simpan kredensial dengan aman.</span>
                </div>
                
                <form method="POST" action="<?= url('/install/step/4') ?>" id="adminForm">
                    <?= csrfField() ?>
                    
                    <div class="form-group">
                        <label class="form-label">
                            Nama Lengkap <span class="required">*</span>
                        </label>
                        <div class="input-wrapper">
                            <input type="text" name="admin_name" class="form-control" 
                                   value="<?= e($adminData['name']) ?>" 
                                   placeholder="John Doe" required>
                            <i class="fas fa-user input-icon"></i>
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label">
                                Email <span class="required">*</span>
                            </label>
                            <div class="input-wrapper">
                                <input type="email" name="admin_email" class="form-control" 
                                       value="<?= e($adminData['email']) ?>" 
                                       placeholder="admin@email.com" required>
                                <i class="fas fa-envelope input-icon"></i>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label">No. Telepon</label>
                            <div class="input-wrapper">
                                <input type="text" name="admin_phone" class="form-control" 
                                       value="<?= e($adminData['phone']) ?>" 
                                       placeholder="08123456789">
                                <i class="fas fa-phone input-icon"></i>
                            </div>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">
                            Password <span class="required">*</span>
                        </label>
                        <div class="input-wrapper">
                            <input type="password" name="admin_password" id="password" class="form-control" 
                                   placeholder="Minimal 8 karakter" minlength="8" required>
                            <i class="fas fa-lock input-icon"></i>
                            <button type="button" class="password-toggle" onclick="togglePassword('password', this)">
                                <i class="fas fa-eye"></i>
                            </button>
                        </div>
                        <div class="password-strength">
                            <div class="password-strength-bar" id="strengthBar"></div>
                        </div>
                        <p class="form-text">Gunakan kombinasi huruf, angka, dan simbol</p>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">
                            Konfirmasi Password <span class="required">*</span>
                        </label>
                        <div class="input-wrapper">
                            <input type="password" name="admin_password_confirmation" id="password_confirm" 
                                   class="form-control" placeholder="Ulangi password" required>
                            <i class="fas fa-lock input-icon"></i>
                            <button type="button" class="password-toggle" onclick="togglePassword('password_confirm', this)">
                                <i class="fas fa-eye"></i>
                            </button>
                        </div>
                    </div>
                </form>
            </div>
            
            <div class="install-footer">
                <span class="step-info">Langkah <?= $step ?> dari <?= $totalSteps ?></span>
                
                <div class="btn-group">
                    <a href="<?= url('/install/step/3') ?>" class="btn btn-secondary">
                        <i class="fas fa-arrow-left"></i>
                        Kembali
                    </a>
                    <button type="submit" form="adminForm" class="btn btn-primary">
                        Lanjutkan
                        <i class="fas fa-arrow-right"></i>
                    </button>
                </div>
            </div>
        </div>
    </div>
    
    <script>
        function togglePassword(inputId, btn) {
            const input = document.getElementById(inputId);
            const icon = btn.querySelector('i');
            
            if (input.type === 'password') {
                input.type = 'text';
                icon.classList.remove('fa-eye');
                icon.classList.add('fa-eye-slash');
            } else {
                input.type = 'password';
                icon.classList.remove('fa-eye-slash');
                icon.classList.add('fa-eye');
            }
        }
        
        document.getElementById('password').addEventListener('input', function() {
            const password = this.value;
            const bar = document.getElementById('strengthBar');
            
            bar.className = 'password-strength-bar';
            
            if (password.length === 0) {
                bar.style.width = '0';
                return;
            }
            
            let strength = 0;
            if (password.length >= 8) strength++;
            if (password.length >= 12) strength++;
            if (/\d/.test(password)) strength++;
            if (/[!@#$%^&*(),.?":{}|<>]/.test(password)) strength++;
            if (/[a-z]/.test(password) && /[A-Z]/.test(password)) strength++;
            
            if (strength <= 2) bar.classList.add('strength-weak');
            else if (strength <= 4) bar.classList.add('strength-medium');
            else bar.classList.add('strength-strong');
        });
        
        document.getElementById('adminForm').addEventListener('submit', function(e) {
            const password = document.getElementById('password').value;
            const confirm = document.getElementById('password_confirm').value;
            
            if (password !== confirm) {
                e.preventDefault();
                alert('Konfirmasi password tidak cocok!');
            }
        });
    </script>
</body>
</html>
