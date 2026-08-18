<?php
/**
 * ================================================================
 * TRAVEL MANAGEMENT SYSTEM - Accounting Helper
 * ================================================================
 * 
 * File: /helpers/AccountingHelper.php
 * 
 * FITUR:
 * - Get/Set accounting settings
 * - Multi-akun pendapatan per service_type
 * - Auto-create invoice journal saat order dibuat
 * - Auto-update/adjust journal saat order diedit
 * - Payment journal dengan integrasi kas/bank
 * - Expense/transaction journal
 * - Void journal
 * 
 * CARA PAKAI:
 * Tambahkan di controller yang membutuhkan:
 * require_once HELPERS_PATH . '/AccountingHelper.php';
 */

// Prevent direct access
if (!defined('BASE_PATH')) {
    exit('No direct script access allowed');
}

// ================================================================
// HELPER: Cek apakah sudah dalam DB transaction
// Digunakan agar fungsi tidak double beginTransaction
// ================================================================
function _isInTransaction(): bool {
    try {
        $db = db();
        if (method_exists($db, 'getPdo')) {
            return $db->getPdo()->inTransaction();
        }
        if (method_exists($db, 'inTransaction')) {
            return $db->inTransaction();
        }
        // Fallback: assume not in transaction
        return false;
    } catch (\Throwable $e) {
        return false;
    }
}

// ================================================================
// ACCOUNTING SETTINGS
// ================================================================

/**
 * Get accounting setting (default account)
 * 
 * @param int $companyId
 * @param string $key Setting key (e.g., 'account_receivable', 'revenue_bus')
 * @return int|null Account ID or null if not found
 */
function getAccountingSetting(int $companyId, string $key): ?int {
    try {
        $result = db()->fetchOne(
            "SELECT account_id FROM accounting_settings WHERE company_id = ? AND setting_key = ?",
            [$companyId, $key]
        );
        return $result ? (int)$result['account_id'] : null;
    } catch (Exception $e) {
        return null;
    }
}

/**
 * Set accounting setting
 * 
 * @param int $companyId
 * @param string $key
 * @param int|null $accountId
 * @param string|null $description
 */
function setAccountingSetting(int $companyId, string $key, ?int $accountId, ?string $description = null): void {
    try {
        $existing = db()->fetchOne(
            "SELECT id FROM accounting_settings WHERE company_id = ? AND setting_key = ?",
            [$companyId, $key]
        );
        
        if ($existing) {
            db()->update('accounting_settings', [
                'account_id' => $accountId,
                'description' => $description
            ], 'id = ?', [$existing['id']]);
        } else {
            db()->insert('accounting_settings', [
                'company_id' => $companyId,
                'setting_key' => $key,
                'account_id' => $accountId,
                'description' => $description
            ]);
        }
    } catch (Exception $e) {
        error_log("setAccountingSetting error: " . $e->getMessage());
    }
}

/**
 * Get all accounting settings for a company
 * 
 * @param int $companyId
 * @return array
 */
function getAllAccountingSettings(int $companyId): array {
    try {
        $rows = db()->fetchAll(
            "SELECT s.*, a.code as account_code, a.name as account_name 
             FROM accounting_settings s
             LEFT JOIN accounts a ON s.account_id = a.id
             WHERE s.company_id = ?
             ORDER BY s.setting_key",
            [$companyId]
        );
        
        $result = [];
        foreach ($rows as $row) {
            $result[$row['setting_key']] = [
                'account_id' => $row['account_id'],
                'account_code' => $row['account_code'],
                'account_name' => $row['account_name'],
                'description' => $row['description']
            ];
        }
        return $result;
    } catch (Exception $e) {
        return [];
    }
}

// ================================================================
// REVENUE ACCOUNT MAPPING
// ================================================================

/**
 * Get revenue account based on service type
 * Supports multi-akun pendapatan per jenis layanan
 * 
 * @param int $companyId
 * @param string $serviceType (bus, towing, hotel, travel, flight, rental, restaurant, other)
 * @return int|null Account ID
 */
function getRevenueAccount(int $companyId, string $serviceType): ?int {
    $mapping = [
        'bus' => 'revenue_bus',
        'towing' => 'revenue_towing',
        'hotel' => 'revenue_hotel',
        'travel' => 'revenue_travel',
        'flight' => 'revenue_travel',      // flight uses same as travel (tiket)
        'rental' => 'revenue_rental',
        'restaurant' => 'revenue_default',
        'other' => 'revenue_default'
    ];
    
    $key = $mapping[$serviceType] ?? 'revenue_default';
    $accountId = getAccountingSetting($companyId, $key);
    
    // Fallback to revenue_default if specific not found
    if (!$accountId) {
        $accountId = getAccountingSetting($companyId, 'revenue_default');
    }
    
    // Final fallback to revenue_bus (legacy support)
    if (!$accountId) {
        $accountId = getAccountingSetting($companyId, 'revenue_bus');
    }
    
    return $accountId;
}

/**
 * Get expense account based on category
 * 
 * @param int $companyId
 * @param string $category
 * @return int|null
 */
function getExpenseAccount(int $companyId, string $category = 'default'): ?int {
    $mapping = [
        'ticket' => 'expense_ticket',
        'hotel' => 'expense_hotel',
        'transport' => 'expense_transport',
        'salary' => 'expense_salary',
        'rent' => 'expense_rent',
        'utility' => 'expense_utility',
        'admin' => 'expense_admin',
        'default' => 'expense_default'
    ];
    
    $key = $mapping[$category] ?? 'expense_default';
    $accountId = getAccountingSetting($companyId, $key);
    
    if (!$accountId) {
        $accountId = getAccountingSetting($companyId, 'expense_default');
    }
    
    return $accountId;
}

// ================================================================
// JOURNAL NUMBER GENERATOR
// ================================================================

/**
 * Generate journal number
 * Format: {TYPE}/{YYMM}/{NUMBER}
 * 
 * @param int $companyId
 * @param string $type JU=Jurnal Umum, JKM=Jurnal Kas Masuk, JKK=Jurnal Kas Keluar, JPA=Jurnal Penyesuaian
 * @return string
 */
function generateJournalNumber(int $companyId, string $type = 'JU'): string {
    $year = date('y');
    $month = date('m');
    
    try {
        $lastJournal = db()->fetchOne(
            "SELECT journal_number FROM journals 
             WHERE company_id = ? 
             AND journal_number LIKE ? 
             ORDER BY id DESC LIMIT 1",
            [$companyId, $type . '/' . $year . $month . '/%']
        );
        
        if ($lastJournal) {
            $parts = explode('/', $lastJournal['journal_number']);
            $lastNum = (int)end($parts);
            $newNum = $lastNum + 1;
        } else {
            $newNum = 1;
        }
    } catch (Exception $e) {
        $newNum = 1;
    }
    
    return $type . '/' . $year . $month . '/' . str_pad($newNum, 4, '0', STR_PAD_LEFT);
}

// ================================================================
// ORDER INVOICE JOURNAL
// ================================================================

/**
 * Create journal for order invoice
 * Called when order status changes to 'invoiced' or when order is created
 * 
 * Jurnal:
 *   Debit:  Piutang Usaha      Rp XXX
 *   Credit: Pendapatan Jasa    Rp XXX
 * 
 * @param array $order Order data
 * @return int|null Journal ID or null on failure
 */
function createOrderInvoiceJournal(array $order): ?int {
    $companyId = (int)$order['company_id'];
    $orderId = (int)$order['id'];
    $amount = (float)$order['total_final_price'];
    
    if ($amount <= 0) {
        return null;
    }
    
    // Get accounts
    $receivableAccountId = getAccountingSetting($companyId, 'account_receivable');
    $revenueAccountId = getRevenueAccount($companyId, $order['service_type'] ?? 'bus');
    
    if (!$receivableAccountId || !$revenueAccountId) {
        error_log("Accounting settings not configured for company {$companyId}");
        return null;
    }
    
    // Check if journal already exists
    try {
        $existing = db()->fetchOne(
            "SELECT id FROM journals WHERE order_id = ? AND journal_type = 'order_invoice' AND status = 'posted'",
            [$orderId]
        );
        
        if ($existing) {
            return (int)$existing['id'];
        }
    } catch (Exception $e) {
        // Continue if column doesn't exist
    }
    
    try {
        $journalNumber = generateJournalNumber($companyId, 'JU');
        $userId = Session::userId() ?? 1;
        
        // Create journal header
        $journalId = db()->insert('journals', [
            'company_id' => $companyId,
            'order_id' => $orderId,
            'journal_number' => $journalNumber,
            'journal_type' => 'order_invoice',
            'journal_date' => date('Y-m-d'),
            'description' => 'Invoice Order: ' . ($order['order_number'] ?? '') . ' - ' . ($order['event_name'] ?? 'N/A'),
            'reference' => $order['order_number'] ?? '',
            'status' => 'posted',
            'posted_at' => date('Y-m-d H:i:s'),
            'posted_by' => $userId,
            'created_by' => $userId
        ]);
        
        // Create journal details - Debit: Piutang
        db()->insert('journal_details', [
            'journal_id' => $journalId,
            'account_id' => $receivableAccountId,
            'description' => 'Piutang dari ' . ($order['order_number'] ?? ''),
            'debit' => $amount,
            'credit' => 0
        ]);
        
        // Credit: Pendapatan
        db()->insert('journal_details', [
            'journal_id' => $journalId,
            'account_id' => $revenueAccountId,
            'description' => 'Pendapatan ' . ($order['order_number'] ?? ''),
            'debit' => 0,
            'credit' => $amount
        ]);
        
        // Update order with journal reference
        try {
            db()->update('orders', [
                'invoice_journal_id' => $journalId
            ], 'id = ?', [$orderId]);
        } catch (Exception $e) {
            // Column might not exist
        }
        
        return $journalId;
        
    } catch (Exception $e) {
        error_log("Failed to create invoice journal: " . $e->getMessage());
        return null;
    }
}

/**
 * Update or create invoice journal when order is edited
 * Creates adjustment journal if price changes
 * 
 * @param array $order Order data (must include id, company_id, total_final_price, service_type)
 * @return array Result with success status and message
 */
function updateOrderInvoiceJournal(array $order): array {
    $companyId = (int)$order['company_id'];
    $orderId = (int)$order['id'];
    $newAmount = (float)$order['total_final_price'];
    
    if ($newAmount <= 0) {
        return ['success' => false, 'message' => 'Total harga harus lebih dari 0'];
    }
    
    // Get accounts
    $receivableAccountId = getAccountingSetting($companyId, 'account_receivable');
    $revenueAccountId = getRevenueAccount($companyId, $order['service_type'] ?? 'bus');
    
    if (!$receivableAccountId || !$revenueAccountId) {
        return ['success' => false, 'message' => 'Pengaturan akun akuntansi belum dikonfigurasi'];
    }
    
    try {
        // Check if invoice journal exists for this order
        $existingJournal = db()->fetchOne(
            "SELECT j.*, 
                    (SELECT SUM(debit) FROM journal_details WHERE journal_id = j.id AND debit > 0) as old_amount
             FROM journals j 
             WHERE j.order_id = ? AND j.journal_type = 'order_invoice' AND j.status = 'posted'
             ORDER BY j.id DESC LIMIT 1",
            [$orderId]
        );
        
        if (!$existingJournal) {
            // No existing journal, create new one
            $journalId = createOrderInvoiceJournal($order);
            return [
                'success' => true, 
                'journal_id' => $journalId,
                'action' => 'created',
                'message' => 'Jurnal invoice berhasil dibuat'
            ];
        }
        
        // Calculate total from ALL invoice + adjustment journals for this order
        $totalRecorded = db()->fetchOne(
            "SELECT COALESCE(SUM(jd.debit), 0) - COALESCE(SUM(jd.credit), 0) as total
             FROM journal_details jd
             JOIN journals j ON jd.journal_id = j.id
             WHERE j.order_id = ? 
             AND j.journal_type IN ('order_invoice', 'adjustment') 
             AND j.status = 'posted'
             AND jd.account_id = ?",
            [$orderId, $receivableAccountId]
        );
        
        $oldAmount = (float)($totalRecorded['total'] ?? 0);
        $journalId = (int)$existingJournal['id'];
        
        // If amount same, no need to update
        if (abs($oldAmount - $newAmount) < 0.01) {
            return [
                'success' => true, 
                'journal_id' => $journalId,
                'action' => 'unchanged',
                'message' => 'Tidak ada perubahan nilai'
            ];
        }
        
        $difference = $newAmount - $oldAmount;
        $userId = Session::userId() ?? 1;
        
        // Create adjustment journal
        $adjustmentNumber = generateJournalNumber($companyId, 'JPA'); // Jurnal Penyesuaian
        
        $adjustJournalId = db()->insert('journals', [
            'company_id' => $companyId,
            'order_id' => $orderId,
            'journal_number' => $adjustmentNumber,
            'journal_type' => 'adjustment',
            'journal_date' => date('Y-m-d'),
            'description' => 'Penyesuaian Invoice: ' . ($order['order_number'] ?? '') . ' - Selisih ' . ($difference >= 0 ? '+' : '') . number_format($difference, 0, ',', '.'),
            'reference' => $order['order_number'] ?? '',
            'status' => 'posted',
            'posted_at' => date('Y-m-d H:i:s'),
            'posted_by' => $userId,
            'created_by' => $userId
        ]);
        
        if ($difference > 0) {
            // Price increased: Debit Piutang, Credit Pendapatan
            db()->insert('journal_details', [
                'journal_id' => $adjustJournalId,
                'account_id' => $receivableAccountId,
                'description' => 'Penambahan piutang - ' . ($order['order_number'] ?? ''),
                'debit' => $difference,
                'credit' => 0
            ]);
            
            db()->insert('journal_details', [
                'journal_id' => $adjustJournalId,
                'account_id' => $revenueAccountId,
                'description' => 'Penambahan pendapatan - ' . ($order['order_number'] ?? ''),
                'debit' => 0,
                'credit' => $difference
            ]);
        } else {
            // Price decreased: Debit Pendapatan, Credit Piutang (reversal)
            $absDiff = abs($difference);
            
            db()->insert('journal_details', [
                'journal_id' => $adjustJournalId,
                'account_id' => $revenueAccountId,
                'description' => 'Pengurangan pendapatan - ' . ($order['order_number'] ?? ''),
                'debit' => $absDiff,
                'credit' => 0
            ]);
            
            db()->insert('journal_details', [
                'journal_id' => $adjustJournalId,
                'account_id' => $receivableAccountId,
                'description' => 'Pengurangan piutang - ' . ($order['order_number'] ?? ''),
                'debit' => 0,
                'credit' => $absDiff
            ]);
        }
        
        return [
            'success' => true,
            'journal_id' => $adjustJournalId,
            'original_journal_id' => $journalId,
            'action' => 'adjusted',
            'old_amount' => $oldAmount,
            'new_amount' => $newAmount,
            'difference' => $difference,
            'message' => 'Jurnal penyesuaian berhasil dibuat'
        ];
        
    } catch (Exception $e) {
        error_log("Failed to update invoice journal: " . $e->getMessage());
        return ['success' => false, 'message' => 'Gagal: ' . $e->getMessage()];
    }
}



/**
 * Void invoice journal when order is cancelled
 * 
 * @param int $orderId
 * @return array
 */
function voidOrderInvoiceJournal(int $orderId): array {
    try {
        $userId = Session::userId() ?? 1;
        
        // Void main invoice journal
        $journal = db()->fetchOne(
            "SELECT * FROM journals WHERE order_id = ? AND journal_type = 'order_invoice' AND status = 'posted'",
            [$orderId]
        );
        
        if (!$journal) {
            return ['success' => false, 'message' => 'Jurnal invoice tidak ditemukan'];
        }
        
        db()->update('journals', [
            'status' => 'void',
            'voided_at' => date('Y-m-d H:i:s'),
            'voided_by' => $userId
        ], 'id = ?', [$journal['id']]);
        
        // Also void any adjustment journals
        db()->query(
            "UPDATE journals SET status = 'void', voided_at = NOW(), voided_by = ? 
             WHERE order_id = ? AND journal_type = 'adjustment' AND status = 'posted'",
            [$userId, $orderId]
        );
        
        // Update order to remove journal reference
        try {
            db()->update('orders', [
                'invoice_journal_id' => null
            ], 'id = ?', [$orderId]);
        } catch (Exception $e) {
            // Column might not exist
        }
        
        return ['success' => true, 'message' => 'Jurnal berhasil di-void'];
        
    } catch (Exception $e) {
        return ['success' => false, 'message' => 'Gagal: ' . $e->getMessage()];
    }
}

// ================================================================
// ORDER PAYMENT JOURNAL
// ================================================================

/**
 * Create journal for order payment
 * 
 * Jurnal:
 *   Debit:  Kas/Bank           Rp XXX
 *   Credit: Piutang Usaha      Rp XXX
 * 
 * @param array $order
 * @param float $paymentAmount
 * @param string $paymentMethod
 * @param int|null $bankCashId
 * @param string|null $reference
 * @param string|null $notes
 * @return array
 */
function createOrderPaymentJournal(
    array $order, 
    float $paymentAmount,
    string $paymentMethod = 'transfer',
    ?int $bankCashId = null,
    ?string $reference = null,
    ?string $notes = null
): array {
    $companyId = (int)$order['company_id'];
    $orderId = (int)$order['id'];
    
    if ($paymentAmount <= 0) {
        return ['success' => false, 'message' => 'Jumlah pembayaran harus lebih dari 0'];
    }
    
    // Get accounts
    $receivableAccountId = getAccountingSetting($companyId, 'account_receivable');
    
    // Determine cash/bank account
    $cashBankAccountId = null;
    $bankCashName = 'Kas/Bank';
    
    if ($bankCashId) {
        try {
            $bankCash = db()->fetchOne("SELECT account_id, name FROM bank_cash WHERE id = ?", [$bankCashId]);
            if ($bankCash) {
                $cashBankAccountId = (int)$bankCash['account_id'];
                $bankCashName = $bankCash['name'];
            }
        } catch (Exception $e) {}
    }
    
    if (!$cashBankAccountId) {
        $settingKey = $paymentMethod === 'cash' ? 'default_cash' : 'default_bank';
        $cashBankAccountId = getAccountingSetting($companyId, $settingKey);
    }
    
    if (!$receivableAccountId || !$cashBankAccountId) {
        return ['success' => false, 'message' => 'Pengaturan akun akuntansi belum dikonfigurasi. Silakan setup di menu Akuntansi > Pengaturan.'];
    }
    
    try {
        $needOwnTransaction = !_isInTransaction();
        if ($needOwnTransaction) db()->beginTransaction();
        
        $journalNumber = generateJournalNumber($companyId, 'JKM'); // Jurnal Kas Masuk
        $userId = Session::userId() ?? 1;
        
        // Create journal header
        $journalId = db()->insert('journals', [
            'company_id' => $companyId,
            'order_id' => $orderId,
            'journal_number' => $journalNumber,
            'journal_type' => 'order_payment',
            'journal_date' => date('Y-m-d'),
            'description' => 'Pembayaran Order: ' . ($order['order_number'] ?? '') . ($notes ? ' - ' . $notes : ''),
            'reference' => $reference ?: ($order['order_number'] ?? ''),
            'status' => 'posted',
            'posted_at' => date('Y-m-d H:i:s'),
            'posted_by' => $userId,
            'created_by' => $userId
        ]);
        
        // Debit: Kas/Bank
        db()->insert('journal_details', [
            'journal_id' => $journalId,
            'account_id' => $cashBankAccountId,
            'description' => 'Terima pembayaran ' . ($order['order_number'] ?? '') . ' via ' . $bankCashName,
            'debit' => $paymentAmount,
            'credit' => 0
        ]);
        
        // Credit: Piutang Usaha
        db()->insert('journal_details', [
            'journal_id' => $journalId,
            'account_id' => $receivableAccountId,
            'description' => 'Pelunasan piutang ' . ($order['order_number'] ?? ''),
            'debit' => 0,
            'credit' => $paymentAmount
        ]);
        
        // Update bank_cash balance (increase)
        if ($bankCashId) {
            db()->query(
                "UPDATE bank_cash SET balance = balance + ? WHERE id = ?",
                [$paymentAmount, $bankCashId]
            );
        }
        
        // Record payment in order_payments table
        $paymentId = null;
        try {
            $paymentId = db()->insert('order_payments', [
                'company_id' => $companyId,
                'order_id' => $orderId,
                'journal_id' => $journalId,
                'amount' => $paymentAmount,
                'payment_date' => date('Y-m-d'),
                'payment_method' => $paymentMethod,
                'bank_cash_id' => $bankCashId,
                'reference' => $reference,
                'notes' => $notes,
                'status' => 'posted',
                'created_by' => $userId
            ]);
        } catch (Exception $e) {
            // Table might not exist or have different structure
            error_log("order_payments insert error: " . $e->getMessage());
        }
        
        // Update order paid_amount and status
        $currentPaid = (float)($order['paid_amount'] ?? 0);
        $newPaidAmount = $currentPaid + $paymentAmount;
        $totalPrice = (float)($order['total_final_price'] ?? 0);
        
        if ($newPaidAmount >= $totalPrice) {
            $paymentStatus = 'paid';
            $paidAt = date('Y-m-d');
        } elseif ($newPaidAmount > 0) {
            $paymentStatus = 'partial';
            $paidAt = date('Y-m-d');
        } else {
            $paymentStatus = 'unpaid';
            $paidAt = null;
        }
        
        db()->update('orders', [
            'paid_amount' => $newPaidAmount,
            'payment_status' => $paymentStatus,
            'paid_at' => $paidAt
        ], 'id = ?', [$orderId]);
        
        if ($needOwnTransaction) db()->commit();
        
        return [
            'success' => true,
            'journal_id' => $journalId,
            'journal_number' => $journalNumber,
            'payment_id' => $paymentId,
            'new_paid_amount' => $newPaidAmount,
            'payment_status' => $paymentStatus,
            'message' => 'Pembayaran berhasil dicatat'
        ];
        
    } catch (Exception $e) {
        if (isset($needOwnTransaction) && $needOwnTransaction) { try { db()->rollBack(); } catch (Exception $rbEx) {} }
        return ['success' => false, 'message' => 'Gagal: ' . $e->getMessage()];
    }
}

/**
 * Void/cancel a payment
 * 
 * @param int $paymentId
 * @return array
 */
function voidOrderPayment(int $paymentId): array {
    try {
        $payment = db()->fetchOne("SELECT * FROM order_payments WHERE id = ?", [$paymentId]);
        
        if (!$payment) {
            return ['success' => false, 'message' => 'Pembayaran tidak ditemukan'];
        }
        
        $order = db()->fetchOne("SELECT * FROM orders WHERE id = ?", [$payment['order_id']]);
        
        if (!$order) {
            return ['success' => false, 'message' => 'Order tidak ditemukan'];
        }
        
        $userId = Session::userId() ?? 1;
        
        // Void the journal
        if ($payment['journal_id']) {
            db()->update('journals', [
                'status' => 'void',
                'voided_at' => date('Y-m-d H:i:s'),
                'voided_by' => $userId
            ], 'id = ?', [$payment['journal_id']]);
        }
        
        // Update order
        $newPaidAmount = max(0, (float)$order['paid_amount'] - (float)$payment['amount']);
        $totalPrice = (float)$order['total_final_price'];
        
        if ($newPaidAmount >= $totalPrice) {
            $paymentStatus = 'paid';
        } elseif ($newPaidAmount > 0) {
            $paymentStatus = 'partial';
        } else {
            $paymentStatus = 'unpaid';
        }
        
        db()->update('orders', [
            'paid_amount' => $newPaidAmount,
            'payment_status' => $paymentStatus,
            'paid_at' => $paymentStatus === 'paid' ? $order['paid_at'] : ($newPaidAmount > 0 ? $order['paid_at'] : null)
        ], 'id = ?', [$order['id']]);
        
        // Update bank balance (decrease)
        if ($payment['bank_cash_id']) {
            db()->query(
                "UPDATE bank_cash SET balance = balance - ? WHERE id = ?",
                [$payment['amount'], $payment['bank_cash_id']]
            );
        }
        
        // Delete or mark payment as void
        db()->delete('order_payments', 'id = ?', [$paymentId]);
        
        return ['success' => true, 'message' => 'Pembayaran berhasil dibatalkan'];
        
    } catch (Exception $e) {
        return ['success' => false, 'message' => 'Gagal: ' . $e->getMessage()];
    }
}

// ================================================================
// EXPENSE / TRANSACTION JOURNAL
// ================================================================

/**
 * Create expense/transaction journal
 * 
 * Jurnal Pengeluaran:
 *   Debit:  Beban XXX           Rp XXX
 *   Credit: Kas/Bank            Rp XXX
 * 
 * @param int $companyId
 * @param int $expenseAccountId
 * @param float $amount
 * @param string $description
 * @param int|null $bankCashId
 * @param string|null $reference
 * @param string|null $transactionDate
 * @return array
 */
function createExpenseJournal(
    int $companyId,
    int $expenseAccountId,
    float $amount,
    string $description,
    ?int $bankCashId = null,
    ?string $reference = null,
    ?string $transactionDate = null
): array {
    if ($amount <= 0) {
        return ['success' => false, 'message' => 'Jumlah harus lebih dari 0'];
    }
    
    // Get cash/bank account
    $cashBankAccountId = null;
    if ($bankCashId) {
        try {
            $bankCash = db()->fetchOne("SELECT account_id FROM bank_cash WHERE id = ?", [$bankCashId]);
            $cashBankAccountId = $bankCash ? (int)$bankCash['account_id'] : null;
        } catch (Exception $e) {}
    }
    
    if (!$cashBankAccountId) {
        $cashBankAccountId = getAccountingSetting($companyId, 'default_cash');
    }
    
    if (!$cashBankAccountId) {
        return ['success' => false, 'message' => 'Akun Kas/Bank belum dikonfigurasi'];
    }
    
    // Cek apakah sudah dalam DB transaction dari caller
    $needOwnTransaction = !_isInTransaction();
    
    try {
        if ($needOwnTransaction) db()->beginTransaction();
        
        $journalNumber = generateJournalNumber($companyId, 'JKK');
        $userId = Session::userId() ?? 1;
        $date = $transactionDate ?: date('Y-m-d');
        
        $journalId = db()->insert('journals', [
            'company_id' => $companyId,
            'journal_number' => $journalNumber,
            'journal_type' => 'expense',
            'journal_date' => $date,
            'description' => $description,
            'reference' => $reference,
            'status' => 'posted',
            'posted_at' => date('Y-m-d H:i:s'),
            'posted_by' => $userId,
            'created_by' => $userId
        ]);
        
        db()->insert('journal_details', [
            'journal_id' => $journalId,
            'account_id' => $expenseAccountId,
            'description' => $description,
            'debit' => $amount,
            'credit' => 0
        ]);
        
        db()->insert('journal_details', [
            'journal_id' => $journalId,
            'account_id' => $cashBankAccountId,
            'description' => 'Pembayaran: ' . $description,
            'debit' => 0,
            'credit' => $amount
        ]);
        
        if ($bankCashId) {
            db()->query("UPDATE bank_cash SET balance = balance - ? WHERE id = ?", [$amount, $bankCashId]);
        }
        
        if ($needOwnTransaction) db()->commit();
        
        return [
            'success' => true,
            'journal_id' => $journalId,
            'journal_number' => $journalNumber,
            'message' => 'Transaksi berhasil dicatat'
        ];
        
    } catch (Exception $e) {
        if ($needOwnTransaction) { try { db()->rollBack(); } catch (Exception $rbEx) {} }
        return ['success' => false, 'message' => 'Gagal: ' . $e->getMessage()];
    }
}

/**
 * Create income journal (non-order)
 * 
 * Jurnal Penerimaan:
 *   Debit:  Kas/Bank            Rp XXX
 *   Credit: Pendapatan XXX      Rp XXX
 * 
 * @param int $companyId
 * @param int $incomeAccountId
 * @param float $amount
 * @param string $description
 * @param int|null $bankCashId
 * @param string|null $reference
 * @param string|null $transactionDate
 * @return array
 */
function createIncomeJournal(
    int $companyId,
    int $incomeAccountId,
    float $amount,
    string $description,
    ?int $bankCashId = null,
    ?string $reference = null,
    ?string $transactionDate = null
): array {
    if ($amount <= 0) {
        return ['success' => false, 'message' => 'Jumlah harus lebih dari 0'];
    }
    
    // Get cash/bank account
    $cashBankAccountId = null;
    if ($bankCashId) {
        try {
            $bankCash = db()->fetchOne("SELECT account_id FROM bank_cash WHERE id = ?", [$bankCashId]);
            $cashBankAccountId = $bankCash ? (int)$bankCash['account_id'] : null;
        } catch (Exception $e) {}
    }
    
    if (!$cashBankAccountId) {
        $cashBankAccountId = getAccountingSetting($companyId, 'default_cash');
    }
    
    if (!$cashBankAccountId) {
        return ['success' => false, 'message' => 'Akun Kas/Bank belum dikonfigurasi'];
    }
    
    try {
        $needOwnTransaction = !_isInTransaction();
        if ($needOwnTransaction) db()->beginTransaction();
        
        $journalNumber = generateJournalNumber($companyId, 'JKM'); // Jurnal Kas Masuk
        $userId = Session::userId() ?? 1;
        $date = $transactionDate ?: date('Y-m-d');
        
        // Create journal
        $journalId = db()->insert('journals', [
            'company_id' => $companyId,
            'journal_number' => $journalNumber,
            'journal_type' => 'general',
            'journal_date' => $date,
            'description' => $description,
            'reference' => $reference,
            'status' => 'posted',
            'posted_at' => date('Y-m-d H:i:s'),
            'posted_by' => $userId,
            'created_by' => $userId
        ]);
        
        // Debit: Kas/Bank
        db()->insert('journal_details', [
            'journal_id' => $journalId,
            'account_id' => $cashBankAccountId,
            'description' => 'Terima: ' . $description,
            'debit' => $amount,
            'credit' => 0
        ]);
        
        // Credit: Pendapatan
        db()->insert('journal_details', [
            'journal_id' => $journalId,
            'account_id' => $incomeAccountId,
            'description' => $description,
            'debit' => 0,
            'credit' => $amount
        ]);
        
        // Update bank_cash balance (increase)
        if ($bankCashId) {
            db()->query(
                "UPDATE bank_cash SET balance = balance + ? WHERE id = ?",
                [$amount, $bankCashId]
            );
        }
        
        if ($needOwnTransaction) db()->commit();
        
        return [
            'success' => true,
            'journal_id' => $journalId,
            'journal_number' => $journalNumber,
            'message' => 'Penerimaan berhasil dicatat'
        ];
        
    } catch (Exception $e) {
        if (isset($needOwnTransaction) && $needOwnTransaction) { try { db()->rollBack(); } catch (Exception $rbEx) {} }
        return ['success' => false, 'message' => 'Gagal: ' . $e->getMessage()];
    }
}

// ================================================================
// LOAN / PINJAMAN
// ================================================================

/**
 * Create journal for receiving loan (pinjaman masuk)
 * 
 * Jurnal:
 *   Debit:  Kas/Bank                    Rp XXX
 *   Credit: Hutang Pinjaman Perorangan  Rp XXX
 * 
 * @param int $companyId
 * @param float $amount
 * @param string $lenderName Nama pemberi pinjaman
 * @param int|null $bankCashId
 * @param string|null $reference
 * @param string|null $notes
 * @param string|null $transactionDate
 * @return array
 */
function createLoanReceivedJournal(
    int $companyId,
    float $amount,
    string $lenderName,
    ?int $bankCashId = null,
    ?string $reference = null,
    ?string $notes = null,
    ?string $transactionDate = null
): array {
    if ($amount <= 0) {
        return ['success' => false, 'message' => 'Jumlah pinjaman harus lebih dari 0'];
    }
    
    // Get accounts
    $loanPayableAccountId = getAccountingSetting($companyId, 'loan_payable_personal');
    
    if (!$loanPayableAccountId) {
        return ['success' => false, 'message' => 'Akun Hutang Pinjaman belum dikonfigurasi'];
    }
    
    // Get cash/bank account
    $cashBankAccountId = null;
    $bankCashName = 'Kas';
    
    if ($bankCashId) {
        try {
            $bankCash = db()->fetchOne("SELECT account_id, name FROM bank_cash WHERE id = ?", [$bankCashId]);
            if ($bankCash) {
                $cashBankAccountId = (int)$bankCash['account_id'];
                $bankCashName = $bankCash['name'];
            }
        } catch (Exception $e) {}
    }
    
    if (!$cashBankAccountId) {
        $cashBankAccountId = getAccountingSetting($companyId, 'default_cash');
    }
    
    if (!$cashBankAccountId) {
        return ['success' => false, 'message' => 'Akun Kas/Bank belum dikonfigurasi'];
    }
    
    try {
        $needOwnTransaction = !_isInTransaction();
        if ($needOwnTransaction) db()->beginTransaction();
        
        $journalNumber = generateJournalNumber($companyId, 'JKM');
        $userId = Session::userId() ?? 1;
        $date = $transactionDate ?: date('Y-m-d');
        $description = 'Pinjaman dari ' . $lenderName . ($notes ? ' - ' . $notes : '');
        
        // Create journal
        $journalId = db()->insert('journals', [
            'company_id' => $companyId,
            'journal_number' => $journalNumber,
            'journal_type' => 'general',
            'journal_date' => $date,
            'description' => $description,
            'reference' => $reference,
            'status' => 'posted',
            'posted_at' => date('Y-m-d H:i:s'),
            'posted_by' => $userId,
            'created_by' => $userId
        ]);
        
        db()->insert('journal_details', [
            'journal_id' => $journalId,
            'account_id' => $cashBankAccountId,
            'description' => 'Terima pinjaman dari ' . $lenderName . ' via ' . $bankCashName,
            'debit' => $amount,
            'credit' => 0
        ]);
        
        db()->insert('journal_details', [
            'journal_id' => $journalId,
            'account_id' => $loanPayableAccountId,
            'description' => 'Hutang pinjaman kepada ' . $lenderName,
            'debit' => 0,
            'credit' => $amount
        ]);
        
        if ($bankCashId) {
            db()->query("UPDATE bank_cash SET balance = balance + ? WHERE id = ?", [$amount, $bankCashId]);
        }
        
        if ($needOwnTransaction) db()->commit();
        
        return [
            'success' => true,
            'journal_id' => $journalId,
            'journal_number' => $journalNumber,
            'message' => 'Pinjaman berhasil dicatat'
        ];
        
    } catch (Exception $e) {
        if (isset($needOwnTransaction) && $needOwnTransaction) { try { db()->rollBack(); } catch (Exception $rbEx) {} }
        return ['success' => false, 'message' => 'Gagal: ' . $e->getMessage()];
    }
}

/**
 * Create journal for loan repayment (bayar pinjaman)
 * 
 * Jurnal:
 *   Debit:  Hutang Pinjaman Perorangan  Rp XXX
 *   Credit: Kas/Bank                    Rp XXX
 * 
 * @param int $companyId
 * @param float $amount
 * @param string $lenderName
 * @param int|null $bankCashId
 * @param string|null $reference
 * @param string|null $notes
 * @param string|null $transactionDate
 * @return array
 */
function createLoanRepaymentJournal(
    int $companyId,
    float $amount,
    string $lenderName,
    ?int $bankCashId = null,
    ?string $reference = null,
    ?string $notes = null,
    ?string $transactionDate = null
): array {
    if ($amount <= 0) {
        return ['success' => false, 'message' => 'Jumlah pembayaran harus lebih dari 0'];
    }
    
    // Get accounts
    $loanPayableAccountId = getAccountingSetting($companyId, 'loan_payable_personal');
    
    if (!$loanPayableAccountId) {
        return ['success' => false, 'message' => 'Akun Hutang Pinjaman belum dikonfigurasi'];
    }
    
    // Get cash/bank account
    $cashBankAccountId = null;
    $bankCashName = 'Kas';
    
    if ($bankCashId) {
        try {
            $bankCash = db()->fetchOne("SELECT account_id, name FROM bank_cash WHERE id = ?", [$bankCashId]);
            if ($bankCash) {
                $cashBankAccountId = (int)$bankCash['account_id'];
                $bankCashName = $bankCash['name'];
            }
        } catch (Exception $e) {}
    }
    
    if (!$cashBankAccountId) {
        $cashBankAccountId = getAccountingSetting($companyId, 'default_cash');
    }
    
    if (!$cashBankAccountId) {
        return ['success' => false, 'message' => 'Akun Kas/Bank belum dikonfigurasi'];
    }
    
    try {
        $needOwnTransaction = !_isInTransaction();
        if ($needOwnTransaction) db()->beginTransaction();
        
        $journalNumber = generateJournalNumber($companyId, 'JKK');
        $userId = Session::userId() ?? 1;
        $date = $transactionDate ?: date('Y-m-d');
        $description = 'Bayar pinjaman ke ' . $lenderName . ($notes ? ' - ' . $notes : '');
        
        $journalId = db()->insert('journals', [
            'company_id' => $companyId,
            'journal_number' => $journalNumber,
            'journal_type' => 'general',
            'journal_date' => $date,
            'description' => $description,
            'reference' => $reference,
            'status' => 'posted',
            'posted_at' => date('Y-m-d H:i:s'),
            'posted_by' => $userId,
            'created_by' => $userId
        ]);
        
        db()->insert('journal_details', [
            'journal_id' => $journalId,
            'account_id' => $loanPayableAccountId,
            'description' => 'Pelunasan hutang kepada ' . $lenderName,
            'debit' => $amount,
            'credit' => 0
        ]);
        
        db()->insert('journal_details', [
            'journal_id' => $journalId,
            'account_id' => $cashBankAccountId,
            'description' => 'Bayar pinjaman ke ' . $lenderName . ' via ' . $bankCashName,
            'debit' => 0,
            'credit' => $amount
        ]);
        
        if ($bankCashId) {
            db()->query("UPDATE bank_cash SET balance = balance - ? WHERE id = ?", [$amount, $bankCashId]);
        }
        
        if ($needOwnTransaction) db()->commit();
        
        return [
            'success' => true,
            'journal_id' => $journalId,
            'journal_number' => $journalNumber,
            'message' => 'Pembayaran pinjaman berhasil dicatat'
        ];
        
    } catch (Exception $e) {
        if (isset($needOwnTransaction) && $needOwnTransaction) { try { db()->rollBack(); } catch (Exception $rbEx) {} }
        return ['success' => false, 'message' => 'Gagal: ' . $e->getMessage()];
    }
}

// ================================================================
// UTILITY FUNCTIONS
// ================================================================

/**
 * Get order payment history
 * 
 * @param int $orderId
 * @return array
 */
function getOrderPayments(int $orderId): array {
    try {
        return db()->fetchAll(
            "SELECT op.*, bc.name as bank_cash_name, j.journal_no as journal_number,
                    u.name as created_by_name
             FROM order_payments op
             LEFT JOIN bank_cash bc ON op.bank_cash_id = bc.id
             LEFT JOIN journals j ON op.journal_id = j.id
             LEFT JOIN users u ON op.created_by = u.id
             WHERE op.order_id = ? AND op.invoice_id IS NULL
             ORDER BY op.payment_date DESC, op.id DESC",
            [$orderId]
        );
    } catch (Exception $e) {
        return [];
    }
}

/**
 * Get order remaining amount (unpaid)
 * 
 * @param array $order
 * @return float
 */
function getOrderRemainingAmount(array $order): float {
    $total = (float)($order['total_final_price'] ?? 0);
    $paid = (float)($order['paid_amount'] ?? 0);
    return max(0, $total - $paid);
}

/**
 * Get order journal history
 * 
 * @param int $orderId
 * @return array
 */
function getOrderJournalHistory(int $orderId): array {
    try {
        return db()->fetchAll(
            "SELECT j.*, 
                    (SELECT SUM(debit) FROM journal_details WHERE journal_id = j.id) as total_amount
             FROM journals j 
             WHERE j.order_id = ? 
             ORDER BY j.journal_date DESC, j.id DESC",
            [$orderId]
        );
    } catch (Exception $e) {
        return [];
    }
}

/**
 * Get bank/cash for dropdown
 * 
 * @param int $companyId
 * @return array
 */
function getBankCashDropdown(int $companyId): array {
    try {
        $items = db()->fetchAll(
            "SELECT id, name, type, balance FROM bank_cash 
             WHERE company_id = ? AND is_active = 1 
             ORDER BY type, name",
            [$companyId]
        );
        
        $result = [];
        foreach ($items as $item) {
            $icon = $item['type'] === 'cash' ? '💵' : '🏦';
            $balance = function_exists('formatRupiah') ? formatRupiah($item['balance']) : 'Rp ' . number_format($item['balance'], 0, ',', '.');
            $result[$item['id']] = $icon . ' ' . $item['name'] . ' (Saldo: ' . $balance . ')';
        }
        
        return $result;
    } catch (Exception $e) {
        return [];
    }
}

/**
 * Get accounts for dropdown
 * 
 * @param int $companyId
 * @param string|null $type Filter by account type
 * @return array
 */
function getAccountsDropdown(int $companyId, ?string $type = null): array {
    try {
        $where = "company_id = ? AND is_active = 1";
        $params = [$companyId];
        
        if ($type) {
            $where .= " AND type = ?";
            $params[] = $type;
        }
        
        $accounts = db()->fetchAll(
            "SELECT id, code, name, type FROM accounts WHERE {$where} ORDER BY code",
            $params
        );
        
        $result = [];
        foreach ($accounts as $acc) {
            $result[$acc['id']] = $acc['code'] . ' - ' . $acc['name'];
        }
        
        return $result;
    } catch (Exception $e) {
        return [];
    }
}

/**
 * Get total receivables (piutang)
 * 
 * @param int $companyId
 * @return float
 */
function getTotalReceivables(int $companyId): float {
    try {
        $result = db()->fetchOne(
            "SELECT COALESCE(SUM(total_final_price - paid_amount), 0) as total
             FROM orders 
             WHERE company_id = ? 
             AND payment_status IN ('unpaid', 'partial')
             AND status NOT IN ('cancelled', 'draft')",
            [$companyId]
        );
        return (float)$result['total'];
    } catch (Exception $e) {
        return 0;
    }
}

/**
 * Get total loan payable (hutang pinjaman)
 * 
 * @param int $companyId
 * @return float
 */
function getTotalLoanPayable(int $companyId): float {
    try {
        $loanAccountId = getAccountingSetting($companyId, 'loan_payable_personal');
        
        if (!$loanAccountId) {
            return 0;
        }
        
        $result = db()->fetchOne(
            "SELECT COALESCE(SUM(jd.credit - jd.debit), 0) as total
             FROM journal_details jd
             JOIN journals j ON jd.journal_id = j.id
             WHERE j.company_id = ? 
             AND j.status = 'posted'
             AND jd.account_id = ?",
            [$companyId, $loanAccountId]
        );
        
        return (float)$result['total'];
    } catch (Exception $e) {
        return 0;
    }
}

/**
 * Recalculate order totals from items
 * 
 * @param int $orderId
 * @return array
 */
function recalculateOrderTotals(int $orderId): array {
    try {
        $totals = db()->fetchOne(
            "SELECT 
                COALESCE(SUM(base_price * quantity * COALESCE(num_days, 1)), 0) as total_base_price,
                COALESCE(SUM(markup_amount), 0) as total_markup,
                COALESCE(SUM(final_price), 0) as total_final_price
             FROM order_items 
             WHERE order_id = ?",
            [$orderId]
        );
        
        if ($totals) {
            db()->update('orders', [
                'total_base_price' => $totals['total_base_price'],
                'total_markup' => $totals['total_markup'],
                'total_final_price' => $totals['total_final_price']
            ], 'id = ?', [$orderId]);
        }
        
        return $totals ?: [];
        
    } catch (Exception $e) {
        return [];
    }
}

// ================================================================
// ORDER ITEM EXPENSE (Bayar Vendor) - FITUR BARU
// ================================================================

/**
 * Create expense journal for order item payment to vendor
 * 
 * @param int $companyId
 * @param int $orderItemId
 * @param float $amount
 * @param int $bankCashId
 * @param string|null $notes
 * @param string|null $expenseDate
 * @return array
 */
function createOrderItemExpenseJournal(
    int $companyId,
    int $orderItemId,
    float $amount,
    int $bankCashId,
    ?string $notes = null,
    ?string $expenseDate = null
): array {
    if ($amount <= 0) {
        return ['success' => false, 'message' => 'Jumlah harus lebih dari 0'];
    }
    
    // Get expense account (HPP atau Biaya Pembelian)
    $expenseAccountId = getAccountingSetting($companyId, 'expense_purchase');
    if (!$expenseAccountId) {
        $expenseAccountId = getAccountingSetting($companyId, 'expense_default');
    }
    
    if (!$expenseAccountId) {
        return ['success' => false, 'message' => 'Akun Biaya Pembelian belum dikonfigurasi'];
    }
    
    // Get cash/bank account
    $cashBankAccountId = null;
    $bankCashName = 'Kas';
    
    try {
        $bankCash = db()->fetchOne("SELECT account_id, name FROM bank_cash WHERE id = ?", [$bankCashId]);
        if ($bankCash) {
            $cashBankAccountId = (int)$bankCash['account_id'];
            $bankCashName = $bankCash['name'];
        }
    } catch (Exception $e) {}
    
    if (!$cashBankAccountId) {
        return ['success' => false, 'message' => 'Kas/Bank tidak valid'];
    }
    
    // Get order item info
    $orderItem = db()->fetchOne(
        "SELECT oi.*, o.order_number, o.event_name 
         FROM order_items oi 
         JOIN orders o ON oi.order_id = o.id 
         WHERE oi.id = ?",
        [$orderItemId]
    );
    
    if (!$orderItem) {
        return ['success' => false, 'message' => 'Item order tidak ditemukan'];
    }
    
    try {
        $needOwnTransaction = !_isInTransaction();
        if ($needOwnTransaction) db()->beginTransaction();
        
        $journalNumber = generateJournalNumber($companyId, 'JKK');
        $userId = Session::userId() ?? 1;
        $date = $expenseDate ?: date('Y-m-d');
        $description = 'Bayar vendor ' . ($orderItem['item_type'] ?? 'item') . ' - ' . ($orderItem['order_number'] ?? '') . ' - ' . ($orderItem['event_name'] ?? '');
        if ($notes) {
            $description .= ' - ' . $notes;
        }
        
        // Create journal
        $journalId = db()->insert('journals', [
            'company_id' => $companyId,
            'order_id' => $orderItem['order_id'],
            'journal_number' => $journalNumber,
            'journal_type' => 'expense',
            'journal_date' => $date,
            'description' => $description,
            'reference' => 'EXP-' . date('Ymd') . '-' . strtoupper(substr(uniqid(), -4)),
            'status' => 'posted',
            'posted_at' => date('Y-m-d H:i:s'),
            'posted_by' => $userId,
            'created_by' => $userId
        ]);
        
        db()->insert('journal_details', [
            'journal_id' => $journalId,
            'account_id' => $expenseAccountId,
            'description' => 'Pembelian ' . ($orderItem['item_type'] ?? 'item') . ' - ' . ($orderItem['description'] ?? ''),
            'debit' => $amount,
            'credit' => 0
        ]);
        
        db()->insert('journal_details', [
            'journal_id' => $journalId,
            'account_id' => $cashBankAccountId,
            'description' => 'Bayar vendor via ' . $bankCashName,
            'debit' => 0,
            'credit' => $amount
        ]);
        
        db()->query("UPDATE bank_cash SET balance = balance - ? WHERE id = ?", [$amount, $bankCashId]);
        
        db()->update('order_items', [
            'expense_status' => 'paid',
            'expense_bank_cash_id' => $bankCashId,
            'expense_date' => $date,
            'expense_journal_id' => $journalId,
            'expense_notes' => $notes
        ], 'id = ?', [$orderItemId]);
        
        if ($needOwnTransaction) db()->commit();
        
        return [
            'success' => true,
            'journal_id' => $journalId,
            'journal_number' => $journalNumber,
            'message' => 'Pembayaran vendor berhasil dicatat'
        ];
        
    } catch (Exception $e) {
        if (isset($needOwnTransaction) && $needOwnTransaction) { try { db()->rollBack(); } catch (Exception $rbEx) {} }
        return ['success' => false, 'message' => 'Gagal: ' . $e->getMessage()];
    }
}

/**
 * Void/cancel order item expense
 * 
 * @param int $orderItemId
 * @return array
 */
function voidOrderItemExpense(int $orderItemId): array {
    try {
        $orderItem = db()->fetchOne(
            "SELECT * FROM order_items WHERE id = ?",
            [$orderItemId]
        );
        
        if (!$orderItem || ($orderItem['expense_status'] ?? '') !== 'paid') {
            return ['success' => false, 'message' => 'Item belum dibayar'];
        }
        
        $journalId = $orderItem['expense_journal_id'] ?? null;
        $bankCashId = $orderItem['expense_bank_cash_id'] ?? null;
        
        // Get amount from journal
        $amount = 0;
        if ($journalId) {
            $amount = db()->fetchColumn(
                "SELECT SUM(credit) FROM journal_details WHERE journal_id = ? AND credit > 0",
                [$journalId]
            );
        }
        
        // Void journal
        if ($journalId) {
            db()->update('journals', [
                'status' => 'void',
                'voided_at' => date('Y-m-d H:i:s'),
                'voided_by' => Session::userId()
            ], 'id = ?', [$journalId]);
        }
        
        // Restore bank balance
        if ($bankCashId && $amount > 0) {
            db()->query(
                "UPDATE bank_cash SET balance = balance + ? WHERE id = ?",
                [$amount, $bankCashId]
            );
        }
        
        // Reset order item expense status
        db()->update('order_items', [
            'expense_status' => 'pending',
            'expense_bank_cash_id' => null,
            'expense_date' => null,
            'expense_journal_id' => null,
            'expense_notes' => null
        ], 'id = ?', [$orderItemId]);
        
        return ['success' => true, 'message' => 'Pembayaran vendor dibatalkan'];
        
    } catch (Exception $e) {
        return ['success' => false, 'message' => 'Gagal: ' . $e->getMessage()];
    }
}

/**
 * Get HPP (Cost of Goods Sold) account based on item type
 * BARU: untuk accrual accounting
 */
function getHppAccount(int $companyId, string $itemType): ?int {
    $mapping = [
        'flight' => 'hpp_flight',
        'hotel' => 'hpp_hotel',
        'rental' => 'hpp_transport',
        'bus' => 'hpp_transport',
        'towing' => 'hpp_transport',
        'restaurant' => 'hpp_default',
        'other' => 'hpp_default'
    ];
    
    $key = $mapping[$itemType] ?? 'hpp_default';
    $accountId = getAccountingSetting($companyId, $key);
    
    if (!$accountId) {
        $accountId = getAccountingSetting($companyId, 'hpp_default');
    }
    
    // Fallback: cari akun dengan kode 5000 atau 50xx
    if (!$accountId) {
        $fallback = db()->fetchOne(
            "SELECT id FROM accounts WHERE company_id = ? AND code LIKE '50%' AND type = 'expense' ORDER BY code LIMIT 1",
            [$companyId]
        );
        $accountId = $fallback ? (int)$fallback['id'] : null;
    }
    
    return $accountId;
}

/**
 * Get Vendor Payable (Hutang Vendor) account based on item type
 * BARU: untuk accrual accounting
 */
function getVendorPayableAccount(int $companyId, string $itemType): ?int {
    $mapping = [
        'flight' => 'payable_flight',
        'hotel' => 'payable_hotel',
        'rental' => 'payable_transport',
        'bus' => 'payable_transport',
        'towing' => 'payable_transport',
        'restaurant' => 'payable_vendor',
        'other' => 'payable_vendor'
    ];
    
    $key = $mapping[$itemType] ?? 'payable_vendor';
    $accountId = getAccountingSetting($companyId, $key);
    
    if (!$accountId) {
        $accountId = getAccountingSetting($companyId, 'payable_vendor');
    }
    
    // Fallback: cari akun Hutang Usaha (code 2000)
    if (!$accountId) {
        $fallback = db()->fetchOne(
            "SELECT id FROM accounts WHERE company_id = ? AND code = '2000' AND type = 'liability' LIMIT 1",
            [$companyId]
        );
        $accountId = $fallback ? (int)$fallback['id'] : null;
    }
    
    return $accountId;
}

/**
 * Get item type label in Indonesian
 */
function getItemTypeLabel(string $itemType): string {
    $labels = [
        'flight' => 'Tiket Pesawat',
        'hotel' => 'Hotel',
        'rental' => 'Rental Kendaraan',
        'bus' => 'Bus',
        'towing' => 'Towing',
        'restaurant' => 'Restaurant',
        'other' => 'Lainnya'
    ];
    return $labels[$itemType] ?? ucfirst($itemType);
}

// ================================================================
// HPP JOURNAL (BARU - untuk Accrual Accounting)
// ================================================================

/**
 * Create HPP (Cost of Goods Sold) journal for order
 * BARU: Ini membuat jurnal saat order dibuat untuk mencatat hutang ke vendor
 * 
 * Jurnal per kategori item:
 *   Debit:  HPP (Beban Pokok Penjualan)  Rp XXX (modal vendor)
 *   Credit: Hutang Vendor                Rp XXX (modal vendor)
 * 
 * @param array $order Order data
 * @param array|null $items Order items (if not provided, will be fetched)
 * @return int|null Journal ID or null
 */
function createOrderHppJournal(array $order, ?array $items = null): ?int {
    $companyId = (int)$order['company_id'];
    $orderId = (int)$order['id'];
    $basePrice = (float)($order['total_base_price'] ?? 0);
    
    if ($basePrice <= 0) {
        return null;
    }
    
    // Check if HPP journal already exists
    try {
        $existing = db()->fetchOne(
            "SELECT id FROM journals WHERE order_id = ? AND journal_type = 'order_hpp' AND status = 'posted'",
            [$orderId]
        );
        
        if ($existing) {
            return (int)$existing['id'];
        }
    } catch (Exception $e) {}
    
    // Fetch items if not provided - grouped by item_type
    if ($items === null) {
        try {
            $items = db()->fetchAll(
                "SELECT item_type, 
                        SUM(base_price * quantity * COALESCE(num_days, 1)) as total_base
                 FROM order_items 
                 WHERE order_id = ? 
                 GROUP BY item_type",
                [$orderId]
            );
        } catch (Exception $e) {
            $items = [];
        }
    }
    
    if (empty($items)) {
        // Fallback: use total_base_price with default type
        $items = [['item_type' => $order['service_type'] ?? 'bus', 'total_base' => $basePrice]];
    }
    
    try {
        $journalNumber = generateJournalNumber($companyId, 'JHPP'); // Jurnal HPP
        $userId = Session::userId() ?? 1;
        
        // Create journal header
        $journalId = db()->insert('journals', [
            'company_id' => $companyId,
            'order_id' => $orderId,
            'journal_number' => $journalNumber,
            'journal_type' => 'order_hpp',
            'journal_date' => date('Y-m-d'),
            'description' => 'HPP: ' . ($order['order_number'] ?? '') . ' - ' . ($order['event_name'] ?? 'N/A'),
            'reference' => $order['order_number'] ?? '',
            'status' => 'posted',
            'posted_at' => date('Y-m-d H:i:s'),
            'posted_by' => $userId,
            'created_by' => $userId
        ]);
        
        // Create journal details per item type
        foreach ($items as $item) {
            $itemType = $item['item_type'] ?? 'other';
            $itemBase = (float)($item['total_base'] ?? 0);
            
            if ($itemBase <= 0) continue;
            
            $hppAccountId = getHppAccount($companyId, $itemType);
            $payableAccountId = getVendorPayableAccount($companyId, $itemType);
            
            if (!$hppAccountId || !$payableAccountId) {
                error_log("HPP or Payable account not found for type: {$itemType}");
                continue;
            }
            
            $typeLabel = getItemTypeLabel($itemType);
            
            // Debit: HPP
            db()->insert('journal_details', [
                'journal_id' => $journalId,
                'account_id' => $hppAccountId,
                'description' => "HPP {$typeLabel} - " . ($order['order_number'] ?? ''),
                'debit' => $itemBase,
                'credit' => 0
            ]);
            
            // Credit: Hutang Vendor
            db()->insert('journal_details', [
                'journal_id' => $journalId,
                'account_id' => $payableAccountId,
                'description' => "Hutang {$typeLabel} - " . ($order['order_number'] ?? ''),
                'debit' => 0,
                'credit' => $itemBase
            ]);
        }
        
        // Update order reference
        try {
            db()->update('orders', ['hpp_journal_id' => $journalId], 'id = ?', [$orderId]);
        } catch (Exception $e) {}
        
        return $journalId;
        
    } catch (Exception $e) {
        error_log("Failed to create HPP journal: " . $e->getMessage());
        return null;
    }
}

/**
 * Create both Invoice and HPP journals for new order
 * Fungsi gabungan untuk dipanggil saat order dibuat
 */
function createOrderJournals(array $order): array {
    $result = [
        'invoice_journal_id' => null,
        'hpp_journal_id' => null
    ];
    
    // 1. Create Invoice Journal (fungsi existing)
    $result['invoice_journal_id'] = createOrderInvoiceJournal($order);
    
    // 2. Create HPP Journal (fungsi baru)
    $result['hpp_journal_id'] = createOrderHppJournal($order);
    
    return $result;
}

// ================================================================
// HPP ADJUSTMENT JOURNAL
// ================================================================

/**
 * Update HPP journal when order is edited
 * Creates adjustment if base_price changes
 */
function updateOrderHppJournal(array $order): array {
    $companyId = (int)$order['company_id'];
    $orderId = (int)$order['id'];
    $newBasePrice = (float)($order['total_base_price'] ?? 0);
    
    if ($newBasePrice <= 0) {
        return ['success' => false, 'message' => 'Total modal harus lebih dari 0'];
    }
    
    try {
        // Check existing HPP journal
        $existingJournal = db()->fetchOne(
            "SELECT j.id FROM journals j 
             WHERE j.order_id = ? AND j.journal_type = 'order_hpp' AND j.status = 'posted'
             ORDER BY j.id DESC LIMIT 1",
            [$orderId]
        );
        
        if (!$existingJournal) {
            // No existing journal, create new one
            $journalId = createOrderHppJournal($order);
            return [
                'success' => true,
                'journal_id' => $journalId,
                'action' => 'created',
                'message' => 'Jurnal HPP berhasil dibuat'
            ];
        }
        
        // Get default payable account for calculation
        $payableAccountId = getVendorPayableAccount($companyId, $order['service_type'] ?? 'bus');
        
        if (!$payableAccountId) {
            return ['success' => false, 'message' => 'Akun Hutang Vendor belum dikonfigurasi'];
        }
        
        // Calculate total recorded HPP (from all HPP + HPP adjustment journals)
        $totalRecorded = db()->fetchOne(
            "SELECT COALESCE(SUM(jd.credit), 0) - COALESCE(SUM(jd.debit), 0) as total
             FROM journal_details jd
             JOIN journals j ON jd.journal_id = j.id
             JOIN accounts a ON jd.account_id = a.id
             WHERE j.order_id = ? 
             AND j.journal_type IN ('order_hpp', 'hpp_adjustment') 
             AND j.status = 'posted'
             AND a.type = 'liability'",
            [$orderId]
        );
        
        $oldBasePrice = (float)($totalRecorded['total'] ?? 0);
        
        // If same, no update needed
        if (abs($oldBasePrice - $newBasePrice) < 0.01) {
            return [
                'success' => true,
                'action' => 'unchanged',
                'message' => 'Tidak ada perubahan modal'
            ];
        }
        
        $difference = $newBasePrice - $oldBasePrice;
        $userId = Session::userId() ?? 1;
        
        // Get accounts
        $hppAccountId = getHppAccount($companyId, $order['service_type'] ?? 'bus');
        
        // Create adjustment journal
        $adjustNumber = generateJournalNumber($companyId, 'JHPA'); // Jurnal HPP Adjustment
        
        $adjustJournalId = db()->insert('journals', [
            'company_id' => $companyId,
            'order_id' => $orderId,
            'journal_number' => $adjustNumber,
            'journal_type' => 'hpp_adjustment',
            'journal_date' => date('Y-m-d'),
            'description' => 'Penyesuaian HPP: ' . ($order['order_number'] ?? '') . ' - Selisih ' . ($difference >= 0 ? '+' : '') . number_format($difference, 0, ',', '.'),
            'reference' => $order['order_number'] ?? '',
            'status' => 'posted',
            'posted_at' => date('Y-m-d H:i:s'),
            'posted_by' => $userId,
            'created_by' => $userId
        ]);
        
        if ($difference > 0) {
            // Modal increased: Debit HPP, Credit Hutang
            db()->insert('journal_details', [
                'journal_id' => $adjustJournalId,
                'account_id' => $hppAccountId,
                'description' => 'Penambahan HPP - ' . ($order['order_number'] ?? ''),
                'debit' => $difference,
                'credit' => 0
            ]);
            
            db()->insert('journal_details', [
                'journal_id' => $adjustJournalId,
                'account_id' => $payableAccountId,
                'description' => 'Penambahan Hutang Vendor - ' . ($order['order_number'] ?? ''),
                'debit' => 0,
                'credit' => $difference
            ]);
        } else {
            // Modal decreased: Debit Hutang, Credit HPP
            $absDiff = abs($difference);
            
            db()->insert('journal_details', [
                'journal_id' => $adjustJournalId,
                'account_id' => $payableAccountId,
                'description' => 'Pengurangan Hutang Vendor - ' . ($order['order_number'] ?? ''),
                'debit' => $absDiff,
                'credit' => 0
            ]);
            
            db()->insert('journal_details', [
                'journal_id' => $adjustJournalId,
                'account_id' => $hppAccountId,
                'description' => 'Pengurangan HPP - ' . ($order['order_number'] ?? ''),
                'debit' => 0,
                'credit' => $absDiff
            ]);
        }
        
        return [
            'success' => true,
            'journal_id' => $adjustJournalId,
            'action' => 'adjusted',
            'old_amount' => $oldBasePrice,
            'new_amount' => $newBasePrice,
            'difference' => $difference,
            'message' => 'Jurnal penyesuaian HPP berhasil dibuat'
        ];
        
    } catch (Exception $e) {
        error_log("Failed to update HPP journal: " . $e->getMessage());
        return ['success' => false, 'message' => 'Gagal: ' . $e->getMessage()];
    }
}

/**
 * Update both Invoice and HPP journals when order is edited
 */
function updateOrderJournals(array $order): array {
    $invoiceResult = updateOrderInvoiceJournal($order);
    $hppResult = updateOrderHppJournal($order);
    
    return [
        'invoice' => $invoiceResult,
        'hpp' => $hppResult,
        'success' => $invoiceResult['success'] && $hppResult['success']
    ];
}

// ================================================================
// VOID HPP JOURNALS
// ================================================================

/**
 * Void HPP journal when order is cancelled
 */
function voidOrderHppJournal(int $orderId): array {
    try {
        $userId = Session::userId() ?? 1;
        
        db()->query(
            "UPDATE journals SET status = 'void', voided_at = NOW(), voided_by = ? 
             WHERE order_id = ? AND journal_type IN ('order_hpp', 'hpp_adjustment') AND status = 'posted'",
            [$userId, $orderId]
        );
        
        try {
            db()->update('orders', ['hpp_journal_id' => null], 'id = ?', [$orderId]);
        } catch (Exception $e) {}
        
        return ['success' => true, 'message' => 'Jurnal HPP berhasil di-void'];
        
    } catch (Exception $e) {
        return ['success' => false, 'message' => 'Gagal: ' . $e->getMessage()];
    }
}

/**
 * Void all order journals (Invoice + HPP)
 */
function voidOrderJournals(int $orderId): array {
    $invoiceResult = voidOrderInvoiceJournal($orderId);
    $hppResult = voidOrderHppJournal($orderId);
    
    return [
        'invoice' => $invoiceResult,
        'hpp' => $hppResult,
        'success' => $invoiceResult['success'] && $hppResult['success']
    ];
}

// ================================================================
// VENDOR PAYMENT JOURNAL (VERSI BARU dengan Hutang)
// ================================================================

/**
 * Create journal for vendor payment (VERSI BARU)
 * Debit Hutang Vendor, Credit Kas/Bank
 * 
 * CATATAN: Fungsi ini BERBEDA dengan createOrderItemExpenseJournal() yang existing.
 * Fungsi lama: Debit Biaya, Credit Kas (cash basis)
 * Fungsi baru: Debit Hutang, Credit Kas (accrual basis)
 * 
 * Gunakan fungsi ini jika sudah migrasi ke accrual accounting.
 */
function createVendorPaymentJournalAccrual(
    int $orderId,
    float $amount,
    int $bankCashId,
    ?string $notes = null,
    ?string $expenseDate = null
): array {
    if ($amount <= 0) {
        return ['success' => false, 'message' => 'Jumlah pembayaran harus lebih dari 0'];
    }
    
    try {
        // Get order
        $order = db()->fetchOne(
            "SELECT o.*, 
                    (SELECT item_type FROM order_items WHERE order_id = o.id LIMIT 1) as item_type
             FROM orders o WHERE o.id = ?",
            [$orderId]
        );
        
        if (!$order) {
            return ['success' => false, 'message' => 'Order tidak ditemukan'];
        }
        
        $companyId = (int)$order['company_id'];
        $itemType = $order['item_type'] ?? $order['service_type'] ?? 'bus';
        
        // Get Hutang Vendor account
        $payableAccountId = getVendorPayableAccount($companyId, $itemType);
        
        // Get Kas/Bank account
        $bankCash = db()->fetchOne(
            "SELECT bc.*, a.name as account_name 
             FROM bank_cash bc 
             LEFT JOIN accounts a ON bc.account_id = a.id 
             WHERE bc.id = ? AND bc.company_id = ?",
            [$bankCashId, $companyId]
        );
        
        if (!$bankCash) {
            return ['success' => false, 'message' => 'Kas/Bank tidak ditemukan'];
        }
        
        $cashAccountId = (int)$bankCash['account_id'];
        
        if (!$payableAccountId || !$cashAccountId) {
            return ['success' => false, 'message' => 'Akun belum dikonfigurasi'];
        }
        
        // Check balance
        if ((float)$bankCash['balance'] < $amount) {
            return ['success' => false, 'message' => 'Saldo ' . $bankCash['name'] . ' tidak mencukupi'];
        }
        
        $journalNumber = generateJournalNumber($companyId, 'JKK');
        $userId = Session::userId() ?? 1;
        $date = $expenseDate ?: date('Y-m-d');
        
        $description = 'Bayar vendor: ' . ($order['event_name'] ?: $order['order_number']);
        if ($notes) $description .= ' - ' . $notes;
        
        $needOwnTransaction = !_isInTransaction();
        if ($needOwnTransaction) db()->beginTransaction();
        
        // Create journal header
        $journalId = db()->insert('journals', [
            'company_id' => $companyId,
            'order_id' => $orderId,
            'journal_number' => $journalNumber,
            'journal_type' => 'vendor_payment',
            'journal_date' => $date,
            'description' => $description,
            'reference' => $order['order_number'],
            'status' => 'posted',
            'posted_at' => date('Y-m-d H:i:s'),
            'posted_by' => $userId,
            'created_by' => $userId
        ]);
        
        $typeLabel = getItemTypeLabel($itemType);
        
        // Debit: Hutang Vendor (mengurangi hutang)
        db()->insert('journal_details', [
            'journal_id' => $journalId,
            'account_id' => $payableAccountId,
            'description' => "Pelunasan Hutang {$typeLabel} - " . $order['order_number'],
            'debit' => $amount,
            'credit' => 0
        ]);
        
        // Credit: Kas/Bank
        db()->insert('journal_details', [
            'journal_id' => $journalId,
            'account_id' => $cashAccountId,
            'description' => 'Pembayaran dari ' . $bankCash['name'],
            'debit' => 0,
            'credit' => $amount
        ]);
        
        // Update bank_cash balance
        db()->query(
            "UPDATE bank_cash SET balance = balance - ? WHERE id = ?",
            [$amount, $bankCashId]
        );
        
        if ($needOwnTransaction) db()->commit();
        
        return [
            'success' => true,
            'journal_id' => $journalId,
            'journal_number' => $journalNumber,
            'message' => 'Pembayaran vendor berhasil dicatat (accrual)'
        ];
        
    } catch (Exception $e) {
        if (isset($needOwnTransaction) && $needOwnTransaction) { try { db()->rollBack(); } catch (Exception $rbEx) {} }
        error_log("Failed to create vendor payment journal: " . $e->getMessage());
        return ['success' => false, 'message' => 'Gagal: ' . $e->getMessage()];
    }
}

// ================================================================
// HELPER: Get Total Vendor Payables
// ================================================================

/**
 * Get total vendor payables (Hutang ke Vendor)
 */
function getTotalVendorPayables(int $companyId): float {
    try {
        $result = db()->fetchOne(
            "SELECT 
                COALESCE(SUM(oi.base_price * oi.quantity * COALESCE(oi.num_days, 1)), 0) as total_modal,
                COALESCE(SUM(CASE WHEN oi.expense_status = 'paid' 
                    THEN oi.base_price * oi.quantity * COALESCE(oi.num_days, 1) 
                    ELSE 0 END), 0) as total_paid
             FROM order_items oi
             JOIN orders o ON oi.order_id = o.id
             WHERE o.company_id = ?
             AND o.status NOT IN ('cancelled', 'draft')",
            [$companyId]
        );
        return (float)($result['total_modal'] ?? 0) - (float)($result['total_paid'] ?? 0);
    } catch (Exception $e) {
        return 0;
    }
}

// === SELESAI COPY SAMPAI SINI ===



// ================================================================
// FINANCIAL DASHBOARD
// ================================================================

/**
 * Get financial dashboard summary
 * 
 * @param int $companyId
 * @return array
 */
function getFinancialDashboard(int $companyId): array {
    $dashboard = [
        'cash_balance' => 0,
        'receivables' => 0,
        'payables_loan' => 0,
        'revenue_this_month' => 0,
        'expense_this_month' => 0,
        'profit_this_month' => 0,
        'pending_expenses' => 0,
        'cash_accounts' => []
    ];
    
    try {
        // 1. Total Cash Balance
        $cashResult = db()->fetchOne(
            "SELECT COALESCE(SUM(balance), 0) as total FROM bank_cash WHERE company_id = ? AND is_active = 1",
            [$companyId]
        );
        $dashboard['cash_balance'] = (float)($cashResult['total'] ?? 0);
        
        // 2. Total Receivables (Piutang) - gunakan fungsi jika ada
        if (function_exists('getTotalReceivables')) {
            $dashboard['receivables'] = getTotalReceivables($companyId);
        } else {
            $recResult = db()->fetchOne(
                "SELECT COALESCE(SUM(total_final_price - paid_amount), 0) as total
                 FROM orders 
                 WHERE company_id = ? 
                 AND payment_status IN ('unpaid', 'partial')
                 AND status NOT IN ('cancelled', 'draft')",
                [$companyId]
            );
            $dashboard['receivables'] = (float)($recResult['total'] ?? 0);
        }
        
        // 3. Total Loan Payables (Hutang Pinjaman) - gunakan fungsi jika ada
        if (function_exists('getTotalLoanPayable')) {
            $dashboard['payables_loan'] = getTotalLoanPayable($companyId);
        } else {
            $loanResult = db()->fetchOne(
                "SELECT COALESCE(SUM(amount - paid_amount), 0) as total
                 FROM loans 
                 WHERE company_id = ? 
                 AND loan_type = 'received'
                 AND status = 'active'",
                [$companyId]
            );
            $dashboard['payables_loan'] = (float)($loanResult['total'] ?? 0);
        }
        
        // 4. Revenue This Month
        $monthStart = date('Y-m-01');
        $monthEnd = date('Y-m-t');
        
        $revenueResult = db()->fetchOne(
            "SELECT COALESCE(SUM(total_final_price), 0) as total
             FROM orders 
             WHERE company_id = ? 
             AND status NOT IN ('cancelled', 'draft')
             AND order_date BETWEEN ? AND ?",
            [$companyId, $monthStart, $monthEnd]
        );
        $dashboard['revenue_this_month'] = (float)($revenueResult['total'] ?? 0);
        
        // 5. Expense This Month (dari journal type expense)
        $expenseResult = db()->fetchOne(
            "SELECT COALESCE(SUM(jd.debit), 0) as total
             FROM journal_details jd
             JOIN journals j ON jd.journal_id = j.id
             WHERE j.company_id = ? 
             AND j.status = 'posted'
             AND j.journal_type = 'expense'
             AND j.journal_date BETWEEN ? AND ?",
            [$companyId, $monthStart, $monthEnd]
        );
        $dashboard['expense_this_month'] = (float)($expenseResult['total'] ?? 0);
        
        // 6. Profit This Month
        $modalResult = db()->fetchOne(
            "SELECT COALESCE(SUM(oi.base_price * oi.quantity * COALESCE(oi.num_days, 1)), 0) as total
             FROM order_items oi
             JOIN orders o ON oi.order_id = o.id
             WHERE o.company_id = ?
             AND o.status NOT IN ('cancelled', 'draft')
             AND o.order_date BETWEEN ? AND ?",
            [$companyId, $monthStart, $monthEnd]
        );
        $dashboard['profit_this_month'] = $dashboard['revenue_this_month'] - (float)($modalResult['total'] ?? 0);
        
        // 7. Pending Expenses (belum bayar vendor)
        try {
            $pendingResult = db()->fetchOne(
                "SELECT COALESCE(SUM(oi.base_price * oi.quantity * COALESCE(oi.num_days, 1)), 0) as total
                 FROM order_items oi
                 JOIN orders o ON oi.order_id = o.id
                 WHERE o.company_id = ?
                 AND o.status NOT IN ('cancelled', 'draft')
                 AND (oi.expense_status IS NULL OR oi.expense_status = 'pending')",
                [$companyId]
            );
            $dashboard['pending_expenses'] = (float)($pendingResult['total'] ?? 0);
        } catch (Exception $e) {
            // Column expense_status might not exist yet
            $dashboard['pending_expenses'] = 0;
        }
        
        // 8. Cash Accounts Detail
        $dashboard['cash_accounts'] = db()->fetchAll(
            "SELECT id, name, type, balance FROM bank_cash WHERE company_id = ? AND is_active = 1 ORDER BY type, name",
            [$companyId]
        );
        
    } catch (Exception $e) {
        error_log("getFinancialDashboard error: " . $e->getMessage());
    }
    
    return $dashboard;
}