<?php
require 'config.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$roleName = $_SESSION['role_name'] ?? '';
$isAdminOrManager = in_array($roleName, ['Admin', 'Manager'], true);

if (!$isAdminOrManager) {
    die("Access denied. You must be an Admin or Manager.");
}

$errors = [];
$success = "";

// Get deal ID from query parameter
$dealId = (int)($_GET['deal_id'] ?? 0);

if ($dealId <= 0) {
    die("Invalid deal ID.");
}

// Fetch deal information
$stmt = $pdo->prepare("SELECT * FROM deals WHERE id = ?");
$stmt->execute([$dealId]);
$deal = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$deal) {
    die("Deal not found.");
}

// Check for success message from redirect
if (isset($_SESSION['product_success'])) {
    $success = $_SESSION['product_success'];
    unset($_SESSION['product_success']);
}

// Handle Remove Product from Deal
if (isset($_GET['remove_product'])) {
    $dealProductId = (int)$_GET['remove_product'];

    try {
        $pdo->beginTransaction();

        // Get the quantity being freed up before deleting
        $stmt = $pdo->prepare("SELECT product_id, available_quantity FROM deal_products WHERE id = ?");
        $stmt->execute([$dealProductId]);
        $dealProduct = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($dealProduct) {
            // Return quantity back to the product's available_qty
            $stmt = $pdo->prepare("UPDATE products SET available_qty = available_qty + ? WHERE id = ?");
            $stmt->execute([$dealProduct['available_quantity'], $dealProduct['product_id']]);

            // Delete the deal_product entry
            $stmt = $pdo->prepare("DELETE FROM deal_products WHERE id = ?");
            $stmt->execute([$dealProductId]);
        }

        $pdo->commit();

        $_SESSION['product_success'] = "Product removed from deal successfully.";
        header("Location: deal_manage_products.php?deal_id=" . $dealId);
        exit;
    } catch (Exception $e) {
        $pdo->rollBack();
        $errors[] = "Failed to remove product: " . $e->getMessage();
    }
}

// Handle Add Product to Deal
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_product'])) {
    $productId = (int)($_POST['product_id'] ?? 0);
    $availableQty = (int)($_POST['available_quantity'] ?? 0);
    $offeredPrice = (float)($_POST['offered_price'] ?? 0);

    // Validation
    if ($productId <= 0) {
        $errors[] = "Please select a product.";
    }
    if ($availableQty <= 0) {
        $errors[] = "Available quantity must be greater than 0.";
    }
    if ($offeredPrice <= 0) {
        $errors[] = "Offered price must be greater than 0.";
    }

    // Check if product already exists in this deal
    if (empty($errors)) {
        $stmt = $pdo->prepare("SELECT id FROM deal_products WHERE deal_id = ? AND product_id = ?");
        $stmt->execute([$dealId, $productId]);
        if ($stmt->fetch()) {
            $errors[] = "This product is already in this deal.";
        }
    }

    // Check if product has enough stock
    if (empty($errors)) {
        $stmt = $pdo->prepare("SELECT available_qty FROM products WHERE id = ?");
        $stmt->execute([$productId]);
        $product = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$product) {
            $errors[] = "Product not found.";
        } elseif ($product['available_qty'] < $availableQty) {
            $errors[] = "Not enough stock available. Available: " . $product['available_qty'];
        }
    }

    if (empty($errors)) {
        try {
            // Insert into deal_products (trigger will automatically deduct from products table)
            $stmt = $pdo->prepare("INSERT INTO deal_products (deal_id, product_id, available_quantity, offered_price) VALUES (?, ?, ?, ?)");
            if ($stmt->execute([$dealId, $productId, $availableQty, $offeredPrice])) {
                $_SESSION['product_success'] = "Product added to deal successfully.";
                header("Location: deal_manage_products.php?deal_id=" . $dealId);
                exit;
            } else {
                $errors[] = "Failed to add product to deal.";
            }
        } catch (Exception $e) {
            $errors[] = "Database error: " . $e->getMessage();
        }
    }
}

// Fetch products already in this deal
$dealProductsStmt = $pdo->prepare("
    SELECT dp.id AS deal_product_id,
           dp.available_quantity,
           dp.offered_price,
           p.id AS product_id,
           p.name AS product_name,
           p.price AS original_price,
           p.product_image_url
    FROM deal_products dp
    INNER JOIN products p ON dp.product_id = p.id
    WHERE dp.deal_id = ?
    ORDER BY dp.id DESC
");
$dealProductsStmt->execute([$dealId]);
$dealProducts = $dealProductsStmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch all products for the dropdown (excluding ones already in this deal)
$productsStmt = $pdo->prepare("
    SELECT p.id, p.name, p.price, p.available_qty
    FROM products p
    WHERE p.id NOT IN (
        SELECT product_id FROM deal_products WHERE deal_id = ?
    )
    ORDER BY p.name ASC
");
$productsStmt->execute([$dealId]);
$availableProducts = $productsStmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Manage Products - <?= htmlspecialchars($deal['deal_name']) ?></title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <style>
        .product-image {
            width: 60px;
            height: 60px;
            object-fit: cover;
            border-radius: 8px;
        }
    </style>
</head>
<body>
<div class="d-flex">
    <?php include 'sidebar.php'; ?>

    <main class="flex-grow-1 bg-light p-4">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h3>Manage Products for: <?= htmlspecialchars($deal['deal_name']) ?></h3>
            <a href="deals_admin.php" class="btn btn-secondary">← Back to Deals</a>
        </div>

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
            <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
        <?php endif; ?>

        <!-- Add Product Form -->
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="mb-0">Add Product to Deal</h5>
            </div>
            <div class="card-body">
                <form method="post">
                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Select Product</label>
                            <select name="product_id" id="product_id" class="form-select" required>
                                <option value="">-- Choose Product --</option>
                                <?php foreach ($availableProducts as $p): ?>
                                    <option value="<?= $p['id'] ?>"
                                            data-price="<?= $p['price'] ?>"
                                            data-stock="<?= $p['available_qty'] ?>">
                                        <?= htmlspecialchars($p['name']) ?>
                                        (Stock: <?= $p['available_qty'] ?>, Price: $<?= number_format($p['price'], 2) ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="col-md-3 mb-3">
                            <label class="form-label">Quantity for Deal</label>
                            <input type="number" name="available_quantity" class="form-control"
                                   min="1" value="1" required>
                            <small class="text-muted">How many units to allocate to this deal</small>
                        </div>

                        <div class="col-md-3 mb-3">
                            <label class="form-label">Offered Price ($)</label>
                            <input type="number" name="offered_price" id="offered_price"
                                   class="form-control" step="0.01" min="0.01" required>
                            <small class="text-muted">Discounted price for this product in deal</small>
                        </div>

                        <div class="col-md-2 mb-3">
                            <label class="form-label">&nbsp;</label>
                            <button type="submit" name="add_product" class="btn btn-primary w-100">Add Product</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <!-- Current Products in Deal -->
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">Products in This Deal (<?= count($dealProducts) ?>)</h5>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-striped mb-0">
                        <thead class="table-light">
                        <tr>
                            <th>Image</th>
                            <th>Product Name</th>
                            <th>Original Price</th>
                            <th>Offered Price</th>
                            <th>Discount</th>
                            <th>Quantity Allocated</th>
                            <th>Actions</th>
                        </tr>
                        </thead>
                        <tbody>
                        <?php if (empty($dealProducts)): ?>
                            <tr>
                                <td colspan="7" class="text-center py-3">
                                    No products added to this deal yet.
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($dealProducts as $dp):
                                $discount = $dp['original_price'] - $dp['offered_price'];
                                $discountPercent = ($dp['original_price'] > 0)
                                    ? ($discount / $dp['original_price'] * 100)
                                    : 0;
                            ?>
                                <tr>
                                    <td>
                                        <?php if ($dp['product_image_url']): ?>
                                            <img src="<?= htmlspecialchars($dp['product_image_url']) ?>"
                                                 class="product-image" alt="Product">
                                        <?php else: ?>
                                            <div class="product-image bg-secondary d-flex align-items-center justify-content-center text-white">
                                                No Img
                                            </div>
                                        <?php endif; ?>
                                    </td>
                                    <td><?= htmlspecialchars($dp['product_name']) ?></td>
                                    <td>$<?= number_format($dp['original_price'], 2) ?></td>
                                    <td>
                                        <strong class="text-success">$<?= number_format($dp['offered_price'], 2) ?></strong>
                                    </td>
                                    <td>
                                        <?php if ($discount > 0): ?>
                                            <span class="badge bg-danger">
                                                -$<?= number_format($discount, 2) ?> (<?= number_format($discountPercent, 1) ?>%)
                                            </span>
                                        <?php else: ?>
                                            <span class="text-muted">No discount</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <span class="badge bg-info"><?= $dp['available_quantity'] ?> units</span>
                                    </td>
                                    <td>
                                        <a href="deal_manage_products.php?deal_id=<?= $dealId ?>&remove_product=<?= $dp['deal_product_id'] ?>"
                                           class="btn btn-sm btn-danger"
                                           onclick="return confirm('Are you sure you want to remove this product from the deal? The quantity will be returned to the product stock.')">
                                            Remove
                                        </a>
                                    </td>
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

<script>
// Auto-fill offered price with original price when product is selected
document.getElementById('product_id').addEventListener('change', function() {
    const selectedOption = this.options[this.selectedIndex];
    const originalPrice = selectedOption.getAttribute('data-price');

    if (originalPrice) {
        document.getElementById('offered_price').value = originalPrice;
    }
});
</script>
</body>
</html>
