<?php
/**
 * Install Step 5 - Finish
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
            --primary-500: #3b82f6;
            --primary-600: #2563eb;
            --primary-700: #1d4ed8;
            --primary-900: #1e3a8a;
            --success: #10b981;
            --danger: #ef4444;
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
            background: linear-gradient(135deg, var(--success) 0%, #059669 100%);
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
        .alert-warning { background: #fef3c7; color: #92400e; }
        .alert i { margin-top: 0.125rem; }
        
        .summary-card {
            background: var(--gray-50);
            border-radius: 12px;
            padding: 1.5rem;
            margin-bottom: 1.5rem;
        }
        
        .summary-title {
            font-size: 0.85rem;
            font-weight: 600;
            color: var(--gray-500);
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 1rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .summary-item {
            display: flex;
            padding: 0.75rem 0;
            border-bottom: 1px solid var(--gray-200);
        }
        
        .summary-item:last-child { border-bottom: none; }
        
        .summary-label {
            width: 40%;
            font-size: 0.9rem;
            color: var(--gray-500);
        }
        
        .summary-value {
            flex: 1;
            font-size: 0.9rem;
            font-weight: 500;
            color: var(--gray-800);
        }
        
        .checklist {
            background: var(--gray-50);
            border-radius: 12px;
            overflow: hidden;
        }
        
        .checklist-item {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            padding: 1rem 1.25rem;
            border-bottom: 1px solid var(--gray-200);
        }
        
        .checklist-item:last-child { border-bottom: none; }
        
        .checklist-icon {
            width: 24px;
            height: 24px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.75rem;
            background: #d1fae5;
            color: var(--success);
        }
        
        .checklist-text {
            font-size: 0.9rem;
            color: var(--gray-700);
        }
        
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
        
        .btn-success {
            background: linear-gradient(135deg, var(--success) 0%, #059669 100%);
            color: white; box-shadow: 0 4px 15px rgba(16, 185, 129, 0.3);
        }
        .btn-success:hover { transform: translateY(-2px); box-shadow: 0 6px 20px rgba(16, 185, 129, 0.4); }
        .btn-success:disabled { opacity: 0.7; cursor: not-allowed; transform: none; }
        
        .btn-secondary { background: var(--gray-200); color: var(--gray-700); }
        .btn-secondary:hover { background: var(--gray-300); }
        
        .step-info { font-size: 0.9rem; color: var(--gray-500); }
        .btn-group { display: flex; gap: 0.75rem; }
    </style>
</head>
<body>
    <div class="install-container">
        <div class="install-card">
            <div class="install-header">
                <div class="install-logo"><i class="fas fa-flag-checkered"></i></div>
                <h1 class="install-title">Siap untuk Instalasi!</h1>
                <p class="install-subtitle">Periksa kembali data Anda sebelum melanjutkan</p>
            </div>
            
            <div class="steps-indicator">
                <?php for ($i = 1; $i <= $totalSteps; $i++): ?>
                <div class="step-dot <?= $i === $step ? 'active' : ($i < $step ? 'completed' : '') ?>"></div>
                <?php endfor; ?>
            </div>
            
            <div class="install-body">
                <?php foreach ($flashErrors as $error): ?>
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-circle"></i>
                    <span><?= e($error) ?></span>
                </div>
                <?php endforeach; ?>
                
                <div class="alert alert-warning">
                    <i class="fas fa-exclamation-triangle"></i>
                    <div>
                        <strong>Perhatian!</strong><br>
                        Pastikan semua data sudah benar. Klik "Mulai Instalasi" untuk melanjutkan.
                    </div>
                </div>
                
                <!-- Company Summary -->
                <div class="summary-card">
                    <div class="summary-title">
                        <i class="fas fa-building"></i>
                        Data Perusahaan
                    </div>
                    <div class="summary-item">
                        <span class="summary-label">Nama Perusahaan</span>
                        <span class="summary-value"><?= e($summary['company']['name']) ?></span>
                    </div>
                    <?php if ($summary['company']['address']): ?>
                    <div class="summary-item">
                        <span class="summary-label">Alamat</span>
                        <span class="summary-value"><?= e($summary['company']['address']) ?></span>
                    </div>
                    <?php endif; ?>
                    <?php if ($summary['company']['phone']): ?>
                    <div class="summary-item">
                        <span class="summary-label">Telepon</span>
                        <span class="summary-value"><?= e($summary['company']['phone']) ?></span>
                    </div>
                    <?php endif; ?>
                    <?php if ($summary['company']['email']): ?>
                    <div class="summary-item">
                        <span class="summary-label">Email</span>
                        <span class="summary-value"><?= e($summary['company']['email']) ?></span>
                    </div>
                    <?php endif; ?>
                </div>
                
                <!-- Admin Summary -->
                <div class="summary-card">
                    <div class="summary-title">
                        <i class="fas fa-user-shield"></i>
                        Akun Administrator
                    </div>
                    <div class="summary-item">
                        <span class="summary-label">Nama</span>
                        <span class="summary-value"><?= e($summary['admin']['name']) ?></span>
                    </div>
                    <div class="summary-item">
                        <span class="summary-label">Email</span>
                        <span class="summary-value"><?= e($summary['admin']['email']) ?></span>
                    </div>
                    <?php if ($summary['admin']['phone']): ?>
                    <div class="summary-item">
                        <span class="summary-label">Telepon</span>
                        <span class="summary-value"><?= e($summary['admin']['phone']) ?></span>
                    </div>
                    <?php endif; ?>
                    <div class="summary-item">
                        <span class="summary-label">Role</span>
                        <span class="summary-value">Super Admin</span>
                    </div>
                </div>
                
                <!-- What will be installed -->
                <h3 class="section-title">
                    <i class="fas fa-tasks"></i>
                    Yang akan diinstal
                </h3>
                
                <div class="checklist">
                    <div class="checklist-item">
                        <span class="checklist-icon"><i class="fas fa-check"></i></span>
                        <span class="checklist-text">Struktur tabel database (23 tabel)</span>
                    </div>
                    <div class="checklist-item">
                        <span class="checklist-icon"><i class="fas fa-check"></i></span>
                        <span class="checklist-text">Stored procedures & functions</span>
                    </div>
                    <div class="checklist-item">
                        <span class="checklist-icon"><i class="fas fa-check"></i></span>
                        <span class="checklist-text">Chart of Accounts standar (52 akun)</span>
                    </div>
                    <div class="checklist-item">
                        <span class="checklist-icon"><i class="fas fa-check"></i></span>
                        <span class="checklist-text">Pengaturan default perusahaan</span>
                    </div>
                    <div class="checklist-item">
                        <span class="checklist-icon"><i class="fas fa-check"></i></span>
                        <span class="checklist-text">Kas utama untuk akuntansi</span>
                    </div>
                </div>
            </div>
            
            <div class="install-footer">
                <span class="step-info">Langkah <?= $step ?> dari <?= $totalSteps ?></span>
                
                <form method="POST" action="<?= url('/install/step/5') ?>" id="installForm">
                    <?= csrfField() ?>
                    
                    <div class="btn-group">
                        <a href="<?= url('/install/step/4') ?>" class="btn btn-secondary">
                            <i class="fas fa-arrow-left"></i>
                            Kembali
                        </a>
                        <button type="submit" class="btn btn-success" id="btnInstall">
                            <i class="fas fa-rocket"></i>
                            Mulai Instalasi
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <script>
        document.getElementById('installForm').addEventListener('submit', function() {
            const btn = document.getElementById('btnInstall');
            btn.disabled = true;
            btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Menginstal...';
            
            // Prevent double submit
            this.querySelector('a.btn-secondary').style.pointerEvents = 'none';
            this.querySelector('a.btn-secondary').style.opacity = '0.5';
        });
    </script>
</body>
</html>
