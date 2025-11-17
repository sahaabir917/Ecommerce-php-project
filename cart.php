<?php
// cart.php
require 'config.php';

function h($s){ return htmlspecialchars($s ?? '', ENT_QUOTES, 'UTF-8'); }
function price($n){ return number_format((float)$n, 2); }

if (!isset($_SESSION['cart_products'])) $_SESSION['cart_products'] = [];
if (!isset($_SESSION['cart_deals']))    $_SESSION['cart_deals']    = [];

$errors = [];

// Handle cart actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'add_product') {
        $pid = (int)($_POST['product_id'] ?? 0);
        $qty = (int)($_POST['quantity'] ?? 1);
        if ($pid > 0 && $qty > 0) {
            if (!isset($_SESSION['cart_products'][$pid])) {
                $_SESSION['cart_products'][$pid] = 0;
            }
            $_SESSION['cart_products'][$pid] += $qty;
        }
        header("Location: cart.php");
        exit;
    }

    if ($action === 'add_deal') {
        $did = (int)($_POST['deal_id'] ?? 0);
        $qty = (int)($_POST['quantity'] ?? 1);
        if ($did > 0 && $qty > 0) {
            if (!isset($_SESSION['cart_deals'][$did])) {
                $_SESSION['cart_deals'][$did] = 0;
            }
            $_SESSION['cart_deals'][$did] += $qty;
        }
        header("Location: cart.php");
        exit;
    }

    if ($action === 'update') {
        if (!empty($_POST['product_quantities'])) {
            foreach ($_POST['product_quantities'] as $pid => $q) {
                $pid = (int)$pid; $q = (int)$q;
                if ($pid <= 0) continue;
                if ($q <= 0) unset($_SESSION['cart_products'][$pid]);
                else $_SESSION['cart_products'][$pid] = $q;
            }
        }
        if (!empty($_POST['deal_quantities'])) {
            foreach ($_POST['deal_quantities'] as $did => $q) {
                $did = (int)$did; $q = (int)$q;
                if ($did <= 0) continue;
                if ($q <= 0) unset($_SESSION['cart_deals'][$did]);
                else $_SESSION['cart_deals'][$did] = $q;
            }
        }
        header("Location: cart.php");
        exit;
    }

    if ($action === 'clear') {
        $_SESSION['cart_products'] = [];
        $_SESSION['cart_deals'] = [];
        header("Location: cart.php");
        exit;
    }
}

$cartProducts = $_SESSION['cart_products'];
$cartDeals    = $_SESSION['cart_deals'];

// Fetch product info
$productRows = [];
$productsById = [];
if (!empty($cartProducts)) {
    $ids = array_keys($cartProducts);
    $placeholders = implode(',', array_fill(0, count($ids), '?'));
    $stmt = $pdo->prepare("SELECT * FROM products WHERE id IN ($placeholders)");
    $stmt->execute($ids);
    $productRows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    foreach ($productRows as $p) {
        $productsById[$p['id']] = $p;
    }
}

// Fetch deal info + compute total price
$dealRows = [];
$dealsById = [];
$dealTotals = []; // deal_id => total price per 1 deal

if (!empty($cartDeals)) {
    $ids = array_keys($cartDeals);
    $placeholders = implode(',', array_fill(0, count($ids), '?'));
    $dstmt = $pdo->prepare("SELECT * FROM deals WHERE id IN ($placeholders)");
    $dstmt->execute($ids);
    $dealRows = $dstmt->fetchAll(PDO::FETCH_ASSOC);
    foreach ($dealRows as $d) {
        $dealsById[$d['id']] = $d;
    }
    // compute deal totals using where-style join
    foreach ($cartDeals as $dealId => $_q) {
        $stmt = $pdo->prepare("
            SELECT dp.offered_price
            FROM deal_products dp
            WHERE dp.deal_id = ?
        ");
        $stmt->execute([$dealId]);
        $sum = 0.0;
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $sum += $row['offered_price'];
        }
        $dealTotals[$dealId] = $sum;
    }
}

// total amount
$total = 0.0;
foreach ($cartProducts as $pid => $qty) {
    if (isset($productsById[$pid])) {
        $total += $productsById[$pid]['price'] * $qty;
    }
}
foreach ($cartDeals as $did => $qty) {
    if (isset($dealTotals[$did])) {
        $total += $dealTotals[$did] * $qty;
    }
}

$cartCount = array_sum($cartProducts) + array_sum($cartDeals);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Your Cart</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
<div class="container py-4">
  <div class="d-flex justify-content-between align-items-center mb-3">
    <h3>Your Cart (<?= $cartCount ?> item<?= $cartCount == 1 ? '' : 's' ?>)</h3>
    <a href="index.php" class="btn btn-outline-secondary btn-sm">← Continue Shopping</a>
  </div>

  <?php if ($cartCount === 0): ?>
    <div class="alert alert-info">Your cart is empty.</div>
  <?php else: ?>
    <form method="post">
      <input type="hidden" name="action" value="update">

      <!-- Products -->
      <?php if (!empty($cartProducts)): ?>
        <h5>Products</h5>
        <div class="table-responsive mb-3">
          <table class="table table-striped align-middle">
            <thead class="table-light">
              <tr>
                <th>Product</th>
                <th style="width:140px;">Price</th>
                <th style="width:120px;">Quantity</th>
                <th style="width:140px;">Subtotal</th>
              </tr>
            </thead>
            <tbody>
            <?php foreach ($cartProducts as $pid => $qty): 
                if (!isset($productsById[$pid])) continue;
                $p = $productsById[$pid];
                $sub = $p['price'] * $qty;
            ?>
              <tr>
                <td><?= h($p['name']) ?></td>
                <td>$ <?= price($p['price']) ?></td>
                <td>
                  <input type="number" name="product_quantities[<?= (int)$pid ?>]" class="form-control form-control-sm" min="0" value="<?= (int)$qty ?>">
                  <div class="form-text small">0 to remove</div>
                </td>
                <td>$ <?= price($sub) ?></td>
              </tr>
            <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      <?php endif; ?>

      <!-- Deals -->
      <?php if (!empty($cartDeals)): ?>
        <h5>Deals</h5>
        <div class="table-responsive mb-3">
          <table class="table table-striped align-middle">
            <thead class="table-light">
              <tr>
                <th>Deal</th>
                <th style="width:140px;">Deal Price</th>
                <th style="width:120px;">Quantity</th>
                <th style="width:140px;">Subtotal</th>
              </tr>
            </thead>
            <tbody>
            <?php foreach ($cartDeals as $did => $qty): 
                if (!isset($dealsById[$did])) continue;
                $d = $dealsById[$did];
                $dealPrice = $dealTotals[$did] ?? 0.0;
                $sub = $dealPrice * $qty;
            ?>
              <tr>
                <td><?= h($d['deal_name']) ?></td>
                <td>$ <?= price($dealPrice) ?></td>
                <td>
                  <input type="number" name="deal_quantities[<?= (int)$did ?>]" class="form-control form-control-sm" min="0" value="<?= (int)$qty ?>">
                  <div class="form-text small">0 to remove</div>
                </td>
                <td>$ <?= price($sub) ?></td>
              </tr>
            <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      <?php endif; ?>

      <div class="d-flex justify-content-between align-items-center">
        <div>
          <button class="btn btn-outline-secondary btn-sm" type="submit">Update Cart</button>
          <button class="btn btn-outline-danger btn-sm" type="submit" name="action" value="clear">Clear Cart</button>
        </div>
        <div class="text-end">
          <div class="fw-bold fs-5">Total: $ <?= price($total) ?></div>
        </div>
      </div>
    </form>

    <div class="mt-4 d-flex justify-content-end">
      <a href="payment.php?mode=cart" class="btn btn-primary btn-lg">Proceed to Payment</a>
    </div>
  <?php endif; ?>
</div>
</body>
</html>
