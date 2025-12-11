<?php
require 'config.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$roleName = $_SESSION['role_name'] ?? '';
$isAdminOrManager = in_array($roleName, ['Admin', 'Manager'], true);

$errors = [];
$success = "";

// Fetch all orders + user + payment info (Admin/Manager only)
$orders = [];
if ($isAdminOrManager) {
    $stmt = $pdo->query("
        SELECT 
            o.id,
            o.order_date,
            u.name  AS user_name,
            u.email AS user_email,
            p.amount,
            p.card_number,
            p.card_holder_name,
            p.created_at AS payment_time
        FROM orders o
        JOIN users    u ON o.user_id    = u.id
        JOIN payments p ON o.payment_id = p.id
        ORDER BY o.id DESC
    ");
    $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function h($s) {
    return htmlspecialchars($s ?? '', ENT_QUOTES, 'UTF-8');
}
function price($n) {
    return number_format((float)$n, 2);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Orders</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
</head>
<body>
<div class="d-flex">
    <?php include 'sidebar.php'; ?>

    <main class="flex-grow-1 bg-light p-4">
        <h3 class="mb-3">Order Management</h3>

        <?php if (!$isAdminOrManager): ?>
            <div class="alert alert-danger">
                You have no access to this URL.
            </div>
        <?php else: ?>

            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">All Orders</h5>
                    <small class="text-muted">
                        Total: <?= count($orders) ?> orders
                    </small>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-striped mb-0">
                            <thead class="table-light">
                            <tr>
                                <th>ID</th>
                                <th>User</th>
                                <th>Payment Amount</th>
                                <th>Card</th>
                                <th>Payment Time</th>
                                <th>Order Date</th>
                                <th>Action</th>
                            </tr>
                            </thead>
                            <tbody>
                            <?php if (empty($orders)): ?>
                                <tr>
                                    <td colspan="7" class="text-center py-3">No orders found.</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($orders as $o): ?>
                                    <?php
                                    $maskedCard = h(substr($o['card_number'], 0, 4)) . '****' . h(substr($o['card_number'], -4));
                                    ?>
                                    <tr>
                                        <td><?= (int)$o['id'] ?></td>
                                        <td>
                                            <?= h($o['user_name']) ?><br>
                                            <small class="text-muted"><?= h($o['user_email']) ?></small>
                                        </td>
                                        <td>$ <?= price($o['amount']) ?></td>
                                        <td>
                                            <small>
                                                <?= $maskedCard ?><br>
                                                <?= h($o['card_holder_name']) ?>
                                            </small>
                                        </td>
                                        <td><small class="text-muted"><?= h($o['payment_time']) ?></small></td>
                                        <td><small class="text-muted"><?= h($o['order_date']) ?></small></td>
                                        <td>
                                            <a href="order_details.php?id=<?= (int)$o['id'] ?>"
                                               class="btn btn-sm btn-primary">
                                                View Details
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

        <?php endif; ?>
    </main>
</div>
</body>
</html>
