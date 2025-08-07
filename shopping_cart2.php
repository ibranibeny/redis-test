<?php
$redis = new Redis();
$redis->connect('127.0.0.1', 6379);

// Simulate a user
$userId = 1001;
$userKey = "user:$userId";
$cartKey = "cart:$userId";

// Set user info
$redis->hMSet($userKey, [
    'name' => 'Beny',
    'email' => 'beny@example.com'
]);

// Add items to cart
function addToCart($redis, $cartKey, $sku, $quantity = 1) {
    $redis->hIncrBy($cartKey, $sku, $quantity);
}

// Remove item or decrement quantity
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

// Add SKUs
addToCart($redis, $cartKey, 'sku123', 2);
addToCart($redis, $cartKey, 'sku888', 1);

// Remove 1 unit of sku123
removeFromCart($redis, $cartKey, 'sku123', 1);

// Show user
echo "👤 User Info:\n";
print_r($redis->hGetAll($userKey));

// Show cart
echo "\n🛒 Cart Contents:\n";
$cart = $redis->hGetAll($cartKey);
foreach ($cart as $sku => $qty) {
    echo "$sku => $qty pcs\n";
}
?>
