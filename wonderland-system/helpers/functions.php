<?php
/**
 * ================================================================
 * TRAVEL MANAGEMENT SYSTEM - Helper Functions
 * ================================================================
 */

// Prevent direct access
if (!defined('BASE_PATH')) {
    exit('No direct script access allowed');
}

// ================================================================
// URL & REDIRECT HELPERS
// ================================================================

/**
 * Generate URL
 * @param string $path
 * @param array $params
 * @return string
 */
function url(string $path = '', array $params = []): string {
    $url = BASE_URL . '/' . ltrim($path, '/');
    
    if (!empty($params)) {
        $url .= '?' . http_build_query($params);
    }
    
    return $url;
}

/**
 * Get Asset URL
 * @param string $path
 * @return string
 */
function asset(string $path): string {
    return ASSETS_URL . '/' . ltrim($path, '/');
}

/**
 * Get Upload URL
 * @param string $path
 * @return string
 */
function uploadUrl(string $path): string {
    return UPLOADS_URL . '/' . ltrim($path, '/');
}

/**
 * Redirect to URL
 * @param string $url
 * @param int $code
 */
function redirect(string $url, int $code = 302): void {
    if (strpos($url, 'http') !== 0) {
        $url = url($url);
    }
    
    header("Location: {$url}", true, $code);
    exit;
}

/**
 * Redirect Back
 * @param string $fallback
 */
function back(string $fallback = '/dashboard'): void {
    $referer = $_SERVER['HTTP_REFERER'] ?? null;
    redirect($referer ?? $fallback);
}

/**
 * Check if AJAX Request
 * @return bool
 */
function isAjax(): bool {
    return !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && 
           strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
}

/**
 * Get Current URL
 * @return string
 */
function currentUrl(): string {
    return BASE_URL . $_SERVER['REQUEST_URI'];
}

// ================================================================
// RESPONSE HELPERS
// ================================================================

/**
 * Send JSON Response
 * @param mixed $data
 * @param int $code
 */
function jsonResponse($data, int $code = 200): void {
    http_response_code($code);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

/**
 * Send Success JSON
 * @param mixed $data
 * @param string $message
 */
function jsonSuccess($data = null, string $message = 'Success'): void {
    jsonResponse([
        'success' => true,
        'message' => $message,
        'data' => $data
    ]);
}

/**
 * Send Error JSON
 * @param string $message
 * @param int $code
 * @param mixed $errors
 */
function jsonError(string $message, int $code = 400, $errors = null): void {
    $response = [
        'success' => false,
        'message' => $message
    ];
    
    if ($errors) {
        $response['errors'] = $errors;
    }
    
    jsonResponse($response, $code);
}

// ================================================================
// VIEW HELPERS
// ================================================================

/**
 * Render View
 * @param string $view
 * @param array $data
 * @return string
 */
function view(string $view, array $data = []): string {
    $viewPath = VIEWS_PATH . '/' . str_replace('.', '/', $view) . '.php';
    
    if (!file_exists($viewPath)) {
        throw new Exception("View not found: {$view}");
    }
    
    extract($data);
    
    ob_start();
    include $viewPath;
    return ob_get_clean();
}

/**
 * Render View with Layout
 * @param string $view
 * @param array $data
 * @param string $layout
 */
function render(string $view, array $data = [], string $layout = 'layouts/app'): void {
    $content = view($view, $data);
    $data['content'] = $content;
    echo view($layout, $data);
}

/**
 * Include Partial View
 * @param string $partial
 * @param array $data
 */
function partial(string $partial, array $data = []): void {
    echo view($partial, $data);
}

/**
 * Escape HTML
 * @param string|null $string
 * @return string
 */
function e(?string $string): string {
    return htmlspecialchars($string ?? '', ENT_QUOTES, 'UTF-8');
}

/**
 * Generate CSRF Field
 * @return string
 */
function csrfField(): string {
    $token = Session::getCsrfToken();
    return '<input type="hidden" name="_token" value="' . e($token) . '">';
}

/**
 * Generate Method Field
 * @param string $method
 * @return string
 */
function methodField(string $method): string {
    return '<input type="hidden" name="_method" value="' . e($method) . '">';
}

// ================================================================
// FORMAT HELPERS
// ================================================================

/**
 * Format Currency (Rupiah)
 * @param float|int $amount
 * @param bool $withSymbol
 * @return string
 */
function formatRupiah($amount, bool $withSymbol = true): string {
    $formatted = number_format(
        (float) $amount,
        defined('CURRENCY_DECIMAL') ? CURRENCY_DECIMAL : 0,
        defined('CURRENCY_DECIMAL_SEP') ? CURRENCY_DECIMAL_SEP : ',',
        defined('CURRENCY_THOUSAND_SEP') ? CURRENCY_THOUSAND_SEP : '.'
    );
    
    // FIX: Use correct symbol (workaround for corrupted constant)
    $symbol = 'Rp';
    if (defined('CURRENCY_SYMBOL') && is_string(CURRENCY_SYMBOL) && CURRENCY_SYMBOL !== '262145') {
        $symbol = CURRENCY_SYMBOL;
    } elseif (isset($GLOBALS['CURRENCY_SYMBOL_VALUE'])) {
        $symbol = $GLOBALS['CURRENCY_SYMBOL_VALUE'];
    }
    
    return $withSymbol ? $symbol . ' ' . $formatted : $formatted;
}

/**
 * Parse Rupiah String to Number
 * @param string $value
 * @return float
 */
function parseRupiah(string $value): float {
    $value = preg_replace('/[^0-9,\-]/', '', $value);
    $value = str_replace(',', '.', $value);
    return (float) $value;
}

/**
 * Format Date
 * @param string|DateTime $date
 * @param string $format
 * @return string
 */
function formatDate($date, string $format = null): string {
    if (empty($date)) {
        return '-';
    }
    
    $format = $format ?? DATE_FORMAT;
    
    if (is_string($date)) {
        $date = new DateTime($date);
    }
    
    return $date->format($format);
}

/**
 * Format Date Indonesian
 * @param string|DateTime $date
 * @param bool $withDay
 * @return string
 */
function formatDateIndo($date, bool $withDay = false): string {
    if (empty($date)) {
        return '-';
    }
    
    if (is_string($date)) {
        $date = new DateTime($date);
    }
    
    $day = INDONESIAN_DAYS[(int) $date->format('w')];
    $d = $date->format('j');
    $month = INDONESIAN_MONTHS[(int) $date->format('n')];
    $year = $date->format('Y');
    
    if ($withDay) {
        return "{$day}, {$d} {$month} {$year}";
    }
    
    return "{$d} {$month} {$year}";
}

/**
 * Format DateTime
 * @param string|DateTime $datetime
 * @param string $format
 * @return string
 */
function formatDateTime($datetime, string $format = null): string {
    if (empty($datetime)) {
        return '-';
    }
    
    $format = $format ?? DATETIME_FORMAT;
    
    if (is_string($datetime)) {
        $datetime = new DateTime($datetime);
    }
    
    return $datetime->format($format);
}

/**
 * Convert Number to Indonesian Words (Terbilang)
 * @param float|int $number
 * @return string
 */
function terbilang($number): string {
    $number = abs((float) $number);
    $words = ['', 'Satu', 'Dua', 'Tiga', 'Empat', 'Lima', 'Enam', 'Tujuh', 'Delapan', 'Sembilan', 'Sepuluh', 'Sebelas'];
    
    if ($number < 12) {
        return $words[(int) $number];
    } elseif ($number < 20) {
        return terbilang($number - 10) . ' Belas';
    } elseif ($number < 100) {
        return terbilang(floor($number / 10)) . ' Puluh ' . terbilang($number % 10);
    } elseif ($number < 200) {
        return 'Seratus ' . terbilang($number - 100);
    } elseif ($number < 1000) {
        return terbilang(floor($number / 100)) . ' Ratus ' . terbilang($number % 100);
    } elseif ($number < 2000) {
        return 'Seribu ' . terbilang($number - 1000);
    } elseif ($number < 1000000) {
        return terbilang(floor($number / 1000)) . ' Ribu ' . terbilang($number % 1000);
    } elseif ($number < 1000000000) {
        return terbilang(floor($number / 1000000)) . ' Juta ' . terbilang($number % 1000000);
    } elseif ($number < 1000000000000) {
        return terbilang(floor($number / 1000000000)) . ' Milyar ' . terbilang($number % 1000000000);
    } else {
        return terbilang(floor($number / 1000000000000)) . ' Trilyun ' . terbilang($number % 1000000000000);
    }
}

/**
 * Get Month Roman Numeral
 * @param int $month
 * @return string
 */
function monthRoman(int $month): string {
    return ROMAN_MONTHS[$month] ?? '';
}

/**
 * Get Indonesian Month Name
 * @param int $month
 * @return string
 */
function monthIndo(int $month): string {
    return INDONESIAN_MONTHS[$month] ?? '';
}

/**
 * Format Percentage
 * @param float $value
 * @param int $decimals
 * @return string
 */
function formatPercent(float $value, int $decimals = 2): string {
    return number_format($value, $decimals, ',', '.') . '%';
}

/**
 * Format Phone Number
 * @param string $phone
 * @return string
 */
function formatPhone(string $phone): string {
    $phone = preg_replace('/[^0-9]/', '', $phone);
    
    if (substr($phone, 0, 2) === '62') {
        $phone = '0' . substr($phone, 2);
    }
    
    return $phone;
}

/**
 * Format Phone for WhatsApp
 * @param string $phone
 * @return string
 */
function formatPhoneWa(string $phone): string {
    $phone = preg_replace('/[^0-9]/', '', $phone);
    
    if (substr($phone, 0, 1) === '0') {
        $phone = '62' . substr($phone, 1);
    }
    
    return $phone;
}

// ================================================================
// STRING HELPERS
// ================================================================

/**
 * Generate Slug
 * @param string $string
 * @return string
 */
function slug(string $string): string {
    $string = strtolower($string);
    $string = preg_replace('/[^a-z0-9\s-]/', '', $string);
    $string = preg_replace('/[\s-]+/', '-', $string);
    return trim($string, '-');
}

/**
 * Truncate String
 * @param string $string
 * @param int $length
 * @param string $suffix
 * @return string
 */
function truncate(string $string, int $length = 100, string $suffix = '...'): string {
    if (mb_strlen($string) <= $length) {
        return $string;
    }
    
    return mb_substr($string, 0, $length) . $suffix;
}

/**
 * Generate Random String
 * @param int $length
 * @return string
 */
function randomString(int $length = 16): string {
    return bin2hex(random_bytes($length / 2));
}

/**
 * Generate UUID
 * @return string
 */
function uuid(): string {
    $data = random_bytes(16);
    $data[6] = chr(ord($data[6]) & 0x0f | 0x40);
    $data[8] = chr(ord($data[8]) & 0x3f | 0x80);
    return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
}

// ================================================================
// ARRAY HELPERS
// ================================================================

/**
 * Get Array Value with Dot Notation
 * @param array $array
 * @param string $key
 * @param mixed $default
 * @return mixed
 */
function arrayGet(array $array, string $key, $default = null) {
    if (isset($array[$key])) {
        return $array[$key];
    }
    
    foreach (explode('.', $key) as $segment) {
        if (!is_array($array) || !array_key_exists($segment, $array)) {
            return $default;
        }
        $array = $array[$segment];
    }
    
    return $array;
}

/**
 * Pluck Values from Array
 * @param array $array
 * @param string $key
 * @param string|null $keyBy
 * @return array
 */
function arrayPluck(array $array, string $key, ?string $keyBy = null): array {
    $result = [];
    
    foreach ($array as $item) {
        $value = is_object($item) ? $item->$key : $item[$key];
        
        if ($keyBy) {
            $keyValue = is_object($item) ? $item->$keyBy : $item[$keyBy];
            $result[$keyValue] = $value;
        } else {
            $result[] = $value;
        }
    }
    
    return $result;
}

// ================================================================
// INPUT HELPERS
// ================================================================

/**
 * Get Input Value (POST/GET)
 * @param string $key
 * @param mixed $default
 * @return mixed
 */
function input(string $key, $default = null) {
    return $_POST[$key] ?? $_GET[$key] ?? $default;
}

/**
 * Get All Input
 * @return array
 */
function allInput(): array {
    return array_merge($_GET, $_POST);
}

/**
 * Get Old Input (from session)
 * @param string $key
 * @param mixed $default
 * @return mixed
 */
function old(string $key, $default = '') {
    return Session::get('_old_input')[$key] ?? $default;
}

/**
 * Flash Input to Session
 * @param array $input
 */
function flashInput(array $input): void {
    // Remove sensitive fields
    unset($input['password'], $input['password_confirmation'], $input['_token']);
    Session::set('_old_input', $input);
}

/**
 * Clear Old Input
 */
function clearOldInput(): void {
    Session::remove('_old_input');
}

// ================================================================
// VALIDATION HELPERS
// ================================================================

/**
 * Simple Validation
 * @param array $data
 * @param array $rules
 * @return array Errors
 */
function validate(array $data, array $rules): array {
    $errors = [];
    
    foreach ($rules as $field => $ruleString) {
        $fieldRules = explode('|', $ruleString);
        $value = $data[$field] ?? null;
        $label = ucfirst(str_replace('_', ' ', $field));
        
        foreach ($fieldRules as $rule) {
            $params = [];
            
            if (strpos($rule, ':') !== false) {
                list($rule, $paramStr) = explode(':', $rule, 2);
                $params = explode(',', $paramStr);
            }
            
            switch ($rule) {
                case 'required':
                    if (empty($value) && $value !== '0') {
                        $errors[$field][] = "{$label} wajib diisi.";
                    }
                    break;
                    
                case 'email':
                    if (!empty($value) && !filter_var($value, FILTER_VALIDATE_EMAIL)) {
                        $errors[$field][] = "{$label} harus berupa email yang valid.";
                    }
                    break;
                    
                case 'min':
                    $min = (int) $params[0];
                    if (strlen($value) < $min) {
                        $errors[$field][] = "{$label} minimal {$min} karakter.";
                    }
                    break;
                    
                case 'max':
                    $max = (int) $params[0];
                    if (strlen($value) > $max) {
                        $errors[$field][] = "{$label} maksimal {$max} karakter.";
                    }
                    break;
                    
                case 'numeric':
                    if (!empty($value) && !is_numeric($value)) {
                        $errors[$field][] = "{$label} harus berupa angka.";
                    }
                    break;
                    
                case 'confirmed':
                    $confirmField = $field . '_confirmation';
                    if ($value !== ($data[$confirmField] ?? null)) {
                        $errors[$field][] = "Konfirmasi {$label} tidak cocok.";
                    }
                    break;
                    
                case 'unique':
                    $table = $params[0];
                    $column = $params[1] ?? $field;
                    $exceptId = $params[2] ?? null;
                    
                    $sql = "SELECT COUNT(*) FROM {$table} WHERE {$column} = ?";
                    $sqlParams = [$value];
                    
                    if ($exceptId) {
                        $sql .= " AND id != ?";
                        $sqlParams[] = $exceptId;
                    }
                    
                    if (db()->fetchColumn($sql, $sqlParams) > 0) {
                        $errors[$field][] = "{$label} sudah digunakan.";
                    }
                    break;
            }
        }
    }
    
    return $errors;
}

// ================================================================
// FILE HELPERS
// ================================================================

/**
 * Upload File
 * @param array $file $_FILES element
 * @param string $destination
 * @param array $allowedTypes
 * @param int $maxSize
 * @return array [success, path/error]
 */
function uploadFile(array $file, string $destination, array $allowedTypes = [], int $maxSize = 0): array {
    if ($file['error'] !== UPLOAD_ERR_OK) {
        $errors = [
            UPLOAD_ERR_INI_SIZE => 'File terlalu besar.',
            UPLOAD_ERR_FORM_SIZE => 'File terlalu besar.',
            UPLOAD_ERR_PARTIAL => 'File tidak terupload sempurna.',
            UPLOAD_ERR_NO_FILE => 'Tidak ada file yang diupload.',
            UPLOAD_ERR_NO_TMP_DIR => 'Folder temporary tidak ditemukan.',
            UPLOAD_ERR_CANT_WRITE => 'Gagal menulis file.',
        ];
        
        return [false, $errors[$file['error']] ?? 'Upload gagal.'];
    }
    
    // Check file type
    if (!empty($allowedTypes)) {
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mimeType = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);
        
        if (!in_array($mimeType, $allowedTypes)) {
            return [false, 'Tipe file tidak diizinkan.'];
        }
    }
    
    // Check file size
    $maxSize = $maxSize ?: MAX_UPLOAD_SIZE;
    if ($file['size'] > $maxSize) {
        $maxMb = round($maxSize / 1024 / 1024, 1);
        return [false, "Ukuran file maksimal {$maxMb}MB."];
    }
    
    // Generate unique filename
    $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
    $filename = randomString(16) . '.' . strtolower($ext);
    $path = rtrim($destination, '/') . '/' . $filename;
    
    // Create directory if not exists
    if (!is_dir($destination)) {
        mkdir($destination, 0755, true);
    }
    
    // Move file
    if (!move_uploaded_file($file['tmp_name'], $path)) {
        return [false, 'Gagal menyimpan file.'];
    }
    
    return [true, $filename];
}

/**
 * Delete File
 * @param string $path
 * @return bool
 */
function deleteFile(string $path): bool {
    if (file_exists($path)) {
        return unlink($path);
    }
    return false;
}

// ================================================================
// LOGGING HELPERS
// ================================================================

/**
 * Log Activity
 * @param string $action
 * @param string $module
 * @param int|null $recordId
 * @param string|null $recordType
 * @param string|null $description
 * @param array|null $oldValues
 * @param array|null $newValues
 */
function logActivity(
    string $action,
    string $module,
    ?int $recordId = null,
    ?string $recordType = null,
    ?string $description = null,
    ?array $oldValues = null,
    ?array $newValues = null
): void {
    try {
        db()->insert('activity_logs', [
            'user_id' => Session::userId(),
            'company_id' => Session::companyId(),
            'action' => $action,
            'module' => $module,
            'record_id' => $recordId,
            'record_type' => $recordType,
            'description' => $description,
            'old_values' => $oldValues ? json_encode($oldValues) : null,
            'new_values' => $newValues ? json_encode($newValues) : null,
            'ip_address' => $_SERVER['REMOTE_ADDR'] ?? null,
            'user_agent' => substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 255)
        ]);
    } catch (Exception $e) {
        error_log("Failed to log activity: " . $e->getMessage());
    }
}

/**
 * Log Error
 * @param string $message
 * @param array $context
 */
function logError(string $message, array $context = []): void {
    $logFile = LOGS_PATH . '/error_' . date('Y-m-d') . '.log';
    $timestamp = date('Y-m-d H:i:s');
    $contextStr = !empty($context) ? json_encode($context) : '';
    
    $entry = "[{$timestamp}] {$message} {$contextStr}\n";
    
    error_log($entry, 3, $logFile);
}

// ================================================================
// PAYMENT HELPERS
// ================================================================

/**
 * Record a payment against an order.
 * Tries the full accounting-journal path first; falls back to a plain
 * order_payments record (no journal/bank_cash side effects) if accounting
 * isn't configured or its tables aren't available.
 * Used by OrderController::storePayment().
 *
 * @return array{success:bool, payment_id?:int, message:string}
 */
function recordPayment(
    int $companyId,
    int $orderId,
    float $amount,
    string $paymentDate,
    string $paymentMethod,
    ?int $bankCashId,
    ?string $reference,
    ?string $notes,
    ?string $proofImage
): array {
    if (function_exists('createOrderPaymentJournal')) {
        $order = db()->fetchOne("SELECT * FROM orders WHERE id = ?", [$orderId]);
        if ($order) {
            if (empty($order['invoice_journal_id']) && function_exists('createOrderInvoiceJournal')) {
                try {
                    $invoiceJournalId = createOrderInvoiceJournal($order);
                    if ($invoiceJournalId) {
                        $order['invoice_journal_id'] = $invoiceJournalId;
                    }
                } catch (Exception $e) {}
            }

            $result = createOrderPaymentJournal($order, $amount, $paymentMethod, $bankCashId, $reference, $notes);
            if ($result['success']) {
                if ($proofImage && !empty($result['payment_id'])) {
                    db()->update('order_payments', ['proof_image' => $proofImage], 'id = ?', [$result['payment_id']]);
                }
                return $result;
            }
            // fall through to the soft path below (accounting unconfigured, tables missing, etc.)
        }
    }

    // Soft path: record directly, no journal/bank_cash side effects
    $paymentId = db()->insert('order_payments', [
        'company_id' => $companyId,
        'order_id' => $orderId,
        'journal_id' => null,
        'amount' => $amount,
        'payment_date' => $paymentDate,
        'payment_method' => $paymentMethod,
        'bank_cash_id' => $bankCashId,
        'reference' => $reference,
        'notes' => $notes,
        'proof_image' => $proofImage,
        'status' => 'posted',
        'created_by' => Session::userId()
    ]);

    require_once MODELS_PATH . '/Order.php';
    $order = Order::find($orderId);
    if ($order) $order->updatePaidAmount();

    return [
        'success' => true,
        'payment_id' => $paymentId,
        'message' => 'Pembayaran berhasil dicatat (tanpa jurnal akuntansi).'
    ];
}

// ================================================================
// NOTIFICATION HELPERS
// ================================================================

/**
 * Create Notification
 * @param int $userId
 * @param string $title
 * @param string $message
 * @param string $type
 * @param string|null $link
 */
function notify(int $userId, string $title, string $message, string $type = 'info', ?string $link = null): void {
    try {
        db()->insert('notifications', [
            'user_id' => $userId,
            'company_id' => Session::companyId(),
            'title' => $title,
            'message' => $message,
            'type' => $type,
            'link' => $link
        ]);
    } catch (Exception $e) {
        error_log("Failed to create notification: " . $e->getMessage());
    }
}

/**
 * Notify every active user of a company
 * @param int $companyId
 * @param string $title
 * @param string $message
 * @param string $type
 * @param string|null $link
 * @param int|null $excludeUserId Skip this user (usually the one who triggered the event)
 */
function notifyCompanyUsers(int $companyId, string $title, string $message, string $type = 'info', ?string $link = null, ?int $excludeUserId = null): void {
    try {
        // Superadmins have implicit access to every company (no user_companies
        // row needed - see Auth::getUserCompanies()), so include them alongside
        // users explicitly linked to this company.
        $users = db()->fetchAll(
            "SELECT DISTINCT u.id FROM users u
             WHERE u.is_active = 1 AND (
                 u.role = 'superadmin'
                 OR EXISTS (
                     SELECT 1 FROM user_companies uc
                     WHERE uc.user_id = u.id AND uc.company_id = ? AND uc.is_active = 1
                 )
             )",
            [$companyId]
        );

        foreach ($users as $u) {
            if ($excludeUserId && (int)$u['id'] === $excludeUserId) {
                continue;
            }
            notify((int)$u['id'], $title, $message, $type, $link);
        }
    } catch (Exception $e) {
        error_log("notifyCompanyUsers error: " . $e->getMessage());
    }
}

// ================================================================
// DOCUMENT NUMBER HELPERS
// ================================================================

/**
 * Generate Document Number
 * @param int $companyId
 * @param string $docType
 * @return string
 */
function generateDocNumber(int $companyId, string $docType): string {
    $year = (int) date('Y');
    $month = (int) date('n');
    
    // Get or create counter
    $counter = db()->fetchOne(
        "SELECT * FROM document_numbers 
         WHERE company_id = ? AND doc_type = ? AND year = ? AND month = ?",
        [$companyId, $docType, $year, $month]
    );
    
    if ($counter) {
        $newNumber = $counter['last_number'] + 1;
        db()->update(
            'document_numbers',
            ['last_number' => $newNumber],
            'id = ?',
            [$counter['id']]
        );
    } else {
        $newNumber = 1;
        db()->insert('document_numbers', [
            'company_id' => $companyId,
            'doc_type' => $docType,
            'year' => $year,
            'month' => $month,
            'last_number' => $newNumber,
            'prefix' => DOCUMENT_TYPES[$docType]['prefix'] ?? 'DOC'
        ]);
    }
    
    // Get format from settings
    $format = getCompanySetting($companyId, "doc_number_format_{$docType}");
    if (!$format) {
        $format = '{NUM}/{PREFIX}/{MONTH}/{YEAR}';
    }
    
    // Build number
    $prefix = DOCUMENT_TYPES[$docType]['prefix'] ?? 'DOC';
    $number = str_pad($newNumber, 4, '0', STR_PAD_LEFT);
    $monthRoman = monthRoman($month);
    
    $docNumber = $format;
    $docNumber = str_replace('{NUM}', $number, $docNumber);
    $docNumber = str_replace('{PREFIX}', $prefix, $docNumber);
    $docNumber = str_replace('{MONTH}', $monthRoman, $docNumber);
    $docNumber = str_replace('{YEAR}', $year, $docNumber);
    $docNumber = str_replace('{MM}', str_pad($month, 2, '0', STR_PAD_LEFT), $docNumber);
    $docNumber = str_replace('{YY}', substr($year, -2), $docNumber);
    
    return $docNumber;
}

// ================================================================
// SETTINGS HELPERS
// ================================================================

/**
 * Get Company Setting
 * @param int $companyId
 * @param string $key
 * @param mixed $default
 * @return mixed
 */
function getCompanySetting(int $companyId, string $key, $default = null) {
    $result = db()->fetchOne(
        "SELECT setting_value, setting_type FROM company_settings 
         WHERE company_id = ? AND setting_key = ?",
        [$companyId, $key]
    );
    
    if (!$result) {
        return $default;
    }
    
    $value = $result['setting_value'];
    
    switch ($result['setting_type']) {
        case 'number':
            return (float) $value;
        case 'boolean':
            return $value === '1' || $value === 'true';
        case 'json':
            return json_decode($value, true);
        default:
            return $value;
    }
}

/**
 * Set Company Setting
 * @param int $companyId
 * @param string $key
 * @param mixed $value
 * @param string $type
 */
function setCompanySetting(int $companyId, string $key, $value, string $type = 'text'): void {
    if ($type === 'json') {
        $value = json_encode($value);
    } elseif ($type === 'boolean') {
        $value = $value ? '1' : '0';
    }
    
    $existing = db()->fetchColumn(
        "SELECT id FROM company_settings WHERE company_id = ? AND setting_key = ?",
        [$companyId, $key]
    );
    
    if ($existing) {
        db()->update(
            'company_settings',
            ['setting_value' => $value, 'setting_type' => $type],
            'id = ?',
            [$existing]
        );
    } else {
        db()->insert('company_settings', [
            'company_id' => $companyId,
            'setting_key' => $key,
            'setting_value' => $value,
            'setting_type' => $type
        ]);
    }
}

// ================================================================
// PAGE ACCESS HELPERS (Super Admin → kontrol halaman per role)
// ================================================================

/**
 * Whether a page is visible/accessible for a role, per Super Admin's
 * settings in role_page_access. No row = visible (safe default: an empty
 * or missing table never locks anyone out). Super Admin always sees
 * everything regardless of this table.
 */
function isPageEnabledForRole(string $role, string $pageKey, ?int $companyId = null): bool {
    if ($role === ROLE_SUPERADMIN) {
        return true;
    }

    $companyId = $companyId ?? Session::companyId();
    if (!$companyId) {
        return true;
    }

    try {
        $disabled = db()->fetchOne(
            "SELECT id FROM role_page_access WHERE company_id = ? AND role = ? AND page_key = ?",
            [$companyId, $role, $pageKey]
        );
        return empty($disabled);
    } catch (Exception $e) {
        // Tabel belum ada (migrasi belum dijalankan) -- jangan mengunci siapa pun.
        return true;
    }
}

/**
 * Set of page_key's currently disabled for a role (for rendering checkboxes).
 */
function getDisabledPagesForRole(string $role, ?int $companyId = null): array {
    $companyId = $companyId ?? Session::companyId();
    if (!$companyId) {
        return [];
    }

    try {
        $rows = db()->fetchAll(
            "SELECT page_key FROM role_page_access WHERE company_id = ? AND role = ?",
            [$companyId, $role]
        );
        return array_column($rows, 'page_key');
    } catch (Exception $e) {
        return [];
    }
}

// ================================================================
// MISC HELPERS
// ================================================================

/**
 * Get Client IP Address
 * @return string
 */
function getClientIp(): string {
    $keys = ['HTTP_CF_CONNECTING_IP', 'HTTP_X_FORWARDED_FOR', 'HTTP_X_REAL_IP', 'REMOTE_ADDR'];
    
    foreach ($keys as $key) {
        if (!empty($_SERVER[$key])) {
            $ip = explode(',', $_SERVER[$key])[0];
            $ip = trim($ip);
            if (filter_var($ip, FILTER_VALIDATE_IP)) {
                return $ip;
            }
        }
    }
    
    return '0.0.0.0';
}

/**
 * Check if Development Environment
 * @return bool
 */
function isDev(): bool {
    return defined('APP_DEBUG') && APP_DEBUG === true;
}

/**
 * Debug Dump
 * @param mixed ...$vars
 */
function dd(...$vars): void {
    echo '<pre style="background:#1e1e1e;color:#d4d4d4;padding:1rem;margin:1rem;border-radius:4px;overflow:auto;">';
    foreach ($vars as $var) {
        var_dump($var);
    }
    echo '</pre>';
    exit;
}

/**
 * Calculate Markup
 * @param float $basePrice
 * @param string $markupType
 * @param float $markupValue
 * @return array [finalPrice, profit, percentage]
 */
function calculateMarkup(float $basePrice, string $markupType, float $markupValue): array {
    if ($markupType === MARKUP_PERCENTAGE) {
        $profit = $basePrice * ($markupValue / 100);
        $finalPrice = $basePrice + $profit;
        $percentage = $markupValue;
    } else {
        $finalPrice = $markupValue; // markupValue is the final price
        $profit = $finalPrice - $basePrice;
        $percentage = $basePrice > 0 ? (($profit / $basePrice) * 100) : 0;
    }
    
    return [
        'final_price' => round($finalPrice, 0),
        'profit' => round($profit, 0),
        'percentage' => round($percentage, 2)
    ];
}
