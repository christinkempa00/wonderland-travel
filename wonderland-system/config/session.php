<?php
/**
 * ================================================================
 * TRAVEL MANAGEMENT SYSTEM - Session Configuration
 * ================================================================
 */

// Prevent direct access
if (!defined('BASE_PATH')) {
    exit('No direct script access allowed');
}

/**
 * Session Class
 * Handles session management with security features
 */
class Session {
    
    private static $started = false;
    
    /**
     * Start Session
     */
    public static function start(): void {
        if (self::$started) {
            return;
        }
        
        // Set session configuration
        ini_set('session.name', SESSION_NAME);
        ini_set('session.gc_maxlifetime', SESSION_LIFETIME);
        ini_set('session.cookie_lifetime', SESSION_LIFETIME);
        ini_set('session.cookie_path', SESSION_PATH);
        ini_set('session.cookie_secure', SESSION_SECURE ? '1' : '0');
        ini_set('session.cookie_httponly', SESSION_HTTPONLY ? '1' : '0');
        ini_set('session.cookie_samesite', 'Lax');
        ini_set('session.use_strict_mode', '1');
        ini_set('session.use_only_cookies', '1');
        
        // Start session
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        self::$started = true;
        
        // Regenerate session ID periodically
        self::regenerateIfNeeded();
        
        // Check session validity
        self::validateSession();
    }
    
    /**
     * Regenerate Session ID if needed
     */
    private static function regenerateIfNeeded(): void {
        $regenerateInterval = 300; // 5 minutes
        
        if (!isset($_SESSION['_last_regenerate'])) {
            $_SESSION['_last_regenerate'] = time();
            return;
        }
        
        if (time() - $_SESSION['_last_regenerate'] > $regenerateInterval) {
            session_regenerate_id(true);
            $_SESSION['_last_regenerate'] = time();
        }
    }
    
    /**
     * Validate Session
     */
    private static function validateSession(): void {
        // Check user agent
        $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? '';
        
        if (!isset($_SESSION['_user_agent'])) {
            $_SESSION['_user_agent'] = md5($userAgent);
        } elseif ($_SESSION['_user_agent'] !== md5($userAgent)) {
            // Possible session hijacking
            self::destroy();
            return;
        }
        
        // Check IP (optional - can cause issues with mobile users)
        // Uncomment if needed for extra security
        /*
        $ip = $_SERVER['REMOTE_ADDR'] ?? '';
        if (!isset($_SESSION['_ip'])) {
            $_SESSION['_ip'] = $ip;
        } elseif ($_SESSION['_ip'] !== $ip) {
            self::destroy();
            return;
        }
        */
    }
    
    /**
     * Set Session Value
     * @param string $key
     * @param mixed $value
     */
    public static function set(string $key, $value): void {
        $_SESSION[$key] = $value;
    }
    
    /**
     * Get Session Value
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    public static function get(string $key, $default = null) {
        return $_SESSION[$key] ?? $default;
    }
    
    /**
     * Check if Session Key Exists
     * @param string $key
     * @return bool
     */
    public static function has(string $key): bool {
        return isset($_SESSION[$key]);
    }
    
    /**
     * Remove Session Key
     * @param string $key
     */
    public static function remove(string $key): void {
        unset($_SESSION[$key]);
    }
    
    /**
     * Get All Session Data
     * @return array
     */
    public static function all(): array {
        return $_SESSION ?? [];
    }
    
    /**
     * Clear All Session Data
     */
    public static function clear(): void {
        $_SESSION = [];
    }
    
    /**
     * Destroy Session
     */
    public static function destroy(): void {
        $_SESSION = [];
        
        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(
                session_name(),
                '',
                time() - 42000,
                $params['path'],
                $params['domain'],
                $params['secure'],
                $params['httponly']
            );
        }
        
        session_destroy();
        self::$started = false;
    }
    
    /**
     * Set Flash Message
     * @param string $type (success, error, warning, info)
     * @param string $message
     */
    public static function flash(string $type, string $message): void {
        $_SESSION['_flash'][$type][] = $message;
    }
    
    /**
     * Get Flash Messages
     * @param string|null $type
     * @return array
     */
    public static function getFlash(?string $type = null): array {
        if ($type) {
            $messages = $_SESSION['_flash'][$type] ?? [];
            unset($_SESSION['_flash'][$type]);
            return $messages;
        }
        
        $messages = $_SESSION['_flash'] ?? [];
        unset($_SESSION['_flash']);
        return $messages;
    }
    
    /**
     * Check if Flash Messages Exist
     * @param string|null $type
     * @return bool
     */
    public static function hasFlash(?string $type = null): bool {
        if ($type) {
            return !empty($_SESSION['_flash'][$type]);
        }
        return !empty($_SESSION['_flash']);
    }
    
    /**
     * Generate CSRF Token
     * @return string
     */
    public static function generateCsrfToken(): string {
        $token = bin2hex(random_bytes(32));
        $_SESSION[CSRF_TOKEN_NAME] = $token;
        $_SESSION['_csrf_time'] = time();
        return $token;
    }
    
    /**
     * Get CSRF Token
     * @return string
     */
    public static function getCsrfToken(): string {
        if (!isset($_SESSION[CSRF_TOKEN_NAME])) {
            return self::generateCsrfToken();
        }
        
        // Regenerate if token is old (1 hour)
        if (time() - ($_SESSION['_csrf_time'] ?? 0) > 3600) {
            return self::generateCsrfToken();
        }
        
        return $_SESSION[CSRF_TOKEN_NAME];
    }
    
    /**
     * Verify CSRF Token
     * @param string $token
     * @return bool
     */
    public static function verifyCsrfToken(string $token): bool {
        if (!isset($_SESSION[CSRF_TOKEN_NAME])) {
            return false;
        }
        
        return hash_equals($_SESSION[CSRF_TOKEN_NAME], $token);
    }
    
    /**
     * Set User Login Data
     * @param array $user
     * @param int|null $companyId
     */
    public static function setUser(array $user, ?int $companyId = null): void {
        $_SESSION['user'] = [
            'id' => $user['id'],
            'name' => $user['name'],
            'email' => $user['email'],
            'role' => $user['role'],
            'avatar' => $user['avatar'] ?? null,
            'is_superadmin' => $user['role'] === ROLE_SUPERADMIN
        ];
        
        $_SESSION['company_id'] = $companyId;
        $_SESSION['logged_in'] = true;
        $_SESSION['login_time'] = time();
        
        // Regenerate session ID on login
        session_regenerate_id(true);
    }
    
    /**
     * Get Current User
     * @return array|null
     */
    public static function user(): ?array {
        return $_SESSION['user'] ?? null;
    }
    
    /**
     * Get Current User ID
     * @return int|null
     */
    public static function userId(): ?int {
        return $_SESSION['user']['id'] ?? null;
    }
    
    /**
     * Get Current User Role
     * @return string|null
     */
    public static function userRole(): ?string {
        return $_SESSION['user']['role'] ?? null;
    }
    
    /**
     * Check if User is Super Admin
     * @return bool
     */
    public static function isSuperAdmin(): bool {
        return ($_SESSION['user']['is_superadmin'] ?? false) === true;
    }
    
    /**
     * Get Current Company ID
     * @return int|null
     */
    public static function companyId(): ?int {
        return $_SESSION['company_id'] ?? null;
    }
    
    /**
     * Set Current Company ID
     * @param int $companyId
     */
    public static function setCompanyId(int $companyId): void {
        $_SESSION['company_id'] = $companyId;
    }
    
    /**
     * Check if User is Logged In
     * @return bool
     */
    public static function isLoggedIn(): bool {
        if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
            return false;
        }
        
        // Check session timeout
        $loginTime = $_SESSION['login_time'] ?? 0;
        if (time() - $loginTime > SESSION_LIFETIME) {
            self::destroy();
            return false;
        }
        
        return true;
    }
    
    /**
     * ============================================================
     * Client Portal Session — completely separate namespace
     * ($_SESSION['client']/'client_logged_in') from staff session
     * above, so a staff login and a client login never collide.
     * ============================================================
     */

    /**
     * Set Logged-In Client
     * @param array $client
     */
    public static function setClient(array $client): void {
        $_SESSION['client'] = [
            'id' => (int) $client['id'],
            'company_id' => (int) $client['company_id'],
            'name' => $client['name'],
            'client_code' => $client['client_code']
        ];
        $_SESSION['client_logged_in'] = true;
        $_SESSION['client_login_time'] = time();

        session_regenerate_id(true);
    }

    /**
     * Check if Client is Logged In
     * @return bool
     */
    public static function isClientLoggedIn(): bool {
        if (!isset($_SESSION['client_logged_in']) || $_SESSION['client_logged_in'] !== true) {
            return false;
        }

        $loginTime = $_SESSION['client_login_time'] ?? 0;
        if (time() - $loginTime > SESSION_LIFETIME) {
            self::destroyClient();
            return false;
        }

        return true;
    }

    /**
     * Get Current Client
     * @return array|null
     */
    public static function client(): ?array {
        return $_SESSION['client'] ?? null;
    }

    /**
     * Get Current Client ID
     * @return int|null
     */
    public static function clientId(): ?int {
        return $_SESSION['client']['id'] ?? null;
    }

    /**
     * Destroy Client Session
     */
    public static function destroyClient(): void {
        unset($_SESSION['client'], $_SESSION['client_logged_in'], $_SESSION['client_login_time']);
    }

    /**
     * Check User Permission
     * @param string $permission
     * @return bool
     */
    public static function can(string $permission): bool {
        $role = self::userRole();
        
        if (!$role || !isset(PERMISSIONS[$role])) {
            return false;
        }
        
        $permissions = PERMISSIONS[$role];
        
        // Super admin has all permissions
        if (in_array('*', $permissions)) {
            return true;
        }
        
        // Check exact match
        if (in_array($permission, $permissions)) {
            return true;
        }
        
        // Check wildcard (e.g., 'orders.*')
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
     * Require Permission (redirect if not allowed)
     * @param string $permission
     */
    public static function requirePermission(string $permission): void {
        if (!self::can($permission)) {
            Session::flash('error', 'Anda tidak memiliki akses ke halaman ini.');
            redirect('/dashboard');
            exit;
        }
    }
    
    /**
     * Require Login (redirect if not logged in)
     * @param string|null $redirectTo
     */
    public static function requireLogin(?string $redirectTo = null): void {
        if (!self::isLoggedIn()) {
            $redirectTo = $redirectTo ?? $_SERVER['REQUEST_URI'];
            Session::set('redirect_after_login', $redirectTo);
            redirect('/login');
            exit;
        }
    }
    
    /**
     * Get Companies User Has Access To
     * @return array
     */
    public static function getUserCompanies(): array {
        return $_SESSION['user_companies'] ?? [];
    }
    
    /**
     * Set Companies User Has Access To
     * @param array $companies
     */
    public static function setUserCompanies(array $companies): void {
        $_SESSION['user_companies'] = $companies;
    }
}

/**
 * Auth Helper Class
 * Additional authentication utilities
 */
class Auth {
    
    /**
     * Attempt Login
     * @param string $email
     * @param string $password
     * @param bool $remember
     * @return array [success, message, user]
     */
    public static function attempt(string $email, string $password, bool $remember = false): array {
        // Check rate limiting
        if (self::isLockedOut($email)) {
            $remaining = self::getLockoutRemaining($email);
            return [
                'success' => false,
                'message' => "Terlalu banyak percobaan login. Coba lagi dalam {$remaining} menit."
            ];
        }
        
        // Find user
        $user = db()->fetchOne(
            "SELECT * FROM users WHERE email = ? AND is_active = 1",
            [$email]
        );
        
        if (!$user) {
            self::recordFailedAttempt($email);
            return [
                'success' => false,
                'message' => 'Email atau password salah.'
            ];
        }
        
        // Verify password
        if (!password_verify($password, $user['password'])) {
            self::recordFailedAttempt($email);
            return [
                'success' => false,
                'message' => 'Email atau password salah.'
            ];
        }
        
        // Clear failed attempts
        self::clearFailedAttempts($email);
        
        // Get user companies
        $companies = self::getUserCompanies($user['id'], $user['role']);
        
        // Set session
        Session::setUser($user, $companies[0]['id'] ?? null);
        Session::setUserCompanies($companies);
        
        // Update last login
        db()->update('users', [
            'last_login' => date('Y-m-d H:i:s')
        ], 'id = ?', [$user['id']]);
        
        // Handle remember me
        if ($remember) {
            self::setRememberToken($user['id']);
        }
        
        // Log activity
        logActivity('login', 'auth', $user['id'], null, 'User logged in');
        
        return [
            'success' => true,
            'message' => 'Login berhasil.',
            'user' => $user
        ];
    }
    
    /**
     * Get User's Companies
     * @param int $userId
     * @param string $role
     * @return array
     */
    public static function getUserCompanies(int $userId, string $role): array {
        if ($role === ROLE_SUPERADMIN) {
            return db()->fetchAll("SELECT * FROM companies WHERE is_active = 1 ORDER BY name");
        }
        
        return db()->fetchAll(
            "SELECT c.* FROM companies c
             JOIN user_companies uc ON c.id = uc.company_id
             WHERE uc.user_id = ? AND uc.is_active = 1 AND c.is_active = 1
             ORDER BY c.name",
            [$userId]
        );
    }
    
    /**
     * Logout
     */
    public static function logout(): void {
        $userId = Session::userId();
        
        if ($userId) {
            // Clear remember token
            db()->update('users', ['remember_token' => null], 'id = ?', [$userId]);
            
            // Log activity
            logActivity('logout', 'auth', $userId, null, 'User logged out');
        }
        
        Session::destroy();
    }
    
    /**
     * Check if Locked Out
     * @param string $email
     * @return bool
     */
    private static function isLockedOut(string $email): bool {
        $key = 'login_attempts_' . md5($email);
        $attempts = Session::get($key, ['count' => 0, 'time' => 0]);
        
        if ($attempts['count'] >= LOGIN_MAX_ATTEMPTS) {
            if (time() - $attempts['time'] < LOGIN_LOCKOUT_TIME) {
                return true;
            }
            // Lockout expired, reset
            Session::remove($key);
        }
        
        return false;
    }
    
    /**
     * Get Lockout Remaining Time
     * @param string $email
     * @return int Minutes
     */
    private static function getLockoutRemaining(string $email): int {
        $key = 'login_attempts_' . md5($email);
        $attempts = Session::get($key, ['count' => 0, 'time' => 0]);
        $remaining = LOGIN_LOCKOUT_TIME - (time() - $attempts['time']);
        return ceil($remaining / 60);
    }
    
    /**
     * Record Failed Login Attempt
     * @param string $email
     */
    private static function recordFailedAttempt(string $email): void {
        $key = 'login_attempts_' . md5($email);
        $attempts = Session::get($key, ['count' => 0, 'time' => time()]);
        $attempts['count']++;
        $attempts['time'] = time();
        Session::set($key, $attempts);
    }
    
    /**
     * Clear Failed Attempts
     * @param string $email
     */
    private static function clearFailedAttempts(string $email): void {
        $key = 'login_attempts_' . md5($email);
        Session::remove($key);
    }
    
    /**
     * Set Remember Token
     * @param int $userId
     */
    private static function setRememberToken(int $userId): void {
        $token = bin2hex(random_bytes(32));
        $expires = time() + (30 * 24 * 60 * 60); // 30 days
        
        db()->update('users', ['remember_token' => $token], 'id = ?', [$userId]);
        
        setcookie(
            'remember_token',
            $token,
            $expires,
            '/',
            '',
            true,
            true
        );
    }
    
    /**
     * Check Remember Token
     * @return bool
     */
    public static function checkRememberToken(): bool {
        if (Session::isLoggedIn()) {
            return true;
        }
        
        $token = $_COOKIE['remember_token'] ?? null;
        
        if (!$token) {
            return false;
        }
        
        $user = db()->fetchOne(
            "SELECT * FROM users WHERE remember_token = ? AND is_active = 1",
            [$token]
        );
        
        if (!$user) {
            return false;
        }
        
        // Set session
        $companies = self::getUserCompanies($user['id'], $user['role']);
        Session::setUser($user, $companies[0]['id'] ?? null);
        Session::setUserCompanies($companies);
        
        // Refresh remember token
        self::setRememberToken($user['id']);
        
        return true;
    }
    
    /**
     * Hash Password
     * @param string $password
     * @return string
     */
    public static function hashPassword(string $password): string {
        return password_hash($password, PASSWORD_DEFAULT, ['cost' => 12]);
    }
    
    /**
     * Verify Password
     * @param string $password
     * @param string $hash
     * @return bool
     */
    public static function verifyPassword(string $password, string $hash): bool {
        return password_verify($password, $hash);
    }
}
