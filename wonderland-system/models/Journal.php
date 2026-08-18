<?php
/**
 * ================================================================
 * TRAVEL MANAGEMENT SYSTEM - Journal Model
 * Fixed to match database structure (debit/credit columns)
 * ================================================================
 */

require_once MODELS_PATH . '/Model.php';

class Journal extends Model {
    
    protected static string $table = 'journals';
    protected static string $primaryKey = 'id';
    protected static string $orderBy = 'journal_date';
    protected static string $orderDir = 'DESC';
    
    protected static array $fillable = [
        'company_id',
        'journal_number',
        'journal_date',
        'description',
        'reference',
        'status',
        'posted_at',
        'posted_by',
        'voided_at',
        'voided_by',
        'created_by'
    ];
    
    /**
     * Status
     */
    const STATUS_DRAFT = 'draft';
    const STATUS_POSTED = 'posted';
    const STATUS_VOID = 'void';
    
    /**
     * Get journals by company with pagination
     */
    public static function getByCompanyPaginated(
        int $companyId,
        int $page = 1,
        int $perPage = 10,
        array $filters = []
    ): array {
        $offset = ($page - 1) * $perPage;
        $where = ["j.company_id = ?"];
        $params = [$companyId];
        
        // Date filter
        if (!empty($filters['start_date'])) {
            $where[] = "j.journal_date >= ?";
            $params[] = $filters['start_date'];
        }
        
        if (!empty($filters['end_date'])) {
            $where[] = "j.journal_date <= ?";
            $params[] = $filters['end_date'];
        }
        
        // Status filter
        if (!empty($filters['status'])) {
            $where[] = "j.status = ?";
            $params[] = $filters['status'];
        }
        
        // Search
        if (!empty($filters['search'])) {
            $where[] = "(j.journal_number LIKE ? OR j.description LIKE ?)";
            $params[] = "%{$filters['search']}%";
            $params[] = "%{$filters['search']}%";
        }
        
        $whereClause = implode(' AND ', $where);
        
        // Count total
        $countSql = "SELECT COUNT(*) FROM journals j WHERE {$whereClause}";
        $total = (int) self::db()->fetchColumn($countSql, $params);
        
        // Get data with totals from details
        $sql = "SELECT j.*, u.name as created_by_name,
                       COALESCE((SELECT SUM(jd.debit) FROM journal_details jd WHERE jd.journal_id = j.id), 0) as total_debit,
                       COALESCE((SELECT SUM(jd.credit) FROM journal_details jd WHERE jd.journal_id = j.id), 0) as total_credit
                FROM journals j
                LEFT JOIN users u ON j.created_by = u.id
                WHERE {$whereClause}
                ORDER BY j.journal_date DESC, j.id DESC
                LIMIT {$perPage} OFFSET {$offset}";
        
        $data = self::db()->fetchAll($sql, $params);
        
        return [
            'data' => $data,
            'total' => $total,
            'per_page' => $perPage,
            'current_page' => $page,
            'last_page' => ceil($total / $perPage),
            'from' => $offset + 1,
            'to' => min($offset + $perPage, $total)
        ];
    }
    
    /**
     * Generate journal number
     */
    public static function generateNumber(int $companyId, string $type = 'JU'): string {
        $prefix = $type . '/' . date('ym');
        
        $lastNumber = self::db()->fetchColumn(
            "SELECT MAX(CAST(SUBSTRING_INDEX(journal_number, '/', -1) AS UNSIGNED))
             FROM journals 
             WHERE company_id = ? AND journal_number LIKE ?",
            [$companyId, $prefix . '%']
        );
        
        $nextNumber = ($lastNumber ?? 0) + 1;
        
        return $prefix . '/' . str_pad($nextNumber, 4, '0', STR_PAD_LEFT);
    }
    
    /**
     * Get journal details
     */
    public function getDetails(): array {
        return self::db()->fetchAll(
            "SELECT jd.*, a.code as account_code, a.name as account_name
             FROM journal_details jd
             JOIN accounts a ON jd.account_id = a.id
             WHERE jd.journal_id = ?
             ORDER BY jd.debit DESC, jd.id",
            [$this->id]
        );
    }
    
    /**
     * Add detail - using debit/credit columns
     */
    public function addDetail(int $accountId, float $debit, float $credit, string $description = ''): int {
        return self::db()->insert('journal_details', [
            'journal_id' => $this->id,
            'account_id' => $accountId,
            'debit' => $debit,
            'credit' => $credit,
            'description' => $description
        ]);
    }
    
    /**
     * Clear details
     */
    public function clearDetails(): void {
        self::db()->delete('journal_details', 'journal_id = ?', [$this->id]);
    }
    
    /**
     * Validate balance (debit = credit)
     */
    public function isBalanced(): bool {
        $result = self::db()->fetchOne(
            "SELECT 
                COALESCE(SUM(debit), 0) as total_debit,
                COALESCE(SUM(credit), 0) as total_credit
             FROM journal_details WHERE journal_id = ?",
            [$this->id]
        );
        
        return abs($result['total_debit'] - $result['total_credit']) < 0.01;
    }
    
    /**
     * Get totals
     */
    public function getTotals(): array {
        return self::db()->fetchOne(
            "SELECT 
                COALESCE(SUM(debit), 0) as total_debit,
                COALESCE(SUM(credit), 0) as total_credit
             FROM journal_details WHERE journal_id = ?",
            [$this->id]
        );
    }
    
    /**
     * Post journal
     */
    public function post(): bool {
        if ($this->status !== 'draft') {
            return false;
        }
        
        if (!$this->isBalanced()) {
            return false;
        }
        
        return $this->update([
            'status' => 'posted',
            'posted_at' => date('Y-m-d H:i:s'),
            'posted_by' => Session::userId()
        ]);
    }
    
    /**
     * Void journal
     */
    public function void(): bool {
        if ($this->status !== 'posted') {
            return false;
        }
        
        return $this->update([
            'status' => 'void',
            'voided_at' => date('Y-m-d H:i:s'),
            'voided_by' => Session::userId()
        ]);
    }
    
    /**
     * Create journal with details
     * Details format: [['account_id' => 1, 'debit' => 100, 'credit' => 0, 'description' => ''], ...]
     */
    public static function createWithDetails(int $companyId, array $journalData, array $details): self {
        $db = self::db();
        $db->beginTransaction();
        
        try {
            // Generate number
            $type = $journalData['type'] ?? 'JU';
            $journalData['journal_number'] = self::generateNumber($companyId, $type);
            $journalData['company_id'] = $companyId;
            $journalData['created_by'] = Session::userId();
            $journalData['status'] = $journalData['status'] ?? 'draft';
            
            unset($journalData['type']);
            
            // Create journal
            $journal = self::create($journalData);
            
            // Add details
            foreach ($details as $detail) {
                $debit = (float) ($detail['debit'] ?? 0);
                $credit = (float) ($detail['credit'] ?? 0);
                
                // Support legacy format with position/amount
                if (isset($detail['position']) && isset($detail['amount'])) {
                    if ($detail['position'] === 'debit') {
                        $debit = (float) $detail['amount'];
                        $credit = 0;
                    } else {
                        $debit = 0;
                        $credit = (float) $detail['amount'];
                    }
                }
                
                $journal->addDetail(
                    $detail['account_id'],
                    $debit,
                    $credit,
                    $detail['description'] ?? ''
                );
            }
            
            $db->commit();
            
            return $journal;
            
        } catch (Exception $e) {
            $db->rollback();
            throw $e;
        }
    }
    
    /**
     * Update journal with details
     */
    public function updateWithDetails(array $journalData, array $details): bool {
        if ($this->status !== 'draft') {
            return false;
        }
        
        $db = self::db();
        $db->beginTransaction();
        
        try {
            // Update journal
            $this->update($journalData);
            
            // Clear and re-add details
            $this->clearDetails();
            
            foreach ($details as $detail) {
                $debit = (float) ($detail['debit'] ?? 0);
                $credit = (float) ($detail['credit'] ?? 0);
                
                // Support legacy format with position/amount
                if (isset($detail['position']) && isset($detail['amount'])) {
                    if ($detail['position'] === 'debit') {
                        $debit = (float) $detail['amount'];
                        $credit = 0;
                    } else {
                        $debit = 0;
                        $credit = (float) $detail['amount'];
                    }
                }
                
                $this->addDetail(
                    $detail['account_id'],
                    $debit,
                    $credit,
                    $detail['description'] ?? ''
                );
            }
            
            $db->commit();
            
            return true;
            
        } catch (Exception $e) {
            $db->rollback();
            throw $e;
        }
    }
    
    /**
     * Get status label
     */
    public function getStatusLabel(): string {
        $labels = [
            'draft' => 'Draft',
            'posted' => 'Posted',
            'void' => 'Void'
        ];
        
        return $labels[$this->status] ?? $this->status;
    }
    
    /**
     * Get status color
     */
    public function getStatusColor(): string {
        $colors = [
            'draft' => 'warning',
            'posted' => 'success',
            'void' => 'danger'
        ];
        
        return $colors[$this->status] ?? 'secondary';
    }
}
