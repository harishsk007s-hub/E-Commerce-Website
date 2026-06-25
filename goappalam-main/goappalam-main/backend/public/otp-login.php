<?php
/**
 * OTP Login - Step 1: Enter Email
 */

session_start();
require_once __DIR__ . '/../includes/otp-system.php';
require_once __DIR__ . '/../includes/email-smtp.php';

$error = '';
$success = '';

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
    <title>OTP Login - Goappalam</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="../assets/style.css">
    <style>
        .login-container { max-width: 400px; margin: 100px auto; padding: 20px; border: 1px solid #ddd; border-radius: 8px; }
        .form-group { margin-bottom: 15px; }
        .form-group label { display: block; margin-bottom: 5px; }
        .form-group input { width: 100%; padding: 10px; border: 1px solid #ccc; border-radius: 4px; box-sizing: border-box; }
        .btn { display: block; width: 100%; padding: 10px; background: #FF5722; color: white; border: none; border-radius: 4px; cursor: pointer; }
        .error { color: red; margin-bottom: 15px; }
    </style>
</head>
<body>
    <div class="login-container">
        <h2>Login to Goappalam</h2>
        <p>Enter your email to receive a 6-digit OTP.</p>
        
        <?php if ($error): ?>
            <div class="error"><?php echo $error; ?></div>
        <?php endif; ?>
        
        <form method="POST">
            <div class="form-group">
                <label>Email Address</label>
                <input type="email" name="email" required placeholder="Enter your email">
            </div>
            <button type="submit" class="btn">Send OTP</button>
        </form>
    </div>
</body>
</html>
