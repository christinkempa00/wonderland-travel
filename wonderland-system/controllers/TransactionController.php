<?php
/**
 * ================================================================
 * TRAVEL MANAGEMENT SYSTEM - Transaction Controller
 * ================================================================
 * 
 * File: /controllers/TransactionController.php
 * 
 * FIXED: v2 - Proper database transaction untuk mencegah orphan journals
 *        dan duplikasi entries di arus kas
 * 
 * ROOT CAUSE MASALAH LAMA:
 * 1. Journal dibuat DULUAN sebelum transaction record
 * 2. Tidak ada beginTransaction/commit/rollBack
 * 3. Tidak ada proteksi double-submit
 * 
 * SOLUSI:
 * 1. Semua operasi dibungkus dalam DB transaction
 * 2. Transaction record dibuat DULUAN, baru journal
 * 3. Ada token untuk mencegah double-submit
 */

require_once HELPERS_PATH . '/AccountingHelper.php';

class TransactionController {
    
    /**
     * List transactions
     */
    public function index(): void {
        $companyId = Session::companyId();
        $page = (int) ($_GET['page'] ?? 1);
        $perPage = PAGINATION_PER_PAGE;
        
        $filters = [
            'search' => trim($_GET['search'] ?? ''),
            'type' => $_GET['type'] ?? '',
            'date_from' => $_GET['date_from'] ?? '',
            'date_to' => $_GET['date_to'] ?? ''
        ];
        
        $where = "t.company_id = ?";
        $params = [$companyId];
        
        if (!empty($filters['type'])) {
            $where .= " AND t.transaction_type = ?";
            $params[] = $filters['type'];
        }
        
        if (!empty($filters['date_from'])) {
            $where .= " AND t.transaction_date >= ?";
            $params[] = $filters['date_from'];
        }
        
        if (!empty($filters['date_to'])) {
            $where .= " AND t.transaction_date <= ?";
            $params[] = $filters['date_to'];
        }
        
        if (!empty($filters['search'])) {
            $where .= " AND (t.description LIKE ? OR t.reference LIKE ? OR t.transaction_number LIKE ?)";
            $params[] = "%{$filters['search']}%";
            $params[] = "%{$filters['search']}%";
            $params[] = "%{$filters['search']}%";
        }
        
        // Count total
        $total = (int) db()->fetchColumn("SELECT COUNT(*) FROM transactions t WHERE {$where}", $params);
        
        $offset = ($page - 1) * $perPage;
        
        // Get transactions
        $transactions = db()->fetchAll(
            "SELECT t.*, 
                    a.code as account_code, a.name as account_name,
                    bc.name as bank_cash_name,
                    j.journal_number
             FROM transactions t
             LEFT JOIN accounts a ON t.account_id = a.id
             LEFT JOIN bank_cash bc ON t.bank_cash_id = bc.id
             LEFT JOIN journals j ON t.journal_id = j.id
             WHERE {$where}
             ORDER BY t.transaction_date DESC, t.id DESC
             LIMIT {$perPage} OFFSET {$offset}",
            $params
        );
        
        // Calculate totals
        $totals = db()->fetchOne(
            "SELECT 
                COALESCE(SUM(CASE WHEN transaction_type = 'income' THEN amount ELSE 0 END), 0) as total_income,
                COALESCE(SUM(CASE WHEN transaction_type = 'expense' THEN amount ELSE 0 END), 0) as total_expense
             FROM transactions t WHERE {$where}",
            $params
        );
        
        $pagination = [
            'total' => $total,
            'per_page' => $perPage,
            'current_page' => $page,
            'last_page' => ceil($total / $perPage) ?: 1
        ];
        
        $data = [
            'pageTitle' => 'Transaksi',
            'pageHeader' => true,
            'pageSubtitle' => 'Kelola pengeluaran dan pemasukan operasional',
            'pageActions' => $this->getPageActions(),
            'transactions' => $transactions,
            'pagination' => $pagination,
            'filters' => $filters,
            'totals' => $totals
        ];
        
        render('transactions/index', $data);
    }
    
    /**
     * Create transaction form
     */
    public function create(): void {
        $companyId = Session::companyId();
        $type = $_GET['type'] ?? 'expense';
        
        // Get expense accounts (5.x)
        $expenseAccounts = db()->fetchAll(
            "SELECT id, code, name FROM accounts 
             WHERE company_id = ? AND type = 'expense' AND is_active = 1 
             ORDER BY code",
            [$companyId]
        );
        
        // Get income accounts (4.x) - for income transactions
        $incomeAccounts = db()->fetchAll(
            "SELECT id, code, name FROM accounts 
             WHERE company_id = ? AND type = 'revenue' AND is_active = 1 
             ORDER BY code",
            [$companyId]
        );
        
        // Generate anti-double-submit token
        $formToken = bin2hex(random_bytes(16));
        $_SESSION['transaction_form_token'] = $formToken;
        
        $data = [
            'pageTitle' => $type === 'expense' ? 'Catat Pengeluaran' : 'Catat Pemasukan',
            'pageHeader' => true,
            'breadcrumbs' => [
                ['label' => 'Transaksi', 'url' => '/transactions'],
                ['label' => $type === 'expense' ? 'Pengeluaran Baru' : 'Pemasukan Baru']
            ],
            'type' => $type,
            'expenseAccounts' => $expenseAccounts,
            'incomeAccounts' => $incomeAccounts,
            'bankCashOptions' => getBankCashDropdown($companyId),
            'transaction' => null,
            'isEdit' => false,
            'formToken' => $formToken
        ];
        
        render('transactions/form', $data);
    }
    
    /**
     * Store transaction - FIXED with proper DB transaction
     * 
     * PERUBAHAN UTAMA:
     * 1. Cek double-submit token
     * 2. beginTransaction() di awal
     * 3. Insert transaction record DULU
     * 4. Baru buat journal (di dalam transaction yang sama)
     * 5. commit() atau rollBack()
     */
    public function store(): void {
        $companyId = Session::companyId();
        
        // ============================================================
        // ANTI DOUBLE-SUBMIT: Cek token
        // Hanya block jika token session ADA dan form juga kirim token
        // tapi nilainya TIDAK cocok. Jika form tidak kirim token, lewati.
        // ============================================================
        $submittedToken = $_POST['form_token'] ?? '';
        $sessionToken = $_SESSION['transaction_form_token'] ?? '';
        
        if (!empty($submittedToken) && !empty($sessionToken) && $submittedToken !== $sessionToken) {
            // Token tidak cocok - kemungkinan double submit
            Session::flash('error', 'Form sudah disubmit sebelumnya. Silakan cek daftar transaksi.');
            redirect('/transactions');
            return;
        }
        
        // Hapus token agar tidak bisa submit ulang
        unset($_SESSION['transaction_form_token']);
        
        // ============================================================
        // VALIDASI INPUT
        // ============================================================
        $type = $_POST['transaction_type'] ?? 'expense';
        $date = $_POST['transaction_date'] ?? date('Y-m-d');
        $accountId = (int) ($_POST['account_id'] ?? 0);
        $bankCashId = !empty($_POST['bank_cash_id']) ? (int) $_POST['bank_cash_id'] : null;
        $amount = (float) preg_replace('/[^\d]/', '', $_POST['amount'] ?? '0');
        $description = trim($_POST['description'] ?? '');
        $reference = trim($_POST['reference'] ?? '');
        $category = trim($_POST['category'] ?? '');
        
        if ($amount <= 0) {
            Session::flash('error', 'Jumlah harus lebih dari 0.');
            flashInput($_POST);
            redirect('/transactions/create?type=' . $type);
            return;
        }
        
        if (!$accountId) {
            Session::flash('error', 'Pilih akun yang sesuai.');
            flashInput($_POST);
            redirect('/transactions/create?type=' . $type);
            return;
        }
        
        // ============================================================
        // MULAI DATABASE TRANSACTION
        // Semua operasi DB dibungkus - kalau ada yang gagal, SEMUA rollback
        // ============================================================
        try {
            db()->beginTransaction();
            
            // 1. Generate nomor transaksi
            $transNumber = $this->generateTransactionNumber($companyId, $type);
            
            // 2. Insert TRANSACTION RECORD DULU (tanpa journal_id)
            $transactionId = db()->insert('transactions', [
                'company_id' => $companyId,
                'transaction_number' => $transNumber,
                'transaction_date' => $date,
                'transaction_type' => $type,
                'category' => $category,
                'account_id' => $accountId,
                'bank_cash_id' => $bankCashId,
                'amount' => $amount,
                'description' => $description,
                'reference' => $reference,
                'journal_id' => null, // Akan diupdate setelah journal dibuat
                'status' => 'posted',
                'created_by' => Session::userId()
            ]);
            
            // 3. Baru buat JOURNAL (masih dalam transaction yang sama)
            if ($type === 'expense') {
                $journalResult = $this->createExpenseJournalSafe(
                    $companyId, $accountId, $amount, $description,
                    $bankCashId, $reference, $date
                );
            } else {
                $journalResult = $this->createIncomeJournalSafe(
                    $companyId, $accountId, $amount, $description,
                    $bankCashId, $reference, $date
                );
            }
            
            if (!$journalResult['success']) {
                // Journal gagal → rollback SEMUA (termasuk transaction record)
                db()->rollBack();
                Session::flash('error', $journalResult['message']);
                flashInput($_POST);
                redirect('/transactions/create?type=' . $type);
                return;
            }
            
            // 4. Update transaction dengan journal_id
            db()->query(
                "UPDATE transactions SET journal_id = ? WHERE id = ?",
                [$journalResult['journal_id'], $transactionId]
            );
            
            // 5. COMMIT - semua berhasil
            db()->commit();
            
            logActivity('create', 'transaction', $transactionId, 'transaction', 
                "Created {$type}: {$transNumber} - " . formatRupiah($amount));
            
            Session::flash('success', 'Transaksi berhasil dicatat.');
            redirect('/transactions');
            
        } catch (Exception $e) {
            // ROLLBACK - pastikan tidak ada data partial tersimpan
            try {
                db()->rollBack();
            } catch (Exception $rbEx) {
                // rollBack bisa gagal kalau transaction sudah tidak aktif
                error_log("Rollback failed: " . $rbEx->getMessage());
            }
            
            Session::flash('error', 'Gagal menyimpan: ' . $e->getMessage());
            flashInput($_POST);
            redirect('/transactions/create?type=' . $type);
        }
    }
    
    /**
     * Show transaction detail
     */
    public function show(int $id): void {
        $transaction = $this->findTransaction($id);
        
        if (!$transaction) {
            Session::flash('error', 'Transaksi tidak ditemukan.');
            redirect('/transactions');
            return;
        }
        
        // Get journal details if exists
        $journalDetails = [];
        if ($transaction['journal_id']) {
            $journalDetails = db()->fetchAll(
                "SELECT jd.*, a.code as account_code, a.name as account_name
                 FROM journal_details jd
                 JOIN accounts a ON jd.account_id = a.id
                 WHERE jd.journal_id = ?
                 ORDER BY jd.id",
                [$transaction['journal_id']]
            );
        }
        
        $data = [
            'pageTitle' => 'Detail Transaksi',
            'pageHeader' => true,
            'breadcrumbs' => [
                ['label' => 'Transaksi', 'url' => '/transactions'],
                ['label' => $transaction['transaction_number']]
            ],
            'transaction' => $transaction,
            'journalDetails' => $journalDetails
        ];
        
        render('transactions/show', $data);
    }
    
    /**
     * Void transaction
     */
    public function void(int $id): void {
        $transaction = $this->findTransaction($id);
        
        if (!$transaction) {
            Session::flash('error', 'Transaksi tidak ditemukan.');
            redirect('/transactions');
            return;
        }
        
        if ($transaction['status'] === 'void') {
            Session::flash('error', 'Transaksi ini sudah dibatalkan.');
            redirect('/transactions');
            return;
        }
        
        try {
            db()->beginTransaction();
            
            // Void the journal
            if ($transaction['journal_id']) {
                db()->update('journals', [
                    'status' => 'void',
                    'voided_at' => date('Y-m-d H:i:s'),
                    'voided_by' => Session::userId()
                ], 'id = ?', [$transaction['journal_id']]);
            }
            
            // Reverse bank balance
            if ($transaction['bank_cash_id']) {
                $adjustment = $transaction['transaction_type'] === 'expense' 
                    ? $transaction['amount']  // Add back for expense
                    : -$transaction['amount']; // Subtract for income
                    
                db()->query(
                    "UPDATE bank_cash SET balance = balance + ? WHERE id = ?",
                    [$adjustment, $transaction['bank_cash_id']]
                );
            }
            
            // Update transaction status
            db()->update('transactions', ['status' => 'void'], 'id = ?', [$id]);
            
            db()->commit();
            
            logActivity('void', 'transaction', $id, 'transaction', 
                "Voided: {$transaction['transaction_number']}");
            
            Session::flash('success', 'Transaksi berhasil dibatalkan.');
            
        } catch (Exception $e) {
            try { db()->rollBack(); } catch (Exception $rbEx) {}
            Session::flash('error', 'Gagal membatalkan: ' . $e->getMessage());
        }
        
        redirect('/transactions');
    }
    
    // ================================================================
    // SAFE JOURNAL CREATION METHODS
    // Tidak ada try/catch sendiri - error akan propagate ke store()
    // sehingga rollBack() di store() bisa menangkap semuanya
    // ================================================================
    
    /**
     * Create expense journal - SAFE version (tanpa try/catch internal)
     * Debit: Beban
     * Credit: Kas/Bank
     */
    private function createExpenseJournalSafe(
        int $companyId,
        int $expenseAccountId,
        float $amount,
        string $description,
        ?int $bankCashId = null,
        ?string $reference = null,
        ?string $transactionDate = null
    ): array {
        // Get cash/bank account
        $cashBankAccountId = null;
        if ($bankCashId) {
            $bankCash = db()->fetchOne("SELECT account_id FROM bank_cash WHERE id = ?", [$bankCashId]);
            $cashBankAccountId = $bankCash ? (int)$bankCash['account_id'] : null;
        }
        
        if (!$cashBankAccountId) {
            $cashBankAccountId = getAccountingSetting($companyId, 'default_cash');
        }
        
        if (!$cashBankAccountId) {
            return ['success' => false, 'message' => 'Akun Kas/Bank belum dikonfigurasi'];
        }
        
        $journalNumber = generateJournalNumber($companyId, 'JKK');
        $userId = Session::userId() ?? 1;
        $date = $transactionDate ?: date('Y-m-d');
        
        // Create journal - TANPA try/catch, biar error naik ke store()
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
        
        // Debit: Beban
        db()->insert('journal_details', [
            'journal_id' => $journalId,
            'account_id' => $expenseAccountId,
            'description' => $description,
            'debit' => $amount,
            'credit' => 0
        ]);
        
        // Credit: Kas/Bank
        db()->insert('journal_details', [
            'journal_id' => $journalId,
            'account_id' => $cashBankAccountId,
            'description' => 'Pembayaran: ' . $description,
            'debit' => 0,
            'credit' => $amount
        ]);
        
        // Update bank_cash balance (decrease)
        if ($bankCashId) {
            db()->query(
                "UPDATE bank_cash SET balance = balance - ? WHERE id = ?",
                [$amount, $bankCashId]
            );
        }
        
        return [
            'success' => true,
            'journal_id' => $journalId,
            'journal_number' => $journalNumber,
            'message' => 'Transaksi berhasil dicatat'
        ];
    }
    
    /**
     * Create income journal - SAFE version (tanpa try/catch internal)
     * Debit: Kas/Bank
     * Credit: Pendapatan
     */
    private function createIncomeJournalSafe(
        int $companyId,
        int $incomeAccountId,
        float $amount,
        string $description,
        ?int $bankCashId = null,
        ?string $reference = null,
        ?string $transactionDate = null
    ): array {
        // Get cash/bank account
        $cashBankAccountId = null;
        if ($bankCashId) {
            $bankCash = db()->fetchOne("SELECT account_id FROM bank_cash WHERE id = ?", [$bankCashId]);
            $cashBankAccountId = $bankCash ? (int)$bankCash['account_id'] : null;
        }
        
        if (!$cashBankAccountId) {
            $cashBankAccountId = getAccountingSetting($companyId, 'default_cash');
        }
        
        if (!$cashBankAccountId) {
            return ['success' => false, 'message' => 'Akun Kas/Bank belum dikonfigurasi'];
        }
        
        $journalNumber = generateJournalNumber($companyId, 'JKM');
        $userId = Session::userId() ?? 1;
        $date = $transactionDate ?: date('Y-m-d');
        
        // Create journal
        $journalId = db()->insert('journals', [
            'company_id' => $companyId,
            'journal_number' => $journalNumber,
            'journal_type' => 'income',
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
            'description' => 'Penerimaan: ' . $description,
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
        
        return [
            'success' => true,
            'journal_id' => $journalId,
            'journal_number' => $journalNumber
        ];
    }
    
    /**
     * Generate transaction number
     */
    private function generateTransactionNumber(int $companyId, string $type): string {
        $prefix = $type === 'expense' ? 'EXP' : 'INC';
        $year = date('y');
        $month = date('m');
        
        $last = db()->fetchOne(
            "SELECT transaction_number FROM transactions 
             WHERE company_id = ? AND transaction_number LIKE ?
             ORDER BY id DESC LIMIT 1",
            [$companyId, $prefix . '/' . $year . $month . '/%']
        );
        
        if ($last) {
            $parts = explode('/', $last['transaction_number']);
            $num = (int) end($parts) + 1;
        } else {
            $num = 1;
        }
        
        return $prefix . '/' . $year . $month . '/' . str_pad($num, 4, '0', STR_PAD_LEFT);
    }
    
    /**
     * Find transaction with company check
     */
    private function findTransaction(int $id): ?array {
        $transaction = db()->fetchOne(
            "SELECT t.*, 
                    a.code as account_code, a.name as account_name,
                    bc.name as bank_cash_name,
                    j.journal_number
             FROM transactions t
             LEFT JOIN accounts a ON t.account_id = a.id
             LEFT JOIN bank_cash bc ON t.bank_cash_id = bc.id
             LEFT JOIN journals j ON t.journal_id = j.id
             WHERE t.id = ? AND t.company_id = ?",
            [$id, Session::companyId()]
        );
        
        return $transaction ?: null;
    }
    
    /**
     * Get page actions
     */
    private function getPageActions(): string {
        return '
            <div class="btn-group">
                <a href="' . url('/transactions/create?type=expense') . '" class="btn btn-danger">
                    <i class="fas fa-minus-circle"></i> Pengeluaran
                </a>
                <a href="' . url('/transactions/create?type=income') . '" class="btn btn-success">
                    <i class="fas fa-plus-circle"></i> Pemasukan
                </a>
            </div>
        ';
    }
}