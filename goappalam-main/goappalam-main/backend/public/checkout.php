<?php
require_once __DIR__ . '/../includes/user-auth.php';
require_once __DIR__ . '/../includes/checkout-validator.php';

require_login();

$user = get_user_data($pdo, $_SESSION['user_id']);
$errors = [];
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $csrf_token = $_POST['csrf_token'];
    if (!check_csrf($csrf_token)) {
        die("CSRF verification failed.");
    }

    $form_data = [
        'full_name' => trim($_POST['full_name']),
        'phone' => trim($_POST['phone']),
        'alternative_phone' => trim($_POST['alternative_phone'] ?? ''),
        'address1' => trim($_POST['address1']),
        'address2' => trim($_POST['address2']),
        'landmark' => trim($_POST['landmark']),
        'city' => trim($_POST['city']),
        'state' => trim($_POST['state']),
        'pincode' => trim($_POST['pincode']),
        'payment_method' => $_POST['payment_method'],
        'order_notes' => trim($_POST['order_notes'] ?? '')
    ];

    $errors = validate_checkout($form_data);

    if (empty($errors)) {
        try {
            $pdo->beginTransaction();

            // Create order (Simulated, linking to products would normally be here)
            // For this simulation, we'll create a dummy order
            $stmt = $pdo->prepare("INSERT INTO orders (customer_id, status, subtotal, total, items, 
                shipping_name, shipping_phone, alternative_phone, shipping_address1, shipping_address2, shipping_landmark, 
                shipping_city, shipping_state, shipping_pincode, order_notes, payment_method) 
                VALUES (?, 'pending', 0, 0, '[]', ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            
            $stmt->execute([
                $_SESSION['user_id'],
                $form_data['full_name'],
                $form_data['phone'],
                $form_data['alternative_phone'],
                $form_data['address1'],
                $form_data['address2'],
                $form_data['landmark'],
                $form_data['city'],
                $form_data['state'],
                $form_data['pincode'],
                $form_data['order_notes'],
                $form_data['payment_method']
            ]);

            $pdo->commit();
            $success = true;
        } catch (Exception $e) {
            $pdo->rollBack();
            $errors['general'] = "Failed to place order: " . $e->getMessage();
        }
    }
}

$states = ['Andhra Pradesh', 'Arunachal Pradesh', 'Assam', 'Bihar', 'Chhattisgarh', 'Goa', 'Gujarat', 'Haryana', 'Himachal Pradesh', 'Jharkhand', 'Karnataka', 'Kerala', 'Madhya Pradesh', 'Maharashtra', 'Manipur', 'Meghalaya', 'Mizoram', 'Nagaland', 'Odisha', 'Punjab', 'Rajasthan', 'Sikkim', 'Tamil Nadu', 'Telangana', 'Tripura', 'Uttar Pradesh', 'Uttarakhand', 'West Bengal'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Checkout - Go Appalam</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/auth.css">
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark mb-4">
        <div class="container">
            <a class="navbar-brand" href="#">Go Appalam</a>
            <div class="navbar-nav ms-auto">
                <span class="nav-item nav-link text-white">Welcome, <?php echo htmlspecialchars($user['username']); ?></span>
                <a class="nav-item nav-link" href="logout.php">Logout</a>
            </div>
        </div>
    </nav>

    <div class="container pb-5">
        <div class="row justify-content-center">
            <div class="col-lg-8">
                <div class="auth-card mt-0">
                    <h2 class="checkout-section-title">Shipping & Payment</h2>

                    <?php if ($success): ?>
                        <div class="alert alert-success text-center py-5">
                            <h4>Order Placed Successfully!</h4>
                            <p>Thank you for your purchase. Your order is being processed.</p>
                            <a href="index.php" class="btn btn-primary px-5">Continue Shopping</a>
                        </div>
                    <?php else: ?>
                        <?php if (isset($errors['general'])): ?>
                            <div class="alert alert-danger"><?php echo $errors['general']; ?></div>
                        <?php endif; ?>

                        <form method="POST" action="">
                            <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                            
                            <div class="row g-3">
                                <!-- 1. Full Name -->
                                <div class="col-md-6">
                                    <label class="form-label">Full Name <span class="text-danger">*</span></label>
                                    <input type="text" name="full_name" class="form-control" required value="<?php echo htmlspecialchars($form_data['full_name'] ?? $user['username']); ?>">
                                    <?php if (isset($errors['full_name'])): ?>
                                        <small class="text-danger"><?php echo $errors['full_name']; ?></small>
                                    <?php endif; ?>
                                </div>

                                <!-- 2. Phone Number -->
                                <div class="col-md-6">
                                    <label class="form-label">Phone Number (+91) <span class="text-danger">*</span></label>
                                    <input type="text" name="phone" class="form-control" placeholder="+919876543210" required value="<?php echo htmlspecialchars($form_data['phone'] ?? $user['phone'] ?? ''); ?>">
                                    <?php if (isset($errors['phone'])): ?>
                                        <small class="text-danger"><?php echo $errors['phone']; ?></small>
                                    <?php endif; ?>
                                </div>

                                <!-- 3. Address Line 1 -->
                                <div class="col-12">
                                    <label class="form-label">Address Line 1 <span class="text-danger">*</span></label>
                                    <input type="text" name="address1" class="form-control" required value="<?php echo htmlspecialchars($form_data['address1'] ?? ''); ?>">
                                    <?php if (isset($errors['address1'])): ?>
                                        <small class="text-danger"><?php echo $errors['address1']; ?></small>
                                    <?php endif; ?>
                                </div>

                                <!-- 4. Address Line 2 -->
                                <div class="col-12">
                                    <label class="form-label">Address Line 2 (Optional)</label>
                                    <input type="text" name="address2" class="form-control" value="<?php echo htmlspecialchars($form_data['address2'] ?? ''); ?>">
                                </div>

                                <!-- 5. Landmark -->
                                <div class="col-md-6">
                                    <label class="form-label">Landmark <span class="text-danger">*</span></label>
                                    <input type="text" name="landmark" class="form-control" required value="<?php echo htmlspecialchars($form_data['landmark'] ?? ''); ?>">
                                    <?php if (isset($errors['landmark'])): ?>
                                        <small class="text-danger"><?php echo $errors['landmark']; ?></small>
                                    <?php endif; ?>
                                </div>

                                <!-- 6. City -->
                                <div class="col-md-6">
                                    <label class="form-label">City <span class="text-danger">*</span></label>
                                    <input type="text" name="city" class="form-control" required value="<?php echo htmlspecialchars($form_data['city'] ?? ''); ?>">
                                    <?php if (isset($errors['city'])): ?>
                                        <small class="text-danger"><?php echo $errors['city']; ?></small>
                                    <?php endif; ?>
                                </div>

                                <!-- 7. State -->
                                <div class="col-md-6">
                                    <label class="form-label">State <span class="text-danger">*</span></label>
                                    <select name="state" class="form-select" required>
                                        <option value="">Select State</option>
                                        <?php foreach ($states as $s): ?>
                                            <option value="<?php echo $s; ?>" <?php echo (isset($form_data['state']) && $form_data['state'] == $s) ? 'selected' : ''; ?>><?php echo $s; ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                    <?php if (isset($errors['state'])): ?>
                                        <small class="text-danger"><?php echo $errors['state']; ?></small>
                                    <?php endif; ?>
                                </div>

                                <!-- 8. PIN Code -->
                                <div class="col-md-6">
                                    <label class="form-label">PIN Code (6 digits) <span class="text-danger">*</span></label>
                                    <input type="text" name="pincode" class="form-control" maxlength="6" required value="<?php echo htmlspecialchars($form_data['pincode'] ?? ''); ?>">
                                    <?php if (isset($errors['pincode'])): ?>
                                        <small class="text-danger"><?php echo $errors['pincode']; ?></small>
                                    <?php endif; ?>
                                </div>

                                <div class="col-12">
                                    <label class="form-label">Order Notes (Optional)</label>
                                    <textarea name="order_notes" class="form-control" rows="2"><?php echo htmlspecialchars($form_data['order_notes'] ?? ''); ?></textarea>
                                </div>

                                <!-- 9. Payment Method -->
                                <div class="col-12 mt-4">
                                    <h5 class="mb-3">Payment Method <span class="text-danger">*</span></h5>
                                    <div class="d-flex gap-4">
                                        <div class="form-check">
                                            <input class="form-check-input" type="radio" name="payment_method" id="pay_razorpay" value="razorpay" <?php echo (!isset($form_data['payment_method']) || $form_data['payment_method'] == 'razorpay') ? 'checked' : ''; ?>>
                                            <label class="form-check-label" for="pay_razorpay">Razorpay / Cards</label>
                                        </div>
                                        <div class="form-check">
                                            <input class="form-check-input" type="radio" name="payment_method" id="pay_cod" value="cod" <?php echo (isset($form_data['payment_method']) && $form_data['payment_method'] == 'cod') ? 'checked' : ''; ?>>
                                            <label class="form-check-label" for="pay_cod">Cash on Delivery (COD)</label>
                                        </div>
                                    </div>
                                    <?php if (isset($errors['payment_method'])): ?>
                                        <small class="text-danger"><?php echo $errors['payment_method']; ?></small>
                                    <?php endif; ?>
                                </div>

                                <div class="col-12 mt-5">
                                    <button type="submit" class="btn btn-primary w-100 py-3 text-uppercase">Place Order Now</button>
                                </div>
                            </div>
                        </form>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
