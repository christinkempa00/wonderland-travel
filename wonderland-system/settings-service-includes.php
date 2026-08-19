<?php
/**
 * Settings - Service Includes
 * Manage "Harga Sudah Termasuk" per jenis layanan
 */

define('BASE_PATH', __DIR__);
require_once __DIR__ . '/config/constants.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/config/session.php';
require_once __DIR__ . '/helpers/functions.php';
Session::start();

if (!Session::isLoggedIn()) {
    http_response_code(403);
    die('Akses ditolak. Silakan login terlebih dahulu.');
}

$pageTitle = 'Settings - Harga Termasuk';

$serviceTypes = [
    'bus' => 'Big Bus',
    'medium_bus' => 'Medium Bus', 
    'mini_bus' => 'Mini Bus',
    'hiace' => 'Hiace',
    'elf' => 'Elf',
    'towing' => 'Towing',
    'hotel' => 'Hotel',
    'flight' => 'Tiket Pesawat',
    'rental' => 'Rental Mobil'
];

$selectedType = isset($_GET['type']) ? $_GET['type'] : 'bus';
$message = '';
$messageType = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = isset($_POST['action']) ? $_POST['action'] : '';
    
    if ($action === 'add') {
        $type = $_POST['service_type'];
        $item = trim($_POST['include_item']);
        if ($item) {
            $maxOrder = db()->fetchOne("SELECT MAX(sort_order) as max_order FROM service_includes WHERE service_type = ?", [$type]);
            $nextOrder = ($maxOrder && $maxOrder['max_order']) ? $maxOrder['max_order'] + 1 : 1;
            
            try {
                db()->insert('service_includes', [
                    'service_type' => $type,
                    'include_item' => $item,
                    'sort_order' => $nextOrder,
                    'is_active' => 1
                ]);
                $message = 'Item berhasil ditambahkan!';
                $messageType = 'success';
            } catch (Exception $e) {
                $message = 'Item sudah ada atau gagal ditambahkan.';
                $messageType = 'error';
            }
        }
        $selectedType = $type;
    }
    
    if ($action === 'delete') {
        $id = (int)$_POST['id'];
        db()->delete('service_includes', 'id = ?', [$id]);
        $message = 'Item berhasil dihapus!';
        $messageType = 'success';
        $selectedType = $_POST['service_type'];
    }
    
    if ($action === 'toggle') {
        $id = (int)$_POST['id'];
        $current = db()->fetchOne("SELECT is_active FROM service_includes WHERE id = ?", [$id]);
        $newStatus = $current['is_active'] ? 0 : 1;
        db()->update('service_includes', ['is_active' => $newStatus], 'id = ?', [$id]);
        $message = 'Status berhasil diubah!';
        $messageType = 'success';
        $selectedType = $_POST['service_type'];
    }
    
    if ($action === 'copy') {
        $fromType = $_POST['from_type'];
        $toType = $_POST['to_type'];
        if ($fromType !== $toType) {
            // Delete existing items in target
            db()->delete('service_includes', 'service_type = ?', [$toType]);
            
            // Copy from source
            $items = db()->fetchAll("SELECT include_item, sort_order FROM service_includes WHERE service_type = ? ORDER BY sort_order", [$fromType]);
            foreach ($items as $item) {
                db()->insert('service_includes', [
                    'service_type' => $toType,
                    'include_item' => $item['include_item'],
                    'sort_order' => $item['sort_order'],
                    'is_active' => 1
                ]);
            }
            $message = "Berhasil copy dari {$serviceTypes[$fromType]} ke {$serviceTypes[$toType]}!";
            $messageType = 'success';
            $selectedType = $toType;
        }
    }
}

// Get items for selected type
$items = db()->fetchAll("SELECT * FROM service_includes WHERE service_type = ? ORDER BY sort_order", [$selectedType]);

$content = '
<div class="container">
    <div class="page-header">
        <h1>⚙️ Settings - Harga Sudah Termasuk</h1>
        <p class="subtitle">Kelola item yang termasuk dalam harga per jenis layanan untuk Surat Penawaran</p>
    </div>
    
    ' . ($message ? '<div class="alert alert-' . $messageType . '">' . e($message) . '</div>' : '') . '
    
    <div class="settings-layout">
        <!-- Service Type Tabs -->
        <div class="service-tabs">
            <h3>Jenis Layanan</h3>
            <div class="tab-list">';

foreach ($serviceTypes as $key => $label) {
    $activeClass = ($key === $selectedType) ? 'active' : '';
    $count = db()->fetchOne("SELECT COUNT(*) as cnt FROM service_includes WHERE service_type = ? AND is_active = 1", [$key]);
    $content .= '<a href="?type=' . $key . '" class="tab-item ' . $activeClass . '">
        <span class="tab-label">' . e($label) . '</span>
        <span class="tab-count">' . $count['cnt'] . '</span>
    </a>';
}

$content .= '
            </div>
        </div>
        
        <!-- Items Panel -->
        <div class="items-panel">
            <div class="panel-header">
                <h2>' . e($serviceTypes[$selectedType]) . '</h2>
                <span class="badge">' . count($items) . ' item</span>
            </div>
            
            <!-- Add New Item -->
            <form method="POST" class="add-form">
                <input type="hidden" name="action" value="add">
                <input type="hidden" name="service_type" value="' . e($selectedType) . '">
                <div class="input-group">
                    <input type="text" name="include_item" placeholder="Tambah item baru..." required>
                    <button type="submit" class="btn btn-primary">+ Tambah</button>
                </div>
            </form>
            
            <!-- Items List -->
            <div class="items-list">';

if (empty($items)) {
    $content .= '<div class="empty-state">
        <p>Belum ada item untuk layanan ini.</p>
    </div>';
} else {
    foreach ($items as $item) {
        $activeClass = $item['is_active'] ? 'active' : 'inactive';
        $content .= '
        <div class="item-row ' . $activeClass . '">
            <span class="item-order">' . $item['sort_order'] . '</span>
            <span class="item-name">' . e($item['include_item']) . '</span>
            <div class="item-actions">
                <form method="POST" style="display:inline">
                    <input type="hidden" name="action" value="toggle">
                    <input type="hidden" name="id" value="' . $item['id'] . '">
                    <input type="hidden" name="service_type" value="' . e($selectedType) . '">
                    <button type="submit" class="btn-icon ' . ($item['is_active'] ? 'btn-on' : 'btn-off') . '" title="' . ($item['is_active'] ? 'Nonaktifkan' : 'Aktifkan') . '">
                        ' . ($item['is_active'] ? '✓' : '✗') . '
                    </button>
                </form>
                <form method="POST" style="display:inline" onsubmit="return confirm(\'Hapus item ini?\')">
                    <input type="hidden" name="action" value="delete">
                    <input type="hidden" name="id" value="' . $item['id'] . '">
                    <input type="hidden" name="service_type" value="' . e($selectedType) . '">
                    <button type="submit" class="btn-icon btn-delete" title="Hapus">🗑️</button>
                </form>
            </div>
        </div>';
    }
}

$content .= '
            </div>
            
            <!-- Copy From -->
            <div class="copy-section">
                <h4>Copy dari layanan lain</h4>
                <form method="POST" class="copy-form">
                    <input type="hidden" name="action" value="copy">
                    <input type="hidden" name="to_type" value="' . e($selectedType) . '">
                    <select name="from_type" required>
                        <option value="">Pilih sumber...</option>';

foreach ($serviceTypes as $key => $label) {
    if ($key !== $selectedType) {
        $content .= '<option value="' . $key . '">' . e($label) . '</option>';
    }
}

$content .= '
                    </select>
                    <button type="submit" class="btn btn-secondary" onclick="return confirm(\'Ini akan mengganti semua item yang ada. Lanjutkan?\')">Copy</button>
                </form>
            </div>
            
            <!-- Preview -->
            <div class="preview-section">
                <h4>Preview di Surat Penawaran</h4>
                <div class="preview-box">
                    <p><strong>Harga sudah termasuk:</strong></p>
                    <ul>';

$activeItems = array_filter($items, function($i) { return $i['is_active']; });
foreach ($activeItems as $item) {
    $content .= '<li>' . e($item['include_item']) . '</li>';
}

if (empty($activeItems)) {
    $content .= '<li><em>(Tidak ada item aktif)</em></li>';
}

$content .= '
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.container { max-width: 1000px; margin: 0 auto; padding: 20px; }
.page-header { margin-bottom: 30px; }
.page-header h1 { font-size: 24px; margin-bottom: 5px; }
.page-header .subtitle { color: #6b7280; font-size: 14px; }

.alert { padding: 12px 16px; border-radius: 8px; margin-bottom: 20px; font-size: 14px; }
.alert-success { background: #d1fae5; color: #065f46; }
.alert-error { background: #fee2e2; color: #991b1b; }

.settings-layout { display: grid; grid-template-columns: 250px 1fr; gap: 30px; }

.service-tabs { background: #f9fafb; border-radius: 12px; padding: 20px; }
.service-tabs h3 { font-size: 12px; text-transform: uppercase; letter-spacing: 1px; color: #6b7280; margin-bottom: 15px; }
.tab-list { display: flex; flex-direction: column; gap: 5px; }
.tab-item { display: flex; justify-content: space-between; align-items: center; padding: 10px 14px; border-radius: 8px; text-decoration: none; color: #374151; font-size: 14px; transition: all 0.2s; }
.tab-item:hover { background: #e5e7eb; }
.tab-item.active { background: #dc2626; color: white; }
.tab-count { background: rgba(0,0,0,0.1); padding: 2px 8px; border-radius: 10px; font-size: 12px; }
.tab-item.active .tab-count { background: rgba(255,255,255,0.2); }

.items-panel { background: white; border-radius: 12px; border: 1px solid #e5e7eb; padding: 25px; }
.panel-header { display: flex; align-items: center; gap: 10px; margin-bottom: 20px; }
.panel-header h2 { font-size: 20px; margin: 0; }
.badge { background: #f3f4f6; color: #6b7280; padding: 4px 10px; border-radius: 12px; font-size: 12px; }

.add-form { margin-bottom: 20px; }
.input-group { display: flex; gap: 10px; }
.input-group input { flex: 1; padding: 10px 14px; border: 1px solid #e5e7eb; border-radius: 8px; font-size: 14px; }
.input-group input:focus { outline: none; border-color: #dc2626; }

.btn { padding: 10px 20px; border: none; border-radius: 8px; font-size: 14px; font-weight: 600; cursor: pointer; transition: all 0.2s; }
.btn-primary { background: #dc2626; color: white; }
.btn-primary:hover { background: #b91c1c; }
.btn-secondary { background: #6b7280; color: white; }
.btn-secondary:hover { background: #4b5563; }

.items-list { margin-bottom: 25px; }
.item-row { display: flex; align-items: center; gap: 12px; padding: 12px; border-radius: 8px; margin-bottom: 5px; transition: all 0.2s; }
.item-row.active { background: #f9fafb; }
.item-row.inactive { background: #fef2f2; opacity: 0.6; }
.item-row:hover { background: #f3f4f6; }
.item-order { width: 24px; height: 24px; background: #e5e7eb; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 11px; color: #6b7280; flex-shrink: 0; }
.item-name { flex: 1; font-size: 14px; }
.item-row.inactive .item-name { text-decoration: line-through; }
.item-actions { display: flex; gap: 5px; }
.btn-icon { width: 32px; height: 32px; border: none; border-radius: 6px; cursor: pointer; font-size: 14px; transition: all 0.2s; }
.btn-on { background: #d1fae5; color: #065f46; }
.btn-off { background: #fee2e2; color: #991b1b; }
.btn-delete { background: #f3f4f6; }
.btn-delete:hover { background: #fee2e2; }

.copy-section { border-top: 1px solid #e5e7eb; padding-top: 20px; margin-bottom: 20px; }
.copy-section h4 { font-size: 12px; text-transform: uppercase; color: #6b7280; margin-bottom: 10px; }
.copy-form { display: flex; gap: 10px; }
.copy-form select { flex: 1; padding: 8px 12px; border: 1px solid #e5e7eb; border-radius: 6px; font-size: 14px; }

.preview-section { background: #fffbeb; border: 1px solid #fcd34d; border-radius: 10px; padding: 15px; }
.preview-section h4 { font-size: 12px; text-transform: uppercase; color: #92400e; margin-bottom: 10px; }
.preview-box { background: white; border-radius: 8px; padding: 15px; font-size: 13px; }
.preview-box ul { margin: 10px 0 0 20px; }
.preview-box li { margin-bottom: 5px; color: #374151; }

.empty-state { text-align: center; padding: 40px; color: #9ca3af; }

@media (max-width: 768px) {
    .settings-layout { grid-template-columns: 1fr; }
    .service-tabs { order: -1; }
    .tab-list { flex-direction: row; flex-wrap: wrap; }
    .tab-item { flex: 1; min-width: 100px; justify-content: center; text-align: center; }
}
</style>';

echo render('layouts/app', ['content' => $content, 'pageTitle' => $pageTitle]);
