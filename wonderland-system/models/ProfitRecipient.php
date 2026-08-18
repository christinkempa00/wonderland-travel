<?php
/**
 * ================================================================
 * TRAVEL MANAGEMENT SYSTEM - Profit Recipient Model
 * ================================================================
 */

require_once MODELS_PATH . '/Model.php';

class ProfitRecipient extends Model {
    
    protected static string $table = 'profit_recipients';
    protected static string $primaryKey = 'id';
    protected static string $orderBy = 'sort_order';
    protected static string $orderDir = 'ASC';
    
    protected static array $fillable = [
        'company_id',
        'name',
        'role',
        'phone',
        'email',
        'bank_name',
        'bank_account',
        'bank_holder',
        'percentage',
        'is_active',
        'sort_order'
    ];
    
    /**
     * Get recipients by company
     */
    public static function getByCompany(int $companyId, bool $activeOnly = true): array {
        $sql = "SELECT * FROM profit_recipients WHERE company_id = ?";
        $params = [$companyId];
        
        if ($activeOnly) {
            $sql .= " AND is_active = 1";
        }
        
        $sql .= " ORDER BY sort_order, name";
        
        return self::db()->fetchAll($sql, $params);
    }
    
    /**
     * Get total percentage
     */
    public static function getTotalPercentage(int $companyId): float {
        return (float) self::db()->fetchColumn(
            "SELECT COALESCE(SUM(percentage), 0) FROM profit_recipients 
             WHERE company_id = ? AND is_active = 1",
            [$companyId]
        );
    }
    
    /**
     * Get pending distributions
     */
    public function getPendingDistributions(): array {
        return self::db()->fetchAll(
            "SELECT ps.*, o.order_number, o.event_name
             FROM profit_shares ps
             JOIN orders o ON ps.order_id = o.id
             WHERE ps.recipient_id = ? AND ps.status = 'pending'
             ORDER BY o.order_date DESC",
            [$this->id]
        );
    }
    
    /**
     * Get total pending amount
     */
    public function getTotalPending(): float {
        return (float) self::db()->fetchColumn(
            "SELECT COALESCE(SUM(amount), 0) FROM profit_shares 
             WHERE recipient_id = ? AND status = 'pending'",
            [$this->id]
        );
    }
    
    /**
     * Get total paid amount
     */
    public function getTotalPaid(): float {
        return (float) self::db()->fetchColumn(
            "SELECT COALESCE(SUM(amount), 0) FROM profit_shares 
             WHERE recipient_id = ? AND status = 'paid'",
            [$this->id]
        );
    }
    
    /**
     * Get distribution history
     */
    public function getDistributionHistory(int $limit = 20): array {
        return self::db()->fetchAll(
            "SELECT ps.*, o.order_number
             FROM profit_shares ps
             JOIN orders o ON ps.order_id = o.id
             WHERE ps.recipient_id = ?
             ORDER BY ps.created_at DESC
             LIMIT {$limit}",
            [$this->id]
        );
    }
    
    /**
     * Create recipient for company
     */
    public static function createForCompany(int $companyId, array $data): self {
        $data['company_id'] = $companyId;
        
        // Get next sort order
        $maxSort = (int) self::db()->fetchColumn(
            "SELECT MAX(sort_order) FROM profit_recipients WHERE company_id = ?",
            [$companyId]
        );
        $data['sort_order'] = $maxSort + 1;
        
        return self::create($data);
    }
    
    /**
     * Mark distributions as paid
     */
    public function markDistributionsAsPaid(array $shareIds, array $paymentData = []): int {
        if (empty($shareIds)) return 0;
        
        $placeholders = implode(',', array_fill(0, count($shareIds), '?'));
        $params = array_merge($shareIds, [$this->id]);
        
        return self::db()->query(
            "UPDATE profit_shares SET status = 'paid', paid_at = NOW() 
             WHERE id IN ({$placeholders}) AND recipient_id = ?",
            $params
        )->rowCount();
    }
}
