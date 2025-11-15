<?php
require 'config.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

// 统一角色判断
$roleNameRaw = $_SESSION['role_name'] ?? '';
$roleName = strtolower($roleNameRaw);
$isAdminOrManager = in_array($roleName, ['admin', 'manager'], true);

$errors = [];
$success = "";

// 只有 Admin/Manager 才能添加分类
if ($isAdminOrManager && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $desc = trim($_POST['description'] ?? '');

    if ($name === '') {
        $errors[] = "Category name is required.";
    }

    if (empty($errors)) {
        $stmt = $pdo->prepare("INSERT INTO categories (name, description) VALUES (?, ?)");
        if ($stmt->execute([$name, $desc])) {
            $success = "Category added successfully.";
        } else {
            $errors[] = "Failed to add category.";
        }
    }
}

// 读取所有分类
$categoryStmt = $pdo->query("SELECT * FROM categories ORDER BY id DESC");
$categories = $categoryStmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Categories</title>
    <link rel="stylesheet"
          href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
</head>
<body>
<div class="d-flex">
    <?php include 'sidebar.php'; ?>

    <main class="flex-grow-1 bg-light p-4">
        <h3 class="mb-3">Category Management</h3>

        <?php if (!$isAdminOrManager): ?>
            <div class="alert alert-danger">
                You have no access to this URL.
            </div>
        <?php else: ?>

            <!-- Add Category -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0">Add Category</h5>
                </div>
                <div class="card-body">
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

                    <form method="post">
                        <div class="mb-3">
                            <label class="form-label">Category Name</label>
                            <input type="text" name="name" class="form-control"
                                   value="<?= htmlspecialchars($_POST['name'] ?? '') ?>" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Description (optional)</label>
                            <input type="text" name="description" class="form-control"
                                   value="<?= htmlspecialchars($_POST['description'] ?? '') ?>">
                        </div>
                        <button type="submit" class="btn btn-primary">Save Category</button>
                    </form>
                </div>
            </div>

            <!-- Category List -->
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">All Categories</h5>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-striped mb-0">
                            <thead class="table-light">
                            <tr>
                                <th>ID</th>
                                <th>Name</th>
                                <th>Description</th>
                                <th>Created At</th>
                            </tr>
                            </thead>
                            <tbody>
                            <?php if (empty($categories)): ?>
                                <tr>
                                    <td colspan="4" class="text-center py-3">No categories found.</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($categories as $c): ?>
                                    <tr>
                                        <td><?= $c['id'] ?></td>
                                        <td><?= htmlspecialchars($c['name']) ?></td>
                                        <td><?= htmlspecialchars($c['description']) ?></td>
                                        <td><?= htmlspecialchars($c['created_at']) ?></td>
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
</body>
</html>
