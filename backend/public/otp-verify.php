<?php
/**
 * OTP Verification - Step 2: Enter 6-digit OTP
 */

session_start();
require_once __DIR__ . '/../includes/otp-system.php';

$error = '';
$success = '';

if (!isset($_SESSION['login_email'])) {
    header('Location: otp-login.php');
    exit;
}

$email = $_SESSION['login_email'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $otp = filter_var($_POST['otp'], FILTER_SANITIZE_STRING);
    
    $result = verifyOTP($email, $otp, 'customer');
    
    if ($result['success']) {
        $_SESSION['user_id'] = $result['user_id'];
        $_SESSION['user_role'] = 'customer';
        unset($_SESSION['login_email']);
        
        // Redirect to checkout or profile
        header('Location: checkout.php');
        exit;
    } else {
        $error = $result['message'];
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Verify OTP - Goappalam</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="../assets/style.css">
    <style>
        .login-container { max-width: 400px; margin: 100px auto; padding: 20px; border: 1px solid #ddd; border-radius: 8px; }
        .form-group { margin-bottom: 15px; }
        .form-group label { display: block; margin-bottom: 5px; }
        .form-group input { width: 100%; padding: 10px; border: 1px solid #ccc; border-radius: 4px; box-sizing: border-box; text-align: center; letter-spacing: 5px; font-size: 24px; font-weight: bold; }
        .btn { display: block; width: 100%; padding: 10px; background: #FF5722; color: white; border: none; border-radius: 4px; cursor: pointer; }
        .error { color: red; margin-bottom: 15px; }
        .resend { text-align: center; margin-top: 15px; }
        .resend a { color: #FF5722; text-decoration: none; }
    </style>
</head>
<body>
    <div class="login-container">
        <h2>Verify OTP</h2>
        <p>Enter the 6-digit OTP sent to <b><?php echo htmlspecialchars($email); ?></b></p>
        
        <?php if ($error): ?>
            <div class="error"><?php echo $error; ?></div>
        <?php endif; ?>
        
        <form method="POST">
            <div class="form-group">
                <input type="text" name="otp" required maxlength="6" pattern="[0-9]{6}" placeholder="000000" autofocus>
            </div>
            <button type="submit" class="btn">Verify & Login</button>
        </form>
        
        <div class="resend">
            Didn't receive code? <a href="resend-otp.php">Resend OTP</a>
        </div>
    </div>
</body>
</html>
