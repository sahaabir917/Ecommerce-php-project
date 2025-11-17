<?php
// deal_details.php
require 'config.php';

function h($s){ return htmlspecialchars($s ?? '', ENT_QUOTES, 'UTF-8'); }
function price($n){ return number_format((float)$n, 2); }

if (!isset($_SESSION['cart_products'])) $_SESSION['cart_products'] = [];
if (!isset($_SESSION['cart_deals']))    $_SESSION['cart_deals']    = [];

// Cart count for header
$cartCount = array_sum($_SESSION['cart_products']) + array_sum($_SESSION['cart_deals']);

// Get deal id from query
$dealId = (int)($_GET['id'] ?? 0);
if ($dealId <= 0) {
    die("Invalid deal id.");
}

// Fetch deal
$dealStmt = $pdo->prepare("
    SELECT d.id, d.deal_name, d.created_at
    FROM deals d
    WHERE d.id = ?
    LIMIT 1
");
$dealStmt->execute([$dealId]);
$deal = $dealStmt->fetch(PDO::FETCH_ASSOC);

if (!$deal) {
    die("Deal not found.");
}

// Fetch deal products + product info (no JOIN keyword)
$dpStmt = $pdo->prepare("
    SELECT dp.id AS deal_product_id,
           dp.available_quantity AS deal_available_qty,
           dp.offered_price,
           p.id AS product_id,
           p.name AS product_name,
           p.price AS product_price,
           p.product_image_url,
           p.description
    FROM deal_products dp, products p
    WHERE dp.product_id = p.id
      AND dp.deal_id = ?
");
$dpStmt->execute([$dealId]);
$dealProducts = $dpStmt->fetchAll(PDO::FETCH_ASSOC);

// Calculate totals & stats
$dealTotal = 0.0;         // sum of offered_price
$originalTotal = 0.0;     // sum of original product price
$dealsRemaining = null;   // min over deal_available_qty

foreach ($dealProducts as $row) {
    $dealTotal      += (float)$row['offered_price'];
    $originalTotal  += (float)$row['product_price'];
    $qtyForThisProd  = (int)$row['deal_available_qty'];
    if ($dealsRemaining === null) {
        $dealsRemaining = $qtyForThisProd;
    } else {
        $dealsRemaining = min($dealsRemaining, $qtyForThisProd);
    }
}

$discountAmount = max(0.0, $originalTotal - $dealTotal);
$discountPercent = ($originalTotal > 0) ? ($discountAmount / $originalTotal * 100.0) : 0.0;

// Reasonable default if no products
if ($dealsRemaining === null) $dealsRemaining = 0;

// For Buy Now default quantity
$defaultQty = $dealsRemaining > 0 ? 1 : 0;
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title><?= h($deal['deal_name']) ?> - Deal Details</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <style>
    body { background:#f6f7f9; }
    .deal-hero {
      background: linear-gradient(135deg, #0d6efd, #20c997);
      color:#fff;
      border-radius: 24px;
    }
    .deal-badge {
      border-radius: 999px;
      padding: .25rem .75rem;
      background: rgba(255,255,255,.15);
      font-size: .85rem;
    }
    .pill-stat {
      border-radius: 999px;
      padding: .25rem .75rem;
      background: rgba(255,255,255,.10);
      font-size: .85rem;
    }
    .product-thumb {
      width: 80px;
      height: 80px;
      object-fit: cover;
      border-radius: 12px;
      border: 1px solid rgba(0,0,0,.05);
    }
    .saving-tag {
      font-size: .8rem;
      border-radius: 999px;
      padding: .1rem .5rem;
    }
  </style>
</head>
<body>
<div class="container py-4">
  <!-- Top bar -->
  <div class="d-flex justify-content-between align-items-center mb-3">
    <a href="index.php" class="navbar-brand fw-bold text-decoration-none">🛍️ MyShop</a>
    <div class="d-flex align-items-center gap-2">
      <a href="deals.php" class="btn btn-outline-info btn-sm">All Deals</a>
      <a href="cart.php" class="btn btn-outline-primary btn-sm">
        Cart (<?= $cartCount ?>)
      </a>
      <?php if (!empty($_SESSION['user_id'])): ?>
        <span class="text-muted small">
          Hi, <?= h($_SESSION['user_name'] ?? '') ?>
        </span>
        <a href="dashboard.php" class="btn btn-outline-secondary btn-sm">Dashboard</a>
        <a href="logout.php" class="btn btn-outline-danger btn-sm">Logout</a>
      <?php else: ?>
        <a href="login.php" class="btn btn-outline-secondary btn-sm">Login</a>
        <a href="register.php" class="btn btn-primary btn-sm">Sign Up</a>
      <?php endif; ?>
    </div>
  </div>

  <!-- Deal Hero Section -->
  <div class="deal-hero p-4 p-md-5 mb-4">
    <div class="row align-items-center">
      <div class="col-lg-7">
        <div class="d-flex align-items-center mb-2">
          <span class="deal-badge me-2">Special Deal</span>
          <small class="text-white-50">Created: <?= h($deal['created_at']) ?></small>
        </div>
        <h1 class="h2 fw-bold mb-2"><?= h($deal['deal_name']) ?></h1>
        <p class="mb-3">
          Get all products in this bundle for a single discounted price.
        </p>

        <div class="d-flex flex-wrap gap-2 mb-3">
          <div class="pill-stat">
            Bundle Price: <strong>$ <?= price($dealTotal) ?></strong>
          </div>
          <div class="pill-stat">
            Original Value: $ <?= price($originalTotal) ?>
          </div>
          <div class="pill-stat">
            You Save: $ <?= price($discountAmount) ?>
            (<?= number_format($discountPercent, 1) ?>%)
          </div>
          <div class="pill-stat">
            Deals Remaining: <strong><?= (int)$dealsRemaining ?></strong>
          </div>
        </div>

        <!-- Deal Action Buttons -->
        <div class="d-flex flex-wrap gap-2">
          <form method="post" action="cart.php" class="d-flex gap-2 align-items-center">
            <input type="hidden" name="action" value="add_deal">
            <input type="hidden" name="deal_id" value="<?= (int)$deal['id'] ?>">
            <label class="text-white-50 small mb-0">Qty</label>
            <input type="number" name="quantity" class="form-control form-control-sm"
                   style="width:80px;" min="1"
                   value="<?= $defaultQty ?: 1 ?>"
                   <?= $dealsRemaining <= 0 ? 'disabled' : '' ?>>
            <button class="btn btn-light btn-sm" type="submit"
                    <?= $dealsRemaining <= 0 ? 'disabled' : '' ?>>
              Add Deal to Cart
            </button>
          </form>

          <form method="get" action="payment.php">
            <input type="hidden" name="mode" value="buy_now_deal">
            <input type="hidden" name="deal_id" value="<?= (int)$deal['id'] ?>">
            <input type="hidden" name="quantity" value="1">
            <button class="btn btn-outline-light btn-sm"
                    type="submit"
                    <?= $dealsRemaining <= 0 ? 'disabled' : '' ?>>
              Buy Deal Now
            </button>
          </form>
        </div>

        <?php if ($dealsRemaining <= 0): ?>
          <p class="mt-2 mb-0 text-warning small">This deal is currently out of stock.</p>
        <?php endif; ?>
      </div>

      <div class="col-lg-5 d-none d-lg-block">
        <!-- Simple collage style preview using first 2 product images -->
        <?php
        $img1 = $dealProducts[0]['product_image_url'] ?? null;
        $img2 = $dealProducts[1]['product_image_url'] ?? null;
        if (!$img1) $img1 = 'https://via.placeholder.com/400x260?text=Deal';
        if (!$img2) $img2 = 'https://via.placeholder.com/200x140?text=Bundle';
        ?>
        <div class="position-relative">
          <img src="<?= h($img1) ?>" class="img-fluid rounded-4 shadow" alt="Deal preview">
          <img src="<?= h($img2) ?>" class="position-absolute rounded-4 shadow"
               style="width: 45%; bottom:-10%; right:5%; border:3px solid #fff;"
               alt="Deal preview small">
        </div>
      </div>
    </div>
  </div>

  <!-- Deal Products List -->
  <div class="card mb-4">
    <div class="card-header bg-white">
      <strong>Included Products</strong>
    </div>
    <div class="card-body">
      <?php if (empty($dealProducts)): ?>
        <p class="text-muted mb-0">No products are attached to this deal yet.</p>
      <?php else: ?>
        <?php foreach ($dealProducts as $row): 
            $img = $row['product_image_url'] ?: 'https://via.placeholder.com/80?text=No+Image';
            $pName = $row['product_name'];
            $orig = (float)$row['product_price'];
            $offer = (float)$row['offered_price'];
            $save = max(0.0, $orig - $offer);
            $savePercent = $orig > 0 ? ($save / $orig * 100.0) : 0.0;
        ?>
          <div class="d-flex align-items-start mb-3 pb-3 border-bottom">
            <img src="<?= h($img) ?>" class="product-thumb me-3" alt="<?= h($pName) ?>">
            <div class="flex-grow-1">
              <div class="d-flex justify-content-between align-items-center mb-1">
                <h6 class="mb-0"><?= h($pName) ?></h6>
                <?php if ($save > 0): ?>
                  <span class="saving-tag bg-success-subtle text-success border border-success-subtle">
                    Save $ <?= price($save) ?> (<?= number_format($savePercent,1) ?>%)
                  </span>
                <?php endif; ?>
              </div>
              <?php if (!empty($row['description'])): ?>
                <p class="mb-1 small text-muted">
                  <?= h(mb_strimwidth($row['description'], 0, 140, '…')) ?>
                </p>
              <?php endif; ?>
              <div class="d-flex align-items-center gap-3 small">
                <span>Original: <span class="text-decoration-line-through">$ <?= price($orig) ?></span></span>
                <span class="fw-semibold">Deal Price: $ <?= price($offer) ?></span>
                <span class="text-muted">Reserved for deal: <?= (int)$row['deal_available_qty'] ?> pcs</span>
              </div>
            </div>
          </div>
        <?php endforeach; ?>
      <?php endif; ?>
    </div>
  </div>

  <footer class="text-center text-muted small py-3">
    © <?= date('Y') ?> MyShop.
  </footer>
</div>
</body>
</html>
