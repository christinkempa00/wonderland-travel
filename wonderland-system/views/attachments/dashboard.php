<?php
/**
 * Attachment Dashboard View
 * Dashboard khusus untuk Staff Lampiran
 */

// Helper function
if (!function_exists('formatDateIndo')) {
    function formatDateIndo($date) {
        if (empty($date)) return '-';
        return date('d/m/Y', strtotime($date));
    }
}

if (!function_exists('truncateText')) {
    function truncateText($text, $length = 25) {
        if (strlen($text) <= $length) return $text;
        return substr($text, 0, $length) . '...';
    }
}
?>

<div class="attachment-dashboard">
    <!-- Header -->
    <div class="page-header mb-4">
        <div class="row align-items-center">
            <div class="col">
                <h1 class="page-title">
                    <i class="fas fa-clipboard-list text-primary"></i>
                    Dashboard Lampiran
                </h1>
                <p class="page-subtitle text-muted">Kelola data lampiran pesanan</p>
            </div>
        </div>
    </div>
    
    <!-- Stats Cards -->
    <div class="row g-3 mb-4">
        <div class="col-6 col-lg-3">
            <div class="stat-card">
                <div class="stat-icon bg-primary-soft">
                    <i class="fas fa-file-alt text-primary"></i>
                </div>
                <div class="stat-content">
                    <div class="stat-value"><?= $stats['total_orders'] ?? 0 ?></div>
                    <div class="stat-label">Total Pesanan</div>
                </div>
            </div>
        </div>
        
        <div class="col-6 col-lg-3">
            <div class="stat-card stat-card-link" onclick="setFilter('hotel')">
                <div class="stat-icon bg-warning-soft">
                    <i class="fas fa-hotel text-warning"></i>
                </div>
                <div class="stat-content">
                    <div class="stat-value"><?= $stats['pending_hotel'] ?? 0 ?></div>
                    <div class="stat-label">Hotel Pending</div>
                </div>
            </div>
        </div>
        
        <div class="col-6 col-lg-3">
            <div class="stat-card stat-card-link" onclick="setFilter('flight')">
                <div class="stat-icon bg-info-soft">
                    <i class="fas fa-plane text-info"></i>
                </div>
                <div class="stat-content">
                    <div class="stat-value"><?= $stats['pending_flight'] ?? 0 ?></div>
                    <div class="stat-label">Pesawat Pending</div>
                </div>
            </div>
        </div>
        
        <div class="col-6 col-lg-3">
            <div class="stat-card stat-card-link" onclick="setFilter('vehicle')">
                <div class="stat-icon bg-danger-soft">
                    <i class="fas fa-bus text-danger"></i>
                </div>
                <div class="stat-content">
                    <div class="stat-value"><?= $stats['pending_vehicle'] ?? 0 ?></div>
                    <div class="stat-label">Kendaraan Pending</div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Filter Tabs & Search -->
    <div class="glass-card mb-4">
        <div class="filter-section">
            <!-- Tab Filters -->
            <div class="filter-tabs mb-3">
                <button type="button" class="filter-tab <?= $filter === 'all' ? 'active' : '' ?>" onclick="setFilter('all')">
                    <i class="fas fa-list"></i> Semua
                </button>
                <button type="button" class="filter-tab <?= $filter === 'hotel' ? 'active' : '' ?>" onclick="setFilter('hotel')">
                    <i class="fas fa-hotel"></i> Hotel
                </button>
                <button type="button" class="filter-tab <?= $filter === 'flight' ? 'active' : '' ?>" onclick="setFilter('flight')">
                    <i class="fas fa-plane"></i> Pesawat
                </button>
                <button type="button" class="filter-tab <?= $filter === 'vehicle' ? 'active' : '' ?>" onclick="setFilter('vehicle')">
                    <i class="fas fa-bus"></i> Bus/Towing
                </button>
                <button type="button" class="filter-tab <?= $filter === 'rental' ? 'active' : '' ?>" onclick="setFilter('rental')">
                    <i class="fas fa-car"></i> Rental
                </button>
            </div>
            
            <!-- Search & Dukungan Filter -->
            <form method="get" class="filter-form">
                <input type="hidden" name="filter" value="<?= htmlspecialchars($filter) ?>">
                <div class="row g-2 align-items-end">
                    <div class="col-md-5">
                        <label class="form-label small text-muted">Cari Pesanan</label>
                        <input type="text" name="search" class="form-control" 
                               placeholder="No. pesanan, event, atau klien..."
                               value="<?= htmlspecialchars($search ?? '') ?>">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label small text-muted">Filter Dukungan</label>
                        <select name="support_for" class="form-control">
                            <option value="">Semua Dukungan</option>
                            <?php if (!empty($supportForOptions)): ?>
                            <?php foreach ($supportForOptions as $support): ?>
                            <option value="<?= htmlspecialchars($support) ?>" <?= ($supportFor ?? '') === $support ? 'selected' : '' ?>>
                                <?= htmlspecialchars(truncateText($support, 30)) ?>
                            </option>
                            <?php endforeach; ?>
                            <?php endif; ?>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <div class="d-flex gap-2">
                            <button type="submit" class="btn btn-primary flex-grow-1">
                                <i class="fas fa-filter"></i> Filter
                            </button>
                            <?php if (!empty($search) || !empty($supportFor) || $filter !== 'all'): ?>
                            <a href="<?= url('/attachment-dashboard') ?>" class="btn btn-outline-secondary">
                                <i class="fas fa-times"></i>
                            </a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>
    
    <!-- Active Filters Info -->
    <?php if ($filter !== 'all' || !empty($supportFor)): ?>
    <div class="alert alert-info mb-4 d-flex align-items-center justify-content-between flex-wrap gap-2">
        <span>
            <i class="fas fa-filter"></i>
            Filter aktif: 
            <?php if ($filter !== 'all'): ?>
                <strong>Tipe: <?= htmlspecialchars($filterLabel ?? ucfirst($filter)) ?></strong>
            <?php endif; ?>
            <?php if (!empty($supportFor)): ?>
                <?php if ($filter !== 'all'): ?> | <?php endif; ?>
                <strong>Dukungan: <?= htmlspecialchars(truncateText($supportFor, 25)) ?></strong>
            <?php endif; ?>
        </span>
        <a href="<?= url('/attachment-dashboard') ?>" class="btn btn-sm btn-outline-info">
            <i class="fas fa-times"></i> Reset Filter
        </a>
    </div>
    <?php endif; ?>
    
    <!-- Orders List -->
    <div class="glass-card">
        <?php if (empty($orders)): ?>
        <div class="empty-state py-5">
            <i class="fas fa-inbox fa-3x text-muted mb-3"></i>
            <h5>Tidak Ada Pesanan</h5>
            <p class="text-muted">Tidak ditemukan pesanan yang memerlukan input lampiran.</p>
        </div>
        <?php else: ?>
        
        <div class="table-responsive">
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th>No. Pesanan</th>
                        <th>Klien / Event</th>
                        <th>Dukungan</th>
                        <th>Tanggal</th>
                        <th class="text-center">Jenis Lampiran</th>
                        <th class="text-center">Status</th>
                        <th class="text-center">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($orders as $order): ?>
                    <tr>
                        <td>
                            <strong><?= htmlspecialchars($order['order_number'] ?? '') ?></strong>
                        </td>
                        <td>
                            <div><?= htmlspecialchars($order['client_name'] ?? '-') ?></div>
                            <?php if (!empty($order['event_name'])): ?>
                            <small class="text-muted"><?= htmlspecialchars(truncateText($order['event_name'], 25)) ?></small>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if (!empty($order['support_for'])): ?>
                            <span class="support-badge" title="<?= htmlspecialchars($order['support_for']) ?>">
                                <i class="fas fa-hands-helping"></i>
                                <?= htmlspecialchars(truncateText($order['support_for'], 20)) ?>
                            </span>
                            <?php else: ?>
                            <span class="text-muted">-</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if (!empty($order['event_date'])): ?>
                            <?= formatDateIndo($order['event_date']) ?>
                            <?php if (!empty($order['event_end_date']) && $order['event_end_date'] !== $order['event_date']): ?>
                            <br><small class="text-muted">s/d <?= formatDateIndo($order['event_end_date']) ?></small>
                            <?php endif; ?>
                            <?php else: ?>
                            <span class="text-muted">-</span>
                            <?php endif; ?>
                        </td>
                        <td class="text-center">
                            <!-- Attachment Type Badges -->
                            <div class="attachment-badges">
                                <?php if (!empty($order['attachment_status']['hotel']['required'])): ?>
                                <span class="badge-attachment <?= !empty($order['attachment_status']['hotel']['filled']) ? 'filled' : 'pending' ?>" 
                                      title="Hotel: <?= !empty($order['attachment_status']['hotel']['filled']) ? 'Sudah diisi' : 'Belum diisi' ?>">
                                    <i class="fas fa-hotel"></i>
                                </span>
                                <?php endif; ?>
                                
                                <?php if (!empty($order['attachment_status']['flight']['required'])): ?>
                                <span class="badge-attachment <?= !empty($order['attachment_status']['flight']['filled']) ? 'filled' : 'pending' ?>"
                                      title="Pesawat: <?= !empty($order['attachment_status']['flight']['filled']) ? 'Sudah diisi' : 'Belum diisi' ?>">
                                    <i class="fas fa-plane"></i>
                                </span>
                                <?php endif; ?>
                                
                                <?php if (!empty($order['attachment_status']['vehicle']['required'])): ?>
                                <span class="badge-attachment <?= !empty($order['attachment_status']['vehicle']['filled']) ? 'filled' : 'pending' ?>"
                                      title="Kendaraan: <?= !empty($order['attachment_status']['vehicle']['filled']) ? 'Sudah diisi' : 'Belum diisi' ?>">
                                    <i class="fas fa-bus"></i>
                                </span>
                                <?php endif; ?>
                                
                                <?php if (!empty($order['attachment_status']['rental']['required'])): ?>
                                <span class="badge-attachment <?= !empty($order['attachment_status']['rental']['filled']) ? 'filled' : 'pending' ?>"
                                      title="Rental: <?= !empty($order['attachment_status']['rental']['filled']) ? 'Sudah diisi' : 'Belum diisi' ?>">
                                    <i class="fas fa-car"></i>
                                </span>
                                <?php endif; ?>
                            </div>
                        </td>
                        <td class="text-center">
                            <?php
                            $allFilled = true;
                            $anyRequired = false;
                            if (!empty($order['attachment_status'])) {
                                foreach ($order['attachment_status'] as $type => $status) {
                                    if (!empty($status['required'])) {
                                        $anyRequired = true;
                                        if (empty($status['filled'])) {
                                            $allFilled = false;
                                        }
                                    }
                                }
                            }
                            ?>
                            <?php if ($allFilled && $anyRequired): ?>
                            <span class="badge bg-success">
                                <i class="fas fa-check"></i> Lengkap
                            </span>
                            <?php else: ?>
                            <span class="badge bg-warning text-dark">
                                <i class="fas fa-clock"></i> Pending
                            </span>
                            <?php endif; ?>
                        </td>
                        <td class="text-center">
                            <a href="<?= url('/attachment-order/' . $order['id']) ?>" 
                               class="btn btn-sm btn-primary" title="Input Lampiran">
                                <i class="fas fa-edit"></i> Kelola
                            </a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        
        <!-- Pagination -->
        <?php if (!empty($pagination) && $pagination['last_page'] > 1): ?>
        <div class="d-flex justify-content-between align-items-center mt-4 pt-3 border-top">
            <div class="text-muted">
                Menampilkan <?= $pagination['from'] ?> - <?= $pagination['to'] ?> dari <?= $pagination['total'] ?> pesanan
            </div>
            <nav>
                <ul class="pagination pagination-sm mb-0">
                    <?php if ($pagination['current_page'] > 1): ?>
                    <li class="page-item">
                        <a class="page-link" href="?page=<?= $pagination['current_page'] - 1 ?>&filter=<?= $filter ?>&search=<?= urlencode($search ?? '') ?>&support_for=<?= urlencode($supportFor ?? '') ?>">
                            <i class="fas fa-chevron-left"></i>
                        </a>
                    </li>
                    <?php endif; ?>
                    
                    <?php for ($i = max(1, $pagination['current_page'] - 2); $i <= min($pagination['last_page'], $pagination['current_page'] + 2); $i++): ?>
                    <li class="page-item <?= $i === $pagination['current_page'] ? 'active' : '' ?>">
                        <a class="page-link" href="?page=<?= $i ?>&filter=<?= $filter ?>&search=<?= urlencode($search ?? '') ?>&support_for=<?= urlencode($supportFor ?? '') ?>"><?= $i ?></a>
                    </li>
                    <?php endfor; ?>
                    
                    <?php if ($pagination['current_page'] < $pagination['last_page']): ?>
                    <li class="page-item">
                        <a class="page-link" href="?page=<?= $pagination['current_page'] + 1 ?>&filter=<?= $filter ?>&search=<?= urlencode($search ?? '') ?>&support_for=<?= urlencode($supportFor ?? '') ?>">
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

<style>
/* Stat Cards */
.stat-card {
    background: white;
    border-radius: 12px;
    padding: 20px;
    display: flex;
    align-items: center;
    gap: 16px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.06);
    transition: all 0.2s;
    text-decoration: none;
    color: inherit;
}

.stat-card-link {
    cursor: pointer;
}

.stat-card-link:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0,0,0,0.1);
}

.stat-icon {
    width: 48px;
    height: 48px;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.25rem;
}

.bg-primary-soft { background: rgba(59, 130, 246, 0.1); }
.bg-warning-soft { background: rgba(245, 158, 11, 0.1); }
.bg-info-soft { background: rgba(6, 182, 212, 0.1); }
.bg-danger-soft { background: rgba(239, 68, 68, 0.1); }
.bg-success-soft { background: rgba(34, 197, 94, 0.1); }

.stat-value {
    font-size: 1.5rem;
    font-weight: 700;
    color: #1f2937;
}

.stat-label {
    font-size: 0.8rem;
    color: #6b7280;
}

/* Filter Tabs */
.filter-tabs {
    display: flex;
    gap: 0.5rem;
    flex-wrap: wrap;
}

.filter-tab {
    padding: 0.5rem 1rem;
    border-radius: 25px;
    font-size: 0.85rem;
    border: 1px solid #e5e7eb;
    background: white;
    color: #6b7280;
    cursor: pointer;
    transition: all 0.2s;
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
}

.filter-tab:hover {
    border-color: #3b82f6;
    color: #3b82f6;
    background: #eff6ff;
}

.filter-tab.active {
    background: #3b82f6;
    border-color: #3b82f6;
    color: white;
}

.filter-tab i {
    font-size: 0.8rem;
}

/* Support Badge */
.support-badge {
    display: inline-flex;
    align-items: center;
    gap: 0.3rem;
    background: linear-gradient(135deg, #dbeafe, #bfdbfe);
    color: #1e40af;
    padding: 0.25rem 0.5rem;
    border-radius: 4px;
    font-size: 0.75rem;
    font-weight: 500;
}

.support-badge i {
    font-size: 0.65rem;
}

/* Attachment Badges */
.attachment-badges {
    display: flex;
    gap: 6px;
    justify-content: center;
    flex-wrap: wrap;
}

.badge-attachment {
    width: 28px;
    height: 28px;
    border-radius: 6px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    font-size: 0.75rem;
    cursor: help;
}

.badge-attachment.filled {
    background: rgba(34, 197, 94, 0.15);
    color: #16a34a;
}

.badge-attachment.pending {
    background: rgba(245, 158, 11, 0.15);
    color: #d97706;
}

/* Empty State */
.empty-state {
    text-align: center;
    padding: 40px 20px;
}

.empty-state i {
    opacity: 0.3;
}

/* Table */
.table th {
    font-weight: 600;
    font-size: 0.8rem;
    text-transform: uppercase;
    color: #6b7280;
    border-bottom: 2px solid #e5e7eb;
}

.table td {
    vertical-align: middle;
    padding: 16px 12px;
}

@media (max-width: 768px) {
    .filter-tabs {
        overflow-x: auto;
        flex-wrap: nowrap;
        padding-bottom: 0.5rem;
    }
    
    .filter-tab {
        white-space: nowrap;
        flex-shrink: 0;
    }
    
    .filter-form .row {
        flex-direction: column;
    }
}
</style>

<script>
function setFilter(type) {
    const currentUrl = new URL(window.location.href);
    currentUrl.searchParams.set('filter', type);
    currentUrl.searchParams.delete('page');
    window.location.href = currentUrl.toString();
}
</script>