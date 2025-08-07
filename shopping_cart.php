<?php
$redis = new Redis();
$redis->connect('127.0.0.1', 6379);

$userId = 1001;
$cartKey = "cart:$userId";
$userKey = "user:$userId";

// Add user info
$redis->hMSet($userKey, [
    'name' => 'Beny',
    'email' => 'beny@example.com'
]);

// Add items to cart
$redis->hIncrBy($cartKey, 'sku123', 2);
$redis->hIncrBy($cartKey, 'sku888', 1);

// Show user info
echo "User info:\n";
print_r($redis->hGetAll($userKey));

// Show cart contents
echo "\nCart contents for user $userId:\n";
print_r($redis->hGetAll($cartKey));
