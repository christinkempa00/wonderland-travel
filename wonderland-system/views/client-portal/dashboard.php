<?php
/**
 * Client Portal Dashboard — Tagihan Tersisa & Riwayat Pesanan
 * Variables: $client, $orders, $outstanding, $totalOutstanding
 */
?>

<div class="row mb-4">
    <div class="col-6 col-md-4">
        <div class="stat-card">
            <div class="stat-icon red">
                <i class="fas fa-hourglass-half"></i>
            </div>
            <div class="stat-content">
                <div class="stat-label">Total Tagihan Tersisa</div>
                <div class="stat-value"><?= formatRupiah($totalOutstanding) ?></div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-4">
        <div class="stat-card">
            <div class="stat-icon yellow">
                <i class="fas fa-file-invoice"></i>
            </div>
            <div class="stat-content">
                <div class="stat-label">Pesanan Belum Lunas</div>
                <div class="stat-value"><?= count($outstanding) ?></div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-4">
        <div class="stat-card">
            <div class="stat-icon blue">
                <i class="fas fa-file-alt"></i>
            </div>
            <div class="stat-content">
                <div class="stat-label">Total Pesanan</div>
                <div class="stat-value"><?= count($orders) ?></div>
            </div>
        </div>
    </div>
</div>

<!-- Tagihan Tersisa -->
<div class="glass-card mb-4">
    <div class="card-header">
        <h3 class="card-title">
            <i class="fas fa-hourglass-half text-danger me-2"></i>
            Tagihan Tersisa
        </h3>
    </div>

    <?php if (empty($outstanding)): ?>
    <div class="empty-state">
        <div class="empty-state-icon"><i class="fas fa-check-circle"></i></div>
        <h4 class="empty-state-title">Tidak Ada Tagihan Tersisa</h4>
        <p class="empty-state-text">Semua pesanan Anda sudah lunas.</p>
    </div>
    <?php else: ?>
    <div class="table-responsive">
        <table class="table">
            <thead>
                <tr>
                    <th>No. Invoice</th>
                    <th>Kegiatan</th>
                    <th>Tanggal Kegiatan</th>
                    <th class="text-right">Total</th>
                    <th class="text-right">Sudah Dibayar</th>
                    <th class="text-right">Sisa Tagihan</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($outstanding as $order): ?>
                <tr>
                    <td><?= e($order['order_number']) ?></td>
                    <td><?= e($order['event_name'] ?: '-') ?></td>
                    <td><?= !empty($order['event_date']) ? formatDate($order['event_date']) : '-' ?></td>
                    <td class="text-right text-nowrap"><?= formatRupiah($order['total_final_price']) ?></td>
                    <td class="text-right text-nowrap text-success"><?= formatRupiah($order['paid_amount']) ?></td>
                    <td class="text-right text-nowrap font-medium text-danger"><?= formatRupiah($order['remaining']) ?></td>
                    <td>
                        <?php $ps = PAYMENT_STATUSES[$order['payment_status']] ?? null; ?>
                        <?php if ($ps): ?>
                        <span class="badge badge-<?= $ps['color'] ?>"><?= $ps['label'] ?></span>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php endif; ?>
</div>

<!-- Riwayat Pesanan -->
<div class="glass-card">
    <div class="card-header">
        <h3 class="card-title">
            <i class="fas fa-history text-primary me-2"></i>
            Riwayat Pesanan
        </h3>
    </div>

    <?php if (empty($orders)): ?>
    <div class="empty-state">
        <div class="empty-state-icon"><i class="fas fa-file-alt"></i></div>
        <h4 class="empty-state-title">Belum Ada Pesanan</h4>
    </div>
    <?php else: ?>
    <div class="table-responsive">
        <table class="table">
            <thead>
                <tr>
                    <th>No. Invoice</th>
                    <th>Kegiatan</th>
                    <th>Tanggal Kegiatan</th>
                    <th class="text-right">Total</th>
                    <th>Status</th>
                    <th>Pembayaran</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($orders as $order): ?>
                <tr>
                    <td><?= e($order['order_number']) ?></td>
                    <td><?= e($order['event_name'] ?: '-') ?></td>
                    <td><?= !empty($order['event_date']) ? formatDate($order['event_date']) : '-' ?></td>
                    <td class="text-right text-nowrap"><?= formatRupiah($order['total_final_price']) ?></td>
                    <td>
                        <?php $status = ORDER_STATUSES[$order['status']] ?? null; ?>
                        <?php if ($status): ?>
                        <span class="badge badge-<?= $status['color'] ?>"><?= $status['label'] ?></span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <?php $ps = PAYMENT_STATUSES[$order['payment_status']] ?? null; ?>
                        <?php if ($ps): ?>
                        <span class="badge badge-<?= $ps['color'] ?>"><?= $ps['label'] ?></span>
                        <?php endif; ?>
                        <?php if (!empty($order['paid_at'])): ?>
                        <small class="d-block text-success mt-1" style="font-size: 0.7rem;">
                            <i class="fas fa-check-circle"></i> <?= formatDate($order['paid_at']) ?>
                        </small>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php endif; ?>
</div>
