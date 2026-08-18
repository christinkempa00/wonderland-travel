<?php
/**
 * ================================================================
 * MIGRATE EXISTING ORDERS TO ACCOUNTING SYSTEM
 * ================================================================
 * 
 * Script ini akan:
 * 1. Generate jurnal piutang untuk order yang sudah invoiced/paid
 * 2. Generate jurnal pembayaran untuk order yang sudah paid
 * 
 * CARA PAKAI:
 * 1. Upload file ini ke folder travel/
 * 2. Akses: https://travel.pelitaintimulia.com/migrate-accounting.php
 * 3. Klik tombol untuk menjalankan migrasi
 * 4. HAPUS file ini setelah selesai!
 * 
 * PENTING: Backup database sebelum menjalankan script ini!
 */

// Load bootstrap
require_once __DIR__ . '/config/bootstrap.php';

// Check if logged in and is admin
if (!Session::isLoggedIn()) {
    die('Silakan login terlebih dahulu.');
}

if (!Session::can('settings.manage')) {
    die('Anda tidak memiliki akses ke halaman ini.');
}

// Include accounting helper
require_once HELPERS_PATH . '/AccountingHelper.php';

$companyId = Session::companyId();
$results = [];
$errors = [];

// Process migration
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];
    
    if ($action === 'check') {
        // Just check, don't execute
        $results['mode'] = 'check';
        
        // Count orders needing invoice journals
        $needInvoiceJournal = db()->fetchOne(
            "SELECT COUNT(*) as total FROM orders 
             WHERE company_id = ? 
             AND status IN ('invoiced', 'paid', 'completed')
             AND invoice_journal_id IS NULL
             AND total_final_price > 0",
            [$companyId]
        );
        $results['need_invoice_journal'] = (int)$needInvoiceJournal['total'];
        
        // Count orders with payments but no journal
        $needPaymentJournal = db()->fetchOne(
            "SELECT COUNT(*) as total FROM orders 
             WHERE company_id = ? 
             AND paid_amount > 0
             AND id NOT IN (SELECT DISTINCT order_id FROM order_payments WHERE order_id IS NOT NULL)",
            [$companyId]
        );
        $results['need_payment_journal'] = (int)$needPaymentJournal['total'];
        
        // Check accounting settings
        $receivableAccount = getAccountingSetting($companyId, 'account_receivable');
        $revenueAccount = getAccountingSetting($companyId, 'revenue_bus');
        $cashAccount = getAccountingSetting($companyId, 'default_cash');
        $bankAccount = getAccountingSetting($companyId, 'default_bank');
        
        $results['settings'] = [
            'account_receivable' => $receivableAccount ? '✅ Configured' : '❌ Not Set',
            'revenue_account' => $revenueAccount ? '✅ Configured' : '❌ Not Set',
            'default_cash' => $cashAccount ? '✅ Configured' : '❌ Not Set',
            'default_bank' => $bankAccount ? '✅ Configured' : '❌ Not Set'
        ];
        
        $results['ready'] = $receivableAccount && $revenueAccount && ($cashAccount || $bankAccount);
        
    } elseif ($action === 'migrate_invoices') {
        // Generate invoice journals
        $results['mode'] = 'migrate_invoices';
        
        $orders = db()->fetchAll(
            "SELECT * FROM orders 
             WHERE company_id = ? 
             AND status IN ('invoiced', 'paid', 'completed')
             AND invoice_journal_id IS NULL
             AND total_final_price > 0
             ORDER BY order_date, id",
            [$companyId]
        );
        
        $created = 0;
        $failed = 0;
        
        foreach ($orders as $order) {
            try {
                $journalId = createOrderInvoiceJournal($order);
                if ($journalId) {
                    $created++;
                } else {
                    $failed++;
                    $errors[] = "Order {$order['order_number']}: Failed to create journal";
                }
            } catch (Exception $e) {
                $failed++;
                $errors[] = "Order {$order['order_number']}: " . $e->getMessage();
            }
        }
        
        $results['created'] = $created;
        $results['failed'] = $failed;
        $results['total'] = count($orders);
        
    } elseif ($action === 'migrate_payments') {
        // Generate payment journals for orders that are already paid
        $results['mode'] = 'migrate_payments';
        
        // Get orders with payment but no payment records
        $orders = db()->fetchAll(
            "SELECT * FROM orders 
             WHERE company_id = ? 
             AND paid_amount > 0
             AND id NOT IN (SELECT DISTINCT order_id FROM order_payments WHERE order_id IS NOT NULL)
             ORDER BY paid_at, id",
            [$companyId]
        );
        
        $created = 0;
        $failed = 0;
        
        foreach ($orders as $order) {
            try {
                // Use default bank account for migration
                $bankAccount = getAccountingSetting($companyId, 'default_bank');
                
                // Get bank_cash_id for this account
                $bankCash = db()->fetchOne(
                    "SELECT id FROM bank_cash WHERE company_id = ? AND account_id = ? LIMIT 1",
                    [$companyId, $bankAccount]
                );
                $bankCashId = $bankCash ? (int)$bankCash['id'] : null;
                
                $result = createOrderPaymentJournal(
                    $order,
                    (float)$order['paid_amount'],
                    'transfer', // default to transfer for migration
                    $bankCashId,
                    'MIGRASI-' . $order['order_number'],
                    'Migrasi data pembayaran existing'
                );
                
                if ($result['success']) {
                    $created++;
                } else {
                    $failed++;
                    $errors[] = "Order {$order['order_number']}: " . $result['message'];
                }
            } catch (Exception $e) {
                $failed++;
                $errors[] = "Order {$order['order_number']}: " . $e->getMessage();
            }
        }
        
        $results['created'] = $created;
        $results['failed'] = $failed;
        $results['total'] = count($orders);
    }
}

?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Migrasi Akuntansi - Travel Management</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #1a1a2e 0%, #16213e 100%);
            min-height: 100vh;
            color: #fff;
        }
        .glass-card {
            background: rgba(255,255,255,0.1);
            backdrop-filter: blur(10px);
            border-radius: 16px;
            padding: 2rem;
            margin-bottom: 1.5rem;
        }
        .btn-gradient {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: none;
            color: white;
        }
        .btn-gradient:hover {
            background: linear-gradient(135deg, #764ba2 0%, #667eea 100%);
            color: white;
        }
        .result-box {
            background: rgba(0,0,0,0.2);
            border-radius: 8px;
            padding: 1rem;
            margin-top: 1rem;
        }
        .text-success { color: #00d26a !important; }
        .text-danger { color: #ff6b6b !important; }
        .text-warning { color: #ffc107 !important; }
    </style>
</head>
<body>
    <div class="container py-5">
        <div class="row justify-content-center">
            <div class="col-lg-8">
                
                <!-- Header -->
                <div class="text-center mb-5">
                    <h1><i class="fas fa-sync-alt me-2"></i>Migrasi Akuntansi</h1>
                    <p class="text-muted">Generate jurnal akuntansi dari order yang sudah ada</p>
                </div>
                
                <!-- Warning -->
                <div class="alert alert-warning mb-4">
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    <strong>Perhatian!</strong> Backup database sebelum menjalankan migrasi. 
                    Hapus file ini setelah selesai!
                </div>
                
                <!-- Results -->
                <?php if (!empty($results)): ?>
                <div class="glass-card">
                    <h4>
                        <?php if ($results['mode'] === 'check'): ?>
                        <i class="fas fa-search text-info me-2"></i>Hasil Pengecekan
                        <?php else: ?>
                        <i class="fas fa-check-circle text-success me-2"></i>Hasil Migrasi
                        <?php endif; ?>
                    </h4>
                    
                    <?php if ($results['mode'] === 'check'): ?>
                    <div class="result-box">
                        <h5>Status Konfigurasi:</h5>
                        <ul class="list-unstyled mb-3">
                            <?php foreach ($results['settings'] as $key => $status): ?>
                            <li><?= ucwords(str_replace('_', ' ', $key)) ?>: <?= $status ?></li>
                            <?php endforeach; ?>
                        </ul>
                        
                        <h5>Order yang Perlu Dimigrasi:</h5>
                        <ul class="list-unstyled">
                            <li>Jurnal Invoice: <strong><?= $results['need_invoice_journal'] ?></strong> order</li>
                            <li>Jurnal Pembayaran: <strong><?= $results['need_payment_journal'] ?></strong> order</li>
                        </ul>
                        
                        <?php if (!$results['ready']): ?>
                        <div class="alert alert-danger mt-3 mb-0">
                            <i class="fas fa-times-circle me-2"></i>
                            Konfigurasi akun akuntansi belum lengkap. Silakan setup terlebih dahulu di 
                            <strong>Pengaturan → Akuntansi</strong>
                        </div>
                        <?php else: ?>
                        <div class="alert alert-success mt-3 mb-0">
                            <i class="fas fa-check-circle me-2"></i>
                            Konfigurasi siap! Anda bisa menjalankan migrasi.
                        </div>
                        <?php endif; ?>
                    </div>
                    
                    <?php else: ?>
                    <div class="result-box">
                        <p class="mb-2">
                            <strong>Total Diproses:</strong> <?= $results['total'] ?> order
                        </p>
                        <p class="mb-2 text-success">
                            <strong>Berhasil:</strong> <?= $results['created'] ?> jurnal dibuat
                        </p>
                        <?php if ($results['failed'] > 0): ?>
                        <p class="mb-0 text-danger">
                            <strong>Gagal:</strong> <?= $results['failed'] ?> order
                        </p>
                        <?php endif; ?>
                    </div>
                    
                    <?php if (!empty($errors)): ?>
                    <div class="result-box mt-3">
                        <h5 class="text-danger">Errors:</h5>
                        <ul class="mb-0 small">
                            <?php foreach (array_slice($errors, 0, 10) as $error): ?>
                            <li><?= e($error) ?></li>
                            <?php endforeach; ?>
                            <?php if (count($errors) > 10): ?>
                            <li>... dan <?= count($errors) - 10 ?> error lainnya</li>
                            <?php endif; ?>
                        </ul>
                    </div>
                    <?php endif; ?>
                    <?php endif; ?>
                </div>
                <?php endif; ?>
                
                <!-- Actions -->
                <div class="glass-card">
                    <h4><i class="fas fa-cogs me-2"></i>Aksi Migrasi</h4>
                    
                    <div class="row g-3 mt-3">
                        <!-- Check Button -->
                        <div class="col-12">
                            <form method="POST">
                                <input type="hidden" name="action" value="check">
                                <button type="submit" class="btn btn-info btn-lg w-100">
                                    <i class="fas fa-search me-2"></i>
                                    Step 1: Cek Status & Konfigurasi
                                </button>
                            </form>
                            <small class="text-muted d-block mt-1">
                                Cek berapa order yang perlu dimigrasi dan status konfigurasi
                            </small>
                        </div>
                        
                        <!-- Migrate Invoice Journals -->
                        <div class="col-md-6">
                            <form method="POST" onsubmit="return confirm('Yakin ingin generate jurnal invoice?\n\nProses ini akan membuat jurnal piutang untuk semua order yang sudah invoiced/paid.')">
                                <input type="hidden" name="action" value="migrate_invoices">
                                <button type="submit" class="btn btn-gradient btn-lg w-100">
                                    <i class="fas fa-file-invoice me-2"></i>
                                    Step 2: Generate Jurnal Invoice
                                </button>
                            </form>
                            <small class="text-muted d-block mt-1">
                                Buat jurnal piutang untuk order yang sudah invoiced
                            </small>
                        </div>
                        
                        <!-- Migrate Payment Journals -->
                        <div class="col-md-6">
                            <form method="POST" onsubmit="return confirm('Yakin ingin generate jurnal pembayaran?\n\nProses ini akan membuat jurnal kas/bank untuk order yang sudah dibayar.')">
                                <input type="hidden" name="action" value="migrate_payments">
                                <button type="submit" class="btn btn-gradient btn-lg w-100">
                                    <i class="fas fa-money-bill-wave me-2"></i>
                                    Step 3: Generate Jurnal Pembayaran
                                </button>
                            </form>
                            <small class="text-muted d-block mt-1">
                                Buat jurnal pembayaran untuk order yang sudah paid
                            </small>
                        </div>
                    </div>
                </div>
                
                <!-- Instructions -->
                <div class="glass-card">
                    <h4><i class="fas fa-info-circle me-2"></i>Petunjuk</h4>
                    <ol class="mb-0">
                        <li class="mb-2">Pastikan <strong>Accounting Settings</strong> sudah dikonfigurasi (akun piutang, pendapatan, kas, bank)</li>
                        <li class="mb-2">Klik <strong>Step 1</strong> untuk cek status konfigurasi dan jumlah order yang perlu dimigrasi</li>
                        <li class="mb-2">Klik <strong>Step 2</strong> untuk generate jurnal piutang dari order yang sudah invoiced</li>
                        <li class="mb-2">Klik <strong>Step 3</strong> untuk generate jurnal pembayaran dari order yang sudah paid</li>
                        <li>Setelah selesai, <strong class="text-danger">HAPUS file ini!</strong></li>
                    </ol>
                </div>
                
                <!-- Back Link -->
                <div class="text-center mt-4">
                    <a href="<?= url('/dashboard') ?>" class="btn btn-outline-light">
                        <i class="fas fa-arrow-left me-2"></i>Kembali ke Dashboard
                    </a>
                </div>
                
            </div>
        </div>
    </div>
</body>
</html>
