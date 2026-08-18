<?php
/**
 * ================================================================
 * TRAVEL MANAGEMENT SYSTEM - Settings Controller
 * ================================================================
 */

require_once MODELS_PATH . '/Company.php';

class SettingsController {
    
    /**
     * Settings index
     */
    public function index(): void {
        redirect('/settings/company');
    }
    
    /**
     * Company settings
     */
    public function company(): void {
        $company = Company::find(Session::companyId());
        
        $data = [
            'pageTitle' => 'Pengaturan Perusahaan',
            'pageHeader' => true,
            'pageSubtitle' => 'Kelola informasi perusahaan',
            'company' => $company,
            'bankAccounts' => $company->getBankAccounts(),
            'activeTab' => 'company'
        ];
        
        render('settings/company', $data);
    }
    
    /**
     * Update company
     */
    public function updateCompany(): void {
        $company = Company::find(Session::companyId());
        
        $data = [
            'name' => trim($_POST['name'] ?? ''),
            'address' => trim($_POST['address'] ?? ''),
            'phone' => trim($_POST['phone'] ?? ''),
            'email' => trim($_POST['email'] ?? ''),
            'website' => trim($_POST['website'] ?? ''),
            'npwp' => trim($_POST['npwp'] ?? '')
        ];
        
        // Handle logo upload
        if (!empty($_FILES['logo']['tmp_name'])) {
            $logoPath = $this->uploadFile($_FILES['logo'], 'logos');
            if ($logoPath) {
                $data['logo'] = $logoPath;
            }
        }
        
        // Handle bank accounts
        $bankAccounts = [];
        if (!empty($_POST['bank_name']) && is_array($_POST['bank_name'])) {
            foreach ($_POST['bank_name'] as $i => $bankName) {
                if (empty($bankName)) continue;
                
                $bankAccounts[] = [
                    'bank_name' => trim($bankName),
                    'account_number' => trim($_POST['account_number'][$i] ?? ''),
                    'account_holder' => trim($_POST['account_holder'][$i] ?? '')
                ];
            }
        }
        $data['bank_accounts'] = json_encode($bankAccounts);
        
        $company->update($data);
        
        logActivity('update', 'company', $company->id, 'settings', 'Updated company settings');
        
        Session::flash('success', 'Pengaturan perusahaan berhasil disimpan.');
        redirect('/settings/company');
    }
    
    /**
     * Document settings
     */
    public function documents(): void {
        $companyId = Session::companyId();
        
        $settings = [
            'doc_number_format_order' => getCompanySetting($companyId, 'doc_number_format_order', 'ORD/{YY}{MM}/{NUM}'),
            'doc_number_format_quotation' => getCompanySetting($companyId, 'doc_number_format_quotation', 'QUO/{YY}{MM}/{NUM}'),
            'doc_number_format_invoice' => getCompanySetting($companyId, 'doc_number_format_invoice', 'INV/{YY}{MM}/{NUM}'),
            'doc_number_format_receipt' => getCompanySetting($companyId, 'doc_number_format_receipt', 'RCP/{YY}{MM}/{NUM}'),
            'invoice_due_days' => getCompanySetting($companyId, 'invoice_due_days', 14),
            'default_markup_type' => getCompanySetting($companyId, 'default_markup_type', 'percentage'),
            'default_markup_value' => getCompanySetting($companyId, 'default_markup_value', 10)
        ];
        
        $data = [
            'pageTitle' => 'Pengaturan Dokumen',
            'pageHeader' => true,
            'pageSubtitle' => 'Format nomor dokumen dan markup',
            'settings' => $settings,
            'activeTab' => 'documents'
        ];
        
        render('settings/documents', $data);
    }
    
    /**
     * Update document settings
     */
    public function updateDocuments(): void {
        $companyId = Session::companyId();
        
        $settings = [
            'doc_number_format_order' => trim($_POST['doc_number_format_order'] ?? 'ORD/{YY}{MM}/{NUM}'),
            'doc_number_format_quotation' => trim($_POST['doc_number_format_quotation'] ?? 'QUO/{YY}{MM}/{NUM}'),
            'doc_number_format_invoice' => trim($_POST['doc_number_format_invoice'] ?? 'INV/{YY}{MM}/{NUM}'),
            'doc_number_format_receipt' => trim($_POST['doc_number_format_receipt'] ?? 'RCP/{YY}{MM}/{NUM}'),
            'invoice_due_days' => (int) ($_POST['invoice_due_days'] ?? 14),
            'default_markup_type' => $_POST['default_markup_type'] ?? 'percentage',
            'default_markup_value' => (float) ($_POST['default_markup_value'] ?? 10)
        ];
        
        foreach ($settings as $key => $value) {
            setCompanySetting($companyId, $key, $value);
        }
        
        logActivity('update', 'settings', 0, 'settings', 'Updated document settings');
        
        Session::flash('success', 'Pengaturan dokumen berhasil disimpan.');
        redirect('/settings/documents');
    }
    
    /**
     * Integration settings (Dripsender)
     */
    public function integrations(): void {
        $companyId = Session::companyId();
        
        $settings = [
            'dripsender_api_key' => getCompanySetting($companyId, 'dripsender_api_key', ''),
            'dripsender_enabled' => getCompanySetting($companyId, 'dripsender_enabled', '0')
        ];
        
        // Get recent logs
        $logs = db()->fetchAll(
            "SELECT * FROM dripsender_logs WHERE company_id = ? ORDER BY created_at DESC LIMIT 20",
            [$companyId]
        );
        
        $data = [
            'pageTitle' => 'Integrasi',
            'pageHeader' => true,
            'pageSubtitle' => 'Pengaturan Dripsender WhatsApp',
            'settings' => $settings,
            'logs' => $logs,
            'activeTab' => 'integrations'
        ];
        
        render('settings/integrations', $data);
    }
    
    /**
     * Update integration settings
     */
    public function updateIntegrations(): void {
        $companyId = Session::companyId();
        
        setCompanySetting($companyId, 'dripsender_api_key', trim($_POST['dripsender_api_key'] ?? ''));
        setCompanySetting($companyId, 'dripsender_enabled', isset($_POST['dripsender_enabled']) ? '1' : '0');
        
        logActivity('update', 'settings', 0, 'settings', 'Updated integration settings');
        
        Session::flash('success', 'Pengaturan integrasi berhasil disimpan.');
        redirect('/settings/integrations');
    }
    
    /**
     * Test Dripsender
     */
    public function testDripsender(): void {
        $companyId = Session::companyId();
        $apiKey = getCompanySetting($companyId, 'dripsender_api_key', '');
        
        if (!$apiKey) {
            jsonError('API Key belum diatur.', 400);
            return;
        }
        
        $phone = $_POST['phone'] ?? '';
        
        if (!$phone) {
            jsonError('Nomor telepon diperlukan.', 400);
            return;
        }
        
        $phone = formatPhoneWa($phone);
        $message = "Ini adalah pesan tes dari " . APP_NAME . ".\nJika Anda menerima pesan ini, integrasi berhasil!";
        
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
        
        if ($httpCode == 200) {
            jsonSuccess(['message' => 'Pesan tes berhasil dikirim!']);
        } else {
            jsonError('Gagal mengirim pesan: ' . $response, 400);
        }
    }
    
    /**
     * Page access settings (Super Admin only) — which sidebar pages are
     * visible/reachable for the "admin" role.
     */
    public function pageAccess(): void {
        $disabled = getDisabledPagesForRole(ROLE_ADMIN);

        $data = [
            'pageTitle' => 'Akses Halaman',
            'pageHeader' => true,
            'pageSubtitle' => 'Atur halaman yang bisa diakses role Admin',
            'pages' => PAGE_ACCESS_PAGES,
            'disabledPages' => $disabled,
            'activeTab' => 'page-access'
        ];

        render('settings/page-access', $data);
    }

    /**
     * Update page access settings (Super Admin only)
     */
    public function updatePageAccess(): void {
        $companyId = Session::companyId();
        $enabledKeys = $_POST['pages'] ?? []; // checked = tetap terlihat

        db()->delete('role_page_access', 'company_id = ? AND role = ?', [$companyId, ROLE_ADMIN]);

        foreach (array_keys(PAGE_ACCESS_PAGES) as $pageKey) {
            if (!in_array($pageKey, $enabledKeys, true)) {
                db()->insert('role_page_access', [
                    'company_id' => $companyId,
                    'role' => ROLE_ADMIN,
                    'page_key' => $pageKey
                ]);
            }
        }

        logActivity('update', 'settings', 0, 'settings', 'Updated page access for role admin');

        Session::flash('success', 'Akses halaman berhasil disimpan.');
        redirect('/settings/page-access');
    }

    /**
     * Upload file helper
     */
    private function uploadFile(array $file, string $folder): ?string {
        $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
        
        if (!in_array($file['type'], $allowedTypes)) {
            Session::flash('error', 'Tipe file tidak diizinkan.');
            return null;
        }
        
        $maxSize = 2 * 1024 * 1024; // 2MB
        if ($file['size'] > $maxSize) {
            Session::flash('error', 'Ukuran file terlalu besar (maks 2MB).');
            return null;
        }
        
        $uploadDir = UPLOADS_PATH . '/' . $folder;
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }
        
        $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
        $filename = Session::companyId() . '_' . time() . '.' . $ext;
        $filepath = $uploadDir . '/' . $filename;
        
        if (move_uploaded_file($file['tmp_name'], $filepath)) {
            return $folder . '/' . $filename;
        }
        
        return null;
    }
}
