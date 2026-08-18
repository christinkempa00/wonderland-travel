<?php
/**
 * Flight Guests/Passengers Form View - v4
 * Ticket-based input with per-flight logo upload
 * Same structure as hotel-guests.php
 */

// Get company for preview
$previewCompany = db()->fetchOne("SELECT * FROM companies WHERE id = ?", [Session::companyId()]);

// Group existing details by flight item
$ticketsByFlight = [];
foreach ($flightItems as $flight) {
    $flightId = $flight['id'];
    $flightDetails = $detailsByFlight[$flightId] ?? [];
    $ticketsByFlight[$flightId] = $flightDetails;
}

// Helper for currency format
if (!function_exists('formatRupiahLocal')) {
    function formatRupiahLocal($number) {
        return 'Rp ' . number_format($number, 0, ',', '.');
    }
}
?>

<style>
/* ========================================
   LOGO UPLOAD STYLES
   ======================================== */
.flight-logo-section {
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

.flight-logo-upload {
    flex: 1;
    display: flex;
    align-items: center;
}

.flight-logo-preview {
    display: flex;
    align-items: center;
    gap: 10px;
}

.flight-logo-preview img {
    max-height: 40px;
    max-width: 120px;
    object-fit: contain;
    border: 1px solid #e5e7eb;
    border-radius: 4px;
    padding: 4px;
    background: white;
}

.flight-logo-actions {
    display: flex;
    gap: 4px;
}

.flight-logo-empty {
    display: flex;
    align-items: center;
    gap: 8px;
    color: #9ca3af;
    font-size: 0.8rem;
}

.flight-logo-empty i {
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
.flight-form-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    margin-bottom: 1rem;
    padding-bottom: 1rem;
    border-bottom: 1px solid #e5e7eb;
}

.flight-form-header .flight-info h4 {
    margin: 0 0 0.5rem 0;
    font-size: 1.1rem;
    color: #1f2937;
}

.flight-form-header .flight-meta {
    display: flex;
    gap: 0.5rem;
}

.ticket-list-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 1rem;
    padding-bottom: 0.75rem;
    border-bottom: 1px solid #e5e7eb;
}

.ticket-list-header h5 {
    margin: 0;
    font-size: 0.95rem;
    color: #374151;
}

.ticket-items {
    display: flex;
    flex-direction: column;
    gap: 1rem;
}

.ticket-row {
    background: white;
    border: 1px solid #e5e7eb;
    border-radius: 12px;
    padding: 1rem;
    transition: all 0.2s;
}

.ticket-row:hover {
    border-color: #c89b2c;
    box-shadow: 0 2px 8px rgba(200, 155, 44, 0.1);
}

.ticket-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 0.75rem;
    padding-bottom: 0.5rem;
    border-bottom: 1px dashed #e5e7eb;
}

.ticket-num {
    font-weight: 600;
    color: #c89b2c;
    font-size: 0.9rem;
}

.ticket-fields {
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

.a4-preview .preview-note {
    font-size: 6px;
    color: #6b7280;
    text-align: center;
    margin-bottom: 8px;
    padding: 4px;
    background: #fef3c7;
    border-radius: 4px;
}

.a4-preview .preview-flight-section {
    margin-bottom: 10px;
    border: 1px solid #e5e7eb;
    border-radius: 6px;
    padding: 8px;
    background: #fafafa;
}

.a4-preview .preview-flight-header {
    display: flex;
    align-items: center;
    gap: 8px;
    margin-bottom: 6px;
    padding-bottom: 6px;
    border-bottom: 1px solid #e5e7eb;
}

.a4-preview .preview-flight-logo {
    max-height: 24px;
    max-width: 60px;
    object-fit: contain;
}

.a4-preview .preview-flight-logo-placeholder {
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

.a4-preview .preview-flight-info {
    flex: 1;
}

.a4-preview .preview-flight-name {
    font-size: 7px;
    font-weight: 600;
    color: #1f2937;
}

.a4-preview .preview-flight-badge {
    font-size: 5px;
    color: #6b7280;
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
    text-align: right;
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

.a4-preview .preview-table td:last-child {
    text-align: right;
    font-weight: 600;
}

.a4-preview .service-badge {
    background: linear-gradient(135deg, #c89b2c, #a67f20);
    color: white;
    padding: 2px 6px;
    border-radius: 3px;
    font-size: 5px;
    font-weight: 600;
    text-transform: uppercase;
}

.a4-preview .preview-total {
    background: #fef2f2;
    padding: 6px 10px;
    border-radius: 4px;
    text-align: right;
    margin-top: 8px;
}

.a4-preview .preview-total-label {
    font-size: 6px;
    color: #6b7280;
}

.a4-preview .preview-total-value {
    font-size: 9px;
    font-weight: 700;
    color: #dc2626;
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
    color: #c89b2c;
    border-color: #c89b2c;
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
    <a href="<?= url('/orders/' . $order->id) ?>" class="btn btn-secondary">
        <i class="fas fa-arrow-left"></i> Kembali ke Pesanan
    </a>
    <?php if (!empty($existingDetails)): ?>
    <a href="<?= url('/orders/' . $order->id . '/flight-attachment') ?>" target="_blank" class="btn btn-success">
        <i class="fas fa-print"></i> Cetak Lampiran
    </a>
    <?php endif; ?>
</div>

<div class="row">
    <div class="col-12 col-lg-7">
        <form method="POST" action="<?= url('/orders/' . $order->id . '/flight-guests') ?>" id="flightGuestsForm">
            <?= csrfField() ?>
            
            <?php foreach ($flightItems as $idx => $flight): ?>
            <div class="glass-card mb-4">
                <div class="flight-form-header">
                    <div class="flight-info">
                        <h4>
                            <i class="fas fa-plane text-primary"></i>
                            <?= e($flight['description'] ?: 'Tiket Pesawat') ?>
                        </h4>
                        <div class="flight-meta">
                            <span class="badge badge-primary"><?= e($flight['quantity']) ?> Tiket</span>
                            <span class="badge badge-secondary"><?= formatRupiahLocal($flight['unit_price']) ?>/tiket</span>
                        </div>
                    </div>
                </div>
                
                <!-- Logo Upload for this flight -->
                <div class="flight-logo-section">
                    <div class="logo-section-label">
                        <i class="fas fa-image"></i> Logo Lampiran
                    </div>
                    <div class="flight-logo-upload" id="logoUpload<?= $flight['id'] ?>">
                        <?php 
                        $flightLogo = $flight['attachment_logo'] ?? '';
                        if ($flightLogo && file_exists(BASE_PATH . '/uploads/logos/' . $flightLogo)): 
                        ?>
                        <div class="flight-logo-preview" id="logoPreview<?= $flight['id'] ?>">
                            <img src="<?= url('/uploads/logos/' . $flightLogo) ?>" alt="Logo" id="logoImg<?= $flight['id'] ?>">
                            <div class="flight-logo-actions">
                                <label class="btn btn-xs btn-outline-primary" for="logoInput<?= $flight['id'] ?>">
                                    <i class="fas fa-edit"></i> Ganti
                                </label>
                                <button type="button" class="btn btn-xs btn-outline-danger" onclick="deleteFlightLogo(<?= $flight['id'] ?>)">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </div>
                        </div>
                        <?php else: ?>
                        <div class="flight-logo-empty" id="logoEmpty<?= $flight['id'] ?>">
                            <i class="fas fa-cloud-upload-alt"></i>
                            <span>Upload logo</span>
                            <label class="btn btn-xs btn-primary" for="logoInput<?= $flight['id'] ?>">
                                <i class="fas fa-upload"></i> Pilih
                            </label>
                        </div>
                        <?php endif; ?>
                        <input type="file" id="logoInput<?= $flight['id'] ?>" 
                               accept="image/png,image/jpeg,image/gif" 
                               style="display:none" 
                               onchange="uploadFlightLogo(<?= $flight['id'] ?>, this)">
                        <div class="upload-progress-mini" id="uploadProgress<?= $flight['id'] ?>" style="display:none">
                            <div class="progress" style="height: 4px;">
                                <div class="progress-bar progress-bar-striped progress-bar-animated" style="width: 100%"></div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Ticket List -->
                <div class="ticket-list" id="ticketList<?= $flight['id'] ?>">
                    <div class="ticket-list-header">
                        <h5><i class="fas fa-ticket-alt"></i> Daftar Tiket</h5>
                        <button type="button" class="btn btn-sm btn-primary" 
                                onclick="addTicket(<?= $flight['id'] ?>, <?= $flight['unit_price'] ?>)">
                            <i class="fas fa-plus"></i> Tambah Tiket
                        </button>
                    </div>
                    
                    <div class="ticket-items" id="ticketItems<?= $flight['id'] ?>">
                        <?php 
                        $flightTickets = $ticketsByFlight[$flight['id']] ?? [];
                        if (empty($flightTickets)): 
                        ?>
                        <div class="empty-state" id="emptyState<?= $flight['id'] ?>">
                            <i class="fas fa-ticket-alt"></i>
                            <p>Belum ada data tiket. Klik "Tambah Tiket" untuk mulai.</p>
                        </div>
                        <?php else: ?>
                            <?php foreach ($flightTickets as $ticketIdx => $ticket): ?>
                            <div class="ticket-row" id="ticket-<?= $flight['id'] ?>-<?= $ticketIdx ?>">
                                <input type="hidden" name="tickets[<?= $flight['id'] ?>][<?= $ticketIdx ?>][order_item_id]" value="<?= $flight['id'] ?>">
                                
                                <div class="ticket-header">
                                    <div class="ticket-num">Tiket <?= $ticketIdx + 1 ?></div>
                                    <button type="button" class="btn btn-sm btn-outline-danger" 
                                            onclick="removeTicket('ticket-<?= $flight['id'] ?>-<?= $ticketIdx ?>', <?= $flight['id'] ?>)">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </div>
                                
                                <div class="ticket-fields">
                                    <div class="row g-2">
                                        <div class="col-md-4">
                                            <label class="form-label-sm">Nama Penumpang <span class="text-danger">*</span></label>
                                            <input type="text" class="form-control" 
                                                   name="tickets[<?= $flight['id'] ?>][<?= $ticketIdx ?>][passenger_name]" 
                                                   value="<?= e($ticket['passenger_name']) ?>"
                                                   placeholder="Nama lengkap" required>
                                        </div>
                                        <div class="col-md-5">
                                            <label class="form-label-sm">Deskripsi (Kode Penerbangan, Rute)</label>
                                            <input type="text" class="form-control" 
                                                   name="tickets[<?= $flight['id'] ?>][<?= $ticketIdx ?>][description]" 
                                                   value="<?= e($ticket['description']) ?>"
                                                   placeholder="GA-123 CGK → DPS 15 Jan 08:00">
                                        </div>
                                        <div class="col-md-3">
                                            <label class="form-label-sm">Harga</label>
                                            <input type="number" class="form-control" 
                                                   name="tickets[<?= $flight['id'] ?>][<?= $ticketIdx ?>][price]" 
                                                   value="<?= $ticket['price'] ?>"
                                                   placeholder="0">
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
                    <i class="fas fa-save"></i> Simpan Data Penumpang
                </button>
            </div>
        </form>
    </div>
    
    <div class="col-12 col-lg-5">
        <!-- A4 Preview Section -->
        <div class="glass-card mb-4 preview-card">
            <div class="preview-header">
                <h5><i class="fas fa-eye text-primary"></i> Preview Lampiran (A4)</h5>
                <?php if (!empty($existingDetails)): ?>
                <a href="<?= url('/orders/' . $order->id . '/flight-attachment') ?>" target="_blank" class="btn btn-sm btn-outline-primary">
                    <i class="fas fa-external-link-alt"></i> Fullscreen
                </a>
                <?php endif; ?>
            </div>
            
            <?php if (empty($existingDetails)): ?>
            <div class="preview-empty">
                <i class="fas fa-file-alt"></i>
                <p>Preview akan muncul setelah data penumpang disimpan</p>
            </div>
            <?php else: ?>
            <div class="a4-preview-container">
                <div class="a4-preview">
                    <p class="preview-note">📄 Setiap item pesawat akan dicetak di halaman terpisah</p>
                    
                    <?php 
                    $grandTotal = 0;
                    foreach ($flightItems as $flight):
                        $flightTickets = $ticketsByFlight[$flight['id']] ?? [];
                        $flightLogo = $flight['attachment_logo'] ?? '';
                        $flightTotal = array_sum(array_column($flightTickets, 'price'));
                        $grandTotal += $flightTotal;
                        if (!empty($flightTickets)):
                    ?>
                    <div class="preview-flight-section">
                        <div class="preview-flight-header">
                            <?php if ($flightLogo && file_exists(BASE_PATH . '/uploads/logos/' . $flightLogo)): ?>
                            <img src="<?= url('/uploads/logos/' . $flightLogo) ?>" alt="Logo" class="preview-flight-logo">
                            <?php else: ?>
                            <div class="preview-flight-logo-placeholder">Logo</div>
                            <?php endif; ?>
                            <div class="preview-flight-info">
                                <div class="preview-flight-name"><?= e($flight['description'] ?: 'Tiket Pesawat') ?></div>
                                <div class="preview-flight-badge">Lampiran Pesawat</div>
                            </div>
                        </div>
                        
                        <table class="preview-table">
                            <thead>
                                <tr>
                                    <th>No</th>
                                    <th>Type</th>
                                    <th>Penumpang / Deskripsi</th>
                                    <th>Harga</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php $no = 1; foreach ($flightTickets as $ticket): ?>
                                <tr>
                                    <td><?= str_pad($no++, 2, '0', STR_PAD_LEFT) ?></td>
                                    <td><span class="service-badge">Flight</span></td>
                                    <td>
                                        <strong><?= e($ticket['passenger_name']) ?></strong><br>
                                        <small style="color:#6b7280"><?= e($ticket['description']) ?></small>
                                    </td>
                                    <td><?= formatRupiahLocal($ticket['price']) ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                        
                        <div class="preview-total">
                            <div class="preview-total-label">Total <?= count($flightTickets) ?> tiket</div>
                            <div class="preview-total-value"><?= formatRupiahLocal($flightTotal) ?></div>
                        </div>
                    </div>
                    <?php 
                        endif;
                    endforeach; 
                    ?>
                    
                    <div class="preview-footer">
                        <span>Dicetak: <?= date('d/m/Y') ?></span>
                        <span>Grand Total: <?= formatRupiahLocal($grandTotal) ?></span>
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
                    <span><?= e($order->order_number) ?></span>
                </div>
                <?php if ($client): ?>
                <div class="info-item">
                    <label>Klien</label>
                    <span><?= e($client['name']) ?></span>
                </div>
                <?php endif; ?>
                <div class="info-item">
                    <label>Total Item Pesawat</label>
                    <span><?= count($flightItems) ?> item</span>
                </div>
            </div>
        </div>
        
        <!-- Quick Actions -->
        <div class="glass-card">
            <h5><i class="fas fa-bolt text-warning"></i> Aksi Cepat</h5>
            <div class="quick-actions">
                <?php if (!empty($existingDetails)): ?>
                <a href="<?= url('/orders/' . $order->id . '/flight-attachment') ?>" target="_blank" class="quick-action-btn">
                    <i class="fas fa-print"></i>
                    <span>Cetak Lampiran</span>
                </a>
                <?php endif; ?>
                <a href="<?= url('/doc.php?order=' . $order->id . '&type=invoice') ?>" target="_blank" class="quick-action-btn">
                    <i class="fas fa-file-invoice"></i>
                    <span>Lihat Invoice</span>
                </a>
                <a href="<?= url('/orders/' . $order->id) ?>" class="quick-action-btn">
                    <i class="fas fa-eye"></i>
                    <span>Detail Pesanan</span>
                </a>
            </div>
        </div>
    </div>
</div>

<!-- Ticket Row Template -->
<template id="ticketRowTemplate">
    <div class="ticket-row" id="ticket-{flightId}-{index}">
        <input type="hidden" name="tickets[{flightId}][{index}][order_item_id]" value="{flightId}">
        
        <div class="ticket-header">
            <div class="ticket-num">Tiket {num}</div>
            <button type="button" class="btn btn-sm btn-outline-danger" onclick="removeTicket('ticket-{flightId}-{index}', {flightId})">
                <i class="fas fa-trash"></i>
            </button>
        </div>
        
        <div class="ticket-fields">
            <div class="row g-2">
                <div class="col-md-4">
                    <label class="form-label-sm">Nama Penumpang <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" 
                           name="tickets[{flightId}][{index}][passenger_name]" 
                           placeholder="Nama lengkap" required>
                </div>
                <div class="col-md-5">
                    <label class="form-label-sm">Deskripsi (Kode Penerbangan, Rute)</label>
                    <input type="text" class="form-control" 
                           name="tickets[{flightId}][{index}][description]" 
                           placeholder="GA-123 CGK → DPS 15 Jan 08:00">
                </div>
                <div class="col-md-3">
                    <label class="form-label-sm">Harga</label>
                    <input type="number" class="form-control" 
                           name="tickets[{flightId}][{index}][price]" 
                           value="{unitPrice}"
                           placeholder="0">
                </div>
            </div>
        </div>
    </div>
</template>

<script>
let ticketCounters = {};

// Initialize counters
<?php foreach ($flightItems as $flight): ?>
ticketCounters[<?= $flight['id'] ?>] = <?= count($ticketsByFlight[$flight['id']] ?? []) ?>;
<?php endforeach; ?>

function addTicket(flightId, unitPrice) {
    const container = document.getElementById('ticketItems' + flightId);
    
    // Remove empty state if exists
    const emptyState = document.getElementById('emptyState' + flightId);
    if (emptyState) {
        emptyState.remove();
    }
    
    const index = ticketCounters[flightId]++;
    const num = container.querySelectorAll('.ticket-row').length + 1;
    
    const template = document.getElementById('ticketRowTemplate').innerHTML;
    const html = template
        .replace(/{flightId}/g, flightId)
        .replace(/{index}/g, index)
        .replace(/{num}/g, num)
        .replace(/{unitPrice}/g, unitPrice);
    
    container.insertAdjacentHTML('beforeend', html);
    
    // Focus on new row
    const newRow = document.getElementById('ticket-' + flightId + '-' + index);
    if (newRow) {
        newRow.querySelector('input[name*="[passenger_name]"]').focus();
    }
}

function removeTicket(rowId, flightId) {
    const row = document.getElementById(rowId);
    if (row) {
        row.remove();
        renumberTickets(flightId);
        
        // Show empty state if no tickets left
        const container = document.getElementById('ticketItems' + flightId);
        if (container.querySelectorAll('.ticket-row').length === 0) {
            container.innerHTML = `
                <div class="empty-state" id="emptyState${flightId}">
                    <i class="fas fa-ticket-alt"></i>
                    <p>Belum ada data tiket. Klik "Tambah Tiket" untuk mulai.</p>
                </div>
            `;
        }
    }
}

function renumberTickets(flightId) {
    const container = document.getElementById('ticketItems' + flightId);
    if (container) {
        container.querySelectorAll('.ticket-row').forEach((row, idx) => {
            row.querySelector('.ticket-num').textContent = 'Tiket ' + (idx + 1);
        });
    }
}

// Form validation
document.getElementById('flightGuestsForm').addEventListener('submit', function(e) {
    let hasTicket = false;
    document.querySelectorAll('input[name*="[passenger_name]"]').forEach(input => {
        if (input.value.trim()) {
            hasTicket = true;
        }
    });
    
    if (!hasTicket) {
        e.preventDefault();
        alert('Minimal satu tiket dengan nama penumpang harus diisi.');
        return false;
    }
});

// ========================================
// Flight Logo Upload Functions
// ========================================

const orderId = <?= $order->id ?>;
const csrfToken = '<?= function_exists('csrf_token') ? csrf_token() : (isset($_SESSION['csrf_token']) ? $_SESSION['csrf_token'] : '') ?>';

function uploadFlightLogo(flightItemId, input) {
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
    
    const progressEl = document.getElementById('uploadProgress' + flightItemId);
    const emptyEl = document.getElementById('logoEmpty' + flightItemId);
    const previewEl = document.getElementById('logoPreview' + flightItemId);
    
    if (progressEl) progressEl.style.display = 'block';
    if (emptyEl) emptyEl.style.display = 'none';
    if (previewEl) previewEl.style.display = 'none';
    
    const formData = new FormData();
    formData.append('logo', file);
    formData.append('flight_item_id', flightItemId);
    formData.append('csrf_token', csrfToken);
    
    fetch(`/orders/${orderId}/upload-flight-logo`, {
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
            updateFlightLogoUI(flightItemId, data.data.url);
        } else {
            alert(data.message || 'Gagal mengupload logo.');
            resetFlightLogoUI(flightItemId);
        }
    })
    .catch(error => {
        console.error('Upload error:', error);
        if (progressEl) progressEl.style.display = 'none';
        alert('Gagal mengupload logo. Error: ' + error.message);
        resetFlightLogoUI(flightItemId);
    });
}

function deleteFlightLogo(flightItemId) {
    if (!confirm('Hapus logo ini?')) return;
    
    fetch(`/orders/${orderId}/delete-flight-logo`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': csrfToken
        },
        body: JSON.stringify({ 
            flight_item_id: flightItemId,
            csrf_token: csrfToken 
        })
    })
    .then(response => {
        if (!response.ok) throw new Error('Server error: ' + response.status);
        return response.json();
    })
    .then(data => {
        if (data.success) {
            resetFlightLogoUI(flightItemId);
        } else {
            alert(data.message || 'Gagal menghapus logo.');
        }
    })
    .catch(error => {
        console.error('Delete error:', error);
        alert('Gagal menghapus logo.');
    });
}

function updateFlightLogoUI(flightItemId, url) {
    const uploadArea = document.getElementById('logoUpload' + flightItemId);
    uploadArea.innerHTML = `
        <div class="flight-logo-preview" id="logoPreview${flightItemId}">
            <img src="${url}" alt="Logo">
            <div class="flight-logo-actions">
                <label class="btn btn-xs btn-outline-primary" for="logoInput${flightItemId}">
                    <i class="fas fa-edit"></i> Ganti
                </label>
                <button type="button" class="btn btn-xs btn-outline-danger" onclick="deleteFlightLogo(${flightItemId})">
                    <i class="fas fa-trash"></i>
                </button>
            </div>
        </div>
        <input type="file" id="logoInput${flightItemId}" 
               accept="image/png,image/jpeg,image/gif" 
               style="display:none" 
               onchange="uploadFlightLogo(${flightItemId}, this)">
        <div class="upload-progress-mini" id="uploadProgress${flightItemId}" style="display:none">
            <div class="progress" style="height: 4px;">
                <div class="progress-bar progress-bar-striped progress-bar-animated" style="width: 100%"></div>
            </div>
        </div>
    `;
}

function resetFlightLogoUI(flightItemId) {
    const uploadArea = document.getElementById('logoUpload' + flightItemId);
    uploadArea.innerHTML = `
        <div class="flight-logo-empty" id="logoEmpty${flightItemId}">
            <i class="fas fa-cloud-upload-alt"></i>
            <span>Upload logo</span>
            <label class="btn btn-xs btn-primary" for="logoInput${flightItemId}">
                <i class="fas fa-upload"></i> Pilih
            </label>
        </div>
        <input type="file" id="logoInput${flightItemId}" 
               accept="image/png,image/jpeg,image/gif" 
               style="display:none" 
               onchange="uploadFlightLogo(${flightItemId}, this)">
        <div class="upload-progress-mini" id="uploadProgress${flightItemId}" style="display:none">
            <div class="progress" style="height: 4px;">
                <div class="progress-bar progress-bar-striped progress-bar-animated" style="width: 100%"></div>
            </div>
        </div>
    `;
}
</script>
