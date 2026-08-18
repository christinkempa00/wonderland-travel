<?php
/**
 * Rental Vehicles Form View
 * Input jenis kendaraan, tanggal pemakaian, harga per hari
 */

// Helper for currency format
if (!function_exists('formatRupiahLocal')) {
    function formatRupiahLocal($number) {
        return 'Rp ' . number_format($number, 0, ',', '.');
    }
}

// Group existing details by rental item
$vehiclesByRental = [];
foreach ($rentalItems as $rental) {
    $rentalId = $rental['id'];
    $rentalDetails = $detailsByRental[$rentalId] ?? [];
    $vehiclesByRental[$rentalId] = $rentalDetails;
}
?>

<style>
/* ========================================
   LOGO UPLOAD STYLES
   ======================================== */
.rental-logo-section {
    background: #f8fafc;
    border: 1px dashed #e5e7eb;
    border-radius: 8px;
    padding: 10px 12px;
    margin-bottom: 1rem;
    display: flex;
    align-items: center;
    gap: 12px;
}

.logo-section-label {
    font-size: 0.8rem;
    color: #6b7280;
    white-space: nowrap;
}

.logo-section-label i {
    margin-right: 4px;
}

.rental-logo-upload {
    flex: 1;
    display: flex;
    align-items: center;
}

.rental-logo-preview {
    display: flex;
    align-items: center;
    gap: 10px;
}

.rental-logo-preview img {
    max-height: 40px;
    max-width: 120px;
    object-fit: contain;
    border: 1px solid #e5e7eb;
    border-radius: 4px;
    padding: 4px;
    background: white;
}

.rental-logo-actions {
    display: flex;
    gap: 4px;
}

.rental-logo-empty {
    display: flex;
    align-items: center;
    gap: 8px;
    color: #9ca3af;
    font-size: 0.8rem;
}

.rental-logo-empty i {
    font-size: 1.2rem;
}

.btn-xs {
    padding: 2px 8px;
    font-size: 0.7rem;
}

.upload-progress-mini {
    flex: 1;
    max-width: 150px;
}

/* ========================================
   FORM STYLES
   ======================================== */
.rental-form-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    margin-bottom: 1rem;
    padding-bottom: 1rem;
    border-bottom: 1px solid #e5e7eb;
}

.rental-form-header .rental-info h4 {
    margin: 0 0 0.5rem 0;
    font-size: 1.1rem;
    color: #1f2937;
}

.rental-form-header .rental-meta {
    display: flex;
    gap: 0.5rem;
}

.vehicle-list-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 1rem;
    padding-bottom: 0.75rem;
    border-bottom: 1px solid #e5e7eb;
}

.vehicle-list-header h5 {
    margin: 0;
    font-size: 0.95rem;
    color: #374151;
}

.vehicle-items {
    display: flex;
    flex-direction: column;
    gap: 1rem;
}

.vehicle-row {
    background: white;
    border: 1px solid #e5e7eb;
    border-radius: 12px;
    padding: 1rem;
    transition: all 0.2s;
}

.vehicle-row:hover {
    border-color: #dc2626;
    box-shadow: 0 2px 8px rgba(220, 38, 38, 0.1);
}

.vehicle-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 0.75rem;
    padding-bottom: 0.5rem;
    border-bottom: 1px dashed #e5e7eb;
}

.vehicle-num {
    font-weight: 600;
    color: #dc2626;
    font-size: 0.9rem;
}

.vehicle-fields {
    flex: 1;
}

.form-label-sm {
    font-size: 0.75rem;
    color: #6b7280;
    margin-bottom: 0.25rem;
    display: block;
    font-weight: 500;
}

.empty-state {
    text-align: center;
    padding: 2rem;
    color: #9ca3af;
}

.empty-state i {
    font-size: 2rem;
    margin-bottom: 0.5rem;
    opacity: 0.5;
}

.form-actions {
    padding: 1rem 0;
}

/* Daily prices section */
.daily-prices-section {
    margin-top: 1rem;
    padding: 1rem;
    background: #fef2f2;
    border-radius: 8px;
    border: 1px solid #fecaca;
}

.daily-prices-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 0.75rem;
}

.daily-prices-header h6 {
    margin: 0;
    font-size: 0.85rem;
    color: #991b1b;
}

.daily-prices-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(140px, 1fr));
    gap: 0.75rem;
}

.daily-price-item {
    background: white;
    padding: 0.5rem;
    border-radius: 6px;
    border: 1px solid #fecaca;
}

.daily-price-item label {
    display: block;
    font-size: 0.7rem;
    color: #6b7280;
    margin-bottom: 0.25rem;
}

.daily-price-item input {
    width: 100%;
    padding: 0.4rem;
    font-size: 0.85rem;
    border: 1px solid #e5e7eb;
    border-radius: 4px;
}

/* Checkbox styling */
.price-toggle {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    margin-top: 0.75rem;
    padding: 0.75rem;
    background: #f3f4f6;
    border-radius: 8px;
}

.price-toggle input[type="checkbox"] {
    width: 18px;
    height: 18px;
    cursor: pointer;
}

.price-toggle label {
    font-size: 0.85rem;
    color: #374151;
    cursor: pointer;
}

/* ========================================
   PREVIEW STYLES
   ======================================== */
.preview-card {
    position: sticky;
    top: 1rem;
}

.preview-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 1rem;
    padding-bottom: 0.75rem;
    border-bottom: 1px solid #e5e7eb;
}

.preview-header h5 {
    margin: 0;
    color: #1f2937;
}

.preview-empty {
    text-align: center;
    padding: 3rem 1rem;
    color: #9ca3af;
    background: #f9fafb;
    border-radius: 8px;
}

.preview-empty i {
    font-size: 3rem;
    margin-bottom: 1rem;
    opacity: 0.5;
}

/* Landscape preview */
.landscape-preview-container {
    background: #e5e7eb;
    padding: 10px;
    border-radius: 8px;
    overflow-x: auto;
}

.landscape-preview {
    background: white;
    width: 297mm;
    min-width: 297mm;
    height: 140mm;
    padding: 15px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
    font-size: 7px;
    transform: scale(0.38);
    transform-origin: top left;
    margin-bottom: -200px;
}

.landscape-preview .preview-table {
    width: 100%;
    border-collapse: collapse;
    font-size: 7px;
}

.landscape-preview .preview-table th {
    background: #1f2937;
    color: white;
    padding: 6px 4px;
    text-align: center;
    font-size: 6px;
    font-weight: 600;
}

.landscape-preview .preview-table td {
    padding: 5px 4px;
    border: 1px solid #e5e7eb;
    text-align: center;
    font-size: 6px;
}

.landscape-preview .preview-table .vehicle-cell {
    text-align: left;
    font-weight: 500;
}

/* Info & Actions */
.info-list {
    display: flex;
    flex-direction: column;
    gap: 0.75rem;
}

.info-list .info-item {
    display: flex;
    flex-direction: column;
    gap: 0.2rem;
}

.info-list .info-item label {
    font-size: 0.7rem;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    color: #6b7280;
    font-weight: 600;
}

.info-list .info-item span {
    font-size: 0.9rem;
    color: #1f2937;
}

.quick-actions {
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
}

.quick-action-btn {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    padding: 0.75rem 1rem;
    background: #f9fafb;
    border-radius: 8px;
    text-decoration: none;
    color: #374151;
    transition: all 0.2s;
    border: 1px solid #e5e7eb;
}

.quick-action-btn:hover {
    background: #f3f4f6;
    color: #dc2626;
    border-color: #dc2626;
}

.quick-action-btn i {
    font-size: 1rem;
    width: 24px;
    text-align: center;
}

@media (max-width: 991.98px) {
    .preview-card {
        position: relative;
        top: 0;
    }
}
</style>

<div class="page-actions mb-4">
    <a href="<?= url('/attachment-order/' . $order['id']) ?>" class="btn btn-secondary">
        <i class="fas fa-arrow-left"></i> Kembali ke Detail Order
    </a>
    <?php if (!empty($existingDetails)): ?>
    <a href="<?= url('/attachment-order/' . $order['id'] . '/rental-attachment') ?>" target="_blank" class="btn btn-success">
        <i class="fas fa-print"></i> Cetak Lampiran
    </a>
    <?php endif; ?>
</div>

<div class="row">
    <div class="col-12 col-lg-7">
        <form method="POST" action="<?= url('/attachment-order/' . $order['id'] . '/rental-vehicles') ?>" id="rentalVehiclesForm">
            <?= csrfField() ?>
            
            <?php foreach ($rentalItems as $idx => $rental): ?>
            <div class="glass-card mb-4">
                <div class="rental-form-header">
                    <div class="rental-info">
                        <h4>
                            <i class="fas fa-car text-danger"></i>
                            <?= e($rental['description'] ?: 'Rental Kendaraan') ?>
                        </h4>
                        <div class="rental-meta">
                            <span class="badge badge-danger"><?= e($rental['quantity']) ?> Unit</span>
                        </div>
                    </div>
                </div>
                
                <!-- Logo Upload -->
                <div class="rental-logo-section">
                    <div class="logo-section-label">
                        <i class="fas fa-image"></i> Logo Lampiran
                    </div>
                    <div class="rental-logo-upload" id="logoUpload<?= $rental['id'] ?>">
                        <?php 
                        $rentalLogo = $rental['attachment_logo'] ?? '';
                        if ($rentalLogo && file_exists(BASE_PATH . '/uploads/logos/' . $rentalLogo)): 
                        ?>
                        <div class="rental-logo-preview" id="logoPreview<?= $rental['id'] ?>">
                            <img src="<?= url('/uploads/logos/' . $rentalLogo) ?>" alt="Logo">
                            <div class="rental-logo-actions">
                                <label class="btn btn-xs btn-outline-primary" for="logoInput<?= $rental['id'] ?>">
                                    <i class="fas fa-edit"></i> Ganti
                                </label>
                                <button type="button" class="btn btn-xs btn-outline-danger" onclick="deleteRentalLogo(<?= $rental['id'] ?>)">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </div>
                        </div>
                        <?php else: ?>
                        <div class="rental-logo-empty" id="logoEmpty<?= $rental['id'] ?>">
                            <i class="fas fa-cloud-upload-alt"></i>
                            <span>Upload logo</span>
                            <label class="btn btn-xs btn-primary" for="logoInput<?= $rental['id'] ?>">
                                <i class="fas fa-upload"></i> Pilih
                            </label>
                        </div>
                        <?php endif; ?>
                        <input type="file" id="logoInput<?= $rental['id'] ?>" 
                               accept="image/png,image/jpeg,image/gif" 
                               style="display:none" 
                               onchange="uploadRentalLogo(<?= $rental['id'] ?>, this)">
                        <div class="upload-progress-mini" id="uploadProgress<?= $rental['id'] ?>" style="display:none">
                            <div class="progress" style="height: 4px;">
                                <div class="progress-bar progress-bar-striped progress-bar-animated" style="width: 100%"></div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Vehicle List -->
                <div class="vehicle-list" id="vehicleList<?= $rental['id'] ?>">
                    <div class="vehicle-list-header">
                        <h5><i class="fas fa-car"></i> Daftar Kendaraan</h5>
                        <button type="button" class="btn btn-sm btn-danger" 
                                onclick="addVehicle(<?= $rental['id'] ?>)">
                            <i class="fas fa-plus"></i> Tambah Kendaraan
                        </button>
                    </div>
                    
                    <div class="vehicle-items" id="vehicleItems<?= $rental['id'] ?>">
                        <?php 
                        $rentalVehicles = $vehiclesByRental[$rental['id']] ?? [];
                        if (empty($rentalVehicles)): 
                        ?>
                        <div class="empty-state" id="emptyState<?= $rental['id'] ?>">
                            <i class="fas fa-car"></i>
                            <p>Belum ada data kendaraan. Klik "Tambah Kendaraan" untuk mulai.</p>
                        </div>
                        <?php else: ?>
                            <?php foreach ($rentalVehicles as $vIdx => $vehicle): 
                                $dailyPrices = json_decode($vehicle['daily_prices'] ?? '{}', true) ?: [];
                                $startDate = $vehicle['start_date'];
                                $endDate = $vehicle['end_date'];
                            ?>
                            <div class="vehicle-row" id="vehicle-<?= $rental['id'] ?>-<?= $vIdx ?>">
                                <input type="hidden" name="vehicles[<?= $rental['id'] ?>][<?= $vIdx ?>][order_item_id]" value="<?= $rental['id'] ?>">
                                
                                <div class="vehicle-header">
                                    <div class="vehicle-num">Kendaraan <?= $vIdx + 1 ?></div>
                                    <button type="button" class="btn btn-sm btn-outline-danger" 
                                            onclick="removeVehicle('vehicle-<?= $rental['id'] ?>-<?= $vIdx ?>', <?= $rental['id'] ?>)">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </div>
                                
                                <div class="vehicle-fields">
                                    <div class="row g-2">
                                        <div class="col-md-4">
                                            <label class="form-label-sm">Jenis Kendaraan <span class="text-danger">*</span></label>
                                            <input type="text" class="form-control" 
                                                   name="vehicles[<?= $rental['id'] ?>][<?= $vIdx ?>][vehicle_type]" 
                                                   value="<?= e($vehicle['vehicle_type']) ?>"
                                                   placeholder="Avanza, Innova, Bus, dll" required>
                                        </div>
                                        <div class="col-md-4">
                                            <label class="form-label-sm">Tanggal Mulai <span class="text-danger">*</span></label>
                                            <input type="date" class="form-control start-date" 
                                                   name="vehicles[<?= $rental['id'] ?>][<?= $vIdx ?>][start_date]" 
                                                   value="<?= e($startDate) ?>"
                                                   data-rental="<?= $rental['id'] ?>"
                                                   data-vehicle="<?= $vIdx ?>"
                                                   onchange="updateDailyPrices(<?= $rental['id'] ?>, <?= $vIdx ?>)" required>
                                        </div>
                                        <div class="col-md-4">
                                            <label class="form-label-sm">Tanggal Selesai <span class="text-danger">*</span></label>
                                            <input type="date" class="form-control end-date" 
                                                   name="vehicles[<?= $rental['id'] ?>][<?= $vIdx ?>][end_date]" 
                                                   value="<?= e($endDate) ?>"
                                                   data-rental="<?= $rental['id'] ?>"
                                                   data-vehicle="<?= $vIdx ?>"
                                                   onchange="updateDailyPrices(<?= $rental['id'] ?>, <?= $vIdx ?>)" required>
                                        </div>
                                    </div>
                                    
                                    <div class="price-toggle">
                                        <input type="checkbox" 
                                               id="samePrice<?= $rental['id'] ?>_<?= $vIdx ?>" 
                                               name="vehicles[<?= $rental['id'] ?>][<?= $vIdx ?>][same_price]"
                                               <?= $vehicle['same_price'] ? 'checked' : '' ?>
                                               onchange="togglePriceMode(<?= $rental['id'] ?>, <?= $vIdx ?>)">
                                        <label for="samePrice<?= $rental['id'] ?>_<?= $vIdx ?>">
                                            Harga sama setiap hari
                                        </label>
                                    </div>
                                    
                                    <!-- Same price input -->
                                    <div class="row g-2 mt-2 same-price-section" id="samePriceSection<?= $rental['id'] ?>_<?= $vIdx ?>" 
                                         style="<?= $vehicle['same_price'] ? '' : 'display:none' ?>">
                                        <div class="col-md-6">
                                            <label class="form-label-sm">Harga Per Hari</label>
                                            <input type="text" class="form-control price-input" 
                                                   name="vehicles[<?= $rental['id'] ?>][<?= $vIdx ?>][price_per_day]" 
                                                   value="<?= number_format($vehicle['price_per_day'], 0, ',', '.') ?>"
                                                   placeholder="500.000">
                                        </div>
                                    </div>
                                    
                                    <!-- Different prices section -->
                                    <div class="daily-prices-section" id="dailyPricesSection<?= $rental['id'] ?>_<?= $vIdx ?>"
                                         style="<?= $vehicle['same_price'] ? 'display:none' : '' ?>">
                                        <div class="daily-prices-header">
                                            <h6><i class="fas fa-calendar-day"></i> Harga Per Tanggal</h6>
                                        </div>
                                        <div class="daily-prices-grid" id="dailyPricesGrid<?= $rental['id'] ?>_<?= $vIdx ?>">
                                            <?php
                                            if ($startDate && $endDate) {
                                                $current = new DateTime($startDate);
                                                $end = new DateTime($endDate);
                                                while ($current <= $end) {
                                                    $dateStr = $current->format('Y-m-d');
                                                    $dateLabel = $current->format('d M');
                                                    $price = $dailyPrices[$dateStr] ?? 0;
                                            ?>
                                            <div class="daily-price-item">
                                                <label><?= $dateLabel ?></label>
                                                <input type="text" class="price-input"
                                                       name="vehicles[<?= $rental['id'] ?>][<?= $vIdx ?>][daily_prices][<?= $dateStr ?>]"
                                                       value="<?= $price > 0 ? number_format($price, 0, ',', '.') : '' ?>"
                                                       placeholder="0">
                                            </div>
                                            <?php
                                                    $current->modify('+1 day');
                                                }
                                            }
                                            ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
            
            <div class="form-actions">
                <button type="submit" class="btn btn-danger btn-lg">
                    <i class="fas fa-save"></i> Simpan Data Kendaraan
                </button>
            </div>
        </form>
    </div>
    
    <div class="col-12 col-lg-5">
        <!-- Preview Section -->
        <div class="glass-card mb-4 preview-card">
            <div class="preview-header">
                <h5><i class="fas fa-eye text-danger"></i> Preview Lampiran (Landscape)</h5>
                <?php if (!empty($existingDetails)): ?>
                <a href="<?= url('/attachment-order/' . $order['id'] . '/rental-attachment') ?>" target="_blank" class="btn btn-sm btn-outline-danger">
                    <i class="fas fa-external-link-alt"></i> Fullscreen
                </a>
                <?php endif; ?>
            </div>
            
            <?php if (empty($existingDetails)): ?>
            <div class="preview-empty">
                <i class="fas fa-file-alt"></i>
                <p>Preview akan muncul setelah data kendaraan disimpan</p>
            </div>
            <?php else: ?>
            <div class="landscape-preview-container">
                <div class="landscape-preview">
                    <p style="text-align:center; font-size:8px; color:#666; margin-bottom:10px;">
                        📄 Format: Landscape A4 - Tabel Horizontal
                    </p>
                    
                    <?php foreach ($rentalItems as $rental): 
                        $rentalVehicles = $vehiclesByRental[$rental['id']] ?? [];
                        if (empty($rentalVehicles)) continue;
                        
                        // Collect all dates
                        $allDates = [];
                        foreach ($rentalVehicles as $v) {
                            if ($v['start_date'] && $v['end_date']) {
                                $current = new DateTime($v['start_date']);
                                $end = new DateTime($v['end_date']);
                                while ($current <= $end) {
                                    $allDates[$current->format('Y-m-d')] = $current->format('d/m');
                                    $current->modify('+1 day');
                                }
                            }
                        }
                        ksort($allDates);
                    ?>
                    <table class="preview-table">
                        <thead>
                            <tr>
                                <th style="width:30px">No</th>
                                <th style="width:100px">Jenis Kendaraan</th>
                                <?php foreach ($allDates as $date => $label): ?>
                                <th><?= $label ?></th>
                                <?php endforeach; ?>
                                <th style="width:60px">Jumlah</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            $no = 1;
                            foreach ($rentalVehicles as $v): 
                                $dailyPrices = json_decode($v['daily_prices'] ?? '{}', true) ?: [];
                                $total = 0;
                            ?>
                            <tr>
                                <td><?= $no++ ?></td>
                                <td class="vehicle-cell"><?= e($v['vehicle_type']) ?></td>
                                <?php foreach ($allDates as $date => $label): 
                                    $price = 0;
                                    if ($v['start_date'] <= $date && $date <= $v['end_date']) {
                                        $price = $v['same_price'] ? $v['price_per_day'] : ($dailyPrices[$date] ?? 0);
                                        $total += $price;
                                    }
                                ?>
                                <td><?= $price > 0 ? number_format($price/1000) . 'K' : '-' ?></td>
                                <?php endforeach; ?>
                                <td style="font-weight:600"><?= number_format($total/1000) ?>K</td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>
        </div>
        
        <!-- Order Info -->
        <div class="glass-card mb-4">
            <h5><i class="fas fa-info-circle text-danger"></i> Info Pesanan</h5>
            <div class="info-list">
                <div class="info-item">
                    <label>No. Pesanan</label>
                    <span><?= e($order['order_number']) ?></span>
                </div>
                <div class="info-item">
                    <label>Klien</label>
                    <span><?= e($order['client_name'] ?? '-') ?></span>
                </div>
                <div class="info-item">
                    <label>Total Item Rental</label>
                    <span><?= count($rentalItems) ?> item</span>
                </div>
            </div>
        </div>
        
        <!-- Quick Actions -->
        <div class="glass-card">
            <h5><i class="fas fa-bolt text-warning"></i> Aksi Cepat</h5>
            <div class="quick-actions">
                <?php if (!empty($existingDetails)): ?>
                <a href="<?= url('/attachment-order/' . $order['id'] . '/rental-attachment') ?>" target="_blank" class="quick-action-btn">
                    <i class="fas fa-print"></i>
                    <span>Cetak Lampiran</span>
                </a>
                <?php endif; ?>
                <a href="<?= url('/attachment-order/' . $order['id']) ?>" class="quick-action-btn">
                    <i class="fas fa-arrow-left"></i>
                    <span>Kembali ke Detail</span>
                </a>
                <a href="<?= url('/attachment-dashboard') ?>" class="quick-action-btn">
                    <i class="fas fa-home"></i>
                    <span>Dashboard Lampiran</span>
                </a>
            </div>
        </div>
    </div>
</div>

<!-- Vehicle Row Template -->
<template id="vehicleRowTemplate">
    <div class="vehicle-row" id="vehicle-{rentalId}-{index}">
        <input type="hidden" name="vehicles[{rentalId}][{index}][order_item_id]" value="{rentalId}">
        
        <div class="vehicle-header">
            <div class="vehicle-num">Kendaraan {num}</div>
            <button type="button" class="btn btn-sm btn-outline-danger" onclick="removeVehicle('vehicle-{rentalId}-{index}', {rentalId})">
                <i class="fas fa-trash"></i>
            </button>
        </div>
        
        <div class="vehicle-fields">
            <div class="row g-2">
                <div class="col-md-4">
                    <label class="form-label-sm">Jenis Kendaraan <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" 
                           name="vehicles[{rentalId}][{index}][vehicle_type]" 
                           placeholder="Avanza, Innova, Bus, dll" required>
                </div>
                <div class="col-md-4">
                    <label class="form-label-sm">Tanggal Mulai <span class="text-danger">*</span></label>
                    <input type="date" class="form-control start-date" 
                           name="vehicles[{rentalId}][{index}][start_date]" 
                           data-rental="{rentalId}"
                           data-vehicle="{index}"
                           onchange="updateDailyPrices({rentalId}, {index})" required>
                </div>
                <div class="col-md-4">
                    <label class="form-label-sm">Tanggal Selesai <span class="text-danger">*</span></label>
                    <input type="date" class="form-control end-date" 
                           name="vehicles[{rentalId}][{index}][end_date]" 
                           data-rental="{rentalId}"
                           data-vehicle="{index}"
                           onchange="updateDailyPrices({rentalId}, {index})" required>
                </div>
            </div>
            
            <div class="price-toggle">
                <input type="checkbox" 
                       id="samePrice{rentalId}_{index}" 
                       name="vehicles[{rentalId}][{index}][same_price]"
                       checked
                       onchange="togglePriceMode({rentalId}, {index})">
                <label for="samePrice{rentalId}_{index}">
                    Harga sama setiap hari
                </label>
            </div>
            
            <!-- Same price input -->
            <div class="row g-2 mt-2 same-price-section" id="samePriceSection{rentalId}_{index}">
                <div class="col-md-6">
                    <label class="form-label-sm">Harga Per Hari</label>
                    <input type="text" class="form-control price-input" 
                           name="vehicles[{rentalId}][{index}][price_per_day]" 
                           placeholder="500.000">
                </div>
            </div>
            
            <!-- Different prices section -->
            <div class="daily-prices-section" id="dailyPricesSection{rentalId}_{index}" style="display:none">
                <div class="daily-prices-header">
                    <h6><i class="fas fa-calendar-day"></i> Harga Per Tanggal</h6>
                </div>
                <div class="daily-prices-grid" id="dailyPricesGrid{rentalId}_{index}">
                    <p style="color:#999; font-size:0.8rem;">Pilih tanggal terlebih dahulu</p>
                </div>
            </div>
        </div>
    </div>
</template>

<script>
let vehicleCounters = {};

// Initialize counters
<?php foreach ($rentalItems as $rental): ?>
vehicleCounters[<?= $rental['id'] ?>] = <?= count($vehiclesByRental[$rental['id']] ?? []) ?>;
<?php endforeach; ?>

function addVehicle(rentalId) {
    const container = document.getElementById('vehicleItems' + rentalId);
    
    const emptyState = document.getElementById('emptyState' + rentalId);
    if (emptyState) {
        emptyState.remove();
    }
    
    const index = vehicleCounters[rentalId]++;
    const num = container.querySelectorAll('.vehicle-row').length + 1;
    
    const template = document.getElementById('vehicleRowTemplate').innerHTML;
    const html = template
        .replace(/{rentalId}/g, rentalId)
        .replace(/{index}/g, index)
        .replace(/{num}/g, num);
    
    container.insertAdjacentHTML('beforeend', html);
    
    const newRow = document.getElementById('vehicle-' + rentalId + '-' + index);
    if (newRow) {
        newRow.querySelector('input[name*="[vehicle_type]"]').focus();
    }
}

function removeVehicle(rowId, rentalId) {
    const row = document.getElementById(rowId);
    if (row) {
        row.remove();
        renumberVehicles(rentalId);
        
        const container = document.getElementById('vehicleItems' + rentalId);
        if (container.querySelectorAll('.vehicle-row').length === 0) {
            container.innerHTML = `
                <div class="empty-state" id="emptyState${rentalId}">
                    <i class="fas fa-car"></i>
                    <p>Belum ada data kendaraan. Klik "Tambah Kendaraan" untuk mulai.</p>
                </div>
            `;
        }
    }
}

function renumberVehicles(rentalId) {
    const container = document.getElementById('vehicleItems' + rentalId);
    if (container) {
        container.querySelectorAll('.vehicle-row').forEach((row, idx) => {
            row.querySelector('.vehicle-num').textContent = 'Kendaraan ' + (idx + 1);
        });
    }
}

function togglePriceMode(rentalId, vehicleIdx) {
    const checkbox = document.getElementById('samePrice' + rentalId + '_' + vehicleIdx);
    const samePriceSection = document.getElementById('samePriceSection' + rentalId + '_' + vehicleIdx);
    const dailyPricesSection = document.getElementById('dailyPricesSection' + rentalId + '_' + vehicleIdx);
    
    if (checkbox.checked) {
        samePriceSection.style.display = '';
        dailyPricesSection.style.display = 'none';
    } else {
        samePriceSection.style.display = 'none';
        dailyPricesSection.style.display = '';
        updateDailyPrices(rentalId, vehicleIdx);
    }
}

function updateDailyPrices(rentalId, vehicleIdx) {
    const row = document.getElementById('vehicle-' + rentalId + '-' + vehicleIdx);
    if (!row) return;
    
    const startDate = row.querySelector('.start-date').value;
    const endDate = row.querySelector('.end-date').value;
    const grid = document.getElementById('dailyPricesGrid' + rentalId + '_' + vehicleIdx);
    
    if (!startDate || !endDate || !grid) return;
    
    const start = new Date(startDate);
    const end = new Date(endDate);
    
    if (start > end) {
        grid.innerHTML = '<p style="color:#dc2626; font-size:0.8rem;">Tanggal selesai harus setelah tanggal mulai</p>';
        return;
    }
    
    let html = '';
    const current = new Date(start);
    
    while (current <= end) {
        const dateStr = current.toISOString().split('T')[0];
        const day = current.getDate();
        const month = current.toLocaleString('id-ID', { month: 'short' });
        
        html += `
            <div class="daily-price-item">
                <label>${day} ${month}</label>
                <input type="text" class="price-input"
                       name="vehicles[${rentalId}][${vehicleIdx}][daily_prices][${dateStr}]"
                       placeholder="0">
            </div>
        `;
        
        current.setDate(current.getDate() + 1);
    }
    
    grid.innerHTML = html;
}

// Form validation
document.getElementById('rentalVehiclesForm').addEventListener('submit', function(e) {
    let hasVehicle = false;
    document.querySelectorAll('input[name*="[vehicle_type]"]').forEach(input => {
        if (input.value.trim()) {
            hasVehicle = true;
        }
    });
    
    if (!hasVehicle) {
        e.preventDefault();
        alert('Minimal satu kendaraan harus diisi.');
        return false;
    }
});

// Logo upload functions
const orderId = <?= $order['id'] ?>;
const csrfToken = '<?= function_exists('csrf_token') ? csrf_token() : (isset($_SESSION['csrf_token']) ? $_SESSION['csrf_token'] : '') ?>';

function uploadRentalLogo(rentalItemId, input) {
    if (!input.files || !input.files[0]) return;
    
    const file = input.files[0];
    
    const allowedTypes = ['image/png', 'image/jpeg', 'image/gif'];
    if (!allowedTypes.includes(file.type)) {
        alert('Format file tidak didukung. Gunakan PNG, JPG, atau GIF.');
        return;
    }
    
    if (file.size > 2 * 1024 * 1024) {
        alert('Ukuran file maksimal 2MB.');
        return;
    }
    
    const progressEl = document.getElementById('uploadProgress' + rentalItemId);
    const emptyEl = document.getElementById('logoEmpty' + rentalItemId);
    const previewEl = document.getElementById('logoPreview' + rentalItemId);
    
    if (progressEl) progressEl.style.display = 'block';
    if (emptyEl) emptyEl.style.display = 'none';
    if (previewEl) previewEl.style.display = 'none';
    
    const formData = new FormData();
    formData.append('logo', file);
    formData.append('rental_item_id', rentalItemId);
    formData.append('csrf_token', csrfToken);
    
    fetch(`/attachment-order/${orderId}/upload-rental-logo`, {
        method: 'POST',
        body: formData,
        headers: {
            'X-CSRF-TOKEN': csrfToken
        }
    })
    .then(response => response.json())
    .then(data => {
        if (progressEl) progressEl.style.display = 'none';
        
        if (data.success) {
            updateRentalLogoUI(rentalItemId, data.data.url);
        } else {
            alert(data.message || 'Gagal mengupload logo.');
            resetRentalLogoUI(rentalItemId);
        }
    })
    .catch(error => {
        console.error('Upload error:', error);
        if (progressEl) progressEl.style.display = 'none';
        alert('Gagal mengupload logo.');
        resetRentalLogoUI(rentalItemId);
    });
}

function deleteRentalLogo(rentalItemId) {
    if (!confirm('Hapus logo ini?')) return;
    
    fetch(`/attachment-order/${orderId}/delete-rental-logo`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': csrfToken
        },
        body: JSON.stringify({ 
            rental_item_id: rentalItemId,
            csrf_token: csrfToken 
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            resetRentalLogoUI(rentalItemId);
        } else {
            alert(data.message || 'Gagal menghapus logo.');
        }
    })
    .catch(error => {
        console.error('Delete error:', error);
        alert('Gagal menghapus logo.');
    });
}

function updateRentalLogoUI(rentalItemId, url) {
    const uploadArea = document.getElementById('logoUpload' + rentalItemId);
    uploadArea.innerHTML = `
        <div class="rental-logo-preview" id="logoPreview${rentalItemId}">
            <img src="${url}" alt="Logo">
            <div class="rental-logo-actions">
                <label class="btn btn-xs btn-outline-primary" for="logoInput${rentalItemId}">
                    <i class="fas fa-edit"></i> Ganti
                </label>
                <button type="button" class="btn btn-xs btn-outline-danger" onclick="deleteRentalLogo(${rentalItemId})">
                    <i class="fas fa-trash"></i>
                </button>
            </div>
        </div>
        <input type="file" id="logoInput${rentalItemId}" 
               accept="image/png,image/jpeg,image/gif" 
               style="display:none" 
               onchange="uploadRentalLogo(${rentalItemId}, this)">
        <div class="upload-progress-mini" id="uploadProgress${rentalItemId}" style="display:none">
            <div class="progress" style="height: 4px;">
                <div class="progress-bar progress-bar-striped progress-bar-animated" style="width: 100%"></div>
            </div>
        </div>
    `;
}

function resetRentalLogoUI(rentalItemId) {
    const uploadArea = document.getElementById('logoUpload' + rentalItemId);
    uploadArea.innerHTML = `
        <div class="rental-logo-empty" id="logoEmpty${rentalItemId}">
            <i class="fas fa-cloud-upload-alt"></i>
            <span>Upload logo</span>
            <label class="btn btn-xs btn-primary" for="logoInput${rentalItemId}">
                <i class="fas fa-upload"></i> Pilih
            </label>
        </div>
        <input type="file" id="logoInput${rentalItemId}" 
               accept="image/png,image/jpeg,image/gif" 
               style="display:none" 
               onchange="uploadRentalLogo(${rentalItemId}, this)">
        <div class="upload-progress-mini" id="uploadProgress${rentalItemId}" style="display:none">
            <div class="progress" style="height: 4px;">
                <div class="progress-bar progress-bar-striped progress-bar-animated" style="width: 100%"></div>
            </div>
        </div>
    `;
}

// Format number with thousand separator (dot)
function formatThousand(num) {
    return num.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ".");
}

// Price input formatting - add thousand separator on blur
document.addEventListener('input', function(e) {
    if (e.target.classList.contains('price-input')) {
        // Only allow digits
        let value = e.target.value.replace(/[^\d]/g, '');
        e.target.value = value;
    }
});

document.addEventListener('blur', function(e) {
    if (e.target.classList.contains('price-input')) {
        let value = e.target.value.replace(/[^\d]/g, '');
        if (value) {
            e.target.value = formatThousand(value);
        }
    }
}, true);

document.addEventListener('focus', function(e) {
    if (e.target.classList.contains('price-input')) {
        // Remove thousand separator on focus for easier editing
        let value = e.target.value.replace(/\./g, '');
        e.target.value = value;
    }
}, true);
</script>