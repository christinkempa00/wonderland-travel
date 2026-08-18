<?php
/**
 * DEBUG & MIGRASI JURNAL HPP
 * File: debug.php
 * 
 * v4: Tambah informasi saldo, total debit/kredit, hutang vendor
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);

date_default_timezone_set('Asia/Jakarta');

echo "<pre style='background:#222;color:#0f0;padding:20px;font-family:monospace;'>";
echo "=== DEBUG HPP MIGRATION v4 ===\n";
echo "Time: " . date('Y-m-d H:i:s') . "\n\n";

// ================================================================
// STEP 1: Load Bootstrap
// ================================================================
echo "[1] Loading Bootstrap...\n";

define('BASE_PATH', __DIR__);

if (!file_exists(BASE_PATH . '/config/constants.php')) {
    die("ERROR: config/constants.php not found!\n");
}
require_once BASE_PATH . '/config/constants.php';
echo "    ✓ constants.php loaded\n";

if (!file_exists(BASE_PATH . '/helpers/functions.php')) {
    die("ERROR: helpers/functions.php not found!\n");
}
require_once BASE_PATH . '/helpers/functions.php';
echo "    ✓ functions.php loaded\n";

if (!file_exists(BASE_PATH . '/config/installed.php')) {
    die("ERROR: Application not installed!\n");
}

if (!file_exists(BASE_PATH . '/config/database.php')) {
    die("ERROR: config/database.php not found!\n");
}
require_once BASE_PATH . '/config/database.php';
echo "    ✓ database.php loaded\n";

if (!file_exists(BASE_PATH . '/config/session.php')) {
    die("ERROR: config/session.php not found!\n");
}
require_once BASE_PATH . '/config/session.php';
echo "    ✓ session.php loaded\n";

// ================================================================
// STEP 2: Start Session
// ================================================================
echo "\n[2] Starting Session...\n";
try {
    Session::start();
    echo "    ✓ Session started\n";
    
    if (!Session::isLoggedIn()) {
        echo "    ✗ NOT LOGGED IN!\n";
        echo "</pre>";
        echo "<meta http-equiv='refresh' content='3;url=" . BASE_URL . "/login'>";
        exit;
    }
    
    $companyId = Session::companyId();
    $userId = Session::userId();
    echo "    ✓ Logged in as User ID: {$userId}, Company ID: {$companyId}\n";
    
} catch (Exception $e) {
    echo "    ✗ Session Error: " . $e->getMessage() . "\n";
    die("</pre>");
}

// ================================================================
// STEP 3: Test Database Connection
// ================================================================
echo "\n[3] Testing Database...\n";
try {
    $test = db()->fetchOne("SELECT 1 as test");
    echo "    ✓ Database connection OK\n";
} catch (Exception $e) {
    echo "    ✗ Database Error: " . $e->getMessage() . "\n";
    die("</pre>");
}

// ================================================================
// STEP 4: Check Required Tables
// ================================================================
echo "\n[4] Checking Tables...\n";

$requiredTables = ['orders', 'order_items', 'journals', 'journal_details', 'accounts'];
foreach ($requiredTables as $table) {
    try {
        $check = db()->fetchOne("SHOW TABLES LIKE '{$table}'");
        if ($check) {
            echo "    ✓ Table '{$table}' exists\n";
        } else {
            echo "    ✗ Table '{$table}' NOT FOUND!\n";
        }
    } catch (Exception $e) {
        echo "    ✗ Error: " . $e->getMessage() . "\n";
    }
}

// ================================================================
// STEP 5: Check journals table structure
// ================================================================
echo "\n[5] Checking journals table structure...\n";
try {
    $columns = db()->fetchAll("SHOW COLUMNS FROM journals");
    $colNames = array_column($columns, 'Field');
    
    $required = ['order_id', 'journal_type', 'journal_date', 'description', 'status'];
    foreach ($required as $col) {
        if (in_array($col, $colNames)) {
            echo "    ✓ Column '{$col}' exists\n";
        } else {
            echo "    ✗ Column '{$col}' NOT FOUND!\n";
        }
    }
} catch (Exception $e) {
    echo "    ✗ Error: " . $e->getMessage() . "\n";
}

// ================================================================
// STEP 6: Check hpp_journal_id Column in orders
// ================================================================
echo "\n[6] Checking orders.hpp_journal_id column...\n";
$hasHppColumn = false;
try {
    $columns = db()->fetchOne("SHOW COLUMNS FROM orders LIKE 'hpp_journal_id'");
    if ($columns) {
        $hasHppColumn = true;
        echo "    ✓ Column hpp_journal_id exists\n";
    } else {
        echo "    ✗ Column hpp_journal_id NOT FOUND\n";
        echo "      → Creating column...\n";
        try {
            db()->query("ALTER TABLE orders ADD COLUMN hpp_journal_id INT UNSIGNED NULL");
            echo "      ✓ Column created!\n";
            $hasHppColumn = true;
        } catch (Exception $e) {
            echo "      ✗ Failed: " . $e->getMessage() . "\n";
        }
    }
} catch (Exception $e) {
    echo "    ✗ Error: " . $e->getMessage() . "\n";
}

// ================================================================
// STEP 7: Check Required Accounts
// ================================================================
echo "\n[7] Checking Required Accounts...\n";

// HPP Account (Beban)
$hppAccount = null;
try {
    $hppAccount = db()->fetchOne("
        SELECT id, code, name, balance FROM accounts 
        WHERE company_id = ? AND type = 'expense' AND is_active = 1 
        AND (code LIKE '5.1%' OR code LIKE '51%' OR name LIKE '%HPP%' OR name LIKE '%Harga Pokok%' OR name LIKE '%Pembelian%' OR name LIKE '%COGS%')
        ORDER BY code LIMIT 1
    ", [$companyId]);
    
    if ($hppAccount) {
        echo "    ✓ HPP Account: [{$hppAccount['code']}] {$hppAccount['name']} (ID: {$hppAccount['id']})\n";
        echo "      Balance: Rp " . number_format($hppAccount['balance'], 0, ',', '.') . "\n";
    } else {
        echo "    ✗ HPP Account NOT FOUND!\n";
    }
} catch (Exception $e) {
    echo "    ✗ Error: " . $e->getMessage() . "\n";
}

// Payable Account (Hutang)
$payableAccount = null;
try {
    $payableAccount = db()->fetchOne("
        SELECT id, code, name, balance FROM accounts 
        WHERE company_id = ? AND type = 'liability' AND is_active = 1 
        AND (code LIKE '2.1%' OR code LIKE '21%' OR name LIKE '%Hutang%' OR name LIKE '%Payable%')
        ORDER BY code LIMIT 1
    ", [$companyId]);
    
    if ($payableAccount) {
        echo "    ✓ Payable Account: [{$payableAccount['code']}] {$payableAccount['name']} (ID: {$payableAccount['id']})\n";
        echo "      Balance: Rp " . number_format($payableAccount['balance'], 0, ',', '.') . "\n";
    } else {
        echo "    ✗ Payable Account NOT FOUND!\n";
    }
} catch (Exception $e) {
    echo "    ✗ Error: " . $e->getMessage() . "\n";
}

// ================================================================
// STEP 8: Saldo Kas/Bank & Total Debit/Kredit
// ================================================================
echo "\n[8] Saldo Kas/Bank & Total Debit/Kredit...\n";
try {
    // Saldo Kas/Bank (akun asset tipe kas)
    $kasAccounts = db()->fetchAll("
        SELECT code, name, balance FROM accounts 
        WHERE company_id = ? AND type = 'asset' AND is_active = 1 
        AND (code LIKE '1.1%' OR code LIKE '11%' OR name LIKE '%Kas%' OR name LIKE '%Bank%' OR name LIKE '%Cash%')
        ORDER BY code
    ", [$companyId]);
    
    $totalKas = 0;
    if (!empty($kasAccounts)) {
        echo "    Akun Kas/Bank:\n";
        foreach ($kasAccounts as $kas) {
            echo "      - [{$kas['code']}] {$kas['name']}: Rp " . number_format($kas['balance'], 0, ',', '.') . "\n";
            $totalKas += (float)$kas['balance'];
        }
        echo "    ─────────────────────────────────────\n";
        echo "    TOTAL SALDO KAS/BANK: Rp " . number_format($totalKas, 0, ',', '.') . "\n";
    } else {
        echo "    ✗ Tidak ada akun Kas/Bank\n";
    }
    
    // Total Debit & Kredit dari semua jurnal posted
    $totals = db()->fetchOne("
        SELECT 
            COALESCE(SUM(jd.debit), 0) as total_debit,
            COALESCE(SUM(jd.credit), 0) as total_credit
        FROM journal_details jd
        JOIN journals j ON j.id = jd.journal_id
        WHERE j.company_id = ? AND j.status = 'posted'
    ", [$companyId]);
    
    echo "\n    Total dari Jurnal Posted:\n";
    echo "      Total Debit : Rp " . number_format($totals['total_debit'] ?? 0, 0, ',', '.') . "\n";
    echo "      Total Kredit: Rp " . number_format($totals['total_credit'] ?? 0, 0, ',', '.') . "\n";
    
} catch (Exception $e) {
    echo "    ✗ Error: " . $e->getMessage() . "\n";
}

// ================================================================
// STEP 9: Hutang Vendor (dari Order)
// ================================================================
echo "\n[9] Hutang Vendor (dari Order)...\n";
try {
    // Hutang vendor = total_base_price - expense_paid_amount
    $hutangVendor = db()->fetchOne("
        SELECT 
            COUNT(*) as jumlah_order,
            COALESCE(SUM(total_base_price), 0) as total_modal,
            COALESCE(SUM(expense_paid_amount), 0) as sudah_dibayar,
            COALESCE(SUM(total_base_price), 0) - COALESCE(SUM(expense_paid_amount), 0) as sisa_hutang
        FROM orders 
        WHERE company_id = ? 
        AND (deleted_at IS NULL OR deleted_at = '0000-00-00 00:00:00')
        AND COALESCE(total_base_price, 0) > 0
    ", [$companyId]);
    
    echo "    Jumlah Order dengan Modal: " . ($hutangVendor['jumlah_order'] ?? 0) . "\n";
    echo "    Total Modal Vendor       : Rp " . number_format($hutangVendor['total_modal'] ?? 0, 0, ',', '.') . "\n";
    echo "    Sudah Dibayar ke Vendor  : Rp " . number_format($hutangVendor['sudah_dibayar'] ?? 0, 0, ',', '.') . "\n";
    echo "    ─────────────────────────────────────\n";
    echo "    SISA HUTANG VENDOR       : Rp " . number_format($hutangVendor['sisa_hutang'] ?? 0, 0, ',', '.') . "\n";
    
    // Detail per kategori
    $hutangPerKategori = db()->fetchAll("
        SELECT 
            COALESCE(oi.item_type, 'other') as kategori,
            COUNT(DISTINCT o.id) as jumlah_order,
            SUM(COALESCE(oi.base_price, 0) * COALESCE(oi.quantity, 1) * COALESCE(oi.num_days, 1)) as total_modal,
            SUM(
                CASE WHEN oi.expense_status = 'paid' 
                THEN COALESCE(oi.base_price, 0) * COALESCE(oi.quantity, 1) * COALESCE(oi.num_days, 1) 
                ELSE 0 END
            ) as sudah_dibayar
        FROM orders o
        JOIN order_items oi ON oi.order_id = o.id
        WHERE o.company_id = ?
        AND (o.deleted_at IS NULL OR o.deleted_at = '0000-00-00 00:00:00')
        GROUP BY COALESCE(oi.item_type, 'other')
        HAVING total_modal > 0
        ORDER BY total_modal DESC
    ", [$companyId]);
    
    if (!empty($hutangPerKategori)) {
        echo "\n    Detail per Kategori:\n";
        $categoryLabels = [
            'flight' => 'Tiket Pesawat',
            'hotel' => 'Hotel',
            'rental' => 'Rental Kendaraan',
            'vehicle' => 'Kendaraan',
            'tour' => 'Paket Tour',
            'other' => 'Lainnya'
        ];
        foreach ($hutangPerKategori as $cat) {
            $sisa = (float)$cat['total_modal'] - (float)$cat['sudah_dibayar'];
            $label = $categoryLabels[$cat['kategori']] ?? ucfirst($cat['kategori']);
            echo "      - {$label}: Rp " . number_format($sisa, 0, ',', '.') . " (dari " . number_format($cat['total_modal'], 0, ',', '.') . ")\n";
        }
    }
    
} catch (Exception $e) {
    echo "    ✗ Error: " . $e->getMessage() . "\n";
}

// ================================================================
// STEP 10: Count Orders
// ================================================================
echo "\n[10] Counting Orders...\n";
try {
    $stats = db()->fetchOne("
        SELECT 
            COUNT(*) as total,
            SUM(CASE WHEN COALESCE(total_base_price, 0) > 0 THEN 1 ELSE 0 END) as with_modal
        FROM orders 
        WHERE company_id = ?
        AND (deleted_at IS NULL OR deleted_at = '0000-00-00 00:00:00')
    ", [$companyId]);
    
    echo "    Total Orders: " . ($stats['total'] ?? 0) . "\n";
    echo "    Orders with Modal: " . ($stats['with_modal'] ?? 0) . "\n";
    
    // Check existing HPP journals
    $existingJournals = db()->fetchOne("
        SELECT COUNT(*) as cnt FROM journals 
        WHERE company_id = ? AND journal_type = 'expense' AND description LIKE '%HPP Order%' AND status = 'posted'
    ", [$companyId]);
    echo "    Existing HPP Journals: " . ($existingJournals['cnt'] ?? 0) . "\n";
    
} catch (Exception $e) {
    echo "    ✗ Error: " . $e->getMessage() . "\n";
}

// ================================================================
// STEP 11: Run Migration (if requested)
// ================================================================
if (isset($_GET['run']) && $_GET['run'] == '1') {
    echo "\n[11] RUNNING MIGRATION...\n";
    echo "=".str_repeat("=", 50)."\n";
    
    if (!$hppAccount || !$payableAccount) {
        echo "    ✗ ABORTED: Required accounts not found!\n";
        echo "      Please create HPP and Hutang accounts first.\n";
    } else {
        $startDate = !empty($_GET['start_date']) ? $_GET['start_date'] : null;
        
        $where = "company_id = ? AND COALESCE(total_base_price, 0) > 0 AND (deleted_at IS NULL OR deleted_at = '0000-00-00 00:00:00')";
        $params = [$companyId];
        
        if ($startDate) {
            $where .= " AND order_date >= ?";
            $params[] = $startDate;
            echo "    Filter: Orders from {$startDate}\n";
        }
        
        try {
            $orders = db()->fetchAll("
                SELECT id, order_number, order_date, event_name, total_base_price 
                FROM orders 
                WHERE {$where}
                ORDER BY order_date
            ", $params);
            
            echo "    Found " . count($orders) . " orders to process\n\n";
            
            $success = 0;
            $skipped = 0;
            $failed = 0;
            $errors = [];
            
            foreach ($orders as $order) {
                $orderId = (int)$order['id'];
                $orderNumber = $order['order_number'];
                $hppAmount = (float)$order['total_base_price'];
                
                echo "    Processing: {$orderNumber} (Rp " . number_format($hppAmount, 0, ',', '.') . ")... ";
                
                // Check if already has HPP journal
                $existing = db()->fetchOne("
                    SELECT id FROM journals 
                    WHERE company_id = ? AND order_id = ? AND journal_type = 'expense' AND description LIKE '%HPP Order%' AND status = 'posted'
                ", [$companyId, $orderId]);
                
                if ($existing) {
                    echo "SKIPPED (already exists)\n";
                    $skipped++;
                    continue;
                }
                
                // Generate journal number
                $year = date('y');
                $month = date('m');
                $last = db()->fetchOne("
                    SELECT journal_number FROM journals 
                    WHERE company_id = ? AND journal_number LIKE ?
                    ORDER BY id DESC LIMIT 1
                ", [$companyId, "HPP/{$year}{$month}/%"]);
                
                if ($last) {
                    $parts = explode('/', $last['journal_number']);
                    $num = (int)end($parts) + 1;
                } else {
                    $num = 1;
                }
                $journalNumber = "HPP/{$year}{$month}/" . str_pad($num, 4, '0', STR_PAD_LEFT);
                
                $description = "HPP Order {$orderNumber}";
                if (!empty($order['event_name'])) {
                    $description .= " - " . substr($order['event_name'], 0, 50);
                }
                
                try {
                    // Create journal header
                    $journalId = db()->insert('journals', [
                        'company_id' => $companyId,
                        'order_id' => $orderId,
                        'journal_number' => $journalNumber,
                        'journal_type' => 'expense',
                        'journal_date' => $order['order_date'],
                        'description' => $description,
                        'reference' => 'HPP-' . $orderId,
                        'status' => 'posted',
                        'posted_at' => date('Y-m-d H:i:s'),
                        'posted_by' => $userId,
                        'created_by' => $userId,
                        'created_at' => date('Y-m-d H:i:s')
                    ]);
                    
                    if (!$journalId) {
                        throw new Exception("Failed to insert journal");
                    }
                    
                    // Create journal details
                    // Debit HPP (Beban bertambah)
                    db()->insert('journal_details', [
                        'journal_id' => $journalId,
                        'account_id' => $hppAccount['id'],
                        'description' => $description,
                        'debit' => $hppAmount,
                        'credit' => 0,
                        'created_at' => date('Y-m-d H:i:s')
                    ]);
                    
                    // Credit Hutang (Hutang bertambah)
                    db()->insert('journal_details', [
                        'journal_id' => $journalId,
                        'account_id' => $payableAccount['id'],
                        'description' => $description,
                        'debit' => 0,
                        'credit' => $hppAmount,
                        'created_at' => date('Y-m-d H:i:s')
                    ]);
                    
                    // Update account balances
                    db()->query("UPDATE accounts SET balance = balance + ? WHERE id = ?", [$hppAmount, $hppAccount['id']]);
                    db()->query("UPDATE accounts SET balance = balance + ? WHERE id = ?", [$hppAmount, $payableAccount['id']]);
                    
                    // Link to order
                    if ($hasHppColumn) {
                        db()->update('orders', ['hpp_journal_id' => $journalId], 'id = ?', [$orderId]);
                    }
                    
                    echo "OK ({$journalNumber})\n";
                    $success++;
                    
                } catch (Exception $e) {
                    echo "FAILED: " . $e->getMessage() . "\n";
                    $failed++;
                    $errors[] = "{$orderNumber}: " . $e->getMessage();
                }
            }
            
            echo "\n" . str_repeat("=", 50) . "\n";
            echo "MIGRATION COMPLETE!\n";
            echo "  Success: {$success}\n";
            echo "  Skipped: {$skipped}\n";
            echo "  Failed:  {$failed}\n";
            
            if (!empty($errors)) {
                echo "\nErrors:\n";
                foreach (array_slice($errors, 0, 10) as $err) {
                    echo "  - {$err}\n";
                }
                if (count($errors) > 10) {
                    echo "  ... and " . (count($errors) - 10) . " more\n";
                }
            }
            
        } catch (Exception $e) {
            echo "    ✗ Migration Error: " . $e->getMessage() . "\n";
        }
    }
} else {
    echo "\n[11] Migration not requested.\n";
    echo "     To run migration, add ?run=1 to URL\n";
    echo "     Optional: ?run=1&start_date=2024-01-01\n";
}

echo "\n=== DEBUG COMPLETE ===\n";
echo "</pre>";

echo "<p style='margin:20px;'>";
echo "<a href='?run=1' style='padding:10px 20px;background:#28a745;color:white;text-decoration:none;border-radius:5px;margin-right:10px;'>Run Migration</a>";
echo "<a href='" . BASE_URL . "/accounting/reports/cash-flow' style='padding:10px 20px;background:#007bff;color:white;text-decoration:none;border-radius:5px;margin-right:10px;'>Cash Flow Report</a>";
echo "<a href='" . BASE_URL . "/dashboard' style='padding:10px 20px;background:#6c757d;color:white;text-decoration:none;border-radius:5px;'>Dashboard</a>";
echo "</p>";