<?php
require_once '../includes/db_connection.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';
require_once 'layout.php';

check_developer_auth();

$action = $_GET['action'] ?? 'list';
$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($action === 'create' || $action === 'edit') {
        $name = sanitize($_POST['name']);
        $slug = generate_slug($name);
        $api_base_url = sanitize($_POST['api_base_url']);
        $status = (int)$_POST['status'];
        
        if ($action === 'create') {
            $api_key = 'sk_' . bin2hex(random_bytes(16));
            $stmt = $pdo->prepare("INSERT INTO api_clients (name, slug, api_base_url, status, api_key) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$name, $slug, $api_base_url, $status, $api_key]);
            $message = "API Client created! Default key: <code>$api_key</code>";
        } else {
            $id = (int)$_POST['id'];
            $stmt = $pdo->prepare("UPDATE api_clients SET name=?, slug=?, api_base_url=?, status=? WHERE id=?");
            $stmt->execute([$name, $slug, $api_base_url, $status, $id]);
            $message = "API Client updated successfully!";
        }
        $action = 'list';
    }
}

if ($action === 'delete') {
    $id = (int)$_GET['id'];
    $pdo->prepare("DELETE FROM api_clients WHERE id = ?")->execute([id]);
    $message = "API Client removed!";
    $action = 'list';
}

$clients = $pdo->query("SELECT * FROM api_clients ORDER BY created_at DESC")->fetchAll();

ob_start();
?>

<?php if ($message): ?>
    <div class="alert alert-info alert-dismissible fade show"><?php echo $message; ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
<?php endif; ?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h3 class="mb-0">Manage API Clients</h3>
    <?php if ($action === 'list'): ?>
        <a href="?action=create" class="btn btn-primary"><i class="fas fa-plus me-2"></i>Add New Client</a>
    <?php endif; ?>
</div>

<?php if ($action === 'list'): ?>
    <div class="row">
        <?php foreach ($clients as $c): ?>
        <div class="col-md-6 col-lg-4 mb-4">
            <div class="card card-dev h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start mb-3">
                        <h5 class="card-title fw-bold mb-0"><?php echo htmlspecialchars($c['name']); ?></h5>
                        <span class="badge bg-<?php echo $c['status'] ? 'success' : 'danger'; ?>"><?php echo $c['status'] ? 'Active' : 'Inactive'; ?></span>
                    </div>
                    <p class="text-muted small mb-2"><i class="fas fa-link me-2"></i><?php echo htmlspecialchars($c['api_base_url'] ?: 'No URL provided'); ?></p>
                    <p class="text-muted small mb-4"><i class="fas fa-clock me-2"></i>Created: <?php echo date('M d, Y', strtotime($c['created_at'])); ?></p>
                    
                    <div class="mb-3">
                        <label class="form-label small opacity-75">API KEY</label>
                        <div class="input-group input-group-sm">
                            <input type="text" class="form-control bg-light border-0" value="<?php echo htmlspecialchars($c['api_key']); ?>" readonly>
                            <button class="btn btn-outline-secondary border-0" onclick="navigator.clipboard.writeText('<?php echo $c['api_key']; ?>')"><i class="fas fa-copy"></i></button>
                        </div>
                    </div>
                    
                    <div class="d-flex justify-content-end gap-2 mt-4 pt-3 border-top">
                        <a href="?action=edit&id=<?php echo $c['id']; ?>" class="btn btn-sm btn-outline-primary"><i class="fas fa-edit me-1"></i>Edit</a>
                        <a href="?action=delete&id=<?php echo $c['id']; ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('Delete this client?')"><i class="fas fa-trash me-1"></i>Delete</a>
                    </div>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
<?php elseif ($action === 'create' || $action === 'edit'): 
    $c = ['name'=>'', 'api_base_url'=>'', 'status'=>1, 'id'=>0];
    if ($action === 'edit') {
        $id = (int)$_GET['id'];
        $stmt = $pdo->prepare("SELECT * FROM api_clients WHERE id = ?");
        $stmt->execute([$id]);
        $c = $stmt->fetch();
    }
?>
    <div class="card card-dev shadow-sm">
        <div class="card-body p-4">
            <form method="POST">
                <input type="hidden" name="id" value="<?php echo $c['id']; ?>">
                <div class="mb-3">
                    <label class="form-label">Client Name</label>
                    <input type="text" name="name" class="form-control" value="<?php echo htmlspecialchars($c['name']); ?>" required placeholder="e.g. My Next.js Frontend">
                </div>
                <div class="mb-3">
                    <label class="form-label">API Base URL</label>
                    <input type="url" name="api_base_url" class="form-control" value="<?php echo htmlspecialchars($c['api_base_url']); ?>" placeholder="https://myapp.com">
                    <small class="text-muted">Optional: The URL where your frontend is hosted.</small>
                </div>
                <div class="mb-3">
                    <label class="form-label">Status</label>
                    <select name="status" class="form-select">
                        <option value="1" <?php echo $c['status'] == 1 ? 'selected' : ''; ?>>Active</option>
                        <option value="0" <?php echo $c['status'] == 0 ? 'selected' : ''; ?>>Inactive</option>
                    </select>
                </div>
                <div class="mt-4 pt-3 border-top d-flex justify-content-end gap-2">
                    <a href="?action=list" class="btn btn-secondary">Cancel</a>
                    <button type="submit" class="btn btn-primary">Save API Client</button>
                </div>
            </form>
        </div>
    </div>
<?php endif; ?>

<?php
$content = ob_get_clean();
developer_layout("API Clients", $content);
?>
