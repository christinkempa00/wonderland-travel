<?php
/**
 * ================================================================
 * TRAVEL MANAGEMENT SYSTEM - Client Controller
 * ================================================================
 */

require_once MODELS_PATH . '/Client.php';
require_once MODELS_PATH . '/ClientCommunication.php';

class ClientController {
    
    /**
     * List clients
     */
    public function index(): void {
        $companyId = Session::companyId();
        $page = (int) ($_GET['page'] ?? 1);
        $search = trim($_GET['search'] ?? '');
        $perPage = PAGINATION_PER_PAGE;
        
        $result = Client::getByCompanyPaginated($companyId, $page, $perPage, $search);
        
        $data = [
            'pageTitle' => 'Daftar Klien',
            'pageHeader' => true,
            'pageSubtitle' => 'Kelola data klien Anda',
            'pageActions' => $this->getPageActions(),
            'clients' => $result['data'],
            'pagination' => $result,
            'search' => $search
        ];
        
        render('clients/index', $data);
    }
    
    /**
     * Create client form
     */
    public function create(): void {
        $data = [
            'pageTitle' => 'Tambah Klien',
            'pageHeader' => true,
            'breadcrumbs' => [
                ['label' => 'Klien', 'url' => '/clients'],
                ['label' => 'Tambah']
            ],
            'client' => null,
            'isEdit' => false
        ];
        
        render('clients/form', $data);
    }
    
    /**
     * Store new client
     */
    public function store(): void {
        $companyId = Session::companyId();
        
        $data = [
            'name' => trim($_POST['name'] ?? ''),
            'contact_person' => trim($_POST['contact_person'] ?? ''),
            'email' => trim($_POST['email'] ?? ''),
            'phone' => trim($_POST['phone'] ?? ''),
            'address' => trim($_POST['address'] ?? ''),
            'npwp' => trim($_POST['npwp'] ?? ''),
            'uses_divisi' => !empty($_POST['uses_divisi']) ? 1 : 0,
            'notes' => trim($_POST['notes'] ?? '')
        ];
        
        // Validate
        $errors = $this->validate($data, $companyId);
        
        if (!empty($errors)) {
            flashInput($_POST);
            foreach ($errors as $error) {
                Session::flash('error', $error);
            }
            redirect('/clients/create');
            return;
        }
        
        // Create client
        $client = Client::createForCompany($companyId, $data);
        
        logActivity('create', 'client', $client->id, 'client', 'Created client: ' . $client->name);
        
        Session::flash('success', 'Klien berhasil ditambahkan.');
        redirect('/clients');
    }
    
    /**
     * Show client detail
     */
    public function show(int $id): void {
        $client = $this->findClient($id);
        
        if (!$client) {
            Session::flash('error', 'Klien tidak ditemukan.');
            redirect('/clients');
            return;
        }
        
        $data = [
            'pageTitle' => $client->name,
            'pageHeader' => true,
            'breadcrumbs' => [
                ['label' => 'Klien', 'url' => '/clients'],
                ['label' => $client->name]
            ],
            'client' => $client,
            'orders' => $client->getRecentOrders(10),
            'communications' => ClientCommunication::getForClient($id),
            'stats' => [
                'total_orders' => $client->getOrderCount(),
                'total_revenue' => $client->getTotalRevenue()
            ]
        ];

        render('clients/show', $data);
    }

    /**
     * Add a communication log entry for a client
     * POST /clients/{id}/communications
     */
    public function addCommunication(int $id): void {
        $client = $this->findClient($id);

        if (!$client) {
            Session::flash('error', 'Klien tidak ditemukan.');
            redirect('/clients');
            return;
        }

        ClientCommunication::create([
            'company_id' => Session::companyId(),
            'client_id' => $id,
            'type' => $_POST['type'] ?? 'phone',
            'notes' => trim($_POST['notes'] ?? ''),
            'communication_date' => $_POST['communication_date'] ?: date('Y-m-d'),
            'created_by' => Session::userId()
        ]);

        logActivity('create', 'client_communication', $id, 'client', 'Added communication log for: ' . $client->name);

        Session::flash('success', 'Riwayat komunikasi berhasil ditambahkan.');
        redirect('/clients/' . $id);
    }
    
    /**
     * Edit client form
     */
    public function edit(int $id): void {
        $client = $this->findClient($id);
        
        if (!$client) {
            Session::flash('error', 'Klien tidak ditemukan.');
            redirect('/clients');
            return;
        }
        
        $data = [
            'pageTitle' => 'Edit Klien',
            'pageHeader' => true,
            'breadcrumbs' => [
                ['label' => 'Klien', 'url' => '/clients'],
                ['label' => $client->name, 'url' => '/clients/' . $client->id],
                ['label' => 'Edit']
            ],
            'client' => $client,
            'isEdit' => true
        ];
        
        render('clients/form', $data);
    }
    
    /**
     * Update client
     */
    public function update(int $id): void {
        $client = $this->findClient($id);
        
        if (!$client) {
            Session::flash('error', 'Klien tidak ditemukan.');
            redirect('/clients');
            return;
        }
        
        $data = [
            'name' => trim($_POST['name'] ?? ''),
            'contact_person' => trim($_POST['contact_person'] ?? ''),
            'email' => trim($_POST['email'] ?? ''),
            'phone' => trim($_POST['phone'] ?? ''),
            'address' => trim($_POST['address'] ?? ''),
            'npwp' => trim($_POST['npwp'] ?? ''),
            'uses_divisi' => !empty($_POST['uses_divisi']) ? 1 : 0,
            'notes' => trim($_POST['notes'] ?? '')
        ];
        
        // Validate
        $errors = $this->validate($data, Session::companyId(), $id);
        
        if (!empty($errors)) {
            flashInput($_POST);
            foreach ($errors as $error) {
                Session::flash('error', $error);
            }
            redirect('/clients/' . $id . '/edit');
            return;
        }
        
        // Update
        $client->update($data);
        
        logActivity('update', 'client', $client->id, 'client', 'Updated client: ' . $client->name);
        
        Session::flash('success', 'Klien berhasil diperbarui.');
        redirect('/clients/' . $id);
    }
    
    /**
     * Delete client
     */
    public function destroy(int $id): void {
        $client = $this->findClient($id);
        
        if (!$client) {
            Session::flash('error', 'Klien tidak ditemukan.');
            redirect('/clients');
            return;
        }
        
        // Check if has orders
        if ($client->hasOrders()) {
            Session::flash('error', 'Klien tidak dapat dihapus karena memiliki pesanan terkait.');
            redirect('/clients/' . $id);
            return;
        }
        
        $name = $client->name;
        $client->delete();
        
        logActivity('delete', 'client', $id, 'client', 'Deleted client: ' . $name);
        
        Session::flash('success', 'Klien berhasil dihapus.');
        redirect('/clients');
    }
    
    /**
     * API: Search clients
     */
    public function search(): void {
        $companyId = Session::companyId();
        $query = trim($_GET['q'] ?? '');
        
        if (strlen($query) < 2) {
            jsonSuccess([]);
            return;
        }
        
        $clients = Client::search($companyId, $query);
        
        $result = array_map(function($client) {
            return [
                'id' => $client['id'],
                'name' => $client['name'],
                'contact_person' => $client['contact_person'],
                'phone' => $client['phone'],
                'email' => $client['email']
            ];
        }, $clients);
        
        jsonSuccess($result);
    }
    
    /**
     * Find client with company check
     */
    private function findClient(int $id): ?Client {
        $client = Client::find($id);
        
        if (!$client || $client->company_id != Session::companyId()) {
            return null;
        }
        
        return $client;
    }
    
    /**
     * Validate client data
     */
    private function validate(array $data, int $companyId, ?int $exceptId = null): array {
        $errors = [];
        
        if (empty($data['name'])) {
            $errors[] = 'Nama klien wajib diisi.';
        }
        
        if (!empty($data['email'])) {
            if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
                $errors[] = 'Format email tidak valid.';
            } elseif (!Client::isEmailUnique($companyId, $data['email'], $exceptId)) {
                $errors[] = 'Email sudah digunakan oleh klien lain.';
            }
        }
        
        return $errors;
    }
    
    /**
     * Get page actions HTML
     */
    private function getPageActions(): string {
        if (!Session::can('clients.create')) {
            return '';
        }
        
        return '<a href="' . url('/clients/create') . '" class="btn btn-primary">
            <i class="fas fa-plus"></i> Tambah Klien
        </a>';
    }
}
