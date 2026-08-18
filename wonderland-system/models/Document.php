<?php
/**
 * ================================================================
 * TRAVEL MANAGEMENT SYSTEM - Document Model
 * ================================================================
 */

require_once MODELS_PATH . '/Model.php';

class Document extends Model {
    
    protected static string $table = 'documents';
    protected static string $primaryKey = 'id';
    protected static string $orderBy = 'created_at';
    protected static string $orderDir = 'DESC';
    
    protected static array $fillable = [
        'company_id',
        'order_id',
        'doc_type',
        'doc_number',
        'doc_date',
        'due_date',
        'content',
        'notes',
        'created_by'
    ];
    
    /**
     * Get documents by company with pagination
     */
    public static function getByCompanyPaginated(
        int $companyId,
        int $page = 1,
        int $perPage = 10,
        array $filters = []
    ): array {
        $offset = ($page - 1) * $perPage;
        $where = ["d.company_id = ?"];
        $params = [$companyId];
        
        if (!empty($filters['doc_type'])) {
            $where[] = "d.doc_type = ?";
            $params[] = $filters['doc_type'];
        }
        
        if (!empty($filters['search'])) {
            $where[] = "(d.doc_number LIKE ? OR o.order_number LIKE ?)";
            $params[] = "%{$filters['search']}%";
            $params[] = "%{$filters['search']}%";
        }
        
        if (!empty($filters['start_date'])) {
            $where[] = "d.doc_date >= ?";
            $params[] = $filters['start_date'];
        }
        
        if (!empty($filters['end_date'])) {
            $where[] = "d.doc_date <= ?";
            $params[] = $filters['end_date'];
        }
        
        $whereClause = implode(' AND ', $where);
        
        $countSql = "SELECT COUNT(*) FROM documents d 
                     LEFT JOIN orders o ON d.order_id = o.id
                     WHERE {$whereClause}";
        $total = (int) self::db()->fetchColumn($countSql, $params);
        
        $sql = "SELECT d.*, o.order_number, o.event_name, c.name as client_name
                FROM documents d
                LEFT JOIN orders o ON d.order_id = o.id
                LEFT JOIN clients c ON o.client_id = c.id
                WHERE {$whereClause}
                ORDER BY d.created_at DESC
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
     * Generate document number
     */
    public static function generateNumber(int $companyId, string $docType): string {
        $formats = [
            'quotation' => getCompanySetting($companyId, 'doc_number_format_quotation', 'QUO/{YY}{MM}/{NUM}'),
            'invoice' => getCompanySetting($companyId, 'doc_number_format_invoice', 'INV/{YY}{MM}/{NUM}'),
            'receipt' => getCompanySetting($companyId, 'doc_number_format_receipt', 'RCP/{YY}{MM}/{NUM}'),
            'agreement' => getCompanySetting($companyId, 'doc_number_format_agreement', 'AGR/{YY}{MM}/{NUM}')
        ];
        
        $format = $formats[$docType] ?? '{TYPE}/{YY}{MM}/{NUM}';
        
        $year = date('Y');
        $month = date('m');
        $yy = date('y');
        
        // Get last number
        $prefix = strtoupper(substr($docType, 0, 3));
        $lastNumber = self::db()->fetchColumn(
            "SELECT MAX(CAST(SUBSTRING_INDEX(doc_number, '/', -1) AS UNSIGNED))
             FROM documents 
             WHERE company_id = ? AND doc_type = ? 
             AND YEAR(created_at) = ? AND MONTH(created_at) = ?",
            [$companyId, $docType, $year, $month]
        );
        
        $nextNumber = ($lastNumber ?? 0) + 1;
        $num = str_pad($nextNumber, 4, '0', STR_PAD_LEFT);
        
        $docNumber = str_replace(
            ['{TYPE}', '{YEAR}', '{YY}', '{MONTH}', '{MM}', '{NUM}'],
            [$prefix, $year, $yy, $month, $month, $num],
            $format
        );
        
        return $docNumber;
    }
    
    /**
     * Get order
     */
    public function getOrder(): ?array {
        if (!$this->order_id) return null;
        
        return self::db()->fetchOne(
            "SELECT o.*, c.name as client_name, c.address as client_address,
                    c.phone as client_phone, c.email as client_email, c.npwp as client_npwp
             FROM orders o
             LEFT JOIN clients c ON o.client_id = c.id
             WHERE o.id = ?",
            [$this->order_id]
        );
    }
    
    /**
     * Get order items
     */
    public function getOrderItems(): array {
        if (!$this->order_id) return [];
        
        return self::db()->fetchAll(
            "SELECT * FROM order_items WHERE order_id = ? ORDER BY id",
            [$this->order_id]
        );
    }
    
    /**
     * Get company
     */
    public function getCompany(): ?array {
        return self::db()->fetchOne(
            "SELECT * FROM companies WHERE id = ?",
            [$this->company_id]
        );
    }
    
    /**
     * Get type info
     */
    public function getTypeInfo(): array {
        return DOCUMENT_TYPES[$this->doc_type] ?? ['label' => $this->doc_type, 'color' => 'secondary'];
    }
    
    /**
     * Create document for order
     */
    public static function createForOrder(int $orderId, string $docType, array $data = []): self {
        $order = self::db()->fetchOne("SELECT * FROM orders WHERE id = ?", [$orderId]);
        
        if (!$order) {
            throw new Exception('Order not found');
        }
        
        $docData = array_merge([
            'company_id' => $order['company_id'],
            'order_id' => $orderId,
            'doc_type' => $docType,
            'doc_number' => self::generateNumber($order['company_id'], $docType),
            'doc_date' => date('Y-m-d'),
            'created_by' => Session::userId()
        ], $data);
        
        // Set due date for invoice
        if ($docType === 'invoice') {
            $dueDays = (int) getCompanySetting($order['company_id'], 'invoice_due_days', 14);
            $docData['due_date'] = date('Y-m-d', strtotime("+{$dueDays} days"));
        }
        
        return self::create($docData);
    }
    
    /**
     * Get content as array
     */
    public function getContent(): array {
        if (!$this->content) return [];
        return json_decode($this->content, true) ?: [];
    }
    
    /**
     * Set content
     */
    public function setContent(array $content): void {
        $this->update(['content' => json_encode($content)]);
    }
}
