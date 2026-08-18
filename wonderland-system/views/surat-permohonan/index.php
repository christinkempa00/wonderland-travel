<div class="row g-3">
    <!-- Client list with outstanding balance -->
    <div class="col-12 col-lg-4">
        <div class="glass-card">
            <h4 class="card-title mb-3">
                <i class="fas fa-users text-primary me-2"></i>
                Klien dengan Tagihan Belum Lunas
            </h4>
            <?php if (empty($clientsWithOutstanding)): ?>
            <p class="text-muted text-sm">Tidak ada klien dengan tagihan belum lunas saat ini.</p>
            <?php else: ?>
            <div class="client-outstanding-list">
                <?php foreach ($clientsWithOutstanding as $c): ?>
                <a href="<?= url('/surat-permohonan?client_id=' . $c['id']) ?>"
                   class="client-outstanding-item <?= ((int) $selectedClientId === (int) $c['id']) ? 'active' : '' ?>">
                    <div>
                        <div class="font-medium"><?= e($c['name']) ?></div>
                        <div class="text-muted" style="font-size: 0.75rem;"><?= (int) $c['outstanding_count'] ?> tagihan belum lunas</div>
                    </div>
                    <div class="text-danger font-medium"><?= formatRupiah($c['outstanding_total']) ?></div>
                </a>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Invoice selection for the chosen client -->
    <div class="col-12 col-lg-8">
        <div class="glass-card">
            <?php if (!$selectedClient): ?>
            <div class="empty-state">
                <div class="empty-state-icon"><i class="fas fa-file-signature"></i></div>
                <h4 class="empty-state-title">Pilih Klien</h4>
                <p class="empty-state-text">Pilih klien di sebelah kiri untuk melihat tagihan yang bisa dilampirkan ke surat permohonan.</p>
            </div>
            <?php elseif (empty($unpaidOrders)): ?>
            <div class="empty-state">
                <div class="empty-state-icon"><i class="fas fa-check-circle text-success"></i></div>
                <h4 class="empty-state-title"><?= e($selectedClient['name']) ?> tidak punya tagihan belum lunas</h4>
            </div>
            <?php else: ?>
            <h4 class="card-title mb-1">
                <i class="fas fa-file-invoice-dollar text-primary me-2"></i>
                Tagihan <?= e($selectedClient['name']) ?>
            </h4>
            <p class="text-muted text-sm mb-3">Centang tagihan yang ingin dilampirkan, lalu buat surat.</p>

            <form method="GET" action="<?= url('/surat-permohonan/generate') ?>" target="_blank" id="suratForm">
                <input type="hidden" name="client_id" value="<?= (int) $selectedClient['id'] ?>">

                <div class="form-group mb-3" style="max-width: 260px;">
                    <label class="form-label">Per Tanggal Cutoff</label>
                    <input type="date" name="cutoff_date" class="form-control" value="<?= e(date('Y-m-d')) ?>">
                </div>

                <div class="table-responsive mb-3">
                    <table class="table">
                        <thead>
                            <tr>
                                <th width="30">
                                    <input type="checkbox" id="checkAll">
                                </th>
                                <th>No. Order</th>
                                <th>Kegiatan</th>
                                <th>Total</th>
                                <th>Sisa Tagihan</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($unpaidOrders as $order):
                                $remaining = max(0, (float) $order['total_final_price'] - (float) $order['paid_amount']);
                            ?>
                            <tr>
                                <td>
                                    <input type="checkbox" name="order_ids[]" value="<?= (int) $order['id'] ?>" class="order-check" checked>
                                </td>
                                <td><?= e($order['order_number']) ?></td>
                                <td><?= e($order['event_name']) ?: '-' ?></td>
                                <td><?= formatRupiah($order['total_final_price']) ?></td>
                                <td class="text-danger font-medium"><?= formatRupiah($remaining) ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-file-export"></i> Buat Surat Permohonan
                </button>
            </form>
            <?php endif; ?>
        </div>
    </div>
</div>

<style>
.client-outstanding-list {
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
}
.client-outstanding-item {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 0.75rem;
    padding: 0.65rem 0.85rem;
    border: 1px solid var(--gray-200);
    border-radius: var(--border-radius);
    text-decoration: none;
    color: inherit;
    transition: all 0.2s;
}
.client-outstanding-item:hover {
    background: var(--gray-50);
    border-color: var(--gray-300);
}
.client-outstanding-item.active {
    background: rgba(200, 155, 44, 0.1);
    border-color: #c89b2c;
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    var checkAll = document.getElementById('checkAll');
    if (checkAll) {
        checkAll.checked = true;
        checkAll.addEventListener('change', function() {
            document.querySelectorAll('.order-check').forEach(function(cb) {
                cb.checked = checkAll.checked;
            });
        });
    }

    var form = document.getElementById('suratForm');
    if (form) {
        form.addEventListener('submit', function(e) {
            var checked = form.querySelectorAll('.order-check:checked');
            if (checked.length === 0) {
                e.preventDefault();
                alert('Pilih minimal satu tagihan untuk dilampirkan.');
            }
        });
    }
});
</script>
