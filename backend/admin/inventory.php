<?php
require_once '../includes/db_connection.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';
require_once 'layout.php';

check_admin_auth();
require_role(['super-admin', 'manager', 'inventory']);

$action = $_GET['action'] ?? 'list';
$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'adjust') {
    $product_id = (int)$_POST['product_id'];
    $adjustment = (int)$_POST['adjustment'];
    $reason = sanitize($_POST['reason']);
    
    log_inventory($pdo, $product_id, $adjustment, 'manual');
    log_activity($pdo, $_SESSION['user_id'], "Adjusted product ID #$product_id stock by $adjustment ($reason)");
    
    $message = "Inventory adjusted successfully!";
}

$products = $pdo->query("SELECT p.*, c.name as category_name FROM products p LEFT JOIN categories c ON p.category_id = c.id ORDER BY p.stock ASC")->fetchAll();
$inventory_logs = $pdo->query("SELECT l.*, p.name as product_name FROM inventory_logs l JOIN products p ON l.product_id = p.id ORDER BY l.created_at DESC LIMIT 50")->fetchAll();

ob_start();
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h2>Inventory Management</h2>
</div>

<?php if ($message): ?>
    <div class="alert alert-success alert-dismissible fade show"><?php echo $message; ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
<?php endif; ?>

<div class="row">
    <div class="col-md-8">
        <div class="card shadow-sm border-0 mb-4">
            <div class="card-header bg-white py-3">
                <h6 class="m-0 font-weight-bold text-primary">Stock Levels</h6>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover align-middle">
                        <thead class="table-light">
                            <tr>
                                <th>Product</th>
                                <th>SKU</th>
                                <th>Stock</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($products as $p): ?>
                            <tr>
                                <td>
                                    <div><strong><?php echo htmlspecialchars($p['name']); ?></strong></div>
                                    <small class="text-muted"><?php echo htmlspecialchars($p['category_name']); ?></small>
                                </td>
                                <td><?php echo htmlspecialchars($p['sku']); ?></td>
                                <td>
                                    <span class="badge bg-<?php echo $p['stock'] < 10 ? 'danger' : 'success'; ?>">
                                        <?php echo $p['stock']; ?>
                                    </span>
                                </td>
                                <td>
                                    <?php if ($p['stock'] < 10): ?>
                                        <span class="text-danger fw-bold"><i class="fas fa-exclamation-triangle me-1"></i>Low Stock</span>
                                    <?php else: ?>
                                        <span class="text-success">Good</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <button class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#adjustModal<?php echo $p['id']; ?>">
                                        <i class="fas fa-edit me-1"></i>Adjust
                                    </button>
                                </td>
                            </tr>
                            
                            <!-- Adjust Modal -->
                            <div class="modal fade" id="adjustModal<?php echo $p['id']; ?>" tabindex="-1">
                                <div class="modal-dialog">
                                    <div class="modal-content">
                                        <form method="POST" action="?action=adjust">
                                            <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                                            <div class="modal-header">
                                                <h5 class="modal-title">Adjust Inventory - <?php echo htmlspecialchars($p['name']); ?></h5>
                                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                            </div>
                                            <div class="modal-body">
                                                <input type="hidden" name="product_id" value="<?php echo $p['id']; ?>">
                                                <div class="mb-3">
                                                    <label class="form-label">Current Stock: <strong><?php echo $p['stock']; ?></strong></label>
                                                </div>
                                                <div class="mb-3">
                                                    <label class="form-label">Adjustment Quantity</label>
                                                    <input type="number" name="adjustment" class="form-control" placeholder="e.g. 10 or -5" required>
                                                    <small class="text-muted">Use positive numbers to add, negative numbers to remove stock.</small>
                                                </div>
                                                <div class="mb-3">
                                                    <label class="form-label">Reason</label>
                                                    <input type="text" name="reason" class="form-control" placeholder="Manual adjustment, restocking, etc.">
                                                </div>
                                            </div>
                                            <div class="modal-footer">
                                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                                                <button type="submit" class="btn btn-primary">Save Changes</button>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-md-4">
        <div class="card shadow-sm border-0">
            <div class="card-header bg-white py-3">
                <h6 class="m-0 font-weight-bold text-primary">Recent Inventory Logs</h6>
            </div>
            <div class="card-body">
                <div class="list-group list-group-flush small">
                    <?php foreach ($inventory_logs as $log): ?>
                    <div class="list-group-item px-0">
                        <div class="d-flex w-100 justify-content-between">
                            <h6 class="mb-1 fw-bold"><?php echo htmlspecialchars($log['product_name']); ?></h6>
                            <small class="text-<?php echo $log['quantity_change'] > 0 ? 'success' : 'danger'; ?> fw-bold">
                                <?php echo ($log['quantity_change'] > 0 ? '+' : '') . $log['quantity_change']; ?>
                            </small>
                        </div>
                        <p class="mb-1 text-muted text-capitalize"><?php echo $log['type']; ?> <?php echo $log['order_id'] ? " (Order #{$log['order_id']})" : ""; ?></p>
                        <small class="text-muted"><?php echo date('M d, H:i', strtotime($log['created_at'])); ?></small>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
$content = ob_get_clean();
admin_layout("Inventory", $content);
?>
