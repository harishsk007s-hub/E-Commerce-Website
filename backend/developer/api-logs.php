<?php
require_once '../includes/db_connection.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';
require_once 'layout.php';

check_developer_auth();

$logs = $pdo->query("SELECT l.*, c.name as client_name FROM api_logs l LEFT JOIN api_clients c ON l.client_id = c.id ORDER BY l.created_at DESC LIMIT 100")->fetchAll();

ob_start();
?>

<div class="card card-dev shadow-sm mb-4">
    <div class="card-header bg-white py-3">
        <h6 class="m-0 font-weight-bold text-primary">Recent API Activity (Last 100 Calls)</h6>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover align-middle">
                <thead class="table-light">
                    <tr>
                        <th>Client</th>
                        <th>Endpoint</th>
                        <th class="text-center">Method</th>
                        <th class="text-center">Status</th>
                        <th class="text-center">Time (ms)</th>
                        <th>IP Address</th>
                        <th>Timestamp</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($logs)): ?>
                    <tr>
                        <td colspan="7" class="text-center py-4 text-muted">No API logs available.</td>
                    </tr>
                    <?php endif; ?>
                    <?php foreach ($logs as $l): ?>
                    <tr>
                        <td><strong><?php echo htmlspecialchars($l['client_name'] ?: 'Unknown'); ?></strong></td>
                        <td><code><?php echo htmlspecialchars($l['endpoint']); ?></code></td>
                        <td class="text-center"><span class="badge bg-secondary text-uppercase"><?php echo htmlspecialchars($l['method']); ?></span></td>
                        <td class="text-center">
                            <span class="badge bg-<?php echo ($l['status'] >= 200 && $l['status'] < 300) ? 'success' : 'danger'; ?>">
                                <?php echo $l['status']; ?>
                            </span>
                        </td>
                        <td class="text-center"><?php echo $l['response_time']; ?>ms</td>
                        <td><small><?php echo htmlspecialchars($l['ip_address']); ?></small></td>
                        <td><?php echo date('M d, H:i:s', strtotime($l['created_at'])); ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php
$content = ob_get_clean();
developer_layout("API Traffic Logs", $content);
?>
