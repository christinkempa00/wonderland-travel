<?php
/**
 * Chart of Accounts View
 */

// Helper function for account type color
if (!function_exists('getAccountTypeColor')) {
    function getAccountTypeColor(string $type): string {
        $colors = [
            'asset' => 'primary',
            'liability' => 'warning',
            'equity' => 'info',
            'revenue' => 'success',
            'expense' => 'danger'
        ];
        return $colors[$type] ?? 'secondary';
    }
}
?>

<!-- Account Type Tabs -->
<div class="glass-card mb-4">
    <div class="account-tabs">
        <button class="tab-btn active" data-type="all">Semua</button>
        <?php foreach ($accountTypes as $type => $typeData): ?>
        <button class="tab-btn" data-type="<?= $type ?>"><?= $typeData['label'] ?? $type ?></button>
        <?php endforeach; ?>
    </div>
</div>

<!-- Accounts List -->
<div class="glass-card">
    <div class="table-responsive">
        <table class="table" id="accountsTable">
            <thead>
                <tr>
                    <th width="120">Kode</th>
                    <th>Nama Akun</th>
                    <th width="120">Tipe</th>
                    <th width="100">Saldo Normal</th>
                    <th width="80">Kas/Bank</th>
                    <th width="80">Status</th>
                    <th width="100">Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($groupedAccounts as $type => $typeAccounts): ?>
                    <?php foreach ($typeAccounts as $account): ?>
                    <tr data-type="<?= $type ?>">
                        <td>
                            <code class="text-primary"><?= e($account['code']) ?></code>
                        </td>
                        <td>
                            <?php if ($account['parent_id']): ?>
                            <span class="text-muted ms-3">└─</span>
                            <?php endif; ?>
                            <?= e($account['name']) ?>
                        </td>
                        <td>
                            <span class="badge badge-<?= getAccountTypeColor($type) ?>">
                                <?= $accountTypes[$type]['label'] ?? $type ?>
                            </span>
                        </td>
                        <td class="text-center">
                            <?= $account['normal_balance'] === 'debit' ? 'Debit' : 'Kredit' ?>
                        </td>
                        <td class="text-center">
                            <?php if ($account['is_cash_bank']): ?>
                            <i class="fas fa-check text-success"></i>
                            <?php else: ?>
                            <i class="fas fa-minus text-muted"></i>
                            <?php endif; ?>
                        </td>
                        <td class="text-center">
                            <?php if ($account['is_active']): ?>
                            <span class="badge badge-success">Aktif</span>
                            <?php else: ?>
                            <span class="badge badge-secondary">Nonaktif</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <div class="btn-group">
                                <?php if (Session::can('accounting.update')): ?>
                                <a href="<?= url('/accounting/accounts/' . $account['id'] . '/edit') ?>" 
                                   class="btn btn-sm btn-icon btn-secondary" title="Edit">
                                    <i class="fas fa-edit"></i>
                                </a>
                                <?php endif; ?>
                                <a href="<?= url('/accounting/ledger?account_id=' . $account['id']) ?>"
                                   class="btn btn-sm btn-icon btn-secondary" title="Buku Besar">
                                    <i class="fas fa-book"></i>
                                </a>
                                <?php if (Session::can('accounting.delete')): ?>
                                <button type="button" class="btn btn-sm btn-icon btn-secondary text-danger"
                                        title="Hapus"
                                        data-delete="<?= url('/accounting/accounts/' . $account['id']) ?>"
                                        data-message="Yakin ingin menghapus akun <?= e($account['name']) ?>?">
                                    <i class="fas fa-trash"></i>
                                </button>
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<style>
.account-tabs {
    display: flex;
    gap: 0.5rem;
    flex-wrap: wrap;
}

.tab-btn {
    padding: 0.5rem 1rem;
    border: 1px solid var(--gray-300);
    background: white;
    border-radius: var(--border-radius-sm);
    font-size: var(--font-size-sm);
    cursor: pointer;
    transition: all 0.2s ease;
}

.tab-btn:hover {
    border-color: var(--primary-500);
    color: var(--primary-600);
}

.tab-btn.active {
    background: var(--primary-500);
    border-color: var(--primary-500);
    color: white;
}
</style>

<script>
document.querySelectorAll('.tab-btn').forEach(btn => {
    btn.addEventListener('click', function() {
        // Update active tab
        document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
        this.classList.add('active');
        
        const type = this.dataset.type;
        
        // Filter rows
        document.querySelectorAll('#accountsTable tbody tr').forEach(row => {
            if (type === 'all' || row.dataset.type === type) {
                row.style.display = '';
            } else {
                row.style.display = 'none';
            }
        });
    });
});
</script>
