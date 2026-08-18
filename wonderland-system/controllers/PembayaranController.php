<?php
/**
 * ================================================================
 * TRAVEL MANAGEMENT SYSTEM - Pembayaran Controller
 * ================================================================
 * Tracking pembayaran company-wide (terpisah dari halaman pembayaran
 * per-order yang sudah ada di /orders/{id}/payment).
 */

class PembayaranController {

    /**
     * Company-wide payment tracking
     * GET /pembayaran
     */
    public function index(): void {
        $companyId = Session::companyId();
        $page = (int) ($_GET['page'] ?? 1);
        $perPage = PAGINATION_PER_PAGE;

        $filters = [
            'search' => trim($_GET['search'] ?? ''),
            'payment_status' => $_GET['payment_status'] ?? ''
        ];

        $where = "o.company_id = ?";
        $params = [$companyId];

        if (!empty($filters['payment_status'])) {
            $where .= " AND o.payment_status = ?";
            $params[] = $filters['payment_status'];
        }

        if (!empty($filters['search'])) {
            $where .= " AND (o.order_number LIKE ? OR c.name LIKE ?)";
            $params[] = "%{$filters['search']}%";
            $params[] = "%{$filters['search']}%";
        }

        $countSql = "SELECT COUNT(*) FROM orders o LEFT JOIN clients c ON o.client_id = c.id WHERE {$where}";
        $total = (int) db()->fetchColumn($countSql, $params);

        $offset = ($page - 1) * $perPage;

        $sql = "SELECT o.*, c.name as client_name,
                       (SELECT MAX(payment_date) FROM order_payments WHERE order_id = o.id AND status = 'posted') as last_payment_date
                FROM orders o
                LEFT JOIN clients c ON o.client_id = c.id
                WHERE {$where}
                ORDER BY
                    CASE o.payment_status WHEN 'unpaid' THEN 0 WHEN 'partial' THEN 1 ELSE 2 END,
                    o.created_at DESC
                LIMIT {$perPage} OFFSET {$offset}";

        $orders = db()->fetchAll($sql, $params);

        $pagination = [
            'data' => $orders,
            'total' => $total,
            'per_page' => $perPage,
            'current_page' => $page,
            'last_page' => ceil($total / $perPage) ?: 1,
            'from' => $total > 0 ? $offset + 1 : 0,
            'to' => min($offset + $perPage, $total)
        ];

        $summary = db()->fetchOne(
            "SELECT
                COALESCE(SUM(total_final_price), 0) as total_billed,
                COALESCE(SUM(paid_amount), 0) as total_paid,
                COALESCE(SUM(GREATEST(total_final_price - paid_amount, 0)), 0) as total_outstanding,
                SUM(CASE WHEN payment_status = 'unpaid' THEN 1 ELSE 0 END) as count_unpaid,
                SUM(CASE WHEN payment_status = 'partial' THEN 1 ELSE 0 END) as count_partial,
                SUM(CASE WHEN payment_status = 'paid' THEN 1 ELSE 0 END) as count_paid
             FROM orders WHERE company_id = ?",
            [$companyId]
        );

        $recentPayments = db()->fetchAll(
            "SELECT op.*, o.order_number, c.name as client_name
             FROM order_payments op
             JOIN orders o ON op.order_id = o.id
             LEFT JOIN clients c ON o.client_id = c.id
             WHERE op.company_id = ? AND op.status = 'posted'
             ORDER BY op.payment_date DESC, op.id DESC
             LIMIT 15",
            [$companyId]
        );

        $data = [
            'pageTitle' => 'Pembayaran',
            'pageHeader' => true,
            'pageSubtitle' => 'Tracking pembayaran seluruh pesanan',
            'breadcrumbs' => [
                ['label' => 'Pembayaran']
            ],
            'orders' => $orders,
            'pagination' => $pagination,
            'filters' => $filters,
            'summary' => $summary,
            'recentPayments' => $recentPayments,
            'paymentStatuses' => PAYMENT_STATUSES
        ];

        render('pembayaran/index', $data);
    }
}
