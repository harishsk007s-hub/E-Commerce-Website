<?php
require_once __DIR__ . '/../includes/user-auth.php';
require_once __DIR__ . '/../includes/email-functions.php';

$message = "";
$status = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email']);
    $csrf_token = $_POST['csrf_token'];

    if (!check_csrf($csrf_token)) {
        die("CSRF verification failed.");
    }

    $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ? AND role = 'customer'");
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if ($user) {
        $reset_token = bin2hex(random_bytes(16));
        $stmt = $pdo->prepare("UPDATE users SET reset_token = ? WHERE id = ?");
        $stmt->execute([$reset_token, $user['id']]);
        
        send_password_reset_email($email, $reset_token);
        
        $message = "If an account exists for this email, we have sent a password reset link.";
        $status = "success";
    } else {
        // We still show success for security reasons
        $message = "If an account exists for this email, we have sent a password reset link.";
        $status = "success";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forgot Password - Go Appalam</title>
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
                        <h3>Reset Your Password</h3>
                        <p class="text-muted">Enter your email and we'll send you a link to reset your password.</p>
                    </div>

                    <?php if ($message): ?>
                        <div class="alert alert-<?php echo ($status === 'success') ? 'success' : 'danger'; ?> text-center">
                            <?php echo $message; ?>
                        </div>
                    <?php endif; ?>

                    <form method="POST" action="">
                        <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                        
                        <div class="mb-4">
                            <label for="email" class="form-label">Email Address</label>
                            <input type="email" name="email" class="form-control" required placeholder="Enter your registered email">
                        </div>

                        <button type="submit" class="btn btn-primary w-100 py-3 text-uppercase">Send Reset Link</button>
                        
                        <div class="text-center mt-4">
                            <a href="login.php" class="text-primary fw-bold text-decoration-none">&larr; Back to Login</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
