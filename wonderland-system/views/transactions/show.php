<?php
/**
 * Transaction Show View
 * File: /views/transactions/show.php
 */

$isExpense = $transaction['transaction_type'] === 'expense';
?>

<div class="row">
    <div class="col-lg-8">
        <!-- Main Info -->
        <div class="glass-card mb-4">
            <div class="d-flex justify-content-between align-items-start mb-4">
                <div class="d-flex align-items-center">
                    <div class="icon-box <?= $isExpense ? 'bg-danger' : 'bg-success' ?> bg-opacity-10 me-3">
                        <i class="fas fa-<?= $isExpense ? 'minus' : 'plus' ?>-circle text-<?= $isExpense ? 'danger' : 'success' ?> fa-2x"></i>
                    </div>
                    <div>
                        <h4 class="mb-1"><?= e($transaction['transaction_number']) ?></h4>
                        <span class="badge bg-<?= $isExpense ? 'danger' : 'success' ?>">
                            <?= $isExpense ? 'Pengeluaran' : 'Pemasukan' ?>
                        </span>
                        <?php if ($transaction['status'] === 'void'): ?>
                        <span class="badge bg-secondary ms-1">Dibatalkan</span>
                        <?php endif; ?>
                    </div>
                </div>
                
                <div class="text-end">
                    <h3 class="text-<?= $isExpense ? 'danger' : 'success' ?> mb-0">
                        <?= $isExpense ? '-' : '+' ?> <?= formatRupiah($transaction['amount']) ?>
                    </h3>
                    <small class="text-muted"><?= formatDateIndo($transaction['transaction_date'], true) ?></small>
                </div>
            </div>
            
            <hr>
            
            <div class="row">
                <div class="col-md-6">
                    <table class="table table-borderless table-sm mb-0">
                        <tr>
                            <td class="text-muted" width="40%">Akun</td>
                            <td>
                                <strong><?= e($transaction['account_code']) ?></strong><br>
                                <?= e($transaction['account_name']) ?>
                            </td>
                        </tr>
                        <tr>
                            <td class="text-muted">Kas/Bank</td>
                            <td><?= e($transaction['bank_cash_name'] ?? '-') ?></td>
                        </tr>
                        <tr>
                            <td class="text-muted">Kategori</td>
                            <td><?= e($transaction['category'] ?? '-') ?></td>
                        </tr>
                    </table>
                </div>
                <div class="col-md-6">
                    <table class="table table-borderless table-sm mb-0">
                        <tr>
                            <td class="text-muted" width="40%">No. Jurnal</td>
                            <td>
                                <?php if ($transaction['journal_number']): ?>
                                <a href="<?= url('/accounting/journals/' . $transaction['journal_id']) ?>">
                                    <?= e($transaction['journal_number']) ?>
                                </a>
                                <?php else: ?>
                                -
                                <?php endif; ?>
                            </td>
                        </tr>
                        <tr>
                            <td class="text-muted">Referensi</td>
                            <td><?= e($transaction['reference'] ?? '-') ?></td>
                        </tr>
                        <tr>
                            <td class="text-muted">Dibuat</td>
                            <td><?= formatDateTime($transaction['created_at']) ?></td>
                        </tr>
                    </table>
                </div>
            </div>
            
            <?php if ($transaction['description']): ?>
            <hr>
            <h6 class="text-muted mb-2">Deskripsi</h6>
            <p class="mb-0"><?= nl2br(e($transaction['description'])) ?></p>
            <?php endif; ?>
        </div>
        
        <!-- Journal Details -->
        <?php if (!empty($journalDetails)): ?>
        <div class="glass-card">
            <h5 class="card-title mb-3">
                <i class="fas fa-book text-primary me-2"></i>
                Detail Jurnal
            </h5>
            
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Akun</th>
                            <th class="text-end">Debit</th>
                            <th class="text-end">Credit</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        $totalDebit = 0;
                        $totalCredit = 0;
                        foreach ($journalDetails as $detail): 
                            $totalDebit += $detail['debit'];
                            $totalCredit += $detail['credit'];
                        ?>
                        <tr>
                            <td>
                                <strong><?= e($detail['account_code']) ?></strong> - <?= e($detail['account_name']) ?>
                                <?php if ($detail['description']): ?>
                                <br><small class="text-muted"><?= e($detail['description']) ?></small>
                                <?php endif; ?>
                            </td>
                            <td class="text-end">
                                <?= $detail['debit'] > 0 ? formatRupiah($detail['debit']) : '-' ?>
                            </td>
                            <td class="text-end">
                                <?= $detail['credit'] > 0 ? formatRupiah($detail['credit']) : '-' ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                    <tfoot>
                        <tr class="table-light fw-bold">
                            <td>Total</td>
                            <td class="text-end"><?= formatRupiah($totalDebit) ?></td>
                            <td class="text-end"><?= formatRupiah($totalCredit) ?></td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
        <?php endif; ?>
    </div>
    
    <!-- Sidebar Actions -->
    <div class="col-lg-4">
        <div class="glass-card">
            <h5 class="card-title mb-3">
                <i class="fas fa-cog text-primary me-2"></i>
                Aksi
            </h5>
            
            <div class="d-grid gap-2">
                <?php if ($transaction['status'] !== 'void'): ?>
                <form method="POST" action="<?= url('/transactions/' . $transaction['id'] . '/void') ?>"
                      onsubmit="return confirm('Yakin batalkan transaksi ini? Jurnal terkait akan di-void.');">
                    <?= csrfField() ?>
                    <button type="submit" class="btn btn-danger w-100">
                        <i class="fas fa-times"></i> Batalkan Transaksi
                    </button>
                </form>
                <?php endif; ?>
                
                <a href="<?= url('/transactions') ?>" class="btn btn-secondary">
                    <i class="fas fa-arrow-left"></i> Kembali ke Daftar
                </a>
                
                <a href="<?= url('/transactions/create?type=' . $transaction['transaction_type']) ?>" class="btn btn-outline-primary">
                    <i class="fas fa-plus"></i> Buat Transaksi Baru
                </a>
            </div>
        </div>
    </div>
</div>

<style>
.icon-box {
    width: 60px;
    height: 60px;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
}
</style>
