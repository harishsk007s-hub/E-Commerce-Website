<?php
require_once __DIR__ . '/../includes/user-auth.php';

$message = "";
$status = "error";

if (isset($_GET['token'])) {
    $token = $_GET['token'];
    
    $stmt = $pdo->prepare("SELECT id FROM users WHERE verification_token = ? AND is_verified = 0");
    $stmt->execute([$token]);
    $user = $stmt->fetch();
    
    if ($user) {
        $stmt = $pdo->prepare("UPDATE users SET is_verified = 1, verification_token = NULL WHERE id = ?");
        if ($stmt->execute([$user['id']])) {
            $message = "Email verified successfully! You can now log in.";
            $status = "success";
        } else {
            $message = "Verification failed. Please try again.";
        }
    } else {
        $message = "Invalid or expired verification token.";
    }
} else {
    $message = "No token provided.";
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Email Verification - Go Appalam</title>
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
                        <h3>Email Verification</h3>
                    </div>

                    <div class="alert alert-<?php echo ($status === 'success') ? 'success' : 'danger'; ?> text-center">
                        <?php echo $message; ?>
                    </div>

                    <div class="text-center mt-4">
                        <a href="login.php" class="btn btn-primary px-5">Go to Login</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
