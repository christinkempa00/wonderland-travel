<?php
/**
 * ================================================================
 * TRAVEL MANAGEMENT SYSTEM - Auth Controller
 * ================================================================
 */

require_once MODELS_PATH . '/User.php';
require_once MODELS_PATH . '/Company.php';

class AuthController {
    
    /**
     * Show login page
     */
    public function loginPage(): void {
        // Codebase yang sama di-deploy juga ke klien.wonderlandtrips.com
        // (portal klien) — di domain itu "/" harus ke login klien, bukan
        // login staff.
        $host = $_SERVER['HTTP_HOST'] ?? '';
        if (stripos($host, 'klien.') === 0) {
            redirect('/portal/login');
            return;
        }

        // Redirect if already logged in
        if (Session::isLoggedIn()) {
            redirect('/dashboard');
            return;
        }

        $pageTitle = 'Login';
        echo view('auth/login', compact('pageTitle'));
    }
    
    /**
     * Process login
     */
    public function login(): void {
        // Validate input
        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        $remember = isset($_POST['remember']);
        
        $errors = validate([
            'email' => $email,
            'password' => $password
        ], [
            'email' => 'required|email',
            'password' => 'required'
        ]);
        
        if (!empty($errors)) {
            flashInput($_POST);
            Session::flash('error', 'Email dan password wajib diisi.');
            redirect('/login');
            return;
        }
        
        // Attempt login
        $result = Auth::attempt($email, $password, $remember);
        
        if (!$result['success']) {
            flashInput($_POST);
            Session::flash('error', $result['message']);
            redirect('/login');
            return;
        }
        
        // Clear old input
        clearOldInput();
        
        // Redirect to intended page or dashboard
        $redirect = Session::get('redirect_after_login', '/dashboard');
        Session::remove('redirect_after_login');
        
        Session::flash('success', 'Selamat datang kembali, ' . Session::user()['name'] . '!');
        redirect($redirect);
    }
    
    /**
     * Logout
     */
    public function logout(): void {
        Auth::logout();
        Session::flash('success', 'Anda telah berhasil keluar.');
        redirect('/login');
    }
    
    /**
     * Show forgot password page
     */
    public function forgotPasswordPage(): void {
        if (Session::isLoggedIn()) {
            redirect('/dashboard');
            return;
        }
        
        $pageTitle = 'Lupa Password';
        echo view('auth/forgot-password', compact('pageTitle'));
    }
    
    /**
     * Process forgot password
     */
    public function forgotPassword(): void {
        $email = trim($_POST['email'] ?? '');
        
        $errors = validate(['email' => $email], ['email' => 'required|email']);
        
        if (!empty($errors)) {
            flashInput($_POST);
            Session::flash('error', 'Email tidak valid.');
            redirect('/forgot-password');
            return;
        }
        
        // Find user
        $user = User::findByEmail($email);
        
        // Always show success message (security - don't reveal if email exists)
        Session::flash('success', 'Jika email terdaftar, kami telah mengirimkan link reset password.');
        
        if ($user && $user->is_active) {
            // Generate token
            $token = $user->generateResetToken();
            
            // Send email or WhatsApp
            $this->sendResetLink($user, $token);
            
            // Log activity
            logActivity('password_reset_request', 'auth', $user->id, 'user', 'Password reset requested');
        }
        
        redirect('/forgot-password');
    }
    
    /**
     * Show reset password page
     */
    public function resetPasswordPage(string $token): void {
        if (Session::isLoggedIn()) {
            redirect('/dashboard');
            return;
        }
        
        // Validate token
        $user = User::findByResetToken($token);
        
        if (!$user) {
            Session::flash('error', 'Link reset password tidak valid atau sudah kadaluarsa.');
            redirect('/forgot-password');
            return;
        }
        
        $pageTitle = 'Reset Password';
        echo view('auth/reset-password', compact('pageTitle', 'token'));
    }
    
    /**
     * Process reset password
     */
    public function resetPassword(): void {
        $token = $_POST['token'] ?? '';
        $password = $_POST['password'] ?? '';
        $passwordConfirm = $_POST['password_confirmation'] ?? '';
        
        // Validate
        if (strlen($password) < PASSWORD_MIN_LENGTH) {
            Session::flash('error', 'Password minimal ' . PASSWORD_MIN_LENGTH . ' karakter.');
            redirect('/reset-password/' . $token);
            return;
        }
        
        if ($password !== $passwordConfirm) {
            Session::flash('error', 'Konfirmasi password tidak cocok.');
            redirect('/reset-password/' . $token);
            return;
        }
        
        // Find user by token
        $user = User::findByResetToken($token);
        
        if (!$user) {
            Session::flash('error', 'Link reset password tidak valid atau sudah kadaluarsa.');
            redirect('/forgot-password');
            return;
        }
        
        // Update password
        $user->setPassword($password);
        $user->update([
            'password' => $user->password,
            'reset_token' => null,
            'reset_expires' => null
        ]);
        
        // Log activity
        logActivity('password_reset', 'auth', $user->id, 'user', 'Password reset completed');
        
        Session::flash('success', 'Password berhasil direset. Silakan login dengan password baru.');
        redirect('/login');
    }
    
    /**
     * Send reset link via email/WhatsApp
     */
    private function sendResetLink(User $user, string $token): void {
        $resetUrl = url('/reset-password/' . $token);
        
        // Try to send via Dripsender if configured
        if ($user->phone) {
            $this->sendResetViaWhatsApp($user, $resetUrl);
        }
        
        // TODO: Add email sending
        // For now, just log it
        error_log("Password reset link for {$user->email}: {$resetUrl}");
    }
    
    /**
     * Send reset link via WhatsApp
     */
    private function sendResetViaWhatsApp(User $user, string $resetUrl): void {
        // Get company settings for Dripsender
        $companies = $user->getCompanies();
        
        foreach ($companies as $company) {
            $apiKey = getCompanySetting($company['id'], 'dripsender_api_key');
            $senderId = getCompanySetting($company['id'], 'dripsender_sender_id');
            
            if ($apiKey && $senderId) {
                $message = "Halo {$user->name},\n\n";
                $message .= "Anda menerima pesan ini karena ada permintaan reset password untuk akun Anda.\n\n";
                $message .= "Klik link berikut untuk reset password:\n{$resetUrl}\n\n";
                $message .= "Link ini berlaku selama 1 jam.\n\n";
                $message .= "Jika Anda tidak meminta reset password, abaikan pesan ini.";
                
                // Send via Dripsender
                $this->sendDripsender($apiKey, $senderId, formatPhoneWa($user->phone), $message);
                
                break; // Only send once
            }
        }
    }
    
    /**
     * Send message via Dripsender API
     */
    private function sendDripsender(string $apiKey, string $senderId, string $phone, string $message): bool {
        $url = 'https://api.dripsender.id/send';
        
        $data = [
            'api_key' => $apiKey,
            'sender_id' => $senderId,
            'phone' => $phone,
            'message' => $message
        ];
        
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => http_build_query($data),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 30
        ]);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        return $httpCode === 200;
    }
}
