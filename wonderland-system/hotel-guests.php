<?php
/**
 * Hotel Guests Manager
 * Untuk mengisi data tamu hotel sebagai lampiran invoice
 * URL: /hotel-guests.php?order_id=1
 */

define('BASE_PATH', __DIR__);
require_once __DIR__ . '/config/constants.php';
require_once __DIR__ . '/helpers/functions.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/config/session.php';

$pageTitle = 'Data Tamu Hotel';

$orderId = isset($_GET['order_id']) ? (int)$_GET['order_id'] : 0;
if (!$orderId) {
    die('Order ID required. Usage: hotel-guests.php?order_id=1');
}

// Get order data
$order = db()->fetchOne("SELECT o.*, c.name as client_name FROM orders o LEFT JOIN clients c ON o.client_id = c.id WHERE o.id = ?", [$orderId]);
if (!$order) {
    die('Order not found');
}

// Get hotel items from this order
$hotelItems = db()->fetchAll("SELECT * FROM order_items WHERE order_id = ? AND item_type = 'hotel' ORDER BY id", [$orderId]);

$message = '';
$messageType = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = isset($_POST['action']) ? $_POST['action'] : '';
    
    if ($action === 'save_guests') {
        // Delete existing guests for this order
        db()->delete('hotel_guests', 'order_id = ?', [$orderId]);
        
        // Insert new guests
        $guests = isset($_POST['guests']) ? $_POST['guests'] : [];
        $inserted = 0;
        
        foreach ($guests as $guest) {
            if (!empty($guest['guest_name'])) {
                db()->insert('hotel_guests', [
                    'order_id' => $orderId,
                    'order_item_id' => $guest['order_item_id'] ?: null,
                    'hotel_name' => $guest['hotel_name'] ?: null,
                    'guest_name' => $guest['guest_name'],
                    'room_number' => $guest['room_number'] ?: null,
                    'room_type' => $guest['room_type'] ?: null,
                    'check_in_date' => $guest['check_in_date'] ?: null,
                    'check_out_date' => $guest['check_out_date'] ?: null,
                ]);
                $inserted++;
            }
        }
        
        $message = "Berhasil menyimpan {$inserted} data tamu!";
        $messageType = 'success';
    }
    
    if ($action === 'update_hotel_info') {
        $itemId = (int)$_POST['item_id'];
        db()->update('order_items', [
            'hotel_name' => $_POST['hotel_name'],
            'room_type' => $_POST['room_type'],
            'check_in_date' => $_POST['check_in_date'],
            'check_out_date' => $_POST['check_out_date'],
        ], 'id = ?', [$itemId]);
        
        $message = "Info hotel berhasil diupdate!";
        $messageType = 'success';
        
        // Refresh hotel items
        $hotelItems = db()->fetchAll("SELECT * FROM order_items WHERE order_id = ? AND item_type = 'hotel' ORDER BY id", [$orderId]);
    }
}

// Get existing guests
$existingGuests = db()->fetchAll("SELECT * FROM hotel_guests WHERE order_id = ? ORDER BY id", [$orderId]);

// Group guests by hotel
$guestsByHotel = [];
foreach ($existingGuests as $g) {
    $key = $g['order_item_id'] ?: 'unknown';
    if (!isset($guestsByHotel[$key])) {
        $guestsByHotel[$key] = [];
    }
    $guestsByHotel[$key][] = $g;
}

$content = '
<div class="container">
    <div class="page-header">
        <div class="header-left">
            <a href="orders/' . $orderId . '" class="btn-back">← Kembali ke Order</a>
            <h1>🏨 Data Tamu Hotel</h1>
            <p class="subtitle">Order #' . $orderId . ' - ' . e($order['client_name']) . '</p>
        </div>
        <div class="header-actions">
            <a href="doc.php?order=' . $orderId . '&type=invoice" target="_blank" class="btn btn-secondary">📄 Lihat Invoice</a>
            <a href="doc-hotel-attachment.php?order_id=' . $orderId . '" target="_blank" class="btn btn-primary">📋 Cetak Lampiran</a>
        </div>
    </div>
    
    ' . ($message ? '<div class="alert alert-' . $messageType . '">' . e($message) . '</div>' : '') . '
    
    <!-- Hotel Items Section -->
    <div class="section">
        <h2>📍 Info Hotel dalam Order</h2>';

if (empty($hotelItems)) {
    $content .= '<div class="empty-state">
        <p>⚠️ Tidak ada item hotel dalam order ini.</p>
        <p>Tambahkan item dengan tipe "Hotel" di halaman order terlebih dahulu.</p>
    </div>';
} else {
    foreach ($hotelItems as $item) {
        $totalRooms = $item['quantity'];
        $guestsCount = isset($guestsByHotel[$item['id']]) ? count($guestsByHotel[$item['id']]) : 0;
        
        $content .= '
        <div class="hotel-card">
            <div class="hotel-header">
                <div class="hotel-info">
                    <h3>' . e($item['hotel_name'] ?: $item['description']) . '</h3>
                    <p>' . e($item['room_type'] ?: 'Room Type belum diisi') . ' • ' . $totalRooms . ' Kamar</p>
                </div>
                <div class="hotel-dates">
                    <span class="date-badge">' . ($item['check_in_date'] ? date('d M Y', strtotime($item['check_in_date'])) : 'Check-in?') . '</span>
                    <span class="date-arrow">→</span>
                    <span class="date-badge">' . ($item['check_out_date'] ? date('d M Y', strtotime($item['check_out_date'])) : 'Check-out?') . '</span>
                </div>
            </div>
            
            <!-- Edit Hotel Info Form -->
            <form method="POST" class="hotel-edit-form">
                <input type="hidden" name="action" value="update_hotel_info">
                <input type="hidden" name="item_id" value="' . $item['id'] . '">
                <div class="form-row">
                    <div class="form-group">
                        <label>Nama Hotel</label>
                        <input type="text" name="hotel_name" value="' . e($item['hotel_name']) . '" placeholder="Contoh: Swiss-Bellin Karawang">
                    </div>
                    <div class="form-group">
                        <label>Tipe Kamar</label>
                        <input type="text" name="room_type" value="' . e($item['room_type']) . '" placeholder="Contoh: Superior Room">
                    </div>
                    <div class="form-group">
                        <label>Check-in</label>
                        <input type="date" name="check_in_date" value="' . e($item['check_in_date']) . '">
                    </div>
                    <div class="form-group">
                        <label>Check-out</label>
                        <input type="date" name="check_out_date" value="' . e($item['check_out_date']) . '">
                    </div>
                    <div class="form-group">
                        <label>&nbsp;</label>
                        <button type="submit" class="btn btn-sm">Update</button>
                    </div>
                </div>
            </form>
            
            <div class="hotel-stats">
                <span class="stat">🛏️ ' . $totalRooms . ' Kamar</span>
                <span class="stat">👥 ' . $guestsCount . ' Tamu terdaftar</span>
                <span class="stat ' . ($guestsCount >= $totalRooms ? 'stat-ok' : 'stat-warning') . '">
                    ' . ($guestsCount >= $totalRooms ? '✅ Lengkap' : '⚠️ Perlu ' . ($totalRooms - $guestsCount) . ' tamu lagi') . '
                </span>
            </div>
        </div>';
    }
}

$content .= '
    </div>
    
    <!-- Guest List Form -->
    <div class="section">
        <h2>👥 Daftar Tamu</h2>
        <p class="section-desc">Isi data tamu untuk lampiran invoice hotel</p>
        
        <form method="POST" id="guestForm">
            <input type="hidden" name="action" value="save_guests">
            
            <div class="guest-table-wrapper">
                <table class="guest-table" id="guestTable">
                    <thead>
                        <tr>
                            <th width="40">No</th>
                            <th width="180">Hotel</th>
                            <th>Nama Tamu</th>
                            <th width="80">No. Room</th>
                            <th width="120">Tipe Kamar</th>
                            <th width="120">Check-in</th>
                            <th width="120">Check-out</th>
                            <th width="50">Aksi</th>
                        </tr>
                    </thead>
                    <tbody id="guestTableBody">';

// Render existing guests
$rowNum = 0;
if (!empty($existingGuests)) {
    foreach ($existingGuests as $guest) {
        $rowNum++;
        $content .= renderGuestRow($rowNum, $guest, $hotelItems);
    }
}

// Add empty rows if needed
$minRows = max(5, count($existingGuests) + 3);
for ($i = $rowNum + 1; $i <= $minRows; $i++) {
    $content .= renderGuestRow($i, null, $hotelItems);
}

$content .= '
                    </tbody>
                </table>
            </div>
            
            <div class="form-actions">
                <button type="button" class="btn btn-secondary" onclick="addRows(5)">+ Tambah 5 Baris</button>
                <button type="button" class="btn btn-secondary" onclick="addRows(10)">+ Tambah 10 Baris</button>
                <button type="submit" class="btn btn-primary btn-lg">💾 Simpan Data Tamu</button>
            </div>
        </form>
    </div>
</div>

<script>
let rowCounter = ' . $minRows . ';

function addRows(count) {
    const tbody = document.getElementById("guestTableBody");
    for (let i = 0; i < count; i++) {
        rowCounter++;
        const row = createGuestRow(rowCounter);
        tbody.insertAdjacentHTML("beforeend", row);
    }
    updateRowNumbers();
}

function createGuestRow(num) {
    return `
        <tr>
            <td class="row-num">${num}</td>
            <td>
                <select name="guests[${num}][order_item_id]" class="form-control">
                    <option value="">Pilih Hotel...</option>
                    ' . getHotelOptionsJS($hotelItems) . '
                </select>
                <input type="hidden" name="guests[${num}][hotel_name]" value="">
            </td>
            <td><input type="text" name="guests[${num}][guest_name]" class="form-control" placeholder="Nama tamu..."></td>
            <td><input type="text" name="guests[${num}][room_number]" class="form-control" placeholder="601"></td>
            <td><input type="text" name="guests[${num}][room_type]" class="form-control" placeholder="Superior"></td>
            <td><input type="date" name="guests[${num}][check_in_date]" class="form-control"></td>
            <td><input type="date" name="guests[${num}][check_out_date]" class="form-control"></td>
            <td><button type="button" class="btn-delete" onclick="deleteRow(this)">🗑️</button></td>
        </tr>
    `;
}

function deleteRow(btn) {
    btn.closest("tr").remove();
    updateRowNumbers();
}

function updateRowNumbers() {
    const rows = document.querySelectorAll("#guestTableBody tr");
    rows.forEach((row, index) => {
        row.querySelector(".row-num").textContent = index + 1;
    });
}

// Auto-fill hotel name when selecting from dropdown
document.addEventListener("change", function(e) {
    if (e.target.matches("select[name*=order_item_id]")) {
        const select = e.target;
        const hotelNameInput = select.closest("td").querySelector("input[name*=hotel_name]");
        const selectedOption = select.options[select.selectedIndex];
        if (hotelNameInput) {
            hotelNameInput.value = selectedOption.text !== "Pilih Hotel..." ? selectedOption.text : "";
        }
    }
});
</script>

<style>
.container { max-width: 1400px; margin: 0 auto; padding: 20px; }
.page-header { display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 30px; flex-wrap: wrap; gap: 20px; }
.header-left h1 { font-size: 24px; margin: 10px 0 5px; }
.header-left .subtitle { color: #6b7280; font-size: 14px; }
.header-actions { display: flex; gap: 10px; }
.btn-back { color: #6b7280; text-decoration: none; font-size: 14px; }
.btn-back:hover { color: #111827; }

.section { background: white; border-radius: 12px; padding: 25px; margin-bottom: 25px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); }
.section h2 { font-size: 18px; margin-bottom: 5px; }
.section-desc { color: #6b7280; font-size: 13px; margin-bottom: 20px; }

.alert { padding: 12px 16px; border-radius: 8px; margin-bottom: 20px; font-size: 14px; }
.alert-success { background: #d1fae5; color: #065f46; }
.alert-error { background: #fee2e2; color: #991b1b; }

.btn { padding: 10px 20px; border: none; border-radius: 8px; font-size: 14px; font-weight: 600; cursor: pointer; text-decoration: none; display: inline-block; transition: all 0.2s; }
.btn-primary { background: #dc2626; color: white; }
.btn-primary:hover { background: #b91c1c; }
.btn-secondary { background: #f3f4f6; color: #374151; }
.btn-secondary:hover { background: #e5e7eb; }
.btn-sm { padding: 6px 12px; font-size: 12px; }
.btn-lg { padding: 14px 28px; font-size: 16px; }

.hotel-card { background: #f9fafb; border-radius: 10px; padding: 20px; margin-bottom: 15px; border: 1px solid #e5e7eb; }
.hotel-header { display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 15px; flex-wrap: wrap; gap: 15px; }
.hotel-info h3 { font-size: 16px; margin-bottom: 4px; }
.hotel-info p { font-size: 13px; color: #6b7280; }
.hotel-dates { display: flex; align-items: center; gap: 8px; }
.date-badge { background: white; padding: 6px 12px; border-radius: 6px; font-size: 12px; font-weight: 600; border: 1px solid #e5e7eb; }
.date-arrow { color: #9ca3af; }

.hotel-edit-form { margin-bottom: 15px; padding-bottom: 15px; border-bottom: 1px solid #e5e7eb; }
.form-row { display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 12px; }
.form-group label { display: block; font-size: 11px; color: #6b7280; margin-bottom: 4px; text-transform: uppercase; }
.form-group input { width: 100%; padding: 8px 10px; border: 1px solid #e5e7eb; border-radius: 6px; font-size: 13px; }
.form-group input:focus { outline: none; border-color: #dc2626; }

.hotel-stats { display: flex; gap: 15px; flex-wrap: wrap; }
.stat { font-size: 12px; color: #6b7280; background: white; padding: 6px 12px; border-radius: 6px; }
.stat-ok { background: #d1fae5; color: #065f46; }
.stat-warning { background: #fef3c7; color: #92400e; }

.guest-table-wrapper { overflow-x: auto; margin-bottom: 20px; }
.guest-table { width: 100%; border-collapse: collapse; min-width: 900px; }
.guest-table th { background: #111827; color: white; padding: 12px 8px; text-align: left; font-size: 11px; text-transform: uppercase; font-weight: 600; }
.guest-table td { padding: 8px; border-bottom: 1px solid #f3f4f6; }
.guest-table tbody tr:hover { background: #fafafa; }
.guest-table .row-num { text-align: center; color: #9ca3af; font-weight: 600; }
.guest-table .form-control { width: 100%; padding: 8px; border: 1px solid #e5e7eb; border-radius: 6px; font-size: 12px; }
.guest-table .form-control:focus { outline: none; border-color: #dc2626; }
.guest-table select.form-control { min-width: 150px; }
.btn-delete { background: none; border: none; cursor: pointer; font-size: 16px; opacity: 0.5; }
.btn-delete:hover { opacity: 1; }

.form-actions { display: flex; gap: 10px; justify-content: flex-end; flex-wrap: wrap; }

.empty-state { text-align: center; padding: 40px; color: #6b7280; background: #fef2f2; border-radius: 10px; }
.empty-state p { margin-bottom: 10px; }

@media (max-width: 768px) {
    .page-header { flex-direction: column; }
    .form-row { grid-template-columns: 1fr; }
    .form-actions { flex-direction: column; }
    .form-actions .btn { width: 100%; }
}
</style>';

echo view('layouts/app', ['content' => $content, 'pageTitle' => $pageTitle]);

// Helper functions
function renderGuestRow($num, $guest, $hotelItems) {
    $html = '<tr>
        <td class="row-num">' . $num . '</td>
        <td>
            <select name="guests[' . $num . '][order_item_id]" class="form-control">
                <option value="">Pilih Hotel...</option>';
    
    foreach ($hotelItems as $item) {
        $label = $item['hotel_name'] ?: $item['description'];
        $selected = ($guest && $guest['order_item_id'] == $item['id']) ? 'selected' : '';
        $html .= '<option value="' . $item['id'] . '" ' . $selected . '>' . e($label) . '</option>';
    }
    
    $html .= '</select>
            <input type="hidden" name="guests[' . $num . '][hotel_name]" value="' . ($guest ? e($guest['hotel_name']) : '') . '">
        </td>
        <td><input type="text" name="guests[' . $num . '][guest_name]" class="form-control" value="' . ($guest ? e($guest['guest_name']) : '') . '" placeholder="Nama tamu..."></td>
        <td><input type="text" name="guests[' . $num . '][room_number]" class="form-control" value="' . ($guest ? e($guest['room_number']) : '') . '" placeholder="601"></td>
        <td><input type="text" name="guests[' . $num . '][room_type]" class="form-control" value="' . ($guest ? e($guest['room_type']) : '') . '" placeholder="Superior"></td>
        <td><input type="date" name="guests[' . $num . '][check_in_date]" class="form-control" value="' . ($guest ? e($guest['check_in_date']) : '') . '"></td>
        <td><input type="date" name="guests[' . $num . '][check_out_date]" class="form-control" value="' . ($guest ? e($guest['check_out_date']) : '') . '"></td>
        <td><button type="button" class="btn-delete" onclick="deleteRow(this)">🗑️</button></td>
    </tr>';
    
    return $html;
}

function getHotelOptionsJS($hotelItems) {
    $options = '';
    foreach ($hotelItems as $item) {
        $label = $item['hotel_name'] ?: $item['description'];
        $options .= '<option value="' . $item['id'] . '">' . e($label) . '</option>';
    }
    return $options;
}
