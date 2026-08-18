<?php
/**
 * Surat Permohonan Pembayaran - Printable Letter
 * PLACEHOLDER TEMPLATE - desain resmi menyusul dari user.
 * Required directly from SuratPermohonanController::generate(), bukan lewat render().
 */
$logoUrl = function_exists('getLogoUrl') ? getLogoUrl($company) : null;
$companyName = $company['name'] ?? 'Wonderland Travel';
$companyAddress = $company['address'] ?? '';
$companyPhone = $company['phone'] ?? '';
$letterNumber = 'SPP/' . date('Y') . '/' . str_pad((string) $client['id'], 4, '0', STR_PAD_LEFT) . '/' . date('md', strtotime($cutoffDate));
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Surat Permohonan Pembayaran - <?= e($client['name']) ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Sora:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Sora', sans-serif;
            background: #e9ecef;
            padding: 20px;
            display: flex;
            flex-direction: column;
            align-items: center;
            min-height: 100vh;
        }
        .print-btn, .back-btn {
            position: fixed;
            top: 20px;
            border: none;
            padding: 12px 24px;
            font-family: 'Sora', sans-serif;
            font-size: 14px;
            font-weight: 600;
            border-radius: 10px;
            cursor: pointer;
            text-decoration: none;
            z-index: 1000;
        }
        .print-btn { right: 20px; background: #c89b2c; color: #1a1a1a; }
        .back-btn { left: 20px; background: #6b7280; color: white; }

        .letter {
            width: 210mm;
            min-height: 297mm;
            background: white;
            box-shadow: 0 20px 60px rgba(0,0,0,0.12);
            margin-top: 20px;
            padding: 30mm 20mm 20mm;
            color: #1a1a1a;
        }
        .letterhead {
            display: flex;
            align-items: center;
            gap: 16px;
            padding-bottom: 16px;
            border-bottom: 3px solid #c9a227;
            margin-bottom: 24px;
        }
        .letterhead img { height: 56px; width: auto; object-fit: contain; }
        .letterhead .co-name { font-size: 20px; font-weight: 700; letter-spacing: 0.5px; }
        .letterhead .co-meta { font-size: 11px; color: #555; margin-top: 2px; }

        .letter-meta { display: flex; justify-content: space-between; font-size: 13px; margin-bottom: 20px; }
        .letter-meta div span { display: block; }
        .letter-meta .no-label { color: #555; font-size: 11px; }

        .salutation { font-size: 13px; margin-bottom: 16px; }
        .salutation strong { display: block; font-size: 14px; }

        p.body-text { font-size: 13px; line-height: 1.7; text-align: justify; margin-bottom: 14px; }

        table.invoice-table { width: 100%; border-collapse: collapse; margin: 18px 0; font-size: 12px; }
        table.invoice-table th, table.invoice-table td {
            border: 1px solid #ccc;
            padding: 8px 10px;
            text-align: left;
        }
        table.invoice-table th { background: #f5efdc; font-weight: 700; }
        table.invoice-table td.num { text-align: right; }
        table.invoice-table tfoot td { font-weight: 700; background: #faf7ee; }

        .closing { margin-top: 24px; font-size: 13px; line-height: 1.7; }
        .signature { margin-top: 50px; font-size: 13px; }
        .signature .name { margin-top: 60px; font-weight: 700; text-decoration: underline; }

        @media print {
            body { background: white; padding: 0; }
            .print-btn, .back-btn { display: none; }
            .letter { box-shadow: none; margin: 0; }
        }
    </style>
</head>
<body>
    <a href="javascript:history.back()" class="back-btn"><i class="fas fa-arrow-left"></i> Kembali</a>
    <button class="print-btn" onclick="window.print()">Cetak / Simpan PDF</button>

    <div class="letter">
        <div class="letterhead">
            <?php if ($logoUrl): ?>
            <img src="<?= e($logoUrl) ?>" alt="Logo">
            <?php endif; ?>
            <div>
                <div class="co-name"><?= e($companyName) ?></div>
                <div class="co-meta">
                    <?= e($companyAddress) ?><?= $companyPhone ? ' &middot; ' . e($companyPhone) : '' ?>
                </div>
            </div>
        </div>

        <div class="letter-meta">
            <div>
                <span class="no-label">Nomor</span>
                <span><?= e($letterNumber) ?></span>
            </div>
            <div>
                <span class="no-label">Tanggal Cutoff</span>
                <span><?= e(formatDateIndo($cutoffDate)) ?></span>
            </div>
        </div>

        <div class="salutation">
            Kepada Yth.<br>
            <strong><?= e($client['name']) ?></strong>
            <?php if (!empty($client['address'])): ?>
            <?= e($client['address']) ?><br>
            <?php endif; ?>
            <?php if (!empty($client['contact_person'])): ?>
            U.p. <?= e($client['contact_person']) ?>
            <?php endif; ?>
        </div>

        <p class="body-text">
            Dengan hormat, sehubungan dengan kerja sama yang telah terjalin, bersama surat ini kami sampaikan
            permohonan pembayaran atas tagihan-tagihan berikut yang tercatat masih belum lunas per tanggal
            cutoff <?= e(formatDateIndo($cutoffDate)) ?>:
        </p>

        <table class="invoice-table">
            <thead>
                <tr>
                    <th width="30">No</th>
                    <th>No. Order</th>
                    <th>Kegiatan</th>
                    <th class="num">Total Tagihan</th>
                    <th class="num">Terbayar</th>
                    <th class="num">Sisa Tagihan</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($orders as $i => $order):
                    $remaining = max(0, (float) $order['total_final_price'] - (float) $order['paid_amount']);
                ?>
                <tr>
                    <td><?= $i + 1 ?></td>
                    <td><?= e($order['order_number']) ?></td>
                    <td><?= e($order['event_name']) ?: '-' ?></td>
                    <td class="num"><?= formatRupiah($order['total_final_price']) ?></td>
                    <td class="num"><?= formatRupiah($order['paid_amount']) ?></td>
                    <td class="num"><?= formatRupiah($remaining) ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
            <tfoot>
                <tr>
                    <td colspan="5">Total Sisa Tagihan</td>
                    <td class="num"><?= formatRupiah($totalOutstanding) ?></td>
                </tr>
            </tfoot>
        </table>

        <p class="body-text">
            Kami mohon kesediaan Bapak/Ibu untuk dapat menyelesaikan pembayaran atas tagihan-tagihan tersebut
            di atas dalam waktu sesegera mungkin. Apabila pembayaran telah dilakukan sebelum surat ini diterima,
            mohon informasinya agar dapat kami sesuaikan. Atas perhatian dan kerja samanya, kami ucapkan terima kasih.
        </p>

        <div class="closing">Hormat kami,</div>
        <div class="signature">
            <?= e($companyName) ?>
            <div class="name">&nbsp;</div>
        </div>
    </div>
</body>
</html>
