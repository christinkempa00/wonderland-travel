<?php
/**
 * Vehicle Documents Print Template
 * Portrait A4 - One page per vehicle
 * Photos: Driver+Kendaraan, SIM, STNK (landscape)
 * Foto di-fit/stretch, tidak di-crop
 */

$pageNum = 0;
$totalPages = count($documents);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lampiran Data Kendaraan - <?= e($order->order_number) ?></title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        @page {
            size: A4 portrait;
            margin: 0;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #f3f4f6;
            color: #1f2937;
            line-height: 1.4;
        }

        .print-controls {
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 1000;
            display: flex;
            gap: 10px;
        }

        .print-controls button,
        .print-controls a {
            padding: 10px 20px;
            border: none;
            border-radius: 8px;
            font-size: 14px;
            cursor: pointer;
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 8px;
            transition: all 0.2s;
        }

        .btn-print {
            background: linear-gradient(135deg, #dc2626 0%, #b91c1c 100%);
            color: white;
        }

        .btn-print:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(220, 38, 38, 0.4);
        }

        .btn-back {
            background: white;
            color: #374151;
            border: 1px solid #e5e7eb;
        }

        .btn-back:hover {
            background: #f9fafb;
        }

        @media print {
            .print-controls {
                display: none !important;
            }
            
            body {
                background: white;
            }
            
            .page-break {
                page-break-after: always;
            }
            
            .page-break:last-child {
                page-break-after: auto;
            }
        }

        /* Page Container - Fixed A4 */
        .page {
            width: 210mm;
            height: 297mm;
            background: white;
            margin: 20px auto;
            box-shadow: 0 10px 40px rgba(0,0,0,0.1);
            position: relative;
            overflow: hidden;
            display: flex;
            flex-direction: column;
        }

        @media print {
            .page {
                margin: 0;
                box-shadow: none;
                width: 210mm;
                height: 297mm;
            }
        }

        /* Wave Header - RED */
        .wave-header {
            position: relative;
            height: 90px;
            background: linear-gradient(135deg, #dc2626 0%, #b91c1c 50%, #991b1b 100%);
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
                radial-gradient(ellipse 80% 50% at 20% 40%, rgba(255, 255, 255, 0.1) 0%, transparent 50%),
                radial-gradient(ellipse 60% 40% at 80% 20%, rgba(255, 200, 200, 0.15) 0%, transparent 40%);
        }

        .header-content {
            position: relative;
            z-index: 10;
            padding: 12px 25px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            height: 100%;
        }

        .header-title {
            color: white;
        }

        .header-title h1 {
            font-size: 16px;
            font-weight: 700;
            margin-bottom: 2px;
        }

        .header-title p {
            font-size: 10px;
            opacity: 0.9;
        }

        .header-badge {
            text-align: right;
            color: white;
        }

        .header-badge .badge-label {
            background: rgba(255,255,255,0.2);
            padding: 4px 12px;
            border-radius: 15px;
            font-size: 8px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 1px;
            margin-bottom: 4px;
            display: inline-block;
        }

        .header-badge .badge-number {
            font-size: 10px;
            opacity: 0.9;
        }

        .abstract-waves {
            position: absolute;
            bottom: -2px;
            left: 0;
            width: 100%;
            height: 35px;
        }

        /* Vehicle Info Card - WITH SHADOW */
        .vehicle-info {
            margin: -20px 20px 12px;
            background: white;
            border-radius: 10px;
            padding: 12px 16px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.12), 0 2px 8px rgba(0,0,0,0.08);
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 12px;
            position: relative;
            z-index: 20;
            border: 1px solid rgba(0,0,0,0.05);
        }

        .info-block h4 {
            font-size: 8px;
            color: #9ca3af;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 3px;
            font-weight: 600;
        }

        .info-block p {
            font-size: 11px;
            font-weight: 600;
            color: #1f2937;
            margin: 0;
        }

        .info-block p.highlight {
            color: #dc2626;
        }

        /* Photo Section - LARGER PHOTOS */
        .photos-section {
            flex: 1;
            padding: 10px 20px 15px;
            display: flex;
            flex-direction: column;
            gap: 10px;
        }

        .photo-row {
            display: grid;
            gap: 10px;
        }

        /* Top Row: Driver & SIM - TALLER */
        .photo-row.top-row {
            grid-template-columns: 1fr 1fr;
            height: 230px;
        }

        /* Bottom Row: STNK - MUCH TALLER */
        .photo-row.bottom-row {
            flex: 1;
            min-height: 180px;
        }

        .photo-box {
            background: #f9fafb;
            border: 1px solid #e5e7eb;
            border-radius: 8px;
            overflow: hidden;
            position: relative;
            display: flex;
            flex-direction: column;
        }

        .photo-label {
            background: linear-gradient(135deg, #dc2626 0%, #b91c1c 100%);
            color: white;
            padding: 6px 10px;
            font-size: 9px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            display: flex;
            align-items: center;
            gap: 5px;
            flex-shrink: 0;
            -webkit-print-color-adjust: exact !important;
            print-color-adjust: exact !important;
        }

        .photo-label i {
            font-size: 10px;
        }

        .photo-container {
            flex: 1;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 6px;
            background: white;
            min-height: 0;
        }

        /* FIT photos - stretch to fill, no crop */
        .photo-container img {
            width: 100%;
            height: 100%;
            object-fit: contain;
        }

        .photo-placeholder {
            color: #d1d5db;
            font-size: 40px;
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 8px;
        }

        .photo-placeholder span {
            font-size: 10px;
            color: #9ca3af;
        }

        /* Footer */
        .page-footer {
            padding: 10px 20px;
            background: #f9fafb;
            border-top: 1px solid #e5e7eb;
            display: flex;
            justify-content: center;
            align-items: center;
            font-size: 8px;
            color: #6b7280;
            flex-shrink: 0;
            -webkit-print-color-adjust: exact !important;
            print-color-adjust: exact !important;
        }

        .footer-company {
            font-weight: 600;
        }

        /* Print specific */
        @media print {
            .wave-header,
            .photo-label,
            .page-footer {
                -webkit-print-color-adjust: exact !important;
                print-color-adjust: exact !important;
            }
            
            .vehicle-info {
                box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            }
        }
    </style>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
</head>
<body>
    <!-- Print Controls -->
    <div class="print-controls">
        <a href="<?= url('/attachment-order/' . $order->id . '/vehicle-documents') ?>" class="btn-back">
            <i class="fas fa-arrow-left"></i> Kembali
        </a>
        <button onclick="window.print()" class="btn-print">
            <i class="fas fa-print"></i> Cetak
        </button>
    </div>

    <?php foreach ($documents as $doc): 
        $pageNum++;
    ?>
    <div class="page <?= $pageNum < $totalPages ? 'page-break' : '' ?>">
        <!-- Wave Header - RED -->
        <div class="wave-header">
            <div class="gradient-overlay"></div>
            <div class="header-content">
                <div class="header-title">
                    <h1><i class="fas fa-car"></i> DATA KENDARAAN</h1>
                    <p><?= e($company['name'] ?? 'PIM Travel') ?></p>
                </div>
                <div class="header-badge">
                    <div class="badge-label">Lampiran Kendaraan</div>
                    <div class="badge-number">#<?= e($order->order_number) ?></div>
                </div>
            </div>
            <div class="abstract-waves">
                <svg width="100%" height="35" viewBox="0 0 1200 35" preserveAspectRatio="none">
                    <path fill="white" opacity="0.3" d="M0,18 Q300,35 600,18 T1200,18 L1200,35 L0,35 Z"/>
                    <path fill="white" opacity="0.5" d="M0,22 Q300,5 600,22 T1200,22 L1200,35 L0,35 Z"/>
                    <path fill="white" d="M0,28 Q300,35 600,28 T1200,28 L1200,35 L0,35 Z"/>
                </svg>
            </div>
        </div>

        <!-- Vehicle Info Card with Shadow -->
        <div class="vehicle-info">
            <div class="info-block">
                <h4>Jenis Kendaraan</h4>
                <p class="highlight"><?= e($doc['vehicle_type']) ?></p>
            </div>
            <div class="info-block">
                <h4>Plat Nomor</h4>
                <p><?= e($doc['plate_number'] ?: '-') ?></p>
            </div>
            <div class="info-block">
                <h4>Nama Driver</h4>
                <p><?= e($doc['driver_name'] ?: '-') ?></p>
            </div>
            <div class="info-block">
                <h4>Klien</h4>
                <p><?= e($client['name'] ?? '-') ?></p>
            </div>
        </div>

        <!-- Photos Section - LARGER -->
        <div class="photos-section">
            <!-- Top Row: Driver Photo & SIM - TALLER -->
            <div class="photo-row top-row">
                <!-- Driver + Vehicle Photo -->
                <div class="photo-box">
                    <div class="photo-label">
                        <i class="fas fa-user"></i> Foto Driver & Kendaraan
                    </div>
                    <div class="photo-container">
                        <?php if (!empty($doc['photo_driver'])): ?>
                        <img src="/uploads/vehicles/<?= e($doc['photo_driver']) ?>" alt="Driver & Kendaraan">
                        <?php else: ?>
                        <div class="photo-placeholder">
                            <i class="fas fa-user-tie"></i>
                            <span>Foto tidak tersedia</span>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- SIM Photo -->
                <div class="photo-box">
                    <div class="photo-label">
                        <i class="fas fa-id-card"></i> Foto SIM
                    </div>
                    <div class="photo-container">
                        <?php if (!empty($doc['photo_sim'])): ?>
                        <img src="/uploads/vehicles/<?= e($doc['photo_sim']) ?>" alt="SIM">
                        <?php else: ?>
                        <div class="photo-placeholder">
                            <i class="fas fa-id-card"></i>
                            <span>Foto tidak tersedia</span>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Bottom Row: STNK - FULL WIDTH, MUCH TALLER -->
            <div class="photo-row bottom-row">
                <div class="photo-box">
                    <div class="photo-label">
                        <i class="fas fa-file-alt"></i> Foto STNK
                    </div>
                    <div class="photo-container">
                        <?php if (!empty($doc['photo_stnk'])): ?>
                        <img src="/uploads/vehicles/<?= e($doc['photo_stnk']) ?>" alt="STNK">
                        <?php else: ?>
                        <div class="photo-placeholder">
                            <i class="fas fa-file-alt"></i>
                            <span>Foto tidak tersedia</span>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Footer -->
        <div class="page-footer">
            <div class="footer-company">
                <?= e($company['name'] ?? 'PIM Travel') ?> - <?= e($company['tagline'] ?? 'Your Trusted Journey Partner') ?>
            </div>
        </div>
    </div>
    <?php endforeach; ?>
</body>
</html>