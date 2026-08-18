<?php
/**
 * DATABASE FIX SCRIPT
 * Fixes column naming mismatches between code and database
 */
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<!DOCTYPE html><html><head><title>Database Fix</title>";
echo "<style>
body{font-family:sans-serif;padding:30px;max-width:900px;margin:0 auto;background:#f5f5f5;}
.card{background:white;padding:25px;margin:15px 0;border-radius:10px;box-shadow:0 2px 8px rgba(0,0,0,0.1);}
.ok{color:#10b981;background:#d1fae5;padding:10px;border-radius:5px;margin:10px 0;}
.err{color:#ef4444;background:#fee2e2;padding:10px;border-radius:5px;margin:10px 0;}
.warn{color:#f59e0b;background:#fef3c7;padding:10px;border-radius:5px;margin:10px 0;}
.btn{display:inline-block;padding:12px 24px;background:#c89b2c;color:white;text-decoration:none;border-radius:8px;border:none;cursor:pointer;font-size:16px;margin:5px;}
.btn:hover{background:#a67f20;}
.btn-danger{background:#ef4444;} .btn-danger:hover{background:#dc2626;}
.btn-success{background:#10b981;} .btn-success:hover{background:#059669;}
pre{background:#1e293b;color:#e2e8f0;padding:15px;border-radius:8px;overflow:auto;font-size:13px;}
h1{color:#6b5216;} h2{color:#374151;margin-top:30px;}
</style></head><body>";

echo "<h1>🔧 Database Fix Tool</h1>";

define('BASE_PATH', __DIR__);

// Load config
try {
    require_once BASE_PATH . '/config/constants.php';
    require_once BASE_PATH . '/config/database.php';
    $db = Database::getInstance();
    echo "<div class='ok'>✅ Database connected</div>";
} catch (Throwable $e) {
    echo "<div class='err'>❌ Database connection failed: " . $e->getMessage() . "</div>";
    exit;
}

// Check what needs to be fixed
echo "<div class='card'><h2>📋 Current Database State</h2>";

// Check bank_cash columns
echo "<h3>Table: bank_cash</h3>";
try {
    $columns = $db->fetchAll("SHOW COLUMNS FROM bank_cash");
    $columnNames = array_column($columns, 'Field');
    
    echo "<p>Current columns: <code>" . implode(', ', $columnNames) . "</code></p>";
    
    $issues = [];
    
    // Check for column naming issues
    if (in_array('account_no', $columnNames) && !in_array('account_number', $columnNames)) {
        $issues[] = "account_no → account_number";
    }
    if (in_array('account_name', $columnNames) && !in_array('account_holder', $columnNames)) {
        $issues[] = "account_name → account_holder";
    }
    if (!in_array('initial_balance', $columnNames)) {
        $issues[] = "Missing: initial_balance";
    }
    if (!in_array('current_balance', $columnNames)) {
        $issues[] = "Missing: current_balance";
    }
    
    if (empty($issues)) {
        echo "<div class='ok'>✅ bank_cash table looks correct</div>";
    } else {
        echo "<div class='warn'>⚠️ Issues found: " . implode(', ', $issues) . "</div>";
    }
} catch (Throwable $e) {
    echo "<div class='err'>❌ Error: " . $e->getMessage() . "</div>";
}

// Check journals columns
echo "<h3>Table: journals</h3>";
try {
    $columns = $db->fetchAll("SHOW COLUMNS FROM journals");
    $columnNames = array_column($columns, 'Field');
    
    echo "<p>Current columns: <code>" . implode(', ', $columnNames) . "</code></p>";
    
    $issues = [];
    if (in_array('journal_no', $columnNames) && !in_array('journal_number', $columnNames)) {
        $issues[] = "journal_no → journal_number";
    }
    if (in_array('is_posted', $columnNames) && !in_array('status', $columnNames)) {
        $issues[] = "is_posted → status (need ENUM)";
    }
    if (!in_array('total_amount', $columnNames)) {
        $issues[] = "Missing: total_amount";
    }
    
    if (empty($issues)) {
        echo "<div class='ok'>✅ journals table looks correct</div>";
    } else {
        echo "<div class='warn'>⚠️ Issues found: " . implode(', ', $issues) . "</div>";
    }
} catch (Throwable $e) {
    echo "<div class='err'>❌ Error: " . $e->getMessage() . "</div>";
}

echo "</div>";

// FIX BUTTON
if (isset($_POST['fix_database'])) {
    echo "<div class='card'><h2>🔄 Applying Fixes...</h2>";
    
    $fixes = [];
    
    // Fix bank_cash table
    try {
        $columns = $db->fetchAll("SHOW COLUMNS FROM bank_cash");
        $columnNames = array_column($columns, 'Field');
        
        // Rename account_no to account_number
        if (in_array('account_no', $columnNames) && !in_array('account_number', $columnNames)) {
            $db->query("ALTER TABLE bank_cash CHANGE COLUMN `account_no` `account_number` VARCHAR(50) NULL");
            $fixes[] = "✅ Renamed account_no → account_number";
        }
        
        // Rename account_name to account_holder
        if (in_array('account_name', $columnNames) && !in_array('account_holder', $columnNames)) {
            $db->query("ALTER TABLE bank_cash CHANGE COLUMN `account_name` `account_holder` VARCHAR(100) NULL");
            $fixes[] = "✅ Renamed account_name → account_holder";
        }
        
        // Add initial_balance if missing
        if (!in_array('initial_balance', $columnNames)) {
            $db->query("ALTER TABLE bank_cash ADD COLUMN `initial_balance` DECIMAL(15,2) NOT NULL DEFAULT 0 AFTER `account_holder`");
            $fixes[] = "✅ Added initial_balance column";
        }
        
        // Add current_balance if missing
        if (!in_array('current_balance', $columnNames)) {
            $db->query("ALTER TABLE bank_cash ADD COLUMN `current_balance` DECIMAL(15,2) NOT NULL DEFAULT 0 AFTER `initial_balance`");
            $fixes[] = "✅ Added current_balance column";
        }
        
    } catch (Throwable $e) {
        $fixes[] = "❌ bank_cash fix failed: " . $e->getMessage();
    }
    
    // Fix journals table
    try {
        $columns = $db->fetchAll("SHOW COLUMNS FROM journals");
        $columnNames = array_column($columns, 'Field');
        
        // Rename journal_no to journal_number
        if (in_array('journal_no', $columnNames) && !in_array('journal_number', $columnNames)) {
            $db->query("ALTER TABLE journals CHANGE COLUMN `journal_no` `journal_number` VARCHAR(50) NOT NULL");
            $fixes[] = "✅ Renamed journal_no → journal_number";
        }
        
        // Add status column if only is_posted exists
        if (in_array('is_posted', $columnNames) && !in_array('status', $columnNames)) {
            $db->query("ALTER TABLE journals ADD COLUMN `status` ENUM('draft', 'posted', 'void') NOT NULL DEFAULT 'draft' AFTER `description`");
            // Migrate data
            $db->query("UPDATE journals SET status = 'posted' WHERE is_posted = 1");
            $fixes[] = "✅ Added status column and migrated from is_posted";
        }
        
        // Add total_amount if missing
        if (!in_array('total_amount', $columnNames)) {
            $db->query("ALTER TABLE journals ADD COLUMN `total_amount` DECIMAL(15,2) NOT NULL DEFAULT 0 AFTER `status`");
            // Calculate from total_debit
            $db->query("UPDATE journals SET total_amount = total_debit");
            $fixes[] = "✅ Added total_amount column";
        }
        
    } catch (Throwable $e) {
        $fixes[] = "❌ journals fix failed: " . $e->getMessage();
    }
    
    // Output results
    foreach ($fixes as $fix) {
        if (strpos($fix, '✅') !== false) {
            echo "<div class='ok'>{$fix}</div>";
        } else {
            echo "<div class='err'>{$fix}</div>";
        }
    }
    
    if (empty(array_filter($fixes, fn($f) => strpos($f, '❌') !== false))) {
        echo "<div class='ok' style='font-size:18px;margin-top:20px;'>🎉 All fixes applied successfully!</div>";
    }
    
    echo "<p><a href='fix-database.php' class='btn'>Refresh Status</a> <a href='/dashboard' class='btn btn-success'>Go to Dashboard</a></p>";
    echo "</div>";
}

// Show fix button
echo "<div class='card'><h2>🛠️ Apply Fixes</h2>";
echo "<p>Click the button below to automatically fix database column names:</p>";
echo "<form method='POST'>";
echo "<button type='submit' name='fix_database' class='btn btn-success' onclick='return confirm(\"Apply database fixes?\")'>Apply Database Fixes</button>";
echo "</form>";
echo "</div>";

// SQL Preview
echo "<div class='card'><h2>📝 Manual SQL (if needed)</h2>";
echo "<p>If automatic fix fails, run these SQL commands manually:</p>";
echo "<pre>";
echo "-- Fix bank_cash table\n";
echo "ALTER TABLE bank_cash CHANGE COLUMN `account_no` `account_number` VARCHAR(50) NULL;\n";
echo "ALTER TABLE bank_cash CHANGE COLUMN `account_name` `account_holder` VARCHAR(100) NULL;\n\n";
echo "-- Fix journals table\n";
echo "ALTER TABLE journals CHANGE COLUMN `journal_no` `journal_number` VARCHAR(50) NOT NULL;\n";
echo "ALTER TABLE journals ADD COLUMN `status` ENUM('draft', 'posted', 'void') NOT NULL DEFAULT 'draft' AFTER `description`;\n";
echo "UPDATE journals SET status = 'posted' WHERE is_posted = 1;\n";
echo "ALTER TABLE journals ADD COLUMN `total_amount` DECIMAL(15,2) NOT NULL DEFAULT 0 AFTER `status`;\n";
echo "UPDATE journals SET total_amount = total_debit;\n";
echo "</pre>";
echo "</div>";

echo "<div class='card'><p class='warn'>⚠️ <strong>Delete this file after fixing!</strong></p></div>";
echo "</body></html>";
?>
