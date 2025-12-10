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

// Check for success message from redirect
if (isset($_SESSION['order_success'])) {
    $success = $_SESSION['order_success'];
    unset($_SESSION['order_success']);
}

// Handle Delete Order
if ($isAdminOrManager && isset($_GET['delete'])) {
    $deleteId = (int)$_GET['delete'];

    try {
        $pdo->beginTransaction();

        // Delete order details first (due to foreign key)
        $stmt = $pdo->prepare("DELETE FROM order_details WHERE order_id = ?");
        $stmt->execute([$deleteId]);

        // Then delete the order
        $stmt = $pdo->prepare("DELETE FROM orders WHERE id = ?");
        $stmt->execute([$deleteId]);

        $pdo->commit();

        $_SESSION['order_success'] = "Order deleted successfully.";
        header("Location: orders.php");
        exit;
    } catch (Exception $e) {
        $pdo->rollBack();
        $errors[] = "Failed to delete order: " . $e->getMessage();
    }
}

// Fetch all orders with user and payment information
$ordersStmt = $pdo->query("
    SELECT o.id, o.order_date,
           u.name AS user_name, u.email AS user_email,
           p.card_number, p.card_holder_name, p.amount AS total_amount
    FROM orders o
    JOIN users u ON o.user_id = u.id
    JOIN payments p ON o.payment_id = p.id
    ORDER BY o.id DESC
");
$orders = $ordersStmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch order details for each order
$orderItemsMap = [];
if (!empty($orders)) {
    $orderIds = array_column($orders, 'id');
    $placeholders = str_repeat('?,', count($orderIds) - 1) . '?';
    $stmt = $pdo->prepare("
        SELECT od.order_id, od.quantity, od.unit_price, od.status,
               p.name as product_name,
               d.deal_name
        FROM order_details od
        LEFT JOIN products p ON od.product_id = p.id
        LEFT JOIN deals d ON od.deal_id = d.id
        WHERE od.order_id IN ($placeholders)
    ");
    $stmt->execute($orderIds);
    $allOrderItems = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($allOrderItems as $item) {
        $orderItemsMap[$item['order_id']][] = $item;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Orders</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <style>
        .order-row:hover {
            background-color: #f8f9fa !important;
            transition: background-color 0.2s ease;
        }
        .order-row {
            cursor: pointer;
        }
    </style>
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

            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">All Orders</h5>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-striped mb-0">
                            <thead class="table-light">
                            <tr>
                                <th>Order ID</th>
                                <th>Customer</th>
                                <th>Order Date</th>
                                <th>Items</th>
                                <th>Total Amount</th>
                                <th>Payment Info</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                            </thead>
                            <tbody>
                            <?php if (empty($orders)): ?>
                                <tr>
                                    <td colspan="8" class="text-center py-3">No orders found.</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($orders as $o): ?>
                                    <tr class="order-row" data-order-id="<?= $o['id'] ?>" style="cursor: pointer;">
                                        <td><?= $o['id'] ?></td>
                                        <td>
                                            <strong><?= htmlspecialchars($o['user_name']) ?></strong><br>
                                            <small class="text-muted"><?= htmlspecialchars($o['user_email']) ?></small>
                                        </td>
                                        <td><?= htmlspecialchars(date('M d, Y h:i A', strtotime($o['order_date']))) ?></td>
                                        <td>
                                            <?php if (isset($orderItemsMap[$o['id']])): ?>
                                                <?php foreach ($orderItemsMap[$o['id']] as $item): ?>
                                                    <small>
                                                        <?php
                                                        // Show product name or deal name
                                                        $itemName = $item['product_name'] ?? $item['deal_name'] ?? 'Unknown Item';
                                                        echo htmlspecialchars($itemName);
                                                        ?>
                                                        (x<?= $item['quantity'] ?>)
                                                    </small><br>
                                                <?php endforeach; ?>
                                            <?php else: ?>
                                                <small class="text-muted">No items</small>
                                            <?php endif; ?>
                                        </td>
                                        <td><strong>$<?= number_format($o['total_amount'], 2) ?></strong></td>
                                        <td>
                                            <?php if ($o['card_holder_name']): ?>
                                                <small><?= htmlspecialchars($o['card_holder_name']) ?></small><br>
                                                <small class="text-muted"><?= htmlspecialchars($o['card_number']) ?></small>
                                            <?php else: ?>
                                                <small class="text-muted">N/A</small>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php
                                            // Get status from first order detail item
                                            $orderStatus = isset($orderItemsMap[$o['id']][0]['status']) ? $orderItemsMap[$o['id']][0]['status'] : 'pending';
                                            ?>
                                            <span class="badge bg-<?=
                                                $orderStatus == 'shipped' ? 'success' :
                                                ($orderStatus == 'cancelled' ? 'danger' :
                                                ($orderStatus == 'confirmed' ? 'info' : 'secondary'))
                                            ?>">
                                                <?= ucfirst($orderStatus) ?>
                                            </span>
                                        </td>
                                        <td onclick="event.stopPropagation();">
                                            <a href="orders.php?delete=<?= $o['id'] ?>"
                                               class="btn btn-sm btn-danger"
                                               onclick="return confirm('Are you sure you want to delete this order?')">Delete</a>
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

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Make order rows clickable to view details
    const orderRows = document.querySelectorAll('.order-row');
    orderRows.forEach(function(row) {
        row.addEventListener('click', function() {
            const orderId = this.getAttribute('data-order-id');
            window.location.href = 'order_details.php?id=' + orderId;
        });
    });
});
</script>
</body>
</html>
