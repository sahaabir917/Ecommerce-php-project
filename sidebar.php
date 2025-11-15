<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$roleNameRaw = $_SESSION['role_name'] ?? '';
$roleName = strtolower($roleNameRaw);

$isAdminOrManager = in_array($roleName, ['admin', 'manager'], true);
$isCustomer = ($roleName === 'customer');

$currentPage = basename($_SERVER['PHP_SELF']);
?>
<nav class="flex-shrink-0 p-3 bg-dark text-white" style="width: 240px; min-height: 100vh;">
    <h4 class="mb-4">My Shop</h4>

    <ul class="nav nav-pills flex-column mb-auto">

        <!-- Dashboard -->
        <li class="nav-item">
            <a href="dashboard.php"
               class="nav-link text-white <?= $currentPage === 'dashboard.php' ? 'active bg-secondary' : '' ?>">
                Dashboard
            </a>
        </li>

        <!-- Admin Only -->
        <?php if ($isAdminOrManager): ?>
            <li class="nav-item">
                <a href="categories.php"
                   class="nav-link text-white <?= $currentPage === 'categories.php' ? 'active bg-secondary' : '' ?>">
                    Categories
                </a>
            </li>
            <li class="nav-item">
                <a href="subcategories.php"
                   class="nav-link text-white <?= $currentPage === 'subcategories.php' ? 'active bg-secondary' : '' ?>">
                    Subcategories
                </a>
            </li>
        <?php endif; ?>

        <!-- Products (Everyone) -->
        <li class="nav-item">
            <a href="products.php"
               class="nav-link text-white <?= $currentPage === 'products.php' ? 'active bg-secondary' : '' ?>">
                Products
            </a>
        </li>

        <!-- Customer Cart & Orders -->
        <?php if ($isCustomer): ?>
            <li class="nav-item">
                <a href="cart.php"
                   class="nav-link text-white <?= $currentPage === 'cart.php' ? 'active bg-secondary' : '' ?>">
                    My Cart
                </a>
            </li>
            <li class="nav-item">
                <a href="orders.php"
                   class="nav-link text-white <?= $currentPage === 'orders.php' ? 'active bg-secondary' : '' ?>">
                    My Orders
                </a>
            </li>

        <!-- Admin Orders -->
        <?php elseif ($isAdminOrManager): ?>
            <li class="nav-item">
                <a href="orders.php"
                   class="nav-link text-white <?= $currentPage === 'orders.php' ? 'active bg-secondary' : '' ?>">
                    All Orders
                </a>
            </li>
        <?php endif; ?>

    </ul>

    <hr>

    <div class="small">
        Logged in as:<br>
        <strong><?= htmlspecialchars($_SESSION['user_name'] ?? '') ?></strong><br>
        <span class="text-muted"><?= htmlspecialchars($roleNameRaw) ?></span>
    </div>

    <a href="logout.php" class="btn btn-outline-light btn-sm mt-3">Logout</a>
</nav>
