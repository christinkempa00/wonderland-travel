<?php
/**
 * ================================================================
 * TRAVEL MANAGEMENT SYSTEM - Accounting Controller
 * ================================================================
 */

require_once MODELS_PATH . '/Account.php';
require_once MODELS_PATH . '/Journal.php';
require_once MODELS_PATH . '/BankCash.php';

class AccountingController {
    
    // ==================== CHART OF ACCOUNTS ====================
    
    /**
     * List accounts (Chart of Accounts)
     */
    public function accounts(): void {
        $companyId = Session::companyId();
        
        $accounts = Account::getTree($companyId);
        $groupedAccounts = Account::getGroupedByType($companyId);
        
        $data = [
            'pageTitle' => 'Daftar Akun',
            'pageHeader' => true,
            'pageSubtitle' => 'Chart of Accounts',
            'pageActions' => $this->getAccountPageActions(),
            'accounts' => $accounts,
            'groupedAccounts' => $groupedAccounts,
            'accountTypes' => ACCOUNT_TYPES
        ];
        
        render('accounting/accounts', $data);
    }
    
    /**
     * Create account form
     */
    public function createAccount(): void {
        $companyId = Session::companyId();
        
        $data = [
            'pageTitle' => 'Tambah Akun',
            'pageHeader' => true,
            'breadcrumbs' => [
                ['label' => 'Akuntansi', 'url' => '/accounting/accounts'],
                ['label' => 'Tambah Akun']
            ],
            'account' => null,
            'parentAccounts' => Account::getForDropdown($companyId),
            'accountTypes' => ACCOUNT_TYPES,
            'isEdit' => false
        ];
        
        render('accounting/account-form', $data);
    }
    
    /**
     * Store account
     */
    public function storeAccount(): void {
        $companyId = Session::companyId();
        
        $data = [
            'code' => trim($_POST['code'] ?? ''),
            'name' => trim($_POST['name'] ?? ''),
            'type' => $_POST['type'] ?? 'asset',
            'parent_id' => $_POST['parent_id'] ?: null,
            'is_cash_bank' => isset($_POST['is_cash_bank']) ? 1 : 0,
            'is_active' => 1
        ];
        
        // Validate
        if (empty($data['code']) || empty($data['name'])) {
            Session::flash('error', 'Kode dan nama akun wajib diisi.');
            flashInput($_POST);
            redirect('/accounting/accounts/create');
            return;
        }
        
        // Check unique code
        $exists = db()->fetchColumn(
            "SELECT id FROM accounts WHERE company_id = ? AND code = ?",
            [$companyId, $data['code']]
        );
        
        if ($exists) {
            Session::flash('error', 'Kode akun sudah digunakan.');
            flashInput($_POST);
            redirect('/accounting/accounts/create');
            return;
        }
        
        $account = Account::createForCompany($companyId, $data);
        
        logActivity('create', 'account', $account->id, 'account', 'Created account: ' . $account->code);
        
        Session::flash('success', 'Akun berhasil ditambahkan.');
        redirect('/accounting/accounts');
    }
    
    /**
     * Edit account form
     */
    public function editAccount(int $id): void {
        $account = $this->findAccount($id);
        
        if (!$account) {
            Session::flash('error', 'Akun tidak ditemukan.');
            redirect('/accounting/accounts');
            return;
        }
        
        $companyId = Session::companyId();
        
        $data = [
            'pageTitle' => 'Edit Akun',
            'pageHeader' => true,
            'breadcrumbs' => [
                ['label' => 'Akuntansi', 'url' => '/accounting/accounts'],
                ['label' => 'Edit Akun']
            ],
            'account' => $account,
            'parentAccounts' => Account::getForDropdown($companyId),
            'accountTypes' => ACCOUNT_TYPES,
            'isEdit' => true
        ];
        
        render('accounting/account-form', $data);
    }
    
    /**
     * Update account
     */
    public function updateAccount(int $id): void {
        $account = $this->findAccount($id);
        
        if (!$account) {
            Session::flash('error', 'Akun tidak ditemukan.');
            redirect('/accounting/accounts');
            return;
        }
        
        $data = [
            'code' => trim($_POST['code'] ?? ''),
            'name' => trim($_POST['name'] ?? ''),
            'type' => $_POST['type'] ?? 'asset',
            'parent_id' => $_POST['parent_id'] ?: null,
            'is_cash_bank' => isset($_POST['is_cash_bank']) ? 1 : 0
        ];
        
        // Validate
        if (empty($data['code']) || empty($data['name'])) {
            Session::flash('error', 'Kode dan nama akun wajib diisi.');
            flashInput($_POST);
            redirect('/accounting/accounts/' . $id . '/edit');
            return;
        }
        
        // Check unique code (except self)
        $exists = db()->fetchColumn(
            "SELECT id FROM accounts WHERE company_id = ? AND code = ? AND id != ?",
            [Session::companyId(), $data['code'], $id]
        );
        
        if ($exists) {
            Session::flash('error', 'Kode akun sudah digunakan.');
            flashInput($_POST);
            redirect('/accounting/accounts/' . $id . '/edit');
            return;
        }
        
        $account->update($data);
        
        logActivity('update', 'account', $account->id, 'account', 'Updated account: ' . $account->code);
        
        Session::flash('success', 'Akun berhasil diperbarui.');
        redirect('/accounting/accounts');
    }
    
    /**
     * Delete account
     */
    public function destroyAccount(int $id): void {
        $account = $this->findAccount($id);
        
        if (!$account) {
            Session::flash('error', 'Akun tidak ditemukan.');
            redirect('/accounting/accounts');
            return;
        }
        
        if ($account->hasTransactions()) {
            Session::flash('error', 'Akun tidak dapat dihapus karena memiliki transaksi.');
            redirect('/accounting/accounts');
            return;
        }
        
        $code = $account->code;
        $account->delete();
        
        logActivity('delete', 'account', $id, 'account', 'Deleted account: ' . $code);
        
        Session::flash('success', 'Akun berhasil dihapus.');
        redirect('/accounting/accounts');
    }
    
    // ==================== JOURNALS ====================
    
    /**
     * List journals
     */
    public function journals(): void {
        $companyId = Session::companyId();
        $page = (int) ($_GET['page'] ?? 1);
        $perPage = PAGINATION_PER_PAGE;
        
        $filters = [
            'search' => trim($_GET['search'] ?? ''),
            'status' => $_GET['status'] ?? '',
            'journal_type' => $_GET['journal_type'] ?? '',
            'start_date' => $_GET['start_date'] ?? '',
            'end_date' => $_GET['end_date'] ?? ''
        ];
        
        // Build WHERE clause
        $where = "j.company_id = ?";
        $params = [$companyId];
        
        if (!empty($filters['status'])) {
            $where .= " AND j.status = ?";
            $params[] = $filters['status'];
        }
        
        if (!empty($filters['journal_type'])) {
            $where .= " AND j.journal_type = ?";
            $params[] = $filters['journal_type'];
        }
        
        if (!empty($filters['start_date'])) {
            $where .= " AND j.journal_date >= ?";
            $params[] = $filters['start_date'];
        }
        
        if (!empty($filters['end_date'])) {
            $where .= " AND j.journal_date <= ?";
            $params[] = $filters['end_date'];
        }
        
        if (!empty($filters['search'])) {
            $where .= " AND (j.journal_number LIKE ? OR j.description LIKE ? OR j.reference LIKE ?)";
            $search = "%{$filters['search']}%";
            $params[] = $search;
            $params[] = $search;
            $params[] = $search;
        }
        
        // Count total
        $total = (int) db()->fetchColumn("SELECT COUNT(*) FROM journals j WHERE {$where}", $params);
        $offset = ($page - 1) * $perPage;
        
        // Get journals with totals
        $journals = db()->fetchAll(
            "SELECT j.*,
                    COALESCE((SELECT SUM(jd.debit) FROM journal_details jd WHERE jd.journal_id = j.id), 0) as total_debit,
                    COALESCE((SELECT SUM(jd.credit) FROM journal_details jd WHERE jd.journal_id = j.id), 0) as total_credit,
                    u.name as created_by_name
             FROM journals j
             LEFT JOIN users u ON j.created_by = u.id
             WHERE {$where}
             ORDER BY j.journal_date DESC, j.id DESC
             LIMIT {$perPage} OFFSET {$offset}",
            $params
        );
        
        // Get summary by status
        $summary = db()->fetchOne(
            "SELECT 
                COUNT(*) as total_count,
                SUM(CASE WHEN status = 'draft' THEN 1 ELSE 0 END) as draft_count,
                SUM(CASE WHEN status = 'posted' THEN 1 ELSE 0 END) as posted_count,
                SUM(CASE WHEN status = 'void' THEN 1 ELSE 0 END) as void_count
             FROM journals j WHERE j.company_id = ?",
            [$companyId]
        );
        
        // Get breakdown by journal type (posted only)
        $breakdown = db()->fetchAll(
            "SELECT 
                j.journal_type,
                COUNT(*) as count,
                COALESCE(SUM(jd.debit), 0) as total_debit
             FROM journals j
             LEFT JOIN journal_details jd ON jd.journal_id = j.id
             WHERE j.company_id = ? AND j.status = 'posted'
             GROUP BY j.journal_type
             ORDER BY total_debit DESC",
            [$companyId]
        );
        
        // Convert breakdown to associative array
        $breakdownByType = [];
        $totalAllDebit = 0;
        foreach ($breakdown as $b) {
            $breakdownByType[$b['journal_type']] = [
                'count' => (int)$b['count'],
                'total' => (float)$b['total_debit']
            ];
            $totalAllDebit += (float)$b['total_debit'];
        }
        
        $pagination = [
            'total' => $total,
            'per_page' => $perPage,
            'current_page' => $page,
            'last_page' => ceil($total / $perPage) ?: 1,
            'from' => $offset + 1,
            'to' => min($offset + $perPage, $total)
        ];
        
        $data = [
            'pageTitle' => 'Jurnal Umum',
            'pageHeader' => true,
            'pageSubtitle' => 'Daftar Jurnal',
            'pageActions' => $this->getJournalPageActions(),
            'journals' => $journals,
            'pagination' => $pagination,
            'filters' => $filters,
            'summary' => $summary,
            'breakdown' => $breakdownByType,
            'totalAllDebit' => $totalAllDebit
        ];
        
        render('accounting/journals/index', $data);
    }
    
    /**
     * Create journal form
     */
    public function createJournal(): void {
        $companyId = Session::companyId();
        
        $data = [
            'pageTitle' => 'Buat Jurnal',
            'pageHeader' => true,
            'breadcrumbs' => [
                ['label' => 'Jurnal', 'url' => '/accounting/journals'],
                ['label' => 'Buat Jurnal']
            ],
            'journal' => null,
            'details' => [],
            'accounts' => Account::getForDropdown($companyId),
            'isEdit' => false
        ];
        
        render('accounting/journals/form', $data);
    }
    
    /**
     * Store journal
     */
    public function storeJournal(): void {
        $companyId = Session::companyId();
        
        $journalData = [
            'journal_date' => $_POST['journal_date'] ?? date('Y-m-d'),
            'description' => trim($_POST['description'] ?? ''),
            'reference' => trim($_POST['reference'] ?? ''),
            'type' => 'JU'
        ];
        
        $details = $this->parseJournalDetails($_POST);
        
        // Validate
        if (empty($details)) {
            Session::flash('error', 'Minimal satu detail jurnal diperlukan.');
            flashInput($_POST);
            redirect('/accounting/journals/create');
            return;
        }
        
        // Validate balance - using debit/credit columns
        $totalDebit = array_reduce($details, function($sum, $d) {
            return $sum + ($d['debit'] ?? 0);
        }, 0);
        
        $totalCredit = array_reduce($details, function($sum, $d) {
            return $sum + ($d['credit'] ?? 0);
        }, 0);
        
        if (abs($totalDebit - $totalCredit) > 0.01) {
            Session::flash('error', 'Total debit dan kredit harus sama. Debit: ' . formatRupiah($totalDebit) . ', Kredit: ' . formatRupiah($totalCredit));
            flashInput($_POST);
            redirect('/accounting/journals/create');
            return;
        }
        
        if ($totalDebit <= 0) {
            Session::flash('error', 'Total jurnal harus lebih dari 0.');
            flashInput($_POST);
            redirect('/accounting/journals/create');
            return;
        }
        
        try {
            $journal = Journal::createWithDetails($companyId, $journalData, $details);
            
            logActivity('create', 'journal', $journal->id, 'journal', 'Created journal: ' . $journal->journal_number);
            
            Session::flash('success', 'Jurnal berhasil dibuat: ' . $journal->journal_number);
            redirect('/accounting/journals/' . $journal->id);
            
        } catch (Exception $e) {
            Session::flash('error', 'Gagal membuat jurnal: ' . $e->getMessage());
            flashInput($_POST);
            redirect('/accounting/journals/create');
        }
    }
    
    /**
     * Show journal
     */
    public function showJournal(int $id): void {
        $companyId = Session::companyId();
        
        // Get journal with creator name
        $journal = db()->fetchOne(
            "SELECT j.*, u.name as created_by_name 
             FROM journals j 
             LEFT JOIN users u ON j.created_by = u.id
             WHERE j.id = ? AND j.company_id = ?",
            [$id, $companyId]
        );
        
        if (!$journal) {
            Session::flash('error', 'Jurnal tidak ditemukan.');
            redirect('/accounting/journals');
            return;
        }
        
        // Get journal details with account info
        $details = db()->fetchAll(
            "SELECT jd.*, a.code as account_code, a.name as account_name 
             FROM journal_details jd 
             JOIN accounts a ON jd.account_id = a.id 
             WHERE jd.journal_id = ? 
             ORDER BY jd.id",
            [$id]
        );
        
        $data = [
            'pageTitle' => 'Jurnal ' . $journal['journal_number'],
            'pageHeader' => true,
            'breadcrumbs' => [
                ['label' => 'Jurnal', 'url' => '/accounting/journals'],
                ['label' => $journal['journal_number']]
            ],
            'journal' => $journal,
            'details' => $details
        ];
        
        render('accounting/journals/show', $data);
    }
    
    /**
     * Post journal
     */
    public function postJournal(int $id): void {
        $journal = $this->findJournal($id);
        
        if (!$journal) {
            jsonError('Jurnal tidak ditemukan.', 404);
            return;
        }
        
        if ($journal->post()) {
            logActivity('post', 'journal', $journal->id, 'journal', 'Posted journal: ' . $journal->journal_number);
            jsonSuccess(['message' => 'Jurnal berhasil diposting.']);
        } else {
            jsonError('Gagal memposting jurnal.', 400);
        }
    }
    
    /**
     * Void journal
     */
    public function voidJournal(int $id): void {
        $journal = $this->findJournal($id);
        
        if (!$journal) {
            jsonError('Jurnal tidak ditemukan.', 404);
            return;
        }
        
        if ($journal->void()) {
            logActivity('void', 'journal', $journal->id, 'journal', 'Voided journal: ' . $journal->journal_number);
            jsonSuccess(['message' => 'Jurnal berhasil dibatalkan.']);
        } else {
            jsonError('Gagal membatalkan jurnal.', 400);
        }
    }
    
    /**
     * Edit journal form (only for draft status)
     */
    public function editJournal(int $id): void {
        $companyId = Session::companyId();
        
        $journal = db()->fetchOne(
            "SELECT * FROM journals WHERE id = ? AND company_id = ?",
            [$id, $companyId]
        );
        
        if (!$journal) {
            Session::flash('error', 'Jurnal tidak ditemukan.');
            redirect('/accounting/journals');
            return;
        }
        
        if ($journal['status'] !== 'draft') {
            Session::flash('error', 'Hanya jurnal draft yang dapat diedit.');
            redirect('/accounting/journals/' . $id);
            return;
        }
        
        $details = db()->fetchAll(
            "SELECT jd.*, a.code as account_code, a.name as account_name 
             FROM journal_details jd 
             JOIN accounts a ON jd.account_id = a.id 
             WHERE jd.journal_id = ? ORDER BY jd.id",
            [$id]
        );
        
        $data = [
            'pageTitle' => 'Edit Jurnal',
            'pageHeader' => true,
            'breadcrumbs' => [
                ['label' => 'Jurnal', 'url' => '/accounting/journals'],
                ['label' => $journal['journal_number'], 'url' => '/accounting/journals/' . $id],
                ['label' => 'Edit']
            ],
            'journal' => $journal,
            'details' => $details,
            'accounts' => Account::getForDropdown($companyId),
            'isEdit' => true
        ];
        
        render('accounting/journals/form', $data);
    }
    
    /**
     * Update journal (only for draft status)
     */
    public function updateJournal(int $id): void {
        $companyId = Session::companyId();
        
        $journal = db()->fetchOne(
            "SELECT * FROM journals WHERE id = ? AND company_id = ?",
            [$id, $companyId]
        );
        
        if (!$journal) {
            Session::flash('error', 'Jurnal tidak ditemukan.');
            redirect('/accounting/journals');
            return;
        }
        
        if ($journal['status'] !== 'draft') {
            Session::flash('error', 'Hanya jurnal draft yang dapat diedit.');
            redirect('/accounting/journals/' . $id);
            return;
        }
        
        $journalData = [
            'journal_date' => $_POST['journal_date'] ?? date('Y-m-d'),
            'description' => trim($_POST['description'] ?? ''),
            'reference' => trim($_POST['reference'] ?? '')
        ];
        
        $details = $this->parseJournalDetails($_POST);
        
        // Validate
        if (empty($details)) {
            Session::flash('error', 'Minimal satu detail jurnal diperlukan.');
            flashInput($_POST);
            redirect('/accounting/journals/' . $id . '/edit');
            return;
        }
        
        // Validate balance
        $totalDebit = array_reduce($details, fn($sum, $d) => $sum + ($d['debit'] ?? 0), 0);
        $totalCredit = array_reduce($details, fn($sum, $d) => $sum + ($d['credit'] ?? 0), 0);
        
        if (abs($totalDebit - $totalCredit) > 0.01 || $totalDebit <= 0) {
            Session::flash('error', 'Total debit dan kredit harus sama dan lebih dari 0.');
            flashInput($_POST);
            redirect('/accounting/journals/' . $id . '/edit');
            return;
        }
        
        try {
            // Update journal
            db()->update('journals', [
                'journal_date' => $journalData['journal_date'],
                'description' => $journalData['description'],
                'reference' => $journalData['reference'],
                'updated_at' => date('Y-m-d H:i:s')
            ], 'id = ?', [$id]);
            
            // Delete old details and insert new
            db()->query("DELETE FROM journal_details WHERE journal_id = ?", [$id]);
            
            foreach ($details as $detail) {
                db()->insert('journal_details', [
                    'journal_id' => $id,
                    'account_id' => $detail['account_id'],
                    'debit' => $detail['debit'],
                    'credit' => $detail['credit'],
                    'description' => $detail['description']
                ]);
            }
            
            logActivity('update', 'journal', $id, 'journal', 'Updated journal: ' . $journal['journal_number']);
            
            Session::flash('success', 'Jurnal berhasil diperbarui.');
            redirect('/accounting/journals/' . $id);
            
        } catch (Exception $e) {
            Session::flash('error', 'Gagal mengupdate jurnal: ' . $e->getMessage());
            flashInput($_POST);
            redirect('/accounting/journals/' . $id . '/edit');
        }
    }
    
    /**
     * Delete journal (only for draft status) - AJAX
     */
    public function deleteJournal(int $id): void {
        $companyId = Session::companyId();
        
        $journal = db()->fetchOne(
            "SELECT * FROM journals WHERE id = ? AND company_id = ?",
            [$id, $companyId]
        );
        
        if (!$journal) {
            jsonError('Jurnal tidak ditemukan.', 404);
            return;
        }
        
        if ($journal['status'] !== 'draft') {
            jsonError('Hanya jurnal draft yang dapat dihapus.', 400);
            return;
        }
        
        try {
            // Delete details first
            db()->query("DELETE FROM journal_details WHERE journal_id = ?", [$id]);
            
            // Delete journal
            db()->query("DELETE FROM journals WHERE id = ?", [$id]);
            
            logActivity('delete', 'journal', $id, 'journal', 'Deleted journal: ' . $journal['journal_number']);
            
            jsonSuccess(['message' => 'Jurnal berhasil dihapus.']);
            
        } catch (Exception $e) {
            jsonError('Gagal menghapus jurnal: ' . $e->getMessage(), 500);
        }
    }
    
    // ==================== BANK & CASH ====================
    
    /**
     * List bank/cash
     */
    public function bankCash(): void {
        $companyId = Session::companyId();
        
        $bankCash = BankCash::getByCompany($companyId, false);
        
        // Calculate totals - use 'balance' column (not current_balance)
        $totalBalance = array_reduce($bankCash, function($sum, $bc) {
            return $sum + ($bc['is_active'] ? ($bc['balance'] ?? 0) : 0);
        }, 0);
        
        $data = [
            'pageTitle' => 'Kas & Bank',
            'pageHeader' => true,
            'pageSubtitle' => 'Kelola kas dan rekening bank',
            'pageActions' => $this->getBankCashPageActions(),
            'bankCash' => $bankCash,
            'totalBalance' => $totalBalance
        ];
        
        render('accounting/bank-cash', $data);
    }
    
    /**
     * Show bank/cash detail with transactions
     */
    public function showBankCash(int $id): void {
        $bankCash = $this->findBankCash($id);
        
        if (!$bankCash) {
            Session::flash('error', 'Data tidak ditemukan.');
            redirect('/accounting/bank-cash');
            return;
        }
        
        $startDate = $_GET['start_date'] ?? date('Y-m-01');
        $endDate = $_GET['end_date'] ?? date('Y-m-t');
        
        $data = [
            'pageTitle' => $bankCash->name,
            'pageHeader' => true,
            'breadcrumbs' => [
                ['label' => 'Kas & Bank', 'url' => '/accounting/bank-cash'],
                ['label' => $bankCash->name]
            ],
            'bankCash' => $bankCash,
            'transactions' => $bankCash->getTransactions($startDate, $endDate),
            'startDate' => $startDate,
            'endDate' => $endDate
        ];
        
        render('accounting/bank-cash-show', $data);
    }
    
    // ==================== LEDGER ====================
    
    /**
     * Ledger (Buku Besar)
     */
    public function ledger(): void {
        $companyId = Session::companyId();
        
        $accountId = (int) ($_GET['account_id'] ?? 0);
        $startDate = $_GET['start_date'] ?? date('Y-m-01');
        $endDate = $_GET['end_date'] ?? date('Y-m-t');
        
        $accounts = Account::getForDropdown($companyId);
        $transactions = [];
        $account = null;
        $openingBalance = 0;
        
        if ($accountId) {
            $account = Account::find($accountId);
            
            if ($account && $account->company_id == $companyId) {
                // Get opening balance (before start date)
                $openingBalance = $this->getOpeningBalance($account, $startDate);
                
                // Get transactions
                $transactions = $account->getTransactions($startDate, $endDate);
            }
        }
        
        $data = [
            'pageTitle' => 'Buku Besar',
            'pageHeader' => true,
            'pageSubtitle' => 'General Ledger',
            'accounts' => $accounts,
            'selectedAccount' => $accountId,
            'account' => $account,
            'transactions' => $transactions,
            'openingBalance' => $openingBalance,
            'startDate' => $startDate,
            'endDate' => $endDate
        ];
        
        render('accounting/reports/ledger', $data);
    }
    
    /**
     * Create bank/cash form
     */
    public function createBankCash(): void {
        $companyId = Session::companyId();
        
        $data = [
            'pageTitle' => 'Tambah Kas/Bank',
            'pageHeader' => true,
            'breadcrumbs' => [
                ['label' => 'Kas & Bank', 'url' => '/accounting/bank-cash'],
                ['label' => 'Tambah']
            ],
            'bankCash' => null,
            'accounts' => Account::getCashBankAccounts($companyId),
            'isEdit' => false
        ];
        
        render('accounting/bank-cash-form', $data);
    }
    
    /**
     * Store bank/cash
     */
    public function storeBankCash(): void {
        $companyId = Session::companyId();
        
        $data = [
            'name' => trim($_POST['name'] ?? ''),
            'type' => $_POST['type'] ?? 'cash',
            'bank_name' => trim($_POST['bank_name'] ?? ''),
            'account_number' => trim($_POST['account_number'] ?? ''),
            'account_holder' => trim($_POST['account_holder'] ?? ''),
            'account_id' => $_POST['account_id'] ?: null,
            'balance' => floatval($_POST['balance'] ?? 0),
            'is_default' => isset($_POST['is_default']) ? 1 : 0,
            'is_active' => 1
        ];
        
        if (empty($data['name'])) {
            Session::flash('error', 'Nama wajib diisi.');
            flashInput($_POST);
            redirect('/accounting/bank-cash/create');
            return;
        }
        
        $bankCash = BankCash::createForCompany($companyId, $data);
        
        logActivity('create', 'bank_cash', $bankCash->id, 'bank_cash', 'Created: ' . $bankCash->name);
        
        Session::flash('success', 'Kas/Bank berhasil ditambahkan.');
        redirect('/accounting/bank-cash');
    }
    
    /**
     * Edit bank/cash form
     */
    public function editBankCash(int $id): void {
        $bankCash = $this->findBankCash($id);
        
        if (!$bankCash) {
            Session::flash('error', 'Data tidak ditemukan.');
            redirect('/accounting/bank-cash');
            return;
        }
        
        $companyId = Session::companyId();
        
        $data = [
            'pageTitle' => 'Edit Kas/Bank',
            'pageHeader' => true,
            'breadcrumbs' => [
                ['label' => 'Kas & Bank', 'url' => '/accounting/bank-cash'],
                ['label' => 'Edit']
            ],
            'bankCash' => $bankCash,
            'accounts' => Account::getCashBankAccounts($companyId),
            'isEdit' => true
        ];
        
        render('accounting/bank-cash-form', $data);
    }
    
    /**
     * Update bank/cash
     */
    public function updateBankCash(int $id): void {
        $bankCash = $this->findBankCash($id);
        
        if (!$bankCash) {
            Session::flash('error', 'Data tidak ditemukan.');
            redirect('/accounting/bank-cash');
            return;
        }
        
        $bankCash->name = trim($_POST['name'] ?? '');
        $bankCash->type = $_POST['type'] ?? 'cash';
        $bankCash->bank_name = trim($_POST['bank_name'] ?? '');
        $bankCash->account_number = trim($_POST['account_number'] ?? '');
        $bankCash->account_holder = trim($_POST['account_holder'] ?? '');
        $bankCash->account_id = $_POST['account_id'] ?: null;
        $bankCash->is_default = isset($_POST['is_default']) ? 1 : 0;
        
        if (empty($bankCash->name)) {
            Session::flash('error', 'Nama wajib diisi.');
            redirect('/accounting/bank-cash/' . $id . '/edit');
            return;
        }
        
        $bankCash->save();
        
        logActivity('update', 'bank_cash', $bankCash->id, 'bank_cash', 'Updated: ' . $bankCash->name);
        
        Session::flash('success', 'Kas/Bank berhasil diperbarui.');
        redirect('/accounting/bank-cash');
    }
    
    /**
     * Delete bank/cash
     */
    public function destroyBankCash(int $id): void {
        $bankCash = $this->findBankCash($id);
        
        if (!$bankCash) {
            if (isAjax()) {
                jsonError('Data tidak ditemukan.', 404);
            }
            Session::flash('error', 'Data tidak ditemukan.');
            redirect('/accounting/bank-cash');
            return;
        }
        
        $name = $bankCash->name;
        $bankCash->delete();
        
        logActivity('delete', 'bank_cash', $id, 'bank_cash', 'Deleted: ' . $name);
        
        if (isAjax()) {
            jsonSuccess(['message' => 'Kas/Bank berhasil dihapus.']);
        }
        
        Session::flash('success', 'Kas/Bank berhasil dihapus.');
        redirect('/accounting/bank-cash');
    }
    
    // ==================== REPORTS ====================
    
    /**
     * Reports index
     */
    public function reports(): void {
        $data = [
            'pageTitle' => 'Laporan Keuangan',
            'pageHeader' => true,
            'pageSubtitle' => 'Pilih laporan yang ingin dilihat'
        ];
        
        render('accounting/reports/index', $data);
    }
    
    /**
     * Trial Balance
     */
    public function trialBalance(): void {
        $companyId = Session::companyId();
        
        $endDate = $_GET['end_date'] ?? date('Y-m-d');
        
        // Get all accounts with their balances
        $trialBalance = db()->fetchAll(
            "SELECT a.code, a.name, a.type, a.normal_balance,
                    COALESCE((
                        SELECT SUM(jd.debit)
                        FROM journal_details jd
                        JOIN journals j ON jd.journal_id = j.id
                        WHERE jd.account_id = a.id AND j.status = 'posted' AND j.journal_date <= ?
                    ), 0) as total_debit,
                    COALESCE((
                        SELECT SUM(jd.credit)
                        FROM journal_details jd
                        JOIN journals j ON jd.journal_id = j.id
                        WHERE jd.account_id = a.id AND j.status = 'posted' AND j.journal_date <= ?
                    ), 0) as total_credit
             FROM accounts a
             WHERE a.company_id = ? AND a.is_active = 1
             ORDER BY a.code",
            [$endDate, $endDate, $companyId]
        );
        
        // Process balances
        $processed = [];
        $totalDebit = 0;
        $totalCredit = 0;
        
        foreach ($trialBalance as $acc) {
            $netDebit = (float)$acc['total_debit'];
            $netCredit = (float)$acc['total_credit'];
            $balance = $netDebit - $netCredit;
            
            // Skip zero balance accounts
            if (abs($balance) < 0.01) {
                continue;
            }
            
            // Determine which column to show balance
            $debit = 0;
            $credit = 0;
            
            if ($balance > 0) {
                $debit = $balance;
            } else {
                $credit = abs($balance);
            }
            
            $processed[] = [
                'code' => $acc['code'],
                'name' => $acc['name'],
                'type' => $acc['type'],
                'debit' => $debit,
                'credit' => $credit
            ];
            
            $totalDebit += $debit;
            $totalCredit += $credit;
        }
        
        $data = [
            'pageTitle' => 'Neraca Saldo',
            'pageHeader' => true,
            'breadcrumbs' => [
                ['label' => 'Laporan', 'url' => '/accounting/reports'],
                ['label' => 'Neraca Saldo']
            ],
            'trialBalance' => $processed,
            'totalDebit' => $totalDebit,
            'totalCredit' => $totalCredit,
            'endDate' => $endDate,
            'isBalanced' => abs($totalDebit - $totalCredit) < 0.01
        ];
        
        render('accounting/reports/trial-balance', $data);
    }
    
    /**
     * Income Statement (Laba Rugi)
     */
    public function incomeStatement(): void {
        $companyId = Session::companyId();
        
        $startDate = $_GET['start_date'] ?? date('Y-m-01');
        $endDate = $_GET['end_date'] ?? date('Y-m-d');
        
        // Get revenue accounts (credit normal balance)
        $revenueAccounts = db()->fetchAll(
            "SELECT a.*, 
                    COALESCE((
                        SELECT SUM(jd.credit) - SUM(jd.debit)
                        FROM journal_details jd
                        JOIN journals j ON jd.journal_id = j.id
                        WHERE jd.account_id = a.id AND j.status = 'posted'
                        AND j.journal_date BETWEEN ? AND ?
                    ), 0) as balance
             FROM accounts a
             WHERE a.company_id = ? AND a.type = 'revenue' AND a.is_active = 1
             ORDER BY a.code",
            [$startDate, $endDate, $companyId]
        );
        
        // Get HPP accounts (code 50xx) - Beban Pokok Penjualan
        $hppAccounts = db()->fetchAll(
            "SELECT a.*, 
                    COALESCE((
                        SELECT SUM(jd.debit) - SUM(jd.credit)
                        FROM journal_details jd
                        JOIN journals j ON jd.journal_id = j.id
                        WHERE jd.account_id = a.id AND j.status = 'posted'
                        AND j.journal_date BETWEEN ? AND ?
                    ), 0) as balance
             FROM accounts a
             WHERE a.company_id = ? AND a.type = 'expense' AND a.is_active = 1
             AND a.code LIKE '50%'
             ORDER BY a.code",
            [$startDate, $endDate, $companyId]
        );
        
        // Get operational expense accounts (non-HPP, code bukan 50xx)
        $operationalAccounts = db()->fetchAll(
            "SELECT a.*, 
                    COALESCE((
                        SELECT SUM(jd.debit) - SUM(jd.credit)
                        FROM journal_details jd
                        JOIN journals j ON jd.journal_id = j.id
                        WHERE jd.account_id = a.id AND j.status = 'posted'
                        AND j.journal_date BETWEEN ? AND ?
                    ), 0) as balance
             FROM accounts a
             WHERE a.company_id = ? AND a.type = 'expense' AND a.is_active = 1
             AND a.code NOT LIKE '50%'
             ORDER BY a.code",
            [$startDate, $endDate, $companyId]
        );
        
        // Fallback: Jika HPP journals belum ada, ambil dari data orders langsung
        $totalHppFromJournal = array_sum(array_column($hppAccounts, 'balance'));
        
        $hppFromOrders = 0;
        if ($totalHppFromJournal < 1) {
            // HPP belum ada di jurnal, hitung dari orders
            $hppResult = db()->fetchOne(
                "SELECT COALESCE(SUM(oi.base_price * oi.quantity * COALESCE(oi.num_days, 1)), 0) as total
                 FROM order_items oi
                 JOIN orders o ON oi.order_id = o.id
                 WHERE o.company_id = ?
                 AND o.status NOT IN ('cancelled', 'draft')
                 AND o.order_date BETWEEN ? AND ?",
                [$companyId, $startDate, $endDate]
            );
            $hppFromOrders = (float)($hppResult['total'] ?? 0);
        }
        
        $totalRevenue = array_sum(array_column($revenueAccounts, 'balance'));
        $totalHpp = $totalHppFromJournal > 0 ? $totalHppFromJournal : $hppFromOrders;
        $grossProfit = $totalRevenue - $totalHpp;
        $totalOperational = array_sum(array_column($operationalAccounts, 'balance'));
        $netIncome = $grossProfit - $totalOperational;
        
        // All expense accounts combined (untuk backward compatibility)
        $expenseAccounts = array_merge($hppAccounts, $operationalAccounts);
        $totalExpense = $totalHpp + $totalOperational;
        
        $data = [
            'pageTitle' => 'Laporan Laba Rugi',
            'pageHeader' => true,
            'breadcrumbs' => [
                ['label' => 'Laporan', 'url' => '/accounting/reports'],
                ['label' => 'Laba Rugi']
            ],
            'revenueAccounts' => $revenueAccounts,
            'hppAccounts' => $hppAccounts,
            'operationalAccounts' => $operationalAccounts,
            'expenseAccounts' => $expenseAccounts,
            'totalRevenue' => $totalRevenue,
            'totalHpp' => $totalHpp,
            'grossProfit' => $grossProfit,
            'totalOperational' => $totalOperational,
            'totalExpense' => $totalExpense,
            'netIncome' => $netIncome,
            'startDate' => $startDate,
            'endDate' => $endDate,
            'hppFromOrders' => $hppFromOrders, // Flag apakah HPP dari orders
            'hppSource' => $totalHppFromJournal > 0 ? 'journal' : 'orders'
        ];
        
        render('accounting/reports/income-statement', $data);
    }
    
    /**
     * Balance Sheet (Neraca)
     */
    public function balanceSheet(): void {
        $companyId = Session::companyId();
        
        $endDate = $_GET['end_date'] ?? date('Y-m-d');
        
        // Helper function to get balance
        $getAccountsWithBalance = function($type, $normalBalance) use ($companyId, $endDate) {
            $sign = $normalBalance === 'debit' ? 'SUM(jd.debit) - SUM(jd.credit)' : 'SUM(jd.credit) - SUM(jd.debit)';
            return db()->fetchAll(
                "SELECT a.*, 
                        COALESCE((
                            SELECT {$sign}
                            FROM journal_details jd
                            JOIN journals j ON jd.journal_id = j.id
                            WHERE jd.account_id = a.id AND j.status = 'posted'
                            AND j.journal_date <= ?
                        ), 0) as balance
                 FROM accounts a
                 WHERE a.company_id = ? AND a.type = ? AND a.is_active = 1
                 ORDER BY a.code",
                [$endDate, $companyId, $type]
            );
        };
        
        $assets = $getAccountsWithBalance('asset', 'debit');
        $liabilities = $getAccountsWithBalance('liability', 'credit');
        $equity = $getAccountsWithBalance('equity', 'credit');
        
        // Calculate retained earnings (Laba Ditahan)
        $retainedEarnings = $this->calculateRetainedEarnings($companyId, $endDate);
        
        $totalAssets = array_sum(array_column($assets, 'balance'));
        $totalLiabilities = array_sum(array_column($liabilities, 'balance'));
        $totalEquity = array_sum(array_column($equity, 'balance')) + $retainedEarnings;
        
        $data = [
            'pageTitle' => 'Neraca',
            'pageHeader' => true,
            'breadcrumbs' => [
                ['label' => 'Laporan', 'url' => '/accounting/reports'],
                ['label' => 'Neraca']
            ],
            'assets' => $assets,
            'liabilities' => $liabilities,
            'equity' => $equity,
            'retainedEarnings' => $retainedEarnings,
            'totalAssets' => $totalAssets,
            'totalLiabilities' => $totalLiabilities,
            'totalEquity' => $totalEquity,
            'endDate' => $endDate
        ];
        
        render('accounting/reports/balance-sheet', $data);
    }
    
    /**
     * Calculate retained earnings (Laba Ditahan)
     */
    private function calculateRetainedEarnings(int $companyId, string $endDate): float {
        // Revenue - Expense = Net Income (as retained earnings)
        $revenue = db()->fetchColumn(
            "SELECT COALESCE(SUM(jd.credit) - SUM(jd.debit), 0)
             FROM journal_details jd
             JOIN journals j ON jd.journal_id = j.id
             JOIN accounts a ON jd.account_id = a.id
             WHERE a.company_id = ? AND a.type = 'revenue' AND j.status = 'posted' AND j.journal_date <= ?",
            [$companyId, $endDate]
        );
        
        $expense = db()->fetchColumn(
            "SELECT COALESCE(SUM(jd.debit) - SUM(jd.credit), 0)
             FROM journal_details jd
             JOIN journals j ON jd.journal_id = j.id
             JOIN accounts a ON jd.account_id = a.id
             WHERE a.company_id = ? AND a.type = 'expense' AND j.status = 'posted' AND j.journal_date <= ?",
            [$companyId, $endDate]
        );
        
        return (float)$revenue - (float)$expense;
    }
    
    /**
     * Receivables Report (Laporan Piutang)
     */
    public function receivables(): void {
        $companyId = Session::companyId();
        
        $endDate = $_GET['end_date'] ?? date('Y-m-d');
        $agingDays = (int)($_GET['aging_days'] ?? 0);
        
        // Base query - orders with outstanding receivables
        $sql = "SELECT 
                    o.id,
                    o.order_number,
                    o.event_name,
                    o.order_date,
                    o.total_final_price,
                    o.paid_amount,
                    (o.total_final_price - COALESCE(o.paid_amount, 0)) as outstanding,
                    o.payment_status,
                    DATEDIFF(?, o.order_date) as days_overdue,
                    c.name as client_name
                FROM orders o
                LEFT JOIN clients c ON o.client_id = c.id
                WHERE o.company_id = ?
                AND o.status != 'cancelled'
                AND (o.total_final_price - COALESCE(o.paid_amount, 0)) > 0";
        
        $params = [$endDate, $companyId];
        
        // Filter by aging
        if ($agingDays > 0) {
            $sql .= " AND DATEDIFF(?, o.order_date) >= ?";
            $params[] = $endDate;
            $params[] = $agingDays;
        }
        
        $sql .= " ORDER BY o.order_date DESC";
        
        $receivables = db()->fetchAll($sql, $params);
        
        // Calculate totals and aging summary
        $totalReceivables = 0;
        $totalOverdue = 0;
        $aging = [
            'current' => 0,
            '31_60' => 0,
            '61_90' => 0,
            'over_90' => 0
        ];
        
        foreach ($receivables as $r) {
            $outstanding = (float)$r['outstanding'];
            $daysOverdue = (int)$r['days_overdue'];
            
            $totalReceivables += $outstanding;
            
            if ($daysOverdue <= 30) {
                $aging['current'] += $outstanding;
            } elseif ($daysOverdue <= 60) {
                $aging['31_60'] += $outstanding;
                $totalOverdue += $outstanding;
            } elseif ($daysOverdue <= 90) {
                $aging['61_90'] += $outstanding;
                $totalOverdue += $outstanding;
            } else {
                $aging['over_90'] += $outstanding;
                $totalOverdue += $outstanding;
            }
        }
        
        $data = [
            'pageTitle' => 'Laporan Piutang',
            'pageHeader' => true,
            'breadcrumbs' => [
                ['label' => 'Laporan', 'url' => '/accounting/reports'],
                ['label' => 'Piutang']
            ],
            'receivables' => $receivables,
            'totalReceivables' => $totalReceivables,
            'totalOverdue' => $totalOverdue,
            'aging' => $aging,
            'endDate' => $endDate,
            'agingDays' => $agingDays
        ];
        
        render('accounting/reports/receivables', $data);
    }
    
    /**
     * Cash Flow Report (Laporan Arus Kas)
     */
    public function cashFlow(): void {
        $companyId = Session::companyId();
        
        $startDate = $_GET['start_date'] ?? date('Y-m-01');
        $endDate = $_GET['end_date'] ?? date('Y-m-d');
        
        // Get all cash/bank transactions
        // NOTE: Tidak pakai JOIN bank_cash karena multiple bank_cash bisa punya
        // account_id sama, yang menyebabkan duplikasi baris
        $transactions = db()->fetchAll(
            "SELECT 
                j.journal_date,
                j.journal_number,
                j.description,
                j.reference,
                j.journal_type,
                jd.debit,
                jd.credit,
                a.name as account_name,
                (SELECT bc.name FROM bank_cash bc WHERE bc.account_id = a.id LIMIT 1) as bank_cash_name
             FROM journal_details jd
             JOIN journals j ON jd.journal_id = j.id
             JOIN accounts a ON jd.account_id = a.id
             WHERE j.company_id = ? 
             AND j.status = 'posted'
             AND j.journal_date BETWEEN ? AND ?
             AND a.is_cash_bank = 1
             ORDER BY j.journal_date, j.id",
            [$companyId, $startDate, $endDate]
        );
        
        // Calculate opening balance (all cash/bank accounts)
        $openingBalance = db()->fetchColumn(
            "SELECT COALESCE(SUM(jd.debit) - SUM(jd.credit), 0)
             FROM journal_details jd
             JOIN journals j ON jd.journal_id = j.id
             JOIN accounts a ON jd.account_id = a.id
             WHERE j.company_id = ? 
             AND j.status = 'posted'
             AND j.journal_date < ?
             AND a.is_cash_bank = 1",
            [$companyId, $startDate]
        );
        
        // Categorize transactions
        $inflows = [];
        $outflows = [];
        $totalInflow = 0;
        $totalOutflow = 0;
        
        foreach ($transactions as $trx) {
            $debit = (float)$trx['debit'];
            $credit = (float)$trx['credit'];
            
            if ($debit > 0) {
                // Cash inflow
                $inflows[] = $trx;
                $totalInflow += $debit;
            }
            if ($credit > 0) {
                // Cash outflow
                $outflows[] = $trx;
                $totalOutflow += $credit;
            }
        }
        
        $netCashFlow = $totalInflow - $totalOutflow;
        $closingBalance = (float)$openingBalance + $netCashFlow;
        
        $data = [
            'pageTitle' => 'Laporan Arus Kas',
            'pageHeader' => true,
            'breadcrumbs' => [
                ['label' => 'Laporan', 'url' => '/accounting/reports'],
                ['label' => 'Arus Kas']
            ],
            'transactions' => $transactions,
            'inflows' => $inflows,
            'outflows' => $outflows,
            'totalInflow' => $totalInflow,
            'totalOutflow' => $totalOutflow,
            'netCashFlow' => $netCashFlow,
            'openingBalance' => (float)$openingBalance,
            'closingBalance' => $closingBalance,
            'startDate' => $startDate,
            'endDate' => $endDate
        ];
        
        render('accounting/reports/cash-flow', $data);
    }
    
    // ==================== HELPER METHODS ====================
    
    private function findAccount(int $id): ?Account {
        $account = Account::find($id);
        if (!$account || $account->company_id != Session::companyId()) {
            return null;
        }
        return $account;
    }
    
    private function findJournal(int $id): ?Journal {
        $journal = Journal::find($id);
        if (!$journal || $journal->company_id != Session::companyId()) {
            return null;
        }
        return $journal;
    }
    
    private function findBankCash(int $id): ?BankCash {
        $bankCash = BankCash::find($id);
        if (!$bankCash || $bankCash->company_id != Session::companyId()) {
            return null;
        }
        return $bankCash;
    }
    
    private function parseJournalDetails(array $post): array {
        $details = [];
        
        if (empty($post['details']) || !is_array($post['details'])) {
            return $details;
        }
        
        foreach ($post['details'] as $detail) {
            if (empty($detail['account_id'])) {
                continue;
            }
            
            // Support both formats:
            // Format 1 (new): debit and credit as separate fields
            // Format 2 (old): amount with position field
            
            $debit = 0;
            $credit = 0;
            
            if (isset($detail['debit']) || isset($detail['credit'])) {
                // New format: debit/credit separate
                $debit = parseRupiah($detail['debit'] ?? 0);
                $credit = parseRupiah($detail['credit'] ?? 0);
            } elseif (isset($detail['amount'])) {
                // Old format: amount with position
                $amount = parseRupiah($detail['amount']);
                $position = $detail['position'] ?? 'debit';
                if ($position === 'debit') {
                    $debit = $amount;
                } else {
                    $credit = $amount;
                }
            }
            
            // Skip if both are zero
            if ($debit <= 0 && $credit <= 0) {
                continue;
            }
            
            $details[] = [
                'account_id' => (int) $detail['account_id'],
                'debit' => $debit,
                'credit' => $credit,
                'description' => trim($detail['description'] ?? '')
            ];
        }
        
        return $details;
    }
    
    private function getOpeningBalance(Account $account, string $beforeDate): float {
        $result = db()->fetchOne(
            "SELECT 
                COALESCE(SUM(jd.debit), 0) as total_debit,
                COALESCE(SUM(jd.credit), 0) as total_credit
             FROM journal_details jd
             JOIN journals j ON jd.journal_id = j.id
             WHERE jd.account_id = ? AND j.status = 'posted' AND j.journal_date < ?",
            [$account->id, $beforeDate]
        );
        
        $debit = (float)($result['total_debit'] ?? 0);
        $credit = (float)($result['total_credit'] ?? 0);
        
        if ($account->normal_balance === 'debit') {
            return $debit - $credit;
        }
        return $credit - $debit;
    }
    
    private function getAccountPageActions(): string {
        if (!Session::can('accounting.create')) return '';
        return '<a href="' . url('/accounting/accounts/create') . '" class="btn btn-primary">
            <i class="fas fa-plus"></i> Tambah Akun
        </a>';
    }
    
    private function getJournalPageActions(): string {
        if (!Session::can('accounting.create')) return '';
        return '<a href="' . url('/accounting/journals/create') . '" class="btn btn-primary">
            <i class="fas fa-plus"></i> Buat Jurnal
        </a>';
    }
    
    private function getBankCashPageActions(): string {
        if (!Session::can('accounting.create')) return '';
        return '<a href="' . url('/accounting/bank-cash/create') . '" class="btn btn-primary">
            <i class="fas fa-plus"></i> Tambah Kas/Bank
        </a>';
    }
    
    // ==================== FINANCIAL DASHBOARD ====================
    
    /**
     * Financial Dashboard - Ringkasan Keuangan
     */
    public function dashboard(): void {
        $companyId = Session::companyId();
        
        // Load AccountingHelper
        $helperPath = HELPERS_PATH . '/AccountingHelper.php';
        if (file_exists($helperPath)) {
            require_once $helperPath;
        }
        
        // Get dashboard data
        $dashboard = [];
        
        if (function_exists('getFinancialDashboard')) {
            $dashboard = getFinancialDashboard($companyId);
        } else {
            // Fallback jika fungsi belum ada
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
                $cashResult = db()->fetchOne(
                    "SELECT COALESCE(SUM(balance), 0) as total FROM bank_cash WHERE company_id = ? AND is_active = 1",
                    [$companyId]
                );
                $dashboard['cash_balance'] = (float)($cashResult['total'] ?? 0);
                
                $dashboard['cash_accounts'] = db()->fetchAll(
                    "SELECT id, name, type, balance FROM bank_cash WHERE company_id = ? AND is_active = 1 ORDER BY type, name",
                    [$companyId]
                );
            } catch (Exception $e) {}
        }
        
        $data = [
            'pageTitle' => 'Dashboard Keuangan',
            'pageHeader' => true,
            'pageSubtitle' => 'Ringkasan kondisi keuangan',
            'dashboard' => $dashboard
        ];
        
        render('accounting/dashboard', $data);
    }
}