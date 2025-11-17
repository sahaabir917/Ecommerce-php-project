<?php
// payment.php
require 'config.php';

function h($s){ return htmlspecialchars($s ?? '', ENT_QUOTES, 'UTF-8'); }
function price($n){ return number_format((float)$n, 2); }

if (empty($_SESSION['user_id'])) {
    // Must be logged in to order
    header("Location: login.php");
    exit;
}

$mode = $_GET['mode'] ?? ($_POST['mode'] ?? 'cart');
$items = [];  // each: ['type'=>'product|deal','id'=>int,'name'=>..., 'unit_price'=>float, 'quantity'=>int]
$total = 0.0;

// Helpers
function buildCartItems($pdo){
    $items = [];
    $total = 0.0;

    if (!isset($_SESSION['cart_products'])) $_SESSION['cart_products'] = [];
    if (!isset($_SESSION['cart_deals']))    $_SESSION['cart_deals']    = [];
    $cartProducts = $_SESSION['cart_products'];
    $cartDeals    = $_SESSION['cart_deals'];

    // Products
    if (!empty($cartProducts)) {
        $ids = array_keys($cartProducts);
        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $stmt = $pdo->prepare("SELECT * FROM products WHERE id IN ($placeholders)");
        $stmt->execute($ids);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $byId = [];
        foreach ($rows as $p) $byId[$p['id']] = $p;

        foreach ($cartProducts as $pid => $qty) {
            if (!isset($byId[$pid])) continue;
            $p = $byId[$pid];
            $price = (float)$p['price'];
            $subtotal = $price * $qty;
            $items[] = [
                'type' => 'product',
                'id' => $pid,
                'name' => $p['name'],
                'unit_price' => $price,
                'quantity' => $qty,
                'subtotal' => $subtotal,
            ];
            $total += $subtotal;
        }
    }

    // Deals
    if (!empty($cartDeals)) {
        $ids = array_keys($cartDeals);
        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $dstmt = $pdo->prepare("SELECT * FROM deals WHERE id IN ($placeholders)");
        $dstmt->execute($ids);
        $dealRows = $dstmt->fetchAll(PDO::FETCH_ASSOC);
        $dealsById = [];
        foreach ($dealRows as $d) $dealsById[$d['id']] = $d;

        foreach ($cartDeals as $did => $qty) {
            if (!isset($dealsById[$did])) continue;
            // total per deal from deal_products
            $stmt = $pdo->prepare("
                SELECT dp.offered_price
                FROM deal_products dp
                WHERE dp.deal_id = ?
            ");
            $stmt->execute([$did]);
            $dealPrice = 0.0;
            while ($r = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $dealPrice += (float)$r['offered_price'];
            }
            $subtotal = $dealPrice * $qty;
            $items[] = [
                'type' => 'deal',
                'id' => $did,
                'name' => $dealsById[$did]['deal_name'],
                'unit_price' => $dealPrice,
                'quantity' => $qty,
                'subtotal' => $subtotal,
            ];
            $total += $subtotal;
        }
    }

    return [$items, $total];
}

function buildBuyNowProduct($pdo, $productId, $quantity){
    $items = [];
    $total = 0.0;
    $stmt = $pdo->prepare("SELECT * FROM products WHERE id = ?");
    $stmt->execute([$productId]);
    $p = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$p) return [[], 0.0];
    $price = (float)$p['price'];
    $subtotal = $price * $quantity;
    $items[] = [
        'type' => 'product',
        'id' => $productId,
        'name' => $p['name'],
        'unit_price' => $price,
        'quantity' => $quantity,
        'subtotal' => $subtotal,
    ];
    $total = $subtotal;
    return [$items, $total];
}

function buildBuyNowDeal($pdo, $dealId, $quantity){
    $items = [];
    $total = 0.0;
    $stmt = $pdo->prepare("SELECT * FROM deals WHERE id = ?");
    $stmt->execute([$dealId]);
    $deal = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$deal) return [[], 0.0];

    $pstmt = $pdo->prepare("
        SELECT dp.offered_price
        FROM deal_products dp
        WHERE dp.deal_id = ?
    ");
    $pstmt->execute([$dealId]);
    $dealPrice = 0.0;
    while ($r = $pstmt->fetch(PDO::FETCH_ASSOC)) {
        $dealPrice += (float)$r['offered_price'];
    }
    $subtotal = $dealPrice * $quantity;
    $items[] = [
        'type' => 'deal',
        'id' => $dealId,
        'name' => $deal['deal_name'],
        'unit_price' => $dealPrice,
        'quantity' => $quantity,
        'subtotal' => $subtotal,
    ];
    $total = $subtotal;
    return [$items, $total];
}

// Decide mode and build items list
if ($mode === 'cart') {
    list($items, $total) = buildCartItems($pdo);
} elseif ($mode === 'buy_now_product') {
    $pid = (int)($_GET['product_id'] ?? $_POST['product_id'] ?? 0);
    $qty = (int)($_GET['quantity'] ?? $_POST['quantity'] ?? 1);
    if ($qty < 1) $qty = 1;
    list($items, $total) = buildBuyNowProduct($pdo, $pid, $qty);
} elseif ($mode === 'buy_now_deal') {
    $did = (int)($_GET['deal_id'] ?? $_POST['deal_id'] ?? 0);
    $qty = (int)($_GET['quantity'] ?? $_POST['quantity'] ?? 1);
    if ($qty < 1) $qty = 1;
    list($items, $total) = buildBuyNowDeal($pdo, $did, $qty);
} else {
    // fallback
    list($items, $total) = buildCartItems($pdo);
    $mode = 'cart';
}

if (empty($items)) {
    // nothing to pay for
    header("Location: cart.php");
    exit;
}

$errors = [];

// Handle POST (final payment submission)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['pay_now'])) {
    $card_number = trim($_POST['card_number'] ?? '');
    $card_holder = trim($_POST['card_holder_name'] ?? '');

    if ($card_number === '') $errors[] = "Card number is required.";
    if ($card_holder === '') $errors[] = "Card holder name is required.";

    if (empty($errors)) {
        try {
            $pdo->beginTransaction();

            // Insert payment
            $payStmt = $pdo->prepare("
                INSERT INTO payments (card_number, amount, card_holder_name)
                VALUES (?, ?, ?)
            ");
            $payStmt->execute([$card_number, $total, $card_holder]);
            $paymentId = $pdo->lastInsertId();

            // Insert order
            $orderStmt = $pdo->prepare("
                INSERT INTO orders (user_id, payment_id)
                VALUES (?, ?)
            ");
            $orderStmt->execute([$_SESSION['user_id'], $paymentId]);
            $orderId = $pdo->lastInsertId();

            // Insert order_details
            $detailStmt = $pdo->prepare("
                INSERT INTO order_details (order_id, product_id, deal_id, unit_price, quantity, status)
                VALUES (?, ?, ?, ?, ?, 'confirmed')
            ");

            foreach ($items as $it) {
                if ($it['type'] === 'product') {
                    $detailStmt->execute([
                        $orderId,
                        $it['id'],     // product_id
                        null,          // deal_id
                        $it['unit_price'],
                        $it['quantity']
                    ]);
                } elseif ($it['type'] === 'deal') {
                    $detailStmt->execute([
                        $orderId,
                        null,          // product_id
                        $it['id'],     // deal_id
                        $it['unit_price'],
                        $it['quantity']
                    ]);
                }
                // Triggers will update product/deal quantities
            }

            $pdo->commit();

            // Clear cart only if from cart mode
            if ($mode === 'cart') {
                $_SESSION['cart_products'] = [];
                $_SESSION['cart_deals'] = [];
            }

            header("Location: order_details.php?id=" . $orderId);
            exit;

        } catch (Exception $e) {
            $pdo->rollBack();
            $errors[] = "Payment/Order failed: " . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Payment</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
<div class="container py-4">
  <div class="d-flex justify-content-between align-items-center mb-3">
    <h3>Payment</h3>
    <a href="<?= $mode === 'cart' ? 'cart.php' : 'index.php' ?>" class="btn btn-outline-secondary btn-sm">← Back</a>
  </div>

  <div class="row">
    <div class="col-md-6">
      <?php if (!empty($errors)): ?>
        <div class="alert alert-danger">
          <ul class="mb-0">
            <?php foreach ($errors as $e): ?>
              <li><?= h($e) ?></li>
            <?php endforeach; ?>
          </ul>
        </div>
      <?php endif; ?>

      <div class="card mb-3">
        <div class="card-header">
          <strong>Pay $ <?= price($total) ?></strong>
        </div>
        <div class="card-body">
          <form method="post">
            <input type="hidden" name="mode" value="<?= h($mode) ?>">
            <?php if ($mode === 'buy_now_product'): ?>
              <input type="hidden" name="product_id" value="<?= (int)($items[0]['id'] ?? 0) ?>">
              <input type="hidden" name="quantity" value="<?= (int)($items[0]['quantity'] ?? 1) ?>">
            <?php elseif ($mode === 'buy_now_deal'): ?>
              <input type="hidden" name="deal_id" value="<?= (int)($items[0]['id'] ?? 0) ?>">
              <input type="hidden" name="quantity" value="<?= (int)($items[0]['quantity'] ?? 1) ?>">
            <?php endif; ?>

            <div class="mb-3">
              <label class="form-label">Card Number</label>
              <input type="text" name="card_number" class="form-control"
                     value="<?= h($_POST['card_number'] ?? '') ?>" required>
            </div>
            <div class="mb-3">
              <label class="form-label">Card Holder Name</label>
              <input type="text" name="card_holder_name" class="form-control"
                     value="<?= h($_POST['card_holder_name'] ?? '') ?>" required>
            </div>
            <button type="submit" name="pay_now" class="btn btn-primary">Confirm Payment & Place Order</button>
          </form>
        </div>
      </div>
    </div>

    <div class="col-md-6">
      <div class="card">
        <div class="card-header"><strong>Order Summary</strong></div>
        <div class="card-body">
          <?php foreach ($items as $it): ?>
            <div class="d-flex justify-content-between mb-1">
              <div>
                <strong><?= h($it['name']) ?></strong>
                <span class="text-muted small">
                  (<?= h($it['type'] === 'product' ? 'Product' : 'Deal') ?>) x <?= (int)$it['quantity'] ?>
                </span>
              </div>
              <div>$ <?= price($it['subtotal']) ?></div>
            </div>
          <?php endforeach; ?>
          <hr>
          <div class="d-flex justify-content-between fw-bold">
            <div>Total</div>
            <div>$ <?= price($total) ?></div>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>
</body>
</html>
