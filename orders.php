<?php
// orders.php
require 'config.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$roleName = strtolower($_SESSION['role_name'] ?? '');
$isAdminOrManager = in_array($roleName, ['admin', 'manager']);
$isCustomer = ($roleName === 'customer');

// 如果 orders 表不存在，简单创建（防止访问时报错）
$pdo->exec("
    CREATE TABLE IF NOT EXISTS orders (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        total_amount DECIMAL(10,2) NOT NULL,
        shipping_name VARCHAR(255),
        shipping_address VARCHAR(255),
        shipping_phone VARCHAR(50),
        payment_method VARCHAR(20),
        payment_status VARCHAR(20),
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )
");

// 读取订单
if ($isAdminOrManager) {
    // 管理员：看所有用户的订单
    $stmt = $pdo->query("
        SELECT o.*, u.name AS user_name, u.email AS user_email
        FROM orders o
        JOIN users u ON o.user_id = u.id
        ORDER BY o.id DESC
    ");
} else {
    // 普通用户：只看自己的订单
    $stmt = $pdo->prepare("
        SELECT o.*, u.name AS user_name, u.email AS user_email
        FROM orders o
        JOIN users u ON o.user_id = u.id
        WHERE o.user_id = ?
        ORDER BY o.id DESC
    ");
    $stmt->execute([$_SESSION['user_id']]);
}

$orders = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Orders</title>
    <link rel="stylesheet"
          href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
</head>
<body>
<div class="d-flex">
    <?php include 'sidebar.php'; ?>

    <main class="flex-grow-1 bg-light p-4">
        <h3 class="mb-3">
            <?= $isAdminOrManager ? 'All Orders' : 'My Orders' ?>
        </h3>

        <?php if (empty($orders)): ?>
            <div class="alert alert-info">
                No orders found.
            </div>
        <?php else: ?>
            <div class="card">
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table mb-0">
                            <thead class="table-light">
                            <tr>
                                <th>ID</th>
                                <?php if ($isAdminOrManager): ?>
                                    <th>User</th>
                                <?php endif; ?>
                                <th>Total</th>
                                <th>Payment</th>
                                <th>Created At</th>
                                <th>Details</th>
                            </tr>
                            </thead>
                            <tbody>
                            <?php foreach ($orders as $o): ?>
                                <tr>
                                    <td><?= $o['id'] ?></td>
                                    <?php if ($isAdminOrManager): ?>
                                        <td>
                                            <?= htmlspecialchars($o['user_name']) ?><br>
                                            <small class="text-muted"><?= htmlspecialchars($o['user_email']) ?></small>
                                        </td>
                                    <?php endif; ?>
                                    <td>$<?= number_format($o['total_amount'], 2) ?></td>
                                    <td>
                                        <?= htmlspecialchars($o['payment_status']) ?><br>
                                        <small><?= htmlspecialchars($o['payment_method']) ?></small>
                                    </td>
                                    <td><?= htmlspecialchars($o['created_at']) ?></td>
                                    <td>
                                        <a href="order_success.php?order_id=<?= $o['id'] ?>"
                                           class="btn btn-sm btn-outline-primary">
                                            View
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
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
