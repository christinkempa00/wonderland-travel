<?php
/**
 * Ledger (Buku Besar) View
 */
?>

<!-- Filters -->
<div class="glass-card mb-4">
    <form method="GET" action="<?= url('/accounting/ledger') ?>" class="d-flex gap-3 flex-wrap align-items-end">
        <div class="flex-1" style="min-width: 250px;">
            <label class="form-label">Akun</label>
            <select name="account_id" class="form-control" required>
                <option value="">-- Pilih Akun --</option>
                <?php foreach ($accounts as $id => $label): ?>
                <option value="<?= $id ?>" <?= $selectedAccount == $id ? 'selected' : '' ?>>
                    <?= e($label) ?>
                </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div style="min-width: 150px;">
            <label class="form-label">Dari Tanggal</label>
            <input type="date" name="start_date" class="form-control" value="<?= e($startDate) ?>">
        </div>
        <div style="min-width: 150px;">
            <label class="form-label">Sampai Tanggal</label>
            <input type="date" name="end_date" class="form-control" value="<?= e($endDate) ?>">
        </div>
        <button type="submit" class="btn btn-primary">
            <i class="fas fa-search"></i> Tampilkan
        </button>
    </form>
</div>

<?php if ($account): ?>
<!-- Account Info -->
<div class="glass-card mb-4">
    <div class="d-flex align-items-center justify-content-between">
        <div>
            <h4 class="mb-1">
                <code class="text-primary"><?= e($account->code) ?></code>
                <?= e($account->name) ?>
            </h4>
            <p class="text-muted mb-0">
                Periode: <?= formatDateIndo($startDate) ?> - <?= formatDateIndo($endDate) ?>
            </p>
        </div>
        <div class="text-right">
            <div class="text-sm text-muted">Saldo Normal</div>
            <div class="font-semibold"><?= $account->normal_balance === 'debit' ? 'Debit' : 'Kredit' ?></div>
        </div>
    </div>
</div>

<!-- Ledger Table -->
<div class="glass-card">
    <?php if (empty($transactions)): ?>
    <div class="text-center py-4 text-muted">
        <i class="fas fa-inbox fa-2x mb-2" style="opacity: 0.3;"></i>
        <p class="mb-0">Tidak ada transaksi pada periode ini.</p>
    </div>
    <?php else: ?>
    <div class="table-responsive">
        <table class="table">
            <thead>
                <tr>
                    <th width="100">Tanggal</th>
                    <th width="120">No. Jurnal</th>
                    <th>Keterangan</th>
                    <th class="text-right" width="130">Debit</th>
                    <th class="text-right" width="130">Kredit</th>
                    <th class="text-right" width="150">Saldo</th>
                </tr>
            </thead>
            <tbody>
                <!-- Opening Balance -->
                <tr class="table-secondary">
                    <td colspan="5"><strong>Saldo Awal</strong></td>
                    <td class="text-right font-semibold"><?= formatRupiah($openingBalance) ?></td>
                </tr>
                
                <?php 
                $runningBalance = $openingBalance;
                foreach ($transactions as $tx): 
                    // Use debit/credit columns directly from database
                    $debit = (float) ($tx['debit'] ?? 0);
                    $credit = (float) ($tx['credit'] ?? 0);
                    
                    if ($account->normal_balance === 'debit') {
                        $runningBalance += $debit - $credit;
                    } else {
                        $runningBalance += $credit - $debit;
                    }
                ?>
                <tr>
                    <td><?= formatDate($tx['journal_date']) ?></td>
                    <td>
                        <a href="<?= url('/accounting/journals/' . ($tx['journal_id'] ?? '')) ?>">
                            <?= e($tx['journal_number']) ?>
                        </a>
                    </td>
                    <td><?= e($tx['description'] ?: $tx['journal_desc']) ?></td>
                    <td class="text-right"><?= $debit > 0 ? formatRupiah($debit) : '-' ?></td>
                    <td class="text-right"><?= $credit > 0 ? formatRupiah($credit) : '-' ?></td>
                    <td class="text-right font-medium"><?= formatRupiah($runningBalance) ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
            <tfoot>
                <tr class="font-semibold" style="font-size: 1.05rem;">
                    <td colspan="5" class="text-right">Saldo Akhir</td>
                    <td class="text-right"><?= formatRupiah($runningBalance) ?></td>
                </tr>
            </tfoot>
        </table>
    </div>
    <?php endif; ?>
</div>
<?php else: ?>
<!-- No Account Selected -->
<div class="glass-card">
    <div class="empty-state">
        <div class="empty-state-icon">
            <i class="fas fa-book"></i>
        </div>
        <h4 class="empty-state-title">Pilih Akun</h4>
        <p class="empty-state-text">Pilih akun untuk melihat buku besar.</p>
    </div>
</div>
<?php endif; ?>
