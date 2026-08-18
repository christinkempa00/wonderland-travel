<?php
/**
 * ================================================================
 * TRAVEL MANAGEMENT SYSTEM - Analysis Controller
 * ================================================================
 * 
 * Halaman Analisa Keuangan - Grouped by Event
 * PROFIT = Tagihan - Modal (bukan sisa pembayaran)
 * 
 * MODIFIKASI v2:
 * - Menambahkan unit_price (harga satuan modal)
 * - Menambahkan total_quantity dan total_days
 * - Untuk perbandingan dengan SBM dan kalkulasi markup
 * 
 * File: controllers/AnalysisController.php
 */

class AnalysisController {
    
    /**
     * Status Pemberkasan
     */
    public static $filingStatuses = [
        'none' => ['label' => 'Belum', 'color' => 'secondary'],
        'incomplete' => ['label' => 'Belum Lengkap', 'color' => 'danger'],
        'processing' => ['label' => 'Proses', 'color' => 'warning'],
        'complete' => ['label' => 'Lengkap', 'color' => 'info'],
        'submitted' => ['label' => 'Diserahkan', 'color' => 'success']
    ];
    
    /**
     * Halaman utama analisa keuangan
     */
    public function index(): void {
        $companyId = Session::companyId();
        
        // Filters
        $filters = [
            'search' => trim($_GET['search'] ?? ''),
            'event_name' => $_GET['event_name'] ?? '',
            'payment_status' => $_GET['payment_status'] ?? '',
            'filing_status' => $_GET['filing_status'] ?? '',
            'date_from' => $_GET['date_from'] ?? '',
            'date_to' => $_GET['date_to'] ?? ''
        ];
        
        // Build where clause
        $where = "o.company_id = ?";
        $params = [$companyId];
        
        if (!empty($filters['event_name'])) {
            $where .= " AND o.event_name = ?";
            $params[] = $filters['event_name'];
        }
        
        if (!empty($filters['payment_status'])) {
            $where .= " AND o.payment_status = ?";
            $params[] = $filters['payment_status'];
        }
        
        if (!empty($filters['filing_status']) && $this->hasFilingColumn()) {
            $where .= " AND o.filing_status = ?";
            $params[] = $filters['filing_status'];
        }
        
        if (!empty($filters['date_from'])) {
            $where .= " AND o.order_date >= ?";
            $params[] = $filters['date_from'];
        }
        
        if (!empty($filters['date_to'])) {
            $where .= " AND o.order_date <= ?";
            $params[] = $filters['date_to'];
        }
        
        if (!empty($filters['search'])) {
            $where .= " AND o.event_name LIKE ?";
            $params[] = "%{$filters['search']}%";
        }
        
        // Check columns
        $hasSbm = $this->hasSbmColumns();
        $hasFiling = $this->hasFilingColumn();
        
        // Build select
        $sbmSelect = $hasSbm 
            ? "COALESCE(o.sbm_price, 0) as sbm_price, COALESCE(o.sbm_markup_percent, 0) as sbm_markup_percent,"
            : "0 as sbm_price, 0 as sbm_markup_percent,";
        
        $filingSelect = $hasFiling
            ? "COALESCE(o.filing_status, 'none') as filing_status,"
            : "'none' as filing_status,";
        
        // Get orders with item_type from order_items
        // PROFIT = tagihan - modal (bukan sisa pembayaran)
        // Order by: event dengan tanggal terbaru di atas
        //
        // MODIFIKASI v2: Tambahkan unit_price, total_quantity, total_days
        // untuk tampilan harga satuan di view
        // MODIFIKASI v4: Tambahkan expense_status dan expense_paid untuk tracking bayar vendor
        $sql = "SELECT
                    o.id,
                    o.order_number,
                    o.order_date,
                    o.event_name,
                    o.status,
                    o.payment_status,
                    {$filingSelect}
                    COALESCE(o.total_base_price, 0) as modal,
                    COALESCE(o.total_markup, 0) as markup,
                    COALESCE(o.total_final_price, 0) as tagihan,
                    COALESCE(o.paid_amount, 0) as dibayar,
                    {$sbmSelect}
                    (COALESCE(o.total_final_price, 0) - COALESCE(o.total_base_price, 0)) as profit,
                    
                    -- Data item (item_type dari item pertama)
                    (SELECT oi.item_type FROM order_items oi WHERE oi.order_id = o.id ORDER BY oi.id LIMIT 1) as item_type,
                    
                    -- HARGA SATUAN: base_price dari item pertama
                    (SELECT COALESCE(oi.base_price, 0) FROM order_items oi WHERE oi.order_id = o.id ORDER BY oi.id LIMIT 1) as unit_price,
                    
                    -- TOTAL QUANTITY: jumlah semua quantity dari items
                    (SELECT COALESCE(SUM(oi.quantity), 1) FROM order_items oi WHERE oi.order_id = o.id) as total_quantity,
                    
                    -- TOTAL DAYS: ambil num_days dari item pertama (atau max jika berbeda)
                    (SELECT COALESCE(MAX(oi.num_days), 1) FROM order_items oi WHERE oi.order_id = o.id) as total_days,
                    
                    -- EXPENSE STATUS: paid jika semua items sudah paid, otherwise pending
                    CASE 
                        WHEN (SELECT COUNT(*) FROM order_items oi2 WHERE oi2.order_id = o.id) = 0 THEN 'pending'
                        WHEN (SELECT COUNT(*) FROM order_items oi2 WHERE oi2.order_id = o.id AND (oi2.expense_status IS NULL OR oi2.expense_status = 'pending')) = 0 THEN 'paid'
                        ELSE 'pending'
                    END as expense_status,
                    
                    -- EXPENSE PAID: total yang sudah dibayar ke vendor
                    COALESCE(o.expense_paid_amount, 0) as expense_paid,
                    
                    -- Group ordering
                    (SELECT MAX(o2.order_date) FROM orders o2 WHERE o2.event_name = o.event_name AND o2.company_id = o.company_id) as group_latest_date
                FROM orders o
                WHERE {$where}
                ORDER BY group_latest_date DESC, o.event_name ASC, o.order_date DESC, o.id DESC";
        
        $allOrders = db()->fetchAll($sql, $params);
        
        // Group orders by event_name
        $groupedOrders = [];
        $groupTotals = [];

        // Filing status counts
        $filingCounts = ['none' => 0, 'incomplete' => 0, 'processing' => 0, 'complete' => 0, 'submitted' => 0];

        foreach ($allOrders as $order) {
            $eventKey = $order['event_name'] ?: '(Tanpa Nama Event)';

            if (!isset($groupedOrders[$eventKey])) {
                $groupedOrders[$eventKey] = [];
                $groupTotals[$eventKey] = [
                    'modal' => 0, 'sbm' => 0, 'markup' => 0,
                    'tagihan' => 0, 'dibayar' => 0, 'profit' => 0, 'count' => 0,
                    'latest_date' => $order['group_latest_date'] ?? $order['order_date']
                ];
            }

            $groupedOrders[$eventKey][] = $order;
            $groupTotals[$eventKey]['modal'] += (float)$order['modal'];
            $groupTotals[$eventKey]['sbm'] += (float)$order['sbm_price'];
            $groupTotals[$eventKey]['markup'] += (float)$order['markup'];
            $groupTotals[$eventKey]['tagihan'] += (float)$order['tagihan'];
            $groupTotals[$eventKey]['dibayar'] += (float)$order['dibayar'];
            $groupTotals[$eventKey]['profit'] += (float)$order['profit'];
            $groupTotals[$eventKey]['count']++;
            
            // Count filing status
            $fs = $order['filing_status'] ?? 'none';
            if (isset($filingCounts[$fs])) {
                $filingCounts[$fs]++;
            }
        }
        
        // Grand totals
        $summary = [
            'total_orders' => count($allOrders),
            'total_groups' => count($groupedOrders),
            'total_modal' => 0, 'total_sbm' => 0, 'total_markup' => 0,
            'total_tagihan' => 0, 'total_dibayar' => 0, 'total_profit' => 0,
            'filing_counts' => $filingCounts
        ];
        
        foreach ($groupTotals as $t) {
            $summary['total_modal'] += $t['modal'];
            $summary['total_sbm'] += $t['sbm'];
            $summary['total_markup'] += $t['markup'];
            $summary['total_tagihan'] += $t['tagihan'];
            $summary['total_dibayar'] += $t['dibayar'];
            $summary['total_profit'] += $t['profit'];
        }
        
        $summary['avg_markup_percent'] = ($summary['total_modal'] > 0) 
            ? ($summary['total_markup'] / $summary['total_modal']) * 100 : 0;
        $summary['payment_progress'] = ($summary['total_tagihan'] > 0) 
            ? ($summary['total_dibayar'] / $summary['total_tagihan']) * 100 : 0;
        
        // Filing progress (processing + complete + submitted = sedang/sudah diproses)
        $filingInProgress = $filingCounts['processing'] + $filingCounts['complete'] + $filingCounts['submitted'];
        $summary['filing_progress'] = ($summary['total_orders'] > 0)
            ? ($filingInProgress / $summary['total_orders']) * 100 : 0;
        $summary['filing_in_progress'] = $filingInProgress;
        
        // Event name options (for filter dropdown)
        $eventNameOptions = [];
        $eventResults = db()->fetchAll(
            "SELECT DISTINCT event_name FROM orders WHERE company_id = ? AND event_name IS NOT NULL AND event_name != '' ORDER BY event_name",
            [$companyId]
        );
        foreach ($eventResults as $row) {
            $eventNameOptions[] = $row['event_name'];
        }
        
        // ========== TAMBAHAN: Get Bank/Cash accounts untuk expense modal ==========
        $bankCashAccounts = [];
        try {
            $bankCashAccounts = db()->fetchAll(
                "SELECT id, name, type, COALESCE(balance, 0) as current_balance 
                 FROM bank_cash 
                 WHERE company_id = ? AND is_active = 1 
                 ORDER BY type, name",
                [$companyId]
            );
        } catch (Exception $e) {
            // Tabel mungkin belum ada, abaikan error
        }
        // ========== END TAMBAHAN ==========
        
        $data = [
            'pageTitle' => 'Analisa Keuangan',
            'pageHeader' => true,
            'groupedOrders' => $groupedOrders,
            'groupTotals' => $groupTotals,
            'summary' => $summary,
            'filters' => $filters,
            'paymentStatuses' => PAYMENT_STATUSES,
            'filingStatuses' => self::$filingStatuses,
            'itemTypes' => ITEM_TYPES,
            'eventNameOptions' => $eventNameOptions,
            'hasSbmColumns' => $hasSbm,
            'hasFilingColumn' => $hasFiling,
            'bankCashAccounts' => $bankCashAccounts
        ];
        
        render('analysis/index', $data);
    }
    
    private function hasSbmColumns(): bool {
        try {
            $result = db()->fetchOne("SHOW COLUMNS FROM orders LIKE 'sbm_price'");
            return !empty($result);
        } catch (Exception $e) {
            return false;
        }
    }
    
    private function hasFilingColumn(): bool {
        try {
            $result = db()->fetchOne("SHOW COLUMNS FROM orders LIKE 'filing_status'");
            return !empty($result);
        } catch (Exception $e) {
            return false;
        }
    }
    
     /**
     * Save checklist status (Clear/Diajukan) via AJAX
     * Endpoint: POST /analysis/save-checklist
     */
    public function saveChecklist(): void {
        header('Content-Type: application/json');
        
        try {
            $companyId = Session::companyId();
            
            // Get input
            $supportKey = $_POST['support_key'] ?? '';
            $supportName = $_POST['support_name'] ?? '';
            $type = $_POST['type'] ?? ''; // 'clear' or 'submitted'
            $value = (int)($_POST['value'] ?? 0); // 1 or 0
            $year = $_POST['year'] ?? '';
            $month = $_POST['month'] ?? '';
            
            // Validate
            if (empty($supportKey) || empty($supportName) || !in_array($type, ['clear', 'submitted'])) {
                throw new Exception('Parameter tidak lengkap');
            }
            
            // Normalize empty string to NULL for proper unique key handling
            $yearVal = !empty($year) ? $year : null;
            $monthVal = !empty($month) ? $month : null;
            
            // Check if record exists
            $existing = db()->fetchOne(
                "SELECT id FROM report_checklist 
                 WHERE company_id = ? AND support_key = ? 
                   AND (year = ? OR (year IS NULL AND ? IS NULL))
                   AND (month = ? OR (month IS NULL AND ? IS NULL))",
                [$companyId, $supportKey, $yearVal, $yearVal, $monthVal, $monthVal]
            );
            
            $column = ($type === 'clear') ? 'is_clear' : 'is_submitted';
            
            if ($existing) {
                // Update existing record
                db()->query(
                    "UPDATE report_checklist SET {$column} = ?, updated_at = NOW() WHERE id = ?",
                    [$value, $existing['id']]
                );
            } else {
                // Insert new record
                $data = [
                    'company_id' => $companyId,
                    'support_key' => $supportKey,
                    'support_name' => $supportName,
                    'year' => $yearVal,
                    'month' => $monthVal,
                    $column => $value,
                    'created_at' => date('Y-m-d H:i:s')
                ];
                db()->insert('report_checklist', $data);
            }
            
            echo json_encode(['success' => true, 'message' => 'Checklist disimpan']);
            
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
    }
    
    /**
     * Save catatan (notes) per row via AJAX
     * Endpoint: POST /analysis/save-catatan
     */
    public function saveCatatan(): void {
        header('Content-Type: application/json');
        
        try {
            $companyId = Session::companyId();
            
            // Get input
            $supportKey = $_POST['support_key'] ?? '';
            $supportName = $_POST['support_name'] ?? '';
            $catatan = trim($_POST['catatan'] ?? '');
            $year = $_POST['year'] ?? '';
            $month = $_POST['month'] ?? '';
            
            // Validate
            if (empty($supportKey) || empty($supportName)) {
                throw new Exception('Parameter tidak lengkap');
            }
            
            // Limit catatan length
            $catatan = mb_substr($catatan, 0, 500);
            
            // Normalize empty string to NULL
            $yearVal = !empty($year) ? $year : null;
            $monthVal = !empty($month) ? $month : null;
            
            // Check if record exists
            $existing = db()->fetchOne(
                "SELECT id FROM report_checklist 
                 WHERE company_id = ? AND support_key = ? 
                   AND (year = ? OR (year IS NULL AND ? IS NULL))
                   AND (month = ? OR (month IS NULL AND ? IS NULL))",
                [$companyId, $supportKey, $yearVal, $yearVal, $monthVal, $monthVal]
            );
            
            if ($existing) {
                // Update existing record
                db()->query(
                    "UPDATE report_checklist SET catatan = ?, updated_at = NOW() WHERE id = ?",
                    [$catatan, $existing['id']]
                );
            } else {
                // Insert new record
                $data = [
                    'company_id' => $companyId,
                    'support_key' => $supportKey,
                    'support_name' => $supportName,
                    'year' => $yearVal,
                    'month' => $monthVal,
                    'catatan' => $catatan,
                    'created_at' => date('Y-m-d H:i:s')
                ];
                db()->insert('report_checklist', $data);
            }
            
            echo json_encode(['success' => true, 'message' => 'Catatan disimpan']);
            
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
    }
    
    /**
     * Load checklist data via AJAX
     * Endpoint: GET /analysis/load-checklist
     */
    public function loadChecklist(): void {
        header('Content-Type: application/json');
        
        try {
            $companyId = Session::companyId();
            
            $year = $_GET['year'] ?? '';
            $month = $_GET['month'] ?? '';
            
            // Normalize empty string to NULL
            $yearVal = !empty($year) ? $year : null;
            $monthVal = !empty($month) ? $month : null;
            
            // Get all checklist data for this period
            $data = db()->fetchAll(
                "SELECT support_key, support_name, is_clear, is_submitted, catatan 
                 FROM report_checklist 
                 WHERE company_id = ? 
                   AND (year = ? OR (year IS NULL AND ? IS NULL))
                   AND (month = ? OR (month IS NULL AND ? IS NULL))",
                [$companyId, $yearVal, $yearVal, $monthVal, $monthVal]
            );
            
            // Convert to associative array by support_key
            $result = [];
            foreach ($data as $row) {
                $result[$row['support_key']] = [
                    'clear' => (bool)$row['is_clear'],
                    'submitted' => (bool)$row['is_submitted'],
                    'catatan' => $row['catatan'] ?? ''
                ];
            }
            
            echo json_encode(['success' => true, 'data' => $result]);
            
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage(), 'data' => []]);
        }
    }
    
    /**
     * Reset all checklist for a period via AJAX
     * Endpoint: POST /analysis/reset-checklist
     */
    public function resetChecklist(): void {
        header('Content-Type: application/json');
        
        try {
            $companyId = Session::companyId();
            
            $year = $_POST['year'] ?? '';
            $month = $_POST['month'] ?? '';
            
            // Normalize empty string to NULL
            $yearVal = !empty($year) ? $year : null;
            $monthVal = !empty($month) ? $month : null;
            
            // Delete all checklist data for this period
            db()->query(
                "DELETE FROM report_checklist 
                 WHERE company_id = ? 
                   AND (year = ? OR (year IS NULL AND ? IS NULL))
                   AND (month = ? OR (month IS NULL AND ? IS NULL))",
                [$companyId, $yearVal, $yearVal, $monthVal, $monthVal]
            );
            
            echo json_encode(['success' => true, 'message' => 'Checklist berhasil direset']);
            
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
    }
    
    /**
     * Check if report_checklist table exists
     */
    private function hasChecklistTable(): bool {
        try {
            $result = db()->fetchOne("SHOW TABLES LIKE 'report_checklist'");
            return !empty($result);
        } catch (Exception $e) {
            return false;
        }
    }
    
    
    
    /**
     * Halaman Report Keuangan
     */
    public function report(): void {
        $companyId = Session::companyId();
        
        // Filters untuk report - kosong berarti semua
        $reportFilters = [
            'year' => $_GET['year'] ?? '',
            'month' => $_GET['month'] ?? ''
        ];

        // Build where clause untuk filter waktu
        $dateWhere = "company_id = ?";
        $dateParams = [$companyId];

        if (!empty($reportFilters['year'])) {
            $dateWhere .= " AND YEAR(order_date) = ?";
            $dateParams[] = $reportFilters['year'];
        }

        if (!empty($reportFilters['month'])) {
            $dateWhere .= " AND MONTH(order_date) = ?";
            $dateParams[] = $reportFilters['month'];
        }

        // 1. REKAP EVENT - Diurutkan berdasarkan tanggal pesanan terakhir (terbaru di atas)
        $eventStats = db()->fetchAll(
            "SELECT
                COALESCE(event_name, '(Tanpa Nama Event)') as event_label,
                COUNT(*) as total_orders,
                COALESCE(SUM(total_base_price), 0) as total_modal,
                COALESCE(SUM(total_final_price), 0) as total_tagihan,
                COALESCE(SUM(total_final_price - total_base_price), 0) as total_profit,
                COALESCE(SUM(paid_amount), 0) as total_dibayar,
                MIN(order_date) as first_order,
                MAX(order_date) as last_order
            FROM orders
            WHERE {$dateWhere}
            GROUP BY event_name
            ORDER BY last_order DESC",
            $dateParams
        );

        // 2. SUMMARY TOTALS
        $grandTotals = db()->fetchOne(
            "SELECT
                COUNT(*) as total_orders,
                COUNT(DISTINCT event_name) as total_events,
                COALESCE(SUM(total_base_price), 0) as total_modal,
                COALESCE(SUM(total_final_price), 0) as total_tagihan,
                COALESCE(SUM(total_final_price - total_base_price), 0) as total_profit,
                COALESCE(SUM(paid_amount), 0) as total_dibayar,
                COALESCE(SUM(total_final_price - paid_amount), 0) as total_sisa
            FROM orders
            WHERE {$dateWhere}",
            $dateParams
        );

        // Default jika null
        if (!$grandTotals) {
            $grandTotals = [
                'total_orders' => 0,
                'total_events' => 0,
                'total_modal' => 0,
                'total_tagihan' => 0,
                'total_profit' => 0,
                'total_dibayar' => 0,
                'total_sisa' => 0
            ];
        }

        // 3. REKAP BULANAN - gunakan filter yang sama
        $monthlyWhere = "company_id = ?";
        $monthlyParams = [$companyId];
        
        if (!empty($reportFilters['year'])) {
            $monthlyWhere .= " AND YEAR(order_date) = ?";
            $monthlyParams[] = $reportFilters['year'];
        }
        
        $monthlyStats = db()->fetchAll(
            "SELECT 
                MONTH(order_date) as bulan,
                DATE_FORMAT(order_date, '%M') as nama_bulan,
                COUNT(*) as total_orders,
                COALESCE(SUM(total_base_price), 0) as total_modal,
                COALESCE(SUM(total_final_price), 0) as total_tagihan,
                COALESCE(SUM(total_final_price - total_base_price), 0) as total_profit
            FROM orders 
            WHERE {$monthlyWhere}
            GROUP BY MONTH(order_date), DATE_FORMAT(order_date, '%M')
            ORDER BY bulan",
            $monthlyParams
        );

        // 4. REKAP BY LAYANAN
        $serviceStats = db()->fetchAll(
            "SELECT 
                COALESCE(
                    (SELECT oi.item_type FROM order_items oi WHERE oi.order_id = o.id LIMIT 1),
                    'other'
                ) as service_type,
                COUNT(*) as total_orders,
                COALESCE(SUM(o.total_base_price), 0) as total_modal,
                COALESCE(SUM(o.total_final_price), 0) as total_tagihan
            FROM orders o
            WHERE o.{$dateWhere}
            GROUP BY service_type
            ORDER BY total_tagihan DESC",
            $dateParams
        );

        // 5. CEK TABEL DRAFT
        $hasDraftTable = false;
        $draftExpenses = [];
        
        try {
            $checkTable = db()->fetchOne("SHOW TABLES LIKE 'draft_expenses'");
            $hasDraftTable = !empty($checkTable);
            
            if ($hasDraftTable) {
                $draftExpenses = db()->fetchAll(
                    "SELECT * FROM draft_expenses WHERE company_id = ? AND status = 'pending' ORDER BY planned_date ASC",
                    [$companyId]
                );
            }
        } catch (Exception $e) {
            $hasDraftTable = false;
        }

        // 6. AVAILABLE YEARS
        $availableYears = db()->fetchAll(
            "SELECT DISTINCT YEAR(order_date) as year FROM orders WHERE company_id = ? ORDER BY year DESC",
            [$companyId]
        );
        
        if (empty($availableYears)) {
            $availableYears = [['year' => date('Y')]];
        }

        // Item types
        $itemTypes = defined('ITEM_TYPES') ? ITEM_TYPES : [
            'bus' => ['label' => 'Bus', 'icon' => 'bus', 'color' => 'primary'],
            'car' => ['label' => 'Mobil', 'icon' => 'car', 'color' => 'info'],
            'towing' => ['label' => 'Towing', 'icon' => 'truck-pickup', 'color' => 'warning'],
            'hotel' => ['label' => 'Hotel', 'icon' => 'hotel', 'color' => 'success'],
            'flight' => ['label' => 'Pesawat', 'icon' => 'plane', 'color' => 'purple'],
            'other' => ['label' => 'Lainnya', 'icon' => 'box', 'color' => 'secondary']
        ];

        $months = [
            1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April',
            5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus',
            9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember'
        ];

        // Render view dengan semua data
        render('analysis/report', [
            'reportFilters' => $reportFilters,
            'eventStats' => $eventStats,
            'grandTotals' => $grandTotals,
            'monthlyStats' => $monthlyStats,
            'serviceStats' => $serviceStats,
            'hasDraftTable' => $hasDraftTable,
            'draftExpenses' => $draftExpenses,
            'availableYears' => $availableYears,
            'itemTypes' => $itemTypes,
            'months' => $months
        ]);
    }

    /**
     * Simpan/Update Draft Pengeluaran
     */
    public function saveDraft(): void {
        header('Content-Type: application/json');
        
        try {
            // Pastikan tabel ada
            $this->ensureDraftTable();
            
            $companyId = Session::companyId();
            $id = (int)($_POST['draft_id'] ?? 0);
            
            $data = [
                'company_id' => $companyId,
                'title' => trim($_POST['title'] ?? ''),
                'description' => trim($_POST['description'] ?? ''),
                'category' => $_POST['category'] ?? 'other',
                'priority' => $_POST['priority'] ?? 'medium',
                'planned_date' => $_POST['planned_date'] ?? date('Y-m-d'),
                'estimated_amount' => (float)preg_replace('/[^0-9]/', '', $_POST['estimated_amount'] ?? '0'),
                'support_for' => trim($_POST['support_for'] ?? ''),
                'status' => 'pending',
                'updated_at' => date('Y-m-d H:i:s')
            ];
            
            if (empty($data['title'])) {
                echo json_encode(['success' => false, 'message' => 'Judul harus diisi']);
                return;
            }
            
            if ($id > 0) {
                // Update
                db()->query(
                    "UPDATE draft_expenses SET 
                        title = ?, description = ?, category = ?, priority = ?,
                        planned_date = ?, estimated_amount = ?, support_for = ?, updated_at = ?
                     WHERE id = ? AND company_id = ?",
                    [
                        $data['title'], $data['description'], $data['category'], $data['priority'],
                        $data['planned_date'], $data['estimated_amount'], $data['support_for'], $data['updated_at'],
                        $id, $companyId
                    ]
                );
                echo json_encode(['success' => true, 'message' => 'Draft berhasil diupdate']);
            } else {
                // Insert
                $data['created_at'] = date('Y-m-d H:i:s');
                db()->insert('draft_expenses', $data);
                echo json_encode(['success' => true, 'message' => 'Draft berhasil disimpan']);
            }
            
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
        }
    }
    
    /**
     * Get Draft by ID
     */
    public function getDraft(): void {
        header('Content-Type: application/json');
        
        $companyId = Session::companyId();
        $id = (int)($_GET['id'] ?? 0);
        
        $draft = db()->fetchOne(
            "SELECT * FROM draft_expenses WHERE id = ? AND company_id = ?",
            [$id, $companyId]
        );
        
        if ($draft) {
            echo json_encode(['success' => true, 'data' => $draft]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Draft tidak ditemukan']);
        }
    }
    
    /**
     * Delete Draft
     */
    public function deleteDraft(): void {
        header('Content-Type: application/json');
        
        try {
            $companyId = Session::companyId();
            $id = (int)($_POST['id'] ?? 0);
            
            db()->delete('draft_expenses', 'id = ? AND company_id = ?', [$id, $companyId]);
            
            echo json_encode(['success' => true, 'message' => 'Draft dihapus']);
            
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
        }
    }
    
    /**
     * Approve Draft
     */
    public function approveDraft(): void {
        header('Content-Type: application/json');
        
        try {
            $companyId = Session::companyId();
            $id = (int)($_POST['id'] ?? 0);
            
            db()->query(
                "UPDATE draft_expenses SET status = 'approved', updated_at = NOW() WHERE id = ? AND company_id = ?",
                [$id, $companyId]
            );
            
            echo json_encode(['success' => true, 'message' => 'Draft disetujui']);
            
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
        }
    }
    
    /**
     * Buat tabel draft_expenses
     */
    public function createDraftTable(): void {
        header('Content-Type: application/json');
        
        try {
            $this->ensureDraftTable();
            echo json_encode(['success' => true, 'message' => 'Tabel draft_expenses berhasil dibuat']);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
        }
    }
    
    /**
     * Export Report ke CSV
     */
    public function exportReport(): void {
        $companyId = Session::companyId();
        
        $year = $_GET['year'] ?? date('Y');
        $month = $_GET['month'] ?? '';
        
        $where = "company_id = ? AND YEAR(order_date) = ?";
        $params = [$companyId, $year];
        
        if (!empty($month)) {
            $where .= " AND MONTH(order_date) = ?";
            $params[] = $month;
        }
        
        $data = db()->fetchAll(
            "SELECT
                event_name as 'Event',
                order_date as 'Tanggal',
                total_base_price as 'Modal',
                total_final_price as 'Tagihan',
                (total_final_price - total_base_price) as 'Profit',
                paid_amount as 'Dibayar',
                payment_status as 'Status Bayar'
            FROM orders
            WHERE {$where}
            ORDER BY event_name, order_date DESC",
            $params
        );
        
        $filename = 'laporan_keuangan_' . $year . ($month ? '_' . str_pad($month, 2, '0', STR_PAD_LEFT) : '') . '.csv';
        
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        
        $output = fopen('php://output', 'w');
        fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));
        
        if (!empty($data)) {
            fputcsv($output, array_keys($data[0]), ';');
        }
        
        foreach ($data as $row) {
            fputcsv($output, $row, ';');
        }
        
        fclose($output);
        exit;
    }
    
    
    /**
     * Update SBM price (AJAX)
     */
    public function updateSbm(): void {
        header('Content-Type: application/json');
        
        try {
            if (!$this->hasSbmColumns()) {
                echo json_encode(['success' => false, 'message' => 'Kolom sbm_price belum ada. Jalankan SQL dulu.']);
                return;
            }
            
            // Get input from POST or JSON body
            $input = $this->getJsonInput();
            
            $orderId = (int)($input['order_id'] ?? 0);
            $sbmPrice = (float)($input['sbm_price'] ?? 0);
            
            if (!$orderId) {
                echo json_encode(['success' => false, 'message' => 'Order ID tidak valid']);
                return;
            }
            
            $companyId = Session::companyId();
            
            $order = db()->fetchOne(
                "SELECT id FROM orders WHERE id = ? AND company_id = ?",
                [$orderId, $companyId]
            );
            
            if (!$order) {
                echo json_encode(['success' => false, 'message' => 'Order tidak ditemukan']);
                return;
            }
            
            db()->query("UPDATE orders SET sbm_price = ?, updated_at = NOW() WHERE id = ?", [$sbmPrice, $orderId]);
            
            echo json_encode(['success' => true, 'message' => 'SBM berhasil diupdate', 'sbm_price' => $sbmPrice]);
            
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
        }
    }
    
    /**
     * Update markup percentage (AJAX)
     */
    public function updateMarkup(): void {
        header('Content-Type: application/json');
        
        try {
            $input = $this->getJsonInput();
            
            $orderId = (int)($input['order_id'] ?? 0);
            $markupPercent = (float)($input['markup_percent'] ?? 0);
            
            if (!$orderId) {
                echo json_encode(['success' => false, 'message' => 'Order ID tidak valid']);
                return;
            }
            
            $companyId = Session::companyId();
            
            $order = db()->fetchOne(
                "SELECT * FROM orders WHERE id = ? AND company_id = ?",
                [$orderId, $companyId]
            );
            
            if (!$order) {
                echo json_encode(['success' => false, 'message' => 'Order tidak ditemukan']);
                return;
            }
            
            $modal = (float)$order['total_base_price'];
            $newMarkup = $modal * ($markupPercent / 100);
            $newTagihan = $modal + $newMarkup;
            $newProfit = $newTagihan - $modal; // profit = tagihan - modal
            
            // Update order
            if ($this->hasSbmColumns()) {
                db()->query(
                    "UPDATE orders SET sbm_markup_percent = ?, total_markup = ?, total_final_price = ?, total_profit = ?, updated_at = NOW() WHERE id = ?",
                    [$markupPercent, $newMarkup, $newTagihan, $newMarkup, $orderId]
                );
            } else {
                db()->query(
                    "UPDATE orders SET total_markup = ?, total_final_price = ?, total_profit = ?, updated_at = NOW() WHERE id = ?",
                    [$newMarkup, $newTagihan, $newMarkup, $orderId]
                );
            }
            
            // Update items
            db()->query(
                "UPDATE order_items SET 
                    markup_type = 'percentage',
                    markup_value = ?,
                    markup_amount = base_price * quantity * COALESCE(num_days, 1) * (? / 100),
                    final_price = (base_price * quantity * COALESCE(num_days, 1)) + (base_price * quantity * COALESCE(num_days, 1) * (? / 100)),
                    updated_at = NOW()
                 WHERE order_id = ?",
                [$markupPercent, $markupPercent, $markupPercent, $orderId]
            );
            
            echo json_encode([
                'success' => true,
                'message' => 'Markup berhasil diupdate',
                'data' => [
                    'markup_percent' => $markupPercent,
                    'markup_amount' => $newMarkup,
                    'tagihan' => $newTagihan,
                    'profit' => $newProfit
                ]
            ]);
            
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
        }
    }
    
    /**
     * Update filing status (AJAX)
     */
    public function updateFilingStatus(): void {
        header('Content-Type: application/json');
        
        try {
            if (!$this->hasFilingColumn()) {
                echo json_encode(['success' => false, 'message' => 'Kolom filing_status belum ada. Jalankan SQL dulu.']);
                return;
            }
            
            $input = $this->getJsonInput();
            
            $orderId = (int)($input['order_id'] ?? 0);
            $filingStatus = $input['filing_status'] ?? 'none';
            
            if (!$orderId) {
                echo json_encode(['success' => false, 'message' => 'Order ID tidak valid']);
                return;
            }
            
            $validStatuses = array_keys(self::$filingStatuses);
            if (!in_array($filingStatus, $validStatuses)) {
                echo json_encode(['success' => false, 'message' => 'Status tidak valid']);
                return;
            }
            
            $companyId = Session::companyId();
            
            $order = db()->fetchOne(
                "SELECT id FROM orders WHERE id = ? AND company_id = ?",
                [$orderId, $companyId]
            );
            
            if (!$order) {
                echo json_encode(['success' => false, 'message' => 'Order tidak ditemukan']);
                return;
            }
            
            db()->query("UPDATE orders SET filing_status = ?, updated_at = NOW() WHERE id = ?", [$filingStatus, $orderId]);
            
            $statusInfo = self::$filingStatuses[$filingStatus];
            
            echo json_encode([
                'success' => true,
                'message' => 'Status pemberkasan diupdate',
                'data' => [
                    'status' => $filingStatus,
                    'label' => $statusInfo['label'],
                    'color' => $statusInfo['color']
                ]
            ]);
            
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
        }
    }
    
    /**
     * Update payment status (AJAX) - Quick toggle
     */
    public function updatePaymentStatus(): void {
        header('Content-Type: application/json');
        
        try {
            $input = $this->getJsonInput();
            
            $orderId = (int)($input['order_id'] ?? 0);
            $paymentStatus = $input['payment_status'] ?? '';
            
            if (!$orderId) {
                echo json_encode(['success' => false, 'message' => 'Order ID tidak valid']);
                return;
            }
            
            $validStatuses = ['unpaid', 'partial', 'paid'];
            if (!in_array($paymentStatus, $validStatuses)) {
                echo json_encode(['success' => false, 'message' => 'Status pembayaran tidak valid']);
                return;
            }
            
            $companyId = Session::companyId();
            
            $order = db()->fetchOne(
                "SELECT * FROM orders WHERE id = ? AND company_id = ?",
                [$orderId, $companyId]
            );
            
            if (!$order) {
                echo json_encode(['success' => false, 'message' => 'Order tidak ditemukan']);
                return;
            }
            
            // Update payment status and paid_amount if paid
            $paidAmount = (float)$order['paid_amount'];
            $totalFinal = (float)$order['total_final_price'];
            $paidAt = $order['paid_at'] ?? null;
            
            if ($paymentStatus === 'paid') {
                $paidAmount = $totalFinal;
                $paidAt = date('Y-m-d');
            } elseif ($paymentStatus === 'unpaid') {
                $paidAmount = 0;
                $paidAt = null;
            }
            
            db()->query(
                "UPDATE orders SET payment_status = ?, paid_amount = ?, paid_at = ?, updated_at = NOW() WHERE id = ?",
                [$paymentStatus, $paidAmount, $paidAt, $orderId]
            );
            
            $statusInfo = PAYMENT_STATUSES[$paymentStatus] ?? ['label' => $paymentStatus, 'color' => 'secondary'];
            
            echo json_encode([
                'success' => true,
                'message' => 'Status pembayaran diupdate',
                'data' => [
                    'status' => $paymentStatus,
                    'label' => $statusInfo['label'],
                    'color' => $statusInfo['color'],
                    'paid_amount' => $paidAmount
                ]
            ]);
            
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
        }
    }
    
    /**
     * Bulk update markup
     */
    public function bulkUpdateMarkup(): void {
        header('Content-Type: application/json');
        
        try {
            $input = $this->getJsonInput();
            $markupPercent = (float)($input['markup_percent'] ?? 0);
            
            // Support both formats: order_ids (array) atau order_ids_json (string)
            $orderIds = $input['order_ids'] ?? [];
            if (empty($orderIds) && !empty($input['order_ids_json'])) {
                $orderIds = json_decode($input['order_ids_json'], true) ?: [];
            }
            
            if (empty($orderIds)) {
                echo json_encode(['success' => false, 'message' => 'Pilih minimal satu order']);
                return;
            }
            
            $companyId = Session::companyId();
            $updatedCount = 0;
            $hasSbm = $this->hasSbmColumns();
            
            foreach ($orderIds as $orderId) {
                $order = db()->fetchOne(
                    "SELECT * FROM orders WHERE id = ? AND company_id = ?",
                    [(int)$orderId, $companyId]
                );
                
                if (!$order) continue;
                
                $modal = (float)$order['total_base_price'];
                $newMarkup = $modal * ($markupPercent / 100);
                $newTagihan = $modal + $newMarkup;
                
                if ($hasSbm) {
                    db()->query(
                        "UPDATE orders SET sbm_markup_percent = ?, total_markup = ?, total_final_price = ?, total_profit = ?, updated_at = NOW() WHERE id = ?",
                        [$markupPercent, $newMarkup, $newTagihan, $newMarkup, (int)$orderId]
                    );
                } else {
                    db()->query(
                        "UPDATE orders SET total_markup = ?, total_final_price = ?, total_profit = ?, updated_at = NOW() WHERE id = ?",
                        [$newMarkup, $newTagihan, $newMarkup, (int)$orderId]
                    );
                }
                
                $updatedCount++;
            }
            
            echo json_encode([
                'success' => true,
                'message' => "Berhasil update {$updatedCount} pesanan dengan markup {$markupPercent}%"
            ]);
            
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
        }
    }
    
    /**
     * Export to CSV
     */
    public function export(): void {
        $companyId = Session::companyId();
        
        $where = "o.company_id = ?";
        $params = [$companyId];
        
        if (!empty($_GET['event_name'])) {
            $where .= " AND o.event_name = ?";
            $params[] = $_GET['event_name'];
        }
        if (!empty($_GET['payment_status'])) {
            $where .= " AND o.payment_status = ?";
            $params[] = $_GET['payment_status'];
        }
        
        $hasSbm = $this->hasSbmColumns();
        $sbmSelect = $hasSbm ? "o.sbm_price," : "0 as sbm_price,";
        
        // Export dengan data harga satuan
        $sql = "SELECT
                    o.event_name,
                    (SELECT oi.item_type FROM order_items oi WHERE oi.order_id = o.id LIMIT 1) as item_type,
                    o.order_date,
                    
                    -- Harga satuan
                    (SELECT COALESCE(oi.base_price, 0) FROM order_items oi WHERE oi.order_id = o.id ORDER BY oi.id LIMIT 1) as unit_price,
                    (SELECT COALESCE(SUM(oi.quantity), 1) FROM order_items oi WHERE oi.order_id = o.id) as total_qty,
                    (SELECT COALESCE(MAX(oi.num_days), 1) FROM order_items oi WHERE oi.order_id = o.id) as total_days,
                    
                    o.total_base_price as modal, 
                    {$sbmSelect} 
                    o.total_markup, 
                    o.total_final_price as tagihan, 
                    (o.total_final_price - o.total_base_price) as profit,
                    o.payment_status
                FROM orders o
                WHERE {$where}
                ORDER BY o.event_name, o.order_date DESC";
        
        $orders = db()->fetchAll($sql, $params);
        
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="analisa_keuangan_' . date('Y-m-d') . '.csv"');
        
        $output = fopen('php://output', 'w');
        fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));
        
        // Header dengan kolom harga satuan
        fputcsv($output, [
            'Event', 'Layanan', 'Tanggal',
            'Harga Satuan', 'Qty', 'Hari',
            'Total Modal', 'SBM', 'Markup', 'Tagihan', 'Profit',
            'Status Bayar'
        ], ';');

        foreach ($orders as $row) {
            fputcsv($output, [
                $row['event_name'],
                ITEM_TYPES[$row['item_type']]['label'] ?? $row['item_type'],
                $row['order_date'],
                $row['unit_price'],
                $row['total_qty'],
                $row['total_days'],
                $row['modal'],
                $row['sbm_price'],
                $row['total_markup'],
                $row['tagihan'],
                $row['profit'],
                PAYMENT_STATUSES[$row['payment_status']]['label'] ?? $row['payment_status']
            ], ';');
        }
        
        fclose($output);
        exit;
    }
    
    /**
     * Get chart data
     */
    public function getChartData(): void {
        header('Content-Type: application/json');
        
        $companyId = Session::companyId();
        $type = $_GET['type'] ?? 'monthly';
        
        try {
            if ($type === 'monthly') {
                $data = db()->fetchAll(
                    "SELECT 
                        DATE_FORMAT(order_date, '%Y-%m') as period,
                        DATE_FORMAT(order_date, '%b %Y') as label,
                        COALESCE(SUM(total_base_price), 0) as modal,
                        COALESCE(SUM(total_final_price), 0) as tagihan,
                        COALESCE(SUM(total_final_price - total_base_price), 0) as profit
                     FROM orders 
                     WHERE company_id = ? AND order_date >= DATE_SUB(CURDATE(), INTERVAL 6 MONTH)
                     GROUP BY DATE_FORMAT(order_date, '%Y-%m')
                     ORDER BY period",
                    [$companyId]
                );
            } elseif ($type === 'by_event') {
                $data = db()->fetchAll(
                    "SELECT
                        COALESCE(event_name, 'Lainnya') as label,
                        COALESCE(SUM(total_final_price), 0) as tagihan
                     FROM orders
                     WHERE company_id = ?
                     GROUP BY event_name
                     ORDER BY tagihan DESC
                     LIMIT 10",
                    [$companyId]
                );
            } else {
                $data = [];
            }
            
            echo json_encode(['success' => true, 'data' => $data]);
            
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
    }
    
    // ================================================================
    // SIMULASI PEMBAGIAN DANA METHODS
    // ================================================================
    
    /**
     * Simpan Simulasi Pembagian Dana
     * Support both GET (via query string) and POST (via JSON body)
     */
    public function saveSimulasi(): void {
        // Set header JSON di awal
        header('Content-Type: application/json');
        
        // Disable any output buffering
        while (ob_get_level()) {
            ob_end_clean();
        }
        
        try {
            // Pastikan tabel ada dulu
            $this->ensureSimulasiTable();
            
            $companyId = Session::companyId();
            
            // Get input - support both POST JSON and GET query
            $input = null;
            
            // Try POST JSON first
            $rawInput = file_get_contents('php://input');
            if (!empty($rawInput)) {
                $input = json_decode($rawInput, true);
            }
            
            // Fallback to GET parameters
            if (empty($input) && !empty($_GET['data'])) {
                $input = json_decode($_GET['data'], true);
            }
            
            // Fallback to individual GET params
            if (empty($input) && isset($_GET['year'])) {
                $pembagianJson = $_GET['pembagian'] ?? '[]';
                $input = [
                    'year' => $_GET['year'],
                    'month' => $_GET['month'] ?? '',
                    'pembagian' => json_decode($pembagianJson, true) ?: []
                ];
            }
            
            if (empty($input)) {
                echo json_encode(['success' => false, 'message' => 'Data tidak valid']);
                exit;
            }
            
            $year = (int)($input['year'] ?? date('Y'));
            $month = (string)($input['month'] ?? '');
            $pembagian = $input['pembagian'] ?? [];
            
            // Cek apakah sudah ada data simulasi untuk periode ini
            $existing = db()->fetchOne(
                "SELECT id FROM simulasi_pembagian WHERE company_id = ? AND tahun = ? AND bulan = ?",
                [$companyId, $year, $month]
            );
            
            if ($existing) {
                // Update
                db()->query(
                    "UPDATE simulasi_pembagian SET data_pembagian = ?, updated_at = NOW() WHERE id = ?",
                    [json_encode($pembagian), $existing['id']]
                );
                echo json_encode(['success' => true, 'message' => 'Simulasi berhasil diupdate']);
                exit;
            } else {
                // Insert
                db()->query(
                    "INSERT INTO simulasi_pembagian (company_id, tahun, bulan, data_pembagian, created_at, updated_at) VALUES (?, ?, ?, ?, NOW(), NOW())",
                    [$companyId, $year, $month, json_encode($pembagian)]
                );
                echo json_encode(['success' => true, 'message' => 'Simulasi berhasil disimpan']);
                exit;
            }
            
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
            exit;
        }
    }
    
    /**
     * Load Simulasi Pembagian Dana
     */
    public function loadSimulasi(): void {
        // Set header JSON di awal
        header('Content-Type: application/json');
        // Prevent caching
        header('Cache-Control: no-cache, no-store, must-revalidate');
        header('Pragma: no-cache');
        header('Expires: 0');
        
        // Disable any output buffering
        while (ob_get_level()) {
            ob_end_clean();
        }
        
        try {
            // Pastikan tabel ada
            $this->ensureSimulasiTable();
            
            $companyId = Session::companyId();
            $year = (int)($_GET['year'] ?? date('Y'));
            $month = $_GET['month'] ?? '';
            
            $simulasi = db()->fetchOne(
                "SELECT * FROM simulasi_pembagian WHERE company_id = ? AND tahun = ? AND bulan = ?",
                [$companyId, $year, $month]
            );
            
            if ($simulasi) {
                $pembagian = json_decode($simulasi['data_pembagian'], true) ?: [];
                echo json_encode([
                    'success' => true, 
                    'data' => $pembagian,
                    'updated_at' => $simulasi['updated_at']
                ]);
            } else {
                echo json_encode([
                    'success' => true, 
                    'data' => [], 
                    'updated_at' => null
                ]);
            }
            exit;
            
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage(), 'data' => []]);
            exit;
        }
    }
    
    /**
     * Buat tabel simulasi_pembagian (endpoint manual)
     */
    public function createSimulasiTable(): void {
        header('Content-Type: application/json');
        
        try {
            $this->ensureSimulasiTable();
            echo json_encode(['success' => true, 'message' => 'Tabel simulasi_pembagian berhasil dibuat']);
            exit;
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
            exit;
        }
    }
    
    /**
     * Helper: Pastikan tabel simulasi_pembagian ada
     */
    private function ensureSimulasiTable(): void {
        $sql = "CREATE TABLE IF NOT EXISTS `simulasi_pembagian` (
            `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
            `company_id` INT(11) UNSIGNED NOT NULL,
            `tahun` INT(4) NOT NULL,
            `bulan` VARCHAR(2) DEFAULT '',
            `data_pembagian` TEXT NULL,
            `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
            `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`),
            UNIQUE KEY `idx_company_period` (`company_id`, `tahun`, `bulan`),
            INDEX `idx_company` (`company_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
        
        db()->query($sql);
    }
    
    /**
     * Helper: Pastikan tabel draft_expenses ada
     */
    private function ensureDraftTable(): void {
        $sql = "CREATE TABLE IF NOT EXISTS `draft_expenses` (
            `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
            `company_id` INT(11) UNSIGNED NOT NULL,
            `title` VARCHAR(255) NOT NULL,
            `description` TEXT NULL,
            `category` VARCHAR(50) DEFAULT 'other',
            `priority` ENUM('low', 'medium', 'high') DEFAULT 'medium',
            `planned_date` DATE NOT NULL,
            `estimated_amount` DECIMAL(15,2) DEFAULT 0.00,
            `support_for` VARCHAR(255) NULL,
            `status` ENUM('pending', 'approved', 'rejected', 'completed') DEFAULT 'pending',
            `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
            `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`),
            INDEX `idx_company` (`company_id`),
            INDEX `idx_status` (`status`),
            INDEX `idx_planned_date` (`planned_date`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
        
        db()->query($sql);
    }
    
    /**
     * Helper: Get JSON input from request body
     */
    private function getJsonInput(): array {
        $rawInput = file_get_contents('php://input');
        
        if (empty($rawInput)) {
            // Fallback ke POST data
            return $_POST;
        }
        
        $decoded = json_decode($rawInput, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            // Jika bukan JSON, coba parse sebagai form data
            parse_str($rawInput, $parsed);
            return $parsed ?: $_POST;
        }
        
        return $decoded ?: [];
    }
    
    // ==================== EXPENSE TRACKING ====================
    
    /**
     * Update expense status for an ORDER (partial payment / DP supported)
     * AJAX endpoint
     */
    public function updateExpenseStatus(): void {
        header('Content-Type: application/json');
        
        try {
            $companyId = Session::companyId();
            $orderId = (int)($_POST['order_id'] ?? 0);
            $expenseStatus = $_POST['expense_status'] ?? 'pending';
            $payAmount = (float)($_POST['pay_amount'] ?? 0);
            $bankCashId = !empty($_POST['bank_cash_id']) ? (int)$_POST['bank_cash_id'] : null;
            $expenseDate = $_POST['expense_date'] ?? date('Y-m-d');
            $notes = trim($_POST['notes'] ?? '');
            
            if (!$orderId) {
                echo json_encode(['success' => false, 'message' => 'Order ID tidak valid']);
                return;
            }
            
            // Verify order belongs to company
            $order = db()->fetchOne(
                "SELECT id, order_number, event_name, company_id, total_base_price, COALESCE(expense_paid_amount, 0) as expense_paid
                 FROM orders WHERE id = ? AND company_id = ?",
                [$orderId, $companyId]
            );
            
            if (!$order) {
                echo json_encode(['success' => false, 'message' => 'Order tidak ditemukan']);
                return;
            }
            
            $totalModal = (float)$order['total_base_price'];
            $currentPaid = (float)$order['expense_paid'];
            
            if ($expenseStatus === 'paid') {
                // ========== PROCESS PAYMENT ==========
                if (!$bankCashId) {
                    echo json_encode(['success' => false, 'message' => 'Pilih Kas/Bank sumber pembayaran']);
                    return;
                }
                
                if ($payAmount <= 0) {
                    echo json_encode(['success' => false, 'message' => 'Masukkan nominal pembayaran']);
                    return;
                }
                
                $remaining = $totalModal - $currentPaid;
                if ($payAmount > $remaining + 1) {
                    echo json_encode(['success' => false, 'message' => 'Nominal melebihi sisa (Rp ' . number_format($remaining, 0, ',', '.') . ')']);
                    return;
                }
                
                // Get bank/cash with account_id
                $bankCash = db()->fetchOne(
                    "SELECT bc.*, a.code as account_code, a.name as account_name 
                     FROM bank_cash bc 
                     LEFT JOIN accounts a ON bc.account_id = a.id 
                     WHERE bc.id = ? AND bc.company_id = ?", 
                    [$bankCashId, $companyId]
                );
                if (!$bankCash) {
                    echo json_encode(['success' => false, 'message' => 'Kas/Bank tidak ditemukan']);
                    return;
                }
                if ((float)$bankCash['balance'] < $payAmount) {
                    echo json_encode(['success' => false, 'message' => 'Saldo ' . $bankCash['name'] . ' tidak mencukupi (Rp ' . number_format($bankCash['balance'], 0, ',', '.') . ')']);
                    return;
                }
                
                // Get expense account (Biaya Vendor / HPP)
                $expenseAccountId = $this->getExpenseAccountId($companyId);
                $cashAccountId = $bankCash['account_id'];
                
                if (!$cashAccountId) {
                    echo json_encode(['success' => false, 'message' => 'Kas/Bank belum terhubung dengan akun. Silakan setting di menu Kas & Bank.']);
                    return;
                }
                
                // ========== CREATE JOURNAL ==========
                $journalId = null;
                if ($expenseAccountId) {
                    $journalNumber = $this->generateExpenseJournalNumber($companyId);
                    $description = 'Bayar vendor: ' . ($order['event_name'] ?: $order['order_number']);
                    if ($notes) $description .= ' - ' . $notes;
                    
                    // Insert journal header
                    $journalId = db()->insert('journals', [
                        'company_id' => $companyId,
                        'journal_number' => $journalNumber,
                        'journal_date' => $expenseDate,
                        'journal_type' => 'expense',
                        'description' => $description,
                        'reference' => $order['order_number'],
                        'status' => 'posted',
                        'posted_at' => date('Y-m-d H:i:s'),
                        'posted_by' => Session::userId(),
                        'created_by' => Session::userId(),
                        'created_at' => date('Y-m-d H:i:s')
                    ]);
                    
                    // Insert journal details
                    // Debit: Biaya Vendor (expense)
                    db()->insert('journal_details', [
                        'journal_id' => $journalId,
                        'account_id' => $expenseAccountId,
                        'description' => 'Biaya vendor ' . $order['order_number'],
                        'debit' => $payAmount,
                        'credit' => 0
                    ]);
                    
                    // Credit: Kas/Bank
                    db()->insert('journal_details', [
                        'journal_id' => $journalId,
                        'account_id' => $cashAccountId,
                        'description' => 'Pembayaran dari ' . $bankCash['name'],
                        'debit' => 0,
                        'credit' => $payAmount
                    ]);
                }
                
                // ========== UPDATE BALANCES ==========
                $newPaidAmount = $currentPaid + $payAmount;
                $isFullyPaid = $newPaidAmount >= $totalModal;
                
                // Update order
                db()->update('orders', [
                    'expense_paid_amount' => $newPaidAmount
                ], 'id = ?', [$orderId]);
                
                // Reduce bank/cash balance
                db()->query("UPDATE bank_cash SET balance = balance - ? WHERE id = ?", [$payAmount, $bankCashId]);
                
                // If fully paid, mark all items
                if ($isFullyPaid) {
                    db()->query(
                        "UPDATE order_items SET expense_status = 'paid', expense_bank_cash_id = ?, expense_date = ?, expense_notes = ? WHERE order_id = ?",
                        [$bankCashId, $expenseDate, $notes, $orderId]
                    );
                }
                
                // ========== RECORD PAYMENT LOG ==========
                try {
                    db()->insert('expense_payments', [
                        'order_id' => $orderId,
                        'company_id' => $companyId,
                        'amount' => $payAmount,
                        'bank_cash_id' => $bankCashId,
                        'journal_id' => $journalId,
                        'payment_date' => $expenseDate,
                        'notes' => $notes,
                        'created_by' => Session::userId(),
                        'created_at' => date('Y-m-d H:i:s')
                    ]);
                } catch (Exception $e) {
                    // Table doesn't exist or missing column, ignore
                }
                
                $pctPaid = ($totalModal > 0) ? round(($newPaidAmount / $totalModal) * 100) : 0;
                $message = $isFullyPaid 
                    ? 'Pembayaran vendor LUNAS (Rp ' . number_format($newPaidAmount, 0, ',', '.') . ')'
                    : 'DP dicatat: Rp ' . number_format($payAmount, 0, ',', '.') . ' (' . $pctPaid . '%)';
                
                if ($journalId) {
                    $message .= ' - Jurnal: ' . $journalNumber;
                }
                
                echo json_encode([
                    'success' => true, 
                    'message' => $message,
                    'paid_amount' => $newPaidAmount,
                    'is_fully_paid' => $isFullyPaid,
                    'journal_id' => $journalId
                ]);
                
            } else {
                // ========== CANCEL ALL PAYMENTS ==========
                if ($currentPaid <= 0) {
                    echo json_encode(['success' => false, 'message' => 'Tidak ada pembayaran untuk dibatalkan']);
                    return;
                }
                
                // Get payments to restore and void journals
                $paymentsToRestore = [];
                $journalsToVoid = [];
                
                try {
                    $payments = db()->fetchAll(
                        "SELECT bank_cash_id, journal_id, SUM(amount) as total 
                         FROM expense_payments 
                         WHERE order_id = ? 
                         GROUP BY bank_cash_id, journal_id",
                        [$orderId]
                    );
                    foreach ($payments as $p) {
                        if ($p['bank_cash_id']) {
                            if (!isset($paymentsToRestore[$p['bank_cash_id']])) {
                                $paymentsToRestore[$p['bank_cash_id']] = 0;
                            }
                            $paymentsToRestore[$p['bank_cash_id']] += $p['total'];
                        }
                        if ($p['journal_id']) {
                            $journalsToVoid[] = $p['journal_id'];
                        }
                    }
                } catch (Exception $e) {
                    // Fallback jika tabel tidak ada
                }
                
                // If no payment records, use currentPaid
                if (empty($paymentsToRestore) && $currentPaid > 0) {
                    $defaultBc = db()->fetchOne(
                        "SELECT id FROM bank_cash WHERE company_id = ? AND is_active = 1 ORDER BY id LIMIT 1", 
                        [$companyId]
                    );
                    if ($defaultBc) {
                        $paymentsToRestore[$defaultBc['id']] = $currentPaid;
                    }
                }
                
                // Restore balances
                foreach ($paymentsToRestore as $bcId => $amount) {
                    if ($bcId && $amount > 0) {
                        db()->query("UPDATE bank_cash SET balance = balance + ? WHERE id = ?", [$amount, $bcId]);
                    }
                }
                
                // Void journals
                foreach ($journalsToVoid as $jId) {
                    db()->update('journals', [
                        'status' => 'void',
                        'voided_at' => date('Y-m-d H:i:s'),
                        'voided_by' => Session::userId()
                    ], 'id = ?', [$jId]);
                }
                
                // Reset order expense
                db()->update('orders', ['expense_paid_amount' => 0], 'id = ?', [$orderId]);
                
                // Reset all items
                db()->query(
                    "UPDATE order_items SET expense_status = 'pending', expense_bank_cash_id = NULL, expense_date = NULL, expense_notes = NULL WHERE order_id = ?",
                    [$orderId]
                );
                
                // Delete payment records
                try {
                    db()->query("DELETE FROM expense_payments WHERE order_id = ?", [$orderId]);
                } catch (Exception $e) {
                    // Ignore
                }
                
                $voidCount = count($journalsToVoid);
                $message = 'Semua pembayaran dibatalkan, saldo dikembalikan (Rp ' . number_format($currentPaid, 0, ',', '.') . ')';
                if ($voidCount > 0) {
                    $message .= ' - ' . $voidCount . ' jurnal di-void';
                }
                
                echo json_encode([
                    'success' => true, 
                    'message' => $message
                ]);
            }
            
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
        }
    }
    
    /**
     * Get expense account ID for vendor payments
     * Prioritas: accounting_settings > akun dengan kode 5.x.x
     */
    private function getExpenseAccountId(int $companyId): ?int {
        // Coba dari settings
        $setting = db()->fetchOne(
            "SELECT setting_value FROM accounting_settings WHERE company_id = ? AND setting_key = 'vendor_expense_account'",
            [$companyId]
        );
        if ($setting && $setting['setting_value']) {
            return (int)$setting['setting_value'];
        }
        
        // Coba cari akun dengan nama mengandung 'vendor' atau 'HPP'
        $account = db()->fetchOne(
            "SELECT id FROM accounts WHERE company_id = ? AND type = 'expense' AND is_active = 1 
             AND (name LIKE '%vendor%' OR name LIKE '%HPP%' OR name LIKE '%Harga Pokok%' OR code LIKE '5.1%')
             ORDER BY code LIMIT 1",
            [$companyId]
        );
        if ($account) {
            return (int)$account['id'];
        }
        
        // Fallback: akun expense pertama
        $fallback = db()->fetchOne(
            "SELECT id FROM accounts WHERE company_id = ? AND type = 'expense' AND is_active = 1 ORDER BY code LIMIT 1",
            [$companyId]
        );
        
        return $fallback ? (int)$fallback['id'] : null;
    }
    
    /**
     * Generate journal number for expense
     */
    private function generateExpenseJournalNumber(int $companyId): string {
        $prefix = 'JKK'; // Jurnal Kas Keluar
        $year = date('y');
        $month = date('m');
        
        $last = db()->fetchOne(
            "SELECT journal_number FROM journals 
             WHERE company_id = ? AND journal_number LIKE ?
             ORDER BY id DESC LIMIT 1",
            [$companyId, $prefix . '/' . $year . $month . '/%']
        );
        
        if ($last) {
            $parts = explode('/', $last['journal_number']);
            $num = (int)end($parts) + 1;
        } else {
            $num = 1;
        }
        
        return $prefix . '/' . $year . $month . '/' . str_pad($num, 4, '0', STR_PAD_LEFT);
    }
    
    /**
     * Get bank/cash options for expense modal
     * AJAX endpoint
     */
    public function getBankCashOptions(): void {
        header('Content-Type: application/json');
        
        try {
            $companyId = Session::companyId();
            
            $options = db()->fetchAll(
                "SELECT id, name, type, balance FROM bank_cash 
                 WHERE company_id = ? AND is_active = 1 
                 ORDER BY type, name",
                [$companyId]
            );
            
            $formatted = [];
            foreach ($options as $opt) {
                $typeLabel = $opt['type'] === 'cash' ? 'Kas' : 'Bank';
                $formatted[] = [
                    'id' => $opt['id'],
                    'name' => "[{$typeLabel}] {$opt['name']}",
                    'balance' => (float)$opt['balance'],
                    'balance_formatted' => 'Rp ' . number_format($opt['balance'], 0, ',', '.')
                ];
            }
            
            echo json_encode(['success' => true, 'data' => $formatted]);
            
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
        }
    }
    
    /**
     * ================================================================
     * VENDOR RECAP - Hutang ke Vendor
     * ================================================================
     * Endpoint: /analysis/vendor-recap
     * Returns: Summary, by_category, transactions
     */
    public function vendorRecap(): void {
        header('Content-Type: application/json');
        
        try {
            $companyId = Session::companyId();
            
            // Get all orders with pending vendor payments
            $sql = "SELECT 
                        o.id,
                        o.order_number,
                        o.order_date,
                        o.event_name,
                        COALESCE(o.total_base_price, 0) as total_modal,
                        COALESCE(o.expense_paid_amount, 0) as paid_amount,
                        (COALESCE(o.total_base_price, 0) - COALESCE(o.expense_paid_amount, 0)) as pending_amount,
                        
                        -- Get item_type from first order_item
                        (SELECT oi.item_type 
                         FROM order_items oi 
                         WHERE oi.order_id = o.id 
                         ORDER BY oi.id LIMIT 1) as item_type,
                        
                        -- Get item description from first order_item
                        (SELECT oi.description 
                         FROM order_items oi 
                         WHERE oi.order_id = o.id 
                         ORDER BY oi.id LIMIT 1) as item_description
                         
                    FROM orders o 
                    WHERE o.company_id = ?
                      AND COALESCE(o.total_base_price, 0) > 0
                      AND COALESCE(o.expense_paid_amount, 0) < COALESCE(o.total_base_price, 0)
                    ORDER BY o.order_date DESC, o.id DESC";
            
            $orders = db()->fetchAll($sql, [$companyId]);
            
            // Calculate summary totals
            $totalModal = 0;
            $totalPaid = 0;
            $totalPending = 0;
            
            // Group by category
            $byCategory = [];
            $transactions = [];
            
            foreach ($orders as $order) {
                $modal = (float)$order['total_modal'];
                $paid = (float)$order['paid_amount'];
                $pending = (float)$order['pending_amount'];
                $itemType = $order['item_type'] ?: 'other';
                
                // Summary totals
                $totalModal += $modal;
                $totalPaid += $paid;
                $totalPending += $pending;
                
                // Group by category
                if (!isset($byCategory[$itemType])) {
                    $byCategory[$itemType] = [
                        'category' => $itemType,
                        'modal' => 0,
                        'paid' => 0,
                        'pending' => 0,
                        'count' => 0
                    ];
                }
                $byCategory[$itemType]['modal'] += $modal;
                $byCategory[$itemType]['paid'] += $paid;
                $byCategory[$itemType]['pending'] += $pending;
                $byCategory[$itemType]['count']++;
                
                // Add to transactions list
                $transactions[] = [
                    'id' => $order['id'],
                    'order_number' => $order['order_number'],
                    'order_date' => $order['order_date'],
                    'event_name' => $order['event_name'],
                    'item_type' => $itemType,
                    'description' => $order['item_description'],
                    'total_modal' => $modal,
                    'paid_amount' => $paid,
                    'amount' => $pending // This is the pending amount (hutang)
                ];
            }
            
            // Convert byCategory to indexed array and sort by pending amount
            $categoriesArray = array_values($byCategory);
            usort($categoriesArray, function($a, $b) {
                return $b['pending'] <=> $a['pending']; // Descending by pending
            });
            
            // Calculate paid percentage
            $paidPercent = $totalModal > 0 ? ($totalPaid / $totalModal) * 100 : 0;
            
            // Return response
            echo json_encode([
                'success' => true,
                'data' => [
                    'summary' => [
                        'total_modal' => $totalModal,
                        'total_paid' => $totalPaid,
                        'total_pending' => $totalPending,
                        'paid_percent' => round($paidPercent, 1),
                        'order_count' => count($orders)
                    ],
                    'by_category' => $categoriesArray,
                    'transactions' => $transactions
                ]
            ]);
            
        } catch (Exception $e) {
            echo json_encode([
                'success' => false, 
                'message' => 'Error: ' . $e->getMessage()
            ]);
        }
    }
}