<?php
/**
 * Lampiran Invoice Hotel - List Tamu
 * URL: /doc-hotel-attachment.php?order_id=1
 */

error_reporting(E_ALL);
ini_set('display_errors', 0);
date_default_timezone_set('Asia/Jakarta');

define('BASE_PATH', __DIR__);
require_once BASE_PATH . '/config/constants.php';
require_once BASE_PATH . '/config/database.php';
require_once BASE_PATH . '/config/session.php';
Session::start();

// Lampiran ini berisi data tamu (nama, no. kamar) per order — jangan biarkan
// siapa pun yang tahu/menebak URL-nya mengaksesnya tanpa login, dan jangan
// biarkan satu company mengintip order company lain.
if (!Session::isLoggedIn()) {
    http_response_code(403);
    die('Akses ditolak. Silakan login terlebih dahulu.');
}

$orderId = isset($_GET['order_id']) ? (int)$_GET['order_id'] : 0;
if (!$orderId) die('Order ID required');

$order = db()->fetchOne("SELECT o.*, c.name as client_name FROM orders o LEFT JOIN clients c ON o.client_id = c.id WHERE o.id = ?", [$orderId]);
if (!$order || (int) $order['company_id'] !== (int) Session::companyId()) {
    http_response_code(404);
    die('Order not found');
}

$company = db()->fetchOne("SELECT * FROM companies WHERE id = ?", [$order['company_id']]);
if (!$company) $company = [];

// Get hotel items
$hotelItems = db()->fetchAll("SELECT * FROM order_items WHERE order_id = ? AND item_type = 'hotel' ORDER BY id", [$orderId]);

// Get all guests
$guests = db()->fetchAll("SELECT * FROM hotel_guests WHERE order_id = ? ORDER BY order_item_id, id", [$orderId]);

// Group guests by hotel
$guestsByHotel = [];
foreach ($hotelItems as $item) {
    $guestsByHotel[$item['id']] = [
        'hotel' => $item,
        'guests' => []
    ];
}
foreach ($guests as $g) {
    if (isset($guestsByHotel[$g['order_item_id']])) {
        $guestsByHotel[$g['order_item_id']]['guests'][] = $g;
    }
}

// Helpers
function tgl($d) {
    if (!$d) return '-';
    $b = [1=>'Januari',2=>'Februari',3=>'Maret',4=>'April',5=>'Mei',6=>'Juni',7=>'Juli',8=>'Agustus',9=>'September',10=>'Oktober',11=>'November',12=>'Desember'];
    return date('j', strtotime($d)) . ' ' . $b[(int)date('n', strtotime($d))] . ' ' . date('Y', strtotime($d));
}
function e($s) { return htmlspecialchars($s ? $s : ''); }

$cn = isset($company['name']) ? $company['name'] : 'PT Pelita Inti Mulia';
$ca = isset($company['address']) ? $company['address'] : '';
$cp = isset($company['phone']) ? $company['phone'] : '';
$ce = isset($company['email']) ? $company['email'] : '';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>List Tamu - <?php echo e($order['client_name']); ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Sora:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Sora', sans-serif; background: #f0f0f0; padding: 20px; }
        
        .no-print { position: fixed; top: 20px; right: 20px; display: flex; gap: 10px; z-index: 100; }
        .btn { padding: 12px 24px; border: none; border-radius: 8px; font-weight: 600; cursor: pointer; text-decoration: none; display: inline-block; font-size: 14px; font-family: inherit; }
        .btn-back { background: #6b7280; color: white; }
        .btn-print { background: #dc2626; color: white; }

        .page { width: 210mm; min-height: 297mm; margin: 0 auto 20px; background: white; box-shadow: 0 5px 30px rgba(0,0,0,0.1); position: relative; padding: 0; page-break-after: always; }
        .page:last-child { page-break-after: auto; }

        .header { background: linear-gradient(135deg, #dc2626 0%, #991b1b 100%); color: white; padding: 25px 40px; position: relative; }
        .header-content { display: flex; justify-content: space-between; align-items: center; }
        .logo-area { display: flex; align-items: center; gap: 15px; }
        .logo-circle { width: 50px; height: 50px; background: rgba(255,255,255,0.15); border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 22px; }
        .logo-text h1 { font-size: 20px; margin-bottom: 3px; }
        .logo-text p { font-size: 9px; opacity: 0.9; }
        .doc-title { text-align: right; }
        .doc-title h2 { font-size: 24px; font-weight: 800; }
        .doc-title p { font-size: 11px; opacity: 0.9; }
        
        .wave { position: absolute; bottom: -1px; left: 0; right: 0; }
        .wave svg { display: block; width: 100%; height: 30px; }

        .content { padding: 30px 40px; }

        .hotel-section { margin-bottom: 30px; }
        .hotel-header { background: linear-gradient(135deg, #f9fafb, #f3f4f6); border-radius: 10px; padding: 15px 20px; margin-bottom: 15px; display: flex; justify-content: space-between; align-items: center; border-left: 4px solid #dc2626; }
        .hotel-name { font-size: 16px; font-weight: 700; color: #111827; margin-bottom: 3px; }
        .hotel-info { font-size: 11px; color: #6b7280; }
        .hotel-dates { text-align: right; }
        .hotel-dates .label { font-size: 9px; color: #9ca3af; text-transform: uppercase; }
        .hotel-dates .value { font-size: 12px; font-weight: 600; color: #374151; }

        .guest-table { width: 100%; border-collapse: collapse; font-size: 10px; }
        .guest-table thead tr { background: linear-gradient(135deg, #111827, #1f2937); color: white; }
        .guest-table th { padding: 10px 8px; text-align: left; font-size: 8px; text-transform: uppercase; font-weight: 700; letter-spacing: 0.5px; }
        .guest-table th:first-child { width: 35px; text-align: center; }
        .guest-table td { padding: 10px 8px; border-bottom: 1px solid #f3f4f6; color: #374151; }
        .guest-table td:first-child { text-align: center; color: #9ca3af; font-weight: 600; }
        .guest-table tbody tr:hover { background: #fafafa; }
        .room-badge { background: #dc2626; color: white; padding: 3px 8px; border-radius: 4px; font-size: 9px; font-weight: 600; }
        .room-type-badge { background: #f3f4f6; color: #374151; padding: 3px 8px; border-radius: 4px; font-size: 9px; }

        .guest-names { line-height: 1.6; }
        .guest-names .main { font-weight: 600; color: #111827; }
        .guest-names .secondary { color: #6b7280; font-size: 9px; }

        .footer { position: absolute; bottom: 0; left: 0; right: 0; background: #111827; padding: 12px 40px; }
        .footer-content { display: flex; justify-content: space-between; align-items: center; color: white; font-size: 9px; }
        .footer-content a { color: #9ca3af; text-decoration: none; }

        .summary-box { background: #fef2f2; border: 1px solid #fee2e2; border-radius: 10px; padding: 15px 20px; margin-top: 20px; display: flex; justify-content: space-between; }
        .summary-item { text-align: center; }
        .summary-item .label { font-size: 9px; color: #9ca3af; text-transform: uppercase; margin-bottom: 3px; }
        .summary-item .value { font-size: 18px; font-weight: 700; color: #dc2626; }

        @media print {
            body { background: white; padding: 0; }
            .no-print { display: none !important; }
            .page { box-shadow: none; margin: 0; width: 100%; }
            @page { size: A4; margin: 0; }
        }
    </style>
</head>
<body>
    <div class="no-print">
        <a href="hotel-guests.php?order_id=<?php echo $orderId; ?>" class="btn btn-back">← Edit Data</a>
        <button onclick="window.print()" class="btn btn-print">🖨️ Cetak</button>
    </div>

<?php 
$pageNum = 0;
foreach ($guestsByHotel as $hotelId => $data): 
    $hotel = $data['hotel'];
    $hotelGuests = $data['guests'];
    if (empty($hotelGuests)) continue;
    $pageNum++;
?>
    <div class="page">
        <div class="header">
            <div class="header-content">
                <div class="logo-area">
                    <div class="logo-circle">✈️</div>
                    <div class="logo-text">
                        <h1><?php echo e($cn); ?></h1>
                        <p><?php echo e($ca); ?></p>
                    </div>
                </div>
                <div class="doc-title">
                    <h2>LIST TAMU</h2>
                    <p><?php echo e($hotel['hotel_name'] ?: $hotel['description']); ?></p>
                </div>
            </div>
            <div class="wave">
                <svg viewBox="0 0 1200 30" preserveAspectRatio="none">
                    <path fill="#fff" d="M0,20 Q300,30 600,20 T1200,25 L1200,30 L0,30 Z"/>
                </svg>
            </div>
        </div>

        <div class="content">
            <div class="hotel-section">
                <div class="hotel-header">
                    <div>
                        <div class="hotel-name"><?php echo e($hotel['hotel_name'] ?: $hotel['description']); ?></div>
                        <div class="hotel-info">Perjalanan Dinas: <?php echo e($order['client_name']); ?></div>
                    </div>
                    <div class="hotel-dates">
                        <div><span class="label">Check-in</span><br><span class="value"><?php echo tgl($hotel['check_in_date']); ?></span></div>
                        <div style="margin-top:8px"><span class="label">Check-out</span><br><span class="value"><?php echo tgl($hotel['check_out_date']); ?></span></div>
                    </div>
                </div>

                <table class="guest-table">
                    <thead>
                        <tr>
                            <th>No</th>
                            <th>Nama Hotel</th>
                            <th>Nama Tamu</th>
                            <th>No. Room</th>
                            <th>Type Room</th>
                            <th>Tanggal Masuk</th>
                            <th>Tanggal Keluar</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php 
                    $no = 1;
                    $currentRoom = '';
                    foreach ($hotelGuests as $idx => $guest): 
                        // Check if this is a shared room (same room number as previous)
                        $isSecondGuest = ($idx > 0 && $guest['room_number'] == $hotelGuests[$idx-1]['room_number']);
                    ?>
                        <tr>
                            <td><?php echo $no++; ?></td>
                            <td><?php echo e($hotel['hotel_name'] ?: $hotel['description']); ?></td>
                            <td>
                                <div class="guest-names">
                                    <span class="<?php echo $isSecondGuest ? 'secondary' : 'main'; ?>"><?php echo e($guest['guest_name']); ?></span>
                                </div>
                            </td>
                            <td><span class="room-badge"><?php echo e($guest['room_number']); ?></span></td>
                            <td><span class="room-type-badge"><?php echo e($guest['room_type'] ?: $hotel['room_type']); ?></span></td>
                            <td><?php echo tgl($guest['check_in_date'] ?: $hotel['check_in_date']); ?></td>
                            <td><?php echo tgl($guest['check_out_date'] ?: $hotel['check_out_date']); ?></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>

                <div class="summary-box">
                    <div class="summary-item">
                        <div class="label">Total Kamar</div>
                        <div class="value"><?php echo $hotel['quantity']; ?></div>
                    </div>
                    <div class="summary-item">
                        <div class="label">Total Tamu</div>
                        <div class="value"><?php echo count($hotelGuests); ?></div>
                    </div>
                    <div class="summary-item">
                        <div class="label">Durasi Menginap</div>
                        <div class="value">
                            <?php 
                            if ($hotel['check_in_date'] && $hotel['check_out_date']) {
                                $diff = (strtotime($hotel['check_out_date']) - strtotime($hotel['check_in_date'])) / 86400;
                                echo (int)$diff . ' Malam';
                            } else {
                                echo '-';
                            }
                            ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="footer">
            <div class="footer-content">
                <span><?php echo e($cn); ?></span>
                <span>📧 <?php echo e($ce); ?> | 📞 <?php echo e($cp); ?></span>
            </div>
        </div>
    </div>
<?php endforeach; ?>

<?php if ($pageNum == 0): ?>
    <div class="page" style="display:flex; align-items:center; justify-content:center; min-height:297mm;">
        <div style="text-align:center; color:#6b7280;">
            <p style="font-size:48px; margin-bottom:20px;">📋</p>
            <p style="font-size:18px; margin-bottom:10px;">Belum ada data tamu</p>
            <p style="font-size:13px;">Silakan isi data tamu terlebih dahulu di halaman <a href="hotel-guests.php?order_id=<?php echo $orderId; ?>">Hotel Guests</a></p>
        </div>
    </div>
<?php endif; ?>

</body>
</html>
