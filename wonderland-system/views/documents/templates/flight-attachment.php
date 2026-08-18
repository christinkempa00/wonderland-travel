<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lampiran Pesawat - <?= e($order->order_number) ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Sora:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Sora', sans-serif;
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
            padding: 20px;
            display: flex;
            flex-direction: column;
            align-items: center;
            min-height: 100vh;
            -webkit-print-color-adjust: exact !important;
            print-color-adjust: exact !important;
            color-adjust: exact !important;
        }

        /* Print Button */
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
            transition: all 0.3s ease;
            z-index: 1000;
        }

        .print-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 25px rgba(220, 38, 38, 0.5);
        }

        .print-btn svg {
            width: 18px;
            height: 18px;
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
            transition: all 0.3s ease;
            z-index: 1000;
            text-decoration: none;
        }

        .back-btn:hover {
            transform: translateY(-2px);
            color: white;
        }

        /* A4 Page Container - Fixed height for proper pagination */
        .page-container {
            width: 210mm;
            height: 297mm;
            background: white;
            box-shadow: 0 30px 80px rgba(0,0,0,0.12);
            position: relative;
            margin: 20px auto;
            display: flex;
            flex-direction: column;
            overflow: hidden;
        }

        /* Abstract Wave Header - RED */
        .wave-header {
            position: relative;
            height: 140px;
            background: linear-gradient(135deg, #dc2626 0%, #991b1b 50%, #7f1d1d 100%);
            overflow: visible;
            flex-shrink: 0;
            -webkit-print-color-adjust: exact !important;
            print-color-adjust: exact !important;
        }

        .gradient-overlay {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: 
                radial-gradient(ellipse 80% 50% at 20% 40%, rgba(239, 68, 68, 0.6) 0%, transparent 50%),
                radial-gradient(ellipse 60% 40% at 80% 20%, rgba(185, 28, 28, 0.5) 0%, transparent 40%);
        }

        .organic-shapes {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            overflow: hidden;
        }

        .blob {
            position: absolute;
            border-radius: 50%;
            filter: blur(40px);
        }

        .blob-1 {
            width: 200px;
            height: 200px;
            background: radial-gradient(circle, rgba(255,255,255,0.2) 0%, transparent 70%);
            top: -80px;
            right: -30px;
        }

        .blob-2 {
            width: 140px;
            height: 140px;
            background: radial-gradient(circle, rgba(255,255,255,0.15) 0%, transparent 70%);
            top: 30px;
            left: -20px;
        }

        .header-content {
            position: relative;
            z-index: 10;
            padding: 15px 30px 10px;
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
        }

        .logo-area {
            display: flex;
            align-items: center;
            color: white;
        }

        .logo-box {
            width: 100px;
            height: 50px;
            background: rgba(255,255,255,0.95);
            backdrop-filter: blur(20px);
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            border: 2px solid rgba(255,255,255,0.5);
            box-shadow: 0 8px 32px rgba(0,0,0,0.15);
            overflow: hidden;
            padding: 5px;
        }

        .logo-box img {
            max-width: 100%;
            max-height: 100%;
            object-fit: contain;
        }

        .logo-box .logo-placeholder {
            font-size: 28px;
        }

        .document-badge {
            text-align: right;
            color: white;
        }

        .document-badge .badge-title {
            font-size: 18px;
            font-weight: 800;
            letter-spacing: -0.5px;
            text-shadow: 0 2px 10px rgba(0,0,0,0.2);
        }

        .document-badge .badge-number {
            font-size: 10px;
            opacity: 0.9;
            margin-top: 3px;
        }

        .abstract-waves {
            position: absolute;
            bottom: -2px;
            left: 0;
            width: 100%;
            height: 60px;
            overflow: visible;
        }

        .wave-svg {
            position: absolute;
            bottom: 0;
            left: 0;
            width: 100%;
            height: 60px;
        }

        /* Glass Card */
        .glass-card {
            position: relative;
            margin: -40px 30px 12px;
            z-index: 20;
            background: rgba(255, 255, 255, 0.6);
            backdrop-filter: blur(30px);
            border-radius: 12px;
            padding: 14px 20px;
            box-shadow: 0 8px 32px rgba(220, 38, 38, 0.12), 0 0 0 1px rgba(255, 255, 255, 0.4) inset;
            border: 1px solid rgba(255, 255, 255, 0.5);
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
            flex-shrink: 0;
        }

        .glass-card .info-block h4 {
            font-size: 8px;
            text-transform: uppercase;
            letter-spacing: 1px;
            color: #9ca3af;
            margin-bottom: 3px;
            font-weight: 700;
        }

        .glass-card .info-block p {
            font-size: 11px;
            color: #111827;
            font-weight: 600;
        }

        .glass-card .info-block p.highlight {
            color: #dc2626;
        }

        /* Page Content */
        .page-content {
            flex: 1;
            display: flex;
            flex-direction: column;
        }

        /* Main Content */
        .main-content {
            padding: 0 30px;
            flex-shrink: 0;
        }

        /* Flight Section */
        .flight-section {
            margin-bottom: 10px;
            flex-shrink: 0;
        }

        .flight-header {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 10px 15px;
            background: #fef2f2;
            border-radius: 8px;
            border: 1px solid #fee2e2;
        }

        .flight-header-logo {
            width: 40px;
            height: 40px;
            background: white;
            border-radius: 6px;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            overflow: hidden;
            padding: 4px;
            flex-shrink: 0;
        }

        .flight-header-logo img {
            max-width: 100%;
            max-height: 100%;
            object-fit: contain;
        }

        .flight-header-logo .placeholder {
            font-size: 20px;
        }

        .flight-header-info h3 {
            font-size: 12px;
            font-weight: 700;
            color: #111827;
            margin-bottom: 2px;
        }

        .flight-header-info span {
            font-size: 10px;
            color: #6b7280;
        }

        /* Table */
        .table-container {
            flex-shrink: 0;
        }

        .table-wrapper {
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 2px 10px rgba(0,0,0,0.04);
        }

        .invoice-table {
            width: 100%;
            border-collapse: collapse;
        }

        .invoice-table thead tr {
            background: linear-gradient(135deg, #111827 0%, #1f2937 100%);
            -webkit-print-color-adjust: exact !important;
            print-color-adjust: exact !important;
        }

        .invoice-table th {
            padding: 8px 10px;
            text-align: left;
            font-size: 8px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: white;
        }

        .invoice-table th:last-child {
            text-align: right;
        }

        .invoice-table tbody tr {
            border-bottom: 1px solid #f3f4f6;
        }

        .invoice-table tbody tr:nth-child(even) {
            background: #f9fafb;
            -webkit-print-color-adjust: exact !important;
            print-color-adjust: exact !important;
        }

        .invoice-table td {
            padding: 6px 10px;
            font-size: 9px;
            color: #374151;
            vertical-align: middle;
        }

        .invoice-table td:first-child {
            font-weight: 600;
            color: #9ca3af;
            width: 30px;
        }

        .invoice-table td:last-child {
            text-align: right;
            font-weight: 600;
            color: #111827;
        }

        .service-badge {
            display: inline-block;
            background: linear-gradient(135deg, #faefd0, #f4dfa3);
            color: #6b5216;
            font-size: 7px;
            font-weight: 700;
            padding: 3px 8px;
            border-radius: 10px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            -webkit-print-color-adjust: exact !important;
            print-color-adjust: exact !important;
        }

        .item-title {
            display: block;
            font-weight: 600;
            color: #111827;
            font-size: 9px;
        }

        .item-desc {
            display: block;
            font-size: 8px;
            color: #6b7280;
            margin-top: 1px;
        }

        /* Summary Section - mengikuti tabel */
        .summary-section {
            margin-top: 10px;
            flex-shrink: 0;
        }

        .totals-card {
            background: linear-gradient(135deg, #fef2f2, #fee2e2);
            border-radius: 8px;
            padding: 10px 15px;
            border: 1px solid #fecaca;
            max-width: 280px;
            margin-left: auto;
            -webkit-print-color-adjust: exact !important;
            print-color-adjust: exact !important;
        }

        .total-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 4px 0;
            font-size: 10px;
            color: #374151;
        }

        .total-row.grand {
            border-top: 2px solid #dc2626;
            margin-top: 6px;
            padding-top: 8px;
            font-size: 13px;
            font-weight: 700;
            color: #dc2626;
        }

        /* Signature Section - mengikuti tabel */
        .signature-section {
            margin-top: 12px;
            display: flex;
            justify-content: flex-end;
            flex-shrink: 0;
        }

        .signature-box {
            text-align: center;
            min-width: 160px;
        }

        .place-date {
            font-size: 8px;
            color: #6b7280;
            margin-bottom: 2px;
        }

        .company-label {
            font-size: 8px;
            color: #6b7280;
            margin-bottom: 30px;
        }

        .signature-name {
            font-size: 10px;
            font-weight: 700;
            color: #111827;
            border-top: 1px solid #111827;
            padding-top: 3px;
        }

        .signature-title {
            font-size: 8px;
            color: #6b7280;
        }

        /* Wave Footer - Always at bottom */
        .wave-footer {
            flex-shrink: 0;
            margin-top: auto;
        }

        .footer-abstract-waves {
            height: 25px;
            position: relative;
        }

        .footer-wave-svg {
            width: 100%;
            height: 100%;
        }

        .footer-bottom {
            background: #111827;
            padding: 8px 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            -webkit-print-color-adjust: exact !important;
            print-color-adjust: exact !important;
        }

        .footer-bottom p {
            font-size: 9px;
            font-weight: 600;
            color: white;
        }

        .footer-contacts {
            font-size: 7px;
            color: #9ca3af;
            display: flex;
            gap: 12px;
            align-items: center;
        }

        .page-number {
            background: rgba(255,255,255,0.1);
            padding: 2px 8px;
            border-radius: 4px;
        }

        /* Continuation header for subsequent pages */
        .continuation-header {
            background: linear-gradient(135deg, #dc2626 0%, #991b1b 100%);
            color: white;
            padding: 12px 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-shrink: 0;
            -webkit-print-color-adjust: exact !important;
            print-color-adjust: exact !important;
        }

        .continuation-header h2 {
            font-size: 14px;
            font-weight: 700;
        }

        .continuation-header .page-info {
            font-size: 10px;
            opacity: 0.9;
        }

        /* Print styles */
        @page {
            size: A4 portrait;
            margin: 0;
        }

        @media print {
            html, body {
                width: 210mm;
                height: 297mm;
                margin: 0;
                padding: 0;
                background: white;
            }

            body {
                display: block;
            }

            .print-btn, .back-btn {
                display: none !important;
            }

            .page-container {
                width: 210mm;
                height: 297mm;
                box-shadow: none;
                margin: 0;
                page-break-after: always;
                page-break-inside: avoid;
            }

            .page-container:last-child {
                page-break-after: auto;
            }

            .invoice-table tbody tr {
                page-break-inside: avoid;
            }
        }
    </style>
</head>
<body>
    <a href="/attachment-order/<?= $order->id ?>/flight-passengers" class="back-btn">
        ← Kembali
    </a>
    
    <button class="print-btn" onclick="window.print()">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <path d="M6 9V2h12v7M6 18H4a2 2 0 01-2-2v-5a2 2 0 012-2h16a2 2 0 012 2v5a2 2 0 01-2 2h-2M6 14h12v8H6z"/>
        </svg>
        Cetak
    </button>

    <?php 
    // Configuration - Maksimalkan rows per page
    $rowsPerFirstPage = 20;  // Rows on first page (with header, card, flight info)
    $rowsPerNextPage = 28;   // Rows on continuation pages
    
    // Count total pages needed
    $globalPageNum = 0;
    $totalGlobalPages = 0;
    
    // Calculate total pages
    foreach ($flightItems as $flight) {
        $flightDetails = $detailsByFlight[$flight['id']] ?? [];
        if (empty($flightDetails)) continue;
        
        $detailCount = count($flightDetails);
        if ($detailCount <= $rowsPerFirstPage) {
            $totalGlobalPages += 1;
        } else {
            $remaining = $detailCount - $rowsPerFirstPage;
            $totalGlobalPages += 1 + ceil($remaining / $rowsPerNextPage);
        }
    }
    
    // Get date one day after event date
    $eventDate = strtotime($order->event_date);
    $signatureDate = date('d F Y', strtotime('+1 day', $eventDate));
    $months = [
        'January' => 'Januari', 'February' => 'Februari', 'March' => 'Maret',
        'April' => 'April', 'May' => 'Mei', 'June' => 'Juni',
        'July' => 'Juli', 'August' => 'Agustus', 'September' => 'September',
        'October' => 'Oktober', 'November' => 'November', 'December' => 'Desember'
    ];
    foreach ($months as $en => $id) {
        $signatureDate = str_replace($en, $id, $signatureDate);
    }
    
    // Render pages
    foreach ($flightItems as $flight):
        $flightDetails = $detailsByFlight[$flight['id']] ?? [];
        if (empty($flightDetails)) continue;
        
        $flightLogo = $flight['attachment_logo'] ?? '';
        $flightTotal = 0;
        foreach ($flightDetails as $d) {
            $flightTotal += floatval($d['price']);
        }
        
        $detailCount = count($flightDetails);
        $pageCount = ($detailCount <= $rowsPerFirstPage) ? 1 : 1 + ceil(($detailCount - $rowsPerFirstPage) / $rowsPerNextPage);
        
        // Split details into pages
        $pages = [];
        if ($detailCount <= $rowsPerFirstPage) {
            $pages[] = $flightDetails;
        } else {
            $pages[] = array_slice($flightDetails, 0, $rowsPerFirstPage);
            $remaining = array_slice($flightDetails, $rowsPerFirstPage);
            while (count($remaining) > 0) {
                $pages[] = array_slice($remaining, 0, $rowsPerNextPage);
                $remaining = array_slice($remaining, $rowsPerNextPage);
            }
        }
        
        $flightPageNum = 0;
        $runningNo = 1;
        
        foreach ($pages as $pageIndex => $pageDetails):
            $globalPageNum++;
            $flightPageNum++;
            $isFirstPage = ($pageIndex === 0);
            $isLastPage = ($pageIndex === count($pages) - 1);
            $isLastGlobalPage = ($globalPageNum === $totalGlobalPages);
    ?>
    <div class="page-container">
        <?php if ($isFirstPage): ?>
        <!-- Wave Header (First Page Only) -->
        <div class="wave-header">
            <div class="gradient-overlay"></div>
            <div class="organic-shapes">
                <div class="blob blob-1"></div>
                <div class="blob blob-2"></div>
            </div>
            
            <div class="header-content">
                <div class="logo-area">
                    <div class="logo-box">
                        <?php if ($flightLogo && file_exists(BASE_PATH . '/uploads/logos/' . $flightLogo)): ?>
                        <img src="/uploads/logos/<?= e($flightLogo) ?>" alt="Logo">
                        <?php else: ?>
                        <span class="logo-placeholder">✈️</span>
                        <?php endif; ?>
                    </div>
                </div>
                
                <div class="document-badge">
                    <div class="badge-title">Lampiran Pesawat</div>
                    <div class="badge-number">#<?= e($order->order_number) ?></div>
                </div>
            </div>
            
            <div class="abstract-waves">
                <svg class="wave-svg" viewBox="0 0 1200 60" preserveAspectRatio="none">
                    <path fill="rgba(255,255,255,0.3)" d="M0,25 Q300,50 600,20 T1200,35 L1200,60 L0,60 Z"/>
                    <path fill="rgba(255,255,255,0.6)" d="M0,38 Q300,15 600,42 T1200,30 L1200,60 L0,60 Z"/>
                    <path fill="#ffffff" d="M0,48 Q300,40 600,52 T1200,45 L1200,60 L0,60 Z"/>
                </svg>
            </div>
        </div>

        <!-- Glass Card -->
        <div class="glass-card">
            <div class="info-block">
                <h4>Klien</h4>
                <p><?= e($client['name'] ?? '-') ?></p>
            </div>
            <div class="info-block">
                <h4>Layanan</h4>
                <p class="highlight"><?= e($flight['description'] ?: 'Tiket Pesawat') ?></p>
            </div>
        </div>
        <?php else: ?>
        <!-- Continuation Header (Subsequent Pages) -->
        <div class="continuation-header">
            <h2>Lampiran Pesawat - <?= e($flight['description'] ?: 'Tiket Pesawat') ?></h2>
            <div class="page-info">#<?= e($order->order_number) ?> • Lanjutan</div>
        </div>
        <?php endif; ?>

        <!-- Page Content -->
        <div class="page-content">
            <!-- Main Content -->
            <div class="main-content">
                <?php if ($isFirstPage): ?>
                <div class="flight-section">
                    <div class="flight-header">
                        <div class="flight-header-logo">
                            <?php if ($flightLogo && file_exists(BASE_PATH . '/uploads/logos/' . $flightLogo)): ?>
                            <img src="/uploads/logos/<?= e($flightLogo) ?>" alt="Logo">
                            <?php else: ?>
                            <span class="placeholder">✈️</span>
                            <?php endif; ?>
                        </div>
                        <div class="flight-header-info">
                            <h3><?= e($flight['description'] ?: 'Tiket Pesawat') ?></h3>
                            <span><?= count($flightDetails) ?> Penumpang • <?= e($flight['quantity']) ?> Tiket</span>
                        </div>
                    </div>
                </div>
                <?php endif; ?>
                
                <div class="table-container">
                    <div class="table-wrapper">
                        <table class="invoice-table">
                            <thead>
                                <tr>
                                    <th>No</th>
                                    <th>Type</th>
                                    <th>Nama Penumpang</th>
                                    <th>Harga</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($pageDetails as $detail): ?>
                                <tr>
                                    <td><?= str_pad($runningNo++, 2, '0', STR_PAD_LEFT) ?></td>
                                    <td><span class="service-badge">Flight</span></td>
                                    <td>
                                        <span class="item-title"><?= e($detail['passenger_name']) ?></span>
                                        <?php if (!empty($detail['description'])): ?>
                                        <span class="item-desc"><?= e($detail['description']) ?></span>
                                        <?php endif; ?>
                                    </td>
                                    <td>Rp <?= number_format($detail['price'], 0, ',', '.') ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
                
                <?php if ($isLastPage): ?>
                <!-- Summary (Last Page Only) -->
                <div class="summary-section">
                    <div class="totals-card">
                        <div class="total-row">
                            <span>Jumlah Tiket</span>
                            <span><?= count($flightDetails) ?> tiket</span>
                        </div>
                        <div class="total-row grand">
                            <span>TOTAL</span>
                            <span>Rp <?= number_format($flightTotal, 0, ',', '.') ?></span>
                        </div>
                    </div>
                </div>
                
                <!-- Signature (Last Page Only) -->
                <div class="signature-section">
                    <div class="signature-box">
                        <div class="place-date">Jakarta, <?= $signatureDate ?></div>
                        <div class="company-label">Hormat Kami,</div>
                        <div class="signature-name">Arif Sukmana</div>
                        <div class="signature-title">Direktur</div>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Wave Footer -->
        <div class="wave-footer">
            <div class="footer-abstract-waves">
                <svg class="footer-wave-svg" viewBox="0 0 1200 25" preserveAspectRatio="none">
                    <path fill="#374151" opacity="0.5" d="M0,5 C150,15 300,0 450,10 C600,20 750,5 900,12 C1050,20 1150,5 1200,8 L1200,25 L0,25 Z"/>
                    <path fill="#1f2937" opacity="0.7" d="M0,10 Q200,18 400,8 Q600,0 800,12 Q1000,20 1200,10 L1200,25 L0,25 Z"/>
                    <path fill="#111827" d="M0,15 Q100,20 200,12 Q300,5 400,15 Q500,22 600,12 Q700,5 800,15 Q900,22 1000,12 Q1100,5 1200,15 L1200,25 L0,25 Z"/>
                </svg>
            </div>
            <div class="footer-bottom">
                <p><?= e($company['name'] ?? 'PT Pelita Inti Mulia') ?> - Your Trusted Journey Partner</p>
                <div class="footer-contacts">
                    <?php if (!empty($company['email'])): ?>
                    <span>📧 <?= e($company['email']) ?></span>
                    <?php endif; ?>
                    <?php if (!empty($company['phone'])): ?>
                    <span>📞 <?= e($company['phone']) ?></span>
                    <?php endif; ?>
                    <span class="page-number">Halaman <?= $globalPageNum ?> dari <?= $totalGlobalPages ?></span>
                </div>
            </div>
        </div>
    </div>
    <?php 
        endforeach; // End pages loop
    endforeach; // End flight items loop
    ?>
</body>
</html>