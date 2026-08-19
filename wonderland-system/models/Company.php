<?php
/**
 * ================================================================
 * TRAVEL MANAGEMENT SYSTEM - Company Model
 * ================================================================
 */

require_once MODELS_PATH . '/Model.php';

class Company extends Model {
    
    protected static string $table = 'companies';
    protected static string $primaryKey = 'id';
    protected static string $orderBy = 'name';
    protected static string $orderDir = 'ASC';
    
    protected static array $fillable = [
        'name',
        'slug',
        'address',
        'phone',
        'email',
        'website',
        'logo',
        'favicon',
        'bank_accounts',
        'npwp',
        'is_active'
    ];
    
    /**
     * Find by slug
     */
    public static function findBySlug(string $slug): ?self {
        return self::findBy('slug', $slug);
    }
    
    /**
     * Get active companies
     */
    public static function getActive(): array {
        return self::all(['is_active' => 1]);
    }
    
    /**
     * Get bank accounts
     */
    public function getBankAccounts(): array {
        if (!$this->bank_accounts) {
            return [];
        }
        
        $accounts = json_decode($this->bank_accounts, true);
        return is_array($accounts) ? $accounts : [];
    }
    
    /**
     * Set bank accounts
     */
    public function setBankAccounts(array $accounts): void {
        $this->bank_accounts = json_encode($accounts);
    }
    
    /**
     * Add bank account
     */
    public function addBankAccount(string $bankName, string $accountNo, string $accountName): void {
        $accounts = $this->getBankAccounts();
        $accounts[] = [
            'bank_name' => $bankName,
            'account_no' => $accountNo,
            'account_name' => $accountName
        ];
        $this->setBankAccounts($accounts);
    }
    
    /**
     * Get logo URL
     */
    public function getLogoUrl(): ?string {
        if ($this->logo) {
            // $this->logo sudah berisi path relatif lengkap dari upload
            // ('logos/xxx.png' — lihat SettingsController::uploadFile()),
            // jangan tambahkan 'logos/' lagi di sini.
            return uploadUrl($this->logo);
        }
        return null;
    }
    
    /**
     * Get favicon URL
     */
    public function getFaviconUrl(): ?string {
        if ($this->favicon) {
            return uploadUrl('favicons/' . $this->favicon);
        }
        return null;
    }
    
    /**
     * Get users
     */
    public function getUsers(): array {
        $sql = "SELECT u.* FROM users u
                JOIN user_companies uc ON u.id = uc.user_id
                WHERE uc.company_id = ? AND uc.is_active = 1 AND u.is_active = 1
                ORDER BY u.name";
        
        return self::db()->fetchAll($sql, [$this->id]);
    }
    
    /**
     * Get user count
     */
    public function getUserCount(): int {
        return (int) self::db()->fetchColumn(
            "SELECT COUNT(*) FROM user_companies WHERE company_id = ? AND is_active = 1",
            [$this->id]
        );
    }
    
    /**
     * Get setting
     */
    public function getSetting(string $key, $default = null) {
        return getCompanySetting($this->id, $key, $default);
    }
    
    /**
     * Set setting
     */
    public function setSetting(string $key, $value, string $type = 'text'): void {
        setCompanySetting($this->id, $key, $value, $type);
    }
    
    /**
     * Get all settings
     */
    public function getSettings(): array {
        $rows = self::db()->fetchAll(
            "SELECT setting_key, setting_value, setting_type FROM company_settings WHERE company_id = ?",
            [$this->id]
        );
        
        $settings = [];
        foreach ($rows as $row) {
            $value = $row['setting_value'];
            
            switch ($row['setting_type']) {
                case 'number':
                    $value = (float) $value;
                    break;
                case 'boolean':
                    $value = $value === '1' || $value === 'true';
                    break;
                case 'json':
                    $value = json_decode($value, true);
                    break;
            }
            
            $settings[$row['setting_key']] = $value;
        }
        
        return $settings;
    }
    
    /**
     * Initialize default settings
     */
    public function initializeSettings(): void {
        foreach (DEFAULT_SETTINGS as $key => $value) {
            $type = 'text';
            if (is_numeric($value)) {
                $type = 'number';
            } elseif (is_bool($value)) {
                $type = 'boolean';
            } elseif (is_array($value)) {
                $type = 'json';
            }
            
            $this->setSetting($key, $value, $type);
        }
    }
    
    /**
     * Get order stats
     */
    public function getOrderStats(): array {
        $sql = "SELECT 
                    COUNT(*) as total_orders,
                    SUM(CASE WHEN status = 'draft' THEN 1 ELSE 0 END) as draft,
                    SUM(CASE WHEN status = 'quotation' THEN 1 ELSE 0 END) as quotation,
                    SUM(CASE WHEN status = 'agreed' THEN 1 ELSE 0 END) as agreed,
                    SUM(CASE WHEN status = 'invoiced' THEN 1 ELSE 0 END) as invoiced,
                    SUM(CASE WHEN status = 'paid' THEN 1 ELSE 0 END) as paid,
                    SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed,
                    COALESCE(SUM(total_final_price), 0) as total_revenue,
                    COALESCE(SUM(total_profit), 0) as total_profit
                FROM orders 
                WHERE company_id = ?";
        
        return self::db()->fetchOne($sql, [$this->id]) ?: [];
    }
    
    /**
     * Get monthly revenue
     */
    public function getMonthlyRevenue(int $year = null): array {
        $year = $year ?? date('Y');
        
        $sql = "SELECT 
                    MONTH(order_date) as month,
                    COALESCE(SUM(total_final_price), 0) as revenue,
                    COALESCE(SUM(total_profit), 0) as profit,
                    COUNT(*) as orders
                FROM orders 
                WHERE company_id = ? 
                  AND YEAR(order_date) = ?
                  AND status NOT IN ('draft', 'cancelled')
                GROUP BY MONTH(order_date)
                ORDER BY month";
        
        $rows = self::db()->fetchAll($sql, [$this->id, $year]);
        
        // Fill all months
        $data = [];
        for ($i = 1; $i <= 12; $i++) {
            $data[$i] = ['month' => $i, 'revenue' => 0, 'profit' => 0, 'orders' => 0];
        }
        
        foreach ($rows as $row) {
            $data[$row['month']] = $row;
        }
        
        return array_values($data);
    }
    
    /**
     * Get profit recipients
     */
    public function getProfitRecipients(): array {
        return self::db()->fetchAll(
            "SELECT * FROM profit_recipients WHERE company_id = ? AND is_active = 1 ORDER BY sort_order, name",
            [$this->id]
        );
    }
    
    /**
     * Initialize default accounts (Chart of Accounts)
     */
    public function initializeAccounts(): void {
        // Check if accounts already exist
        $count = self::db()->fetchColumn(
            "SELECT COUNT(*) FROM accounts WHERE company_id = ?",
            [$this->id]
        );
        
        if ($count > 0) {
            return;
        }
        
        // Get default accounts
        $defaults = self::db()->fetchAll("SELECT * FROM default_accounts ORDER BY sort_order");
        
        $codeToId = [];
        
        foreach ($defaults as $acc) {
            $parentId = null;
            if ($acc['parent_code'] && isset($codeToId[$acc['parent_code']])) {
                $parentId = $codeToId[$acc['parent_code']];
            }
            
            $id = self::db()->insert('accounts', [
                'company_id' => $this->id,
                'code' => $acc['code'],
                'name' => $acc['name'],
                'type' => $acc['type'],
                'parent_id' => $parentId,
                'normal_balance' => $acc['normal_balance'],
                'is_cash_bank' => $acc['is_cash_bank'],
                'is_system' => 1,
                'sort_order' => $acc['sort_order']
            ]);
            
            $codeToId[$acc['code']] = $id;
        }
    }
    
    /**
     * Initialize default bank/cash
     */
    public function initializeBankCash(): void {
        // Check if already exists
        $count = self::db()->fetchColumn(
            "SELECT COUNT(*) FROM bank_cash WHERE company_id = ?",
            [$this->id]
        );
        
        if ($count > 0) {
            return;
        }
        
        // Get cash account id
        $cashAccountId = self::db()->fetchColumn(
            "SELECT id FROM accounts WHERE company_id = ? AND code = '1.1.01'",
            [$this->id]
        );
        
        // Create default cash
        self::db()->insert('bank_cash', [
            'company_id' => $this->id,
            'account_id' => $cashAccountId,
            'name' => 'Kas Utama',
            'type' => 'cash',
            'initial_balance' => 0,
            'current_balance' => 0,
            'is_default' => 1,
            'is_active' => 1
        ]);
    }
    
    /**
     * Generate slug from name
     */
    public static function generateSlug(string $name, ?int $exceptId = null): string {
        $slug = slug($name);
        $originalSlug = $slug;
        $counter = 1;
        
        while (true) {
            $sql = "SELECT id FROM companies WHERE slug = ?";
            $params = [$slug];
            
            if ($exceptId) {
                $sql .= " AND id != ?";
                $params[] = $exceptId;
            }
            
            $exists = self::db()->fetchColumn($sql, $params);
            
            if (!$exists) {
                break;
            }
            
            $slug = $originalSlug . '-' . $counter;
            $counter++;
        }
        
        return $slug;
    }
    
    /**
     * Create company with initialization
     */
    public static function createWithDefaults(array $data): self {
        // Generate slug if not provided
        if (empty($data['slug'])) {
            $data['slug'] = self::generateSlug($data['name']);
        }
        
        $company = self::create($data);
        
        // Initialize settings
        $company->initializeSettings();
        
        // Initialize accounts
        $company->initializeAccounts();
        
        // Initialize bank/cash
        $company->initializeBankCash();
        
        return $company;
    }
    
    /**
     * Get for dropdown
     */
    public static function getForDropdown(): array {
        return self::pluck('name', 'id', ['is_active' => 1]);
    }
}
