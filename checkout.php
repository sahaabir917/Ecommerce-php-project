<?php
// checkout.php
require 'config.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$roleName = strtolower($_SESSION['role_name'] ?? '');
$isCustomer = ($roleName === 'customer');

if (!$isCustomer) {
    die("Only customers can checkout.");
}

// 读取购物车
$stmt = $pdo->prepare("
    SELECT c.id AS cart_id, c.quantity,
           p.id AS product_id, p.name, p.price
    FROM cart c
    JOIN products p ON c.product_id = p.id
    WHERE c.user_id = ?
");
$stmt->execute([$_SESSION['user_id']]);
$items = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (empty($items)) {
    header("Location: cart.php");
    exit;
}

$total = 0;
foreach ($items as $it) {
    $total += $it['price'] * $it['quantity'];
}

// 默认使用 users 表的信息
$userStmt = $pdo->prepare("SELECT name, email, phone, address FROM users WHERE id = ?");
$userStmt->execute([$_SESSION['user_id']]);
$user = $userStmt->fetch(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Checkout</title>
    <link rel="stylesheet"
          href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
</head>
<body>
<div class="d-flex">
    <?php include 'sidebar.php'; ?>

    <main class="flex-grow-1 bg-light p-4">
        <h3 class="mb-3">Checkout</h3>

        <div class="row">
            <div class="col-md-6">
                <div class="card mb-3">
                    <div class="card-header">
                        <h5 class="mb-0">Shipping & Payment Info</h5>
                    </div>
                    <div class="card-body">
                        <form method="post" action="place_order.php">
                            <div class="mb-3">
                                <label class="form-label">Full Name</label>
                                <input type="text" name="shipping_name" class="form-control"
                                       value="<?= htmlspecialchars($user['name'] ?? '') ?>" required>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Shipping Address</label>
                                <input type="text" name="shipping_address" class="form-control"
                                       value="<?= htmlspecialchars($user['address'] ?? '') ?>" required>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Phone</label>
                                <input type="text" name="shipping_phone" class="form-control"
                                       value="<?= htmlspecialchars($user['phone'] ?? '') ?>" required>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Payment Method</label>
                                <select name="payment_method" class="form-select">
                                    <option value="cod">Cash on Delivery (模拟)</option>
                                    <option value="card">Credit Card (模拟)</option>
                                </select>
                            </div>

                            <button type="submit" class="btn btn-success">Place Order</button>
                            <a href="cart.php" class="btn btn-outline-secondary ms-2">Back to Cart</a>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Order Summary -->
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">Order Summary</h5>
                    </div>
                    <div class="card-body">
                        <ul class="list-group mb-3">
                            <?php foreach ($items as $it): ?>
                                <?php $sub = $it['price'] * $it['quantity']; ?>
                                <li class="list-group-item d-flex justify-content-between">
                                    <div>
                                        <?= htmlspecialchars($it['name']) ?> × <?= $it['quantity'] ?>
                                    </div>
                                    <span>$<?= number_format($sub, 2) ?></span>
                                </li>
                            <?php endforeach; ?>
                            <li class="list-group-item d-flex justify-content-between">
                                <strong>Total</strong>
                                <strong>$<?= number_format($total, 2) ?></strong>
                            </li>
                        </ul>
                        <p class="text-muted mb-0">
                            This is a simulated payment. No real money will be charged.
                        </p>
                    </div>
                </div>
            </div>
        </div>

    </main>
</div>
</body>
</html>
