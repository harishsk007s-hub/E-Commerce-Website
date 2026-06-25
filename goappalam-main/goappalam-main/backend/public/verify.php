<?php
require_once '../includes/db_connection.php';
require_once '../includes/functions.php';

$message = '';
$status = 'info';

if (isset($_GET['token'])) {
    $token = sanitize($_GET['token']);
    
    $stmt = $pdo->prepare("SELECT id FROM users WHERE verification_token = ? AND is_verified = 0");
    $stmt->execute([$token]);
    $user = $stmt->fetch();
    
    if ($user) {
        $stmt = $pdo->prepare("UPDATE users SET is_verified = 1, verification_token = NULL WHERE id = ?");
        if ($stmt->execute([$user['id']])) {
            $message = "Your email has been verified successfully! You can now login.";
            $status = 'success';
        } else {
            $message = "Something went wrong. Please try again later.";
            $status = 'danger';
        }
    } else {
        $message = "Invalid or expired verification token.";
        $status = 'danger';
    }
} else {
    header('Location: login.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Email Verification - eCommerce Appalam</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light d-flex align-items-center justify-content-center min-vh-100">
    <div class="card shadow-sm border-0 p-4" style="max-width: 400px; border-radius: 15px;">
        <div class="text-center">
            <h3 class="fw-bold mb-3">Email Verification</h3>
            <div class="alert alert-<?php echo $status; ?> mb-4">
                <?php echo $message; ?>
            </div>
            <a href="login.php" class="btn btn-primary px-4 py-2" style="background-color: #ff4757; border: none; border-radius: 10px;">Go to Login</a>
        </div>
    </div>
</body>
</html>
