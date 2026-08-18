<?php
/**
 * ================================================================
 * TRAVEL MANAGEMENT SYSTEM - Client Model
 * ================================================================
 */

require_once MODELS_PATH . '/Model.php';

class Client extends Model {
    
    protected static string $table = 'clients';
    protected static string $primaryKey = 'id';
    protected static string $orderBy = 'name';
    protected static string $orderDir = 'ASC';
    
    protected static array $fillable = [
        'company_id',
        'client_code',
        'name',
        'contact_person',
        'email',
        'phone',
        'address',
        'npwp',
        'uses_divisi',
        'notes'
    ];

    /**
     * Generate next client code (CLI-0001, CLI-0002, ...) for a company
     */
    public static function generateClientCode(int $companyId): string {
        $lastNumber = self::db()->fetchColumn(
            "SELECT MAX(CAST(SUBSTRING_INDEX(client_code, '-', -1) AS UNSIGNED))
             FROM clients WHERE company_id = ? AND client_code IS NOT NULL",
            [$companyId]
        );

        $nextNumber = ($lastNumber ?? 0) + 1;
        return 'CLI-' . str_pad($nextNumber, 4, '0', STR_PAD_LEFT);
    }
    
    /**
     * Get clients by company with pagination
     */
    public static function getByCompanyPaginated(
        int $companyId, 
        int $page = 1, 
        int $perPage = 10, 
        string $search = ''
    ): array {
        return self::paginate(
            $page, 
            $perPage, 
            ['company_id' => $companyId],
            $search,
            ['name', 'contact_person', 'email', 'phone']
        );
    }
    
    /**
     * Get for dropdown
     */
    public static function getForDropdown(int $companyId): array {
        return self::pluck('name', 'id', ['company_id' => $companyId]);
    }
    
    /**
     * Search clients
     */
    public static function search(int $companyId, string $query, int $limit = 10): array {
        $sql = "SELECT * FROM clients 
                WHERE company_id = ? 
                AND (name LIKE ? OR contact_person LIKE ? OR email LIKE ? OR phone LIKE ?)
                ORDER BY name
                LIMIT {$limit}";
        
        $search = "%{$query}%";
        return self::db()->fetchAll($sql, [$companyId, $search, $search, $search, $search]);
    }
    
    /**
     * Get order count
     */
    public function getOrderCount(): int {
        return (int) self::db()->fetchColumn(
            "SELECT COUNT(*) FROM orders WHERE client_id = ?",
            [$this->id]
        );
    }
    
    /**
     * Get total revenue from client
     */
    public function getTotalRevenue(): float {
        return (float) self::db()->fetchColumn(
            "SELECT COALESCE(SUM(total_final_price), 0) FROM orders 
             WHERE client_id = ? AND status NOT IN ('draft', 'cancelled')",
            [$this->id]
        );
    }
    
    /**
     * Get recent orders
     */
    public function getRecentOrders(int $limit = 5): array {
        return self::db()->fetchAll(
            "SELECT * FROM orders WHERE client_id = ? 
             ORDER BY created_at DESC LIMIT {$limit}",
            [$this->id]
        );
    }
    
    /**
     * Check if client has orders
     */
    public function hasOrders(): bool {
        return $this->getOrderCount() > 0;
    }
    
    /**
     * Format phone for WhatsApp
     */
    public function getWhatsAppPhone(): ?string {
        if (!$this->phone) return null;
        return formatPhoneWa($this->phone);
    }
    
    /**
     * Create client with company
     */
    public static function createForCompany(int $companyId, array $data): self {
        $data['company_id'] = $companyId;
        if (empty($data['client_code'])) {
            $data['client_code'] = self::generateClientCode($companyId);
        }
        return self::create($data);
    }
    
    /**
     * Validate unique email per company
     */
    public static function isEmailUnique(int $companyId, string $email, ?int $exceptId = null): bool {
        $sql = "SELECT id FROM clients WHERE company_id = ? AND email = ?";
        $params = [$companyId, $email];
        
        if ($exceptId) {
            $sql .= " AND id != ?";
            $params[] = $exceptId;
        }
        
        return !self::db()->fetchColumn($sql, $params);
    }
}
