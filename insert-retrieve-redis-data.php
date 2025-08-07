<?php
$redis = new Redis();
$redis->connect('127.0.0.1', 6379);

// === Insert values 1-100 into Redis list and print in reverse ===
$listKey = 'numbers';
$redis->del($listKey); // clear previous list
for ($i = 1; $i <= 100; $i++) {
    $redis->rPush($listKey, $i);
}
$reversed = array_reverse($redis->lRange($listKey, 0, -1));
echo "Values 1-100 in reverse order:\n";
print_r($reversed);

// === Insert 100 random values into Redis sorted set and print descending ===
$setKey = 'random_sorted';
$redis->del($setKey);
$used = [];
while (count($used) < 100) {
    $rand = rand(1, 1000);
    if (!in_array($rand, $used)) {
        $used[] = $rand;
        $redis->zAdd($setKey, $rand, $rand);
    }
}
$sortedDesc = $redis->zRevRange($setKey, 0, -1);
echo "\n100 random values (descending):\n";
print_r($sortedDesc);
