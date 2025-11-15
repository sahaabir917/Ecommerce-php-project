<?php
// cart.php
require 'config.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$roleName = strtolower($_SESSION['role_name'] ?? '');
$isCustomer = ($roleName === 'customer');

if (!$isCustomer) {
    // 非 customer 也可以进来，但只给个提示
    $warning = "Only customers normally use the cart.";
}

// 确保 cart 表存在（防止没有在 products.php 创建过）
$pdo->exec("
    CREATE TABLE IF NOT EXISTS cart (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        product_id INT NOT NULL,
        quantity INT NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )
");

$errors = [];
$success = "";

// 处理 更新数量
if (isset($_POST['update_cart'])) {
    $cartId = (int)($_POST['cart_id'] ?? 0);
    $qty    = (int)($_POST['qty'] ?? 1);
    if ($qty < 1) $qty = 1;

    $stmt = $pdo->prepare("UPDATE cart SET quantity = ? WHERE id = ? AND user_id = ?");
    $stmt->execute([$qty, $cartId, $_SESSION['user_id']]);
    $success = "Cart updated.";
}

// 处理 删除商品
if (isset($_POST['remove_item'])) {
    $cartId = (int)($_POST['cart_id'] ?? 0);
    $stmt = $pdo->prepare("DELETE FROM cart WHERE id = ? AND user_id = ?");
    $stmt->execute([$cartId, $_SESSION['user_id']]);
    $success = "Item removed from cart.";
}

// 读取购物车
$stmt = $pdo->prepare("
    SELECT c.id AS cart_id, c.quantity, 
           p.id AS product_id, p.name, p.price, p.product_image_url
    FROM cart c
    JOIN products p ON c.product_id = p.id
    WHERE c.user_id = ?
");
$stmt->execute([$_SESSION['user_id']]);
$items = $stmt->fetchAll(PDO::FETCH_ASSOC);

$total = 0;
foreach ($items as $item) {
    $total += $item['price'] * $item['quantity'];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>My Cart</title>
    <link rel="stylesheet"
          href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
</head>
<body>
<div class="d-flex">
    <?php include 'sidebar.php'; ?>

    <main class="flex-grow-1 bg-light p-4">
        <h3 class="mb-3">My Cart</h3>

        <?php if (!empty($warning)): ?>
            <div class="alert alert-warning"><?= htmlspecialchars($warning) ?></div>
        <?php endif; ?>

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

        <?php if (empty($items)): ?>
            <div class="alert alert-info">
                Your cart is empty. <a href="products.php">Go to Products</a>.
            </div>
        <?php else: ?>
            <div class="card mb-3">
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table mb-0">
                            <thead class="table-light">
                            <tr>
                                <th>Product</th>
                                <th>Price</th>
                                <th>Qty</th>
                                <th>Subtotal</th>
                                <th>Actions</th>
                            </tr>
                            </thead>
                            <tbody>
                            <?php foreach ($items as $it): ?>
                                <?php $sub = $it['price'] * $it['quantity']; ?>
                                <tr>
                                    <td>
                                        <strong><?= htmlspecialchars($it['name']) ?></strong><br>
                                        <?php if ($it['product_image_url']): ?>
                                            <img src="<?= htmlspecialchars($it['product_image_url']) ?>"
                                                 style="width:60px;height:auto;">
                                        <?php endif; ?>
                                    </td>
                                    <td>$<?= number_format($it['price'], 2) ?></td>
                                    <td style="max-width:120px;">
                                        <form method="post" class="d-flex">
                                            <input type="hidden" name="cart_id" value="<?= $it['cart_id'] ?>">
                                            <input type="number" name="qty" value="<?= $it['quantity'] ?>"
                                                   min="1" class="form-control form-control-sm me-2">
                                            <button type="submit" name="update_cart"
                                                    class="btn btn-sm btn-primary">Update</button>
                                        </form>
                                    </td>
                                    <td>$<?= number_format($sub, 2) ?></td>
                                    <td>
                                        <form method="post">
                                            <input type="hidden" name="cart_id" value="<?= $it['cart_id'] ?>">
                                            <button type="submit" name="remove_item"
                                                    class="btn btn-sm btn-danger"
                                                    onclick="return confirm('Remove this item?');">
                                                Remove
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                            </tbody>
                            <tfoot class="table-light">
                            <tr>
                                <th colspan="3" class="text-end">Total:</th>
                                <th colspan="2">$<?= number_format($total, 2) ?></th>
                            </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>
            </div>

            <a href="checkout.php" class="btn btn-success">Proceed to Checkout</a>
            <a href="products.php" class="btn btn-outline-secondary ms-2">Continue Shopping</a>
        <?php endif; ?>
    </main>
</div>
</body>
</html>
