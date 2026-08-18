<?php
/**
 * ================================================================
 * TRAVEL MANAGEMENT SYSTEM - Dashboard Controller
 * ================================================================
 * 
 * UPDATED: Added redirect for role attachment
 * UPDATED: Added financial summary
 */

require_once MODELS_PATH . '/User.php';
require_once MODELS_PATH . '/Company.php';

class DashboardController {
    
    /**
     * Dashboard index
     */
    public function index(): void {
        // ============================================
        // Redirect attachment role ke dashboard khusus
        // Gunakan flag untuk mencegah infinite loop
        // ============================================
        if (!isset($_GET['no_redirect'])) {
            $userRole = $_SESSION['user']['role'] ?? null;
            if ($userRole === 'attachment') {
                redirect('/attachment-dashboard');
                return;
            }
        }
        
        $companyId = Session::companyId();
        
        if (!$companyId) {
            // Redirect to select company if superadmin
            if (Session::isSuperAdmin()) {
                $companies = Session::getUserCompanies();
                if (!empty($companies)) {
                    Session::setCompanyId($companies[0]['id']);
                    $companyId = $companies[0]['id'];
                }
            }
        }
        
        // Get financial summary
        $financial = $this->getFinancialSummary($companyId);
        
        $data = [
            'pageTitle' => 'Dashboard',
            'pageHeader' => true,
            'pageSubtitle' => 'Selamat datang kembali, ' . Session::user()['name'],
            'stats' => $this->getStats($companyId),
            'recentOrders' => $this->getRecentOrders($companyId),
            'monthlyRevenue' => $this->getMonthlyRevenue($companyId),
            'reservationTrend' => $this->getReservationTrend($companyId),
            'financial' => $financial
        ];
        
        render('dashboard/index', $data);
    }
    
    /**
     * Get financial summary for dashboard
     */
    private function getFinancialSummary(int $companyId): array {
        $financial = [
            'cash_balance' => 0,
            'receivables' => 0,
            'payables_loan' => 0,
            'pending_expenses' => 0
        ];
        
        try {
            // Load AccountingHelper jika ada
            $helperPath = HELPERS_PATH . '/AccountingHelper.php';
            if (file_exists($helperPath)) {
                require_once $helperPath;
            }
            
            if (function_exists('getFinancialDashboard')) {
                $financial = getFinancialDashboard($companyId);
            } else {
                // Fallback - ambil data basic
                
                // 1. Cash Balance
                $cashResult = db()->fetchOne(
                    "SELECT COALESCE(SUM(balance), 0) as total FROM bank_cash WHERE company_id = ? AND is_active = 1",
                    [$companyId]
                );
                $financial['cash_balance'] = (float)($cashResult['total'] ?? 0);
                
                // 2. Receivables (Piutang)
                $recResult = db()->fetchOne(
                    "SELECT COALESCE(SUM(total_final_price - paid_amount), 0) as total
                     FROM orders 
                     WHERE company_id = ? 
                     AND payment_status IN ('unpaid', 'partial')
                     AND status NOT IN ('cancelled', 'draft')",
                    [$companyId]
                );
                $financial['receivables'] = (float)($recResult['total'] ?? 0);
                
                // 3. Hutang pinjaman
                try {
                    $loanResult = db()->fetchOne(
                        "SELECT COALESCE(SUM(amount - paid_amount), 0) as total
                         FROM loans 
                         WHERE company_id = ? 
                         AND loan_type = 'received'
                         AND status = 'active'",
                        [$companyId]
                    );
                    $financial['payables_loan'] = (float)($loanResult['total'] ?? 0);
                } catch (Exception $e) {
                    $financial['payables_loan'] = 0;
                }
                
                // 4. Pending expenses (belum bayar vendor)
                try {
                    $pendingResult = db()->fetchOne(
                        "SELECT COALESCE(SUM(oi.base_price * oi.quantity * COALESCE(oi.num_days, 1)), 0) as total
                         FROM order_items oi
                         JOIN orders o ON oi.order_id = o.id
                         WHERE o.company_id = ?
                         AND o.status NOT IN ('cancelled', 'draft')
                         AND (oi.expense_status IS NULL OR oi.expense_status = 'pending')",
                        [$companyId]
                    );
                    $financial['pending_expenses'] = (float)($pendingResult['total'] ?? 0);
                } catch (Exception $e) {
                    $financial['pending_expenses'] = 0;
                }
            }
        } catch (Exception $e) {
            // Jika error total, return default
        }
        
        return $financial;
    }
    
    /**
     * Switch company
     */
    public function switchCompany(): void {
        $companyId = (int) ($_POST['company_id'] ?? 0);
        
        if (!$companyId) {
            Session::flash('error', 'Perusahaan tidak valid.');
            redirect('/dashboard');
            return;
        }
        
        // Verify access
        $user = User::find(Session::userId());
        
        if (!$user->hasCompanyAccess($companyId)) {
            Session::flash('error', 'Anda tidak memiliki akses ke perusahaan ini.');
            redirect('/dashboard');
            return;
        }
        
        Session::setCompanyId($companyId);
        
        // Get company name
        $company = Company::find($companyId);
        Session::flash('success', 'Beralih ke ' . $company->name);
        
        redirect('/dashboard');
    }
    
    /**
     * Get dashboard stats
     */
    private function getStats(int $companyId): array {
        // Today's date range
        $today = date('Y-m-d');
        $monthStart = date('Y-m-01');
        $monthEnd = date('Y-m-t');
        
        // Total orders this month
        $totalOrders = (int) db()->fetchColumn(
            "SELECT COUNT(*) FROM orders 
             WHERE company_id = ? AND order_date BETWEEN ? AND ?",
            [$companyId, $monthStart, $monthEnd]
        );
        
        // Total revenue this month
        $totalRevenue = (float) db()->fetchColumn(
            "SELECT COALESCE(SUM(total_final_price), 0) FROM orders 
             WHERE company_id = ? AND order_date BETWEEN ? AND ? 
             AND status NOT IN ('draft', 'cancelled')",
            [$companyId, $monthStart, $monthEnd]
        );
        
        // Total profit this month
        $totalProfit = (float) db()->fetchColumn(
            "SELECT COALESCE(SUM(total_profit), 0) FROM orders 
             WHERE company_id = ? AND order_date BETWEEN ? AND ?
             AND status NOT IN ('draft', 'cancelled')",
            [$companyId, $monthStart, $monthEnd]
        );
        
        // Pending payments
        $pendingPayments = (float) db()->fetchColumn(
            "SELECT COALESCE(SUM(total_final_price - paid_amount), 0) FROM orders 
             WHERE company_id = ? AND payment_status != 'paid' 
             AND status NOT IN ('draft', 'cancelled')",
            [$companyId]
        );
        
        // Count by status
        $statusCounts = db()->fetchAll(
            "SELECT status, COUNT(*) as count FROM orders 
             WHERE company_id = ? GROUP BY status",
            [$companyId]
        );
        
        $byStatus = [];
        foreach ($statusCounts as $row) {
            $byStatus[$row['status']] = (int) $row['count'];
        }
        
        // Total clients
        $totalClients = (int) db()->fetchColumn(
            "SELECT COUNT(*) FROM clients WHERE company_id = ?",
            [$companyId]
        );
        
        // Growth comparison (vs last month)
        $lastMonthStart = date('Y-m-01', strtotime('-1 month'));
        $lastMonthEnd = date('Y-m-t', strtotime('-1 month'));
        
        $lastMonthRevenue = (float) db()->fetchColumn(
            "SELECT COALESCE(SUM(total_final_price), 0) FROM orders 
             WHERE company_id = ? AND order_date BETWEEN ? AND ?
             AND status NOT IN ('draft', 'cancelled')",
            [$companyId, $lastMonthStart, $lastMonthEnd]
        );
        
        $revenueGrowth = $lastMonthRevenue > 0 
            ? (($totalRevenue - $lastMonthRevenue) / $lastMonthRevenue) * 100 
            : 0;
        
        return [
            'total_orders' => $totalOrders,
            'total_revenue' => $totalRevenue,
            'total_profit' => $totalProfit,
            'pending_payments' => $pendingPayments,
            'total_clients' => $totalClients,
            'by_status' => $byStatus,
            'revenue_growth' => round($revenueGrowth, 1)
        ];
    }
    
    /**
     * Get recent orders
     */
    private function getRecentOrders(int $companyId, int $limit = 5): array {
        return db()->fetchAll(
            "SELECT o.*, c.name as client_name 
             FROM orders o
             LEFT JOIN clients c ON o.client_id = c.id
             WHERE o.company_id = ?
             ORDER BY o.created_at DESC
             LIMIT {$limit}",
            [$companyId]
        );
    }
    
    /**
     * Get monthly revenue for chart
     */
    private function getMonthlyRevenue(int $companyId): array {
        $year = date('Y');
        
        $data = db()->fetchAll(
            "SELECT 
                MONTH(order_date) as month,
                COALESCE(SUM(total_final_price), 0) as revenue,
                COALESCE(SUM(total_profit), 0) as profit
             FROM orders 
             WHERE company_id = ? AND YEAR(order_date) = ?
             AND status NOT IN ('draft', 'cancelled')
             GROUP BY MONTH(order_date)
             ORDER BY month",
            [$companyId, $year]
        );
        
        // Fill all months
        $monthly = [];
        for ($i = 1; $i <= 12; $i++) {
            $monthly[$i] = ['month' => $i, 'revenue' => 0, 'profit' => 0];
        }
        
        foreach ($data as $row) {
            $monthly[(int)$row['month']] = [
                'month' => (int)$row['month'],
                'revenue' => (float)$row['revenue'],
                'profit' => (float)$row['profit']
            ];
        }
        
        return array_values($monthly);
    }

    /**
     * Reservation count trend for the last 6 months (rolling window),
     * adapted from AnalysisController::getChartData()'s 6-month pattern.
     */
    private function getReservationTrend(int $companyId): array {
        return db()->fetchAll(
            "SELECT DATE_FORMAT(order_date, '%Y-%m') as period,
                    DATE_FORMAT(order_date, '%b %Y') as label,
                    COUNT(*) as total
             FROM orders
             WHERE company_id = ? AND order_date >= DATE_SUB(CURDATE(), INTERVAL 6 MONTH)
             GROUP BY DATE_FORMAT(order_date, '%Y-%m')
             ORDER BY period",
            [$companyId]
        );
    }

    /**
     * API: Get dashboard stats
     */
    public function apiStats(): void {
        $companyId = Session::companyId();
        $stats = $this->getStats($companyId);
        jsonSuccess($stats);
    }
    
    /**
     * API: Get chart data
     */
    public function getChartData(string $type): void {
        $companyId = Session::companyId();
        
        switch ($type) {
            case 'revenue':
                $data = $this->getMonthlyRevenue($companyId);
                break;
                
            case 'orders-by-type':
                $data = db()->fetchAll(
                    "SELECT oi.item_type, COUNT(DISTINCT oi.order_id) as count
                     FROM order_items oi
                     JOIN orders o ON oi.order_id = o.id
                     WHERE o.company_id = ? AND YEAR(o.order_date) = YEAR(NOW())
                     GROUP BY oi.item_type",
                    [$companyId]
                );
                break;
                
            case 'orders-by-status':
                $data = db()->fetchAll(
                    "SELECT status, COUNT(*) as count FROM orders 
                     WHERE company_id = ? GROUP BY status",
                    [$companyId]
                );
                break;
                
            default:
                jsonError('Invalid chart type', 400);
                return;
        }
        
        jsonSuccess($data);
    }
}