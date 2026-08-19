<?php
/**
 * ================================================================
 * TRAVEL MANAGEMENT SYSTEM - Order Controller
 * ================================================================
 * 
 * COMPLETE VERSION WITH ACCOUNTING INTEGRATION - FIXED v2
 * 
 * PERBAIKAN:
 * - parseItems() sekarang menyimpan num_days
 * - Kalkulasi harga sekarang benar (qty × days × price)
 * 
 * PART 1 of 3: CRUD Operations
 */

require_once MODELS_PATH . '/Order.php';
require_once MODELS_PATH . '/Client.php';

// Load accounting helper if available
$accountingHelperPath = HELPERS_PATH . '/AccountingHelper.php';
if (file_exists($accountingHelperPath)) {
    require_once $accountingHelperPath;
}

class OrderController {
    
    /**
     * List orders
     */
    public function index(): void {
        $companyId = Session::companyId();
        $page = (int) ($_GET['page'] ?? 1);
        $perPage = PAGINATION_PER_PAGE;
        
        $filters = [
            'search' => trim($_GET['search'] ?? ''),
            'status' => $_GET['status'] ?? '',
            'payment_status' => $_GET['payment_status'] ?? ''
        ];
        
        // Get orders with client info directly
        $where = "o.company_id = ?";
        $params = [$companyId];
        
        if (!empty($filters['status'])) {
            $where .= " AND o.status = ?";
            $params[] = $filters['status'];
        }
        
        if (!empty($filters['payment_status'])) {
            $where .= " AND o.payment_status = ?";
            $params[] = $filters['payment_status'];
        }
        
        if (!empty($filters['search'])) {
            $where .= " AND (o.order_number LIKE ? OR o.event_name LIKE ? OR c.name LIKE ?)";
            $params[] = "%{$filters['search']}%";
            $params[] = "%{$filters['search']}%";
            $params[] = "%{$filters['search']}%";
        }
        
        // Count total
        $countSql = "SELECT COUNT(*) FROM orders o LEFT JOIN clients c ON o.client_id = c.id WHERE {$where}";
        $total = (int) db()->fetchColumn($countSql, $params);
        
        $offset = ($page - 1) * $perPage;
        
        // Get orders with event dates for proper display
        $sql = "SELECT o.*, c.name as client_name 
                FROM orders o 
                LEFT JOIN clients c ON o.client_id = c.id 
                WHERE {$where} 
                ORDER BY o.created_at DESC 
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
        
        $data = [
            'pageTitle' => 'Daftar Pesanan',
            'pageHeader' => true,
            'pageSubtitle' => 'Kelola pesanan pelanggan',
            'pageActions' => $this->getPageActions(),
            'orders' => $orders,
            'pagination' => $pagination,
            'filters' => $filters,
            'statuses' => ORDER_STATUSES,
            'paymentStatuses' => PAYMENT_STATUSES
        ];
        
        render('orders/index', $data);
    }
    
    /**
     * Create order form
     */
    public function create(): void {
        $companyId = Session::companyId();

        $data = [
            'pageTitle' => 'Buat Pesanan',
            'pageHeader' => true,
            'breadcrumbs' => [
                ['label' => 'Pesanan', 'url' => '/orders'],
                ['label' => 'Buat']
            ],
            'order' => null,
            'items' => [],
            'clients' => Client::getForDropdown($companyId),
            'clientsUsesDivisi' => $this->getClientsUsesDivisi($companyId),
            'itemTypes' => ITEM_TYPES,
            'markupTypes' => MARKUP_TYPES,
            'defaultMarkupType' => getCompanySetting($companyId, 'default_markup_type', 'percentage'),
            'defaultMarkupValue' => getCompanySetting($companyId, 'default_markup_value', 10),
            'isEdit' => false
        ];

        render('orders/form', $data);
    }

    /**
     * Store new order
     */
    public function store(): void {
        $companyId = Session::companyId();
        
        // Validate
        $errors = $this->validateOrder($_POST);
        
        if (!empty($errors)) {
            flashInput($_POST);
            foreach ($errors as $error) {
                Session::flash('error', $error);
            }
            redirect('/orders/create');
            return;
        }
        
        try {
            // Prepare order data
            $orderData = [
                'client_id' => $_POST['client_id'] ?: null,
                'order_date' => $_POST['order_date'],
                'event_date' => $_POST['event_date'] ?: null,
                'event_end_date' => $_POST['event_end_date'] ?: null,
                'event_name' => trim($_POST['event_name'] ?? ''),
                'pic_name' => trim($_POST['pic_name'] ?? ''),
                'pic_phone' => trim($_POST['pic_phone'] ?? ''),
                'divisi' => $this->resolveDivisi($_POST),
                'description' => trim($_POST['description'] ?? ''),
                'notes' => trim($_POST['notes'] ?? ''),
                'status' => 'invoiced',
                'payment_status' => 'unpaid',
                'paid_amount' => 0
            ];
            
            // Prepare items - FIXED: now includes num_days
            $items = $this->parseItems($_POST);
            
            if (empty($items)) {
                Session::flash('error', 'Minimal satu item pesanan diperlukan.');
                flashInput($_POST);
                redirect('/orders/create');
                return;
            }
            
            // === AUTO-SET SERVICE_TYPE FROM FIRST ITEM ===
            $orderData['service_type'] = $items[0]['item_type'] ?? 'travel';
            // === END AUTO-SET ===
            
            // Create order
            $order = Order::createWithItems($companyId, $orderData, $items);
            
            logActivity('create', 'order', $order->id, 'order', 'Created order: ' . $order->order_number);

            if (function_exists('notifyCompanyUsers')) {
                notifyCompanyUsers(
                    $companyId,
                    'Pesanan Baru',
                    "Pesanan {$order->order_number} telah dibuat.",
                    'info',
                    '/orders/' . $order->id,
                    Session::userId()
                );
            }

            Session::flash('success', 'Pesanan berhasil dibuat dengan nomor ' . $order->order_number);
            redirect('/orders/' . $order->id);
            
        } catch (Exception $e) {
            Session::flash('error', 'Gagal membuat pesanan: ' . $e->getMessage());
            flashInput($_POST);
            redirect('/orders/create');
        }
    }
    
    /**
     * Show order detail
     */
    public function show(int $id): void {
        $order = $this->findOrder($id);
        
        if (!$order) {
            Session::flash('error', 'Pesanan tidak ditemukan.');
            redirect('/orders');
            return;
        }
        
        $orderData = Order::getWithClient($id);
        $items = $order->getItems();
        
        // Check if order has hotel items
        $hasHotelItems = false;
        foreach ($items as $item) {
            if ($item['item_type'] === 'hotel') {
                $hasHotelItems = true;
                break;
            }
        }
        
        // Count hotel guests
        $hotelGuestCount = 0;
        if ($hasHotelItems) {
            $hotelGuestCount = (int) db()->fetchColumn(
                "SELECT COUNT(*) FROM hotel_guests WHERE order_id = ?", 
                [$id]
            );
        }
        
        // Check if order has flight items
        $hasFlightItems = false;
        foreach ($items as $item) {
            if ($item['item_type'] === 'flight') {
                $hasFlightItems = true;
                break;
            }
        }
        
        // Count flight details
        $flightDetailCount = 0;
        if ($hasFlightItems) {
            $flightDetailCount = (int) db()->fetchColumn(
                "SELECT COUNT(*) FROM flight_details WHERE order_id = ?", 
                [$id]
            );
        }
        
        // Check if order has rental items
        $hasRentalItems = false;
        foreach ($items as $item) {
            if ($item['item_type'] === 'rental') {
                $hasRentalItems = true;
                break;
            }
        }
        
        // Count rental details
        $rentalDetailCount = 0;
        if ($hasRentalItems) {
            try {
                $rentalDetailCount = (int) db()->fetchColumn(
                    "SELECT COUNT(*) FROM rental_details WHERE order_id = ?", 
                    [$id]
                );
            } catch (Exception $e) {
                $rentalDetailCount = 0;
            }
        }
        
        // Check if order has vehicle items for vehicle documents
        $hasVehicleItems = false;
        foreach ($items as $item) {
            if (in_array($item['item_type'], ['rental', 'bus', 'towing'])) {
                $hasVehicleItems = true;
                break;
            }
        }
        
        // Count vehicle documents
        $vehicleDocCount = 0;
        if ($hasVehicleItems) {
            try {
                $vehicleDocCount = (int) db()->fetchColumn(
                    "SELECT COUNT(*) FROM vehicle_documents WHERE order_id = ?", 
                    [$id]
                );
            } catch (Exception $e) {
                $vehicleDocCount = 0;
            }
        }
        
        // Get order payments for accounting
        $orderPayments = [];
        if (function_exists('getOrderPayments')) {
            $orderPayments = getOrderPayments($id);
        }
        
        $data = [
            'pageTitle' => 'Pesanan ' . $order->order_number,
            'pageHeader' => true,
            'breadcrumbs' => [
                ['label' => 'Pesanan', 'url' => '/orders'],
                ['label' => $order->order_number]
            ],
            'order' => $order,
            'orderData' => $orderData,
            'items' => $items,
            'client' => $order->getClient(),
            'documents' => $order->getDocuments(),
            'payments' => $order->getPayments(),
            'orderPayments' => $orderPayments,
            'profitShares' => $order->getProfitShares(),
            'statuses' => ORDER_STATUSES,
            'documentTypes' => DOCUMENT_TYPES,
            'hasHotelItems' => $hasHotelItems,
            'hotelGuestCount' => $hotelGuestCount,
            'hasFlightItems' => $hasFlightItems,
            'flightDetailCount' => $flightDetailCount,
            'hasRentalItems' => $hasRentalItems,
            'rentalDetailCount' => $rentalDetailCount,
            'hasVehicleItems' => $hasVehicleItems,
            'vehicleDocCount' => $vehicleDocCount
        ];
        
        render('orders/show', $data);
    }
    
    /**
     * Edit order form
     */
    public function edit(int $id): void {
        $order = $this->findOrder($id);
        
        if (!$order) {
            Session::flash('error', 'Pesanan tidak ditemukan.');
            redirect('/orders');
            return;
        }
        
        // Check if editable
        if (!in_array($order->status, ['draft', 'quotation', 'invoiced'])) {
            Session::flash('error', 'Pesanan dengan status ini tidak dapat diedit.');
            redirect('/orders/' . $id);
            return;
        }
        
        $companyId = Session::companyId();

        $data = [
            'pageTitle' => 'Edit Pesanan',
            'pageHeader' => true,
            'breadcrumbs' => [
                ['label' => 'Pesanan', 'url' => '/orders'],
                ['label' => $order->order_number, 'url' => '/orders/' . $order->id],
                ['label' => 'Edit']
            ],
            'order' => $order,
            'items' => $order->getItems(),
            'clients' => Client::getForDropdown($companyId),
            'clientsUsesDivisi' => $this->getClientsUsesDivisi($companyId),
            'itemTypes' => ITEM_TYPES,
            'markupTypes' => MARKUP_TYPES,
            'defaultMarkupType' => getCompanySetting($companyId, 'default_markup_type', 'percentage'),
            'defaultMarkupValue' => getCompanySetting($companyId, 'default_markup_value', 10),
            'isEdit' => true
        ];

        render('orders/form', $data);
    }

    /**
     * Update order
     */
    public function update(int $id): void {
        $order = $this->findOrder($id);
        
        if (!$order) {
            Session::flash('error', 'Pesanan tidak ditemukan.');
            redirect('/orders');
            return;
        }
        
        // Check if editable
        if (!in_array($order->status, ['draft', 'quotation', 'invoiced'])) {
            Session::flash('error', 'Pesanan dengan status ini tidak dapat diedit.');
            redirect('/orders/' . $id);
            return;
        }
        
        $errors = $this->validateOrder($_POST);
        
        if (!empty($errors)) {
            flashInput($_POST);
            foreach ($errors as $error) {
                Session::flash('error', $error);
            }
            redirect('/orders/' . $id . '/edit');
            return;
        }
        
        try {
            // Prepare data
            $orderData = [
                'client_id' => $_POST['client_id'] ?: null,
                'order_date' => $_POST['order_date'],
                'event_date' => $_POST['event_date'] ?: null,
                'event_end_date' => $_POST['event_end_date'] ?: null,
                'event_name' => trim($_POST['event_name'] ?? ''),
                'pic_name' => trim($_POST['pic_name'] ?? ''),
                'pic_phone' => trim($_POST['pic_phone'] ?? ''),
                'divisi' => $this->resolveDivisi($_POST),
                'description' => trim($_POST['description'] ?? ''),
                'notes' => trim($_POST['notes'] ?? ''),
                'status' => 'invoiced'
            ];
            
            // Prepare items - FIXED: now includes num_days
            $items = $this->parseItems($_POST);
            
            if (empty($items)) {
                Session::flash('error', 'Minimal satu item pesanan diperlukan.');
                flashInput($_POST);
                redirect('/orders/' . $id . '/edit');
                return;
            }
            
            // === AUTO-SET SERVICE_TYPE FROM FIRST ITEM ===
            $orderData['service_type'] = $items[0]['item_type'] ?? 'travel';
            // === END AUTO-SET ===
            
            $order->updateWithItems($orderData, $items);
            
            // === UPDATE INVOICE JOURNAL IF PRICE CHANGED ===
            if (function_exists('updateOrderInvoiceJournal')) {
                // Refresh order data to get new totals
                $updatedOrder = Order::find($id);
                if ($updatedOrder) {
                    $orderArray = $updatedOrder->toArray();
                    $result = updateOrderInvoiceJournal($orderArray);
                    
                    if ($result['success'] && $result['action'] === 'adjusted') {
                        $diff = $result['difference'];
                        $diffFormatted = 'Rp ' . number_format(abs($diff), 0, ',', '.');
                        $diffMsg = $diff >= 0 ? "bertambah {$diffFormatted}" : "berkurang {$diffFormatted}";
                        Session::flash('info', "Jurnal penyesuaian dibuat karena total {$diffMsg}");
                    }
                }
            }
            // === END UPDATE INVOICE JOURNAL ===
            
            logActivity('update', 'order', $order->id, 'order', 'Updated order: ' . $order->order_number);
            
            Session::flash('success', 'Pesanan berhasil diperbarui.');
            redirect('/orders/' . $id);
            
        } catch (Exception $e) {
            Session::flash('error', 'Gagal memperbarui pesanan: ' . $e->getMessage());
            flashInput($_POST);
            redirect('/orders/' . $id . '/edit');
        }
    }
    
    /**
     * Delete order
     */
    public function destroy(int $id): void {
        $order = $this->findOrder($id);
        
        if (!$order) {
            Session::flash('error', 'Pesanan tidak ditemukan.');
            redirect('/orders');
            return;
        }
        
        $orderNumber = $order->order_number;
        
        // Delete related data
        db()->delete('order_items', 'order_id = ?', [$id]);
        db()->delete('profit_shares', 'order_id = ?', [$id]);
        db()->delete('documents', 'order_id = ?', [$id]);
        db()->delete('hotel_guests', 'order_id = ?', [$id]);
        
        try { db()->delete('flight_details', 'order_id = ?', [$id]); } catch (Exception $e) {}
        try { db()->delete('rental_details', 'order_id = ?', [$id]); } catch (Exception $e) {}
        try { db()->delete('vehicle_documents', 'order_id = ?', [$id]); } catch (Exception $e) {}
        try { db()->delete('order_payments', 'order_id = ?', [$id]); } catch (Exception $e) {}
        
        $order->delete();
        
        logActivity('delete', 'order', $id, 'order', 'Deleted order: ' . $orderNumber);
        
        Session::flash('success', 'Pesanan berhasil dihapus.');
        redirect('/orders');
    }
    
    
    
    
    /**
     * Update order status
     * WITH AUTO-CREATE INVOICE JOURNAL
     */
    public function updateStatus(int $id): void {
        $order = $this->findOrder($id);
        
        if (!$order) {
            jsonError('Pesanan tidak ditemukan.', 404);
            return;
        }
        
        $status = $_POST['status'] ?? '';
        
        if (!isset(ORDER_STATUSES[$status])) {
            jsonError('Status tidak valid.', 400);
            return;
        }
        
        $order->updateStatus($status);
        
        // === AUTO-CREATE INVOICE JOURNAL ===
        if ($status === 'invoiced' && function_exists('createOrderInvoiceJournal')) {
            try {
                $orderArray = $order->toArray();
                $journalId = createOrderInvoiceJournal($orderArray);
                if ($journalId) {
                    // Journal created successfully
                }
            } catch (Exception $e) {
                error_log("Failed to create invoice journal for order {$id}: " . $e->getMessage());
            }
        }
        // === END AUTO-CREATE ===
        
        logActivity('update_status', 'order', $order->id, 'order',
            "Changed status to {$status}: " . $order->order_number);

        if (in_array($status, ['completed', 'cancelled'], true) && function_exists('notifyCompanyUsers')) {
            $statusLabel = ORDER_STATUSES[$status]['label'] ?? $status;
            notifyCompanyUsers(
                (int)$order->company_id,
                'Status Pesanan Diperbarui',
                "Pesanan {$order->order_number} sekarang berstatus {$statusLabel}.",
                $status === 'cancelled' ? 'warning' : 'success',
                '/orders/' . $order->id,
                Session::userId()
            );
        }

        jsonSuccess(['status' => $status, 'label' => ORDER_STATUSES[$status]['label']]);
    }
    
    /**
     * Update payment status (quick update)
     */
    public function updatePaymentStatus(int $id): void {
        $order = $this->findOrder($id);
        
        if (!$order) {
            Session::flash('error', 'Pesanan tidak ditemukan.');
            redirect('/orders');
            return;
        }
        
        $paymentStatus = $_POST['payment_status'] ?? '';
        $paidAt = $_POST['paid_at'] ?? null;
        
        // Validate payment status
        if (!isset(PAYMENT_STATUSES[$paymentStatus])) {
            Session::flash('error', 'Status pembayaran tidak valid.');
            redirect('/orders/' . $id);
            return;
        }
        
        try {
            // Prepare update data
            $updateData = [
                'payment_status' => $paymentStatus
            ];
            
            // Set paid_at based on payment status
            if ($paymentStatus === 'paid' || $paymentStatus === 'partial') {
                $updateData['paid_at'] = $paidAt ?: date('Y-m-d');
            } else {
                $updateData['paid_at'] = null;
            }
            
            // Update order
            db()->update('orders', $updateData, 'id = ?', [$id]);
            
            // Log activity
            $statusLabel = PAYMENT_STATUSES[$paymentStatus]['label'];
            logActivity('update_payment', 'order', $order->id, 'order', 
                "Changed payment status to {$statusLabel}: " . $order->order_number);
            
            Session::flash('success', 'Status pembayaran berhasil diperbarui.');
            
        } catch (Exception $e) {
            Session::flash('error', 'Gagal memperbarui status pembayaran: ' . $e->getMessage());
        }
        
        // Check if request came from index page
        $referer = $_SERVER['HTTP_REFERER'] ?? '';
        if (strpos($referer, '/orders') !== false && strpos($referer, '/orders/' . $id) === false) {
            redirect('/orders');
        } else {
            redirect('/orders/' . $id);
        }
    }

// ==========================================
// END OF PART 1 - Continue to Part 2
// ==========================================

    // ========================================
    // PAYMENT MANAGEMENT (WITH ACCOUNTING)
    // ========================================

    /**
     * Show payment form for an order
     * GET /orders/{id}/payment
     */
    public function payment(int $id): void {
        $order = $this->findOrder($id);
        
        if (!$order) {
            Session::flash('error', 'Pesanan tidak ditemukan.');
            redirect('/orders');
            return;
        }
        
        $companyId = Session::companyId();
        $client = $order->getClient();
        
        // Get payment history
        $payments = [];
        if (function_exists('getOrderPayments')) {
            $payments = getOrderPayments($id);
        }
        
        // Get bank/cash options
        $bankCashOptions = [];
        if (function_exists('getBankCashDropdown')) {
            $bankCashOptions = getBankCashDropdown($companyId);
        } else {
            // Fallback
            try {
                $bankCashList = db()->fetchAll(
                    "SELECT id, name, type FROM bank_cash WHERE company_id = ? AND is_active = 1 ORDER BY name",
                    [$companyId]
                );
                foreach ($bankCashList as $bc) {
                    $bankCashOptions[$bc['id']] = ($bc['type'] === 'cash' ? '💵 ' : '🏦 ') . $bc['name'];
                }
            } catch (Exception $e) {}
        }
        
        $data = [
            'pageTitle' => 'Pembayaran - ' . $order->order_number,
            'pageHeader' => true,
            'breadcrumbs' => [
                ['label' => 'Pesanan', 'url' => '/orders'],
                ['label' => $order->order_number, 'url' => '/orders/' . $id],
                ['label' => 'Pembayaran']
            ],
            'order' => $order,
            'client' => $client,
            'payments' => $payments,
            'bankCashOptions' => $bankCashOptions
        ];
        
        render('orders/payment', $data);
    }

    /**
     * Store a payment for an order
     * POST /orders/{id}/payment
     */
    public function storePayment(int $id): void {
        $order = $this->findOrder($id);
        
        if (!$order) {
            Session::flash('error', 'Pesanan tidak ditemukan.');
            redirect('/orders');
            return;
        }
        
        $orderArray = $order->toArray();
        
        // Calculate remaining
        $totalPrice = (float)($orderArray['total_final_price'] ?? 0);
        $paidAmount = (float)($orderArray['paid_amount'] ?? 0);
        $remainingAmount = max(0, $totalPrice - $paidAmount);
        
        // Get input
        $amount = (float) preg_replace('/[^\d]/', '', $_POST['amount'] ?? '0');
        $paymentDate = $_POST['payment_date'] ?? date('Y-m-d');
        $paymentMethod = $_POST['payment_method'] ?? 'transfer';
        $bankCashId = !empty($_POST['bank_cash_id']) ? (int)$_POST['bank_cash_id'] : null;
        $reference = trim($_POST['reference'] ?? '');
        $notes = trim($_POST['notes'] ?? '');
        
        // Validate
        if ($amount <= 0) {
            Session::flash('error', 'Jumlah pembayaran harus lebih dari 0.');
            redirect('/orders/' . $id . '/payment');
            return;
        }
        
        if ($amount > $remainingAmount) {
            Session::flash('error', 'Jumlah pembayaran melebihi sisa tagihan (' . formatRupiah($remainingAmount) . ').');
            redirect('/orders/' . $id . '/payment');
            return;
        }
        
        // Optional proof-of-payment upload
        $proofImage = null;
        if (!empty($_FILES['proof_image']['name'])) {
            [$uploadOk, $uploadResult] = uploadFile(
                $_FILES['proof_image'],
                UPLOADS_PATH . '/payment-proofs',
                ALLOWED_IMAGE_TYPES,
                2 * 1024 * 1024
            );
            if ($uploadOk) {
                $proofImage = $uploadResult;
            } else {
                Session::flash('error', 'Bukti pembayaran gagal diupload: ' . $uploadResult);
                redirect('/orders/' . $id . '/payment');
                return;
            }
        }

        $result = recordPayment(
            (int)$orderArray['company_id'],
            $id,
            $amount,
            $paymentDate,
            $paymentMethod,
            $bankCashId,
            $reference,
            $notes,
            $proofImage
        );

        if ($result['success']) {
            Session::flash('success', 'Pembayaran sebesar ' . formatRupiah($amount) . ' berhasil dicatat.');
            
            // Check if now fully paid
            $updatedOrder = $this->findOrder($id);
            if ($updatedOrder && $updatedOrder->payment_status === 'paid') {
                Session::flash('info', '🎉 Order ini sudah LUNAS!');
                if (function_exists('notifyCompanyUsers')) {
                    notifyCompanyUsers(
                        (int)$orderArray['company_id'],
                        'Pembayaran Lunas',
                        "Order {$updatedOrder->order_number} telah lunas.",
                        'success',
                        '/orders/' . $id,
                        Session::userId()
                    );
                }
            }
        } else {
            Session::flash('error', $result['message']);
        }
        
        redirect('/orders/' . $id . '/payment');
    }

    /**
     * Void/cancel a payment
     * POST /orders/{id}/payment/{paymentId}/void
     */
    public function voidPayment(int $id, int $paymentId): void {
        $order = $this->findOrder($id);
        
        if (!$order) {
            Session::flash('error', 'Pesanan tidak ditemukan.');
            redirect('/orders');
            return;
        }
        
        if (!function_exists('voidOrderPayment')) {
            Session::flash('error', 'AccountingHelper belum di-install.');
            redirect('/orders/' . $id . '/payment');
            return;
        }
        
        $result = voidOrderPayment($paymentId);
        
        if ($result['success']) {
            Session::flash('success', $result['message']);
        } else {
            Session::flash('error', $result['message']);
        }
        
        redirect('/orders/' . $id . '/payment');
    }

    // ========================================
    // HOTEL GUESTS MANAGEMENT
    // ========================================
    
    /**
     * Hotel guests form
     */
    public function hotelGuests(int $id): void {
        $order = $this->findOrder($id);
        
        if (!$order) {
            Session::flash('error', 'Pesanan tidak ditemukan.');
            redirect('/orders');
            return;
        }
        
        // Get hotel items from this order
        $hotelItems = db()->fetchAll(
            "SELECT * FROM order_items WHERE order_id = ? AND item_type = 'hotel' ORDER BY id", 
            [$id]
        );
        
        if (empty($hotelItems)) {
            Session::flash('error', 'Pesanan ini tidak memiliki item hotel.');
            redirect('/orders/' . $id);
            return;
        }
        
        // Get existing guests
        $existingGuests = db()->fetchAll(
            "SELECT * FROM hotel_guests WHERE order_id = ? ORDER BY order_item_id, id", 
            [$id]
        );
        
        // Group guests by hotel item
        $guestsByHotel = [];
        foreach ($existingGuests as $guest) {
            $key = $guest['order_item_id'] ?: 0;
            if (!isset($guestsByHotel[$key])) {
                $guestsByHotel[$key] = [];
            }
            $guestsByHotel[$key][] = $guest;
        }
        
        // Get client info
        $client = $order->getClient();
        
        $data = [
            'pageTitle' => 'Data Tamu Hotel',
            'pageHeader' => true,
            'breadcrumbs' => [
                ['label' => 'Pesanan', 'url' => '/orders'],
                ['label' => $order->order_number, 'url' => '/orders/' . $order->id],
                ['label' => 'Data Tamu Hotel']
            ],
            'order' => $order,
            'client' => $client,
            'hotelItems' => $hotelItems,
            'existingGuests' => $existingGuests,
            'guestsByHotel' => $guestsByHotel
        ];
        
        render('orders/hotel-guests', $data);
    }
    
    /**
     * Save hotel guests
     */
    public function saveHotelGuests(int $id): void {
        $order = $this->findOrder($id);
        
        if (!$order) {
            Session::flash('error', 'Pesanan tidak ditemukan.');
            redirect('/orders');
            return;
        }
        
        try {
            // ============================================
            // FIXED: Get hotel items with description for hotel_name
            // ============================================
            $hotelItems = db()->fetchAll(
                "SELECT id, description FROM order_items WHERE order_id = ? AND item_type = 'hotel'", [$id]
            );
            $hotelDescMap = [];
            foreach ($hotelItems as $hi) {
                $hotelDescMap[$hi['id']] = $hi['description'] ?? '';
            }
            
            // Insert guests from room-based form
            $rooms = $_POST['rooms'] ?? [];
            $inserted = 0;
            
            foreach ($rooms as $hotelId => $hotelRooms) {
                $hotelId = (int)$hotelId;
                
                // ============================================
                // FIXED: Delete hanya untuk hotel ini, bukan semua
                // ============================================
                db()->delete('hotel_guests', 'order_id = ? AND order_item_id = ?', [$id, $hotelId]);
                
                // FIXED: Get hotel_name from order_items.description
                $hotelName = $hotelDescMap[$hotelId] ?? '';
                
                foreach ($hotelRooms as $room) {
                    $guest1 = trim($room['guest_1'] ?? '');
                    $guest2 = trim($room['guest_2'] ?? '');
                    
                    if (empty($guest1) && empty($guest2)) {
                        continue;
                    }
                    
                    $roomNumber = trim($room['room_number'] ?? '') ?: null;
                    $roomType = trim($room['room_type'] ?? '') ?: null;
                    $checkIn = !empty($room['check_in_date']) ? $room['check_in_date'] : null;
                    $checkOut = !empty($room['check_out_date']) ? $room['check_out_date'] : null;
                    
                    // Insert guest 1
                    if (!empty($guest1)) {
                        db()->insert('hotel_guests', [
                            'order_id' => $id,
                            'order_item_id' => $hotelId,
                            'hotel_name' => $hotelName,  // FIXED: dari order_items.description
                            'guest_name' => $guest1,
                            'room_number' => $roomNumber,
                            'room_type' => $roomType,
                            'check_in_date' => $checkIn,
                            'check_out_date' => $checkOut,
                            'notes' => null
                        ]);
                        $inserted++;
                    }
                    
                    // Insert guest 2 (if provided)
                    if (!empty($guest2)) {
                        db()->insert('hotel_guests', [
                            'order_id' => $id,
                            'order_item_id' => $hotelId,
                            'hotel_name' => $hotelName,  // FIXED: dari order_items.description
                            'guest_name' => $guest2,
                            'room_number' => $roomNumber,
                            'room_type' => $roomType,
                            'check_in_date' => $checkIn,
                            'check_out_date' => $checkOut,
                            'notes' => null
                        ]);
                        $inserted++;
                    }
                }
            }
            
            // Also update hotel info in order_items if provided
            if (!empty($_POST['hotel_info'])) {
                foreach ($_POST['hotel_info'] as $itemId => $info) {
                    $updateData = [];
                    if (!empty($info['hotel_name'])) {
                        $updateData['hotel_name'] = trim($info['hotel_name']);
                    }
                    if (!empty($info['room_type'])) {
                        $updateData['room_type'] = trim($info['room_type']);
                    }
                    if (!empty($info['check_in_date'])) {
                        $updateData['check_in_date'] = $info['check_in_date'];
                    }
                    if (!empty($info['check_out_date'])) {
                        $updateData['check_out_date'] = $info['check_out_date'];
                    }
                    
                    if (!empty($updateData)) {
                        db()->update('order_items', $updateData, 'id = ?', [(int)$itemId]);
                    }
                }
            }
            
            logActivity('update', 'order', $order->id, 'hotel_guests', 
                "Updated hotel guests ({$inserted} tamu): " . $order->order_number);
            
            Session::flash('success', "Berhasil menyimpan {$inserted} data tamu hotel.");
            redirect('/orders/' . $id . '/hotel-guests');
            
        } catch (Exception $e) {
            Session::flash('error', 'Gagal menyimpan data tamu: ' . $e->getMessage());
            redirect('/orders/' . $id . '/hotel-guests');
        }
    }
    
    /**
     * Hotel attachment print view
     */
    public function hotelAttachment(int $id): void {
        $order = $this->findOrder($id);
        
        if (!$order) {
            die('Pesanan tidak ditemukan.');
        }
        
        $hotelItems = db()->fetchAll(
            "SELECT * FROM order_items WHERE order_id = ? AND item_type = 'hotel' ORDER BY id", 
            [$id]
        );
        
        if (empty($hotelItems)) {
            die('Pesanan ini tidak memiliki item hotel.');
        }
        
        $guests = db()->fetchAll(
            "SELECT * FROM hotel_guests WHERE order_id = ? ORDER BY order_item_id, room_number, id", 
            [$id]
        );
        
        $guestsByHotel = [];
        foreach ($guests as $guest) {
            $key = $guest['order_item_id'] ?: 0;
            if (!isset($guestsByHotel[$key])) {
                $guestsByHotel[$key] = [];
            }
            $guestsByHotel[$key][] = $guest;
        }
        
        $companyId = Session::companyId();
        $company = db()->fetchOne("SELECT * FROM companies WHERE id = ?", [$companyId]);
        $client = $order->getClient();
        
        $data = [
            'order' => $order,
            'company' => $company ?: [],
            'client' => $client ?: [],
            'hotelItems' => $hotelItems,
            'guests' => $guests,
            'guestsByHotel' => $guestsByHotel
        ];
        
        extract($data);
        require VIEWS_PATH . '/documents/templates/hotel-attachment.php';
        exit;
    }
    
    /**
     * Upload hotel logo (per hotel item)
     */
    public function uploadHotelLogo(int $id): void {
        header('Content-Type: application/json');
        
        try {
            $order = $this->findOrder($id);
            
            if (!$order) {
                echo json_encode(['success' => false, 'message' => 'Pesanan tidak ditemukan.']);
                return;
            }
            
            $hotelItemId = $_POST['hotel_item_id'] ?? null;
            if (!$hotelItemId) {
                echo json_encode(['success' => false, 'message' => 'Hotel item ID tidak ditemukan.']);
                return;
            }
            
            $hotelItem = db()->fetchOne(
                "SELECT * FROM order_items WHERE id = ? AND order_id = ? AND item_type = 'hotel'",
                [$hotelItemId, $id]
            );
            
            if (!$hotelItem) {
                echo json_encode(['success' => false, 'message' => 'Hotel item tidak valid.']);
                return;
            }
            
            if (empty($_FILES['logo']) || $_FILES['logo']['error'] !== UPLOAD_ERR_OK) {
                echo json_encode(['success' => false, 'message' => 'Tidak ada file yang diupload.']);
                return;
            }
            
            $file = $_FILES['logo'];
            $allowedTypes = ['image/png', 'image/jpeg', 'image/jpg', 'image/gif'];
            
            if (!in_array($file['type'], $allowedTypes)) {
                echo json_encode(['success' => false, 'message' => 'Format file tidak didukung.']);
                return;
            }
            
            if ($file['size'] > 2 * 1024 * 1024) {
                echo json_encode(['success' => false, 'message' => 'Ukuran file maksimal 2MB.']);
                return;
            }
            
            $uploadDir = BASE_PATH . '/uploads/logos';
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0755, true);
            }
            
            $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
            $filename = 'hotel_' . $hotelItemId . '_' . time() . '.' . $ext;
            $destination = $uploadDir . '/' . $filename;
            
            $oldLogo = $hotelItem['attachment_logo'] ?? '';
            if ($oldLogo && file_exists($uploadDir . '/' . $oldLogo)) {
                @unlink($uploadDir . '/' . $oldLogo);
            }
            
            if (!move_uploaded_file($file['tmp_name'], $destination)) {
                echo json_encode(['success' => false, 'message' => 'Gagal menyimpan file.']);
                return;
            }
            
            db()->query("UPDATE order_items SET attachment_logo = ?, updated_at = NOW() WHERE id = ?", [$filename, $hotelItemId]);
            
            echo json_encode([
                'success' => true,
                'data' => [
                    'filename' => $filename,
                    'url' => url('/uploads/logos/' . $filename)
                ]
            ]);
            
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
        }
    }
    
    /**
     * Delete hotel logo (per hotel item)
     */
    public function deleteHotelLogo(int $id): void {
        header('Content-Type: application/json');
        
        try {
            $order = $this->findOrder($id);
            
            if (!$order) {
                echo json_encode(['success' => false, 'message' => 'Pesanan tidak ditemukan.']);
                return;
            }
            
            $data = json_decode(file_get_contents('php://input'), true);
            $hotelItemId = $data['hotel_item_id'] ?? null;
            
            if (!$hotelItemId) {
                echo json_encode(['success' => false, 'message' => 'Hotel item ID tidak ditemukan.']);
                return;
            }
            
            $hotelItem = db()->fetchOne(
                "SELECT * FROM order_items WHERE id = ? AND order_id = ? AND item_type = 'hotel'",
                [$hotelItemId, $id]
            );
            
            if (!$hotelItem) {
                echo json_encode(['success' => false, 'message' => 'Hotel item tidak valid.']);
                return;
            }
            
            $currentLogo = $hotelItem['attachment_logo'] ?? '';
            if ($currentLogo) {
                $logoPath = BASE_PATH . '/uploads/logos/' . $currentLogo;
                if (file_exists($logoPath)) {
                    @unlink($logoPath);
                }
                
                db()->query("UPDATE order_items SET attachment_logo = NULL, updated_at = NOW() WHERE id = ?", [$hotelItemId]);
            }
            
            echo json_encode(['success' => true, 'deleted' => true]);
            
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
        }
    }

// ==========================================
// END OF PART 2 - Continue to Part 3
// ==========================================

    // ========================================
    // FLIGHT GUESTS MANAGEMENT
    // ========================================
    
    /**
     * Show flight guests/passengers management page
     */
    public function flightGuests(int $id): void {
        $order = $this->findOrder($id);
        
        if (!$order) {
            Session::flash('error', 'Pesanan tidak ditemukan.');
            redirect('/orders');
            return;
        }
        
        $flightItems = db()->fetchAll(
            "SELECT * FROM order_items WHERE order_id = ? AND item_type = 'flight' ORDER BY id", 
            [$id]
        );
        
        if (empty($flightItems)) {
            Session::flash('error', 'Pesanan ini tidak memiliki item pesawat.');
            redirect('/orders/' . $id);
            return;
        }
        
        $details = [];
        $detailsByFlight = [];
        
        try {
            $details = db()->fetchAll(
                "SELECT * FROM flight_details WHERE order_id = ? ORDER BY order_item_id, id", 
                [$id]
            );
            
            foreach ($details as $detail) {
                $key = $detail['order_item_id'] ?: 0;
                if (!isset($detailsByFlight[$key])) {
                    $detailsByFlight[$key] = [];
                }
                $detailsByFlight[$key][] = $detail;
            }
        } catch (Exception $e) {
            $details = [];
            $detailsByFlight = [];
        }
        
        $client = $order->getClient();
        
        render('orders/flight-guests', [
            'order' => $order,
            'flightItems' => $flightItems,
            'details' => $details,
            'detailsByFlight' => $detailsByFlight,
            'existingDetails' => $details,
            'client' => $client
        ]);
    }
    
    /**
     * Save flight details/passengers
     */
    public function saveFlightGuests(int $id): void {
        $order = $this->findOrder($id);
        
        if (!$order) {
            Session::flash('error', 'Pesanan tidak ditemukan.');
            redirect('/orders');
            return;
        }
        
        try {
            // ============================================
            // FIXED: Get flight items with description for flight_name
            // ============================================
            $flightItems = db()->fetchAll(
                "SELECT id, description FROM order_items WHERE order_id = ? AND item_type = 'flight'", [$id]
            );
            $flightDescMap = [];
            foreach ($flightItems as $fi) {
                $flightDescMap[$fi['id']] = $fi['description'] ?? '';
            }
            
            $tickets = $_POST['tickets'] ?? [];
            $savedCount = 0;
            
            foreach ($tickets as $flightId => $flightTickets) {
                $flightId = (int)$flightId;
                $flightName = $flightDescMap[$flightId] ?? '';
                
                // ============================================
                // FIXED: Delete hanya untuk flight ini, bukan semua
                // ============================================
                try { db()->delete('flight_details', 'order_id = ? AND order_item_id = ?', [$id, $flightId]); } catch (Exception $e) {}
                
                foreach ($flightTickets as $ticket) {
                    $passengerName = trim($ticket['passenger_name'] ?? '');
                    if (empty($passengerName)) continue;
                    
                    try {
                        db()->insert('flight_details', [
                            'order_id' => $id,
                            'order_item_id' => $flightId,
                            'flight_name' => $flightName,  // FIXED: Simpan nama flight!
                            'passenger_name' => $passengerName,
                            'description' => trim($ticket['description'] ?? ''),
                            'price' => floatval($ticket['price'] ?? 0)
                        ]);
                    } catch (Exception $e) {
                        // Fallback jika kolom flight_name tidak ada
                        db()->insert('flight_details', [
                            'order_id' => $id,
                            'order_item_id' => $flightId,
                            'passenger_name' => $passengerName,
                            'description' => trim($ticket['description'] ?? ''),
                            'price' => floatval($ticket['price'] ?? 0)
                        ]);
                    }
                    $savedCount++;
                }
            }
            
            Session::flash('success', "Berhasil menyimpan {$savedCount} data penumpang.");
            
        } catch (Exception $e) {
            Session::flash('error', 'Gagal menyimpan: ' . $e->getMessage());
        }
        
        redirect('/orders/' . $id . '/flight-guests');
    }
    
    /**
     * Print flight attachment
     */
    public function flightAttachment(int $id): void {
        try {
            $order = $this->findOrder($id);
            
            if (!$order) {
                die('Pesanan tidak ditemukan.');
            }
            
            $flightItems = db()->fetchAll(
                "SELECT * FROM order_items WHERE order_id = ? AND item_type = 'flight' ORDER BY id", 
                [$id]
            );
            
            if (empty($flightItems)) {
                die('Pesanan ini tidak memiliki item pesawat.');
            }
            
            $details = [];
            $detailsByFlight = [];
            
            try {
                $details = db()->fetchAll(
                    "SELECT * FROM flight_details WHERE order_id = ? ORDER BY order_item_id, id", 
                    [$id]
                );
                
                foreach ($details as $detail) {
                    $key = $detail['order_item_id'] ?: 0;
                    if (!isset($detailsByFlight[$key])) {
                        $detailsByFlight[$key] = [];
                    }
                    $detailsByFlight[$key][] = $detail;
                }
            } catch (Exception $e) {
                $details = [];
                $detailsByFlight = [];
            }
            
            if (empty($details)) {
                die('Belum ada data penumpang. Silakan input data terlebih dahulu.');
            }
            
            $companyId = Session::companyId();
            $company = db()->fetchOne("SELECT * FROM companies WHERE id = ?", [$companyId]);
            $client = $order->getClient();
            
            $data = [
                'order' => $order,
                'company' => $company ?: [],
                'client' => $client ?: [],
                'flightItems' => $flightItems,
                'details' => $details,
                'detailsByFlight' => $detailsByFlight
            ];
            
            extract($data);
            require VIEWS_PATH . '/documents/templates/flight-attachment.php';
            exit;
            
        } catch (Exception $e) {
            die('Error: ' . $e->getMessage());
        }
    }
    
    /**
     * Upload flight logo (per flight item)
     */
    public function uploadFlightLogo(int $id): void {
        header('Content-Type: application/json');
        
        try {
            $order = $this->findOrder($id);
            
            if (!$order) {
                echo json_encode(['success' => false, 'message' => 'Pesanan tidak ditemukan.']);
                return;
            }
            
            $flightItemId = $_POST['flight_item_id'] ?? null;
            if (!$flightItemId) {
                echo json_encode(['success' => false, 'message' => 'Flight item ID tidak ditemukan.']);
                return;
            }
            
            $flightItem = db()->fetchOne(
                "SELECT * FROM order_items WHERE id = ? AND order_id = ? AND item_type = 'flight'",
                [$flightItemId, $id]
            );
            
            if (!$flightItem) {
                echo json_encode(['success' => false, 'message' => 'Flight item tidak valid.']);
                return;
            }
            
            if (empty($_FILES['logo']) || $_FILES['logo']['error'] !== UPLOAD_ERR_OK) {
                echo json_encode(['success' => false, 'message' => 'Tidak ada file yang diupload.']);
                return;
            }
            
            $file = $_FILES['logo'];
            $allowedTypes = ['image/png', 'image/jpeg', 'image/jpg', 'image/gif'];
            
            if (!in_array($file['type'], $allowedTypes)) {
                echo json_encode(['success' => false, 'message' => 'Format file tidak didukung.']);
                return;
            }
            
            if ($file['size'] > 2 * 1024 * 1024) {
                echo json_encode(['success' => false, 'message' => 'Ukuran file maksimal 2MB.']);
                return;
            }
            
            $uploadDir = BASE_PATH . '/uploads/logos';
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0755, true);
            }
            
            $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
            $filename = 'flight_' . $flightItemId . '_' . time() . '.' . $ext;
            $destination = $uploadDir . '/' . $filename;
            
            $oldLogo = $flightItem['attachment_logo'] ?? '';
            if ($oldLogo && file_exists($uploadDir . '/' . $oldLogo)) {
                @unlink($uploadDir . '/' . $oldLogo);
            }
            
            if (!move_uploaded_file($file['tmp_name'], $destination)) {
                echo json_encode(['success' => false, 'message' => 'Gagal menyimpan file.']);
                return;
            }
            
            db()->query("UPDATE order_items SET attachment_logo = ?, updated_at = NOW() WHERE id = ?", [$filename, $flightItemId]);
            
            echo json_encode([
                'success' => true,
                'data' => [
                    'filename' => $filename,
                    'url' => url('/uploads/logos/' . $filename)
                ]
            ]);
            
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
        }
    }
    
    /**
     * Delete flight logo (per flight item)
     */
    public function deleteFlightLogo(int $id): void {
        header('Content-Type: application/json');
        
        try {
            $order = $this->findOrder($id);
            
            if (!$order) {
                echo json_encode(['success' => false, 'message' => 'Pesanan tidak ditemukan.']);
                return;
            }
            
            $data = json_decode(file_get_contents('php://input'), true);
            $flightItemId = $data['flight_item_id'] ?? null;
            
            if (!$flightItemId) {
                echo json_encode(['success' => false, 'message' => 'Flight item ID tidak ditemukan.']);
                return;
            }
            
            $flightItem = db()->fetchOne(
                "SELECT * FROM order_items WHERE id = ? AND order_id = ? AND item_type = 'flight'",
                [$flightItemId, $id]
            );
            
            if (!$flightItem) {
                echo json_encode(['success' => false, 'message' => 'Flight item tidak valid.']);
                return;
            }
            
            $currentLogo = $flightItem['attachment_logo'] ?? '';
            if ($currentLogo) {
                $logoPath = BASE_PATH . '/uploads/logos/' . $currentLogo;
                if (file_exists($logoPath)) {
                    @unlink($logoPath);
                }
                
                db()->query("UPDATE order_items SET attachment_logo = NULL, updated_at = NOW() WHERE id = ?", [$flightItemId]);
            }
            
            echo json_encode(['success' => true, 'deleted' => true]);
            
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
        }
    }

    // ========================================
    // RENTAL/VEHICLE ATTACHMENT MANAGEMENT
    // ========================================
    
    /**
     * Show rental vehicles form
     */
    public function rentalVehicles(int $id): void {
        $order = $this->findOrder($id);
        
        if (!$order) {
            Session::flash('error', 'Pesanan tidak ditemukan.');
            redirect('/orders');
            return;
        }
        
        $rentalItems = db()->fetchAll(
            "SELECT * FROM order_items WHERE order_id = ? AND item_type = 'rental' ORDER BY id", 
            [$id]
        );
        
        if (empty($rentalItems)) {
            Session::flash('error', 'Pesanan ini tidak memiliki item rental kendaraan.');
            redirect('/orders/' . $id);
            return;
        }
        
        $details = [];
        $detailsByRental = [];
        
        try {
            $details = db()->fetchAll(
                "SELECT * FROM rental_details WHERE order_id = ? ORDER BY order_item_id, id", 
                [$id]
            );
            
            foreach ($details as $detail) {
                $key = $detail['order_item_id'] ?: 0;
                if (!isset($detailsByRental[$key])) {
                    $detailsByRental[$key] = [];
                }
                $detailsByRental[$key][] = $detail;
            }
        } catch (Exception $e) {
            $details = [];
            $detailsByRental = [];
        }
        
        $client = $order->getClient();
        
        render('orders/rental-vehicles', [
            'order' => $order,
            'rentalItems' => $rentalItems,
            'details' => $details,
            'detailsByRental' => $detailsByRental,
            'existingDetails' => $details,
            'client' => $client
        ]);
    }
    
    /**
     * Save rental vehicle details
     */
    public function saveRentalVehicles(int $id): void {
        $order = $this->findOrder($id);
        
        if (!$order) {
            Session::flash('error', 'Pesanan tidak ditemukan.');
            redirect('/orders');
            return;
        }
        
        try {
            $vehicles = $_POST['vehicles'] ?? [];
            $savedCount = 0;
            
            foreach ($vehicles as $rentalId => $vehicleList) {
                $rentalId = (int)$rentalId;
                
                // ============================================
                // FIXED: Delete hanya untuk rental ini, bukan semua
                // ============================================
                try { db()->delete('rental_details', 'order_id = ? AND order_item_id = ?', [$id, $rentalId]); } catch (Exception $e) {}
                
                foreach ($vehicleList as $vehicle) {
                    $vehicleType = trim($vehicle['vehicle_type'] ?? '');
                    if (empty($vehicleType)) continue;
                    
                    $priceRaw = $vehicle['price_per_day'] ?? '0';
                    $pricePerDay = floatval(str_replace('.', '', $priceRaw));
                    
                    $startDate = !empty($vehicle['start_date']) ? $vehicle['start_date'] : null;
                    $endDate = !empty($vehicle['end_date']) ? $vehicle['end_date'] : null;
                    
                    $dailyPrices = null;
                    if (empty($vehicle['same_price']) && !empty($vehicle['daily_prices'])) {
                        $cleaned = [];
                        foreach ($vehicle['daily_prices'] as $d => $p) {
                            $cleaned[$d] = floatval(str_replace('.', '', $p));
                        }
                        $dailyPrices = json_encode($cleaned);
                    }
                    
                    db()->insert('rental_details', [
                        'order_id' => $id,
                        'order_item_id' => $rentalId,
                        'vehicle_type' => $vehicleType,
                        'start_date' => $startDate,
                        'end_date' => $endDate,
                        'same_price' => isset($vehicle['same_price']) ? 1 : 0,
                        'price_per_day' => $pricePerDay,
                        'daily_prices' => $dailyPrices
                    ]);
                    $savedCount++;
                }
            }
            
            Session::flash('success', "Berhasil menyimpan {$savedCount} data kendaraan.");
            
        } catch (Exception $e) {
            Session::flash('error', 'Gagal menyimpan: ' . $e->getMessage());
        }
        
        redirect('/orders/' . $id . '/rental-vehicles');
    }
    
    /**
     * Print rental attachment (Landscape A4)
     */
    public function rentalAttachment(int $id): void {
        try {
            $order = $this->findOrder($id);
            
            if (!$order) {
                die('Pesanan tidak ditemukan.');
            }
            
            $rentalItems = db()->fetchAll(
                "SELECT * FROM order_items WHERE order_id = ? AND item_type = 'rental' ORDER BY id", 
                [$id]
            );
            
            if (empty($rentalItems)) {
                die('Pesanan ini tidak memiliki item rental kendaraan.');
            }
            
            $details = [];
            $detailsByRental = [];
            
            try {
                $details = db()->fetchAll(
                    "SELECT * FROM rental_details WHERE order_id = ? ORDER BY order_item_id, id", 
                    [$id]
                );
                
                foreach ($details as $detail) {
                    $key = $detail['order_item_id'] ?: 0;
                    if (!isset($detailsByRental[$key])) {
                        $detailsByRental[$key] = [];
                    }
                    $detailsByRental[$key][] = $detail;
                }
            } catch (Exception $e) {
                $details = [];
                $detailsByRental = [];
            }
            
            if (empty($details)) {
                die('Belum ada data kendaraan. Silakan input data terlebih dahulu.');
            }
            
            $companyId = Session::companyId();
            $company = db()->fetchOne("SELECT * FROM companies WHERE id = ?", [$companyId]);
            $client = $order->getClient();
            
            $data = [
                'order' => $order,
                'company' => $company ?: [],
                'client' => $client ?: [],
                'rentalItems' => $rentalItems,
                'details' => $details,
                'detailsByRental' => $detailsByRental
            ];
            
            extract($data);
            require VIEWS_PATH . '/documents/templates/rental-attachment.php';
            exit;
            
        } catch (Exception $e) {
            die('Error: ' . $e->getMessage());
        }
    }
    
    /**
     * Upload rental logo
     */
    public function uploadRentalLogo(int $id): void {
        header('Content-Type: application/json');
        
        try {
            $order = $this->findOrder($id);
            
            if (!$order) {
                echo json_encode(['success' => false, 'message' => 'Pesanan tidak ditemukan.']);
                return;
            }
            
            $rentalItemId = $_POST['rental_item_id'] ?? null;
            if (!$rentalItemId) {
                echo json_encode(['success' => false, 'message' => 'Rental item ID tidak ditemukan.']);
                return;
            }
            
            $rentalItem = db()->fetchOne(
                "SELECT * FROM order_items WHERE id = ? AND order_id = ? AND item_type = 'rental'",
                [$rentalItemId, $id]
            );
            
            if (!$rentalItem) {
                echo json_encode(['success' => false, 'message' => 'Rental item tidak valid.']);
                return;
            }
            
            if (empty($_FILES['logo']) || $_FILES['logo']['error'] !== UPLOAD_ERR_OK) {
                echo json_encode(['success' => false, 'message' => 'Tidak ada file yang diupload.']);
                return;
            }
            
            $file = $_FILES['logo'];
            $allowedTypes = ['image/png', 'image/jpeg', 'image/jpg', 'image/gif'];
            
            if (!in_array($file['type'], $allowedTypes)) {
                echo json_encode(['success' => false, 'message' => 'Format file tidak didukung.']);
                return;
            }
            
            if ($file['size'] > 2 * 1024 * 1024) {
                echo json_encode(['success' => false, 'message' => 'Ukuran file maksimal 2MB.']);
                return;
            }
            
            $uploadDir = BASE_PATH . '/uploads/logos';
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0755, true);
            }
            
            $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
            $filename = 'rental_' . $rentalItemId . '_' . time() . '.' . $ext;
            $destination = $uploadDir . '/' . $filename;
            
            $oldLogo = $rentalItem['attachment_logo'] ?? '';
            if ($oldLogo && file_exists($uploadDir . '/' . $oldLogo)) {
                @unlink($uploadDir . '/' . $oldLogo);
            }
            
            if (!move_uploaded_file($file['tmp_name'], $destination)) {
                echo json_encode(['success' => false, 'message' => 'Gagal menyimpan file.']);
                return;
            }
            
            db()->query("UPDATE order_items SET attachment_logo = ?, updated_at = NOW() WHERE id = ?", [$filename, $rentalItemId]);
            
            echo json_encode([
                'success' => true,
                'data' => [
                    'filename' => $filename,
                    'url' => url('/uploads/logos/' . $filename)
                ]
            ]);
            
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
        }
    }
    
    /**
     * Delete rental logo
     */
    public function deleteRentalLogo(int $id): void {
        header('Content-Type: application/json');
        
        try {
            $order = $this->findOrder($id);
            
            if (!$order) {
                echo json_encode(['success' => false, 'message' => 'Pesanan tidak ditemukan.']);
                return;
            }
            
            $data = json_decode(file_get_contents('php://input'), true);
            $rentalItemId = $data['rental_item_id'] ?? null;
            
            if (!$rentalItemId) {
                echo json_encode(['success' => false, 'message' => 'Rental item ID tidak ditemukan.']);
                return;
            }
            
            $rentalItem = db()->fetchOne(
                "SELECT * FROM order_items WHERE id = ? AND order_id = ? AND item_type = 'rental'",
                [$rentalItemId, $id]
            );
            
            if (!$rentalItem) {
                echo json_encode(['success' => false, 'message' => 'Rental item tidak valid.']);
                return;
            }
            
            $currentLogo = $rentalItem['attachment_logo'] ?? '';
            if ($currentLogo) {
                $logoPath = BASE_PATH . '/uploads/logos/' . $currentLogo;
                if (file_exists($logoPath)) {
                    @unlink($logoPath);
                }
                
                db()->query("UPDATE order_items SET attachment_logo = NULL, updated_at = NOW() WHERE id = ?", [$rentalItemId]);
            }
            
            echo json_encode(['success' => true, 'deleted' => true]);
            
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
        }
    }
    
    // ========================================
    // ORDER ITEMS API
    // ========================================
    
    /**
     * API: Get order items
     */
    public function getItems(int $id): void {
        $order = $this->findOrder($id);
        
        if (!$order) {
            jsonError('Pesanan tidak ditemukan.', 404);
            return;
        }
        
        jsonSuccess($order->getItems());
    }
    
    /**
     * API: Add order item
     */
    public function addItem(int $id): void {
        $order = $this->findOrder($id);
        
        if (!$order) {
            jsonError('Pesanan tidak ditemukan.', 404);
            return;
        }
        
        $data = json_decode(file_get_contents('php://input'), true);
        
        $itemData = [
            'item_type' => $data['item_type'] ?? 'bus',
            'description' => $data['description'] ?? '',
            'quantity' => (int) ($data['quantity'] ?? 1),
            'num_days' => (int) ($data['num_days'] ?? 1),  // FIXED: Added num_days
            'base_price' => (float) ($data['base_price'] ?? 0),
            'markup_type' => $data['markup_type'] ?? 'percentage',
            'markup_value' => (float) ($data['markup_value'] ?? 0),
            'final_price' => (float) ($data['final_price'] ?? 0)
        ];
        
        $itemId = $order->addItem($itemData);
        $order->recalculateTotals();
        $order->calculateProfitShares();
        
        jsonSuccess(['id' => $itemId]);
    }
    
    /**
     * API: Update order item
     */
    public function updateItem(int $itemId): void {
        $item = db()->fetchOne("SELECT order_id FROM order_items WHERE id = ?", [$itemId]);
        if (!$item) {
            jsonError('Item tidak ditemukan.', 404);
            return;
        }
        
        $order = $this->findOrder($item['order_id']);
        if (!$order) {
            jsonError('Pesanan tidak ditemukan.', 404);
            return;
        }
        
        $data = json_decode(file_get_contents('php://input'), true);
        
        $order->updateItem($itemId, $data);
        $order->recalculateTotals();
        $order->calculateProfitShares();
        
        jsonSuccess(['success' => true]);
    }
    
    /**
     * API: Delete order item
     */
    public function deleteItem(int $itemId): void {
        $item = db()->fetchOne("SELECT order_id FROM order_items WHERE id = ?", [$itemId]);
        if (!$item) {
            jsonError('Item tidak ditemukan.', 404);
            return;
        }
        
        $order = $this->findOrder($item['order_id']);
        if (!$order) {
            jsonError('Pesanan tidak ditemukan.', 404);
            return;
        }
        
        $order->deleteItem($itemId);
        $order->recalculateTotals();
        $order->calculateProfitShares();
        
        jsonSuccess(['success' => true]);
    }
    
    // ========================================
    // VEHICLE DOCUMENTS (Data Kendaraan)
    // ========================================
    
    /**
     * Show vehicle documents form
     */
    public function vehicleDocuments(int $id): void {
        $order = $this->findOrder($id);
        
        if (!$order) {
            Session::flash('error', 'Pesanan tidak ditemukan.');
            redirect('/orders');
            return;
        }
        
        $vehicleItems = db()->fetchAll(
            "SELECT * FROM order_items WHERE order_id = ? AND item_type IN ('rental', 'bus', 'towing') ORDER BY id", 
            [$id]
        );
        
        if (empty($vehicleItems)) {
            Session::flash('error', 'Pesanan ini tidak memiliki item kendaraan (rental/bus/towing).');
            redirect('/orders/' . $id);
            return;
        }
        
        $documents = [];
        $documentsByItem = [];
        
        try {
            $documents = db()->fetchAll(
                "SELECT * FROM vehicle_documents WHERE order_id = ? ORDER BY order_item_id, id", 
                [$id]
            );
            
            foreach ($documents as $doc) {
                $key = $doc['order_item_id'] ?: 0;
                if (!isset($documentsByItem[$key])) {
                    $documentsByItem[$key] = [];
                }
                $documentsByItem[$key][] = $doc;
            }
        } catch (Exception $e) {
            $documents = [];
            $documentsByItem = [];
        }
        
        $client = $order->getClient();
        
        render('orders/vehicle-documents', [
            'order' => $order,
            'vehicleItems' => $vehicleItems,
            'documents' => $documents,
            'documentsByItem' => $documentsByItem,
            'client' => $client
        ]);
    }
    
    /**
     * Save vehicle documents
     */
    public function saveVehicleDocuments(int $id): void {
        $order = $this->findOrder($id);
        
        if (!$order) {
            Session::flash('error', 'Pesanan tidak ditemukan.');
            redirect('/orders');
            return;
        }
        
        try {
            $vehicles = $_POST['vehicles'] ?? [];
            $savedCount = 0;
            
            foreach ($vehicles as $itemId => $vehicleList) {
                $itemId = (int)$itemId;
                
                // ============================================
                // FIXED: Delete hanya untuk item ini, bukan semua
                // ============================================
                try { db()->delete('vehicle_documents', 'order_id = ? AND order_item_id = ?', [$id, $itemId]); } catch (Exception $e) {}
                
                foreach ($vehicleList as $vehicle) {
                    $vehicleType = trim($vehicle['vehicle_type'] ?? '');
                    if (empty($vehicleType)) continue;
                    
                    db()->insert('vehicle_documents', [
                        'order_id' => $id,
                        'order_item_id' => $itemId,
                        'vehicle_type' => $vehicleType,
                        'plate_number' => trim($vehicle['plate_number'] ?? '') ?: null,
                        'driver_name' => trim($vehicle['driver_name'] ?? '') ?: null,
                        'photo_driver' => $vehicle['photo_driver'] ?? null,
                        'photo_sim' => $vehicle['photo_sim'] ?? null,
                        'photo_stnk' => $vehicle['photo_stnk'] ?? null
                    ]);
                    $savedCount++;
                }
            }
            
            Session::flash('success', "Berhasil menyimpan {$savedCount} data kendaraan.");
            
        } catch (Exception $e) {
            Session::flash('error', 'Gagal menyimpan: ' . $e->getMessage());
        }
        
        redirect('/orders/' . $id . '/vehicle-documents');
    }
    
    /**
     * Upload vehicle photo (driver, sim, stnk)
     */
    public function uploadVehiclePhoto(int $id): void {
        header('Content-Type: application/json');
        
        try {
            $order = $this->findOrder($id);
            if (!$order) {
                echo json_encode(['success' => false, 'message' => 'Pesanan tidak ditemukan.']);
                return;
            }
            
            if (empty($_FILES['photo']) || $_FILES['photo']['error'] !== UPLOAD_ERR_OK) {
                $errorMsg = 'Tidak ada file yang diupload.';
                if (!empty($_FILES['photo']['error'])) {
                    $errors = [
                        1 => 'File terlalu besar (melebihi upload_max_filesize)',
                        2 => 'File terlalu besar (melebihi MAX_FILE_SIZE)',
                        3 => 'File hanya terupload sebagian',
                        4 => 'Tidak ada file yang diupload',
                        6 => 'Folder temporary tidak ada',
                        7 => 'Gagal menulis file ke disk',
                        8 => 'Upload dihentikan oleh extension'
                    ];
                    $errorMsg = $errors[$_FILES['photo']['error']] ?? 'Error upload tidak diketahui';
                }
                echo json_encode(['success' => false, 'message' => $errorMsg]);
                return;
            }
            
            $file = $_FILES['photo'];
            $photoType = $_POST['photo_type'] ?? 'driver';
            $vehicleIndex = $_POST['vehicle_index'] ?? '0';
            $itemId = $_POST['item_id'] ?? '0';
            
            $allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp'];
            if (!in_array($file['type'], $allowedTypes)) {
                echo json_encode(['success' => false, 'message' => 'Format file tidak didukung.']);
                return;
            }
            
            if ($file['size'] > 5 * 1024 * 1024) {
                echo json_encode(['success' => false, 'message' => 'Ukuran file maksimal 5MB.']);
                return;
            }
            
            $uploadDir = BASE_PATH . '/uploads/vehicles';
            if (!is_dir($uploadDir)) {
                if (!mkdir($uploadDir, 0755, true)) {
                    echo json_encode(['success' => false, 'message' => 'Gagal membuat folder upload.']);
                    return;
                }
            }
            
            $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION)) ?: 'jpg';
            $filename = "vehicle_{$id}_{$itemId}_{$vehicleIndex}_{$photoType}_" . time() . ".{$ext}";
            $filepath = $uploadDir . '/' . $filename;
            
            if (move_uploaded_file($file['tmp_name'], $filepath)) {
                echo json_encode([
                    'success' => true,
                    'data' => [
                        'filename' => $filename,
                        'url' => '/uploads/vehicles/' . $filename,
                        'photo_type' => $photoType
                    ]
                ]);
            } else {
                echo json_encode(['success' => false, 'message' => 'Gagal menyimpan file.']);
            }
            
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
        }
    }
    
    /**
     * Delete vehicle photo
     */
    public function deleteVehiclePhoto(int $id): void {
        header('Content-Type: application/json');
        
        try {
            $data = json_decode(file_get_contents('php://input'), true);
            $filename = $data['filename'] ?? '';
            
            if (empty($filename)) {
                echo json_encode(['success' => false, 'message' => 'Filename tidak ditemukan.']);
                return;
            }
            
            $filepath = BASE_PATH . '/uploads/vehicles/' . basename($filename);
            
            if (file_exists($filepath)) {
                unlink($filepath);
            }
            
            echo json_encode(['success' => true]);
            
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
    }
    
    /**
     * Print vehicle documents (Portrait A4)
     */
    public function vehicleDocumentsPrint(int $id): void {
        try {
            $order = $this->findOrder($id);
            
            if (!$order) {
                die('Pesanan tidak ditemukan.');
            }
            
            $vehicleItems = db()->fetchAll(
                "SELECT * FROM order_items WHERE order_id = ? AND item_type IN ('rental', 'bus', 'towing') ORDER BY id", 
                [$id]
            );
            
            if (empty($vehicleItems)) {
                die('Pesanan ini tidak memiliki item kendaraan.');
            }
            
            $documents = db()->fetchAll(
                "SELECT * FROM vehicle_documents WHERE order_id = ? ORDER BY order_item_id, id", 
                [$id]
            );
            
            if (empty($documents)) {
                die('Belum ada data kendaraan yang diinput.');
            }
            
            $client = $order->getClient();
            $company = db()->fetchOne("SELECT * FROM companies WHERE id = ?", [Session::companyId()]);
            
            require BASE_PATH . '/views/documents/templates/vehicle-documents-print.php';
            
        } catch (Exception $e) {
            die('Error: ' . $e->getMessage());
        }
    }
    
    // ========================================
    // PRIVATE HELPER METHODS
    // ========================================
    
    /**
     * Find order with company check
     */
    private function findOrder(int $id): ?Order {
        $order = Order::find($id);
        
        if (!$order || $order->company_id != Session::companyId()) {
            return null;
        }
        
        return $order;
    }
    
    /**
     * Parse items from POST
     * ================================================================
     * FIXED: Sekarang menyimpan num_days dengan benar
     * Kalkulasi: qty × days × base_price
     * ================================================================
     */
    private function parseItems(array $post): array {
        $items = [];
        
        if (empty($post['items']) || !is_array($post['items'])) {
            return $items;
        }
        
        foreach ($post['items'] as $item) {
            if (empty($item['description'])) {
                continue;
            }
            
            $basePrice = parseRupiah($item['base_price'] ?? '0');
            $cashback = parseRupiah($item['cashback'] ?? '0');
            $markupType = $item['markup_type'] ?? 'percentage';
            $markupValue = (float) ($item['markup_value'] ?? 0);

            // FIXED: Ambil num_days dari form
            $numDays = max(1, (int) ($item['num_days'] ?? 1));
            $quantity = max(1, (int) ($item['quantity'] ?? 1));

            // Calculate final price dengan num_days: qty × days × price
            // Markup dihitung dari (harga beli + cashback), bukan harga beli saja
            $itemBase = $quantity * $numDays * $basePrice;
            $itemCashback = $quantity * $numDays * $cashback;
            $markupBase = $itemBase + $itemCashback;
            if ($markupType === 'percentage') {
                $markupAmount = $markupBase * ($markupValue / 100);
            } else {
                $markupAmount = $markupValue * $quantity * $numDays;
            }
            $finalPrice = $markupBase + $markupAmount;

            $items[] = [
                'item_type' => $item['item_type'] ?? 'bus',
                'description' => trim($item['description']),
                'quantity' => $quantity,
                'num_days' => $numDays,  // FIXED: Sekarang tersimpan!
                'base_price' => $basePrice,
                'cashback' => $cashback,
                'markup_type' => $markupType,
                'markup_value' => $markupValue,
                'markup_amount' => $markupAmount,  // FIXED: Dihitung dengan benar
                'final_price' => $finalPrice,       // FIXED: Dihitung dengan benar
                'vehicle_plate' => trim($item['vehicle_plate'] ?? ''),
                'participant_qty' => (isset($item['participant_qty']) && $item['participant_qty'] !== '')
                    ? max(0, (int) $item['participant_qty']) : null,
                'participant_names' => trim($item['participant_names'] ?? '') ?: null
            ];
        }

        return $items;
    }

    /**
     * A client needs the Divisi field when explicitly flagged (uses_divisi=1)
     * OR when its name contains "PELNI" — a safety net so the known PELNI
     * client always gets the field even if someone forgets to tick the
     * checkbox on the client record.
     */
    private function clientNeedsDivisi(array $client): bool {
        return !empty($client['uses_divisi']) || stripos($client['name'] ?? '', 'PELNI') !== false;
    }

    /**
     * Resolve the PELNI "Divisi" from POST data — only returns a value when
     * the selected client needs it and the submitted value is one of the
     * known options; otherwise null (non-PELNI clients are unaffected).
     */
    private function resolveDivisi(array $post): ?string {
        $divisi = $post['divisi'] ?? '';
        if (!isset(DIVISI_OPTIONS[$divisi])) {
            return null;
        }

        $clientId = (int) ($post['client_id'] ?? 0);
        if (!$clientId) {
            return null;
        }

        $client = Client::find($clientId);
        if (!$client || !$this->clientNeedsDivisi($client->toArray())) {
            return null;
        }

        return $divisi;
    }

    /**
     * Map of client_id => needs-divisi (bool), for the order form's JS to
     * decide when to reveal the Divisi dropdown.
     */
    private function getClientsUsesDivisi(int $companyId): array {
        $rows = db()->fetchAll("SELECT id, name, uses_divisi FROM clients WHERE company_id = ?", [$companyId]);
        $map = [];
        foreach ($rows as $row) {
            $map[(int) $row['id']] = $this->clientNeedsDivisi($row);
        }
        return $map;
    }

    /**
     * Validate order data
     */
    private function validateOrder(array $data): array {
        $errors = [];

        if (empty($data['order_date'])) {
            $errors[] = 'Tanggal pesanan wajib diisi.';
        }

        // Bagian Informasi Pesanan wajib diisi semua, kecuali Tgl Selesai Event.
        if (empty($data['event_date'])) {
            $errors[] = 'Tgl Mulai Kegiatan wajib diisi.';
        }
        if (empty($data['client_id'])) {
            $errors[] = 'Klien wajib dipilih.';
        }
        if (trim($data['event_name'] ?? '') === '') {
            $errors[] = 'Nama Kegiatan wajib diisi.';
        }
        if (trim($data['pic_name'] ?? '') === '') {
            $errors[] = 'PIC Name wajib diisi.';
        }
        if (trim($data['pic_phone'] ?? '') === '') {
            $errors[] = 'Phone Number wajib diisi.';
        }
        if (trim($data['description'] ?? '') === '') {
            $errors[] = 'Deskripsi wajib diisi.';
        }

        // Divisi hanya wajib untuk klien PELNI-style.
        if (!empty($data['client_id'])) {
            $client = Client::find((int) $data['client_id']);
            if ($client && $this->clientNeedsDivisi($client->toArray()) && empty($data['divisi'])) {
                $errors[] = 'Divisi wajib dipilih untuk klien ini.';
            }
        }

        return $errors;
    }
    
    /**
     * Get page actions HTML
     */
    private function getPageActions(): string {
        if (!Session::can('orders.create')) {
            return '';
        }
        
        return '<a href="' . url('/orders/create') . '" class="btn btn-primary">
            <i class="fas fa-plus"></i> Buat Pesanan
        </a>';
    }
}
// ==========================================
// END OF OrderController
// ==========================================