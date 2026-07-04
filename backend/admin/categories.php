<?php
require_once '../includes/db_connection.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';
require_once 'layout.php';

check_admin_auth();
require_role(['super-admin', 'manager', 'inventory']);

$action = $_GET['action'] ?? 'list';
$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($action === 'create' || $action === 'edit') {
        $name = sanitize($_POST['name']);
        $parent_id = (int)$_POST['parent_id'];
        $description = sanitize($_POST['description']);
        $slug = generate_slug($name);
        
        // Handle category image
        $image = '';
        if (!empty($_FILES['category_image']['name'])) {
            $image = handle_file_upload($_FILES['category_image']);
        }
        
        if ($action === 'create') {
            $stmt = $pdo->prepare("INSERT INTO categories (name, parent_id, description, slug, image) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$name, $parent_id, $description, $slug, $image]);
            $message = "Category created successfully!";
        } else {
            $id = (int)$_POST['id'];
            $stmt = $pdo->prepare("UPDATE categories SET name=?, parent_id=?, description=?, slug=?, image=IF(?='', image, ?) WHERE id=?");
            $stmt->execute([$name, $parent_id, $description, $slug, $image, $image, $id]);
            $message = "Category updated successfully!";
        }
        $action = 'list';
    }
}

if ($action === 'delete') {
    $id = (int)$_GET['id'];
    $pdo->prepare("DELETE FROM categories WHERE id = ?")->execute([$id]);
    $message = "Category deleted successfully!";
    $action = 'list';
}

$categories = $pdo->query("SELECT c1.*, c2.name as parent_name FROM categories c1 LEFT JOIN categories c2 ON c1.parent_id = c2.id ORDER BY c1.name ASC")->fetchAll();

ob_start();
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h2>Categories</h2>
    <?php if ($action === 'list'): ?>
        <a href="?action=create" class="btn btn-primary"><i class="fas fa-plus me-2"></i>Add Category</a>
    <?php endif; ?>
</div>

<?php if ($message): ?>
    <div class="alert alert-success alert-dismissible fade show"><?php echo $message; ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
<?php endif; ?>

<?php if ($action === 'list'): ?>
    <div class="card shadow-sm border-0">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead class="table-light">
                        <tr>
                            <th>Image</th>
                            <th>Name</th>
                            <th>Parent Category</th>
                            <th>Slug</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($categories as $c): 
                            $img_src = !empty($c['image']) ? format_image_path($c['image']) : 'https://via.placeholder.com/50';
                        ?>
                        <tr>
                            <td><img src="<?php echo $img_src; ?>" width="50" height="50" class="rounded object-fit-cover"></td>
                            <td><?php echo htmlspecialchars($c['name']); ?></td>
                            <td><?php echo htmlspecialchars($c['parent_name'] ?: 'None'); ?></td>
                            <td><?php echo htmlspecialchars($c['slug']); ?></td>
                            <td>
                                <a href="?action=edit&id=<?php echo $c['id']; ?>" class="btn btn-sm btn-outline-primary"><i class="fas fa-edit"></i></a>
                                <a href="?action=delete&id=<?php echo $c['id']; ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('Delete this category?')"><i class="fas fa-trash"></i></a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
<?php elseif ($action === 'create' || $action === 'edit'): 
    $c = ['name'=>'', 'parent_id'=>0, 'description'=>'', 'id'=>0];
    if ($action === 'edit') {
        $id = (int)$_GET['id'];
        $stmt = $pdo->prepare("SELECT * FROM categories WHERE id = ?");
        $stmt->execute([$id]);
        $c = $stmt->fetch();
    }
?>
    <div class="card shadow-sm border-0">
        <div class="card-body p-4">
            <form method="POST" enctype="multipart/form-data">
                <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                <input type="hidden" name="id" value="<?php echo $c['id']; ?>">
                <div class="mb-3">
                    <label class="form-label">Category Name</label>
                    <input type="text" name="name" class="form-control" value="<?php echo htmlspecialchars($c['name']); ?>" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">Parent Category</label>
                    <select name="parent_id" class="form-select">
                        <option value="0">None (Top Level)</option>
                        <?php foreach ($categories as $cat): if ($cat['id'] == $c['id']) continue; ?>
                            <option value="<?php echo $cat['id']; ?>" <?php echo $cat['id'] == $c['parent_id'] ? 'selected' : ''; ?>><?php echo htmlspecialchars($cat['name']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="mb-3">
                    <label class="form-label">Description</label>
                    <textarea name="description" class="form-control" rows="3"><?php echo htmlspecialchars($c['description']); ?></textarea>
                </div>
                <div class="mb-3">
                    <label class="form-label">Category Image</label>
                    <input type="file" name="category_image" class="form-control" accept="image/*">
                </div>
                <hr>
                <div class="d-flex justify-content-end gap-2">
                    <a href="?action=list" class="btn btn-secondary">Cancel</a>
                    <button type="submit" class="btn btn-primary">Save Category</button>
                </div>
            </form>
        </div>
    </div>
<?php endif; ?>

<?php
$content = ob_get_clean();
admin_layout("Categories", $content);
?>
