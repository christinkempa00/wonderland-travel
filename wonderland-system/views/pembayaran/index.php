<!-- Summary Cards -->
<div class="row g-3 mb-4">
    <div class="col-6 col-md-3">
        <div class="glass-card summary-card">
            <div class="summary-card-label">Total Tagihan</div>
            <div class="summary-card-value"><?= formatRupiah($summary['total_billed'] ?? 0) ?></div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="glass-card summary-card">
            <div class="summary-card-label">Total Diterima</div>
            <div class="summary-card-value text-success"><?= formatRupiah($summary['total_paid'] ?? 0) ?></div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="glass-card summary-card">
            <div class="summary-card-label">Sisa Piutang</div>
            <div class="summary-card-value text-danger"><?= formatRupiah($summary['total_outstanding'] ?? 0) ?></div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="glass-card summary-card">
            <div class="summary-card-label">Status Pesanan</div>
            <div class="summary-card-value" style="font-size: 0.85rem;">
                <span class="badge badge-danger"><?= (int) ($summary['count_unpaid'] ?? 0) ?> Belum</span>
                <span class="badge badge-warning"><?= (int) ($summary['count_partial'] ?? 0) ?> Sebagian</span>
                <span class="badge badge-success"><?= (int) ($summary['count_paid'] ?? 0) ?> Lunas</span>
            </div>
        </div>
    </div>
</div>

<div class="row g-3">
    <!-- Orders payment tracking table -->
    <div class="col-12 col-xl-8">
        <div class="glass-card mb-4">
            <form method="GET" action="<?= url('/pembayaran') ?>" class="d-flex gap-3 flex-wrap">
                <div class="flex-1" style="min-width: 200px;">
                    <input type="text" name="search" class="form-control"
                           placeholder="Cari nomor order atau nama klien..."
                           value="<?= e($filters['search'] ?? '') ?>">
                </div>
                <div style="min-width: 170px;">
                    <select name="payment_status" class="form-control">
                        <option value="">Semua Status</option>
                        <?php foreach ($paymentStatuses as $key => $status): ?>
                        <option value="<?= $key ?>" <?= ($filters['payment_status'] ?? '') === $key ? 'selected' : '' ?>>
                            <?= $status['label'] ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-filter"></i> Filter
                </button>
                <?php if (($filters['search'] ?? '') || ($filters['payment_status'] ?? '')): ?>
                <a href="<?= url('/pembayaran') ?>" class="btn btn-secondary">
                    <i class="fas fa-times"></i> Reset
                </a>
                <?php endif; ?>
            </form>
        </div>

        <div class="glass-card">
            <?php if (empty($orders)): ?>
            <div class="empty-state">
                <div class="empty-state-icon"><i class="fas fa-money-check-alt"></i></div>
                <h4 class="empty-state-title">Tidak Ada Data</h4>
                <p class="empty-state-text">Belum ada pesanan yang cocok dengan filter ini.</p>
            </div>
            <?php else: ?>
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Klien</th>
                            <th>Total</th>
                            <th>Terbayar</th>
                            <th>Sisa</th>
                            <th>Status</th>
                            <th>Pembayaran Terakhir</th>
                            <th width="60">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($orders as $order):
                            $paid = (float) ($order['paid_amount'] ?? 0);
                            $totalFinal = (float) ($order['total_final_price'] ?? 0);
                            $remaining = max(0, $totalFinal - $paid);
                            $status = PAYMENT_STATUSES[$order['payment_status']] ?? null;
                        ?>
                        <tr>
                            <td>
                                <a href="<?= url('/orders/' . $order['id']) ?>" class="font-medium">
                                    <?= e($order['client_name'] ?? 'Walk-in') ?>
                                </a>
                                <div class="text-muted" style="font-size: 0.75rem;"><?= e($order['order_number']) ?></div>
                            </td>
                            <td><?= formatRupiah($totalFinal) ?></td>
                            <td class="text-success"><?= formatRupiah($paid) ?></td>
                            <td class="<?= $remaining > 0 ? 'text-danger' : '' ?>"><?= formatRupiah($remaining) ?></td>
                            <td>
                                <?php if ($status): ?>
                                <span class="badge badge-<?= $status['color'] ?>"><?= $status['label'] ?></span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?= !empty($order['last_payment_date']) ? formatDate($order['last_payment_date']) : '-' ?>
                            </td>
                            <td>
                                <a href="<?= url('/orders/' . $order['id'] . '/payment') ?>" class="btn btn-sm btn-icon btn-secondary" title="Kelola Pembayaran">
                                    <i class="fas fa-eye"></i>
                                </a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <?php if ($pagination['last_page'] > 1): ?>
            <div class="d-flex align-items-center justify-content-between mt-4 pt-4" style="border-top: 1px solid var(--gray-200);">
                <div class="text-sm text-muted">
                    Menampilkan <?= $pagination['from'] ?> - <?= $pagination['to'] ?> dari <?= $pagination['total'] ?> data
                </div>
                <nav>
                    <ul class="pagination mb-0">
                        <?php if ($pagination['current_page'] > 1): ?>
                        <li class="page-item">
                            <a class="page-link" href="<?= url('/pembayaran?' . http_build_query(array_merge($filters, ['page' => $pagination['current_page'] - 1]))) ?>">
                                <i class="fas fa-chevron-left"></i>
                            </a>
                        </li>
                        <?php endif; ?>
                        <?php
                        $start = max(1, $pagination['current_page'] - 2);
                        $end = min($pagination['last_page'], $pagination['current_page'] + 2);
                        ?>
                        <?php for ($i = $start; $i <= $end; $i++): ?>
                        <li class="page-item <?= $i === $pagination['current_page'] ? 'active' : '' ?>">
                            <a class="page-link" href="<?= url('/pembayaran?' . http_build_query(array_merge($filters, ['page' => $i]))) ?>"><?= $i ?></a>
                        </li>
                        <?php endfor; ?>
                        <?php if ($pagination['current_page'] < $pagination['last_page']): ?>
                        <li class="page-item">
                            <a class="page-link" href="<?= url('/pembayaran?' . http_build_query(array_merge($filters, ['page' => $pagination['current_page'] + 1]))) ?>">
                                <i class="fas fa-chevron-right"></i>
                            </a>
                        </li>
                        <?php endif; ?>
                    </ul>
                </nav>
            </div>
            <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>

    <!-- Recent payments ledger -->
    <div class="col-12 col-xl-4">
        <div class="glass-card">
            <h4 class="card-title mb-3">
                <i class="fas fa-receipt text-primary me-2"></i>
                Transaksi Terbaru
            </h4>
            <?php if (empty($recentPayments)): ?>
            <p class="text-muted text-sm">Belum ada transaksi pembayaran.</p>
            <?php else: ?>
            <div class="recent-payments-list">
                <?php foreach ($recentPayments as $payment): ?>
                <a href="<?= url('/orders/' . $payment['order_id'] . '/payment') ?>" class="recent-payment-item">
                    <div>
                        <div class="font-medium"><?= e($payment['client_name'] ?? 'Walk-in') ?></div>
                        <div class="text-muted" style="font-size: 0.75rem;">
                            <?= e($payment['order_number']) ?> &middot; <?= formatDate($payment['payment_date']) ?>
                        </div>
                    </div>
                    <div class="text-success font-medium"><?= formatRupiah($payment['amount']) ?></div>
                </a>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<style>
.summary-card {
    padding: 1rem 1.25rem;
}
.summary-card-label {
    font-size: 0.75rem;
    color: var(--gray-500);
    text-transform: uppercase;
    letter-spacing: 0.5px;
    margin-bottom: 0.35rem;
}
.summary-card-value {
    font-size: 1.15rem;
    font-weight: 700;
    color: var(--gray-800);
}
.summary-card-value .badge {
    margin-right: 0.25rem;
}
.recent-payments-list {
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
}
.recent-payment-item {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 0.75rem;
    padding: 0.6rem 0.75rem;
    border: 1px solid var(--gray-200);
    border-radius: var(--border-radius);
    text-decoration: none;
    color: inherit;
    transition: all 0.2s;
}
.recent-payment-item:hover {
    background: var(--gray-50);
    border-color: var(--gray-300);
}
</style>
