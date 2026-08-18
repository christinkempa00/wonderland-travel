<?php
/**
 * ================================================================
 * WONDERLAND TRAVEL - Invoice Controller
 * Invoice resmi dengan item & pembayaran sendiri, dibuat dari order
 * yang sudah ada tapi independen setelahnya.
 * ================================================================
 */

require_once MODELS_PATH . '/Invoice.php';
require_once MODELS_PATH . '/Order.php';
require_once MODELS_PATH . '/Client.php';

class InvoiceController {

    /**
     * List invoices
     */
    public function index(): void {
        $companyId = Session::companyId();
        $page = (int) ($_GET['page'] ?? 1);
        $perPage = PAGINATION_PER_PAGE;

        $search = trim($_GET['search'] ?? '');
        $paymentStatus = $_GET['payment_status'] ?? '';

        $where = "i.company_id = ?";
        $params = [$companyId];

        if (!empty($paymentStatus)) {
            $where .= " AND i.payment_status = ?";
            $params[] = $paymentStatus;
        }

        if (!empty($search)) {
            $where .= " AND (i.invoice_number LIKE ? OR o.order_number LIKE ? OR c.name LIKE ?)";
            $params[] = "%{$search}%";
            $params[] = "%{$search}%";
            $params[] = "%{$search}%";
        }

        $countSql = "SELECT COUNT(*) FROM invoices i
                     JOIN orders o ON i.order_id = o.id
                     LEFT JOIN clients c ON o.client_id = c.id
                     WHERE {$where}";
        $total = (int) db()->fetchColumn($countSql, $params);

        $offset = ($page - 1) * $perPage;

        $sql = "SELECT i.*, o.order_number, c.name as client_name
                FROM invoices i
                JOIN orders o ON i.order_id = o.id
                LEFT JOIN clients c ON o.client_id = c.id
                WHERE {$where}
                ORDER BY i.created_at DESC
                LIMIT {$perPage} OFFSET {$offset}";
        $invoices = db()->fetchAll($sql, $params);

        $pagination = [
            'data' => $invoices,
            'total' => $total,
            'per_page' => $perPage,
            'current_page' => $page,
            'last_page' => ceil($total / $perPage) ?: 1,
            'from' => $total > 0 ? $offset + 1 : 0,
            'to' => min($offset + $perPage, $total)
        ];

        $data = [
            'pageTitle' => 'Invoice',
            'pageHeader' => true,
            'pageSubtitle' => 'Kelola invoice dan pembayaran',
            'invoices' => $invoices,
            'pagination' => $pagination,
            'search' => $search,
            'paymentStatus' => $paymentStatus
        ];

        render('invoices/index', $data);
    }

    /**
     * Create invoice form - pre-filled from an order's items
     * GET /invoices/create?order_id=
     */
    public function create(): void {
        $orderId = (int) ($_GET['order_id'] ?? 0);
        $order = $this->findOrder($orderId);

        if (!$order) {
            Session::flash('error', 'Order tidak ditemukan.');
            redirect('/orders');
            return;
        }

        $companyId = Session::companyId();
        $client = Client::find($order->client_id);
        $orderItems = $order->getItems();

        // Pre-fill invoice items from order items using the same
        // unit-price-with-markup formula as doc.php's invoice print view
        $prefillItems = [];
        foreach ($orderItems as $item) {
            $basePrice = (float)($item['base_price'] ?? 0);
            $numDays = (int)($item['num_days'] ?? 1);
            $markupType = $item['markup_type'] ?? 'percentage';
            $markupValue = (float)($item['markup_value'] ?? 0);

            $unitPriceWithMarkup = $markupType === 'percentage'
                ? $basePrice + ($basePrice * ($markupValue / 100))
                : $basePrice + $markupValue;

            $prefillItems[] = [
                'name' => $item['description'] ?: ($item['item_name'] ?? ''),
                'quantity' => (int)($item['quantity'] ?? 1),
                'unit_price' => $numDays * $unitPriceWithMarkup
            ];
        }

        $dueDays = (int) getCompanySetting($companyId, 'invoice_due_days', 14);

        $data = [
            'pageTitle' => 'Buat Invoice',
            'pageHeader' => true,
            'breadcrumbs' => [
                ['label' => 'Invoice', 'url' => '/invoices'],
                ['label' => 'Buat Baru']
            ],
            'order' => $order,
            'client' => $client,
            'prefillItems' => $prefillItems,
            'dueDate' => date('Y-m-d', strtotime("+{$dueDays} days"))
        ];

        render('invoices/form', $data);
    }

    /**
     * Store new invoice
     * POST /invoices
     */
    public function store(): void {
        $orderId = (int) ($_POST['order_id'] ?? 0);
        $order = $this->findOrder($orderId);

        if (!$order) {
            Session::flash('error', 'Order tidak ditemukan.');
            redirect('/orders');
            return;
        }

        $items = $this->parseItems($_POST);
        if (empty($items)) {
            Session::flash('error', 'Invoice harus memiliki minimal 1 item.');
            redirect('/invoices/create?order_id=' . $orderId);
            return;
        }

        $invoice = Invoice::createWithItems(Session::companyId(), $orderId, [
            'invoice_date' => $_POST['invoice_date'] ?: date('Y-m-d'),
            'due_date' => $_POST['due_date'] ?: null,
            'discount' => parseRupiah($_POST['discount'] ?? '0'),
            'notes' => trim($_POST['notes'] ?? ''),
            'created_by' => Session::userId()
        ], $items);

        logActivity('create', 'invoice', $invoice->id, 'order', 'Created invoice: ' . $invoice->invoice_number);

        if (function_exists('notifyCompanyUsers')) {
            notifyCompanyUsers(
                Session::companyId(),
                'Invoice Baru',
                "Invoice {$invoice->invoice_number} telah dibuat untuk order {$order->order_number}.",
                'info',
                '/invoices/' . $invoice->id,
                Session::userId()
            );
        }

        Session::flash('success', 'Invoice ' . $invoice->invoice_number . ' berhasil dibuat.');
        redirect('/invoices/' . $invoice->id);
    }

    /**
     * Invoice detail
     * GET /invoices/{id}
     */
    public function show(int $id): void {
        $invoice = $this->findInvoice($id);

        if (!$invoice) {
            Session::flash('error', 'Invoice tidak ditemukan.');
            redirect('/invoices');
            return;
        }

        $order = Order::find($invoice->order_id);
        $client = $order ? Client::find($order->client_id) : null;

        $totalPaid = (float) $invoice->paid_amount;
        $totalAmount = (float) $invoice->total;
        $remainingAmount = max(0, $totalAmount - $totalPaid);

        $bankCashOptions = [];
        try {
            $bankCashOptions = db()->fetchAll(
                "SELECT id, name FROM bank_cash WHERE company_id = ? AND is_active = 1 ORDER BY name",
                [Session::companyId()]
            );
        } catch (Exception $e) {}

        $data = [
            'pageTitle' => 'Invoice ' . $invoice->invoice_number,
            'pageHeader' => true,
            'breadcrumbs' => [
                ['label' => 'Invoice', 'url' => '/invoices'],
                ['label' => $invoice->invoice_number]
            ],
            'invoice' => $invoice,
            'order' => $order,
            'client' => $client,
            'items' => $invoice->getItems(),
            'payments' => $invoice->getPayments(),
            'remainingAmount' => $remainingAmount,
            'bankCashOptions' => $bankCashOptions
        ];

        render('invoices/show', $data);
    }

    /**
     * Record a payment against an invoice
     * POST /invoices/{id}/payment
     */
    public function storePayment(int $id): void {
        $invoice = $this->findInvoice($id);

        if (!$invoice) {
            Session::flash('error', 'Invoice tidak ditemukan.');
            redirect('/invoices');
            return;
        }

        $remainingAmount = max(0, (float)$invoice->total - (float)$invoice->paid_amount);
        $amount = parseRupiah($_POST['amount'] ?? '0');

        if ($amount <= 0) {
            Session::flash('error', 'Jumlah pembayaran harus lebih dari 0.');
            redirect('/invoices/' . $id);
            return;
        }

        if ($amount > $remainingAmount) {
            Session::flash('error', 'Jumlah pembayaran melebihi sisa tagihan (' . formatRupiah($remainingAmount) . ').');
            redirect('/invoices/' . $id);
            return;
        }

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
                redirect('/invoices/' . $id);
                return;
            }
        }

        $bankCashId = !empty($_POST['bank_cash_id']) ? (int)$_POST['bank_cash_id'] : null;

        $result = recordPayment(
            Session::companyId(),
            (int)$invoice->order_id,
            $id,
            $amount,
            $_POST['payment_date'] ?? date('Y-m-d'),
            $_POST['payment_method'] ?? 'transfer',
            $bankCashId,
            trim($_POST['reference'] ?? ''),
            trim($_POST['notes'] ?? ''),
            $proofImage
        );

        if ($result['success']) {
            Session::flash('success', 'Pembayaran sebesar ' . formatRupiah($amount) . ' berhasil dicatat.');

            $updated = Invoice::find($id);
            if ($updated && $updated->payment_status === 'paid') {
                Session::flash('info', '🎉 Invoice ini sudah LUNAS!');
                if (function_exists('notifyCompanyUsers')) {
                    notifyCompanyUsers(
                        Session::companyId(),
                        'Pembayaran Lunas',
                        "Invoice {$updated->invoice_number} telah lunas.",
                        'success',
                        '/invoices/' . $id,
                        Session::userId()
                    );
                }
            }
        } else {
            Session::flash('error', $result['message'] ?? 'Gagal mencatat pembayaran.');
        }

        redirect('/invoices/' . $id);
    }

    /**
     * Find invoice with company check
     */
    private function findInvoice(int $id): ?Invoice {
        $invoice = Invoice::find($id);

        if (!$invoice || $invoice->company_id != Session::companyId()) {
            return null;
        }

        return $invoice;
    }

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
     * Parse invoice items from POST (same items[i][field] convention as orders/form.php)
     */
    private function parseItems(array $post): array {
        $items = [];

        if (empty($post['invoice_items']) || !is_array($post['invoice_items'])) {
            return $items;
        }

        foreach ($post['invoice_items'] as $item) {
            if (empty($item['name'])) {
                continue;
            }

            $items[] = [
                'name' => trim($item['name']),
                'quantity' => max(1, (int)($item['quantity'] ?? 1)),
                'unit_price' => parseRupiah($item['unit_price'] ?? '0')
            ];
        }

        return $items;
    }
}
