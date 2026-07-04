<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once '../includes/db_connection.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';

if (isset($_SESSION['user_id']) && in_array($_SESSION['role'], ['super-admin', 'manager', 'inventory', 'orders', 'support'])) {
    header('Location: /admin/dashboard.php');
    exit;
}

// CSRF check is now handled in backend/includes/auth.php
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = sanitize($_POST['username']);
    $password = $_POST['password'];

    $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ? AND role != 'customer' AND status = 1");
    $stmt->execute([$username]);
    $user = $stmt->fetch();

    if ($user && password_verify($password, $user['password'])) {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['role'] = $user['role'];
        
        log_activity($pdo, $user['id'], "Admin logged in");
        
        header('Location: /admin/dashboard.php');
        exit;
    } else {
        $error = "Invalid username or password";
    }
}

// CSRF token is already set by auth.php if missing
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login - Goappalam Admin Panel</title>
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        :root { --admin-primary: #FFC222; --admin-dark: #1E1D23; }
        body { 
            background: #f8f9fc;
            height: 100vh;
            width: 100vw;
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;
            margin: 0;
            overflow: hidden;
        }
        .login-card {
            width: 100%;
            max-width: 400px;
            border: none;
            border-radius: 2rem;
            box-shadow: 0 1rem 3rem rgba(0,0,0,0.1);
            overflow: hidden;
            background: #fff;
        }
        .login-header {
            background: var(--admin-primary);
            color: var(--admin-dark);
            padding: 3rem 1.5rem;
            text-align: center;
        }
        .login-body { padding: 2.5rem 2.5rem; }
        .form-label { font-weight: 800; text-transform: uppercase; letter-spacing: 0.5px; font-size: 0.75rem; color: var(--admin-dark); margin-bottom: 0.5rem; }
        .form-control { 
            border-radius: 1rem; 
            padding: 0.85rem 1.25rem; 
            border: 2px solid #f1f1f1;
            font-weight: 600;
        }
        .form-control:focus {
            border-color: var(--admin-primary);
            box-shadow: none;
            background-color: #fff;
        }
        .btn-login { 
            border-radius: 1rem; 
            padding: 0.85rem; 
            font-weight: 900; 
            background: var(--admin-primary); 
            border: none; 
            color: var(--admin-dark);
            text-transform: uppercase;
            letter-spacing: 1px;
            box-shadow: 0 0.5rem 1.5rem rgba(255, 194, 34, 0.3);
            transition: all 0.3s;
        }
        .btn-login:hover { 
            background: var(--admin-dark); 
            color: #fff;
            transform: translateY(-2px);
            box-shadow: 0 0.5rem 1.5rem rgba(0, 0, 0, 0.2);
        }
        .admin-logo { margin-bottom: 1rem; }
        .admin-logo img { height: 50px; filter: brightness(0); }
    </style>
</head>
<body>
    <div class="login-card">
        <div class="login-header">
            <h4 class="mb-1 fw-black" style="font-weight: 900;">Goappalam Admin Panel</h4>
            <p class="mb-0 small opacity-75 fw-bold">Sign in to manage your store</p>
        </div>
        <div class="login-body">
            <?php if ($error): ?>
                <div class="alert alert-danger border-0 rounded-4 small fw-bold py-2 mb-4 text-center"><?php echo $error; ?></div>
            <?php endif; ?>
            <form method="POST">
                <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                <div class="mb-3">
                    <label class="form-label">Username</label>
                    <input type="text" name="username" class="form-control" required placeholder="Enter username">
                </div>
                <div class="mb-3">
                    <label class="form-label">Password</label>
                    <input type="password" name="password" class="form-control" required placeholder="••••••••">
                </div>
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <div class="form-check">
                        <input type="checkbox" class="form-check-input" id="rememberMe">
                        <label class="form-check-label small fw-bold text-muted" for="rememberMe">Remember me</label>
                    </div>
                    <a href="#" class="text-decoration-none small fw-bold text-primary">Forgot Password?</a>
                </div>
                <button type="submit" class="btn btn-primary btn-login w-100">Login Now</button>
            </form>
        </div>
    </div>
</body>
</html>
