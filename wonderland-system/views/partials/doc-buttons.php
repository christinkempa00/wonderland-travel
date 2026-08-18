<?php
/**
 * Document Download Buttons
 * Include this in your order show page
 * 
 * Usage: <?php include 'partials/doc-buttons.php'; ?>
 * Required: $order variable with order ID
 */

$orderId = $order['id'] ?? $order->id ?? 0;
?>

<style>
.doc-buttons {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 12px;
    margin-top: 20px;
}

.doc-btn {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 15px 18px;
    border-radius: 12px;
    text-decoration: none;
    color: white;
    font-weight: 600;
    font-size: 13px;
    transition: all 0.2s;
    border: none;
    cursor: pointer;
}

.doc-btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 20px rgba(0,0,0,0.2);
}

.doc-btn .icon {
    width: 40px;
    height: 40px;
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 18px;
    background: rgba(255,255,255,0.2);
}

.doc-btn .text {
    flex: 1;
}

.doc-btn .text span {
    display: block;
    font-size: 10px;
    opacity: 0.8;
    font-weight: 400;
}

.doc-btn.invoice {
    background: linear-gradient(135deg, #dc2626, #b91c1c);
}

.doc-btn.kwitansi {
    background: linear-gradient(135deg, #7c3aed, #6d28d9);
}

@media (max-width: 768px) {
    .doc-buttons {
        grid-template-columns: 1fr;
    }
}
</style>

<div class="doc-buttons">
    <a href="<?= url('/doc.php?order=' . $orderId . '&type=invoice') ?>" target="_blank" class="doc-btn invoice">
        <div class="icon">📄</div>
        <div class="text">
            Invoice
            <span>Tagihan untuk klien</span>
        </div>
    </a>
    
    <a href="<?= url('/doc.php?order=' . $orderId . '&type=kwitansi') ?>" target="_blank" class="doc-btn kwitansi">
        <div class="icon">🧾</div>
        <div class="text">
            Kwitansi
            <span>Bukti pembayaran</span>
        </div>
    </a>
</div>
