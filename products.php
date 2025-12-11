<?php
require 'config.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$roleName = $_SESSION['role_name'] ?? '';
$isAdminOrManager = in_array($roleName, ['Admin', 'Manager'], true);

$errors = [];
$success = $_SESSION['success_message'] ?? "";
unset($_SESSION['success_message']);
if (!empty($_SESSION['error_message'])) {
    $errors[] = $_SESSION['error_message'];
    unset($_SESSION['error_message']);
}
$editProduct = null;

// Fetch categories & subcategories
$catStmt = $pdo->query("SELECT id, name FROM categories ORDER BY name ASC");
$categories = $catStmt->fetchAll(PDO::FETCH_ASSOC);

$subStmt = $pdo->query("
    SELECT s.id, s.name, s.category_id, c.name AS category_name
    FROM subcategories s
    JOIN categories c ON s.category_id = c.id
    ORDER BY c.name, s.name
");
$subcategories = $subStmt->fetchAll(PDO::FETCH_ASSOC);

// Handle Delete
if ($isAdminOrManager && isset($_GET['delete'])) {
    $deleteId = (int)$_GET['delete'];
    try {
        $stmt = $pdo->prepare("DELETE FROM products WHERE id = ?");
        if ($stmt->execute([$deleteId])) {
            $_SESSION['success_message'] = "Product deleted successfully.";
        }
    } catch (PDOException $e) {
        $_SESSION['error_message'] = "Cannot delete product. It may be in use by orders or carts.";
    }
    header("Location: products.php");
    exit;
}

// Handle Edit - Load product data
if ($isAdminOrManager && isset($_GET['edit'])) {
    $editId = (int)$_GET['edit'];
    $stmt = $pdo->prepare("SELECT * FROM products WHERE id = ?");
    $stmt->execute([$editId]);
    $editProduct = $stmt->fetch(PDO::FETCH_ASSOC);
}

// Handle Add/Update Product
if ($isAdminOrManager && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $name       = trim($_POST['name'] ?? '');
    $price      = trim($_POST['price'] ?? '');
    $qty        = trim($_POST['available_qty'] ?? '');
    $category_id    = (int)($_POST['category_id'] ?? 0);
    $subcategory_id = (int)($_POST['subcategory_id'] ?? 0);
    $image_url  = trim($_POST['product_image_url'] ?? '');
    $desc       = trim($_POST['description'] ?? '');
    $productId  = (int)($_POST['product_id'] ?? 0);

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

    if (empty($errors)) {
        if ($productId > 0) {
            // Update existing product
            $stmt = $pdo->prepare("
                UPDATE products
                SET name = ?, price = ?, available_qty = ?, category_id = ?,
                    subcategory_id = ?, product_image_url = ?, description = ?
                WHERE id = ?
            ");
            $ok = $stmt->execute([
                $name,
                $price,
                (int)$qty,
                $category_id,
                $subcategory_id,
                $image_url,
                $desc,
                $productId
            ]);

            if ($ok) {
                $_SESSION['success_message'] = "Product updated successfully.";
                header("Location: products.php");
                exit;
            } else {
                $errors[] = "Failed to update product.";
            }
        } else {
            // Insert new product
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
                $_SESSION['success_message'] = "Product added successfully.";
                header("Location: products.php");
                exit;
            } else {
                $errors[] = "Failed to add product.";
            }
        }
    }
}

// Fetch products
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
        <h3 class="mb-3">Product Management</h3>

        <?php if (!$isAdminOrManager): ?>
            <div class="alert alert-danger">
                You have no access to this URL.
            </div>
        <?php else: ?>
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0"><?= $editProduct ? 'Edit Product' : 'Add Product' ?></h5>
                </div>
                <div class="card-body">
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

                    <form method="post">
                        <input type="hidden" name="product_id" value="<?= $editProduct['id'] ?? 0 ?>">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Product Name</label>
                                <input type="text" name="name" class="form-control"
                                       value="<?= htmlspecialchars($editProduct['name'] ?? $_POST['name'] ?? '') ?>" required>
                            </div>
                            <div class="col-md-3 mb-3">
                                <label class="form-label">Price</label>
                                <input type="text" name="price" class="form-control"
                                       value="<?= htmlspecialchars($editProduct['price'] ?? $_POST['price'] ?? '') ?>" required>
                            </div>
                            <div class="col-md-3 mb-3">
                                <label class="form-label">Available Quantity</label>
                                <input type="number" name="available_qty" min="0" class="form-control"
                                       value="<?= htmlspecialchars($editProduct['available_qty'] ?? $_POST['available_qty'] ?? '0') ?>" required>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Category</label>
                                <select name="category_id" class="form-select" required>
                                    <option value="">-- Select Category --</option>
                                    <?php foreach ($categories as $c): ?>
                                        <option value="<?= $c['id'] ?>"
                                            <?= (($editProduct['category_id'] ?? $_POST['category_id'] ?? '') == $c['id']) ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($c['name']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="col-md-6 mb-3">
                                <label class="form-label">Subcategory</label>
                                <select name="subcategory_id" class="form-select" required>
                                    <option value="">-- Select Subcategory --</option>
                                    <?php foreach ($subcategories as $s): ?>
                                        <option value="<?= $s['id'] ?>"
                                            <?= (($editProduct['subcategory_id'] ?? $_POST['subcategory_id'] ?? '') == $s['id']) ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($s['category_name'] . ' - ' . $s['name']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Product Image URL</label>
                            <input type="text" name="product_image_url" class="form-control"
                                   value="<?= htmlspecialchars($editProduct['product_image_url'] ?? $_POST['product_image_url'] ?? '') ?>">
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Product Description</label>
                            <textarea name="description" class="form-control" rows="3"><?= htmlspecialchars($editProduct['description'] ?? $_POST['description'] ?? '') ?></textarea>
                        </div>

                        <button type="submit" class="btn btn-primary">
                            <?= $editProduct ? 'Update Product' : 'Save Product' ?>
                        </button>
                        <?php if ($editProduct): ?>
                            <a href="products.php" class="btn btn-secondary">Cancel</a>
                        <?php endif; ?>
                    </form>
                </div>
            </div>

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
                                <th>Available Qty</th>
                                <th>Image URL</th>
                                <th>Created At</th>
                                <th>Actions</th>
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
                                            <small class="text-muted">
                                                <?= nl2br(htmlspecialchars($p['description'] ?? '')) ?>
                                            </small>
                                        </td>
                                        <td>
                                            <?= htmlspecialchars($p['category_name']) ?>
                                            /
                                            <?= htmlspecialchars($p['subcategory_name']) ?>
                                        </td>
                                        <td><?= number_format($p['price'], 2) ?></td>
                                        <td><?= $p['available_qty'] ?></td>
                                        <td style="max-width: 200px; word-wrap: break-word;">
                                            <?= htmlspecialchars($p['product_image_url']) ?>
                                        </td>
                                        <td><?= htmlspecialchars($p['created_at']) ?></td>
                                        <td>
                                            <a href="products.php?edit=<?= $p['id'] ?>" class="btn btn-sm btn-warning">Edit</a>
                                            <a href="products.php?delete=<?= $p['id'] ?>"
                                               class="btn btn-sm btn-danger"
                                               onclick="return confirm('Are you sure you want to delete this product?')">Delete</a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </main>
</div>
</body>
</html>
