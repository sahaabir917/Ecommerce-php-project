<?php
// deals.php
require 'config.php';

function h($s){ return htmlspecialchars($s ?? '', ENT_QUOTES, 'UTF-8'); }
function price($n){ return number_format((float)$n, 2); }

if (!isset($_SESSION['cart_products'])) $_SESSION['cart_products'] = [];
if (!isset($_SESSION['cart_deals']))    $_SESSION['cart_deals']    = [];

$cartCount = array_sum($_SESSION['cart_products']) + array_sum($_SESSION['cart_deals']);

// Fetch all deals
$dealsStmt = $pdo->query("
    SELECT d.id, d.deal_name, d.created_at
    FROM deals d
    ORDER BY d.id DESC
");
$deals = $dealsStmt->fetchAll(PDO::FETCH_ASSOC);


function getDealProductsSummary($pdo, $dealId){
    $stmt = $pdo->prepare("
        SELECT 
            dp.available_quantity,
            dp.offered_price,
            p.name AS product_name
        FROM deal_products dp, products p
        WHERE dp.product_id = p.id
          AND dp.deal_id = ?
    ");
    $stmt->execute([$dealId]);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $dealTotal = 0.0;
    $dealsRemaining = null;

    foreach ($rows as $r) {
        $dealTotal += (float)$r['offered_price'];
        $qty = (int)$r['available_quantity'];
        if ($dealsRemaining === null) {
            $dealsRemaining = $qty;
        } else {
            $dealsRemaining = min($dealsRemaining, $qty);
        }
    }

    if ($dealsRemaining === null) $dealsRemaining = 0; // no products => 0 availability

    return [$rows, $dealTotal, $dealsRemaining];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Deals</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <style>
    body { background:#f6f7f9; }
  </style>
</head>
<body>
<div class="container py-4">
  <div class="d-flex justify-content-between align-items-center mb-3">
    <h3>Deals</h3>
    <div class="d-flex align-items-center gap-2">
      <a href="index.php" class="btn btn-outline-secondary btn-sm">← Home</a>
      <a href="cart.php" class="btn btn-outline-primary btn-sm">Cart (<?= $cartCount ?>)</a>
    </div>
  </div>

  <div class="row g-4">
    <?php
    $shownAny = false;
    if (!empty($deals)):
      foreach ($deals as $deal):
        list($dealProducts, $dealTotal, $dealsRemaining) = getDealProductsSummary($pdo, $deal['id']);

        // ❗ Skip deals that have no availability
        if ($dealsRemaining <= 0) {
            continue;
        }
        $shownAny = true;
    ?>
        <div class="col-12 col-md-6 col-lg-4">
          <div class="card h-100 shadow-sm">
            <div class="card-body d-flex flex-column">
              <!-- Deal title links to details page -->
              <h5 class="card-title mb-1">
                <a href="deal_details.php?id=<?= (int)$deal['id'] ?>" class="text-decoration-none">
                  <?= h($deal['deal_name']) ?>
                </a>
              </h5>
              <p class="text-muted small mb-2">
                Includes <?= count($dealProducts) ?> product(s) · Created: <?= h($deal['created_at']) ?>
              </p>

              <p class="mb-2">
                <span class="badge bg-success">Available: <?= (int)$dealsRemaining ?></span>
              </p>

              <?php if (empty($dealProducts)): ?>
                <p class="text-muted small mb-3">No products are attached to this deal yet.</p>
              <?php else: ?>
                <ul class="list-unstyled small mb-3">
                  <?php foreach ($dealProducts as $dp): ?>
                    <li class="mb-1">
                      <strong><?= h($dp['product_name']) ?></strong>
                      &nbsp;–&nbsp; Offer Price: $ <?= price($dp['offered_price']) ?>
                    </li>
                  <?php endforeach; ?>
                </ul>
              <?php endif; ?>

              <div class="mt-auto">
                <div class="d-flex justify-content-between align-items-center mb-2">
                  <span class="fw-bold">Total Deal Price:</span>
                  <span class="fs-5">$ <?= price($dealTotal) ?></span>
                </div>

                <!-- View Details button -->
                <a href="deal_details.php?id=<?= (int)$deal['id'] ?>" 
                   class="btn btn-outline-secondary btn-sm w-100 mb-2">
                  View Details
                </a>

                <!-- Add Deal to Cart -->
                <form method="post" action="cart.php" class="mb-2">
                  <input type="hidden" name="action" value="add_deal">
                  <input type="hidden" name="deal_id" value="<?= (int)$deal['id'] ?>">
                  <div class="input-group input-group-sm">
                    <input type="number" name="quantity" class="form-control" min="1" value="1">
                    <button class="btn btn-primary" type="submit">
                      Add to Cart
                    </button>
                  </div>
                </form>

                <!-- Buy Deal Now -->
                <form method="get" action="payment.php">
                  <input type="hidden" name="mode" value="buy_now_deal">
                  <input type="hidden" name="deal_id" value="<?= (int)$deal['id'] ?>">
                  <input type="hidden" name="quantity" value="1">
                  <button class="btn btn-success btn-sm w-100" type="submit">
                    Buy Now
                  </button>
                </form>
              </div>
            </div>
          </div>
        </div>
    <?php
      endforeach;
    endif;

    if (!$shownAny): ?>
      <div class="col-12">
        <div class="alert alert-info">No deals available right now.</div>
      </div>
    <?php endif; ?>
  </div>
</div>
</body>
</html>
