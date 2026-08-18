<?php
/**
 * Invoice List View
 */
$statusColors = ['unpaid' => 'danger', 'partial' => 'warning', 'paid' => 'success'];
$statusLabels = ['unpaid' => 'Belum Bayar', 'partial' => 'Sebagian', 'paid' => 'Lunas'];
?>

<!-- Search & Filter -->
<div class="glass-card mb-4">
    <form method="GET" action="<?= url('/invoices') ?>" class="d-flex gap-3 flex-wrap">
        <div class="flex-1">
            <div class="input-group">
                <span class="input-group-text"><i class="fas fa-search"></i></span>
                <input type="text" name="search" class="form-control"
                       placeholder="Cari no. invoice, no. order, atau nama klien..."
                       value="<?= e($search) ?>">
            </div>
        </div>
        <select name="payment_status" class="form-control" style="max-width: 200px;">
            <option value="">-- Semua Status Bayar --</option>
            <?php foreach ($statusLabels as $key => $label): ?>
            <option value="<?= $key ?>" <?= $paymentStatus === $key ? 'selected' : '' ?>><?= $label ?></option>
            <?php endforeach; ?>
        </select>
        <button type="submit" class="btn btn-primary">
            <i class="fas fa-search"></i> Cari
        </button>
        <?php if ($search || $paymentStatus): ?>
        <a href="<?= url('/invoices') ?>" class="btn btn-secondary">
            <i class="fas fa-times"></i> Reset
        </a>
        <?php endif; ?>
    </form>
</div>

<!-- Invoice List -->
<div class="glass-card">
    <?php if (empty($invoices)): ?>
    <div class="empty-state">
        <div class="empty-state-icon">
            <i class="fas fa-file-invoice-dollar"></i>
        </div>
        <h4 class="empty-state-title">
            <?= ($search || $paymentStatus) ? 'Tidak Ada Hasil' : 'Belum Ada Invoice' ?>
        </h4>
        <p class="empty-state-text">
            Invoice dibuat dari halaman detail order dengan tombol "Buat Invoice Resmi".
        </p>
    </div>
    <?php else: ?>
    <div class="table-responsive">
        <table class="table">
            <thead>
                <tr>
                    <th>No. Invoice</th>
                    <th>Order</th>
                    <th>Klien</th>
                    <th>Jatuh Tempo</th>
                    <th>Total</th>
                    <th>Status Bayar</th>
                    <th width="80">Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($invoices as $inv): ?>
                <tr>
                    <td>
                        <a href="<?= url('/invoices/' . $inv['id']) ?>" class="font-medium">
                            <?= e($inv['invoice_number']) ?>
                        </a>
                    </td>
                    <td>
                        <a href="<?= url('/orders/' . $inv['order_id']) ?>"><?= e($inv['order_number']) ?></a>
                    </td>
                    <td><?= e($inv['client_name'] ?? '-') ?></td>
                    <td><?= $inv['due_date'] ? formatDate($inv['due_date']) : '-' ?></td>
                    <td><?= formatRupiah($inv['total']) ?></td>
                    <td>
                        <span class="badge badge-<?= $statusColors[$inv['payment_status']] ?? 'secondary' ?>">
                            <?= $statusLabels[$inv['payment_status']] ?? $inv['payment_status'] ?>
                        </span>
                    </td>
                    <td>
                        <a href="<?= url('/invoices/' . $inv['id']) ?>"
                           class="btn btn-sm btn-icon btn-secondary" title="Lihat">
                            <i class="fas fa-eye"></i>
                        </a>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <!-- Pagination -->
    <?php if ($pagination && isset($pagination['last_page']) && $pagination['last_page'] > 1): ?>
    <div class="d-flex align-items-center justify-content-between mt-4 pt-4" style="border-top: 1px solid var(--gray-200);">
        <div class="text-sm text-muted">
            Menampilkan <?= $pagination['from'] ?> - <?= $pagination['to'] ?> dari <?= $pagination['total'] ?> data
        </div>
        <nav class="pagination">
            <?php if ($pagination['current_page'] > 1): ?>
            <a href="<?= url('/invoices?page=' . ($pagination['current_page'] - 1) . ($search ? '&search=' . urlencode($search) : '')) ?>"
               class="page-link">
                <i class="fas fa-chevron-left"></i>
            </a>
            <?php endif; ?>

            <?php
            $start = max(1, $pagination['current_page'] - 2);
            $end = min($pagination['last_page'], $pagination['current_page'] + 2);
            for ($i = $start; $i <= $end; $i++):
            ?>
            <a href="<?= url('/invoices?page=' . $i . ($search ? '&search=' . urlencode($search) : '')) ?>"
               class="page-link <?= $i === $pagination['current_page'] ? 'active' : '' ?>">
                <?= $i ?>
            </a>
            <?php endfor; ?>

            <?php if ($pagination['current_page'] < $pagination['last_page']): ?>
            <a href="<?= url('/invoices?page=' . ($pagination['current_page'] + 1) . ($search ? '&search=' . urlencode($search) : '')) ?>"
               class="page-link">
                <i class="fas fa-chevron-right"></i>
            </a>
            <?php endif; ?>
        </nav>
    </div>
    <?php endif; ?>
    <?php endif; ?>
</div>
