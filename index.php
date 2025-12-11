<?php
// index.php
require 'config.php';

function h($s){ return htmlspecialchars($s ?? '', ENT_QUOTES, 'UTF-8'); }
function price($n){ return number_format((float)$n, 2); }

if (!isset($_SESSION['cart_products'])) $_SESSION['cart_products'] = [];
if (!isset($_SESSION['cart_deals']))    $_SESSION['cart_deals']    = [];

$cartCount = array_sum($_SESSION['cart_products']) + array_sum($_SESSION['cart_deals']);



// Categories with product counts
$catStmt = $pdo->query("
    SELECT c.id, c.name,
           (SELECT COUNT(p2.id)
            FROM products p2
            WHERE p2.category_id = c.id) AS product_count
    FROM categories c
    ORDER BY c.name ASC
");
$categories = $catStmt->fetchAll(PDO::FETCH_ASSOC);

// Featured products
$featuredStmt = $pdo->query("
    SELECT p.*, c.name AS category_name, s.name AS subcategory_name
    FROM products p, categories c, subcategories s
    WHERE p.category_id = c.id
      AND p.subcategory_id = s.id
    ORDER BY p.available_qty DESC, p.id DESC
    LIMIT 8
");
$featured = $featuredStmt->fetchAll(PDO::FETCH_ASSOC);

// New arrivals
$newStmt = $pdo->query("
    SELECT p.*, c.name AS category_name, s.name AS subcategory_name
    FROM products p, categories c, subcategories s
    WHERE p.category_id = c.id
      AND p.subcategory_id = s.id
    ORDER BY p.id DESC
    LIMIT 8
");
$newArrivals = $newStmt->fetchAll(PDO::FETCH_ASSOC);

// Budget picks
$budgetStmt = $pdo->query("
    SELECT p.*, c.name AS category_name, s.name AS subcategory_name
    FROM products p, categories c, subcategories s
    WHERE p.category_id = c.id
      AND p.subcategory_id = s.id
    ORDER BY p.price ASC
    LIMIT 8
");
$budget = $budgetStmt->fetchAll(PDO::FETCH_ASSOC);

function productCard($p){
    $img = $p['product_image_url'] ?: 'https://via.placeholder.com/400x250?text=No+Image';
    $title = h($p['name']);
    $cat = h($p['category_name']); $sub = h($p['subcategory_name']);
    $price = price($p['price']);
    $qty = (int)$p['available_qty'];
    $desc = trim($p['description'] ?? '');
    if (strlen($desc) > 80) $desc = substr($desc, 0, 80) . '…';
    $desc = h($desc);
    $disabled = $qty <= 0 ? 'disabled' : '';

    return "
    <div class='col-12 col-sm-6 col-lg-3 mb-4'>
      <div class='card h-100 shadow-sm'>
        <div class='ratio ratio-16x9'>
          <img src='{$img}' alt='{$title}' class='card-img-top object-fit-cover'>
        </div>
        <div class='card-body d-flex flex-column'>
          <h6 class='card-title mb-1'>{$title}</h6>
          <small class='text-muted'>{$cat} / {$sub}</small>
          <p class='mt-2 mb-2 text-secondary small'>{$desc}</p>
          <div class='mt-auto d-flex justify-content-between align-items-center'>
            <span class='fw-bold'>\$ {$price}</span>
            <span class='badge bg-".($qty>0?'success':'secondary')."'>".($qty>0?'In stock':'Out')."</span>
          </div>

          <!-- Add to Cart -->
          <form method='post' action='cart.php' class='mt-2'>
            <input type='hidden' name='action' value='add_product'>
            <input type='hidden' name='product_id' value='".(int)$p['id']."'>
            <div class='input-group input-group-sm'>
              <input type='number' name='quantity' class='form-control' min='1' value='1' {$disabled}>
              <button class='btn btn-primary' type='submit' {$disabled}>Add to Cart</button>
            </div>
          </form>

          <!-- Buy Now -->
          <form method='get' action='payment.php' class='mt-2'>
            <input type='hidden' name='mode' value='buy_now_product'>
            <input type='hidden' name='product_id' value='".(int)$p['id']."'>
            <input type='hidden' name='quantity' value='1'>
            <button class='btn btn-outline-success btn-sm w-100' type='submit' {$disabled}>Buy Now</button>
          </form>
        </div>
      </div>
    </div>";
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Shop Home</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <style>
    body { background:#f6f7f9; }
    .hero { background:linear-gradient(135deg,#0d6efd,#6610f2); color:#fff; border-radius:24px; }
    .hero .cta-btn { border-radius:999px; padding:.6rem 1.2rem; }
    .section-title { display:flex; align-items:center; gap:.5rem; }
    .section-title .bar { width:28px; height:4px; background:#0d6efd; border-radius:4px; }
  </style>
</head>
<body>
<div class="container py-4">
  <!-- Navbar -->
  <div class="d-flex justify-content-between align-items-center mb-3">
    <a class="navbar-brand fw-bold" href="index.php">🛍️ MyShop</a>
    <div class="d-flex align-items-center gap-2">
      <a href="deals.php" class="btn btn-outline-info btn-sm">Deals</a>
      <a href="cart.php" class="btn btn-outline-primary btn-sm">Cart (<?= $cartCount ?>)</a>
      <?php if (!empty($_SESSION['user_id'])): ?>
        <span class="text-muted small">Hi, <?= h($_SESSION['user_name'] ?? '') ?></span>
        <?php
        $roleName = $_SESSION['role_name'] ?? '';
        $isAdminOrManager = in_array($roleName, ['Admin', 'Manager'], true);
        if ($isAdminOrManager):
        ?>
          <a href="dashboard.php" class="btn btn-outline-secondary btn-sm">Dashboard</a>
        <?php endif; ?>
        <a href="logout.php" class="btn btn-outline-danger btn-sm">Logout</a>
      <?php else: ?>
        <a href="login.php" class="btn btn-outline-secondary btn-sm">Login</a>
        <a href="register.php" class="btn btn-primary btn-sm">Sign Up</a>
      <?php endif; ?>
    </div>
  </div>

  <!-- Hero -->
  <div class="hero p-4 p-md-5 mb-4">
    <h1 class="display-6 fw-bold mb-2">Discover Products You’ll Love</h1>
    <p class="lead mb-3">Browse featured picks, new arrivals and budget-friendly options.</p>
    <a href="#new" class="btn btn-light cta-btn me-2">Shop New Arrivals</a>
    <a href="#budget" class="btn btn-outline-light cta-btn">Budget Picks</a>
  </div>

  <!-- Categories -->
  <section class="mb-5">
    <div class="d-flex justify-content-between align-items-center mb-3">
      <div class="section-title">
        <span class="bar"></span><h4 class="mb-0">Browse by Category</h4>
      </div>
    </div>
    <div class="row g-3">
      <?php if (empty($categories)): ?>
        <div class="col-12"><div class="alert alert-light border">No categories yet.</div></div>
      <?php else: foreach($categories as $cat): ?>
        <div class="col-6 col-md-4 col-lg-3">
          <div class="border bg-white rounded-3 p-3 h-100">
            <div class="fw-semibold"><?= h($cat['name']) ?></div>
            <small class="text-muted"><?= (int)$cat['product_count'] ?> items</small>
          </div>
        </div>
      <?php endforeach; endif; ?>
    </div>
  </section>

  <!-- Featured -->
  <section class="mb-5">
    <div class="d-flex justify-content-between align-items-center mb-3">
      <div class="section-title">
        <span class="bar"></span><h4 class="mb-0">Featured Products</h4>
      </div>
    </div>
    <div class="row">
      <?php if (empty($featured)): ?>
        <div class="col-12"><div class="alert alert-light border">No featured products.</div></div>
      <?php else: foreach($featured as $p) echo productCard($p); endif; ?>
    </div>
  </section>

  <!-- New Arrivals -->
  <section class="mb-5" id="new">
    <div class="d-flex justify-content-between align-items-center mb-3">
      <div class="section-title">
        <span class="bar"></span><h4 class="mb-0">New Arrivals</h4>
      </div>
    </div>
    <div class="row">
      <?php if (empty($newArrivals)): ?>
        <div class="col-12"><div class="alert alert-light border">No new products.</div></div>
      <?php else: foreach($newArrivals as $p) echo productCard($p); endif; ?>
    </div>
  </section>

  <!-- Budget -->
  <section class="mb-5" id="budget">
    <div class="d-flex justify-content-between align-items-center mb-3">
      <div class="section-title">
        <span class="bar"></span><h4 class="mb-0">Budget Picks</h4>
      </div>
    </div>
    <div class="row">
      <?php if (empty($budget)): ?>
        <div class="col-12"><div class="alert alert-light border">No budget picks.</div></div>
      <?php else: foreach($budget as $p) echo productCard($p); endif; ?>
    </div>
  </section>

  <footer class="text-center text-muted small py-3">
    © <?= date('Y') ?> MyShop.
  </footer>
</div>
</body>
</html>
