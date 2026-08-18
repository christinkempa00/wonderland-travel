<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lampiran Rental - <?= e($order->order_number) ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Sora:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        @page {
            size: A4 landscape;
            margin: 8mm;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Sora', sans-serif;
            background: #f3f4f6;
            -webkit-print-color-adjust: exact !important;
            print-color-adjust: exact !important;
            color-adjust: exact !important;
        }

        /* Buttons */
        .print-btn {
            position: fixed;
            top: 20px;
            right: 20px;
            background: linear-gradient(135deg, #dc2626 0%, #b91c1c 100%);
            color: white;
            border: none;
            padding: 14px 28px;
            font-family: 'Sora', sans-serif;
            font-size: 14px;
            font-weight: 600;
            border-radius: 12px;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 10px;
            box-shadow: 0 4px 20px rgba(220, 38, 38, 0.4);
            z-index: 1000;
        }

        .back-btn {
            position: fixed;
            top: 20px;
            left: 20px;
            background: linear-gradient(135deg, #6b7280 0%, #4b5563 100%);
            color: white;
            border: none;
            padding: 14px 28px;
            font-family: 'Sora', sans-serif;
            font-size: 14px;
            font-weight: 600;
            border-radius: 12px;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 10px;
            box-shadow: 0 4px 20px rgba(107, 114, 128, 0.4);
            z-index: 1000;
            text-decoration: none;
        }

        /* Page Container */
        .page {
            width: 281mm;
            height: 194mm;
            background: white;
            margin: 20px auto;
            box-shadow: 0 20px 60px rgba(0,0,0,0.1);
            position: relative;
            page-break-after: always;
            padding: 12px 15px;
            display: flex;
            flex-direction: column;
        }

        .page:last-child {
            page-break-after: auto;
        }

        .page-content {
            flex: 1;
            display: flex;
            flex-direction: column;
        }

        /* Compact Header */
        .page-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 8px 15px;
            background: linear-gradient(135deg, #dc2626 0%, #991b1b 100%);
            border-radius: 8px;
            margin-bottom: 10px;
            -webkit-print-color-adjust: exact !important;
            print-color-adjust: exact !important;
        }

        .header-left {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .logo-box {
            height: 30px;
        }

        .logo-box img {
            height: 100%;
            width: auto;
        }

        .logo-placeholder {
            font-size: 24px;
        }

        .header-title {
            color: white;
        }

        .header-title h1 {
            font-size: 11px;
            font-weight: 700;
            letter-spacing: 0.5px;
        }

        .header-title p {
            font-size: 9px;
            opacity: 0.9;
        }

        .header-right {
            text-align: right;
            color: white;
            font-size: 8px;
        }

        .header-right .label {
            opacity: 0.8;
        }

        .header-right .value {
            font-weight: 600;
        }

        /* Info Row */
        .info-row {
            display: flex;
            gap: 20px;
            margin-bottom: 8px;
            padding: 6px 10px;
            background: #f9fafb;
            border-radius: 6px;
            font-size: 8px;
        }

        .info-item {
            display: flex;
            gap: 5px;
        }

        .info-item .label {
            color: #6b7280;
        }

        .info-item .value {
            font-weight: 600;
            color: #1f2937;
        }

        .info-item .value.highlight {
            color: #dc2626;
        }

        /* Table */
        .rental-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 8px;
        }

        .rental-table thead th {
            background: linear-gradient(135deg, #1f2937 0%, #374151 100%);
            color: white;
            padding: 5px 3px;
            text-align: center;
            font-weight: 600;
            font-size: 8px;
            -webkit-print-color-adjust: exact !important;
            print-color-adjust: exact !important;
        }

        .rental-table thead th:first-child {
            border-radius: 4px 0 0 0;
        }

        .rental-table thead th:last-child {
            border-radius: 0 4px 0 0;
        }

        .rental-table tbody td {
            padding: 4px 3px;
            border-bottom: 1px solid #e5e7eb;
            text-align: center;
            vertical-align: middle;
        }

        .rental-table tbody tr:nth-child(even) {
            background: #fafafa;
        }

        .rental-table .no-col {
            width: 25px;
            font-weight: 600;
            color: #6b7280;
        }

        .rental-table .vehicle-col {
            text-align: left;
            font-weight: 500;
            padding-left: 8px !important;
        }

        .rental-table .date-col {
            min-width: 65px;
        }

        .rental-table .date-day {
            font-size: 10px;
            font-weight: 700;
        }

        .rental-table .date-month {
            font-size: 7px;
            opacity: 0.8;
        }

        .rental-table .price-col {
            min-width: 65px;
            font-size: 8px;
            color: #6b7280;
        }

        .rental-table .price-col.has-price {
            color: #059669;
            font-weight: 500;
        }

        .price-cell {
            display: flex;
            justify-content: space-between;
            padding: 0 2px;
        }

        .price-cell .currency {
            text-align: left;
        }

        .price-cell .amount {
            text-align: right;
        }

        .rental-table .total-col {
            min-width: 85px;
            font-weight: 700;
            color: #dc2626;
            background: #fef2f2;
            -webkit-print-color-adjust: exact !important;
            print-color-adjust: exact !important;
        }

        .rental-table tfoot td {
            background: linear-gradient(135deg, #fef2f2 0%, #fee2e2 100%);
            padding: 6px 3px;
            font-weight: 700;
            font-size: 9px;
            border-top: 2px solid #dc2626;
            -webkit-print-color-adjust: exact !important;
            print-color-adjust: exact !important;
        }

        .rental-table tfoot .grand-total {
            color: #dc2626;
            font-size: 10px;
        }

        /* Footer - selalu di bawah */
        .page-footer {
            flex-shrink: 0;
            margin-top: auto;
        }

        .footer-abstract-waves {
            height: 18px;
            position: relative;
        }

        .footer-wave-svg {
            width: 100%;
            height: 100%;
        }

        .footer-bottom {
            background: #111827;
            padding: 6px 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            color: rgba(255,255,255,0.7);
            font-size: 7px;
            -webkit-print-color-adjust: exact !important;
            print-color-adjust: exact !important;
        }

        .footer-contacts {
            display: flex;
            gap: 15px;
            align-items: center;
        }

        /* Signature Section - inline di halaman terakhir */
        .signature-section {
            display: flex;
            justify-content: flex-end;
            padding: 15px 0;
            margin-top: 10px;
        }

        .signature-box {
            text-align: center;
            min-width: 180px;
        }

        .signature-title {
            font-size: 9px;
            color: #6b7280;
            margin-bottom: 60px;
        }

        .signature-line {
            border-bottom: 1px solid #1f2937;
            margin-bottom: 5px;
        }

        .signature-name {
            font-size: 10px;
            font-weight: 600;
            color: #1f2937;
        }

        /* Print Styles */
        @media print {
            body {
                background: white;
            }

            .print-btn, .back-btn {
                display: none !important;
            }

            .page {
                box-shadow: none;
                margin: 0;
                page-break-after: always;
            }

            .page:last-child {
                page-break-after: auto;
            }
        }
    </style>
</head>
<body>
    <a href="<?= url('/attachment-order/' . $order->id) ?>" class="back-btn">
        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" width="18" height="18">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
        </svg>
        Kembali
    </a>

    <button onclick="window.print()" class="print-btn">
        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" width="18" height="18">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                d="M6 9V2h12v7M6 18H4a2 2 0 01-2-2v-5a2 2 0 012-2h16a2 2 0 012 2v5a2 2 0 01-2 2h-2M6 14h12v8H6z"/>
        </svg>
        Cetak
    </button>

    <?php 
    // ============================================
    // GABUNGKAN SEMUA KENDARAAN DARI SEMUA RENTAL
    // ============================================
    
    $allVehicles = [];
    $allDates = [];
    $firstLogo = '';
    $serviceNames = [];
    
    foreach ($rentalItems as $rental) {
        $rentalDetails = $detailsByRental[$rental['id']] ?? [];
        
        if (empty($firstLogo) && !empty($rental['attachment_logo'])) {
            $firstLogo = $rental['attachment_logo'];
        }
        
        if (!empty($rental['description'])) {
            $serviceNames[] = $rental['description'];
        }
        
        foreach ($rentalDetails as $v) {
            $allVehicles[] = $v;
            
            if ($v['start_date'] && $v['end_date']) {
                $current = new DateTime($v['start_date']);
                $end = new DateTime($v['end_date']);
                while ($current <= $end) {
                    $dateStr = $current->format('Y-m-d');
                    $allDates[$dateStr] = [
                        'day' => $current->format('d'),
                        'month' => $current->format('M'),
                        'year' => $current->format('y'),
                        'full' => $current->format('d/m/Y')
                    ];
                    $current->modify('+1 day');
                }
            }
        }
    }
    
    ksort($allDates);
    
    $combinedService = !empty($serviceNames) ? implode(', ', array_unique($serviceNames)) : 'Rental Kendaraan';
    
    // Calculate grand total
    $grandTotal = 0;
    foreach ($allVehicles as $v) {
        $dailyPrices = json_decode($v['daily_prices'] ?? '{}', true) ?: [];
        if ($v['start_date'] && $v['end_date']) {
            $current = new DateTime($v['start_date']);
            $end = new DateTime($v['end_date']);
            while ($current <= $end) {
                $dateStr = $current->format('Y-m-d');
                $price = $v['same_price'] ? floatval($v['price_per_day']) : floatval($dailyPrices[$dateStr] ?? 0);
                $grandTotal += $price;
                $current->modify('+1 day');
            }
        }
    }
    
    $totalVehicles = count($allVehicles);
    $totalDays = count($allDates);
    
    // ============================================
    // PAGINATION - Hitung dinamis berdasarkan jumlah kolom tanggal
    // ============================================
    // Dengan lebih banyak kolom tanggal, row height bisa lebih kecil
    // Estimasi: header ~50px, info ~25px, footer ~20px = ~95px overhead
    // Available height ~194mm - 95px ≈ 640px untuk tabel
    // Setiap row ~18px, jadi sekitar 35 rows max
    $rowsPerPage = 30;
    
    // Split vehicles into pages
    $vehiclePages = array_chunk($allVehicles, $rowsPerPage);
    if (empty($vehiclePages)) {
        $vehiclePages = [[]];
    }
    
    $totalPages = count($vehiclePages);
    $runningNo = 1;
    
    foreach ($vehiclePages as $pageIndex => $pageVehicles):
        $isLastDataPage = ($pageIndex === $totalPages - 1);
    ?>
    <div class="page">
        <div class="page-content">
            <!-- Compact Header -->
            <div class="page-header">
                <div class="header-left">
                    <div class="logo-box">
                        <?php if ($firstLogo && file_exists(BASE_PATH . '/uploads/logos/' . $firstLogo)): ?>
                        <img src="/uploads/logos/<?= e($firstLogo) ?>" alt="Logo">
                        <?php else: ?>
                        <span class="logo-placeholder">🚗</span>
                        <?php endif; ?>
                    </div>
                    <div class="header-title">
                        <h1>LAMPIRAN RENTAL KENDARAAN</h1>
                        <p>#<?= e($order->order_number) ?></p>
                    </div>
                </div>
                <div class="header-right">
                    <div><span class="label">Klien:</span> <span class="value"><?= e($client['name'] ?? '-') ?></span></div>
                    <div><span class="label">Periode:</span> <span class="value"><?= $totalDays ?> Hari (<?= !empty($allDates) ? reset($allDates)['full'] . ' - ' . end($allDates)['full'] : '-' ?>)</span></div>
            </div>
        </div>

        <!-- Info Row -->
        <div class="info-row">
            <div class="info-item">
                <span class="label">Layanan:</span>
                <span class="value highlight"><?= e($combinedService) ?></span>
            </div>
        </div>

        <!-- Table -->
        <table class="rental-table">
            <thead>
                <tr>
                    <th class="no-col">No</th>
                    <th class="vehicle-col">Jenis Kendaraan</th>
                    <?php foreach ($allDates as $date => $info): ?>
                    <th class="date-col">
                        <span class="date-day"><?= $info['day'] ?></span>
                        <span class="date-month"><?= $info['month'] ?> '<?= $info['year'] ?></span>
                    </th>
                    <?php endforeach; ?>
                    <th class="total-col">Jumlah</th>
                </tr>
            </thead>
            <tbody>
                <?php 
                foreach ($pageVehicles as $v): 
                    $dailyPrices = json_decode($v['daily_prices'] ?? '{}', true) ?: [];
                    $vehicleTotal = 0;
                ?>
                <tr>
                    <td class="no-col"><?= str_pad($runningNo++, 2, '0', STR_PAD_LEFT) ?></td>
                    <td class="vehicle-col"><?= e($v['vehicle_type']) ?></td>
                    <?php foreach ($allDates as $date => $info): 
                        $price = 0;
                        $hasPrice = false;
                        
                        if ($v['start_date'] <= $date && $date <= $v['end_date']) {
                            $price = $v['same_price'] ? floatval($v['price_per_day']) : floatval($dailyPrices[$date] ?? 0);
                            $hasPrice = $price > 0;
                            $vehicleTotal += $price;
                        }
                    ?>
                    <td class="price-col <?= $hasPrice ? 'has-price' : '' ?>">
                        <div class="price-cell">
                            <?php if ($hasPrice): ?>
                            <span class="currency">Rp</span>
                            <span class="amount"><?= number_format($price, 0, ',', '.') ?></span>
                            <?php else: ?>
                            <span class="currency"></span>
                            <span class="amount">-</span>
                            <?php endif; ?>
                        </div>
                    </td>
                    <?php endforeach; ?>
                    <td class="total-col">
                        <div class="price-cell">
                            <span class="currency">Rp</span>
                            <span class="amount"><?= number_format($vehicleTotal, 0, ',', '.') ?></span>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
            <?php if ($isLastDataPage): ?>
            <tfoot>
                <tr>
                    <td colspan="2" style="text-align:right; font-weight:700;">GRAND TOTAL</td>
                    <?php foreach ($allDates as $date => $total): ?>
                    <td></td>
                    <?php endforeach; ?>
                    <td class="grand-total">
                        <div class="price-cell">
                            <span class="currency">Rp</span>
                            <span class="amount"><?= number_format($grandTotal, 0, ',', '.') ?></span>
                        </div>
                    </td>
                </tr>
            </tfoot>
            <?php endif; ?>
        </table>

        <?php if ($isLastDataPage): ?>
        <!-- Signature Section -->
        <div class="signature-section">
            <div class="signature-box">
                <p class="signature-title">Direktur</p>
                <div class="signature-line"></div>
                <p class="signature-name">Arif Sukmana</p>
            </div>
        </div>
        <?php endif; ?>
        </div><!-- end page-content -->

        <!-- Wave Footer -->
        <div class="page-footer">
            <div class="footer-abstract-waves">
                <svg class="footer-wave-svg" viewBox="0 0 1200 20" preserveAspectRatio="none">
                    <path fill="#374151" opacity="0.5" d="M0,5 C150,15 300,0 450,10 C600,20 750,5 900,12 C1050,20 1150,5 1200,8 L1200,20 L0,20 Z"/>
                    <path fill="#1f2937" opacity="0.8" d="M0,10 Q200,18 400,8 Q600,0 800,12 Q1000,20 1200,10 L1200,20 L0,20 Z"/>
                    <path fill="#111827" d="M0,15 Q100,20 200,12 Q300,5 400,15 Q500,20 600,12 Q700,5 800,15 Q900,20 1000,12 Q1100,5 1200,15 L1200,20 L0,20 Z"/>
                </svg>
            </div>
            <div class="footer-bottom">
                <p><?= e($company['name'] ?? 'PIM Travel') ?> - <?= e($company['tagline'] ?? 'Your Trusted Journey Partner') ?></p>
                <div class="footer-contacts">
                    <?php if (!empty($company['email'])): ?>
                    <span>📧 <?= e($company['email']) ?></span>
                    <?php endif; ?>
                    <?php if (!empty($company['phone'])): ?>
                    <span>📞 <?= e($company['phone']) ?></span>
                    <?php endif; ?>
                    <span>Halaman <?= $pageIndex + 1 ?> dari <?= $totalPages ?></span>
                </div>
            </div>
        </div>
    </div>
    <?php endforeach; ?>
</body>
</html>