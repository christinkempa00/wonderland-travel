<?php
/**
 * ================================================================
 * TRAVEL MANAGEMENT SYSTEM - Document Controller
 * ================================================================
 */

require_once MODELS_PATH . '/Document.php';
require_once MODELS_PATH . '/Order.php';

class DocumentController {
    
    /**
     * List documents
     */
    public function index(): void {
        $companyId = Session::companyId();
        $page = (int) ($_GET['page'] ?? 1);
        
        $filters = [
            'search' => $_GET['search'] ?? '',
            'doc_type' => $_GET['doc_type'] ?? '',
            'start_date' => $_GET['start_date'] ?? '',
            'end_date' => $_GET['end_date'] ?? ''
        ];
        
        $result = Document::getByCompanyPaginated($companyId, $page, PAGINATION_PER_PAGE, $filters);
        
        $data = [
            'pageTitle' => 'Dokumen',
            'pageHeader' => true,
            'pageSubtitle' => 'Daftar dokumen penawaran, invoice, dan kwitansi',
            'documents' => $result['data'],
            'pagination' => $result,
            'filters' => $filters,
            'documentTypes' => DOCUMENT_TYPES
        ];
        
        render('documents/index', $data);
    }
    
    /**
     * Generate document from order
     */
    public function generate(int $orderId, string $docType): void {
        $order = $this->findOrder($orderId);
        
        if (!$order) {
            Session::flash('error', 'Pesanan tidak ditemukan.');
            redirect('/orders');
            return;
        }
        
        if (!isset(DOCUMENT_TYPES[$docType])) {
            Session::flash('error', 'Tipe dokumen tidak valid.');
            redirect('/orders/' . $orderId);
            return;
        }
        
        // Check if can generate
        if (!$order->canGenerateDocument($docType)) {
            Session::flash('error', 'Dokumen tidak dapat dibuat untuk status pesanan ini.');
            redirect('/orders/' . $orderId);
            return;
        }
        
        try {
            $document = Document::createForOrder($orderId, $docType);
            
            // Update order status if needed
            if ($docType === 'quotation' && $order->status === 'draft') {
                $order->updateStatus('quotation');
            } elseif ($docType === 'invoice' && in_array($order->status, ['agreed', 'quotation'])) {
                $order->updateStatus('invoiced');
            }
            
            logActivity('create', 'document', $document->id, 'document', 
                "Generated {$docType}: " . $document->doc_number);
            
            // Send WhatsApp notification for invoice
            if ($docType === 'invoice') {
                $this->sendInvoiceNotification($document);
            }
            
            Session::flash('success', DOCUMENT_TYPES[$docType]['label'] . ' berhasil dibuat: ' . $document->doc_number);
            redirect('/documents/' . $document->id);
            
        } catch (Exception $e) {
            Session::flash('error', 'Gagal membuat dokumen: ' . $e->getMessage());
            redirect('/orders/' . $orderId);
        }
    }
    
    /**
     * Show document
     */
    public function show(int $id): void {
        $document = $this->findDocument($id);
        
        if (!$document) {
            Session::flash('error', 'Dokumen tidak ditemukan.');
            redirect('/documents');
            return;
        }
        
        $order = $document->getOrder();
        $items = $document->getOrderItems();
        $company = $document->getCompany();
        
        $data = [
            'pageTitle' => $document->doc_number,
            'pageHeader' => true,
            'breadcrumbs' => [
                ['label' => 'Dokumen', 'url' => '/documents'],
                ['label' => $document->doc_number]
            ],
            'document' => $document,
            'order' => $order,
            'items' => $items,
            'company' => $company
        ];
        
        render('documents/show', $data);
    }
    
    /**
     * View document as printable HTML
     */
    public function view(int $id): void {
        $document = $this->findDocument($id);
        
        if (!$document) {
            http_response_code(404);
            echo 'Document not found';
            return;
        }
        
        $order = $document->getOrder();
        $items = $document->getOrderItems();
        $company = $document->getCompany();
        
        // Render printable template
        $data = [
            'document' => $document,
            'order' => $order,
            'items' => $items,
            'company' => $company
        ];
        
        $templateFile = VIEWS_PATH . '/documents/templates/' . $document->doc_type . '.php';
        
        if (!file_exists($templateFile)) {
            $templateFile = VIEWS_PATH . '/documents/templates/default.php';
        }
        
        extract($data);
        include $templateFile;
    }
    
    /**
     * Download as PDF
     */
    public function pdf(int $id): void {
        $document = $this->findDocument($id);
        
        if (!$document) {
            http_response_code(404);
            echo 'Document not found';
            return;
        }
        
        $order = $document->getOrder();
        $items = $document->getOrderItems();
        $company = $document->getCompany();
        
        // Generate HTML
        ob_start();
        $data = [
            'document' => $document,
            'order' => $order,
            'items' => $items,
            'company' => $company,
            'isPdf' => true
        ];
        
        $templateFile = VIEWS_PATH . '/documents/templates/' . $document->doc_type . '.php';
        if (!file_exists($templateFile)) {
            $templateFile = VIEWS_PATH . '/documents/templates/default.php';
        }
        
        extract($data);
        include $templateFile;
        $html = ob_get_clean();
        
        // Simple HTML to PDF using browser print
        // For production, use library like TCPDF, DOMPDF, or wkhtmltopdf
        header('Content-Type: text/html; charset=utf-8');
        echo $html;
        echo '<script>window.print();</script>';
    }
    
    /**
     * Delete document
     */
    public function destroy(int $id): void {
        $document = $this->findDocument($id);
        
        if (!$document) {
            Session::flash('error', 'Dokumen tidak ditemukan.');
            redirect('/documents');
            return;
        }
        
        $docNumber = $document->doc_number;
        $orderId = $document->order_id;
        
        $document->delete();
        
        logActivity('delete', 'document', $id, 'document', 'Deleted document: ' . $docNumber);
        
        Session::flash('success', 'Dokumen berhasil dihapus.');
        
        if ($orderId) {
            redirect('/orders/' . $orderId);
        } else {
            redirect('/documents');
        }
    }
    
    /**
     * Send invoice notification via WhatsApp
     */
    private function sendInvoiceNotification(Document $document): void {
        $order = $document->getOrder();
        if (!$order || !$order['client_phone']) return;
        
        $company = $document->getCompany();
        
        $message = "Yth. {$order['client_name']},\n\n";
        $message .= "Berikut adalah invoice untuk pesanan Anda:\n";
        $message .= "No. Invoice: {$document->doc_number}\n";
        $message .= "Tanggal: " . formatDateIndo($document->doc_date) . "\n";
        $message .= "Jatuh Tempo: " . formatDateIndo($document->due_date) . "\n";
        $message .= "Total: " . formatRupiah($order['total_final_price']) . "\n\n";
        $message .= "Terima kasih.\n";
        $message .= $company['name'];
        
        // Send via Dripsender
        $this->sendDripsender($order['client_phone'], $message, $document->company_id);
    }
    
    /**
     * Send via Dripsender
     */
    private function sendDripsender(string $phone, string $message, int $companyId): void {
        $apiKey = getCompanySetting($companyId, 'dripsender_api_key', '');
        
        if (!$apiKey) return;
        
        $phone = formatPhoneWa($phone);
        
        $payload = [
            'api_key' => $apiKey,
            'phone' => $phone,
            'text' => $message
        ];
        
        $ch = curl_init('https://api.dripsender.id/send');
        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => json_encode($payload),
            CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 30
        ]);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        // Log
        db()->insert('dripsender_logs', [
            'company_id' => $companyId,
            'phone' => $phone,
            'message' => $message,
            'response' => $response,
            'status' => $httpCode == 200 ? 'sent' : 'failed',
            'created_at' => date('Y-m-d H:i:s')
        ]);
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
     * Find document with company check
     */
    private function findDocument(int $id): ?Document {
        $document = Document::find($id);
        if (!$document || $document->company_id != Session::companyId()) {
            return null;
        }
        return $document;
    }
}
