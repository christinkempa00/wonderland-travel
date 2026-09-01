<?php
/**
 * ================================================================
 * TRAVEL MANAGEMENT SYSTEM - Constants Configuration
 * ================================================================
 * 
 * UPDATED: Added ROLE_ATTACHMENT for Staff Lampiran
 */

// Prevent direct access
if (!defined('BASE_PATH')) {
    exit('No direct script access allowed');
}

/**
 * Feature flags
 */
// Kelola Data Penumang (tamu hotel, penumpang pesawat, rental, dokumen
// kendaraan) — dinonaktifkan sesuai permintaan, TIDAK dihapus. Set ke true
// untuk mengaktifkan lagi (juga mempengaruhi role Staff Lampiran, yang
// seluruh fiturnya berbasis ini).
define('FEATURE_PASSENGER_MANAGEMENT', false);

// Halaman Dokumen (daftar penawaran/invoice/kwitansi tersimpan) —
// dinonaktifkan sesuai permintaan, TIDAK dihapus. Set ke true untuk
// mengaktifkan lagi.
define('FEATURE_DOCUMENTS', false);

/**
 * Application Info
 */
define('APP_NAME', 'Wonderland Travel');
define('APP_SHORT_NAME', 'TravelMS');
define('APP_VERSION', '1.0.0');
define('APP_AUTHOR', 'Wonderland Travel');
define('APP_DEBUG', false); // Set true untuk development

/**
 * URL Configuration
 * Detects the current host so it works both locally and in production
 * without needing to be hand-edited before each deploy.
 */
if (php_sapi_name() !== 'cli' && isset($_SERVER['HTTP_HOST'])) {
    $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    define('BASE_URL', $scheme . '://' . $_SERVER['HTTP_HOST']);
} else {
    define('BASE_URL', 'https://admin.wonderlandtrips.com');
}
define('ASSETS_URL', BASE_URL . '/assets');
define('UPLOADS_URL', BASE_URL . '/uploads');

/**
 * Directory Paths
 */
define('CONFIG_PATH', BASE_PATH . '/config');
define('CONTROLLERS_PATH', BASE_PATH . '/controllers');
define('MODELS_PATH', BASE_PATH . '/models');
define('VIEWS_PATH', BASE_PATH . '/views');
define('HELPERS_PATH', BASE_PATH . '/helpers');
define('ASSETS_PATH', BASE_PATH . '/assets');
define('UPLOADS_PATH', BASE_PATH . '/uploads');
define('CACHE_PATH', BASE_PATH . '/cache');
define('LOGS_PATH', BASE_PATH . '/logs');

/**
 * Upload Subdirectories
 */
define('LOGOS_PATH', UPLOADS_PATH . '/logos');
define('FAVICONS_PATH', UPLOADS_PATH . '/favicons');
define('DOCUMENTS_PATH', UPLOADS_PATH . '/documents');
define('AVATARS_PATH', UPLOADS_PATH . '/avatars');

/**
 * User Roles
 */
define('ROLE_SUPERADMIN', 'superadmin');
define('ROLE_ADMIN', 'admin');
define('ROLE_STAFF', 'staff');
define('ROLE_ACCOUNTING', 'accounting');
define('ROLE_VIEWER', 'viewer');
define('ROLE_ATTACHMENT', 'attachment');  // NEW: Staff Lampiran

define('ROLES', [
    ROLE_SUPERADMIN => 'Super Admin',
    ROLE_ADMIN => 'Administrator',
    ROLE_STAFF => 'Staff',
    ROLE_ACCOUNTING => 'Akuntansi',
    ROLE_VIEWER => 'Viewer',
    ROLE_ATTACHMENT => 'Staff Lampiran'  // NEW
]);

// Untuk form user (tanpa superadmin)
define('USER_ROLES', [
    ROLE_ADMIN => 'Administrator',
    ROLE_STAFF => 'Staff',
    ROLE_ACCOUNTING => 'Akuntansi',
    ROLE_VIEWER => 'Viewer',
    ROLE_ATTACHMENT => 'Staff Lampiran'  // NEW
]);

/**
 * Role Permissions Matrix
 */
define('PERMISSIONS', [
    ROLE_SUPERADMIN => ['*'], // All permissions
    ROLE_ADMIN => [
        'dashboard.view',
        'orders.*',
        'clients.*',
        'invoices.*',
        'documents.*',
        'profit.*',
        'accounting.*',
        'settings.company',
        'settings.users',
        'settings.templates',
        'settings.profit_sharing',
        'reports.*'
    ],
    ROLE_STAFF => [
        'dashboard.view',
        'orders.view',
        'orders.create',
        'orders.edit',
        'clients.view',
        'clients.create',
        'clients.edit',
        'invoices.view',
        'invoices.create',
        'documents.view',
        'documents.create'
    ],
    ROLE_ACCOUNTING => [
        'dashboard.view',
        'orders.view',
        'accounting.*',
        'reports.*',
        'profit.view'
    ],
    ROLE_VIEWER => [
        'dashboard.view',
        'orders.view',
        'clients.view',
        'documents.view',
        'reports.view'
    ],
    // NEW: Staff Lampiran - hanya bisa input lampiran
    ROLE_ATTACHMENT => [
        'dashboard.view',
        'attachments.*',
        'orders.view_limited'
    ]
]);

/**
 * Pages that Super Admin can individually show/hide per role, via
 * Pengaturan > Akses Halaman. Key => sidebar label + the route prefix its
 * middleware checks (see 'page:' case in index.php's handleSingleMiddleware).
 * Dashboard is intentionally excluded — it's the post-login landing page.
 */
define('PAGE_ACCESS_PAGES', [
    'orders' => 'Pesanan',
    'clients' => 'Klien',
    'documents' => 'Dokumen',
    'pembayaran' => 'Pembayaran',
    'surat_permohonan' => 'Surat Permohonan Pembayaran',
    'profit' => 'Bagi Hasil',
    'settings' => 'Pengaturan',
    'users' => 'Pengguna',
    'activity_logs' => 'Log Aktivitas',
]);

/**
 * Order Status
 */
define('ORDER_STATUS_DRAFT', 'draft');
define('ORDER_STATUS_QUOTATION', 'quotation');
define('ORDER_STATUS_AGREED', 'agreed');
define('ORDER_STATUS_INVOICED', 'invoiced');
define('ORDER_STATUS_PAID', 'paid');
define('ORDER_STATUS_COMPLETED', 'completed');
define('ORDER_STATUS_CANCELLED', 'cancelled');

define('ORDER_STATUSES', [
    ORDER_STATUS_DRAFT => ['label' => 'Draft', 'color' => 'secondary', 'icon' => 'file-text'],
    ORDER_STATUS_QUOTATION => ['label' => 'Penawaran', 'color' => 'info', 'icon' => 'send'],
    ORDER_STATUS_AGREED => ['label' => 'Disepakati', 'color' => 'primary', 'icon' => 'check-circle'],
    ORDER_STATUS_INVOICED => ['label' => 'Invoice Terbit', 'color' => 'warning', 'icon' => 'file-invoice'],
    ORDER_STATUS_PAID => ['label' => 'Lunas', 'color' => 'success', 'icon' => 'check-double'],
    ORDER_STATUS_COMPLETED => ['label' => 'Selesai', 'color' => 'success', 'icon' => 'flag-checkered'],
    ORDER_STATUS_CANCELLED => ['label' => 'Dibatalkan', 'color' => 'danger', 'icon' => 'times-circle']
]);

/**
 * Payment Status
 */
define('PAYMENT_UNPAID', 'unpaid');
define('PAYMENT_PARTIAL', 'partial');
define('PAYMENT_PAID', 'paid');

define('PAYMENT_STATUSES', [
    PAYMENT_UNPAID => ['label' => 'Belum Dibayar', 'color' => 'danger'],
    PAYMENT_PARTIAL => ['label' => 'Sebagian', 'color' => 'warning'],
    PAYMENT_PAID => ['label' => 'Lunas', 'color' => 'success']
]);

/**
 * PELNI Divisi (khusus klien dengan clients.uses_divisi = 1)
 * Menentukan format penomoran invoice: INV-PLNI{TAHUN ROMAWI}{INFIX}-{NOMOR}
 */
define('DIVISI_JM', 'JM');
define('DIVISI_OFFICE', 'OFFICE');
define('DIVISI_TIKOM', 'TIKOM');

define('DIVISI_OPTIONS', [
    DIVISI_JM => 'JM',
    DIVISI_OFFICE => 'Office',
    DIVISI_TIKOM => 'Tikom',
]);

define('DIVISI_INVOICE_INFIX', [
    DIVISI_JM => '',
    DIVISI_OFFICE => 'OF',
    DIVISI_TIKOM => 'TI',
]);

/**
 * Order Item Types
 */
define('ITEM_TYPE_BUS', 'bus');
define('ITEM_TYPE_TOWING', 'towing');
define('ITEM_TYPE_HOTEL', 'hotel');
define('ITEM_TYPE_FLIGHT', 'flight');
define('ITEM_TYPE_TRAVEL', 'travel');
define('ITEM_TYPE_RENTAL', 'rental');
define('ITEM_TYPE_RESTAURANT', 'restaurant');
define('ITEM_TYPE_TRAIN', 'train');
define('ITEM_TYPE_KONTRAKAN', 'kontrakan');
define('ITEM_TYPE_SHIP', 'ship');
define('ITEM_TYPE_OTHER', 'other');

define('ITEM_TYPES', [
    ITEM_TYPE_BUS => ['label' => 'Bus/Bis', 'icon' => 'bus', 'color' => 'primary'],
    ITEM_TYPE_TOWING => ['label' => 'Towing', 'icon' => 'truck-pickup', 'color' => 'warning'],
    ITEM_TYPE_HOTEL => ['label' => 'Hotel', 'icon' => 'hotel', 'color' => 'info'],
    ITEM_TYPE_FLIGHT => ['label' => 'Pesawat', 'icon' => 'plane', 'color' => 'danger'],
    ITEM_TYPE_TRAVEL => ['label' => 'Travel', 'icon' => 'route', 'color' => 'success'],
    ITEM_TYPE_RENTAL => ['label' => 'Sewa Kendaraan', 'icon' => 'car', 'color' => 'secondary'],
    ITEM_TYPE_RESTAURANT => ['label' => 'Restoran', 'icon' => 'utensils', 'color' => 'orange'],
    ITEM_TYPE_TRAIN => ['label' => 'Kereta Api', 'icon' => 'train', 'color' => 'info'],
    ITEM_TYPE_KONTRAKAN => ['label' => 'Kontrakan', 'icon' => 'house', 'color' => 'secondary'],
    ITEM_TYPE_SHIP => ['label' => 'Kapal Laut', 'icon' => 'ship', 'color' => 'primary'],
    ITEM_TYPE_OTHER => ['label' => 'Lainnya', 'icon' => 'ellipsis-h', 'color' => 'dark']
]);

/**
 * Document Types
 */
define('DOC_QUOTATION', 'quotation');
define('DOC_AGREEMENT', 'agreement');
define('DOC_INVOICE', 'invoice');
define('DOC_RECEIPT', 'receipt');

define('DOCUMENT_TYPES', [
    DOC_QUOTATION => ['label' => 'Surat Penawaran', 'prefix' => 'PNW', 'icon' => 'file-alt'],
    DOC_AGREEMENT => ['label' => 'Surat Kesepakatan', 'prefix' => 'SPK', 'icon' => 'handshake'],
    DOC_INVOICE => ['label' => 'Invoice', 'prefix' => 'INV', 'icon' => 'file-invoice-dollar'],
    DOC_RECEIPT => ['label' => 'Kwitansi', 'prefix' => 'KWT', 'icon' => 'receipt']
]);

/**
 * Account Types (Akuntansi)
 */
define('ACCOUNT_ASSET', 'asset');
define('ACCOUNT_LIABILITY', 'liability');
define('ACCOUNT_EQUITY', 'equity');
define('ACCOUNT_REVENUE', 'revenue');
define('ACCOUNT_EXPENSE', 'expense');

define('ACCOUNT_TYPES', [
    ACCOUNT_ASSET => ['label' => 'Aset', 'normal' => 'debit'],
    ACCOUNT_LIABILITY => ['label' => 'Liabilitas', 'normal' => 'credit'],
    ACCOUNT_EQUITY => ['label' => 'Ekuitas', 'normal' => 'credit'],
    ACCOUNT_REVENUE => ['label' => 'Pendapatan', 'normal' => 'credit'],
    ACCOUNT_EXPENSE => ['label' => 'Beban', 'normal' => 'debit']
]);

/**
 * Markup Types
 */
define('MARKUP_PERCENTAGE', 'percentage');
define('MARKUP_FIXED', 'fixed');

define('MARKUP_TYPES', [
    MARKUP_PERCENTAGE => 'Persentase (%)',
    MARKUP_FIXED => 'Nominal (Rp)'
]);

/**
 * Profit Share Status
 */
define('PROFIT_PENDING', 'pending');
define('PROFIT_PAID', 'paid');

/**
 * Date & Time Format
 */
define('DATE_FORMAT', 'd/m/Y');
define('DATE_FORMAT_DB', 'Y-m-d');
define('DATETIME_FORMAT', 'd/m/Y H:i');
define('DATETIME_FORMAT_DB', 'Y-m-d H:i:s');
define('TIME_FORMAT', 'H:i');

/**
 * Currency
 */
define('CURRENCY_CODE', 'IDR');
if (!defined('CURRENCY_SYMBOL') || CURRENCY_SYMBOL === 262145 || !is_string(CURRENCY_SYMBOL)) {
    $GLOBALS['CURRENCY_SYMBOL_VALUE'] = 'Rp';
} else {
    $GLOBALS['CURRENCY_SYMBOL_VALUE'] = CURRENCY_SYMBOL;
}
@define('CURRENCY_SYMBOL', 'Rp');
define('CURRENCY_DECIMAL', 0);
define('CURRENCY_THOUSAND_SEP', '.');
define('CURRENCY_DECIMAL_SEP', ',');

/**
 * Pagination
 */
define('PER_PAGE_DEFAULT', 10);
define('PER_PAGE_OPTIONS', [10, 25, 50, 100]);
define('PAGINATION_PER_PAGE', 10);

/**
 * File Upload Limits
 */
define('MAX_UPLOAD_SIZE', 5 * 1024 * 1024); // 5MB
define('ALLOWED_IMAGE_TYPES', ['image/jpeg', 'image/png', 'image/gif', 'image/webp']);
define('ALLOWED_DOCUMENT_TYPES', ['application/pdf', 'image/jpeg', 'image/png']);

/**
 * Session Configuration
 */
define('SESSION_NAME', 'travelms_session');
define('SESSION_LIFETIME', 7200); // 2 hours
define('SESSION_PATH', '/');
define('SESSION_SECURE', true); // HTTPS only
define('SESSION_HTTPONLY', true);

/**
 * Security
 */
define('CSRF_TOKEN_NAME', 'csrf_token');
define('PASSWORD_MIN_LENGTH', 8);
define('LOGIN_MAX_ATTEMPTS', 5);
define('LOGIN_LOCKOUT_TIME', 900); // 15 minutes

/**
 * Roman Numerals for Months
 */
define('ROMAN_MONTHS', [
    1 => 'I', 2 => 'II', 3 => 'III', 4 => 'IV',
    5 => 'V', 6 => 'VI', 7 => 'VII', 8 => 'VIII',
    9 => 'IX', 10 => 'X', 11 => 'XI', 12 => 'XII'
]);

/**
 * Indonesian Month Names
 */
define('INDONESIAN_MONTHS', [
    1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April',
    5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus',
    9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember'
]);

/**
 * Indonesian Day Names
 */
define('INDONESIAN_DAYS', [
    0 => 'Minggu', 1 => 'Senin', 2 => 'Selasa', 3 => 'Rabu',
    4 => 'Kamis', 5 => 'Jumat', 6 => 'Sabtu'
]);

/**
 * Paper Sizes (in mm)
 */
define('PAPER_SIZES', [
    'A4' => ['width' => 210, 'height' => 297],
    'Letter' => ['width' => 216, 'height' => 279],
    'Legal' => ['width' => 216, 'height' => 356],
    'F4' => ['width' => 210, 'height' => 330]
]);

/**
 * Default Settings Keys
 */
define('DEFAULT_SETTINGS', [
    'dripsender_api_key' => '',
    'dripsender_sender_id' => '',
    'default_markup_type' => MARKUP_PERCENTAGE,
    'default_markup_value' => '10',
    'invoice_due_days' => '14',
    'doc_number_format_quotation' => '{NUM}/{PREFIX}/{MONTH}/{YEAR}',
    'doc_number_format_agreement' => '{NUM}/{PREFIX}/{MONTH}/{YEAR}',
    'doc_number_format_invoice' => '{NUM}/{PREFIX}/{MONTH}/{YEAR}',
    'doc_number_format_receipt' => '{NUM}/{PREFIX}/{MONTH}/{YEAR}',
    'doc_number_format_order' => 'ORD/{YY}{MM}/{NUM}'
]);