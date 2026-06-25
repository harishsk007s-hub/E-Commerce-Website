<?php
require_once '../includes/db_connection.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';
require_once 'layout.php';

check_admin_auth();
require_role(['super-admin']);

$message = '';
$tab = $_GET['tab'] ?? 'general';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($tab === 'general' || $tab === 'tax' || $tab === 'seo' || $tab === 'features') {
        foreach ($_POST['settings'] as $key => $value) {
            $type = $_POST['types'][$key] ?? 'string';
            set_setting($pdo, $key, $value, $tab, $type);
        }
        $message = "Settings updated successfully!";
    } elseif ($tab === 'payment') {
        $id = (int)$_POST['gateway_id'];
        $enabled = isset($_POST['enabled']) ? 1 : 0;
        $config = json_encode($_POST['config']);
        
        $stmt = $pdo->prepare("UPDATE payment_gateways SET enabled = ?, config = ? WHERE id = ?");
        $stmt->execute([$enabled, $config, $id]);
        $message = "Payment gateway updated!";
    } elseif ($tab === 'add_user') {
        $username = sanitize($_POST['username']);
        $email = sanitize($_POST['email']);
        $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
        $role = sanitize($_POST['role']);
        
        $stmt = $pdo->prepare("INSERT INTO users (username, email, password, role) VALUES (?, ?, ?, ?)");
        $stmt->execute([$username, $email, $password, $role]);
        $message = "Admin user added successfully!";
    }
}

// Fetch settings for the current tab
$stmt = $pdo->prepare("SELECT * FROM settings WHERE setting_group = ?");
$stmt->execute([$tab]);
$settings_rows = $stmt->fetchAll();
$settings = [];
foreach ($settings_rows as $row) {
    $settings[$row['setting_key']] = $row;
}

// Fetch gateways if on payment tab
$gateways = ($tab === 'payment') ? get_payment_gateways($pdo, false) : [];

// Fetch users for users tab
$users = ($tab === 'users') ? $pdo->query("SELECT * FROM users WHERE role != 'developer-admin' ORDER BY role ASC")->fetchAll() : [];

ob_start();
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h2>System Settings</h2>
</div>

<?php if ($message): ?>
    <div class="alert alert-success alert-dismissible fade show"><?php echo $message; ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
<?php endif; ?>

<div class="card shadow-sm border-0">
    <div class="card-header bg-white p-0 border-bottom">
        <ul class="nav nav-tabs card-header-tabs m-0 border-0">
            <li class="nav-item">
                <a class="nav-link <?php echo $tab == 'general' ? 'active fw-bold' : ''; ?>" href="?tab=general"><i class="fas fa-store me-1"></i> General</a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo $tab == 'payment' ? 'active fw-bold' : ''; ?>" href="?tab=payment"><i class="fas fa-credit-card me-1"></i> Payments</a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo $tab == 'tax' ? 'active fw-bold' : ''; ?>" href="?tab=tax"><i class="fas fa-percentage me-1"></i> Tax/GST</a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo $tab == 'features' ? 'active fw-bold' : ''; ?>" href="?tab=features"><i class="fas fa-toggle-on me-1"></i> Features</a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo $tab == 'seo' ? 'active fw-bold' : ''; ?>" href="?tab=seo"><i class="fas fa-search me-1"></i> SEO/Analytics</a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo $tab == 'users' ? 'active fw-bold' : ''; ?>" href="?tab=users"><i class="fas fa-users me-1"></i> Admin Users</a>
            </li>
        </ul>
    </div>
    <div class="card-body p-4">
        
        <?php if ($tab === 'general'): ?>
            <form method="POST">
                <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label">Store Name</label>
                        <input type="text" name="settings[store_name]" class="form-control" value="<?php echo htmlspecialchars($settings['store_name']['setting_value'] ?? ''); ?>">
                        <input type="hidden" name="types[store_name]" value="string">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Contact Email</label>
                        <input type="email" name="settings[store_email]" class="form-control" value="<?php echo htmlspecialchars($settings['store_email']['setting_value'] ?? ''); ?>">
                        <input type="hidden" name="types[store_email]" value="string">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Currency Code (e.g. INR)</label>
                        <input type="text" name="settings[currency_code]" class="form-control" value="<?php echo htmlspecialchars($settings['currency_code']['setting_value'] ?? 'INR'); ?>">
                        <input type="hidden" name="types[currency_code]" value="string">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Currency Symbol (e.g. ₹)</label>
                        <input type="text" name="settings[currency_symbol]" class="form-control" value="<?php echo htmlspecialchars($settings['currency_symbol']['setting_value'] ?? '₹'); ?>">
                        <input type="hidden" name="types[currency_symbol]" value="string">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Timezone</label>
                        <input type="text" name="settings[timezone]" class="form-control" value="<?php echo htmlspecialchars($settings['timezone']['setting_value'] ?? 'UTC'); ?>">
                        <input type="hidden" name="types[timezone]" value="string">
                    </div>
                </div>
                <button type="submit" class="btn btn-primary mt-4">Save General Settings</button>
            </form>

        <?php elseif ($tab === 'payment'): ?>
            <div class="row">
                <?php foreach ($gateways as $gw): ?>
                <div class="col-md-6 mb-4">
                    <div class="card border h-100">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <h6 class="mb-0 fw-bold"><?php echo htmlspecialchars($gw['name']); ?></h6>
                            <span class="badge bg-<?php echo $gw['enabled'] ? 'success' : 'secondary'; ?>"><?php echo $gw['enabled'] ? 'Enabled' : 'Disabled'; ?></span>
                        </div>
                        <div class="card-body">
                            <form method="POST">
                                <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                                <input type="hidden" name="gateway_id" value="<?php echo $gw['id']; ?>">
                                <div class="form-check form-switch mb-3">
                                    <input class="form-check-input" type="checkbox" name="enabled" <?php echo $gw['enabled'] ? 'checked' : ''; ?>>
                                    <label class="form-check-label">Enable Gateway</label>
                                </div>
                                <?php foreach ($gw['config'] as $key => $val): ?>
                                    <div class="mb-2">
                                        <label class="form-label small text-uppercase fw-bold text-muted"><?php echo str_replace('_', ' ', $key); ?></label>
                                        <input type="text" name="config[<?php echo $key; ?>]" class="form-control form-control-sm" value="<?php echo htmlspecialchars($val); ?>">
                                    </div>
                                <?php endforeach; ?>
                                <button type="submit" class="btn btn-sm btn-outline-primary mt-3">Update Gateway</button>
                            </form>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>

        <?php elseif ($tab === 'tax'): ?>
            <form method="POST">
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label">Tax / GST Percentage (%)</label>
                        <input type="number" step="0.01" name="settings[tax_gst_percentage]" class="form-control" value="<?php echo htmlspecialchars($settings['tax_gst_percentage']['setting_value'] ?? '0'); ?>">
                        <input type="hidden" name="types[tax_gst_percentage]" value="decimal">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Tax Type</label>
                        <select name="settings[tax_inclusive]" class="form-select">
                            <option value="0" <?php echo ($settings['tax_inclusive']['setting_value'] ?? '0') == '0' ? 'selected' : ''; ?>>Exclusive (Add tax to price)</option>
                            <option value="1" <?php echo ($settings['tax_inclusive']['setting_value'] ?? '0') == '1' ? 'selected' : ''; ?>>Inclusive (Price includes tax)</option>
                        </select>
                        <input type="hidden" name="types[tax_inclusive]" value="boolean">
                    </div>
                </div>
                <button type="submit" class="btn btn-primary mt-4">Save Tax Settings</button>
            </form>

        <?php elseif ($tab === 'features'): ?>
            <form method="POST">
                <div class="row g-3">
                    <div class="col-md-4">
                        <div class="form-check form-switch card p-3">
                            <input class="form-check-input ms-0 me-2" type="checkbox" name="settings[reviews_enabled]" value="1" <?php echo ($settings['reviews_enabled']['setting_value'] ?? '1') == '1' ? 'checked' : ''; ?>>
                            <label class="form-check-label fw-bold">Product Reviews</label>
                            <input type="hidden" name="types[reviews_enabled]" value="boolean">
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-check form-switch card p-3">
                            <input class="form-check-input ms-0 me-2" type="checkbox" name="settings[blog_enabled]" value="1" <?php echo ($settings['blog_enabled']['setting_value'] ?? '1') == '1' ? 'checked' : ''; ?>>
                            <label class="form-check-label fw-bold">Blog System</label>
                            <input type="hidden" name="types[blog_enabled]" value="boolean">
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-check form-switch card p-3">
                            <input class="form-check-input ms-0 me-2" type="checkbox" name="settings[returns_enabled]" value="1" <?php echo ($settings['returns_enabled']['setting_value'] ?? '1') == '1' ? 'checked' : ''; ?>>
                            <label class="form-check-label fw-bold">Returns & Refunds</label>
                            <input type="hidden" name="types[returns_enabled]" value="boolean">
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-check form-switch card p-3">
                            <input class="form-check-input ms-0 me-2" type="checkbox" name="settings[abandoned_cart_tracking]" value="1" <?php echo ($settings['abandoned_cart_tracking']['setting_value'] ?? '1') == '1' ? 'checked' : ''; ?>>
                            <label class="form-check-label fw-bold">Abandoned Cart Tracking</label>
                            <input type="hidden" name="types[abandoned_cart_tracking]" value="boolean">
                        </div>
                    </div>
                </div>
                <button type="submit" class="btn btn-primary mt-4">Save Features</button>
            </form>

        <?php elseif ($tab === 'seo'): ?>
            <form method="POST">
                <div class="mb-3">
                    <label class="form-label">Google Analytics Measurement ID</label>
                    <input type="text" name="settings[google_analytics_id]" class="form-control" value="<?php echo htmlspecialchars($settings['google_analytics_id']['setting_value'] ?? ''); ?>" placeholder="G-XXXXXXXXXX">
                    <input type="hidden" name="types[google_analytics_id]" value="string">
                </div>
                <div class="mb-3">
                    <label class="form-label">Default SEO Title</label>
                    <input type="text" name="settings[default_seo_title]" class="form-control" value="<?php echo htmlspecialchars($settings['default_seo_title']['setting_value'] ?? ''); ?>">
                    <input type="hidden" name="types[default_seo_title]" value="string">
                </div>
                <div class="mb-3">
                    <label class="form-label">Default SEO Description</label>
                    <textarea name="settings[default_seo_desc]" class="form-control" rows="3"><?php echo htmlspecialchars($settings['default_seo_desc']['setting_value'] ?? ''); ?></textarea>
                    <input type="hidden" name="types[default_seo_desc]" value="string">
                </div>
                <button type="submit" class="btn btn-primary mt-4">Save SEO Settings</button>
            </form>

        <?php elseif ($tab === 'users'): ?>
            <div class="table-responsive mb-4">
                <table class="table table-sm align-middle">
                    <thead class="table-light">
                        <tr>
                            <th>Username</th>
                            <th>Email</th>
                            <th>Role</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($users as $u): ?>
                        <tr>
                            <td><strong><?php echo htmlspecialchars($u['username']); ?></strong></td>
                            <td><?php echo htmlspecialchars($u['email']); ?></td>
                            <td class="text-capitalize small"><?php echo htmlspecialchars($u['role']); ?></td>
                            <td><span class="badge bg-success">Active</span></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            
            <h6 class="font-weight-bold mb-3 border-top pt-3">Add New Admin User</h6>
            <form method="POST" action="?tab=users">
                <input type="hidden" name="tab" value="add_user">
                <div class="row g-2">
                    <div class="col-md-3">
                        <input type="text" name="username" class="form-control" placeholder="Username" required>
                    </div>
                    <div class="col-md-3">
                        <input type="email" name="email" class="form-control" placeholder="Email" required>
                    </div>
                    <div class="col-md-3">
                        <input type="password" name="password" class="form-control" placeholder="Password" required>
                    </div>
                    <div class="col-md-3">
                        <select name="role" class="form-select" required>
                            <option value="super-admin">Super Admin</option>
                            <option value="manager">Manager</option>
                            <option value="inventory">Inventory</option>
                            <option value="orders">Orders</option>
                            <option value="support">Support</option>
                        </select>
                    </div>
                </div>
                <button type="submit" class="btn btn-primary mt-3">Add User</button>
            </form>
        <?php endif; ?>

    </div>
</div>

<?php
$content = ob_get_clean();
admin_layout("Settings", $content);
?>
