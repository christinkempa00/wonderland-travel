<?php
/**
 * Document Generator - PIM Template - FIXED v5
 * URL: /doc.php?order=1&type=invoice
 * 
 * KOREKSI v5:
 * - Fix signature margin bug
 * - Adaptive sizing: menyesuaikan ukuran berdasarkan jumlah item
 * - Grouping item dengan deskripsi dan jenis layanan yang sama
 * - Header tabel kendaraan: Jenis Layanan | Jenis Kendaraan | Harga Sewa
 * - Format harga: Rp di kiri, nominal di kanan
 * - Auto-fit untuk A4
 */

error_reporting(E_ALL);
ini_set('display_errors', 0);
date_default_timezone_set('Asia/Jakarta');

define('BASE_PATH', __DIR__);
require_once BASE_PATH . '/config/database.php';

$orderId = isset($_GET['order']) ? (int)$_GET['order'] : 0;
$docType = isset($_GET['type']) ? $_GET['type'] : 'invoice';

if (!$orderId) die('Usage: doc.php?order=ID&type=invoice|kwitansi|penawaran|kesepakatan');

$order = db()->fetchOne("SELECT * FROM orders WHERE id = ?", [$orderId]);
if (!$order) die('Order not found');

$items = db()->fetchAll("SELECT * FROM order_items WHERE order_id = ?", [$orderId]);
$client = !empty($order['client_id']) ? db()->fetchOne("SELECT * FROM clients WHERE id = ?", [$order['client_id']]) : [];
$company = db()->fetchOne("SELECT * FROM companies WHERE id = ?", [$order['company_id']]);
if (!$company) $company = [];
if (!$client) $client = [];

// ============================================
// HITUNG ULANG SEMUA NILAI DARI DATA ITEM
// ============================================
$calculatedItems = [];
$calcTotalBase = 0;
$calcTotalMarkup = 0;
$calcTotalFinal = 0;

foreach ($items as $item) {
    $basePrice = (float)($item['base_price'] ?? 0);
    $cashback = (float)($item['cashback'] ?? 0);
    $quantity = (int)($item['quantity'] ?? 1);
    $numDays = (int)($item['num_days'] ?? 1);
    $markupType = $item['markup_type'] ?? 'percentage';
    $markupValue = (float)($item['markup_value'] ?? 0);

    // Markup dihitung dari (harga beli + cashback), bukan harga beli saja --
    // sama seperti OrderController::parseItems()/Order::recalculateTotals().
    $itemBaseTotal = $quantity * $numDays * $basePrice;
    $itemCashbackTotal = $quantity * $numDays * $cashback;
    $markupBaseTotal = $itemBaseTotal + $itemCashbackTotal;

    $markupAmount = 0;
    if ($markupType === 'percentage') {
        $markupAmount = $markupBaseTotal * ($markupValue / 100);
    } else {
        $markupAmount = $markupValue * $quantity * $numDays;
    }

    $finalPrice = $markupBaseTotal + $markupAmount;

    if ($markupType === 'percentage') {
        $unitPriceWithMarkup = ($basePrice + $cashback) + (($basePrice + $cashback) * ($markupValue / 100));
    } else {
        $unitPriceWithMarkup = ($basePrice + $cashback) + $markupValue;
    }

    $calculatedItems[] = array_merge($item, [
        'calc_base_total' => $itemBaseTotal,
        'calc_cashback_total' => $itemCashbackTotal,
        'calc_markup_amount' => $markupAmount,
        'calc_final_price' => $finalPrice,
        'calc_unit_price_with_markup' => $unitPriceWithMarkup,
    ]);

    $calcTotalBase += $itemBaseTotal;
    $calcTotalMarkup += $markupAmount;
    $calcTotalFinal += $finalPrice;
}

$items = $calculatedItems;
$total = $calcTotalFinal;

// Total peserta lintas item, dipakai di baris "Untuk Pembayaran" kwitansi
// (mis. "... ( 5 Orang )").
$totalParticipantQty = 0;
foreach ($items as $__pItem) {
    $totalParticipantQty += (int) ($__pItem['participant_qty'] ?? 0);
}
unset($__pItem);

// ============================================
// GROUPING ITEMS UNTUK PENAWARAN & KESEPAKATAN
// ============================================
$groupedItems = [];
foreach ($items as $item) {
    $key = $item['item_type'] . '|' . $item['description'];
    
    if (!isset($groupedItems[$key])) {
        $groupedItems[$key] = $item;
    }
}
$groupedItems = array_values($groupedItems);

// ============================================
// ADAPTIVE SIZING BERDASARKAN JUMLAH ITEM
// Semua nilai dalam angka (tanpa px)
// ============================================
$itemCount = count($groupedItems);

if ($itemCount <= 3) {
    $sizeClass = 'size-normal';
    $headerHeight = 200;
    $waveHeight = 100;
    $glassMarginTop = -70;
    $signatureMarginTop = 80;
} elseif ($itemCount <= 5) {
    $sizeClass = 'size-medium';
    $headerHeight = 160;
    $waveHeight = 80;
    $glassMarginTop = -50;
    $signatureMarginTop = 60;
} elseif ($itemCount <= 8) {
    $sizeClass = 'size-compact';
    $headerHeight = 130;
    $waveHeight = 60;
    $glassMarginTop = -40;
    $signatureMarginTop = 45;
} else {
    $sizeClass = 'size-mini';
    $headerHeight = 100;
    $waveHeight = 50;
    $glassMarginTop = -30;
    $signatureMarginTop = 35;
}

// Get service type from first item for "includes" list
$serviceType = (!empty($items) && isset($items[0]['item_type'])) ? $items[0]['item_type'] : 'bus';

// Get "Harga Sudah Termasuk" from database
$serviceIncludes = [];
try {
    $includesResult = db()->fetchAll("SELECT include_item FROM service_includes WHERE service_type = ? AND is_active = 1 ORDER BY sort_order", [$serviceType]);
} catch (Exception $e) {
    $includesResult = [];
}
if ($includesResult) {
    foreach ($includesResult as $inc) {
        $serviceIncludes[] = $inc['include_item'];
    }
}

if (empty($serviceIncludes)) {
    $serviceIncludes = ['Biaya Sewa Kendaraan', 'Jasa Driver', 'Bahan Bakar', 'Parkir', 'Toll', 'Asuransi'];
}

// Helpers
function rp($n) { return 'Rp ' . number_format((float)$n, 0, ',', '.'); }

function rpTable($n) {
    return '<span class="price-rp">Rp</span><span class="price-nominal">' . number_format((float)$n, 0, ',', '.') . '</span>';
}

function tgl($d) {
    $b = [1=>'Januari',2=>'Februari',3=>'Maret',4=>'April',5=>'Mei',6=>'Juni',7=>'Juli',8=>'Agustus',9=>'September',10=>'Oktober',11=>'November',12=>'Desember'];
    return date('j', strtotime($d)) . ' ' . $b[(int)date('n', strtotime($d))] . ' ' . date('Y', strtotime($d));
}

function bilang($n) {
    $n = abs((int)$n);
    $h = ["","Satu","Dua","Tiga","Empat","Lima","Enam","Tujuh","Delapan","Sembilan","Sepuluh","Sebelas"];
    if ($n < 12) return $h[$n];
    if ($n < 20) return bilang($n - 10) . " Belas";
    if ($n < 100) return bilang($n / 10) . " Puluh " . bilang($n % 10);
    if ($n < 200) return "Seratus " . bilang($n - 100);
    if ($n < 1000) return bilang($n / 100) . " Ratus " . bilang($n % 100);
    if ($n < 2000) return "Seribu " . bilang($n - 1000);
    if ($n < 1000000) return bilang($n / 1000) . " Ribu " . bilang($n % 1000);
    if ($n < 1000000000) return bilang($n / 1000000) . " Juta " . bilang($n % 1000000);
    return bilang($n / 1000000000) . " Miliar " . bilang($n % 1000000000);
}

function e($s) { return htmlspecialchars($s ? $s : ''); }

function getRandomMarkup() {
    $options = [50000, 75000, 100000, 125000, 150000, 175000, 200000];
    return $options[array_rand($options)];
}

function getLogoUrl($company) {
    if (!empty($company['logo'])) {
        if (strpos($company['logo'], 'http') === 0) {
            return $company['logo'];
        }
        return '/uploads/' . $company['logo'];
    }
    return null;
}

$types = ['bus'=>'Big Bus','medium_bus'=>'Medium Bus','mini_bus'=>'Mini Bus','hiace'=>'Hiace','elf'=>'Elf','towing'=>'Towing','hotel'=>'Hotel','flight'=>'Tiket Pesawat','rental'=>'Rental Mobil','restaurant'=>'Restoran','travel'=>'Travel','other'=>'Lainnya'];
$prefixes = ['invoice'=>'INV','kwitansi'=>'KWT','penawaran'=>'PNW','kesepakatan'=>'KSP'];
$titles = ['invoice'=>'INVOICE','kwitansi'=>'KWITANSI','penawaran'=>'SURAT PENAWARAN','kesepakatan'=>'SURAT KESEPAKATAN'];

// ============================================
// DETEKSI JENIS LAYANAN UNTUK NARASI DINAMIS
// ============================================
$vehicleTypes = ['bus', 'medium_bus', 'mini_bus', 'hiace', 'elf', 'towing', 'rental'];
$hotelTypes = ['hotel'];
$flightTypes = ['flight'];
$restaurantTypes = ['restaurant'];
$travelTypes = ['travel'];

$hasVehicle = false;
$hasHotel = false;
$hasFlight = false;
$hasRestaurant = false;
$hasTravel = false;
$hasOther = false;

foreach ($items as $item) {
    $itemType = $item['item_type'] ?? 'other';
    if (in_array($itemType, $vehicleTypes)) $hasVehicle = true;
    elseif (in_array($itemType, $hotelTypes)) $hasHotel = true;
    elseif (in_array($itemType, $flightTypes)) $hasFlight = true;
    elseif (in_array($itemType, $restaurantTypes)) $hasRestaurant = true;
    elseif (in_array($itemType, $travelTypes)) $hasTravel = true;
    else $hasOther = true;
}

$categoryCount = ($hasVehicle ? 1 : 0) + ($hasHotel ? 1 : 0) + ($hasFlight ? 1 : 0) + ($hasRestaurant ? 1 : 0) + ($hasTravel ? 1 : 0) + ($hasOther ? 1 : 0);

function getServiceNarration($hasVehicle, $hasHotel, $hasFlight, $hasRestaurant, $hasTravel, $hasOther, $categoryCount) {
    if ($categoryCount == 1) {
        if ($hasVehicle) return ['penawaran' => 'sewa kendaraan', 'kesepakatan' => 'pemakaian kendaraan'];
        if ($hasHotel) return ['penawaran' => 'akomodasi hotel', 'kesepakatan' => 'akomodasi hotel'];
        if ($hasFlight) return ['penawaran' => 'tiket pesawat', 'kesepakatan' => 'tiket pesawat'];
        if ($hasRestaurant) return ['penawaran' => 'layanan restoran', 'kesepakatan' => 'layanan restoran'];
        if ($hasTravel) return ['penawaran' => 'layanan travel', 'kesepakatan' => 'layanan travel'];
        return ['penawaran' => 'layanan', 'kesepakatan' => 'layanan'];
    }
    
    $services = [];
    if ($hasVehicle) $services[] = 'sewa kendaraan';
    if ($hasHotel) $services[] = 'akomodasi hotel';
    if ($hasFlight) $services[] = 'tiket pesawat';
    if ($hasRestaurant) $services[] = 'layanan restoran';
    if ($hasTravel) $services[] = 'layanan travel';
    if ($hasOther) $services[] = 'layanan lainnya';
    
    if (count($services) == 2) {
        $narration = $services[0] . ' dan ' . $services[1];
    } elseif (count($services) > 2) {
        $lastService = array_pop($services);
        $narration = implode(', ', $services) . ', dan ' . $lastService;
    } else {
        $narration = 'layanan travel';
    }
    
    return ['penawaran' => $narration, 'kesepakatan' => $narration];
}

function getTableHeaders($hasVehicle, $hasHotel, $hasFlight, $hasRestaurant, $hasTravel, $hasOther, $categoryCount) {
    if ($categoryCount == 1) {
        if ($hasVehicle) return ['col1' => 'Jenis Layanan', 'col2' => 'Jenis Kendaraan', 'col3' => 'Harga Sewa'];
        if ($hasHotel) return ['col1' => 'Akomodasi', 'col2' => 'Keterangan', 'col3' => 'Harga'];
        if ($hasFlight) return ['col1' => 'Penerbangan', 'col2' => 'Rute', 'col3' => 'Harga'];
        if ($hasRestaurant) return ['col1' => 'Restoran', 'col2' => 'Keterangan', 'col3' => 'Harga'];
        if ($hasTravel) return ['col1' => 'Layanan', 'col2' => 'Keterangan', 'col3' => 'Harga'];
    }
    return ['col1' => 'Layanan', 'col2' => 'Keterangan', 'col3' => 'Harga'];
}

$serviceNarration = getServiceNarration($hasVehicle, $hasHotel, $hasFlight, $hasRestaurant, $hasTravel, $hasOther, $categoryCount);
$tableHeaders = getTableHeaders($hasVehicle, $hasHotel, $hasFlight, $hasRestaurant, $hasTravel, $hasOther, $categoryCount);

$cn = isset($company['name']) ? $company['name'] : 'PT Pelita Inti Mulia';
$ca = isset($company['address']) ? $company['address'] : '';
$cp = isset($company['phone']) ? $company['phone'] : '';
$ce = isset($company['email']) ? $company['email'] : '';
$cl = isset($client['name']) ? $client['name'] : 'Pelanggan';
$clAddr = isset($client['address']) ? $client['address'] : '';
$clPhone = isset($client['phone']) ? $client['phone'] : '';
$orderPicName = isset($order['pic_name']) ? $order['pic_name'] : '';
$orderPicPhone = isset($order['pic_phone']) ? $order['pic_phone'] : '';
// Label Divisi untuk klien PELNI (uses_divisi) — dipakai di tabel item invoice
// ("Team {label}") dan baris "Untuk Pembayaran" di kwitansi ("{label} PELNI").
$divisiLabels = ['JM' => 'JM', 'OFFICE' => 'Office', 'TIKOM' => 'Tikom'];
$orderDivisiLabel = !empty($order['divisi']) ? ($divisiLabels[$order['divisi']] ?? $order['divisi']) : '';
// Klien PELNI (uses_divisi) punya penomoran invoice persisten sendiri,
// dibuat sekali saat order dibuat/diedit — lihat Order::generatePelniInvoiceNumber().
// Dipakai juga di kwitansi (sesuai contoh template, No kwitansi = No invoice).
// Klien lain tetap pakai skema lama (dibuat ulang tiap render, tidak persisten).
if (($docType === 'invoice' || $docType === 'kwitansi') && !empty($order['divisi']) && !empty($order['pelni_invoice_number'])) {
    $docNum = $order['pelni_invoice_number'];
} else {
    $docNum = '#' . (isset($prefixes[$docType]) ? $prefixes[$docType] : 'DOC') . '-' . date('Y') . '-' . str_pad($order['id'], 5, '0', STR_PAD_LEFT);
}
$docTitle = isset($titles[$docType]) ? $titles[$docType] : 'DOKUMEN';

// event_date sering NULL (pesanan tanpa tanggal event) — jangan biarkan jatuh
// ke epoch (1 Jan 1970), pakai order_date sebagai dasar kalau kosong.
$eventDateBase = !empty($order['event_date']) ? strtotime($order['event_date']) : strtotime($order['order_date']);

if ($docType == 'penawaran' || $docType == 'kesepakatan') {
    $docDate = tgl($order['order_date']);
} elseif ($docType == 'invoice') {
    $invoiceDate = date('Y-m-d', strtotime('+1 day', $eventDateBase));
    $docDate = tgl($invoiceDate);
} else {
    $kwitansiDate = date('Y-m-d', strtotime('+2 days', $eventDateBase));
    $docDate = tgl($kwitansiDate);
}

$logoUrl = getLogoUrl($company);

$mainColor1 = '#dc2626';
$mainColor2 = '#991b1b';
$mainColor3 = '#7f1d1d';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $docTitle . ' ' . e($docNum); ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Sora:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Sora', sans-serif; background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%); padding: 20px; display: flex; flex-direction: column; align-items: center; min-height: 100vh; }
        
        .print-btn { position: fixed; top: 20px; right: 20px; background: linear-gradient(135deg, #dc2626 0%, #991b1b 100%); color: white; border: none; padding: 14px 28px; font-family: 'Sora', sans-serif; font-size: 14px; font-weight: 600; border-radius: 12px; cursor: pointer; display: flex; align-items: center; gap: 10px; box-shadow: 0 4px 20px rgba(220, 38, 38, 0.4); z-index: 1000; text-decoration: none; }
        .print-btn:hover { transform: translateY(-2px); }
        .back-btn { position: fixed; top: 20px; left: 20px; background: #6b7280; color: white; border: none; padding: 14px 28px; font-family: 'Sora', sans-serif; font-size: 14px; font-weight: 600; border-radius: 12px; cursor: pointer; text-decoration: none; z-index: 1000; }

        .invoice-container { width: 210mm; min-height: 297mm; height: 297mm; background: white; box-shadow: 0 30px 80px rgba(0,0,0,0.12); position: relative; overflow: hidden; margin-top: 20px; display: flex; flex-direction: column; }

        /* ===== WAVE HEADER - ADAPTIVE ===== */
        .wave-header { 
            position: relative; 
            height: <?php echo $headerHeight; ?>px; 
            background: linear-gradient(135deg, <?php echo $mainColor1; ?> 0%, <?php echo $mainColor2; ?> 50%, <?php echo $mainColor3; ?> 100%); 
            overflow: visible; 
            flex-shrink: 0; 
        }
        .gradient-overlay { position: absolute; top: 0; left: 0; width: 100%; height: 100%; background: radial-gradient(ellipse 80% 50% at 20% 40%, rgba(239, 68, 68, 0.6) 0%, transparent 50%), radial-gradient(ellipse 60% 40% at 80% 20%, rgba(185, 28, 28, 0.5) 0%, transparent 40%); }
        .organic-shapes { position: absolute; top: 0; left: 0; width: 100%; height: 100%; overflow: hidden; }
        .blob { position: absolute; border-radius: 50%; filter: blur(40px); }
        .blob-1 { width: 200px; height: 200px; background: radial-gradient(circle, rgba(255,255,255,0.2) 0%, transparent 70%); top: -80px; right: -30px; }
        .blob-2 { width: 140px; height: 140px; background: radial-gradient(circle, rgba(255,255,255,0.15) 0%, transparent 70%); top: 30px; left: -20px; }

        .header-content { position: relative; z-index: 10; padding: 20px 40px 15px; }
        .logo-area { display: flex; align-items: center; gap: 14px; color: white; }
        
        .logo-only { height: 60px; width: auto; max-width: 250px; object-fit: contain; }
        
        .logo-placeholder { width: 60px; height: 60px; background: rgba(255,255,255,0.15); backdrop-filter: blur(20px); border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 28px; border: 2px solid rgba(255,255,255,0.25); }

        .abstract-waves { position: absolute; bottom: -2px; left: 0; width: 100%; height: <?php echo $waveHeight; ?>px; overflow: visible; }
        .wave-svg { position: absolute; bottom: 0; left: 0; width: 100%; height: <?php echo $waveHeight; ?>px; }

        /* Glass Card - ADAPTIVE */
        .glass-card { 
            position: relative; 
            margin: <?php echo $glassMarginTop; ?>px 40px 15px;
            z-index: 20; 
            background: rgba(255, 255, 255, 0.5); 
            backdrop-filter: blur(30px); 
            border-radius: 12px; 
            padding: 18px 24px;
            box-shadow: 0 8px 32px rgba(220, 38, 38, 0.12), 0 0 0 1px rgba(255, 255, 255, 0.4) inset; 
            border: 1px solid rgba(255, 255, 255, 0.5); 
            display: grid; 
            grid-template-columns: 1fr 1.2fr; 
            gap: 20px; 
            flex-shrink: 0; 
        }
        
        .invoice-meta-section { padding-right: 20px; border-right: 2px solid #f3f4f6; }
        .invoice-title { font-size: 22px; font-weight: 800; color: #dc2626; letter-spacing: -1px; line-height: 1; margin-bottom: 6px; }
        .invoice-number { font-size: 11px; color: #6b7280; font-weight: 600; margin-bottom: 10px; }
        .meta-item { margin-bottom: 8px; }
        .meta-label { font-size: 8px; text-transform: uppercase; letter-spacing: 1.5px; color: #9ca3af; margin-bottom: 3px; font-weight: 700; }
        .meta-value { font-size: 11px; color: #374151; font-weight: 600; }
        .bill-to-section h3 { font-size: 8px; text-transform: uppercase; letter-spacing: 1.5px; color: #111827; margin-bottom: 8px; font-weight: 700; }
        .client-name { font-size: 14px; font-weight: 700; color: #111827; margin-bottom: 6px; }
        .client-details { font-size: 10px; color: #6b7280; line-height: 1.6; }

        /* Main Content - ADAPTIVE */
        .main-content { padding: 0 40px; flex: 1; display: flex; flex-direction: column; }
        .table-container { margin-bottom: 10px; }
        .table-wrapper { border-radius: 8px; overflow: hidden; box-shadow: 0 2px 10px rgba(0,0,0,0.04); }
        .invoice-table { width: 100%; border-collapse: collapse; }
        .invoice-table thead tr { background: linear-gradient(135deg, #111827 0%, #1f2937 100%); }
        .invoice-table th { padding: 10px 12px; text-align: left; font-size: 9px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px; color: white; }
        .invoice-table th:last-child { text-align: right; }
        .invoice-table tbody tr { border-bottom: 1px solid #f3f4f6; }
        .invoice-table td { padding: 10px 12px; font-size: 11px; color: #374151; vertical-align: top; }
        .invoice-table td:first-child { font-weight: 600; color: #9ca3af; width: 30px; }
        .invoice-table td:last-child { text-align: right; font-weight: 600; color: #111827; }
        .item-title { display: block; font-weight: 600; color: #111827; font-size: 11px; }
        .item-desc { display: block; font-size: 9px; color: #9ca3af; }
        .plate-badge { background: linear-gradient(135deg, #dc2626, #991b1b); color: white; padding: 2px 5px; border-radius: 3px; font-size: 8px; font-weight: 600; }

        /* Price cell styling */
        .invoice-table td.price-cell { text-align: right; font-weight: 600; color: #111827; white-space: nowrap; }
        .invoice-table td.price-cell .price-rp { float: left; font-weight: 500; color: #6b7280; margin-right: 5px; }
        .invoice-table td.price-cell .price-nominal { float: right; font-weight: 700; color: #111827; }

        .summary-section { display: flex; justify-content: flex-end; margin-bottom: 12px; }
        .totals-card { background: linear-gradient(135deg, #fef2f2 0%, #fff 100%); border-radius: 8px; padding: 14px 20px; border: 1px solid #fee2e2; min-width: 260px; }
        .terbilang-row { font-size: 9px; color: #6b7280; margin-bottom: 8px; padding-bottom: 8px; border-bottom: 1px dashed #e5e7eb; }
        .terbilang-row span { font-style: italic; color: #111827; font-weight: 500; }
        .total-row { display: flex; justify-content: space-between; padding: 8px 0; font-size: 14px; font-weight: 800; color: #dc2626; }

        .payment-section { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; padding-top: 12px; border-top: 2px solid #f3f4f6; margin-top: auto; }
        .payment-info h4 { font-size: 9px; text-transform: uppercase; letter-spacing: 1px; color: #6b7280; margin-bottom: 8px; font-weight: 700; }
        .bank-box { background: linear-gradient(135deg, #1f2937 0%, #111827 100%); color: white; border-radius: 6px; padding: 12px; font-size: 10px; line-height: 1.7; }
        .signature-area { text-align: right; }
        .signature-area p { font-size: 10px; color: #6b7280; margin-bottom: 4px; }
        .signature-area .hormat-kami { margin-bottom: <?php echo $signatureMarginTop; ?>px; }
        .signature-area .signature-name { font-weight: 700; font-size: 12px; color: #111827; border-top: 2px solid #111827; padding-top: 6px; display: inline-block; min-width: 160px; }
        .signature-area .signature-title { font-size: 10px; color: #6b7280; margin-top: 3px; }
        
        /* Signature area untuk surat penawaran/kesepakatan - ADAPTIVE */
        .signature-area-letter {
            text-align: right;
            margin-top: 10px;
            padding-top: 8px;
        }
        
        .signature-area-letter .place-date {
            font-size: 11px;
            color: #374151;
            margin-bottom: 4px;
        }
        
        .signature-area-letter .company-label {
            font-size: 11px;
            color: #6b7280;
            margin-bottom: <?php echo $signatureMarginTop; ?>px;
        }
        
        .signature-area-letter .signature-name {
            font-weight: 700;
            font-size: 12px;
            color: #111827;
            border-top: 2px solid #111827;
            padding-top: 6px;
            display: inline-block;
            min-width: 160px;
        }
        
        .signature-area-letter .signature-title {
            font-size: 10px;
            color: #6b7280;
            margin-top: 3px;
        }

        .wave-footer { position: relative; flex-shrink: 0; margin-top: auto; }
        .footer-abstract-waves { height: 50px; position: relative; overflow: hidden; }
        .footer-wave-svg { position: absolute; bottom: 0; left: 0; width: 100%; height: 50px; }
        .footer-bottom { background: #111827; padding: 10px 40px; text-align: center; }
        .footer-bottom p { font-size: 10px; font-weight: 600; color: white; margin-bottom: 3px; }
        .footer-contacts { font-size: 8px; color: #9ca3af; }

        /* Kwitansi */
        .kwitansi-content { padding-top: 8px; }
        .kwitansi-row { display: grid; grid-template-columns: 140px 15px 1fr; margin-bottom: 10px; font-size: 11px; }
        .kwitansi-row .label { color: #6b7280; }
        .kwitansi-row .val { color: #111827; }
        .kwitansi-row .val.highlight { color: #dc2626; font-weight: 700; font-style: italic; }
        .amount-display { background: linear-gradient(135deg, #1f2937, #111827); color: white; padding: 18px 28px; border-radius: 10px; display: flex; justify-content: space-between; align-items: center; margin: 16px 0; }
        .amount-display .label { font-size: 11px; opacity: 0.8; }
        .amount-display .value { font-size: 22px; font-weight: 800; }

        /* Surat content - ADAPTIVE */
        .surat-content { font-size: 11px; line-height: 1.7; color: #374151; }
        .surat-content p { margin-bottom: 10px; }
        .badge-agreed { background: linear-gradient(135deg, #fef2f2, #fee2e2); border: 2px solid #dc2626; border-radius: 8px; padding: 10px 14px; margin-bottom: 12px; display: flex; align-items: center; gap: 12px; }
        .badge-agreed .icon { width: 32px; height: 32px; background: #dc2626; border-radius: 50%; display: flex; align-items: center; justify-content: center; color: white; font-size: 16px; flex-shrink: 0; }
        .badge-agreed .text { font-size: 10px; color: #991b1b; }
        .badge-agreed .text strong { display: block; font-size: 11px; margin-bottom: 2px; }
        
        /* Includes box - ADAPTIVE */
        .includes-box { background: #f9fafb; border-radius: 8px; padding: 12px; margin: 12px 0; }
        .includes-box h4 { font-size: 9px; text-transform: uppercase; letter-spacing: 1px; color: #6b7280; margin-bottom: 8px; }
        .includes-box ul { display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 5px; list-style: none; }
        .includes-box li { font-size: 9px; color: #6b7280; padding-left: 14px; position: relative; }
        .includes-box li::before { content: '✓'; position: absolute; left: 0; color: #22c55e; font-weight: bold; font-size: 10px; }

        /* ============================================
           SIZE VARIANTS - Override styles based on item count
           ============================================ */
        
        /* Size Medium (4-5 items) */
        .size-medium .logo-only { height: 50px; }
        .size-medium .invoice-title { font-size: 20px; }
        .size-medium .client-name { font-size: 13px; }
        
        /* Size Compact (6-8 items) */
        .size-compact .logo-only { height: 45px; }
        .size-compact .invoice-title { font-size: 18px; }
        .size-compact .client-name { font-size: 12px; }
        .size-compact .invoice-table th { padding: 8px 10px; font-size: 8px; }
        .size-compact .invoice-table td { padding: 8px 10px; font-size: 10px; }
        .size-compact .surat-content { font-size: 10px; line-height: 1.6; }
        .size-compact .surat-content p { margin-bottom: 8px; }
        .size-compact .includes-box { padding: 10px; margin: 10px 0; }
        .size-compact .includes-box ul { gap: 4px; }
        .size-compact .includes-box li { font-size: 8px; }
        
        /* Size Mini (9+ items) */
        .size-mini .logo-only { height: 40px; }
        .size-mini .glass-card { padding: 14px 18px; gap: 15px; }
        .size-mini .invoice-title { font-size: 16px; margin-bottom: 4px; }
        .size-mini .invoice-number { font-size: 9px; margin-bottom: 6px; }
        .size-mini .meta-label { font-size: 7px; }
        .size-mini .meta-value { font-size: 10px; }
        .size-mini .client-name { font-size: 11px; margin-bottom: 4px; }
        .size-mini .client-details { font-size: 9px; }
        .size-mini .invoice-table th { padding: 6px 8px; font-size: 7px; }
        .size-mini .invoice-table td { padding: 6px 8px; font-size: 9px; }
        .size-mini .invoice-table td:first-child { width: 25px; }
        .size-mini .surat-content { font-size: 9px; line-height: 1.5; }
        .size-mini .surat-content p { margin-bottom: 6px; }
        .size-mini .badge-agreed { padding: 8px 10px; margin-bottom: 8px; gap: 8px; }
        .size-mini .badge-agreed .icon { width: 26px; height: 26px; font-size: 14px; }
        .size-mini .badge-agreed .text { font-size: 8px; }
        .size-mini .badge-agreed .text strong { font-size: 9px; }
        .size-mini .includes-box { padding: 8px; margin: 8px 0; }
        .size-mini .includes-box h4 { font-size: 8px; margin-bottom: 6px; }
        .size-mini .includes-box ul { gap: 3px; }
        .size-mini .includes-box li { font-size: 8px; padding-left: 12px; }
        .size-mini .signature-area-letter { margin-top: 6px; padding-top: 5px; }
        .size-mini .signature-area-letter .place-date { font-size: 9px; }
        .size-mini .signature-area-letter .company-label { font-size: 9px; }
        .size-mini .signature-area-letter .signature-name { font-size: 10px; min-width: 140px; }
        .size-mini .signature-area-letter .signature-title { font-size: 8px; }
        .size-mini .footer-bottom { padding: 8px 40px; }
        .size-mini .footer-bottom p { font-size: 9px; }
        .size-mini .footer-contacts { font-size: 7px; }

        @media print {
            @page { size: A4; margin: 0; }
            body { padding: 0; background: white; -webkit-print-color-adjust: exact !important; print-color-adjust: exact !important; }
            .print-btn, .back-btn { display: none !important; }
            .invoice-container { box-shadow: none; margin: 0; height: 297mm; min-height: 297mm; max-height: 297mm; overflow: hidden; }
            .wt-container { box-shadow: none !important; margin: 0 !important; }
        }

        /* ================================================================
           WONDERLAND TRAVEL — Invoice & Kwitansi (gold/black brand)
           ================================================================ */
        :root {
            --wt-gold: #c9a227;
            --wt-gold-dark: #a8841c;
            --wt-ink: #1a1a1a;
        }

        .wt-container {
            width: 210mm;
            min-height: 297mm;
            margin: 20px auto;
            background: #fff;
            box-shadow: 0 10px 40px rgba(0,0,0,0.15);
            position: relative;
            display: flex;
            flex-direction: column;
            font-family: 'Segoe UI', Arial, sans-serif;
            color: var(--wt-ink);
        }

        /* Diagonal gold/black brand band, used top and bottom */
        .wt-band { position: relative; height: 26px; background: var(--wt-gold); overflow: hidden; flex-shrink: 0; }
        .wt-band::before, .wt-band::after {
            content: ""; position: absolute; top: -40%; height: 180%; width: 26%; background: var(--wt-ink);
            transform: skewX(-28deg);
        }
        .wt-band::before { right: 22%; }
        .wt-band::after { right: 4%; width: 10%; }
        .wt-band.wt-band-bottom { margin-top: auto; }

        .wt-header {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            padding: 22px 34px 14px;
        }

        .wt-logo { display: flex; align-items: center; gap: 12px; }
        .wt-logo-mark {
            width: 46px; height: 46px; border-radius: 6px;
            background: linear-gradient(135deg, var(--wt-gold) 0%, var(--wt-gold-dark) 100%);
            display: flex; align-items: center; justify-content: center;
            color: #fff; font-weight: 800; font-size: 22px; font-style: italic; flex-shrink: 0;
        }
        .wt-logo img.wt-logo-img { width: 46px; height: 46px; object-fit: contain; border-radius: 6px; flex-shrink: 0; }
        .wt-logo-text { line-height: 1.35; }
        .wt-logo-text .wt-brand { font-size: 15px; font-weight: 800; letter-spacing: 0.5px; }
        .wt-logo-text .wt-brand-sub { font-size: 8px; letter-spacing: 3px; color: #6b6b6b; text-transform: uppercase; }
        .wt-company-block { font-size: 10px; line-height: 1.5; color: #333; }
        .wt-company-block strong { display: block; font-size: 11px; color: var(--wt-ink); margin-bottom: 2px; }

        .wt-doc-title { text-align: right; }
        .wt-doc-title h1 { font-size: 26px; font-weight: 800; letter-spacing: 1px; margin: 0; color: var(--wt-ink); }
        .wt-doc-title .wt-doc-no { font-size: 10px; color: #666; margin-top: 4px; }

        .wt-meta-rule { border: none; border-top: 3px double var(--wt-ink); margin: 0 34px; }

        .wt-meta {
            padding: 14px 34px 4px;
            font-size: 11px;
            line-height: 1.9;
        }
        .wt-meta-row { display: flex; gap: 10px; }
        .wt-meta-row .wt-label { width: 110px; color: #444; flex-shrink: 0; }
        .wt-meta-row .wt-colon { width: 10px; flex-shrink: 0; }
        .wt-meta-row .wt-value { font-weight: 600; }

        .wt-body { padding: 16px 34px; flex: 1; }

        .wt-table { width: 100%; border-collapse: collapse; font-size: 10.5px; }
        .wt-table th {
            background: var(--wt-ink); color: #fff; text-align: left;
            padding: 9px 10px; font-size: 10px; text-transform: uppercase; letter-spacing: 0.5px;
            border: 1px solid var(--wt-ink);
        }
        .wt-table th.wt-num, .wt-table td.wt-num { text-align: center; width: 60px; }
        .wt-table th.wt-price, .wt-table td.wt-price { text-align: right; width: 110px; }
        .wt-table td { padding: 9px 10px; border: 1px solid #ddd; vertical-align: top; }
        .wt-table .wt-item-sub { display: block; font-size: 9px; color: #777; margin-top: 2px; }
        .wt-table tbody tr:nth-child(even) { background: #fafafa; }
        .wt-participant-list { list-style: disc; margin: 4px 0 0 16px; padding: 0; font-size: 9.5px; color: #333; }
        .wt-participant-list li { margin-bottom: 1px; }

        .wt-subtotal-row {
            display: flex; justify-content: flex-end; gap: 20px;
            padding: 12px 4px 0; font-size: 13px; font-weight: 700;
        }
        .wt-subtotal-row .wt-subtotal-value { min-width: 130px; text-align: right; color: var(--wt-gold-dark); }

        .wt-terbilang { font-size: 10px; font-style: italic; color: #555; margin-top: 4px; text-align: right; }

        .wt-footer-block {
            display: flex; justify-content: space-between; align-items: flex-end;
            padding: 10px 34px 20px;
            gap: 20px;
        }

        .wt-payment-box {
            border: 2px solid var(--wt-gold); border-radius: 10px;
            padding: 10px 16px; font-size: 10px; line-height: 1.7; max-width: 260px;
        }
        .wt-payment-box strong { display: block; font-size: 10.5px; margin-bottom: 3px; }

        .wt-signature { text-align: center; font-size: 11px; min-width: 200px; }
        .wt-signature .wt-hormat { margin-bottom: 45px; }
        .wt-signature .wt-sign-name { font-weight: 700; text-decoration: underline; }

        .wt-bottom-contacts {
            display: flex; justify-content: center; gap: 26px;
            padding: 10px 20px; font-size: 9.5px; color: #333;
        }
        .wt-bottom-contacts span { display: inline-flex; align-items: center; gap: 6px; }

        /* Kwitansi-specific — flex:1 sama seperti .wt-body di invoice, supaya
           amount box + tanda tangan ikut terdorong ke bawah halaman (bukan
           menumpuk di atas dengan sisa halaman kosong total di bawahnya). */
        .wt-kwitansi-fields { padding: 20px 34px 10px; font-size: 12px; flex: 1; }
        .wt-kwitansi-row { display: flex; gap: 14px; padding: 7px 0; border-bottom: 1px solid #e5e5e5; }
        .wt-kwitansi-row .wt-label { width: 150px; color: #444; }
        .wt-kwitansi-row .wt-colon { width: 10px; }
        .wt-kwitansi-row .wt-value { font-weight: 700; flex: 1; }
        .wt-kwitansi-row.wt-highlight .wt-value { color: var(--wt-gold-dark); font-style: italic; }

        .wt-amount-row { display: flex; align-items: center; gap: 14px; padding: 30px 34px 10px; }
        .wt-amount-box {
            background: #4a4a4a; color: #fff; font-weight: 800; font-size: 20px;
            padding: 14px 30px 14px 22px; border-radius: 0 6px 6px 0;
            clip-path: polygon(0 0, 100% 0, 92% 100%, 0% 100%);
        }
        .wt-amount-value { font-size: 22px; font-weight: 800; color: var(--wt-ink); }

        .wt-kwitansi-signoff {
            display: flex; justify-content: flex-end; padding: 20px 34px 30px;
        }
    </style>
</head>
<body class="<?php echo $sizeClass; ?>">
    <a href="javascript:history.back()" class="back-btn">← Kembali</a>
    <button onclick="window.print()" class="print-btn">🖨️ Cetak</button>

    <?php if ($docType === 'invoice' || $docType === 'kwitansi'): ?>
    <!-- ================================================================
         WONDERLAND TRAVEL TEMPLATE (Invoice & Kwitansi)
         ================================================================ -->
    <?php
        $bankAccounts = [];
        if (!empty($company['bank_accounts'])) {
            $decoded = json_decode($company['bank_accounts'], true);
            if (is_array($decoded)) $bankAccounts = $decoded;
        }
        $primaryBank = $bankAccounts[0] ?? ['bank_name' => 'BNI', 'account_no' => '2019709091', 'account_name' => 'PT. NUSA ERA ARTHA'];
        $companyName = $cn ?: 'PT. Nusa Era Artha';
        $signatoryName = 'Dian Novianti';
        $signatoryTitle = 'Direktur';
    ?>
    <div class="wt-container">
        <div class="wt-band"></div>

        <div class="wt-header">
            <div class="wt-logo">
                <?php if ($logoUrl): ?>
                <img src="<?php echo e($logoUrl); ?>" alt="Logo" class="wt-logo-img">
                <?php else: ?>
                <div class="wt-logo-mark">W</div>
                <?php endif; ?>
                <div class="wt-logo-text">
                    <div class="wt-brand">WONDERLAND<br>TRAVEL</div>
                </div>
            </div>
            <div class="wt-doc-title">
                <h1><?php echo $docType === 'invoice' ? 'INVOICE' : 'KWITANSI'; ?></h1>
                <div class="wt-doc-no">No: <?php echo e($docNum); ?></div>
            </div>
        </div>

        <?php if ($docType === 'invoice'): ?>
        <div class="wt-meta">
            <div class="wt-meta-row"><div class="wt-label">Invoice No</div><div class="wt-colon">:</div><div class="wt-value"><?php echo e($docNum); ?></div></div>
            <div class="wt-meta-row"><div class="wt-label">Date</div><div class="wt-colon">:</div><div class="wt-value"><?php echo $docDate; ?></div></div>
            <div class="wt-meta-row"><div class="wt-label">Corporate</div><div class="wt-colon">:</div><div class="wt-value"><?php echo e($cl); ?></div></div>
            <?php if ($orderPicName): ?>
            <div class="wt-meta-row"><div class="wt-label">PIC Name</div><div class="wt-colon">:</div><div class="wt-value"><?php echo e($orderPicName); ?></div></div>
            <?php endif; ?>
            <?php if ($clAddr): ?>
            <div class="wt-meta-row"><div class="wt-label">Address</div><div class="wt-colon">:</div><div class="wt-value"><?php echo e($clAddr); ?></div></div>
            <?php endif; ?>
            <?php if ($orderPicPhone || $clPhone): ?>
            <div class="wt-meta-row"><div class="wt-label">Phone Number</div><div class="wt-colon">:</div><div class="wt-value"><?php echo e($orderPicPhone ?: $clPhone); ?></div></div>
            <?php endif; ?>
        </div>

        <div class="wt-body">
            <table class="wt-table">
                <thead>
                    <tr>
                        <th class="wt-num">QTY</th>
                        <th>Description</th>
                        <th class="wt-price">Unit Price</th>
                        <th class="wt-price">Total</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($items as $item): ?>
                    <tr>
                        <td class="wt-num"><?php echo (int)$item['quantity']; ?><?php if ((int)$item['num_days'] > 1): ?> × <?php echo (int)$item['num_days']; ?>h<?php endif; ?></td>
                        <td>
                            <?php echo e($item['description']); ?>
                            <span class="wt-item-sub"><?php echo isset($types[$item['item_type']]) ? $types[$item['item_type']] : $item['item_type']; ?></span>
                            <?php if ($orderDivisiLabel): ?>
                            <span class="wt-item-sub">Team <?php echo e($orderDivisiLabel); ?></span>
                            <?php endif; ?>
                            <?php if (!empty($item['participant_names'])): ?>
                            <ul class="wt-participant-list">
                                <?php foreach (preg_split('/\r\n|\r|\n/', trim($item['participant_names'])) as $pName): ?>
                                <?php if (trim($pName) !== ''): ?>
                                <li><?php echo e(trim($pName)); ?></li>
                                <?php endif; ?>
                                <?php endforeach; ?>
                            </ul>
                            <?php endif; ?>
                        </td>
                        <td class="wt-price"><?php echo rp($item['calc_unit_price_with_markup']); ?></td>
                        <td class="wt-price"><?php echo rp($item['calc_final_price']); ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>

            <div class="wt-subtotal-row">
                <span>Sub TOTAL</span>
                <span class="wt-subtotal-value"><?php echo rp($total); ?></span>
            </div>
            <div class="wt-terbilang"><?php echo trim(bilang($total)); ?> Rupiah</div>
        </div>

        <div class="wt-footer-block">
            <div class="wt-payment-box">
                <strong>Payment Detail</strong>
                ○ Cash&nbsp;&nbsp;&nbsp;○ Transfer<br>
                <?php echo e($primaryBank['bank_name'] ?? 'BNI'); ?> <?php echo e($primaryBank['account_no'] ?? ''); ?><br>
                A/N: <?php echo e($primaryBank['account_name'] ?? ''); ?>
            </div>
            <div class="wt-signature">
                <p class="wt-hormat">Hormat Kami</p>
                <div class="wt-sign-name"><?php echo e($companyName); ?></div>
                <div><?php echo e($signatoryName); ?></div>
            </div>
        </div>
        <?php else: /* kwitansi */ ?>
        <div class="wt-kwitansi-fields">
            <div class="wt-kwitansi-row">
                <div class="wt-label">Sudah diterima dari</div><div class="wt-colon">:</div>
                <div class="wt-value"><?php echo e($cl); ?><?php if ($orderPicName): ?> (U.p. <?php echo e($orderPicName); ?>)<?php endif; ?></div>
            </div>
            <div class="wt-kwitansi-row wt-highlight">
                <div class="wt-label">Banyaknya Uang</div><div class="wt-colon">:</div>
                <div class="wt-value"><?php echo trim(bilang($total)); ?> Rupiah</div>
            </div>
            <div class="wt-kwitansi-row">
                <div class="wt-label">Untuk Pembayaran</div><div class="wt-colon">:</div>
                <div class="wt-value">
                    <?php echo e(isset($order['description']) && $order['description'] ? $order['description'] : 'Pembayaran layanan'); ?><?php if ($totalParticipantQty > 0): ?> ( <?php echo $totalParticipantQty; ?> Orang )<?php endif; ?>
                    <?php if ($orderDivisiLabel): ?><br><?php echo e($orderDivisiLabel); ?> PELNI<?php endif; ?>
                </div>
            </div>
        </div>

        <div class="wt-amount-row">
            <div class="wt-amount-box">Rp.</div>
            <div class="wt-amount-value"><?php echo number_format($total, 0, ',', '.'); ?></div>
        </div>

        <div class="wt-kwitansi-signoff">
            <div class="wt-signature">
                <p>Jakarta, <?php echo $docDate; ?></p>
                <p class="wt-hormat">Yang menerima</p>
                <div class="wt-sign-name"><?php echo e($signatoryName); ?></div>
                <div><?php echo e($signatoryTitle); ?></div>
            </div>
        </div>
        <?php endif; ?>

        <div class="wt-bottom-contacts">
            <span>@wonderland__traveling</span>
            <span>&bull;</span>
            <span><?php echo e($ce ?: 'travelingwithwonderland@gmail.com'); ?></span>
            <span>&bull;</span>
            <span>www.wonderlandtrips.com</span>
            <span>&bull;</span>
            <span><?php echo e($cp ?: '0878-0486-1367'); ?></span>
        </div>

        <div class="wt-band wt-band-bottom"></div>
    </div>
    <?php else: ?>
    <div class="invoice-container">
        <!-- ===== WAVE HEADER (Penawaran/Kesepakatan — tidak lagi dipakai, tombolnya sudah dihapus dari order detail) ===== -->
        <div class="wave-header">
            <div class="gradient-overlay"></div>
            <div class="organic-shapes">
                <div class="blob blob-1"></div>
                <div class="blob blob-2"></div>
            </div>
            <div class="header-content">
                <div class="logo-area">
                    <?php if ($logoUrl): ?>
                    <img src="<?php echo e($logoUrl); ?>" alt="Logo" class="logo-only">
                    <?php else: ?>
                    <div class="logo-placeholder">✈️</div>
                    <?php endif; ?>
                </div>
            </div>
            <div class="abstract-waves">
                <svg class="wave-svg" viewBox="0 0 1200 100" preserveAspectRatio="none">
                    <path fill="rgba(255,255,255,0.3)" d="M0,50 Q300,80 600,40 T1200,60 L1200,100 L0,100 Z"/>
                    <path fill="rgba(255,255,255,0.6)" d="M0,70 Q300,40 600,75 T1200,55 L1200,100 L0,100 Z"/>
                    <path fill="#ffffff" d="M0,85 Q300,75 600,90 T1200,80 L1200,100 L0,100 Z"/>
                </svg>
            </div>
        </div>

        <!-- Glass Card -->
        <div class="glass-card">
            <div class="invoice-meta-section">
                <div class="invoice-title"><?php echo $docTitle; ?></div>
                <div class="invoice-number"><?php echo e($docNum); ?></div>
                <div class="meta-item">
                    <div class="meta-label">Tanggal</div>
                    <div class="meta-value"><?php echo $docDate; ?></div>
                </div>
            </div>
            <div class="bill-to-section">
                <h3>Kepada Yth</h3>
                <div class="client-name"><?php echo e($cl); ?></div>
                <div class="client-details">
                    <?php if ($clAddr): ?><?php echo e($clAddr); ?><br><?php endif; ?>
                    <?php if ($orderPicName): ?>U.p. <?php echo e($orderPicName); ?><br><?php endif; ?>
                    <?php if ($orderPicPhone): ?>📞 <?php echo e($orderPicPhone); ?><?php elseif ($clPhone): ?>📞 <?php echo e($clPhone); ?><?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Main Content -->
        <div class="main-content">

<?php if ($docType == 'invoice'): ?>
            <div class="table-container">
                <div class="table-wrapper">
                    <table class="invoice-table">
                        <thead>
                            <tr><th>No</th><th>Deskripsi Layanan</th><th>Qty</th><th>Total</th></tr>
                        </thead>
                        <tbody>
                        <?php $no = 1; foreach ($items as $item): ?>
                            <tr>
                                <td><?php echo str_pad($no++, 2, '0', STR_PAD_LEFT); ?></td>
                                <td>
                                    <span class="item-title"><?php echo e($item['description']); ?></span>
                                    <span class="item-desc"><?php echo isset($types[$item['item_type']]) ? $types[$item['item_type']] : $item['item_type']; ?><?php if (isset($item['vehicle_plate']) && $item['vehicle_plate']): ?> • <span class="plate-badge"><?php echo e($item['vehicle_plate']); ?></span><?php endif; ?></span>
                                </td>
                                <td><?php echo $item['quantity']; ?></td>
                                <td><?php echo rp($item['calc_final_price']); ?></td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="summary-section">
                <div class="totals-card">
                    <div class="terbilang-row">Terbilang: <span><?php echo trim(bilang($total)); ?> Rupiah</span></div>
                    <div class="total-row"><span>TOTAL</span><span><?php echo rp($total); ?></span></div>
                </div>
            </div>

            <div class="payment-section">
                <div class="payment-info">
                    <h4>Informasi Pembayaran</h4>
                    <div class="bank-box">
                        <strong>Bank BNI</strong><br>
                        No. Rekening: <strong>8828878862</strong><br>
                        A/N: <strong>PT PELITA INTI MULIA</strong>
                    </div>
                </div>
                <div class="signature-area">
                    <p>Jakarta, <?php echo $docDate; ?></p>
                    <p class="hormat-kami">Hormat Kami,</p>
                    <div class="signature-name">Arif Sukmana</div>
                    <div class="signature-title">Direktur</div>
                </div>
            </div>

<?php elseif ($docType == 'kwitansi'): ?>
            <div class="kwitansi-content">
                <div class="kwitansi-row"><div class="label">Telah Terima Dari</div><div>:</div><div class="val"><strong><?php echo e($cl); ?></strong><?php if ($orderPicName): ?> (U.p. <?php echo e($orderPicName); ?>)<?php endif; ?></div></div>
                <div class="kwitansi-row"><div class="label">Uang Sejumlah</div><div>:</div><div class="val highlight"><?php echo trim(bilang($total)); ?> Rupiah</div></div>
                <div class="kwitansi-row"><div class="label">Untuk Pembayaran</div><div>:</div><div class="val"><?php echo e(isset($order['description']) ? $order['description'] : 'Pembayaran layanan'); ?></div></div>
                
                <div class="amount-display">
                    <div class="label">Total Pembayaran</div>
                    <div class="value"><?php echo rp($total); ?></div>
                </div>

                <div class="signature-area" style="margin-top: 25px;">
                    <p>Jakarta, <?php echo $docDate; ?></p>
                    <p class="hormat-kami">Hormat Kami,</p>
                    <div class="signature-name">Arif Sukmana</div>
                    <div class="signature-title">Direktur</div>
                </div>
            </div>

<?php elseif ($docType == 'penawaran' || $docType == 'kesepakatan'): ?>
            <div class="surat-content">
                <?php if ($docType == 'kesepakatan'): ?>
                <div class="badge-agreed">
                    <div class="icon">✓</div>
                    <div class="text"><strong>Harga Telah Disepakati</strong>Dokumen konfirmasi harga yang telah disetujui kedua belah pihak.</div>
                </div>
                <?php endif; ?>

                <p>Dengan Hormat,</p>
                <p>
                    <?php if ($docType == 'penawaran'): ?>
                    Kami bermaksud menawarkan harga <?php echo $serviceNarration['penawaran']; ?> untuk kebutuhan <?php echo e($cl); ?>. Adapun harga yang kami tawarkan sebagai berikut:
                    <?php else: ?>
                    Dari penawaran yang telah disepakati untuk <?php echo $serviceNarration['kesepakatan']; ?> <?php echo e($cl); ?>, berikut kesepakatan harga:
                    <?php endif; ?>
                </p>
            </div>

            <div class="table-container">
                <div class="table-wrapper">
                    <table class="invoice-table">
                        <thead><tr><th>No</th><th><?php echo $tableHeaders['col1']; ?></th><th><?php echo $tableHeaders['col2']; ?></th><th><?php echo $tableHeaders['col3']; ?></th></tr></thead>
                        <tbody>
                        <?php 
                        $no = 1; 
                        foreach ($groupedItems as $item): 
                            if ($docType == 'penawaran') {
                                $price = $item['calc_unit_price_with_markup'] + getRandomMarkup();
                            } else {
                                $price = $item['calc_unit_price_with_markup'];
                            }
                        ?>
                        <tr>
                            <td><?php echo $no++; ?>.</td>
                            <td><?php echo isset($types[$item['item_type']]) ? $types[$item['item_type']] : $item['item_type']; ?></td>
                            <td><?php echo e($item['description']); ?></td>
                            <td class="price-cell"><?php echo rpTable($price); ?></td>
                        </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="includes-box">
                <h4>Harga sudah termasuk:</h4>
                <ul>
                    <?php foreach ($serviceIncludes as $include): ?>
                    <li><?php echo e($include); ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>

            <div class="surat-content">
                <p>Demikian surat ini kami sampaikan. Atas perhatian dan kerjasamanya kami ucapkan terima kasih.</p>
            </div>

            <!-- Signature Area untuk Surat Penawaran/Kesepakatan -->
            <div class="signature-area-letter">
                <div class="place-date">Jakarta, <?php echo $docDate; ?></div>
                <div class="company-label">Hormat Kami,</div>
                <div class="signature-name">Arif Sukmana</div>
                <div class="signature-title">Direktur</div>
            </div>
<?php endif; ?>

        </div>

        <!-- Wave Footer -->
        <div class="wave-footer">
            <div class="footer-abstract-waves">
                <svg class="footer-wave-svg" viewBox="0 0 1200 60" preserveAspectRatio="none">
                    <path fill="#374151" opacity="0.5" d="M0,20 Q300,50 600,15 T1200,35 L1200,60 L0,60 Z"/>
                    <path fill="#1f2937" opacity="0.7" d="M0,35 Q300,20 600,40 T1200,25 L1200,60 L0,60 Z"/>
                    <path fill="#111827" d="M0,45 Q300,35 600,50 T1200,40 L1200,60 L0,60 Z"/>
                </svg>
            </div>
            <div class="footer-bottom">
                <p>PIM TRAVEL - Your Trusted Journey Partner</p>
                <div class="footer-contacts">📧 <?php echo e($ce); ?> | 📞 <?php echo e($cp); ?></div>
            </div>
        </div>
    </div>
    <?php endif; ?>
</body>
</html>