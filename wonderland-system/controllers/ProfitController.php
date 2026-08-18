<?php
/**
 * ================================================================
 * TRAVEL MANAGEMENT SYSTEM - Profit Controller
 * ================================================================
 */

require_once MODELS_PATH . '/ProfitRecipient.php';

class ProfitController {
    
    /**
     * List profit recipients
     */
    public function index(): void {
        $companyId = Session::companyId();
        
        $recipients = ProfitRecipient::getByCompany($companyId, false);
        $totalPercentage = ProfitRecipient::getTotalPercentage($companyId);
        
        // Get stats for each recipient
        foreach ($recipients as &$recipient) {
            $model = new ProfitRecipient($recipient);
            $model->exists = true;
            $recipient['total_pending'] = $model->getTotalPending();
            $recipient['total_paid'] = $model->getTotalPaid();
        }
        
        $data = [
            'pageTitle' => 'Bagi Hasil',
            'pageHeader' => true,
            'pageSubtitle' => 'Kelola penerima bagi hasil',
            'pageActions' => $this->getPageActions(),
            'recipients' => $recipients,
            'totalPercentage' => $totalPercentage
        ];
        
        render('profit/index', $data);
    }
    
    /**
     * Create recipient form
     */
    public function create(): void {
        $companyId = Session::companyId();
        $totalPercentage = ProfitRecipient::getTotalPercentage($companyId);
        $remainingPercentage = 100 - $totalPercentage;
        
        $data = [
            'pageTitle' => 'Tambah Penerima',
            'pageHeader' => true,
            'breadcrumbs' => [
                ['label' => 'Bagi Hasil', 'url' => '/profit'],
                ['label' => 'Tambah']
            ],
            'recipient' => null,
            'remainingPercentage' => $remainingPercentage,
            'isEdit' => false
        ];
        
        render('profit/form', $data);
    }
    
    /**
     * Store recipient
     */
    public function store(): void {
        $companyId = Session::companyId();
        
        $data = [
            'name' => trim($_POST['name'] ?? ''),
            'role' => trim($_POST['role'] ?? ''),
            'phone' => trim($_POST['phone'] ?? ''),
            'email' => trim($_POST['email'] ?? ''),
            'bank_name' => trim($_POST['bank_name'] ?? ''),
            'bank_account' => trim($_POST['bank_account'] ?? ''),
            'bank_holder' => trim($_POST['bank_holder'] ?? ''),
            'percentage' => (float) ($_POST['percentage'] ?? 0),
            'is_active' => isset($_POST['is_active']) ? 1 : 0
        ];
        
        // Validate
        $errors = $this->validate($data, $companyId);
        
        if (!empty($errors)) {
            foreach ($errors as $error) {
                Session::flash('error', $error);
            }
            flashInput($_POST);
            redirect('/profit/create');
            return;
        }
        
        $recipient = ProfitRecipient::createForCompany($companyId, $data);
        
        logActivity('create', 'profit_recipient', $recipient->id, 'profit', 'Created recipient: ' . $recipient->name);
        
        Session::flash('success', 'Penerima bagi hasil berhasil ditambahkan.');
        redirect('/profit');
    }
    
    /**
     * Show recipient detail
     */
    public function show(int $id): void {
        $recipient = $this->findRecipient($id);
        
        if (!$recipient) {
            Session::flash('error', 'Data tidak ditemukan.');
            redirect('/profit');
            return;
        }
        
        $data = [
            'pageTitle' => $recipient->name,
            'pageHeader' => true,
            'breadcrumbs' => [
                ['label' => 'Bagi Hasil', 'url' => '/profit'],
                ['label' => $recipient->name]
            ],
            'recipient' => $recipient,
            'pendingDistributions' => $recipient->getPendingDistributions(),
            'history' => $recipient->getDistributionHistory(),
            'totalPending' => $recipient->getTotalPending(),
            'totalPaid' => $recipient->getTotalPaid()
        ];
        
        render('profit/show', $data);
    }
    
    /**
     * Edit recipient form
     */
    public function edit(int $id): void {
        $recipient = $this->findRecipient($id);
        
        if (!$recipient) {
            Session::flash('error', 'Data tidak ditemukan.');
            redirect('/profit');
            return;
        }
        
        $companyId = Session::companyId();
        $totalPercentage = ProfitRecipient::getTotalPercentage($companyId);
        $remainingPercentage = 100 - $totalPercentage + $recipient->percentage;
        
        $data = [
            'pageTitle' => 'Edit Penerima',
            'pageHeader' => true,
            'breadcrumbs' => [
                ['label' => 'Bagi Hasil', 'url' => '/profit'],
                ['label' => $recipient->name, 'url' => '/profit/' . $recipient->id],
                ['label' => 'Edit']
            ],
            'recipient' => $recipient,
            'remainingPercentage' => $remainingPercentage,
            'isEdit' => true
        ];
        
        render('profit/form', $data);
    }
    
    /**
     * Update recipient
     */
    public function update(int $id): void {
        $recipient = $this->findRecipient($id);
        
        if (!$recipient) {
            Session::flash('error', 'Data tidak ditemukan.');
            redirect('/profit');
            return;
        }
        
        $data = [
            'name' => trim($_POST['name'] ?? ''),
            'role' => trim($_POST['role'] ?? ''),
            'phone' => trim($_POST['phone'] ?? ''),
            'email' => trim($_POST['email'] ?? ''),
            'bank_name' => trim($_POST['bank_name'] ?? ''),
            'bank_account' => trim($_POST['bank_account'] ?? ''),
            'bank_holder' => trim($_POST['bank_holder'] ?? ''),
            'percentage' => (float) ($_POST['percentage'] ?? 0),
            'is_active' => isset($_POST['is_active']) ? 1 : 0
        ];
        
        // Validate
        $errors = $this->validate($data, Session::companyId(), $id);
        
        if (!empty($errors)) {
            foreach ($errors as $error) {
                Session::flash('error', $error);
            }
            flashInput($_POST);
            redirect('/profit/' . $id . '/edit');
            return;
        }
        
        $recipient->update($data);
        
        logActivity('update', 'profit_recipient', $recipient->id, 'profit', 'Updated recipient: ' . $recipient->name);
        
        Session::flash('success', 'Penerima bagi hasil berhasil diperbarui.');
        redirect('/profit/' . $id);
    }
    
    /**
     * Delete recipient
     */
    public function destroy(int $id): void {
        $recipient = $this->findRecipient($id);
        
        if (!$recipient) {
            Session::flash('error', 'Data tidak ditemukan.');
            redirect('/profit');
            return;
        }
        
        $name = $recipient->name;
        $recipient->delete();
        
        logActivity('delete', 'profit_recipient', $id, 'profit', 'Deleted recipient: ' . $name);
        
        Session::flash('success', 'Penerima bagi hasil berhasil dihapus.');
        redirect('/profit');
    }
    
    /**
     * Mark distributions as paid
     */
    public function markPaid(int $id): void {
        $recipient = $this->findRecipient($id);
        
        if (!$recipient) {
            jsonError('Data tidak ditemukan.', 404);
            return;
        }
        
        $data = json_decode(file_get_contents('php://input'), true);
        $shareIds = $data['share_ids'] ?? [];
        
        if (empty($shareIds)) {
            jsonError('Pilih minimal satu distribusi.', 400);
            return;
        }
        
        $count = $recipient->markDistributionsAsPaid($shareIds);
        
        logActivity('update', 'profit_shares', $id, 'profit', "Marked {$count} distributions as paid");
        
        jsonSuccess(['message' => "{$count} distribusi berhasil ditandai lunas."]);
    }
    
    /**
     * Send WhatsApp notification
     */
    public function notify(int $id): void {
        $recipient = $this->findRecipient($id);
        
        if (!$recipient) {
            jsonError('Data tidak ditemukan.', 404);
            return;
        }
        
        if (!$recipient->phone) {
            jsonError('Nomor telepon tidak tersedia.', 400);
            return;
        }
        
        $totalPending = $recipient->getTotalPending();
        
        if ($totalPending <= 0) {
            jsonError('Tidak ada pembayaran tertunda.', 400);
            return;
        }
        
        $company = db()->fetchOne(
            "SELECT * FROM companies WHERE id = ?",
            [Session::companyId()]
        );
        
        $message = "Halo {$recipient->name},\n\n";
        $message .= "Berikut adalah informasi bagi hasil Anda:\n";
        $message .= "Total Tertunda: " . formatRupiah($totalPending) . "\n\n";
        $message .= "Terima kasih.\n";
        $message .= $company['name'];
        
        // Send via Dripsender
        $this->sendDripsender($recipient->phone, $message);
        
        jsonSuccess(['message' => 'Notifikasi berhasil dikirim.']);
    }
    
    /**
     * Send via Dripsender
     */
    private function sendDripsender(string $phone, string $message): void {
        $companyId = Session::companyId();
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
        curl_close($ch);
        
        // Log
        db()->insert('dripsender_logs', [
            'company_id' => $companyId,
            'phone' => $phone,
            'message' => $message,
            'response' => $response,
            'status' => 'sent',
            'created_at' => date('Y-m-d H:i:s')
        ]);
    }
    
    /**
     * Find recipient with company check
     */
    private function findRecipient(int $id): ?ProfitRecipient {
        $recipient = ProfitRecipient::find($id);
        if (!$recipient || $recipient->company_id != Session::companyId()) {
            return null;
        }
        return $recipient;
    }
    
    /**
     * Validate data
     */
    private function validate(array $data, int $companyId, ?int $exceptId = null): array {
        $errors = [];
        
        if (empty($data['name'])) {
            $errors[] = 'Nama penerima wajib diisi.';
        }
        
        if ($data['percentage'] <= 0 || $data['percentage'] > 100) {
            $errors[] = 'Persentase harus antara 0.01 dan 100.';
        }
        
        // Check total percentage
        $totalPercentage = ProfitRecipient::getTotalPercentage($companyId);
        
        if ($exceptId) {
            $currentPercentage = (float) db()->fetchColumn(
                "SELECT percentage FROM profit_recipients WHERE id = ?",
                [$exceptId]
            );
            $totalPercentage -= $currentPercentage;
        }
        
        if ($totalPercentage + $data['percentage'] > 100) {
            $errors[] = 'Total persentase tidak boleh melebihi 100%. Sisa: ' . (100 - $totalPercentage) . '%';
        }
        
        return $errors;
    }
    
    /**
     * Get page actions
     */
    private function getPageActions(): string {
        if (!Session::can('profit.create')) return '';
        return '<a href="' . url('/profit/create') . '" class="btn btn-primary">
            <i class="fas fa-plus"></i> Tambah Penerima
        </a>';
    }
}
