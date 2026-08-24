<?php
/**
 * ================================================================
 * TRAVEL MANAGEMENT SYSTEM - Client Portal Controller
 * ================================================================
 * Klien login cukup pakai client_code (Session::setClient(),
 * namespace terpisah dari staff — lihat config/session.php) untuk
 * lihat tagihan tersisa & riwayat pesanan mereka sendiri.
 * ================================================================
 */

class ClientPortalController {

    /**
     * Login page
     */
    public function loginPage(): void {
        echo view('client-portal/login', ['pageTitle' => 'Login Klien']);
    }

    /**
     * Handle login
     */
    public function login(): void {
        $code = trim($_POST['client_code'] ?? '');

        if ($code === '') {
            Session::flash('error', 'Kode klien wajib diisi.');
            redirect('/portal/login');
            return;
        }

        if ($this->isLockedOut($code)) {
            $remaining = $this->getLockoutRemaining($code);
            Session::flash('error', "Terlalu banyak percobaan login. Coba lagi dalam {$remaining} menit.");
            redirect('/portal/login');
            return;
        }

        $client = db()->fetchOne(
            "SELECT * FROM clients WHERE client_code = ? AND is_active = 1 AND portal_enabled = 1",
            [$code]
        );

        if (!$client) {
            $this->recordFailedAttempt($code);
            Session::flash('error', 'Kode klien tidak ditemukan atau belum diaktifkan.');
            redirect('/portal/login');
            return;
        }

        $this->clearFailedAttempts($code);
        Session::setClient($client);

        $redirectTo = Session::get('client_redirect_after_login', '/portal');
        Session::remove('client_redirect_after_login');
        redirect($redirectTo);
    }

    /**
     * Logout
     */
    public function logout(): void {
        Session::destroyClient();
        redirect('/portal/login');
    }

    /**
     * Dashboard — tagihan tersisa & riwayat pesanan
     */
    public function dashboard(): void {
        $clientId = Session::clientId();
        $client = db()->fetchOne("SELECT * FROM clients WHERE id = ?", [$clientId]);

        $orders = db()->fetchAll(
            "SELECT id, order_number, event_name, event_date, event_end_date,
                    status, payment_status, total_final_price, paid_amount, paid_at, created_at
             FROM orders
             WHERE client_id = ?
             ORDER BY created_at DESC",
            [$clientId]
        );

        $outstanding = [];
        $totalOutstanding = 0.0;

        foreach ($orders as $order) {
            if (in_array($order['status'], ['draft', 'cancelled'])) {
                continue;
            }
            if ($order['payment_status'] === 'paid') {
                continue;
            }
            $remaining = max(0, (float) $order['total_final_price'] - (float) $order['paid_amount']);
            if ($remaining <= 0) {
                continue;
            }
            $order['remaining'] = $remaining;
            $outstanding[] = $order;
            $totalOutstanding += $remaining;
        }

        render('client-portal/dashboard', [
            'pageTitle' => 'Tagihan Saya',
            'client' => $client,
            'orders' => $orders,
            'outstanding' => $outstanding,
            'totalOutstanding' => $totalOutstanding
        ], 'client-portal/layout');
    }

    /**
     * ============================================================
     * Simple rate limiting, mirroring Auth::attempt()'s pattern but
     * keyed by client_code instead of email so it doesn't touch the
     * staff login's attempt counters.
     * ============================================================
     */
    private function isLockedOut(string $code): bool {
        $key = 'client_login_attempts_' . md5($code);
        $attempts = Session::get($key, ['count' => 0, 'time' => 0]);

        if ($attempts['count'] >= LOGIN_MAX_ATTEMPTS) {
            if (time() - $attempts['time'] < LOGIN_LOCKOUT_TIME) {
                return true;
            }
            Session::remove($key);
        }

        return false;
    }

    private function getLockoutRemaining(string $code): int {
        $key = 'client_login_attempts_' . md5($code);
        $attempts = Session::get($key, ['count' => 0, 'time' => 0]);
        $remaining = LOGIN_LOCKOUT_TIME - (time() - $attempts['time']);
        return (int) ceil($remaining / 60);
    }

    private function recordFailedAttempt(string $code): void {
        $key = 'client_login_attempts_' . md5($code);
        $attempts = Session::get($key, ['count' => 0, 'time' => time()]);
        $attempts['count']++;
        $attempts['time'] = time();
        Session::set($key, $attempts);
    }

    private function clearFailedAttempts(string $code): void {
        Session::remove('client_login_attempts_' . md5($code));
    }
}
