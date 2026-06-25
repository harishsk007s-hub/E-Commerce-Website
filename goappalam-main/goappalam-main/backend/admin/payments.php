<?php
require_once '../includes/db_connection.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';
require_once 'layout.php';

check_admin_auth();

// Handle Status Update (Manual "Mark Completed")
if (isset($_GET['action']) && $_GET['action'] === 'complete' && isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    
    try {
        $pdo->beginTransaction();
        
        // 1. Update payment status
        $stmt = $pdo->prepare("UPDATE payments SET status = 'paid' WHERE id = ?");
        $stmt->execute([$id]);
        
        // 2. Fetch associated order
        $stmt = $pdo->prepare("SELECT order_id FROM payments WHERE id = ?");
        $stmt->execute([$id]);
        $order_id = $stmt->fetchColumn();
        
        if ($order_id) {
            // 3. Update order status and payment_verified
            // COD confirmed manually here adds to confirmed revenue
            $stmt = $pdo->prepare("UPDATE orders SET payment_status = 'paid', payment_verified = 1, status = 'processing' WHERE id = ?");
            $stmt->execute([$order_id]);
        }
        
        $pdo->commit();
        header("Location: payments.php?success=1");
        exit;
    } catch (Exception $e) {
        $pdo->rollBack();
        header("Location: payments.php?error=" . urlencode($e->getMessage()));
        exit;
    }
}

// Handle CSV Export
if (isset($_GET['export']) && $_GET['export'] === 'csv') {
    $filename = "payments_report_" . date('Y-m-d') . ".csv";
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    
    $output = fopen('php://output', 'w');
    fputcsv($output, ['ID', 'Order ID', 'Customer', 'Amount', 'Gateway', 'Payment ID', 'Payment Key', 'Status', 'Date']);
    
    $stmt = $pdo->query("SELECT p.*, o.id as order_display_id, c.name as customer_name 
                        FROM payments p 
                        LEFT JOIN orders o ON p.order_id = o.id 
                        LEFT JOIN customers c ON o.customer_id = c.id 
                        ORDER BY p.id DESC");
    
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        fputcsv($output, [
            $row['id'],
            $row['order_display_id'],
            $row['customer_name'] ?: 'Guest',
            $row['amount'],
            strtoupper($row['gateway']),
            $row['payment_id'] ?: $row['transaction_id'],
            $row['payment_key'],
            strtoupper($row['status']),
            $row['created_at']
        ]);
    }
    fclose($output);
    exit;
}

$gateway_filter = $_GET['gateway'] ?? '';
$where = "";
$params = [];

if ($gateway_filter) {
    $where = " WHERE p.gateway = ?";
    $params[] = $gateway_filter;
}

$sql = "SELECT p.*, o.id as order_display_id, c.name as customer_name 
        FROM payments p 
        LEFT JOIN orders o ON p.order_id = o.id 
        LEFT JOIN customers c ON o.customer_id = c.id 
        $where 
        ORDER BY p.id DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$payments = $stmt->fetchAll();

$gateways = $pdo->query("SELECT DISTINCT gateway FROM payments WHERE gateway IS NOT NULL")->fetchAll(PDO::FETCH_COLUMN);

ob_start();
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h4 class="fw-bold mb-0">Payment Management</h4>
    <a href="?export=csv" class="btn btn-dark btn-sm shadow-sm px-4 py-2">
        <i class="fas fa-file-export me-2"></i> Export CSV
    </a>
</div>

<div class="card border-0 shadow-sm rounded-4">
    <div class="card-header bg-transparent border-0 py-4 px-4 d-flex justify-content-between align-items-center">
        <h5 class="fw-bold mb-0">Transactions</h5>
        <form class="d-flex gap-2">
            <select name="gateway" class="form-select form-select-sm border-0 bg-light px-3 rounded-pill" onchange="this.form.submit()">
                <option value="">All Gateways</option>
                <option value="razorpay" <?php echo $gateway_filter === 'razorpay' ? 'selected' : ''; ?>>RAZORPAY</option>
                <option value="stripe" <?php echo $gateway_filter === 'stripe' ? 'selected' : ''; ?>>STRIPE</option>
                <option value="cod" <?php echo $gateway_filter === 'cod' ? 'selected' : ''; ?>>COD</option>
                <?php foreach($gateways as $g): if(!in_array($g, ['razorpay','stripe','cod'])): ?>
                    <option value="<?php echo $g; ?>" <?php echo $gateway_filter === $g ? 'selected' : ''; ?>><?php echo strtoupper($g); ?></option>
                <?php endif; endforeach; ?>
            </select>
        </form>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0 datatable">
                <thead class="bg-light">
                    <tr>
                        <th class="px-4 py-3 border-0">Customer</th>
                        <th class="py-3 border-0">Amount</th>
                        <th class="py-3 border-0">Gateway</th>
                        <th class="py-3 border-0">Verification</th>
                        <th class="py-3 border-0">Payment Details</th>
                        <th class="py-3 border-0">Status</th>
                        <th class="py-3 border-0 text-end px-4">Actions</th>
                    </tr>
                </thead>
                <tbody class="border-top-0">
                    <?php foreach ($payments as $p): ?>
                    <tr>
                        <td class="px-4"><?php echo htmlspecialchars($p['customer_name'] ?? 'Guest'); ?></td>
                        <td class="fw-bold text-dark">₹<?php echo number_format($p['amount'], 2); ?></td>
                        <td><span class="badge bg-light text-dark border text-uppercase"><?php echo $p['gateway']; ?></span></td>
                        <td>
                            <?php if ($p['gateway'] === 'razorpay'): ?>
                                <span class="badge bg-success-soft text-success px-2 py-1 small">
                                    <i class="fas fa-shield-alt"></i> Verified
                                </span>
                            <?php else: ?>
                                <span class="text-muted small">N/A</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <div class="small">
                                <div class="text-muted">ID: <span class="font-monospace"><?php echo $p['payment_id'] ?: $p['transaction_id']; ?></span></div>
                                <?php if($p['payment_key']): ?>
                                    <div class="text-muted">Key: <span class="font-monospace"><?php echo $p['payment_key']; ?></span></div>
                                <?php endif; ?>
                                <div class="text-muted small"><?php echo date('d M Y, H:i', strtotime($p['created_at'])); ?></div>
                            </div>
                        </td>
                        <td>
                            <span class="badge rounded-pill bg-<?php 
                                echo match($p['status']) {
                                    'paid' => 'success',
                                    'pending' => 'warning',
                                    'failed', 'refunded' => 'danger',
                                    default => 'secondary'
                                };
                            ?>-soft text-<?php 
                                echo match($p['status']) {
                                    'paid' => 'success',
                                    'pending' => 'warning',
                                    'failed', 'refunded' => 'danger',
                                    default => 'secondary'
                                };
                            ?> px-3">
                                <?php echo $p['status']; ?>
                            </span>
                        </td>
                        <td class="text-end px-4">
                            <div class="d-flex justify-content-end gap-2">
                                <?php if($p['gateway_response']): ?>
                                    <button class="btn btn-sm btn-outline-info rounded-pill px-3" data-bs-toggle="modal" data-bs-target="#responseModal<?php echo $p['id']; ?>">
                                        <i class="fas fa-code"></i>
                                    </button>
                                <?php endif; ?>
                                
                                <?php if ($p['status'] === 'pending'): ?>
                                    <a href="?action=complete&id=<?php echo $p['id']; ?>" class="btn btn-sm btn-success rounded-pill px-3 fw-bold" onclick="return confirm('Mark this payment as completed?')">
                                        <i class="fas fa-check"></i> Complete
                                    </a>
                                <?php else: ?>
                                    <span class="badge bg-success-soft text-success rounded-pill px-3 py-2"><i class="fas fa-check-double me-1"></i> Verified</span>
                                <?php endif; ?>
                            </div>

                            <!-- Modal for JSON Response -->
                            <?php if($p['gateway_response']): ?>
                            <div class="modal fade" id="responseModal<?php echo $p['id']; ?>" tabindex="-1">
                                <div class="modal-dialog modal-dialog-centered">
                                    <div class="modal-content border-0 shadow">
                                        <div class="modal-header bg-info text-white border-0">
                                            <h5 class="modal-title fw-bold">Gateway Response</h5>
                                            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                                        </div>
                                        <div class="modal-body bg-light text-start p-4">
                                            <pre class="bg-white p-3 rounded border small mb-0 overflow-auto" style="max-height: 400px;"><?php 
                                                $json = json_decode($p['gateway_response'], true);
                                                echo json_encode($json, JSON_PRETTY_PRINT);
                                            ?></pre>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<style>
    .bg-success-soft { background-color: rgba(25, 135, 84, 0.1); color: #198754 !important; }
    .bg-warning-soft { background-color: rgba(255, 193, 7, 0.1); color: #9a6e03 !important; }
    .bg-danger-soft { background-color: rgba(220, 53, 69, 0.1); color: #dc3545 !important; }
    .bg-info-soft { background-color: rgba(13, 202, 240, 0.1); color: #0dcaf0 !important; }
    pre { white-space: pre-wrap; word-wrap: break-word; }
</style>

<?php
$content = ob_get_clean();
admin_layout("Payment Management", $content);
?>
