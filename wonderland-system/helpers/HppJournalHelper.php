<?php
/**
 * ================================================================
 * HPP Journal Helper
 * ================================================================
 * File: helpers/HppJournalHelper.php
 * 
 * Helper untuk membuat jurnal HPP (Harga Pokok Penjualan) otomatis
 * saat order dibuat. HPP akan dicatat sebagai hutang vendor.
 * 
 * CARA PENGGUNAAN DI OrderController:
 * ===================================
 * 
 * 1. Di method store(), setelah order dibuat:
 *    require_once HELPERS_PATH . '/HppJournalHelper.php';
 *    HppJournalHelper::createHppJournal($orderId, $companyId, $totalBasePrice, $orderNumber, $eventName, $orderDate);
 * 
 * 2. Di method update(), setelah order diupdate:
 *    require_once HELPERS_PATH . '/HppJournalHelper.php';
 *    HppJournalHelper::recalculateHpp($orderId, $companyId);
 * 
 * 3. Di method delete(), sebelum order dihapus:
 *    require_once HELPERS_PATH . '/HppJournalHelper.php';
 *    HppJournalHelper::voidHppJournal($orderId, $companyId);
 * 
 * JURNAL YANG DIBUAT:
 * ===================
 * Debit  : HPP / Beban Pokok Penjualan (5.1.x)
 * Credit : Hutang Vendor / Accounts Payable (2.1.x)
 */

class HppJournalHelper {
    
    /**
     * Buat jurnal HPP saat order dibuat/diupdate
     * 
     * @param int $orderId
     * @param int $companyId
     * @param float $hppAmount Modal vendor (total_base_price)
     * @param string $orderNumber
     * @param string $eventName
     * @param string $orderDate
     * @return array ['success' => bool, 'message' => string, 'journal_id' => int|null]
     */
    public static function createHppJournal(
        int $orderId, 
        int $companyId, 
        float $hppAmount, 
        string $orderNumber,
        string $eventName = '',
        string $orderDate = ''
    ): array {
        
        if ($hppAmount <= 0) {
            return ['success' => true, 'message' => 'No HPP to record (amount is 0)', 'journal_id' => null];
        }
        
        try {
            // Cek apakah sudah ada jurnal HPP untuk order ini
            $existingJournal = db()->fetchOne(
                "SELECT id, total_debit FROM journals WHERE company_id = ? AND reference_type = 'hpp' AND reference_id = ? AND status = 'posted'",
                [$companyId, $orderId]
            );
            
            if ($existingJournal) {
                // Cek apakah amount berubah
                if (abs((float)$existingJournal['total_debit'] - $hppAmount) < 0.01) {
                    return ['success' => true, 'message' => 'Jurnal HPP sudah ada dan tidak berubah', 'journal_id' => (int)$existingJournal['id']];
                }
                // Update jurnal yang sudah ada
                return self::updateHppJournal((int)$existingJournal['id'], $hppAmount, $companyId);
            }
            
            // Get HPP account (Beban HPP / Cost of Goods Sold)
            $hppAccount = self::getHppAccount($companyId);
            if (!$hppAccount) {
                return ['success' => false, 'message' => 'Akun HPP tidak ditemukan. Buat akun expense dengan kode 5.1.x atau nama mengandung "HPP"', 'journal_id' => null];
            }
            
            // Get Hutang Vendor account (Accounts Payable)
            $payableAccount = self::getPayableAccount($companyId);
            if (!$payableAccount) {
                return ['success' => false, 'message' => 'Akun Hutang Vendor tidak ditemukan. Buat akun liability dengan kode 2.1.x atau nama mengandung "Hutang"', 'journal_id' => null];
            }
            
            // Generate journal number
            $journalNumber = self::generateJournalNumber($companyId, 'HPP');
            
            // Prepare description
            $description = "HPP Order {$orderNumber}";
            if ($eventName) {
                $description .= " - " . substr($eventName, 0, 50);
            }
            
            // Create journal header
            $journalId = db()->insert('journals', [
                'company_id' => $companyId,
                'journal_number' => $journalNumber,
                'journal_date' => $orderDate ?: date('Y-m-d'),
                'description' => $description,
                'reference_type' => 'hpp',
                'reference_id' => $orderId,
                'total_debit' => $hppAmount,
                'total_credit' => $hppAmount,
                'status' => 'posted',
                'created_by' => Session::userId() ?? 1,
                'created_at' => date('Y-m-d H:i:s')
            ]);
            
            if (!$journalId) {
                return ['success' => false, 'message' => 'Gagal membuat jurnal', 'journal_id' => null];
            }
            
            // Create journal entries
            // 1. Debit HPP (Beban bertambah)
            db()->insert('journal_entries', [
                'journal_id' => $journalId,
                'account_id' => $hppAccount['id'],
                'description' => $description,
                'debit' => $hppAmount,
                'credit' => 0,
                'created_at' => date('Y-m-d H:i:s')
            ]);
            
            // 2. Credit Hutang Vendor (Hutang bertambah)
            db()->insert('journal_entries', [
                'journal_id' => $journalId,
                'account_id' => $payableAccount['id'],
                'description' => $description,
                'debit' => 0,
                'credit' => $hppAmount,
                'created_at' => date('Y-m-d H:i:s')
            ]);
            
            // Update account balances
            // HPP (expense) - normal balance debit, so debit increases
            db()->query("UPDATE accounts SET balance = balance + ? WHERE id = ?", [$hppAmount, $hppAccount['id']]);
            // Hutang (liability) - normal balance credit, so credit increases
            db()->query("UPDATE accounts SET balance = balance + ? WHERE id = ?", [$hppAmount, $payableAccount['id']]);
            
            // Link journal to order (jika kolom ada)
            try {
                db()->update('orders', ['hpp_journal_id' => $journalId], 'id = ?', [$orderId]);
            } catch (Exception $e) {
                // Kolom mungkin belum ada, abaikan
            }
            
            return [
                'success' => true, 
                'message' => "Jurnal HPP {$journalNumber} berhasil dibuat", 
                'journal_id' => $journalId
            ];
            
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Error: ' . $e->getMessage(), 'journal_id' => null];
        }
    }
    
    /**
     * Update jurnal HPP yang sudah ada (jika order diedit)
     */
    private static function updateHppJournal(int $journalId, float $newAmount, int $companyId): array {
        try {
            // Get current journal
            $journal = db()->fetchOne("SELECT * FROM journals WHERE id = ?", [$journalId]);
            if (!$journal) {
                return ['success' => false, 'message' => 'Jurnal tidak ditemukan', 'journal_id' => null];
            }
            
            $oldAmount = (float)$journal['total_debit'];
            $difference = $newAmount - $oldAmount;
            
            if (abs($difference) < 0.01) {
                return ['success' => true, 'message' => 'Tidak ada perubahan', 'journal_id' => $journalId];
            }
            
            // Update journal header
            db()->update('journals', [
                'total_debit' => $newAmount,
                'total_credit' => $newAmount,
                'updated_at' => date('Y-m-d H:i:s')
            ], 'id = ?', [$journalId]);
            
            // Get entries and update
            $entries = db()->fetchAll("SELECT * FROM journal_entries WHERE journal_id = ?", [$journalId]);
            
            foreach ($entries as $entry) {
                if ($entry['debit'] > 0) {
                    // HPP account - update debit
                    db()->update('journal_entries', ['debit' => $newAmount], 'id = ?', [$entry['id']]);
                    // Update account balance (add difference)
                    db()->query("UPDATE accounts SET balance = balance + ? WHERE id = ?", [$difference, $entry['account_id']]);
                } else {
                    // Payable account - update credit
                    db()->update('journal_entries', ['credit' => $newAmount], 'id = ?', [$entry['id']]);
                    // Update account balance (add difference for liability)
                    db()->query("UPDATE accounts SET balance = balance + ? WHERE id = ?", [$difference, $entry['account_id']]);
                }
            }
            
            return ['success' => true, 'message' => 'Jurnal HPP diperbarui', 'journal_id' => $journalId];
            
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Error: ' . $e->getMessage(), 'journal_id' => null];
        }
    }
    
    /**
     * Void jurnal HPP (jika order dibatalkan/dihapus)
     */
    public static function voidHppJournal(int $orderId, int $companyId): array {
        try {
            $journal = db()->fetchOne(
                "SELECT * FROM journals WHERE company_id = ? AND reference_type = 'hpp' AND reference_id = ? AND status = 'posted'",
                [$companyId, $orderId]
            );
            
            if (!$journal) {
                return ['success' => true, 'message' => 'Tidak ada jurnal HPP untuk order ini'];
            }
            
            $amount = (float)$journal['total_debit'];
            
            // Get entries to reverse balances
            $entries = db()->fetchAll("SELECT * FROM journal_entries WHERE journal_id = ?", [$journal['id']]);
            
            foreach ($entries as $entry) {
                if ($entry['debit'] > 0) {
                    // HPP account - reverse debit (decrease)
                    db()->query("UPDATE accounts SET balance = balance - ? WHERE id = ?", [$amount, $entry['account_id']]);
                } else {
                    // Payable account - reverse credit (decrease)
                    db()->query("UPDATE accounts SET balance = balance - ? WHERE id = ?", [$amount, $entry['account_id']]);
                }
            }
            
            // Void the journal
            db()->update('journals', [
                'status' => 'void',
                'voided_at' => date('Y-m-d H:i:s'),
                'voided_by' => Session::userId() ?? 1
            ], 'id = ?', [$journal['id']]);
            
            // Remove link from order
            try {
                db()->update('orders', ['hpp_journal_id' => null], 'id = ?', [$orderId]);
            } catch (Exception $e) {
                // Ignore if column doesn't exist
            }
            
            return ['success' => true, 'message' => 'Jurnal HPP dibatalkan'];
            
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Error: ' . $e->getMessage()];
        }
    }
    
    /**
     * Recalculate HPP for an order (useful after order items changed)
     */
    public static function recalculateHpp(int $orderId, int $companyId): array {
        // Get fresh total_base_price from order
        $order = db()->fetchOne(
            "SELECT order_number, order_date, event_name, total_base_price FROM orders WHERE id = ? AND company_id = ?",
            [$orderId, $companyId]
        );
        
        if (!$order) {
            return ['success' => false, 'message' => 'Order tidak ditemukan'];
        }
        
        $totalBasePrice = (float)$order['total_base_price'];
        
        if ($totalBasePrice <= 0) {
            // Void existing journal if any
            return self::voidHppJournal($orderId, $companyId);
        }
        
        return self::createHppJournal(
            $orderId,
            $companyId,
            $totalBasePrice,
            $order['order_number'],
            $order['event_name'] ?? '',
            $order['order_date']
        );
    }
    
    /**
     * Get HPP Account (Beban Pokok Penjualan)
     */
    private static function getHppAccount(int $companyId): ?array {
        // Coba dari settings dulu
        try {
            $setting = db()->fetchOne(
                "SELECT setting_value FROM accounting_settings WHERE company_id = ? AND setting_key = 'hpp_account'",
                [$companyId]
            );
            if ($setting && $setting['setting_value']) {
                $account = db()->fetchOne(
                    "SELECT id, code, name FROM accounts WHERE id = ? AND is_active = 1",
                    [$setting['setting_value']]
                );
                if ($account) return $account;
            }
        } catch (Exception $e) {
            // Table mungkin tidak ada
        }
        
        // Cari akun HPP berdasarkan kode atau nama
        $account = db()->fetchOne(
            "SELECT id, code, name FROM accounts 
             WHERE company_id = ? AND type = 'expense' AND is_active = 1 
             AND (
                 code LIKE '5.1%' OR 
                 code LIKE '51%' OR
                 name LIKE '%HPP%' OR 
                 name LIKE '%Harga Pokok%' OR 
                 name LIKE '%Cost of%' OR
                 name LIKE '%Beban Pokok%' OR
                 name LIKE '%COGS%'
             )
             ORDER BY 
                CASE 
                    WHEN code LIKE '5.1.1%' OR code LIKE '511%' THEN 1
                    WHEN code LIKE '5.1%' OR code LIKE '51%' THEN 2
                    ELSE 3
                END,
                code
             LIMIT 1",
            [$companyId]
        );
        
        return $account ?: null;
    }
    
    /**
     * Get Payable Account (Hutang Vendor)
     */
    private static function getPayableAccount(int $companyId): ?array {
        // Coba dari settings dulu
        try {
            $setting = db()->fetchOne(
                "SELECT setting_value FROM accounting_settings WHERE company_id = ? AND setting_key = 'vendor_payable_account'",
                [$companyId]
            );
            if ($setting && $setting['setting_value']) {
                $account = db()->fetchOne(
                    "SELECT id, code, name FROM accounts WHERE id = ? AND is_active = 1",
                    [$setting['setting_value']]
                );
                if ($account) return $account;
            }
        } catch (Exception $e) {
            // Table mungkin tidak ada
        }
        
        // Cari akun Hutang berdasarkan kode atau nama
        $account = db()->fetchOne(
            "SELECT id, code, name FROM accounts 
             WHERE company_id = ? AND type = 'liability' AND is_active = 1 
             AND (
                 code LIKE '2.1%' OR 
                 code LIKE '21%' OR
                 name LIKE '%Hutang Usaha%' OR
                 name LIKE '%Hutang Vendor%' OR
                 name LIKE '%Hutang Dagang%' OR
                 name LIKE '%Account%Payable%' OR
                 name LIKE '%Payable%' OR
                 name LIKE '%Supplier%'
             )
             ORDER BY 
                CASE 
                    WHEN name LIKE '%Vendor%' THEN 1
                    WHEN name LIKE '%Usaha%' THEN 2
                    WHEN name LIKE '%Dagang%' THEN 3
                    ELSE 4
                END,
                code
             LIMIT 1",
            [$companyId]
        );
        
        return $account ?: null;
    }
    
    /**
     * Generate journal number
     */
    private static function generateJournalNumber(int $companyId, string $prefix = 'HPP'): string {
        $year = date('y');
        $month = date('m');
        
        $last = db()->fetchOne(
            "SELECT journal_number FROM journals 
             WHERE company_id = ? AND journal_number LIKE ?
             ORDER BY id DESC LIMIT 1",
            [$companyId, $prefix . '/' . $year . $month . '/%']
        );
        
        if ($last) {
            $parts = explode('/', $last['journal_number']);
            $num = (int)end($parts) + 1;
        } else {
            $num = 1;
        }
        
        return $prefix . '/' . $year . $month . '/' . str_pad($num, 4, '0', STR_PAD_LEFT);
    }
    
    /**
     * Migrate existing orders to create HPP journals
     */
    public static function migrateExistingOrders(int $companyId, ?string $startDate = null): array {
        $result = ['success' => 0, 'failed' => 0, 'skipped' => 0, 'errors' => []];
        
        $where = "company_id = ? AND COALESCE(total_base_price, 0) > 0";
        $params = [$companyId];
        
        if ($startDate) {
            $where .= " AND order_date >= ?";
            $params[] = $startDate;
        }
        
        // Check if hpp_journal_id column exists
        $hasHppColumn = true;
        try {
            db()->fetchOne("SELECT hpp_journal_id FROM orders LIMIT 1");
        } catch (Exception $e) {
            $hasHppColumn = false;
        }
        
        if ($hasHppColumn) {
            $where .= " AND (hpp_journal_id IS NULL OR hpp_journal_id = 0)";
        }
        
        $orders = db()->fetchAll(
            "SELECT id, order_number, order_date, event_name, total_base_price 
             FROM orders WHERE {$where} ORDER BY order_date",
            $params
        );
        
        foreach ($orders as $order) {
            // Check if HPP journal already exists
            $existing = db()->fetchOne(
                "SELECT id FROM journals WHERE company_id = ? AND reference_type = 'hpp' AND reference_id = ? AND status = 'posted'",
                [$companyId, $order['id']]
            );
            
            if ($existing) {
                $result['skipped']++;
                continue;
            }
            
            $res = self::createHppJournal(
                (int)$order['id'],
                $companyId,
                (float)$order['total_base_price'],
                $order['order_number'],
                $order['event_name'] ?? '',
                $order['order_date']
            );
            
            if ($res['success']) {
                $result['success']++;
            } else {
                $result['failed']++;
                $result['errors'][] = "Order {$order['order_number']}: {$res['message']}";
            }
        }
        
        return $result;
    }
}