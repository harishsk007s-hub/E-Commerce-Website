<?php
require_once '../includes/db_connection.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';
require_once 'layout.php';

check_admin_auth();
require_role(['super-admin', 'manager']);

$action = $_GET['action'] ?? 'list';
$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($action === 'create' || $action === 'edit') {
        $code = sanitize($_POST['code']);
        $discount_type = sanitize($_POST['discount_type']);
        $discount_value = (float)$_POST['discount_value'];
        $expiry = $_POST['expiry'];
        $usage_limit = (int)$_POST['usage_limit'];
        $status = (int)$_POST['status'];
        
        if ($action === 'create') {
            $stmt = $pdo->prepare("INSERT INTO coupons (code, discount_type, discount_value, expiry, usage_limit, status) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->execute([$code, $discount_type, $discount_value, $expiry, $usage_limit, $status]);
            $message = "Coupon created successfully!";
        } else {
            $id = (int)$_POST['id'];
            $stmt = $pdo->prepare("UPDATE coupons SET code=?, discount_type=?, discount_value=?, expiry=?, usage_limit=?, status=? WHERE id=?");
            $stmt->execute([$code, $discount_type, $discount_value, $expiry, $usage_limit, $status, $id]);
            $message = "Coupon updated successfully!";
        }
        $action = 'list';
    }
}

if ($action === 'delete') {
    $id = (int)$_GET['id'];
    $pdo->prepare("DELETE FROM coupons WHERE id = ?")->execute([$id]);
    $message = "Coupon deleted successfully!";
    $action = 'list';
}

$coupons = $pdo->query("SELECT * FROM coupons ORDER BY created_at DESC")->fetchAll();

ob_start();
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h2>Coupons</h2>
    <?php if ($action === 'list'): ?>
        <a href="?action=create" class="btn btn-primary"><i class="fas fa-plus me-2"></i>Add Coupon</a>
    <?php endif; ?>
</div>

<?php if ($message): ?>
    <div class="alert alert-success alert-dismissible fade show"><?php echo $message; ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
<?php endif; ?>

<?php if ($action === 'list'): ?>
    <div class="card shadow-sm border-0">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead class="table-light">
                        <tr>
                            <th>Code</th>
                            <th>Discount</th>
                            <th>Type</th>
                            <th>Expiry</th>
                            <th>Usage</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($coupons as $c): ?>
                        <tr>
                            <td><code class="fs-6"><?php echo htmlspecialchars($c['code']); ?></code></td>
                            <td><?php echo $c['discount_value']; ?><?php echo $c['discount_type'] == 'percentage' ? '%' : '₹'; ?></td>
                            <td class="text-capitalize"><?php echo $c['discount_type']; ?></td>
                            <td><?php echo $c['expiry'] ? date('M d, Y', strtotime($c['expiry'])) : 'No Expiry'; ?></td>
                            <td><?php echo $c['used_count']; ?> / <?php echo $c['usage_limit'] ?: '∞'; ?></td>
                            <td>
                                <span class="badge bg-<?php echo $c['status'] ? 'success' : 'danger'; ?>">
                                    <?php echo $c['status'] ? 'Active' : 'Inactive'; ?>
                                </span>
                            </td>
                            <td>
                                <a href="?action=edit&id=<?php echo $c['id']; ?>" class="btn btn-sm btn-outline-primary"><i class="fas fa-edit"></i></a>
                                <a href="?action=delete&id=<?php echo $c['id']; ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('Delete this coupon?')"><i class="fas fa-trash"></i></a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
<?php elseif ($action === 'create' || $action === 'edit'): 
    $c = ['code'=>'', 'discount_type'=>'percentage', 'discount_value'=>'', 'expiry'=>'', 'usage_limit'=>0, 'status'=>1, 'id'=>0];
    if ($action === 'edit') {
        $id = (int)$_GET['id'];
        $stmt = $pdo->prepare("SELECT * FROM coupons WHERE id = ?");
        $stmt->execute([$id]);
        $c = $stmt->fetch();
    }
?>
    <div class="card shadow-sm border-0">
        <div class="card-body p-4">
            <form method="POST">
                <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                <input type="hidden" name="id" value="<?php echo $c['id']; ?>">
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Coupon Code</label>
                        <input type="text" name="code" class="form-control" value="<?php echo htmlspecialchars($c['code']); ?>" required style="text-transform: uppercase;">
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Discount Type</label>
                        <select name="discount_type" class="form-select" required>
                            <option value="percentage" <?php echo $c['discount_type'] == 'percentage' ? 'selected' : ''; ?>>Percentage (%)</option>
                            <option value="fixed" <?php echo $c['discount_type'] == 'fixed' ? 'selected' : ''; ?>>Fixed Amount (₹)</option>
                            <option value="bogo" <?php echo $c['discount_type'] == 'bogo' ? 'selected' : ''; ?>>Buy One Get One (BOGO)</option>
                        </select>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Discount Value</label>
                        <input type="number" step="0.01" name="discount_value" class="form-control" value="<?php echo $c['discount_value']; ?>" required>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Expiry Date</label>
                        <input type="date" name="expiry" class="form-control" value="<?php echo $c['expiry']; ?>">
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Usage Limit</label>
                        <input type="number" name="usage_limit" class="form-control" value="<?php echo $c['usage_limit']; ?>" required>
                        <small class="text-muted">Set to 0 for unlimited usage.</small>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Status</label>
                        <select name="status" class="form-select">
                            <option value="1" <?php echo $c['status'] == 1 ? 'selected' : ''; ?>>Active</option>
                            <option value="0" <?php echo $c['status'] == 0 ? 'selected' : ''; ?>>Inactive</option>
                        </select>
                    </div>
                </div>
                <hr>
                <div class="d-flex justify-content-end gap-2">
                    <a href="?action=list" class="btn btn-secondary">Cancel</a>
                    <button type="submit" class="btn btn-primary">Save Coupon</button>
                </div>
            </form>
        </div>
    </div>
<?php endif; ?>

<?php
$content = ob_get_clean();
admin_layout("Coupons", $content);
?>
