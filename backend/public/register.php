<?php
require_once __DIR__ . '/../includes/user-auth.php';
require_once __DIR__ . '/../includes/email-functions.php';

$errors = [];
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    $csrf_token = $_POST['csrf_token'];

    if (!check_csrf($csrf_token)) {
        die("CSRF verification failed.");
    }

    if (strlen($password) < 6) {
        $errors['password'] = "Password must be at least 6 characters long.";
    }

    if ($password !== $confirm_password) {
        $errors['confirm_password'] = "Passwords do not match.";
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors['email'] = "Invalid email format.";
    }

    // Check duplicate email
    $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->execute([$email]);
    if ($stmt->fetch()) {
        $errors['email'] = "Email already registered.";
    }

    if (empty($errors)) {
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        $verification_token = bin2hex(random_bytes(16));
        
        $stmt = $pdo->prepare("INSERT INTO users (username, password, role, email, phone, status, is_verified, verification_token) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        if ($stmt->execute([$username, $hashed_password, 'customer', $email, $phone, 1, 0, $verification_token])) {
            send_verification_email($email, $verification_token, $username);
            $success = true;
        } else {
            $errors['general'] = "Registration failed. Please try again.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Customer Registration - Go Appalam</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/auth.css">
</head>
<body>
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-6 col-lg-5">
                <div class="auth-card">
                    <div class="auth-header">
                        <img src="../assests/uploads/2021/Contact/Logo.png" alt="Go Appalam Logo">
                        <h3>Create Your Account</h3>
                    </div>

                    <?php if ($success): ?>
                        <div class="alert alert-success">
                            Registration successful! Please check your email for a verification link.
                            <br><a href="login.php" class="alert-link">Click here to login</a>
                        </div>
                    <?php else: ?>
                        <form method="POST" action="">
                            <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                            
                            <div class="mb-3">
                                <label for="username" class="form-label">Username</label>
                                <input type="text" name="username" class="form-control" required value="<?php echo htmlspecialchars($username ?? ''); ?>">
                            </div>

                            <div class="mb-3">
                                <label for="email" class="form-label">Email address</label>
                                <input type="email" name="email" class="form-control" required value="<?php echo htmlspecialchars($email ?? ''); ?>">
                                <?php if (isset($errors['email'])): ?>
                                    <small class="text-danger"><?php echo $errors['email']; ?></small>
                                <?php endif; ?>
                            </div>

                            <div class="mb-3">
                                <label for="phone" class="form-label">Phone Number</label>
                                <input type="text" name="phone" class="form-control" required placeholder="e.g., 9876543210" value="<?php echo htmlspecialchars($phone ?? ''); ?>">
                            </div>

                            <div class="mb-3">
                                <label for="password" class="form-label">Password</label>
                                <input type="password" name="password" class="form-control" required>
                                <?php if (isset($errors['password'])): ?>
                                    <small class="text-danger"><?php echo $errors['password']; ?></small>
                                <?php endif; ?>
                            </div>

                            <div class="mb-3">
                                <label for="confirm_password" class="form-label">Confirm Password</label>
                                <input type="password" name="confirm_password" class="form-control" required>
                                <?php if (isset($errors['confirm_password'])): ?>
                                    <small class="text-danger"><?php echo $errors['confirm_password']; ?></small>
                                <?php endif; ?>
                            </div>

                            <button type="submit" class="btn btn-primary w-full py-3 mb-3">Register</button>
                            
                            <div class="text-center mt-3">
                                <span>Already have an account? <a href="login.php" class="text-primary fw-bold">Login here</a></span>
                            </div>
                        </form>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
