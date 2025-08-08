<?php
// ====== Redis Connection ======
$redis = new Redis();
$redis->connect('127.0.0.1', 6379);

// ====== User Info ======
$userId = 1001;
$userKey = "user:$userId";
$cartKey = "cart:$userId";

// Simpan data user
$redis->hMSet($userKey, [
    'name'  => 'Beny',
    'email' => 'ibrani.beny@gmail.com'
]);

// ====== Product Info ======
// Product Information
$products = [
    'sku123' => ['name' => 'iPhone 15', 'price' => 1500],
    'sku888' => ['name' => 'MacBook Air', 'price' => 1200],
    'sku555' => ['name' => 'Xiaomi 14 Pro', 'price' => 900]
];

foreach ($products as $sku => $info) {
    $redis->hMSet("product:$sku", $info);
}

// ====== Cart Functions ======
function addToCart($redis, $cartKey, $sku, $quantity = 1) {
    $redis->hIncrBy($cartKey, $sku, $quantity);
}

function removeFromCart($redis, $cartKey, $sku, $quantity = 1) {
    if ($redis->hExists($cartKey, $sku)) {
        $current = $redis->hGet($cartKey, $sku);
        $newQty = $current - $quantity;
        if ($newQty <= 0) {
            $redis->hDel($cartKey, $sku);
        } else {
            $redis->hSet($cartKey, $sku, $newQty);
        }
    }
}

// ====== Add Items to Cart ======
addToCart($redis, $cartKey, 'sku123', 2); // 2 iPhones
addToCart($redis, $cartKey, 'sku888', 1); // 1 Samsung
addToCart($redis, $cartKey, 'sku555', 3); // 3 Xiaomi

// Remove 1 iPhone
#removeFromCart($redis, $cartKey, 'sku123', 1);

// ====== Display User Info ======
echo "User Info:\n";
print_r($redis->hGetAll($userKey));

// ====== Display Cart with Product Info ======
echo "\n Cart Contents:\n";
$cart = $redis->hGetAll($cartKey);
$totalPrice = 0;

foreach ($cart as $sku => $qty) {
    $productInfo = $redis->hGetAll("product:$sku");
    $itemTotal = $qty * $productInfo['price'];
    $totalPrice += $itemTotal;

    echo "{$productInfo['name']} ($sku): $qty pcs @ \${$productInfo['price']} each → Total: \${$itemTotal}\n";
}

echo "\nGrand Total: \${$totalPrice}\n";
