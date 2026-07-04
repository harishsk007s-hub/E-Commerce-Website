<?php
function admin_layout($title, $content_html, $scripts = '') {
    global $pdo;
    $current_page = basename($_SERVER['PHP_SELF']);
    $role = $_SESSION['role'] ?? 'guest';
    $username = $_SESSION['username'] ?? 'User';
    
    // Breadcrumb logic
    $breadcrumbs = [
        'dashboard.php' => 'Dashboard',
        'products.php' => 'Products',
        'categories.php' => 'Categories',
        'orders.php' => 'Orders',
        'payments.php' => 'Payments',
        'customers.php' => 'Customers',
        'inventory.php' => 'Inventory',
        'shipping.php' => 'Shipping',
        'coupons.php' => 'Coupons',
        'settings.php' => 'Settings',
        'reports.php' => 'Reports'
    ];
    $current_crumb = $breadcrumbs[$current_page] ?? 'Page';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $title; ?> - Admin Panel</title>
    <!-- Favicon -->
    <link rel="icon" type="image/png" href="/assests/uploads/2021/07/ICON-01-1-500x560.png" />
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- DataTables CSS -->
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.4/css/dataTables.bootstrap5.min.css">
    <!-- Custom Admin Style -->
    <style>
        :root { --sidebar-bg: #1e1e2d; --sidebar-active: #2c2c3d; --accent-color: #007bff; }
        body { background-color: #f4f6f9; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; }
        .wrapper { display: flex; width: 100%; align-items: stretch; }
        #sidebar { min-width: 250px; max-width: 250px; background: var(--sidebar-bg); color: #fff; transition: all 0.3s; min-height: 100vh; position: sticky; top: 0; }
        #sidebar .sidebar-header { padding: 20px; background: rgba(0,0,0,0.1); border-bottom: 1px solid rgba(255,255,255,0.05); }
        #sidebar ul.components { padding: 10px 0; }
        #sidebar ul li a { padding: 12px 20px; font-size: 0.9rem; display: block; color: rgba(255,255,255,0.7); text-decoration: none; transition: 0.3s; border-left: 3px solid transparent; }
        #sidebar ul li a:hover { color: #fff; background: var(--sidebar-active); border-left-color: var(--accent-color); }
        #sidebar ul li a.active { color: #fff; background: var(--sidebar-active); border-left-color: var(--accent-color); font-weight: 600; }
        #sidebar ul li i { width: 30px; }
        #content { width: 100%; padding: 0; min-height: 100vh; display: flex; flex-direction: column; position: relative; }
        .main-header { background: #fff; padding: 15px 30px; box-shadow: 0 2px 4px rgba(0,0,0,0.04); display: flex; justify-content: space-between; align-items: center; }
        .main-header h5 { white-space: nowrap; overflow: hidden; text-overflow: ellipsis; max-width: 180px; }
        .main-body { padding: 30px; flex: 1; }
        .breadcrumb-item + .breadcrumb-item::before { content: "›"; color: #adb5bd; }
        .card { border: none; border-radius: 10px; box-shadow: 0 4px 6px rgba(0,0,0,0.02); margin-bottom: 20px; }
        .card-header { background: transparent; border-bottom: 1px solid #f1f1f1; padding: 15px 20px; font-weight: 700; color: #333; }
        .status-badge { font-size: 0.75rem; font-weight: 700; text-transform: uppercase; padding: 4px 10px; border-radius: 5px; }
        .btn-action { padding: 4px 8px; font-size: 0.8rem; border-radius: 5px; }
        
        /* Responsive adjustments */
        @media (max-width: 768px) {
            #sidebar { margin-left: -250px; position: fixed; z-index: 1050; height: 100vh; }
            #sidebar.active { margin-left: 0; }
            #content { width: 100%; }
            .main-header { padding: 10px 15px; }
            .main-body { padding: 15px; }
            .overlay { display: none; position: fixed; width: 100vw; height: 100vh; background: rgba(0, 0, 0, 0.5); z-index: 1049; }
            .overlay.active { display: block; }
            .dataTables_wrapper { overflow-x: auto; }
            .main-header h5 { max-width: 180px; }
        }
        @media (min-width: 769px) {
            .main-header h5 { max-width: 100%; }
        }
    </style>
</head>
<body>
    <div class="wrapper">
        <!-- Sidebar -->
        <nav id="sidebar">
            <div class="sidebar-header">
                <h5 class="mb-0 text-white font-black" style="font-weight: 900;"><i class="fas fa-shield-alt me-2 text-[#FFC222]"></i> Goappalam Admin</h5>
            </div>
            <ul class="list-unstyled components">
                <li>
                    <a href="dashboard.php" class="<?php echo $current_page == 'dashboard.php' ? 'active' : ''; ?>">
                        <i class="fas fa-home"></i> Dashboard
                    </a>
                </li>
                
                <?php if (in_array($role, ['super-admin', 'manager', 'inventory'])): ?>
                <li>
                    <a href="products.php" class="<?php echo $current_page == 'products.php' ? 'active' : ''; ?>">
                        <i class="fas fa-box"></i> Products
                    </a>
                </li>
                <li>
                    <a href="categories.php" class="<?php echo $current_page == 'categories.php' ? 'active' : ''; ?>">
                        <i class="fas fa-th-large"></i> Categories
                    </a>
                </li>
                <li>
                    <a href="inventory.php" class="<?php echo $current_page == 'inventory.php' ? 'active' : ''; ?>">
                        <i class="fas fa-warehouse"></i> Inventory
                    </a>
                </li>
                <?php endif; ?>

                <?php if (in_array($role, ['super-admin', 'manager', 'orders'])): ?>
                <li>
                    <a href="orders.php" class="<?php echo $current_page == 'orders.php' ? 'active' : ''; ?>">
                        <i class="fas fa-shopping-cart"></i> Orders
                    </a>
                </li>
                <li>
                    <a href="payments.php" class="<?php echo $current_page == 'payments.php' ? 'active' : ''; ?>">
                        <i class="fas fa-wallet"></i> Payments
                    </a>
                </li>
                <?php endif; ?>

                <?php if (in_array($role, ['super-admin', 'manager'])): ?>
                <li>
                    <a href="customers.php" class="<?php echo $current_page == 'customers.php' ? 'active' : ''; ?>">
                        <i class="fas fa-users"></i> Customers
                    </a>
                </li>
                <li>
                    <a href="shipping.php" class="<?php echo $current_page == 'shipping.php' ? 'active' : ''; ?>">
                        <i class="fas fa-truck"></i> Shipping
                    </a>
                </li>
                <li>
                    <a href="coupons.php" class="<?php echo $current_page == 'coupons.php' ? 'active' : ''; ?>">
                        <i class="fas fa-percent"></i> Coupons
                    </a>
                </li>
                <?php endif; ?>

                <?php if ($role == 'super-admin'): ?>
                <li>
                    <a href="reports.php" class="<?php echo $current_page == 'reports.php' ? 'active' : ''; ?>">
                        <i class="fas fa-chart-line"></i> Reports
                    </a>
                </li>
                <li>
                    <a href="settings.php" class="<?php echo $current_page == 'settings.php' ? 'active' : ''; ?>">
                        <i class="fas fa-sliders-h"></i> Settings
                    </a>
                </li>
                <?php endif; ?>

                <li class="mt-4">
                    <a href="logout.php" class="text-danger">
                        <i class="fas fa-sign-out-alt"></i> Logout
                    </a>
                </li>
            </ul>
        </nav>

        <!-- Page Content -->
        <div id="content">
            <div class="overlay"></div>
            <nav class="main-header">
                <div class="d-flex align-items-center">
                    <button type="button" id="sidebarCollapse" class="btn btn-link text-dark d-md-none me-2 p-0">
                        <i class="fas fa-bars fa-lg"></i>
                    </button>
                    <h5 class="mb-0 fw-bold"><?php echo $title; ?></h5>
                </div>
                <div class="d-flex align-items-center">
                    <div class="dropdown">
                        <a href="#" class="text-dark text-decoration-none dropdown-toggle fw-semibold" data-bs-toggle="dropdown">
                            <i class="fas fa-user-circle me-2"></i> <?php echo htmlspecialchars($username); ?>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end shadow border-0">
                            <li><a class="dropdown-item py-2" href="settings.php"><i class="fas fa-cog me-2"></i> Profile</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item py-2 text-danger" href="logout.php"><i class="fas fa-sign-out-alt me-2"></i> Logout</a></li>
                        </ul>
                    </div>
                </div>
            </nav>

            <div class="main-body">
                <nav aria-label="breadcrumb" class="mb-4">
                    <ol class="breadcrumb mb-0">
                        <li class="breadcrumb-item"><a href="dashboard.php" class="text-decoration-none text-muted">Home</a></li>
                        <?php if ($current_page != 'dashboard.php'): ?>
                            <li class="breadcrumb-item active fw-bold" aria-current="page"><?php echo $current_crumb; ?></li>
                        <?php endif; ?>
                    </ol>
                </nav>

                <?php echo $content_html; ?>
            </div>

            <footer class="text-center py-4 bg-white mt-auto border-top text-muted small">
                &copy; <?php echo date('Y'); ?> Goappalam Admin Panel. All rights reserved.
            </footer>
        </div>
    </div>

    <!-- Scripts -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.4/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.4/js/dataTables.bootstrap5.min.js"></script>
    
    <script>
        $(document).ready(function() {
            // Default DataTable initialization for any table with class .datatable
            $('.datatable').each(function() {
                var defaultOrder = [[0, "desc"]];
                var tableOrder = $(this).data('order');
                
                $(this).DataTable({
                    "pageLength": 10,
                    "ordering": false,
                    "order": [],
                    "info": true,
                    "responsive": true,
                    "language": {
                        "search": "",
                        "searchPlaceholder": "Search records..."
                    }
                });
            });
            // Style search input
            $('.dataTables_filter input').addClass('form-control form-control-sm');

            // Sidebar toggle
            $('#sidebarCollapse, .overlay').on('click', function() {
                $('#sidebar, .overlay').toggleClass('active');
                if ($(window).width() <= 768) {
                    if ($('#sidebar').hasClass('active')) {
                        $('body').css('overflow', 'hidden');
                    } else {
                        $('body').css('overflow', 'auto');
                    }
                }
            });
        });
    </script>
    <?php echo $scripts; ?>
</body>
</html>
<?php
}
?>
