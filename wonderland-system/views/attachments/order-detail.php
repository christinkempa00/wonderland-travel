<?php
/**
 * Attachment Order Detail View
 * Detail pesanan untuk Staff Lampiran (tanpa informasi harga)
 */
?>

<style>
.attachment-section {
    background: #f8f9fa;
    border-radius: 12px;
    padding: 1.25rem;
    margin-bottom: 1rem;
}
.attachment-section h5 {
    margin-bottom: 1rem;
    display: flex;
    align-items: center;
    gap: 0.5rem;
}
.attachment-item {
    background: white;
    border: 1px solid #e5e7eb;
    border-radius: 8px;
    padding: 0.75rem 1rem;
    margin-bottom: 0.5rem;
}
.attachment-item:last-child {
    margin-bottom: 0;
}
.attachment-buttons {
    display: flex;
    flex-wrap: wrap;
    gap: 0.5rem;
    margin-top: 1rem;
}
.attachment-buttons .btn {
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
}
.item-badge {
    font-size: 0.75rem;
    padding: 0.25rem 0.5rem;
}
</style>

<div class="container-fluid py-4">
    <!-- Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <a href="<?= url('/attachment-dashboard') ?>" class="text-muted text-decoration-none mb-2 d-inline-block">
                <i class="fas fa-arrow-left"></i> Kembali ke Dashboard
            </a>
            <h2 class="mb-0">
                <i class="fas fa-file-alt text-primary"></i>
                <?= htmlspecialchars($order['order_number'] ?? 'Order') ?>
            </h2>
        </div>
    </div>
    
    <!-- Order Info -->
    <div class="card mb-4">
        <div class="card-body">
            <div class="row">
                <div class="col-md-6">
                    <p class="mb-2"><strong>No. Pesanan:</strong> <?= htmlspecialchars($order['order_number'] ?? '-') ?></p>
                    <p class="mb-2"><strong>Klien:</strong> <?= htmlspecialchars($order['client_name'] ?? '-') ?></p>
                    <p class="mb-0"><strong>Event:</strong> <?= htmlspecialchars($order['event_name'] ?? '-') ?></p>
                </div>
                <div class="col-md-6">
                    <p class="mb-2"><strong>Tanggal:</strong> 
                        <?php if (!empty($order['event_date'])): ?>
                            <?= date('d/m/Y', strtotime($order['event_date'])) ?>
                            <?php if (!empty($order['event_end_date']) && $order['event_end_date'] != $order['event_date']): ?>
                                - <?= date('d/m/Y', strtotime($order['event_end_date'])) ?>
                            <?php endif; ?>
                        <?php else: ?>
                            -
                        <?php endif; ?>
                    </p>
                    <p class="mb-0"><strong>Status:</strong> 
                        <span class="badge bg-<?= $order['status'] == 'paid' || $order['status'] == 'completed' ? 'success' : ($order['status'] == 'cancelled' ? 'danger' : 'warning') ?>">
                            <?= ucfirst($order['status'] ?? '-') ?>
                        </span>
                    </p>
                </div>
            </div>
        </div>
    </div>
    
    <h4 class="mb-3"><i class="fas fa-paperclip"></i> Kelola Lampiran</h4>
    
    <div class="row">
        <!-- Hotel -->
        <?php if (!empty($groupedItems['hotel'])): ?>
        <div class="col-md-6 mb-3">
            <div class="attachment-section">
                <h5><i class="fas fa-hotel text-primary"></i> Hotel</h5>
                
                <?php foreach ($groupedItems['hotel'] as $item): ?>
                <div class="attachment-item">
                    <strong><?= htmlspecialchars($item['hotel_name'] ?? $item['description'] ?? 'Hotel') ?></strong>
                    <div class="text-muted small">
                        <?= (int)($item['quantity'] ?? 1) ?> kamar
                        <?php if (!empty($item['num_days'])): ?>
                            × <?= (int)$item['num_days'] ?> malam
                        <?php endif; ?>
                    </div>
                </div>
                <?php endforeach; ?>
                
                <div class="attachment-buttons">
                    <a href="<?= url('/attachment-order/' . $order['id'] . '/hotel-guests') ?>" class="btn btn-primary btn-sm">
                        <i class="fas fa-users"></i> Data Tamu Hotel
                    </a>
                </div>
            </div>
        </div>
        <?php endif; ?>
        
        <!-- Flight -->
        <?php if (!empty($groupedItems['flight'])): ?>
        <div class="col-md-6 mb-3">
            <div class="attachment-section">
                <h5><i class="fas fa-plane text-info"></i> Pesawat</h5>
                
                <?php foreach ($groupedItems['flight'] as $item): ?>
                <div class="attachment-item">
                    <strong><?= htmlspecialchars($item['description'] ?? 'Tiket Pesawat') ?></strong>
                    <div class="text-muted small"><?= (int)($item['quantity'] ?? 1) ?> tiket</div>
                </div>
                <?php endforeach; ?>
                
                <div class="attachment-buttons">
                    <a href="<?= url('/attachment-order/' . $order['id'] . '/flight-guests') ?>" class="btn btn-info btn-sm">
                        <i class="fas fa-users"></i> Data Penumpang
                    </a>
                </div>
            </div>
        </div>
        <?php endif; ?>
        
        <!-- Vehicle (Bus/Towing) - 2 Lampiran -->
        <?php if (!empty($groupedItems['vehicle'])): ?>
        <div class="col-md-6 mb-3">
            <div class="attachment-section">
                <h5><i class="fas fa-bus text-success"></i> Kendaraan (Bus/Towing)</h5>
                
                <?php foreach ($groupedItems['vehicle'] as $item): ?>
                <div class="attachment-item">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <strong><?= htmlspecialchars($item['description'] ?? 'Kendaraan') ?></strong>
                            <div class="text-muted small">
                                <?= (int)($item['quantity'] ?? 1) ?> unit
                                <?php if (!empty($item['num_days'])): ?>
                                    × <?= (int)$item['num_days'] ?> hari
                                <?php endif; ?>
                            </div>
                        </div>
                        <span class="badge bg-secondary item-badge"><?= ucfirst($item['item_type'] ?? '') ?></span>
                    </div>
                </div>
                <?php endforeach; ?>
                
                <div class="attachment-buttons">
                    <a href="<?= url('/attachment-order/' . $order['id'] . '/vehicle-documents') ?>" class="btn btn-success btn-sm">
                        <i class="fas fa-id-card"></i> Data Kendaraan & Driver
                    </a>
                    <a href="<?= url('/attachment-order/' . $order['id'] . '/rental-vehicles') ?>" class="btn btn-outline-success btn-sm">
                        <i class="fas fa-calendar-alt"></i> Data Harga & Jadwal
                    </a>
                </div>
            </div>
        </div>
        <?php endif; ?>
        
        <!-- Rental - 2 Lampiran -->
        <?php if (!empty($groupedItems['rental'])): ?>
        <div class="col-md-6 mb-3">
            <div class="attachment-section">
                <h5><i class="fas fa-car text-warning"></i> Rental Kendaraan</h5>
                
                <?php foreach ($groupedItems['rental'] as $item): ?>
                <div class="attachment-item">
                    <strong><?= htmlspecialchars($item['description'] ?? 'Rental') ?></strong>
                    <div class="text-muted small">
                        <?= (int)($item['quantity'] ?? 1) ?> unit
                        <?php if (!empty($item['num_days'])): ?>
                            × <?= (int)$item['num_days'] ?> hari
                        <?php endif; ?>
                    </div>
                </div>
                <?php endforeach; ?>
                
                <div class="attachment-buttons">
                    <a href="<?= url('/attachment-order/' . $order['id'] . '/vehicle-documents') ?>" class="btn btn-warning btn-sm">
                        <i class="fas fa-id-card"></i> Data Kendaraan & Driver
                    </a>
                    <a href="<?= url('/attachment-order/' . $order['id'] . '/rental-vehicles') ?>" class="btn btn-outline-warning btn-sm">
                        <i class="fas fa-calendar-alt"></i> Data Harga & Jadwal
                    </a>
                </div>
            </div>
        </div>
        <?php endif; ?>
    </div>
    
    <?php if (empty($groupedItems['hotel']) && empty($groupedItems['flight']) && empty($groupedItems['vehicle']) && empty($groupedItems['rental'])): ?>
    <div class="alert alert-info">
        <i class="fas fa-info-circle"></i> Pesanan ini tidak memiliki item yang memerlukan lampiran.
    </div>
    <?php endif; ?>
</div>