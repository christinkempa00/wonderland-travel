<?php
/**
 * ================================================================
 * TRAVEL MANAGEMENT SYSTEM - Activity Log Controller
 * ================================================================
 */

class ActivityLogController {
    
    /**
     * List activity logs
     */
    public function index(): void {
        $companyId = Session::companyId();
        $page = (int) ($_GET['page'] ?? 1);
        $perPage = 30;
        $offset = ($page - 1) * $perPage;
        
        $filters = [
            'action' => $_GET['action'] ?? '',
            'entity_type' => $_GET['entity_type'] ?? '',
            'user_id' => $_GET['user_id'] ?? '',
            'start_date' => $_GET['start_date'] ?? '',
            'end_date' => $_GET['end_date'] ?? ''
        ];
        
        $where = ["al.company_id = ?"];
        $params = [$companyId];
        
        if (!empty($filters['action'])) {
            $where[] = "al.action = ?";
            $params[] = $filters['action'];
        }
        
        if (!empty($filters['entity_type'])) {
            $where[] = "al.entity_type = ?";
            $params[] = $filters['entity_type'];
        }
        
        if (!empty($filters['user_id'])) {
            $where[] = "al.user_id = ?";
            $params[] = $filters['user_id'];
        }
        
        if (!empty($filters['start_date'])) {
            $where[] = "DATE(al.created_at) >= ?";
            $params[] = $filters['start_date'];
        }
        
        if (!empty($filters['end_date'])) {
            $where[] = "DATE(al.created_at) <= ?";
            $params[] = $filters['end_date'];
        }
        
        $whereClause = implode(' AND ', $where);
        
        // Count total
        $total = (int) db()->fetchColumn(
            "SELECT COUNT(*) FROM activity_logs al WHERE {$whereClause}",
            $params
        );
        
        // Get data
        $logs = db()->fetchAll(
            "SELECT al.*, u.name as user_name
             FROM activity_logs al
             LEFT JOIN users u ON al.user_id = u.id
             WHERE {$whereClause}
             ORDER BY al.created_at DESC
             LIMIT {$perPage} OFFSET {$offset}",
            $params
        );
        
        // Get filter options
        $actions = db()->fetchAll(
            "SELECT DISTINCT action FROM activity_logs WHERE company_id = ? ORDER BY action",
            [$companyId]
        );
        
        $entityTypes = db()->fetchAll(
            "SELECT DISTINCT entity_type FROM activity_logs WHERE company_id = ? ORDER BY entity_type",
            [$companyId]
        );
        
        $users = db()->fetchAll(
            "SELECT DISTINCT al.user_id, u.name 
             FROM activity_logs al
             LEFT JOIN users u ON al.user_id = u.id
             WHERE al.company_id = ? AND u.name IS NOT NULL
             ORDER BY u.name",
            [$companyId]
        );
        
        $data = [
            'pageTitle' => 'Log Aktivitas',
            'pageHeader' => true,
            'pageSubtitle' => 'Riwayat aktivitas sistem',
            'logs' => $logs,
            'pagination' => [
                'total' => $total,
                'per_page' => $perPage,
                'current_page' => $page,
                'last_page' => ceil($total / $perPage),
                'from' => $offset + 1,
                'to' => min($offset + $perPage, $total)
            ],
            'filters' => $filters,
            'actions' => array_column($actions, 'action'),
            'entityTypes' => array_column($entityTypes, 'entity_type'),
            'users' => $users
        ];
        
        render('activity-logs/index', $data);
    }
    
    /**
     * Export to CSV
     */
    public function export(): void {
        $companyId = Session::companyId();
        
        $logs = db()->fetchAll(
            "SELECT al.*, u.name as user_name
             FROM activity_logs al
             LEFT JOIN users u ON al.user_id = u.id
             WHERE al.company_id = ?
             ORDER BY al.created_at DESC
             LIMIT 1000",
            [$companyId]
        );
        
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="activity_logs_' . date('Ymd_His') . '.csv"');
        
        $output = fopen('php://output', 'w');
        
        // Headers
        fputcsv($output, ['Tanggal', 'User', 'Aksi', 'Modul', 'Deskripsi', 'IP']);
        
        // Data
        foreach ($logs as $log) {
            fputcsv($output, [
                $log['created_at'],
                $log['user_name'] ?? '-',
                $log['action'],
                $log['entity_type'],
                $log['description'],
                $log['ip_address']
            ]);
        }
        
        fclose($output);
        exit;
    }
}
