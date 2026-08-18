<?php



/**
 * Hotel Guests Form View - v4
 * Room-based input (2 guests per room)
 * With per-hotel logo upload
 */

// Get company for preview
$previewCompany = db()->fetchOne("SELECT * FROM companies WHERE id = ?", [Session::companyId()]);

// Group existing guests by room (room_number within same hotel)
$roomsByHotel = [];
foreach ($hotelItems as $hotel) {
    $hotelId = $hotel['id'];
    $hotelGuests = $guestsByHotel[$hotelId] ?? [];
    
    // Group by room_number
    $rooms = [];
    foreach ($hotelGuests as $guest) {
        $roomKey = $guest['room_number'] ?: 'room_' . $guest['id'];
        if (!isset($rooms[$roomKey])) {
            $rooms[$roomKey] = [
                'room_number' => $guest['room_number'],
                'room_type' => $guest['room_type'],
                'check_in_date' => $guest['check_in_date'],
                'check_out_date' => $guest['check_out_date'],
                'guest_1' => '',
                'guest_2' => ''
            ];
        }
        // Fill guest slots
        if (empty($rooms[$roomKey]['guest_1'])) {
            $rooms[$roomKey]['guest_1'] = $guest['guest_name'];
        } elseif (empty($rooms[$roomKey]['guest_2'])) {
            $rooms[$roomKey]['guest_2'] = $guest['guest_name'];
        }
    }
    $roomsByHotel[$hotelId] = array_values($rooms);
}
?>

<style>
/* ========================================
   LOGO UPLOAD STYLES
   ======================================== */
.logo-section-header {
    margin-bottom: 1rem;
}

.logo-section-header h5 {
    margin: 0;
    color: #1f2937;
}

.logo-upload-area {
    border: 2px dashed #e5e7eb;
    border-radius: 12px;
    padding: 1.5rem;
    text-align: center;
    transition: all 0.3s;
}

.logo-upload-area:hover {
    border-color: #3b82f6;
    background: #f8fafc;
}

.logo-upload-area.dragover {
    border-color: #3b82f6;
    background: #eff6ff;
}

.logo-placeholder {
    color: #9ca3af;
}

.logo-placeholder i {
    font-size: 3rem;
    margin-bottom: 0.5rem;
    opacity: 0.5;
}

.logo-placeholder p {
    margin: 0.5rem 0 0.25rem;
    font-weight: 500;
    color: #6b7280;
}

.logo-placeholder small {
    color: #9ca3af;
}

.current-logo {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 1rem;
}

.current-logo img {
    max-height: 80px;
    max-width: 200px;
    object-fit: contain;
    border-radius: 8px;
    border: 1px solid #e5e7eb;
    padding: 10px;
    background: white;
}

.logo-actions {
    display: flex;
    gap: 0.5rem;
}

.upload-progress {
    padding: 1rem;
}

.upload-progress .progress {
    height: 8px;
    border-radius: 4px;
    margin-bottom: 0.5rem;
}

/* ========================================
   FORM STYLES
   ======================================== */
.hotel-form-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    margin-bottom: 1rem;
    padding-bottom: 1rem;
    border-bottom: 1px solid #e5e7eb;
}

.hotel-form-header .hotel-info h4 {
    margin: 0 0 0.5rem 0;
    font-size: 1.1rem;
    color: #1f2937;
}

.hotel-form-header .hotel-meta {
    display: flex;
    gap: 0.5rem;
}

.hotel-info-edit {
    background: #f9fafb;
    padding: 1rem;
    border-radius: 8px;
    margin-bottom: 1rem;
    border: 1px solid #e5e7eb;
}

.guest-list-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 1rem;
    padding-bottom: 0.75rem;
    border-bottom: 1px solid #e5e7eb;
}

.guest-list-header h5 {
    margin: 0;
    font-size: 0.95rem;
    color: #374151;
}

/* Room-based styles */
.room-items {
    display: flex;
    flex-direction: column;
    gap: 1rem;
}

/* Hotel Logo Upload Section */
.hotel-logo-section {
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

.hotel-logo-upload {
    flex: 1;
    display: flex;
    align-items: center;
}

.hotel-logo-preview {
    display: flex;
    align-items: center;
    gap: 10px;
}

.hotel-logo-preview img {
    max-height: 40px;
    max-width: 120px;
    object-fit: contain;
    border: 1px solid #e5e7eb;
    border-radius: 4px;
    padding: 4px;
    background: white;
}

.hotel-logo-actions {
    display: flex;
    gap: 4px;
}

.hotel-logo-empty {
    display: flex;
    align-items: center;
    gap: 8px;
    color: #9ca3af;
    font-size: 0.8rem;
}

.hotel-logo-empty i {
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

.room-row {
    background: white;
    border: 1px solid #e5e7eb;
    border-radius: 12px;
    padding: 1rem;
    transition: all 0.2s;
}

.room-row:hover {
    border-color: #3b82f6;
    box-shadow: 0 2px 8px rgba(59, 130, 246, 0.1);
}

.room-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 0.75rem;
    padding-bottom: 0.5rem;
    border-bottom: 1px dashed #e5e7eb;
}

.room-num {
    font-weight: 600;
    color: #3b82f6;
    font-size: 0.9rem;
}

.room-fields {
    flex: 1;
}

.form-label-sm {
    font-size: 0.75rem;
    color: #6b7280;
    margin-bottom: 0.25rem;
    display: block;
    font-weight: 500;
}

/* Legacy guest styles (keep for compatibility) */
.guest-items {
    display: flex;
    flex-direction: column;
    gap: 0.75rem;
}

.guest-row {
    display: flex;
    align-items: flex-start;
    gap: 0.75rem;
    padding: 0.75rem;
    background: #f9fafb;
    border-radius: 8px;
    border: 1px solid #e5e7eb;
}

.guest-num {
    width: 28px;
    height: 28px;
    background: #3b82f6;
    color: white;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 0.75rem;
    font-weight: 600;
    flex-shrink: 0;
}

.guest-fields {
    flex: 1;
    min-width: 0;
}

.guest-fields .form-control {
    font-size: 0.85rem;
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

/* ========================================
   A4 PREVIEW STYLES
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

/* A4 Preview Container */
.a4-preview-container {
    background: #e5e7eb;
    padding: 15px;
    border-radius: 8px;
    max-height: 600px;
    overflow-y: auto;
}

.a4-preview {
    background: white;
    width: 100%;
    aspect-ratio: 210 / 297;
    padding: 20px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
    font-size: 8px;
    overflow: hidden;
}

.a4-preview .preview-doc-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding-bottom: 10px;
    margin-bottom: 12px;
    border-bottom: 1px solid #e5e7eb;
}

.a4-preview .preview-logo {
    max-height: 30px;
    max-width: 80px;
    object-fit: contain;
}

.a4-preview .preview-logo-placeholder {
    width: 60px;
    height: 25px;
    background: #f3f4f6;
    border: 1px dashed #d1d5db;
    border-radius: 4px;
    display: flex;
    align-items: center;
    justify-content: center;
    color: #9ca3af;
    font-size: 6px;
}

.a4-preview .preview-badge {
    background: linear-gradient(135deg, #1f2937, #374151);
    color: white;
    padding: 4px 10px;
    border-radius: 10px;
    font-size: 6px;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.a4-preview .preview-table {
    width: 100%;
    border-collapse: collapse;
    font-size: 7px;
}

.a4-preview .preview-table th {
    background: linear-gradient(135deg, #1f2937, #374151);
    color: white;
    padding: 5px 4px;
    text-align: left;
    font-size: 6px;
    text-transform: uppercase;
    font-weight: 600;
}

.a4-preview .preview-table th:first-child {
    border-radius: 4px 0 0 0;
    text-align: center;
    width: 20px;
}

.a4-preview .preview-table th:last-child {
    border-radius: 0 4px 0 0;
}

.a4-preview .preview-table td {
    padding: 4px 4px;
    border-bottom: 1px solid #f3f4f6;
    color: #374151;
    vertical-align: middle;
}

.a4-preview .preview-table td:first-child {
    text-align: center;
    color: #9ca3af;
}

.a4-preview .preview-table tr.room-start td {
    border-top: 1px solid #d1d5db;
}

.a4-preview .preview-table tbody tr:first-child td {
    border-top: none;
}

.a4-preview .room-badge {
    background: #1f2937;
    color: white;
    padding: 2px 5px;
    border-radius: 2px;
    font-size: 6px;
}

.a4-preview .room-type-badge {
    background: #e5e7eb;
    color: #374151;
    padding: 2px 5px;
    border-radius: 2px;
    font-size: 6px;
}

.a4-preview .guest-name-cell {
    text-align: left !important;
    font-weight: 500;
}

.a4-preview .preview-hotel-section {
    margin-bottom: 12px;
    border: 1px solid #e5e7eb;
    border-radius: 6px;
    padding: 8px;
    background: #fafafa;
}

.a4-preview .preview-hotel-header {
    display: flex;
    align-items: center;
    gap: 8px;
    margin-bottom: 6px;
    padding-bottom: 6px;
    border-bottom: 1px solid #e5e7eb;
}

.a4-preview .preview-hotel-logo {
    max-height: 24px;
    max-width: 60px;
    object-fit: contain;
}

.a4-preview .preview-hotel-logo-placeholder {
    width: 40px;
    height: 20px;
    background: #e5e7eb;
    border-radius: 2px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 5px;
    color: #9ca3af;
}

.a4-preview .preview-hotel-info {
    flex: 1;
}

.a4-preview .preview-hotel-name {
    font-size: 7px;
    font-weight: 600;
    color: #1f2937;
}

.a4-preview .preview-hotel-badge {
    font-size: 5px;
    color: #6b7280;
}

.a4-preview .preview-note {
    font-size: 6px;
    color: #6b7280;
    text-align: center;
    margin-bottom: 8px;
    padding: 4px;
    background: #fef3c7;
    border-radius: 4px;
}

.a4-preview .preview-hotel-title {
    font-size: 8px;
    font-weight: 600;
    color: #1f2937;
    background: #f3f4f6;
    padding: 4px 6px;
    border-radius: 4px;
    margin-bottom: 4px;
}

.a4-preview .preview-hotel-section {
    margin-bottom: 10px;
}

.a4-preview .preview-hotel-title {
    background: linear-gradient(135deg, #1f2937 0%, #374151 100%);
    color: white;
    padding: 4px 8px;
    border-radius: 4px 4px 0 0;
    font-size: 7px;
    font-weight: 600;
}

.a4-preview .preview-hotel-section .preview-table {
    border-radius: 0 0 4px 4px;
}

.a4-preview .preview-hotel-section .preview-table th:first-child,
.a4-preview .preview-hotel-section .preview-table th:last-child {
    border-radius: 0;
}

.a4-preview .preview-footer {
    margin-top: 12px;
    padding-top: 8px;
    border-top: 1px solid #e5e7eb;
    display: flex;
    justify-content: space-between;
    font-size: 6px;
    color: #9ca3af;
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
    color: #3b82f6;
    border-color: #3b82f6;
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

@media (max-width: 767.98px) {
    .guest-row {
        flex-direction: column;
    }
    
    .guest-num {
        align-self: flex-start;
    }
}
</style>

<div class="page-actions mb-4">
    <a href="<?= url('/attachment-order/' . $order['id']) ?>" class="btn btn-secondary">
        <i class="fas fa-arrow-left"></i> Kembali ke Detail Order
    </a>
    <?php if (!empty($existingGuests)): ?>
    <a href="<?= url('/attachment-order/' . $order['id'] . '/hotel-attachment') ?>" target="_blank" class="btn btn-success">
        <i class="fas fa-print"></i> Cetak Lampiran
    </a>
    <?php endif; ?>
</div>

<div class="row">
    <div class="col-12 col-lg-7">
        <form method="POST" action="<?= url('/attachment-order/' . $order['id'] . '/hotel-guests') ?>" id="hotelGuestsForm">
            <?= csrfField() ?>
            
            <?php foreach ($hotelItems as $idx => $hotel): ?>
            <div class="glass-card mb-4">
                <div class="hotel-form-header">
                    <div class="hotel-info">
                        <h4>
                            <i class="fas fa-hotel text-primary"></i>
                            <?= e($hotel['hotel_name'] ?: $hotel['description']) ?>
                        </h4>
                        <div class="hotel-meta">
                            <span class="badge badge-primary"><?= e($hotel['quantity']) ?> Kamar</span>
                            <?php if ($hotel['room_type']): ?>
                            <span class="badge badge-secondary"><?= e($hotel['room_type']) ?></span>
                            <?php endif; ?>
                        </div>
                    </div>
                    <button type="button" class="btn btn-sm btn-outline-primary" onclick="toggleHotelInfo(<?= $hotel['id'] ?>)">
                        <i class="fas fa-cog"></i> Edit Info
                    </button>
                </div>
                
                <!-- Hotel Info Edit (collapsed by default) -->
                <div class="hotel-info-edit" id="hotelInfo<?= $hotel['id'] ?>" style="display:none;">
                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label class="form-label">Nama Hotel</label>
                            <input type="text" class="form-control" 
                                   name="hotel_info[<?= $hotel['id'] ?>][hotel_name]" 
                                   value="<?= e($hotel['hotel_name'] ?: $hotel['description']) ?>">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Tipe Kamar</label>
                            <input type="text" class="form-control" 
                                   name="hotel_info[<?= $hotel['id'] ?>][room_type]" 
                                   value="<?= e($hotel['room_type']) ?>"
                                   placeholder="Deluxe, Superior, dll">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Check-in</label>
                            <input type="date" class="form-control" 
                                   name="hotel_info[<?= $hotel['id'] ?>][check_in_date]" 
                                   value="<?= e($hotel['check_in_date']) ?>">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Check-out</label>
                            <input type="date" class="form-control" 
                                   name="hotel_info[<?= $hotel['id'] ?>][check_out_date]" 
                                   value="<?= e($hotel['check_out_date']) ?>">
                        </div>
                    </div>
                </div>
                
                <!-- Logo Upload for this hotel -->
                <div class="hotel-logo-section">
                    <div class="logo-section-label">
                        <i class="fas fa-image"></i> Logo Lampiran Hotel
                    </div>
                    <div class="hotel-logo-upload" id="logoUpload<?= $hotel['id'] ?>">
                        <?php 
                        $hotelLogo = $hotel['attachment_logo'] ?? '';
                        if ($hotelLogo && file_exists(BASE_PATH . '/uploads/logos/' . $hotelLogo)): 
                        ?>
                        <div class="hotel-logo-preview" id="logoPreview<?= $hotel['id'] ?>">
                            <img src="<?= url('/uploads/logos/' . $hotelLogo) ?>" alt="Logo" id="logoImg<?= $hotel['id'] ?>">
                            <div class="hotel-logo-actions">
                                <label class="btn btn-xs btn-outline-primary" for="logoInput<?= $hotel['id'] ?>">
                                    <i class="fas fa-edit"></i> Ganti
                                </label>
                                <button type="button" class="btn btn-xs btn-outline-danger" onclick="deleteHotelLogo(<?= $hotel['id'] ?>)">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </div>
                        </div>
                        <?php else: ?>
                        <div class="hotel-logo-empty" id="logoEmpty<?= $hotel['id'] ?>">
                            <i class="fas fa-cloud-upload-alt"></i>
                            <span>Upload logo hotel</span>
                            <label class="btn btn-xs btn-primary" for="logoInput<?= $hotel['id'] ?>">
                                <i class="fas fa-upload"></i> Pilih
                            </label>
                        </div>
                        <?php endif; ?>
                        <input type="file" id="logoInput<?= $hotel['id'] ?>" 
                               accept="image/png,image/jpeg,image/gif" 
                               style="display:none" 
                               onchange="uploadHotelLogo(<?= $hotel['id'] ?>, this)">
                        <div class="upload-progress-mini" id="uploadProgress<?= $hotel['id'] ?>" style="display:none">
                            <div class="progress" style="height: 4px;">
                                <div class="progress-bar progress-bar-striped progress-bar-animated" style="width: 100%"></div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Guest List -->
                <div class="guest-list" id="guestList<?= $hotel['id'] ?>">
                    <div class="guest-list-header">
                        <h5><i class="fas fa-door-open"></i> Daftar Room</h5>
                        <button type="button" class="btn btn-sm btn-primary" 
                                onclick="addRoom(<?= $hotel['id'] ?>, '<?= e(addslashes($hotel['hotel_name'] ?: $hotel['description'])) ?>')">
                            <i class="fas fa-plus"></i> Tambah Room
                        </button>
                    </div>
                    
                    <div class="room-items" id="roomItems<?= $hotel['id'] ?>">
                        <?php 
                        $hotelRooms = $roomsByHotel[$hotel['id']] ?? [];
                        if (empty($hotelRooms)): 
                        ?>
                        <div class="empty-state" id="emptyState<?= $hotel['id'] ?>">
                            <i class="fas fa-door-open"></i>
                            <p>Belum ada data room. Klik "Tambah Room" untuk mulai.</p>
                        </div>
                        <?php else: ?>
                            <?php foreach ($hotelRooms as $roomIdx => $room): ?>
                            <div class="room-row" id="room-<?= $hotel['id'] ?>-<?= $roomIdx ?>">
                                <input type="hidden" name="rooms[<?= $hotel['id'] ?>][<?= $roomIdx ?>][order_item_id]" value="<?= $hotel['id'] ?>">
                                <input type="hidden" name="rooms[<?= $hotel['id'] ?>][<?= $roomIdx ?>][hotel_name]" value="<?= e($hotel['hotel_name'] ?: $hotel['description']) ?>">
                                
                                <div class="room-header">
                                    <div class="room-num">Room <?= $roomIdx + 1 ?></div>
                                    <button type="button" class="btn btn-sm btn-outline-danger" 
                                            onclick="removeRoom('room-<?= $hotel['id'] ?>-<?= $roomIdx ?>', <?= $hotel['id'] ?>)">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </div>
                                
                                <div class="room-fields">
                                    <!-- Room Info Row -->
                                    <div class="row g-2 mb-2">
                                        <div class="col-md-3">
                                            <label class="form-label-sm">No. Room</label>
                                            <input type="text" class="form-control" 
                                                   name="rooms[<?= $hotel['id'] ?>][<?= $roomIdx ?>][room_number]" 
                                                   value="<?= e($room['room_number']) ?>"
                                                   placeholder="101, 102, dll">
                                        </div>
                                        <div class="col-md-3">
                                            <label class="form-label-sm">Type Room</label>
                                            <input type="text" class="form-control" 
                                                   name="rooms[<?= $hotel['id'] ?>][<?= $roomIdx ?>][room_type]" 
                                                   value="<?= e($room['room_type']) ?>"
                                                   placeholder="Deluxe, Twin, dll">
                                        </div>
                                        <div class="col-md-3">
                                            <label class="form-label-sm">Check-in</label>
                                            <input type="date" class="form-control" 
                                                   name="rooms[<?= $hotel['id'] ?>][<?= $roomIdx ?>][check_in_date]" 
                                                   value="<?= e($room['check_in_date']) ?>">
                                        </div>
                                        <div class="col-md-3">
                                            <label class="form-label-sm">Check-out</label>
                                            <input type="date" class="form-control" 
                                                   name="rooms[<?= $hotel['id'] ?>][<?= $roomIdx ?>][check_out_date]" 
                                                   value="<?= e($room['check_out_date']) ?>">
                                        </div>
                                    </div>
                                    <!-- Guest Names Row -->
                                    <div class="row g-2">
                                        <div class="col-md-6">
                                            <label class="form-label-sm">Tamu 1 <span class="text-danger">*</span></label>
                                            <input type="text" class="form-control" 
                                                   name="rooms[<?= $hotel['id'] ?>][<?= $roomIdx ?>][guest_1]" 
                                                   value="<?= e($room['guest_1']) ?>"
                                                   placeholder="Nama tamu pertama" required>
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label-sm">Tamu 2 <span class="text-muted">(opsional)</span></label>
                                            <input type="text" class="form-control" 
                                                   name="rooms[<?= $hotel['id'] ?>][<?= $roomIdx ?>][guest_2]" 
                                                   value="<?= e($room['guest_2']) ?>"
                                                   placeholder="Nama tamu kedua (jika ada)">
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
                <button type="submit" class="btn btn-primary btn-lg">
                    <i class="fas fa-save"></i> Simpan Data Tamu
                </button>
            </div>
        </form>
    </div>
    
    <div class="col-12 col-lg-5">
        <!-- A4 Preview Section -->
        <div class="glass-card mb-4 preview-card">
            <div class="preview-header">
                <h5><i class="fas fa-eye text-primary"></i> Preview Lampiran (A4)</h5>
                <?php if (!empty($existingGuests)): ?>
                <a href="<?= url('/attachment-order/' . $order['id'] . '/hotel-attachment') ?>" target="_blank" class="btn btn-sm btn-outline-primary">
                    <i class="fas fa-external-link-alt"></i> Fullscreen
                </a>
                <?php endif; ?>
            </div>
            
            <?php if (empty($existingGuests)): ?>
            <div class="preview-empty">
                <i class="fas fa-file-alt"></i>
                <p>Preview akan muncul setelah data tamu disimpan</p>
            </div>
            <?php else: ?>
            <!-- A4 Preview - Shows separate sections per hotel -->
            <div class="a4-preview-container">
                <div class="a4-preview">
                    <p class="preview-note">📄 Setiap hotel akan dicetak di halaman terpisah</p>
                    
                    <!-- Tables per Hotel -->
                    <?php 
                    $totalRooms = 0;
                    foreach ($hotelItems as $hotel):
                        $hotelRooms = $roomsByHotel[$hotel['id']] ?? [];
                        $totalRooms += count($hotelRooms);
                        $hotelLogo = $hotel['attachment_logo'] ?? '';
                        if (!empty($hotelRooms)):
                    ?>
                    <div class="preview-hotel-section">
                        <!-- Mini Header per Hotel -->
                        <div class="preview-hotel-header">
                            <?php if ($hotelLogo && file_exists(BASE_PATH . '/uploads/logos/' . $hotelLogo)): ?>
                            <img src="<?= url('/uploads/logos/' . $hotelLogo) ?>" alt="Logo" class="preview-hotel-logo">
                            <?php else: ?>
                            <div class="preview-hotel-logo-placeholder">Logo</div>
                            <?php endif; ?>
                            <div class="preview-hotel-info">
                                <div class="preview-hotel-name"><?= e($hotel['hotel_name'] ?: $hotel['description']) ?></div>
                                <div class="preview-hotel-badge">Lampiran Daftar Tamu</div>
                            </div>
                        </div>
                        
                        <table class="preview-table">
                            <thead>
                                <tr>
                                    <th>No</th>
                                    <th>Room</th>
                                    <th>Type</th>
                                    <th>Nama Tamu</th>
                                    <th>In</th>
                                    <th>Out</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php 
                                $no = 1;
                                foreach ($hotelRooms as $room):
                                    $hasGuest2 = !empty($room['guest_2']);
                                    $rowspan = $hasGuest2 ? 2 : 1;
                                ?>
                                <tr class="room-start">
                                    <td rowspan="<?= $rowspan ?>"><?= $no++ ?></td>
                                    <td rowspan="<?= $rowspan ?>">
                                        <?php if (!empty($room['room_number'])): ?>
                                        <span class="room-badge"><?= e($room['room_number']) ?></span>
                                        <?php else: ?>-<?php endif; ?>
                                    </td>
                                    <td rowspan="<?= $rowspan ?>">
                                        <?php if (!empty($room['room_type'])): ?>
                                        <span class="room-type-badge"><?= e($room['room_type']) ?></span>
                                        <?php else: ?>-<?php endif; ?>
                                    </td>
                                    <td class="guest-name-cell">1. <?= e($room['guest_1']) ?></td>
                                    <td rowspan="<?= $rowspan ?>"><?= !empty($room['check_in_date']) ? date('d/m', strtotime($room['check_in_date'])) : '-' ?></td>
                                    <td rowspan="<?= $rowspan ?>"><?= !empty($room['check_out_date']) ? date('d/m', strtotime($room['check_out_date'])) : '-' ?></td>
                                </tr>
                                <?php if ($hasGuest2): ?>
                                <tr>
                                    <td class="guest-name-cell">2. <?= e($room['guest_2']) ?></td>
                                </tr>
                                <?php endif; ?>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <?php 
                        endif;
                    endforeach; 
                    ?>
                    
                    <!-- Footer -->
                    <div class="preview-footer">
                        <span>Dicetak: <?= date('d/m/Y') ?></span>
                        <span>Total: <?= $totalRooms ?> room</span>
                    </div>
                </div>
            </div>
            <?php endif; ?>
        </div>
        
        <!-- Order Info -->
        <div class="glass-card mb-4">
            <h5><i class="fas fa-info-circle text-primary"></i> Info Pesanan</h5>
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
                    <label>Event</label>
                    <span><?= e($order['event_name']) ?: '-' ?></span>
                </div>
                <div class="info-item">
                    <label>Tanggal Event</label>
                    <span>
                        <?php if (!empty($order['event_date'])): ?>
                        <?= formatDate($order['event_date']) ?>
                        <?php else: ?>
                        -
                        <?php endif; ?>
                    </span>
                </div>
            </div>
        </div>
        
        <!-- Quick Actions -->
        <div class="glass-card">
            <h5><i class="fas fa-bolt text-warning"></i> Aksi Cepat</h5>
            <div class="quick-actions">
                <?php if (!empty($existingGuests)): ?>
                <a href="<?= url('/attachment-order/' . $order['id'] . '/hotel-attachment') ?>" target="_blank" class="quick-action-btn">
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

<!-- Room Row Template -->
<template id="roomRowTemplate">
    <div class="room-row" id="room-{hotelId}-{index}">
        <input type="hidden" name="rooms[{hotelId}][{index}][order_item_id]" value="{hotelId}">
        <input type="hidden" name="rooms[{hotelId}][{index}][hotel_name]" value="{hotelName}">
        
        <div class="room-header">
            <div class="room-num">Room {num}</div>
            <button type="button" class="btn btn-sm btn-outline-danger" onclick="removeRoom('room-{hotelId}-{index}', {hotelId})">
                <i class="fas fa-trash"></i>
            </button>
        </div>
        
        <div class="room-fields">
            <!-- Room Info Row -->
            <div class="row g-2 mb-2">
                <div class="col-md-3">
                    <label class="form-label-sm">No. Room</label>
                    <input type="text" class="form-control" 
                           name="rooms[{hotelId}][{index}][room_number]" 
                           placeholder="101, 102, dll">
                </div>
                <div class="col-md-3">
                    <label class="form-label-sm">Type Room</label>
                    <input type="text" class="form-control" 
                           name="rooms[{hotelId}][{index}][room_type]" 
                           placeholder="Deluxe, Twin, dll">
                </div>
                <div class="col-md-3">
                    <label class="form-label-sm">Check-in</label>
                    <input type="date" class="form-control" 
                           name="rooms[{hotelId}][{index}][check_in_date]">
                </div>
                <div class="col-md-3">
                    <label class="form-label-sm">Check-out</label>
                    <input type="date" class="form-control" 
                           name="rooms[{hotelId}][{index}][check_out_date]">
                </div>
            </div>
            <!-- Guest Names Row -->
            <div class="row g-2">
                <div class="col-md-6">
                    <label class="form-label-sm">Tamu 1 <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" 
                           name="rooms[{hotelId}][{index}][guest_1]" 
                           placeholder="Nama tamu pertama" required>
                </div>
                <div class="col-md-6">
                    <label class="form-label-sm">Tamu 2 <span class="text-muted">(opsional)</span></label>
                    <input type="text" class="form-control" 
                           name="rooms[{hotelId}][{index}][guest_2]" 
                           placeholder="Nama tamu kedua (jika ada)">
                </div>
            </div>
        </div>
    </div>
</template>

<script>
let roomCounters = {};

// Initialize counters
<?php foreach ($hotelItems as $hotel): ?>
roomCounters[<?= $hotel['id'] ?>] = <?= count($roomsByHotel[$hotel['id']] ?? []) ?>;
<?php endforeach; ?>

function toggleHotelInfo(hotelId) {
    const el = document.getElementById('hotelInfo' + hotelId);
    el.style.display = el.style.display === 'none' ? 'block' : 'none';
}

function addRoom(hotelId, hotelName) {
    const container = document.getElementById('roomItems' + hotelId);
    
    // Remove empty state if exists
    const emptyState = document.getElementById('emptyState' + hotelId);
    if (emptyState) {
        emptyState.remove();
    }
    
    const index = roomCounters[hotelId]++;
    const num = container.querySelectorAll('.room-row').length + 1;
    
    const template = document.getElementById('roomRowTemplate').innerHTML;
    const html = template
        .replace(/{hotelId}/g, hotelId)
        .replace(/{index}/g, index)
        .replace(/{hotelName}/g, hotelName)
        .replace(/{num}/g, num);
    
    container.insertAdjacentHTML('beforeend', html);
    
    // Focus on new row
    const newRow = document.getElementById('room-' + hotelId + '-' + index);
    if (newRow) {
        newRow.querySelector('input[name*="[guest_1]"]').focus();
    }
}

function removeRoom(rowId, hotelId) {
    const row = document.getElementById(rowId);
    if (row) {
        row.remove();
        renumberRooms(hotelId);
        
        // Show empty state if no rooms left
        const container = document.getElementById('roomItems' + hotelId);
        if (container.querySelectorAll('.room-row').length === 0) {
            container.innerHTML = `
                <div class="empty-state" id="emptyState${hotelId}">
                    <i class="fas fa-door-open"></i>
                    <p>Belum ada data room. Klik "Tambah Room" untuk mulai.</p>
                </div>
            `;
        }
    }
}

function renumberRooms(hotelId) {
    const container = document.getElementById('roomItems' + hotelId);
    if (container) {
        container.querySelectorAll('.room-row').forEach((row, idx) => {
            row.querySelector('.room-num').textContent = 'Room ' + (idx + 1);
        });
    }
}

// Form validation
document.getElementById('hotelGuestsForm').addEventListener('submit', function(e) {
    let hasRoom = false;
    document.querySelectorAll('input[name*="[guest_1]"]').forEach(input => {
        if (input.value.trim()) {
            hasRoom = true;
        }
    });
    
    if (!hasRoom) {
        e.preventDefault();
        alert('Minimal satu room dengan nama tamu harus diisi.');
        return false;
    }
});

// ========================================
// Hotel Logo Upload Functions (Per Hotel)
// ========================================

const orderId = <?= $order['id'] ?>;
const csrfToken = '<?= function_exists('csrf_token') ? csrf_token() : (isset($_SESSION['csrf_token']) ? $_SESSION['csrf_token'] : '') ?>';

function uploadHotelLogo(hotelItemId, input) {
    if (!input.files || !input.files[0]) return;
    
    const file = input.files[0];
    
    // Validate file type
    const allowedTypes = ['image/png', 'image/jpeg', 'image/gif'];
    if (!allowedTypes.includes(file.type)) {
        alert('Format file tidak didukung. Gunakan PNG, JPG, atau GIF.');
        return;
    }
    
    // Validate file size (2MB max)
    if (file.size > 2 * 1024 * 1024) {
        alert('Ukuran file maksimal 2MB.');
        return;
    }
    
    // Show progress
    const progressEl = document.getElementById('uploadProgress' + hotelItemId);
    const emptyEl = document.getElementById('logoEmpty' + hotelItemId);
    const previewEl = document.getElementById('logoPreview' + hotelItemId);
    
    if (progressEl) progressEl.style.display = 'block';
    if (emptyEl) emptyEl.style.display = 'none';
    if (previewEl) previewEl.style.display = 'none';
    
    // Create form data
    const formData = new FormData();
    formData.append('logo', file);
    formData.append('hotel_item_id', hotelItemId);
    formData.append('csrf_token', csrfToken);
    
    // Upload
    fetch(`/attachment-order/${orderId}/upload-hotel-logo`, {
        method: 'POST',
        body: formData,
        headers: {
            'X-CSRF-TOKEN': csrfToken
        }
    })
    .then(response => {
        if (!response.ok) throw new Error('Server error: ' + response.status);
        return response.json();
    })
    .then(data => {
        if (progressEl) progressEl.style.display = 'none';
        
        if (data.success) {
            updateHotelLogoUI(hotelItemId, data.data.url);
        } else {
            alert(data.message || 'Gagal mengupload logo.');
            resetHotelLogoUI(hotelItemId);
        }
    })
    .catch(error => {
        console.error('Upload error:', error);
        if (progressEl) progressEl.style.display = 'none';
        alert('Gagal mengupload logo. Error: ' + error.message);
        resetHotelLogoUI(hotelItemId);
    });
}

function deleteHotelLogo(hotelItemId) {
    if (!confirm('Hapus logo hotel ini?')) return;
    
    fetch(`/attachment-order/${orderId}/delete-hotel-logo`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': csrfToken
        },
        body: JSON.stringify({ 
            hotel_item_id: hotelItemId,
            csrf_token: csrfToken 
        })
    })
    .then(response => {
        if (!response.ok) throw new Error('Server error: ' + response.status);
        return response.json();
    })
    .then(data => {
        if (data.success) {
            resetHotelLogoUI(hotelItemId);
        } else {
            alert(data.message || 'Gagal menghapus logo.');
        }
    })
    .catch(error => {
        console.error('Delete error:', error);
        alert('Gagal menghapus logo.');
    });
}

function updateHotelLogoUI(hotelItemId, url) {
    const uploadArea = document.getElementById('logoUpload' + hotelItemId);
    uploadArea.innerHTML = `
        <div class="hotel-logo-preview" id="logoPreview${hotelItemId}">
            <img src="${url}" alt="Logo">
            <div class="hotel-logo-actions">
                <label class="btn btn-xs btn-outline-primary" for="logoInput${hotelItemId}">
                    <i class="fas fa-edit"></i> Ganti
                </label>
                <button type="button" class="btn btn-xs btn-outline-danger" onclick="deleteHotelLogo(${hotelItemId})">
                    <i class="fas fa-trash"></i>
                </button>
            </div>
        </div>
        <input type="file" id="logoInput${hotelItemId}" 
               accept="image/png,image/jpeg,image/gif" 
               style="display:none" 
               onchange="uploadHotelLogo(${hotelItemId}, this)">
        <div class="upload-progress-mini" id="uploadProgress${hotelItemId}" style="display:none">
            <div class="progress" style="height: 4px;">
                <div class="progress-bar progress-bar-striped progress-bar-animated" style="width: 100%"></div>
            </div>
        </div>
    `;
}

function resetHotelLogoUI(hotelItemId) {
    const uploadArea = document.getElementById('logoUpload' + hotelItemId);
    uploadArea.innerHTML = `
        <div class="hotel-logo-empty" id="logoEmpty${hotelItemId}">
            <i class="fas fa-cloud-upload-alt"></i>
            <span>Upload logo hotel</span>
            <label class="btn btn-xs btn-primary" for="logoInput${hotelItemId}">
                <i class="fas fa-upload"></i> Pilih
            </label>
        </div>
        <input type="file" id="logoInput${hotelItemId}" 
               accept="image/png,image/jpeg,image/gif" 
               style="display:none" 
               onchange="uploadHotelLogo(${hotelItemId}, this)">
        <div class="upload-progress-mini" id="uploadProgress${hotelItemId}" style="display:none">
            <div class="progress" style="height: 4px;">
                <div class="progress-bar progress-bar-striped progress-bar-animated" style="width: 100%"></div>
            </div>
        </div>
    `;
}
</script>