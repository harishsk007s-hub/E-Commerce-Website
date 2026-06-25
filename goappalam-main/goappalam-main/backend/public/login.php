<?php
/**
 * Customer Login - Step 1: Enter Email (OTP based)
 */

session_start();
require_once __DIR__ . '/../includes/otp-system.php';
require_once __DIR__ . '/../includes/email-smtp.php';

if (isset($_SESSION['user_id'])) {
    header('Location: checkout.php');
    exit();
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = filter_var($_POST['email'], FILTER_SANITIZE_EMAIL);
    
    if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
        // Check if user or customer exists or create new customer
        $userId = getOrCreateCustomer($email);
        
        // Generate and Save OTP
        $otp = generateOTP();
        if (saveOTP($email, $otp, 'customer')) {
            // Send OTP via Email
            $subject = "Your Login OTP - Goappalam";
            $body = "<h2>Hello,</h2><p>Your 6-digit OTP for login is: <b>$otp</b></p><p>This code expires in 10 minutes.</p>";
            
            if (sendEmail($email, $subject, $body)) {
                $_SESSION['login_email'] = $email;
                header('Location: otp-verify.php');
                exit;
            } else {
                $error = "Failed to send OTP. Please try again.";
            }
        } else {
            $error = "Failed to generate OTP.";
        }
    } else {
        $error = "Please enter a valid email address.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Customer Login (OTP) - Go Appalam</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/auth.css">
    <style>
        .auth-card { max-width: 450px; margin: 80px auto; padding: 40px; border-radius: 20px; box-shadow: 0 10px 30px rgba(0,0,0,0.1); }
        .btn-primary { background-color: #FF5722; border: none; padding: 12px; }
        .btn-primary:hover { background-color: #E64A19; }
        .auth-header img { width: 150px; margin-bottom: 20px; }
    </style>
</head>
<body>
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-8 col-lg-5">
                <div class="auth-card bg-white">
                    <div class="auth-header text-center">
                        <img src="../assests/uploads/2021/Contact/Logo.png" alt="Go Appalam Logo">
                        <h3 class="fw-bold">Welcome Back</h3>
                        <p class="text-muted">Enter your email to receive a login OTP</p>
                    </div>

                    <?php if ($error): ?>
                        <div class="alert alert-danger border-0 small"><?php echo $error; ?></div>
                    <?php endif; ?>

                    <form method="POST" action="">
                        <div class="mb-4">
                            <label for="email" class="form-label small fw-bold text-uppercase">Email address</label>
                            <input type="email" name="email" class="form-control form-control-lg border-2" required placeholder="john@example.com" value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>">
                        </div>

                        <button type="submit" class="btn btn-primary w-100 fw-bold rounded-3 py-3 mb-3 shadow-sm">Send OTP</button>
                        
                        <div class="text-center mt-3">
                            <span class="text-muted small">New customer? <a href="register.php" class="text-primary fw-bold text-decoration-none">Create Account</a></span>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
