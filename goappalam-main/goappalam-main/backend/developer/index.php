<?php
require_once '../includes/db_connection.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';

if (isset($_SESSION['user_id']) && $_SESSION['role'] === 'developer-admin') {
    header('Location: clients.php');
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = sanitize($_POST['username']);
    $password = $_POST['password'];

    $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ? AND role = 'developer-admin' AND status = 1");
    $stmt->execute([$username]);
    $user = $stmt->fetch();

    if ($user && password_verify($password, $user['password'])) {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['role'] = $user['role'];
        
        log_activity($pdo, $user['id'], "Developer logged in");
        
        header('Location: clients.php');
        exit;
    } else {
        $error = "Invalid developer credentials";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Developer Login - eCommerce Backend</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background-color: #1a1a1a; color: #f8f9fa; }
        .login-container { max-width: 400px; margin-top: 100px; }
        .card { background-color: #2b2b2b; border: 1px solid #444; color: white; }
    </style>
</head>
<body>
    <div class="container login-container">
        <div class="card shadow-lg">
            <div class="card-body p-4 text-center">
                <i class="fas fa-code fa-3x mb-3 text-primary"></i>
                <h3 class="mb-4">Developer Hub</h3>
                <?php if ($error): ?>
                    <div class="alert alert-danger py-2"><?php echo $error; ?></div>
                <?php endif; ?>
                <form method="POST">
                    <div class="mb-3 text-start">
                        <label class="form-label small opacity-75">Username</label>
                        <input type="text" name="username" class="form-control form-control-dark bg-dark text-white border-secondary" required autofocus>
                    </div>
                    <div class="mb-3 text-start">
                        <label class="form-label small opacity-75">Password</label>
                        <input type="password" name="password" class="form-control form-control-dark bg-dark text-white border-secondary" required>
                    </div>
                    <button type="submit" class="btn btn-primary w-100 mt-2">Access Developer Panel</button>
                </form>
            </div>
        </div>
        <p class="text-center mt-4 small text-muted">Protected API Management Area</p>
    </div>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</body>
</html>
