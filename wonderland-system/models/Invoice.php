<?php
/**
 * ================================================================
 * WONDERLAND TRAVEL - Invoice Model
 * Invoice resmi dengan item tersendiri, terpisah dari order_items.
 * Dibuat dari sebuah order (pre-filled dari order_items) tapi
 * setelah itu independen - edit di invoice tidak mengubah order.
 * ================================================================
 */

require_once MODELS_PATH . '/Model.php';

class Invoice extends Model {

    protected static string $table = 'invoices';
    protected static string $primaryKey = 'id';
    protected static string $orderBy = 'created_at';
    protected static string $orderDir = 'DESC';

    protected static array $fillable = [
        'company_id',
        'order_id',
        'invoice_number',
        'invoice_date',
        'due_date',
        'discount',
        'subtotal',
        'total',
        'paid_amount',
        'payment_status',
        'notes',
        'created_by'
    ];

    /**
     * Generate next invoice number for a company, using the
     * doc_number_format_invoice setting (Settings > Dokumen).
     */
    public static function generateInvoiceNumber(int $companyId): string {
        $format = getCompanySetting($companyId, 'doc_number_format_invoice', 'INV/{YY}{MM}/{NUM}');

        $year = date('Y');
        $month = date('m');
        $yy = date('y');

        $lastNumber = self::db()->fetchColumn(
            "SELECT MAX(CAST(SUBSTRING_INDEX(invoice_number, '/', -1) AS UNSIGNED))
             FROM invoices
             WHERE company_id = ? AND YEAR(created_at) = ? AND MONTH(created_at) = ?",
            [$companyId, $year, $month]
        );

        $nextNumber = ($lastNumber ?? 0) + 1;
        $num = str_pad($nextNumber, 4, '0', STR_PAD_LEFT);

        return str_replace(
            ['{YEAR}', '{YY}', '{MONTH}', '{MM}', '{NUM}'],
            [$year, $yy, $month, $month, $num],
            $format
        );
    }

    /**
     * Get invoice items
     */
    public function getItems(): array {
        return self::db()->fetchAll(
            "SELECT * FROM invoice_items WHERE invoice_id = ? ORDER BY sort_order, id",
            [$this->id]
        );
    }

    /**
     * Add an invoice item
     */
    public function addItem(array $data): int {
        $data['invoice_id'] = $this->id;
        return self::db()->insert('invoice_items', $data);
    }

    /**
     * Clear all invoice items
     */
    public function clearItems(): void {
        self::db()->delete('invoice_items', 'invoice_id = ?', [$this->id]);
    }

    /**
     * Create an invoice together with its line items in one call.
     * $items: array of ['name' => string, 'quantity' => int, 'unit_price' => float]
     */
    public static function createWithItems(int $companyId, int $orderId, array $invoiceData, array $items): self {
        $subtotal = 0;
        foreach ($items as $item) {
            $subtotal += (float)$item['quantity'] * (float)$item['unit_price'];
        }
        $discount = (float)($invoiceData['discount'] ?? 0);
        $total = max(0, $subtotal - $discount);

        $invoice = self::create(array_merge($invoiceData, [
            'company_id' => $companyId,
            'order_id' => $orderId,
            'invoice_number' => self::generateInvoiceNumber($companyId),
            'subtotal' => $subtotal,
            'total' => $total,
            'paid_amount' => 0,
            'payment_status' => 'unpaid'
        ]));

        $sort = 0;
        foreach ($items as $item) {
            $qty = (float)$item['quantity'];
            $unitPrice = (float)$item['unit_price'];
            $invoice->addItem([
                'name' => $item['name'],
                'quantity' => $qty,
                'unit_price' => $unitPrice,
                'subtotal' => $qty * $unitPrice,
                'sort_order' => $sort++
            ]);
        }

        return $invoice;
    }

    /**
     * Recompute paid_amount/payment_status from order_payments,
     * mirroring Order::updatePaidAmount().
     */
    public function updatePaidAmount(): void {
        $totalPaid = (float) self::db()->fetchColumn(
            "SELECT COALESCE(SUM(amount), 0) FROM order_payments WHERE invoice_id = ? AND status = 'posted'",
            [$this->id]
        );

        $paymentStatus = 'unpaid';
        if ($totalPaid >= $this->total) {
            $paymentStatus = 'paid';
        } elseif ($totalPaid > 0) {
            $paymentStatus = 'partial';
        }

        $this->update([
            'paid_amount' => $totalPaid,
            'payment_status' => $paymentStatus
        ]);
    }

    /**
     * Get payment history for this invoice
     */
    public function getPayments(): array {
        return self::db()->fetchAll(
            "SELECT op.*, bc.name as bank_cash_name, u.name as created_by_name
             FROM order_payments op
             LEFT JOIN bank_cash bc ON op.bank_cash_id = bc.id
             LEFT JOIN users u ON op.created_by = u.id
             WHERE op.invoice_id = ?
             ORDER BY op.payment_date DESC, op.id DESC",
            [$this->id]
        );
    }

    /**
     * Get invoices for a client (joined through orders), most recent first
     */
    public static function getForClient(int $clientId, int $limit = 10): array {
        return self::db()->fetchAll(
            "SELECT i.*, o.order_number
             FROM invoices i
             JOIN orders o ON i.order_id = o.id
             WHERE o.client_id = ?
             ORDER BY i.created_at DESC
             LIMIT {$limit}",
            [$clientId]
        );
    }
}
