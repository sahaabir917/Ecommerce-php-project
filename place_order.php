<?php
// place_order.php
require 'config.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$roleName = strtolower($_SESSION['role_name'] ?? '');
$isCustomer = ($roleName === 'customer');

if (!$isCustomer) {
    die("Only customers can place orders.");
}

// 简单接收表单
$shipping_name    = trim($_POST['shipping_name'] ?? '');
$shipping_address = trim($_POST['shipping_address'] ?? '');
$shipping_phone   = trim($_POST['shipping_phone'] ?? '');
$payment_method   = trim($_POST['payment_method'] ?? 'cod');

if ($shipping_name === '' || $shipping_address === '' || $shipping_phone === '') {
    // 返回 checkout
    header("Location: checkout.php");
    exit;
}

// 读取购物车
$stmt = $pdo->prepare("
    SELECT c.id AS cart_id, c.quantity,
           p.id AS product_id, p.name, p.price, p.available_qty
    FROM cart c
    JOIN products p ON c.product_id = p.id
    WHERE c.user_id = ?
");
$stmt->execute([$_SESSION['user_id']]);
$items = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (empty($items)) {
    header("Location: cart.php");
    exit;
}

// 计算总价 & 简单库存检查
$total = 0;
foreach ($items as $it) {
    if ($it['quantity'] > $it['available_qty']) {
        // 简单处理：数量超出库存
        echo "Not enough stock for product: " . htmlspecialchars($it['name']);
        exit;
    }
    $total += $it['price'] * $it['quantity'];
}

// 创建 orders / order_items 表（如果不存在）
$pdo->exec("
    CREATE TABLE IF NOT EXISTS orders (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        total_amount DECIMAL(10,2) NOT NULL,
        shipping_name VARCHAR(255),
        shipping_address VARCHAR(255),
        shipping_phone VARCHAR(50),
        payment_method VARCHAR(20),
        payment_status VARCHAR(20),
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )
");

$pdo->exec("
    CREATE TABLE IF NOT EXISTS order_items (
        id INT AUTO_INCREMENT PRIMARY KEY,
        order_id INT NOT NULL,
        product_id INT NOT NULL,
        quantity INT NOT NULL,
        unit_price DECIMAL(10,2) NOT NULL,
        subtotal DECIMAL(10,2) NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )
");

// 开启事务
$pdo->beginTransaction();

try {
    // 插入 orders
    $orderStmt = $pdo->prepare("
        INSERT INTO orders 
        (user_id, total_amount, shipping_name, shipping_address, shipping_phone,
         payment_method, payment_status)
        VALUES (?, ?, ?, ?, ?, ?, ?)
    ");

    $orderStmt->execute([
        $_SESSION['user_id'],
        $total,
        $shipping_name,
        $shipping_address,
        $shipping_phone,
        $payment_method,
        'paid'  // 模拟已支付
    ]);

    $orderId = $pdo->lastInsertId();

    // 插入 order_items + 更新库存
    $itemStmt = $pdo->prepare("
        INSERT INTO order_items 
        (order_id, product_id, quantity, unit_price, subtotal)
        VALUES (?, ?, ?, ?, ?)
    ");

    $updateStockStmt = $pdo->prepare("
        UPDATE products SET available_qty = available_qty - ? WHERE id = ?
    ");

    foreach ($items as $it) {
        $sub = $it['price'] * $it['quantity'];

        $itemStmt->execute([
            $orderId,
            $it['product_id'],
            $it['quantity'],
            $it['price'],
            $sub
        ]);

        // 减库存
        $updateStockStmt->execute([
            $it['quantity'],
            $it['product_id']
        ]);
    }

    // 清空购物车
    $clearStmt = $pdo->prepare("DELETE FROM cart WHERE user_id = ?");
    $clearStmt->execute([$_SESSION['user_id']]);

    $pdo->commit();

    // 跳转到 成功页面
    header("Location: order_success.php?order_id=" . $orderId);
    exit;

} catch (Exception $e) {
    $pdo->rollBack();
    echo "Error placing order: " . $e->getMessage();
    exit;
}
