<?php
require_once '../includes/auth.php';
require_once '../includes/functions.php';

check_customer_auth();

$user_id = $_SESSION['user_id'];
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch();

// Fetch customer details
$stmt = $pdo->prepare("SELECT * FROM customers WHERE email = ?");
$stmt->execute([$user['email']]);
$customer = $stmt->fetch();
$addresses = $customer ? json_decode($customer['addresses'], true) : ['billing' => '', 'shipping' => ''];

// Fetch order history
$stmt = $pdo->prepare("SELECT * FROM orders WHERE customer_id = ? ORDER BY id DESC");
$stmt->execute([$customer['id'] ?? 0]);
$orders = $stmt->fetchAll();

$success = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // CSRF check
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        die('CSRF token validation failed');
    }

    if (isset($_POST['change_password'])) {
        $current_password = $_POST['current_password'];
        $new_password = $_POST['new_password'];
        $confirm_password = $_POST['confirm_password'];

        if (password_verify($current_password, $user['password'])) {
            if ($new_password === $confirm_password) {
                if (strlen($new_password) >= 8) {
                    $hashed = password_hash($new_password, PASSWORD_DEFAULT);
                    $pdo->prepare("UPDATE users SET password = ? WHERE id = ?")->execute([$hashed, $user_id]);
                    $success = "Password updated successfully!";
                } else {
                    $error = "New password must be at least 8 characters.";
                }
            } else {
                $error = "New passwords do not match.";
            }
        } else {
            $error = "Current password is incorrect.";
        }
    } elseif (isset($_POST['update_address'])) {
        $billing = sanitize($_POST['billing_address']);
        $shipping = sanitize($_POST['shipping_address']);
        $new_addresses = json_encode(['billing' => $billing, 'shipping' => $shipping]);
        
        if ($customer) {
            $stmt = $pdo->prepare("UPDATE customers SET addresses = ? WHERE id = ?");
            $stmt->execute([$new_addresses, $customer['id']]);
        } else {
            $stmt = $pdo->prepare("INSERT INTO customers (name, email, addresses) VALUES (?, ?, ?)");
            $stmt->execute([$user['username'], $user['email'], $new_addresses]);
        }
        $success = "Address updated successfully!";
        // Refresh customer data
        $stmt = $pdo->prepare("SELECT * FROM customers WHERE email = ?");
        $stmt->execute([$user['email']]);
        $customer = $stmt->fetch();
        $addresses = json_decode($customer['addresses'], true);
    }
}

// Generate new CSRF token
$_SESSION['csrf_token'] = bin2hex(random_bytes(32));
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Profile - eCommerce Appalam</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background-color: #f4f6f9; }
        .navbar-brand { font-weight: 800; color: #ff4757 !important; }
        .card { border: none; border-radius: 15px; box-shadow: 0 4px 15px rgba(0,0,0,0.05); }
        .btn-primary { background-color: #ff4757; border-color: #ff4757; }
        .btn-primary:hover { background-color: #e84118; border-color: #e84118; }
        .status-badge { font-size: 0.75rem; font-weight: 700; text-transform: uppercase; padding: 5px 12px; border-radius: 20px; }
    </style>
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-white bg-white shadow-sm py-3 mb-5">
        <div class="container">
            <a class="navbar-brand" href="#">Appalam <span class="text-dark">Store</span></a>
            <div class="ms-auto d-flex align-items-center">
                <span class="me-3 text-muted small">Hello, <strong><?php echo htmlspecialchars($user['username']); ?></strong></span>
                <a href="logout.php" class="btn btn-outline-danger btn-sm px-4">Logout</a>
            </div>
        </div>
    </nav>

    <div class="container pb-5">
        <?php if ($success): ?>
            <div class="alert alert-success border-0 shadow-sm mb-4"><?php echo $success; ?></div>
        <?php endif; ?>
        <?php if ($error): ?>
            <div class="alert alert-danger border-0 shadow-sm mb-4"><?php echo $error; ?></div>
        <?php endif; ?>

        <div class="row g-4">
            <!-- Sidebar Info -->
            <div class="col-lg-4">
                <div class="card p-4 mb-4">
                    <h5 class="fw-bold mb-4">Profile Details</h5>
                    <div class="mb-3">
                        <label class="text-muted small text-uppercase fw-bold">Username</label>
                        <p class="mb-0 fw-semibold text-dark"><?php echo htmlspecialchars($user['username']); ?></p>
                    </div>
                    <div class="mb-3">
                        <label class="text-muted small text-uppercase fw-bold">Email</label>
                        <p class="mb-0 fw-semibold text-dark"><?php echo htmlspecialchars($user['email']); ?></p>
                    </div>
                    <div>
                        <label class="text-muted small text-uppercase fw-bold">Member Since</label>
                        <p class="mb-0 fw-semibold text-dark"><?php echo date('M d, Y', strtotime($user['created_at'])); ?></p>
                    </div>
                </div>

                <!-- Change Password -->
                <div class="card p-4 mb-4">
                    <h5 class="fw-bold mb-4">Change Password</h5>
                    <form method="POST">
                        <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                        <input type="hidden" name="change_password" value="1">
                        <div class="mb-3">
                            <input type="password" name="current_password" placeholder="Current Password" required class="form-control form-control-sm">
                        </div>
                        <div class="mb-3">
                            <input type="password" name="new_password" placeholder="New Password" required class="form-control form-control-sm">
                        </div>
                        <div class="mb-3">
                            <input type="password" name="confirm_password" placeholder="Confirm New Password" required class="form-control form-control-sm">
                        </div>
                        <button type="submit" class="btn btn-primary btn-sm w-100 py-2 fw-bold">Update Password</button>
                    </form>
                </div>

                <!-- Address Management -->
                <div class="card p-4">
                    <h5 class="fw-bold mb-4">Addresses</h5>
                    <form method="POST">
                        <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                        <input type="hidden" name="update_address" value="1">
                        <div class="mb-3">
                            <label class="form-label small fw-bold">Billing Address</label>
                            <textarea name="billing_address" required class="form-control form-control-sm" rows="3"><?php echo htmlspecialchars($addresses['billing'] ?? ''); ?></textarea>
                        </div>
                        <div class="mb-3">
                            <label class="form-label small fw-bold">Shipping Address</label>
                            <textarea name="shipping_address" required class="form-control form-control-sm" rows="3"><?php echo htmlspecialchars($addresses['shipping'] ?? ''); ?></textarea>
                        </div>
                        <button type="submit" class="btn btn-dark btn-sm w-100 py-2 fw-bold">Save Changes</button>
                    </form>
                </div>
            </div>

            <!-- Order History -->
            <div class="col-lg-8">
                <div class="card p-4 min-vh-100">
                    <h5 class="fw-bold mb-4">Order History</h5>
                    <?php if (empty($orders)): ?>
                        <div class="text-center py-5">
                            <img src="https://cdn-icons-png.flaticon.com/512/2038/2038854.png" width="80" class="opacity-25 mb-3">
                            <p class="text-muted">You haven't placed any orders yet.</p>
                            <a href="#" class="btn btn-primary btn-sm px-4">Start Shopping</a>
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-hover align-middle">
                                <thead class="table-light">
                                    <tr class="small text-muted text-uppercase fw-bold">
                                        <th class="border-0">ID</th>
                                        <th class="border-0">Date</th>
                                        <th class="border-0">Amount</th>
                                        <th class="border-0 text-center">Status</th>
                                        <th class="border-0 text-end">Payment</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($orders as $order): ?>
                                        <tr>
                                            <td class="fw-bold text-dark">#<?php echo $order['id']; ?></td>
                                            <td class="text-muted small"><?php echo date('M d, Y', strtotime($order['created_at'])); ?></td>
                                            <td class="fw-bold">₹<?php echo number_format($order['total'], 2); ?></td>
                                            <td class="text-center">
                                                <span class="status-badge <?php echo match($order['status']) {
                                                    'pending' => 'bg-warning-subtle text-warning',
                                                    'processing' => 'bg-info-subtle text-info',
                                                    'completed' => 'bg-success-subtle text-success',
                                                    'cancelled', 'refunded' => 'bg-danger-subtle text-danger',
                                                    default => 'bg-secondary-subtle text-secondary'
                                                }; ?>">
                                                    <?php echo $order['status']; ?>
                                                </span>
                                            </td>
                                            <td class="text-end">
                                                <span class="small fw-bold <?php echo $order['payment_status'] === 'paid' ? 'text-success' : 'text-danger'; ?>">
                                                    <?php echo strtoupper($order['payment_status']); ?>
                                                </span>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
