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

// 下拉菜单用：读取所有分类
$catStmt = $pdo->query("SELECT id, name FROM categories ORDER BY name ASC");
$categories = $catStmt->fetchAll(PDO::FETCH_ASSOC);

// 只有 Admin/Manager 才能新增子分类
if ($isAdminOrManager && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $category_id = (int)($_POST['category_id'] ?? 0);
    $name = trim($_POST['name'] ?? '');
    $desc = trim($_POST['description'] ?? '');

    if ($category_id <= 0) {
        $errors[] = "Parent category is required.";
    }
    if ($name === '') {
        $errors[] = "Subcategory name is required.";
    }

    if (empty($errors)) {
        $stmt = $pdo->prepare("
            INSERT INTO subcategories (category_id, name, description)
            VALUES (?, ?, ?)
        ");
        if ($stmt->execute([$category_id, $name, $desc])) {
            $success = "Subcategory added successfully.";
        } else {
            $errors[] = "Failed to add subcategory.";
        }
    }
}

// 子分类列表：带上所属分类名称
$subStmt = $pdo->query("
    SELECT s.id, s.name, s.description, s.created_at, c.name AS category_name
    FROM subcategories s
    JOIN categories c ON s.category_id = c.id
    ORDER BY s.id DESC
");
$subcategories = $subStmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Subcategories</title>
    <link rel="stylesheet"
          href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
</head>
<body>
<div class="d-flex">
    <?php include 'sidebar.php'; ?>

    <main class="flex-grow-1 bg-light p-4">
        <h3 class="mb-3">Subcategory Management</h3>

        <?php if (!$isAdminOrManager): ?>
            <div class="alert alert-danger">
                You have no access to this URL.
            </div>
        <?php else: ?>

            <!-- Add Subcategory -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0">Add Subcategory</h5>
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
                            <label class="form-label">Parent Category</label>
                            <select name="category_id" class="form-select" required>
                                <option value="">-- Select Category --</option>
                                <?php foreach ($categories as $c): ?>
                                    <option value="<?= $c['id'] ?>"
                                        <?= (($_POST['category_id'] ?? '') == $c['id']) ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($c['name']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Subcategory Name</label>
                            <input type="text" name="name" class="form-control"
                                   value="<?= htmlspecialchars($_POST['name'] ?? '') ?>" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Description (optional)</label>
                            <input type="text" name="description" class="form-control"
                                   value="<?= htmlspecialchars($_POST['description'] ?? '') ?>">
                        </div>
                        <button type="submit" class="btn btn-primary">Save Subcategory</button>
                    </form>
                </div>
            </div>

            <!-- Subcategory List -->
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">All Subcategories</h5>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-striped mb-0">
                            <thead class="table-light">
                            <tr>
                                <th>ID</th>
                                <th>Subcategory Name</th>
                                <th>Category</th>
                                <th>Description</th>
                                <th>Created At</th>
                            </tr>
                            </thead>
                            <tbody>
                            <?php if (empty($subcategories)): ?>
                                <tr>
                                    <td colspan="5" class="text-center py-3">No subcategories found.</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($subcategories as $s): ?>
                                    <tr>
                                        <td><?= $s['id'] ?></td>
                                        <td><?= htmlspecialchars($s['name']) ?></td>
                                        <td><?= htmlspecialchars($s['category_name']) ?></td>
                                        <td><?= htmlspecialchars($s['description']) ?></td>
                                        <td><?= htmlspecialchars($s['created_at']) ?></td>
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
