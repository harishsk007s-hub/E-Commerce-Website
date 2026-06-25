<?php
require_once '../includes/db_connection.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';
require_once 'layout.php';

$indian_states = [
    "Andaman and Nicobar Islands", "Andhra Pradesh", "Arunachal Pradesh", "Assam", "Bihar", 
    "Chandigarh", "Chhattisgarh", "Dadra and Nagar Haveli and Daman and Diu", "Delhi", "Goa", 
    "Gujarat", "Haryana", "Himachal Pradesh", "Jammu and Kashmir", "Jharkhand", "Karnataka", 
    "Kerala", "Ladakh", "Lakshadweep", "Madhya Pradesh", "Maharashtra", "Manipur", "Meghalaya", 
    "Mizoram", "Nagaland", "Odisha", "Puducherry", "Punjab", "Rajasthan", "Sikkim", "Tamil Nadu", 
    "Telangana", "Tripura", "Uttar Pradesh", "Uttarakhand", "West Bengal"
];

check_admin_auth();
require_role(['super-admin', 'manager']);

$action = $_GET['action'] ?? 'list';
$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($action === 'create_zone' || $action === 'edit_zone') {
        $name = sanitize($_POST['name']);
        $country = sanitize($_POST['country']);
        $state = sanitize($_POST['state']);
        $city = sanitize($_POST['city']);
        $status = isset($_POST['status']) ? 1 : 0;
        
        if ($action === 'create_zone') {
            $stmt = $pdo->prepare("INSERT INTO shipping_zones (name, country, state, city, status) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$name, $country, $state, $city, $status]);
            $message = "Shipping zone created!";
        } else {
            $id = (int)$_POST['id'];
            $stmt = $pdo->prepare("UPDATE shipping_zones SET name=?, country=?, state=?, city=?, status=? WHERE id=?");
            $stmt->execute([$name, $country, $state, $city, $status, $id]);
            $message = "Shipping zone updated!";
        }
        $action = 'list';
    } elseif ($action === 'add_rule') {
        $zone_id = (int)$_POST['zone_id'];
        $flat_rate = (float)$_POST['flat_rate'];
        $free_shipping = isset($_POST['free_shipping']) ? 1 : 0;
        
        $stmt = $pdo->prepare("INSERT INTO shipping_rules (zone_id, min_amount, max_amount, flat_rate, free_shipping) VALUES (?, 0, 999999, ?, ?)");
        $stmt->execute([$zone_id, $flat_rate, $free_shipping]);
        $message = "Shipping rule added!";
        $action = 'list';
    }
}

if ($action === 'delete_zone') {
    $id = (int)$_GET['id'];
    $pdo->prepare("DELETE FROM shipping_zones WHERE id = ?")->execute([$id]);
    $message = "Zone deleted!";
    $action = 'list';
}

if ($action === 'delete_rule') {
    $id = (int)$_GET['id'];
    $pdo->prepare("DELETE FROM shipping_rules WHERE id = ?")->execute([$id]);
    $message = "Rule deleted!";
    $action = 'list';
}

$zones = $pdo->query("SELECT * FROM shipping_zones ORDER BY name ASC")->fetchAll();
foreach ($zones as &$z) {
    $stmt = $pdo->prepare("SELECT * FROM shipping_rules WHERE zone_id = ? ORDER BY min_amount ASC");
    $stmt->execute([$z['id']]);
    $z['rules'] = $stmt->fetchAll();
}

ob_start();
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h2>Shipping Management</h2>
    <?php if ($action === 'list'): ?>
        <a href="?action=create_zone" class="btn btn-primary"><i class="fas fa-plus me-2"></i>Add Shipping Zone</a>
    <?php endif; ?>
</div>

<?php if ($message): ?>
    <div class="alert alert-success alert-dismissible fade show"><?php echo $message; ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
<?php endif; ?>

<?php if ($action === 'list'): ?>
    <div class="row">
        <?php foreach ($zones as $z): ?>
        <div class="col-md-12 mb-4">
            <div class="card shadow-sm border-0">
                <div class="card-header bg-white d-flex justify-content-between align-items-center py-3">
                    <h5 class="mb-0 fw-bold"><?php echo htmlspecialchars($z['name']); ?> 
                        <span class="small text-muted fw-normal ms-2">(<?php echo htmlspecialchars($z['country'] ?: 'Global'); ?>)</span>
                        <?php if (!$z['status']): ?><span class="badge bg-danger ms-2">Disabled</span><?php endif; ?>
                    </h5>
                    <div>
                        <a href="?action=edit_zone&id=<?php echo $z['id']; ?>" class="btn btn-sm btn-outline-primary"><i class="fas fa-edit me-1"></i>Edit Zone</a>
                        <a href="?action=delete_zone&id=<?php echo $z['id']; ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('Delete zone and all its rules?')"><i class="fas fa-trash"></i></a>
                    </div>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-sm align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Shipping Fee (Flat Rate)</th>
                                    <th>Free Shipping</th>
                                    <th class="text-end">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($z['rules'] as $rule): ?>
                                <tr>
                                    <td>₹<?php echo number_format($rule['flat_rate'], 2); ?></td>
                                    <td><?php echo $rule['free_shipping'] ? '<span class="text-success"><i class="fas fa-check-circle"></i> Yes</span>' : 'No'; ?></td>
                                    <td class="text-end">
                                        <a href="?action=delete_rule&id=<?php echo $rule['id']; ?>" class="btn btn-link text-danger p-0" onclick="return confirm('Delete this rule?')"><i class="fas fa-times-circle"></i></a>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                                <tr class="bg-light bg-opacity-10">
                                    <form method="POST" action="?action=add_rule">
                                        <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                                        <input type="hidden" name="zone_id" value="<?php echo $z['id']; ?>">
                                        <td><input type="number" step="0.01" name="flat_rate" class="form-control form-control-sm" placeholder="10.00" required></td>
                                        <td>
                                            <div class="form-check form-switch mt-1">
                                                <input class="form-check-input" type="checkbox" name="free_shipping">
                                            </div>
                                        </td>
                                        <td class="text-end"><button type="submit" class="btn btn-sm btn-primary">Add Shipping Fee</button></td>
                                    </form>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
<?php elseif ($action === 'create_zone' || $action === 'edit_zone'): 
    $z = ['name'=>'', 'country'=>'', 'state'=>'', 'city'=>'', 'status'=>1, 'id'=>0];
    if ($action === 'edit_zone') {
        $id = (int)$_GET['id'];
        $stmt = $pdo->prepare("SELECT * FROM shipping_zones WHERE id = ?");
        $stmt->execute([$id]);
        $z = $stmt->fetch();
    }
?>
    <div class="card shadow-sm border-0">
        <div class="card-body p-4">
            <form method="POST">
                <input type="hidden" name="id" value="<?php echo $z['id']; ?>">
                <div class="row g-3">
                    <div class="col-md-12">
                        <label class="form-label">Zone Name (e.g. North America, Local Pickup)</label>
                        <input type="text" name="name" class="form-control" value="<?php echo htmlspecialchars($z['name']); ?>" required>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Country</label>
                        <input type="text" name="country" class="form-control" value="<?php echo htmlspecialchars($z['country']); ?>" placeholder="Leave empty for all">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">State / Province</label>
                        <select name="state" class="form-select">
                            <option value="">Global / All States</option>
                            <?php foreach ($indian_states as $st): ?>
                                <option value="<?php echo htmlspecialchars($st); ?>" <?php echo (strtolower($z['state']) === strtolower($st)) ? 'selected' : ''; ?>><?php echo htmlspecialchars($st); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">City</label>
                        <input type="text" name="city" class="form-control" value="<?php echo htmlspecialchars($z['city']); ?>" placeholder="Leave empty for all">
                    </div>
                    <div class="col-md-12">
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" name="status" <?php echo $z['status'] ? 'checked' : ''; ?>>
                            <label class="form-check-label">Zone Active</label>
                        </div>
                    </div>
                </div>
                <hr>
                <div class="d-flex justify-content-end gap-2">
                    <a href="?action=list" class="btn btn-secondary">Cancel</a>
                    <button type="submit" class="btn btn-primary">Save Shipping Zone</button>
                </div>
            </form>
        </div>
    </div>
<?php endif; ?>

<?php
$content = ob_get_clean();
admin_layout("Shipping", $content);
?>
