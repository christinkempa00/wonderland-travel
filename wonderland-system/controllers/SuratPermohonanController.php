<?php
/**
 * ================================================================
 * TRAVEL MANAGEMENT SYSTEM - Surat Permohonan Pembayaran Controller
 * ================================================================
 * Surat cutoff mingguan untuk klien dengan tagihan belum terbayarkan.
 * User memilih invoice/pesanan mana saja yang mau dilampirkan.
 *
 * Template surat masih placeholder — menyusul dari user, lihat generate().
 */

class SuratPermohonanController {

    /**
     * Client selection + daftar tagihan belum terbayarkan
     * GET /surat-permohonan
     */
    public function index(): void {
        $companyId = Session::companyId();
        $clientId = (int) ($_GET['client_id'] ?? 0);

        $clientsWithOutstanding = db()->fetchAll(
            "SELECT c.id, c.name, COUNT(o.id) as outstanding_count,
                    COALESCE(SUM(GREATEST(o.total_final_price - o.paid_amount, 0)), 0) as outstanding_total
             FROM clients c
             JOIN orders o ON o.client_id = c.id AND o.company_id = c.company_id
                 AND o.payment_status IN ('unpaid', 'partial')
             WHERE c.company_id = ?
             GROUP BY c.id, c.name
             ORDER BY c.name",
            [$companyId]
        );

        $selectedClient = null;
        $unpaidOrders = [];

        if ($clientId) {
            $selectedClient = db()->fetchOne(
                "SELECT * FROM clients WHERE id = ? AND company_id = ?",
                [$clientId, $companyId]
            );

            if ($selectedClient) {
                $unpaidOrders = db()->fetchAll(
                    "SELECT * FROM orders
                     WHERE company_id = ? AND client_id = ? AND payment_status IN ('unpaid', 'partial')
                     ORDER BY created_at DESC",
                    [$companyId, $clientId]
                );
            }
        }

        $data = [
            'pageTitle' => 'Surat Permohonan Pembayaran',
            'pageHeader' => true,
            'pageSubtitle' => 'Buat surat permohonan pembayaran untuk tagihan yang belum lunas',
            'breadcrumbs' => [
                ['label' => 'Surat Permohonan Pembayaran']
            ],
            'clientsWithOutstanding' => $clientsWithOutstanding,
            'selectedClient' => $selectedClient,
            'selectedClientId' => $clientId,
            'unpaidOrders' => $unpaidOrders
        ];

        render('surat-permohonan/index', $data);
    }

    /**
     * Generate the printable letter for the chosen orders.
     * GET /surat-permohonan/generate?client_id=X&cutoff_date=Y&order_ids[]=1&order_ids[]=2
     */
    public function generate(): void {
        $companyId = Session::companyId();
        $clientId = (int) ($_GET['client_id'] ?? 0);
        $cutoffDate = $_GET['cutoff_date'] ?? date('Y-m-d');
        $orderIds = array_filter(array_map('intval', $_GET['order_ids'] ?? []));

        if (!$clientId || empty($orderIds)) {
            die('Pilih klien dan minimal satu tagihan untuk dilampirkan.');
        }

        $client = db()->fetchOne("SELECT * FROM clients WHERE id = ? AND company_id = ?", [$clientId, $companyId]);
        if (!$client) {
            die('Klien tidak ditemukan.');
        }

        $placeholders = implode(',', array_fill(0, count($orderIds), '?'));
        $params = array_merge([$companyId, $clientId], $orderIds);
        $orders = db()->fetchAll(
            "SELECT * FROM orders
             WHERE company_id = ? AND client_id = ? AND id IN ({$placeholders})
             ORDER BY created_at ASC",
            $params
        );

        if (empty($orders)) {
            die('Tagihan yang dipilih tidak ditemukan.');
        }

        $company = db()->fetchOne("SELECT * FROM companies WHERE id = ?", [$companyId]);
        if (!$company) {
            $company = [];
        }

        $totalOutstanding = 0;
        foreach ($orders as $order) {
            $totalOutstanding += max(0, (float) $order['total_final_price'] - (float) $order['paid_amount']);
        }

        require BASE_PATH . '/views/surat-permohonan/print.php';
    }
}
