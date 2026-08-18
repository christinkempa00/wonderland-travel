<?php
/**
 * ================================================================
 * TRAVEL MANAGEMENT SYSTEM - Loan Controller
 * ================================================================
 * 
 * File: /controllers/LoanController.php
 * 
 * FIXED: Menghapus pembagian /2 yang menyebabkan nilai tampil setengah
 */

// Load accounting helper
$accountingHelperPath = HELPERS_PATH . '/AccountingHelper.php';
if (file_exists($accountingHelperPath)) {
    require_once $accountingHelperPath;
}

class LoanController {
    
    /**
     * List all loan transactions
     */
    public function index(): void {
        $companyId = Session::companyId();
        
        $filter = $_GET['filter'] ?? 'all';
        $search = trim($_GET['search'] ?? '');
        $page = max(1, (int)($_GET['page'] ?? 1));
        $perPage = 20;
        
        // Build query
        $where = "j.company_id = ? AND j.status = 'posted'";
        $params = [$companyId];
        
        if ($filter === 'received') {
            $where .= " AND j.description LIKE 'Pinjaman dari%'";
        } elseif ($filter === 'repaid') {
            $where .= " AND j.description LIKE 'Bayar pinjaman%'";
        } else {
            $where .= " AND (j.description LIKE 'Pinjaman dari%' OR j.description LIKE 'Bayar pinjaman%')";
        }
        
        if (!empty($search)) {
            $where .= " AND (j.description LIKE ? OR j.reference LIKE ? OR j.journal_number LIKE ?)";
            $params[] = "%{$search}%";
            $params[] = "%{$search}%";
            $params[] = "%{$search}%";
        }
        
        // Count total
        $total = (int) db()->fetchColumn(
            "SELECT COUNT(*) FROM journals j WHERE {$where}",
            $params
        );
        
        $offset = ($page - 1) * $perPage;
        
        // Get transactions
        // FIXED: Ambil nilai dari akun yang tepat, bukan SUM semua
        $transactions = db()->fetchAll(
            "SELECT j.*,
                    (SELECT jd.debit FROM journal_details jd 
                     JOIN accounts a ON jd.account_id = a.id 
                     WHERE jd.journal_id = j.id AND a.is_cash_bank = 1 
                     LIMIT 1) as cash_debit,
                    (SELECT jd.credit FROM journal_details jd 
                     JOIN accounts a ON jd.account_id = a.id 
                     WHERE jd.journal_id = j.id AND a.is_cash_bank = 1 
                     LIMIT 1) as cash_credit,
                    CASE 
                        WHEN j.description LIKE 'Pinjaman dari%' THEN 'received'
                        WHEN j.description LIKE 'Bayar pinjaman%' THEN 'repaid'
                        ELSE 'other'
                    END as transaction_type
             FROM journals j
             WHERE {$where}
             ORDER BY j.journal_date DESC, j.id DESC
             LIMIT {$perPage} OFFSET {$offset}",
            $params
        );
        
        // Calculate summary
        $summary = $this->getLoanSummary($companyId);
        
        // Get outstanding loans per lender
        $outstandingLoans = $this->getOutstandingLoans($companyId);
        
        $pagination = [
            'total' => $total,
            'per_page' => $perPage,
            'current_page' => $page,
            'last_page' => ceil($total / $perPage) ?: 1,
            'from' => $total > 0 ? $offset + 1 : 0,
            'to' => min($offset + $perPage, $total)
        ];
        
        $data = [
            'pageTitle' => 'Pinjaman',
            'pageHeader' => true,
            'pageSubtitle' => 'Kelola pinjaman dari perorangan',
            'pageActions' => $this->getPageActions(),
            'transactions' => $transactions,
            'summary' => $summary,
            'outstandingLoans' => $outstandingLoans,
            'filter' => $filter,
            'search' => $search,
            'pagination' => $pagination
        ];
        
        render('accounting/loans/index', $data);
    }
    
    /**
     * Form terima pinjaman
     */
    public function receive(): void {
        $companyId = Session::companyId();
        
        // Get bank/cash options
        $bankCashOptions = $this->getBankCashOptions($companyId);
        
        // Get lender suggestions from existing transactions
        $lenderSuggestions = $this->getLenderSuggestions($companyId);
        
        $data = [
            'pageTitle' => 'Terima Pinjaman',
            'pageHeader' => true,
            'breadcrumbs' => [
                ['label' => 'Pinjaman', 'url' => '/loans'],
                ['label' => 'Terima Pinjaman']
            ],
            'bankCashOptions' => $bankCashOptions,
            'lenderSuggestions' => $lenderSuggestions
        ];
        
        render('accounting/loans/receive', $data);
    }
    
    /**
     * Store terima pinjaman
     */
    public function storeReceive(): void {
        $companyId = Session::companyId();
        
        $errors = [];
        
        $lenderName = trim($_POST['lender_name'] ?? '');
        $amount = $this->parseRupiah($_POST['amount'] ?? '0');
        $bankCashId = !empty($_POST['bank_cash_id']) ? (int)$_POST['bank_cash_id'] : null;
        $transactionDate = $_POST['transaction_date'] ?? date('Y-m-d');
        $notes = trim($_POST['notes'] ?? '');
        
        // Auto-generate reference: LOAN-YYYYMMDD-XXXX
        $reference = 'LOAN-' . date('Ymd') . '-' . strtoupper(substr(uniqid(), -4));
        
        if (empty($lenderName)) {
            $errors[] = 'Nama pemberi pinjaman wajib diisi.';
        }
        
        if ($amount <= 0) {
            $errors[] = 'Jumlah pinjaman harus lebih dari 0.';
        }
        
        if (empty($bankCashId)) {
            $errors[] = 'Pilih Kas/Bank tujuan.';
        }
        
        if (!empty($errors)) {
            Session::flash('error', implode('<br>', $errors));
            flashInput($_POST);
            redirect('/loans/receive');
            return;
        }
        
        if (!function_exists('createLoanReceivedJournal')) {
            Session::flash('error', 'Fungsi akuntansi tidak tersedia. Pastikan AccountingHelper.php sudah di-update.');
            flashInput($_POST);
            redirect('/loans/receive');
            return;
        }
        
        $result = createLoanReceivedJournal(
            $companyId,
            $amount,
            $lenderName,
            $bankCashId,
            $reference,
            $notes,
            $transactionDate
        );
        
        if ($result['success']) {
            Session::flash('success', "Pinjaman dari {$lenderName} sebesar Rp " . number_format($amount, 0, ',', '.') . " berhasil dicatat.");
            redirect('/loans');
        } else {
            Session::flash('error', $result['message']);
            flashInput($_POST);
            redirect('/loans/receive');
        }
    }
    
    /**
     * Form bayar pinjaman - PILIH DARI DAFTAR PINJAMAN EXISTING
     */
    public function repay(): void {
        $companyId = Session::companyId();
        
        // Get bank/cash options
        $bankCashOptions = $this->getBankCashOptions($companyId);
        
        // Get outstanding loans per lender - UNTUK DIPILIH
        $outstandingLoans = $this->getOutstandingLoans($companyId);
        
        // Get current total loan balance
        $loanBalance = 0;
        if (function_exists('getTotalLoanPayable')) {
            $loanBalance = getTotalLoanPayable($companyId);
        }
        
        $data = [
            'pageTitle' => 'Bayar Pinjaman',
            'pageHeader' => true,
            'breadcrumbs' => [
                ['label' => 'Pinjaman', 'url' => '/loans'],
                ['label' => 'Bayar Pinjaman']
            ],
            'bankCashOptions' => $bankCashOptions,
            'outstandingLoans' => $outstandingLoans,
            'loanBalance' => $loanBalance
        ];
        
        render('accounting/loans/repay', $data);
    }
    
    /**
     * Store bayar pinjaman
     */
    public function storeRepay(): void {
        $companyId = Session::companyId();
        
        $errors = [];
        
        $lenderName = trim($_POST['lender_name'] ?? '');
        $amount = $this->parseRupiah($_POST['amount'] ?? '0');
        $bankCashId = !empty($_POST['bank_cash_id']) ? (int)$_POST['bank_cash_id'] : null;
        $transactionDate = $_POST['transaction_date'] ?? date('Y-m-d');
        $notes = trim($_POST['notes'] ?? '');
        
        // Auto-generate reference: PAY-YYYYMMDD-XXXX
        $reference = 'PAY-' . date('Ymd') . '-' . strtoupper(substr(uniqid(), -4));
        
        if (empty($lenderName)) {
            $errors[] = 'Pilih pemberi pinjaman.';
        }
        
        if ($amount <= 0) {
            $errors[] = 'Jumlah pembayaran harus lebih dari 0.';
        }
        
        if (empty($bankCashId)) {
            $errors[] = 'Pilih Kas/Bank sumber pembayaran.';
        }
        
        if (!empty($errors)) {
            Session::flash('error', implode('<br>', $errors));
            flashInput($_POST);
            redirect('/loans/repay');
            return;
        }
        
        if (!function_exists('createLoanRepaymentJournal')) {
            Session::flash('error', 'Fungsi akuntansi tidak tersedia. Pastikan AccountingHelper.php sudah di-update.');
            flashInput($_POST);
            redirect('/loans/repay');
            return;
        }
        
        $result = createLoanRepaymentJournal(
            $companyId,
            $amount,
            $lenderName,
            $bankCashId,
            $reference,
            $notes,
            $transactionDate
        );
        
        if ($result['success']) {
            Session::flash('success', "Pembayaran pinjaman ke {$lenderName} sebesar Rp " . number_format($amount, 0, ',', '.') . " berhasil dicatat.");
            redirect('/loans');
        } else {
            Session::flash('error', $result['message']);
            flashInput($_POST);
            redirect('/loans/repay');
        }
    }
    
    /**
     * Void loan transaction
     */
    public function void(int $id): void {
        $companyId = Session::companyId();
        
        try {
            $journal = db()->fetchOne(
                "SELECT * FROM journals WHERE id = ? AND company_id = ?",
                [$id, $companyId]
            );
            
            if (!$journal) {
                Session::flash('error', 'Transaksi tidak ditemukan.');
                redirect('/loans');
                return;
            }
            
            if ($journal['status'] === 'void') {
                Session::flash('error', 'Transaksi sudah di-void sebelumnya.');
                redirect('/loans');
                return;
            }
            
            // Void journal
            db()->update('journals', [
                'status' => 'void',
                'voided_at' => date('Y-m-d H:i:s'),
                'voided_by' => Session::userId()
            ], 'id = ?', [$id]);
            
            // Reverse bank_cash balance
            $cashBankDetail = db()->fetchOne(
                "SELECT jd.*, a.code 
                 FROM journal_details jd
                 JOIN accounts a ON jd.account_id = a.id
                 WHERE jd.journal_id = ? AND a.is_cash_bank = 1",
                [$id]
            );
            
            if ($cashBankDetail) {
                $bankCash = db()->fetchOne(
                    "SELECT id FROM bank_cash WHERE account_id = ? AND company_id = ?",
                    [$cashBankDetail['account_id'], $companyId]
                );
                
                if ($bankCash) {
                    if ($cashBankDetail['debit'] > 0) {
                        db()->query(
                            "UPDATE bank_cash SET balance = balance - ? WHERE id = ?",
                            [$cashBankDetail['debit'], $bankCash['id']]
                        );
                    } elseif ($cashBankDetail['credit'] > 0) {
                        db()->query(
                            "UPDATE bank_cash SET balance = balance + ? WHERE id = ?",
                            [$cashBankDetail['credit'], $bankCash['id']]
                        );
                    }
                }
            }
            
            Session::flash('success', 'Transaksi berhasil di-void.');
            
        } catch (Exception $e) {
            Session::flash('error', 'Gagal void transaksi: ' . $e->getMessage());
        }
        
        redirect('/loans');
    }
    
    // ========================================
    // PRIVATE HELPER METHODS
    // ========================================
    
    /**
     * Get outstanding loans grouped by lender
     * FIXED: Tidak lagi dibagi 2!
     */
    private function getOutstandingLoans(int $companyId): array {
        $loans = [];
        
        try {
            // Ambil dari akun kas/bank yang terkait, bukan SUM semua
            $transactions = db()->fetchAll(
                "SELECT j.id, j.description, j.journal_date,
                        (SELECT jd.debit FROM journal_details jd 
                         JOIN accounts a ON jd.account_id = a.id 
                         WHERE jd.journal_id = j.id AND a.is_cash_bank = 1 
                         LIMIT 1) as cash_debit,
                        (SELECT jd.credit FROM journal_details jd 
                         JOIN accounts a ON jd.account_id = a.id 
                         WHERE jd.journal_id = j.id AND a.is_cash_bank = 1 
                         LIMIT 1) as cash_credit,
                        CASE 
                            WHEN j.description LIKE 'Pinjaman dari%' THEN 'received'
                            WHEN j.description LIKE 'Bayar pinjaman%' THEN 'repaid'
                        END as type
                 FROM journals j
                 WHERE j.company_id = ? 
                 AND j.status = 'posted'
                 AND (j.description LIKE 'Pinjaman dari%' OR j.description LIKE 'Bayar pinjaman%')
                 ORDER BY j.journal_date ASC",
                [$companyId]
            );
            
            $lenderTotals = [];
            
            foreach ($transactions as $trx) {
                $lenderName = '';
                
                if ($trx['type'] === 'received') {
                    // Pinjaman masuk: extract lender name dari "Pinjaman dari XXX - Dari YYY"
                    preg_match('/^Pinjaman dari ([^-]+)/', $trx['description'], $matches);
                    $lenderName = trim($matches[1] ?? '');
                    
                    // FIXED: Nilai pinjaman = debit pada akun kas (uang masuk ke kas)
                    $amount = (float)($trx['cash_debit'] ?? 0);
                    
                    if (!isset($lenderTotals[$lenderName])) {
                        $lenderTotals[$lenderName] = ['received' => 0, 'paid' => 0];
                    }
                    $lenderTotals[$lenderName]['received'] += $amount;
                    
                } elseif ($trx['type'] === 'repaid') {
                    // Bayar pinjaman: extract lender name dari "Bayar pinjaman ke XXX - ..."
                    preg_match('/^Bayar pinjaman ke ([^-]+)/', $trx['description'], $matches);
                    $lenderName = trim($matches[1] ?? '');
                    
                    // FIXED: Nilai pembayaran = credit pada akun kas (uang keluar dari kas)
                    $amount = (float)($trx['cash_credit'] ?? 0);
                    
                    if (!isset($lenderTotals[$lenderName])) {
                        $lenderTotals[$lenderName] = ['received' => 0, 'paid' => 0];
                    }
                    $lenderTotals[$lenderName]['paid'] += $amount;
                }
            }
            
            foreach ($lenderTotals as $name => $totals) {
                $outstanding = $totals['received'] - $totals['paid'];
                if ($outstanding > 0 && !empty($name)) {
                    $loans[] = [
                        'lender_name' => $name,
                        'total_received' => $totals['received'],
                        'total_paid' => $totals['paid'],
                        'outstanding' => $outstanding
                    ];
                }
            }
            
            usort($loans, function($a, $b) {
                return $b['outstanding'] <=> $a['outstanding'];
            });
            
        } catch (Exception $e) {
            error_log("getOutstandingLoans error: " . $e->getMessage());
        }
        
        return $loans;
    }
    
    /**
     * Get loan summary (total received, repaid, outstanding)
     */
    private function getLoanSummary(int $companyId): array {
        $summary = [
            'total_received' => 0,
            'total_repaid' => 0,
            'outstanding' => 0,
            'lender_count' => 0
        ];
        
        try {
            // Cara paling akurat: dari akun Hutang Pinjaman Perorangan
            $loanAccountId = null;
            if (function_exists('getAccountingSetting')) {
                $loanAccountId = getAccountingSetting($companyId, 'loan_payable_personal');
            }
            
            if ($loanAccountId) {
                // Dari akun hutang:
                // Credit = pinjaman masuk (hutang bertambah)
                // Debit = pembayaran (hutang berkurang)
                $result = db()->fetchOne(
                    "SELECT 
                        COALESCE(SUM(jd.credit), 0) as total_received,
                        COALESCE(SUM(jd.debit), 0) as total_repaid
                     FROM journal_details jd
                     JOIN journals j ON jd.journal_id = j.id
                     WHERE j.company_id = ? 
                     AND j.status = 'posted'
                     AND jd.account_id = ?",
                    [$companyId, $loanAccountId]
                );
                
                if ($result) {
                    $summary['total_received'] = (float)$result['total_received'];
                    $summary['total_repaid'] = (float)$result['total_repaid'];
                    $summary['outstanding'] = $summary['total_received'] - $summary['total_repaid'];
                }
            }
            
            // Hitung jumlah pemberi pinjaman
            $outstandingLoans = $this->getOutstandingLoans($companyId);
            $summary['lender_count'] = count($outstandingLoans);
            
        } catch (Exception $e) {
            error_log("getLoanSummary error: " . $e->getMessage());
        }
        
        return $summary;
    }
    
    /**
     * Get lender name suggestions from existing transactions
     */
    private function getLenderSuggestions(int $companyId): array {
        $suggestions = [];
        
        try {
            $results = db()->fetchAll(
                "SELECT DISTINCT 
                    TRIM(
                        CASE 
                            WHEN description LIKE 'Pinjaman dari %' THEN 
                                SUBSTRING_INDEX(SUBSTRING(description, 15), ' - ', 1)
                            WHEN description LIKE 'Bayar pinjaman ke %' THEN 
                                SUBSTRING_INDEX(SUBSTRING(description, 19), ' - ', 1)
                        END
                    ) as lender_name
                 FROM journals 
                 WHERE company_id = ? 
                 AND status = 'posted'
                 AND (description LIKE 'Pinjaman dari%' OR description LIKE 'Bayar pinjaman%')
                 HAVING lender_name IS NOT NULL AND lender_name != ''
                 ORDER BY lender_name",
                [$companyId]
            );
            
            foreach ($results as $row) {
                if (!empty($row['lender_name'])) {
                    $suggestions[] = $row['lender_name'];
                }
            }
        } catch (Exception $e) {}
        
        return $suggestions;
    }
    
    /**
     * Get bank/cash options for dropdown
     */
    private function getBankCashOptions(int $companyId): array {
        $options = [];
        
        try {
            $bankCashList = db()->fetchAll(
                "SELECT * FROM bank_cash 
                 WHERE company_id = ? AND is_active = 1 
                 ORDER BY type, name",
                [$companyId]
            );
            
            foreach ($bankCashList as $bc) {
                $typeLabel = $bc['type'] === 'cash' ? 'Kas' : 'Bank';
                $balanceValue = $bc['balance'] ?? $bc['current_balance'] ?? 0;
                $balance = 'Rp ' . number_format($balanceValue, 0, ',', '.');
                $options[$bc['id']] = "[{$typeLabel}] {$bc['name']} (Saldo: {$balance})";
            }
        } catch (Exception $e) {
            error_log("getBankCashOptions error: " . $e->getMessage());
        }
        
        return $options;
    }
    
    /**
     * Parse rupiah string to float
     */
    private function parseRupiah(string $value): float {
        $cleaned = preg_replace('/[^\d]/', '', $value);
        return (float) $cleaned;
    }
    
    /**
     * Get page actions HTML
     */
    private function getPageActions(): string {
        return '
        <a href="' . url('/loans/receive') . '" class="btn btn-success">
            <i class="fas fa-plus-circle"></i> Terima Pinjaman
        </a>
        <a href="' . url('/loans/repay') . '" class="btn btn-warning">
            <i class="fas fa-money-bill-wave"></i> Bayar Pinjaman
        </a>';
    }
}