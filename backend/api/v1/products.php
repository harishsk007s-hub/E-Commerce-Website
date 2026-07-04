<?php
require_once 'api_init.php';

$action = $_GET['action'] ?? 'list';

try {
    if ($action === 'list') {
        $category_id = (int)($_GET['category_id'] ?? 0);
        $search = sanitize($_GET['search'] ?? '');
        $page = (int)($_GET['page'] ?? 1);
        $limit = 1000;
        $offset = ($page - 1) * $limit;
        
        $sql = "SELECT p.*, c.name as category_name 
                FROM products p 
                LEFT JOIN categories c ON p.category_id = c.id 
                WHERE p.status = 1";
        $params = [];
        
        if ($category_id > 0) {
            $sql .= " AND p.category_id = ?";
            $params[] = $category_id;
        }
        
        if (!empty($search)) {
            $sql .= " AND (p.name LIKE ? OR p.description LIKE ?)";
            $params[] = "%$search%";
            $params[] = "%$search%";
        }
        
        $sql .= " ORDER BY p.created_at DESC LIMIT $limit OFFSET $offset";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $products = $stmt->fetchAll();
        
        log_api_call($pdo, 200);
        json_response([
            'status' => 'success',
            'page' => $page,
            'products' => array_map(function($p) {
                $p['images'] = !empty($p['images']) ? json_decode($p['images'], true) : [];
                if (json_last_error() !== JSON_ERROR_NONE) $p['images'] = [];
                
                $p['variants'] = !empty($p['variants']) ? json_decode($p['variants'], true) : [];
                if (json_last_error() !== JSON_ERROR_NONE) $p['variants'] = [];
                
                return $p;
            }, $products)
        ]);
        
    } elseif ($action === 'sync') {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            json_response(['error' => 'POST method required'], 405);
        }
        
        $data = json_decode(file_get_contents('php://input'), true);
        $products = $data['products'] ?? [];
        
        if (empty($products)) {
            json_response(['error' => 'No products provided'], 400);
        }
        
        $pdo->beginTransaction();
        try {
            $stmt = $pdo->prepare("INSERT INTO products (name, sku, price, price_1kg, price_500g, price_250g, category_id, slug, status, images, description) 
                                   VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?) 
                                   ON DUPLICATE KEY UPDATE 
                                   name = VALUES(name), 
                                   price = VALUES(price), 
                                   price_1kg = VALUES(price_1kg),
                                   price_500g = VALUES(price_500g),
                                   price_250g = VALUES(price_250g),
                                   category_id = VALUES(category_id), 
                                   slug = VALUES(slug), 
                                   status = VALUES(status), 
                                   images = VALUES(images), 
                                   description = VALUES(description)");
                                   
            foreach ($products as $p) {
                // Find or create category
                $cat_name = $p['category'] ?? 'Papadam';
                $stmt_cat = $pdo->prepare("SELECT id FROM categories WHERE name = ?");
                $stmt_cat->execute([$cat_name]);
                $cat_id = $stmt_cat->fetchColumn();
                
                if (!$cat_id) {
                    $stmt_cat_ins = $pdo->prepare("INSERT INTO categories (name, slug) VALUES (?, ?)");
                    $slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $cat_name)));
                    $stmt_cat_ins->execute([$cat_name, $slug]);
                    $cat_id = $pdo->lastInsertId();
                }
                
                $images = json_encode($p['images'] ?? []);
                $status = 1;
                $slug = $p['slug'] ?? generate_slug($p['name']);
                
                $stmt->execute([
                    $p['name'],
                    $p['sku'] ?? ('SYNC-' . uniqid()),
                    $p['price'],
                    $p['price_1kg'] ?? '250.00',
                    $p['price_500g'] ?? '130.00',
                    $p['price_250g'] ?? '65.00',
                    $cat_id,
                    $slug,
                    $status,
                    $images,
                    $p['description'] ?? ''
                ]);
            }
            
            $pdo->commit();
            log_api_call($pdo, 200);
            json_response(['status' => 'success', 'message' => count($products) . ' products synced']);
        } catch (Exception $e) {
            $pdo->rollBack();
            throw $e;
        }
        
    } elseif ($action === 'view') {
        $id = (int)($_GET['id'] ?? 0);
        $slug = sanitize($_GET['slug'] ?? '');
        
        if ($id > 0) {
            $stmt = $pdo->prepare("SELECT p.*, c.name as category_name 
                                   FROM products p 
                                   LEFT JOIN categories c ON p.category_id = c.id 
                                   WHERE p.id = ? AND p.status = 1");
            $stmt->execute([$id]);
        } elseif (!empty($slug)) {
            $stmt = $pdo->prepare("SELECT p.*, c.name as category_name 
                                   FROM products p 
                                   LEFT JOIN categories c ON p.category_id = c.id 
                                   WHERE p.slug = ? AND p.status = 1");
            $stmt->execute([$slug]);
        } else {
            json_response(['error' => 'Product ID or Slug required'], 400);
        }
        
        $product = $stmt->fetch();
        
        if (!$product) {
            log_api_call($pdo, 404, 'Product not found');
            json_response(['error' => 'Product not found'], 404);
        }
        
        // Handle potential missing columns safely
        $product['images'] = !empty($product['images']) ? json_decode($product['images'], true) : [];
        if (json_last_error() !== JSON_ERROR_NONE) $product['images'] = [];
        
        $product['variants'] = !empty($product['variants']) ? json_decode($product['variants'], true) : [];
        if (json_last_error() !== JSON_ERROR_NONE) $product['variants'] = [];

        $product['tags'] = !empty($product['tags']) ? json_decode($product['tags'], true) : [];
        if (json_last_error() !== JSON_ERROR_NONE) $product['tags'] = [];
        
        log_api_call($pdo, 200);
        json_response(['status' => 'success', 'product' => $product]);
    }
} catch (Exception $e) {
    log_api_call($pdo, 500, $e->getMessage());
    json_response(['error' => 'Server Error'], 500);
}
