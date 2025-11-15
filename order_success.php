<?php
// order_success.php
require 'config.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$orderId = (int)($_GET['order_id'] ?? 0);

$order = null;
$items = [];

if ($orderId > 0) {
    // 读取订单（只允许该用户或 admin 查看）
    $roleName = strtolower($_SESSION['role_name'] ?? '');
    $isAdminOrManager = in_array($roleName, ['admin', 'manager']);

    if ($isAdminOrManager) {
        $orderStmt = $pdo->prepare("SELECT * FROM orders WHERE id = ?");
        $orderStmt->execute([$orderId]);
    } else {
        $orderStmt = $pdo->prepare("SELECT * FROM orders WHERE id = ? AND user_id = ?");
        $orderStmt->execute([$orderId, $_SESSION['user_id']]);
    }

    $order = $orderStmt->fetch(PDO::FETCH_ASSOC);

    if ($order) {
        $itemsStmt = $pdo->prepare("
            SELECT oi.*, p.name 
            FROM order_items oi
            JOIN products p ON oi.product_id = p.id
            WHERE oi.order_id = ?
        ");
        $itemsStmt->execute([$orderId]);
        $items = $itemsStmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Order Success</title>
    <link rel="stylesheet"
          href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
</head>
<body>
<div class="d-flex">
    <?php include 'sidebar.php'; ?>

    <main class="flex-grow-1 bg-light p-4">
        <h3 class="mb-3">Order Success</h3>

        <?php if (!$order): ?>
            <div class="alert alert-danger">
                Order not found.
            </div>
        <?php else: ?>
            <div class="alert alert-success">
                Thank you! Your order has been placed successfully.
            </div>

            <h5>Order #<?= $order['id'] ?></h5>
            <p>
                <strong>Total:</strong> $<?= number_format($order['total_amount'], 2) ?><br>
                <strong>Payment Status:</strong> <?= htmlspecialchars($order['payment_status']) ?><br>
                <strong>Created At:</strong> <?= htmlspecialchars($order['created_at']) ?><br>
            </p>

            <h6>Items:</h6>
            <ul class="list-group mb-3">
                <?php foreach ($items as $it): ?>
                    <li class="list-group-item d-flex justify-content-between">
                        <span>
                            <?= htmlspecialchars($it['name']) ?> × <?= $it['quantity'] ?>
                        </span>
                        <span>$<?= number_format($it['subtotal'], 2) ?></span>
                    </li>
                <?php endforeach; ?>
            </ul>

            <a href="orders.php" class="btn btn-primary">View My Orders</a>
            <a href="products.php" class="btn btn-outline-secondary ms-2">Continue Shopping</a>
        <?php endif; ?>
    </main>
</div>
</body>
</html>
