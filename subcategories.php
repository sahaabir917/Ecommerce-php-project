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
$editSubcategory = null;

// Fetch categories for dropdown
$catStmt = $pdo->query("SELECT id, name FROM categories ORDER BY name ASC");
$categories = $catStmt->fetchAll(PDO::FETCH_ASSOC);

// Handle Delete
if ($isAdminOrManager && isset($_GET['delete'])) {
    $deleteId = (int)$_GET['delete'];
    try {
        $stmt = $pdo->prepare("DELETE FROM subcategories WHERE id = ?");
        if ($stmt->execute([$deleteId])) {
            $_SESSION['success_message'] = "Subcategory deleted successfully.";
        }
    } catch (PDOException $e) {
        $_SESSION['error_message'] = "Cannot delete subcategory. It may be in use by products.";
    }
    header("Location: subcategories.php");
    exit;
}

// Handle Edit - Load subcategory data
if ($isAdminOrManager && isset($_GET['edit'])) {
    $editId = (int)$_GET['edit'];
    $stmt = $pdo->prepare("SELECT * FROM subcategories WHERE id = ?");
    $stmt->execute([$editId]);
    $editSubcategory = $stmt->fetch(PDO::FETCH_ASSOC);
}

// Handle Add/Update Subcategory
if ($isAdminOrManager && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $category_id = (int)($_POST['category_id'] ?? 0);
    $name = trim($_POST['name'] ?? '');
    $desc = trim($_POST['description'] ?? '');
    $subcategoryId = (int)($_POST['subcategory_id'] ?? 0);

    if ($category_id <= 0) {
        $errors[] = "Parent category is required.";
    }
    if ($name === '') {
        $errors[] = "Subcategory name is required.";
    }

    if (empty($errors)) {
        if ($subcategoryId > 0) {
            // Update existing subcategory
            $stmt = $pdo->prepare("UPDATE subcategories SET category_id = ?, name = ?, description = ? WHERE id = ?");
            if ($stmt->execute([$category_id, $name, $desc, $subcategoryId])) {
                $_SESSION['success_message'] = "Subcategory updated successfully.";
                header("Location: subcategories.php");
                exit;
            } else {
                $errors[] = "Failed to update subcategory.";
            }
        } else {
            // Insert new subcategory
            $stmt = $pdo->prepare("INSERT INTO subcategories (category_id, name, description) VALUES (?, ?, ?)");
            if ($stmt->execute([$category_id, $name, $desc])) {
                $_SESSION['success_message'] = "Subcategory added successfully.";
                header("Location: subcategories.php");
                exit;
            } else {
                $errors[] = "Failed to add subcategory.";
            }
        }
    }
}

// Fetch subcategories with category name
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
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
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
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0"><?= $editSubcategory ? 'Edit Subcategory' : 'Add Subcategory' ?></h5>
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
                        <input type="hidden" name="subcategory_id" value="<?= $editSubcategory['id'] ?? 0 ?>">
                        <div class="mb-3">
                            <label class="form-label">Parent Category</label>
                            <select name="category_id" class="form-select" required>
                                <option value="">-- Select Category --</option>
                                <?php foreach ($categories as $c): ?>
                                    <option value="<?= $c['id'] ?>"
                                        <?= (($editSubcategory['category_id'] ?? $_POST['category_id'] ?? '') == $c['id']) ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($c['name']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Subcategory Name</label>
                            <input type="text" name="name" class="form-control"
                                   value="<?= htmlspecialchars($editSubcategory['name'] ?? $_POST['name'] ?? '') ?>" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Description (optional)</label>
                            <input type="text" name="description" class="form-control"
                                   value="<?= htmlspecialchars($editSubcategory['description'] ?? $_POST['description'] ?? '') ?>">
                        </div>
                        <button type="submit" class="btn btn-primary">
                            <?= $editSubcategory ? 'Update Subcategory' : 'Save Subcategory' ?>
                        </button>
                        <?php if ($editSubcategory): ?>
                            <a href="subcategories.php" class="btn btn-secondary">Cancel</a>
                        <?php endif; ?>
                    </form>
                </div>
            </div>

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
                                <th>Actions</th>
                            </tr>
                            </thead>
                            <tbody>
                            <?php if (empty($subcategories)): ?>
                                <tr>
                                    <td colspan="6" class="text-center py-3">No subcategories found.</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($subcategories as $s): ?>
                                    <tr>
                                        <td><?= $s['id'] ?></td>
                                        <td><?= htmlspecialchars($s['name']) ?></td>
                                        <td><?= htmlspecialchars($s['category_name']) ?></td>
                                        <td><?= htmlspecialchars($s['description']) ?></td>
                                        <td><?= htmlspecialchars($s['created_at']) ?></td>
                                        <td>
                                            <a href="subcategories.php?edit=<?= $s['id'] ?>" class="btn btn-sm btn-warning">Edit</a>
                                            <a href="subcategories.php?delete=<?= $s['id'] ?>"
                                               class="btn btn-sm btn-danger"
                                               onclick="return confirm('Are you sure you want to delete this subcategory?')">Delete</a>
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
