<?php
require_once '../includes/db_connection.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';
require_once 'layout.php';

check_admin_auth();
require_role(['super-admin', 'manager', 'inventory']);

$action = $_GET['action'] ?? 'list';
$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($action === 'create' || $action === 'edit') {
        $name = sanitize($_POST['name']);
        $description = $_POST['description']; // Allow rich text
        $sku = sanitize($_POST['sku']);
        $category_id = (int)$_POST['category_id'];
        
        // Fetch category name to check if it's a combo
        $stmt_cat = $pdo->prepare("SELECT name FROM categories WHERE id = ?");
        $stmt_cat->execute([$category_id]);
        $cat_data = $stmt_cat->fetch();
        $is_combo = $cat_data && stripos($cat_data['name'], 'combo') !== false;

        if ($is_combo) {
            $price = sanitize($_POST['price']);
        } else {
            $p250 = (float)$_POST['price_250g'];
            $p1kg = (float)$_POST['price_1kg'];
            $price = $p250 . ' - ' . $p1kg;
        }

        $price_1kg = (float)$_POST['price_1kg'];
        $price_500g = (float)$_POST['price_500g'];
        $price_250g = (float)$_POST['price_250g'];
        $stock = (int)$_POST['stock'];
        $status = (int)$_POST['status'];
        $slug = generate_slug($name);
        
        // Ensure unique slug
        $base_slug = $slug;
        $counter = 1;
        while (true) {
            $check_slug_sql = "SELECT id FROM products WHERE slug = ?";
            $check_slug_params = [$slug];
            if ($action === 'edit') {
                $check_slug_sql .= " AND id != ?";
                $check_slug_params[] = (int)$_POST['id'];
            }
            $stmt = $pdo->prepare($check_slug_sql);
            $stmt->execute($check_slug_params);
            if ($stmt->fetch()) {
                $slug = $base_slug . '-' . $counter;
                $counter++;
            } else {
                break;
            }
        }
        
        // Auto-generate SKU if empty
        if (empty($sku)) {
            $sku = 'GAP-' . strtoupper(substr(uniqid(), -6));
        }
        
        // Check for duplicate SKU
        $check_sql = "SELECT id FROM products WHERE sku = ?";
        $check_params = [$sku];
        if ($action === 'edit') {
            $check_sql .= " AND id != ?";
            $check_params[] = (int)$_POST['id'];
        }
        $stmt = $pdo->prepare($check_sql);
        $stmt->execute($check_params);
        if ($stmt->fetch()) {
            $error = "Error: The SKU '$sku' is already in use by another product. Please use a unique SKU.";
        } else {
            // Handle existing images and reordering
            $current_images = $_POST['current_images'] ?? [];
            $deleted_images = $_POST['deleted_images'] ?? [];
            
            // Remove deleted images from current images
            $images = array_values(array_diff($current_images, $deleted_images));
            
            // Handle new image upload
            if (!empty($_FILES['product_images']['name'][0])) {
                foreach ($_FILES['product_images']['tmp_name'] as $key => $tmp_name) {
                    $file = [
                        'name' => $_FILES['product_images']['name'][$key],
                        'type' => $_FILES['product_images']['type'][$key],
                        'tmp_name' => $_FILES['product_images']['tmp_name'][$key],
                        'error' => $_FILES['product_images']['error'][$key],
                        'size' => $_FILES['product_images']['size'][$key]
                    ];
                    $uploaded_file = handle_file_upload($file);
                    if ($uploaded_file) $images[] = $uploaded_file;
                }
            }
            
            $images_json = json_encode($images);
            
            try {
                if ($action === 'create') {
                    $stmt = $pdo->prepare("INSERT INTO products (name, description, sku, category_id, price, price_1kg, price_500g, price_250g, stock, status, slug, images) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                    $stmt->execute([$name, $description, $sku, $category_id, $price, $price_1kg, $price_500g, $price_250g, $stock, $status, $slug, $images_json]);
                    $message = "Product created successfully!";
                } else {
                    $id = (int)$_POST['id'];
                    $stmt = $pdo->prepare("UPDATE products SET name=?, description=?, sku=?, category_id=?, price=?, price_1kg=?, price_500g=?, price_250g=?, stock=?, status=?, slug=?, images=? WHERE id=?");
                    $stmt->execute([$name, $description, $sku, $category_id, $price, $price_1kg, $price_500g, $price_250g, $stock, $status, $slug, $images_json, $id]);
                    $message = "Product updated successfully!";
                }
                $action = 'list';
            } catch (PDOException $e) {
                $error = "Database Error: " . $e->getMessage();
            }
        }
    }
}

if ($action === 'delete') {
    $id = (int)$_GET['id'];
    $pdo->prepare("DELETE FROM products WHERE id = ?")->execute([$id]);
    $message = "Product deleted successfully!";
    $action = 'list';
}

// Fetch data for list
$products = $pdo->query("SELECT p.*, c.name as category_name FROM products p LEFT JOIN categories c ON p.category_id = c.id ORDER BY p.created_at DESC")->fetchAll();
$categories = $pdo->query("SELECT id, name FROM categories")->fetchAll();

ob_start();
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h4 class="fw-bold mb-0">Products</h4>
    <?php if ($action === 'list'): ?>
        <a href="?action=create" class="btn btn-primary shadow-sm px-4"><i class="fas fa-plus me-2"></i>Add Product</a>
    <?php endif; ?>
</div>

<?php if ($message): ?>
    <div class="alert alert-success alert-dismissible fade show border-0 shadow-sm mb-4"><?php echo $message; ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
<?php endif; ?>

<?php if ($error): ?>
    <div class="alert alert-danger alert-dismissible fade show border-0 shadow-sm mb-4"><?php echo $error; ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
<?php endif; ?>

<?php if ($action === 'list'): ?>
    <div class="card border-0 shadow-sm rounded-4">
        <div class="card-body p-4">
            <div class="table-responsive">
                <table class="table table-hover align-middle datatable">
                    <thead class="bg-light">
                        <tr>
                            <th class="border-0">Image</th>
                            <th class="border-0">Name</th>
                            <th class="border-0">SKU</th>
                            <th class="border-0">Category</th>
                            <th class="border-0">Price</th>
                            <th class="border-0">Stock</th>
                            <th class="border-0 text-center">Status</th>
                            <th class="border-0 text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="border-top-0">
                        <?php foreach ($products as $p): 
                            $imgs = json_decode($p['images'], true);
                            $img_src = !empty($imgs) ? format_image_path($imgs[0]) : 'https://via.placeholder.com/50';
                        ?>
                        <tr>
                            <td><img src="<?php echo $img_src; ?>" width="45" height="45" class="rounded shadow-sm object-fit-cover border"></td>
                            <td class="fw-bold text-dark"><?php echo htmlspecialchars($p['name']); ?></td>
                            <td class="small text-muted font-monospace"><?php echo htmlspecialchars($p['sku']); ?></td>
                            <td><span class="badge bg-light text-dark border"><?php echo htmlspecialchars($p['category_name']); ?></span></td>
                            <td class="fw-bold text-dark">
                                <?php 
                                // Split by any dash-like character with or without spaces
                                $price_parts = preg_split('/\s*[–-]\s*/u', $p['price']);
                                $formatted_parts = array_map(function($part) {
                                    $part = trim($part);
                                    if (empty($part)) return '';
                                    return (strpos($part, '₹') === false ? '₹' : '') . $part;
                                }, $price_parts);
                                echo htmlspecialchars(implode(' – ', array_filter($formatted_parts)));
                                ?>
                            </td>
                            <td>
                                <span class="fw-semibold <?php echo $p['stock'] < 10 ? 'text-danger' : 'text-dark'; ?>">
                                    <?php echo $p['stock']; ?>
                                </span>
                            </td>
                            <td class="text-center">
                                <span class="status-badge <?php echo $p['status'] ? 'bg-success-subtle text-success' : 'bg-danger-subtle text-danger'; ?>">
                                    <?php echo $p['status'] ? 'Active' : 'Inactive'; ?>
                                </span>
                            </td>
                            <td class="text-end">
                                <div class="d-flex justify-content-end gap-2">
                                    <a href="?action=edit&id=<?php echo $p['id']; ?>" class="btn btn-sm btn-outline-primary rounded-pill px-3"><i class="fas fa-edit"></i></a>
                                    <a href="?action=delete&id=<?php echo $p['id']; ?>" class="btn btn-sm btn-outline-danger rounded-pill px-3" onclick="return confirm('Delete this product?')"><i class="fas fa-trash"></i></a>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
<?php elseif ($action === 'create' || $action === 'edit'): 
    $p = ['name'=>'', 'description'=>'', 'sku'=>'', 'category_id'=>'', 'price'=>'0.00', 'price_1kg'=>'0.00', 'price_500g'=>'0.00', 'price_250g'=>'0.00', 'stock'=>'0', 'status'=>1, 'id'=>0];
    if ($action === 'edit') {
        $id = (int)$_GET['id'];
        $stmt = $pdo->prepare("SELECT * FROM products WHERE id = ?");
        $stmt->execute([$id]);
        $p = $stmt->fetch();
    }
?>
    <div class="card shadow-sm border-0">
        <div class="card-body p-4">
            <form method="POST" enctype="multipart/form-data">
                <input type="hidden" name="id" value="<?php echo $p['id']; ?>">
                <div class="row">
                    <div class="col-md-8">
                        <div class="mb-3">
                            <label class="form-label">Product Name</label>
                            <input type="text" name="name" class="form-control" value="<?php echo htmlspecialchars($p['name']); ?>" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Description</label>
                            <textarea name="description" id="editor" class="form-control" rows="10"><?php echo htmlspecialchars($p['description']); ?></textarea>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="mb-3">
                            <label class="form-label">Category</label>
                            <select name="category_id" id="category_id" class="form-select" required>
                                <option value="">Select Category</option>
                                <?php foreach ($categories as $cat): ?>
                                    <option value="<?php echo $cat['id']; ?>" data-name="<?php echo htmlspecialchars($cat['name']); ?>" <?php echo $cat['id'] == $p['category_id'] ? 'selected' : ''; ?>><?php echo htmlspecialchars($cat['name']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">SKU</label>
                            <input type="text" name="sku" class="form-control" value="<?php echo htmlspecialchars($p['sku']); ?>" placeholder="Leave empty to auto-generate">
                            <small class="text-muted">Unique identifier for the product.</small>
                        </div>
                        <div class="mb-3" id="price-container">
                            <label class="form-label" id="price-label">Price (Default/Base)</label>
                            <input type="text" name="price" class="form-control" value="<?php echo htmlspecialchars($p['price']); ?>" placeholder="e.g. 65 - 250" required>
                        </div>
                        <div id="weight-prices-container">
                            <div class="row">
                                <div class="col-md-4 mb-3">
                                    <label class="form-label">Price 1kg</label>
                                    <input type="number" step="0.01" name="price_1kg" class="form-control" value="<?php echo $p['price_1kg']; ?>">
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label class="form-label">Price 1/2kg</label>
                                    <input type="number" step="0.01" name="price_500g" class="form-control" value="<?php echo $p['price_500g']; ?>">
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label class="form-label">Price 1/4kg</label>
                                    <input type="number" step="0.01" name="price_250g" class="form-control" value="<?php echo $p['price_250g']; ?>">
                                </div>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Stock</label>
                            <input type="number" name="stock" class="form-control" value="<?php echo $p['stock']; ?>" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Status</label>
                            <select name="status" class="form-select">
                                <option value="1" <?php echo $p['status'] == 1 ? 'selected' : ''; ?>>Active</option>
                                <option value="0" <?php echo $p['status'] == 0 ? 'selected' : ''; ?>>Inactive</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Images</label>
                            <input type="file" name="product_images[]" class="form-control" multiple accept="image/*">
                            <small class="text-muted">Upload new images. You can reorder or delete existing ones below.</small>
                        </div>
                        
                        <?php 
                        $imgs = json_decode($p['images'] ?? '[]', true);
                        if (!empty($imgs)): ?>
                        <div class="mb-3">
                            <label class="form-label d-block">Manage Existing Images (Drag to reorder)</label>
                            <div id="image-management-list" class="row g-2">
                                <?php foreach ($imgs as $idx => $img): ?>
                                <div class="col-6 col-md-4 image-item" data-filename="<?php echo $img; ?>">
                                    <div class="card h-100 border shadow-sm">
                                        <div class="position-relative">
                                            <img src="<?php echo format_image_path($img); ?>" class="card-img-top object-fit-cover" style="height: 100px;">
                                            <button type="button" class="btn btn-sm btn-danger position-absolute top-0 end-0 m-1 delete-image" title="Delete image">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </div>
                                        <div class="card-footer p-1 bg-light text-center">
                                            <small class="priority-label text-muted">Priority: <?php echo $idx + 1; ?></small>
                                            <input type="hidden" name="current_images[]" value="<?php echo $img; ?>" class="image-filename-input">
                                        </div>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            </div>
                            <div id="deleted-images-container"></div>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
                <hr>
                <div class="d-flex justify-content-end gap-2">
                    <a href="?action=list" class="btn btn-secondary">Cancel</a>
                    <button type="submit" class="btn btn-primary">Save Product</button>
                </div>
            </form>
        </div>
    </div>
<?php endif; ?>

<?php
$content = ob_get_clean();
$scripts = '
<link href="https://cdn.jsdelivr.net/npm/summernote@0.8.18/dist/summernote-lite.min.css" rel="stylesheet">
<script src="https://code.jquery.com/jquery-3.4.1.slim.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/summernote@0.8.18/dist/summernote-lite.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.0/Sortable.min.js"></script>
<script>
    $(document).ready(function() {
        $("#editor").summernote({
            placeholder: "Enter product description...",
            tabsize: 2,
            height: 300,
            toolbar: [
                ["style", ["style"]],
                ["font", ["bold", "underline", "clear"]],
                ["color", ["color"]],
                ["para", ["ul", "ol", "paragraph"]],
                ["table", ["table"]],
                ["insert", ["link", "picture", "video"]],
                ["view", ["fullscreen", "codeview", "help"]]
            ]
        });

        function toggleWeightPrices() {
            var selectedOption = $("#category_id option:selected");
            var categoryName = (selectedOption.data("name") || "").toLowerCase();
            
            if (categoryName.indexOf("combo") !== -1) {
                $("#weight-prices-container").hide();
                $("#price-container").show();
                $("#price-label").text("Combo Price");
            } else {
                $("#weight-prices-container").show();
                $("#price-container").hide();
                $("#price-label").text("Price Range (Auto-calculated)");
            }
        }

        $("#category_id").change(toggleWeightPrices);
        toggleWeightPrices(); // Run on load

        // Image Management Logic
        var el = document.getElementById("image-management-list");
        if (el) {
            var sortable = Sortable.create(el, {
                animation: 150,
                onEnd: function() {
                    updatePriorities();
                }
            });
        }

        function updatePriorities() {
            $(".image-item").each(function(index) {
                $(this).find(".priority-label").text("Priority: " + (index + 1));
            });
        }

        $(".delete-image").on("click", function() {
            var $item = $(this).closest(".image-item");
            var filename = $item.data("filename");
            $("#deleted-images-container").append(\'<input type="hidden" name="deleted_images[]" value="\' + filename + \'">\');
            $item.fadeOut(300, function() {
                $(this).remove();
                updatePriorities();
            });
        });
    });
</script>';
admin_layout("Products", $content, $scripts);
?>
