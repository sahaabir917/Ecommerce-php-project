<?php
require 'config.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$roleName = $_SESSION['role_name'] ?? '';
$isAdminOrManager = in_array($roleName, ['Admin', 'Manager'], true);

$errors = [];
$success = $_SESSION['success_message'] ?? "";
unset($_SESSION['success_message']);
if (!empty($_SESSION['error_message'])) {
    $errors[] = $_SESSION['error_message'];
    unset($_SESSION['error_message']);
}
$editCategory = null;

// Handle Delete
if ($isAdminOrManager && isset($_GET['delete'])) {
    $deleteId = (int)$_GET['delete'];
    try {
        $stmt = $pdo->prepare("DELETE FROM categories WHERE id = ?");
        if ($stmt->execute([$deleteId])) {
            $_SESSION['success_message'] = "Category deleted successfully.";
        }
    } catch (PDOException $e) {
        $_SESSION['error_message'] = "Cannot delete category. It may be in use by products or subcategories.";
    }
    header("Location: categories.php");
    exit;
}

// Handle Edit - Load category data
if ($isAdminOrManager && isset($_GET['edit'])) {
    $editId = (int)$_GET['edit'];
    $stmt = $pdo->prepare("SELECT * FROM categories WHERE id = ?");
    $stmt->execute([$editId]);
    $editCategory = $stmt->fetch(PDO::FETCH_ASSOC);
}

// Handle Add/Update Category
if ($isAdminOrManager && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $desc = trim($_POST['description'] ?? '');
    $categoryId = (int)($_POST['category_id'] ?? 0);

    if ($name === '') {
        $errors[] = "Category name is required.";
    }

    if (empty($errors)) {
        if ($categoryId > 0) {
            // Update existing category
            $stmt = $pdo->prepare("UPDATE categories SET name = ?, description = ? WHERE id = ?");
            if ($stmt->execute([$name, $desc, $categoryId])) {
                $_SESSION['success_message'] = "Category updated successfully.";
                header("Location: categories.php");
                exit;
            } else {
                $errors[] = "Failed to update category.";
            }
        } else {
            // Insert new category
            $stmt = $pdo->prepare("INSERT INTO categories (name, description) VALUES (?, ?)");
            if ($stmt->execute([$name, $desc])) {
                $_SESSION['success_message'] = "Category added successfully.";
                header("Location: categories.php");
                exit;
            } else {
                $errors[] = "Failed to add category.";
            }
        }
    }
}

// Fetch all categories
$categoryStmt = $pdo->query("SELECT * FROM categories ORDER BY id DESC");
$categories = $categoryStmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Categories</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
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
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0"><?= $editCategory ? 'Edit Category' : 'Add Category' ?></h5>
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
                        <input type="hidden" name="category_id" value="<?= $editCategory['id'] ?? 0 ?>">
                        <div class="mb-3">
                            <label class="form-label">Category Name</label>
                            <input type="text" name="name" class="form-control"
                                   value="<?= htmlspecialchars($editCategory['name'] ?? $_POST['name'] ?? '') ?>" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Description (optional)</label>
                            <input type="text" name="description" class="form-control"
                                   value="<?= htmlspecialchars($editCategory['description'] ?? $_POST['description'] ?? '') ?>">
                        </div>
                        <button type="submit" class="btn btn-primary">
                            <?= $editCategory ? 'Update Category' : 'Save Category' ?>
                        </button>
                        <?php if ($editCategory): ?>
                            <a href="categories.php" class="btn btn-secondary">Cancel</a>
                        <?php endif; ?>
                    </form>
                </div>
            </div>

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
                                <th>Actions</th>
                            </tr>
                            </thead>
                            <tbody>
                            <?php if (empty($categories)): ?>
                                <tr>
                                    <td colspan="5" class="text-center py-3">No categories found.</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($categories as $c): ?>
                                    <tr>
                                        <td><?= $c['id'] ?></td>
                                        <td><?= htmlspecialchars($c['name']) ?></td>
                                        <td><?= htmlspecialchars($c['description']) ?></td>
                                        <td><?= htmlspecialchars($c['created_at']) ?></td>
                                        <td>
                                            <a href="categories.php?edit=<?= $c['id'] ?>" class="btn btn-sm btn-warning">Edit</a>
                                            <a href="categories.php?delete=<?= $c['id'] ?>"
                                               class="btn btn-sm btn-danger"
                                               onclick="return confirm('Are you sure you want to delete this category?')">Delete</a>
                                        </td>
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
