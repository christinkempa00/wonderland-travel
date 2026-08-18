<?php
/**
 * ================================================================
 * TRAVEL MANAGEMENT SYSTEM - Account Model
 * ================================================================
 */

require_once MODELS_PATH . '/Model.php';

class Account extends Model {
    
    protected static string $table = 'accounts';
    protected static string $primaryKey = 'id';
    protected static string $orderBy = 'code';
    protected static string $orderDir = 'ASC';
    
    protected static array $fillable = [
        'company_id',
        'code',
        'name',
        'type',
        'parent_id',
        'normal_balance',
        'is_cash_bank',
        'is_active',
        'sort_order'
    ];
    
    /**
     * Account types
     */
    const TYPE_ASSET = 'asset';
    const TYPE_LIABILITY = 'liability';
    const TYPE_EQUITY = 'equity';
    const TYPE_REVENUE = 'revenue';
    const TYPE_EXPENSE = 'expense';
    
    /**
     * Get accounts by company
     */
    public static function getByCompany(int $companyId, bool $activeOnly = true): array {
        $sql = "SELECT * FROM accounts WHERE company_id = ?";
        $params = [$companyId];
        
        if ($activeOnly) {
            $sql .= " AND is_active = 1";
        }
        
        $sql .= " ORDER BY code";
        
        return self::db()->fetchAll($sql, $params);
    }
    
    /**
     * Get accounts tree structure
     */
    public static function getTree(int $companyId): array {
        $accounts = self::getByCompany($companyId);
        return self::buildTree($accounts);
    }
    
    /**
     * Build tree from flat array
     */
    private static function buildTree(array $accounts, ?int $parentId = null): array {
        $tree = [];
        
        foreach ($accounts as $account) {
            if ($account['parent_id'] == $parentId) {
                $children = self::buildTree($accounts, $account['id']);
                $account['children'] = $children;
                $account['has_children'] = !empty($children);
                $tree[] = $account;
            }
        }
        
        return $tree;
    }
    
    /**
     * Get accounts grouped by type
     */
    public static function getGroupedByType(int $companyId): array {
        $accounts = self::getByCompany($companyId);
        
        $grouped = [
            'asset' => [],
            'liability' => [],
            'equity' => [],
            'revenue' => [],
            'expense' => []
        ];
        
        foreach ($accounts as $account) {
            $grouped[$account['type']][] = $account;
        }
        
        return $grouped;
    }
    
    /**
     * Get for dropdown
     */
    public static function getForDropdown(int $companyId, ?string $type = null): array {
        $sql = "SELECT id, CONCAT(code, ' - ', name) as label FROM accounts WHERE company_id = ? AND is_active = 1";
        $params = [$companyId];
        
        if ($type) {
            $sql .= " AND type = ?";
            $params[] = $type;
        }
        
        $sql .= " ORDER BY code";
        
        $rows = self::db()->fetchAll($sql, $params);
        
        $result = [];
        foreach ($rows as $row) {
            $result[$row['id']] = $row['label'];
        }
        
        return $result;
    }
    
    /**
     * Get cash/bank accounts for dropdown
     */
    public static function getCashBankForDropdown(int $companyId): array {
        $sql = "SELECT a.id, CONCAT(a.code, ' - ', a.name) as label 
                FROM accounts a
                WHERE a.company_id = ? AND a.is_cash_bank = 1 AND a.is_active = 1
                ORDER BY a.code";
        
        $rows = self::db()->fetchAll($sql, [$companyId]);
        
        $result = [];
        foreach ($rows as $row) {
            $result[$row['id']] = $row['label'];
        }
        
        return $result;
    }
    
    /**
     * Get cash/bank accounts
     */
    public static function getCashBankAccounts(int $companyId): array {
        $sql = "SELECT * FROM accounts 
                WHERE company_id = ? AND is_cash_bank = 1 AND is_active = 1
                ORDER BY code";
        
        return self::db()->fetchAll($sql, [$companyId]);
    }
    
    /**
     * Get balance for account
     */
    public function getBalance(?string $startDate = null, ?string $endDate = null): float {
        $sql = "SELECT 
                    COALESCE(SUM(jd.debit), 0) as total_debit,
                    COALESCE(SUM(jd.credit), 0) as total_credit
                FROM journal_details jd
                JOIN journals j ON jd.journal_id = j.id
                WHERE jd.account_id = ? AND j.status = 'posted'";
        
        $params = [$this->id];
        
        if ($startDate) {
            $sql .= " AND j.journal_date >= ?";
            $params[] = $startDate;
        }
        
        if ($endDate) {
            $sql .= " AND j.journal_date <= ?";
            $params[] = $endDate;
        }
        
        $result = self::db()->fetchOne($sql, $params);
        
        $debit = (float) ($result['total_debit'] ?? 0);
        $credit = (float) ($result['total_credit'] ?? 0);
        
        // Calculate balance based on normal balance
        if ($this->normal_balance === 'debit') {
            return $debit - $credit;
        } else {
            return $credit - $debit;
        }
    }
    
    /**
     * Get transactions (ledger entries) - UPDATED with journal_id and reference
     */
    public function getTransactions(?string $startDate = null, ?string $endDate = null): array {
        $sql = "SELECT 
                    j.id as journal_id,
                    j.journal_date, 
                    j.journal_number, 
                    j.description as journal_description,
                    j.reference,
                    jd.description, 
                    jd.debit, 
                    jd.credit
                FROM journal_details jd
                JOIN journals j ON jd.journal_id = j.id
                WHERE jd.account_id = ? AND j.status = 'posted'";
        
        $params = [$this->id];
        
        if ($startDate) {
            $sql .= " AND j.journal_date >= ?";
            $params[] = $startDate;
        }
        
        if ($endDate) {
            $sql .= " AND j.journal_date <= ?";
            $params[] = $endDate;
        }
        
        $sql .= " ORDER BY j.journal_date, j.id";
        
        return self::db()->fetchAll($sql, $params);
    }
    
    /**
     * Check if account has transactions
     */
    public function hasTransactions(): bool {
        $count = self::db()->fetchColumn(
            "SELECT COUNT(*) FROM journal_details WHERE account_id = ?",
            [$this->id]
        );
        
        return $count > 0;
    }
    
    /**
     * Get children accounts
     */
    public function getChildren(): array {
        return self::db()->fetchAll(
            "SELECT * FROM accounts WHERE parent_id = ? ORDER BY code",
            [$this->id]
        );
    }
    
    /**
     * Get parent account
     */
    public function getParent(): ?array {
        if (!$this->parent_id) return null;
        
        return self::db()->fetchOne(
            "SELECT * FROM accounts WHERE id = ?",
            [$this->parent_id]
        );
    }
    
    /**
     * Generate next code
     */
    public static function generateNextCode(int $companyId, string $prefix): string {
        $lastCode = self::db()->fetchColumn(
            "SELECT MAX(code) FROM accounts WHERE company_id = ? AND code LIKE ?",
            [$companyId, $prefix . '%']
        );
        
        if (!$lastCode) {
            return $prefix . '01';
        }
        
        // Extract number part
        $num = (int) substr($lastCode, strlen($prefix));
        $nextNum = $num + 1;
        
        return $prefix . str_pad($nextNum, 2, '0', STR_PAD_LEFT);
    }
    
    /**
     * Create account
     */
    public static function createForCompany(int $companyId, array $data): self {
        $data['company_id'] = $companyId;
        
        // Set normal balance based on type
        if (empty($data['normal_balance'])) {
            $data['normal_balance'] = in_array($data['type'], ['asset', 'expense']) ? 'debit' : 'credit';
        }
        
        return self::create($data);
    }
    
    /**
     * Get type label
     */
    public function getTypeLabel(): string {
        $labels = [
            'asset' => 'Aset',
            'liability' => 'Kewajiban',
            'equity' => 'Ekuitas',
            'revenue' => 'Pendapatan',
            'expense' => 'Beban'
        ];
        
        return $labels[$this->type] ?? $this->type;
    }
}