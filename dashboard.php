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
if (isset($_SESSION['user_success'])) {
    $success = $_SESSION['user_success'];
    unset($_SESSION['user_success']);
}

// Handle Delete User
if ($isAdminOrManager && isset($_GET['delete'])) {
    $deleteId = (int)$_GET['delete'];

    // Prevent deleting yourself
    if ($deleteId == $_SESSION['user_id']) {
        $errors[] = "You cannot delete your own account.";
    } else {
        $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
        if ($stmt->execute([$deleteId])) {
            $_SESSION['user_success'] = "User deleted successfully.";
            header("Location: dashboard.php");
            exit;
        } else {
            $errors[] = "Failed to delete user.";
        }
    }
}

// Handle Add/Edit User
if ($isAdminOrManager && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $user_id = (int)($_POST['user_id'] ?? 0);
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $address = trim($_POST['address'] ?? '');
    $role_id = (int)($_POST['role_id'] ?? 3);
    $password = $_POST['password'] ?? '';
    $password_confirm = $_POST['password_confirm'] ?? '';

    // Validation
    if ($name === '') {
        $errors[] = "Name is required.";
    }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Valid email is required.";
    }
    if ($phone === '') {
        $errors[] = "Phone is required.";
    }
    if ($address === '') {
        $errors[] = "Address is required.";
    }

    // Password validation for new users
    if ($user_id == 0) {
        if (strlen($password) < 6) {
            $errors[] = "Password must be at least 6 characters.";
        }
        if ($password !== $password_confirm) {
            $errors[] = "Passwords do not match.";
        }
    } else {
        // Password validation for updates (only if password is provided)
        if ($password !== '') {
            if (strlen($password) < 6) {
                $errors[] = "Password must be at least 6 characters.";
            }
            if ($password !== $password_confirm) {
                $errors[] = "Passwords do not match.";
            }
        }
    }

    if (empty($errors)) {
        // Check if email already exists (exclude current user when editing)
        if ($user_id > 0) {
            $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
            $stmt->execute([$email, $user_id]);
        } else {
            $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
            $stmt->execute([$email]);
        }

        if ($stmt->fetch()) {
            $errors[] = "Email already exists.";
        } else {
            if ($user_id > 0) {
                // Update existing user
                if ($password !== '') {
                    // Update with new password
                    $password_hash = password_hash($password, PASSWORD_DEFAULT);
                    $stmt = $pdo->prepare("
                        UPDATE users
                        SET name = ?, email = ?, phone = ?, address = ?, role_id = ?, password = ?
                        WHERE id = ?
                    ");
                    $stmt->execute([$name, $email, $phone, $address, $role_id, $password_hash, $user_id]);
                } else {
                    // Update without changing password
                    $stmt = $pdo->prepare("
                        UPDATE users
                        SET name = ?, email = ?, phone = ?, address = ?, role_id = ?
                        WHERE id = ?
                    ");
                    $stmt->execute([$name, $email, $phone, $address, $role_id, $user_id]);
                }

                // Update session if editing own account
                if ($user_id == $_SESSION['user_id']) {
                    $_SESSION['user_name'] = $name;
                    $_SESSION['user_email'] = $email;

                    // Update role name in session
                    $stmt = $pdo->prepare("SELECT name FROM roles WHERE id = ?");
                    $stmt->execute([$role_id]);
                    $role = $stmt->fetch(PDO::FETCH_ASSOC);
                    if ($role) {
                        $_SESSION['role_name'] = $role['name'];
                    }
                }

                $_SESSION['user_success'] = "User updated successfully.";
                header("Location: dashboard.php");
                exit;

            } else {
                // Insert new user
                $password_hash = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("
                    INSERT INTO users (name, email, phone, address, password, role_id)
                    VALUES (?, ?, ?, ?, ?, ?)
                ");

                if ($stmt->execute([$name, $email, $phone, $address, $password_hash, $role_id])) {
                    $_SESSION['user_success'] = "User created successfully.";
                    header("Location: dashboard.php");
                    exit;
                } else {
                    $errors[] = "Failed to create user.";
                }
            }
        }
    }
}

// Fetch user for editing if edit parameter is present
$editUser = null;
if ($isAdminOrManager && isset($_GET['edit'])) {
    $editId = (int)$_GET['edit'];
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$editId]);
    $editUser = $stmt->fetch(PDO::FETCH_ASSOC);
}

// Fetch all roles for dropdown
$rolesStmt = $pdo->query("SELECT id, name FROM roles ORDER BY id ASC");
$roles = $rolesStmt->fetchAll(PDO::FETCH_ASSOC);

// Get selected week (default to current week)
$selectedWeek = $_GET['week'] ?? date('Y-\WW');

// Calculate the start and end date of the selected week
$weekYear = substr($selectedWeek, 0, 4);
$weekNumber = substr($selectedWeek, 6);
$startDate = new DateTime();
$startDate->setISODate($weekYear, $weekNumber);
$startDateStr = $startDate->format('Y-m-d');
$endDate = clone $startDate;
$endDate->modify('+6 days');
$endDateStr = $endDate->format('Y-m-d');

// Fetch daily sales for the selected week
$dailySalesStmt = $pdo->prepare("
    SELECT DATE(o.order_date) as sale_date,
           SUM(od.unit_price * od.quantity) as daily_total
    FROM orders o
    JOIN order_details od ON o.id = od.order_id
    WHERE DATE(o.order_date) BETWEEN ? AND ?
    GROUP BY DATE(o.order_date)
    ORDER BY sale_date ASC
");
$dailySalesStmt->execute([$startDateStr, $endDateStr]);
$dailySalesData = $dailySalesStmt->fetchAll(PDO::FETCH_ASSOC);

// Create array with all days of the week (even if no sales)
$dailySales = [];
$totalWeeklySales = 0;
$currentDate = clone $startDate;

for ($i = 0; $i < 7; $i++) {
    $dateStr = $currentDate->format('Y-m-d');
    $dayName = $currentDate->format('D'); // Mon, Tue, Wed, etc.

    // Find sales for this date
    $dayTotal = 0;
    foreach ($dailySalesData as $row) {
        if ($row['sale_date'] === $dateStr) {
            $dayTotal = (float)$row['daily_total'];
            break;
        }
    }

    $dailySales[] = [
        'date' => $dateStr,
        'day' => $dayName,
        'total' => $dayTotal
    ];

    $totalWeeklySales += $dayTotal;
    $currentDate->modify('+1 day');
}

// Generate weeks for dropdown (last 12 weeks)
$weekOptions = [];
$tempDate = new DateTime();
for ($i = 0; $i < 12; $i++) {
    $year = $tempDate->format('Y');
    $week = $tempDate->format('W');
    $weekValue = $year . '-W' . $week;
    $weekLabel = 'Week ' . $week . ', ' . $year;

    $tempStart = clone $tempDate;
    $tempStart->setISODate($year, $week);
    $tempEnd = clone $tempStart;
    $tempEnd->modify('+6 days');
    $weekLabel .= ' (' . $tempStart->format('M d') . ' - ' . $tempEnd->format('M d') . ')';

    $weekOptions[] = ['value' => $weekValue, 'label' => $weekLabel];
    $tempDate->modify('-1 week');
}

// Fetch all users
$users = [];
if ($isAdminOrManager) {
    $stmt = $pdo->query("
        SELECT u.id, u.name, u.email, u.phone, u.address, r.name AS role_name, u.role_id, u.created_at
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
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
</head>
<body>
<div class="d-flex">
    <?php include 'sidebar.php'; ?>

    <main class="flex-grow-1 bg-light p-4">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h3 class="mb-0">Dashboard</h3>
            <?php if ($isAdminOrManager): ?>
                <a href="index.php" class="btn btn-success">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-shop" viewBox="0 0 16 16">
                        <path d="M2.97 1.35A1 1 0 0 1 3.73 1h8.54a1 1 0 0 1 .76.35l2.609 3.044A1.5 1.5 0 0 1 16 5.37v.255a2.375 2.375 0 0 1-4.25 1.458A2.37 2.37 0 0 1 9.875 8 2.37 2.37 0 0 1 8 7.083 2.37 2.37 0 0 1 6.125 8a2.37 2.37 0 0 1-1.875-.917A2.375 2.375 0 0 1 0 5.625V5.37a1.5 1.5 0 0 1 .361-.976zm1.78 4.275a1.375 1.375 0 0 0 2.75 0 .5.5 0 0 1 1 0 1.375 1.375 0 0 0 2.75 0 .5.5 0 0 1 1 0 1.375 1.375 0 1 0 2.75 0V5.37a.5.5 0 0 0-.12-.325L12.27 2H3.73L1.12 5.045A.5.5 0 0 0 1 5.37v.255a1.375 1.375 0 0 0 2.75 0 .5.5 0 0 1 1 0M1.5 8.5A.5.5 0 0 1 2 9v6h1v-5a1 1 0 0 1 1-1h3a1 1 0 0 1 1 1v5h6V9a.5.5 0 0 1 1 0v6h.5a.5.5 0 0 1 0 1H.5a.5.5 0 0 1 0-1H1V9a.5.5 0 0 1 .5-.5M4 15h3v-5H4zm5-5a1 1 0 0 1 1-1h2a1 1 0 0 1 1 1v3a1 1 0 0 1-1 1h-2a1 1 0 0 1-1-1zm3 0h-2v3h2z"/>
                    </svg>
                    View Shop
                </a>
            <?php endif; ?>
        </div>

        <?php if ($isAdminOrManager): ?>
            <p class="text-muted">
                You are an <strong><?= htmlspecialchars($roleName) ?></strong>.
                You can manage users, categories, subcategories, and products.
            </p>

            <!-- Weekly Sales Analytics Section -->
            <div class="card mb-4">
                <div class="card-header bg-primary text-white">
                    <div class="d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">Weekly Sales Analytics</h5>
                        <form method="get" class="d-flex align-items-center gap-2">
                            <label class="mb-0 text-white">Select Week:</label>
                            <select name="week" class="form-select form-select-sm" style="width: auto;" onchange="this.form.submit()">
                                <?php foreach ($weekOptions as $wo): ?>
                                    <option value="<?= htmlspecialchars($wo['value']) ?>"
                                        <?= $wo['value'] === $selectedWeek ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($wo['label']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </form>
                    </div>
                </div>
                <div class="card-body">
                    <div class="row mb-4">
                        <div class="col-md-12">
                            <div class="alert alert-info mb-0">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <strong>Total Sales for Selected Week:</strong>
                                        <span class="fs-4 ms-2">$<?= number_format($totalWeeklySales, 2) ?></span>
                                    </div>
                                    <div class="text-muted">
                                        <?= $startDate->format('M d, Y') ?> - <?= $endDate->format('M d, Y') ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-12">
                            <canvas id="salesChart" height="80"></canvas>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0"><?= $editUser ? 'Edit User' : 'Add New User' ?></h5>
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
                        <?php if ($editUser): ?>
                            <input type="hidden" name="user_id" value="<?= $editUser['id'] ?>">
                        <?php endif; ?>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Full Name</label>
                                <input type="text" name="name" class="form-control"
                                       value="<?= htmlspecialchars($editUser['name'] ?? $_POST['name'] ?? '') ?>" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Email Address</label>
                                <input type="email" name="email" class="form-control"
                                       value="<?= htmlspecialchars($editUser['email'] ?? $_POST['email'] ?? '') ?>" required>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Phone</label>
                                <input type="text" name="phone" class="form-control"
                                       value="<?= htmlspecialchars($editUser['phone'] ?? $_POST['phone'] ?? '') ?>" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Role</label>
                                <select name="role_id" class="form-select" required>
                                    <?php foreach ($roles as $r): ?>
                                        <option value="<?= $r['id'] ?>"
                                            <?= (($editUser['role_id'] ?? $_POST['role_id'] ?? 3) == $r['id']) ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($r['name']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Address</label>
                            <input type="text" name="address" class="form-control"
                                   value="<?= htmlspecialchars($editUser['address'] ?? $_POST['address'] ?? '') ?>" required>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Password <?= $editUser ? '(leave blank to keep current)' : '' ?></label>
                                <input type="password" name="password" class="form-control"
                                       <?= $editUser ? '' : 'required' ?>>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Confirm Password</label>
                                <input type="password" name="password_confirm" class="form-control"
                                       <?= $editUser ? '' : 'required' ?>>
                            </div>
                        </div>

                        <button type="submit" class="btn btn-primary">
                            <?= $editUser ? 'Update User' : 'Create User' ?>
                        </button>
                        <?php if ($editUser): ?>
                            <a href="dashboard.php" class="btn btn-secondary">Cancel</a>
                        <?php endif; ?>
                    </form>
                </div>
            </div>

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
                                <th>Actions</th>
                            </tr>
                            </thead>
                            <tbody>
                            <?php if (empty($users)): ?>
                                <tr>
                                    <td colspan="8" class="text-center py-3">No users found.</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($users as $u): ?>
                                    <tr>
                                        <td><?= $u['id'] ?></td>
                                        <td><?= htmlspecialchars($u['name']) ?></td>
                                        <td><?= htmlspecialchars($u['email']) ?></td>
                                        <td><?= htmlspecialchars($u['phone']) ?></td>
                                        <td><?= htmlspecialchars($u['address']) ?></td>
                                        <td>
                                            <span class="badge bg-<?= $u['role_name'] == 'Admin' ? 'danger' : ($u['role_name'] == 'Manager' ? 'warning' : 'secondary') ?>">
                                                <?= htmlspecialchars($u['role_name']) ?>
                                            </span>
                                        </td>
                                        <td><?= htmlspecialchars($u['created_at']) ?></td>
                                        <td>
                                            <a href="dashboard.php?edit=<?= $u['id'] ?>" class="btn btn-sm btn-warning">Edit</a>
                                            <?php if ($u['id'] != $_SESSION['user_id']): ?>
                                                <a href="dashboard.php?delete=<?= $u['id'] ?>"
                                                   class="btn btn-sm btn-danger"
                                                   onclick="return confirm('Are you sure you want to delete this user?')">Delete</a>
                                            <?php endif; ?>
                                        </td>
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
                    <p class="text-muted mb-0">Contact an Admin or Manager if you need higher access.</p>
                </div>
            </div>
        <?php endif; ?>
    </main>
</div>

<?php if ($isAdminOrManager): ?>
<script>
    // Prepare data for Chart.js
    const salesData = <?= json_encode($dailySales) ?>;
    const labels = salesData.map(day => `${day.day}\n${day.date}`);
    const data = salesData.map(day => day.total);

    // Create the bar chart
    const ctx = document.getElementById('salesChart').getContext('2d');
    const salesChart = new Chart(ctx, {
        type: 'bar',
        data: {
            labels: labels,
            datasets: [{
                label: 'Daily Sales ($)',
                data: data,
                backgroundColor: 'rgba(54, 162, 235, 0.6)',
                borderColor: 'rgba(54, 162, 235, 1)',
                borderWidth: 1
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: true,
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        callback: function(value) {
                            return '$' + value.toFixed(2);
                        }
                    }
                }
            },
            plugins: {
                legend: {
                    display: true,
                    position: 'top'
                },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            return 'Sales: $' + context.parsed.y.toFixed(2);
                        }
                    }
                }
            }
        }
    });
</script>
<?php endif; ?>
</body>
</html>
