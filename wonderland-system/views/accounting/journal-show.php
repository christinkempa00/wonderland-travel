<?php
/**
 * Journal Detail View
 * File: /views/accounting/journals/show.php
 */

$statusColors = [
    'draft' => 'warning', 
    'posted' => 'success', 
    'void' => 'secondary'
];
$statusLabels = [
    'draft' => 'Draft', 
    'posted' => 'Posted', 
    'void' => 'Void'
];
$statusIcons = [
    'draft' => 'edit',
    'posted' => 'check-circle',
    'void' => 'ban'
];

$status = $journal['status'] ?? 'draft';
?>

<div class="row">
    <!-- Left: Journal Details -->
    <div class="col-lg-8">
        <div class="glass-card mb-4">
            <!-- Header -->
            <div class="d-flex align-items-start justify-content-between mb-4">
                <div>
                    <div class="d-flex align-items-center gap-2 mb-2">
                        <h4 class="mb-0"><?= e($journal['journal_number']) ?></h4>
                        <span class="badge bg-<?= $statusColors[$status] ?>">
                            <i class="fas fa-<?= $statusIcons[$status] ?> me-1"></i>
                            <?= $statusLabels[$status] ?>
                        </span>
                    </div>
                    <p class="text-muted mb-0">
                        <i class="fas fa-calendar me-1"></i>
                        <?= formatDate($journal['journal_date'], 'd F Y') ?>
                        <?php if (!empty($journal['reference'])): ?>
                        <span class="ms-3">
                            <i class="fas fa-file-alt me-1"></i>
                            Ref: <?= e($journal['reference']) ?>
                        </span>
                        <?php endif; ?>
                    </p>
                </div>
            </div>
            
            <?php if (!empty($journal['description'])): ?>
            <div class="mb-4 p-3 bg-light rounded">
                <label class="text-muted small d-block mb-1">Keterangan</label>
                <p class="mb-0"><?= nl2br(e($journal['description'])) ?></p>
            </div>
            <?php endif; ?>
            
            <!-- Journal Details Table -->
            <div class="table-responsive">
                <table class="table table-bordered mb-0">
                    <thead class="table-light">
                        <tr>
                            <th width="120">Kode</th>
                            <th>Nama Akun</th>
                            <th>Keterangan</th>
                            <th width="140" class="text-end">Debit</th>
                            <th width="140" class="text-end">Kredit</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        $totalDebit = 0;
                        $totalCredit = 0;
                        
                        if (!empty($details)):
                            foreach ($details as $detail): 
                                $debit = (float)($detail['debit'] ?? 0);
                                $credit = (float)($detail['credit'] ?? 0);
                                $totalDebit += $debit;
                                $totalCredit += $credit;
                        ?>
                        <tr>
                            <td>
                                <code class="bg-light px-2 py-1 rounded"><?= e($detail['account_code'] ?? '-') ?></code>
                            </td>
                            <td class="fw-medium"><?= e($detail['account_name'] ?? '-') ?></td>
                            <td class="text-muted"><?= e($detail['description'] ?: '-') ?></td>
                            <td class="text-end <?= $debit > 0 ? 'text-primary fw-medium' : 'text-muted' ?>">
                                <?= $debit > 0 ? formatRupiah($debit) : '-' ?>
                            </td>
                            <td class="text-end <?= $credit > 0 ? 'text-info fw-medium' : 'text-muted' ?>">
                                <?= $credit > 0 ? formatRupiah($credit) : '-' ?>
                            </td>
                        </tr>
                        <?php 
                            endforeach;
                        else:
                        ?>
                        <tr>
                            <td colspan="5" class="text-center text-muted py-4">
                                <i class="fas fa-inbox fa-2x mb-2 d-block opacity-50"></i>
                                Tidak ada detail jurnal
                            </td>
                        </tr>
                        <?php endif; ?>
                    </tbody>
                    <tfoot class="table-light">
                        <tr class="fw-bold">
                            <td colspan="3" class="text-end">TOTAL</td>
                            <td class="text-end text-primary"><?= formatRupiah($totalDebit) ?></td>
                            <td class="text-end text-info"><?= formatRupiah($totalCredit) ?></td>
                        </tr>
                        <?php if (abs($totalDebit - $totalCredit) > 0.01): ?>
                        <tr class="text-danger">
                            <td colspan="3" class="text-end">SELISIH</td>
                            <td colspan="2" class="text-end">
                                <i class="fas fa-exclamation-triangle me-1"></i>
                                <?= formatRupiah(abs($totalDebit - $totalCredit)) ?>
                            </td>
                        </tr>
                        <?php endif; ?>
                    </tfoot>
                </table>
            </div>
        </div>
        
        <!-- Print View -->
        <div class="glass-card d-none d-print-block">
            <div class="text-center mb-4">
                <h5>JURNAL UMUM</h5>
                <p class="mb-0"><?= e($journal['journal_number']) ?> - <?= formatDate($journal['journal_date'], 'd F Y') ?></p>
            </div>
        </div>
    </div>
    
    <!-- Right: Actions & Info -->
    <div class="col-lg-4">
        <!-- Actions Card -->
        <div class="glass-card mb-4">
            <h6 class="mb-3">
                <i class="fas fa-cog text-primary me-2"></i>
                Aksi
            </h6>
            
            <div class="d-grid gap-2">
                <?php if ($status === 'draft'): ?>
                    <a href="<?= url('/accounting/journals/' . $journal['id'] . '/edit') ?>" class="btn btn-primary">
                        <i class="fas fa-edit me-1"></i> Edit Jurnal
                    </a>
                    <button type="button" class="btn btn-success" id="postBtn">
                        <i class="fas fa-check me-1"></i> Posting Jurnal
                    </button>
                    <button type="button" class="btn btn-outline-danger" id="deleteBtn">
                        <i class="fas fa-trash me-1"></i> Hapus
                    </button>
                <?php elseif ($status === 'posted'): ?>
                    <button type="button" class="btn btn-warning" id="voidBtn">
                        <i class="fas fa-ban me-1"></i> Void Jurnal
                    </button>
                    <button type="button" class="btn btn-outline-primary" onclick="window.print()">
                        <i class="fas fa-print me-1"></i> Cetak
                    </button>
                <?php else: ?>
                    <div class="alert alert-secondary mb-0">
                        <i class="fas fa-info-circle me-1"></i>
                        Jurnal ini sudah di-void dan tidak dapat diubah.
                    </div>
                <?php endif; ?>
                
                <a href="<?= url('/accounting/journals') ?>" class="btn btn-secondary">
                    <i class="fas fa-arrow-left me-1"></i> Kembali
                </a>
            </div>
        </div>
        
        <!-- Info Card -->
        <div class="glass-card">
            <h6 class="mb-3">
                <i class="fas fa-info-circle text-primary me-2"></i>
                Informasi
            </h6>
            
            <table class="table table-sm table-borderless mb-0">
                <tr>
                    <td class="text-muted" width="40%">Tipe</td>
                    <td><?= e(ucfirst($journal['journal_type'] ?? 'general')) ?></td>
                </tr>
                <tr>
                    <td class="text-muted">Dibuat</td>
                    <td><?= formatDate($journal['created_at'] ?? '', 'd/m/Y H:i') ?></td>
                </tr>
                <?php if (!empty($journal['created_by_name'])): ?>
                <tr>
                    <td class="text-muted">Oleh</td>
                    <td><?= e($journal['created_by_name']) ?></td>
                </tr>
                <?php endif; ?>
                <?php if ($status === 'posted' && !empty($journal['posted_at'])): ?>
                <tr>
                    <td class="text-muted">Diposting</td>
                    <td><?= formatDate($journal['posted_at'], 'd/m/Y H:i') ?></td>
                </tr>
                <?php endif; ?>
                <?php if ($status === 'void' && !empty($journal['voided_at'])): ?>
                <tr>
                    <td class="text-muted">Di-void</td>
                    <td><?= formatDate($journal['voided_at'], 'd/m/Y H:i') ?></td>
                </tr>
                <?php endif; ?>
            </table>
        </div>
    </div>
</div>

<script>
var csrfToken = '<?= Session::getCsrfToken() ?>';
var journalId = <?= $journal['id'] ?>;

<?php if ($status === 'draft'): ?>
// Post Journal
document.getElementById('postBtn')?.addEventListener('click', function() {
    if (!confirm('Posting jurnal ini?\n\nJurnal yang sudah diposting tidak dapat diedit lagi.')) return;
    
    this.disabled = true;
    this.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i> Memproses...';
    
    fetch('<?= url('/accounting/journals/' . $journal['id'] . '/post') ?>', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': csrfToken,
            'X-Requested-With': 'XMLHttpRequest'
        }
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            showToast(data.message || 'Jurnal berhasil diposting', 'success');
            setTimeout(() => location.reload(), 1000);
        } else {
            showToast(data.message || 'Gagal posting jurnal', 'error');
            this.disabled = false;
            this.innerHTML = '<i class="fas fa-check me-1"></i> Posting Jurnal';
        }
    })
    .catch(err => {
        showToast('Terjadi kesalahan', 'error');
        this.disabled = false;
        this.innerHTML = '<i class="fas fa-check me-1"></i> Posting Jurnal';
    });
});

// Delete Journal
document.getElementById('deleteBtn')?.addEventListener('click', function() {
    if (!confirm('Hapus jurnal ini?\n\nAksi ini tidak dapat dibatalkan.')) return;
    
    this.disabled = true;
    
    fetch('<?= url('/accounting/journals/' . $journal['id']) ?>', {
        method: 'DELETE',
        headers: {
            'X-CSRF-TOKEN': csrfToken,
            'X-Requested-With': 'XMLHttpRequest'
        }
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            showToast(data.message || 'Jurnal berhasil dihapus', 'success');
            setTimeout(() => window.location.href = '<?= url('/accounting/journals') ?>', 1000);
        } else {
            showToast(data.message || 'Gagal menghapus jurnal', 'error');
            this.disabled = false;
        }
    });
});
<?php endif; ?>

<?php if ($status === 'posted'): ?>
// Void Journal
document.getElementById('voidBtn')?.addEventListener('click', function() {
    if (!confirm('Void jurnal ini?\n\nJurnal yang di-void tidak dapat diubah atau digunakan kembali.')) return;
    
    this.disabled = true;
    this.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i> Memproses...';
    
    fetch('<?= url('/accounting/journals/' . $journal['id'] . '/void') ?>', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': csrfToken,
            'X-Requested-With': 'XMLHttpRequest'
        }
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            showToast(data.message || 'Jurnal berhasil di-void', 'success');
            setTimeout(() => location.reload(), 1000);
        } else {
            showToast(data.message || 'Gagal void jurnal', 'error');
            this.disabled = false;
            this.innerHTML = '<i class="fas fa-ban me-1"></i> Void Jurnal';
        }
    });
});
<?php endif; ?>

// Toast helper (fallback jika tidak ada)
function showToast(message, type) {
    if (typeof Toast !== 'undefined') {
        type === 'success' ? Toast.success('Berhasil', message) : Toast.error('Gagal', message);
    } else {
        alert(message);
    }
}
</script>

<style>
@media print {
    .btn, .glass-card:not(.d-print-block) { display: none !important; }
    .col-lg-8 { width: 100% !important; max-width: 100% !important; }
}
</style>