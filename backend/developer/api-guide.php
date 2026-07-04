<?php
require_once '../includes/db_connection.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';
require_once 'layout.php';

check_developer_auth();

$api_base = "https://delight.goappalam.in/backend/api/v1/";

ob_start();
?>

<div class="card card-dev shadow-sm mb-4">
    <div class="card-header bg-white py-3">
        <h6 class="m-0 font-weight-bold text-primary">Getting Started</h6>
    </div>
    <div class="card-body">
        <p>The eCommerce Backend provides a headless REST API to manage products, categories, carts, and orders. All API requests must be authenticated using the <code>X-API-KEY</code> header.</p>
        
        <div class="bg-light p-3 rounded mb-4 border border-secondary border-opacity-25">
            <h6 class="fw-bold mb-2">Base API URL</h6>
            <code class="fs-5"><?php echo $api_base; ?></code>
        </div>
        
        <h5 class="fw-bold mb-3"><i class="fas fa-plug me-2 text-primary"></i>Authentication Example</h5>
        <pre class="bg-dark text-light p-3 rounded"><code>fetch('<?php echo $api_base; ?>products', {
  method: 'GET',
  headers: {
    'X-API-KEY': 'YOUR_CLIENT_API_KEY',
    'Content-Type': 'application/json'
  }
})
.then(res => res.json())
.then(data => console.log(data));</code></pre>
    </div>
</div>

<div class="card card-dev shadow-sm mb-4">
    <div class="card-header bg-white py-3">
        <h6 class="m-0 font-weight-bold text-primary">Endpoints Reference</h6>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-bordered">
                <thead class="table-light">
                    <tr>
                        <th style="width: 250px;">Endpoint</th>
                        <th>Method</th>
                        <th>Description</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td><code>products.php</code></td>
                        <td><span class="badge bg-success">GET</span></td>
                        <td>List products with optional pagination and category filters.</td>
                    </tr>
                    <tr>
                        <td><code>products.php?id={ID}</code></td>
                        <td><span class="badge bg-success">GET</span></td>
                        <td>Get detailed product information.</td>
                    </tr>
                    <tr>
                        <td><code>cart.php?action=add</code></td>
                        <td><span class="badge bg-primary">POST</span></td>
                        <td>Add product to the cart (requires <code>session_id</code>).</td>
                    </tr>
                    <tr>
                        <td><code>cart.php?action=view</code></td>
                        <td><span class="badge bg-success">GET</span></td>
                        <td>View current cart items and totals.</td>
                    </tr>
                    <tr>
                        <td><code>orders.php?action=checkout</code></td>
                        <td><span class="badge bg-primary">POST</span></td>
                        <td>Place an order from the current cart.</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</div>

<div class="alert alert-info py-4">
    <div class="d-flex align-items-center">
        <i class="fas fa-info-circle fa-2x me-3"></i>
        <div>
            <h5 class="mb-1">Multi-Tenant Support</h5>
            <p class="mb-0">Carts and orders are automatically isolated based on your <code>X-API-KEY</code>. Data from one client ID never leaks into another client's context.</p>
        </div>
    </div>
</div>

<?php
$content = ob_get_clean();
developer_layout("API Integration Guide", $content);
?>
