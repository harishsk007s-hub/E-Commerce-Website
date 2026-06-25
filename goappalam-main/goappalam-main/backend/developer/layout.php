<?php
function developer_layout($title, $content_html) {
    $current_page = basename($_SERVER['PHP_SELF']);
    $username = $_SESSION['username'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $title; ?> - Developer Hub</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body { background-color: #f1f3f5; }
        .sidebar { min-width: 260px; max-width: 260px; min-height: 100vh; background-color: #212529; color: #ced4da; }
        .sidebar .nav-link { color: #adb5bd; padding: 12px 20px; border-left: 3px solid transparent; }
        .sidebar .nav-link:hover { color: #fff; background-color: #2b3035; border-left-color: #0d6efd; }
        .sidebar .nav-link.active { color: #fff; background-color: #2b3035; border-left-color: #0d6efd; }
        .content { flex-grow: 1; padding: 30px; }
        .navbar-dev { background-color: #fff; border-bottom: 1px solid #dee2e6; padding: 15px 30px; }
        .card-dev { border: none; border-radius: 10px; box-shadow: 0 4px 12px rgba(0,0,0,0.05); }
    </style>
</head>
<body>
    <div class="d-flex">
        <!-- Sidebar -->
        <nav class="sidebar">
            <div class="p-4 fs-5 fw-bold text-white border-bottom border-dark text-center">
                <i class="fas fa-terminal me-2 text-primary"></i> Developer Hub
            </div>
            <ul class="nav flex-column mt-3">
                <li class="nav-item">
                    <a href="clients.php" class="nav-link <?php echo $current_page == 'clients.php' ? 'active' : ''; ?>">
                        <i class="fas fa-layer-group me-2"></i> API Clients
                    </a>
                </li>
                <li class="nav-item">
                    <a href="api-keys.php" class="nav-link <?php echo $current_page == 'api-keys.php' ? 'active' : ''; ?>">
                        <i class="fas fa-key me-2"></i> API Keys
                    </a>
                </li>
                <li class="nav-item">
                    <a href="api-logs.php" class="nav-link <?php echo $current_page == 'api-logs.php' ? 'active' : ''; ?>">
                        <i class="fas fa-history me-2"></i> API Logs
                    </a>
                </li>
                <li class="nav-item">
                    <a href="api-guide.php" class="nav-link <?php echo $current_page == 'api-guide.php' ? 'active' : ''; ?>">
                        <i class="fas fa-book me-2"></i> API Guide
                    </a>
                </li>
                <li class="nav-item mt-5 pt-5">
                    <a href="logout.php" class="nav-link text-danger">
                        <i class="fas fa-sign-out-alt me-2"></i> Logout
                    </a>
                </li>
            </ul>
        </nav>

        <!-- Main Content -->
        <div class="content">
            <header class="navbar-dev d-flex justify-content-between align-items-center rounded mb-4 shadow-sm">
                <h4 class="mb-0 fw-bold"><?php echo $title; ?></h4>
                <div class="text-muted small">
                    Logged in as <span class="fw-bold text-dark"><?php echo htmlspecialchars($username); ?></span>
                </div>
            </header>
            
            <?php echo $content_html; ?>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
<?php
}
?>
