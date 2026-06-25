<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once '../includes/db_connection.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';
require_once 'layout.php';

check_admin_auth();

// Fetch metrics
$total_sales = $pdo->query("SELECT SUM(total) FROM orders WHERE payment_status = 'paid'")->fetchColumn() ?: 0;

// Calculate dynamic percentage change (Current Month vs Previous Month)
$current_month_sales = $pdo->query("SELECT SUM(total) FROM orders WHERE payment_status = 'paid' AND MONTH(created_at) = MONTH(CURRENT_DATE()) AND YEAR(created_at) = YEAR(CURRENT_DATE())")->fetchColumn() ?: 0;
$prev_month_sales = $pdo->query("SELECT SUM(total) FROM orders WHERE payment_status = 'paid' AND MONTH(created_at) = MONTH(CURRENT_DATE() - INTERVAL 1 MONTH) AND YEAR(created_at) = YEAR(CURRENT_DATE() - INTERVAL 1 MONTH)")->fetchColumn() ?: 0;

$rev_change_pct = 0;
if ($prev_month_sales > 0) {
    $rev_change_pct = (($current_month_sales - $prev_month_sales) / $prev_month_sales) * 100;
} elseif ($current_month_sales > 0) {
    $rev_change_pct = 100; // 100% growth if started from 0
}

$total_orders = $pdo->query("SELECT COUNT(*) FROM orders WHERE status != 'pending_payment'")->fetchColumn();
// Exclude admin and developer emails from customer count
$total_customers = $pdo->query("SELECT COUNT(*) FROM customers WHERE role = 'customer' AND email NOT LIKE '%admin%' AND email NOT LIKE '%developer%'")->fetchColumn();
$low_stock = $pdo->query("SELECT COUNT(*) FROM products WHERE stock < 10")->fetchColumn();

// Recent orders - Hide pending_payment
$recent_orders = $pdo->query("SELECT o.*, c.name as customer_name FROM orders o LEFT JOIN customers c ON o.customer_id = c.id WHERE o.status != 'pending_payment' ORDER BY o.id DESC LIMIT 10")->fetchAll();

// Revenue trend (Last 7 days)
$revenue_trend = $pdo->query("
    SELECT DATE(created_at) as date, SUM(total) as revenue 
    FROM orders 
    WHERE payment_status = 'paid' 
    AND status != 'pending_payment'
    AND created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
    GROUP BY DATE(created_at)
    ORDER BY date ASC
")->fetchAll(PDO::FETCH_KEY_PAIR);

$status_dist = $pdo->query("SELECT status, COUNT(*) as count FROM orders WHERE status != 'pending_payment' GROUP BY status")->fetchAll(PDO::FETCH_KEY_PAIR);

ob_start();
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h4 class="fw-bold mb-0">Dashboard Overview</h4>
    <button onclick="window.location.reload()" class="btn btn-sm btn-white shadow-sm border py-2 px-3">
        <i class="fas fa-sync-alt me-2 text-primary"></i> Refresh Stats
    </button>
</div>

<div class="row g-4 mb-5">
    <div class="col-md-3">
        <div class="card h-100 border-0 shadow-sm rounded-4 hover-lift">
            <div class="card-body p-4">
                <div class="d-flex align-items-center justify-content-between mb-3">
                    <div class="icon-shape bg-primary-soft text-primary rounded-3">
                        <i class="fas fa-rupee-sign fs-4"></i>
                    </div>
                    <?php if ($rev_change_pct != 0): ?>
                    <span class="badge bg-<?php echo $rev_change_pct >= 0 ? 'success' : 'danger'; ?>-soft text-<?php echo $rev_change_pct >= 0 ? 'success' : 'danger'; ?>">
                        <?php echo ($rev_change_pct > 0 ? '+' : '') . round($rev_change_pct, 1); ?>% 
                        <i class="fas fa-arrow-<?php echo $rev_change_pct >= 0 ? 'up' : 'down'; ?>"></i>
                    </span>
                    <?php endif; ?>
                </div>
                <h6 class="text-muted fw-semibold mb-1">Total Revenue</h6>
                <h3 class="fw-bold mb-0">₹<?php echo number_format($total_sales, 2); ?></h3>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card h-100 border-0 shadow-sm rounded-4 hover-lift">
            <div class="card-body p-4">
                <div class="d-flex align-items-center justify-content-between mb-3">
                    <div class="icon-shape bg-success-soft text-success rounded-3">
                        <i class="fas fa-shopping-bag fs-4"></i>
                    </div>
                </div>
                <h6 class="text-muted fw-semibold mb-1">Total Orders</h6>
                <h3 class="fw-bold mb-0"><?php echo $total_orders; ?></h3>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card h-100 border-0 shadow-sm rounded-4 hover-lift">
            <div class="card-body p-4">
                <div class="d-flex align-items-center justify-content-between mb-3">
                    <div class="icon-shape bg-info-soft text-info rounded-3">
                        <i class="fas fa-users fs-4"></i>
                    </div>
                </div>
                <h6 class="text-muted fw-semibold mb-1">Total Customers</h6>
                <h3 class="fw-bold mb-0"><?php echo $total_customers; ?></h3>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card h-100 border-0 shadow-sm rounded-4 hover-lift">
            <div class="card-body p-4">
                <div class="d-flex align-items-center justify-content-between mb-3">
                    <div class="icon-shape bg-warning-soft text-warning rounded-3">
                        <i class="fas fa-exclamation-triangle fs-4"></i>
                    </div>
                </div>
                <h6 class="text-muted fw-semibold mb-1">Low Stock</h6>
                <h3 class="fw-bold mb-0"><?php echo $low_stock; ?></h3>
            </div>
        </div>
    </div>
</div>

<div class="row g-4">
    <div class="col-lg-8">
        <div class="card border-0 shadow-sm rounded-4 mb-4">
            <div class="card-header bg-transparent border-0 py-4 px-4 d-flex justify-content-between align-items-center">
                <h5 class="fw-bold mb-0">Revenue Trend</h5>
                <div class="dropdown">
                    <button class="btn btn-sm btn-light border" type="button">Last 7 Days</button>
                </div>
            </div>
            <div class="card-body px-4 pb-4">
                <canvas id="revenueChart" style="height: 300px;"></canvas>
            </div>
        </div>

        <div class="card border-0 shadow-sm rounded-4">
            <div class="card-header bg-transparent border-0 py-4 px-4">
                <h5 class="fw-bold mb-0">Recent Orders</h5>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="bg-light">
                            <tr>
                                <th class="px-4 py-3 border-0">Customer</th>
                                <th class="py-3 border-0">Total</th>
                                <th class="py-3 border-0">Status</th>
                                <th class="py-3 border-0 text-end px-4">Date</th>
                            </tr>
                        </thead>
                        <tbody class="border-top-0">
                            <?php foreach ($recent_orders as $order): ?>
                            <tr>
                                <td class="px-4"><?php echo htmlspecialchars($order['customer_name'] ?? 'Guest'); ?></td>
                                <td class="fw-bold text-dark">₹<?php echo number_format($order['total'], 2); ?></td>
                                <td>
                                    <?php 
                                        $status_class = 'secondary';
                                        switch($order['status']) {
                                            case 'pending': $status_class = 'warning'; break;
                                            case 'processing': $status_class = 'info'; break;
                                            case 'shipped': $status_class = 'primary'; break;
                                            case 'completed': $status_class = 'success'; break;
                                            case 'cancelled':
                                            case 'refunded': $status_class = 'danger'; break;
                                        }
                                    ?>
                                    <span class="badge rounded-pill bg-<?php echo $status_class; ?>-soft text-<?php echo $status_class; ?> text-capitalize px-3">
                                        <?php echo $order['status']; ?>
                                    </span>
                                </td>
                                <td class="text-end px-4 text-muted"><?php echo date('M d, Y', strtotime($order['created_at'])); ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="card-footer bg-transparent border-0 py-3 text-center">
                <a href="orders.php" class="text-decoration-none fw-bold small">View All Orders <i class="fas fa-chevron-right ms-1"></i></a>
            </div>
        </div>
    </div>
    
    <div class="col-lg-4">
        <div class="card border-0 shadow-sm rounded-4 h-100">
            <div class="card-header bg-transparent border-0 py-4 px-4">
                <h5 class="fw-bold mb-0">Order Status</h5>
            </div>
            <div class="card-body px-4">
                <div style="height: 250px; position: relative;">
                    <canvas id="statusChart"></canvas>
                </div>
                <div class="mt-4">
                    <?php foreach($status_dist as $status => $count): ?>
                        <?php 
                            $dot_color = '#adb5bd';
                            switch($status) {
                                case 'pending': $dot_color = '#ffc107'; break;
                                case 'processing': $dot_color = '#0dcaf0'; break;
                                case 'shipped': $dot_color = '#0d6efd'; break;
                                case 'completed': $dot_color = '#198754'; break;
                                case 'cancelled': $dot_color = '#dc3545'; break;
                            }
                        ?>
                        <div class="d-flex align-items-center justify-content-between mb-2">
                            <span class="text-muted small text-capitalize"><i class="fas fa-circle me-2" style="color: <?php echo $dot_color; ?>"></i><?php echo $status; ?></span>
                            <span class="fw-bold small"><?php echo $count; ?></span>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
    .hover-lift { transition: transform 0.2s ease, box-shadow 0.2s ease; }
    .hover-lift:hover { transform: translateY(-5px); box-shadow: 0 10px 25px rgba(0,0,0,0.1) !important; }
    .bg-primary-soft { background-color: rgba(13, 110, 253, 0.1); }
    .bg-success-soft { background-color: rgba(25, 135, 84, 0.1); }
    .bg-info-soft { background-color: rgba(13, 202, 240, 0.1); }
    .bg-warning-soft { background-color: rgba(255, 193, 7, 0.1); }
    .bg-danger-soft { background-color: rgba(220, 53, 69, 0.1); }
    .text-success-soft { color: #198754; }
    .icon-shape { width: 48px; height: 48px; display: flex; align-items: center; justify-content: center; }
</style>

<?php
$content = ob_get_clean();

$rev_labels = json_encode(array_keys($revenue_trend));
$rev_data = json_encode(array_values($revenue_trend));
$status_labels = json_encode(array_keys($status_dist));
$status_data = json_encode(array_values($status_dist));

$scripts = <<<JS
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    // Revenue Chart
    new Chart(document.getElementById('revenueChart'), {
        type: 'line',
        data: {
            labels: $rev_labels,
            datasets: [{
                label: 'Revenue',
                data: $rev_data,
                borderColor: '#0d6efd',
                backgroundColor: 'rgba(13, 110, 253, 0.05)',
                fill: true,
                tension: 0.4,
                pointRadius: 4,
                pointBackgroundColor: '#fff',
                pointBorderWidth: 2
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: { legend: { display: false } },
            scales: {
                y: { grid: { borderDash: [5, 5] }, ticks: { callback: v => '₹' + v } },
                x: { grid: { display: false } }
            }
        }
    });

    // Status Chart
    new Chart(document.getElementById('statusChart'), {
        type: 'doughnut',
        data: {
            labels: $status_labels,
            datasets: [{
                data: $status_data,
                backgroundColor: ['#ffc107', '#0dcaf0', '#0d6efd', '#198754', '#dc3545', '#adb5bd'],
                borderWidth: 0,
                cutout: '75%'
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: { legend: { display: false } }
        }
    });
</script>
JS;

admin_layout("Dashboard Overview", $content, $scripts);
?>
