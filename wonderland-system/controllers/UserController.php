<?php
/**
 * ================================================================
 * TRAVEL MANAGEMENT SYSTEM - User Controller
 * ================================================================
 */

require_once MODELS_PATH . '/User.php';
require_once MODELS_PATH . '/Company.php';

class UserController {
    
    /**
     * List users
     */
    public function index(): void {
        $companyId = Session::companyId();
        $search = trim($_GET['search'] ?? '');
        
        // For superadmin, show all users
        if (Session::isSuperAdmin()) {
            $users = User::getAllWithCompanyCount();
        } else {
            // Show users in current company
            $users = User::getByCompany($companyId);
        }
        
        // Filter by search if provided
        if ($search) {
            $users = array_filter($users, function($user) use ($search) {
                $userData = is_array($user) ? $user : $user->toArray();
                return stripos($userData['name'], $search) !== false || 
                       stripos($userData['email'], $search) !== false;
            });
        }
        
        $data = [
            'pageTitle' => 'Pengguna',
            'pageHeader' => true,
            'pageSubtitle' => 'Kelola akun pengguna',
            'pageActions' => $this->getPageActions(),
            'users' => $users,
            'pagination' => null,
            'search' => $search,
            'roles' => ROLES
        ];
        
        render('users/index', $data);
    }
    
    /**
     * Create user form
     */
    public function create(): void {
        $companies = [];
        if (Session::isSuperAdmin()) {
            $companies = Company::getForDropdown();
        }
        
        $data = [
            'pageTitle' => 'Tambah Pengguna',
            'pageHeader' => true,
            'breadcrumbs' => [
                ['label' => 'Pengguna', 'url' => '/users'],
                ['label' => 'Tambah']
            ],
            'user' => null,
            'companies' => $companies,
            'roles' => USER_ROLES,
            'isEdit' => false
        ];
        
        render('users/form', $data);
    }
    
    /**
     * Store user
     */
    public function store(): void {
        $data = [
            'name' => trim($_POST['name'] ?? ''),
            'email' => trim($_POST['email'] ?? ''),
            'phone' => trim($_POST['phone'] ?? ''),
            'role' => $_POST['role'] ?? 'staff',
            'password' => $_POST['password'] ?? '',
            'is_active' => isset($_POST['is_active']) ? 1 : 0
        ];
        
        // Validate
        $errors = $this->validate($data);
        
        if (!empty($errors)) {
            foreach ($errors as $error) {
                Session::flash('error', $error);
            }
            flashInput($_POST);
            redirect('/users/create');
            return;
        }
        
        // Check unique email
        if (User::findByEmail($data['email'])) {
            Session::flash('error', 'Email sudah digunakan.');
            flashInput($_POST);
            redirect('/users/create');
            return;
        }
        
        // Get company IDs
        $companyIds = [];
        if (Session::isSuperAdmin() && !empty($_POST['company_ids'])) {
            $companyIds = $_POST['company_ids'];
        } else {
            $companyIds = [Session::companyId()];
        }
        
        // Hash password
        $data['password'] = password_hash($data['password'], PASSWORD_DEFAULT);
        
        // Create user
        $user = User::createWithCompanies($data, $companyIds);
        
        logActivity('create', 'user', $user->id, 'user', 'Created user: ' . $user->email);
        
        Session::flash('success', 'Pengguna berhasil ditambahkan.');
        redirect('/users');
    }
    
    /**
     * Edit user form
     */
    public function edit(int $id): void {
        $user = $this->findUser($id);
        
        if (!$user) {
            Session::flash('error', 'Pengguna tidak ditemukan.');
            redirect('/users');
            return;
        }
        
        $companies = [];
        $userCompanyIds = [];
        
        if (Session::isSuperAdmin()) {
            $companies = Company::getForDropdown();
            $userCompanies = $user->getCompanies();
            $userCompanyIds = array_column($userCompanies, 'id');
        }
        
        $data = [
            'pageTitle' => 'Edit Pengguna',
            'pageHeader' => true,
            'breadcrumbs' => [
                ['label' => 'Pengguna', 'url' => '/users'],
                ['label' => 'Edit']
            ],
            'user' => $user,
            'companies' => $companies,
            'userCompanyIds' => $userCompanyIds,
            'roles' => USER_ROLES,
            'isEdit' => true
        ];
        
        render('users/form', $data);
    }
    
    /**
     * Update user
     */
    public function update(int $id): void {
        $user = $this->findUser($id);
        
        if (!$user) {
            Session::flash('error', 'Pengguna tidak ditemukan.');
            redirect('/users');
            return;
        }
        
        $data = [
            'name' => trim($_POST['name'] ?? ''),
            'email' => trim($_POST['email'] ?? ''),
            'phone' => trim($_POST['phone'] ?? ''),
            'role' => $_POST['role'] ?? 'staff',
            'is_active' => isset($_POST['is_active']) ? 1 : 0
        ];
        
        // Validate
        $errors = $this->validate($data, false);
        
        if (!empty($errors)) {
            foreach ($errors as $error) {
                Session::flash('error', $error);
            }
            flashInput($_POST);
            redirect('/users/' . $id . '/edit');
            return;
        }
        
        // Check unique email (except self)
        $existingUser = User::findByEmail($data['email']);
        if ($existingUser && $existingUser->id != $id) {
            Session::flash('error', 'Email sudah digunakan.');
            flashInput($_POST);
            redirect('/users/' . $id . '/edit');
            return;
        }
        
        // Update password if provided
        if (!empty($_POST['password'])) {
            if (strlen($_POST['password']) < 8) {
                Session::flash('error', 'Password minimal 8 karakter.');
                flashInput($_POST);
                redirect('/users/' . $id . '/edit');
                return;
            }
            $data['password'] = password_hash($_POST['password'], PASSWORD_DEFAULT);
        }
        
        $user->update($data);
        
        // Sync companies
        if (Session::isSuperAdmin() && isset($_POST['company_ids'])) {
            $user->syncCompanies($_POST['company_ids']);
        }
        
        logActivity('update', 'user', $user->id, 'user', 'Updated user: ' . $user->email);
        
        Session::flash('success', 'Pengguna berhasil diperbarui.');
        redirect('/users');
    }
    
    /**
     * Delete user
     */
    public function destroy(int $id): void {
        $user = $this->findUser($id);
        
        if (!$user) {
            Session::flash('error', 'Pengguna tidak ditemukan.');
            redirect('/users');
            return;
        }
        
        // Cannot delete self
        if ($user->id == Session::userId()) {
            Session::flash('error', 'Tidak dapat menghapus akun sendiri.');
            redirect('/users');
            return;
        }
        
        $email = $user->email;
        
        // Remove from companies
        db()->delete('user_companies', 'user_id = ?', [$id]);
        
        $user->delete();
        
        logActivity('delete', 'user', $id, 'user', 'Deleted user: ' . $email);
        
        Session::flash('success', 'Pengguna berhasil dihapus.');
        redirect('/users');
    }
    
    /**
     * User profile
     */
    public function profile(): void {
        $user = User::find(Session::userId());
        
        $data = [
            'pageTitle' => 'Profil Saya',
            'pageHeader' => true,
            'user' => $user
        ];
        
        render('users/profile', $data);
    }
    
    /**
     * Update profile
     */
    public function updateProfile(): void {
        $user = User::find(Session::userId());
        
        $data = [
            'name' => trim($_POST['name'] ?? ''),
            'phone' => trim($_POST['phone'] ?? '')
        ];
        
        // Handle avatar upload
        if (!empty($_FILES['avatar']['tmp_name'])) {
            $avatarPath = $this->uploadAvatar($_FILES['avatar']);
            if ($avatarPath) {
                $data['avatar'] = $avatarPath;
            }
        }
        
        $user->update($data);
        
        // Update session
        $_SESSION['user']['name'] = $data['name'];
        
        Session::flash('success', 'Profil berhasil diperbarui.');
        redirect('/profile');
    }
    
    /**
     * Change password
     */
    public function changePassword(): void {
        $user = User::find(Session::userId());
        
        $currentPassword = $_POST['current_password'] ?? '';
        $newPassword = $_POST['new_password'] ?? '';
        $confirmPassword = $_POST['confirm_password'] ?? '';
        
        // Validate current password
        if (!$user->verifyPassword($currentPassword)) {
            Session::flash('error', 'Password saat ini salah.');
            redirect('/profile');
            return;
        }
        
        // Validate new password
        if (strlen($newPassword) < 8) {
            Session::flash('error', 'Password baru minimal 8 karakter.');
            redirect('/profile');
            return;
        }
        
        if ($newPassword !== $confirmPassword) {
            Session::flash('error', 'Konfirmasi password tidak cocok.');
            redirect('/profile');
            return;
        }
        
        // Set password dan simpan ke database
        $user->setPassword($newPassword);
        $user->save(); // ✅ PERBAIKAN: Simpan perubahan ke database
        
        logActivity('update', 'user', $user->id, 'user', 'Changed password');
        
        Session::flash('success', 'Password berhasil diubah.');
        redirect('/profile');
    }
    
    /**
     * Find user with access check
     */
    private function findUser(int $id): ?User {
        $user = User::find($id);
        
        if (!$user) return null;
        
        // Superadmin can access all users
        if (Session::isSuperAdmin()) {
            return $user;
        }
        
        // Check if user belongs to current company
        if (!$user->hasCompanyAccess(Session::companyId())) {
            return null;
        }
        
        return $user;
    }
    
    /**
     * Validate user data
     */
    private function validate(array $data, bool $requirePassword = true): array {
        $errors = [];
        
        if (empty($data['name'])) {
            $errors[] = 'Nama wajib diisi.';
        }
        
        if (empty($data['email'])) {
            $errors[] = 'Email wajib diisi.';
        } elseif (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'Format email tidak valid.';
        }
        
        if ($requirePassword) {
            if (empty($data['password'])) {
                $errors[] = 'Password wajib diisi.';
            } elseif (strlen($data['password']) < 8) {
                $errors[] = 'Password minimal 8 karakter.';
            }
        }
        
        return $errors;
    }
    
    /**
     * Upload avatar
     */
    private function uploadAvatar(array $file): ?string {
        $allowedTypes = ['image/jpeg', 'image/png', 'image/gif'];
        
        if (!in_array($file['type'], $allowedTypes)) {
            Session::flash('error', 'Format foto tidak didukung.');
            return null;
        }
        
        $maxSize = 1 * 1024 * 1024; // 1MB
        if ($file['size'] > $maxSize) {
            Session::flash('error', 'Ukuran foto maksimal 1MB.');
            return null;
        }
        
        $uploadDir = UPLOADS_PATH . '/avatars';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }
        
        $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
        $filename = Session::userId() . '_' . time() . '.' . $ext;
        $filepath = $uploadDir . '/' . $filename;
        
        if (move_uploaded_file($file['tmp_name'], $filepath)) {
            return 'avatars/' . $filename;
        }
        
        return null;
    }
    
    /**
     * Get page actions
     */
    private function getPageActions(): string {
        if (!Session::can('users.create')) return '';
        return '<a href="' . url('/users/create') . '" class="btn btn-primary">
            <i class="fas fa-plus"></i> Tambah Pengguna
        </a>';
    }
}