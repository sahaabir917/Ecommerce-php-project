<?php
// order_details.php
require 'config.php';

function h($s){ return htmlspecialchars($s ?? '', ENT_QUOTES, 'UTF-8'); }
function price($n){ return number_format((float)$n, 2); }

$orderId = (int)($_GET['id'] ?? 0);
if ($orderId <= 0) die("Invalid order id");

// Order + payment + user (WHERE-style joins)
$stmt = $pdo->prepare("
    SELECT o.id AS order_id, o.order_date,
           u.name AS user_name, u.email AS user_email,
           p.card_number, p.amount, p.card_holder_name, p.created_at AS payment_date
    FROM orders o, users u, payments p
    WHERE o.user_id = u.id
      AND o.payment_id = p.id
      AND o.id = ?
    LIMIT 1
");
$stmt->execute([$orderId]);
$order = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$order) die("Order not found");

// Order items: either product or deal
$itemsStmt = $pdo->prepare("
    SELECT od.id, od.product_id, od.deal_id, od.unit_price, od.quantity, od.status,
           pr.name AS product_name,
           d.deal_name
    FROM order_details od
    LEFT JOIN products pr ON od.product_id = pr.id
    LEFT JOIN deals d ON od.deal_id = d.id
    WHERE od.order_id = ?
");
$itemsStmt->execute([$orderId]);
$items = $itemsStmt->fetchAll(PDO::FETCH_ASSOC);

?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Order #<?= $orderId ?></title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
<div class="container py-4">
  <div class="d-flex justify-content-between align-items-center mb-3">
    <h3>Order #<?= (int)$orderId ?></h3>
    <a href="orders.php" class="btn btn-outline-secondary btn-sm">← Back to Orders</a>
  </div>

  <div class="row mb-4">
    <div class="col-md-6">
      <div class="card mb-3">
        <div class="card-header"><strong>Order Info</strong></div>
        <div class="card-body">
          <p><strong>Customer:</strong> <?= h($order['user_name']) ?> (<?= h($order['user_email']) ?>)</p>
          <p><strong>Date:</strong> <?= h($order['order_date']) ?></p>
        </div>
      </div>
    </div>
    <div class="col-md-6">
      <div class="card mb-3">
        <div class="card-header"><strong>Payment</strong></div>
        <div class="card-body">
          <p><strong>Card Holder:</strong> <?= h($order['card_holder_name']) ?></p>
          <p><strong>Card Number:</strong> <?= h($order['card_number']) ?></p>
          <p><strong>Amount:</strong> $ <?= price($order['amount']) ?></p>
          <p><strong>Payment Date:</strong> <?= h($order['payment_date']) ?></p>
        </div>
      </div>
    </div>
  </div>

  <div class="card">
    <div class="card-header"><strong>Items</strong></div>
    <div class="card-body p-0">
      <div class="table-responsive">
        <table class="table table-striped mb-0 align-middle">
          <thead class="table-light">
            <tr>
              <th>Item</th>
              <th>Type</th>
              <th>Unit Price</th>
              <th>Quantity</th>
              <th>Status</th>
              <th>Subtotal</th>
            </tr>
          </thead>
          <tbody>
          <?php $sum = 0.0;
          if (empty($items)): ?>
            <tr><td colspan="6" class="text-center py-3">No items.</td></tr>
          <?php else:
            foreach ($items as $it):
              $name = $it['product_id'] ? $it['product_name'] : $it['deal_name'];
              $type = $it['product_id'] ? 'Product' : 'Deal';
              $sub = $it['unit_price'] * $it['quantity'];
              $sum += $sub;
          ?>
            <tr>
              <td><?= h($name) ?></td>
              <td><?= h($type) ?></td>
              <td>$ <?= price($it['unit_price']) ?></td>
              <td><?= (int)$it['quantity'] ?></td>
              <td><?= h($it['status']) ?></td>
              <td>$ <?= price($sub) ?></td>
            </tr>
          <?php endforeach; endif; ?>
          </tbody>
        </table>
      </div>
    </div>
    <div class="card-footer text-end fw-bold">
      Total: $ <?= price($sum) ?>
    </div>
  </div>
</div>
</body>
</html>
