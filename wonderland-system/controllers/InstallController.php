<?php
/**
 * ================================================================
 * TRAVEL MANAGEMENT SYSTEM - Install Controller
 * ================================================================
 */

class InstallController {
    
    /**
     * Check if already installed
     */
    private function checkInstalled(): bool {
        return file_exists(BASE_PATH . '/config/installed.php');
    }
    
    /**
     * Redirect if already installed
     */
    private function guardInstalled(): void {
        if ($this->checkInstalled()) {
            redirect('/login');
            exit;
        }
    }
    
    /**
     * Get current step from session
     */
    private function getCurrentStep(): int {
        return Session::get('install_step', 1);
    }
    
    /**
     * Set current step
     */
    private function setCurrentStep(int $step): void {
        Session::set('install_step', $step);
    }
    
    /**
     * Index - redirect to current step
     */
    public function index(): void {
        $this->guardInstalled();
        
        $step = $this->getCurrentStep();
        redirect('/install/step/' . $step);
    }
    
    /**
     * Show step
     */
    public function step(int $step): void {
        $this->guardInstalled();
        
        // Validate step access
        $currentStep = $this->getCurrentStep();
        if ($step > $currentStep) {
            redirect('/install/step/' . $currentStep);
            return;
        }
        
        $data = [
            'step' => $step,
            'totalSteps' => 5,
            'pageTitle' => 'Instalasi - Langkah ' . $step
        ];
        
        switch ($step) {
            case 1:
                $data['requirements'] = $this->checkRequirements();
                $data['allPassed'] = !in_array(false, array_column($data['requirements'], 'passed'));
                break;
                
            case 2:
                $data['dbConfig'] = Session::get('install_db', [
                    'host' => 'localhost',
                    'name' => '',
                    'user' => '',
                    'pass' => ''
                ]);
                break;
                
            case 3:
                $data['companyData'] = Session::get('install_company', [
                    'name' => '',
                    'address' => '',
                    'phone' => '',
                    'email' => ''
                ]);
                break;
                
            case 4:
                $data['adminData'] = Session::get('install_admin', [
                    'name' => '',
                    'email' => '',
                    'phone' => ''
                ]);
                break;
                
            case 5:
                $data['summary'] = [
                    'company' => Session::get('install_company'),
                    'admin' => Session::get('install_admin')
                ];
                break;
        }
        
        echo view('install/step' . $step, $data);
    }
    
    /**
     * Process step
     */
    public function processStep(int $step): void {
        $this->guardInstalled();
        
        switch ($step) {
            case 1:
                $this->processStep1();
                break;
            case 2:
                $this->processStep2();
                break;
            case 3:
                $this->processStep3();
                break;
            case 4:
                $this->processStep4();
                break;
            case 5:
                $this->processStep5();
                break;
        }
    }
    
    /**
     * Step 1: Requirements Check
     */
    private function processStep1(): void {
        $requirements = $this->checkRequirements();
        $allPassed = !in_array(false, array_column($requirements, 'passed'));
        
        if (!$allPassed) {
            Session::flash('error', 'Beberapa persyaratan sistem belum terpenuhi.');
            redirect('/install/step/1');
            return;
        }
        
        $this->setCurrentStep(2);
        redirect('/install/step/2');
    }
    
    /**
     * Step 2: Database Configuration
     */
    private function processStep2(): void {
        $host = trim($_POST['db_host'] ?? 'localhost');
        $name = trim($_POST['db_name'] ?? '');
        $user = trim($_POST['db_user'] ?? '');
        $pass = $_POST['db_pass'] ?? '';
        
        // Validate
        if (empty($name) || empty($user)) {
            Session::flash('error', 'Nama database dan username wajib diisi.');
            redirect('/install/step/2');
            return;
        }
        
        // Test connection
        try {
            $dsn = "mysql:host={$host};charset=utf8mb4";
            $pdo = new PDO($dsn, $user, $pass, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
            ]);
            
            // Check if database exists
            $stmt = $pdo->query("SHOW DATABASES LIKE '{$name}'");
            if ($stmt->rowCount() === 0) {
                // Try to create database
                $pdo->exec("CREATE DATABASE IF NOT EXISTS `{$name}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
            }
            
            // Connect to database
            $pdo->exec("USE `{$name}`");
            
        } catch (PDOException $e) {
            Session::flash('error', 'Koneksi database gagal: ' . $e->getMessage());
            Session::set('install_db', compact('host', 'name', 'user', 'pass'));
            redirect('/install/step/2');
            return;
        }
        
        // Save to session
        Session::set('install_db', compact('host', 'name', 'user', 'pass'));
        
        $this->setCurrentStep(3);
        redirect('/install/step/3');
    }
    
    /**
     * Step 3: Company Setup
     */
    private function processStep3(): void {
        $name = trim($_POST['company_name'] ?? '');
        $address = trim($_POST['company_address'] ?? '');
        $phone = trim($_POST['company_phone'] ?? '');
        $email = trim($_POST['company_email'] ?? '');
        
        // Validate
        if (empty($name)) {
            Session::flash('error', 'Nama perusahaan wajib diisi.');
            redirect('/install/step/3');
            return;
        }
        
        // Save to session
        Session::set('install_company', compact('name', 'address', 'phone', 'email'));
        
        $this->setCurrentStep(4);
        redirect('/install/step/4');
    }
    
    /**
     * Step 4: Admin Account
     */
    private function processStep4(): void {
        $name = trim($_POST['admin_name'] ?? '');
        $email = trim($_POST['admin_email'] ?? '');
        $phone = trim($_POST['admin_phone'] ?? '');
        $password = $_POST['admin_password'] ?? '';
        $passwordConfirm = $_POST['admin_password_confirmation'] ?? '';
        
        // Validate
        $errors = [];
        
        if (empty($name)) {
            $errors[] = 'Nama wajib diisi.';
        }
        
        if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'Email tidak valid.';
        }
        
        if (strlen($password) < 8) {
            $errors[] = 'Password minimal 8 karakter.';
        }
        
        if ($password !== $passwordConfirm) {
            $errors[] = 'Konfirmasi password tidak cocok.';
        }
        
        if (!empty($errors)) {
            foreach ($errors as $error) {
                Session::flash('error', $error);
            }
            Session::set('install_admin', compact('name', 'email', 'phone'));
            redirect('/install/step/4');
            return;
        }
        
        // Save to session (password will be hashed later)
        Session::set('install_admin', compact('name', 'email', 'phone', 'password'));
        
        $this->setCurrentStep(5);
        redirect('/install/step/5');
    }
    
    /**
     * Step 5: Finish Installation
     */
    private function processStep5(): void {
        $dbConfig = Session::get('install_db');
        $companyData = Session::get('install_company');
        $adminData = Session::get('install_admin');
        
        if (!$dbConfig || !$companyData || !$adminData) {
            Session::flash('error', 'Data instalasi tidak lengkap.');
            redirect('/install/step/1');
            return;
        }
        
        try {
            // Connect to database
            $dsn = "mysql:host={$dbConfig['host']};dbname={$dbConfig['name']};charset=utf8mb4";
            $pdo = new PDO($dsn, $dbConfig['user'], $dbConfig['pass'], [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
            ]);
            
            // Import SQL schema
            $this->importSchema($pdo);
            
            // Create company
            $companyId = $this->createCompany($pdo, $companyData);
            
            // Create admin user
            $this->createAdmin($pdo, $adminData, $companyId);
            
            // Initialize company settings and accounts
            $this->initializeCompany($pdo, $companyId);
            
            // Create config file
            $this->createConfigFile($dbConfig);
            
            // Create installed marker
            $this->createInstalledFile();
            
            // Clear installation session
            Session::remove('install_step');
            Session::remove('install_db');
            Session::remove('install_company');
            Session::remove('install_admin');
            
            Session::flash('success', 'Instalasi berhasil! Silakan login dengan akun admin Anda.');
            redirect('/login');
            
        } catch (Exception $e) {
            Session::flash('error', 'Instalasi gagal: ' . $e->getMessage());
            redirect('/install/step/5');
        }
    }
    
    /**
     * Check system requirements
     */
    private function checkRequirements(): array {
        $requirements = [];
        
        // PHP Version
        $requirements[] = [
            'name' => 'PHP Version',
            'required' => '>= 8.0',
            'current' => PHP_VERSION,
            'passed' => version_compare(PHP_VERSION, '8.0.0', '>=')
        ];
        
        // PDO Extension
        $requirements[] = [
            'name' => 'PDO Extension',
            'required' => 'Enabled',
            'current' => extension_loaded('pdo') ? 'Enabled' : 'Disabled',
            'passed' => extension_loaded('pdo')
        ];
        
        // PDO MySQL
        $requirements[] = [
            'name' => 'PDO MySQL',
            'required' => 'Enabled',
            'current' => extension_loaded('pdo_mysql') ? 'Enabled' : 'Disabled',
            'passed' => extension_loaded('pdo_mysql')
        ];
        
        // JSON Extension
        $requirements[] = [
            'name' => 'JSON Extension',
            'required' => 'Enabled',
            'current' => extension_loaded('json') ? 'Enabled' : 'Disabled',
            'passed' => extension_loaded('json')
        ];
        
        // Mbstring Extension
        $requirements[] = [
            'name' => 'Mbstring Extension',
            'required' => 'Enabled',
            'current' => extension_loaded('mbstring') ? 'Enabled' : 'Disabled',
            'passed' => extension_loaded('mbstring')
        ];
        
        // cURL Extension
        $requirements[] = [
            'name' => 'cURL Extension',
            'required' => 'Enabled',
            'current' => extension_loaded('curl') ? 'Enabled' : 'Disabled',
            'passed' => extension_loaded('curl')
        ];
        
        // FileInfo Extension
        $requirements[] = [
            'name' => 'FileInfo Extension',
            'required' => 'Enabled',
            'current' => extension_loaded('fileinfo') ? 'Enabled' : 'Disabled',
            'passed' => extension_loaded('fileinfo')
        ];
        
        // Config directory writable
        $configWritable = is_writable(BASE_PATH . '/config');
        $requirements[] = [
            'name' => 'Config Directory',
            'required' => 'Writable',
            'current' => $configWritable ? 'Writable' : 'Not Writable',
            'passed' => $configWritable
        ];
        
        // Uploads directory writable
        $uploadsPath = BASE_PATH . '/uploads';
        if (!is_dir($uploadsPath)) {
            @mkdir($uploadsPath, 0755, true);
        }
        $uploadsWritable = is_writable($uploadsPath);
        $requirements[] = [
            'name' => 'Uploads Directory',
            'required' => 'Writable',
            'current' => $uploadsWritable ? 'Writable' : 'Not Writable',
            'passed' => $uploadsWritable
        ];
        
        // Cache directory writable
        $cachePath = BASE_PATH . '/cache';
        if (!is_dir($cachePath)) {
            @mkdir($cachePath, 0755, true);
        }
        $cacheWritable = is_writable($cachePath);
        $requirements[] = [
            'name' => 'Cache Directory',
            'required' => 'Writable',
            'current' => $cacheWritable ? 'Writable' : 'Not Writable',
            'passed' => $cacheWritable
        ];
        
        return $requirements;
    }
    
    /**
     * Import SQL schema
     */
    private function importSchema(PDO $pdo): void {
        $sqlFile = BASE_PATH . '/sql/install.sql';
        
        if (!file_exists($sqlFile)) {
            throw new Exception('File SQL tidak ditemukan: ' . $sqlFile);
        }
        
        $sql = file_get_contents($sqlFile);
        
        // Split by delimiter for stored procedures
        $queries = $this->splitSqlQueries($sql);
        
        foreach ($queries as $query) {
            $query = trim($query);
            if (!empty($query)) {
                try {
                    $pdo->exec($query);
                } catch (PDOException $e) {
                    // Skip duplicate errors for tables that already exist
                    if (strpos($e->getMessage(), 'already exists') === false) {
                        throw $e;
                    }
                }
            }
        }
    }
    
    /**
     * Split SQL queries handling DELIMITER
     */
    private function splitSqlQueries(string $sql): array {
        $queries = [];
        $delimiter = ';';
        $currentQuery = '';
        
        $lines = explode("\n", $sql);
        
        foreach ($lines as $line) {
            $trimmedLine = trim($line);
            
            // Skip comments
            if (strpos($trimmedLine, '--') === 0 || empty($trimmedLine)) {
                continue;
            }
            
            // Check for DELIMITER change
            if (preg_match('/^DELIMITER\s+(.+)$/i', $trimmedLine, $matches)) {
                $delimiter = trim($matches[1]);
                continue;
            }
            
            $currentQuery .= $line . "\n";
            
            // Check if query ends with current delimiter
            if (substr(rtrim($currentQuery), -strlen($delimiter)) === $delimiter) {
                $query = substr(rtrim($currentQuery), 0, -strlen($delimiter));
                if (!empty(trim($query))) {
                    $queries[] = $query;
                }
                $currentQuery = '';
            }
        }
        
        // Add remaining query if any
        if (!empty(trim($currentQuery))) {
            $queries[] = $currentQuery;
        }
        
        return $queries;
    }
    
    /**
     * Create company
     */
    private function createCompany(PDO $pdo, array $data): int {
        $slug = $this->generateSlug($data['name']);
        
        $stmt = $pdo->prepare(
            "INSERT INTO companies (name, slug, address, phone, email, is_active, created_at) 
             VALUES (?, ?, ?, ?, ?, 1, NOW())"
        );
        
        $stmt->execute([
            $data['name'],
            $slug,
            $data['address'],
            $data['phone'],
            $data['email']
        ]);
        
        return (int) $pdo->lastInsertId();
    }
    
    /**
     * Create admin user
     */
    private function createAdmin(PDO $pdo, array $data, int $companyId): int {
        $hashedPassword = password_hash($data['password'], PASSWORD_DEFAULT, ['cost' => 12]);
        
        $stmt = $pdo->prepare(
            "INSERT INTO users (name, email, phone, password, role, is_active, created_at) 
             VALUES (?, ?, ?, ?, 'superadmin', 1, NOW())"
        );
        
        $stmt->execute([
            $data['name'],
            $data['email'],
            $data['phone'],
            $hashedPassword
        ]);
        
        $userId = (int) $pdo->lastInsertId();
        
        // Assign to company
        $stmt = $pdo->prepare(
            "INSERT INTO user_companies (user_id, company_id, is_active, created_at) 
             VALUES (?, ?, 1, NOW())"
        );
        $stmt->execute([$userId, $companyId]);
        
        return $userId;
    }
    
    /**
     * Initialize company settings and accounts
     */
    private function initializeCompany(PDO $pdo, int $companyId): void {
        // Insert default settings
        $defaultSettings = [
            'dripsender_api_key' => ['', 'text'],
            'dripsender_sender_id' => ['', 'text'],
            'default_markup_type' => ['percentage', 'text'],
            'default_markup_value' => ['10', 'number'],
            'invoice_due_days' => ['14', 'number'],
            'doc_number_format_quotation' => ['{NUM}/{PREFIX}/{MONTH}/{YEAR}', 'text'],
            'doc_number_format_agreement' => ['{NUM}/{PREFIX}/{MONTH}/{YEAR}', 'text'],
            'doc_number_format_invoice' => ['{NUM}/{PREFIX}/{MONTH}/{YEAR}', 'text'],
            'doc_number_format_receipt' => ['{NUM}/{PREFIX}/{MONTH}/{YEAR}', 'text'],
            'doc_number_format_order' => ['ORD/{YY}{MM}/{NUM}', 'text']
        ];
        
        $stmt = $pdo->prepare(
            "INSERT INTO company_settings (company_id, setting_key, setting_value, setting_type) VALUES (?, ?, ?, ?)"
        );
        
        foreach ($defaultSettings as $key => [$value, $type]) {
            $stmt->execute([$companyId, $key, $value, $type]);
        }
        
        // Insert default accounts from default_accounts table
        $defaults = $pdo->query("SELECT * FROM default_accounts ORDER BY sort_order")->fetchAll();
        
        $codeToId = [];
        
        $stmtAccount = $pdo->prepare(
            "INSERT INTO accounts (company_id, code, name, type, parent_id, normal_balance, is_cash_bank, is_system, sort_order, created_at) 
             VALUES (?, ?, ?, ?, ?, ?, ?, 1, ?, NOW())"
        );
        
        foreach ($defaults as $acc) {
            $parentId = null;
            if ($acc['parent_code'] && isset($codeToId[$acc['parent_code']])) {
                $parentId = $codeToId[$acc['parent_code']];
            }
            
            $stmtAccount->execute([
                $companyId,
                $acc['code'],
                $acc['name'],
                $acc['type'],
                $parentId,
                $acc['normal_balance'],
                $acc['is_cash_bank'],
                $acc['sort_order']
            ]);
            
            $codeToId[$acc['code']] = $pdo->lastInsertId();
        }
        
        // Create default cash account
        $cashAccountId = $codeToId['1.1.01'] ?? null;
        
        $stmt = $pdo->prepare(
            "INSERT INTO bank_cash (company_id, account_id, name, type, initial_balance, current_balance, is_default, is_active, created_at) 
             VALUES (?, ?, 'Kas Utama', 'cash', 0, 0, 1, 1, NOW())"
        );
        $stmt->execute([$companyId, $cashAccountId]);
    }
    
    /**
     * Generate slug
     */
    private function generateSlug(string $text): string {
        $slug = strtolower($text);
        $slug = preg_replace('/[^a-z0-9\s-]/', '', $slug);
        $slug = preg_replace('/[\s-]+/', '-', $slug);
        return trim($slug, '-');
    }
    
    /**
     * Create database config file
     */
    private function createConfigFile(array $dbConfig): void {
        $content = "<?php
/**
 * Database Configuration
 * Generated by installer on " . date('Y-m-d H:i:s') . "
 */

define('DB_HOST', " . var_export($dbConfig['host'], true) . ");
define('DB_NAME', " . var_export($dbConfig['name'], true) . ");
define('DB_USER', " . var_export($dbConfig['user'], true) . ");
define('DB_PASS', " . var_export($dbConfig['pass'], true) . ");
";
        
        file_put_contents(BASE_PATH . '/config/database.env.php', $content);
    }
    
    /**
     * Create installed marker file
     */
    private function createInstalledFile(): void {
        $content = "<?php
/**
 * Installation marker
 * Generated on " . date('Y-m-d H:i:s') . "
 * DO NOT DELETE THIS FILE
 */
define('INSTALLED_AT', '" . date('Y-m-d H:i:s') . "');
";
        
        file_put_contents(BASE_PATH . '/config/installed.php', $content);
    }
}
