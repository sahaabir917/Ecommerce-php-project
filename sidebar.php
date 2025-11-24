<?php

// sidebar.php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$roleName = $_SESSION['role_name'] ?? '';
$isAdminOrManager = in_array($roleName, ['Admin', 'Manager'], true);
?>
<nav class="flex-shrink-0 p-3 bg-dark text-white" style="width: 240px; min-height: 100vh;">
    <h4 class="mb-4">My Admin</h4>
    <ul class="nav nav-pills flex-column mb-auto">
        <li class="nav-item">
            <a href="dashboard.php" class="nav-link text-white <?= basename($_SERVER['PHP_SELF']) === 'dashboard.php' ? 'active' : '' ?>">
                Dashboard
            </a>
        </li>

        <?php if ($isAdminOrManager): ?>
            <li>
                <a href="categories.php" class="nav-link text-white <?= basename($_SERVER['PHP_SELF']) === 'categories.php' ? 'active' : '' ?>">
                    Categories
                </a>
            </li>
            <li>
                <a href="subcategories.php" class="nav-link text-white <?= basename($_SERVER['PHP_SELF']) === 'subcategories.php' ? 'active' : '' ?>">
                    Subcategories
                </a>
            </li>
            <li>
                <a href="products.php" class="nav-link text-white <?= basename($_SERVER['PHP_SELF']) === 'products.php' ? 'active' : '' ?>">
                    Products
                </a>
            </li>
        <?php endif; ?>
    </ul>
    <hr>
    <div class="small">
        Logged in as:<br>
        <strong><?= htmlspecialchars($_SESSION['user_name'] ?? '') ?></strong><br>
        <span class="text-muted"><?= htmlspecialchars($roleName) ?></span>
    </div>
    <a href="logout.php" class="btn btn-outline-light btn-sm mt-3">Logout</a>
</nav>