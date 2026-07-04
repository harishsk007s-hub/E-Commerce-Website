<?php
require_once '../includes/db_connection.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';
require_once 'layout.php';

check_admin_auth();
require_role(['super-admin', 'manager']); // Allow managers too

$action = $_GET['action'] ?? 'list';

if ($action === 'export_csv') {
    $orders = $pdo->query("SELECT o.id, c.name as customer, o.total, o.status, o.created_at FROM orders o LEFT JOIN customers c ON o.customer_id = c.id ORDER BY o.created_at DESC")->fetchAll();
    
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="sales_report_'.date('Ymd').'.csv"');
    
    $output = fopen('php://output', 'w');
    fputcsv($output, ['Order ID', 'Customer', 'Total', 'Status', 'Date']);
    
    foreach ($orders as $o) {
        fputcsv($output, [$o['id'], $o['customer'] ?: 'Guest', $o['total'], $o['status'], $o['created_at']]);
    }
    fclose($output);
    exit;
}

// 1. Overview Statistics
$total_revenue = $pdo->query("SELECT SUM(total) FROM orders WHERE payment_status = 'paid' OR payment_method = 'cod'")->fetchColumn() ?: 0;
$total_orders = $pdo->query("SELECT COUNT(*) FROM orders")->fetchColumn() ?: 0;
$total_customers = $pdo->query("SELECT COUNT(*) FROM customers")->fetchColumn() ?: 0;
$avg_order_value = $total_orders > 0 ? $total_revenue / $total_orders : 0;

// 2. Daily Sales (Last 14 Days)
$daily_sales = $pdo->query("
    SELECT DATE(created_at) as date, SUM(total) as revenue, COUNT(*) as count 
    FROM orders 
    WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 14 DAY)
    GROUP BY DATE(created_at) 
    ORDER BY date ASC
")->fetchAll(PDO::FETCH_ASSOC);

// 3. Weekly Sales (Last 12 Weeks)
$weekly_sales = $pdo->query("
    SELECT YEARWEEK(created_at, 1) as week_num, MIN(DATE(created_at)) as week_start, SUM(total) as revenue, COUNT(*) as count 
    FROM orders 
    WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 12 WEEK)
    GROUP BY week_num 
    ORDER BY week_num ASC
")->fetchAll(PDO::FETCH_ASSOC);

// 4. Monthly Sales (Last 6 Months)
$monthly_sales = $pdo->query("
    SELECT DATE_FORMAT(created_at, '%Y-%m') as month, SUM(total) as revenue, COUNT(*) as count 
    FROM orders 
    GROUP BY month 
    ORDER BY month DESC 
    LIMIT 6
")->fetchAll(PDO::FETCH_ASSOC);
$monthly_sales = array_reverse($monthly_sales);

// 5. Top Selling Products (Simplistic approach without JSON_TABLE for compatibility)
$orders_items = $pdo->query("SELECT items FROM orders WHERE payment_status = 'paid' OR payment_method = 'cod' LIMIT 500")->fetchAll(PDO::FETCH_COLUMN);
$product_sales = [];
foreach ($orders_items as $json) {
    $items = json_decode($json, true);
    if (is_array($items)) {
        foreach ($items as $item) {
            $pid = $item['product_id'] ?? 0;
            $qty = $item['quantity'] ?? 0;
            $name = $item['name'] ?? ('Product #' . $pid);
            if ($pid > 0) {
                if (!isset($product_sales[$pid])) {
                    $product_sales[$pid] = ['name' => $name, 'qty' => 0, 'revenue' => 0];
                }
                $product_sales[$pid]['qty'] += $qty;
                $product_sales[$pid]['revenue'] += ($item['price'] ?? 0) * $qty;
            }
        }
    }
}
uasort($product_sales, fn($a, $b) => $b['qty'] <=> $a['qty']);
$top_products = array_slice($product_sales, 0, 5, true);

ob_start();
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h4 class="fw-bold mb-0">Reports & Analytics</h4>
    <div class="d-flex gap-2">
        <a href="?action=export_csv" class="btn btn-dark btn-sm shadow-sm px-3 py-2 rounded-pill">
            <i class="fas fa-file-export me-1"></i> Export CSV
        </a>
    </div>
</div>

<!-- Sales Analytics Chart -->
<div class="card border-0 shadow-sm rounded-4 mb-5">
    <div class="card-header bg-transparent border-0 pt-4 px-4 pb-0 d-flex justify-content-between align-items-center">
        <div>
            <h5 class="fw-bold mb-0">Sales Analytics</h5>
            <p class="text-muted small mb-0">Revenue and order trends for the last 14 days</p>
        </div>
        <div class="dropdown">
            <button class="btn btn-light btn-sm rounded-pill px-3 dropdown-toggle" type="button" data-bs-toggle="dropdown">
                Last 14 Days
            </button>
            <ul class="dropdown-menu dropdown-menu-end shadow border-0">
                <li><a class="dropdown-item" href="#">Last 7 Days</a></li>
                <li><a class="dropdown-item active" href="#">Last 14 Days</a></li>
                <li><a class="dropdown-item" href="#">Last 30 Days</a></li>
            </ul>
        </div>
    </div>
    <div class="card-body p-4">
        <div style="height: 350px;">
            <canvas id="salesChart"></canvas>
        </div>
    </div>
</div>

<div class="row g-4">
    <!-- Daily Sales Table -->
    <div class="col-md-7">
        <div class="card border-0 shadow-sm rounded-4 h-100">
            <div class="card-header bg-transparent border-0 pt-4 px-4 pb-0 d-flex justify-content-between align-items-center">
                <h5 class="fw-bold mb-0">Daily Sales <span class="text-muted small fw-normal">(Last 14 Days)</span></h5>
            </div>
            <div class="card-body p-4">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="bg-light">
                            <tr>
                                <th class="border-0 rounded-start">Date</th>
                                <th class="border-0">Orders</th>
                                <th class="border-0 text-end rounded-end">Revenue</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($daily_sales as $day): ?>
                            <tr>
                                <td class="fw-semibold"><?php echo date('d M Y', strtotime($day['date'])); ?></td>
                                <td><span class="badge bg-light text-dark px-3 rounded-pill"><?php echo $day['count']; ?></span></td>
                                <td class="text-end fw-bold text-dark">₹<?php echo number_format($day['revenue'], 2); ?></td>
                            </tr>
                            <?php endforeach; ?>
                            <?php if (empty($daily_sales)): ?>
                            <tr><td colspan="3" class="text-center py-4 text-muted">No data available</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Top Products & Weekly Stats -->
    <div class="col-md-5">
        <div class="card border-0 shadow-sm rounded-4 mb-4">
            <div class="card-header bg-transparent border-0 pt-4 px-4 pb-0">
                <h5 class="fw-bold mb-0">Top Selling Products</h5>
            </div>
            <div class="card-body p-4">
                <ul class="list-group list-group-flush">
                    <?php foreach ($top_products as $pid => $prod): ?>
                    <li class="list-group-item px-0 border-0 mb-3">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <div class="fw-bold text-dark"><?php echo htmlspecialchars($prod['name']); ?></div>
                                <small class="text-muted"><?php echo $prod['qty']; ?> units sold</small>
                            </div>
                            <div class="text-end">
                                <div class="fw-bold">₹<?php echo number_format($prod['revenue'], 2); ?></div>
                            </div>
                        </div>
                        <div class="progress mt-2" style="height: 6px; border-radius: 10px;">
                            <div class="progress-bar bg-warning" role="progressbar" style="width: <?php echo min(100, ($prod['qty'] / 100) * 100); ?>%"></div>
                        </div>
                    </li>
                    <?php endforeach; ?>
                    <?php if (empty($top_products)): ?>
                    <div class="text-center py-3 text-muted">No sales recorded yet</div>
                    <?php endif; ?>
                </ul>
            </div>
        </div>

        <div class="card border-0 shadow-sm rounded-4 mb-4">
            <div class="card-header bg-transparent border-0 pt-4 px-4 pb-0">
                <h5 class="fw-bold mb-0">Weekly Sales <span class="text-muted small fw-normal">(Last 12 Weeks)</span></h5>
            </div>
            <div class="card-body p-4">
                <div class="table-responsive">
                    <table class="table table-sm table-hover align-middle mb-0">
                        <thead>
                            <tr class="text-muted small">
                                <th class="border-0">Week Starting</th>
                                <th class="border-0 text-end">Revenue</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($weekly_sales as $week): ?>
                            <tr>
                                <td class="py-2"><?php echo date('d M', strtotime($week['week_start'])); ?></td>
                                <td class="text-end fw-bold">₹<?php echo number_format($week['revenue'], 0); ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="card border-0 shadow-sm rounded-4">
            <div class="card-header bg-transparent border-0 pt-4 px-4 pb-0">
                <h5 class="fw-bold mb-0">Monthly Sales <span class="text-muted small fw-normal">(Last 6 Months)</span></h5>
            </div>
            <div class="card-body p-4">
                <div class="table-responsive">
                    <table class="table table-sm table-hover align-middle mb-0">
                        <thead>
                            <tr class="text-muted small">
                                <th class="border-0">Month</th>
                                <th class="border-0 text-end">Revenue</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($monthly_sales as $month): ?>
                            <tr>
                                <td class="py-2"><?php echo date('F Y', strtotime($month['month'] . '-01')); ?></td>
                                <td class="text-end fw-bold">₹<?php echo number_format($month['revenue'], 0); ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
    .fw-black { font-weight: 900; }
</style>

<?php
$content = ob_get_clean();

// Prepare data for the chart
$chart_labels = [];
$chart_revenue = [];
$chart_orders = [];

// Create a date-indexed map for existing daily sales
$sales_map = [];
foreach ($daily_sales as $day) {
    $sales_map[$day['date']] = $day;
}

// Fill in all 14 days to ensure the chart is continuous
for ($i = 13; $i >= 0; $i--) {
    $date = date('Y-m-d', strtotime("-$i days"));
    $chart_labels[] = date('d M', strtotime($date));
    $chart_revenue[] = $sales_map[$date]['revenue'] ?? 0;
    $chart_orders[] = $sales_map[$date]['count'] ?? 0;
}

$scripts = '
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    $(document).ready(function() {
        const ctx = document.getElementById("salesChart").getContext("2d");
        
        // Gradient for revenue line
        const revenueGradient = ctx.createLinearGradient(0, 0, 0, 400);
        revenueGradient.addColorStop(0, "rgba(13, 110, 253, 0.2)");
        revenueGradient.addColorStop(1, "rgba(13, 110, 253, 0)");

        new Chart(ctx, {
            type: "line",
            data: {
                labels: ' . json_encode($chart_labels) . ',
                datasets: [
                    {
                        label: "Revenue (₹)",
                        data: ' . json_encode($chart_revenue) . ',
                        borderColor: "#0d6efd",
                        backgroundColor: revenueGradient,
                        fill: true,
                        tension: 0.4,
                        borderWidth: 3,
                        pointRadius: 4,
                        pointBackgroundColor: "#fff",
                        pointBorderColor: "#0d6efd",
                        pointBorderWidth: 2,
                        yAxisID: "y-revenue"
                    },
                    {
                        label: "Orders",
                        data: ' . json_encode($chart_orders) . ',
                        borderColor: "#198754",
                        backgroundColor: "#198754",
                        type: "bar",
                        barThickness: 20,
                        borderRadius: 5,
                        yAxisID: "y-orders"
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                interaction: {
                    mode: "index",
                    intersect: false
                },
                plugins: {
                    legend: {
                        position: "top",
                        align: "end",
                        labels: {
                            usePointStyle: true,
                            padding: 20,
                            font: { size: 12, weight: "bold" }
                        }
                    },
                    tooltip: {
                        padding: 12,
                        backgroundColor: "rgba(0,0,0,0.8)",
                        titleFont: { size: 14 },
                        bodyFont: { size: 13 },
                        callbacks: {
                            label: function(context) {
                                let label = context.dataset.label || "";
                                if (label) label += ": ";
                                if (context.dataset.yAxisID === "y-revenue") {
                                    label += "₹" + context.parsed.y.toLocaleString();
                                } else {
                                    label += context.parsed.y;
                                }
                                return label;
                            }
                        }
                    }
                },
                scales: {
                    x: {
                        grid: { display: false },
                        ticks: { font: { size: 11 } }
                    },
                    "y-revenue": {
                        type: "linear",
                        display: true,
                        position: "left",
                        grid: { borderDash: [5, 5] },
                        ticks: {
                            callback: value => "₹" + value.toLocaleString(),
                            font: { size: 11 }
                        }
                    },
                    "y-orders": {
                        type: "linear",
                        display: true,
                        position: "right",
                        grid: { display: false },
                        ticks: {
                            stepSize: 1,
                            font: { size: 11 }
                        }
                    }
                }
            }
        });
    });
</script>
'; 
admin_layout("Reports", $content, $scripts);
?>
