<?php
/**
 * Vehicle Documents Form View
 * Input data kendaraan dengan foto Driver, SIM, STNK
 */

// Group existing documents by item
$docsByItem = [];
foreach ($vehicleItems as $item) {
    $itemId = $item['id'];
    $docsByItem[$itemId] = $documentsByItem[$itemId] ?? [];
}
?>

<style>
/* ========================================
   VEHICLE DOCUMENTS FORM STYLES
   ======================================== */
.vehicle-card {
    background: white;
    border-radius: 12px;
    box-shadow: 0 2px 12px rgba(0,0,0,0.08);
    margin-bottom: 1.5rem;
    overflow: hidden;
}

.vehicle-card-header {
    background: linear-gradient(135deg, #1f2937 0%, #374151 100%);
    color: white;
    padding: 1rem 1.25rem;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.vehicle-card-header h4 {
    margin: 0;
    font-size: 1rem;
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.vehicle-card-header .badge {
    font-size: 0.7rem;
    padding: 4px 10px;
}

.vehicle-card-body {
    padding: 1.25rem;
}

.vehicle-item {
    background: #f8fafc;
    border: 1px solid #e5e7eb;
    border-radius: 10px;
    padding: 1rem;
    margin-bottom: 1rem;
}

.vehicle-item:last-child {
    margin-bottom: 0;
}

.vehicle-item-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 1rem;
    padding-bottom: 0.75rem;
    border-bottom: 1px dashed #e5e7eb;
}

.vehicle-item-num {
    font-weight: 600;
    color: #dc2626;
    font-size: 0.9rem;
}

.vehicle-fields {
    margin-bottom: 1rem;
}

.form-label-sm {
    font-size: 0.75rem;
    color: #6b7280;
    margin-bottom: 0.25rem;
    display: block;
    font-weight: 500;
}

/* Photo Upload Section */
.photo-upload-section {
    background: white;
    border: 1px solid #e5e7eb;
    border-radius: 8px;
    padding: 1rem;
}

.photo-upload-section h6 {
    font-size: 0.85rem;
    color: #374151;
    margin: 0 0 1rem 0;
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.photo-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 1rem;
}

.photo-item {
    background: #f9fafb;
    border: 2px dashed #d1d5db;
    border-radius: 8px;
    padding: 0.75rem;
    text-align: center;
    transition: all 0.2s;
}

.photo-item:hover {
    border-color: #c89b2c;
    background: #f0f9ff;
}

.photo-item.has-photo {
    border-style: solid;
    border-color: #10b981;
    background: #f0fdf4;
}

.photo-item.photo-stnk {
    grid-column: span 2;
}

.photo-item-label {
    font-size: 0.75rem;
    font-weight: 600;
    color: #6b7280;
    margin-bottom: 0.5rem;
    display: block;
}

.photo-preview {
    width: 100%;
    height: 120px;
    display: flex;
    align-items: center;
    justify-content: center;
    background: #f3f4f6;
    border-radius: 6px;
    overflow: hidden;
    margin-bottom: 0.5rem;
}

.photo-preview.stnk-preview {
    height: 80px;
}

.photo-preview img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.photo-preview .photo-placeholder {
    color: #9ca3af;
    font-size: 2rem;
}

.photo-actions {
    display: flex;
    gap: 0.5rem;
    justify-content: center;
}

.photo-upload-progress {
    display: none;
    padding: 0.5rem;
}

.empty-state {
    text-align: center;
    padding: 2rem;
    color: #9ca3af;
}

.empty-state i {
    font-size: 2.5rem;
    margin-bottom: 0.5rem;
    opacity: 0.5;
}

.empty-state p {
    margin: 0;
    font-size: 0.85rem;
}

/* Form Actions */
.form-actions {
    display: flex;
    gap: 1rem;
    justify-content: space-between;
    align-items: center;
    padding: 1rem 0;
    border-top: 1px solid #e5e7eb;
    margin-top: 1rem;
}

/* Preview Section */
.preview-card {
    background: #fafafa;
}

.preview-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 1rem;
}

.preview-empty {
    text-align: center;
    padding: 2rem;
    color: #9ca3af;
}

.preview-empty i {
    font-size: 2rem;
    margin-bottom: 0.5rem;
    opacity: 0.5;
}

/* Info List */
.info-list {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 1rem;
}

.info-item {
    display: flex;
    flex-direction: column;
}

.info-item label {
    font-size: 0.7rem;
    color: #9ca3af;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.info-item span {
    font-size: 0.9rem;
    color: #1f2937;
    font-weight: 500;
}

/* Responsive */
@media (max-width: 768px) {
    .photo-grid {
        grid-template-columns: 1fr;
    }
    
    .photo-item.photo-stnk {
        grid-column: span 1;
    }
}
</style>

<div class="container-fluid py-4">
    <div class="row">
        <div class="col-lg-8">
            <!-- Header -->
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h4 class="mb-1">
                        <i class="fas fa-id-card text-danger"></i> 
                        Data Kendaraan
                    </h4>
                    <p class="text-muted mb-0">
                        Pesanan #<?= e($order->order_number) ?>
                        <?php if ($client): ?>
                        - <?= e($client['name']) ?>
                        <?php endif; ?>
                    </p>
                </div>
                <a href="<?= url('/orders/' . $order->id) ?>" class="btn btn-outline-secondary">
                    <i class="fas fa-arrow-left"></i> Kembali
                </a>
            </div>
            
            <form method="POST" action="<?= url('/orders/' . $order->id . '/vehicle-documents') ?>" id="vehicleDocumentsForm">
                <?= csrfField() ?>
                
                <?php foreach ($vehicleItems as $item): 
                    $itemDocs = $docsByItem[$item['id']] ?? [];
                    $itemIcon = $item['item_type'] === 'bus' ? 'bus' : ($item['item_type'] === 'towing' ? 'truck-pickup' : 'car');
                ?>
                <div class="vehicle-card">
                    <div class="vehicle-card-header">
                        <h4>
                            <i class="fas fa-<?= $itemIcon ?>"></i>
                            <?= e($item['description']) ?>
                        </h4>
                        <div>
                            <span class="badge badge-light"><?= ucfirst($item['item_type']) ?></span>
                            <button type="button" class="btn btn-sm btn-outline-light ml-2" onclick="addVehicleDoc(<?= $item['id'] ?>)">
                                <i class="fas fa-plus"></i> Tambah Kendaraan
                            </button>
                        </div>
                    </div>
                    
                    <div class="vehicle-card-body">
                        <div class="vehicle-items" id="vehicleItems<?= $item['id'] ?>">
                            <?php if (empty($itemDocs)): ?>
                            <div class="empty-state" id="emptyState<?= $item['id'] ?>">
                                <i class="fas fa-car"></i>
                                <p>Belum ada data kendaraan. Klik "Tambah Kendaraan" untuk mulai.</p>
                            </div>
                            <?php else: ?>
                                <?php foreach ($itemDocs as $vIdx => $doc): ?>
                                <div class="vehicle-item" id="vehicle-<?= $item['id'] ?>-<?= $vIdx ?>">
                                    <div class="vehicle-item-header">
                                        <div class="vehicle-item-num">Kendaraan <?= $vIdx + 1 ?></div>
                                        <button type="button" class="btn btn-sm btn-outline-danger" 
                                                onclick="removeVehicleDoc('vehicle-<?= $item['id'] ?>-<?= $vIdx ?>', <?= $item['id'] ?>)">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </div>
                                    
                                    <div class="vehicle-fields">
                                        <div class="row g-2 mb-3">
                                            <div class="col-md-4">
                                                <label class="form-label-sm">Jenis Kendaraan <span class="text-danger">*</span></label>
                                                <input type="text" class="form-control" 
                                                       name="vehicles[<?= $item['id'] ?>][<?= $vIdx ?>][vehicle_type]" 
                                                       value="<?= e($doc['vehicle_type']) ?>"
                                                       placeholder="Avanza, Innova, Bus, dll" required>
                                            </div>
                                            <div class="col-md-4">
                                                <label class="form-label-sm">Plat Nomor</label>
                                                <input type="text" class="form-control" 
                                                       name="vehicles[<?= $item['id'] ?>][<?= $vIdx ?>][plate_number]" 
                                                       value="<?= e($doc['plate_number'] ?? '') ?>"
                                                       placeholder="B 1234 ABC">
                                            </div>
                                            <div class="col-md-4">
                                                <label class="form-label-sm">Nama Driver</label>
                                                <input type="text" class="form-control" 
                                                       name="vehicles[<?= $item['id'] ?>][<?= $vIdx ?>][driver_name]" 
                                                       value="<?= e($doc['driver_name'] ?? '') ?>"
                                                       placeholder="Nama driver">
                                            </div>
                                        </div>
                                        
                                        <!-- Photo Upload Section -->
                                        <div class="photo-upload-section">
                                            <h6><i class="fas fa-camera text-primary"></i> Foto Dokumen</h6>
                                            
                                            <div class="photo-grid">
                                                <!-- Driver Photo -->
                                                <div class="photo-item <?= !empty($doc['photo_driver']) ? 'has-photo' : '' ?>" id="photoDriver<?= $item['id'] ?>_<?= $vIdx ?>">
                                                    <span class="photo-item-label">📸 Foto Driver & Kendaraan</span>
                                                    <div class="photo-preview">
                                                        <?php if (!empty($doc['photo_driver'])): ?>
                                                        <img src="/uploads/vehicles/<?= e($doc['photo_driver']) ?>" alt="Driver">
                                                        <?php else: ?>
                                                        <span class="photo-placeholder"><i class="fas fa-user"></i></span>
                                                        <?php endif; ?>
                                                    </div>
                                                    <input type="hidden" name="vehicles[<?= $item['id'] ?>][<?= $vIdx ?>][photo_driver]" 
                                                           value="<?= e($doc['photo_driver'] ?? '') ?>" id="inputPhotoDriver<?= $item['id'] ?>_<?= $vIdx ?>">
                                                    <div class="photo-actions">
                                                        <label class="btn btn-xs btn-primary">
                                                            <i class="fas fa-upload"></i> Upload
                                                            <input type="file" accept="image/*" style="display:none" 
                                                                   onchange="uploadVehiclePhoto(<?= $item['id'] ?>, <?= $vIdx ?>, 'driver', this)">
                                                        </label>
                                                        <?php if (!empty($doc['photo_driver'])): ?>
                                                        <button type="button" class="btn btn-xs btn-outline-danger" 
                                                                onclick="deleteVehiclePhoto(<?= $item['id'] ?>, <?= $vIdx ?>, 'driver')">
                                                            <i class="fas fa-trash"></i>
                                                        </button>
                                                        <?php endif; ?>
                                                    </div>
                                                    <div class="photo-upload-progress" id="progressDriver<?= $item['id'] ?>_<?= $vIdx ?>">
                                                        <div class="progress" style="height: 4px;">
                                                            <div class="progress-bar progress-bar-striped progress-bar-animated" style="width: 100%"></div>
                                                        </div>
                                                    </div>
                                                </div>
                                                
                                                <!-- SIM Photo -->
                                                <div class="photo-item <?= !empty($doc['photo_sim']) ? 'has-photo' : '' ?>" id="photoSim<?= $item['id'] ?>_<?= $vIdx ?>">
                                                    <span class="photo-item-label">🪪 Foto SIM</span>
                                                    <div class="photo-preview">
                                                        <?php if (!empty($doc['photo_sim'])): ?>
                                                        <img src="/uploads/vehicles/<?= e($doc['photo_sim']) ?>" alt="SIM">
                                                        <?php else: ?>
                                                        <span class="photo-placeholder"><i class="fas fa-id-card"></i></span>
                                                        <?php endif; ?>
                                                    </div>
                                                    <input type="hidden" name="vehicles[<?= $item['id'] ?>][<?= $vIdx ?>][photo_sim]" 
                                                           value="<?= e($doc['photo_sim'] ?? '') ?>" id="inputPhotoSim<?= $item['id'] ?>_<?= $vIdx ?>">
                                                    <div class="photo-actions">
                                                        <label class="btn btn-xs btn-primary">
                                                            <i class="fas fa-upload"></i> Upload
                                                            <input type="file" accept="image/*" style="display:none" 
                                                                   onchange="uploadVehiclePhoto(<?= $item['id'] ?>, <?= $vIdx ?>, 'sim', this)">
                                                        </label>
                                                        <?php if (!empty($doc['photo_sim'])): ?>
                                                        <button type="button" class="btn btn-xs btn-outline-danger" 
                                                                onclick="deleteVehiclePhoto(<?= $item['id'] ?>, <?= $vIdx ?>, 'sim')">
                                                            <i class="fas fa-trash"></i>
                                                        </button>
                                                        <?php endif; ?>
                                                    </div>
                                                    <div class="photo-upload-progress" id="progressSim<?= $item['id'] ?>_<?= $vIdx ?>">
                                                        <div class="progress" style="height: 4px;">
                                                            <div class="progress-bar progress-bar-striped progress-bar-animated" style="width: 100%"></div>
                                                        </div>
                                                    </div>
                                                </div>
                                                
                                                <!-- STNK Photo (Landscape) -->
                                                <div class="photo-item photo-stnk <?= !empty($doc['photo_stnk']) ? 'has-photo' : '' ?>" id="photoStnk<?= $item['id'] ?>_<?= $vIdx ?>">
                                                    <span class="photo-item-label">📄 Foto STNK (Landscape)</span>
                                                    <div class="photo-preview stnk-preview">
                                                        <?php if (!empty($doc['photo_stnk'])): ?>
                                                        <img src="/uploads/vehicles/<?= e($doc['photo_stnk']) ?>" alt="STNK" style="object-fit: contain;">
                                                        <?php else: ?>
                                                        <span class="photo-placeholder"><i class="fas fa-file-alt"></i></span>
                                                        <?php endif; ?>
                                                    </div>
                                                    <input type="hidden" name="vehicles[<?= $item['id'] ?>][<?= $vIdx ?>][photo_stnk]" 
                                                           value="<?= e($doc['photo_stnk'] ?? '') ?>" id="inputPhotoStnk<?= $item['id'] ?>_<?= $vIdx ?>">
                                                    <div class="photo-actions">
                                                        <label class="btn btn-xs btn-primary">
                                                            <i class="fas fa-upload"></i> Upload
                                                            <input type="file" accept="image/*" style="display:none" 
                                                                   onchange="uploadVehiclePhoto(<?= $item['id'] ?>, <?= $vIdx ?>, 'stnk', this)">
                                                        </label>
                                                        <?php if (!empty($doc['photo_stnk'])): ?>
                                                        <button type="button" class="btn btn-xs btn-outline-danger" 
                                                                onclick="deleteVehiclePhoto(<?= $item['id'] ?>, <?= $vIdx ?>, 'stnk')">
                                                            <i class="fas fa-trash"></i>
                                                        </button>
                                                        <?php endif; ?>
                                                    </div>
                                                    <div class="photo-upload-progress" id="progressStnk<?= $item['id'] ?>_<?= $vIdx ?>">
                                                        <div class="progress" style="height: 4px;">
                                                            <div class="progress-bar progress-bar-striped progress-bar-animated" style="width: 100%"></div>
                                                        </div>
                                                    </div>
                                                </div>
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
                    <a href="<?= url('/orders/' . $order->id) ?>" class="btn btn-outline-secondary">
                        <i class="fas fa-times"></i> Batal
                    </a>
                    <button type="submit" class="btn btn-success">
                        <i class="fas fa-save"></i> Simpan Data Kendaraan
                    </button>
                </div>
            </form>
        </div>
        
        <div class="col-lg-4">
            <!-- Preview Section -->
            <div class="glass-card mb-4 preview-card">
                <div class="preview-header">
                    <h5><i class="fas fa-eye text-danger"></i> Preview & Cetak</h5>
                </div>
                
                <?php if (empty($documents)): ?>
                <div class="preview-empty">
                    <i class="fas fa-file-alt"></i>
                    <p>Preview akan muncul setelah data kendaraan disimpan</p>
                </div>
                <?php else: ?>
                <p class="text-muted mb-3" style="font-size: 0.85rem;">
                    <?= count($documents) ?> kendaraan terdaftar
                </p>
                <a href="<?= url('/orders/' . $order->id . '/vehicle-documents-print') ?>" target="_blank" class="btn btn-danger btn-block">
                    <i class="fas fa-print"></i> Cetak Lampiran
                </a>
                <?php endif; ?>
            </div>
            
            <!-- Order Info -->
            <div class="glass-card mb-4">
                <h5><i class="fas fa-info-circle text-primary"></i> Info Pesanan</h5>
                <div class="info-list">
                    <div class="info-item">
                        <label>No. Pesanan</label>
                        <span><?= e($order->order_number) ?></span>
                    </div>
                    <?php if ($client): ?>
                    <div class="info-item">
                        <label>Klien</label>
                        <span><?= e($client['name']) ?></span>
                    </div>
                    <?php endif; ?>
                    <div class="info-item">
                        <label>Jumlah Item Kendaraan</label>
                        <span><?= count($vehicleItems) ?> item</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Vehicle Document Template -->
<template id="vehicleDocTemplate">
    <div class="vehicle-item" id="vehicle-{itemId}-{index}">
        <div class="vehicle-item-header">
            <div class="vehicle-item-num">Kendaraan {num}</div>
            <button type="button" class="btn btn-sm btn-outline-danger" 
                    onclick="removeVehicleDoc('vehicle-{itemId}-{index}', {itemId})">
                <i class="fas fa-trash"></i>
            </button>
        </div>
        
        <div class="vehicle-fields">
            <div class="row g-2 mb-3">
                <div class="col-md-4">
                    <label class="form-label-sm">Jenis Kendaraan <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" 
                           name="vehicles[{itemId}][{index}][vehicle_type]" 
                           placeholder="Avanza, Innova, Bus, dll" required>
                </div>
                <div class="col-md-4">
                    <label class="form-label-sm">Plat Nomor</label>
                    <input type="text" class="form-control" 
                           name="vehicles[{itemId}][{index}][plate_number]" 
                           placeholder="B 1234 ABC">
                </div>
                <div class="col-md-4">
                    <label class="form-label-sm">Nama Driver</label>
                    <input type="text" class="form-control" 
                           name="vehicles[{itemId}][{index}][driver_name]" 
                           placeholder="Nama driver">
                </div>
            </div>
            
            <!-- Photo Upload Section -->
            <div class="photo-upload-section">
                <h6><i class="fas fa-camera text-primary"></i> Foto Dokumen</h6>
                
                <div class="photo-grid">
                    <!-- Driver Photo -->
                    <div class="photo-item" id="photoDriver{itemId}_{index}">
                        <span class="photo-item-label">📸 Foto Driver & Kendaraan</span>
                        <div class="photo-preview">
                            <span class="photo-placeholder"><i class="fas fa-user"></i></span>
                        </div>
                        <input type="hidden" name="vehicles[{itemId}][{index}][photo_driver]" value="" id="inputPhotoDriver{itemId}_{index}">
                        <div class="photo-actions">
                            <label class="btn btn-xs btn-primary">
                                <i class="fas fa-upload"></i> Upload
                                <input type="file" accept="image/*" style="display:none" 
                                       onchange="uploadVehiclePhoto({itemId}, {index}, 'driver', this)">
                            </label>
                        </div>
                        <div class="photo-upload-progress" id="progressDriver{itemId}_{index}">
                            <div class="progress" style="height: 4px;">
                                <div class="progress-bar progress-bar-striped progress-bar-animated" style="width: 100%"></div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- SIM Photo -->
                    <div class="photo-item" id="photoSim{itemId}_{index}">
                        <span class="photo-item-label">🪪 Foto SIM</span>
                        <div class="photo-preview">
                            <span class="photo-placeholder"><i class="fas fa-id-card"></i></span>
                        </div>
                        <input type="hidden" name="vehicles[{itemId}][{index}][photo_sim]" value="" id="inputPhotoSim{itemId}_{index}">
                        <div class="photo-actions">
                            <label class="btn btn-xs btn-primary">
                                <i class="fas fa-upload"></i> Upload
                                <input type="file" accept="image/*" style="display:none" 
                                       onchange="uploadVehiclePhoto({itemId}, {index}, 'sim', this)">
                            </label>
                        </div>
                        <div class="photo-upload-progress" id="progressSim{itemId}_{index}">
                            <div class="progress" style="height: 4px;">
                                <div class="progress-bar progress-bar-striped progress-bar-animated" style="width: 100%"></div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- STNK Photo (Landscape) -->
                    <div class="photo-item photo-stnk" id="photoStnk{itemId}_{index}">
                        <span class="photo-item-label">📄 Foto STNK (Landscape)</span>
                        <div class="photo-preview stnk-preview">
                            <span class="photo-placeholder"><i class="fas fa-file-alt"></i></span>
                        </div>
                        <input type="hidden" name="vehicles[{itemId}][{index}][photo_stnk]" value="" id="inputPhotoStnk{itemId}_{index}">
                        <div class="photo-actions">
                            <label class="btn btn-xs btn-primary">
                                <i class="fas fa-upload"></i> Upload
                                <input type="file" accept="image/*" style="display:none" 
                                       onchange="uploadVehiclePhoto({itemId}, {index}, 'stnk', this)">
                            </label>
                        </div>
                        <div class="photo-upload-progress" id="progressStnk{itemId}_{index}">
                            <div class="progress" style="height: 4px;">
                                <div class="progress-bar progress-bar-striped progress-bar-animated" style="width: 100%"></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script>
let vehicleCounters = {};
const orderId = <?= $order->id ?>;
const csrfToken = '<?= function_exists('csrf_token') ? csrf_token() : (isset($_SESSION['csrf_token']) ? $_SESSION['csrf_token'] : '') ?>';

// Initialize counters
<?php foreach ($vehicleItems as $item): ?>
vehicleCounters[<?= $item['id'] ?>] = <?= count($docsByItem[$item['id']] ?? []) ?>;
<?php endforeach; ?>

function addVehicleDoc(itemId) {
    const container = document.getElementById('vehicleItems' + itemId);
    
    // Remove empty state if exists
    const emptyState = document.getElementById('emptyState' + itemId);
    if (emptyState) {
        emptyState.remove();
    }
    
    const index = vehicleCounters[itemId]++;
    const num = container.querySelectorAll('.vehicle-item').length + 1;
    
    const template = document.getElementById('vehicleDocTemplate').innerHTML;
    const html = template
        .replace(/{itemId}/g, itemId)
        .replace(/{index}/g, index)
        .replace(/{num}/g, num);
    
    container.insertAdjacentHTML('beforeend', html);
    
    // Focus on new row
    const newRow = document.getElementById('vehicle-' + itemId + '-' + index);
    if (newRow) {
        newRow.querySelector('input[name*="[vehicle_type]"]').focus();
    }
}

function removeVehicleDoc(rowId, itemId) {
    const row = document.getElementById(rowId);
    if (row) {
        row.remove();
        renumberVehicles(itemId);
        
        // Show empty state if no vehicles left
        const container = document.getElementById('vehicleItems' + itemId);
        if (container.querySelectorAll('.vehicle-item').length === 0) {
            container.innerHTML = `
                <div class="empty-state" id="emptyState${itemId}">
                    <i class="fas fa-car"></i>
                    <p>Belum ada data kendaraan. Klik "Tambah Kendaraan" untuk mulai.</p>
                </div>
            `;
        }
    }
}

function renumberVehicles(itemId) {
    const container = document.getElementById('vehicleItems' + itemId);
    if (container) {
        container.querySelectorAll('.vehicle-item').forEach((row, idx) => {
            row.querySelector('.vehicle-item-num').textContent = 'Kendaraan ' + (idx + 1);
        });
    }
}

// Photo Upload Functions
function uploadVehiclePhoto(itemId, vehicleIdx, photoType, input) {
    if (!input.files || !input.files[0]) return;
    
    const file = input.files[0];
    
    // Validate file type
    const allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
    if (!allowedTypes.includes(file.type)) {
        alert('Format file tidak didukung. Gunakan JPG, PNG, GIF, atau WebP.');
        return;
    }
    
    // Validate file size (5MB max)
    if (file.size > 5 * 1024 * 1024) {
        alert('Ukuran file maksimal 5MB.');
        return;
    }
    
    // Show progress
    const progressId = 'progress' + photoType.charAt(0).toUpperCase() + photoType.slice(1) + itemId + '_' + vehicleIdx;
    const progressEl = document.getElementById(progressId);
    if (progressEl) progressEl.style.display = 'block';
    
    // Create form data
    const formData = new FormData();
    formData.append('photo', file);
    formData.append('photo_type', photoType);
    formData.append('item_id', itemId);
    formData.append('vehicle_index', vehicleIdx);
    formData.append('csrf_token', csrfToken);
    
    // Upload
    fetch(`/orders/${orderId}/upload-vehicle-photo`, {
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
            updatePhotoUI(itemId, vehicleIdx, photoType, data.data.filename, data.data.url);
        } else {
            alert(data.message || 'Gagal mengupload foto.');
        }
    })
    .catch(error => {
        console.error('Upload error:', error);
        if (progressEl) progressEl.style.display = 'none';
        alert('Gagal mengupload foto.');
    });
}

function updatePhotoUI(itemId, vehicleIdx, photoType, filename, url) {
    const typeCapital = photoType.charAt(0).toUpperCase() + photoType.slice(1);
    const containerId = 'photo' + typeCapital + itemId + '_' + vehicleIdx;
    const inputId = 'inputPhoto' + typeCapital + itemId + '_' + vehicleIdx;
    
    const container = document.getElementById(containerId);
    const input = document.getElementById(inputId);
    
    if (input) {
        input.value = filename;
    }
    
    if (container) {
        container.classList.add('has-photo');
        const preview = container.querySelector('.photo-preview');
        if (preview) {
            preview.innerHTML = `<img src="${url}" alt="${photoType}" style="${photoType === 'stnk' ? 'object-fit: contain;' : ''}">`;
        }
        
        // Update actions
        const actions = container.querySelector('.photo-actions');
        if (actions) {
            actions.innerHTML = `
                <label class="btn btn-xs btn-primary">
                    <i class="fas fa-edit"></i> Ganti
                    <input type="file" accept="image/*" style="display:none" 
                           onchange="uploadVehiclePhoto(${itemId}, ${vehicleIdx}, '${photoType}', this)">
                </label>
                <button type="button" class="btn btn-xs btn-outline-danger" 
                        onclick="deleteVehiclePhoto(${itemId}, ${vehicleIdx}, '${photoType}')">
                    <i class="fas fa-trash"></i>
                </button>
            `;
        }
    }
}

function deleteVehiclePhoto(itemId, vehicleIdx, photoType) {
    if (!confirm('Hapus foto ini?')) return;
    
    const typeCapital = photoType.charAt(0).toUpperCase() + photoType.slice(1);
    const inputId = 'inputPhoto' + typeCapital + itemId + '_' + vehicleIdx;
    const input = document.getElementById(inputId);
    const filename = input ? input.value : '';
    
    // Delete from server
    fetch(`/orders/${orderId}/delete-vehicle-photo`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': csrfToken
        },
        body: JSON.stringify({ 
            filename: filename,
            csrf_token: csrfToken 
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            resetPhotoUI(itemId, vehicleIdx, photoType);
        }
    })
    .catch(error => {
        console.error('Delete error:', error);
    });
}

function resetPhotoUI(itemId, vehicleIdx, photoType) {
    const typeCapital = photoType.charAt(0).toUpperCase() + photoType.slice(1);
    const containerId = 'photo' + typeCapital + itemId + '_' + vehicleIdx;
    const inputId = 'inputPhoto' + typeCapital + itemId + '_' + vehicleIdx;
    
    const container = document.getElementById(containerId);
    const input = document.getElementById(inputId);
    
    if (input) {
        input.value = '';
    }
    
    if (container) {
        container.classList.remove('has-photo');
        const preview = container.querySelector('.photo-preview');
        const iconMap = { driver: 'user', sim: 'id-card', stnk: 'file-alt' };
        if (preview) {
            preview.innerHTML = `<span class="photo-placeholder"><i class="fas fa-${iconMap[photoType]}"></i></span>`;
        }
        
        // Update actions
        const actions = container.querySelector('.photo-actions');
        if (actions) {
            actions.innerHTML = `
                <label class="btn btn-xs btn-primary">
                    <i class="fas fa-upload"></i> Upload
                    <input type="file" accept="image/*" style="display:none" 
                           onchange="uploadVehiclePhoto(${itemId}, ${vehicleIdx}, '${photoType}', this)">
                </label>
            `;
        }
    }
}

// Form validation
document.getElementById('vehicleDocumentsForm').addEventListener('submit', function(e) {
    let hasVehicle = false;
    document.querySelectorAll('input[name*="[vehicle_type]"]').forEach(input => {
        if (input.value.trim()) {
            hasVehicle = true;
        }
    });
    
    if (!hasVehicle) {
        e.preventDefault();
        alert('Minimal satu kendaraan dengan jenis kendaraan harus diisi.');
        return false;
    }
});
</script>