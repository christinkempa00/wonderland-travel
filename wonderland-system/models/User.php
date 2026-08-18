<?php
/**
 * ================================================================
 * TRAVEL MANAGEMENT SYSTEM - User Model
 * ================================================================
 */

require_once MODELS_PATH . '/Model.php';

class User extends Model {
    
    protected static string $table = 'users';
    protected static string $primaryKey = 'id';
    protected static string $orderBy = 'name';
    protected static string $orderDir = 'ASC';
    
    protected static array $fillable = [
        'name',
        'email',
        'phone',
        'password',
        'role',
        'avatar',
        'is_active',
        'last_login',
        'remember_token',
        'reset_token',
        'reset_expires'
    ];
    
    protected static array $hidden = [
        'password',
        'remember_token',
        'reset_token'
    ];
    
    /**
     * Find user by email
     */
    public static function findByEmail(string $email): ?self {
        return self::findBy('email', $email);
    }
    
    /**
     * Find user by remember token
     */
    public static function findByRememberToken(string $token): ?self {
        return self::findBy('remember_token', $token);
    }
    
    /**
     * Find user by reset token
     */
    public static function findByResetToken(string $token): ?self {
        $sql = "SELECT * FROM users WHERE reset_token = ? AND reset_expires > NOW() AND is_active = 1";
        $row = self::db()->fetchOne($sql, [$token]);
        
        if (!$row) {
            return null;
        }
        
        $user = new self($row);
        $user->exists = true;
        return $user;
    }
    
    /**
     * Get user's companies
     */
    public function getCompanies(): array {
        if ($this->role === ROLE_SUPERADMIN) {
            return self::db()->fetchAll(
                "SELECT * FROM companies WHERE is_active = 1 ORDER BY name"
            );
        }
        
        return self::db()->fetchAll(
            "SELECT c.* FROM companies c
             JOIN user_companies uc ON c.id = uc.company_id
             WHERE uc.user_id = ? AND uc.is_active = 1 AND c.is_active = 1
             ORDER BY c.name",
            [$this->id]
        );
    }
    
    /**
     * Check if user has access to company
     */
    public function hasCompanyAccess(int $companyId): bool {
        if ($this->role === ROLE_SUPERADMIN) {
            return true;
        }
        
        $count = self::db()->fetchColumn(
            "SELECT COUNT(*) FROM user_companies 
             WHERE user_id = ? AND company_id = ? AND is_active = 1",
            [$this->id, $companyId]
        );
        
        return $count > 0;
    }
    
    /**
     * Assign company to user
     */
    public function assignCompany(int $companyId): bool {
        // Check if already assigned
        $exists = self::db()->fetchColumn(
            "SELECT id FROM user_companies WHERE user_id = ? AND company_id = ?",
            [$this->id, $companyId]
        );
        
        if ($exists) {
            // Reactivate
            return self::db()->update(
                'user_companies',
                ['is_active' => 1],
                'id = ?',
                [$exists]
            ) > 0;
        }
        
        // Create new
        self::db()->insert('user_companies', [
            'user_id' => $this->id,
            'company_id' => $companyId,
            'is_active' => 1
        ]);
        
        return true;
    }
    
    /**
     * Remove company from user
     */
    public function removeCompany(int $companyId): bool {
        return self::db()->update(
            'user_companies',
            ['is_active' => 0],
            'user_id = ? AND company_id = ?',
            [$this->id, $companyId]
        ) > 0;
    }
    
    /**
     * Sync companies (replace all)
     */
    public function syncCompanies(array $companyIds): void {
        // Deactivate all
        self::db()->update(
            'user_companies',
            ['is_active' => 0],
            'user_id = ?',
            [$this->id]
        );
        
        // Assign new
        foreach ($companyIds as $companyId) {
            $this->assignCompany($companyId);
        }
    }
    
    /**
     * Check permission
     */
    public function can(string $permission): bool {
        if (!isset(PERMISSIONS[$this->role])) {
            return false;
        }
        
        $permissions = PERMISSIONS[$this->role];
        
        // Superadmin has all permissions
        if (in_array('*', $permissions)) {
            return true;
        }
        
        // Check exact match
        if (in_array($permission, $permissions)) {
            return true;
        }
        
        // Check wildcard
        $parts = explode('.', $permission);
        if (count($parts) === 2) {
            $wildcard = $parts[0] . '.*';
            if (in_array($wildcard, $permissions)) {
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * Is superadmin
     */
    public function isSuperAdmin(): bool {
        return $this->role === ROLE_SUPERADMIN;
    }
    
    /**
     * Is admin
     */
    public function isAdmin(): bool {
        return in_array($this->role, [ROLE_SUPERADMIN, ROLE_ADMIN]);
    }
    
    /**
     * Set password (hashed)
     */
    public function setPassword(string $password): void {
        $this->password = password_hash($password, PASSWORD_DEFAULT, ['cost' => 12]);
    }
    
    /**
     * Verify password
     */
    public function verifyPassword(string $password): bool {
        return password_verify($password, $this->password);
    }
    
    /**
     * Generate password reset token
     */
    public function generateResetToken(): string {
        $token = bin2hex(random_bytes(32));
        
        $this->update([
            'reset_token' => $token,
            'reset_expires' => date('Y-m-d H:i:s', strtotime('+1 hour'))
        ]);
        
        return $token;
    }
    
    /**
     * Clear reset token
     */
    public function clearResetToken(): void {
        $this->update([
            'reset_token' => null,
            'reset_expires' => null
        ]);
    }
    
    /**
     * Update last login
     */
    public function updateLastLogin(): void {
        $this->update([
            'last_login' => date('Y-m-d H:i:s')
        ]);
    }
    
    /**
     * Get role label
     */
    public function getRoleLabel(): string {
        return ROLES[$this->role] ?? $this->role;
    }
    
    /**
     * Get avatar URL
     */
    public function getAvatarUrl(): string {
        if ($this->avatar) {
            return uploadUrl('avatars/' . $this->avatar);
        }
        
        // Generate initial avatar
        $initial = strtoupper(substr($this->name, 0, 1));
        return "https://ui-avatars.com/api/?name={$initial}&background=3b82f6&color=fff&size=128";
    }
    
    /**
     * Get users by company
     */
    public static function getByCompany(int $companyId): array {
        $sql = "SELECT u.* FROM users u
                JOIN user_companies uc ON u.id = uc.user_id
                WHERE uc.company_id = ? AND uc.is_active = 1 AND u.is_active = 1
                ORDER BY u.name";
        
        $rows = self::db()->fetchAll($sql, [$companyId]);
        
        return array_map(function($row) {
            $user = new self($row);
            $user->exists = true;
            return $user;
        }, $rows);
    }
    
    /**
     * Get all users with company count
     */
    public static function getAllWithCompanyCount(): array {
        $sql = "SELECT u.*, 
                       (SELECT COUNT(*) FROM user_companies uc WHERE uc.user_id = u.id AND uc.is_active = 1) as company_count
                FROM users u
                WHERE u.is_active = 1
                ORDER BY u.name";
        
        return self::db()->fetchAll($sql);
    }
    
    /**
     * Search users
     */
    public static function search(string $query, ?int $companyId = null, int $limit = 10): array {
        $params = ["%{$query}%", "%{$query}%"];
        
        $sql = "SELECT DISTINCT u.* FROM users u";
        
        if ($companyId) {
            $sql .= " JOIN user_companies uc ON u.id = uc.user_id AND uc.company_id = ? AND uc.is_active = 1";
            array_unshift($params, $companyId);
        }
        
        $sql .= " WHERE u.is_active = 1 AND (u.name LIKE ? OR u.email LIKE ?)";
        $sql .= " ORDER BY u.name LIMIT {$limit}";
        
        return self::db()->fetchAll($sql, $params);
    }
    
    /**
     * Create user with companies
     */
    public static function createWithCompanies(array $data, array $companyIds): self {
        // Hash password
        if (isset($data['password'])) {
            $data['password'] = password_hash($data['password'], PASSWORD_DEFAULT, ['cost' => 12]);
        }
        
        $user = self::create($data);
        
        // Assign companies
        foreach ($companyIds as $companyId) {
            $user->assignCompany($companyId);
        }
        
        return $user;
    }
}
