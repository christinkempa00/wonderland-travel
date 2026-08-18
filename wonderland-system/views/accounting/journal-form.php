<?php
/**
 * Journal Form View (Create/Edit)
 * File: /views/accounting/journals/form.php
 * 
 * Standar Akuntansi:
 * - Setiap jurnal HARUS balance (Total Debit = Total Credit)
 * - Minimal 2 baris (1 debit, 1 credit)
 * - Satu baris bisa debit ATAU credit, tidak boleh keduanya
 */

$isEdit = !empty($journal);
$journalData = $isEdit ? $journal : [
    'journal_date' => date('Y-m-d'),
    'description' => '',
    'reference' => ''
];

// Jika edit, ambil details
$existingDetails = $details ?? [];
?>

<form method="POST" action="<?= url($isEdit ? '/accounting/journals/' . $journal['id'] : '/accounting/journals') ?>" id="journalForm">
    <?= csrfField() ?>
    <?php if ($isEdit): ?>
    <input type="hidden" name="_method" value="PUT">
    <?php endif; ?>
    
    <div class="row">
        <!-- Left Column: Form Info -->
        <div class="col-lg-6">
            <div class="glass-card mb-4">
                <h5 class="mb-4">
                    <i class="fas fa-info-circle text-primary me-2"></i>
                    Informasi Jurnal
                </h5>
                
                <div class="row g-3">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label class="form-label">Tanggal <span class="text-danger">*</span></label>
                            <input type="date" name="journal_date" class="form-control" 
                                   value="<?= e(old('journal_date', $journalData['journal_date'])) ?>" required>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label class="form-label">No. Referensi</label>
                            <input type="text" name="reference" class="form-control" 
                                   value="<?= e(old('reference', $journalData['reference'] ?? '')) ?>"
                                   placeholder="No. bukti, nota, dll">
                        </div>
                    </div>
                    <div class="col-12">
                        <div class="form-group">
                            <label class="form-label">Keterangan</label>
                            <textarea name="description" class="form-control" rows="3" 
                                      placeholder="Keterangan jurnal..."><?= e(old('description', $journalData['description'])) ?></textarea>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Right Column: Summary -->
        <div class="col-lg-6">
            <div class="glass-card mb-4" style="position: sticky; top: 80px;">
                <h5 class="mb-4">
                    <i class="fas fa-calculator text-primary me-2"></i>
                    Ringkasan
                </h5>
                
                <table class="table table-borderless mb-0">
                    <tr>
                        <td class="text-muted">Total Debit</td>
                        <td class="text-end fw-bold text-primary" id="totalDebit">Rp 0</td>
                    </tr>
                    <tr>
                        <td class="text-muted">Total Kredit</td>
                        <td class="text-end fw-bold text-info" id="totalCredit">Rp 0</td>
                    </tr>
                    <tr class="border-top" id="balanceRow">
                        <td class="text-muted">Selisih</td>
                        <td class="text-end fw-bold" id="balance">Rp 0</td>
                    </tr>
                </table>
                
                <div class="alert alert-info mt-3 mb-0 small" id="balanceAlert">
                    <i class="fas fa-info-circle me-1"></i>
                    Jurnal harus balance (Debit = Kredit)
                </div>
                
                <div class="d-flex gap-2 mt-4">
                    <button type="submit" class="btn btn-primary flex-fill" id="submitBtn" disabled>
                        <i class="fas fa-save me-1"></i> 
                        <?= $isEdit ? 'Update Jurnal' : 'Simpan Jurnal' ?>
                    </button>
                    <a href="<?= url('/accounting/journals') ?>" class="btn btn-secondary">
                        Batal
                    </a>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Journal Details -->
    <div class="glass-card">
        <div class="d-flex align-items-center justify-content-between mb-4">
            <h5 class="mb-0">
                <i class="fas fa-list text-primary me-2"></i>
                Detail Jurnal
            </h5>
            <button type="button" class="btn btn-success btn-sm" id="addRowBtn">
                <i class="fas fa-plus me-1"></i> Tambah Baris
            </button>
        </div>
        
        <div class="table-responsive">
            <table class="table table-bordered" id="detailsTable">
                <thead class="table-light">
                    <tr>
                        <th width="30%">Akun <span class="text-danger">*</span></th>
                        <th>Keterangan</th>
                        <th width="150" class="text-center">Debit</th>
                        <th width="150" class="text-center">Kredit</th>
                        <th width="50"></th>
                    </tr>
                </thead>
                <tbody id="detailsBody">
                    <?php if (!empty($existingDetails)): ?>
                        <?php foreach ($existingDetails as $idx => $detail): ?>
                        <tr class="detail-row" data-index="<?= $idx ?>">
                            <td>
                                <select name="details[<?= $idx ?>][account_id]" class="form-control form-control-sm account-select" required>
                                    <option value="">-- Pilih Akun --</option>
                                    <?php foreach ($accounts as $id => $label): ?>
                                    <option value="<?= $id ?>" <?= $detail['account_id'] == $id ? 'selected' : '' ?>><?= e($label) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </td>
                            <td>
                                <input type="text" name="details[<?= $idx ?>][description]" class="form-control form-control-sm" 
                                       value="<?= e($detail['description'] ?? '') ?>" placeholder="Keterangan baris">
                            </td>
                            <td>
                                <input type="text" name="details[<?= $idx ?>][debit]" 
                                       class="form-control form-control-sm text-end debit-input" 
                                       value="<?= $detail['debit'] > 0 ? number_format($detail['debit'], 0, ',', '.') : '' ?>"
                                       placeholder="0" autocomplete="off">
                            </td>
                            <td>
                                <input type="text" name="details[<?= $idx ?>][credit]" 
                                       class="form-control form-control-sm text-end credit-input" 
                                       value="<?= $detail['credit'] > 0 ? number_format($detail['credit'], 0, ',', '.') : '' ?>"
                                       placeholder="0" autocomplete="off">
                            </td>
                            <td class="text-center">
                                <button type="button" class="btn btn-sm btn-outline-danger remove-row-btn" title="Hapus">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <!-- Default 2 rows: 1 debit, 1 credit -->
                        <tr class="detail-row" data-index="0">
                            <td>
                                <select name="details[0][account_id]" class="form-control form-control-sm account-select" required>
                                    <option value="">-- Pilih Akun --</option>
                                    <?php foreach ($accounts as $id => $label): ?>
                                    <option value="<?= $id ?>"><?= e($label) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </td>
                            <td>
                                <input type="text" name="details[0][description]" class="form-control form-control-sm" placeholder="Keterangan baris">
                            </td>
                            <td>
                                <input type="text" name="details[0][debit]" class="form-control form-control-sm text-end debit-input" placeholder="0" autocomplete="off">
                            </td>
                            <td>
                                <input type="text" name="details[0][credit]" class="form-control form-control-sm text-end credit-input" placeholder="0" autocomplete="off">
                            </td>
                            <td class="text-center">
                                <button type="button" class="btn btn-sm btn-outline-danger remove-row-btn" title="Hapus">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </td>
                        </tr>
                        <tr class="detail-row" data-index="1">
                            <td>
                                <select name="details[1][account_id]" class="form-control form-control-sm account-select" required>
                                    <option value="">-- Pilih Akun --</option>
                                    <?php foreach ($accounts as $id => $label): ?>
                                    <option value="<?= $id ?>"><?= e($label) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </td>
                            <td>
                                <input type="text" name="details[1][description]" class="form-control form-control-sm" placeholder="Keterangan baris">
                            </td>
                            <td>
                                <input type="text" name="details[1][debit]" class="form-control form-control-sm text-end debit-input" placeholder="0" autocomplete="off">
                            </td>
                            <td>
                                <input type="text" name="details[1][credit]" class="form-control form-control-sm text-end credit-input" placeholder="0" autocomplete="off">
                            </td>
                            <td class="text-center">
                                <button type="button" class="btn btn-sm btn-outline-danger remove-row-btn" title="Hapus">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        
        <div class="text-muted small mt-3">
            <i class="fas fa-lightbulb me-1"></i>
            Tips: Setiap baris isi <strong>salah satu</strong> kolom Debit atau Kredit, jangan keduanya.
        </div>
    </div>
</form>

<!-- Row Template -->
<template id="rowTemplate">
    <tr class="detail-row" data-index="{index}">
        <td>
            <select name="details[{index}][account_id]" class="form-control form-control-sm account-select" required>
                <option value="">-- Pilih Akun --</option>
                <?php foreach ($accounts as $id => $label): ?>
                <option value="<?= $id ?>"><?= e($label) ?></option>
                <?php endforeach; ?>
            </select>
        </td>
        <td>
            <input type="text" name="details[{index}][description]" class="form-control form-control-sm" placeholder="Keterangan baris">
        </td>
        <td>
            <input type="text" name="details[{index}][debit]" class="form-control form-control-sm text-end debit-input" placeholder="0" autocomplete="off">
        </td>
        <td>
            <input type="text" name="details[{index}][credit]" class="form-control form-control-sm text-end credit-input" placeholder="0" autocomplete="off">
        </td>
        <td class="text-center">
            <button type="button" class="btn btn-sm btn-outline-danger remove-row-btn" title="Hapus">
                <i class="fas fa-trash"></i>
            </button>
        </td>
    </tr>
</template>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const detailsBody = document.getElementById('detailsBody');
    const addRowBtn = document.getElementById('addRowBtn');
    const template = document.getElementById('rowTemplate');
    const submitBtn = document.getElementById('submitBtn');
    const balanceAlert = document.getElementById('balanceAlert');
    const balanceRow = document.getElementById('balanceRow');
    
    let rowIndex = detailsBody.querySelectorAll('.detail-row').length;
    
    // Format number helper
    function formatNumber(num) {
        return new Intl.NumberFormat('id-ID').format(num);
    }
    
    function parseNumber(str) {
        if (!str) return 0;
        return parseInt(String(str).replace(/[^0-9]/g, '')) || 0;
    }
    
    // Add row
    addRowBtn.addEventListener('click', function() {
        const html = template.innerHTML.replace(/{index}/g, rowIndex);
        detailsBody.insertAdjacentHTML('beforeend', html);
        
        const newRow = detailsBody.lastElementChild;
        bindRowEvents(newRow);
        rowIndex++;
        
        // Focus on account select
        newRow.querySelector('.account-select').focus();
    });
    
    // Bind events to all existing rows
    detailsBody.querySelectorAll('.detail-row').forEach(bindRowEvents);
    
    function bindRowEvents(row) {
        // Remove button
        row.querySelector('.remove-row-btn').addEventListener('click', function() {
            if (detailsBody.querySelectorAll('.detail-row').length > 2) {
                row.remove();
                recalculateTotals();
            } else {
                alert('Minimal 2 baris diperlukan untuk jurnal.');
            }
        });
        
        // Debit input
        const debitInput = row.querySelector('.debit-input');
        const creditInput = row.querySelector('.credit-input');
        
        debitInput.addEventListener('input', function() {
            let value = parseNumber(this.value);
            this.value = value > 0 ? formatNumber(value) : '';
            
            // Jika debit diisi, kosongkan credit
            if (value > 0) {
                creditInput.value = '';
            }
            recalculateTotals();
        });
        
        debitInput.addEventListener('focus', function() {
            if (parseNumber(this.value) === 0) this.value = '';
        });
        
        debitInput.addEventListener('blur', function() {
            let value = parseNumber(this.value);
            this.value = value > 0 ? formatNumber(value) : '';
        });
        
        // Credit input
        creditInput.addEventListener('input', function() {
            let value = parseNumber(this.value);
            this.value = value > 0 ? formatNumber(value) : '';
            
            // Jika credit diisi, kosongkan debit
            if (value > 0) {
                debitInput.value = '';
            }
            recalculateTotals();
        });
        
        creditInput.addEventListener('focus', function() {
            if (parseNumber(this.value) === 0) this.value = '';
        });
        
        creditInput.addEventListener('blur', function() {
            let value = parseNumber(this.value);
            this.value = value > 0 ? formatNumber(value) : '';
        });
    }
    
    function recalculateTotals() {
        let totalDebit = 0;
        let totalCredit = 0;
        
        detailsBody.querySelectorAll('.detail-row').forEach(row => {
            totalDebit += parseNumber(row.querySelector('.debit-input').value);
            totalCredit += parseNumber(row.querySelector('.credit-input').value);
        });
        
        document.getElementById('totalDebit').textContent = 'Rp ' + formatNumber(totalDebit);
        document.getElementById('totalCredit').textContent = 'Rp ' + formatNumber(totalCredit);
        
        const balance = Math.abs(totalDebit - totalCredit);
        const balanceEl = document.getElementById('balance');
        balanceEl.textContent = 'Rp ' + formatNumber(balance);
        
        // Check if balanced
        const isBalanced = balance === 0 && totalDebit > 0;
        
        if (isBalanced) {
            balanceRow.classList.remove('text-danger');
            balanceRow.classList.add('text-success');
            balanceAlert.className = 'alert alert-success mt-3 mb-0 small';
            balanceAlert.innerHTML = '<i class="fas fa-check-circle me-1"></i> Jurnal sudah balance!';
            submitBtn.disabled = false;
        } else {
            balanceRow.classList.remove('text-success');
            balanceRow.classList.add('text-danger');
            balanceAlert.className = 'alert alert-warning mt-3 mb-0 small';
            balanceAlert.innerHTML = '<i class="fas fa-exclamation-triangle me-1"></i> Jurnal belum balance (Debit ≠ Kredit)';
            submitBtn.disabled = true;
        }
    }
    
    // Form submit - convert formatted numbers to raw
    document.getElementById('journalForm').addEventListener('submit', function(e) {
        detailsBody.querySelectorAll('.debit-input, .credit-input').forEach(input => {
            input.value = parseNumber(input.value);
        });
    });
    
    // Initial calculation
    recalculateTotals();
});
</script>

<style>
.debit-input:focus, .credit-input:focus {
    background-color: #fffef0;
}
.debit-input:not(:placeholder-shown) {
    background-color: #f0f9ff;
}
.credit-input:not(:placeholder-shown) {
    background-color: #fdf4ff;
}
</style>