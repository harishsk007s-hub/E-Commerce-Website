<?php
require_once '../includes/db_connection.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';
require_once 'layout.php';

check_developer_auth();

$action = $_GET['action'] ?? 'list';
$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'regenerate') {
    $id = (int)$_POST['id'];
    $new_key = 'sk_' . bin2hex(random_bytes(16));
    
    $stmt = $pdo->prepare("UPDATE api_clients SET api_key = ? WHERE id = ?");
    $stmt->execute([$new_key, $id]);
    
    $message = "API Key regenerated successfully for client ID #$id";
}

$clients = $pdo->query("SELECT id, name, api_key, status, created_at FROM api_clients ORDER BY name ASC")->fetchAll();

ob_start();
?>

<?php if ($message): ?>
    <div class="alert alert-success alert-dismissible fade show"><?php echo $message; ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
<?php endif; ?>

<div class="card card-dev shadow-sm">
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover align-middle">
                <thead class="table-light">
                    <tr>
                        <th>Client Name</th>
                        <th>API Key</th>
                        <th>Status</th>
                        <th>Created At</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($clients as $c): ?>
                    <tr>
                        <td><strong><?php echo htmlspecialchars($c['name']); ?></strong></td>
                        <td>
                            <div class="input-group input-group-sm" style="max-width: 350px;">
                                <input type="text" class="form-control bg-light border-0" value="<?php echo htmlspecialchars($c['api_key']); ?>" readonly id="key-<?php echo $c['id']; ?>">
                                <button class="btn btn-outline-secondary border-0" onclick="navigator.clipboard.writeText('<?php echo $c['api_key']; ?>')"><i class="fas fa-copy"></i></button>
                            </div>
                        </td>
                        <td>
                            <span class="badge bg-<?php echo $c['status'] ? 'success' : 'danger'; ?> badge-pill">
                                <?php echo $c['status'] ? 'Active' : 'Revoked'; ?>
                            </span>
                        </td>
                        <td><?php echo date('M d, Y', strtotime($c['created_at'])); ?></td>
                        <td class="text-end">
                            <form method="POST" action="?action=regenerate" class="d-inline" onsubmit="return confirm('WARNING: This will invalidate the existing key. Continue?')">
                                <input type="hidden" name="id" value="<?php echo $c['id']; ?>">
                                <button type="submit" class="btn btn-sm btn-outline-warning">
                                    <i class="fas fa-sync-alt me-1"></i>Regenerate
                                </button>
                            </form>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<div class="alert alert-warning mt-4 small">
    <i class="fas fa-exclamation-triangle me-2"></i>
    <strong>Security Warning:</strong> API keys provide full access to your store's backend. Never share them in public repositories or frontend code that isn't properly secured.
</div>

<?php
$content = ob_get_clean();
developer_layout("API Keys Management", $content);
?>
