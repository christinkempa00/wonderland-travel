<?php
/**
 * Client Detail View
 */
?>

<div class="row">
    <!-- Main Info -->
    <div class="col-6">
        <div class="glass-card mb-4">
            <div class="d-flex align-items-start justify-content-between mb-4">
                <div>
                    <h3 class="mb-1">
                        <?= e($client->name) ?>
                        <?php if ($client->client_code): ?>
                        <span class="badge badge-secondary" style="font-size: 0.7rem; vertical-align: middle;"><?= e($client->client_code) ?></span>
                        <?php endif; ?>
                    </h3>
                    <?php if ($client->contact_person): ?>
                    <p class="text-muted mb-0">
                        <i class="fas fa-user"></i> <?= e($client->contact_person) ?>
                    </p>
                    <?php endif; ?>
                </div>
                <?php if (Session::can('clients.update')): ?>
                <a href="<?= url('/clients/' . $client->id . '/edit') ?>" class="btn btn-primary">
                    <i class="fas fa-edit"></i> Edit
                </a>
                <?php endif; ?>
            </div>
            
            <div class="info-grid">
                <?php if ($client->email): ?>
                <div class="info-item">
                    <label><i class="fas fa-envelope"></i> Email</label>
                    <a href="mailto:<?= e($client->email) ?>"><?= e($client->email) ?></a>
                </div>
                <?php endif; ?>
                
                <?php if ($client->phone): ?>
                <div class="info-item">
                    <label><i class="fas fa-phone"></i> Telepon</label>
                    <a href="https://wa.me/<?= e($client->getWhatsAppPhone()) ?>" target="_blank" class="text-success">
                        <i class="fab fa-whatsapp"></i> <?= e($client->phone) ?>
                    </a>
                </div>
                <?php endif; ?>
                
                <?php if ($client->address): ?>
                <div class="info-item full-width">
                    <label><i class="fas fa-map-marker-alt"></i> Alamat</label>
                    <span><?= nl2br(e($client->address)) ?></span>
                </div>
                <?php endif; ?>
                
                <?php if ($client->npwp): ?>
                <div class="info-item">
                    <label><i class="fas fa-id-card"></i> NPWP</label>
                    <span><?= e($client->npwp) ?></span>
                </div>
                <?php endif; ?>
                
                <?php if ($client->notes): ?>
                <div class="info-item full-width">
                    <label><i class="fas fa-sticky-note"></i> Catatan</label>
                    <span><?= nl2br(e($client->notes)) ?></span>
                </div>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- Recent Orders -->
        <div class="glass-card">
            <div class="d-flex align-items-center justify-content-between mb-4">
                <h4 class="card-title mb-0">
                    <i class="fas fa-file-alt text-primary"></i>
                    Pesanan Terbaru
                </h4>
                <?php if (Session::can('orders.create')): ?>
                <a href="<?= url('/orders/create?client_id=' . $client->id) ?>" class="btn btn-sm btn-success">
                    <i class="fas fa-plus"></i> Buat Pesanan
                </a>
                <?php endif; ?>
            </div>
            
            <?php if (empty($orders)): ?>
            <div class="text-center py-4 text-muted">
                <i class="fas fa-file-alt fa-2x mb-2" style="opacity: 0.3;"></i>
                <p class="mb-0">Belum ada pesanan</p>
            </div>
            <?php else: ?>
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th>No. Order</th>
                            <th>Tanggal</th>
                            <th>Total</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($orders as $order): ?>
                        <tr>
                            <td>
                                <a href="<?= url('/orders/' . $order['id']) ?>">
                                    <?= e($order['order_number']) ?>
                                </a>
                            </td>
                            <td><?= formatDate($order['order_date']) ?></td>
                            <td><?= formatRupiah($order['total_final_price']) ?></td>
                            <td>
                                <?php $status = ORDER_STATUSES[$order['status']] ?? null; ?>
                                <?php if ($status): ?>
                                <span class="badge badge-<?= $status['color'] ?>">
                                    <?= $status['label'] ?>
                                </span>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php endif; ?>
        </div>

        <!-- Communications -->
        <div class="glass-card mt-4">
            <h4 class="card-title mb-4">
                <i class="fas fa-comments text-primary"></i>
                Riwayat Komunikasi
            </h4>

            <form method="POST" action="<?= url('/clients/' . $client->id . '/communications') ?>" class="mb-4">
                <?= csrfField() ?>
                <div class="row g-2">
                    <div class="col-4">
                        <select name="type" class="form-control" required>
                            <option value="phone">Telepon</option>
                            <option value="whatsapp">WhatsApp</option>
                            <option value="email">Email</option>
                            <option value="in_person">Tatap Muka</option>
                            <option value="other">Lainnya</option>
                        </select>
                    </div>
                    <div class="col-4">
                        <input type="date" name="communication_date" class="form-control" value="<?= date('Y-m-d') ?>" required>
                    </div>
                    <div class="col-4">
                        <button type="submit" class="btn btn-primary w-100">
                            <i class="fas fa-plus"></i> Tambah
                        </button>
                    </div>
                    <div class="col-12">
                        <textarea name="notes" class="form-control" rows="2" placeholder="Catatan isi komunikasi..."></textarea>
                    </div>
                </div>
            </form>

            <?php if (empty($communications)): ?>
            <div class="text-center py-3 text-muted">
                <p class="mb-0">Belum ada riwayat komunikasi</p>
            </div>
            <?php else: ?>
            <?php
            $commIcons = ['phone' => 'fa-phone', 'whatsapp' => 'fa-whatsapp', 'email' => 'fa-envelope', 'in_person' => 'fa-handshake', 'other' => 'fa-comment'];
            $commLabels = ['phone' => 'Telepon', 'whatsapp' => 'WhatsApp', 'email' => 'Email', 'in_person' => 'Tatap Muka', 'other' => 'Lainnya'];
            ?>
            <div class="d-flex flex-column gap-2">
                <?php foreach ($communications as $comm): ?>
                <div class="info-item">
                    <div class="d-flex justify-content-between align-items-start mb-1">
                        <span>
                            <i class="fa<?= $comm['type'] === 'whatsapp' ? 'b' : 's' ?> <?= $commIcons[$comm['type']] ?? 'fa-comment' ?>"></i>
                            <strong><?= $commLabels[$comm['type']] ?? $comm['type'] ?></strong>
                        </span>
                        <small class="text-muted"><?= formatDate($comm['communication_date']) ?></small>
                    </div>
                    <?php if ($comm['notes']): ?>
                    <p class="mb-1"><?= nl2br(e($comm['notes'])) ?></p>
                    <?php endif; ?>
                    <?php if ($comm['created_by_name']): ?>
                    <small class="text-muted">oleh <?= e($comm['created_by_name']) ?></small>
                    <?php endif; ?>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Stats Sidebar -->
    <div class="col-6">
        <div class="glass-card mb-4">
            <h4 class="card-title mb-4">
                <i class="fas fa-chart-bar text-primary"></i>
                Statistik
            </h4>
            
            <div class="stats-grid">
                <div class="stat-item">
                    <div class="stat-value"><?= number_format($stats['total_orders']) ?></div>
                    <div class="stat-label">Total Pesanan</div>
                </div>
                <div class="stat-item">
                    <div class="stat-value"><?= formatRupiah($stats['total_revenue']) ?></div>
                    <div class="stat-label">Total Transaksi</div>
                </div>
            </div>
        </div>
        
        <!-- Quick Actions -->
        <div class="glass-card">
            <h4 class="card-title mb-4">
                <i class="fas fa-bolt text-primary"></i>
                Aksi Cepat
            </h4>
            
            <div class="d-flex flex-column gap-2">
                <?php if ($client->phone): ?>
                <a href="https://wa.me/<?= e($client->getWhatsAppPhone()) ?>" target="_blank" 
                   class="btn btn-success">
                    <i class="fab fa-whatsapp"></i> Chat WhatsApp
                </a>
                <?php endif; ?>
                
                <?php if ($client->email): ?>
                <a href="mailto:<?= e($client->email) ?>" class="btn btn-primary">
                    <i class="fas fa-envelope"></i> Kirim Email
                </a>
                <?php endif; ?>
                
                <?php if (Session::can('orders.create')): ?>
                <a href="<?= url('/orders/create?client_id=' . $client->id) ?>" class="btn btn-secondary">
                    <i class="fas fa-plus"></i> Buat Pesanan Baru
                </a>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<style>
.info-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 1rem;
}

.info-item {
    padding: 0.75rem;
    background: var(--gray-50);
    border-radius: var(--border-radius-sm);
}

.info-item.full-width {
    grid-column: 1 / -1;
}

.info-item label {
    display: block;
    font-size: 0.8rem;
    color: var(--gray-500);
    margin-bottom: 0.25rem;
}

.info-item label i {
    width: 16px;
}

.stats-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 1rem;
}

.stat-item {
    text-align: center;
    padding: 1.5rem;
    background: var(--gray-50);
    border-radius: var(--border-radius-sm);
}

.stat-value {
    font-size: 1.5rem;
    font-weight: 700;
    color: var(--primary-600);
    margin-bottom: 0.25rem;
}

.stat-label {
    font-size: 0.85rem;
    color: var(--gray-500);
}
</style>
