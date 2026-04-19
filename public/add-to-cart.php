<?php
require_once __DIR__ . '/../app/config/config.php';
require_once __DIR__ . '/../app/helpers/functions.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . APP_URL . '/products.php');
    exit;
}

$db = Database::connect();
$productId = isset($_POST['product_id']) ? (int) $_POST['product_id'] : 0;
$quantity = isset($_POST['quantity']) ? (int) $_POST['quantity'] : 1;
$quantity = max(1, min(10, $quantity));

if ($productId <= 0) {
    header('Location: ' . APP_URL . '/products.php');
    exit;
}

$stmt = $db->prepare(
    "SELECT id, name, brand, default_price, thumbnail
     FROM products
     WHERE id = :id AND status = 'active'
     LIMIT 1"
);
$stmt->execute(['id' => $productId]);
$product = $stmt->fetch();

if (!$product) {
    header('Location: ' . APP_URL . '/products.php');
    exit;
}

if (!isset($_SESSION['cart']) || !is_array($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

if (isset($_SESSION['cart'][$productId])) {
    $_SESSION['cart'][$productId]['quantity'] += $quantity;
} else {
    $_SESSION['cart'][$productId] = [
        'id' => (int) $product['id'],
        'name' => $product['name'],
        'brand' => $product['brand'] ?: 'LUMINA',
        'price' => (float) $product['default_price'],
        'thumbnail' => $product['thumbnail'],
        'quantity' => $quantity,
    ];
}

header('Location: ' . APP_URL . '/cart.php?added=1');
exit;
