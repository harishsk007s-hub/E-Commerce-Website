<?php
require_once '../includes/db_connection.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';
require_once '../includes/email-functions.php';
require_once 'layout.php';

check_admin_auth();
require_role(['super-admin', 'manager', 'orders']);

$action = $_GET['action'] ?? 'list';
$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'update_status') {
    $id = (int)$_POST['id'];
    $status = sanitize($_POST['status']);
    
    $stmt = $pdo->prepare("UPDATE orders SET status = ? WHERE id = ?");
    $stmt->execute([$status, $id]);
    
    // Send email notification for specific statuses
    $notify_statuses = ['shipped', 'completed', 'cancelled', 'refunded'];
    if (in_array(strtolower($status), $notify_statuses)) {
        $stmt = $pdo->prepare("SELECT o.id, c.email, c.name FROM orders o JOIN customers c ON o.customer_id = c.id WHERE o.id = ?");
        $stmt->execute([$id]);
        $order_info = $stmt->fetch();
        
        if ($order_info && !empty($order_info['email'])) {
            send_order_status_email($order_info['email'], $order_info['name'], $id, $status);
        }
    }
    
    log_activity($pdo, $_SESSION['user_id'], "Updated order #$id status to $status");
    $message = "Order status updated successfully!";
    $action = 'list';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'update_payment') {
    $id = (int)$_POST['id'];
    $payment_status = sanitize($_POST['payment_status']);
    
    try {
        $pdo->beginTransaction();
        
        // 1. Update Order Payment Status
        $stmt = $pdo->prepare("UPDATE orders SET payment_status = ? WHERE id = ?");
        $stmt->execute([$payment_status, $id]);
        
        // 2. Sync with Payments Table
        $stmt = $pdo->prepare("UPDATE payments SET status = ? WHERE order_id = ?");
        $stmt->execute([$payment_status, $id]);
        
        $pdo->commit();
        log_activity($pdo, $_SESSION['user_id'], "Updated order #$id payment status to $payment_status");
        $message = "Payment status updated successfully!";
    } catch (Exception $e) {
        $pdo->rollBack();
        $message = "Error updating payment: " . $e->getMessage();
    }
    $action = 'list';
}

// Handle Order Deletion
if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    
    try {
        $pdo->beginTransaction();
        
        // Delete child records first to satisfy FK constraints
        $pdo->prepare("DELETE FROM payments WHERE order_id = ?")->execute([$id]);
        $pdo->prepare("DELETE FROM invoices WHERE order_id = ?")->execute([$id]);
        $pdo->prepare("DELETE FROM inventory_logs WHERE order_id = ?")->execute([$id]);
        $pdo->prepare("DELETE FROM orders WHERE id = ?")->execute([$id]);
        
        $pdo->commit();
        log_activity($pdo, $_SESSION['user_id'], "Permanently deleted order #$id");
        header("Location: orders.php?success=Order deleted successfully");
        exit;
    } catch (Exception $e) {
        $pdo->rollBack();
        header("Location: orders.php?error=Delete failed: " . urlencode($e->getMessage()));
        exit;
    }
}

// Updated query to hide pending_payment orders from admin panel
$orders = $pdo->query("SELECT o.*, c.name as customer_name, c.email as customer_email FROM orders o LEFT JOIN customers c ON o.customer_id = c.id WHERE o.status != 'pending_payment' ORDER BY o.id DESC")->fetchAll();

ob_start();
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h4 class="fw-bold mb-0">Order Management</h4>
</div>

<?php if ($message): ?>
    <div class="alert alert-success alert-dismissible fade show border-0 shadow-sm mb-4">
        <?php echo $message; ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<?php if ($action === 'list'): ?>
    <div class="card border-0 shadow-sm rounded-4">
        <div class="card-body p-4">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0 datatable" data-order='[[0, "desc"]]'>
                    <thead class="bg-light">
                        <tr>
                            <th class="border-0">Customer</th>
                            <th class="border-0">Total</th>
                            <th class="border-0">Payment</th>
                            <th class="border-0">Verified</th>
                            <th class="border-0">COD OTP</th>
                            <th class="border-0 text-center">Status</th>
                            <th class="border-0">Invoice</th>
                            <th class="border-0 text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="border-top-0">
                        <?php foreach ($orders as $o): ?>
                        <tr>
                            <td>
                                <div class="fw-bold"><?php echo htmlspecialchars($o['customer_name'] ?? 'Guest'); ?></div>
                                <div class="small text-muted"><?php echo htmlspecialchars($o['customer_email'] ?? ''); ?></div>
                            </td>
                            <td class="fw-bold text-dark">₹<?php echo number_format($o['total'], 2); ?></td>
                            <td>
                                <div class="text-uppercase small fw-bold text-muted"><?php echo htmlspecialchars($o['payment_method'] ?: 'N/A'); ?></div>
                                <span class="badge bg-<?php echo $o['payment_status'] === 'paid' ? 'success' : 'warning'; ?>-soft text-<?php echo $o['payment_status'] === 'paid' ? 'success' : 'warning'; ?> small px-2">
                                    <?php echo strtoupper($o['payment_status']); ?>
                                </span>
                            </td>
                            <td>
                                <?php if ($o['payment_method'] === 'cod'): ?>
                                    <span class="text-muted small">N/A</span>
                                <?php elseif ($o['payment_verified']): ?>
                                    <span class="badge bg-success-soft text-success small px-2">
                                        <i class="fas fa-check-circle me-1"></i> VERIFIED
                                    </span>
                                <?php else: ?>
                                    <span class="badge bg-danger-soft text-danger small px-2">
                                        <i class="fas fa-times-circle me-1"></i> UNVERIFIED
                                    </span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($o['payment_method'] === 'cod'): ?>
                                    <span class="badge bg-info text-white"><?php echo $o['cod_delivery_otp'] ?: 'N/A'; ?></span>
                                <?php else: ?>
                                    <span class="text-muted">-</span>
                                <?php endif; ?>
                            </td>
                            <td class="text-center">
                                <span class="status-badge <?php 
                                    echo match($o['status']) {
                                        'pending' => 'bg-warning-subtle text-warning',
                                        'pending_payment' => 'bg-danger-subtle text-danger',
                                        'processing' => 'bg-info-subtle text-info',
                                        'shipped' => 'bg-primary-subtle text-primary',
                                        'completed' => 'bg-success-subtle text-success',
                                        'cancelled', 'refunded' => 'bg-danger-subtle text-danger',
                                        default => 'bg-secondary-subtle text-secondary'
                                    };
                                ?>">
                                    <?php echo str_replace('_', ' ', $o['status']); ?>
                                </span>
                            </td>
                            <td>
                                <a href="invoices.php?order_id=<?php echo $o['id']; ?>" class="btn btn-sm btn-link text-danger p-0" title="Download Invoice">
                                    <i class="fas fa-file-pdf fa-lg"></i>
                                </a>
                            </td>
                            <td class="text-end">
                                <div class="d-flex justify-content-end gap-2">
                                    <a href="?action=view&id=<?php echo $o['id']; ?>" class="btn btn-sm btn-outline-primary rounded-pill px-3">
                                        <i class="fas fa-eye me-1"></i> View
                                    </a>
                                    <a href="?action=delete&id=<?php echo $o['id']; ?>" class="btn btn-sm btn-outline-danger rounded-pill px-3" onclick="return confirm('Are you sure you want to permanently delete this order?')">
                                        <i class="fas fa-trash me-1"></i> Delete
                                    </a>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
<?php elseif ($action === 'view'): 
    $id = (int)$_GET['id'];
    $stmt = $pdo->prepare("SELECT o.*, c.name as customer_name, c.email as customer_email, c.phone as customer_phone FROM orders o LEFT JOIN customers c ON o.customer_id = c.id WHERE o.id = ?");
    $stmt->execute([$id]);
    $o = $stmt->fetch();
    if (!$o) die("Order not found.");
    $items = json_decode($o['items'], true);
    $address = json_decode($o['shipping_address'], true);
?>
    <div class="row g-4">
        <div class="col-lg-8">
            <div class="card border-0 shadow-sm rounded-4 mb-4">
                <div class="card-header bg-transparent border-0 py-3 d-flex justify-content-between align-items-center">
                    <h6 class="mb-0 fw-bold">Order Items</h6>
                    <span class="badge bg-light text-dark border">Order #<?php echo $o['id']; ?></span>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-borderless align-middle mb-0">
                            <thead class="bg-light">
                                <tr class="small text-muted text-uppercase fw-bold">
                                    <th class="px-4 py-3">Item implementation</th>
                                    <th class="text-center py-3">Price</th>
                                    <th class="text-center py-3">Qty</th>
                                    <th class="text-end py-3 px-4">Total</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($items as $item): ?>
                                <tr class="border-bottom">
                                    <td class="px-4 py-3">
                                        <div class="fw-bold text-dark"><?php echo htmlspecialchars($item['name']); ?></div>
                                        <?php if (!empty($item['variant'])): ?>
                                            <small class="text-muted"><?php echo htmlspecialchars($item['variant']); ?></small>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-center py-3">₹<?php echo number_format($item['price'], 2); ?></td>
                                    <td class="text-center py-3"><?php echo $item['quantity']; ?></td>
                                    <td class="text-end py-3 px-4 fw-bold">₹<?php echo number_format($item['price'] * $item['quantity'], 2); ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <div class="p-4 bg-light-subtle rounded-bottom">
                        <div class="row justify-content-end">
                            <div class="col-md-5 col-lg-4">
                                <div class="d-flex justify-content-between mb-2">
                                    <span class="text-muted">Subtotal</span>
                                    <span class="fw-bold">₹<?php echo number_format($o['subtotal'], 2); ?></span>
                                </div>
                                <?php if ($o['discount_amount'] > 0): ?>
                                <div class="d-flex justify-content-between mb-2 text-danger">
                                    <span>Discount</span>
                                    <span>-₹<?php echo number_format($o['discount_amount'], 2); ?></span>
                                </div>
                                <?php endif; ?>
                                <div class="d-flex justify-content-between mb-2">
                                    <span class="text-muted">Tax / GST</span>
                                    <span>₹<?php echo number_format($o['tax_amount'], 2); ?></span>
                                </div>
                                <div class="d-flex justify-content-between mb-2">
                                    <span class="text-muted">Shipping</span>
                                    <span>₹<?php echo number_format($o['shipping_fee'], 2); ?></span>
                                </div>
                                <hr>
                                <div class="d-flex justify-content-between">
                                    <span class="h6 fw-bold mb-0">Grand Total</span>
                                    <span class="h5 fw-bold text-primary mb-0">₹<?php echo number_format($o['total'], 2); ?></span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="card border-0 shadow-sm rounded-4">
                <div class="card-header bg-transparent border-0 py-3">
                    <h6 class="mb-0 fw-bold">Shipping Information</h6>
                </div>
                <div class="card-body p-4">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="small text-muted text-uppercase fw-bold d-block mb-1">Full Name</label>
                            <span class="fw-bold text-dark"><?php echo htmlspecialchars($o['shipping_name'] ?? 'N/A'); ?></span>
                        </div>
                        <div class="col-md-6">
                            <label class="small text-muted text-uppercase fw-bold d-block mb-1">Phone</label>
                            <span class="fw-bold text-dark"><?php echo htmlspecialchars($o['shipping_phone'] ?? 'N/A'); ?></span>
                        </div>
                        <div class="col-12">
                            <label class="small text-muted text-uppercase fw-bold d-block mb-1">Address Line 1</label>
                            <span class="fw-bold text-dark"><?php echo htmlspecialchars($o['shipping_address1'] ?? 'N/A'); ?></span>
                        </div>
                        <div class="col-12">
                            <label class="small text-muted text-uppercase fw-bold d-block mb-1">Address Line 2</label>
                            <span class="fw-bold text-dark"><?php echo htmlspecialchars($o['shipping_address2'] ?: 'N/A'); ?></span>
                        </div>
                        <div class="col-md-6">
                            <label class="small text-muted text-uppercase fw-bold d-block mb-1">Landmark</label>
                            <span class="fw-bold text-dark"><?php echo htmlspecialchars($o['shipping_landmark'] ?? 'N/A'); ?></span>
                        </div>
                        <div class="col-md-6">
                            <label class="small text-muted text-uppercase fw-bold d-block mb-1">City</label>
                            <span class="fw-bold text-dark"><?php echo htmlspecialchars($o['shipping_city'] ?? 'N/A'); ?></span>
                        </div>
                        <div class="col-md-6">
                            <label class="small text-muted text-uppercase fw-bold d-block mb-1">State</label>
                            <span class="fw-bold text-dark"><?php echo htmlspecialchars($o['shipping_state'] ?? 'N/A'); ?></span>
                        </div>
                        <div class="col-md-6">
                            <label class="small text-muted text-uppercase fw-bold d-block mb-1">PIN Code</label>
                            <span class="fw-bold text-dark"><?php echo htmlspecialchars($o['shipping_pincode'] ?? 'N/A'); ?></span>
                        </div>
                        <?php if (!empty($o['order_notes'])): ?>
                        <div class="col-12 mt-3 p-3 bg-light rounded-3">
                            <label class="small text-muted text-uppercase fw-bold d-block mb-1">Order Notes</label>
                            <p class="mb-0 small italic"><?php echo nl2br(htmlspecialchars($o['order_notes'])); ?></p>
                        </div>
                        <?php endif; ?>
                    </div>
                    <?php if (!empty($o['tracking_id'])): ?>
                        <div class="mt-4">
                            <span class="text-muted small text-uppercase fw-bold d-block mb-1">Tracking ID</span>
                            <span class="badge bg-dark px-3 py-2 fs-6"><?php echo htmlspecialchars($o['tracking_id']); ?></span>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
        <div class="col-lg-4">
            <div class="card border-0 shadow-sm rounded-4 mb-4">
                <div class="card-header bg-transparent border-0 py-3">
                    <h6 class="mb-0 fw-bold">Customer Profile</h6>
                </div>
                <div class="card-body p-4">
                    <div class="d-flex align-items-center mb-3">
                        <div class="bg-primary-subtle text-primary rounded-circle p-3 me-3">
                            <i class="fas fa-user"></i>
                        </div>
                        <div>
                            <div class="fw-bold"><?php echo htmlspecialchars($o['customer_name'] ?? 'Guest'); ?></div>
                            <div class="small text-muted">ID: #<?php echo $o['customer_id'] ?: '0'; ?></div>
                        </div>
                    </div>
                    <hr class="my-3 opacity-25">
                    <div class="mb-2 small"><i class="fas fa-envelope me-2 text-muted"></i> <?php echo htmlspecialchars($o['customer_email'] ?? 'N/A'); ?></div>
                    <div class="mb-0 small"><i class="fas fa-phone me-2 text-muted"></i> <?php echo htmlspecialchars($o['customer_phone'] ?? 'N/A'); ?></div>
                </div>
            </div>
            
            <div class="card border-0 shadow-sm rounded-4 mb-4 overflow-hidden">
                <div class="card-header bg-primary text-white border-0 py-3">
                    <h6 class="mb-0 fw-bold">Payment Details</h6>
                </div>
                <div class="card-body p-4">
                    <div class="mb-3">
                        <label class="small text-muted text-uppercase fw-bold d-block mb-1">Method</label>
                        <span class="text-uppercase fw-bold text-dark"><?php echo htmlspecialchars($o['payment_method']); ?></span>
                    </div>
                    <form method="POST" action="?action=update_payment">
                        <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                        <input type="hidden" name="id" value="<?php echo $o['id']; ?>">
                        <div class="mb-3">
                            <label class="small text-muted text-uppercase fw-bold d-block mb-1">Update Status</label>
                            <div class="input-group">
                                <select name="payment_status" class="form-select border-2 small">
                                    <option value="pending" <?php echo $o['payment_status'] == 'pending' ? 'selected' : ''; ?>>Pending</option>
                                    <option value="paid" <?php echo $o['payment_status'] == 'paid' ? 'selected' : ''; ?>>Paid</option>
                                    <option value="failed" <?php echo $o['payment_status'] == 'failed' ? 'selected' : ''; ?>>Failed</option>
                                    <option value="refunded" <?php echo $o['payment_status'] == 'refunded' ? 'selected' : ''; ?>>Refunded</option>
                                </select>
                                <button type="submit" class="btn btn-dark btn-sm px-3">Update</button>
                            </div>
                        </div>
                    </form>
                    <div class="mb-0">
                        <label class="small text-muted text-uppercase fw-bold d-block mb-1">Current Status</label>
                        <span class="badge bg-<?php echo $o['payment_status'] == 'paid' ? 'success' : 'warning'; ?>-soft text-<?php echo $o['payment_status'] == 'paid' ? 'success' : 'warning'; ?> px-3 py-2 fw-bold text-uppercase">
                            <?php echo htmlspecialchars($o['payment_status']); ?>
                        </span>
                    </div>
                </div>
            </div>

            <div class="card border-0 shadow-sm rounded-4">
                <div class="card-header bg-transparent border-0 py-3">
                    <h6 class="mb-0 fw-bold text-primary">Order Status Control</h6>
                </div>
                <div class="card-body p-4">
                    <form method="POST" action="?action=update_status">
                        <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                        <input type="hidden" name="id" value="<?php echo $o['id']; ?>">
                        <div class="mb-3">
                            <label class="form-label small fw-bold">Set Current Status</label>
                            <select name="status" class="form-select border-2">
                                <?php foreach(['pending', 'processing', 'shipped', 'completed', 'cancelled', 'refunded'] as $s): ?>
                                    <option value="<?php echo $s; ?>" <?php echo $o['status'] == $s ? 'selected' : ''; ?>><?php echo ucfirst($s); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <button type="submit" class="btn btn-primary w-100 py-2 fw-bold shadow-sm">Update Order</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
<?php endif; ?>

<style>
    .bg-success-soft { background-color: rgba(25, 135, 84, 0.1); color: #198754 !important; }
    .bg-warning-soft { background-color: rgba(255, 193, 7, 0.1); color: #9a6e03 !important; }
</style>

<?php
$content = ob_get_clean();
admin_layout("Orders", $content);
?>
