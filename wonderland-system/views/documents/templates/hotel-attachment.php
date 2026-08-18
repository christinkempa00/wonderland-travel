<?php
/**
 * Hotel Attachment Print Template - v4
 * Separate page per hotel with logo
 */

// Helper function for escaping
if (!function_exists('e')) {
    function e($str) {
        return htmlspecialchars($str ?? '', ENT_QUOTES, 'UTF-8');
    }
}

// Helper function for date formatting
function tglFormat($date) {
    if (!$date) return '-';
    $months = ['', 'Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'];
    $d = date('j', strtotime($date));
    $m = $months[(int)date('n', strtotime($date))];
    $y = date('Y', strtotime($date));
    return "$d $m $y";
}

// Get data
$orderNumber = is_object($order) ? $order->order_number : ($order['order_number'] ?? '');
$companyLogo = $company['logo'] ?? '';
$clientName = $client['name'] ?? '-';

// Prepare room data per hotel
$hotelRoomData = [];
foreach ($hotelItems as $hotel) {
    $hotelGuests = $guestsByHotel[$hotel['id']] ?? [];
    $roomGroups = [];
    
    foreach ($hotelGuests as $guest) {
        $roomKey = $guest['room_number'] ?: 'single_' . $guest['id'];
        
        if (!isset($roomGroups[$roomKey])) {
            $roomGroups[$roomKey] = [
                'room_number' => $guest['room_number'],
                'room_type' => $guest['room_type'] ?: $hotel['room_type'],
                'check_in' => $guest['check_in_date'] ?: $hotel['check_in_date'],
                'check_out' => $guest['check_out_date'] ?: $hotel['check_out_date'],
                'guest_1' => '',
                'guest_2' => ''
            ];
        }
        
        if (empty($roomGroups[$roomKey]['guest_1'])) {
            $roomGroups[$roomKey]['guest_1'] = $guest['guest_name'];
        } elseif (empty($roomGroups[$roomKey]['guest_2'])) {
            $roomGroups[$roomKey]['guest_2'] = $guest['guest_name'];
        }
    }
    
    $hotelRoomData[$hotel['id']] = [
        'hotel' => $hotel,
        'rooms' => array_values($roomGroups)
    ];
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lampiran Daftar Tamu - <?= e($orderNumber) ?></title>
    <style>
        @page {
            size: A4;
            margin: 15mm 15mm 20mm 15mm;
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Segoe UI', Arial, sans-serif;
            font-size: 11px;
            line-height: 1.4;
            color: #1f2937;
            background: #f3f4f6;
        }
        
        .page {
            width: 210mm;
            min-height: 297mm;
            margin: 0 auto 20px;
            background: white;
            padding: 15mm;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
            page-break-after: always;
        }
        
        .page:last-child {
            page-break-after: auto;
        }
        
        /* Header - Logo kiri, Badge kanan */
        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding-bottom: 15px;
            margin-bottom: 15px;
            border-bottom: 2px solid #e5e7eb;
        }
        
        .header-logo {
            max-height: 60px;
            max-width: 200px;
            width: auto;
            height: auto;
            object-fit: contain;
        }
        
        .header-logo-placeholder {
            width: 120px;
            height: 50px;
            background: #f3f4f6;
            border: 1px dashed #d1d5db;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #9ca3af;
            font-size: 10px;
            border-radius: 4px;
        }
        
        .header-badge {
            background: linear-gradient(135deg, #374151, #1f2937);
            color: white;
            padding: 10px 20px;
            border-radius: 8px;
            font-size: 14px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        /* Hotel Info Section */
        .hotel-info {
            background: #f8fafc;
            border: 1px solid #e5e7eb;
            border-radius: 8px;
            padding: 12px 15px;
            margin-bottom: 15px;
        }
        
        .hotel-name {
            font-size: 16px;
            font-weight: 700;
            color: #1f2937;
            margin-bottom: 5px;
        }
        
        .hotel-meta {
            display: flex;
            gap: 15px;
            font-size: 10px;
            color: #6b7280;
        }
        
        .hotel-meta span {
            display: flex;
            align-items: center;
            gap: 4px;
        }
        
        /* Table */
        .guest-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 15px;
            font-size: 10px;
        }
        
        .guest-table th {
            background: linear-gradient(135deg, #374151, #1f2937);
            color: white;
            padding: 10px 8px;
            font-weight: 600;
            text-align: left;
            font-size: 9px;
            text-transform: uppercase;
            letter-spacing: 0.3px;
        }
        
        .guest-table th:first-child {
            text-align: center;
            border-radius: 8px 0 0 0;
            width: 35px;
        }
        
        .guest-table th:last-child {
            border-radius: 0 8px 0 0;
        }
        
        .guest-table td {
            padding: 10px 8px;
            border-bottom: 1px solid #e5e7eb;
            color: #374151;
            vertical-align: middle;
        }
        
        .guest-table td:first-child {
            text-align: center;
            color: #6b7280;
            font-weight: 600;
        }
        
        .guest-table tr.room-start td {
            border-top: 2px solid #d1d5db;
        }
        
        .guest-table tbody tr:first-child td {
            border-top: none;
        }
        
        .guest-table tbody tr:last-child td {
            border-bottom: 2px solid #e5e7eb;
        }
        
        .guest-table tbody tr:last-child td:first-child {
            border-radius: 0 0 0 8px;
        }
        
        .guest-table tbody tr:last-child td:last-child {
            border-radius: 0 0 8px 0;
        }
        
        /* Guest name styling */
        .guest-table .guest-name {
            font-weight: 500;
            text-align: left !important;
        }
        
        .room-badge {
            background: #1f2937;
            color: white;
            padding: 4px 10px;
            border-radius: 4px;
            font-size: 9px;
            font-weight: 600;
            display: inline-block;
        }
        
        .room-type-badge {
            background: #e5e7eb;
            color: #374151;
            padding: 4px 10px;
            border-radius: 4px;
            font-size: 9px;
            font-weight: 500;
            display: inline-block;
        }
        
        /* Footer */
        .footer {
            margin-top: auto;
            padding-top: 15px;
            border-top: 1px solid #e5e7eb;
            display: flex;
            justify-content: space-between;
            font-size: 9px;
            color: #9ca3af;
        }
        
        /* No data state */
        .no-data {
            text-align: center;
            padding: 40px 20px;
            color: #9ca3af;
        }
        
        /* Print button bar */
        .print-bar {
            background: #f3f4f6;
            padding: 15px 20px;
            display: flex;
            justify-content: center;
            gap: 15px;
            position: sticky;
            top: 0;
            z-index: 100;
        }
        
        .print-bar button {
            padding: 10px 24px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-size: 13px;
            font-weight: 600;
            transition: all 0.2s;
        }
        
        .btn-print {
            background: #1f2937;
            color: white;
        }
        
        .btn-print:hover {
            background: #111827;
        }
        
        .btn-back {
            background: white;
            color: #374151;
            border: 1px solid #d1d5db !important;
        }
        
        .btn-back:hover {
            background: #f9fafb;
        }
        
        /* Print styles */
        @media print {
            body {
                background: white;
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }
            
            .page {
                width: 100%;
                min-height: auto;
                margin: 0;
                padding: 0;
                box-shadow: none;
            }
            
            .print-bar {
                display: none !important;
            }
            
            .guest-table {
                page-break-inside: auto;
            }
            
            .guest-table tr {
                page-break-inside: avoid;
            }
            
            .guest-table thead {
                display: table-header-group;
            }
        }
        
        @media screen {
            body {
                padding: 20px 0;
            }
        }
    </style>
</head>
<body>
    <!-- Print Bar -->
    <div class="print-bar">
        <button class="btn-print" onclick="window.print()">
            🖨️ Cetak Lampiran
        </button>
        <button class="btn-back" onclick="history.back()">
            ← Kembali
        </button>
    </div>

    <?php 
    foreach ($hotelItems as $hotel): 
        $hotelData = $hotelRoomData[$hotel['id']] ?? null;
        $rooms = $hotelData['rooms'] ?? [];
    ?>
    <!-- Page for: <?= e($hotel['hotel_name'] ?: $hotel['description']) ?> -->
    <div class="page">
        <!-- Header: Logo + Badge -->
        <div class="header">
            <?php 
            $hotelLogo = $hotel['attachment_logo'] ?? '';
            if ($hotelLogo && file_exists(BASE_PATH . '/uploads/logos/' . $hotelLogo)): 
            ?>
            <img src="<?= url('/uploads/logos/' . $hotelLogo) ?>" alt="Logo" class="header-logo">
            <?php else: ?>
            <div class="header-logo-placeholder">Logo Hotel</div>
            <?php endif; ?>
            <div class="header-badge">Lampiran Daftar Tamu</div>
        </div>
        
        <!-- Hotel Info -->
        <div class="hotel-info">
            <div class="hotel-name"><?= e($hotel['hotel_name'] ?: $hotel['description']) ?></div>
            <div class="hotel-meta">
                <span>📋 Order: <?= e($orderNumber) ?></span>
                <span>👤 Klien: <?= e($clientName) ?></span>
                <?php if ($hotel['check_in_date']): ?>
                <span>📅 <?= tglFormat($hotel['check_in_date']) ?> - <?= tglFormat($hotel['check_out_date']) ?></span>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- Guest Table -->
        <?php if (empty($rooms)): ?>
        <div class="no-data">
            <div style="font-size: 36px; margin-bottom: 10px; opacity: 0.3;">📋</div>
            <p>Belum ada data tamu untuk hotel ini</p>
        </div>
        <?php else: ?>
        <table class="guest-table">
            <thead>
                <tr>
                    <th>No</th>
                    <th>No. Room</th>
                    <th>Type Room</th>
                    <th>Nama Tamu</th>
                    <th>Check-in</th>
                    <th>Check-out</th>
                </tr>
            </thead>
            <tbody>
                <?php $no = 1; foreach ($rooms as $room): 
                    $hasGuest2 = !empty($room['guest_2']);
                    $rowspan = $hasGuest2 ? 2 : 1;
                ?>
                <tr class="room-start">
                    <td rowspan="<?= $rowspan ?>"><?= $no++ ?></td>
                    <td rowspan="<?= $rowspan ?>">
                        <?php if (!empty($room['room_number'])): ?>
                        <span class="room-badge"><?= e($room['room_number']) ?></span>
                        <?php else: ?>-<?php endif; ?>
                    </td>
                    <td rowspan="<?= $rowspan ?>">
                        <?php if (!empty($room['room_type'])): ?>
                        <span class="room-type-badge"><?= e($room['room_type']) ?></span>
                        <?php else: ?>-<?php endif; ?>
                    </td>
                    <td class="guest-name">1. <?= e($room['guest_1']) ?></td>
                    <td rowspan="<?= $rowspan ?>"><?= tglFormat($room['check_in']) ?></td>
                    <td rowspan="<?= $rowspan ?>"><?= tglFormat($room['check_out']) ?></td>
                </tr>
                <?php if ($hasGuest2): ?>
                <tr>
                    <td class="guest-name">2. <?= e($room['guest_2']) ?></td>
                </tr>
                <?php endif; ?>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php endif; ?>
        
        <!-- Footer -->
        <div class="footer">
            <span>Dicetak: <?= date('d/m/Y H:i') ?></span>
            <span>Total: <?= count($rooms) ?> room</span>
        </div>
    </div>
    <?php endforeach; ?>
</body>
</html>