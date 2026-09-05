<?php
/**
 * Client Portal — Detail Pesanan (dokumen + e-tiket, read-only)
 * Variables: $order, $tickets
 */
?>

<div class="mb-4">
    <a href="<?= url('/portal') ?>" class="btn btn-sm btn-secondary">
        <i class="fas fa-arrow-left"></i> Kembali
    </a>
</div>

<div class="glass-card mb-4">
    <h3 class="mb-3"><?= e($order['invoice_display']) ?></h3>
    <div class="row">
        <div class="col-6">
            <div class="text-sm text-muted">Kegiatan</div>
            <div class="font-medium mb-3"><?= e($order['event_name'] ?: '-') ?></div>

            <div class="text-sm text-muted">Tanggal Kegiatan</div>
            <div class="font-medium mb-3">
                <?= !empty($order['event_date']) ? formatDate($order['event_date']) : '-' ?>
                <?php if (!empty($order['event_end_date']) && $order['event_end_date'] !== $order['event_date']): ?>
                    s/d <?= formatDate($order['event_end_date']) ?>
                <?php endif; ?>
            </div>
        </div>
        <div class="col-6">
            <div class="text-sm text-muted">Total Tagihan</div>
            <div class="font-medium mb-3"><?= formatRupiah($order['total_final_price']) ?></div>

            <div class="text-sm text-muted">Status</div>
            <div class="mb-3">
                <?php $ps = PAYMENT_STATUSES[$order['payment_status']] ?? null; ?>
                <?php if ($ps): ?>
                <span class="badge badge-<?= $ps['color'] ?>"><?= $ps['label'] ?></span>
                <?php endif; ?>
                <?php if ($order['remaining'] > 0): ?>
                <span class="text-danger font-medium ms-2">Sisa: <?= formatRupiah($order['remaining']) ?></span>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Dokumen -->
<div class="glass-card mb-4">
    <h3 class="mb-3"><i class="fas fa-file-pdf text-primary"></i> Dokumen</h3>
    <div class="doc-buttons">
        <a href="<?= url('/doc.php?order=' . $order['id'] . '&type=invoice') ?>" target="_blank" class="doc-btn doc-invoice">
            <span class="doc-icon"><i class="fas fa-file-invoice-dollar"></i></span>
            <span class="doc-text">
                <strong>Invoice</strong>
                <small>Tagihan Pembayaran</small>
            </span>
            <i class="fas fa-external-link-alt"></i>
        </a>
        <a href="<?= url('/doc.php?order=' . $order['id'] . '&type=kwitansi') ?>" target="_blank" class="doc-btn doc-kwitansi">
            <span class="doc-icon"><i class="fas fa-receipt"></i></span>
            <span class="doc-text">
                <strong>Kwitansi</strong>
                <small>Bukti Pembayaran</small>
            </span>
            <i class="fas fa-external-link-alt"></i>
        </a>
    </div>
</div>

<!-- E-Tiket -->
<div class="glass-card">
    <h3 class="mb-3"><i class="fas fa-ticket-alt text-primary"></i> E-Tiket</h3>

    <?php if (empty($tickets)): ?>
    <p class="text-muted mb-0">Belum ada e-tiket yang diupload.</p>
    <?php else: ?>
    <div class="ticket-list">
        <?php foreach ($tickets as $ticket): ?>
        <div class="ticket-item">
            <i class="fas fa-file-pdf text-danger"></i>
            <a href="<?= e(uploadUrl($ticket['file_path'])) ?>" target="_blank" class="ticket-name">
                <?= e($ticket['file_name']) ?>
            </a>
            <span class="ticket-size"><?= formatFileSize((int) $ticket['file_size']) ?></span>
        </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>
</div>

<style>
.doc-buttons { display: flex; flex-direction: column; gap: 0.5rem; }
.doc-btn {
    display: flex; align-items: center; gap: 0.75rem;
    padding: 0.75rem 1rem; border-radius: 10px;
    text-decoration: none; color: white; transition: all 0.2s;
}
.doc-btn:hover { transform: translateY(-2px); box-shadow: 0 6px 16px rgba(0,0,0,0.15); color: white; }
.doc-btn .doc-icon { font-size: 1.3rem; }
.doc-btn .doc-text { flex: 1; }
.doc-btn .doc-text strong { display: block; font-size: 0.85rem; }
.doc-btn .doc-text small { font-size: 0.7rem; opacity: 0.85; }
.doc-btn .fa-external-link-alt { opacity: 0.6; font-size: 0.7rem; }
.doc-invoice { background: linear-gradient(135deg, #dc2626, #b91c1c); }
.doc-kwitansi { background: linear-gradient(135deg, #7c3aed, #6d28d9); }

.ticket-list { display: flex; flex-direction: column; gap: 0.5rem; }
.ticket-item {
    display: flex; align-items: center; gap: 0.75rem;
    padding: 0.6rem 0.75rem; border: 1px solid var(--gray-200); border-radius: 8px;
}
.ticket-item .ticket-name { flex: 1; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
.ticket-item .ticket-size { font-size: 0.75rem; color: var(--gray-500); white-space: nowrap; }
</style>
