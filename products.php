<?php
require 'config.php';

// ----------------------------
// Session & Role Checking
// ----------------------------
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$roleName = strtolower($_SESSION['role_name'] ?? '');
$isAdminOrManager = in_array($roleName, ['admin', 'manager']);   // Admin 管理模式
$isCustomer = ($roleName === 'customer');                        // Customer 模式

$errors = [];
$success = "";

// ----------------------------
// Fetch Categories & Subcategories
// ----------------------------
$catStmt = $pdo->query("SELECT id, name FROM categories ORDER BY name ASC");
$categories = $catStmt->fetchAll(PDO::FETCH_ASSOC);

$subStmt = $pdo->query("
    SELECT s.id, s.name, c.name AS category_name
    FROM subcategories s
    JOIN categories c ON s.category_id = c.id
    ORDER BY c.name, s.name
");
$subcategories = $subStmt->fetchAll(PDO::FETCH_ASSOC);

// =============================================================
// Customer Add to Cart (POST: add_to_cart)
// =============================================================
if ($isCustomer && isset($_POST['add_to_cart'])) {

    $productId = (int) $_POST['product_id'];
    $quantity  = (int) $_POST['qty'];

    if ($quantity <= 0) $quantity = 1;

    // 检查是否存在 product
    $check = $pdo->prepare("SELECT id, name, price FROM products WHERE id = ?");
    $check->execute([$productId]);
    $product = $check->fetch(PDO::FETCH_ASSOC);

    if ($product) {

        // 如果 cart not exists → create cart table
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS cart (
                id INT AUTO_INCREMENT PRIMARY KEY,
                user_id INT NOT NULL,
                product_id INT NOT NULL,
                quantity INT NOT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            )
        ");

        // 查看该用户是否已添加过该商品
        $cartCheck = $pdo->prepare("
            SELECT id, quantity FROM cart 
            WHERE user_id = ? AND product_id = ?
        ");
        $cartCheck->execute([$_SESSION['user_id'], $productId]);
        $existing = $cartCheck->fetch(PDO::FETCH_ASSOC);

        if ($existing) {
            // 已存在 → 增加数量
            $update = $pdo->prepare("
                UPDATE cart SET quantity = quantity + ?
                WHERE id = ?
            ");
            $update->execute([$quantity, $existing['id']]);

        } else {
            // 新增购物车
            $insert = $pdo->prepare("
                INSERT INTO cart (user_id, product_id, quantity)
                VALUES (?, ?, ?)
            ");
            $insert->execute([$_SESSION['user_id'], $productId, $quantity]);
        }

        $success = "Product added to cart successfully!";
    }
}

// ----------------------------
// Handle Add Product (admin/manager only)
// ----------------------------
if ($isAdminOrManager && isset($_POST['add_product'])) {

    $name       = trim($_POST['name'] ?? '');
    $price      = trim($_POST['price'] ?? '');
    $qty        = trim($_POST['available_qty'] ?? '');
    $category_id    = (int)($_POST['category_id'] ?? 0);
    $subcategory_id = (int)($_POST['subcategory_id'] ?? 0);
    $image_url  = trim($_POST['product_image_url'] ?? '');
    $desc       = trim($_POST['description'] ?? '');

    // ---- Validation ----
    if ($name === '') {
        $errors[] = "Product name is required.";
    }
    if ($price === '' || !is_numeric($price) || $price < 0) {
        $errors[] = "Valid price is required.";
    }
    if ($qty === '' || !ctype_digit($qty)) {
        $errors[] = "Available quantity must be a non-negative integer.";
    }
    if ($category_id <= 0) {
        $errors[] = "Category is required.";
    }
    if ($subcategory_id <= 0) {
        $errors[] = "Subcategory is required.";
    }

    // ---- Insert into DB ----
    if (empty($errors)) {
        $stmt = $pdo->prepare("
            INSERT INTO products
            (name, price, available_qty, category_id, subcategory_id, product_image_url, description)
            VALUES (?, ?, ?, ?, ?, ?, ?)
        ");

        $ok = $stmt->execute([
            $name,
            $price,
            (int)$qty,
            $category_id,
            $subcategory_id,
            $image_url,
            $desc
        ]);

        if ($ok) {
            $success = "Product added successfully.";
        } else {
            $errors[] = "Failed to add product.";
        }
    }
}

// ----------------------------
// Fetch Product List
// ----------------------------
$prodStmt = $pdo->query("
    SELECT p.id, p.name, p.price, p.available_qty, p.product_image_url, p.description, p.created_at,
           c.name AS category_name, s.name AS subcategory_name
    FROM products p
    JOIN categories c ON p.category_id = c.id
    JOIN subcategories s ON p.subcategory_id = s.id
    ORDER BY p.id DESC
");
$products = $prodStmt->fetchAll(PDO::FETCH_ASSOC);

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Products</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
</head>

<body>
<div class="d-flex">
    <?php include 'sidebar.php'; ?>

    <main class="flex-grow-1 bg-light p-4">

        <h3 class="mb-3">Products</h3>

        <!-- Alerts -->
        <?php if (!empty($errors)): ?>
            <div class="alert alert-danger">
                <ul class="mb-0">
                    <?php foreach ($errors as $e): ?>
                        <li><?= htmlspecialchars($e) ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <?php if ($success): ?>
            <div class="alert alert-success"><?= $success ?></div>
        <?php endif; ?>

        <!-- Admin Add Product Form -->
        <?php if ($isAdminOrManager): ?>
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0">Add Product</h5>
                </div>
                <div class="card-body">

                    <form method="post">
                        <input type="hidden" name="add_product" value="1">

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Product Name</label>
                                <input type="text" name="name" class="form-control" required>
                            </div>

                            <div class="col-md-3 mb-3">
                                <label class="form-label">Price</label>
                                <input type="text" name="price" class="form-control" required>
                            </div>

                            <div class="col-md-3 mb-3">
                                <label class="form-label">Available Quantity</label>
                                <input type="number" name="available_qty" class="form-control" min="0" required>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Category</label>
                                <select name="category_id" class="form-select" required>
                                    <option value="">-- Select Category --</option>
                                    <?php foreach ($categories as $c): ?>
                                        <option value="<?= $c['id'] ?>"><?= htmlspecialchars($c['name']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="col-md-6 mb-3">
                                <label class="form-label">Subcategory</label>
                                <select name="subcategory_id" class="form-select" required>
                                    <option value="">-- Select Subcategory --</option>
                                    <?php foreach ($subcategories as $s): ?>
                                        <option value="<?= $s['id'] ?>">
                                            <?= htmlspecialchars($s['category_name'] . ' - ' . $s['name']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Image URL</label>
                            <input type="text" name="product_image_url" class="form-control">
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Description</label>
                            <textarea name="description" class="form-control" rows="3"></textarea>
                        </div>

                        <button type="submit" class="btn btn-primary">Save Product</button>
                    </form>

                </div>
            </div>
        <?php endif; ?>

        <!-- Product Table -->
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">All Products</h5>
            </div>
            <div class="card-body p-0">

                <div class="table-responsive">
                    <table class="table table-striped mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>ID</th>
                                <th>Name / Description</th>
                                <th>Category / Subcategory</th>
                                <th>Price</th>
                                <th>Quantity</th>
                                <th>Image</th>
                                <?php if ($isCustomer): ?>
                                    <th>Add to Cart</th>
                                <?php endif; ?>
                                <th>Created At</th>
                            </tr>
                        </thead>

                        <tbody>
                        <?php if (empty($products)): ?>
                            <tr>
                                <td colspan="8" class="text-center py-3">No products found.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($products as $p): ?>
                                <tr>
                                    <td><?= $p['id'] ?></td>

                                    <td>
                                        <strong><?= htmlspecialchars($p['name']) ?></strong><br>
                                        <small class="text-muted"><?= nl2br(htmlspecialchars($p['description'])) ?></small>
                                    </td>

                                    <td><?= htmlspecialchars($p['category_name']) ?> / <?= htmlspecialchars($p['subcategory_name']) ?></td>

                                    <td>$<?= number_format($p['price'], 2) ?></td>

                                    <td><?= $p['available_qty'] ?></td>

                                    <td>
                                        <?php if ($p['product_image_url']): ?>
                                            <img src="<?= htmlspecialchars($p['product_image_url']) ?>"
                                                 style="width:80px;height:auto;">
                                        <?php endif; ?>
                                    </td>

                                    <?php if ($isCustomer): ?>
                                        <td>
                                            <form method="post">
                                                <input type="hidden" name="product_id" value="<?= $p['id'] ?>">
                                                <input type="hidden" name="add_to_cart" value="1">
                                                <input type="number" name="qty" value="1" min="1" class="form-control mb-2" style="width:70px;">
                                                <button class="btn btn-success btn-sm">Add</button>
                                            </form>
                                        </td>
                                    <?php endif; ?>

                                    <td><?= $p['created_at'] ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                        </tbody>

                    </table>
                </div>

            </div>
        </div>

    </main>
</div>
</body>
</html>
