<?php
/**
 * Dashboard View - With Financial Summary
 * File: /views/dashboard/index.php
 */
?>

<!-- Stats Cards -->
<div class="row mb-4">
    <div class="col-6 col-xl-3 stagger-item">
        <div class="stat-card">
            <div class="stat-icon blue">
                <i class="fas fa-file-alt"></i>
            </div>
            <div class="stat-content">
                <div class="stat-label">Pesanan Bulan Ini</div>
                <div class="stat-value"><?= number_format($stats['total_orders']) ?></div>
            </div>
        </div>
    </div>
    
    <div class="col-6 col-xl-3 stagger-item">
        <div class="stat-card">
            <div class="stat-icon green">
                <i class="fas fa-money-bill-wave"></i>
            </div>
            <div class="stat-content">
                <div class="stat-label">Pendapatan Bulan Ini</div>
                <div class="stat-value"><?= formatRupiah($stats['total_revenue']) ?></div>
                <?php if ($stats['revenue_growth'] != 0): ?>
                <div class="stat-change <?= $stats['revenue_growth'] >= 0 ? 'up' : 'down' ?>">
                    <i class="fas fa-arrow-<?= $stats['revenue_growth'] >= 0 ? 'up' : 'down' ?>"></i>
                    <?= abs($stats['revenue_growth']) ?>% dari bulan lalu
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <div class="col-6 col-xl-3 stagger-item">
        <div class="stat-card">
            <div class="stat-icon yellow">
                <i class="fas fa-chart-line"></i>
            </div>
            <div class="stat-content">
                <div class="stat-label">Profit Bulan Ini</div>
                <div class="stat-value"><?= formatRupiah($stats['total_profit']) ?></div>
            </div>
        </div>
    </div>
    
    <div class="col-6 col-xl-3 stagger-item">
        <div class="stat-card">
            <div class="stat-icon red">
                <i class="fas fa-clock"></i>
            </div>
            <div class="stat-content">
                <div class="stat-label">Belum Dibayar</div>
                <div class="stat-value"><?= formatRupiah($stats['pending_payments']) ?></div>
            </div>
        </div>
    </div>
</div>

<!-- Charts Row -->
<div class="row mb-4">
    <div class="col-lg-6 stagger-item">
        <div class="glass-card">
            <div class="card-header">
                <h3 class="card-title">Pendapatan & Profit <?= date('Y') ?></h3>
            </div>
            <div class="chart-container">
                <canvas id="revenueChart"></canvas>
            </div>
        </div>
    </div>
    
    <div class="col-lg-6 stagger-item">
        <div class="glass-card">
            <div class="card-header">
                <h3 class="card-title">Status Pembayaran</h3>
            </div>
            <div class="chart-container">
                <canvas id="statusChart"></canvas>
            </div>
        </div>
    </div>
</div>

<!-- Reservation Trend Row -->
<div class="row mb-4">
    <div class="col-12 stagger-item">
        <div class="glass-card">
            <div class="card-header">
                <h3 class="card-title">Tren Reservasi (6 Bulan Terakhir)</h3>
            </div>
            <div class="chart-container">
                <canvas id="reservationTrendChart"></canvas>
            </div>
        </div>
    </div>
</div>

<!-- Recent Orders & Quick Stats -->
<div class="row">
    <div class="col-lg-6 stagger-item">
        <div class="glass-card">
            <div class="card-header">
                <h3 class="card-title">Pesanan Terbaru</h3>
                <a href="<?= url('/orders') ?>" class="btn btn-sm btn-outline-primary">
                    Lihat Semua
                </a>
            </div>
            
            <?php if (empty($recentOrders)): ?>
            <div class="empty-state">
                <div class="empty-state-icon">
                    <i class="fas fa-file-alt"></i>
                </div>
                <h4 class="empty-state-title">Belum Ada Pesanan</h4>
                <p class="empty-state-text">Mulai buat pesanan pertama Anda.</p>
                <a href="<?= url('/orders/create') ?>" class="btn btn-primary">
                    <i class="fas fa-plus"></i> Buat Pesanan
                </a>
            </div>
            <?php else: ?>
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th>No. Order</th>
                            <th>Klien</th>
                            <th>Total</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($recentOrders as $order): ?>
                        <tr>
                            <td>
                                <a href="<?= url('/orders/' . $order['id']) ?>">
                                    <?= e($order['order_number']) ?>
                                </a>
                            </td>
                            <td><?= e($order['client_name'] ?? '-') ?></td>
                            <td><?= formatRupiah($order['total_final_price']) ?></td>
                            <td>
                                <?php 
                                $status = ORDER_STATUSES[$order['status']] ?? null;
                                if ($status):
                                ?>
                                <span class="badge badge-<?= $status['color'] ?>">
                                    <?= $status['label'] ?>
                                </span>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php endif; ?>
        </div>
    </div>
    
    <div class="col-lg-6 stagger-item">
        <div class="glass-card">
            <div class="card-header">
                <h3 class="card-title">Ringkasan</h3>
            </div>
            
            <div class="summary-list">
                <div class="summary-item">
                    <div class="summary-icon" style="background: var(--primary-100); color: var(--primary-600);">
                        <i class="fas fa-users"></i>
                    </div>
                    <div class="summary-info">
                        <div class="summary-label">Total Klien</div>
                        <div class="summary-value"><?= number_format($stats['total_clients']) ?></div>
                    </div>
                </div>
                
                <div class="summary-item">
                    <div class="summary-icon" style="background: #faefd0; color: #a67f20;">
                        <i class="fas fa-file-alt"></i>
                    </div>
                    <div class="summary-info">
                        <div class="summary-label">Draft</div>
                        <div class="summary-value"><?= number_format($stats['by_status']['draft'] ?? 0) ?></div>
                    </div>
                </div>
                
                <div class="summary-item">
                    <div class="summary-icon" style="background: #fef3c7; color: #d97706;">
                        <i class="fas fa-file-invoice"></i>
                    </div>
                    <div class="summary-info">
                        <div class="summary-label">Menunggu Pembayaran</div>
                        <div class="summary-value"><?= number_format($stats['by_status']['invoiced'] ?? 0) ?></div>
                    </div>
                </div>
                
                <div class="summary-item">
                    <div class="summary-icon" style="background: #d1fae5; color: #059669;">
                        <i class="fas fa-check-circle"></i>
                    </div>
                    <div class="summary-info">
                        <div class="summary-label">Selesai</div>
                        <div class="summary-value"><?= number_format($stats['by_status']['completed'] ?? 0) ?></div>
                    </div>
                </div>
            </div>
            
            <div class="mt-4">
                <a href="<?= url('/orders/create') ?>" class="btn btn-primary w-100">
                    <i class="fas fa-plus"></i>
                    Buat Pesanan Baru
                </a>
            </div>
        </div>
    </div>
</div>

<style>
.summary-list {
    display: flex;
    flex-direction: column;
    gap: 1rem;
}

.summary-item {
    display: flex;
    align-items: center;
    gap: 1rem;
    padding: 1rem;
    background: var(--gray-50);
    border-radius: var(--border-radius-sm);
}

.summary-icon {
    width: 48px;
    height: 48px;
    border-radius: var(--border-radius-sm);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.25rem;
}

.summary-info {
    flex: 1;
}

.summary-label {
    font-size: var(--font-size-sm);
    color: var(--gray-500);
}

.summary-value {
    font-size: var(--font-size-xl);
    font-weight: 600;
    color: var(--gray-800);
}

</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Revenue Chart
    const revenueCtx = document.getElementById('revenueChart').getContext('2d');
    const monthlyData = <?= json_encode($monthlyRevenue) ?>;
    
    const months = ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'];
    
    new Chart(revenueCtx, {
        type: 'line',
        data: {
            labels: months,
            datasets: [
                {
                    label: 'Pendapatan',
                    data: monthlyData.map(d => d.revenue),
                    borderColor: '#c89b2c',
                    backgroundColor: 'rgba(200, 155, 44, 0.1)',
                    fill: true,
                    tension: 0.4
                },
                {
                    label: 'Profit',
                    data: monthlyData.map(d => d.profit),
                    borderColor: '#10b981',
                    backgroundColor: 'rgba(16, 185, 129, 0.1)',
                    fill: true,
                    tension: 0.4
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'bottom'
                },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            return context.dataset.label + ': ' + formatRupiah(context.raw);
                        }
                    }
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        callback: function(value) {
                            return 'Rp ' + (value / 1000000).toFixed(0) + 'jt';
                        }
                    }
                }
            }
        }
    });
    
    // Reservation Trend Chart (6-month rolling window)
    const trendCtx = document.getElementById('reservationTrendChart').getContext('2d');
    const trendData = <?= json_encode($reservationTrend) ?>;

    new Chart(trendCtx, {
        type: 'bar',
        data: {
            labels: trendData.map(d => d.label),
            datasets: [{
                label: 'Jumlah Reservasi',
                data: trendData.map(d => d.total),
                backgroundColor: 'rgba(200, 155, 44, 0.6)',
                borderColor: '#c89b2c',
                borderWidth: 1
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { display: false }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: { precision: 0 }
                }
            }
        }
    });

    // Status Chart (status pembayaran)
    const statusCtx = document.getElementById('statusChart').getContext('2d');
    const statusData = <?= json_encode($stats['by_payment_status']) ?>;

    const statusLabels = {
        unpaid: 'Belum Dibayar',
        partial: 'Sebagian',
        paid: 'Lunas'
    };

    const statusColors = {
        unpaid: '#ef4444',
        partial: '#f59e0b',
        paid: '#10b981'
    };
    
    const labels = Object.keys(statusData).map(k => statusLabels[k] || k);
    const values = Object.values(statusData);
    const colors = Object.keys(statusData).map(k => statusColors[k] || '#94a3b8');
    
    new Chart(statusCtx, {
        type: 'doughnut',
        data: {
            labels: labels,
            datasets: [{
                data: values,
                backgroundColor: colors,
                borderWidth: 0
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'bottom'
                }
            },
            cutout: '60%'
        }
    });
});

function formatRupiah(value) {
    return 'Rp ' + new Intl.NumberFormat('id-ID').format(value);
}
</script>