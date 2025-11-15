<?php
require 'config.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

// 统一转成小写来判断权限（不再受数据库大小写影响）
$roleName = strtolower($_SESSION['role_name'] ?? '');
$isAdminOrManager = in_array($roleName, ['admin', 'manager'], true);

// Admin/Manager 才能看到所有用户
$users = [];
if ($isAdminOrManager) {
    $stmt = $pdo->query("
        SELECT u.id, u.name, u.email, u.phone, u.address, r.name AS role_name, u.created_at
        FROM users u
        JOIN roles r ON u.role_id = r.id
        ORDER BY u.id ASC
    ");
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Dashboard</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
</head>
<body>
<div class="d-flex">
    <?php include 'sidebar.php'; ?>

    <main class="flex-grow-1 bg-light p-4">
        <h3 class="mb-3">Dashboard</h3>

        <?php if ($isAdminOrManager): ?>
            <p class="text-muted">
                You are an <strong><?= htmlspecialchars($_SESSION['role_name']) ?></strong>.
                You can manage users, categories, subcategories, and products.
            </p>

            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">All Users</h5>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-striped mb-0">
                            <thead class="table-light">
                            <tr>
                                <th>ID</th>
                                <th>Name</th>
                                <th>Email</th>
                                <th>Phone</th>
                                <th>Address</th>
                                <th>Role</th>
                                <th>Created At</th>
                            </tr>
                            </thead>
                            <tbody>
                            <?php if (empty($users)): ?>
                                <tr>
                                    <td colspan="7" class="text-center py-3">No users found.</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($users as $u): ?>
                                    <tr>
                                        <td><?= $u['id'] ?></td>
                                        <td><?= htmlspecialchars($u['name']) ?></td>
                                        <td><?= htmlspecialchars($u['email']) ?></td>
                                        <td><?= htmlspecialchars($u['phone']) ?></td>
                                        <td><?= htmlspecialchars($u['address']) ?></td>
                                        <td><?= htmlspecialchars($u['role_name']) ?></td>
                                        <td><?= htmlspecialchars($u['created_at']) ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

        <?php else: ?>

            <p class="text-muted">
                You are logged in as a regular <strong>User</strong>.
                You do not have access to management pages.
            </p>

            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Your Profile</h5>
                </div>
                <div class="card-body">
                    <p><strong>Name:</strong> <?= htmlspecialchars($_SESSION['user_name'] ?? '') ?></p>
                    <p><strong>Email:</strong> <?= htmlspecialchars($_SESSION['user_email'] ?? '') ?></p>
                    <p class="text-muted mb-0">
                        Contact an Admin or Manager if you need higher access.
                    </p>
                </div>
            </div>

        <?php endif; ?>
    </main>
</div>
</body>
</html>
