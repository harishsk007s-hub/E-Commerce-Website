<?php
require_once '../includes/db_connection.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';
require_once 'layout.php';

check_admin_auth();
require_role(['super-admin', 'manager']);

$action = $_GET['action'] ?? 'list';
$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'add') {
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone']);
    $password = $_POST['password'];
    
    if (empty($name) || empty($email) || empty($password)) {
        $error = "Name, Email and Password are required.";
    } else {
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        try {
            // Check if email already exists
            $stmt = $pdo->prepare("SELECT id FROM customers WHERE email = ?");
            $stmt->execute([$email]);
            if ($stmt->fetch()) {
                $error = "Email already registered.";
            } else {
                // Determine if role column exists
                $res = $pdo->query("SHOW COLUMNS FROM customers LIKE 'role'");
                $has_role = $res->rowCount() > 0;
                
                if ($has_role) {
                    $stmt = $pdo->prepare("INSERT INTO customers (name, email, phone, password_hash, role) VALUES (?, ?, ?, ?, 'customer')");
                    $stmt->execute([$name, $email, $phone, $hashed_password]);
                } else {
                    $stmt = $pdo->prepare("INSERT INTO customers (name, email, phone, password_hash) VALUES (?, ?, ?, ?)");
                    $stmt->execute([$name, $email, $phone, $hashed_password]);
                }
                $message = "New customer created successfully!";
                $action = 'list';
                
                // Refresh customer list
                try {
                    $customers = $pdo->query("SELECT * FROM customers WHERE role = 'customer' OR role IS NULL ORDER BY created_at DESC")->fetchAll();
                } catch (Exception $e) {
                    $customers = $pdo->query("SELECT * FROM customers ORDER BY created_at DESC")->fetchAll();
                }
            }
        } catch (Exception $e) {
            $error = "Error creating customer: " . $e->getMessage();
        }
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'reset_password') {
    $id = (int)$_POST['id'];
    $new_password = bin2hex(random_bytes(4)); // Random 8 chars
    $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
    
    $stmt = $pdo->prepare("UPDATE customers SET password_hash = ? WHERE id = ?");
    $stmt->execute([$hashed_password, $id]);
    
    $message = "Password reset successfully! New password: <strong>$new_password</strong>";
}

if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    
    try {
        $pdo->beginTransaction();
        
        // 1. Delete associated orders first (cascading cleanup)
        // Note: orders table has customer_id as FK, but we should handle payments/etc linked to those orders
        $stmt = $pdo->prepare("SELECT id FROM orders WHERE customer_id = ?");
        $stmt->execute([$id]);
        $order_ids = $stmt->fetchAll(PDO::FETCH_COLUMN);
        
        foreach ($order_ids as $oid) {
            $pdo->prepare("DELETE FROM payments WHERE order_id = ?")->execute([$oid]);
            $pdo->prepare("DELETE FROM invoices WHERE order_id = ?")->execute([$oid]);
            $pdo->prepare("DELETE FROM inventory_logs WHERE order_id = ?")->execute([$oid]);
        }
        
        $pdo->prepare("DELETE FROM orders WHERE customer_id = ?")->execute([$id]);
        $pdo->prepare("DELETE FROM carts WHERE user_id = ?")->execute([$id]);
        
        // 2. Delete the customer
        $pdo->prepare("DELETE FROM customers WHERE id = ?")->execute([$id]);
        
        $pdo->commit();
        log_activity($pdo, $_SESSION['user_id'], "Permanently deleted customer #$id and all their data");
        header("Location: customers.php?success=Customer deleted successfully");
        exit;
    } catch (Exception $e) {
        $pdo->rollBack();
        header("Location: customers.php?error=Delete failed: " . urlencode($e->getMessage()));
        exit;
    }
}

if ($action === 'toggle_status') {
    $id = (int)$_GET['id'];
    $pdo->prepare("UPDATE customers SET status = NOT status WHERE id = ?")->execute([$id]);
    $message = "Customer status updated!";
    $action = 'list';
}

// Check for role column before querying to avoid errors if it doesn't exist yet
try {
    $customers = $pdo->query("SELECT * FROM customers WHERE (role = 'customer' OR role IS NULL) AND email NOT LIKE '%admin%' AND email NOT LIKE '%developer%' ORDER BY created_at DESC")->fetchAll();
} catch (Exception $e) {
    // Fallback if role column doesn't exist
    $customers = $pdo->query("SELECT * FROM customers WHERE email NOT LIKE '%admin%' AND email NOT LIKE '%developer%' ORDER BY created_at DESC")->fetchAll();
}

ob_start();
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h4 class="fw-bold mb-0">Customer Management</h4>
    <a href="?action=add" class="btn btn-primary rounded-pill px-4 shadow-sm">
        <i class="fas fa-plus me-2"></i>Add New Customer
    </a>
</div>

<?php if ($message): ?>
    <div class="alert alert-success alert-dismissible fade show border-0 shadow-sm mb-4"><?php echo $message; ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
<?php endif; ?>

<?php if ($error): ?>
    <div class="alert alert-danger alert-dismissible fade show border-0 shadow-sm mb-4"><?php echo $error; ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
<?php endif; ?>

<?php if ($action === 'list'): ?>
    <div class="card border-0 shadow-sm rounded-4">
        <div class="card-body p-4">
            <div class="table-responsive">
                <table class="table table-hover align-middle datatable">
                    <thead class="bg-light">
                        <tr>
                            <th class="border-0">ID</th>
                            <th class="border-0">Customer Info</th>
                            <th class="border-0">Contact</th>
                            <th class="border-0 text-center">Orders</th>
                            <th class="border-0">Joined Date</th>
                            <th class="border-0 text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="border-top-0">
                        <?php foreach ($customers as $c): ?>
                        <tr>
                            <td class="fw-bold text-primary">#<?php echo $c['id']; ?></td>
                            <td>
                                <div class="fw-bold text-dark"><?php echo htmlspecialchars($c['name']); ?></div>
                                <div class="small text-muted"><?php echo htmlspecialchars($c['email']); ?></div>
                            </td>
                            <td>
                                <div class="small fw-semibold"><?php echo htmlspecialchars($c['phone'] ?: 'No Phone'); ?></div>
                            </td>
                            <td class="text-center">
                                <span class="badge bg-light text-dark border px-3"><?php echo $c['orders_count']; ?></span>
                            </td>
                            <td class="small text-muted"><?php echo date('M d, Y', strtotime($c['created_at'])); ?></td>
                            <td class="text-end">
                                <div class="d-flex justify-content-end gap-2">
                                    <a href="?action=view&id=<?php echo $c['id']; ?>" class="btn btn-sm btn-outline-primary rounded-pill px-3"><i class="fas fa-eye"></i></a>
                                    <form method="POST" action="?action=reset_password" class="d-inline" onsubmit="return confirm('Reset this customer\'s password?')">
                                        <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                                        <input type="hidden" name="id" value="<?php echo $c['id']; ?>">
                                        <button type="submit" class="btn btn-sm btn-outline-warning rounded-pill px-3"><i class="fas fa-key"></i></button>
                                    </form>
                                    <a href="?action=delete&id=<?php echo $c['id']; ?>" class="btn btn-sm btn-outline-danger rounded-pill px-3" onclick="return confirm('Are you sure you want to permanently delete this customer and ALL their data (orders, etc)?')"><i class="fas fa-trash"></i></a>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
<?php elseif ($action === 'add'): ?>
    <div class="row justify-content-center">
        <div class="col-md-6">
            <div class="card border-0 shadow-sm rounded-4">
                <div class="card-header bg-white p-4 border-0">
                    <h5 class="fw-bold mb-0">Add New Customer</h5>
                </div>
                <div class="card-body p-4 pt-0">
                    <form method="POST" action="?action=add">
                        <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                        <div class="mb-3">
                            <label class="form-label small fw-bold">Full Name</label>
                            <input type="text" name="name" class="form-control rounded-3" required placeholder="John Doe">
                        </div>
                        <div class="mb-3">
                            <label class="form-label small fw-bold">Email Address</label>
                            <input type="email" name="email" class="form-control rounded-3" required placeholder="john@example.com">
                        </div>
                        <div class="mb-3">
                            <label class="form-label small fw-bold">Phone Number</label>
                            <input type="text" name="phone" class="form-control rounded-3" placeholder="+91 9876543210">
                        </div>
                        <div class="mb-4">
                            <label class="form-label small fw-bold">Password</label>
                            <input type="password" name="password" class="form-control rounded-3" required placeholder="********">
                        </div>
                        <div class="d-flex gap-2">
                            <button type="submit" class="btn btn-primary rounded-pill px-4">Create Customer</button>
                            <a href="?action=list" class="btn btn-light rounded-pill px-4">Cancel</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
<?php elseif ($action === 'view'): 
    $id = (int)$_GET['id'];
    $stmt = $pdo->prepare("SELECT * FROM customers WHERE id = ?");
    $stmt->execute([$id]);
    $c = $stmt->fetch();
    if (!$c) die("Customer not found.");
    
    $orders = $pdo->prepare("SELECT * FROM orders WHERE customer_id = ? ORDER BY created_at DESC");
    $orders->execute([$id]);
    $customer_orders = $orders->fetchAll();
?>
    <div class="row">
        <div class="col-md-4">
            <div class="card shadow-sm border-0 mb-4">
                <div class="card-header bg-white py-3 text-center">
                    <div class="rounded-circle bg-light d-inline-flex align-items-center justify-content-center mb-3" style="width: 80px; height: 80px;">
                        <i class="fas fa-user fa-3x text-secondary"></i>
                    </div>
                    <h5 class="m-0"><?php echo htmlspecialchars($c['name']); ?></h5>
                    <p class="text-muted"><?php echo htmlspecialchars($c['email']); ?></p>
                </div>
                <div class="card-body">
                    <p><strong>Phone:</strong> <?php echo htmlspecialchars($c['phone'] ?: 'N/A'); ?></p>
                    <p><strong>Joined:</strong> <?php echo date('M d, Y', strtotime($c['created_at'])); ?></p>
                    <p><strong>Total Orders:</strong> <?php echo $c['orders_count']; ?></p>
                </div>
            </div>
        </div>
        
        <div class="col-md-8">
            <div class="card shadow-sm border-0">
                <div class="card-header bg-white py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Order History</h6>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle">
                            <thead class="table-light">
                                <tr>
                                    <th>Order ID</th>
                                    <th>Total</th>
                                    <th>Status</th>
                                    <th>Date</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($customer_orders as $o): ?>
                                <tr>
                                    <td>#<?php echo $o['id']; ?></td>
                                    <td>₹<?php echo number_format($o['total'], 2); ?></td>
                                    <td>
                                        <span class="badge bg-<?php 
                                            echo match($o['status']) {
                                                'pending' => 'warning',
                                                'processing' => 'info',
                                                'shipped' => 'primary',
                                                'completed' => 'success',
                                                'cancelled', 'refunded' => 'danger',
                                                default => 'secondary'
                                            };
                                        ?> text-capitalize">
                                            <?php echo $o['status']; ?>
                                        </span>
                                    </td>
                                    <td><?php echo date('M d, Y', strtotime($o['created_at'])); ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
<?php endif; ?>

<?php
$content = ob_get_clean();
admin_layout("Customers", $content);
?>
