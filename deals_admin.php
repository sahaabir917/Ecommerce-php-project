<?php
require 'config.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$roleName = $_SESSION['role_name'] ?? '';
$isAdminOrManager = in_array($roleName, ['Admin', 'Manager'], true);

$errors = [];
$success = "";

// Check for success message from redirect
if (isset($_SESSION['deal_success'])) {
    $success = $_SESSION['deal_success'];
    unset($_SESSION['deal_success']);
}

// Handle Delete Deal
if ($isAdminOrManager && isset($_GET['delete'])) {
    $deleteId = (int)$_GET['delete'];

    try {
        $pdo->beginTransaction();

        // Delete deal_products first (due to foreign key)
        $stmt = $pdo->prepare("DELETE FROM deal_products WHERE deal_id = ?");
        $stmt->execute([$deleteId]);

        // Then delete the deal
        $stmt = $pdo->prepare("DELETE FROM deals WHERE id = ?");
        $stmt->execute([$deleteId]);

        $pdo->commit();

        $_SESSION['deal_success'] = "Deal deleted successfully.";
        header("Location: deals_admin.php");
        exit;
    } catch (Exception $e) {
        $pdo->rollBack();
        $errors[] = "Failed to delete deal: " . $e->getMessage();
    }
}

// Handle Add/Edit Deal
if ($isAdminOrManager && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $deal_id = (int)($_POST['deal_id'] ?? 0);
    $deal_name = trim($_POST['deal_name'] ?? '');

    // Validation
    if ($deal_name === '') {
        $errors[] = "Deal name is required.";
    }

    if (empty($errors)) {
        try {
            if ($deal_id > 0) {
                // Update existing deal
                $stmt = $pdo->prepare("UPDATE deals SET deal_name = ? WHERE id = ?");
                if ($stmt->execute([$deal_name, $deal_id])) {
                    $_SESSION['deal_success'] = "Deal updated successfully.";
                    header("Location: deals_admin.php");
                    exit;
                } else {
                    $errors[] = "Failed to update deal.";
                }
            } else {
                // Insert new deal
                $stmt = $pdo->prepare("INSERT INTO deals (deal_name) VALUES (?)");
                if ($stmt->execute([$deal_name])) {
                    $_SESSION['deal_success'] = "Deal added successfully.";
                    header("Location: deals_admin.php");
                    exit;
                } else {
                    $errors[] = "Failed to add deal.";
                }
            }
        } catch (Exception $e) {
            $errors[] = "Database error: " . $e->getMessage();
        }
    }
}

// Fetch deal for editing if edit parameter is present
$editDeal = null;
if ($isAdminOrManager && isset($_GET['edit'])) {
    $editId = (int)$_GET['edit'];
    $stmt = $pdo->prepare("SELECT * FROM deals WHERE id = ?");
    $stmt->execute([$editId]);
    $editDeal = $stmt->fetch(PDO::FETCH_ASSOC);
}

// Fetch all deals with product count
$dealsStmt = $pdo->query("
    SELECT d.id, d.deal_name, d.created_at,
           COUNT(dp.id) as product_count
    FROM deals d
    LEFT JOIN deal_products dp ON d.id = dp.deal_id
    GROUP BY d.id
    ORDER BY d.id DESC
");
$deals = $dealsStmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Deals Management</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
</head>
<body>
<div class="d-flex">
    <?php include 'sidebar.php'; ?>

    <main class="flex-grow-1 bg-light p-4">
        <h3 class="mb-3">Deals Management</h3>

        <?php if (!$isAdminOrManager): ?>
            <div class="alert alert-danger">
                You have no access to this URL.
            </div>
        <?php else: ?>
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0"><?= $editDeal ? 'Edit Deal' : 'Add Deal' ?></h5>
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
                        <?php if ($editDeal): ?>
                            <input type="hidden" name="deal_id" value="<?= $editDeal['id'] ?>">
                        <?php endif; ?>

                        <div class="mb-3">
                            <label class="form-label">Deal Name</label>
                            <input type="text" name="deal_name" class="form-control"
                                   value="<?= htmlspecialchars($editDeal['deal_name'] ?? $_POST['deal_name'] ?? '') ?>"
                                   placeholder="e.g., Winter Sale, Summer Clearance" required>
                        </div>

                        <button type="submit" class="btn btn-primary">
                            <?= $editDeal ? 'Update Deal' : 'Save Deal' ?>
                        </button>
                        <?php if ($editDeal): ?>
                            <a href="deals_admin.php" class="btn btn-secondary">Cancel</a>
                        <?php endif; ?>
                    </form>
                </div>
            </div>

            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">All Deals</h5>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-striped mb-0">
                            <thead class="table-light">
                            <tr>
                                <th>ID</th>
                                <th>Deal Name</th>
                                <th>Products Count</th>
                                <th>Created At</th>
                                <th>Actions</th>
                            </tr>
                            </thead>
                            <tbody>
                            <?php if (empty($deals)): ?>
                                <tr>
                                    <td colspan="5" class="text-center py-3">No deals found.</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($deals as $d): ?>
                                    <tr>
                                        <td><?= $d['id'] ?></td>
                                        <td><?= htmlspecialchars($d['deal_name']) ?></td>
                                        <td>
                                            <span class="badge bg-info"><?= $d['product_count'] ?> product(s)</span>
                                        </td>
                                        <td><?= htmlspecialchars(date('M d, Y h:i A', strtotime($d['created_at']))) ?></td>
                                        <td>
                                            <a href="deal_manage_products.php?deal_id=<?= $d['id'] ?>" class="btn btn-sm btn-primary">Manage Products</a>
                                            <a href="deals_admin.php?edit=<?= $d['id'] ?>" class="btn btn-sm btn-warning">Edit</a>
                                            <a href="deals_admin.php?delete=<?= $d['id'] ?>"
                                               class="btn btn-sm btn-danger"
                                               onclick="return confirm('Are you sure you want to delete this deal? This will also remove all associated products from this deal.')">Delete</a>
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
