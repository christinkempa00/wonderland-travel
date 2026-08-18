<?php
/**
 * ================================================================
 * TRAVEL MANAGEMENT SYSTEM - BankCash Model
 * ================================================================
 */

require_once MODELS_PATH . '/Model.php';

class BankCash extends Model {
    
    protected static string $table = 'bank_cash';
    protected static string $primaryKey = 'id';
    protected static string $orderBy = 'name';
    protected static string $orderDir = 'ASC';
    
    protected static array $fillable = [
        'company_id',
        'account_id',
        'name',
        'type',
        'bank_name',
        'account_number',
        'account_holder',
        'balance',
        'is_default',
        'is_active'
    ];
    
    /**
     * Create bank/cash for company
     */
    public static function createForCompany(int $companyId, array $data): self {
        $model = new self();
        $model->company_id = $companyId;
        $model->name = $data['name'] ?? '';
        $model->type = $data['type'] ?? 'cash';
        $model->bank_name = $data['bank_name'] ?? '';
        $model->account_number = $data['account_number'] ?? '';
        $model->account_holder = $data['account_holder'] ?? '';
        $model->account_id = $data['account_id'] ?? null;
        $model->balance = $data['balance'] ?? 0;
        $model->is_default = $data['is_default'] ?? 0;
        $model->is_active = $data['is_active'] ?? 1;
        $model->save();
        return $model;
    }
    
    /**
     * Get by company
     */
    public static function getByCompany(int $companyId, bool $activeOnly = true): array {
        $sql = "SELECT bc.*, a.code as account_code, a.name as account_name
                FROM bank_cash bc
                LEFT JOIN accounts a ON bc.account_id = a.id
                WHERE bc.company_id = ?";
        $params = [$companyId];
        
        if ($activeOnly) {
            $sql .= " AND bc.is_active = 1";
        }
        
        $sql .= " ORDER BY bc.is_default DESC, bc.name";
        
        return self::db()->fetchAll($sql, $params);
    }
    
    /**
     * Get for dropdown
     */
    public static function getForDropdown(int $companyId): array {
        $items = self::getByCompany($companyId);
        
        $result = [];
        foreach ($items as $item) {
            $label = $item['name'];
            if ($item['type'] === 'bank' && $item['bank_name']) {
                $label .= ' (' . $item['bank_name'] . ')';
            }
            $result[$item['id']] = $label;
        }
        
        return $result;
    }
    
    /**
     * Get default
     */
    public static function getDefault(int $companyId): ?self {
        $row = self::db()->fetchOne(
            "SELECT * FROM bank_cash WHERE company_id = ? AND is_default = 1 AND is_active = 1",
            [$companyId]
        );
        
        if (!$row) return null;
        
        $model = new self($row);
        $model->exists = true;
        return $model;
    }
    
    /**
     * Get account
     */
    public function getAccount(): ?array {
        if (!$this->account_id) return null;
        
        return self::db()->fetchOne(
            "SELECT * FROM accounts WHERE id = ?",
            [$this->account_id]
        );
    }
    
    /**
     * Update balance from transactions
     */
    public function recalculateBalance(): void {
        $account = $this->getAccount();
        if (!$account) return;
        
        // Get balance from journal details (using debit/credit columns)
        $result = self::db()->fetchOne(
            "SELECT 
                COALESCE(SUM(jd.debit), 0) as total_debit,
                COALESCE(SUM(jd.credit), 0) as total_credit
             FROM journal_details jd
             JOIN journals j ON jd.journal_id = j.id
             WHERE jd.account_id = ? AND j.status = 'posted'",
            [$this->account_id]
        );
        
        $balance = ($result['total_debit'] ?? 0) - ($result['total_credit'] ?? 0);
        
        $this->update(['balance' => $balance]);
    }
    
    /**
     * Get transactions
     */
    public function getTransactions(?string $startDate = null, ?string $endDate = null, int $limit = 50): array {
        $sql = "SELECT j.journal_date, j.journal_number, j.description as journal_desc,
                       jd.description, jd.debit, jd.credit
                FROM journal_details jd
                JOIN journals j ON jd.journal_id = j.id
                WHERE jd.account_id = ? AND j.status = 'posted'";
        
        $params = [$this->account_id];
        
        if ($startDate) {
            $sql .= " AND j.journal_date >= ?";
            $params[] = $startDate;
        }
        
        if ($endDate) {
            $sql .= " AND j.journal_date <= ?";
            $params[] = $endDate;
        }
        
        $sql .= " ORDER BY j.journal_date DESC, j.id DESC LIMIT {$limit}";
        
        return self::db()->fetchAll($sql, $params);
    }
    
    /**
     * Set as default
     */
    public function setAsDefault(): void {
        // Remove default from others
        self::db()->update(
            'bank_cash',
            ['is_default' => 0],
            'company_id = ? AND id != ?',
            [$this->company_id, $this->id]
        );
        
        $this->update(['is_default' => 1]);
    }
    
    /**
     * Create with account
     */
    public static function createWithAccount(int $companyId, array $data): self {
        $db = self::db();
        $db->beginTransaction();
        
        try {
            // Create account if not exists
            if (empty($data['account_id'])) {
                $accountCode = Account::generateNextCode($companyId, '1.1.');
                
                $accountId = $db->insert('accounts', [
                    'company_id' => $companyId,
                    'code' => $accountCode,
                    'name' => $data['name'],
                    'type' => 'asset',
                    'normal_balance' => 'debit',
                    'is_cash_bank' => 1,
                    'is_active' => 1,
                    'created_at' => date('Y-m-d H:i:s')
                ]);
                
                $data['account_id'] = $accountId;
            }
            
            $data['company_id'] = $companyId;
            $data['current_balance'] = $data['initial_balance'] ?? 0;
            
            $bankCash = self::create($data);
            
            $db->commit();
            
            return $bankCash;
            
        } catch (Exception $e) {
            $db->rollback();
            throw $e;
        }
    }
    
    /**
     * Get type label
     */
    public function getTypeLabel(): string {
        return $this->type === 'bank' ? 'Bank' : 'Kas';
    }
}
