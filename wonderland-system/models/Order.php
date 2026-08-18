<?php
/**
 * ================================================================
 * TRAVEL MANAGEMENT SYSTEM - Order Model
 * ================================================================
 * 
 * UPDATED v6 - SMART ATTACHMENT MAPPING:
 * - Mapping berdasarkan description/nama, bukan hanya posisi
 * - Support multiple items dengan nama sama (by position within same name)
 * - Always fixes orphans with best-match fallback
 */

require_once MODELS_PATH . '/Model.php';

class Order extends Model {
    
    protected static string $table = 'orders';
    protected static string $primaryKey = 'id';
    protected static string $orderBy = 'created_at';
    protected static string $orderDir = 'DESC';
    
    protected static array $fillable = [
        'company_id',
        'client_id',
        'order_number',
        'order_date',
        'event_date',
        'event_end_date',
        'event_name',
        'description',
        'notes',
        'total_base_price',
        'total_markup',
        'total_final_price',
        'total_profit',
        'status',
        'payment_status',
        'paid_amount',
        'paid_at',
        'created_by'
    ];
    
    /**
     * Get orders by company with pagination
     */
    public static function getByCompanyPaginated(
        int $companyId, 
        int $page = 1, 
        int $perPage = 10, 
        array $filters = []
    ): array {
        $where = ['company_id' => $companyId];
        $search = '';
        $searchColumns = ['order_number', 'event_name'];
        
        if (!empty($filters['status'])) {
            $where['status'] = $filters['status'];
        }
        
        if (!empty($filters['payment_status'])) {
            $where['payment_status'] = $filters['payment_status'];
        }
        
        if (!empty($filters['search'])) {
            $search = $filters['search'];
        }
        
        return self::paginate($page, $perPage, $where, $search, $searchColumns);
    }
    
    /**
     * Get with client
     */
    public static function getWithClient(int $id): ?array {
        $sql = "SELECT o.*, c.name as client_name, c.contact_person, c.phone as client_phone, c.email as client_email
                FROM orders o
                LEFT JOIN clients c ON o.client_id = c.id
                WHERE o.id = ?";
        
        return self::db()->fetchOne($sql, [$id]);
    }
    
    /**
     * Generate order number
     */
    public static function generateOrderNumber(int $companyId): string {
        $format = getCompanySetting($companyId, 'doc_number_format_order', 'ORD/{YY}{MM}/{NUM}');
        
        $year = date('Y');
        $month = date('m');
        $yy = date('y');
        
        $lastNumber = self::db()->fetchColumn(
            "SELECT MAX(CAST(SUBSTRING_INDEX(order_number, '/', -1) AS UNSIGNED)) 
             FROM orders 
             WHERE company_id = ? AND YEAR(created_at) = ? AND MONTH(created_at) = ?",
            [$companyId, $year, $month]
        );
        
        $nextNumber = ($lastNumber ?? 0) + 1;
        $num = str_pad($nextNumber, 4, '0', STR_PAD_LEFT);
        
        $orderNumber = str_replace(
            ['{YEAR}', '{YY}', '{MONTH}', '{MM}', '{NUM}'],
            [$year, $yy, $month, $month, $num],
            $format
        );
        
        return $orderNumber;
    }
    
    /**
     * Get items
     */
    public function getItems(): array {
        return self::db()->fetchAll(
            "SELECT * FROM order_items WHERE order_id = ? ORDER BY id",
            [$this->id]
        );
    }
    
    /**
     * Add item
     */
    public function addItem(array $data): int {
        $data['order_id'] = $this->id;
        $data['created_at'] = date('Y-m-d H:i:s');
        
        return self::db()->insert('order_items', $data);
    }
    
    /**
     * Update item
     */
    public function updateItem(int $itemId, array $data): bool {
        $data['updated_at'] = date('Y-m-d H:i:s');
        
        return self::db()->update('order_items', $data, 'id = ? AND order_id = ?', [$itemId, $this->id]) > 0;
    }
    
    /**
     * Delete item
     * FIXED: Also cleanup related attachment data
     */
    public function deleteItem(int $itemId): bool {
        // Get item info before deleting
        $item = self::db()->fetchOne(
            "SELECT item_type FROM order_items WHERE id = ? AND order_id = ?",
            [$itemId, $this->id]
        );
        
        if ($item) {
            // Cleanup attachment tables based on item type
            $type = $item['item_type'];
            try {
                if ($type === 'hotel') {
                    self::db()->delete('hotel_guests', 'order_item_id = ?', [$itemId]);
                }
                if ($type === 'flight') {
                    self::db()->delete('flight_details', 'order_item_id = ?', [$itemId]);
                }
                if (in_array($type, ['rental', 'bus', 'towing'])) {
                    self::db()->delete('vehicle_documents', 'order_item_id = ?', [$itemId]);
                }
                if ($type === 'rental') {
                    self::db()->delete('rental_details', 'order_item_id = ?', [$itemId]);
                }
            } catch (Exception $e) {
                // Table might not exist, continue
            }
        }
        
        return self::db()->delete('order_items', 'id = ? AND order_id = ?', [$itemId, $this->id]) > 0;
    }
    
    /**
     * Clear all items
     */
    public function clearItems(): void {
        self::db()->delete('order_items', 'order_id = ?', [$this->id]);
    }
    
    /**
     * Recalculate totals from items
     */
    public function recalculateTotals(): void {
        // Markup dihitung dari (harga beli + cashback), bukan harga beli saja —
        // lihat createWithItems()/updateWithItems() untuk formula yang sama.
        $result = self::db()->fetchOne(
            "SELECT
                COALESCE(SUM(quantity * num_days * base_price), 0) as total_base,
                COALESCE(SUM(
                    CASE
                        WHEN markup_type = 'percentage' THEN
                            (quantity * num_days * (base_price + cashback)) * (markup_value / 100)
                        ELSE
                            markup_value * quantity * num_days
                    END
                ), 0) as total_markup,
                COALESCE(SUM(
                    (quantity * num_days * (base_price + cashback)) +
                    CASE
                        WHEN markup_type = 'percentage' THEN
                            (quantity * num_days * (base_price + cashback)) * (markup_value / 100)
                        ELSE
                            markup_value * quantity * num_days
                    END
                ), 0) as total_final
             FROM order_items WHERE order_id = ?",
            [$this->id]
        );
        
        $this->update([
            'total_base_price' => $result['total_base'],
            'total_markup' => $result['total_markup'],
            'total_final_price' => $result['total_final'],
            'total_profit' => $result['total_markup']
        ]);
    }
    
    /**
     * Get documents
     */
    public function getDocuments(): array {
        return self::db()->fetchAll(
            "SELECT * FROM documents WHERE order_id = ? ORDER BY created_at DESC",
            [$this->id]
        );
    }
    
    /**
     * Get document by type
     */
    public function getDocumentByType(string $type): ?array {
        return self::db()->fetchOne(
            "SELECT * FROM documents WHERE order_id = ? AND doc_type = ? ORDER BY created_at DESC LIMIT 1",
            [$this->id, $type]
        );
    }
    
    /**
     * Get payments
     */
    public function getPayments(): array {
        return self::db()->fetchAll(
            "SELECT * FROM payment_records WHERE order_id = ? ORDER BY payment_date DESC",
            [$this->id]
        );
    }
    
    /**
     * Add payment
     */
    public function addPayment(array $data): int {
        $data['order_id'] = $this->id;
        $data['company_id'] = $this->company_id;
        $data['created_at'] = date('Y-m-d H:i:s');
        
        $paymentId = self::db()->insert('payment_records', $data);
        
        $this->updatePaidAmount();
        
        return $paymentId;
    }
    
    /**
     * Update paid amount from payments
     */
    public function updatePaidAmount(): void {
        $totalPaid = (float) self::db()->fetchColumn(
            "SELECT COALESCE(SUM(amount), 0) FROM order_payments WHERE order_id = ? AND invoice_id IS NULL AND status = 'posted'",
            [$this->id]
        );
        
        $paymentStatus = 'unpaid';
        $paidAt = null;
        if ($totalPaid >= $this->total_final_price) {
            $paymentStatus = 'paid';
            $paidAt = date('Y-m-d');
        } elseif ($totalPaid > 0) {
            $paymentStatus = 'partial';
            $paidAt = date('Y-m-d');
        }

        $this->update([
            'paid_amount' => $totalPaid,
            'payment_status' => $paymentStatus,
            'paid_at' => $paidAt
        ]);
    }
    
    /**
     * Get remaining amount
     */
    public function getRemainingAmount(): float {
        return max(0, $this->total_final_price - $this->paid_amount);
    }
    
    /**
     * Update status
     */
    public function updateStatus(string $status): bool {
        if (!isset(ORDER_STATUSES[$status])) {
            return false;
        }
        
        return $this->update(['status' => $status]);
    }
    
    /**
     * Get status info
     */
    public function getStatusInfo(): ?array {
        return ORDER_STATUSES[$this->status] ?? null;
    }
    
    /**
     * Get payment status info
     */
    public function getPaymentStatusInfo(): ?array {
        return PAYMENT_STATUSES[$this->payment_status] ?? null;
    }
    
    /**
     * Can generate document type
     */
    public function canGenerateDocument(string $type): bool {
        switch ($type) {
            case 'quotation':
                return in_array($this->status, ['draft', 'quotation']);
            case 'agreement':
                return in_array($this->status, ['quotation', 'agreed']);
            case 'invoice':
                return in_array($this->status, ['agreed', 'invoiced', 'paid']);
            case 'receipt':
                return $this->paid_amount > 0;
            default:
                return false;
        }
    }
    
    /**
     * Get client
     */
    public function getClient(): ?array {
        if (!$this->client_id) return null;
        
        return self::db()->fetchOne(
            "SELECT * FROM clients WHERE id = ?",
            [$this->client_id]
        );
    }
    
    /**
     * Get profit shares
     */
    public function getProfitShares(): array {
        return self::db()->fetchAll(
            "SELECT ps.*, pr.name as recipient_name 
             FROM profit_shares ps
             JOIN profit_recipients pr ON ps.recipient_id = pr.id
             WHERE ps.order_id = ?
             ORDER BY ps.id",
            [$this->id]
        );
    }
    
    /**
     * Calculate and save profit shares
     */
    public function calculateProfitShares(): void {
        self::db()->delete('profit_shares', 'order_id = ?', [$this->id]);
        
        if ($this->total_profit <= 0) {
            return;
        }
        
        $recipients = self::db()->fetchAll(
            "SELECT * FROM profit_recipients WHERE company_id = ? AND is_active = 1 ORDER BY sort_order",
            [$this->company_id]
        );
        
        foreach ($recipients as $recipient) {
            $amount = ($recipient['percentage'] / 100) * $this->total_profit;
            
            self::db()->insert('profit_shares', [
                'order_id' => $this->id,
                'recipient_id' => $recipient['id'],
                'percentage' => $recipient['percentage'],
                'amount' => $amount,
                'status' => 'pending',
                'created_at' => date('Y-m-d H:i:s')
            ]);
        }
    }
    
    /**
     * Create order with items
     */
    public static function createWithItems(int $companyId, array $orderData, array $items): self {
        $db = self::db();
        $db->beginTransaction();
        
        try {
            $orderData['company_id'] = $companyId;
            $orderData['order_number'] = self::generateOrderNumber($companyId);
            $orderData['created_by'] = Session::userId();
            
            $order = self::create($orderData);
            
            foreach ($items as $item) {
                $qty = (int)($item['quantity'] ?? 1);
                $days = (int)($item['num_days'] ?? 1);
                $basePrice = (float)($item['base_price'] ?? 0);
                $cashback = (float)($item['cashback'] ?? 0);
                $markupType = $item['markup_type'] ?? 'percentage';
                $markupValue = (float)($item['markup_value'] ?? 0);

                // Markup dihitung dari (harga beli + cashback), bukan harga beli saja
                $itemBase = $qty * $days * $basePrice;
                $itemCashback = $qty * $days * $cashback;
                $markupBase = $itemBase + $itemCashback;
                if ($markupType === 'percentage') {
                    $itemMarkup = $markupBase * ($markupValue / 100);
                } else {
                    $itemMarkup = $markupValue * $qty * $days;
                }

                $item['cashback'] = $cashback;
                $item['markup_amount'] = $itemMarkup;
                $item['final_price'] = $markupBase + $itemMarkup;

                $order->addItem($item);
            }
            
            $order->recalculateTotals();
            $order->calculateProfitShares();
            
            $db->commit();
            
            return $order;
            
        } catch (Exception $e) {
            $db->rollback();
            throw $e;
        }
    }
    
    /**
     * Update order with items
     * FIXED v6: Smart mapping berdasarkan description/nama
     */
    public function updateWithItems(array $orderData, array $items): bool {
        $db = self::db();
        $db->beginTransaction();
        
        try {
            // Update order data
            $this->update($orderData);
            
            // ============================================
            // STEP 1: Get old items with FULL DATA before deleting
            // ============================================
            $oldItems = $this->getItems();
            
            // Build mapping: type -> description -> [old_ids in order]
            $oldItemMap = [];
            foreach ($oldItems as $oldItem) {
                $type = $oldItem['item_type'];
                $desc = trim($oldItem['description'] ?? '');
                $key = $type . '::' . $desc;
                
                if (!isset($oldItemMap[$key])) {
                    $oldItemMap[$key] = [];
                }
                $oldItemMap[$key][] = $oldItem['id'];
            }
            
            // ============================================
            // STEP 2: Clear old items
            // ============================================
            $this->clearItems();
            
            // ============================================
            // STEP 3: Add new items and build new mapping
            // ============================================
            $newItemMap = [];
            foreach ($items as $item) {
                $qty = (int)($item['quantity'] ?? 1);
                $days = (int)($item['num_days'] ?? 1);
                $basePrice = (float)($item['base_price'] ?? 0);
                $cashback = (float)($item['cashback'] ?? 0);
                $markupType = $item['markup_type'] ?? 'percentage';
                $markupValue = (float)($item['markup_value'] ?? 0);

                // Markup dihitung dari (harga beli + cashback), bukan harga beli saja
                $itemBase = $qty * $days * $basePrice;
                $itemCashback = $qty * $days * $cashback;
                $markupBase = $itemBase + $itemCashback;
                if ($markupType === 'percentage') {
                    $itemMarkup = $markupBase * ($markupValue / 100);
                } else {
                    $itemMarkup = $markupValue * $qty * $days;
                }

                $item['cashback'] = $cashback;
                $item['markup_amount'] = $itemMarkup;
                $item['final_price'] = $markupBase + $itemMarkup;

                $newId = $this->addItem($item);
                
                $type = $item['item_type'];
                $desc = trim($item['description'] ?? '');
                $key = $type . '::' . $desc;
                
                if (!isset($newItemMap[$key])) {
                    $newItemMap[$key] = [];
                }
                $newItemMap[$key][] = $newId;
            }
            
            // ============================================
            // STEP 4: Smart update attachment references
            // ============================================
            $this->updateAttachmentReferencesSmart($oldItemMap, $newItemMap);
            
            // Recalculate totals
            $this->recalculateTotals();
            
            // Recalculate profit shares
            $this->calculateProfitShares();
            
            $db->commit();
            
            return true;
            
        } catch (Exception $e) {
            $db->rollback();
            throw $e;
        }
    }
    
    /**
     * Smart update attachment references based on description/name matching
     * FIXED v6: Maps by type+description, handles multiple same-name items
     */
    private function updateAttachmentReferencesSmart(array $oldItemMap, array $newItemMap): void {
        $db = self::db();
        
        // Get ALL valid item IDs for this order
        $validItemIds = $db->fetchAll(
            "SELECT id, item_type, description FROM order_items WHERE order_id = ?",
            [$this->id]
        );
        $allValidIds = array_column($validItemIds, 'id');
        
        if (empty($allValidIds)) {
            return;
        }
        
        $globalFallbackId = $allValidIds[0];
        
        // Build ID to ID mapping based on type+description
        $idMapping = [];
        foreach ($oldItemMap as $key => $oldIds) {
            $newIds = $newItemMap[$key] ?? [];
            
            foreach ($oldIds as $index => $oldId) {
                if (!empty($newIds)) {
                    // Map to corresponding new ID by position, or first if not enough
                    $idMapping[$oldId] = $newIds[$index] ?? $newIds[0];
                }
            }
        }
        
        // Define attachment tables and their configurations
        $attachmentConfig = [
            'hotel_guests' => [
                'item_types' => ['hotel'],
                'name_column' => 'hotel_name'
            ],
            'flight_details' => [
                'item_types' => ['flight'],
                'name_column' => 'flight_name'
            ],
            'rental_details' => [
                'item_types' => ['rental'],
                'name_column' => null
            ],
            'vehicle_documents' => [
                'item_types' => ['rental', 'bus', 'towing'],
                'name_column' => null
            ]
        ];
        
        // Build type to new IDs lookup
        $newIdsByType = [];
        foreach ($validItemIds as $item) {
            $newIdsByType[$item['item_type']][] = $item['id'];
        }
        
        // Build description to new ID lookup
        $descToNewId = [];
        foreach ($validItemIds as $item) {
            $key = $item['item_type'] . '::' . trim($item['description'] ?? '');
            if (!isset($descToNewId[$key])) {
                $descToNewId[$key] = [];
            }
            $descToNewId[$key][] = $item['id'];
        }
        
        foreach ($attachmentConfig as $table => $config) {
            if (!$this->tableExists($table)) {
                continue;
            }
            
            $itemTypes = $config['item_types'];
            $nameColumn = $config['name_column'];
            
            // Find best fallback for this table
            $tableFallbackId = $globalFallbackId;
            foreach ($itemTypes as $type) {
                if (!empty($newIdsByType[$type])) {
                    $tableFallbackId = $newIdsByType[$type][0];
                    break;
                }
            }
            
            // Get all attachment records for this order
            $records = $db->fetchAll(
                "SELECT * FROM {$table} WHERE order_id = ?",
                [$this->id]
            );
            
            foreach ($records as $record) {
                $oldItemId = $record['order_item_id'];
                $newItemId = null;
                
                // ============================================
                // Strategy 1: Direct ID mapping (jika old ID ada di mapping)
                // ============================================
                if (isset($idMapping[$oldItemId])) {
                    $newItemId = $idMapping[$oldItemId];
                }
                
                // ============================================
                // Strategy 2: Match by name column if available
                // ============================================
                if (!$newItemId && $nameColumn && !empty($record[$nameColumn])) {
                    $recordName = trim($record[$nameColumn]);
                    
                    // Find new item with matching description
                    foreach ($itemTypes as $type) {
                        $key = $type . '::' . $recordName;
                        if (!empty($descToNewId[$key])) {
                            $newItemId = $descToNewId[$key][0];
                            break;
                        }
                    }
                }
                
                // ============================================
                // Strategy 3: Check if current order_item_id is still valid
                // ============================================
                if (!$newItemId && in_array($oldItemId, $allValidIds)) {
                    $newItemId = $oldItemId; // Still valid, keep it
                }
                
                // ============================================
                // Strategy 4: Use table-specific fallback
                // ============================================
                if (!$newItemId) {
                    $newItemId = $tableFallbackId;
                }
                
                // Update if different
                if ($newItemId && $newItemId != $oldItemId) {
                    $db->query(
                        "UPDATE {$table} SET order_item_id = ? WHERE id = ?",
                        [$newItemId, $record['id']]
                    );
                }
            }
        }
    }
    
    /**
     * Check if a table exists in the database
     */
    private function tableExists(string $table): bool {
        try {
            self::db()->query("SELECT 1 FROM {$table} LIMIT 1");
            return true;
        } catch (Exception $e) {
            return false;
        }
    }
    
    /**
     * Static method to fix all orphan attachments for an order
     * Can be called manually: Order::fixOrphanAttachments($orderId)
     */
    public static function fixOrphanAttachments(int $orderId): int {
        $db = self::db();
        $fixed = 0;
        
        // Get valid items for this order with their descriptions
        $validItems = $db->fetchAll(
            "SELECT id, item_type, description FROM order_items WHERE order_id = ?",
            [$orderId]
        );
        
        if (empty($validItems)) {
            return 0;
        }
        
        $allValidIds = array_column($validItems, 'id');
        $itemsByType = [];
        $itemsByDesc = [];
        
        foreach ($validItems as $item) {
            $itemsByType[$item['item_type']][] = $item['id'];
            $key = $item['item_type'] . '::' . trim($item['description'] ?? '');
            if (!isset($itemsByDesc[$key])) {
                $itemsByDesc[$key] = [];
            }
            $itemsByDesc[$key][] = $item['id'];
        }
        
        $globalFallback = $allValidIds[0];
        
        // Define attachment tables
        $attachmentConfig = [
            'hotel_guests' => ['types' => ['hotel'], 'name_col' => 'hotel_name'],
            'flight_details' => ['types' => ['flight'], 'name_col' => 'flight_name'],
            'rental_details' => ['types' => ['rental'], 'name_col' => null],
            'vehicle_documents' => ['types' => ['rental', 'bus', 'towing'], 'name_col' => null]
        ];
        
        foreach ($attachmentConfig as $table => $config) {
            try {
                $types = $config['types'];
                $nameCol = $config['name_col'];
                
                // Find fallback for this table
                $fallbackId = $globalFallback;
                foreach ($types as $type) {
                    if (!empty($itemsByType[$type])) {
                        $fallbackId = $itemsByType[$type][0];
                        break;
                    }
                }
                
                // Get orphan records
                $placeholders = implode(',', array_fill(0, count($allValidIds), '?'));
                $orphans = $db->fetchAll(
                    "SELECT * FROM {$table} 
                     WHERE order_id = ? 
                     AND (order_item_id IS NULL OR order_item_id NOT IN ({$placeholders}))",
                    array_merge([$orderId], $allValidIds)
                );
                
                foreach ($orphans as $orphan) {
                    $newId = $fallbackId;
                    
                    // Try to match by name if available
                    if ($nameCol && !empty($orphan[$nameCol])) {
                        $name = trim($orphan[$nameCol]);
                        foreach ($types as $type) {
                            $key = $type . '::' . $name;
                            if (!empty($itemsByDesc[$key])) {
                                $newId = $itemsByDesc[$key][0];
                                break;
                            }
                        }
                    }
                    
                    $db->query(
                        "UPDATE {$table} SET order_item_id = ? WHERE id = ?",
                        [$newId, $orphan['id']]
                    );
                    $fixed++;
                }
            } catch (Exception $e) {
                // Table might not exist
            }
        }
        
        return $fixed;
    }
}